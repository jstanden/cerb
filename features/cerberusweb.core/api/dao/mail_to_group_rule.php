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

class DAO_MailToGroupRule extends Cerb_ORMHelper {
	const ACTIONS_SER = 'actions_ser';
	const CREATED = 'created';
	const CRITERIA_SER = 'criteria_ser';
	const ID = 'id';
	const IS_STICKY = 'is_sticky';
	const NAME = 'name';
	const POS = 'pos';
	const STICKY_ORDER = 'sticky_order';
	
	const _CACHE_ALL = 'cerb:dao:mail_to_group_rule:all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// mediumtext
		$validation
			->addField(self::ACTIONS_SER)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		// mediumtext
		$validation
			->addField(self::CRITERIA_SER)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::IS_STICKY)
			->bit()
			;
		// varchar(128)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(128)
			;
		// int(10) unsigned
		$validation
			->addField(self::POS)
			->uint(4)
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::STICKY_ORDER)
			->uint(1)
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO mail_to_group_rule (created) ".
			"VALUES (%d)",
			time()
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'mail_to_group_rule', $fields);
		
		self::clearCache();
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($results = $cache->load(self::_CACHE_ALL))) {
			$results = self::getWhere(
				null,
				array(DAO_MailToGroupRule::IS_STICKY, DAO_MailToGroupRule::STICKY_ORDER, DAO_MailToGroupRule::POS),
				array(false, true, false),
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($results))
				return false;
			
			$cache->save($results, self::_CACHE_ALL, array(), 1200); // 20 mins
		}
		
		return $results;
	}
	
	/**
	 * @param string $where
	 * @return Model_MailToGroupRule[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, pos, created, name, criteria_ser, actions_ser, is_sticky, sticky_order ".
			"FROM mail_to_group_rule ".
			$where_sql .
			$sort_sql .
			$limit_sql 
			;
			
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_MailToGroupRule
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_MailToGroupRule[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_MailToGroupRule();
			$object->id = $row['id'];
			$object->pos = $row['pos'];
			$object->created = $row['created'];
			$object->name = $row['name'];
			$object->is_sticky = $row['is_sticky'];
			$object->sticky_order = $row['sticky_order'];
			
			$criteria_ser = $row['criteria_ser'];
			$object->criteria = (!empty($criteria_ser)) ? @unserialize($criteria_ser) : [];
			
			$actions_ser = $row['actions_ser'];
			$object->actions = (!empty($actions_ser)) ? @unserialize($actions_ser) : [];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = [$ids];
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::services()->database();
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM mail_to_group_rule WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}

	/**
	 * Increment the number of times we've matched this rule
	 *
	 * @param integer $id
	 */
	static function increment($id, $by=1) {
		$db = DevblocksPlatform::services()->database();
		$db->ExecuteMaster(sprintf("UPDATE mail_to_group_rule SET pos = pos + %d WHERE id = %d",
			$by,
			$id
		));
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
};

class Model_MailToGroupRule {
	public $id = 0;
	public $pos = 0;
	public $created = 0;
	public $name = '';
	public $criteria = [];
	public $actions = [];
	public $is_sticky = 0;
	public $sticky_order = 0;
	
	static function getMatches(Model_Address $fromAddress, CerberusParserMessage $message) {
		$matches = [];
		$rules = DAO_MailToGroupRule::getAll();
		$message_headers = $message->headers;
		$custom_fields = DAO_CustomField::getAll();
		
		// Lazy load when needed on criteria basis
		$address_field_values = null;
		$org_field_values = null;
		
		// Check filters
		if(is_array($rules))
		foreach($rules as $rule) { /* @var $rule Model_MailToGroupRule */
			$passed = 0;

			// check criteria
			if(is_array($rule->criteria))
			foreach($rule->criteria as $crit_key => $crit) {
				$value = $crit['value'] ?? null;
							
				switch($crit_key) {
					case 'dayofweek':
						$current_day = date('w');
//						$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($crit[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = date('H');
						$current_min = date('i');
//						$current_hour = 17;
//						$current_min = 5;

						if(null != ($from_time = @$crit['from']))
							list($from_hour, $from_min) = explode(':', $from_time);
						
						if(null != ($to_time = @$crit['to']))
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
						$tocc = [];
						$destinations = DevblocksPlatform::parseCsvString($value);

						// Build a list of To/Cc addresses on this message
						$to_list = CerberusMail::parseRfcAddresses(@$message_headers['to']);
						$cc_list = CerberusMail::parseRfcAddresses(@$message_headers['cc']);
						
						if(is_array($to_list))
						foreach($to_list as $addy) {
							if(!$addy['mailbox'] || !$addy['host'])
								continue;
						
							$tocc[] = $addy['email'];
						}
						
						if(is_array($cc_list))
						foreach($cc_list as $addy) {
							if(!$addy['mailbox'] || !$addy['host'])
								continue;
							
							$tocc[] = $addy['email'];
						}
						
						$dest_flag = false; // bail out when true
						if(is_array($destinations) && is_array($tocc))
						foreach($destinations as $dest) {
							if($dest_flag)
								break;
								
							$regexp_dest = DevblocksPlatform::strToRegExp($dest);
							
							foreach($tocc as $addy) {
								if(@preg_match($regexp_dest, $addy)) {
									$passed++;
									$dest_flag = true;
									break;
								}
							}
						}
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_from, $fromAddress->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						// [TODO] Decode if necessary
						$subject = $message_headers['subject'] ?? null;

						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $subject)) {
							$passed++;
						}
						break;

					case 'body':
						// Line-by-line body scanning (sed-like)
						$lines = preg_split("/[\r\n]/", $message->body);
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
						@$header = DevblocksPlatform::strLower($crit['header']);
						@$header_value = is_array($message_headers[$header]) ? implode(" ", $message_headers[$header]) : (string) $message_headers[$header];
						
						if(empty($header)) {
							$passed++;
							break;
						}
						
						if(empty($value)) { // we're checking for null/blanks
							if(empty($header_value)) {
								$passed++;
							}
							
						} elseif($header_value) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// Flatten CRLF
							if(@preg_match($regexp_header, $header_value)) {
								$passed++;
							}
						}
						
						break;
						
					default: // ignore invalids
						// Custom Fields
						if(0==strcasecmp('cf_',substr($crit_key,0,3))) {
							$field_id = substr($crit_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue 2;

							// Lazy values loader
							$field_values = array();
							switch($field->context) {
								case CerberusContexts::CONTEXT_ADDRESS:
									if(null == $address_field_values) {
										$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $fromAddress->id);
										$address_field_values = is_array($custom_field_values) ? array_shift($custom_field_values) : [];
									}
									$field_values =& $address_field_values;
									break;
								
								case CerberusContexts::CONTEXT_ORG:
									if(null == $org_field_values) {
										$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $fromAddress->contact_org_id);
										$org_field_values = is_array($custom_field_values) ? array_shift($custom_field_values) : [];
									}
									$field_values =& $org_field_values;
									break;
							}
							
							// No values, default.
							if(!isset($field_values[$field_id]))
								continue 2;
							
							// Type sensitive value comparisons
							switch($field->type) {
								case Model_CustomField::TYPE_CURRENCY:
									if(!isset($field_values[$field_id]))
										break;

									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									$currency_id = $field->params['currency_id'] ?? null;
									
									if(false == ($currency = DAO_Currency::get($currency_id)))
										break;
									
									$field_val = $currency->format($field_val, false);
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && intval($field_val) < intval($value))
										$passed++;
									break;
									
								case Model_CustomField::TYPE_DECIMAL:
									if(!isset($field_values[$field_id]))
										break;

									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									$decimal_at = $field->params['decimal_at'] ?? null;
									$field_val = DevblocksPlatform::strFormatDecimal($field_val, $decimal_at);
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && intval($field_val) < intval($value))
										$passed++;
									break;
									
								case Model_CustomField::TYPE_SINGLE_LINE:
								case Model_CustomField::TYPE_MULTI_LINE:
								case Model_CustomField::TYPE_URL:
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									break;
								case Model_CustomField::TYPE_NUMBER:
									if(!isset($field_values[$field_id]))
										break;

									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && intval($field_val) < intval($value))
										$passed++;
									break;
								case Model_CustomField::TYPE_DATE:
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($crit['from']) ? $crit['from'] : "0";
									$to = isset($crit['to']) ? $crit['to'] : "now";
									
									if(intval(@strtotime($from)) <= $field_val && intval(@strtotime($to)) >= $field_val) {
										$passed++;
									}
									break;
								case Model_CustomField::TYPE_CHECKBOX:
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									if(intval($value)==intval($field_val))
										$passed++;
									break;
								case Model_CustomField::TYPE_DROPDOWN:
								case Model_CustomField::TYPE_MULTI_CHECKBOX:
								case Model_CustomField::TYPE_WORKER:
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
			if($passed == count($rule->criteria)) {
				DAO_MailToGroupRule::increment($rule->id); // ++ the times we've matched
				$matches[$rule->id] = $rule;
				
				// Bail out if this rule had a move action
				if(isset($rule->actions['move']))
					return $matches;
			}
		}
		
		// If we're at the end of rules and didn't bail out yet
		if(!empty($matches))
			return $matches;
		
		// No matches
		return NULL;
	}
	
	/**
	 * @param CerberusParserModel $model
	 */
	function run(CerberusParserModel &$model) {
		if(null == $model->getTicketId())
			return;
		
		$groups = DAO_Group::getAll();
		$custom_fields = DAO_CustomField::getAll();
		
		// Update the model using actions
		if(is_array($this->actions))
		foreach($this->actions as $action => $params) {
			switch($action) {
				case 'move':
					if(array_key_exists('group_id', $params) && array_key_exists($params['group_id'], $groups)) {
						$model->setRouteGroup($groups[$params['group_id']]);
					}
					break;
					
				default:
					// Custom fields
					if(substr($action,0,3)=="cf_") {
						$field_id = intval(substr($action,3));
						
						if(!isset($custom_fields[$field_id]) || !isset($params['value']))
							break;

						// Persist in the model
						$model->getMessage()->custom_fields[] = array(
							'field_id' => $field_id,
							'context' => CerberusContexts::CONTEXT_TICKET,
							'context_id' => $model->getTicketId(),
							'value' => $params['value'],
						);
					}
					break;
			}
		}
	}
};