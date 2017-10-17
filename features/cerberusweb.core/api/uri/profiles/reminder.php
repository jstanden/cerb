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

class PageSection_ProfilesReminder extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // reminder 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($reminder = DAO_Reminder::get($id))) {
			return;
		}
		$tpl->assign('reminder', $reminder);
		
		// Tab persistence
		
		$point = 'profiles.reminder.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['remind_at'] = array(
			'label' => mb_ucfirst($translate->_('common.remind_at')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $reminder->remind_at,
		);
		
		$properties['worker_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.worker'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $reminder->worker_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_WORKER,
			]
		);
		
		$properties['is_closed'] = array(
			'label' => mb_ucfirst($translate->_('common.is_closed')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $reminder->is_closed,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $reminder->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_REMINDER, $reminder->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_REMINDER, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_REMINDER, $reminder->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_REMINDER => array(
				$reminder->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_REMINDER,
						$reminder->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_REMINDER);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/reminder.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_REMINDER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!Context_Reminder::isWriteableByActor($id, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Reminder::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$remind_at = DevblocksPlatform::importGPC($_REQUEST['remind_at'], 'string', '');
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
				@$behavior_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['behavior_ids'], 'array', []), 'int');
				@$behaviors_params = DevblocksPlatform::importGPC($_REQUEST['behavior_params'], 'array', []);
				
				$remind_at = !empty($remind_at) ? @strtotime($remind_at) : '';
				$is_closed = ($remind_at && $remind_at <= time()) ? 1 : 0;
				
				// Behaviors
				
				$params = [
					'behaviors' => [],
				];

				$behaviors = DAO_TriggerEvent::getIds($behavior_ids);
				
				foreach($behaviors as $behavior_id => $behavior) {
					$behavior_params = @$behaviors_params[$behavior_id] ?: [];
					$params['behaviors'][$behavior_id] = $behavior_params;
				}
				
				if(empty($id)) { // New
					if(!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_REMINDER)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
				
					$fields = array(
						DAO_Reminder::IS_CLOSED => $is_closed,
						DAO_Reminder::NAME => $name,
						DAO_Reminder::PARAMS_JSON => json_encode($params),
						DAO_Reminder::REMIND_AT => $remind_at,
						DAO_Reminder::UPDATED_AT => time(),
						DAO_Reminder::WORKER_ID => $worker_id,
					);
					
					if(!DAO_Reminder::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Reminder::create($fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_REMINDER, $id);
					
				} else { // Edit
					if(!$active_worker->hasPriv(sprintf("contexts.%s.update", CerberusContexts::CONTEXT_REMINDER)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					if(false == ($reminder = DAO_Reminder::get($id)))
						throw new Exception_DevblocksAjaxValidationError("Invalid record.");
					
					if(!Context_Reminder::isWriteableByActor($reminder, $active_worker))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
						
					$fields = array(
						DAO_Reminder::IS_CLOSED => $is_closed,
						DAO_Reminder::NAME => $name,
						DAO_Reminder::PARAMS_JSON => json_encode($params),
						DAO_Reminder::REMIND_AT => $remind_at,
						DAO_Reminder::UPDATED_AT => time(),
						DAO_Reminder::WORKER_ID => $worker_id,
					);
					
					if(!DAO_Reminder::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Reminder::update($id, $fields);
					
				}
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', []);
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_REMINDER, $id, $field_ids);
				
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=reminder', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.reminder.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=reminder&id=%d-%s", $row[SearchFields_Reminder::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Reminder::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Reminder::ID],
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
