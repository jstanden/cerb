<?php
// Classes
$path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/App.php', array(
    'C4_RssExpItemView'
));

class RssExpPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class RssExpTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return realpath(dirname(__FILE__).'/../') . '/strings.xml';
	}
};

if (class_exists('Extension_ActivityTab')):
class RssExpActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_RSS = 'activity_rss';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', self::VIEW_ACTIVITY_RSS))) {
			$view = new C4_RssExpItemView();
			$view->id = self::VIEW_ACTIVITY_RSS;
			$view->renderSortBy = SearchFields_RssExpItem::CREATED_DATE;
			$view->renderSortAsc = 0;
			
			$view->name = "RSS";
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/rss');
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_RssExpItemView::getFields());
		$tpl->assign('view_searchable_fields', C4_RssExpItemView::getSearchFields());
		
		$tpl->display($tpl_path . 'activity_tab/index.tpl.php');		
	}
}
endif;

class DAO_RssExpFeed extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const URL = 'url';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO rssexp_feed (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'rssexp_feed', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_RssExpFeed[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, url ".
			"FROM rssexp_feed ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_RssExpFeed	 */
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
	 * @return Model_RssExpFeed[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_RssExpFeed();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$object->url = $rs->fields['url'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM rssexp_feed WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class Model_RssExpFeed {
	public $id;
	public $name;
	public $url;
};

class DAO_RssExpItem extends DevblocksORMHelper {
	const ID = 'id';
	const FEED_ID = 'feed_id';
	const TITLE = 'title';
	const URL = 'url';
	const CREATED_DATE = 'created_date';
	const IS_READ = 'is_read';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('rssexp_item_seq');
		
		$sql = sprintf("INSERT INTO rssexp_item (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'rssexp_item', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_RssExpItem[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, feed_id, title, url, created_date, is_read ".
			"FROM rssexp_item ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY created_date desc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_RssExpItem	 */
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
	 * @return Model_RssExpItem[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_RssExpItem();
			$object->id = $rs->fields['id'];
			$object->feed_id = $rs->fields['feed_id'];
			$object->title = $rs->fields['title'];
			$object->url = $rs->fields['url'];
			$object->created_date = $rs->fields['created_date'];
			$object->is_read = $rs->fields['is_read'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM rssexp_item WHERE id IN (%s)", $ids_list));
		
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
		$fields = SearchFields_RssExpItem::getFields();

		// Sanitize
		if(!isset($fields[$sortBy]))
			unset($sortBy);
		
        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"r.id as %s, ".
			"r.feed_id as %s, ".
			"r.title as %s, ".
			"r.url as %s, ".
			"r.created_date as %s, ".
			"r.is_read as %s, ".
			"f.name as %s ",
			    SearchFields_RssExpItem::ID,
			    SearchFields_RssExpItem::FEED_ID,
			    SearchFields_RssExpItem::TITLE,
			    SearchFields_RssExpItem::URL,
			    SearchFields_RssExpItem::CREATED_DATE,
			    SearchFields_RssExpItem::IS_READ,
			    SearchFields_RssExpItem::FEED_NAME
			 );
		
		$join_sql = 
			"FROM rssexp_item r ".
			"LEFT JOIN rssexp_feed f ON (r.feed_id=f.id) "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=tt.debit_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .  
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_RssExpItem::ID]);
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

class Model_RssExpItem {
	public $id;
	public $feed_id;
	public $title;
	public $url;
	public $created_date;
	public $is_read;
};

class SearchFields_RssExpItem {
	// RssExp_Item
	const ID = 'r_id';
	const FEED_ID = 'r_feed_id';
	const TITLE = 'r_title';
	const URL = 'r_url';
	const CREATED_DATE = 'r_created_date';
	const IS_READ = 'r_is_read';
	
	// RssExp_Feed
	const FEED_NAME = 'f_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'r', 'id', null, $translate->_('common.id')),
			self::FEED_ID => new DevblocksSearchField(self::FEED_ID, 'r', 'feed_id', null, $translate->_('rssexp_item.model.feed_id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'r', 'title', null, $translate->_('rssexp_item.model.title')),
			self::URL => new DevblocksSearchField(self::URL, 'r', 'url', null, $translate->_('common.url')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'r', 'created_date', null, $translate->_('rssexp_item.model.created_date')),
			self::IS_READ => new DevblocksSearchField(self::IS_READ, 'r', 'is_read', null, $translate->_('rssexp_item.model.is_read')),
			
			self::FEED_NAME => new DevblocksSearchField(self::FEED_NAME, 'f', 'name', null, $translate->_('rssexp_feed.model.name')),
		);
	}
};

class C4_RssExpItemView extends C4_AbstractView {
	const DEFAULT_ID = 'rssexp_item_entries';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('common.search_results');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_RssExpItem::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_RssExpItem::TITLE,
			SearchFields_RssExpItem::URL,
			SearchFields_RssExpItem::FEED_NAME,
			SearchFields_RssExpItem::CREATED_DATE,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_RssExpItem::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

//		$workers = DAO_Worker::getAll();
//		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.rss_explorer/templates/rss/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_RssExpItem::TITLE:
			case SearchFields_RssExpItem::URL:
			case SearchFields_RssExpItem::FEED_NAME:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
			case SearchFields_RssExpItem::CREATED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
			case SearchFields_RssExpItem::IS_READ:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl.php');
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
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_RssExpItem::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_RssExpItem::ID]);
		unset($fields[SearchFields_RssExpItem::FEED_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_RssExpItem::ID]);
		unset($fields[SearchFields_RssExpItem::FEED_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_RssExpItem::IS_READ => new DevblocksSearchCriteria(SearchFields_RssExpItem::IS_READ,DevblocksSearchCriteria::OPER_EQ,0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_RssExpItem::TITLE:
			case SearchFields_RssExpItem::URL:
			case SearchFields_RssExpItem::FEED_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			
			case SearchFields_RssExpItem::IS_READ:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_RssExpItem::CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};

?>