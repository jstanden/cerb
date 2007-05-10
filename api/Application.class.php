<?php
define("APP_BUILD", 142);

include_once(APP_PATH . "/api/ClassLoader.php");
include_once(APP_PATH . "/api/DAO.class.php");
include_once(APP_PATH . "/api/Model.class.php");
include_once(APP_PATH . "/api/Extension.class.php");

class CerberusApplication extends DevblocksApplication {
	const INDEX_TICKETS = 'tickets';
		
	const VIEW_SEARCH = 'search';
	const VIEW_MY_TICKETS = 'teamwork_my';
	const VIEW_TEAM_TICKETS = 'teamwork_team';
	const VIEW_TEAM_TASKS = 'teamwork_tasks';
	
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
	
    // [JAS]: [TODO] Cache/Kill this
	static function getPages() {
		$pages = array();
		$extModules = DevblocksPlatform::getExtensions("cerberusweb.page");
		foreach($extModules as $mod) { /* @var $mod DevblocksExtensionManifest */
			$instance = $mod->createInstance(); /* @var $instance CerberusPageExtension */
			if($instance instanceof DevblocksExtension && $instance->isVisible())
				$pages[$mod->id] = $instance;
		}
		return $pages;
	}	
	
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
//		$trashAction = new Model_DashboardViewAction();
//		$trashAction->id = 'trash';
//		$trashAction->dashboard_view_id = CerberusApplication::VIEW_MY_TICKETS;
//		$trashAction->name = 'Trash';
//		$trashAction->params = array(
//			'status' => CerberusTicketStatus::DELETED
//		);
//
//		$view_actions = array(
//			$trashAction->id => $trashAction,
//		);
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
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_STATUS,$translate->_('ticket.status')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_PRIORITY,$translate->_('ticket.priority')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_LAST_WROTE,$translate->_('ticket.last_wrote')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_FIRST_WROTE,$translate->_('ticket.first_wrote')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_CREATED_DATE,$translate->_('ticket.created')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_UPDATED_DATE,$translate->_('ticket.updated')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_DUE_DATE,$translate->_('ticket.due')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_SPAM_SCORE,$translate->_('ticket.spam_score')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TICKET_TASKS,$translate->_('common.tasks')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::TEAM_NAME,$translate->_('common.team')),
			new CerberusDashboardViewColumn(SearchFields_Ticket::CATEGORY_NAME,$translate->_('common.category')),
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
	const SAVE_FILE_PATH = 'save_file_path'; 
	const SMTP_HOST = 'smtp_host'; 
	const SMTP_AUTH_USER = 'smtp_auth_user'; 
	const SMTP_AUTH_PASS = 'smtp_auth_pass'; 
	
	private static $instance = null;
	
	private $settings = array( // defaults
		self::DEFAULT_TEAM_ID => 0,
		self::DEFAULT_REPLY_FROM => '',
		self::DEFAULT_REPLY_PERSONAL => '',
		self::HELPDESK_TITLE => 'Cerberus Helpdesk :: Team-based E-mail Management',
		self::SMTP_HOST => 'localhost',
		self::SMTP_AUTH_USER => '',
		self::SMTP_AUTH_PASS => '',
	);

	/**
	 * @return CerberusSettings
	 */
	private function __construct() {
	    // Defaults (dynamic)
	    $this->settings[self::SAVE_FILE_PATH] = realpath(APP_PATH . '/attachments').DIRECTORY_SEPARATOR;
	    
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

// [JAS]: [TODO] This probably isn't needed
class ToCriterion implements ICerberusCriterion {
	function getValue($rfcMessage) {
		return $rfcMessage->headers['to'];
	}
};
?>
