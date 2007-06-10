<?php
class Model_WorkerPreference {
    public $setting = '';
    public $value = '';
};

class Model_DashboardViewAction {
	public $id = 0;
	public $dashboard_view_id = 0;
	public $name = '';
	public $worker_id = 0;
	public $params = array();
	
	/*
	 * [TODO] [JAS] This could be way more efficient by doing a single DAO_Ticket::update() 
	 * call where the DAO accepts multiple IDs for a single update, vs. a loop with 'n'.
	 */
	
	/**
	 * @param integer[] $ticket_ids
	 */
	function run($ticket_ids) {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit(); /* @var $visit CerberusVisit */
		$agent_id = $visit->getWorker()->id;
		
		if(is_array($ticket_ids))
		foreach($ticket_ids as $ticket_id) {
			$fields = array();
			
			// actions
			if(is_array($this->params))
			foreach($this->params as $k => $v) {
				if(empty($v)) continue;
				
				switch($k) {
					case 'closed':
						$fields[DAO_Ticket::IS_CLOSED] = intval($v);
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
						list($team_id,$category_id) = CerberusApplication::translateTeamCategoryCode($v);
						$fields[DAO_Ticket::TEAM_ID] = $team_id;
						$fields[DAO_Ticket::CATEGORY_ID] = $category_id;
						break;
					
					default:
						// [TODO] Log?
						break;
				}
			}
			
			// [TODO] Accept multiple ticket IDs in DAO stub
			DAO_Ticket::updateTicket($ticket_id,$fields);
		}
	}
};

class Model_Activity {
    public $translation_code;
    public $params;
    
    public function __construct($translation_code='activity.default',$params=array()) {
        $this->translation_code = $translation_code;
        $this->params = $params;
    }
    
    public function toString() {
        $translate = DevblocksPlatform::getTranslationService();
        return vsprintf($translate->_($this->translation_code), $this->params);
    }
}

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
	public $last_activity;
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
		$tickets = DAO_Ticket::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $tickets;	
	}
};

// [JAS] This is no longer needed
class CerberusResourceSearchFields implements IDevblocksSearchFields {
	// Resource
	const KB_ID = 'kb_id';
	const KB_TITLE = 'kb_title';
	const KB_TYPE = 'kb_type';
	
	// Content
	const KB_CONTENT = 'kb_content';
	
	// Category
	const KB_CATEGORY_ID = 'kbc_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			CerberusResourceSearchFields::KB_ID => new DevblocksSearchField(CerberusResourceSearchFields::KB_ID, 'kb', 'id'),
			CerberusResourceSearchFields::KB_TITLE => new DevblocksSearchField(CerberusResourceSearchFields::KB_TITLE, 'kb', 'title'),
			CerberusResourceSearchFields::KB_TYPE => new DevblocksSearchField(CerberusResourceSearchFields::KB_TYPE, 'kb', 'type'),
			
			CerberusResourceSearchFields::KB_CONTENT => new DevblocksSearchField(CerberusResourceSearchFields::KB_CONTENT, 'kbc', 'content'),
			
			CerberusResourceSearchFields::KB_CATEGORY_ID => new DevblocksSearchField(CerberusResourceSearchFields::KB_CATEGORY_ID, 'kbcat', 'id'),
		);
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

class CerberusTicketStatus {
	const OPEN = 0;
	const CLOSED = 1;
	
	/**
	 * @return array 
	 */
	public static function getOptions() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::OPEN => $translate->_('status.open'),
			self::CLOSED => $translate->_('status.closed'),
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
	public $is_closed = 0;
	public $is_deleted = 0;
	public $team_id;
	public $category_id;
	public $priority;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $due_date;
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

class CerberusCategory {
	public $id;
	public $name;
	public $team_id;
	public $tags = array();
}

class Enum_CerberusTaskOwnerType {
	const WORKER = 'W';
	const TEAM = 'T';
};

class Model_CerberusTask {
	public $id;
	public $ticket_id;
	public $title;
	public $due_date;
	public $is_completed;
	
	/**
	 * @return string
	 */
	function getContent() {
		return DAO_Task::getContent($this->id);
	}
	
	/**
	 * @return Model_CerberusTaskOwners[]
	 */
	function getOwners() {
		$owners = DAO_Task::getOwners(array($this->id));
		return $owners[$this->id];
	}
}

class Model_CerberusTaskOwners {
	public $workers = array();
	public $teams = array();
}

class CerberusPop3Account {
	public $id;
	public $enabled=1;
	public $nickname;
	public $protocol='pop3';
	public $host;
	public $username;
	public $password;
	public $port=110;
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

class Model_Community {
    public $id = 0;
    public $name = '';
    public $url = '';
}

interface ICerberusCriterion {
	public function getValue($rfcMessage);
};

?>