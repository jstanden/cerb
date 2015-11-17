<?php
class CerbEval_UI_Setup extends PHPUnit_Extensions_SeleniumTestCase {
	private $path;
	
	protected function setUp() {
		$this->setHost(SELENIUM_SERVER_HOST);
		$this->setPort(SELENIUM_SERVER_PORT);
		$this->setBrowser(SELENIUM_SERVER_BROWSER);
		$this->setBrowserUrl(SELENIUM_BROWSER_URL);
	}
	
	private function _runTestCase($path) {
		$path = APP_PATH . '/tests/selenium/' . $path;
		$this->runSelenese($path);
	}
	
	private function _runTestCaseWithPlaceholders($path, $placeholders=array()) {
		$path_to_template = APP_PATH . '/tests/selenium/' . $path;
		$path_to_tests = APP_PATH . '/tests/';
		
		if(!file_exists($path_to_template))
			$this->assertTrue(false, "The template '" . $path . ' does not exist.');
		
		if(!is_readable($path_to_template))
			$this->assertTrue(false, "The template '" . $path . ' is not readable.');
		
		// Add common placeholders
		$placeholders['path_to_tests'] = $path_to_tests;
		
		// Get a temp file
		$fp = DevblocksPlatform::getTempFile();
		$tmp_filename = DevblocksPlatform::getTempFileInfo($fp);
		
		// Read the original template
		$output = file_get_contents($path_to_template);

		// Substitute placeholders
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$output = $tpl_builder->build($output, $placeholders);
		
		// Write the new temp file
		file_put_contents($tmp_filename, $output);
		
		// Run the temp file
		$this->runSelenese($tmp_filename);
	}
	
	public function testLogIn() {
		$this->_runTestCase('sessions/LogInKina.htm');
	}
	
	public function testCloseTour() {
		$this->_runTestCase('sessions/CloseTour.htm');
	}
	
	public function testAddDefaultWorkspace() {
		$this->_runTestCase('workspaces/AddMailPage.htm');
	}
	
	public function testSetupMailTransport() {
		$this->_runTestCase('setup/SetupMailTransports.htm');
		$this->_runTestCase('setup/mail_transports/AddNullTransport.htm');
	}
	
	public function testSetupMailReplyTo() {
		$this->_runTestCase('setup/SetupMailReplyTo.htm');
		$this->_runTestCase('setup/mail_replyto/AddSupportReplyTo.htm');
	}
	
	public function testSetupRoles() {
		$this->_runTestCase('setup/SetupRoles.htm');
		$this->_runTestCase('setup/roles/CreateEveryone.htm');
	}
	
	public function testSetupWorkers() {
		$this->_runTestCase('setup/SetupWorkers.htm');
		$this->_runTestCase('setup/workers/UpdateKina.htm');
		$this->_runTestCase('setup/workers/CreateMilo.htm');
		$this->_runTestCase('setup/workers/CreateKarl.htm');
		$this->_runTestCase('setup/workers/CreateMara.htm');
		$this->_runTestCase('setup/workers/CreateJaney.htm');
		$this->_runTestCase('setup/workers/CreateNed.htm');
	}
	
	public function testImportOrgs() {
		$this->_runTestCaseWithPlaceholders('import/ImportOrgs.htm', array());
	}
	
	public function testImportContacts() {
		$this->_runTestCaseWithPlaceholders('import/ImportContacts.htm', array());
	}
	
	public function testSetupGroups() {
		$this->_runTestCase('setup/SetupGroups.htm');
		$this->_runTestCase('setup/groups/CreateSupport.htm');
		$this->_runTestCase('setup/groups/CreateSales.htm');
		$this->_runTestCase('setup/groups/CreateDevelopment.htm');
	}
	
	public function testSetupMailRouting() {
		$this->_runTestCase('setup/SetupMailRouting.htm');
		$this->_runTestCase('setup/mail_routing/SetDefaultRouting.htm');
	}
	
	public function testSetupScheduler() {
		$this->_runTestCase('setup/SetupScheduler.htm');
		$this->_runTestCase('setup/scheduler/ConfigureSchedulerJobs.htm');
	}
	
	/*
	public function testSetupCustomFields() {
		$this->_runTestCase('setup/SetupCustomFields.htm');
		$this->_runTestCase('setup/custom_fields/AddTaskOwnerCustomField.htm');
	}
	*/
	
	public function testSetupPlugins() {
		$this->_runTestCase('setup/SetupPlugins.htm');
	}
	
	public function testCalendars() {
		$this->_runTestCase('calendars/SwitchToKinaCalendar.htm');
		$this->_runTestCase('calendars/AddWorkSchedule.htm');
		$this->_runTestCase('calendars/AddHolidays.htm');
	}
	
	public function testLogOut() {
		$this->_runTestCase('sessions/LogOut.htm');
	}
	
};
