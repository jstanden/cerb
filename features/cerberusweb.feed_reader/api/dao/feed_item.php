<?php
class DAO_FeedItem extends C4_ORMHelper {
	const ID = 'id';
	const FEED_ID = 'feed_id';
	const GUID = 'guid';
	const TITLE = 'title';
	const URL = 'url';
	const CREATED_DATE = 'created_date';
	const IS_CLOSED = 'is_closed';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO feed_item () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'feed_item', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('feed_item', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_FeedItem[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, feed_id, guid, title, url, created_date, is_closed ".
			"FROM feed_item ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FeedItem	 */
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
	 * @return Model_FeedItem[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_FeedItem();
			$object->id = $row['id'];
			$object->feed_id = $row['feed_id'];
			$object->guid = $row['guid'];
			$object->title = $row['title'];
			$object->url = $row['url'];
			$object->created_date = $row['created_date'];
			$object->is_closed = $row['is_closed'];
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
		
		$db->Execute(sprintf("DELETE FROM feed_item WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	// [TODO] Listen on cron.maint
	static function maint() {
    	$db = DevblocksPlatform::getDatabaseService();
    	$logger = DevblocksPlatform::getConsoleLog();
		
		// Purge orphaned FROM context links  
		$sql = "DELETE QUICK context_link FROM context_link LEFT JOIN feed_item ON (context_link.from_context_id=feed_item.id) WHERE context_link.from_context = 'cerberusweb.contexts.feed.item' AND feed_item.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned outbound feed_item context links.');

		// Purge orphaned TO context links  
		$sql = "DELETE QUICK context_link FROM context_link LEFT JOIN feed_item ON (context_link.to_context_id=feed_item.id) WHERE context_link.to_context = 'cerberusweb.contexts.feed.item' AND feed_item.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned inbound feed_item context links.');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_FeedItem::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"feed_item.id as %s, ".
			"feed_item.feed_id as %s, ".
			"feed_item.guid as %s, ".
			"feed_item.title as %s, ".
			"feed_item.url as %s, ".
			"feed_item.created_date as %s, ".
			"feed_item.is_closed as %s ",
				SearchFields_FeedItem::ID,
				SearchFields_FeedItem::FEED_ID,
				SearchFields_FeedItem::GUID,
				SearchFields_FeedItem::TITLE,
				SearchFields_FeedItem::URL,
				SearchFields_FeedItem::CREATED_DATE,
				SearchFields_FeedItem::IS_CLOSED
			);
			
		$join_sql = "FROM feed_item ".
		
		// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.feed.item' AND context_link.to_context_id = feed_item.id) " : " ")
			;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'feed_item.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Virtuals
		foreach($params as $param) {
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_FeedItem::VIRTUAL_WATCHERS:
					$has_multiple_values = true;
					$from_context = 'cerberusweb.contexts.feed.item';
					$from_index = 'feed_item.id';
					
					// Join and return anything
					if(DevblocksSearchCriteria::OPER_TRUE == $param->operator) {
						$join_sql .= sprintf("LEFT JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker') ", $from_context, $from_index);
					} elseif(empty($param->value)) { // empty
						// Either any watchers (1 or more); or no watchers
						if(DevblocksSearchCriteria::OPER_NIN == $param->operator || DevblocksSearchCriteria::OPER_NEQ == $param->operator) {
							$join_sql .= sprintf("LEFT JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker') ", $from_context, $from_index);
							$where_sql .= "AND context_watcher.to_context_id IS NOT NULL ";
						} else {
							$join_sql .= sprintf("LEFT JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker') ", $from_context, $from_index);
							$where_sql .= "AND context_watcher.to_context_id IS NULL ";
						}
					// Specific watchers
					} else {
						$join_sql .= sprintf("INNER JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker' AND context_watcher.to_context_id IN (%s)) ",
							$from_context,
							$from_index,
							implode(',', $param->value)
						);
					}
					break;
			}
		}
		
		return array(
			'primary_table' => 'feed_item',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
    /**
     * Enter description here...
     *
     * @param array $columns
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
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
			($has_multiple_values ? 'GROUP BY feed_item.id ' : '').
			$sort_sql;
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_FeedItem::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT feed_item.id) " : "SELECT COUNT(feed_item.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_FeedItem implements IDevblocksSearchFields {
	const ID = 'fi_id';
	const FEED_ID = 'fi_feed_id';
	const GUID = 'fi_guid';
	const TITLE = 'fi_title';
	const URL = 'fi_url';
	const CREATED_DATE = 'fi_created_date';
	const IS_CLOSED = 'fi_is_closed';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	const VIRTUAL_WATCHERS = '*_workers';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'feed_item', 'id', $translate->_('common.id')),
			self::FEED_ID => new DevblocksSearchField(self::FEED_ID, 'feed_item', 'feed_id', $translate->_('dao.feed_item.feed_id')),
			self::GUID => new DevblocksSearchField(self::GUID, 'feed_item', 'guid', $translate->_('dao.feed_item.guid')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'feed_item', 'title', $translate->_('common.title')),
			self::URL => new DevblocksSearchField(self::URL, 'feed_item', 'url', $translate->_('common.url')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'feed_item', 'created_date', $translate->_('common.created')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'feed_item', 'is_closed', $translate->_('dao.feed_item.is_closed')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.feed.item');

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_FeedItem {
	public $id;
	public $feed_id;
	public $guid;
	public $title;
	public $url;
	public $created_date;
	public $is_closed;
};

class View_FeedItem extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'feed_items';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Headlines');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_FeedItem::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_FeedItem::URL,
			SearchFields_FeedItem::FEED_ID,
			SearchFields_FeedItem::CREATED_DATE,
		);
		$this->addColumnsHidden(array(
			SearchFields_FeedItem::GUID,
			SearchFields_FeedItem::ID,
			SearchFields_FeedItem::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_FeedItem::GUID,
			SearchFields_FeedItem::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_FeedItem::search(
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
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_FeedItem', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Feed
				case SearchFields_FeedItem::FEED_ID:
					$pass = true;
					break;
					
				// Booleans
				case SearchFields_FeedItem::IS_CLOSED:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_FeedItem::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_FeedItem::FEED_ID:
				$feeds = DAO_Feed::getWhere(); // [TODO] Cache!!
				$label_map = array();
				foreach($feeds as $feed_id => $feed) { /* @var $feed Model_Feed */
					$label_map[$feed_id] = $feed->name;
				}
				$counts = $this->_getSubtotalCountForStringColumn('DAO_FeedItem', $column, $label_map, 'in', 'feed_id[]');
				break;

			case SearchFields_FeedItem::IS_CLOSED:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_FeedItem', $column);
				break;
				
				
			case SearchFields_FeedItem::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_FeedItem', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_FeedItem', $column, 'feed_item.id');
				}
				
				break;
		}
		
		return $counts;
	}	
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->assign('workers', DAO_Worker::getAll());
		
		// [TODO] Cache getAll()
		$feeds = DAO_Feed::getWhere();
		$tpl->assign('feeds', $feeds);
		
		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.feed_reader::feeds/item/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_FeedItem::VIRTUAL_WATCHERS:
				if(empty($param->value)) {
					echo "There are no <b>watchers</b>";
					
				} elseif(is_array($param->value)) {
					$workers = DAO_Worker::getAll();
					$strings = array();
					
					foreach($param->value as $worker_id) {
						if(isset($workers[$worker_id]))
							$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
					
					echo sprintf("Watcher is %s", implode(' or ', $strings));
				}
				break;
		}
	}	
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_FeedItem::GUID:
			case SearchFields_FeedItem::TITLE:
			case SearchFields_FeedItem::URL:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_FeedItem::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_FeedItem::IS_CLOSED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_FeedItem::CREATED_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_FeedItem::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
			case SearchFields_FeedItem::FEED_ID:
				$feeds = DAO_Feed::getWhere();
				$tpl->assign('feeds', $feeds);
				$tpl->display('devblocks:cerberusweb.feed_reader::feeds/item/filter/feed.tpl');
				break;
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_FeedItem::FEED_ID:
				$feeds = DAO_Feed::getWhere();
				$strings = array();

				if(empty($values)) {
					echo "(blank)";
					break;
				}
				
				foreach($values as $val) {
					if(empty($val))
						$strings[] = "";
					elseif(!isset($feeds[$val]))
						continue;
					else
						$strings[] = $feeds[$val]->name;
				}
				echo implode(", ", $strings);
				break;			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_FeedItem::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_FeedItem::GUID:
			case SearchFields_FeedItem::TITLE:
			case SearchFields_FeedItem::URL:
			case 'placeholder_string':
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_FeedItem::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_FeedItem::CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_FeedItem::IS_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $worker_ids);
				break;
				
			case SearchFields_FeedItem::FEED_ID:
				@$feed_ids = DevblocksPlatform::importGPC($_REQUEST['feed_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$feed_ids);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'is_closed':
					$change_fields[DAO_FeedItem::IS_CLOSED] = !empty($v) ? 1 : 0;
					break;
					
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_FeedItem::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_FeedItem::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_FeedItem::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields('cerberusweb.contexts.feed.item', $custom_fields, $batch_ids);
			
			// Watchers
			if(isset($do['watchers']) && is_array($do['watchers'])) {
				$watcher_params = $do['watchers'];
				foreach($batch_ids as $batch_id) {
					if(isset($watcher_params['add']) && is_array($watcher_params['add']))
						CerberusContexts::addWatchers('cerberusweb.contexts.feed.item', $batch_id, $watcher_params['add']);
					if(isset($watcher_params['remove']) && is_array($watcher_params['remove']))
						CerberusContexts::removeWatchers('cerberusweb.contexts.feed.item', $batch_id, $watcher_params['remove']);
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_FeedItem extends Extension_DevblocksContext {
	function getMeta($context_id) {
		$item = DAO_FeedItem::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $item->id,
			'name' => $item->title,
			'permalink' => $url_writer->write('c=feeds&i=item&id='.$context_id, true),
		);
	}
	
	function getContext($item, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Feed:Item:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.feed.item');

		// Polymorph
		if(is_numeric($item)) {
			$item = DAO_FeedItem::get($item);
		} elseif($item instanceof Model_FeedItem) {
			// It's what we want already.
		} else {
			$item = null;
		}
		
		// Token labels
		$token_labels = array(
//			'created|date' => $prefix.$translate->_('common.created'),
//			'is_closed' => $prefix.$translate->_('call_entry.model.is_closed'),
//			'is_outgoing' => $prefix.$translate->_('call_entry.model.is_outgoing'),
//			'phone' => $prefix.$translate->_('call_entry.model.phone'),
//			'subject' => $prefix.$translate->_('message.header.subject'),
//			'updated|date' => $prefix.$translate->_('common.updated'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Call token values
		if($item) {
			$token_values['id'] = $item->id;
//			$token_values['created'] = $item->created_date;
//			$token_values['is_closed'] = $item->is_closed;
//			$token_values['is_outgoing'] = $item->is_outgoing;
//			$token_values['phone'] = $item->phone;
//			$token_values['subject'] = $item->subject;
//			$token_values['updated'] = $item->updated_date;

			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds('cerberusweb.contexts.feed.item', $item->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $item)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $item)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// [TODO] Feed
		
		// Person
//		@$address_id = $item->primary_email_id;
//		$merge_token_labels = array();
//		$merge_token_values = array();
//		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $address_id, $merge_token_labels, $merge_token_values, '', true);
//
//		CerberusContexts::merge(
//			'email_',
//			'Lead:',
//			$merge_token_labels,
//			$merge_token_values,
//			$token_labels,
//			$token_values
//		);

		return true;
	}

	function getChooserView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
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
			SearchFields_FeedItem::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_FeedItem::IS_CLOSED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_FeedItem::CREATED_DATE;
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
				new DevblocksSearchCriteria(SearchFields_FeedItem::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_FeedItem::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};