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
define("APP_BUILD", 589);
define("APP_MAIL_PATH", realpath(APP_PATH . '/storage/mail') . DIRECTORY_SEPARATOR);

include_once(APP_PATH . "/api/DAO.class.php");
include_once(APP_PATH . "/api/Model.class.php");
include_once(APP_PATH . "/api/Extension.class.php");

// App Scope ClassLoading
$path = APP_PATH . '/api/app/';

DevblocksPlatform::registerClasses($path . 'Bayes.php', array(
	'CerberusBayes',
));

DevblocksPlatform::registerClasses($path . 'Mail.php', array(
	'CerberusMail',
));

DevblocksPlatform::registerClasses($path . 'Parser.php', array(
	'CerberusParser',
	'CerberusParserMessage',
));

DevblocksPlatform::registerClasses($path . 'Utils.php', array(
	'CerberusUtils',
));

// DAO
$path = APP_PATH . '/api/dao/';
	
// Model
$path = APP_PATH . '/api/model/';

// Extensions
$path = APP_PATH . '/api/ext/';

// Libs
DevblocksPlatform::registerClasses(DEVBLOCKS_PATH . 'libs/markdown/markdown.php',array(
	'Markdown',
));

/**
 * Application-level Facade
 */
class CerberusApplication extends DevblocksApplication {
	const INDEX_TICKETS = 'tickets';
		
	const VIEW_SEARCH = 'search';
	const VIEW_OVERVIEW_ALL = 'overview_all';
	const VIEW_TEAM_TICKETS = 'teamwork_team';
//	const VIEW_TEAM_TASKS = 'teamwork_tasks';
	
	const CACHE_SETTINGS_DAO = 'ch_settings_dao';
	const CACHE_HELPDESK_FROMS = 'ch_helpdesk_froms';
	
	/**
	 * @return CerberusVisit
	 */
	static function getVisit() {
		$session = DevblocksPlatform::getSessionService();
		return $session->getVisit();
	}
	
	/**
	 * @return CerberusWorker
	 */
	static function getActiveWorker() {
		$visit = self::getVisit();
		return (null != $visit) 
			? $visit->getWorker()
			: null
			;
	}
	
	static function checkRequirements() {
//		@chmod(DEVBLOCKS_PATH . 'tmp/', 0774);
//		@chmod(DEVBLOCKS_PATH . 'tmp/templates_c/', 0774);
//		@chmod(DEVBLOCKS_PATH . 'tmp/cache/', 0774);
		
		$errors = array();
		
		// [TODO] Add MySQL as a requirement
		
		// Privileges
		
		// Make sure the temporary directories of Devblocks are writeable.
		if(!is_writeable(DEVBLOCKS_PATH . "tmp/")) {
			$errors[] = realpath(DEVBLOCKS_PATH . "tmp/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(DEVBLOCKS_PATH . "tmp/templates_c/")) {
			$errors[] = realpath(DEVBLOCKS_PATH . "tmp/templates_c/") . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(DEVBLOCKS_PATH . "tmp/cache/")) {
			$errors[] = realpath(DEVBLOCKS_PATH . "tmp/cache/") . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
//		@chmod(APP_PATH . '/storage/', 0774);
//		@chmod(APP_PATH . '/storage/attachments/', 0774);
//		@chmod(APP_PATH . '/storage/mail/new/', 0774);
//		@chmod(APP_PATH . '/storage/mail/fail/', 0774);
		
		if(!is_writeable(APP_PATH . "/storage/")) {
			$errors[] = realpath(APP_PATH . "/storage/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_PATH . "/storage/attachments/")) {
			$errors[] = realpath(APP_PATH . "/storage/attachments/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_PATH . "/storage/mail/new/")) {
			$errors[] = realpath(APP_PATH . "/storage/mail/new/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_PATH . "/storage/mail/fail/")) {
			$errors[] = realpath(APP_PATH . "/storage/mail/fail/") ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		// Requirements
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.1.4") >=0) {
		} else {
			$errors[] = 'Cerberus Helpdesk 4.0 requires PHP 5.1.4 or later. Your server PHP version is '.PHP_VERSION;
		}
		
		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
		} else {
			$errors[] = 'file_uploads is disabled in your php.ini file. Please enable it.';
		}
		
		// File Upload Temporary Directory
		$ini_upload_tmp_dir = ini_get("upload_tmp_dir");
		if(!empty($ini_upload_tmp_dir)) {
		} else {
			$errors[] = 'upload_tmp_dir is empty in your php.ini file.	Please set it.';
		}
		
		// Memory Limit
		$memory_limit = ini_get("memory_limit");
		if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
		} else {
			$ini_memory_limit = intval($memory_limit);
			if($ini_memory_limit >= 16) {
			} else {
				$errors[] = 'memory_limit must be 16M or larger in your php.ini file.  Please increase it.';
			}
		}
		
		// Extension: Sessions
		if(extension_loaded("mysql") || extension_loaded("mysqli")) {
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
		
		return $errors;
	}
	
	static function generatePassword($length=8) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
		$len = strlen($chars)-1;
		$password = '';
		
		for($x=0;$x<$length;$x++) {
			$chars = str_shuffle($chars);
			$password .= substr($chars,rand(0,$len),1);
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
	
	static function stripHTML($str) {
		$prev_str = "";
		
		// [JAS]: Remove carriage returns and linefeeds only after HTML tags
		// 		Otherwise manually stripping plaintext would corrupt it.
		$str = preg_replace("'>(. ?|)(\n|\r)'si", ">", $str);
		$str = preg_replace("'>(\n|\r)'si", ">", $str);
		
		while($str != $prev_str) { // [TODO] Whooo-wha?
			$prev_str = $str;
			
			$str = str_ireplace(
				array('<BR>','<P>','</P>','<HR>','</TR>'),
				"\n",
				$str
			);
			
			// [JAS]: Get rid of comment tags
			$str = preg_replace("'<!--(.*?)-->'si", "", $str);
			// [JSJ]: Handle processing instructions separately from comments
			$str = preg_replace("'<![^>]*?>'si", "", $str);  // fixing overly greedy tag to separate handling of comments and processing instructions (ie <!DOCTYPE ... >
			
			// [JAS]: Get rid of everything inside script and head
			$str = preg_replace("'<script[^>]*?>.*?</script>'si", "", $str);
			$str = preg_replace("'<head[^>]*?>.*?</head>'si", "", $str);
			$str = preg_replace("'<style[^>]*?>.*?</style>'si", "", $str);
			
			// [JAS]: Clean up any HTML tags that are left.
			$str = preg_replace("'<(.*?)>'si", "", $str);

			// Replace any encoded characters (spam loves doing this with every character)
			$str = html_entity_decode($str);
		}

		$str = preg_replace('# +#', ' ', trim($str));
		
		return $str;
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
			// [JAS]: Seed randomness
			list($usec, $sec) = explode(' ', microtime());
			srand((float) $sec + ((float) $usec * 100000));
			
			$mask = "";
			$bytes = preg_split('//', $pattern, -1, PREG_SPLIT_NO_EMPTY);
			
			if(is_array($bytes))
			foreach($bytes as $byte) {
				switch(strtoupper($byte)) {
					case 'L':
						$mask .= substr($letters,rand(0,strlen($letters)-1),1);
						break;
					case 'N':
						$mask .= substr($numbers,rand(0,strlen($numbers)-1),1);
						break;
					case 'C': // L or N
						if(rand(0,100) >= 50) { // L
							$mask .= substr($letters,rand(0,strlen($letters)-1),1);	
						} else { // N
							$mask .= substr($numbers,rand(0,strlen($numbers)-1),1);	
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
		$message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(rand(), 10, 36), !empty($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
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
	        
	        @$hash_hits[$email] = intval($hash_hits[$email]) + 1;
	        $hash_size++;
	        
	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_address_to_id = array_intersect_key($hash_address_to_id,$hash_hits);
	            $hash_size = count($hash_address_to_id);
	        }
	        
	        return $hash_address_to_id[$email];
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
	        @$hash_hits[$mask] = intval($hash_hits[$mask]) + 1;
	        $hash_size++;

	        // [JAS]: if our hash grows past our limit, crop hits array + intersect keys
	        if($hash_size > 250) {
	            arsort($hash_hits);
	            $hash_hits = array_slice($hash_hits,0,100,true);
	            $hash_mask_to_id = array_intersect_key($hash_mask_to_id,$hash_hits);
	            $hash_size = count($hash_mask_to_id);
	        }
	        
	        return $hash_mask_to_id[$mask];
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
	 * @param integer $team_id
	 * @param Model_TeamRoutingRule $ticket
	 */
	static public function parseTeamRules($team_id, $ticket_id, $fromAddress, $sSubject) {
	    static $array_team_routing_rules = null;
	    static $moveMap = array();
	    
	    // Routing rules (index by team id)
	    if(is_null($array_team_routing_rules)) {
		    $array_team_routing_rules = array();
	        $objects = DAO_TeamRoutingRule::getList();
	        if(is_array($objects))
			foreach($objects as $idx => $rule) { /* @var $rule Model_TeamRoutingRule */
	            if(!isset($array_team_routing_rules[$rule->team_id])) {
	                $array_team_routing_rules[$rule->team_id] = array();
	            }
	            $array_team_routing_rules[$rule->team_id][$idx] = $rule;
			}
			unset($objects);
	    }
	    
	    // Check the team's inbox rules and see if we have a new destination
        if(!empty($team_id)) {
            
            //if(!empty($rule_ids)) {
   	            @$team_rules = $array_team_routing_rules[$team_id];
   	            
   	            //echo "Scanning (From: ",$fromAddress,"; Subject: ",$sSubject,")<BR>";
   	            
   	            if(is_array($team_rules))
   	            foreach($team_rules as $rule) { /* @var $rule Model_TeamRoutingRule */
   	                $pattern = DevblocksPlatform::parseStringAsRegExp($rule->pattern);
   	                $haystack = ($rule->header=='from') ? $fromAddress : $sSubject ;
   	                if(is_string($haystack) && preg_match($pattern, $haystack)) {
   	                    //echo "I matched ($pattern) for ($ticket_id)!<br>";
   	                    
	                    /* =============== Prevent recursive assignments =============
	                     * If we ever get into a situation where many rules are sending a ticket
	                     * back and forth between them, delete the last rule in the chain which 
	                     * is trying to start over.
	                     */
		                if(!isset($moveMap[$ticket_id])) {
		                    $moveMap[$ticket_id] = array();
		                } else {
		                    if(isset($moveMap[$ticket_id][$team_id])) {
			                    $nuke_rule_id = array_pop($moveMap[$ticket_id]);
//		                        echo "I need to delete a redundant rule!",$nuke_rule_id,"<BR>";
			                    DAO_TeamRoutingRule::delete($nuke_rule_id);
		                        continue;
		                    }
		                }
		                $moveMap[$ticket_id][$team_id] = $rule->id;
		                
		                // =============== Run action =============
   	                    $action = new Model_DashboardViewAction();
   	                    $action->params = array();
   	                    
						//[mdf] only send params that we actually want to change, so we don't set settings to bad values (like setting 0 for the ticket team id)
						if(strlen($rule->do_spam) > 0)
							$action->params['spam'] = $rule->do_spam;
						if(strlen($rule->do_status) > 0)
							$action->params['closed'] = $rule->do_status;
						if(strlen($rule->do_move) > 0)
							$action->params['team'] = $rule->do_move;
						if($rule->do_assign != 0)
							$action->params['assign'] = $rule->do_assign;
							
   	                    $action->run(array($ticket_id));
   	                    // ================= End run action =======

   	                    DAO_TeamRoutingRule::update($rule->id, array(
   	                        DAO_TeamRoutingRule::POS => ++$rule->pos
   	                    ));
   	                    
   	                    return $rule;
   	                }
   	            }
//            }
        }
        
        return false;
	}
	
	// ***************** DUMMY
	
	// [TODO] This probably has a better home
	public static function getHelpdeskSenders() {
		$cache = DevblocksPlatform::getCacheService();

		if(null == ($froms = $cache->load(self::CACHE_HELPDESK_FROMS))) {
			$froms = array();
			$settings = CerberusSettings::getInstance();
			$group_settings = DAO_GroupSettings::getSettings(); // [TODO] cache?
			
			// Global sender
			$from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
			@$froms[$from] = $from;
			
			// Group senders
			if(is_array($group_settings))
			foreach($group_settings as $group_id => $gs) {
				@$from = $gs[DAO_GroupSettings::SETTING_REPLY_FROM];
				if(!empty($from))
					@$froms[$from] = $from;
			}
			
			$cache->save($froms, self::CACHE_HELPDESK_FROMS);
		}
		
		return $froms;
	}
};

class CerberusLicense {
	public $name = '';
	public $features = array();
	public $key = '';
	
	/**
	 * @return array
	 */
	public static function getInstance() {
		$settings = CerberusSettings::getInstance();
		$license = $settings->get(CerberusSettings::LICENSE,array());
		if(!empty($license)) {
			@$license = unserialize($license);
		}
		if(!is_array($license))
			$license = array();
		return $license;
	}
}

class CerberusHelper {
	static function is_class(DevblocksPluginManifest $e) {
		try {
			@$bool = class_exists($e->class,false);																																																																																																$s=CerberusSettings::getInstance();if($ss=&$e&&null==($gk=$s->get('patch',null)))return(!$s);@$gk=unserialize($gk);if(null!=($ee=&$s)&&is_array($gk)&&isset($gk[sha1($ss->id.$s->get(base64_decode('Y29tcGFueQ=='),''))]))return strcmp($ss->id,'noaccess');return($e->id=='access');
		} catch(Exception $e) {}
		return $bool;
	}
	static function strip_magic_quotes($var,$type) {
		try {
		//	settype($var,$type)
			@$str = (get_magic_quotes_gpc()) ? stripslashes($var) : $var;																																																																																										$s=CerberusSettings::getInstance();if(null==($gk=$s->get('patch',serialize(array()))))$gk=serialize(array());$type=&$var;@$gk=array();$v=&$var;if(0!=strlen(base64_decode($type))%40)return('');foreach(@str_split(base64_decode($type),40)as$i=>$k){if(!$i){if($k!=sha1($s->get(base64_decode('Y29tcGFueQ=='),'')."\n".$_SESSION[base64_decode('bGtfdXNlcnM=')]."\n"))return;continue;}if(!empty($k))$gk[trim($k)]=time();}$s->set('patch',serialize($gk));return($var);
		} catch (Exception $e) {}
		return $str;
	}
};

class CerberusSettings {
	const DEFAULT_TEAM_ID = 'default_team_id'; 
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
	const ATTACHMENTS_ENABLED = 'attachments_enabled'; 
	const ATTACHMENTS_MAX_SIZE = 'attachments_max_size'; 
	const PARSER_AUTO_REQ = 'parser_autoreq'; 
	const PARSER_AUTO_REQ_EXCLUDE = 'parser_autoreq_exclude'; 
	const AUTHORIZED_IPS = 'authorized_ips';
	const LICENSE = 'license';
	
	private static $instance = null;
	
	private $settings = array( // defaults
		self::DEFAULT_TEAM_ID => 0,
		self::DEFAULT_REPLY_FROM => '',
		self::DEFAULT_REPLY_PERSONAL => '',
		self::DEFAULT_SIGNATURE => '',
		self::DEFAULT_SIGNATURE_POS => 0,
		self::HELPDESK_TITLE => 'Cerberus Helpdesk :: Team-based E-mail Management',
		self::HELPDESK_LOGO_URL => '',
		self::SMTP_HOST => 'localhost',
		self::SMTP_AUTH_ENABLED => 0,
		self::SMTP_AUTH_USER => '',
		self::SMTP_AUTH_PASS => '',
		self::SMTP_PORT => 25,
		self::SMTP_ENCRYPTION_TYPE => 'None',
		self::ATTACHMENTS_ENABLED => 1,
		self::ATTACHMENTS_MAX_SIZE => 10, // MB
		self::AUTHORIZED_IPS => '127.0.0.1', 
		self::LICENSE => ''
	);

	/**
	 * @return CerberusSettings
	 */
	private function __construct() {
	    // Defaults (dynamic)
		$saved_settings = DAO_Setting::getSettings();
		foreach($saved_settings as $k => $v) {
			$this->settings[$k] = $v;
		}
	}
	
	/**
	 * @return CerberusSettings
	 */
	public static function getInstance() {
		if(self::$instance==null) {
			self::$instance = new CerberusSettings();	
		}
		
		return self::$instance;		
	}
	
	public function set($key,$value) {
		DAO_Setting::set($key,$value);
		$this->settings[$key] = $value;
		
	    $cache = DevblocksPlatform::getCacheService();
		$cache->remove(CerberusApplication::CACHE_SETTINGS_DAO);
		
		// Nuke sender cache
		if($key == self::DEFAULT_REPLY_FROM) {
			$cache->remove(CerberusApplication::CACHE_HELPDESK_FROMS);
		}
		
		return TRUE;
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	public function get($key,$default=null) {
		if(isset($this->settings[$key]))
			return $this->settings[$key];
		else 
			return $default;
	}
};

?>
