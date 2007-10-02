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
/**
 * @author Jeff Standen <jeff@webgroupmedia.com> [JAS]
 */

if(version_compare(PHP_VERSION, "5.1.4", "<"))
	die("Cerberus Helpdesk 4.0 requires PHP 5.1.4 or later.");

@set_time_limit(3600);
require('../framework.config.php');
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');
require_once(APP_PATH . '/api/Application.class.php');
require_once(APP_PATH . '/install/classes.php');

DevblocksPlatform::getCacheService()->clean();

// DevblocksPlatform::init() workaround 
if(!defined('DEVBLOCKS_WEBPATH')) {
	$php_self = $_SERVER["PHP_SELF"];
	$php_self = str_replace('/install','',$php_self);
	$pos = strrpos($php_self,'/');
	$php_self = substr($php_self,0,$pos) . '/';
	@define('DEVBLOCKS_WEBPATH',$php_self);
}

//if('' == APP_DB_DRIVER 
//	|| '' == APP_DB_HOST 
//	|| '' == APP_DB_DATABASE 
//	|| null == ($db = DevblocksPlatform::getDatabaseService())
//	)
//	throw new Exception("Database details not set.");
//	
//	$tables = $db->MetaTables('table',false);
//	if(empty($tables))
//		throw new Exception("Database empty.");


define('STEP_ENVIRONMENT', 1);
define('STEP_LICENSE', 2);
define('STEP_DATABASE', 3);
define('STEP_SAVE_CONFIG_FILE', 4);
define('STEP_INIT_DB', 5);
define('STEP_CONTACT', 6);
define('STEP_OUTGOING_MAIL', 7);
define('STEP_INCOMING_MAIL', 8);
define('STEP_WORKFLOW', 9);
define('STEP_CATCHALL', 10);
//define('STEP_ANTISPAM', x);
define('STEP_REGISTER', 11);
define('STEP_UPGRADE', 12);
define('STEP_FINISHED', 13);

define('TOTAL_STEPS', 13);

// Import GPC variables to determine our scope/step.
@$step = DevblocksPlatform::importGPC($_REQUEST['step'],'integer');

/*
 * [TODO] We can run some quick tests to bypass steps we've already passed
 * even when returning to the page with a NULL step.
 */
if(empty($step)) $step = STEP_ENVIRONMENT;

@chmod(DEVBLOCKS_PATH . 'tmp/', 0774);
@chmod(DEVBLOCKS_PATH . 'tmp/templates_c/', 0774);
@chmod(DEVBLOCKS_PATH . 'tmp/cache/', 0774);

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

@chmod(APP_PATH . '/storage/', 0774);
@chmod(APP_PATH . '/storage/attachments/', 0774);
@chmod(APP_PATH . '/storage/mail/new/', 0774);
@chmod(APP_PATH . '/storage/mail/fail/', 0774);
@chmod(APP_PATH . '/storage/indexes/', 0774);

if(!is_writeable(APP_PATH . "/storage/")) {
	die(realpath(APP_PATH . "/storage/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_PATH . "/storage/attachments/")) {
	die(realpath(APP_PATH . "/storage/attachments/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_PATH . "/storage/mail/new/")) {
	die(realpath(APP_PATH . "/storage/mail/new/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_PATH . "/storage/mail/fail/")) {
	die(realpath(APP_PATH . "/storage/mail/fail/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_PATH . "/storage/indexes/")) {
	die(realpath(APP_PATH . "/storage/indexes/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

//require_once(DEVBLOCKS_PATH . 'libs/Zend.php');
//require_once(DEVBLOCKS_PATH . 'libs/Zend/Config.php');

// [JAS]: Email Validator
//require_once 'Zend/Validate/EmailAddress.php';

// [TODO] Move this to the framework init (installer blocks this at the moment)
$locale = DevblocksPlatform::getLocaleService();
$locale->setLocale('en_US');

// [JAS]: Translations
// [TODO] Should probably cache this
// [TODO] This breaks if you change the platform tables (it needs to look up plugins)
$translate = DevblocksPlatform::getTranslationService();
$translate->addTranslation(APP_PATH . '/install/strings.xml',$locale);
//$date = DevblocksPlatform::getDateService();
//echo sprintf($translate->_('installer.today'),$date->get(Zend_Date::WEEKDAY));

// Get a reference to the template system and configure it
$tpl = DevblocksPlatform::getTemplateService();
$tpl->template_dir = APP_PATH . '/install/templates';
$tpl->caching = 0;

$tpl->assign('translate', $translate);

$tpl->assign('step', $step);

switch($step) {
	// [TODO] Check server + php environment (extensions + php.ini)
	default:
	case STEP_ENVIRONMENT:
		$results = array();
		$fails = 0;
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.1.4") >=0) {
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

		$ini_memory_limit = intval(ini_get("memory_limit"));
		if($ini_memory_limit >= 16) {
			$results['memory_limit'] = true;
		} else {
			$results['memory_limit'] = false;
			$fails++;
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

		// Extension: GD
		if(extension_loaded("gd")) {
			$results['ext_gd'] = true;
		} else {
			$results['ext_gd'] = false;
			$fails++;
		}
		
		// Extension: IMAP
		if(extension_loaded("imap")) {
			$results['ext_imap'] = true;
		} else {
			$results['ext_imap'] = false;
			$fails++;
		}
		
		// Extension: MailParse
		if(extension_loaded("mailparse")) {
			$results['ext_mailparse'] = true;
		} else {
			$results['ext_mailparse'] = false;
			$fails++;
		}
		
		// Extension: mbstring
		if(extension_loaded("mbstring")) {
			$results['ext_mbstring'] = true;
		} else {
			$results['ext_mbstring'] = false;
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
	
	case STEP_LICENSE:
	    @$accept = DevblocksPlatform::importGPC($_POST['accept'],'integer', 0);
	    
	    if(1 == $accept) {
			$tpl->assign('step', STEP_DATABASE);
			$tpl->display('steps/redirect.tpl.php');
			exit;
	    }
		
		$tpl->assign('template', 'steps/step_license.tpl.php');
		
	    break;	
	
	// Configure and test the database connection
	// [TODO] This should also patch in app_id + revision order
	// [TODO] This should remind the user to make a backup (and refer to a wiki article how)
	case STEP_DATABASE:
		// Import scope (if post)
		@$db_driver = DevblocksPlatform::importGPC($_POST['db_driver'],'string');
		@$db_server = DevblocksPlatform::importGPC($_POST['db_server'],'string');
		@$db_name = DevblocksPlatform::importGPC($_POST['db_name'],'string');
		@$db_user = DevblocksPlatform::importGPC($_POST['db_user'],'string');
		@$db_pass = DevblocksPlatform::importGPC($_POST['db_pass'],'string');

		@$db = DevblocksPlatform::getDatabaseService();
		if(!is_null($db) && @$db->IsConnected()) {
			// If we've been to this step, skip past framework.config.php
			$tpl->assign('step', STEP_INIT_DB);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		unset($db);
		
		// [JAS]: Detect available database drivers
		
		$drivers = array();
		
		if(extension_loaded('mysql')) {
			$drivers['mysql'] = 'MySQL 3.23/4.x/5.x';
		}
		
		if(extension_loaded('mysqli')) {
			$drivers['mysqli'] = 'MySQLi 4.x/5.x';
		}
		
		if(extension_loaded('pgsql')) {
			$drivers['postgres8'] = 'PostgreSQL 8.x';
			$drivers['postgres7'] = 'PostgreSQL 7.x';
			$drivers['postgres64'] = 'PostgreSQL 6.4';
		}

		if(extension_loaded('mssql')) {
			$drivers['mssql'] = 'Microsoft SQL Server 7.x/2000/2005';
		}
		
		if(extension_loaded('oci8')) {
			$drivers['oci8'] = 'Oracle 8/9';
		}
		
		$tpl->assign('drivers', $drivers);
		
		if(!empty($db_driver) && !empty($db_server) && !empty($db_name) && !empty($db_user)) {
			// Test the given settings, bypass platform initially
			include_once(DEVBLOCKS_PATH . "libs/adodb5/adodb.inc.php");
			$ADODB_CACHE_DIR = APP_PATH . "/tmp/cache";
			@$db =& ADONewConnection($db_driver);
			@$db->Connect($db_server, $db_user, $db_pass, $db_name);

			$tpl->assign('db_driver', $db_driver);
			$tpl->assign('db_server', $db_server);
			$tpl->assign('db_name', $db_name);
			$tpl->assign('db_user', $db_user);
			$tpl->assign('db_pass', $db_pass);
			
			// If passed, write config file and continue
			if(!is_null($db) && $db->IsConnected()) {
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
		
		if(CerberusInstaller::isDatabaseEmpty()) { // install
			$patchMgr = DevblocksPlatform::getPatchService();
			
			// [JAS]: Run our overloaded container for the platform
			$patchMgr->registerPatchContainer(new PlatformPatchContainer());
			
			// Clean script
			if(!$patchMgr->run()) {
				// [TODO] Show more info on the error
				$tpl->assign('template', 'steps/step_init_db.tpl.php');
				
			} else { // success
				// Read in plugin information from the filesystem to the database
				DevblocksPlatform::readPlugins();
				
				/*
				 * [TODO] This possibly needs to only start with core, because as soon 
				 * as we add back another feature with licensing we'll have installer 
				 * errors trying to license plugins before core runs its DB install.
				 */
				$plugins = DevblocksPlatform::getPluginRegistry();
				
				// Tailor which plugins are enabled by default
				if(is_array($plugins))
				foreach($plugins as $plugin_manifest) { /* @var $plugin_manifest DevblocksPluginManifest */
					switch ($plugin_manifest->id) {
						case "cerberusweb.core":
						case "cerberusweb.simulator":
						case "usermeet.core":
							$plugin_manifest->setEnabled(true);
							break;
						
						default:
							$plugin_manifest->setEnabled(false);
							break;
					}
				}
				
				DevblocksPlatform::clearCache();
				
				// Run enabled plugin patches
				$patches = DevblocksPlatform::getExtensions("devblocks.patch.container");
				
				if(is_array($patches))
				foreach($patches as $patch_manifest) { /* @var $patch_manifest DevblocksExtensionManifest */ 
					 $container = $patch_manifest->createInstance(); /* @var $container DevblocksPatchContainerExtension */
					 $patchMgr->registerPatchContainer($container);
				}
				
				if(!$patchMgr->run()) { // fail
					$tpl->assign('template', 'steps/step_init_db.tpl.php');
					
				} else {
					// success
					$tpl->assign('step', STEP_CONTACT);
					$tpl->display('steps/redirect.tpl.php');
					exit;
				}
			
				// [TODO] Verify the database
			}
			
			
		} else { // upgrade / patch
			/*
			 * [TODO] We should probably only forward to upgrade when we know 
			 * the proper tables were installed.  We may be repeating an install 
			 * request where the clean DB failed.
			 */
			$tpl->assign('step', STEP_UPGRADE);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
			
		break;
		

	// Personalize system information (title, timezone, language)
	case STEP_CONTACT:
		$settings = CerberusSettings::getInstance();
		
		@$default_reply_from = DevblocksPlatform::importGPC($_POST['default_reply_from'],'string',$settings->get(CerberusSettings::DEFAULT_REPLY_FROM));
		@$default_reply_personal = DevblocksPlatform::importGPC($_POST['default_reply_personal'],'string',$settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL));
		@$helpdesk_title = DevblocksPlatform::importGPC($_POST['helpdesk_title'],'string',$settings->get(CerberusSettings::HELPDESK_TITLE));
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		
		if(!empty($form_submit) && !empty($default_reply_from)) {
			
			$validate = imap_rfc822_parse_adrlist(sprintf("<%s>", $default_reply_from),"localhost");
			
			if(!empty($default_reply_from) && is_array($validate) && 1==count($validate)) {
				$settings->set(CerberusSettings::DEFAULT_REPLY_FROM, $default_reply_from);
			}
			
			if(!empty($default_reply_personal)) {
				$settings->set(CerberusSettings::DEFAULT_REPLY_PERSONAL, $default_reply_personal);
			}
			
			if(!empty($helpdesk_title)) {
				$settings->set(CerberusSettings::HELPDESK_TITLE, $helpdesk_title);
			}
			
			$tpl->assign('step', STEP_OUTGOING_MAIL);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		
		if(!empty($form_submit) && empty($default_reply_from)) {
			$tpl->assign('failed', true);
		}
		
		$tpl->assign('default_reply_from', $default_reply_from);
		$tpl->assign('default_reply_personal', $default_reply_personal);
		$tpl->assign('helpdesk_title', $helpdesk_title);
		
		$tpl->assign('template', 'steps/step_contact.tpl.php');
		
		break;
	
	// Set up and test the outgoing SMTP
	case STEP_OUTGOING_MAIL:
		$settings = CerberusSettings::getInstance();
		
		@$smtp_host = DevblocksPlatform::importGPC($_POST['smtp_host'],'string',$settings->get(CerberusSettings::SMTP_HOST,'localhost'));
		@$smtp_port = DevblocksPlatform::importGPC($_POST['smtp_port'],'integer',$settings->get(CerberusSettings::SMTP_PORT,25));
		@$smtp_auth_user = DevblocksPlatform::importGPC($_POST['smtp_auth_user'],'string');
		@$smtp_auth_pass = DevblocksPlatform::importGPC($_POST['smtp_auth_pass'],'string');
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$passed = DevblocksPlatform::importGPC($_POST['passed'],'integer');
		
		if(!empty($form_submit)) {
			$mail_service = DevblocksPlatform::getMailService();
			
			$mailer = null;
			try {
				$mailer = $mail_service->getMailer($smtp_host, $smtp_auth_user, $smtp_auth_pass, $smtp_port); // [TODO] port
				$mailer->connect();
				$mailer->disconnect();
				
				if(!empty($smtp_host))
					$settings->set(CerberusSettings::SMTP_HOST, $smtp_host);
				if(!empty($smtp_port))
					$settings->set(CerberusSettings::SMTP_PORT, $smtp_port);
				if(!empty($smtp_auth_user)) {
					$settings->set(CerberusSettings::SMTP_AUTH_ENABLED, 1);
					$settings->set(CerberusSettings::SMTP_AUTH_USER, $smtp_auth_user);
					$settings->set(CerberusSettings::SMTP_AUTH_PASS, $smtp_auth_pass);
				} else {
					$settings->set(CerberusSettings::SMTP_AUTH_ENABLED, 0);
				}
				
				$tpl->assign('step', STEP_INCOMING_MAIL);
				$tpl->display('steps/redirect.tpl.php');
				exit;
				
			}
			catch(Exception $e) {
				$form_submit = 0;
				$tpl->assign('smtp_error_display', 'SMTP Connection Failed! ' . $e->getMessage());
			}
			$tpl->assign('smtp_host', $smtp_host);
			$tpl->assign('smtp_port', $smtp_port);
			$tpl->assign('smtp_auth_user', $smtp_auth_user);
			$tpl->assign('smtp_auth_pass', $smtp_auth_pass);
			$tpl->assign('form_submit', $form_submit);
		} else {
			$tpl->assign('smtp_host', 'localhost');
			$tpl->assign('smtp_port', '25');
		}
		
		// First time, or retry
		$tpl->assign('template', 'steps/step_outgoing_mail.tpl.php');
		
		break;

	// Set up a POP3/IMAP mailbox
	case STEP_INCOMING_MAIL:
		@$imap_service = DevblocksPlatform::importGPC($_POST['imap_service'],'string');
		@$imap_host = DevblocksPlatform::importGPC($_POST['imap_host'],'string');
		@$imap_user = DevblocksPlatform::importGPC($_POST['imap_user'],'string');
		@$imap_pass = DevblocksPlatform::importGPC($_POST['imap_pass'],'string');
		@$imap_port = DevblocksPlatform::importGPC($_POST['imap_port'],'integer');
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');

		// Allow skip by submitting a blank form
		// Skip if we already have a pop3 box defined.
		$accounts = DAO_Mail::getPop3Accounts();
		$skip = (!empty($form_submit) && empty($imap_host) && empty($imap_user)) ? true : false; 
		if($skip OR !empty($accounts)) {
			$tpl->assign('step', STEP_WORKFLOW);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		
		if(!empty($form_submit)) {
			$mail = DevblocksPlatform::getMailService();

			// Test mailbox
			if($mail->testImap($imap_host,$imap_port,$imap_service,$imap_user,$imap_pass)) { // Success!
				// [TODO] Check to make sure the details aren't duplicate
	            // [TODO] Set protocol

                $fields = array(
				    'enabled' => 1,
					'nickname' => 'POP3',
					'protocol' => $imap_service,
					'host' => $imap_host,
					'username' => $imap_user,
					'password' => $imap_pass,
					'port' => $imap_port
				);
			    $id = DAO_Mail::createPop3Account($fields);
				
				$tpl->assign('step', STEP_WORKFLOW);
				$tpl->display('steps/redirect.tpl.php');
				exit;
				
			} else { // Failed
				$tpl->assign('imap_service', $imap_service);
				$tpl->assign('imap_host', $imap_host);
				$tpl->assign('imap_user', $imap_user);
				$tpl->assign('imap_pass', $imap_pass);
				$tpl->assign('imap_port', $imap_port);
				
				$tpl->assign('failed', true);
				$tpl->assign('error_msgs', $mail->getErrors());
				$tpl->assign('template', 'steps/step_incoming_mail.tpl.php');
			}
			
		} else { // defaults
			$tpl->assign('imap_port', 110);
		}
		
		$tpl->assign('template', 'steps/step_incoming_mail.tpl.php');
		
		break;
		
	// Create initial workers, mailboxes, teams
	case STEP_WORKFLOW:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		$settings = CerberusSettings::getInstance();

		// Catch the submit
		switch($form_submit) {
			case 1: // names form submit
				@$workers = DevblocksPlatform::importGPC($_POST['worker'],'array',array());
				@$teams_str = DevblocksPlatform::importGPC($_POST['teams'],'string','');
				
				$worker_ids = array();
				$team_ids = array();

				// Clear any empties
				foreach($workers as $idx => $w) {
					if(empty($w))
						unset($workers[$idx]);
				}
				
				$teams = CerberusApplication::parseCrlfString($teams_str);
				
				if(empty($workers) || empty($teams)) {
					$tpl->assign('failed', true);
					$tpl->assign('workers', $workers);
					$tpl->assign('teams_str', $teams_str);
					$tpl->assign('template', 'steps/step_workflow.tpl.php');
					break;
				}
				
				// Create worker records
				foreach($workers as $worker_email) {
					if(empty($worker_email)) continue;
					$id = DAO_Worker::create($worker_email,'new','First','Last','');
					$worker_ids[$id] = $worker_email; 
				}
				
				// Create team records
				if(is_array($teams))
				foreach($teams as $team_name) {
					if(empty($team_name)) continue;
					$fields = array(
						DAO_Group::TEAM_NAME => $team_name
					);
					$id = DAO_Group::createTeam($fields);
					$team_ids[$id] = $team_name;
				}
				
				$tpl->assign('worker_ids', $worker_ids);
				$tpl->assign('team_ids', $team_ids);
				$tpl->assign('default_reply_from', $settings->get(CerberusSettings::DEFAULT_REPLY_FROM));
				$tpl->assign('template', 'steps/step_workflow2.tpl.php');
				break;
				
			case 2: // detailed form submit
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_ids'],'array');
				@$worker_first = DevblocksPlatform::importGPC($_POST['worker_first'],'array');
				@$worker_last = DevblocksPlatform::importGPC($_POST['worker_last'],'array');
				@$worker_title = DevblocksPlatform::importGPC($_POST['worker_title'],'array');
				@$worker_superuser = DevblocksPlatform::importGPC($_POST['worker_superuser'],'array');
				@$worker_pw = DevblocksPlatform::importGPC($_POST['worker_pw'],'array');
				@$team_ids = DevblocksPlatform::importGPC($_POST['team_ids'],'array');

				$replyFrom = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
				$replyPersonal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');
				$url = DevblocksPlatform::getUrlService();
				
				// Worker Details
				if(is_array($worker_ids))
				foreach($worker_ids as $idx => $worker_id) {
				    $worker = DAO_Worker::getAgent($worker_id);
					$worker_email_parts = explode('@', $worker->email);
					
					@$sFirst = !empty($worker_first[$idx]) ? $worker_first[$idx] : ucwords($worker_email_parts[0]);
					@$sLast = !empty($worker_last[$idx]) ? $worker_last[$idx] : '';
					@$sPassword = !empty($worker_pw[$idx]) ? $worker_pw[$idx] : '';
					
				    if(empty($sPassword)) {
				    	$sPassword = CerberusApplication::generatePassword(8);
				    	
				        $mail_service = DevblocksPlatform::getMailService();
				        $mailer = $mail_service->getMailer();
				        $mail = $mail_service->createMessage();
				        
				        $sendTo = new Swift_Address($worker->email, $worker->getName());
				        $sendFrom = new Swift_Address($replyFrom, $replyPersonal);
				        
				        $mail->setSubject('Your new helpdesk login information!');
				        $mail->generateId();
				        $mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
				        
					    $body = sprintf("Your new helpdesk login information is below:\r\n".
							"\r\n".
					        "URL: %s\r\n".
					        "Login: %s\r\n".
					        "Password: %s\r\n".
					        "\r\n".
					        "You should change your password from Preferences after logging in for the first time.\r\n".
					        "\r\n",
						        $url->write('',true),
						        $worker->email,
						        $sPassword
					    );
				        
					    $mail->attach(new Swift_Message_Part($body));

						if(!$mailer->send($mail, $sendTo, $sendFrom)) {
							// [TODO] Report when the message wasn't sent.
						}
				    }
				    
					$fields = array(
						DAO_Worker::FIRST_NAME => $sFirst,
						DAO_Worker::LAST_NAME => $sLast,
						DAO_Worker::TITLE => $worker_title[$idx],
						DAO_Worker::PASSWORD => md5($sPassword),
						DAO_Worker::IS_SUPERUSER => (in_array($worker_id,$worker_superuser) ? 1 : 0)
					);
					DAO_Worker::updateAgent($worker_id, $fields);
				}
				
				// Team Details
				if(is_array($team_ids))
				foreach($team_ids as $idx => $team_id) {
					@$team_members = DevblocksPlatform::importGPC($_POST['team_members_'.$team_id],'array');
					
					// Team Members
					if(is_array($team_members))
					foreach($team_members as $team_member_id) {
						DAO_Group::setTeamMember($team_id, $team_member_id, false);
					}
				}
				
				$tpl->assign('step', STEP_CATCHALL);
				$tpl->display('steps/redirect.tpl.php');
				exit;
				
				break;
				
			default: // first time
				$tpl->assign('teams_str', "Dispatch\n");
				$tpl->assign('template', 'steps/step_workflow.tpl.php');
				break;
		}
		
		break;

	case STEP_CATCHALL:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		
		if(!empty($form_submit)) {
			@$default_team_id = DevblocksPlatform::importGPC($_POST['default_team_id'],'integer');
			
			$settings = CerberusSettings::getInstance();
			$settings->set(CerberusSettings::DEFAULT_TEAM_ID,$default_team_id);
			
			$tpl->assign('step', STEP_REGISTER);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$tpl->assign('template', 'steps/step_catchall.tpl.php');
		
		break;
		
	case STEP_REGISTER:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		
		if(!empty($form_submit)) {
			$tpl->assign('step', STEP_FINISHED);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		
		$tpl->assign('template', 'steps/step_register.tpl.php');
		break;
		
	case STEP_UPGRADE:
		$patchMgr = DevblocksPlatform::getPatchService();
		$patchMgr->clear();
		
		// [JAS]: Run our overloaded container for the platform
		$patchMgr->registerPatchContainer(new PlatformPatchContainer());
		
		// Clean script
		if(!$patchMgr->run()) {
			// [TODO] Show more info on the error
			$tpl->assign('template', 'steps/step_upgrade.tpl.php');
			
		} else { // success
			// Read in plugin information from the filesystem to the database
			DevblocksPlatform::readPlugins();
			DevblocksPlatform::clearCache();
			
			// Run enabled plugin patches
			$patches = DevblocksPlatform::getExtensions("devblocks.patch.container");
			
			if(is_array($patches))
			foreach($patches as $patch_manifest) { /* @var $patch_manifest DevblocksExtensionManifest */ 
				 $container = $patch_manifest->createInstance(); /* @var $container DevblocksPatchContainerExtension */
				 $patchMgr->registerPatchContainer($container);
			}
			
			if(!$patchMgr->run()) { // fail
				$tpl->assign('template', 'steps/step_upgrade.tpl.php');
				
			} else { // pass
				$tpl->assign('step', STEP_FINISHED);
				$tpl->display('steps/redirect.tpl.php');
				exit;
			}
		}
		
		break;
		
	// [TODO] Delete the /install/ directory (security)
	case STEP_FINISHED:
		
		// Set up the default cron jobs
		$crons = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
		if(is_array($crons))
		foreach($crons as $id => $cron) { /* @var $cron CerberusCronPageExtension */
			switch($id) {
				case 'cron.pop3':
					$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
					$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
					$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
					$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Today'));
					break;
				case 'cron.parser':
					$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
					$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '1');
					$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
					$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Today'));
					break;
				case 'cron.maint':
					$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
					$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '24');
					$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'h');
					$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday'));
					break;
			}
			
		}
		
		$tpl->assign('template', 'steps/step_finished.tpl.php');
		break;
}

// [TODO] Check apache rewrite (somehow)

// [TODO] Check if safe_mode is disabled, and if so set our php.ini overrides in the framework.config.php rewrite

/*
Jeremy: yup... that's it... :)
stupid adodb hiding that error
I switched framework.config.php to have mysqli as the db driver (the extension which I have loaded) and it works

Jeff: k, sweet. I just need to add that to the dropdown, and then have the platform or installer check for any of the possible ones being there and complain if none
I'll add to install/index.php [TODO]
 */

$tpl->display('base.tpl.php');
