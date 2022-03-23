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

class UmScKbController extends Extension_UmScController {
	const PARAM_KB_ROOTS = 'kb.roots';
	const PARAM_KB_VIEW_NUMROWS = 'kb.view.num_rows';
	const PARAM_WORKLIST_COLUMNS_JSON = 'kb.worklist.columns';
	
	const SESSION_ARTICLE_LIST = 'kb_article_list';
	
	function isVisible() {
		// Disable the KB if no categories were selected
		$sKbRoots = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_KB_ROOTS, '');
		$kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		return !empty($kb_roots);
	}
	
	function invoke(string $action, DevblocksHttpRequest $request=null) {
		return false;
	}
	
	function renderSidebar(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$q = DevblocksPlatform::importGPC($_POST['q'] ?? null, 'string','');
		$tpl->assign('q', $q);
		
		$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/sidebar.tpl");
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$umsession = ChPortalHelper::getSession();
		
		$stack = $response->path;
		array_shift($stack); // kb
		
		// KB Roots
		
		$sKbRoots = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_KB_ROOTS, '');
		$kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		
		// KB worklist
		
		@$params_columns = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_WORKLIST_COLUMNS_JSON, '[]', true);
		
		if(empty($params_columns))
			$params_columns = array(
				SearchFields_KbArticle::TITLE,
				SearchFields_KbArticle::UPDATED,
				SearchFields_KbArticle::VIEWS,
			);
		
		// Actions
		
		switch(array_shift($stack)) {
			case 'search':
				$q = DevblocksPlatform::importGPC($_REQUEST['q'] ?? null, 'string','');
				$scope = DevblocksPlatform::importGPC($_REQUEST['scope'] ?? null, 'string','all');

				$tpl->assign('q', $q);
				$tpl->assign('scope', $scope);

				if(null == ($view = UmScAbstractViewLoader::getView('', UmSc_KbArticleView::DEFAULT_ID))) {
					$view = new UmSc_KbArticleView();
				}
				
				$view->name = "";
				$params = array();
				
				switch($scope) {
					default:
					case "expert":
						$params[SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT] = new DevblocksSearchCriteria(SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT, array($q, 'expert'));
						break;
				}

				$params[SearchFields_KbArticle::TOP_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots));
				
				$view->view_columns = $params_columns;
				$view->addParams($params, true);
				$view->renderPage = 0;
				$view->renderLimit = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_KB_VIEW_NUMROWS, 10);
				
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
				
				// Attachments

				$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $id);
				$tpl->assign('attachments', $attachments);
				
				// Article list

				@$article_list = $umsession->getProperty(self::SESSION_ARTICLE_LIST, array());
				if(!empty($article) && !isset($article_list[$id])) {
					DAO_KbArticle::update($article->id, [
						DAO_KbArticle::VIEWS => ++$article->views,
						DAO_KbArticle::UPDATED => $article->updated,
					]);
					$article_list[$id] = $id;
					$umsession->setProperty(self::SESSION_ARTICLE_LIST, $article_list);
				}

				$categories = DAO_KbCategory::getAll();
				$tpl->assign('categories', $categories);
				
				$cats = DAO_KbArticle::getCategoriesByArticleId($id);

				$trails = [];
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
				
				// Template
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
				foreach($tree_map as $children) {
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
				
				$tpl->assign('mid', intval(ceil(count($tree_map[$root] ?? [])/2)));
				
				// Articles
				
				if(null == ($view = UmScAbstractViewLoader::getView('', UmSc_KbArticleView::DEFAULT_ID))) {
					$view = new UmSc_KbArticleView();
					$view->renderSortBy = SearchFields_KbArticle::UPDATED;
					$view->renderSortAsc = false;
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

				// View
				
				$view->name = "";
				$view->view_columns = $params_columns;
				$view->renderPage = 0;
				$view->renderLimit = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_KB_VIEW_NUMROWS, 10);
				
				// Render

				UmScAbstractViewLoader::setView($view->id, $view);
				$tpl->assign('view', $view);
				
				$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/index.tpl");
			break;
		}
		
	}
	
	function configure(Model_CommunityTool $portal) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $portal);

		// Knowledgebase
		
		$tree_map = DAO_KbCategory::getTreeMap();
		$tpl->assign('tree_map', $tree_map);
		
		$levels = DAO_KbCategory::getTree(0);
		$tpl->assign('levels', $levels);
		
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$sKbRoots = DAO_CommunityToolProperty::get($portal->code,self::PARAM_KB_ROOTS, '');
		$kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		$tpl->assign('kb_roots', $kb_roots);

		$prop_kb_view_numrows = DAO_CommunityToolProperty::get($portal->code,self::PARAM_KB_VIEW_NUMROWS, 10);
		$tpl->assign('kb_view_numrows', max(intval($prop_kb_view_numrows), 5));
		
		// Worklist columns
		
		$params = array(
			'columns' => DAO_CommunityToolProperty::get($portal->code, self::PARAM_WORKLIST_COLUMNS_JSON, '[]', true),
		);
		$tpl->assign('kb_params', $params);
		
		$view = new View_KbArticle();
		$view->id = View_KbArticle::DEFAULT_ID;
		
		$columns = array_filter(
			$view->getColumnsAvailable(),
			function($column) {
				return !empty($column->db_label);
			}
		);
		
		$columns_selected = [];
		if(is_array($params['columns']))
		foreach($params['columns'] as $column_key)
			$columns_selected[$column_key] = $columns[$column_key];
		
		$columns_available = array_diff_key($columns, $columns_selected);
		DevblocksPlatform::sortObjects($columns_available, 'db_label');
		
		$tpl->assign('kb_columns', array_merge($columns_selected, $columns_available));
		
		// Template
		
		$tpl->display("devblocks:cerberusweb.kb::portal/sc/config/kb.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		// KB topics
		
		$aKbRoots = DevblocksPlatform::importGPC($_POST['category_ids'] ?? null, 'array', []);
		$aKbRoots = array_flip($aKbRoots);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_KB_ROOTS, serialize($aKbRoots));
		
		// Worklist num rows
		
		$prop_kb_view_numrows = DevblocksPlatform::importGPC($_POST['kb_view_numrows'] ?? null, 'integer',10);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_KB_VIEW_NUMROWS, max($prop_kb_view_numrows, 5));
		
		// Worklist columns
		
		$columns = DevblocksPlatform::importGPC($_POST['kb_columns'] ?? null, 'array', []);

		$columns = array_filter($columns, function($column) {
			return !empty($column);
		});
		
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_WORKLIST_COLUMNS_JSON, $columns, true);
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
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		$columns = array_merge($this->view_columns, array($this->renderSortBy));
		
		return DAO_KbArticle::search(
			$columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_KbArticle');
		
		return $objects;
	}

	function render() {
		//$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$currencies = DAO_Currency::getAll();
		$tpl->assign('currencies', $currencies);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display("devblocks:cerberusweb.kb:portal_".ChPortalHelper::getCode() . ":support_center/kb/view.tpl");
	}

	function getFields() {
		return SearchFields_KbArticle::getFields();
	}
};