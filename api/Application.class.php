<?php
define("APP_BUILD", 108);

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
	
	static function writeDefaultHttpResponse($response) {
		$path = $response->path;

		// [JAS]: Ajax?
		if(empty($path))
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$mapping = DevblocksPlatform::getMappingRegistry();
		@$extension_id = $mapping[$path[0]];
		
		if(empty($visit))
			$extension_id = 'core.page.signin';
		
		if(empty($extension_id)) 
			$extension_id = 'core.page.tickets';
	
		$pages = CerberusApplication::getPages();
		$tpl->assign('pages',$pages);		
		
		$pageManifest = DevblocksPlatform::getExtension($extension_id);
		$page = $pageManifest->createInstance();
		$tpl->assign('page',$page);
		
		$settings = CerberusSettings::getInstance();
		$tpl->assign('settings', $settings);
				
		$tpl->assign('session', $_SESSION);
		$tpl->assign('visit', $visit);
		
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		$tpl->display('border.php');
	}
	
	static function getPages() {
		$pages = array();
		$extModules = DevblocksPlatform::getExtensions("cerberusweb.page");
		foreach($extModules as $mod) { /* @var $mod DevblocksExtensionManifest */
			$instance = $mod->createInstance(); /* @var $instance CerberusPageExtension */
			if(is_a($instance,'devblocksextension') && $instance->isVisible())
				$pages[] = $instance;
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
	
	// TODO: may need to also have an agent_id passed to it in the request, to identify the agent making the reply
	// [TODO] This needs to move into mail api/App functions and out of Application
	function sendMessage($type) {
		// variable loading
		@$id		= DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		@$content	= DevblocksPlatform::importGPC($_REQUEST['content']);
		@$priority	= DevblocksPlatform::importGPC($_REQUEST['priority']);	// DDH: TODO: if priority and/or status change, we need to update the
		@$status	= DevblocksPlatform::importGPC($_REQUEST['status']);		// ticket object.  not sure if we want to do that here or not.
		@$agent_id	= DevblocksPlatform::importGPC($_REQUEST['agent_id']);
		
		// object loading
		$message	= DAO_Ticket::getMessage($id);
		$ticket_id	= $message->ticket_id;
		$ticket		= DAO_Ticket::getTicket($ticket_id);
		$mailbox	= DAO_Mail::getMailbox($ticket->mailbox_id);
		$requesters	= DAO_Ticket::getRequestersByTicket($ticket_id);
		$mailMgr	= DevblocksPlatform::getMailService();
		$headers	= DAO_Mail::getHeaders($type, $ticket_id);
		
		$files = $_FILES['attachment'];
		// send email (if necessary)
		if ($type != CerberusMessageType::COMMENT) {
			// build MIME message if message has attachments
			if (is_array($files) && !empty($files)) {
				
				/*
				 * [JAS]: [TODO] If we're going to call platform libs directly we should just have
				 * the platform provide the functionality.
				 */
			//	require_once(DEVBLOCKS_PATH . '/libS/pear/mime.php');
				$mime_mail = new Mail_mime();
				$mime_mail->setTXTBody($content);
				foreach ($files['tmp_name'] as $idx => $file) {
					$mime_mail->addAttachment($files['tmp_name'][$idx], $files['type'][$idx], $files['name'][$idx]);
				}
				
				$email_body = $mime_mail->get();
				$email_headers = $mime_mail->headers($headers);
			} else {
				$email_body = $content;
				$email_headers = $headers;
			}
			
			$mail_result =& $mailMgr->send('mail.webgroupmedia.com', $headers['x-rcpt'], $email_headers, $email_body); // DDH: TODO: this needs to pull the servername from a config, not hardcoded.
			if ($mail_result !== true) die("Error message was: " . $mail_result->getMessage());
		}
		
		// TODO: create DAO object for Agent, be able to pull address by having agent id.
//		$headers['From'] = $agent_address->personal . ' <' . $agent_address->email . '>';
//		$message_id = DAO_Ticket::createMessage($ticket_id,$type,gmmktime(),$agent_id,$headers,$content);
		$message_id = DAO_Ticket::createMessage($ticket_id,$type,gmmktime(),1,$headers,$content);
		
		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			$settings = CerberusSettings::getInstance();
			$attachmentlocation = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		
			foreach ($files['tmp_name'] as $idx => $file) {
				copy($files['tmp_name'][$idx],$attachmentlocation.$message_id.$idx);
				DAO_Ticket::createAttachment($message_id, $files['name'][$idx], $message_id.$idx);
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$id)));
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
//		$spamAction = new Model_DashboardViewAction();
//		$spamAction->id = 'spam';
//		$spamAction->dashboard_view_id = CerberusApplication::VIEW_MY_TICKETS;
//		$spamAction->name = 'Report Spam';
//		$spamAction->params = array(
//			'spam' => CerberusTicketSpamTraining::SPAM
//		);
//		
//		$view_actions = array(
//			$releaseAction->id => $releaseAction,
//			$trashAction->id => $trashAction,
//			$spamAction->id => $spamAction
//		);
		
//		return $view_actions;
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
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_MASK,$translate->_('ticket.id')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_STATUS,$translate->_('ticket.status')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_PRIORITY,$translate->_('ticket.priority')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_LAST_WROTE,$translate->_('ticket.last_wrote')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_FIRST_WROTE,$translate->_('ticket.first_wrote')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_CREATED_DATE,$translate->_('ticket.created')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_UPDATED_DATE,$translate->_('ticket.updated')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_SPAM_SCORE,$translate->_('ticket.spam_score')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_TASKS,$translate->_('common.tasks')),
			new CerberusDashboardViewColumn(CerberusSearchFields::TEAM_NAME,$translate->_('common.team')),
			new CerberusDashboardViewColumn(CerberusSearchFields::CATEGORY_NAME,$translate->_('common.category')),
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
	
	static $instance = null;
	private $settings = array( // defaults
		self::DEFAULT_TEAM_ID => 0,
		self::DEFAULT_REPLY_FROM => '',
		self::DEFAULT_REPLY_PERSONAL => '',
		self::HELPDESK_TITLE => 'Cerberus Helpdesk :: Team-based E-mail Management',
		self::SAVE_FILE_PATH => 'ftps://cerberus:cerberus@localhost/',
		self::SMTP_HOST => 'localhost',
		self::SMTP_AUTH_USER => '',
		self::SMTP_AUTH_PASS => '',
	);

	/**
	 * @return CerberusSettings
	 */
	private function __construct() {
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
