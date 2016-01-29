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

class PageSection_ProfilesTicket extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		$url = DevblocksPlatform::getUrlService();
		
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // ticket
		@$id_string = array_shift($stack);
		@$section = array_shift($stack);
		
		// Translate masks to IDs
		if(null == ($ticket_id = DAO_Ticket::getTicketIdByMask($id_string))) {
			$ticket_id = intval($id_string);
		}
		
		// Trigger ticket view event (before we load it, in case we change it)
		Event_TicketViewedByWorker::trigger($ticket_id, $active_worker->id);
		
		// Load the record
		if(false == ($ticket = DAO_Ticket::get($ticket_id))) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest());
			return;
		}
		
		$tpl->assign('ticket', $ticket);
		
		// Permissions
		
		if(false == ($group = $ticket->getGroup()))
			return;
		
		// Check group membership ACL
		if(!$group->isReadableByWorker($active_worker)) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest());
			exit;
		}
		
		$point = 'cerberusweb.profiles.ticket';
		$tpl->assign('point', $point);
		
		@$mail_always_show_all = DAO_WorkerPref::get($active_worker->id,'mail_always_show_all',0);

		if($mail_always_show_all)
			$tpl->assign('expand_all', true);
		
		if(!empty($section)) {
			switch($section) {
				case 'conversation':
					@$tab_option = array_shift($stack);
			
					if($mail_always_show_all || 0==strcasecmp("read_all",$tab_option)) {
						$tpl->assign('expand_all', true);
					}
					break;
					
				case 'comment':
					@$focus_id = intval(array_shift($stack));
					$section = 'conversation';
					
					if(!empty($focus_id)) {
						$tpl->assign('convo_focus_ctx', CerberusContexts::CONTEXT_COMMENT);
						$tpl->assign('convo_focus_ctx_id', $focus_id);
					}
					
					break;
					
				case 'message':
					@$focus_id = intval(array_shift($stack));
					$section = 'conversation';
					
					if(!empty($focus_id)) {
						$tpl->assign('convo_focus_ctx', CerberusContexts::CONTEXT_MESSAGE);
						$tpl->assign('convo_focus_ctx_id', $focus_id);
					}
					
					break;
			}
			
			$tpl->assign('tab', $section);
		}
		
		// Properties
		
		$properties = array(
			'status' => null,
			'owner' => array(
				'label' => mb_ucfirst($translate->_('common.owner')),
				'type' => Model_CustomField::TYPE_LINK,
				'value' => $ticket->owner_id,
				'params' => array(
					'context' => CerberusContexts::CONTEXT_WORKER,
				),
			),
			'mask' => null,
			'bucket' => null,
			'org' => array(
				'label' => mb_ucfirst($translate->_('common.organization')),
				'type' => Model_CustomField::TYPE_LINK,
				'value' => $ticket->org_id,
				'params' => array(
					'context' => CerberusContexts::CONTEXT_ORG,
				),
			),
			'importance' => null,
			'created' => array(
				'label' => mb_ucfirst($translate->_('common.created')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $ticket->created_date,
			),
			'updated' => array(
				'label' => mb_ucfirst($translate->_('common.updated')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $ticket->updated_date,
			),
		);
		
		if(!empty($ticket->closed_at)) {
			$properties['closed'] = array(
				'label' => mb_ucfirst($translate->_('ticket.closed_at')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $ticket->closed_at,
			);
		}
		
		if(!empty($ticket->elapsed_response_first)) {
			$properties['elapsed_response_first'] = array(
				'label' => mb_ucfirst($translate->_('ticket.elapsed_response_first')),
				'type' => null,
				'value' => DevblocksPlatform::strSecsToString($ticket->elapsed_response_first, 2),
			);
		}
		
		if(!empty($ticket->elapsed_resolution_first)) {
			$properties['elapsed_resolution_first'] = array(
				'label' => mb_ucfirst($translate->_('ticket.elapsed_resolution_first')),
				'type' => null,
				'value' => DevblocksPlatform::strSecsToString($ticket->elapsed_resolution_first, 2),
			);
		}
		
		$properties['spam_score'] = array(
			'label' => mb_ucfirst($translate->_('ticket.spam_score')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => (100*$ticket->spam_score) . '%',
		);
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_TICKET, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		

		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_TICKET, $ticket->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_TICKET => array(
				$ticket->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_TICKET,
						$ticket->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(isset($ticket->org_id)) {
			$properties_links[CerberusContexts::CONTEXT_ORG] = array(
				$ticket->org_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ORG,
						$ticket->org_id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.ticket'
		);

		// Filter macros to only those owned by the ticket's group
		
		$macros = array_filter($macros, function($macro) use ($ticket) { /* @var $macro Model_TriggerEvent */
			$va = $macro->getVirtualAttendant(); /* @var $va Model_VirtualAttendant */
			
			if($va->owner_context == CerberusContexts::CONTEXT_GROUP && $va->owner_context_id != $ticket->group_id)
				return false;
			
			return true;
		});
		
		$tpl->assign('macros', $macros);
		
		// Requesters
		$requesters = DAO_Ticket::getRequestersByTicket($ticket->id);
		$tpl->assign('requesters', $requesters);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Watchers
		// [TODO] Is this necessary or redundant?
		$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$tpl->assign('context_watchers', $context_watchers);
		
		// Buckets
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Log Activity
		DAO_Worker::logActivity(
			new Model_Activity('activity.display_ticket',array(
				sprintf("<a href='%s' title='[%s] %s'>#%s</a>",
					$url->write("c=profiles&type=ticket&id=".$ticket->mask),
					htmlspecialchars(@$groups[$ticket->group_id]->name, ENT_QUOTES, LANG_CHARSET_CODE),
					htmlspecialchars($ticket->subject, ENT_QUOTES, LANG_CHARSET_CODE),
					$ticket->mask
				)
			)),
			true
		);
		
		// If deleted, check for a new merge parent URL
		if($ticket->is_deleted) {
			if(false !== ($new_mask = DAO_Ticket::getMergeParentByMask($ticket->mask))) {
				if(false !== ($merge_parent = DAO_Ticket::getTicketByMask($new_mask)))
					if(!empty($merge_parent->mask))
						$tpl->assign('merge_parent', $merge_parent);
			}
		}
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/ticket.tpl');
	}
	
	function getPeekPreviewAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		switch($context) {
			case CerberusContexts::CONTEXT_MESSAGE:
				if(false == ($message = DAO_Message::get($context_id)))
					return;
				
				$tpl->assign('message', $message);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
				
			case CerberusContexts::CONTEXT_COMMENT:
				if(false == ($comment = DAO_Comment::get($context_id)))
					return;
					
				$tpl->assign('comment', $comment);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
		}
		
	}
};