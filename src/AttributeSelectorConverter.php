<?php

namespace Gt\CssXPath;

class AttributeSelectorConverter {
	/** @param array<string, mixed> $token */
	public function apply(
		array $token,
		XPathExpression $expression,
		bool $htmlMode
	):void {
		$expression->ensureElement();

		$attribute = (string)$token["content"];
		if($htmlMode) {
			$attribute = strtolower($attribute);
		}

		$detail = $token["detail"] ?? null;
		$detailType = $detail[0] ?? null;
		$detailValue = $detail[1] ?? null;

		if(!$this->hasEqualsType($detailType)) {
			$expression->appendFragment("[@{$attribute}]");
			return;
		}

		$valueString = trim((string)$detailValue["content"], " '\"");
		$equalsType = $detailType["content"];
		$expression->appendFragment(
			$this->buildExpression($attribute, $valueString, $equalsType)
		);
	}

	/** @param array<string, mixed> $token */
	public function buildConditionFromToken(array $token, bool $htmlMode):string {
		$attribute = (string)$token["content"];
		if($htmlMode) {
			$attribute = strtolower($attribute);
		}

		$detail = $token["detail"] ?? null;
		$detailType = $detail[0] ?? null;
		$detailValue = $detail[1] ?? null;

		if(!$this->hasEqualsType($detailType)) {
			return "@{$attribute}";
		}

		$valueString = trim((string)$detailValue["content"], " '\"");
		$equalsType = $detailType["content"];
		return $this->buildCondition($attribute, $valueString, $equalsType);
	}

	/** @param array<string, mixed>|null $detailType */
	private function hasEqualsType(?array $detailType):bool {
		return isset($detailType["type"])
			&& $detailType["type"] === "attribute_equals";
	}

	private function buildCondition(
		string $attribute,
		string $value,
		string $equalsType
	):string {
		return match($equalsType) {
			Translator::EQUALS_EXACT => "@{$attribute}=\"{$value}\"",
			Translator::EQUALS_CONTAINS => "contains(@{$attribute},\"{$value}\")",
			Translator::EQUALS_CONTAINS_WORD => ""
				. "contains(concat(\" \",@{$attribute},\" \"),"
				. "concat(\" \",\"{$value}\",\" \"))"
				. "",
			Translator::EQUALS_OR_STARTS_WITH_HYPHENATED => ""
				. "@{$attribute}=\"{$value}\" or "
				. "starts-with(@{$attribute}, \"{$value}-\")"
				. "",
			Translator::EQUALS_STARTS_WITH => ""
				. "starts-with(@{$attribute}, \"{$value}\")"
				. "",
				Translator::EQUALS_ENDS_WITH => ""
				. "substring(@{$attribute},"
				. "string-length(@{$attribute}) - "
				. "string-length(\"{$value}\") + 1)"
				. "=\"{$value}\""
				. "",
			default => "@{$attribute}",
		};
	}

	private function buildExpression(
		string $attribute,
		string $value,
		string $equalsType
	):string {
		return "[" . $this->buildCondition($attribute, $value, $equalsType) . "]";
	}
}
