<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
		if(null == ($worker = CerberusApplication::getActiveWorker()))
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
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		// Categories
		
		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0);
		$tpl->assign('levels',$levels);
		
		// Render template
		
		$tpl->display('devblocks:cerberusweb.kb::kb/tabs/articles/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array');

		@$topic_id = intval($params['topic_id']);
		
		// Make sure it's a valid topic
		if(false == ($topic = DAO_KbCategory::get($topic_id)))
			$topic_id = 0;
		
		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
	}	
	
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$root_category_id = intval($tab->params['topic_id']);
		
		$this->_renderCategory($root_category_id, $tab->id);
	}
	
	public function changeCategoryAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'integer',0);
		@$category_id = DevblocksPlatform::importGPC($_REQUEST['category_id'],'integer',0);

		$this->_renderCategory($category_id, $tab_id);
	}
	
	private function _renderCategory($category_id=0, $tab_id) {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();

		$root_id = intval($category_id);
		$tpl->assign('root_id', $root_id);

		$tree = DAO_KbCategory::getTreeMap(false);
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

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.kb::kb/tabs/articles/index.tpl');
	}
	
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
				'uid' => 'workspace_tab_' . $tab->id,
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'params' => $tab->params,
			),
		);
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab) || empty($tab->id) || !is_array($json))
			return false;
		
		return true;
	}
}
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class ChKbReplyToolbarButton extends Extension_ReplyToolbarItem {
		function render(Model_Message $message) {
			$tpl = DevblocksPlatform::services()->template();
			
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
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		
		if(!$id && !$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_article.create'))
			return;
		
		if($id && !$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_article.update'))
			return;
		
		$tpl = DevblocksPlatform::services()->template();

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
				if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_article.create'))
					return;
				
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
				if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_article.update'))
					return;
				
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT => $content,
					DAO_KbArticle::UPDATED => time(),
				);
				DAO_KbArticle::update($id, $fields);
				
			}
			
			// Categories
			DAO_KbArticle::setCategories($id, $category_ids, true);
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $field_ids);
			
			// Files
			@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_ids'], 'array', array());
			if(is_array($file_ids))
				DAO_Attachment::setLinks(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $file_ids);
		}
		
		// JSON
		echo json_encode(array('id'=>$id));
	}
	
	function showKbCategoryEditPanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		
		if(!$id && !$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_category.create'))
			return;
		
		if($id && !$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_category.update'))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
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
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete_box'],'integer',0);

		$refresh_id = 0;
		
		if(!empty($id) && !empty($delete)) {
			if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_category.delete'))
				return;
			
			$ids = DAO_KbCategory::getDescendents($id);
			DAO_KbCategory::delete($ids);
			$refresh_id = $parent_id;
			
		} elseif(empty($id)) { // create
			if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_category.create'))
				return;
			
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			$id = DAO_KbCategory::create($fields);
			$refresh_id = $parent_id;
			
		} else { // update
			if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.kb_category.update'))
				return;
			
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

class DAO_KbCategory extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const PARENT_ID = 'parent_id';
	
	const CACHE_ALL = 'ch_cache_kbcategories_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(64)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(64)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::PARENT_ID)
			->id()
			;

		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO kb_category () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'kb_category', $fields);
		
		self::clearCache();
	}
	
	static function getTreeMap($prune_empty=false) {
		$db = DevblocksPlatform::services()->database();
		
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
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$count_cat = intval($row['kb_category_id']);
			$count_hits = intval($row['hits']);
			
			$pid = $count_cat;
			while($pid) {
				@$parent_id = $categories[$pid]->parent_id;
				$tree[$parent_id][$pid] += $count_hits;
				$pid = $parent_id;
			}
		}
		
		mysqli_free_result($rs);
		
		// Filter out empty categories on public
		if($prune_empty) {
			foreach($tree as $parent_id => $nodes) {
				$tree[$parent_id] = array_filter($nodes, function($count) {
					return !empty($count);
				});
			}
		}
		
		return $tree;
	}

	/**
	 *
	 * @param bool $nocache
	 * @return Model_KbCategory[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($categories = $cache->load(self::CACHE_ALL))) {
			$categories = self::getWhere(
				null,
				DAO_KbCategory::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($categories))
				return false;
			
			$cache->save($categories, self::CACHE_ALL);
		}
		
		return $categories;
	}
	
	static function getTopics() {
		$categories = self::getAll();
		
		if(is_array($categories))
		foreach($categories as $key => $category) { /* @var $category Model_KbCategory */
			if(0 != $category->parent_id)
				unset($categories[$key]);
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
	
	static public function getAncestors($root_id, $categories=null) {
		if(empty($categories))
			$categories = DAO_KbCategory::getAll();
		
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
			
		return array_reverse($breadcrumb);
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
	static function getWhere($where=null, $sortBy=DAO_KbCategory::NAME, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, parent_id, name ".
			"FROM kb_category ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;

		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_KbCategory	 */
	static function get($id) {
		if(empty($id))
			return null;
		
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
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_KbCategory();
			$object->id = $row['id'];
			$object->parent_id = $row['parent_id'];
			$object->name = $row['name'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM kb_category WHERE id IN (%s)", $ids_list));

		$db->ExecuteMaster(sprintf("DELETE FROM kb_article_to_category WHERE kb_category_id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('kb_category');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_KbCategory::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_KbCategory', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"kbc.id as %s, ".
			"kbc.name as %s, ".
			"kbc.parent_id as %s ",
				SearchFields_KbCategory::ID,
				SearchFields_KbCategory::NAME,
				SearchFields_KbCategory::PARENT_ID
			);
			
		$join_sql = "FROM kb_category kbc ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_KbCategory');
		
		$result = array(
			'primary_table' => 'kbc',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_KbCategory::ID]);
			$results[$id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(kbc.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_KbCategory extends DevblocksSearchFields {
	// Table
	const ID = 'kbc_id';
	const PARENT_ID = 'kbc_parent_id';
	const NAME = 'kbc_name';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'kbc.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_KB_CATEGORY => new DevblocksSearchFieldContextKeys('kbc.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		if('cf_' == substr($param->field, 0, 3)) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
		}
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'kbc', 'id', $translate->_('kb_category.id'), null, true),
			self::PARENT_ID => new DevblocksSearchField(self::PARENT_ID, 'kbc', 'parent_id', $translate->_('kb_category.parent_id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'kbc', 'name', $translate->_('kb_category.name'), null, true),

		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Context_KbCategory extends Extension_DevblocksContext {
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function getRandom() {
		return DAO_KbCategory::random();
	}
	
	function getMeta($context_id) {
		$category = DAO_KbCategory::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();
		
		return array(
			'id' => $category->id,
			'name' => $category->name,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=profiles&type=kb_category&id=%d-%s", $category->id, DevblocksPlatform::strToPermalink($category->name), true)),
			'updated' => 0, // [TODO]
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
		} elseif(is_array($category)) {
			$category = Cerb_ORMHelper::recastArrayToModel($category, 'Model_KbCategory');
		} else {
			$category = null;
		}
		/* @var $category Model_KbCategory */
			
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('kb_category.name'),
			'parent_id' => $prefix.$translate->_('kb_category.parent_id'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'parent_id' => Model_CustomField::TYPE_NUMBER,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_KB_CATEGORY;
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $category) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $category->name;
			$token_values['id'] = $category->id;
			$token_values['name'] = $category->name;
			$token_values['parent_id'] = $category->parent_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($category, $token_values);
		}
		
		return TRUE;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_KbCategory::ID,
			'name' => DAO_KbCategory::NAME,
			'parent_id' => DAO_KbCategory::PARENT_ID,
		];
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
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
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->addParams(array(), true);
		$view->renderSortBy = SearchFields_KbCategory::NAME;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		//$view->name = 'Calls';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				//new DevblocksSearchCriteria(SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
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