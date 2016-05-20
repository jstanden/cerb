<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class DAO_Message extends Cerb_ORMHelper {
	const ID = 'id';
	const TICKET_ID = 'ticket_id';
	const CREATED_DATE = 'created_date';
	const ADDRESS_ID = 'address_id';
	const IS_BROADCAST = 'is_broadcast';
	const IS_OUTGOING = 'is_outgoing';
	const IS_NOT_SENT = 'is_not_sent';
	const WORKER_ID = 'worker_id';
	const HTML_ATTACHMENT_ID = 'html_attachment_id';
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	const STORAGE_SIZE = 'storage_size';
	const RESPONSE_TIME = 'response_time';
	const HASH_HEADER_MESSAGE_ID = 'hash_header_message_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
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

	static function update($id, $fields) {
		parent::_update($id, 'message', $fields);
	}

	/**
	 * @param string $where
	 * @return Model_Message[]
	 */
	static function getWhere($where=null, $sortBy='created_date', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, ticket_id, created_date, is_outgoing, worker_id, html_attachment_id, address_id, storage_extension, storage_key, storage_profile_id, storage_size, response_time, is_broadcast, is_not_sent, hash_header_message_id ".
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
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) FROM message WHERE ticket_id = %d",
			$ticket_id
		);
		return intval($db->GetOneSlave($sql));
	}

	static function delete($ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$eventMgr = DevblocksPlatform::getEventService();
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
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
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

		// Attachments
		$db->ExecuteMaster("DELETE attachment_link FROM attachment_link INNER JOIN _tmp_maint_message ON (_tmp_maint_message.id=attachment_link.context_id AND attachment_link.context = 'cerberusweb.contexts.message')");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message attachment_links.');
		
		// Search indexes
		if(isset($tables['fulltext_message_content'])) {
			$db->ExecuteMaster("DELETE fulltext_message_content FROM fulltext_message_content INNER JOIN _tmp_maint_message ON (_tmp_maint_message.id=fulltext_message_content.id)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_message_content records.');
		}
		
		$db->ExecuteMaster("DROP TABLE _tmp_maint_message");
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
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
			"t.group_id as %s, ".
			"t.mask as %s, ".
			"t.subject as %s, ".
			"t.status_id as %s, ".
			"a.email as %s ",
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
			SearchFields_Message::TICKET_GROUP_ID,
			SearchFields_Message::TICKET_MASK,
			SearchFields_Message::TICKET_SUBJECT,
			SearchFields_Message::TICKET_STATUS_ID,
			SearchFields_Message::ADDRESS_EMAIL
		);
		
		$join_sql = "FROM message m ".
			"INNER JOIN ticket t ON (m.ticket_id = t.id) ".
			"INNER JOIN address a ON (m.address_id = a.id) "
			;
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Message');
		
		$has_multiple_values = false;
		
		$result = array(
			'primary_table' => 'm',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
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
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY m.id ' : '').
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
					($has_multiple_values ? "SELECT COUNT(DISTINCT m.id) " : "SELECT COUNT(m.id) ").
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
	const VIRTUAL_ATTACHMENT_NAME = '*_attachment_name';
	const VIRTUAL_HAS_ATTACHMENTS = '*_has_attachments';
	const VIRTUAL_TICKET_STATUS = '*_ticket_status';
	const VIRTUAL_TICKET_IN_GROUPS_OF_WORKER = '*_in_groups_of_worker';

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
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::MESSAGE_CONTENT:
				return self::_getWhereSQLFromFulltextField($param, Search_MessageContent::ID, self::getPrimaryKey());
				break;
				
			case self::FULLTEXT_NOTE_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_MESSAGE, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_ATTACHMENTS:
				return sprintf("%s %sIN (".
					"SELECT context_id ".
					"FROM attachment_link ".
					"WHERE context='cerberusweb.contexts.message' AND context_id = %s ".
					")",
					self::getPrimaryKey(),
					!empty($param->value) ? '' : 'NOT ',
					self::getPrimaryKey()
				);
				break;
				
			case self::VIRTUAL_ATTACHMENT_NAME:
				$where = '0';
				$values = array();
				$not = false;
				
				if(is_array($param->value))
				foreach($param->value as $value)
					$values[] = Cerb_ORMHelper::qstr($value);
				
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_NIN:
						$not = ($param->operator == DevblocksSearchCriteria::OPER_NIN);
						$wheres = sprintf('attachment.display_name IN (%s)',
							implode(',', $values)
						);
						break;
						
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_NEQ:
						$not = ($param->operator == DevblocksSearchCriteria::OPER_NEQ);
						$wheres = sprintf('attachment.display_name = %s',
							Cerb_ORMHelper::qstr($param->value)
						);
						break;
						
					case DevblocksSearchCriteria::OPER_LIKE:
					case DevblocksSearchCriteria::OPER_NOT_LIKE:
						$not = ($param->operator == DevblocksSearchCriteria::OPER_NOT_LIKE);
						$wheres = sprintf('attachment.display_name LIKE %s',
							Cerb_ORMHelper::qstr(str_replace('*','%',$param->value))
						);
						break;
				}
				
				return sprintf("%s %sIN (".
					"SELECT context_id ".
					"FROM attachment_link ".
					"INNER JOIN attachment ON (attachment.id=attachment_link.attachment_id) ".
					"WHERE attachment_link.context='cerberusweb.contexts.message' AND attachment_link.context_id = %s ".
					"AND %s".
					")",
					self::getPrimaryKey(),
					($not ? 'NOT ' : ''),
					self::getPrimaryKey(),
					$wheres
				);
				break;
			
			case SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER:
				if(null == ($member = DAO_Worker::get($param->value)))
					break;
					
				$all_groups = DAO_Group::getAll();
				$roster = $member->getMemberships();
				
				if(empty($roster))
					$roster = array(0 => 0);
				
				$restricted_groups = array_diff(array_keys($all_groups), array_keys($roster));
				
				// If the worker is in every group, ignore this filter entirely
				if(empty($restricted_groups))
					break;
				
				// [TODO] If the worker is in most of the groups, possibly try a NOT IN instead
				
				return sprintf("t.group_id IN (%s)", implode(',', array_keys($roster)));
				break;
				
			case SearchFields_Message::VIRTUAL_TICKET_STATUS:
				$values = $param->value;
				if(!is_array($values))
					$values = array($values);
					
				$oper_sql = array();
				$statuses = array();
				
				switch($param->operator) {
					default:
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_IN_OR_NULL:
						$oper = '';
						break;
					case DevblocksSearchCriteria::OPER_NIN:
					case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
						$oper = 'NOT ';
						break;
				}
				
				foreach($values as $value) {
					switch($value) {
						case 'open':
							$statuses[] = Model_Ticket::STATUS_OPEN;
							break;
						case 'waiting':
							$statuses[] = Model_Ticket::STATUS_WAITING;
							break;
						case 'closed':
							$statuses[] = Model_Ticket::STATUS_CLOSED;
							break;
						case 'deleted':
							$statuses[] = Model_Ticket::STATUS_DELETED;
							break;
					}
				}
				
				if(empty($statuses))
					break;
				
				return sprintf('t.status_id %sIN (%s) ', $oper, implode(', ', $statuses));
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
			SearchFields_Message::ADDRESS_ID => new DevblocksSearchField(SearchFields_Message::ADDRESS_ID, 'm', 'address_id', null, true),
			SearchFields_Message::CREATED_DATE => new DevblocksSearchField(SearchFields_Message::CREATED_DATE, 'm', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Message::IS_OUTGOING => new DevblocksSearchField(SearchFields_Message::IS_OUTGOING, 'm', 'is_outgoing', $translate->_('message.is_outgoing'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::TICKET_ID => new DevblocksSearchField(SearchFields_Message::TICKET_ID, 'm', 'ticket_id', 'Ticket ID', null, true),
			SearchFields_Message::WORKER_ID => new DevblocksSearchField(SearchFields_Message::WORKER_ID, 'm', 'worker_id', $translate->_('common.worker'), Model_CustomField::TYPE_WORKER, true),
			SearchFields_Message::HTML_ATTACHMENT_ID => new DevblocksSearchField(SearchFields_Message::HTML_ATTACHMENT_ID, 'm', 'html_attachment_id', null, null, true),
			SearchFields_Message::RESPONSE_TIME => new DevblocksSearchField(SearchFields_Message::RESPONSE_TIME, 'm', 'response_time', $translate->_('message.response_time'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Message::IS_BROADCAST => new DevblocksSearchField(SearchFields_Message::IS_BROADCAST, 'm', 'is_broadcast', $translate->_('message.is_broadcast'), Model_CustomField::TYPE_CHECKBOX, true),
			SearchFields_Message::IS_NOT_SENT => new DevblocksSearchField(SearchFields_Message::IS_NOT_SENT, 'm', 'is_not_sent', $translate->_('message.is_not_sent'), Model_CustomField::TYPE_CHECKBOX, true),
			
			SearchFields_Message::STORAGE_EXTENSION => new DevblocksSearchField(SearchFields_Message::STORAGE_EXTENSION, 'm', 'storage_extension', null, true),
			SearchFields_Message::STORAGE_KEY => new DevblocksSearchField(SearchFields_Message::STORAGE_KEY, 'm', 'storage_key', null, true),
			SearchFields_Message::STORAGE_PROFILE_ID => new DevblocksSearchField(SearchFields_Message::STORAGE_PROFILE_ID, 'm', 'storage_profile_id', null, true),
			SearchFields_Message::STORAGE_SIZE => new DevblocksSearchField(SearchFields_Message::STORAGE_SIZE, 'm', 'storage_size', null, true),
			
			SearchFields_Message::ADDRESS_EMAIL => new DevblocksSearchField(SearchFields_Message::ADDRESS_EMAIL, 'a', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			SearchFields_Message::TICKET_GROUP_ID => new DevblocksSearchField(SearchFields_Message::TICKET_GROUP_ID, 't', 'group_id', $translate->_('common.group'), null, true),
			SearchFields_Message::TICKET_STATUS_ID => new DevblocksSearchField(SearchFields_Message::TICKET_STATUS_ID, 't', 'status_id', $translate->_('common.status'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Message::TICKET_MASK => new DevblocksSearchField(SearchFields_Message::TICKET_MASK, 't', 'mask', $translate->_('ticket.mask'), Model_CustomField::TYPE_SINGLE_LINE, true),
			SearchFields_Message::TICKET_SUBJECT => new DevblocksSearchField(SearchFields_Message::TICKET_SUBJECT, 't', 'subject', $translate->_('ticket.subject'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			SearchFields_Message::VIRTUAL_ATTACHMENT_NAME => new DevblocksSearchField(SearchFields_Message::VIRTUAL_ATTACHMENT_NAME, '*', 'attachment_name', $translate->_('message.search.attachment_name'), null, false),
			SearchFields_Message::VIRTUAL_HAS_ATTACHMENTS => new DevblocksSearchField(SearchFields_Message::VIRTUAL_HAS_ATTACHMENTS, '*', 'has_attachments', $translate->_('message.search.has_attachments'), Model_CustomField::TYPE_CHECKBOX, false),
			SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER => new DevblocksSearchField(SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER, '*', 'in_groups_of_worker', $translate->_('ticket.groups_of_worker'), null, false),
			SearchFields_Message::VIRTUAL_TICKET_STATUS => new DevblocksSearchField(SearchFields_Message::VIRTUAL_TICKET_STATUS, '*', 'ticket_status', $translate->_('common.status'), null, false),
				
			SearchFields_Message::MESSAGE_CONTENT => new DevblocksSearchField(SearchFields_Message::MESSAGE_CONTENT, 'ftmc', 'content', $translate->_('common.content'), 'FT', false),
			SearchFields_Message::FULLTEXT_NOTE_CONTENT => new DevblocksSearchField(self::FULLTEXT_NOTE_CONTENT, 'ftnc', 'content', $translate->_('message.note.content'), 'FT', false),
		);

		// Fulltext indexes
		
		$columns[self::MESSAGE_CONTENT]->ft_schema = Search_MessageContent::ID;
		$columns[self::FULLTEXT_NOTE_CONTENT]->ft_schema = Search_CommentContent::ID;
		
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
	
	private $_sender_object = null;
	private $_headers_raw = null;

	function Model_Message() {}

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
	
	function getLinksAndAttachments() {
		return DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_MESSAGE, $this->id);
	}
	
	/**
	 * @return Model_Ticket
	 */
	function getTicket() {
		return DAO_Ticket::get($this->ticket_id);
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
	
	public function query($query, $attributes=array(), $limit=500) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ids = $engine->query($this, $query, $attributes, $limit);
		return $ids;
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'last_indexed_id', 0);
		$done = false;
		
		while(!$done && time() < $stop_time) {
			$where = sprintf("%s > %d", DAO_Message::ID, $id);
			$messages = DAO_Message::getWhere($where, 'id', true, 100);
	
			if(empty($messages)) {
				$done = true;
				continue;
			}
			
			$count = 0;
			
			if(is_array($messages))
			foreach($messages as $message) { /* @var $message Model_Message */
				$id = $message->id;
				
				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));
				
				$doc = array();
				
				// Add sender fields
				if(false != ($sender = $message->getSender())) {
					$doc['sender_name'] = $sender->getName();
					$doc['sender_email'] = $sender->email;
				}
				
				// Add ticket fields
				if(false != ($ticket = DAO_Ticket::get($message->ticket_id))) {
					$doc['mask'] = $ticket->mask;
					$doc['subject'] = $ticket->subject;
					
					// Org
					if(null != ($org = $ticket->getOrg()) && $org instanceof Model_ContactOrg) {
						$doc['org_name'] = $org->name;
					}
				}
				
				if(false !== ($content = Storage_MessageContent::get($message))) {
					// Strip reply quotes
					$content = preg_replace("/(^\>(.*)\$)/m", "", $content);
					$content = preg_replace("/[\r\n]+/", "\n", $content);
					
					// Truncate to 10KB
					$content = $engine->truncateOnWhitespace($content, 10000);
					
					$doc['content'] = $content;
				}
				
				if(false === ($engine->index($this, $id, $doc)))
					return false;

				// Record our progress every 25th index
				if(++$count % 25 == 0) {
					if(!empty($id))
						DAO_DevblocksExtensionPropertyStore::put(self::ID, 'last_indexed_id', $id);
				}
			}
			
			flush();
			
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
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days'));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/message_content/render.tpl");
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::getTemplateService();
		
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
	 * @return unknown_type
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
		
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$logger = DevblocksPlatform::getConsoleLog();
		
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
			SearchFields_Message::TICKET_STATUS_ID, // [TODO] Keep, or convert the virtual?
			SearchFields_Message::VIRTUAL_ATTACHMENT_NAME,
			SearchFields_Message::VIRTUAL_HAS_ATTACHMENTS,
			SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Message::HTML_ATTACHMENT_ID,
			SearchFields_Message::ID,
			SearchFields_Message::TICKET_STATUS_ID, // [TODO] Keep the virtual?
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
				case SearchFields_Message::WORKER_ID:
				case SearchFields_Message::VIRTUAL_TICKET_STATUS:
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Message::ADDRESS_EMAIL:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Message', $column);
				break;
				
			case SearchFields_Message::TICKET_GROUP_ID:
				$groups = DAO_Group::getAll();
				$label_map = array();
				foreach($groups as $group_id => $group)
					$label_map[$group_id] = $group->name;
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Message', $column, $label_map, 'in', 'group_id[]');
				break;
				
			case SearchFields_Message::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$label_map = array();
				foreach($workers as $worker_id => $worker)
					$label_map[$worker_id] = $worker->getName();
				$counts = $this->_getSubtotalCountForNumberColumn('DAO_Message', $column, $label_map, 'in', 'worker_id[]');
				break;

			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Message', $column);
				break;
			
			case SearchFields_Message::VIRTUAL_TICKET_STATUS:
				$counts = $this->_getSubtotalCountForStatus();
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Message', $column, 'm.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForStatus($dao_class, $field_key) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = $this->getFields();
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		// We want counts for all statuses even though we're filtering
		if(
			isset($params[SearchFields_Message::VIRTUAL_TICKET_STATUS])
			&& is_array($params[SearchFields_Message::VIRTUAL_TICKET_STATUS]->value)
			&& count($params[SearchFields_Message::VIRTUAL_TICKET_STATUS]->value) < 2
			)
			unset($params[SearchFields_Message::VIRTUAL_TICKET_STATUS]);
			
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return array();
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = "SELECT COUNT(t.id) AS hits, t.status_id ".
			$join_sql.
			$where_sql.
			' GROUP BY t.status_id'
		;
		
		$results = $db->GetArraySlave($sql);

		return $results;
	}
	
	protected function _getSubtotalCountForStatus() {
		$workers = DAO_Worker::getAll();
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$results = $this->_getSubtotalDataForStatus('DAO_Message', SearchFields_Message::VIRTUAL_TICKET_STATUS);

		$oper = DevblocksSearchCriteria::OPER_IN;
		
		foreach($results as $result) {
			if(empty($result['hits']))
				continue;
			
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$label = $translate->_('status.open');
					$values = array('options[]' => 'open');
					break;
				case Model_Ticket::STATUS_WAITING:
					$label = $translate->_('status.waiting');
					$values = array('options[]' => 'waiting');
					break;
				case Model_Ticket::STATUS_CLOSED:
					$label = $translate->_('status.closed');
					$values = array('options[]' => 'closed');
					break;
				case Model_Ticket::STATUS_DELETED:
					$label = $translate->_('status.deleted');
					$values = array('options[]' => 'deleted');
					break;
				default:
					$label = '';
					break;
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $result['hits'],
					'label' => $label,
					'filter' =>
						array(
							'field' => SearchFields_Message::VIRTUAL_TICKET_STATUS,
							'oper' => $oper,
							'values' => $values,
						),
					'children' => array()
				);
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
			'attachments.exist' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_HAS_ATTACHMENTS),
				),
			'attachments.name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_ATTACHMENT_NAME),
					'examples' => array(
						'*.html',
						'image.png',
						'[file1.png,file2.html]',
					),
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
			'from' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Message::ADDRESS_EMAIL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'group' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::TICKET_GROUP_ID),
					'examples' => array_slice($group_names, 0, 15),
				),
			'inGroupsOfWorker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER),
					'examples' => array_merge(array('me','current'),array_slice($worker_names, 0, 13)),
				),
			'isBroadcast' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Message::IS_BROADCAST),
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
			'ticket.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Message::TICKET_ID),
				),
			'ticket.mask' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Message::TICKET_MASK, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'examples' => array(
						'ABC*',
						'XYZ-12345-678',
					),
				),
			'ticket.status' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Message::VIRTUAL_TICKET_STATUS),
					'examples' => array(
						'open',
						'waiting',
						'closed',
						'deleted',
						'[o,w]',
						'![d]',
					),
				),
			'ticket.subject' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Message::TICKET_SUBJECT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Message::WORKER_ID),
				),
		);
		
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
		switch($field) {
			case 'attachments.name':
				$field_key = SearchFields_Message::VIRTUAL_ATTACHMENT_NAME;
				$oper = null;
				$value = null;
				
				$param = DevblocksSearchCriteria::getTextParamFromTokens($field_key, $tokens);
				return $param;
				break;
				
			case 'group':
				$field_key = SearchFields_Message::TICKET_GROUP_ID;
				$oper = null;
				$terms = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $terms);
				
				if(!is_array($terms))
					break;
				
				$groups = DAO_Group::getAll();
				$group_ids = array();
				
				foreach($terms as $term) {
					foreach($groups as $group_id => $group) {
						if(isset($group_ids[$group_id]))
							continue;
						
						if(false !== stristr($group->name, $term)) {
							$group_ids[$group_id] = true;
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
				
				return false;
				break;
				
			case 'inGroupsOfWorker':
				$field_key = SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER;
				$oper = null;
				$v = null;
				
				CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $v);
				
				$worker_id = 0;
				
				switch(strtolower($v)) {
					case 'current':
						$worker_id = '{{current_worker_id}}';
						break;
						
					case 'me':
					case 'mine':
					case 'my':
						if(false != ($active_worker = CerberusApplication::getActiveWorker()))
							$worker_id = $active_worker->id;
						break;
					
					default:
						if(false != ($matches = DAO_Worker::getByString($v)) && !empty($matches))
							$worker_id = key($matches);
						break;
				}
				
				if($worker_id) {
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						$worker_id
					);
				}
				break;
			
			case 'responseTime':
				$field_key = SearchFields_Message::RESPONSE_TIME;
				return DevblocksSearchCriteria::getNumberParamFromTokens($field_key, $tokens);
				break;
				
			case 'ticket.status':
				$field_key = SearchFields_Message::VIRTUAL_TICKET_STATUS;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
				
				$values = array();
				
				// Normalize status labels
				foreach($value as $idx => $status) {
					switch(substr(strtolower($status), 0, 1)) {
						case 'o':
							$values['open'] = true;
							break;
						case 'w':
							$values['waiting'] = true;
							break;
						case 'c':
							$values['closed'] = true;
							break;
						case 'd':
							$values['deleted'] = true;
							break;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
				break;
			
			default:
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
			case SearchFields_Message::VIRTUAL_ATTACHMENT_NAME:
				$strings_or = array();

				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_LIKE:
						$oper = 'is';
						break;
					case DevblocksSearchCriteria::OPER_NEQ:
					case DevblocksSearchCriteria::OPER_NIN:
					case DevblocksSearchCriteria::OPER_NOT_LIKE:
						$oper = 'is not';
						break;
					default:
						$oper = $param->operator;
						break;
				}
				
				if(is_array($param->value)) {
					foreach($param->value as $param_value) {
						$strings_or[] = sprintf("<b>%s</b>",
							DevblocksPlatform::strEscapeHtml($param_value)
						);
					}
				} else {
					$strings_or[] = sprintf("<b>%s</b>",
						DevblocksPlatform::strEscapeHtml($param->value)
					);
				}
				
				echo sprintf("Attachment name %s %s",
					DevblocksPlatform::strEscapeHtml($oper),
					implode(' or ', $strings_or)
				);
				break;
				
			case SearchFields_Message::VIRTUAL_HAS_ATTACHMENTS:
				if($param->value)
					echo "<b>Has</b> attachments";
				else
					echo "<b>Doesn't</b> have attachments";
				break;
			
			case SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER:
				$worker_name = $param->value;
				
				if(is_numeric($worker_name)) {
					if(null == ($worker = DAO_Worker::get($worker_name)))
						break;
					
					$worker_name = $worker->getName();
				}
					
				echo sprintf("In <b>%s</b>'s groups", DevblocksPlatform::strEscapeHtml($worker_name));
				break;
				
			case SearchFields_Message::VIRTUAL_TICKET_STATUS:
				if(!is_array($param->value))
					$param->value = array($param->value);
					
				$strings = array();
				
				foreach($param->value as $value) {
					switch($value) {
						case 'open':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.open')) . '</b>';
							break;
						case 'waiting':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.waiting')) . '</b>';
							break;
						case 'closed':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.closed')) . '</b>';
							break;
						case 'deleted':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.deleted')) . '</b>';
							break;
					}
				}
				
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IN:
						$oper = 'is';
						break;
					case DevblocksSearchCriteria::OPER_IN_OR_NULL:
						$oper = 'is blank or';
						break;
					case DevblocksSearchCriteria::OPER_NIN:
						$oper = 'is not';
						break;
					case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
						$oper = 'is blank or not';
						break;
				}
				echo sprintf("Status %s %s",
					DevblocksPlatform::strEscapeHtml($oper),
					implode(' or ', $strings)
				);
				break;
		}
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_Message::ADDRESS_EMAIL:
			case SearchFields_Message::TICKET_MASK:
			case SearchFields_Message::TICKET_SUBJECT:
			case SearchFields_Message::VIRTUAL_ATTACHMENT_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case '_placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_Message::RESPONSE_TIME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__time_elapsed.tpl');
				break;
				
			case SearchFields_Message::IS_BROADCAST:
			case SearchFields_Message::IS_NOT_SENT:
			case SearchFields_Message::IS_OUTGOING:
			case SearchFields_Message::VIRTUAL_HAS_ATTACHMENTS:
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
				
			case SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER:
				$tpl->assign('workers', DAO_Worker::getAllActive());
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__worker.tpl');
				break;
				
			case SearchFields_Message::VIRTUAL_TICKET_STATUS:
				$translate = DevblocksPlatform::getTranslationService();
				
				$options = array(
					'open' => $translate->_('status.open'),
					'waiting' => $translate->_('status.waiting'),
					'closed' => $translate->_('status.closed'),
					'deleted' => $translate->_('status.deleted'),
				);
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
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
						echo $value;
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
			case SearchFields_Message::ADDRESS_EMAIL:
			case SearchFields_Message::TICKET_MASK:
			case SearchFields_Message::TICKET_SUBJECT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
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
			case SearchFields_Message::VIRTUAL_HAS_ATTACHMENTS:
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
				
			case SearchFields_Message::VIRTUAL_ATTACHMENT_NAME:
				$criteria = new DevblocksSearchCriteria($field,$oper,explode(' OR ', $value));
				break;
				
			case SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'string','');
				$criteria = new DevblocksSearchCriteria($field, '=', $worker_id);
				break;
				
			case SearchFields_Message::VIRTUAL_TICKET_STATUS:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
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

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();

		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
//				case 'is_disabled':
//					$change_fields[DAO_Worker::IS_DISABLED] = intval($v);
//					break;
//				default:
//					// Custom fields
//					if(substr($k,0,3)=="cf_") {
//						$custom_fields[substr($k,3)] = $v;
//					}
//					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Message::search(
			array(),
			$this->getParams(),
			100,
			$pg++,
			SearchFields_Message::ID,
			true,
			false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Message::update($batch_ids, $change_fields);
			
			// Custom Fields
			//self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_WORKER, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Message extends Extension_DevblocksContext implements IDevblocksContextPeek {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;
				
			if(null == ($message = DAO_Message::get($context_id)))
				throw new Exception();
			
			if(null == ($ticket = DAO_Ticket::get($message->ticket_id)))
				throw new Exception();
			
			return $worker->isGroupMember($ticket->group_id);
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		return DAO_Message::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::getUrlService();

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
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'ticket__label',
			'ticket_status',
			'sender__label',
			'is_outgoing',
			'worker__label',
			'ticket_org__label',
			'created',
		);
	}
	
	function getContext($message, &$token_labels, &$token_values, $prefix=null) {
		$is_nested = $prefix ? true : false;
		
		if(is_null($prefix))
			$prefix = 'Message:';
		
		$translate = DevblocksPlatform::getTranslationService();

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
		);
		
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
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($message, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
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
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_MESSAGE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
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
				
			case 'headers':
				$headers = DAO_MessageHeaders::getAll($context_id);
				$values['headers'] = $headers;
				break;
				
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
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Messages';
		$view->addParams(array(), true);
		
		$params_required = array();
		
		if(!empty($active_worker)) {
			$params[SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER] = new DevblocksSearchCriteria(SearchFields_Message::VIRTUAL_TICKET_IN_GROUPS_OF_WORKER,'=',$active_worker->id);
		}
		
		$view->addParamsRequired($params_required, true);
		
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
		$view->name = 'Messages';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Message::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Message::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::getTemplateService();
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
							array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $message, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			$tpl->assign('properties',
				array(
					'ticket_status',
					'ticket__label',
					'ticket_group__label',
					'ticket_bucket__label',
					'ticket_org__label',
					'ticket_updated',
				)
			);
			
			$tpl->display('devblocks:cerberusweb.core::internal/messages/peek.tpl');
		}
	}
};