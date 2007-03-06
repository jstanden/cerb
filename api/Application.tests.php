<?php
require_once 'PHPUnit/Framework.php';

class ApplicationTest extends PHPUnit_Framework_TestCase {
	function testGenerateTicketMask() {
		$mask = CerberusApplication::generateTicketMask("LLL-NNNNN-NNN");
		$this->assertRegExp("/[A-Z]{3}\-[0-9]{5}\-[0-9]{3}/",$mask);
	}
	
	function testParseCsvString() {
		$array = CerberusApplication::parseCsvString("1,2,3,4");
		$this->assertEquals(4,count($array));
		$this->assertEquals(3,$array[2]);
	}
}

class CerberusBayesTest extends PHPUnit_Framework_TestCase {
	function testBayesProcessText() {
		$words = CerberusBayes::processText("only words only four unique words, four!");
		$this->assertEquals(4, count($words));
		$this->assertArrayHasKey('unique', $words);
	}
}

class CerberusParserTest extends PHPUnit_Framework_TestCase {
	function testParseRfcAddress() {
		$structure = CerberusParser::parseRfcAddress("jeff@webgroupmedia.com");
		$addy = array_shift($structure);
		
		$this->assertEquals('jeff', $addy->mailbox);
		$this->assertEquals('webgroupmedia.com', $addy->host);
	}
}

?>