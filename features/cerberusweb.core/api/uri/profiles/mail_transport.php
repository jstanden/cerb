<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mail_transport
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_MAIL_TRANSPORT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
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
				@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
				@$name = DevblocksPlatform::importGPC($_POST['name'],'string',null);
				@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string',null);
				@$params = DevblocksPlatform::importGPC($_POST['params'][$extension_id],'array',[]);
				
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
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAIL_TRANSPORT, $id, $field_ids, $error))
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
