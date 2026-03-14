<?php
namespace Gt\CssXPath\Test\Helper;

class Helper {
	const HTML_SIMPLE = <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>HTML Simple</title>
</head>
<body>
	<h1 id="the-title">HTML Simple</h1>
	<p>This is a <em>very</em> simple HTML document for testing the basics.</p>
</body>
</html>
HTML;

	const HTML_COMPLEX = <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>HTML Complex</title>
</head>
<body>

<header>
	<h1 class="c-logo">
		<a href="/">
			<span>Site logo</span>
		</a>
	</h1>

	<nav class="c-menu main-selection">
		<input type="checkbox" id="toggle-menu" />
		<label for="toggle-menu">
			<span>Menu</span>
		</label>

		<ul>
			<li>
				<a href="/">Home</a>
			</li>
			<li class="selected">
				<a href="/blog">Blog</a>
			</li>
			<li>
				<a href="/about">About</a>
			</li>
			<li>
				<a href="/contact">Contact</a>
			</li>
		</ul>
	</nav>
</header>

<main>
	<p>I'm a paragraph, but I'm not part of the article.</p>

	<article>
		<header>
			<h1>
				<a href="/blog/2018/04/27/first-example-article-title">
					First example article title
				</a>
			</h1>
			<time datetime="2018-04-27 02:24:00">27th April 2018</time>
		</header>
			<div
				id="content-element"
				class="content this-is-a-test"
				data-categories="example test blog-test"
				data-test-thing="my_test">
			<p>Example article paragraph 1.</p>
			<p>Example article paragraph 2.</p>
			<p>Example article paragraph 3.</p>
		</div>
		<div class="details" data-test-thing="another-test">
			<p>Here are some details: 12345</p>
		</div>
	</article>
</main>

<footer>
	<form method="post">
		<label>
			<span>Your name</span>
			<input name="your-name" placeholder="e.g. John Smith" required />
		</label>
		<label>
			<span>Your email address</span>
				<input
					name="email"
					type="email"
					placeholder="e.g. j.smith@email.com"
					required />
		</label>
		<label>
			<span data-ga-client="(Test) Message, this has a comma">Your message</span>
			<textarea></textarea>
		</label>
		<label>
			<span>Spam me with marketing?</span>
			<input type="checkbox" name="marketing" checked />
		</label>
		<button name="do" value="contact">Send</button>
	</form>
</footer>

</body>
</html>
HTML;

	const HTML_SELECTS = <<<HTML
<!doctype html>
<form>
	<label>
		<span>From:</span>
		<select name="from">
			<option>0</option>
			<option>1</option>
			<option>2</option>
			<option>3</option>
			<option>4</option>
			<option>5</option>
		</select>
	</label>

	<label>
		<span>To:</span>
		<select name="to">
			<option>5</option>
			<option>6</option>
			<option>7</option>
			<option>8</option>
			<option>9</option>
			<option>10</option>
		</select>
	</label>
</form>
HTML;

	const HTML_CHECKBOX = <<<HTML
<!doctype html>
<form method="post">
	<p>Please select and of the following:</p>

	<label>
		<input type="checkbox" name="choice[]" value="1" />
		<span>Choice 1</span>
	</label>
	<label>
		<input type="checkbox" name="choice[]" value="2" />
		<span>Choice 2</span>
	</label>
	<label>
		<input type="checkbox" name="choice[]" value="3" />
		<span>Choice 3</span>
	</label>

	<button>Submit</button>
</form>
HTML;

	const HTML_SELECTORS = <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>HTML Complex</title>
</head>
<body>


<main>
	<div class="content">First content without ID</div>
	<div id="content-element" class="content">Content with ID</div>
	<div class="content">Second content without ID</div>
	<div class="content" data-attr="1">Content with attribute 1</div>
	<div class="content" data-attr="2">Content with attribute 2</div>
	<div class="content">Third content without ID</div>
</main>

</body>
</html>
HTML;

	const HTML_HAS_CHILD_SECTION = <<<HTML
<!doctype html>
<main>
	<section id="direct">
		<h2>Direct heading</h2>
	</section>
	<section id="nested">
		<div><h2>Nested heading</h2></div>
	</section>
</main>
HTML;

	const HTML_HAS_ADJACENT_SIBLING = <<<HTML
<!doctype html>
<main>
	<h1 id="pass">Pass</h1>
	<p>Next is paragraph</p>
	<h1 id="fail">Fail</h1>
	<div>Next is div</div>
</main>
HTML;

	const HTML_HAS_GENERAL_SIBLING = <<<HTML
<!doctype html>
<main>
	<h2 id="pass">Pass</h2>
	<div>intermediate</div>
	<p class="warning">warning</p>
	<h2 id="fail">Fail</h2>
	<div>no warning sibling</div>
</main>
HTML;

	const HTML_HAS_SELECTOR_LIST = <<<HTML
<!doctype html>
<main>
	<section id="h1"><h1>One</h1></section>
	<section id="h2"><h2>Two</h2></section>
	<section id="none"><p>None</p></section>
</main>
HTML;

	const HTML_HAS_RELATIVE_SELECTOR_LIST = <<<HTML
<!doctype html>
<main>
	<section id="h1"><h1>One</h1></section>
	<section id="h2"><h2>Two</h2></section>
	<section id="nested"><div><h2>Two nested</h2></div></section>
</main>
HTML;

	const HTML_HAS_NESTED_NOT = <<<HTML
<!doctype html>
<main>
	<ul id="all-selected">
		<li class="selected">A</li>
		<li class="selected">B</li>
	</ul>
	<ul id="has-unselected">
		<li class="selected">C</li>
		<li>D</li>
	</ul>
</main>
HTML;

	const HTML_HAS_LEADING_SIBLING_WITH_PSEUDO = <<<HTML
<!doctype html>
<form>
	<label id="checked">Checked sibling</label>
	<input type="checkbox" checked />
	<label id="unchecked">Unchecked sibling</label>
	<input type="checkbox" />
</form>
HTML;

	const HTML_DEFINITION_LIST = <<<HTML
<!doctype html>
<dl>
	<dt>gigogne</dt>
	<dd>
	<dl>
		<dt>fusee</dt>
		<dd>multistage rocket</dd>
		<dt>table</dt>
		<dd>nest of tables</dd>
	</dl>
	</dd>
</dl>
HTML;

	const HTML_ATHLETE_LIST = <<<HTML
<!doctype html>
<div><p>Track & field champions:</p>
<ul>
  <li>Adhemar da Silva</li>
  <li>Wang Junxia</li>
  <li>Wilma Rudolph</li>
  <li>Babe Didrikson-Zaharias</li>
  <li>Betty Cuthbert</li>
  <li>Fanny Blankers-Koen</li>
  <li>Florence Griffith-Joyner</li>
</ul></div>
HTML;

}
