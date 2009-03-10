<?php
class ChKbPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
		
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function getActivity() {
		return new Model_Activity('activity.kb');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		array_shift($stack); // contacts
		
		switch(array_shift($stack)) {
			case 'search':
				if(null == ($view = C4_AbstractViewLoader::getView(null, 'kb_search'))) {
					$view = new C4_KbArticleView();
					$view->id = 'kb_search';
					$view->name = $translate->_('common.search_results');
					C4_AbstractViewLoader::setView($view->id, $view);
				}
				$tpl->assign('view', $view);
				
				$tpl->assign('view_fields', C4_KbArticleView::getFields());
				$tpl->assign('view_searchable_fields', C4_KbArticleView::getSearchFields());
				
				$tpl->display('file:' . $this->_TPL_PATH . 'kb/search.tpl');
				break;
				
			default:
			case 'overview':
				@$root_id = intval(array_shift($stack));
				$tpl->assign('root_id', $root_id);

				$tree = DAO_KbCategory::getTreeMap($root_id);
				$tpl->assign('tree', $tree);

				$categories = DAO_KbCategory::getWhere();
				$tpl->assign('categories', $categories);
				
				// Breadcrumb // [TODO] API-ize inside Model_KbTree ?
				$breadcrumb = array();
				$pid = $root_id;
				while(0 != $pid) {
					$breadcrumb[] = $pid;
					$pid = $categories[$pid]->parent_id;
				}
				$tpl->assign('breadcrumb',array_reverse($breadcrumb));
				
				$tpl->assign('mid', @intval(ceil(count($tree[$root_id])/2)));
				
				if(null == ($view = C4_AbstractViewLoader::getView(null, C4_KbArticleView::DEFAULT_ID))) {
					$view = new C4_KbArticleView();
				}
				
				// Articles
				if(empty($root_id)) {
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,DevblocksSearchCriteria::OPER_IS_NULL,true),
					);
					$view->name = $translate->_('kb.view.uncategorized');
					
				} else {
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root_id),
					);
					$view->name = vsprintf($translate->_('kb.view.articles'), $categories[$root_id]->name);
				}

				C4_AbstractViewLoader::setView($view->id, $view);
				
				$tpl->assign('view', $view);
				
				$tpl->display('file:' . $this->_TPL_PATH . 'kb/index.tpl');
				break;
		}
	}
	
	function doViewDeleteAction() {
		// [TODO] Check privs/superuser
		// [TODO] Check that articles are in no topic (XSS/etc)
		
		@$row_id = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$return = DevblocksPlatform::importGPC($_POST['return'],'string','');
		
		if(!empty($row_id)) {
			DAO_KbArticle::delete($row_id);
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
	}
	
	function doArticleQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $translate = DevblocksPlatform::getTranslationService();
		
        // [TODO] This is redundant with render(), move to C4_KbArticleView::getSearchView() ?
        if(null == ($searchView = C4_AbstractViewLoader::getView('','kb_search'))) {
        	$searchView = new C4_KbArticleView();
        	$searchView->id = 'kb_search';
        	$searchView->name = $translate->_('common.search_results');
        	C4_AbstractViewLoader::setView($searchView->id, $searchView);
        }
		
//        $visit->set('quick_search_type', $type);
        
        $params = array();
        
        switch($type) {
            case "content":
//		        if($query && false===strpos($query,'*'))
//		            $query = '*' . $query . '*';
            	$params[SearchFields_KbArticle::CONTENT] = new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,$query);               
                break;
        }
        
        $searchView->params = $params;
        $searchView->renderPage = 0;
        $searchView->renderSortBy = null;
        
        C4_AbstractViewLoader::setView($searchView->id,$searchView);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('kb','search')));
	}
	
	function showArticlePeekPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
//		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

//		$tpl->assign('root_id', $root_id);
		$tpl->assign('return', $return);
		
		if(!empty($id)) {
			$article = DAO_KbArticle::get($id);
			$tpl->assign('article', $article);
			
//			$article_categories = DAO_KbArticle::getCategoriesByArticleId($id);
//			$tpl->assign('article_categories', $article_categories);
		}
		
//		$categories = DAO_KbCategory::getWhere();
//		$tpl->assign('categories', $categories);
		
//		$levels = DAO_KbCategory::getTree(0,$map); //$root_id
//		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'kb/rpc/article_peek_panel.tpl');
	}
	
	function showArticleEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->assign('root_id', $root_id);
		$tpl->assign('return', $return);
		
		if(!empty($id)) {
			$article = DAO_KbArticle::get($id);
			$tpl->assign('article', $article);
			
			$article_categories = DAO_KbArticle::getCategoriesByArticleId($id);
			$tpl->assign('article_categories', $article_categories);
		}
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'kb/rpc/article_edit_panel.tpl');
	}

	function saveArticleEditPanelAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string');
		@$category_ids = DevblocksPlatform::importGPC($_REQUEST['category_ids'],'array',array());
		@$content_raw = DevblocksPlatform::importGPC($_REQUEST['content_raw'],'string');
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'integer',0);
		
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_KbArticle::delete($id);
			
		} else { // Create|Modify
			// Sanitize
			if($format > 2 || $format < 0)
				$format = 0;
				
			if(empty($title))
				$title = '(' . $translate->_('kb_article.title') . ')';
			
			switch($format) {
				default:
				case 0: // plaintext
					$content_html = $content_raw;
					break;
				case 1: // HTML
					$content_html = $content_raw;
					break;
			}
				
			if(empty($id)) { // create
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT_RAW => $content_raw,
					DAO_KbArticle::CONTENT => $content_html,
					DAO_KbArticle::UPDATED => time(),
				);
				$id = DAO_KbArticle::create($fields);
				
			} else { // update
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT_RAW => $content_raw,
					DAO_KbArticle::CONTENT => $content_html,
					DAO_KbArticle::UPDATED => time(),
				);
				DAO_KbArticle::update($id, $fields);
				
			}
			
			DAO_KbArticle::setCategories($id, $category_ids, true);
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
	}
	
	function showKbCategoryEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('root_id', $root_id);
		$tpl->assign('return', $return);
		
		if(!empty($id)) {
			$category = DAO_KbCategory::get($id);
			$tpl->assign('category', $category);
		}
		
		/*
		 * [TODO] Remove the current category + descendents from the categories, 
		 * so the worker can't create a closed subtree (e.g. category's parent is its child)
		 */
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'kb/rpc/subcategory_edit_panel.tpl');
	}
	
	function saveKbCategoryEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete_box'],'integer',0);

		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		if(!empty($id) && !empty($delete)) {
			$ids = DAO_KbCategory::getDescendents($id);
			DAO_KbCategory::delete($ids);
			
			// Change $return to category parent
			$return = "kb/overview/" . sprintf("%06d", $parent_id);
			
		} elseif(empty($id)) { // create
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			DAO_KbCategory::create($fields);
			
		} else { // update
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			DAO_KbCategory::update($id, $fields);
			
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
	}
};
