<?php
enum QueueJobStatus : int{
	case RUNNING = 0;
	case PAUSED = 1;
	case DONE = 2;
	case CANCELED = 3;
}

class DAO_QueueJob extends Cerb_ORMHelper {
	const COUNT_AVAILABLE = 'count_available';
	const COUNT_DONE = 'count_done';
	const COUNT_FAILED = 'count_failed';
	const COUNT_INFLIGHT = 'count_inflight';
	const COUNT_TOTAL = 'count_total';
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const METADATA = 'metadata';
	const NAME = 'name';
	const QUEUE_ID = 'queue_id';
	const SINGLETON_KEY = 'singleton_key';
	const STATUS_ID = 'status_id';
	const UPDATED_AT = 'updated_at';
	const WORKER_ID = 'worker_id';

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
			->setMaxLength(255)
			;
		$validation
			->addField(self::QUEUE_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_QUEUE))
			;
		$validation
			->addField(self::STATUS_ID)
			->uint()
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::WORKER_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER))
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;

		return $validation->getFields();
	}

	static function create(Model_QueueJob $job) : ?Model_QueueJob {
		$db = DevblocksPlatform::services()->database();

		if(!$job->created_at) $job->created_at = time();
		if(!$job->updated_at) $job->updated_at = time();

		// If there's a unique key, check for dupes first
		if($job->singleton_key && ($dupe_job = DAO_QueueJob::getOpenByQueueAndSingleton($job->queue_id, $job->singleton_key))) {
			return $dupe_job;
		}

		$result = $db->ExecuteMaster(sprintf(
			"INSERT IGNORE INTO queue_job (queue_id, `name`, singleton_key, status_id, worker_id, metadata, count_total, count_available, count_inflight, count_done, count_failed, created_at, updated_at) ".
			"VALUES (%d, %s, %s, %d, %d, %s, %d, %d, %d, %d, %d, %d, %d)",
			$job->queue_id,
			$db->qstr($job->name),
			$db->qstr($job->singleton_key),
			$job->status_id,
			$job->worker_id,
			$db->qstr(json_encode($job->metadata)),
			$job->count_total,
			$job->count_available,
			$job->count_inflight,
			$job->count_done,
			$job->count_failed,
			$job->created_at,
			$job->updated_at
		));

		if(!$result || !($id = $db->LastInsertId()))
			return null;

		$job->id = $id;

		return $job;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_QueueJob::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'queue_job', $fields);

			if($check_deltas) {
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.queue_job.update',
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
		parent::_updateWhere('queue_job', $fields, $where);
	}

	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}

		$context = CerberusContexts::CONTEXT_QUEUE_JOB;

		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;

		return true;
	}

	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_QueueJob[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT id, name, queue_id, worker_id, singleton_key, status_id, metadata, count_total, count_available, count_inflight, count_done, count_failed, created_at, updated_at " .
			"FROM queue_job " .
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
	 * @return Model_QueueJob[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_QueueJob|null
	 */
	static function get($id) {
		if(empty($id))
			return null;

		$objects = self::getWhere(sprintf("%s = %d", self::ID, $id));

		if(array_key_exists($id, $objects))
			return $objects[$id];

		return null;
	}

	/**
	 * @param array $ids
	 * @return Model_QueueJob[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	static function getOpenByQueueAndSingleton(mixed $queue_id, string $singleton_key) : ?Model_QueueJob {
		$db = DevblocksPlatform::services()->database();

		if(is_string($queue_id) && !is_numeric($queue_id)) {
			if (!($queue = DAO_Queue::getByName($queue_id)))
				return null;

			$queue_id = $queue->id;
		}

		if(!$queue_id)
			return null;

		$sql = sprintf(
			"SELECT id, name, queue_id, worker_id, singleton_key, status_id, metadata, count_total, count_available, count_inflight, count_done, count_failed, created_at, updated_at ".
			"FROM queue_job ".
			"WHERE queue_id = %d ".
			"AND singleton_key = %s ".
			"AND status_id IN (0,1)",
			$queue_id,
			$db->qstr($singleton_key)
		);

		if(!($row = $db->GetRowMaster($sql)))
			return null;

		return self::_getResultAsModel($row);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_QueueJob[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = self::_getResultAsModel($row);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static private function _getResultAsModel(array $row) : Model_QueueJob {
		$job = new Model_QueueJob();
		$job->count_available = intval($row['count_available']);
		$job->count_done = intval($row['count_done']);
		$job->count_failed = intval($row['count_failed']);
		$job->count_inflight = intval($row['count_inflight']);
		$job->count_total = intval($row['count_total']);
		$job->created_at = intval($row['created_at']);
		$job->id = intval($row['id']);
		$job->metadata = json_decode($row['metadata'] ?? '', true);
		$job->name = $row['name'];
		$job->queue_id = intval($row['queue_id']);
		$job->singleton_key = $row['singleton_key'];
		$job->status_id = intval($row['status_id']);
		$job->updated_at = intval($row['updated_at']);
		$job->worker_id = intval($row['worker_id']);
		return $job;
	}

	static function random() {
		return self::_getRandom('queue_job');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_QueueJob::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		DAO_QueueMessage::deleteByJobIds($ids);
		DAO_QueueJobChunk::deleteByJobIds($ids);
		DAO_QueueJobLog::deleteByJobIds($ids);
		$db->ExecuteMaster(sprintf("DELETE FROM queue_job WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		return true;
	}

	static function bulkUpdate(Model_ContextBulkUpdate $update) : bool {
		$do = $update->actions;
		$ids = $update->context_ids;

		if(empty($ids) || empty($do))
			return false;

		// Don't let the parent bulk-update job delete itself mid-flight
		if($update->job_id)
			$ids = array_values(array_filter($ids, fn($id) => $id != $update->job_id));

		if(empty($ids))
			return false;

		$deleted = false;

		foreach($do as $k => $v) {
			if ($k == 'delete') {
				$deleted = true;
			}
		}

		if($deleted) {
			CerberusContexts::logActivityRecordDelete(Context_QueueJob::ID, $ids);
			DAO_QueueJob::delete($ids);
		}

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_QueueJob::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_QueueJob', $sortBy);

		$select_sql = sprintf("SELECT " .
			"queue_job.count_available as %s, ".
			"queue_job.count_done as %s, ".
			"queue_job.count_failed as %s, ".
			"queue_job.count_inflight as %s, ".
			"queue_job.count_total as %s, ".
			"queue_job.created_at as %s, ".
			"queue_job.id as %s, ".
			"queue_job.name as %s, ".
			"queue_job.queue_id as %s, ".
			"queue_job.singleton_key as %s, ".
			"queue_job.status_id as %s, ".
			"queue_job.updated_at as %s, ".
			"queue_job.worker_id as %s",
			SearchFields_QueueJob::COUNT_AVAILABLE,
			SearchFields_QueueJob::COUNT_DONE,
			SearchFields_QueueJob::COUNT_FAILED,
			SearchFields_QueueJob::COUNT_INFLIGHT,
			SearchFields_QueueJob::COUNT_TOTAL,
			SearchFields_QueueJob::CREATED_AT,
			SearchFields_QueueJob::ID,
			SearchFields_QueueJob::NAME,
			SearchFields_QueueJob::QUEUE_ID,
			SearchFields_QueueJob::SINGLETON_KEY,
			SearchFields_QueueJob::STATUS_ID,
			SearchFields_QueueJob::UPDATED_AT,
			SearchFields_QueueJob::WORKER_ID
		);

		$join_sql = "FROM queue_job ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ", implode(' AND ', $wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_QueueJob');

		return [
			'primary_table' => 'queue_job',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	/**
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
		$query_parts = self::getSearchQueryComponents($columns, $params, $sortBy, $sortAsc);

		return self::_searchWithTimeout(
			SearchFields_QueueJob::ID,
			$query_parts['select'],
			$query_parts['join'],
			$query_parts['where'],
			$query_parts['sort'],
			$page,
			$limit,
			$withCounts
		);
	}

	public static function syncProgress(int $job_id) : void {
		$db = DevblocksPlatform::services()->database();

		$sql = sprintf(
			"UPDATE queue_job JOIN ( ".
			"SELECT SUM(status_id=0) AS count_available, SUM(status_id=1) AS count_inflight, SUM(status_id=2) AS count_failed, SUM(status_id=3) AS count_done, COUNT(*) AS count_total FROM queue_message WHERE job_id = %d".
			") AS agg ON queue_job.id = %d ".
			"SET ".
			"queue_job.count_total = agg.count_total, ".
			"queue_job.count_available = agg.count_available, ".
			"queue_job.count_inflight = agg.count_inflight, ".
			"queue_job.count_failed = agg.count_failed, ".
			"queue_job.count_done = agg.count_done",
			$job_id,
			$job_id
		);
		$db->ExecuteWriter($sql);
	}

	/**
	 * @param array $job_ids
	 * @return Model_QueueJob[]
	 */
	public static function checkForCompletedJobs(array $job_ids) : array {
		$db = DevblocksPlatform::services()->database();

		$job_ids = DevblocksPlatform::sanitizeArray($job_ids, 'int');

		if(!$job_ids) return [];

		$sql = sprintf(
			"SELECT id, name, queue_id, worker_id, singleton_key, status_id, metadata, count_total, count_available, count_inflight, count_done, count_failed, created_at, updated_at ".
			"FROM queue_job ".
			"WHERE id IN (%s) ".
			"AND status_id NOT IN (2, 3) ".
			"AND (0=count_available+count_inflight)",
			implode(',', $job_ids)
		);
		$results = $db->GetArrayMaster($sql);

		if(!$results)
			return [];

		return array_combine(
			array_column($results, 'id'),
			array_map(fn($row) => self::_getResultAsModel($row), $results)
		);
	}

	public static function setStatus(array $job_ids, QueueJobStatus $status) : void {
		$db = DevblocksPlatform::services()->database();

		$job_ids = DevblocksPlatform::sanitizeArray($job_ids, 'int');

		if(!$job_ids) return;

		$sql = sprintf(
			"UPDATE queue_job SET status_id = %d, updated_at = %d WHERE id IN (%s)",
			$status->value,
			time(),
			implode(',', $job_ids)
		);
		$db->ExecuteWriter($sql);
	}
	
	public static function getAvailableMessages(Model_Queue $queue) : array {
		$db = DevblocksPlatform::services()->database();

		$sql = sprintf("SELECT job_id, count(*) AS hits FROM queue_message " .
			"WHERE queue_id = %d AND status_id = 0 AND consumer_id IS NULL " .
			"GROUP BY job_id",
			$queue->id,
		);
		return $db->GetArrayMaster($sql);
	}

	/**
	 * Count messages still open (available + inflight) for a given job. Reads
	 * master so monitor pacing decisions don't lag behind concurrent workers.
	 *
	 * Sibling to getAvailableMessages() — that one excludes claimed messages for
	 * cron dispatch decisions; this one includes them so monitors see in-flight
	 * work and don't prematurely scale down.
	 */
	public static function getAvailableAndInFlightMessages(Model_QueueJob $job) : int {
		$db = DevblocksPlatform::services()->database();

		return intval($db->GetOneMaster(sprintf(
			"SELECT COUNT(*) FROM queue_message ".
			"WHERE queue_id = %d AND status_id IN (0,1) AND job_id = %d",
			$job->queue_id,
			$job->id
		)));
	}
}

class SearchFields_QueueJob extends DevblocksSearchFields {
	const COUNT_AVAILABLE = 'qj_count_available';
	const COUNT_DONE = 'qj_count_done';
	const COUNT_FAILED = 'qj_count_failed';
	const COUNT_INFLIGHT = 'qj_count_inflight';
	const COUNT_TOTAL = 'qj_count_total';
	const CREATED_AT = 'qj_created_at';
	const ID = 'qj_id';
	const NAME = 'qj_name';
	const QUEUE_ID = 'qj_queue_id';
	const SINGLETON_KEY = 'qj_singleton_key';
	const STATUS_ID = 'qj_status_id';
	const UPDATED_AT = 'qj_updated_at';
	const WORKER_ID = 'qj_worker_id';

	const VIRTUAL_QUEUE_SEARCH = '*_queue_search';
	const VIRTUAL_STATUS = '*_status';
	const VIRTUAL_WORKER_SEARCH = '*_worker_search';

	static private $_fields = null;

	static function getTableName() : string {
		return 'queue_job';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_QueueJob::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_QueueJob::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_QueueJob::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_QueueJob::ID => new DevblocksSearchFieldContextKeys('queue_job.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_QUEUE_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_QUEUE, 'queue_job.queue_id');

			case self::VIRTUAL_STATUS:
				$values = is_array($param->value) ? $param->value : [$param->value];
				$statuses = [];

				$oper = match($param->operator) {
					DevblocksSearchCriteria::OPER_NIN,
					DevblocksSearchCriteria::OPER_NIN_OR_NULL => 'NOT ',
					default => '',
				};

				foreach($values as $value) {
					switch(substr(DevblocksPlatform::strLower($value), 0, 1)) {
						case 'r': $statuses[] = QueueJobStatus::RUNNING->value; break;
						case 'p': $statuses[] = QueueJobStatus::PAUSED->value; break;
						case 'd': $statuses[] = QueueJobStatus::DONE->value; break;
						case 'c': $statuses[] = QueueJobStatus::CANCELED->value; break;
					}
				}

				if(empty($statuses))
					return null;

				return sprintf('queue_job.status_id %sIN (%s) ', $oper, implode(', ', $statuses));

			case self::VIRTUAL_WORKER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 'queue_job.worker_id');

			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_QueueJob::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::ID:
				$models = DAO_QueueJob::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');

			case self::QUEUE_ID:
				$models = DAO_Queue::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');

			case self::STATUS_ID:
				return [
					QueueJobStatus::RUNNING->value => 'Running',
					QueueJobStatus::PAUSED->value => 'Paused',
					QueueJobStatus::DONE->value => 'Done',
					QueueJobStatus::CANCELED->value => 'Canceled',
				];

			case self::WORKER_ID:
				$models = DAO_Worker::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				if(in_array(0, $values))
					$label_map[0] = DevblocksPlatform::translate('common.nobody');
				return $label_map;
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
			self::COUNT_AVAILABLE => new DevblocksSearchField(self::COUNT_AVAILABLE, 'queue_job', 'count_available', $translate->_('dao.queue_job.count_available'), null, true),
			self::COUNT_DONE => new DevblocksSearchField(self::COUNT_DONE, 'queue_job', 'count_done', $translate->_('dao.queue_job.count_done'), null, true),
			self::COUNT_FAILED => new DevblocksSearchField(self::COUNT_FAILED, 'queue_job', 'count_failed', $translate->_('dao.queue_job.count_failed'), null, true),
			self::COUNT_INFLIGHT => new DevblocksSearchField(self::COUNT_INFLIGHT, 'queue_job', 'count_inflight', $translate->_('dao.queue_job.count_inflight'), null, true),
			self::COUNT_TOTAL => new DevblocksSearchField(self::COUNT_TOTAL, 'queue_job', 'count_total', $translate->_('dao.queue_job.count_total'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'queue_job', 'created_at', $translate->_('common.created'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'queue_job', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'queue_job', 'name', $translate->_('common.name'), null, true),
			self::QUEUE_ID => new DevblocksSearchField(self::QUEUE_ID, 'queue_job', 'queue_id', $translate->_('dao.queue_job.queue_id'), null, true),
			self::SINGLETON_KEY => new DevblocksSearchField(self::SINGLETON_KEY, 'queue_job', 'singleton_key', $translate->_('dao.queue_job.singleton_key'), null, true),
			self::STATUS_ID => new DevblocksSearchField(self::STATUS_ID, 'queue_job', 'status_id', $translate->_('common.status'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'queue_job', 'updated_at', $translate->_('common.updated'), null, true),
			self::VIRTUAL_QUEUE_SEARCH => new DevblocksSearchField(self::VIRTUAL_QUEUE_SEARCH, '*', 'queue_search', null, null, false),
			self::VIRTUAL_STATUS => new DevblocksSearchField(self::VIRTUAL_STATUS, '*', 'status', $translate->_('common.status'), null, false),
			self::VIRTUAL_WORKER_SEARCH => new DevblocksSearchField(self::VIRTUAL_WORKER_SEARCH, '*', 'worker_search', null, null, false),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'queue_job', 'worker_id', $translate->_('common.worker'), null, true),
		];

		if(($virtual_columns = DevblocksSearchField::getVirtualFields()))
			$columns = array_merge($columns, $virtual_columns);

		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));

		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
}

class Model_QueueJob extends DevblocksRecordModel {
	public int $count_available = 0;
	public int $count_done = 0;
	public int $count_failed = 0;
	public int $count_inflight = 0;
	public int $count_total = 0;
	public int $created_at = 0;
	public int $id = 0;
	public mixed $metadata = null;
	public string $name = '';
	public int $queue_id = 0;
	public string $singleton_key = '';
	public int $status_id = 0;
	public int $updated_at = 0;
	public int $worker_id = 0;

	public function getProgress() : array {
		$stats = [
			'total' => $this->count_total,
			'counts' => [
				'available' => $this->count_available,
				'inflight' => $this->count_inflight,
				'failed' => $this->count_failed,
				'done' => $this->count_done,
			],
			'percents' =>
				$this->count_total
					// If we have a denominator, we can calculate percentages
					? [
					'available' => round($this->count_available / $this->count_total, 2),
					'inflight' => round($this->count_inflight / $this->count_total, 2),
					'failed' => round($this->count_failed / $this->count_total, 2),
					'done' => round($this->count_done / $this->count_total, 2),
				]
					// Otherwise, zero
					: array_fill_keys(['available','inflight','failed','done'], 0)
			,
		];

		return $stats;
	}

	public function isDone() : bool {
		return $this->status_id == QueueJobStatus::DONE->value;
	}

	public function isTerminal() : bool {
		return in_array(
			$this->status_id,
			[QueueJobStatus::DONE->value, QueueJobStatus::CANCELED->value],
			true
		);
	}
}

class View_QueueJob extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'queue_jobs';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Queue Jobs';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_QueueJob::UPDATED_AT;
		$this->renderSortAsc = false;

		$this->view_columns = [
			SearchFields_QueueJob::NAME,
			SearchFields_QueueJob::STATUS_ID,
			SearchFields_QueueJob::QUEUE_ID,
			SearchFields_QueueJob::WORKER_ID,
			SearchFields_QueueJob::COUNT_TOTAL,
			SearchFields_QueueJob::COUNT_DONE,
			SearchFields_QueueJob::COUNT_FAILED,
			SearchFields_QueueJob::UPDATED_AT,
		];

		$this->addColumnsHidden([
			SearchFields_QueueJob::VIRTUAL_STATUS,
		]);

		$this->doResetCriteria();
	}

	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_QueueJob::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_QueueJob');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_QueueJob', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_QueueJob', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;

			switch($field_key) {
				case SearchFields_QueueJob::QUEUE_ID:
				case SearchFields_QueueJob::SINGLETON_KEY:
				case SearchFields_QueueJob::STATUS_ID:
				case SearchFields_QueueJob::WORKER_ID:
					$pass = true;
					break;

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
		$context = Context_QueueJob::ID;

		if(!array_key_exists($column, $fields))
			return [];

		switch($column) {
			case SearchFields_QueueJob::QUEUE_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_QueueJob::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in');
				break;

			case SearchFields_QueueJob::SINGLETON_KEY:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;

			case SearchFields_QueueJob::STATUS_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_QueueJob::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in');
				break;

			case SearchFields_QueueJob::WORKER_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_QueueJob::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in');
				break;

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
		$search_fields = SearchFields_QueueJob::getFields();

		$fields = [
			'count.available' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_QueueJob::COUNT_AVAILABLE],
			],
			'count.done' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_QueueJob::COUNT_DONE],
			],
			'count.failed' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_QueueJob::COUNT_FAILED],
			],
			'count.inflight' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_QueueJob::COUNT_INFLIGHT],
			],
			'count.total' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_QueueJob::COUNT_TOTAL],
			],
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_QueueJob::CREATED_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_QueueJob::ID],
				]
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_QueueJob::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_QueueJob::ID, 'q' => ''],
				]
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_QueueJob::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'queue' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => SearchFields_QueueJob::VIRTUAL_QUEUE_SEARCH],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_QUEUE, 'q' => ''],
				],
			],
			'singleton.key' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_QueueJob::SINGLETON_KEY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'status' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => SearchFields_QueueJob::VIRTUAL_STATUS],
				'examples' => ['running', 'paused', 'done', '[r,p]', '![d]'],
			],
			'status.id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_QueueJob::STATUS_ID],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_QueueJob::UPDATED_AT],
			],
			'worker' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => SearchFields_QueueJob::VIRTUAL_WORKER_SEARCH],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
				],
			],
		];

		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		$fields = self::_appendFieldsFromQuickSearchContext(Context_QueueJob::ID, $fields, null);
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		ksort($fields);

		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');

			case 'queue':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_QueueJob::VIRTUAL_QUEUE_SEARCH);

			case 'status':
				$oper = null;
				$value = null;
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);

				$statuses = [];
				foreach($value as $v) {
					switch(substr(DevblocksPlatform::strLower($v), 0, 1)) {
						case 'r': $statuses['running'] = true; break;
						case 'p': $statuses['paused']  = true; break;
						case 'd': $statuses['done']    = true; break;
					}
				}

				return new DevblocksSearchCriteria(SearchFields_QueueJob::VIRTUAL_STATUS, $oper, array_keys($statuses));

			case 'worker':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_QueueJob::VIRTUAL_WORKER_SEARCH);

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

		$custom_fields = DAO_CustomField::getByContext(Context_QueueJob::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$queues = DAO_Queue::getAll();
		$tpl->assign('queues', $queues);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/queue_job/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? [$param->value] : $param->value;

		switch($field) {
			case SearchFields_QueueJob::QUEUE_ID:
				$label_map = SearchFields_QueueJob::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			case SearchFields_QueueJob::STATUS_ID:
				$labels = SearchFields_QueueJob::getLabelsForKeyValues(SearchFields_QueueJob::STATUS_ID, $values);
				$this->_renderCriteriaParamString($param, $labels);
				break;

			case SearchFields_QueueJob::WORKER_ID:
				$label_map = SearchFields_QueueJob::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			case SearchFields_QueueJob::VIRTUAL_QUEUE_SEARCH:
				echo sprintf('%s matches <b>%s</b>',
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('dao.queue_job.queue_id')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;

			case SearchFields_QueueJob::VIRTUAL_STATUS:
				$values = is_array($param->value) ? $param->value : [$param->value];
				$labels = [];
				foreach($values as $v) {
					$labels[] = '<b>' . DevblocksPlatform::strEscapeHtml(match(substr(DevblocksPlatform::strLower($v), 0, 1)) {
						'r' => 'Running',
						'p' => 'Paused',
						'd' => 'Done',
						default => $v,
					}) . '</b>';
				}
				echo sprintf('Status is %s', implode(' or ', $labels));
				break;

			case SearchFields_QueueJob::VIRTUAL_WORKER_SEARCH:
				echo sprintf('%s matches <b>%s</b>',
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.worker')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;

			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_QueueJob::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_QueueJob::NAME:
			case SearchFields_QueueJob::SINGLETON_KEY:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			case SearchFields_QueueJob::COUNT_AVAILABLE:
			case SearchFields_QueueJob::COUNT_DONE:
			case SearchFields_QueueJob::COUNT_FAILED:
			case SearchFields_QueueJob::COUNT_INFLIGHT:
			case SearchFields_QueueJob::COUNT_TOTAL:
			case SearchFields_QueueJob::ID:
			case SearchFields_QueueJob::QUEUE_ID:
			case SearchFields_QueueJob::STATUS_ID:
			case SearchFields_QueueJob::WORKER_ID:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;

			case SearchFields_QueueJob::CREATED_AT:
			case SearchFields_QueueJob::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_QueueJob::VIRTUAL_STATUS:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field, $oper, $options);
				break;

			default:
				if(str_starts_with($field, 'cf_')) {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field, 3));
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
}

class Context_QueueJob extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_QUEUE_JOB;
	const URI = 'queue_job';

	static function isReadableByActor($models, $actor) {
		return CerberusContexts::allowEverything($models);
	}

	static function isWriteableByActor($models, $actor) {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}

	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}

	function getRandom() {
		return DAO_QueueJob::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=queue_job&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_QueueJob();

		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => ['context' => self::ID],
		];

		$properties['queue_id'] = [
			'label' => mb_ucfirst($translate->_('dao.queue_job.queue_id')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->queue_id,
			'params' => ['context' => CerberusContexts::CONTEXT_QUEUE],
		];

		$properties['worker_id'] = [
			'label' => mb_ucfirst($translate->_('common.worker')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->worker_id,
			'params' => ['context' => CerberusContexts::CONTEXT_WORKER],
		];

		$properties['status_id'] = [
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => match($model->status_id) {
				QueueJobStatus::RUNNING->value => 'Running',
				QueueJobStatus::PAUSED->value => 'Paused',
				QueueJobStatus::DONE->value => 'Done',
				QueueJobStatus::CANCELED->value => 'Canceled',
				default => $model->status_id,
			},
		];

		$properties['singleton_key'] = [
			'label' => mb_ucfirst($translate->_('dao.queue_job.singleton_key')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->singleton_key,
		];

		$properties['count_total'] = [
			'label' => mb_ucfirst($translate->_('dao.queue_job.count_total')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->count_total,
		];

		$properties['count_done'] = [
			'label' => mb_ucfirst($translate->_('dao.queue_job.count_done')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->count_done,
		];

		$properties['count_failed'] = [
			'label' => mb_ucfirst($translate->_('dao.queue_job.count_failed')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->count_failed,
		];

		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($queue_job = DAO_QueueJob::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($queue_job->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $queue_job->id,
			'name' => $queue_job->name,
			'permalink' => $url,
			'updated' => $queue_job->updated_at,
		];
	}

	function getDefaultProperties() : array {
		return [
			'queue_id',
			'worker_id',
			'status_id',
			'count_total',
			'count_done',
			'count_failed',
			'updated_at',
		];
	}

	function getContextIdFromAlias($alias) {
		return null;
	}

	function getContext($queue_job, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Queue Job:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_QueueJob::ID);

		// Polymorph
		if(is_numeric($queue_job)) {
			$queue_job = DAO_QueueJob::get($queue_job);
		} elseif($queue_job instanceof Model_QueueJob) {
			DevblocksPlatform::noop();
		} elseif(is_array($queue_job)) {
			$queue_job = Cerb_ORMHelper::recastArrayToModel($queue_job, 'Model_QueueJob');
		} else {
			$queue_job = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'count_available' => $prefix.$translate->_('dao.queue_job.count_available'),
			'count_done' => $prefix.$translate->_('dao.queue_job.count_done'),
			'count_failed' => $prefix.$translate->_('dao.queue_job.count_failed'),
			'count_inflight' => $prefix.$translate->_('dao.queue_job.count_inflight'),
			'count_total' => $prefix.$translate->_('dao.queue_job.count_total'),
			'created_at' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'queue__context' => $prefix.$translate->_('dao.queue_job.queue_id'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'singleton_key' => $prefix.$translate->_('dao.queue_job.singleton_key'),
			'status_id' => $prefix.$translate->_('common.status'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'worker__context' => $prefix.$translate->_('common.worker'),
		];

		$token_types = [
			'_label' => 'context_url',
			'count_available' => Model_CustomField::TYPE_NUMBER,
			'count_done' => Model_CustomField::TYPE_NUMBER,
			'count_failed' => Model_CustomField::TYPE_NUMBER,
			'count_inflight' => Model_CustomField::TYPE_NUMBER,
			'count_total' => Model_CustomField::TYPE_NUMBER,
			'created_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'queue__context' => 'context_url',
			'record_url' => Model_CustomField::TYPE_URL,
			'singleton_key' => Model_CustomField::TYPE_SINGLE_LINE,
			'status_id' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'worker__context' => 'context_url',
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_QueueJob::ID;
		$token_values['_type'] = 'queue_job';
		$token_values['_types'] = $token_types;

		if($queue_job) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $queue_job->name;
			$token_values['count_available'] = $queue_job->count_available;
			$token_values['count_done'] = $queue_job->count_done;
			$token_values['count_failed'] = $queue_job->count_failed;
			$token_values['count_inflight'] = $queue_job->count_inflight;
			$token_values['count_total'] = $queue_job->count_total;
			$token_values['created_at'] = $queue_job->created_at;
			$token_values['id'] = $queue_job->id;
			$token_values['name'] = $queue_job->name;
			$token_values['queue__context'] = CerberusContexts::CONTEXT_QUEUE;
			$token_values['queue_id'] = $queue_job->queue_id;
			$token_values['singleton_key'] = $queue_job->singleton_key;
			$token_values['status_id'] = $queue_job->status_id;
			$token_values['updated_at'] = $queue_job->updated_at;
			$token_values['worker__context'] = CerberusContexts::CONTEXT_WORKER;
			$token_values['worker_id'] = $queue_job->worker_id;
			$token_values = $this->_importModelCustomFieldsAsValues($queue_job, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=queue_job&id=%d-%s", $queue_job->id, DevblocksPlatform::strToPermalink($queue_job->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_QueueJob::CREATED_AT,
			'id' => DAO_QueueJob::ID,
			'links' => '_links',
			'name' => DAO_QueueJob::NAME,
			'queue_id' => DAO_QueueJob::QUEUE_ID,
			'singleton_key' => DAO_QueueJob::SINGLETON_KEY,
			'status_id' => DAO_QueueJob::STATUS_ID,
			'updated_at' => DAO_QueueJob::UPDATED_AT,
			'worker_id' => DAO_QueueJob::WORKER_ID,
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
		$lazy_keys = parent::lazyLoadGetKeys();
		$lazy_keys['queue'] = ['label' => 'Queue', 'key' => 'queue_id'];
		$lazy_keys['worker'] = ['label' => 'Worker', 'key' => 'worker_id'];
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = Context_QueueJob::ID;
		$context_id = $dictionary['id'];

		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];

		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}

		switch($token) {
			case 'queue':
				$labels = [];
				$values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_QUEUE, $dictionary['queue_id'] ?? 0, $labels, $values, null, true, true);
				DevblocksDictionaryDelegate::mergeLabelsAndValues($label_map ?? [], $value_map ?? [], 'queue_', $labels, $values);
				break;

			case 'worker':
				$labels = [];
				$values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $dictionary['worker_id'] ?? 0, $labels, $values, null, true, true);
				DevblocksDictionaryDelegate::mergeLabelsAndValues($label_map ?? [], $value_map ?? [], 'worker_', $labels, $values);
				break;

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
		$view->name = 'Queue Jobs';
		$view->renderSortBy = SearchFields_QueueJob::UPDATED_AT;
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
		$view->name = 'Queue Jobs';

		$params_req = [];

		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(DevblocksSearchField::VIRTUAL_CONTEXT_LINK, 'in', [$context.':'.$context_id]),
			];
		}

		$view->addParamsRequired($params_req, true);
		$view->renderTemplate = 'context';
		return $view;
	}

	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_QueueJob::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_QueueJob::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}

		if(empty($context_id) || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);

			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);

				$tpl->assign('model', $model);

				$metadata_json = '';
				if(!empty($model->metadata) && (is_array($model->metadata) || is_object($model->metadata)))
					$metadata_json = json_encode($model->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				$tpl->assign('metadata_json', $metadata_json);
			}

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/queue_job/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
}
