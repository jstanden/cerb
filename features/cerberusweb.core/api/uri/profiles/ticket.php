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
			echo "<H1>".$translate->_('display.invalid_ticket')."</H1>";
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
		
		$tpl->assign('selected_tab', $selected_tab);
		
		switch($selected_tab) {
			case 'conversation':
				@$mail_always_show_all = DAO_WorkerPref::get($active_worker->id,'mail_always_show_all',0);
				@$tab_option = array_shift($stack);
		
				if($mail_always_show_all || 0==strcasecmp("read_all",$tab_option)) {
					$tpl->assign('expand_all', true);
				}
				break;
		}
		
		// Trigger ticket view event
		Event_TicketViewedByWorker::trigger($ticket->id, $active_worker->id);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
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
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id)) or array();
		
		foreach($custom_fields as $cf_id => $cfield) {
			if(!isset($values[$cf_id]))
				continue;
		
			if(!empty($cfield->group_id) && $cfield->group_id != $ticket->group_id)
				continue;
		
			$properties['cf_' . $cf_id] = array(
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => $values[$cf_id],
			);
		}
		
		$tpl->assign('properties', $properties);
		
		// Permissions
		
		$active_worker_memberships = $active_worker->getMemberships();
		
		// Check group membership ACL
		if(!isset($active_worker_memberships[$ticket->group_id])) {
			echo "<H1>".$translate->_('common.access_denied')."</H1>";
			return;
		}
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwners(
			array(
				array(CerberusContexts::CONTEXT_WORKER, $active_worker->id, null),
				array(CerberusContexts::CONTEXT_GROUP, $ticket->group_id, $groups[$ticket->group_id]->name),
			),
			'event.macro.ticket'
		);
		$tpl->assign('macros', $macros);
		
		// Requesters
		$requesters = DAO_Ticket::getRequestersByTicket($ticket->id);
		$tpl->assign('requesters', $requesters);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Watchers
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