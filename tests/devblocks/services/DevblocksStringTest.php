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
}
