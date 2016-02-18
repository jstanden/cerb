<?php
class DAO_WebApiCredentials extends Cerb_ORMHelper {
	const _CACHE_ALL = 'dao_webapi_credentials_all';
	
	const ID = 'id';
	const LABEL = 'label';
	const WORKER_ID = 'worker_id';
	const ACCESS_KEY = 'access_key';
	const SECRET_KEY = 'secret_key';
	const PARAMS_JSON = 'params_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO webapi_credentials () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'webapi_credentials', $fields);
		
		self::clearCache();
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WebApiCredentials[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, label, worker_id, access_key, secret_key, params_json ".
			"FROM webapi_credentials ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;

		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WebApiCredentials
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param boolean $nocache
	 * @return <Model_WebApiCredentials[], NULL, array>
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();

		if($nocache || null === ($credentials = $cache->load(self::_CACHE_ALL))) {
			$credentials = self::getWhere(
				null,
				null,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			$cache->save($credentials, self::_CACHE_ALL);
		}
		
		return $credentials;
	}
	
	static function getByAccessKey($access_key) {
		$credentials = self::getAll();
		
		foreach($credentials as $credential) { /* @var $credential Model_WebApiCredential */
			if($credential->access_key == $access_key)
				return $credential;
		}
		
		return false;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WebApiCredentials[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WebApiCredentials();
			$object->id = $row['id'];
			$object->label = $row['label'];
			$object->worker_id = $row['worker_id'];
			$object->access_key = $row['access_key'];
			$object->secret_key = $row['secret_key'];
			
			@$params = json_decode($row['params_json'], true);
			$object->params = !empty($params) ? $params : array();
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM webapi_credentials WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WebApiCredentials::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"webapi_credentials.id as %s, ".
			"webapi_credentials.label as %s, ".
			"webapi_credentials.worker_id as %s, ".
			"webapi_credentials.access_key as %s ",
				SearchFields_WebApiCredentials::ID,
				SearchFields_WebApiCredentials::LABEL,
				SearchFields_WebApiCredentials::WORKER_ID,
				SearchFields_WebApiCredentials::ACCESS_KEY
			);
			
		$join_sql = "FROM webapi_credentials ";
		
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields);
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_WebApiCredentials', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'webapi_credentials',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		//$from_context = CerberusContexts::CONTEXT_EXAMPLE;
		//$from_index = 'example.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			/*
			case SearchFields_EXAMPLE::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
			*/
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY webapi_credentials.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
		} else {
			$rs = $db->ExecuteSlave($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_WebApiCredentials::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT webapi_credentials.id) " : "SELECT COUNT(webapi_credentials.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
};

class SearchFields_WebApiCredentials implements IDevblocksSearchFields {
	const ID = 'w_id';
	const LABEL = 'w_label';
	const WORKER_ID = 'w_worker_id';
	const ACCESS_KEY = 'w_access_key';
	const SECRET_KEY = 'w_secret_key';
	const PARAMS_JSON = 'w_params_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'webapi_credentials', 'id', $translate->_('common.id'), null, true),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'webapi_credentials', 'label', $translate->_('common.label'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'webapi_credentials', 'worker_id', $translate->_('common.worker'), Model_CustomField::TYPE_WORKER, true),
			self::ACCESS_KEY => new DevblocksSearchField(self::ACCESS_KEY, 'webapi_credentials', 'access_key', $translate->_('dao.webapi_credentials.access_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::SECRET_KEY => new DevblocksSearchField(self::SECRET_KEY, 'webapi_credentials', 'secret_key', $translate->_('dao.webapi_credentials.secret_key'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'webapi_credentials', 'params_json', $translate->_('dao.webapi_credentials.params_json'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_WebApiCredentials {
	public $id;
	public $label;
	public $worker_id;
	public $access_key;
	public $secret_key;
	public $params = array();
};

class View_WebApiCredentials extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'webapi_credentials';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Web API Credentials');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_WebApiCredentials::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_WebApiCredentials::LABEL,
			SearchFields_WebApiCredentials::ACCESS_KEY,
			SearchFields_WebApiCredentials::WORKER_ID,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_WebApiCredentials::ID,
			SearchFields_WebApiCredentials::PARAMS_JSON,
			SearchFields_WebApiCredentials::SECRET_KEY,
		));
		
		$this->addParamsHidden(array(
			SearchFields_WebApiCredentials::ID,
			SearchFields_WebApiCredentials::PARAMS_JSON,
			SearchFields_WebApiCredentials::SECRET_KEY,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WebApiCredentials::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_WebApiCredentials', $size);
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_WebApiCredentials::getFields();
		
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WebApiCredentials::LABEL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'accessKey' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WebApiCredentials::ACCESS_KEY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'label' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WebApiCredentials::LABEL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_WebApiCredentials::WORKER_ID),
				),
		);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamsFromQuickSearchFields($fields) {
		$search_fields = $this->getQuickSearchFields();
		$params = DevblocksSearchCriteria::getParamsFromQueryFields($fields, $search_fields);

		// Handle virtual fields and overrides
		if(is_array($fields))
		foreach($fields as $k => $v) {
			switch($k) {
				// ...
			}
		}
		
		return $params;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.restapi::view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WebApiCredentials::LABEL:
			case SearchFields_WebApiCredentials::ACCESS_KEY:
			case SearchFields_WebApiCredentials::SECRET_KEY:
			case SearchFields_WebApiCredentials::PARAMS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_WebApiCredentials::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_WebApiCredentials::WORKER_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__worker.tpl');
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
		}
	}

	function getFields() {
		return SearchFields_WebApiCredentials::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WebApiCredentials::ACCESS_KEY:
			case SearchFields_WebApiCredentials::LABEL:
			case SearchFields_WebApiCredentials::PARAMS:
			case SearchFields_WebApiCredentials::SECRET_KEY:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_WebApiCredentials::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_WebApiCredentials::WORKER_ID:
				$criteria = $this->_doSetCriteriaWorker($field, $oper);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_WebApiCredentials::EXAMPLE] = 'some value';
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_WebApiCredentials::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_WebApiCredentials::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_WebApiCredentials::update($batch_ids, $change_fields);
			}

			unset($batch_ids);
		}

		unset($ids);
	}
};
