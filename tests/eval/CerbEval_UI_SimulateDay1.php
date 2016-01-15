<?php
class CerbEval_UI_SimulateDay1 extends PHPUnit_Extensions_SeleniumTestCase {
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
		$db->ExecuteMaster("UPDATE worker set last_activity_date=last_activity_date-@secs WHERE last_activity_date > 0");
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
	
	public function testSupportDay1() {
		/**
		 * Incoming mail
		 */
		
		// Ticket #1 is created from a new message
		$this->_downloadMessage('convos/001_always_use_html/1.txt');
		
		// Ticket #2 is created from a new message
		$this->_downloadMessage('convos/002_server_requirements/1.txt');

		// Ticket #3 is created from a new message
		$this->_downloadMessage('convos/003_oldest_ie_version/1.txt');

		// The parser runs
		$this->_runTestCase('scheduler/RunParser.htm');
		
		/**
		 * Kina
		 */
		
		// Kina logs in
		$this->_runTestCase('sessions/LogInKina.htm');
		
		// 2 mins go by
		$this->_pushBackTime(120);

		// Kina replies to ticket #1
		$this->_runTestCase('convos/001_always_use_html/2.htm');
		
		// 1 min 10 sec goes by
		$this->_pushBackTime(70);
		
		// Kina comments on ticket #2
		$this->_runTestCase('convos/002_server_requirements/comment_to_milo.htm');
		
		// 5 mins 23 sec goes by
		$this->_pushBackTime(323);
		
		// Kina replies to ticket #3
		$this->_runTestCase('convos/003_oldest_ie_version/2.htm');
		
		// 2 mins go by
		$this->_pushBackTime(120);
	
		// Kina logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		// 7 mins go by
		$this->_pushBackTime(420);
		
		/**
		 * Milo
		 */
	
		// Milo logs in for the first time
		$this->_runTestCase('sessions/LogInMilo.htm');
		
		// Milo closes the tour
		$this->_runTestCase('sessions/CloseTour.htm');
		
		// Milo adds a default mail page
		$this->_runTestCase('workspaces/AddMailPage.htm');
		
		// 2 mins go by
		$this->_pushBackTime(120);
		
		// Milo views his notifications
		$this->_runTestCase('notifications/ViewUnread.htm');
		
		// 1 min goes by
		$this->_pushBackTime(60);

		// Milo comments on Ticket #1
		$this->_runTestCase('convos/001_always_use_html/comment_to_kina.htm');
		
		// 2 min goes by
		$this->_pushBackTime(120);
		
		// Milo replies to Ticket #2
		$this->_runTestCase('convos/002_server_requirements/2.htm');
		
		// Milo logs out
		$this->_runTestCase('sessions/LogOut.htm');

		// 3 min goes by
		$this->_pushBackTime(180);
		
		/**
		 * Mara
		 */
		
		// Mara logs in for the first time
		$this->_runTestCase('sessions/LogInMara.htm');
		
		// Mara closes the tour
		$this->_runTestCase('sessions/CloseTour.htm');
		
		// Mara adds a default mail page
		$this->_runTestCase('workspaces/AddMailPage.htm');

		// Mara logs out
		$this->_runTestCase('sessions/LogOut.htm');

		// 2 min goes by
		$this->_pushBackTime(117);
		
		/**
		 * Karl
		 */

		// Karl logs in for the first time
		$this->_runTestCase('sessions/LogInKarl.htm');
		
		// Karl closes the tour
		$this->_runTestCase('sessions/CloseTour.htm');
		
		// Karl adds a default mail page
		$this->_runTestCase('workspaces/AddMailPage.htm');

		// Karl logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		// 1 min goes by
		$this->_pushBackTime(78);
		
		/**
		 * Janey
		 */

		// Janey logs in for the first time
		$this->_runTestCase('sessions/LogInJaney.htm');
		
		// Janey closes the tour
		$this->_runTestCase('sessions/CloseTour.htm');
		
		// Janey adds a default mail page
		$this->_runTestCase('workspaces/AddMailPage.htm');

		// Janey logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		/**
		 * Ned
		 */

		// Ned logs in for the first time
		$this->_runTestCase('sessions/LogInNed.htm');
		
		// Ned closes the tour
		$this->_runTestCase('sessions/CloseTour.htm');
		
		// Ned adds a default mail page
		$this->_runTestCase('workspaces/AddMailPage.htm');

		// Ned logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		/**
		 * Incoming mail
		 */
		
		// 2 hours go by
		$this->_pushBackTime(7200);
		
		// The customer replies to Ticket #1
		$this->_downloadMessage('convos/001_always_use_html/3.txt');
		
		// The customer replies to Ticket #2
		$this->_downloadMessage('convos/002_server_requirements/3.txt');
		
		// The parser runs
		$this->_runTestCase('scheduler/RunParser.htm');
		
		// 30 mins go by
		$this->_pushBackTime(1800);
		
		/**
		 * Kina
		 */
		
		// Kina logs in
		$this->_runTestCase('sessions/LogInKina.htm');
		
		// 6 mins go by
		$this->_pushBackTime(360);
		
		// Kina replies to Ticket #1
		$this->_runTestCase('convos/001_always_use_html/4.htm');
		
		// Kina logs out
		$this->_runTestCase('sessions/LogOut.htm');
		
		/**
		 * Milo
		 */
		
		// Milo logs in
		$this->_runTestCase('sessions/LogInMilo.htm');
		
		// Kina replies to Ticket #2
		$this->_runTestCase('convos/002_server_requirements/4.htm');
		
		// Milo logs out
		$this->_runTestCase('sessions/LogOut.htm');
	}
	
};
