<?php
class DAO_AgentFile extends Cerb_ORMHelper {
	const CONTENT = 'content';
	const CREATED_AT = 'created_at';
	const FILE_EXTENSION = 'file_extension';
	const FILESYSTEM_ID = 'filesystem_id';
	const FRONTMATTER_JSON = 'frontmatter_json';
	const ID = 'id';
	const IMPORT_UUID = 'import_uuid';
	const NAME = 'name';
	const SHA1 = 'sha1';
	const SIZE = 'size';
	const UPDATED_AT = 'updated_at';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		$validation
			->addField(self::CONTENT)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::FILE_EXTENSION)
			->string()
			->setMaxLength(32)
			;
		$validation
			->addField(self::FILESYSTEM_ID)
			->uint()
			;
		$validation
			->addField(self::FRONTMATTER_JSON)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// Bookkeeping for ZIP imports (`Cerb\Agent\FilesystemImporter`); not worker-editable
		$validation
			->addField(self::IMPORT_UUID)
			->string()
			->setMaxLength(36)
			->setEditable(false)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			->setMaxLength(1024)
			;
		$validation
			->addField(self::SHA1)
			->string()
			;
		$validation
			->addField(self::SIZE)
			->uint()
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

		// We need to appease the unique key
		$name = $fields[self::NAME] ?? ('tmp/' . DevblocksPlatform::services()->string()->uuid());
		$filesystem_id = $fields[self::FILESYSTEM_ID] ?? 0;
		
		$sql = sprintf("INSERT INTO agent_file (name, filesystem_id) VALUES (%s, %d)", $db->qstr($name), $filesystem_id);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		// Re-stating the volume the INSERT already used, so the volume's counter refresh fires from update()
		// even for a create that carries no `content` -- the row exists either way, and it counts.
		$fields[self::FILESYSTEM_ID] = $filesystem_id;

		CerberusContexts::checkpointCreations(Context_AgentFile::ID, $id);

		self::update($id, $fields);

		return $id;
	}

	/**
	 * Extract a leading `--- ... ---` YAML block as JSON, or null. The body is left intact either way — the
	 * frontmatter is a cheap index for `ls --fields`/tag filtering, not a replacement for reading the file.
	 */
	static function parseFrontmatterJson(string $name, string $content) : ?string {
		if(!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['md', 'markdown'], true))
			return null;

		if(!preg_match('/^---\r?\n(.*?)\r?\n---[ \t]*(?:\r?\n|$)/s', $content, $matches))
			return null;

		if(false === ($parsed = @yaml_parse($matches[1])) || !is_array($parsed))
			return null;

		if(false === ($json = json_encode($parsed)))
			return null;

		return strlen($json) > 65535 ? null : $json;
	}

	/**
	 * Columns computed FROM the content, applied to any write that carries one: `sha1`/`size` from the body,
	 * `file_extension`/`frontmatter_json` from the body plus the path.
	 *
	 * They live here rather than in each writer because every caller was deriving a different subset — the ZIP
	 * importer did all four, the peek editor skipped `frontmatter_json`, the VFS skipped it too until it was
	 * patched in three places. An explicitly-supplied value always wins (the importer passes its own `sha1`,
	 * which it needed for dedupe anyway).
	 *
	 * $name is the row's path. Null means "unknown here" — the caller resolves it per row, since a content
	 * write with no `name:` in the same field set takes its extension/frontmatter from the STORED path.
	 */
	private static function _deriveContentFields(array $fields, ?string $name) : array {
		if(!array_key_exists(self::CONTENT, $fields))
			return $fields;

		$content = strval($fields[self::CONTENT]);

		if(!array_key_exists(self::SHA1, $fields))
			$fields[self::SHA1] = sha1($content);

		if(!array_key_exists(self::SIZE, $fields))
			$fields[self::SIZE] = strlen($content);

		if(is_null($name))
			return $fields;

		if(!array_key_exists(self::FILE_EXTENSION, $fields)) {
			// A pathological "extension" (`notes.this-is-not-really-an-extension`) would overflow the column
			// and fail validation, so anything implausible is simply not an extension.
			$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			$fields[self::FILE_EXTENSION] = (strlen($extension) <= 32) ? $extension : '';
		}

		if(!array_key_exists(self::FRONTMATTER_JSON, $fields))
			$fields[self::FRONTMATTER_JSON] = self::parseFrontmatterJson($name, $content);

		return $fields;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		// Derived columns. When the write carries its own `name:` that path answers for every row; otherwise
		// each row's stored path does, so rows whose derived values differ are updated separately (a content
		// write across differently-named files is rare, but it must not stamp one file's metadata on another).
		if(array_key_exists(self::CONTENT, $fields) && $ids) {
			if(array_key_exists(self::NAME, $fields)) {
				$fields = self::_deriveContentFields($fields, strval($fields[self::NAME]));

			} else {
				$db = DevblocksPlatform::services()->database();

				$names = $db->GetArrayMaster(sprintf("SELECT id, name FROM agent_file WHERE id IN (%s)",
					implode(',', DevblocksPlatform::sanitizeArray($ids, 'int'))
				));

				$groups = [];

				foreach($names as $row)
					$groups[strval($row['name'])][] = intval($row['id']);

				if(count($groups) > 1) {
					foreach($groups as $group_name => $group_ids)
						self::update($group_ids, self::_deriveContentFields($fields, $group_name), $check_deltas);

					return;
				}

				if($groups)
					$fields = self::_deriveContentFields($fields, strval(array_key_first($groups)));
			}
		}

		$context = Context_AgentFile::ID;
		self::_updateAbstract($context, $ids, $fields);

		// Which volumes' counters this write invalidates. Read BEFORE the write, because a `filesystem_id`
		// change moves a file between two volumes and BOTH are now wrong -- the one losing it as much as the
		// one gaining it. A rename or a bookkeeping write touches neither, so it doesn't ask.
		$recount_filesystem_ids = [];

		if(
			$ids
			&& (
				array_key_exists(self::CONTENT, $fields)
				|| array_key_exists(self::SIZE, $fields)
				|| array_key_exists(self::FILESYSTEM_ID, $fields)
			)
		) {
			$db = DevblocksPlatform::services()->database();

			$recount_filesystem_ids = array_column($db->GetArrayMaster(sprintf(
				"SELECT DISTINCT filesystem_id FROM agent_file WHERE id IN (%s)",
				implode(',', DevblocksPlatform::sanitizeArray($ids, 'int'))
			)), 'filesystem_id');

			if(array_key_exists(self::FILESYSTEM_ID, $fields))
				$recount_filesystem_ids[] = $fields[self::FILESYSTEM_ID];
		}

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'agent_file', $fields);

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}

			// Re-index NOW rather than on the next `search` cron tick. These files exist to be searched by an
			// agent that may have just written one and will grep for it in the same conversation, so indexing
			// latency reads as data loss. Only a body/path change matters — the index content is
			// `{{name}}` + content — so bookkeeping-only writes don't queue anything.
			if(array_key_exists(self::CONTENT, $fields) || array_key_exists(self::NAME, $fields))
				DevblocksPlatform::services()->search()->queueIndexRecords($context, $batch_ids);
		}

		DAO_AgentFilesystem::recount($recount_filesystem_ids);
	}

	// Bypasses the derived-column pass in update() (and markContextChanged), so never write `content` through
	// this — it's for bookkeeping like the importer's `import_uuid` stamp on unchanged rows.
	static function updateWhere($fields, $where) {
		parent::_updateWhere('agent_file', $fields, $where);
	}

	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}

		return true;
	}

	/**
	 * @param string $where
	 * @return Model_AgentFile[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT content, created_at, file_extension, filesystem_id, frontmatter_json, id, name, sha1, size, updated_at " .
			"FROM agent_file " .
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
	 * @return Model_AgentFile[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_AgentFile|null
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
	 * @return Model_AgentFile[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}


	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AgentFile[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AgentFile();
			$object->content = $row['content'];
			$object->created_at = intval($row['created_at']);
			$object->file_extension = $row['file_extension'];
			$object->filesystem_id = intval($row['filesystem_id']);
			$object->frontmatter_json = $row['frontmatter_json'];
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->sha1 = $row['sha1'];
			$object->size = intval($row['size']);
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function random() {
		return self::_getRandom('agent_file');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_AgentFile::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		// While the rows still exist to be asked
		$filesystem_ids = array_column($db->GetArrayMaster(sprintf(
			"SELECT DISTINCT filesystem_id FROM agent_file WHERE id IN (%s)",
			$ids_list
		)), 'filesystem_id');

		$db->ExecuteMaster(sprintf("DELETE FROM agent_file WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		DAO_AgentFilesystem::recount($filesystem_ids);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AgentFile::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AgentFile', $sortBy);

		$select_sql = sprintf("SELECT " .
			"agent_file.content as %s, " .
			"agent_file.created_at as %s, " .
			"agent_file.file_extension as %s, " .
			"agent_file.filesystem_id as %s, " .
			"agent_file.frontmatter_json as %s, " .
			"agent_file.id as %s, " .
			"agent_file.name as %s, " .
			"agent_file.sha1 as %s, " .
			"agent_file.size as %s, " .
			"agent_file.updated_at as %s",
			SearchFields_AgentFile::CONTENT,
			SearchFields_AgentFile::CREATED_AT,
			SearchFields_AgentFile::FILE_EXTENSION,
			SearchFields_AgentFile::FILESYSTEM_ID,
			SearchFields_AgentFile::FRONTMATTER_JSON,
			SearchFields_AgentFile::ID,
			SearchFields_AgentFile::NAME,
			SearchFields_AgentFile::SHA1,
			SearchFields_AgentFile::SIZE,
			SearchFields_AgentFile::UPDATED_AT
		);

		$join_sql = "FROM agent_file ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AgentFile');

		return [
			'primary_table' => 'agent_file',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		return self::_searchWithTimeout(
			SearchFields_AgentFile::ID,
			$query_parts['select'],
			$query_parts['join'],
			$query_parts['where'],
			$query_parts['sort'],
			$page,
			$limit,
			$withCounts
		);
	}
};

class SearchFields_AgentFile extends DevblocksSearchFields {
	const CONTENT = 'a_content';
	const CREATED_AT = 'a_created_at';
	const FILE_EXTENSION = 'a_file_extension';
	const FILESYSTEM_ID = 'a_filesystem_id';
	const FRONTMATTER_JSON = 'a_frontmatter_json';
	const ID = 'a_id';
	const NAME = 'a_name';
	const SHA1 = 'a_sha1';
	const SIZE = 'a_size';
	const UPDATED_AT = 'a_updated_at';

	static private $_fields = null;

	static function getTableName() : string {
		return 'agent_file';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentFile::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentFile::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentFile::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_AgentFile::ID => new DevblocksSearchFieldContextKeys('agent_file.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_AgentFile::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::ID:
				$models = DAO_AgentFile::getIds($values);
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
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'agent_file', 'content', $translate->_('dao.agent_file.content'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'agent_file', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::FILE_EXTENSION => new DevblocksSearchField(self::FILE_EXTENSION, 'agent_file', 'file_extension', $translate->_('dao.agent_file.file_extension'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::FILESYSTEM_ID => new DevblocksSearchField(self::FILESYSTEM_ID, 'agent_file', 'filesystem_id', $translate->_('dao.agent_file.filesystem_id'), Model_CustomField::TYPE_NUMBER, true),
			self::FRONTMATTER_JSON => new DevblocksSearchField(self::FRONTMATTER_JSON, 'agent_file', 'frontmatter_json', $translate->_('dao.agent_file.frontmatter_json'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'agent_file', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'agent_file', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::SHA1 => new DevblocksSearchField(self::SHA1, 'agent_file', 'sha1', $translate->_('dao.agent_file.sha1'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::SIZE => new DevblocksSearchField(self::SIZE, 'agent_file', 'size', $translate->_('dao.agent_file.size'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'agent_file', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
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

class Model_AgentFile extends DevblocksRecordModel {
	public $content;
	public $created_at;
	public $file_extension;
	public $filesystem_id;
	public $frontmatter_json;
	public $id;
	public $name;
	public $sha1;
	public $size;
	public $updated_at;
};

class View_AgentFile extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'agentfile';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Agent File');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AgentFile::ID;
		$this->renderSortAsc = true;

		$this->view_columns = [
			SearchFields_AgentFile::NAME,
			SearchFields_AgentFile::FILE_EXTENSION,
			SearchFields_AgentFile::FILESYSTEM_ID,
			SearchFields_AgentFile::SIZE,
			SearchFields_AgentFile::UPDATED_AT,
		];

		// Don't surface the file body / frontmatter blob as default worklist columns
		$this->addColumnsHidden([
			SearchFields_AgentFile::CONTENT,
			SearchFields_AgentFile::FRONTMATTER_JSON,
		]);

		$this->doResetCriteria();
	}

	protected function _getData() {
		return DAO_AgentFile::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AgentFile');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_AgentFile', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AgentFile', $size);
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
		$context = Context_AgentFile::ID;

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
		$search_fields = SearchFields_AgentFile::getFields();

		$fields = [
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentFile::CREATED_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_AgentFile::ID],
				]
			],
			'filesystem.id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentFile::FILESYSTEM_ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_AgentFilesystem::ID, 'q' => ''],
				]
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentFile::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_AgentFile::ID, 'q' => ''],
				]
			],
			'file_extension' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentFile::FILE_EXTENSION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentFile::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'size' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentFile::SIZE],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentFile::UPDATED_AT],
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
		$fields = self::_appendFieldsFromQuickSearchContext(Context_AgentFile::ID, $fields, null);
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

		$custom_fields = DAO_CustomField::getByContext(Context_AgentFile::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$filesystems = DAO_AgentFilesystem::getAll();
		$tpl->assign('filesystems', $filesystems);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/agent_file/view.tpl');
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
		return SearchFields_AgentFile::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_AgentFile::CREATED_AT:
			case SearchFields_AgentFile::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_AgentFile::FILESYSTEM_ID:
			case SearchFields_AgentFile::ID:
			case SearchFields_AgentFile::SIZE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_AgentFile::CONTENT:
			case SearchFields_AgentFile::FILE_EXTENSION:
			case SearchFields_AgentFile::FRONTMATTER_JSON:
			case SearchFields_AgentFile::NAME:
			case SearchFields_AgentFile::SHA1:
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

class Context_AgentFile extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.agent.file';
	const URI = 'agent_file';

	static function isReadableByActor($models, $actor) {
		return CerberusContexts::allowEverything($models);
	}

	static function isWriteableByActor($models, $actor) {
		// [TODO] A file's privileges should eventually delegate to its parent agent_filesystem:
		// resolve each model's filesystem_id -> Model_AgentFilesystem and defer to
		// Context_AgentFilesystem::isWriteableByActor($filesystems, $actor), so whoever can write a
		// filesystem can write its files. Filesystems are admin-only for now, so that delegation would
		// resolve to admin-only anyway -- short-circuit to admin-only here until non-admin filesystems exist.
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}

	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}

	function getRandom() {
		return DAO_AgentFile::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=agent_file&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_AgentFile();

		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => ['context' => self::ID],
		];

		$properties['content'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_file.content')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->content,
		];

		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];

		$properties['filesystem_id'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_file.filesystem_id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->filesystem_id,
		];

		$properties['frontmatter_json'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_file.frontmatter_json')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->frontmatter_json,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['sha1'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_file.sha1')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->sha1,
		];

		$properties['size'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_file.size')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->size,
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($agent_file = DAO_AgentFile::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($agent_file->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $agent_file->id,
			'name' => $agent_file->name,
			'permalink' => $url,
			'updated' => $agent_file->updated_at,
			'created' => $agent_file->created_at,
		];
	}

	function getDefaultProperties() : array {
		return [
			'created_at',
			'updated_at',
		];
	}

	function getContext($agent_file, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Agent File:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_AgentFile::ID);

		if(is_numeric($agent_file)) {
			$agent_file = DAO_AgentFile::get($agent_file);
		} elseif($agent_file instanceof Model_AgentFile) {
			DevblocksPlatform::noop();
		} elseif(is_array($agent_file)) {
			$agent_file = Cerb_ORMHelper::recastArrayToModel($agent_file, 'Model_AgentFile');
		} else {
			$agent_file = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'content' => $prefix.$translate->_('dao.agent_file.content'),
			'file_extension' => $prefix.$translate->_('dao.agent_file.file_extension'),
			'filesystem_id' => $prefix.$translate->_('dao.agent_file.filesystem_id'),
			'frontmatter_json' => $prefix.$translate->_('dao.agent_file.frontmatter_json'),
			'sha1' => $prefix.$translate->_('dao.agent_file.sha1'),
			'size' => $prefix.$translate->_('dao.agent_file.size'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'content' => Model_CustomField::TYPE_SINGLE_LINE,
			'file_extension' => Model_CustomField::TYPE_SINGLE_LINE,
			'filesystem_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'frontmatter_json' => Model_CustomField::TYPE_SINGLE_LINE,
			'sha1' => Model_CustomField::TYPE_SINGLE_LINE,
			'size' => Model_CustomField::TYPE_SINGLE_LINE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_AgentFile::ID;
		$token_values['_type'] = 'agent_file';
		$token_values['_types'] = $token_types;

		if($agent_file) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $agent_file->name;
			$token_values['id'] = $agent_file->id;
			$token_values['name'] = $agent_file->name;
			$token_values['created_at'] = $agent_file->created_at;
			$token_values['updated_at'] = $agent_file->updated_at;
			$token_values['content'] = $agent_file->content;
			$token_values['file_extension'] = $agent_file->file_extension;
			$token_values['filesystem_id'] = $agent_file->filesystem_id;
			$token_values['frontmatter_json'] = $agent_file->frontmatter_json;
			$token_values['sha1'] = $agent_file->sha1;
			$token_values['size'] = $agent_file->size;
			$token_values = $this->_importModelCustomFieldsAsValues($agent_file, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=agent_file&id=%d-%s", $agent_file->id, DevblocksPlatform::strToPermalink($agent_file->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'content' => DAO_AgentFile::CONTENT,
			'created_at' => DAO_AgentFile::CREATED_AT,
			'file_extension' => DAO_AgentFile::FILE_EXTENSION,
			'filesystem_id' => DAO_AgentFile::FILESYSTEM_ID,
			'frontmatter_json' => DAO_AgentFile::FRONTMATTER_JSON,
			'id' => DAO_AgentFile::ID,
			'name' => DAO_AgentFile::NAME,
			'sha1' => DAO_AgentFile::SHA1,
			'size' => DAO_AgentFile::SIZE,
			'updated_at' => DAO_AgentFile::UPDATED_AT,
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

		$context = Context_AgentFile::ID;
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
		$view->name = 'Agent File';
		$view->renderSortBy = SearchFields_AgentFile::UPDATED_AT;
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
		$view->name = 'Agent File';

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
		$context = Context_AgentFile::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_AgentFile::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}

		if(empty($context_id) || $edit) {
			// ACL
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

			// Current filesystem (for the RecordChooser seed on edit)
			if($model && $model->filesystem_id)
				$tpl->assign('filesystem', DAO_AgentFilesystem::get($model->filesystem_id));

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/agent_file/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
