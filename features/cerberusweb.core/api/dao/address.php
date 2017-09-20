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

class DAO_Address extends Cerb_ORMHelper {
	const CONTACT_ID = 'contact_id';
	const CONTACT_ORG_ID = 'contact_org_id';
	const EMAIL = 'email';
	const HOST = 'host';
	const ID = 'id';
	const IS_BANNED = 'is_banned';
	const IS_DEFUNCT = 'is_defunct';
	const NUM_NONSPAM = 'num_nonspam';
	const NUM_SPAM = 'num_spam';
	const UPDATED = 'updated';
	
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
			
			$id = $db->GetOne(sprintf("SELECT id FROM address WHERE email = %s", $db->qstr($full_address)));
			
			// If an email address is a duplicate, we can only set it on the same record
			if($id && !in_array($id, $ids))
				return NULL;
			
			$fields[self::EMAIL] = $full_address;
			$fields[self::HOST] = substr($full_address, strpos($full_address, '@')+1);
		}
		
		if(!isset($fields[DAO_Address::UPDATED]))
			$fields[DAO_Address::UPDATED] = time();
		
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
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('address', $fields, $where);
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = array();
		$custom_fields = array();

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
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
	
		DAO_Address::update($ids, $change_fields);
		
		// Custom Fields
		C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_ADDRESS, $custom_fields, $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_ADDRESS, $do['behavior'], $ids);
		
		// Broadcast
		if(isset($do['broadcast']))
			C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_ADDRESS, $do['broadcast'], $ids, 'address');
		
		$update->markCompleted();
		return true;
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		$sql = "DELETE FROM address_to_worker WHERE worker_id NOT IN (SELECT id FROM worker)";
		$db->ExecuteMaster($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' address_to_worker records.');

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
	}
	
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, email, host, contact_id, contact_org_id, num_spam, num_nonspam, is_banned, is_defunct, updated ".
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
			$object->num_spam = intval($row['num_spam']);
			$object->num_nonspam = intval($row['num_nonspam']);
			$object->is_banned = intval($row['is_banned']);
			$object->is_defunct = intval($row['is_defunct']);
			$object->updated = intval($row['updated']);
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
	
	static function countByTicketId($ticket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(address_id) FROM requester WHERE ticket_id = %d",
			$ticket_id
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
	
	/**
	 *
	 * @param string $email
	 * @param boolean $create_if_null
	 * @return Model_Address
	 */
	static function lookupAddress($email, $create_if_null=false) {
		$db = DevblocksPlatform::services()->database();
		
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
		
		list($tables, $wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Address', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.email as %s, ".
			"a.host as %s, ".
			"a.contact_id as %s, ".
			"a.contact_org_id as %s, ".
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
		
		// Translate virtual fields
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
		
		array_walk_recursive(
			$params,
			array('DAO_Address', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 'a',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;

		$from_context = CerberusContexts::CONTEXT_ADDRESS;
		$from_index = 'a.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Address::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}
	
	static function autocomplete($term, $as='models') {
		// If we have a special email character then switch to literal email matching
		if(preg_match('/[\.\@\_]/', $term)) {
			// If a leading '@', then prefix/trailing wildcard
			if(substr($term,0,1) == '@') {
				$query = '*' . $term . '*';
			// Otherwise, only suffix wildcard
			} else {
				$query = $term . '*';
			}
			
			$params = array(
				SearchFields_Address::EMAIL => new DevblocksSearchCriteria(SearchFields_Address::EMAIL, DevblocksSearchCriteria::OPER_LIKE, $query),
				SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED, DevblocksSearchCriteria::OPER_EQ, 0),
				SearchFields_Address::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_Address::IS_DEFUNCT, DevblocksSearchCriteria::OPER_EQ, 0),
			);
			
		// Otherwise, use fulltext
		} else {
			$params = array(
				SearchFields_Address::FULLTEXT_ADDRESS => new DevblocksSearchCriteria(SearchFields_Address::FULLTEXT_ADDRESS, DevblocksSearchCriteria::OPER_FULLTEXT, $term.'*'),
				SearchFields_Address::IS_BANNED => new DevblocksSearchCriteria(SearchFields_Address::IS_BANNED, DevblocksSearchCriteria::OPER_EQ, 0),
				SearchFields_Address::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_Address::IS_DEFUNCT, DevblocksSearchCriteria::OPER_EQ, 0),
			);
		}
		
		list($results, $null) = DAO_Address::search(
			array(),
			$params,
			25,
			0,
			SearchFields_Address::NUM_NONSPAM,
			false,
			false
		);

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
	const NUM_SPAM = 'a_num_spam';
	const NUM_NONSPAM = 'a_num_nonspam';
	const IS_BANNED = 'a_is_banned';
	const IS_DEFUNCT = 'a_is_defunct';
	const UPDATED = 'a_updated';
	
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
			self::NUM_SPAM => new DevblocksSearchField(self::NUM_SPAM, 'a', 'num_spam', $translate->_('address.num_spam'), Model_CustomField::TYPE_NUMBER, true),
			self::NUM_NONSPAM => new DevblocksSearchField(self::NUM_NONSPAM, 'a', 'num_nonspam', $translate->_('address.num_nonspam'), Model_CustomField::TYPE_NUMBER, true),
			self::IS_BANNED => new DevblocksSearchField(self::IS_BANNED, 'a', 'is_banned', $translate->_('address.is_banned'), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_DEFUNCT => new DevblocksSearchField(self::IS_DEFUNCT, 'a', 'is_defunct', $translate->_('address.is_defunct'), Model_CustomField::TYPE_CHECKBOX, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'a', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
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
		
		$ns = self::getNamespace();
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
	public $email = '';
	public $host = '';
	public $is_banned = 0;
	public $is_defunct = 0;
	public $num_nonspam = 0;
	public $num_spam = 0;
	public $updated = 0;
	
	private $_contact_model = null;
	private $_org_model = null;

	function __get($name) {
		switch($name) {
			// [DEPRECATED] Added in 7.1
			case 'first_name':
				if(false == ($contact = $this->getContact()))
					return '';
					
				error_log("The 'first_name' field on address records is deprecated. Use contacts instead.", E_USER_DEPRECATED);
					
				return $contact->first_name;
				break;
				
			// [DEPRECATED] Added in 7.1
			case 'last_name':
				if(false == ($contact = $this->getContact()))
					return '';
					
				error_log("The 'last_name' field on address records is deprecated. Use contacts instead.", E_USER_DEPRECATED);
					
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
		
		$this->addParamsHidden(array(
			SearchFields_Address::ID,
			SearchFields_Address::ORG_NAME,
			SearchFields_Address::VIRTUAL_CONTACT_SEARCH,
			SearchFields_Address::VIRTUAL_ORG_SEARCH,
			SearchFields_Address::VIRTUAL_TICKET_ID,
			SearchFields_Address::VIRTUAL_TICKET_SEARCH,
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
				case SearchFields_Address::CONTACT_ORG_ID:
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
				
			case SearchFields_Address::CONTACT_ORG_ID:
				$label_map = function($ids) {
					$rows = DAO_ContactOrg::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'name', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, SearchFields_Address::CONTACT_ORG_ID, $label_map, '=', 'value[]');
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
				if('cf_' == substr($column,0,3)) {
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
			'email' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Address::EMAIL),
				),
			'host' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Address::HOST),
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
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Address::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
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

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::HOST:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
			case SearchFields_Address::CONTACT_ORG_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_Address::IS_BANNED:
			case SearchFields_Address::IS_DEFUNCT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Address::UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Address::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Address::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_ADDRESS);
				break;
				
			case SearchFields_Address::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			case SearchFields_Address::FULLTEXT_ADDRESS:
			case SearchFields_Address::FULLTEXT_COMMENT_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
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
				if(empty($param->value)) {
					$contact_names[] = '(empty)';
					
				} else {
					$contact_ids = !is_array($param->value) ? array($param->value) : $param->value;
					$contacts = DAO_Contact::getIds($contact_ids);
					$contact_names = array();
					
					foreach($contacts as $contact)
						$contact_names[] = sprintf("<b>%s</b>", DevblocksPlatform::strEscapeHtml($contact->getName()));
				}
				
				echo implode(' or ', $contact_names);
				break;
				
			case SearchFields_Address::CONTACT_ORG_ID:
				if(empty($param->value)) {
					$org_names[] = '(empty)';
					
				} else {
					$org_ids = !is_array($param->value) ? array($param->value) : $param->value;
					$orgs = DAO_ContactOrg::getIds($org_ids);
					$org_names = array();
					
					foreach($orgs as $org)
						$org_names[] = sprintf('<b>%s</b>', DevblocksPlatform::strEscapeHtml($org->name));
				}
				
				echo implode(' or ', $org_names);
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
				
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Address::CONTACT_ORG_ID:
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$value);
				break;
				
			case SearchFields_Address::IS_BANNED:
			case SearchFields_Address::IS_DEFUNCT:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Address::UPDATED:
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Address::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Address::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Address::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Address::FULLTEXT_ADDRESS:
			case SearchFields_Address::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
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

class Context_Address extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport, IDevblocksContextAutocomplete {
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function searchInboundLinks($from_context, $from_context_id) {
		list($results, $null) = DAO_Address::search(
			array(
				SearchFields_Address::ID,
			),
			array(
				new DevblocksSearchCriteria(SearchFields_Address::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			),
			-1,
			0,
			SearchFields_Address::EMAIL,
			true,
			false
		);
		
		return $results;
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
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();

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
		return array(
			'org__label',
			'contact__label',
			'is_banned',
			'is_defunct',
			'num_nonspam',
			'num_spam',
			'updated',
		);
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		
		$models = DAO_Address::autocomplete($term);
		$list = array();
		
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
			
			$meta = array();
			
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
			'full_name' => $prefix.$translate->_('common.name.full'),
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
			'full_name' => Model_CustomField::TYPE_SINGLE_LINE,
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
			$merge_token_labels = array();
			$merge_token_values = array();
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
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'contact_id' => DAO_Address::CONTACT_ID,
			'email' => DAO_Address::EMAIL,
			'host' => DAO_Address::HOST,
			'id' => DAO_Address::ID,
			'is_banned' => DAO_Address::IS_BANNED,
			'is_defunct' => DAO_Address::IS_DEFUNCT,
			'num_nonspam' => DAO_Address::NUM_NONSPAM,
			'num_spam' => DAO_Address::NUM_SPAM,
			'org_id' => DAO_Address::CONTACT_ORG_ID,
			'updated' => DAO_Address::UPDATED,
		];
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
			
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
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
		$view->renderFilters = false;
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
		
		$context = CerberusContexts::CONTEXT_ADDRESS;
		$active_worker = CerberusApplication::getActiveWorker();
		$address = null;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id)) {
			$email = '';
			if(null != ($addy = DAO_Address::get($context_id))) {
				@$email = $addy->email;
			}
		}
		$tpl->assign('email', $email);
		
		if(!empty($email)) {
			$address = DAO_Address::getByEmail($email);
			$tpl->assign('address', $address);
			
			if(empty($context_id) && $address instanceof Model_Address) {
				$context_id = $address->id;
			}
		}
		
		// Display
		$tpl->assign('id', $context_id);
		
		if(empty($context_id) || $edit) {
			if (!empty($org_id)) {
				$org = DAO_ContactOrg::get($org_id);
				$tpl->assign('org_name',$org->name);
				$tpl->assign('org_id',$org->id);
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
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context, $address, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			// Counts
			$activity_counts = array(
				'comments' => DAO_Comment::count($context, $context_id),
				'tickets' => DAO_Ticket::countsByAddressId($context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($context, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}
			
			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			// Interactions
			$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
			$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
			$tpl->assign('interactions_menu', $interactions_menu);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$tpl->display('devblocks:cerberusweb.core::contacts/addresses/peek.tpl');
		}
	}
	
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'contact_org_id' => array(
				'label' => 'Org',
				'type' => 'ctx_' . CerberusContexts::CONTEXT_ORG,
				'param' => SearchFields_Address::CONTACT_ORG_ID,
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
				'label' => 'Updated',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Address::UPDATED,
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