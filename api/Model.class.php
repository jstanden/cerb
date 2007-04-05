<?php

class Model_DashboardViewAction {
	public $id = 0;
	public $dashboard_view_id = 0;
	public $name = '';
	public $worker_id = 0;
	public $params = array();
	
	/**
	 * @param CerberusTicket[] $tickets
	 */
	function run($tickets) {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit(); /* @var $visit CerberusVisit */
		$agent_id = $visit->getWorker()->id;
		
		if(is_array($tickets))
		foreach($tickets as $ticket_id => $ticket) {
			$fields = array();
			
			// actions
			if(is_array($this->params))
			foreach($this->params as $k => $v) {
				switch($k) {
					case 'status':
						$fields[DAO_Ticket::STATUS] = $v;
						break;
					
					case 'priority':
						$fields[DAO_Ticket::PRIORITY] = $v;
						break;
					
					case 'spam':
						$fields[DAO_Ticket::SPAM_TRAINING] = $v;
						
						if($v == CerberusTicketSpamTraining::NOT_SPAM) {
							CerberusBayes::markTicketAsNotSpam($ticket_id);
						} else {
							CerberusBayes::markTicketAsSpam($ticket_id);
						}
						
						break;
					
					case 'team':
						$fields[DAO_Ticket::TEAM_ID] = $v;
					
					default:
						// [TODO] Log?
						break;
				}
			}
			
			DAO_Ticket::updateTicket($ticket_id,$fields);
		}
	}
};

class Model_MailRoute {
	public $id = 0;
	public $pattern = '';
	public $team_id = 0;
	public $pos = 0;
};

class CerberusVisit extends DevblocksVisit {
	private $worker;
	
	const KEY_VIEW_MANAGER = 'view_manager';
	const KEY_DASHBOARD_ID = 'cur_dashboard_id';

	public function __construct() {
		$this->worker = null;
		$this->set(self::KEY_VIEW_MANAGER, new CerberusStaticViewManager());
	}
	
	/**
	 * @return CerberusWorker
	 */
	public function getWorker() {
		return $this->worker;
	}
	
	public function setWorker(CerberusWorker $worker=null) {
		$this->worker = $worker;
	}
	
}

class CerberusBayesWord {
	public $id = -1;
	public $word = '';
	public $spam = 0;
	public $nonspam = 0;
	public $probability = CerberusBayes::PROBABILITY_UNKNOWN;
	public $interest_rating = 0.0;
}

class CerberusWorker {
	public $id;
	public $first_name;
	public $last_name;
	public $email;
	public $title;
	public $last_activity_date;
	
	function getTeams() {
		return DAO_Worker::getAgentTeams($this->id);
	}
	
	function getName() {
		return sprintf("%s%s%s",
			$this->first_name,
			(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
			$this->last_name
		);
	}
	
}

class CerberusDashboardViewColumn {
	public $column;
	public $name;
	
	public function CerberusDashboardViewColumn($column, $name) {
		$this->column = $column;
		$this->name = $name;
	}
}

class CerberusDashboard {
	public $id = 0;
	public $name = "";
	public $agent_id = 0;
}

class CerberusDashboardView {
	public $id = 0;
	public $name = "";
	public $dashboard_id = 0;
	public $type = '';
	public $view_columns = array();
	public $params = array();
	
	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = 't_subject';
	public $renderSortAsc = 1;
	
	function getTickets() {
		$tickets = DAO_Search::searchTickets(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $tickets;	
	}
};

class CerberusSearchFields {
	// Ticket
	const TICKET_ID = 't_id';
	const TICKET_MASK = 't_mask';
	const TICKET_STATUS = 't_status';
	const TICKET_PRIORITY = 't_priority';
	const TICKET_SUBJECT = 't_subject';
	const TICKET_LAST_WROTE = 't_last_wrote';
	const TICKET_FIRST_WROTE = 't_first_wrote';
	const TICKET_CREATED_DATE = 't_created_date';
	const TICKET_UPDATED_DATE = 't_updated_date';
	const TICKET_SPAM_SCORE = 't_spam_score';
	
	// Message
	const MESSAGE_CONTENT = 'msg_content';
	
	// Requester
	const REQUESTER_ID = 'ra_id';
	const REQUESTER_ADDRESS = 'ra_email';
	
	// Teams
	const TEAM_ID = 'tm_id';
	const TEAM_NAME = 'tm_name';
	
	/**
	 * @return CerberusSearchField[]
	 */
	static function getFields() {
		return array(
			CerberusSearchFields::TICKET_MASK => new CerberusSearchField(CerberusSearchFields::TICKET_MASK, 't', 'mask'),
			CerberusSearchFields::TICKET_STATUS => new CerberusSearchField(CerberusSearchFields::TICKET_STATUS, 't', 'status'),
			CerberusSearchFields::TICKET_PRIORITY => new CerberusSearchField(CerberusSearchFields::TICKET_PRIORITY, 't', 'priority'),
			CerberusSearchFields::TICKET_SUBJECT => new CerberusSearchField(CerberusSearchFields::TICKET_SUBJECT, 't', 'subject'),
			CerberusSearchFields::TICKET_LAST_WROTE => new CerberusSearchField(CerberusSearchFields::TICKET_LAST_WROTE, 'a2', 'email'),
			CerberusSearchFields::TICKET_FIRST_WROTE => new CerberusSearchField(CerberusSearchFields::TICKET_FIRST_WROTE, 'a1', 'email'),
			CerberusSearchFields::TICKET_CREATED_DATE => new CerberusSearchField(CerberusSearchFields::TICKET_CREATED_DATE, 't', 'created_date'),
			CerberusSearchFields::TICKET_UPDATED_DATE => new CerberusSearchField(CerberusSearchFields::TICKET_FIRST_WROTE, 't', 'updated_date'),
			CerberusSearchFields::TICKET_SPAM_SCORE => new CerberusSearchField(CerberusSearchFields::TICKET_SPAM_SCORE, 't', 'spam_score'),
			
			CerberusSearchFields::MESSAGE_CONTENT => new CerberusSearchField(CerberusSearchFields::MESSAGE_CONTENT, 'msg', 'content'),

			CerberusSearchFields::REQUESTER_ID => new CerberusSearchField(CerberusSearchFields::REQUESTER_ID, 'ra', 'id'),
			CerberusSearchFields::REQUESTER_ADDRESS => new CerberusSearchField(CerberusSearchFields::REQUESTER_ADDRESS, 'ra', 'email'),
			
			CerberusSearchFields::TEAM_ID => new CerberusSearchField(CerberusSearchFields::TEAM_ID,'tm','id'),
			CerberusSearchFields::TEAM_NAME => new CerberusSearchField(CerberusSearchFields::TEAM_NAME,'tm','name'),
		);
	}
};

// [JAS] This is no longer needed
class CerberusResourceSearchFields {
	// Resource
	const KB_ID = 'kb_id';
	const KB_TITLE = 'kb_title';
	const KB_TYPE = 'kb_type';
	
	// Content
	const KB_CONTENT = 'kb_content';
	
	// Category
	const KB_CATEGORY_ID = 'kbc_id';
	
	/**
	 * @return CerberusSearchField[]
	 */
	static function getFields() {
		return array(
			CerberusResourceSearchFields::KB_ID => new CerberusSearchField(CerberusResourceSearchFields::KB_ID, 'kb', 'id'),
			CerberusResourceSearchFields::KB_TITLE => new CerberusSearchField(CerberusResourceSearchFields::KB_TITLE, 'kb', 'title'),
			CerberusResourceSearchFields::KB_TYPE => new CerberusSearchField(CerberusResourceSearchFields::KB_TYPE, 'kb', 'type'),
			
			CerberusResourceSearchFields::KB_CONTENT => new CerberusSearchField(CerberusResourceSearchFields::KB_CONTENT, 'kbc', 'content'),
			
			CerberusResourceSearchFields::KB_CATEGORY_ID => new CerberusSearchField(CerberusResourceSearchFields::KB_CATEGORY_ID, 'kbcat', 'id'),
		);
	}
};

class CerberusSearchField {
	public $token;
	public $db_table;
	public $db_column;
	
	function __construct($token, $db_table, $db_column) {
		$this->token = $token;
		$this->db_table = $db_table;
		$this->db_column = $db_column;
	}
};

class CerberusSearchCriteria {
	public $field;
	public $operator;
	public $value;
	
	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param mixed $value
	 * @return CerberusSearchCriteria
	 */
	 public function CerberusSearchCriteria($field,$oper,$value) {
		$this->field = $field;
		$this->operator = $oper;
		$this->value = $value;
	}
};

class CerberusMessageType { // [TODO] Append 'Enum' to class name?
	const EMAIL = 'E';
	const FORWARD = 'F';
	const COMMENT = 'C';
	const AUTORESPONSE = 'A';
};

class CerberusTicketBits {
	const CREATED_FROM_WEB = 1;
};

class CerberusTicketStatus { // [TODO] Append 'Enum' to class name?
	const OPEN = 'O';
	const WAITING = 'W';
	const CLOSED = 'C';
	const DELETED = 'D';
	
	/**
	 * @return array 
	 */
	public static function getOptions() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::OPEN => $translate->_('status.open'),
			self::WAITING => $translate->_('status.waiting'),
			self::CLOSED => $translate->_('status.closed'),
			self::DELETED => $translate->_('status.deleted'),
		);
	}
};

class CerberusTicketSpamTraining { // [TODO] Append 'Enum' to class name?
	const NOT_SPAM = 'N';
	const SPAM = 'S';
	
	public static function getOptions() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::NOT_SPAM => $translate->_('training.not_spam'),
			self::SPAM => $translate->_('training.report_spam'),
		);
	}
};

class CerberusTicketPriority { // [TODO] Append 'Enum' to class name?
	const NONE = 0;
	const LOW = 25;
	const MODERATE = 50;
	const HIGH = 75;
	
	public static function getOptions() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::NONE => $translate->_('priority.none'),
			self::LOW => $translate->_('priority.low'),
			self::MODERATE => $translate->_('priority.moderate'),
			self::HIGH => $translate->_('priority.high'),
		);
	}
};

// [TODO] Is this used?
class CerberusAddressBits {
	const AGENT = 1;
	const BANNED = 2;
	const QUEUE = 4;
};

class CerberusTicket {
	public $id;
	public $mask;
	public $subject;
	public $status;
	public $team_id;
	public $priority;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $spam_score;
	public $spam_training;
	
	function CerberusTicket() {}
	
	function getMessages() {
		$messages = DAO_Ticket::getMessagesByTicket($this->id);
		return $messages[0];
	}
	
	function getRequesters() {
		$requesters = DAO_Ticket::getRequestersByTicket($this->id);
		return $requesters;
	}
	
	/**
	 * @return CloudGlueTag[]
	 */
	function getTags() {
		$result = DAO_CloudGlue::getTagsOnContents(array($this->id), CerberusApplication::INDEX_TICKETS);
		$tags = array_shift($result);
		return $tags;
	}
	
};

class CerberusMessage {
	public $id;
	public $ticket_id;
	public $message_type;
	public $created_date;
	public $address_id;
	public $message_id;
	public $headers;
	private $content; // use getter
	
	function CerberusMessage() {}
	
	function getContent() {
		return DAO_Ticket::getMessageContent($this->id);
	}

	/**
	 * returns an array of the message's attachments
	 *
	 * @return CerberusAttachment[]
	 */
	function getAttachments() {
		$attachments = DAO_Ticket::getAttachmentsByMessage($this->id);
		return $attachments;
	}

};

class CerberusAddress {
	public $id;
	public $email;
	public $personal;
	public $bitflags;
	
	function CerberusAddress() {}
};

class CerberusAttachment {
	public $id;
	public $message_id;
	public $display_name;
	public $filepath;
	
	function CerberusAttachment() {}
};

class CerberusTeam {
	public $id;
	public $name;
	public $count;
	
	function getWorkers() {
		return DAO_Workflow::getTeamWorkers($this->id);
	}
}

class CerberusTeamCategory {
	public $id;
	public $name;
	public $team_id;
	public $tags = array();
}

class CerberusPop3Account {
	public $id;
	public $nickname;
	public $host;
	public $username;
	public $password;
};

//class CerberusTag {
//	public $id;
//	public $name;
//	
//	function getTerms() {
//		return DAO_Workflow::getTagTerms($this->id);
//	}
//};
//
//class CerberusTagTerm {
//	public $tag_id;
//	public $term;
//};

class CerberusMailRule {
	public $id;
	public $criteria;
	public $sequence;
	public $strictness;
	
	function CerberusMailRule() {}
};

class CerberusMailRuleCriterion {
	public $field;
	public $operator;
	public $value;
	
	function CerberusMailRuleCriterion() {}
};

class CerberusKbCategory {
	public $id;
	public $name;
	public $parent_id;
	
	public $hits=0;
	public $level=0;
	public $children = array(); // ptr array
};

class CerberusKbResource {
	public $id;
	public $title;
	public $type; // CerberusKbResourceTypes
	public $categories = array();
	
	function getContent() { 
		
		return '';
	}
};

class CerberusKbResourceTypes {
	const ARTICLE = 'A';
	const URL = 'U';
};

class CerberusPatch extends DevblocksPatch {
	private $plugin_id = null;
	private $revision = null;
	private $container = null;
	
	function __construct($plugin_id, $revision, DevblocksPatchContainerExtension $container) {
		parent::__construct($plugin_id, $revision);
		$this->revision = intval($revision);
		$this->container = $container;
	}
	
	public function run() {
		if(empty($this->container) || !is_object($this->container)) {
			return FALSE;
		}
		
		// Callback
		$result = $this->container->runRevision($this->revision);
		
		if($result) {
			$this->_ran();
			return TRUE;
		} else {
			return FALSE;
		}
	}
};

interface ICerberusCriterion {
	public function getValue($rfcMessage);
};

?>