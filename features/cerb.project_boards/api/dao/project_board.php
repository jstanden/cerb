<?php
class DAO_ProjectBoard extends Cerb_ORMHelper {
	const COLUMNS_JSON = 'columns_json';
	const ID = 'id';
	const NAME = 'name';
	const CARDS_KATA = 'cards_kata';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::COLUMNS_JSON)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// mediumtext
		$validation
			->addField(self::CARDS_KATA)
			->string()
			->setMaxLength(16777215)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// varchar(255)
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			;
		// int(10) unsigned
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		$context = Context_ProjectBoard::ID;
		
		$sql = "INSERT INTO project_board () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations($context, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		self::_updateAbstract(Context_ProjectBoard::ID, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				//CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'project_board', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.project_board.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				//DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('project_board', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = Context_ProjectBoard::ID;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ProjectBoard[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, cards_kata, owner_context, owner_context_id, updated_at, columns_json ".
			"FROM project_board ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_ProjectBoard[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ProjectBoard::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ProjectBoard
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_ProjectBoard[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ProjectBoard[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ProjectBoard();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->cards_kata = $row['cards_kata'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->updated_at = $row['updated_at'];

			@$json = json_decode($row['columns_json'], true);
			$object->columns = (false !== $json) ? $json : [];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('project_board');
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return;
		
		// Delete project columns
		DAO_ProjectBoardColumn::deleteByProjectIds($ids);

		// Delete boards
		$ids_list = implode(',', $ids);
		$db->ExecuteMaster(sprintf("DELETE FROM project_board WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => Context_ProjectBoard::ID,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ProjectBoard::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ProjectBoard', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"project_board.id as %s, ".
			"project_board.name as %s, ".
			"project_board.owner_context as %s, ".
			"project_board.owner_context_id as %s, ".
			"project_board.updated_at as %s ",
				SearchFields_ProjectBoard::ID,
				SearchFields_ProjectBoard::NAME,
				SearchFields_ProjectBoard::OWNER_CONTEXT,
				SearchFields_ProjectBoard::OWNER_CONTEXT_ID,
				SearchFields_ProjectBoard::UPDATED_AT
			);
			
		$join_sql = "FROM project_board ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ProjectBoard');
	
		return [
			'primary_table' => 'project_board',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}
	
	/**
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_ProjectBoard::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}

};

class SearchFields_ProjectBoard extends DevblocksSearchFields {
	const ID = 'p_id';
	const NAME = 'p_name';
	const OWNER_CONTEXT = 'p_owner_context';
	const OWNER_CONTEXT_ID = 'p_owner_context_id';
	const UPDATED_AT = 'p_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'project_board.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			Context_ProjectBoard::ID => new DevblocksSearchFieldContextKeys('project_board.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, Context_ProjectBoard::ID, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(Context_ProjectBoard::ID), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, Context_ProjectBoard::ID, self::getPrimaryKey());
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'project_board', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'project_board', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'project_board', 'owner_context', null, null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'project_board', 'owner_context_id', null, null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'project_board', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ProjectBoard {
	public $id = 0;
	public $cards_kata = null;
	public $name = null;
	public $owner_context = null;
	public $owner_context_id = 0;
	public $updated_at = 0;
	public $columns = [];
	
	/**
	 * @param DevblocksDictionaryDelegate $card
	 * @return array|null
	 */
	function getCardSheet(DevblocksDictionaryDelegate $card) {
		$active_worker = CerberusApplication::getActiveWorker();
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$dict = clone $card;
		$dict->mergeKeys('board_', DevblocksDictionaryDelegate::getDictionaryFromModel($this, Context_ProjectBoard::ID));
		
		if($active_worker)
			$dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
		
		$card_handlers = $event_handler->parse($this->cards_kata, $dict);
		
		if(null == ($results = $event_handler->handleOnce(
			AutomationTrigger_ProjectBoardRenderCard::ID,
			$card_handlers,
			$card->getDictionary()
			)))
			return null;
		
		if('return' == $results->getKeyPath('__exit')) {
			return $results->getKeyPath('__return.sheet', null);
		}
		
		return null;
	}
	
	/**
	 * 
	 * @return Model_ProjectBoardColumn[]
	 */
	function getColumns() {
		$columns = DAO_ProjectBoardColumn::getByBoardId($this->id);
		
		if(is_array($this->columns)) {
			$sort = array_flip($this->columns);
		} else {
			$sort = [];
		}
		
		uksort($columns, function($a, $b) use ($sort) {
			$a_pos = isset($sort[$a]) ? $sort[$a] : PHP_INT_MAX;
			$b_pos = isset($sort[$b]) ? $sort[$b] : PHP_INT_MAX;
			
			if($a_pos == $b_pos)
				return 0;
			
			return ($a_pos < $b_pos) ? -1 : 1;
		});
		
		return $columns;
	}
	
	function renderCard(DevblocksDictionaryDelegate $card, Model_ProjectBoardColumn $column=null) {
		$tpl = DevblocksPlatform::services()->template();
		$sheets = DevblocksPlatform::services()->sheet();
		
		$dict = DevblocksDictionaryDelegate::instance($card->getDictionary(null, false, 'card_'));
		
		// [TODO] Set this earlier (and expanded)
		$dict->set('column__context', Context_ProjectBoardColumn::ID);
		$dict->set('column_id', $this->id);
		
		// Try the column first
		$sheet_schema = $column->getCardSheet($dict);
		
		// Then try the board
		if(!$sheet_schema) {
			if(false == ($board = $column->getProjectBoard()))
				return null;
			
			$sheet_schema = $board->getCardSheet($dict);
		}
		
		if(!is_array($sheet_schema)) {
			$sheet_schema = [
				'columns' => [
					'card/card__label' => [],
				],
			];
		}
		
		$sheets->addType('card', $sheets->types()->card());
		$sheets->addType('date', $sheets->types()->date());
		$sheets->addType('selection', $sheets->types()->selection());
		$sheets->addType('icon', $sheets->types()->icon());
		$sheets->addType('link', $sheets->types()->link());
		$sheets->addType('slider', $sheets->types()->slider());
		$sheets->addType('text', $sheets->types()->text());
		$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
		$sheets->setDefaultType('text');
		
		$columns = $sheets->getColumns($sheet_schema);
		$tpl->assign('columns', $columns);
		
		// Otherwise use defaults
		if(!$sheet_schema) {
			$sheet_schema = [
				'layout' => [
					'style' => 'fieldsets',
					'paging' => 'false',
					'title_column' => 'card__label',
				],
				'columns' => [
					'card/card__label' => [],
				],
			];
		} else {
			if(!array_key_exists('layout', $sheet_schema)) {
				$sheet_schema['layout'] = [
					'style' => 'fieldsets',
					'paging' => 'false',
					'title_column' => key($columns),
				];
			}
		}
		
		$layout = $sheets->getLayout($sheet_schema);
		$tpl->assign('layout', $layout);
		
		$rows = $sheets->getRows($sheet_schema, [$dict]);
		$tpl->assign('rows', $rows);
		
		$html = $tpl->fetch('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_sheet_fieldsets.tpl');
		
		echo $html;
	}
};

class View_ProjectBoard extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'project_boards';

	function __construct() {
		$this->id = self::DEFAULT_ID;

		$this->name = DevblocksPlatform::translateCapitalized('projects.common.boards');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ProjectBoard::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ProjectBoard::NAME,
			SearchFields_ProjectBoard::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_ProjectBoard::OWNER_CONTEXT,
			SearchFields_ProjectBoard::OWNER_CONTEXT_ID,
			SearchFields_ProjectBoard::VIRTUAL_CONTEXT_LINK,
			SearchFields_ProjectBoard::VIRTUAL_HAS_FIELDSET,
			SearchFields_ProjectBoard::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_ProjectBoard::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ProjectBoard');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ProjectBoard', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ProjectBoard', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
//				case SearchFields_ProjectBoard::EXAMPLE:
//					$pass = true;
//					break;
					
				// Virtuals
				case SearchFields_ProjectBoard::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ProjectBoard::VIRTUAL_HAS_FIELDSET:
				case SearchFields_ProjectBoard::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();
		$context = Context_ProjectBoard::ID;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
//			case SearchFields_ProjectBoard::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_ProjectBoard::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
				
			case SearchFields_ProjectBoard::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_ProjectBoard::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_ProjectBoard::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_ProjectBoard::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProjectBoard::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ProjectBoard::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_ProjectBoard::ID],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ProjectBoard::ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_ProjectBoard::ID, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProjectBoard::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ProjectBoard::UPDATED_AT),
				),
			'watchers' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ProjectBoard::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				],
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_ProjectBoard::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_ProjectBoard::ID, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	// [TODO] Implement quick search fields
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_ProjectBoard::VIRTUAL_WATCHERS, $tokens);
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(Context_ProjectBoard::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerb.project_boards::boards/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ProjectBoard::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_ProjectBoard::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_ProjectBoard::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ProjectBoard::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ProjectBoard::NAME:
			case SearchFields_ProjectBoard::OWNER_CONTEXT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ProjectBoard::ID:
			case SearchFields_ProjectBoard::OWNER_CONTEXT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ProjectBoard::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ProjectBoard::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ProjectBoard::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_ProjectBoard::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_ProjectBoard extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete { // IDevblocksContextImport
	const ID = 'cerberusweb.contexts.project.board';
	const URI = 'project_board';
	
	function autocomplete($term, $query=null) {
		$list = [];
		
		list($results,) = DAO_ProjectBoard::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_ProjectBoard::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
			),
			25,
			0,
			SearchFields_ProjectBoard::NAME,
			true,
			false
		);

		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_ProjectBoard::NAME];
			$entry->value = $row[SearchFields_ProjectBoard::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_ProjectBoard::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=project_board&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ProjectBoard();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($project_board = DAO_ProjectBoard::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($project_board->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $project_board->id,
			'name' => $project_board->name,
			'permalink' => $url,
			'updated' => $project_board->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($project_board, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Project Board:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_ProjectBoard::ID);

		// Polymorph
		if(is_numeric($project_board)) {
			$project_board = DAO_ProjectBoard::get($project_board);
		} elseif($project_board instanceof Model_ProjectBoard) {
			// It's what we want already.
		} elseif(is_array($project_board)) {
			$project_board = Cerb_ORMHelper::recastArrayToModel($project_board, 'Model_ProjectBoard');
		} else {
			$project_board = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'columns' => $prefix.$translate->_('dashboard.columns'),
			'id' => $prefix.$translate->_('common.id'),
			'cards_kata' => $prefix.$translate->_('common.cards_kata'),
			'name' => $prefix.$translate->_('common.name'),
			'params' => $prefix.$translate->_('common.params'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'columns' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'cards_kata' => Model_CustomField::TYPE_MULTI_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'params' => null,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_ProjectBoard::ID;
		$token_values['_type'] = Context_ProjectBoard::URI;
		$token_values['_types'] = $token_types;
		
		if($project_board) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $project_board->name;
			$token_values['columns'] = $project_board->columns;
			$token_values['cards_kata'] = $project_board->cards_kata;
			$token_values['id'] = $project_board->id;
			$token_values['name'] = $project_board->name;
			$token_values['updated_at'] = $project_board->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($project_board, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=project_board&id=%d-%s",$project_board->id, DevblocksPlatform::strToPermalink($project_board->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_ProjectBoard::ID,
			'links' => '_links',
			'cards_kata' => DAO_ProjectBoard::CARDS_KATA,
			'name' => DAO_ProjectBoard::NAME,
			'owner__context' => DAO_ProjectBoard::OWNER_CONTEXT,
			'owner_id' => DAO_ProjectBoard::OWNER_CONTEXT_ID,
			'updated_at' => DAO_ProjectBoard::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['params'] = [
			'key' => 'params',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
		];
		
		$keys['columns'] = [
			'key' => 'columns',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded array of [project board column](/docs/records/types/project_board_column/) IDs; e.g. `[1,2,3]`',
			'type' => 'string',
		];
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'columns':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				// Sanitize the array to ints
				$value = DevblocksPlatform::sanitizeArray($value, 'int');
				
				// Encode
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_ProjectBoard::COLUMNS_JSON] = $json;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_ProjectBoard::ID;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Project Board';
		/*
		$view->addParams(array(
			SearchFields_ProjectBoard::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_ProjectBoard::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_ProjectBoard::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Project Board';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ProjectBoard::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) { // @audited
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_ProjectBoard::ID;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_ProjectBoard::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_ProjectBoard::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			// Link contexts
			$contexts = Extension_DevblocksContext::getAll(false, 'links');
			$tpl->assign('contexts', $contexts);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Library
			if(!$context_id) {
				$packages = DAO_PackageLibrary::getByPoint('project_board');
				$tpl->assign('packages', $packages);
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerb.project_boards::boards/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
