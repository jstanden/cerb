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
}
