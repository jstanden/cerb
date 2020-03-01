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

class PageSection_ProfilesMailHtmlTemplate extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mail_html_template
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'preview':
					return $this->_profileAction_preview();
				case 'previewSignature':
					return $this->_profileAction_previewSignature();
				case 'getSignatureParsedownPreview':
					return $this->_profileAction_getSignatureParsedownPreview();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_MailHtmlTemplate::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_MailHtmlTemplate::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_MAIL_ROUTING_RULE, $model->id, $model->name);
				
				DAO_MailHtmlTemplate::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$content = DevblocksPlatform::importGPC($_POST['content'], 'string', '');
				@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
				@$signature = DevblocksPlatform::importGPC($_POST['signature'], 'string', '');
				
				$owner_ctx = CerberusContexts::CONTEXT_APPLICATION;
				$owner_ctx_id = 0;
				
				$fields = array(
					DAO_MailHtmlTemplate::CONTENT => $content,
					DAO_MailHtmlTemplate::NAME => $name,
					DAO_MailHtmlTemplate::OWNER_CONTEXT => $owner_ctx,
					DAO_MailHtmlTemplate::OWNER_CONTEXT_ID => $owner_ctx_id,
					DAO_MailHtmlTemplate::SIGNATURE => $signature,
					DAO_MailHtmlTemplate::UPDATED_AT => time(),
				);
				
				if(empty($id)) { // New
					if(!DAO_MailHtmlTemplate::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailHtmlTemplate::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_MailHtmlTemplate::create($fields)))
						return false;
					
					DAO_MailHtmlTemplate::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id);
					
				} else { // Edit
					if(!DAO_MailHtmlTemplate::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailHtmlTemplate::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_MailHtmlTemplate::update($id, $fields);
					DAO_MailHtmlTemplate::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Files
					@$file_ids = DevblocksPlatform::importGPC($_POST['file_ids'], 'array', array());
					if(is_array($file_ids))
						DAO_Attachment::setLinks(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id, $file_ids);
				}
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $name,
				'view_id' => $view_id,
			));
			return;
			
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
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$template = DevblocksPlatform::importGPC($_REQUEST['template'],'string', '');
		
		$dict = DevblocksDictionaryDelegate::instance([
			'message_body' => '<blockquote>This text is quoted.</blockquote><p>This text contains <b>bold</b>, <i>italics</i>, <a href="javascript:;">links</a>, and <code>code formatting</code>.</p><p><ul><li>These are unordered</li><li>list items</li></ul></p>',
		]);
		
		$output = $tpl_builder->build($template, $dict);
		
		$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
		$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
		
		$tpl->assign('content', $output);
		
		$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
	}
	
	private function _profileAction_previewSignature() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$signature = DevblocksPlatform::importGPC($_REQUEST['signature'],'string', '');
		
		$dict = DevblocksDictionaryDelegate::instance([
			'_context' => CerberusContexts::CONTEXT_WORKER,
			'id' => $active_worker->id,
		]);
		
		$output = $tpl_builder->build($signature, $dict);
		
		$output = DevblocksPlatform::parseMarkdown($output);
		
		$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
		$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
		
		$tpl->assign('content', $output);
		
		$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
	}
	
	private function _profileAction_getSignatureParsedownPreview() {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$signature = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');
		
		header('Content-Type: text/html; charset=' . LANG_CHARSET_CODE);
		
		// Token substitution
		
		$labels = $values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $labels, $values, null, true, true);
		$dict = new DevblocksDictionaryDelegate($values);
		
		$signature = $tpl_builder->build($signature, $dict);
		
		// Parsedown
		
		$output = DevblocksPlatform::parseMarkdown($signature);
		
		echo $output;
	}
	
	private function _profileAction_viewExplore() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
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
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=html_template', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=html_template&id=%d-%s", $row[SearchFields_MailHtmlTemplate::ID], DevblocksPlatform::strToPermalink($row[SearchFields_MailHtmlTemplate::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_MailHtmlTemplate::ID],
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
