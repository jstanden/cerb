<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesCommunityPortal extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // community_tool 
		@$id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_PORTAL;
		
		if(null == ($community_tool = DAO_CommunityTool::get($id)))
			return;
			
		$tpl->assign('community_tool', $community_tool);

		// Context

		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $community_tool, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);

		if(null == ($extension = $community_tool->getExtension()))
			return;
		
		$tpl->assign('extension', $extension);
		
		// Tab persistence
		
		$point = 'profiles.community_tool.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['extension'] = array(
			'label' => mb_ucfirst($translate->_('common.extension')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $extension->manifest->name,
		);
		
		$properties['path'] = array(
			'label' => mb_ucfirst($translate->_('common.path')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $community_tool->uri,
		);
		
		$properties['code'] = array(
			'label' => mb_ucfirst($translate->_('community_portal.code')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $community_tool->code,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $community_tool->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $community_tool->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $community_tool->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$community_tool->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$community_tool->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, $context);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Card search buttons
		$search_buttons = $context_ext->getCardSearchButtons($dict, []);
		$tpl->assign('search_buttons', $search_buttons);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/community_portal.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_PORTAL)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_CommunityTool::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$path = DevblocksPlatform::importGPC($_REQUEST['path'], 'string', '');
				
				if(empty($id)) { // New
					@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
					
					$fields = array(
						DAO_CommunityTool::EXTENSION_ID => $extension_id,
						DAO_CommunityTool::NAME => $name,
						DAO_CommunityTool::UPDATED_AT => time(),
						DAO_CommunityTool::URI => $path,
					);
					
					if(!DAO_CommunityTool::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_CommunityTool::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_CommunityTool::create($fields);
					DAO_CommunityTool::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_PORTAL, $id);
					
				} else { // Edit
					$fields = array(
						DAO_CommunityTool::NAME => $name,
						DAO_CommunityTool::UPDATED_AT => time(),
						DAO_CommunityTool::URI => $path,
					);
					
					if(!DAO_CommunityTool::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_CommunityTool::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CommunityTool::update($id, $fields);
					DAO_CommunityTool::onUpdateByActor($active_worker, $id, $fields);
					
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_PORTAL, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
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
			$models = [];
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=community_portal', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.community.tool.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=community_portal&id=%d-%s", $row[SearchFields_CommunityTool::ID], DevblocksPlatform::strToPermalink($row[SearchFields_CommunityTool::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_CommunityTool::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function showProfileTabAction() {
		@$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'], 'integer', 0);
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'], 'string', '');
		
		if(false == ($portal = DAO_CommunityTool::get($portal_id)))
			return;
		
		if(false == ($extension = $portal->getExtension()))
			return;
		
		$extension->profileRenderTab($tab_id, $portal);
	}
	
	function handleProfileTabActionAction() {
		@$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'], 'integer', 0);
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'], 'string', '');
		@$tab_action = DevblocksPlatform::importGPC($_REQUEST['tab_action'], 'string', '');
		
		if(false == ($portal = DAO_CommunityTool::get($portal_id)))
			return;
		
		if(false == ($extension = $portal->getExtension()))
			return;
		
		if($extension instanceof Extension_CommunityPortal && method_exists($extension, $tab_action.'Action')) {
			call_user_func(array($extension, $tab_action.'Action'));
		}
	}
};
