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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class WorkspaceWidget_KnowledgebaseBrowser extends Extension_WorkspaceWidget {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($action) {
			case 'changeCategory':
				return $this->_workspaceWidgetAction_changeCategory($model);
		}
		return false;
	}

	function render(Model_WorkspaceWidget $widget) {
		@$root_category_id = intval($widget->params['topic_id']);
		
		$this->_renderCategory($widget, $root_category_id);
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		
		// Categories
		
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0);
		$tpl->assign('levels',$levels);
		
		// Render template
		
		$tpl->display('devblocks:cerberusweb.kb::widgets/browser/articles/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		@$topic_id = intval($params['topic_id']);
		
		// Make sure it's a valid topic
		if(false == (DAO_KbCategory::get($topic_id)))
			$params['topic_id'] = 0;
		
		DAO_WorkspaceWidget::update($widget->id, [
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		]);
		
		return true;
	}
	
	private function _workspaceWidgetAction_changeCategory(Model_WorkspaceWidget $model) {
		$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'] ?? null, 'integer',0);
		$category_id = DevblocksPlatform::importGPC($_REQUEST['category_id'] ?? null, 'integer',0);
		
		if(false == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;

		$this->_renderCategory($widget, $category_id);
	}
	
	private function _renderCategory(Model_WorkspaceWidget $widget, $category_id=0) {
		$tpl = DevblocksPlatform::services()->template();
		$translate = DevblocksPlatform::getTranslationService();

		$tpl->assign('widget', $widget);
		
		$root_id = intval($category_id);
		$tpl->assign('root_id', $root_id);

		$tree = DAO_KbCategory::getTreeMap(false);
		$tpl->assign('tree', $tree);

		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		if($root_id && !array_key_exists($root_id, $categories))
			return;
		
		// Breadcrumb
		
		$breadcrumb = [];
		
		$pid = $root_id;
		while(0 != $pid) {
			$breadcrumb[] = $pid;
			if(isset($categories[$pid])) {
				$pid = $categories[$pid]->parent_id;
			} else {
				$pid = 0;
			}
		}
		
		$tpl->assign('breadcrumb',array_reverse($breadcrumb));
		
		$tpl->assign('mid', intval(ceil(count($tree[$root_id] ?? [])/2)));
		
		// Each view_id should be unique to the tab it's on
		$view_id = 'kb_browse_' . $widget->id;
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view = new View_KbArticle();
			$view->id = $view_id;
		}
		
		// Articles
		if(!$root_id) {
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,DevblocksSearchCriteria::OPER_IS_NULL,true),
			), true);
			$view->name = $translate->_('kb.view.uncategorized');
			
		} else {
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root_id),
			), true);
			$view->name = sprintf($translate->_('kb.view.articles'), $categories[$root_id]->name);
		}

		$view->renderPage = 0;

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.kb::widgets/browser/articles/index.tpl');
	}
}

class EventListener_Kb extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_KbArticle::maint();
				break;
		}
	}
};

class ProfileWidget_KbArticle extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.kb_article';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$target_context_id = $model->extension_params['context_id'] ?? null;
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		if($target_context_id) {
			$labels = $values = $merge_token_labels = $merge_token_values = [];
			
			CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'record_',
				'Record:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $model, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'widget_',
				'Widget:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			$values['widget__context'] = CerberusContexts::CONTEXT_PROFILE_WIDGET;
			$values['widget_id'] = $model->id;
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$context = CerberusContexts::CONTEXT_KB_ARTICLE;
			$context_id = $tpl_builder->build($target_context_id, $dict);
			
			if(false == ($record = DAO_KbArticle::get($context_id))) {
				return;
			}
		}
		
		$tpl->assign('article', $record);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.kb::widgets/kb_article/article.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		$tpl->display('devblocks:cerberusweb.kb::widgets/kb_article/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
};