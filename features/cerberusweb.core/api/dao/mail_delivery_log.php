<?php
class DAO_MailDeliveryLog extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const FROM_ID = 'from_id';
	const HEADER_MESSAGE_ID = 'header_message_id';
	const ID = 'id';
	const MAIL_TRANSPORT_ID = 'mail_transport_id';
	const PROPERTIES_JSON = 'properties_json';
	const STATUS_ID = 'status_id';
	const STATUS_MESSAGE = 'status_message';
	const SUBJECT = 'subject';
	const TO = 'to';
	const TYPE = 'type';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
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
			->addField(self::MAIL_TRANSPORT_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MAIL_TRANSPORT, true))
		;
		$validation
			->addField(self::PROPERTIES_JSON)
			->string()
			->setMaxLength('24 bits')
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
			->setMaxLength(255)
		;
		$validation
			->addField(self::SUBJECT)
			->string($validation::STRING_UTF8MB4)
		;
		$validation
			->addField(self::TO)
			->string()
		;
		$validation
			->addField(self::TYPE)
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
		
		if(!array_key_exists(DAO_MailDeliveryLog::CREATED_AT, $fields))
			$fields[DAO_MailDeliveryLog::CREATED_AT] = time();
		
		$sql = "INSERT INTO mail_delivery_log () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_MailDeliveryLog::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function createFromEmailDeliverySuccess(Model_DevblocksOutboundEmail $email_model, Model_MailTransport $transport) : int|false {
		return self::_createFromEmailDelivery($email_model, $transport);
	}
	
	public static function createFromEmailDeliveryFailure(Model_DevblocksOutboundEmail $email_model, Model_MailTransport $transport, string $error_message='') : int|false {
		return self::_createFromEmailDelivery($email_model, $transport, $error_message);
	}
	
	private static function _createFromEmailDelivery(Model_DevblocksOutboundEmail $email_model, Model_MailTransport $transport, string $error_message='') : int|false {
		$properties = $email_model->getProperties();
		
		// Compress empty properties
		$properties = array_filter($properties, function($v) {
			if(is_string($v) && !strlen($v))
				return false;
			
			if(is_array($v) && empty($v))
				return false;
			
			return true;
		});
		
		// If sensitive, don't log the content
		if(!DEVELOPMENT_MODE && $email_model->getProperty('is_sensitive')) {
			if(array_key_exists('content', $properties))
				$properties['content'] = '(redacted)';
		}
		
		// Sort properties by key name
		ksort($properties);
		
		$fields = [
			DAO_MailDeliveryLog::TYPE => $email_model->getType(),
			DAO_MailDeliveryLog::STATUS_ID => $error_message ? 2 : 0,
			DAO_MailDeliveryLog::STATUS_MESSAGE => $error_message,
			DAO_MailDeliveryLog::CREATED_AT => time(),
			DAO_MailDeliveryLog::TO => implode(', ', array_keys($email_model->getTo())),
			DAO_MailDeliveryLog::FROM_ID => $email_model->getFromAddressModel()->id ?? 0,
			DAO_MailDeliveryLog::SUBJECT => $email_model->getSubject(),
			DAO_MailDeliveryLog::HEADER_MESSAGE_ID => '<' . ($email_model->getProperty('outgoing_message_id') ?? '') . '>',
			DAO_MailDeliveryLog::MAIL_TRANSPORT_ID => $transport->id,
			DAO_MailDeliveryLog::PROPERTIES_JSON => json_encode($properties),
		];
		
		if(!DAO_MailDeliveryLog::validate($fields, $error))
			return false;
			
		return DAO_MailDeliveryLog::create($fields);
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];
		
		$context = Context_MailDeliveryLog::ID;
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
			parent::_update($batch_ids, 'mail_delivery_log', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.mail_delivery_log.update',
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
		parent::_updateWhere('mail_delivery_log', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$context = Context_MailDeliveryLog::ID;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_MailDeliveryLog[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT `id`, `type`, `status_id`, `status_message`, `created_at`, `to`, `from_id`, `subject`, `header_message_id`, `mail_transport_id` ".
			"FROM `mail_delivery_log` ".
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
	 * @return Model_MailDeliveryLog
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
	
	/**
	 *
	 * @param array $ids
	 * @return Model_MailDeliveryLog[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	public static function getPropertiesById($context_id) : array {
		$db = DevblocksPlatform::services()->database();
		
		$json_string = $db->GetOneReader(sprintf("SELECT properties_json FROM mail_delivery_log WHERE id = %d",
			$context_id
		));
		
		if(!$json_string || false === ($properties = json_decode($json_string, true)))
			return [];
		
		// Make sure our content is redacted if we're not in DEVELOPMENT_MODE
		if(!DEVELOPMENT_MODE && ($properties['is_sensitive'] ?? false)) {
			if(array_key_exists('content', $properties))
				$properties['content'] = '(redacted)';
		}
		
		return $properties;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_MailDeliveryLog[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_MailDeliveryLog();
			$object->created_at = intval($row['created_at']);
			$object->from_id = intval($row['from_id']);
			$object->header_message_id = $row['header_message_id'];
			$object->id = intval($row['id']);
			$object->mail_transport_id = intval($row['mail_transport_id']);
			$object->status_id = intval($row['status_id']);
			$object->status_message = $row['status_message'];
			$object->subject = $row['subject'];
			$object->to = $row['to'];
			$object->type = $row['type'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('mail_delivery_log');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = Context_MailDeliveryLog::ID;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM mail_delivery_log WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		return true;
	}
	
	public static function maint() : bool {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM mail_delivery_log WHERE created_at BETWEEN 0 AND %d",
			strtotime('today -90 days')
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_MailDeliveryLog::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_MailDeliveryLog', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"mail_delivery_log.id as %s, ".
			"mail_delivery_log.type as %s, ".
			"mail_delivery_log.status_id as %s, ".
			"mail_delivery_log.status_message as %s, ".
			"mail_delivery_log.created_at as %s, ".
			"mail_delivery_log.to as %s, ".
			"mail_delivery_log.from_id as %s, ".
			"mail_delivery_log.subject as %s, ".
			"mail_delivery_log.header_message_id as %s, ".
			"mail_delivery_log.mail_transport_id as %s",
			SearchFields_MailDeliveryLog::ID,
			SearchFields_MailDeliveryLog::TYPE,
			SearchFields_MailDeliveryLog::STATUS_ID,
			SearchFields_MailDeliveryLog::STATUS_MESSAGE,
			SearchFields_MailDeliveryLog::CREATED_AT,
			SearchFields_MailDeliveryLog::TO,
			SearchFields_MailDeliveryLog::FROM_ID,
			SearchFields_MailDeliveryLog::SUBJECT,
			SearchFields_MailDeliveryLog::HEADER_MESSAGE_ID,
			SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID
		);
		
		$join_sql = "FROM mail_delivery_log ";
		
		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_MailDeliveryLog');
		
		return [
			'primary_table' => 'mail_delivery_log',
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
			SearchFields_MailDeliveryLog::ID,
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

class SearchFields_MailDeliveryLog extends DevblocksSearchFields {
	const CREATED_AT = 'm_created_at';
	const FROM_ID = 'm_from_id';
	const HEADER_MESSAGE_ID = 'm_header_message_id';
	const ID = 'm_id';
	const MAIL_TRANSPORT_ID = 'm_mail_transport_id';
	const STATUS_ID = 'm_status_id';
	const STATUS_MESSAGE = 'm_status_message';
	const SUBJECT = 'm_subject';
	const TO = 'm_to';
	const TYPE = 'm_type';
	
	const VIRTUAL_SENDER_SEARCH = '*_sender_search';
	const VIRTUAL_TRANSPORT_SEARCH = '*_transport_search';
	
	static private $_fields = null;
	
	static function getTableName() : string {
		return 'mail_delivery_log';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_MailDeliveryLog::ID);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_MailDeliveryLog::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_MailDeliveryLog::ID => new DevblocksSearchFieldContextKeys('mail_delivery_log.id', self::ID),
		];
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_SENDER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'mail_delivery_log.from_id');
				
			case self::VIRTUAL_TRANSPORT_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_MAIL_TRANSPORT, 'mail_delivery_log.mail_transport_id');
				
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_MailDeliveryLog::ID, self::getPrimaryKey())))
						return $virtual_where_sql;
					
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID:
			case SearchFields_MailDeliveryLog::FROM_ID:
			case SearchFields_MailDeliveryLog::STATUS_ID:
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_MailDeliveryLog::FROM_ID:
				$models = DAO_Address::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'email', 'id');
			
			case SearchFields_MailDeliveryLog::ID:
				$models = DAO_MailDeliveryLog::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
			
			case SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID:
				$models = DAO_MailTransport::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				
			case SearchFields_MailDeliveryLog::STATUS_ID:
				return [
					0 => 'sent',
					1 => 'delivered',
					2 => 'failed',
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'mail_delivery_log', 'created_at', $translate->_('common.created'), null, true),
			self::FROM_ID => new DevblocksSearchField(self::FROM_ID, 'mail_delivery_log', 'from_id', $translate->_('message.header.from'), null, true),
			self::HEADER_MESSAGE_ID => new DevblocksSearchField(self::HEADER_MESSAGE_ID, 'mail_delivery_log', 'header_message_id', $translate->_('dao.mail_log.header_message_id'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'mail_delivery_log', 'id', $translate->_('common.id'), null, true),
			self::MAIL_TRANSPORT_ID => new DevblocksSearchField(self::MAIL_TRANSPORT_ID, 'mail_delivery_log', 'mail_transport_id', $translate->_('common.email_transport'), null, true),
			self::STATUS_ID => new DevblocksSearchField(self::STATUS_ID, 'mail_delivery_log', 'status_id', $translate->_('common.status'), null, true),
			self::STATUS_MESSAGE => new DevblocksSearchField(self::STATUS_MESSAGE, 'mail_delivery_log', 'status_message', $translate->_('dao.mail_log.status_message'), null, true),
			self::SUBJECT => new DevblocksSearchField(self::SUBJECT, 'mail_delivery_log', 'subject', $translate->_('message.header.subject'), null, true),
			self::TO => new DevblocksSearchField(self::TO, 'mail_delivery_log', 'to', $translate->_('message.header.to'), null, true),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'mail_delivery_log', 'type', $translate->_('common.type'), null, true),
			
			self::VIRTUAL_SENDER_SEARCH => new DevblocksSearchField(self::VIRTUAL_SENDER_SEARCH, '*', 'sender_search', null, null, false),
			self::VIRTUAL_TRANSPORT_SEARCH => new DevblocksSearchField(self::VIRTUAL_TRANSPORT_SEARCH, '*', 'transport_search', null, null, false),
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

class Model_MailDeliveryLog extends DevblocksRecordModel {
	public $created_at;
	public $from_id;
	public $header_message_id;
	public $id;
	public $mail_transport_id;
	public $status_id;
	public $status_message;
	public $subject;
	public $to;
	public $type;
	
	public function getStatusText() : string {
		return match($this->status_id) {
			0 => 'sent',
			1 => 'delivered',
			2 => 'failed',
			default => '',
		};
	}
};

class View_MailDeliveryLog extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'mail_delivery_log';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Mail Delivery Log');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_MailDeliveryLog::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_MailDeliveryLog::STATUS_ID,
			SearchFields_MailDeliveryLog::TYPE,
			SearchFields_MailDeliveryLog::TO,
			SearchFields_MailDeliveryLog::FROM_ID,
			SearchFields_MailDeliveryLog::STATUS_MESSAGE,
			SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID,
			SearchFields_MailDeliveryLog::CREATED_AT,
		];
		$this->addColumnsHidden([
			SearchFields_MailDeliveryLog::VIRTUAL_SENDER_SEARCH,
			SearchFields_MailDeliveryLog::VIRTUAL_TRANSPORT_SEARCH,
			DevblocksSearchField::VIRTUAL_CONTEXT_LINK,
			DevblocksSearchField::VIRTUAL_HAS_FIELDSET,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_MailDeliveryLog::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_MailDeliveryLog');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_MailDeliveryLog', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_MailDeliveryLog', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Fields
					case SearchFields_MailDeliveryLog::FROM_ID:
					case SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID:
					case SearchFields_MailDeliveryLog::STATUS_ID:
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
		$context = Context_MailDeliveryLog::ID;
		
		if(!array_key_exists($column, $fields))
			return [];
		
		switch($column) {
			case SearchFields_MailDeliveryLog::FROM_ID:
				$label_map = function($ids) {
					return SearchFields_MailDeliveryLog::getLabelsForKeyValues(SearchFields_MailDeliveryLog::FROM_ID, $ids);
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map);
				break;
				
			case SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID:
				$label_map = function($ids) {
					return SearchFields_MailDeliveryLog::getLabelsForKeyValues(SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID, $ids);
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map);
				break;
				
			case SearchFields_MailDeliveryLog::STATUS_ID:
				$label_map = [
					0 => 'sent',
					1 => 'delivered',
					2 => 'failed',
				];
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map);
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
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_MailDeliveryLog::getFields();
		
		$fields = [
			'text' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::SUBJECT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'created' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::CREATED_AT],
				],
			'fieldset' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_MailDeliveryLog::ID],
					]
				],
			'from' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailDeliveryLog::VIRTUAL_SENDER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				],
			'from.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::FROM_ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				],
			'header.messageId' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::HEADER_MESSAGE_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX],
				],
			'id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::ID],
					'examples' => [
						['type' => 'chooser', 'context' => Context_MailDeliveryLog::ID, 'q' => ''],
					]
				],
			'mailTransport' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailDeliveryLog::VIRTUAL_TRANSPORT_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_MAIL_TRANSPORT, 'q' => ''],
					]
				],
			'mailTransport.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MAIL_TRANSPORT, 'q' => ''],
					]
				],
			'status' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::STATUS_ID],
					'examples' => [
						'sent',
						'delivered',
						'failed',
					],
				],
			'status.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::STATUS_ID],
				],
			'subject' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::SUBJECT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'to' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::TO, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'type' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_MailDeliveryLog::TYPE],
					'examples' => ['mail.compose','mail.transactional','ticket.forward','ticket.reply'],
				],
		];
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_MailDeliveryLog::ID, $fields, null);
		
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
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_MailDeliveryLog::VIRTUAL_SENDER_SEARCH);
				
			case 'mailTransport':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_MailDeliveryLog::VIRTUAL_TRANSPORT_SEARCH);
				
			case 'status':
				$field_key = SearchFields_MailDeliveryLog::STATUS_ID;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
				
				$values = [];
				
				// Normalize status labels
				foreach($value as $status) {
					if(DevblocksPlatform::strStartsWith($status, 's')) {
						$values['0'] = true;
					} else if(DevblocksPlatform::strStartsWith($status, 'd')) {
						$values['1'] = true;
					} else if(DevblocksPlatform::strStartsWith($status, 'f')) {
						$values['2'] = true;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
			
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
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(Context_MailDeliveryLog::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Mail Transports
		if(in_array(SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID, $this->view_columns)) {
			$mail_transports = DAO_MailTransport::getAll();
			$tpl->assign('mail_transports', $mail_transports);
		}
		
		// Sender addresses
		if(in_array(SearchFields_MailDeliveryLog::FROM_ID, $this->view_columns)) {
			$sender_addresses = DAO_Address::getIds(array_column($data ?? [], SearchFields_MailDeliveryLog::FROM_ID) ?? []);
			$tpl->assign('sender_addresses', $sender_addresses);
		}
		
		// Data
		$tpl->assign('total', $total);
		$tpl->assign('data', $data);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/mail_delivery_log/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;
		
		switch($field) {
			case SearchFields_MailDeliveryLog::FROM_ID:
			case SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID:
			case SearchFields_MailDeliveryLog::STATUS_ID:
				$label_map = SearchFields_MailDeliveryLog::getLabelsForKeyValues($field, $values);
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
			case SearchFields_MailDeliveryLog::VIRTUAL_SENDER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('message.header.from')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_MailDeliveryLog::VIRTUAL_TRANSPORT_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.email_transport')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_MailDeliveryLog::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_MailDeliveryLog::STATUS_MESSAGE:
			case SearchFields_MailDeliveryLog::SUBJECT:
			case SearchFields_MailDeliveryLog::TO:
			case SearchFields_MailDeliveryLog::TYPE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_MailDeliveryLog::FROM_ID:
			case SearchFields_MailDeliveryLog::HEADER_MESSAGE_ID:
			case SearchFields_MailDeliveryLog::ID:
			case SearchFields_MailDeliveryLog::MAIL_TRANSPORT_ID:
			case SearchFields_MailDeliveryLog::STATUS_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_MailDeliveryLog::CREATED_AT:
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

class Context_MailDeliveryLog extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.mail.delivery.log';
	const URI = 'mail_delivery_log';
	
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
		return DAO_MailDeliveryLog::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=mail_delivery_log&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_MailDeliveryLog();
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];
		
		$properties['from_id'] = [
			'label' => mb_ucfirst($translate->_('message.header.from')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->from_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			],
		];
		
		$properties['header_message_id'] = [
			'label' => mb_ucfirst($translate->_('dao.mail_log.header_message_id')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->header_message_id,
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		$properties['mail_transport_id'] = [
			'label' => mb_ucfirst($translate->_('common.email_transport')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->mail_transport_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_MAIL_TRANSPORT,
			],
		];
		
		$properties['status_id'] = [
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->getStatusText(),
		];
		
		$properties['status_message'] = [
			'label' => mb_ucfirst($translate->_('dao.mail_log.status_message')),
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
		
		$properties['to'] = [
			'label' => mb_ucfirst($translate->_('message.header.to')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->to,
		];
		
		$properties['type'] = [
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->type,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($mail_delivery_log = DAO_MailDeliveryLog::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($mail_delivery_log->subject);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $mail_delivery_log->id,
			'name' => $mail_delivery_log->subject,
			'permalink' => $url,
			'updated' => $mail_delivery_log->created_at,
		];
	}
	
	function getDefaultProperties() : array {
		return [
			'to',
			'from_id',
			'subject',
			'mail_transport_id',
		];
	}
	
	function getContext($mail_delivery_log, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Mail Delivery Log:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_MailDeliveryLog::ID);
		
		// Polymorph
		if(is_numeric($mail_delivery_log)) {
			$mail_delivery_log = DAO_MailDeliveryLog::get($mail_delivery_log);
		} elseif($mail_delivery_log instanceof Model_MailDeliveryLog) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($mail_delivery_log)) {
			$mail_delivery_log = Cerb_ORMHelper::recastArrayToModel($mail_delivery_log, 'Model_MailDeliveryLog');
		} else {
			$mail_delivery_log = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'from_id' => $prefix.$translate->_('message.header.from'),
			'header_message_id' => $prefix.$translate->_('dao.mail_log.header_message_id'),
			'id' => $prefix.$translate->_('common.id'),
			'mail_transport_id' => $prefix.$translate->_('common.email_transport'),
			'subject' => $prefix.$translate->_('message.header.subject'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'status_id' => $prefix.$translate->_('common.status'),
			'status_message' => $prefix.$translate->_('dao.mail_log.status_message'),
			'to' => $prefix.$translate->_('message.header.to'),
			'type' => $prefix.$translate->_('common.type'),
		];
		
		// Token types
		$token_types = [
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'from_id' => 'id',
			'header_message_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'mail_transport_id' => 'id',
			'subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'status_id' => Model_CustomField::TYPE_NUMBER,
			'status_message' => Model_CustomField::TYPE_SINGLE_LINE,
			'to' => Model_CustomField::TYPE_SINGLE_LINE,
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
		];
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_MailDeliveryLog::ID;
		$token_values['_type'] = 'mail_delivery_log';
		$token_values['_types'] = $token_types;
		$token_values['from__context'] = CerberusContexts::CONTEXT_ADDRESS;
		$token_values['mail_transport__context'] = CerberusContexts::CONTEXT_MAIL_TRANSPORT;
		
		if($mail_delivery_log) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $mail_delivery_log->subject;
			$token_values['created_at'] = $mail_delivery_log->created_at;
			$token_values['from_id'] = $mail_delivery_log->from_id;
			$token_values['header_message_id'] = $mail_delivery_log->header_message_id;
			$token_values['id'] = $mail_delivery_log->id;
			$token_values['mail_transport_id'] = $mail_delivery_log->mail_transport_id;
			$token_values['subject'] = $mail_delivery_log->subject;
			$token_values['status_id'] = $mail_delivery_log->status_id;
			$token_values['status_message'] = $mail_delivery_log->status_message;
			$token_values['to'] = $mail_delivery_log->to;
			$token_values['type'] = $mail_delivery_log->type;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($mail_delivery_log, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=mail_delivery_log&id=%d-%s",$mail_delivery_log->id, DevblocksPlatform::strToPermalink($mail_delivery_log->subject)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() : array {
		return [
			'created_at' => DAO_MailDeliveryLog::CREATED_AT,
			'from_id' => DAO_MailDeliveryLog::FROM_ID,
			'header_message_id' => DAO_MailDeliveryLog::HEADER_MESSAGE_ID,
			'id' => DAO_MailDeliveryLog::ID,
			'links' => '_links',
			'mail_transport_id' => DAO_MailDeliveryLog::MAIL_TRANSPORT_ID,
			'status_id' => DAO_MailDeliveryLog::STATUS_ID,
			'status_message' => DAO_MailDeliveryLog::STATUS_MESSAGE,
			'subject' => DAO_MailDeliveryLog::SUBJECT,
			'to' => DAO_MailDeliveryLog::TO,
			'type' => DAO_MailDeliveryLog::TYPE,
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
		
		$lazy_keys['properties'] = [
			'label' => 'Properties',
			'type' => 'Object',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_MailDeliveryLog::ID;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'properties':
				$values[$token] = DAO_MailDeliveryLog::getPropertiesById($context_id) ?: [];
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
		$view->name = 'Mail Delivery Log';
		$view->renderSortBy = SearchFields_MailDeliveryLog::CREATED_AT;
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
		$view->name = 'Mail Delivery Log';
		
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
		$context = Context_MailDeliveryLog::ID;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(!($model = DAO_MailDeliveryLog::get($context_id)))
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
			$tpl->display('devblocks:cerberusweb.core::records/types/mail_delivery_log/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
