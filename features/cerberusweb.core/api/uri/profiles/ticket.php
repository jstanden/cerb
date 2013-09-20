<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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

class PageSection_ProfilesTicket extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		$url = DevblocksPlatform::getUrlService();
		
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // ticket
		@$id_string = array_shift($stack);
		
		// Translate masks to IDs
		if(!is_numeric($id_string)) {
			$id = DAO_Ticket::getTicketIdByMask($id_string);
			
			if(empty($id))
				$id = intval($id_string);
			
		} else {
			$id = intval($id_string);
		}
		
		if(null == ($ticket = DAO_Ticket::get($id))) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest());
			return;
		}
		
		$tpl->assign('ticket', $ticket);
		
		// Remember the last tab/URL
		@$selected_tab = array_shift($stack);
		
		$point = 'cerberusweb.profiles.ticket';
		$tpl->assign('point', $point);
		
		/*
		 * Disabled for #CHD-2966
 		if(null == $selected_tab) {
 			$selected_tab = $visit->get($point, '');
 		}
 		*/
		
		if(empty($selected_tab))
			$selected_tab = 'conversation';
		
		@$mail_always_show_all = DAO_WorkerPref::get($active_worker->id,'mail_always_show_all',0);
		
		switch($selected_tab) {
			case 'conversation':
				@$tab_option = array_shift($stack);
		
				if($mail_always_show_all || 0==strcasecmp("read_all",$tab_option)) {
					$tpl->assign('expand_all', true);
				}
				break;
				
			case 'comment':
				@$focus_id = intval(array_shift($stack));
				$selected_tab = 'conversation';
				
				if(!empty($focus_id)) {
					$tpl->assign('convo_focus_ctx', CerberusContexts::CONTEXT_COMMENT);
					$tpl->assign('convo_focus_ctx_id', $focus_id);
				}
				
				if($mail_always_show_all)
					$tpl->assign('expand_all', true);
				
				break;
				
			case 'message':
				@$focus_id = intval(array_shift($stack));
				$selected_tab = 'conversation';
				
				if(!empty($focus_id)) {
					$tpl->assign('convo_focus_ctx', CerberusContexts::CONTEXT_MESSAGE);
					$tpl->assign('convo_focus_ctx_id', $focus_id);
				}
				
				if($mail_always_show_all)
					$tpl->assign('expand_all', true);
				
				break;
		}
		
		$tpl->assign('selected_tab', $selected_tab);
		
		// Trigger ticket view event
		Event_TicketViewedByWorker::trigger($ticket->id, $active_worker->id);
		
		// Properties
		
		$properties = array(
			'status' => null,
			'owner' => null,
			'mask' => null,
			'bucket' => null,
			'org' => null,
			'created' => array(
				'label' => ucfirst($translate->_('common.created')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $ticket->created_date,
			),
			'updated' => array(
				'label' => ucfirst($translate->_('common.updated')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $ticket->updated_date,
			),
		);
		
		if(!empty($ticket->closed_at)) {
			$properties['updated'] = array(
				'label' => ucfirst($translate->_('ticket.closed_at')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $ticket->closed_at,
			);
		}
		
		if(!empty($ticket->elapsed_response_first)) {
			$properties['elapsed_response_first'] = array(
				'label' => ucfirst($translate->_('ticket.elapsed_response_first')),
				'type' => null,
				'value' => DevblocksPlatform::strSecsToString($ticket->elapsed_response_first, 2),
			);
		}
		
		if(!empty($ticket->elapsed_resolution_first)) {
			$properties['elapsed_resolution_first'] = array(
				'label' => ucfirst($translate->_('ticket.elapsed_resolution_first')),
				'type' => null,
				'value' => DevblocksPlatform::strSecsToString($ticket->elapsed_resolution_first, 2),
			);
		}
		
		$properties['spam_score'] = array(
			'label' => ucfirst($translate->_('ticket.spam_score')),
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
		
		// Permissions
		
		$active_worker_memberships = $active_worker->getMemberships();
		
		// Check group membership ACL
		if(!isset($active_worker_memberships[$ticket->group_id])) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest());
			exit;
		}
		
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
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/ticket.tpl');
	}
};