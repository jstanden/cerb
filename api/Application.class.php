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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */
define("APP_BUILD", 2022051801);
define("APP_VERSION", '10.2.5');

define("APP_MAIL_PATH", APP_STORAGE_PATH . '/mail/');

require_once(APP_PATH . "/api/Extension.class.php");

// App Scope ClassLoading
$path = APP_PATH . '/api/app/';

DevblocksPlatform::registerClasses($path . 'Mail.php', array(
	'CerberusMail',
	'Cerb_SwiftPlugin_GPGSigner',
	'Cerb_SwiftPlugin_TransportExceptionLogger',
));

DevblocksPlatform::registerClasses($path . 'Parser.php', array(
	'CerberusParser',
	'CerberusParserMessage',
	'CerberusParserModel',
	'ParserFile',
	'ParserFileBuffer',
));

DevblocksPlatform::registerClasses($path . 'Update.php', array(
	'ChUpdateController',
));

/**
 * Application-level Facade
 */
class CerberusApplication extends DevblocksApplication {
	private static $_active_worker = null;
	private static $_login_state = null;

	/**
	 * @return CerberusVisit
	 */
	static function getVisit() {
		$session = DevblocksPlatform::services()->session();
		return $session->getVisit();
	}

	static function setActiveWorker($worker) {
		self::$_active_worker = $worker;
	}

	/**
	 * @return Model_Worker
	 */
	static function getActiveWorker() {
		if(DevblocksPlatform::isStateless())
			return null;

		if(isset(self::$_active_worker))
			return self::$_active_worker;

		$visit = self::getVisit();
		return (null != $visit)
			? $visit->getWorker()
			: null
			;
	}
	
	static function getWorkersByAtMentionsText($text, $with_searches=true) {
		$workers = [];

		if(false !== ($at_mentions = DevblocksPlatform::parseAtMentionString($text))) {
			$workers = DAO_Worker::getByAtMentions($at_mentions, $with_searches);
		}

		return $workers;
	}

	static function getWorkerPickerData($population, $sample, $group_id=0, $bucket_id=0) {
		$cache = DevblocksPlatform::services()->cache();
		
		$cache_key_responsibilities = sprintf('watchers:responsibilities:%d', $group_id);
		$cache_key_workloads = 'watchers:workloads';
		
		// Shared objects

		$online_workers = DAO_Worker::getOnlineWithoutIdle();
		
		if(null === ($group_responsibilities = $cache->load($cache_key_responsibilities))) {
			$group_responsibilities = DAO_Group::getResponsibilities($group_id);
			$cache->save($group_responsibilities, $cache_key_responsibilities, [], 300);
		}
		
		$bucket_responsibilities = $group_responsibilities[$bucket_id] ?? [];
		
		if(null === ($workloads = $cache->load($cache_key_workloads))) {
			$workloads = DAO_Worker::getWorkloads();
			$cache->save($workloads, $cache_key_workloads, [], 300);
		}

		// Workers

		$picker_workers = array(
			'sample' => [],
			'population' => [],
		);

		// Bulk load population statistics
		foreach($population as $worker) { /* @var Model_Worker $worker */
			$worker->__is_selected = isset($sample[$worker->id]);
			$worker->__is_online = isset($online_workers[$worker->id]);
			$worker->__workload = isset($workloads[$worker->id]) ? $workloads[$worker->id] : [];
			$worker->__responsibility = isset($bucket_responsibilities[$worker->id]) ? $bucket_responsibilities[$worker->id] : 0;
		}

		// Sort population by score
		uasort($population, function($a, $b) {
			if($a->__responsibility == $b->__responsibility)
				return 0;

			return ($a->__responsibility < $b->__responsibility) ? 1 : -1;
		});

		// Set sample
		foreach($sample as &$worker) {
			if(!isset($population[$worker->id]))
				continue;

			$picker_workers['sample'][$worker->id] = $worker;
			unset($population[$worker->id]);
		}

		// Set remaining population
		foreach($population as &$worker) {
			$picker_workers['population'][$worker->id] = $worker;
		}

		// Return a result object
		return array(
			'show_responsibilities' => !empty($group_id),
			'workers' => $picker_workers,
		);
	}

	static function getAtMentionsWorkerDictionaryJson($with_searches=true) {
		$workers = DAO_Worker::getAllActive();
		$list = [];
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
			$searches = DAO_ContextSavedSearch::getUsableByActor($active_worker, CerberusContexts::CONTEXT_WORKER);

			foreach($searches as $search) {
				if(empty($search->tag))
					continue;

				$list[DevblocksPlatform::strLower($search->tag)] = array(
					'id' => $search->id,
					'name' => DevblocksPlatform::strEscapeHtml($search->name),
					'email' => DevblocksPlatform::strEscapeHtml(null),
					'title' => DevblocksPlatform::strEscapeHtml(null),
					'at_mention' => DevblocksPlatform::strEscapeHtml($search->tag),
					'_index' => DevblocksPlatform::strEscapeHtml($search->name . ' ' . $search->tag),
				);
			}
		}
		
		foreach($workers as $worker) {
			if(empty($worker->at_mention_name))
				continue;
			
			$list[DevblocksPlatform::strLower($worker->at_mention_name)] = array(
				'id' => $worker->id,
				'name' => DevblocksPlatform::strEscapeHtml($worker->getName()),
				'email_id' => $worker->email_id,
				'title' => DevblocksPlatform::strEscapeHtml($worker->title),
				'at_mention' => DevblocksPlatform::strEscapeHtml($worker->at_mention_name),
				'_index' => DevblocksPlatform::strEscapeHtml($worker->getName() . ' ' . $worker->at_mention_name),
			);
		}

		return json_encode(array_values($list));
	}

	/**
	 *
	 * @param string $uri
	 * @return DevblocksExtensionManifest or NULL
	 */
	static function getPageManifestByUri($uri) {
		$pages = DevblocksPlatform::getExtensions('cerberusweb.page', false, false);
		foreach($pages as $manifest) { /* @var $manifest DevblocksExtensionManifest */
			if(0 == strcasecmp($uri ?? '', $manifest->params['uri'] ?? '')) {
				return $manifest;
			}
		}
		return NULL;
	}

	static function processRequest(DevblocksHttpRequest $request, $is_ajax=false) {
		/**
		 * Override the 'update' URI since we can't count on the database
		 * being populated from XML beforehand when /update loads it.
		 */
		if(!$is_ajax && isset($request->path[0]) && 0 == strcasecmp($request->path[0],'update')) {
			if(null != ($update_controller = new ChUpdateController(null)))
				$update_controller->handleRequest($request);

		} else {
			// Hand it off to the platform
			DevblocksPlatform::processRequest($request, $is_ajax);
		}
	}

	static function checkRequirements() {
		$errors = [];

		// Privileges

		// Make sure the temporary directories of Devblocks are writeable.
		if(!is_writeable(APP_TEMP_PATH)) {
			$errors[] = APP_TEMP_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!file_exists(APP_SMARTY_COMPILE_PATH)) {
			@mkdir(APP_SMARTY_COMPILE_PATH);
		}

		if(!file_exists(APP_PATH . "/vendor/")) {
			$errors[] = APP_PATH . "/vendor/" ." doesn't exist. Did you run `composer install` first?";
		}

		if(!is_writeable(APP_SMARTY_COMPILE_PATH)) {
			$errors[] = APP_SMARTY_COMPILE_PATH . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!file_exists(APP_TEMP_PATH . "/cache")) {
			@mkdir(APP_TEMP_PATH . "/cache");
		}

		if(!is_writeable(APP_TEMP_PATH . "/cache/")) {
			$errors[] = APP_TEMP_PATH . "/cache/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!is_writeable(APP_STORAGE_PATH)) {
			$errors[] = APP_STORAGE_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!is_writeable(APP_STORAGE_PATH . "/mail/new/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!is_writeable(APP_STORAGE_PATH . "/mail/fail/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		// Requirements

		// PHP Version
		if(version_compare(PHP_VERSION,"7.4") < 0) {
			$errors[] = sprintf("Cerb %s requires PHP 7.4 or later. Your server PHP version is %s",
				APP_VERSION,
				PHP_VERSION
			);
		}

		// Mailparse version
		if(
			version_compare(PHP_VERSION, '8.0.0') >= 0 
			&& version_compare(phpversion('mailparse'),"3.1.3") < 0
		) {
			$errors[] = sprintf("Cerb %s requires mailparse 3.1.3 or later with PHP 8.x. Your mailparse extension version is %s",
				APP_VERSION,
				phpversion('mailparse')
			);
		} else if (
			version_compare(phpversion('mailparse'), '3.0.3') < 0
		) {
			$errors[] = sprintf("Cerb %s requires mailparse 3.0.3 or later with PHP 7.4. Your mailparse extension version is %s",
				APP_VERSION,
				phpversion('mailparse')
			);
		}

		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if(!($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0)) {
			$errors[] = 'file_uploads is disabled in your php.ini file. Please enable it.';
		}

		// Memory Limit
		$memory_limit = ini_get("memory_limit");
		if(!($memory_limit == '' || $memory_limit == -1)) { // empty string means failure or not defined, assume no compiled memory limits
			$ini_memory_limit = DevblocksPlatform::parseBytesString($memory_limit);
			if($ini_memory_limit < 16777216) {
				$errors[] = 'memory_limit must be 16M or larger (32M recommended) in your php.ini file.  Please increase it.';
			}
		}

		// Extension: MySQLi
		if(!extension_loaded("mysqli")) {
			$errors[] = "The 'mysqli' PHP extension is required.  Please enable it.";
		}

		// Extension: MySQLnd
		if(!extension_loaded("mysqlnd")) {
			$errors[] = "The 'mysqlnd' PHP extension is required.  Please enable it.";
		}

		// Extension: Sessions
		if(!extension_loaded("session")) {
			$errors[] = "The 'Session' PHP extension is required.  Please enable it.";
		}

		// Extension: cURL
		if(!extension_loaded("curl")) {
			$errors[] = "The 'cURL' PHP extension is required.  Please enable it.";
		}

		// Extension: PCRE
		if(!extension_loaded("pcre")) {
			$errors[] = "The 'PCRE' PHP extension is required.  Please enable it.";
		}

		// Extension: GD
		if(!extension_loaded("gd") && function_exists('imagettfbbox')) {
			$errors[] = "The 'GD' PHP extension (with FreeType library support) is required.  Please enable them.";
		}

		// Extension: MailParse
		if(!extension_loaded("mailparse")) {
			$errors[] = "The 'MailParse' PHP extension is required.  Please enable it.";
		}

		// Extension: mbstring
		if(!extension_loaded("mbstring")) {
			$errors[] = "The 'mbstring' PHP extension is required.  Please enable it.";
		}

		// Extension: XML
		if(!extension_loaded("xml")) {
			$errors[] = "The 'XML' PHP extension is required.  Please enable it.";
		}

		// Extension: SimpleXML
		if(!extension_loaded("simplexml")) {
			$errors[] = "The 'SimpleXML' PHP extension is required.  Please enable it.";
		}

		// Extension: DOM
		if(!extension_loaded("dom")) {
			$errors[] = "The 'DOM' PHP extension is required.  Please enable it.";
		}

		// Extension: SPL
		if(!extension_loaded("spl")) {
			$errors[] = "The 'SPL' PHP extension is required.  Please enable it.";
		}

		// Extension: ctype
		if(!extension_loaded("ctype")) {
			$errors[] = "The 'ctype' PHP extension is required.  Please enable it.";
		}

		// Extension: JSON
		if(!extension_loaded("json")) {
			$errors[] = "The 'JSON' PHP extension is required.  Please enable it.";
		}

		// Extension: OpenSSL
		if(!extension_loaded("openssl")) {
			$errors[] = "The 'openssl' PHP extension is required.  Please enable it.";
		}
		
		// Extension: YAML
		if(!extension_loaded("yaml")) {
			$errors[] = "The 'yaml' PHP extension is required.  Please enable it.";
		}

		return $errors;
	}
	
	public static function respondNotFound() {
		$tpl = DevblocksPlatform::services()->template();
		$settings = DevblocksPlatform::services()->pluginSettings();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('settings', $settings);
		$tpl->assign('session', $_SESSION ?? []);
		$tpl->assign('translate', $translate);
		
		if($active_worker) {
			$tpl->assign('pref_dark_mode', DAO_WorkerPref::get($active_worker->id, 'dark_mode', 0));
		}
		
		$message = $tpl->fetch('devblocks:cerberusweb.core::404_page.tpl');
		DevblocksPlatform::dieWithHttpErrorHtml($message, 404);
	}
	
	static function kataSchemas() : _CerbApplication_KataSchemas {
		return new _CerbApplication_KataSchemas();
	}
	
	static function kataAutocompletions() : _CerbApplication_KataAutocompletions {
		return new _CerbApplication_KataAutocompletions();
	}
	
	static function packages() {
		return new _CerbApplication_Packages();
	}
	
	static function update() {
		// Update the platform
		if(!DevblocksPlatform::update())
			throw new Exception("Couldn't update Devblocks.");

		// Read in plugin information from the filesystem to the database
		DevblocksPlatform::readPlugins();

		// Clean up missing plugins
		DAO_Platform::cleanupPluginTables();
		DAO_Platform::maint();

		// Registry
		$plugins = DevblocksPlatform::getPluginRegistry();

		// Update the application core (version by version)
		if(!isset($plugins['cerberusweb.core']))
			throw new Exception("Couldn't read application manifest.");

		$plugin_patches = [];

		// Load patches
		foreach($plugins as $p) { /* @var $p DevblocksPluginManifest */
			if('devblocks.core'==$p->id)
				continue;

			// Don't patch disabled plugins
			if($p->enabled) {
				// Ensure that the plugin requirements match, or disable
				if(!$p->checkRequirements()) {
					$p->setEnabled(false);
					continue;
				}

				$plugin_patches[$p->id] = $p->getPatches();
			}
		}

		$core_patches = $plugin_patches['cerberusweb.core'];
		unset($plugin_patches['cerberusweb.core']);

		/*
		 * For each core release, patch plugins in dependency order
		 */
		foreach($core_patches as $patch) { /* @var $patch DevblocksPatch */
			if(!file_exists($patch->getFilename()))
				throw new Exception("Missing application patch: ".$patch->getFilename());

			$version = $patch->getVersion();

			if(!$patch->run())
				throw new Exception("Application patch failed to apply: ".$patch->getFilename());

			// Patch this version and then patch plugins up to this version
			foreach($plugin_patches as $plugin_id => $patches) {
				$pass = true;
				foreach($patches as $k => $plugin_patch) {
					// Recursive patch up to _version_
					if($pass && version_compare($plugin_patch->getVersion(), $version, "<=")) {
						if($plugin_patch->run()) {
							unset($plugin_patches[$plugin_id][$k]);
						} else {
							$plugins[$plugin_id]->setEnabled(false);
							$pass = false;
						}
					}
				}
			}
		}

		return TRUE;
	}
	
	static function sendEmailTemplate($email, $template_id, $values) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$sender_addresses = DAO_Address::getLocalAddresses();
		
		$templates = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_AUTOMATED_TEMPLATES, '', true);
		$default_templates = json_decode(CerberusSettingsDefaults::MAIL_AUTOMATED_TEMPLATES, true);
		
		if(!isset($templates[$template_id]) && !isset($default_templates[$template_id]))
			return false;
		
		$default_template = $default_templates[$template_id] ?? [];
		
		if(false == ($template = $templates[$template_id] ?? null))
			$template = $default_template;

		$send_from_id = ($template['send_from_id'] ?? null) ?: ($default_template['send_from_id'] ?? null);
		$send_as = ($template['send_as'] ?? null) ?: ($default_template['send_as'] ?? null);
		$subject = ($template['subject'] ?? null) ?: ($default_template['subject'] ?? null);
		$body = ($template['body'] ?? null) ?: ($default_template['body'] ?? null);
		
		if(!$send_from_id || false == (@$send_from = $sender_addresses[$send_from_id]))
			$send_from = DAO_Address::getDefaultLocalAddress();
		
		$send_as = $tpl_builder->build($send_as, $values);
		$subject = $tpl_builder->build($subject, $values);
		$body = $tpl_builder->build($body, $values);
		
		if(empty($subject) || empty($body))
			return false;
		
		CerberusMail::quickSend($email, $subject, $body, $send_from->email, $send_as);
		
		return true;
	}

	/**
	 *
	 * @param integer $length
	 * @return string
	 * @test CerberusApplicationTest
	 */
	static function generatePassword($length=8, $chars='ABCDEFGHJKLMNPQRSTUVWXYZ123456789') {
		$len = strlen($chars)-1;
		$password = '';

		for($x=0;$x<$length;$x++) {
			$chars = str_shuffle($chars);
			$password .= substr($chars,mt_rand(0,$len),1);
		}

		return $password;
	}

	/**
	 * @return string a unique ticket mask
	 */
	static function generateTicketMask($pattern = null) {
		$pattern = trim(strval($pattern));

		if(empty($pattern))
			$pattern = trim(strval(DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::TICKET_MASK_FORMAT)));
		if(empty($pattern))
			$pattern = CerberusSettingsDefaults::TICKET_MASK_FORMAT;

		$letters = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$numbers = "123456789";

		do {
			$mask = "";
			$bytes = str_split($pattern, 1);
			$literal = false;

			if(is_array($bytes))
			foreach($bytes as $byte) {
				$append = '';

				switch(DevblocksPlatform::strUpper($byte)) {
					case '{':
						$literal = true;
						$byte = '';
						break;
					case '}':
						$literal = false;
						$append = '';
						break;
					case 'L':
						$append .= substr($letters,mt_rand(0,strlen($letters)-1),1);
						break;
					case 'N':
						$append .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);
						break;
					case 'C': // L or N
						if(mt_rand(0,100) >= 50) { // L
							$append .= substr($letters,mt_rand(0,strlen($letters)-1),1);
						} else { // N
							$append .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);
						}
						break;
					case 'Y':
						$append .= date('Y');
						break;
					case 'M':
						$append .= date('m');
						break;
					case 'D':
						$append .= date('d');
						break;
					default:
						$append .= $byte;
						break;
				}

				if($literal) {
					$mask .= $byte;
				} else {
					$mask .= $append;
				}

				$mask = DevblocksPlatform::strUpper(DevblocksPlatform::strAlphaNum($mask,'\-'));
			}
		} while(null != DAO_Ticket::getTicketIdByMask($mask));

		if(empty($mask)) {
			return self::generateTicketMask(CerberusSettingsDefaults::TICKET_MASK_FORMAT);
		}

		return $mask;
	}

	static function generateTicketMaskCardinality($pattern = null) {
		if(empty($pattern))
			$pattern = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::TICKET_MASK_FORMAT);
		if(empty($pattern))
			$pattern = CerberusSettingsDefaults::TICKET_MASK_FORMAT;

		$combinations = 1;
		$bytes = str_split($pattern, 1);
		$literal = false;

		if(is_array($bytes))
		foreach($bytes as $byte) {
			$mul = 1;
			switch(DevblocksPlatform::strUpper($byte)) {
				case '{':
					$literal = true;
					break;
				case '}':
					$literal = false;
					break;
				case 'L':
					$mul *= 25;
					break;
				case 'N':
					$mul *= 9;
					break;
				case 'C': // L or N
					$mul *= 34;
					break;
				case 'Y':
					$mul *= 1;
					break;
				case 'M':
					$mul *= 12;
					break;
				case 'D':
					$mul *= 30;
					break;
				default:
					break;
			}

			if(!$literal)
				$combinations = round($combinations*$mul,0);
		}

		return $combinations;
	}

	/**
	 * Generate an RFC-compliant Message-ID
	 *
	 * @return string
	 * @test CerberusApplicationTest
	 */
	static function generateMessageId() {
		$hostname = DevblocksPlatform::getHostname();

		$id_left = md5(getmypid().'.'.time().'.'.uniqid(mt_rand(), true));

		$message_id = sprintf('<%s@%s>', $id_left, $hostname);
		return $message_id;
	}

	/**
	 * Save form-uploaded files as Cerb attachments, with dupe detection.
	 *
	 * @param array $files
	 * @return array
	 */
	static function saveHttpUploadedFiles($files) {
		$file_ids = [];

		// Sanitize
		if(!isset($files['name']) || !isset($files['tmp_name']))
			return false;

		// Convert a single file upload into an array
		if(!is_array($files['name'])) {
			$files['name'] = array($files['name']);
			$files['type'] = array($files['type']);
			$files['tmp_name'] = array($files['tmp_name']);
			$files['error'] = array($files['error']);
			$files['size'] = array($files['size']);
		}

		if (is_array($files) && !empty($files)) {
			reset($files);
			foreach($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
					continue;

				// Dupe detection
				$sha1_hash = sha1_file($file) ?? null;

				if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $files['size'][$idx], $files['type'][$idx]))) {
					$fields = array(
						DAO_Attachment::NAME => $files['name'][$idx],
						DAO_Attachment::MIME_TYPE => $files['type'][$idx],
						DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
					);
					$file_id = DAO_Attachment::create($fields);

					// Content
					if(null !== ($fp = fopen($file, 'rb'))) {
						Storage_Attachments::put($file_id, $fp);
						fclose($fp);
					}
				}

				// Save results
				if($file_id)
					$file_ids[] = intval($file_id);

				@unlink($file);
			}
		}

		return $file_ids;
	}
};

class CerbException extends DevblocksException {
};

interface IContextToken {
	static function getValue($context, $context_values);
};

class CerberusContexts {
	private static $_is_caching_loads = false;
	private static $_cache_loads = [];

	private static $_default_actor_stack = [];
	private static $_default_actor_context = null;
	private static $_default_actor_context_id = null;

	private static $_stack = [];
	private static $_stack_max_empty_depth = 0;

	const CONTEXT_APPLICATION = 'cerberusweb.contexts.app';
	const CONTEXT_ACTIVITY_LOG = 'cerberusweb.contexts.activity_log';
	const CONTEXT_ADDRESS = 'cerberusweb.contexts.address';
	const CONTEXT_ATTACHMENT = 'cerberusweb.contexts.attachment';
	const CONTEXT_AUTOMATION = 'cerb.contexts.automation';
	const CONTEXT_AUTOMATION_EVENT = 'cerb.contexts.automation.event';
	const CONTEXT_AUTOMATION_RESOURCE = 'cerb.contexts.automation.resource';
	const CONTEXT_AUTOMATION_TIMER = 'cerb.contexts.automation.timer';
	const CONTEXT_BEHAVIOR = 'cerberusweb.contexts.behavior';
	const CONTEXT_BEHAVIOR_NODE = 'cerberusweb.contexts.behavior.node';
	const CONTEXT_BEHAVIOR_SCHEDULED = 'cerberusweb.contexts.behavior.scheduled';
	const CONTEXT_BOT = 'cerberusweb.contexts.bot';
	const CONTEXT_BUCKET = 'cerberusweb.contexts.bucket';
	const CONTEXT_CALENDAR = 'cerberusweb.contexts.calendar';
	const CONTEXT_CALENDAR_EVENT = 'cerberusweb.contexts.calendar_event';
	const CONTEXT_CALENDAR_EVENT_RECURRING = 'cerberusweb.contexts.calendar_event.recurring';
	const CONTEXT_CALL = 'cerberusweb.contexts.call';
	const CONTEXT_CARD_WIDGET = 'cerb.contexts.card.widget';
	const CONTEXT_CLASSIFIER = 'cerberusweb.contexts.classifier';
	const CONTEXT_CLASSIFIER_CLASS = 'cerberusweb.contexts.classifier.class';
	const CONTEXT_CLASSIFIER_ENTITY = 'cerberusweb.contexts.classifier.entity';
	const CONTEXT_CLASSIFIER_EXAMPLE = 'cerberusweb.contexts.classifier.example';
	const CONTEXT_COMMENT = 'cerberusweb.contexts.comment';
	const CONTEXT_CONNECTED_ACCOUNT = 'cerberusweb.contexts.connected_account';
	const CONTEXT_CONNECTED_SERVICE = 'cerberusweb.contexts.connected_service';
	const CONTEXT_CONTACT = 'cerberusweb.contexts.contact';
	const CONTEXT_CONTEXT_AVATAR = 'cerberusweb.contexts.context.avatar';
	const CONTEXT_CURRENCY = 'cerberusweb.contexts.currency';
	const CONTEXT_CUSTOM_FIELD = 'cerberusweb.contexts.custom_field';
	const CONTEXT_CUSTOM_FIELDSET = 'cerberusweb.contexts.custom_fieldset';
	const CONTEXT_CUSTOM_RECORD = 'cerberusweb.contexts.custom_record';
	const CONTEXT_DOMAIN = 'cerberusweb.contexts.datacenter.domain';
	const CONTEXT_DRAFT = 'cerberusweb.contexts.mail.draft';
	const CONTEXT_EMAIL_SIGNATURE = 'cerberusweb.contexts.email.signature';
	const CONTEXT_FEED = 'cerberusweb.contexts.feed';
	const CONTEXT_FEED_ITEM = 'cerberusweb.contexts.feed.item';
	const CONTEXT_FILE_BUNDLE = 'cerberusweb.contexts.file_bundle';
	const CONTEXT_GPG_PUBLIC_KEY = 'cerberusweb.contexts.gpg_public_key';
	const CONTEXT_GROUP = 'cerberusweb.contexts.group';
	const CONTEXT_KB_ARTICLE = 'cerberusweb.contexts.kb_article';
	const CONTEXT_KB_CATEGORY = 'cerberusweb.contexts.kb_category';
	const CONTEXT_MAIL_TRANSPORT = 'cerberusweb.contexts.mail.transport';
	const CONTEXT_MAILBOX = 'cerberusweb.contexts.mailbox';
	const CONTEXT_MAIL_HTML_TEMPLATE = 'cerberusweb.contexts.mail.html_template';
	const CONTEXT_MESSAGE = 'cerberusweb.contexts.message';
	const CONTEXT_METRIC = 'cerb.contexts.metric';
	const CONTEXT_NOTIFICATION= 'cerberusweb.contexts.notification';
	const CONTEXT_OPPORTUNITY = 'cerberusweb.contexts.opportunity';
	const CONTEXT_ORG = 'cerberusweb.contexts.org';
	const CONTEXT_PACKAGE = 'cerberusweb.contexts.package.library';
	const CONTEXT_PORTAL = 'cerberusweb.contexts.portal';
	const CONTEXT_PROFILE_TAB = 'cerberusweb.contexts.profile.tab';
	const CONTEXT_PROFILE_WIDGET = 'cerberusweb.contexts.profile.widget';
	const CONTEXT_QUEUE = 'cerb.contexts.queue';
	const CONTEXT_REMINDER = 'cerberusweb.contexts.reminder';
	const CONTEXT_RESOURCE = 'cerb.contexts.resource';
	const CONTEXT_ROLE = 'cerberusweb.contexts.role';
	const CONTEXT_SAVED_SEARCH = 'cerberusweb.contexts.context.saved.search';
	const CONTEXT_SERVER = 'cerberusweb.contexts.datacenter.server';
	const CONTEXT_SNIPPET = 'cerberusweb.contexts.snippet';
	const CONTEXT_TASK = 'cerberusweb.contexts.task';
	const CONTEXT_TICKET = 'cerberusweb.contexts.ticket';
	const CONTEXT_TIMETRACKING = 'cerberusweb.contexts.timetracking';
	const CONTEXT_TIMETRACKING_ACTIVITY = 'cerberusweb.contexts.timetracking.activity';
	const CONTEXT_TOOLBAR = 'cerb.contexts.toolbar';
	const CONTEXT_WEBAPI_CREDENTIAL = 'cerberusweb.contexts.webapi.credential';
	const CONTEXT_WEBHOOK_LISTENER = 'cerberusweb.contexts.webhook_listener';
	const CONTEXT_WORKER = 'cerberusweb.contexts.worker';
	const CONTEXT_WORKSPACE_PAGE = 'cerberusweb.contexts.workspace.page';
	const CONTEXT_WORKSPACE_TAB = 'cerberusweb.contexts.workspace.tab';
	const CONTEXT_WORKSPACE_WIDGET = 'cerberusweb.contexts.workspace.widget';
	const CONTEXT_WORKSPACE_WORKLIST = 'cerberusweb.contexts.workspace.list';

	public static function setCacheLoads($state) {
		$was_caching_loads = self::$_is_caching_loads;
		self::$_is_caching_loads = ($state ? true : false);

		// Clear the cache when disabled
		if(!self::$_is_caching_loads) {
			self::$_cache_loads = [];
		}
		
		return $was_caching_loads;
	}

	public static function getStack() {
		return self::$_stack;
	}

	public static function pushStack($context) {
		self::$_stack[] = $context;
		return self::$_stack;
	}

	public static function popStack() {
		return array_pop(self::$_stack);
	}
	
	public static function setStackMaxEmptyDepth($n) {
		$n = DevblocksPlatform::intClamp($n, 0, 15);
		
		$was = self::$_stack_max_empty_depth;
		
		self::$_stack_max_empty_depth = $n;
		
		return $was;
	}
	
	public static function getStackMaxEmptyDepth() {
		return self::$_stack_max_empty_depth;
	}

	public static function getContext($context, $context_object, &$labels, &$values, $prefix=null, $nested=false, $skip_labels=false) {
		// Push the stack
		self::$_stack[] = $context;
		
		if(false != ($ctx = Extension_DevblocksContext::getByAlias($context, true))) {
			// If blank, check the cache for a prebuilt context object
			if(is_null($context_object)) {
				$stack_max_empty_depth = CerberusContexts::getStackMaxEmptyDepth();
				
				if($stack_max_empty_depth && count(self::$_stack) > $stack_max_empty_depth) {
					$labels = [];
					$values = ['_context' => $context];
					
				} else {
					$cache = DevblocksPlatform::services()->cache();
					
					$stack = CerberusContexts::getStack();
					array_pop($stack);
					
					$parent = end($stack);
					
					$hash_parent = [
						CerberusContexts::CONTEXT_ADDRESS => [CerberusContexts::CONTEXT_CONTACT, CerberusContexts::CONTEXT_ORG, CerberusContexts::CONTEXT_MAIL_TRANSPORT, CerberusContexts::CONTEXT_WORKER],
						CerberusContexts::CONTEXT_CONTACT => [CerberusContexts::CONTEXT_ADDRESS, CerberusContexts::CONTEXT_ORG],
						CerberusContexts::CONTEXT_MESSAGE => [CerberusContexts::CONTEXT_TICKET],
						CerberusContexts::CONTEXT_ORG => [CerberusContexts::CONTEXT_ADDRESS],
					];
					
					if(!array_key_exists($context, $hash_parent) || !in_array($parent, $hash_parent[$context]))
						$parent = null;
					
					// Hash with the parent we're loading from
					$hash = sha1($context.':'.$parent.':'.json_encode($prefix));
					$cache_key = sprintf("cerb:ctx:%s", $hash);
					$cache_local = true;
					
					$labels = $values = [];
	
					// Cache hit
					if(null !== ($data = $cache->load($cache_key, false, $cache_local))) {
						$labels = $data['labels'];
						$values = $data['values'];
						unset($data);
						
					// Cache miss
					} else {
						$ctx->getContext(null, $labels, $values, $prefix);
						$cache->save(array('labels' => $labels, 'values' => $values), $cache_key, [], 0, $cache_local);
					}
				}
				
			} else {
				// If instance caching is enabled
				if(self::$_is_caching_loads) {
					$hash_context_id = $context_object;
					
					// Hash uniformly (if we have a model, hash as its ID, so an ID only request uses cache)
					if(is_object($context_object) && isset($context_object->id)) {
						$hash_context_id = $context_object->id;
					}
					
					if(is_numeric($hash_context_id))
						$hash_context_id = intval($hash_context_id);
					
					$hash = sha1($context . ':' . $hash_context_id . ':' . $prefix . ':' . ($nested ? 1 : 0));
					
					if(isset(self::$_cache_loads[$hash])) {
						$values = self::$_cache_loads[$hash];

					} else {
						$ctx->getContext($context_object, $labels, $values, $prefix);
						self::$_cache_loads[$hash] = $values;
					}
					
				} else {
					$ctx->getContext($context_object, $labels, $values, $prefix);
					
					if($skip_labels) {
						$labels = [];
						unset($values['_labels']);
						unset($values['_types']);
					}
				}
			}
		}

		if(!$nested) {
			$values['timestamp'] = time();

			// Current worker (Don't add to worker context)
			if($context != CerberusContexts::CONTEXT_WORKER) {
				$active_worker = CerberusApplication::getActiveWorker();
				$merge_token_labels = [];
				$merge_token_values = [];
				self::getContext(self::CONTEXT_WORKER, $active_worker, $merge_token_labels, $merge_token_values, '', true);

				CerberusContexts::merge(
					'worker_',
					'Current:Worker:',
					$merge_token_labels,
					$merge_token_values,
					$labels,
					$values
				);
			}

			// Plugin-provided tokens
			$token_extension_mfts = DevblocksPlatform::getExtensions('cerberusweb.snippet.token', false);
			foreach($token_extension_mfts as $mft) { /* @var $mft DevblocksExtensionManifest */
				$token = $mft->params['token'] ?? null;
				$label = $mft->params['label'] ?? null;
				$contexts = $mft->params['contexts'][0] ?? null;

				if(empty($token) || empty($label) || !is_array($contexts))
					continue;

				if(!isset($contexts[$context]))
					continue;

				if(null != ($ext = $mft->createInstance()) && $ext instanceof IContextToken) {
					/* @var $ext IContextToken */
					$value = $ext->getValue($context, $values);

					if(!empty($value)) {
						$labels['plugin_'.$token] = '(Plugin) '.$label;
						$values['plugin_'.$token] = $value;
					}
				}
			}
		}
		
		// Rename labels
		// [TODO] Phase out $labels
		
		if($skip_labels) {
			unset($values['_labels']);

		} else {
			if(is_array($labels)) {
				$finished = (1 == count(self::$_stack));

				array_walk($labels, function(&$label) use ($finished) {
					$label = strtr(trim($label), ':',' ');

					if($finished) {
						// Condense whitespace in labels
						$label = preg_replace('#\s{2,}#', ' ', $label);
						
						// Title
						$label = DevblocksPlatform::strUpperFirst($label, true);
					}
				});

				if($finished)
					asort($labels);

				$values['_labels'] = $labels;
			}
		}

		// Pop the stack
		array_pop(self::$_stack);
		
		return null;
	}

	public static function scrubTokensWithRegexp(&$labels, &$values, $patterns=[]) {
		foreach($patterns as $pattern) {
			foreach(array_keys($labels) as $token) {
				if(preg_match($pattern, $token)) {
					unset($labels[$token]);
				}
			}
			foreach(array_keys($values) as $token) {
				if(false !== ($pos = strpos($token,'|')))
					$token = substr($token,0,$pos);

				if(preg_match($pattern, $token)) {
					unset($values[$token]);
				}
			}
		}

		return TRUE;
	}

	/**
	 *
	 * @param string $token_prefix
	 * @param array $label_replace
	 * @param array $src_labels
	 * @param array $dst_labels
	 * @param array $src_values
	 * @param array $dst_values
	 * @return void
	 */
	public static function merge($token_prefix, $label_prefix, $src_labels, $src_values, &$dst_labels, &$dst_values) {
		if(is_array($src_labels))
		foreach($src_labels as $token => $label) {
			$dst_labels[$token_prefix.$token] = $label_prefix.$label;
		}

		if(is_array($src_values))
		foreach($src_values as $token => $value) {
			if(in_array($token, array('_labels', '_types'))) {

				switch($token) {
					case '_labels':
						if(!isset($dst_values['_labels']))
							$dst_values['_labels'] = [];

						foreach($value as $key => $label) {
							$dst_values['_labels'][$token_prefix.$key] = $label_prefix.$label;
						}
						break;

					case '_types':
						if(!isset($dst_values['_types']))
							$dst_values['_types'] = [];

						foreach($value as $key => $type) {
							$dst_values['_types'][$token_prefix.$key] = $type;
						}
						break;
				}

			} else {
				$dst_values[$token_prefix.$token] = $value;
			}
		}

		return true;
	}

	public static function isSameActor($a, $b) {
		if(false == ($a = CerberusContexts::polymorphActorToDictionary($a)))
			return false;

		if(false == ($b = CerberusContexts::polymorphActorToDictionary($b)))
			return false;

		return ($a->_context == $b->_context && $a->id == $b->id);
	}
	
	public static function isSameContext($a, $b) {
		if(false == ($a = Extension_DevblocksContext::getByAlias($a, false)))
			return false;

		if(false == ($b = Extension_DevblocksContext::getByAlias($b, false)))
			return false;

		return ($a->id == $b->id);
	}

	public static function allowEverything($models) {
		if(is_array($models)) {
			if(is_numeric(current($models)))
				$models = array_flip($models);

			return array_fill_keys(array_keys($models), true);

		} else {
			return true;
		}
	}

	public static function denyEverything($models) {
		if(is_array($models)) {
			if(is_numeric(current($models)))
				$models = array_flip($models);

			return array_fill_keys(array_keys($models), false);

		} else {
			return false;
		}
	}

	public static function isActorAnAdmin($actor) {
		// Polymorph
		if(!($actor instanceof DevblocksDictionaryDelegate))
			if(false == ($actor = self::polymorphActorToDictionary($actor)))
				return false;
		
		if(
			// If it's Cerb
			$actor->_context == CerberusContexts::CONTEXT_APPLICATION
			// Of if it's a role
			|| $actor->_context == CerberusContexts::CONTEXT_ROLE
			// Or if it's a superuser
			|| ($actor->_context == CerberusContexts::CONTEXT_WORKER && $actor->is_superuser)
		) {
			return true;
		}
		return false;
	}

	public static function isOwnableBy($owner_context, $owner_context_id, $actor) {
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return false;

		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return true;

		// Workers can own records, even though they can't write themselves
		if(
			$owner_context == CerberusContexts::CONTEXT_WORKER
			&& $actor->_context == CerberusContexts::CONTEXT_WORKER
			&& $actor->id == $owner_context_id
		) return true;
		
		// Workers can set roles when they're a role editor
		if($owner_context == CerberusContexts::CONTEXT_ROLE && $actor->_context == CerberusContexts::CONTEXT_WORKER) {
			$role_editors = DAO_WorkerRole::getEditableBy($actor->id);
			if(array_key_exists($owner_context_id, $role_editors))
				return true;
		}
		
		return self::isWriteableByActor($owner_context, $owner_context_id, $actor);
	}

	public static function polymorphModelsToDictionaries($models, $context) {
		// Normalize objects/primatives into an array
		if(!is_array($models)) {
			if($models instanceof DevblocksDictionaryDelegate) {
				$models = [$models->id => $models];
			} elseif(is_numeric($models)) {
				$models = [$models => $models];
			} else if(is_object($models) && DevblocksPlatform::strStartsWith(get_class($models), 'Model_')) {
				$models = [$models->id => $models];
			}
		}

		if(is_array($models) && current($models) instanceof DevblocksDictionaryDelegate) {
			return $models;

		} elseif(is_array($models) && is_numeric(current($models))) {
			$models = CerberusContexts::getModels($context, $models);
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context);
			return $dicts;

		} elseif(is_array($models) && is_object(current($models)) && DevblocksPlatform::strStartsWith(get_class(current($models)), 'Model_')) {
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context);
			return $dicts;
		}

		return null;
	}

	public static function polymorphActorToDictionary($actor, $undelegate_bots=true) {
		if(null == ($actor = self::_polymorphActorToDictionary($actor)))
			return null;

		// If the actor is a bot, delegate to its owner
		if($undelegate_bots && $actor->_context == CerberusContexts::CONTEXT_BOT) {
			$owner_dict = $actor->extract('owner_');

			if(false == ($actor = CerberusContexts::polymorphActorToDictionary($owner_dict, false)))
				return null;
		}

		return $actor;
	}

	public static function _polymorphActorToDictionary($actor) {
		if(is_array($actor)) {
			if(isset($actor['context']) && isset($actor['context_id'])) {
				$context = $actor['context'];
				$context_id = $actor['context_id'];

			} else if(2 == count($actor)) {
				list($context, $context_id) = array_pad(array_values($actor), 2, null);

			} else {
				return false;
			}
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
				return false;

			switch($context_ext->id) {
				case CerberusContexts::CONTEXT_APPLICATION:
				case CerberusContexts::CONTEXT_ADDRESS:
				case CerberusContexts::CONTEXT_CONTACT:
				case CerberusContexts::CONTEXT_GROUP:
				case CerberusContexts::CONTEXT_ORG:
				case CerberusContexts::CONTEXT_ROLE:
				case CerberusContexts::CONTEXT_BOT:
				case CerberusContexts::CONTEXT_WORKER:
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels([$context_id => $context_id], $context_ext->id);

					if(isset($dicts[$context_id])) {
						return $dicts[$context_id];
					} else {
						return false;
					}
					break;
			}

		} else if ($actor instanceof DevblocksDictionaryDelegate) {
			switch($actor->_context) {
				case CerberusContexts::CONTEXT_APPLICATION:
				case CerberusContexts::CONTEXT_ADDRESS:
				case CerberusContexts::CONTEXT_CONTACT:
				case CerberusContexts::CONTEXT_GROUP:
				case CerberusContexts::CONTEXT_ORG:
				case CerberusContexts::CONTEXT_ROLE:
				case CerberusContexts::CONTEXT_BOT:
				case CerberusContexts::CONTEXT_WORKER:
					return $actor;
					break;
			}

		} else if(is_object($actor)) {
			$context = null;

			switch(get_class($actor)) {
				case 'Model_Application':
					$context = CerberusContexts::CONTEXT_APPLICATION;
					break;

				case 'Model_Address':
					$context = CerberusContexts::CONTEXT_ADDRESS;
					break;

				case 'Model_Contact':
					$context = CerberusContexts::CONTEXT_CONTACT;
					break;

				case 'Model_Group':
					$context = CerberusContexts::CONTEXT_GROUP;
					break;

				case 'Model_ContactOrg':
					$context = CerberusContexts::CONTEXT_ORG;
					break;

				case 'Model_WorkerRole':
					$context = CerberusContexts::CONTEXT_ROLE;
					break;

				case 'Model_Bot':
					$context = CerberusContexts::CONTEXT_BOT;
					break;

				case 'Model_Worker':
					$context = CerberusContexts::CONTEXT_WORKER;
					break;
			}

			if($context) {
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels([$actor->id => $actor], $context);

				if(isset($dicts[$actor->id]))
					return $dicts[$actor->id];
			}

		}

		return null;
	}

	public static function isReadableByActor($context, $models, $actor) {
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return self::denyEverything($models);
		
		return $context_ext::isReadableByActor($models, $actor);
	}

	public static function isWriteableByActor($context, $models, $actor) {
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return self::denyEverything($models);

		return $context_ext::isWriteableByActor($models, $actor);
	}
	
	public static function isDeletableByActor($context, $models, $actor) {
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return self::denyEverything($models);
		
		if(method_exists(get_class($context_ext), 'isDeletableByActor')) {
			return $context_ext::isDeletableByActor($models, $actor);
		} else {
			// Default to deletable by write access
			return $context_ext::isWriteableByActor($models, $actor);
		}
	}

	public static function isReadableByDelegateOwner($actor, $context, $models, $owner_key_prefix='owner_', $ignore_admins=false, $allow_unassigned=false) {
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);

		// Admins can do whatever they want
		if(!$ignore_admins && CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);

		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, $context)))
			return CerberusContexts::denyEverything($models);

		$results = [];

		$owner_key_context = $owner_key_prefix . '_context';
		$owner_key_id = $owner_key_prefix . 'id';
		
		DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $owner_key_prefix);

		foreach($dicts as $id => $dict) {
			$is_readable = false;

			switch($dict->$owner_key_context) {
				case '':
					$is_readable = $allow_unassigned;
					break;
				
				// Everyone can read app-owned records
				case CerberusContexts::CONTEXT_APPLICATION:
					$is_readable = true;
					break;

				// Role members can read role-owned records
				case CerberusContexts::CONTEXT_ROLE:
					// Is the actor the same role?
					if($actor->_context == CerberusContexts::CONTEXT_ROLE && $actor->id == $dict->$owner_key_id) {
						$is_readable = true;
						break;
					}

					// Can we read role content?
					if($actor->_context == CerberusContexts::CONTEXT_WORKER) {
						$roles = DAO_WorkerRole::getReadableBy($actor->id);
						$is_readable = isset($roles[$dict->$owner_key_id]);
						break;
					}
					break;

				// Members can read group-owned records, or everyone if a public group
				case CerberusContexts::CONTEXT_GROUP:
					// Is the actor the same group?
					if($actor->_context == CerberusContexts::CONTEXT_GROUP && $actor->id == $dict->$owner_key_id) {
						$is_readable = true;
						break;
					}

					// Is it a public group?
					$owner_key_is_private = $owner_key_prefix . 'is_private';
					if(!$dict->$owner_key_is_private) {
						$is_readable = true;
						break;
					}

					// Are we a member of the group?
					$members = DAO_Group::getGroupMembers($dict->$owner_key_id);
					if($actor->_context == CerberusContexts::CONTEXT_WORKER && isset($members[$actor->id])) {
						$is_readable = true;
						break;
					}
					break;

				// Only the same worker can view a worker owned record
				case CerberusContexts::CONTEXT_WORKER:
					$is_readable = ($actor->_context == CerberusContexts::CONTEXT_WORKER && $actor->id == $dict->$owner_key_id);
					break;
			}

			$results[$id] = $is_readable;
		}

		if(is_array($models)) {
			return $results;

		} else {
			return array_shift($results);
		}
	}

	public static function isWriteableByDelegateOwner($actor, $context, $models, $owner_key_prefix='owner_', $ignore_admins=false, $allow_unassigned=false) {
		if(!($actor instanceof DevblocksDictionaryDelegate))
			if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
				return CerberusContexts::denyEverything($models);

		// Admins can do whatever they want
		if(!$ignore_admins && CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, $context)))
			return CerberusContexts::denyEverything($models);

		DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $owner_key_prefix);

		$results = [];

		$owner_key_context = $owner_key_prefix . '_context';
		$owner_key_id = $owner_key_prefix . 'id';

		foreach($dicts as $id => $dict) {
			$is_writeable = false;

			switch($dict->$owner_key_context) {
				case '':
					$is_writeable = $allow_unassigned;
					break;
				
				// Role owners can modify role-owned records
				case CerberusContexts::CONTEXT_ROLE:
					// Is the actor the same role?
					if($actor->_context == CerberusContexts::CONTEXT_ROLE && $actor->id == $dict->$owner_key_id) {
						$is_writeable = true;
						break;
					}
					
					// Are we an owner of the role?
					if($actor->_context == CerberusContexts::CONTEXT_WORKER) {
						$roles = DAO_WorkerRole::getEditableBy($actor->id);
						
						if(array_key_exists($dict->$owner_key_id, $roles)) {
							$is_writeable = true;
							break;
						}
					}
					break;
					
				// Managers can modify group-owned records
				case CerberusContexts::CONTEXT_GROUP:
					// Is the actor the same group?
					if($actor->_context == CerberusContexts::CONTEXT_GROUP && $actor->id == $dict->$owner_key_id) {
						$is_writeable = true;
						break;
					}

					// Are we a manager of the group?
					$members = DAO_Group::getGroupMembers($dict->$owner_key_id);
					if($actor->_context == CerberusContexts::CONTEXT_WORKER && isset($members[$actor->id]) && $members[$actor->id]->is_manager) {
						$is_writeable = true;
						break;
					}
					break;

				// Only the same worker can modify a worker owned record
				case CerberusContexts::CONTEXT_WORKER:
					$is_writeable = ($actor->_context == CerberusContexts::CONTEXT_WORKER && $actor->id == $dict->$owner_key_id);
					break;
			}

			$results[$id] = $is_writeable;
		}

		if(is_array($models)) {
			return $results;

		} else {
			return array_shift($results);
		}
	}

	static public function filterModelsByActorReadable($context_class, $models, $actor) {
		return array_intersect_key($models, array_flip(array_keys($context_class::isReadableByActor($models, $actor), true)));
	}

	static public function filterModelsByActorWriteable($context_class, $models, $actor) {
		return array_intersect_key($models, array_flip(array_keys($context_class::isWriteableByActor($models, $actor), true)));
	}

	// [TODO] This could also cache for request until new links are set involving the source/target
	static public function getWatchers($context, $context_id, $as_contexts=false) {
		$links = DAO_ContextLink::getContextLinks($context, $context_id, CerberusContexts::CONTEXT_WORKER);

		if(empty($links) || !isset($links[$context_id]))
			return [];

		$watcher_ids = array_keys($links[$context_id]);

		$workers = [];

		// Does the caller want the watchers as context objects?
		if($as_contexts) {
			if(is_array($watcher_ids))
			foreach($watcher_ids as $watcher_id) {
				$null_labels = [];
				$watcher_values = [];

				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $watcher_id, $null_labels, $watcher_values, null, true);

				$workers[$watcher_id] = new DevblocksDictionaryDelegate($watcher_values);
			}

		// Or as Model_* objects?
		} else {
			if(is_array($watcher_ids))
			foreach($watcher_ids as $watcher_id)
				$workers[$watcher_id] = DAO_Worker::get($watcher_id);
		}

		return $workers;
	}

	// [TODO] Are these the only methods that set watcher links?
	static public function addWatchers($context, $context_id, $worker_ids) {
		$workers = DAO_Worker::getAll();

		if(!is_array($worker_ids))
			$worker_ids = array($worker_ids);

		foreach($worker_ids as $worker_id) {
			if(null != ($worker = @$workers[$worker_id]) && $worker instanceof Model_Worker && !$worker->is_disabled) {
				DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_WORKER, $worker_id);
			}
		}
	}

	// [TODO] Are these the only methods that set watcher links?
	static public function removeWatchers($context, $context_id, $worker_ids) {
		if(!is_array($worker_ids))
			$worker_ids = array($worker_ids);

		foreach($worker_ids as $worker_id)
			DAO_ContextLink::deleteLink($context, $context_id, CerberusContexts::CONTEXT_WORKER, $worker_id);
	}

	static public function formatActivityLogEntry($entry, $format=null, $scrub_tokens=[], $personalize=false) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$url_writer = DevblocksPlatform::services()->url();
		$translate = DevblocksPlatform::getTranslationService();
		
		if(!is_array($entry)) {
			return '';
		}
		
		// Adjust things for certain messages
		
		if('activities.comment.create' == $entry['message']) {
			if(@$entry['urls']['target']) {
				if(false != ($target = CerberusContexts::parseContextUrl($entry['urls']['target']))) {
					if(CerberusContexts::CONTEXT_COMMENT == $target['context']) {
						if(@$entry['variables']['target']) {
							$entry['variables']['object'] = $entry['variables']['target'];
							$entry['variables']['target'] = null;
						} else {
							$parent_comment = DAO_Comment::get($target['id']);
							$entry['variables']['object'] = $parent_comment->getActorDictionary()->get('_label') . "'s comment";
							$entry['variables']['target'] = null;
						}
						
						$entry['urls']['object'] = $entry['urls']['target'];
					}
				}
			}
		}
		
		// Load the translated version of the message
		$entry['message'] = $translate->_($entry['message']);

		// Scrub desired tokens

		if(is_array($scrub_tokens) && !empty($scrub_tokens)) {
			foreach($scrub_tokens as $token) {
				// Scrub tokens and only preserve trailing whitespace
				$entry['message'] = preg_replace('#\s*\{\{'.$token.'\}\}(\s*)#', '\1', $entry['message']);
			}
		}

		// Variables

		$vars = $entry['variables'] ?? null;

		// Do we need to translate any token variables/urls?
		$matches = null;
		if(preg_match_all('#\{\{\'(.*?)\'.*?\}\}#', $entry['message'], $matches)) {
			foreach(array_keys($matches[0]) as $idx) {
				$new_key = uniqid('var_');
				$entry['message'] = str_replace($matches[$idx], sprintf('{{%s}}', $new_key), $entry['message']);

				if(isset($vars[$matches[1][$idx]])) {
					$vars[$new_key] = $vars[$matches[1][$idx]];
					unset($vars[$matches[1][$idx]]);
				} else {
					$vars[$new_key] = DevblocksPlatform::translate($matches[1][$idx]);
				}

				if(isset($entry['urls'][$matches[1][$idx]])) {
					$entry['urls'][$new_key] = $entry['urls'][$matches[1][$idx]];
					unset($entry['urls'][$matches[1][$idx]]);
				}
			}
		}

		// Personalize variables
		if($personalize && is_array($vars)) {
			if(isset($vars['actor'])) {
				$active_worker = CerberusApplication::getActiveWorker();
				$actor_name = $vars['actor'];
				$actor_self_target = 'themselves';

				// Replace actor with 'You'
				if($active_worker) {
					if($vars['actor'] == $active_worker->getName()) {
						$actor_name = 'You';
						$actor_self_target = 'yourself';
					}
				}

				// Handle the actor doing things to 'themselves'
				foreach($vars as $k => $v) {
					if($k == 'actor')
						continue;

					if($v == $vars['actor'])
						$vars[$k] = $actor_self_target;
					elseif($active_worker && $v == $active_worker->getName())
						$vars[$k] = 'you';
				}

				$vars['actor'] = $actor_name;
			}
		}

		switch($format) {
			case 'html':
				// HTML formatting and incorporating URLs
				if(is_array($vars))
				foreach($vars as $k => $v) {
					$vars[$k] = DevblocksPlatform::strEscapeHtml($v);
				}

				if(isset($entry['urls']))
				foreach($entry['urls'] as $token => $url) {
					if(0 == strcasecmp('ctx://',substr($url,0,6))) {
						$url = self::getUrlFromContextUrl($url);
						if(isset($vars[$token])) {
							$vars[$token] = '<a href="'.$url.'" class="subject">'.$vars[$token].'</a>';
						}
					} elseif(0 == strcasecmp('http',substr($url,0,4))) {
						if(isset($vars[$token])) {
							$vars[$token] = '<a href="'.$url.'" target="_blank" rel="noopener" class="subject">'.$vars[$token].'</a>';
						}
					} else {
						$url = $url_writer->writeNoProxy($url, true);
						if(isset($vars[$token])) {
							$vars[$token] = '<a href="'.$url.'" class="subject">'.$vars[$token].'</a>';
						}
					}
				}
				break;

			case 'html-cards':
				// HTML formatting and incorporating URLs
				if(is_array($vars))
				foreach($vars as $k => $v) {
					$vars[$k] = DevblocksPlatform::strEscapeHtml($v);
				}

				if(isset($entry['urls']))
				foreach($entry['urls'] as $token => $url) {
					if(0 == strcasecmp('ctx://',substr($url,0,6))) {
						if(false != ($parts = self::parseContextUrl($url))) {
							$vars[$token] = sprintf('<a href="javascript:;" class="cerb-peek-trigger subject" data-context="%s" data-context-id="%d" data-profile-url="%s">%s</a>',
								$parts['context'],
								$parts['id'],
								$parts['url'],
								$vars[$token]
							);
						}
					} elseif(0 == strcasecmp('http',substr($url,0,4))) {
						$vars[$token] = '<a href="'.$url.'" target="_blank" rel="noopener" class="subject">'.$vars[$token].'</a>';

					} else {
						$url = $url_writer->writeNoProxy($url, true);
						$vars[$token] = '<a href="'.$url.'" class="subject">'.$vars[$token].'</a>';
					}
				}
				break;

			case 'markdown':
				if(isset($entry['urls']))
				foreach($entry['urls'] as $token => $url) {
					if(0 == strcasecmp('ctx://',substr($url,0,6))) {
						$url = self::getUrlFromContextUrl($url);
						$vars[$token] = '['.$vars[$token].']('.$url.')';
					} elseif(0 == strcasecmp('http',substr($url,0,4))) {
						$vars[$token] = '['.$vars[$token].']('.$url.')';
					} else {
						$url = $url_writer->writeNoProxy($url, true);
						$vars[$token] = '['.$vars[$token].']('.$url.')';
					}
				}
				break;

			case 'email':
				@$url = reset($entry['urls']);

				if(empty($url))
					break;

				if(0 == strcasecmp('ctx://',substr($url,0,6))) {
					$url = self::getUrlFromContextUrl($url);
					$entry['message'] .= ' <' . $url . '>';
				} elseif(0 == strcasecmp('http',substr($url,0,4))) {
					$entry['message'] .= ' <' . $url . '>';
				} else {
					$url = $url_writer->writeNoProxy($url, true);
					$entry['message'] .= ' <' . $url . '>';
				}
				break;

			default:
				break;
		}

		if(!is_array($vars))
			$vars = [];

		return $tpl_builder->build($entry['message'], $vars);
	}

	static function parseContextUrl($url) {
		if(0 != strcasecmp('ctx://',substr($url,0,6))) {
			return false;
		}

		$context_parts = explode('/', substr($url,6));
		$context_pair = explode(':', $context_parts[0], 2);

		if(count($context_pair) != 2)
			return false;

		$context = $context_pair[0];
		$context_id = $context_pair[1];

		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return false;

		$context = $context_ext->id;
		
		switch($context) {
			case CerberusContexts::CONTEXT_TICKET:
				if(!is_numeric($context_id)) {
					$context_id = DAO_Ticket::getTicketIdByMask($context_id);
				}
				break;
		}

		$url = null;

		if($context_ext instanceof IDevblocksContextProfile) {
			$url = $context_ext->profileGetUrl($context_id);

		} else {
			$meta = $context_ext->getMeta($context_id);

			if(is_array($meta) && isset($meta['permalink']))
				$url = $meta['permalink'];
		}

		return [
			'context' => $context,
			'id' => $context_id,
			'url' => $url,
		];
	}

	static function getUrlFromContextUrl($url) {
		if(0 != strcasecmp('ctx://',substr($url,0,6))) {
			return false;
		}

		$context_parts = explode('/', substr($url,6));
		$context_pair = explode(':', $context_parts[0], 2);

		if(count($context_pair) != 2)
			return false;

		$context = $context_pair[0];
		$context_id = $context_pair[1];

		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return null;

		if($context_ext instanceof IDevblocksContextProfile) {
			$url = $context_ext->profileGetUrl($context_id);

		} else {
			$meta = $context_ext->getMeta($context_id);

			if(is_array($meta) && isset($meta['permalink']))
				$url = $meta['permalink'];
		}

		return $url;
	}

	static public function pushActivityDefaultActor($context=null, $context_id=null) {
		if(empty($context) || is_null($context_id)) {
			self::$_default_actor_context = null;
			self::$_default_actor_context_id = null;
		} else {
			self::$_default_actor_context = $context;
			self::$_default_actor_context_id = $context_id;
			self::$_default_actor_stack[] = array($context, $context_id);
		}
	}

	static public function popActivityDefaultActor() {
		array_pop(self::$_default_actor_stack);

		self::$_default_actor_context = null;
		self::$_default_actor_context_id = null;

		if(!empty(self::$_default_actor_stack)) {
			$context_pair = end(self::$_default_actor_stack);

			if(is_array($context_pair) && 2 == count($context_pair)) {
				self::$_default_actor_context = $context_pair[0];
				self::$_default_actor_context_id = $context_pair[1];
			}
		}
	}

	static public function getCurrentActor($actor_context=null, $actor_context_id=null) {
		// Forced actor
		if(!empty($actor_context)) {
			if(null != ($ctx = Extension_DevblocksContext::get($actor_context))
				&& $ctx instanceof Extension_DevblocksContext) {
				$meta = $ctx->getMeta($actor_context_id);
				$actor_name = $meta['name'];
				$actor_url = sprintf("ctx://%s:%d", $actor_context, $actor_context_id);

			} else {
				$actor_context = null;
				$actor_context_id = null;
			}
		}

		// Auto-detect the actor
		if(empty($actor_context)) {
			$actor_name = null;
			$actor_context = null;
			$actor_context_id = 0;
			$actor_url = null;

			// See if we're inside of a bot's running decision tree

			$stack = EventListener_Triggers::getTriggerStack();

			if(EventListener_Triggers::getDepth() > 0
				&& null != ($trigger_id = end($stack))
				&& !empty($trigger_id)
				&& null != ($trigger = DAO_TriggerEvent::get($trigger_id))
				&& false != ($trigger_va = $trigger->getBot())
			) {
				/* @var $trigger Model_TriggerEvent */

				$actor_name = sprintf("%s [%s]", $trigger_va->name, $trigger->title);
				$actor_context = CerberusContexts::CONTEXT_BOT;
				$actor_context_id = $trigger_va->id;
				$actor_url = sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_BOT, $trigger_va->id);

			// Otherwise see if we have an active session
			} else {
				// If we have a default, use it instead of the current session
				if(empty($actor_context) && !empty(self::$_default_actor_context)) {
					$actor_context = self::$_default_actor_context;
					$actor_context_id = self::$_default_actor_context_id;

					if(null != ($ctx = Extension_DevblocksContext::get($actor_context))
						&& $ctx instanceof Extension_DevblocksContext) {
						$meta = $ctx->getMeta($actor_context_id);
						$actor_name = $meta['name'] ?? 'Cerb';
						$actor_url = sprintf("ctx://%s:%d", $actor_context, $actor_context_id);
					}
				}

				// Try using current session
				if(empty($actor_context) && null != ($active_worker = CerberusApplication::getActiveWorker())) {
					$actor_name = $active_worker->getName();
					$actor_context = CerberusContexts::CONTEXT_WORKER;
					$actor_context_id = $active_worker->id;
					$actor_url = sprintf("ctx://%s:%d", $actor_context, $actor_context_id);
				}

			}
		}

		if(empty($actor_context)) {
			$actor_context = CerberusContexts::CONTEXT_APPLICATION;
			$actor_context_id = 0;
			$actor_name = 'Cerb';
			$actor_url = null;
		}

		return array(
			'context' => $actor_context,
			'context_id' => $actor_context_id,
			'name' => $actor_name ?? 'Cerb',
			'url' => $actor_url ?? null,
		);
	}

	static public function logActivity($activity_point, $target_context, $target_context_id, &$entry_array, $actor_context=null, $actor_context_id=null, $also_notify_worker_ids=[], $also_notify_ignore_self=false) {
		$activity_point = strval($activity_point);
		$target_context = strval($target_context);
		$target_context_id = intval($target_context_id);
		
		if(null != ($target_ctx = Extension_DevblocksContext::get($target_context))
			&& $target_ctx instanceof Extension_DevblocksContext) {
				$target_meta = $target_ctx->getMeta($target_context_id);
		}
		
		$actor = self::getCurrentActor($actor_context, $actor_context_id);
		
		$entry_array['variables']['actor'] = $actor['name'];
		
		if(isset($actor['url']) && !empty($actor['url']))
			$entry_array['urls']['actor'] = $actor['url'];
		
		// Activity Log
		$activity_entry_id = DAO_ContextActivityLog::create(array(
			DAO_ContextActivityLog::ACTIVITY_POINT => $activity_point,
			DAO_ContextActivityLog::CREATED => time(),
			DAO_ContextActivityLog::ACTOR_CONTEXT => $actor['context'],
			DAO_ContextActivityLog::ACTOR_CONTEXT_ID => $actor['context_id'],
			DAO_ContextActivityLog::TARGET_CONTEXT => $target_context,
			DAO_ContextActivityLog::TARGET_CONTEXT_ID => $target_context_id,
			DAO_ContextActivityLog::ENTRY_JSON => json_encode($entry_array),
		));

		// Tell target watchers about the activity

		$do_notifications = true;

		// Only fire notifications if supported by the activity options (!no_notifications)

		$activity_points = DevblocksPlatform::getActivityPointRegistry();

		if(isset($activity_points[$activity_point])) {
			$activity_mft = $activity_points[$activity_point];
			if(
				isset($activity_mft['params'])
				&& isset($activity_mft['params']['options'])
				&& in_array('no_notifications', DevblocksPlatform::parseCsvString($activity_mft['params']['options']))
				)
				$do_notifications = false;
		}

		// Send notifications

		if($do_notifications) {
			$watchers = [];

			// Merge in the record owner if defined
			if(isset($target_meta) && isset($target_meta['owner_id']) && !empty($target_meta['owner_id'])) {
				$watchers = array_merge(
					$watchers,
					array($target_meta['owner_id'])
				);
			}

			// Merge in watchers of the actor (if not a worker)
			if(CerberusContexts::CONTEXT_WORKER != $actor['context']) {
				$watchers = array_merge(
					$watchers,
					array_keys(CerberusContexts::getWatchers($actor['context'], $actor['context_id']))
				);
			}

			// And watchers of the target (if not a worker)
			if(CerberusContexts::CONTEXT_WORKER != $target_context) {
				$watchers = array_merge(
					$watchers,
					array_keys(CerberusContexts::getWatchers($target_context, $target_context_id))
				);
			}

			// Include the 'also notify' list
			if(!is_array($also_notify_worker_ids))
				$also_notify_worker_ids = [];

			// Merge watchers and the notification list
			$watchers = array_merge(
				$watchers,
				$also_notify_worker_ids
			);

			// And include any worker-based custom fields with the 'send watcher notifications' option
			$target_custom_fields = DAO_CustomField::getByContext($target_context, true);

			if(is_array($target_custom_fields))
			foreach($target_custom_fields as $target_custom_field_id => $target_custom_field) {
				if($target_custom_field->type != Model_CustomField::TYPE_WORKER)
					continue;

				if(!isset($target_custom_field->params['send_notifications']) || empty($target_custom_field->params['send_notifications']))
					continue;

				$values = DAO_CustomFieldValue::getValuesByContextIds($target_context, $target_context_id);

				if(isset($values[$target_context_id]) && isset($values[$target_context_id][$target_custom_field_id]))
					$watchers = array_merge(
						$watchers,
						array($values[$target_context_id][$target_custom_field_id])
					);
			}

			// Remove dupe watchers

			$watcher_ids = array_unique($watchers);

			// Fire off notifications

			if(is_array($watcher_ids)) {
				$workers = DAO_Worker::getAllActive();

				foreach($watcher_ids as $watcher_id) {
					// Skip inactive workers
					if(!isset($workers[$watcher_id]))
						continue;

					// If not inside a VA
					if(0 == EventListener_Triggers::getDepth()) {
						// Skip a watcher if they are the actor
						if($actor['context'] == CerberusContexts::CONTEXT_WORKER
							&& $actor['context_id'] == $watcher_id) {
								// If they explicitly added themselves to the notify, allow it.
								// Otherwise, don't tell them what they just did.
								if($also_notify_ignore_self || !in_array($watcher_id, $also_notify_worker_ids))
									continue;
						}
					}

					// Does the worker want this kind of notification?
					$dont_notify_on_activities = WorkerPrefs::getDontNotifyOnActivities($watcher_id);
					if(in_array($activity_point, $dont_notify_on_activities))
						continue;

					// If yes, send it
					DAO_Notification::create(array(
						DAO_Notification::CONTEXT => $target_context,
						DAO_Notification::CONTEXT_ID => $target_context_id,
						DAO_Notification::CREATED_DATE => time(),
						DAO_Notification::IS_READ => 0,
						DAO_Notification::WORKER_ID => $watcher_id,
						DAO_Notification::ACTIVITY_POINT => $activity_point,
						DAO_Notification::ENTRY_JSON => json_encode($entry_array),
					));
				}
			}
		} // end if($do_notifications)

		return $activity_entry_id;
	}

	static function getModels($context, array $ids) {
		$context = trim($context);
		$ids = DevblocksPlatform::importVar($ids, 'array:integer', []);

		$models = [];

		if(empty($ids))
			return $models;

		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return $models;

		if(false == ($dao_class = $context_ext->getDaoClass()))
			return $models;

		if(method_exists($dao_class, 'getIds')) {
			$models = $dao_class::getIds($ids);

		} elseif(method_exists($dao_class, 'getWhere')) {
			$models = $dao_class::getWhere(sprintf("id IN (%s)", implode(',', $ids)), null);
		}

		return $models;
	}

	static private array $_context_creations = [];
	
	static function checkpointCreations($context, $id) {
		if (php_sapi_name() == 'cli')
			return;
		
		if (!DevblocksPlatform::services()->event()->isEnabled())
			return;
		
		$id = DevblocksPlatform::importVar($id, 'integer');
		
		if(!array_key_exists($context, self::$_context_creations))
			self::$_context_creations[$context] = [];
		
		self::$_context_creations[$context][$id] = true;
	}
	
	static private array $_context_checkpoints = [];

	static function checkpointChanges($context, $ids) {
		if(php_sapi_name() == 'cli')
			return;
		
		if(!DevblocksPlatform::services()->event()->isEnabled())
			return;

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');
		$actor = CerberusContexts::getCurrentActor();

		if(!isset(self::$_context_checkpoints[$context]))
			self::$_context_checkpoints[$context] = [];

		// Cache full model objects the first time we encounter an ID (before persisting any changes)

		$load_ids = array_diff($ids, array_keys(self::$_context_checkpoints[$context]));

		if(!empty($load_ids)) {
			$models = CerberusContexts::getModels($context, $load_ids);
			$values = DAO_CustomFieldValue::getValuesByContextIds($context, $load_ids);

			foreach($models as $model_id => $model) {
				$custom_field_values = $values[$model_id] ?? [];
				
				// `custom_` keys
				$model->custom_fields = $custom_field_values;
				
				// Actor
				$model->_actor = $actor;

				self::$_context_checkpoints[$context][$model_id] =
					DevblocksPlatform::objectToArray($model);
			}
		}
	}
	
	/**
	 * @param string $context
	 * @param int $id
	 * @return bool
	 */
	private static function _wasJustCreated(string $context, int $id) : bool {
		if(!array_key_exists($context, self::$_context_creations))
			return false;
		
		return array_key_exists($id, self::$_context_creations[$context]);
	}

	static function getCheckpoints($context, $ids) {
		$models = [];

		if(isset(self::$_context_checkpoints[$context]))
		foreach($ids as $id) {
			if(isset(self::$_context_checkpoints[$context][$id]))
				$models[$id] = self::$_context_checkpoints[$context][$id];
		}

		return $models;
	}

	static function flush() {
		if(empty(self::$_context_checkpoints))
			return;

		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		$record_changed_events = DAO_AutomationEvent::getByName('record.changed');
		
		foreach(self::$_context_checkpoints as $context => &$old_models) {

			// Do this in batches of 100 in order to save memory
			$ids = array_keys($old_models);

			foreach(array_chunk($ids, 100) as $context_ids) {
				$new_models = CerberusContexts::getModels($context, $context_ids);

				$values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_ids);

				foreach($new_models as $context_id => $new_model) {
					$old_model = $old_models[$context_id];
					$new_model->custom_fields = ($values[$context_id] ?? null) ?: [];
					$actor = null;

					if(isset($old_model['_actor'])) {
						$actor = $old_model['_actor'];
						unset($old_model['_actor']);
					}
					
					// Trigger automations
					if($record_changed_events) {
						$dict_new = DevblocksDictionaryDelegate::getDictionaryFromModel($new_model, $context);
						$dict_old = DevblocksDictionaryDelegate::getDictionaryFromModel($old_model, $context);
						
						$dict = DevblocksDictionaryDelegate::instance([
							'is_new' => self::_wasJustCreated($context, $context_id),
							'actor__context' => $actor['context'],
							'actor_id' => $actor['context_id'],
						]);
						$dict->mergeKeys('record_', $dict_new->getDictionary('', false));
						$dict->mergeKeys('was_record_', $dict_old->getDictionary('', false));
						
						$initial_state = $dict->getDictionary();
						
						$error = null;
						
						$handlers = $record_changed_events->getKata($dict, $error);
						
						if(false === $handlers && $error) {
							error_log('[KATA] Invalid record.changed KATA: ' . $error);
							$handlers = [];
						}
						
						$event_handler->handleEach(
							AutomationTrigger_RecordChanged::ID,
							$handlers,
							$initial_state,
							$error,
							null,
							function(Model_TriggerEvent $behavior, array $handler) use ($context, $new_model, $old_model, $actor) {
								if($behavior->event_point == Event_RecordChanged::ID) {
									$event_model = new Model_DevblocksEvent(
										Event_RecordChanged::ID,
										[
											'context' => $context,
											'new_model' => $new_model,
											'old_model' => $old_model,
											'actor' => $actor,
										]
									);
									
								} else if($behavior->event_point == Event_TaskCreatedByWorker::ID && $new_model instanceof Model_Task) {
									$event_model = new Model_DevblocksEvent(
										Event_TaskCreatedByWorker::ID,
										[
											'context_id' => $new_model->id,
											'worker_id' => null,
										]
									);
									
								} else if($behavior->event_point == Event_CommentCreatedByWorker::ID && $new_model instanceof Model_Comment) {
									$event_model = new Model_DevblocksEvent(
										Event_CommentCreatedByWorker::ID,
										[
											'context_id' => $new_model->id,
										]
									);
									
								} else if($behavior->event_point == Event_CommentOnTicketInGroup::ID && $new_model instanceof Model_Comment) {
									$behavior_bot = $behavior->getBot();
									
									if(
										$behavior_bot->owner_context != CerberusContexts::CONTEXT_GROUP
										|| $behavior_bot->owner_context_id != ($new_model->getTargetDictionary()->get('group_id') ?? null)
										)
										return false;
									
									$event_model = new Model_DevblocksEvent(
										Event_CommentOnTicketInGroup::ID,
										[
											'context_id' => $new_model->context_id,
											'comment_id' => $new_model->id,
										]
									);
									
								} else {
									return false;
								}
								
								if($behavior->is_disabled || false == ($event = $behavior->getEvent()))
									return false;
								
								$event->setEvent($event_model, $behavior);
								
								$values = $event->getValues();
								
								// Inputs
								
								if(array_key_exists('inputs', $handler['data'])) {
									foreach($handler['data']['inputs'] as $k => $v) {
										if(DevblocksPlatform::strStartsWith($k, 'var_'))
											$values[$k] = $v;
									}
								}
								
								// Run behavior
								
								$dict = DevblocksDictionaryDelegate::instance($values);
								
								return $behavior->runDecisionTree($dict, false, $event);
							}
						);
					}
				}
			}
		}

		self::$_context_creations = [];
		self::$_context_checkpoints = [];
	}
	
	public static function logActivityRecordDelete($context, $record_ids, $record_labels=null) {
		// Polymorph
		if($context instanceof DevblocksExtensionManifest) {
			$context_mft = $context;
			
		} else if ($context instanceof Extension_DevblocksContext) {
			$context_mft = $context->manifest;
			
		} else if (is_string($context)) {
			if(false == ($context_mft = Extension_DevblocksContext::get($context, false)))
				return false; /** @var $context_mft DevblocksExtensionManifest */
			
		} else {
			return false;
		}
		
		if(is_numeric($record_ids) && !is_array($record_ids))
			$record_ids = [$record_ids];
		
		if(is_string($record_labels)) {
			$record_labels = [$record_labels];
		}
		
		// Lazy load the record labels, if needed
		if($record_ids && !$record_labels) {
			$models = CerberusContexts::getModels($context_mft->id, $record_ids);
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_mft->id);
			$record_labels = array_column($dicts, '_label', 'id');
		}
		
		if(count($record_ids) === count($record_labels))
		foreach(array_combine($record_ids, $record_labels) as $record_id => $record_label) {
			$entry = [
				// {{actor}} deleted {{record_type}} `{{record_label}}` (#{{record_id}})
				'message' => 'activities.record.delete',
				'variables' => [
					'record_type' => DevblocksPlatform::strLower($context_mft->name),
					'record_context' => $context_mft->id,
					'record_id' => $record_id,
					'record_label' => $record_label,
				],
				'urls' => [],
			];
			CerberusContexts::logActivity('record.deleted', null, null, $entry);
		}
	}
};

class DAO_Application extends Cerb_ORMHelper {
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
		
		return $validation->getFields();
	}

	static function getWhere($where=null, $sortBy='', $sortAsc=true, $limit=null) {
		return [
			0 => new Model_Application(),
		];
	}
}

class Model_Application {
	public $id = 0;
	public $name = 'Cerb';
}

class Context_Application extends Extension_DevblocksContext implements IDevblocksContextProfile {
	const ID = 'cerberusweb.contexts.app';
	const URI = 'app';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}

	static function isWriteableByActor($models, $actor) {
		// Only admin workers can modify

		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);

		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);

		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function profileGetUrl($context_id) {
		return null;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		/* @var $model Model_Application */
		
		if(is_null($model))
			$model = new Model_Application();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getRandom() {
		return 0;
	}

	function getMeta($context_id) {
		//$url_writer = DevblocksPlatform::services()->url();

		return array(
			'id' => 0,
			'name' => 'Cerb',
			'permalink' => null, //$url_writer->writeNoProxy('', true),
			'updated' => APP_BUILD,
		);
	}

	function getDefaultProperties() {
		return array(
			'name',
		);
	}

	function getContext($object, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Application:';

		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_APPLICATION);

		// Polymorph
		if(is_numeric($object)) {
			$object = new Model_Application();
			$object->name = 'Cerb';

		} elseif($object instanceof Model_Application) {
			// It's what we want already.

		} elseif(is_array($object)) {
			$object = Cerb_ORMHelper::recastArrayToModel($object, 'Model_Application');

		} else {
			$object = null;
		}

		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'name' => $prefix . DevblocksPlatform::translate('common.name'),
		);

		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
		);

		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		// Token values
		$token_values = [];

		$token_values['_context'] = Context_Application::ID;
		$token_values['_type'] = Context_Application::URI;
		$token_values['_types'] = $token_types;

		// Worker token values
		if(null != $object) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = 'Cerb';
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'app', 0), true) . '?v=' . APP_BUILD;
			$token_values['name'] = 'Cerb';
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'links' => '_links',
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		return parent::getKeyMeta($with_dao_fields);
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = CerberusContexts::CONTEXT_APPLICATION;
		$context_id = $dictionary['id'];

		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];

		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}

		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
				$values = array_merge($values, $defaults);
				break;
		}

		return $values;
	}

	function getChooserView($view_id=null) {
		return null;
	}

	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		return null;
	}
};

class CerberusLicense {
	private static ?CerberusLicense $instance = null;
	private $data = [];

	/**
	 * Commercial open source is the most constructive business model for our project.
	 * We trust you.
	 */
	private function __construct() {																																																																																			try{$l=(null!=($data=json_decode(DevblocksPlatform::getPluginSetting('cerberusweb.core',CerberusSettings::LICENSE,''),true)))?$data:[];self::validate(base64_decode($l[base64_decode('ZGF0YQ==')]??''),$l['company']??null,$l['email']??null)?$this->data=$l:$this->data=[];} catch(Exception $e) { $this->data=[];}if(1=='1')return;
		/*
		 * We've spent many years working on this software. Please be honest!
		 * https://cerb.ai/pricing
	   */	  																																																																																															$this->data = (null != ($data = json_decode(DevblocksPlatform::getPluginSetting('devblocks.core','license_info',[]),true)))?$data:[];}public function __get($property){switch($property){case 'w':return(max(1,intval($this->seats ?? null)));default:return(isset($this->data[$property]))?$this->data[$property]:null;}}
	/**
	 * @return CerberusLicense
	 */
	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new CerberusLicense();
		}
		return self::$instance;
	}

	/*
	 * Please purchase a legitimate license and help support the project.
	 * https://cerb.ai/pricing
	 */
		public static function validate($key, $company, $email) {																																																																						$matches=[];$w=$s=$e=$c=$u='';try{foreach(array('L0tleTogKC4qKS8='=>'s','L0NyZWF0ZWQ6ICguKikv'=>'c','L1VwZGF0ZWQ6ICguKikv'=>'u','L1VwZ3JhZGVzOiAoLiopLw=='=>'e','L1NlYXRzOiAoLiopLw=='=>'w') as $k=>$v)preg_match(base64_decode($k)?:'',$key,$matches)?$$v=trim($matches[1]??null):null;$r=[];$w=intval($w??'');$cp=base64_decode('Y29tcGFueQ==');$em=base64_decode('ZW1haWw=');$cd=preg_replace('/[^A-Z0-9]/','',$s);$l=explode('-',$e);$e=gmmktime(0,0,0,intval($l[1]??0),intval($l[2]??0),intval($l[0]??0));$l=explode('-',$c);$c=gmmktime(0,0,0,intval($l[1]??0),intval($l[2]??0),intval($l[0]??0));$l=explode('-',$u);$u=gmmktime(0,0,0,intval($l[1]??0),intval($l[2]??0),intval($l[0]??0));$h=str_split(DevblocksPlatform::strUpper(sha1(sha1('cerb5').sha1($$cp??'').sha1($$em??'').sha1($w??'').sha1(gmdate('Y-m-d',$c)).sha1(gmdate('Y-m-d',$e)))),1);if(0==strcasecmp(sprintf("%02X",strlen($$cp??'')+$w),substr($cd,3,2))&&@intval(hexdec(substr($cd,5,1))==@intval(bindec(sprintf("%d%d%d%d",(182<=gmdate('z',$e))?1:0,(5==gmdate('w',$e))?1:0,('th'==gmdate('S',$e))?1:0,(1==gmdate('w',$e))?1:0))))&&0==@strcasecmp($h[hexdec(substr($cd,1,2))-@hexdec(substr($cd,0,1))],substr($cd,0,1)))@$r=array(base64_decode('a2V5')=>$s,base64_decode('Y3JlYXRlZA==')=>$c,base64_decode('dXBkYXRlZA==')=>$u,base64_decode('dXBncmFkZXM=')=>$e,$cp=>$$cp??null,$em=>$$em??null,base64_decode('c2VhdHM=')=>$w,base64_decode('ZGF0YQ==')=>base64_encode($key));if(1=='1')return $r;}catch(Exception $e){return [];}
		/*
		 * Simple, huh?
		 */
		$lines = explode("\n", $key);

		/*
		 * Remember that our cache can return stale data here. Be sure to
		 * clear caches.  The config area does already.
		 */
		return (!empty($key) && !empty($lines))
			? array(
				'company' => $company,
				'email' => $email,
				'key' => (list(,$v)=explode(":",$lines[1]))?trim($v):null,
				'created' => (list(,$v)=explode(":",$lines[2]))?trim($v):null,
				'updated' => (list(,$v)=explode(":",$lines[3]))?trim($v):null,
				'upgrades' => (list(,$v)=explode(":",$lines[4]))?trim($v):null,
				'seats' => (list(,$v)=explode(":",$lines[5]))?trim($v):null
			)
			: [];
	}

	public static function getReleases() {
		/*																																																																																																	*/if(1>0) return json_decode(base64_decode('eyI1LjAuMCI6MTI3MTg5NDQwMCwiNS4xLjAiOjEyODE4MzA0MDAsIjUuMi4wIjoxMjg4NTY5NjAwLCI1LjMuMCI6MTI5NTA0OTYwMCwiNS40LjAiOjEzMDM4NjI0MDAsIjUuNS4wIjoxMzEyNDE2MDAwLCI1LjYuMCI6MTMxNzY4NjQwMCwiNS43LjAiOjEzMjYwNjcyMDAsIjYuMC4wIjoxMzM4MTYzMjAwLCI2LjEuMCI6MTM0NjAyNTYwMCwiNi4yLjAiOjEzNTM4ODgwMDAsIjYuMy4wIjoxMzY0MTY5NjAwLCI2LjQuMCI6MTM3MDIxNzYwMCwiNi41LjAiOjEzNzkyODk2MDAsIjYuNi4wIjoxMzkxMTI2NDAwLCI2LjcuMCI6MTM5ODEyNDgwMCwiNi44LjAiOjE0MTA3MzkyMDAsIjYuOS4wIjoxNDIyMjMwNDAwLCI3LjAuMCI6MTQzMjU5ODQwMCwiNy4xLjAiOjE0NDg5MjgwMDAsIjcuMi4wIjoxNDYyMDYwODAwLCI3LjMuMCI6MTQ3MjY4ODAwMCwiOC4wLjAiOjE0OTU3NTY4MDAsIjguMS4wIjoxNTAzOTY0ODAwLCI4LjIuMCI6MTUwOTMyMTYwMCwiOC4zLjAiOjE1MTk2MDMyMDAsIjkuMC4wIjoxNTMzNTEzNjAwLCI5LjEuMCI6MTU0NDgzMjAwMCwiOS4yLjAiOjE1NTEzMTIwMDAsIjkuMy4wIjoxNTU5MjYwODAwLCI5LjQuMCI6MTU2OTgwMTYwMCwiOS41LjAiOjE1Nzc3NTA0MDAsIjkuNi4wIjoxNTkwODgzMjAwLCIxMC4wLjAiOjE2MDE0MjQwMDAsIjEwLjEuMCI6MTYwOTM3MjgwMCwiMTAuMi4wIjoxNjE5NzQwODAwLCIxMC4zLjAiOjE2MzAzNjgwMDB9'),true);/*
		 * Major versions by release date (in GMT)
		 */
		return [
			'5.0.0' => gmmktime(0,0,0,4,22,2010),
			'5.1.0' => gmmktime(0,0,0,8,15,2010),
			'5.2.0' => gmmktime(0,0,0,11,1,2010),
			'5.3.0' => gmmktime(0,0,0,1,15,2011),
			'5.4.0' => gmmktime(0,0,0,4,27,2011),
			'5.5.0' => gmmktime(0,0,0,8,4,2011),
			'5.6.0' => gmmktime(0,0,0,10,4,2011),
			'5.7.0' => gmmktime(0,0,0,1,9,2012),
			'6.0.0' => gmmktime(0,0,0,5,28,2012),
			'6.1.0' => gmmktime(0,0,0,8,27,2012),
			'6.2.0' => gmmktime(0,0,0,11,26,2012),
			'6.3.0' => gmmktime(0,0,0,3,25,2013),
			'6.4.0' => gmmktime(0,0,0,6,3,2013),
			'6.5.0' => gmmktime(0,0,0,9,16,2013),
			'6.6.0' => gmmktime(0,0,0,1,31,2014),
			'6.7.0' => gmmktime(0,0,0,4,22,2014),
			'6.8.0' => gmmktime(0,0,0,9,15,2014),
			'6.9.0' => gmmktime(0,0,0,1,26,2015),
			'7.0.0' => gmmktime(0,0,0,5,26,2015),
			'7.1.0' => gmmktime(0,0,0,12,1,2015),
			'7.2.0' => gmmktime(0,0,0,5,1,2016),
			'7.3.0' => gmmktime(0,0,0,9,1,2016),
			'8.0.0' => gmmktime(0,0,0,5,26,2017),
			'8.1.0' => gmmktime(0,0,0,8,29,2017),
			'8.2.0' => gmmktime(0,0,0,10,30,2017),
			'8.3.0' => gmmktime(0,0,0,2,26,2018),
			'9.0.0' => gmmktime(0,0,0,8,6,2018),
			'9.1.0' => gmmktime(0,0,0,12,15,2018),
			'9.2.0' => gmmktime(0,0,0,2,28,2019),
			'9.3.0' => gmmktime(0,0,0,5,31,2019),
			'9.4.0' => gmmktime(0,0,0,9,30,2019),
			'9.5.0' => gmmktime(0,0,0,12,31,2019),
			'9.6.0' => gmmktime(0,0,0,5,31,2020),
			'10.0.0' => gmmktime(0,0,0,9,30,2020),
			'10.1.0' => gmmktime(0,0,0,12,31,2020),
			'10.2.0' => gmmktime(0,0,0,4,30,2021),
		];
	}

	public static function getReleaseDate($version) {
		$latest_licensed = 0;
		$version_parts = explode("-",$version,2);
		$version = array_shift($version_parts);
		foreach(self::getReleases() as $release => $release_date) {
			if(version_compare($release, $version) <= 0)
				$latest_licensed = $release_date;
		}
		return $latest_licensed;
	}
};

class CerberusSettings {
	const HELPDESK_TITLE = 'helpdesk_title';
	const HELPDESK_FAVICON_URL = 'helpdesk_favicon_url';
	const UI_USER_LOGO_UPDATED_AT = 'ui_user_logo_updated_at';
	const UI_USER_STYLESHEET = 'ui_user_stylesheet';
	const UI_USER_STYLESHEET_UPDATED_AT = 'ui_user_stylesheet_updated_at';
	const ATTACHMENTS_ENABLED = 'attachments_enabled';
	const ATTACHMENTS_MAX_SIZE = 'attachments_max_size';
	const PARSER_AUTO_REQ = 'parser_autoreq';
	const PARSER_AUTO_REQ_EXCLUDE = 'parser_autoreq_exclude';
	const TICKET_MASK_FORMAT = 'ticket_mask_format';
	const AUTHORIZED_IPS = 'authorized_ips';
	const LICENSE = 'license_json';
	const RELAY_DISABLE = 'relay_disable';
	const RELAY_DISABLE_AUTH = 'relay_disable_auth';
	const RELAY_SPOOF_FROM = 'relay_spoof_from';
	const SESSION_LIFESPAN = 'session_lifespan';
	const TIMEZONE = 'timezone';
	const TIME_FORMAT = 'time_format';
	const AVATAR_DEFAULT_STYLE_CONTACT = 'avatar_default_style_contact';
	const AVATAR_DEFAULT_STYLE_WORKER = 'avatar_default_style_worker';
	const HTML_NO_STRIP_MICROSOFT = 'html_no_strip_microsoft';
	const MAIL_DEFAULT_FROM_ID = 'mail_default_from_id';
	const MAIL_AUTOMATED_TEMPLATES = 'mail_automated_templates';
	const AUTH_MFA_ALLOW_REMEMBER = 'auth_mfa_allow_remember';
	const AUTH_MFA_REMEMBER_DAYS = 'auth_mfa_remember_days';
	const AUTH_SSO_SERVICE_IDS = 'auth_sso_service_ids';
	const AUTH_DEFAULT_WORKER_DISABLE_PASSWORD = 'auth_new_worker_disable_password';
	const AUTH_DEFAULT_WORKER_REQUIRE_MFA = 'auth_new_worker_require_mfa';
	const MAIL_HTML_IMAGE_PROXY_TIMEOUT_MS = 'mail_html_image_proxy_timeout_ms';
	const MAIL_HTML_IMAGE_PROXY_REDIRECTS_DISABLED = 'mail_html_image_proxy_redirects_disabled';
	const MAIL_HTML_IMAGE_PROXY_BLOCKLIST = 'mail_html_image_blocklist';
	const MAIL_HTML_IMAGE_SECRET = 'mail_html_image_secret';
	const MAIL_HTML_LINKS_WHITELIST = 'mail_html_links_whitelist';
};

class CerberusSettingsDefaults {
	const HELPDESK_TITLE = 'Cerb';
	const UI_USER_LOGO_UPDATED_AT = 0;
	const UI_USER_STYLESHEET = '';
	const UI_USER_STYLESHEET_UPDATED_AT = 0;
	const ATTACHMENTS_ENABLED = 1;
	const ATTACHMENTS_MAX_SIZE = 10;
	const PARSER_AUTO_REQ = 0;
	const PARSER_AUTO_REQ_EXCLUDE = '';
	const TICKET_MASK_FORMAT = 'LLL-NNNNN-NNN';
	const AUTHORIZED_IPS = "127.0.0.1\n::1\n";
	const RELAY_DISABLE = 0;
	const RELAY_DISABLE_AUTH = 0;
	const RELAY_SPOOF_FROM = 0;
	const SESSION_LIFESPAN = 0;
	const TIME_FORMAT = 'D, d M Y h:i a';
	const TIMEZONE = '';
	const AVATAR_DEFAULT_STYLE_CONTACT = 'monograms';
	const AVATAR_DEFAULT_STYLE_WORKER = 'monograms';
	const HTML_NO_STRIP_MICROSOFT = 0;
	const MAIL_DEFAULT_FROM_ID = 0;
	const MAIL_AUTOMATED_TEMPLATES = "{\"worker_invite\":{\"send_from_id\":\"0\",\"send_as\":\"Cerb\",\"subject\":\"Welcome to Cerb!\",\"body\":\"Welcome, {{worker_first_name}}!\\r\\n\\r\\nYour team has invited you to create a Cerb login at:\\r\\n{{url}}\\r\\n\"},\"worker_recover\":{\"send_from_id\":\"0\",\"send_as\":\"Cerb\",\"subject\":\"Your account recovery confirmation code\",\"body\":\"Hi, {{worker_first_name}}.\\r\\n\\r\\nWe recently received a request to reset your account's login information.\\r\\n\\r\\nHere's your account reset confirmation code: {{code}}\\r\\n\\r\\nIP: {{ip}}\\r\\n\\r\\nIf you didn't initiate this request, please forward this message to a system administrator.\"}}";
	const AUTH_DEFAULT_WORKER_DISABLE_PASSWORD = 0;
	const AUTH_DEFAULT_WORKER_REQUIRE_MFA = 0;
	const AUTH_MFA_ALLOW_REMEMBER = 0;
	const AUTH_MFA_REMEMBER_DAYS = 0;
	const AUTH_SSO_SERVICE_IDS = "";
};

class Cerb_DevblocksSessionHandler implements IDevblocksHandler_Session {
	static $_data = null;

	static function open($save_path, $session_name) {
		return true;
	}

	static function close() {
		return true;
	}

	static function isReady() {
		$tables = DevblocksPlatform::getDatabaseTables();

		if(!isset($tables['devblocks_session']))
			return false;

		return true;
	}

	static function read($id) {
		$db = DevblocksPlatform::services()->database();
		
		if(!self::isReady())
			return '';
		
		// [TODO] Allow Cerb to configure sticky IP sessions (or by subnet) as setting
		// [TODO] Allow Cerb to enable user-agent comparisons as setting
		// [TODO] Limit the IPs a worker can log in from (per-worker?)

		if(null != (@$session = $db->GetRowReader(sprintf("SELECT session_id, refreshed_at, session_data FROM devblocks_session WHERE session_token = %s", $db->qstr($id))))) {
			$maxlifetime = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::SESSION_LIFESPAN, CerberusSettingsDefaults::SESSION_LIFESPAN);
			//$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');

			// Refresh the session cookie (move expiration forward) after 2.5 minutes have elapsed
			if(array_key_exists('refreshed_at', $session) && (time() - $session['refreshed_at'] >= 150)) { // !$is_ajax
				// If the cookie is going to expire at a future date, extend it
				if($maxlifetime) {
					$url_writer = DevblocksPlatform::services()->url();
					setcookie('Devblocks', $id, time()+$maxlifetime, '/', '', $url_writer->isSSL(), true);
				}

				$db->ExecuteMaster(sprintf("UPDATE devblocks_session SET updated=%d, refreshed_at=%d WHERE session_token = %s",
					time(),
					time(),
					$db->qstr($id)
				), _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
			}

			self::$_data = $session['session_data'];
			return self::$_data;
		}

		return '';
	}

	static function write($id, $session_data) {
		// Nothing changed!
		if(self::$_data==$session_data) {
			return true;
		}

		$active_worker = CerberusApplication::getActiveWorker();
		$user_id = !is_null($active_worker) ? $active_worker->id : 0;
		$user_ip = DevblocksPlatform::getClientIp();
		$user_agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';

		$db = DevblocksPlatform::services()->database();
		
		if(!self::isReady())
			return true;

		// Update
		$sql = sprintf("UPDATE devblocks_session SET updated=%d, refreshed_at=%d, session_data=%s, user_id=%d, user_ip=%s, user_agent=%s WHERE session_token=%s",
			time(),
			time(),
			$db->qstr($session_data),
			$user_id,
			$db->qstr($user_ip),
			$db->qstr($user_agent),
			$db->qstr($id)
		);
		$db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);

		if(0==$db->Affected_Rows()) {
			// Insert
			$sql = sprintf("INSERT IGNORE INTO devblocks_session (session_id, session_token, created, updated, refreshed_at, user_id, user_ip, user_agent, session_data) ".
				"VALUES (%s, %s, %d, %d, %d, %d, %s, %s, %s)",
				$db->qstr(sha1(random_bytes(255))),
				$db->qstr($id),
				time(),
				time(),
				time(),
				$user_id,
				$db->qstr($user_ip),
				$db->qstr($user_agent),
				$db->qstr($session_data)
			);
			$db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		}

		return true;
	}

	static function destroy($id) {
		$db = DevblocksPlatform::services()->database();

		if(!self::isReady())
			return false;
		
		if(40 == strlen($id)) {
			$db->ExecuteMaster(sprintf("DELETE FROM devblocks_session WHERE session_id = %s", $db->qstr($id)));
		} else {
			$db->ExecuteMaster(sprintf("DELETE FROM devblocks_session WHERE session_token = %s", $db->qstr($id)));
		}
		return true;
	}

	static function gc($maxlifetime) {
		$db = DevblocksPlatform::services()->database();
		
		if(!self::isReady())
			return false;
		
		// Clear abandoned unauthenticated sessions after 15 mins
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_session WHERE user_id = 0 AND updated < %d", time()-900));
		
		// We ignore caller's $maxlifetime (session.gc_maxlifetime) on purpose.
		// Look up Cerb's session max lifetime
		$maxlifetime = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::SESSION_LIFESPAN, CerberusSettingsDefaults::SESSION_LIFESPAN);
		
		if(!$maxlifetime)
			$maxlifetime = 86400;
		
		// Clear any remaining (authenticated) sessions older than the max idle lifetime
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_session WHERE updated < %d",time()-$maxlifetime));
		return true;
	}

	static function getAll() {
		$db = DevblocksPlatform::services()->database();

		if(!self::isReady())
			return false;

		return $db->GetArrayMaster("SELECT session_id, created, updated, user_id, user_ip, user_agent, refreshed_at, session_data FROM devblocks_session");
	}
	
	static function getLoggedInSeats($idle_secs=900) {
		$db = DevblocksPlatform::services()->database();

		if(!self::isReady())
			return false;
		
		return $db->GetArrayMaster(sprintf(
			"SELECT DISTINCT user_id FROM devblocks_session WHERE user_id > 0 AND updated >= %d",
			time()-$idle_secs
		));
	}

	static function destroyAll() {
		$db = DevblocksPlatform::services()->database();

		if(!self::isReady())
			return false;

		$db->ExecuteMaster("DELETE FROM devblocks_session");
	}

	static function destroyByWorkerIds($ids) {
		if(!self::isReady())
			return false;

		if(!is_array($ids)) $ids = array($ids);

		$ids_list = implode(',', $ids);

		if(empty($ids_list))
			return;

		$db = DevblocksPlatform::services()->database();
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_session WHERE user_id IN (%s)", $ids_list));
	}
};

class CerberusVisit extends DevblocksVisit {
	private $worker_id;
	private $imposter_id;

	const KEY_VIEW_LAST_ACTION = 'view_last_action';
	const KEY_MY_WORKSPACE = 'view_my_workspace';
	const KEY_WORKFLOW_FILTER = 'workflow_filter';

	public function __construct() {
		$this->worker_id = null;
		$this->imposter_id = null;
	}

	/**
	 * @return Model_Worker
	 */
	public function getWorker() {
		if(empty($this->worker_id))
			return null;

		return DAO_Worker::get($this->worker_id);
	}

	public function setWorker(Model_Worker $worker=null) {
		if(is_null($worker)) {
			$this->worker_id = null;

		} else {
			$this->worker_id = $worker->id;

			// Language
			if($worker->language) {
				$_SESSION['locale'] = $worker->language;
				DevblocksPlatform::setLocale($worker->language);
			}

			// Timezone
			if($worker->timezone) {
				$_SESSION['timezone'] = $worker->timezone;
				DevblocksPlatform::setTimezone($worker->timezone);
			}

			// Time format
			if($worker->time_format) {
				$_SESSION['time_format'] = $worker->time_format;
				DevblocksPlatform::setDateTimeFormat($worker->time_format);
			}
		}
	}

	public function isImposter() {
		return !empty($this->imposter_id);
	}

	/**
	 * @return Model_Worker
	 */
	public function getImposter() {
		if(empty($this->imposter_id))
			return null;

		return DAO_Worker::get($this->imposter_id);
	}

	public function setImposter(Model_Worker $worker=null) {
		if(is_null($worker)) {
			$this->imposter_id = null;
		} else {
			$this->imposter_id = $worker->id;
		}
	}
};

class CerbLoginWorkerAuthState {
	private static $_instance = null;
	
	private $email = null;
	private $is_consent_given = false;
	private $is_consent_required = false;
	private $is_mfa_authenticated = false;
	private $is_mfa_required = false;
	private $is_password_authenticated = false;
	private $is_sso_authenticated = false;
	private $params = [];
	private $redirect_uris = [];
	private $time_created_at = 0;
	private $time_consented_at = 0;
	private $time_mfa_challenged_at = 0;
	private $was_consent_asked = false;
	private $worker_id = 0;
	
	private $worker = null;
	
	static function getInstance() {
		if(!is_null(self::$_instance))
			return self::$_instance;
		
		@$session_state =& $_SESSION['login.state'];
		
		if(
			!$session_state 
			|| $session_state instanceof __PHP_Incomplete_Class
			|| $session_state->time_created_at + 1200 < time() // expire after 20 minutes
		) {
			$session_state = new CerbLoginWorkerAuthState();
		}
		
		self::$_instance = $session_state;
		return self::$_instance;
	}
	
	private function __construct() {
		$this->time_created_at = time();
		
		// If we know the email from a cookie, use it
		if(array_key_exists('cerb_login_email', $_COOKIE))
			$this->email = $_COOKIE['cerb_login_email'];
	}
	
	function __sleep() {
		return [
			'email',
			'is_consent_given',
			'is_consent_required',
			'is_mfa_authenticated',
			'is_mfa_required',
			'is_password_authenticated',
			'is_sso_authenticated',
			'params',
			'redirect_uris',
			'time_created_at',
			'time_consented_at',
			'time_mfa_challenged_at',
			'was_consent_asked',
			'worker_id',
		];
	}
	
	function __wakeup() {
		if($this->worker_id)
			$this->setWorker(DAO_Worker::get($this->worker_id));
	}
	
	function destroy() {
		self::$_instance = null;
		unset($_SESSION['login.state']);
	}
	
	function clearAuthState() {
		return $this
			->setWorker(null)
			->setParams([])
			->setIsConsentGiven(false)
			->setIsMfaAuthenticated(false)
			->setIsMfaRequired(false)
			->setIsPasswordAuthenticated(false)
			->setIsSSOAuthenticated(false)
			->setTimeConsentedAt(0)
			->setTimeMfaChallengedAt(0)
			->setWasConsentAsked(false)
			;
	}
	
	function getEmail() {
		return $this->email;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	function isAuthenticated(array $options=[]) {
		if(!$this->getWorker())
			return false;
		
		if(!(array_key_exists('ignore_mfa', $options) && $options['ignore_mfa']))
		if($this->isMfaRequired() && !$this->isMfaAuthenticated())
			return false;
		
		if($this->isSSOAuthenticated())
			return true;
		
		if(!$this->isPasswordAuthenticated())
			return false;
		
		return true;
	}
	
	function isConsentGiven() {
		return $this->is_consent_given;
	}
	
	function isConsentRequired() {
		return $this->is_consent_required;
	}
	
	function isMfaAuthenticated() {
		return $this->is_mfa_authenticated;
	}
	
	function isMfaRequired() {
		return $this->is_mfa_required;
	}
	
	function isPasswordAuthenticated() {
		return $this->worker_id && $this->is_password_authenticated;
	}
	
	function isSSOAuthenticated() {
		return $this->is_sso_authenticated;
	}
	
	function getParam($key, $default=null) {
		if(array_key_exists($key, $this->params))
			return $this->params[$key];
		
		return $default;
	}
	
	function popRedirectUri() {
		$uri = array_pop($this->redirect_uris);
		return $uri;
	}
	
	function pushRedirectUri($uri, $replace=false) {
		if($replace)
			$this->redirect_uris = [];
		
		$this->redirect_uris[] = $uri;
		return $this;
	}
	
	function getWorker() {
		return $this->worker;
	}
	
	function getWorkerId() {
		return $this->worker_id;
	}
	
	function setEmail($email) {
		$url_writer = DevblocksPlatform::services()->url();
		
		$this->email = DevblocksPlatform::strLower($email);
		
		// Set a cookie
		setcookie(
			'cerb_login_email',
			$email,
			time()+30*86400,
			$url_writer->write('c=login',false,false),
			'',
			$url_writer->isSSL(),
			true
		);
		
		return $this;
	}
	
	function setIsConsentGiven($bool) {
		$this->is_consent_given = boolval($bool);
		return $this;
	}
	
	function setIsConsentRequired(array $params) {
		$this->is_consent_required = $params ?: false;
		return $this;
	}
	
	function setIsPasswordAuthenticated($bool) {
		$this->is_password_authenticated = boolval($bool);
		return $this;
	}
	
	function setIsMfaAuthenticated($bool) {
		$this->is_mfa_authenticated = boolval($bool);
		return $this;
	}
	
	function setIsMfaRequired($bool) {
		$this->is_mfa_required = boolval($bool);
		return $this;
	}
	
	function setIsSSOAuthenticated($bool) {
		$this->is_sso_authenticated = boolval($bool);
		return $this;
	}
	
	function setParams(array $params) {
		$this->params = $params;
		return $this;
	}
	
	function setParam($key, $value) {
		$this->params[$key] = $value;
		return $this;
	}
	
	function setParamIncr($key, $n, $min=PHP_INT_MIN, $max=PHP_INT_MAX) {
		$value = intval($this->params[$key] ?? 0);
		$this->params[$key] = DevblocksPlatform::intClamp($value + intval($n), $min, $max);
		return $this;
	}
	
	function setParamDecr($key, $n, $min=PHP_INT_MIN, $max=PHP_INT_MAX) {
		$value = intval($this->params[$key] ?? 0);
		$this->params[$key] = DevblocksPlatform::intClamp($value - intval($n), $min, $max);
		return $this;
	}
	
	function setTimeConsentedAt($time) {
		$this->time_consented_at = intval($time);
		return $this;
	}
	
	function setTimeMfaChallengedAt($time) {
		$this->time_mfa_challenged_at = intval($time);
		return $this;
	}
	
	function setWasConsentAsked($bool) {
		$this->was_consent_asked = boolval($bool);
		return $this;
	}
	
	function setWorker($worker) {
		if(!($worker instanceof Model_Worker)) {
			$this->worker_id = 0;
			$this->worker = null;
			
		} else {
			$this->worker_id = $worker->id;
			$this->worker = $worker;
		}
		
		return $this;
	}
	
	function unsetParam($key) {
		unset($this->params[$key]);
		return $this;
	}
	
	function wasConsentAsked() {
		return $this->was_consent_asked;
	}
};

class Cerb_ORMHelper extends DevblocksORMHelper {
	/**
	 * @override
	 * @return array
	 */
	protected static function getFields() {
		return [];
	}

	static public function escape($str) {
		$db = DevblocksPlatform::services()->database();
		return $db->escape($str);
	}

	static public function qstr($str) {
		$db = DevblocksPlatform::services()->database();
		return $db->qstr($str);
	}
	
	static public function qstrArray(array $arr) {
		$db = DevblocksPlatform::services()->database();
		return $db->qstrArray($arr);
	}

	static function recastArrayToModel($array, $model_class) {
		if(false == ($model = new $model_class))
			return false;

		if(is_array($array))
		foreach($array as $k => $v) {
			$model->$k = $v;
		}

		return $model;
	}
	
	/**
	 * @param array $fields
	 * @param mixed $model
	 * @return array
	 */
	static function uniqueFields(array $fields, $model) : array {
		if(is_object($model))
			$model = (array) $model;

		if(!is_array($model))
			return [];

		foreach($fields as $k => $v) {
			if(isset($model[$k]) && $model[$k] == $v)
				unset($fields[$k]);
		}

		return $fields;
	}

	/**
	 *
	 * @param array $ids
	 * @return object[]
	 */
	static function getIds(array $ids) : array {
		if(!is_array($ids)) {
			if(is_null($ids) || !is_numeric($ids))
				return [];
			
			$ids = [$ids];
		}

		if(empty($ids))
			return [];
		
		if(method_exists(get_called_class(), 'getAll')) {
			$results = array_intersect_key(static::getAll(), array_flip($ids));
			
		} else {
			if(!method_exists(get_called_class(), 'getWhere'))
				return [];
			
			$ids = DevblocksPlatform::importVar($ids, 'array:integer');
			
			$results = static::getWhere(sprintf("id IN (%s)",
				implode(',', $ids)
			));
		}
		
		$models = [];
		
		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(array_key_exists($id, $results))
				$models[$id] = $results[$id];
		}
		
		return $models;
	}

	static protected function paramExistsInSet($key, $params) {
		$exists = false;

		if(is_array($params))
		array_walk_recursive($params, function($param) use ($key, &$exists) {
			if($param instanceof DevblocksSearchCriteria
				&& 0 == strcasecmp($param->field, $key))
					$exists = true;
		});

		return $exists;
	}

	static protected function _getRandom($table, $pkey='id') {
		$db = DevblocksPlatform::services()->database();
		$offset = $db->GetOneReader(sprintf("SELECT ROUND(RAND()*(SELECT COUNT(*)-1 FROM %s))", $table));
		return $db->GetOneReader(sprintf("SELECT %s FROM %s LIMIT %d,1", $pkey, $table, $offset));
	}

	static function _searchComponentsVirtualOwner(&$param, &$join_sql, &$where_sql) {
		$worker_ids = DevblocksPlatform::sanitizeArray($param->value, 'integer', array('nonzero','unique'));

		// Join and return anything
		if(DevblocksSearchCriteria::OPER_TRUE == $param->operator) {
			$param->operator = DevblocksSearchCriteria::OPER_IS_NOT_NULL;

		} else {
			if(empty($param->value)) {
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IN:
						$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
						break;
					case DevblocksSearchCriteria::OPER_NIN:
						$param->operator = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
						break;
				}
			}

			switch($param->operator) {
				case DevblocksSearchCriteria::OPER_IN:
					$where_sql .= sprintf("AND owner_context = %s AND owner_context_id IN (%s) ",
						self::qstr(CerberusContexts::CONTEXT_WORKER),
						implode(',', $worker_ids)
					);
					break;
				case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				case DevblocksSearchCriteria::OPER_IS_NULL:
					$worker_ids[] = 0;
					$where_sql .= sprintf("AND owner_context = %s AND owner_context_id IN (%s) ",
						self::qstr(CerberusContexts::CONTEXT_WORKER),
						implode(',', $worker_ids)
					);
					break;
				case DevblocksSearchCriteria::OPER_NIN:
					$where_sql .= sprintf("AND owner_context = %s AND owner_context_id NOT IN (%s) ",
						self::qstr(CerberusContexts::CONTEXT_WORKER),
						implode(',', $worker_ids)
					);
					break;
				case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
					$where_sql .= sprintf("AND owner_context = %s AND owner_context_id NOT = 0 ",
						self::qstr(CerberusContexts::CONTEXT_WORKER)
					);
					break;
				case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
					$worker_ids[] = 0;
					$where_sql .= sprintf("AND owner_context = %s AND owner_context_id NOT IN (%s) ",
						self::qstr(CerberusContexts::CONTEXT_WORKER),
						implode(',', $worker_ids)
					);
					break;
			}
		}
	}
};

class _CerbApplication_KataAutocompletions {
	function fromSchema(array $schema) {
		$autocompletions = [];
		
		// Crawl schema to create automation suggestions
		
		$recurse = function($object, $stack=[]) use (&$recurse, &$autocompletions) {
			$parent_key = $stack ? (implode(':', $stack) . ':') : '';
			
			foreach($object['attributes'] ?? [] as $attribute_key => $attribute) {
				$stack[] = $attribute_key;
				$attribute_types = $attribute['types'] ?? []; 
				
				if(array_key_exists('object', $attribute_types)) {
					$autocompletions[$parent_key][] = $attribute_key . ':';
					$recurse($attribute['types']['object'], $stack);
					
				} else if(array_key_exists('bool', $attribute_types)) {
					$autocompletions[$parent_key][] = $attribute_key . '@bool:';
					
				} else {
					$autocompletions[$parent_key][] = $attribute_key . ':';
				}
				
				array_pop($stack);
			}
		};
		
		$recurse($schema['schema']);
		
		return $autocompletions;
	}
	
	function automationEvent() {
		return [
			'' => [
				[
					'caption' => 'automation:',
					'snippet' => 'automation/${1:name}:'
				]
			],
			'automation:' => [
				[
					'caption' => 'uri:',
					'snippet' => 'uri: cerb:automation:${1:name}'
				],
				[
					'caption' => 'disabled:',
					'snippet' => 'disabled@bool: ${1:yes}'
				],
				'inputs:'
			],
			'automation:uri:' => [
				'type' => 'cerb-uri'
			],
			'automation:inputs:' => [
				'type' => 'automation-inputs'
			],
		];
	}
	
	function snippetPrompt() : array {
		return [
			'' => [
				[
					'caption' => 'checkbox:',
					'snippet' => "checkbox/\${1:name}:\n  label: \${2:Checkbox:}\n  default@bool: no\n",
				],
				[
					'caption' => 'picklist:',
					'snippet' => "picklist/\${1:name}:\n  label: \${2:Picklist:}\n  default: one\n  params:\n    options@list:\n      one\n      two\n      three\n",
				],
				[
					'caption' => 'text:',
					'snippet' => "text/\${1:name}:\n  label: \${2:Text:}\n  params:\n    multiple@bool: no\n",
				],
			],
			'checkbox:' => [
				'label:',
				'default:',
			],
			'picklist:' => [
				'label:',
				'params:',
				'default:',
			],
			'picklist:params:' => [
				[
					'caption' => 'options:',
					'snippet' => "options@list:\n  one\n  two\n  three\n",
				]
			],
			'text:' => [
				'label:',
				'params:',
				'default:',
			],
			'text:params:' => [
				'multiple@bool: no',
			],
		];
	}
	
	function portalInteractionWebsite() {
		return [
			'' => [
				'layout:',
				'security:',
			],
			
			'layout:' => [
				'badge:',
				'header:',
				'meta:',
			],
			'layout:badge:' => [
				'interaction:'
			],
			'layout:header:' => [
				'logo:',
				'navbar:',
			],
			'layout:header:logo:' => [
				'text:',
				'image:',
			],
			'layout:header:logo:image:' => [
				[
					'caption' => 'uri:',
					'snippet' => 'uri:',
					'score' => 2000,
				],
			],
			'layout:header:logo:image:uri:' => [
				'type' => 'cerb-uri',
				'params' => [
					'resource' => [
						'types' => [
							ResourceType_PortalImage::ID,
						]
					]
				]
			],
			'layout:header:navbar:' => [
				[
					'caption' => 'link:',
					'snippet' => "link/\${1:name}:\n  label: \${2:Label}\n  href: \${3:https://example.com}\n",
				]
			],
			'layout:header:navbar:link:' => [
				[
					'caption' => 'label:',
					'snippet' => 'label:',
					'score' => 1999,
				],
				[
					'caption' => 'href:',
					'snippet' => 'href:',
					'score' => 2000,
				],
				'class:',
			],
			'layout:header:navbar:link:class:' => [
				'cerb-link-button',
			],
			'layout:header:navbar:href:' => [
				[
					'caption' => '(External URL)',
					'snippet' => "\${1:https://example.com/}",
				],
				[
					'caption' => '(Relative path)',
					'snippet' => "\${1:/relative/path}",
				],
			],
				
			'layout:meta:' => [
				'favicon:',
				'title:',
			],
			'layout:meta:favicon:' => [
				'uri:',
			],
			'layout:meta:favicon:uri:' => [
				'type' => 'cerb-uri',
				'params' => [
					'resource' => [
						'types' => [
							ResourceType_PortalImage::ID,
						]
					]
				]
			],
			
			'security:' => [
				'contentSecurityPolicy:',
				'imageRequests:',
			],
			'security:contentSecurityPolicy:' => [
				[
					'caption' => 'imageHosts:',
					'snippet' => "imageHosts@list:\n\thttps://example.com",
				],
			],
			'security:imageRequests:' => [
				'secret:',
			],
		];
	}
}

class _CerbApplication_KataSchemas {
	function automation() : string {
		return <<< EOD
    definitions:
      inputs:
        types:
          object:
            attributes:
              array:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      default:
                        types:
                          list:
                      required:
                        types:
                          bool:
                      snippet:
                        types:
                          list:
                          string:
              record:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      default:
                        types:
                          number:
                      expand:
                        types:
                          string:
                      record_type:
                        types:
                          string:
                      required:
                        types:
                          bool:
                      snippet:
                        types:
                          list:
                          string:
              records:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      default:
                        types:
                          # [TODO] list of type number
                          list:
                      expand:
                        types:
                          string:
                      record_type:
                        types:
                          string:
                      required:
                        types:
                          bool:
                      snippet:
                        types:
                          list:
                          string:
              text:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      default:
                        types:
                          string:
                      required:
                        types:
                          bool:
                      snippet:
                        types:
                          list:
                          string:
                      type_options:
                      type:
                        types:
                          string:      
      
      commands:
        types:
          object:
            attributes:
              await:
                multiple@bool: yes
                types:
                  list:
              
              data.query:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              query:
                                types:
                                  string:
                              query_params:
                                types:
                                  list:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              decision:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      outcome:
                        multiple@bool: yes
                        types:
                          object:
                            attributes:
                              if:
                                types:
                                  bool:
                              then:
                                ref: commands
                      
              decrypt.pgp:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              message:
                                required@bool: yes
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              email.parse:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              message:
                                required@bool: yes
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              encrypt.pgp:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              message:
                                types:
                                  string:
                              public_keys:
                                types:
                                  object:
                                    attributes:
                                      ids:
                                        types:
                                          list:
                                      uri:
                                        types:
                                          string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              error:
                multiple@bool: yes
                types:
                  list:
              
              file.read:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              extract:
                                types:
                                  string:
                              length:
                                types:
                                  number:
                              offset:
                                types:
                                  number:
                              uri:
                                types:
                                  string:
                      on_error:
                        ref: commands
                      on_simulate:
                        ref: commands
                      on_success:
                        ref: commands
                      output:
                        types:
                          string:
                
              function:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          list:
                      on_error:
                        ref: commands
                      on_simulate:
                        ref: commands
                      on_success:
                        ref: commands
                      output:
                        types:
                          string:
                      uri:
                        required@bool: yes
                        types:
                          string:
              
              http.request:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              authentication:
                                types:
                                  string:
                              body:
                                types:
                                  list:
                                  string:
                              headers:
                                types:
                                  list:
                                  string:
                              method:
                                types:
                                  string:
                              timeout:
                                types:
                                  number:
                              url:
                                types:
                                  # [TODO] URL
                                  string:
                      on_error:
                        ref: commands
                      on_simulate:
                        ref: commands
                      on_success:
                        ref: commands
                      output:
                        types:
                          string:
              
              kata.parse:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              kata:
                                types:
                                  list:
                                  string:
                              dict:
                                types:
                                  list:
                              schema:
                                types:
                                  list:
                      on_error:
                        ref: commands
                      on_simulate:
                        ref: commands
                      on_success:
                        ref: commands
                      output:
                        types:
                          string:
              
              log:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      message:
                        types:
                          string:
                  string:
              
              log.error:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      message:
                        types:
                          string:
                  string:
              
              log.warn:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      message:
                        types:
                          string:
                  string:
              
              outcome:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      if:
                        types:
                          bool:
                      then:
                        ref: commands
              
              metric.increment:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              dimensions:
                                types:
                                  any:
                              is_realtime:
                                types:
                                  bool:
                              metric_name:
                                types:
                                  string:
                              timestamp:
                                types:
                                  number:
                              values:
                                types:
                                  list:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              queue.pop:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              queue_name:
                                types:
                                  string:
                              limit:
                                types:
                                  number:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              queue.push:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              queue_name:
                                types:
                                  string:
                              messages:
                                types:
                                  list:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              record.create:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              expand:
                                types:
                                  string:
                              fields:
                                types:
                                  list:
                              record_type:
                                types:
                                  # [TODO] Record types
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              record.delete:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              record_type:
                                types:
                                  string:
                              record_id:
                                types:
                                  number:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              record.get:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              record_id:
                                types:
                                  number:
                              record_type:
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              record.search:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              record_expand:
                                types:
                                  string:
                              record_query:
                                types:
                                  string:
                              record_query_params:
                                types:
                                  list:
                              record_type:
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              record.update:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              expand:
                                types:
                                  string:
                              fields:
                                types:
                                  # [TODO] Dependent on record_type
                                  list:
                              record_id:
                                types:
                                  number:
                              record_type:
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
                    
              record.upsert:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              fields:
                                types:
                                  list:
                              record_query:
                                types:
                                  string:
                              record_query_params:
                                types:
                                  list:
                              record_type:
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              repeat:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      as:
                        types:
                          string:
                      do:
                        ref: commands
                      each:
                        types:
                          list:
              
              return:
                multiple@bool: yes
                types:
                  list:
              
              set:
                multiple@bool: yes
                types:
                  list:
              
              simulate.error:
                multiple@bool: yes
                types:
                  list:
              
              simulate.success:
                multiple@bool: yes
                types:
                  list:
              
              storage.delete:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              key:
                                required@bool: yes
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              storage.get:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              key:
                                types:
                                  string:
                              default:
                                types:
                                  list:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              storage.set:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              key:
                                types:
                                  string:
                              expires:
                                types:
                                  string:
                              value:
                                types:
                                  list:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              var.expand:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              key:
                                types:
                                  string:
                              paths:
                                required@bool: yes
                                types:
                                  list:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              var.push:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              key:
                                types:
                                  string:
                              value:
                                types:
                                  list:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              var.set:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              key:
                                types:
                                  string:
                              value:
                                types:
                                  list:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              var.unset:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      inputs:
                        types:
                          object:
                            attributes:
                              key:
                                types:
                                  string:
                      output:
                        types:
                          string:
                      on_error:
                        ref: commands
                      on_success:
                        ref: commands
                      on_simulate:
                        ref: commands
              
              while:
                multiple@bool: yes
                types:
                  object:
                    attributes:
                      if:
                        types:
                          bool:
                      do:
                        ref: commands
        
    schema:
      attributes:
        inputs:
          ref: inputs
        start:
          required@bool: yes
          ref: commands
    EOD;
	}
	
	function automationEvent() : string {
		return <<< EOD
    schema:
      attributes:
        automation:
          multiple@bool: yes
          types:
            object:
              attributes:
                uri:
                  required@bool: yes
                  types:
                    # [TODO] Cerb Record URI of type automation
                    string:
                disabled:
                  types:
                    bool:
                inputs:
                  types:
                    list:
        
        # [TODO] Remove this in 11.0+
        behavior:
          multiple@bool: yes
          types:
            object:
              attributes:
                uri:
                  required@bool: yes
                  types:
                    string:
                disabled:
                  types:
                    bool:
                inputs:
                  types:
                    list:
    EOD;
	}
	
	function automationPolicy() : string {
		return <<< EOD
    &policyRule:
      types:
        object:
          attributes@ref:
            allow:
              multiple@bool: yes
              types:
                bool:
            deny:
              multiple@bool: yes
              types:
                bool:

    schema:
      attributes:
        callers:
          types:
            object:
              attributePatterns:
                pattern/caller:
                  match: *
                  attributes@ref: policyRule
        
        commands:
          types:
            object:
              attributes:
                data.query@ref: policyRule
                decrypt.pgp@ref: policyRule
                email.parse@ref: policyRule
                encrypt.pgp@ref: policyRule
                file.read@ref: policyRule
                function@ref: policyRule
                http.request@ref: policyRule
                metric.increment@ref: policyRule
                queue.pop@ref: policyRule
                queue.push@ref: policyRule
                record.create@ref: policyRule
                record.delete@ref: policyRule
                record.get@ref: policyRule
                record.search@ref: policyRule
                record.update@ref: policyRule
                record.upsert@ref: policyRule
                storage.delete@ref: policyRule
                storage.get@ref: policyRule
                storage.set@ref: policyRule

        settings:
          types:
            object:
              attributes:
                time_limit_ms:
                  types:
                    number:
    EOD;		
	}
	
	function dashboardPrompts() : string {
		return <<< EOD
    &label:
      required@bool: yes
      types:
        string:
    
    schema:
      attributes:
        chooser:
          multiple@bool: yes
          types:
            object:
              attributes:
                label@ref: label
                default:
                  types:
                    list:
                    string:
                params:
                  types:
                    object:
                      attributes:
                        context:
                          types:
                            string:
                        single:
                          types:
                            bit:
                            bool:
        
        date_range:
          multiple@bool: yes
          types:
            object:
              attributes:
                label@ref: label
                default:
                  types:
                    string:
                params:
                  types:
                    object:
                      attributes:
                        presets:
                          types:
                            # [TODO] attributePatterns (* -> query)
                            list:
        
        picklist:
          multiple@bool: yes
          types:
            object:
              attributes:
                label@ref: label
                default:
                  types:
                    list:
                    string:
                params:
                  types:
                    object:
                      attributes:
                        multiple:
                          types:
                            bool:
                        options:
                          required@bool: yes
                          types:
                            list:
        
        text:
          multiple@bool: yes
          types:
            object:
              attributes:
                label@ref: label
                default:
                  types:
                    string:
    EOD;
	}
	
	function interactionToolbar() : string {
		return <<< EOD
    definitions:
      interaction:
        multiple@bool: yes
        types:
          object:
            attributes:
              after:
                types:
                  list:
              badge:
                types:
                  string:
              hidden:
                types:
                  bool:
              icon:
                types:
                  string:
              inputs:
                types:
                  list:
              label:
                types:
                  string:
              tooltip:
                types:
                  string:
              uri:
                required@bool: yes
                types:
                  string:
      menu:
        multiple@bool: yes
        types:
          object:
            attributes:
              default:
                types:
                  string:
              hidden:
                types:
                  bool:
              icon:
                types:
                  string:
              items:
                types:
                  object:
                    attributes:
                      interaction:
                        ref: interaction
                      menu:
                        ref: menu
              label:
                types:
                  string:
              tooltip:
                types:
                  string:
    
    schema:
      attributes:
        interaction:
          ref: interaction
        menu:
          ref: menu
    EOD;
	}
	
	function map() : string {
		return <<< EOD
    schema:
      attributes:
        map:
          types:
            object:
              attributes:
                points:
                  types:
                    object:
                      attributes:
                        data:
                          types:
                            object:
                              attributes:
                                point:
                                  multiple@bool: yes
                                  types:
                                    object:
                                      attributes:
                                        latitude:
                                          types:
                                            number:
                                        longitude:
                                          types:
                                            number:
                                        properties:
                                          types:
                                            # [TODO] Object with any attribute
                                            list:
                        fill:
                          types:
                            object:
                              attributes:
                                color_map:
                                  types:
                                    object:
                                      attributes:
                                        property:
                                          types:
                                            string:
                                        colors:
                                          types:
                                            list:
                                default:
                                  types:
                                    string:
                        filter:
                          types:
                            object:
                              attributes:
                                is:
                                  types:
                                    string:
                                    list:
                                not:
                                  types:
                                    string:
                                    list:
                                property:
                                  types:
                                    string:
                        label:
                          types:
                            object:
                              attributes:
                                properties:
                                  types:
                                    list:
                                title:
                                  types:
                                    string:
                        resource:
                          types:
                            object:
                              attributes:
                                uri:
                                  types:
                                    string:
                        size:
                          types:
                            object:
                              attributes:
                                default:
                                  types:
                                    string:
                                value_map:
                                  types:
                                    object:
                                      attributes:
                                        property:
                                          types:
                                            string:
                                        values:
                                          types:
                                            list:
                projection:
                  types:
                    object:
                      attributes:
                        center:
                          types:
                            object:
                              attributes:
                                latitude:
                                  types:
                                    number:
                                longitude:
                                  types:
                                    number:
                        scale:
                          types:
                            number:
                        type:
                          types:
                            # [TODO] mercator, albersUsa, naturalEarth
                            string:
                        zoom:
                          types:
                            object:
                              attributes:
                                latitude:
                                  types:
                                    number:
                                longitude:
                                  types:
                                    number:
                                scale:
                                  types:
                                    number:
                regions:
                  types:
                    object:
                      attributes:
                        fill:
                          types:
                            object:
                              attributes:
                                choropleth:
                                  types:
                                    object:
                                      attributes:
                                        property:
                                          types:
                                            string:
                                        classes:
                                          types:
                                            number:
                                color_key:
                                  types:
                                    object:
                                      attributes:
                                        property:
                                          types:
                                            string:
                                color_map:
                                  types:
                                    object:
                                      attributes:
                                        property:
                                          types:
                                            string:
                                        colors:
                                          types:
                                            list:
                        filter:
                          types:
                            object:
                              attributes:
                                is:
                                  types:
                                    string:
                                    list:
                                not:
                                  types:
                                    string:
                                    list:
                                property:
                                  types:
                                    string:
                        label:
                          types:
                            object:
                              attributes:
                                properties:
                                  types:
                                    list:
                                title:
                                  types:
                                    string:
                        properties:
                          types:
                            object:
                              attributes:
                                join:
                                  required@bool: yes
                                  types:
                                    object:
                                      attributes:
                                        property:
                                          required@bool: yes
                                          types:
                                            string:
                                        # [TODO] upper, lower
                                        case:
                                          types:
                                            string:
                                data:
                                  types:
                                    list:
                                resource:
                                  types:
                                    object:
                                      attributes:
                                        uri:
                                          types:
                                            string:
                resource:
                  types:
                    object:
                      attributes:
                        uri:
                          required@bool: yes
                          types:
                            # [TODO] Format cerb URI
                            string:
    EOD;
	}
	
	function metricDimensions() : string {
		return <<< EOD
    schema:
      attributes:
        extension:
          multiple@bool: yes
          types:
            object:
              attributes:
        number:
          multiple@bool: yes
          types:
            object:
              attributes:
        record:
          multiple@bool: yes
          types:
            object:
              attributes:
                record_type:
                  required@bool: yes
                  types:
                    string:
        text:
          multiple@bool: yes
          types:
            object:
              attributes:
    EOD;
	}
	
	function sheet() : string {
		return <<< EOD
    &columnLabel:
      types:
        string:
    
    schema:
      attributes:
        layout:
          types:
            object:
              attributes:
                filtering:
                  types:
                    bool:
                headings:
                  types:
                    bool:
                paging:
                  types:
                    bool:
                style:
                  types:
                    # [TODO] Possible values: table, etc.
                    string:
                title_column:
                  types:
                    string:
        
        columns:
          types:
            object:
              attributes:
                card:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                bold:
                                  types:
                                    bool:
                                context_key:
                                  types:
                                    string:
                                context_template:
                                  types:
                                    string:
                                context:
                                  types:
                                    string:
                                icon:
                                  types:
                                    string:
                                    object:
                                      attributes:
                                        image_key:
                                          types:
                                            string:
                                        image_template:
                                          types:
                                            string:
                                        image:
                                          types:
                                            string:
                                        record_uri:
                                          types:
                                            string:
                                id_key:
                                  types:
                                    string:
                                id_template:
                                  types:
                                    string:
                                id:
                                  types:
                                    string:
                                image:
                                  types:
                                    bool:
                                label_key:
                                  types:
                                    string:
                                label_template:
                                  types:
                                    string:
                                label:
                                  types:
                                    string:
                                underline:
                                  types:
                                    bool:
                
                date:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                bold:
                                  types:
                                    bool:
                                format:
                                  types:
                                    string:
                                value_key:
                                  types:
                                    string:
                                value_template:
                                  types:
                                    string:
                                value:
                                  types:
                                    string:
                
                icon:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                image_key:
                                  types:
                                    string:
                                image_template:
                                  types:
                                    string:
                                image:
                                  types:
                                    string:
                                record_uri:
                                  types:
                                    string:
                
                link:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                bold:
                                  types:
                                    bool:
                                href_key:
                                  types:
                                    string:
                                href_template:
                                  types:
                                    string:
                                href:
                                  types:
                                    string:
                                text_key:
                                  types:
                                    string:
                                text_template:
                                  types:
                                    string:
                                text:
                                  types:
                                    string:
                        
                search_button:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                context_key:
                                  types:
                                    string:
                                context_template:
                                  types:
                                    string:
                                context:
                                  types:
                                    string:
                                query_key:
                                  types:
                                    string:
                                query_template:
                                  types:
                                    string:
                                query:
                                  types:
                                    string:
                
                search:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                bold:
                                  types:
                                    bool:
                                context_key:
                                  types:
                                    string:
                                context_template:
                                  types:
                                    string:
                                context:
                                  types:
                                    string:
                                label_key:
                                  types:
                                    string:
                                label_template:
                                  types:
                                    string:
                                label:
                                  types:
                                    string:
                                query_key:
                                  types:
                                    string:
                                query_template:
                                  types:
                                    string:
                                query:
                                  types:
                                    string:
                
                selection:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        params:
                          types:
                            object:
                              attributes:
                                value_key:
                                  types:
                                    string:
                                value_template:
                                  types:
                                    string:
                                value:
                                  types:
                                    string:
                
                slider:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                max:
                                  types:
                                    number:
                                min:
                                  types:
                                    number:
                                value_key:
                                  types:
                                    string:
                                value_template:
                                  types:
                                    string:
                                value:
                                  types:
                                    string:
                
                text:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                bold:
                                  types:
                                    bool:
                                icon:
                                  types:
                                    string:
                                    object:
                                      attributes:
                                        image_key:
                                          types:
                                            string:
                                        image_template:
                                          types:
                                            string:
                                        image:
                                          types:
                                            string:
                                        record_uri:
                                          types:
                                            string:
                                value_key:
                                  types:
                                    string:
                                value_map:
                                  types:
                                    # [TODO]
                                    list:
                                value_template:
                                  types:
                                    string:
                                value:
                                  types:
                                    string:
                
                time_elapsed:
                  multiple@bool: yes
                  types:
                    object:
                      attributes:
                        label@ref: columnLabel
                        params:
                          types:
                            object:
                              attributes:
                                bold:
                                  types:
                                    bool:
                                precision:
                                  types:
                                    number:
                                value_key:
                                  types:
                                    string:
                                value_template:
                                  types:
                                    string:
                                value:
                                  types:
                                    string:
    EOD;
	}
	
	function snippetPrompts() : string {
		return <<< EOD
    schema:
      attributes:
        checkbox:
          multiple@bool: yes
          types:
            object:
              attributes:
                label:
                  required@bool: yes
                  types:
                    string:
                default:
                  types:
                    bit:
                    bool:
        chooser:
          multiple@bool: yes
          types:
            object:
              attributes:
                label:
                  required@bool: yes
                  types:
                    string:
                default:
                  types:
                    string:
                params:
                  types:
                    object:
                      attributes:
                        type:
                          types:
                            # [TODO] Record type (format)
                            string:
                        query:
                          types:
                            string:
        picklist:
          multiple@bool: yes
          types:
            object:
              attributes:
                label:
                  required@bool: yes
                  types:
                    string:
                default:
                  types:
                    string:
                params:
                  types:
                    object:
                      attributes:
                        options:
                          types:
                            # [TODO] Each list item must be a string
                            list:
        text:
          multiple@bool: yes
          types:
            object:
              attributes:
                label:
                  required@bool: yes
                  types:
                    string:
                default:
                  types:
                    string:
                params:
                  types:
                    object:
                      attributes:
                        multiple:
                          types:
                            bit:
                            bool:
    EOD;		
	}
}

class _CerbApplication_Packages {
	function prompts($json_string) {
		$json = Cerb_Packages::loadPackageFromJson($json_string);
		return Cerb_Packages::getPromptsFromjson($json);
	}
	
	/**
	 * @param string $json_string
	 * @param array $prompts
	 * @param array $records_created
	 * @param array $records_modified
	 * @return bool|null
	 * @throws Exception_DevblocksValidationError
	 */
	function import($json_string, array $prompts=[], &$records_created=null, &$records_modified=null) : ?bool {
		$json = Cerb_Packages::loadPackageFromJson($json_string);
		return Cerb_Packages::importFromJson($json, $prompts, $records_created, $records_modified);
	}
	
	function importToLibraryFromString($package_json) {
		$db = DevblocksPlatform::services()->database();
		
		$storage = new DevblocksStorageEngineDisk();
		$storage->setOptions([]);
		
		if(false === (@$package_data = json_decode($package_json, true)))
			return;
		
		if(false == ($library_meta = ($package_data['package']['library'] ?? null)))
			return;
		
		$db->ExecuteMaster(sprintf("INSERT INTO package_library (uri, name, description, instructions, point, updated_at, package_json) ".
			"VALUES (%s, %s, %s, %s, %s, %d, %s) ".
			"ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), name=VALUES(name), uri=VALUES(uri), description=VALUES(description), instructions=VALUES(instructions), point=VALUES(point), updated_at=VALUES(updated_at), package_json=VALUES(package_json)",
			$db->qstr($library_meta['uri'] ?? ''),
			$db->qstr($library_meta['name'] ?? ''),
			$db->qstr($library_meta['description'] ?? ''),
			$db->qstr($library_meta['instructions'] ?? ''),
			$db->qstr($library_meta['point'] ?? ''),
			time(),
			$db->qstr($package_json)
		));
		
		$package_id = $db->LastInsertId();
		
		// Package images
		if($package_id && array_key_exists('image', $library_meta) && $library_meta['image']) {
			$imagedata = $library_meta['image'];
			
			if(DevblocksPlatform::strStartsWith($imagedata,'data:image/png;base64,')) {
				$content_type = 'image/png';
				
				// Decode it to binary
				if(false !== ($imagedata = base64_decode(substr($imagedata, 22)))) {
					$sql = sprintf("INSERT INTO context_avatar (context,context_id,content_type,is_approved,updated_at) ".
						"VALUES (%s,%d,%s,%d,%d) ".
						"ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), context=VALUES(context), context_id=VALUES(context_id), is_approved=VALUES(is_approved), updated_at=VALUES(updated_at)",
						$db->qstr('cerberusweb.contexts.package.library'),
						$package_id,
						$db->qstr($content_type),
						1,
						time()
					);
					$db->ExecuteMaster($sql);
					
					$storage_id = $db->LastInsertId();
					
					// Put in storage
					$storage_key = $storage->put('context_avatar', $storage_id, $imagedata);
					
					// Update record key
					$sql = sprintf("UPDATE context_avatar SET storage_extension = %s, storage_key = %s, storage_size = %d WHERE id = %d",
						$db->qstr('devblocks.storage.engine.disk'),
						$db->qstr($storage_key),
						strlen($imagedata),
						$storage_id
					);
					$db->ExecuteMaster($sql);
				}
			}
		}
	}
	
	function importToLibraryFromFiles(array $package_files, $package_basepath=null) {
		if(is_null($package_basepath))
			$package_basepath = APP_PATH . '/features/cerberusweb.core/packages/library/';
		
		foreach($package_files as $package_file) {
			if(false == ($package_json = file_get_contents($package_basepath . $package_file)))
				continue;
			
			$this->importToLibraryFromString($package_json);
		}
	}
};
