<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

class DAO_Message extends Cerb_ORMHelper {
	const ADDRESS_ID = 'address_id';
	const CREATED_DATE = 'created_date';
	const HASH_HEADER_MESSAGE_ID = 'hash_header_message_id';
	const HTML_ATTACHMENT_ID = 'html_attachment_id';
	const ID = 'id';
	const IS_BROADCAST = 'is_broadcast';
	const IS_NOT_SENT = 'is_not_sent';
	const IS_OUTGOING = 'is_outgoing';
	const RESPONSE_TIME = 'response_time';
	const SIGNED_KEY_FINGERPRINT = 'signed_key_fingerprint';
	const SIGNED_AT = 'signed_at';
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	const STORAGE_SIZE = 'storage_size';
	const TICKET_ID = 'ticket_id';
	const TOKEN = 'token';
	const WAS_ENCRYPTED = 'was_encrypted';
	const WORKER_ID = 'worker_id';
	const _CONTENT = '_content';
	const _CONTENT_HTML = '_content_html';
	const _HEADERS = '_headers';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::ADDRESS_ID, 'sender_id')
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS))
			;
		// int(10) unsigned
		$validation
			->addField(self::CREATED_DATE)
			->timestamp()
			;
		// varchar(40)
		$validation
			->addField(self::HASH_HEADER_MESSAGE_ID)
			->string()
			->setMaxLength(40)
			;
		// int(10) unsigned
		$validation
			->addField(self::HTML_ATTACHMENT_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ATTACHMENT, true))
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::IS_BROADCAST)
			->bit()
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::IS_NOT_SENT)
			->bit()
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::IS_OUTGOING)
			->bit()
			;
		// int(10) unsigned
		$validation
			->addField(self::RESPONSE_TIME)
			->uint(4)
			;
		// varchar(64)
		$validation
			->addField(self::SIGNED_KEY_FINGERPRINT)
			->string()
			->setMaxLength(64)
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::SIGNED_AT)
			->timestamp()
			->setEditable(false)
		;
		// varchar(255)
		$validation
			->addField(self::STORAGE_EXTENSION)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::STORAGE_KEY)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::STORAGE_PROFILE_ID)
			->id()
			;
		// int(10) unsigned
		$validation
			->addField(self::STORAGE_SIZE)
			->uint(4)
			;
		// int(10) unsigned
		$validation
			->addField(self::TICKET_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_TICKET))
			;
		// varchar(16)
		$validation
			->addField(self::TOKEN)
			->string()
			->setMaxLength(16)
			;
		// tinyint(1)
		$validation
			->addField(self::WAS_ENCRYPTED)
			->bit()
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKER_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER, true))
			;
		// text
		$validation
			->addField(self::_CONTENT)
			->string()
			->setMaxLength(16777215)
			->setRequired(true)
			->setNotEmpty(false)
			;
		// text
		$validation
			->addField(self::_CONTENT_HTML)
			->string()
			->setMaxLength(16777215)
			->setRequired(false)
			->setNotEmpty(false)
			;
		// text
		$validation
			->addField(self::_HEADERS)
			->string()
			->setMaxLength(16777215)
			->setRequired(true)
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
		
		if(!isset($fields[self::CREATED_DATE]))
			$fields[self::CREATED_DATE] = time();
		
		if(!array_key_exists(self::TOKEN, $fields)) {
			do {
				$token = substr(DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(48)), 0, 10);
			} while(false != DAO_MailQueue::getByToken($token));
			$fields[self::TOKEN] = $token;
		}
		
		$sql = "INSERT INTO message () VALUES ()";
		if(!($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_MESSAGE, $id);
		
		self::update($id, $fields);
		
		if(isset($fields[self::TICKET_ID])) {
			DAO_Ticket::updateMessageCount($fields[self::TICKET_ID]);
		}
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];
		
		$context = CerberusContexts::CONTEXT_MESSAGE;
		self::_updateAbstract($context, $ids, $fields);
		
		if(array_key_exists(self::_CONTENT, $fields)) {
			foreach($ids as $id)
				Storage_MessageContent::put($id, $fields[self::_CONTENT]);
			unset($fields[self::_CONTENT]);
		}
		
		if(array_key_exists(self::_CONTENT_HTML, $fields)) {
			$default_file_id = 0;
			$messages = DAO_Message::getIds($ids);
			
			foreach($ids as $id) {
				if(!($messages[$id] ?? null)) continue;
				
				$file_id = $messages[$id]->html_attachment_id ?? 0;
				
				if(!$file_id) {
					if(!$default_file_id) {
						// Create an attachment record
						$default_file_id = DAO_Attachment::create([
							DAO_Attachment::NAME => 'original_message.html',
							DAO_Attachment::MIME_TYPE => 'text/html',
							DAO_Attachment::UPDATED => time(),
							DAO_Attachment::STORAGE_SHA1HASH => sha1($fields[self::_CONTENT_HTML]),
						]);
					}
					
					$file_id = $default_file_id;
				
					// Update the attachment ID if it wasn't set
					DAO_Message::update($id, [
						self::HTML_ATTACHMENT_ID => $file_id,
					]);
					
					// Link the new file to these messages
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $id, $file_id);
				}
				
				Storage_Attachments::put($file_id, $fields[self::_CONTENT_HTML]);
			}
			
			unset($fields[self::_CONTENT_HTML]);
		}
		
		if(array_key_exists(self::_HEADERS, $fields)) {
			foreach($ids as $id)
				DAO_MessageHeaders::upsert($id, $fields[self::_HEADERS]);
			unset($fields[self::_HEADERS]);
		}
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_MESSAGE, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'message', $fields);
			
			if($check_deltas) {
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_MESSAGE, $batch_ids);
			}
		}		
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_MESSAGE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::TICKET_ID])) {
			$error = "A 'ticket_id' is required.";
			return false;
		}
		
		if(isset($fields[self::TICKET_ID])) {
			@$ticket_id = $fields[self::TICKET_ID];
			
			if(!$ticket_id) {
				$error = "Invalid 'ticket_id' value.";
				return false;
			}
			
			if(!Context_Ticket::isWriteableByActor($ticket_id, $actor)) {
				$error = "You do not have permission to create messages on this ticket.";
				return false;
			}
		}
		
		return true;
	}
	
	static function onUpdateByActor($actor, $fields, $id) {
		if(array_key_exists(self::HTML_ATTACHMENT_ID, $fields)) {
			DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $id, $fields[self::HTML_ATTACHMENT_ID]);
		}
		
		if(($ticket_id = ($fields[self::TICKET_ID] ?? null))) {
			DAO_Ticket::rebuild($ticket_id);
		}
	}

	/**
	 * @param string $where
	 * @return Model_Message[]
	 */
	static function getWhere($where=null, $sortBy='created_date', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, ticket_id, created_date, is_outgoing, worker_id, html_attachment_id, address_id, storage_extension, storage_key, storage_profile_id, storage_size, response_time, is_broadcast, is_not_sent, hash_header_message_id, token, was_encrypted, signed_key_fingerprint, signed_at ".
			"FROM message ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Message
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
	
	public static function getByToken($token) {
		if(empty($token))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %s",
			self::TOKEN,
			self::qstr($token)
		));
		
		return array_shift($objects);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Message[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Message();
			$object->id = intval($row['id']);
			$object->ticket_id = intval($row['ticket_id']);
			$object->created_date = intval($row['created_date']);
			$object->is_outgoing = !empty($row['is_outgoing']) ? 1 : 0;
			$object->worker_id = intval($row['worker_id']);
			$object->html_attachment_id = intval($row['html_attachment_id']);
			$object->address_id = intval($row['address_id']);
			$object->storage_extension = $row['storage_extension'];
			$object->storage_key = $row['storage_key'];
			$object->storage_profile_id = $row['storage_profile_id'];
			$object->storage_size = intval($row['storage_size']);
			$object->response_time = intval($row['response_time']);
			$object->is_broadcast = intval($row['is_broadcast']);
			$object->is_not_sent = intval($row['is_not_sent']);
			$object->hash_header_message_id = $row['hash_header_message_id'];
			$object->token = $row['token'];
			$object->was_encrypted = !empty($row['was_encrypted']) ? 1 : 0;
			$object->signed_key_fingerprint = $row['signed_key_fingerprint'];
			$object->signed_at = intval($row['signed_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * @return Model_Message[]
	 */
	static function getMessagesByTicket($ticket_id) {
		return self::getWhere(
			sprintf("%s = %d",
				self::TICKET_ID,
				$ticket_id
			),
			DAO_Message::CREATED_DATE,
			true
		);
	}
	
	public static function getLatestIdByRecipientId(int $address_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$address_id)
			return 0;
		
		return $db->GetOneReader(sprintf("select max(id) from message where is_outgoing=1 and ticket_id in (select ticket_id from requester where address_id = %d)",
			$address_id
		));
	}
	
	public static function getLatestIdByRecipientContactId(int $contact_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$contact_id)
			return 0;
		
		return $db->GetOneReader(sprintf("select max(id) from message where is_outgoing=1 and ticket_id in (select ticket_id from requester where address_id in (select id from address where contact_id = %d))",
			$contact_id
		));
	}
	
	public static function getLatestIdByRecipientOrgId(int $org_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$org_id)
			return 0;
		
		return $db->GetOneReader(sprintf("select max(id) from message where is_outgoing=1 and ticket_id in (select ticket_id from requester where address_id in (select id from address where contact_org_id = %d))",
			$org_id
		));
	}
	
	public static function getLatestIdBySenderId(int $address_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$address_id)
			return 0;
		
		return $db->GetOneReader(sprintf("select max(id) from message where is_outgoing=0 and address_id = %d",
			$address_id
		));
	}
	
	public static function getLatestIdBySenderContactId(int $contact_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$contact_id)
			return 0;
		
		return $db->GetOneReader(sprintf("select max(id) from message where is_outgoing=0 and address_id in (select id from address where contact_id = %d)",
			$contact_id
		));
	}
	
	public static function getLatestIdBySenderOrgId(int $org_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$org_id)
			return 0;
		
		return $db->GetOneReader(sprintf("select max(id) from message where is_outgoing=0 and address_id in (select id from address where contact_org_id = %d)",
			$org_id
		));
	}
	
	static function countByTicketId($ticket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM message WHERE ticket_id = %d",
			$ticket_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	public static function deleteByTicketIds(array $ticket_ids, $rebuild=true) {
		$db = DevblocksPlatform::services()->database();
		
		if(!($ticket_ids = DevblocksPlatform::sanitizeArray($ticket_ids, 'int')))
			return;
		
		// Batch delete messages by groups of tickets
		foreach(array_chunk($ticket_ids, 50) as $batch_ids) {
			if(!($batch_ids = DevblocksPlatform::sanitizeArray($batch_ids, 'int')))
				continue;
			
			$message_ids = $db->GetArrayReader(sprintf('SELECT id FROM message WHERE ticket_id IN (%s)',
				implode(',', $batch_ids)
			));
			
			if(!$message_ids)
				continue;
			
			$message_ids = array_column($message_ids, 'id');
			
			self::delete($message_ids, $rebuild);
		}
	}

	static function delete($ids, $rebuild=true) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_MESSAGE;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		// Message Headers
		DAO_MessageHeaders::delete($ids);
		
		// Message Content
		Storage_MessageContent::delete($ids);

		$ticket_ids = [];
		
		if($rebuild) {
			$messages = DAO_Message::getIds($ids);
			$ticket_ids = array_unique(array_column($messages, 'ticket_id'));
		}
		
		// Messages
		$db->ExecuteMaster(sprintf("DELETE FROM message WHERE id IN (%s)",
			$ids_list
		));
		
		// Remap first/last on distinct ticket
		if($rebuild) {
			array_walk($ticket_ids, fn($ticket_id) => DAO_Ticket::rebuild($ticket_id));
		}
		
		parent::_deleteAbstractAfter($context, $ids);
	}
	
	static function maint() {
	}

	public static function random() {
		return self::_getRandom('message');
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Message::getFields();
		
		list($tables,$wheres,) = parent::_parseSearchParams($params, [], 'SearchFields_Message', $sortBy);

		$select_sql = sprintf('SELECT message.id AS %s ',
			SearchFields_Message::ID
		);
		
		$join_sql = "FROM message ".
			(isset($tables['ticket']) ? "INNER JOIN ticket ON (message.ticket_id = ticket.id) " : " ").
			(isset($tables['address']) ? "INNER JOIN address ON (message.address_id = address.id) " : " ")
			;
		
		$where_sql = 
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Message');
		
		return [
			'primary_table' => 'message',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	/**
	 *
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
		$query_parts = self::getSearchQueryComponents($columns, $params, $sortBy, $sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];

		$results = self::_searchWithTimeout(
			SearchFields_Message::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
		
		$models = CerberusContexts::getModels(
			CerberusContexts::CONTEXT_MESSAGE,
			array_column(
				$results[0],
				SearchFields_Message::ID
			)
		);
		
		foreach($results[0] as $id => $result) {
			if(null != ($model = $models[$id] ?? null)) { /* @var Model_Message $model */
				$result[SearchFields_Message::ADDRESS_ID] = $model->address_id;
				$result[SearchFields_Message::CREATED_DATE] = $model->created_date;
				$result[SearchFields_Message::ID] = $model->id;
				$result[SearchFields_Message::IS_BROADCAST] = $model->is_broadcast;
				$result[SearchFields_Message::IS_NOT_SENT] = $model->is_not_sent;
				$result[SearchFields_Message::IS_OUTGOING] = $model->is_outgoing;
				$result[SearchFields_Message::RESPONSE_TIME] = $model->response_time;
				$result[SearchFields_Message::SIGNED_AT] = $model->signed_at;
				$result[SearchFields_Message::SIGNED_KEY_FINGERPRINT] = $model->signed_key_fingerprint;
				$result[SearchFields_Message::STORAGE_EXTENSION] = $model->storage_extension;
				$result[SearchFields_Message::STORAGE_KEY] = $model->storage_key;
				$result[SearchFields_Message::STORAGE_PROFILE_ID] = $model->storage_profile_id;
				$result[SearchFields_Message::STORAGE_SIZE] = $model->storage_size;
				$result[SearchFields_Message::TICKET_ID] = $model->ticket_id;
				$result[SearchFields_Message::TOKEN] = $model->token;
				$result[SearchFields_Message::WAS_ENCRYPTED] = $model->was_encrypted;
				$result[SearchFields_Message::WORKER_ID] = $model->worker_id;
				
				$results[0][$id] = array_merge($result, $results[0][$id]);
			}
		}
		
		return $results;
	}
};

class SearchFields_Message extends DevblocksSearchFields {
	// Message
	const ID = 'm_id';
	const ADDRESS_ID = 'm_address_id';
	const CREATED_DATE = 'm_created_date';
	const IS_OUTGOING = 'm_is_outgoing';
	const TICKET_ID = 'm_ticket_id';
	const WORKER_ID = 'm_worker_id';
	const HTML_ATTACHMENT_ID = 'm_html_attachment_id';
	const RESPONSE_TIME = 'm_response_time';
	const IS_BROADCAST = 'm_is_broadcast';
	const IS_NOT_SENT = 'm_is_not_sent';
	const SIGNED_KEY_FINGERPRINT = 'm_signed_key_fingerprint';
	const SIGNED_AT = 'm_signed_at';
	const TOKEN = 'm_token';
	const WAS_ENCRYPTED = 'm_was_encrypted';
	
	// Storage
	const STORAGE_EXTENSION = 'm_storage_extension';
	const STORAGE_KEY = 'm_storage_key';
	const STORAGE_PROFILE_ID = 'm_storage_profile_id';
	const STORAGE_SIZE = 'm_storage_size';

	// Address
	const ADDRESS_EMAIL = 'a_email';
	
	// Ticket
	const TICKET_BUCKET_ID = 't_bucket_id';
	const TICKET_GROUP_ID = 't_group_id';
	const TICKET_STATUS_ID = 't_status_id';
	const TICKET_MASK = 't_mask';
	const TICKET_SUBJECT = 't_subject';
	
	// Virtuals
	const VIRTUAL_ATTACHMENTS_SEARCH = '*_attachments_search';
	const VIRTUAL_NOTES_SEARCH = '*_notes_search';
	const VIRTUAL_HEADER_MESSAGE_ID = '*_header_message_id';
	const VIRTUAL_SENDER_SEARCH = '*_sender_search';
	const VIRTUAL_TICKET_SEARCH = '*_ticket_search';
	const VIRTUAL_WORKER_SEARCH = '*_worker_search';
	
	static private $_fields = null;
	
	static function getTableName() : string {
		return 'message';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Message::ID);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Message::CREATED_DATE);
	}

	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_MESSAGE => new DevblocksSearchFieldContextKeys('message.id', self::ID),
			CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('message.address_id', self::ADDRESS_ID),
			CerberusContexts::CONTEXT_GROUP => new DevblocksSearchFieldContextKeys('ticket.group_id', self::TICKET_GROUP_ID),
			CerberusContexts::CONTEXT_BUCKET => new DevblocksSearchFieldContextKeys('ticket.group_id', self::TICKET_BUCKET_ID),
			CerberusContexts::CONTEXT_TICKET => new DevblocksSearchFieldContextKeys('message.ticket_id', self::TICKET_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_ATTACHMENTS_SEARCH:
				return self::_getWhereSQLFromAttachmentsField($param, CerberusContexts::CONTEXT_MESSAGE, self::getPrimaryKey());
				
			case self::VIRTUAL_NOTES_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_COMMENT, sprintf('SELECT context_id FROM comment WHERE context = %s AND id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_MESSAGE), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_HEADER_MESSAGE_ID:
				$value = $param->value;
				
				if(DevblocksPlatform::strStartsWith($value, '<'))
					$value = sha1($value);
				
				if(str_contains($value, '*')) {
					return sprintf("message.hash_header_message_id LIKE %s",
						Cerb_ORMHelper::qstr(str_replace('*','%',$value))
					);
				} else {
					return sprintf("message.hash_header_message_id = %s",
						Cerb_ORMHelper::qstr($value)
					);
				}
				
			case self::VIRTUAL_SENDER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'message.address_id');
				
			case self::VIRTUAL_TICKET_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_TICKET, 'message.ticket_id');
				
			case self::VIRTUAL_WORKER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 'message.worker_id');
				
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, CerberusContexts::CONTEXT_MESSAGE, self::getPrimaryKey())))
						return $virtual_where_sql;
					
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'sender':
				$key = 'sender.id';
				break;
				
			case 'ticket':
				$key = 'ticket.id';
				break;
				
			case 'group':
			case 'ticket.group':
			case 'ticket.group.id':
			case 'ticket.group.name':
				$search_key = $key;
				$group_field = $search_fields[SearchFields_Message::TICKET_GROUP_ID];
				
				$join_as = uniqid('t_');
				
				return [
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_GROUP,
					],
					'sql_select' => sprintf("%s.%s",
						Cerb_ORMHelper::escape($join_as),
						Cerb_ORMHelper::escape($group_field->db_column)
					),
					'sql_join' => sprintf("INNER JOIN ticket AS %s ON (%s.id=message.ticket_id)",
						Cerb_ORMHelper::escape($join_as),
						Cerb_ORMHelper::escape($join_as)
					),
					'get_value_as_filter_callback' => function($value, &$filter) {
						$filter = 'ticket:(group:(id:%s))';
						return $value;
					}
				];
				
			case 'bucket':
			case 'ticket.bucket':
			case 'ticket.bucket.id':
			case 'ticket.bucket.name':
				$search_key = $key;
				$bucket_field = $search_fields[SearchFields_Message::TICKET_BUCKET_ID];
				
				$join_as = uniqid('t_');
				
				return [
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_BUCKET,
					],
					'sql_select' => sprintf("%s.%s",
						Cerb_ORMHelper::escape($join_as),
						Cerb_ORMHelper::escape($bucket_field->db_column)
					),
					'sql_join' => sprintf("INNER JOIN ticket AS %s ON (%s.id=message.ticket_id)",
						Cerb_ORMHelper::escape($join_as),
						Cerb_ORMHelper::escape($join_as)
					),
					'get_value_as_filter_callback' => function($value, &$filter) {
						$filter = 'ticket:(bucket:(id:%s))';
						return $value;
					}
				];
				break;
				
			case 'ticket.mask':
				$search_key = $key;
				$mask_field = $search_fields[SearchFields_Message::TICKET_MASK];
				
				return [
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'sql_select' => sprintf("(SELECT mask FROM ticket WHERE id = message.ticket_id)",
						Cerb_ORMHelper::escape($mask_field->db_table),
						Cerb_ORMHelper::escape($mask_field->db_column)
					),
					'get_value_as_filter_callback' => function($value, &$filter) {
						$filter = 'ticket:(mask:%s)';
						return $value;
					}
				];
				break;
				
			case 'worker':
				$key = 'worker.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Message::ADDRESS_ID:
				$models = DAO_Address::getIds($values);
				$label_map = array_column($models, 'email', 'id');
				$label_map[0] = DevblocksPlatform::translate('common.nobody');
				return $label_map;
				
			case 'group':
			case 'ticket.group':
			case 'ticket.group.name':
				$models = DAO_Group::getIds($values);
				$label_map = array_column($models, 'name', 'id');
				$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
	
			case 'bucket':
			case 'ticket.bucket.name':
				$models = DAO_Bucket::getIds($values);
				$label_map = array_column($models, 'name', 'id');
				$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
				
			case SearchFields_Message::TICKET_ID:
				$models = DAO_Ticket::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_TICKET);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				return $label_map;
				break;
				
			case SearchFields_Message::ID:
				$models = DAO_Message::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_MESSAGE);
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'sender_');
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'ticket_');
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, '_label');
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				
			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::WAS_ENCRYPTED:
				return parent::_getLabelsForKeyBooleanValues();
				
			case SearchFields_Message::WORKER_ID:
				$models = DAO_Worker::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				$label_map[0] = DevblocksPlatform::translate('common.nobody');
				return $label_map;
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
			SearchFields_Message::ID => new DevblocksSearchField(SearchFields_Message::ID, 'message', 'id', $translate->_('common.id'), null, true),
			SearchFields_Message::ADDRESS_ID => new DevblocksSearchField(SearchFields_Message::ADDRESS_ID, 'message', 'address_id', $translate->_('common.sender'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Message::CREATED_DATE => new DevblocksSearchField(SearchFields_Message::CREATED_DATE, 'message', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Message::IS_OUTGOING => new DevblocksSearchField(SearchFields_Message::IS_OUTGOING, 'message', 'is_outgoing', $translate->_('message.is_outgoing'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::TICKET_ID => new DevblocksSearchField(SearchFields_Message::TICKET_ID, 'message', 'ticket_id', 'Ticket ID', null, true),
			SearchFields_Message::WORKER_ID => new DevblocksSearchField(SearchFields_Message::WORKER_ID, 'message', 'worker_id', $translate->_('common.worker'), Model_CustomField::TYPE_WORKER, true),
			SearchFields_Message::HTML_ATTACHMENT_ID => new DevblocksSearchField(SearchFields_Message::HTML_ATTACHMENT_ID, 'message', 'html_attachment_id', null, null, true),
			SearchFields_Message::RESPONSE_TIME => new DevblocksSearchField(SearchFields_Message::RESPONSE_TIME, 'message', 'response_time', $translate->_('message.response_time'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Message::IS_BROADCAST => new DevblocksSearchField(SearchFields_Message::IS_BROADCAST, 'message', 'is_broadcast', $translate->_('message.is_broadcast'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::IS_NOT_SENT => new DevblocksSearchField(SearchFields_Message::IS_NOT_SENT, 'message', 'is_not_sent', $translate->_('message.is_not_sent'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::SIGNED_KEY_FINGERPRINT => new DevblocksSearchField(SearchFields_Message::SIGNED_KEY_FINGERPRINT, 'message', 'signed_key_fingerprint', $translate->_('message.signed_key_fingerprint'), Model_CustomField::TYPE_SINGLE_LINE, true),
			SearchFields_Message::SIGNED_AT => new DevblocksSearchField(SearchFields_Message::SIGNED_AT, 'message', 'signed_at', $translate->_('message.signed_at'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Message::TOKEN => new DevblocksSearchField(SearchFields_Message::TOKEN, 'message', 'token', $translate->_('common.token'), Model_CustomField::TYPE_SINGLE_LINE, true),
			SearchFields_Message::WAS_ENCRYPTED => new DevblocksSearchField(SearchFields_Message::WAS_ENCRYPTED, 'message', 'was_encrypted', $translate->_('message.is_encrypted'), Model_CustomField::TYPE_CHECKBOX, true),
			
			SearchFields_Message::STORAGE_EXTENSION => new DevblocksSearchField(SearchFields_Message::STORAGE_EXTENSION, 'message', 'storage_extension', null, true),
			SearchFields_Message::STORAGE_KEY => new DevblocksSearchField(SearchFields_Message::STORAGE_KEY, 'message', 'storage_key', null, true),
			SearchFields_Message::STORAGE_PROFILE_ID => new DevblocksSearchField(SearchFields_Message::STORAGE_PROFILE_ID, 'message', 'storage_profile_id', null, true),
			SearchFields_Message::STORAGE_SIZE => new DevblocksSearchField(SearchFields_Message::STORAGE_SIZE, 'message', 'storage_size', $translate->_('common.size'), true),
			
			SearchFields_Message::ADDRESS_EMAIL => new DevblocksSearchField(SearchFields_Message::ADDRESS_EMAIL, 'a', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE, false),
			
			SearchFields_Message::TICKET_BUCKET_ID => new DevblocksSearchField(SearchFields_Message::TICKET_BUCKET_ID, 'ticket', 'bucket_id', $translate->_('common.bucket'), null, false),
			SearchFields_Message::TICKET_GROUP_ID => new DevblocksSearchField(SearchFields_Message::TICKET_GROUP_ID, 'ticket', 'group_id', $translate->_('common.group'), null, false),
			SearchFields_Message::TICKET_STATUS_ID => new DevblocksSearchField(SearchFields_Message::TICKET_STATUS_ID, 'ticket', 'status_id', $translate->_('common.status'), Model_CustomField::TYPE_NUMBER, false),
			SearchFields_Message::TICKET_MASK => new DevblocksSearchField(SearchFields_Message::TICKET_MASK, 'ticket', 'mask', $translate->_('ticket.mask'), Model_CustomField::TYPE_SINGLE_LINE, false),
			SearchFields_Message::TICKET_SUBJECT => new DevblocksSearchField(SearchFields_Message::TICKET_SUBJECT, 'ticket', 'subject', $translate->_('ticket.subject'), Model_CustomField::TYPE_SINGLE_LINE, false),
			
			SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH, '*', 'attachments_search', null, null, false),
			SearchFields_Message::VIRTUAL_NOTES_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_NOTES_SEARCH, '*', 'notes_search', null, null, false),
			SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID => new DevblocksSearchField(SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID, '*', 'header_message_id', $translate->_('message.search.header_message_id'), Model_CustomField::TYPE_SINGLE_LINE, false),
			SearchFields_Message::VIRTUAL_SENDER_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_SENDER_SEARCH, '*', 'sender_search', null, null, false),
			SearchFields_Message::VIRTUAL_TICKET_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_TICKET_SEARCH, '*', 'ticket_search', null, null, false),
			SearchFields_Message::VIRTUAL_WORKER_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_WORKER_SEARCH, '*', 'worker_search', null, null, false),
		];
		
		// Virtual fields
		if(($virtual_columns = DevblocksSearchField::getVirtualFields(watchers: false)))
			$columns = array_merge($columns, $virtual_columns);

		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Message extends DevblocksRecordModel {
	public $id;
	public $ticket_id;
	public $created_date;
	public $address_id;
	public $is_outgoing;
	public $worker_id;
	public $html_attachment_id = 0;
	public $storage_extension;
	public $storage_key;
	public $storage_profile_id;
	public $storage_size;
	public $response_time;
	public $is_broadcast;
	public $is_not_sent;
	public $hash_header_message_id;
	public $signed_key_fingerprint;
	public $signed_at;
	public $token;
	public $was_encrypted;
	
	private $_attachments = null;
	private $_custom_field_values = null;
	private $_headers_raw = null;
	private $_sender_object = null;
	private $_worker_object = null;
	
	function getContent(&$fp=null) {
		if(empty($this->storage_extension) || empty($this->storage_key))
			return '';

		return Storage_MessageContent::get($this, $fp);
	}
	
	function getContentAsHtml($allow_images=false, &$filtering_results=null, $unstyled=false) {
		// If we don't have an HTML part, or the given ID fails to load, HTMLify the regular content
		if(empty($this->html_attachment_id) 
			|| !($attachment = DAO_Attachment::get($this->html_attachment_id))) {
				return false;
		}
		
		// We can keep a local warm cache of remote HTML content
		$is_stored_remotely = in_array(
			$attachment->storage_extension,
			[
				'cerb.cloud.storage.engine.s3',
				'devblocks.storage.engine.s3',
				'devblocks.storage.engine.gatekeeper',
			]
		);
		
		// Was it previous cached?
		$dirty_html = $is_stored_remotely ? DAO_MessageHtmlCache::get($this->id) : null;
		
		if(!$dirty_html) {
			// If attachment size is more than 1MB, fall back to plaintext
			if($attachment->storage_size > 1000000)
				return false;
			
			// If the attachment is inaccessible, fallback to plaintext 
			if(!($dirty_html = $attachment->getFileContents()))
				return false;
		}
		
		// Cache it
		if($is_stored_remotely)
			DAO_MessageHtmlCache::set($this->id, $dirty_html);
		
		// If the 'tidy' extension exists
		if(extension_loaded('tidy')) {
			$tidy = new tidy();
			
			$config = array (
				'bare' => true,
				'clean' => true,
				'drop-proprietary-attributes' => true,
				'indent' => false,
				'output-xhtml' => false,
				'output-html' => true,
				'wrap' => 0,
			);
			
			$dirty_html = str_replace(
				[
					'<center',
					'</center>',
				],
				[
					'<span',
					'</span>',
				],
				$dirty_html
			);
			
			// If we're not stripping Microsoft Office formatting
			if(DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::HTML_NO_STRIP_MICROSOFT, CerberusSettingsDefaults::HTML_NO_STRIP_MICROSOFT)) {
				unset($config['bare']);
				unset($config['drop-proprietary-attributes']);
			}
			
			$dirty_html = $tidy->repairString($dirty_html, $config, DB_CHARSET_CODE);
		}
		
		$filter = new Cerb_HTMLPurifier_URIFilter_Email($allow_images);
		
		$dirty_html = DevblocksPlatform::purifyHTML($dirty_html, true, true, [$filter], $unstyled);
		
		$filtering_results = $filter->flush();
		
		return $dirty_html;
	}
	
	function setHeadersRaw($headers_raw) {
		$this->_headers_raw = $headers_raw;
	}

	function getHeaders($raw = false) {
		if(is_null($this->_headers_raw))
			$this->_headers_raw = DAO_MessageHeaders::getRaw($this->id);
		
		return $raw ? $this->_headers_raw : DAO_MessageHeaders::parse($this->_headers_raw);
	}
	
	/**
	 *
	 * Enter description here ...
	 * @return Model_Address
	 */
	function getSender() {
		// Lazy load + cache
		if(null == $this->_sender_object) {
			$this->_sender_object = DAO_Address::get($this->address_id);
		}
		
		return $this->_sender_object;
	}
	
	public function setSender(Model_Address $sender) {
		$this->_sender_object = $sender;
	}
	
	function getWorker() {
		if(!is_null($this->_worker_object))
			return $this->_worker_object;
	
		if(empty($this->worker_id))
			return null;
		
		$this->_worker_object = DAO_Worker::get($this->worker_id);
		
		return $this->_worker_object;
	}
	
	function setWorker(Model_Worker $worker) {
		$this->_worker_object = $worker;
	}
	
	/**
	 * returns an array of the message's attachments
	 *
	 * @return Model_Attachment[]
	 */
	function getAttachments() {
		if(!is_null($this->_attachments))
			return $this->_attachments;
		
		$this->_attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, $this->id, true);
		
		return $this->_attachments;
	}
	
	function setAttachments(array $attachments) {
		$this->_attachments = $attachments;
	}
	
	/**
	 * @return Model_Ticket
	 */
	function getTicket() {
		return DAO_Ticket::get($this->ticket_id);
	}
	
	function getCustomFieldValues() {
		if(!is_null($this->_custom_field_values))
			return $this->_custom_field_values;
		
		$values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MESSAGE, $this->id);
		
		$this->_custom_field_values = $values[$this->id] ?? [];
		
		return $this->_custom_field_values;
	}
	
	function setCustomFieldValues(array $values) {
		$this->_custom_field_values = $values;
	}
	
	function getTimeline($is_ascending=true) {
		$timeline = [
			$this,
		];
		
		if(($comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, $this->id)))
			$timeline = array_merge($timeline, $comments);
		
		usort($timeline, function($a, $b) use ($is_ascending) {
			if($a instanceof Model_Message) {
				$a_time = intval($a->created_date);
			} else if($a instanceof Model_Comment) {
				$a_time = intval($a->created);
			} else {
				$a_time = 0;
			}
			
			if($b instanceof Model_Message) {
				$b_time = intval($b->created_date);
			} else if($b instanceof Model_Comment) {
				$b_time = intval($b->created);
			} else {
				$b_time = 0;
			}
			
			if($a_time > $b_time) {
				return ($is_ascending) ? 1 : -1;
			} else if ($a_time < $b_time) {
				return ($is_ascending) ? -1 : 1;
			} else {
				return 0;
			}
		});
		
		return $timeline;
	}
	
};

class Storage_MessageContent extends Extension_DevblocksStorageSchema {
	const string ID = 'cerberusweb.storage.schema.message_content';

	public static function getStorageTableName() : string {
		return 'message';
	}

	public static function getStorageNamespace() : string {
		return 'message_content';
	}

	/**
	 * @param Model_Message | $message_id
	 * @return string
	 */
	public static function get($object, &$fp=null) {
		if($object instanceof Model_Message) {
			// Do nothing
		} elseif(is_numeric($object)) {
			$object = DAO_Message::get($object);
		} else {
			$object = null;
		}
		
		if(empty($object))
			return false;
		
		$key = $object->storage_key;
		$profile = !empty($object->storage_profile_id) ? $object->storage_profile_id : $object->storage_extension;
		
		if(false === ($storage = DevblocksPlatform::getStorageService($profile)))
			return false;
			
		$contents = $storage->get('message_content', $key, $fp);
		
		// Convert the appropriate bytes
		if(is_string($contents) && !mb_check_encoding($contents, LANG_CHARSET_CODE))
			$contents = mb_convert_encoding($contents, LANG_CHARSET_CODE);
			
		return $contents;
	}
	
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

		if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
			
		} else {
			if(!is_string($contents))
				$contents = '';
			
			// Store the appropriate bytes
			if(!mb_check_encoding($contents, LANG_CHARSET_CODE))
				$contents = mb_convert_encoding($contents, LANG_CHARSET_CODE);
			
			$storage_size = strlen($contents);
		}
		
		// Save to storage
		if(false === ($storage_key = $storage->put('message_content', $id, $contents)))
			return false;
			
		// Update storage key
		DAO_Message::update($id, array(
			DAO_Message::STORAGE_EXTENSION => $storage->manifest->id,
			DAO_Message::STORAGE_KEY => $storage_key,
			DAO_Message::STORAGE_PROFILE_ID => $profile_id,
			DAO_Message::STORAGE_SIZE => $storage_size,
		));
	
		return $storage_key;
	}

	protected static function getArchiveCandidates(string $src_extension, int $src_profile_id, int $before, int $last_at, int $last_id, int $limit) : array {
		$db = DevblocksPlatform::services()->database();

		return $db->GetArrayReader(sprintf(
			"SELECT id, created_date AS cursor_at FROM message ".
			"WHERE storage_extension = %s AND storage_profile_id = %d ".
			"AND created_date < %d AND (created_date > %d OR (created_date = %d AND id > %d)) ".
			"ORDER BY created_date ASC, id ASC LIMIT %d",
			$db->qstr($src_extension), $src_profile_id,
			$before, $last_at, $last_at, $last_id, $limit
		));
	}
};

class View_Message extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'messages';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Messages';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Message::CREATED_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = [
			SearchFields_Message::ADDRESS_EMAIL,
			SearchFields_Message::TICKET_GROUP_ID,
			SearchFields_Message::WORKER_ID,
			SearchFields_Message::CREATED_DATE,
		];
		
		$this->addColumnsHidden([
			SearchFields_Message::HTML_ATTACHMENT_ID,
			SearchFields_Message::STORAGE_EXTENSION,
			SearchFields_Message::STORAGE_KEY,
			SearchFields_Message::STORAGE_PROFILE_ID,
			SearchFields_Message::STORAGE_SIZE,
			SearchFields_Message::TICKET_STATUS_ID,
			SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH,
			SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID,
			SearchFields_Message::VIRTUAL_NOTES_SEARCH,
			SearchFields_Message::VIRTUAL_TICKET_SEARCH,
		]);

		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Message::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Message');
		
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_Message', $ids, $total);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Message::ADDRESS_EMAIL:
				case SearchFields_Message::IS_BROADCAST:
				case SearchFields_Message::IS_NOT_SENT:
				case SearchFields_Message::IS_OUTGOING:
				case SearchFields_Message::TICKET_BUCKET_ID:
				case SearchFields_Message::TICKET_GROUP_ID:
				case SearchFields_Message::TICKET_ID:
				case SearchFields_Message::TICKET_MASK:
				case SearchFields_Message::WAS_ENCRYPTED:
				case SearchFields_Message::WORKER_ID:
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
		$context = CerberusContexts::CONTEXT_MESSAGE;

		if(!array_key_exists($column, $fields))
			return [];
		
		switch($column) {
			case SearchFields_Message::ADDRESS_EMAIL:
				$label_map = function($ids) {
					$rows = DAO_Address::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'email', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Message::ADDRESS_ID, $label_map, 'in', 'value[]');
				break;
			
			case SearchFields_Message::TICKET_BUCKET_ID:
				$label_map = function($ids) {
					$rows = DAO_Bucket::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'name', 'id');
				};
				$counts = $this->_getSubtotalCountForVirtualColumn($context, SearchFields_Message::TICKET_BUCKET_ID, $label_map, SearchFields_Message::VIRTUAL_TICKET_SEARCH, 'bucket:(id:%s)', 'bucket:null');
				break;
				
			case SearchFields_Message::TICKET_GROUP_ID:
				$label_map = function($ids) {
					$rows = DAO_Group::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'name', 'id');
				};
				$counts = $this->_getSubtotalCountForVirtualColumn($context, SearchFields_Message::TICKET_GROUP_ID, $label_map, SearchFields_Message::VIRTUAL_TICKET_SEARCH, 'group:(id:%s)', 'group:null');
				break;
			
			case SearchFields_Message::TICKET_ID:
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, [], '=', 'value');
				break;
				
			case SearchFields_Message::TICKET_MASK:
				$label_map = function($ids) {
					$rows = DAO_Ticket::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'mask', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Message::TICKET_ID, $label_map, 'in', 'value[]');
				break;
				
			case SearchFields_Message::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$label_map = [];
				foreach($workers as $worker_id => $worker)
					$label_map[$worker_id] = $worker->getName();
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in', 'worker_id[]');
				break;

			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::WAS_ENCRYPTED:
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
		return 'content';
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Message::getFields();
		
		$fields = array(
			'attachments' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ATTACHMENT, 'q' => ''],
					]
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'score' => 2000,
					'options' => array('param_key' => SearchFields_Message::CREATED_DATE),
				),
			'header.messageId' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_MESSAGE],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_MESSAGE,
					],
					'options' => array('param_key' => SearchFields_Message::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MESSAGE, 'q' => ''],
					]
				),
			'isBroadcast' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Message::IS_BROADCAST),
				),
			'isEncrypted' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Message::WAS_ENCRYPTED),
				),
			'isNotSent' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Message::IS_NOT_SENT),
				),
			'isOutgoing' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'score' => 2000,
					'options' => array('param_key' => SearchFields_Message::IS_OUTGOING),
				),
			'signed.at' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Message::SIGNED_AT),
				),
			'signed.fingerprint' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Message::SIGNED_KEY_FINGERPRINT),
				),
			'size' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::STORAGE_SIZE),
					'examples' => [
						'>1MB',
						'<=512KB',
					]
				),
			'token' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Message::TOKEN),
				),
			'notes' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_NOTES_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_COMMENT, 'q' => ''],
					]
				),
			'responseTime' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER_SECONDS,
					'options' => array('param_key' => SearchFields_Message::RESPONSE_TIME),
				),
			'sender' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 2000,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_SENDER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'sender.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_ADDRESS,
					],
					'options' => array('param_key' => SearchFields_Message::ADDRESS_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'ticket' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 2000,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_TICKET_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				),
			'ticket.bucket.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Message::TICKET_BUCKET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BUCKET, 'q' => ''],
					]
				),
			'ticket.bucket.name' => 
				array(
					'type' => 'hidden',
					'options' => array('param_key' => SearchFields_Message::TICKET_BUCKET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BUCKET, 'q' => ''],
					]
				),
			'ticket.group.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Message::TICKET_GROUP_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					]
				),
			'ticket.group.name' => 
				array(
					'type' => 'hidden',
					'options' => array('param_key' => SearchFields_Message::TICKET_BUCKET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					]
				),
			'ticket.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_TICKET,
					],
					'options' => array('param_key' => SearchFields_Message::TICKET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				),
			'worker.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Message::WORKER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 1500,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_WORKER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_MESSAGE, $fields, null);

		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		$search_fields = $this->getQuickSearchFields();
		
		switch($field) {
			case 'attachments':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH);
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				
			case 'notes':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_NOTES_SEARCH);
				
			case 'from':
			case 'sender':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_SENDER_SEARCH);
				
			case 'header.messageId':
				$field_key = SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $value);
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					$value ?? ''
				);
			
			case 'size':
				return DevblocksSearchCriteria::getBytesParamFromTokens(SearchFields_Message::STORAGE_SIZE, $tokens);
				
			case 'ticket':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_TICKET_SEARCH);
				
			case 'worker':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_WORKER_SEARCH);
			
			default:
				if($field == 'links' || str_starts_with($field, 'links.'))
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);

				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		$this->_sanitize();
		
		$results = $this->getData();
		$tpl->assign('results', $results);
		
		switch($this->renderTemplate) {
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::messages/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) : void {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.attachments')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_NOTES_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.notes')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID:
				echo sprintf("Message-ID header matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_SENDER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.sender')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_TICKET_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.ticket')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_WORKER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.worker')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::WAS_ENCRYPTED:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_Message::ADDRESS_ID:
			case SearchFields_Message::TICKET_BUCKET_ID:
			case SearchFields_Message::TICKET_GROUP_ID:
			case SearchFields_Message::TOKEN:
			case SearchFields_Message::WORKER_ID:
				$label_map = SearchFields_Message::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Message::RESPONSE_TIME:
				$values = !is_array($param->value) ? array($param->value) : $param->value;
				
				foreach($values as &$value) {
					if(0 == $value) {
						$value = 'never';
					} else {
						$value = DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value, 2));
					}
				}
				unset($value);

				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_BETWEEN:
						echo implode(' and ', $values);
						break;
						
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_NIN:
						echo implode(' or ', $values);
						break;
						
					default:
						$value = array_shift($values);
						echo DevblocksPlatform::strEscapeHtml($value);
						break;
				}
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Message::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Message::VIRTUAL_TICKET_SEARCH:
				if(is_array($value))
					$value = array_shift($value);
				$criteria = new DevblocksSearchCriteria($field, DevblocksSearchCriteria::OPER_CUSTOM, $value);
				break;
			
			case SearchFields_Message::ADDRESS_EMAIL:
			case SearchFields_Message::SIGNED_KEY_FINGERPRINT:
			case SearchFields_Message::TICKET_MASK:
			case SearchFields_Message::TICKET_SUBJECT:
			case SearchFields_Message::TOKEN:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Message::ADDRESS_ID:
			case SearchFields_Message::TICKET_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Message::RESPONSE_TIME:
				$now = time();
				@$then = intval(strtotime($value, $now));
				$value = $then - $now;
				
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Message::CREATED_DATE:
			case SearchFields_Message::SIGNED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::WAS_ENCRYPTED:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;

			case SearchFields_Message::TICKET_GROUP_ID:
				$group_ids = DevblocksPlatform::importGPC($_POST['group_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$group_ids);
				break;
				
			case SearchFields_Message::WORKER_ID:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			default:
				if(($virtual_criteria = $this->_doSetCriteriaVirtual($field, $_POST, $oper)))
					$criteria = $virtual_criteria;
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

class Context_Message extends Extension_DevblocksContext implements IDevblocksContextPeek, IDevblocksContextProfile {
	const ID = CerberusContexts::CONTEXT_MESSAGE;
	const URI = 'message';
	
	static function isReadableByActor($models, $actor) {
		// Only admins and group members can see, unless public
		
		if(!($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(!($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_MESSAGE)))
			return CerberusContexts::denyEverything($models);
		
		DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'ticket_group_');
		
		$results = array_fill_keys(array_keys($dicts), false);
		
		foreach($dicts as $id => $dict) {
			$ticket_dict = $dict->extract('ticket_');
			$results[$id] = Context_Ticket::isReadableByActor($ticket_dict, $actor);
		}
		
		if(is_array($models)) {
			return $results;
		} else {
			return array_shift($results);
		}
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins and group members can modify
		
		if(!($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(!($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_MESSAGE)))
			return CerberusContexts::denyEverything($models);
		
		DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'ticket_group_');
		
		$results = array_fill_keys(array_keys($dicts), false);
		
		foreach($dicts as $id => $dict) {
			$ticket_dict = $dict->extract('ticket_');
			$results[$id] = Context_Ticket::isWriteableByActor($ticket_dict, $actor);
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
		return DAO_Message::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();

		if(null == ($message = DAO_Message::get($context_id)))
			return FALSE;
			
		if(null == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return FALSE;
			
		return array(
			'id' => $context_id,
			'name' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
			'permalink' => $url_writer->writeNoProxy(sprintf('c=profiles&type=ticket&mask=%s&focus=message&focusid=%d', $ticket->mask, $message->id), true),
			'updated' => $ticket->updated_date,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				// [TODO] Translate
				$label = preg_replace(sprintf("#^%s #i", preg_quote($prefix)), '', $label);
				$label = preg_replace(sprintf("#^%s #i", preg_quote('Ticket org')), 'Org', $label);
				
				switch($key) {
					case 'ticket_org__label':
						$label = 'Org';
						break;
						
					case 'worker__label':
						$label = 'Worker';
						break;
						
					case 'ticket_status':
						$label = 'Status';
						break;
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() : array {
		return [
			'ticket_status',
			'ticket__label',
			'ticket_group__label',
			'ticket_bucket__label',
			'ticket_org__label',
			'ticket_updated',
		];
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_Message::getByToken($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($message, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Message:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MESSAGE);

		// Polymorph
		if(is_numeric($message)) {
			$message = DAO_Message::get($message);
		} elseif($message instanceof Model_Message) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($message)) {
			$message = Cerb_ORMHelper::recastArrayToModel($message, 'Model_Message');
		} else {
			$message = null;
		}
		
		/* @var $message Model_Message */
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'html_attachment_id' => $prefix.'HTML Attachment ID', // [TODO] Translate
			'id' => $prefix.$translate->_('common.id'),
			'content' => $prefix.$translate->_('common.content'),
			'content_html' => $prefix.$translate->_('Content HTML'),
			'created' => $prefix.$translate->_('common.created'),
			'is_broadcast' => $prefix.$translate->_('message.is_broadcast'),
			'is_not_sent' => $prefix.$translate->_('message.is_not_sent'),
			'is_outgoing' => $prefix.$translate->_('message.is_outgoing'),
			'response_time' => $prefix.$translate->_('message.response_time'),
			'storage_size' => $prefix.$translate->_('message.storage_size'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'headers' => $prefix.$translate->_('message.headers'),
			'reply_cc' => $prefix."Reply Cc",
			'reply_to' => $prefix."Reply To",
			'signed_at' => $prefix.$translate->_('message.signed_at'),
			'signed_key_fingerprint' => $prefix.$translate->_('message.signed_key_fingerprint'),
			'token' => $prefix.$translate->_('common.token'),
			'was_encrypted' => $prefix.$translate->_('message.is_encrypted'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'html_attachment_id' => Model_CustomField::TYPE_NUMBER,
			'id' => Model_CustomField::TYPE_NUMBER,
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'content_html' => Model_CustomField::TYPE_MULTI_LINE,
			'created' => Model_CustomField::TYPE_DATE,
			'is_broadcast' => Model_CustomField::TYPE_CHECKBOX,
			'is_not_sent' => Model_CustomField::TYPE_CHECKBOX,
			'is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'response_time' => 'time_secs',
			'storage_size' => 'size_bytes',
			'record_url' => Model_CustomField::TYPE_URL,
			'headers' => null,
			'reply_cc' => Model_CustomField::TYPE_SINGLE_LINE,
			'reply_to' => Model_CustomField::TYPE_SINGLE_LINE,
			'signed_at' => Model_CustomField::TYPE_DATE,
			'signed_key_fingerprint' => Model_CustomField::TYPE_SINGLE_LINE,
			'token' => Model_CustomField::TYPE_SINGLE_LINE,
			'was_encrypted' => Model_CustomField::TYPE_CHECKBOX,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_Message::ID;
		$token_values['_type'] = Context_Message::URI;
		
		$token_values['_types'] = $token_types;
		
		// Message token values
		if($message) {
			$token_values['_loaded'] = true;
			$token_values['created'] = $message->created_date;
			$token_values['html_attachment_id'] = $message->html_attachment_id;
			$token_values['id'] = $message->id;
			$token_values['is_broadcast'] = $message->is_broadcast;
			$token_values['is_not_sent'] = $message->is_not_sent;
			$token_values['is_outgoing'] = $message->is_outgoing;
			$token_values['response_time'] = $message->response_time;
			$token_values['sender_id'] = $message->address_id;
			$token_values['storage_size'] = $message->storage_size;
			$token_values['ticket_id'] = $message->ticket_id;
			$token_values['worker_id'] = $message->worker_id;
			$token_values['hash_header_message_id'] = $message->hash_header_message_id;
			$token_values['signed_at'] = $message->signed_at;
			$token_values['signed_key_fingerprint'] = $message->signed_key_fingerprint;
			$token_values['token'] = $message->token;
			$token_values['was_encrypted'] = $message->was_encrypted;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($message, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=message&id=%d", $message->id), true);
			$token_values['ticket_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=ticket&id=%d", $message->ticket_id), true) . '#message' . $message->id;
		}

		$context_stack = CerberusContexts::getStack();
		
		// Only link ticket placeholders if the message isn't nested under a ticket already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_TICKET, $context_stack)) {
			$merge_token_labels = [];
			$merge_token_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, null, $merge_token_labels, $merge_token_values, '', true);
	
			CerberusContexts::merge(
				'ticket_',
				$prefix.'Ticket:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		}
		
		// Sender
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'sender_',
			$prefix.'Sender:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Sender Worker
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'worker_',
			$prefix.'Sender:Worker:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created' => DAO_Message::CREATED_DATE,
			'hash_header_message_id' => DAO_Message::HASH_HEADER_MESSAGE_ID,
			'html_attachment_id' => DAO_Message::HTML_ATTACHMENT_ID,
			'id' => DAO_Message::ID,
			'is_broadcast' => DAO_Message::IS_BROADCAST,
			'is_not_sent' => DAO_Message::IS_NOT_SENT,
			'is_outgoing' => DAO_Message::IS_OUTGOING,
			'links' => '_links',
			'response_time' => DAO_Message::RESPONSE_TIME,
			'sender_id' => DAO_Message::ADDRESS_ID,
			'signed_at' => DAO_Message::SIGNED_AT,
			'signed_key_fingerprint' => DAO_Message::SIGNED_KEY_FINGERPRINT,
			'storage_size' => DAO_Message::STORAGE_SIZE,
			'ticket_id' => DAO_Message::TICKET_ID,
			'was_encrypted' => DAO_Message::WAS_ENCRYPTED,
			'token' => DAO_Message::TOKEN,
			'worker_id' => DAO_Message::WORKER_ID,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['content'] = [
			'key' => 'content',
			'is_immutable' => false,
			'is_required' => true,
			'notes' => 'Message content',
			'type' => 'string',
		];
		
		$keys['content_html'] = [
			'key' => 'content_html',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'Optional alternative content for the HTML version of a message',
			'type' => 'string',
		];
		
		$keys['headers'] = [
			'key' => 'headers',
			'is_immutable' => false,
			'is_required' => true,
			'notes' => 'Message headers',
			'type' => 'string',
		];
		
		$keys['sender'] = [
			'key' => 'sender',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'The [email address](/docs/records/types/address/) of the sender; alternative to `sender_id`',
			'type' => 'string',
		];
		
		$keys['ticket_mask'] = [
			'key' => 'ticket_mask',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'The parent [ticket](/docs/records/types/ticket/) mask; alternative to `ticket_id`',
			'type' => 'string',
		];
		
		$keys['worker'] = [
			'key' => 'worker',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'The [worker](/docs/records/types/worker/) who sent the message (if any); alternative to `worker_id`',
			'type' => 'string',
		];
		
		$keys['hash_header_message_id']['notes'] = "A SHA-1 hash of the `Message-Id:` header; used for message threading";
		$keys['html_attachment_id']['notes'] = "The [attachment](/docs/records/types/attachment/) ID containing the HTML message content";
		$keys['is_broadcast']['notes'] = "Was this message sent using the broadcast feature?";
		$keys['is_not_sent']['notes'] = "Was this message saved without sending?";
		$keys['is_outgoing']['notes'] = "Was this an outgoing reply from a worker?";
		$keys['response_time']['notes'] = "Response time in seconds";
		$keys['sender_id']['notes'] = "The ID of the sender's [email address](/docs/records/types/address/) record";
		$keys['signed_at']['notes'] = "The date the message was cryptographically signed";
		$keys['signed_key_fingerprint']['notes'] = "The key that cryptographically signed this message";
		$keys['storage_size']['notes'] = "Size of the message in bytes";
		$keys['ticket_id']['notes'] = "The ID of the message's [ticket](/docs/records/types/ticket/) record";
		$keys['token']['notes'] = "A random unique identifier for the message (synchronized with draft)";
		$keys['was_encrypted']['notes'] = "Was the message sent encrypted?";
		$keys['worker_id']['notes'] = "If outgoing, the ID of the [worker](/docs/records/types/worker/) who sent the message";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'content':
				$out_fields[DAO_Message::_CONTENT] = $value;
				break;
				
			case 'content_html':
				$out_fields[DAO_Message::_CONTENT_HTML] = $value;
				break;
				
			case 'headers':
				$out_fields[DAO_Message::_HEADERS] = $value;
				break;
				
				
			case 'sender':
				if(false == ($address = DAO_Address::lookupAddress($value, true))) {
					$error = sprintf("Failed to lookup address: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Message::ADDRESS_ID] = $address->id;
				break;
				
			case 'ticket_mask':
				if(false == ($ticket = DAO_Ticket::getTicketByMask($value))) {
					$error = sprintf("Failed to lookup ticket mask : %s", $value);
					return false;
				}
				
				$out_fields[DAO_Message::TICKET_ID] = $ticket->id;
				break;
				
			case 'worker':
				if(
					false == ($workers = DAO_Worker::getByString($value, true)) 
					|| !is_array($workers) 
					|| 1 != count($workers)) 
				{
					$error = sprintf("Failed to lookup worker: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Message::WORKER_ID] = key($workers);
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['content'] = [
			'label' => 'Content',
			'type' => 'Text',
		];
		
		$lazy_keys['content_html'] = [
			'label' => 'Content (HTML)',
			'type' => 'Text',
		];
		
		$lazy_keys['headers'] = [
			'label' => 'Headers',
			'type' => 'HashMap',
		];
		
		$lazy_keys['reply_cc'] = [
			'label' => '`Cc:` recipients (comma-separated)',
			'type' => 'Text',
		];
		
		$lazy_keys['reply_to'] = [
			'label' => '`To:` recipients (comma-separated)',
			'type' => 'Text',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_MESSAGE;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
			$dictionary = $values;
		}
		
		switch($token) {
			case '_label':
				$dict = DevblocksDictionaryDelegate::instance($dictionary);
				
				$sender_address = $dict->sender_address;
				$ticket_label = $dict->ticket__label;
				
				$values = array_merge($dict->getDictionary(), $values);
				
				$values['_label'] = sprintf("%s wrote on %s", $sender_address, $ticket_label);
				break;
			
			case 'content':
				// [TODO] Allow an array with storage meta here?  It removes an extra (n) SELECT in dictionaries for content
				$values['content'] = Storage_MessageContent::get($context_id);
				break;
				
			case 'content_html':
				if(
					!($dictionary['html_attachment_id'] ?? 0)
					|| !($html_part = DAO_Attachment::get($dictionary['html_attachment_id']))
					)
					break;
				
				$values['content_html'] = $html_part->getFileContents();
				break;
				
			case 'headers':
				$headers = DAO_MessageHeaders::getAll($context_id);
				$values['headers'] = $headers;
				break;
				
			case 'reply_to':
				$dict = DevblocksDictionaryDelegate::instance($dictionary);
				$message_headers = $dict->headers;
				$values['reply_to'] = '';
				
				if(isset($message_headers['to'])) {
					$from = isset($message_headers['reply-to']) ? $message_headers['reply-to'] : $message_headers['from'];
					$addys = CerberusMail::parseRfcAddresses($from . ', ' . $message_headers['to'], true);
					$recipients = [];
					
					if(is_array($addys))
					foreach($addys as $addy) {
						$recipients[] = $addy['full_email'];
					}
					
					$values['reply_to'] = implode(', ', $recipients);
				}
				break;
				
			case 'reply_cc':
				$dict = DevblocksDictionaryDelegate::instance($dictionary);
				$message_headers = $dict->headers;
				$values['reply_cc'] = '';
				
				if(isset($message_headers['cc'])) {
					$addys = CerberusMail::parseRfcAddresses($message_headers['cc'], true);
					$recipients = [];
					
					if(is_array($addys))
					foreach($addys as $addy) {
						$recipients[] = $addy['full_email'];
					}
					
					$values['reply_cc'] = implode(', ', $recipients);
				}
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
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = DevblocksPlatform::translateCapitalized('common.messages');
		$view->removeAllParams();
		
		$view->renderSortBy = SearchFields_Message::CREATED_DATE;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.messages');
		
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
		$context = CerberusContexts::CONTEXT_MESSAGE;
		
		$tpl->assign('view_id', $view_id);
		
		$message = null;
		
		if($context_id) {
			if(false == ($message = DAO_Message::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$tpl->assign('model', $message);
		}
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MESSAGE, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if($context_id) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MESSAGE, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		
		if(!$context_id || $edit) {
			if($message) {
				if(!Context_Message::isWriteableByActor($message, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			$tpl->display('devblocks:cerberusweb.core::internal/messages/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $message);
		}
	}
	
	public function profileGetFields($model = null) {
		$properties = [];
		
		/* @var $model Model_Message */
		
		if(is_null($model))
			$model = new Model_Message();
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_date,
			'params' => [],
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
			'params' => [],
		];
		
		$properties['is_broadcast'] = [
			'label' => DevblocksPlatform::translate('message.is_broadcast'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_broadcast,
			'params' => [],
		];
		
		$properties['is_not_sent'] = [
			'label' => DevblocksPlatform::translate('message.is_not_sent'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_not_sent,
			'params' => [],
		];
		
		$properties['is_outgoing'] = [
			'label' => DevblocksPlatform::translate('message.is_outgoing'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_outgoing,
			'params' => [],
		];
		
		$properties['response_time'] = [
			'label' => DevblocksPlatform::translate('message.response_time'),
			'type' => 'time_secs',
			'value' => $model->response_time,
			'params' => [],
		];
		
		$properties['sender'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.sender'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->address_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			],
		];
		
		$properties['signed_at'] = [
			'label' => DevblocksPlatform::translateCapitalized('message.signed_at'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->signed_at,
			'params' => [],
		];
		
		$properties['signed_key_fingerprint'] = [
			'label' => DevblocksPlatform::translateCapitalized('message.signed_key_fingerprint'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->signed_key_fingerprint,
			'params' => [],
		];
		
		$properties['ticket'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.ticket'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->ticket_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_TICKET,
			],
		];
		
		$properties['ticket_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('ticket.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->ticket_id,
			'params' => [],
		];
		
		$properties['token'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.token'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->token,
			'params' => [],
		];
		
		$properties['was_encrypted'] = [
			'label' => DevblocksPlatform::translateCapitalized('message.is_encrypted'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->was_encrypted,
			'params' => [],
		];
		
		$properties['worker'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.worker'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->worker_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_WORKER,
			],
		];
		
		$properties['worker_id'] = [
			'label' => 'Worker ID',
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->worker_id,
			'params' => [],
		];
		
		return $properties;
	}

	public function profileGetUrl($context_id) {
		$url_writer = DevblocksPlatform::services()->url();
		
		if(empty($context_id))
			return '';
		
		return $url_writer->writeNoProxy('c=profiles&type=message&id='.$context_id, true);
	}
};