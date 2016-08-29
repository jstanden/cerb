<?php
class CerbEval_UI_SimulateDay2 extends PHPUnit_Extensions_SeleniumTestCase {
	private $path;
	
	protected function setUp() {
		$this->setHost(SELENIUM_SERVER_HOST);
		$this->setPort(SELENIUM_SERVER_PORT);
		$this->setBrowser(SELENIUM_SERVER_BROWSER);
		$this->setBrowserUrl(SELENIUM_BROWSER_URL);
	}
	
	private function _pushBackTime($secs) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("SET @secs = %d", $secs));
		$db->ExecuteMaster("UPDATE ticket set created_date=created_date-@secs, updated_date=updated_date-@secs");
		$db->ExecuteMaster("UPDATE message set created_date=created_date-@secs");
		$db->ExecuteMaster("UPDATE attachment set updated=updated-@secs");
		$db->ExecuteMaster("UPDATE comment set created=created-@secs");
		$db->ExecuteMaster("UPDATE context_activity_log set created=created-@secs");
		$db->ExecuteMaster("UPDATE notification set created_date=created_date-@secs");
	}
	
	private function _runTestCase($path) {
		$path = APP_PATH . '/tests/selenium/' . $path;
		$this->runSelenese($path);
	}
	
	private function _downloadMessage($filename, $and_parse=false) {
		$path_to_source = APP_PATH . '/tests/selenium/' . $filename;
		
		if(!file_exists($path_to_source))
			$this->assertTrue(false, "The message '" . $filename . '" does not exist');
		
		$path_to_message = APP_MAIL_PATH . 'new/' . str_replace('/', '_', $filename) . '.msg';
		
		if(!copy($path_to_source, $path_to_message))
			$this->assertTrue(false, "Failed copying message '" . $filename . '" into storage/mail/new/');
		
		if(!chmod($path_to_message, 0664))
			$this->assertTrue(false, "Failed chmod on message '" . $filename . '"');
		
		if($and_parse)
			$this->_runTestCase('scheduler/RunParser.htm');
	}
	
	public function testSupportDay2() {
		/**
		 * Incoming mail
		 */
		
		// Ticket #4 is created from a new message
		$this->_downloadMessage('convos/004_no_mail_received/1.txt');
		
		// Ticket #5 is created from a new message
		$this->_downloadMessage('convos/005_swift_api/1.txt');

		// Ticket #6 is created from a new message
		$this->_downloadMessage('convos/006_german_translation/1.txt');
		
		// Ticket #7 is created from a new message
		$this->_downloadMessage('convos/007_itil_compliance/1.txt');
		
		// Ticket #8 is created from a new message
		$this->_downloadMessage('convos/008_charity_discount/1.txt');
		
		// Ticket #9 is created from a new message
		$this->_downloadMessage('convos/009_backups_process/1.txt');

		// The parser runs
		$this->_runTestCase('scheduler/RunParser.htm');
		
		// 10 mins go by
		$this->_pushBackTime(600);
		
		/**
		 * Kina
		 */
		
		// Kina logs in
		$this->_runTestCase('sessions/LogInKina.htm');
		
		// 2 mins go by
		$this->_pushBackTime(124);

		// Kina replies to ticket #4
		$this->_runTestCase('convos/004_no_mail_received/2.htm');
		
		// 1 min goes by
		$this->_pushBackTime(66);
		
		// Kina moves ticket #5 to development inbox
		$this->_runTestCase('convos/005_swift_api/move_to_dev.htm');
		
		// 3 mins go by
		$this->_pushBackTime(198);
		
		// Kina replies to ticket #6
		$this->_runTestCase('convos/006_german_translation/2.htm');
		
		// 4 mins go by
		$this->_pushBackTime(254);
		
		// Kina replies to ticket #7
		$this->_runTestCase('convos/007_itil_compliance/2.htm');
		
		// 1 min goes by
		$this->_pushBackTime(66);
		
		// Kina moves ticket #8 to sales inbox
		$this->_runTestCase('convos/008_charity_discount/move_to_sales.htm');
		
		// Kina logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		// 3 hours goes by
		$this->_pushBackTime(3 * 3600 + 12);
		
		/**
		 * Milo
		 */
		
		// Milo logs in
		$this->_runTestCase('sessions/LogInMilo.htm');
		
		// Milo replies to ticket #5
		$this->_runTestCase('convos/005_swift_api/2.htm');
		
		// Milo replies to ticket #9
		$this->_runTestCase('convos/009_backups_process/2.htm');
		
		// Milo logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		/**
		 * Karl
		 */
		
		// Karl logs in
		$this->_runTestCase('sessions/LogInKarl.htm');
		
		// Karl replies to ticket #8
		$this->_runTestCase('convos/008_charity_discount/2.htm');
		
		// Karl logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
	}
	
};
