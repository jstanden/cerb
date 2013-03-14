<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
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
		
		// Remember the last tab/URL
		
		@$selected_tab = array_shift($stack);
		
		$point = 'cerberusweb.profiles.kb';
		$tpl->assign('point', $point);
		
		if(null == $selected_tab) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$breadcrumbs = $article->getCategories();
		$tpl->assign('breadcrumbs', $breadcrumbs);
			
		// Properties
			
		$properties = array();
			
		$properties['updated'] = array(
			'label' => ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $article->updated,
		);
			
		$properties['views'] = array(
			'label' => ucfirst($translate->_('kb_article.views')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $article->views,
		);
			
		$properties['id'] = array(
			'label' => ucfirst($translate->_('common.id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $article->id,
		);
			
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $article->id)) or array();
			
		foreach($custom_fields as $cf_id => $cfield) {
			if(!isset($values[$cf_id]))
				continue;
				
			$properties['cf_' . $cf_id] = array(
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => $values[$cf_id],
			);
		}
			
		$tpl->assign('properties', $properties);
			
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.kb_article');
		$tpl->assign('macros', $macros);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_KB_ARTICLE);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Template
		$tpl->display('devblocks:cerberusweb.kb::kb/profile.tpl');
	}
};