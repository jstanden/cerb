<?php

use Cerb\Extensions\Extension_QueueConsumer;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;

class DAO_Queue extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const EXTENSION_ID = 'extension_id';
	const EXTENSION_PARAMS_JSON = 'extension_params_json';
	const ID = 'id';
	const IS_FIFO = 'is_fifo';
	const NAME = 'name';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'queues_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			->addValidator($validation->validators()->extension('\Cerb\Extensions\Extension_QueueConsumer'))
		;
		$validation
			->addField(self::EXTENSION_PARAMS_JSON)
			->string()
			->setMaxLength(65_535)
		;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
		;
		$validation
			->addField(self::IS_FIFO)
			->bit()
		;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			->setUnique(__CLASS__)
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '._'))) {
					$error = "may only contain letters, numbers, underscores, and dots";
					return false;
				}
				
				if(strlen($string) > 255) {
					$error = "must be shorter than 255 characters.";
					return false;
				}
				
				return true;
			})
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
		
		if(!array_key_exists(DAO_Queue::CREATED_AT, $fields))
			$fields[DAO_Queue::CREATED_AT] = time();
		
		$sql = "INSERT INTO queue () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_Queue::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_QUEUE;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'queue', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.queue.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('queue', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$context = CerberusContexts::CONTEXT_QUEUE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Queue[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, extension_id, extension_params_json, is_fifo, created_at, updated_at ".
			"FROM queue ".
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
	 * @return Model_Queue[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_Queue::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		
			if(!is_array($objects))
				return [];
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_Queue
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$queues = self::getAll();
		
		if(array_key_exists($id, $queues))
			return $queues[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_Queue[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	static function getByName(string $name) : ?Model_Queue {
		$queues = self::getByNames([$name]);
		
		if(!$queues)
			return null;
		
		return current($queues);
	}

	static function getByNames(array $names) : array {
		if(empty($names))
			return [];
		
		$queues = self::getAll();
		$queue_names_to_ids = array_change_key_case(array_column($queues, 'id', 'name'), CASE_LOWER);
		$queues_names_to_find = array_map(fn($name) => DevblocksPlatform::strLower($name), $names);
		$results = [];
		
		foreach($queues_names_to_find as $name) {
			if(array_key_exists($name, $queue_names_to_ids)) {
				$queue_id = $queue_names_to_ids[$name];
				$results[$queue_id] = $queues[$queue_id];
			}
		}
		
		return $results;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Queue[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Queue();
			$object->id = intval($row['id']);
			$object->is_fifo = intval($row['is_fifo']) ? 1 : 0;
			$object->extension_id = $row['extension_id'];
			$object->name = $row['name'];
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			
			if(false !== ($json = json_decode($row['extension_params_json'] ?? '', true)))
				$object->extension_params = $json;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('queue');
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_QUEUE;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM queue WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Queue::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Queue', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"queue.id as %s, ".
			"queue.name as %s, ".
			"queue.extension_id as %s, ".
			"queue.is_fifo as %s, ".
			"queue.created_at as %s, ".
			"queue.updated_at as %s ",
			SearchFields_Queue::ID,
			SearchFields_Queue::NAME,
			SearchFields_Queue::EXTENSION_ID,
			SearchFields_Queue::IS_FIFO,
			SearchFields_Queue::CREATED_AT,
			SearchFields_Queue::UPDATED_AT
		);
		
		$join_sql = "FROM queue ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Queue');
		
		return array(
			'primary_table' => 'queue',
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
			SearchFields_Queue::ID,
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

class DAO_QueueJob {
	private static function _getResultAsModel(array $row) : ?Model_QueueJob {
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
	
	static function get(int $id) : ?Model_QueueJob {
		$results = self::getIds([$id]);
		return $results[$id] ?? null;
	}
	
	public static function getIds(array $ids) {
		$db = DevblocksPlatform::services()->database();
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(!$ids) return [];
		
		$sql = sprintf(
			"SELECT id, queue_id, name, singleton_key, status_id, worker_id, metadata, count_total, count_available, count_inflight, count_done, count_failed, created_at, updated_at ".
			"FROM queue_job ".
			"WHERE id IN (%s)",
			implode(',', $ids)
		);
		
		if(!($rows = $db->GetArrayMaster($sql)))
			return [];
		
		return array_combine(
			array_column($rows, 'id'),
			array_map(fn($row) => self::_getResultAsModel($row), $rows)
		);
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
			"SELECT id, queue_id, name, singleton_key, status_id, worker_id, metadata, count_total, count_available, count_inflight, count_done, count_failed, created_at, updated_at ".
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
		
		// Find changed queue jobs that are now done
		$sql = sprintf(
			"SELECT id, queue_id, name, singleton_key, status_id, worker_id, metadata, count_total, count_available, count_inflight, count_done, count_failed, created_at, updated_at ".
			"FROM queue_job ".
			"WHERE id IN (%s) ".
			"AND status_id != 2 ".
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
			"UPDATE queue_job SET status_id = %d WHERE id IN (%s)",
			$status->value,
			implode(',', $job_ids)
		);
		$db->ExecuteWriter($sql);
	}
}

class DAO_QueueLog {
	// [TODO]
}

enum QueueMessageStatus : int {
	case AVAILABLE = 0;
	case IN_FLIGHT = 1;
	case FAILED = 2;
	case DONE = 3;
}

class DAO_QueueMessage {
	/**
	 * @param Model_Queue $queue
	 * @param array $messages
	 * @param int $job_id
	 * @param int $available_at
	 * @return array|false
	 */
	static function enqueue(Model_Queue $queue, array $messages, int $job_id=0, int $available_at=0) {
		$db = DevblocksPlatform::services()->database();
		$nodeProvider = new RandomNodeProvider();
		
		if(empty($messages))
			return false;
		
		$results = [];
		$insert_values = [];
		
		foreach($messages as $message) {
			$uuid = Uuid::uuid6($nodeProvider->getNode());
			$message_uuid = $uuid->getHex();
			
			$insert_values[] = sprintf("(%s, %d, %d, %d, %d, %s, %s, %d)",
				'0x' . $db->escape($message_uuid),
				$queue->id,
				$job_id,
				QueueMessageStatus::AVAILABLE->value,
				time(),
				$db->escape('NULL'),
				$db->qstr(json_encode($message)),
				$available_at
			);
			
			$results[] = $message_uuid->toString();
		}
		
		$db->ExecuteWriter(
			sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, status_at, consumer_id, message, available_at) VALUES %s",
			implode(',', $insert_values)
		));
		
		return $results;
	}
	
	/**
	 * @param Model_Queue $queue
	 * @param int|null $limit
	 * @param $consumer_id
	 * @param ?int $job_id (null=any, zero=no job)
	 * @return Model_QueueMessage[]
	 */
	static function dequeue(Model_Queue $queue, ?int $limit=1, &$consumer_id=null, ?int $job_id=null) : array {
		$db = DevblocksPlatform::services()->database();
		$nodeProvider = new RandomNodeProvider();
		
		if(!is_numeric($limit) || !$limit)
			$limit = 1;
		
		$uuid = Uuid::uuid6($nodeProvider->getNode());
		$consumer_id = '0x' . $uuid->getHex();
		
		$db->ExecuteWriter(
			sprintf(
				"UPDATE queue_message SET status_id=%d, status_at=%d, consumer_id=%s ".
				"WHERE queue_id=%d %s%sAND status_id=%d AND available_at <= %d LIMIT %d",
				self::STATUS_IN_FLIGHT,
				QueueMessageStatus::IN_FLIGHT->value,
				time(),
				$db->escape($consumer_id),
				$queue->id,
				!is_null($job_id) ? sprintf("AND job_id=%d ", $job_id) : '',
				QueueMessageStatus::AVAILABLE->value,
				time(),
				$limit
			)
		);
		
		$results = $db->GetArrayMaster(sprintf(
			"SELECT uuid, job_id, message, available_at FROM queue_message ".
			"WHERE queue_id=%d %s%sAND status_id=%d AND consumer_id=%s",
			$queue->id,
			!is_null($job_id) ? sprintf("AND job_id=%d ", $job_id) : '',
			QueueMessageStatus::IN_FLIGHT->value,
			$db->escape($consumer_id)
		));
		
		$messages = [];
		
		if(!$results)
			return $messages;
		
		foreach($results as $result) {
			$message = new Model_QueueMessage();
			$message->uuid = Uuid::fromBytes($result['uuid'])->getHex()->toString();
			$message->queue_id = intval($queue->id);
			$message->job_id = intval($result['job_id']);
			$message->message = json_decode($result['message'], true);
			$message->available_at = intval($result['available_at']);
			$messages[] = $message;
		}
		
		unset($results);
		
		$job_ids = array_unique(array_filter(array_column($messages, 'job_id')));
		
		// If we have pulled from a job, update its counts
		if($job_ids) {
			foreach ($job_ids as $job_id)
				DAO_QueueJob::syncProgress($job_id);
		}
		
		return $messages;
	}
	
	static function reportSuccess(array $message_uuids) : void {
		self::_reportStatus(QueueMessageStatus::DONE, $message_uuids);
	}
	
	static function reportFailure(array $message_uuids) : void {
		self::_reportStatus(QueueMessageStatus::FAILED, $message_uuids);
	}
	
	static private function _reportStatus($status_id, $message_uuids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$message_uuids)
			return;
		
		$insert_values = array_map(
			fn($uuid) => '0x' . $db->escape($uuid),
			$message_uuids
		);
		
		$db->ExecuteWriter(sprintf("UPDATE queue_message SET status_id=%d WHERE uuid IN (%s)",
			$status_id,
			implode(',', $insert_values)
		));
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$before = time()-86400;
		
		$db->ExecuteWriter(sprintf("DELETE FROM queue_message WHERE status_id = 3 AND status_at < %d",
			$before
		));
	}
}

class SearchFields_Queue extends DevblocksSearchFields {
	const CREATED_AT = 'q_created_at';
	const EXTENSION_ID = 'q_extension_id';
	const ID = 'q_id';
	const IS_FIFO = 'q_is_fifo';
	const NAME = 'q_name';
	const UPDATED_AT = 'q_updated_at';
	
	static private $_fields = null;
	
	static function getTableName() : string {
		return 'queue';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Queue::ID);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Queue::UPDATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_QUEUE => new DevblocksSearchFieldContextKeys('queue.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, CerberusContexts::CONTEXT_QUEUE, self::getPrimaryKey())))
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
			case SearchFields_Queue::ID:
				$models = DAO_Queue::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
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
		
		$columns = [
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'queue', 'created_at', $translate->_('common.created'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'queue', 'extension_id', $translate->_('common.extension'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'queue', 'id', $translate->_('common.id'), null, true),
			self::IS_FIFO => new DevblocksSearchField(self::IS_FIFO, 'queue', 'is_fifo', $translate->_('dao.queue.is_fifo'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'queue', 'name', $translate->_('common.name'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'queue', 'updated_at', $translate->_('common.updated'), null, true),
		];
		
		// Virtual fields
		if(($virtual_columns = DevblocksSearchField::getVirtualFields()))
			$columns = array_merge($columns, $virtual_columns);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Model_Queue extends DevblocksRecordModel {
	public $created_at = 0;
	public $extension_id = '';
	public $extension_params = [];
	public $id = 0;
	public $is_fifo = 0;
	public $name = '';
	public $updated_at = 0;
	
	public function getExtension() : Extension_QueueConsumer {
		return Extension_QueueConsumer::get($this->extension_id);
	}
};

enum QueueJobStatus : int{
	case RUNNING = 0;
	case PAUSED = 1;
	case DONE = 2;
}

class Model_QueueJob {
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
		return $this->status_id == QueueJobStatus::DONE;
	}
}

class Model_QueueMessage {
	public string $uuid = '';
	public int $queue_id = 0;
	public $message = null;
	public int $job_id = 0;
	public int $available_at = 0;
	
	public function reportStatus(QueueMessageStatus $status, string $message='') : void {
		$queue_service = DevblocksPlatform::services()->queue();
		
		if(QueueMessageStatus::DONE == $status) {
			$queue_service->reportSuccess([$this], $message);
		} else {
			$queue_service->reportFailure([$this], $message);
		}
	}
}

class View_Queue extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'queues';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.queues');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Queue::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_Queue::NAME,
			SearchFields_Queue::EXTENSION_ID,
			SearchFields_Queue::IS_FIFO,
			SearchFields_Queue::UPDATED_AT,
		];
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Queue::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Queue');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_Queue', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Queue', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					case SearchFields_Queue::EXTENSION_ID:
					case SearchFields_Queue::IS_FIFO:
						$pass = true;
						break;
						
					// Valid custom fields
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
		$context = CerberusContexts::CONTEXT_QUEUE;
		
		if(!array_key_exists($column, $fields))
			return [];
		
		switch($column) {
			case SearchFields_Queue::EXTENSION_ID:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Queue::IS_FIFO:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
				
			default:
				// Custom fields
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
		$search_fields = SearchFields_Queue::getFields();
		
		$fields = array(
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Queue::CREATED_AT),
				),
			'extension' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Queue::EXTENSION_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_QUEUE],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Queue::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_QUEUE, 'q' => ''],
					]
				),
			'isFifo' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Queue::IS_FIFO),
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Queue::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Queue::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_WATCHERS],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_QUEUE, $fields, null);
		
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
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_QUEUE);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/queue/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		
		switch($field) {
			case SearchFields_Queue::IS_FIFO:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
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
		return SearchFields_Queue::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_Queue::NAME:
			case SearchFields_Queue::EXTENSION_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_Queue::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_Queue::IS_FIFO:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_Queue::CREATED_AT:
			case SearchFields_Queue::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			default:
				// Custom Fields
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

class Context_Queue extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete, IDevblocksContextWorkflow {
	const ID = CerberusContexts::CONTEXT_QUEUE;
	const URI = 'queue';
	
	static function isReadableByActor($models, $actor) : bool {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) : bool {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}
	
	static function isDeletableByActor($models, $actor) : bool {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Queue::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=queue&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Queue();
		
		$properties['created'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		$properties['extension_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.extension'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->extension_id,
		);
		
		$properties['is_fifo'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.queue.is_fifo'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_fifo,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($queue = DAO_Queue::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($queue->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $queue->id,
			'name' => $queue->name,
			'permalink' => $url,
			'updated' => $queue->updated_at,
		);
	}
	
	function getDefaultProperties() : array {
		return [
			'extension_id',
			'is_fifo',
			'updated_at',
		];
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(($model = DAO_Queue::getByName($alias)))
			return $model->id;
		
		return null;
	}
	
	public function autocomplete($term, $query = null) {
		$list = [];
		
		list($results,) = DAO_Queue::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Queue::NAME,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%'),
			),
			25,
			0,
			SearchFields_Queue::NAME,
			true,
			false
		);
		
		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_Queue::NAME];
			$entry->value = $row[SearchFields_Queue::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($queue, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Queue:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_QUEUE);
		
		// Polymorph
		if(is_numeric($queue)) {
			$queue = DAO_Queue::get($queue);
		} elseif($queue instanceof Model_Queue) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($queue)) {
			$queue = Cerb_ORMHelper::recastArrayToModel($queue, 'Model_Queue');
		} else {
			$queue = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'extension_id' => $prefix.$translate->_('common.extension'),
			'id' => $prefix.$translate->_('common.id'),
			'is_fifo' => $prefix.$translate->_('dao.queue.is_fifo'),
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_fifo' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_QUEUE;
		$token_values['_type'] = 'queue';
		$token_values['_types'] = $token_types;
		
		if($queue) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $queue->name;
			$token_values['created_at'] = $queue->created_at;
			$token_values['extension_id'] = $queue->extension_id;
			$token_values['id'] = $queue->id;
			$token_values['is_fifo'] = $queue->is_fifo ? 1 : 0;
			$token_values['name'] = $queue->name;
			$token_values['updated_at'] = $queue->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($queue, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=queue&id=%d-%s",$queue->id, DevblocksPlatform::strToPermalink($queue->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_Queue::CREATED_AT,
			'extension_id' => DAO_Queue::EXTENSION_ID,
			'id' => DAO_Queue::ID,
			'is_fifo' => DAO_Queue::IS_FIFO,
			'links' => '_links',
			'name' => DAO_Queue::NAME,
			'updated_at' => DAO_Queue::UPDATED_AT,
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
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_QUEUE;
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
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Queues';
		$view->renderSortBy = SearchFields_Queue::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Queue';
		
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
		$context = CerberusContexts::CONTEXT_QUEUE;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(!($model = DAO_Queue::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$queue_extension = $model->getExtension();
				$tpl->assign('queue_extension', $queue_extension);
				
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
			
			// Extensions
			
			$queue_extensions = Extension_QueueConsumer::getAll(false);
			$tpl->assign('queue_extensions', $queue_extensions);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/queue/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}

	function workflowExport(array $ids, DevblocksWorkflowExportModel $export_model, bool $include_children = false): array {
		$workflow_kata = [
			'records' => [],
		];
		
		$record_uri = CerberusContexts::getContextName($this->id, 'uri');
		
		$models = DAO_Queue::getIds($ids);
		
		foreach($models as $model) {
			$model_key = $export_model->getLabelMapFor(sprintf('%s_%d', $record_uri, $model->id));
			$record_key = sprintf('%s/%s', $record_uri, $model_key);
			
			$workflow_kata['records'][$record_key] = [
				'fields' => [
					'name' => $model->name,
					'extension_id' => $model->extension_id,
					'is_fifo' => $model->is_fifo ? 1 : 0,
				],
			];
		}
		
		return $workflow_kata;
	}
};