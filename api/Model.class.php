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
class Model_TeamRoutingRule {
    public $id = 0;
    public $team_id = 0;
    public $header = '';
    public $pattern = '';
    public $pos = 0;
//    public $params = array();
    public $do_move = '';
    public $do_status = '';
    public $do_spam = '';
    
    function getPatternAsRegexp() {
		$pattern = str_replace(array('*'),'__any__', $this->pattern);
		$pattern = sprintf("/%s/i",
		    str_replace(array('__any__'),'(.*?)', preg_quote($pattern))
		);
		
//		 if(false !== @preg_match($pattern, '')) {
	    // [TODO] Test the pattern we created?

		return $pattern;
    }
}

// [TODO] This should move somewhere more generic (App/API)
abstract class C4_AbstractView {
	public $id = 0;
	public $name = "";
	public $view_columns = array();
	public $params = array();
	
	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = '';
	public $renderSortAsc = 1;
	
	function getData() {
	}
	
	function renderCriteria($field) {
		echo ' '; // Expect Override
	}
	
	function render() {
		echo ' '; // Expect Override
	}

	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param string $value
	 * @abstract
	 */
	function doSetCriteria($field, $oper, $value) {
		// Expect Override
	}
	
	function getSearchFields() {
		// Expect Override
		return array();
	}
	
	function doCustomize($columns, $num_rows=10) {
		$this->renderLimit = $num_rows;
		
		$viewColumns = array();
		foreach($columns as $col) {
			if(empty($col))
				continue;
			$viewColumns[] = $col;
		}
		
		$this->view_columns = $viewColumns;
	}
	
	function doSortBy($sortBy) {
		$iSortAsc = intval($this->renderSortAsc);
		
		// [JAS]: If clicking the same header, toggle asc/desc.
		if(0 == strcasecmp($sortBy,$this->renderSortBy)) {
			$iSortAsc = (0 == $iSortAsc) ? 1 : 0;
		} else { // [JAS]: If a new header, start with asc.
			$iSortAsc = 1;
		}
		
		$this->renderSortBy = $sortBy;
		$this->renderSortAsc = $iSortAsc;
	}
	
	function doPage($page) {
		$this->renderPage = $page;
	}
	
	function doRemoveCriteria($field) {
		unset($this->params[$field]);
		$this->renderPage = 0;
	}
	
	function doResetCriteria() {
		$this->params = array();
		$this->renderPage = 0;
	}
};

class C4_AbstractViewLoader {
	static $views = null;
	const VISIT_ABSTRACTVIEWS = 'abstractviews_list';
	
	static private function _init() {
		$visit = CerberusApplication::getVisit();
		self::$views = $visit->get(self::VISIT_ABSTRACTVIEWS,array());
	}
	
	/**
	 * @param string $view_label Abstract view identifier
	 * @return boolean
	 */
	static function exists($view_label) {
		if(is_null(self::$views)) self::_init();
		return isset(self::$views[$view_label]);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @return C4_AbstractView instance
	 */
	static function getView($class, $view_label) {
		if(is_null(self::$views)) self::_init();
		if(!self::exists($view_label)) {
			// [JAS]: [TODO] port this to working generically on any ID
//			if($view_label == CerberusApplication::VIEW_SEARCH) {
//				self::setView($view_label, self::createSearchView());
//				return self::views[$view_label];
//			}

			if(empty($class) || !class_exists($class))
				return null;
			
			$view = new $class;
			self::setView($class, $view_label, $view);
			return $view;
		}
		
		return self::$views[$view_label];
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @param C4_AbstractView $view
	 */
	static function setView($class, $view_label, $view) {
		if(is_null(self::$views)) self::_init();
		self::$views[$view_label] = $view;
		self::_save();
	}
	
	static private function _save() {
		// persist
		$visit = CerberusApplication::getVisit();
		$visit->set(self::VISIT_ABSTRACTVIEWS, self::$views);
	}
};

class Model_Address {
	public $id;
	public $email;
	public $first_name;
	public $last_name;
	public $contact_org_id;
	
	function Model_Address() {}
};

class C4_AddressView extends C4_AbstractView {
	const DEFAULT_ID = 'addresses';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'E-mail Addresses';
		$this->renderLimit = 10;
		$this->renderSortBy = 'a_email';
		$this->renderSortAsc = true;
		
		$this->view_columns = array(
			SearchFields_Address::FIRST_NAME,
			SearchFields_Address::LAST_NAME,
			SearchFields_Address::ORG_NAME,
		);
	}
	
	function getData() {
		$objects = DAO_Address::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;	
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/addresses/address_view.tpl.php');
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::FIRST_NAME:
			case SearchFields_Address::LAST_NAME:
			case SearchFields_Address::ORG_NAME:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/criteria/org__string.tpl.php');
				break;
			default:
				echo '';
				break;
		}
	}
	
	function getSearchFields() {
		return SearchFields_Address::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::FIRST_NAME:
			case SearchFields_Address::LAST_NAME:
			case SearchFields_Address::ORG_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE) 
					&& false === (strpos($value,'*'))) {
						$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
		}
		
		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};

class C4_ContactOrgView extends C4_AbstractView {
	const DEFAULT_ID = 'contact_orgs';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Organizations';
		$this->renderSortBy = 'c_name';
		$this->renderSortAsc = true;
		
		$this->view_columns = array(
			SearchFields_ContactOrg::PHONE,
			SearchFields_ContactOrg::PROVINCE,
			SearchFields_ContactOrg::COUNTRY,
			SearchFields_ContactOrg::WEBSITE,
			SearchFields_ContactOrg::CREATED,
		);
	}
	
	function getData() {
		$objects = DAO_ContactOrg::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;	
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
//		// Undo?
//	    $last_action = CerberusDashboardView::getLastAction($id);
//	    $tpl->assign('last_action', $last_action);
//	    if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
//	        $tpl->assign('last_action_count', count($last_action->ticket_ids));
//	    }

		$tpl->cache_lifetime = "0";
		$tpl->assign('search_columns', SearchFields_ContactOrg::getFields());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/orgs/contact_view.tpl.php');
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_ContactOrg::NAME:
			case SearchFields_ContactOrg::ACCOUNT_NUMBER:
			case SearchFields_ContactOrg::PHONE:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::WEBSITE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/criteria/org__string.tpl.php');
				break;
			default:
				echo '';
				break;
		}
	}
	
	function getSearchFields() {
		return SearchFields_ContactOrg::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_ContactOrg::NAME:
			case SearchFields_ContactOrg::ACCOUNT_NUMBER:
			case SearchFields_ContactOrg::PHONE:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::WEBSITE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE) 
					&& false === (strpos($value,'*'))) {
						$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
		}
		
		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};

class Model_ContactOrg {
	public $id;
	public $account_number;
	public $name;
	public $street;
	public $city;
	public $province;
	public $postal;
	public $country;
	public $phone;
	public $fax;
	public $website;
	public $created;
	public $sync_id = '';
};

class Model_WorkerPreference {
    public $setting = '';
    public $value = '';
    public $worker_id = '';
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
//		if(is_array($ticket_ids))
//		foreach($ticket_ids as $ticket_id) {
		$fields = array();
		
		// actions
		if(is_array($this->params))
		foreach($this->params as $k => $v) {
			if(empty($v) && !is_numeric($v)) continue;
			
			switch($k) {
				case 'closed':
					switch(intval($v)) {
				        case CerberusTicketStatus::OPEN:
				            $fields[DAO_Ticket::IS_CLOSED] = 0;
				            $fields[DAO_Ticket::IS_DELETED] = 0;
				            break;
				        case CerberusTicketStatus::CLOSED:
				            $fields[DAO_Ticket::IS_CLOSED] = 1;
				            break;
				        case 2:
				            $fields[DAO_Ticket::IS_CLOSED] = 1;
				            $fields[DAO_Ticket::IS_DELETED] = 1;
				            break;
				    }
					break;
				
				case 'spam':
					if($v == CerberusTicketSpamTraining::NOT_SPAM) {
					    foreach($ticket_ids as $ticket_id) {
						    CerberusBayes::markTicketAsNotSpam($ticket_id);
					    }
						$fields[DAO_Ticket::SPAM_TRAINING] = $v;
						
					} elseif($v == CerberusTicketSpamTraining::SPAM) {
					    foreach($ticket_ids as $ticket_id) {
					        CerberusBayes::markTicketAsSpam($ticket_id);
                        }
						$fields[DAO_Ticket::SPAM_TRAINING] = $v;
			            $fields[DAO_Ticket::IS_CLOSED] = 1;
			            $fields[DAO_Ticket::IS_DELETED] = 1;
					}
					
					break;
				
				case 'team':
				    // [TODO] Make sure the team/bucket still exists
					list($team_id,$category_id) = CerberusApplication::translateTeamCategoryCode($v);
					$fields[DAO_Ticket::TEAM_ID] = $team_id;
					$fields[DAO_Ticket::CATEGORY_ID] = $category_id;
					break;
				
				default:
					// [TODO] Log?
					break;
			}
		}
//		}

		DAO_Ticket::updateTicket($ticket_ids, $fields);
		
		if(!empty($this->params['team']) && !empty($team_id)) {
		    
		    $eventMgr = DevblocksPlatform::getEventService();
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.moved', // [TODO] Const
	                array(
	                    'ticket_ids' => $ticket_ids,
	                    'team_id' => $team_id,
	                    'bucket_id' => $category_id,
	                )
	            )
		    );
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
	const KEY_WORKSPACE_GROUP_ID = 'cur_group_id';
	const KEY_VIEW_LAST_ACTION = 'view_last_action';

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
	public $is_superuser=0;
	public $can_delete=0;
	public $last_activity;
	public $last_activity_date;
	
	/**
	 * @return Model_TeamMember[]
	 */
	function getMemberships() {
		return DAO_Worker::getGroupMemberships($this->id);
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

class Model_TicketRss {
	public $id = 0;
	public $title = '';
	public $hash = '';
	public $worker_id = 0;
	public $created = 0;
	public $params = array();
}

class Model_TicketViewLastAction {
    // [TODO] Recycle the bulk update constants for these actions?
    const ACTION_NOT_SPAM = 'not_spam';
    const ACTION_SPAM = 'spam';
    const ACTION_CLOSE = 'close';
    const ACTION_DELETE = 'delete';
    const ACTION_MOVE = 'move';
    const ACTION_SURRENDER = 'surrender';
    
    public $ticket_ids = array(); // key = ticket id, value=old value
    public $action = ''; // spam/closed/move, etc.
	public $action_params = array(); // DAO Actions Taken
};

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
	
	static public function setLastAction($view_id, Model_TicketViewLastAction $last_action=null) {
	    $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
	    $view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
	    
	    if(!is_null($last_action) && !empty($last_action->ticket_ids)) {
	        $view_last_actions[$view_id] = $last_action;
	    } else {
	        if(isset($view_last_actions[$view_id])) {
	            unset($view_last_actions[$view_id]);
	        }
	    }
	    
        $visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,$view_last_actions);
	}
	
	/**
	 * @param string $view_id
	 * @return Model_TicketViewLastAction
	 */
	static public function getLastAction($view_id) {
	    $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
        return (isset($view_last_actions[$view_id]) ? $view_last_actions[$view_id] : null);
	}
	
	static public function clearLastActions() {
	    $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
	    $visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
	}
	
	/**
	 * @param array
	 * @param array
	 * @return boolean
	 */
	function doBulkUpdate($filter, $filter_param, $data, $do, $ticket_ids=array(), $always_do_for_team_id=0) {
	    @set_time_limit(600); // [TODO] Temp!
	    
		$action = new Model_DashboardViewAction();
		$action->params = $do;
		$action->dashboard_view_id = $this->id;
	    
		$params = $this->params;

		$team_id = 0;
		$bucket_id = 0;
		
		if(empty($filter)) {
			$data[] = '*'; // All, just to permit a loop in foreach($data ...)
		}
		
		if(!empty($do['team']))
	        list($team_id, $bucket_id) = CerberusApplication::translateTeamCategoryCode($do['team']);
		
		switch($filter) {
			default:
		    case 'subject':
            case 'sender':
            case 'header':

		        foreach($data as $v) {
		        	$new_params = array();
		        	$do_header = null;
		        	
					switch($filter) {
					    case 'subject':
					        $new_params = array(
					            new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$v)
					        );
		                    $do_header = 'subject';
		                    $ticket_ids = array();
					        break;
					    case 'sender':
			                $new_params = array(
			                    new DevblocksSearchCriteria(SearchFields_Ticket::SENDER_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,$v)
			                );
                            $do_header = 'from';
                            $ticket_ids = array();
			                break;
			            case 'header':
	                        $new_params = array(
	                            // [TODO] It will eventually come up that we need multiple header matches (which need to be pair grouped as OR)
	                            new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER,DevblocksSearchCriteria::OPER_EQ,$filter_param),
	                            new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER_VALUE,DevblocksSearchCriteria::OPER_EQ,$v)
	                        );
	                        $ticket_ids = array();
	                        break;
					}               
		            
		            $new_params = array_merge($new_params, $params);
	                $pg = 0;

	                if(empty($ticket_ids)) {
				        do {
					        list($tickets,$null) = DAO_Ticket::search(
					            $new_params,
					            500,
					            $pg++,
					            SearchFields_Ticket::TICKET_ID,
					            true,
					            false
					        );
					        
					        $ticket_ids = array_merge($ticket_ids, array_keys($tickets));
					        
				        } while(!empty($tickets));
		        	}
			        
			        // [TODO] Allow rule creation on headers
			        
			        // Did we want to save this and repeat it in the future?
				    if($always_do_for_team_id && !empty($do_header)) {
					  $fields = array(
					      DAO_TeamRoutingRule::HEADER => $do_header,
					      DAO_TeamRoutingRule::PATTERN => $v,
					      DAO_TeamRoutingRule::TEAM_ID => $always_do_for_team_id,
					      DAO_TeamRoutingRule::POS => count($ticket_ids),
					      DAO_TeamRoutingRule::DO_MOVE => $do['team'],
					      DAO_TeamRoutingRule::DO_SPAM => $do['spam'],
					      DAO_TeamRoutingRule::DO_STATUS => $do['closed'],
					  );
					  DAO_TeamRoutingRule::create($fields);
					}
					
			        $batch_total = count($ticket_ids);
			        for($x=0;$x<=$batch_total;$x+=500) {
			            $batch_ids = array_slice($ticket_ids,$x,500);
	                    $action->run($batch_ids);
			            unset($batch_ids);
			        }
		        }
		        
		        break;
		}

        unset($ticket_ids);
	}
	
	function getInvolvedGroups() {
		$groups = array();
		foreach($this->params as $criteria) {
			if($criteria->field == SearchFields_Ticket::TEAM_ID) {
				if(is_array($criteria->value)) {
					foreach($criteria->value as $val) {
						$groups[] = $val;
					}
				}
				else {
					$groups[] = $criteria->value;
				} 
			}
		}
		return $groups;
	}
};

class CerberusMessageType { // [TODO] Append 'Enum' to class name?
	const EMAIL = 'E';
	const FORWARD = 'F';
	const COMMENT = 'C';
	const AUTORESPONSE = 'A';
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
	const BLANK = '';
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

class CerberusTicket {
	public $id;
	public $mask;
	public $subject;
	public $is_closed = 0;
	public $is_deleted = 0;
	public $team_id;
	public $category_id;
	public $priority;
	public $first_message_id;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $due_date;
	public $spam_score;
	public $spam_training;
	public $interesting_words;
	public $next_action;
	public $last_action_code;
	public $last_worker_id;
	public $next_worker_id;
	
	function CerberusTicket() {}
	
	function getMessages() {
		$messages = DAO_Ticket::getMessagesByTicket($this->id);
		return $messages;
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

class CerberusTicketActionCode {
  const TICKET_OPENED = 'O';  
  const TICKET_CUSTOMER_REPLY = 'R';  
  const TICKET_WORKER_REPLY = 'W';  
};

class CerberusMessage {
	public $id;
	public $ticket_id;
	public $message_type;
	public $created_date;
	public $address_id;
	public $message_id;
	
	function CerberusMessage() {}
	
	function getContent() {
		return DAO_MessageContent::get($this->id);
	}
	
	function getHeaders() {
	    return DAO_MessageHeader::getAll($this->id);
	}

	/**
	 * returns an array of the message's attachments
	 *
	 * @return Model_Attachment[]
	 */
	function getAttachments() {
		$attachments = DAO_Ticket::getAttachmentsByMessage($this->id);
		return $attachments;
	}

};

class Model_MessageNote {
	public $id;
	public $message_id;
	public $created;
	public $worker_id;
	public $content;
};

class Model_Attachment {
	public $id;
	public $message_id;
	public $display_name;
	public $filepath;
	public $file_size = 0;
	public $mime_type = '';
	
	public function getFileContents() {
	    $file_path = APP_PATH . '/storage/attachments/';
		if (!empty($this->filepath))
	        return file_get_contents($file_path.$this->filepath,false);
	}
};

class CerberusTeam {
	public $id;
	public $name;
	public $count;
}

class Model_TeamMember {
	public $id;
	public $team_id;
	public $is_manager = 0;
}

class CerberusCategory {
	public $id;
	public $name;
	public $team_id;
	public $tags = array();
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

class Model_Community {
    public $id = 0;
    public $name = '';
}

class Model_FnrTopic {
	public $id = 0;
	public $name = '';
	
	function getResources() {
		$where = sprintf("%s = %d",
			DAO_FnrExternalResource::TOPIC_ID,
			$this->id
		);
		$resources = DAO_FnrExternalResource::getWhere($where);
		return $resources;
	}
};

class Model_FnrExternalResource {
	public $id = 0;
	public $name = '';
	public $url = '';
	public $topic_id = 0;
};

class Model_MailTemplateReply {
	public $id = 0;
	public $title = '';
	public $description = '';
	public $folder = '';
	public $owner_id = 0;
	public $content = '';
	
	public function getRenderedContent($message_id) {
		$raw = $this->content;
		
		if(empty($message_id))
			return $raw;
		
		$message = DAO_Ticket::getMessage($message_id);
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$sender = DAO_Address::get($message->address_id);
		$sender_org = DAO_ContactOrg::get($sender->contact_org_id);
		$worker = CerberusApplication::getActiveWorker();
		
		$out = str_replace(
			array(
				'#sender_first_name#',
				'#sender_last_name#',
				'#sender_org#',

				'#ticket_mask#',
				'#ticket_subject#',
				
				'#worker_first_name#',
				'#worker_last_name#',
				'#worker_title#',
			),
			array(
				$sender->first_name,
				$sender->last_name,
				(!empty($sender_org)?$sender_org->name:""),
				
				$ticket->mask,
				$ticket->subject,
				
				$worker->first_name,
				$worker->last_name,
				$worker->title,
			),
			$raw
		);
		
		return $out;
	}
};

?>