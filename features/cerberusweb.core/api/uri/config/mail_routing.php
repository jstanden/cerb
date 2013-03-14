<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupMailRouting extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'mail_routing');
		
		$rules = DAO_MailToGroupRule::getWhere();
		$tpl->assign('rules', $rules);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

//		$buckets = DAO_Bucket::getAll();
//		$tpl->assign('buckets', $buckets);
		
		// Custom Field Sources
		$tpl->assign('context_manifests', Extension_DevblocksContext::getAll());
		
		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_routing/index.tpl');
	}
	
	// Form Submit
	function saveRoutingAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
		@$sticky_ids = DevblocksPlatform::importGPC($_REQUEST['sticky_ids'],'array',array());
		@$sticky_order = DevblocksPlatform::importGPC($_REQUEST['sticky_order'],'array',array());
		
		@$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->is_superuser)
			return;
		
		// Deletes
		if(!empty($deletes)) {
			DAO_MailToGroupRule::delete($deletes);
		}
		
		// Reordering
		if(is_array($sticky_ids) && is_array($sticky_order))
		foreach($sticky_ids as $idx => $id) {
			@$order = intval($sticky_order[$idx]);
			DAO_MailToGroupRule::update($id, array (
				DAO_MailToGroupRule::STICKY_ORDER => $order
			));
		}
		
		// Default group
		@$default_group_id = DevblocksPlatform::importGPC($_REQUEST['default_group_id'],'integer','0');
		DAO_Group::setDefaultGroup($default_group_id);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail_routing')));
	}
	
   	function showMailRoutingRulePanelAction() {
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('group_id', $group_id);
		
		if(null != ($rule = DAO_MailToGroupRule::get($id))) {
			$tpl->assign('rule', $rule);
		}

		// Make sure we're allowed to change this group's setup
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom Fields: Address
		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$tpl->assign('org_fields', $org_fields);

		// Custom Fields: Ticket
		$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('ticket_fields', $ticket_fields);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_routing/peek.tpl');
   	}
   	
   	function saveMailRoutingRuleAddAction() {
   		$translate = DevblocksPlatform::getTranslationService();
   		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		
		@$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->is_superuser)
			return;

		/*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
//		@$is_stackable = DevblocksPlatform::importGPC($_POST['is_stackable'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(empty($name))
			$name = $translate->_('Mail Routing Rule');
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNum($rule, '\_\.\-');
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
				case 'subject':
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
//				case 'attachment':
//					break;
				default: // ignore invalids // [TODO] Very redundant
					// Custom fields
					if("cf_" == substr($rule,0,3)) {
						$field_id = intval(substr($rule,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'U': // URL
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
							case 'D': // dropdown
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
						
					} else {
						continue;
					}
					
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				// Move group/bucket
				case 'move':
					@$move_code = DevblocksPlatform::importGPC($_REQUEST['do_move'],'string',null);
					if(0 != strlen($move_code)) {
						list($g_id, $b_id) = CerberusApplication::translateGroupBucketCode($move_code);
						$action = array(
							'group_id' => intval($g_id),
							'bucket_id' => intval($b_id),
						);
					}
					break;
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($act,0,3)) {
						$field_id = intval(substr($act,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						$action = array();
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'U': // URL
							case 'D': // dropdown
							case 'W': // worker
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'X': // multi-checkbox
								$in_array = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$action['value'] = $out_array;
								break;
							case 'E': // date
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'N': // number
							case 'C': // checkbox
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					break;
			}
			
			$actions[$act] = $action;
		}

   		$fields = array(
   			DAO_MailToGroupRule::NAME => $name,
   			DAO_MailToGroupRule::IS_STICKY => $is_sticky,
   			DAO_MailToGroupRule::CRITERIA_SER => serialize($criterion),
   			DAO_MailToGroupRule::ACTIONS_SER => serialize($actions),
   		);

   		// Only sticky filters can manual order and be stackable
   		if(!$is_sticky) {
   			$fields[DAO_MailToGroupRule::STICKY_ORDER] = 0;
//   			$fields[DAO_MailToGroupRule::IS_STACKABLE] = 0;
//   		} else { // is sticky
//   			$fields[DAO_MailToGroupRule::IS_STACKABLE] = $is_stackable;
   		}
   		
   		// Create
   		if(empty($id)) {
   			$fields[DAO_MailToGroupRule::POS] = 0;
	   		$id = DAO_MailToGroupRule::create($fields);
	   		
	   	// Update
   		} else {
   			DAO_MailToGroupRule::update($id, $fields);
   		}
   		
   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','mail_routing')));
   	}
}