<?php
class ChFnrAjaxController extends DevblocksControllerExtension {
	private $_CORE_TPL_PATH = '';
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_CORE_TPL_PATH = APP_PATH . '/features/cerberusweb.core/templates/';
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		parent::__construct($manifest);
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
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // timetracking

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

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
	
	function doFnrAction() {
		$q = DevblocksPlatform::importGPC(@$_POST['q'],'string','');
		$sources = DevblocksPlatform::importGPC(@$_POST['sources'],'array',array());

		@$sources = array_flip($sources);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$feeds = array();
		$where = null;
		
		if(!empty($sources)) {
			$where = sprintf("%s IN (%s)",
				DAO_FnrExternalResource::ID,
				implode(',', array_keys($sources))
			);
		}
		
		$resources = DAO_FnrExternalResource::getWhere($where);
		$feeds = Model_FnrExternalResource::searchResources($resources, $q);
	
		$tpl->assign('terms', $q);
		$tpl->assign('feeds', $feeds);
		$tpl->assign('sources', $sources);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'results.tpl');
	}
};

if (class_exists('Extension_ResearchTab')):
class ChFnrResearchTab extends Extension_ResearchTab {
	const VIEW_RESEARCH_FNR_SEARCH = 'research_fnr_search';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		@$request_path = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		$tpl->assign('request_path', $request_path);

		@$stack =  explode('/', $request_path);
		
		@array_shift($stack); // research
		@array_shift($stack); // fnr
		
		@$action = array_shift($stack);
		
		switch($action) {
			default:
//				if(null == ($view = C4_AbstractViewLoader::getView(self::VIEW_RESEARCH_FNR_SEARCH))) {
//					$view = new C4_KbArticleView();
//					$view->id = self::VIEW_RESEARCH_FNR_SEARCH;
//					$view->name = $translate->_('common.search_results');
//					C4_AbstractViewLoader::setView($view->id, $view);
//				}
//				
//				$tpl->assign('view', $view);
//				$tpl->assign('view_fields', C4_KbArticleView::getFields());
//				$tpl->assign('view_searchable_fields', C4_KbArticleView::getSearchFields());
				
//				$tpl->assign('response_uri', 'research/fnr/search');

				$fnr_topics = DAO_FnrTopic::getWhere();
				$tpl->assign('fnr_topics', $fnr_topics);

				$tpl->display($tpl_path . 'research_tab/index.tpl');
				break;
		}
	}
}
endif;

class ChFnrConfigTab extends Extension_ConfigTab {
	const ID = 'fnr.config.tab';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		@$topics = DAO_FnrTopic::getWhere();
		$tpl->assign('fnr_topics', $topics);
		
		@$resources = DAO_FnrExternalResource::getWhere();
		$tpl->assign('fnr_resources', $resources);

		$tpl->display('file:' . $tpl_path . 'config/index.tpl');
	}
	
	function getFnrResourceAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		if(!empty($id) && null != ($fnr_resource = DAO_FnrExternalResource::get($id)))
			$tpl->assign('fnr_resource', $fnr_resource);
		
		@$topics = DAO_FnrTopic::getWhere();
		$tpl->assign('fnr_topics', $topics);
		
		$tpl->display('file:' . $tpl_path . 'config/edit_fnr_resource.tpl');
	}
	
	function getFnrTopicAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		if(!empty($id) && null != ($fnr_topic = DAO_FnrTopic::get($id)))
			$tpl->assign('fnr_topic', $fnr_topic);
		
		$tpl->display('file:' . $tpl_path . 'config/edit_fnr_topic.tpl');
	}
	
	function saveTab() {
		@$form_type = DevblocksPlatform::importGPC($_REQUEST['form_type'],'string','');
		
		switch($form_type) {
			case 'fnr_topic':
				$this->_saveTabFnrTopic();
				break;
				
			case 'fnr_resource':
				$this->_saveTabFnrResource();
				break;
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','fnr')));
		exit;		
	}
	
	private function _saveTabFnrTopic() {
		// Form
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fnr')));
			return;
		}
		
		// Deletes
		if(!empty($do_delete)) {
			DAO_FnrTopic::delete($id);
			
			// [TODO] Delete all resources on this topic
			
			return;
		}

		$fields = array(
			DAO_FnrTopic::NAME => $name,
		);

		// Edit
		if(!empty($id)) {
			DAO_FnrTopic::update($id, $fields);

		// Add			
		} else {
			$id = DAO_FnrTopic::create($fields);
			
		}
		
	}
	
	private function _saveTabFnrResource() {
		// Form
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string','');
		@$topic_id = DevblocksPlatform::importGPC($_REQUEST['topic_id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fnr')));
			return;
		}
		
		// Deletes
		if(!empty($do_delete)) {
			DAO_FnrExternalResource::delete($id);
			return;
		}

		$fields = array(
			DAO_FnrExternalResource::NAME => $name,
			DAO_FnrExternalResource::URL => $url,
			DAO_FnrExternalResource::TOPIC_ID => $topic_id,
		);

		// Edit
		if(!empty($id)) {
			DAO_FnrExternalResource::update($id, $fields);

		// Add			
		} else {
			$id = DAO_FnrExternalResource::create($fields);
			
		}
	}
	
};

class DAO_FnrQuery extends DevblocksORMHelper {
	const ID = 'id';
	const QUERY = 'query';
	const CREATED = 'created';
	const SOURCE = 'source';
	const NO_MATCH = 'no_match';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('fnr_query_seq');
		
		$sql = sprintf("INSERT INTO fnr_query (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'fnr_query', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_FnrQuery[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, query, created, source, no_match ".
			"FROM fnr_query ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FnrQuery	 */
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
	 * @return Model_FnrQuery[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_FnrQuery();
			$object->id = $rs->fields['id'];
			$object->query = $rs->fields['query'];
			$object->created = $rs->fields['created'];
			$object->source = $rs->fields['source'];
			$object->no_match = $rs->fields['no_match'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_list = implode(',', $ids);
		$db->Execute(sprintf("DELETE QUICK FROM fnr_query WHERE id IN (%s)",$id_list));
	}

};

class DAO_FnrTopic extends DevblocksORMHelper {
	const _TABLE = 'fnr_topic';
	
	const ID = 'id';
	const NAME = 'name';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO %s (id,name) ".
			"VALUES (%d,'')",
			self::_TABLE,
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($ids, $fields) {
		parent::_update($ids, self::_TABLE, $fields);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		$ids_string = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM fnr_topic WHERE id IN (%s)", $ids_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$sql = sprintf("DELETE QUICK FROM fnr_external_resource WHERE topic_id IN (%s)", $ids_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
	public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id, name ".
			"FROM %s ".
			(!empty($where) ? ("WHERE $where ") : " ").
			" ORDER BY name ",
			self::_TABLE
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_createObjectsFromResultSet($rs);
	}
	
	public static function get($id) {
		$objects = self::getWhere(sprintf("id = %d", $id));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	public static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_FnrTopic();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
};

class DAO_FnrExternalResource extends DevblocksORMHelper {
	const _TABLE = 'fnr_external_resource';
	
	const ID = 'id';
	const NAME = 'name';
	const URL = 'url';
	const TOPIC_ID = 'topic_id';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO %s (id,name,url,topic_id) ".
			"VALUES (%d,'','',0)",
			self::_TABLE,
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($ids, $fields) {
		parent::_update($ids, self::_TABLE, $fields);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM %s WHERE id IN (%s)",
			self::_TABLE,
			implode(',', $ids)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id, name, url, topic_id ".
			"FROM %s ".
			(!empty($where) ? ("WHERE $where ") : " ").
			" ORDER BY name ",
			self::_TABLE
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_createObjectsFromResultSet($rs);
	}
	
	public static function get($id) {
		$objects = self::getWhere(sprintf("id = %d", $id));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	public static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_FnrTopic();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$object->topic_id = intval($rs->fields['topic_id']);
			$object->url = $rs->fields['url'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
};

class Model_FnrTopic {
	public $id = 0;
	public $name = '';

	function getResources() {
		$where = sprintf("%s = %d",
		DAO_FnrExternalResource::TOPIC_ID,
		$this->id
		);
		$resources = DAO_FnrExternalResource::getWhere($where);
		return $resources;
	}
};

class Model_FnrQuery {
	public $id;
	public $query;
	public $created;
	public $source;
	public $no_match;
};

class Model_FnrExternalResource {
	public $id = 0;
	public $name = '';
	public $url = '';
	public $topic_id = 0;

	public static function searchResources($resources, $query) {
		$feeds = array();
		$topics = DAO_FnrTopic::getWhere();

		if(is_array($resources))
		foreach($resources as $resource) { /* @var $resource Model_FnrExternalResource */
			try {
				$url = str_replace("#find#",rawurlencode($query),$resource->url);
				$feed = Zend_Feed::import($url);
				if($feed->count())
					$feeds[] = array(
					'name' => $resource->name,
					'topic_name' => @$topics[$resource->topic_id]->name,
					'feed' => $feed
				);
			} catch(Exception $e) {}
		}
		
		return $feeds;
	}
};
