<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
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

class PageSection_ProfilesKbArticle extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();
		$translate = DevblocksPlatform::getTranslationService();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // article
		@$id = intval(array_shift($stack));
		
		if(null == ($article = DAO_KbArticle::get($id))) {
			return;
		}
		$tpl->assign('article', $article);	/* @var $article Model_KbArticle */
		
		$point = 'cerberusweb.profiles.kb';
		$tpl->assign('point', $point);
		
		// Categories
		
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$breadcrumbs = $article->getCategories();
		$tpl->assign('breadcrumbs', $breadcrumbs);
			
		// Properties
			
		$properties = array();
			
		$properties['updated'] = array(
			'label' => mb_ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $article->updated,
		);
			
		$properties['views'] = array(
			'label' => mb_ucfirst($translate->_('kb_article.views')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $article->views,
		);
			
		$properties['id'] = array(
			'label' => mb_ucfirst($translate->_('common.id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $article->id,
		);
			
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $article->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_KB_ARTICLE, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_KB_ARTICLE, $article->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_KB_ARTICLE => array(
				$article->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_KB_ARTICLE,
						$article->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.kb_article'
		);
		$tpl->assign('macros', $macros);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_KB_ARTICLE);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Attachments
		
		$attachments_map = DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_KB_ARTICLE, $article->id);
		
		$internal_urls = $article->extractInternalURLsFromContent();
		
		// Filter out inline URLs
		
		foreach($internal_urls as $internal_url => $internal_url_parts) {
			@list($attachment_sha1hash, $attachment_name) = explode('/', $internal_url_parts['path'], 2);
			
			if(40 == strlen($attachment_sha1hash)) {
				foreach($attachments_map['attachments'] as $attachment_id => $attachment_model) {
					if($attachment_model->storage_sha1hash == $attachment_sha1hash) {
						unset($attachments_map['attachments'][$attachment_id]);
					}
				}
			}
		}
		
		// Filter out attachment links with no content
		 
		foreach($attachments_map['links'] as $attachment_guid => $attachment_link) {
			if(!isset($attachments_map['attachments'][$attachment_link->attachment_id]))
				unset($attachments_map['links'][$attachment_guid]);
		}
		
		$tpl->assign('attachments_map', $attachments_map);

		// Template
		$tpl->display('devblocks:cerberusweb.kb::kb/profile.tpl');
	}
	
	function showBulkPopupAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}
		
		// Categories
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_KB_ARTICLE, false);
		$tpl->assign('custom_fields', $custom_fields);

		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.kb_article'
		);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.kb::kb/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();

		// Categories
		@$category_ids = DevblocksPlatform::importGPC($_REQUEST['category_ids'],'array',array());
		
		if(is_array($category_ids)) {
			$do['category_delta'] = array();
			
			foreach($category_ids as $cat_id) {
				@$cat_mode = DevblocksPlatform::importGPC($_REQUEST['category_ids_'.$cat_id],'string','');
				if(!empty($cat_mode))
					$do['category_delta'][] = $cat_mode . $cat_id;
			}
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_KbArticle::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}	
};