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
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
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
		
		$macros = DAO_TriggerEvent::getUsableMacrosByWorker(
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
				@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', null);
				@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', null);
				
				$account = new Model_ConnectedAccount();
				$account->id = 0;
				$account->extension_id = $extension_id;
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
				// Edit
				if($id) {
					if(false == ($account = DAO_ConnectedAccount::get($id))
						|| !Context_ConnectedAccount::isWriteableByActor($account, $active_worker)
						)
						throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this record.");
						
					if(false == ($extension = $account->getExtension()))
						throw new Exception_DevblocksAjaxValidationError("Invalid service provider.");
					
					$fields = array(
						DAO_ConnectedAccount::NAME => $name,
						DAO_ConnectedAccount::UPDATED_AT => time(),
					);
					
					// Owner (only admins)
					if(!empty($owner) && $active_worker->is_superuser) {
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
						
						if(empty($owner_ctx))
							throw new Exception_DevblocksAjaxValidationError("A valid 'Owner' is required.");
						
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT] = $owner_ctx;
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT_ID] = $owner_ctx_id;
					}
				
				// Create
				} else {
					if(false == ($extension = Extension_ServiceProvider::get($extension_id)))
						throw new Exception_DevblocksAjaxValidationError("Invalid service provider.");
					
					$fields = array(
						DAO_ConnectedAccount::NAME => $name,
						DAO_ConnectedAccount::UPDATED_AT => time(),
						DAO_ConnectedAccount::EXTENSION_ID => $extension_id,
					);
					
					// Owner (only admins)
					if(!empty($owner) && $active_worker->is_superuser) {
						$owner_ctx = '';
						@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
						
						// Make sure we're given a valid ctx
						
						switch($owner_ctx) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								$fields[DAO_ConnectedAccount::OWNER_CONTEXT] = $owner_ctx;
								$fields[DAO_ConnectedAccount::OWNER_CONTEXT_ID] = $owner_ctx_id;
								break;
						}
					}
					
					// Use the current worker as the owner by default
					if(!isset($fields[DAO_ConnectedAccount::OWNER_CONTEXT])) {
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT] = CerberusContexts::CONTEXT_WORKER;
						$fields[DAO_ConnectedAccount::OWNER_CONTEXT_ID] = $active_worker->id;
					}
				}
				
				// Custom params
					
				$params = $account->decryptParams($active_worker) ?: [];
				
				if(true !== ($result = $extension->saveConfigForm($account, $params)))
					throw new Exception_DevblocksAjaxValidationError($result);
				
				if(empty($id)) {
					$id = DAO_ConnectedAccount::create($fields);
					
					if($view_id && $id) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $id);
					}
					
				} else {
					DAO_ConnectedAccount::update($id, $fields);
				}

				if($id) {
					// Encrypt params
					DAO_ConnectedAccount::setAndEncryptParams($id, $params);
					
					// Custom fields
					@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $id, $field_ids);
				
					echo json_encode(array(
						'status' => true,
						'id' => $id,
						'label' => $name,
						'view_id' => $view_id,
					));
				}
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
		
		// Load the extension
		if(false == ($ext = Extension_ServiceProvider::get($extension_id)))
			DevblocksPlatform::dieWithHttpError("Invalid extension.");
		
		if(!$ext instanceof IServiceProvider_OAuth)
			return;
			
		$ext->oauthRender();
	}
};
