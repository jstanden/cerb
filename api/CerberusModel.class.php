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
	public $renderSortBy = 't.subject';
	public $renderSortAsc = 1;
	
	function getTickets() {
		$tickets = CerberusTicketDAO::searchTickets(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $tickets;	
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
	
	/**
	 * Enter description here...
	 *
	 * @return CerberusMailbox[]
	 */
	function getMailboxes() {
		return CerberusWorkflowDAO::getTeamMailboxes($this->id);
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