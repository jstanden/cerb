<?php
class CallsAjaxController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // calls.ajax

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	function showEntryAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		/*
		 * [IMPORTANT -- Yes, this is simply a line in the sand.]
		 * You're welcome to modify the code to meet your needs, but please respect 
		 * our licensing.  Buy a legitimate copy to help support the project!
		 * http://www.cerberusweb.com/
		 */
		$license = CerberusLicense::getInstance();
//		if(empty($id) && (empty($license['serial']) || (!empty($license['serial']) && !empty($license['users'])))
//			&& 10 <= DAO_TimeTrackingEntry::getItemCount()) {
//			$tpl->display('file:' . $tpl_path . 'calls/ajax/trial.tpl');
//			return;
//		}
		
		/*
		 * This treats procedurally created model objects
		 * the same as existing objects
		 */ 
//		if(!empty($id)) { // Were we given a model ID to load?
//			if(null != ($model = DAO_TimeTrackingEntry::get($id)))
//				$tpl->assign('model', $model);
//		} elseif (!empty($model)) { // Were we passed a model object without an ID?
//			$tpl->assign('model', $model);
//		}
		
		// Org Name
//		if(!empty($model->debit_org_id)) {
//			if(null != ($org = DAO_ContactOrg::get($model->debit_org_id)))
//				$tpl->assign('org', $org);
//		}

		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'calls/ajax/call_entry_panel.tpl');
	}
	
	function saveEntryAction() {
		
	}
};

if (class_exists('Extension_ActivityTab')):
class CallsActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_CALLS = 'activity_calls';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', self::VIEW_ACTIVITY_CALLS))) {
			$view = new C4_CallEntryView();
			$view->id = self::VIEW_ACTIVITY_CALLS;
			$view->renderSortBy = SearchFields_CallEntry::UPDATED_DATE;
			$view->renderSortAsc = 0;
			
			$view->name = "Calls";
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/calls');

		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_CallEntryView::getFields());
		$tpl->assign('view_searchable_fields', C4_CallEntryView::getSearchFields());
		
		$tpl->display($tpl_path . 'activity_tab/index.tpl');		
	}
}
endif;

class DAO_CallEntry extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const PHONE = 'phone';
	const ORG_ID = 'org_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const WORKER_ID = 'worker_id';
	const IS_CLOSED = 'is_closed';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('call_entry_seq');
		
		$sql = sprintf("INSERT INTO call_entry (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'call_entry', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_CallEntry[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, phone, org_id, created_date, updated_date, worker_id, is_closed ".
			"FROM call_entry ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY updated_date desc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CallEntry	 */
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
	 * @return Model_CallEntry[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_CallEntry();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$object->phone = $rs->fields['phone'];
			$object->org_id = $rs->fields['org_id'];
			$object->created_date = $rs->fields['created_date'];
			$object->updated_date = $rs->fields['updated_date'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->is_closed = $rs->fields['is_closed'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM call_entry WHERE id IN (%s)", $ids_list));
		
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
		$fields = SearchFields_CallEntry::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"c.id as %s, ".
			"c.name as %s, ".
			"c.phone as %s, ".
			"c.org_id as %s, ".
			"c.created_date as %s, ".
			"c.updated_date as %s, ".
			"c.worker_id as %s, ".
			"c.is_closed as %s ",
			    SearchFields_CallEntry::ID,
			    SearchFields_CallEntry::NAME,
			    SearchFields_CallEntry::PHONE,
			    SearchFields_CallEntry::ORG_ID,
			    SearchFields_CallEntry::CREATED_DATE,
			    SearchFields_CallEntry::UPDATED_DATE,
			    SearchFields_CallEntry::WORKER_ID,
			    SearchFields_CallEntry::IS_CLOSED
			 );
		
		$join_sql = 
			"FROM call_entry c "
//			"LEFT JOIN address a ON (f.quote_address_id=a.id) "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=tt.debit_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .  
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_CallEntry::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }
	
};

class Model_CallEntry {
	public $id;
	public $name;
	public $phone;
	public $org_id;
	public $created_date;
	public $updated_date;
	public $worker_id;
	public $is_closed;
};

class SearchFields_CallEntry {
	// Feedback_Entry
	const ID = 'c_id';
	const NAME = 'c_name';
	const PHONE = 'c_phone';
	const ORG_ID = 'c_org_id';
	const CREATED_DATE = 'c_created_date';
	const UPDATED_DATE = 'c_updated_date';
	const WORKER_ID = 'c_worker_id';
	const IS_CLOSED = 'c_is_closed';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'c', 'id', null, $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'c', 'name', null, $translate->_('call_entry.model.name')),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'c', 'phone', null, $translate->_('call_entry.model.phone')),
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'c', 'org_id', null, $translate->_('contact_org.name')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'c', 'created_date', null, $translate->_('call_entry.model.created_date')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'c', 'updated_date', null, $translate->_('call_entry.model.updated_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'c', 'worker_id', null, $translate->_('call_entry.model.worker_id')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'c', 'is_closed', null, $translate->_('call_entry.model.is_closed')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class C4_CallEntryView extends C4_AbstractView {
	const DEFAULT_ID = 'call_entries';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('common.search_results');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_CallEntry::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_CallEntry::NAME,
			SearchFields_CallEntry::PHONE,
			SearchFields_CallEntry::UPDATED_DATE,
			SearchFields_CallEntry::WORKER_ID,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CallEntry::search(
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
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.calls/templates/calls/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CallEntry::NAME:
			case SearchFields_CallEntry::PHONE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_CallEntry::ID:
//				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl');
//				break;
			case SearchFields_CallEntry::CREATED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_CallEntry::IS_CLOSED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_CallEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
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
			case SearchFields_CallEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "Nobody";
					} else {
						if(!isset($workers[$val]))
							continue;
						$strings[] = $workers[$val]->getName();
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
		return SearchFields_CallEntry::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_CallEntry::ID]);
		unset($fields[SearchFields_CallEntry::ORG_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_CallEntry::ID]);
		unset($fields[SearchFields_CallEntry::ORG_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_CallEntry::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CallEntry::IS_CLOSED,DevblocksSearchCriteria::OPER_EQ,0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CallEntry::NAME:
			case SearchFields_CallEntry::PHONE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			
//			case SearchFields_CallEntry::ID:
//				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
//				break;
				
			case SearchFields_CallEntry::IS_CLOSED:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CallEntry::CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
			case SearchFields_CallEntry::WORKER_ID:
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

// Workspace Sources

class ChWorkspaceSource_Call extends Extension_WorkspaceSource {
	const ID = 'calls.workspace.source.call';
};

?>