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
define('STEP_DEFAULTS', 8);
//define('STEP_INCOMING_MAIL', 7);
//define('STEP_WORKFLOW', 8);
//define('STEP_CATCHALL', 9);
//define('STEP_ANTISPAM', x);
define('STEP_REGISTER', 9);
define('STEP_UPGRADE', 10);
define('STEP_FINISHED', 11);

define('TOTAL_STEPS', 11);

// Import GPC variables to determine our scope/step.
@$step = DevblocksPlatform::importGPC($_REQUEST['step'],'integer');

/*
 * [TODO] We can run some quick tests to bypass steps we've already passed
 * even when returning to the page with a NULL step.
 */
if(empty($step)) $step = STEP_ENVIRONMENT;

// [TODO] Could convert to CerberusApplication::checkRequirements()

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

if(!is_writeable(APP_PATH . "/storage/")) {
	die(realpath(APP_PATH . "/storage/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_PATH . "/storage/import/fail/")) {
	die(realpath(APP_PATH . "/storage/import/fail/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_PATH . "/storage/import/new/")) {
	die(realpath(APP_PATH . "/storage/import/new/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
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

		$memory_limit = ini_get("memory_limit");
		if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
			$results['memory_limit'] = true;
		} else {
			$ini_memory_limit = intval($memory_limit);
			if($ini_memory_limit >= 16) {
				$results['memory_limit'] = true;
			} else {
				$results['memory_limit'] = false;
				$fails++;
			}
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

		// Extension: SPL
		if(extension_loaded("spl")) {
			$results['ext_spl'] = true;
		} else {
			$results['ext_spl'] = false;
			$fails++;
		}

		// Extension: GD
		if(extension_loaded("gd") && function_exists('imagettfbbox')) {
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
		
		// Extension: DOM
		if(extension_loaded("dom")) {
			$results['ext_dom'] = true;
		} else {
			$results['ext_dom'] = false;
			$fails++;
		}
		
		// Extension: XML
		if(extension_loaded("xml")) {
			$results['ext_xml'] = true;
		} else {
			$results['ext_xml'] = false;
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
		
//		if(extension_loaded('pgsql')) {
//			$drivers['postgres8'] = 'PostgreSQL 8.x';
//			$drivers['postgres7'] = 'PostgreSQL 7.x';
//			$drivers['postgres64'] = 'PostgreSQL 6.4';
//		}
//
//		if(extension_loaded('mssql')) {
//			$drivers['mssql'] = 'Microsoft SQL Server 7.x/2000/2005';
//		}
//		
//		if(extension_loaded('oci8')) {
//			$drivers['oci8'] = 'Oracle 8/9';
//		}
		
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
		
		if(DevblocksPlatform::isDatabaseEmpty()) { // install
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
						case "cerberusweb.watchers":
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
		@$smtp_enc = DevblocksPlatform::importGPC($_POST['smtp_enc'],'string',$settings->get(CerberusSettings::SMTP_ENCRYPTION_TYPE,'None'));
		@$smtp_auth_user = DevblocksPlatform::importGPC($_POST['smtp_auth_user'],'string');
		@$smtp_auth_pass = DevblocksPlatform::importGPC($_POST['smtp_auth_pass'],'string');
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$passed = DevblocksPlatform::importGPC($_POST['passed'],'integer');
		
		if(!empty($form_submit)) {
			$mail_service = DevblocksPlatform::getMailService();
			
			$mailer = null;
			try {
				$mailer = $mail_service->getMailer($smtp_host, $smtp_auth_user, $smtp_auth_pass, $smtp_port, $smtp_enc); // [TODO] port
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
				if(!empty($smtp_enc))
					$settings->set(CerberusSettings::SMTP_ENCRYPTION_TYPE, $smtp_enc);
				
				$tpl->assign('step', STEP_DEFAULTS);
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
			$tpl->assign('smtp_enc', $smtp_enc);
			$tpl->assign('form_submit', $form_submit);
		} else {
			$tpl->assign('smtp_host', 'localhost');
			$tpl->assign('smtp_port', '25');
			$tpl->assign('smtp_enc', 'None');
		}
		
		// First time, or retry
		$tpl->assign('template', 'steps/step_outgoing_mail.tpl.php');
		
		break;

	// Set up the default objects
	case STEP_DEFAULTS:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$worker_email = DevblocksPlatform::importGPC($_POST['worker_email'],'string');
		@$worker_pass = DevblocksPlatform::importGPC($_POST['worker_pass'],'string');
		@$worker_pass2 = DevblocksPlatform::importGPC($_POST['worker_pass2'],'string');

		$settings = CerberusSettings::getInstance();
		
		if(!empty($form_submit)) {
			// Persist form scope
			$tpl->assign('worker_email', $worker_email);
			$tpl->assign('worker_pass', $worker_pass);
			$tpl->assign('worker_pass2', $worker_pass2);
			
			// Sanity/Error checking
			if(!empty($worker_email) && !empty($worker_pass) && $worker_pass == $worker_pass2) {
				// If we have no groups, make a Dispatch group
				$groups = DAO_Group::getAll(true);
				if(empty($groups)) {
					// Dispatch Group
					$dispatch_gid = DAO_Group::createTeam(array(
						DAO_Group::TEAM_NAME => 'Dispatch',
					));
					
					// Dispatch Spam Bucket
					$dispatch_spam_bid = DAO_Bucket::create('Spam', $dispatch_gid);
					DAO_GroupSettings::set($dispatch_gid,DAO_GroupSettings::SETTING_SPAM_ACTION,'2');
					DAO_GroupSettings::set($dispatch_gid,DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM,$dispatch_spam_bid);
					DAO_GroupSettings::set($dispatch_gid,DAO_GroupSettings::SETTING_SPAM_THRESHOLD,'85');
					
					// Support Group
					$support_gid = DAO_Group::createTeam(array(
						DAO_Group::TEAM_NAME => 'Support',
					));

					// Support Spam Bucket
					$support_spam_bid = DAO_Bucket::create('Spam', $support_gid);
					DAO_GroupSettings::set($support_gid,DAO_GroupSettings::SETTING_SPAM_ACTION,'2');
					DAO_GroupSettings::set($support_gid,DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM,$support_spam_bid);
					DAO_GroupSettings::set($support_gid,DAO_GroupSettings::SETTING_SPAM_THRESHOLD,'85');
					
					// Sales Group
					$sales_gid = DAO_Group::createTeam(array(
						DAO_Group::TEAM_NAME => 'Sales',
					));
					
					// Sales Spam Bucket
					$sales_spam_bid = DAO_Bucket::create('Spam', $sales_gid);
					DAO_GroupSettings::set($sales_gid,DAO_GroupSettings::SETTING_SPAM_ACTION,'2');
					DAO_GroupSettings::set($sales_gid,DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM,$sales_spam_bid);
					DAO_GroupSettings::set($sales_gid,DAO_GroupSettings::SETTING_SPAM_THRESHOLD,'85');
					
					// Default catchall
					$settings->set(CerberusSettings::DEFAULT_TEAM_ID,$dispatch_gid);
				}
				
				// If this worker doesn't exist, create them
				if(null === ($lookup = DAO_Worker::lookupAgentEmail($worker_email))) {
					$worker_id = DAO_Worker::create(
						$worker_email, // email
						$worker_pass, // pass
						'Super', // first
						'User', // last
						'Administrator' // title
					);
	
					// Superuser bit
					$fields = array(
						DAO_Worker::IS_SUPERUSER => 1, 
					);
					DAO_Worker::updateAgent($worker_id, $fields);
					
					// Authorize this e-mail address (watchers, etc.)
					DAO_AddressToWorker::assign($worker_email, $worker_id);
					DAO_AddressToWorker::update($worker_email, array(
						DAO_AddressToWorker::IS_CONFIRMED => 1
					));					
					
					// Default group memberships
					if(!empty($dispatch_gid))
						DAO_Group::setTeamMember($dispatch_gid,$worker_id,true);			
					if(!empty($support_gid))
						DAO_Group::setTeamMember($support_gid,$worker_id,true);			
					if(!empty($sales_gid))
						DAO_Group::setTeamMember($sales_gid,$worker_id,true);			
				}
				
				// Send a first ticket which allows people to reply for support
				if(null !== ($default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM,''))) {
					$message = new CerberusParserMessage();
						$message->headers['from'] = '"WebGroup Media, LLC." <support@webgroupmedia.com>';
						$message->headers['to'] = $default_from;
						$message->headers['subject'] = "Welcome to Cerberus Helpdesk 4.0!";
						$message->headers['date'] = date('r');
						$message->headers['message-id'] = CerberusApplication::generateMessageId();
						$message->body = <<< EOF
Welcome to Cerberus Helpdesk 4.0!

We automatically set up a few things for you during the installation process.

You'll notice you have three groups:
* Dispatch: All your mail will be delivered to this group by default.
* Support: This is a group for holding tickets related to customer service.
* Sales: This is a group for holding tickets relates to sales.

If these default groups don't meet your needs, feel free to change them by clicking 'Helpdesk Setup' in the top-right and selecting the 'Groups' tab.

We also set up a 'Spam' bucket inside each group to start quarantining junk mail.  Your helpdesk's spam training functionality is adaptive and will become increasingly accurate as you use your helpdesk.

If you have any questions about your new helpdesk, simply reply to this message.  Our response will show up on this page as a new message.

---
The Cerb4 Team
WebGroup Media, LLC.
http://www.cerberusweb.com/
EOF;
					CerberusParser::parseMessage($message);
				}
				
				$tpl->assign('step', STEP_REGISTER);
				$tpl->display('steps/redirect.tpl.php');
				exit;
				
			} else {
				$tpl->assign('failed', true);
				
			}
			
		} else {
			// Defaults
			
		}
		
		$tpl->assign('template', 'steps/step_defaults.tpl.php');
		
		break;
		
	case STEP_REGISTER:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$skip = DevblocksPlatform::importGPC($_POST['skip'],'integer',0);
		
		if(!empty($form_submit)) {
			if(empty($skip)) {
				$settings = CerberusSettings::getInstance();
				@$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM,'');
				
				@$contact_name = str_replace(array("\r","\n"),'',stripslashes($_REQUEST['contact_name']));
				@$contact_company = stripslashes($_REQUEST['contact_company']);
				@$contact_phone = stripslashes($_REQUEST['contact_phone']);
				@$q1 = stripslashes($_REQUEST['q1']);
				@$q2 = stripslashes($_REQUEST['q2']);
				@$q3 = stripslashes($_REQUEST['q3']);
				@$q4 = stripslashes($_REQUEST['q4']);
				@$q5_support = stripslashes($_REQUEST['q5_support']);
				@$q5_opensource = stripslashes($_REQUEST['q5_opensource']);
				@$q5_price = stripslashes($_REQUEST['q5_price']);
				@$q5_updates = stripslashes($_REQUEST['q5_updates']);
				@$q5_developers = stripslashes($_REQUEST['q5_developers']);
				@$q5_community = stripslashes($_REQUEST['q5_community']);
				@$comments = stripslashes($_REQUEST['comments']);
				
				if(isset($_REQUEST['form_submit'])) {
				  $msg = sprintf(
				    "Contact Name: %s\r\n".
				    "Organization: %s\r\n".
				    "Phone: %s\r\n".
				    "\r\n".
				    "#1: Briefly, what does your organization do?\r\n%s\r\n\r\n".
				    "#2: How is your team currently handling e-mail management?\r\n%s\r\n\r\n".
				    "#3: Are you considering both free and commercial solutions?\r\n%s\r\n\r\n".
				    "#4: What will be your first important milestone?\r\n%s\r\n\r\n".
				    "#5: How important are the following benefits in making your decision?\r\n".
				    "Near-Instant Support: %d\r\nAvailable Source Code: %d\r\nCompetitive Purchase Price: %d\r\n".
				    "Frequent Product Updates: %d\r\nAccess to Developers: %d\r\nLarge User Community: %d\r\n".
				    "\r\n".
				    "Additional Comments: \r\n%s\r\n\r\n"
				    ,
				    $contact_name,
				    $contact_company,
				    $contact_phone,
				    $q1,
				    $q2,
				    $q3,
				    $q4,
				    $q5_support,
				    $q5_opensource,
				    $q5_price,
				    $q5_updates,
				    $q5_developers,
				    $q5_community,
				    $comments
				  );

				  CerberusMail::quickSend('aboutme@cerberusweb.com',"About: $contact_name of $contact_company",$msg);
				}
			}
			
			$tpl->assign('step', STEP_FINISHED);
			$tpl->display('steps/redirect.tpl.php');
			exit;
		}
		
		$tpl->assign('template', 'steps/step_register.tpl.php');
		break;
		
	case STEP_UPGRADE:
		$tpl->assign('template', 'steps/step_upgrade.tpl.php');
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
				case 'cron.heartbeat':
					$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
					$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
					$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
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
