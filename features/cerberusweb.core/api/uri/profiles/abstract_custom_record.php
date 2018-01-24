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

class PageSection_ProfilesAbstractCustomRecord extends Extension_PageSection {
	static private function _getContextName() {
		return 'contexts.custom_record.' . static::_ID;
	}
	
	function render() {
		// This shouldn't be called directly
		if(get_called_class() == 'PageSection_ProfilesAbstractCustomRecord')
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // uri 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($custom_record = DAO_CustomRecord::get(static::_ID)))
			return;
		
		$dao_class = sprintf('DAO_AbstractCustomRecord_%d', static::_ID);
		
		if(null == ($abstract_record = $dao_class::get($id))) {
			return;
		}
		
		$tpl->assign('custom_record', $custom_record);
		$tpl->assign('abstract_custom_record', $abstract_record);
		
		$context = self::_getContextName();
		$tpl->assign('page_context', $context);
		
		// Tab persistence
		
		$point = sprintf('profiles.abstract_custom_record_%d.tab', static::_ID);
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $abstract_record->name,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $abstract_record->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $abstract_record->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $abstract_record->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$abstract_record->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$abstract_record->id,
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
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/abstract_custom_record/profile.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$record_id = DevblocksPlatform::importGPC($_REQUEST['_record_id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$record_id || false == ($custom_record = DAO_CustomRecord::get($record_id)))
				throw new Exception_DevblocksAjaxValidationError("Invalid record type.", '_record_id');
			
			$dao_class = $custom_record->getDaoClass();
			$context = $custom_record->getContext();
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(
					!CerberusContexts::isWriteableByActor($context, $id, $active_worker)
					|| !$active_worker->hasPriv(sprintf("contexts.%s.delete", $context))
					)
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				$dao_class::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
				
				// Owner
			
				$owner_ctx = '';
				@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
				
				// Make sure we're given a valid ctx
				
				switch($owner_ctx) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_ctx = null;
				}
				
				$fields = [
					$dao_class::UPDATED_AT => time(),
					$dao_class::NAME => $name,
					$dao_class::OWNER_CONTEXT => $owner_ctx,
					$dao_class::OWNER_CONTEXT_ID => intval($owner_ctx_id),
				];
				
				// DAO
				
				if(empty($id)) { // New
					if(!$dao_class::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$dao_class::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = $dao_class::create($fields);
					$dao_class::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);
					
				} else { // Edit
					if(!$dao_class::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$dao_class::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$dao_class::update($id, $fields);
					$dao_class::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', []);
				DAO_CustomFieldValue::handleFormPost($context, $id, $field_ids);
				
				// Avatar image
				@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
				DAO_ContextAvatar::upsertWithImage($context, $id, $avatar_image);
				
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
		
		// Abstraction
		
		$context = self::_getContextName();
		
		if(false == ($custom_record = DAO_CustomRecord::get(static::_ID)))
			return;
		
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
				$model->params = [
					'title' => $view->name,
					'created' => time(),
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy(sprintf('c=search&type=%s', $custom_record->uri), true),
					'toolbar_extension_id' => sprintf('%s.explore.toolbar', self::_getContextName()),
				];
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=%s&id=%d-%s", $custom_record->uri, $row[SearchFields_AbstractCustomRecord::ID], DevblocksPlatform::strToPermalink($row[SearchFields_AbstractCustomRecord::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_AbstractCustomRecord::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(['explore', $hash, $orig_pos]));
	}
};
