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

class PageSection_ProfilesCustomFieldset extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // custom_fieldset 
		@$id = intval(array_shift($stack)); // 123
		
		if(null == ($custom_fieldset = DAO_CustomFieldset::get($id))) {
			return;
		}
		$tpl->assign('custom_fieldset', $custom_fieldset);

		// Context

		$context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;

		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;

		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $custom_fieldset, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Tab persistence
		
		$point = 'profiles.custom_fieldset.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $custom_fieldset->owner_context_id,
			'params' => [
				'context' => $custom_fieldset->owner_context,
			]
		);
		
		$properties['updated_date'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $custom_fieldset->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $custom_fieldset->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $custom_fieldset->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$custom_fieldset->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$custom_fieldset->id,
						array($context)
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
		$tpl->display('devblocks:cerberusweb.core::profiles/custom_fieldset.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'],'string','');
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CUSTOM_FIELDSET)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_CustomFieldset::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				// Owner
				@list($owner_context, $owner_context_id) = explode(':', $owner);
			
				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_BOT:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}
				
				if(empty($id)) { // New
					$fields = array(
						DAO_CustomFieldset::NAME => $name,
						DAO_CustomFieldset::CONTEXT => $context,
						DAO_CustomFieldset::OWNER_CONTEXT => $owner_context,
						DAO_CustomFieldset::OWNER_CONTEXT_ID => $owner_context_id,
					);
					
					if(!DAO_CustomFieldset::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CustomFieldset::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_CustomFieldset::create($fields);
					DAO_CustomFieldset::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $id);
					
				} else { // Edit
					$fields = array(
						DAO_CustomFieldset::NAME => $name,
						DAO_CustomFieldset::OWNER_CONTEXT => $owner_context,
						DAO_CustomFieldset::OWNER_CONTEXT_ID => $owner_context_id,
					);
					
					if(!DAO_CustomFieldset::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CustomFieldset::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CustomFieldset::update($id, $fields);
					DAO_CustomFieldset::onUpdateByActor($active_worker, $fields, $id);
				}
				
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=custom_fieldset', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.custom.fieldset.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=custom_fieldset&id=%d-%s", $row[SearchFields_CustomFieldset::ID], DevblocksPlatform::strToPermalink($row[SearchFields_CustomFieldset::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_CustomFieldset::ID],
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
