<?php

class CerberusAgent {
	public $id;
	public $login;
	public $admin;
	
	function getTeams() {
		return CerberusAgentDAO::getAgentTeams($this->id);
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
	
	// Requester
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
			CerberusSearchFields::TICKET_LAST_WROTE => new CerberusSearchField(CerberusSearchFields::TICKET_LAST_WROTE, 't', 'last_wrote'),
			CerberusSearchFields::TICKET_FIRST_WROTE => new CerberusSearchField(CerberusSearchFields::TICKET_FIRST_WROTE, 't', 'first_wrote'),
			CerberusSearchFields::TICKET_CREATED_DATE => new CerberusSearchField(CerberusSearchFields::TICKET_CREATED_DATE, 't', 'created_date'),
			CerberusSearchFields::TICKET_UPDATED_DATE => new CerberusSearchField(CerberusSearchFields::TICKET_FIRST_WROTE, 't', 'updated_date'),

			CerberusSearchFields::ASSIGNED_WORKER => new CerberusSearchField(CerberusSearchFields::ASSIGNED_WORKER, 'att', 'agent_id'),
			CerberusSearchFields::SUGGESTED_WORKER => new CerberusSearchField(CerberusSearchFields::SUGGESTED_WORKER, 'stt', 'agent_id'),
			
			CerberusSearchFields::REQUESTER_ADDRESS => new CerberusSearchField(CerberusSearchFields::REQUESTER_ADDRESS, 'ra', 'email'),
			
			CerberusSearchFields::MAILBOX_ID => new CerberusSearchField(CerberusSearchFields::MAILBOX_ID, 'm', 'id'),
			CerberusSearchFields::MAILBOX_NAME => new CerberusSearchField(CerberusSearchFields::MAILBOX_NAME, 'm', 'name'),
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

class CerberusMessageType {
	const EMAIL = 'E';
	const FORWARD = 'F';
	const COMMENT = 'C';
	const AUTORESPONSE = 'A';
};

class CerberusTicketBits {
	const CREATED_FROM_WEB = 1;
};

class CerberusTicketStatus {
	const OPEN = 'O';
	const WAITING = 'W';
	const CLOSED = 'C';
	const DELETED = 'D';
};

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
	public $first_wrote;
	public $last_wrote;
	public $created_date;
	public $updated_date;
	
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

interface ICerberusCriterion {
	public function getValue($rfcMessage);
};

?>