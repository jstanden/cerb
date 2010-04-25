<?php
class DAO_GroupInboxFilter extends DevblocksORMHelper {
    const ID = 'id';
    const NAME = 'name';
    const GROUP_ID = 'group_id';
	const CRITERIA_SER = 'criteria_ser';
	const ACTIONS_SER = 'actions_ser';
    const POS = 'pos';
    const IS_STICKY = 'is_sticky';
    const STICKY_ORDER = 'sticky_order';
    const IS_STACKABLE = 'is_stackable';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO group_inbox_filter (id,name,created,group_id,criteria_ser,actions_ser,pos,is_sticky,sticky_order,is_stackable) ".
		    "VALUES (%d,'',%d,0,'','',0,0,0,0)",
		    $id,
		    time()
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function increment($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE group_inbox_filter SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'group_inbox_filter', $fields);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_GroupInboxFilter
	 */
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $group_id
	 * @return Model_GroupInboxFilter
	 */
	public static function getByGroupId($group_id) {
	    if(empty($group_id)) return array();
	    
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id, name, group_id, criteria_ser, actions_ser, pos, is_sticky, sticky_order, is_stackable ".
		    "FROM group_inbox_filter ".
		    "WHERE group_id = %d ".
		    "ORDER BY is_sticky DESC, sticky_order ASC, pos DESC",
		    $group_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		return self::_getResultsAsModel($rs);
	}
	
    /**
     * @return Model_GroupInboxFilter[]
     */
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, group_id, criteria_ser, actions_ser, pos, is_sticky, sticky_order, is_stackable ".
		    "FROM group_inbox_filter ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    "ORDER BY is_sticky DESC, sticky_order ASC, pos DESC"
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		return self::_getResultsAsModel($rs);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_GroupInboxFilter[]
	 */
	private static function _getResultsAsModel($rs) {
		$objects = array();
		
		
		while($row = mysql_fetch_assoc($rs)) {
		    $object = new Model_GroupInboxFilter();
		    $object->id = intval($row['id']);
		    $object->name = $row['name'];
		    $object->group_id = intval($row['group_id']);
		    $object->pos = intval($row['pos']);
		    $object->is_sticky = intval($row['is_sticky']);
		    $object->sticky_order = intval($row['sticky_order']);
		    $object->is_stackable = intval($row['is_stackable']);

            // Criteria
		    $criteria_ser = $row['criteria_ser'];
		    if(!empty($criteria_ser))
		    	@$criteria = unserialize($criteria_ser);
		    if(is_array($criteria))
		    	$object->criteria = $criteria;
            
            // Actions
		    $actions_ser = $row['actions_ser'];
		    if(!empty($actions_ser))
		    	@$actions = unserialize($actions_ser);
		    if(is_array($actions))
		    	$object->actions = $actions;
            
		    $objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    $db = DevblocksPlatform::getDatabaseService();
	    
		if(empty($ids))
			return;
		
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE QUICK FROM group_inbox_filter WHERE id IN (%s)", $id_list);
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
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		$fields = SearchFields_GroupInboxFilter::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, array(), $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"trr.id as %s, ".
			"trr.group_id as %s, ".
			"trr.pos as %s, ".
			"trr.is_sticky as %s, ".
			"trr.sticky_order as %s, ".
			"trr.is_stackable as %s ".
			"FROM group_inbox_filter trr ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_GroupInboxFilter::ID,
			    SearchFields_GroupInboxFilter::GROUP_ID,
			    SearchFields_GroupInboxFilter::POS,
			    SearchFields_GroupInboxFilter::IS_STICKY,
			    SearchFields_GroupInboxFilter::STICKY_ORDER,
			    SearchFields_GroupInboxFilter::IS_STACKABLE
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$row_id = intval($row[SearchFields_GroupInboxFilter::ID]);
			$results[$row_id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = mysql_num_rows($rs);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_GroupInboxFilter implements IDevblocksSearchFields {
	// Table
	const ID = 'trr_id';
	const GROUP_ID = 'trr_group_id';
	const POS = 'trr_pos';
	const IS_STICKY = 'trr_is_sticky';
	const STICKY_ORDER = 'trr_sticky_order';
	const IS_STACKABLE = 'trr_is_stackable';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'trr', 'id'),
			self::GROUP_ID => new DevblocksSearchField(self::GROUP_ID, 'trr', 'group_id'),
			self::POS => new DevblocksSearchField(self::POS, 'trr', 'pos'),
			self::IS_STICKY => new DevblocksSearchField(self::IS_STICKY, 'trr', 'is_sticky'),
			self::STICKY_ORDER => new DevblocksSearchField(self::STICKY_ORDER, 'trr', 'sticky_order'),
			self::IS_STACKABLE => new DevblocksSearchField(self::IS_STACKABLE, 'trr', 'is_stackable'),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_GroupInboxFilter {
	public $id = 0;
	public $name = '';
	public $group_id = 0;
	public $criteria = array();
	public $actions = array();
	public $pos = 0;
	public $is_sticky = 0;
	public $sticky_order = 0;
	public $is_stackable = 0;
	
	/**
	 * @return Model_GroupInboxFilter|false
	 */
	static function getMatches($group_id, $ticket_id, $only_rule_id=0) {
		$matches = array();
		
		if(empty($group_id))
			return false;

		if(!empty($only_rule_id)) {
			$filters = array(
				DAO_GroupInboxFilter::get($only_rule_id)
			);
		} else {
			$filters = DAO_GroupInboxFilter::getByGroupId($group_id);
		}

		// Check the ticket
		if(null === ($ticket = DAO_Ticket::getTicket($ticket_id)))
			return false;
			
		// Build our objects
		$ticket_from = DAO_Address::get($ticket->last_wrote_address_id);
		$ticket_group_id = $ticket->team_id;
		
		// [TODO] These expensive checks should only populate when needed
		$messages = DAO_Message::getMessagesByTicket($ticket_id);
		$message_headers = array();

		if(empty($messages))
			return false;
		
		if(null != (@$message_last = array_pop($messages))) { /* @var $message_last Model_Message */
			$message_headers = $message_last->getHeaders();
		}

		// Clear the rest of the message manifests
		unset($messages);
		
		$custom_fields = DAO_CustomField::getAll();
		
		// Lazy load when needed on criteria basis
		$ticket_field_values = null;
		$address_field_values = null;
		$org_field_values = null;
		
		// Check filters
		if(is_array($filters))
		foreach($filters as $filter) { /* @var $filter Model_GroupInboxFilter */
			$passed = 0;

			// Skip filters with no criteria
			if(!is_array($filter->criteria) || empty($filter->criteria))
				continue; 

			// check criteria
			foreach($filter->criteria as $rule_key => $rule) {
				@$value = $rule['value'];
							
				switch($rule_key) {
					case 'dayofweek':
						$current_day = strftime('%w');
//						$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($rule[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = strftime('%H');
						$current_min = strftime('%M');
//						$current_hour = 17;
//						$current_min = 5;

						if(null != ($from_time = @$rule['from']))
							list($from_hour, $from_min) = explode(':', $from_time);
						
						if(null != ($to_time = @$rule['to']))
							if(list($to_hour, $to_min) = explode(':', $to_time));

						// Do we need to wrap around to the next day's hours?
						if($from_hour > $to_hour) { // yes
							$to_hour += 24; // add 24 hrs to the destination (1am = 25th hour)
						}
							
						// Are we in the right 24 hourly range?
						if((integer)$current_hour >= $from_hour && (integer)$current_hour <= $to_hour) {
							// If we're in the first hour, are we minutes early?
							if($current_hour==$from_hour && (integer)$current_min < $from_min)
								break;
							// If we're in the last hour, are we minutes late?
							if($current_hour==$to_hour && (integer)$current_min > $to_min)
								break;
								
							$passed++;
						}
						break;						
						
					case 'tocc':
						$tocc = array();
						$destinations = DevblocksPlatform::parseCsvString($value);

						// Build a list of To/Cc addresses on this message
						@$to_list = imap_rfc822_parse_adrlist($message_headers['to'],'localhost');
						@$cc_list = imap_rfc822_parse_adrlist($message_headers['cc'],'localhost');
						
						if(is_array($to_list))
						foreach($to_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						if(is_array($cc_list))
						foreach($cc_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						
						$dest_flag = false; // bail out when true
						if(is_array($destinations) && is_array($tocc))
						foreach($destinations as $dest) {
							if($dest_flag) break;
							$regexp_dest = DevblocksPlatform::strToRegExp($dest);
							
							foreach($tocc as $addy) {
								if(@preg_match($regexp_dest, $addy)) {
									$passed++;
									$dest_flag = false;
									break;
								}
							}
						}
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_from, $ticket_from->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $ticket->subject)) {
							$passed++;
						}
						break;
						
					case 'body':
						if(null == ($message_body = $message_last->getContent()))
							break;
							
						// Line-by-line body scanning (sed-like)
						$lines = preg_split("/[\r\n]/", $message_body);
						if(is_array($lines))
						foreach($lines as $line) {
							if(@preg_match($value, $line)) {
								$passed++;
								break;
							}
						}
						break;
						
					case 'header1':
					case 'header2':
					case 'header3':
					case 'header4':
					case 'header5':
						@$header = strtolower($rule['header']);

						if(empty($header)) {
							$passed++;
							break;
						}
						
						if(empty($value)) { // we're checking for null/blanks
							if(!isset($message_headers[$header]) || empty($message_headers[$header])) {
								$passed++;
							}
							
						} elseif(isset($message_headers[$header]) && !empty($message_headers[$header])) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// Flatten CRLF
							if(@preg_match($regexp_header, str_replace(array("\r","\n"),' ',$message_headers[$header]))) {
								$passed++;
							}
						}
						
						break;
						
					default: // ignore invalids
						// Custom Fields
						if(0==strcasecmp('cf_',substr($rule_key,0,3))) {
							$field_id = substr($rule_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue;

							// Lazy values loader
							$field_values = array();
							switch($field->source_extension) {
								case ChCustomFieldSource_Address::ID:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $ticket_from->id));
									$field_values =& $address_field_values;
									break;
								case ChCustomFieldSource_Org::ID:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $ticket_from->contact_org_id));
									$field_values =& $org_field_values;
									break;
								case ChCustomFieldSource_Ticket::ID:
									if(null == $ticket_field_values)
										$ticket_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket->id));
									$field_values =& $ticket_field_values;
									break;
							}
							
							// No values, default.
//							if(!isset($field_values[$field_id]))
//								continue;
							
							// Type sensitive value comparisons
							// [TODO] Operators
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									break;
								case 'N': // number
									if(!isset($field_values[$field_id]))
										break;
								
									$field_val = intval($field_values[$field_id]);
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper=="=" && $field_val == intval($value))
										$passed++;
									elseif($oper=="!=" && $field_val != intval($value))
										$passed++;
									elseif($oper==">" && $field_val > intval($value))
										$passed++;
									elseif($oper=="<" && $field_val < intval($value))
										$passed++;
									break;
								case 'E': // date
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($rule['from']) ? $rule['from'] : "0";
									$to = isset($rule['to']) ? $rule['to'] : "now";
									
									if(intval(@strtotime($from)) <= $field_val && intval(@strtotime($to)) >= $field_val) {
										$passed++;
									}
									break;
								case 'C': // checkbox
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									if(intval($value)==intval($field_val))
										$passed++;
									break;
								case 'D': // dropdown
								case 'X': // multi-checkbox
								case 'M': // multi-picklist
								case 'W': // worker
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
										
									if(is_array($field_val)) { // if multiple things set
										foreach($field_val as $v) { // loop through possible
											if(isset($value[$v])) { // is any possible set?
												$passed++;
												break;
											}
										}
										
									} else { // single
										if(isset($value[$field_val])) { // is our set field in possibles?
											$passed++;
											break;
										}
										
									}
									break;
							}
						}
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the filter
			if($passed == count($filter->criteria)) {
				DAO_GroupInboxFilter::increment($filter->id); // ++ the times we've matched
				$matches[$filter->id] = $filter;
				
				// If we're not stackable anymore, bail out.
				if(!$filter->is_stackable)
					return $matches;
			}
		}
		
		// If last rule was still stackable...
		if(!empty($matches))
			return $matches;
		
		// No matches
		return false;
	}
	
	/**
	 * @param integer[] $ticket_ids
	 */
	function run($ticket_ids) {
		$change_fields = array();
		$field_values = array();

		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		$workers = DAO_Worker::getAll();
		$custom_fields = DAO_CustomField::getAll();
		
		// actions
		if(is_array($this->actions))
		foreach($this->actions as $action => $params) {
			switch($action) {
				case 'status':
					if(isset($params['is_waiting']))
						$change_fields[DAO_Ticket::IS_WAITING] = intval($params['is_waiting']);
					if(isset($params['is_closed']))
						$change_fields[DAO_Ticket::IS_CLOSED] = intval($params['is_closed']);
					if(isset($params['is_deleted']))
						$change_fields[DAO_Ticket::IS_DELETED] = intval($params['is_deleted']);
					break;

				case 'assign':
					if(isset($params['worker_id'])) {
						$w_id = intval($params['worker_id']);
						if(0 == $w_id || isset($workers[$w_id]))
							$change_fields[DAO_Ticket::NEXT_WORKER_ID] = $w_id;
					}
					break;

				case 'move':
					if(isset($params['group_id']) && isset($params['bucket_id'])) {
						$g_id = intval($params['group_id']);
						$b_id = intval($params['bucket_id']);
						if(isset($groups[$g_id]) && (0==$b_id || isset($buckets[$b_id]))) {
							$change_fields[DAO_Ticket::TEAM_ID] = $g_id;
							$change_fields[DAO_Ticket::CATEGORY_ID] = $b_id;
						}
					}
					break;
					
				case 'spam':
					if(isset($params['is_spam'])) {
						if(intval($params['is_spam'])) {
							foreach($ticket_ids as $ticket_id)
								CerberusBayes::markTicketAsSpam($ticket_id);
						} else {
							foreach($ticket_ids as $ticket_id)
								CerberusBayes::markTicketAsNotSpam($ticket_id);
						}
					}
					break;
					
				case 'broadcast':
					if(
						!isset($params['worker_id']) || empty($params['worker_id'])
						|| !isset($params['message']) || empty($params['message'])
						)
						break;
						
					list($tickets, $null) = DAO_Ticket::search(
						array(),
						array(
							SearchFields_Ticket::TICKET_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID,DevblocksSearchCriteria::OPER_IN,$ticket_ids),
						),
						-1,
						0,
						null,
						true,
						false
					);
					$is_queued = (isset($params['is_queued']) && $params['is_queued']) ? true : false; 
					
					$tpl_builder = DevblocksPlatform::getTemplateBuilder();
					
					if(is_array($tickets))
					foreach($tickets as $ticket_id => $row) {
						CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $row, $tpl_labels, $tpl_tokens);
						$body = $tpl_builder->build($params['message'], $tpl_tokens);
						
						$fields = array(
							DAO_MailQueue::TYPE => Model_MailQueue::TYPE_TICKET_REPLY,
							DAO_MailQueue::TICKET_ID => $ticket_id,
							DAO_MailQueue::WORKER_ID => $params['worker_id'],
							DAO_MailQueue::UPDATED => time(),
							DAO_MailQueue::HINT_TO => $row[SearchFields_Ticket::TICKET_FIRST_WROTE],
							DAO_MailQueue::SUBJECT => $row[SearchFields_Ticket::TICKET_SUBJECT],
							DAO_MailQueue::BODY => $body,
							DAO_MailQueue::PARAMS_JSON => json_encode(array(
								'in_reply_message_id' => $row[SearchFields_Ticket::TICKET_FIRST_MESSAGE_ID],
							)),
						);
						
						if($is_queued) {
							$fields[DAO_MailQueue::IS_QUEUED] = 1;
						}
						
						$draft_id = DAO_MailQueue::create($fields);
					}
					break;

				default:
					// Custom fields
					if(substr($action,0,3)=="cf_") {
						$field_id = intval(substr($action,3));
						
						if(!isset($custom_fields[$field_id]) || !isset($params['value']))
							break;

						$field_values[$field_id] = $params;
					}
					break;
			}
		}

		if(!empty($ticket_ids)) {
			if(!empty($change_fields))
				DAO_Ticket::updateTicket($ticket_ids, $change_fields);
			
			// Custom Fields
			C4_AbstractView::_doBulkSetCustomFields(ChCustomFieldSource_Ticket::ID, $field_values, $ticket_ids);
		}
	}
};