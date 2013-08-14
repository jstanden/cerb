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

class PageSection_ProfilesVirtualAttendant extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // virtual_attendant
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($virtual_attendant = DAO_VirtualAttendant::get($id))) {
			return;
		}
		$tpl->assign('virtual_attendant', $virtual_attendant);
	
		// Tab persistence
		
		$point = 'profiles.virtual_attendant.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
		
		$properties['_owner'] = array(
			'label' => ucfirst($translate->_('common.owner')),
			'type' => null,
			'value' => null,
		);
		
		$properties['created'] = array(
			'label' => ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $virtual_attendant->created_at,
		);
		
		$properties['updated'] = array(
			'label' => ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $virtual_attendant->updated_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $virtual_attendant->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $virtual_attendant->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		//$macros = DAO_TriggerEvent::getByVirtualAttendantOwners(
		//	array(
		//		array(CerberusContexts::CONTEXT_WORKER, $active_worker->id),
		//	),
		//	'event.macro.virtual_attendant'
		//);
		//$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/virtual_attendant.tpl');
	}
	
	function savePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			return;
		
		// Owner
		
		$owner_ctx = '';
		@list($owner_ctx_code, $owner_ctx_id) = explode('_', $owner, 2);
		
		switch(strtolower($owner_ctx_code)) {
			case 'a':
				$owner_ctx = CerberusContexts::CONTEXT_APPLICATION;
				break;
			case 'w':
				$owner_ctx = CerberusContexts::CONTEXT_WORKER;
				break;
			case 'g':
				$owner_ctx = CerberusContexts::CONTEXT_GROUP;
				break;
			case 'r':
				$owner_ctx = CerberusContexts::CONTEXT_ROLE;
				break;
		}
		
		// Model
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_VirtualAttendant::delete($id);
			
		} else {
			if(empty($id)) { // New
				$fields = array(
					DAO_VirtualAttendant::CREATED_AT => time(),
					DAO_VirtualAttendant::UPDATED_AT => time(),
					DAO_VirtualAttendant::NAME => $name,
					DAO_VirtualAttendant::OWNER_CONTEXT => $owner_ctx,
					DAO_VirtualAttendant::OWNER_CONTEXT_ID => $owner_ctx_id,
				);
				$id = DAO_VirtualAttendant::create($fields);
				
				// Watchers
				@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
				if(!empty($add_watcher_ids))
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $id, $add_watcher_ids);
				
				// Context Link (if given)
				@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
				@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
				if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $id, $link_context, $link_context_id);
				}
				
				if(!empty($view_id) && !empty($id))
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $id);
				
			} else { // Edit
				$fields = array(
					DAO_VirtualAttendant::UPDATED_AT => time(),
					DAO_VirtualAttendant::NAME => $name,
					DAO_VirtualAttendant::OWNER_CONTEXT => $owner_ctx,
					DAO_VirtualAttendant::OWNER_CONTEXT_ID => $owner_ctx_id,
				);
				DAO_VirtualAttendant::update($id, $fields);
				
			}

			// If we're adding a comment
			if(!empty($comment)) {
				@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT,
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $id, $field_ids);
		}
	}
	
	function viewExploreAction() {
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=virtual_attendant', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.virtual.attendant.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=virtual_attendant&id=%d-%s", $row[SearchFields_VirtualAttendant::ID], DevblocksPlatform::strToPermalink($row[SearchFields_VirtualAttendant::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_VirtualAttendant::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
