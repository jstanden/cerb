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

class Model_PreParseRule {
	public $id;
	public $name;
	public $criteria;
	public $actions;
	public $pos;
};

class Model_TeamRoutingRule {
	public $id = 0;
	public $name = '';
	public $team_id = 0;
	public $criteria = array();
	public $pos = 0;
	public $do_move = '';
	public $do_status = '';
	public $do_spam = '';
	public $do_assign = '';
	
	// [TODO] Make this support getMatches() -- every matching rule?
	static function getMatch($group_id, $ticket_id, $only_rule_id=0) {
		if(empty($group_id))
			return false;

		if(!empty($only_rule_id)) {
			$filters = array(
				DAO_TeamRoutingRule::get($only_rule_id)
			);
		} else {
			$filters = DAO_TeamRoutingRule::getByTeamId($group_id);
		}

		// Check the ticket
		if(null === ($ticket = DAO_Ticket::getTicket($ticket_id)))
			return false;
			
		// Build our objects
		$ticket_from = DAO_Address::get($ticket->last_wrote_address_id);
		$ticket_group_id = $ticket->team_id;
		// [TODO] These expensive checks should only populate when needed
		$messages = DAO_Ticket::getMessagesByTicket($ticket_id);
		
		if(empty($messages))
			return false;
		
		$message = array_pop($messages); /* @var $message CerberusMessage */
		if(empty($message))
			return false;
		
//		$ticket_is_new = ($ticket->first_message_id==$message->id) ? 1 : 0;
		$message_headers = $message->getHeaders();
//		$message_body = $message->getContent();
		
		// Check filters
		if(is_array($filters))
		foreach($filters as $filter) { /* @var $filter Model_TeamRoutingRule */
			$passed = 0;

			// check criteria
			foreach($filter->criteria as $rule_key => $rule) {
				@$value = $rule['value'];
							
				switch($rule_key) {
//					case 'type':
//						if(($ticket_is_new && 0 == strcasecmp($value,'new')) 
//							|| (!$ticket_is_new && 0 == strcasecmp($value,'reply')))
//								$passed++; 
//						break;
						
					case 'tocc':
						$destinations = DevblocksPlatform::parseCsvString($value);

						// Build a list of To/Cc addresses on this message
						@$to_list = imap_rfc822_parse_adrlist($message_headers['to'],'localhost');
						@$cc_list = imap_rfc822_parse_adrlist($message_headers['cc'],'localhost');
						
						if(is_array($to_list))
						foreach($to_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						if(is_array($cc_list))
						foreach($cc_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						
						$dest_flag = false; // bail out when true
						if(is_array($destinations) && is_array($tocc))
						foreach($destinations as $dest) {
							if($dest_flag) break;
							$regexp_dest = DevblocksPlatform::strToRegExp($dest);
							
							foreach($tocc as $addy) {
								if(@preg_match($regexp_dest, $addy)) {
									$passed++;
									$dest_flag = false;
									break;
								}
							}
						}
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_from, $ticket_from->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $ticket->subject)) {
							$passed++;
						}
						break;
						
//					case 'to':
//						if(intval($ticket_group_id)==intval($value))
//							$passed++;
//						break;
						
					case 'header1':
					case 'header2':
					case 'header3':
					case 'header4':
					case 'header5':
						@$header = strtolower($rule['header']);

						if(empty($header)) {
							$passed++;
							break;
						}
						
						if(empty($value)) { // we're checking for null/blanks
							if(!isset($message_headers[$header]) || empty($message_headers[$header])) {
								$passed++;
							}
							
						} elseif(isset($message_headers[$header]) && !empty($message_headers[$header])) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// Flatten CRLF
							if(@preg_match($regexp_header, str_replace(array("\r","\n"),' ',$message_headers[$header]))) {
								$passed++;
							}
						}
						
						break;
						
//					case 'body':
//						$regexp_body = DevblocksPlatform::strToRegExp($value);
//
//						// Flatten CRLF
//						if(@preg_match($regexp_body, str_replace(array("\r","\n"),' ',$message_body)))
//							$passed++;
//						break;
						
//					case 'attachment':
//						$regexp_file = DevblocksPlatform::strToRegExp($value);
//
//						// check the files in the raw message
//						foreach($message->files as $file_name => $file) { /* @var $file ParserFile */
//							if(@preg_match($regexp_file, $file_name)) {
//								$passed++;
//								break;
//							}
//						}
//						break;
						
					default: // ignore invalids
						continue;
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the filter
			if($passed == count($filter->criteria)) {
				DAO_TeamRoutingRule::increment($filter->id); // ++ the times we've matched
				return $filter;
			}
		}
		
		return false;
	}
};

/**
 * Enter description here...
 *
 */
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

	function render() {
		echo ' '; // Expect Override
	}

	function renderCriteria($field) {
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

	function renderCriteriaParam($param) {
		$field = $param->field;
		$vals = $param->value;

		if(!is_array($vals))
		$vals	= array($vals);

		$count = count($vals);
			
		for($i=0;$i<$count;$i++) {
			echo sprintf("%s%s",
			$vals[$i],
			($i+1<$count?', ':'')
			);
		}
	}

	/**
	 * All the view's available fields
	 *
	 * @return array
	 */
	static function getFields() {
		// Expect Override
		return array();
	}

	/**
	 * All searchable fields
	 *
	 * @return array
	 */
	static function getSearchFields() {
		// Expect Override
		return array();
	}

	/**
	 * All fields that can be displayed as columns in the view
	 *
	 * @return array
	 */
	static function getColumns() {
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
		$this->renderSortBy = null;
		$this->renderSortAsc = true;
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

/**
 * Used to persist a C4_AbstractView instance and not be encumbered by
 * classloading issues (out of the session) from plugins that might have
 * concrete AbstractView implementations.
 */
class C4_AbstractViewModel {
	public $class_name = '';

	public $id = 0;
	public $name = "";
	public $view_columns = array();
	public $params = array();

	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = '';
	public $renderSortAsc = 1;
};

/**
 * This is essentially an AbstractView Factory
 */
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
			if(empty($class) || !class_exists($class))
			return null;
				
			$view = new $class;
			self::setView($view_label, $view);
			return $view;
		}

		$model = self::$views[$view_label];
		$view = self::unserializeAbstractView($model);

		return $view;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @param C4_AbstractView $view
	 */
	static function setView($view_label, $view) {
		if(is_null(self::$views)) self::_init();
		self::$views[$view_label] = self::serializeAbstractView($view);
		self::_save();
	}

	static private function _save() {
		// persist
		$visit = CerberusApplication::getVisit();
		$visit->set(self::VISIT_ABSTRACTVIEWS, self::$views);
	}

	static function serializeAbstractView($view) {
		if(!$view instanceof C4_AbstractView)
		return null;

		$model = new C4_AbstractViewModel();
			
		$model->class_name = get_class($view);

		$model->id = $view->id;
		$model->name = $view->name;
		$model->view_columns = $view->view_columns;
		$model->params = $view->params;

		$model->renderPage = $view->renderPage;
		$model->renderLimit = $view->renderLimit;
		$model->renderSortBy = $view->renderSortBy;
		$model->renderSortAsc = $view->renderSortAsc;

		return $model;
	}

	static function unserializeAbstractView(C4_AbstractViewModel $model) {
		if(null == ($inst = new $model->class_name)) /* @var $inst C4_AbstractView */
		return null;

		$inst->id = $model->id;
		$inst->name = $model->name;
		$inst->view_columns = $model->view_columns;
		$inst->params = $model->params;

		$inst->renderPage = $model->renderPage;
		$inst->renderLimit = $model->renderLimit;
		$inst->renderSortBy = $model->renderSortBy;
		$inst->renderSortAsc = $model->renderSortAsc;

		return $inst;
	}
};

class Model_Address {
	public $id;
	public $email = '';
	public $first_name = '';
	public $last_name = '';
	public $phone = '';
	public $contact_org_id = 0;
	public $num_spam = 0;
	public $num_nonspam = 0;
	public $is_banned = 0;
	public $sla_id = 0;
	public $sla_expires = 0;
	public $last_autoreply;

	function Model_Address() {}
	
	function getName() {
		return sprintf("%s%s%s",
			$this->first_name,
			(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
			$this->last_name
		);
	}
};

class Model_AddressAuth {
	public $address_id;
	public $confirm;
	public $pass;
}

class Model_AddressToWorker {
	public $address;
	public $worker_id;
	public $is_confirmed;
	public $code;
	public $code_expire;
}

class C4_TicketView extends C4_AbstractView {
	const DEFAULT_ID = 'tickets_workspace';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_NEXT_ACTION,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
		);
	}

	function getData() {
		$objects = DAO_Ticket::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$view_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/tickets/';
		$tpl->assign('view_path',$view_path);
		$tpl->assign('view', $this);

		$visit = CerberusApplication::getVisit();

		$active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);

		$results = self::getData();
		$tpl->assign('results', $results);
		
		@$ids = array_keys($results[0]);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);

		$ticket_fields = DAO_TicketField::getAll();
		$tpl->assign('ticket_fields', $ticket_fields);
		
		// Undo?
		$last_action = C4_TicketView::getLastAction($this->id);
		$tpl->assign('last_action', $last_action);
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}

		// View Quick Moves
		// [TODO] Move this into an API
		$active_worker = CerberusApplication::getActiveWorker();
		$move_counts_str = DAO_WorkerPref::get($active_worker->id,''.DAO_WorkerPref::SETTING_MOVE_COUNTS,serialize(array()));
		if(is_string($move_counts_str)) {
			// [TODO] We no longer need the move hash, do we?
			// [TODO] Phase this out.
			$category_name_hash = DAO_Bucket::getCategoryNameHash();
			$tpl->assign('category_name_hash', $category_name_hash);
			 
			$categories = DAO_Bucket::getAll();
			$tpl->assign('categories', $categories);

			@$move_counts = unserialize($move_counts_str);
			if(!empty($move_counts))
				$tpl->assign('move_to_counts', array_slice($move_counts,0,10,true));
		}

		$tpl->assign('timestamp_now', time());
		$tpl->register_modifier('prettytime', array('CerberusUtils', 'smarty_modifier_prettytime'));
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . $view_path . 'ticket_view.tpl.php');
	}

	/**
	 * This method automatically fixes any cached strange options, like 
	 * deleted custom fields.
	 *
	 */
	private function _sanitize() {
		$custom_fields = DAO_TicketField::getAll();
		$needs_save = false;
		
		// Parameter sanity check
		foreach($this->params as $pidx => $null) {
			if(substr($pidx,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($pidx,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($this->params[$pidx]);
					$needs_save = true;
				}
			}
		}
		
		// View column sanity check
		foreach($this->view_columns as $cidx => $c) {
			if(substr($c,0,3)!="cf_")
				continue;
			
			if(0 != ($cf_id = intval(substr($c,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($this->view_columns[$cidx]);
					$needs_save = true;
				}
			}
		}
		
		// Sort by sanity check
		if(substr($this->renderSortBy,0,3)=="cf_") {
			if(0 != ($cf_id = intval(substr($this->renderSortBy,3)))) {
				if(!isset($custom_fields[$cf_id])) {
					$this->renderSortBy = null;
					$needs_save = true;
				}
			}
    	}
    	
    	if($needs_save) {
    		C4_AbstractViewLoader::setView($this->id, $this);
    	}
	}
	
	function doResetCriteria() {
		$active_worker = CerberusApplication::getActiveWorker(); /* @var $active_worker CerberusWorker */
		$active_worker_memberships = $active_worker->getMemberships();
		
		$this->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
			SearchFields_Ticket::TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($active_worker_memberships)), // censor
		);
		$this->renderPage = 0;
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		$tpl_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';

		switch($field) {
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_NEXT_ACTION:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__string.tpl.php');
				break;

			case SearchFields_Ticket::TICKET_MESSAGE_CONTENT:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__fulltext.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__number.tpl.php');
				break;
					
			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__bool.tpl.php');
				break;
					
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__date.tpl.php');
				break;
					
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_spam_training.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_spam_score.tpl.php');
				break;

			case SearchFields_Ticket::TICKET_SLA_ID:
				$slas = DAO_Sla::getAll();
				$tpl->assign('slas', $slas);
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_sla.tpl.php');
				break;

			case SearchFields_Ticket::TICKET_SLA_PRIORITY:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_sla_priority.tpl.php');
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_last_action.tpl.php');
				break;

			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__worker.tpl.php');
				break;
					
			case SearchFields_Ticket::TEAM_NAME:
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);

				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);

				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_team.tpl.php');
				break;

			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$cfield_id = substr($field,3);
					$cfield = DAO_TicketField::get($cfield_id);
					switch($cfield->type) {
						case Model_TicketField::TYPE_DROPDOWN:
							$tpl->assign('cfield', $cfield);
							$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/cfield_dropdown.tpl.php');
							break;
						case Model_TicketField::TYPE_CHECKBOX:
							$tpl->assign('cfield', $cfield);
							$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/cfield_checkbox.tpl.php');
							break;
						case Model_TicketField::TYPE_DATE:
							$tpl->assign('cfield', $cfield);
							$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/cfield_date.tpl.php');
							break;
						default:
							$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__string.tpl.php');
							break;
					}
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
					$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
					continue;
					else
					$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TEAM_ID:
				$teams = DAO_Group::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($teams[$val]))
					continue;

					$strings[] = $teams[$val]->name;
				}
				echo implode(", ", $strings);
				break;
					
			case SearchFields_Ticket::TICKET_CATEGORY_ID:
				$buckets = DAO_Bucket::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "Inbox";
					} elseif(!isset($buckets[$val])) {
						continue;
					} else {
						$strings[] = $buckets[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_SLA_ID:
				$slas = DAO_Sla::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($slas[$val]))
						continue;
						$strings[] = $slas[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'O':
							$strings[] = "New Ticket";
							break;
						case 'R':
							$strings[] = "Customer Reply";
							break;
						case 'W':
							$strings[] = "Worker Reply";
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'S':
							$strings[] = "Spam";
							break;
						case 'N':
							$strings[] = "Not Spam";
							break;
						default:
							$strings[] = "Not Trained";
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Ticket::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Ticket::TEAM_ID]);
		unset($fields[SearchFields_Ticket::TICKET_CATEGORY_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Ticket::TEAM_ID]);
		unset($fields[SearchFields_Ticket::TICKET_MESSAGE_CONTENT]);
		unset($fields[SearchFields_Ticket::REQUESTER_ID]);
		unset($fields[SearchFields_Ticket::REQUESTER_ADDRESS]);
		return $fields;
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_NEXT_ACTION:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;

			case SearchFields_Ticket::TICKET_MESSAGE_CONTENT:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from) || (!is_numeric($from) && @false === strtotime(str_replace('.','-',$from))))
					$from = 0;
					
				if(empty($to) || (!is_numeric($to) && @false === strtotime(str_replace('.','-',$to))))
					$to = 'now';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				@$score = DevblocksPlatform::importGPC($_REQUEST['score'],'integer',null);
				if(!is_null($score) && is_numeric($score)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,intval($score)/100);
				}
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_SLA_ID:
				@$sla_ids = DevblocksPlatform::importGPC($_REQUEST['sla_ids'],'array',array());
				if(is_array($sla_ids) && !empty($sla_ids)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,$sla_ids);
				}
				break;

			case SearchFields_Ticket::TICKET_SLA_PRIORITY:
				@$priority = DevblocksPlatform::importGPC($_REQUEST['priority'],'integer',null);
				if(!is_null($priority) && is_numeric($priority)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,$priority);
				}
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				@$last_action_code = DevblocksPlatform::importGPC($_REQUEST['last_action'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$last_action_code);
				break;

			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;

			case SearchFields_Ticket::TEAM_NAME:
				@$team_ids = DevblocksPlatform::importGPC($_REQUEST['team_id'],'array');
				@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array');

				if(!empty($team_ids))
				$this->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,$oper,$team_ids);
				if(!empty($bucket_ids))
				$this->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,$oper,$bucket_ids);

				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$cfield_id = substr($field,3);
					$cfield = DAO_TicketField::get($cfield_id);
					switch($cfield->type) {
						case Model_TicketField::TYPE_DROPDOWN:
							@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
							if(!empty($options)) {
								$criteria = new DevblocksSearchCriteria($field,$oper,$options);
							} else {
								$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IS_NULL);
							}
							break;
						case Model_TicketField::TYPE_CHECKBOX:
							$check = !empty($value) ? 1 : 0;
							$criteria = new DevblocksSearchCriteria($field,$oper,$check);
							break;
						case Model_TicketField::TYPE_DATE:
							@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
							@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
			
							if(empty($from)) $from = 0;
							if(empty($to)) $to = 'today';
			
							$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
							break;
						default:
							if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
							&& false === (strpos($value,'*'))) {
								$value = '*'.$value.'*';
							}
							$criteria = new DevblocksSearchCriteria($field,$oper,$value);
							break;
					}
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	/**
	 * @param array
	 * @param array
	 * @return boolean
	 * [TODO] Find a better home for this?
	 */
	function doBulkUpdate($filter, $filter_param, $data, $do, $ticket_ids=array()) {
		@set_time_limit(0); // [TODO] Temp!
	  
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

				if(is_array($data))
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
								array(),
								$new_params,
								100,
								$pg++,
								SearchFields_Ticket::TICKET_ID,
								true,
								false
							);
							 
							$ticket_ids = array_merge($ticket_ids, array_keys($tickets));
							 
						} while(!empty($tickets));
					}
			   
					$batch_total = count($ticket_ids);
					for($x=0;$x<=$batch_total;$x+=200) {
						$batch_ids = array_slice($ticket_ids,$x,200);
						$action->run($batch_ids);
						unset($batch_ids);
					}
				}

				break;
		}

		unset($ticket_ids);
	}

	static function createSearchView() {
		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		$translate = DevblocksPlatform::getTranslationService();
		
		$view = new C4_TicketView();
		$view->id = CerberusApplication::VIEW_SEARCH;
		$view->name = $translate->_('common.search_results');
		$view->dashboard_id = 0;
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TEAM_NAME,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
			SearchFields_Ticket::TICKET_NEXT_ACTION,
		);
		$view->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,DevblocksSearchCriteria::OPER_EQ,0),
			SearchFields_Ticket::TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($memberships)), // censor
		);
		$view->renderLimit = 100;
		$view->renderPage = 0;
		$view->renderSortBy = null; // SearchFields_Ticket::TICKET_UPDATED_DATE
		$view->renderSortAsc = 0;

		return $view;
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
			SearchFields_Address::PHONE,
			SearchFields_Address::SLA_ID,
			SearchFields_Address::NUM_NONSPAM,
			SearchFields_Address::NUM_SPAM,
		);
		
		$this->params = array(
			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
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

		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
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
			case SearchFields_Address::PHONE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl.php');
				break;
			case SearchFields_Address::IS_BANNED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl.php');
				break;
			case SearchFields_Address::SLA_ID:
				$slas = DAO_Sla::getAll();
				$tpl->assign('slas', $slas);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/addresses/criteria/sla.tpl.php');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Address::SLA_ID:
				$slas = DAO_Sla::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($slas[$val]))
						continue;
						$strings[] = $slas[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Address::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Address::ID]);
		unset($fields[SearchFields_Address::CONTACT_ORG_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::FIRST_NAME:
			case SearchFields_Address::LAST_NAME:
			case SearchFields_Address::ORG_NAME:
			case SearchFields_Address::PHONE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Address::IS_BANNED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			case SearchFields_Address::SLA_ID:
				@$sla_ids = DevblocksPlatform::importGPC($_REQUEST['sla_ids'], 'array', array());
				$criteria = new DevblocksSearchCriteria($field, $oper, $sla_ids);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
		$change_fields = array();

		if(empty($do))
		return;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'sla':
					$change_fields[DAO_Address::SLA_ID] = intval($v);
					break;
				case 'banned':
					$change_fields[DAO_Address::IS_BANNED] = intval($v);
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Address::search(
			$this->params,
			100,
			$pg++,
			SearchFields_Address::ID,
			true,
			false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Address::update($batch_ids, $change_fields);

			// Cascade SLA changes
			if(isset($do['sla'])) {
				foreach($batch_ids as $id) {
					DAO_Sla::cascadeAddressSla($id, $do['sla']);
				}
			}

			unset($batch_ids);
		}

		unset($ids);
	}

};

class C4_AttachmentView extends C4_AbstractView {
	const DEFAULT_ID = 'attachments';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Attachments';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_Attachment::FILE_SIZE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Attachment::MIME_TYPE,
			SearchFields_Attachment::FILE_SIZE,
			SearchFields_Attachment::MESSAGE_CREATED_DATE,
			SearchFields_Attachment::ADDRESS_EMAIL,
			SearchFields_Attachment::TICKET_MASK,
		);
		
//		$this->params = array(
//			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
//		);
	}

	function getData() {
		$objects = DAO_Attachment::search(
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

//		$slas = DAO_Sla::getAll();
//		$tpl->assign('slas', $slas);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/configuration/tabs/attachments/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::FILEPATH:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
//			case SearchFields_Attachment::ID:
//			case SearchFields_Attachment::MESSAGE_ID:
//			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::FILE_SIZE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl.php');
				break;
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl.php');
				break;
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
//			case SearchFields_Attachment::SLA_ID:
//				$slas = DAO_Sla::getAll();
//				$tpl->assign('slas', $slas);
//				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/addresses/criteria/sla.tpl.php');
//				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
//			case SearchFields_Address::SLA_ID:
//				$slas = DAO_Sla::getAll();
//				$strings = array();
//
//				foreach($values as $val) {
//					if(0==$val) {
//						$strings[] = "None";
//					} else {
//						if(!isset($slas[$val]))
//						continue;
//						$strings[] = $slas[$val]->name;
//					}
//				}
//				echo implode(", ", $strings);
//				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Attachment::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Attachment::ID]);
		unset($fields[SearchFields_Attachment::MESSAGE_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
//		$this->params = array(
//			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
//		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::FILEPATH:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Attachment::ID:
			case SearchFields_Attachment::MESSAGE_ID:
			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::FILE_SIZE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
//			case SearchFields_Address::SLA_ID:
//				@$sla_ids = DevblocksPlatform::importGPC($_REQUEST['sla_ids'], 'array', array());
//				$criteria = new DevblocksSearchCriteria($field, $oper, $sla_ids);
//				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

//	function doBulkUpdate($filter, $do, $ids=array()) {
//		@set_time_limit(600); // [TODO] Temp!
//	  
//		$change_fields = array();
//
//		if(empty($do))
//		return;
//
//		if(is_array($do))
//		foreach($do as $k => $v) {
//			switch($k) {
//				case 'sla':
//					$change_fields[DAO_Address::SLA_ID] = intval($v);
//					break;
//				case 'banned':
//					$change_fields[DAO_Address::IS_BANNED] = intval($v);
//					break;
//			}
//		}
//
//		$pg = 0;
//
//		if(empty($ids))
//		do {
//			list($objects,$null) = DAO_Address::search(
//			$this->params,
//			100,
//			$pg++,
//			SearchFields_Address::ID,
//			true,
//			false
//			);
//			 
//			$ids = array_merge($ids, array_keys($objects));
//			 
//		} while(!empty($objects));
//
//		$batch_total = count($ids);
//		for($x=0;$x<=$batch_total;$x+=100) {
//			$batch_ids = array_slice($ids,$x,100);
//			DAO_Address::update($batch_ids, $change_fields);
//
//			// Cascade SLA changes
//			if(isset($do['sla'])) {
//				foreach($batch_ids as $id) {
//					DAO_Sla::cascadeAddressSla($id, $do['sla']);
//				}
//			}
//
//			unset($batch_ids);
//		}
//
//		unset($ids);
//	}

};

class C4_ContactOrgView extends C4_AbstractView {
	const DEFAULT_ID = 'contact_orgs';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Organizations';
		$this->renderSortBy = 'c_name';
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContactOrg::SLA_ID,
			SearchFields_ContactOrg::COUNTRY,
			SearchFields_ContactOrg::CREATED,
			SearchFields_ContactOrg::PHONE,
			SearchFields_ContactOrg::WEBSITE,
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

		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
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
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
			case SearchFields_ContactOrg::SLA_ID:
				$slas = DAO_Sla::getAll();
				$tpl->assign('slas', $slas);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/orgs/criteria/sla.tpl.php');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ContactOrg::SLA_ID:
				$slas = DAO_Sla::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($slas[$val]))
						continue;
						$strings[] = $slas[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_ContactOrg::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_ContactOrg::ID]);
		return $fields;
	}

	static function getColumns() {
		return self::getFields();
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
			case SearchFields_ContactOrg::SLA_ID:
				@$sla_ids = DevblocksPlatform::importGPC($_REQUEST['sla_ids'], 'array', array());
				$criteria = new DevblocksSearchCriteria($field, $oper, $sla_ids);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};

class C4_KbArticleView extends C4_AbstractView {
	const DEFAULT_ID = 'kb_overview';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Articles';
		$this->renderSortBy = 'kb_updated';
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_KbArticle::TITLE,
			SearchFields_KbArticle::UPDATED,
			SearchFields_KbArticle::VIEWS,
		);
	}

	function getData() {
		$objects = DAO_KbArticle::search(
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

//		$slas = DAO_Sla::getAll();
//		$tpl->assign('slas', $slas);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/kb/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_KbArticle::TITLE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
			case SearchFields_KbArticle::UPDATED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
			case SearchFields_KbArticle::VIEWS:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl.php');
				break;
			case SearchFields_KbArticle::CONTENT:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__fulltext.tpl.php');
				break;
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				$topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
					DAO_KbCategory::PARENT_ID,
					0
				));
				$tpl->assign('topics', $topics);

				$tpl->display('file:' . $tpl_path . 'kb/search/criteria/kb_topic.tpl.php');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				$topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
					DAO_KbCategory::PARENT_ID,
					0
				));
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($topics[$val]))
						continue;
						$strings[] = $topics[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_KbArticle::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_KbArticle::ID]);
		unset($fields[SearchFields_KbArticle::FORMAT]);
		return $fields;
	}

	static function getColumns() {
		return self::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_KbArticle::TITLE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_KbArticle::UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_KbArticle::VIEWS:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_KbArticle::CONTENT:
				$criteria = new DevblocksSearchCriteria($field, DevblocksSearchCriteria::OPER_FULLTEXT, $value);
				break;
				
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				@$topic_ids = DevblocksPlatform::importGPC($_REQUEST['topic_id'], 'array', array());
				$criteria = new DevblocksSearchCriteria($field, $oper, $topic_ids);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};

class C4_TaskView extends C4_AbstractView {
	const DEFAULT_ID = 'tasks';
	const DEFAULT_TITLE = 'All Open Tasks';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Task::PRIORITY;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::PRIORITY,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			);
		
		$this->params = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_Task::search(
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

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->assign('timestamp_now', time());
		$tpl->register_modifier('prettytime', array('CerberusUtils', 'smarty_modifier_prettytime'));

		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);

//		$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
		
		// Make a list of unique source_extension and load their renderers
		$source_extensions = array();
		if(is_array($results) && isset($results[0]))
		foreach($results[0] as $rows) {
			$source_extension = $rows[SearchFields_Task::SOURCE_EXTENSION];
			if(!isset($source_extensions[$source_extension]) 
				&& !empty($source_extension)
				&& null != ($mft = DevblocksPlatform::getExtension($source_extension))) {
				$source_extensions[$source_extension] = $mft->createInstance();
			} 
		}
		$tpl->assign('source_renderers', $source_extensions);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/tasks/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_Task::TITLE:
			case SearchFields_Task::CONTENT:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
				
			case SearchFields_Task::PRIORITY:
				$tpl->display('file:' . $tpl_path . 'tasks/criteria/priority.tpl.php');
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
				$tpl->assign('sources', $source_renderers);
				$tpl->display('file:' . $tpl_path . 'tasks/criteria/source.tpl.php');
				break;
				
			case SearchFields_Task::IS_COMPLETED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl.php');
				break;
				
			case SearchFields_Task::DUE_DATE:
			case SearchFields_Task::COMPLETED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
				
			case SearchFields_Task::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl.php');
				break;
				
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Task::PRIORITY:
				foreach($values as $val) {
					switch($val) {
						case 1:
							$strings[] = $translate->_('priority.high');
							break;
						case 2:
							$strings[] = $translate->_('priority.normal');
							break;
						case 3:
							$strings[] = $translate->_('priority.low');
							break;
						case 4:
							$strings[] = $translate->_('priority.none');
							break;
					}
				}
				echo implode(", ", $strings);
				break;
				
			case SearchFields_Task::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
						$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
						continue;
					else
						$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				$sources = $ext = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);			
				$strings = array();
				
				foreach($values as $val) {
					if(!isset($sources[$val]))
						continue;
					else
						$strings[] = $sources[$val]->getSourceName();
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Task::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Task::ID]);
		unset($fields[SearchFields_Task::SOURCE_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Task::ID]);
		unset($fields[SearchFields_Task::CONTENT]);
		unset($fields[SearchFields_Task::SOURCE_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0)
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Task::TITLE:
			case SearchFields_Task::CONTENT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Task::PRIORITY:
				@$priority = DevblocksPlatform::importGPC($_REQUEST['priority'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$priority);
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				@$sources = DevblocksPlatform::importGPC($_REQUEST['sources'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$sources);
				break;
				
			case SearchFields_Task::COMPLETED_DATE:
			case SearchFields_Task::DUE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Task::IS_COMPLETED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Task::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
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
	public $sla_id;
	public $sla_expires;
};

class Model_WorkerWorkspaceList {
	public $id = 0;
	public $worker_id = 0;
	public $workspace = '';
	public $list_view = '';
	public $list_pos = 0;
};

class Model_WorkerWorkspaceListView {
	public $title = 'New List';
	//	public $workspace = '';
	public $columns = array();
	public $num_rows = 10;
	public $params = array();
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

						} elseif($v == CerberusTicketSpamTraining::SPAM) {
							foreach($ticket_ids as $ticket_id) {
								CerberusBayes::markTicketAsSpam($ticket_id);
							}
						}
							
						break;

					case 'team':
						// [TODO] Make sure the team/bucket still exists
						list($team_id,$category_id) = CerberusApplication::translateTeamCategoryCode($v);
						$fields[DAO_Ticket::TEAM_ID] = $team_id;
						$fields[DAO_Ticket::CATEGORY_ID] = $category_id;
						break;

					case 'next_worker':
					case 'assign':
						$fields[DAO_Ticket::NEXT_WORKER_ID] = intval($v);
						break;

					default:
						// [TODO] Log?
						break;
			}
		}
		//		}

		if(!empty($fields) && !empty($ticket_ids)) {
			DAO_Ticket::updateTicket($ticket_ids, $fields);
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

	public function toString(CerberusWorker $worker=null) {
		if(null == $worker)
			return;
			
		$translate = DevblocksPlatform::getTranslationService();
		$params = $this->params;

		// Prepend the worker name to the activity's param list
		array_unshift($params, sprintf("<b>%s</b>%s",
			$worker->getName(),
			(!empty($worker->title) 
				? (' (' . $worker->title . ')') 
				: ''
			)
		));
		
		return vsprintf(
			$translate->_($this->translation_code), 
			$params
		);
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

	const KEY_DASHBOARD_ID = 'cur_dashboard_id';
	const KEY_WORKSPACE_GROUP_ID = 'cur_group_id';
	const KEY_VIEW_LAST_ACTION = 'view_last_action';
	const KEY_MY_WORKSPACE = 'view_my_workspace';
	const KEY_MAIL_MODE = 'mail_mode';
	const KEY_OVERVIEW_FILTER = 'overview_filter';

	public function __construct() {
		$this->worker = null;
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
};

class C4_Overview {
	static function getGroupTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();

		// Does the active worker want to filter anything out?
		// [TODO] DAO_WorkerPref should really auto serialize/deserialize
		$hide_bucket_ids = array();
		if(null != ($hide_bucket_str = DAO_WorkerPref::get($active_worker->id, DAO_WorkerPref::SETTING_OVERVIEW_FILTER, ''))) {
			@$hide_bucket_ids = unserialize($hide_bucket_str);
		}
		
		if(empty($memberships))
			return array();
		
		// Group Loads
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
		"FROM ticket ".
		"WHERE is_waiting = 0 AND is_closed = 0 AND is_deleted = 0 ".
		"AND next_worker_id = 0 ".
		"GROUP BY team_id, category_id "
		);
		$rs_buckets = $db->Execute($sql);

		$group_counts = array();
		while(!$rs_buckets->EOF) {
			$team_id = intval($rs_buckets->fields['team_id']);
			$category_id = intval($rs_buckets->fields['category_id']);
			$hits = intval($rs_buckets->fields['hits']);
				
			if(isset($memberships[$team_id])) {
				// If the active worker is filtering out these buckets, don't total.
				if(!empty($category_id) && isset($hide_bucket_ids[$category_id])) {
					// do nothing
				} else {
					if(!isset($group_counts[$team_id]))
						$group_counts[$team_id] = array();
	
					$group_counts[$team_id][$category_id] = $hits;
					@$group_counts[$team_id]['total'] = intval($group_counts[$team_id]['total']) + $hits;
				}
			}
				
			$rs_buckets->MoveNext();
		}

		return $group_counts;
	}

	static function getWaitingTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();

		if(empty($memberships))
			return array();
		
		// Waiting For Reply Loads
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
		"FROM ticket ".
		"WHERE is_waiting = 1 AND is_closed = 0 AND is_deleted = 0 ".
//		"AND next_worker_id = 0 ".
		"GROUP BY team_id, category_id "
		);
		$rs_buckets = $db->Execute($sql);

		$waiting_counts = array();
		while(!$rs_buckets->EOF) {
			$team_id = intval($rs_buckets->fields['team_id']);
			$category_id = intval($rs_buckets->fields['category_id']);
			$hits = intval($rs_buckets->fields['hits']);
				
			if(isset($memberships[$team_id])) {
				if(!isset($waiting_counts[$team_id]))
				$waiting_counts[$team_id] = array();

				$waiting_counts[$team_id][$category_id] = $hits;
				@$waiting_counts[$team_id]['total'] = intval($waiting_counts[$team_id]['total']) + $hits;
			}
				
			$rs_buckets->MoveNext();
		}

		return $waiting_counts;
	}

	static function getWorkerTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		
		if(empty($memberships))
			return array();
		
		// Worker Loads
		$sql = sprintf("SELECT count(*) AS hits, t.team_id, t.next_worker_id ".
			"FROM ticket t ".
			"WHERE t.is_waiting = 0 AND t.is_closed = 0 AND t.is_deleted = 0 ".
			"AND t.next_worker_id > 0 ".
			"AND t.team_id IN (%s) ".
			"GROUP BY t.team_id, t.next_worker_id ",
			implode(',', array_keys($memberships))
		);
		$rs_workers = $db->Execute($sql);

		$worker_counts = array();
		while(!$rs_workers->EOF) {
			$hits = intval($rs_workers->fields['hits']);
			$team_id = intval($rs_workers->fields['team_id']);
			$worker_id = intval($rs_workers->fields['next_worker_id']);
				
			if(!isset($worker_counts[$worker_id]))
			$worker_counts[$worker_id] = array();
				
			$worker_counts[$worker_id][$team_id] = $hits;
			@$worker_counts[$worker_id]['total'] = intval($worker_counts[$worker_id]['total']) + $hits;
			$rs_workers->MoveNext();
		}

		return $worker_counts;
	}

	static function getSlaTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		
		if(empty($memberships))
			return array();
		
		// SLA Loads
		$sql = sprintf("SELECT count(*) AS hits, t.sla_id ".
			"FROM ticket t ".
			"INNER JOIN sla s ON (s.id=t.sla_id) ".
			"WHERE t.is_waiting = 0 AND t.is_closed = 0 AND t.is_deleted = 0 ".
			"AND t.next_worker_id = 0 ".
			"AND t.team_id IN (%s) ".
			"GROUP BY t.sla_id, t.sla_priority ".
			"ORDER BY t.sla_priority DESC ",
			implode(',', array_keys($memberships))
		);
		$rs_sla = $db->Execute($sql);

		$sla_counts = array();
		while(!$rs_sla->EOF) {
			$sla_id = intval($rs_sla->fields['sla_id']);
			$hits = intval($rs_sla->fields['hits']);
				
			$sla_counts[$sla_id] = $hits;
				
			@$sla_counts['total'] = intval($sla_counts['total']) + $hits;
				
			$rs_sla->MoveNext();
		}

		return $sla_counts;
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
	public $pass;
	public $title;
	public $is_superuser=0;
	public $can_delete=0;
	public $last_activity;
	public $last_activity_date;

	/**
	 * @return Model_TeamMember[]
	 */
	function getMemberships() {
		return DAO_Worker::getWorkerGroups($this->id); 
	}

	function isTeamManager($team_id) {
		@$memberships = $this->getMemberships();
		$teams = DAO_Group::getAll();
		if(
		empty($team_id) // null
		|| !isset($teams[$team_id]) // doesn't exist
		|| !isset($memberships[$team_id])  // not a member
		|| (!$memberships[$team_id]->is_manager && !$this->is_superuser) // not a manager or superuser
		){
			return false;
		}
		return true;
	}

	function isTeamMember($team_id) {
		@$memberships = $this->getMemberships();
		$teams = DAO_Group::getAll();
		if(
			empty($team_id) // null
			|| !isset($teams[$team_id]) // not a team
			|| !isset($memberships[$team_id]) // not a member
		) {
			return false;
		}
		return true;
	}
	
	function getName($reverse=false) {
		
		if(!$reverse) {
			$name = sprintf("%s%s%s",
				$this->first_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
				$this->last_name
			);
		} else {
			$name = sprintf("%s%s%s",
				$this->last_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? ", " : "",
				$this->first_name
			);
		}
		
		return $name;
	}

}

class Model_Sla {
	public $id = 0;
	public $name = '';
	public $priority = 100;
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
	const ACTION_TAKE = 'take';
	const ACTION_SURRENDER = 'surrender';
	const ACTION_WAITING = 'waiting';
	const ACTION_NOT_WAITING = 'not_waiting';

	public $ticket_ids = array(); // key = ticket id, value=old value
	public $action = ''; // spam/closed/move, etc.
	public $action_params = array(); // DAO Actions Taken
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
	public $is_waiting = 0;
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
	public $unlock_date;
	public $spam_score;
	public $spam_training;
	public $interesting_words;
	public $next_action;
	public $last_action_code;
	public $last_worker_id;
	public $next_worker_id;
	public $sla_id;
	public $sla_priority;

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
	public $created_date;
	public $address_id;
	public $is_outgoing;
	public $worker_id;

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
		$attachments = DAO_Attachment::getByMessageId($this->id);
		return $attachments;
	}

};

class Model_MessageNote {
	const TYPE_NOTE = 0;
	const TYPE_WARNING = 1;
	const TYPE_ERROR = 2;

	public $id;
	public $type;
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
	
	public function getFileSize() {
		$file_path = APP_PATH . '/storage/attachments/';
		if (!empty($this->filepath))
		return filesize($file_path.$this->filepath);
	}
	
	public static function saveToFile($file_id, $contents) {
		$attachment_path = APP_PATH . '/storage/attachments/';
		
	    // Make file attachments use buckets so we have a max per directory
		$attachment_bucket = sprintf("%03d/",
			mt_rand(1,100)
		);
		$attachment_file = $file_id;
		
		if(!file_exists($attachment_path.$attachment_bucket)) {
			@mkdir($attachment_path.$attachment_bucket, 0770, true);
			// [TODO] Needs error checking
		}
		
		file_put_contents($attachment_path.$attachment_bucket.$attachment_file, $contents);
		
		return $attachment_bucket.$attachment_file;
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
	public $name = '';
	public $team_id = 0;
	public $response_hrs = 0;
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

class Model_FnrQuery {
	public $id;
	public $query;
	public $created;
	public $source;
	public $no_match;
};

class Model_FnrExternalResource {
	public $id = 0;
	public $name = '';
	public $url = '';
	public $topic_id = 0;

	public static function searchResources($resources, $query) {
		$feeds = array();
		$topics = DAO_FnrTopic::getWhere();

		if(is_array($resources))
		foreach($resources as $resource) { /* @var $resource Model_FnrExternalResource */
			try {
				$url = str_replace("#find#",rawurlencode($query),$resource->url);
				$feed = Zend_Feed::import($url);
				if($feed->count())
					$feeds[] = array(
					'name' => $resource->name,
					'topic_name' => @$topics[$resource->topic_id]->name,
					'feed' => $feed
				);
			} catch(Exception $e) {}
		}
		
		return $feeds;
	}
};

class Model_KbArticle {
	public $id = 0;
	public $title = '';
	public $views = 0;
	public $updated = 0;
	public $format = 0;
	public $content = '';
	public $content_raw = '';
};

class Model_KbCategory {
	public $id;
	public $parent_id;
	public $name;
};

class Model_MailTemplate {
	const TYPE_COMPOSE = 1;
	const TYPE_REPLY = 2;
	const TYPE_CREATE = 3;
//	const TYPE_CLOSE = 4;
	
	public $id = 0;
	public $title = '';
	public $description = '';
	public $folder = '';
	public $owner_id = 0;
	public $template_type = 0;
	public $content = '';

	public function getRenderedContent($message_id) {
		$raw = $this->content;

		$replace = array();
		$with = array();

		if(!empty($message_id)) {
			$message = DAO_Ticket::getMessage($message_id);
			$ticket = DAO_Ticket::getTicket($message->ticket_id);
			$sender = DAO_Address::get($message->address_id);
			$sender_org = DAO_ContactOrg::get($sender->contact_org_id);

			$replace[] = '#timestamp#';
			$with[] = date('r');
			
			$replace[] = '#sender_first_name#';
			$replace[] = '#sender_last_name#';
			$replace[] = '#sender_org#';
	
			$with[] = $sender->first_name;
			$with[] = $sender->last_name;
			$with[] = (!empty($sender_org)?$sender_org->name:"");
			
			$replace[] = '#ticket_id#';
			$replace[] = '#ticket_mask#';
			$replace[] = '#ticket_subject#';

			$with[] = $ticket->id;
			$with[] = $ticket->mask;
			$with[] = $ticket->subject;
		}
			
		if(null != ($active_worker = CerberusApplication::getActiveWorker())) {
			$worker = DAO_Worker::getAgent($active_worker->id); // most recent info (not session)
			
			$replace[] = '#worker_first_name#';
			$replace[] = '#worker_last_name#';
			$replace[] = '#worker_title#';
	
			$with[] = $worker->first_name;
			$with[] = $worker->last_name;
			$with[] = $worker->title;
		}

		return str_replace($replace, $with, $raw);
	}
};

class Model_TicketComment {
	public $id;
	public $ticket_id;
	public $address_id;
	public $created;
	public $comment;
	
	public function getAddress() {
		return DAO_Address::get($this->address_id);
	}
};

class Model_TicketField {
	const TYPE_SINGLE_LINE = 'S';
	const TYPE_MULTI_LINE = 'T';
	const TYPE_CHECKBOX = 'C';
	const TYPE_DROPDOWN = 'D';
	const TYPE_DATE = 'E';
	
	public $id = 0;
	public $name = '';
	public $type = '';
	public $group_id = 0;
	public $pos = 0;
	public $options = array();
	
	static function getTypes() {
		return array(
			self::TYPE_SINGLE_LINE => 'Text: Single Line',
			self::TYPE_MULTI_LINE => 'Text: Multi-Line',
			self::TYPE_CHECKBOX => 'Checkbox',
			self::TYPE_DROPDOWN => 'Dropdown',
			self::TYPE_DATE => 'Date',
		);
	}
};

class Model_Task {
	public $id;
	public $title;
	public $worker_id;
	public $priority;
	public $due_date;
	public $is_completed;
	public $completed_date;
	public $content;
	public $source_extension;
	public $source_id;
};

?>