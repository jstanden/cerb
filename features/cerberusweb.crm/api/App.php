<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

// [TODO] Move to profile
class CrmPage extends CerberusPageExtension {
	function render() {
	}
	
	function saveOppPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$amount = DevblocksPlatform::importGPC($_REQUEST['amount'],'string','0.00');
		@$email_id = DevblocksPlatform::importGPC($_REQUEST['email_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$created_date_str = DevblocksPlatform::importGPC($_REQUEST['created_date'],'string','');
		@$closed_date_str = DevblocksPlatform::importGPC($_REQUEST['closed_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		// State
		$is_closed = (0==$status) ? 0 : 1;
		$is_won = (1==$status) ? 1 : 0;
		
		// Strip currency formatting symbols
		$amount = floatval(str_replace(array(',','$','¢','£','€'),'',$amount));
		
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
			if(null == ($address = DAO_Address::get($email_id)))
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
			
			// Watchers
			@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
			if(!empty($add_watcher_ids))
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $add_watcher_ids);
			
			// Context Link (if given)
			@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
			@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
			if(!empty($opp_id) && !empty($link_context) && !empty($link_context_id)) {
				DAO_ContextLink::setLink(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $link_context, $link_context_id);
			}
			
			// View marquee
			if(!empty($opp_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
			}
			
		} else {
			if(empty($opp_id))
				return;
			
			// Check privs
			if(!$active_worker->hasPriv('crm.opp.actions.update_all'))
				return;
			
			if(null == ($address = DAO_Address::get($email_id)))
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
				$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
					DAO_Comment::CONTEXT_ID => $opp_id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.crm.opportunity'
		);
		$tpl->assign('macros', $macros);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
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
		$view->setAutoPersist(false);
		
		// Opp fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));
		@$closed_date = trim(DevblocksPlatform::importGPC($_POST['closed_date'],'string',''));
		@$worker_id = trim(DevblocksPlatform::importGPC($_POST['worker_id'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
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
				'params' => $behavior_params,
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
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			@$broadcast_is_closed = DevblocksPlatform::importGPC($_REQUEST['broadcast_next_is_closed'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'next_is_closed' => $broadcast_is_closed,
					'group_id' => $broadcast_group_id,
					'worker_id' => $active_worker->id,
					'file_ids' => $broadcast_file_ids,
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
		$view->setAutoPersist(false);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if($active_worker->hasPriv('crm.opp.view.actions.broadcast')) {
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);

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
						
						switch($broadcast_format) {
							case 'parsedown':
								// Markdown
								$output = DevblocksPlatform::parseMarkdown($output);
								
								// HTML Template
								
								$html_template = null;
								
								if($broadcast_html_template_id)
									$html_template = DAO_MailHtmlTemplate::get($broadcast_html_template_id);
								
								if(!$html_template && false != ($group = DAO_Group::get($broadcast_group_id)))
									$html_template = $group->getReplyHtmlTemplate(0);
								
								if(!$html_template && false != ($replyto = DAO_AddressOutgoing::getDefault()))
									$html_template = $replyto->getReplyHtmlTemplate();
								
								if($html_template)
									$output = $tpl_builder->build($html_template->content, array('message_body' => $output));
								
								// HTML Purify
								$output = DevblocksPlatform::purifyHTML($output, true);
								break;
								
							default:
								$output = nl2br(DevblocksPlatform::strEscapeHtml($output));
								break;
						}
					}
				}
			}
			
			if($success) {
				header("Content-Type: text/html; charset=" . LANG_CHARSET_CODE);
				echo sprintf('<html><head><meta http-equiv="content-type" content="text/html; charset=%s"></head><body>',
					LANG_CHARSET_CODE
				);
				echo $output;
				echo '</body></html>';
				
			} else {
				echo $output;
			}
		}
	}
	
	function viewOppsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=opportunity', true),
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
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&tab=opportunity&id=%d-%s", $row[SearchFields_CrmOpportunity::ID], DevblocksPlatform::strToPermalink($row[SearchFields_CrmOpportunity::NAME])), true),
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

if (class_exists('Extension_ContextProfileTab')):
class CrmOrgOppTab extends Extension_ContextProfileTab {
	function showTab($context, $context_id) {
		if(0 != strcasecmp($context, CerberusContexts::CONTEXT_ORG))
			return;

		$org_id = $context_id;
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($org = DAO_ContactOrg::get($org_id)))
			return;
			
		$tpl->assign('org_id', $org_id);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_CrmOpportunity');
		$defaults->id = 'org_opps';
		
		$view = C4_AbstractViewLoader::getView('org_opps', $defaults);
		
		$view->name = "Org: " . $org->name;
		$view->addParamsRequired(array(
			SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org_id)
		), true);

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
};
endif;

if (class_exists('Extension_ContextProfileTab')):
class CrmTicketOppTab extends Extension_ContextProfileTab {
	function showTab($context, $context_id) {
		if($context != CerberusContexts::CONTEXT_TICKET)
			return;

		$ticket_id = $context_id;
		
		$tpl = DevblocksPlatform::getTemplateService();

		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		
		if(null == ($view = C4_AbstractViewLoader::getView('ticket_opps'))) {
			$view = new View_CrmOpportunity();
			$view->id = 'ticket_opps';
		}

		$view->name = sprintf("Opportunities: %s recipient(s)", count($requesters));
		$view->addParamsRequired(array(
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,'in',array_keys($requesters)),
		), true);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function saveTab() {
	}
};
endif;

if(class_exists('Extension_DevblocksEventAction')):
class VaAction_CreateOpportunity extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.crm::events/action_create_opportunity.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$out = null;
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$email = $tpl_builder->build($params['email'], $dict);
		@$status = $params['status'];
		@$amount = floatval($tpl_builder->build($params['amount'], $dict));
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		if(empty($name))
			return "[ERROR] Name is required.";
		
		if(empty($email))
			return "[ERROR] Email is required.";
		
		if(false == ($email_model = DAO_Address::lookupAddress($email, true)))
			return "[ERROR] The email address is invalid.";
		
		if(!in_array($status, array('open', 'closed_won', 'closed_lost')))
			return "[ERROR] Status has an invalid value.";
		
		if(!is_float($amount))
			return "[ERROR] Amount should be a numeric value.";
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		$out = sprintf(">>> Creating opportunity: %s\nEmail: %s\nStatus: %s\nAmount: %0.2f\n",
			$name,
			$email,
			$status,
			$amount
		);
		
		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on opportunity\n\n".
				"%s\n\n",
				$comment
			);
			
			if(!empty($notify_worker_ids) && is_array($notify_worker_ids)) {
				$out .= ">>> Notifying\n";
				foreach($notify_worker_ids as $worker_id) {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= ' * ' . $worker->getName() . "\n";
					}
				}
				$out .= "\n";
			}
		}
		
		// Links
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$email = $tpl_builder->build($params['email'], $dict);
		@$status = $params['status'];
		@$amount = floatval($tpl_builder->build($params['amount'], $dict));

		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		if(empty($name))
			return;
		
		if(empty($email))
			return;
		
		if(false == ($email_model = DAO_Address::lookupAddress($email, true)))
			return;
		
		if(!in_array($status, array('open', 'closed_won', 'closed_lost')))
			return ;
		
		if(!is_float($amount))
			return;
		
		$fields = array(
			DAO_CrmOpportunity::NAME => $name,
			DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $email_model->id,
			DAO_CrmOpportunity::AMOUNT => sprintf("%0.2f", $amount),
		);
		
		switch($status) {
			case 'open':
				$fields[DAO_CrmOpportunity::IS_CLOSED] = 0;
				$fields[DAO_CrmOpportunity::IS_WON] = 0;
				break;
			case 'closed_won':
				$fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
				$fields[DAO_CrmOpportunity::IS_WON] = 1;
				break;
			case 'closed_lost':
				$fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
				$fields[DAO_CrmOpportunity::IS_WON] = 0;
				break;
		}
			
		if(false == ($opp_id = DAO_CrmOpportunity::create($fields)))
			return;
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $params, $dict);
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT,
				DAO_Comment::OWNER_CONTEXT_ID => $trigger->virtual_attendant_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
				DAO_Comment::CONTEXT_ID => $opp_id,
				DAO_Comment::CREATED => time(),
			);
			DAO_Comment::create($fields, $notify_worker_ids);
		}

		// Connection
		DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $params, $dict);
		
		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $params, $dict);
	}
	
};
endif;