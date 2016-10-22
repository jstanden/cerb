<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesConnectedAccount extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // connected_account
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($connected_account = DAO_ConnectedAccount::get($id))) {
			return;
		}
		
		if(!Context_ConnectedAccount::isReadableByActor($connected_account, $active_worker)) {
			echo DevblocksPlatform::translateCapitalized('common.access_denied');
			return;
		}
		
		$tpl->assign('connected_account', $connected_account);
	
		// Tab persistence
		
		$point = 'profiles.connected_account.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $connected_account->owner_context_id,
			'params' => [
				'context' => $connected_account->owner_context,
			],
		);
			
		$properties['extension'] = array(
			'label' => mb_ucfirst($translate->_('common.service.provider')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $connected_account->extension_id,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $connected_account->created_at,
		);
		
		$properties['updated'] = array(
			'label' => mb_ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $connected_account->updated_at,
		);
			
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $connected_account->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $connected_account->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CONNECTED_ACCOUNT => array(
				$connected_account->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CONNECTED_ACCOUNT,
						$connected_account->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.connected_account'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/connected_account.tpl');
	}
	
	function showPeekPopupAction() {
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($connected_account = DAO_ConnectedAccount::get($context_id))) {
			$tpl->assign('model', $connected_account);
		}
		
		/*
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		*/

		// Comments
		/*
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $context_id);
		$comments = array_reverse($comments, true);
		$tpl->assign('comments', $comments);
		*/
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_account/peek.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_ConnectedAccount::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
				if(empty($id)) { // New
					throw new Exception_DevblocksAjaxValidationError("This form can't create new accounts.");
					
				} else { // Edit
					$fields = array(
						DAO_ConnectedAccount::NAME => $name,
						DAO_ConnectedAccount::UPDATED_AT => time(),
					);
					DAO_ConnectedAccount::update($id, $fields);
					
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $id, $field_ids);
				
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=connected_account', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.connected_account.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=connected_account&id=%d-%s", $row[SearchFields_ConnectedAccount::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ConnectedAccount::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ConnectedAccount::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function authAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
		
		// [TODO] Verify auth
		
		// Load the extension
		if(false == ($ext = Extension_ServiceProvider::get($extension_id)))
			DevblocksPlatform::dieWithHttpError("Invalid extension.");
		
		$ext->renderPopup();
	}
	
	function saveAuthFormJsonAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'], 'string', '');
		
		header('Content-Type: application/json; charset=' . LANG_CHARSET_CODE);
		
		// Load the extension
		if(false == ($ext = Extension_ServiceProvider::get($extension_id))) {
			echo json_encode(array(
				'status' => false,
				'error' => "Invalid extension.",
			));
			return;
		}
		
		if(!($ext instanceof IServiceProvider_Popup)) {
			echo json_encode(array(
				'status' => false,
				'error' => "Invalid extension.",
			));
			return;
		}
		
		/* @var $ext IServiceProvider_Popup */
		$json = $ext->saveAuthFormAndReturnJson();
		echo $json;
	}
};
