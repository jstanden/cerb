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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_MailHtmlTemplate::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_MailHtmlTemplate::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $model->id, $model->name);
				
				DAO_MailHtmlTemplate::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$content = DevblocksPlatform::importGPC($_POST['content'] ?? null, 'string', '');
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$signature_id = DevblocksPlatform::importGPC($_POST['signature_id'] ?? null, 'integer', 0);
				
				$owner_ctx = CerberusContexts::CONTEXT_APPLICATION;
				$owner_ctx_id = 0;
				
				$fields = array(
					DAO_MailHtmlTemplate::CONTENT => $content,
					DAO_MailHtmlTemplate::NAME => $name,
					DAO_MailHtmlTemplate::OWNER_CONTEXT => $owner_ctx,
					DAO_MailHtmlTemplate::OWNER_CONTEXT_ID => $owner_ctx_id,
					DAO_MailHtmlTemplate::SIGNATURE_ID => $signature_id,
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
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Files
					$file_ids = DevblocksPlatform::importGPC($_POST['file_ids'] ?? null, 'array', array());
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
		
		$template = DevblocksPlatform::importGPC($_REQUEST['template'] ?? null, 'string', '');
		
		$random_group = DAO_Group::get(DAO_Group::random());
		
		$message_body = <<< EOD
<h1>Heading 1</h1>
<h2>Heading 2</h2>
<h3>Heading 3</h3>
<h4>Heading 4</h4>
<h5>Heading 5</h5>
<h6>Heading 6</h6>
<p>This text contains <b>bold</b>, <i>italics</i>, <a href="https://cerb.ai/">links</a>, and <code>code formatting</code>.</p>
<blockquote>This text is quoted.</blockquote>
<p>This is an unordered list:<ul><li>red</li><li>green</li><li>blue</li></ul></p>
<p>This is an ordered list:<ol><li>one</li><li>two</li><li>three</li></ol></p>
<p>Some preformatted text:</p>
<pre><code>function double(\$n) {
  return intval(\$n) * 2;
}
</code></pre>
EOD;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'message_body' => $message_body,
			'group__context' => CerberusContexts::CONTEXT_GROUP,
			'group_id' => $random_group->id ?? 0,
			'bucket__context' => CerberusContexts::CONTEXT_BUCKET,
			'bucket_id' => @$random_group->getDefaultBucket()->id ?? 0,
			'message_id_header' => sprintf("<%s@message.example>", sha1(random_bytes(32))),
		]);
		
		$output = $tpl_builder->build($template, $dict);
		
		$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
		$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
		
		$tpl->assign('css_class', 'emailBodyHtmlLight');
		$tpl->assign('content', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
