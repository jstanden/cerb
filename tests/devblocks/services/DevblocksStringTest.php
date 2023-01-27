<?php
use PHPUnit\Framework\TestCase;

class DevblocksStringTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testStrAfter() {
		$strings = DevblocksPlatform::services()->string();
		
		$expected = 'host';
		$actual = $strings->strAfter('user@host', '@');
		$this->assertEquals($expected, $actual);
		
		// No marker found
		$expected = null;
		$actual = $strings->strAfter('user@host', '#');
		$this->assertEquals($expected, $actual);
	}
	
	function testStrBefore() {
		$strings = DevblocksPlatform::services()->string();
		
		$expected = 'user';
		$actual = $strings->strBefore('user@host', '@');
		$this->assertEquals($expected, $actual);
		
		// No marker found
		$expected = 'user@host';
		$actual = $strings->strBefore('user@host', '#');
		$this->assertEquals($expected, $actual);
	}
	
	function testIndentWith() {
		$strings = DevblocksPlatform::services()->string();
		
		$expected = "> One\n> Two\n> Three";
		$actual = $strings->indentWith("One\nTwo\nThree", '> ');
		$this->assertEquals($expected, $actual);
		
		// From line
		
		$expected = "> One\n> Two\n> Three";
		$actual = $strings->indentWith("One\nTwo\nThree", '> ', 1);
		$this->assertEquals($expected, $actual);
		
		$expected = "One\n> Two\n> Three";
		$actual = $strings->indentWith("One\nTwo\nThree", '> ', 2);
		$this->assertEquals($expected, $actual);
	}
	
	function testSplitQuotedPhrases() {
		$strings = DevblocksPlatform::services()->string();
		
		// No quotes
		$text = 'example terms';
		$expected = [
			'example',
			'terms',
		];
		$actual = $strings->splitQuotedPhrases($text);
		$this->assertEquals($expected, $actual);
		
		// Mixed phrases and terms
		$text = 'example terms "quoted phrase" words customer@cerb.example 1.2.3.4 "f*trade"';
		$expected = [
			'example',
			'terms',
			'"quoted phrase"',
			'words',
			'customer@cerb.example',
			'1.2.3.4',
			'"f*trade"',
		];
		$actual = $strings->splitQuotedPhrases($text);
		$this->assertEquals($expected, $actual);
		
		// Non-terminated quote
		$text = 'example terms "quoted phrase words';
		$expected = [
			'example',
			'terms',
			'quoted',
			'phrase',
			'words',
		];
		$actual = $strings->splitQuotedPhrases($text);
		$this->assertEquals($expected, $actual);
	}
	
	function testHtmlToText() {
		$strings = DevblocksPlatform::services()->string();
		
		// Empty string
		$html = '';
		$expected = '';
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Styles
		$html = '<b>bold</b>, <strong>strong</strong>, <i>italics</i>, <em>emphasis</em>, <code>$variable</code>';
		$expected = 'bold, strong, italics, emphasis, $variable';
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Breaks
		$html = 'Some text<br>Some more text<br/>';
		$expected = "Some text\nSome more text\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Paragraphs
		$html = '<p>This is a paragraph.</p>';
		$expected = "This is a paragraph.\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Link (label and href differ)
		$html = '<a href="https://cerb.ai/">cerb.ai</a>';
		$expected = 'cerb.ai <https://cerb.ai/>';
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Link (label and href same)
		$html = '<a href="https://cerb.ai/">https://cerb.ai/</a>';
		$expected = 'https://cerb.ai/';
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Ordered list
		$html = '<ol><li>one</li><li>two</li><li>three</li></ol>';
		$expected = "1. one\n2. two\n3. three\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Unordered list
		$html = '<ul><li>red</li><li>green</li><li>blue</li></ul>';
		$expected = "* red\n* green\n* blue\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Strip basic HTML tags
		$html = '<html><head><script>alert("hi");</script></head><body><b>Bold</b> really <i>suits</i> you!</body></html>';
		$expected = "Bold really suits you!";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Strip a bunch of whitespace
		$html = '1<br>2<br />3<div>4</div>';
		$expected = "1\n2\n3\n4\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Convert links
		$html = '<a href="http://www.example.com/">link text</a>';
		$expected = "link text <http://www.example.com/>";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Convert unordered list
		$html = '<ul><li>one</li><li>two</li><li>three</li></ul>';
		$expected = "* one\n* two\n* three\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Convert ordered list
		$html = '<ol><li>one</li><li>two</li><li>three</li></ol>';
		$expected = "1. one\n2. two\n3. three\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Convert nested lists
		$html = '<ol><li><ul><li>red</li><li>green</li></ul></li><li><ul><li>blue</li><li>orange</li></ul></li><li><ul><li>yellow</li><li>purple</li></ul></li></ol>';
		$expected = "1. \n* red\n* green\n\n\n2. \n* blue\n* orange\n\n\n3. \n* yellow\n* purple\n\n\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Collapse multiple spaces
		$html = 'this  had	multiple	  spaces';
		$expected = "this had multiple spaces\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Maximum of two consecutive linefeeds
		$html = "this<br><br><br>had<br>multiple<br><div>linefeeds</div><br>";
		$expected = "this\n\n\nhad\nmultiple\n\nlinefeeds\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Non-breaking spaces
		$html = "this has &nbsp;non-breaking&nbsp; spaces";
		$expected = "this has non-breaking spaces\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// High ASCII encoding
		$html = "à á â ã ä å æ ç è é ê ë ì í î ï";
		$expected = "à á â ã ä å æ ç è é ê ë ì í î ï\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// High ASCII encoding with html+head+body
		$html = "<html><head></head><body>à á â ã ä å æ ç è é ê ë ì í î ï</body></html>";
		$expected = "à á â ã ä å æ ç è é ê ë ì í î ï";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// High ASCII encoding with an explicit charset
		$html = "<html><head><meta http-equiv='content-type' content='text/html; charset=iso-8859-1'></head><body>à á â ã ä å æ ç è é ê ë ì í î ï</body></html>";
		$expected = "à á â ã ä å æ ç è é ê ë ì í î ï";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// GB2312 encoding with an explicit charset
		$html = "<html><head><meta http-equiv='content-type' content='text/html; charset=gb2312'></head><body>善讼的人的故事 赖和</body></html>";
		$expected = "善讼的人的故事 赖和";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// GB2312 encoding with html-entities and an explicit charset
		$html = "<html><head><meta http-equiv='content-type' content='text/html; charset=gb2312'></head><body>&#21892;&#35772;&#30340;&#20154;&#30340;&#25925;&#20107; &#36182;&#21644;</body></html>";
		$expected = "善讼的人的故事 赖和";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		/*
		// Convert Windows' silly quotes
		$html = '&ldquo;quotes&rdquo; and &#8220;quotes&#8221; and &#x201c;quotes&#x201d;';
		$expected = '"quotes" and "quotes" and "quotes"' . "\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		
		// Convert Ellipsis
		$html = 'And&hellip; then&#8230; it&#x2026;';
		$expected = "And... then... it...\n\n";
		$actual = $strings->htmlToText($html);
		$this->assertEquals($expected, $actual);
		*/
	}
	
	function testToBool() {
		$this->assertTrue(DevblocksPlatform::services()->string()->toBool('yes'));
		$this->assertTrue(DevblocksPlatform::services()->string()->toBool('y'));
		$this->assertTrue(DevblocksPlatform::services()->string()->toBool(true));
		$this->assertTrue(DevblocksPlatform::services()->string()->toBool(1));
		$this->assertTrue(DevblocksPlatform::services()->string()->toBool('1'));
		$this->assertTrue(DevblocksPlatform::services()->string()->toBool('arbitrary text'));
		
		$this->assertFalse(DevblocksPlatform::services()->string()->toBool('no'));
		$this->assertFalse(DevblocksPlatform::services()->string()->toBool('n'));
		$this->assertFalse(DevblocksPlatform::services()->string()->toBool(false));
		$this->assertFalse(DevblocksPlatform::services()->string()->toBool(0));
		$this->assertFalse(DevblocksPlatform::services()->string()->toBool('0'));
	}
	
	function testCapitalizeDashed() {
		$string = DevblocksPlatform::services()->string();
		
		$expected = 'X-Example-Header';
		$actual = $string->capitalizeDashed('x-example-header');
		$this->assertEquals($expected, $actual);
		
		$expected = 'X-EXAMPLE';
		$actual = $string->capitalizeDashed('x-EXAMPLE');
		$this->assertEquals($expected, $actual);
	}
	
	function testTruncate() {
		$string = DevblocksPlatform::services()->string();
		
		// Normal truncation
		$expected = 'This is...';
		$actual = $string->truncate('This is truncated to 10 characters', 10);
		$this->assertEquals($expected, $actual);
		
		// Truncation with a custom separator
		$expected = 'This is tr';
		$actual = $string->truncate('This is truncated to 10 characters', 10, '');
		$this->assertEquals($expected, $actual);
		
		// Japanese
		$expected = 'これは...';
		$actual = $string->truncate('これはテストです', 10);
		$this->assertEquals($expected, $actual);
	}
}
