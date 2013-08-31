<?php
/**
 * Devblocks DAO
 * @author Jeff Standen, Webgroup Media LLC <jeff@webgroupmedia.com>
 * @version 2013-08-13
 */

$plugin_id = 'example.plugin';
$plugin_namespace = 'example';

$tables = array();

$tables['Example_Object'] = "
id INT UNSIGNED NOT NULL AUTO_INCREMENT,
name VARCHAR(255) DEFAULT '',
updated_at INT UNSIGNED NOT NULL DEFAULT 0,
";

foreach($tables as $table_name => $field_strs) {
	// Class
	$class_name = str_replace(' ','',ucwords(str_replace('_',' ',$table_name)));
	$object_name = ucwords(str_replace('_',' ',$table_name));

	$table_name = strtolower($table_name);
	
	// Fields
	$fields = array();

	$schema = trim($field_strs);
	$schema = str_replace(array("\r"), array("\n"), $schema);
	$schema = str_replace(array("\n\n"), array("\n"), $schema);
	
	foreach(explode("\n", $schema) as $field_str) {
		$field_props = explode(' ', rtrim($field_str, ",\n\r "));
		$fields[trim($field_props[0])] = trim($field_props[1]);
	}
	
	// Contexts
	
	$ctx_var_model = $table_name;
	$ctx_ext_id = sprintf("cerberusweb.contexts.%s",
		strtolower(str_replace('_','.',$table_name))
	);
?>

<h2>DAO</h2>

<b>api/dao/<?php echo $table_name; ?>.php</b><br>
<textarea style="width:98%;height:200px;">
class DAO_<?php echo $class_name; ?> extends Cerb_ORMHelper {
<?php
foreach($fields as $field_name => $field_type) {
	printf("\tconst %s = '%s';\n",
		strtoupper($field_name),
		$field_name
	);
}
?>

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO <?php echo $table_name; ?> () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Get state before changes
			$object_changes = parent::_getUpdateDeltas($batch_ids, $fields, get_class());

			// Make changes
			parent::_update($batch_ids, '<?php echo $table_name; ?>', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.<?php echo $table_name; ?>.update',
						array(
							'objects' => $object_changes,
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
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT <?php echo implode(', ', array_keys($fields)); ?> ".
			"FROM <?php echo $table_name; ?> ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_<?php echo $class_name,"\n"; ?>
	 */
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
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_<?php echo $class_name; ?>();
<?php
foreach($fields as $field_name => $field_type) {
	printf("\t\t\t\$object->%s = \$row['%s'];\n",
		$field_name,
		$field_name
	);
}
?>
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
		
		$db->Execute(sprintf("DELETE FROM <?php echo $table_name; ?> WHERE id IN (%s)", $ids_list));
		
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
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
<?php
$num_fields = 0;
foreach($fields as $field_name => $field_type) {
	$num_fields++;
	printf("\t\t\t\"%s.%s as %%s%s",
		$table_name,
		$field_name,
		(($num_fields==count($fields)) ? " \",\n" : ", \".\n") // ending
	);
}
$num_fields = 0;
foreach($fields as $field_name => $field_type) {
	$num_fields++;
	printf("\t\t\t\tSearchFields_%s::%s%s",
		$class_name,
		strtoupper($field_name),
		($num_fields==count($fields)) ? "\n" : ",\n"
	);
}
?>
			);
			
		$join_sql = "FROM <?php echo $table_name; ?> ".
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = '<?php echo $ctx_ext_id; ?>' AND context_link.to_context_id = <?php echo $table_name; ?>.id) " : " ").
			'';
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'<?php echo $table_name; ?>.id',
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
			array('DAO_<?php echo $class_name; ?>', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => '<?php echo $table_name; ?>',
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
			
		$from_context = '<?php echo $ctx_ext_id; ?>';
		$from_index = '<?php echo $table_name; ?>.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
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
			($has_multiple_values ? 'GROUP BY <?php echo $table_name; ?>.id ' : '').
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
			$object_id = intval($row[SearchFields_<?php echo $class_name; ?>::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT <?php echo $table_name; ?>.id) " : "SELECT COUNT(<?php echo $table_name; ?>.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};
</textarea>

<textarea style="width:98%;height:200px;">
class SearchFields_<?php echo $class_name; ?> implements IDevblocksSearchFields {
<?php
foreach($fields as $field_name => $field_type) {
	printf("\tconst %s = '%s_%s';\n",
		strtoupper($field_name),
		substr($table_name,0,1),
		$field_name
	);
}
?>

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
<?php
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tself::%s => new DevblocksSearchField(self::%s, '%s', '%s', \$translate->_('dao.%s.%s')),\n",
		strtoupper($field_name),
		strtoupper($field_name),
		$table_name,
		$field_name,
		$table_name,
		$field_name
	);
}
?>

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			'<?php echo $ctx_ext_id; ?>',
		));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};
</textarea>

<textarea style="width:98%;height:200px;">
class Model_<?php echo $class_name; ?> {
<?php
foreach($fields as $field_name => $field_type) {
	printf("\tpublic \$%s;\n",
		$field_name
	);
};
?>
};
</textarea>

<textarea style="width:98%;height:200px;">
class View_<?php echo $class_name; ?> extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = '<?php echo strtolower($class_name); ?>';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = $translate->_('<?php echo $class_name; ?>');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_<?php echo $class_name; ?>::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
<?php
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tSearchFields_%s::%s,\n",
		$class_name,
		strtoupper($field_name)
	);
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
//			case SearchFields_<?php echo $class_name; ?>::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_<?php echo $class_name; ?>', $column);
//				break;

//			case SearchFields_<?php echo $class_name; ?>::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn('DAO_<?php echo $class_name; ?>', $column);
//				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_<?php echo $class_name; ?>', '<?php echo $ctx_ext_id; ?>', $column);
				break;

			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_<?php echo $class_name; ?>', '<?php echo $ctx_ext_id; ?>', $column);
				break;
				
			case SearchFields_<?php echo $class_name; ?>::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_<?php echo $class_name; ?>', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_<?php echo $class_name; ?>', $column, '<?php echo $table_name; ?>.id');
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
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tcase SearchFields_%s::%s:\n",
		$class_name,
		strtoupper($field_name)
	);
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
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tcase SearchFields_%s::%s:\n",
		$class_name,
		strtoupper($field_name)
	);
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
					//$change_fields[DAO_<?php echo $class_name; ?>::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_<?php echo $class_name; ?>::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_<?php echo $class_name; ?>::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_<?php echo $class_name; ?>::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields('<?php echo $ctx_ext_id; ?>', $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};
</textarea>

<b>plugin.xml</b><br>
<textarea style="width:98%;height:200px;">
<file path="api/dao/<?php echo $table_name; ?>.php">
	<class name="Context_<?php echo $class_name; ?>" />
	<class name="DAO_<?php echo $class_name; ?>" />
	<class name="Model_<?php echo $class_name; ?>" />
	<class name="SearchFields_<?php echo $class_name; ?>" />
	<class name="View_<?php echo $class_name; ?>" />
</file>
</textarea>

<b>strings.xml</b><br>
<textarea style="width:98%;height:200px;">
<!-- <?php echo $class_name; ?> -->

<?php foreach($fields as $field_name => $field_type) { ?>
<tu tuid='dao.<?php echo $table_name; ?>.<?php echo $field_name; ?>'>
	<tuv xml:lang="en_US"><seg><?php echo ucwords(str_replace('_',' ',$field_name)); ?></seg></tuv>
</tu>
<?php } ?>
</textarea>

<h2>Context</h2>

<b>plugin.xml</b><br>
<textarea style="width:98%;height:200px;">
		<!-- Contexts -->
		
		<extension point="devblocks.context">
			<id><?php echo $ctx_ext_id; ?></id>
			<name><?php echo $object_name; ?></name>
			<class>
				<file>api/dao/<?php echo $table_name; ?>.php</file>
				<name>Context_<?php echo $class_name; ?></name>
			</class>
			<params>
				<param key="alias" value="<?php echo $table_name; ?>" />
				<param key="dao_class" value="DAO_<?php echo $class_name; ?>" />
				<param key="view_class" value="View_<?php echo $class_name;?>" />
				<param key="options">
					<value>
						<data key="create" />
						<data key="custom_fields" />
						<data key="find" />
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
	function getRandom() {
		//return DAO_<?php echo $class_name; ?>::random();
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
		);
	}
	
	// [TODO] Interface
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
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = '<?php echo $object_name ?>';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_<?php echo $class_name; ?>::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_<?php echo $class_name; ?>::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($<?php echo $ctx_var_model; ?> = DAO_<?php echo $class_name; ?>::get($context_id))) {
			$tpl->assign('model', $<?php echo $ctx_var_model; ?>);
		}
		
		$custom_fields = DAO_CustomField::getByContext('<?php echo $ctx_ext_id; ?>', false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds('<?php echo $ctx_ext_id; ?>', $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}

		// Comments
		$comments = DAO_Comment::getByContext('<?php echo $ctx_ext_id; ?>', $context_id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		$tpl->display('devblocks:<?php echo $plugin_id; ?>::<?php echo $table_name; ?>/peek.tpl');
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
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frm<?php echo $class_name; ?>Peek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="<?php echo $table_name; ?>">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;">
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks('<?php echo $ctx_ext_id; ?>', array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='<?php echo $ctx_ext_id; ?>' context_id=$model->id full=true}
				{/if}
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

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	&lt;textarea name="comment" rows="5" cols="45" style="width:98%;"&gt;&lt;/textarea&gt;
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
	</div>
</fieldset>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this <?php echo strtolower($object_name); ?>?
	</div>
	
	<button type="button" class="delete" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"><span class="cerb-sprite2 sprite-tick-circle"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frm<?php echo $class_name; ?>Peek','{$view_id}', false, '<?php echo $table_name; ?>_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=<?php echo $table_name; ?>&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'<?php echo $object_name; ?>'}");
		
		$(this).find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
		
		$('#frm<?php echo $class_name; ?>Peek button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$(this).find('input:text:first').focus();
	} );
</script>
</textarea>

<?php
$field_prefix = strtolower(substr($table_name,0,1));
?>
<b>templates/<?php echo $table_name; ?>/view.tpl</b><br>
<textarea style="width:98%;height:200px;">
{$view_context = '<?php echo $ctx_ext_id; ?>'}
{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$view_context}&context_id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></a>
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=viewShowQuickSearchPopup&view_id={$view->id}',this,false,'400');"><span class="cerb-sprite2 sprite-document-search-result"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="cerb-sprite2 sprite-gear"></span></a>
			<a href="javascript:;" title="Subtotals" class="subtotals minimal"><span class="cerb-sprite2 sprite-application-sidebar-list"></span></a>
			{*<a href="javascript:;" title="{$translate->_('common.import')|capitalize}" onclick="genericAjaxPopup('import','c=internal&a=showImportPopup&context={$view_context}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-application-import"></span></a>*}
			<a href="javascript:;" title="{$translate->_('common.export')|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-application-export"></span></a>
			<a href="javascript:;" title="{$translate->_('common.copy')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-applications"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite2 sprite-arrow-circle-135-left"></span></a>
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
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="text-align:center;width:75px;">
			<a href="javascript:;">{'common.watchers'|devblocks_translate|capitalize}</a>
		</th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="cerb-sprite sprite-sort_ascending"></span>
				{else}
					<span class="cerb-sprite sprite-sort_descending"></span>
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				<input type="checkbox" name="row_id[]" value="{$result.<?php echo $field_prefix; ?>_id}" style="display:none;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.<?php echo $field_prefix; ?>_id}
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column == "<?php echo $field_prefix; ?>_name"}
			<td>
				<a href="{devblocks_url}c=profiles&type=<?php echo $table_name; ?>&id={$result.<?php echo $field_prefix; ?>_id}-{$result.<?php echo $field_prefix; ?>_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.<?php echo $field_prefix; ?>_name}</a>
				<button type="button" class="peek" style="visibility:hidden;padding:1px;margin:0px 5px;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$view_context}&context_id={$result.<?php echo $field_prefix; ?>_id}&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite2 sprite-document-search-result" style="margin-left:2px" title="{$translate->_('views.peek')}"></span></button>
			</td>
			{elseif $column == "<?php echo $field_prefix; ?>_updated_at"}
				<td title="{$result.$column|devblocks_date}">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&amp;nbsp;
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
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
		<button type="button" class="action-always-show action-explore" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.action.value='viewExplore';this.form.submit();"><span class="cerb-sprite sprite-media_play_green"></span> {'common.explore'|devblocks_translate|lower}</button>
		{*
		{if $active_worker->hasPriv('calls.actions.update_all')}<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=profiles&a=handleSectionAction&section=<?php echo $table_name; ?>&action=showBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite2 sprite-folder-gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		*}
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$frm = $('#viewForm{$view->id}');

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

class PageSection_Profiles<?php echo $class_name; ?> extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // <?php echo $table_name, "\n"; ?>
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
			'label' => ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $<?php echo $table_name; ?>->name,
		);
			
		$properties['updated'] = array(
			'label' => ucfirst($translate->_('common.updated')),
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
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.<?php echo $table_name; ?>'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, '<?php echo $ctx_ext_id; ?>');
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:<?php echo $plugin_id; ?>::<?php echo $table_name; ?>/profile.tpl');
	}
	
	function savePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_<?php echo $class_name; ?>::delete($id);
			
		} else {
			if(empty($id)) { // New
				$fields = array(
					DAO_<?php echo $class_name; ?>::UPDATED_AT => time(),
					DAO_<?php echo $class_name; ?>::NAME => $name,
				);
				$id = DAO_<?php echo $class_name; ?>::create($fields);
				
				// Watchers
				@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
				if(!empty($add_watcher_ids))
					CerberusContexts::addWatchers('<?php echo $ctx_ext_id; ?>', $id, $add_watcher_ids);
				
				// Context Link (if given)
				@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
				@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
				if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
					DAO_ContextLink::setLink('<?php echo $ctx_ext_id; ?>', $id, $link_context, $link_context_id);
				}
				
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
				@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
				
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

<div style="float:left">
	<h1>{$<?php echo $table_name; ?>->name}</h1>
</div>

<div style="float:right;">
{$ctx = Extension_DevblocksContext::get($page_context)}
{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}" reset=true}
</div>

<div style="clear:both;"></div>

<div class="cerb-profile-toolbar">
	<form class="toolbar" action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">
		<!-- Toolbar -->
		
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
		</span>
		
		<!-- Macros -->
		{devblocks_url assign=return_url full=true}c=profiles&type=<?php echo $table_name; ?>&id={$page_context_id}-{$<?php echo $table_name; ?>->name|devblocks_permalink}{/devblocks_url}
		{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
		
		<!-- Edit -->
		<button type="button" id="btnDisplay<?php echo $class_name; ?>Edit" title="{'common.edit'|devblocks_translate|capitalize}">&amp;nbsp;<span class="cerb-sprite2 sprite-gear"></span>&amp;nbsp;</button>
	</form>
	
	{if $pref_keyboard_shortcuts}
		<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
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

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="<?php echo $table_name; ?>Tabs">
	<ul>
		{$tabs = [activity,comments,links]}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.comments')|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.links')} <div class="tab-badge">{DAO_ContextLink::count($page_context, $page_context_id)|default:0}</div></a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div>
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#<?php echo $table_name; ?>Tabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDisplay<?php echo $class_name; ?>Edit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$page_context}&context_id={$page_context_id}',null,false,'550');
			$popup.one('<?php echo $table_name; ?>_save', function(event) {
				event.stopPropagation();
				document.location.reload();
			});
		});

		{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl" selector_button=null selector_menu=null}
	});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
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
				$tabs.tabs('select', idx);
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
{/if}
</script>

{$profile_scripts = Extension_ContextProfileScript::getExtensions(true, $page_context)}
{if !empty($profile_scripts)}
{foreach from=$profile_scripts item=renderer}
	{if method_exists($renderer,'renderScript')}
		{$renderer->renderScript($page_context, $page_context_id)}
	{/if}
{/foreach}
{/if}
</textarea>

<?php } ?>