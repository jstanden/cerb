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

class PageSection_ProfilesBucket extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // bucket
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($bucket = DAO_Bucket::get($id)))
			return;
		
		$tpl->assign('bucket', $bucket);
	
		// Tab persistence
		
		$point = 'profiles.bucket.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['group'] = array(
			'label' => mb_ucfirst($translate->_('common.group')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_GROUP),
			'value' => $bucket->group_id,
		);
			
		$properties['updated'] = array(
			'label' => mb_ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $bucket->updated_at,
		);
		
		$properties['is_default'] = array(
			'label' => mb_ucfirst($translate->_('common.default')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $bucket->is_default,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_BUCKET, $bucket->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_BUCKET, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_BUCKET, $bucket->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_BUCKET => array(
				$bucket->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_BUCKET,
						$bucket->id,
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
			'event.macro.bucket'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_BUCKET);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/bucket.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=' . LANG_CHARSET_CODE);
		
		try {
			
			if($id && false == ($bucket = DAO_Bucket::get($id)))
				throw new Exception_DevblocksAjaxValidationError("The specified bucket record doesn't exist.");
			
			// ACL
			if($id && !$active_worker->is_superuser && !$active_worker->isGroupManager($bucket->group_id))
				throw new Exception_DevblocksAjaxValidationError("You do not have permission to delete this bucket.");
			
			if($id && !empty($do_delete)) { // Delete
				@$delete_moveto = DevblocksPlatform::importGPC($_REQUEST['delete_moveto'],'integer',0);
				$buckets = DAO_Bucket::getAll();
				
				// Destination must exist
				if(empty($delete_moveto) || false == ($bucket_moveto = DAO_Bucket::get($delete_moveto)))
					throw new Exception_DevblocksAjaxValidationError("The destination bucket doesn't exist.");
				
				$where = sprintf("%s = %d", DAO_Ticket::BUCKET_ID, $id);
				DAO_Ticket::updateWhere(array(DAO_Ticket::BUCKET_ID => $bucket_moveto->id), $where);
				DAO_Bucket::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
				@$reply_address_id = DevblocksPlatform::importGPC($_REQUEST['reply_address_id'],'integer',0);
				@$reply_personal = DevblocksPlatform::importGPC($_REQUEST['reply_personal'],'string','');
				@$reply_signature = DevblocksPlatform::importGPC($_REQUEST['reply_signature'],'string','');
				@$reply_html_template_id = DevblocksPlatform::importGPC($_REQUEST['reply_html_template_id'],'integer',0);
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
				if(empty($id)) { // New
					@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
					
					if(!$group_id || false == ($group = DAO_Group::get($group_id)))
						throw new Exception_DevblocksAjaxValidationError("The specified group doesn't exist.", 'group_id');
					
					$fields = array(
						DAO_Bucket::NAME => $name,
						DAO_Bucket::GROUP_ID => $group_id,
						DAO_Bucket::REPLY_ADDRESS_ID => $reply_address_id,
						DAO_Bucket::REPLY_PERSONAL => $reply_personal,
						DAO_Bucket::REPLY_SIGNATURE => $reply_signature,
						DAO_Bucket::REPLY_HTML_TEMPLATE_ID => $reply_html_template_id,
						DAO_Bucket::UPDATED_AT => time(),
					);
					$id = DAO_Bucket::create($fields);
					
					// Default bucket responsibilities
					DAO_Group::setBucketDefaultResponsibilities($id);
					
					// Context Link (if given)
					@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
					@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
					if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
						DAO_ContextLink::setLink(CerberusContexts::CONTEXT_BUCKET, $id, $link_context, $link_context_id);
					}
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BUCKET, $id);
					
				} else { // Edit
					$fields = array(
						DAO_Bucket::NAME => $name,
						DAO_Bucket::REPLY_ADDRESS_ID => $reply_address_id,
						DAO_Bucket::REPLY_PERSONAL => $reply_personal,
						DAO_Bucket::REPLY_SIGNATURE => $reply_signature,
						DAO_Bucket::REPLY_HTML_TEMPLATE_ID => $reply_html_template_id,
						DAO_Bucket::UPDATED_AT => time(),
					);
					DAO_Bucket::update($id, $fields);
					
				}
	
				// Custom fields
				
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BUCKET, $id, $field_ids);
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
					'id' => $id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
				return;
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=bucket', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.bucket.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=bucket&id=%d-%s", $row[SearchFields_Bucket::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Bucket::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Bucket::ID],
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
