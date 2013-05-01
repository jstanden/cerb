<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerb Development Team
 *
 * Sure, it would be so easy to just cheat and edit this file to use the
 * software without paying for it.  But we trust you anyway.  In fact, we're
 * writing this software for you!
 *
 * Quality software backed by a dedicated team takes money to develop.  We
 * don't want to be out of the office bagging groceries when you call up
 * needing a helping hand.  We'd rather spend our free time coding your
 * feature requests than mowing the neighbors' lawns for rent money.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your inbox that you probably
 * haven't had since spammers found you in a game of 'E-mail Battleship'.
 * Miss. Miss. You sunk my inbox!
 *
 * A legitimate license entitles you to support from the developers,
 * and the warm fuzzy feeling of feeding a couple of obsessed developers
 * who want to help you get more done.
 *
 \* - Jeff Standen, Darren Sugita, Dan Hildebrandt
 *	 Webgroup Media LLC - Developers of Cerb
 */

class ChKbPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
	}
	
	function viewKbArticlesExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;

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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&tab=kb_article', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
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

if (class_exists('Extension_WorkspaceTab')):
class WorkspaceTab_KbBrowse extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$this->_renderCategory(0, $tab->id);
	}
	
	public function changeCategoryAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'integer',0);
		@$category_id = DevblocksPlatform::importGPC($_REQUEST['category_id'],'integer',0);

		$this->_renderCategory($category_id, $tab_id);
	}
	
	private function _renderCategory($category_id=0, $tab_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();

		$root_id = intval($category_id);
		$tpl->assign('root_id', $root_id);

		$tree = DAO_KbCategory::getTreeMap($root_id);
		$tpl->assign('tree', $tree);

		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		// Breadcrumb
		
		$breadcrumb = array();
		
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
		$view_id = 'kb_browse_' . $tab_id;
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view = new View_KbArticle();
			$view->id = $view_id;
		}
		
		// Articles
		if(empty($root_id)) {
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

		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.kb::kb/tabs/articles/index.tpl');
	}
}
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class ChKbReplyToolbarButton extends Extension_ReplyToolbarItem {
		function render(Model_Message $message) {
			$tpl = DevblocksPlatform::getTemplateService();
			
			$tpl->assign('div', 'replyToolbarOptions'.$message->id);
			
			$tpl->display('devblocks:cerberusweb.kb::renderers/toolbar_kb_button.tpl');
		}
	};
endif;

// [TODO] This should just be merged into KbPage
class ChKbAjaxController extends DevblocksControllerExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
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
		$controller = array_shift($path); // timetracking

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
	
	function showArticleEditPanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('core.kb.articles.modify'))
			return;
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('root_id', $root_id);
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		if(!empty($view_id))
			$tpl->assign('view_id', $view_id);
			
		if(!empty($id)) {
			$article = DAO_KbArticle::get($id);
			$tpl->assign('article', $article);
			
			$article_categories = DAO_KbArticle::getCategoriesByArticleId($id);
			$tpl->assign('article_categories', $article_categories);
			
			$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_KB_ARTICLE, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $id);
			if(isset($custom_field_values[$id]))
				$tpl->assign('custom_field_values', $custom_field_values[$id]);
		}
		
		// Categories
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('devblocks:cerberusweb.kb::kb/peek_edit.tpl');
	}

	function saveArticleEditPanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('core.kb.articles.modify'))
			return;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string');
		@$category_ids = DevblocksPlatform::importGPC($_REQUEST['category_ids'],'array',array());
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string');
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'integer',0);
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_KbArticle::delete($id);
			
		} else { // Create|Modify
			// Sanitize
			if($format > 2 || $format < 0)
				$format = 0;
				
			if(empty($title))
				$title = '(' . $translate->_('kb_article.title') . ')';
			
			if(empty($id)) { // create
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT => $content,
					DAO_KbArticle::UPDATED => time(),
				);
				$id = DAO_KbArticle::create($fields);
				
				// View marquee
				if(!empty($id) && !empty($view_id)) {
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_KB_ARTICLE, $id);
				}
				
			} else { // update
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT => $content,
					DAO_KbArticle::UPDATED => time(),
				);
				DAO_KbArticle::update($id, $fields);
				
			}
			
			// Search index
			$search = DevblocksPlatform::getSearchService();
			$search->index('kb_article', $id, $title . ' ' . strip_tags($content), true);
			
			// Categories
			DAO_KbArticle::setCategories($id, $category_ids, true);
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $field_ids);
			
			// Files
			@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_ids'], 'array', array());
			if(is_array($file_ids))
				DAO_AttachmentLink::setLinks(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $file_ids);
		}
		
		// JSON
		echo json_encode(array('id'=>$id));
	}
	
	function showKbCategoryEditPanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('core.kb.categories.modify'))
			return;
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('root_id', $root_id);
		
		if(!empty($id)) {
			$category = DAO_KbCategory::get($id);
			$tpl->assign('category', $category);
		}
		
		/*
		 * [TODO] Remove the current category + descendents from the categories,
		 * so the worker can't create a closed subtree (e.g. category's parent is its child)
		 */
		
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('devblocks:cerberusweb.kb::kb/ajax/category_edit_panel.tpl');
	}
	
	function saveKbCategoryEditPanelJsonAction() {
		header('Content-type: application/json');
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('core.kb.categories.modify'))
			return;
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete_box'],'integer',0);

		$refresh_id = 0;
		
		if(!empty($id) && !empty($delete)) {
			$ids = DAO_KbCategory::getDescendents($id);
			DAO_KbCategory::delete($ids);
			$refresh_id = $parent_id;
			
		} elseif(empty($id)) { // create
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			$id = DAO_KbCategory::create($fields);
			$refresh_id = $parent_id;
			
		} else { // update
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			DAO_KbCategory::update($id, $fields);
			$refresh_id = $id;
		}
		
		echo json_encode(array(
			'id' => $refresh_id,
		));
	}
	
	function showArticlesBulkPanelAction() {
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
//		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK);
//		$tpl->assign('custom_fields', $custom_fields);

		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.kb_article');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.kb::kb/bulk.tpl');
	}
	
	function doArticlesBulkUpdateAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
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
		
		// Feedback fields
//		@$list_id = trim(DevblocksPlatform::importGPC($_POST['list_id'],'integer',0));
		
		// Do: List
//		if(0 != strlen($list_id))
//			$do['list_id'] = $list_id;
			
		// Do: Custom fields
//		$do = DAO_CustomFieldValue::handleBulkPost($do);

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
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
	function getArticleContentAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		// [TODO] ACL
		// [TODO] Fetch article content from storage
		
		if(null == ($article = DAO_KbArticle::get($id)))
			return;

		$tpl->assign('body', $article->getContent());
		
		$tpl->display('devblocks:cerberusweb.core::internal/html_editor/preview.tpl');
	}
};

class DAO_KbCategory extends Cerb_ORMHelper {
	const CACHE_ALL = 'ch_cache_kbcategories_all';
	
	const ID = 'id';
	const PARENT_ID = 'parent_id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO kb_category () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'kb_category', $fields);
		
		self::clearCache();
	}
	
	static function getTreeMap() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$categories = self::getWhere();
		$tree = array();

		// Fake recursion
		foreach($categories as $cat_id => $cat) {
			$pid = $cat->parent_id;
			if(!isset($tree[$pid])) {
				$tree[$pid] = array();
			}
				
			$tree[$pid][$cat_id] = 0;
		}
		
		// Add counts (and bubble up)
		$sql = "SELECT count(*) AS hits, kb_category_id FROM kb_article_to_category GROUP BY kb_category_id";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$count_cat = intval($row['kb_category_id']);
			$count_hits = intval($row['hits']);
			
			$pid = $count_cat;
			while($pid) {
				@$parent_id = $categories[$pid]->parent_id;
				$tree[$parent_id][$pid] += $count_hits;
				$pid = $parent_id;
			}
		}
		
		// [TODO] Filter out empty categories on public
		
		mysql_free_result($rs);
		
		return $tree;
	}

	/**
	 *
	 * @param bool $nocache
	 * @return Model_KbCategory[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($categories = $cache->load(self::CACHE_ALL))) {
			$categories = self::getWhere();
			$cache->save($categories, self::CACHE_ALL);
		}
		
		return $categories;
	}
	
	static function getTree($root=0) {
		$levels = array();
		$map = self::getTreeMap();
		
		self::_recurseTree($levels,$map,$root);
		
		return $levels;
	}
	
	// [TODO] Move to Model_KbCategoryTree?
	static private function _recurseTree(&$levels,$map,$node=0,$level=-1) {
		if(!isset($map[$node]) || empty($map[$node]))
			return;

		$level++; // we're dropping down a node

		// recurse through children
		foreach($map[$node] as $pid => $children) {
			$levels[$pid] = $level;
			self::_recurseTree($levels,$map,$pid,$level);
		}
	}
	
	static public function getDescendents($root_id) {
		$tree = self::getTree($root_id);
		@$ids = array_merge(array($root_id),array_keys($tree));
		return $ids;
	}
	
	/**
	 * @param string $where
	 * @return Model_KbCategory[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, parent_id, name ".
			"FROM kb_category ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_KbCategory	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_KbCategory[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_KbCategory();
			$object->id = $row['id'];
			$object->parent_id = $row['parent_id'];
			$object->name = $row['name'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM kb_category WHERE id IN (%s)", $ids_list));

		$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_category_id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('kb_category');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_KbCategory::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"kbc.id as %s, ".
			"kbc.name as %s, ".
			"kbc.parent_id as %s ",
				SearchFields_KbCategory::ID,
				SearchFields_KbCategory::NAME,
				SearchFields_KbCategory::PARENT_ID
			);
			
		$join_sql = "FROM kb_category kbc ";

		// [JAS]: Dynamic table joins
//		if(isset($tables['context_link']))
//			$join_sql .= "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.kb_article' AND context_link.to_context_id = kb.id) ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'kbc.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$result = array(
			'primary_table' => 'kbc',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => true,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY kbc.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_KbCategory::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT kbc.id) " : "SELECT COUNT(kbc.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_KbCategory implements IDevblocksSearchFields {
	// Table
	const ID = 'kbc_id';
	const PARENT_ID = 'kbc_parent_id';
	const NAME = 'kbc_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'kbc', 'id', $translate->_('kb_category.id')),
			self::PARENT_ID => new DevblocksSearchField(self::PARENT_ID, 'kbc', 'parent_id', $translate->_('kb_category.parent_id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'kbc', 'name', $translate->_('kb_category.name')),

		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_KB_CATEGORY,
		));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Context_KbCategory extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		return TRUE;
	}
	
	function getRandom() {
		return DAO_KbCategory::random();
	}
	
	function getMeta($context_id) {
		$category = DAO_KbCategory::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $category->id,
			'name' => $category->name,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=profiles&type=kb_category&id=%d-%s", $category->id, DevblocksPlatform::strToPermalink($category->name), true)),
		);
	}
	
	function getContext($category, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Category:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_KB_CATEGORY);
		
		// Polymorph
		if(is_numeric($category)) {
			$category = DAO_KbCategory::get($category);
		} elseif($category instanceof Model_KbCategory) {
			// It's what we want already.
		} else {
			$category = null;
		}
		/* @var $category Model_KbCategory */
			
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('kb_category.name'),
			'parent_id' => $prefix.$translate->_('kb_category.parent_id'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_KB_CATEGORY;
		
		// Token values
		if(null != $category) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $category->name;
			$token_values['id'] = $category->id;
			$token_values['name'] = $category->name;
			$token_values['parent_id'] = $category->parent_id;
		}
		
		return TRUE;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_KB_CATEGORY;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
//		$view->name = 'Headlines';
//		$view->view_columns = array(
//			SearchFields_CallEntry::IS_OUTGOING,
//			SearchFields_CallEntry::PHONE,
//			SearchFields_CallEntry::UPDATED_DATE,
//		);
		$view->addParams(array(
			//SearchFields_KbArticle::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_KbArticle::IS_CLOSED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_KbCategory::NAME;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		//$view->name = 'Calls';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				//new DevblocksSearchCriteria(SearchFields_KbCategory::CONTEXT_LINK,'=',$context),
				//new DevblocksSearchCriteria(SearchFields_KbCategory::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};

class Model_KbCategory {
	public $id;
	public $parent_id;
	public $name;
};

if (class_exists('DevblocksEventListenerExtension')):
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
endif;