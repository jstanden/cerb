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

class PageSection_ProfilesMailHtmlTemplate extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mail_html_template
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($mail_html_template = DAO_MailHtmlTemplate::get($id))) {
			return;
		}
		$tpl->assign('mail_html_template', $mail_html_template);
	
		// Tab persistence
		
		$point = 'profiles.mail_html_template.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $mail_html_template->updated_at,
		);
		
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $mail_html_template->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $mail_html_template->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE => array(
				$mail_html_template->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE,
						$mail_html_template->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/mail_html_template.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		// Only admins can edit mail templates
		if(!$active_worker->is_superuser) {
			throw new Exception_DevblocksAjaxValidationError("Only administrators can modify email template records.");
		}
		
		try {
			if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE)))
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_MailHtmlTemplate::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$content = DevblocksPlatform::importGPC($_REQUEST['content'], 'string', '');
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$signature = DevblocksPlatform::importGPC($_REQUEST['signature'], 'string', '');
				
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
					if(!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
					if(!DAO_MailHtmlTemplate::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_MailHtmlTemplate::create($fields)))
						return false;
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id);
					
				} else { // Edit
					if(!$active_worker->hasPriv(sprintf("contexts.%s.update", CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					if(!DAO_MailHtmlTemplate::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_MailHtmlTemplate::update($id, $fields);
					
				}
				
				if($id) {
					// Custom fields
					@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id, $field_ids);
					
					// Files
					@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_ids'], 'array', array());
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
	
	function getSignatureParsedownPreviewAction() {
		@$signature = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: text/html; charset=' . LANG_CHARSET_CODE);
		
		// Token substitution
		
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $labels, $values, null, true, true);
		$dict = new DevblocksDictionaryDelegate($values);
		
		$signature = $tpl_builder->build($signature, $dict);
		
		// Parsedown
		
		$output = DevblocksPlatform::parseMarkdown($signature);
		
		echo $output;
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=html_template', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.mail.html_template.explore.toolbar',
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
