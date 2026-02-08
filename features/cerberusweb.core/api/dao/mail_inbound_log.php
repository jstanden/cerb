<?php
class DAO_MailInboundLog extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const EVENTS_LOG_JSON = 'events_log_json';
	const FROM_ID = 'from_id';
	const HEADER_MESSAGE_ID = 'header_message_id';
	const ID = 'id';
	const MAILBOX_ID = 'mailbox_id';
	const MESSAGE_ID = 'message_id';
	const PARSE_TIME_MS = 'parse_time_ms';
	const STATUS_ID = 'status_id';
	const STATUS_MESSAGE = 'status_message';
	const SUBJECT = 'subject';
	const TICKET_ID = 'ticket_id';
	const TO = 'to';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::EVENTS_LOG_JSON)
			->string()
			->setMaxLength('24 bits')
		;
		$validation
			->addField(self::FROM_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS, true))
		;
		$validation
			->addField(self::HEADER_MESSAGE_ID)
			->string()
		;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
		;
		$validation
			->addField(self::MAILBOX_ID)
			->id()
			//->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MAILBOX, true))
		;
		$validation
			->addField(self::MESSAGE_ID)
			->id()
			//->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MESSAGE, true))
		;
		$validation
			->addField(self::PARSE_TIME_MS)
			->uint(4)
		;
		$validation
			->addField(self::STATUS_ID)
			->number()
			->setMin(0)
			->setMax(2)
		;
		$validation
			->addField(self::STATUS_MESSAGE)
			->string()
		;
		$validation
			->addField(self::SUBJECT)
			->string($validation::STRING_UTF8MB4)
		;
		$validation
			->addField(self::TICKET_ID)
			->id()
			//->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_TICKET, true))
		;
		$validation
			->addField(self::TO)
			->string()
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
		
		if(!array_key_exists(DAO_MailInboundLog::CREATED_AT, $fields))
			$fields[DAO_MailInboundLog::CREATED_AT] = time();
		
		$sql = "INSERT INTO mail_inbound_log () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_MailInboundLog::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function createFromModel(CerberusParserModel $model) : int|false {
		$header_message_id = $model->getHeaders()['message-id'] ?? '';
		
		if(is_array($header_message_id))
			$header_message_id = array_shift($header_message_id);
		
		$fields = [
			self::CREATED_AT => time(),
			self::HEADER_MESSAGE_ID => (is_string($header_message_id) && $header_message_id) ? $header_message_id : '',
			self::FROM_ID => $model->getSenderAddressModel()->id ?? 0,
			self::TO => implode(',', $model->getRecipients() ?: []),
			self::SUBJECT => $model->getSubject() ?? '',
			self::MAILBOX_ID => 0,
			self::MESSAGE_ID => $model->getMessageId() ?? 0,
			self::PARSE_TIME_MS => $model->getParseTimeMs() ?? 0,
			self::STATUS_ID => $model->getExitState() ?? 0,
			self::STATUS_MESSAGE => $model->getStatusMessage() ?? '',
			self::TICKET_ID => $model->getTicketId() ?? 0,
		];
		
		if(($mailbox_name = $model->getHeader('x-cerberus-mailbox'))) {
			if(($mailbox = DAO_Mailbox::getByName($mailbox_name)))
				$fields[self::MAILBOX_ID] = $mailbox->id;
		}
		
		// Log the events that affected this message
		if(($event_results = $model->getEventResults())) {
			$fields[self::EVENTS_LOG_JSON] = json_encode($event_results);
		}
		
		$error = null;
		
		if(!self::validate($fields, $error)) {
			DevblocksPlatform::logError($error, true);
			return false;
		}
			
		return self::create($fields);
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];
		
		$context = Context_MailInboundLog::ID;
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
			parent::_update($batch_ids, 'mail_inbound_log', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.mail_inbound_log.update',
						[
							'fields' => $fields,
						]
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('mail_inbound_log', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$context = Context_MailInboundLog::ID;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_MailInboundLog[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT `id`, `status_id`, `status_message`, `created_at`, `to`, `from_id`, `ticket_id`, `message_id`, `subject`, `header_message_id`, `mailbox_id`, `parse_time_ms` ".
			"FROM mail_inbound_log ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & DevblocksORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param integer $id
	 * @return Model_MailInboundLog	 */
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
	
	/**
	 *
	 * @param array $ids
	 * @return Model_MailInboundLog[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_MailInboundLog[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_MailInboundLog();
			$object->created_at = $row['created_at'];
			$object->from_id = intval($row['from_id']);
			$object->header_message_id = $row['header_message_id'];
			$object->id = intval($row['id']);
			$object->mailbox_id = intval($row['mailbox_id']);
			$object->message_id = intval($row['message_id']);
			$object->parse_time_ms = intval($row['parse_time_ms']);
			$object->status_id = intval($row['status_id']);
			$object->status_message = $row['status_message'];
			$object->subject = $row['subject'];
			$object->ticket_id = intval($row['ticket_id']);
			$object->to = $row['to'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('mail_inbound_log');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = Context_MailInboundLog::ID;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM mail_inbound_log WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		return true;
	}
	
	public static function maint() : bool {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM mail_inbound_log WHERE created_at BETWEEN 0 AND %d",
			strtotime('today -90 days')
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_MailInboundLog::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_MailInboundLog', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"mail_inbound_log.id as %s, ".
			"mail_inbound_log.status_id as %s, ".
			"mail_inbound_log.status_message as %s, ".
			"mail_inbound_log.created_at as %s, ".
			"mail_inbound_log.to as %s, ".
			"mail_inbound_log.from_id as %s, ".
			"mail_inbound_log.ticket_id as %s, ".
			"mail_inbound_log.message_id as %s, ".
			"mail_inbound_log.subject as %s, ".
			"mail_inbound_log.header_message_id as %s, ".
			"mail_inbound_log.mailbox_id as %s, ".
			"mail_inbound_log.parse_time_ms as %s ",
			SearchFields_MailInboundLog::ID,
			SearchFields_MailInboundLog::STATUS_ID,
			SearchFields_MailInboundLog::STATUS_MESSAGE,
			SearchFields_MailInboundLog::CREATED_AT,
			SearchFields_MailInboundLog::TO,
			SearchFields_MailInboundLog::FROM_ID,
			SearchFields_MailInboundLog::TICKET_ID,
			SearchFields_MailInboundLog::MESSAGE_ID,
			SearchFields_MailInboundLog::SUBJECT,
			SearchFields_MailInboundLog::HEADER_MESSAGE_ID,
			SearchFields_MailInboundLog::MAILBOX_ID,
			SearchFields_MailInboundLog::PARSE_TIME_MS
		);
		
		$join_sql = "FROM mail_inbound_log ";
		
		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_MailInboundLog');
		
		return [
			'primary_table' => 'mail_inbound_log',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
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
			SearchFields_MailInboundLog::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
	
	public static function getEventsLogById(int $id) {
		$db = DevblocksPlatform::services()->database();
		
		$events_log_json = $db->GetOneReader(sprintf("SELECT events_log_json FROM mail_inbound_log WHERE id = %d",
			$id
		));
		
		if(!$events_log_json || false === ($events_log = json_decode($events_log_json, true)))
			return [];
		
		return $events_log;
	}
};

class SearchFields_MailInboundLog extends DevblocksSearchFields {
	const CREATED_AT = 'm_created_at';
	const FROM_ID = 'm_from_id';
	const HEADER_MESSAGE_ID = 'm_header_message_id';
	const ID = 'm_id';
	const MAILBOX_ID = 'm_mailbox_id';
	const MESSAGE_ID = 'm_message_id';
	const PARSE_TIME_MS = 'm_parse_time_ms';
	const STATUS_ID = 'm_status_id';
	const STATUS_MESSAGE = 'm_status_message';
	const SUBJECT = 'm_subject';
	const TICKET_ID = 'm_ticket_id';
	const TO = 'm_to';
	
	const VIRTUAL_MAILBOX_SEARCH = '*_mailbox_search';
	const VIRTUAL_MESSAGE_SEARCH = '*_message_search';
	const VIRTUAL_SENDER_SEARCH = '*_sender_search';
	const VIRTUAL_TICKET_SEARCH = '*_ticket_search';
	
	static private $_fields = null;
	
	static function getTableName() : string {
		return 'mail_inbound_log';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_MailInboundLog::ID);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_MailInboundLog::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {		return [
			Context_MailInboundLog::ID => new DevblocksSearchFieldContextKeys('mail_inbound_log.id', self::ID),
		];
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_MAILBOX_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_MAILBOX, 'mail_inbound_log.mailbox_id');
			
			case self::VIRTUAL_MESSAGE_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_MESSAGE, 'mail_inbound_log.message_id');
			
			case self::VIRTUAL_SENDER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'mail_inbound_log.from_id');
			
			case self::VIRTUAL_TICKET_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_TICKET, 'mail_inbound_log.ticket_id');
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_MailInboundLog::ID, self::getPrimaryKey())))
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
			case SearchFields_MailInboundLog::FROM_ID:
				$models = DAO_Address::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'email', 'id');
			
			case SearchFields_MailInboundLog::ID:
				$models = DAO_MailInboundLog::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'subject', 'id');
				
			case SearchFields_MailInboundLog::MAILBOX_ID:
				$models = DAO_Mailbox::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				
			case SearchFields_MailInboundLog::STATUS_ID:
				return [
					0 => 'failed',
					1 => 'parsed',
					2 => 'rejected',
				];
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'mail_inbound_log', 'created_at', $translate->_('common.created'), null, true),
			self::FROM_ID => new DevblocksSearchField(self::FROM_ID, 'mail_inbound_log', 'from_id', $translate->_('message.header.from'), null, true),
			self::HEADER_MESSAGE_ID => new DevblocksSearchField(self::HEADER_MESSAGE_ID, 'mail_inbound_log', 'header_message_id', $translate->_('dao.mail_log.header_message_id'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'mail_inbound_log', 'id', $translate->_('common.id'), null, true),
			self::MAILBOX_ID => new DevblocksSearchField(self::MAILBOX_ID, 'mail_inbound_log', 'mailbox_id', $translate->_('common.mailbox'), null, true),
			self::MESSAGE_ID => new DevblocksSearchField(self::MESSAGE_ID, 'mail_inbound_log', 'message_id', $translate->_('common.message'), null, true),
			self::PARSE_TIME_MS => new DevblocksSearchField(self::PARSE_TIME_MS, 'mail_inbound_log', 'parse_time_ms', $translate->_('dao.mail_log.parse_time_ms'), null, true),
			self::STATUS_ID => new DevblocksSearchField(self::STATUS_ID, 'mail_inbound_log', 'status_id', $translate->_('common.status'), null, true),
			self::STATUS_MESSAGE => new DevblocksSearchField(self::STATUS_MESSAGE, 'mail_inbound_log', 'status_message', $translate->_('dao.mail_log.status_message'), null, true),
			self::SUBJECT => new DevblocksSearchField(self::SUBJECT, 'mail_inbound_log', 'subject', $translate->_('message.header.subject'), null, true),
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 'mail_inbound_log', 'ticket_id', $translate->_('common.ticket'), null, true),
			self::TO => new DevblocksSearchField(self::TO, 'mail_inbound_log', 'to', $translate->_('message.header.to'), null, true),
			
			self::VIRTUAL_MAILBOX_SEARCH => new DevblocksSearchField(self::VIRTUAL_MAILBOX_SEARCH, '*', 'mailbox_search', null, null, false),
			self::VIRTUAL_MESSAGE_SEARCH => new DevblocksSearchField(self::VIRTUAL_MESSAGE_SEARCH, '*', 'message_search', null, null, false),
			self::VIRTUAL_SENDER_SEARCH => new DevblocksSearchField(self::VIRTUAL_SENDER_SEARCH, '*', 'sender_search', null, null, false),
			self::VIRTUAL_TICKET_SEARCH => new DevblocksSearchField(self::VIRTUAL_TICKET_SEARCH, '*', 'ticket_search', null, null, false),
		];
		
		// Virtual fields
		if(($virtual_columns = DevblocksSearchField::getVirtualFields(watchers: false)))
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

class Model_MailInboundLog extends DevblocksRecordModel {
	public $created_at;
	public $from_id;
	public $header_message_id;
	public $id;
	public $mailbox_id;
	public $message_id;
	public $parse_time_ms;
	public $status_id;
	public $status_message;
	public $subject;
	public $ticket_id;
	public $to;
	
	public function getEventsLog() : array {
		return DAO_MailInboundLog::getEventsLogById($this->id);
	}
};

class View_MailInboundLog extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'mail_inbound_log';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Mail Inbound Log');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_MailInboundLog::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_MailInboundLog::STATUS_ID,
			SearchFields_MailInboundLog::FROM_ID,
			SearchFields_MailInboundLog::TO,
			SearchFields_MailInboundLog::STATUS_MESSAGE,
			SearchFields_MailInboundLog::MESSAGE_ID,
			SearchFields_MailInboundLog::TICKET_ID,
			SearchFields_MailInboundLog::MAILBOX_ID,
			SearchFields_MailInboundLog::PARSE_TIME_MS,
			SearchFields_MailInboundLog::CREATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_MailInboundLog::VIRTUAL_MAILBOX_SEARCH,
			SearchFields_MailInboundLog::VIRTUAL_MESSAGE_SEARCH,
			SearchFields_MailInboundLog::VIRTUAL_SENDER_SEARCH,
			SearchFields_MailInboundLog::VIRTUAL_TICKET_SEARCH,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_MailInboundLog::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_MailInboundLog');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_MailInboundLog', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_MailInboundLog', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Fields
					case SearchFields_MailInboundLog::FROM_ID:
					case SearchFields_MailInboundLog::MAILBOX_ID:
					case SearchFields_MailInboundLog::STATUS_ID:
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
		$context = Context_MailInboundLog::ID;
		
		if(!array_key_exists($column, $fields))
			return [];
		
		switch($column) {
			case SearchFields_MailInboundLog::FROM_ID:
				$label_map = function($ids) {
					return SearchFields_MailInboundLog::getLabelsForKeyValues(SearchFields_MailInboundLog::FROM_ID, $ids);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_MailInboundLog::MAILBOX_ID:
				$label_map = function($ids) {
					return SearchFields_MailInboundLog::getLabelsForKeyValues(SearchFields_MailInboundLog::MAILBOX_ID, $ids);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_MailInboundLog::STATUS_ID:
				$label_map = [
					'0' => 'failed',
					'1' => 'parsed',
					'2' => 'rejected',
				];
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
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
		return 'subject';
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_MailInboundLog::getFields();
		
		$fields = [
			'created' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_MailInboundLog::CREATED_AT],
				],
			'fieldset' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_MailInboundLog::ID],
					]
				],
			'from' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailInboundLog::VIRTUAL_SENDER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				],
			'from.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailInboundLog::FROM_ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				],
			'header.messageId' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailInboundLog::HEADER_MESSAGE_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX],
				],
			'id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailInboundLog::ID],
					'examples' => [
						['type' => 'chooser', 'context' => Context_MailInboundLog::ID, 'q' => ''],
					]
				],
			'mailbox' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailInboundLog::VIRTUAL_MAILBOX_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_MAILBOX, 'q' => ''],
					]
				],
			'mailbox.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailInboundLog::MAILBOX_ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MAILBOX, 'q' => ''],
					]
				],
			'message' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailInboundLog::VIRTUAL_MESSAGE_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_MESSAGE, 'q' => ''],
					]
				],
			'message.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailInboundLog::MESSAGE_ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MESSAGE, 'q' => ''],
					]
				],
			'parseTime.ms' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailInboundLog::PARSE_TIME_MS],
					'examples' => [],
				],
			'status' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_MailInboundLog::STATUS_ID],
					'examples' => [
						'parsed',
						'rejected',
						'failed',
					],
				],
			'status.message' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailInboundLog::STATUS_MESSAGE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'subject' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailInboundLog::SUBJECT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'ticket' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailInboundLog::VIRTUAL_TICKET_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				],
			'ticket.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailInboundLog::TICKET_ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				],
			'to' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailInboundLog::TO, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
		];
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_MailInboundLog::ID, $fields, null);
		
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
			
			case 'from':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_MailInboundLog::VIRTUAL_SENDER_SEARCH);
				
			case 'mailbox':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_MailInboundLog::VIRTUAL_MAILBOX_SEARCH);
				
			case 'message':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_MailInboundLog::VIRTUAL_MESSAGE_SEARCH);
				
			case 'status':
				$field_key = SearchFields_MailInboundLog::STATUS_ID;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
				
				$values = [];
				
				// Normalize status labels
				foreach($value as $status) {
					if(DevblocksPlatform::strStartsWith($status, 'f')) {
						$values['0'] = true;
					} else if(DevblocksPlatform::strStartsWith($status, 'p')) {
						$values['1'] = true;
					} else if(DevblocksPlatform::strStartsWith($status, 'r')) {
						$values['2'] = true;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
			
			case 'ticket':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_MailInboundLog::VIRTUAL_TICKET_SEARCH);
				
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
		
		list($data, $total) = $this->getData();
		
		// Mailboxes
		if(in_array(SearchFields_MailInboundLog::MAILBOX_ID, $this->view_columns)) {
			$mailbox_ids = array_unique(array_column($data ?? [], SearchFields_MailInboundLog::MAILBOX_ID) ?? []);
			$mailboxes = DAO_Mailbox::getIds($mailbox_ids);
			$tpl->assign('mailboxes', $mailboxes);
		}
		
		// Messages
		if(in_array(SearchFields_MailInboundLog::MESSAGE_ID, $this->view_columns)) {
			$message_ids = array_unique(array_column($data ?? [], SearchFields_MailInboundLog::MESSAGE_ID) ?? []);
			$messages = DAO_Message::getIds($message_ids);
			$tpl->assign('messages', $messages);
		}
		
		// Sender addresses
		if(in_array(SearchFields_MailInboundLog::FROM_ID, $this->view_columns)) {
			$sender_ids = array_unique(array_column($data ?? [], SearchFields_MailInboundLog::FROM_ID) ?? []);
			$sender_addresses = DAO_Address::getIds($sender_ids);
			$tpl->assign('sender_addresses', $sender_addresses);
		}
		
		// Tickets
		if(in_array(SearchFields_MailInboundLog::TICKET_ID, $this->view_columns)) {
			$ticket_ids = array_unique(array_column($data ?? [], SearchFields_MailInboundLog::TICKET_ID) ?? []);
			$tickets = DAO_Ticket::getIds($ticket_ids);
			$tpl->assign('tickets', $tickets);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(Context_MailInboundLog::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Data
		$tpl->assign('total', $total);
		$tpl->assign('data', $data);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/mail_inbound_log/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;
		
		switch($field) {
			case SearchFields_MailInboundLog::FROM_ID:
			case SearchFields_MailInboundLog::MAILBOX_ID:
			case SearchFields_MailInboundLog::STATUS_ID:
				$label_map = SearchFields_MailInboundLog::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function renderVirtualCriteria($param) : void {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_MailInboundLog::VIRTUAL_MAILBOX_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.mailbox')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_MailInboundLog::VIRTUAL_MESSAGE_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.message')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_MailInboundLog::VIRTUAL_SENDER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('message.header.from')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_MailInboundLog::VIRTUAL_TICKET_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.ticket')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_MailInboundLog::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_MailInboundLog::HEADER_MESSAGE_ID:
			case SearchFields_MailInboundLog::STATUS_MESSAGE:
			case SearchFields_MailInboundLog::SUBJECT:
			case SearchFields_MailInboundLog::TO:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_MailInboundLog::FROM_ID:
			case SearchFields_MailInboundLog::ID:
			case SearchFields_MailInboundLog::MAILBOX_ID:
			case SearchFields_MailInboundLog::MESSAGE_ID:
			case SearchFields_MailInboundLog::PARSE_TIME_MS:
			case SearchFields_MailInboundLog::STATUS_ID:
			case SearchFields_MailInboundLog::TICKET_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_MailInboundLog::CREATED_AT:
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

class Context_MailInboundLog extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.mail.inbound.log';
	const URI = 'mail_inbound_log';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_MailInboundLog::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=mail_inbound_log&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_MailInboundLog();
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];
		
		$properties['from_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('message.header.from'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->from_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			],
		];
		
		$properties['header_message_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('dao.mail_log.header_message_id'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->header_message_id,
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		$properties['mailbox_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.mailbox'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->mailbox_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_MAILBOX,
			],
		];
		
		$properties['message_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.message'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->message_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_MESSAGE,
			],
		];
		
		$properties['parse_time_ms'] = [
			'label' => DevblocksPlatform::translateCapitalized('dao.mail_log.parse_time_ms'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->parse_time_ms,
		];
		
		$properties['status_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.status'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => [
				0 => 'failed',
				1 => 'parsed',
				2 => 'rejected',
			][$model->status_id] ?? '',
		];
		
		$properties['status_message'] = [
			'label' => DevblocksPlatform::translateCapitalized('dao.mail_log.status_message'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->status_message,
		];
		
		$properties['subject'] = [
			'label' => mb_ucfirst($translate->_('message.header.subject')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		];
		
		$properties['ticket_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.ticket'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->ticket_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_TICKET,
			],
		];
		
		$properties['to'] = [
			'label' => DevblocksPlatform::translateCapitalized('message.header.to'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->to,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($mail_inbound_log = DAO_MailInboundLog::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($mail_inbound_log->subject);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $mail_inbound_log->id,
			'name' => $mail_inbound_log->subject,
			'permalink' => $url,
			'updated' => $mail_inbound_log->created_at,
		];
	}
	
	function getDefaultProperties() : array {
		return [
			'created_at',
			'to',
			'from_id',
			'subject',
			'mailbox_id',
			'parse_time_ms',
		];
	}
	
	function getContext($mail_inbound_log, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Mail Inbound Log:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_MailInboundLog::ID);
		
		// Polymorph
		if(is_numeric($mail_inbound_log)) {
			$mail_inbound_log = DAO_MailInboundLog::get($mail_inbound_log);
		} elseif($mail_inbound_log instanceof Model_MailInboundLog) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($mail_inbound_log)) {
			$mail_inbound_log = Cerb_ORMHelper::recastArrayToModel($mail_inbound_log, 'Model_MailInboundLog');
		} else {
			$mail_inbound_log = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'from_id' => $prefix.$translate->_('message.header.from'),
			'header_message_id' => $prefix.$translate->_('dao.mail_log.header_message_id'),
			'id' => $prefix.$translate->_('common.id'),
			'mailbox_id' => $prefix.$translate->_('common.mailbox'),
			'message_id' => $prefix.$translate->_('common.message'),
			'parse_time_ms' => $prefix.$translate->_('dao.mail_log.parse_time_ms'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'status_id' => $prefix.$translate->_('common.status'),
			'status_message' => $prefix.$translate->_('dao.mail_log.status_message'),
			'subject' => $prefix.$translate->_('message.header.subject'),
			'ticket_id' => $prefix.$translate->_('common.ticket'),
			'to' => $prefix.$translate->_('message.header.to'),
		];
		
		// Token types
		$token_types = [
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'from_id' => Model_CustomField::TYPE_NUMBER,
			'header_message_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'mailbox_id' => Model_CustomField::TYPE_NUMBER,
			'message_id' => Model_CustomField::TYPE_NUMBER,
			'parse_time_ms' => Model_CustomField::TYPE_NUMBER,
			'record_url' => Model_CustomField::TYPE_URL,
			'status_id' => Model_CustomField::TYPE_NUMBER,
			'status_message' => Model_CustomField::TYPE_SINGLE_LINE,
			'subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_id' => Model_CustomField::TYPE_NUMBER,
			'to' => Model_CustomField::TYPE_SINGLE_LINE,
		];
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_MailInboundLog::ID;
		$token_values['_type'] = 'mail_inbound_log';
		$token_values['_types'] = $token_types;
		$token_values['from__context'] = CerberusContexts::CONTEXT_ADDRESS;
		$token_values['mailbox__context'] = CerberusContexts::CONTEXT_MAILBOX;
		$token_values['message__context'] = CerberusContexts::CONTEXT_MESSAGE;
		$token_values['ticket__context'] = CerberusContexts::CONTEXT_TICKET;
		
		if($mail_inbound_log) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $mail_inbound_log->subject;
			$token_values['created_at'] = $mail_inbound_log->created_at;
			$token_values['from_id'] = $mail_inbound_log->from_id;
			$token_values['header_message_id'] = $mail_inbound_log->header_message_id;
			$token_values['id'] = $mail_inbound_log->id;
			$token_values['mailbox_id'] = $mail_inbound_log->mailbox_id;
			$token_values['message_id'] = $mail_inbound_log->message_id;
			$token_values['parse_time_ms'] = $mail_inbound_log->parse_time_ms;
			$token_values['status_id'] = $mail_inbound_log->status_id;
			$token_values['status_message'] = $mail_inbound_log->status_message;
			$token_values['subject'] = $mail_inbound_log->subject;
			$token_values['ticket_id'] = $mail_inbound_log->ticket_id;
			$token_values['to'] = $mail_inbound_log->to;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($mail_inbound_log, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=mail_inbound_log&id=%d-%s",$mail_inbound_log->id, DevblocksPlatform::strToPermalink($mail_inbound_log->subject)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_MailInboundLog::CREATED_AT,
			'events_log_json' => DAO_MailInboundLog::EVENTS_LOG_JSON,
			'from_id' => DAO_MailInboundLog::FROM_ID,
			'header_message_id' => DAO_MailInboundLog::HEADER_MESSAGE_ID,
			'id' => DAO_MailInboundLog::ID,
			'links' => '_links',
			'mailbox_id' => DAO_MailInboundLog::MAILBOX_ID,
			'message_id' => DAO_MailInboundLog::MESSAGE_ID,
			'parse_time_ms' => DAO_MailInboundLog::PARSE_TIME_MS,
			'status_id' => DAO_MailInboundLog::STATUS_ID,
			'status_message' => DAO_MailInboundLog::STATUS_MESSAGE,
			'subject' => DAO_MailInboundLog::SUBJECT,
			'ticket_id' => DAO_MailInboundLog::TICKET_ID,
			'to' => DAO_MailInboundLog::TO,
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
		
		$lazy_keys['events_log'] = [
			'label' => 'Events Log',
			'type' => 'Object[]',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_MailInboundLog::ID;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'events_log':
				$values[$token] = DAO_MailInboundLog::getEventsLogById($context_id) ?: [];
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
		$view->name = 'Mail Inbound Log';
		$view->renderSortBy = SearchFields_MailInboundLog::CREATED_AT;
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
		$view->name = 'Mail Inbound Log';
		
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
		$context = Context_MailInboundLog::ID;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(!($model = DAO_MailInboundLog::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
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
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/mail_inbound_log/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
