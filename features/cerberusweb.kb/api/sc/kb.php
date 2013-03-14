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

class UmScKbController extends Extension_UmScController {
	const PARAM_KB_ROOTS = 'kb.roots';
	const SESSION_ARTICLE_LIST = 'kb_article_list';
	
	function isVisible() {
		// Disable the KB if no categories were selected
		$sKbRoots = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_KB_ROOTS, '');
		$kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		return !empty($kb_roots);
	}
	
	function renderSidebar(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		@$q = DevblocksPlatform::importGPC($_POST['q'],'string','');
		$tpl->assign('q', $q);
		
		$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/sidebar.tpl");
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$umsession = ChPortalHelper::getSession();
		
		$stack = $response->path;
		array_shift($stack); // kb
		
		// KB Roots
		$sKbRoots = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_KB_ROOTS, '');
		$kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		
		$kb_roots_str = '0';
		if(!empty($kb_roots))
			$kb_roots_str = implode(',', array_keys($kb_roots));
		
		switch(array_shift($stack)) {
			case 'search':
				@$q = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','all');

				$tpl->assign('q', $q);
				$tpl->assign('scope', $scope);

				if(null == ($view = UmScAbstractViewLoader::getView('', UmSc_KbArticleView::DEFAULT_ID))) {
					$view = new UmSc_KbArticleView();
				}
				
				$view->name = "";
				$params = array();
				
				switch($scope) {
					default:
					case "all":
						$params[SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT] = new DevblocksSearchCriteria(SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($q,'all'));
						break;
					case "any":
						$params[SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT] = new DevblocksSearchCriteria(SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($q,'any'));
						break;
					case "phrase":
						$params[SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT] = new DevblocksSearchCriteria(SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($q,'phrase'));
						break;
				}

				$params[SearchFields_KbArticle::TOP_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots));
				
				$view->addParams($params, true);
				$view->renderPage = 0;
				
				UmScAbstractViewLoader::setView($view->id, $view);
				$tpl->assign('view', $view);
				
				$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/search_results.tpl");
				break;
				
			case 'article':
				if(empty($kb_roots))
					return;
				
				$id = intval(array_shift($stack));

				list($articles, $count) = DAO_KbArticle::search(
					array(),
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

				$categories = DAO_KbCategory::getAll();
				$tpl->assign('categories', $categories);
				
				$cats = DAO_KbArticle::getCategoriesByArticleId($id);

				$breadcrumbs = array();
				$trails = array();
				foreach($cats as $cat_id) {
					$pid = $cat_id;
					$trail = array();
					while($pid) {
						array_unshift($trail,$pid);
						$pid = $categories[$pid]->parent_id;
					}
					
					// Remove any breadcrumbs not in this SC profile
					if(isset($kb_roots[reset($trail)]))
						$trails[] = $trail;
				}
				
				// Remove redundant trails
				foreach($trails as $idx => $trail) {
					foreach($trails as $c_idx => $compare_trail) {
						if($idx != $c_idx && count($compare_trail) >= count($trail)) {
							if(array_slice($compare_trail,0,count($trail))==$trail) {
								unset($trails[$idx]);
							}
						}
					}
				}
				
				$tpl->assign('breadcrumbs',$trails);
				
				// Attachments
				$attachments_map = DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_KB_ARTICLE, $id);
				$tpl->assign('attachments_map', $attachments_map);
				
				$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/article.tpl");
				break;
			
			default:
			case 'browse':
				@$root = intval(array_shift($stack));
				$tpl->assign('root_id', $root);
				
				$categories = DAO_KbCategory::getAll();
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
					$view->addParams(array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root),
						new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
					), true);
				} else {
					// Most Popular Articles
					$view->addParams(array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
					), true);
				}

				$view->name = "";
				$view->renderSortBy = SearchFields_KbArticle::UPDATED;
				$view->renderSortAsc = false;
				$view->renderPage = 0;
				$view->renderLimit = 10;

				UmScAbstractViewLoader::setView($view->id, $view);
				$tpl->assign('view', $view);
				
				$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/index.tpl");
			break;
		}
		
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		// Knowledgebase
		$tree_map = DAO_KbCategory::getTreeMap();
		$tpl->assign('tree_map', $tree_map);
		
		$levels = DAO_KbCategory::getTree(0);
		$tpl->assign('levels', $levels);
		
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$sKbRoots = DAO_CommunityToolProperty::get($instance->code,self::PARAM_KB_ROOTS, '');
		$kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		$tpl->assign('kb_roots', $kb_roots);

		$tpl->display("devblocks:cerberusweb.kb::portal/sc/config/kb.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		// KB
		@$aKbRoots = DevblocksPlatform::importGPC($_POST['category_ids'],'array',array());
		$aKbRoots = array_flip($aKbRoots);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_KB_ROOTS, serialize($aKbRoots));
	}
};

class UmSc_KbArticleView extends C4_AbstractView {
	const DEFAULT_ID = 'sc_kb';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Articles';
		$this->renderSortBy = 'kb_updated';
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_KbArticle::TITLE,
			SearchFields_KbArticle::UPDATED,
			SearchFields_KbArticle::VIEWS,
		);
		
		$this->addParamsHidden(array(
			SearchFields_KbArticle::ID,
			SearchFields_KbArticle::FORMAT,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_KbArticle::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		//$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/view.tpl");
	}

	function getFields() {
		return SearchFields_KbArticle::getFields();
	}
};