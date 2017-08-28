<?php
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Exception\NoSuchElementException;

class CerbEval_UI_SimulateDay3 extends CerbTestBase {
	function testMiloLogsIn() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
	
		$cerb->logInAs('milo@cerb.example', 'cerb');
	
		$this->assertTrue(true);
	}
	
	function testMiloRepliesToTicket10() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/10');
		
		$cerb->replyOnTicket(10, file_get_contents('resources/convos/010_mobile_app/2.txt'));
		
		$driver->wait(10)->until(
			function() use (&$driver) {
				try {
					$objects = $driver->findElements(WebDriverBy::cssSelector('#conversation > div'));
					return 2 == count($objects);
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Error waiting for the new reply to display in the convo history."
		);
		
		$this->assertTrue(true);
	}
	
	function testMiloRepliesToTicket11() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/11');
		
		$cerb->replyOnTicket(11, file_get_contents('resources/convos/011_kb_translations/2.txt'));
		
		$driver->wait(10)->until(
			function() use (&$driver) {
				try {
					$objects = $driver->findElements(WebDriverBy::cssSelector('#conversation > div'));
					return 2 == count($objects);
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Error waiting for the new reply to display in the convo history."
		);
		
		$this->assertTrue(true);
	}
	
	function testLogMiloLogsOut() {
		$cerb = CerbTestHelper::getInstance();
	
		$cerb->logOut();
	
		$this->assertTrue(true);
	}
	
};
