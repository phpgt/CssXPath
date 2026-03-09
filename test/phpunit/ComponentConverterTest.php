<?php

namespace Gt\CssXPath\Test;

use Gt\CssXPath\AttributeSelectorConverter;
use Gt\CssXPath\PseudoSelectorConverter;
use Gt\CssXPath\SingleSelectorConverter;
use Gt\CssXPath\ThreadMatcher;
use Gt\CssXPath\Translator;
use Gt\CssXPath\XPathExpression;
use PHPUnit\Framework\TestCase;

class ComponentConverterTest extends TestCase {
	public function testThreadMatcherCollatesAttributeDetailParts():void {
		$matcher = new ThreadMatcher();
		$thread = array_values(
			$matcher->collate(Translator::CSS_REGEX, "[name='email']")
		);

		self::assertSame("attribute", $thread[0]["type"]);
		self::assertSame("name", $thread[0]["content"]);
		self::assertSame("attribute_equals", $thread[0]["detail"][0]["type"]);
		self::assertSame("=", $thread[0]["detail"][0]["content"]);
		self::assertSame("attribute_value", $thread[0]["detail"][1]["type"]);
		self::assertSame("'email'", $thread[0]["detail"][1]["content"]);
	}

	public function testThreadMatcherUsesTransformForPrimaryAndDetailMatches():void {
		$matcher = new ThreadMatcher();
		$thread = array_values(
			$matcher->collate(
				Translator::CSS_REGEX,
				"[name=email]",
				fn(string $type, string $content):array => [
					"type" => strtoupper($type),
					"content" => "v:" . $content,
				]
			)
		);

		self::assertSame("ATTRIBUTE", $thread[0]["type"]);
		self::assertSame("v:name", $thread[0]["content"]);
		self::assertSame("ATTRIBUTE_EQUALS", $thread[0]["detail"][0]["type"]);
		self::assertSame("v:=", $thread[0]["detail"][0]["content"]);
	}

	public function testPseudoSelectorConverterContainsRequiresSpecifier():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("p", true);

			$converter->apply(
				["type" => "pseudo", "content" => "contains"],
				null,
				$expression,
				true
			);
		self::assertSame(".//p", $expression->toString());

			$converter->apply(
				["type" => "pseudo", "content" => "contains"],
				["type" => "pseudospecifier", "content" => "'Example'"],
				$expression,
				true
			);
		self::assertSame(".//p[contains(text(),'Example')]", $expression->toString());
	}

	public function testPseudoSelectorConverterNthChildCanRefineExistingPredicate():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("li", true);
		$expression->appendFragment("[contains(@class,\"selected\")]");

			$converter->apply(
				["type" => "pseudo", "content" => "nth-child"],
				["type" => "pseudospecifier", "content" => "2"],
				$expression,
				true
			);

		self::assertSame(
			".//li[contains(@class,\"selected\") and position() = 2]",
			$expression->toString()
		);
	}

	public function testAttributeSelectorConverterHonoursHtmlModeCaseSensitivity():void {
		$converter = new AttributeSelectorConverter();

		$htmlExpression = new XPathExpression(".//");
		$converter->apply(
			["type" => "attribute", "content" => "DATA-FOO"],
			$htmlExpression,
			true
		);
		self::assertSame(".//*[@data-foo]", $htmlExpression->toString());

		$xmlExpression = new XPathExpression("//");
		$converter->apply(
			["type" => "attribute", "content" => "DATA-FOO"],
			$xmlExpression,
			false
		);
		self::assertSame("//*[@DATA-FOO]", $xmlExpression->toString());
	}

	public function testAttributeSelectorConverterBuildsHyphenatedPrefixExpression():void {
		$converter = new AttributeSelectorConverter();
		$expression = new XPathExpression(".//");

		$converter->apply(
			[
				"type" => "attribute",
				"content" => "lang",
				"detail" => [
					["type" => "attribute_equals", "content" => "|="],
					["type" => "attribute_value", "content" => "en"],
				],
			],
			$expression,
			true
		);

		self::assertSame(
			".//*[@lang=\"en\" or starts-with(@lang, \"en-\")]",
			$expression->toString()
		);
	}

	public function testPseudoSelectorConverterNotBuildsElementAndClassCondition():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("li", true);

		$converter->apply(
			["type" => "pseudo", "content" => "not"],
			["type" => "pseudospecifier", "content" => ".selected"],
			$expression,
			true
		);

		self::assertSame(
			".//li[not(contains(concat(' ',normalize-space(@class),' '),' selected '))]",
			$expression->toString()
		);
	}

	public function testPseudoSelectorConverterNotSupportsSelectorLists():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("li", true);

		$converter->apply(
			["type" => "pseudo", "content" => "not"],
			["type" => "pseudospecifier", "content" => ".selected, [data-state='hidden']"],
			$expression,
			true
		);

		self::assertSame(
			".//li[not((contains(concat(' ',normalize-space(@class),' '),' selected ') or @data-state=\"hidden\"))]",
			$expression->toString()
		);
	}

	public function testPseudoSelectorConverterNotIgnoresUnsupportedComplexSelector():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("li", true);

		$converter->apply(
			["type" => "pseudo", "content" => "not"],
			["type" => "pseudospecifier", "content" => "div span"],
			$expression,
			true
		);

		self::assertSame(".//li", $expression->toString());
	}

	public function testPseudoSelectorConverterHasBuildsDescendantCondition():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("section", true);

		$converter->apply(
			["type" => "pseudo", "content" => "has"],
			["type" => "pseudospecifier", "content" => "h1"],
			$expression,
			true
		);

		self::assertSame(".//section[.//h1]", $expression->toString());
	}

	public function testPseudoSelectorConverterHasBuildsChildCondition():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("section", true);

		$converter->apply(
			["type" => "pseudo", "content" => "has"],
			["type" => "pseudospecifier", "content" => "> h1"],
			$expression,
			true
		);

		self::assertSame(".//section[./h1]", $expression->toString());
	}

	public function testPseudoSelectorConverterHasCombinesSelectorListWithOr():void {
		$converter = new PseudoSelectorConverter();
		$expression = new XPathExpression(".//");
		$expression->appendElement("section", true);

		$converter->apply(
			["type" => "pseudo", "content" => "has"],
			["type" => "pseudospecifier", "content" => "h1, h2"],
			$expression,
			true
		);

		self::assertSame(
			".//section[(.//h1) or (.//h2)]",
			$expression->toString()
		);
	}

	public function testSingleSelectorConverterHandlesWildcardClassAndIdSelectors():void {
		$converter = new SingleSelectorConverter();

		self::assertSame(
			".//*[@id='the-title']",
			$converter->convert("#the-title", ".//", true)
		);
		self::assertSame(
			".//*[contains(concat(' ',normalize-space(@class),' '),' c-menu ')]",
			$converter->convert(".c-menu", ".//", true)
		);
	}

	public function testSingleSelectorConverterSupportsPseudoTextOnInput():void {
		$converter = new SingleSelectorConverter();

		self::assertSame(
			".//input[@type=\"text\"]",
			$converter->convert("input:text", ".//", true)
		);
	}

	public function testXPathExpressionMarksAxisTransitionsBeforeEnsuringElement():void {
		$expression = new XPathExpression(".//");
		$expression->appendElement("main", true);
		$expression->appendFragment("/");
		$expression->markElementMissing();
		$expression->ensureElement();

		self::assertSame(".//main/*", $expression->toString());
	}
}
