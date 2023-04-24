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
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class DAO_Group extends Cerb_ORMHelper {
	const CREATED = 'created';
	const ID = 'id';
	const IS_DEFAULT = 'is_default';
	const IS_PRIVATE = 'is_private';
	const REPLY_ADDRESS_ID = 'reply_address_id';
	const REPLY_HTML_TEMPLATE_ID = 'reply_html_template_id';
	const REPLY_PERSONAL = 'reply_personal';
	const REPLY_SIGNATURE_ID = 'reply_signature_id';
	const REPLY_SIGNING_KEY_ID = 'reply_signing_key_id';
	const NAME = 'name';
	const UPDATED = 'updated';
	
	const _IMAGE = '_image';
	const _MEMBERS = '_members';
	
	const CACHE_ALL = 'cerberus_cache_groups_all';
	const CACHE_ROSTERS = 'ch_group_rosters';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
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
			->addField(self::IS_PRIVATE)
			->bit()
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::REPLY_ADDRESS_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS))
			->addValidator(function($value, &$error) {
				if(false == ($address = DAO_Address::get($value))) {
					$error = "is not a valid email address.";
					return false;
				}
				
				if(!$address->mail_transport_id) {
					$error = "is not configured for outgoing mail.";
					return false;
				}
				
				return true;
			})
			;
		$validation
			->addField(self::REPLY_HTML_TEMPLATE_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, true))
			;
		$validation
			->addField(self::REPLY_PERSONAL)
			->string()
			;
		$validation
			->addField(self::REPLY_SIGNATURE_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, true))
			;
		$validation
			->addField(self::REPLY_SIGNING_KEY_ID)
			->id()
			->addValidator($validation->validators()->contextId(Context_GpgPrivateKey::ID, true))
			;
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		// base64 blob png
		$validation
			->addField(self::_IMAGE)
			->image('image/png', 50, 50, 500, 500, 1000000)
			;
		$validation
			->addField(self::_MEMBERS)
			->string()
			->setMaxLength(65535)
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
	
	// Groups
	
	/**
	 *
	 * @param integer $id
	 * @return Model_Group
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$groups = DAO_Group::getAll();
		
		if(isset($groups[$id]))
			return $groups[$id];
			
		return null;
	}
	
	/**
	 * @param string $where
	 * @param string $sortBy
	 * @param bool $sortAsc
	 * @param integer $limit
	 * @return Model_ContactOrg[]
	 */
	static function getWhere($where=null, $sortBy=DAO_Group::NAME, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, is_default, is_private, reply_address_id, reply_html_template_id, reply_personal, reply_signature_id, reply_signing_key_id, created, updated ".
			"FROM worker_group ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;

		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		$objects = self::_getObjectsFromResultSet($rs);

		return $objects;
	}
	
	/**
	 * 
	 * @param boolean $nocache
	 * @return Model_Group[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($groups = $cache->load(self::CACHE_ALL))) {
			$groups = DAO_Group::getWhere(
				null,
				DAO_Group::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($groups))
				return false;
			
			$cache->save($groups, self::CACHE_ALL);
		}
		
		return $groups;
	}
	
	static function getByName($name) {
		return DevblocksPlatform::arraySearchNoCase($name, DAO_Group::getNames());
	}
	
	static function getNames(Model_Worker $for_worker=null) {
		$groups = DAO_Group::getAll();
		$names = [];
		
		foreach($groups as $group) {
			if(is_null($for_worker) || $for_worker->isGroupMember($group->id))
				$names[$group->id] = $group->name;
		}
		
		return $names;
	}
	
	static function getByMembers($worker_ids) {
		if(!is_array($worker_ids))
			$worker_ids = [$worker_ids];
		
		$worker_ids = array_flip($worker_ids);
		
		if(!($rosters = DAO_Group::getRosters()))
			return [];
		
		$rosters = array_filter($rosters, function($roster) use ($worker_ids) {
			$res = array_intersect_key($roster, $worker_ids);
			return !empty($res);
		});
		
		return DAO_Group::getIds(array_keys($rosters));
	}
	
	static function getPublicGroups() {
		$groups = self::getAll();
		
		$groups = array_filter($groups, function(Model_Group $group) {
			return !$group->is_private;
		});
		
		return $groups;
	}
	
	static function getResponsibilities($group_id) {
		$db = DevblocksPlatform::services()->database();
		$responsibilities = [];
		
		$results = $db->GetArrayReader(sprintf("SELECT worker_id, bucket_id, responsibility_level FROM worker_to_bucket WHERE bucket_id IN (SELECT id FROM bucket WHERE group_id = %d)",
			$group_id
		));
		
		foreach($results as $row) {
			if(!isset($responsibilities[$row['bucket_id']]))
				$responsibilities[$row['bucket_id']] = [];
			
			$responsibilities[$row['bucket_id']][$row['worker_id']] = $row['responsibility_level'];
		}
		
		return $responsibilities;
	}
	
	static function setResponsibilities($group_id, $responsibilities) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($responsibilities))
			return false;
		
		$values = [];
		
		foreach($responsibilities as $bucket_id => $workers) {
			if(!is_array($workers))
				continue;
			
			foreach($workers as $worker_id => $level) {
				$values[] = sprintf("(%d,%d,%d)", $bucket_id, $worker_id, $level);
			}
		}
		
		// Wipe current bucket responsibilities
		$db->ExecuteMaster(sprintf("DELETE FROM worker_to_bucket WHERE bucket_id IN (SELECT id FROM bucket WHERE group_id = %d)",
			$group_id
		));
		
		if(!empty($values)) {
			$db->ExecuteMaster(sprintf("REPLACE INTO worker_to_bucket (bucket_id, worker_id, responsibility_level) VALUES %s",
				implode(',', $values)
			));
		}
		
		return true;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Notification[]
	 */
	static private function _getObjectsFromResultSet($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Group();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->is_default = intval($row['is_default']);
			$object->is_private = intval($row['is_private']);
			$object->reply_address_id = intval($row['reply_address_id']);
			$object->reply_html_template_id = intval($row['reply_html_template_id']);
			$object->reply_personal = $row['reply_personal'];
			$object->reply_signature_id = intval($row['reply_signature_id']);
			$object->reply_signing_key_id = intval($row['reply_signing_key_id']);
			$object->created = intval($row['created']);
			$object->updated = intval($row['updated']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	/**
	 *
	 * @return Model_Group|null
	 */
	static function getDefaultGroup() {
		$groups = self::getAll();
		
		if(is_array($groups))
		foreach($groups as $group) { /* @var $group Model_Group */
			if($group->is_default)
				return $group;
		}
		
		return null;
	}
	
	static function setDefaultGroup($group_id) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster("UPDATE worker_group SET is_default = 0");
		$db->ExecuteMaster(sprintf("UPDATE worker_group SET is_default = 1 WHERE id = %d", $group_id));
		
		self::clearCache();
	}
	
	/**
	 *
	 * @param string $name
	 * @return integer
	 */
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO worker_group () VALUES ()";
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_GROUP, $id);
		
		if(!isset($fields[self::CREATED]))
			$fields[self::CREATED] = time();
		
		if(!isset($fields[self::REPLY_ADDRESS_ID]) && false !== ($default_sender = DAO_Address::getDefaultLocalAddress()))
			$fields[self::REPLY_ADDRESS_ID] = $default_sender->id;
		
		self::update($id, $fields);
		
		return $id;
	}

	/**
	 *
	 * @param array $ids
	 * @param array $fields
	 */
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED]))
			$fields[self::UPDATED] = time();
		
		$context = CerberusContexts::CONTEXT_GROUP;
		self::_updateAbstract($context, $ids, $fields);
		
		// Handle membership changes
		if(isset($fields[self::_MEMBERS])) {
			if(false != (@$roster_changes = json_decode($fields[self::_MEMBERS], true))) {
				@$roster_managers = DevblocksPlatform::parseCsvString($roster_changes['manager']);
				@$roster_members = DevblocksPlatform::parseCsvString($roster_changes['member']);
				@$roster_remove = DevblocksPlatform::parseCsvString($roster_changes['remove']);
				
				$changed_member_ids = [];
				
				if(is_array($roster_managers))
				foreach($ids as $group_id)
					foreach($roster_managers as $worker_id) {
						DAO_Group::setGroupMember($group_id, $worker_id, true);
						$changed_member_ids[$worker_id] = $worker_id;
					}
				
				if(is_array($roster_members))
				foreach($ids as $group_id)
					foreach($roster_members as $worker_id) {
						DAO_Group::setGroupMember($group_id, $worker_id, false);
						$changed_member_ids[$worker_id] = $worker_id;
					}
				
				if(is_array($roster_remove))
				foreach($ids as $group_id)
					foreach($roster_remove as $worker_id) {
						DAO_Group::unsetGroupMember($group_id, $worker_id);
						$changed_member_ids[$worker_id] = $worker_id;
					}
			}
			
			unset($fields[self::_MEMBERS]);
			
			foreach($changed_member_ids as $member_id)
				DAO_WorkerRole::clearWorkerCache($member_id);
		}
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_GROUP, $batch_ids);
			}

			// Make changes
			parent::_update($batch_ids, 'worker_group', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.group.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_GROUP, $batch_ids);
			}
		}
		
		// Clear caches
		self::clearCache();
		DAO_Bucket::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_GROUP;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		return true;
	}
	
	static function countByEmailFromId($email_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM worker_group WHERE reply_address_id = %d",
			$email_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function countByEmailSignatureId($sig_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM worker_group WHERE reply_signature_id = %d",
			$sig_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function countByEmailTemplateId($template_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM worker_group WHERE reply_html_template_id = %d",
			$template_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function countByMemberId($worker_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(group_id) FROM worker_to_group WHERE worker_id = %d",
			$worker_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	/**
	 *
	 * @param integer $id
	 */
	static function delete($id) {
		if(empty($id))
			return;
		
		if(false == ($deleted_group = DAO_Group::get($id)))
			return;
		
		$db = DevblocksPlatform::services()->database();
		
		/*
		 * Notify anything that wants to know when groups delete.
		 */
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'group.delete',
				array(
					'group_ids' => array($id),
				)
			)
		);
		
		// Move any records in these buckets to the default group/bucket
		if(false != ($default_group = DAO_Group::getDefaultGroup()) && $default_group->id != $deleted_group->id) {
			if(false != ($default_bucket = $default_group->getDefaultBucket())) {
				DAO_Ticket::updateWhere(array(DAO_Ticket::GROUP_ID => $default_group->id, DAO_Ticket::BUCKET_ID => $default_bucket->id), sprintf("%s = %d", DAO_Ticket::GROUP_ID, $deleted_group->id));		
			}
		}
		
		$sql = sprintf("DELETE FROM worker_group WHERE id = %d", $deleted_group->id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		$sql = sprintf("DELETE FROM group_setting WHERE group_id = %d", $deleted_group->id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$sql = sprintf("DELETE FROM worker_to_group WHERE group_id = %d", $deleted_group->id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		// Delete associated buckets
		
		$deleted_buckets = $deleted_group->getBuckets();
		
		if(is_array($deleted_buckets))
		foreach($deleted_buckets as $deleted_bucket) {
			DAO_Bucket::delete($deleted_bucket->id);
		}

		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_GROUP,
					'context_ids' => array($deleted_group->id)
				)
			)
		);
		
		self::clearCache();
		DAO_Bucket::clearCache();
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
				case 'email_template_id':
					$change_fields[DAO_Group::REPLY_HTML_TEMPLATE_ID] = intval($v);
					break;
				case 'is_private':
					$change_fields[DAO_Group::IS_PRIVATE] = intval($v);
					break;
				case 'send_as':
					$change_fields[DAO_Group::REPLY_PERSONAL] = $v;
					break;
				case 'send_from_id':
					$change_fields[DAO_Group::REPLY_ADDRESS_ID] = intval($v);
					break;
				case 'signature_id':
					$change_fields[DAO_Group::REPLY_SIGNATURE_ID] = intval($v);
					break;
				case 'signing_key_id':
					$change_fields[DAO_Group::REPLY_SIGNING_KEY_ID] = intval($v);
					break;
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_GROUP, $ids);
	
		DAO_Group::update($ids, $change_fields, false);
		
		// Custom Fields
		C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_GROUP, $custom_fields, $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_GROUP, $do['behavior'], $ids);
		
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_GROUP, $ids);
		
		$update->markCompleted();
		return true;
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$db->ExecuteMaster("DELETE FROM bucket WHERE group_id NOT IN (SELECT id FROM worker_group)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' bucket records.');
		
		$db->ExecuteMaster("DELETE FROM group_setting WHERE group_id NOT IN (SELECT id FROM worker_group)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' group_setting records.');
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_GROUP,
					'context_table' => 'worker_group',
					'context_key' => 'id',
				)
			)
		);
	}
	
	static function setGroupMember($group_id, $worker_id, $is_manager=false) {
		if(empty($worker_id) || empty($group_id))
			return FALSE;
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO worker_to_group (worker_id, group_id, is_manager) VALUES (%d, %d, %d) ".
			"ON DUPLICATE KEY UPDATE is_manager=%d",
			$worker_id,
			$group_id,
			($is_manager?1:0),
			($is_manager?1:0)
		);
		$db->ExecuteMaster($sql);
		
		if(1 == $db->Affected_Rows()) { // insert but no delete
			DAO_Group::setMemberDefaultResponsibilities($group_id, $worker_id);
		}
		
		self::clearCache();
	}
	
	static function setMemberDefaultResponsibilities($group_id, $worker_id) {
		if(empty($worker_id) || empty($group_id))
			return FALSE;
		
		if(false == ($group = DAO_Group::get($group_id)))
			return FALSE;
		
		$buckets = $group->getBuckets();
		$responsibilities = [];
		
		if(is_array($buckets))
		foreach(array_keys($buckets) as $bucket_id) {
			$responsibilities[$bucket_id] = 50;
		}
		
		self::addMemberResponsibilities($group_id, $worker_id, $responsibilities);
	}
	
	static function addMemberResponsibilities($group_id, $worker_id, $responsibilities) {
		if(empty($worker_id) || empty($group_id) || empty($responsibilities) || !is_array($responsibilities))
			return FALSE;
		
		$db = DevblocksPlatform::services()->database();
		
		$values = [];
		
		foreach($responsibilities as $bucket_id => $level) {
			$values[] = sprintf("(%d,%d,%d)",
				$worker_id,
				$bucket_id,
				$level
			);
		}
		
		if(empty($values))
			return;
		
		$sql = sprintf("REPLACE INTO worker_to_bucket (worker_id, bucket_id, responsibility_level) VALUES %s",
			implode(',', $values)
		);
		$db->ExecuteMaster($sql);
		
		// [TODO] Clear responsibility cache
	}
	
	static function setBucketDefaultResponsibilities($bucket_id) {
		$responsibilities = [];
		
		if(false == ($bucket = DAO_Bucket::get($bucket_id)))
			return false;
		
		if(false == ($group = $bucket->getGroup()))
			return false;
		
		if(false == ($members = $group->getMembers()))
			return false;
		
		if(is_array($members))
		foreach(array_keys($members) as $worker_id) {
			$responsibilities[$worker_id] = 50;
		}
		
		self::setBucketResponsibilities($bucket_id, $responsibilities);
	}
	
	static function setBucketResponsibilities($bucket_id, $responsibilities) {
		if(empty($bucket_id) || empty($responsibilities) || !is_array($responsibilities))
			return FALSE;
		
		$db = DevblocksPlatform::services()->database();
		
		$values = [];
		
		foreach($responsibilities as $worker_id => $level) {
			$values[] = sprintf("(%d,%d,%d)",
				$worker_id,
				$bucket_id,
				$level
			);
		}
		
		if(empty($values))
			return;
		
		$sql = sprintf("REPLACE INTO worker_to_bucket (worker_id, bucket_id, responsibility_level) VALUES %s",
			implode(',', $values)
		);
		$db->ExecuteMaster($sql);
		
		// [TODO] Clear responsibility cache
	}
	
	static function unsetGroupMember($group_id, $worker_id) {
		if(empty($worker_id) || empty($group_id))
			return FALSE;
			
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM worker_to_group WHERE group_id = %d AND worker_id = %d",
			$group_id,
			$worker_id
		);
		$db->ExecuteMaster($sql);
		
		self::unsetGroupMemberResponsibilities($group_id, $worker_id);
		self::clearCache();
	}
	
	static function unsetGroupMemberResponsibilities($group_id, $worker_id) {
		if(empty($worker_id) || empty($group_id))
			return FALSE;
			
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM worker_to_bucket WHERE worker_id = %d AND bucket_id IN (SELECT id FROM bucket WHERE group_id = %d)",
			$worker_id,
			$group_id
		);
		$db->ExecuteMaster($sql);
		
		// [TODO] Clear responsibility cache
	}
	
	static function getRosters() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($objects = $cache->load(self::CACHE_ROSTERS))) {
			$db = DevblocksPlatform::services()->database();
			$sql = sprintf("SELECT wt.worker_id, wt.group_id, wt.is_manager, w.is_disabled ".
				"FROM worker_to_group wt ".
				"INNER JOIN worker_group g ON (wt.group_id=g.id) ".
				"INNER JOIN worker w ON (w.id=wt.worker_id) ".
				"ORDER BY g.name ASC, w.first_name ASC "
			);
			
			if(false == ($rs = $db->QueryReader($sql)))
				return false;
			
			$objects = [];
			
			if(!($rs instanceof mysqli_result))
				return false;
			
			while($row = mysqli_fetch_assoc($rs)) {
				$worker_id = intval($row['worker_id']);
				$group_id = intval($row['group_id']);
				$is_manager = intval($row['is_manager']);
				$is_disabled = intval($row['is_disabled']);
				
				if($is_disabled)
					continue;
				
				if(!isset($objects[$group_id]))
					$objects[$group_id] = [];
				
				$member = new Model_GroupMember();
				$member->id = $worker_id;
				$member->group_id = $group_id;
				$member->is_manager = $is_manager;
				$objects[$group_id][$worker_id] = $member;
			}
			
			mysqli_free_result($rs);
			
			$cache->save($objects, self::CACHE_ROSTERS);
		}
		
		return $objects;
	}
	
	static function getGroupMembers($group_id) {
		$rosters = self::getRosters();
		
		if(isset($rosters[$group_id]))
			return $rosters[$group_id];
		
		return null;
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
		$cache->remove(self::CACHE_ROSTERS);
	}
	
	public static function random() {
		return self::_getRandom('worker_group');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Group::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Group', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"g.id as %s, ".
			"g.name as %s, ".
			"g.is_default as %s, ".
			"g.is_private as %s, ".
			"g.reply_address_id as %s, ".
			"g.reply_html_template_id as %s, ".
			"g.reply_personal as %s, ".
			"g.reply_signature_id as %s, ".
			"g.reply_signing_key_id as %s, ".
			"g.created as %s, ".
			"g.updated as %s ",
				SearchFields_Group::ID,
				SearchFields_Group::NAME,
				SearchFields_Group::IS_DEFAULT,
				SearchFields_Group::IS_PRIVATE,
				SearchFields_Group::REPLY_ADDRESS_ID,
				SearchFields_Group::REPLY_HTML_TEMPLATE_ID,
				SearchFields_Group::REPLY_PERSONAL,
				SearchFields_Group::REPLY_SIGNATURE_ID,
				SearchFields_Group::REPLY_SIGNING_KEY_ID,
				SearchFields_Group::CREATED,
				SearchFields_Group::UPDATED
			);
			
		$join_sql = "FROM worker_group g ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Group');

		$result = array(
			'primary_table' => 'g',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	/**
	 * @param string[] $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param int $limit
	 * @param int $page
	 * @param null $sortBy
	 * @param null $sortAsc
	 * @param bool $withCounts
	 * @return array|bool
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
			SearchFields_Group::ID,
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

class SearchFields_Group extends DevblocksSearchFields {
	// Worker
	const ID = 'g_id';
	const NAME = 'g_name';
	const CREATED = 'g_created';
	const IS_DEFAULT = 'g_is_default';
	const IS_PRIVATE = 'g_is_private';
	const REPLY_ADDRESS_ID = 'g_reply_address_id';
	const REPLY_HTML_TEMPLATE_ID = 'g_reply_html_template_id';
	const REPLY_PERSONAL = 'g_reply_personal';
	const REPLY_SIGNATURE_ID = 'g_reply_signature_id';
	const REPLY_SIGNING_KEY_ID = 'g_reply_signing_key_id';
	const UPDATED = 'g_updated';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_MANAGER_SEARCH = '*_manager_search';
	const VIRTUAL_MEMBER_SEARCH = '*_member_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'g.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_GROUP => new DevblocksSearchFieldContextKeys('g.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_GROUP, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_GROUP), '%s'), self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_MANAGER_SEARCH:
				$sql = "SELECT DISTINCT wtg.group_id FROM worker_to_group wtg WHERE wtg.is_manager = 1 AND wtg.worker_id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_WORKER, $sql, 'g.id');
				break;
				
			case self::VIRTUAL_MEMBER_SEARCH:
				$sql = "SELECT DISTINCT wtg.group_id FROM worker_to_group wtg WHERE wtg.worker_id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_WORKER, $sql, 'g.id');
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
			case 'send.from':
				$key = 'send.from.id';
				break;
				
			case 'signature':
				$key = 'signature.id';
				break;
				
			case 'template':
				$key = 'template.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Group::ID:
				$models = DAO_Group::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Group::IS_DEFAULT:
			case SearchFields_Group::IS_PRIVATE:
				$label_map = [
					0 => DevblocksPlatform::translate('common.no'),
					1 => DevblocksPlatform::translate('common.yes'),
				];
				return $label_map;
				break;
				
			case SearchFields_Group::REPLY_ADDRESS_ID:
				$models = DAO_Address::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'email', 'id');
				break;
				
			case SearchFields_Group::REPLY_HTML_TEMPLATE_ID:
				$models = DAO_MailHtmlTemplate::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Group::REPLY_SIGNATURE_ID:
				$models = DAO_EmailSignature::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Group::REPLY_SIGNING_KEY_ID:
				$models = DAO_GpgPrivateKey::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
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
	static private function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'g', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'g', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'g', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::IS_DEFAULT => new DevblocksSearchField(self::IS_DEFAULT, 'g', 'is_default', $translate->_('common.default'), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_PRIVATE => new DevblocksSearchField(self::IS_PRIVATE, 'g', 'is_private', $translate->_('common.private'), Model_CustomField::TYPE_CHECKBOX, true),
			self::REPLY_ADDRESS_ID => new DevblocksSearchField(self::REPLY_ADDRESS_ID, 'g', 'reply_address_id', $translate->_('common.send.from'), Model_CustomField::TYPE_NUMBER, true),
			self::REPLY_HTML_TEMPLATE_ID => new DevblocksSearchField(self::REPLY_HTML_TEMPLATE_ID, 'g', 'reply_html_template_id', $translate->_('common.email_template'), Model_CustomField::TYPE_NUMBER, true),
			self::REPLY_PERSONAL => new DevblocksSearchField(self::REPLY_PERSONAL, 'g', 'reply_personal', $translate->_('common.send.as'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::REPLY_SIGNATURE_ID => new DevblocksSearchField(self::REPLY_SIGNATURE_ID, 'g', 'reply_signature_id', $translate->_('common.signature'), Model_CustomField::TYPE_NUMBER, true),
			self::REPLY_SIGNING_KEY_ID => new DevblocksSearchField(self::REPLY_SIGNING_KEY_ID, 'g', 'reply_signing_key_id', $translate->_('common.encrypt.signing.key'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'g', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_MANAGER_SEARCH => new DevblocksSearchField(self::VIRTUAL_MANAGER_SEARCH, '*', 'manager_search', null, null, false),
			self::VIRTUAL_MEMBER_SEARCH => new DevblocksSearchField(self::VIRTUAL_MEMBER_SEARCH, '*', 'member_search', null, null, false),
		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Group {
	public $id;
	public $name;
	public $count;
	public $is_default = 0;
	public $is_private = 0;
	public $reply_address_id = 0;
	public $reply_personal;
	public $reply_signature_id = 0;
	public $reply_signing_key_id = 0;
	public $reply_html_template_id = 0;
	public $created;
	public $updated;
	
	public function __toString() {
		return $this->name;
	}
	
	public function getMembers() {
		return DAO_Group::getGroupMembers($this->id);
	}
	
	public function getResponsibilities() {
		return DAO_Group::getResponsibilities($this->id);
	}
	
	public function setResponsibilities($responsibilities) {
		return DAO_Group::setResponsibilities($this->id, $responsibilities);
	}
	
	/**
	 * @return Model_Bucket
	 */
	public function getDefaultBucket() {
		$buckets = $this->getBuckets();
		
		foreach($buckets as $bucket)
			if($bucket->is_default)
				return $bucket;
			
		return null;
	}
	
	public function getBuckets() {
		return DAO_Bucket::getByGroup($this->id);
	}
	
	/**
	 *
	 * @param integer $bucket_id
	 * @return Model_Address
	 */
	public function getReplyTo($bucket_id=0) {
		if($bucket_id && $bucket = DAO_Bucket::get($bucket_id)) {
			return $bucket->getReplyTo();
			
		} else {
			return DAO_Address::get($this->reply_address_id);
		}
		
		return null;
	}
	
	public function getReplyFrom($bucket_id=0) {
		if($bucket_id && $bucket = DAO_Bucket::get($bucket_id)) {
			return $bucket->getReplyFrom();
			
		} else {
			return $this->reply_address_id;
		}
		
		return null;
	}
	
	public function getReplyPersonal($bucket_id=0, $worker_model=null) {
		if($bucket_id && $bucket = DAO_Bucket::get($bucket_id)) {
			return $bucket->getReplyPersonal($worker_model);
			
		} else {
			return $this->reply_personal;
		}
		
		return null;
	}
	
	public function getReplySignature($bucket_id=0, $worker_model=null, $as_html=false) {
		if($bucket_id && $bucket = DAO_Bucket::get($bucket_id)) {
			return $bucket->getReplySignature($worker_model, $as_html);
			
		} else if (false != ($signature = DAO_EmailSignature::get($this->reply_signature_id))) {
			return $signature->getSignature($worker_model, $as_html);
		}
		
		return null;
	}
	
	public function getReplySigningKey($bucket_id=0) {
		if($bucket_id && $bucket = DAO_Bucket::get($bucket_id)) {
			return $bucket->getReplySigningKey();
			
		} else if (false != ($signing_key = DAO_GpgPrivateKey::get($this->reply_signing_key_id))) {
			return $signing_key;
		}
		
		return null;
	}
	
	public function getReplyHtmlTemplate($bucket_id=0) {
		if($bucket_id && $bucket = DAO_Bucket::get($bucket_id)) {
			return $bucket->getReplyHtmlTemplate();
			
		} else {
			return DAO_MailHtmlTemplate::get($this->reply_html_template_id);
		}
		
		return null;
	}
};

class DAO_GroupSettings extends Cerb_ORMHelper {
	const GROUP_ID = 'group_id';
	const SETTING = 'setting';
	const VALUE = 'value';
	
	const SETTING_SUBJECT_HAS_MASK = 'subject_has_mask';
	const SETTING_SUBJECT_PREFIX = 'subject_prefix';
	
	const CACHE_ALL = 'ch_group_settings';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::GROUP_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::SETTING)
			->string()
			->setMaxLength(64)
			->setRequired(true)
			;
		$validation
			->addField(self::VALUE)
			->string()
			->setMaxLength(65535)
			->setRequired(true)
			;
		
		return $validation->getFields();
	}
	
	static function set($group_id, $key, $value) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("REPLACE INTO group_setting (group_id, setting, value) ".
			"VALUES (%d, %s, %s)",
			$group_id,
			$db->qstr($key),
			$db->qstr($value)
		));
		
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function get($group_id, $key, $default=null) {
		$value = null;
		
		if(null !== ($group = self::getSettings($group_id)) && isset($group[$key])) {
			$value = $group[$key];
		}
		
		if(null == $value && !is_null($default)) {
			return $default;
		}
		
		return $value;
	}
	
	static function getSettings($group_id=0) {
		$cache = DevblocksPlatform::services()->cache();
		if(null === ($groups = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::services()->database();
	
			$groups = [];
			
			$sql = "SELECT group_id, setting, value FROM group_setting";
			
			if(false == ($rs = $db->QueryReader($sql)))
				return false;
			
			if(!($rs instanceof mysqli_result))
				return false;
			
			while($row = mysqli_fetch_assoc($rs)) {
				$gid = intval($row['group_id']);
				
				if(!isset($groups[$gid]))
					$groups[$gid] = [];
				
				$groups[$gid][$row['setting']] = $row['value'];
			}
			
			mysqli_free_result($rs);
			
			$cache->save($groups, self::CACHE_ALL);
		}

		// Empty
		if(empty($groups))
			return null;
		
		// Specific group
		if(!empty($group_id)) {
			// Requested group id exists
			if(isset($groups[$group_id]))
				return $groups[$group_id];
			else // doesn't
				return null;
		}
		
		// All groups
		return $groups;
	}
};

class View_Group extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'groups';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Groups';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Group::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Group::NAME,
			SearchFields_Group::IS_PRIVATE,
			SearchFields_Group::IS_DEFAULT,
			SearchFields_Group::REPLY_ADDRESS_ID,
			SearchFields_Group::REPLY_PERSONAL,
			SearchFields_Group::REPLY_SIGNATURE_ID,
			SearchFields_Group::REPLY_SIGNING_KEY_ID,
			SearchFields_Group::REPLY_HTML_TEMPLATE_ID,
			SearchFields_Group::UPDATED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Group::VIRTUAL_HAS_FIELDSET,
			SearchFields_Group::VIRTUAL_CONTEXT_LINK,
			SearchFields_Group::VIRTUAL_MANAGER_SEARCH,
			SearchFields_Group::VIRTUAL_MEMBER_SEARCH,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Group::search(
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

		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Group');
		
		return $objects;
	}
	
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Group', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Group', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Group::IS_DEFAULT:
				case SearchFields_Group::IS_PRIVATE:
				case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_GROUP;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Group::IS_DEFAULT;
			case SearchFields_Group::IS_PRIVATE;
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
				
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK;
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_Group::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Group::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Group::CREATED),
				),
			'default' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Group::IS_DEFAULT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Group::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_GROUP],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Group::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					]
				),
			'manager' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Group::VIRTUAL_MANAGER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'member' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Group::VIRTUAL_MEMBER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'score' => 2000,
					'options' => array('param_key' => SearchFields_Group::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:groups by:name~25 query:(name:{{term}}*) format:dictionaries',
						'key' => 'name',
						'limit' => 25,
					]
				),
			'private' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Group::IS_PRIVATE),
				),
			'send.as' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Group::REPLY_PERSONAL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'send.from.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Group::REPLY_ADDRESS_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => 'mailTransport.id:>0'],
					]
				),
			'signature.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Group::REPLY_SIGNATURE_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_EMAIL_SIGNATURE, 'q' => ''],
					]
				),
			'signing.key.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Group::REPLY_SIGNING_KEY_ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_GpgPrivateKey::ID, 'q' => ''],
					]
				),
			'template.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Group::REPLY_HTML_TEMPLATE_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, 'q' => ''],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Group::UPDATED),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Group::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_GROUP, $fields, null);
		
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
			
			case 'manager':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Group::VIRTUAL_MANAGER_SEARCH);
				break;
				
			case 'member':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Group::VIRTUAL_MEMBER_SEARCH);
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

		$custom_fields = DAO_CustomField::getByContext(Context_Group::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$replyto_addresses = DAO_Address::getLocalAddresses();
		$tpl->assign('replyto_addresses', $replyto_addresses);
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		$signatures = DAO_EmailSignature::getAll();
		$tpl->assign('signatures', $signatures);
		
		$signing_keys = DAO_GpgPrivateKey::getAll();
		$tpl->assign('signing_keys', $signing_keys);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::groups/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Group::VIRTUAL_MANAGER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.manager')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Group::VIRTUAL_MEMBER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.member')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Group::IS_DEFAULT:
			case SearchFields_Group::IS_PRIVATE:
				parent::_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_Group::REPLY_ADDRESS_ID:
			case SearchFields_Group::REPLY_HTML_TEMPLATE_ID:
			case SearchFields_Group::REPLY_SIGNATURE_ID:
			case SearchFields_Group::REPLY_SIGNING_KEY_ID:
				$label_map = SearchFields_Group::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Group::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Group::REPLY_ADDRESS_ID:
			case SearchFields_Group::REPLY_HTML_TEMPLATE_ID:
			case SearchFields_Group::REPLY_SIGNATURE_ID:
			case SearchFields_Group::REPLY_SIGNING_KEY_ID:
				break;
				
			case SearchFields_Group::NAME:
			case SearchFields_Group::REPLY_PERSONAL:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Group::CREATED:
			case SearchFields_Group::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Group::IS_DEFAULT:
			case SearchFields_Group::IS_PRIVATE:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
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

class Context_Group extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = 'cerberusweb.contexts.group';
	const URI = 'group';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins and group managers can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_GROUP)))
			return CerberusContexts::denyEverything($models);
		
		DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'members');
		
		$results = array_fill_keys(array_keys($dicts), false);
			
		switch($actor->_context) {
			// A group can manage itself
			case CerberusContexts::CONTEXT_GROUP:
				foreach($dicts as $context_id => $dict) {
					if($dict->id == $actor->id) {
						$results[$context_id] = true;
					}
				}
				break;
			
			// A worker can edit groups they are a manager of
			case CerberusContexts::CONTEXT_WORKER:
				foreach($dicts as $context_id => $dict) {
					if(is_array($dict->members) && isset($dict->members[$actor->id]) && $dict->members[$actor->id]['is_manager']) {
						$results[$context_id] = true;
					}
				}
				break;
		}
	
		if(is_array($models)) {
			return $results;
		} else {
			return array_shift($results);
		}
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Group::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=group&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Group();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_GROUP,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['send_from'] = array(
			'label' => mb_ucfirst($translate->_('common.send.from')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->reply_address_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			],
		);
		
		$properties['send_as'] = array(
			'label' => mb_ucfirst($translate->_('common.send.as')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->reply_personal,
		);
		
		$properties['template_id'] = array(
			'label' => mb_ucfirst($translate->_('common.email_template')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->reply_html_template_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE,
			],
		);
		
		$properties['signature_id'] = array(
			'label' => mb_ucfirst($translate->_('common.signature')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->reply_signature_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_EMAIL_SIGNATURE,
			],
		);
		
		$properties['signing_key_id'] = array(
			'label' => mb_ucfirst($translate->_('common.encrypt.signing.key')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->reply_signing_key_id,
			'params' => [
				'context' => Context_GpgPrivateKey::ID,
			],
		);
		
		$properties['is_default'] = array(
			'label' => mb_ucfirst($translate->_('common.default')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_default,
		);
		
		$properties['is_private'] = array(
			'label' => mb_ucfirst($translate->_('common.private')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_private,
		);
		
		$properties['created_at'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created,
		);
		
		$properties['updated_at'] = array(
			'label' => mb_ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($group = DAO_Group::get($context_id)))
			return false;
		
		$url = $this->profileGetUrl($context_id);
		
		$who = DevblocksPlatform::strToPermalink($group->name);
		
		if(!empty($who))
			$url .= '-' . $who;
		
		return array(
			'id' => $group->id,
			'created' => $group->created,
			'name' => $group->name,
			'updated' => $group->updated,
			'permalink' => $url,
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
		return [
			'replyto__label',
			'is_private',
			'is_default',
			'updated',
		];
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		$list = [];
		
		list($results,) = DAO_Group::search(
			[],
			[
				new DevblocksSearchCriteria(SearchFields_Group::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
			],
			25,
			0,
			DAO_Group::NAME,
			true,
			false
		);

		if(is_array($results))
		foreach($results as $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_Group::NAME];
			$entry->value = $row[SearchFields_Group::ID];
			$entry->icon = $url_writer->write('c=avatars&type=group&id=' . $row[SearchFields_Group::ID], true) . '?v=' . $row[SearchFields_Group::UPDATED];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($group, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Group:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GROUP);
		
		// Polymorph
		if(is_numeric($group)) {
			$group = DAO_Group::get($group);
		} elseif($group instanceof Model_Group) {
			// It's what we want already.
		} elseif(is_array($group)) {
			$group = Cerb_ORMHelper::recastArrayToModel($group, 'Model_Group');
		} else {
			$group = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'created' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'is_default' => $prefix.$translate->_('common.default'),
			'is_private' => $prefix.$translate->_('common.private'),
			'name' => $prefix.$translate->_('common.name'),
			'updated' => $prefix.$translate->_('common.updated'),
			'reply_personal' => $prefix.$translate->_('common.send.as'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		];
		
		// Token types
		$token_types = [
			'_label' => 'context_url',
			'created' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_default' => Model_CustomField::TYPE_CHECKBOX,
			'is_private' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'replyto_id' => Model_CustomField::TYPE_NUMBER,
			'reply_html_template_id' => Model_CustomField::TYPE_NUMBER,
			'reply_personal' => Model_CustomField::TYPE_SINGLE_LINE,
			'reply_signature_id' => Model_CustomField::TYPE_NUMBER,
			'reply_signing_key_id' => Model_CustomField::TYPE_NUMBER,
		];
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_Group::ID;
		$token_values['_type'] = Context_Group::URI;
		
		$token_values['_types'] = $token_types;
		
		// Group token values
		if(null != $group) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $group->name;
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'group', $group->id), true) . '?v=' . $group->updated;
			$token_values['created'] = $group->created;
			$token_values['id'] = $group->id;
			$token_values['is_default'] = $group->is_default;
			$token_values['is_private'] = $group->is_private;
			$token_values['name'] = $group->name;
			$token_values['updated'] = $group->updated;
			
			$token_values['replyto_id'] = $group->reply_address_id;
			$token_values['reply_html_template_id'] = $group->reply_html_template_id;
			$token_values['reply_personal'] = $group->reply_personal;
			$token_values['reply_signature_id'] = $group->reply_signature_id;
			$token_values['reply_signing_key_id'] = $group->reply_signing_key_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($group, $token_values);
			
			// URL
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=group&id=%d-%s", $group->id, DevblocksPlatform::strToPermalink($group->name)), true);
		}
		
		// Reply-To Address
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::scrubTokensWithRegexp(
			$merge_token_labels,
			$merge_token_values,
			array(
				'#^contact_(.*)$#',
				'#^org_(.*)$#',
			)
		);
		
		CerberusContexts::merge(
			'replyto_',
			$prefix.'Send from:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// HTML template
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'reply_html_template_',
			$prefix.'Email template:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Email signature
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'reply_signature_',
			$prefix.'Signature:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Email signing key
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(Context_GpgPrivateKey::ID, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'reply_signing_key_',
			$prefix.'Signing Key:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created' => DAO_Group::CREATED,
			'id' => DAO_Group::ID,
			'image' => '_image',
			'is_default' => DAO_Group::IS_DEFAULT,
			'is_private' => DAO_Group::IS_PRIVATE,
			'links' => '_links',
			'name' => DAO_Group::NAME,
			'reply_address_id' => DAO_Group::REPLY_ADDRESS_ID,
			'replyto_id' => DAO_Group::REPLY_ADDRESS_ID,
			'reply_html_template_id' => DAO_Group::REPLY_HTML_TEMPLATE_ID,
			'reply_personal' => DAO_Group::REPLY_PERSONAL,
			'reply_signature_id' => DAO_Group::REPLY_SIGNATURE_ID,
			'reply_signing_key_id' => DAO_Group::REPLY_SIGNING_KEY_ID,
			'updated' => DAO_Group::UPDATED,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['members'] = [
			'key' => 'members',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded array of [worker](/docs/records/types/worker/) IDs; `[1,2,3]`',
			'type' => 'string',
		];
		
		$keys['is_default']['notes'] = "[Tickets](/docs/tickets/) are assigned to the default group when no other routing rules match";
		$keys['is_private']['notes'] = "The content in public (`0`) groups is visible to everyone; in private (`1`) groups content is only visible to members";
		$keys['reply_address_id']['notes'] = "The ID of the [email address](/docs/records/types/address/) used when sending replies from this group";
		$keys['reply_html_template_id']['notes'] = "The ID of the default [mail template](/docs/records/types/html_template/) used when sending HTML mail from this group";
		$keys['reply_personal']['notes'] = "The default personal name in the `From:` of replies";
		$keys['reply_signature_id']['notes'] = "The ID of the default [signature](/docs/records/types/email_signature/) used when sending replies from this group";
		$keys['reply_signing_key_id']['notes'] = "The [private key](/docs/records/types/gpg_private_key/) used to cryptographically sign outgoing mail";
		
		unset($keys['replyto_id']);
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'image':
				$out_fields[DAO_Group::_IMAGE] = $value;
				break;
			
				
			case 'members':
				if(is_array($value))
					$value = json_encode($value);

				if(is_string($value))
					$out_fields[DAO_Group::_MEMBERS] = $value;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['buckets'] = [
			'label' => 'Buckets',
			'type' => 'Records',
		];
		
		$lazy_keys['default_bucket_'] = [
			'label' => 'Default Bucket',
			'type' => 'Record',
		];
		
		$lazy_keys['members'] = [
			'label' => 'Members',
			'type' => 'Records',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_GROUP;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'buckets':
				// [TODO] Can't $values clobber the lazy load above here?
				$values = $dictionary;

				if(!isset($values['buckets']))
					$values['buckets'] = array(
						'results_meta' => array(
							'labels' => [],
							'types' => [],
						),
						'results' => [],
					);
					
				$buckets = DAO_Bucket::getByGroup($context_id);
				
				if(is_array($buckets))
				foreach($buckets as $bucket) { /* @var $bucket Model_Bucket */
					$bucket_labels = [];
					$bucket_values = [];
					CerberusContexts::getContext(CerberusContexts::CONTEXT_BUCKET, $bucket, $bucket_labels, $bucket_values, null, true);
					
					// Results meta
					if(is_null($values['buckets']['results_meta']['labels']))
						$values['buckets']['results_meta']['labels'] = $bucket_values['_labels'];
					
					if(is_null($values['buckets']['results_meta']['types']))
						$values['buckets']['results_meta']['types'] = $bucket_values['_types'];
					
					// Remove redundancy
					$bucket_values = array_filter($bucket_values, function($values) use (&$bucket_values) {
						$key = key($bucket_values);
						next($bucket_values);
						
						switch($key) {
							case '_labels':
							case '_types':
								return false;
								
							default:
								if(preg_match('#^(.*)_loaded$#', $key))
									return false;
								break;
						}
						
						return true;
					});
					
					$values['buckets']['results'][] = $bucket_values;
				}
				break;
				
			case 'members':
				$values = $dictionary;
				
				if(!isset($values['members']))
					$values['members'] = [];
				
				$rosters = DAO_Group::getRosters();
				
				@$roster = $rosters[$context_id];
				
				if(!is_array($roster) || empty($roster))
					break;
				
				if(is_array($roster))
				foreach($roster as $member) { /* @var $member Model_GroupMember */
					$member_labels = [];
					$member_values = [];
					CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $member->id, $member_labels, $member_values, null, true);
					
					if(empty($member_values))
						continue;
					
					// Ignore disabled
					if(isset($member_values['is_disabled']) && $member_values['is_disabled'])
						continue;
					
					// Add a manager value
					$member_values['is_manager'] = $member->is_manager ? true : false;
					
					// Lazy load
					$member_dict = new DevblocksDictionaryDelegate($member_values);
					$member_dict->address_;
					$member_values = $member_dict->getDictionary(null, false);
					unset($member_dict);
					
					// Remove redundancy
					$member_values = array_filter($member_values, function($values) use (&$member_values) {
						$key = key($member_values);
						next($member_values);
						
						switch($key) {
							case '_labels':
							case '_types':
							case 'address_':
							case 'custom_':
								return false;
								
							default:
								if(preg_match('#^(.*)_loaded$#', $key))
									return false;
								break;
						}
						
						return true;
					});
					
					$values['members'][$member_values['id']] = $member_values;
				}
				break;
			
			default:
				if(DevblocksPlatform::strStartsWith($token, 'default_bucket_')) {
					if(false != ($bucket = DAO_Bucket::getDefaultForGroup($dictionary['id']))) {
						$values['default_bucket__context'] = CerberusContexts::CONTEXT_BUCKET;
						$values['default_bucket_id'] = $bucket->id;
					}
					
				} else {
					$defaults = $this->_lazyLoadDefaults($token, $dictionary);
					$values = array_merge($values, $defaults);
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
		$view->name = 'Groups';
		$view->addParams(array(
		), true);
//		$view->renderSortBy = SearchFields_Group::NAME;
//		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';

		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Groups';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Group::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$context = CerberusContexts::CONTEXT_GROUP;
		@$context_id = DevblocksPlatform::importVar($context_id,'integer',0);
		$group = null;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		if($context_id) {
			if(false == ($group = DAO_Group::get($context_id))) {
				$tpl->assign('error_message', DevblocksPlatform::translate('error.core.record.not_found'));
				$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
				DevblocksPlatform::dieWithHttpError(null, 404);
			}
			
			$tpl->assign('group', $group);
		}
		
		// Members
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		if(isset($group) && $group instanceof Model_Group && false != ($members = $group->getMembers()))
			$tpl->assign('members', $members);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext($context, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
		if(isset($custom_field_values[$context_id]))
			$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Delete destinations
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$destination_buckets = DAO_Bucket::getGroups();
		unset($destination_buckets[$context_id]);
		$tpl->assign('destination_buckets', $destination_buckets);
		
		// Settings
		
		if(false != ($group_settings = DAO_GroupSettings::getSettings($context_id)))
			$tpl->assign('group_settings', $group_settings);
		
		// Template
		
		if($edit) {
			// ACL check
			if(!($active_worker->is_superuser || $active_worker->isGroupManager($context_id))) {
				$tpl->assign('error_message', $translate->_('common.access_denied'));
				$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
				return;
			}
			
			$tpl->display('devblocks:cerberusweb.core::groups/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $group);
		}
	}
};

class Model_GroupMember {
	public $id;
	public $group_id;
	public $is_manager = 0;
};
