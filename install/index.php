<?php
/**
 * @author Jeff Standen <jeff@webgroupmedia.com> [JAS]
 */

@set_time_limit(3600);
require('../framework.config.php');
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');
require_once(APP_PATH . '/api/Application.class.php');
require_once(APP_PATH . '/install/classes.php');

define('STEP_ENVIRONMENT', 1);
define('STEP_DATABASE', 2);
define('STEP_SAVE_CONFIG_FILE', 3);
define('STEP_INIT_DB', 4);
define('STEP_CONTACT', 5);
define('STEP_OUTGOING_MAIL', 6);
define('STEP_INCOMING_MAIL', 7);
define('STEP_WORKFLOW', 8);
define('STEP_ANTISPAM', 9);

// Import GPC variables to determine our scope/step.
@$step = DevblocksPlatform::importGPC($_REQUEST['step'],'integer');

/*
 * [TODO] We can run some quick tests to bypass steps we've already passed
 * even when returning to the page with a NULL step.
 */
if(empty($step)) $step = STEP_ENVIRONMENT;

// Make sure the temporary directories of Devblocks are writeable.
if(!is_writeable(DEVBLOCKS_PATH . "tmp/")) {
	die(realpath(DEVBLOCKS_PATH . "tmp/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(DEVBLOCKS_PATH . "tmp/templates_c/")) {
	die(realpath(DEVBLOCKS_PATH . "tmp/templates_c/") . " is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(DEVBLOCKS_PATH . "tmp/cache/")) {
	die(realpath(DEVBLOCKS_PATH . "tmp/cache/") . " is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

// Get a reference to the template system and configure it
$tpl = DevblocksPlatform::getTemplateService();
$tpl->template_dir = APP_PATH . '/install/templates';
$tpl->caching = 0;

switch($step) {
	// [TODO] Check server + php environment (extensions + php.ini)
	default:
	case STEP_ENVIRONMENT:
		$results = array();
		$fails = 0;
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.0.0") >=0) {
			$results['php_version'] = PHP_VERSION;
		} else {
			$results['php_version'] = false;
			$fails++;
		}
		
		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
			$results['file_uploads'] = true;
		} else {
			$results['file_uploads'] = false;
			$fails++;
		}
		
		// File Upload Temporary Directory
		$ini_upload_tmp_dir = ini_get("upload_tmp_dir");
		if(!empty($ini_upload_tmp_dir)) {
			$results['upload_tmp_dir'] = true;
		} else {
			$results['upload_tmp_dir'] = false;
			//$fails++; // Not fatal
		}

		// Extension: Sessions
		if(extension_loaded("session")) {
			$results['ext_session'] = true;
		} else {
			$results['ext_session'] = false;
			$fails++;
		}
		
		// Extension: PCRE
		if(extension_loaded("pcre")) {
			$results['ext_pcre'] = true;
		} else {
			$results['ext_pcre'] = false;
			$fails++;
		}

		// Extension: IMAP
		if(extension_loaded("imap")) {
			$results['ext_imap'] = true;
		} else {
			$results['ext_imap'] = false;
			$fails++;
		}
		
		// Extension: SimpleXML
		if(extension_loaded("simplexml")) {
			$results['ext_simplexml'] = true;
		} else {
			$results['ext_simplexml'] = false;
			$fails++;
		}
		
		$tpl->assign('fails', $fails);
		$tpl->assign('results', $results);
		$tpl->assign('template', 'steps/step_environment.tpl.php');
		
		break;
		
	// Configure and test the database connection 
	case STEP_DATABASE:
		// Import scope (if post)
		@$db_driver = DevblocksPlatform::importGPC($_POST['db_driver'],'string');
		@$db_server = DevblocksPlatform::importGPC($_POST['db_server'],'string');
		@$db_name = DevblocksPlatform::importGPC($_POST['db_name'],'string');
		@$db_user = DevblocksPlatform::importGPC($_POST['db_user'],'string');
		@$db_pass = DevblocksPlatform::importGPC($_POST['db_pass'],'string');

		@$db = DevblocksPlatform::getDatabaseService();
		if(@$db->IsConnected()) {
			// If we've been to this step, skip past framework.config.php
			$tpl->assign('step', STEP_INIT_DB);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		unset($db);
		
		if(!empty($db_driver) && !empty($db_server) && !empty($db_name) && !empty($db_user)) {
			// Test the given settings, bypass platform initially
			include_once(DEVBLOCKS_PATH . "adodb/adodb.inc.php");
			$ADODB_CACHE_DIR = APP_PATH . "/tmp/cache";
			@$db =& ADONewConnection($db_driver);
			@$db->Connect($db_server, $db_user, $db_pass, $db_name);

			$tpl->assign('db_driver', $db_driver);
			$tpl->assign('db_server', $db_server);
			$tpl->assign('db_name', $db_name);
			$tpl->assign('db_user', $db_user);
			$tpl->assign('db_pass', $db_pass);
			
			// If passed, write config file and continue
			if($db->IsConnected()) {
				// [TODO] Write database settings to framework.config.php
				$result = CerberusInstaller::saveFrameworkConfig($db_driver, $db_server, $db_name, $db_user, $db_pass);
				
				// [JAS]: If we didn't save directly to the config file, user action required
				if(0 != strcasecmp($result,'config')) {
					$tpl->assign('result', $result);
					$tpl->assign('config_path', realpath(APP_PATH . "/framework.config.php"));
					$tpl->assign('template', 'steps/step_config_file.tpl.php');
					
				} else { // skip the config writing step
					$tpl->assign('step', STEP_INIT_DB);
					$tpl->display('steps/redirect.tpl.php');
					exit;
				}
				
			} else { // If failed, re-enter
				$tpl->assign('failed', true);
				$tpl->assign('template', 'steps/step_database.tpl.php');
			}
			
		} else {
			$tpl->assign('db_server', 'localhost');
			$tpl->assign('template', 'steps/step_database.tpl.php');
		}
		break;
		
	// [JAS]: If we didn't save directly to the config file, user action required		
	case STEP_SAVE_CONFIG_FILE:
		@$db_driver = DevblocksPlatform::importGPC($_POST['db_driver'],'string');
		@$db_server = DevblocksPlatform::importGPC($_POST['db_server'],'string');
		@$db_name = DevblocksPlatform::importGPC($_POST['db_name'],'string');
		@$db_user = DevblocksPlatform::importGPC($_POST['db_user'],'string');
		@$db_pass = DevblocksPlatform::importGPC($_POST['db_pass'],'string');
		@$result = DevblocksPlatform::importGPC($_POST['result'],'string');
		
		// Check to make sure our constants match our input
		if(
			0 == strcasecmp($db_driver,APP_DB_DRIVER) &&
			0 == strcasecmp($db_server,APP_DB_HOST) &&
			0 == strcasecmp($db_name,APP_DB_DATABASE) &&
			0 == strcasecmp($db_user,APP_DB_USER) &&
			0 == strcasecmp($db_pass,APP_DB_PASS)
		) { // we did it!
			$tpl->assign('step', STEP_INIT_DB);
			$tpl->display('steps/redirect.tpl.php');
			exit;
			
		} else { // oops!
			$tpl->assign('db_driver', $db_driver);
			$tpl->assign('db_server', $db_server);
			$tpl->assign('db_name', $db_name);
			$tpl->assign('db_user', $db_user);
			$tpl->assign('db_pass', $db_pass);
			$tpl->assign('failed', true);
			$tpl->assign('result', $result);
			$tpl->assign('config_path', realpath(APP_PATH . "/framework.config.php"));
			
			$tpl->assign('template', 'steps/step_config_file.tpl.php');
		}
		
		break;

	// Initialize the database
	case STEP_INIT_DB:
		// [TODO] Add current user to patcher/upgrade authorized IPs
		
		// Is the database empty?
		if(CerberusInstaller::isDatabaseEmpty()) {
			
			// Clean script
			if(!CerberusInstaller::initDatabase()) {
				// [TODO] Show more info on the error
				$tpl->assign('template', 'steps/step_init_db.tpl.php');
			}
		}
			
		// [TODO] Verify the database
		
		// Read in plugin information from the filesystem to the database
		DevblocksPlatform::readPlugins();
		
		$tpl->assign('step', STEP_CONTACT);
		$tpl->display('steps/redirect.tpl.php');
		exit;
		
		break;
		

	// Personalize system information (title, timezone, language)
	case STEP_CONTACT:
		@$superuser_pass = DevblocksPlatform::importGPC($_POST['superuser_pass'],'string');

		// Set up a default superuser login
		$id = CerberusAgentDAO::lookupAgentLogin('superuser');
		
		// If our agent already exists, skip.
		if(!empty($id)) {
			$tpl->assign('step',STEP_OUTGOING_MAIL);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		
		if(!empty($superuser_pass)) {
			
			if(empty($id)) {
				$id = CerberusAgentDAO::createAgent('superuser', $superuser_pass, 'Superuser', '', 'Administrator');
			}
			
			$tpl->assign('step', STEP_OUTGOING_MAIL);
			$tpl->display('steps/redirect.tpl.php');
			exit;
					
		} else { // first time
			$tpl->assign('template', 'steps/step_contact.tpl.php');
		}
		
		break;
	
	// [TODO] Test the outgoing SMTP
	case STEP_OUTGOING_MAIL:
		@$smtp_host = DevblocksPlatform::importGPC($_POST['smtp_host'],'string');
		@$smtp_auth_user = DevblocksPlatform::importGPC($_POST['smtp_auth_user'],'string');
		@$smtp_auth_pass = DevblocksPlatform::importGPC($_POST['smtp_auth_pass'],'string');
		
		if(!empty($smtp_host)) {
			// [TODO] Actually test outgoing mail
			
			// Pass
			if(1) {
				$tpl->assign('step', STEP_INCOMING_MAIL);
				$tpl->display('steps/redirect.tpl.php');
				exit;
			}
		}
		
		// First time, or retry
		$tpl->assign('smtp_host', 'localhost');
		$tpl->assign('template', 'steps/step_outgoing_mail.tpl.php');
		
		break;

	case STEP_INCOMING_MAIL:
		
		if(1) {
			$tpl->assign('step', STEP_WORKFLOW);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		
		$tpl->assign('template', 'steps/step_incoming_mail.tpl.php');
		
		break;
		
	// Create initial workers, mailboxes, teams
	case STEP_WORKFLOW:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');

		// Catch the submit
		switch($form_submit) {
			case 1: // names form submit
				@$workers_str = DevblocksPlatform::importGPC($_POST['workers'],'string');
				@$mailboxes_str = DevblocksPlatform::importGPC($_POST['mailboxes'],'string');
				@$teams_str = DevblocksPlatform::importGPC($_POST['teams'],'string');
				
				$worker_ids = array();
				$mailbox_ids = array();
				$team_ids = array();
				
				// Create worker records
				$workers = CerberusApplication::parseCrlfString($workers_str);
				if(is_array($workers))
				foreach($workers as $worker_email) {
					// [TODO] Need a function for generating a password (PEAR/Platform)
					$id = CerberusAgentDAO::createAgent($worker_email,md5('new'),'Joe','User','');
					$worker_ids[$id] = $worker_email; 
				}
				
				// Create mailbox records
				$mailboxes = CerberusApplication::parseCrlfString($mailboxes_str);
				if(is_array($mailboxes))
				foreach($mailboxes as $mailbox_name) {
					$id = CerberusMailDAO::createMailbox($mailbox_name,0);
					$mailbox_ids[$id] = $mailbox_name;
				}
				
				// Create team records
				$teams = CerberusApplication::parseCrlfString($teams_str);
				if(is_array($teams))
				foreach($teams as $team_name) {
					$id = CerberusWorkflowDAO::createTeam($team_name);
					$team_ids[$id] = $team_name;
				}

				$tpl->assign('worker_ids', $worker_ids);
				$tpl->assign('mailbox_ids', $mailbox_ids);
				$tpl->assign('team_ids', $team_ids);
				$tpl->assign('template', 'steps/step_workflow2.tpl.php');
				break;
				
			case 2: // detailed form submit
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_ids'],'array');
				@$worker_first = DevblocksPlatform::importGPC($_POST['worker_first'],'array');
				@$worker_last = DevblocksPlatform::importGPC($_POST['worker_last'],'array');
				@$worker_title = DevblocksPlatform::importGPC($_POST['worker_title'],'array');
				@$mailbox_ids = DevblocksPlatform::importGPC($_POST['mailbox_ids'],'array');
				@$mailbox_from = DevblocksPlatform::importGPC($_POST['mailbox_from'],'array');
				@$team_ids = DevblocksPlatform::importGPC($_POST['team_ids'],'array');
				
				// Worker Details
				// [TODO] E-mail the workers their logins (generate random pw)
				// [TODO] Create a default dashboard for each worker
				// [TODO] Add default actions to worker dashboards (Report Spam)
				if(is_array($worker_ids))
				foreach($worker_ids as $idx => $worker_id) {
					$fields = array(
						CerberusAgentDAO::FIRST_NAME => $worker_first[$idx],
						CerberusAgentDAO::LAST_NAME => $worker_last[$idx],
						CerberusAgentDAO::TITLE => $worker_title[$idx]
					);
					CerberusAgentDAO::updateAgent($worker_id, $fields);
				}
				
				// Mailbox Details
				// [TODO] Add inbound addresses (and create DB routing)
				if(is_array($mailbox_ids))
				foreach($mailbox_ids as $idx => $mailbox_id) {
					$addy_id = CerberusContactDAO::lookupAddress($mailbox_from[$idx],true);
					
					$fields = array(
						CerberusMailDAO::MAILBOX_REPLY_ADDRESS_ID => $addy_id
					);
					CerberusMailDAO::updateMailbox($mailbox_id, $fields);
				}

				// Team Details
				// [TODO] Permissions
				if(is_array($team_ids))
				foreach($team_ids as $idx => $team_id) {
					@$team_members = DevblocksPlatform::importGPC($_POST['team_members_'.$team_id],'array');
					@$team_mailboxes = DevblocksPlatform::importGPC($_POST['team_mailboxes_'.$team_id],'array');
					
					// Team Members
					if(is_array($team_members))
						CerberusWorkflowDAO::setTeamWorkers($team_id,$team_members);
					
					// Team Mailboxes
					if(is_array($team_mailboxes))
						CerberusWorkflowDAO::setTeamMailboxes($team_id,$team_mailboxes);
				}
				
				$tpl->assign('step', STEP_ANTISPAM);
//				$tpl->display('steps/redirect.tpl.php');
				exit;
				
				break;
				
			default: // first time
				$tpl->assign('template', 'steps/step_workflow.tpl.php');
				break;
		}
		
		break;
		
	case STEP_ANTISPAM:
//		$tpl->assign('template', 'steps/step_workflow.tpl.php');
		break;
}

// [TODO] Create POP3 mailbox

// [TODO] Configure a catch-all rule

// [TODO] Create an anti-spam rule and mailbox automatically?

// [TODO] Prompt to create sample tickets (Dan's madlibs with sets in the API)

// [TODO] Check apache rewrite (somehow)

// [TODO] Delete the /install/ directory (security)

// [TODO] Send the user to login

// [TODO] Show a progress bar on the page by counting toward max steps?

// [TODO] Add all mailboxes/teams to superuser? (This needs an overall concept too, admin should bypass all privs?)

$tpl->display('base.tpl.php');

?>