<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	const STORAGE_SIZE = 'storage_size';
	const TICKET_ID = 'ticket_id';
	const WAS_ENCRYPTED = 'was_encrypted';
	const WAS_SIGNED = 'was_signed';
	const WORKER_ID = 'worker_id';
	const _CONTENT = '_content';
	const _HEADERS = '_headers';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::ADDRESS_ID)
			->id()
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
			;
		// tinyint(1)
		$validation
			->addField(self::WAS_ENCRYPTED)
			->bit()
			;
		// tinyint(1)
		$validation
			->addField(self::WAS_SIGNED)
			->bit()
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKER_ID)
			->id()
			;
		// text
		$validation
			->addField(self::_CONTENT)
			->string()
			->setMaxLength(16777215)
			;
		// text
		$validation
			->addField(self::_HEADERS)
			->string()
			->setMaxLength(16777215)
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO message () VALUES ()";
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();

		self::update($id, $fields);
		
		if(isset($fields[self::TICKET_ID])) {
			DAO_Ticket::updateMessageCount($fields[self::TICKET_ID]);
		}
		
		return $id;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = [$ids];
		
		if(isset($fields[self::_CONTENT])) {
			foreach($ids as $id)
				Storage_MessageContent::put($id, $fields[self::_CONTENT]);
			unset($fields[self::_CONTENT]);
		}
		
		if(isset($fields[self::_HEADERS])) {
			foreach($ids as $id)
				DAO_MessageHeaders::upsert($id, $fields[self::_HEADERS]);
			unset($fields[self::_HEADERS]);
		}
		
		parent::_update($ids, 'message', $fields);
	}
	
	static function onUpdateAbstract($id, $fields) {
		if(isset($fields[self::TICKET_ID])) {
			DAO_Ticket::rebuild($fields[self::TICKET_ID]);
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
		$sql = "SELECT id, ticket_id, created_date, is_outgoing, worker_id, html_attachment_id, address_id, storage_extension, storage_key, storage_profile_id, storage_size, response_time, is_broadcast, is_not_sent, hash_header_message_id, was_encrypted, was_signed ".
			"FROM message ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
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
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Message[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
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
			$object->was_encrypted = !empty($row['was_encrypted']) ? 1 : 0;
			$object->was_signed = !empty($row['was_signed']) ? 1 : 0;
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
	
	static function countByTicketId($ticket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM message WHERE ticket_id = %d",
			$ticket_id
		);
		return intval($db->GetOneSlave($sql));
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = array($ids);
		
		if(empty($ids))
			return array();
		
		$ids_list = implode(',', $ids);

		$messages = DAO_Message::getWhere(sprintf("%s IN (%s)",
			DAO_Message::ID,
			$ids_list
		));

		// Message Headers
		DAO_MessageHeaders::delete($ids);
		
		// Message Content
		Storage_MessageContent::delete($ids);
		
		// Search indexes
		$search = Extension_DevblocksSearchSchema::get(Search_MessageContent::ID, true);
		$search->delete($ids);
		
		// Messages
		$sql = sprintf("DELETE FROM message WHERE id IN (%s)",
			$ids_list
		);
		$db->ExecuteMaster($sql);
		
		// Remap first/last on ticket
		foreach($messages as $message_id => $message) {
			DAO_Ticket::rebuild($message->ticket_id);
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_MESSAGE,
					'context_ids' => $ids
				)
			)
		);
	}

	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		// Purge message content (storage)
		$db->ExecuteMaster("CREATE TEMPORARY TABLE _tmp_maint_message (PRIMARY KEY (id)) SELECT id FROM message WHERE ticket_id NOT IN (SELECT id FROM ticket)");
		
		$sql = "SELECT id FROM _tmp_maint_message";
		
		if(false == ($rs = $db->ExecuteMaster($sql)))
			return false;

		$ids_buffer = array();
		$count = 0;
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$ids_buffer[$count++] = $row['id'];
			
			// Flush buffer every 50
			if(0 == $count % 50) {
				Storage_MessageContent::delete($ids_buffer);
				$ids_buffer = array();
				$count = 0;
			}
		}
		mysqli_free_result($rs);

		// Any remainder
		if(!empty($ids_buffer)) {
			Storage_MessageContent::delete($ids_buffer);
			unset($ids_buffer);
			unset($count);
		}

		// Purge messages without linked tickets
		$db->ExecuteMaster("DELETE message FROM message INNER JOIN _tmp_maint_message ON (_tmp_maint_message.id=message.id)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message records.');
		
		// Headers
		$db->ExecuteMaster("DELETE message_headers FROM message_headers INNER JOIN _tmp_maint_message ON (_tmp_maint_message.id=message_headers.message_id)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message_headers records.');

		// Search indexes
		if(isset($tables['fulltext_message_content'])) {
			$db->ExecuteMaster("DELETE fulltext_message_content FROM fulltext_message_content INNER JOIN _tmp_maint_message ON (_tmp_maint_message.id=fulltext_message_content.id)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_message_content records.');
		}
		
		$db->ExecuteMaster("DROP TABLE _tmp_maint_message");
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_MESSAGE,
					'context_table' => 'message',
					'context_key' => 'id',
				)
			)
		);
	}

	public static function random() {
		return self::_getRandom('message');
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Message::getFields();
		
		list($tables,$wheres,$selects) = parent::_parseSearchParams($params, array(), 'SearchFields_Message', $sortBy);

		$select_sql = sprintf("SELECT ".
			"m.id as %s, ".
			"m.address_id as %s, ".
			"m.created_date as %s, ".
			"m.is_outgoing as %s, ".
			"m.ticket_id as %s, ".
			"m.worker_id as %s, ".
			"m.html_attachment_id as %s, ".
			"m.storage_extension as %s, ".
			"m.storage_key as %s, ".
			"m.storage_profile_id as %s, ".
			"m.storage_size as %s, ".
			"m.response_time as %s, ".
			"m.is_broadcast as %s, ".
			"m.is_not_sent as %s, ".
			"m.was_encrypted as %s, ".
			"m.was_signed as %s ",
			SearchFields_Message::ID,
			SearchFields_Message::ADDRESS_ID,
			SearchFields_Message::CREATED_DATE,
			SearchFields_Message::IS_OUTGOING,
			SearchFields_Message::TICKET_ID,
			SearchFields_Message::WORKER_ID,
			SearchFields_Message::HTML_ATTACHMENT_ID,
			SearchFields_Message::STORAGE_EXTENSION,
			SearchFields_Message::STORAGE_KEY,
			SearchFields_Message::STORAGE_PROFILE_ID,
			SearchFields_Message::STORAGE_SIZE,
			SearchFields_Message::RESPONSE_TIME,
			SearchFields_Message::IS_BROADCAST,
			SearchFields_Message::IS_NOT_SENT,
			SearchFields_Message::WAS_ENCRYPTED,
			SearchFields_Message::WAS_SIGNED
		);
		
		$join_sql = "FROM message m ".
			(isset($tables['t']) ? "INNER JOIN ticket t ON (m.ticket_id = t.id) " : " ").
			(isset($tables['a']) ? "INNER JOIN address a ON (m.address_id = a.id) " : " ")
			;
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Message');
		
		$result = array(
			'primary_table' => 'm',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
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
		
		$fulltext_params = array();
		
		foreach($params as $param_key => $param) {
			if(!($param instanceof DevblocksSearchCriteria))
				continue;
			
			if($param->field == SearchFields_Message::MESSAGE_CONTENT) {
				$fulltext_params[$param_key] = $param;
				unset($params[$param_key]);
			}
		}
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		// [TODO] This is only needed in <= 7.2.5, not 7.3 release
		if(isset($params['req_*_in_groups_of_worker']))
			unset($params['req_*_in_groups_of_worker']);
		
		if(!empty($fulltext_params)) {
			$prefetch_sql = null;
			
			if(!empty($params)) {
				$prefetch_sql = 
					sprintf('SELECT message.id FROM message INNER JOIN (SELECT m.id %s%s ORDER BY id DESC LIMIT 20000) AS search ON (search.id=message.id)',
						$join_sql,
						$where_sql
					);
			}
			
			// Restrict the scope of the fulltext search to these IDs
			if($prefetch_sql) {
				foreach($fulltext_params as $param_key => $param) {
					$where_sql .= 'AND ' . SearchFields_Message::getWhereSQL($param, array('prefetch_sql' => $prefetch_sql)) . ' ';
				}
			} else {
				foreach($fulltext_params as $param_key => $param) {
					$where_sql .= 'AND ' . SearchFields_Message::getWhereSQL($param) . ' ';
				}
			}
		}
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Message::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(m.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}

		mysqli_free_result($rs);
		
		return array($results,$total);
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
	const WAS_ENCRYPTED = 'm_was_encrypted';
	const WAS_SIGNED = 'm_was_signed';
	
	// Storage
	const STORAGE_EXTENSION = 'm_storage_extension';
	const STORAGE_KEY = 'm_storage_key';
	const STORAGE_PROFILE_ID = 'm_storage_profile_id';
	const STORAGE_SIZE = 'm_storage_size';
	
	// Fulltexts
	const MESSAGE_CONTENT = 'ftmc_content';
	const FULLTEXT_NOTE_CONTENT = 'ftnc_content';
	
	// Address
	const ADDRESS_EMAIL = 'a_email';
	
	// Ticket
	const TICKET_GROUP_ID = 't_group_id';
	const TICKET_STATUS_ID = 't_status_id';
	const TICKET_MASK = 't_mask';
	const TICKET_SUBJECT = 't_subject';
	
	// Virtuals
	const VIRTUAL_ATTACHMENTS_SEARCH = '*_attachments_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_HEADER_MESSAGE_ID = '*_header_message_id';
	const VIRTUAL_SENDER_SEARCH = '*_sender_search';
	const VIRTUAL_TICKET_SEARCH = '*_ticket_search';
	const VIRTUAL_WORKER_SEARCH = '*_worker_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'm.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_MESSAGE => new DevblocksSearchFieldContextKeys('m.id', self::ID),
			CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('m.address_id', self::ADDRESS_ID),
			CerberusContexts::CONTEXT_GROUP => new DevblocksSearchFieldContextKeys('t.group_id', self::TICKET_GROUP_ID),
			CerberusContexts::CONTEXT_TICKET => new DevblocksSearchFieldContextKeys('m.ticket_id', self::TICKET_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param, $options=array()) {
		if(!is_array($options))
			$options = array();
		
		switch($param->field) {
			case self::VIRTUAL_ATTACHMENTS_SEARCH:
				return self::_getWhereSQLFromAttachmentsField($param, CerberusContexts::CONTEXT_MESSAGE, self::getPrimaryKey());
				break;
				
			case self::MESSAGE_CONTENT:
				return self::_getWhereSQLFromFulltextField($param, Search_MessageContent::ID, self::getPrimaryKey(), $options);
				break;
				
			case self::FULLTEXT_NOTE_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_MESSAGE, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_MESSAGE, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HEADER_MESSAGE_ID:
				$value = $param->value;
				
				if(DevblocksPlatform::strStartsWith($value, '<'))
					$value = sha1($value);
				
				if(false !== strpos($value, '*')) {
					return sprintf("m.hash_header_message_id LIKE %s",
						Cerb_ORMHelper::qstr(str_replace('*','%',$value))
					);
				} else {
					return sprintf("m.hash_header_message_id = %s",
						Cerb_ORMHelper::qstr($value)
					);
				}
				
				break;
				
			case self::VIRTUAL_SENDER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'm.address_id');
				break;
				
			case self::VIRTUAL_TICKET_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_TICKET, 'm.ticket_id');
				break;
				
			case self::VIRTUAL_WORKER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 'm.worker_id');
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
			SearchFields_Message::ID => new DevblocksSearchField(SearchFields_Message::ID, 'm', 'id', $translate->_('common.id'), null, true),
			SearchFields_Message::ADDRESS_ID => new DevblocksSearchField(SearchFields_Message::ADDRESS_ID, 'm', 'address_id', $translate->_('common.sender'), true),
			SearchFields_Message::CREATED_DATE => new DevblocksSearchField(SearchFields_Message::CREATED_DATE, 'm', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Message::IS_OUTGOING => new DevblocksSearchField(SearchFields_Message::IS_OUTGOING, 'm', 'is_outgoing', $translate->_('message.is_outgoing'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::TICKET_ID => new DevblocksSearchField(SearchFields_Message::TICKET_ID, 'm', 'ticket_id', 'Ticket ID', null, true),
			SearchFields_Message::WORKER_ID => new DevblocksSearchField(SearchFields_Message::WORKER_ID, 'm', 'worker_id', $translate->_('common.worker'), Model_CustomField::TYPE_WORKER, true),
			SearchFields_Message::HTML_ATTACHMENT_ID => new DevblocksSearchField(SearchFields_Message::HTML_ATTACHMENT_ID, 'm', 'html_attachment_id', null, null, true),
			SearchFields_Message::RESPONSE_TIME => new DevblocksSearchField(SearchFields_Message::RESPONSE_TIME, 'm', 'response_time', $translate->_('message.response_time'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Message::IS_BROADCAST => new DevblocksSearchField(SearchFields_Message::IS_BROADCAST, 'm', 'is_broadcast', $translate->_('message.is_broadcast'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::IS_NOT_SENT => new DevblocksSearchField(SearchFields_Message::IS_NOT_SENT, 'm', 'is_not_sent', $translate->_('message.is_not_sent'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::WAS_ENCRYPTED => new DevblocksSearchField(SearchFields_Message::WAS_ENCRYPTED, 'm', 'was_encrypted', $translate->_('message.is_encrypted'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::WAS_SIGNED => new DevblocksSearchField(SearchFields_Message::WAS_SIGNED, 'm', 'was_signed', $translate->_('message.is_signed'), Model_CustomField::TYPE_CHECKBOX, true),
			
			SearchFields_Message::STORAGE_EXTENSION => new DevblocksSearchField(SearchFields_Message::STORAGE_EXTENSION, 'm', 'storage_extension', null, true),
			SearchFields_Message::STORAGE_KEY => new DevblocksSearchField(SearchFields_Message::STORAGE_KEY, 'm', 'storage_key', null, true),
			SearchFields_Message::STORAGE_PROFILE_ID => new DevblocksSearchField(SearchFields_Message::STORAGE_PROFILE_ID, 'm', 'storage_profile_id', null, true),
			SearchFields_Message::STORAGE_SIZE => new DevblocksSearchField(SearchFields_Message::STORAGE_SIZE, 'm', 'storage_size', null, true),
			
			SearchFields_Message::ADDRESS_EMAIL => new DevblocksSearchField(SearchFields_Message::ADDRESS_EMAIL, 'a', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE, false),
			
			SearchFields_Message::TICKET_GROUP_ID => new DevblocksSearchField(SearchFields_Message::TICKET_GROUP_ID, 't', 'group_id', $translate->_('common.group'), null, false),
			SearchFields_Message::TICKET_STATUS_ID => new DevblocksSearchField(SearchFields_Message::TICKET_STATUS_ID, 't', 'status_id', $translate->_('common.status'), Model_CustomField::TYPE_NUMBER, false),
			SearchFields_Message::TICKET_MASK => new DevblocksSearchField(SearchFields_Message::TICKET_MASK, 't', 'mask', $translate->_('ticket.mask'), Model_CustomField::TYPE_SINGLE_LINE, false),
			SearchFields_Message::TICKET_SUBJECT => new DevblocksSearchField(SearchFields_Message::TICKET_SUBJECT, 't', 'subject', $translate->_('ticket.subject'), Model_CustomField::TYPE_SINGLE_LINE, false),
			
			SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH, '*', 'attachments_search', null, null, false),
			SearchFields_Message::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(SearchFields_Message::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID => new DevblocksSearchField(SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID, '*', 'header_message_id', $translate->_('message.search.header_message_id'), Model_CustomField::TYPE_SINGLE_LINE, false),
			SearchFields_Message::VIRTUAL_SENDER_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_SENDER_SEARCH, '*', 'sender_search', null, null, false),
			SearchFields_Message::VIRTUAL_TICKET_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_TICKET_SEARCH, '*', 'ticket_search', null, null, false),
			SearchFields_Message::VIRTUAL_WORKER_SEARCH => new DevblocksSearchField(SearchFields_Message::VIRTUAL_WORKER_SEARCH, '*', 'worker_search', null, null, false),
				
			SearchFields_Message::MESSAGE_CONTENT => new DevblocksSearchField(SearchFields_Message::MESSAGE_CONTENT, 'ftmc', 'content', $translate->_('common.content'), 'FT', false),
			SearchFields_Message::FULLTEXT_NOTE_CONTENT => new DevblocksSearchField(self::FULLTEXT_NOTE_CONTENT, 'ftnc', 'content', $translate->_('message.note.content'), 'FT', false),
		);

		// Fulltext indexes
		
		$columns[self::MESSAGE_CONTENT]->ft_schema = Search_MessageContent::ID;
		$columns[self::FULLTEXT_NOTE_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Message {
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
	public $was_encrypted;
	public $was_signed;
	
	private $_sender_object = null;
	private $_headers_raw = null;

	function getContent(&$fp=null) {
		if(empty($this->storage_extension) || empty($this->storage_key))
			return '';

		return Storage_MessageContent::get($this, $fp);
	}
	
	function getContentAsHtml() {
		// If we don't have an HTML part, or the given ID fails to load, HTMLify the regular content
		if(empty($this->html_attachment_id) 
			|| false == ($attachment = DAO_Attachment::get($this->html_attachment_id))) {
				return false;
		}
		
		// If attachment size is more than 1MB, fall back to plaintext
		if($attachment->storage_size > 1000000)
			return false;
		
		// If the attachment is inaccessible, fallback to plaintext 
		if(false == ($dirty_html = $attachment->getFileContents()))
			return false;
		
		// If the 'tidy' extension exists
		if(extension_loaded('tidy')) {
			$tidy = new tidy();
			
			$config = array (
				'bare' => true,
				'clean' => true,
				'drop-proprietary-attributes' => true,
				'indent' => false,
				'output-xhtml' => true,
				'wrap' => 0,
			);
			
			// If we're not stripping Microsoft Office formatting
			if(DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::HTML_NO_STRIP_MICROSOFT, CerberusSettingsDefaults::HTML_NO_STRIP_MICROSOFT)) {
				unset($config['bare']);
				unset($config['drop-proprietary-attributes']);
			}
			
			$dirty_html = $tidy->repairString($dirty_html, $config, DB_CHARSET_CODE);
		}
		
		$options = array(
			'HTML.TargetBlank' => true,
		);
		
		$dirty_html = DevblocksPlatform::purifyHTML($dirty_html, true, $options);
		return $dirty_html;
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
	
	function getWorker() {
		if(empty($this->worker_id))
			return null;
		
		return DAO_Worker::get($this->worker_id);
	}
	
	/**
	 * returns an array of the message's attachments
	 *
	 * @return Model_Attachment[]
	 */
	function getAttachments() {
		return DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, $this->id);
	}
	
	/**
	 * @return Model_Ticket
	 */
	function getTicket() {
		return DAO_Ticket::get($this->ticket_id);
	}
	
	function getTimeline($is_ascending=true) {
		$timeline = [
			$this,
		];
		
		if(false != ($comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, $this->id)))
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

class Search_MessageContent extends Extension_DevblocksSearchSchema {
	const ID = 'cerberusweb.search.schema.message_content';
	
	public function getNamespace() {
		return 'message_content';
	}
	
	public function getAttributes() {
		return array();
	}
	
	public function getFields() {
		return array(
			'content',
			'created',
		);
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the engine can tell us where the index left off
		if(isset($meta['max_id']) && $meta['max_id']) {
			$this->setParam('last_indexed_id', $meta['max_id']);
		
		// If the index has a delta, start from the current record
		} elseif($meta['is_indexed_externally']) {
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
				if(null != ($last_msgs = DAO_Message::getWhere('id is not null', 'id', false, 1))
					&& is_array($last_msgs)
					&& null != ($last_msg = array_shift($last_msgs))) {
						$this->setParam('last_indexed_id', $last_msg->id);
						$this->setParam('last_indexed_time', $last_msg->created_date);
				} else {
					$this->setParam('last_indexed_id', 0);
					$this->setParam('last_indexed_time', 0);
				}
				break;
		}
	}
	
	public function query($query, $attributes=array(), $limit=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ids = $engine->query($this, $query, $attributes, $limit);
		return $ids;
	}
	
	private function _indexDictionary($dict, $engine) {
		$logger = DevblocksPlatform::services()->log();

		$id = $dict->id;
		
		if(empty($id))
			return false;
		
		$content = $dict->content;
		
		// Strip reply quotes
		$content = preg_replace("/(^\>(.*)\$)/m", "", $content);
		$content = preg_replace("/[\r\n]+/", "\n", $content);
		
		// Truncate to 5KB
		$content = $engine->truncateOnWhitespace($content, 5000);
		
		$doc = array(
			'created' => $dict->created,
		);
		
		$doc['content'] = implode("\n", array(
			$dict->sender__label,
			$dict->ticket_subject,
			$dict->ticket_mask,
			$dict->ticket_org__label,
			$content,
		));
		
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
		
		if(false == ($models = DAO_Message::getIds($ids)))
			return;
		
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_MESSAGE, array('ticket_','ticket_org_','sender_','content'));
		
		if(empty($dicts))
			return;
		
		foreach($dicts as $dict) {
			$this->_indexDictionary($dict, $engine);
		}
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::services()->log();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'last_indexed_id', 0);
		$done = false;
		
		while(!$done && time() < $stop_time) {
			$where = sprintf("%s > %d", DAO_Message::ID, $id);
			$models = DAO_Message::getWhere($where, 'id', true, 100);
			
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_MESSAGE, array('ticket_','ticket_org_','sender_','content'));
			
			if(empty($dicts)) {
				$done = true;
				continue;
			}
			
			// Loop dictionaries
			foreach($dicts as $dict) {
				$id = $dict->id;
				
				if(false == $this->_indexDictionary($dict, $engine))
					return false;
			}
			
			// Record our index every batch
			if(!empty($id))
				DAO_DevblocksExtensionPropertyStore::put(self::ID, 'last_indexed_id', $id);
		}
	}
	
	public function delete($ids) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		return $engine->delete($this, $ids);
	}
};

class Storage_MessageContent extends Extension_DevblocksStorageSchema {
	const ID = 'cerberusweb.storage.schema.message_content';
	
	public static function getActiveStorageProfile() {
		return DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile', 'devblocks.storage.engine.database');
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days'));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/message_content/render.tpl");
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days'));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/message_content/config.tpl");
	}
	
	function saveConfig() {
		@$active_storage_profile = DevblocksPlatform::importGPC($_REQUEST['active_storage_profile'],'string','');
		@$archive_storage_profile = DevblocksPlatform::importGPC($_REQUEST['archive_storage_profile'],'string','');
		@$archive_after_days = DevblocksPlatform::importGPC($_REQUEST['archive_after_days'],'integer',0);
		
		if(!empty($active_storage_profile))
			$this->setParam('active_storage_profile', $active_storage_profile);
		
		if(!empty($archive_storage_profile))
			$this->setParam('archive_storage_profile', $archive_storage_profile);

		$this->setParam('archive_after_days', $archive_after_days);
		
		return true;
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
		
		if($profile instanceof Model_DevblocksStorageProfile) {
			$profile_id = $profile->id;
		} elseif(is_numeric($profile)) {
			$profile_id = intval($profile_id);
		} elseif(is_string($profile)) {
			$profile_id = 0;
		}
		
		$storage = DevblocksPlatform::getStorageService($profile);

		if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
			
		} else {
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

	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT storage_extension, storage_key, storage_profile_id FROM message WHERE id IN (%s)", implode(',',$ids));

		if(false == ($rs = $db->ExecuteMaster($sql)))
			return false;
		
		// Delete the physical files
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$profile = !empty($row['storage_profile_id']) ? $row['storage_profile_id'] : $row['storage_extension'];
			if(null != ($storage = DevblocksPlatform::getStorageService($profile)))
				$storage->delete('message_content', $row['storage_key']);
		}
		
		mysqli_free_result($rs);
		
		return true;
	}
	
	public function getStats() {
		return $this->_stats('message');
	}
		
	public static function archive($stop_time=null) {
		$db = DevblocksPlatform::services()->database();
		
		// Params
		$src_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile'));
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
				
		if(empty($src_profile) || empty($dst_profile))
			return;

		if(json_encode($src_profile) == json_encode($dst_profile))
			return;
		
		// Find inactive attachments
		$sql = sprintf("SELECT message.id, message.storage_extension, message.storage_key, message.storage_profile_id, message.storage_size ".
			"FROM message ".
			"INNER JOIN ticket ON (ticket.id=message.ticket_id) ".
			"WHERE ticket.status_id != 3 ".
			"AND ticket.updated_date < %d ".
			"AND (message.storage_extension = %s AND message.storage_profile_id = %d) ".
			"ORDER BY message.id ASC ",
				time()-(86400*$archive_after_days),
				$db->qstr($src_profile->extension_id),
				$src_profile->id
		);
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row);

			if(time() > $stop_time)
				return;
		}
	}
	
	public static function unarchive($stop_time=null) {
		// We don't want to unarchive message content under any condition
		/*
		$db = DevblocksPlatform::services()->database();
		
		// Params
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
				
		if(empty($dst_profile))
			return;
		
		// Find active attachments
		$sql = sprintf("SELECT message.id, message.storage_extension, message.storage_key, message.storage_profile_id, message.storage_size ".
			"FROM message ".
			"INNER JOIN ticket ON (ticket.id=message.ticket_id) ".
			"WHERE ticket.status_id != 3 ".
			"AND ticket.updated_date >= %d ".
			"AND NOT (message.storage_extension = %s AND message.storage_profile_id = %d) ".
			"ORDER BY message.id DESC ",
				time()-(86400*$archive_after_days),
				$db->qstr($dst_profile->extension_id),
				$dst_profile->id
		);
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row, true);
			
			if(time() > $stop_time)
				return;
		}
		*/
	}
	
	private static function _migrate($dst_profile, $row, $is_unarchive=false) {
		$logger = DevblocksPlatform::services()->log();
		
		$ns = 'message_content';
		
		$src_key = $row['storage_key'];
		$src_id = $row['id'];
		$src_size = $row['storage_size'];
		
		$src_profile = new Model_DevblocksStorageProfile();
		$src_profile->id = $row['storage_profile_id'];
		$src_profile->extension_id = $row['storage_extension'];
		
		if(empty($src_key) || empty($src_id)
			|| !$src_profile instanceof Model_DevblocksStorageProfile
			|| !$dst_profile instanceof Model_DevblocksStorageProfile
			)
			return;
		
		$src_engine = DevblocksPlatform::getStorageService(!empty($src_profile->id) ? $src_profile->id : $src_profile->extension_id);
		
		$logger->info(sprintf("[Storage] %s %s %d (%d bytes) from (%s) to (%s)...",
			(($is_unarchive) ? 'Unarchiving' : 'Archiving'),
			$ns,
			$src_id,
			$src_size,
			$src_profile->extension_id,
			$dst_profile->extension_id
		));

		// Do as quicker strings if under 1MB?
		$is_small = ($src_size < (1024 * 1000)) ? true : false;
		
		// Allocate a temporary file for retrieving content
		if($is_small) {
			if(false === ($data = $src_engine->get($ns, $src_key))) {
				$logger->error(sprintf("[Storage] Error reading %s key (%s) from (%s)",
					$ns,
					$src_key,
					$src_profile->extension_id
				));
				return;
			}
		} else {
			$fp_in = DevblocksPlatform::getTempFile();
			if(false === $src_engine->get($ns, $src_key, $fp_in)) {
				$logger->error(sprintf("[Storage] Error reading %s key (%s) from (%s)",
					$ns,
					$src_key,
					$src_profile->extension_id
				));
				return;
			}
		}

		if($is_small) {
			$loaded_size = strlen($data);
		} else {
			$stats_in = fstat($fp_in);
			$loaded_size = $stats_in['size'];
		}
		
		$logger->info(sprintf("[Storage] Loaded %d bytes of data from (%s)...",
			$loaded_size,
			$src_profile->extension_id
		));
		
		if($is_small) {
			if(false === ($dst_key = self::put($src_id, $data, $dst_profile))) {
				$logger->error(sprintf("[Storage] Error saving %s %d to (%s)",
					$ns,
					$src_id,
					$dst_profile->extension_id
				));
				unset($data);
				return;
			}
		} else {
			if(false === ($dst_key = self::put($src_id, $fp_in, $dst_profile))) {
				$logger->error(sprintf("[Storage] Error saving %s %d to (%s)",
					$ns,
					$src_id,
					$dst_profile->extension_id
				));
				if(is_resource($fp_in))
					fclose($fp_in);
				return;
			}
		}
		
		$logger->info(sprintf("[Storage] Saved %s %d to destination (%s) as key (%s)...",
			$ns,
			$src_id,
			$dst_profile->extension_id,
			$dst_key
		));
		
		// Free resources
		if($is_small) {
			unset($data);
		} else {
			@unlink(DevblocksPlatform::getTempFileInfo($fp_in));
			if(is_resource($fp_in))
				fclose($fp_in);
		}
		
		$src_engine->delete($ns, $src_key);
		$logger->info(sprintf("[Storage] Deleted %s %d from source (%s)...",
			$ns,
			$src_id,
			$src_profile->extension_id
		));
		
		$logger->info(''); // blank
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

		$this->view_columns = array(
			SearchFields_Message::ADDRESS_EMAIL,
			SearchFields_Message::TICKET_GROUP_ID,
			SearchFields_Message::WORKER_ID,
			SearchFields_Message::CREATED_DATE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Message::FULLTEXT_NOTE_CONTENT,
			SearchFields_Message::HTML_ATTACHMENT_ID,
			SearchFields_Message::ID,
			SearchFields_Message::MESSAGE_CONTENT,
			SearchFields_Message::STORAGE_EXTENSION,
			SearchFields_Message::STORAGE_KEY,
			SearchFields_Message::STORAGE_PROFILE_ID,
			SearchFields_Message::STORAGE_SIZE,
			SearchFields_Message::TICKET_STATUS_ID,
			SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH,
			SearchFields_Message::VIRTUAL_CONTEXT_LINK,
			SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID,
			SearchFields_Message::VIRTUAL_TICKET_SEARCH,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Message::ADDRESS_ID,
			SearchFields_Message::HTML_ATTACHMENT_ID,
			SearchFields_Message::ID,
			SearchFields_Message::TICKET_STATUS_ID,
			SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH,
			SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID,
			SearchFields_Message::VIRTUAL_TICKET_SEARCH,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Message::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Message');
		
		return $objects;
	}

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Message', $ids);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Message::ADDRESS_EMAIL:
				case SearchFields_Message::IS_BROADCAST:
				case SearchFields_Message::IS_NOT_SENT:
				case SearchFields_Message::IS_OUTGOING:
				case SearchFields_Message::TICKET_GROUP_ID:
				case SearchFields_Message::TICKET_ID:
				case SearchFields_Message::TICKET_MASK:
				case SearchFields_Message::WAS_ENCRYPTED:
				case SearchFields_Message::WAS_SIGNED:
				case SearchFields_Message::WORKER_ID:
				case SearchFields_Message::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Message::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_MESSAGE;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Message::ADDRESS_EMAIL:
				$label_map = function($ids) {
					$rows = DAO_Address::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'email', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Message::ADDRESS_ID, $label_map, 'in', 'value[]');
				break;
			
			case SearchFields_Message::TICKET_GROUP_ID:
				$label_map = function($ids) {
					$rows = DAO_Group::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'name', 'id');
				};
				$counts = $this->_getSubtotalCountForVirtualColumn($context, SearchFields_Message::TICKET_GROUP_ID, $label_map, SearchFields_Message::VIRTUAL_TICKET_SEARCH, 'group:(id:%s)', 'group:null');
				break;
			
			case SearchFields_Message::TICKET_ID:
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, array(), '=', 'value');
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
				$label_map = array();
				foreach($workers as $worker_id => $worker)
					$label_map[$worker_id] = $worker->getName();
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in', 'worker_id[]');
				break;

			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::WAS_ENCRYPTED:
			case SearchFields_Message::WAS_SIGNED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
				
			case SearchFields_Message::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Message::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_Message::getFields();
		
		$active_worker = CerberusApplication::getActiveWorker();
		$group_names = DAO_Group::getNames($active_worker);
		$worker_names = array_map(function(&$name) {
			return '"'.$name.'"';
		}, DAO_Worker::getNames());
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Message::MESSAGE_CONTENT),
				),
			'attachments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array(),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ATTACHMENT, 'q' => ''],
					]
				),
			'content' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Message::MESSAGE_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Message::CREATED_DATE),
				),
			'header.messageId' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
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
					'options' => array('param_key' => SearchFields_Message::IS_OUTGOING),
				),
			'isSigned' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Message::WAS_SIGNED),
				),
			'notes' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Message::FULLTEXT_NOTE_CONTENT),
				),
			'responseTime' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::RESPONSE_TIME),
				),
			'sender' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_SENDER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'sender.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Message::ADDRESS_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'ticket' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_TICKET_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				),
			'ticket.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
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
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_WORKER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_MESSAGE, $fields, null);
		//$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ORG, $fields, 'org');
		//$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_TICKET, $fields, 'ticket');
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_MessageContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
			$fields['content']['examples'] = $ft_examples;
		}
		
		// Engine/schema examples: Notes
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['notes']['examples'] = $ft_examples;
		
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
				break;
				
			case 'from':
			case 'sender':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_SENDER_SEARCH);
				break;
				
			case 'header.messageId':
				$field_key = SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $value);
				
				if($value) {
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						$value
					);
				}
				break;
			
			case 'responseTime':
				$tokens = CerbQuickSearchLexer::getHumanTimeTokensAsNumbers($tokens);
				
				$field_key = SearchFields_Message::RESPONSE_TIME;
				return DevblocksSearchCriteria::getNumberParamFromTokens($field_key, $tokens);
				break;
				
			case 'ticket':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_TICKET_SEARCH);
				break;
				
			case 'worker':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Message::VIRTUAL_WORKER_SEARCH);
				break;
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
					
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		$this->_sanitize();
		
		$results = $this->getData();
		$tpl->assign('results', $results);
		
		$this->_checkFulltextMarquee();
		
		switch($this->renderTemplate) {
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::messages/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_Message::VIRTUAL_ATTACHMENTS_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.attachments')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_HEADER_MESSAGE_ID:
				echo sprintf("Message-ID header matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_message::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
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
		}
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_Message::ADDRESS_EMAIL:
			case SearchFields_Message::TICKET_MASK:
			case SearchFields_Message::TICKET_SUBJECT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Message::ADDRESS_ID:
			case SearchFields_Message::TICKET_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_Message::RESPONSE_TIME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__time_elapsed.tpl');
				break;
				
			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::WAS_ENCRYPTED:
			case SearchFields_Message::WAS_SIGNED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Message::CREATED_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Message::TICKET_GROUP_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_group.tpl');
				break;
				
			case SearchFields_Message::WORKER_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			case SearchFields_Message::FULLTEXT_NOTE_CONTENT:
			case SearchFields_Message::MESSAGE_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_message::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Message::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_ASSET);
				break;
				
			default:
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
			case SearchFields_Message::WAS_SIGNED:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_Message::TICKET_GROUP_ID:
				$groups = DAO_Group::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($groups[$val]))
					continue;

					$strings[] = DevblocksPlatform::strEscapeHtml($groups[$val]->name);
				}
				echo implode(" or ", $strings);
				break;
				
			case SearchFields_Message::ADDRESS_ID:
				$senders = DAO_Address::getIds($values);
				$strings = array();

				foreach($senders as $sender) {
					$strings[] = DevblocksPlatform::strEscapeHtml($sender->getNameWithEmail());
				}
				echo implode(" or ", $strings);
				break;
				
			case SearchFields_Message::WORKER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			case SearchFields_Message::RESPONSE_TIME:
				$values = !is_array($param->value) ? array($param->value) : $param->value;
				
				foreach($values as &$value) {
					if(0 == $value) {
						$value = 'never';
					} else {
						$value = DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value));
					}
				}
				
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
			case SearchFields_Message::TICKET_MASK:
			case SearchFields_Message::TICKET_SUBJECT:
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
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::WAS_ENCRYPTED:
			case SearchFields_Message::WAS_SIGNED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;

			case SearchFields_Message::TICKET_GROUP_ID:
				@$group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$group_ids);
				break;
				
			case SearchFields_Message::WORKER_ID:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Message::FULLTEXT_NOTE_CONTENT:
			case SearchFields_Message::MESSAGE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Message::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Message::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			default:
				// Custom Fields
//				if(substr($field,0,3)=='cf_') {
//					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
//				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

class Context_Message extends Extension_DevblocksContext implements IDevblocksContextPeek {
	static function isReadableByActor($models, $actor) {
		// Only admins and group members can see, unless public
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_MESSAGE)))
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
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_MESSAGE)))
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
	
	function getDefaultProperties() {
		return array(
			'ticket_status',
			'ticket__label',
			'ticket_group__label',
			'ticket_bucket__label',
			'ticket_org__label',
			'ticket_updated',
		);
	}
	
	function getContext($message, &$token_labels, &$token_values, $prefix=null) {
		$is_nested = $prefix ? true : false;
		
		if(is_null($prefix))
			$prefix = 'Message:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MESSAGE);

		// Polymorph
		if(is_numeric($message)) {
			$message = DAO_Message::get($message);
		} elseif($message instanceof Model_Message) {
			// It's what we want already.
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
			'was_encrypted' => $prefix.$translate->_('message.is_encrypted'),
			'was_signed' => $prefix.$translate->_('message.is_signed'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'html_attachment_id' => Model_CustomField::TYPE_NUMBER,
			'id' => Model_CustomField::TYPE_NUMBER,
			'content' => Model_CustomField::TYPE_MULTI_LINE,
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
			'was_encrypted' => Model_CustomField::TYPE_CHECKBOX,
			'was_signed' => Model_CustomField::TYPE_CHECKBOX,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_MESSAGE;
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
			$token_values['was_encrypted'] = $message->was_encrypted;
			$token_values['was_signed'] = $message->was_signed;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($message, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=ticket&id=%d/message/%d", $message->ticket_id, $message->id), true);
		}

		$context_stack = CerberusContexts::getStack();
		
		// Only link ticket placeholders if the message isn't nested under a ticket already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_TICKET, $context_stack)) {
			$merge_token_labels = array();
			$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
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
			'response_time' => DAO_Message::RESPONSE_TIME,
			'sender_id' => DAO_Message::ADDRESS_ID,
			'storage_size' => DAO_Message::STORAGE_SIZE,
			'ticket_id' => DAO_Message::TICKET_ID,
			'was_encrypted' => DAO_Message::WAS_ENCRYPTED,
			'was_signed' => DAO_Message::WAS_SIGNED,
			'worker_id' => DAO_Message::WORKER_ID,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'sender':
				if(false == ($address = DAO_Address::lookupAddress($value, true))) {
					$error = sprintf("Failed to lookup address: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Message::ADDRESS_ID] = $address->id;
				break;
				
			case 'content':
				$out_fields[DAO_Message::_CONTENT] = $value;
				break;
				
			case 'headers':
				$out_fields[DAO_Message::_HEADERS] = $value;
				break;
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_MESSAGE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
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
				
			case 'attachments':
				$results = DAO_Attachment::getByContextIds($context, $context_id);
				$objects = [];
				
				foreach($results as $attachment_id => $attachment) {
					$object = [
						'id' => $attachment_id,
						'file_name' => $attachment->name,
						'file_size' => $attachment->storage_size,
						'file_type' => $attachment->mime_type,
					];
					$objects[$attachment_id] = $object;
				}
				
				$values['attachments'] = $objects;
				break;
				
			case 'content':
				// [TODO] Allow an array with storage meta here?  It removes an extra (n) SELECT in dictionaries for content
				$values['content'] = Storage_MessageContent::get($context_id);
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
					$recipients = array();
					
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
					$recipients = array();
					
					if(is_array($addys))
					foreach($addys as $addy) {
						$recipients[] = $addy['full_email'];
					}
					
					$values['reply_cc'] = implode(', ', $recipients);
				}
				break;
				
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
				
			default:
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

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
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = DevblocksPlatform::translateCapitalized('common.messages');
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Message::VIRTUAL_CONTEXT_LINK,'in',[$context.':'.$context_id]),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($message = DAO_Message::get($context_id))) {
			$tpl->assign('model', $message);
		}
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MESSAGE, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MESSAGE, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		
		if(empty($context_id) || $edit) {
			$tpl->display('devblocks:cerberusweb.core::internal/messages/peek_edit.tpl');
			
		} else {
			$activity_counts = array(
				//'comments' => DAO_Comment::count(CerberusContexts::CONTEXT_CONTACT, $context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			$links = array(
				CerberusContexts::CONTEXT_MESSAGE => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							CerberusContexts::CONTEXT_MESSAGE,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);

			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_MESSAGE)))
				return;
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $message, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$is_readable = Context_Message::isReadableByActor($dict, $active_worker);
			$tpl->assign('is_readable', $is_readable);
			
			$is_writeable = Context_Message::isWriteableByActor($dict, $active_worker);
			$tpl->assign('is_writeable', $is_writeable);
			
			// Timeline
			if($is_readable && $message) {
				$timeline_json = Page_Profiles::getTimelineJson($message->getTimeline());
				$tpl->assign('timeline_json', $timeline_json);
			}
			
			$tpl->display('devblocks:cerberusweb.core::internal/messages/peek.tpl');
		}
	}
};