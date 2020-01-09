<?php
/**
 * Devblocks DAO
 * @author Jeff Standen, Webgroup Media LLC <jeff@webgroupmedia.com>
 * @version 2018-09-18
 */

$plugin_id = 'example.plugin';
$plugin_namespace = 'example';

$tables = [];

$tables['Example_Object'] = "
id int(10) unsigned NOT NULL AUTO_INCREMENT,
name varchar(255) NOT NULL DEFAULT '',
updated_at int(10) unsigned NOT NULL DEFAULT '0',
";

foreach($tables as $table_name => $field_strs) {
	echo '<h1>', $table_name, '</h1>';
	
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
	$ctx_ext_id = sprintf ( "cerb.contexts.%s", strtolower ( str_replace ( '_', '.', $table_name ) ) );
	?>

<h2>DAO</h2>

<b>api/dao/<?php echo $table_name; ?>.php</b>
<br>
<textarea style="width: 98%; height: 200px;">
class DAO_<?php echo $class_name; ?> extends Cerb_ORMHelper {
<?php
	foreach ( array_keys($fields) as $field_name ) {
		printf ( "\tconst %s = '%s';\n", strtoupper ( $field_name ), $field_name );
	}
	?>
	
	private function __construct() {}
	
	// [TODO]
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
		
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		//	$fields[DAO_Automation::CREATED_AT] = time();
		$sql = "INSERT INTO <?php echo $table_name; ?> () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
			
		$context = '<?php echo $ctx_ext_id; ?>';
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, '<?php echo $table_name; ?>', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.<?php echo $table_name; ?>.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('<?php echo $table_name; ?>', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = '<?php echo $ctx_ext_id; ?>';
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

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
		//$cache = DevblocksPlatform::services()->cache();
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
		return parent::getIds($ids);
	}	
	
	/**
	 * @param resource $rs
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_<?php echo $class_name; ?>();
<?php
	foreach ( array_keys($fields) as $field_name ) {
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
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM <?php echo $table_name; ?> WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
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
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_<?php echo $class_name; ?>', $sortBy);
		
		$select_sql = sprintf("SELECT ".
<?php
	$num_fields = 0;
	foreach ( array_keys($fields) as $field_name ) {
		$num_fields ++;
		printf ( "\t\t\t\"%s.%s as %%s%s", $table_name, $field_name, (($num_fields == count ( $fields )) ? " \",\n" : ", \".\n") ) // ending
;
	}
	$num_fields = 0;
	foreach ( array_keys($fields) as $field_name ) {
		$num_fields ++;
		printf ( "\t\t\t\tSearchFields_%s::%s%s", $class_name, strtoupper ( $field_name ), ($num_fields == count ( $fields )) ? "\n" : ",\n" );
	}
	?>
			);
			
		$join_sql = "FROM <?php echo $table_name; ?> ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_<?php echo $class_name; ?>');
	
		return array(
			'primary_table' => '<?php echo $table_name; ?>',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	/**
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
		
		$results = [];
		
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
	foreach ( array_keys($fields) as $field_name ) {
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
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr('<?php echo $ctx_ext_id; ?>')), self::getPrimaryKey());
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
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_<?php echo $class_name; ?>::ID:
				$models = DAO_<?php echo $class_name; ?>::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
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
	foreach ( array_keys($fields) as $field_name ) {
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
	foreach ( array_keys($fields) as $field_name ) {
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
	foreach ( array_keys($fields) as $field_name ) {
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
		
		$fields = [];

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
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = '<?php echo $ctx_ext_id; ?>';

		if(!isset($fields[$column]))
			return [];
		
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
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		// [TODO] Implement quick search fields
		$search_fields = SearchFields_<?php echo $class_name; ?>::getFields();
	
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
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . '<?php echo $ctx_ext_id; ?>'],
					]
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
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK);
		
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
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
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
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('<?php echo $ctx_ext_id; ?>');
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:<?php echo $plugin_id; ?>::<?php echo $table_name; ?>/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
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
	foreach ( array_keys($fields) as $field_name ) {
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
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',[]);
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

<textarea style="width:98%;height:200px;">
class Context_<?php echo $class_name;?> extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
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
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=<?php echo $table_name; ?>&id='.$context_id, true);
		return $url;
	}
	
	// [TODO] Profile fields
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_<?php echo $class_name; ?>();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
  
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$<?php echo $ctx_var_model; ?> = DAO_<?php echo $class_name; ?>::get($context_id);
		
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
		$token_values = [];
		
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
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=<?php echo $table_name; ?>&id=%d-%s",$<?php echo $ctx_var_model; ?>->id, DevblocksPlatform::strToPermalink($<?php echo $ctx_var_model; ?>->name)), true);
		}
		
		return true;
	}
	
	// [TODO]
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_<?php echo $class_name ?>::ID,
			'links' => '_links',
			'name' => DAO_<?php echo $class_name ?>::NAME,
			'updated_at' => DAO_<?php echo $class_name ?>::UPDATED_AT,
		];
	}
	
	// [TODO]
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = '<?php echo $ctx_ext_id; ?>';
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
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
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = '<?php echo $object_name ?>';
		
		$params_req = [];
		
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
		$tpl = DevblocksPlatform::services()->template();
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
			Page_Profiles::renderCard($context, $context_id, $model);
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

<textarea style="width: 98%; height: 200px;">
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
                        <data key="comments" />
						<data key="custom_fields" />
						<data key="links" />
						<data key="records" />
						<data key="search" />
						<data key="snippets" />
						<data key="va_variable" />
						<data key="watchers" />
						<data key="workspace" />
					</value>
				</param>
				<param key="acl">
					<value>
						<data key="broadcast" />
						<data key="comment" />
						<data key="create" />
						<data key="delete" />
						<data key="export" />
						<data key="import" />
						<data key="merge" />
						<data key="update" />
						<data key="update.bulk" />
					</value>
				</param>
			</params>
		</extension>
</textarea>

<b>strings.xml</b>
<br>
<textarea style="width: 98%; height: 200px;">
<!-- <?php echo $class_name; ?> -->

<?php foreach(array_keys($fields) as $field_name) { ?>
<tu tuid='dao.<?php echo $table_name; ?>.<?php echo $field_name; ?>'>
	<tuv xml:lang="en_US">
	<seg><?php echo ucwords(str_replace('_',' ',$field_name)); ?></seg></tuv>
</tu>
<?php } ?>
</textarea>

<b>templates/<?php echo $table_name; ?>/peek_edit.tpl</b><br>
<textarea style="width:98%;height:200px;">
{$peek_context = '<?php echo $ctx_ext_id; ?>'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="<?php echo $table_name; ?>">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>

	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>


{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this <?php echo mb_strtolower($object_name); ?>?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
		<button type="button" class="create-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.create_and_continue'|devblocks_translate|capitalize}</button>
	{/if}
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
		
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.create-continue').click({ mode: 'create_continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
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

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->hasPriv("contexts.{$view_context}.create")}<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="0"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=viewShowQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.import")}<a href="javascript:;" title="{$translate->_('common.import')|capitalize}" onclick="genericAjaxPopup('import','c=internal&a=showImportPopup&context={$view_context}&view_id={$view->id}',null,false,'50%');"><span class="glyphicons glyphicons-file-import"></span></a>{/if}
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a href="javascript:;" title="{$translate->_('common.export')|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
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
			<td data-column="*_watchers" align="center" nowrap="nowrap" style="padding:5px;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.<?php echo $field_prefix; ?>_id}
			</td>
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

class PageSection_Profiles<?php echo $class_name; ?> extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // <?php echo $table_name; ?> 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = '<?php echo $ctx_ext_id; ?>';
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", '<?php echo $ctx_ext_id; ?>')))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_<?php echo $class_name; ?>::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = array(
						DAO_<?php echo $class_name; ?>::UPDATED_AT => time(),
						DAO_<?php echo $class_name; ?>::NAME => $name,
					);
					
					if(!DAO_<?php echo $class_name; ?>::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_<?php echo $class_name; ?>::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_<?php echo $class_name; ?>::create($fields);
					DAO_<?php echo $class_name; ?>::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, '<?php echo $ctx_ext_id; ?>', $id);
					
				} else { // Edit
					$fields = array(
						DAO_<?php echo $class_name; ?>::UPDATED_AT => time(),
						DAO_<?php echo $class_name; ?>::NAME => $name,
					);
					
					if(!DAO_<?php echo $class_name; ?>::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_<?php echo $class_name; ?>::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_<?php echo $class_name; ?>::update($id, $fields);
					DAO_<?php echo $class_name; ?>::onUpdateByActor($active_worker, $id, $fields);
					
				}
				
				if($id) {
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost('<?php echo $ctx_ext_id; ?>', $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode(array(
					'status' => true,
					'context' => '<?php echo $ctx_ext_id; ?>',
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
		$url_writer = DevblocksPlatform::services()->url();
		
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
			$models = [];
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

<?php } ?>