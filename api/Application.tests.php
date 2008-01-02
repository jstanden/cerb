<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
require_once 'PHPUnit/Framework.php';

class ApplicationTest extends PHPUnit_Framework_TestCase {
	function testGenerateTicketMask() {
		$mask = CerberusApplication::generateTicketMask("LLL-NNNNN-NNN");
		$this->assertRegExp("/[A-Z]{3}\-[0-9]{5}\-[0-9]{3}/",$mask);
	}
	
	function testParseCsvString() {
		$array = DevblocksPlatform::parseCsvString("1,2,3,4");
		$this->assertEquals(4,count($array));
		$this->assertEquals(3,$array[2]);
	}
	
	function testParseCrlfString() {
		$string = "this\nhas\nfour\nlines";
		$string2 = "this\r\nhas\r\nfour\r\nlines\r\n";
		$string3 = "this\r\nhas\nmixed\r\nlinefeeds\n";
		$string4 = "\r\two\n\n\r\nlines\n";
		
		$array = DevblocksPlatform::parseCrlfString($string);
		$array2 = DevblocksPlatform::parseCrlfString($string2);
		$array3 = DevblocksPlatform::parseCrlfString($string3);
		$array4 = DevblocksPlatform::parseCrlfString($string4);
		
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