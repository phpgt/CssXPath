<?php

namespace Gt\CssXPath;

class NotSelectorConditionBuilder {
	private ThreadMatcher $threadMatcher;
	private AttributeSelectorConverter $attributeSelectorConverter;

	public function __construct(
		?ThreadMatcher $threadMatcher = null,
		?AttributeSelectorConverter $attributeSelectorConverter = null,
	) {
		$this->threadMatcher = $threadMatcher ?? new ThreadMatcher();
		$this->attributeSelectorConverter = $attributeSelectorConverter
			?? new AttributeSelectorConverter();
	}

	public function build(string $selector, bool $htmlMode):?string {
		$selector = trim($selector);
		if($selector === "") {
			return null;
		}

		$thread = array_values(
			$this->threadMatcher->collate(Translator::CSS_REGEX, $selector)
		);
		if(!$this->isSupportedThread($thread)) {
			return null;
		}

		$token = $thread[0];
		$next = $thread[1] ?? null;
		return $this->buildConditionFromToken($token, $next, $htmlMode);
	}

	/** @param array<int, array<string, mixed>> $thread */
	private function isSupportedThread(array $thread):bool {
		if(empty($thread) || count($thread) > 2) {
			return false;
		}

		foreach($thread as $token) {
			if($this->isAxisToken((string)$token["type"])) {
				return false;
			}
		}

		return true;
	}

	private function isAxisToken(string $type):bool {
		return in_array($type, [
			"descendant",
			"child",
			"sibling",
			"subsequentsibling",
		], true);
	}

	/**
	 * @param array<string, mixed> $token
	 * @param array<string, mixed>|null $next
	 */
	private function buildConditionFromToken(
		array $token,
		?array $next,
		bool $htmlMode
	):?string {
		return match($token["type"]) {
			"element", "star" => $this->buildElementCondition(
				(string)$token["content"],
				$htmlMode
			),
			"id" => "@id='" . $token["content"] . "'",
			"class" => ""
				. "contains(concat(' ',normalize-space(@class),' '),"
				. "' " . $token["content"] . " ')",
			"attribute" => $this
				->attributeSelectorConverter
				->buildConditionFromToken($token, $htmlMode),
			"pseudo" => $this->buildPseudoCondition($token, $next),
			default => null,
		};
	}

	/**
	 * @param array<string, mixed> $token
	 * @param array<string, mixed>|null $next
	 */
	private function buildPseudoCondition(array $token, ?array $next):?string {
		$pseudo = (string)$token["content"];
		$specifier = $this->extractSpecifier($next);

		if(in_array($pseudo, ["disabled", "checked", "selected"], true)) {
			return "@{$pseudo}";
		}

		return match($pseudo) {
			"text" => '@type="text"',
			"contains" => $specifier !== ""
				? "contains(text(),{$specifier})"
				: null,
			"first-child", "first-of-type" => "position() = 1",
			"nth-child", "nth-of-type" => $specifier !== ""
				? "position() = {$specifier}"
				: null,
			"last-child", "last-of-type" => "position() = last()",
			default => null,
		};
	}

	private function buildElementCondition(string $name, bool $htmlMode):string {
		if($name === "*") {
			return "self::*";
		}

		$element = $htmlMode ? strtolower($name) : $name;
		return "self::{$element}";
	}

	/** @param array<string, mixed>|null $next */
	private function extractSpecifier(?array $next):string {
		if(!$next || $next["type"] !== "pseudospecifier") {
			return "";
		}

		return (string)$next["content"];
	}
}
