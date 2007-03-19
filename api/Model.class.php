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
		$agent_id = $visit->worker->id;
		
		if(is_array($tickets))
		foreach($tickets as $ticket_id => $ticket) {
			$fields = array();
			
			// actions
			if(is_array($this->params))
			foreach($this->params as $k => $v) {
				switch($k) {
					case 'status':
						$fields[CerberusTicketDAO::STATUS] = $v;
						break;
					
					case 'priority':
						$fields[CerberusTicketDAO::PRIORITY] = $v;
						break;
					
					case 'mailbox':
						$fields[CerberusTicketDAO::MAILBOX_ID] = $v;
						break;
					
					case 'spam':
						$fields[CerberusTicketDAO::SPAM_TRAINING] = $v;
						break;
					
					case 'flag':
						if($v==CerberusTicketFlagEnum::TAKE) {
							CerberusTicketDAO::flagTicket($ticket_id, $agent_id);
						} else { // release
							CerberusTicketDAO::unflagTicket($ticket_id, $agent_id);
						}
						break;
					
					default:
						// [TODO] Log?
						break;
				}
			}
			
			CerberusTicketDAO::updateTicket($ticket_id,$fields);
		}
	}
};

class CerberusVisit extends DevblocksVisit {
	public $worker;
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
	public $login;
	public $title;
	public $last_activity_date;
	
	function getTeams() {
		return CerberusAgentDAO::getAgentTeams($this->id);
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
	public $agent_id = 0;
	public $view_columns = array();
	public $params = array();
	
	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = 't_subject';
	public $renderSortAsc = 1;
	
	function getTickets() {
		$tickets = CerberusSearchDAO::searchTickets(
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
	
	// Mailbox
	const MAILBOX_ID = 'm_id';
	const MAILBOX_NAME = 'm_name';
	
	// Worker Workflow
	const ASSIGNED_WORKER = 'att_agent_id';
	const SUGGESTED_WORKER = 'stt_agent_id';
	
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

			CerberusSearchFields::ASSIGNED_WORKER => new CerberusSearchField(CerberusSearchFields::ASSIGNED_WORKER, 'att', 'agent_id'),
			CerberusSearchFields::SUGGESTED_WORKER => new CerberusSearchField(CerberusSearchFields::SUGGESTED_WORKER, 'stt', 'agent_id'),
			
			CerberusSearchFields::REQUESTER_ID => new CerberusSearchField(CerberusSearchFields::REQUESTER_ID, 'ra', 'id'),
			CerberusSearchFields::REQUESTER_ADDRESS => new CerberusSearchField(CerberusSearchFields::REQUESTER_ADDRESS, 'ra', 'email'),
			
			CerberusSearchFields::MAILBOX_ID => new CerberusSearchField(CerberusSearchFields::MAILBOX_ID, 'm', 'id'),
			CerberusSearchFields::MAILBOX_NAME => new CerberusSearchField(CerberusSearchFields::MAILBOX_NAME, 'm', 'name'),
		);
	}
};

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
			self::OPEN => $translate->say('status.open'),
			self::WAITING => $translate->say('status.waiting'),
			self::CLOSED => $translate->say('status.closed'),
			self::DELETED => $translate->say('status.deleted'),
		);
	}
};

class CerberusTicketSpamTraining { // [TODO] Append 'Enum' to class name?
	const NOT_SPAM = 'N';
	const SPAM = 'S';
	
	public static function getOptions() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::NOT_SPAM => $translate->say('training.not_spam'),
			self::SPAM => $translate->say('training.report_spam'),
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
			self::NONE => $translate->say('priority.none'),
			self::LOW => $translate->say('priority.low'),
			self::MODERATE => $translate->say('priority.moderate'),
			self::HIGH => $translate->say('priority.high'),
		);
	}
};

class CerberusTicketFlagEnum {
	const TAKE = 'T';
	const RELEASE = 'R';
	
	public static function getOptions() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::TAKE => $translate->say('workflow.take'),
			self::RELEASE => $translate->say('workflow.release'),
		);
	}
}

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
	public $bitflags;
	public $status;
	public $priority;
	public $mailbox_id;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $spam_score;
	public $spam_training;
	
	function CerberusTicket() {}
	
	function getMessages() {
		$messages = CerberusTicketDAO::getMessagesByTicket($this->id);
		return $messages[0];
	}
	
	function getRequesters() {
		$requesters = CerberusTicketDAO::getRequestersByTicket($this->id);
		return $requesters;
	}
	
	function getTags() {
		$tags = CerberusWorkflowDAO::getTagsByTicket($this->id);
		return $tags;
	}
	
	function getFlaggedWorkers() {
		$agents = CerberusWorkflowDAO::getWorkersByTicket($this->id, true);
		return $agents;
	}
	
	function getSuggestedWorkers() {
		$agents = CerberusWorkflowDAO::getWorkersByTicket($this->id, false);
		return $agents;
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
		return CerberusTicketDAO::getMessageContent($this->id);
	}

	/**
	 * returns an array of the message's attachments
	 *
	 * @return CerberusAttachment[]
	 */
	function getAttachments() {
		$attachments = CerberusTicketDAO::getAttachmentsByMessage($this->id);
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

class CerberusMailbox {
	public $id;
	public $name;
	public $reply_address_id;
	public $display_name;
	public $close_autoresponse;
	public $new_autoresponse;
	public $count;
	
	function CerberusMailbox() {}
	
	/**
	 * Enter description here...
	 *
	 * @return CerberusTeam[]
	 */
	function getTeams() {
		return CerberusMailDAO::getMailboxTeams($this->id);
	}
};

class CerberusTeam {
	public $id;
	public $name;
	public $count;
	
	/**
	 * Enter description here...
	 *
	 * @return CerberusMailbox[]
	 */
	function getMailboxes($with_counts = false) {
		return CerberusWorkflowDAO::getTeamMailboxes($this->id, $with_counts);
	}
	
	function getWorkers() {
		return CerberusWorkflowDAO::getTeamWorkers($this->id);
	}
}

class CerberusPop3Account {
	public $id;
	public $nickname;
	public $host;
	public $username;
	public $password;
};

class CerberusTag {
	public $id;
	public $name;
	
	function getTerms() {
		return CerberusWorkflowDAO::getTagTerms($this->id);
	}
};

class CerberusTagTerm {
	public $tag_id;
	public $term;
};

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

interface ICerberusCriterion {
	public function getValue($rfcMessage);
};

?>