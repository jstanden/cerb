<?php
class DAO_AutomationContinuation extends Cerb_ORMHelper {
	const EXPIRES_AT = 'expires_at';
	const EXTENSION_ID = 'extension_id';
	const PARENT_TOKEN = 'parent_token';
	const RESUME_SCOPE = 'resume_scope';
	const ROOT_TOKEN = 'root_token';
	const STATE = 'state';
	const STATE_AWAIT = 'state_await';
	const STATE_DATA = 'state_data';
	const TOKEN = 'token';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	const WORKER_ID = 'worker_id';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::EXPIRES_AT)
			->timestamp()
			;
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(255)
			;
		$validation
			->addField(self::STATE_AWAIT)
			->string()
			->setMaxLength(32)
			;
		$validation
			->addField(self::PARENT_TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::RESUME_SCOPE)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::ROOT_TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::STATE)
			->string()
			->setPossibleValues([
				'',
				'await',
				'error',
				'exit',
				'return',
			])
			;
		$validation
			->addField(self::STATE_DATA)
			->string()
			->setMaxLength(16777216)
			;
		$validation
			->addField(self::TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::URI)
			->string()
			->addValidator($validation->validators()->uri())
			;
		$validation
			->addField(self::WORKER_ID)
			->id()
			;

		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$token = DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(48));
		
		$sql = sprintf("INSERT INTO automation_continuation (token) VALUES (%s)",
			$db->qstr($token)
		);
		$db->ExecuteMaster($sql);
		
		self::update($token, $fields);
		
		return $token;
	}
	
	static function upsert($fields, $token=null) {
		$db = DevblocksPlatform::services()->database();
		
		if(is_null($token))
			$token = DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(48));
		
		$sql = sprintf("REPLACE INTO automation_continuation (token) VALUES (%s)",
			$db->qstr($token)
		);
		$db->ExecuteMaster($sql);
		
		self::update($token, $fields);
		
		return $token;
	}
	
	static function update($ids, $fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		self::updateWhere($fields, sprintf("token IN (%s)",
			implode(',', $db->qstrArray($ids))
		));
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('automation_continuation', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_AutomationContinuation[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT token, uri, state, state_data, parent_token, root_token, expires_at, updated_at, extension_id, worker_id, state_await, resume_scope ".
			"FROM automation_continuation ".
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
	 * @param string $token
	 * @return Model_AutomationContinuation
	 */
	static function getByToken(string $token) {
		if(empty($token))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %s",
			self::TOKEN,
			Cerb_ORMHelper::qstr($token)
		));
		
		if(array_key_exists($token, $objects))
			return $objects[$token];
		
		return null;
	}
	
	/**
	 * @param string $token
	 * @return Model_AutomationContinuation[]
	 */
	static function getByRootToken(string $token) : iterable {
		if(!$token)
			return [];
		
		return self::getWhere(sprintf("%s = %s OR %s = %s",
			self::TOKEN,
			Cerb_ORMHelper::qstr($token),
			self::ROOT_TOKEN,
			Cerb_ORMHelper::qstr($token)
		));
	}
	
	// The await SUB-STATE for a parked continuation, derived from its `__return`: which kind of await it's
	// parked on. Pure record metadata — computed only when writing the continuation, never fed back into
	// automation state. It answers READINESS ("is it parked somewhere a UI can re-enter"); WHETHER and WHERE it
	// may be reopened is `resume_scope`, set by the launcher.
	static function stateAwaitFor(array $return) : string {
		foreach(self::getAwaitTypes() as $type) {
			if(array_key_exists($type, $return))
				return $type;
		}

		return '';
	}

	static function getAwaitTypes() : array {
		return ['form', 'interaction', 'duration', 'draft', 'record', 'queue'];
	}

	// The await kinds a UI can drop back into. `form` is the obvious one; `queue` matters because a worker who
	// navigates away mid-LLM-turn would otherwise have NO way back — the turn finishes server-side, but the row
	// stayed invisible until it expired. Resuming one re-renders the poll marker, which restarts the two-loop.
	// The rest (interaction/duration/draft/record) are mid-flow with their own client handling.
	static function getResumableAwaitTypes() : array {
		return ['form', 'queue'];
	}

	// The interaction triggers that render in the worker popup and are therefore resumable. Website/portal
	// (anon, token-as-cookie) and the headless timer are deliberately excluded; explore renders full-page.
	// A sanity check on the trigger family — `worker_id` is the security gate, `resume_scope` the routing one.
	static function getWorkerResumableExtensionIds() : array {
		return [
			AutomationTrigger_InteractionWorker::ID,
			AutomationTrigger_InteractionInternal::ID,
		];
	}

	/**
	 * WHERE a parked interaction may be reopened — and, by being non-empty, whether it may be at all. Derived
	 * from the launcher's `caller` (recorded in `state_data` at start), NOT from the automation or the record:
	 * a chat scoped `agent.pane:automation` follows the worker from one automation editor to the next, which is
	 * the point. A launcher that wants per-record pinning appends its own segment (`agent.pane:icon:123`).
	 *
	 * `ui_capabilities` is deliberately excluded — it grows as a host gains commands, and including it would
	 * orphan every history row on each update.
	 */
	static function resumeScopeFor(?array $caller) : string {
		if(!is_array($caller) || !($name = trim(strval($caller['name'] ?? ''))))
			return '';

		// The global command bar's own launches read as a plain `commandbar` namespace rather than its toolbar id.
		if(Toolbar_GlobalMenu::ID === $name)
			return 'commandbar';

		if(Toolbar_AgentPane::CALLER_NAME === $name) {
			$component = trim(strval($caller['params']['component'] ?? ''));
			return $component ? ('agent.pane:' . $component) : '';
		}

		return '';
	}

	// The scopes the GLOBAL command bar lists. A positive allowlist, not a `NOT LIKE`: sargable, and it fails
	// CLOSED, so a future launcher's chats never leak into a surface that can't drive them. (An editor-pane chat
	// opened from the command bar would render in a popup with no `command` bridge and its `uiCommand` awaits
	// would silently return empty.) `''` is excluded — unscoped means not resumable anywhere.
	static function getGlobalResumeScopes() : array {
		return ['commandbar'];
	}

	/**
	 * Display rows for a set of resumable continuations, keyed by token: the name the worker gave it on pause,
	 * else the automation's description, else its uri — plus how long it's been idle.
	 *
	 * Shared by the global command bar and the agent pane's History so the two can't drift into describing the
	 * same conversation differently. Batches the automation lookup rather than one query per row.
	 *
	 * @param Model_AutomationContinuation[] $continuations
	 */
	static function getResumableLabels(array $continuations) : array {
		if(!$continuations)
			return [];

		$labels = [];

		foreach(DAO_Automation::getByUris(array_values(array_unique(array_map(fn($c) => $c->uri, $continuations)))) as $automation) {
			if($automation->description)
				$labels[$automation->name] = $automation->description;
		}

		$out = [];

		foreach($continuations as $continuation) {
			$name = $continuation->state_data['name'] ?? '';

			$out[$continuation->token] = [
				'token' => $continuation->token,
				'label' => $name ?: ($labels[$continuation->uri] ?? $continuation->uri),
				'icon' => 'history',
				'description' => sprintf('Last active %s', DevblocksPlatform::strPrettyTime($continuation->updated_at)),
				'updated_at' => intval($continuation->updated_at),
			];
		}

		return $out;
	}

	/**
	 * A worker's own non-terminal continuations that the GLOBAL command bar offers to resume.
	 * @return Model_AutomationContinuation[]
	 */
	static function getResumableByWorker(int $worker_id, int $limit=25) : array {
		return self::getResumableByWorkerForScopes($worker_id, self::getGlobalResumeScopes(), $limit);
	}

	/**
	 * A worker's own non-terminal continuations reopenable from one or more launcher scopes.
	 * @return Model_AutomationContinuation[]
	 */
	static function getResumableByWorkerForScopes(int $worker_id, array $scopes, int $limit=25) : array {
		if($worker_id < 1 || !($scopes = array_filter(array_map('strval', $scopes), fn($s) => '' !== $s)))
			return [];

		$db = DevblocksPlatform::services()->database();

		return self::getWhere(
			sprintf("%s = %d AND %s IN (%s) AND %s IN (%s) AND %s = '' AND %s IN (%s) AND %s = %s AND (%s = 0 OR %s > %d)",
				self::WORKER_ID,
				$worker_id,
				self::RESUME_SCOPE,
				implode(',', $db->qstrArray(array_values($scopes))),
				self::STATE_AWAIT,
				implode(',', $db->qstrArray(self::getResumableAwaitTypes())),
				self::PARENT_TOKEN,
				self::EXTENSION_ID,
				implode(',', $db->qstrArray(self::getWorkerResumableExtensionIds())),
				self::STATE,
				$db->qstr('await'),
				self::EXPIRES_AT,
				self::EXPIRES_AT,
				time()
			),
			self::UPDATED_AT,
			false,
			$limit
		);
	}

	/**
	 *
	 * @param array $ids
	 * @return Model_AutomationContinuation[]
	 */
	static function getIds(array $ids) : array {
		if(!is_array($ids))
			$ids = [$ids];

		if(empty($ids))
			return [];

		if(!method_exists(get_called_class(), 'getWhere'))
			return [];

		$db = DevblocksPlatform::services()->database();

		$models = [];

		$results = static::getWhere(sprintf("token IN (%s)",
			implode(',', $db->qstrArray($ids))
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AutomationContinuation[]|false
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AutomationContinuation();
			$object->token = $row['token'];
			$object->parent_token = $row['parent_token'];
			$object->root_token = $row['root_token'];
			$object->uri = $row['uri'];
			$object->state = $row['state'];
			$object->expires_at = intval($row['expires_at']);
			$object->updated_at = intval($row['updated_at']);
			$object->extension_id = $row['extension_id'];
			$object->worker_id = intval($row['worker_id']);
			$object->state_await = $row['state_await'];
			$object->resume_scope = strval($row['resume_scope'] ?? '');
			
			@$state_data = json_decode($row['state_data'], true);
			$object->state_data = $state_data ?: [];
			
			$objects[$object->token] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation_continuation');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return false;
		
		if(!is_array($ids))
			$ids = [$ids];
		
		$ids_list = implode(',', $db->qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation_continuation WHERE token IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM automation_continuation WHERE expires_at BETWEEN 1 AND %d",
			time()
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AutomationContinuation::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AutomationContinuation', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation_continuation.token as %s, ".
			"automation_continuation.parent_token as %s, ".
			"automation_continuation.root_token as %s, ".
			"automation_continuation.uri as %s, ".
			"automation_continuation.state as %s, ".
			"automation_continuation.expires_at as %s, ".
			"automation_continuation.updated_at as %s ",
			SearchFields_AutomationContinuation::TOKEN,
			SearchFields_AutomationContinuation::PARENT_TOKEN,
			SearchFields_AutomationContinuation::ROOT_TOKEN,
			SearchFields_AutomationContinuation::URI,
			SearchFields_AutomationContinuation::STATE,
			SearchFields_AutomationContinuation::EXPIRES_AT,
			SearchFields_AutomationContinuation::UPDATED_AT
			);
			
		$join_sql = "FROM automation_continuation ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AutomationContinuation');
	
		return array(
			'primary_table' => 'automation_continuation',
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
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_AutomationContinuation::TOKEN,
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

class SearchFields_AutomationContinuation extends DevblocksSearchFields {
	const EXPIRES_AT = 'a_expires_at';
	const PARENT_TOKEN = 'a_parent_token';
	const ROOT_TOKEN = 'a_root_token';
	const STATE = 'a_state';
	const TOKEN = 'a_token';
	const UPDATED_AT = 'a_updated_at';
	const URI = 'a_uri';

	static private $_fields = null;
	
	static function getTableName() : string {
		return 'automation_continuation';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AutomationContinuation::TOKEN);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AutomationContinuation::UPDATED_AT);
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('automation_continuation.token', self::TOKEN),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
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
		
		$columns = [
			self::EXPIRES_AT => new DevblocksSearchField(self::EXPIRES_AT, 'automation_continuation', 'expires_at', $translate->_('common.expires'), Model_CustomField::TYPE_DATE, true),
			self::PARENT_TOKEN => new DevblocksSearchField(self::PARENT_TOKEN, 'automation_continuation', 'parent_token', null, Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ROOT_TOKEN => new DevblocksSearchField(self::ROOT_TOKEN, 'automation_continuation', 'root_token', null, Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STATE => new DevblocksSearchField(self::STATE, 'automation_continuation', 'state', DevblocksPlatform::translateCapitalized('common.state'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TOKEN => new DevblocksSearchField(self::TOKEN, 'automation_continuation', 'token', null, Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation_continuation', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::URI => new DevblocksSearchField(self::STATE, 'automation_continuation', 'uri', DevblocksPlatform::translate('common.uri'), Model_CustomField::TYPE_SINGLE_LINE, true),
		];
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_AutomationContinuation {
	public $expires_at = 0;
	public $extension_id = '';
	public $parent_token = null;
	public $root_token = null;
	public $state = null;
	public $state_data = [];
	public $token = null;
	public $updated_at = 0;
	public $uri = null;
	public $worker_id = 0;
	public $state_await = '';
	// WHERE this may be reopened; '' = nowhere (not resumable). See DAO_AutomationContinuation::resumeScopeFor().
	public $resume_scope = '';
	
	private ?Model_AutomationContinuation  $_parent = null;
	private ?Model_AutomationContinuation  $_root = null;
	private ?Model_Automation $_automation = null;
	
	function getAutomation() : ?Model_Automation {
		if(is_null($this->_automation)) {
			$this->_automation = DAO_Automation::getByUri($this->uri);
		}
		
		return $this->_automation;
	}
	
	public function getParent() : ?Model_AutomationContinuation {
		if(is_null($this->_parent) && $this->parent_token) {
			$this->_parent = DAO_AutomationContinuation::getByToken($this->parent_token);
		}
		
		return $this->_parent;
	}
	
	public function getRoot() : ?Model_AutomationContinuation {
		if(is_null($this->_root) && $this->root_token) {
			$this->_root = DAO_AutomationContinuation::getByToken($this->root_token);
		}
		
		return $this->_root;
	}
};
