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
				$tpl->assign('opp', $opp);						

				// Remember the last tab/URL
				if(null == (@$selected_tab = $stack[0])) {
					$selected_tab = $visit->get(Extension_CrmOpportunityTab::POINT, '');
				}
				$tpl->assign('selected_tab', $selected_tab);
				
				$address = DAO_Address::get($opp->primary_email_id);
				$tpl->assign('address', $address);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
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
		
		// Workers
		$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
		$tpl->assign('context_workers', $context_workers);
		
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
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id))
			 && $active_worker->hasPriv('crm.opp.actions.create')
			)
				DAO_CrmOpportunity::delete($opp_id);
			
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
			
			// Context Link (if given)
			@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
			@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
			if(!empty($opp_id) && !empty($context) && !empty($context_id)) {
				DAO_ContextLink::setLink(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $context, $context_id);
			}
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $field_ids);
			
			// If we're adding a first comment
			if(!empty($comment)) {
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
					DAO_Comment::CONTEXT_ID => $opp_id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
				);
				$comment_id = DAO_Comment::create($fields);
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
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id))
				&& $active_worker->hasPriv('crm.opp.actions.create')
			) {
				DAO_CrmOpportunity::update($opp_id, $fields);
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $field_ids);
			}
		}
		
		// Workers
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		CerberusContexts::setWorkers(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $worker_ids);
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showOppMailTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		// Remember the selected tab
		$visit->set(Extension_CrmOpportunityTab::POINT, 'mail');
		
		// Opp
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		// Recall the history scope
		$scope = $visit->get('crm.opps.history.scope', '');

		// Addy
		$address = DAO_Address::get($opp->primary_email_id);
		$tpl->assign('address', $address);

		// Addy->Org
		if(!empty($address->contact_org_id)) {
			if(null != ($contact_org = DAO_ContactOrg::get($address->contact_org_id)))
				$tpl->assign('contact_org', $contact_org);
		}
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = 'opp_tickets';
		$defaults->name = '';
		$defaults->renderPage = 0;
		$defaults->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
		);
		
		$view = C4_AbstractViewLoader::getView('opp_tickets', $defaults);

		// Sanitize scope options
		if('org'==$scope && empty($contact_org))
			$scope = '';
		if('domain'==$scope) {
			$email_parts = explode('@', $address->email);
			if(!is_array($email_parts) || 2 != count($email_parts))
				$scope = '';
		}

		switch($scope) {
			case 'org':
				$view->addParams(array(
					SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID,'=',$address->contact_org_id),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				), true);
				$view->name = ucwords($translate->_('contact_org.name')) . ": " . $contact_org->name;
				break;
				
			case 'domain':
				$view->addParams(array(
					SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'like','*@'.$email_parts[1]),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				), true);
				$view->name = ucwords($translate->_('common.email')) . ": *@" . $email_parts[1];
				break;
				
			default:
			case 'email':
				$scope = 'email';
				$view->addParams(array(
					SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array($opp->primary_email_id)),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				), true);
				$view->name = ucwords($translate->_('common.email')) . ": " . $address->email;
				break;
		}
		
		$tpl->assign('scope', $scope);
		
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/display/tabs/mail.tpl');
	}
	
	function doOppHistoryScopeAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','');
		
		$visit = CerberusApplication::getVisit();

		$visit->set('crm.opps.history.scope', $scope);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps',$opp_id,'mail')));
	}
	
	function showOppBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

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
			
		// Owners
		$owner_options = array();
		
		@$owner_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_add_ids'],'array',array());
		if(!empty($owner_add_ids))
			$owner_params['add'] = $owner_add_ids;
			
		@$owner_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_remove_ids'],'array',array());
		if(!empty($owner_remove_ids))
			$owner_params['remove'] = $owner_remove_ids;
		
		if(!empty($owner_params))
			$do['owner'] = $owner_params;
			
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=activity&tab=opps', true),
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
					'url' => $url_writer->write(sprintf("c=crm&tab=opps&id=%d", $row[SearchFields_CrmOpportunity::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};

class DAO_CrmOpportunity extends C4_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const AMOUNT = 'amount';
	const PRIMARY_EMAIL_ID = 'primary_email_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const CLOSED_DATE = 'closed_date';
	const IS_WON = 'is_won';
	const IS_CLOSED = 'is_closed';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO crm_opportunity () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		// New opportunity
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'opportunity.create',
                array(
                    'opp_id' => $id,
                	'fields' => $fields,
                )
            )
	    );
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'crm_opportunity', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('crm_opportunity', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmOpportunity[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, amount, primary_email_id, created_date, updated_date, closed_date, is_won, is_closed ".
			"FROM crm_opportunity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmOpportunity	 */
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
	 * @param resource $rs
	 * @return Model_CrmOpportunity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CrmOpportunity();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->amount = doubleval($row['amount']);
			$object->primary_email_id = intval($row['primary_email_id']);
			$object->created_date = $row['created_date'];
			$object->updated_date = $row['updated_date'];
			$object->closed_date = $row['closed_date'];
			$object->is_won = $row['is_won'];
			$object->is_closed = $row['is_closed'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne("SELECT count(id) FROM crm_opportunity");
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();

		// Context Links
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN crm_opportunity ON context_link.from_context_id=crm_opportunity.id WHERE context_link.from_context = %s AND crm_opportunity.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_OPPORTUNITY)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' opportunity context link sources.');
		
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN crm_opportunity ON context_link.to_context_id=crm_opportunity.id WHERE context_link.to_context = %s AND crm_opportunity.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_OPPORTUNITY)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' opportunity context link targets.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Opps
		$db->Execute(sprintf("DELETE QUICK FROM crm_opportunity WHERE id IN (%s)", $ids_list));

		// Context links
		DAO_ContextLink::delete(CerberusContexts::CONTEXT_OPPORTUNITY, $ids);
		
		// Custom fields
		DAO_CustomFieldValue::deleteByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $ids);
		
		// Notes
		DAO_Comment::deleteByContext(CerberusContexts::CONTEXT_OPPORTUNITY, $ids);
		
		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CrmOpportunity::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"o.id as %s, ".
			"o.name as %s, ".
			"o.amount as %s, ".
			"org.id as %s, ".
			"org.name as %s, ".
			"o.primary_email_id as %s, ".
			"a.email as %s, ".
			"a.first_name as %s, ".
			"a.last_name as %s, ".
			"a.num_spam as %s, ".
			"a.num_nonspam as %s, ".
			"o.created_date as %s, ".
			"o.updated_date as %s, ".
			"o.closed_date as %s, ".
			"o.is_closed as %s, ".
			"o.is_won as %s ",
			    SearchFields_CrmOpportunity::ID,
			    SearchFields_CrmOpportunity::NAME,
			    SearchFields_CrmOpportunity::AMOUNT,
			    SearchFields_CrmOpportunity::ORG_ID,
			    SearchFields_CrmOpportunity::ORG_NAME,
			    SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			    SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			    SearchFields_CrmOpportunity::EMAIL_FIRST_NAME,
			    SearchFields_CrmOpportunity::EMAIL_LAST_NAME,
			    SearchFields_CrmOpportunity::EMAIL_NUM_SPAM,
			    SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM,
			    SearchFields_CrmOpportunity::CREATED_DATE,
			    SearchFields_CrmOpportunity::UPDATED_DATE,
			    SearchFields_CrmOpportunity::CLOSED_DATE,
			    SearchFields_CrmOpportunity::IS_CLOSED,
			    SearchFields_CrmOpportunity::IS_WON
			);
			
		$join_sql = 
			"FROM crm_opportunity o ".
			"INNER JOIN address a ON (a.id = o.primary_email_id) ".
			"LEFT JOIN contact_org org ON (org.id = a.contact_org_id) ".
			
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.opportunity' AND context_link.to_context_id = o.id) " : " ")
			;
			
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'o.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		// Virtuals
		foreach($params as $param) {
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_CrmOpportunity::VIRTUAL_WORKERS:
					$has_multiple_values = true;
					if(empty($param->value)) { // empty
						$join_sql .= "LEFT JOIN context_link AS context_owner ON (context_owner.from_context = 'cerberusweb.contexts.opportunity' AND context_owner.from_context_id = o.id AND context_owner.to_context = 'cerberusweb.contexts.worker') ";
						$where_sql .= "AND context_owner.to_context_id IS NULL ";
					} else {
						$join_sql .= sprintf("INNER JOIN context_link AS context_owner ON (context_owner.from_context = 'cerberusweb.contexts.opportunity' AND context_owner.from_context_id = o.id AND context_owner.to_context = 'cerberusweb.contexts.worker' AND context_owner.to_context_id IN (%s)) ",
							implode(',', $param->value)
						);
					}
					break;
			}
		}

		$result = array(
			'primary_table' => 'o',
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
			($has_multiple_values ? 'GROUP BY o.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_CrmOpportunity::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT o.id) " : "SELECT COUNT(o.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_CrmOpportunity implements IDevblocksSearchFields {
	// Table
	const ID = 'o_id';
	const PRIMARY_EMAIL_ID = 'o_primary_email_id';
	const NAME = 'o_name';
	const AMOUNT = 'o_amount';
	const CREATED_DATE = 'o_created_date';
	const UPDATED_DATE = 'o_updated_date';
	const CLOSED_DATE = 'o_closed_date';
	const IS_WON = 'o_is_won';
	const IS_CLOSED = 'o_is_closed';
	
	const ORG_ID = 'org_id';
	const ORG_NAME = 'org_name';

	const EMAIL_ADDRESS = 'a_email';
	const EMAIL_FIRST_NAME = 'a_first_name';
	const EMAIL_LAST_NAME = 'a_last_name';
	const EMAIL_NUM_SPAM = 'a_num_spam';
	const EMAIL_NUM_NONSPAM = 'a_num_nonspam';

	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	const VIRTUAL_WORKERS = '*_workers';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'o', 'id', $translate->_('crm.opportunity.id')),
			
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'o', 'primary_email_id', $translate->_('crm.opportunity.primary_email_id')),
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'a', 'email', $translate->_('crm.opportunity.email_address')),
			self::EMAIL_FIRST_NAME => new DevblocksSearchField(self::EMAIL_FIRST_NAME, 'a', 'first_name', $translate->_('address.first_name')),
			self::EMAIL_LAST_NAME => new DevblocksSearchField(self::EMAIL_LAST_NAME, 'a', 'last_name', $translate->_('address.last_name')),
			self::EMAIL_NUM_SPAM => new DevblocksSearchField(self::EMAIL_NUM_SPAM, 'a', 'num_spam', $translate->_('address.num_spam')),
			self::EMAIL_NUM_NONSPAM => new DevblocksSearchField(self::EMAIL_NUM_NONSPAM, 'a', 'num_nonspam', $translate->_('address.num_nonspam')),
			
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'org', 'id'),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'org', 'name', $translate->_('crm.opportunity.org_name')),
			
			self::NAME => new DevblocksSearchField(self::NAME, 'o', 'name', $translate->_('crm.opportunity.name')),
			self::AMOUNT => new DevblocksSearchField(self::AMOUNT, 'o', 'amount', $translate->_('crm.opportunity.amount')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'o', 'created_date', $translate->_('crm.opportunity.created_date')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'o', 'updated_date', $translate->_('crm.opportunity.updated_date')),
			self::CLOSED_DATE => new DevblocksSearchField(self::CLOSED_DATE, 'o', 'closed_date', $translate->_('crm.opportunity.closed_date')),
			self::IS_WON => new DevblocksSearchField(self::IS_WON, 'o', 'is_won', $translate->_('crm.opportunity.is_won')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'o', 'is_closed', $translate->_('crm.opportunity.is_closed')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_WORKERS => new DevblocksSearchField(self::VIRTUAL_WORKERS, '*', 'workers', $translate->_('common.owners')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);
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

class Model_CrmOpportunity {
	public $id;
	public $name;
	public $amount;
	public $primary_email_id;
	public $created_date;
	public $updated_date;
	public $closed_date;
	public $is_won;
	public $is_closed;
};

class View_CrmOpportunity extends C4_AbstractView {
	const DEFAULT_ID = 'crm_opportunities';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Opportunities';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::AMOUNT,
			SearchFields_CrmOpportunity::UPDATED_DATE,
			SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM,
		);
		$this->addColumnsHidden(array(
			SearchFields_CrmOpportunity::ID,
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::ORG_ID,
			SearchFields_CrmOpportunity::CONTEXT_LINK,
			SearchFields_CrmOpportunity::CONTEXT_LINK_ID,
			SearchFields_CrmOpportunity::VIRTUAL_WORKERS
		));
		
		$this->addParamsDefault(array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
		));
		$this->addParamsHidden(array(
			SearchFields_CrmOpportunity::ID,
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::ORG_ID,
			SearchFields_CrmOpportunity::CONTEXT_LINK,
			SearchFields_CrmOpportunity::CONTEXT_LINK_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CrmOpportunity::search(
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
		return $this->_doGetDataSample('DAO_CrmOpportunity', $size);
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.crm::crm/opps/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.crm::crm/opps/view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_CrmOpportunity::VIRTUAL_WORKERS:
				if(empty($param->value)) {
					echo "Owners <b>are not assigned</b>";
					
				} elseif(is_array($param->value)) {
					$workers = DAO_Worker::getAll();
					$strings = array();
					
					foreach($param->value as $worker_id) {
						if(isset($workers[$worker_id]))
							$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
					
					echo sprintf("Owner is %s", implode(' or ', $strings));
				}
				break;
		}
	}	
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::EMAIL_FIRST_NAME:
			case SearchFields_CrmOpportunity::EMAIL_LAST_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
			case SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM:
			case SearchFields_CrmOpportunity::EMAIL_NUM_SPAM:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_WORKERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
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
		return SearchFields_CrmOpportunity::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::EMAIL_FIRST_NAME:
			case SearchFields_CrmOpportunity::EMAIL_LAST_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
			case SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM:
			case SearchFields_CrmOpportunity::EMAIL_NUM_SPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:		
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_WORKERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $worker_ids);
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
				case 'status':
					switch(strtolower($v)) {
						case 'open':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 0;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 0;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = 0;
							break;
						case 'won':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 1;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = time();
							break;
						case 'lost':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 0;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = time();
							break;
					}
					break;
				case 'closed_date':
					$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = intval($v);
					break;
//				case 'worker_id':
//					$change_fields[DAO_CrmOpportunity::WORKER_ID] = intval($v);
//					break;
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
			list($objects, $null) = DAO_CrmOpportunity::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CrmOpportunity::ID,
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
			foreach($ids as $opp_id) {
				try {
					CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $tpl_labels, $tpl_tokens);
					$subject = $tpl_builder->build($params['subject'], $tpl_tokens);
					$body = $tpl_builder->build($params['message'], $tpl_tokens);
					
					$json_params = array(
						'to' => $tpl_tokens['email_address'],
						'group_id' => $params['group_id'],
						'next_is_closed' => $next_is_closed,
					);
					
					$fields = array(
						DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
						DAO_MailQueue::TICKET_ID => 0,
						DAO_MailQueue::WORKER_ID => $params['worker_id'],
						DAO_MailQueue::UPDATED => time(),
						DAO_MailQueue::HINT_TO => $tpl_tokens['email_address'],
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
			DAO_CrmOpportunity::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $custom_fields, $batch_ids);
			
			// Owners
			if(isset($do['owner']) && is_array($do['owner'])) {
				$owner_params = $do['owner'];
				foreach($batch_ids as $batch_id) {
					if(isset($owner_params['add']) && is_array($owner_params['add']))
						CerberusContexts::addWorkers(CerberusContexts::CONTEXT_OPPORTUNITY, $batch_id, $owner_params['add']);
					if(isset($owner_params['remove']) && is_array($owner_params['remove']))
						CerberusContexts::removeWorkers(CerberusContexts::CONTEXT_OPPORTUNITY, $batch_id, $owner_params['remove']);
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
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

class Context_Opportunity extends Extension_DevblocksContext {
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=crm&tab=opps&id='.$context_id, true);
    }
    
	function getContext($opp, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Opportunity:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);

		// Polymorph
		if(is_numeric($opp)) {
			$opp = DAO_CrmOpportunity::get($opp);
		} elseif($opp instanceof Model_CrmOpportunity) {
			// It's what we want already.
		} else {
			$opp = null;
		}
		
		// Token labels
		$token_labels = array(
			'amount' => $prefix.$translate->_('crm.opportunity.amount'),
			'created|date' => $prefix.$translate->_('crm.opportunity.created_date'),
			'is_closed' => $prefix.$translate->_('crm.opportunity.is_closed'),
			'is_won' => $prefix.$translate->_('crm.opportunity.is_won'),
			'title' => $prefix.$translate->_('crm.opportunity.name'),
			'updated|date' => $prefix.$translate->_('crm.opportunity.updated_date'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Opp token values
		if($opp) {
			$token_values['id'] = $opp->id;
			$token_values['amount'] = $opp->amount;
			$token_values['created'] = $opp->created_date;
			$token_values['is_closed'] = $opp->is_closed;
			$token_values['is_won'] = $opp->is_won;
			$token_values['title'] = $opp->name;
			$token_values['updated'] = $opp->updated_date;
//			if(!empty($org->city))
//				$token_values['city'] = $org->city;

			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $opp->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $opp)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $opp)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Person
		@$address_id = $opp->primary_email_id;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $address_id, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'email_',
			'Lead:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Assignee
//		@$assignee_id = $opp->worker_id;
//		$merge_token_labels = array();
//		$merge_token_values = array();
//		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $assignee_id, $merge_token_labels, $merge_token_values, '', true);
//
//		CerberusContexts::merge(
//			'assignee_',
//			'Assignee:',
//			$merge_token_labels,
//			$merge_token_values,
//			$token_labels,
//			$token_values
//		);		
		
		return true;
	}

	function getChooserView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Opportunities';
		$view->view_columns = array(
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::UPDATED_DATE,
		);
		$view->addParams(array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
			//SearchFields_CrmOpportunity::WORKER_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::WORKER_ID,'=',$active_worker->id),
		), true);
		$view->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$view->renderSortAsc = false;
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
		$view->name = 'Opportunities';
		
		$params = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params = array(
				new DevblocksSearchCriteria(SearchFields_CrmOpportunity::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_CrmOpportunity::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		if(isset($options['filter_open']))
			$params[] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0);
		
		$view->addParams($params, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
