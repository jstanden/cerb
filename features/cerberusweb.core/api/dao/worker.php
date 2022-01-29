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
|	http://cerb.ai		http://webgroup.media
***********************************************************************/

class DAO_Worker extends Cerb_ORMHelper {
	const AT_MENTION_NAME = 'at_mention_name';
	const CALENDAR_ID = 'calendar_id';
	const DOB = 'dob';
	const EMAIL_ID = 'email_id';
	const FIRST_NAME = 'first_name';
	const GENDER = 'gender';
	const ID = 'id';
	const IS_DISABLED = 'is_disabled';
	const IS_MFA_REQUIRED = 'is_mfa_required';
	const IS_PASSWORD_DISABLED = 'is_password_disabled';
	const IS_SUPERUSER = 'is_superuser';
	const LANGUAGE = 'language';
	const LAST_NAME = 'last_name';
	const LOCATION = 'location';
	const MOBILE = 'mobile';
	const PHONE = 'phone';
	const TIMEZONE = 'timezone';
	const TIME_FORMAT = 'time_format';
	const TIMEOUT_IDLE_SECS = 'timeout_idle_secs';
	const TITLE = 'title';
	const UPDATED = 'updated';
	
	const _EMAIL_IDS = '_email_ids';
	const _IMAGE = '_image';
	const _PASSWORD = '_password';
	
	const CACHE_ALL = 'ch_workers';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(64)
		$validation
			->addField(self::AT_MENTION_NAME, DevblocksPlatform::translateCapitalized('worker.at_mention_name'))
			->string()
			->setMaxLength(64)
			->setUnique(get_class())
			->setNotEmpty(false)
			->addValidator(function($string, &$error=null) {
				if(0 != strcasecmp($string, DevblocksPlatform::strAlphaNum($string, '-._'))) {
					$error = "may only contain letters, numbers, dashes, and dots";
					return false;
				}
				
				if(strlen($string) > 64) {
					$error = "must be shorter than 64 characters.";
					return false;
				}
				
				return true;
			})
			;
		// int(10) unsigned
		$validation
			->addField(self::CALENDAR_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CALENDAR, true))
			;
		// date
		$validation
			->addField(self::DOB)
			->string()
			->addValidator(function($value, &$error) {
				if($value && false == (strtotime($value . ' 00:00 GMT'))) {
					$error = sprintf("(%s) is not formatted properly (YYYY-MM-DD).",
						$value
					);
					return false;
				}
				
				return true;
			})
			;
		// int(10) unsigned
		$validation
			->addField(self::EMAIL_ID, DevblocksPlatform::translateCapitalized('common.email'))
			->id()
			->setRequired(true)
			->setUnique(get_class())
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS))
			->addValidator(function($value, &$error=null) {
				if(DAO_Address::isLocalAddressId($value)) {
					$error = "You can not assign an email address to a worker that is already assigned to a group or bucket.";
					return false;
				}
				
				return true;
			})
			;
		// varchar(128)
		$validation
			->addField(self::FIRST_NAME)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			;
		// char(1)
		$validation
			->addField(self::GENDER)
			->string()
			->setMaxLength(1)
			->setPossibleValues(['','F','M'])
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::IS_DISABLED)
			->bit()
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::IS_MFA_REQUIRED)
			->bit()
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::IS_PASSWORD_DISABLED)
			->bit()
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::IS_SUPERUSER)
			->bit()
			;
		// varchar(16)
		$validation
			->addField(self::LANGUAGE)
			->string()
			->setMaxLength(16)
			->setRequired(true)
			->addValidator($validation->validators()->language())
			;
		// varchar(128)
		$validation
			->addField(self::LAST_NAME)
			->string()
			->setMaxLength(128)
			;
		// varchar(255)
		$validation
			->addField(self::LOCATION)
			->string($validation::STRING_UTF8MB4)
			->setMaxLength(255)
			;
		// varchar(64)
		$validation
			->addField(self::MOBILE)
			->string()
			->setMaxLength(64)
			;
		// varchar(64)
		$validation
			->addField(self::PHONE)
			->string()
			->setMaxLength(64)
			;
		// varchar(255)
		$validation
			->addField(self::TIMEZONE)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			->addValidator($validation->validators()->timezone())
			;
		// varchar(64)
		$validation
			->addField(self::TIME_FORMAT)
			->string()
			->setMaxLength(64)
			;
		// int(10) unsigned
		$validation
			->addField(self::TIMEOUT_IDLE_SECS)
			->number()
			->setMin(60)
			->setMax(2678400)
			;
		// varchar(255)
		$validation
			->addField(self::TITLE)
			->string($validation::STRING_UTF8MB4)
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		// array
		$validation
			->addField(self::_EMAIL_IDS)
			->idArray()
			->addValidator($validation->validators()->contextIds(CerberusContexts::CONTEXT_ADDRESS, true))
			;
		// base64 blob png
		$validation
			->addField(self::_IMAGE)
			->image('image/png', 50, 50, 500, 500, 1000000)
			;
		// string
		$validation
			->addField(self::_PASSWORD)
			->string()
			->setMinLength(8)
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
		
		$sql = sprintf("INSERT INTO worker () ".
			"VALUES ()"
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_WORKER, $id);
		
		if(!array_key_exists(DAO_Worker::TIMEOUT_IDLE_SECS, $fields))
			$fields[DAO_Worker::TIMEOUT_IDLE_SECS] = 600;

		self::update($id, $fields);
		
		DAO_WorkerPref::setAsJson($id, 'search_favorites_json', [
			"cerberusweb.contexts.contact",
			"cerberusweb.contexts.address",
			"cerberusweb.contexts.org",
			"cerberusweb.contexts.task",
			"cerberusweb.contexts.ticket", 
		]);
		
		self::clearCache();
		
		return $id;
	}

	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
		$cache->removeByTags(['schema_mentions']);
		DAO_WorkerRole::clearWorkerCache();
	}
	
	/**
	 * @return Model_Worker[]
	 */
	static function getAllActive() {
		return self::getAll(false, false);
	}
	
	/**
	 * @return Model_Worker[]
	 */
	static function getAllWithDisabled() {
		return self::getAll(false, true);
	}
	
	/**
	 * @return Model_Worker[]
	 */
	static function getAllAdmins() {
		$workers = self::getAllActive();
		
		return array_filter($workers, function($worker) {
			return $worker->is_superuser;
		});
	}
	
	static function getOnlineAndMostIdle($max=1) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT devblocks_session.user_id, MAX(devblocks_session.updated+worker.timeout_idle_secs) as idle_after ".
			"FROM devblocks_session ".
			"INNER JOIN worker ON (worker.id=devblocks_session.user_id) ".
			"WHERE user_id > 0 ".
			"GROUP BY user_id ".
			"HAVING idle_after < unix_timestamp() ".
			"ORDER BY idle_after asc ".
			($max ? sprintf("LIMIT %d", $max) : '')
		;
		$results = $db->GetArrayMaster($sql);
		
		if(false == $results)
			return [];
		
		return array_column($results, 'idle_after', 'user_id');
	}
	
	static private function _getOnline() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT user_id ".
			"FROM devblocks_session ".
			"WHERE user_id > 0 ".
			"GROUP BY user_id "
		;
		$results = $db->GetArrayMaster($sql);
		
		if(false == $results)
			return [];
		
		return DAO_Worker::getIds(array_column($results, 'user_id'));
	}
	
	static public function getOnlineWithoutIdle() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT devblocks_session.user_id, MAX(devblocks_session.updated+worker.timeout_idle_secs) as idle_after ".
			"FROM devblocks_session ".
			"INNER JOIN worker ON (worker.id=devblocks_session.user_id) ".
			"WHERE user_id > 0 ".
			"GROUP BY user_id ".
			"HAVING idle_after > unix_timestamp() "
		;
		$results = $db->GetArrayMaster($sql);
		
		if(false == $results)
			return [];
		
		return DAO_Worker::getIds(array_column($results, 'user_id'));
	}
	
	/**
	 * @param int $idle_kick_limit
	 * @return Model_Worker[]
	 */
	static function getAllOnline($idle_kick_limit=0) {
		// Do we need to try and make room?
		if($idle_kick_limit) {
			$idle_workers = self::getOnlineAndMostIdle($idle_kick_limit);
			
			if($idle_workers) {
				DAO_DevblocksSession::deleteByUserIds(array_keys($idle_workers));
			}
			
			foreach($idle_workers as $idle_worker_id => $idle_worker_after) {
				$idle_worker = DAO_Worker::get($idle_worker_id);
				
				// Add the session kick to the worker's activity log
				$entry = array(
					//{{actor}} logged {{target}} out to free up a license seat.
					'message' => 'activities.worker.seat_expired',
					'variables' => array(
							'target' => $idle_worker->getName(),
							'idle_time' => time()-($idle_worker_after-$idle_worker->timeout_idle_secs),
						),
					'urls' => array(
							'target' => sprintf("ctx://cerberusweb.contexts.worker:%d/%s", $idle_worker->id, DevblocksPlatform::strToPermalink($idle_worker->getName())),
						)
				);
				CerberusContexts::logActivity('worker.seat_expired', CerberusContexts::CONTEXT_WORKER, $idle_worker->id, $entry, CerberusContexts::CONTEXT_APPLICATION, 0);
			}
		}
		
		return self::_getOnline();
	}
	
	/**
	 * 
	 * @param bool $nocache
	 * @param bool $with_disabled
	 * @return Model_Worker[]
	 */
	static function getAll($nocache=false, $with_disabled=true) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($workers = $cache->load(self::CACHE_ALL))) {
			$workers = self::getWhere(
				null,
				array(DAO_Worker::FIRST_NAME, DAO_Worker::LAST_NAME),
				array(true,true),
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($workers))
				return false;

			if(!empty($workers))
				$cache->save($workers, self::CACHE_ALL);
		}
		
		/*
		 * If the caller doesn't want disabled workers then remove them from the results,
		 * but don't bother caching two different versions (always cache all)
		 */
		if(!$with_disabled) {
			$workers = array_filter($workers, function($worker) {
				return !$worker->is_disabled;
			});
		}
		
		return $workers;
	}
	
	static function getWhere($where=null, $sortBy=array(DAO_Worker::FIRST_NAME, DAO_Worker::LAST_NAME), $sortAsc=array(true, true), $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, first_name, last_name, email_id, title, is_superuser, is_disabled, is_password_disabled, is_mfa_required, at_mention_name, timezone, time_format, timeout_idle_secs, language, calendar_id, gender, dob, location, phone, mobile, updated ".
			"FROM worker ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
			
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	static function getMentions() {
		$workers = DAO_Worker::getAllActive();
		$mentions = array_column(DevblocksPlatform::objectsToArrays($workers), 'at_mention_name', 'id');
		
		foreach($mentions as &$mention)
			$mention = DevblocksPlatform::strLower($mention);
		
		return array_flip($mentions);
	}
	
	public static function getByAtMention($at_mention) {
		$all_workers = DAO_Worker::getAllActive();
		
		$at_mentions = array_change_key_case(
			array_column($all_workers, 'id', 'at_mention_name'),
			CASE_LOWER
		);
		
		if(array_key_exists($at_mention, $at_mentions))
			return DAO_Worker::get($at_mentions[$at_mention]);
		
		return null;
	}
	
	static function getByAtMentions($at_mentions, $with_searches=true) {
		if(!is_array($at_mentions) && is_string($at_mentions))
			$at_mentions = [$at_mentions];
		
		$workers = [];
		$all_workers = DAO_Worker::getAllActive();
		$mentions_to_worker_id = DAO_Worker::getMentions();
		
		foreach($at_mentions as $at_mention) {
			$at_mention = DevblocksPlatform::strLower(ltrim($at_mention, '@'));
			
			// Check workers first
			if(isset($mentions_to_worker_id[$at_mention])) {
				$worker_id = $mentions_to_worker_id[$at_mention];
				
				if(isset($all_workers[$worker_id])) {
					$workers[$worker_id] = $all_workers[$worker_id];
					continue;
				}
			}
			
			// Then check saved searches
			if($with_searches && false != ($search = DAO_ContextSavedSearch::getByTag($at_mention))) {
				if(false == ($results = $search->getResults()))
					continue;
				
				$worker_ids = array_keys($results);
				
				if(!empty($worker_ids)) {
					$workers = $workers + array_intersect_key($all_workers, array_flip($worker_ids));
				}
			}
		}
		
		return $workers;
	}
	
	static function getResponsibilities($worker_id) {
		$db = DevblocksPlatform::services()->database();
		$responsibilities = [];
		
		$results = $db->GetArrayReader(sprintf("SELECT worker_id, bucket_id, responsibility_level FROM worker_to_bucket WHERE worker_id = %d",
			$worker_id
		));
		
		foreach($results as $row) {
			if(!isset($responsibilities[$row['bucket_id']]))
				$responsibilities[$row['bucket_id']] = [];
			
			$responsibilities[$row['bucket_id']] = $row['responsibility_level'];
		}
		
		return $responsibilities;
	}
	
	static function setResponsibility($worker_id, $bucket_id, $responsibility) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("REPLACE INTO worker_to_bucket (bucket_id, worker_id, responsibility_level) ".
			"VALUES (%d, %d, %d)",
			$bucket_id, $worker_id, $responsibility
		));
	}
	
	static function setResponsibilities($worker_id, $responsibilities) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$worker_id || false == (DAO_Worker::get($worker_id)))
			return false;
		
		if(!is_array($responsibilities))
			return false;
		
		$values = [];
		
		foreach($responsibilities as $bucket_id => $level) {
			$values[] = sprintf("(%d,%d,%d)", $bucket_id, $worker_id, $level);
		}
		
		// Wipe current bucket responsibilities
		$db->ExecuteMaster(sprintf("DELETE FROM worker_to_bucket WHERE worker_id = %d",
			$worker_id
		));
		
		if(!empty($values)) {
			$db->ExecuteMaster(sprintf("REPLACE INTO worker_to_bucket (bucket_id, worker_id, responsibility_level) VALUES %s",
				implode(',', $values)
			));
		}
		
		return true;
	}
	
	/**
	 *
	 * @param mysqli_result|false $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Worker();
			$object->at_mention_name = $row['at_mention_name'];
			$object->calendar_id = intval($row['calendar_id']);
			$object->dob = $row['dob'];
			$object->email_id = intval($row['email_id']);
			$object->first_name = trim($row['first_name']);
			$object->gender = $row['gender'];
			$object->id = intval($row['id']);
			$object->is_disabled = intval($row['is_disabled']);
			$object->is_mfa_required = intval($row['is_mfa_required']);
			$object->is_password_disabled = intval($row['is_password_disabled']);
			$object->is_superuser = intval($row['is_superuser']);
			$object->language = $row['language'];
			$object->last_name = trim($row['last_name']);
			$object->location = $row['location'];
			$object->mobile = $row['mobile'];
			$object->phone = $row['phone'];
			$object->time_format = $row['time_format'];
			$object->timezone = $row['timezone'];
			$object->timeout_idle_secs = intval($row['timeout_idle_secs']);
			$object->title = $row['title'];
			$object->updated = intval($row['updated']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * @return Model_Worker
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$workers = self::getAllWithDisabled();
		
		if(isset($workers[$id]))
			return $workers[$id];
			
		return null;
	}

	/**
	 * Retrieve a worker by email address
	 *
	 * @param integer $email
	 * @return Model_Worker
	 */
	static function getByEmailId($email_id) {
		if(empty($email_id))
			return null;
		
		$workers = DAO_Worker::getAll();
		
		if(is_array($workers))
		foreach($workers as $worker) {
			if($worker->email_id == $email_id)
				return $worker;
		}
		
		return null;
	}
	
	/**
	 * Retrieve a worker by email address
	 *
	 * @param string $email
	 * @return Model_Worker
	 */
	static function getByEmail($email) {
		if(empty($email))
			return null;
		
		if(false == ($model = DAO_Address::getByEmail($email)))
			return null;
		
		if(false != ($worker = $model->getWorker()))
			return $worker;
		
		return null;
	}
	
	/**
	 * @return array
	 */
	static function getNames($as_mentions=true) {
		$workers = DAO_Worker::getAll();
		$names = [];
		
		foreach($workers as $worker) {
			$names[$worker->id] = ($as_mentions && !empty($worker->at_mention_name)) ? $worker->at_mention_name : $worker->getName();
		}
		
		return $names;
	}
	
	/**
	 * 
	 * @param string $string
	 * @return Model_Worker[]
	 */
	static function getByString($string, $with_disabled=false) {
		if($with_disabled) {
			$workers = DAO_Worker::getAll();
		} else {
			$workers = DAO_Worker::getAllActive();
		}
		
		$patterns = DevblocksPlatform::parseCsvString($string);
		
		$results = [];
		
		if(is_array($patterns))
		foreach($patterns as $pattern) {
			foreach($workers as $worker_id => $worker) {
				$worker_name = $worker->getName();
			
				if(isset($results[$worker_id]))
					continue;

				// Check @mention
				if(0 == strcasecmp($worker->at_mention_name, $pattern)) {
					$results[$worker_id] = $worker;
					continue;
				}
				
				// Check full name
				if(false !== stristr($worker_name, $pattern)) {
					$results[$worker_id] = $worker;
					continue;
				}
			}
		}
		
		return $results;
	}
	
	static function getWorkloads() {
		$db = DevblocksPlatform::services()->database();
		$workloads = [];
		
		$sql = "SELECT 'cerberusweb.contexts.ticket' AS context, owner_id AS worker_id, COUNT(id) AS hits FROM ticket WHERE status_id = 0 GROUP BY owner_id ".
			"UNION ALL ".
			"SELECT 'cerberusweb.contexts.notification' AS context, worker_id, COUNT(id) AS hits FROM notification WHERE is_read = 0 GROUP BY worker_id "
			;
		$results = $db->GetArrayReader($sql);
		
		foreach($results as $result) {
			$context = $result['context'];
			$worker_id = $result['worker_id'];
			$hits = $result['hits'];
			
			if(!isset($workloads[$worker_id]))
				$workloads[$worker_id] = array(
					'total' => 0,
					'records' => [],
				);
				
			$workloads[$worker_id]['records'][$context] = $hits;
			$workloads[$worker_id]['total'] += $hits;
		}
		
		return $workloads;
	}
	
	static function updateWhere($fields, $where) {
		self::_updateWhere('worker', $fields, $where);
		self::clearCache();
	}
	
	static function update($ids, $fields, $option_bits=0, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];
		
		if(!isset($fields[self::UPDATED]) && !($option_bits & DevblocksORMHelper::OPT_UPDATE_NO_EVENTS))
			$fields[self::UPDATED] = time();
		
		$context = CerberusContexts::CONTEXT_WORKER;
		self::_updateAbstract($context, $ids, $fields);
		
		// Handle alternate email addresses
		if(isset($fields[self::_EMAIL_IDS])) {
			foreach($ids as $id) {
				if(is_array($fields[self::_EMAIL_IDS]))
					DAO_Address::updateForWorkerId($id, $fields[self::_EMAIL_IDS]);
			}
			unset($fields[self::_EMAIL_IDS]);
		}
		
		// Handle password updates
		if(isset($fields[self::_PASSWORD])) {
			foreach($ids as $id) {
				DAO_Worker::setAuth($id, $fields[self::_PASSWORD]);
			}
			unset($fields[self::_PASSWORD]);
		}
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if(!($option_bits & DevblocksORMHelper::OPT_UPDATE_NO_EVENTS) && $check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_WORKER, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'worker', $fields, 'id', $option_bits);
			
			// Send events
			if(!($option_bits & DevblocksORMHelper::OPT_UPDATE_NO_EVENTS) && $check_deltas) {
				// Local events
				self::processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.worker.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_WORKER, $batch_ids);
			}
		}
		
		// Flush cache
		if(0 == ($option_bits & DevblocksORMHelper::OPT_UPDATE_NO_FLUSH_CACHE)) {
			self::clearCache();
		}
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_WORKER;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		return true;
	}
	
	/**
	 * @abstract
	 * @param array $fields
	 * @param integer $id
	 */
	static public function onUpdateByActor($actor, $fields, $id) {
		// If we set 'email_id', link the address
		if(array_key_exists(DAO_Worker::EMAIL_ID, $fields) && $fields[DAO_Worker::EMAIL_ID]) {
			DAO_Address::update($fields[DAO_Worker::EMAIL_ID], [
				DAO_Address::MAIL_TRANSPORT_ID => 0,
				DAO_Address::WORKER_ID => $id,
			]);
		}
		
		// Rebuild all role rosters
		DAO_WorkerRole::updateRosters();
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		$custom_fields = [];

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'title':
					$change_fields[DAO_Worker::TITLE] = $v;
					break;
					
				case 'location':
					$change_fields[DAO_Worker::LOCATION] = $v;
					break;
					
				case 'gender':
					if(in_array($v, ['M', 'F', '']))
						$change_fields[DAO_Worker::GENDER] = $v;
					break;
					
				case 'language':
					$change_fields[DAO_Worker::LANGUAGE] = $v;
					break;
					
				case 'timezone':
					$change_fields[DAO_Worker::TIMEZONE] = $v;
					break;
					
				case 'is_disabled':
					$change_fields[DAO_Worker::IS_DISABLED] = intval($v);
					break;
					
				case 'is_mfa_required':
					$change_fields[DAO_Worker::IS_MFA_REQUIRED] = intval($v);
					break;
					
				case 'is_password_disabled':
					$change_fields[DAO_Worker::IS_PASSWORD_DISABLED] = intval($v);
					break;
					
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_WORKER, $ids);
		
		if(!empty($change_fields)) {
			DAO_Worker::update($ids, $change_fields, 0, false);
			DAO_Worker::processUpdateEvents($ids, $change_fields);
		}
		
		// Custom Fields
		if(!empty($custom_fields))
			C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_WORKER, $custom_fields, $ids);
		
		// Broadcast
		if(isset($do['broadcast']))
			C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_WORKER, $do['broadcast'], $ids);
		
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_WORKER, $ids);
		
		$update->markCompleted();
		return true;
	}
	
	static function processUpdateEvents($ids, $change_fields) {
		// We only care about these fields, so abort if they aren't referenced

		$observed_fields = array(
			DAO_Worker::EMAIL_ID,
			DAO_Worker::IS_DISABLED,
		);
		
		$used_fields = array_intersect($observed_fields, array_keys($change_fields));
		
		if(empty($used_fields))
			return;
		
		// Load records only if they're needed
		
		if(false == ($before_models = CerberusContexts::getCheckpoints(CerberusContexts::CONTEXT_WORKER, $ids)))
			return;
		
		foreach($before_models as $id => $before_model) {
			$before_model = (object) $before_model;
			
			/*
			 * Worker email address changed
			 */
			
			@$email_id = $change_fields[DAO_Worker::EMAIL_ID];
			
			if($email_id == $before_model->email_id)
				unset($change_fields[DAO_Worker::EMAIL_ID]);
			
			if(isset($change_fields[DAO_Worker::EMAIL_ID]) && $email_id) {
				DAO_Address::update($email_id, [
					DAO_Address::MAIL_TRANSPORT_ID => 0,
					DAO_Address::WORKER_ID => $id,
				]);
			}
			
			/*
			 * Worker deactivated
			 */
			
			@$is_disabled = $change_fields[DAO_Worker::IS_DISABLED];
			
			if($is_disabled == $before_model->is_disabled)
				unset($change_fields[DAO_Worker::IS_DISABLED]);
			
			if(isset($change_fields[DAO_Worker::IS_DISABLED]) && $is_disabled) {
				Cerb_DevblocksSessionHandler::destroyByWorkerIds($before_model->id);
			}
		}
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		$db->ExecuteMaster("DELETE FROM worker_pref WHERE worker_id NOT IN (SELECT id FROM worker)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_pref records.');
		
		$db->ExecuteMaster("DELETE FROM worker_view_model WHERE worker_id NOT IN (SELECT id FROM worker)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_view_model records.');
		
		$db->ExecuteMaster("DELETE FROM worker_to_group WHERE worker_id NOT IN (SELECT id FROM worker)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_to_group records.');
		
		$db->ExecuteMaster("DELETE FROM worker_to_role WHERE worker_id NOT IN (SELECT id FROM worker)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_to_role records.');
		
		// Search indexes
		if(isset($tables['fulltext_worker'])) {
			$db->ExecuteMaster("DELETE FROM fulltext_worker WHERE id NOT IN (SELECT id FROM worker)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_worker records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_WORKER,
					'context_table' => 'worker',
					'context_key' => 'id',
				)
			)
		);
	}
	
	static function countByGroupId($group_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(worker_id) FROM worker_to_group WHERE group_id = %d",
			$group_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function delete($id) {
		if(empty($id))
			return;
		
		/* This event fires before the delete takes place in the db,
		 * so we can denote what is actually changing against the db state
		 */
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'worker.delete',
				array(
					'worker_ids' => array($id),
				)
			)
		);
		
		$db = DevblocksPlatform::services()->database();
		
		// Clear their task assignments
		$sql = sprintf("UPDATE task SET owner_id = 0 WHERE owner_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		// Clear their ticket assignments
		$sql = sprintf("UPDATE ticket SET owner_id = 0 WHERE owner_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$sql = sprintf("DELETE FROM worker WHERE id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$sql = sprintf("DELETE FROM worker_auth_hash WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		// Clear worker addresses
		$sql = sprintf("UPDATE address SET worker_id = 0 WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$sql = sprintf("DELETE FROM webapi_credentials WHERE worker_id = %d", $id);
		$db->ExecuteMaster($sql);
		
		$sql = sprintf("DELETE FROM worker_to_group WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		$sql = sprintf("DELETE FROM worker_to_role WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		$sql = sprintf("DELETE FROM worker_to_bucket WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		// Sessions
		DAO_DevblocksSession::deleteByUserIds($id);
		
		// OAuth tokens
		DAO_OAuthToken::deleteByWorkerId($id);
		
		// Clear search records
		$search = Extension_DevblocksSearchSchema::get(Search_Worker::ID);
		$search->delete(array($id));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_WORKER,
					'context_ids' => array($id)
				)
			)
		);
		
		// Invalidate caches
		self::clearCache();
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(DAO_Group::CACHE_ROSTERS);
		DAO_WorkerRole::clearWorkerCache($id);
	}
	
	static function hasAuth($worker_id) {
		$db = DevblocksPlatform::services()->database();
		$worker_auth = $db->GetRowReader(sprintf("SELECT pass_hash, pass_salt, method FROM worker_auth_hash WHERE worker_id = %d", $worker_id));
		return (is_array($worker_auth) && isset($worker_auth['pass_hash']));
	}
	
	static function setAuth($worker_id, $password) {
		$db = DevblocksPlatform::services()->database();
		
		if(is_null($password)) {
			return $db->ExecuteMaster(sprintf("DELETE FROM worker_auth_hash WHERE worker_id = %d",
				$worker_id
			));
			
		} else {
			$password_hash = password_hash($password, PASSWORD_DEFAULT);
			
			return $db->ExecuteMaster(sprintf("REPLACE INTO worker_auth_hash (worker_id, pass_hash, pass_salt, method) ".
				"VALUES (%d, %s, %s, %d)",
				$worker_id,
				$db->qstr($password_hash),
				$db->qstr(''),
				1
			));
		}
	}
	
	static function login($email, $password) {
		$db = DevblocksPlatform::services()->database();

		if(null == ($worker = DAO_Worker::getByEmail($email)) || $worker->is_disabled)
			return null;
		
		if($worker->is_disabled)
			return null;
		
		if($worker->is_password_disabled)
			return null;
		
		$worker_auth = $db->GetRowReader(sprintf("SELECT pass_hash, pass_salt, method FROM worker_auth_hash WHERE worker_id = %d", $worker->id));
		
		if(!isset($worker_auth['pass_hash']))
			return null;
		
		if(empty($worker_auth['pass_hash']))
			return null;
		
		switch(@$worker_auth['method']) {
			// password_hash()
			case 1:
				if(password_verify($password, $worker_auth['pass_hash'])) {
					if(password_needs_rehash($worker_auth['pass_hash'], PASSWORD_DEFAULT)) {
						$db->ExecuteMaster(sprintf("UPDATE worker_auth_hash SET pass_hash = %s WHERE worker_id = %d",
							$db->qstr(password_hash($password, PASSWORD_DEFAULT)),
							$worker->id
						));
					}
					
					return $worker;
				}
				break;
				
			// Legacy hashing (Cerb < 9.4)
			default:
				if(!array_key_exists('pass_salt', $worker_auth) || !$worker_auth['pass_salt'])
					return null;
				
				$given_hash = sha1($worker_auth['pass_salt'] . md5($password));
				
				if($given_hash == $worker_auth['pass_hash']) {
					// Upgrade password to stronger hashing method
					$db->ExecuteMaster(sprintf("UPDATE worker_auth_hash SET pass_hash = %s, pass_salt = '', method = 1 WHERE worker_id = %d",
						$db->qstr(password_hash($password, PASSWORD_DEFAULT)),
						$worker->id
					));
					
					return $worker;
				}
				break;
		}
		
		return null;
	}
	
	/**
	 * @return Model_GroupMember[]
	 */
	static function getWorkerGroups($worker_id, $only_if_manager=false) {
		// Get the cache
		$rosters = DAO_Group::getRosters();

		$memberships = [];
		
		// Remove any groups our desired worker isn't in
		if(is_array($rosters))
		foreach($rosters as $group_id => $members) {
			if(isset($members[$worker_id])) {
				if(!$only_if_manager || $members[$worker_id]->is_manager)
					$memberships[$group_id] = $members[$worker_id];
			}
		}
		
		return $memberships;
	}
	
	public static function random() {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader("SELECT id FROM worker WHERE is_disabled=0 ORDER BY rand() LIMIT 1");
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Worker::getFields();
		
		list($tables, $wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Worker', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"w.id as %s, ".
			"w.first_name as %s, ".
			"w.last_name as %s, ".
			"w.title as %s, ".
			"w.email_id as %s, ".
			"w.is_superuser as %s, ".
			"w.is_mfa_required as %s, ".
			"w.is_password_disabled as %s, ".
			"w.at_mention_name as %s, ".
			"w.timezone as %s, ".
			"w.time_format as %s, ".
			"w.timeout_idle_secs as %s, ".
			"w.language as %s, ".
			"w.calendar_id as %s, ".
			"w.gender as %s, ".
			"w.dob as %s, ".
			"w.location as %s, ".
			"w.mobile as %s, ".
			"w.phone as %s, ".
			"w.updated as %s, ".
			"w.is_disabled as %s ",
				SearchFields_Worker::ID,
				SearchFields_Worker::FIRST_NAME,
				SearchFields_Worker::LAST_NAME,
				SearchFields_Worker::TITLE,
				SearchFields_Worker::EMAIL_ID,
				SearchFields_Worker::IS_SUPERUSER,
				SearchFields_Worker::IS_MFA_REQUIRED,
				SearchFields_Worker::IS_PASSWORD_DISABLED,
				SearchFields_Worker::AT_MENTION_NAME,
				SearchFields_Worker::TIMEZONE,
				SearchFields_Worker::TIME_FORMAT,
				SearchFields_Worker::TIMEOUT_IDLE_SECS,
				SearchFields_Worker::LANGUAGE,
				SearchFields_Worker::CALENDAR_ID,
				SearchFields_Worker::GENDER,
				SearchFields_Worker::DOB,
				SearchFields_Worker::LOCATION,
				SearchFields_Worker::MOBILE,
				SearchFields_Worker::PHONE,
				SearchFields_Worker::UPDATED,
				SearchFields_Worker::IS_DISABLED
			);
			
		$join_sql = "FROM worker w ".

		// Dynamic joins
		(isset($tables['address']) ? "INNER JOIN address ON (w.email_id = address.id) " : " ")
		;
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Worker');
		
		$result = array(
			'primary_table' => 'w',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function autocomplete($term, $as='models', $query=null) {
		$db = DevblocksPlatform::services()->database();
		$workers = DAO_Worker::getAll();
		$objects = [];
		
		$context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_WORKER);
		
		$view = $context_ext->getSearchView('autocomplete_worker');
		$view->is_ephemeral = true;
		$view->renderPage = 0;
		$view->addParamsWithQuickSearch($query, true);
		$view->addParam(new DevblocksSearchCriteria(SearchFields_Worker::IS_DISABLED, DevblocksSearchCriteria::OPER_EQ, 0));
		
		$query_parts = DAO_Worker::getSearchQueryComponents([], $view->getParams(), $view->renderSortBy, $view->renderSortAsc);
		
		$sql = "SELECT w.id ".
			$query_parts['join'] .
			$query_parts['where'] .
			sprintf('AND (first_name LIKE %s OR last_name LIKE %s %s) ',
				$db->qstr($term.'%'),
				$db->qstr($term.'%'),
				(false != strpos($term,' ')
					? sprintf("OR concat(first_name,' ',last_name) LIKE %s ", $db->qstr($term.'%'))
					: '')
			).
			'ORDER BY w.first_name ASC '.
			'LIMIT 25 '
			;
		
		$results = $db->GetArrayReader($sql);
		
		if(is_array($results))
		foreach($results as $row) {
			$worker_id = $row['id'];
			
			if(!isset($workers[$worker_id]))
				continue;
				
			$objects[$worker_id] = $workers[$worker_id];
		}
		
		switch($as) {
			case 'ids':
				return array_keys($objects);
				break;
				
			default:
				return DAO_Worker::getIds(array_keys($objects));
				break;
		}
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
			SearchFields_Worker::ID,
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

/**
 * ...
 *
 */
class SearchFields_Worker extends DevblocksSearchFields {
	// Worker
	const ID = 'w_id';
	const AT_MENTION_NAME = 'w_at_mention_name';
	const CALENDAR_ID = 'w_calendar_id';
	const DOB = 'w_dob';
	const EMAIL_ID = 'w_email_id';
	const FIRST_NAME = 'w_first_name';
	const GENDER = 'w_gender';
	const IS_DISABLED = 'w_is_disabled';
	const IS_MFA_REQUIRED = 'w_is_mfa_required';
	const IS_PASSWORD_DISABLED = 'w_is_password_disabled';
	const IS_SUPERUSER = 'w_is_superuser';
	const LANGUAGE = 'w_language';
	const LAST_NAME = 'w_last_name';
	const LOCATION = 'w_location';
	const MOBILE = 'w_mobile';
	const PHONE = 'w_phone';
	const TIMEZONE = 'w_timezone';
	const TIME_FORMAT = 'w_time_format';
	const TIMEOUT_IDLE_SECS = 'w_timeout_idle_secs';
	const TITLE = 'w_title';
	const UPDATED = 'w_updated';
	
	const EMAIL_ADDRESS = 'a_address_email';
	
	const FULLTEXT_WORKER = 'ft_worker';
	
	const VIRTUAL_ALIAS = '*_alias';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_EMAIL_SEARCH = '*_email_search';
	const VIRTUAL_GROUPS = '*_groups';
	const VIRTUAL_GROUP_SEARCH = '*_group_search';
	const VIRTUAL_GROUP_MANAGER_SEARCH = '*_group_manager_search';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_CALENDAR_AVAILABILITY = '*_calendar_availability';
	const VIRTUAL_SESSION_ACTIVITY = '*_session_activity';
	const VIRTUAL_ROLE_SEARCH = '*_role_search';
	const VIRTUAL_ROLE_EDITOR_SEARCH = '*_role_editor_search';
	const VIRTUAL_ROLE_READER_SEARCH = '*_role_reader_search';
	const VIRTUAL_USING_WORKSPACE_PAGE = '*_using_workspace_page';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'w.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_WORKER => new DevblocksSearchFieldContextKeys('w.id', self::ID),
			CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('w.email_id', self::EMAIL_ID),
			CerberusContexts::CONTEXT_CALENDAR => new DevblocksSearchFieldContextKeys('w.calendar_id', self::CALENDAR_ID),
		);
	}
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_WORKER:
				return self::_getWhereSQLFromFulltextField($param, Search_Worker::ID, self::getPrimaryKey());
				
			case self::VIRTUAL_ALIAS:
				return self::_getWhereSQLFromAliasesField($param, CerberusContexts::CONTEXT_WORKER, self::getPrimaryKey());
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_WORKER, self::getPrimaryKey());
				
			case self::VIRTUAL_EMAIL_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'w.email_id');
				
			case self::VIRTUAL_GROUPS:
				@$ids = $param->value;

				$ids = DevblocksPlatform::sanitizeArray($ids, 'int', ['nonzero']);
				
				if(!is_array($ids) || empty($ids))
					return '0';
				
				return sprintf("w.id IN (SELECT worker_id FROM worker_to_group WHERE group_id IN (%s))", implode(',', $ids));
				
			case self::VIRTUAL_GROUP_SEARCH:
				$sql = "SELECT worker_id FROM worker_to_group WHERE group_id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_GROUP, $sql, 'w.id');
				
			case self::VIRTUAL_GROUP_MANAGER_SEARCH:
				$sql = "SELECT worker_id FROM worker_to_group WHERE is_manager = 1 AND group_id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_GROUP, $sql, 'w.id');
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_WORKER), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_CALENDAR_AVAILABILITY:
				if(!is_array($param->value) || count($param->value) != 3)
					break;
					
				$from = $param->value[0];
				$to = $param->value[1];
				$is_available = !empty($param->value[2]);
				
				// [TODO] Load all worker availability calendars
				
				$workers = DAO_Worker::getAllActive();
				$results = [];
				
				foreach($workers as $worker_id => $worker) {
					@$calendar_id = $worker->calendar_id;
					
					if(empty($calendar_id)) {
						if(!$is_available)
							$results[] = $worker_id;
						continue;
					}
					
					if(false == ($calendar = DAO_Calendar::get($calendar_id))) {
						if(!$is_available)
							$results[] = $worker_id;
						continue;
					}
					
					@$cal_from = strtotime("today", strtotime($from));
					@$cal_to = strtotime("tomorrow", strtotime($to));
					
					// [TODO] Cache!!
					$calendar_events = $calendar->getEvents($cal_from, $cal_to);
					$availability = $calendar->computeAvailability($cal_from, $cal_to, $calendar_events);
					
					$pass = $availability->isAvailableBetween(strtotime($from), strtotime($to));
					
					if($pass == $is_available) {
						$results[] = $worker_id;
						continue;
					}
				}
				
				if(empty($results))
					$results[] = '-1';
				
				return sprintf("w.id IN (%s) ", implode(', ', $results));
			
			case self::VIRTUAL_SESSION_ACTIVITY:
				@$from_ts = strtotime($param->value[0]);
				@$to_ts = strtotime($param->value[1]);
				
				return sprintf('w.id IN (SELECT DISTINCT user_id FROM devblocks_session WHERE refreshed_at BETWEEN %d AND %d)', $from_ts, $to_ts);
			
			case self::VIRTUAL_ROLE_SEARCH:
				$sql = "SELECT worker_id FROM worker_to_role WHERE is_member = 1 AND role_id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_ROLE, $sql, 'w.id');
				
			case self::VIRTUAL_ROLE_EDITOR_SEARCH:
				$sql = "SELECT worker_id FROM worker_to_role WHERE is_editable = 1 AND role_id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_ROLE, $sql, 'w.id');
				
			case self::VIRTUAL_ROLE_READER_SEARCH:
				$sql = "SELECT worker_id FROM worker_to_role WHERE is_readable = 1 AND role_id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_ROLE, $sql, 'w.id');
				
			case self::VIRTUAL_USING_WORKSPACE_PAGE:
				$db = DevblocksPlatform::services()->database();
				$workspace_page_sql = self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_WORKSPACE_PAGE, '%s');
				
				if(false == ($rows = $db->GetArrayReader($workspace_page_sql)))
					return '0';
				
				if(false == ($worker_ids = DAO_WorkspacePage::getUsers(array_column($rows, 'id'))))
					return '0';
				
				return sprintf('w.id IN (%s)', implode(',', $worker_ids));
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
		
		return false;
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'group':
				$key_select = 'wtg_' . uniqid();
				
				return [
					'key_query' => $key,
					'key_select' => $key_select,
					'label' => DevblocksPlatform::translateCapitalized('common.group'),
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'sql_select' => sprintf("`%s`.group_id",
						Cerb_ORMHelper::escape($key_select)
					),
					'sql_join' => sprintf("INNER JOIN worker_to_group AS `%s` ON (`%s`.worker_id = %s)",
						Cerb_ORMHelper::escape($key_select),
						Cerb_ORMHelper::escape($key_select),
						$primary_key
					),
					'get_value_as_filter_callback' => function($value, &$filter) {
						$filter = 'group:(id:%s)';
						return $value;
					}
				];
			
			case 'lang':
				$key = 'language';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		if(DevblocksPlatform::strStartsWith($key, 'wtg_')) {
			$models = DAO_Group::getIds($values);
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_GROUP);
			return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
		}
		
		switch($key) {
			case SearchFields_Worker::GENDER:
				$label_map = [
					'' => DevblocksPlatform::translateCapitalized('common.unknown'),
					'F' => DevblocksPlatform::translateCapitalized('common.gender.female'),
					'M' => DevblocksPlatform::translateCapitalized('common.gender.male'),
				];
				return $label_map;
				break;
				
			case SearchFields_Worker::ID:
				$models = DAO_Worker::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				break;
				
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				return parent::_getLabelsForKeyBooleanValues();
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
			
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static private function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'w', 'id', $translate->_('common.id'), null, true),
			self::AT_MENTION_NAME => new DevblocksSearchField(self::AT_MENTION_NAME, 'w', 'at_mention_name', $translate->_('worker.at_mention_name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CALENDAR_ID => new DevblocksSearchField(self::CALENDAR_ID, 'w', 'calendar_id', $translate->_('common.calendar'), null, true),
			self::DOB => new DevblocksSearchField(self::DOB, 'w', 'dob', $translate->_('common.dob.abbr'), Model_CustomField::TYPE_DATE, true),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'w', 'email_id', ucwords($translate->_('common.email')), null, true),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'w', 'first_name', $translate->_('common.name.first'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::GENDER => new DevblocksSearchField(self::GENDER, 'w', 'gender', $translate->_('common.gender'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'w', 'is_disabled', ucwords($translate->_('common.disabled')), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_MFA_REQUIRED => new DevblocksSearchField(self::IS_MFA_REQUIRED, 'w', 'is_mfa_required', ucwords($translate->_('worker.is_mfa_required')), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_PASSWORD_DISABLED => new DevblocksSearchField(self::IS_PASSWORD_DISABLED, 'w', 'is_password_disabled', ucwords($translate->_('worker.is_password_disabled')), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_SUPERUSER => new DevblocksSearchField(self::IS_SUPERUSER, 'w', 'is_superuser', $translate->_('worker.is_superuser'), Model_CustomField::TYPE_CHECKBOX, true),
			self::LANGUAGE => new DevblocksSearchField(self::LANGUAGE, 'w', 'language', $translate->_('common.language'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'w', 'last_name', $translate->_('common.name.last'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LOCATION => new DevblocksSearchField(self::LOCATION, 'w', 'location', $translate->_('common.location'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MOBILE => new DevblocksSearchField(self::MOBILE, 'w', 'mobile', $translate->_('common.mobile'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'w', 'phone', $translate->_('common.phone'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TIME_FORMAT => new DevblocksSearchField(self::TIME_FORMAT, 'w', 'time_format', $translate->_('worker.time_format'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TIMEZONE => new DevblocksSearchField(self::TIMEZONE, 'w', 'timezone', $translate->_('common.timezone'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TIMEOUT_IDLE_SECS => new DevblocksSearchField(self::TIMEOUT_IDLE_SECS, 'w', 'timeout_idle_secs', $translate->_('worker.timeout_idle_secs'), Model_CustomField::TYPE_NUMBER, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'w', 'title', $translate->_('worker.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'w', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'address', 'email', ucwords($translate->_('common.email_address')), Model_CustomField::TYPE_SINGLE_LINE, false),
			
			self::FULLTEXT_WORKER => new DevblocksSearchField(self::FULLTEXT_WORKER, 'ft', 'content', $translate->_('common.content'), 'FT'),
			
			self::VIRTUAL_ALIAS => new DevblocksSearchField(self::VIRTUAL_ALIAS, '*', 'alias', $translate->_('common.aliases'), null, false),
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_EMAIL_SEARCH => new DevblocksSearchField(self::VIRTUAL_EMAIL_SEARCH, '*', 'email_search', null, null),
			self::VIRTUAL_GROUP_SEARCH => new DevblocksSearchField(self::VIRTUAL_GROUP_SEARCH, '*', 'group_search', DevblocksPlatform::translateCapitalized('common.groups'), null, false),
			self::VIRTUAL_GROUP_MANAGER_SEARCH => new DevblocksSearchField(self::VIRTUAL_GROUP_MANAGER_SEARCH, '*', 'group_manager_search', null, null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_CALENDAR_AVAILABILITY => new DevblocksSearchField(self::VIRTUAL_CALENDAR_AVAILABILITY, '*', 'calendar_availability', 'Calendar Availability', null),
			self::VIRTUAL_SESSION_ACTIVITY => new DevblocksSearchField(self::VIRTUAL_SESSION_ACTIVITY, '*', 'session_activity', 'Last Activity', null),
			self::VIRTUAL_ROLE_SEARCH => new DevblocksSearchField(self::VIRTUAL_ROLE_SEARCH, '*', 'role_search', null, null),
			self::VIRTUAL_ROLE_EDITOR_SEARCH => new DevblocksSearchField(self::VIRTUAL_ROLE_SEARCH, '*', 'role_editor_search', null, null),
			self::VIRTUAL_ROLE_READER_SEARCH => new DevblocksSearchField(self::VIRTUAL_ROLE_READER_SEARCH, '*', 'role_reader_search', null, null),
			self::VIRTUAL_USING_WORKSPACE_PAGE => new DevblocksSearchField(self::VIRTUAL_USING_WORKSPACE_PAGE, '*', 'using_workspace_page', null, null),
		);

		// Fulltext indexes
		
		$columns[self::FULLTEXT_WORKER]->ft_schema = Search_Worker::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_Worker extends Extension_DevblocksSearchSchema {
	const ID = 'cerb.search.schema.worker';
	
	public function getNamespace() {
		return 'worker';
	}
	
	public function getAttributes() {
		return [];
	}
	
	public function getIdField() {
		return 'id';
	}
	
	public function getDataField() {
		return 'content';
	}
	
	public function getPrimaryKey() {
		return 'id';
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
	
	private function _indexDictionary($dict, $engine) {
		$logger = DevblocksPlatform::services()->log();

		$id = $dict->id;
		
		if(empty($id))
			return false;
		
		$doc = array(
			'content' => implode("\n", array(
				$dict->_label,
				$dict->address_email,
				$dict->title,
				$dict->at_mention_name,
			)),
		);

		$logger->info(sprintf("[Search] Indexing %s %d...",
			$this->getNamespace(),
			$id
		));
		
		if(false === ($engine->index($this, $id, $doc)))
			return false;
		
		return true;
	}
	
	public function indexIds(array $ids=[]) {
		if(empty($ids))
			return;
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		if(false == ($models = DAO_Worker::getIds($ids)))
			return;
		
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER, array('address_'));
		
		if(empty($dicts))
			return;
		
		foreach($dicts as $dict) {
			$this->_indexDictionary($dict, $engine);
		}
	}
	
	public function index($stop_time=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%1$s = %2$d AND %3$s > %4$d) OR (%1$s > %2$d)',
				DAO_Worker::UPDATED,
				$ptr_time,
				DAO_Worker::ID,
				$id
			);
			$models = DAO_Worker::getWhere($where, array(DAO_Worker::UPDATED, DAO_Worker::ID), array(true, true), 100);
			
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER, array('address_'));
			
			if(empty($dicts)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			// Loop dictionaries
			foreach($dicts as $dict) {
				$id = $dict->id;
				$ptr_time = $dict->updated;
				
				$ptr_id = ($last_time == $ptr_time) ? $id : 0;
				
				if(false == $this->_indexDictionary($dict, $engine))
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

class Model_Worker {
	public $at_mention_name;
	public $calendar_id = 0;
	public $dob;
	public $email_id = 0;
	public $first_name;
	public $gender;
	public $id;
	public $is_disabled = 0;
	public $is_mfa_required = 0;
	public $is_password_disabled = 0;
	public $is_superuser = 0;
	public $language;
	public $last_name;
	public $location;
	public $mobile;
	public $phone;
	public $time_format;
	public $timezone;
	public $timeout_idle_secs;
	public $title;
	public $updated;
	
	private $_email_model = null;
	
	function __get($name) {
		switch($name) {
			// [DEPRECATED] Added in 7.1
			case 'email':
				//error_log("The 'email' field on worker records is deprecated. Use \$worker->getEmailString() instead.", E_USER_DEPRECATED);
				
				return $this->getEmailString();
				break;
		}
	}
	
	/**
	 * @return Model_GroupMember[]
	 */
	function getMemberships() {
		return DAO_Worker::getWorkerGroups($this->id);
	}
	
	function getManagerships() {
		return DAO_Worker::getWorkerGroups($this->id, true);
	}

	function getRoles() {
		return DAO_WorkerRole::getByMember($this->id);
	}
	
	/**
	 * 
	 * @return Model_Address
	 */
	function getEmailModel() {
		if(is_null($this->_email_model))
			$this->_email_model = DAO_Address::get($this->email_id);
		
		return $this->_email_model;
	}
	
	function getEmailModels() {
		if(!$this->id)
			return [];
		
		return DAO_Address::getByWorkerId($this->id);
	}
	
	/**
	 * 
	 * @return NULL|string
	 */
	function getEmailString() {
		if(false == ($model = $this->getEmailModel()))
			return null;
		
		return $model->email;
	}
	
	function getInitials() {
		return mb_convert_case(DevblocksPlatform::strToInitials($this->getName()), MB_CASE_UPPER);
	}
	
	function getImageUrl() {
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->write(sprintf('c=avatars&type=worker&id=%d', $this->id)) . '?v=' . $this->updated;
	}
	
	function getLatestActivity() {
		return DAO_ContextActivityLog::getLatestEntriesByActor(CerberusContexts::CONTEXT_WORKER, $this->id, 1);
	}
	
	function getLatestSession() {
		return DAO_DevblocksSession::getLatestByUserId($this->id);
	}
	
	function getGenderAsString() {
		$genders = [
			'' => '',
			'M' => DevblocksPlatform::translateCapitalized('common.gender.male'),
			'F' => DevblocksPlatform::translateCapitalized('common.gender.female'),
		];
		
		return @$genders[$this->gender];
	}
	
	function getPagesMenu() {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("worker:%d:pages_menu", $this->id);
		
		if(null === ($pages_menu = $cache->load($cache_key))) {
			$menu = DAO_WorkerPref::getAsJson($this->id, 'menu_json','[]');
			$menu_pages = DAO_WorkspacePage::getIds($menu);
			
			$pages_menu = [];
			
			foreach($menu_pages as $menu_page) {
				$pages_menu[$menu_page->id] = [
					'id' => $menu_page->id,
					'name' => $menu_page->name,
					'tabs' => $menu_page->getTabs($this),
				];
			}
			
			$cache->save($pages_menu, $cache_key, ['schema_workspaces'], 86400);
		}
		
		return $pages_menu;
	}
	
	function clearPagesMenuCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("worker:%d:pages_menu", $this->id);
		$cache->remove($cache_key);
	}
	
	/**
	 * 
	 * @return array
	 */
	function getResponsibilities() {
		return DAO_Worker::getResponsibilities($this->id);
	}
	
	public function setResponsibilities($responsibilities) {
		return DAO_Worker::setResponsibilities($this->id, $responsibilities);
	}
	
	function getPlaceholderLabelsValues(&$labels, &$values, $label_prefix='Current worker ', $values_prefix='current_worker_') {
		$labels = [];
		$values = [];
		
		$placeholder_labels = [];
		$worker_labels = $worker_values = [];
			
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $this, $worker_labels, $worker_values, $label_prefix, true, false);
		CerberusContexts::merge($values_prefix, null, $worker_labels, $worker_values, $labels, $values);
		
		@$types = $values['_types'];
		
		foreach(array_keys($labels) as $k) {
			@$label = $labels[$k];
			@$type = $types[$k];
			$placeholder_labels[$k] = array('label' => $label, 'type' => $type);
		}
		
		$labels = $placeholder_labels;
	}
	
	function getAvailability($date_from, $date_to) {
		// In full (00:00:00 - 23:59:59) days
		$day_from = strtotime('midnight', $date_from);
		$day_to = strtotime('23:59:59', $date_to);
		
		if(false == ($calendar = DAO_Calendar::get($this->calendar_id))) {
			$calendar = new Model_Calendar();
			$calendar_events = [];
			
		} else {
			$calendar_events = $calendar->getEvents($day_from, $day_to);
		}
		
		return $calendar->computeAvailability($date_from, $date_to, $calendar_events);
	}
	
	function getAvailabilityAsBlocks() {
		$date_from = time() - (time() % 60);
		$date_to = strtotime('+24 hours', $date_from);
		
		$blocks = [];
		
		$availability = $this->getAvailability($date_from, $date_to);
		$mins = $availability->getMinutes();
		$ticks = strlen($mins);

		while(0 != strlen($mins)) {
			$from = 0;
			$is_available = $mins[$from] == 1;
			
			if(false === ($to = strpos($mins, $is_available ? '0' : '1'))) {
				$to = strlen($mins);
				$mins = '';
				
			} else {
				$mins = substr($mins, $to);
			}
			
			$pos = $ticks - strlen($mins);
			
			$blocks[] = array(
				'available' => $is_available,
				'length' => $to,
				'start' => $date_from + (($pos - $to) * 60),
				'end' => $date_from + ($pos * 60 - 1),
			);
		}
		
		return array(
			'start' => $date_from,
			'end' => $date_to,
			'ticks' => $ticks,
			'blocks' => $blocks,
		);
	}
	
	function hasPriv($priv_id) {
		// We don't need to do much work if we're a superuser
		if($this->is_superuser)
			return true;

		// Check the aggregated worker privs from roles
		$privs = DAO_WorkerRole::getCumulativePrivsByWorker($this->id);
		
		// If they have the 'everything' privilege, or no roles, permit non-config ACL
		if(isset($privs['*']))
			return !DevblocksPlatform::strStartsWith($priv_id, 'core.config');
		
		if(!empty($priv_id) && isset($privs[$priv_id]))
			return true;
		
		return false;
	}
	
	function isGroupManager($group_id=null) {
		@$memberships = $this->getMemberships();
		$groups = DAO_Group::getAll();
		
		if($this->is_superuser)
			return true;
		
		if(empty($group_id) && is_array($groups)) {
			foreach(array_keys($groups) as $group_id) {
				// Is the worker a manager of this group?
				if(isset($memberships[$group_id]) && $memberships[$group_id]->is_manager)
					return true;
			}
			
			return false;
		}
		
		if(
			!isset($groups[$group_id]) // doesn't exist
			|| !isset($memberships[$group_id])  // not a member
			|| (!$memberships[$group_id]->is_manager && !$this->is_superuser) // not a manager or superuser
		){
			return false;
		}
		return true;
	}

	function isGroupMember($group_id) {
		@$memberships = $this->getMemberships();
		$groups = DAO_Group::getAll();
		if(
			empty($group_id) // null
			|| !isset($groups[$group_id]) // not a group
			|| !isset($memberships[$group_id]) // not a member
		) {
			return false;
		}
		return true;
	}
	
	function isRoleMember($role_id) {
		$roles = DAO_WorkerRole::getByMember($this->id);
		
		if(array_key_exists($role_id, $roles))
			return true;
		
		return false;
	}
	
	function getName($reverse=false) {
		if(!$reverse) {
			$name = sprintf("%s%s%s",
				$this->first_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
				$this->last_name
			);
		} else {
			$name = sprintf("%s%s%s",
				$this->last_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? ", " : "",
				$this->first_name
			);
		}
		
		return $name;
	}
};

class WorkerPrefs {
	static function setDontNotifyOnActivities($worker_id, $array) {
		if(empty($worker_id) || !is_array($array))
			return;
		
		DAO_WorkerPref::set($worker_id, 'dont_notify_on_activities_json', json_encode($array));
	}
	
	static function getDontNotifyOnActivities($worker_id) {
		$dont_notify_on_activities = DAO_WorkerPref::get($worker_id, 'dont_notify_on_activities_json', null);
		if(empty($dont_notify_on_activities) || false == ($dont_notify_on_activities = @json_decode($dont_notify_on_activities, true))) {
			$dont_notify_on_activities = [];
		}
		return $dont_notify_on_activities;
	}
};

class View_Worker extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'workers';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Workers';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Worker::FIRST_NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Worker::TITLE,
			SearchFields_Worker::EMAIL_ADDRESS,
			SearchFields_Worker::IS_SUPERUSER,
			SearchFields_Worker::AT_MENTION_NAME,
			SearchFields_Worker::LANGUAGE,
			SearchFields_Worker::TIMEZONE,
			SearchFields_Worker::IS_MFA_REQUIRED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Worker::EMAIL_ID,
			SearchFields_Worker::VIRTUAL_ALIAS,
			SearchFields_Worker::VIRTUAL_CONTEXT_LINK,
			SearchFields_Worker::VIRTUAL_EMAIL_SEARCH,
			SearchFields_Worker::VIRTUAL_HAS_FIELDSET,
			SearchFields_Worker::VIRTUAL_GROUP_SEARCH,
			SearchFields_Worker::VIRTUAL_GROUP_MANAGER_SEARCH,
			SearchFields_Worker::VIRTUAL_ROLE_SEARCH,
			SearchFields_Worker::VIRTUAL_ROLE_EDITOR_SEARCH,
			SearchFields_Worker::VIRTUAL_ROLE_READER_SEARCH,
			SearchFields_Worker::VIRTUAL_USING_WORKSPACE_PAGE,
			SearchFields_Worker::VIRTUAL_SESSION_ACTIVITY,
			SearchFields_Worker::FULLTEXT_WORKER,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Worker::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Worker');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Worker', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Worker', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Worker::AT_MENTION_NAME:
				case SearchFields_Worker::FIRST_NAME:
				case SearchFields_Worker::GENDER:
				case SearchFields_Worker::IS_DISABLED:
				case SearchFields_Worker::IS_MFA_REQUIRED:
				case SearchFields_Worker::IS_PASSWORD_DISABLED:
				case SearchFields_Worker::IS_SUPERUSER:
				case SearchFields_Worker::LANGUAGE:
				case SearchFields_Worker::LAST_NAME:
				case SearchFields_Worker::TIMEZONE:
				case SearchFields_Worker::TITLE:
					$pass = true;
					break;
					
				case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Worker::VIRTUAL_GROUP_SEARCH:
				case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_WORKER;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Worker::AT_MENTION_NAME:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LANGUAGE:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TIMEZONE:
			case SearchFields_Worker::TITLE:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Worker::GENDER:
				$label_map = SearchFields_Worker::getLabelsForKeyValues($column, []);
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;

			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_MFA_REQUIRED:
			case SearchFields_Worker::IS_PASSWORD_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
			
			case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUP_SEARCH:
				$label_map = function($ids) {
					$rows = DAO_Group::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'name', 'id');
				};
				
				$counts = $this->_getSubtotalCountForGroup($column, $label_map);
				break;
				
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
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
	
	private function _getSubtotalCountForGroup($field_key, $label_map=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(!isset($columns[$field_key]))
			$columns[] = $field_key;
		
		$query_parts = DAO_Worker::getSearchQueryComponents(
			$columns,
			$params,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		
		$sql = sprintf("SELECT wtg.group_id AS label, count(*) AS hits ".
			"%s ". // from
			"INNER JOIN worker_to_group AS wtg ON (wtg.worker_id=w.id) ".
			"%s ". // where
			"GROUP BY label ".
			"ORDER BY hits DESC ".
			"LIMIT 25",
			$query_parts['join'],
			$query_parts['where']
		);
		
		$counts = [];
		
		if(false == ($results = $db->GetArrayReader($sql)))
			return $counts;
		
		if(is_callable($label_map)) {
			$label_map = $label_map(array_column($results, 'label'));
		}
		
		if(is_array($results))
		foreach($results as $result) {
			$group_id = $result['label'];
			$label = $result['label'];
			$key = $label;
			$hits = $result['hits'];

			if(is_array($label_map) && isset($label_map[$label]))
				$label = $label_map[$label];
			
			if(!isset($counts[$key]))
				$counts[$key] = [
					'hits' => $hits,
					'label' => $label,
					'filter' => [
						'field' => SearchFields_Worker::VIRTUAL_GROUP_SEARCH,
						'query' => sprintf('group:(id:[%d])', $group_id),
					],
					'children' => [],
				];
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Worker::getFields();
		
		$date = DevblocksPlatform::services()->date();
		$timezones = $date->getTimezones();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Worker::FULLTEXT_WORKER),
				),
			'alias' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_ALIAS),
				),
			'email.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Worker::EMAIL_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'email' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 1500,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_EMAIL_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_WORKER],
					]
				),
			'firstName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'score' => 1501,
					'options' => array('param_key' => SearchFields_Worker::FIRST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:workers by:firstName~25 query:(firstName:{{term}}*) format:dictionaries',
						'key' => 'firstName',
						'limit' => 25,
					]
				),
			'gender' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::GENDER, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'examples' => [
						'male',
						'female',
						'unknown',
					],
				),
			'group' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_GROUP_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					]
				),
			'group.manager' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_GROUP_MANAGER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Worker::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'isAdmin' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Worker::IS_SUPERUSER),
				),
			'isAvailable' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY),
					'examples' => array(
						'"noon to 1pm"',
						'"now to +15 mins"',
					),
				),
			'isBusy' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY),
					'examples' => array(
						'"noon to 1pm"',
						'"now to +15 mins"',
					),
				),
			'isDisabled' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Worker::IS_DISABLED),
				),
			'isMfaRequired' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Worker::IS_MFA_REQUIRED),
				),
			'isPasswordDisabled' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Worker::IS_PASSWORD_DISABLED),
				),
			'language' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::LANGUAGE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'lastName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'score' => 1502,
					'options' => array('param_key' => SearchFields_Worker::LAST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:workers by:lastName~25 query:(lastName:{{term}}*) format:dictionaries',
						'key' => 'lastName',
						'limit' => 25,
					]
				),
			'location' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::LOCATION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:workers by:location~25 query:(location:{{term}}*) format:dictionaries',
						'key' => 'location',
						'limit' => 25,
					]
				),
			'mention' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'score' => 2000,
					'options' => array('param_key' => SearchFields_Worker::AT_MENTION_NAME),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:workers by:mention~25 query:(isDisabled:n mention:{{term}}*) format:dictionaries',
						'key' => 'mention',
						'limit' => 25,
					]
				),
			'mobile' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::MOBILE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'phone' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::PHONE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'role' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_ROLE_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ROLE, 'q' => ''],
					]
				),
			'role.editor' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_ROLE_EDITOR_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ROLE, 'q' => ''],
					]
				),
			'role.reader' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_ROLE_READER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ROLE, 'q' => ''],
					]
				),
			'lastActivity' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_SESSION_ACTIVITY),
				),
			'timezone' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::TIMEZONE),
					'examples' => array(
						['type' => 'list', 'values' => array_combine($timezones, $timezones), 'label_delimiter' => '/', 'key_delimiter' => '/'],
					)
				),
			'title' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:workers by:title~25 query:(title:{{term}}*) format:dictionaries',
						'key' => 'title',
						'limit' => 25,
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Worker::UPDATED),
				),
			'using.workspace' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_USING_WORKSPACE_PAGE),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKSPACE_PAGE, 'q' => ''],
					]
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Worker::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_WORKER, $fields, null);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_Worker::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['text']['examples'] = $ft_examples;
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'mentionName':
				$field = 'mention';
				break;
				
			case 'text':
				if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
					$oper = $value = null;
					CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $value);
					
					@$value = DevblocksPlatform::strLower($value);
					
					// [TODO] Implement 'nobody'
					if($value && in_array($value, ['me'])) {
						switch($value) {
							case 'me':
								return new DevblocksSearchCriteria(
									SearchFields_Worker::ID,
									DevblocksSearchCriteria::OPER_EQ,
									$active_worker->id
								);
								break;
						}
					}
				}
				
				return DevblocksSearchCriteria::getFulltextParamFromTokens(SearchFields_Worker::FULLTEXT_WORKER, $tokens);
				break;
				
			case 'alias':
				return DevblocksSearchCriteria::getContextAliasParamFromTokens(SearchFields_Worker::VIRTUAL_ALIAS, $tokens);
				break;
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
			case 'gender':
				$field_key = SearchFields_Worker::GENDER;
				$oper = null;
				$value = null;
				
				if(false == CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value))
					return false;
				
				foreach($value as &$v) {
					if(substr(DevblocksPlatform::strLower($v), 0, 1) == 'm') {
						$v = 'M';
					} else if(substr(DevblocksPlatform::strLower($v), 0, 1) == 'f') {
						$v = 'F';
					} else {
						$v = '';
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					$value
				);
				break;
				
			case 'email':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Worker::VIRTUAL_EMAIL_SEARCH);
				break;
				
			case 'group':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Worker::VIRTUAL_GROUP_SEARCH);
				break;
				
			case 'group.manager':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Worker::VIRTUAL_GROUP_MANAGER_SEARCH);
				break;
				
			case 'isAvailable':
				$param = DevblocksSearchCriteria::getDateParamFromTokens(SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY, $tokens);
				$param->value[] = '1';
				return $param;
				break;
				
			case 'isBusy':
				$param = DevblocksSearchCriteria::getDateParamFromTokens(SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY, $tokens);
				$param->value[] = '0';
				return $param;
				break;
				
			case 'lastActivity':
				return DevblocksSearchCriteria::getDateParamFromTokens(SearchFields_Worker::VIRTUAL_SESSION_ACTIVITY, $tokens);
				break;
				
			case 'role':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Worker::VIRTUAL_ROLE_SEARCH);
				break;
				
			case 'role.editor':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Worker::VIRTUAL_ROLE_EDITOR_SEARCH);
				break;
			
			case 'role.reader':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Worker::VIRTUAL_ROLE_READER_SEARCH);
				break;
			
			case 'using.workspace':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Worker::VIRTUAL_USING_WORKSPACE_PAGE);
				break;
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				break;
		}
		
		$search_fields = $this->getQuickSearchFields();
		return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Template
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::workers/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Worker::VIRTUAL_ALIAS:
				echo sprintf("%s %s <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.alias')),
					DevblocksPlatform::strEscapeHtml($param->operator),
					DevblocksPlatform::strEscapeHtml(json_encode($param->value))
				);
				break;
			
			case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_Worker::VIRTUAL_EMAIL_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.email')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUP_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.group')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUP_MANAGER_SEARCH:
				echo sprintf("%s of <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.manager')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_ROLE_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.role')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_ROLE_EDITOR_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml('Role editor'),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_ROLE_READER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml('Role reader'),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_USING_WORKSPACE_PAGE:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml('Using workspace'),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY:
				if(!is_array($param->value) || count($param->value) != 3)
					break;
				
				echo sprintf("Calendar matches <b>%s</b> between <b>%s</b> and <b>%s</b>",
					DevblocksPlatform::strEscapeHtml((!empty($param->value[2]) ? 'available' : 'busy')),
					DevblocksPlatform::strEscapeHtml($param->value[0]),
					DevblocksPlatform::strEscapeHtml($param->value[1])
				);
				break;
				
			case SearchFields_Worker::VIRTUAL_SESSION_ACTIVITY:
				if(!is_array($param->value) || count($param->value) != 2)
					break;
				
				echo sprintf("Last activity between <b>%s</b> and <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($param->value[0]),
					DevblocksPlatform::strEscapeHtml($param->value[1])
				);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Worker::GENDER:
				$label_map = SearchFields_Worker::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
			
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_MFA_REQUIRED:
			case SearchFields_Worker::IS_PASSWORD_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Worker::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Worker::AT_MENTION_NAME:
			case SearchFields_Worker::EMAIL_ADDRESS:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::GENDER:
			case SearchFields_Worker::LANGUAGE:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::LOCATION:
			case SearchFields_Worker::MOBILE:
			case SearchFields_Worker::PHONE:
			case SearchFields_Worker::TIME_FORMAT:
			case SearchFields_Worker::TIMEZONE:
			case SearchFields_Worker::TIMEOUT_IDLE_SECS:
			case SearchFields_Worker::TITLE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Worker::DOB:
			case SearchFields_Worker::UPDATED:
			case SearchFields_Worker::VIRTUAL_SESSION_ACTIVITY:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Worker::EMAIL_ID:
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_MFA_REQUIRED:
			case SearchFields_Worker::IS_PASSWORD_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Worker::FULLTEXT_WORKER:
				@$scope = DevblocksPlatform::importGPC($_POST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY:
				@$from = DevblocksPlatform::importGPC($_POST['from'],'string','now');
				@$to = DevblocksPlatform::importGPC($_POST['to'],'string','now');
				@$is_available = DevblocksPlatform::importGPC($_POST['is_available'],'integer',0);
				$criteria = new DevblocksSearchCriteria($field,null,array($from,$to,$is_available));
				break;
				
			case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUPS:
				@$group_ids = DevblocksPlatform::importGPC($_POST['group_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,'in', $group_ids);
				break;
				
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

class DAO_WorkerPref extends Cerb_ORMHelper {
	const SETTING = 'setting';
	const VALUE = 'value';
	const WORKER_ID = 'worker_id';
	
	const CACHE_PREFIX = 'ch_workerpref_';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::SETTING)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::VALUE)
			->string()
			->setMaxLength(65535)
			->setRequired(true)
			;
		$validation
			->addField(self::WORKER_ID)
			->id()
			->setRequired(true)
			;
		
		return $validation->getFields();
	}
	
	static function delete($worker_id, $key) {
		$db = DevblocksPlatform::services()->database();
		$db->ExecuteMaster(sprintf("DELETE FROM worker_pref WHERE worker_id = %d AND setting = %s",
			$worker_id,
			$db->qstr($key)
		));
		
		// Invalidate cache
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
	}
	
	static function set($worker_id, $key, $value) {
		// Persist long-term
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("REPLACE INTO worker_pref (worker_id, setting, value) ".
			"VALUES (%d, %s, %s)",
			$worker_id,
			$db->qstr($key),
			$db->qstr($value)
		));
		
		// Invalidate cache
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
	}
	
	static function setAsJson($worker_id, $key, $value) {
		self::set($worker_id, $key, json_encode($value));
	}
	
	static function get($worker_id, $key, $default=null) {
		$value = null;
		
		if(null !== ($worker_prefs = self::getByWorker($worker_id))) {
			if(isset($worker_prefs[$key])) {
				$value = $worker_prefs[$key];
			}
		}
		
		if(null === $value && !is_null($default)) {
			return $default;
		}
		
		return $value;
	}
	
	static function getAsJson($worker_id, $key, $default=null) {
		$value = self::get($worker_id, $key, $default);
		return json_decode($value, true);
	}

	static function getByKey($key) {
		$db = DevblocksPlatform::services()->database();
		$response = [];
		
		$results = $db->GetArrayMaster(sprintf("SELECT worker_id, value FROM worker_pref WHERE setting = %s",
			$db->qstr($key)
		));
		
		if(is_array($results))
		foreach($results as $result)
			if(!empty($result['worker_id']))
				$response[$result['worker_id']] = $result['value'];
		
		return $response;
	}
	
	static function getByWorker($worker_id) {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($objects = $cache->load(self::CACHE_PREFIX.$worker_id))) {
			$db = DevblocksPlatform::services()->database();
			$sql = sprintf("SELECT setting, value FROM worker_pref WHERE worker_id = %d", $worker_id);
			
			if(false === ($rs = $db->QueryReader($sql)))
				return false;
			
			$objects = [];
			
			if(!($rs instanceof mysqli_result))
				return false;
			
			while($row = mysqli_fetch_assoc($rs)) {
				$objects[$row['setting']] = $row['value'];
			}
			
			mysqli_free_result($rs);
			
			$cache->save($objects, self::CACHE_PREFIX.$worker_id);
		}
		
		return $objects;
	}
};

class Context_Worker extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextBroadcast, IDevblocksContextAutocomplete {
	const ID = 'cerberusweb.contexts.worker';
	const URI = 'worker';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins can edit
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=worker&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Worker();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['email'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_ADDRESS),
			'value' => $model->email_id,
		);
		
		$properties['location'] = array(
			'label' => mb_ucfirst($translate->_('common.location')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->location,
		);
		
		$properties['title'] = array(
			'label' => mb_ucfirst($translate->_('common.title')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->title,
		);
		
		$properties['gender'] = array(
			'label' => mb_ucfirst($translate->_('common.gender')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->getGenderAsString(),
		);
		
		$properties['is_password_disabled'] = array(
			'label' => mb_ucfirst($translate->_('worker.is_password_disabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_password_disabled,
		);
		
		$properties['is_mfa_required'] = array(
			'label' => mb_ucfirst($translate->_('worker.is_mfa_required')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_mfa_required,
		);
		
		$properties['is_superuser'] = array(
			'label' => mb_ucfirst($translate->_('worker.is_superuser')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_superuser,
		);
		
		$properties['mention_name'] = array(
			'label' => mb_ucfirst($translate->_('worker.at_mention_name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->at_mention_name,
		);
		
		$properties['mobile'] = array(
			'label' => mb_ucfirst($translate->_('common.mobile')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->mobile,
		);
		
		$properties['phone'] = array(
			'label' => mb_ucfirst($translate->_('common.phone')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->phone,
		);
		
		$properties['language'] = array(
			'label' => mb_ucfirst($translate->_('common.language')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->language,
		);
		
		$properties['timezone'] = array(
			'label' => mb_ucfirst($translate->_('common.timezone')),
			'type' => 'timezone',
			'value' => $model->timezone,
		);
		
		$properties['calendar_id'] = array(
			'label' => mb_ucfirst($translate->_('common.calendar')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_CALENDAR),
			'value' => $model->calendar_id,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getRandom() {
		return DAO_Worker::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();
		
		if(null == ($worker = DAO_Worker::get($context_id)))
			return false;
		
		$worker_name = $worker->getName();
		
		$who = sprintf("%d-%s",
			$worker->id,
			DevblocksPlatform::strToPermalink($worker_name)
		);
		
		return array(
			'id' => $worker->id,
			'name' => $worker_name,
			'permalink' => $url_writer->writeNoProxy('c=profiles&type=worker&who='.$who, true),
			'updated' => $worker->updated,
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
			'address__label',
			'at_mention_name',
			'is_disabled',
			'is_superuser',
			'language',
			'location',
			'phone',
			'mobile',
			'timezone',
			'updated',
		);
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		$results = DAO_Worker::autocomplete($term, 'models', $query);
		$list = [];

		if(stristr('unassigned',$term) || stristr('nobody',$term) || stristr('empty',$term) || stristr('no worker',$term)) {
			$empty = new stdClass();
			$empty->label = '(no worker)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the worker');
			$list[] = $empty;
		}
		
		if(is_array($results))
		foreach($results as $worker_id => $worker){
			$entry = new stdClass();
			$entry->label = $worker->getName();
			$entry->value = sprintf("%d", $worker_id);
			$entry->icon = $url_writer->write('c=avatars&type=worker&id=' . $worker->id, true) . '?v=' . $worker->updated;
			
			$meta = [];
			
			if($worker->title)
				$meta['title'] = $worker->title;
			
			$entry->meta = $meta;
			
			$list[] = $entry;
		}

		return $list;
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_Worker::getByAtMention($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($worker, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Worker:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER);
		
		// Polymorph
		if(is_numeric($worker)) {
			$worker = DAO_Worker::get($worker);
		} elseif($worker instanceof Model_Worker) {
			// It's what we want already.
		} elseif(is_array($worker)) {
			$worker = Cerb_ORMHelper::recastArrayToModel($worker, 'Model_Worker');
		} else {
			$worker = null;
		}
			
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'at_mention_name' => $prefix.$translate->_('worker.at_mention_name'),
			'dob' => $prefix.$translate->_('common.dob'),
			'first_name' => $prefix.$translate->_('common.name.first'),
			'full_name' => $prefix.$translate->_('common.name.full'),
			'gender' => $prefix.$translate->_('common.gender'),
			'id' => $prefix.$translate->_('common.id'),
			'is_disabled' => $prefix.$translate->_('common.disabled'),
			'is_superuser' => $prefix.$translate->_('worker.is_superuser'),
			'language' => $prefix.$translate->_('common.language'),
			'last_name' => $prefix.$translate->_('common.name.last'),
			'location' => $prefix.$translate->_('common.location'),
			'mobile' => $prefix.$translate->_('common.mobile'),
			'phone' => $prefix.$translate->_('common.phone'),
			'time_format' => $prefix.$translate->_('worker.time_format'),
			'timeout_idle_secs' => $prefix.$translate->_('worker.timeout_idle_secs'),
			'timezone' => $prefix.$translate->_('common.timezone'),
			'title' => $prefix.$translate->_('worker.title'),
			'updated' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'at_mention_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'dob' => Model_CustomField::TYPE_SINGLE_LINE,
			'first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'gender' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_WORKER,
			'is_disabled' => Model_CustomField::TYPE_CHECKBOX,
			'is_superuser' => Model_CustomField::TYPE_CHECKBOX,
			'language' => Model_CustomField::TYPE_SINGLE_LINE,
			'last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'location' => Model_CustomField::TYPE_SINGLE_LINE,
			'mobile' => Model_CustomField::TYPE_SINGLE_LINE,
			'phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'time_format' => Model_CustomField::TYPE_SINGLE_LINE,
			'timezone' => Model_CustomField::TYPE_SINGLE_LINE,
			'timeout_idle_secs' => Model_CustomField::TYPE_NUMBER,
			'title' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
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
		
		// Context for lazy-loading
		$token_values['_context'] = Context_Worker::ID;
		$token_values['_type'] = Context_Worker::URI;
		
		$token_values['_types'] = $token_types;
		
		// Worker token values
		if(null != $worker) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $worker->getName();
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'worker', $worker->id), true) . '?v=' . $worker->updated;
			$token_values['at_mention_name'] = $worker->at_mention_name;
			$token_values['calendar_id'] = $worker->calendar_id;
			$token_values['dob'] = $worker->dob;
			$token_values['id'] = $worker->id;
			$token_values['first_name'] = $worker->first_name;
			$token_values['full_name'] = $worker->getName();
			$token_values['gender'] = $worker->gender;
			$token_values['is_disabled'] = $worker->is_disabled;
			$token_values['is_superuser'] = $worker->is_superuser;
			$token_values['language'] = $worker->language;
			$token_values['last_name'] = $worker->last_name;
			$token_values['location'] = $worker->location;
			$token_values['mobile'] = $worker->mobile;
			$token_values['phone'] = $worker->phone;
			$token_values['time_format'] = $worker->time_format;
			$token_values['timezone'] = $worker->timezone;
			$token_values['timeout_idle_secs'] = $worker->timeout_idle_secs;
			$token_values['title'] = $worker->title;
			$token_values['updated'] = $worker->updated;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($worker, $token_values);
			
			// URL
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=worker&id=%d-%s",$worker->id, DevblocksPlatform::strToPermalink($worker->getName())), true);
			
			// Email
			$token_values['address_id'] = $worker->email_id;
		}
		
		// Worker email
		
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'address_',
			$prefix,
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Worker availability calendar
		
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CALENDAR, null, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'calendar_',
			$prefix,
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'address_id' => DAO_Worker::EMAIL_ID,
			'at_mention_name' => DAO_Worker::AT_MENTION_NAME,
			'calendar_id' => DAO_Worker::CALENDAR_ID,
			'dob' => DAO_Worker::DOB,
			'first_name' => DAO_Worker::FIRST_NAME,
			'gender' => DAO_Worker::GENDER,
			'email_id' => DAO_Worker::EMAIL_ID,
			'id' => DAO_Worker::ID,
			'image' => '_image',
			'is_disabled' => DAO_Worker::IS_DISABLED,
			'is_mfa_required' => DAO_Worker::IS_MFA_REQUIRED,
			'is_password_disabled' => DAO_Worker::IS_PASSWORD_DISABLED,
			'is_superuser' => DAO_Worker::IS_SUPERUSER,
			'language' => DAO_Worker::LANGUAGE,
			'last_name' => DAO_Worker::LAST_NAME,
			'links' => '_links',
			'location' => DAO_Worker::LOCATION,
			'mobile' => DAO_Worker::MOBILE,
			'phone' => DAO_Worker::PHONE,
			'time_format' => DAO_Worker::TIME_FORMAT,
			'timezone' => DAO_Worker::TIMEZONE,
			'timeout_idle_secs' => DAO_Worker::TIMEOUT_IDLE_SECS,
			'title' => DAO_Worker::TITLE,
			'updated' => DAO_Worker::UPDATED,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['at_mention_name']['notes'] = "The nickname used for `@mention` notifications in comments";
		$keys['calendar_id']['notes'] = "The ID of the [calendar](/docs/records/types/calendar/) used to compute worker availability";
		$keys['dob']['notes'] = "Date of birth in `YYYY-MM-DD` format";
		$keys['email_id']['notes'] = "The ID of the primary [email address](/docs/records/types/address/); alternative to `email`";
		$keys['first_name']['notes'] = "Given name";
		$keys['gender']['notes'] = "`F` (female), `M` (male), or blank or unknown";
		$keys['is_disabled']['notes'] = "Is this worker deactivated and prevented from logging in?";
		$keys['is_mfa_required']['notes'] = "Is this worker required to use multi-factor authentication?";
		$keys['is_password_disabled']['notes'] = "Is this worker allowed to log in with a password?";
		$keys['is_superuser']['notes'] = "Is this worker an administrator with full privileges?";
		$keys['language']['notes'] = "ISO-639 language code and ISO-3166 country code; e.g. `en_US`";
		$keys['last_name']['notes'] = "Surname";
		$keys['location']['notes'] = "Location description; `Los Angeles, CA, USA`";
		$keys['mobile']['notes'] = "Mobile number";
		$keys['time_format']['notes'] = "Preference for displaying timestamps, `strftime()` syntax";
		$keys['timezone']['notes'] = "IANA tz/zoneinfo timezone; `America/Los_Angeles`";
		$keys['timeout_idle_secs']['notes'] = "Consider a session idle after this many seconds of inactivity";
		$keys['title']['notes'] = "Job title / Position";
		
		$keys['email'] = [
			'key' => 'email',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'The primary email address of the worker; alternative to `email_id`',
			'type' => 'string',
		];
		
		$keys['email_ids'] = [
			'key' => 'email_ids',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'A comma-separated list of IDs for alternative [email addresses](/docs/records/types/address/)',
			'type' => 'string',
		];
		
		$keys['password'] = [
			'key' => 'password',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => "The worker's password, if applicable; stored security; will be automatically generated if blank",
			'type' => 'string',
		];
		
		unset($keys['address_id']);
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'email':
				if(false == ($address = DAO_Address::lookupAddress($value, true))) {
					$error = sprintf("Failed to lookup address: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Worker::EMAIL_ID] = $address->id;
				break;
				
			case 'email_ids':
				$out_fields[DAO_Worker::_EMAIL_IDS] = $value;
				break;
				
			case 'image':
				$out_fields[DAO_Worker::_IMAGE] = $value;
				break;
				
			case 'password':
				$out_fields[DAO_Worker::_PASSWORD] = $value;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['emails'] = [
			'label' => 'Emails',
			'type' => 'Records',
		];
		
		$lazy_keys['groups'] = [
			'label' => 'Groups',
			'type' => 'Records',
		];
		
		$lazy_keys['roles'] = [
			'label' => 'Roles',
			'type' => 'Records',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKER;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'emails':
				$models = DAO_Address::getByWorkerId($context_id);
				$values['emails'] = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ADDRESS);
				break;
			
			case 'groups':
				$models = DAO_Group::getByMembers([$context_id]);
				$values['groups'] = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_GROUP);
				break;
				
			case 'roles':
				$models = DAO_WorkerRole::getByMember($context_id);
				$values['roles'] = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ROLE);
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
		$view->name = 'Workers';
		$view->addParams(array(
			SearchFields_Worker::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Worker::IS_DISABLED,'=',0),
		), true);
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Workers';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Worker::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}

		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function broadcastRecipientFieldsGet() {
		$results = $this->_broadcastRecipientFieldsGet(CerberusContexts::CONTEXT_WORKER, 'Worker', [
			'address_address',
		]);
		
		asort($results);
		return $results;
	}
	
	function broadcastPlaceholdersGet() {
		$token_values = $this->_broadcastPlaceholdersGet(CerberusContexts::CONTEXT_WORKER);
		return $token_values;
	}
	
	function broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict) {
		$emails = $this->_broadcastRecipientFieldsToEmails($fields, $dict);
		return $emails;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$date = DevblocksPlatform::services()->date();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_WORKER;
		
		$tpl->assign('view_id', $view_id);
		
		$worker = null;
		
		if($context_id) {
			if(false == ($worker = DAO_Worker::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			$worker = new Model_Worker();
			$worker->id = 0;
			$worker->timezone = $active_worker->timezone;
			$worker->time_format = $active_worker->time_format;
			$worker->language = $active_worker->language;
			$worker->timeout_idle_secs = 600;
			$worker->is_password_disabled = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_DEFAULT_WORKER_DISABLE_PASSWORD, 0);
			$worker->is_mfa_required = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_DEFAULT_WORKER_REQUIRE_MFA, 0);
		}
		
		if(!$context_id || $edit) {
			// ACL
			if(!$active_worker->is_superuser)
				return DevblocksPlatform::dieWithHttpError(null, 403);
			
			$tpl->assign('worker', $worker);
			
			$groups = DAO_Group::getAll();
			$tpl->assign('groups', $groups);
			
			// Aliases
			$tpl->assign('aliases', DAO_ContextAlias::get($context, $context_id));
			
			// Custom Fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			// Calendars
			$calendars = DAO_Calendar::getOwnedByWorker($worker);
			$tpl->assign('calendars', $calendars);
			
			// Languages
			$languages = DAO_Translation::getDefinedLangCodes();
			$tpl->assign('languages', $languages);
			
			// Timezones
			$timezones = $date->getTimezones();
			$tpl->assign('timezones', $timezones);
			
			// Time Format
			$tpl->assign('time_format', DevblocksPlatform::getDateTimeFormat());
			
			$tpl->display('devblocks:cerberusweb.core::workers/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $worker);
		}
	}
};
