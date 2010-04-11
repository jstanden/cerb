<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
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
define("APP_BUILD", 2010041001);
define("APP_MAIL_PATH", APP_STORAGE_PATH . '/mail/');

require_once(APP_PATH . "/api/DAO.class.php");
require_once(APP_PATH . "/api/Model.class.php");
require_once(APP_PATH . "/api/Extension.class.php");

// App Scope ClassLoading
$path = APP_PATH . '/api/app/';

DevblocksPlatform::registerClasses($path . 'Mail.php', array(
	'CerberusMail',
));

DevblocksPlatform::registerClasses($path . 'Parser.php', array(
	'CerberusParser',
	'CerberusParserMessage',
	'ParserFile',
));

DevblocksPlatform::registerClasses($path . 'Update.php', array(
	'ChUpdateController',
));

DevblocksPlatform::registerClasses($path . 'Utils.php', array(
	'CerberusUtils',
));

/**
 * Application-level Facade
 */
class CerberusApplication extends DevblocksApplication {
	const INDEX_TICKETS = 'tickets';
		
	const VIEW_SEARCH = 'search';
	const VIEW_MAIL_WORKFLOW = 'mail_workflow';
	const VIEW_OVERVIEW_ALL = 'overview_all';
	
	const CACHE_HELPDESK_FROMS = 'ch_helpdesk_froms';
	
	/**
	 * @return CerberusVisit
	 */
	static function getVisit() {
		$session = DevblocksPlatform::getSessionService();
		return $session->getVisit();
	}
	
	/**
	 * @return Model_Worker
	 */
	static function getActiveWorker() {
		$visit = self::getVisit();
		return (null != $visit) 
			? $visit->getWorker()
			: null
			;
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
		$errors = array();
		
		// Privileges
		
		// Make sure the temporary directories of Devblocks are writeable.
		if(!is_writeable(APP_TEMP_PATH)) {
			$errors[] = APP_TEMP_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!file_exists(APP_TEMP_PATH . "/templates_c")) {
			@mkdir(APP_TEMP_PATH . "/templates_c");
		}
		
		if(!is_writeable(APP_TEMP_PATH . "/templates_c/")) {
			$errors[] = APP_TEMP_PATH . "/templates_c/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
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
		
		if(!is_writeable(APP_STORAGE_PATH . "/import/fail")) {
			$errors[] = APP_STORAGE_PATH . "/import/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/import/new")) {
			$errors[] = APP_STORAGE_PATH . "/import/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/mail/new/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH . "/mail/fail/")) {
			$errors[] = APP_STORAGE_PATH . "/mail/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		// Requirements
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.2") >=0) {
		} else {
			$errors[] = 'Cerberus Helpdesk 5.x requires PHP 5.2 or later. Your server PHP version is '.PHP_VERSION;
		}
		
		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
		} else {
			$errors[] = 'file_uploads is disabled in your php.ini file. Please enable it.';
		}
		
		// File Upload Temporary Directory
		// [TODO] This isn't fatal
//		$ini_upload_tmp_dir = ini_get("upload_tmp_dir");
//		if(!empty($ini_upload_tmp_dir)) {
//		} else {
//			$errors[] = 'upload_tmp_dir is empty in your php.ini file.	Please set it.';
//		}
		
		// Memory Limit
		$memory_limit = ini_get("memory_limit");
		if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
		} else {
			$ini_memory_limit = intval($memory_limit);
			if($ini_memory_limit >= 16) {
			} else {
				$errors[] = 'memory_limit must be 16M or larger (32M recommended) in your php.ini file.  Please increase it.';
			}
		}
		
		// Extension: MySQL
		if(extension_loaded("mysql")) {
		} else {
			$errors[] = "The 'MySQL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: Sessions
		if(extension_loaded("session")) {
		} else {
			$errors[] = "The 'Session' PHP extension is required.  Please enable it.";
		}
		
		// Extension: PCRE
		if(extension_loaded("pcre")) {
		} else {
			$errors[] = "The 'PCRE' PHP extension is required.  Please enable it.";
		}
		
		// Extension: GD
		if(extension_loaded("gd") && function_exists('imagettfbbox')) {
		} else {
			$errors[] = "The 'GD' PHP extension (with FreeType library support) is required.  Please enable them.";
		}
		
		// Extension: IMAP
		if(extension_loaded("imap")) {
		} else {
			$errors[] = "The 'IMAP' PHP extension is required.  Please enable it.";
		}
		
		// Extension: MailParse
		if(extension_loaded("mailparse")) {
		} else {
			$errors[] = "The 'MailParse' PHP extension is required.  Please enable it.";
		}
		
		// Extension: mbstring
		if(extension_loaded("mbstring")) {
		} else {
			$errors[] = "The 'MbString' PHP extension is required.  Please	enable it.";
		}
		
		// Extension: XML
		if(extension_loaded("xml")) {
		} else {
			$errors[] = "The 'XML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SimpleXML
		if(extension_loaded("simplexml")) {
		} else {
			$errors[] = "The 'SimpleXML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: DOM
		if(extension_loaded("dom")) {
		} else {
			$errors[] = "The 'DOM' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SPL
		if(extension_loaded("spl")) {
		} else {
			$errors[] = "The 'SPL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: JSON
		if(extension_loaded("json")) {
		} else {
			$errors[] = "The 'JSON' PHP extension is required.  Please enable it.";
		}
		
		return $errors;
	}
	
	static function generatePassword($length=8) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
		$len = strlen($chars)-1;
		$password = '';
		
		for($x=0;$x<$length;$x++) {
			$chars = str_shuffle($chars);
			$password .= substr($chars,mt_rand(0,$len),1);
		}
		
		return $password;		
	}
	
	// [JAS]: [TODO] Cleanup + move (platform, diff ext point, DAO?)
	/**
	 * @return DevblocksTourCallout[]
	 */
	static function getTourCallouts() {
	    static $callouts = null;
	    
	    if(!is_null($callouts))
	        return $callouts;
	    
	    $callouts = array();
	        
	    $listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
	    foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
	         $inst = $listenerManifest->createInstance(); /* @var $inst IDevblocksTourListener */
	         
	         if($inst instanceof IDevblocksTourListener)
	             $callouts += $inst->registerCallouts();
	    }
	    
	    return $callouts;
	}
	    
	/**
	 * Enter description here...
	 *
	 * @return a unique ticket mask as a string
	 */
	static function generateTicketMask($pattern = "LLL-NNNNN-NNN") {
		$letters = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$numbers = "123456789";

		do {		
			$mask = "";
			$bytes = preg_split('//', $pattern, -1, PREG_SPLIT_NO_EMPTY);
			
			if(is_array($bytes))
			foreach($bytes as $byte) {
				switch(strtoupper($byte)) {
					case 'L':
						$mask .= substr($letters,mt_rand(0,strlen($letters)-1),1);
						break;
					case 'N':
						$mask .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);
						break;
					case 'C': // L or N
						if(mt_rand(0,100) >= 50) { // L
							$mask .= substr($letters,mt_rand(0,strlen($letters)-1),1);	
						} else { // N
							$mask .= substr($numbers,mt_rand(0,strlen($numbers)-1),1);	
						}
						break;
					case 'Y':
						$mask .= date('Y');
						break;
					case 'M':
						$mask .= date('m');
						break;
					case 'D':
						$mask .= date('d');
						break;
					default:
						$mask .= $byte;
						break;
				}
			}
		} while(null != DAO_Ticket::getTicketIdByMask($mask));
		
//		echo "Generated unique mask: ",$mask,"<BR>";
		
		return $mask;
	}
	
	/**
	 * Generate an RFC-compliant Message-ID
	 */
	static function generateMessageId() {
		$message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(mt_rand(), 10, 36), !empty($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
		return $message_id;
	}
	
	/**
	 * Translates the string version of a group/bucket combo into their 
	 * respective IDs.
	 * 
	 * @todo This needs a better name and home
	 */
	static function translateTeamCategoryCode($code) {
		$t_or_c = substr($code,0,1);
		$t_or_c_id = intval(substr($code,1));
		
		if($t_or_c=='c') {
			$categories = DAO_Bucket::getAll();
			$team_id = $categories[$t_or_c_id]->team_id;
			$category_id = $t_or_c_id; 
		} else {
			$team_id = $t_or_c_id;
			$category_id = 0;
		}
		
		return array($team_id, $category_id);
	}
	
	/**
	 * Looks up an e-mail address using a revolving cache.  This is helpful 
	 * in situations where you may look up the same e-mail address multiple 
	 * times (reports, audit log, views) and you don't want to waste code 
	 * filtering out dupes.
	 * 
	 * @param string $address The e-mail address to look up
	 * @param bool $create Should the address be created if not found?
	 * @return Model_Address The address object or NULL 
	 * 
	 * @todo [JAS]: Move this to a global cache/hash registry
	 */
	static public function hashLookupAddress($email, $create=false) {
	    static $hash_address_to_id = array();
	    static $hash_hits = array();
	    static $hash_size = 0;
	    
	    if(isset($hash_address_to_id[$email])) {
	    	$return = $hash_address_to_id[$email];
	    	
	        @$hash_hits[$email] = intval($hash_hits[$email]) + 1;
	        $hash_size++;
	        
	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_address_to_id = array_intersect_key($hash_address_to_id,$hash_hits);
	            $hash_size = count($hash_address_to_id);
	        }
	        
	        return $return;
	    }
	    
	    $address = DAO_Address::lookupAddress($email, $create);
	    if(!empty($address)) {
	        $hash_address_to_id[$email] = $address;
	    }
	    return $address;
	}

	/**
	 * Looks up a ticket ID by the provided mask using a revolving cache.
	 * This is useful if you need to translate several ticket masks into 
	 * IDs where there may be a lot of redundancy (batches in the e-mail 
	 * parser, etc.)
	 * 
	 * @param string $mask The ticket mask to look up
	 * @return integer The ticket id, or NULL if not found
	 *  
	 * @todo [JAS]: Move this to a global cache/hash registry 
	 */
	static public function hashLookupTicketIdByMask($mask) {
	    static $hash_mask_to_id = array();
	    static $hash_hits = array();
	    static $hash_size = 0;
	    
	    if(isset($hash_mask_to_id[$mask])) {
	    	$return = $hash_mask_to_id[$mask];
	    	
	        @$hash_hits[$mask] = intval($hash_hits[$mask]) + 1;
	        $hash_size++;

	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_mask_to_id = array_intersect_key($hash_mask_to_id,$hash_hits);
	            $hash_size = count($hash_mask_to_id);
	        }
	        
	        return $return;
	    }
	    
	    $ticket_id = DAO_Ticket::getTicketIdByMask($mask);
	    if(!empty($ticket_id)) {
	        $hash_mask_to_id[$mask] = $ticket_id;
	    }
	    return $ticket_id;
	}
	
	/**
	 * Enter description here...
	 * [TODO] Move this into a better API holding place
	 *
	 * @param integer $group_id
	 * @param integer $ticket_id
	 * @param integer $only_rule_id
	 * @return Model_GroupInboxFilter[]|false
	 */
	static public function runGroupRouting($group_id, $ticket_id, $only_rule_id=0) {
		static $moveMap = array();
		$dont_move = false;
		
		if(false != ($matches = Model_GroupInboxFilter::getMatches($group_id, $ticket_id, $only_rule_id))) { /* @var $match Model_GroupInboxFilter */
			if(is_array($matches))
			foreach($matches as $idx => $match) {
				/* =============== Prevent recursive assignments =============
				* If we ever get into a situation where many rules are sending a ticket
				* back and forth between them, ignore the last move action in the chain  
				* which is trying to start over.
				*/
				if(isset($match->actions['move'])) { 
					if(!isset($moveMap[$ticket_id])) {
						$moveMap[$ticket_id] = array();
					} else {
						if(isset($moveMap[$ticket_id][$group_id])) {
							$dont_move = true;
						}
					}
					
					$moveMap[$ticket_id][$group_id] = $match->id;
				}
	
				// Stop any move actions if we're going to loop again
				if($dont_move) {
					unset($matches[$idx]->actions['move']);
				}
				
				// Run filter actions
				$match->run(array($ticket_id));
			}
		}
		
	    return $matches;
	}
	
	// [TODO] This probably has a better home
	public static function getHelpdeskSenders() {
		$cache = DevblocksPlatform::getCacheService();

		if(null === ($froms = $cache->load(self::CACHE_HELPDESK_FROMS))) {
			$froms = array();
			$settings = DevblocksPlatform::getPluginSettingsService();
			$group_settings = DAO_GroupSettings::getSettings();
			
			// Global sender
			$from = strtolower($settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM,CerberusSettingsDefaults::DEFAULT_REPLY_FROM));
			@$froms[$from] = $from;
			
			// Group senders
			if(is_array($group_settings))
			foreach($group_settings as $group_id => $gs) {
				@$from = strtolower($gs[DAO_GroupSettings::SETTING_REPLY_FROM]);
				if(!empty($from))
					@$froms[$from] = $from;
			}
			
			asort($froms);
			
			$cache->save($froms, self::CACHE_HELPDESK_FROMS);
		}
		
		return $froms;
	}
};

interface ISnippetContextToken {
	static function getValue($context, $context_values);
};

class CerberusSnippetContexts {
	const CONTEXT_ADDRESS = 'cerberusweb.snippets.address';
	const CONTEXT_BUCKET = 'cerberusweb.snippets.bucket';
	const CONTEXT_GROUP = 'cerberusweb.snippets.group';
	const CONTEXT_ORG = 'cerberusweb.snippets.org';
	const CONTEXT_TICKET = 'cerberusweb.snippets.ticket';
	const CONTEXT_WORKER = 'cerberusweb.snippets.worker';
	
	public static function getContext($context, $context_object, &$labels, &$values, $prefix=null, $nested=false) {
		switch($context) {
			case 'cerberusweb.snippets.address':
				self::_getAddressContext($context_object, $labels, $values, $prefix);
				break;
			case 'cerberusweb.snippets.bucket':
				self::_getBucketContext($context_object, $labels, $values, $prefix);
				break;
			case 'cerberusweb.snippets.group':
				self::_getGroupContext($context_object, $labels, $values, $prefix);
				break;
			case 'cerberusweb.snippets.org':
				self::_getOrganizationContext($context_object, $labels, $values, $prefix);
				break;
			case 'cerberusweb.snippets.ticket':
				self::_getTicketContext($context_object, $labels, $values, $prefix);
				break;
			case 'cerberusweb.snippets.worker':
				self::_getWorkerContext($context_object, $labels, $values, $prefix);
				break;
			default:
				break;
		}

		if(!$nested) {
			// Globals
			self::_merge(
				'global_',
				'(Global) ',
				array(
					'timestamp|date' => 'Current Date+Time',
				),
				array(
					'timestamp' => time(),
				),
				$labels,
				$values
			);
			
			// Plugin-provided tokens
			$token_extension_mfts = DevblocksPlatform::getExtensions('cerberusweb.snippet.token', false);
			foreach($token_extension_mfts as $mft) { /* @var $mft DevblocksExtensionManifest */
				@$token = $mft->params['token'];
				@$label = $mft->params['label'];
				@$contexts = $mft->params['contexts'][0];
				
				if(empty($token) || empty($label) || !is_array($contexts))
					continue;
	
				if(!isset($contexts[$context]))
					continue;
					
				if(null != ($ext = $mft->createInstance()) && $ext instanceof ISnippetContextToken) {
					/* @var $ext ISnippetContextToken */
					$value = $ext->getValue($context, $values);
					
					if(!empty($value)) {
						$labels['plugin_'.$token] = '(Plugin) '.$label;
						$values['plugin_'.$token] = $value;
					}
				}
			}
		}
		
		asort($labels);
		
		return null;
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
	private static function _merge($token_prefix, $label_prefix, $src_labels, $src_values, &$dst_labels, &$dst_values) {
		foreach($src_labels as $token => $label) {
			$dst_labels[$token_prefix.$token] = $label_prefix.$label; 
		}
		
		foreach($src_values as $token => $value) {
			$dst_values[$token_prefix.$token] = $src_values[$token];
		}

		return true;
	}
	
	/**
	 * 
	 * @param mixed $address
	 * @param array $token_labels
	 * @param array $token_values
	 */
	private static function _getAddressContext($address, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Email:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		
		// Polymorph
		if(is_numeric($address)) {
			$address = DAO_Address::get($address);
		} elseif($address instanceof Model_Address) {
			// It's what we want already.
		} elseif(is_string($address)) {
			$address = DAO_Address::getByEmail($address);
		} else {
			$address = null;
		}
			
		// Token labels
		$token_labels = array(
			'address' => $prefix.$translate->_('common.email'),
			'first_name' => $prefix.$translate->_('address.first_name'),
			'last_name' => $prefix.$translate->_('address.last_name'),
			'num_spam' => $prefix.$translate->_('address.num_spam'),
			'num_nonspam' => $prefix.$translate->_('address.num_nonspam'),
			'is_registered' => $prefix.$translate->_('address.is_registered'),
			'is_banned' => $prefix.$translate->_('address.is_banned'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Address token values
		if(null != $address) {
			if(!empty($address->email))
				$token_values['address'] = $address->email;
			if(!empty($address->first_name))
				$token_values['first_name'] = $address->first_name;
			if(!empty($address->last_name))
				$token_values['last_name'] = $address->last_name;
			$token_values['num_spam'] = $address->num_spam;
			$token_values['num_nonspam'] = $address->num_nonspam;
			$token_values['is_registered'] = $address->is_registered;
			$token_values['is_banned'] = $address->is_banned;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $address->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $address)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $address)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Email Org
		$org_id = (null != $address && !empty($address->contact_org_id)) ? $address->contact_org_id : null;
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_ORG, $org_id, $merge_token_labels, $merge_token_values, null, true);

		self::_merge(
			'org_',
			'',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);		
		
		return true;
	}
	
	/**
	 * 
	 * @param mixed $worker
	 * @param array $token_labels
	 * @param array $token_values
	 */
	private static function _getWorkerContext($worker, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Worker:';
			
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Worker::ID);
		
		// Polymorph
		if(is_numeric($worker)) {
			$worker = DAO_Worker::getAgent($worker);
		} elseif($worker instanceof Model_Worker) {
			// It's what we want already.
		} else {
			$worker = null;
		}
			
		// Token labels
		$token_labels = array(
			'first_name' => $prefix.$translate->_('worker.first_name'),
			'last_name' => $prefix.$translate->_('worker.last_name'),
			'title' => $prefix.$translate->_('worker.title'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['worker_custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Worker token values
		if(null != $worker) {
			if(!empty($worker->first_name))
				$token_values['first_name'] = $worker->first_name;
			if(!empty($worker->last_name))
				$token_values['last_name'] = $worker->last_name;
			if(!empty($worker->title))
				$token_values['title'] = $worker->title;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Worker::ID, $worker->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $worker)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $worker)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Worker email
		@$worker_email = !is_null($worker) ? $worker->email : null;
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_ADDRESS, $worker_email, $merge_token_labels, $merge_token_values, null, true);

		self::_merge(
			'address_',
			'',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);		
		
		return true;
	}
	
	/**
	 * 
	 * @param mixed $ticket
	 * @param array $token_labels
	 * @param array $token_values
	 */
	private static function _getTicketContext($ticket, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Ticket:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$workers = DAO_Worker::getAll();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		
		// Polymorph
		if(is_numeric($ticket)) {
			list($results, $null) = DAO_Ticket::search(
				array(),
				array(
					SearchFields_Ticket::TICKET_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID,'=',$ticket),
					// [TODO] Enforce worker privs
				),
				1,
				0,
				null,
				null,
				false
			);
			
			if(!empty($results))
				$ticket = array_shift($results);
			else
				$ticket = null;
				
		} elseif(is_array($ticket)) {
			// It's what we want
		} else {
			$ticket = null;
		}
			
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('ticket.id'),
			'mask' => $prefix.$translate->_('ticket.mask'),
			'subject' => $prefix.$translate->_('ticket.subject'),
			'next_worker_id' => $prefix.$translate->_('ticket.next_worker'). ' ID',
			'first_wrote' => $prefix.$translate->_('ticket.first_wrote'),
			'last_wrote' => $prefix.$translate->_('ticket.last_wrote'),
			'created|date' => $prefix.$translate->_('ticket.created'),
			'updated|date' => $prefix.$translate->_('ticket.updated'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Ticket token values
		if(null != $ticket) {
			$token_values['id'] = $ticket[SearchFields_Ticket::TICKET_ID];
			$token_values['mask'] = $ticket[SearchFields_Ticket::TICKET_MASK];
			$token_values['subject'] = $ticket[SearchFields_Ticket::TICKET_SUBJECT];
			$token_values['next_worker_id'] = $ticket[SearchFields_Ticket::TICKET_NEXT_WORKER_ID];
			$token_values['first_wrote'] = $ticket[SearchFields_Ticket::TICKET_FIRST_WROTE];
			$token_values['last_wrote'] = $ticket[SearchFields_Ticket::TICKET_LAST_WROTE];
			$token_values['created'] = $ticket[SearchFields_Ticket::TICKET_CREATED_DATE];
			$token_values['updated'] = $ticket[SearchFields_Ticket::TICKET_UPDATED_DATE];
			$token_values['custom'] = array();
			
			// Custom fields
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket[SearchFields_Ticket::TICKET_ID]));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $ticket)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $ticket)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}

		// Group
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_GROUP, $ticket[SearchFields_Ticket::TICKET_TEAM_ID], $merge_token_labels, $merge_token_values, '', true);

		self::_merge(
			'group_',
			'Ticket:Group:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Bucket
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_BUCKET, $ticket[SearchFields_Ticket::TICKET_CATEGORY_ID], $merge_token_labels, $merge_token_values, '', true);

		self::_merge(
			'bucket_',
			'Ticket:Bucket:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Current worker
		$active_worker = CerberusApplication::getActiveWorker();
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_WORKER, $active_worker, $merge_token_labels, $merge_token_values, '', true);

		self::_merge(
			'worker_',
			'Current:Worker:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Next worker
		$next_worker_id = $ticket[SearchFields_Ticket::TICKET_NEXT_WORKER_ID];
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_WORKER, $next_worker_id, $merge_token_labels, $merge_token_values, '', true);

		self::_merge(
			'assignee_',
			'Assignee:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// First wrote
		$first_wrote_id = $ticket[SearchFields_Ticket::TICKET_FIRST_WROTE_ID];
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_ADDRESS, $first_wrote_id, $merge_token_labels, $merge_token_values, 'Sender:', true);
		
		self::_merge(
			'initial_sender_',
			'Initial:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		
		);

		// Last wrote
		@$last_wrote_id = $ticket[SearchFields_Ticket::TICKET_LAST_WROTE_ID];
		$merge_token_labels = array();
		$merge_token_values = array();
		self::getContext(self::CONTEXT_ADDRESS, $last_wrote_id, $merge_token_labels, $merge_token_values, 'Sender:', true);

		self::_merge(
			'latest_sender_',
			'Latest:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Plugin-provided tokens
		$token_extension_mfts = DevblocksPlatform::getExtensions('cerberusweb.template.token', false);
		foreach($token_extension_mfts as $mft) { /* @var $mft DevblocksExtensionManifest */
			@$token = $mft->params['token'];
			@$label = $mft->params['label'];
			@$bind = $mft->params['bind'][0];
			
			if(empty($token) || empty($label) || !is_array($bind))
				continue;

			if(!isset($bind['ticket']))
				continue;
				
			if(null != ($ext = $mft->createInstance()) && $ext instanceof ITemplateToken_Ticket) {
				/* @var $ext ITemplateToken_Signature */
				$value = $ext->getTicketTokenValue($worker);
				
				if(!empty($value)) {
					$token_labels[$token] = $label;
					$token_values[$token] = $value;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * 
	 * @param mixed $org
	 * @param array $token_labels
	 * @param array $token_values
	 */
	private static function _getOrganizationContext($org, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Org:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);

		// Polymorph
		if(is_numeric($org)) {
			$org = DAO_ContactOrg::get($org);
		} elseif($org instanceof Model_ContactOrg) {
			// It's what we want already.
		} else {
			$org = null;
		}
		
		// Token labels
		$token_labels = array(
			'name' => $prefix.$translate->_('contact_org.name'),
			'city' => $prefix.$translate->_('contact_org.city'),
			'country' => $prefix.$translate->_('contact_org.country'),
			'created' => $prefix.$translate->_('contact_org.created'),
			'phone' => $prefix.$translate->_('contact_org.phone'),
			'postal' => $prefix.$translate->_('contact_org.postal'),
			'province' => $prefix.$translate->_('contact_org.province'),
			'street' => $prefix.$translate->_('contact_org.street'),
			'website' => $prefix.$translate->_('contact_org.website'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Org token values
		if($org) {
			$token_values['name'] = $org->name;
			$token_values['created'] = $org->created;
			if(!empty($org->city))
				$token_values['city'] = $org->city;
			if(!empty($org->county))
				$token_values['country'] = $org->country;
			if(!empty($org->phone))
				$token_values['phone'] = $org->phone;
			if(!empty($org->postal))
				$token_values['postal'] = $org->postal;
			if(!empty($org->province))
				$token_values['province'] = $org->province;
			if(!empty($org->street))
				$token_values['street'] = $org->street;
			if(!empty($org->website))
				$token_values['website'] = $org->website;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $org->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $org)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $org)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}

		return true;
	}
	
	/**
	 * 
	 * @param mixed $group
	 * @param array $token_labels
	 * @param array $token_values
	 */
	private static function _getGroupContext($group, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Group:';
		
		$translate = DevblocksPlatform::getTranslationService();
		//$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);

		// Polymorph
		if(is_numeric($group)) {
			$group = DAO_Group::getTeam($group); 
		} elseif($group instanceof Model_Group) {
			// It's what we want already.
		} else {
			$group = null;
		}
		/* @var $group Model_Group */
		
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
		);
		
//		if(is_array($fields))
//		foreach($fields as $cf_id => $field) {
//			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
//		}

		// Token values
		$token_values = array();
		
		// Group token values
		if($group) {
			$token_values['id'] = $group->id;
			$token_values['name'] = $group->name;
			//$token_values['custom'] = array();
			
//			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $org->id));
//			if(is_array($field_values) && !empty($field_values)) {
//				foreach($field_values as $cf_id => $cf_val) {
//					if(!isset($fields[$cf_id]))
//						continue;
//					
//					// The literal value
//					if(null != $org)
//						$token_values['custom'][$cf_id] = $cf_val;
//					
//					// Stringify
//					if(is_array($cf_val))
//						$cf_val = implode(', ', $cf_val);
//						
//					if(is_string($cf_val)) {
//						if(null != $org)
//							$token_values['custom_'.$cf_id] = $cf_val;
//					}
//				}
//			}
		}

		return true;
	}
	
	/**
	 * 
	 * @param mixed $bucket
	 * @param array $token_labels
	 * @param array $token_values
	 */
	private static function _getBucketContext($bucket, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Bucket:';
		
		$translate = DevblocksPlatform::getTranslationService();
		//$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);

		// Polymorph
		if(is_numeric($bucket)) {
			$bucket = DAO_Bucket::get($bucket); 
		} elseif($bucket instanceof Model_Bucket) {
			// It's what we want already.
		} else {
			$bucket = null;
		}
		/* @var $bucket Model_Bucket */
		
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'name|default(\'Inbox\')' => $prefix.$translate->_('common.name'),
		);
		
//		if(is_array($fields))
//		foreach($fields as $cf_id => $field) {
//			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
//		}

		// Token values
		$token_values = array();
		
		// Bucket token values
		if($bucket) {
			$token_values['id'] = $bucket->id;
			$token_values['name'] = $bucket->name;
			//$token_values['custom'] = array();
			
//			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $org->id));
//			if(is_array($field_values) && !empty($field_values)) {
//				foreach($field_values as $cf_id => $cf_val) {
//					if(!isset($fields[$cf_id]))
//						continue;
//					
//					// The literal value
//					if(null != $org)
//						$token_values['custom'][$cf_id] = $cf_val;
//					
//					// Stringify
//					if(is_array($cf_val))
//						$cf_val = implode(', ', $cf_val);
//						
//					if(is_string($cf_val)) {
//						if(null != $org)
//							$token_values['custom_'.$cf_id] = $cf_val;
//					}
//				}
//			}
		}

		return true;
	}
		
};

class CerberusLicense {
	/**
	 * @return array
	 */
	public static function getInstance() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		@$license = json_decode($settings->get('cerberusweb.core',CerberusSettings::LICENSE,array()),true);
		if(empty($license) || !is_array($license))
			$license = array();
		return $license;
	}

	/*
	 * Please purchase a legitimate license and help support the project.
	 * http://www.cerberusweb.com/
	 */
	public static function validate($key, $company, $email) {
		/*  																																																																																																																														*/try{foreach(array('L0tleTogKC4qKS8='=>'s','L0NyZWF0ZWQ6ICguKikv'=>'c','L1VwZGF0ZWQ6ICguKikv'=>'u','L0V4cGlyZXM6ICguKikv'=>'e','L1dvcmtlcnM6ICguKikv'=>'w') as $k=>$v)@preg_match(base64_decode($k),$key,$matches)?@$$v=trim($matches[1]):null;$r=array();@$w=intval($w);@$cp=base64_decode('Y29tcGFueQ==');@$em=base64_decode('ZW1haWw=');@$cd=preg_replace('/[^A-Z0-9]/','',$s);@$l=explode('-',$e);@$e=gmmktime(0,0,0,$l[1],$l[2],$l[0]);@$l=explode('-',$c);@$c=gmmktime(0,0,0,$l[1],$l[2],$l[0]);@$l=explode('-',$u);@$u=gmmktime(0,0,0,$l[1],$l[2],$l[0]);@$h=str_split(strtoupper(sha1(sha1('cerb5').sha1($$cp).sha1($$em).sha1(intval($w)).sha1($c).sha1($e))),1);if(0==@strcasecmp(sprintf("%02X",strlen($$cp)+intval($w)),substr($cd,3,2))&&@intval(hexdec(substr($cd,5,1))==@intval(bindec(sprintf("%d%d%d%d",(182<=gmdate('z',$e))?1:0,(5==gmdate('w',$e))?1:0,('th'==gmdate('S',$e))?1:0,(1==gmdate('w',$e))?1:0))))&&0==@strcasecmp($h[hexdec(substr($cd,1,2))-@hexdec(substr($cd,0,1))],substr($cd,0,1)))@$r=array(base64_decode('a2V5')=>$s,base64_decode('Y3JlYXRlZA==')=>$c,base64_decode('dXBkYXRlZA==')=>$u,base64_decode('ZXhwaXJlcw==')=>$e,@$cp=>$$cp,@$em=>$$em,base64_decode('d29ya2Vycw==')=>intval($w));return $r;}catch(Exception $e){return array();}/*
		 * [TODO] This should probably do a little more checking
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
				'key'     => (list($k,$v)=explode(":",$lines[1]))?trim($v):null,
				'created' => (list($k,$v)=explode(":",$lines[2]))?trim($v):null,
				'updated' => (list($k,$v)=explode(":",$lines[3]))?trim($v):null,
				'expires' => (list($k,$v)=explode(":",$lines[4]))?trim($v):null,
				'workers' => (list($k,$v)=explode(":",$lines[5]))?trim($v):null
			)
			: array();
	}
};

class CerberusSettings {
	const DEFAULT_REPLY_FROM = 'default_reply_from'; 
	const DEFAULT_REPLY_PERSONAL = 'default_reply_personal'; 
	const DEFAULT_SIGNATURE = 'default_signature'; 
	const DEFAULT_SIGNATURE_POS = 'default_signature_pos'; 
	const HELPDESK_TITLE = 'helpdesk_title'; 
	const HELPDESK_LOGO_URL = 'helpdesk_logo_url'; 
	const SMTP_HOST = 'smtp_host'; 
	const SMTP_AUTH_ENABLED = 'smtp_auth_enabled'; 
	const SMTP_AUTH_USER = 'smtp_auth_user'; 
	const SMTP_AUTH_PASS = 'smtp_auth_pass'; 
	const SMTP_PORT = 'smtp_port'; 
	const SMTP_ENCRYPTION_TYPE = 'smtp_enc';
	const SMTP_MAX_SENDS = 'smtp_max_sends';
	const SMTP_TIMEOUT = 'smtp_timeout';
	const ATTACHMENTS_ENABLED = 'attachments_enabled'; 
	const ATTACHMENTS_MAX_SIZE = 'attachments_max_size'; 
	const PARSER_AUTO_REQ = 'parser_autoreq'; 
	const PARSER_AUTO_REQ_EXCLUDE = 'parser_autoreq_exclude'; 
	const AUTHORIZED_IPS = 'authorized_ips';
	const LICENSE = 'license_json';
	const ACL_ENABLED = 'acl_enabled';
};

class CerberusSettingsDefaults {
	const DEFAULT_REPLY_FROM = 'do-not-reply@localhost'; //$_SERVER['SERVER_ADMIN'] 
	const DEFAULT_REPLY_PERSONAL = ''; 
	const DEFAULT_SIGNATURE = ''; 
	const DEFAULT_SIGNATURE_POS = 0; 
	const HELPDESK_TITLE = 'Cerberus Helpdesk :: Team-based E-mail Management'; 
	const SMTP_HOST = 'localhost'; 
	const SMTP_AUTH_ENABLED = 0; 
	const SMTP_AUTH_USER = ''; 
	const SMTP_AUTH_PASS = ''; 
	const SMTP_PORT = 25; 
	const SMTP_ENCRYPTION_TYPE = 'None';
	const SMTP_MAX_SENDS = 20;
	const SMTP_TIMEOUT = 30;
	const ATTACHMENTS_ENABLED = 1; 
	const ATTACHMENTS_MAX_SIZE = 10; 
	const PARSER_AUTO_REQ = 0; 
	const PARSER_AUTO_REQ_EXCLUDE = ''; 
	const AUTHORIZED_IPS = '';
	const ACL_ENABLED = 0;
};

// [TODO] This gets called a lot when it happens after the registry cache
class C4_DevblocksExtensionDelegate implements DevblocksExtensionDelegate {
	static $_worker = null;
	
	static function shouldLoadExtension(DevblocksExtensionManifest $extension_manifest) {
		// Always allow core
		if("cerberusweb.core" == $extension_manifest->plugin_id)
			return true;
			
		// [TODO] This should limit to just things we can run with no session
		// Community Tools, Cron/Update.  They are still limited by their own
		// isVisible() otherwise.
		if(null == self::$_worker) {
			if(null == (self::$_worker = CerberusApplication::getActiveWorker()))
				return true;
		}
		
		return self::$_worker->hasPriv('plugin.'.$extension_manifest->plugin_id);
	}
};
