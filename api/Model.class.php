<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2007, WebGroup Media LLC
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
 * We've never believed in encoding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your in-box that you probably
 * haven't had since spammers found you in a game of "E-mail Address
 * Battleship".  Miss. Miss. You sunk my in-box!
 *
 * A legitimate license entitles you to support, access to the developer
 * mailing list, the ability to participate in betas and the warm fuzzy
 * feeling of feeding a couple obsessed developers who want to help you get
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

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
	 * @return Model_PreParserRule
	 */
	static function getMatches($is_new, Model_Address $fromInst, CerberusParserMessage $message) {
		$filters = DAO_PreParseRule::getAll();
		$headers = $message->headers;
		
		// [TODO] Handle stackable
		$matches = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
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
							list($from_hour, $from_min) = split(':', $from_time);
						
						if(null != ($to_time = @$rule['to']))
							if(list($to_hour, $to_min) = split(':', $to_time));

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
						$lines = split("[\r\n]", $message->body);
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
							switch($field->source_extension) {
								case ChCustomFieldSource_Address::ID:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $fromInst->id));
									$field_values =& $address_field_values;
									break;
								case ChCustomFieldSource_Org::ID:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $fromInst->contact_org_id));
									$field_values =& $org_field_values;
									break;
							}
							
							// Type sensitive value comparisons
							// [TODO] Operators
							// [TODO] Highly redundant
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									break;
								case 'N': // number
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && $intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && $intval($field_val) < intval($value))
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
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
									
									foreach($value as $v) {
										if(isset($field_val[$v])) {
											$passed++;
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
				DAO_PreParseRule::increment($filter->id); // ++ the times we've matched
				return $filter;
			}
		}
		
		return NULL;
	}
	
}

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
		$messages = DAO_Ticket::getMessagesByTicket($ticket_id);
		$message_headers = array();

		if(empty($messages))
			return false;
		
		if(null != (@$message_last = array_pop($messages))) { /* @var $message_last CerberusMessage */
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
							list($from_hour, $from_min) = split(':', $from_time);
						
						if(null != ($to_time = @$rule['to']))
							if(list($to_hour, $to_min) = split(':', $to_time));

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
						$lines = split("[\r\n]", $message_body);
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
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									break;
								case 'N': // number
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && $intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && $intval($field_val) < intval($value))
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
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
									
									foreach($value as $v) {
										if(isset($field_val[$v])) {
											$passed++;
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
		$fields = array();
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
						$fields[DAO_Ticket::IS_WAITING] = intval($params['is_waiting']);
					if(isset($params['is_closed']))
						$fields[DAO_Ticket::IS_CLOSED] = intval($params['is_closed']);
					if(isset($params['is_deleted']))
						$fields[DAO_Ticket::IS_DELETED] = intval($params['is_deleted']);
					break;

				case 'assign':
					if(isset($params['worker_id'])) {
						$w_id = intval($params['worker_id']);
						if(0 == $w_id || isset($workers[$w_id]))
							$fields[DAO_Ticket::NEXT_WORKER_ID] = $w_id;
					}
					break;

				case 'move':
					if(isset($params['group_id']) && isset($params['bucket_id'])) {
						$g_id = intval($params['group_id']);
						$b_id = intval($params['bucket_id']);
						if(isset($groups[$g_id]) && (0==$b_id || isset($buckets[$b_id]))) {
							$fields[DAO_Ticket::TEAM_ID] = $g_id;
							$fields[DAO_Ticket::CATEGORY_ID] = $b_id;
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
			if(!empty($fields))
				DAO_Ticket::updateTicket($ticket_ids, $fields);
			
			// Custom Fields
			C4_AbstractView::_doBulkSetCustomFields(ChCustomFieldSource_Ticket::ID, $field_values, $ticket_ids);
		}
	}
};

/**
 * Enter description here...
 *
 */
abstract class C4_AbstractView {
	public $id = 0;
	public $name = "";
	public $view_columns = array();
	public $params = array();

	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = '';
	public $renderSortAsc = 1;

	function getData() {
	}

	function render() {
		echo ' '; // Expect Override
	}

	function renderCriteria($field) {
		echo ' '; // Expect Override
	}

	protected function _renderCriteriaCustomField($tpl, $field_id) {
		$field = DAO_CustomField::get($field_id);
		$tpl_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_PICKLIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				$tpl->assign('field', $field);
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__cfield_picklist.tpl');
				break;
			case Model_CustomField::TYPE_CHECKBOX:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__cfield_checkbox.tpl');
				break;
			case Model_CustomField::TYPE_DATE:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__date.tpl');
				break;
			case Model_CustomField::TYPE_NUMBER:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__number.tpl');
				break;
			default:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__string.tpl');
				break;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param string $value
	 * @abstract
	 */
	function doSetCriteria($field, $oper, $value) {
		// Expect Override
	}

	protected function _doSetCriteriaCustomField($token, $field_id) {
		$field = DAO_CustomField::get($field_id);
		@$oper = DevblocksPlatform::importGPC($_POST['oper'],'string','');
		@$value = DevblocksPlatform::importGPC($_POST['value'],'string','');
		
		$criteria = null;
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_PICKLIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				if(!empty($options)) {
					$criteria = new DevblocksSearchCriteria($token,$oper,$options);
				} else {
					$criteria = new DevblocksSearchCriteria($token,DevblocksSearchCriteria::OPER_IS_NULL);
				}
				break;
			case Model_CustomField::TYPE_CHECKBOX:
				$criteria = new DevblocksSearchCriteria($token,$oper,!empty($value) ? 1 : 0);
				break;
			case Model_CustomField::TYPE_NUMBER:
				$criteria = new DevblocksSearchCriteria($token,$oper,intval($value));
				break;
			case Model_CustomField::TYPE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
	
				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';
	
				$criteria = new DevblocksSearchCriteria($token,$oper,array($from,$to));
				break;
			default: // TYPE_SINGLE_LINE || TYPE_MULTI_LINE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($token,$oper,$value);
				break;
		}
		
		return $criteria;
	}
	
	/**
	 * This method automatically fixes any cached strange options, like 
	 * deleted custom fields.
	 *
	 */
	protected function _sanitize() {
		$fields = $this->getColumns();
		$custom_fields = DAO_CustomField::getAll();
		$needs_save = false;
		
		// Parameter sanity check
		foreach($this->params as $pidx => $null) {
			if(substr($pidx,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($pidx,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($this->params[$pidx]);
					$needs_save = true;
				}
			}
		}
		
		// View column sanity check
		foreach($this->view_columns as $cidx => $c) {
			// Custom fields
			if(substr($c,0,3) == "cf_") {
				if(0 != ($cf_id = intval(substr($c,3)))) {
					// Make sure our custom fields still exist
					if(!isset($custom_fields[$cf_id])) {
						unset($this->view_columns[$cidx]);
						$needs_save = true;
					}
				}
			} else {
				// If the column no longer exists (rare but worth checking)
				if(!isset($fields[$c])) {
					unset($this->view_columns[$cidx]);
					$needs_save = true;
				}
			}
		}
		
		// Sort by sanity check
		if(substr($this->renderSortBy,0,3)=="cf_") {
			if(0 != ($cf_id = intval(substr($this->renderSortBy,3)))) {
				if(!isset($custom_fields[$cf_id])) {
					$this->renderSortBy = null;
					$needs_save = true;
				}
			}
    	}
    	
    	if($needs_save) {
    		C4_AbstractViewLoader::setView($this->id, $this);
    	}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$vals = $param->value;

		if(!is_array($vals))
		$vals	= array($vals);

		$count = count($vals);
			
		for($i=0;$i<$count;$i++) {
			echo sprintf("%s%s",
			$vals[$i],
			($i+1<$count?', ':'')
			);
		}
	}

	/**
	 * All the view's available fields
	 *
	 * @return array
	 */
	static function getFields() {
		// Expect Override
		return array();
	}

	/**
	 * All searchable fields
	 *
	 * @return array
	 */
	static function getSearchFields() {
		// Expect Override
		return array();
	}

	/**
	 * All fields that can be displayed as columns in the view
	 *
	 * @return array
	 */
	static function getColumns() {
		// Expect Override
		return array();
	}

	function doCustomize($columns, $num_rows=10) {
		$this->renderLimit = $num_rows;

		$viewColumns = array();
		foreach($columns as $col) {
			if(empty($col))
			continue;
			$viewColumns[] = $col;
		}

		$this->view_columns = $viewColumns;
	}

	function doSortBy($sortBy) {
		$iSortAsc = intval($this->renderSortAsc);

		// [JAS]: If clicking the same header, toggle asc/desc.
		if(0 == strcasecmp($sortBy,$this->renderSortBy)) {
			$iSortAsc = (0 == $iSortAsc) ? 1 : 0;
		} else { // [JAS]: If a new header, start with asc.
			$iSortAsc = 1;
		}

		$this->renderSortBy = $sortBy;
		$this->renderSortAsc = $iSortAsc;
	}

	function doPage($page) {
		$this->renderPage = $page;
	}

	function doRemoveCriteria($field) {
		unset($this->params[$field]);
		$this->renderPage = 0;
	}

	function doResetCriteria() {
		$this->params = array();
		$this->renderPage = 0;
	}
	
	public static function _doBulkSetCustomFields($source_extension,$custom_fields, $ids) {
		$fields = DAO_CustomField::getAll();
		
		if(!empty($custom_fields))
		foreach($custom_fields as $cf_id => $params) {
			if(!is_array($params) || !isset($params['value']))
				continue;
				
			$cf_val = $params['value'];
			
			// Data massaging
			switch($fields[$cf_id]->type) {
				case Model_CustomField::TYPE_DATE:
					$cf_val = intval(@strtotime($cf_val));
					break;
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_NUMBER:
					$cf_val = (0==strlen($cf_val)) ? '' : intval($cf_val);
					break;
			}

			// If multi-selection types, handle delta changes
			if(Model_CustomField::TYPE_MULTI_PICKLIST==$fields[$cf_id]->type 
				|| Model_CustomField::TYPE_MULTI_CHECKBOX==$fields[$cf_id]->type) {
				if(is_array($cf_val))
				foreach($cf_val as $val) {
					$op = substr($val,0,1);
					$val = substr($val,1);
				
					if(is_array($ids))
					foreach($ids as $id) {
						if($op=='+')
							DAO_CustomFieldValue::setFieldValue($source_extension,$id,$cf_id,$val,true);
						elseif($op=='-')
							DAO_CustomFieldValue::unsetFieldValue($source_extension,$id,$cf_id,$val);
					}
				}
					
			// Otherwise, set/unset as a single field
			} else {
				if(is_array($ids))
				foreach($ids as $id) {
					if(0 != strlen($cf_val))
						DAO_CustomFieldValue::setFieldValue($source_extension,$id,$cf_id,$cf_val);
					else
						DAO_CustomFieldValue::unsetFieldValue($source_extension,$id,$cf_id);
				}
			}
		}
	}
};

/**
 * Used to persist a C4_AbstractView instance and not be encumbered by
 * classloading issues (out of the session) from plugins that might have
 * concrete AbstractView implementations.
 */
class C4_AbstractViewModel {
	public $class_name = '';

	public $id = 0;
	public $name = "";
	public $view_columns = array();
	public $params = array();

	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = '';
	public $renderSortAsc = 1;
};

/**
 * This is essentially an AbstractView Factory
 */
class C4_AbstractViewLoader {
	static $views = null;
	const VISIT_ABSTRACTVIEWS = 'abstractviews_list';

	static private function _init() {
		$visit = CerberusApplication::getVisit();
		self::$views = $visit->get(self::VISIT_ABSTRACTVIEWS,array());
	}

	/**
	 * @param string $view_label Abstract view identifier
	 * @return boolean
	 */
	static function exists($view_label) {
		if(is_null(self::$views)) self::_init();
		return isset(self::$views[$view_label]);
	}

	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @return C4_AbstractView instance
	 */
	static function getView($class, $view_label) {
		if(is_null(self::$views)) self::_init();

		if(!self::exists($view_label)) {
			if(empty($class) || !class_exists($class))
			return null;
				
			$view = new $class;
			self::setView($view_label, $view);
			return $view;
		}

		$model = self::$views[$view_label];
		$view = self::unserializeAbstractView($model);

		return $view;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @param C4_AbstractView $view
	 */
	static function setView($view_label, $view) {
		if(is_null(self::$views)) self::_init();
		self::$views[$view_label] = self::serializeAbstractView($view);
		self::_save();
	}

	static function deleteView($view_label) {
		unset(self::$views[$view_label]);
		self::_save();
	}
	
	static private function _save() {
		// persist
		$visit = CerberusApplication::getVisit();
		$visit->set(self::VISIT_ABSTRACTVIEWS, self::$views);
	}

	static function serializeAbstractView($view) {
		if(!$view instanceof C4_AbstractView) {
			return null;
		}

		$model = new C4_AbstractViewModel();
			
		$model->class_name = get_class($view);

		$model->id = $view->id;
		$model->name = $view->name;
		$model->view_columns = $view->view_columns;
		$model->params = $view->params;

		$model->renderPage = $view->renderPage;
		$model->renderLimit = $view->renderLimit;
		$model->renderSortBy = $view->renderSortBy;
		$model->renderSortAsc = $view->renderSortAsc;

		return $model;
	}

	static function unserializeAbstractView(C4_AbstractViewModel $model) {
		if(!class_exists($model->class_name, true))
			return null;
		
		if(null == ($inst = new $model->class_name))
			return null;

		/* @var $inst C4_AbstractView */
			
		$inst->id = $model->id;
		$inst->name = $model->name;
		$inst->view_columns = $model->view_columns;
		$inst->params = $model->params;

		$inst->renderPage = $model->renderPage;
		$inst->renderLimit = $model->renderLimit;
		$inst->renderSortBy = $model->renderSortBy;
		$inst->renderSortAsc = $model->renderSortAsc;

		return $inst;
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

class Model_AddressAuth {
	public $address_id;
	public $confirm;
	public $pass;
}

class Model_AddressToWorker {
	public $address;
	public $worker_id;
	public $is_confirmed;
	public $code;
	public $code_expire;
}

class C4_TicketView extends C4_AbstractView {
	const DEFAULT_ID = 'tickets_workspace';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TEAM_NAME,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
		);
	}

	function getData() {
		$objects = DAO_Ticket::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$view_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/tickets/';
		$tpl->assign('view_path',$view_path);
		$tpl->assign('view', $this);

		$visit = CerberusApplication::getVisit();

		$results = self::getData();
		$tpl->assign('results', $results);
		
		@$ids = array_keys($results[0]);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Undo?
		$last_action = C4_TicketView::getLastAction($this->id);
		$tpl->assign('last_action', $last_action);
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}

		$tpl->assign('timestamp_now', time());
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . $view_path . 'ticket_view.tpl');
	}

	function doResetCriteria() {
		$active_worker = CerberusApplication::getActiveWorker(); /* @var $active_worker CerberusWorker */
		$active_worker_memberships = $active_worker->getMemberships();
		
		$this->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
			SearchFields_Ticket::TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($active_worker_memberships)), // censor
		);
		$this->renderPage = 0;
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		$tpl_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';

		switch($field) {
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__string.tpl');
				break;

			case SearchFields_Ticket::TICKET_MESSAGE_CONTENT:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__number.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__bool.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__date.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_spam_training.tpl');
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_spam_score.tpl');
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_last_action.tpl');
				break;

			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__worker.tpl');
				break;
					
			case SearchFields_Ticket::TEAM_NAME:
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);

				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);

				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_team.tpl');
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
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
					$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
					continue;
					else
					$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TEAM_ID:
				$teams = DAO_Group::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($teams[$val]))
					continue;

					$strings[] = $teams[$val]->name;
				}
				echo implode(", ", $strings);
				break;
					
			case SearchFields_Ticket::TICKET_CATEGORY_ID:
				$buckets = DAO_Bucket::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "Inbox";
					} elseif(!isset($buckets[$val])) {
						continue;
					} else {
						$strings[] = $buckets[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'O':
							$strings[] = "New Ticket";
							break;
						case 'R':
							$strings[] = "Customer Reply";
							break;
						case 'W':
							$strings[] = "Worker Reply";
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'S':
							$strings[] = "Spam";
							break;
						case 'N':
							$strings[] = "Not Spam";
							break;
						default:
							$strings[] = "Not Trained";
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Ticket::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Ticket::TEAM_ID]);
		unset($fields[SearchFields_Ticket::TICKET_CATEGORY_ID]);
		unset($fields[SearchFields_Ticket::TICKET_UNLOCK_DATE]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Ticket::TEAM_ID]);
		unset($fields[SearchFields_Ticket::TICKET_MESSAGE_CONTENT]);
		unset($fields[SearchFields_Ticket::REQUESTER_ID]);
		unset($fields[SearchFields_Ticket::REQUESTER_ADDRESS]);
		unset($fields[SearchFields_Ticket::TICKET_UNLOCK_DATE]);
		return $fields;
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;

			case SearchFields_Ticket::TICKET_MESSAGE_CONTENT:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from) || (!is_numeric($from) && @false === strtotime(str_replace('.','-',$from))))
					$from = 0;
					
				if(empty($to) || (!is_numeric($to) && @false === strtotime(str_replace('.','-',$to))))
					$to = 'now';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				@$score = DevblocksPlatform::importGPC($_REQUEST['score'],'integer',null);
				if(!is_null($score) && is_numeric($score)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,intval($score)/100);
				}
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				@$last_action_code = DevblocksPlatform::importGPC($_REQUEST['last_action'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$last_action_code);
				break;

			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;

			case SearchFields_Ticket::TEAM_NAME:
				@$team_ids = DevblocksPlatform::importGPC($_REQUEST['team_id'],'array');
				@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array');

				if(!empty($team_ids))
				$this->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,$oper,$team_ids);
				if(!empty($bucket_ids))
				$this->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,$oper,$bucket_ids);

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

	/**
	 * @param array
	 * @param array
	 * @return boolean
	 * [TODO] Find a better home for this?
	 */
	function doBulkUpdate($filter, $filter_param, $data, $do, $ticket_ids=array()) {
		@set_time_limit(600);
	  
		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ticket_ids))
			return;
		
		$rule = new Model_GroupInboxFilter();
		$rule->actions = $do;
	  
		$params = $this->params;

		if(empty($filter)) {
			$data[] = '*'; // All, just to permit a loop in foreach($data ...)
		}

		switch($filter) {
			default:
			case 'subject':
			case 'sender':
			case 'header':
				if(is_array($data))
				foreach($data as $v) {
					$new_params = array();
					$do_header = null;
		    
					switch($filter) {
						case 'subject':
							$new_params = array(
								new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$v)
							);
							$do_header = 'subject';
							$ticket_ids = array();
							break;
						case 'sender':
							$new_params = array(
								new DevblocksSearchCriteria(SearchFields_Ticket::SENDER_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,$v)
							);
							$do_header = 'from';
							$ticket_ids = array();
							break;
						case 'header':
							$new_params = array(
								// [TODO] It will eventually come up that we need multiple header matches (which need to be pair grouped as OR)
								new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER,DevblocksSearchCriteria::OPER_EQ,$filter_param),
								new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER_VALUE,DevblocksSearchCriteria::OPER_EQ,$v)
							);
							$ticket_ids = array();
							break;
					}

					$new_params = array_merge($new_params, $params);
					$pg = 0;

					if(empty($ticket_ids)) {
						do {
							list($tickets,$null) = DAO_Ticket::search(
								array(),
								$new_params,
								100,
								$pg++,
								SearchFields_Ticket::TICKET_ID,
								true,
								false
							);
							 
							$ticket_ids = array_merge($ticket_ids, array_keys($tickets));
							 
						} while(!empty($tickets));
					}
			   
					$batch_total = count($ticket_ids);
					for($x=0;$x<=$batch_total;$x+=200) {
						$batch_ids = array_slice($ticket_ids,$x,200);
						$rule->run($batch_ids);
						unset($batch_ids);
					}
				}

				break;
		}

		unset($ticket_ids);
	}

	static function createSearchView() {
		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		$translate = DevblocksPlatform::getTranslationService();
		
		$view = new C4_TicketView();
		$view->id = CerberusApplication::VIEW_SEARCH;
		$view->name = $translate->_('common.search_results');
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TEAM_NAME,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
		);
		$view->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,DevblocksSearchCriteria::OPER_EQ,0),
			SearchFields_Ticket::TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($memberships)), // censor
		);
		$view->renderLimit = 100;
		$view->renderPage = 0;
		$view->renderSortBy = null; // SearchFields_Ticket::TICKET_UPDATED_DATE
		$view->renderSortAsc = 0;

		return $view;
	}

	static public function setLastAction($view_id, Model_TicketViewLastAction $last_action=null) {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
	  
		if(!is_null($last_action) && !empty($last_action->ticket_ids)) {
			$view_last_actions[$view_id] = $last_action;
		} else {
			if(isset($view_last_actions[$view_id])) {
				unset($view_last_actions[$view_id]);
			}
		}
	  
		$visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,$view_last_actions);
	}

	/**
	 * @param string $view_id
	 * @return Model_TicketViewLastAction
	 */
	static public function getLastAction($view_id) {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
		return (isset($view_last_actions[$view_id]) ? $view_last_actions[$view_id] : null);
	}

	static public function clearLastActions() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
	}

};

class C4_AddressView extends C4_AbstractView {
	const DEFAULT_ID = 'addresses';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'E-mail Addresses';
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
		
		$this->params = array(
			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
		);
	}

	function getData() {
		$objects = DAO_Address::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
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
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/addresses/address_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		$tpl_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';
		
		switch($field) {
			case SearchFields_Address::EMAIL:
			case SearchFields_Address::FIRST_NAME:
			case SearchFields_Address::LAST_NAME:
			case SearchFields_Address::ORG_NAME:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Address::NUM_SPAM:
			case SearchFields_Address::NUM_NONSPAM:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Address::IS_BANNED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
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
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
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

class C4_AttachmentView extends C4_AbstractView {
	const DEFAULT_ID = 'attachments';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Attachments';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_Attachment::FILE_SIZE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Attachment::MIME_TYPE,
			SearchFields_Attachment::FILE_SIZE,
			SearchFields_Attachment::MESSAGE_CREATED_DATE,
			SearchFields_Attachment::ADDRESS_EMAIL,
			SearchFields_Attachment::TICKET_MASK,
		);
		
//		$this->params = array(
//			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
//		);
	}

	function getData() {
		$objects = DAO_Attachment::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/configuration/tabs/attachments/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::FILEPATH:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
			case SearchFields_Attachment::ADDRESS_EMAIL:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_Attachment::ID:
//			case SearchFields_Attachment::MESSAGE_ID:
			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::FILE_SIZE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			default:
				echo '';
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
		return SearchFields_Attachment::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Attachment::ID]);
		unset($fields[SearchFields_Attachment::MESSAGE_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
//		$this->params = array(
//			SearchFields_Address::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_Address::NUM_NONSPAM,'>',0),
//		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::FILEPATH:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
			case SearchFields_Attachment::ADDRESS_EMAIL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Attachment::ID:
			case SearchFields_Attachment::MESSAGE_ID:
			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::FILE_SIZE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
		$change_fields = array();
		$deleted = false;

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'deleted':
					$deleted = true;
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Attachment::search(
				$this->params,
				100,
				$pg++,
				SearchFields_Attachment::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted) { 
				DAO_Attachment::update($batch_ids, $change_fields);
			} else {
				DAO_Attachment::delete($batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			

};

class C4_ContactOrgView extends C4_AbstractView {
	const DEFAULT_ID = 'contact_orgs';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Organizations';
		$this->renderSortBy = 'c_name';
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContactOrg::COUNTRY,
			SearchFields_ContactOrg::CREATED,
			SearchFields_ContactOrg::PHONE,
			SearchFields_ContactOrg::WEBSITE,
		);
	}

	function getData() {
		$objects = DAO_ContactOrg::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('core_tpl', DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/');
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$org_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('custom_fields', $org_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/contacts/orgs/contact_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ContactOrg::NAME:
			case SearchFields_ContactOrg::STREET:
			case SearchFields_ContactOrg::CITY:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::POSTAL:
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::PHONE:
			case SearchFields_ContactOrg::WEBSITE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_ContactOrg::CREATED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
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
		return SearchFields_ContactOrg::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_ContactOrg::ID]);
		return $fields;
	}

	static function getColumns() {
		return self::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContactOrg::NAME:
			case SearchFields_ContactOrg::STREET:
			case SearchFields_ContactOrg::CITY:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::POSTAL:
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::PHONE:
			case SearchFields_ContactOrg::WEBSITE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_ContactOrg::CREATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
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
		@set_time_limit(0);
	  
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
				case 'country':
					$change_fields[DAO_ContactOrg::COUNTRY] = $v;
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_ContactOrg::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_ContactOrg::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_ContactOrg::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_Org::ID, $custom_fields, $batch_ids);

			unset($batch_ids);
		}

		unset($ids);
	}
		
};

class C4_TaskView extends C4_AbstractView {
	const DEFAULT_ID = 'tasks';
	const DEFAULT_TITLE = 'All Open Tasks';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Task::DUE_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			);
		
		$this->params = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_Task::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->assign('timestamp_now', time());

		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);

//		$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
		
		// Make a list of unique source_extension and load their renderers
		$source_extensions = array();
		if(is_array($results) && isset($results[0]))
		foreach($results[0] as $rows) {
			$source_extension = $rows[SearchFields_Task::SOURCE_EXTENSION];
			if(!isset($source_extensions[$source_extension]) 
				&& !empty($source_extension)
				&& null != ($mft = DevblocksPlatform::getExtension($source_extension))) {
				$source_extensions[$source_extension] = $mft->createInstance();
			} 
		}
		$tpl->assign('source_renderers', $source_extensions);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/tasks/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_Task::TITLE:
			case SearchFields_Task::CONTENT:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
				$tpl->assign('sources', $source_renderers);
				$tpl->display('file:' . $tpl_path . 'tasks/criteria/source.tpl');
				break;
				
			case SearchFields_Task::IS_COMPLETED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Task::DUE_DATE:
			case SearchFields_Task::COMPLETED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Task::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
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
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Task::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
						$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
						continue;
					else
						$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				$sources = $ext = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);			
				$strings = array();
				
				foreach($values as $val) {
					if(!isset($sources[$val]))
						continue;
					else
						$strings[] = $sources[$val]->getSourceName();
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Task::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Task::ID]);
		unset($fields[SearchFields_Task::SOURCE_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Task::ID]);
		unset($fields[SearchFields_Task::CONTENT]);
		unset($fields[SearchFields_Task::SOURCE_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0)
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Task::TITLE:
			case SearchFields_Task::CONTENT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				@$sources = DevblocksPlatform::importGPC($_REQUEST['sources'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$sources);
				break;
				
			case SearchFields_Task::COMPLETED_DATE:
			case SearchFields_Task::DUE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Task::IS_COMPLETED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Task::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
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
				case 'due':
					@$date = strtotime($v);
					$change_fields[DAO_Task::DUE_DATE] = intval($date);
					break;
				case 'status':
					if(1==intval($v)) { // completed
						$change_fields[DAO_Task::IS_COMPLETED] = 1;
						$change_fields[DAO_Task::COMPLETED_DATE] = time();
					} else { // active
						$change_fields[DAO_Task::IS_COMPLETED] = 0;
						$change_fields[DAO_Task::COMPLETED_DATE] = 0;
					}
					break;
				case 'worker_id':
					$change_fields[DAO_Task::WORKER_ID] = intval($v);
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
			list($objects,$null) = DAO_Task::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_Task::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Task::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_Task::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};

class C4_WorkerEventView extends C4_AbstractView {
	const DEFAULT_ID = 'worker_events';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Worker Events';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_WorkerEvent::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_WorkerEvent::CONTENT,
			SearchFields_WorkerEvent::CREATED_DATE,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WorkerEvent::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/home/tabs/my_events/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WorkerEvent::TITLE:
			case SearchFields_WorkerEvent::CONTENT:
			case SearchFields_WorkerEvent::URL:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_WorkerEvent::ID:
//			case SearchFields_WorkerEvent::MESSAGE_ID:
//			case SearchFields_WorkerEvent::TICKET_ID:
//			case SearchFields_WorkerEvent::FILE_SIZE:
//				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl');
//				break;
			case SearchFields_WorkerEvent::IS_READ:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_WorkerEvent::CREATED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_WorkerEvent::WORKER_ID:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
				break;
			default:
				echo '';
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
		return SearchFields_WorkerEvent::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_WorkerEvent::ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
//		$this->params = array(
//			SearchFields_WorkerEvent::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_WorkerEvent::NUM_NONSPAM,'>',0),
//		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WorkerEvent::TITLE:
			case SearchFields_WorkerEvent::CONTENT:
			case SearchFields_WorkerEvent::URL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_WorkerEvent::WORKER_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_WorkerEvent::CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_WorkerEvent::IS_READ:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

//	function doBulkUpdate($filter, $do, $ids=array()) {
//		@set_time_limit(600); // [TODO] Temp!
//	  
//		$change_fields = array();
//
//		if(empty($do))
//		return;
//
//		if(is_array($do))
//		foreach($do as $k => $v) {
//			switch($k) {
//				case 'banned':
//					$change_fields[DAO_Address::IS_BANNED] = intval($v);
//					break;
//			}
//		}
//
//		$pg = 0;
//
//		if(empty($ids))
//		do {
//			list($objects,$null) = DAO_Address::search(
//			$this->params,
//			100,
//			$pg++,
//			SearchFields_Address::ID,
//			true,
//			false
//			);
//			 
//			$ids = array_merge($ids, array_keys($objects));
//			 
//		} while(!empty($objects));
//
//		$batch_total = count($ids);
//		for($x=0;$x<=$batch_total;$x+=100) {
//			$batch_ids = array_slice($ids,$x,100);
//			DAO_Address::update($batch_ids, $change_fields);
//			unset($batch_ids);
//		}
//
//		unset($ids);
//	}

};

class Model_ContactOrg {
	public $id;
	public $name;
	public $street;
	public $city;
	public $province;
	public $postal;
	public $country;
	public $phone;
	public $website;
	public $created;
	public $sync_id = '';
};

class Model_WorkerWorkspaceList {
	public $id = 0;
	public $worker_id = 0;
	public $workspace = '';
	public $source_extension = '';
	public $list_view = '';
	public $list_pos = 0;
};

class Model_WorkerWorkspaceListView {
	public $title = 'New List';
//	public $workspace = '';
	public $columns = array();
	public $num_rows = 10;
	public $params = array();
};

class Model_Activity {
	public $translation_code;
	public $params;

	public function __construct($translation_code='activity.default',$params=array()) {
		$this->translation_code = $translation_code;
		$this->params = $params;
	}

	public function toString(CerberusWorker $worker=null) {
		if(null == $worker)
			return;
			
		$translate = DevblocksPlatform::getTranslationService();
		$params = $this->params;

		// Prepend the worker name to the activity's param list
		array_unshift($params, sprintf("<b>%s</b>%s",
			$worker->getName(),
			(!empty($worker->title) 
				? (' (' . $worker->title . ')') 
				: ''
			)
		));
		
		return vsprintf(
			$translate->_($this->translation_code), 
			$params
		);
	}
}

class Model_MailToGroupRule {
	public $id = 0;
	public $pos = 0;
	public $created = 0;
	public $name = '';
	public $criteria = array();
	public $actions = array();
	public $is_sticky = 0;
	public $sticky_order = 0;
	
	static function getMatches(Model_Address $fromAddress, CerberusParserMessage $message) {
//		print_r($fromAddress);
//		print_r($message);
		
		$matches = array();
		$rules = DAO_MailToGroupRule::getWhere();
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
			foreach($rule->criteria as $crit_key => $crit) {
				@$value = $crit['value'];
							
				switch($crit_key) {
					case 'dayofweek':
						$current_day = strftime('%w');
//						$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($crit[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = strftime('%H');
						$current_min = strftime('%M');
//						$current_hour = 17;
//						$current_min = 5;

						if(null != ($from_time = @$crit['from']))
							list($from_hour, $from_min) = split(':', $from_time);
						
						if(null != ($to_time = @$crit['to']))
							if(list($to_hour, $to_min) = split(':', $to_time));

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
						if(@preg_match($regexp_from, $fromAddress->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						// [TODO] Decode if necessary
						@$subject = $message_headers['subject'];

						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $subject)) {
							$passed++;
						}
						break;

					case 'body':
						// Line-by-line body scanning (sed-like)
						$lines = split("[\r\n]", $message->body);
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
						@$header = strtolower($crit['header']);

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
						if(0==strcasecmp('cf_',substr($crit_key,0,3))) {
							$field_id = substr($crit_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue;

							// Lazy values loader
							$field_values = array();
							switch($field->source_extension) {
								case ChCustomFieldSource_Address::ID:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $fromAddress->id));
									$field_values =& $address_field_values;
									break;
								case ChCustomFieldSource_Org::ID:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $fromAddress->contact_org_id));
									$field_values =& $org_field_values;
									break;
							}
							
							// No values, default.
							if(!isset($field_values[$field_id]))
								continue;
							
							// Type sensitive value comparisons
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value), $field_val))
										$passed++;
									break;
								case 'N': // number
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && $intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && $intval($field_val) < intval($value))
										$passed++;
									break;
								case 'E': // date
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($crit['from']) ? $crit['from'] : "0";
									$to = isset($crit['to']) ? $crit['to'] : "now";
									
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
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
									
									foreach($value as $v) {
										if(isset($field_val[$v])) {
											$passed++;
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
	 * @param integer[] $ticket_ids
	 */
	function run($ticket_ids) {
		if(!is_array($ticket_ids)) $ticket_ids = array($ticket_ids);
		
		$fields = array();
		$field_values = array();

		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
//		$workers = DAO_Worker::getAll();
		$custom_fields = DAO_CustomField::getAll();
		
		// actions
		if(is_array($this->actions))
		foreach($this->actions as $action => $params) {
			switch($action) {
//				case 'status':
//					if(isset($params['is_waiting']))
//						$fields[DAO_Ticket::IS_WAITING] = intval($params['is_waiting']);
//					if(isset($params['is_closed']))
//						$fields[DAO_Ticket::IS_CLOSED] = intval($params['is_closed']);
//					if(isset($params['is_deleted']))
//						$fields[DAO_Ticket::IS_DELETED] = intval($params['is_deleted']);
//					break;

//				case 'assign':
//					if(isset($params['worker_id'])) {
//						$w_id = intval($params['worker_id']);
//						if(0 == $w_id || isset($workers[$w_id]))
//							$fields[DAO_Ticket::NEXT_WORKER_ID] = $w_id;
//					}
//					break;

				case 'move':
					if(isset($params['group_id']) && isset($params['bucket_id'])) {
						$g_id = intval($params['group_id']);
						$b_id = intval($params['bucket_id']);
						if(isset($groups[$g_id]) && (0==$b_id || isset($buckets[$b_id]))) {
							$fields[DAO_Ticket::TEAM_ID] = $g_id;
							$fields[DAO_Ticket::CATEGORY_ID] = $b_id;
						}
					}
					break;
					
//				case 'spam':
//					if(isset($params['is_spam'])) {
//						if(intval($params['is_spam'])) {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsSpam($ticket_id);
//						} else {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsNotSpam($ticket_id);
//						}
//					}
//					break;

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
			if(!empty($fields))
				DAO_Ticket::updateTicket($ticket_ids, $fields);
			
			// Custom Fields
			C4_AbstractView::_doBulkSetCustomFields(ChCustomFieldSource_Ticket::ID, $field_values, $ticket_ids);
		}
	}
	
};

class CerberusVisit extends DevblocksVisit {
	private $worker;

	const KEY_VIEW_LAST_ACTION = 'view_last_action';
	const KEY_MY_WORKSPACE = 'view_my_workspace';
	const KEY_MAIL_MODE = 'mail_mode';
	const KEY_HOME_SELECTED_TAB = 'home_selected_tab';
	const KEY_OVERVIEW_FILTER = 'overview_filter';
	const KEY_WORKFLOW_FILTER = 'workflow_filter';

	public function __construct() {
		$this->worker = null;
	}

	/**
	 * @return CerberusWorker
	 */
	public function getWorker() {
		return $this->worker;
	}
	
	public function setWorker(CerberusWorker $worker=null) {
		$this->worker = $worker;
	}
};

class CerberusBayesWord {
	public $id = -1;
	public $word = '';
	public $spam = 0;
	public $nonspam = 0;
	public $probability = CerberusBayes::PROBABILITY_UNKNOWN;
	public $interest_rating = 0.0;
}

class CerberusWorker {
	public $id;
	public $first_name;
	public $last_name;
	public $email;
	public $pass;
	public $title;
	public $is_superuser=0;
	public $is_disabled=0;
	public $last_activity;
	public $last_activity_date;

	/**
	 * @return Model_TeamMember[]
	 */
	function getMemberships() {
		return DAO_Worker::getWorkerGroups($this->id); 
	}

	function hasPriv($priv_id) {
		// We don't need to do much work if we're a superuser
		if($this->is_superuser)
			return true;
		
		$settings = CerberusSettings::getInstance();
		$acl_enabled = $settings->get(CerberusSettings::ACL_ENABLED);
			
		// ACL is a paid feature (please respect the licensing and support the project!)
		$license = CerberusLicense::getInstance();
		if(!$acl_enabled || !isset($license['serial']) || isset($license['a']))
			return ("core.config"==substr($priv_id,0,11)) ? false : true;
			
		// Check the aggregated worker privs from roles
		$acl = DAO_WorkerRole::getACL();
		$privs_by_worker = $acl[DAO_WorkerRole::CACHE_KEY_PRIVS_BY_WORKER];
		
		if(!empty($priv_id) && isset($privs_by_worker[$this->id][$priv_id]))
			return true;
			
		return false;
	}
	
	function isTeamManager($team_id) {
		@$memberships = $this->getMemberships();
		$teams = DAO_Group::getAll();
		if(
			empty($team_id) // null
			|| !isset($teams[$team_id]) // doesn't exist
			|| !isset($memberships[$team_id])  // not a member
			|| (!$memberships[$team_id]->is_manager && !$this->is_superuser) // not a manager or superuser
		){
			return false;
		}
		return true;
	}

	function isTeamMember($team_id) {
		@$memberships = $this->getMemberships();
		$teams = DAO_Group::getAll();
		if(
			empty($team_id) // null
			|| !isset($teams[$team_id]) // not a team
			|| !isset($memberships[$team_id]) // not a member
		) {
			return false;
		}
		return true;
	}
	
	function getName($reverse=false) {
		if(!$reverse) {
			$name = sprintf("%s%s%s",
				$this->first_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
				$this->last_name
			);
		} else {
			$name = sprintf("%s%s%s",
				$this->last_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? ", " : "",
				$this->first_name
			);
		}
		
		return $name;
	}
	
};

class Model_WorkerRole {
	public $id;
	public $name;
};

class Model_WorkerEvent {
	public $id;
	public $created_date;
	public $worker_id;
	public $title;
	public $content;
	public $is_read;
	public $url;
};

class Model_ViewRss {
	public $id = 0;
	public $title = '';
	public $hash = '';
	public $worker_id = 0;
	public $created = 0;
	public $source_extension = '';
	public $params = array();
};

class Model_TicketViewLastAction {
	// [TODO] Recycle the bulk update constants for these actions?
	const ACTION_NOT_SPAM = 'not_spam';
	const ACTION_SPAM = 'spam';
	const ACTION_CLOSE = 'close';
	const ACTION_DELETE = 'delete';
	const ACTION_MOVE = 'move';
	const ACTION_TAKE = 'take';
	const ACTION_SURRENDER = 'surrender';
	const ACTION_WAITING = 'waiting';
	const ACTION_NOT_WAITING = 'not_waiting';

	public $ticket_ids = array(); // key = ticket id, value=old value
	public $action = ''; // spam/closed/move, etc.
	public $action_params = array(); // DAO Actions Taken
};

class CerberusTicketStatus {
	const OPEN = 0;
	const CLOSED = 1;
};

class CerberusTicketSpamTraining { // [TODO] Append 'Enum' to class name?
	const BLANK = '';
	const NOT_SPAM = 'N';
	const SPAM = 'S';
};

class CerberusTicket {
	public $id;
	public $mask;
	public $subject;
	public $is_waiting = 0;
	public $is_closed = 0;
	public $is_deleted = 0;
	public $team_id;
	public $category_id;
	public $first_message_id;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $due_date;
	public $unlock_date;
	public $spam_score;
	public $spam_training;
	public $interesting_words;
	public $last_action_code;
	public $last_worker_id;
	public $next_worker_id;

	function CerberusTicket() {}

	function getMessages() {
		$messages = DAO_Ticket::getMessagesByTicket($this->id);
		return $messages;
	}

	function getRequesters() {
		$requesters = DAO_Ticket::getRequestersByTicket($this->id);
		return $requesters;
	}

	/**
	 * @return CloudGlueTag[]
	 */
	function getTags() {
		$result = DAO_CloudGlue::getTagsOnContents(array($this->id), CerberusApplication::INDEX_TICKETS);
		$tags = array_shift($result);
		return $tags;
	}

};

class CerberusTicketActionCode {
	const TICKET_OPENED = 'O';
	const TICKET_CUSTOMER_REPLY = 'R';
	const TICKET_WORKER_REPLY = 'W';
};

class CerberusMessage {
	public $id;
	public $ticket_id;
	public $created_date;
	public $address_id;
	public $is_outgoing;
	public $worker_id;

	function CerberusMessage() {}

	function getContent() {
		return DAO_MessageContent::get($this->id);
	}

	function getHeaders() {
		return DAO_MessageHeader::getAll($this->id);
	}

	/**
	 * returns an array of the message's attachments
	 *
	 * @return Model_Attachment[]
	 */
	function getAttachments() {
		$attachments = DAO_Attachment::getByMessageId($this->id);
		return $attachments;
	}

};

class Model_MessageNote {
	const TYPE_NOTE = 0;
	const TYPE_WARNING = 1;
	const TYPE_ERROR = 2;

	public $id;
	public $type;
	public $message_id;
	public $created;
	public $worker_id;
	public $content;
};

class Model_Note {
	const EXTENSION_ID = 'cerberusweb.note';
	
	public $id;
	public $source_extension_id;
	public $source_id;
	public $created;
	public $worker_id;
	public $content;
};

class Model_Attachment {
	public $id;
	public $message_id;
	public $display_name;
	public $filepath;
	public $file_size = 0;
	public $mime_type = '';

	public function getFileContents() {
		$file_path = APP_STORAGE_PATH . '/attachments/';
		if (!empty($this->filepath))
		return file_get_contents($file_path.$this->filepath,false);
	}
	
	public function getFileSize() {
		$file_path = APP_STORAGE_PATH . '/attachments/';
		if (!empty($this->filepath))
		return filesize($file_path.$this->filepath);
	}
	
	public static function saveToFile($file_id, $contents) {
		$attachment_path = APP_STORAGE_PATH . '/attachments/';
		
	    // Make file attachments use buckets so we have a max per directory
		$attachment_bucket = sprintf("%03d/",
			mt_rand(1,100)
		);
		$attachment_file = $file_id;
		
		if(!file_exists($attachment_path.$attachment_bucket)) {
			@mkdir($attachment_path.$attachment_bucket, 0770, true);
			// [TODO] Needs error checking
		}
		
		file_put_contents($attachment_path.$attachment_bucket.$attachment_file, $contents);
		
		return $attachment_bucket.$attachment_file;
	}
};

class CerberusTeam {
	public $id;
	public $name;
	public $count;
	public $is_default = 0;
}

class Model_TeamMember {
	public $id;
	public $team_id;
	public $is_manager = 0;
}

class CerberusCategory {
	public $id;
	public $pos=0;
	public $name = '';
	public $team_id = 0;
	public $is_assignable = 1;
}

class CerberusPop3Account {
	public $id;
	public $enabled=1;
	public $nickname;
	public $protocol='pop3';
	public $host;
	public $username;
	public $password;
	public $port=110;
};

class Model_Community {
	public $id = 0;
	public $name = '';
}

class Model_FnrTopic {
	public $id = 0;
	public $name = '';

	function getResources() {
		$where = sprintf("%s = %d",
		DAO_FnrExternalResource::TOPIC_ID,
		$this->id
		);
		$resources = DAO_FnrExternalResource::getWhere($where);
		return $resources;
	}
};

class Model_FnrQuery {
	public $id;
	public $query;
	public $created;
	public $source;
	public $no_match;
};

class Model_FnrExternalResource {
	public $id = 0;
	public $name = '';
	public $url = '';
	public $topic_id = 0;

	public static function searchResources($resources, $query) {
		$feeds = array();
		$topics = DAO_FnrTopic::getWhere();

		if(is_array($resources))
		foreach($resources as $resource) { /* @var $resource Model_FnrExternalResource */
			try {
				$url = str_replace("#find#",rawurlencode($query),$resource->url);
				$feed = Zend_Feed::import($url);
				if($feed->count())
					$feeds[] = array(
					'name' => $resource->name,
					'topic_name' => @$topics[$resource->topic_id]->name,
					'feed' => $feed
				);
			} catch(Exception $e) {}
		}
		
		return $feeds;
	}
};

class Model_MailTemplate {
	const TYPE_COMPOSE = 1;
	const TYPE_REPLY = 2;
	const TYPE_CREATE = 3;
//	const TYPE_CLOSE = 4;
	
	public $id = 0;
	public $title = '';
	public $description = '';
	public $folder = '';
	public $owner_id = 0;
	public $template_type = 0;
	public $content = '';

	public function getRenderedContent($message_id) {
		$raw = $this->content;

		$replace = array();
		$with = array();

		$replace[] = '#timestamp#';
		$with[] = date('r');
		
		if(!empty($message_id)) {
			$message = DAO_Ticket::getMessage($message_id);
			$ticket = DAO_Ticket::getTicket($message->ticket_id);
			$sender = DAO_Address::get($message->address_id);
			$sender_org = DAO_ContactOrg::get($sender->contact_org_id);
			
			$replace[] = '#sender_first_name#';
			$replace[] = '#sender_last_name#';
			$replace[] = '#sender_org#';
	
			$with[] = $sender->first_name;
			$with[] = $sender->last_name;
			$with[] = (!empty($sender_org)?$sender_org->name:"");
			
			$replace[] = '#ticket_id#';
			$replace[] = '#ticket_mask#';
			$replace[] = '#ticket_subject#';

			$with[] = $ticket->id;
			$with[] = $ticket->mask;
			$with[] = $ticket->subject;
		}
			
		if(null != ($active_worker = CerberusApplication::getActiveWorker())) {
			$worker = DAO_Worker::getAgent($active_worker->id); // most recent info (not session)
			
			$replace[] = '#worker_first_name#';
			$replace[] = '#worker_last_name#';
			$replace[] = '#worker_title#';
	
			$with[] = $worker->first_name;
			$with[] = $worker->last_name;
			$with[] = $worker->title;
		}

		return str_replace($replace, $with, $raw);
	}
};

class Model_TicketComment {
	public $id;
	public $ticket_id;
	public $address_id;
	public $created;
	public $comment;
	
	public function getAddress() {
		return DAO_Address::get($this->address_id);
	}
};

class Model_CustomField {
	const TYPE_SINGLE_LINE = 'S';
	const TYPE_MULTI_LINE = 'T';
	const TYPE_NUMBER = 'N';
	const TYPE_DATE = 'E';
	const TYPE_DROPDOWN = 'D';
	const TYPE_MULTI_PICKLIST = 'M';
	const TYPE_CHECKBOX = 'C';
	const TYPE_MULTI_CHECKBOX = 'X';
	
	public $id = 0;
	public $name = '';
	public $type = '';
	public $group_id = 0;
	public $source_extension = '';
	public $pos = 0;
	public $options = array();
	
	static function getTypes() {
		return array(
			self::TYPE_SINGLE_LINE => 'Text: Single Line',
			self::TYPE_MULTI_LINE => 'Text: Multi-Line',
			self::TYPE_NUMBER => 'Number',
			self::TYPE_DATE => 'Date',
			self::TYPE_DROPDOWN => 'Picklist',
			self::TYPE_MULTI_PICKLIST => 'Multi-Picklist',
			self::TYPE_CHECKBOX => 'Checkbox',
			self::TYPE_MULTI_CHECKBOX => 'Multi-Checkbox',
		);
	}
};

class Model_Task {
	public $id;
	public $title;
	public $worker_id;
	public $due_date;
	public $is_completed;
	public $completed_date;
	public $content;
	public $source_extension;
	public $source_id;
};

