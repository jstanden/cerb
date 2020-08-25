<?php
class DevblocksStringTest extends PHPUnit_Framework_TestCase {
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
	
	function testHtmlToText() {
		$strings = DevblocksPlatform::services()->string();
		
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
	}
}
