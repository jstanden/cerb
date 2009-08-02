<?php
class UmScKbController extends Extension_UmScController {
	const PARAM_REQUIRE_LOGIN = 'kb.require_login';
	const PARAM_KB_ROOTS = 'kb.roots';
	
	const SESSION_ARTICLE_LIST = 'kb_article_list';	
	
	function isVisible() {
		$require_login = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_REQUIRE_LOGIN, 0);
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		// If we're requiring log in...
		if($require_login && empty($active_user))
			return false;
		
		// Disable the KB if no categories were selected
		$sKbRoots = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_KB_ROOTS, '');
        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
        return !empty($kb_roots);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // kb
		
		// KB Roots
		$sKbRoots = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_KB_ROOTS, '');
        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		
		$kb_roots_str = '0';
		if(!empty($kb_roots))
			$kb_roots_str = implode(',', array_keys($kb_roots)); 
		
		switch(array_shift($stack)) {
			case 'search':
				@$q = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
				$tpl->assign('q', $q);

				if(null == ($view = UmScAbstractViewLoader::getView('', UmSc_KbArticleView::DEFAULT_ID))) {
					$view = new UmSc_KbArticleView();
				}
				
				$view->name = "";
				$view->params = array(
					array(
						DevblocksSearchCriteria::GROUP_OR,
						new DevblocksSearchCriteria(SearchFields_KbArticle::TITLE,DevblocksSearchCriteria::OPER_FULLTEXT,$q),
						new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,$q),
					),
					new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots))
				);
				
				UmScAbstractViewLoader::setView($view->id, $view);
				$tpl->assign('view', $view);
				
				$tpl->display("file:${tpl_path}portal/sc/kb/search_results.tpl");
				break;
				
			case 'article':
				if(empty($kb_roots))
					return;
				
				$id = intval(array_shift($stack));

				list($articles, $count) = DAO_KbArticle::search(
					array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::ID,'=',$id),
						new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots))
					),
					-1,
					0,
					null,
					null,
					false
				);
				
				if(!isset($articles[$id]))
					break;
				
				$article = DAO_KbArticle::get($id);
				$tpl->assign('article', $article);

				@$article_list = $umsession->getProperty(self::SESSION_ARTICLE_LIST, array());
				if(!empty($article) && !isset($article_list[$id])) {
					DAO_KbArticle::update($article->id, array(
						DAO_KbArticle::VIEWS => ++$article->views
					));
					$article_list[$id] = $id;
					$umsession->setProperty(self::SESSION_ARTICLE_LIST, $article_list);
				}

				$categories = DAO_KbCategory::getWhere();
				$tpl->assign('categories', $categories);
				
				$cats = DAO_KbArticle::getCategoriesByArticleId($id);

				$breadcrumbs = array();
				foreach($cats as $cat_id) {
					if(!isset($breadcrumbs[$cat_id]))
						$breadcrumbs[$cat_id] = array();
					$pid = $cat_id;
					while($pid) {
						$breadcrumbs[$cat_id][] = $pid;
						$pid = $categories[$pid]->parent_id;
					}
					$breadcrumbs[$cat_id] = array_reverse($breadcrumbs[$cat_id]);
					
					// Remove any breadcrumbs not in this SC profile
					$pid = reset($breadcrumbs[$cat_id]);
					if(!isset($kb_roots[$pid]))
						unset($breadcrumbs[$cat_id]);
					
				}
				
				$tpl->assign('breadcrumbs',$breadcrumbs);
				$tpl->display("file:${tpl_path}portal/sc/kb/article.tpl");
				break;
			
			default:
			case 'browse':
				@$root = intval(array_shift($stack));
				$tpl->assign('root_id', $root);
				
				$categories = DAO_KbCategory::getWhere();
				$tpl->assign('categories', $categories);
				
				$tree_map = DAO_KbCategory::getTreeMap(0);
				
				// Remove other top-level categories
				if(is_array($tree_map[0]))
				foreach($tree_map[0] as $child_id => $count) {
					if(!isset($kb_roots[$child_id]))
						unset($tree_map[0][$child_id]);
				}

				// Remove empty categories
				if(is_array($tree_map[0]))
				foreach($tree_map as $node_id => $children) {
					foreach($children as $child_id => $count) {
						if(empty($count)) {
							@$pid = $categories[$child_id]->parent_id;
							unset($tree_map[$pid][$child_id]);
							unset($tree_map[$child_id]);
						}
					}
				}
				
				$tpl->assign('tree', $tree_map);
				
				// Breadcrumb // [TODO] API-ize inside Model_KbTree ?
				$breadcrumb = array();
				$pid = $root;
				while(0 != $pid) {
					$breadcrumb[] = $pid;
					$pid = $categories[$pid]->parent_id;
				}
				$tpl->assign('breadcrumb',array_reverse($breadcrumb));
				
				$tpl->assign('mid', @intval(ceil(count($tree_map[$root])/2)));
				
				// Articles
				
				if(null == ($view = UmScAbstractViewLoader::getView('', UmSc_KbArticleView::DEFAULT_ID))) {
					$view = new UmSc_KbArticleView();
				}
				
				if(!empty($root)) {
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root),
						new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
					);
				} else {
					// Most Popular Articles
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
					);
				}

				$view->name = "";
				$view->renderSortBy = SearchFields_KbArticle::VIEWS;
				$view->renderSortAsc = false;
				$view->renderPage = 0;
				$view->renderLimit = 10;

				UmScAbstractViewLoader::setView($view->id, $view);
				$tpl->assign('view', $view);
				
	    		$tpl->display("file:${tpl_path}portal/sc/kb/index.tpl");
	    	break;
		}
		
	}
	
	function configure() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';

		$require_login = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_REQUIRE_LOGIN, 0);
		$tpl->assign('kb_require_login', $require_login);

		// Knowledgebase
		$tree_map = DAO_KbCategory::getTreeMap();
		$tpl->assign('tree_map', $tree_map);
		
		$levels = DAO_KbCategory::getTree(0);
		$tpl->assign('levels', $levels);
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$sKbRoots = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_KB_ROOTS, '');
        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
        $tpl->assign('kb_roots', $kb_roots);

		$tpl->display("file:${tpl_path}portal/sc/config/kb.tpl");
	}
	
	function saveConfiguration() {
        @$iRequireLogin = DevblocksPlatform::importGPC($_POST['kb_require_login'],'integer',0);
		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_REQUIRE_LOGIN, $iRequireLogin);
		
        // KB
        @$aKbRoots = DevblocksPlatform::importGPC($_POST['category_ids'],'array',array());
        $aKbRoots = array_flip($aKbRoots);
		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_KB_ROOTS, serialize($aKbRoots));
	}
};

class UmSc_KbArticleView extends C4_AbstractView {
	const DEFAULT_ID = 'sc_kb';
	
	private $_TPL_PATH = '';

	function __construct() {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		
		$this->id = self::DEFAULT_ID;
		$this->name = 'Articles';
		$this->renderSortBy = 'kb_updated';
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_KbArticle::TITLE,
			SearchFields_KbArticle::UPDATED,
			SearchFields_KbArticle::VIEWS,
		);
	}

	function getData() {
		$objects = DAO_KbArticle::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		//$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . $this->_TPL_PATH . 'portal/sc/kb/view.tpl');
	}

	static function getFields() {
		return SearchFields_KbArticle::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_KbArticle::ID]);
		unset($fields[SearchFields_KbArticle::FORMAT]);
		return $fields;
	}

	static function getColumns() {
		return self::getFields();
	}
};