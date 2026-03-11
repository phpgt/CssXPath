<?php

namespace Gt\CssXPath;

class ThreadMatcher {
	/** @return array<int, array<string, mixed>> */
	public function collate(
		string $regex,
		string $string,
		?callable $transform = null
	):array {
		if($regex === Translator::CSS_REGEX) {
			return $this->collateCssSelector($string, $transform);
		}

		preg_match_all(
			$regex,
			$string,
			$matches,
			PREG_PATTERN_ORDER
		);

		$set = $this->initialiseSet($matches[0]);

		foreach($matches as $key => $matchedGroup) {
			if(is_numeric($key)) {
				continue;
			}

			$this->collateGroup($set, $key, $matchedGroup, $transform);
		}

		return $set;
	}

	/** @return array<int, array<string, mixed>> */
	private function collateCssSelector(
		string $selector,
		?callable $transform
	):array {
		$tokens = [];
		$length = strlen($selector);

		for($index = 0; $index < $length;) {
			$char = $selector[$index];

			if(ctype_space($char)) {
				$index = $this->consumeWhitespace(
					$selector,
					$index,
					$tokens,
					$transform
				);
				continue;
			}

			if($char === "*") {
				$tokens[] = $this->buildMatchPayload("star", "*", $transform);
				$index++;
				continue;
			}

			if($char === ">") {
				$tokens[] = $this->buildMatchPayload("child", ">", $transform);
				$index++;
				continue;
			}

			if($char === "+") {
				$tokens[] = $this->buildMatchPayload("sibling", "+", $transform);
				$index++;
				continue;
			}

			if($char === "~") {
				$tokens[] = $this->buildMatchPayload(
					"subsequentsibling",
					"~",
					$transform
				);
				$index++;
				continue;
			}

			if($char === "#") {
				[$identifier, $index] = $this->readIdentifier($selector, $index + 1);
				$tokens[] = $this->buildMatchPayload("id", $identifier, $transform);
				continue;
			}

			if($char === ".") {
				[$className, $index] = $this->readIdentifier($selector, $index + 1);
				$tokens[] = $this->buildMatchPayload("class", $className, $transform);
				continue;
			}

			if($char === ":") {
				[$pseudoTokens, $index] = $this->readPseudo(
					$selector,
					$index,
					$transform
				);
				array_push($tokens, ...$pseudoTokens);
				continue;
			}

			if($char === "[") {
				[$attributeToken, $index] = $this->readAttribute(
					$selector,
					$index,
					$transform
				);
				$tokens[] = $attributeToken;
				continue;
			}

			if($this->isIdentifierCharacter($char)) {
				[$element, $index] = $this->readIdentifier($selector, $index);
				$tokens[] = $this->buildMatchPayload(
					"element",
					$element,
					$transform
				);
				continue;
			}

			$index++;
		}

		return $tokens;
	}

	/**
	 * @param array<int, string> $matches
	 * @return array<int, array<string, mixed>|null>
	 */
	private function initialiseSet(array $matches):array {
		$set = [];

		foreach($matches as $index => $value) {
			if($value !== "") {
				$set[$index] = null;
			}
		}

		return $set;
	}

	/**
	 * @param array<int, array<string, mixed>|null> $set
	 * @param array<int, string> $matchedGroup
	 */
	private function collateGroup(
		array &$set,
		string $groupKey,
		array $matchedGroup,
		?callable $transform
	):void {
		foreach($matchedGroup as $index => $match) {
			if($match === "") {
				continue;
			}

			$toSet = $this->buildMatchPayload($groupKey, $match, $transform);
			$this->appendMatch($set, $index, $toSet);
		}
	}

	/** @return array<string, string> */
	private function buildMatchPayload(
		string $groupKey,
		string $match,
		?callable $transform
	):array {
		if($transform) {
			return $transform($groupKey, $match);
		}

		return ["type" => $groupKey, "content" => $match];
	}

	/**
	 * @param array<int, array<string, mixed>|null> $set
	 * @param array<string, string> $toSet
	 */
	private function appendMatch(array &$set, int $index, array $toSet):void {
		if(!isset($set[$index])) {
			$set[$index] = $toSet;
			return;
		}

		if(!isset($set[$index]["detail"])) {
			$set[$index]["detail"] = [];
		}

		$set[$index]["detail"][] = $toSet;
	}

	/**
	 * @param array<int, array<string, mixed>> $tokens
	 */
	private function consumeWhitespace(
		string $selector,
		int $index,
		array &$tokens,
		?callable $transform
	):int {
		$length = strlen($selector);
		$nextIndex = $index;
		while($nextIndex < $length && ctype_space($selector[$nextIndex])) {
			$nextIndex++;
		}

		if($this->shouldEmitDescendantToken($selector, $tokens, $nextIndex)) {
			$tokens[] = $this->buildMatchPayload("descendant", " ", $transform);
		}

		return $nextIndex;
	}

	/**
	 * @param array<int, array<string, mixed>> $tokens
	 */
	private function shouldEmitDescendantToken(
		string $selector,
		array $tokens,
		int $nextIndex
	):bool {
		if(empty($tokens) || !isset($selector[$nextIndex])) {
			return false;
		}

		$nextChar = $selector[$nextIndex];
		if(in_array($nextChar, [">", "+", "~", ",", ")"], true)) {
			return false;
		}

		$previousType = (string)$tokens[array_key_last($tokens)]["type"];
		return !in_array($previousType, [
			"child",
			"sibling",
			"subsequentsibling",
			"descendant",
		], true);
	}

	/** @return array{0: string, 1: int} */
	private function readIdentifier(string $selector, int $index):array {
		$length = strlen($selector);
		$identifier = "";

		while($index < $length && $this->isIdentifierCharacter($selector[$index])) {
			$identifier .= $selector[$index];
			$index++;
		}

		return [$identifier, $index];
	}

	/**
	 * @return array{0: array<int, array<string, mixed>>, 1: int}
	 */
	private function readPseudo(
		string $selector,
		int $index,
		?callable $transform
	):array {
		$tokens = [];
		$isPseudoElement = isset($selector[$index + 1])
			&& $selector[$index + 1] === ":";
		$nameStart = $index + ($isPseudoElement ? 2 : 1);
		[$name, $nextIndex] = $this->readIdentifier($selector, $nameStart);

		$tokens[] = $this->buildMatchPayload(
			$isPseudoElement ? "pseudo-element" : "pseudo",
			$name,
			$transform
		);

		if(isset($selector[$nextIndex]) && $selector[$nextIndex] === "(") {
			[$content, $nextIndex] = $this->readBalancedContent(
				$selector,
				$nextIndex,
				"(",
				")"
			);
			$tokens[] = $this->buildMatchPayload(
				"pseudospecifier",
				$content,
				$transform
			);
		}

		return [$tokens, $nextIndex];
	}

	/**
	 * @return array{0: array<string, mixed>, 1: int}
	 */
	private function readAttribute(
		string $selector,
		int $index,
		?callable $transform
	):array {
		[$content, $nextIndex] = $this->readBalancedContent(
			$selector,
			$index,
			"[",
			"]"
		);
		$token = $this->buildAttributeToken($content, $transform);
		return [$token, $nextIndex];
	}

	/** @return array{0: string, 1: int} */
	private function readBalancedContent(
		string $selector,
		int $startIndex,
		string $open,
		string $close
	):array {
		$length = strlen($selector);
		$depth = 1;
		$content = "";
		$quote = null;

		for($index = $startIndex + 1; $index < $length; $index++) {
			$char = $selector[$index];

			if($quote !== null) {
				$content .= $char;
				if($char === $quote) {
					$quote = null;
				}
				continue;
			}

			if($char === "'" || $char === '"') {
				$quote = $char;
				$content .= $char;
				continue;
			}

			if($char === $open) {
				$depth++;
				$content .= $char;
				continue;
			}

			if($char === $close) {
				$depth--;
				if($depth === 0) {
					return [$content, $index + 1];
				}

				$content .= $char;
				continue;
			}

			$content .= $char;
		}

		return [$content, $length];
	}

	/** @return array<string, mixed> */
	private function buildAttributeToken(
		string $content,
		?callable $transform
	):array {
		$operatorData = $this->extractAttributeOperator($content);
		$token = $this->buildMatchPayload(
			"attribute",
			$operatorData["name"],
			$transform
		);

		if($operatorData["operator"] === null) {
			return $token;
		}

		$token["detail"] = [
			$this->buildMatchPayload(
				"attribute_equals",
				$operatorData["operator"],
				$transform
			),
			$this->buildMatchPayload(
				"attribute_value",
				$operatorData["value"],
				$transform
			),
		];
		return $token;
	}

	/**
	 * @return array{name: string, operator: string|null, value: string}
	 */
	private function extractAttributeOperator(string $content):array {
		$operators = ["~=", "$=", "|=", "^=", "*=", "="];
		$quote = null;
		$length = strlen($content);

		for($index = 0; $index < $length; $index++) {
			$char = $content[$index];
			if($quote !== null) {
				if($char === $quote) {
					$quote = null;
				}
				continue;
			}

			if($char === "'" || $char === '"') {
				$quote = $char;
				continue;
			}

			foreach($operators as $operator) {
				if(substr($content, $index, strlen($operator)) !== $operator) {
					continue;
				}

				return [
					"name" => trim(substr($content, 0, $index)),
					"operator" => $operator,
					"value" => trim(substr($content, $index + strlen($operator))),
				];
			}
		}

		return [
			"name" => trim($content),
			"operator" => null,
			"value" => "",
		];
	}

	private function isIdentifierCharacter(string $char):bool {
		return preg_match('/[\w-]/', $char) === 1;
	}
}
