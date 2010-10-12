<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_PreParseRule extends DevblocksORMHelper {
	const CACHE_ALL = 'cerberus_cache_preparse_rules_all';
	
	const ID = 'id';
	const CREATED = 'created';
	const NAME = 'name';
	const CRITERIA_SER = 'criteria_ser';
	const ACTIONS_SER = 'actions_ser';
	const POS = 'pos';
	const IS_STICKY = 'is_sticky';
	const STICKY_ORDER = 'sticky_order';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO preparse_rule (created) ".
			"VALUES (%d)",
			time()
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'preparse_rule', $fields);

		self::clearCache();
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($rules = $cache->load(self::CACHE_ALL))) {
    	    $rules = self::getWhere();
    	    $cache->save($rules, self::CACHE_ALL);
	    }
	    
	    return $rules;
	}
	
	/**
	 * @param string $where
	 * @return Model_PreParseRule[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, created, name, criteria_ser, actions_ser, pos, is_sticky, sticky_order ".
			"FROM preparse_rule ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY is_sticky DESC, sticky_order ASC, pos desc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_PreParseRule	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * Increment the number of times we've matched this filter
	 *
	 * @param integer $id
	 */
	static function increment($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE preparse_rule SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}
	
	/**
	 * @param resource $rs
	 * @return Model_PreParseRule[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_PreParseRule();
			$object->created = $row['created'];
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->criteria = !empty($row['criteria_ser']) ? @unserialize($row['criteria_ser']) : array();
			$object->actions = !empty($row['actions_ser']) ? @unserialize($row['actions_ser']) : array();
			$object->pos = $row['pos'];
			$object->is_sticky = $row['is_sticky'];
			$object->sticky_order = $row['sticky_order'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM preparse_rule WHERE id IN (%s)", $ids_list));

		self::clearCache();
		
		return true;
	}

	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class Model_PreParseRule {
	public $id;
	public $created;
	public $name;
	public $criteria;
	public $actions;
	public $pos;
	public $is_sticky = 0;
	public $sticky_order = 0;
	
	/**
	 * Returns a Model_PreParserRule on a match, or NULL
	 *
	 * @param boolean $is_new
	 * @param string $from
	 * @param string $to
	 * @param CerberusParserMessage $message
	 * @return Model_PreParserRule[]
	 */
	static function getMatches(CerberusParserMessage $message) {
		$filters = DAO_PreParseRule::getAll();
		$headers = $message->headers;

		// New or reply?
		$is_new = (isset($message->headers['in-reply-to']) || isset($message->headers['references'])) ? false : true;

		// From address
		$fromInst = CerberusParser::getAddressFromHeaders($headers);
		
		// Stackable
		$matches = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria extensions
		$filter_criteria_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.criteria', false);
		
		// Lazy load when needed on criteria basis
		$address_field_values = null;
		$org_field_values = null;
		
		// check filters
		if(is_array($filters))
		foreach($filters as $filter) {
			$passed = 0;

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

					case 'type':
						if(($is_new && 0 == strcasecmp($value,'new')) 
							|| (!$is_new && 0 == strcasecmp($value,'reply')))
								$passed++; 
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(preg_match($regexp_from, $fromInst->email)) {
							$passed++;
						}
						break;
						
					case 'tocc':
						$tocc = array();
						$destinations = DevblocksPlatform::parseCsvString($value);

						// Build a list of To/Cc addresses on this message
						@$to_list = imap_rfc822_parse_adrlist($headers['to'],'localhost');
						@$cc_list = imap_rfc822_parse_adrlist($headers['cc'],'localhost');
						
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
						
					case 'header1':
					case 'header2':
					case 'header3':
					case 'header4':
					case 'header5':
						$header = strtolower($rule['header']);

						if(empty($value)) { // we're checking for null/blanks
							if(!isset($headers[$header]) || empty($headers[$header])) {
								$passed++;
							}
							
						} elseif(isset($headers[$header]) && !empty($headers[$header])) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// handle arrays like Received: and (broken)Content-Type headers  (farking spammers)
							if(is_array($headers[$header])) {
								foreach($headers[$header] as $array_header) {
									if(preg_match($regexp_header, str_replace(array("\r","\n"),' ',$array_header))) {
										$passed++;
										break;
									}
								}
							} else {
								// Flatten CRLF
								if(preg_match($regexp_header, str_replace(array("\r","\n"),' ',$headers[$header]))) {
									$passed++;
								}								
							}
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
						
					case 'body_encoding':
						$regexp_bodyenc = DevblocksPlatform::strToRegExp($value);

						if(preg_match($regexp_bodyenc, $message->body_encoding))
							$passed++;
						break;
						
					case 'attachment':
						$regexp_file = DevblocksPlatform::strToRegExp($value);

						// check the files in the raw message
						foreach($message->files as $file_name => $file) { /* @var $file ParserFile */
							if(preg_match($regexp_file, $file_name)) {
								$passed++;
								break;
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
							switch($field->context) {
								case CerberusContexts::CONTEXT_ADDRESS:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $fromInst->id));
									$field_values =& $address_field_values;
									break;
								case CerberusContexts::CONTEXT_ORG:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $fromInst->contact_org_id));
									$field_values =& $org_field_values;
									break;
							}
							
							// Type sensitive value comparisons
							// [TODO] Operators
							// [TODO] Highly redundant
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
								
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && intval($field_val) < intval($value))
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
								case 'M': // multi-picklist
								case 'X': // multi-checkbox
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
						
						} elseif(isset($filter_criteria_exts[$rule_key])) { // criteria extensions
							try {
								$crit_ext = $filter_criteria_exts[$rule_key]->createInstance();
								if($crit_ext->matches($filter, $message)) {
									$passed++;
									break;
								}
								
							} catch(Exception $e) {
								// Oops!
								//print_r($e);
							}
							
						}
						
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the filter
			if($passed == count($filter->criteria)) {
				DAO_PreParseRule::increment($filter->id); // ++ the times we've matched
				$matches[] = $filter;
				
				// Check our actions and see if we should bail out early
				if(isset($filter->actions) && !empty($filter->actions))
				foreach($filter->actions as $action_key => $action) {
					switch($action_key) {
						case 'nothing':
						case 'blackhole':
						case 'redirect':
						case 'bounce':
							return $matches;
							break;
					}
				}
			}
		}
		
		return $matches;
	}
	
};