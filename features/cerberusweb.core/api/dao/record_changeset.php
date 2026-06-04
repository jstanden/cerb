<?php

class DAO_RecordChangeset {
	public static function create(string $record_type, int $record_id, array $changeset_data, int $worker_id) : array {
		$db = DevblocksPlatform::services()->database();
		$ids = [];
		
		$last_changesets = self::getLastChangesets($record_type, $record_id, array_keys($changeset_data));
		
		foreach($changeset_data as $record_key => $changeset_datum) {
			$changeset_data_json = json_encode([$record_key => $changeset_datum]);
			$changeset_data_hash = sha1($changeset_data_json);
			
			$last_changeset = $last_changesets[$record_key] ?? null;
			
			// If the last changeset was identical, return early
			if (
				$last_changeset
				&& $last_changeset->storage_sha1hash == $changeset_data_hash
			) {
				$ids[$record_key] = $last_changeset->id;
				continue;
			}
			
			$result = $db->ExecuteMaster(sprintf("INSERT INTO `record_changeset` (record_type, record_id, record_key, worker_id, created_at, storage_sha1hash) " .
				"VALUES (%s, %d, %s, %d, %d, %s)",
				$db->qstr($record_type),
				$record_id,
				$db->qstr($record_key),
				$worker_id,
				time(),
				$db->qstr($changeset_data_hash),
			));
			
			if (!$result) {
				$ids[$record_key] = false;
				continue;
			}
			
			if (0 == ($id = $db->LastInsertId())) {
				$ids[$record_key] = false;
				continue;
			}
			
			if(!Storage_RecordChangeset::put($id, $changeset_data_json)) {
				$ids[$record_key] = false;
				continue;
			}
			
			$ids[$record_key] = $id;
		}
		
		return $ids;
	}
	
	public static function get(int $id) : ?Model_RecordChangeset {
		$db = DevblocksPlatform::services()->database();
		
		$record = $db->GetRowMaster(sprintf('SELECT id, created_at, record_type, record_id, record_key, worker_id, storage_size, storage_key, storage_extension, storage_profile_id, storage_sha1hash '.
			'FROM record_changeset '.
			'WHERE id = %d',
			$id
		));
		
		if($record)
			return new Model_RecordChangeset($record);
		
		return null;
	}
	
	public static function getLastChangesets(string $record_type, int $record_id, array $record_keys) : array {
		$db = DevblocksPlatform::services()->database();
		
		$sql = '';
		$records = [];
		
		foreach($record_keys as $index => $record_key) {
			if($index)
				$sql .= ' UNION ALL ';
			
			$sql .= sprintf('(SELECT id, created_at, record_type, record_id, record_key, worker_id, storage_size, storage_key, storage_extension, storage_profile_id, storage_sha1hash ' .
				'FROM record_changeset ' .
				'WHERE record_type = %s ' .
				'AND record_id = %d ' .
				'AND record_key = %s ' .
				'ORDER BY id DESC ' .
				'LIMIT 1)',
				$db->qstr($record_type),
				$record_id,
				$db->qstr($record_key)
			);
		}
		
		$results = $db->GetArrayMaster($sql);
		
		if(is_array($results)) {
			foreach($results as $result) {
				$records[$result['record_key']] = new Model_RecordChangeset($result);
			}
		}
		
		return $records;
	}

	public static function getChangesets(string $record_type, int $record_id, string $record_key, int $limit=10, bool $is_descending=true, int $since_id=0) : array {
		$db = DevblocksPlatform::services()->database();
		
		$sort = sprintf('ORDER BY id %s', $is_descending ? 'DESC' : 'ASC');
		
		if($since_id && $is_descending) {
			$sort = sprintf('AND id < %d ', $since_id) . $sort;
		} else if($since_id && !$is_descending) {
			$sort = sprintf('AND id > %d ', $since_id) . $sort;
		}
		
		$results = [];
		
		$records = $db->GetArrayMaster(sprintf('SELECT id, created_at, record_type, record_id, record_key, worker_id, storage_size, storage_key, storage_extension, storage_profile_id, storage_sha1hash '.
			'FROM record_changeset '.
			'WHERE record_type = %s '.
			'AND record_id = %d '.
			'AND record_key = %s '.
			'%s '.
			'LIMIT %d',
			$db->qstr($record_type),
			$record_id,
			$db->qstr($record_key),
			$sort,
			$limit
		));
		
		if(!$records)
			return [];
		
		foreach($records as $record)
			$results[$record['id']] = new Model_RecordChangeset($record);
		
		return $results;
	}
	
	public static function updateStorageMeta(int $id, string $storage_extension_id, int $profile_id, string $storage_key, int $storage_size=0) : bool {
		$db = DevblocksPlatform::services()->database();
		
		$result = $db->ExecuteMaster(sprintf("UPDATE `record_changeset` SET storage_extension=%s, storage_profile_id=%d, storage_key=%s, storage_size=%d WHERE id = %d",
			$db->qstr($storage_extension_id),
			$profile_id,
			$db->qstr($storage_key),
			$storage_size,
			$id
		));
		
		if(!$result)
			return false;
		
		return true;
	}
	
	/**
	 * @param string $record_type
	 * @param int|array $ids
	 * @return bool
	 */
	public static function delete(string $record_type, $ids) : bool {
		$db = DevblocksPlatform::services()->database();
		
		if(is_int($ids))
			$ids = [$ids];
		
		if(!is_array($ids))
			return false;
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($record_type) || empty($ids))
			return false;
		
		$results = $db->GetArrayMaster(sprintf('SELECT id FROM record_changeset WHERE record_type = %s AND record_id IN (%s)',
			$db->qstr($record_type),
			implode(',', $ids)
		));
		
		if($results) {
			Storage_RecordChangeset::delete(array_column($results, 'id'));
		}
		
		$db->ExecuteWriter(sprintf('DELETE FROM record_changeset WHERE record_type = %s AND record_id IN (%s)',
			$db->qstr($record_type),
			implode(',', $ids)
		));
		
		return true;
	}
}

class Model_RecordChangeset extends DevblocksRecordModel {
	public int $created_at = 0;
	public int $id = 0;
	public int $record_id = 0;
	public string $record_key = '';
	public string $record_type = '';
	public int $storage_profile_id = 0;
	public string $storage_extension = '';
	public string $storage_key = '';
	public string $storage_sha1hash = '';
	public int $storage_size = 0;
	public int $worker_id = 0;
	
	function __construct(array $record=[]) {
		if($record) {
			$this->created_at = intval($record['created_at'] ?? 0);	
			$this->id = intval($record['id'] ?? 0);	
			$this->record_id = intval($record['record_id'] ?? 0);	
			$this->record_key = strval($record['record_key'] ?? '');	
			$this->record_type = strval($record['record_type'] ?? '');	
			$this->storage_profile_id = intval($record['storage_profile_id'] ?? 0);	
			$this->storage_extension = strval($record['storage_extension'] ?? '');	
			$this->storage_key = strval($record['storage_key'] ?? '');	
			$this->storage_sha1hash = strval($record['storage_sha1hash'] ?? '');	
			$this->storage_size = intval($record['storage_size'] ?? 0);	
			$this->worker_id = intval($record['worker_id'] ?? 0);	
		}
	}
	
	function getWorker() : ?Model_Worker {
		return DAO_Worker::get($this->worker_id);
	}
	
	public function getContent() : array {
		if(!($content = Storage_RecordChangeset::get($this->id)))
			return [];
		
		if(!($content = json_decode($content, true)))
			return [];
		
		return $content;
	}
}

class Storage_RecordChangeset extends Extension_DevblocksStorageSchema {
	const ID = 'cerberusweb.storage.schema.record.changeset';

	public static function getStorageTableName() : string {
		return 'record_changeset';
	}

	public static function getStorageNamespace() : string {
		return 'record_changeset';
	}

	/**
	 * @param Model_RecordChangeset|integer $object
	 * @param resource $fp
	 * @return mixed
	 */
	public static function get($object, &$fp=null) {
		if($object instanceof Model_RecordChangeset) {
			// Do nothing
			DevblocksPlatform::noop();
		} elseif(is_numeric($object)) {
			$object = DAO_RecordChangeset::get($object);
		} else {
			$object = null;
		}
		
		if(empty($object))
			return false;
		
		$key = $object->storage_key;
		$profile = !empty($object->storage_profile_id) ? $object->storage_profile_id : $object->storage_extension;
		
		if(false === ($storage = DevblocksPlatform::getStorageService($profile)))
			return false;
		
		return $storage->get('record_changeset', $key, $fp);
	}
	
	/**
	 * @param int $id
	 * @param string $contents
	 * @param Model_DevblocksStorageProfile|int $profile
	 * @return bool|void
	 */
	public static function put($id, $contents, $profile=null) {
		if(empty($profile)) {
			$profile = self::getActiveStorageProfile();
		}
		
		$profile_id = 0;
		
		if($profile instanceof Model_DevblocksStorageProfile) {
			$profile_id = $profile->id;
		} elseif(is_numeric($profile)) {
			$profile_id = intval($profile);
		}
		
		$storage = DevblocksPlatform::getStorageService($profile);
		
		if(is_string($contents)) {
			$storage_size = strlen($contents);
		} else if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
		} else {
			return false;
		}
		
		// Save to storage
		if(false === ($storage_key = $storage->put('record_changeset', $id, $contents)))
			return false;
		
		// Update storage key
		DAO_RecordChangeset::updateStorageMeta($id, $storage->manifest->id, $profile_id, $storage_key, $storage_size);
		
		return $storage_key;
	}
	
	protected static function getArchiveCandidates(string $src_extension, int $src_profile_id, int $before, int $last_at, int $last_id, int $limit) : array {
		$db = DevblocksPlatform::services()->database();

		return $db->GetArrayReader(sprintf(
			"SELECT id, created_at AS cursor_at FROM record_changeset ".
			"WHERE storage_extension = %s AND storage_profile_id = %d ".
			"AND created_at < %d AND (created_at > %d OR (created_at = %d AND id > %d)) ".
			"ORDER BY created_at ASC, id ASC LIMIT %d",
			$db->qstr($src_extension), $src_profile_id,
			$before, $last_at, $last_at, $last_id, $limit
		));
	}
};