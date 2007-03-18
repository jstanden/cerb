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
	
	function testParseCrlfString() {
		$string = "this\nhas\nfour\nlines";
		$string2 = "this\r\nhas\r\nfour\r\nlines\r\n";
		$string3 = "this\r\nhas\nmixed\r\nlinefeeds\n";
		$string4 = "\r\two\n\n\r\nlines\n";
		
		$array = CerberusApplication::parseCrlfString($string);
		$array2 = CerberusApplication::parseCrlfString($string2);
		$array3 = CerberusApplication::parseCrlfString($string3);
		$array4 = CerberusApplication::parseCrlfString($string4);
		
		$this->assertEquals(4,count($array),"Just linefeeds");
		$this->assertEquals(4,count($array2),"CRLF");
		$this->assertEquals(4,count($array3),"Mixed CR+LF");
		$this->assertEquals(2,count($array4),"Blank lines");
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