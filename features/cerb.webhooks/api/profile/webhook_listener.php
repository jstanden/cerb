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

class PageSection_ProfilesWebhookListener extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // webhook_listener
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($webhook_listener = DAO_WebhookListener::get($id))) {
			return;
		}
		$tpl->assign('webhook_listener', $webhook_listener);
	
		// Tab persistence
		
		$point = 'profiles.webhook_listener.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		// [TODO] Translate type
		$properties['extension_id'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $webhook_listener->extension_id,
		);
	
		// [TODO] HREF?
		$properties['guid'] = array(
			'label' => mb_ucfirst($translate->_('common.guid')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $webhook_listener->guid,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $webhook_listener->updated_at,
		);
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WEBHOOK_LISTENER, $webhook_listener->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_WEBHOOK_LISTENER, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_WEBHOOK_LISTENER, $webhook_listener->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_WEBHOOK_LISTENER => array(
				$webhook_listener->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_WEBHOOK_LISTENER,
						$webhook_listener->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_WEBHOOK_LISTENER);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerb.webhooks::webhook_listener/profile.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_WEBHOOK_LISTENER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_WebhookListener::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
				@$extension_params = DevblocksPlatform::importGPC($_REQUEST['extension_params'], 'array', array());
				
				$extension_params = @$extension_params[$extension_id] ?: array();
				$extension_params_json = json_encode(is_array($extension_params) ? $extension_params : array());
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
				if(empty($id)) { // New
					if(!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_WEBHOOK_LISTENER)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
				
					$fields = array(
						DAO_WebhookListener::UPDATED_AT => time(),
						DAO_WebhookListener::NAME => $name,
						DAO_WebhookListener::GUID => sha1($name . time() . mt_rand(0,10000)),
						DAO_WebhookListener::EXTENSION_ID => $extension_id,
						DAO_WebhookListener::EXTENSION_PARAMS_JSON => $extension_params_json,
					);
					
					if(!DAO_WebhookListener::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_WebhookListener::create($fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WEBHOOK_LISTENER, $id);
					
				} else { // Edit
					if(!$active_worker->hasPriv(sprintf("contexts.%s.update", CerberusContexts::CONTEXT_WEBHOOK_LISTENER)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
						
					$fields = array(
						DAO_WebhookListener::UPDATED_AT => time(),
						DAO_WebhookListener::NAME => $name,
						DAO_WebhookListener::EXTENSION_ID => $extension_id,
						DAO_WebhookListener::EXTENSION_PARAMS_JSON => $extension_params_json,
					);
					
					if(!DAO_WebhookListener::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_WebhookListener::update($id, $fields);
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', []);
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WEBHOOK_LISTENER, $id, $field_ids);
				
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
	
	function savePeekOLD() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_WebhookListener::delete($id);
			
		} else {
			@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
			@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
			@$extension_params = DevblocksPlatform::importGPC($_REQUEST['extension_params'], 'array', array());
			
			$extension_params = @$extension_params[$extension_id] ?: array();
			$extension_params_json = json_encode(is_array($extension_params) ? $extension_params : array());
			
			if(empty($id)) { // New
				$fields = array(
					DAO_WebhookListener::UPDATED_AT => time(),
					DAO_WebhookListener::NAME => $name,
					DAO_WebhookListener::GUID => sha1($name . time() . mt_rand(0,10000)),
					DAO_WebhookListener::EXTENSION_ID => $extension_id,
					DAO_WebhookListener::EXTENSION_PARAMS_JSON => $extension_params_json,
				);
				$id = DAO_WebhookListener::create($fields);
				
				if(!empty($view_id) && !empty($id))
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WEBHOOK_LISTENER, $id);
				
			} else { // Edit
				$fields = array(
					DAO_WebhookListener::UPDATED_AT => time(),
					DAO_WebhookListener::NAME => $name,
					DAO_WebhookListener::EXTENSION_ID => $extension_id,
					DAO_WebhookListener::EXTENSION_PARAMS_JSON => $extension_params_json,
				);
				DAO_WebhookListener::update($id, $fields);
				
			}

			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WEBHOOK_LISTENER, $id, $field_ids);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=webhook_listener', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.webhook_listener.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=webhook_listener&id=%d-%s", $row[SearchFields_WebhookListener::ID], DevblocksPlatform::strToPermalink($row[SearchFields_WebhookListener::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_WebhookListener::ID],
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
