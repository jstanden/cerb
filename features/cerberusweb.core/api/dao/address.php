<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_Address extends C4_ORMHelper {
	const ID = 'id';
	const EMAIL = 'email';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const CONTACT_PERSON_ID = 'contact_person_id';
	const CONTACT_ORG_ID = 'contact_org_id';
	const NUM_SPAM = 'num_spam';
	const NUM_NONSPAM = 'num_nonspam';
	const IS_BANNED = 'is_banned';
	const LAST_AUTOREPLY = 'last_autoreply';
	
	private function __construct() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			'id' => $translate->_('address.id'),
			'email' => $translate->_('address.email'),
			'first_name' => $translate->_('address.first_name'),
			'last_name' => $translate->_('address.last_name'),
			'contact_person_id' => $translate->_('address.contact_person_id'),
			'contact_org_id' => $translate->_('address.contact_org_id'),
			'num_spam' => $translate->_('address.num_spam'),
			'num_nonspam' => $translate->_('address.num_nonspam'),
			'is_banned' => $translate->_('address.is_banned'),
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
			$sql = sprintf("INSERT INTO address (email,first_name,last_name,contact_person_id,contact_org_id,num_spam,num_nonspam,is_banned,last_autoreply) ".
				"VALUES (%s,'','',0,0,0,0,0,0)",
				$db->qstr($full_address)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			$id = $db->LastInsertId(); 

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
		
		// Context Links
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN address ON context_link.from_context_id=address.id WHERE context_link.from_context = %s AND address.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_ADDRESS)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' address context link sources.');
		
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN address ON context_link.to_context_id=address.id WHERE context_link.to_context = %s AND address.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_ADDRESS)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' address context link targets.');
		
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
       
		// Context links
		DAO_ContextLink::delete(CerberusContexts::CONTEXT_ADDRESS, $ids);
        
        // Custom fields
        DAO_CustomFieldValue::deleteByContextIds(CerberusContexts::CONTEXT_ADDRESS, $ids);
    }
		
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$addresses = array();
		
		$sql = sprintf("SELECT a.id, a.email, a.first_name, a.last_name, a.contact_person_id, a.contact_org_id, a.num_spam, a.num_nonspam, a.is_banned, a.last_autoreply ".
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
			$address->contact_person_id = intval($row['contact_person_id']);
			$address->contact_org_id = intval($row['contact_org_id']);
			$address->num_spam = intval($row['num_spam']);
			$address->num_nonspam = intval($row['num_nonspam']);
			$address->is_banned = intval($row['is_banned']);
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
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Address::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.email as %s, ".
			"a.first_name as %s, ".
			"a.last_name as %s, ".
			"a.contact_person_id as %s, ".
			"a.contact_org_id as %s, ".
			"o.name as %s, ".
			"a.num_spam as %s, ".
			"a.num_nonspam as %s, ".
			"a.is_banned as %s ",
			    SearchFields_Address::ID,
			    SearchFields_Address::EMAIL,
			    SearchFields_Address::FIRST_NAME,
			    SearchFields_Address::LAST_NAME,
			    SearchFields_Address::CONTACT_PERSON_ID,
			    SearchFields_Address::CONTACT_ORG_ID,
			    SearchFields_Address::ORG_NAME,
			    SearchFields_Address::NUM_SPAM,
			    SearchFields_Address::NUM_NONSPAM,
			    SearchFields_Address::IS_BANNED
			 );
		
		$join_sql = 
			"FROM address a ".
			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) ".
		
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.address' AND context_link.to_context_id = a.id) " : " ")
			;

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'a.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql =	(!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$result = array(
			'primary_table' => 'a',
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
			($has_multiple_values ? 'GROUP BY a.id ' : '').
			$sort_sql;
			
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
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
	const CONTACT_PERSON_ID = 'a_contact_person_id';
	const CONTACT_ORG_ID = 'a_contact_org_id';
	const NUM_SPAM = 'a_num_spam';
	const NUM_NONSPAM = 'a_num_nonspam';
	const IS_BANNED = 'a_is_banned';
	
	const ORG_NAME = 'o_name';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
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
			self::CONTACT_PERSON_ID => new DevblocksSearchField(self::NUM_SPAM, 'a', 'contact_person_id', $translate->_('address.contact_person_id')),
			self::NUM_SPAM => new DevblocksSearchField(self::NUM_SPAM, 'a', 'num_spam', $translate->_('address.num_spam')),
			self::NUM_NONSPAM => new DevblocksSearchField(self::NUM_NONSPAM, 'a', 'num_nonspam', $translate->_('address.num_nonspam')),
			self::IS_BANNED => new DevblocksSearchField(self::IS_BANNED, 'a', 'is_banned', $translate->_('address.is_banned')),
			
			self::CONTACT_ORG_ID => new DevblocksSearchField(self::CONTACT_ORG_ID, 'a', 'contact_org_id', $translate->_('address.contact_org_id')),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', $translate->_('contact_org.name')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
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
	public $contact_person_id = 0;
	public $contact_org_id = 0;
	public $num_spam = 0;
	public $num_nonspam = 0;
	public $is_banned = 0;
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
			SearchFields_Address::NUM_NONSPAM,
			SearchFields_Address::NUM_SPAM,
		);
		$this->addColumnsHidden(array(
			SearchFields_Address::CONTACT_PERSON_ID,
			SearchFields_Address::CONTACT_ORG_ID,
			SearchFields_Address::CONTEXT_LINK,
			SearchFields_Address::CONTEXT_LINK_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Address::CONTACT_PERSON_ID,
			SearchFields_Address::CONTACT_ORG_ID,
			SearchFields_Address::ID,
			SearchFields_Address::CONTEXT_LINK,
			SearchFields_Address::CONTEXT_LINK_ID,
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
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Address', $size);
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		
		$tpl->assign('view', $this);

		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('custom_fields', $address_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.core::contacts/addresses/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.core::contacts/addresses/address_view.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::FIRST_NAME:
			case SearchFields_Address::LAST_NAME:
			case SearchFields_Address::ORG_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Address::IS_BANNED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
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

	function getFields() {
		return SearchFields_Address::getFields();
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
					$value = $value.'*';
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
				$this->getParams(),
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
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_ADDRESS, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Address extends Extension_DevblocksContext {
    static function searchInboundLinks($from_context, $from_context_id) {
    	list($results, $null) = DAO_Address::search(
    		array(
    			SearchFields_Address::ID,
    		),
    		array(
				new DevblocksSearchCriteria(SearchFields_Address::CONTEXT_LINK,'=',$from_context),
				new DevblocksSearchCriteria(SearchFields_Address::CONTEXT_LINK_ID,'=',$from_context_id),
    		),
    		-1,
    		0,
    		SearchFields_Address::EMAIL,
    		true,
    		false
    	);
    	
    	return $results;
    }
    
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=contacts&tab=addresses', true);
    }
    
	function getContext($address, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Email:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		
		// Polymorph
		if(is_numeric($address)) {
			$address = DAO_Address::get($address);
		} elseif($address instanceof Model_Address) {
			// It's what we want already.
		} elseif(is_string($address)) {
			$address = DAO_Address::getByEmail($address);
		} else {
			$address = null;
		}
			
		// Token labels
		$token_labels = array(
			'address' => $prefix.$translate->_('common.email'),
			'first_name' => $prefix.$translate->_('address.first_name'),
			'last_name' => $prefix.$translate->_('address.last_name'),
			'num_spam' => $prefix.$translate->_('address.num_spam'),
			'num_nonspam' => $prefix.$translate->_('address.num_nonspam'),
			'is_banned' => $prefix.$translate->_('address.is_banned'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Address token values
		if(null != $address) {
			$token_values['id'] = $address->id;
			if(!empty($address->email))
				$token_values['address'] = $address->email;
			if(!empty($address->first_name))
				$token_values['first_name'] = $address->first_name;
			if(!empty($address->last_name))
				$token_values['last_name'] = $address->last_name;
			$token_values['num_spam'] = $address->num_spam;
			$token_values['num_nonspam'] = $address->num_nonspam;
			$token_values['is_banned'] = $address->is_banned;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $address->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $address)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $address)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Email Org
		$org_id = (null != $address && !empty($address->contact_org_id)) ? $address->contact_org_id : null;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, $org_id, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'org_',
			'',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);		
		
		// [TODO] Link contact
		
		return true;		
	}

	function getChooserView() {
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Organizations';
		
		$view->view_columns = array(
			SearchFields_Address::FIRST_NAME,
			SearchFields_Address::LAST_NAME,
			SearchFields_Address::ORG_NAME,
		);
		
		$view->addParamsDefault(array(
			SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED,'=',0),
		), true);
		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_Address::EMAIL;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'E-mail Addresses';
		
		$params = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params = array(
				new DevblocksSearchCriteria(SearchFields_Address::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Address::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		if(isset($options['filter_open']))
			true; // Do nothing
		
		$view->addParams($params, true);
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};