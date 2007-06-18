<?php
define("APP_BUILD", 167);
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

class CerberusApplication extends DevblocksApplication {
	const INDEX_TICKETS = 'tickets';
		
	const VIEW_SEARCH = 'search';
	const VIEW_MY_TICKETS = 'teamwork_my';
	const VIEW_TEAM_TICKETS = 'teamwork_team';
//	const VIEW_TEAM_TASKS = 'teamwork_tasks';
	
	const CACHE_SETTINGS_DAO = 'ch_settings_dao';
	
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
		return $visit->getWorker();
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
	
//    // [JAS]: [TODO] Cache/Kill this
//	static function getPages() {
//		$pages = array();
//		$extModules = DevblocksPlatform::getExtensions("cerberusweb.page");
//		foreach($extModules as $mod) { /* @var $mod DevblocksExtensionManifest */
//			$instance = $mod->createInstance(); /* @var $instance CerberusPageExtension */
//			if($instance instanceof DevblocksExtension && $instance->isVisible())
//				$pages[$mod->id] = $instance;
//		}
//		return $pages;
//	}	
	
	// [TODO] Move to a FormHelper service?
	static function parseCrlfString($string) {
		// Make linefeeds uniform (CR to LF)
//		$string = str_replace("\r","\n",$string);
		
		// Condense repeat LF into a single LF
//		$string = preg_replace('#\n+#', '\n', $string);
		
		// 
		$parts = split("[\r\n]", $string);
		
		// Remove any empty tokens
		foreach($parts as $idx => $part) {
			$parts[$idx] = trim($part);
			if(empty($parts[$idx])) 
				unset($parts[$idx]);
		}
		
		return $parts;
	}
	
	/**
	 * Takes a comma-separated value string and returns an array of tokens.
	 * [TODO] Move to a FormHelper service?
	 * 
	 * @param string $string
	 * @return array
	 */
	static function parseCsvString($string) {
		$tokens = explode(',', $string);

		if(!is_array($tokens))
			return array();
		
		foreach($tokens as $k => $v) {
			$tokens[$k] = trim($v);
		}
		
		return $tokens;
	}
	
	static function stripHTML($str) {
	    static $ENT_REPLACE = null;
	    static $ENT_WITH = null;
	    
	    // [TODO] Get a better entity list with ASCII replacements
	    
		// [JAS]: HTML 2.0 Entity Replacement
	    if(is_null($ENT_REPLACE)) {
		    $ENT_REPLACE = array(
				'&lt;',
				'&gt;',
				'&amp;',
				'&quot;',
				'&nbsp;',
//				'&iexcl;',
//				'&cent;',
//				'&pound;',
//				'&curren;',
//				'&yen;',
//				'&brvbar;',
//				'&sect;',
//				'&uml;',
//				'&copy;',
//				'&ordf;',
//				'&laquo;',
//				'&not;',
//				'&shy;',
//				'&reg;',
//				'&macr;',
//				'&deg;',
//				'&plusmn;',
//				'&sup2;',
//				'&sup3;',
//				'&acute;',
//				'&micro;',
//				'&para;',
//				'&middot;',
//				'&cedil;',
//				'&sup1;',
//				'&ordm;',
//				'&raquo;',
//				'&frac14;',
//				'&frac12;',
//				'&frac34;',
//				'&iquest;',
//				'&Agrave;',
//				'&Acute;',
//				'&Acirc;',
//				'&Atilde;',
//				'&Auml;',
//				'&Aring;',
//				'&AElig;',
//				'&Ccedil;',
//				'&Egrave;',
//				'&Eacute;',
//				'&Ecirc;',
//				'&Euml;',
//				'&Igrave;',
//				'&Iacute;',
//				'&Icirc;',
//				'&Iuml;',
//				'&ETH;',
//				'&Ntilde;',
//				'&Ograve;',
//				'&Oacute;',
//				'&Ocirc;',
//				'&Otilde;',
//				'&Ouml;',
//				'&times;',
//				'&Oslash;',
//				'&Ugrave;',
//				'&Uacute;',
//				'&Ucirc;',
//				'&Uuml;',
//				'&Yacute;',
//				'&THORN;',
//				'&szlig;',
//				'&agrave;',
//				'&aacute;',
//				'&acirc;',
//				'&atilde;',
//				'&auml;',
//				'&aring;',
//				'&aelig;',
//				'&ccedil;',
//				'&egrave;',
//				'&eacute;',
//				'&ecirc;',
//				'&euml;',
//				'&igrave;',
//				'&iacute;',
//				'&icirc;',
//				'&iuml;',
//				'&eth;',
//				'&ntilde;',
//				'&ograve;',
//				'&oacute;',
//				'&ocirc;',
//				'&otilde;',
//				'&ouml;',
//				'&divide;',
//				'&oslash;',
//				'&ugrave;',
//				'&uacute;',
//				'&ucirc;',
//				'&uuml;',
//				'&yacute;',
//				'&thorn;',
//				'&yuml'
			);
			
			$ENT_WITH = array(
				'<;',
				'>;',
				'&;',
				'";',
				chr(32),
//				chr(161),
//				chr(162),
//				chr(163),
//				chr(164),
//				chr(165),
//				chr(166),
//				chr(167),
//				chr(168),
//				chr(169),
//				chr(170),
//				chr(171),
//				chr(172),
//				chr(173),
//				chr(174),
//				chr(175),
//				chr(176),
//				chr(177),
//				chr(178),
//				chr(179),
//				chr(180),
//				chr(181),
//				chr(182),
//				chr(183),
//				chr(184),
//				chr(185),
//				chr(186),
//				chr(187),
//				chr(188),
//				chr(189),
//				chr(190),
//				chr(191),
//				chr(192),
//				chr(193),
//				chr(194),
//				chr(195),
//				chr(196),
//				chr(197),
//				chr(198),
//				chr(199),
//				chr(200),
//				chr(201),
//				chr(202),
//				chr(203),
//				chr(204),
//				chr(205),
//				chr(206),
//				chr(207),
//				chr(208),
//				chr(209),
//				chr(210),
//				chr(211),
//				chr(212),
//				chr(213),
//				chr(214),
//				chr(215),
//				chr(216),
//				chr(217),
//				chr(218),
//				chr(219),
//				chr(220),
//				chr(221),
//				chr(222),
//				chr(223),
//				chr(224),
//				chr(225),
//				chr(226),
//				chr(227),
//				chr(228),
//				chr(229),
//				chr(230),
//				chr(231),
//				chr(232),
//				chr(233),
//				chr(234),
//				chr(235),
//				chr(236),
//				chr(237),
//				chr(238),
//				chr(239),
//				chr(240),
//				chr(241),
//				chr(242),
//				chr(243),
//				chr(244),
//				chr(245),
//				chr(246),
//				chr(247),
//				chr(248),
//				chr(249),
//				chr(250),
//				chr(251),
//				chr(252),
//				chr(253),
//				chr(254),
//				chr(255)
			);
		    
			for($c=1;$c<256;$c++) { 
			    array_push($ENT_REPLACE,'&#' . sprintf("%03d",$c) . ';');
			    array_push($ENT_WITH, chr($c));
			}
	    }
		
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

			$str = str_replace($ENT_REPLACE,$ENT_WITH,$str);
		}

		$str = preg_replace('# +#', ' ', $str);
		
//		$lines = split("\n", $str);
//		$str = '';
//		
//		foreach($lines as $line) {
//		    $str .= ltrim($line);
//		}
//		
//		unset($lines);
		
		return $str;
	}
	    
	/**
	 * Enter description here...
	 *
	 * @return a unique ticket mask as a string
	 */
	static function generateTicketMask($pattern = "LLL-NNNNN-NNN") {
		$letters = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$numbers = "1234567890";
//		$pattern = "Y-M-D-LLLL";

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
					case 'Y':
						$mask .= date('Y');
						break;
					case 'M':
						$mask .= date('n');
						break;
					case 'D':
						$mask .= date('j');
						break;
					default:
						$mask .= $byte;
						break;
				}
			}
		} while(null != DAO_Ticket::getTicketByMask($mask));
		
//		echo "Generated unique mask: ",$mask,"<BR>";
		
		return $mask;
	}
	
	static function generateMessageId() {
		$message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(rand(), 10, 36), !empty($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
		return $message_id;
	}
	
	// [TODO] Rethink
	static function getDashboardGlobalActions() {
		return array();
	}
	
	// [TODO] This needs a better name and home (and hell, while at it, implementation)
	static function translateTeamCategoryCode($code) {
		$t_or_c = substr($code,0,1);
		$t_or_c_id = intval(substr($code,1));
		
		if($t_or_c=='c') {
			$categories = DAO_Category::getList(); // [TODO] Cache
			$team_id = $categories[$t_or_c_id]->team_id;
			$category_id = $t_or_c_id; 
		} else {
			$team_id = $t_or_c_id;
			$category_id = 0;
		}
		
		return array($team_id, $category_id);
	}
	
	// ***************** DUMMY [TODO] Move to Model?  Combine with search fields?
	static function getDashboardViewColumns() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_MASK,$translate->_('ticket.id')),
//			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_PRIORITY,$translate->_('ticket.priority')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_LAST_WROTE,$translate->_('ticket.last_wrote')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_FIRST_WROTE,$translate->_('ticket.first_wrote')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_CREATED_DATE,$translate->_('ticket.created')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_UPDATED_DATE,$translate->_('ticket.updated')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TEAM_NAME,$translate->_('common.team')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::CATEGORY_NAME,$translate->_('common.bucket')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_DUE_DATE,$translate->_('ticket.due')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_SPAM_SCORE,$translate->_('ticket.spam_score')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_NEXT_ACTION,$translate->_('ticket.next_action')),
//			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_TASKS,$translate->_('common.tasks')),
			);
	}
	
	// ***************** DUMMY
	
};

/**
 * [TODO] This goes in the session
 */
class CerberusStaticViewManager {
	private $views = array();
	
	public function exists($view_label) {
		return isset($this->views[$view_label]);
	}
	
	public function &getView($view_label) {
		if(!$this->exists($view_label)) return NULL;
		
		return $this->views[$view_label];
	}
	
	public function setView($view_label, $view) {
		$this->views[$view_label] = $view;
	}
};

class CerberusSettings {
	const DEFAULT_TEAM_ID = 'default_team_id'; 
	const DEFAULT_REPLY_FROM = 'default_reply_from'; 
	const DEFAULT_REPLY_PERSONAL = 'default_reply_personal'; 
	const HELPDESK_TITLE = 'helpdesk_title'; 
	const SMTP_HOST = 'smtp_host'; 
	const SMTP_AUTH_USER = 'smtp_auth_user'; 
	const SMTP_AUTH_PASS = 'smtp_auth_pass'; 
	const ATTACHMENTS_ENABLED = 'attachments_enabled'; 
	const ATTACHMENTS_MAX_SIZE = 'attachments_max_size'; 
	
	private static $instance = null;
	
	private $settings = array( // defaults
		self::DEFAULT_TEAM_ID => 0,
		self::DEFAULT_REPLY_FROM => '',
		self::DEFAULT_REPLY_PERSONAL => '',
		self::HELPDESK_TITLE => 'Cerberus Helpdesk :: Team-based E-mail Management',
		self::SMTP_HOST => 'localhost',
		self::SMTP_AUTH_USER => '',
		self::SMTP_AUTH_PASS => '',
		self::ATTACHMENTS_ENABLED => 1,
		self::ATTACHMENTS_MAX_SIZE => 10, // MB
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
