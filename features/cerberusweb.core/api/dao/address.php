<?php
class DAO_Address extends C4_ORMHelper {
	const ID = 'id';
	const EMAIL = 'email';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const CONTACT_ORG_ID = 'contact_org_id';
	const NUM_SPAM = 'num_spam';
	const NUM_NONSPAM = 'num_nonspam';
	const IS_BANNED = 'is_banned';
	const LAST_AUTOREPLY = 'last_autoreply';
	const IS_REGISTERED = 'is_registered';
	const PASS = 'pass';
	
	private function __construct() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			'id' => $translate->_('address.id'),
			'email' => $translate->_('address.email'),
			'first_name' => $translate->_('address.first_name'),
			'last_name' => $translate->_('address.last_name'),
			'contact_org_id' => $translate->_('address.contact_org_id'),
			'num_spam' => $translate->_('address.num_spam'),
			'num_nonspam' => $translate->_('address.num_nonspam'),
			'is_banned' => $translate->_('address.is_banned'),
			'is_registered' => $translate->_('address.is_registered'),
			'pass' => ucwords($translate->_('common.password')),
		);
	}
	
	/**
	 * Creates a new e-mail address record.
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
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('address_seq');

		if(null == ($email = @$fields[self::EMAIL]))
			return NULL;
		
		// [TODO] Validate
		@$addresses = imap_rfc822_parse_adrlist('<'.$email.'>', 'host');
		
		if(!is_array($addresses) || empty($addresses))
			return NULL;
		
		$address = array_shift($addresses);
		
		if(empty($address->host) || $address->host == 'host')
			return NULL;
		
		$full_address = trim(strtolower($address->mailbox.'@'.$address->host));
			
		// Make sure the address doesn't exist already
		if(null == ($check = self::getByEmail($full_address))) {
			$sql = sprintf("INSERT INTO address (id,email,first_name,last_name,contact_org_id,num_spam,num_nonspam,is_banned,is_registered,pass,last_autoreply) ".
				"VALUES (%d,%s,'','',0,0,0,0,0,'',0)",
				$id,
				$db->qstr($full_address)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		} else { // update
			$id = $check->id;
			unset($fields[self::ID]);
			unset($fields[self::EMAIL]);
		}

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'address', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('address', $fields, $where);
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK address_to_worker FROM address_to_worker LEFT JOIN worker ON address_to_worker.worker_id=worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' address_to_worker records.');
	}
	
    static function delete($ids) {
        if(!is_array($ids)) $ids = array($ids);

		if(empty($ids))
			return;

		$db = DevblocksPlatform::getDatabaseService();
        
        $address_ids = implode(',', $ids);
        
        // Addresses
        $sql = sprintf("DELETE QUICK FROM address WHERE id IN (%s)", $address_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
       
        // Custom fields
        DAO_CustomFieldValue::deleteBySourceIds(ChCustomFieldSource_Address::ID, $ids);
    }
		
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$addresses = array();
		
		$sql = sprintf("SELECT a.id, a.email, a.first_name, a.last_name, a.contact_org_id, a.num_spam, a.num_nonspam, a.is_banned, a.is_registered, a.pass, a.last_autoreply ".
			"FROM address a ".
			((!empty($where)) ? "WHERE %s " : " ").
			"ORDER BY a.email ",
			$where
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$address = new Model_Address();
			$address->id = intval($row['id']);
			$address->email = $row['email'];
			$address->first_name = $row['first_name'];
			$address->last_name = $row['last_name'];
			$address->contact_org_id = intval($row['contact_org_id']);
			$address->num_spam = intval($row['num_spam']);
			$address->num_nonspam = intval($row['num_nonspam']);
			$address->is_banned = intval($row['is_banned']);
			$address->is_registered = intval($row['is_registered']);
			$address->pass = $row['pass'];
			$address->last_autoreply = intval($row['last_autoreply']);
			$addresses[$address->id] = $address;
		}
		
		mysql_free_result($rs);
		
		return $addresses;
	}

	/**
	 * @return Model_Address|null
	 */
	static function getByEmail($email) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = self::getWhere(sprintf("%s = %s",
			self::EMAIL,
			$db->qstr(strtolower($email))
		));

		if(!empty($results))
			return array_shift($results);
			
		return NULL;
	}
	
	static function getCountByOrgId($org_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) FROM address WHERE contact_org_id = %d",
			$org_id
		);
		return intval($db->GetOne($sql));
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return Model_Address
	 */
	static function get($id) {
		if(empty($id)) return null;
		
		$addresses = DAO_Address::getWhere(
			sprintf("%s = %d",
				self::ID,
				$id
		));
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $email
	 * @param unknown_type $create_if_null
	 * @return Model_Address
	 */
	static function lookupAddress($email,$create_if_null=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$address = null;
		
		$email = trim(mb_convert_case($email, MB_CASE_LOWER));
		
		$addresses = self::getWhere(sprintf("email = %s",
			$db->qstr($email)
		));
		
		if(is_array($addresses) && !empty($addresses)) {
			$address = array_shift($addresses);
		} elseif($create_if_null) {
			$fields = array(
				self::EMAIL => $email
			);
			$id = DAO_Address::create($fields);
			$address = DAO_Address::get($id);
		}
		
		return $address;
	}
	
	static function addOneToSpamTotal($address_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE address SET num_spam = num_spam + 1 WHERE id = %d",$address_id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
	
	static function addOneToNonSpamTotal($address_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE address SET num_nonspam = num_nonspam + 1 WHERE id = %d",$address_id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
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
		$fields = SearchFields_Address::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.email as %s, ".
			"a.first_name as %s, ".
			"a.last_name as %s, ".
			"a.contact_org_id as %s, ".
			"o.name as %s, ".
			"a.num_spam as %s, ".
			"a.num_nonspam as %s, ".
			"a.is_banned as %s, ".
			"a.is_registered as %s, ".
			"a.pass as %s ",
			    SearchFields_Address::ID,
			    SearchFields_Address::EMAIL,
			    SearchFields_Address::FIRST_NAME,
			    SearchFields_Address::LAST_NAME,
			    SearchFields_Address::CONTACT_ORG_ID,
			    SearchFields_Address::ORG_NAME,
			    SearchFields_Address::NUM_SPAM,
			    SearchFields_Address::NUM_NONSPAM,
			    SearchFields_Address::IS_BANNED,
			    SearchFields_Address::IS_REGISTERED,
			    SearchFields_Address::PASS
			 );
		
		$join_sql = 
			"FROM address a ".
			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'a.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
		
		$sort_sql =	(!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY a.id ' : '').
			$sort_sql;
			
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Address::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT a.id) " : "SELECT COUNT(a.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_Address implements IDevblocksSearchFields {
	// Address
	const ID = 'a_id';
	const EMAIL = 'a_email';
	const FIRST_NAME = 'a_first_name';
	const LAST_NAME = 'a_last_name';
	const CONTACT_ORG_ID = 'a_contact_org_id';
	const NUM_SPAM = 'a_num_spam';
	const NUM_NONSPAM = 'a_num_nonspam';
	const IS_BANNED = 'a_is_banned';
	const IS_REGISTERED = 'a_is_registered';
	const PASS = 'a_pass';
	
	const ORG_NAME = 'o_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', $translate->_('address.id')),
			self::EMAIL => new DevblocksSearchField(self::EMAIL, 'a', 'email', $translate->_('address.email')),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'a', 'first_name', $translate->_('address.first_name')),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'a', 'last_name', $translate->_('address.last_name')),
			self::NUM_SPAM => new DevblocksSearchField(self::NUM_SPAM, 'a', 'num_spam', $translate->_('address.num_spam')),
			self::NUM_NONSPAM => new DevblocksSearchField(self::NUM_NONSPAM, 'a', 'num_nonspam', $translate->_('address.num_nonspam')),
			self::IS_BANNED => new DevblocksSearchField(self::IS_BANNED, 'a', 'is_banned', $translate->_('address.is_banned')),
			self::IS_REGISTERED => new DevblocksSearchField(self::IS_REGISTERED, 'a', 'is_registered', $translate->_('address.is_registered')),
			self::PASS => new DevblocksSearchField(self::PASS, 'a', 'pass', ucwords($translate->_('common.password'))),
			
			self::CONTACT_ORG_ID => new DevblocksSearchField(self::CONTACT_ORG_ID, 'a', 'contact_org_id', $translate->_('address.contact_org_id')),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', $translate->_('contact_org.name')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));
		
		return $columns;
	}
};

class Model_Address {
	public $id;
	public $email = '';
	public $first_name = '';
	public $last_name = '';
	public $contact_org_id = 0;
	public $num_spam = 0;
	public $num_nonspam = 0;
	public $is_banned = 0;
	public $is_registered = 0;
	public $pass = '';
	public $last_autoreply;

	function Model_Address() {}
	
	function getName() {
		return sprintf("%s%s%s",
			$this->first_name,
			(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
			$this->last_name
		);
	}
};

class View_Address extends C4_AbstractView {
	const DEFAULT_ID = 'addresses';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('addy_book.tab.addresses');
		$this->renderLimit = 10;
		$this->renderSortBy = 'a_email';
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Address::FIRST_NAME,
			SearchFields_Address::LAST_NAME,
			SearchFields_Address::ORG_NAME,
			SearchFields_Address::IS_REGISTERED,
			SearchFields_Address::NUM_NONSPAM,
			SearchFields_Address::NUM_SPAM,
		);
		
		$this->params = array(
			SearchFields_Address::IS_REGISTERED => new DevblocksSearchCriteria(SearchFields_Address::IS_REGISTERED,'=',1),
		);
	}

	function getData() {
		$objects = DAO_Address::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		
		$tpl->assign('view', $this);

		$address_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		$tpl->assign('custom_fields', $address_fields);
		
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/contacts/addresses/address_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		$tpl_path = APP_PATH . '/features/cerberusweb.core/templates/';
		
		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::FIRST_NAME:
			case SearchFields_Address::LAST_NAME:
			case SearchFields_Address::ORG_NAME:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Address::IS_BANNED:
			case SearchFields_Address::IS_REGISTERED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
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
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Address::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Address::ID]);
		unset($fields[SearchFields_Address::CONTACT_ORG_ID]);
		unset($fields[SearchFields_Address::PASS]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Address::PASS]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Address::IS_REGISTERED => new DevblocksSearchCriteria(SearchFields_Address::IS_REGISTERED,'=',1),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::FIRST_NAME:
			case SearchFields_Address::LAST_NAME:
			case SearchFields_Address::ORG_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Address::IS_BANNED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			case SearchFields_Address::IS_REGISTERED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'org_id':
					$change_fields[DAO_Address::CONTACT_ORG_ID] = intval($v);
					break;
				case 'banned':
					$change_fields[DAO_Address::IS_BANNED] = intval($v);
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Address::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_Address::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		// Broadcast?
		if(isset($do['broadcast'])) {
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			
			$params = $do['broadcast'];
			if(
				!isset($params['worker_id']) 
				|| empty($params['worker_id'])
				|| !isset($params['subject']) 
				|| empty($params['subject'])
				|| !isset($params['message']) 
				|| empty($params['message'])
				)
				break;

			$is_queued = (isset($params['is_queued']) && $params['is_queued']) ? true : false; 
			$next_is_closed = (isset($params['next_is_closed'])) ? intval($params['next_is_closed']) : 0; 
						
			if(is_array($ids))
			foreach($ids as $addy_id) {
				try {
					CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $addy_id, $tpl_labels, $tpl_tokens);
					$subject = $tpl_builder->build($params['subject'], $tpl_tokens);
					$body = $tpl_builder->build($params['message'], $tpl_tokens);
					
					$json_params = array(
						'to' => $tpl_tokens['address'],
						'group_id' => $params['group_id'],
						'next_is_closed' => $next_is_closed,
					);
					
					$fields = array(
						DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
						DAO_MailQueue::TICKET_ID => 0,
						DAO_MailQueue::WORKER_ID => $params['worker_id'],
						DAO_MailQueue::UPDATED => time(),
						DAO_MailQueue::HINT_TO => $tpl_tokens['address'],
						DAO_MailQueue::SUBJECT => $subject,
						DAO_MailQueue::BODY => $body,
						DAO_MailQueue::PARAMS_JSON => json_encode($json_params),
					);
					
					if($is_queued) {
						$fields[DAO_MailQueue::IS_QUEUED] = 1;
					}
					
					$draft_id = DAO_MailQueue::create($fields);
					
				} catch (Exception $e) {
					// [TODO] ...
				}
			}
		}		
		
		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Address::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_Address::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};