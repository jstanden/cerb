<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class DAO_Worker extends Cerb_ORMHelper {
	const CACHE_ALL = 'ch_workers';
	
	const AT_MENTION_NAME = 'at_mention_name';
	const AUTH_EXTENSION_ID = 'auth_extension_id';
	const CALENDAR_ID = 'calendar_id';
	const DOB = 'dob';
	const EMAIL_ID = 'email_id';
	const FIRST_NAME = 'first_name';
	const GENDER = 'gender';
	const ID = 'id';
	const IS_DISABLED = 'is_disabled';
	const IS_SUPERUSER = 'is_superuser';
	const LANGUAGE = 'language';
	const LAST_NAME = 'last_name';
	const LOCATION = 'location';
	const MOBILE = 'mobile';
	const PHONE = 'phone';
	const TIME_FORMAT = 'time_format';
	const TIMEZONE = 'timezone';
	const TITLE = 'title';
	const UPDATED = 'updated';
	
	static function create($fields) {
		if(empty($fields[DAO_Worker::EMAIL_ID]))
			return NULL;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO worker () ".
			"VALUES ()"
		);
		if(false == ($rs = $db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();

		self::update($id, $fields);
		
		self::clearCache();
		
		return $id;
	}

	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
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
	
	/**
	 * @return Model_Worker[]
	 */
	static function getAllOnline($idle_limit=600, $idle_kick_limit=0) {
		$session = DevblocksPlatform::getSessionService();

		$sessions = $session->getAll();
		$workers = DAO_Worker::getAll();
		
		if(!is_array($sessions) || empty($sessions))
			return array();
		
		$sessions = array_filter($sessions, function($object) {
			// Only worker-based sessions (no anonymous)
			if(empty($object['user_id']))
				return false;

			return true;
		});
		
		if(empty($sessions))
			return array();

		// Least idle sessions first
		DevblocksPlatform::sortObjects($sessions, '[updated]', false);

		$workers_by_last_activity = array();
		
		foreach($sessions as $object) {
			// If we've already seen this worker, their first session was the least idle
			if(isset($workers_by_last_activity[$object['user_id']]))
				continue;
			
			$workers_by_last_activity[$object['user_id']] = $object['updated'];
		}
		
		// Most idle workers first
		asort($workers_by_last_activity);
		
		foreach($workers_by_last_activity as $worker_id => $last_activity) {
			if(!isset($workers[$worker_id]))
				continue;
			
			$worker = $workers[$worker_id];
			$worker_idle_time = time() - $last_activity;
			
			if($worker_idle_time > $idle_limit) {
				unset($workers_by_last_activity[$worker_id]);
				
				// If we're still clearing seats
				if($idle_kick_limit > 0) {
					
					// Clear all the sessions for this worker
					foreach($sessions as $object) {
						if($object['user_id'] == $worker_id) {
							$session->clear($object['session_key']);
						}
					}
					
					// One more seat freed up
					$idle_kick_limit--;
					
					// Add the session kick to the worker's activity log
					$entry = array(
						//{{actor}} logged {{target}} out to free up a license seat.
						'message' => 'activities.worker.seat_expired',
						'variables' => array(
								'target' => $worker->getName(),
								'idle_time' => $worker_idle_time,
							),
						'urls' => array(
								'target' => sprintf("ctx://cerberusweb.contexts.worker:%d/%s", $worker->id, DevblocksPlatform::strToPermalink($worker->getName())),
							)
					);
					CerberusContexts::logActivity('worker.seat_expired', CerberusContexts::CONTEXT_WORKER, $worker->id, $entry, CerberusContexts::CONTEXT_APPLICATION, 0);
				}
			}
		}
		
		// Least idle workers first
		arsort($workers_by_last_activity);
		
		$active_workers = array_intersect_key($workers, $workers_by_last_activity);
		
		return $active_workers;
	}
	
	/**
	 * 
	 * @param bool $nocache
	 * @param bool $with_disabled
	 * @return Model_Worker[]
	 */
	static function getAll($nocache=false, $with_disabled=true) {
		$cache = DevblocksPlatform::getCacheService();
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, first_name, last_name, email_id, title, is_superuser, is_disabled, auth_extension_id, at_mention_name, timezone, time_format, language, calendar_id, gender, dob, location, phone, mobile, updated ".
			"FROM worker ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
			
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	static function getByAtMentions($at_mentions) {
		if(!is_array($at_mentions) && is_string($at_mentions))
			$at_mentions = array($at_mentions);
		
		$workers = DAO_Worker::getAllActive();
		
		$workers = array_filter($workers, function($worker) use ($at_mentions) {
			foreach($at_mentions as $at_mention) {
				if($worker->at_mention_name && 0 == strcasecmp(ltrim($at_mention, '@'), $worker->at_mention_name)) {
					return true;
				}
			}
			
			return false;
		});
		
		return $workers;
	}
	
	static function getResponsibilities($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$responsibilities = array();
		
		$results = $db->GetArray(sprintf("SELECT worker_id, bucket_id, responsibility_level FROM worker_to_bucket WHERE worker_id = %d",
			$worker_id
		));
		
		foreach($results as $row) {
			if(!isset($responsibilities[$row['bucket_id']]))
				$responsibilities[$row['bucket_id']] = array();
			
			$responsibilities[$row['bucket_id']] = $row['responsibility_level'];
		}
		
		return $responsibilities;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param resource $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Worker();
			$object->at_mention_name = $row['at_mention_name'];
			$object->auth_extension_id = $row['auth_extension_id'];
			$object->calendar_id = intval($row['calendar_id']);
			$object->dob = $row['dob'];
			$object->email_id = intval($row['email_id']);
			$object->first_name = $row['first_name'];
			$object->gender = $row['gender'];
			$object->id = intval($row['id']);
			$object->is_disabled = intval($row['is_disabled']);
			$object->is_superuser = intval($row['is_superuser']);
			$object->language = $row['language'];
			$object->last_name = $row['last_name'];
			$object->location = $row['location'];
			$object->mobile = $row['mobile'];
			$object->phone = $row['phone'];
			$object->time_format = $row['time_format'];
			$object->timezone = $row['timezone'];
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
		
		$workers = DAO_Worker::getAll();
		
		if(is_array($workers))
		foreach($workers as $worker) {
			if($model->id == $worker->email_id)
				return $worker;
		}
		
		return null;
	}
	
	/**
	 * @return array
	 */
	static function getNames() {
		$workers = DAO_Worker::getAllActive();
		$names = array();
		
		foreach($workers as $worker) {
			$names[$worker->id] = !empty($worker->at_mention_name) ? $worker->at_mention_name : $worker->getName();
		}
		
		return $names;
	}
	
	/**
	 * 
	 * @param string $string
	 * @return Model_Worker[]
	 */
	static function getByString($string) {
		$workers = DAO_Worker::getAllActive();
		$patterns = DevblocksPlatform::parseCsvString($string);
		
		$results = array();
		
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
		$db = DevblocksPlatform::getDatabaseService();
		$workloads = array();
		
		$sql = "SELECT 'cerberusweb.contexts.ticket' AS context, owner_id AS worker_id, COUNT(id) AS hits FROM ticket WHERE status_id = 0 GROUP BY owner_id ".
			"UNION ALL ".
			"SELECT 'cerberusweb.contexts.recommendation' AS context, worker_id, COUNT(*) AS hits FROM context_recommendation GROUP BY worker_id ".
			"UNION ALL ".
			"SELECT 'cerberusweb.contexts.notification' AS context, worker_id, COUNT(id) AS hits FROM notification WHERE is_read = 0 GROUP BY worker_id ".
			//"UNION ALL ".
			//"SELECT 'cerberusweb.contexts.task' AS context, owner_id AS worker_id, COUNT(id) AS hits FROM task WHERE is_completed = 0 GROUP BY worker_id ".
			""
			;
		$results = $db->GetArraySlave($sql);
		
		foreach($results as $result) {
			$context = $result['context'];
			$worker_id = $result['worker_id'];
			$hits = $result['hits'];
			
			if(!isset($workloads[$worker_id]))
				$workloads[$worker_id] = array(
					'total' => 0,
					'records' => array(),
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
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED]) && !($option_bits & DevblocksORMHelper::OPT_UPDATE_NO_EVENTS))
			$fields[self::UPDATED] = time();
		
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
				self::_processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
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
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = array();
		$custom_fields = array();

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
					if(in_array($v, array('M', 'F', '')))
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
					
				case 'auth_extension_id':
					if(null !== (Extension_LoginAuthenticator::get($v, false)))
						$change_fields[DAO_Worker::AUTH_EXTENSION_ID] = $v;
					break;
					
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		if(!empty($change_fields))
			DAO_Worker::update($ids, $change_fields);
		
		// Custom Fields
		if(!empty($custom_fields))
			C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_WORKER, $custom_fields, $ids);
		
		// Broadcast
		if(isset($do['broadcast']))
			C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_WORKER, $do['broadcast'], $ids, 'address_address');
		
		$update->markCompleted();
		return true;
	}
	
	static function _processUpdateEvents($ids, $change_fields) {
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
				DAO_AddressToWorker::assign($email_id, $id, true);
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
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		$db->ExecuteMaster("DELETE FROM worker_pref WHERE worker_id NOT IN (SELECT id FROM worker)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_pref records.');
		
		$db->ExecuteMaster("DELETE FROM worker_view_model WHERE worker_id NOT IN (SELECT id FROM worker)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_view_model records.');
		
		$db->ExecuteMaster("DELETE FROM worker_to_group WHERE worker_id NOT IN (SELECT id FROM worker)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_to_group records.');
		
		// Search indexes
		if(isset($tables['fulltext_worker'])) {
			$db->ExecuteMaster("DELETE FROM fulltext_worker WHERE id NOT IN (SELECT id FROM worker)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_worker records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
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
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(worker_id) FROM worker_to_group WHERE group_id = %d",
			$group_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function delete($id) {
		if(empty($id)) return;
		
		/* This event fires before the delete takes place in the db,
		 * so we can denote what is actually changing against the db state
		 */
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'worker.delete',
				array(
					'worker_ids' => array($id),
				)
			)
		);
		
		$db = DevblocksPlatform::getDatabaseService();
		
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
		
		DAO_AddressToWorker::unassignAll($id);
		
		$sql = sprintf("DELETE FROM worker_to_group WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		$sql = sprintf("DELETE FROM worker_to_bucket WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		$sql = sprintf("DELETE FROM snippet_use_history WHERE worker_id = %d", $id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		// Sessions
		DAO_DevblocksSession::deleteByUserIds($id);
		
		// Clear search records
		$search = Extension_DevblocksSearchSchema::get(Search_Worker::ID);
		$search->delete(array($id));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
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
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(DAO_Group::CACHE_ROSTERS);
	}
	
	static function hasAuth($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$worker_auth = $db->GetRowSlave(sprintf("SELECT pass_hash, pass_salt, method FROM worker_auth_hash WHERE worker_id = %d", $worker_id));
		return (is_array($worker_auth) && isset($worker_auth['pass_hash']));
	}
	
	static function setAuth($worker_id, $password, $asMd5=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(is_null($password)) {
			return $db->ExecuteMaster(sprintf("DELETE FROM worker_auth_hash WHERE worker_id = %d",
				$worker_id
			));
			
		} else {
			$salt = CerberusApplication::generatePassword(12);
			
			$password_hash = ($asMd5) ? $password : md5($password);
			
			return $db->ExecuteMaster(sprintf("REPLACE INTO worker_auth_hash (worker_id, pass_hash, pass_salt, method) ".
				"VALUES (%d, %s, %s, %d)",
				$worker_id,
				$db->qstr(sha1($salt.$password_hash)),
				$db->qstr($salt),
				0
			));
		}
	}
	
	static function login($email, $password) {
		$db = DevblocksPlatform::getDatabaseService();

		if(null == ($worker = DAO_Worker::getByEmail($email)) || $worker->is_disabled)
			return null;
		
		$worker_auth = $db->GetRowSlave(sprintf("SELECT pass_hash, pass_salt, method FROM worker_auth_hash WHERE worker_id = %d", $worker->id));
		
		if(!isset($worker_auth['pass_hash']) || !isset($worker_auth['pass_salt']))
			return null;
		
		if(empty($worker_auth['pass_hash']) || empty($worker_auth['pass_salt']))
			return null;
		
		switch(@$worker_auth['method']) {
			default:
				$given_hash = sha1($worker_auth['pass_salt'] . md5($password));
				
				if($given_hash == $worker_auth['pass_hash'])
					return $worker;
				break;
		}
		
		return null;
	}
	
	/**
	 * @return Model_GroupMember[]
	 */
	static function getWorkerGroups($worker_id) {
		// Get the cache
		$rosters = DAO_Group::getRosters();

		$memberships = array();
		
		// Remove any groups our desired worker isn't in
		if(is_array($rosters))
		foreach($rosters as $group_id => $members) {
			if(isset($members[$worker_id])) {
				$memberships[$group_id] = $members[$worker_id];
			}
		}
		
		return $memberships;
	}
	
	public static function random() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOneSlave("SELECT id FROM worker WHERE is_disabled=0 ORDER BY rand() LIMIT 1");
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
			"w.auth_extension_id as %s, ".
			"w.at_mention_name as %s, ".
			"w.timezone as %s, ".
			"w.time_format as %s, ".
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
				SearchFields_Worker::AUTH_EXTENSION_ID,
				SearchFields_Worker::AT_MENTION_NAME,
				SearchFields_Worker::TIMEZONE,
				SearchFields_Worker::TIME_FORMAT,
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
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
		
		array_walk_recursive(
			$params,
			array('DAO_Worker', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 'w',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_WORKER;
		$from_index = 'w.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}
	
	static function autocomplete($term) {
		$db = DevblocksPlatform::getDatabaseService();
		$workers = DAO_Worker::getAll();
		$objects = array();
		
		$results = $db->GetArraySlave(sprintf("SELECT id ".
			"FROM worker ".
			"WHERE is_disabled = 0 ".
			"AND (".
			"first_name LIKE %s ".
			"OR last_name LIKE %s ".
			"%s".
			")",
			$db->qstr($term.'%'),
			$db->qstr($term.'%'),
			(false != strpos($term,' ')
				? sprintf("OR concat(first_name,' ',last_name) LIKE %s ", $db->qstr($term.'%'))
				: '')
		));
		
		if(is_array($results))
		foreach($results as $row) {
			$worker_id = $row['id'];
			
			if(!isset($workers[$worker_id]))
				continue;
				
			$objects[$worker_id] = $workers[$worker_id];
		}
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

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
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Worker::ID]);
			$results[$object_id] = $row;
		}
		
		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(w.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

/**
 * ...
 *
 */
class SearchFields_Worker extends DevblocksSearchFields {
	// Worker
	const ID = 'w_id';
	const AUTH_EXTENSION_ID = 'w_auth_extension_id';
	const AT_MENTION_NAME = 'w_at_mention_name';
	const CALENDAR_ID = 'w_calendar_id';
	const DOB = 'w_dob';
	const EMAIL_ID = 'w_email_id';
	const FIRST_NAME = 'w_first_name';
	const GENDER = 'w_gender';
	const IS_DISABLED = 'w_is_disabled';
	const IS_SUPERUSER = 'w_is_superuser';
	const LANGUAGE = 'w_language';
	const LAST_NAME = 'w_last_name';
	const LOCATION = 'w_location';
	const MOBILE = 'w_mobile';
	const PHONE = 'w_phone';
	const TIMEZONE = 'w_timezone';
	const TIME_FORMAT = 'w_time_format';
	const TITLE = 'w_title';
	const UPDATED = 'w_updated';
	
	const EMAIL_ADDRESS = 'a_address_email';
	
	const FULLTEXT_WORKER = 'ft_worker';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_GROUPS = '*_groups';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_CALENDAR_AVAILABILITY = '*_calendar_availability';
	
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
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_WORKER, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_GROUPS:
				if(!is_array($param->value))
					break;
					
				// Sanitize array
				$param->value = DevblocksPlatform::sanitizeArray($param->value, 'int');
				
				$param->value = array_filter($param->value, function($v) {
					return !empty($v);
				});
				
				if($param->value) {
					return sprintf("w.id %sIN (SELECT worker_id FROM worker_to_group WHERE group_id IN (%s))",
						$param->operator == 'not in' ? 'NOT ' : '',
						implode(',', $param->value)
					);
				}
				break;
				
			case self::VIRTUAL_CALENDAR_AVAILABILITY:
				if(!is_array($param->value) || count($param->value) != 3)
					break;
					
				$from = $param->value[0];
				$to = $param->value[1];
				$is_available = !empty($param->value[2]);
				
				// [TODO] Load all worker availability calendars
				
				$workers = DAO_Worker::getAllActive();
				$results = array();
				
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
			self::AUTH_EXTENSION_ID => new DevblocksSearchField(self::AUTH_EXTENSION_ID, 'w', 'auth_extension_id', $translate->_('worker.auth_extension_id'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CALENDAR_ID => new DevblocksSearchField(self::CALENDAR_ID, 'w', 'calendar_id', $translate->_('common.calendar'), null, true),
			self::DOB => new DevblocksSearchField(self::DOB, 'w', 'dob', $translate->_('common.dob.abbr'), Model_CustomField::TYPE_DATE, true),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'w', 'email_id', ucwords($translate->_('common.email')), null, true),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'w', 'first_name', $translate->_('common.name.first'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::GENDER => new DevblocksSearchField(self::GENDER, 'w', 'gender', $translate->_('common.gender'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'w', 'is_disabled', ucwords($translate->_('common.disabled')), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_SUPERUSER => new DevblocksSearchField(self::IS_SUPERUSER, 'w', 'is_superuser', $translate->_('worker.is_superuser'), Model_CustomField::TYPE_CHECKBOX, true),
			self::LANGUAGE => new DevblocksSearchField(self::LANGUAGE, 'w', 'language', $translate->_('common.language'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'w', 'last_name', $translate->_('common.name.last'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LOCATION => new DevblocksSearchField(self::LOCATION, 'w', 'location', $translate->_('common.location'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MOBILE => new DevblocksSearchField(self::MOBILE, 'w', 'mobile', $translate->_('common.mobile'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'w', 'phone', $translate->_('common.phone'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TIME_FORMAT => new DevblocksSearchField(self::TIME_FORMAT, 'w', 'time_format', $translate->_('worker.time_format'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TIMEZONE => new DevblocksSearchField(self::TIMEZONE, 'w', 'timezone', $translate->_('common.timezone'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'w', 'title', $translate->_('worker.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'w', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'address', 'email', ucwords($translate->_('common.email_address')), Model_CustomField::TYPE_SINGLE_LINE, false),
				
			self::FULLTEXT_WORKER => new DevblocksSearchField(self::FULLTEXT_WORKER, 'ft', 'content', $translate->_('common.content'), 'FT'),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_GROUPS => new DevblocksSearchField(self::VIRTUAL_GROUPS, '*', 'groups', $translate->_('common.groups'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_CALENDAR_AVAILABILITY => new DevblocksSearchField(self::VIRTUAL_CALENDAR_AVAILABILITY, '*', 'calendar_availability', 'Calendar Availability', null),
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
		return array();
	}
	
	public function getFields() {
		return array(
			'content',
		);
	}
	
	public function query($query, $attributes=array(), $limit=null) {
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
	
	private function _indexDictionary($dict, $engine) {
		$logger = DevblocksPlatform::getConsoleLog();

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
	
	public function indexIds(array $ids=array()) {
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
		$logger = DevblocksPlatform::getConsoleLog();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
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
	public $auth_extension_id;
	public $calendar_id = 0;
	public $dob;
	public $email_id = 0;
	public $first_name;
	public $gender;
	public $id;
	public $is_superuser = 0;
	public $is_disabled = 0;
	public $language;
	public $last_name;
	public $location;
	public $mobile;
	public $phone;
	public $time_format;
	public $timezone;
	public $title;
	public $updated;
	
	private $_email_model = null;
	
	function __get($name) {
		switch($name) {
			// [DEPRECATED] Added in 7.1
			case 'email':
				error_log("The 'email' field on worker records is deprecated. Use \$worker->getEmailString() instead.", E_USER_DEPRECATED);
				
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

	function getRoles() {
		return DAO_WorkerRole::getRolesByWorker($this->id);
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
	
	/**
	 * 
	 * @return NULL|string
	 */
	function getEmailString() {
		if(false == $model = $this->getEmailModel())
			return null;
		
		return $model->email;
	}
	
	function getInitials() {
		return mb_convert_case(DevblocksPlatform::strToInitials($this->getName()), MB_CASE_UPPER);
	}
	
	function getLatestActivity() {
		return DAO_ContextActivityLog::getLatestEntriesByActor(CerberusContexts::CONTEXT_WORKER, $this->id, 1);
	}
	
	function getLatestSession() {
		return DAO_DevblocksSession::getLatestByUserId($this->id);
	}

	/**
	 * 
	 * @return array
	 */
	function getResponsibilities() {
		return DAO_Worker::getResponsibilities($this->id);
	}
	
	function getPlaceholderLabelsValues(&$labels, &$values, $label_prefix='Current worker ', $values_prefix='current_worker_') {
		$labels = array();
		$values = array();
		
		$placeholder_labels = array();
			
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $this, $worker_labels, $worker_values, $label_prefix, true, false);
		CerberusContexts::merge($values_prefix, null, $worker_labels, $worker_values, $labels, $values);
		
		@$types = $values['_types'];
		
		foreach($labels as $k => $v) {
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
		
		$calendar = DAO_Calendar::get($this->calendar_id);
		
		if(false == ($calendar = DAO_Calendar::get($this->calendar_id))) {
			$calendar = new Model_Calendar();
			$calendar_events = array();
			
		} else {
			$calendar_events = $calendar->getEvents($day_from, $day_to);
		}
		
		$availability = $calendar->computeAvailability($date_from, $date_to, $calendar_events);
		
		return $availability;
	}
	
	function getAvailabilityAsBlocks() {
		$date_from = time() - (time() % 60);
		$date_to = strtotime('+24 hours', $date_from);
		
		$blocks = array();
		
		$availability = $this->getAvailability($date_from, $date_to);
		$mins = $availability->getMinutes();
		$ticks = strlen($mins);

		while(0 != strlen($mins)) {
			$from = 0;
			$is_available = $mins{$from} == 1;
			
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
			return ("core.config"==substr($priv_id,0,11)) ? false : true;
		
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
		$roles = $this->getRoles();
		
		if(isset($roles[$role_id]))
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
			$dont_notify_on_activities = array();
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
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Worker::EMAIL_ID,
			SearchFields_Worker::VIRTUAL_CONTEXT_LINK,
			SearchFields_Worker::VIRTUAL_GROUPS,
			SearchFields_Worker::VIRTUAL_HAS_FIELDSET,
			SearchFields_Worker::FULLTEXT_WORKER,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Worker::CALENDAR_ID,
			SearchFields_Worker::EMAIL_ID,
			SearchFields_Worker::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Worker::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
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
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Worker::AT_MENTION_NAME:
				case SearchFields_Worker::FIRST_NAME:
				case SearchFields_Worker::GENDER:
				case SearchFields_Worker::IS_DISABLED:
				case SearchFields_Worker::IS_SUPERUSER:
				case SearchFields_Worker::LANGUAGE:
				case SearchFields_Worker::LAST_NAME:
				case SearchFields_Worker::TIMEZONE:
				case SearchFields_Worker::TITLE:
					$pass = true;
					break;
					
				case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
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
		$context = CerberusContexts::CONTEXT_WORKER;
		
		if(!isset($fields[$column]))
			return array();
		
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
				$label_map = array(
					'M' => 'Male',
					'F' => 'Female',
					'' => '(unknown)',
				);
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;

			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
			
			case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Worker::getFields();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$group_names = DAO_Group::getNames();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Worker::FULLTEXT_WORKER),
				),
			'email.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Worker::EMAIL_ID),
				),
			'email' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::EMAIL_ADDRESS, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'firstName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::FIRST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'gender' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::GENDER, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'group' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_GROUPS),
					'examples' => array_slice($group_names, 0, 15),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Worker::ID),
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
						'(noon to 1pm)',
						'(now to +15 mins)',
					),
				),
			'isBusy' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY),
					'examples' => array(
						'noon to 1pm',
						'now to +15 mins',
					),
				),
			'isDisabled' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Worker::IS_DISABLED),
				),
			'language' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::LANGUAGE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'lastName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::LAST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'location' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::LOCATION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'mentionName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::AT_MENTION_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
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
			'timezone' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::TIMEZONE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'title' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Worker::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Worker::UPDATED),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendLinksFromQuickSearchContexts($fields);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_WORKER, $fields, null);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = array();
		
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
			case 'gender':
				$field_key = SearchFields_Worker::GENDER;
				$oper = null;
				$value = null;
				
				if(false == CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value))
					return false;
				
				foreach($value as &$v) {
					if(substr(strtolower($v), 0, 1) == 'm') {
						$v = 'M';
					} else if(substr(strtolower($v), 0, 1) == 'f') {
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
			
			case 'group':
			case 'inGroups':
				$field_key = SearchFields_Worker::VIRTUAL_GROUPS;
				$oper = DevblocksSearchCriteria::OPER_IN;
				
				$terms = array();
				
				foreach($tokens as $token) {
					switch($token->type) {
						case 'T_NOT':
							$oper = DevblocksSearchCriteria::OPER_NIN;
							break;
						case 'T_QUOTED_TEXT':
						case 'T_TEXT':
							$terms[] = $token->value;
							break;
						case 'T_ARRAY':
							$terms += $token->value;
							break;
					}
				}
				
				$groups = DAO_Group::getAll();
				
				if(!is_array($terms))
					break;
				
				$group_ids = array();
				
				foreach($terms as $term) {
					// Allow raw IDs
					if(is_numeric($term) && isset($groups[$term])) {
						$group_ids[intval($term)] = true;
						
					} else {
						foreach($groups as $group_id => $group) {
							if(isset($group_ids[$group_id]))
								continue;
							
							if(false !== stristr($group->name, $term)) {
								$group_ids[$group_id] = true;
							}
						}
					}
				}
				
				if(!empty($group_ids)) {
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						array_keys($group_ids)
					);
				}
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Login auth
		$auth_extensions = Extension_LoginAuthenticator::getAll(false);
		$tpl->assign('auth_extensions', $auth_extensions);
		
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
			case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_Worker::VIRTUAL_GROUPS:
				$groups = DAO_Group::getAll();
				
				// Empty
				if(empty($param->value)) {
					echo "<b>Not</b> a member of any groups";
					
				// Group IDs array
				} elseif(is_array($param->value)) {
					$strings = array();
					
					foreach($param->value as $group_id) {
						if(isset($groups[$group_id]))
							$strings[] = '<b>'.DevblocksPlatform::strEscapeHtml($groups[$group_id]->name).'</b>';
					}
					
					echo sprintf("Is %sa member of %s",
						$param->operator == 'not in' ? 'not ' : '',
						implode(' or ', $strings)
					);
				}
				
				break;
				
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY:
				if(!is_array($param->value) || count($param->value) != 3)
					break;
				
				echo sprintf("Calendar is <b>%s</b> between <b>%s</b> and <b>%s</b>",
					DevblocksPlatform::strEscapeHtml((!empty($param->value[2]) ? 'available' : 'busy')),
					DevblocksPlatform::strEscapeHtml($param->value[0]),
					DevblocksPlatform::strEscapeHtml($param->value[1])
				);
				break;
		}
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

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
			case SearchFields_Worker::TITLE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Worker::EMAIL_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_Worker::DOB:
			case SearchFields_Worker::UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Worker::FULLTEXT_WORKER:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUPS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_group.tpl');
				break;
				
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_WORKER);
				break;
				
			case SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY:
				$tpl->display('devblocks:cerberusweb.core::workers/criteria/calendar_availability.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Worker::GENDER:
				$strings = array();
				$values = is_array($param->value) ? $param->value : array($param->value);
				
				foreach($values as $value) {
					switch($value) {
						case 'M':
							$strings[] = '<b>Male</b>';
							break;
						case 'F':
							$strings[] = '<b>Female</b>';
							break;
						default:
							$strings[] = '<b>(unknown)</b>';
							break;
					}
				}
				
				echo sprintf("%s", implode(' or ', $strings));
				break;
			
			case SearchFields_Worker::IS_DISABLED:
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
			case SearchFields_Worker::TITLE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Worker::DOB:
			case SearchFields_Worker::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Worker::EMAIL_ID:
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Worker::FULLTEXT_WORKER:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Worker::VIRTUAL_CALENDAR_AVAILABILITY:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','now');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','now');
				@$is_available = DevblocksPlatform::importGPC($_REQUEST['is_available'],'integer',0);
				$criteria = new DevblocksSearchCriteria($field,null,array($from,$to,$is_available));
				break;
				
			case SearchFields_Worker::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUPS:
				@$group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $group_ids);
				break;
				
			case SearchFields_Worker::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
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
	const CACHE_PREFIX = 'ch_workerpref_';
	
	static function delete($worker_id, $key) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("DELETE FROM worker_pref WHERE worker_id = %d AND setting = %s",
			$worker_id,
			$db->qstr($key)
		));
		
		// Invalidate cache
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
	}
	
	static function set($worker_id, $key, $value) {
		// Persist long-term
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->ExecuteMaster(sprintf("REPLACE INTO worker_pref (worker_id, setting, value) ".
			"VALUES (%d, %s, %s)",
			$worker_id,
			$db->qstr($key),
			$db->qstr($value)
		));
		
		// Invalidate cache
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
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

	static function getByKey($key) {
		$db = DevblocksPlatform::getDatabaseService();
		$response = array();
		
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
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_PREFIX.$worker_id))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = sprintf("SELECT setting, value FROM worker_pref WHERE worker_id = %d", $worker_id);
			
			if(false === ($rs = $db->ExecuteSlave($sql)))
				return false;
			
			$objects = array();
			
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

class Context_Worker extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;
				
			if($context_id == $worker->id)
				return TRUE;
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=worker&id='.$context_id, true);
		return $url;
	}
	
	function getRandom() {
		return DAO_Worker::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::getUrlService();
		
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
	
	function autocomplete($term) {
		$url_writer = DevblocksPlatform::getUrlService();
		$results = DAO_Worker::autocomplete($term);
		$list = array();

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
			
			$meta = array();
			
			if($worker->title)
				$meta['title'] = $worker->title;
			
			$entry->meta = $meta;
			
			$list[] = $entry;
		}

		return $list;
	}
	
	function getContext($worker, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Worker:';
			
		$translate = DevblocksPlatform::getTranslationService();
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
			'mobile' => 'phone',
			'phone' => 'phone',
			'time_format' => Model_CustomField::TYPE_SINGLE_LINE,
			'timezone' => Model_CustomField::TYPE_SINGLE_LINE,
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
		$token_values = array();
		
		// Context for lazy-loading
		$token_values['_context'] = CerberusContexts::CONTEXT_WORKER;
		$token_values['_types'] = $token_types;
		
		// Worker token values
		if(null != $worker) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $worker->getName();
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
			$token_values['title'] = $worker->title;
			$token_values['updated'] = $worker->updated;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($worker, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=worker&id=%d-%s",$worker->id, DevblocksPlatform::strToPermalink($worker->getName())), true);
			
			// Email
			$token_values['address_id'] = $worker->email_id;
		}
		
		// Worker email
		
		$merge_token_labels = array();
		$merge_token_values = array();
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
		
		$merge_token_labels = array();
		$merge_token_values = array();
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
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKER;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
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
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Workers';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Worker::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}

		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$date = DevblocksPlatform::getDateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(false == ($worker = DAO_Worker::get($context_id))) {
			$worker = new Model_Worker();
			$worker->id = 0;
			$worker->timezone = $active_worker->timezone;
			$worker->time_format = $active_worker->time_format;
			$worker->language = $active_worker->language;
		}
		
		// If a worker is trying to edit and they aren't a superuser, show the card instead
		if($context_id && $edit && !$active_worker->is_superuser)
			$edit = false;
		
		if(empty($context_id) || $edit) {
			// ACL
			if(!$active_worker->is_superuser) {
				$tpl->assign('error_message', "Only administrators can edit worker records.");
				$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
				return;
			}
			
			$tpl->assign('worker', $worker);
			
			$groups = DAO_Group::getAll();
			$tpl->assign('groups', $groups);
			
			// Custom Fields
			$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WORKER, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			// Authenticators
			$auth_extensions = Extension_LoginAuthenticator::getAll(false);
			$tpl->assign('auth_extensions', $auth_extensions);
			
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
			// Counts
			$activity_counts = array(
				'groups' => DAO_Group::countByMemberId($context_id),
				'tickets' => DAO_Ticket::countsByOwnerId($context_id),
				'comments' => DAO_Comment::count(CerberusContexts::CONTEXT_WORKER, $context_id),
				//'emails' => DAO_Address::countByContactId($context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			// Links
			$links = array(
				CerberusContexts::CONTEXT_WORKER => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							CerberusContexts::CONTEXT_WORKER,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Latest activity
			$tpl->assign('latest_activity', $worker->getLatestActivity());
			$tpl->assign('latest_session', $worker->getLatestSession());
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments(CerberusContexts::CONTEXT_WORKER, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}
			
			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_WORKER)))
				return;
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$tpl->display('devblocks:cerberusweb.core::workers/peek.tpl');
		}
	}
};