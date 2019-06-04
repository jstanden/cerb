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

class ChKbPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == (CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
	}
	
	function viewKbArticlesExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
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
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&tab=kb', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $task_id => $row) {
				if($task_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_KbArticle::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=kb&id=%d", $row[SearchFields_KbArticle::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
};

class WorkspaceWidget_KnowledgebaseBrowser extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget) {
		@$root_category_id = intval($widget->params['topic_id']);
		
		$this->_renderCategory($root_category_id, $widget);
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
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		@$topic_id = intval($params['topic_id']);
		
		// Make sure it's a valid topic
		if(false == (DAO_KbCategory::get($topic_id)))
			$params['topic_id'] = 0;
		
		DAO_WorkspaceWidget::update($widget->id, [
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		]);
	}
	
	public function changeCategoryAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'],'integer',0);
		@$category_id = DevblocksPlatform::importGPC($_REQUEST['category_id'],'integer',0);
		
		if(false == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;

		$this->_renderCategory($category_id, $widget);
	}
	
	private function _renderCategory($category_id=0, Model_WorkspaceWidget $widget) {
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
		
		$tpl->assign('mid', @intval(ceil(count($tree[$root_id])/2)));
		
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
			$view->name = vsprintf($translate->_('kb.view.articles'), $categories[$root_id]->name);
		}

		$view->renderPage = 0;

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.kb::widgets/browser/articles/index.tpl');
	}
}

class ChKbReplyToolbarButton extends Extension_ReplyToolbarItem {
	function render(Model_Message $message) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('div', 'replyToolbarOptions'.$message->id);
		
		$tpl->display('devblocks:cerberusweb.kb::renderers/toolbar_kb_button.tpl');
	}
};

// [TODO] This should just be merged into KbPage
class ChKbAjaxController extends DevblocksControllerExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == (CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
		$path = $request->path;
		
		array_shift($path); // Controller
		
		@$action = DevblocksPlatform::strAlphaNum(array_shift($path), '\_') . 'Action';

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
				
			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
				break;
		}
	}

	function getArticleContentAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		// [TODO] ACL
		// [TODO] Fetch article content from storage
		
		if(null == ($article = DAO_KbArticle::get($id)))
			return;
		
		$tpl->assign('body', $article->getContent());
		
		$tpl->display('devblocks:cerberusweb.core::internal/html_editor/preview.tpl');
	}
};

class EventListener_Kb extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				//DAO_KbCategory::maint();
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

	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$target_context_id = $model->extension_params['context_id'];
		
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
};