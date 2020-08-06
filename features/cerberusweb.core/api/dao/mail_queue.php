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

class DAO_MailQueue extends Cerb_ORMHelper {
	const HINT_TO = 'hint_to';
	const ID = 'id';
	const IS_QUEUED = 'is_queued';
	const PARAMS_JSON = 'params_json';
	const QUEUE_DELIVERY_DATE = 'queue_delivery_date';
	const QUEUE_FAILS = 'queue_fails';
	const NAME = 'name';
	const TICKET_ID = 'ticket_id';
	const TYPE = 'type';
	const UPDATED = 'updated';
	const WORKER_ID = 'worker_id';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// text
		$validation
			->addField(self::HINT_TO)
			->string()
			->setMaxLength(65535)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::IS_QUEUED)
			->bit()
			;
		// longtext
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength('32 bits')
			;
		// int(10) unsigned
		$validation
			->addField(self::QUEUE_DELIVERY_DATE)
			->uint(4)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::QUEUE_FAILS)
			->uint(1)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::TICKET_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_TICKET, true))
			;
		// varchar(255)
		$validation
			->addField(self::TYPE)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			->setPossibleValues(['mail.compose', 'ticket.reply', 'ticket.forward'])
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKER_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER))
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
		
		$sql = "INSERT INTO mail_queue () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		$context = CerberusContexts::CONTEXT_DRAFT;
		
		if(!array_key_exists(self::UPDATED, $fields))
			$fields[self::UPDATED] = time();
		
		// Send events
		if($check_deltas) {
			CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_DRAFT, $ids);
		}
		
		self::_updateAbstract($context, $ids, $fields);
		
		parent::_update($ids, 'mail_queue', $fields);
		
		// Send events
		if($check_deltas) {
			// Log the context update
			DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_DRAFT, $ids);
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('mail_queue', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_DRAFT;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::WORKER_ID])) {
			$error = "A 'worker_id' is required.";
			return false;
		}
		
		if(isset($fields[self::WORKER_ID])) {
			@$worker_id = $fields[self::WORKER_ID];
			
			if(!$worker_id) {
				$error = "Invalid 'worker_id' value.";
				return false;
			}
			
			if(!CerberusContexts::isOwnableBy(CerberusContexts::CONTEXT_WORKER, $worker_id, $actor)) {
				$error = "You do not have permission to create drafts for this worker.";
				return false;
			}
		}
		
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
		$deleted = false;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'status':
					switch($v) {
						case 'queue':
							$change_fields[DAO_MailQueue::IS_QUEUED] = 1;
							$change_fields[DAO_MailQueue::QUEUE_FAILS] = 0;
							break;
							
						case 'draft':
							$change_fields[DAO_MailQueue::IS_QUEUED] = 0;
							$change_fields[DAO_MailQueue::QUEUE_FAILS] = 0;
							break;
							
						case 'delete':
							$deleted = true;
							break;
					}
					break;
				default:
					break;
			}
		}
		
		if(!$deleted) {
			CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_DRAFT, $ids);
			
			if(!empty($change_fields))
				DAO_MailQueue::update($ids, $change_fields, false);
			
			DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_DRAFT, $ids);
		} else {
			CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_DRAFT, $ids);
			
			DAO_MailQueue::delete($ids);
		}
		
		$update->markCompleted();
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_MailQueue[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, worker_id, updated, type, ticket_id, hint_to, name, params_json, is_queued, queue_delivery_date, queue_fails ".
			"FROM mail_queue ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_MailQueue	 */
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
	
	static function getDraftsByTicketIds($ids, $max_age=600, $ignore_worker_ids=[]) {
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int', array('unique','nonzero'));
		$ignore_worker_ids = DevblocksPlatform::sanitizeArray($ignore_worker_ids, 'int', array('unique','nonzero'));
		
		if(empty($ids))
			return [];

		$results = self::getWhere(sprintf("%s != %d AND %s = %d AND %s in (%s) AND %s >= %d %s",
			DAO_MailQueue::TICKET_ID,
			0, // ticket_id exists
			DAO_MailQueue::IS_QUEUED,
			0, // is a draft
			DAO_MailQueue::TICKET_ID,
			implode(',', $ids), // is for one of these tickets
			DAO_MailQueue::UPDATED,
			time() - $max_age, // is no older than $max_age in secs
			(!empty($ignore_worker_ids) // doesn't include one of these workers (optional) 
				? sprintf("AND %s NOT IN (%s) ", DAO_MailQueue::WORKER_ID, implode(',', $ignore_worker_ids)) 
				: ''
			)
		));
		
		$out = [];
		
		if(is_array($results))
		foreach($results as $draft) {
			if(!isset($out[$draft->ticket_id]))
				$out[$draft->ticket_id] = [];
			
			// Only use the newest draft per ticket
			if(isset($out[$draft->ticket_id]) && $out[$draft->ticket_id]->updated > $draft->updated)
				continue;
			
			unset($draft->params);
			
			$out[$draft->ticket_id] = $draft;
		}
		
		return $out;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_MailQueue[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_MailQueue();
			$object->id = $row['id'];
			$object->worker_id = $row['worker_id'];
			$object->updated = $row['updated'];
			$object->type = $row['type'];
			$object->ticket_id = $row['ticket_id'];
			$object->hint_to = $row['hint_to'];
			$object->name = $row['name'];
			$object->is_queued = $row['is_queued'];
			$object->queue_delivery_date = $row['queue_delivery_date'];
			$object->queue_fails = $row['queue_fails'];
			
			// Deprecated
			$object->subject = $row['name'];
			
			// Unserialize params
			$params_json = $row['params_json'];
			if(!empty($params_json))
				$object->params = json_decode($params_json, true);
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('mail_queue');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM mail_queue WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_DRAFT,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_MailQueue::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_MailQueue', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"mail_queue.id as %s, ".
			"mail_queue.worker_id as %s, ".
			"mail_queue.updated as %s, ".
			"mail_queue.type as %s, ".
			"mail_queue.ticket_id as %s, ".
			"mail_queue.hint_to as %s, ".
			"mail_queue.name as %s, ".
			"mail_queue.is_queued as %s, ".
			"mail_queue.queue_delivery_date as %s, ".
			"mail_queue.queue_fails as %s ",
				SearchFields_MailQueue::ID,
				SearchFields_MailQueue::WORKER_ID,
				SearchFields_MailQueue::UPDATED,
				SearchFields_MailQueue::TYPE,
				SearchFields_MailQueue::TICKET_ID,
				SearchFields_MailQueue::HINT_TO,
				SearchFields_MailQueue::NAME,
				SearchFields_MailQueue::IS_QUEUED,
				SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
				SearchFields_MailQueue::QUEUE_FAILS
			);
			
		$join_sql = "FROM mail_queue ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_MailQueue');
		
		$result = array(
			'primary_table' => 'mail_queue',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
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
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		$results = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_MailQueue::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(mail_queue.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$db->ExecuteMaster("DELETE FROM attachment_link WHERE context = 'cerberusweb.contexts.mail.draft' AND context_id NOT IN (SELECT id FROM mail_queue)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' draft attachment_link records.');

		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_DRAFT,
					'context_table' => 'mail_queue',
					'context_key' => 'id',
				)
			)
		);
	}
	
	public static function getFieldsFromMessageProperties($properties) {
		$change_fields = [];
		$params = [];
		
		if(array_key_exists('ticket_id', $properties)) {
			$params['ticket_id'] = $properties['ticket_id'];
			$change_fields[DAO_MailQueue::TICKET_ID] = $properties['ticket_id'];
		}
		
		if(array_key_exists('message_id', $properties))
			$params['in_reply_message_id'] = $properties['message_id'];
		
		if(array_key_exists('to', $properties)) {
			$params['to'] = $properties['to'];
			$change_fields[DAO_MailQueue::HINT_TO] = $properties['to'];
		}
		
		if(array_key_exists('cc', $properties))
			$params['cc'] = $properties['cc'];
		
		if(array_key_exists('bcc', $properties))
			$params['bcc'] = $properties['bcc'];
		
		if(array_key_exists('subject', $properties)) {
			$params['subject'] = $properties['subject'];
			$change_fields[DAO_MailQueue::NAME] = $properties['subject'];
		}
		
		if(array_key_exists('content', $properties)) {
			$params['content'] = $properties['content'];
		}
		
		if(array_key_exists('content_format', $properties))
			$params['format'] = $properties['content_format'];
		
		if(array_key_exists('html_template_id', $properties))
			$params['html_template_id'] = $properties['html_template_id'];
		
		if(array_key_exists('status_id', $properties))
			$params['status_id'] = $properties['status_id'];
		
		if(array_key_exists('group_id', $properties))
			$params['group_id'] = $properties['group_id'];
		
		if(array_key_exists('bucket_id', $properties))
			$params['bucket_id'] = $properties['bucket_id'];
		
		if(array_key_exists('owner_id', $properties))
			$params['owner_id'] = $properties['owner_id'];
		
		if(array_key_exists('worker_id', $properties)) {
			$params['worker_id'] = $properties['worker_id'];
			$change_fields[DAO_MailQueue::WORKER_ID] = $properties['worker_id'];
		}
		
		if(array_key_exists('ticket_reopen', $properties))
			$params['ticket_reopen'] = $properties['ticket_reopen'];
		
		if(array_key_exists('options_gpg_encrypt', $properties))
			$params['gpg_encrypt'] = $properties['options_gpg_encrypt'];
		
		if(array_key_exists('options_gpg_sign', $properties))
			$params['gpg_sign'] = $properties['options_gpg_sign'];
		
		if(array_key_exists('is_forward', $properties))
			$params['is_forward'] = $properties['is_forward'];
		
		if(array_key_exists('forward_files', $properties))
			$params['file_ids'] = $properties['forward_files'];
		
		if(array_key_exists('send_at', $properties))
			$params['send_at'] = $properties['send_at'];
		
		if(array_key_exists('custom_fields', $properties))
			$params['custom_fields'] = $properties['custom_fields'];
		
		$change_fields[DAO_MailQueue::PARAMS_JSON] = json_encode($params);
		$change_fields[DAO_MailQueue::UPDATED] = time();
		
		return $change_fields;
	}
};

class SearchFields_MailQueue extends DevblocksSearchFields {
	const ID = 'm_id';
	const WORKER_ID = 'm_worker_id';
	const UPDATED = 'm_updated';
	const TYPE = 'm_type';
	const TICKET_ID = 'm_ticket_id';
	const HINT_TO = 'm_hint_to';
	const NAME = 'm_name';
	const IS_QUEUED = 'm_is_queued';
	const QUEUE_DELIVERY_DATE = 'm_queue_delivery_date';
	const QUEUE_FAILS = 'm_queue_fails';
	
	const VIRTUAL_WORKER_SEARCH = '*_worker_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'mail_queue.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_DRAFT => new DevblocksSearchFieldContextKeys('mail_queue.id', self::ID),
			CerberusContexts::CONTEXT_TICKET => new DevblocksSearchFieldContextKeys('mail_queue.ticket_id', self::TICKET_ID),
			CerberusContexts::CONTEXT_WORKER => new DevblocksSearchFieldContextKeys('mail_queue.worker_id', self::WORKER_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_WORKER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 'mail_queue.worker_id');
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
			case 'worker':
				$key = 'worker.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_MailQueue::ID:
				$models = DAO_MailQueue::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_DRAFT);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				break;
				
			case SearchFields_MailQueue::TYPE:
				$label_map = array(
					'mail.compose' => 'Compose',
					'ticket.reply' => 'Reply',
					'ticket.forward' => 'Forward',
				);
				return $label_map;
				break;
				
			case SearchFields_MailQueue::WORKER_ID:
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
			self::ID => new DevblocksSearchField(self::ID, 'mail_queue', 'id', $translate->_('common.id'), null, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'mail_queue', 'worker_id', ucwords($translate->_('common.worker')), null, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'mail_queue', 'updated', ucwords($translate->_('common.updated')), null, true),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'mail_queue', 'type', $translate->_('mail_queue.type'), null, true),
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 'mail_queue', 'ticket_id', $translate->_('mail_queue.ticket_id'), null, true),
			self::HINT_TO => new DevblocksSearchField(self::HINT_TO, 'mail_queue', 'hint_to', $translate->_('message.header.to'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'mail_queue', 'name', $translate->_('common.name'), null, true),
			self::IS_QUEUED => new DevblocksSearchField(self::IS_QUEUED, 'mail_queue', 'is_queued', $translate->_('mail_queue.is_queued'), null, true),
			self::QUEUE_DELIVERY_DATE => new DevblocksSearchField(self::QUEUE_DELIVERY_DATE, 'mail_queue', 'queue_delivery_date', $translate->_('mail_queue.queue_delivery_date'), null, true),
			self::QUEUE_FAILS => new DevblocksSearchField(self::QUEUE_FAILS, 'mail_queue', 'queue_fails', $translate->_('mail_queue.queue_fails'), null, true),
				
			self::VIRTUAL_WORKER_SEARCH => new DevblocksSearchField(self::VIRTUAL_WORKER_SEARCH, '*', 'worker_search', null, null, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_MailQueue {
	const TYPE_COMPOSE = 'mail.compose';
	const TYPE_TICKET_FORWARD = 'ticket.forward';
	const TYPE_TICKET_REPLY = 'ticket.reply';
	
	public $id;
	public $worker_id;
	public $updated;
	public $type;
	public $ticket_id;
	public $hint_to;
	public $name;
	public $params = [];
	public $is_queued;
	public $queue_delivery_date;
	public $queue_fails;
	
	public function getTicket() {
		return DAO_Ticket::get($this->ticket_id);
	}
	
	public function getWorker() {
		return DAO_Worker::get($this->worker_id);
	}
	
	public function getContent() {
		$message_properties = [
			'group_id' => $this->getParam('group_id', 0),
			'content' => $this->getParam('content', ''),
			'content_format' => $this->getParam('format', '')
		];
		
		if($this->hasParam('bucket_id'))
			$message_properties['bucket_id'] = $this->getParam('bucket_id');
		
		$commands = [];
		
		if(false != ($worker = $this->getWorker())) {
			switch ($this->type) {
				case Model_MailQueue::TYPE_TICKET_REPLY:
				case Model_MailQueue::TYPE_TICKET_FORWARD:
					CerberusMail::parseReplyHashCommands($worker, $message_properties, $commands);
					break;
				
				case Model_MailQueue::TYPE_COMPOSE:
					CerberusMail::parseComposeHashCommands($worker, $message_properties, $commands);
					break;
			}
		}
		
		if('parsedown' == $message_properties['content_format']) {
			$output = $message_properties['content'];
			$output = DevblocksPlatform::parseMarkdown($output);
			
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
			return $output;
		
		} else {
			return $message_properties['content'];
		}
	}
	
	function getTimeline($is_ascending=true) {
		$timeline = [
			$this,
		];
		
		if(false != ($comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_DRAFT, $this->id)))
			$timeline = array_merge($timeline, $comments);
		
		usort($timeline, function($a, $b) use ($is_ascending) {
			if($a instanceof Model_MailQueue) {
				$a_time = intval($a->updated);
			} else if($a instanceof Model_Comment) {
				$a_time = intval($a->created);
			} else {
				$a_time = 0;
			}
			
			if($b instanceof Model_MailQueue) {
				$b_time = intval($b->updated);
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
	
	public function hasParam($key) {
		return array_key_exists($key, $this->params);
	}
	
	public function getParam($key, $default=null) {
		if($this->hasParam($key))
			return $this->params[$key];
		
		return $default;
	}
	
	public function removeParam($key) {
		unset($this->params[$key]);
	}
	
	public function removeParams(array $keys) {
		foreach($keys as $key)
			$this->removeParam($key);
	}
	
	public function getMessageProperties() {
		$properties = [
			'draft_id' => $this->id,
			'to' => $this->getParam('to'),
			'cc' => $this->getParam('cc'),
			'bcc' => $this->getParam('bcc'),
			'subject' => $this->getParam('subject'),
			'content' => $this->getParam('content'),
			'content_format' => $this->getParam('format'),
			'worker_id' => $this->worker_id,
		];
		
		if($this->ticket_id) {
			$properties['ticket_id'] = $this->ticket_id;
		}
		
		if($this->hasParam('is_forward'))
			$properties['is_forward'] = $this->getParam('is_forward');
		
		if($this->hasParam('in_reply_message_id'))
			$properties['message_id'] = $this->getParam('in_reply_message_id');
		
		if($this->hasParam('html_template_id'))
			$properties['html_template_id'] = $this->getParam('html_template_id');
		
		if($this->hasParam('is_autoreply'))
			$properties['is_autoreply'] = $this->getParam('is_autoreply');
		
		if($this->hasParam('is_broadcast'))
			$properties['is_broadcast'] = $this->getParam('is_broadcast');
		
		if($this->hasParam('options_dont_send'))
			$properties['dont_send'] = $this->getParam('options_dont_send');
		
		if($this->hasParam('options_gpg_encrypt'))
			$properties['gpg_encrypt'] = $this->getParam('options_gpg_encrypt');
		
		if($this->hasParam('options_gpg_sign'))
			$properties['gpg_sign'] = $this->getParam('options_gpg_sign');
		
		if($this->hasParam('custom_fields'))
			$properties['custom_fields'] = $this->getParam('custom_fields', []);
		
		if($this->hasParam('custom_fieldset_deletes'))
			$properties['custom_fieldset_deletes'] = $this->getParam('custom_fieldset_deletes', []);
		
		if($this->hasParam('context_links'))
			$properties['context_links'] = $this->getParam('context_links', []);
		
		if($this->hasParam('file_ids')) {
			$properties['forward_files'] = $this->getParam('file_ids', []);
			$properties['link_forward_files'] = true;
		}
		
		if($this->hasParam('org_id'))
			$properties['org_id'] = $this->getParam('org_id');
		
		if($this->hasParam('org_name'))
			$properties['org_id'] = DAO_ContactOrg::lookup($this->getParam('org_name'));
		
		if($this->hasParam('status_id'))
			$properties['status_id'] = $this->getParam('status_id');
			
		if($this->hasParam('group_id'))
			$properties['group_id'] = $this->getParam('group_id');
		
		if($this->hasParam('bucket_id'))
			$properties['bucket_id'] = $this->getParam('bucket_id');
		
		if($this->hasParam('owner_id'))
			$properties['owner_id'] = $this->getParam('owner_id');
		
		if($this->hasParam('watcher_ids'))
			$properties['watcher_ids'] = $this->getParam('watcher_ids');
		
		if($this->hasParam('ticket_reopen'))
			$properties['ticket_reopen'] = $this->getParam('ticket_reopen');
		
		if($this->hasParam('send_at'))
			$properties['send_at'] = $this->getParam('send_at');
		
		return $properties;
	}
	
	/**
	 * @return boolean
	 */
	public function send() {
		$success = false;
		
		// Determine the type of message
		switch($this->type) {
			case Model_MailQueue::TYPE_COMPOSE:
				$success = $this->_sendCompose();
				break;
				
			case Model_MailQueue::TYPE_TICKET_FORWARD:
			case Model_MailQueue::TYPE_TICKET_REPLY:
				$success = $this->_sendTicketReply();
				break;
		}
		
		return $success;
	}
	
	private function _sendCompose() {
		$properties = $this->getMessageProperties();
		
		return CerberusMail::compose($properties);
	}
	
	private function _sendTicketReply() {
		$properties = $this->getMessageProperties();
		
		if('save' == $this->getParam('reply_mode'))
			$properties['dont_send'] = true;
		
		return CerberusMail::sendTicketMessage($properties);
	}
};

class View_MailQueue extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'mail_queue';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Mail Queue');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_MailQueue::UPDATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_MailQueue::HINT_TO,
			SearchFields_MailQueue::TYPE,
			SearchFields_MailQueue::WORKER_ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::UPDATED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_MailQueue::TICKET_ID,
			SearchFields_MailQueue::VIRTUAL_WORKER_SEARCH,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_MailQueue::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_MailQueue');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_MailQueue', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_MailQueue', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_MailQueue::TYPE:
				case SearchFields_MailQueue::WORKER_ID:
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
		$context = CerberusContexts::CONTEXT_DRAFT;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_MailQueue::TYPE:
				$label_map = array(
					'mail.compose' => 'Compose',
					'ticket.reply' => 'Reply',
					'ticket.forward' => 'Forward',
				);
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;

			case SearchFields_MailQueue::WORKER_ID:
				$label_map = [];
				$workers = DAO_Worker::getAll();
				foreach($workers as $worker_id => $worker)
					$label_map[$worker_id] = $worker->getName();
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'worker_id[]');
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
		$search_fields = SearchFields_MailQueue::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailQueue::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_MailQueue::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_DRAFT, 'q' => ''],
					]
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailQueue::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'to' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailQueue::HINT_TO, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'is.queued' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_MailQueue::IS_QUEUED),
				),
			'queue.fails' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_MailQueue::QUEUE_FAILS),
				),
			'queue.deliverAt' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_MailQueue::QUEUE_DELIVERY_DATE),
				),
			'type' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailQueue::TYPE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'examples' => [
						'mail.compose',
						'ticket.forward',
						'ticket.reply',
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_MailQueue::UPDATED),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailQueue::VIRTUAL_WORKER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'worker.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_MailQueue::WORKER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => 'isDisabled:n'],
					]
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_DRAFT, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'worker':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_MailQueue::VIRTUAL_WORKER_SEARCH);
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
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$label_map_type = [
			'mail.compose' => 'Compose',
			'ticket.reply' => 'Reply',
			'ticket.forward' => 'Forward',
		];
		$tpl->assign('label_map_type', $label_map_type);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::mail/queue/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_MailQueue::IS_QUEUED:
				$this->_renderCriteriaParamBoolean($param);
				break;
			
			case SearchFields_MailQueue::TYPE:
				$label_map = SearchFields_MailQueue::getLabelsForKeyValues($field, $values);
				$this->_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_MailQueue::WORKER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_MailQueue::VIRTUAL_WORKER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.worker')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}

	function getFields() {
		return SearchFields_MailQueue::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_MailQueue::TYPE:
			case SearchFields_MailQueue::HINT_TO:
			case SearchFields_MailQueue::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_MailQueue::ID:
			case SearchFields_MailQueue::TICKET_ID:
			case SearchFields_MailQueue::QUEUE_FAILS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_MailQueue::QUEUE_DELIVERY_DATE:
			case SearchFields_MailQueue::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_MailQueue::IS_QUEUED:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_MailQueue::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

class Context_Draft extends Extension_DevblocksContext implements IDevblocksContextPeek, IDevblocksContextProfile {
	const ID = 'cerberusweb.contexts.mail.draft';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Admins and owner can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);
		
		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_DRAFT)))
			return CerberusContexts::denyEverything($models);
		
		$results = [];
		
		foreach($dicts as $id => $dict) {
			$is_writeable = false;
			
			if($actor->_context == CerberusContexts::CONTEXT_WORKER && $actor->id == $dict->worker_id)
				$is_writeable = true;
			
			$results[$id] = $is_writeable;
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
	
	function getDaoClass() {
		return 'DAO_MailQueue';
	}
	
	function getSearchClass() {
		return 'SearchFields_MailQueue';
	}
	
	function getViewClass() {
		return 'View_MailQueue';
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=draft&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		/* @var $model Model_MailQueue */
		
		if(is_null($model))
			$model = new Model_MailQueue();
		
		$properties['is_queued'] = array(
			'label' => mb_ucfirst($translate->_('mail_queue.is_queued')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_queued,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['queue_fails'] = array(
			'label' => mb_ucfirst($translate->_('mail_queue.queue_fails')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->queue_fails,
		);
		
		$properties['queue_delivery_date'] = array(
			'label' => mb_ucfirst($translate->_('mail_queue.queue_delivery_date')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->queue_delivery_date,
		);
		
		$properties['ticket_id'] = array(
			'label' => mb_ucfirst($translate->_('common.ticket')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->ticket_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_TICKET,
			],
		);
		
		$properties['to'] = array(
			'label' => mb_ucfirst($translate->_('message.header.to')),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $model->hint_to,
		);
		
		$properties['type'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->type,
		);
		
		$properties['updated'] = array(
			'label' => mb_ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated,
		);
		
		$properties['worker_id'] = array(
			'label' => mb_ucfirst($translate->_('common.worker')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->worker_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_WORKER,
			],
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($draft = DAO_MailQueue::get($context_id)))
			return false;
		
		//$url = $this->profileGetUrl($context_id);
		//$friendly = DevblocksPlatform::strToPermalink($task->title);
		
// 		if(!empty($friendly))
// 			$url .= '-' . $friendly;
		
		return array(
			'id' => $context_id,
			'name' => $draft->name,
			'permalink' => '',
			'updated' => $draft->updated,
		);
	}
	
	function getRandom() {
		return DAO_MailQueue::random();
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
			'to',
			'name',
			'updated',
		);
	}
	
	function getContext($object, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Draft:';
		
		$translate = DevblocksPlatform::getTranslationService();

		// Polymorph
		if(is_numeric($object)) {
			$object = DAO_MailQueue::get($object);
		} elseif($object instanceof Model_MailQueue) {
			// It's what we want already.
		} elseif(is_array($object)) {
			$object = Cerb_ORMHelper::recastArrayToModel($object, 'Model_MailQueue');
		} else {
			$object = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'content' => $prefix.$translate->_('common.content'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'to' => $prefix.$translate->_('message.header.to'),
			'updated' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'to' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
		);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_DRAFT;
		$token_values['_types'] = $token_types;
		
		if($object) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $object->name;
			$token_values['id'] = $object->id;
			$token_values['name'] = $object->name;
			$token_values['to'] = $object->hint_to;
			$token_values['updated'] = $object->updated;
			
			$token_values['worker_id'] = $object->worker_id;
			
			// Deprecated
			$token_values['subject'] = $object->name;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($object, $token_values);
		}
		
		// Worker
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'worker_',
			$prefix.'Worker:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_MailQueue::ID,
			'is_queued' => DAO_MailQueue::IS_QUEUED,
			'links' => '_links',
			'queue_delivery_date' => DAO_MailQueue::QUEUE_DELIVERY_DATE,
			'queue_fails' => DAO_MailQueue::QUEUE_FAILS,
			'name' => DAO_MailQueue::NAME,
			'to' => DAO_MailQueue::HINT_TO,
			'ticket_id' => DAO_MailQueue::TICKET_ID,
			'type' => DAO_MailQueue::TYPE,
			'updated' => DAO_MailQueue::UPDATED,
			'worker_id' => DAO_MailQueue::WORKER_ID,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['params'] = [
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
			'_reference' => [
				'params (mail.compose)' => [
					'bcc' => 'The `Bcc:` recipients',
					'bucket_id' => 'The [bucket](/docs/records/types/bucket/) ID to move the ticket to',
					'cc' => 'The `Cc:` recipients',
					'content' => 'The message content',
					'custom_fields' => 'An object with custom field IDs as keys and their values',
					'format' => '`parsedown` (Markdown), or blank for plaintext',
					'file_ids' => 'An array of [attachment](/docs/records/types/attachment/) IDs',
					'group_id' => 'The [group](/docs/records/types/group/) ID to move the ticket to',
					'org_id' => 'The [org](/docs/records/types/org/) ID to assign',
					'org_name' => 'The [org](/docs/records/types/org/) name to assign',
					'owner_id' => 'The [worker](/docs/records/types/worker/) ID to assign',
					'send_at' => 'The optional timestamp to deliver the message at',
					'status_id' => '`0` (open), `1` (waiting), `2` (closed)',
					'subject' => 'The message `Subject:`',
					'ticket_reopen' => 'When the status is waiting or closed, the timestamp to reopen at',
					'to' => 'The `To:` recipients',
				],
				'params (ticket.reply / ticket.forward)' => [
					'bcc' => 'The `Bcc:` recipients',
					'bucket_id' => 'The [bucket](/docs/records/types/bucket/) ID to move the ticket to',
					'cc' => 'The `Cc:` recipients',
					'content' => 'The message content',
					'custom_fields' => 'An object with custom field IDs as keys and their values',
					'file_ids' => 'An array of [attachment](/docs/records/types/attachment/) IDs',
					'format' => '`parsedown` (Markdown), or blank for plaintext',
					'group_id' => 'The [group](/docs/records/types/group/) ID to move the ticket to',
					'in_reply_message_id' => 'The [message](/docs/records/types/message/) ID being responded to',
					'owner_id' => 'The [worker](/docs/records/types/worker/) ID to assign',
					'send_at' => 'The optional timestamp to deliver the message at',
					'status_id' => '`0` (open), `1` (waiting), `2` (closed)',
					'subject' => 'The message `Subject:`',
					'ticket_reopen' => 'When the status is waiting or closed, the timestamp to reopen at',
					'to' => 'The `To:` recipients',
				],
			],
		];
		
		$keys['name']['notes'] = "The subject line of the draft message";
		$keys['to']['notes'] = "The `To:` line of the draft message";
		$keys['type']['notes'] = "The type of draft: `mail.compose`, `ticket.reply`, or `ticket.forward`";
		$keys['worker_id']['notes'] = "The ID of the [worker](/docs/records/types/worker/) who owns the draft";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_MailQueue::PARAMS_JSON] = $json;
				break;
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
		
		$context = CerberusContexts::CONTEXT_DRAFT;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
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
		$view->name = 'Drafts';
		
		$view->view_columns = array(
			SearchFields_MailQueue::HINT_TO,
			SearchFields_MailQueue::TYPE,
			SearchFields_MailQueue::WORKER_ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::UPDATED,
		);
		
		$view->addColumnsHidden(array(
			SearchFields_MailQueue::TICKET_ID,
		));
		
		$view->renderSortBy = SearchFields_MailQueue::UPDATED;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		// [TODO]
		return NULL;
		
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Drafts';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
// 				new DevblocksSearchCriteria(SearchFields_MailQueue::CONTEXT_LINK,'=',$context),
// 				new DevblocksSearchCriteria(SearchFields_MailQueue::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id = 0, $view_id = '', $edit = false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_DRAFT;
		
		$tpl->assign('view_id', $view_id);
		
		$draft = null;
		
		if($context_id) {
			if(false == ($draft = DAO_MailQueue::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($draft) {
				if(!Context_Draft::isWriteableByActor($draft, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
			} else {
				$draft = new Model_MailQueue();
			}
			
			$tpl->assign('draft', $draft);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/draft/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $draft);
		}
	}
};