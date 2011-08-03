<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
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

abstract class Extension_CrmOpportunityToolbarItem extends DevblocksExtension {
	function render(Model_CrmOpportunity $opp) { }
};

abstract class Extension_CrmOpportunityTab extends DevblocksExtension {
	const POINT = 'cerberusweb.crm.opportunity.tab';
	
	function showTab() {}
	function saveTab() {}
};

if (class_exists('Extension_ActivityTab')):
class CrmOppsActivityTab extends Extension_ActivityTab {
	const EXTENSION_ID = 'crm.activity.tab.opps';
	const VIEW_ACTIVITY_OPPS = 'activity_opps';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Read original request
		@$request_path = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		@$stack =  explode('/', $request_path);
		@array_shift($stack); // activity
		@array_shift($stack); // opps
		
		switch(@array_shift($stack)) {
			case 'import':
				if(!$active_worker->hasPriv('crm.opp.actions.import'))
					break;

				switch(@array_shift($stack)) {
					case 'step2':
						// Load first row headings
						$csv_file = $visit->get('crm.import.last.csv','');
						$fp = fopen($csv_file, "rt");
						if($fp) {
							$parts = fgetcsv($fp, 8192, ',', '"');
							$tpl->assign('parts', $parts);
						}
						@fclose($fp);

						$fields = array(
							'name' => $translate->_('crm.opportunity.name'),
							'email' => $translate->_('crm.opportunity.email_address'),
							'created_date' => $translate->_('crm.opportunity.created_date'),
							'updated_date' => $translate->_('crm.opportunity.updated_date'),
							'closed_date' => $translate->_('crm.opportunity.closed_date'),
							'is_won' => $translate->_('crm.opportunity.is_won'),
							'is_closed' => $translate->_('crm.opportunity.is_closed'),
//							'worker' => $translate->_('crm.opportunity.worker_id'),
							'amount' => $translate->_('crm.opportunity.amount'),
						);
						$tpl->assign('fields',$fields);
						
						$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);
						$tpl->assign('custom_fields', $custom_fields);
						
						$workers = DAO_Worker::getAllActive();
						$tpl->assign('workers', $workers);
						
						$tpl->display('devblocks:cerberusweb.crm::crm/opps/activity_tab/import/mapping.tpl');
						return;
						break;
						
				} // import:switch
				break;
		}
			
		// Index
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_CrmOpportunity';
		$defaults->id = self::VIEW_ACTIVITY_OPPS;
		$defaults->name = $translate->_('crm.tab.title');
		$defaults->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		
		$view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_OPPS, $defaults);
		
		$quick_search_type = $visit->get('crm.opps.quick_search_type');
		$tpl->assign('quick_search_type', $quick_search_type);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/activity_tab/index.tpl');		
	}
}
endif;

class CrmPage extends CerberusPageExtension {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // crm
		
		$module = array_shift($stack); // opps
		
		switch($module) {
			default:
			case 'opps':
				@$opp_id = intval(array_shift($stack));
				if(null == ($opp = DAO_CrmOpportunity::get($opp_id))) {
					break;
				}
				$tpl->assign('opp', $opp);	/* @var $opp Model_CrmOpportunity */					

				// Remember the last tab/URL
				if(null == (@$selected_tab = $stack[0])) {
					$selected_tab = $visit->get(Extension_CrmOpportunityTab::POINT, '');
				}
				$tpl->assign('selected_tab', $selected_tab);

				// Custom fields
				
				$custom_fields = DAO_CustomField::getAll();
				$tpl->assign('custom_fields', $custom_fields);
				
				// Properties
				
				$properties = array();
				
				$properties['status'] = array(
					'label' => ucfirst($translate->_('common.status')),
					'type' => null,
					'is_closed' => $opp->is_closed,
					'is_won' => $opp->is_won,
				);
				
				if(!empty($opp->primary_email_id)) {
					if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
						$properties['lead'] = array(
							'label' => ucfirst($translate->_('common.email')),
							'type' => null,
							'address' => $address,
						);
					}
				}
				
				if(!empty($opp->is_closed))
					if(!empty($opp->closed_date))
						$properties['closed_date'] = array(
							'label' => ucfirst($translate->_('crm.opportunity.closed_date')),
							'type' => Model_CustomField::TYPE_DATE,
							'value' => $opp->closed_date,
						);
					
				if(!empty($opp->amount))
					$properties['amount'] = array(
						'label' => ucfirst($translate->_('crm.opportunity.amount')),
						'type' => Model_CustomField::TYPE_NUMBER,
						'value' => $opp->amount,
					);
					
				$properties['created_date'] = array(
					'label' => ucfirst($translate->_('common.created')),
					'type' => Model_CustomField::TYPE_DATE,
					'value' => $opp->created_date,
				);
				
				$properties['updated_date'] = array(
					'label' => ucfirst($translate->_('common.updated')),
					'type' => Model_CustomField::TYPE_DATE,
					'value' => $opp->updated_date,
				);
				
				@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $opp->id)) or array();
		
				foreach($custom_fields as $cf_id => $cfield) {
					if(!isset($values[$cf_id]))
						continue;
						
					$properties['cf_' . $cf_id] = array(
						'label' => $cfield->name,
						'type' => $cfield->type,
						'value' => $values[$cf_id],
					);
				}
				
				$tpl->assign('properties', $properties);

				// Workers
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				// Macros
				$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.crm.opportunity');
				$tpl->assign('macros', $macros);
				
				$tpl->display('devblocks:cerberusweb.crm::crm/opps/display/index.tpl');
				break;
		}
	}
	
	// Ajax
	function showOppTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_CrmOpportunityTab) {
				$visit->set(Extension_CrmOpportunityTab::POINT, $inst->manifest->params['uri']);
				$inst->showTab();
		}
	}
	
	function showOppPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('email', $email);
		
		// Handle context links ([TODO] as an optional array)
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		if(!empty($opp_id) && null != ($opp = DAO_CrmOpportunity::get($opp_id))) {
			$tpl->assign('opp', $opp);
			
			if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
				$tpl->assign('address', $address);
			}
		}
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($opp_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
			if(isset($custom_field_values[$opp->id]))
				$tpl->assign('custom_field_values', $custom_field_values[$opp->id]);
		}

		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/rpc/peek.tpl');
	}
	
	function saveOppPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$amount_dollars = DevblocksPlatform::importGPC($_REQUEST['amount'],'string','0');
		@$amount_cents = DevblocksPlatform::importGPC($_REQUEST['amount_cents'],'integer',0);
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$created_date_str = DevblocksPlatform::importGPC($_REQUEST['created_date'],'string','');
		@$closed_date_str = DevblocksPlatform::importGPC($_REQUEST['closed_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		// State
		$is_closed = (0==$status) ? 0 : 1;
		$is_won = (1==$status) ? 1 : 0;
		
		// Strip commas and decimals and put together the "dollars+cents"
		$amount = intval(str_replace(array(',','.'),'',$amount_dollars)).'.'.number_format($amount_cents,0,'','');
		
		// Dates
		if(false === ($created_date = strtotime($created_date_str)))
			$created_date = time();
			
		if(false === ($closed_date = strtotime($closed_date_str)))
			$closed_date = ($is_closed) ? time() : 0;

		if(!$is_closed)
			$closed_date = 0;
			
		// Worker
		$active_worker = CerberusApplication::getActiveWorker();

		// Save
		if($do_delete) {
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id)) && $active_worker->hasPriv('crm.opp.actions.create')) {
				DAO_CrmOpportunity::delete($opp_id);
				$opp_id = null;
			}
			
		} elseif(empty($opp_id)) {
			// Check privs
			if(!$active_worker->hasPriv('crm.opp.actions.create'))
				return;
			
			// One opportunity per provided e-mail address
			if(null == ($address = DAO_Address::lookupAddress($email, true)))
				return;
				
			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
				DAO_CrmOpportunity::AMOUNT => $amount,
				DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
				DAO_CrmOpportunity::CREATED_DATE => intval($created_date),
				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
				DAO_CrmOpportunity::IS_CLOSED => $is_closed,
				DAO_CrmOpportunity::IS_WON => $is_won,
			);
			$opp_id = DAO_CrmOpportunity::create($fields);
			
			@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
			if($is_watcher)
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $active_worker->id);
			
			// Context Link (if given)
			@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
			@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
			if(!empty($opp_id) && !empty($context) && !empty($context_id)) {
				DAO_ContextLink::setLink(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $context, $context_id);
			}
			
		} else {
			if(empty($opp_id))
				return;
			
			if(null == ($address = DAO_Address::lookupAddress($email, true)))
				return;

			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
				DAO_CrmOpportunity::AMOUNT => $amount,
				DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
				DAO_CrmOpportunity::CREATED_DATE => intval($created_date),
				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
				DAO_CrmOpportunity::IS_CLOSED => $is_closed,
				DAO_CrmOpportunity::IS_WON => $is_won,
			);
			
			// Check privs
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id)) && $active_worker->hasPriv('crm.opp.actions.create')) {
				DAO_CrmOpportunity::update($opp_id, $fields);
			}
		}
		
		if(!empty($opp_id)) {
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $field_ids);
			
			// If we're adding a comment
			if(!empty($comment)) {
				@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
					DAO_Comment::CONTEXT_ID => $opp_id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showOppBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('opp_ids', implode(',', $id_list));
	    }
		
	    // Workers
	    $workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
	    
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.crm.opportunity');
		$tpl->assign('macros', $macros);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/bulk.tpl');
	}
	
	function doOppBulkUpdateAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    $ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id); /* @var $view View_CrmOpportunity */
		
		// Opp fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));
		@$closed_date = trim(DevblocksPlatform::importGPC($_POST['closed_date'],'string',''));
		@$worker_id = trim(DevblocksPlatform::importGPC($_POST['worker_id'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		
		$do = array();
		
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
		// Do: Closed Date
		if(0 != strlen($closed_date))
			@$do['closed_date'] = intval(strtotime($closed_date));
		// Do: Worker
		if(0 != strlen($worker_id))
			$do['worker_id'] = $worker_id;

		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
			);
		}
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
			
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('crm.opp.view.actions.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			@$broadcast_is_closed = DevblocksPlatform::importGPC($_REQUEST['broadcast_next_is_closed'],'integer',0);
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'is_queued' => $broadcast_is_queued,
					'next_is_closed' => $broadcast_is_closed,
					'group_id' => $broadcast_group_id,
					'worker_id' => $active_worker->id,
				);
			}
		}
		
		switch($filter) {
			// Checked rows
			case 'checks':
			    @$opp_ids_str = DevblocksPlatform::importGPC($_REQUEST['opp_ids'],'string');
		        $ids = DevblocksPlatform::parseCsvString($opp_ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
	function doOppBulkUpdateBroadcastTestAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$view = C4_AbstractViewLoader::getView($view_id);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if($active_worker->hasPriv('crm.opp.view.actions.broadcast')) {
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);

			@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
			@$opp_ids = DevblocksPlatform::importGPC($_REQUEST['opp_ids'],'string','');
			
			// Filter to checked
			if('checks' == $filter && !empty($opp_ids)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ID,'in',explode(',', $opp_ids)));
			}
			
			$results = $view->getDataSample(1);
			
			if(empty($results)) {
				$success = false;
				$output = "There aren't any rows in this view!";
				
			} else {
				@$opp = DAO_CrmOpportunity::get(current($results));
				
				// Try to build the template
				CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $opp, $token_labels, $token_values);

				if(empty($broadcast_subject)) {
					$success = false;
					$output = "Subject is blank.";
				
				} else {
					$template = "Subject: $broadcast_subject\n\n$broadcast_message";
					
					if(false === ($out = $tpl_builder->build($template, $token_values))) {
						// If we failed, show the compile errors
						$errors = $tpl_builder->getErrors();
						$success= false;
						$output = @array_shift($errors);
					} else {
						// If successful, return the parsed template
						$success = true;
						$output = $out;
					}
				}
			}
			
			$tpl->assign('success', $success);
			$tpl->assign('output', $output);
			
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
		}
	}	
	
	function doQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $translate = DevblocksPlatform::getTranslationService();
		
        if(null == ($searchView = C4_AbstractViewLoader::getView(CrmOppsActivityTab::VIEW_ACTIVITY_OPPS))) {
        	$searchView = new View_CrmOpportunity();
        	$searchView->id = CrmOppsActivityTab::VIEW_ACTIVITY_OPPS;
        	$searchView->name = $translate->_('common.search_results');
        	C4_AbstractViewLoader::setView($searchView->id, $searchView);
        }
		
		$visit->set('crm.opps.quick_search_type', $type);
		
        $params = array();
        
        switch($type) {
            case "title":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_CrmOpportunity::NAME] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::NAME,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
            case "email":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_CrmOpportunity::EMAIL_ADDRESS] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::EMAIL_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
            case "org":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_CrmOpportunity::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_NAME,DevblocksSearchCriteria::OPER_LIKE,$query);      
                break;
                
            case "comments_all":
            	$params[SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($query,'all'));               
                break;
                
            case "comments_phrase":
            	$params[SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($query,'phrase'));               
                break;
        }
        
        $searchView->addParams($params, true);
        $searchView->renderPage = 0;
        $searchView->renderSortBy = null;
        
        C4_AbstractViewLoader::setView($searchView->id,$searchView);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('activity','opps')));
	}
	
	// Ajax
	function showImportPanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('crm.opp.actions.import'))
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/activity_tab/import/panel.tpl');		
	}
	
	// Post
	function parseUploadAction() {
		@$csv_file = $_FILES['csv_file'];

		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('crm.opp.actions.import'))
			return;

		if(!is_array($csv_file) || !isset($csv_file['tmp_name']) || empty($csv_file['tmp_name'])) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('activity','opps')));
			return;
		}
		
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$filename = basename($csv_file['tmp_name']);
		$newfilename = APP_TEMP_PATH . '/' . $filename;
		
		if(!rename($csv_file['tmp_name'], $newfilename)) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('activity','opps')));
			return; // [TODO] Throw error
		}
		
		$visit->set('crm.import.last.csv', $newfilename);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('activity','opps','import','step2')));
	}
	
	// Post
	function doImportAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('crm.opp.actions.import'))
			return;
		
		@$pos = DevblocksPlatform::importGPC($_REQUEST['pos'],'array',array());
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		@$sync_dupes = DevblocksPlatform::importGPC($_REQUEST['sync_dupes'],'array',array());
		@$include_first = DevblocksPlatform::importGPC($_REQUEST['include_first'],'integer',0);
		@$is_blank_unset = DevblocksPlatform::importGPC($_REQUEST['is_blank_unset'],'integer',0);
		@$opt_assign = DevblocksPlatform::importGPC($_REQUEST['opt_assign'],'integer',0);
		@$opt_assign_worker_id = DevblocksPlatform::importGPC($_REQUEST['opt_assign_worker_id'],'integer',0);
		
		$visit = CerberusApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();
		
		$workers = DAO_Worker::getAllActive();
		
		$csv_file = $visit->get('crm.import.last.csv','');
		
		$fp = fopen($csv_file, "rt");
		if(!$fp) return;

		// [JAS]: Do we need to consume a first row of headings?
		if(!$include_first)
			@fgetcsv($fp, 8192, ',', '"');
		
		while(!feof($fp)) {
			$parts = fgetcsv($fp, 8192, ',', '"');
			
			if(empty($parts) || (1==count($parts) && is_null($parts[0])))
				continue;
			
			$fields = array();
			$custom_fields = array();
			$sync_fields = array();
			
			foreach($pos as $idx => $p) {
				$key = $field[$idx];
				$val = $parts[$idx];
				
				// Special handling
				if(!empty($key)) {
					switch($key) {
						case 'amount':
							if(0 != strlen($val) && is_numeric($val)) {
								@$val = floatval($val);
							} else {
								unset($key);
							}
							break;
						// Translate e-mail address to ID
						case 'email':
							if(null != ($addy = CerberusApplication::hashLookupAddress($val,true))) {
								$key = 'primary_email_id';
								$val = $addy->id;
							} else {
								unset($key);
							}
							break;
						
						// Bools
						case 'is_won':
						case 'is_closed':
							if(0 != strlen($val)) {
								@$val = !empty($val) ? 1 : 0;
							} else {
								unset($key);
							}
							break;
													
						// Dates
						case 'created_date':
						case 'updated_date':
						case 'closed_date':
							if(0 != strlen($val)) {
								@$val = !is_numeric($val) ? strtotime($val) : $val;
							} else {
								unset($key);
							}
							break;

						// Worker by name							
						case 'worker':
							unset($key);
							if(is_array($workers))
							foreach($workers as $worker_id=>$worker)
								if(0==strcasecmp($val,$worker->getName())) {
									$key = 'worker_id';
									$val = $worker_id;
								}
							break;
							
					}

					if(!isset($key))
						continue;

					// Custom fields
					if('cf_' == substr($key,0,3)) {
						$custom_fields[substr($key,3)] = $val;
					} elseif(!empty($key)) {
						$fields[$key] = $val;
					}
					
					// Find dupe combos
					if(in_array($idx,$sync_dupes)) {
						$search_field = '';
						$search_val = '';
						
						switch($key) {
							case 'primary_email_id':
								$search_field = SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID;
								$search_val = intval($val);
								break;
							case 'title':
								$search_field = SearchFields_CrmOpportunity::NAME;
								$search_val = $val;
								break;
							case 'amount':
								$search_field = SearchFields_CrmOpportunity::AMOUNT;
								$search_val = floatval($val);
								break;
							case 'is_won':
								$search_field = SearchFields_CrmOpportunity::IS_WON;
								$search_val = intval($val);
								break;
							case 'is_closed':
								$search_field = SearchFields_CrmOpportunity::IS_CLOSED;
								$search_val = intval($val);
								break;
							case 'created_date':
								$search_field = SearchFields_CrmOpportunity::CREATED_DATE;
								$search_val = intval($val);
								break;
							case 'updated_date':
								$search_field = SearchFields_CrmOpportunity::UPDATED_DATE;
								$search_val = intval($val);
								break;
							case 'closed_date':
								$search_field = SearchFields_CrmOpportunity::CLOSED_DATE;
								$search_val = intval($val);
								break;
//							case 'worker_id':
//								$search_field = SearchFields_CrmOpportunity::WORKER_ID;
//								$search_val = intval($val);
//								break;
							default:
								// Custom field dupe
								if('cf_'==substr($key,0,3)) {
									$search_field = $key;
									// [TODO] Need to format this for proper custom fields
									$search_val = $val;
								}
								break;
						}
						
						if(!empty($search_field) && !empty($search_val))
							$sync_fields[$search_field] = new DevblocksSearchCriteria($search_field,'=',$search_val);
					}
				}
			} // end foreach($pos)
			
			// Dupe checking
			if(!empty($fields) && !empty($sync_fields)) {
				list($dupes,$null) = DAO_CrmOpportunity::search(
					array(),
					$sync_fields,
					1, // only need 1 to be a dupe
					0,
					null,
					false,
					false
				);
			}
			
			if(!empty($fields)) {
				if(isset($fields['primary_email_id'])) {
					// Make sure a minimum amount of fields are provided
					if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
						$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();
					
//					if($opt_assign && !isset($fields[DAO_CrmOpportunity::WORKER_ID]))
//						$fields[DAO_CrmOpportunity::WORKER_ID] = $opt_assign_worker_id;
					
					if(empty($dupes)) {
						// [TODO] Provide an import prefix for blank names
						if(!isset($fields[DAO_CrmOpportunity::NAME]) && isset($addy))
							$fields[DAO_CrmOpportunity::NAME] = $addy->email;
						if(!isset($fields[DAO_CrmOpportunity::CREATED_DATE]))
							$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
						$id = DAO_CrmOpportunity::create($fields);
						
					} else {
						$id = key($dupes);
						DAO_CrmOpportunity::update($id, $fields);
					}
				}
			}
			
			if(!empty($custom_fields) && !empty($id)) {
				// Format (typecast) and set the custom field types
				$context_ext_id = CerberusContexts::CONTEXT_OPPORTUNITY;
				DAO_CustomFieldValue::formatAndSetFieldValues($context_ext_id, $id, $custom_fields, $is_blank_unset, true, true);
			}
			
		}
		
		@unlink($csv_file); // nuke the imported file
		
		$visit->set('crm.import.last.csv',null);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('activity','opps')));
	}
	
	function viewOppsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=activity&tab=opps', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_CrmOpportunity::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=crm&tab=opps&id=%d", $row[SearchFields_CrmOpportunity::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};



class CrmEventListener extends DevblocksEventListenerExtension {
    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'cron.maint':
            	DAO_CrmOpportunity::maint();
            	break;
        }
    }
};

class CrmOrgOppTab extends Extension_OrgTab {
	function showTab() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		$org = DAO_ContactOrg::get($org_id);
		$tpl->assign('org_id', $org_id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_CrmOpportunity';
		$defaults->id = 'org_opps';
		$defaults->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::AMOUNT,
			SearchFields_CrmOpportunity::UPDATED_DATE,
		);
		
		$view = C4_AbstractViewLoader::getView('org_opps', $defaults);
		
		$view->name = "Org: " . $org->name;
		$view->addParams(array(
			SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org_id) 
		), true);

		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/org/tab.tpl');
	}
	
	function saveTab() {
	}
};

class CrmTicketOppTab extends Extension_TicketTab {
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		
		if(null == ($view = C4_AbstractViewLoader::getView('ticket_opps'))) {
			$view = new View_CrmOpportunity();
			$view->id = 'ticket_opps';
		}

		$view->name = sprintf("Opportunities: %s recipient(s)", count($requesters));
		$view->addParams(array(
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,'in',array_keys($requesters)), 
		), true);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/ticket/tab.tpl');
	}
	
	function saveTab() {
	}
};
