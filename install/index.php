<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

if(version_compare(PHP_VERSION, "7.2", "<")) {
	http_response_code(500);
	die("Cerb requires PHP 7.2 or later.");
}

if(!extension_loaded('mysqli')) {
	http_response_code(500);
	die("Cerb requires the 'mysqli' PHP extension.  Please enable it.");
}

if(!extension_loaded('mbstring')) {
	http_response_code(500);
	die("Cerb requires the 'mbstring' PHP extension.  Please enable it.");
}

@set_time_limit(3600); // 1hr
require_once('../framework.config.php');
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');
require_once(APP_PATH . '/api/Application.class.php');
require_once(APP_PATH . '/install/classes.php');

DevblocksPlatform::services()->cache()->clean();

// DevblocksPlatform::init() workaround
if(!defined('DEVBLOCKS_WEBPATH')) {
	$php_self = $_SERVER["SCRIPT_NAME"];
	$php_self = str_replace('/install','',$php_self);
	$pos = strrpos($php_self,'/');
	$php_self = substr($php_self,0,$pos) . '/';
	@define('DEVBLOCKS_WEBPATH',$php_self);
	@define('DEVBLOCKS_APP_WEBPATH',$php_self);
}

DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');

define('STEP_ENVIRONMENT', 1);
define('STEP_LICENSE', 2);
define('STEP_DATABASE', 3);
define('STEP_SAVE_CONFIG_FILE', 4);
define('STEP_INIT_DB', 5);
define('STEP_OUTGOING_MAIL', 6);
define('STEP_DEFAULTS', 7);
define('STEP_PACKAGES', 8);
define('STEP_REGISTER', 9);
define('STEP_UPGRADE', 10);
define('STEP_FINISHED', 11);

define('TOTAL_STEPS', 11);

// Import GPC variables to determine our scope/step.
@$step = DevblocksPlatform::importGPC($_REQUEST['step'],'integer',0) ?: STEP_ENVIRONMENT;

// [TODO] Could convert to CerberusApplication::checkRequirements()

@chmod(APP_TEMP_PATH, 0770);
@mkdir(APP_SMARTY_COMPILE_PATH);
@chmod(APP_SMARTY_COMPILE_PATH, 0770);
@mkdir(APP_TEMP_PATH . '/cache/');
@chmod(APP_TEMP_PATH . '/cache/', 0770);

if(!file_exists(APP_PATH . "/vendor/")) {
	DevblocksPlatform::dieWithHttpError(APP_PATH . "/vendor/" ." doesn't exist. Did you run `composer install` first?", 500);
}

// Make sure the temporary directories of Devblocks are writeable.
if(!is_writeable(APP_TEMP_PATH)) {
	DevblocksPlatform::dieWithHttpError(APP_TEMP_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.", 500);
}

if(!is_writeable(APP_SMARTY_COMPILE_PATH)) {
	DevblocksPlatform::dieWithHttpError(APP_SMARTY_COMPILE_PATH . " is not writeable by the webserver.  Please adjust permissions and reload this page.", 500);
}

if(!is_writeable(APP_TEMP_PATH . "/cache/")) {
	DevblocksPlatform::dieWithHttpError(APP_TEMP_PATH . "/cache/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.", 500);
}

@chmod(APP_STORAGE_PATH, 0770);
@chmod(APP_STORAGE_PATH . '/mail/new/', 0770);
@chmod(APP_STORAGE_PATH . '/mail/fail/', 0770);

if(!is_writeable(APP_STORAGE_PATH)) {
	DevblocksPlatform::dieWithHttpError(APP_STORAGE_PATH . " is not writeable by the webserver.  Please adjust permissions and reload this page.", 500);
}

if(!is_writeable(APP_STORAGE_PATH . "/mail/new/")) {
	DevblocksPlatform::dieWithHttpError(APP_STORAGE_PATH . "/mail/new/ is not writeable by the webserver.  Please adjust permissions and reload this page.", 500);
}

if(!is_writeable(APP_STORAGE_PATH . "/mail/fail/")) {
	DevblocksPlatform::dieWithHttpError(APP_STORAGE_PATH . "/mail/fail/ is not writeable by the webserver.  Please adjust permissions and reload this page.", 500);
}

// [TODO] Move this to the framework init (installer blocks this at the moment)
DevblocksPlatform::setLocale('en_US');

// Get a reference to the template system and configure it
$tpl = DevblocksPlatform::services()->template();
$tpl->template_dir = APP_PATH . '/install/templates';
$tpl->caching = 0;

$tpl->assign('step', $step);

switch($step) {
	default:
	case STEP_ENVIRONMENT:
		$results = array();
		$fails = 0;
		
		// PHP Version
		if(version_compare(PHP_VERSION,"7.2") >=0) {
			$results['php_version'] = PHP_VERSION;
		} else {
			$results['php_version'] = false;
			$fails++;
		}
		
		// Mailparse version
		if(version_compare(phpversion('mailparse'),"3.0.2") >= 0) {
			$results['mailparse_version'] = phpversion('mailparse');
		} else {
			$results['mailparse_version'] = false;
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
		if ($memory_limit == '' || $memory_limit == -1) { // empty string means failure or not defined, assume no compiled memory limits
			$results['memory_limit'] = true;
		} else {
			$ini_memory_limit = DevblocksPlatform::parseBytesString($memory_limit);
			if($ini_memory_limit >= 16777216) {
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
		
		// Extension: cURL
		if(extension_loaded("curl")) {
			$results['ext_curl'] = true;
		} else {
			$results['ext_curl'] = false;
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

		// Extension: ctype
		if(extension_loaded("ctype")) {
			$results['ext_ctype'] = true;
		} else {
			$results['ext_ctype'] = false;
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
		
		// Extension: MySQLi
		if(extension_loaded("mysqli")) {
			$results['ext_mysqli'] = true;
		} else {
			$results['ext_mysqli'] = false;
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
		
		// Extension: JSON
		if(extension_loaded("json")) {
			$results['ext_json'] = true;
		} else {
			$results['ext_json'] = false;
			$fails++;
		}
		
		// Extension: OpenSSL
		if(extension_loaded("openssl")) {
			$results['ext_openssl'] = true;
		} else {
			$results['ext_openssl'] = false;
			$fails++;
		}
		
		// Extension: YAML
		if(extension_loaded("yaml")) {
			$results['ext_yaml'] = true;
		} else {
			$results['ext_yaml'] = false;
			$fails++;
		}
		
		$tpl->assign('fails', $fails);
		$tpl->assign('results', $results);
		$tpl->assign('template', 'steps/step_environment.tpl');
		
		break;
	
	case STEP_LICENSE:
			@$accept = DevblocksPlatform::importGPC($_POST['accept'],'integer', 0);
			
			if(1 == $accept) {
			$tpl->assign('step', STEP_DATABASE);
			$tpl->display('steps/redirect.tpl');
			exit;
			}
		
		$tpl->assign('template', 'steps/step_license.tpl');
		
			break;
	
	// Configure and test the database connection
	case STEP_DATABASE:
		// Import scope (if post)
		@$db_driver = DevblocksPlatform::importGPC($_POST['db_driver'],'string');
		@$db_engine = DevblocksPlatform::importGPC($_POST['db_engine'],'string');
		@$db_server = DevblocksPlatform::importGPC($_POST['db_server'],'string');
		@$db_name = DevblocksPlatform::importGPC($_POST['db_name'],'string');
		@$db_user = DevblocksPlatform::importGPC($_POST['db_user'],'string');
		@$db_pass = DevblocksPlatform::importGPC($_POST['db_pass'],'string');

		if(defined('APP_DB_HOST') && defined('APP_DB_DATABASE') && APP_DB_HOST && APP_DB_DATABASE) {
			// If we've been to this step, skip past framework.config.php
			$tpl->assign('step', STEP_INIT_DB);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
		
		// [JAS]: Detect available database drivers
		
		$drivers = array();
		
		if(extension_loaded('mysqli'))
			$drivers['mysqli'] = 'MySQLi';
		
		$tpl->assign('drivers', $drivers);
		
		$engines = array(
			'innodb' => 'InnoDB (Recommended)',
			'myisam' => 'MyISAM (Legacy)',
		);
		
		$tpl->assign('engines', $engines);
		
		if(!empty($db_driver) && !empty($db_engine) && !empty($db_server) && !empty($db_name) && !empty($db_user)) {
			$db_passed = false;
			$errors = array();
			
			if(false !== (@$_db = mysqli_connect($db_server, $db_user, $db_pass))) {
				if(false !== mysqli_select_db($_db, $db_name)) {
					$db_passed = true;
				} else {
					$db_passed = false;
					$errors[] = mysqli_error($_db);
				}
				
				// Check if the engine we want exists, otherwise default
				$rs = mysqli_query($_db, "SHOW ENGINES");
				
				if(!($rs instanceof mysqli_result)) {
					$db_passed = false;
					$errors[] = "Can't run SHOW ENGINES query against the database.";
				}
				
				$discovered_engines = array();
				while($row = mysqli_fetch_assoc($rs)) {
					$discovered_engines[] = mb_strtolower($row['Engine']);
				}
				mysqli_free_result($rs);
				
				// Check the preferred DB engine
				if(!in_array($db_engine, $discovered_engines)) {
					$db_passed = false;
					$errors[] = sprintf("The '%s' storage engine is not enabled.", $db_engine);
				}
				
				// Check user privileges
				if($db_passed) {
					$engine = sprintf(" ENGINE=%s", mysqli_real_escape_string($_db, $db_engine));
					
					// RESET
					mysqli_query($_db, "DROP TABLE IF EXISTS _installer_test_suite");
					
					// CREATE TABLE
					if($db_passed && false === mysqli_query($_db, "CREATE TABLE _installer_test_suite (id int)" . $engine)) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the CREATE privilege.");
					}
					// INSERT
					if($db_passed && false === mysqli_query($_db, "INSERT INTO _installer_test_suite (id) values(1)")) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the INSERT privilege.");
					}
					// SELECT
					if($db_passed && false === mysqli_query($_db, "SELECT id FROM _installer_test_suite")) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the SELECT privilege.");
					}
					// UPDATE
					if($db_passed && false === mysqli_query($_db, "UPDATE _installer_test_suite SET id = 2")) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the UPDATE privilege.");
					}
					// DELETE
					if($db_passed && false === mysqli_query($_db, "DELETE FROM _installer_test_suite WHERE id > 0")) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the DELETE privilege.");
					}
					// ALTER TABLE
					if($db_passed && false === mysqli_query($_db, "ALTER TABLE _installer_test_suite MODIFY COLUMN id int unsigned")) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the ALTER privilege.");
					}
					// ADD FULLTEXT INDEX
					if($db_passed && false === mysqli_query($_db, "ALTER TABLE _installer_test_suite ADD COLUMN content TEXT, ADD FULLTEXT (content)")) {
						$db_passed = false;
						$errors[] = sprintf("The database engine doesn't support FULLTEXT indexes.");
					}
					// The table must be empty to add the spatial index (must not contain null values)
					if($db_passed && false === mysqli_query($_db, "DELETE FROM _installer_test_suite")) {
						$db_passed = false;
						$errors[] = "Failed to empty the test table in the database.";
					}
					// ADD SPATIAL INDEX
					if($db_passed && false === mysqli_query($_db, "ALTER TABLE _installer_test_suite ADD COLUMN pos POINT NOT NULL")) {
						$db_passed = false;
						$errors[] = sprintf("The database engine doesn't support POINT columns.");
					}
					if($db_passed && false === mysqli_query($_db, "ALTER TABLE _installer_test_suite ADD SPATIAL INDEX (pos)")) {
						$db_passed = false;
						$errors[] = sprintf("The database engine doesn't support SPATIAL indexes.");
					}
					// DROP TABLE
					if($db_passed && false === mysqli_query($_db, "DROP TABLE IF EXISTS _installer_test_suite")) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the DROP privilege.");
					}
					// CREATE TEMPORARY TABLES
					if($db_passed && false === mysqli_query($_db, "CREATE TEMPORARY TABLE IF NOT EXISTS _installer_test_suite_tmp (id int)" . $engine)) {
						$db_passed = false;
						$errors[] = sprintf("The database user lacks the CREATE TEMPORARY TABLES privilege.");
					}
					if($db_passed && false === mysqli_query($_db, "DROP TABLE IF EXISTS _installer_test_suite_tmp")) {
						$db_passed = false;
					}
					
					// Privs summary
					if(!$db_passed)
						$errors[] = sprintf("The database user must have the following privileges: CREATE, ALTER, DROP, SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES");
				}
				
				unset($discovered_engines);
				
			} else {
				$db_passed = false;
				$errors[] = "Database connection failed!  Please check your settings and try again.";
			}
			
			$tpl->assign('db_driver', $db_driver);
			$tpl->assign('db_engine', $db_engine);
			$tpl->assign('db_server', $db_server);
			$tpl->assign('db_name', $db_name);
			$tpl->assign('db_user', $db_user);
			$tpl->assign('db_pass', $db_pass);
			
			// If passed, write config file and continue
			if($db_passed) {
				$encoding = 'utf8';
				
				// Write database settings to framework.config.php
				$result = CerberusInstaller::saveFrameworkConfig($db_driver, $db_engine, $encoding, $db_server, $db_name, $db_user, $db_pass);
				
				// [JAS]: If we didn't save directly to the config file, user action required
				if(0 != strcasecmp($result,'config')) {
					$tpl->assign('result', $result);
					$tpl->assign('config_path', APP_PATH . "/framework.config.php");
					$tpl->assign('template', 'steps/step_config_file.tpl');
					
				} else { // skip the config writing step
					usleep(2500000);
					$tpl->assign('step', STEP_INIT_DB);
					$tpl->display('steps/redirect.tpl');
					exit;
				}
				
			} else { // If failed, re-enter
				$tpl->assign('failed', true);
				$tpl->assign('errors', $errors);
				$tpl->assign('template', 'steps/step_database.tpl');
			}
			
		} else {
			$tpl->assign('db_server', 'localhost');
			$tpl->assign('template', 'steps/step_database.tpl');
		}
		break;
		
	// [JAS]: If we didn't save directly to the config file, user action required
	case STEP_SAVE_CONFIG_FILE:
		@$db_driver = DevblocksPlatform::importGPC($_POST['db_driver'],'string');
		@$db_engine = DevblocksPlatform::importGPC($_POST['db_engine'],'string');
		@$db_server = DevblocksPlatform::importGPC($_POST['db_server'],'string');
		@$db_name = DevblocksPlatform::importGPC($_POST['db_name'],'string');
		@$db_user = DevblocksPlatform::importGPC($_POST['db_user'],'string');
		@$db_pass = DevblocksPlatform::importGPC($_POST['db_pass'],'string');
		@$result = DevblocksPlatform::importGPC($_POST['result'],'string');
		
		// Check to make sure our constants match our input
		if(
			0 == strcasecmp($db_engine,APP_DB_ENGINE) &&
			0 == strcasecmp($db_server,APP_DB_HOST) &&
			0 == strcasecmp($db_name,APP_DB_DATABASE) &&
			0 == strcasecmp($db_user,APP_DB_USER) &&
			0 == strcasecmp($db_pass,APP_DB_PASS)
		) { // we did it!
			$tpl->assign('step', STEP_INIT_DB);
			$tpl->display('steps/redirect.tpl');
			exit;
			
		} else { // oops!
			$tpl->assign('db_driver', $db_driver);
			$tpl->assign('db_engine', $db_engine);
			$tpl->assign('db_server', $db_server);
			$tpl->assign('db_name', $db_name);
			$tpl->assign('db_user', $db_user);
			$tpl->assign('db_pass', $db_pass);
			$tpl->assign('failed', true);
			$tpl->assign('result', $result);
			$tpl->assign('config_path', APP_PATH . "/framework.config.php");
			
			$tpl->assign('template', 'steps/step_config_file.tpl');
		}
		
		break;

	// Initialize the database
	case STEP_INIT_DB:
		if(false == ($db = DevblocksPlatform::services()->database()) || !$db || !method_exists($db, 'metaTables')) {
			$tpl->assign('error', "Can't connect to the database.");
			$tpl->assign('template', 'steps/step_init_db.tpl');
			break;
		}
		
		$tables = $db->metaTables();
		
		if(empty($tables)) { // install
			try {
				DevblocksPlatform::update();
				
			} catch(Exception $e) {
				$tpl->assign('error', $e->getMessage());
				$tpl->assign('template', 'steps/step_init_db.tpl');
				break;
			}
			
			// Read in plugin information from the filesystem to the database
			$plugins = DevblocksPlatform::readPlugins();
			
			// Tailor which plugins are enabled by default
			if(is_array($plugins))
			foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
				switch ($plugin->id) {
					case 'devblocks.core':
					case 'cerberusweb.core':
					case 'cerberusweb.crm':
					case 'cerberusweb.feedback':
					case 'cerberusweb.kb':
					case 'cerberusweb.restapi':
					case 'cerberusweb.support_center':
					case 'cerberusweb.timetracking':
					case 'cerb.bots.portal.widget':
					case 'cerb.project_boards':
					case 'cerb.webhooks':
						$plugin->setEnabled(true);
						break;
					
					default:
						$plugin->setEnabled(false);
						break;
				}
			}
			
			// Platform + App
			try {
				// Flush cache
				DevblocksPlatform::clearCache();
				
				CerberusApplication::update();
				
				// Set up the default cron jobs
				$crons = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
				if(is_array($crons))
				foreach($crons as $id => $cron) { /* @var $cron CerberusCronPageExtension */
					switch($id) {
						case 'cron.bot.scheduled_behavior':
							$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
							$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
							$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
							$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday'));
							break;
						case 'cron.heartbeat':
							$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
							$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
							$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
							$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday'));
							break;
						case 'cron.mailbox':
							$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
							$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
							$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
							$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Today'));
							break;
						case 'cron.maint':
							$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
							$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '24');
							$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'h');
							$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday'));
							break;
						case 'cron.parser':
							$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
							$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '1');
							$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
							$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Today'));
							break;
					}
				}
				// Reload plugin translations
				DAO_Translation::reloadPluginStrings();
				
				// Update the cached version to prevent /update
				$path = APP_STORAGE_PATH . '/version.php';
				$contents = sprintf('<?php define(\'APP_BUILD_CACHED\', %s);', APP_BUILD);
				file_put_contents($path, $contents);
				
				// [TODO] Verify the database
				
				// Success
				$tpl->assign('step', STEP_OUTGOING_MAIL);
				$tpl->display('steps/redirect.tpl');
				exit;
				
			} catch(Exception $e) {
				$tpl->assign('error', $e->getMessage());
				$tpl->assign('template', 'steps/step_init_db.tpl');
				break;
			}
			
		} else { // upgrade / patch
			/*
			 * [TODO] We should probably only forward to upgrade when we know
			 * the proper tables were installed.  We may be repeating an install
			 * request where the clean DB failed.
			 */
			$tpl->assign('step', STEP_UPGRADE);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
			
		break;
		
	// Set up and test the outgoing SMTP
	case STEP_OUTGOING_MAIL:
		$settings = DevblocksPlatform::services()->pluginSettings();
		
		@$default_reply_from = DevblocksPlatform::importGPC($_POST['default_reply_from'],'string','noreply@cerb.example');
		@$default_reply_personal = DevblocksPlatform::importGPC($_POST['default_reply_personal'],'string','');
		
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string');
		@$smtp_host = DevblocksPlatform::importGPC($_POST['smtp_host'],'string');
		@$smtp_port = DevblocksPlatform::importGPC($_POST['smtp_port'],'integer');
		@$smtp_enc = DevblocksPlatform::importGPC($_POST['smtp_enc'],'string');
		@$smtp_auth_user = DevblocksPlatform::importGPC($_POST['smtp_auth_user'],'string');
		@$smtp_auth_pass = DevblocksPlatform::importGPC($_POST['smtp_auth_pass'],'string');
		
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$passed = DevblocksPlatform::importGPC($_POST['passed'],'integer');
		
		if(!empty($form_submit)) {
			if(!$default_reply_from)
				throw new Exception_CerbInstaller("The default sender is required.");
				
			$validate = imap_rfc822_parse_adrlist(sprintf("<%s>", $default_reply_from),"localhost");
			
			if(!is_array($validate) || 1 != count($validate))
				throw new Exception_CerbInstaller("The default sender is invalid.");

			if(false == ($address = DAO_Address::lookupAddress($default_reply_from, true)))
				throw new Exception_CerbInstaller("The default sender is invalid.");
				
			DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::MAIL_DEFAULT_FROM_ID, $address->id);
			
			if(!empty($default_reply_personal)) {
				DevblocksPlatform::setPluginSetting('cerberusweb.core', 'mail_default_from_personal', $default_reply_personal);
			}
			
			// Test the given mail transport details
			try {
				if(false == ($mail_transport = Extension_MailTransport::get($extension_id)))
					throw new Exception_CerbInstaller("Invalid mail transport extension.");
				
				$error = null;
				
				if($mail_transport->id == CerbMailTransport_Smtp::ID) {
					$options = array(
						'host' => $smtp_host,
						'port' => $smtp_port,
						'auth_user' => $smtp_auth_user,
						'auth_pass' => $smtp_auth_pass,
						'enc' => $smtp_enc,
					);
				
					if(false == ($mail_transport->testConfig($options, $error)))
						throw new Exception_CerbInstaller($error);
					
					$fields = array(
						DAO_MailTransport::NAME => $smtp_host . ' SMTP',
						DAO_MailTransport::EXTENSION_ID => CerbMailTransport_Smtp::ID,
						DAO_MailTransport::PARAMS_JSON => json_encode($options),
					);
					$transport_id = DAO_MailTransport::create($fields);
					
				} elseif($mail_transport->id == CerbMailTransport_Null::ID) {
					// This is always valid
					$fields = array(
						DAO_MailTransport::NAME => 'Null Mailer',
						DAO_MailTransport::EXTENSION_ID => CerbMailTransport_Null::ID,
						DAO_MailTransport::PARAMS_JSON => json_encode(array()),
					);
					$transport_id = DAO_MailTransport::create($fields);
					
				}
				
				if($transport_id) {
					$mail_default_from_id = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_DEFAULT_FROM_ID, 0);
					
					// Set it as the transport on the default reply-to
					if($mail_default_from_id && false != ($from_addy = DAO_Address::get($mail_default_from_id))) {
						DAO_Address::update($from_addy->id, [
							DAO_Address::MAIL_TRANSPORT_ID => $transport_id,
						]);
					}
				}
				
				// If we made it this far then we succeeded
				
				$tpl->assign('step', STEP_DEFAULTS);
				$tpl->display('steps/redirect.tpl');
				exit;
				
			} catch (Exception_CerbInstaller $e) {
				$error = $e->getMessage();
				
				$tpl->assign('default_reply_from', $default_reply_from);
				$tpl->assign('default_reply_personal', $default_reply_personal);
				
				$tpl->assign('extension_id', $extension_id);
				$tpl->assign('smtp_host', $smtp_host);
				$tpl->assign('smtp_port', $smtp_port);
				$tpl->assign('smtp_auth_user', $smtp_auth_user);
				$tpl->assign('smtp_auth_pass', $smtp_auth_pass);
				$tpl->assign('smtp_enc', $smtp_enc);
				$tpl->assign('form_submit', true);
				
				$tpl->assign('error_display', 'SMTP Connection Failed! ' . $error);
				$tpl->assign('template', 'steps/step_outgoing_mail.tpl');
			}
			
		} else {
			$tpl->assign('default_reply_from', $default_reply_from);
			$tpl->assign('default_reply_personal', $default_reply_personal);
			
			$tpl->assign('template', 'steps/step_outgoing_mail.tpl');
		}
		
		break;

	// Set up the default objects
	case STEP_DEFAULTS:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$org_name = DevblocksPlatform::importGPC($_POST['org_name'],'string');
		@$worker_email = DevblocksPlatform::importGPC($_POST['worker_email'],'string');
		@$worker_firstname = DevblocksPlatform::importGPC($_POST['worker_firstname'],'string');
		@$worker_lastname = DevblocksPlatform::importGPC($_POST['worker_lastname'],'string');
		@$worker_pass = DevblocksPlatform::importGPC($_POST['worker_pass'],'string');
		@$worker_pass2 = DevblocksPlatform::importGPC($_POST['worker_pass2'],'string');
		@$timezone = DevblocksPlatform::importGPC($_POST['timezone'],'string');

		$date = DevblocksPlatform::services()->date();
		
		$timezones = $date->getTimezones();
		$tpl->assign('timezones', $timezones);
		
		if(!empty($form_submit)) {
			// Persist form scope
			$tpl->assign('org_name', $org_name);
			$tpl->assign('worker_firstname', $worker_firstname);
			$tpl->assign('worker_lastname', $worker_lastname);
			$tpl->assign('worker_email', $worker_email);
			$tpl->assign('worker_pass', $worker_pass);
			$tpl->assign('worker_pass2', $worker_pass2);
			$tpl->assign('timezone', $timezone);
			
			// Sanity/Error checking
			if(!empty($worker_email) && !empty($worker_pass) && $worker_pass == $worker_pass2 && strlen($worker_pass) >= 8) {
				$encrypt = DevblocksPlatform::services()->encryption();
				
				// Set the configuration details in the session
				file_put_contents(APP_TEMP_PATH . '/setup.json', $encrypt->encrypt(json_encode([
					'admin_name_first' => $worker_firstname ?: 'Admin',
					'admin_name_last' => $worker_lastname ?: '',
					'admin_email' => $worker_email,
					'admin_password' => $worker_pass,
					'admin_timezone' => $timezone ?: 'America/Los_Angeles',
					'org_name' => $org_name ?: 'Example, Inc.',
				])));
				
				$tpl->assign('step', STEP_PACKAGES);
				$tpl->display('steps/redirect.tpl');
				exit;
				
			} else {
				$tpl->assign('failed', true);
				
			}
		}
		
		$tpl->assign('template', 'steps/step_defaults.tpl');
		
		break;
		
	case STEP_PACKAGES:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		
		if(!empty($form_submit)) {
			@$package = DevblocksPlatform::importGPC($_POST['package'],'string', '');
			@$optional_packages = DevblocksPlatform::importGPC($_POST['optional_packages'],'array', []);
			
			$encrypt = DevblocksPlatform::services()->encryption();
			@$setup_defaults = json_decode($encrypt->decrypt(file_get_contents(APP_TEMP_PATH . '/setup.json')), true) ?: [];
			
			$records_created = [];
			
			switch($package) {
				case 'demo':
					$json = file_get_contents(APP_PATH . '/install/packages/install_demo_package.json');
					$prompts = $setup_defaults;
					CerberusApplication::packages()->import($json, $prompts, $records_created);
					break;
					
				case 'standard':
					$json = file_get_contents(APP_PATH . '/install/packages/install_standard_package.json');
					$prompts = $setup_defaults;
					CerberusApplication::packages()->import($json, $prompts, $records_created);
					break;
			}
			
			if($optional_packages && is_array($optional_packages)) {
				foreach($optional_packages as $package) {
					switch($package) {
						case 'chat_bot':
							$prompts = [];
							$json = file_get_contents(APP_PATH . '/install/packages/chat_bot_package.json');
							$results = [];
							CerberusApplication::packages()->import($json, $prompts, $results);
							break;
							
						case 'customer_satisfaction':
							$prompts = [
								'product_name' => $setup_defaults['org_name'],
							];
							$json = file_get_contents(APP_PATH . '/install/packages/customer_satisfaction_package.json');
							$results = [];
							CerberusApplication::packages()->import($json, $prompts, $results);
							break;
							
						case 'reminder_bot':
							$prompts = [];
							$json = file_get_contents(APP_PATH . '/install/packages/reminder_bot_package.json');
							$results = [];
							CerberusApplication::packages()->import($json, $prompts, $results);
							break;
					}
				}
			}
			
			// Index initial content
			$search = DevblocksPlatform::getExtension('cron.search', true);
			$search->run();
			
			$tpl->assign('step', STEP_REGISTER);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
		
		$tpl->assign('template', 'steps/step_packages.tpl');
		break;
		
	case STEP_REGISTER:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$skip = DevblocksPlatform::importGPC($_POST['skip'],'integer',0);
		
		if(!empty($form_submit)) {
			$tpl->assign('step', STEP_FINISHED);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
		
		$tpl->assign('template', 'steps/step_register.tpl');
		break;
		
	case STEP_UPGRADE:
		$tpl->assign('template', 'steps/step_upgrade.tpl');
		break;
		
	case STEP_FINISHED:
		@unlink(APP_TEMP_PATH . '/setup.json');
		
		$tpl->assign('template', 'steps/step_finished.tpl');
		break;
}

$tpl->display('base.tpl');
