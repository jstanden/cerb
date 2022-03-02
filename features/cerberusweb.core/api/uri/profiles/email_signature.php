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

class PageSection_ProfilesEmailSignature extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // email_signature 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'preview':
					return $this->_profileAction_preview();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_EMAIL_SIGNATURE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_EmailSignature::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_EmailSignature::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, $model->id, $model->name);
				
				DAO_EmailSignature::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$owner = DevblocksPlatform::importGPC($_POST['owner'] ?? null, 'string', '');
				$signature = DevblocksPlatform::importGPC($_POST['signature'] ?? null, 'string', '');
				$signature_html = DevblocksPlatform::importGPC($_POST['signature_html'] ?? null, 'string', '');
				$file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['file_ids'] ?? null,'array',array()), 'int');
				
				$error = null;
				
				// Owner
				
				list($owner_ctx, $owner_ctx_id) = array_pad(explode(':', $owner, 2), 2, null);
				
				// Make sure we're given a valid ctx
				
				switch($owner_ctx) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
						break;
						
					default:
						$owner_ctx = null;
				}
				
				$fields = array(
					DAO_EmailSignature::NAME => $name,
					DAO_EmailSignature::SIGNATURE => $signature,
					DAO_EmailSignature::SIGNATURE_HTML => $signature_html,
					DAO_EmailSignature::UPDATED_AT => time(),
				);
				
				$fields[DAO_EmailSignature::OWNER_CONTEXT] = $owner_ctx;
				$fields[DAO_EmailSignature::OWNER_CONTEXT_ID] = $owner_ctx_id;
				
				if(empty($id)) { // New
					if(!DAO_EmailSignature::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_EmailSignature::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_EmailSignature::create($fields);
					DAO_EmailSignature::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_EMAIL_SIGNATURE, $id);
					
				} else { // Edit
					if(!DAO_EmailSignature::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_EmailSignature::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_EmailSignature::update($id, $fields);
					DAO_EmailSignature::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Add attachments
					DAO_Attachment::setLinks(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, $id, $file_ids);
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_EMAIL_SIGNATURE, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
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
	
	private function _profileAction_preview() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$tpl = DevblocksPlatform::services()->template();
		
		$signature = DevblocksPlatform::importGPC($_REQUEST['signature'] ?? null, 'string');
		$format = DevblocksPlatform::importGPC($_REQUEST['format'] ?? null, 'string');
		
		$dict = DevblocksDictionaryDelegate::instance([
			'_context' => CerberusContexts::CONTEXT_WORKER,
			'id' => $active_worker->id,
		]);
		
		if(false === ($signature = $tpl_builder->build($signature, $dict))) {
			return;
		}
		
		if('markdown' == $format) {
			$signature = DevblocksPlatform::parseMarkdown($signature);
			
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			$signature = DevblocksPlatform::purifyHTML($signature, true, true, [$filter]);
			
		} else {
			$signature = DevblocksPlatform::strEscapeHtml($signature);
			$signature = nl2br($signature);
		}
		
		$tpl->assign('css_class', 'emailBodyHtmlLight');
		$tpl->assign('content', $signature);
		$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'integer',0);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=email_signature', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=email_signature&id=%d-%s", $row[SearchFields_EmailSignature::ID], DevblocksPlatform::strToPermalink($row[SearchFields_EmailSignature::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_EmailSignature::ID],
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
