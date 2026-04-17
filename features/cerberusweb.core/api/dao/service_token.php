<?php
class DAO_ServiceToken extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const EXPIRES_AT = 'expires_at';
	const ID = 'id';
	const LAST_ACCESSED_AT = 'last_accessed_at';
	const NAME = 'name';
	const SCOPES = 'scopes';
	const TOKEN_HASH = 'token_hash';
	const TOKEN_HINT = 'token_hint';
	const UPDATED_AT = 'updated_at';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

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
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::SCOPES)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::EXPIRES_AT)
			->timestamp()
			;
		$validation
			->addField(self::LAST_ACCESSED_AT)
			->timestamp()
			;
		$validation
			->addField(self::TOKEN_HASH)
			->string()
			;
		$validation
			->addField(self::TOKEN_HINT)
			->string()
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

		$sql = "INSERT INTO service_token (`scopes`) VALUES ('')";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		CerberusContexts::checkpointCreations(Context_ServiceToken::ID, $id);

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_ServiceToken::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'service_token', $fields);

			if($check_deltas) {
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.service_token.update',
						[
							'fields' => $fields,
						]
					)
				);

				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('service_token', $fields, $where);
	}

	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$context = Context_ServiceToken::ID;

		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;

		return true;
	}
	
	static function getByToken(string $token) : ?Model_ServiceToken{
		// [TODO] We could cache this, but it needs to be done separately from DAO to not leak
		
		$objects = self::getWhere(sprintf("%s = %s",
			Cerb_ORMHelper::escape(DAO_ServiceToken::TOKEN_HASH),
			Cerb_ORMHelper::qstr(hash('sha256', $token))
		));
		
		if(1 == count($objects)) {
			return array_shift($objects);
		}
		
		return null;
	}

	/**
	 * @param string $where
	 * @return Model_ServiceToken[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT created_at, expires_at, id, last_accessed_at, name, scopes, token_hint, updated_at " .
			"FROM service_token " .
			$where_sql .
			$sort_sql .
			$limit_sql
		;

		if($options & DevblocksORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}

		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @return Model_ServiceToken[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ServiceToken|null
	 */
	static function get($id) {
		if(empty($id))
			return null;

		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));

		if(array_key_exists($id, $objects))
			return $objects[$id];

		return null;
	}

	/**
	 * @param array $ids
	 * @return Model_ServiceToken[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ServiceToken[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ServiceToken();
			$object->created_at = intval($row['created_at']);
			$object->expires_at = intval($row['expires_at']);
			$object->id = intval($row['id']);
			$object->last_accessed_at = intval($row['last_accessed_at']);
			$object->name = $row['name'];
			$object->scopes = array_filter(explode(' ', $row['scopes'] ?? ''));
			$object->token_hint = $row['token_hint'];
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}
	
	public static function getMasterToken() : ?Model_ServiceToken {
		if(
			!defined('APP_SERVICE_TOKEN')
			|| !APP_SERVICE_TOKEN
			|| !is_string(APP_SERVICE_TOKEN)
		) return null;
		
		$service_token = new Model_ServiceToken();
		$service_token->id = 0;
		$service_token->created_at = time();
		$service_token->expires_at = 0;
		$service_token->last_accessed_at = time();
		$service_token->name = 'Master Service Token';
		$service_token->scopes = array_filter(explode(' ', APP_SERVICE_TOKEN_SCOPE ?? ''));
		$service_token->token_hint = sprintf('%s...%s', substr(APP_SERVICE_TOKEN, 0, 6), substr(APP_SERVICE_TOKEN, -1) ?? '');
		$service_token->updated_at = time();
		return $service_token;
	}
	
	static function random() {
		return self::_getRandom('service_token');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_ServiceToken::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM service_token WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ServiceToken::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ServiceToken', $sortBy);

		$select_sql = sprintf("SELECT ".
			"service_token.created_at as %s, ".
			"service_token.expires_at as %s, ".
			"service_token.id as %s, ".
			"service_token.last_accessed_at as %s, ".
			"service_token.name as %s, ".
			"service_token.scopes as %s, ".
			"service_token.token_hint as %s, ".
			"service_token.updated_at as %s ",
			SearchFields_ServiceToken::CREATED_AT,
			SearchFields_ServiceToken::EXPIRES_AT,
			SearchFields_ServiceToken::ID,
			SearchFields_ServiceToken::LAST_ACCESSED_AT,
			SearchFields_ServiceToken::NAME,
			SearchFields_ServiceToken::SCOPES,
			SearchFields_ServiceToken::TOKEN_HINT,
			SearchFields_ServiceToken::UPDATED_AT
		);

		$join_sql = "FROM service_token ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ServiceToken');

		return [
			'primary_table' => 'service_token',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		list($data, $total) = self::_searchWithTimeout(
			SearchFields_ServiceToken::ID,
			$query_parts['select'],
			$query_parts['join'],
			$query_parts['where'],
			$query_parts['sort'],
			$page,
			$limit,
			$withCounts
		);

        return [$data, $total];
	}

    public static function generateToken() : ?string {
        try {
            $random_bytes = random_bytes(48);
            return 'sk_' . DevblocksPlatform::services()->string()->base64UrlEncode($random_bytes);

        } catch(Throwable $e) {
            DevblocksPlatform::logException($e);
            return null;
        }
    }
};

class SearchFields_ServiceToken extends DevblocksSearchFields {
	const CREATED_AT = 's_created_at';
	const EXPIRES_AT = 's_expires_at';
	const ID = 's_id';
	const LAST_ACCESSED_AT = 's_last_accessed_at';
	const NAME = 's_name';
	const SCOPES = 's_scopes';
	const TOKEN_HINT = 's_token_hint';
	const UPDATED_AT = 's_updated_at';

	static private $_fields = null;

	static function getTableName() : string {
		return 'service_token';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_ServiceToken::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_ServiceToken::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_ServiceToken::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_ServiceToken::ID => new DevblocksSearchFieldContextKeys('service_token.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_ServiceToken::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::ID:
				$models = DAO_ServiceToken::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
		}

		return parent::getLabelsForKeyValues($key, $values);
	}

	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();

		return self::$_fields;
	}

	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = [
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'service_token', 'created_at', $translate->_('common.created'), null, true),
			self::EXPIRES_AT => new DevblocksSearchField(self::EXPIRES_AT, 'service_token', 'expires_at', $translate->_('common.expires'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'service_token', 'id', $translate->_('common.id'), null, true),
			self::LAST_ACCESSED_AT => new DevblocksSearchField(self::LAST_ACCESSED_AT, 'service_token', 'last_accessed_at', $translate->_('dao.service_token.last_accessed_at'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'service_token', 'name', $translate->_('common.name'), null, true),
			self::SCOPES => new DevblocksSearchField(self::SCOPES, 'service_token', 'scopes', $translate->_('common.scopes'), null, true),
			self::TOKEN_HINT => new DevblocksSearchField(self::TOKEN_HINT, 'service_token', 'token_hint', $translate->_('common.token'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'service_token', 'updated_at', $translate->_('common.updated'), null, true),
		];

		if(($virtual_columns = DevblocksSearchField::getVirtualFields()))
			$columns = array_merge($columns, $virtual_columns);

		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));

		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ServiceToken extends DevblocksRecordModel {
	public int $created_at = 0;
	public int $expires_at = 0;
	public int $id = 0;
	public int $last_accessed_at = 0;
	public string $name = '';
	public array $scopes = [];
	public string $token_hint = '';
	public int $updated_at = 0;
	
	public function hasScope(string $scope) : bool {
		// If this is a nested scope, check the parent first
		if(str_contains($scope, ':')) {
			$parent_scope = DevblocksPlatform::services()->string()->strBefore($scope, ':');
			if(in_array($parent_scope, $this->scopes))
				return true;
		}
		
		return in_array($scope, $this->scopes);
	}
	
	public function isExpired() : bool {
		return $this->expires_at > 0 && $this->expires_at < time();
	}
};

class View_ServiceToken extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'service_tokens';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Service Tokens');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ServiceToken::ID;
		$this->renderSortAsc = true;

		$this->view_columns = [
			SearchFields_ServiceToken::NAME,
			SearchFields_ServiceToken::TOKEN_HINT,
			SearchFields_ServiceToken::EXPIRES_AT,
			SearchFields_ServiceToken::LAST_ACCESSED_AT,
			SearchFields_ServiceToken::SCOPES,
			SearchFields_ServiceToken::UPDATED_AT,
		];

		$this->addColumnsHidden([]);

		$this->doResetCriteria();
	}

	protected function _getData() {
		return DAO_ServiceToken::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ServiceToken');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_ServiceToken', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ServiceToken', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;

			switch($field_key) {
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_')) {
						$pass = $this->_canSubtotalCustomField($field_key);
					} else if (str_starts_with($field_key, '*_')) {
						$pass = $this->_canSubtotalVirtualField($field_key);
					}
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
		$context = Context_ServiceToken::ID;

		if(!array_key_exists($column, $fields))
			return [];

		switch($column) {
			default:
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				} else if(DevblocksPlatform::strStartsWith($column, '*_')) {
					$counts = $this->_getSubtotalCountForVirtualField($context, $column);
				}
				break;
		}

		return $counts;
	}

	function getQuickSearchDefaultFilter(?DevblocksSearchCriteria $criteria=null) : string {
		return 'name';
	}

	function getQuickSearchFields() {
		$search_fields = SearchFields_ServiceToken::getFields();

		$fields = [
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_ServiceToken::CREATED_AT],
			],
			'expires' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_ServiceToken::EXPIRES_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_ServiceToken::ID],
				]
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_ServiceToken::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_ServiceToken::ID, 'q' => ''],
				]
			],
			'lastAccessed' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_ServiceToken::LAST_ACCESSED_AT],
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_ServiceToken::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'scopes' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_ServiceToken::SCOPES, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_ServiceToken::UPDATED_AT],
			],
			'watchers' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_WATCHERS],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
				],
			],
		];

		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		$fields = self::_appendFieldsFromQuickSearchContext(Context_ServiceToken::ID, $fields, null);
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		ksort($fields);

		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');

			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(DevblocksSearchField::VIRTUAL_WATCHERS, $tokens);

			default:
				if($field == 'links' || str_starts_with($field, 'links.'))
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

		$custom_fields = DAO_CustomField::getByContext(Context_ServiceToken::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/service_token/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		switch($param->field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ServiceToken::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ServiceToken::CREATED_AT:
			case SearchFields_ServiceToken::EXPIRES_AT:
			case SearchFields_ServiceToken::LAST_ACCESSED_AT:
			case SearchFields_ServiceToken::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_ServiceToken::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_ServiceToken::NAME:
			case SearchFields_ServiceToken::SCOPES:
			case SearchFields_ServiceToken::TOKEN_HINT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			default:
				if(str_starts_with($field, 'cf_')) {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				} else if (str_starts_with($field, '*_')) {
					if(($virtual_criteria = $this->_doSetCriteriaVirtual($field, $_POST, $oper)))
						$criteria = $virtual_criteria;
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_ServiceToken extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.service.token';
	const URI = 'service_token';

	static function isReadableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}

	static function isWriteableByActor($models, $actor) : bool {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}

	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}

	function getRandom() {
		return DAO_ServiceToken::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=service_token&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_ServiceToken();

		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => ['context' => self::ID],
		];

		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];

		$properties['expires_at'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.expires'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->expires_at,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['last_accessed_at'] = [
			'label' => DevblocksPlatform::translateCapitalized('dao.service_token.last_accessed_at'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->last_accessed_at,
		];

		$properties['scopes'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.scopes'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->scopes,
		];

		$properties['token_hint'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.token_hint'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->token_hint,
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($service_token = DAO_ServiceToken::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($service_token->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $service_token->id,
			'name' => $service_token->name,
			'permalink' => $url,
			'updated' => $service_token->updated_at,
			'created' => $service_token->created_at,
		];
	}

	function getDefaultProperties() : array {
		return [
			'created_at',
			'updated_at',
		];
	}

	function getContext($service_token, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Service Tokens:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_ServiceToken::ID);

		if(is_numeric($service_token)) {
			$service_token = DAO_ServiceToken::get($service_token);
		} elseif($service_token instanceof Model_ServiceToken) {
			DevblocksPlatform::noop();
		} elseif(is_array($service_token)) {
			$service_token = Cerb_ORMHelper::recastArrayToModel($service_token, 'Model_ServiceToken');
		} else {
			$service_token = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'expires_at' => $prefix.$translate->_('common.expires'),
			'last_accessed_at' => $prefix.$translate->_('dao.service_token.last_accessed_at'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'scopes' => $prefix.$translate->_('common.scopes'),
			'token_hint' => $prefix.$translate->_('common.token'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'expires_at' => Model_CustomField::TYPE_DATE,
			'last_accessed_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'scopes' => Model_CustomField::TYPE_SINGLE_LINE,
			'token_hint' => Model_CustomField::TYPE_SINGLE_LINE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_ServiceToken::ID;
		$token_values['_type'] = 'service_token';
		$token_values['_types'] = $token_types;

		if($service_token) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $service_token->name;
			$token_values['id'] = $service_token->id;
			$token_values['name'] = $service_token->name;
			$token_values['created_at'] = $service_token->created_at;
			$token_values['expires_at'] = $service_token->expires_at;
			$token_values['last_accessed_at'] = $service_token->last_accessed_at;
			$token_values['updated_at'] = $service_token->updated_at;
			$token_values['scopes'] = $service_token->scopes;
			$token_values['token_hint'] = $service_token->token_hint;
			$token_values = $this->_importModelCustomFieldsAsValues($service_token, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=service_token&id=%d-%s", $service_token->id, DevblocksPlatform::strToPermalink($service_token->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_ServiceToken::CREATED_AT,
			'expires_at' => DAO_ServiceToken::EXPIRES_AT,
			'id' => DAO_ServiceToken::ID,
			'last_accessed_at' => DAO_ServiceToken::LAST_ACCESSED_AT,
			'name' => DAO_ServiceToken::NAME,
			'scopes' => DAO_ServiceToken::SCOPES,
			'token_hint' => DAO_ServiceToken::TOKEN_HINT,
			'updated_at' => DAO_ServiceToken::UPDATED_AT,
			'links' => '_links',
		];
	}

	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		return $keys;
	}

	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		return true;
	}

	function lazyLoadGetKeys() {
		return parent::lazyLoadGetKeys();
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = Context_ServiceToken::ID;
		$context_id = $dictionary['id'];
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];

		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}

		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
				$values = array_merge($values, $defaults);
				break;
		}

		return $values;
	}

	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);

		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Service Tokens';
		$view->renderSortBy = SearchFields_ServiceToken::UPDATED_AT;
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
		$view->name = 'Service Tokens';

		$params_req = [];

		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(DevblocksSearchField::VIRTUAL_CONTEXT_LINK,'in',[$context.':'.$context_id]),
			];
		}

		$view->addParamsRequired($params_req, true);
		$view->renderTemplate = 'context';
		return $view;
	}

	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_ServiceToken::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_ServiceToken::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(!$model) {
			$model = new Model_ServiceToken();
			$model->id = 0;
			$model->token_hint = DAO_ServiceToken::generateToken();
		}

		if(empty($context_id) || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);

				$tpl->assign('model', $model);
			}

			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);

			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);

			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);

			// Parse space-delimited scopes (e.g. "debug cron:cron.maint update") into a lookup set
			$scopes = array_fill_keys($model->scopes, true);
			$tpl->assign('scopes', $scopes);

			$cron_extensions = DevblocksPlatform::getExtensions('cerberusweb.cron', false);
			DevblocksPlatform::sortObjects($cron_extensions, 'name');

			$available_scopes = [
				'update' => [
					'label' => 'Run version and schema upgrades',
					'children' => [],
				],
				'cron' => [
					'label' => 'Scheduler jobs',
					'children' => array_column(
						array_map(
							fn($ext) => ['key' => 'cron:' . $ext->id, 'value' => ['name' => $ext->id, 'label' => $ext->name]],
							$cron_extensions
						),
						'value', 'key'
					),
				],
				'debug' => [
					'label' => 'Debug endpoints',
					'children' => [
						'debug:check'   => ['name' => 'check',   'label' => 'System requirements check'],
						'debug:phpinfo' => ['name' => 'phpinfo',  'label' => 'PHP configuration'],
						'debug:report' => ['name' => 'report',  'label' => 'Environment report for support'],
						'debug:status'  => ['name' => 'status',   'label' => 'Platform status'],
					],
				],
			];

			$tpl->assign('available_scopes', $available_scopes);

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/service_token/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
