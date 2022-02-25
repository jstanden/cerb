<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class DAO_Bot extends Cerb_ORMHelper {
	const AT_MENTION_NAME = 'at_mention_name';
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const IS_DISABLED = 'is_disabled';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const PARAMS_JSON = 'params_json';
	const UPDATED_AT = 'updated_at';
	
	const _IMAGE = '_image';
	
	const CACHE_ALL = 'ch_bots';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::AT_MENTION_NAME)
			->string()
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_DISABLED)
			->bit()
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		// base64 blob png
		$validation
			->addField(self::_IMAGE)
			->image('image/png', 50, 50, 500, 500, 1000000)
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
		
		if(!isset($fields[DAO_Bot::CREATED_AT]))
			$fields[DAO_Bot::CREATED_AT] = time();
		
		$sql = "INSERT INTO bot () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_BOT, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		self::_updateAbstract(Context_Bot::ID, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_BOT, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'bot', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.bot.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_BOT, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('bot', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_BOT;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		return true;
	}
	
	static function autocomplete($term, $as='models') {
		$params = array(
			SearchFields_Bot::NAME => new DevblocksSearchCriteria(SearchFields_Bot::NAME, DevblocksSearchCriteria::OPER_LIKE, $term.'*'),
		);
		
		list($results,) = DAO_Bot::search(
			array(),
			$params,
			25,
			0,
			SearchFields_Bot::NAME,
			true,
			false
		);
		
		switch($as) {
			case 'ids':
				return array_keys($results);
				break;
				
			default:
				return DAO_Bot::getIds(array_keys($results));
				break;
		}
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Bot[]
	 */
	static function getWhere($where=null, $sortBy='name', $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, at_mention_name, owner_context, owner_context_id, is_disabled, params_json, created_at, updated_at ".
			"FROM bot ".
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
	
	static function getByAtMentions($at_mentions) {
		if(!is_array($at_mentions) && is_string($at_mentions))
			$at_mentions = array($at_mentions);
		
		$bots = DAO_Bot::getAll();
		
		$bots = array_filter($bots, function($bot) use ($at_mentions) {
			foreach($at_mentions as $at_mention) {
				if($bot->at_mention_name && 0 == strcasecmp(ltrim($at_mention, '@'), $bot->at_mention_name)) {
					return true;
				}
			}
			
			return false;
		});
		
		return $bots;
	}
	
	/**
	 * @param integer $id
	 * @return Model_Bot
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = DAO_Bot::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}

	static function random() {
		return parent::_getRandom('bot');
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::CACHE_ALL))) {
			$objects = DAO_Bot::getWhere(
				null,
				DAO_Bot::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 *
	 * @param string $context
	 * @param integer $context_id
	 * @return Model_Bot[]
	 */
	static function getByOwner($context, $context_id, $with_disabled=false) {
		$vas = DAO_Bot::getAll();
		$results = array();
		
		foreach($vas as $va_id => $va) {
			if(!$with_disabled && $va->is_disabled)
				continue;
			
			if($context == $va->owner_context && $context_id == $va->owner_context_id)
				$results[$va_id] = $va;
		}

		return $results;
	}
	
	static function getReadableByActor($actor, $ignore_admins=false) {
		$bots = DAO_Bot::getAll();
		$privs = Context_Bot::isReadableByActor($bots, $actor, $ignore_admins);
		return array_intersect_key($bots, array_flip(array_keys($privs, true)));
	}
	
	static function getWriteableByActor($actor, $ignore_admins=false) {
		$bots = DAO_Bot::getAll();
		$privs = Context_Bot::isWriteableByActor($bots, $actor, $ignore_admins);
		return array_intersect_key($bots, array_flip(array_keys($privs, true)));
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Bot[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Bot();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->at_mention_name = $row['at_mention_name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$object->is_disabled = $row['is_disabled'] ? true : false;
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			
			$params = json_decode($row['params_json'] ?? '', true);
			$object->params = $params ?: array();
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static public function count($owner_context, $owner_context_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(*) FROM bot ".
			"WHERE owner_context = %s AND owner_context_id = %d",
			$db->qstr($owner_context),
			$owner_context_id
		));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM bot WHERE id IN (%s)", $ids_list));

		// Cascade
		if(is_array($ids))
		foreach($ids as $id)
			DAO_TriggerEvent::deleteByBot($id);
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_BOT,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function deleteByOwner($context, $context_ids) {
		if(empty($context) || empty($context_ids))
			return false;
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		foreach($context_ids as $context_id) {
			$vas = DAO_Bot::getByOwner($context, $context_id, true);
			
			if(is_array($vas) && !empty($vas))
				DAO_Bot::delete(array_keys($vas));
		}
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Bot::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Bot', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"bot.id as %s, ".
			"bot.name as %s, ".
			"bot.at_mention_name as %s, ".
			"bot.owner_context as %s, ".
			"bot.owner_context_id as %s, ".
			"bot.is_disabled as %s, ".
			"bot.params_json as %s, ".
			"bot.created_at as %s, ".
			"bot.updated_at as %s ",
				SearchFields_Bot::ID,
				SearchFields_Bot::NAME,
				SearchFields_Bot::AT_MENTION_NAME,
				SearchFields_Bot::OWNER_CONTEXT,
				SearchFields_Bot::OWNER_CONTEXT_ID,
				SearchFields_Bot::IS_DISABLED,
				SearchFields_Bot::PARAMS_JSON,
				SearchFields_Bot::CREATED_AT,
				SearchFields_Bot::UPDATED_AT
			);
			
		$join_sql = "FROM bot ";
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Bot');
	
		return array(
			'primary_table' => 'bot',
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
	 * @return array
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
			SearchFields_Bot::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}

	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_Bot extends DevblocksSearchFields {
	const ID = 'v_id';
	const NAME = 'v_name';
	const AT_MENTION_NAME = 'v_at_mention_name';
	const OWNER_CONTEXT = 'v_owner_context';
	const OWNER_CONTEXT_ID = 'v_owner_context_id';
	const IS_DISABLED = 'v_is_disabled';
	const PARAMS_JSON = 'v_params_json';
	const CREATED_AT = 'v_created_at';
	const UPDATED_AT = 'v_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'bot.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_BOT => new DevblocksSearchFieldContextKeys('bot.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_BOT, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_BOT), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'bot.owner_context', 'bot.owner_context_id');
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_BOT, self::getPrimaryKey());
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'owner':
				$key = 'owner';
				$search_key = 'owner';
				$owner_field = $search_fields[SearchFields_Bot::OWNER_CONTEXT];
				$owner_id_field = $search_fields[SearchFields_Bot::OWNER_CONTEXT_ID];
				
				return [
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'sql_select' => sprintf("CONCAT_WS(':',%s.%s,%s.%s)",
						Cerb_ORMHelper::escape($owner_field->db_table),
						Cerb_ORMHelper::escape($owner_field->db_column),
						Cerb_ORMHelper::escape($owner_id_field->db_table),
						Cerb_ORMHelper::escape($owner_id_field->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('owner'),
				];
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Bot::ID:
				$models = DAO_Bot::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Bot::IS_DISABLED:
				return [
					0 => DevblocksPlatform::translate('common.no'),
					1 => DevblocksPlatform::translate('common.yes'),
				];
				break;
				
			case 'owner':
				return self::_getLabelsForKeyContextAndIdValues($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'bot', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'bot', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::AT_MENTION_NAME => new DevblocksSearchField(self::AT_MENTION_NAME, 'bot', 'at_mention_name', $translate->_('worker.at_mention_name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'bot', 'owner_context', $translate->_('common.owner_context'), null, false),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'bot', 'owner_context_id', $translate->_('common.owner_context_id'), null, false),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'bot', 'is_disabled', $translate->_('common.disabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'bot', 'params_json', $translate->_('common.parameters'), null, false),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'bot', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'bot', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
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

class Model_Bot {
	public $id;
	public $name;
	public $at_mention_name;
	public $owner_context;
	public $owner_context_id;
	public $is_disabled;
	public $params;
	public $created_at;
	public $updated_at;
	
	public function getOwnerMeta() {
		if(null != ($ext = Extension_DevblocksContext::get($this->owner_context))) {
			$meta = $ext->getMeta($this->owner_context_id);
			$meta['context'] = $this->owner_context;
			$meta['context_ext'] = $ext;
			return $meta;
		}
	}
	
	function getBehaviors($event_point=null, $with_disabled=false, $sort_by='pos') {
		return DAO_TriggerEvent::getByBot($this->id, $event_point, $with_disabled, $sort_by);
	}
	
	function canUseEvent($event_point) {
		@$events_mode = $this->params['events']['mode'];
		@$events_items = $this->params['events']['items'];
		
		switch($events_mode) {
			case 'allow':
				return in_array($event_point, $events_items);
				break;
				
			case 'deny':
				return !in_array($event_point, $events_items);
				break;
		}
		
		return true;
	}
	
	function filterEventsByAllowed($events) {
		@$events_mode = $this->params['events']['mode'];
		@$events_items = $this->params['events']['items'];
		
		switch($events_mode) {
			case 'allow':
				$events = array_intersect_key($events, array_flip($events_items));
				break;
				
			case 'deny':
				$events = array_diff_key($events, array_flip($events_items));
				break;
		}
		
		return $events;
	}
	
	function filterActionManifestsByAllowed($manifests) {
		@$actions_mode = $this->params['actions']['mode'];
		@$actions_items = $this->params['actions']['items'];
		
		switch($actions_mode) {
			case 'allow':
				$manifests = array_intersect_key($manifests, array_flip($actions_items));
				break;
				
			case 'deny':
				$manifests = array_diff_key($manifests, array_flip($actions_items));
				break;
		}
		
		return $manifests;
	}
	
	function exportToJson() {
		$bot_data = [
			'uid' => 'bot_'.$this->id,
			'name' => $this->name,
			'owner' => [
				'context' => $this->owner_context,
				'id' => $this->owner_context_id,
			],
			'is_disabled' => $this->is_disabled ? true : false,
			'params' => $this->params,
			'image' => null,
			'behaviors' => [],
		];
		
		if(false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_BOT, $this->id))) {
			if(false != ($image = Storage_ContextAvatar::get($avatar)))
				$bot_data['image'] = 'data:image/png;base64,' . base64_encode($image);
		}
		
		$behaviors = $this->getBehaviors(null, true);
		
		foreach($behaviors as $behavior) {
			if(null == ($behavior->getEvent()))
				continue;
			
			$behavior_json = $behavior->exportToJson(0);
			$behavior_data = json_decode($behavior_json, true);
			$bot_data['behaviors'][] = $behavior_data['behavior'];
		}
		
		return DevblocksPlatform::strFormatJson($bot_data);
	}
};

class View_Bot extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'bots';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = mb_convert_case($translate->_('common.bots'), MB_CASE_TITLE);
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Bot::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Bot::NAME,
			SearchFields_Bot::VIRTUAL_OWNER,
			SearchFields_Bot::AT_MENTION_NAME,
			SearchFields_Bot::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Bot::OWNER_CONTEXT,
			SearchFields_Bot::OWNER_CONTEXT_ID,
			SearchFields_Bot::PARAMS_JSON,
			SearchFields_Bot::VIRTUAL_CONTEXT_LINK,
			SearchFields_Bot::VIRTUAL_HAS_FIELDSET,
			SearchFields_Bot::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Bot::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Bot');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Bot', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Bot', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_Bot::IS_DISABLED:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Bot::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Bot::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Bot::VIRTUAL_OWNER:
				case SearchFields_Bot::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_BOT;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Bot::IS_DISABLED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_Bot::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Bot::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Bot::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_Bot::OWNER_CONTEXT, DAO_Bot::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			case SearchFields_Bot::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Bot::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Bot::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Bot::CREATED_AT),
				),
			'disabled' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Bot::IS_DISABLED),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Bot::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_BOT],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Bot::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BOT, 'q' => ''],
					]
				),
			'mentionName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Bot::AT_MENTION_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Bot::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'score' => 2000,
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:bots by:name~25 query:(name:{{term}}*) format:dictionaries',
						'key' => 'name',
						'limit' => 25,
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Bot::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Bot::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add dynamic owner.* fields
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner', SearchFields_Bot::VIRTUAL_OWNER);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Bot::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_BOT, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Bot::VIRTUAL_WATCHERS, $tokens);
				
			default:
				if($field == 'owner' || substr($field, 0, strlen('owner.')) == 'owner.')
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_Bot::VIRTUAL_OWNER);
				
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BOT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/bot/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_Bot::IS_DISABLED:
				$this->_renderCriteriaParamBoolean($param);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Bot::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Bot::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Bot::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners', 'Owner matches');
				break;
			
			case SearchFields_Bot::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Bot::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Bot::AT_MENTION_NAME:
			case SearchFields_Bot::NAME:
			case SearchFields_Bot::OWNER_CONTEXT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Bot::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Bot::CREATED_AT:
			case SearchFields_Bot::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Bot::IS_DISABLED:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Bot::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Bot::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Bot::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_POST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
				
			case SearchFields_Bot::VIRTUAL_WATCHERS:
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

class Context_Bot extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete { // IDevblocksContextImport
	const ID = CerberusContexts::CONTEXT_BOT;
	const URI = 'bot';
	
	static function isReadableByActor($models, $actor, $ignore_admins=false) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_BOT, $models, 'owner_', $ignore_admins);
	}
	
	static function isWriteableByActor($models, $actor, $ignore_admins=false) {
		// Only admins can modify

		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);

		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);

		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor, $ignore_admins=false) {
		return self::isWriteableByActor($models, $actor, $ignore_admins);
	}
	
	function getRandom() {
		return DAO_Bot::random();
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		$list = array();
		
		$models = DAO_Bot::autocomplete($term);
		
		if(stristr('none',$term) || stristr('empty',$term)) {
			$empty = new stdClass();
			$empty->label = '(no bot)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the bot');
			$list[] = $empty;
		}
		
		if(is_array($models))
		foreach($models as $bot_id => $bot){
			$entry = new stdClass();
			$entry->label = $bot->name;
			$entry->value = sprintf("%d", $bot_id);
			$entry->icon = $url_writer->write('c=avatars&type=bot&id=' . $bot->id, true) . '?v=' . $bot->updated_at;
			
			$meta = array();
			$entry->meta = $meta;
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=bot&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Bot();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_BOT,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			],
		);
		
		$properties['is_disabled'] = array(
			'label' => mb_ucfirst($translate->_('common.disabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_disabled,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(false == ($bot = DAO_Bot::get($context_id)))
			return [];
			
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($bot->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $bot->id,
			'name' => $bot->name,
			'permalink' => $url,
			'updated' => $bot->updated_at,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'owner__label',
			'mention_name',
			'is_disabled',
			'updated_at',
		);
	}
	
	function getContext($model, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Bot:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BOT);

		// Polymorph
		if(is_numeric($model)) {
			$model = DAO_Bot::get($model);
		} elseif($model instanceof Model_Bot) {
			// It's what we want already.
		} elseif(is_array($model)) {
			$model = Cerb_ORMHelper::recastArrayToModel($model, 'Model_Bot');
		} else {
			$model = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'config' => $prefix.$translate->_('common.configuration'),
			'created_at' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'mention_name' => $prefix.$translate->_('worker.at_mention_name'),
			'name' => $prefix.$translate->_('common.name'),
			'is_disabled' => $prefix.$translate->_('common.disabled'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			
			'record_url' => $prefix.$translate->_('common.url.record'),
			
			'owner__label' => $prefix.$translate->_('common.owner'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'config' => null,
			'created_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'mention_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_disabled' => Model_CustomField::TYPE_CHECKBOX,
			'updated_at' => Model_CustomField::TYPE_DATE,
			
			'record_url' => Model_CustomField::TYPE_URL,
			
			'owner__label' => 'context_url',
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_Bot::ID;
		$token_values['_type'] = Context_Bot::URI;
		
		$token_values['_types'] = $token_types;
		
		if($model) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $model->name;
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'bot', $model->id), true) . '?v=' . $model->updated_at;
			$token_values['created_at'] = $model->created_at;
			$token_values['id'] = $model->id;
			$token_values['mention_name'] = $model->at_mention_name;
			$token_values['name'] = $model->name;
			$token_values['is_disabled'] = $model->is_disabled;
			$token_values['updated_at'] = $model->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($model, $token_values);
			
			// URL
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=bot&id=%d-%s",$model->id, DevblocksPlatform::strToPermalink($model->name)), true);
			
			// Owner
			$token_values['owner__context'] = $model->owner_context;
			$token_values['owner_id'] = $model->owner_context_id;
			
			// Configuration JSON
			$token_values['config'] = is_array(@$model->params['config']) ? $model->params['config'] : [];
		}
		
		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_Bot::CREATED_AT,
			'id' => DAO_Bot::ID,
			'image' => '_image',
			'is_disabled' => DAO_Bot::IS_DISABLED,
			'links' => '_links',
			'mention_name' => DAO_Bot::AT_MENTION_NAME,
			'name' => DAO_Bot::NAME,
			'owner__context' => DAO_Bot::OWNER_CONTEXT,
			'owner_id' => DAO_Bot::OWNER_CONTEXT_ID,
			'updated_at' => DAO_Bot::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['is_disabled']['notes'] = "Is this bot disabled?";
		$keys['mention_name']['notes'] = "(deprecated)";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'image':
				$out_fields[DAO_Bot::_IMAGE] = $value;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['behaviors'] = [
			'label' => 'Behaviors',
			'type' => 'Records',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_BOT;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'behaviors':
				$values = $dictionary;
				
				if(null == ($va = DAO_Bot::get($context_id)))
					break;

				$values['behaviors'] = array();

				$behaviors = $va->getBehaviors(null, true, 'pos');

				foreach($behaviors as $behavior) { /* @var $behavior Model_TriggerEvent */
					if(false == ($behavior_json = $behavior->exportToJson()))
						continue;
					
					@$json = json_decode($behavior_json, true);
					
					if(empty($json))
						continue;
					
					$values['behaviors'][$behavior->id] = $json;
				}
				break;
				
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
		$view->name = DevblocksPlatform::translateCapitalized('common.bots');
		/*
		$view->addParams(array(
			SearchFields_Bot::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Bot::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_Bot::UPDATED_AT;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.bots');
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Bot::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_BOT;
		$active_worker = CerberusApplication::getActiveWorker();
		
		if($context_id) {
			if(false == ($model = DAO_Bot::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			@$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'string','');
			@$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'],'integer',0);
			
			$model = new Model_Bot();
			$model->owner_context = $owner_context;
			$model->owner_context_id = $owner_context_id;
		}
		
		if(empty($context_id) || $edit) {
			if(!$active_worker || !$active_worker->is_superuser) {
				DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			if($model)
				$tpl->assign('model', $model);
			
			// Custom Fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree([CerberusContexts::CONTEXT_APPLICATION, CerberusContexts::CONTEXT_GROUP, CerberusContexts::CONTEXT_ROLE, CerberusContexts::CONTEXT_WORKER]);
			$tpl->assign('owners_menu', $owners_menu);
			
			// VA Events
			$event_extensions = DevblocksPlatform::getExtensions('devblocks.event', false);
			DevblocksPlatform::sortObjects($event_extensions, 'name');
			$tpl->assign('event_extensions', $event_extensions);
			
			// VA actions
			$action_extensions = DevblocksPlatform::getExtensions('devblocks.event.action', false);
			DevblocksPlatform::sortObjects($action_extensions, 'params->[label]');
			$tpl->assign('action_extensions', $action_extensions);
			
			// Library
			if(!$context_id) {
				$packages = DAO_PackageLibrary::getByPoint('bot');
				$tpl->assign('packages', $packages);
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/bot/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
