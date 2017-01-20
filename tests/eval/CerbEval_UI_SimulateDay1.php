<?php
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\NoSuchElementException;

class CerbEval_UI_SimulateDay1 extends CerbTestBase {
	function testKinaLogsIn() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->logInAs('kina@cerb.example', 'cerb');
		
		$this->assertTrue(true);
	}
	
	public function testParser() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$message_files = [
			'001_always_use_html/1.txt',
			'002_server_requirements/1.txt',
			'003_oldest_ie_version/1.txt',
			'004_no_mail_received/1.txt',
			'005_swift_api/1.txt',
			'006_german_translation/1.txt',
			'007_itil_compliance/1.txt',
			'008_charity_discount/1.txt',
			'009_backups_process/1.txt',
			'010_mobile_app/1.txt',
			'011_kb_translations/1.txt',
			'012_restore_binlogs/1.txt',
			'013_more_subtotals/1.txt',
			'014_scaling_with_aws/1.txt',
			'015_slow_performance/1.txt',
			'016_pay_by_wire/1.txt',
			'017_salesforce_integration/1.txt',
			'018_quote_for_14_seats/1.txt',
			'019_incremental_quick_search/1.txt',
			'020_upgrade_with_local_changes/1.txt',
			'021_thai/1.txt',
			'022_volume_discount/1.txt',
			'023_calendar_shared_holidays/1.txt',
			'024_same_license_two_instances/1.txt',
			'025_migrate_standalone_to_cloud/1.txt',
			'026_sso_login_via_sc/1.txt',
			'027_auto_close_va/1.txt',
		];
		
		foreach($message_files as $message_file) {
			$cerb->getPathAndWait('/config/mail_import');
			
			$by = WebDriverBy::id('frmSetupMailImport');
			
			$driver->wait(5, 250)->until(
				WebDriverExpectedCondition::presenceOfElementLocated($by)
			);
			
			$form = $driver->findElement($by);
			
			$textarea = $form->findElement(WebDriverBy::name('message_source'));
			$textarea->sendKeys(file_get_contents('resources/convos/' . $message_file));
			
			$form->findElement(WebDriverBy::tagName('button'))
				->click();
			
			$driver->wait(5, 250)->until(
				WebDriverExpectedCondition::elementTextContains(WebDriverBy::cssSelector('div.output'), 'Ticket updated:')
			);
		}
		
		$this->assertTrue(true);
	}
	
	public function testKinaRepliesToTicket1() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/1');
		
		$cerb->replyOnTicket(1, file_get_contents('resources/convos/001_always_use_html/2.txt'));
		
		$driver->wait(5)->until(
			function() use (&$driver) {
				try {
					$objects = $driver->findElements(WebDriverBy::cssSelector('#conversation > div'));
					return 3 == count($objects);
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Error waiting for the new reply to display in the convo history."
		);
		
		$this->assertTrue(true);
	}
	
	public function testKinaCommentsToMiloOnTicket2() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/2');
		
		$cerb->commentOnTicket(2, '@Milo Can you respond to the server management questions here?');
		
		$this->assertTrue(true);
	}
	
	public function testKinaRepliesToTicket3() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/3');
		
		$cerb->replyOnTicket(3, file_get_contents('resources/convos/003_oldest_ie_version/2.txt'));
		
		$driver->wait(5)->until(
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
	
	public function testLogKinaLogsOut() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logOut();
		
		$this->assertTrue(true);
	}
	
	public function testMiloLogsIn() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logInAs('milo@cerb.example', 'cerb');
	}
	
	public function testMiloCommentsToKinaOnTicket1() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/1');
		
		$cerb->commentOnTicket(3, '@Kina We could add global mail preferences to Setup->Mail->Settings without much difficulty. I went ahead and filed it as feature request #1234 on GitHub.');
	}
	
	public function testMiloRepliesToTicket2() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/2');
		
		$cerb->replyOnTicket(2, file_get_contents('resources/convos/002_server_requirements/2.txt'));
		
		$driver->wait(5)->until(
			function() use (&$driver) {
				try {
					$objects = $driver->findElements(WebDriverBy::cssSelector('#conversation > div'));
					return 3 == count($objects);
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Error waiting for the new reply to display in the convo history."
		);
		
		$this->assertTrue(true);
	}
	
	public function testMiloLogsOut() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logOut();
	}
	
	public function testKinaLogsInAgain() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logInAs('kina@cerb.example', 'cerb');
	}
	
	public function testCustomersReply() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$message_files = [
			'001_always_use_html/3.txt',
			'002_server_requirements/3.txt',
		];
		
		foreach($message_files as $message_file) {
			$cerb->getPathAndWait('/config/mail_import');
			
			$by = WebDriverBy::id('frmSetupMailImport');
			
			$driver->wait(5, 250)->until(
				WebDriverExpectedCondition::presenceOfElementLocated($by)
			);
			
			$form = $driver->findElement($by);
			
			$textarea = $form->findElement(WebDriverBy::name('message_source'));
			$textarea->sendKeys(file_get_contents('resources/convos/' . $message_file));
			
			$form->findElement(WebDriverBy::tagName('button'))
				->click();
			
			$driver->wait(5, 250)->until(
				WebDriverExpectedCondition::elementTextContains(WebDriverBy::cssSelector('div.output'), 'Ticket updated:')
			);
		}
		
		$this->assertTrue(true);
	}
	
	public function testKinaRepliesToTicket1Again() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/1');
		
		$cerb->replyOnTicket(1, file_get_contents('resources/convos/001_always_use_html/4.txt'));
		
		$driver->wait(5)->until(
			function() use (&$driver) {
				try {
					$objects = $driver->findElements(WebDriverBy::cssSelector('#conversation > div'));
					return 6 == count($objects);
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Error waiting for the new reply to display in the convo history."
		);
		
		$this->assertTrue(true);
	}
	
	public function testKinaLogsOutAgain() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logOut();
	}
	
	public function testMiloLogsInAgain() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logInAs('milo@cerb.example', 'cerb');
	}
	
	public function testMiloRepliesToTicket2Again() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/2');
		
		$cerb->replyOnTicket(2, file_get_contents('resources/convos/002_server_requirements/4.txt'));
		
		$driver->wait(5)->until(
			function() use (&$driver) {
				try {
					$objects = $driver->findElements(WebDriverBy::cssSelector('#conversation > div'));
					return 4 == count($objects);
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Error waiting for the new reply to display in the convo history."
		);
		
		$this->assertTrue(true);
	}
	
	public function testMiloLogsOutAgain() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logOut();
	}
	
};
