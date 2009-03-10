<?php
class ChGroupsPage extends CerberusPageExtension  {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	// [TODO] Refactor to isAuthorized
	function isVisible() {
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($worker)) {
			return false;
		} else {
			return true;
		}
	}
	
	function getActivity() {
	    return new Model_Activity('activity.default');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack); // groups
		
    	$groups = DAO_Group::getAll();
    	$tpl->assign('groups', $groups);
    	
    	@$team_id = array_shift($stack); // team_id

		// Only group managers and superusers can configure
		if(empty($team_id) || (!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)) {
			// do nothing (only show list)
			
		} else {
			$teams = DAO_Group::getAll();
			
			$team =& $teams[$team_id];
	    	$tpl->assign('team', $team);
	    	
    		@$tab_selected = array_shift($stack); // tab
	    	if(!empty($tab_selected))
	    		$tpl->assign('tab_selected', $tab_selected);
		}
    	
		$tpl->display('file:' . $this->_TPL_PATH . 'groups/index.tpl');
	}
	
	function showTabMailAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$team_categories = DAO_Bucket::getByTeam($group_id);
		$tpl->assign('categories', $team_categories);
	    
		$group_settings = DAO_GroupSettings::getSettings($group_id);
		$tpl->assign('group_settings', $group_settings);
		
		@$tpl->assign('group_spam_threshold', $group_settings[DAO_GroupSettings::SETTING_SPAM_THRESHOLD]);
		@$tpl->assign('group_spam_action', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION]);
		@$tpl->assign('group_spam_action_param', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM]);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/index.tpl');
	}
	
	function showTabInboxAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);

		$tpl->assign('group_id', $group_id);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$team_rules = DAO_GroupInboxFilter::getByGroupId($group_id);
		$tpl->assign('team_rules', $team_rules);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Custom Field Sources
		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
		$tpl->assign('source_manifests', $source_manifests);
		
		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/filters/index.tpl');
	}
	
	function saveTabInboxAction() {
	    @$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
	    @$sticky_ids = DevblocksPlatform::importGPC($_REQUEST['sticky_ids'],'array',array());
	    @$sticky_order = DevblocksPlatform::importGPC($_REQUEST['sticky_order'],'array',array());
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;
	    
	    // Deletes
	    if(!empty($group_id) && !empty($deletes)) {
	        DAO_GroupInboxFilter::delete($deletes);
	    }
	    
	    // Reordering
	    if(is_array($sticky_ids) && is_array($sticky_order))
	    foreach($sticky_ids as $idx => $id) {
	    	@$order = intval($sticky_order[$idx]);
	    	DAO_GroupInboxFilter::update($id, array(
	    		DAO_GroupInboxFilter::STICKY_ORDER => $order
	    	));
	    }
	    
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'inbox')));
   	}
   	
   	function showInboxFilterPanelAction() {
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
   		
		$tpl->assign('group_id', $group_id);
		
		if(null != ($filter = DAO_GroupInboxFilter::get($id))) {
			$tpl->assign('filter', $filter);
		}

		// Make sure we're allowed to change this group's setup
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$category_name_hash = DAO_Bucket::getCategoryNameHash();
		$tpl->assign('category_name_hash', $category_name_hash);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom Fields: Address
		$address_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('org_fields', $org_fields);
		
		// Custom Fields: Tickets
		$ticket_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('ticket_fields', $ticket_fields);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/filters/peek.tpl');
   	}
   	
   	function saveTabInboxAddAction() {
   		$translate = DevblocksPlatform::getTranslationService();
   		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
   		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;

	    /*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
		@$is_stackable = DevblocksPlatform::importGPC($_POST['is_stackable'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(empty($name))
			$name = $translate->_('mail.inbox_filter');
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
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
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
							case 'D': // dropdown
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
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
						list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($move_code);
						$action = array(
							'group_id' => intval($g_id),
							'bucket_id' => intval($b_id),
						);
					}
					break;
				// Assign to worker
				case 'assign':
					@$worker_id = DevblocksPlatform::importGPC($_REQUEST['do_assign'],'string',null);
					if(0 != strlen($worker_id))
						$action = array(
							'worker_id' => intval($worker_id)
						);
					break;
				// Spam training
				case 'spam':
					@$is_spam = DevblocksPlatform::importGPC($_REQUEST['do_spam'],'string',null);
					if(0 != strlen($is_spam))
						$action = array(
							'is_spam' => (!$is_spam?0:1)
						);
					break;
				// Set status
				case 'status':
					@$status = DevblocksPlatform::importGPC($_REQUEST['do_status'],'string',null);
					if(0 != strlen($status)) {
						$action = array(
							'is_waiting' => (3==$status?1:0), // explicit waiting
							'is_closed' => ((0==$status||3==$status)?0:1), // not open or waiting
							'is_deleted' => (2==$status?1:0), // explicit deleted
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
							
						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'D': // dropdown
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'M': // multi-dropdown
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
   			DAO_GroupInboxFilter::NAME => $name,
   			DAO_GroupInboxFilter::IS_STICKY => $is_sticky,
   			DAO_GroupInboxFilter::CRITERIA_SER => serialize($criterion),
   			DAO_GroupInboxFilter::ACTIONS_SER => serialize($actions),
   		);

   		// Only sticky filters can manual order and be stackable
   		if(!$is_sticky) {
   			$fields[DAO_GroupInboxFilter::STICKY_ORDER] = 0;
   			$fields[DAO_GroupInboxFilter::IS_STACKABLE] = 0;
   		} else { // is sticky
   			$fields[DAO_GroupInboxFilter::IS_STACKABLE] = $is_stackable;
   		}
   		
   		// Create
   		if(empty($id)) {
   			$fields[DAO_GroupInboxFilter::GROUP_ID] = $group_id;
   			$fields[DAO_GroupInboxFilter::POS] = 0;
	   		$id = DAO_GroupInboxFilter::create($fields);
	   		
	   	// Update
   		} else {
   			DAO_GroupInboxFilter::update($id, $fields);
   		}
   		
   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'inbox')));
   	}
	
	// Post
	function saveTabMailAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');

	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
	    	
		// Validators
		// [TODO] This could move into a Devblocks validation class later.
		$validator_email = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_LOCAL);
	    
	    //========== GENERAL
	    @$signature = DevblocksPlatform::importGPC($_REQUEST['signature'],'string','');
	    @$auto_reply_enabled = DevblocksPlatform::importGPC($_REQUEST['auto_reply_enabled'],'integer',0);
	    @$auto_reply = DevblocksPlatform::importGPC($_REQUEST['auto_reply'],'string','');
	    @$close_reply_enabled = DevblocksPlatform::importGPC($_REQUEST['close_reply_enabled'],'integer',0);
	    @$close_reply = DevblocksPlatform::importGPC($_REQUEST['close_reply'],'string','');
	    @$sender_address = DevblocksPlatform::importGPC($_REQUEST['sender_address'],'string','');
	    @$sender_personal = DevblocksPlatform::importGPC($_REQUEST['sender_personal'],'string','');
	    @$sender_personal_with_worker = DevblocksPlatform::importGPC($_REQUEST['sender_personal_with_worker'],'integer',0);
	    @$subject_has_mask = DevblocksPlatform::importGPC($_REQUEST['subject_has_mask'],'integer',0);
	    @$subject_prefix = DevblocksPlatform::importGPC($_REQUEST['subject_prefix'],'string','');
	    @$spam_threshold = DevblocksPlatform::importGPC($_REQUEST['spam_threshold'],'integer',80);
	    @$spam_action = DevblocksPlatform::importGPC($_REQUEST['spam_action'],'integer',0);
	    @$spam_moveto = DevblocksPlatform::importGPC($_REQUEST['spam_action_moveto'],'integer',0);

	    // Validate sender address
	    if(!$validator_email->isValid($sender_address)) {
	    	$sender_address = '';
	    }
	    
	    // [TODO] Move this into DAO_GroupSettings
	    DAO_Group::updateTeam($team_id, array(
	        DAO_Group::TEAM_SIGNATURE => $signature
	    ));
	    
	    // [TODO] Verify the sender address has been used in a 'To' header in the past
		// select count(header_value) from message_header where header_name = 'to' AND (header_value = 'sales@webgroupmedia.com' OR header_value = '<sales@webgroupmedia.com>');
		// DAO_MessageHeader::
	    
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_FROM, $sender_address);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL, $sender_personal);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL_WITH_WORKER, $sender_personal_with_worker);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_THRESHOLD, $spam_threshold);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_ACTION, $spam_action);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM, $spam_moveto);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_AUTO_REPLY_ENABLED, $auto_reply_enabled);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_AUTO_REPLY, $auto_reply);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_CLOSE_REPLY_ENABLED, $close_reply_enabled);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_CLOSE_REPLY, $close_reply);
	       
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id)));
	}
	
	function showTabMembersAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$members = DAO_Group::getTeamMembers($group_id);
	    $tpl->assign('members', $members);
	    
		$workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/members.tpl');
	}
	
	function saveTabMembersAction() {
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
		@$worker_levels = DevblocksPlatform::importGPC($_REQUEST['worker_levels'],'array',array());
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    @$members = DAO_Group::getTeamMembers($team_id);
	    
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
	    
	    if(is_array($worker_ids) && !empty($worker_ids))
	    foreach($worker_ids as $idx => $worker_id) {
	    	@$level = $worker_levels[$idx];
	    	if(isset($members[$worker_id]) && empty($level)) {
	    		DAO_Group::unsetTeamMember($team_id, $worker_id);
	    	} elseif(!empty($level)) { // member|manager
				 DAO_Group::setTeamMember($team_id, $worker_id, (1==$level)?false:true);
	    	}
	    }
	    
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id,'members')));
	}
	
	function showTabBucketsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);

		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$team_categories = DAO_Bucket::getByTeam($group_id);
		$tpl->assign('categories', $team_categories);
		
		$inbox_is_assignable = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_INBOX_IS_ASSIGNABLE, 1);
		$tpl->assign('inbox_is_assignable', $inbox_is_assignable);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/buckets.tpl');
	}
	
	function saveTabBucketsAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
	    
	    // Inbox assignable
	    @$inbox_assignable = DevblocksPlatform::importGPC($_REQUEST['inbox_assignable'],'integer',0);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_INBOX_IS_ASSIGNABLE, intval($inbox_assignable));
	    	
	    //========== BUCKETS   
	    @$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array');
	    @$add_str = DevblocksPlatform::importGPC($_REQUEST['add'],'string');
	    @$pos = DevblocksPlatform::importGPC($_REQUEST['pos'],'array');
	    @$names = DevblocksPlatform::importGPC($_REQUEST['names'],'array');
	    @$assignables = DevblocksPlatform::importGPC($_REQUEST['is_assignable'],'array');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array');
	    
	    // Updates
	    if(!empty($ids)) {
		    $cats = DAO_Bucket::getList($ids);
		    foreach($ids as $idx => $id) {
		        @$cat = $cats[$id];
		        if(is_object($cat)) {
		        	$is_assignable = (false === array_search($id, $assignables)) ? 0 : 1;
		        	
		        	$fields = array(
		        		DAO_Bucket::NAME => $names[$idx],
		        		DAO_Bucket::POS => intval($pos[$idx]),
		        		DAO_Bucket::IS_ASSIGNABLE => intval($is_assignable),
		        	);
		            DAO_Bucket::update($id, $fields);
		        }
		    }
	    }
	    
	    // Adds: Sort and insert team categories
	    $categories = DevblocksPlatform::parseCrlfString($add_str);

	    if(is_array($categories))
	    foreach($categories as $category) {
	        // [TODO] Dupe checking
	        $cat_id = DAO_Bucket::create($category, $team_id);
	    }
	    
	    if(!empty($deletes)) {
	        DAO_Bucket::delete(array_values($deletes));
	    }
	        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id,'buckets')));
	}
	
	function showTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$group_fields = DAO_CustomField::getBySourceAndGroupId(ChCustomFieldSource_Ticket::ID, $group_id); 
		$tpl->assign('group_fields', $group_fields);
                    
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/fields.tpl');
	}
	
	// Post
	function saveTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;
	    	
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array',array());
		@$orders = DevblocksPlatform::importGPC($_POST['orders'],'array',array());
		@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
		@$allow_delete = DevblocksPlatform::importGPC($_POST['allow_delete'],'integer',0);
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		
		if(!empty($ids))
		foreach($ids as $idx => $id) {
			@$name = $names[$idx];
			@$order = intval($orders[$idx]);
			@$option = $options[$idx];
			@$delete = (false !== array_search($id,$deletes) ? 1 : 0);
			
			if($allow_delete && $delete) {
				DAO_CustomField::delete($id);
				
			} else {
				$fields = array(
					DAO_CustomField::NAME => $name, 
					DAO_CustomField::POS => $order,
					DAO_CustomField::OPTIONS => !is_null($option) ? $option : '',
				);
				DAO_CustomField::update($id, $fields);
			}
		}
		
		// Add custom field
		@$add_name = DevblocksPlatform::importGPC($_POST['add_name'],'string','');
		@$add_type = DevblocksPlatform::importGPC($_POST['add_type'],'string','');
		@$add_options = DevblocksPlatform::importGPC($_POST['add_options'],'string','');
		
		if(!empty($add_name) && !empty($add_type)) {
			$fields = array(
				DAO_CustomField::NAME => $add_name,
				DAO_CustomField::TYPE => $add_type,
				DAO_CustomField::GROUP_ID => $group_id,
				DAO_CustomField::SOURCE_EXTENSION => ChCustomFieldSource_Ticket::ID,
				DAO_CustomField::OPTIONS => $add_options,
			);
			$id = DAO_CustomField::create($fields);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('groups',$group_id,'fields')));
	}
	
	function showGroupPanelAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($group_id) && null != ($group = DAO_Group::getTeam($group_id))) {
			$tpl->assign('group', $group);
		}
		
		$tpl->display('file:' . $tpl_path . 'groups/rpc/peek.tpl');
	}
	
	function saveGroupPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');

		$fields = array(
			DAO_Group::TEAM_NAME => $name			
		);
		
		// [TODO] Delete
		
		if(empty($group_id)) { // new
			$group_id = DAO_Group::create($fields);
			
		} else { // update
			DAO_Group::update($group_id, $fields);
			
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		exit;
	}
};
