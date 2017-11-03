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

class PageSection_ProfilesMailTransport extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mail_transport
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($mail_transport = DAO_MailTransport::get($id))) {
			return;
		}
		$tpl->assign('mail_transport', $mail_transport);
	
		// Tab persistence
		
		$point = 'profiles.mail_transport.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $mail_transport->name,
		);
			
		$properties['extension'] = array(
			'label' => mb_ucfirst($translate->_('common.extension')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $mail_transport->extension_id,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $mail_transport->created_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $mail_transport->updated_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MAIL_TRANSPORT, $mail_transport->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_MAIL_TRANSPORT, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_MAIL_TRANSPORT, $mail_transport->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_MAIL_TRANSPORT => array(
				$mail_transport->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_MAIL_TRANSPORT,
						$mail_transport->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_MAIL_TRANSPORT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/mail_transport.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		// Only admins can edit mail transports
		if(!$active_worker->is_superuser) {
			throw new Exception_DevblocksAjaxValidationError("Only administrators can modify email transport records.");
		}
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_MAIL_TRANSPORT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_MailTransport::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string',null);
				@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string',null);
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'][$extension_id],'array',[]);
				
				if(empty($id)) { // New
					$fields = array(
						DAO_MailTransport::EXTENSION_ID => $extension_id,
						DAO_MailTransport::NAME => $name,
						DAO_MailTransport::PARAMS_JSON => json_encode($params),
						DAO_MailTransport::UPDATED_AT => time(),
					);
					
					if(!DAO_MailTransport::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailTransport::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$this->_testTransportParamsAction($extension_id, $params, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_MailTransport::create($fields);
					DAO_MailTransport::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_MAIL_TRANSPORT, $id);
					
				} else { // Edit
					$fields = array(
						DAO_MailTransport::EXTENSION_ID => $extension_id,
						DAO_MailTransport::NAME => $name,
						DAO_MailTransport::PARAMS_JSON => json_encode($params),
						DAO_MailTransport::UPDATED_AT => time(),
					);
					
					if(!DAO_MailTransport::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailTransport::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$this->_testTransportParamsAction($extension_id, $params, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_MailTransport::update($id, $fields);
					DAO_MailTransport::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAIL_TRANSPORT, $id, $field_ids);
				
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
	
	function getTransportParamsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string',null);
		
		if(false == ($mail_transport_ext = Extension_MailTransport::get($extension_id)))
			return;
		
		if(empty($id) || false == ($model = DAO_MailTransport::get($id))) {
			$model = new Model_MailTransport();
			$model->extension_id = $mail_transport_ext->id;
		}
		
		$mail_transport_ext->renderConfig($model);
		
		exit;
	}
	
	private function _testTransportParamsAction($extension_id, array $params, &$error=null) {
		try {
			if(empty($extension_id) || false == ($mail_transport_ext = Extension_MailTransport::get($extension_id))) {
				$error = 'The "transport" field is required.';
				return false;
			}
			
			// Test the transport specfic parameters
			if(false == $mail_transport_ext->testConfig($params, $error)) {
				return false;
			}
			
		} catch(Exception $e) {
			$error = 'A problem occurred. Please check your settings and try again.';
			return false;
		}
		
		$error = null;
		return true;
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=mail_transport', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.mail.transport.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=mail_transport&id=%d-%s", $row[SearchFields_MailTransport::ID], DevblocksPlatform::strToPermalink($row[SearchFields_MailTransport::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_MailTransport::ID],
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
