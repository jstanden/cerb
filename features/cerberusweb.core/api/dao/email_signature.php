<?php
class DAO_EmailSignature extends Cerb_ORMHelper {
	const ID = 'id';
	const IS_DEFAULT = 'is_default';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const SIGNATURE = 'signature';
	const SIGNATURE_HTML = 'signature_html';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'email_signatures_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_DEFAULT)
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
			;
		$validation
			->addField(self::SIGNATURE)
			->string()
			->setMaxLength('24 bits')
			->setRequired(true)
			;
		$validation
			->addField(self::SIGNATURE_HTML)
			->string()
			->setMaxLength('24 bits')
			;
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
		
		$sql = "INSERT INTO email_signature () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'email_signature', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.email_signature.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('email_signature', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		@$owner_context = $fields[self::OWNER_CONTEXT];
		@$owner_context_id = intval($fields[self::OWNER_CONTEXT_ID]);
		
		if($owner_context) {
			// Signatures can be owned by app or groups
			if(!in_array($owner_context, [ CerberusContexts::CONTEXT_APPLICATION, CerberusContexts::CONTEXT_GROUP ])) {
				$error = "Email signatures may only be owned by Cerb or a group.";
				return false;
			}
			
			// Verify that the actor can use this new owner
			if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $actor)) {
				$error = DevblocksPlatform::translate('error.core.no_acl.owner');
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
	 * @return Model_EmailSignature[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, signature, signature_html, owner_context, owner_context_id, is_default, updated_at ".
			"FROM email_signature ".
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
	 * @return Model_EmailSignature[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_EmailSignature::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return false;
				
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_EmailSignature	 */
	static function get($id) {
		$objects = self::getAll();
		
		if(!$id || !isset($objects[$id]))
			return null;
		
		return $objects[$id];
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_EmailSignature[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}	
	
	/**
	 * @param resource $rs
	 * @return Model_EmailSignature[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_EmailSignature();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$object->signature = $row['signature'];
			$object->signature_html = $row['signature_html'];
			$object->is_default = @$row['is_default'] ? 1 : 0;
			$object->updated_at = $row['updated_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('email_signature');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM email_signature WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_EMAIL_SIGNATURE,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_EmailSignature::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_EmailSignature', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"email_signature.id as %s, ".
			"email_signature.name as %s, ".
			"email_signature.signature as %s, ".
			"email_signature.owner_context as %s, ".
			"email_signature.owner_context_id as %s, ".
			"email_signature.is_default as %s, ".
			"email_signature.updated_at as %s ",
				SearchFields_EmailSignature::ID,
				SearchFields_EmailSignature::NAME,
				SearchFields_EmailSignature::SIGNATURE,
				SearchFields_EmailSignature::OWNER_CONTEXT,
				SearchFields_EmailSignature::OWNER_CONTEXT_ID,
				SearchFields_EmailSignature::IS_DEFAULT,
				SearchFields_EmailSignature::UPDATED_AT
			);
			
		$join_sql = "FROM email_signature ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_EmailSignature');
	
		return array(
			'primary_table' => 'email_signature',
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
			SearchFields_EmailSignature::ID,
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

class SearchFields_EmailSignature extends DevblocksSearchFields {
	const ID = 'e_id';
	const NAME = 'e_name';
	const OWNER_CONTEXT = 'e_owner_context';
	const OWNER_CONTEXT_ID = 'e_owner_context_id';
	const SIGNATURE = 'e_signature';
	const IS_DEFAULT = 'e_is_default';
	const UPDATED_AT = 'e_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'email_signature.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_EMAIL_SIGNATURE => new DevblocksSearchFieldContextKeys('email_signature.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_EMAIL_SIGNATURE, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_EMAIL_SIGNATURE)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'email_signature.owner_context', 'email_signature.owner_context_id');
				break;
				
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
			case 'default':
				$key = 'isDefault';
				break;
				
			case 'owner':
				$key = 'owner';
				$search_key = 'owner';
				$owner_field = $search_fields[SearchFields_EmailSignature::OWNER_CONTEXT];
				$owner_id_field = $search_fields[SearchFields_EmailSignature::OWNER_CONTEXT_ID];
				
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
			case SearchFields_EmailSignature::ID:
				$models = DAO_EmailSignature::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_EmailSignature::IS_DEFAULT:
				return parent::_getLabelsForKeyBooleanValues();
				break;
				
			case 'owner':
				return parent::_getLabelsForKeyContextAndIdValues($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'email_signature', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'email_signature', 'name', $translate->_('common.name'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'email_signature', 'owner_context', $translate->_('common.owner_context'), null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'email_signature', 'owner_context_id', $translate->_('common.owner_context_id'), null, true),
			self::SIGNATURE => new DevblocksSearchField(self::SIGNATURE, 'email_signature', 'signature', $translate->_('common.signature'), null, true),
			self::IS_DEFAULT => new DevblocksSearchField(self::IS_DEFAULT, 'email_signature', 'is_default', $translate->_('common.default'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'email_signature', 'updated_at', $translate->_('common.updated'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
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

class Model_EmailSignature {
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	public $signature;
	public $signature_html;
	public $is_default;
	public $updated_at;
	
	public function getSignature($worker_model, bool $as_html) {
		// If we have a worker model, convert template tokens
		if(!$worker_model)
			$worker_model = new Model_Worker();
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'_context' => CerberusContexts::CONTEXT_WORKER,
			'id' => $worker_model->id,
		]);
		
		if($as_html && $this->signature_html) {
			return $tpl_builder->build($this->signature_html, $dict);
			
		} else {
			return $tpl_builder->build($this->signature, $dict);
		}
	}
};

class View_EmailSignature extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'email_signatures';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.email_signatures');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_EmailSignature::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_EmailSignature::NAME,
			SearchFields_EmailSignature::IS_DEFAULT,
			SearchFields_EmailSignature::VIRTUAL_OWNER,
			SearchFields_EmailSignature::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_EmailSignature::OWNER_CONTEXT,
			SearchFields_EmailSignature::OWNER_CONTEXT_ID,
			SearchFields_EmailSignature::VIRTUAL_CONTEXT_LINK,
			SearchFields_EmailSignature::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_EmailSignature::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_EmailSignature');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_EmailSignature', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_EmailSignature', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_EmailSignature::VIRTUAL_OWNER:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_EmailSignature::VIRTUAL_CONTEXT_LINK:
				case SearchFields_EmailSignature::VIRTUAL_HAS_FIELDSET:
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
		$counts = [];
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_EmailSignature::IS_DEFAULT:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_EmailSignature::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_EmailSignature::OWNER_CONTEXT, DAO_EmailSignature::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			case SearchFields_EmailSignature::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_EmailSignature::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
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
		$search_fields = SearchFields_EmailSignature::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_EmailSignature::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_EmailSignature::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_EMAIL_SIGNATURE],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_EmailSignature::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_EMAIL_SIGNATURE, 'q' => ''],
					]
				),
			'isDefault' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_EmailSignature::IS_DEFAULT),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_EmailSignature::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'signature' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_EmailSignature::SIGNATURE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_EmailSignature::UPDATED_AT),
				),
		);
		
		// Add dynamic owner.* fields
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner', SearchFields_EmailSignature::VIRTUAL_OWNER);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_EmailSignature::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, $fields, null);
		
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
				break;
			
			default:
				if($field == 'owner' || substr($field, 0, strlen('owner.')) == 'owner.')
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_EmailSignature::VIRTUAL_OWNER);
				
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_EMAIL_SIGNATURE);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/email_signature/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_EmailSignature::IS_DEFAULT:
				parent::_renderCriteriaParamBoolean($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_EmailSignature::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_EmailSignature::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_EmailSignature::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners', 'Owner matches');
				break;
		}
	}

	function getFields() {
		return SearchFields_EmailSignature::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_EmailSignature::NAME:
			case SearchFields_EmailSignature::SIGNATURE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_EmailSignature::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_EmailSignature::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_EmailSignature::IS_DEFAULT:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_EmailSignature::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_EmailSignature::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_EmailSignature::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_POST['owner_context'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
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

class Context_EmailSignature extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, self::ID, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, self::ID, $models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_EmailSignature::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=email_signature&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_EmailSignature();
		
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
		
		$properties['is_default'] = array(
			'label' => mb_ucfirst($translate->_('common.is_default')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_default,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['signature'] = array(
			'label' => mb_ucfirst($translate->_('common.signature')),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $model->signature,
		);
		
		$properties['signature_html'] = array(
			'label' => mb_ucfirst($translate->_('common.signature.html')),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $model->signature_html,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$email_signature = DAO_EmailSignature::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($email_signature->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $email_signature->id,
			'name' => $email_signature->name,
			'permalink' => $url,
			'updated' => $email_signature->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'owner__label',
			'updated_at',
		);
	}
	
	function autocomplete($term, $query=null) {
		$list = [];
		
		list($results,) = DAO_EmailSignature::search(
			[],
			[
				new DevblocksSearchCriteria(SearchFields_EmailSignature::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
			],
			25,
			0,
			DAO_EmailSignature::NAME,
			true,
			false
		);

		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_EmailSignature::NAME];
			$entry->value = $row[SearchFields_EmailSignature::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($email_signature, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Email Signature:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_EMAIL_SIGNATURE);

		// Polymorph
		if(is_numeric($email_signature)) {
			$email_signature = DAO_EmailSignature::get($email_signature);
		} elseif($email_signature instanceof Model_EmailSignature) {
			// It's what we want already.
		} elseif(is_array($email_signature)) {
			$email_signature = Cerb_ORMHelper::recastArrayToModel($email_signature, 'Model_EmailSignature');
		} else {
			$email_signature = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'is_default' => $prefix.$translate->_('common.is_default'),
			'name' => $prefix.$translate->_('common.name'),
			'owner__label' => $prefix.$translate->_('common.owner'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'signature' => $prefix.$translate->_('common.signature'),
			'signature_html' => $prefix.$translate->_('common.signature.html'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_default' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner__label' => 'context_url',
			'record_url' => Model_CustomField::TYPE_URL,
			'signature' => Model_CustomField::TYPE_MULTI_LINE,
			'signature_html' => Model_CustomField::TYPE_MULTI_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;
		$token_values['_types'] = $token_types;
		
		if($email_signature) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $email_signature->name;
			$token_values['id'] = $email_signature->id;
			$token_values['is_default'] = $email_signature->is_default;
			$token_values['name'] = $email_signature->name;
			$token_values['owner__context'] = $email_signature->owner_context;
			$token_values['owner_id'] = $email_signature->owner_context_id;
			$token_values['signature'] = $email_signature->signature;
			$token_values['signature_html'] = $email_signature->signature_html;
			$token_values['updated_at'] = $email_signature->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($email_signature, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=email_signature&id=%d-%s",$email_signature->id, DevblocksPlatform::strToPermalink($email_signature->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_EmailSignature::ID,
			'is_default' => DAO_EmailSignature::IS_DEFAULT,
			'links' => '_links',
			'name' => DAO_EmailSignature::NAME,
			'owner__context' => DAO_EmailSignature::OWNER_CONTEXT,
			'owner_id' => DAO_EmailSignature::OWNER_CONTEXT_ID,
			'signature' => DAO_EmailSignature::SIGNATURE,
			'signature_html' => DAO_EmailSignature::SIGNATURE_HTML,
			'updated_at' => DAO_EmailSignature::UPDATED_AT,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['is_default']['notes'] = "Is this the default signature?";
		$keys['signature']['notes'] = "The [template](/docs/bots/scripting/) of the signature";
		$keys['signature_html']['notes'] = "The HTML [template](/docs/bots/scripting/) of the signature";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
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
		
		$context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
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
		$view->name = 'Email Signature';
		/*
		$view->addParams(array(
			SearchFields_EmailSignature::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_EmailSignature::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_EmailSignature::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Email Signature';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_EmailSignature::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_EmailSignature::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_EmailSignature::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree([CerberusContexts::CONTEXT_APPLICATION, CerberusContexts::CONTEXT_GROUP]);
			$tpl->assign('owners_menu', $owners_menu);
			
			// Attachments
			$attachments = DAO_Attachment::getByContextIds($context, $context_id);
			$tpl->assign('attachments', $attachments);
			
			// Placeholders
			
			$labels = $values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $labels, $values, '', true, false);
			
			$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
			$tpl->assign('placeholders', $placeholders);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/email_signature/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
