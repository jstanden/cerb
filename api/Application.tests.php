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
	
	function testBayesCombineP() {
		$expected = "0.9028";
		$actual = number_format(CerberusBayes::_combineP(array(0.99,0.99,0.99,0.047225013,0.047225013,0.07347802,0.08221981,0.09019077,0.09019077,0.9075001,0.8921298,0.12454646,0.8568143,0.14758544,0.82347786)),4);
		$this->assertEquals($expected,$actual,"Failed to combine probabilities.");
	}
}

class CerberusParserTest extends PHPUnit_Framework_TestCase {
	function testParseRfcAddress() {
		// FQDN Addy
		$structure = CerberusParser::parseRfcAddress("jeff@webgroupmedia.com");
		$addy = array_shift($structure);
		
		$this->assertEquals('jeff', $addy->mailbox);
		$this->assertEquals('webgroupmedia.com', $addy->host);
		
		// Local Addy
		$structure = CerberusParser::parseRfcAddress("jeff@localhost");
		$addy = array_shift($structure);
		
		$this->assertEquals('jeff', $addy->mailbox);
		$this->assertEquals('localhost', $addy->host);
	}
}

?>