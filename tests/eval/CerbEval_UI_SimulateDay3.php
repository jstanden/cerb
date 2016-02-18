<?php
class CerbEval_UI_SimulateDay3 extends PHPUnit_Extensions_SeleniumTestCase {
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
		
		if($and_parse)
			$this->_runTestCase('scheduler/RunParser.htm');
	}
	
	public function testSupportDay3() {
		/**
		 * Incoming mail
		 */
		
		// Ticket #10 is created from a new message
		$this->_downloadMessage('convos/010_mobile_app/1.txt');

		// Ticket #11 is created from a new message
		$this->_downloadMessage('convos/011_kb_translations/1.txt');
		
		// Ticket #12 is created from a new message
		$this->_downloadMessage('convos/012_restore_binlogs/1.txt');
		
		// Ticket #13 is created from a new message
		$this->_downloadMessage('convos/013_more_subtotals/1.txt');
				
		// Ticket #14 is created from a new message
		$this->_downloadMessage('convos/014_scaling_with_aws/1.txt');
		
		// The parser runs
		$this->_runTestCase('scheduler/RunParser.htm');
		
		// 12 mins go by
		$this->_pushBackTime(720);
		
		/**
		 * Kina
		 */
		
		// Kina logs in
		$this->_runTestCase('sessions/LogInKina.htm');
		
		// 3 mins go by
		$this->_pushBackTime(198);

		// Kina moves ticket #10 to development inbox
		$this->_runTestCase('convos/010_mobile_app/move_to_dev.htm');
		
		// Kina moves ticket #11 to development inbox
		$this->_runTestCase('convos/011_kb_translations/move_to_dev.htm');
		
		// Kina logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		// 1 hour goes by
		$this->_pushBackTime(1 * 3600 + 121);
		
		/**
		 * Karl
		 */
		
		/*
		// Karl logs in
		$this->_runTestCase('sessions/LogInKarl.htm');
		
		// Karl logs out
		$this->_runTestCase('sessions/LogOut.htm');
		*/
		
		/**
		 * Milo
		 */
		
		// Milo logs in
		$this->_runTestCase('sessions/LogInMilo.htm');
		
		// Milo replies to ticket #10
		$this->_runTestCase('convos/010_mobile_app/2.htm');
		
		// Milo replies to ticket #11
		$this->_runTestCase('convos/011_kb_translations/2.htm');
		
		// Milo logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
	}
	
};
