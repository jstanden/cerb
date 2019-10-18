<?php
class DAO_JiraIssue extends Cerb_ORMHelper {
	const CREATED = 'created';
	const DESCRIPTION = 'description';
	const ID = 'id';
	const JIRA_ID = 'jira_id';
	const JIRA_KEY = 'jira_key';
	const JIRA_PROJECT_ID = 'jira_project_id';
	const JIRA_VERSIONS = 'jira_versions';
	const PROJECT_ID = 'project_id';
	const STATUS = 'status';
	const SUMMARY = 'summary';
	const TYPE = 'type';
	const UPDATED = 'updated';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		// text
		$validation
			->addField(self::DESCRIPTION)
			->string()
			->setMaxLength(65535)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::JIRA_ID)
			->id()
			;
		// varchar(32)
		$validation
			->addField(self::JIRA_KEY)
			->string()
			->setMaxLength(32)
			;
		// int(10) unsigned
		$validation
			->addField(self::JIRA_PROJECT_ID)
			->id()
			;
		// smallint(5) unsigned
		$validation
			->addField(self::STATUS)
			->string()
			;
		// smallint(5) unsigned
		$validation
			->addField(self::TYPE)
			->string()
			;
		// varchar(255)
		$validation
			->addField(self::JIRA_VERSIONS)
			->string()
			;
		// int(10) unsigned
		$validation
			->addField(self::PROJECT_ID)
			->id()
			;
		// varchar(255)
		$validation
			->addField(self::SUMMARY)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED)
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
		
		$sql = "INSERT INTO jira_issue () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$context = Context_JiraIssue::ID;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(Context_JiraIssue::ID, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'jira_issue', $fields);
			
			// Send events
			if($check_deltas) {
				// Local events
				self::_processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.jira_issue.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(Context_JiraIssue::ID, $batch_ids);
			}
		}
	}
	
	static function _processUpdateEvents($ids, $change_fields) {
		// We only care about these fields, so abort if they aren't referenced

		$observed_fields = array(
			DAO_JiraIssue::STATUS,
		);
		
		$used_fields = array_intersect($observed_fields, array_keys($change_fields));
		
		if(empty($used_fields))
			return;
		
		// Load records only if they're needed
		
		if(false == ($before_models = CerberusContexts::getCheckpoints(Context_JiraIssue::ID, $ids)))
			return;
		
		foreach($before_models as $id => $before_model) {
			$before_model = (object) $before_model; /* @var $before_model Model_JiraIssue */
			
			/*
			 * Status change
			 */
			// [TODO] Fold into 'Record changed'
			
			@$status = $change_fields[DAO_JiraIssue::STATUS];
			
			if($status == $before_model->status)
				unset($change_fields[DAO_JiraIssue::STATUS]);
			
			if(isset($change_fields[DAO_JiraIssue::STATUS])) {
				Event_JiraIssueStatusChanged::trigger($id);
			}
		}
		
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('jira_issue', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_JiraIssue[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, jira_project_id, project_id, jira_id, jira_key, jira_versions, type, status, summary, description, created, updated ".
			"FROM jira_issue ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param integer $id
	 * @return Model_JiraIssue	 */
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
	
	static function random() {
		return self::_getRandom('jira_issue');
	}
	
	static function randomComment($issue_id=null) {
		$db = DevblocksPlatform::services()->database();
		
		// With a specific issue ID?
		if($issue_id && false != ($comment_id = $db->GetOneSlave(sprintf("SELECT id FROM jira_issue_comment WHERE issue_id = %d ORDER BY RAND() LIMIT 1", $issue_id))))
			return $comment_id;
		
		return $db->GetOneSlave("SELECT id FROM jira_issue_comment ORDER BY RAND() LIMIT 1");
	}
	
	static function getByJiraId($remote_id) {
		$results = self::getWhere(sprintf("%s = %d", self::JIRA_ID, $remote_id));
		
		if(empty($results))
			return NULL;
		
		return current($results);
	}
	
	static function getByJiraIdForBackend($remote_id, $account_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT id FROM jira_issue WHERE jira_id = %d AND project_id IN (SELECT id FROM jira_project WHERE connected_account_id = %d)",
			$remote_id,
			$account_id
		);
		$local_id = $db->GetOneSlave($sql);
		
		if(empty($local_id))
			return NULL;
		
		return DAO_JiraIssue::get($local_id);
	}
	
	static function getByJiraKey($issue_key) {
		$results = self::getWhere(sprintf("%s = %s",
			self::JIRA_KEY,
			Cerb_ORMHelper::qstr($issue_key)
		));
		
		if(empty($results))
			return NULL;
		
		return current($results);
	}
	
	static function saveComment($jira_comment_id, $jira_issue_id, $issue_id, $created, $author, $body) {
		$db = DevblocksPlatform::services()->database();
		
		$comment_id = $db->GetOneMaster(sprintf('SELECT id FROM jira_issue_comment WHERE jira_comment_id = %d AND issue_id = %d',
			$jira_comment_id,
			$issue_id
		));
		
		if($comment_id) {
			$db->ExecuteMaster(sprintf("UPDATE jira_issue_comment SET body = %s WHERE id = %d",
				$db->qstr($body),
				$comment_id
			));
			
		} else {
			$db->ExecuteMaster(sprintf("INSERT INTO jira_issue_comment (jira_comment_id, jira_issue_id, issue_id, created, jira_author, body) ".
				"VALUES (%d, %d, %d, %d, %s, %s) ",
				$jira_comment_id,
				$jira_issue_id,
				$issue_id,
				$created,
				$db->qstr($author),
				$db->qstr($body)
			));
			
			$comment_id = $db->LastInsertId();
			
			// If we inserted, trigger 'New JIRA issue comment' event
			Event_JiraIssueCommented::trigger($issue_id, $comment_id);
		}
		
		return $comment_id;
	}
	
	static function getCommentsByIssueId($issue_id) {
		$db = DevblocksPlatform::services()->database();

		$results = $db->GetArraySlave(sprintf("SELECT jira_comment_id, jira_issue_id, created, jira_author, body ".
			"FROM jira_issue_comment ".
			"WHERE issue_id = %d ".
			"ORDER BY created DESC",
			$issue_id
		));
		
		return $results;
	}
	
	static function getComment($comment_id) {
		$db = DevblocksPlatform::services()->database();

		$results = $db->GetRowSlave(sprintf("SELECT id, issue_id, jira_comment_id, jira_issue_id, created, jira_author, body ".
			"FROM jira_issue_comment ".
			"WHERE id = %d ".
			"ORDER BY created DESC",
			$comment_id
		));
		
		return $results;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_JiraIssue[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_JiraIssue();
			$object->id = $row['id'];
			$object->project_id = $row['project_id'];
			$object->jira_id = $row['jira_id'];
			$object->jira_key = $row['jira_key'];
			$object->jira_project_id = $row['jira_project_id'];
			$object->jira_versions = $row['jira_versions'];
			$object->type = $row['type'];
			$object->status = $row['status'];
			$object->summary = $row['summary'];
			$object->description = $row['description'];
			$object->created = $row['created'];
			$object->updated = $row['updated'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM jira_issue WHERE id IN (%s)", $ids_list));
		
		// Cascade delete to linked tables
		$db->ExecuteMaster("DELETE FROM jira_issue_comment WHERE issue_id NOT IN (SELECT id FROM jira_issue)");
		
		// [TODO] Maint
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => Context_JiraIssue::ID,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_JiraIssue::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_JiraIssue', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"jira_issue.id as %s, ".
			"jira_issue.jira_id as %s, ".
			"jira_issue.jira_key as %s, ".
			"jira_issue.project_id as %s, ".
			"jira_issue.jira_project_id as %s, ".
			"jira_issue.jira_versions as %s, ".
			"jira_issue.type as %s, ".
			"jira_issue.status as %s, ".
			"jira_issue.summary as %s, ".
			"jira_issue.created as %s, ".
			"jira_issue.updated as %s ",
				SearchFields_JiraIssue::ID,
				SearchFields_JiraIssue::JIRA_ID,
				SearchFields_JiraIssue::JIRA_KEY,
				SearchFields_JiraIssue::PROJECT_ID,
				SearchFields_JiraIssue::JIRA_PROJECT_ID,
				SearchFields_JiraIssue::JIRA_VERSIONS,
				SearchFields_JiraIssue::TYPE,
				SearchFields_JiraIssue::STATUS,
				SearchFields_JiraIssue::SUMMARY,
				SearchFields_JiraIssue::CREATED,
				SearchFields_JiraIssue::UPDATED
			);
			
		$join_sql = "FROM jira_issue ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_JiraIssue');
	
		return array(
			'primary_table' => 'jira_issue',
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
		$db = DevblocksPlatform::services()->database();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_JiraIssue::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(jira_issue.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_JiraIssue extends DevblocksSearchFields {
	const ID = 'j_id';
	const PROJECT_ID = 'j_project_id';
	const JIRA_ID = 'j_jira_id';
	const JIRA_KEY = 'j_jira_key';
	const JIRA_PROJECT_ID = 'j_jira_project_id';
	const JIRA_VERSIONS = 'j_jira_versions';
	const STATUS = 'j_status';
	const TYPE = 'j_type';
	const SUMMARY = 'j_summary';
	const CREATED = 'j_created';
	const UPDATED = 'j_updated';
	
	const FULLTEXT_CONTENT = 'ft_j_content';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_PROJECT_SEARCH = '*_project_search';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'jira_issue.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			Context_JiraIssue::ID => new DevblocksSearchFieldContextKeys('jira_issue.id', self::ID),
			Context_JiraProject::ID => new DevblocksSearchFieldContextKeys('jira_issue.project_id', self::PROJECT_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_CONTENT:
				return self::_getWhereSQLFromFulltextField($param, Search_JiraIssue::ID, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, Context_JiraIssue::ID, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(Context_JiraIssue::ID)), self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_PROJECT_SEARCH:
				$sql = "SELECT id FROM jira_project WHERE id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, Context_JiraProject::ID, $sql, 'jira_issue.project_id');
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, Context_JiraIssue::ID, self::getPrimaryKey());
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
		
		return false;
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'project':
				$key = 'project.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_JiraIssue::PROJECT_ID:
				$models = DAO_JiraProject::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_JiraIssue::ID:
				$models = DAO_JiraIssue::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'summary', 'id');
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
			self::ID => new DevblocksSearchField(self::ID, 'jira_issue', 'id', $translate->_('common.id'), null, true),
			self::PROJECT_ID => new DevblocksSearchField(self::PROJECT_ID, 'jira_issue', 'project_id', $translate->_('dao.jira_issue.project_id'), null, true),
			self::JIRA_ID => new DevblocksSearchField(self::JIRA_ID, 'jira_issue', 'jira_id', $translate->_('dao.jira_issue.jira_id'), null, true),
			self::JIRA_KEY => new DevblocksSearchField(self::JIRA_KEY, 'jira_issue', 'jira_key', $translate->_('dao.jira_issue.jira_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::JIRA_PROJECT_ID => new DevblocksSearchField(self::JIRA_PROJECT_ID, 'jira_issue', 'jira_project_id', null, null, true),
			self::JIRA_VERSIONS => new DevblocksSearchField(self::JIRA_VERSIONS, 'jira_issue', 'jira_versions', $translate->_('dao.jira_issue.jira_versions'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'jira_issue', 'type', $translate->_('common.type'), null, true),
			self::STATUS => new DevblocksSearchField(self::STATUS, 'jira_issue', 'status', $translate->_('common.status'), null, true),
			self::SUMMARY => new DevblocksSearchField(self::SUMMARY, 'jira_issue', 'summary', $translate->_('dao.jira_issue.summary'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'jira_issue', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'jira_issue', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_PROJECT_SEARCH => new DevblocksSearchField(self::VIRTUAL_PROJECT_SEARCH, '*', 'project_search', null, null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			
			self::FULLTEXT_CONTENT => new DevblocksSearchField(self::FULLTEXT_CONTENT, 'ft', 'content', $translate->_('common.content'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_CONTENT]->ft_schema = Search_JiraIssue::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_JiraIssue extends Extension_DevblocksSearchSchema {
	const ID = 'jira.search.schema.jira_issue';
	
	public function getNamespace() {
		return 'jira_issue';
	}
	
	public function getAttributes() {
		return [];
	}
	
	public function getFields() {
		return array(
			'content',
		);
	}
	
	public function query($query, $attributes=[], $limit=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ids = $engine->query($this, $query, $attributes, $limit);
		
		return $ids;
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the index has a delta, start from the current record
		if($meta['is_indexed_externally']) {
			// Do nothing (let the remote tool update the DB)
			
		// Otherwise, start over
		} else {
			$this->setIndexPointer(self::INDEX_POINTER_RESET);
		}
	}
	
	public function setIndexPointer($pointer) {
		switch($pointer) {
			case self::INDEX_POINTER_RESET:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', 0);
				break;
				
			case self::INDEX_POINTER_CURRENT:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', time());
				break;
		}
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::services()->log();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%1$s = %2$d AND %3$s > %4$d) OR (%1$s > %2$d)',
				DAO_JiraIssue::UPDATED,
				$ptr_time,
				DAO_JiraIssue::ID,
				$id
			);
			$issues = DAO_JiraIssue::getWhere($where, array(DAO_JiraIssue::UPDATED, DAO_JiraIssue::ID), array(true, true), 100);

			if(empty($issues)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			foreach($issues as $issue) { /* @var $issue Model_JiraIssue */
				$id = $issue->id;
				$ptr_time = $issue->updated;
				
				$ptr_id = ($last_time == $ptr_time) ? $id : 0;
				
				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));
				
				$doc = array(
					'content' => implode("\n", array(
						$issue->jira_key,
						$issue->summary,
						$issue->description,
					))
				);
				
				$comments = $issue->getComments();
				if(is_array($comments))
				foreach($comments as $comment) {
					$doc['content'] .= "\n" . $comment['body'];
				}
				
				if(false === ($engine->index($this, $id, $doc)))
					return false;
			}
		}
		
		// If we ran out of records, always reset the ID and use the current time
		if($done) {
			$ptr_id = 0;
			$ptr_time = time();
		}
		
		$this->setParam('last_indexed_id', $ptr_id);
		$this->setParam('last_indexed_time', $ptr_time);
	}
	
	public function delete($ids) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		return $engine->delete($this, $ids);
	}
};

class Model_JiraIssue {
	public $id;
	public $project_id;
	public $jira_id;
	public $jira_key;
	public $jira_project_id;
	public $status;
	public $type;
	public $jira_versions;
	public $summary;
	public $description;
	public $created;
	public $updated;
	
	function getProject() {
		return DAO_JiraProject::get($this->project_id);
	}
	
	function getType() {
		return $this->type;
	}
	
	function getStatus() {
		return $this->status;
	}
	
	function getComments() {
		return DAO_JiraIssue::getCommentsByIssueId($this->id);
	}
};

class View_JiraIssue extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'jira_issues';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('JIRA Issues');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_JiraIssue::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_JiraIssue::JIRA_KEY,
			SearchFields_JiraIssue::PROJECT_ID,
			SearchFields_JiraIssue::JIRA_VERSIONS,
			SearchFields_JiraIssue::TYPE,
			SearchFields_JiraIssue::STATUS,
			SearchFields_JiraIssue::UPDATED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_JiraIssue::JIRA_ID,
			SearchFields_JiraIssue::VIRTUAL_CONTEXT_LINK,
			SearchFields_JiraIssue::VIRTUAL_HAS_FIELDSET,
			SearchFields_JiraIssue::VIRTUAL_PROJECT_SEARCH,
			SearchFields_JiraIssue::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_JiraIssue::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_JiraIssue');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_JiraIssue', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_JiraIssue', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_JiraIssue::PROJECT_ID:
				case SearchFields_JiraIssue::STATUS:
				case SearchFields_JiraIssue::TYPE:
				case SearchFields_JiraIssue::JIRA_VERSIONS:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_JiraIssue::VIRTUAL_CONTEXT_LINK:
				case SearchFields_JiraIssue::VIRTUAL_HAS_FIELDSET:
				case SearchFields_JiraIssue::VIRTUAL_WATCHERS:
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
		$context = Context_JiraIssue::ID;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_JiraIssue::JIRA_VERSIONS:
			case SearchFields_JiraIssue::STATUS:
			case SearchFields_JiraIssue::TYPE:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_JiraIssue::PROJECT_ID:
				$label_map = [];
				
				$projects = DAO_JiraProject::getAll();
				foreach($projects as $project)
					$label_map[$project->id] = $project->name;
				
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_JiraIssue::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_JiraIssue::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_JiraIssue::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_JiraIssue::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_JiraIssue::FULLTEXT_CONTENT),
				),
			'content' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_JiraIssue::FULLTEXT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_JiraIssue::CREATED),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_JiraIssue::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_JiraIssue::ID],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_JiraIssue::ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_JiraIssue::ID, 'q' => ''],
					]
				),
			'key' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraIssue::JIRA_KEY),
					'examples' => array(
						'CHD',
					),
			),
			'project' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_JiraIssue::VIRTUAL_PROJECT_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => Context_JiraProject::ID, 'q' => ''],
					]
			),
			'project.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_JiraIssue::PROJECT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_JiraProject::ID, 'q' => ''],
					]
			),
			'status' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraIssue::STATUS, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
			),
			'summary' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraIssue::SUMMARY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraIssue::TYPE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
			),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_JiraIssue::UPDATED),
				),
			'version' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraIssue::JIRA_VERSIONS, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'examples' => array(
						'1.*',
						'("2.0")',
					),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_JiraIssue::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_JiraIssue::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_JiraIssue::ID, $fields, null);
		$fields = self::_appendFieldsFromQuickSearchContext(Context_JiraProject::ID, $fields, 'project');
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_JiraIssue::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
			$fields['content']['examples'] = $ft_examples;
		}
		
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
			
			case 'project':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_JiraIssue::VIRTUAL_PROJECT_SEARCH);
				break;
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_JiraIssue::VIRTUAL_WATCHERS, $tokens);
				break;
				
			default:
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
		$custom_fields = DAO_CustomField::getByContext(Context_JiraIssue::ID);
		$tpl->assign('custom_fields', $custom_fields);

		// Projects
		
		$projects = DAO_JiraProject::getAll();
		$tpl->assign('projects', $projects);
		
		$tpl->assign('view_template', 'devblocks:wgm.jira::jira_issue/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_JiraIssue::PROJECT_ID:
				$strings = [];
				$projects = DAO_JiraProject::getAll();
				
				foreach($values as $v) {
					if(array_key_exists($v, $projects))
						$strings[] = DevblocksPlatform::strEscapeHtml($projects[$v]->name);
				}
				
				echo implode(' or ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_JiraIssue::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_JiraIssue::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_JiraIssue::VIRTUAL_PROJECT_SEARCH:
				echo sprintf("Project matches <b>%s</b>", DevblocksPlatform::strEscapeHtml($param->value));
				break;
			
			case SearchFields_JiraIssue::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_JiraIssue::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_JiraIssue::JIRA_KEY:
			case SearchFields_JiraIssue::JIRA_VERSIONS:
			case SearchFields_JiraIssue::STATUS:
			case SearchFields_JiraIssue::SUMMARY:
			case SearchFields_JiraIssue::TYPE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_JiraIssue::ID:
			case SearchFields_JiraIssue::JIRA_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_JiraIssue::CREATED:
			case SearchFields_JiraIssue::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_JiraIssue::PROJECT_ID:
			case SearchFields_JiraIssue::JIRA_PROJECT_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$options = DevblocksPlatform::sanitizeArray($options, 'integer', array('nonzero','unique'));
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
				
			case SearchFields_JiraIssue::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_JiraIssue::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_JiraIssue::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',[]);
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

class Context_JiraIssue extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.jira.issue';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can view
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function getRandom() {
		return DAO_JiraIssue::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=jira_issue&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_JiraIssue();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['jira_key'] = array(
			'label' => mb_ucfirst($translate->_('dao.jira_issue.jira_key')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->jira_key,
		);
		
		$properties['project_id'] = array(
			'label' => mb_ucfirst($translate->_('dao.jira_issue.project_id')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->getProject()->id,
			'params' => [
				'context' => Context_JiraProject::ID,
			],
		);
		
		/*
		$properties['jira_project_id'] = array(
			'label' => mb_ucfirst($translate->_('dao.jira_issue.jira_project_id')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->getProject()->id,
			'params' => [
				'context' => Context_JiraProject::ID,
			],
		);
		*/
		
		$properties['jira_versions'] = array(
			'label' => mb_ucfirst($translate->_('dao.jira_issue.jira_versions')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->jira_versions,
		);
		
		$properties['type'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->type,
		);
		
		$properties['status'] = array(
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->status
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$jira_issue = DAO_JiraIssue::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($jira_issue->summary);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $jira_issue->id,
			'name' => sprintf("[%s] %s", $jira_issue->jira_key, $jira_issue->summary),
			'permalink' => $url,
			'updated' => $jira_issue->updated,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				switch($key) {
					case 'project__label':
						$label = 'Project';
						break;
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
			'project__label',
			'jira_key',
			'jira_type',
			'jira_status',
			'jira_versions',
			'created',
			'updated',
		);
	}
	
	function getContext($jira_issue, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'JIRA Issue:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_JiraIssue::ID);

		// Polymorph
		if(is_numeric($jira_issue)) {
			$jira_issue = DAO_JiraIssue::get($jira_issue);
		} elseif($jira_issue instanceof Model_JiraIssue) {
			// It's what we want already.
		} elseif(is_array($jira_issue)) {
			$jira_issue = Cerb_ORMHelper::recastArrayToModel($jira_issue, 'Model_JiraIssue');
		} else {
			$jira_issue = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'jira_key' => $prefix.$translate->_('dao.jira_issue.jira_key'),
			'jira_type' => $prefix.$translate->_('common.type'),
			'jira_status' => $prefix.$translate->_('common.status'),
			'summary' => $prefix.$translate->_('dao.jira_issue.summary'),
			'description' => $prefix.$translate->_('common.description'),
			'created' => $prefix.$translate->_('common.created'),
			'updated' => $prefix.$translate->_('common.updated'),
			'jira_versions' => $prefix.$translate->_('dao.jira_issue.jira_versions'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		];
		
		// Token types
		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'jira_key' => Model_CustomField::TYPE_SINGLE_LINE,
			'jira_type' => Model_CustomField::TYPE_SINGLE_LINE,
			'jira_status' => Model_CustomField::TYPE_SINGLE_LINE,
			'summary' => Model_CustomField::TYPE_SINGLE_LINE,
			'description' => Model_CustomField::TYPE_MULTI_LINE,
			'created' => Model_CustomField::TYPE_DATE,
			'updated' => Model_CustomField::TYPE_DATE,
			'jira_versions' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
		];
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_JiraIssue::ID;
		$token_values['_types'] = $token_types;
		
		if($jira_issue) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = '[' . $jira_issue->jira_key . '] ' . $jira_issue->summary;
			$token_values['id'] = $jira_issue->id;
			$token_values['jira_id'] = $jira_issue->jira_id;
			$token_values['jira_key'] = $jira_issue->jira_key;
			$token_values['jira_project_id'] = $jira_issue->jira_project_id;
			$token_values['jira_status'] = $jira_issue->status;
			$token_values['jira_type'] = $jira_issue->type;
			$token_values['jira_versions'] = $jira_issue->jira_versions;
			$token_values['project_id'] = $jira_issue->project_id;
			$token_values['summary'] = $jira_issue->summary;
			$token_values['description'] = $jira_issue->description;
			$token_values['created'] = $jira_issue->created;
			$token_values['updated'] = $jira_issue->updated;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($jira_issue, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=jira_issue&id=%d-%s",$jira_issue->id, DevblocksPlatform::strToPermalink($jira_issue->summary)), true);
		}
		
		// JIRA Project
		$merge_token_labels = $merge_token_values = [];
		CerberusContexts::getContext(Context_JiraProject::ID, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'project_',
			$prefix.'Project:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);

		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created' => DAO_JiraIssue::CREATED,
			'id' => DAO_JiraIssue::ID,
			'jira_id' => DAO_JiraIssue::JIRA_ID,
			'jira_key' => DAO_JiraIssue::JIRA_KEY,
			'jira_project_id' => DAO_JiraIssue::JIRA_PROJECT_ID,
			'status' => DAO_JiraIssue::STATUS,
			'type' => DAO_JiraIssue::TYPE,
			'links' => '_links',
			'project_id' => DAO_JiraIssue::PROJECT_ID,
			'summary' => DAO_JiraIssue::SUMMARY,
			'description' => DAO_JiraIssue::DESCRIPTION,
			'updated' => DAO_JiraIssue::UPDATED,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['description'] = [
			'label' => 'Description',
			'type' => 'Text',
		];
		
		$lazy_keys['discussion'] = [
			'label' => 'Discussion',
			'type' => 'HashMap',
		];
		
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_JiraIssue::ID;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'discussion':
				$values['discussion'] = DAO_JiraIssue::getCommentsByIssueId($context_id);
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
		$view->addParams(array(
		), true);
		$view->renderSortBy = SearchFields_JiraIssue::UPDATED;
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
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_JiraIssue::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = Context_JiraIssue::ID;
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Model
		
		if(empty($context_id) || null == ($jira_issue = DAO_JiraIssue::get($context_id)))
			return;
		
		$tpl->assign('model', $jira_issue);
		
		if($jira_issue) {
			if(false != ($jira_project = $jira_issue->getProject()))
				$tpl->assign('jira_base_url', $jira_project->getBaseUrl());
		}
		
		// Dictionary
		$labels = [];
		$values = [];
		CerberusContexts::getContext($context, $jira_issue, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
	
		$custom_fields = DAO_CustomField::getByContext(Context_JiraIssue::ID, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(Context_JiraIssue::ID, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}

		// Comments
		$comments = DAO_Comment::getByContext(Context_JiraIssue::ID, $context_id);
		$comments = array_reverse($comments, true);
		$tpl->assign('comments', $comments);
		
		// Interactions
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
		
		$tpl->display('devblocks:wgm.jira::jira_issue/peek.tpl');
	}
};