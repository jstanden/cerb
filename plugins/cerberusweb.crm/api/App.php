<?php
// Classes
$path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/App.php', array(
    'C4_CrmOpportunityView'
));

class CrmPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class CrmCustomFieldSource_Opportunity extends Extension_CustomFieldSource {
	const ID = 'crm.fields.source.opportunity';
};

class CrmNotesSource_Opportunity extends Extension_NoteSource {
	const ID = 'crm.notes.source.opportunity';
};

if (class_exists('Extension_ActivityTab')):
class CrmOppsActivityTab extends Extension_ActivityTab {
	const EXTENSION_ID = 'crm.activity.tab.opps';
	const VIEW_ACTIVITY_OPPS = 'activity_opps';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('core_tpl', realpath(APP_PATH . '/plugins/cerberusweb.core/templates') . DIRECTORY_SEPARATOR);
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$translate = DevblocksPlatform::getTranslationService();
		
		if(null == ($view = C4_AbstractViewLoader::getView('', self::VIEW_ACTIVITY_OPPS))) {
			$view = new C4_CrmOpportunityView();
			$view->id = self::VIEW_ACTIVITY_OPPS;
			$view->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
			$view->renderSortAsc = 0;
			
			$view->name = $translate->_('common.search_results');
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/opps');
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_CrmOpportunityView::getFields());
		$tpl->assign('view_searchable_fields', C4_CrmOpportunityView::getSearchFields());
		
		$tpl->display($tpl_path . 'crm/opps/activity_tab/index.tpl.php');		
	}
}
endif;

class CrmPage extends CerberusPageExtension {
	private $plugin_path = '';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->plugin_path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = $this->plugin_path . '/templates/';
		$tpl->assign('path', $tpl_path);

		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // crm
		
		$module = array_shift($stack); // opps
		
		switch($module) {
			default:
			case 'opps':
				@$opp_id = intval(array_shift($stack));
				if(null == ($opp = DAO_CrmOpportunity::get($opp_id))) {
					break; // [TODO] Not found
				}
				$tpl->assign('opp', $opp);						

				$address = DAO_Address::get($opp->primary_email_id);
				$tpl->assign('address', $address);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$task_count = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.opp', $opp_id);
				$tpl->assign('tasks_total', $task_count);
				
				$visit = CerberusApplication::getVisit();
				
				// Does a series exist?
				if(null != ($series_info = $visit->get('ch_opp_series', null))) {
					@$series = $series_info['series'];
					// Is this ID part of the series?  If not, invalidate
					if(!isset($series[$opp_id])) {
						$visit->set('ch_opp_series', null);
					} else {
						$series_stats = array(
							'title' => $series_info['title'],
							'total' => $series_info['total'],
							'count' => count($series)
						);
						reset($series);
						$cur = 1;
						while(current($series)) {
							$pos = key($series);
							if(intval($pos)==intval($opp_id)) {
								$series_stats['cur'] = $cur;
								if(false !== prev($series)) {
									@$series_stats['prev'] = $series[key($series)][SearchFields_CrmOpportunity::ID];
									next($series); // skip to current
								} else {
									reset($series);
								}
								next($series); // next
								@$series_stats['next'] = $series[key($series)][SearchFields_CrmOpportunity::ID];
								break;
							}
							next($series);
							$cur++;
						}
						
						$tpl->assign('series_stats', $series_stats);
					}
				}
				
				$tpl->display($tpl_path . 'crm/opps/display/index.tpl.php');
				break;
		}
	}
	
	function browseOppsAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // crm
		array_shift($stack); // browseOpps
		
		@$id = array_shift($stack);
		
		$opp = DAO_CrmOpportunity::get($id);
	
		if(empty($opp)) {
			echo "<H1>Invalid Opportunity ID.</H1>";
			return;
		}
		
		// Display series support (inherited paging from Display)
		@$view_id = array_shift($stack);
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('',$view_id);

			// Restrict to the active worker's groups
			$active_worker = CerberusApplication::getActiveWorker();
//			$memberships = $active_worker->getMemberships();
//			$view->params['tmp'] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::TEAM_ID, 'in', array_keys($memberships)); 
			
			$range = 100;
			$pos = $view->renderLimit * $view->renderPage;
			$page = floor($pos / $range);
			
			list($series, $series_count) = DAO_CrmOpportunity::search(
				$view->params,
				$range,
				$page,
				$view->renderSortBy,
				$view->renderSortAsc,
				false
			);
			
			$series_info = array(
				'title' => $view->name,
				'total' => count($series),
				'series' => $series
			);
			
			$visit->set('ch_opp_series', $series_info);
		}
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps',$opp->id)));
		exit;
	}
	
	function showOppPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('email', $email);
		
		if(!empty($opp_id) && null != ($opp = DAO_CrmOpportunity::get($opp_id))) {
			$tpl->assign('opp', $opp);
			
			if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
				$tpl->assign('address', $address);
			}
		}
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/peek.tpl.php');
	}
	
	function saveOppPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$email_str = DevblocksPlatform::importGPC($_REQUEST['emails'],'string','');
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($opp_id)) {
			$emails = DevblocksPlatform::parseCsvString($email_str);
			
			// One opportunity per provided e-mail address
			if(is_array($emails))
			foreach($emails as $email) {
				if(null == ($address = DAO_Address::lookupAddress($email, true)))
					continue;
				
				$fields = array(
					DAO_CrmOpportunity::NAME => $name,
					DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
					DAO_CrmOpportunity::CREATED_DATE => time(),
					DAO_CrmOpportunity::UPDATED_DATE => time(),
					DAO_CrmOpportunity::WORKER_ID => $worker_id,
				);
				$opp_id = DAO_CrmOpportunity::create($fields);
				
				// If we're adding a first comment
				if(!empty($comment)) {
					$fields = array(
						DAO_Note::CREATED => time(),
						DAO_Note::SOURCE_EXTENSION_ID => CrmNotesSource_Opportunity::ID,
						DAO_Note::SOURCE_ID => $opp_id,
						DAO_Note::CONTENT => $comment,
						DAO_Note::WORKER_ID => $active_worker->id,
					);
					$comment_id = DAO_Note::create($fields);
				}
			}
			
		} else {
			if(empty($opp_id))
				return;
			
			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
//				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::WORKER_ID => $worker_id,
			);
			DAO_CrmOpportunity::update($opp_id, $fields);
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showOppTasksTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);
		
		$view = C4_AbstractViewLoader::getView('C4_TaskView', 'opp_tasks');
		$view->id = 'opp_tasks';
		$view->name = 'Opportunity Tasks';
		$view->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::PRIORITY,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			SearchFields_Task::COMPLETED_DATE,
		);
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.opp'),
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$opp_id),
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
//		$view->name = "Most recent tickets from " . htmlentities($contact->email);
//		$view->params = array(
//			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_EQ,$contact->email)
//		);
//		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/tasks.tpl.php');
	}
	
	function showOppMailTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		$address = DAO_Address::get($opp->primary_email_id);
		$tpl->assign('address', $address);
		
		$view = C4_AbstractViewLoader::getView('C4_TicketView', 'opp_tickets');
		$view->id = 'opp_tickets';
		$view->name = 'Open Tickets';
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TEAM_NAME,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_NEXT_ACTION,
			SearchFields_Ticket::TICKET_NEXT_WORKER_ID,
		);
		$view->params = array(
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array($opp->primary_email_id)),
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
		);
		$view->name = "Requester: " . $address->email;
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/mail.tpl.php');
	}
	
	function showOppNotesTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		list($notes, $null) = DAO_Note::search(
			array(
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_EXT_ID,'=',CrmNotesSource_Opportunity::ID),
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_ID,'=',$opp->id),
			),
			25,
			0,
			DAO_Note::CREATED,
			false,
			false
		);
		$tpl->assign('notes', $notes);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);

		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);
				
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/notes.tpl.php');
	}
	
	function saveOppNoteAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer', 0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($opp_id) && 0 != strlen(trim($content))) {
			$fields = array(
				DAO_Note::SOURCE_EXTENSION_ID => CrmNotesSource_Opportunity::ID,
				DAO_Note::SOURCE_ID => $opp_id,
				DAO_Note::WORKER_ID => $active_worker->id,
				DAO_Note::CREATED => time(),
				DAO_Note::CONTENT => $content,
			);
			$note_id = DAO_Note::create($fields);
		}
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		
		// Worker notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=crm&a=opps&id='.$opp_id,true),
				DAO_WorkerEvent::TITLE => 'New Opportunity Note', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("%s\n%s notes: %s", $opp->name, $active_worker->getName(), $content), // [TODO] Translate
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opp',$opp_id)));
	}
	
	// [TODO] This is redundant and should be handled by ?c=internal by passing a $return_path
	function deleteOppNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($note = DAO_Note::get($id))) {
			if($note->worker_id == $active_worker->id || $active_worker->is_superuser) {
				DAO_Note::delete($id);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opp',$opp_id)));
	}
	
//	function showOppWonPanelAction() {
//		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
//		$tpl->assign('path', $tpl_path);
//
//		$opp = DAO_CrmOpportunity::get($opp_id);
//		$tpl->assign('opp', $opp);
//		
//		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/won.tpl.php');
//	}
	
	function saveOppWonPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer');
		
		$fields = array(
			DAO_CrmOpportunity::CLOSED_DATE => time(),
			DAO_CrmOpportunity::IS_CLOSED => 1,
			DAO_CrmOpportunity::IS_WON => 1,
		);
		DAO_CrmOpportunity::update($opp_id, $fields);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps',$opp_id)));
	}
	
//	function showOppLostPanelAction() {
//		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
//		$tpl->assign('path', $tpl_path);
//
//		$opp = DAO_CrmOpportunity::get($opp_id);
//		$tpl->assign('opp', $opp);
//		
//		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/lost.tpl.php');
//	}
	
	function saveOppLostPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer');
		
		$fields = array(
			DAO_CrmOpportunity::CLOSED_DATE => time(),
			DAO_CrmOpportunity::IS_CLOSED => 1,
			DAO_CrmOpportunity::IS_WON => 0,
		);
		DAO_CrmOpportunity::update($opp_id, $fields);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps',$opp_id)));
	}
	
	function reopenOppAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer');
		
		$fields = array(
			DAO_CrmOpportunity::CLOSED_DATE => 0,
			DAO_CrmOpportunity::IS_CLOSED => 0,
			DAO_CrmOpportunity::IS_WON => 0,
		);
		DAO_CrmOpportunity::update($opp_id, $fields);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps',$opp_id)));
	}
};

class DAO_CrmOpportunity extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const PRIMARY_EMAIL_ID = 'primary_email_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const CLOSED_DATE = 'closed_date';
	const IS_WON = 'is_won';
	const IS_CLOSED = 'is_closed';
	const WORKER_ID = 'worker_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('crm_opportunity_seq');
		
		$sql = sprintf("INSERT INTO crm_opportunity (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'crm_opportunity', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('crm_opportunity', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmOpportunity[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, primary_email_id, created_date, updated_date, closed_date, is_won, is_closed, worker_id ".
			"FROM crm_opportunity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmOpportunity	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_CrmOpportunity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_CrmOpportunity();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$object->primary_email_id = intval($rs->fields['primary_email_id']);
			$object->created_date = $rs->fields['created_date'];
			$object->updated_date = $rs->fields['updated_date'];
			$object->closed_date = $rs->fields['closed_date'];
			$object->is_won = $rs->fields['is_won'];
			$object->is_closed = $rs->fields['is_closed'];
			$object->worker_id = $rs->fields['worker_id'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM crm_opportunity WHERE id IN (%s)", $ids_list));
		
		return true;
	}

    /**
     * Enter description here...
     *
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, array(), SearchFields_CrmOpportunity::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"o.id as %s, ".
			"o.name as %s, ".
			"org.id as %s, ".
			"org.name as %s, ".
			"org.website as %s, ".
			"o.primary_email_id as %s, ".
			"a.email as %s, ".
			"o.created_date as %s, ".
			"o.updated_date as %s, ".
			"o.closed_date as %s, ".
			"o.is_closed as %s, ".
			"o.is_won as %s, ".
			"o.worker_id as %s ".
			"FROM crm_opportunity o ".
			"LEFT JOIN address a ON (a.id = o.primary_email_id) ".
			"LEFT JOIN contact_org org ON (org.id = a.contact_org_id) ",
			    SearchFields_CrmOpportunity::ID,
			    SearchFields_CrmOpportunity::NAME,
			    SearchFields_CrmOpportunity::ORG_ID,
			    SearchFields_CrmOpportunity::ORG_NAME,
			    SearchFields_CrmOpportunity::ORG_WEBSITE,
			    SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			    SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			    SearchFields_CrmOpportunity::CREATED_DATE,
			    SearchFields_CrmOpportunity::UPDATED_DATE,
			    SearchFields_CrmOpportunity::CLOSED_DATE,
			    SearchFields_CrmOpportunity::IS_CLOSED,
			    SearchFields_CrmOpportunity::IS_WON,
			    SearchFields_CrmOpportunity::WORKER_ID
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['m']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_CrmOpportunity::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
	
};

class SearchFields_CrmOpportunity implements IDevblocksSearchFields {
	// Table
	const ID = 'o_id';
	const PRIMARY_EMAIL_ID = 'o_primary_email_id';
	const NAME = 'o_name';
	const CREATED_DATE = 'o_created_date';
	const UPDATED_DATE = 'o_updated_date';
	const CLOSED_DATE = 'o_closed_date';
	const IS_WON = 'o_is_won';
	const IS_CLOSED = 'o_is_closed';
	const WORKER_ID = 'o_worker_id';
	
	const ORG_ID = 'org_id';
	const ORG_NAME = 'org_name';
	const ORG_WEBSITE = 'org_website';

	const EMAIL_ADDRESS = 'a_email';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'o', 'id', null, $translate->_('crm.opportunity.id')),
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'o', 'primary_email_id', null, $translate->_('crm.opportunity.primary_email_id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'o', 'name', null, $translate->_('crm.opportunity.name')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'o', 'created_date', null, $translate->_('crm.opportunity.created_date')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'o', 'updated_date', null, $translate->_('crm.opportunity.updated_date')),
			self::CLOSED_DATE => new DevblocksSearchField(self::CLOSED_DATE, 'o', 'closed_date', null, $translate->_('crm.opportunity.closed_date')),
			self::IS_WON => new DevblocksSearchField(self::IS_WON, 'o', 'is_won', null, $translate->_('crm.opportunity.is_won')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'o', 'is_closed', null, $translate->_('crm.opportunity.is_closed')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'o', 'worker_id', null, $translate->_('crm.opportunity.worker_id')),
			
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'org', 'id', null),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'org', 'name', null, $translate->_('crm.opportunity.org_name')),
			self::ORG_WEBSITE => new DevblocksSearchField(self::ORG_WEBSITE, 'org', 'website', null, $translate->_('crm.opportunity.org_website')),
			
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'a', 'email', null, $translate->_('crm.opportunity.email_address')),
		);
	}
};	

class Model_CrmOpportunity {
	public $id;
	public $name;
	public $primary_email_id;
	public $created_date;
	public $updated_date;
	public $closed_date;
	public $is_won;
	public $is_closed;
	public $worker_id;
};

class C4_CrmOpportunityView extends C4_AbstractView {
	const DEFAULT_ID = 'crm_opportunities';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Opportunities';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::UPDATED_DATE,
		);
		
		$this->params = array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_CrmOpportunity::search(
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
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.crm/templates/crm/opps/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::ORG_WEBSITE:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl.php');
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
				
			case SearchFields_CrmOpportunity::WORKER_ID:
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
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_CrmOpportunity::WORKER_ID:
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
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	// [TODO] change globally to getColumnFields() in AbstractView
	static function getFields() {
		$fields = SearchFields_CrmOpportunity::getFields();
		return $fields;
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_CrmOpportunity::ID]);
		unset($fields[SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID]);
		unset($fields[SearchFields_CrmOpportunity::ORG_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_CrmOpportunity::ID]);
		unset($fields[SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID]);
		unset($fields[SearchFields_CrmOpportunity::ORG_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::ORG_WEBSITE:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:		
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_CrmOpportunity::WORKER_ID:
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

class CrmTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return realpath(dirname(__FILE__).'/../') . '/strings.xml';
	}
};

// [TODO] Can possibly remove this listener
class CrmEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
//        switch($event->id) {
//            case 'address.peek.saved':
//            	break;
//        }
    }
};

class CrmTaskSource_Opp extends Extension_TaskSource {
	function getSourceName() {
		return "Opportunities";
	}
	
	function getSourceInfo($object_id) {
		if(null == ($opp = DAO_CrmOpportunity::get($object_id)))
			return;
		
		$url = DevblocksPlatform::getUrlService();
		return array(
			'name' => '[Opp] '.$opp->name,
			'url' => $url->write(sprintf('c=crm&a=opps&id=%d',$opp->id)),
		);
	}
};

class CrmOrgOppTab extends Extension_OrgTab {
	function showTab() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$org = DAO_ContactOrg::get($org_id);
		$tpl->assign('org_id', $org_id);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', 'org_opps'))) {
			$view = new C4_CrmOpportunityView();
			$view->id = 'org_opps';
		}
		
		$view->name = "Org: " . $org->name;
		$view->params = array(
			SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org_id) 
		);

		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/org/tab.tpl.php');
	}
	
	function saveTab() {
	}
};

class CrmTicketOppTab extends Extension_TicketTab {
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		
		$address = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('address', $address);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', 'ticket_opps'))) {
			$view = new C4_CrmOpportunityView();
			$view->id = 'ticket_opps';
		}

		if(!empty($address->contact_org_id)) { // org
			@$org = DAO_ContactOrg::get($address->contact_org_id);
			
			$view->name = "Org: " . $org->name;
			$view->params = array(
				SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org->id) 
			);
			
		} else { // address
			$view->name = "Requester: " . $address->email;
			$view->params = array(
				SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,'=',$ticket->first_wrote_address_id) 
			);
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/ticket/tab.tpl.php');
	}
	
	function saveTab() {
	}
};
?>