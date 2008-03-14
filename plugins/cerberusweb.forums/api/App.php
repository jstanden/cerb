<?php
$path = realpath(dirname(__FILE__) . '/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/App.php', array(
    'C4_ForumsThreadView'
));

class ChForumsPlugin extends DevblocksPlugin {
	const ID = 'cerberusweb.forums';
	
	const SETTING_POSTER_WORKERS = 'forums.forum_workers';
	
	function load(DevblocksPluginManifest $manifest) {
	}
};

class ChForumsConfigTab extends Extension_ConfigTab {
	const ID = 'forums.config.tab';
	
	function showTab() {
		$settings = CerberusSettings::getInstance();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		@$sources = DAO_ForumsSource::getWhere();
		$tpl->assign('sources', $sources);

		if(null != ($poster_workers_str = $settings->get(ChForumsPlugin::SETTING_POSTER_WORKERS, null))) {
			$tpl->assign('poster_workers_str', $poster_workers_str);
		}
		
		$tpl->display('file:' . $tpl_path . 'config/index.tpl.php');
	}
	
	function saveTab() {
		$settings = CerberusSettings::getInstance();
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		// Edit|Delete
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_REQUEST['names'],'array',array());
		@$urls = DevblocksPlatform::importGPC($_REQUEST['urls'],'array',array());
		@$keys = DevblocksPlatform::importGPC($_REQUEST['keys'],'array',array());
		@$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
				
		@$poster_workers = DevblocksPlatform::importGPC($_REQUEST['poster_workers'],'string','');
		
		// Add
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string','');
		@$secret_key = DevblocksPlatform::importGPC($_REQUEST['secret_key'],'string','');

		// Deletes
		if(is_array($deletes) && !empty($deletes)) {
			DAO_ForumsSource::delete($deletes);
		}

		if(!empty($poster_workers)) {
			$settings->set(ChForumsPlugin::SETTING_POSTER_WORKERS, strtolower($poster_workers));
		}
		
		// Edits
		if(is_array($ids) && !empty($ids)) {
			foreach($ids as $idx => $source_id) {
				$source_name = $names[$idx];
				$source_url = $urls[$idx];
				$source_key = $keys[$idx];
				
				$fields = array(
					DAO_ForumsSource::NAME => $source_name,
					DAO_ForumsSource::URL => $source_url,
					DAO_ForumsSource::SECRET_KEY => $source_key,
				);
				DAO_ForumsSource::update($source_id, $fields);
			}
		}
		
		// Add
		if(!empty($name) && !empty($url)) {
			$fields = array(
				DAO_ForumsSource::NAME => $name,
				DAO_ForumsSource::URL => $url,
				DAO_ForumsSource::SECRET_KEY => $secret_key,
			);
			$source_id = DAO_ForumsSource::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','forums')));
		exit;
	}
};

class ChForumsTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return realpath(dirname(__FILE__).'/../') . '/strings.xml';
	}
};

class ChForumsPage extends CerberusPageExtension {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);

		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function explorerAction() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // forums 
		array_shift($stack); // explorer

		switch(array_shift($stack)) {
			case 'navigation':
				$this->_renderExplorerNavigation($stack);
				break;

			default:
				$visit = CerberusApplication::getVisit();
				
				@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'integer', 0);
				
				// Load results cache
				if(null == ($forums_view = C4_AbstractViewLoader::getView('', C4_ForumsThreadView::DEFAULT_ID))) {
					// Do something
					$forums_view = new C4_ForumsThreadView();
				}
				
				$tpl = DevblocksPlatform::getTemplateService();
				$tpl->cache_lifetime = "0";
				$tpl->assign('path', $this->tpl_path);
				
				$view_start = $forums_view->renderLimit * $forums_view->renderPage;
				$view_page = floor($view_start/100);
				
				list($posts, $count) = DAO_ForumsThread::search(
					$forums_view->params,
					100, // $forums_view->renderLimit
					$view_page, // $forums_view->renderPage
					$forums_view->renderSortBy,
					$forums_view->renderSortAsc,
					true
				);
				
				// Advance the internal pointer to the requested start position
				if(!empty($start) && isset($posts[$start])) {
					while(key($posts) != $start) {
						next($posts);
					}
				}

				if(empty($start))
					$start = key($posts);
				
				$current = current($posts);
					
				$visit->set('forums_explorer_results', $posts);
				$visit->set('forums_explorer_results_pos', $start);
//				$visit->set('forums_explorer_results_total', $count);
				
				$tpl->assign('current_post', $current);
				$tpl->display('file:' . $this->tpl_path . '/explorer/index.tpl.php');
				break;
		}
		
		session_write_close();
		exit;
	}
	
	private function _renderExplorerNavigation($stack) {
		$visit = CerberusApplication::getVisit();
		
		if(null == ($posts = $visit->get('forums_explorer_results', null))) {
			return;
		}
		
		$pos = $visit->get('forums_explorer_results_pos', -1);

		// Advance our pointer
		if(!empty($pos) && isset($posts[$pos]))
			while(key($posts)!=$pos) {
				next($posts);
			}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		switch(array_shift($stack)) {
			case 'next':
				next($posts);
				$key = key($posts);
				$visit->set('forums_explorer_results_pos', $key);
				break;
				
			case 'prev':
				prev($posts);
				$key = key($posts);
				$visit->set('forums_explorer_results_pos', $key);
				break;
		}
		
		if(null != $post = DAO_ForumsThread::get(key($posts))) {
			$tpl->assign('current_post', $post);
		}

		// Check for previous position
		if(prev($posts)) {
			$current = current($posts);
			$tpl->assign('prev_post', $current);
			next($posts); //re-advance
		} else {
			reset($posts);
		}
		
		// Check for next position
		if(next($posts)) {
			$current = current($posts);
			$tpl->assign('next_post', $current);
		} else {
			end($posts);
		}

		$tpl->assign('active_worker', $visit->getWorker());
		
		$tpl->display('file:' . $this->tpl_path . '/explorer/navigation.tpl.php');
	}
	
	function ajaxCloseAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		DAO_ForumsThread::update($id, array(
			DAO_ForumsThread::IS_CLOSED => 1
		));
		
		exit;
	}
	
	function ajaxReopenAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		DAO_ForumsThread::update($id, array(
			DAO_ForumsThread::IS_CLOSED => 0
		));
		
		exit;
	}
	
	function ajaxAssignAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);

		DAO_ForumsThread::update($id, array(
			DAO_ForumsThread::WORKER_ID => $worker_id
		));
		
		exit;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		array_shift($stack); // forums
		
		switch(array_shift($stack)) {
			case 'search':
				if(null == ($view = C4_AbstractViewLoader::getView('', 'forums_search'))) {
					$view = new C4_ForumsThreadView();
					$view->id = 'forums_search';
					C4_AbstractViewLoader::setView($view->id, $view);
				}

				$view->name = "Search Results";
				$tpl->assign('view', $view);

				$tpl->assign('view_fields', C4_ForumsThreadView::getFields());
				$tpl->assign('view_searchable_fields', C4_ForumsThreadView::getSearchFields());
				
				$tpl->display($this->tpl_path . '/forums/search.tpl.php');
				break;
			
			case 'overview':
		    default:
		    	$sources = DAO_ForumsSource::getWhere();
		    	$tpl->assign('sources', $sources);
		    	
		    	$workers = DAO_Worker::getAll();
		    	$tpl->assign('workers', $workers);
		    	
				$source_unassigned_totals = DAO_ForumsThread::getUnassignedTotals();
				$tpl->assign('source_unassigned_totals', $source_unassigned_totals);
		    	
				$source_assigned_totals = DAO_ForumsThread::getAssignedWorkerTotals();
				$tpl->assign('source_assigned_totals', $source_assigned_totals);
		    	
		    	// View
				if(null == ($forums_overview = C4_AbstractViewLoader::getView('', C4_ForumsThreadView::DEFAULT_ID))) {
					$forums_overview = new C4_ForumsThreadView();
					C4_AbstractViewLoader::setView(C4_ForumsThreadView::DEFAULT_ID, $forums_overview);
				}
				
				// Overview control
		    	if(null != (@$module = array_shift($stack))) {
					$params = array(
						SearchFields_ForumsThread::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_ForumsThread::IS_CLOSED,'=',0),
					);
					$forums_overview->renderPage = 0;
			    	
					switch($module) {
						case 'all':
							$forums_overview->name = "All Forum Threads";
							$params[SearchFields_ForumsThread::WORKER_ID] = new DevblocksSearchCriteria(SearchFields_ForumsThread::WORKER_ID,'=',0);
							$forums_overview->params = $params;
							break;
							
						case 'forum':
							@$forum_id = array_shift($stack);
							if(!empty($forum_id) && isset($sources[$forum_id])) {
								$params[SearchFields_ForumsThread::FORUM_ID] = new DevblocksSearchCriteria(SearchFields_ForumsThread::FORUM_ID,'=',$forum_id);
								$params[SearchFields_ForumsThread::WORKER_ID] = new DevblocksSearchCriteria(SearchFields_ForumsThread::WORKER_ID,'=',0);
								$forums_overview->name = $sources[$forum_id]->name;
								$forums_overview->params = $params;
							}
							break;
							
						case 'worker':
							@$worker_id = array_shift($stack);
							if(!empty($worker_id) && isset($workers[$worker_id])) {
								$params[SearchFields_ForumsThread::WORKER_ID] = new DevblocksSearchCriteria(SearchFields_ForumsThread::WORKER_ID,'=',$worker_id);
								$forums_overview->name = "For " . $workers[$worker_id]->getName();
								$forums_overview->params = $params;
							}
							break;
					}
					
					C4_AbstractViewLoader::setView(C4_ForumsThreadView::DEFAULT_ID, $forums_overview);
		    	}
				
				$tpl->assign('forums_overview', $forums_overview);
				
				$tpl->display('file:' . $this->tpl_path . '/forums/index.tpl.php');
		        break;
		}
	}
	
	function viewCloseThreadsAction() {
		@$row_ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		
		$fields = array(
			DAO_ForumsThread::IS_CLOSED => 1
		);
		
		DAO_ForumsThread::update($row_ids, $fields);
	}
	
	function viewAssignThreadsAction() {
		@$row_ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['assign_worker_id'],'integer',0);
		
		$fields = array(
			DAO_ForumsThread::WORKER_ID => $worker_id
		);
		DAO_ForumsThread::update($row_ids, $fields);
	}
	
	function importAction() {
		$sources = DAO_ForumsSource::getWhere();

		$settings = CerberusSettings::getInstance();
		
		// Track posters that are also workers
		$poster_workers = array();
		if(null != ($poster_workers_str = $settings->get(ChForumsPlugin::SETTING_POSTER_WORKERS, null))) {
			$poster_workers = DevblocksPlatform::parseCrlfString($poster_workers_str);
		}
		
		foreach($sources as $source) { /* @var $source Model_ForumsSource */
			// [TODO] Convert to REST client (move into Devblocks)
			$source_url = sprintf("%s?lastpostid=%d&limit=100",
				$source->url,
				$source->last_postid
			);
			
			$xml_in = file_get_contents($source_url);
			$xml = new SimpleXMLElement($xml_in);

			$last_postid = 0;
			
			foreach($xml->thread as $thread) {
				@$thread_id = (string) $thread->id;
				@$thread_title = (string) $thread->title;
				@$thread_last_updated = (string) $thread->last_updated;
				@$thread_last_postid = (string) $thread->last_postid;
				@$thread_last_poster = (string) $thread->last_poster;
				@$thread_link = (string) $thread->link;
				
				if(null == ($th = DAO_ForumsThread::getBySourceThreadId($source->id, $thread_id))) {
					$fields = array(
						DAO_ForumsThread::FORUM_ID => $source->id,
						DAO_ForumsThread::THREAD_ID => $thread_id,
						DAO_ForumsThread::TITLE => $thread_title,
						DAO_ForumsThread::LAST_UPDATED => intval($thread_last_updated),
						DAO_ForumsThread::LAST_POSTER => $thread_last_poster,
						DAO_ForumsThread::LINK => $thread_link,
					);
					DAO_ForumsThread::create($fields);
					
				} else {
					// If the last post was a worker, leave the thread at the current closed state
					$closed = (false===array_search(strtolower($thread_last_poster),$poster_workers)) ? 0 : $th->is_closed;
					
					$fields = array(
						DAO_ForumsThread::LAST_UPDATED => intval($thread_last_updated),
						DAO_ForumsThread::LAST_POSTER => $thread_last_poster,
						DAO_ForumsThread::LINK => $thread_link,
						DAO_ForumsThread::IS_CLOSED => $closed,
					);
					DAO_ForumsThread::update($th->id, $fields);
				}
				
				$last_postid = $thread_last_postid;
				
			} // foreach($xml->thread)

			// Save our progress to the database
			if(!empty($last_postid)) {
				DAO_ForumsSource::update($source->id,array(
					DAO_ForumsSource::LAST_POSTID => $last_postid
				));
			}
		
		} // foreach($sources)
	}
};

class DAO_ForumsThread extends DevblocksORMHelper {
	const ID = 'id';
	const FORUM_ID = 'forum_id';
	const THREAD_ID = 'thread_id';
	const TITLE = 'title';
	const LAST_UPDATED = 'last_updated';
	const LAST_POSTER = 'last_poster';
	const LINK = 'link';
	const WORKER_ID = 'worker_id';
	const IS_CLOSED = 'is_closed';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('forums_thread_seq');
		
		$sql = sprintf("INSERT INTO forums_thread (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'forums_thread', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_ForumsThread[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, forum_id, thread_id, last_updated, last_poster, title, link, worker_id, is_closed ".
			"FROM forums_thread ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY last_updated DESC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ForumsThread
	 */
	static function getById($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return NULL;
	}
	
	/**
	 * @param integer $thread_id
	 * @return Model_ForumsThread
	 */
	static function getBySourceThreadId($source_id,$thread_id) {
		$objects = self::getWhere(sprintf("%s = %d AND %s = %d",
			self::FORUM_ID,
			$source_id,
			self::THREAD_ID,
			$thread_id
		));
		
		if(is_array($objects) && !empty($objects))
			return array_shift($objects);
			
		return NULL;
	}
	
	/**
	 * @param integer $id
	 * @return Model_ForumsThread	 */
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
	 * @param ADORecordSet $rs
	 * @return Model_ForumsThread[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_ForumsThread();
			$object->id = $rs->fields['id'];
			$object->forum_id = $rs->fields['forum_id'];
			$object->thread_id = $rs->fields['thread_id'];
			$object->title = $rs->fields['title'];
			$object->last_updated = $rs->fields['last_updated'];
			$object->last_poster = $rs->fields['last_poster'];
			$object->link = $rs->fields['link'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->is_closed = $rs->fields['is_closed'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function getUnassignedTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$rs = $db->Execute("SELECT count(id) as hits, forum_id ".
			"FROM forums_thread ".
			"WHERE worker_id = 0 ".
			"AND is_closed = 0 ".
			"GROUP BY forum_id ".
			"HAVING count(id) > 0 "
		);
		
		$totals = array();
		
		while(!$rs->EOF) {
			$totals[$rs->fields['forum_id']] = intval($rs->fields['hits']);
			$rs->MoveNext();
		}
		
		return $totals;
	}
	
	static function getAssignedWorkerTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$rs = $db->Execute("SELECT count(id) as hits, worker_id ".
			"FROM forums_thread ".
			"WHERE worker_id > 0 ".
			"AND is_closed = 0 ".
			"GROUP BY worker_id ".
			"HAVING count(id) > 0 "
		);
		
		$totals = array();
		
		while(!$rs->EOF) {
			$totals[$rs->fields['worker_id']] = intval($rs->fields['hits']);
			$rs->MoveNext();
		}
		
		return $totals;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM forums_thread WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
    /**
     * Enter description here...
     *
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_ForumsThread::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.thread_id as %s, ".
			"t.forum_id as %s, ".
			"t.title as %s, ".
			"t.last_updated as %s, ".
			"t.last_poster as %s, ".
			"t.link as %s, ".
			"t.is_closed as %s, ".
			"t.worker_id as %s ",
			    SearchFields_ForumsThread::ID,
			    SearchFields_ForumsThread::THREAD_ID,
			    SearchFields_ForumsThread::FORUM_ID,
			    SearchFields_ForumsThread::TITLE,
			    SearchFields_ForumsThread::LAST_UPDATED,
			    SearchFields_ForumsThread::LAST_POSTER,
			    SearchFields_ForumsThread::LINK,
			    SearchFields_ForumsThread::IS_CLOSED,
			    SearchFields_ForumsThread::WORKER_ID
			 );
		
		$join_sql = 
			"FROM forums_thread t "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .  
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_ForumsThread::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }
};

class SearchFields_ForumsThread implements IDevblocksSearchFields {
	// Address
	const ID = 't_id';
	const THREAD_ID = 't_thread_id';
	const FORUM_ID = 't_forum_id';
	const TITLE = 't_title';
	const LAST_UPDATED = 't_last_updated';
	const LAST_POSTER = 't_last_poster';
	const LINK = 't_link';
	const IS_CLOSED = 't_is_closed';
	const WORKER_ID = 't_worker_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 't', 'id', null, $translate->_('forumsthread.id')),
			self::THREAD_ID => new DevblocksSearchField(self::THREAD_ID, 't', 'thread_id', null, $translate->_('forumsthread.thread_id')),
			self::FORUM_ID => new DevblocksSearchField(self::FORUM_ID, 't', 'forum_id', null, $translate->_('forumsthread.forum_id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 't', 'title', null, $translate->_('forumsthread.title')),
			self::LAST_UPDATED => new DevblocksSearchField(self::LAST_UPDATED, 't', 'last_updated', null, $translate->_('forumsthread.last_updated')),
			self::LAST_POSTER => new DevblocksSearchField(self::LAST_POSTER, 't', 'last_poster', null, $translate->_('forumsthread.last_poster')),
			self::LINK => new DevblocksSearchField(self::LINK, 't', 'link', null, $translate->_('forumsthread.link')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 't', 'is_closed', null, $translate->_('forumsthread.is_closed')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 't', 'worker_id', null, $translate->_('forumsthread.worker_id')),
		);
	}
};

class Model_ForumsThread {
	public $id;
	public $forum_id;
	public $thread_id;
	public $last_updated;
	public $title;
	public $worker_id;
	public $is_closed;
};

class DAO_ForumsSource extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const URL = 'url';
	const SECRET_KEY = 'secret_key';
	const LAST_POSTID = 'last_postid';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO forums_source (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'forums_source', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_ForumsSource[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, url, secret_key, last_postid ".
			"FROM forums_source ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ForumsSource	 */
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
	 * @param ADORecordSet $rs
	 * @return Model_ForumsSource[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_ForumsSource();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$object->url = $rs->fields['url'];
			$object->secret_key = $rs->fields['secret_key'];
			$object->last_postid = intval($rs->fields['last_postid']);
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM forums_source WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class Model_ForumsSource {
	public $id;
	public $name;
	public $url;
	public $secret_key;
	public $last_postid;
};

class ChForumsPatchContainer extends DevblocksPatchContainerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */

		$file_prefix = realpath(dirname(__FILE__) . '/../patches');
		
		$this->registerPatch(new DevblocksPatch('cerberusweb.forums',3,$file_prefix.'/1.0.0.php',''));
	}
};

class C4_ForumsThreadView extends C4_AbstractView {
	const DEFAULT_ID = 'forums_overview';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'All Forum Threads';
		$this->renderLimit = 10;
		$this->renderSortBy = 't_last_updated';
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_ForumsThread::FORUM_ID,
			SearchFields_ForumsThread::LAST_UPDATED,
			SearchFields_ForumsThread::LAST_POSTER,
//			SearchFields_ForumsThread::LINK,
//			SearchFields_ForumsThread::WORKER_ID,
		);
		
		$this->params = array(
			SearchFields_ForumsThread::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_ForumsThread::IS_CLOSED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_ForumsThread::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$sources = DAO_ForumsSource::getWhere();
		$tpl->assign('sources', $sources);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.forums/templates/forums/forums_view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ForumsThread::FORUM_ID:
				$forums = DAO_ForumsSource::getWhere();
				$tpl->assign('forums', $forums);
				
				$tpl->display('file:' . $tpl_path . 'forums/criteria/forum.tpl.php');
				break;
				
			case SearchFields_ForumsThread::TITLE:
			case SearchFields_ForumsThread::LINK:
			case SearchFields_ForumsThread::LAST_POSTER:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
				
			case SearchFields_ForumsThread::LAST_UPDATED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
				
			case SearchFields_ForumsThread::IS_CLOSED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl.php');
				break;
				
			case SearchFields_ForumsThread::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl.php');
				break;
				
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ForumsThread::FORUM_ID:
				$forums = DAO_ForumsSource::getWhere();
				$strings = array();

				foreach($values as $val) {
					if(!isset($forums[$val]))
						continue;
					else
						$strings[] = $forums[$val]->name;
				}
				echo implode(", ", $strings);
				break;
			
			case SearchFields_ForumsThread::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
						$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
						continue;
					else
						$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_ForumsThread::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_ForumsThread::ID]);
		unset($fields[SearchFields_ForumsThread::THREAD_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_ForumsThread::ID]);
		unset($fields[SearchFields_ForumsThread::THREAD_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_ForumsThread::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_ForumsThread::IS_CLOSED,'=',0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ForumsThread::FORUM_ID:
				@$forum_ids = DevblocksPlatform::importGPC($_REQUEST['forum_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$forum_ids);
				break;
				
			case SearchFields_ForumsThread::TITLE:
			case SearchFields_ForumsThread::LINK:
			case SearchFields_ForumsThread::LAST_POSTER:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_ForumsThread::LAST_UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_ForumsThread::IS_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_ForumsThread::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

};


?>