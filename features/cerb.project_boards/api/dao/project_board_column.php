<?php
class DAO_ProjectBoardColumn extends Cerb_ORMHelper {
	const BOARD_ID = 'board_id';
	const CARDS_JSON = 'cards_json';
	const CARDS_KATA = 'cards_kata';
	const ID = 'id';
	const NAME = 'name';
	const TOOLBAR_KATA = 'toolbar_kata';
	const FUNCTIONS_KATA = 'functions_kata';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::BOARD_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(Context_ProjectBoard::ID))
			;
		// mediumtext
		$validation
			->addField(self::CARDS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		// text
		$validation
			->addField(self::CARDS_KATA)
			->string()
			->setMaxLength(16777215)
			;
		// text
		$validation
			->addField(self::FUNCTIONS_KATA)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// text
		$validation
			->addField(self::TOOLBAR_KATA)
			->string()
			->setMaxLength(16777215)
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
		
		$sql = "INSERT INTO project_board_column () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_ProjectBoardColumn::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		self::_updateAbstract(Context_ProjectBoardColumn::ID, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(Context_ProjectBoardColumn::ID, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'project_board_column', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.project_board_column.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(Context_ProjectBoardColumn::ID, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('project_board_column', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = Context_ProjectBoardColumn::ID;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		// Actor must have access to modify the project board
		if(isset($fields[self::BOARD_ID])) {
			$board_id = $fields[self::BOARD_ID];
			
			if(!$board_id || !Context_ProjectBoard::isWriteableByActor($board_id, $actor)) {
				$error = "You do not have permission to add columns to this project board.";
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ProjectBoardColumn[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, board_id, updated_at, cards_kata, toolbar_kata, functions_kata, cards_json ".
			"FROM project_board_column ".
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
	 * @return Model_ProjectBoardColumn[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ProjectBoardColumn::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ProjectBoardColumn	 */
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
	
	static function countByBoardId($ticket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(board_id) FROM project_board_column WHERE board_id = %d",
			$ticket_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function getByBoardId($board_id) {
		$columns = self::getAll();
		
		return array_filter($columns, function($column) use ($board_id) {
			if($column->board_id == $board_id)
				return true;
			
			return false;
		});
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_ProjectBoardColumn[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ProjectBoardColumn[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ProjectBoardColumn();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->board_id = intval($row['board_id']);
			$object->cards_kata = $row['cards_kata'];
			$object->toolbar_kata = $row['toolbar_kata'];
			$object->functions_kata = $row['functions_kata'];
			$object->updated_at = intval($row['updated_at']);
			
			$json = json_decode($row['cards_json'] ?? '', true) ?: [];
			$object->cards = $json;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('project_board_column');
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM project_board_column WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => Context_ProjectBoardColumn::ID,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	static function deleteByProjectIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return;
		
		foreach($ids as $id) {
			if(false == ($columns = DAO_ProjectBoardColumn::getByBoardId($id)))
				continue;
			
			DAO_ProjectBoardColumn::delete(array_keys($columns));
		}
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ProjectBoardColumn::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ProjectBoardColumn', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"project_board_column.id as %s, ".
			"project_board_column.name as %s, ".
			"project_board_column.board_id as %s, ".
			"project_board_column.updated_at as %s ",
				SearchFields_ProjectBoardColumn::ID,
				SearchFields_ProjectBoardColumn::NAME,
				SearchFields_ProjectBoardColumn::BOARD_ID,
				SearchFields_ProjectBoardColumn::UPDATED_AT
			);
			
		$join_sql = "FROM project_board_column ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ProjectBoardColumn');
	
		return array(
			'primary_table' => 'project_board_column',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
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
			SearchFields_ProjectBoardColumn::ID,
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

class SearchFields_ProjectBoardColumn extends DevblocksSearchFields {
	const ID = 'p_id';
	const NAME = 'p_name';
	const BOARD_ID = 'p_board_id';
	const UPDATED_AT = 'p_updated_at';
	
	const VIRTUAL_BOARD_SEARCH = '*_board_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'project_board_column.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'cerberusweb.contexts.project.board.column' => new DevblocksSearchFieldContextKeys('project_board_column.id', self::ID),
			'cerberusweb.contexts.project.board' => new DevblocksSearchFieldContextKeys('project_board_column.board_id', self::BOARD_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_BOARD_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, Context_ProjectBoard::ID, 'project_board_column.board_id');
			
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, Context_ProjectBoardColumn::ID, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(Context_ProjectBoardColumn::ID), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, Context_ProjectBoardColumn::ID, self::getPrimaryKey());
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'board':
				$key = 'board.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ProjectBoardColumn::ID:
				$models = DAO_ProjectBoardColumn::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_ProjectBoardColumn::BOARD_ID:
				$models = DAO_ProjectBoard::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
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
			self::ID => new DevblocksSearchField(self::ID, 'project_board_column', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'project_board_column', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::BOARD_ID => new DevblocksSearchField(self::BOARD_ID, 'project_board_column', 'board_id', $translate->_('projects.common.board'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'project_board_column', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_BOARD_SEARCH => new DevblocksSearchField(self::VIRTUAL_BOARD_SEARCH, '*', 'board_search', null, null, false),
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

class Model_ProjectBoardColumn {
	public $id;
	public $name;
	public $board_id;
	public $cards_kata;
	public $toolbar_kata;
	public $functions_kata;
	public $updated_at;
	public $params;
	public $cards;
	
	private $_board = null;
	
	// Load and cache board
	function getProjectBoard() {
		if(is_null($this->_board)) {
			$board = DAO_ProjectBoard::get($this->board_id);
			$this->_board = $board ?: false;
		}
		
		return $this->_board;
	}
	
	function getLimit() : int {
		return 100;
	}
	
	function getCards($since=null, $limit=null) {
		if(!is_numeric($limit))
			$limit = $this->getLimit();
		
		$card_models = [];
		$offset = 0;
		
		$links = DAO_ContextLink::getAllContextLinks(Context_ProjectBoardColumn::ID, $this->id, true);
		
		// Append links that aren't in the sorted cards
		$this->cards = array_merge(
			$this->cards,
			array_diff(
				array_keys($links),
				$this->cards
			)
		);
		
		// If we're paging, slice the cards array
		if($limit) {
			if($since) {
				if(false == ($offset = array_search($since, $this->cards)))
					$offset = -1;
				
				$offset++;
			}
			
			$this->cards = array_slice($this->cards, $offset, $limit, true);
		}
		
		$this->cards = array_fill_keys($this->cards, null);
		
		array_map(
			function($k) use (&$card_models) {
				list($context, $context_id) = array_pad(explode(':', $k, 2), 2, null);
				
				if(!array_key_exists($context, $card_models))
					$card_models[$context] = [];
				
				$card_models[$context][] = $context_id;
			},
			array_keys($this->cards)
		);
		
		foreach($card_models as $model_context => $model_ids) {
			$models = CerberusContexts::getModels($model_context, $model_ids);
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $model_context, ['_label']);
			
			// Iterate model IDs in case the dictionary doesn't exist
			foreach($model_ids as $model_id) {
				// Skip cards for missing dictionaries
				if(null == ($dict = ($dicts[$model_id] ?? null))) {
					unset($this->cards[$model_context . ':' . $model_id]);
					continue;
				}
				
				// Add keys for the project board column
				$dict->set('column__context', Context_ProjectBoardColumn::ID);
				$dict->set('column_id', $this->id);
				
				$this->cards[$model_context . ':' . $model_id] = $dict;
			}
		}
		
		return array_combine(
			array_map(fn($k) => sha1($k), array_keys($this->cards)),
			$this->cards
		);
	}
	
	/**
	 * @param DevblocksDictionaryDelegate $card
	 * @return array|null
	 */
	function getCardSheet(DevblocksDictionaryDelegate $card) {
		$active_worker = CerberusApplication::getActiveWorker();
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$error = null;
		
		$dict = clone $card;
		$dict->mergeKeys('board_', DevblocksDictionaryDelegate::getDictionaryFromModel($this->getProjectBoard(), Context_ProjectBoard::ID));
		
		if($active_worker)
			$dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
		
		$handlers = $event_handler->parse($this->cards_kata, $dict, $error);
		
		$results = $event_handler->handleOnce(
			AutomationTrigger_ProjectBoardRenderCard::ID,
			$handlers,
			$dict->getDictionary(),
			$error
		);
		
		if(false == $results)
			return null;
		
		$exit_state = $results->get('__exit');
		
		if('return' == $exit_state) {
			return $results->getKeyPath('__return.sheet', null);
		}
		
		return null;
	}
	
	function runDropActionsForCard($context, $context_id) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'card__context' => $context,
			'card_id' => $context_id,
		]);
		$dict->mergeKeys('board_', DevblocksDictionaryDelegate::getDictionaryFromModel($this->getProjectBoard(), Context_ProjectBoard::ID));
		$dict->mergeKeys('column_', DevblocksDictionaryDelegate::getDictionaryFromModel($this, Context_ProjectBoardColumn::ID));
		
		if($active_worker)
			$dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
		
		$handlers = $event_handler->parse($this->functions_kata, $dict, $error);
		
		$initial_state = $dict->getDictionary();
		
		return $event_handler->handleEach(
			AutomationTrigger_ProjectBoardCardAction::ID,
			$handlers,
			$initial_state,
			$error,
			null,
			function(Model_TriggerEvent $behavior, array $handler) use ($context, $context_id) {
				$event_ext = $behavior->getEvent();
				
				// Only run events for this context
				if (@$event_ext->manifest->params['macro_context'] != $context)
					return null;
				
				return call_user_func([$event_ext->manifest->class, 'trigger'], $behavior->id, $context_id, @$handler['data']['inputs'] ?: []);
			}
		);
	}
};

class View_ProjectBoardColumn extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'project_board_columns';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('projects.common.board.columns');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ProjectBoardColumn::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ProjectBoardColumn::NAME,
			SearchFields_ProjectBoardColumn::BOARD_ID,
			SearchFields_ProjectBoardColumn::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_ProjectBoardColumn::VIRTUAL_BOARD_SEARCH,
			SearchFields_ProjectBoardColumn::VIRTUAL_CONTEXT_LINK,
			SearchFields_ProjectBoardColumn::VIRTUAL_HAS_FIELDSET,
			SearchFields_ProjectBoardColumn::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_ProjectBoardColumn::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ProjectBoardColumn');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ProjectBoardColumn', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ProjectBoardColumn', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_ProjectBoardColumn::BOARD_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_ProjectBoardColumn::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ProjectBoardColumn::VIRTUAL_HAS_FIELDSET:
				case SearchFields_ProjectBoardColumn::VIRTUAL_WATCHERS:
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
		$context = Context_ProjectBoardColumn::ID;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ProjectBoardColumn::BOARD_ID:
				$label_map = function($ids) {
					$models = DAO_ProjectBoard::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_ProjectBoardColumn::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_ProjectBoardColumn::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_ProjectBoardColumn::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_ProjectBoardColumn::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'board' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::VIRTUAL_BOARD_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => Context_ProjectBoard::ID, 'q' => ''],
					]
				),
			'board.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::BOARD_ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_ProjectBoard::ID, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_ProjectBoardColumn::ID],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_ProjectBoardColumn::ID, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ProjectBoardColumn::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_ProjectBoardColumn::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_ProjectBoardColumn::ID, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'board':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ProjectBoardColumn::VIRTUAL_BOARD_SEARCH);
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_ProjectBoardColumn::VIRTUAL_WATCHERS, $tokens);
				
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
		$custom_fields = DAO_CustomField::getByContext(Context_ProjectBoardColumn::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$boards = DAO_ProjectBoard::getAll();
		$tpl->assign('boards', $boards);
		
		$tpl->assign('view_template', 'devblocks:cerb.project_boards::project_board_column/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? [$param->value] : $param->value;

		switch($field) {
			case SearchFields_ProjectBoardColumn::ID:
				$label_map = SearchFields_ProjectBoardColumn::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_ProjectBoardColumn::BOARD_ID:
				$label_map = SearchFields_ProjectBoardColumn::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ProjectBoardColumn::VIRTUAL_BOARD_SEARCH:
				echo sprintf("Project matches <b>%s</b>", DevblocksPlatform::strEscapeHtml($param->value));
				break;
			
			case SearchFields_ProjectBoardColumn::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_ProjectBoardColumn::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_ProjectBoardColumn::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ProjectBoardColumn::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ProjectBoardColumn::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ProjectBoardColumn::BOARD_ID:
			case SearchFields_ProjectBoardColumn::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ProjectBoardColumn::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ProjectBoardColumn::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ProjectBoardColumn::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_ProjectBoardColumn::VIRTUAL_WATCHERS:
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

class Context_ProjectBoardColumn extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	const ID = 'cerberusweb.contexts.project.board.column';
	const URI = 'project_board_column';
	
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
		return DAO_ProjectBoardColumn::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=project_board_column&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ProjectBoardColumn();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['board_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('projects.common.board'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->board_id,
			'params' => [
				'context' => Context_ProjectBoard::ID,
			]
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
		if(false == ($project_board_column = DAO_ProjectBoardColumn::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($project_board_column->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $project_board_column->id,
			'name' => $project_board_column->name,
			'permalink' => $url,
			//'updated' => $project_board_column->updated_at, // [TODO]
		);
	}
	
	function getDefaultProperties() {
		return array(
			'board__label',
			'updated_at',
		);
	}
	
	function getContext($project_board_column, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Project Board Column:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_ProjectBoardColumn::ID);

		// Polymorph
		if(is_numeric($project_board_column)) {
			$project_board_column = DAO_ProjectBoardColumn::get($project_board_column);
		} elseif($project_board_column instanceof Model_ProjectBoardColumn) {
			// It's what we want already.
		} elseif(is_array($project_board_column)) {
			$project_board_column = Cerb_ORMHelper::recastArrayToModel($project_board_column, 'Model_ProjectBoardColumn');
		} else {
			$project_board_column = null;
		}
		
		// [TODO] Column->Board
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'cards_kata' => $prefix.$translate->_('dao.project_board.cards_kata'),
			'functions_kata' => $prefix.$translate->_('dao.project_board.functions_kata'),
			'toolbar_kata' => $prefix.$translate->_('dao.project_board.toolbar_kata'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'board__label' => $prefix.$translate->_('projects.common.board'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'board_id' => Model_CustomField::TYPE_NUMBER,
			'board__label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'cards_kata' => Model_CustomField::TYPE_MULTI_LINE,
			'functions_kata' => Model_CustomField::TYPE_MULTI_LINE,
			'toolbar_kata' => Model_CustomField::TYPE_MULTI_LINE,
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
		
		$token_values['_context'] = Context_ProjectBoardColumn::ID;
		$token_values['_type'] = Context_ProjectBoardColumn::URI;
		$token_values['_types'] = $token_types;
		
		$token_values['board__context'] = Context_ProjectBoard::ID;
		
		if($project_board_column) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $project_board_column->name;
			$token_values['board_id'] = $project_board_column->board_id;
			$token_values['id'] = $project_board_column->id;
			$token_values['name'] = $project_board_column->name;
			$token_values['cards_kata'] = $project_board_column->cards_kata;
			$token_values['functions_kata'] = $project_board_column->functions_kata;
			$token_values['toolbar_kata'] = $project_board_column->toolbar_kata;
			$token_values['updated_at'] = $project_board_column->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($project_board_column, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=project_board_column&id=%d-%s",$project_board_column->id, DevblocksPlatform::strToPermalink($project_board_column->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'board_id' => DAO_ProjectBoardColumn::BOARD_ID,
			'cards_kata' => DAO_ProjectBoardColumn::CARDS_KATA,
			'toolbar_kata' => DAO_ProjectBoardColumn::TOOLBAR_KATA,
			'functions_kata' => DAO_ProjectBoardColumn::FUNCTIONS_KATA,
			'id' => DAO_ProjectBoardColumn::ID,
			'links' => '_links',
			'name' => DAO_ProjectBoardColumn::NAME,
			'updated_at' => DAO_ProjectBoardColumn::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['cards'] = [
			'key' => 'cards',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'An array of record `type:id` tuples to add to this column',
			'type' => 'links',
		];
		
		$keys['params'] = [
			'key' => 'params',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
		];
		
		$keys['board_id']['notes'] = "The [project board](/docs/records/types/project_board/) containing this column";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'cards':
				if(!is_array($value)) {
					$error = 'must be an array of context:id pairs.';
					return false;
				}
				
				$links = [];
				
				foreach($value as &$tuple) {
					@list($context, $id) = explode(':', $tuple, 2);
					
					if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, false))) {
						$error = sprintf("has a card with an invalid context (%s)", $tuple);
						return false;
					}
					
					$context = $context_ext->id;
					
					$tuple = sprintf("%s:%d",
						$context,
						$id
					);
					
					$links[] = $tuple;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_ProjectBoardColumn::CARDS_JSON] = $json;
				$out_fields['_links'] = json_encode($links);
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
		
		$context = Context_ProjectBoardColumn::ID;
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
		$view->name = 'Project Board Column';
		/*
		$view->addParams(array(
			SearchFields_ProjectBoardColumn::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_ProjectBoardColumn::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_ProjectBoardColumn::UPDATED_AT;
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
		$view->name = 'Project Board Column';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ProjectBoardColumn::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_ProjectBoardColumn::ID;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_ProjectBoardColumn::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_ProjectBoardColumn::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			} else {
				$model = new Model_ProjectBoardColumn();
			}
			
			if(!empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					@list($k,$v) = explode(':', $token);
					
					if(empty($k) || empty($v))
						continue;
					
					switch($k) {
						case 'board.id':
							$model->board_id = intval($v);
							break;
					}
				}
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$tpl->assign('model', $model);
			
			if(isset($model->params['behaviors'])) {
				$behaviors = DAO_TriggerEvent::getIds(array_keys($model->params['behaviors']));
				$tpl->assign('behaviors', $behaviors);
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerb.project_boards::project_board_column/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
	
	/*
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'name' => array(
				'label' => 'Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ProjectBoardColumn::NAME,
				'required' => true,
			),
			'updated_at' => array(
				'label' => 'Updated Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_ProjectBoardColumn::UPDATED_AT,
			),
		);
	
		$fields = SearchFields_ProjectBoardColumn::getFields();
		self::_getImportCustomFields($fields, $keys);
	
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have a name
			if(!isset($fields[DAO_ProjectBoardColumn::NAME])) {
				$fields[DAO_ProjectBoardColumn::NAME] = 'New ' . $this->manifest->name;
			}
	
			// Create
			$meta['object_id'] = DAO_ProjectBoardColumn::create($fields);
	
		} else {
			// Update
			DAO_ProjectBoardColumn::update($meta['object_id'], $fields);
		}
	
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
	*/
};
