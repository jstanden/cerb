<?php
class PageSection_SetupMailFiltering extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'mail_filtering');
		
		$filters = DAO_PreParseRule::getAll(true);
		$tpl->assign('filters', $filters);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Custom Field contexts (tickets, orgs, etc.)
		$tpl->assign('context_manifests', Extension_DevblocksContext::getAll());
		
		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);

		// Criteria extensions
		$filter_criteria_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.criteria', false);
		$tpl->assign('filter_criteria_exts', $filter_criteria_exts);
		
		// Action extensions
		$filter_action_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.action', false);
		$tpl->assign('filter_action_exts', $filter_action_exts);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_filtering/index.tpl');
	}

	function saveAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
	    @$sticky_ids = DevblocksPlatform::importGPC($_REQUEST['sticky_ids'],'array',array());
	    @$sticky_order = DevblocksPlatform::importGPC($_REQUEST['sticky_order'],'array',array());

		DAO_PreParseRule::delete($ids);

	    // Reordering
	    if(is_array($sticky_ids) && is_array($sticky_order))
	    foreach($sticky_ids as $idx => $id) {
	    	@$order = intval($sticky_order[$idx]);
			DAO_PreParseRule::update($id, array (
	    		DAO_PreParseRule::STICKY_ORDER => $order
	    	));
	    }
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','mail_filtering')));
	}
	
	function peekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		if(null != ($filter = DAO_PreParseRule::get($id))) {
			$tpl->assign('filter', $filter);
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		// Custom Fields: Address
		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$tpl->assign('org_fields', $org_fields);
		
		// Criteria extensions
		$filter_criteria_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.criteria', true);
		$tpl->assign('filter_criteria_exts', $filter_criteria_exts);
		
		// Action extensions
		$filter_action_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.action', true);
		$tpl->assign('filter_action_exts', $filter_action_exts);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_filtering/peek.tpl');
	}
	
	// Post
	function savePeekAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria extensions
		$filter_criteria_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.criteria', false);
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNumDash($rule);
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'dayofweek':
					// days
					$days = DevblocksPlatform::importGPC($_REQUEST['value_dayofweek'],'array',array());
					if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
					if(in_array(1,$days)) $criteria['mon'] = 'Monday';
					if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
					if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
					if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
					if(in_array(5,$days)) $criteria['fri'] = 'Friday';
					if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
					unset($criteria['value']);
					break;
				case 'timeofday':
					$from = DevblocksPlatform::importGPC($_REQUEST['timeofday_from'],'string','');
					$to = DevblocksPlatform::importGPC($_REQUEST['timeofday_to'],'string','');
					$criteria['from'] = $from;
					$criteria['to'] = $to;
					unset($criteria['value']);
					break;
				case 'type':
					break;
				case 'from':
					break;
				case 'tocc':
					break;
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = strtolower($header);
					break;
				case 'body':
					break;
				case 'body_encoding':
					break;
				case 'attachment':
					break;
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($rule,0,3)) {
						$field_id = intval(substr($rule,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'U': // URL
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
							case 'D': // dropdown
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
							case 'W': // worker
								$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$criteria['value'] = $out_array;
								break;
							case 'E': // date
								$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
								$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
								$criteria['from'] = $from;
								$criteria['to'] = $to;
								unset($criteria['value']);
								break;
							case 'N': // number
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
								$criteria['oper'] = $oper;
								$criteria['value'] = intval($value);
								break;
							case 'C': // checkbox
								$criteria['value'] = intval($value);
								break;
						}
						
					} elseif(isset($filter_criteria_exts[$rule])) { // Extensions
						// Save custom criteria properties
						try {
							$crit_ext = $filter_criteria_exts[$rule]->createInstance();
							/* @var $crit_ext Extension_MailFilterCriteria */
							$criteria = $crit_ext->saveConfig();
						} catch(Exception $e) {
							// print_r($e);
						}
					} else {
						continue;
					}
					
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		$filter_action_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.action', false);

		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				case 'stop':
					if(null != (@$do_stop = DevblocksPlatform::importGPC($_POST['do_stop'],'string',null))) {
						$act = $do_stop;
						switch($do_stop) {
							case 'nothing':
								$action = array();
								break;
							case 'blackhole':
								$action = array();
								break;
							case 'redirect':
								if(null != (@$to = DevblocksPlatform::importGPC($_POST['do_redirect'],'string',null)))
									$action = array(
										'to' => $to
									);
								break;
							case 'bounce':
								if(null != (@$msg = DevblocksPlatform::importGPC($_POST['do_bounce'],'string',null)))
									$action = array(
										'message' => $msg
									);
								break;
						}
					}
					break;
					
				default: // ignore invalids
					// Check action plugins
					if(isset($filter_action_exts[$act])) {
						// Save custom action properties
						try {
							$action_ext = $filter_action_exts[$act]->createInstance();
							$action = $action_ext->saveConfig();
							
						} catch(Exception $e) {
							// print_r($e);
						}
					} else {
						continue;
					}
					break;
			}
			
			$actions[$act] = $action;
		}
		
		if(!empty($criterion)) {
			if(empty($id))  {
				$fields = array(
					DAO_PreParseRule::NAME => $name,
					DAO_PreParseRule::CRITERIA_SER => serialize($criterion),
					DAO_PreParseRule::ACTIONS_SER => serialize($actions),
					DAO_PreParseRule::POS => 0,
					DAO_PreParseRule::IS_STICKY => intval($is_sticky),
				);
				$id = DAO_PreParseRule::create($fields);
			} else {
				$fields = array(
					DAO_PreParseRule::NAME => $name,
					DAO_PreParseRule::CRITERIA_SER => serialize($criterion),
					DAO_PreParseRule::ACTIONS_SER => serialize($actions),
					DAO_PreParseRule::IS_STICKY => intval($is_sticky),
				);
				DAO_PreParseRule::update($id, $fields);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','mail_filtering')));
	}	
}
