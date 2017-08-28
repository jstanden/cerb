<?php
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Exception\NoSuchElementException;

class CerbEval_UI_SimulateDay2 extends CerbTestBase {
	function testKinaLogsIn() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
	
		$cerb->logInAs('kina@cerb.example', 'cerb');
	
		$this->assertTrue(true);
	}
	
	function testKinaRepliesToTicket4() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/4');
		
		$cerb->replyOnTicket(4, file_get_contents('resources/convos/004_no_mail_received/2.txt'));
		
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
	
	function testKinaRepliesToTicket6() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/6');
		
		$cerb->replyOnTicket(6, file_get_contents('resources/convos/006_german_translation/2.txt'));
		
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
	
	function testKinaRepliesToTicket7() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/7');
		
		$cerb->replyOnTicket(7, file_get_contents('resources/convos/007_itil_compliance/2.txt'));
		
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
	
	function testLogKinaLogsOut() {
		$cerb = CerbTestHelper::getInstance();
	
		$cerb->logOut();
	
		$this->assertTrue(true);
	}
	
	function testMiloLogsIn() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
	
		$cerb->logInAs('milo@cerb.example', 'cerb');
	
		$this->assertTrue(true);
	}
	
	function testMiloRepliesToTicket5() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/5');
		
		$cerb->replyOnTicket(7, file_get_contents('resources/convos/005_swift_api/2.txt'));
		
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
	
	function testMiloRepliesToTicket9() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/9');
		
		$cerb->replyOnTicket(7, file_get_contents('resources/convos/009_backups_process/2.txt'));
		
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
	
	function testKarlLogsIn() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
	
		$cerb->logInAs('karl@cerb.example', 'cerb');
	
		$this->assertTrue(true);
	}
	
	function testKarlRepliesToTicket8() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/8');
		
		$cerb->replyOnTicket(8, file_get_contents('resources/convos/008_charity_discount/2.txt'));
		
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
	
	function testLogKarlLogsOut() {
		$cerb = CerbTestHelper::getInstance();
	
		$cerb->logOut();
	
		$this->assertTrue(true);
	}
};
