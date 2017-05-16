<?php
/**
 * Devblocks DAO
 * @author Jeff Standen, Webgroup Media LLC <jeff@webgroupmedia.com>
 * @version 2017-03-30
 */

$plugin_id = 'example.plugin';
$plugin_namespace = 'example';

$tables = array();

$tables['Example_Object'] = "
id INT UNSIGNED NOT NULL AUTO_INCREMENT,
name VARCHAR(255) DEFAULT '',
updated_at INT UNSIGNED NOT NULL DEFAULT 0,
";

foreach ( $tables as $table_name => $field_strs ) {
	// Class
	$class_name = str_replace ( ' ', '', ucwords ( str_replace ( '_', ' ', $table_name ) ) );
	$object_name = ucwords ( str_replace ( '_', ' ', $table_name ) );
	
	$table_name = strtolower ( $table_name );
	
	// Fields
	$fields = array ();
	
	$schema = trim ( $field_strs );
	$schema = str_replace ( array (
		"\r" 
	), array (
		"\n" 
	), $schema );
	$schema = str_replace ( array (
		"\n\n" 
	), array (
		"\n" 
	), $schema );
	
	foreach ( explode ( "\n", $schema ) as $field_str ) {
		$field_props = explode ( ' ', rtrim ( $field_str, ",\n\r " ) );
		$fields [trim ( $field_props [0] )] = trim ( $field_props [1] );
	}
	
	// Contexts
	
	$ctx_var_model = $table_name;
	$ctx_ext_id = sprintf ( "cerberusweb.contexts.%s", strtolower ( str_replace ( '_', '.', $table_name ) ) );
	?>

<h2>DAO</h2>

<b>api/dao/<?php echo $table_name; ?>.php</b>
<br>
<textarea style="width: 98%; height: 200px;">
class DAO_<?php echo $class_name; ?> extends Cerb_ORMHelper {
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\tconst %s = '%s';\n", strtoupper ( $field_name ), $field_name );
	}
	?>

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO <?php echo $table_name; ?> () VALUES ()";
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
				//CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, '<?php echo $table_name; ?>', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.<?php echo $table_name; ?>.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				//DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('<?php echo $table_name; ?>', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT <?php echo implode(', ', array_keys($fields)); ?> ".
			"FROM <?php echo $table_name; ?> ".
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
	 *
	 * @param bool $nocache
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::getCacheService();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_<?php echo $class_name; ?>::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_<?php echo $class_name; ?>
	 */
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
	 * 
	 * @param array $ids
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return array();

		if(!method_exists(get_called_class(), 'getWhere'))
			return array();

		$db = DevblocksPlatform::getDatabaseService();

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');

		$models = array();

		$results = static::getWhere(sprintf("id IN (%s)",
			implode(',', $ids)
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}	
	
	/**
	 * @param resource $rs
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_<?php echo $class_name; ?>();
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\t\t\t\$object->%s = \$row['%s'];\n", $field_name, $field_name );
	}
	?>
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('<?php echo $table_name; ?>');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM <?php echo $table_name; ?> WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => '<?php echo $ctx_ext_id; ?>',
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_<?php echo $class_name; ?>::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_<?php echo $class_name; ?>', $sortBy);
		
		$select_sql = sprintf("SELECT ".
<?php
	$num_fields = 0;
	foreach ( $fields as $field_name => $field_type ) {
		$num_fields ++;
		printf ( "\t\t\t\"%s.%s as %%s%s", $table_name, $field_name, (($num_fields == count ( $fields )) ? " \",\n" : ", \".\n") ) // ending
;
	}
	$num_fields = 0;
	foreach ( $fields as $field_name => $field_type ) {
		$num_fields ++;
		printf ( "\t\t\t\tSearchFields_%s::%s%s", $class_name, strtoupper ( $field_name ), ($num_fields == count ( $fields )) ? "\n" : ",\n" );
	}
	?>
			);
			
		$join_sql = "FROM <?php echo $table_name; ?> ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_<?php echo $class_name; ?>');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
	
		array_walk_recursive(
			$params,
			array('DAO_<?php echo $class_name; ?>', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => '<?php echo $table_name; ?>',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = '<?php echo $ctx_ext_id; ?>';
		$from_index = '<?php echo $table_name; ?>.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
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
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_<?php echo $class_name; ?>::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(<?php echo $table_name; ?>.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};
</textarea>

<textarea style="width: 98%; height: 200px;">
class SearchFields_<?php echo $class_name; ?> extends DevblocksSearchFields {
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\tconst %s = '%s_%s';\n", strtoupper ( $field_name ), substr ( $table_name, 0, 1 ), $field_name );
	}
	?>

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return '<?php echo $table_name; ?>.id';
	}
	
	static function getCustomFieldContextKeys() {
		// [TODO] Context
		return array(
			'' => new DevblocksSearchFieldContextKeys('<?php echo $table_name; ?>.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, '<?php echo $ctx_ext_id; ?>', self::getPrimaryKey());
				break;
				
			/*
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, '', self::getPrimaryKey());
				break;
			*/
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
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
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\t\t\tself::%s => new DevblocksSearchField(self::%s, '%s', '%s', \$translate->_('dao.%s.%s'), null, true),\n", strtoupper ( $field_name ), strtoupper ( $field_name ), $table_name, $field_name, $table_name, $field_name );
	}
	?>

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};
</textarea>

<textarea style="width: 98%; height: 200px;">
class Model_<?php echo $class_name; ?> {
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\tpublic \$%s;\n", $field_name );
	}
	;
	?>
};
</textarea>

<textarea style="width: 98%; height: 200px;">
class View_<?php echo $class_name; ?> extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = '<?php echo mb_strtolower($class_name); ?>';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = DevblocksPlatform::translateCapitalized('<?php echo $class_name; ?>');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_<?php echo $class_name; ?>::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\t\t\tSearchFields_%s::%s,\n", $class_name, strtoupper ( $field_name ) );
	}
	?>
		);
		// [TODO] Filter fields
		$this->addColumnsHidden(array(
			SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK,
			SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET,
			SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS,
		));
		
		// [TODO] Filter fields
		$this->addParamsHidden(array(
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_<?php echo $class_name; ?>::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_<?php echo $class_name; ?>');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_<?php echo $class_name; ?>', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_<?php echo $class_name; ?>', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
//				case SearchFields_<?php echo $class_name; ?>::EXAMPLE:
//					$pass = true;
//					break;
					
				// Virtuals
				case SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK:
				case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
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
		$context = '<?php echo $ctx_ext_id; ?>';

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
//			case SearchFields_<?php echo $class_name; ?>::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_<?php echo $class_name; ?>::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		// [TODO] Implement quick search fields
		$search_fields = SearchFields_<?php echo $class_name; ?>::getFields();
	
		/*
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_<?php echo $class_name; ?>::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_<?php echo $class_name; ?>::CREATED_AT),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_<?php echo $class_name; ?>::ID),
					'examples' => [
						['type' => 'chooser', 'context' => '<?php echo $ctx_ext_id; ?>', 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_<?php echo $class_name; ?>::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_<?php echo $class_name; ?>::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS),
				),
		);
		*/
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext('<?php echo $ctx_ext_id; ?>', $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	// [TODO] Implement quick search fields
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('<?php echo $ctx_ext_id; ?>');
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:<?php echo $plugin_id; ?>::<?php echo $table_name; ?>/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		// [TODO] Move the fields into the proper data type
		switch($field) {
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\t\t\tcase SearchFields_%s::%s:\n", $class_name, strtoupper ( $field_name ) );
	}
	?>
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, '<?php echo $ctx_ext_id; ?>');
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
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
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_<?php echo $class_name; ?>::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
<?php
	foreach ( $fields as $field_name => $field_type ) {
		printf ( "\t\t\tcase SearchFields_%s::%s:\n", $class_name, strtoupper ( $field_name ) );
	}
	?>
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
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
};
</textarea>

<b>plugin.xml</b>
<br>
<textarea style="width: 98%; height: 200px;">
<file path="api/dao/<?php echo $table_name; ?>.php">
	<class name="Context_<?php echo $class_name; ?>" />
	<class name="DAO_<?php echo $class_name; ?>" />
	<class name="Model_<?php echo $class_name; ?>" />
	<class name="SearchFields_<?php echo $class_name; ?>" />
	<class name="View_<?php echo $class_name; ?>" />
</file>
</textarea>

<b>strings.xml</b>
<br>
<textarea style="width: 98%; height: 200px;">
<!-- <?php echo $class_name; ?> -->

<?php foreach($fields as $field_name => $field_type) { ?>
<tu tuid='dao.<?php echo $table_name; ?>.<?php echo $field_name; ?>'>
	<tuv xml:lang="en_US">
	<seg><?php echo ucwords(str_replace('_',' ',$field_name)); ?></seg></tuv>
</tu>
<?php } ?>
</textarea>

<h2>Context</h2>

<b>plugin.xml</b>
<br>
<textarea style="width: 98%; height: 200px;">
		<!-- Contexts -->
		
		<extension point="devblocks.context">
			<id><?php echo $ctx_ext_id; ?></id>
			<name><?php echo $object_name; ?></name>
			<class>
				<file>api/dao/<?php echo $table_name; ?>.php</file>
				<name>Context_<?php echo $class_name; ?></name>
			</class>
			<params>
				<param key="names">
					<value>
						<data key="<?php echo $table_name; ?>" value="singular" />
						<data key="<?php echo $table_name; ?>" value="plural" />
						<data key="<?php echo $table_name; ?>" value="singular short" />
						<data key="<?php echo $table_name; ?>" value="plural short" />
					</value>
				</param>
				<param key="alias" value="<?php echo $table_name; ?>" />
				<param key="dao_class" value="DAO_<?php echo $class_name; ?>" />
				<param key="view_class" value="View_<?php echo $class_name;?>" />
				<param key="options">
					<value>
						<data key="cards" />
						<data key="create" />
						<data key="custom_fields" />
						<data key="links" />
						<data key="search" />
						<data key="snippets" />
						<data key="va_variable" />
						<data key="workspace" />
					</value>
				</param>
			</params>
		</extension>
</textarea>

<b>api/dao/<?php echo $table_name; ?>.php</b><br>
<textarea style="width:98%;height:200px;">
class Context_<?php echo $class_name;?> extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	const ID = '<?php echo $ctx_ext_id; ?>';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}

	function getRandom() {
		return DAO_<?php echo $class_name; ?>::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=<?php echo $table_name; ?>&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$<?php echo $ctx_var_model; ?> = DAO_<?php echo $class_name; ?>::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($<?php echo $ctx_var_model; ?>->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $<?php echo $ctx_var_model; ?>->id,
			'name' => $<?php echo $ctx_var_model; ?>->name,
			'permalink' => $url,
			//'updated' => $<?php echo $ctx_var_model; ?>->updated_at, // [TODO]
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($<?php echo $ctx_var_model; ?>, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = '<?php echo $object_name; ?>:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('<?php echo $ctx_ext_id; ?>');

		// Polymorph
		if(is_numeric($<?php echo $ctx_var_model; ?>)) {
			$<?php echo $ctx_var_model; ?> = DAO_<?php echo $class_name; ?>::get($<?php echo $ctx_var_model; ?>);
		} elseif($<?php echo $ctx_var_model; ?> instanceof Model_<?php echo $class_name; ?>) {
			// It's what we want already.
		} elseif(is_array($<?php echo $ctx_var_model; ?>)) {
			$<?php echo $ctx_var_model; ?> = Cerb_ORMHelper::recastArrayToModel($<?php echo $ctx_var_model; ?>, 'Model_<?php echo $class_name; ?>');
		} else {
			$<?php echo $ctx_var_model; ?> = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = '<?php echo $ctx_ext_id; ?>';
		$token_values['_types'] = $token_types;
		
		if($<?php echo $ctx_var_model; ?>) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $<?php echo $ctx_var_model; ?>->name;
			$token_values['id'] = $<?php echo $ctx_var_model; ?>->id;
			$token_values['name'] = $<?php echo $ctx_var_model; ?>->name;
			$token_values['updated_at'] = $<?php echo $ctx_var_model; ?>->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($<?php echo $ctx_var_model; ?>, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=<?php echo $table_name; ?>&id=%d-%s",$<?php echo $ctx_var_model; ?>->id, DevblocksPlatform::strToPermalink($<?php echo $ctx_var_model; ?>->name)), true);
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = '<?php echo $ctx_ext_id; ?>';
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
		$view->name = '<?php echo $object_name ?>';
		/*
		$view->addParams(array(
			SearchFields_<?php echo $class_name; ?>::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_<?php echo $class_name; ?>::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_<?php echo $class_name; ?>::UPDATED_AT;
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
		$view->name = '<?php echo $object_name ?>';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		$context = '<?php echo $ctx_ext_id; ?>';
		
		if(!empty($context_id)) {
			$model = DAO_<?php echo $class_name; ?>::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(isset($model))
				$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:<?php echo $plugin_id; ?>::<?php echo $table_name; ?>/peek_edit.tpl');
			
		} else {
			// Counts
			$activity_counts = array(
				//'comments' => DAO_Comment::count($context, $context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($context, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}

			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$tpl->display('devblocks:<?php echo $plugin_id; ?>::<?php echo $table_name; ?>/peek.tpl');
		}
	}
	
	/*
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'name' => array(
				'label' => 'Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_<?php echo $class_name; ?>::NAME,
				'required' => true,
			),
			'updated_at' => array(
				'label' => 'Updated Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_<?php echo $class_name; ?>::UPDATED_AT,
			),
		);
	
		$fields = SearchFields_<?php echo $class_name; ?>::getFields();
		self::_getImportCustomFields($fields, $keys);
	
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have a name
			if(!isset($fields[DAO_<?php echo $class_name; ?>::NAME])) {
				$fields[DAO_<?php echo $class_name; ?>::NAME] = 'New ' . $this->manifest->name;
			}
	
			// Create
			$meta['object_id'] = DAO_<?php echo $class_name; ?>::create($fields);
	
		} else {
			// Update
			DAO_<?php echo $class_name; ?>::update($meta['object_id'], $fields);
		}
	
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
	*/
};
</textarea>

<b>templates/<?php echo $table_name; ?>/peek.tpl</b><br>
<textarea style="width:98%;height:200px;">
{$div_id = "peek{uniqid()}"}
{$peek_context = '<?php echo $ctx_ext_id; ?>'}
{$is_writeable = Context_<?php echo $class_name; ?>::isWriteableByActor($dict, $active_worker)}

<div id="{$div_id}">
	
	<div style="float:left;">
		<h1>
			{$dict->_label}
		</h1>
		
		<div style="margin-top:5px;">
			{if !empty($dict->id)}
				{$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($dict->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$dict->id full=true}
			{/if}
		
			{if $is_writeable}
			<button type="button" class="cerb-peek-edit" data-context="{$peek_context}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
			{/if}
			
			{if $dict->id}<button type="button" class="cerb-peek-profile"><span class="glyphicons glyphicons-nameplate"></span> {'common.profile'|devblocks_translate|capitalize}</button>{/if}
			<button type="button" class="cerb-peek-comments-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$peek_context} context.id:{$dict->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>
		</div>
	</div>
</div>

<div style="clear:both;padding-top:10px;"></div>

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<div class="cerb-properties-grid" data-column-width="100">
	
		{$labels = $dict->_labels}
		{$types = $dict->_types}
		{foreach from=$properties item=k name=props}
			{if $dict->$k}
			<div>
			{if $k == ''}
			{else}
				{include file="devblocks:cerberusweb.core::internal/peek/peek_property_grid_cell.tpl" dict=$dict k=$k labels=$labels types=$types}
			{/if}
			</div>
			{/if}
		{/foreach}
	</div>
	
	<div style="clear:both;"></div>
	
	{*
	<div style="margin-top:5px;">
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR_EVENT}" data-query="calendar.id:{$dict->id}"><div class="badge-count">{$activity_counts.events|default:0}</div> {'common.events'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING}" data-query="calendar.id:{$dict->id}"><div class="badge-count">{$activity_counts.events_recurring|default:0}</div> {'common.events.recurring'|devblocks_translate|capitalize}</button>
	</div>
	*}
	
</fieldset>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties_links=$links peek=true page_context=$peek_context page_context_id=$dict->id}

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$peek_context context_id=$dict->id view_id=$view_id}

{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_pager.tpl"}

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	var $timeline = {$timeline_json|default:'{}' nofilter};

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'<?php echo $table_name; ?>'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Properties grid
		$popup.find('div.cerb-properties-grid').cerbPropertyGrid();
		
		// Edit button
		{if $is_writeable}
		$popup.find('button.cerb-peek-edit')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
			.on('cerb-peek-saved', function(e) {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
			})
			.on('cerb-peek-deleted', function(e) {
				genericAjaxPopupClose($layer);
			})
			;
		{/if}
		
		// Comments
		$popup.find('button.cerb-peek-comments-add')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function() {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={$peek_context}&context_id={$dict->id}&view_id={$view_id}','reuse',false,'50%');
			})
			;
		
		// Peeks
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// Searches
		$popup.find('.cerb-search-trigger')
			.cerbSearchTrigger()
			;
		
		// Menus
		$popup.find('ul.cerb-menu').menu();
		
		// View profile
		$popup.find('.cerb-peek-profile').click(function(e) {
			if(e.shiftKey || e.metaKey) {
				window.open('{devblocks_url}c=profiles&type=<?php echo $table_name; ?>&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}', '_blank');
				
			} else {
				document.location='{devblocks_url}c=profiles&type=<?php echo $table_name; ?>&id={$dict->id}-{$dict->_label|devblocks_permalink}{/devblocks_url}';
			}
		});
		
		// Timeline
		{include file="devblocks:cerberusweb.core::internal/peek/card_timeline_script.tpl"}
	});
});
</script>
</textarea>

<b>templates/<?php echo $table_name; ?>/peek_edit.tpl</b><br>
<textarea style="width:98%;height:200px;">
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="<?php echo $table_name; ?>">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
			</td>
		</tr>
	</table>
	
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context='<?php echo $ctx_ext_id; ?>' context_id=$model->id}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	&lt;textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"&gt;&lt;/textarea&gt;
</fieldset>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this <?php echo mb_strtolower($object_name); ?>?
	</div>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'<?php echo $object_name; ?>'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		var $textarea = $(this).find('textarea[name=comment]');
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});
	});
});
</script>
</textarea>

<?php
	$field_prefix = strtolower ( substr ( $table_name, 0, 1 ) );
	?>
<b>templates/<?php echo $table_name; ?>/view.tpl</b><br>
<textarea style="width:98%;height:200px;">
{$view_context = '<?php echo $ctx_ext_id; ?>'}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="0"><span class="glyphicons glyphicons-circle-plus"></span></a>
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=viewShowQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{*<a href="javascript:;" title="{$translate->_('common.import')|capitalize}" onclick="genericAjaxPopup('import','c=internal&a=showImportPopup&context={$view_context}&view_id={$view->id}',null,false,'50%');"><span class="glyphicons glyphicons-file-import"></span></a>*}
			<a href="javascript:;" title="{$translate->_('common.export')|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>
			<a href="javascript:;" title="{$translate->_('common.copy')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="<?php echo $table_name; ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{if !$view->options.disable_watchers}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.watchers'|devblocks_translate|capitalize}">
			<span class="glyphicons glyphicons-eye-open" style="color:rgb(80,80,80);"></span>
		</th>
		{/if}

		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if $view->options.disable_sorting}no-sort{/if}">
			{if !$view->options.disable_sorting && !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="*_watchers" align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.<?php echo $field_prefix; ?>_id}
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column == "<?php echo $field_prefix; ?>_name"}
			<td>
				<input type="checkbox" name="row_id[]" value="{$result.<?php echo $field_prefix; ?>_id}" style="display:none;">
				<a href="{devblocks_url}c=profiles&type=<?php echo $table_name; ?>&id={$result.<?php echo $field_prefix; ?>_id}-{$result.<?php echo $field_prefix; ?>_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.<?php echo $field_prefix; ?>_name}</a>
				<button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.<?php echo $field_prefix; ?>_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
			{elseif in_array($column, ["<?php echo $field_prefix; ?>_updated_at"])}
				<td>
					{if !empty($result.$column)}
						<abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
					{/if}
				</td>
			{else}
				<td data-column="{$column}">{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

<div style="padding-top:5px;">
	<div style="float:right;">
		{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
		{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
		{math assign=nextPage equation="x+1" x=$view->renderPage}
		{math assign=prevPage equation="x-1" x=$view->renderPage}
		{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
		
		{* Sanity checks *}
		{if $toRow > $total}{assign var=toRow value=$total}{/if}
		{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
		
		{if $view->renderPage > 0}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&amp;lt;&amp;lt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&amp;lt;{$translate->_('common.previous_short')|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&amp;gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&amp;gt;&amp;gt;</a>
		{/if}
	</div>
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
		<button type="button" class="action-always-show action-explore" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.action.value='viewExplore';this.form.submit();"><span class="glyphicons glyphicons-play-button"></span> {'common.explore'|devblocks_translate|lower}</button>
		{*
		{if $active_worker->hasPriv('calls.actions.update_all')}<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=profiles&a=handleSectionAction&amp;section=<?php echo $table_name; ?>&action=showBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'50%');"><span class="glyphicons glyphicons-folder-closed"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		*}
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$(function() {
	var $frm = $('#viewForm{$view->id}');
	
	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		$view_actions = $('#{$view->id}_actions');
		
		hotkey_activated = true;
	
		switch(event.keypress_event.which) {
			{*
			case 98: // (b) bulk update
				$btn = $view_actions.find('button.action-bulkupdate');
			
				if(event.indirect) {
					$btn.select().focus();
					
				} else {
					$btn.click();
				}
				break;
			*}
			
			case 101: // (e) explore
				$btn = $view_actions.find('button.action-explore');
			
				if(event.indirect) {
					$btn.select().focus();
					
				} else {
					$btn.click();
				}
				break;
				
			default:
				hotkey_activated = false;
				break;
		}
	
		if(hotkey_activated)
			event.preventDefault();
	});
	{/if}
});
</script>
</textarea>

<h2>Profile</h2>

<b>plugin.xml</b><br>
<textarea style="width:98%;height:200px;">
		<!-- Profile Page Sections -->
		
		<extension point="cerberusweb.ui.page.section">
			<id><?php echo $plugin_namespace; ?>.page.profiles.<?php echo $table_name; ?></id>
			<name><?php echo $object_name; ?> Section</name>
			<class>
				<file>api/profile/<?php echo $table_name; ?>.php</file>
				<name>PageSection_Profiles<?php echo $class_name; ?></name>
			</class>
			<params>
				<param key="page_id" value="core.page.profiles" />
				<param key="uri" value="<?php echo $table_name; ?>" />
			</params>
		</extension>

</textarea>

<b>api/profile/<?php echo $table_name; ?>.php</b><br>
<textarea style="width:98%;height:200px;">
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
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

class PageSection_Profiles<?php echo $class_name; ?> extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // <?php echo $table_name; ?> 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($<?php echo $table_name; ?> = DAO_<?php echo $class_name; ?>::get($id))) {
			return;
		}
		$tpl->assign('<?php echo $table_name; ?>', $<?php echo $table_name; ?>);
		
		// Tab persistence
		
		$point = 'profiles.<?php echo $table_name; ?>.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = array();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $<?php echo $table_name; ?>->name,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $<?php echo $table_name; ?>->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds('<?php echo $ctx_ext_id; ?>', $<?php echo $table_name; ?>->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields('<?php echo $ctx_ext_id; ?>', $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets('<?php echo $ctx_ext_id; ?>', $<?php echo $table_name; ?>->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			'<?php echo $ctx_ext_id; ?>' => array(
				$<?php echo $table_name; ?>->id => 
					DAO_ContextLink::getContextLinkCounts(
						'<?php echo $ctx_ext_id; ?>',
						$<?php echo $table_name; ?>->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, '<?php echo $ctx_ext_id; ?>');
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:<?php echo $plugin_id; ?>::<?php echo $table_name; ?>/profile.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_<?php echo $class_name; ?>::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'], 'string', '');
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
				if(empty($id)) { // New
					$fields = array(
						DAO_<?php echo $class_name; ?>::UPDATED_AT => time(),
						DAO_<?php echo $class_name; ?>::NAME => $name,
					);
					$id = DAO_<?php echo $class_name; ?>::create($fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, '<?php echo $ctx_ext_id; ?>', $id);
					
				} else { // Edit
					$fields = array(
						DAO_<?php echo $class_name; ?>::UPDATED_AT => time(),
						DAO_<?php echo $class_name; ?>::NAME => $name,
					);
					DAO_<?php echo $class_name; ?>::update($id, $fields);
					
				}
	
				// If we're adding a comment
				if(!empty($comment)) {
					$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
					
					$fields = array(
						DAO_Comment::CREATED => time(),
						DAO_Comment::CONTEXT => '<?php echo $ctx_ext_id; ?>',
						DAO_Comment::CONTEXT_ID => $id,
						DAO_Comment::COMMENT => $comment,
						DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
					);
					$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
				}
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost('<?php echo $ctx_ext_id; ?>', $id, $field_ids);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
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
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=<?php echo $table_name; ?>', true),
					'toolbar_extension_id' => '<?php echo $ctx_ext_id; ?>.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=<?php echo $table_name; ?>&id=%d-%s", $row[SearchFields_<?php echo $class_name; ?>::ID], DevblocksPlatform::strToPermalink($row[SearchFields_<?php echo $class_name; ?>::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_<?php echo $class_name; ?>::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
</textarea>

<b>templates/<?php echo $table_name; ?>/profile.tpl</b><br>
<textarea style="width:98%;height:200px;">
{$page_context = '<?php echo $ctx_ext_id; ?>'}
{$page_context_id = $<?php echo $table_name; ?>->id}
{$is_writeable = Context_<?php echo $class_name; ?>::isWriteableByActor($<?php echo $table_name; ?>, $active_worker)}

<h1>{$<?php echo $table_name; ?>->name}</h1>

<div class="cerb-profile-toolbar">
	<form class="toolbar" action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<span id="spanInteractions">
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
		</span>
		
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
		</span>
		
		<!-- Edit -->
		{if $is_writeable}
		<button type="button" id="btnDisplay<?php echo $class_name; ?>Edit" title="{'common.edit'|devblocks_translate|capitalize} (E)" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
		{/if}
	</form>
	
	{if $pref_keyboard_shortcuts}
		<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		(<b>1-9</b>) change tab
		</small>
	{/if}
</div>

<fieldset class="properties">
	<legend>{'<?php echo $object_name; ?>'|devblocks_translate|capitalize}</legend>

	<div style="margin-left:15px;">
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == '...'}
				<b>{$translate->_('...')|capitalize}:</b>
				...
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	<br clear="all">
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="<?php echo $table_name; ?>Tabs">
	<ul>
		{$tabs = [activity,comments]}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.comments')|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div>
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('<?php echo $table_name; ?>Tabs');

	var tabs = $("#<?php echo $table_name; ?>Tabs").tabs(tabOptions);

	// Edit
	{if $is_writeable}
	$('#btnDisplay<?php echo $class_name; ?>Edit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			document.location.reload();
		})
		.on('cerb-peek-deleted', function(e) {
			document.location.href = '{devblocks_url}{/devblocks_url}';
			
		})
		.on('cerb-peek-closed', function(e) {
		})
	;
	{/if}

	// Interactions
	var $interaction_container = $('#spanInteractions');
	{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
});
</script>

{if $pref_keyboard_shortcuts}
<script type="text/javascript">
$(function() {
	$(document).keypress(function(event) {
		if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
			return;
		
		if($(event.target).is(':input'))
			return;
	
		hotkey_activated = true;
		
		switch(event.which) {
			case 49:  // (1) tab cycle
			case 50:  // (2) tab cycle
			case 51:  // (3) tab cycle
			case 52:  // (4) tab cycle
			case 53:  // (5) tab cycle
			case 54:  // (6) tab cycle
			case 55:  // (7) tab cycle
			case 56:  // (8) tab cycle
			case 57:  // (9) tab cycle
			case 58:  // (0) tab cycle
				try {
					idx = event.which-49;
					$tabs = $("#<?php echo $table_name; ?>Tabs").tabs();
					$tabs.tabs('option', 'active', idx);
				} catch(ex) { }
				break;
			case 101:  // (E) edit
				try {
					$('#btnDisplay<?php echo $class_name; ?>Edit').click();
				} catch(ex) { }
				break;
			case 109:  // (M) macros
				try {
					$('#btnDisplayMacros').click();
				} catch(ex) { }
				break;
			default:
				// We didn't find any obvious keys, try other codes
				hotkey_activated = false;
				break;
		}
		
		if(hotkey_activated)
			event.preventDefault();
	});
});
</script>
{/if}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}
</textarea>

<?php } ?>