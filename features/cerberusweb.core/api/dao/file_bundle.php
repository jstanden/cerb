<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class DAO_FileBundle extends Cerb_ORMHelper {
	const CACHE_ALL = 'cerb.dao.file_bundles.all';
	
	const ID = 'id';
	const NAME = 'name';
	const TAG = 'tag';
	const UPDATED_AT = 'updated_at';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();

		$sql = "INSERT INTO file_bundle () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);

		// Make a diff for the requested objects in batches

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_FILE_BUNDLE, $batch_ids);
			}
				
			// Make changes
			parent::_update($batch_ids, 'file_bundle', $fields);
				
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.file_bundle.update',
						array(
							'fields' => $fields,
						)
					)
				);

				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_FILE_BUNDLE, $batch_ids);
			}
		}
		
		self::clearCache();
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('file_bundle', $fields, $where);
		self::clearCache();
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_FileBundle[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || null === ($bundles = $cache->load(self::CACHE_ALL))) {
			$bundles = self::getWhere(
				null,
				null,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			$cache->save($bundles, self::CACHE_ALL);
		}
		
		return $bundles;
	}

	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_FileBundle[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		// SQL
		$sql = "SELECT id, name, tag, updated_at, owner_context, owner_context_id ".
			"FROM file_bundle ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}

		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FileBundle
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$bundles = DAO_FileBundle::getAll();
		
		if(isset($bundles[$id]))
			return $bundles[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param string $tag
	 * @return Model_FileBundle
	 */
	static function getByTag($tag) {
		$bundles = DAO_FileBundle::getAll();
		
		$results = array_filter($bundles, function($bundle) use ($tag) { /* @var $bundle Model_FileBundle */
			if($bundle->tag == $tag)
				return true;
			
			return false;
		});
		
		if(!empty($results))
			return array_shift($results);

		return null;
	}

	/**
	 * @param resource $rs
	 * @return Model_FileBundle[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_FileBundle();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->tag = $row['tag'];
			$object->updated_at = intval($row['updated_at']);
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function random() {
		return self::_getRandom('file_bundle');
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		if(empty($ids))
			return;

		$ids_list = implode(',', $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM file_bundle WHERE id IN (%s)", $ids_list));

		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_FILE_BUNDLE,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_FileBundle::getFields();

		switch($sortBy) {
			case SearchFields_FileBundle::VIRTUAL_OWNER:
				$sortBy = SearchFields_FileBundle::OWNER_CONTEXT;
				
				if(!in_array($sortBy, $columns))
					$columns[] = $sortBy;
				break;
		}
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);

		$select_sql = sprintf("SELECT ".
			"file_bundle.id as %s, ".
			"file_bundle.name as %s, ".
			"file_bundle.tag as %s, ".
			"file_bundle.updated_at as %s, ".
			"file_bundle.owner_context as %s, ".
			"file_bundle.owner_context_id as %s ",
			SearchFields_FileBundle::ID,
			SearchFields_FileBundle::NAME,
			SearchFields_FileBundle::TAG,
			SearchFields_FileBundle::UPDATED_AT,
			SearchFields_FileBundle::OWNER_CONTEXT,
			SearchFields_FileBundle::OWNER_CONTEXT_ID
		);
			
		$join_sql = "FROM file_bundle ".
			(isset($tables['context_link']) ? sprintf("INNER JOIN context_link ON (context_link.to_context = %s AND context_link.to_context_id = file_bundle.id) ", Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_FILE_BUNDLE)) : " ").
			'';

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'file_bundle.id',
			$select_sql,
			$join_sql
		);

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";

		// Virtuals

		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);

		array_walk_recursive(
			$params,
			array('DAO_FileBundle', '_translateVirtualParameters'),
			$args
		);

		return array(
			'primary_table' => 'file_bundle',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}

	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_FILE_BUNDLE;
		$from_index = 'file_bundle.id';

		$param_key = $param->field;
		settype($param_key, 'string');

		switch($param_key) {
			case SearchFields_FileBundle::FULLTEXT_COMMENT_CONTENT:
				$search = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID);
				$query = $search->getQueryFromParam($param);
				
				if(false === ($ids = $search->query($query, array('context_crc32' => sprintf("%u", crc32($from_context)))))) {
					$args['where_sql'] .= 'AND 0 ';
				
				} elseif(is_array($ids)) {
					$from_ids = DAO_Comment::getContextIdsByContextAndIds($from_context, $ids);
					
					$args['where_sql'] .= sprintf('AND %s IN (%s) ',
						$from_index,
						implode(', ', (!empty($from_ids) ? $from_ids : array(-1)))
					);
					
				} elseif(is_string($ids)) {
					$db = DevblocksPlatform::getDatabaseService();
					$temp_table = sprintf("_tmp_%s", uniqid());
					
					$db->ExecuteSlave(sprintf("CREATE TEMPORARY TABLE %s (PRIMARY KEY (id)) SELECT DISTINCT context_id AS id FROM comment INNER JOIN %s ON (%s.id=comment.id)",
						$temp_table,
						$ids,
						$ids
					));
					
					$args['join_sql'] .= sprintf("INNER JOIN %s ON (%s.id=%s) ",
						$temp_table,
						$temp_table,
						$from_index
					);
				}
				break;
			
			case SearchFields_FileBundle::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;

			case SearchFields_FileBundle::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;

			case SearchFields_FileBundle::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
				
			case SearchFields_FileBundle::VIRTUAL_OWNER:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
				$args['has_multiple_values'] = true;
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					if(!empty($context_id)) {
						$wheres[] = sprintf("(file_bundle.owner_context = %s AND file_bundle.owner_context_id = %d)",
							Cerb_ORMHelper::qstr($context),
							$context_id
						);
						
					} else {
						$wheres[] = sprintf("(file_bundle.owner_context = %s)",
							Cerb_ORMHelper::qstr($context)
						);
					}
					
				}
				
				if(!empty($wheres))
					$args['where_sql'] .= 'AND ' . implode(' OR ', $wheres);
				
				break;
		}
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
			($has_multiple_values ? 'GROUP BY file_bundle.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
		} else {
			$rs = $db->ExecuteSlave($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
			$total = mysqli_num_rows($rs);
		}

		$results = array();

		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_FileBundle::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);

		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT file_bundle.id) " : "SELECT COUNT(file_bundle.id) ").
				$join_sql.
				$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}

		mysqli_free_result($rs);

		return array($results,$total);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_FileBundle implements IDevblocksSearchFields {
	const ID = 'f_id';
	const NAME = 'f_name';
	const TAG = 'f_tag';
	const UPDATED_AT = 'f_updated_at';
	const OWNER_CONTEXT = 'f_owner_context';
	const OWNER_CONTEXT_ID = 'f_owner_context_id';

	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';

	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';

	const VIRTUAL_OWNER = '*_owner';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'file_bundle', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'file_bundle', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::TAG => new DevblocksSearchField(self::TAG, 'file_bundle', 'tag', $translate->_('common.tag'), Model_CustomField::TYPE_SINGLE_LINE),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'file_bundle', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'file_bundle', 'owner_context', $translate->_('common.owner_context')),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'file_bundle', 'owner_context_id', $translate->_('common.owner_context_id')),

			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT'),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner')),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
				
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);

		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_FILE_BUNDLE,
		));

		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_FileBundle {
	public $id;
	public $name;
	public $tag;
	public $updated_at;
	public $owner_context;
	public $owner_context_id;
	
	function isReadableByActor($actor) {
		return CerberusContexts::isReadableByActor($this->owner_context, $this->owner_context_id, $actor);		
	}
	
	function isWriteableByActor($actor) {
		return CerberusContexts::isWriteableByActor($this->owner_context, $this->owner_context_id, $actor);		
	}
	
	function getAttachments() {
		return DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_FILE_BUNDLE, $this->id);
	}
};

class View_FileBundle extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'file_bundles';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('File Bundles');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_FileBundle::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_FileBundle::NAME,
			SearchFields_FileBundle::TAG,
			SearchFields_FileBundle::UPDATED_AT,
			SearchFields_FileBundle::VIRTUAL_OWNER,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_FileBundle::OWNER_CONTEXT,
			SearchFields_FileBundle::OWNER_CONTEXT_ID,
			SearchFields_FileBundle::FULLTEXT_COMMENT_CONTENT,
			SearchFields_FileBundle::VIRTUAL_CONTEXT_LINK,
			SearchFields_FileBundle::VIRTUAL_HAS_FIELDSET,
			SearchFields_FileBundle::VIRTUAL_WATCHERS,
		));

		$this->addParamsHidden(array(
			SearchFields_FileBundle::OWNER_CONTEXT,
			SearchFields_FileBundle::OWNER_CONTEXT_ID,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_FileBundle::search(
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

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_FileBundle', $ids);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_FileBundle', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);

		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
				
			switch($field_key) {
				// Fields
				//	case SearchFields_FileBundle::EXAMPLE:
				//		$pass = true;
				//		break;
						
				// Virtuals
				case SearchFields_FileBundle::VIRTUAL_CONTEXT_LINK:
				case SearchFields_FileBundle::VIRTUAL_HAS_FIELDSET:
				case SearchFields_FileBundle::VIRTUAL_OWNER:
				case SearchFields_FileBundle::VIRTUAL_WATCHERS:
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
			//			case SearchFields_FileBundle::EXAMPLE_BOOL:
			//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_FileBundle', $column);
			//				break;

			//			case SearchFields_FileBundle::EXAMPLE_STRING:
			//				$counts = $this->_getSubtotalCountForStringColumn('DAO_FileBundle', $column);
			//				break;

			case SearchFields_FileBundle::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_FileBundle', CerberusContexts::CONTEXT_FILE_BUNDLE, $column);
				break;

			case SearchFields_FileBundle::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_FileBundle', CerberusContexts::CONTEXT_FILE_BUNDLE, $column);
				break;

			case SearchFields_FileBundle::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_FileBundle', $column);
				break;
					
			case SearchFields_FileBundle::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_FileBundle', CerberusContexts::CONTEXT_FILE_BUNDLE, $column, DAO_FileBundle::OWNER_CONTEXT, DAO_Snippet::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_FileBundle', $column, 'file_bundle.id');
				}

				break;
		}

		return $counts;
	}

	function getQuickSearchFields() {
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_FileBundle::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_FileBundle::FULLTEXT_COMMENT_CONTENT),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_FileBundle::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'tag' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_FileBundle::TAG, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_FileBundle::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_FileBundle::VIRTUAL_WATCHERS),
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_FILE_BUNDLE, $fields, null);
		
		// Engine/schema examples: Comments
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['comments']['examples'] = $ft_examples;
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamsFromQuickSearchFields($fields) {
		$search_fields = $this->getQuickSearchFields();
		$params = DevblocksSearchCriteria::getParamsFromQueryFields($fields, $search_fields);

		// Handle virtual fields and overrides
		if(is_array($fields))
		foreach($fields as $k => $v) {
			switch($k) {
				// ...
			}
		}
		
		$this->renderPage = 0;
		$this->addParams($params, true);
		
		return $params;
	}
	
	function render() {
		$this->_sanitize();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FILE_BUNDLE);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/file_bundle/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_FileBundle::OWNER_CONTEXT:
			case SearchFields_FileBundle::NAME:
			case SearchFields_FileBundle::TAG:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case SearchFields_FileBundle::OWNER_CONTEXT_ID:
			case SearchFields_FileBundle::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;

			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;

			case SearchFields_FileBundle::UPDATED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_FileBundle::FULLTEXT_COMMENT_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;

			case SearchFields_FileBundle::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;

			case SearchFields_FileBundle::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_FILE_BUNDLE);
				break;

			case SearchFields_FileBundle::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_owner.tpl');
				break;
				
			case SearchFields_FileBundle::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
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
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;

		$translate = DevblocksPlatform::getTranslationService();

		switch($key) {
			case SearchFields_FileBundle::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;

			case SearchFields_FileBundle::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_FileBundle::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners');
				break;
					
			case SearchFields_FileBundle::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_FileBundle::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_FileBundle::OWNER_CONTEXT:
			case SearchFields_FileBundle::NAME:
			case SearchFields_FileBundle::TAG:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			case SearchFields_FileBundle::OWNER_CONTEXT_ID:
			case SearchFields_FileBundle::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_FileBundle::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_FileBundle::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;

			case SearchFields_FileBundle::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;

			case SearchFields_FileBundle::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_FileBundle::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;

			case SearchFields_FileBundle::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
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
		@set_time_limit(600); // 10m

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
					// [TODO] Implement actions
					case 'example':
						//$change_fields[DAO_FileBundle::EXAMPLE] = 'some value';
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
				list($objects,$null) = DAO_FileBundle::search(
					array(),
					$this->getParams(),
					100,
					$pg++,
					SearchFields_FileBundle::ID,
					true,
					false
				);
				$ids = array_merge($ids, array_keys($objects));

	} while(!empty($objects));

	$batch_total = count($ids);
	for($x=0;$x<=$batch_total;$x+=100) {
		$batch_ids = array_slice($ids,$x,100);
			
		if(!empty($change_fields)) {
			DAO_FileBundle::update($batch_ids, $change_fields);
		}

		// Custom Fields
		self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_FILE_BUNDLE, $custom_fields, $batch_ids);
			
		unset($batch_ids);
	}

	unset($ids);
	}
};

class Context_FileBundle extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	function getRandom() {
		return DAO_FileBundle::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=file_bundle&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$file_bundle = DAO_FileBundle::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($file_bundle->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $file_bundle->id,
			'name' => $file_bundle->name,
			'permalink' => $url,
		);
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($file_bundle, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'File Bundle:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FILE_BUNDLE);

		// Polymorph
		if(is_numeric($file_bundle)) {
			$file_bundle = DAO_FileBundle::get($file_bundle);
		} elseif($file_bundle instanceof Model_FileBundle) {
			// It's what we want already.
		} elseif(is_array($file_bundle)) {
			$file_bundle = Cerb_ORMHelper::recastArrayToModel($file_bundle, 'Model_FileBundle');
		} else {
			$file_bundle = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'tag' => $prefix.$translate->_('common.tag'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'tag' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_FILE_BUNDLE;
		$token_values['_types'] = $token_types;
		
		if($file_bundle) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $file_bundle->name;
			$token_values['id'] = $file_bundle->id;
			$token_values['name'] = $file_bundle->name;
			$token_values['tag'] = $file_bundle->tag;
			$token_values['updated_at'] = $file_bundle->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($file_bundle, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=file_bundle&id=%d-%s",$file_bundle->id, DevblocksPlatform::strToPermalink($file_bundle->name)), true);
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_FILE_BUNDLE;
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
		$defaults->is_ephemeral = true;
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'File Bundles';

		$params_required = array();
		
		$worker_group_ids = array_keys($active_worker->getMemberships());
		$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
		
		// Restrict owners
		$param_ownership = array(
			DevblocksSearchCriteria::GROUP_OR,
			SearchFields_FileBundle::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_FileBundle::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_APPLICATION),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_FileBundle::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_FileBundle::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_WORKER),
				SearchFields_FileBundle::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_FileBundle::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_EQ,$active_worker->id),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_FileBundle::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_FileBundle::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_GROUP),
				SearchFields_FileBundle::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_FileBundle::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_group_ids),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_FileBundle::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_FileBundle::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_ROLE),
				SearchFields_FileBundle::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_FileBundle::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_role_ids),
			),
		);
		$params_required['_ownership'] = $param_ownership;
		
		$view->addParamsRequired($params_required, true);
		
		$view->renderSortBy = SearchFields_FileBundle::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'File Bundle';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_FileBundle::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_FileBundle::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($file_bundle = DAO_FileBundle::get($context_id))) {
			// ACL
			if(!$file_bundle->isWriteableByActor($active_worker))
				return;
			
			$tpl->assign('model', $file_bundle);
		}
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FILE_BUNDLE, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_FILE_BUNDLE, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}

		// Ownership

		$available_owners = CerberusContexts::getAvailableOwners($active_worker);
		$tpl->assign('available_owners', $available_owners);
		
		// Attachments
		
		$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_FILE_BUNDLE, $context_id);
		$tpl->assign('attachments', $attachments);
		
		// Comments
		
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_FILE_BUNDLE, $context_id);
		$comments = array_reverse($comments, true);
		$tpl->assign('comments', $comments);
		
		$tpl->display('devblocks:cerberusweb.core::internal/file_bundle/peek.tpl');
	}
};
