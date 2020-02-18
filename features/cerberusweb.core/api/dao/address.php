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

class DAO_Address extends Cerb_ORMHelper {
	const CONTACT_ID = 'contact_id';
	const CONTACT_ORG_ID = 'contact_org_id';
	const CREATED_AT = 'created_at';
	const EMAIL = 'email';
	const HOST = 'host';
	const ID = 'id';
	const IS_BANNED = 'is_banned';
	const IS_DEFUNCT = 'is_defunct';
	const MAIL_TRANSPORT_ID = 'mail_transport_id';
	const NUM_NONSPAM = 'num_nonspam';
	const NUM_SPAM = 'num_spam';
	const UPDATED = 'updated';
	const WORKER_ID = 'worker_id';
	
	const _CACHE_LOCAL_ADDRESSES = 'addresses_local';
	const _CACHE_WORKER_ADDRESSES = 'addresses_worker';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONTACT_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CONTACT, true))
			;
		$validation
			->addField(self::CONTACT_ORG_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ORG, true))
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::EMAIL)
			->string()
			->setUnique('DAO_Address')
			->setNotEmpty(true)
			->setRequired(true)
			->addValidator($validation->validators()->email())
			;
		$validation
			->addField(self::HOST)
			->string()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_BANNED)
			->bit()
			;
		$validation
			->addField(self::IS_DEFUNCT)
			->bit()
			;
		$validation
			->addField(self::MAIL_TRANSPORT_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MAIL_TRANSPORT, true))
			;
		$validation
			->addField(self::NUM_NONSPAM)
			->uint()
			->setEditable(false)
			;
		$validation
			->addField(self::NUM_SPAM)
			->uint()
			->setEditable(false)
			;
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		$validation
			->addField(self::WORKER_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER, true))
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
	
	/**
	 * Creates a new email address record.
	 *
	 * @param array $fields An array of fields=>values
	 * @return integer The new address ID
	 *
	 * DAO_Address::create(array(
	 *   DAO_Address::EMAIL => 'user@domain'
	 * ));
	 *
	 */
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO address () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		if(!isset($fields[DAO_Address::CREATED_AT]))
			$fields[DAO_Address::CREATED_AT] = time();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		if(isset($fields[self::EMAIL])) {
			$email = $fields[self::EMAIL];
			
			// We can only set the email address on one record
			if(count($ids) != 1)
				return NULL;
			
			@$addresses = imap_rfc822_parse_adrlist('<'.$email.'>', 'host');
			
			if(!is_array($addresses) || empty($addresses))
				return NULL;
			
			$address = array_shift($addresses);
			
			if(empty($address->host) || $address->host == 'host')
				return NULL;
			
			// Format the email address
			$full_address = trim(DevblocksPlatform::strLower($address->mailbox.'@'.$address->host));
			
			$id = $db->GetOneMaster(sprintf("SELECT id FROM address WHERE email = %s", $db->qstr($full_address)));
			
			// If an email address is a duplicate, we can only set it on the same record
			if($id && !in_array($id, $ids))
				return NULL;
			
			$fields[self::EMAIL] = $full_address;
			$fields[self::HOST] = substr($full_address, strpos($full_address, '@')+1);
		}
		
		// If we're setting a contact, make sure we also set their org if not provided
		if(array_key_exists(self::CONTACT_ID, $fields) && !array_key_exists(self::CONTACT_ORG_ID, $fields)) {
			if(false != ($contact = DAO_Contact::get($fields[self::CONTACT_ID]))) {
				$fields[self::CONTACT_ORG_ID] = $contact->org_id;
			}
		}
		
		if(!isset($fields[DAO_Address::UPDATED]))
			$fields[DAO_Address::UPDATED] = time();
		
		self::_updateAbstract(Context_Address::ID, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_ADDRESS, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'address', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.address.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_ADDRESS, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateForWorkerId($worker_id, array $email_ids=[]) {
		$db = DevblocksPlatform::services()->database();
		
		// Clear existing email IDs
		$db->ExecuteMaster(sprintf(
			"UPDATE address SET worker_id = 0 WHERE worker_id = %d",
			$worker_id
		));
		
		// Insert new email IDs
		if(false != ($email_ids = DevblocksPlatform::sanitizeArray($email_ids, 'int'))) {
			$db->ExecuteMaster(sprintf(
				"UPDATE address SET worker_id = %d, mail_transport_id = 0 WHERE id IN (%s)",
				$worker_id,
				implode(',', $email_ids)
			));
		}
		
		return true;
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('address', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_ADDRESS;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
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
				case 'org_id':
					$change_fields[DAO_Address::CONTACT_ORG_ID] = intval($v);
					break;
				case 'banned':
					$change_fields[DAO_Address::IS_BANNED] = intval($v);
					break;
				case 'defunct':
					$change_fields[DAO_Address::IS_DEFUNCT] = intval($v);
					break;
				case 'mail_transport_id':
					$change_fields[DAO_Address::WORKER_ID] = 0;
					$change_fields[DAO_Address::MAIL_TRANSPORT_ID] = intval($v);
					break;
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_ADDRESS, $ids);
	
		DAO_Address::update($ids, $change_fields, false);
		
		// Custom Fields
		C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_ADDRESS, $custom_fields, $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_ADDRESS, $do['behavior'], $ids);
		
		// Broadcast
		if(isset($do['broadcast']))
			C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_ADDRESS, $do['broadcast'], $ids);
		
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_ADDRESS, $ids);
		
		$update->markCompleted();
		return true;
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		$sql = "UPDATE address SET worker_id = 0 WHERE worker_id != 0 AND worker_id NOT IN (SELECT id FROM worker)";
		$db->ExecuteMaster($sql);
		$logger->info('[Maint] Corrected ' . $db->Affected_Rows() . ' missing workers on address records.');

		// Search indexes
		if(isset($tables['fulltext_address'])) {
			$db->ExecuteMaster("DELETE FROM fulltext_address WHERE id NOT IN (SELECT id FROM address)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_address records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_ADDRESS,
					'context_table' => 'address',
					'context_key' => 'id',
				)
			)
		);
	}
	
	static function mergeIds($from_ids, $to_id) {
		$db = DevblocksPlatform::services()->database();

		$context = CerberusContexts::CONTEXT_ADDRESS;
		
		if(empty($from_ids) || empty($to_id))
			return false;
			
		if(!is_numeric($to_id) || !is_array($from_ids))
			return false;
		
		self::_mergeIds($context, $from_ids, $to_id);
		
		// Merge bucket
		$db->ExecuteMaster(sprintf("UPDATE bucket SET reply_address_id = %d WHERE reply_address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge contact primary
		$db->ExecuteMaster(sprintf("UPDATE contact SET primary_email_id = %d WHERE primary_email_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge org primary
		$db->ExecuteMaster(sprintf("UPDATE contact_org SET email_id = %d WHERE email_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge crm_opportunity
		$db->ExecuteMaster(sprintf("UPDATE IGNORE crm_opportunity SET primary_email_id = %d WHERE primary_email_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge feedback_entry
		$db->ExecuteMaster(sprintf("UPDATE feedback_entry SET quote_address_id = %d WHERE quote_address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge message sender
		$db->ExecuteMaster(sprintf("UPDATE message SET address_id = %d WHERE address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge requester
		$db->ExecuteMaster(sprintf("UPDATE IGNORE requester SET address_id = %d WHERE address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		$db->ExecuteMaster(sprintf("DELETE FROM requester WHERE address_id IN (%s)",
			implode(',', $from_ids)
		));
		
		// Merge supportcenter_address_share
		$db->ExecuteMaster(sprintf("UPDATE IGNORE supportcenter_address_share SET share_address_id = %d WHERE share_address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		$db->ExecuteMaster(sprintf("UPDATE IGNORE supportcenter_address_share SET with_address_id = %d WHERE with_address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge ticket first wrote
		$db->ExecuteMaster(sprintf("UPDATE ticket SET first_wrote_address_id = %d WHERE first_wrote_address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge ticket last wrote
		$db->ExecuteMaster(sprintf("UPDATE ticket SET last_wrote_address_id = %d WHERE last_wrote_address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge worker
		$db->ExecuteMaster(sprintf("UPDATE worker SET email_id = %d WHERE email_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge worker_group
		$db->ExecuteMaster(sprintf("UPDATE worker_group SET reply_address_id = %d WHERE reply_address_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);

		if(empty($ids))
			return;

		$db = DevblocksPlatform::services()->database();
		
		$address_ids = implode(',', $ids);
		
		// Addresses
		$sql = sprintf("DELETE FROM address WHERE id IN (%s)", $address_ids);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
	
		// Clear search records
		$search = Extension_DevblocksSearchSchema::get(Search_Address::ID);
		$search->delete($ids);
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_ADDRESS,
					'context_ids' => $ids
				)
			)
		);

		self::clearCache();
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_LOCAL_ADDRESSES);
		$cache->remove(self::_CACHE_WORKER_ADDRESSES);
	}
	
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, email, host, contact_id, contact_org_id, mail_transport_id, worker_id, num_spam, num_nonspam, is_banned, is_defunct, created_at, updated ".
			"FROM address ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);

		$objects = self::_getObjectsFromResult($rs);

		return $objects;
	}

	/**
	 * @param resource $rs
	 * @return Model_Address[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Address();
			$object->id = intval($row['id']);
			$object->email = $row['email'];
			$object->host = $row['host'];
			$object->contact_id = intval($row['contact_id']);
			$object->contact_org_id = intval($row['contact_org_id']);
			$object->created_at = intval($row['created_at']);
			$object->mail_transport_id = intval($row['mail_transport_id']);
			$object->num_spam = intval($row['num_spam']);
			$object->num_nonspam = intval($row['num_nonspam']);
			$object->is_banned = intval($row['is_banned']);
			$object->is_defunct = intval($row['is_defunct']);
			$object->updated = intval($row['updated']);
			$object->worker_id = intval($row['worker_id']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * @return Model_Address|null
	 */
	static function getByEmail($email) {
		$db = DevblocksPlatform::services()->database();
		
		$results = self::getWhere(sprintf("%s = %s",
			self::EMAIL,
			$db->qstr(DevblocksPlatform::strLower($email))
		));

		if(!empty($results))
			return array_shift($results);
			
		return NULL;
	}
	
	/**
	 * @return Model_Address[]
	 */
	static function getByEmails(array $emails) {
		$db = DevblocksPlatform::services()->database();
		
		$in_emails = implode(',', $db->qstrArray(array_map(function($email) {
			return DevblocksPlatform::strLower($email);
		}, $emails)));
		
		$results = self::getWhere(sprintf("%s IN (%s)",
			self::EMAIL,
			$in_emails
		));
		
		return $results;
	}
	
	static function getAllWithWorker() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null == ($results = $cache->load(self::_CACHE_WORKER_ADDRESSES))) {
			$results = self::getWhere(
				sprintf("%s > 0",
					self::WORKER_ID
				),
				self::EMAIL,
				true
			);
			
			$cache->save($results, self::_CACHE_WORKER_ADDRESSES);
		}
		
		return $results;
	}
	
	static function getByWorkerId($worker_id) {
		if(empty($worker_id))
			return [];
		
		$results = self::getWhere(
			sprintf("%s = %d",
				self::WORKER_ID,
				$worker_id
			),
			self::EMAIL,
			true
		);
		
		return $results;
	}
	
	static function getByWorkers() {
		$addys = self::getAllWithWorker();
		$workers = DAO_Worker::getAll();
		
		array_walk($addys, function($addy) use ($workers) {
			if(!isset($workers[$addy->worker_id]))
				return;
			
			if(!isset($workers[$addy->worker_id]->relay_emails))
				$workers[$addy->worker_id]->relay_emails = [];
				
			$workers[$addy->worker_id]->relay_emails[] = $addy->id;
		});
		
		return $workers;
	}
	
	static function countByTicketId($ticket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(address_id) FROM requester WHERE ticket_id = %d",
			$ticket_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function countByTransportId($transport_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM address WHERE mail_transport_id = %d",
			$transport_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function countByWorkerId($worker_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM address WHERE worker_id = %d",
			$worker_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function countByContactId($org_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM address WHERE contact_id = %d",
			$org_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function countByOrgId($org_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM address WHERE contact_org_id = %d",
			$org_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_Address
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$addresses = DAO_Address::getWhere(
			sprintf("%s = %d",
				self::ID,
				$id
		));
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;
	}
	
	static function getLocalAddresses() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null == ($sender_addresses = $cache->load(self::_CACHE_LOCAL_ADDRESSES))) {
			$sender_addresses = self::getWhere("mail_transport_id > 0");
			$cache->save($sender_addresses, self::_CACHE_LOCAL_ADDRESSES);
		}
		
		return $sender_addresses;
	}
	
	/**
	 * 
	 * @return Model_Address[]
	 */
	static function getDefaultLocalAddress() {
		$sender_addresses = self::getLocalAddresses();
		$address_id = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_DEFAULT_FROM_ID, 0);
		
		if(isset($sender_addresses[$address_id]))
			return $sender_addresses[$address_id];
		
		return null;
	}
	
	static function isLocalAddress($address) {
		$sender_addresses = self::getLocalAddresses();
		foreach($sender_addresses as $from) {
			if(0 == strcasecmp($from->email, $address))
				return true;
		}
		
		return false;
	}
	
	static function isLocalAddressId($id) {
		$sender_addresses = self::getLocalAddresses();
		foreach(array_keys($sender_addresses) as $from_id) {
			if(intval($from_id)==intval($id))
				return true;
		}
		
		return false;
	}
	
	/**
	 *
	 * @param string $email
	 * @param boolean $create_if_null
	 * @return Model_Address
	 */
	static function lookupAddress($email, $create_if_null=false) {
		$address = null;
		
		$email = trim(mb_convert_case($email, MB_CASE_LOWER));
		
		// Make sure this a valid, normalized, and properly formatted email address
		
		$results = CerberusMail::parseRfcAddresses($email);
		
		if(!is_array($results) || false == ($email_data = array_shift($results)) || !is_array($email_data))
			return false;
		
		if(!isset($email_data['email']))
			return false;
		
		if($address = DAO_Address::getByEmail($email_data['email'])) {
			// This is what we want
			
		} elseif($create_if_null) {
			$fields = array(
				self::EMAIL => $email_data['email']
			);
			
			if(false == ($id = DAO_Address::create($fields)))
				return false;
			
			$address = DAO_Address::get($id);
		}
		
		return $address;
	}
	
	/**
	 * @param array $emails
	 * @param boolean $create_if_null
	 * @return Model_Address[]
	 */
	static function lookupAddresses(array $emails, $create_if_null=false) {
		$addresses = [];
		
		if(is_array($emails))
		foreach($emails as $email) {
			if(false != ($address = DAO_Address::lookupAddress($email, $create_if_null))) {
				$addresses[$address->id] = $address;
			}
		}
		
		return $addresses;
	}
	
	static function addOneToSpamTotal($address_id) {
		$db = DevblocksPlatform::services()->database();
		$sql = sprintf("UPDATE address SET num_spam = num_spam + 1 WHERE id = %d",$address_id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
	}
	
	static function addOneToNonSpamTotal($address_id) {
		$db = DevblocksPlatform::services()->database();
		$sql = sprintf("UPDATE address SET num_nonspam = num_nonspam + 1 WHERE id = %d",$address_id);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
	}
	
	public static function random() {
		return self::_getRandom('address');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Address::getFields();
		
		if(is_string($sortBy))
		switch($sortBy) {
			case SearchFields_Address::ORG_NAME:
				$sortBy = SearchFields_Address::CONTACT_ORG_ID;
				break;
		}
		
		list(, $wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Address', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.email as %s, ".
			"a.host as %s, ".
			"a.contact_id as %s, ".
			"a.contact_org_id as %s, ".
			"a.created_at as %s, ".
			"a.mail_transport_id as %s, ".
			"a.worker_id as %s, ".
			"a.num_spam as %s, ".
			"a.num_nonspam as %s, ".
			"a.is_banned as %s, ".
			"a.is_defunct as %s, ".
			"a.updated as %s ",
				SearchFields_Address::ID,
				SearchFields_Address::EMAIL,
				SearchFields_Address::HOST,
				SearchFields_Address::CONTACT_ID,
				SearchFields_Address::CONTACT_ORG_ID,
				SearchFields_Address::CREATED_AT,
				SearchFields_Address::MAIL_TRANSPORT_ID,
				SearchFields_Address::WORKER_ID,
				SearchFields_Address::NUM_SPAM,
				SearchFields_Address::NUM_NONSPAM,
				SearchFields_Address::IS_BANNED,
				SearchFields_Address::IS_DEFUNCT,
				SearchFields_Address::UPDATED
			);
		
		$join_sql = "FROM address a ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Address');
		
		$result = array(
			'primary_table' => 'a',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function autocomplete($term, $as='models', $query=null) {
		$context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_ADDRESS);
		
		$view = $context_ext->getSearchView('autocomplete_address');
		$view->is_ephemeral = true;
		$view->renderPage = 0;
		$view->addParamsWithQuickSearch($query, true);
		
		// If we have a special email character then switch to literal email matching
		if(preg_match('/[\.\@\_]/', $term)) {
			// If a leading '@', then prefix/trailing wildcard
			if(DevblocksPlatform::strStartsWith($term, '@')) {
				$q = '*' . $term . '*';
			// Otherwise, only suffix wildcard
			} else {
				$q = $term . '*';
			}
			
			$params = [
				SearchFields_Address::EMAIL => new DevblocksSearchCriteria(SearchFields_Address::EMAIL, DevblocksSearchCriteria::OPER_LIKE, $q),
				SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED, DevblocksSearchCriteria::OPER_EQ, 0),
				SearchFields_Address::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_Address::IS_DEFUNCT, DevblocksSearchCriteria::OPER_EQ, 0),
			];
			
			$view->addParams($params);
			
		// Does it start with a number?
		} else if (is_numeric(substr($term,0,1))) {
			$params = [
				SearchFields_Address::EMAIL => new DevblocksSearchCriteria(SearchFields_Address::EMAIL, DevblocksSearchCriteria::OPER_LIKE, $term.'*'),
				SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED, DevblocksSearchCriteria::OPER_EQ, 0),
				SearchFields_Address::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_Address::IS_DEFUNCT, DevblocksSearchCriteria::OPER_EQ, 0),
			];
			
			$view->addParams($params);
			
		// Otherwise, use fulltext
		} else {
			$params = [
				SearchFields_Address::FULLTEXT_ADDRESS => new DevblocksSearchCriteria(SearchFields_Address::FULLTEXT_ADDRESS, DevblocksSearchCriteria::OPER_FULLTEXT, $term.'*'),
				SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED, DevblocksSearchCriteria::OPER_EQ, 0),
				SearchFields_Address::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_Address::IS_DEFUNCT, DevblocksSearchCriteria::OPER_EQ, 0),
			];
			
			$view->addParams($params);
		}
		
		$view->renderLimit = 25;
		$view->renderSortBy = SearchFields_Address::NUM_NONSPAM;
		$view->renderSortAsc = false;
		$view->renderTotal = false;
		
		list($results,) = $view->getData();
		
		switch($as) {
			case 'ids':
				return array_keys($results);
				break;
				
			default:
				return DAO_Address::getIds(array_keys($results));
				break;
		}
	}
	
	/**
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
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_Address::ID]);
			$results[$id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(*) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_Address extends DevblocksSearchFields {
	// Address
	const ID = 'a_id';
	const EMAIL = 'a_email';
	const HOST = 'a_host';
	const CONTACT_ID = 'a_contact_id';
	const CONTACT_ORG_ID = 'a_contact_org_id';
	const CREATED_AT = 'a_created_at';
	const MAIL_TRANSPORT_ID = 'a_mail_transport_id';
	const NUM_SPAM = 'a_num_spam';
	const NUM_NONSPAM = 'a_num_nonspam';
	const IS_BANNED = 'a_is_banned';
	const IS_DEFUNCT = 'a_is_defunct';
	const UPDATED = 'a_updated';
	const WORKER_ID = 'a_worker_id';
	
	const ORG_NAME = 'o_name';

	// Fulltexts
	const FULLTEXT_ADDRESS = 'ft_address';
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';

	// Virtuals
	const VIRTUAL_CONTACT_SEARCH = '*_contact_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_ORG_SEARCH = '*_org_search';
	const VIRTUAL_TICKET_ID = '*_ticket_id';
	const VIRTUAL_TICKET_SEARCH = '*_ticket_search';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'a.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('a.id', self::ID),
			CerberusContexts::CONTEXT_CONTACT => new DevblocksSearchFieldContextKeys('a.contact_id', self::CONTACT_ID),
			CerberusContexts::CONTEXT_ORG => new DevblocksSearchFieldContextKeys('a.contact_org_id', self::CONTACT_ORG_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_TICKET_ID:
				if(!is_array($param->value))
					break;
				
				$ids = DevblocksPlatform::sanitizeArray($param->value, 'integer');
				
				return sprintf("%s IN (SELECT address_id FROM requester r WHERE r.ticket_id IN (%s))",
					self::getPrimaryKey(),
					implode(',', $ids)
				);
				break;
				
			case self::FULLTEXT_ADDRESS:
				return self::_getWhereSQLFromFulltextField($param, Search_Address::ID, self::getPrimaryKey());
				break;
				
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_ADDRESS, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTACT_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CONTACT, 'a.contact_id');
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_ADDRESS, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_ADDRESS)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_ORG_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ORG, 'a.contact_org_id');
				break;
				
			case self::VIRTUAL_TICKET_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_TICKET, "SELECT address_id FROM requester r WHERE r.ticket_id IN (%s)", 'a.id');
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_ADDRESS, self::getPrimaryKey());
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
		
		return null;
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'contact':
				$key = 'contact.id';
				break;
				
			case 'mailtransport':
			case 'mailTransport':
				$key = 'mailTransport.id';
				break;
				
			case 'org':
				$key = 'org.id';
				break;
				
			case 'worker':
				$key = 'worker.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Address::ID:
				$models = DAO_Address::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'email', 'id');
				break;
				
			case SearchFields_Address::CONTACT_ID:
				$models = DAO_Contact::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_CONTACT);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				break;
				
			case SearchFields_Address::CONTACT_ORG_ID:
				$models = DAO_ContactOrg::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Address::MAIL_TRANSPORT_ID:
				$models = DAO_MailTransport::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Address::WORKER_ID:
				$models = DAO_Worker::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
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
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::EMAIL => new DevblocksSearchField(self::EMAIL, 'a', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::HOST => new DevblocksSearchField(self::HOST, 'a', 'host', $translate->_('common.host'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CONTACT_ID => new DevblocksSearchField(self::CONTACT_ID, 'a', 'contact_id', $translate->_('common.contact'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'a', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::MAIL_TRANSPORT_ID => new DevblocksSearchField(self::MAIL_TRANSPORT_ID, 'a', 'mail_transport_id', $translate->_('common.email_transport'), Model_CustomField::TYPE_NUMBER, true),
			self::NUM_SPAM => new DevblocksSearchField(self::NUM_SPAM, 'a', 'num_spam', $translate->_('address.num_spam'), Model_CustomField::TYPE_NUMBER, true),
			self::NUM_NONSPAM => new DevblocksSearchField(self::NUM_NONSPAM, 'a', 'num_nonspam', $translate->_('address.num_nonspam'), Model_CustomField::TYPE_NUMBER, true),
			self::IS_BANNED => new DevblocksSearchField(self::IS_BANNED, 'a', 'is_banned', $translate->_('address.is_banned'), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_DEFUNCT => new DevblocksSearchField(self::IS_DEFUNCT, 'a', 'is_defunct', $translate->_('address.is_defunct'), Model_CustomField::TYPE_CHECKBOX, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'a', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'a', 'worker_id', $translate->_('common.worker'), Model_CustomField::TYPE_NUMBER, true),
			
			self::CONTACT_ORG_ID => new DevblocksSearchField(self::CONTACT_ORG_ID, 'a', 'contact_org_id', $translate->_('common.organization') . ' ' . $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', $translate->_('common.organization'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			self::FULLTEXT_ADDRESS => new DevblocksSearchField(self::FULLTEXT_ADDRESS, 'ft', 'address', $translate->_('common.search.fulltext'), 'FT', false),
			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_CONTACT_SEARCH => new DevblocksSearchField(self::VIRTUAL_CONTACT_SEARCH, '*', 'contact_search', null, null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_ORG_SEARCH => new DevblocksSearchField(self::VIRTUAL_ORG_SEARCH, '*', 'org_search', null, null, false),
			self::VIRTUAL_TICKET_ID => new DevblocksSearchField(self::VIRTUAL_TICKET_ID, '*', 'ticket_id', $translate->_('common.ticket'), null, false),
			self::VIRTUAL_TICKET_SEARCH => new DevblocksSearchField(self::VIRTUAL_TICKET_SEARCH, '*', 'ticket_search', null, null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_ADDRESS]->ft_schema = Search_Address::ID;
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Search_Address extends Extension_DevblocksSearchSchema {
	const ID = 'cerb.search.schema.address';
	
	public function getNamespace() {
		return 'address';
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
		$logger = DevblocksPlatform::services()->log();

		$id = $dict->id;
		
		if(empty($id))
			return false;
		
		$doc = array(
			'content' => implode("\n", array(
				$dict->address,
				$dict->contact__label,
				$dict->org__label,
			))
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
		
		if(false == ($models = DAO_Address::getIds($ids)))
			return;
		
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ADDRESS, array('contact_','org_'));
		
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
				DAO_Address::UPDATED,
				$ptr_time,
				DAO_Address::ID,
				$id
			);
			$models = DAO_Address::getWhere($where, array(DAO_Address::UPDATED, DAO_Address::ID), array(true, true), 100);

			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ADDRESS, array('contact_','org_name'));
			
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

class Model_Address {
	public $id;
	public $contact_id = 0;
	public $contact_org_id = 0;
	public $created_at = 0;
	public $email = '';
	public $host = '';
	public $is_banned = 0;
	public $is_defunct = 0;
	public $mail_transport_id = 0;
	public $num_nonspam = 0;
	public $num_spam = 0;
	public $updated = 0;
	public $worker_id = 0;
	
	private $_contact_model = null;
	private $_org_model = null;

	function __get($name) {
		switch($name) {
			// [DEPRECATED] Added in 7.1
			case 'first_name':
				if(false == ($contact = $this->getContact()))
					return '';
				
				//error_log("The 'first_name' field on address records is deprecated. Use contacts instead.", E_USER_DEPRECATED);
				
				return $contact->first_name;
				break;
				
			// [DEPRECATED] Added in 7.1
			case 'last_name':
				if(false == ($contact = $this->getContact()))
					return '';
				
				//error_log("The 'last_name' field on address records is deprecated. Use contacts instead.", E_USER_DEPRECATED);
				
				return $contact->last_name;
				break;
		}
	}
	
	function getName() {
		if(false == ($contact = $this->getContact()))
			return '';
		
		return $contact->getName();
	}
	
	function getNameWithEmail() {
		$name = $this->getName();
		
		if(!empty($name))
			$name .= ' <' . $this->email . '>';
		else
			$name = $this->email;
		
		return $name;
	}
	
	function getContact() {
		if(is_null($this->_contact_model))
			$this->_contact_model = DAO_Contact::get($this->contact_id);
		
		return $this->_contact_model;
	}
	
	function getOrg() {
		if(is_null($this->_org_model))
			$this->_org_model = DAO_ContactOrg::get($this->contact_org_id);
		
		return $this->_org_model;
	}
	
	function getMailTransport() {
		if($this->mail_transport_id && false != ($transport = DAO_MailTransport::get($this->mail_transport_id)))
			return $transport;
		
		return null;
	}
	
	function getWorker() {
		if($this->worker_id && false != ($worker = DAO_Worker::get($this->worker_id)))
			return $worker;
		
		return null;
	}
};

class View_Address extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'addresses';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('addy_book.tab.addresses');
		$this->renderLimit = 10;
		$this->renderSortBy = 'a_email';
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Address::CONTACT_ID,
			SearchFields_Address::ORG_NAME,
			SearchFields_Address::NUM_NONSPAM,
			SearchFields_Address::NUM_SPAM,
			SearchFields_Address::MAIL_TRANSPORT_ID,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Address::CONTACT_ORG_ID,
			SearchFields_Address::FULLTEXT_ADDRESS,
			SearchFields_Address::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Address::VIRTUAL_CONTACT_SEARCH,
			SearchFields_Address::VIRTUAL_CONTEXT_LINK,
			SearchFields_Address::VIRTUAL_HAS_FIELDSET,
			SearchFields_Address::VIRTUAL_ORG_SEARCH,
			SearchFields_Address::VIRTUAL_TICKET_ID,
			SearchFields_Address::VIRTUAL_TICKET_SEARCH,
			SearchFields_Address::VIRTUAL_WATCHERS,
		));
	}

	function getData() {
		$objects = DAO_Address::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Address');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Address', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Address', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Address::HOST:
				case SearchFields_Address::IS_BANNED:
				case SearchFields_Address::IS_DEFUNCT:
				case SearchFields_Address::CONTACT_ID:
				case SearchFields_Address::CONTACT_ORG_ID:
				case SearchFields_Address::MAIL_TRANSPORT_ID:
				case SearchFields_Address::WORKER_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Address::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Address::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Address::VIRTUAL_WATCHERS:
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
		$counts = array();
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_ADDRESS;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Address::IS_BANNED:
			case SearchFields_Address::IS_DEFUNCT:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
				
			case SearchFields_Address::HOST:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Address::CONTACT_ID:
				$label_map = function($ids) {
					$models = DAO_Contact::getIds($ids);
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_CONTACT);
					return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Address::CONTACT_ID, $label_map, '=', 'value[]');
				break;
				
			case SearchFields_Address::CONTACT_ORG_ID:
				$label_map = function($ids) {
					$rows = DAO_ContactOrg::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'name', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Address::CONTACT_ORG_ID, $label_map, '=', 'value[]');
				break;
				
			case SearchFields_Address::MAIL_TRANSPORT_ID:
				$mail_transports = DAO_MailTransport::getAll();
				$label_map = array_column($mail_transports, 'name', 'id');
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Address::MAIL_TRANSPORT_ID, $label_map, '=', 'value');
				break;
				
			case SearchFields_Address::WORKER_ID:
				$label_map = function($ids) {
					return array_map(function($worker) {
						return $worker->getName();
					}, DAO_Worker::getIds($ids));
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Address::WORKER_ID, $label_map, '=', 'value');
				break;
				
			// Virtuals
			
			case SearchFields_Address::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Address::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Address::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Address::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Address::FULLTEXT_ADDRESS),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Address::FULLTEXT_COMMENT_CONTENT),
				),
			'contact' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Address::VIRTUAL_CONTACT_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CONTACT, 'q' => ''],
					]
				),
			'contact.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Address::CONTACT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CONTACT, 'q' => ''],
					]
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Address::CREATED_AT),
				),
			'email' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Address::EMAIL),
					'score' => 2000,
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:address by:email~25 query:(email:{{term}}*) format:dictionaries',
						'key' => 'email',
						'limit' => 25,
						'min_length' => 1,
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Address::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_ADDRESS],
					]
				),
			'host' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Address::HOST),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:address by:host~25 query:(host:{{term}}*) format:dictionaries',
						'key' => 'host',
						'limit' => 25,
						'min_length' => 1,
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Address::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'isBanned' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Address::IS_BANNED),
				),
			'isDefunct' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Address::IS_DEFUNCT),
				),
			'mailTransport.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Address::MAIL_TRANSPORT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MAIL_TRANSPORT, 'q' => ''],
					]
				),
			'nonspam' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Address::NUM_NONSPAM),
				),
			'org' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Address::VIRTUAL_ORG_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ORG, 'q' => ''],
					]
				),
			'org.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Address::CONTACT_ORG_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ORG, 'q' => ''],
					]
				),
			'spam' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Address::NUM_SPAM),
				),
			'ticket' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Address::VIRTUAL_TICKET_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				),
			'ticket.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Address::VIRTUAL_TICKET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Address::UPDATED),
				),
			'worker.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Address::WORKER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Address::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Address::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ADDRESS, $fields, null);
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ORG, $fields, 'org');
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_Address::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['text']['examples'] = $ft_examples;
		
		// Engine/schema examples: Comments
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['comments']['examples'] = $ft_examples;
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'contact':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Address::VIRTUAL_CONTACT_SEARCH);
				break;
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
				
			case 'org':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Address::VIRTUAL_ORG_SEARCH);
				break;
				
			case 'ticket':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Address::VIRTUAL_TICKET_SEARCH);
				break;
			
			case 'ticket.id':
				$field_key = SearchFields_Address::VIRTUAL_TICKET_ID;
				$oper = null;
				$value = null;
				
				if(false == CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value))
					return false;
				
				$value = DevblocksPlatform::sanitizeArray($value, 'int');
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					$value
				);
				break;
				
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Address::VIRTUAL_WATCHERS, $tokens);
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

		$custom_fields =
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS) +
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG)
			;
		$tpl->assign('custom_fields', $custom_fields);
		
		$mail_transports = DAO_MailTransport::getAll();
		$tpl->assign('mail_transports', $mail_transports);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::contacts/addresses/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
		$tpl->clearAssign('custom_fields');
		$tpl->clearAssign('id');
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Address::VIRTUAL_CONTACT_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.contact')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_Address::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Address::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Address::VIRTUAL_ORG_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.organization')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_Address::VIRTUAL_TICKET_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.ticket')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_Address::VIRTUAL_TICKET_ID:
				echo sprintf("%s on %s <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.participant')),
					1 == count($param->value) ? 'ticket' : 'tickets',
					DevblocksPlatform::strEscapeHtml(implode(' or ', $param->value))
				);
				break;
			
			case SearchFields_Address::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Address::IS_BANNED:
			case SearchFields_Address::IS_DEFUNCT:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_Address::CONTACT_ID:
				$label_map = SearchFields_Address::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Address::CONTACT_ORG_ID:
				$label_map = SearchFields_Address::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
			
			case SearchFields_Address::MAIL_TRANSPORT_ID:
				$label_map = SearchFields_Address::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Address::WORKER_ID:
				$label_map = SearchFields_Address::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Address::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::HOST:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Address::MAIL_TRANSPORT_ID:
			case SearchFields_Address::NUM_NONSPAM:
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::WORKER_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Address::CONTACT_ID:
			case SearchFields_Address::CONTACT_ORG_ID:
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$value);
				break;
				
			case SearchFields_Address::IS_BANNED:
			case SearchFields_Address::IS_DEFUNCT:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Address::CREATED_AT:
			case SearchFields_Address::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Address::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Address::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Address::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Address::FULLTEXT_ADDRESS:
			case SearchFields_Address::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_POST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
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

class Context_Address extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport, IDevblocksContextBroadcast, IDevblocksContextMerge, IDevblocksContextAutocomplete {
	const ID = 'cerberusweb.contexts.address';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	// Email addresses can't be deleted through normal means
	static function isDeleteableByActor($models, $actor) {
		return false;
	}
	
	function getRandom() {
		return DAO_Address::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=address&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Address();
		
		$properties['_label'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
		);
		
		$properties['contact'] = array(
			'label' => mb_ucfirst($translate->_('common.contact')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->contact_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_CONTACT,
			),
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['is_banned'] = array(
			'label' => mb_ucfirst($translate->_('address.is_banned')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_banned,
		);
		
		$properties['is_defunct'] = array(
			'label' => mb_ucfirst($translate->_('address.is_defunct')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_defunct,
		);
		
		$properties['mail_transport_id'] = array(
			'label' => mb_ucfirst($translate->_('common.email_transport')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->mail_transport_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_MAIL_TRANSPORT,
			),
		);
		
		$properties['org'] = array(
			'label' => mb_ucfirst($translate->_('common.organization')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->contact_org_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_ORG,
			),
		);
		
		$properties['num_nonspam'] = array(
			'label' => mb_ucfirst($translate->_('address.num_nonspam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->num_nonspam,
		);
		
		$properties['num_spam'] = array(
			'label' => mb_ucfirst($translate->_('address.num_spam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->num_spam,
		);
		
		$properties['updated_at'] = array(
			'label' => mb_ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($address = DAO_Address::get($context_id)))
			return array();
		
		$addy_name = $address->getNameWithEmail();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($address->email);

		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $address->id,
			'name' => $addy_name,
			'permalink' => $url,
			'updated' => $address->updated,
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
			'org__label',
			'contact__label',
			'is_banned',
			'is_defunct',
			'num_nonspam',
			'num_spam',
			'mail_transport__label',
			'worker__label',
			'created',
			'updated',
		];
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		
		$models = DAO_Address::autocomplete($term, 'models', $query);
		$list = [];
		
		if(stristr('none', $term) || stristr('empty', $term) || stristr('null', $term)) {
			$empty = new stdClass();
			$empty->label = '(no email address)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the email address');
			$list[] = $empty;
		}
	
		// Efficiently load all of the referenced orgs in one query
		$orgs = DAO_ContactOrg::getIds(DevblocksPlatform::extractArrayValues($models, 'contact_org_id'));

		if(is_array($models))
		foreach($models as $model) {
			$entry = new stdClass();
			$entry->label = $model->email;
			$entry->value = $model->id;
			$entry->icon = $url_writer->write('c=avatars&type=address&id=' . $model->id, true) . '?v=' . $model->updated;
			
			$meta = [];
			
			if(false != ($full_name = $model->getName()))
				$meta['full_name'] = $full_name;
			
			if($model->contact_org_id && isset($orgs[$model->contact_org_id])) {
				$org = $orgs[$model->contact_org_id]; /* @var $org Model_ContactOrg */
				$meta['org'] = $org->name;
			}

			$entry->meta = $meta;
			
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($address, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Email:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		
		// Polymorph
		if(is_numeric($address)) {
			$address = DAO_Address::get($address);
			
		} elseif(is_array($address)) {
			$address = Cerb_ORMHelper::recastArrayToModel($address, 'Model_Address');
			
		} elseif($address instanceof Model_Address) {
			// It's what we want already.
			
		} elseif(is_string($address)) {
			$address = DAO_Address::getByEmail($address);
			
		} else {
			$address = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'address' => $prefix.$translate->_('address.address'),
			'created_at' => $prefix.$translate->_('common.created'),
			'full_name' => $prefix.$translate->_('common.name.full'),
			'host' => $prefix.$translate->_('common.host'),
			'is_banned' => $prefix.$translate->_('address.is_banned'),
			'is_contact' => $prefix.$translate->_('address.is_contact'),
			'is_defunct' => $prefix.$translate->_('address.is_defunct'),
			'num_spam' => $prefix.$translate->_('address.num_spam'),
			'num_nonspam' => $prefix.$translate->_('address.num_nonspam'),
			'updated' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'address' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'host' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'is_contact' => Model_CustomField::TYPE_CHECKBOX,
			'is_defunct' => Model_CustomField::TYPE_CHECKBOX,
			'num_spam' => Model_CustomField::TYPE_NUMBER,
			'num_nonspam' => Model_CustomField::TYPE_NUMBER,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_ADDRESS;
		$token_values['_types'] = $token_types;

		// Address token values
		if(null != $address) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $address->getNameWithEmail();
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'address', $address->id), true) . '?v=' . $address->updated;
			$token_values['id'] = $address->id;
			$token_values['address'] = $address->email;
			$token_values['created_at'] = $address->created_at;
			$token_values['email'] = $address->email;
			$token_values['host'] = $address->host;
			$token_values['num_spam'] = $address->num_spam;
			$token_values['num_nonspam'] = $address->num_nonspam;
			$token_values['is_banned'] = $address->is_banned;
			$token_values['is_contact'] = !empty($address->contact_id);
			$token_values['is_defunct'] = $address->is_defunct;
			$token_values['updated'] = $address->updated;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($address, $token_values);
			
			// URL
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=address&id=%d-%s",$address->id, DevblocksPlatform::strToPermalink($address->email)), true);
			
			// Contact
			$token_values['contact_id'] = $address->contact_id;
			
			// Org
			$org_id = (null != $address && !empty($address->contact_org_id)) ? $address->contact_org_id : null;
			$token_values['org_id'] = $org_id;
			
			// Transport
			$token_values['mail_transport_id'] = $address->mail_transport_id;
			
			// Worker
			$token_values['worker_id'] = $address->worker_id;
		}
		
		$context_stack = CerberusContexts::getStack();
		
		// Email Contact
		// Only link contact placeholders if the address isn't nested under a contact already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_CONTACT, $context_stack)) {
			$merge_token_labels = array();
			$merge_token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_CONTACT, null, $merge_token_labels, $merge_token_values, null, true);
	
			CerberusContexts::merge(
				'contact_',
				$prefix,
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		}
		
		// Email Org
		// Only link org placeholders if the org isn't nested under a contact already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_CONTACT, $context_stack)) {
			$merge_token_labels = [];
			$merge_token_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, null, $merge_token_labels, $merge_token_values, null, true);
	
			CerberusContexts::merge(
				'org_',
				$prefix,
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		}
		
		// Email Transport
		// Only link org placeholders if the org isn't nested under a contact already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_ADDRESS, $context_stack)) {
			$merge_token_labels = [];
			$merge_token_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_MAIL_TRANSPORT, null, $merge_token_labels, $merge_token_values, null, true);
	
			CerberusContexts::merge(
				'mail_transport_',
				$prefix,
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		}
		
		// Worker
		// Only link worker placeholders if the worker isn't nested under an address already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_ADDRESS, $context_stack)) {
			$merge_token_labels = [];
			$merge_token_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, null, true);
	
			CerberusContexts::merge(
				'worker_',
				$prefix,
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'contact_id' => DAO_Address::CONTACT_ID,
			'created_at' => DAO_Address::CREATED_AT,
			'email' => DAO_Address::EMAIL,
			'host' => DAO_Address::HOST,
			'id' => DAO_Address::ID,
			'is_banned' => DAO_Address::IS_BANNED,
			'is_defunct' => DAO_Address::IS_DEFUNCT,
			'links' => '_links',
			'mail_transport_id' => DAO_Address::MAIL_TRANSPORT_ID,
			'num_nonspam' => DAO_Address::NUM_NONSPAM,
			'num_spam' => DAO_Address::NUM_SPAM,
			'org_id' => DAO_Address::CONTACT_ORG_ID,
			'updated' => DAO_Address::UPDATED,
			'worker_id' => DAO_Address::WORKER_ID,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['contact_id']['notes'] = "The [contact](/docs/records/types/contact/) linked to this email";
		$keys['email']['notes'] = "An email address";
		$keys['host']['notes'] = "The hostname of the email address";
		$keys['is_banned']['notes'] = "Is incoming email blocked?";
		$keys['is_defunct']['notes'] = "Is this address non-deliverable?";
		$keys['mail_transport_id']['notes'] = "If this address is used for outgoing mail, the [mail transport](/docs/records/types/mail_transport/) to use; otherwise empty";
		$keys['org_id']['notes'] = "The [organization](/docs/records/types/org/) linked to this email";
		$keys['worker_id']['notes'] = "Is this address owned by a [worker](/docs/records/types/worker/)?";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['last_recipient_message'] = [
			'label' => 'Latest Message Received',
			'type' => 'Record',
		];
		
		$lazy_keys['last_sender_message'] = [
			'label' => 'Latest Message Sent',
			'type' => 'Record',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_ADDRESS;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			// Deprecated
			case 'first_name':
				$dict = DevblocksDictionaryDelegate::instance($dictionary);
				$values['first_name'] = $dict->contact_first_name;
				break;
				
			// Deprecated
			case 'full_name':
				$dict = DevblocksDictionaryDelegate::instance($dictionary);
				$values['full_name'] = $dict->contact_name;
				break;
				
			// Deprecated
			case 'last_name':
				$dict = DevblocksDictionaryDelegate::instance($dictionary);
				$values['last_name'] = $dict->contact_last_name;
				break;
				
			default:
				if($token == 'last_recipient_message' || DevblocksPlatform::strStartsWith($token, 'last_recipient_message_')) {
					$values['last_recipient_message__context'] = CerberusContexts::CONTEXT_MESSAGE;
					$values['last_recipient_message_id'] = intval(DAO_Message::getLatestIdByRecipientId($dictionary['id']));
					
				} else if($token == 'last_sender_message' || DevblocksPlatform::strStartsWith($token, 'last_sender_message_')) {
					$values['last_sender_message__context'] = CerberusContexts::CONTEXT_MESSAGE;
					$values['last_sender_message_id'] = intval(DAO_Message::getLatestIdBySenderId($dictionary['id']));
					
				} else {
					$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
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
		$view->name = 'Addresses';
		
		$view->addParamsDefault(array(
			SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED,'=',0),
			SearchFields_Address::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_Address::IS_DEFUNCT,'=',0),
		), true);
		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_Address::EMAIL;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Email Addresses';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Address::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_ADDRESS;
		
		$address = null;
		$email = '';
		
		$tpl->assign('view_id', $view_id);
		
		if($context_id) {
			if(false == ($addy = DAO_Address::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			@$email = $addy->email;
		}
		$tpl->assign('email', $email);
		
		if($email) {
			$address = DAO_Address::getByEmail($email);
			$tpl->assign('address', $address);
			
			if(!$context_id && $address instanceof Model_Address) {
				$context_id = $address->id;
			}
		}
		
		// Display
		$tpl->assign('id', $context_id);
		
		if(!$context_id || $edit) {
			if($address) {
				if(!Context_Address::isWriteableByActor($address, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			if($org_id) {
				if(false != ($org = DAO_ContactOrg::get($org_id))) {
					$tpl->assign('org_name', $org->name);
					$tpl->assign('org_id', $org->id);
				}
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$tpl->display('devblocks:cerberusweb.core::contacts/addresses/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $address);
		}
	}
	
	function mergeGetKeys() {
		$keys = [
			'is_banned',
			'is_defunct',
			'contact__label',
			'org__label',
			'mail_transport__label',
			'worker__label',
		];
		
		return $keys;
	}
	
	function broadcastRecipientFieldsGet() {
		$results = $this->_broadcastRecipientFieldsGet(CerberusContexts::CONTEXT_ADDRESS, 'Email', [
			'address',
			'org_email_address',
		]);
		
		asort($results);
		return $results;
	}
	
	function broadcastPlaceholdersGet() {
		$token_values = $this->_broadcastPlaceholdersGet(CerberusContexts::CONTEXT_ADDRESS);
		return $token_values;
	}
	
	function broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict) {
		$emails = $this->_broadcastRecipientFieldsToEmails($fields, $dict);
		return $emails;
	}
	
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'contact_org_id' => array(
				'label' => 'Org',
				'type' => 'ctx_' . CerberusContexts::CONTEXT_ORG,
				'param' => SearchFields_Address::CONTACT_ORG_ID,
			),
			'created_at' => array(
				'label' => DevblocksPlatform::translateCapitalized('common.created'),
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Address::CREATED_AT,
			),
			'email' => array(
				'label' => 'Email',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Address::EMAIL,
				'required' => true,
				'force_match' => true,
			),
			'is_banned' => array(
				'label' => 'Is Banned',
				'type' => Model_CustomField::TYPE_CHECKBOX,
				'param' => SearchFields_Address::IS_BANNED,
			),
			'is_defunct' => array(
				'label' => 'Is Defunct',
				'type' => Model_CustomField::TYPE_CHECKBOX,
				'param' => SearchFields_Address::IS_DEFUNCT,
			),
			'mail_transport_id' => array(
				'label' => 'Mail Transport Id',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Address::MAIL_TRANSPORT_ID,
			),
			'num_nonspam' => array(
				'label' => '# Nonspam',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Address::NUM_NONSPAM,
			),
			'num_spam' => array(
				'label' => '# Spam',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Address::NUM_SPAM,
			),
			'updated' => array(
				'label' => DevblocksPlatform::translateCapitalized('common.updated'),
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Address::UPDATED,
			),
			'worker_id' => array(
				'label' => 'Worker',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Address::WORKER_ID,
			),
		);
	
		$fields = SearchFields_Address::getFields();
		self::_getImportCustomFields($fields, $keys);
		
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have a name
			if(!isset($fields[DAO_Address::EMAIL])) {
				return FALSE;
			}
	
			// Create
			$meta['object_id'] = DAO_Address::create($fields);
	
		} else {
			// Update
			DAO_Address::update($meta['object_id'], $fields);
		}
	
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
};