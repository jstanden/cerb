<?php
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

abstract class C4_AbstractView {
	public $id = null;
	public $is_ephemeral = 0;
	public $name = '';
	public $options = [];
	
	public $view_columns = [];
	private $_columnsHidden = [];
	
	private $_paramsQuery = null;
	private $_paramsEditable = [];
	private $_paramsDefault = [];
	private $_paramsRequired = [];
	private $_paramsRequiredQuery = null;
	private $_paramsTimezone = null;
	
	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderTotal = true;
	public $renderSortBy = '';
	public $renderSortAsc = 1;

	public $renderSubtotals = null;
	
	public $renderTemplate = null;

	function getContext() {
		if(false == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($this))))
			return null;
		
		return $context_ext->id;
	}
	abstract function getData();
	function getDataAsObjects($ids=null) { return []; }
	
	function getPaging(array $results, int $total) {
		$paging = [
			'page' => [
				'of' => intval(ceil($total / $this->renderLimit)),
				'rows' => [
					'of' => intval($total),
					'count' => count($results),
					'limit' => intval($this->renderLimit),
				],
			]
		];
		
		$paging['page']['index'] = DevblocksPlatform::intClamp($this->renderPage, 0, PHP_INT_MAX);
		
		$paging['page']['rows']['from'] = $paging['page']['index'] * $paging['page']['rows']['limit'] + 1;
		$paging['page']['rows']['to'] = min($paging['page']['rows']['from']+$paging['page']['rows']['limit'] - 1, $paging['page']['rows']['of']);
		
		if($paging['page']['rows']['from'] > $paging['page']['rows']['of']) {
			$paging['page']['rows']['from'] = 0;
			$paging['page']['rows']['to'] = 0;
		}
		
		if($paging['page']['index'] - 1 >= 0) {
			$paging['page']['prev'] = $paging['page']['index'] - 1;
			$paging['page']['first'] = 0;
		}
		
		if($paging['page']['index'] + 1 < $paging['page']['of']) {
			$paging['page']['next'] = $paging['page']['index'] + 1;
			$paging['page']['last'] = $paging['page']['of']-1;
		}
		
		return $paging;
	}
	
	/**
	 * @param integer $size
	 * @return array
	 */
	function getDataSample($size) { return []; }
	
	// Adjust the last page if we hit the list bounds
	protected function _getDataBoundedTimed() {
		if(!method_exists($this, '_getData'))
			return [];
		
		$db = DevblocksPlatform::services()->database();
		$platform_timezone = DevblocksPlatform::getTimezone();
		
		try {
			// Override the platform timezone
			if($query_timezone = $this->getParamsTimezone()) {
				DevblocksPlatform::setTimezone($query_timezone);
				$db->SetReaderTimezone($query_timezone);
			}
			
			$objects = $this->_getData();
			
			if(false === $objects) {
				$error = "The query failed.";
				C4_AbstractView::marqueeAppend($this->id, $error);
				return [[], -1];
			}

			// If we have no results, it's not the first page, and we're returning totals
			if(!$objects[0] && $this->renderPage && $this->renderTotal) {
				$total = $objects[1];
				$this->renderPage = max(floor($total/$this->renderLimit)-1, 0);
				$objects = $this->_getData();
			}
			
			return $objects;
			
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$error = "The query timed out.";
			C4_AbstractView::marqueeAppend($this->id, $error);
			return [[], -1];
			
		} finally {
			// Reset the platform timezone
			if($query_timezone) {
				DevblocksPlatform::setTimezone($platform_timezone);
				$db->ResetReaderTimezone();
			}
		}
	}
	
	private $_placeholderLabels = [];
	private $_placeholderValues = [];
	
	public function __destruct() {
		if(isset($this->__auto_persist) && !$this->__auto_persist)
			return;
		
		if(empty($this->id))
			return;
		
		$this->persist();
	}
	
	public function persist($force=false) {
		if($force) {
			$this->_init_checksum = uniqid();
		}
		
		C4_AbstractViewLoader::setView($this->id, $this);
	}
	
	public function getAutoPersist() {
		if(isset($this->__auto_persist))
			return $this->__auto_persist ?: false;
		
		return true;
	}
	
	public function setAutoPersist($auto_persist) {
		if($auto_persist) {
			unset($this->__auto_persist);
		} else {
			$this->__auto_persist = false;
		}
	}
	
	protected function _getDataAsObjects($dao_class, $ids=null, &$total=null) {
		if(is_null($ids)) {
			if(!method_exists($dao_class,'search'))
				return [];
			
			$data = call_user_func_array(
				array($dao_class, 'search'),
				array(
					$this->view_columns,
					$this->getParams(),
					$this->renderLimit,
					$this->renderPage,
					$this->renderSortBy,
					$this->renderSortAsc,
					true
				)
			);
			
			list($results, $total) = $data;
			
			$ids = array_keys($results);
			
		} else {
			$total = count($ids);
		}
		
		if(!is_array($ids) || empty($ids))
			return [];

		$sql = sprintf("id IN (%s)",
			implode(',', $ids)
		);

		if(!method_exists($dao_class, 'getWhere'))
			return [];
		
		$results = [];
		
		$models = call_user_func_array(
			array($dao_class, 'getWhere'),
			array(
				$sql,
				null,
			)
		);
		
		foreach($ids as $id) {
			if(!isset($models[$id]))
				continue;
			
			$results[$id] = $models[$id];
		}
		
		unset($models);
		
		return $results;
	}
	
	protected function _doGetDataSample($dao_class, $size, $id_col = 'id') {
		$db = DevblocksPlatform::services()->database();

		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$this->view_columns,
				$this->getParams(),
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$select_sql = sprintf("SELECT %s.%s ",
			$query_parts['primary_table'],
			$id_col
		);
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = sprintf("ORDER BY RAND() LIMIT %d ", $size);
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
		
		$rs = $db->QueryReaderAsync($sql, 10000);
		
		$objects = [];
		
		if($rs instanceof mysqli_result) {
			while($row = mysqli_fetch_row($rs)) {
				$objects[] = $row[0];
			}
		}
		
		return $objects;
	}
	
	protected function _lazyLoadCustomFieldsIntoObjects(&$objects, $search_class) {
		if(!$search_class || !class_exists($search_class) || !class_implements('DevblocksSearchFields'))
			return false;
		
		if(!is_array($objects) || !isset($objects[0]) || !is_array($objects[0]))
			return false;
		
		$fields = $search_class::getFields();
		$custom_fields = DAO_CustomField::getAll();
		
		$cfield_columns = array_values(array_filter($this->view_columns, function($field_key) {
			return 'cf_' == substr($field_key, 0, 3);
		}));
		
		$cfield_contexts = [];
		
		// Remove any cfields that we're sorting on (we already have their values in SELECT)
		$sort_columns = is_array($this->renderSortBy) ? $this->renderSortBy : array($this->renderSortBy);
		$cfield_columns = array_diff($cfield_columns, $sort_columns);
		
		foreach($cfield_columns as $cfield_key) {
			$cfield_id = intval(substr($cfield_key, 3));
			
			if(!$cfield_id || false == (@$cfield = $custom_fields[$cfield_id]))
				continue;
			
			if(false == ($field_key = $search_class::getCustomFieldContextFieldKey($cfield->context))
				|| !isset($fields[$field_key]))
					continue;
				
			if(!isset($cfield_contexts[$cfield->context]))
				$cfield_contexts[$cfield->context] = [];
				
			$cfield_contexts[$cfield->context][$cfield_key] = array('context' => $cfield->context, 'on_key' => $field_key);
		}
		
		foreach($cfield_contexts as $cfield_context => $cfields) {
			foreach($cfields as $cfield_key => $cfield_data) {
				$on_key = $cfield_data['on_key'];
				
				if(empty($on_key))
					continue;
				
				$ids = DevblocksPlatform::extractArrayValues($objects, $on_key);
				$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($cfield_context, $ids);
				
				array_walk($objects[0], function(&$object) use ($on_key, $custom_field_values) {
					foreach($custom_field_values as $join_id => $values) {
						if(!isset($object[$on_key]) || $object[$on_key] != $join_id)
							continue;
						
						foreach($values as $k => $v) {
							if(is_array($v))
								$v = implode(', ', $v);
							
							$object['cf_' . $k] = $v;
						}
					}
				});
			}
		}
	}
	
	function isCustom() {
		if(DevblocksPlatform::strStartsWith($this->id, 'cust_'))
			return true;
		
		if(DevblocksPlatform::strStartsWith($this->id, 'profile_widget_'))
			return true;
			
		return false;
	}
	
	function getCustomWorklistModel() {
		if(!$this->isCustom())
			return false;
		
		$id = substr($this->id, 5);
		return DAO_WorkspaceList::get($id);
	}

	function getColumnsAvailable() {
		$columns = $this->getFields();
		
		foreach($this->getColumnsHidden() as $col)
			unset($columns[$col]);
			
		return $columns;
	}
	
	// Columns Hidden

	function getColumnsHidden() {
		$columnsHidden = $this->_columnsHidden;
		
		if(!is_array($columnsHidden))
			$columnsHidden = [];
			
		return $columnsHidden;
	}
	
	function addColumnsHidden($columnsToHide, $replace=false) {
		if($replace)
			$this->_columnsHidden = $columnsToHide;
		else
			$this->_columnsHidden = array_unique(array_merge($this->getColumnsHidden(), $columnsToHide));
	}
	
	// Params Editable
	
	function getParamsAvailable($filter_fieldsets=false) {
		$params = $this->getFields();
		
		// Hide other custom fields when filtering to a specific fieldset
		if($filter_fieldsets)
			$params = $this->_filterParamsByFieldset($params);
		
		return $params;
	}

	private function _filterParamsByFieldset($params) {
		$results = $this->findParam('*_has_fieldset', $this->getParams(false));
		
		if(!empty($results)) {
			$fieldset_ids = [];
			
			foreach($results as $result) { /* @var $result DevblocksSearchField */
				if($result->operator == DevblocksSearchCriteria::OPER_IN) {
					$fieldset_ids = array_merge($fieldset_ids, $result->value);
				}
			}
			
			if(!empty($fieldset_ids)) {
				foreach(array_keys($params) as $k) {
					if('cf_' == substr($k,0,3)) {
						list(, $field_id) = explode('_', $k, 2);
						$cfield = DAO_CustomField::get($field_id);
						
						if(empty($cfield->custom_fieldset_id))
							continue;
						
						if(!in_array($cfield->custom_fieldset_id, $fieldset_ids))
							unset($params[$k]);
					}
				}
			}
		}
		
		return $params;
	}
	
	function getParams($parse_placeholders=true) {
		$params = DevblocksPlatform::deepCloneArray($this->_paramsEditable);
		
		$error = null;
		
		// Required should supersede editable
		
		if(is_array($this->_paramsRequired)) {
			$params_required = DevblocksPlatform::deepCloneArray($this->_paramsRequired);
			
			foreach($params_required as $key => $param) {
				$params['req_'.$key] = $param;
			}
		}
		
		// Required quick search
		
		if($this->_paramsRequiredQuery) {
			if(false != ($params_required = $this->getParamsFromQuickSearch($this->_paramsRequiredQuery, [], $error))) {
				foreach($params_required as $key => $param) {
					$params['req_'.$key] = $param;
				}
			} else {
				self::marqueeFlush($this->id);
				self::marqueeAppend($this->id, '[Warning] Required query: ' . $error);
			}
		}
		
		if($parse_placeholders) {
			// Translate snippets in filters
			array_walk_recursive(
				$params,
				['C4_AbstractView', '_translatePlaceholders'],
				[
					'placeholder_values' => $this->getPlaceholderValues(),
				]
			);
		}
		
		return $params;
	}
	
	function getEditableParams() {
		return $this->_paramsEditable;
	}
	
	function getParamsQuery() {
		return $this->_paramsQuery;
	}
	
	function setParamsQuery($query) {
		$this->_paramsQuery = $query;
	}
	
	function addParam($param, $key=null) {
		if(!$key || is_numeric($key))
			$key = substr(sha1(json_encode($param)), 0, 16);
		
		$this->_paramsEditable[$key] = $param;
	}
	
	function addParams($params, $replace=false) {
		if($replace)
			$this->removeAllParams();
		
		if(is_array($params))
		foreach($params as $key => $param) {
			$key = is_numeric($key) ? null : $key;
			$this->addParam($param, $key);
		}
	}
	
	function getParamsFromQuickSearch(?string $query, array $bindings=[], &$error=null) {
		if(!($this instanceof IAbstractView_QuickSearch)) {
			$error = "This record type doesn't support search queries.";
			return false;
		}
		
		// Replace placeholders

		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$dict = new DevblocksDictionaryDelegate($this->getPlaceholderValues());
		$query = $tpl_builder->build($query, $dict);
		
		// Get fields
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($query, $bindings);
		
		$params_timezone = null;
		
		// Quick search multi-sorting
		
		foreach($fields as $k => $p) {
			if($p instanceof DevblocksSearchCriteria) {
				switch($p->key) {
					case '_limit':
					case 'limit':
						$oper = null;
						$value = null;
						
						if(false == (CerbQuickSearchLexer::getOperStringFromTokens($p->tokens, $oper, $value)))
							break;
						
						$limit = DevblocksPlatform::intClamp($value, 1, 250);
						$this->renderLimit = $limit;
						
						unset($fields[$k]);
						break;
						
					case '_page':
					case 'page':
						$oper = null;
						$value = null;
						
						if(false == (CerbQuickSearchLexer::getOperStringFromTokens($p->tokens, $oper, $value)))
							break;
						
						// Convert from 1-based to 0-based
						$page = DevblocksPlatform::intClamp($value - 1, 0, 2000);
						$this->renderPage = $page;
						
						unset($fields[$k]);
						break;
						
					case '_sort':
					case 'sort':
						$oper = null;
						$value = null;
						
						if(false == (CerbQuickSearchLexer::getOperStringFromTokens($p->tokens, $oper, $value)))
							break;
						
						if(false == ($sort_results = $this->_getSortFromQuickSearchQuery($value)))
							break;
						
						if(isset($sort_results['sort_by']) && !empty($sort_results['sort_by']))
							$this->renderSortBy = $sort_results['sort_by'];
						
						if(isset($sort_results['sort_asc']) && !empty($sort_results['sort_asc']))
							$this->renderSortAsc = $sort_results['sort_asc'];
						
						unset($fields[$k]);
						break;
						
					case '_subtotal':
					case 'subtotal':
						$oper = null;
						$value = null;
						
						if(false == (CerbQuickSearchLexer::getOperStringFromTokens($p->tokens, $oper, $value)))
							break;
						
						if(false == ($subtotal_results = $this->_getSubtotalFromQuickSearchQuery($value))) {
							$this->renderSubtotals = '';
						} else {
							$this->renderSubtotals = $subtotal_results[0];
						}
						
						unset($fields[$k]);
						break;
						
					case 'set.timezone':
						$oper = null;
						$value = null;
						
						if(false == (CerbQuickSearchLexer::getOperStringFromTokens($p->tokens, $oper, $value)))
							break;
						
						// Fail on invalid timezones
						if(!DevblocksPlatform::services()->date()->isValidTimezoneLocation($value)) {
							$error = sprintf("`%s` is an invalid timezone.", $value);
							return false;
						}
						
						$params_timezone = $value;
						
						unset($fields[$k]);
						break;
				}
			}
		}
		
		if($params_timezone) {
			$this->setParamsTimezone($params_timezone);
		} else {
			$this->setParamsTimezone(null);
		}
		
		// Convert fields T_FIELD to DevblocksSearchCriteria
		
		$error = null;
		
		array_walk_recursive($fields, function(&$v, $k) use (&$fields, &$error) {
			if($error)
				return;
			
			if($v instanceof DevblocksSearchCriteria) {
				$param = $this->getParamFromQuickSearchFieldTokens($v->key, $v->tokens);
				
				if($param instanceof DevblocksSearchCriteria) {
					$v = $param;
				} else {
					$error = sprintf('Unknown filter `%s:`', $v->key);
				}
			}
		});
		
		if($error)
			return false;
		
		return $fields;
	}
	
	function addParamsWithQuickSearch(?string $query, bool $replace=true, array $bindings=[], &$error=null) : bool {
		if(false === ($fields = $this->getParamsFromQuickSearch($query, $bindings, $error))) {
			$this->addParams([false], true);
			C4_AbstractView::marqueeAppend($this->id, $error);
			return false;
		}
		
		$this->addParams($fields, $replace);
		return true;
	}
	
	function addParamsRequiredWithQuickSearch(?string $query, bool $replace=true, array $bindings=[], &$error=null) : bool {
		if(false === ($fields = $this->getParamsFromQuickSearch($query, $bindings, $error))) {
			$this->addParamsRequired([false], true);
			C4_AbstractView::marqueeAppend($this->id, $error);
			return false;
		}
		
		$this->addParamsRequired($fields, $replace);
		return true;
	}
	
	function getSorts() {
		$render_sort = null;
		
		if(is_array($this->renderSortBy) && is_array($this->renderSortAsc) && count($this->renderSortBy) == count($this->renderSortAsc)) {
			$render_sort = array_combine($this->renderSortBy, $this->renderSortAsc);
		} else if(!is_array($this->renderSortBy) && !is_array($this->renderSortAsc)) {
			$render_sort = [$this->renderSortBy => ($this->renderSortAsc ? true : false) ];
		}
		
		return $render_sort;
	}
	
	function _getSortFromQuickSearchQuery($sort_query) {
		$sort_results = [
			'sort_by' => [],
			'sort_asc' => [],
		];
		
		if(empty($sort_query) || !($this instanceof IAbstractView_QuickSearch))
			return false;
		
		if(false == ($search_fields = $this->getQuickSearchFields()))
			return false;
		
		// Tokenize the sort string with commas
		$sort_fields = explode(',', $sort_query);
		
		if(!is_array($sort_fields) || empty($sort_fields))
			return false;
			
		foreach($sort_fields as $sort_field) {
			$sort_asc = true;
			
			if('-' == substr($sort_field,0,1))
				$sort_asc = false;
			
			$sort_field = ltrim($sort_field, '+-');
			
			@$search_field = $search_fields[$sort_field];
			
			if(!is_array($search_field) || empty($search_field))
				continue;
			
			$param_key = $search_field['options']['param_key'] ?? null;
			
			if(empty($param_key))
				continue;
			
			$sort_results['sort_by'][] = $param_key;
			$sort_results['sort_asc'][] = $sort_asc;
		}
		
		return $sort_results;
	}
	
	function _getSubtotalFromQuickSearchQuery($subtotal_query) {
		$subtotal_results = [];
		
		if(
			empty($subtotal_query) 
			|| (!$this instanceof IAbstractView_Subtotals)
			|| (!$this instanceof IAbstractView_QuickSearch)
			)
			return false;
		
		if(false == ($subtotal_fields = $this->getSubtotalFields()))
			return false;
		
		if(false == ($search_fields = $this->getQuickSearchFields()))
			return false;
		
		if(0 == strcasecmp($subtotal_query, 'null'))
			return [];
		
		// Tokenize the sort string with commas
		$subtotal_keys = explode(',', $subtotal_query);
		
		if(!is_array($subtotal_keys) || empty($subtotal_keys))
			return [];
		
		foreach($subtotal_keys as $subtotal_key) {
			@$search_field = $search_fields[$subtotal_key];
			
			if(!is_array($search_field) || empty($search_field))
				continue;
			
			$param_key = $search_field['options']['param_key'] ?? null;
			
			if(empty($param_key))
				continue;
			
			if(!isset($subtotal_fields[$param_key]))
				continue;
			
			$subtotal_results[] = $param_key;
		}
		
		return $subtotal_results;
	}
	
	function _getColumnsFromQuickSearchQuery(array $columns) {
		$view_columns = [];
		
		if(empty($columns) || !($this instanceof IAbstractView_QuickSearch))
			return false;
		
		if(false == ($search_fields = $this->getQuickSearchFields()))
			return false;
		
		foreach($columns as $column) {
			@$search_field = $search_fields[$column];
			
			if(!is_array($search_field) || empty($search_field))
				continue;
			
			$param_key = $search_field['options']['param_key'] ?? null;
			
			if(empty($param_key))
				continue;
			
			$view_columns[] = $param_key;
		}
		
		return $view_columns;
	}
	
	function removeParam($key) {
		if(isset($this->_paramsEditable[$key]))
			unset($this->_paramsEditable[$key]);
	}
	
	function removeParamRequired($key) {
		if(isset($this->_paramsRequired[$key]))
			unset($this->_paramsRequired[$key]);
	}
	
	function removeParamByField($field, &$params=null) {
		if(is_null($params))
			$params =& $this->_paramsEditable;
		
		foreach($params as $k => $p) {
		if($p instanceof DevblocksSearchCriteria)
			if($p->field == $field)
				unset($params[$k]);
		}
	}
	
	function removeAllParams() {
		$this->_paramsEditable = [];
	}
	
	function removeAllParamsRequired() {
		$this->_paramsRequired = [];
	}
	
	// Params Default
	
	function addParamsDefault($params, $replace=false) {
		if($replace)
			$this->_paramsDefault = $params;
		else
			$this->_paramsDefault = array_merge($this->_paramsDefault, $params);
	}
	
	function getParamsDefault() {
		return $this->_paramsDefault;
	}
	
	// Params Required
	
	function addParamRequired($param, $key=null) {
		if(!$key || is_numeric($key))
			$key = substr(sha1(json_encode($param)), 0, 16);
		
		$this->_paramsRequired[$key] = $param;
	}
	
	function addParamsRequired($params, $replace=false) {
		if($replace)
			$this->removeAllParamsRequired();
		
		if(is_array($params))
		foreach($params as $key => $param) {
			$key = is_numeric($key) ? null : $key;
			$this->addParamRequired($param, $key);
		}
	}
	
	function getParamsRequired() {
		return $this->_paramsRequired;
	}
	
	function getParamsRequiredQuery() {
		return $this->_paramsRequiredQuery;
	}
	
	function setParamsRequiredQuery($query) {
		$this->_paramsRequiredQuery = $query;
	}
	
	function getParamsTimezone() {
		return $this->_paramsTimezone;
	}
	
	function setParamsTimezone($timezone) {
		$this->_paramsTimezone = $timezone;
	}
	
	// Search params
	
	static function findParam($field_key, $params, $recursive=true) {
		$results = [];
		
		if($recursive) {
			array_walk_recursive($params, function(&$v, $k) use (&$results, $field_key) {
				if(!($v instanceof DevblocksSearchCriteria))
					return;
	
				if($v->field == $field_key) {
					$results[$k] = $v;
				}
			});
			
		} else {
			array_walk($params, function(&$v, $k) use (&$results, $field_key) {
				if(!($v instanceof DevblocksSearchCriteria))
					return;
	
				if($v->field == $field_key) {
					$results[$k] = $v;
				}
			});
		}
		
		return $results;
	}
	
	static function hasParam($field_key, $params, $recursive=true) {
		return count(self::findParam($field_key, $params, $recursive)) > 0;
	}
	
	public static function findKey(string $key, array $params, $recursive=true) : array {
		$results = [];
		
		if($recursive) {
			array_walk_recursive($params, function(&$v, $k) use (&$results, $key) {
				if(!($v instanceof DevblocksSearchCriteria))
					return;
				
				if($v->key == $key) {
					$results[$k] = $v;
				}
			});
			
		} else {
			array_walk($params, function(&$v, $k) use (&$results, $key) {
				if(!($v instanceof DevblocksSearchCriteria))
					return;
				
				if($v->key == $key) {
					$results[$k] = $v;
				}
			});
		}
		
		return $results;
	}
	
	// Placeholders
	
	function setPlaceholderLabels($labels, $replace=true) {
		if(!is_array($labels))
			return false;
		
		if($replace) {
			$this->_placeholderLabels = $labels;
		} else {
			$this->_placeholderLabels = array_merge($this->_placeholderLabels, $labels);
		}
	}
	
	function getPlaceholderLabels() {
		return $this->_placeholderLabels;
	}
	
	function setPlaceholderValues($values, $replace=true) {
		if(!is_array($values))
			return false;
		
		if($replace) {
			$this->_placeholderValues = $values;
		} else {
			$this->_placeholderValues = array_merge($this->_placeholderValues, $values);
		}
	}
	
	function getPlaceholderValues() {
		return $this->_placeholderValues;
	}
	
	protected static function _translatePlaceholders(&$param, $key, $args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;

		$param_key = $param->field;
		settype($param_key, 'string');

		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		if(is_string($param->value)) {
			if(false !== ($value = $tpl_builder->build($param->value, $args['placeholder_values']))) {
				$param->value = $value;
			}
			
		} elseif(is_array($param->value)) {
			foreach($param->value as $k => $v) {
				if(is_string($v)) {
					if(false !== ($value = $tpl_builder->build($v, $args['placeholder_values']))) {
						$param->value[$k] = $value;
					}
					
				} elseif(is_array($v)) {
					foreach($v as $idx => $nested_v) {
						if(!is_string($nested_v))
							continue;
						
						if(false !== ($value = $tpl_builder->build($nested_v, $args['placeholder_values']))) {
							$param->value[$k][$idx] = $value;
						}
					}
				}
			}
		}
	}
	
	// Marquee
	
	static function setMarqueeContextCreated($view_id, $context, $context_id) {
		$string = null;
		
		if(null != ($ctx = Extension_DevblocksContext::get($context))) {
			if(null != ($meta = $ctx->getMeta($context_id))) {
				if(!isset($meta['name']) || !isset($meta['permalink']))
					return;
				
				// Use abstract popups if we can
				if($ctx instanceof IDevblocksContextPeek) {
					$string = sprintf("New %s created: <a href='javascript:;' class='cerb-peek-trigger' data-context='%s' data-context-id='%d' data-profile-url='%s'><b>%s</b></a>",
						DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strLower($ctx->manifest->name)),
						DevblocksPlatform::strEscapeHtml($context),
						DevblocksPlatform::strEscapeHtml($context_id),
						DevblocksPlatform::strEscapeHtml($meta['permalink']),
						DevblocksPlatform::strEscapeHtml($meta['name'])
					);
					
				// Otherwise, try linking to profile pages
				} elseif(!empty($meta['permalink'])) {
					$string = sprintf("New %s created: <a href='%s'><b>%s</b></a>",
						DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strLower($ctx->manifest->name)),
						DevblocksPlatform::strEscapeHtml($meta['permalink']),
						DevblocksPlatform::strEscapeHtml($meta['name'])
					);
					
				// Lastly, just output some text
				} else {
					$string = sprintf("New %s created: <b>%s</b>",
						DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strLower($ctx->manifest->name)),
						DevblocksPlatform::strEscapeHtml($meta['name'])
					);
				}
			}
			
		}
		
		if($string)
			self::marqueeAppend($view_id, $string);
	}
	
	static function setMarqueeContextImported($view_id, $context, $count) {
		$string = null;
		
		if(null != ($ctx = Extension_DevblocksContext::get($context))) {
			$string = sprintf("Imported <b>%d %s</b> record%s.",
				$count,
				DevblocksPlatform::strLower($ctx->manifest->name),
				($count == 1 ? '' : 's')
			);
		}
		
		if($string)
			self::marqueeAppend($view_id, $string);
	}
	
	static function marqueeAppend($view_id, $string) {
		if(null == ($visit = CerberusApplication::getVisit()))
			return false;
		
		$visit->append($view_id . '_marquee', $string);
	}
	
	static function marqueeFlush($view_id) {
		if(null == ($visit = CerberusApplication::getVisit()))
			return false;
		
		$mar_key = $view_id . '_marquee';
		
		$marquees = $visit->get($mar_key);
		$visit->remove($mar_key);
		
		return $marquees;
	}
	
	// Render
	
	function render() {
		echo ' '; // Expect Override
	}
	
	function renderCustomizeOptions() {
		echo ' '; // Expect Override
	}
	
	protected function _renderCriteriaParamString($param, $label_map) {
		$strings = [];
		
		if(!is_array($param->value) && is_null($param->value))
			$param->value = '';
		
		$values = is_array($param->value) ? $param->value : array($param->value);
		
		if(is_callable($label_map))
			$label_map = $label_map($values);
		
		foreach($values as $v) {
			$strings[] = sprintf("<b>%s</b>",
				DevblocksPlatform::strEscapeHtml((isset($label_map[$v]) ? $label_map[$v] : $v))
			);
		}
		
		$list_of_strings = implode(' or ', $strings);
		
		if(count($strings) > 2) {
			$list_of_strings = sprintf("any of <abbr style='font-weight:bold;' title='%s'>(%d values)</abbr>",
				strip_tags($list_of_strings),
				count($strings)
			);
		}
		
		echo $list_of_strings;
	}
	
	protected function _renderCriteriaParamBoolean($param) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$strings = [];
		
		$values = is_array($param->value) ? $param->value : array($param->value);
		
		foreach($values as $v) {
			$strings[] = sprintf("<b>%s</b>",
				DevblocksPlatform::strEscapeHtml((!empty($v) ? $translate->_('common.yes') : $translate->_('common.no')))
			);
		}
		
		echo implode(' or ', $strings);
	}
	
	protected function _renderCriteriaParamWorker($param) {
		$workers = DAO_Worker::getAll();
		$strings = [];
		
		$values = $param->value;
		
		if(!is_array($values))
			$values = array($values);
		
		foreach($values as $worker_id) {
			if(!is_numeric($worker_id)) {
				$strings[] = sprintf('<b>%s</b>', $worker_id);
			} elseif(isset($workers[$worker_id])) {
				$strings[] = sprintf('<b>%s</b>',DevblocksPlatform::strEscapeHtml($workers[$worker_id]->getName()));
			} elseif (!empty($worker_id)) {
				$strings[] = sprintf('<b>%d</b>',$worker_id);
			} elseif (0 == strlen($worker_id)) {
				$strings[] = '<b>nobody</b>';
			} elseif (empty($worker_id)) {
				$strings[] = '<b>nobody</b>';
			}
		}
		
		$list_of_strings = implode(' or ', $strings);
		
		if(count($strings) > 2) {
			$list_of_strings = sprintf("any of <abbr style='font-weight:bold;' title='%s'>(%d people)</abbr>",
				strip_tags($list_of_strings),
				count($strings)
			);
		}
		
		echo $list_of_strings;
	}
	
	protected function _renderVirtualContextLinks($param, $label_singular='Link', $label_plural='Links', $label_verb='Linked to', $label_null=null) {
		$strings = [];
		
		if(is_null($label_null))
			$label_null = $label_plural;
		
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			list($alias, $query) = array_pad(explode(':', $param->value, 2), 2, null);
			list($alias, $field) = array_pad(explode('.', $alias, 2),2, null);
			
			if(empty($alias) || (false == ($mft = Extension_DevblocksContext::getByAlias($alias, false))))
				return;
			
			$aliases = Extension_DevblocksContext::getAliasesForContext($mft);
			$alias = $aliases['plural'] ?: $aliases['singular'];
			
			echo sprintf("%s %s%s <b>%s</b>",
				DevblocksPlatform::strEscapeHtml($label_verb),
				DevblocksPlatform::strEscapeHtml($alias),
				DevblocksPlatform::strEscapeHtml($field ? (' ' . $field) : ''),
				DevblocksPlatform::strEscapeHtml($query)
			);
			return;
		}
		
		if(is_array($param->value))
		foreach($param->value as $context_data) {
			list($context, $context_id) = array_pad(explode(':',$context_data), 2, null);
			
			if(empty($context))
				continue;
			
			$context_ext = Extension_DevblocksContext::get($context,true);
			
			if(!empty($context_id)) {
				$meta = $context_ext->getMeta($context_id);
				$strings[] = sprintf("<b>%s</b> (%s)", DevblocksPlatform::strEscapeHtml($meta['name']),DevblocksPlatform::strEscapeHtml($context_ext->manifest->name));
			} else {
				$strings[] = sprintf("(<b>%s</b>)", DevblocksPlatform::strEscapeHtml($context_ext->manifest->name));
			}
		}
		
		if(empty($param->value)) {
			switch($param->operator) {
				case DevblocksSearchCriteria::OPER_EQ:
				case DevblocksSearchCriteria::OPER_IN:
				case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
					break;
				case DevblocksSearchCriteria::OPER_NEQ:
				case DevblocksSearchCriteria::OPER_NIN:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
					break;
			}
		}
		
		$list_of_strings = implode(' or ', $strings);
		
		if(count($strings) > 2) {
			$list_of_strings = sprintf("any of <abbr style='font-weight:bold;' title='%s'>(%d %s)</abbr>",
				strip_tags($list_of_strings),
				count($strings),
				DevblocksPlatform::strLower($label_plural)
			);
		}
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_IS_NULL:
				echo sprintf("There are no <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strLower($label_null))
				);
				break;
			case DevblocksSearchCriteria::OPER_TRUE:
			case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
				echo sprintf("There are <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strLower($label_null))
				);
				break;
			case DevblocksSearchCriteria::OPER_IN:
				echo sprintf("%s is %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				echo sprintf("%s is blank or %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_NIN:
				echo sprintf("%s is not %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
				echo sprintf("%s is blank or not %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
		}
	}
	
	protected function _renderVirtualHasFieldset($param) {
		echo sprintf("%s matches <b>%s</b>",
			DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.custom_fieldset')),
			DevblocksPlatform::strEscapeHtml($param->value)
		);
		return;
	}
	
	protected function _renderVirtualWatchers($param) {
		return $this->_renderVirtualWorkers($param, 'Watcher', 'Watchers');
	}
	
	protected function _renderVirtualWatchersCount($param) {
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_EQ:
			case DevblocksSearchCriteria::OPER_NEQ:
			case DevblocksSearchCriteria::OPER_GT:
			case DevblocksSearchCriteria::OPER_GTE:
			case DevblocksSearchCriteria::OPER_LT:
			case DevblocksSearchCriteria::OPER_LTE:
				echo sprintf("%s %s <b>%d</b>",
					DevblocksPlatform::strEscapeHtml('Watcher count'),
					$param->operator,
					$param->value
				);
				break;
				
			case DevblocksSearchCriteria::OPER_IN:
			case DevblocksSearchCriteria::OPER_NIN:
				echo sprintf("%s %s <b>[%s]</b>",
					DevblocksPlatform::strEscapeHtml('Watcher count'),
					$param->operator,
					implode(',', $param->value)
				);
				break;
				
			case DevblocksSearchCriteria::OPER_BETWEEN:
			case DevblocksSearchCriteria::OPER_NOT_BETWEEN:
				echo sprintf("%s %s <b>%d and %d</b>",
					DevblocksPlatform::strEscapeHtml('Watcher count'),
					$param->operator,
					@$param->value[0] ?: 0,
					@$param->value[1] ?: 0
				);
				break;
		}
	}
	
	protected function _renderVirtualWorkers($param, $label_singular='Worker', $label_plural='Workers') {
		$workers = DAO_Worker::getAll();
		$strings = [];
		
		if(is_array($param->value))
		foreach($param->value as $worker_id) {
			if(isset($workers[$worker_id]))
				$strings[] = sprintf("<b>%s</b>",DevblocksPlatform::strEscapeHtml($workers[$worker_id]->getName()));
			elseif(empty($worker_id)) {
				$strings[] = '<b>nobody</b>';
			} else {
				$strings[] = sprintf("<b>%d</b>",$worker_id);
			}
		}
		
		if(empty($param->value)) {
			switch($param->operator) {
				case DevblocksSearchCriteria::OPER_EQ:
				case DevblocksSearchCriteria::OPER_IN:
				case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
					break;
				case DevblocksSearchCriteria::OPER_NEQ:
				case DevblocksSearchCriteria::OPER_NIN:
					$param->operator = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
					break;
			}
		}
		
		$list_of_strings = implode(' or ', $strings);
		
		if(count($strings) > 2) {
			$list_of_strings = sprintf("any of <abbr style='font-weight:bold;' title='%s'>(%d people)</abbr>",
				strip_tags($list_of_strings),
				count($strings)
			);
		}
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_CUSTOM:
				echo sprintf("Watcher matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			case DevblocksSearchCriteria::OPER_IS_NULL:
				echo sprintf("There are no <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($label_plural)
				);
				break;
			case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
				echo sprintf("There are <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($label_plural)
				);
				break;
			case DevblocksSearchCriteria::OPER_IN:
				echo sprintf("%s is %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				echo sprintf("%s is blank or %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_NIN:
				echo sprintf("%s is not %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
			case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
				echo sprintf("%s is blank or not %s", DevblocksPlatform::strEscapeHtml($label_singular), $list_of_strings);
				break;
		}
	}
	
	/**
	 *
	 * @param string $field
	 * @param string $oper
	 * @param string $value
	 * @abstract
	 */
	function doSetCriteria($field, $oper, $value) {
		// Expect Override
	}

	protected function _doSetCriteriaString($field, $oper, $value) {
		// force wildcards if none used on a LIKE
		if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
		&& false === (strpos($value,'*'))) {
			$value = '*'.$value.'*';
		}
		return new DevblocksSearchCriteria($field, $oper, $value);
	}
	
	protected function _doSetCriteriaDate($field, $oper) {
		switch($oper) {
			default:
			case DevblocksSearchCriteria::OPER_BETWEEN:
			case DevblocksSearchCriteria::OPER_NOT_BETWEEN:
				$from = DevblocksPlatform::importGPC($_POST['from'] ?? null, 'string','big bang');
				$to = DevblocksPlatform::importGPC($_POST['to'] ?? null, 'string','now');
		
				if(is_null($from) || (!is_numeric($from) && @false === strtotime(str_replace('.','-',$from))))
					$from = 'big bang';
					
				if(is_null($to) || (!is_numeric($to) && @false === strtotime(str_replace('.','-',$to))))
					$to = 'now';
				
				return new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case DevblocksSearchCriteria::OPER_EQ_OR_NULL:
				return new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_EQ_OR_NULL,0);
				break;
		}
		
	}
	
	protected function _doSetCriteriaWorker($field, $oper) {
		$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
		
		switch($oper) {
			case DevblocksSearchCriteria::OPER_IN:
				if(empty($worker_ids)) {
					$worker_ids[] = '0';
				}
				break;
			case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				$oper = DevblocksSearchCriteria::OPER_IN;
				if(!in_array('0', $worker_ids))
					$worker_ids[] = '0';
				break;
			case DevblocksSearchCriteria::OPER_NIN:
				if(empty($worker_ids)) {
					$worker_ids[] = '0';
				}
				break;
			case 'not in and not null':
				$oper = DevblocksSearchCriteria::OPER_NIN;
				if(!in_array('0', $worker_ids))
					$worker_ids[] = '0';
				break;
			case DevblocksSearchCriteria::OPER_EQ:
			case DevblocksSearchCriteria::OPER_NEQ:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'integer',0);
				break;
		}
		
		return new DevblocksSearchCriteria($field, $oper, $worker_ids);
	}
	
	protected function _doSetCriteriaCustomField($token, $field_id) {
		$field = DAO_CustomField::get($field_id);
		$oper = DevblocksPlatform::importGPC($_POST['oper'] ?? null, 'string','');
		$value = DevblocksPlatform::importGPC($_POST['value'] ?? null, 'string','');
		
		$criteria = null;
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				if(!empty($options)) {
					$criteria = new DevblocksSearchCriteria($token,$oper,$options);
				} else {
					$criteria = new DevblocksSearchCriteria($token,DevblocksSearchCriteria::OPER_IS_NULL);
				}
				break;
				
			case Model_CustomField::TYPE_CHECKBOX:
				$criteria = new DevblocksSearchCriteria($token,$oper,!empty($value) ? 1 : 0);
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				$criteria = new DevblocksSearchCriteria($token,$oper,intval($value));
				break;
				
			case Model_CustomField::TYPE_DATE:
				$from = DevblocksPlatform::importGPC($_POST['from'] ?? null, 'string','');
				$to = DevblocksPlatform::importGPC($_POST['to'] ?? null, 'string','');
	
				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';
	
				switch($oper) {
					case DevblocksSearchCriteria::OPER_EQ_OR_NULL:
						$criteria = new DevblocksSearchCriteria($token,$oper,0);
						break;
						
					default:
						$criteria = new DevblocksSearchCriteria($token,$oper,array($from,$to));
						break;
				}
				
				break;
				
			case Model_CustomField::TYPE_WORKER:
				$oper = DevblocksPlatform::importGPC($_POST['oper'] ?? null, 'string','eq');
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				
				if(empty($worker_ids)) {
					switch($oper) {
						case DevblocksSearchCriteria::OPER_IN:
							$oper = DevblocksSearchCriteria::OPER_IS_NULL;
							$worker_ids = null;
							break;
						case DevblocksSearchCriteria::OPER_NIN:
							$oper = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
							$worker_ids = null;
							break;
					}
				}
				
				$criteria = new DevblocksSearchCriteria($token,$oper,$worker_ids);
				break;
				
			case Model_CustomField::TYPE_LINK:
				$context_id = DevblocksPlatform::importGPC($_POST['context_id'] ?? null, 'integer','');
				$criteria = new DevblocksSearchCriteria($token,$oper,$context_id);
				break;
				
			default: // TYPE_SINGLE_LINE || TYPE_MULTI_LINE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($token,$oper,$value);
				break;
		}
		
		return $criteria;
	}
	
	protected function _appendVirtualFiltersFromQuickSearchContexts($prefix, $fields=[], $option='search', $param_key=null) {
		$context_mfts = Extension_DevblocksContext::getAll(false, [$option]);
		$context_uris = Extension_DevblocksContext::getUris();
		
		$fields[$prefix] = array(
			'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
			'options' => ['param_key' => $param_key],
			'examples' => $context_uris,
		);
		
		if($param_key)
			$fields[$prefix]['options']['param_key'] = $param_key;
		
		foreach($context_mfts as $context_mft) {
			if(false == ($alias = $context_mft->params['alias']))
				continue;
			
			$field = array(
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'score' => 500,
				'options' => [
					'param_key' => $param_key
				],
				'examples' => [
					['type' => 'search', 'context' => $context_mft->id, 'q' => ''],
				]
			);
			
			if($context_mft->id == CerberusContexts::CONTEXT_APPLICATION)
				$field['examples'] = [
					['type' => 'list', 'values' => ['Cerb' => 'Cerb']],
				];
			
			$fields[$prefix.'.'.str_replace(' ', '.', $alias)] = $field;
		}
		
		return $fields;
	}
	
	protected function _appendFieldLinksFromQuickSearchContext($context, $fields=[], $prefix=null) {
		if(false == Extension_DevblocksContext::get($context, false))
			return $fields;
		
		if(false == ($context_mfts = Extension_DevblocksContext::getAll(false)))
			return $fields;
		
		// All custom field record link + links
		$custom_field_links = array_filter(
			DAO_CustomField::getAll(),
			function(Model_CustomField $field) use ($context) {
				if(
					$field->type == Model_CustomField::TYPE_WORKER 
					&& $context == CerberusContexts::CONTEXT_WORKER
					)
					return true;
				
				return
					in_array($field->type, [
						Model_CustomField::TYPE_LINK,
						CustomField_RecordLinks::ID,
					])
					&& CerberusContexts::isSameContext($context, $field->params['context'] ?? '')
				;
			}
		);
		
		if(!is_array($custom_field_links) || !$custom_field_links)
			return $fields;
		
		foreach($custom_field_links as $field_id => $field) { /* @var Model_CustomField $field */
			if(null == ($link_context_mft = ($context_mfts[$field->context] ?? null)))
				continue;
			
			if(null == ($link_alias = ($link_context_mft->params['alias'] ?? null)))
				continue;
			
			// Prefix the custom fieldset namespace
			$field_key = sprintf("%slinks.%s.%s",
				!empty($prefix) ? ($prefix . '.') : '',
				$link_alias,
				$field->uri
			);
			
			$search_field_meta = [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'is_sortable' => false,
				'options' => [
					'param_key' => sprintf("cf_%d", $field_id),
					'cf_ctx' => $link_context_mft->id,
					'cf_id' => $field->id,
				],
				'examples' => [
					[
						'type' => 'search',
						'context' => $link_context_mft->id,
						'q' => '',
					]
				]
			];
			
			$fields[$field_key] = $search_field_meta;
		}
		
		return $fields;
	}
	
	protected function _appendFieldsFromQuickSearchContext($context, $fields=[], $prefix=null) {
		$custom_fields = DAO_CustomField::getByContext($context, true, false);
		$custom_fieldsets = DAO_CustomFieldset::getAll();
		
		foreach($custom_fields as $cf_id => $cfield) {
			$search_field_meta = array(
				'type' => null,
				'is_sortable' => true,
				'options' => [
					'param_key' => sprintf("cf_%d", $cf_id),
					'cf_ctx' => $cfield->context,
					'cf_id' => $cf_id,
				]
			);
			
			$custom_fieldset = $custom_fieldsets[$cfield->custom_fieldset_id] ?? null;
			
			// Prefix the custom fieldset namespace
			$field_key = sprintf("%s%s%s",
				!empty($prefix) ? ($prefix . '.') : '',
				$custom_fieldset ? (DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($custom_fieldset->name, MB_CASE_TITLE))) . '.') : '',
				DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($cfield->name, MB_CASE_TITLE)))
			);
			
			// Make sure the field key is unique by appending the cf_id when it already exists
			if(isset($fields[$field_key])) {
				$field_key .= $cf_id;
			}
			
			switch($cfield->type) {
				case Model_CustomField::TYPE_CHECKBOX:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_BOOL;
					break;
					
				case Model_CustomField::TYPE_CURRENCY:
					$currency_id = $cfield->params['currency_id'] ?? 0;
					
					if(!$currency_id || false == ($currency = DAO_Currency::get($currency_id)))
						break;
					
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_DECIMAL;
					$search_field_meta['options']['decimal_at'] = $currency->decimal_at;
					break;
					
				case Model_CustomField::TYPE_DATE:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_DATE;
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_DECIMAL;
					$search_field_meta['options']['decimal_at'] = @intval($cfield->params['decimal_at']);
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_TEXT;
					$search_field_meta['options']['match'] = DevblocksSearchCriteria::OPTION_TEXT_PREFIX;
					if(isset($cfield->params['options']) && is_array($cfield->params['options']))
						$search_field_meta['examples'] = array_slice(
								array_map(function($e) { 
									return sprintf('"%s"', $e);
								},
								$cfield->params['options']
							),
							0,
							20
						);
					break;
					
				case Model_CustomField::TYPE_FILE:
				case Model_CustomField::TYPE_FILES:
				case Model_CustomField::TYPE_LINK:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_VIRTUAL;
					
					if(in_array($cfield->type, [Model_CustomField::TYPE_FILE,Model_CustomField::TYPE_FILES])) {
						$link_context_id = CerberusContexts::CONTEXT_ATTACHMENT;
					} else {
						$link_context_id = $cfield->params['context'] ?? null;
					}
					
					// Deep search links
					
					$search_field_meta['examples'][] = [
						'type' => 'search',
						'context' => $link_context_id,
						'q' => '',
					];

					// Add a field.id quick search key for choosers
					
					$id_field_meta = [
						'type' => DevblocksSearchCriteria::TYPE_NUMBER,
						'is_sortable' => true,
						'options' => [
							'param_key' => sprintf("cf_%d", $cf_id),
							'cf_ctx' => $cfield->context,
							'cf_id' => $cf_id,
						],
						'examples' => [
							[
								'type' => 'chooser',
								'context' => $link_context_id,
								'q' => '',
								'single' => false,
							],
						]
					];
					
					$fields[$field_key . '.id'] = $id_field_meta;
					break;
					
				case Model_CustomField::TYPE_LIST:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_TEXT;
					$search_field_meta['options']['match'] = DevblocksSearchCriteria::OPTION_TEXT_PREFIX;
					break;
					
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_TEXT;
					$search_field_meta['options']['match'] = DevblocksSearchCriteria::OPTION_TEXT_PARTIAL;
					if(isset($cfield->params['options']))
						$search_field_meta['examples'] = array_slice(
								array_map(function($e) { 
									return sprintf('"%s"', $e);
								},
								$cfield->params['options']
							),
							0,
							20
						);
					break;
					
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_TEXT;
					$search_field_meta['options']['match'] = DevblocksSearchCriteria::OPTION_TEXT_PARTIAL;
					break;
					
				case Model_CustomField::TYPE_NUMBER:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_NUMBER;
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_WORKER;
					
					// Add a field.id quick search key for choosers
					
					$id_field_meta = [
						'type' => DevblocksSearchCriteria::TYPE_NUMBER,
						'is_sortable' => true,
						'options' => [
							'param_key' => sprintf("cf_%d", $cf_id),
							'cf_ctx' => $cfield->context,
							'cf_id' => $cf_id,
						],
						'examples' => [
							[
								'type' => 'chooser',
								'context' => CerberusContexts::CONTEXT_WORKER,
								'q' => '',
								'single' => false,
							],
						]
					];
					
					$fields[$field_key . '.id'] = $id_field_meta;
					break;
					
				default:
					if(false != ($field_ext = $cfield->getTypeExtension())) {
						$field_ext->populateQuickSearchMeta($cfield, $search_field_meta);
					}
					break;
			}
			
			// Skip custom field types we can't quick search easily
			if(empty($search_field_meta['type']))
				continue;
			
			$fields[$field_key] = $search_field_meta;
		}
		
		// Add reverse links for custom fields
		if($prefix) { // Skip nested links for now (ticket->org)
			return $fields;
		} else {
			return self::_appendFieldLinksFromQuickSearchContext($context, $fields, $prefix);
		}
	}
	
	protected function _setSortableQuickSearchFields($fields, $search_fields) {
		foreach($fields as &$field) {
			$param_key = $field['options']['param_key'] ?? null;
			$field['is_sortable'] = ($param_key && isset($search_fields[$param_key]) && $search_fields[$param_key]->is_sortable);
		}
		
		return $fields;
	}

	/**
	 * This method automatically fixes any cached strange options, like
	 * deleted custom fields.
	 *
	 */
	protected function _sanitize() {
		$fields = $this->getColumnsAvailable();
		$custom_fields = DAO_CustomField::getAll();
		
		$params = $this->getParams(false);
		
		// Parameter sanity check
		if(is_array($params))
		foreach(array_keys($params) as $pidx) {
			if(substr($pidx,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($pidx,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					$this->removeParam($pidx);
				}
			}
		}
		unset($params);
		
		// View column sanity check
		if(is_array($this->view_columns))
		foreach($this->view_columns as $cidx => $c) {
			// Custom fields
			if(substr($c,0,3) == "cf_") {
				if(0 != ($cf_id = intval(substr($c,3)))) {
					// Make sure our custom fields still exist
					if(!isset($custom_fields[$cf_id])) {
						unset($this->view_columns[$cidx]);
					}
				}
			} else {
				// If the column no longer exists (rare but worth checking)
				if(!isset($fields[$c])) {
					unset($this->view_columns[$cidx]);
				}
			}
		}
		
		// Sort by sanity check
		if(is_array($this->renderSortBy)) {
			foreach($this->renderSortBy as $idx => $k) {
				if(DevblocksPlatform::strStartsWith($k, "cf_")) {
					if(0 != ($cf_id = intval(substr($k,3)))) {
						if(!isset($custom_fields[$cf_id])) {
							unset($this->renderSortBy[$idx]);
							unset($this->renderSortAsc[$idx]);
						}
					}
				}
			}
		} else if (is_string($this->renderSortBy)) {
			if(DevblocksPlatform::strStartsWith($this->renderSortBy, 'cf_')) {
				if(0 != ($cf_id = intval(substr($this->renderSortBy,3)))) {
					if(!isset($custom_fields[$cf_id]))
						$this->renderSortBy = null;
				}
			}
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$vals = $param->value;

		if(!is_array($vals))
			$vals = array($vals);
		
		$fields = $this->getFields();
		
		if(isset($fields[$field]) && $fields[$field]->type == Model_CustomField::TYPE_DATE) {
			$implode_token = ' to ';
			
		} else if(in_array($param->operator, array(DevblocksSearchCriteria::OPER_BETWEEN, DevblocksSearchCriteria::OPER_NOT_BETWEEN))) {
			$implode_token = ' and ';
			
		} else {
			$implode_token = ' or ';
			
		}

		if($param->operator == DevblocksSearchCriteria::OPER_FULLTEXT)
			unset($vals[1]);
		
		// Do we need to do anything special on custom fields?
		if('cf_'==substr($field,0,3)) {
			$field_id = intval(substr($field,3));
			$custom_fields = DAO_CustomField::getAll();
			
			$translate = DevblocksPlatform::getTranslationService();
			
			if(!isset($custom_fields[$field_id]))
				return;
			
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_CHECKBOX:
					foreach($vals as $idx => $val) {
						$vals[$idx] = !empty($val) ? $translate->_('common.yes') : $translate->_('common.no');
					}
					break;
					
				case Model_CustomField::TYPE_DATE:
					if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
						if (array_key_exists('label', $param->value)) {
							echo $param->value['label'];
							return;
						}
					}
					
					$implode_token = ' to ';
					break;
					
				case Model_CustomField::TYPE_CURRENCY:
					$currency_id = $custom_fields[$field_id]->params['currency_id'] ?? null;
					
					if(false == ($currency = DAO_Currency::get($currency_id)))
						break;
					
					foreach($vals as $idx => $val) {
						$vals[$idx] = $currency->format($val, true);
					}
					
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					$decimal_at = $custom_fields[$field_id]->params['decimal_at'] ?? null;
					
					foreach($vals as $idx => $val) {
						$vals[$idx] = DevblocksPlatform::strFormatDecimal($val, $decimal_at);
					}
					break;
					
				case Model_CustomField::TYPE_LINK:
					if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
						echo DevblocksPlatform::strEscapeHtml($param->value);
						return;
						
					} else {
						$context = $custom_fields[$field_id]->params['context'] ?? null;
						
						if(empty($context) || empty($vals))
							break;
						
						if(false == ($context_ext = Extension_DevblocksContext::get($context)))
							break;
						
						$dao_class = $context_ext->getDaoClass();
						
						$models = $dao_class::getIds($vals);
						
						$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context, ['_label']);
						
						$vals = array_column($dicts, '_label') ?: [];
					}
					
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$this->_renderCriteriaParamWorker($param);
					return;
					
				default:
					$cfield = $custom_fields[$field_id];
					if(false != ($field_ext = $cfield->getTypeExtension())) {
						$field_ext->prepareCriteriaParam($cfield, $param, $vals, $implode_token);
					}
					break;
			}
			
		} else if ($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			if(is_array($param->value) && array_key_exists('label', $param->value)) {
				echo $param->value['label'];
				return;
			}
		}
		
		// HTML escape
		if(is_array($vals))
		foreach($vals as $k => $v) {
			$vals[$k] = DevblocksPlatform::strEscapeHtml($v);
		}
		
		echo implode($implode_token, $vals);
	}

	/**
	 * All the view's available fields
	 *
	 * @return array
	 */
	function getFields() {
		// Expect Override
		return [];
	}

	function doCustomize($columns, $num_rows=10, $options=[]) {
		$this->renderLimit = $num_rows;

		$viewColumns = [];
		foreach($columns as $col) {
			if(empty($col))
				continue;
			$viewColumns[] = $col;
		}

		$this->view_columns = $viewColumns;
		
		$this->options = $options;
	}

	function doSortBy($sortBy) {
		$render_sort_by = is_array($this->renderSortBy) ? array_shift($this->renderSortBy) : $this->renderSortBy;
		$render_sort_asc = is_array($this->renderSortAsc) ? array_shift($this->renderSortAsc) : ($this->renderSortAsc ? true : false);
		
		// [JAS]: If clicking the same header, toggle asc/desc.
		if(0 == strcasecmp($sortBy, $render_sort_by)) {
			$render_sort_asc = empty($render_sort_asc) ? true : false;
		} else { // [JAS]: If a new header, start with asc.
			$render_sort_asc = true;
		}
		
		$this->renderSortBy = $sortBy;
		$this->renderSortAsc = $render_sort_asc;
	}

	function doPage($page) {
		$this->renderPage = intval($page);
	}

	function doRemoveCriteria($key) {
		$this->removeParam($key);
		$this->renderPage = 0;
	}
	
	function doResetCriteria() {
		$this->addParams($this->_paramsDefault, true);
		$this->renderPage = 0;
	}
	
	function getQueryAutocompleteSuggestions() {
		$suggestions = [
			'' => [],
			'_contexts' => [],
		];
		
		$query_fields = $this->getQuickSearchFields();
		
		foreach($query_fields as $query_field_key => $query_field) {
			$suggestion_key = $query_field_key . ':'; 
			$suggestion = $suggestion_key;
			
			// Type-specific suggestions
			switch($query_field['type']) {
				case 'bool':
					$suggestions[$suggestion_key] = [
						'yes',
						'no',
					];
					break;
					
				case 'context':
					$suggestion = [
						'caption' => $suggestion_key,
						'snippet' => $suggestion_key . '[${1}]',
					];
					break;
					
				case 'date':
					$suggestions[$suggestion_key] = [
						[
							'caption' => '(date range)',
							'snippet' => '"${1:-1 week} to ${2:now}"'
						],
						[
							'caption' => '(advanced)',
							'snippet' => '(since:"${1:-1 week}" until:"${2:now}" days:[${3:Weekdays}] hours:${4:9a-5p})'
						],
					];
					break;
					
				case 'decimal':
					$suggestions[$suggestion_key] = [
						[
							'caption' => '(equals)',
							'snippet' => '${1:3.14}'
						],
						[
							'caption' => '(not)',
							'snippet' => '!${1:3.14}'
						],
						[
							'caption' => '(greater than)',
							'snippet' => '>${1:3.14}'
						],
						[
							'caption' => '(less than)',
							'snippet' => '<${1:3.14}'
						],
						[
							'caption' => '(in set)',
							'snippet' => '[${1:3.14},${2:1.234}]'
						],
					];
					break;
					
				case 'fulltext':
					if (array_key_exists('examples', $query_field)) {
						$suggestions[$suggestion_key] = [];
						
						foreach($query_field['examples'] as $example) {
							$suggestions[$suggestion_key][] = $example . ' ';
						}
						
					} else {
						//var_dump($query_field);
					}
					break;
					
				case 'geo_point':
					break;
					
				case 'number':
					$suggestions[$suggestion_key] = [
						[
							'caption' => '(equals)',
							'snippet' => '${1:1234}'
						],
						[
							'caption' => '(not)',
							'snippet' => '!${1:1234}'
						],
						[
							'caption' => '(greater than)',
							'snippet' => '>${1:1234}'
						],
						[
							'caption' => '(less than)',
							'snippet' => '<${1:1234}'
						],
						[
							'caption' => '(between)',
							'snippet' => '${1:1}...${2:100}'
						],
						[
							'caption' => '(in set)',
							'snippet' => '[${1:1},${2:2}]'
						],
					];
					break;
					
				case 'number_seconds':
					$suggestions[$suggestion_key] = [
						[
							'caption' => '(human readable time)',
							'snippet' => '"${1:5 mins}"'
						],
					];
					break;
					
				case 'text':
					if(array_key_exists('suggester', $query_field)) {
						$suggestions[$suggestion_key] = [
							'_type' => 'autocomplete',
							'query' => $query_field['suggester']['query'],
							'key' => $query_field['suggester']['key'],
							'limit' => ($query_field['suggester']['limit'] ?? null) ?: 0,
							'min_length' => ($query_field['suggester']['min_length'] ?? null) ?: 0,
						];
						
					} else if(array_key_exists('examples', $query_field)) {
						$suggestions[$suggestion_key] = [];
						
						$examples = $query_field['examples'];
						
						if(!is_array($examples))
							break;
						
						$examples_type = $examples[0]['type'] ?? null;
						
						if($examples_type) {
							$examples = $examples[0];
							
							switch ($examples_type) {
								case 'list':
									if (array_key_exists('values', $examples)) {
										foreach ($examples['values'] as $value => $label) {
											$suggestions[$suggestion_key][] = [
												'caption' => $label,
												'snippet' => $value,
											];
										}
									}
									break;
							}
						} else {
							foreach($examples as $value) {
								$suggestions[$suggestion_key][] = $value;
							}
						}
						
					} else {
						$suggestions[$suggestion_key] = [
							[
								'caption' => '(string)',
								'snippet' => '"${1}"',
							],
						];
					}
					break;
					
				case 'virtual':
					$field_type = $query_field['examples'][0]['type'] ?? null;
					
					if('search' == $field_type) {
						$suggestion = [
							'caption' => $suggestion_key,
							'snippet' => $suggestion_key . '(${1})',
						];
						
						$suggestions['_contexts'][$suggestion_key] = $query_field['examples'][0]['context'];
						
					} else if('chooser' == $field_type) {
						$suggestion = [
							'caption' => $suggestion_key,
							'snippet' => $suggestion_key . '[${1}]',
						];
						
						$suggestions['_contexts'][$suggestion_key] = $query_field['examples'][0]['context'];
						
					} else if (array_key_exists('examples', $query_field)) {
						$suggestions[$suggestion_key] = [];
						
						foreach($query_field['examples'] as $example) {
							$suggestions[$suggestion_key][] = $example;
						}
					} else {
						$suggestions[$suggestion_key] = [];
					}
					break;
					
				case 'worker':
					break;
					
				default:
					if (array_key_exists('examples', $query_field)) {
						$suggestions[$suggestion_key] = [];
						
						foreach($query_field['examples'] as $example) {
							$suggestions[$suggestion_key][] = $example;
						}
						
					} else {
						//error_log(json_encode($query_field));
					}
					break;
			}
			
			if(array_key_exists('score', $query_field)) {
				if(is_array($suggestion)) {
					$suggestion['score'] = $query_field['score'];
					
				} else if (is_string($suggestion)) {
					$suggestion = [
						'value' => $suggestion,
						'score' => $query_field['score'],
					];
				}
			}
			
			// Add to top-level suggestions
			$suggestions[''][] = $suggestion;
		}
		
		$suggestions[''][] = [
			'caption' => 'limit:',
			'snippet' => 'limit:${1:25}'
		];
		
		$search_params = $this->getParamsAvailable();
		
		// Sort
		
		$suggestions[''][] = [
			'caption' => 'sort:',
			'snippet' => 'sort:[${1}]'
		];
		$suggestions['sort:'] = [];
		
		foreach($query_fields as $field_key => $field) {
			if(!$field['is_sortable'])
				continue;
			
			if(false == (@$search_params[$field['options']['param_key']]))
				continue;
			
			$suggestions['sort:'][] = $field_key;
		}
		
		// Subtotal
		
		if($this instanceof IAbstractView_Subtotals) {
			$suggestions[''][] = [
				'caption' => 'subtotal:',
				'snippet' => 'subtotal:[${1}]'
			];
			$suggestions['subtotal:'] = $this->getQueryAutocompleteFieldSuggestions(null, true);
		}
		
		// Timezone
		$suggestions['set.timezone:'] = DevblocksPlatform::services()->date()->getTimezones();
		
		// Saved searches
		
		if(false != ($view_context = $this->getContext()) 
			&& false != ($active_worker = CerberusApplication::getActiveWorker())
		) {
			$searches = DAO_ContextSavedSearch::getUsableByActor($active_worker, $view_context);
			
			foreach($searches as $search) {
				$suggestions[''][] = [
					'caption' => '#' . $search->tag,
					'snippet' => $search->query,
					'suppress_autocomplete' => true,
				];
			}
		}
		
		return $suggestions;
	}
	
	function getQueryAutocompleteFieldSuggestions($types=null, $as_subtotals=false) {
		$suggestions = [];
		
		if($this instanceof IAbstractView_Subtotals) {
			$query_fields = $this->getQuickSearchFields();
			$search_params = $this->getParamsAvailable();
			
			foreach($query_fields as $field_key => $field) {
				if(false == ($search_params[$field['options']['param_key'] ?? null] ?? null))
					continue;
				
				// Filter types
				if($types && !in_array($field['type'], $types))
					continue;
				
				if('links.' == $field_key || DevblocksPlatform::strStartsWith($field_key, ['links.']))
					continue;
				
				$suggestions[] = $field_key;
				
				if($as_subtotals) {
					if('date' == $field['type']) {
						$suggestions[] = $field_key . '@hourofday';
						$suggestions[] = $field_key . '@hourofdayofweek';
						$suggestions[] = $field_key . '@hour';
						$suggestions[] = $field_key . '@day';
						$suggestions[] = $field_key . '@dayofmonth';
						$suggestions[] = $field_key . '@dayofweek';
						$suggestions[] = $field_key . '@minute';
						$suggestions[] = $field_key . '@week';
						$suggestions[] = $field_key . '@week-sun';
						$suggestions[] = $field_key . '@weekofyear';
						$suggestions[] = $field_key . '@month';
						$suggestions[] = $field_key . '@monthofyear';
						$suggestions[] = $field_key . '@quarter';
						$suggestions[] = $field_key . '@quarterofyear';
						$suggestions[] = $field_key . '@year';
					}
				}
			}
		}
		
		return $suggestions;
	}
	
	function renderSubtotals() {
		if(!$this instanceof IAbstractView_Subtotals)
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $this->id);
		$tpl->assign('view', $this);

		$fields = $this->getSubtotalFields();
		$tpl->assign('subtotal_fields', $fields);
		
		$counts = $this->getSubtotalCounts($this->renderSubtotals) ?: [];
		
		// Unless we're subtotalling by group, limit the results to top 20
		if($this->renderSubtotals != 't_group_id' && is_array($counts))
			$counts = array_slice($counts, 0, 20);
		
		$tpl->assign('subtotal_counts', $counts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/sidebar.tpl');
	}
	
	protected function _canSubtotalCustomField($field_key) {
		$custom_fields = DAO_CustomField::getAll();
		
		if('cf_' != substr($field_key,0,3))
			return false;
		
		$cfield_id = substr($field_key,3);
		
		if(!isset($custom_fields[$cfield_id]))
			return false;
			
		$cfield = $custom_fields[$cfield_id]; /* @var $cfield Model_CustomField */

		$pass = false;
		
		switch($cfield->type) {
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
			case Model_CustomField::TYPE_LINK:
			case Model_CustomField::TYPE_LIST:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
			case Model_CustomField::TYPE_WORKER:
				$pass = true;
				break;
		}
		
		return $pass;
	}
	
	protected function _getSubtotalDataForColumn($context, $field_key) {
		$db = DevblocksPlatform::services()->database();
		
		$fields = $this->getFields();
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return [];
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return [];
		
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return [];
		
		if(!isset($columns[$field_key]))
			$columns[] = $field_key;
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = sprintf("SELECT %s.%s as label, count(*) as hits ", //SQL_CALC_FOUND_ROWS
				$fields[$field_key]->db_table,
				$fields[$field_key]->db_column
			).
			$join_sql.
			$where_sql.
			sprintf("GROUP BY %s.%s ",
				$fields[$field_key]->db_table,
				$fields[$field_key]->db_column
			).
			"ORDER BY hits DESC ".
			"LIMIT 0,250 "
		;
		
		try {
			$results = $db->GetArrayReader($sql, 15000);
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$results = false;
		}
		
		return $results;
	}
	
	protected function _getSubtotalDataForVirtualColumn($context, $field_key) {
		$db = DevblocksPlatform::services()->database();
		
		$fields = $this->getFields();
		$columns = $this->view_columns;

		$params = $this->getParams();
		
		$param = new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_IS_NOT_NULL, true);
		$param_key = substr(sha1(json_encode($param)), 0, 16);
		$params[$param_key] = $param;
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return [];
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return [];
		
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return [];
		
		if(!isset($columns[$field_key]))
			$columns[] = $field_key;
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = sprintf("SELECT %s.%s as label, count(*) as hits ", //SQL_CALC_FOUND_ROWS
				$fields[$field_key]->db_table,
				$fields[$field_key]->db_column
			).
			$join_sql.
			$where_sql.
			sprintf("GROUP BY %s.%s ",
				$fields[$field_key]->db_table,
				$fields[$field_key]->db_column
			).
			"ORDER BY hits DESC ".
			"LIMIT 0,250 "
		;
		
		try {
			$results = $db->GetArrayReader($sql, 15000);
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$results = false;
		}

		return $results;
	}
	
	protected function _getSubtotalCountForVirtualColumn($context, $field_key, $label_map, $virtual_key, $virtual_query, $virtual_query_null) {
		$counts = [];
		$results = $this->_getSubtotalDataForVirtualColumn($context, $field_key);
		
		if(!is_array($results))
			return $counts;
		
		if(is_callable($label_map)) {
			$label_map = $label_map(array_column($results, 'label'));
		}
		
		foreach($results as $result) {
			$label = $result['label'];
			$key = $label;
			$hits = $result['hits'];

			if(is_array($label_map) && isset($label_map[$result['label']]))
				$label = $label_map[$result['label']];
			
			// Null strings
			if(empty($label)) {
				$label = '(none)';
				if(!isset($counts[$key]))
					$counts[$key] = [
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							[
								'field' => $virtual_key,
								'oper' => DevblocksSearchCriteria::OPER_CUSTOM,
								'values' => ['value' => $virtual_query_null],
							],
						'children' => []
					];
				
			// Anything else
			} else {
				if(!isset($counts[$key]))
					$counts[$key] = [
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							[
								'field' => $virtual_key,
								'oper' => DevblocksSearchCriteria::OPER_CUSTOM,
								'values' => ['value' => sprintf($virtual_query, $key)],
							],
						'children' => []
					];
				
			}
			
		}
		
		return $counts;
	}
	
	protected function _getSubtotalCountForStringColumn($context, $field_key, $label_map=[], $value_oper='=', $value_key='value') {
		$counts = [];
		$results = $this->_getSubtotalDataForColumn($context, $field_key);
		
		if(!is_array($results))
			return $counts;
		
		if(is_callable($label_map)) {
			$label_map = $label_map(array_column($results, 'label'));
		}
		
		foreach($results as $result) {
			$label = $result['label'];
			$key = $label;
			$hits = $result['hits'];

			if(is_array($label_map) && isset($label_map[$result['label']]))
				$label = $label_map[$result['label']];
			
			// Null strings
			if(empty($label)) {
				$label = '(none)';
				if(!isset($counts[$key]))
					$counts[$key] = [
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							[
								'field' => $field_key,
								'oper' => DevblocksSearchCriteria::OPER_IN_OR_NULL,
								'values' => [$value_key => ''],
							],
						'children' => [],
					];
				
			// Anything else
			} else {
				if(!isset($counts[$key]))
					$counts[$key] = [
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							[
								'field' => $field_key,
								'oper' => $value_oper,
								'values' => [$value_key => $key],
							],
						'children' => [],
					];
				
			}
		}
		
		return $counts;
	}
	
	protected function _getSubtotalCountForNumberColumn($context, $field_key, $label_map=[], $value_oper='=', $value_key='value') {
		$counts = [];
		$results = $this->_getSubtotalDataForColumn($context, $field_key);
		
		if(!is_array($results))
			return $counts;
		
		if(is_callable($label_map)) {
			$label_map = $label_map(array_column($results, 'label'));
		}
		
		foreach($results as $result) {
			$label = $result['label'];
			$key = $label;
			$hits = $result['hits'];

			if(isset($label_map[$result['label']]))
				$label = $label_map[$result['label']];
			
			// Null strings
			if(empty($label)) {
				$label = '(none)';
				if(!isset($counts[$key]))
					$counts[$key] = array(
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => DevblocksSearchCriteria::OPER_IN,
								'values' => array($value_key => 0),
							),
						'children' => []
					);
				
			// Anything else
			} else {
				if(!isset($counts[$key]))
					$counts[$key] = array(
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => $value_oper,
								'values' => [$value_key => $key],
							),
						'children' => []
					);
				
			}
			
		}
		
		return $counts;
	}
	
	protected function _getSubtotalCountForBooleanColumn($context, $field_key) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = [];
		$results = $this->_getSubtotalDataForColumn($context, $field_key);
		
		if(!is_array($results))
			return $counts;
		
		foreach($results as $result) {
			$label = $result['label'];
			$hits = $result['hits'];

			if(!empty($label)) {
				$label = $translate->_('common.yes');
				$value = 1;
				
			} else {
				$label = $translate->_('common.no');
				$value = 0;
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $hits,
					'label' => $label,
					'filter' =>
						array(
							'field' => $field_key,
							'oper' => '=',
							'values' => array('bool' => $value),
						),
					'children' => []
				);
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForWatcherColumn($context, $field_key) {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;
		$params = $this->getParams();

		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return [];
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return [];
		
		if(false == ($search_class = $context_ext->getSearchClass()))
			return [];
		
		if(!method_exists($dao_class, 'getSearchQueryComponents'))
			return [];
		
		if(!method_exists($search_class, 'getPrimaryKey'))
			return [];
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$join_sql .= sprintf(" LEFT JOIN context_link AS watchers ON (".
			"watchers.to_context = 'cerberusweb.contexts.worker' ".
			"AND watchers.from_context = %s ".
			"AND watchers.from_context_id = %s) ",
			$db->qstr($context),
			$search_class::getPrimaryKey()
		);
		
		$sql = "SELECT watchers.to_context_id as watcher_id, count(*) as hits ". //SQL_CALC_FOUND_ROWS
			$join_sql.
			$where_sql.
			"GROUP BY watcher_id ".
			"ORDER BY hits DESC ".
			"LIMIT 0,250 "
		;
		
		try {
			$results = $db->GetArrayReader($sql, 15000);
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$results = false;
		}

		return $results;
	}
	
	protected function _getSubtotalCountForWatcherColumn($context, $field_key) {
		$workers = DAO_Worker::getAll();
		
		$counts = [];
		$results = $this->_getSubtotalDataForWatcherColumn($context, $field_key);
		
		if(!is_array($results))
			return $counts;
		
		foreach($results as $result) {
			$watcher_id = intval($result['watcher_id']);
			$hits = $result['hits'];

			if(isset($workers[$watcher_id])) {
				$label = $workers[$watcher_id]->getName();
				$oper = DevblocksSearchCriteria::OPER_IN;
				$values = array('worker_id[]' => $watcher_id);
				
			} else {
				$label = '(nobody)';
				$oper = DevblocksSearchCriteria::OPER_IS_NULL;
				$values = array('');
			}
			
			if(!isset($counts[$watcher_id]))
				$counts[$watcher_id] = array(
					'hits' => $hits,
					'label' => $label,
					'filter' =>
						array(
							'field' => $field_key,
							'oper' => $oper,
							'values' => $values,
						),
					'children' => []
				);
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForContextLinkColumn($context, $field_key) {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;

		$params = $this->getParams();
		$param_results = C4_AbstractView::findParam($field_key, $params);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return [];
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return [];
		
		$has_context_already = false;
		
		if(!empty($param_results)) {
			// Did the worker add this filter?
			$param_results = C4_AbstractView::findParam($field_key, $this->getEditableParams());
			
			if(count($param_results) > 0) {
				$param_result = array_shift($param_results);
				
				if($param_result->operator == DevblocksSearchCriteria::OPER_IN)
				if(is_array($param_result->value)) {
					$context_pair = current($param_result->value);
					@$context_data = explode(':', $context_pair);
	
					if(1 == count($context_data)) {
						$has_context_already = $context_data[0];
						
					} elseif(2 == count($context_data)) {
						$has_context_already = $context_data[0];
	
						$new_params = array(
							$field_key => new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_IN, array($has_context_already))
						);
						
						$params = array_merge($params, $new_params);
					}
				}
			}
			
		} else {
			$new_params = array(
				$field_key => new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE),
			);
			
			$params = array_merge($params, $new_params);
		}
		
		if(!method_exists($dao_class, 'getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		if(empty($has_context_already)) {
			// This intentionally isn't constrained with a LIMIT
			$sql = sprintf("SELECT from_context AS link_from_context, count(*) AS hits FROM context_link WHERE to_context = %s AND to_context_id IN (%s) GROUP BY from_context ORDER BY hits DESC ",
				$db->qstr($context),
				(
					sprintf("SELECT %s.id ", $query_parts['primary_table']).
					$query_parts['join'] .
					$query_parts['where']
				)
			);
			
		} else {
			$sql = sprintf("SELECT from_context AS link_from_context, from_context_id AS link_from_context_id, count(*) AS hits FROM context_link WHERE to_context = %s AND to_context_id IN (%s) AND from_context = %s GROUP BY from_context, from_context_id ORDER BY hits DESC LIMIT 0,250 ",
				$db->qstr($context),
				(
					sprintf("SELECT %s.id ", $query_parts['primary_table']).
					$query_parts['join'] .
					$query_parts['where']
				),
				$db->qstr($has_context_already)
			);
			
		}
		
		try {
			$results = $db->GetArrayReader($sql, 15000);
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$results = false;
		}

		return $results;
	}
	
	protected function _getSubtotalCountForContextLinkColumn($context, $field_key) {
		$contexts = Extension_DevblocksContext::getAll(false);
		$counts = [];
		
		$results = $this->_getSubtotalDataForContextLinkColumn($context, $field_key);
		
		if(!is_array($results))
			return $counts;
		
		foreach($results as $result) {
			$hits = $result['hits'];
			
			if(isset($result['link_from_context_id'])) {
				$from_context = $result['link_from_context'];
				$from_context_id = $result['link_from_context_id'];
	
				if(!isset($contexts[$from_context]))
					continue;
				
				if(null == ($ext = Extension_DevblocksContext::get($from_context)))
					continue;
				
				if(false == ($meta = $ext->getMeta($from_context_id)) || empty($meta['name']))
					continue;
				
				$label = $meta['name'];
				$field_key = '*_context_link';
				$oper = DevblocksSearchCriteria::OPER_IN;
				$values = array('context_link[]' => $from_context . ':' . $from_context_id);
				
			} elseif(isset($result['link_from_context'])) {
				$from_context = $result['link_from_context'];
	
				if(!isset($contexts[$from_context]))
					continue;
				
				$label = $contexts[$from_context]->name;
				$field_key = '*_context_link';
				$oper = DevblocksSearchCriteria::OPER_IN;
				$values = array('context_link[]' => $from_context);
				
			} else {
				continue;
				
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $hits,
					'label' => $label,
					'filter' =>
						array(
							'field' => $field_key,
							'oper' => $oper,
							'values' => $values,
						),
					'children' => []
				);
		}
		
		return $counts;
	}
		
	protected function _getSubtotalDataForContextAndIdColumns($context, $field_key, $context_field, $context_id_field) {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;

		$params = $this->getParams();
		$param_results = C4_AbstractView::findParam($field_key, $params);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return [];
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return [];
		
		$has_context_already = false;
		
		if(!empty($param_results)) {
			// Did the worker add this filter?
			$param_results = C4_AbstractView::findParam($field_key, $this->getEditableParams());
			
			if(count($param_results) > 0) {
				$param_result = array_shift($param_results);
				
				if($param_result->operator == DevblocksSearchCriteria::OPER_IN)
				if(is_array($param_result->value)) {
					$context_pair = current($param_result->value);
					@$context_data = explode(':', $context_pair);
	
					if(1 == count($context_data)) {
						$has_context_already = $context_data[0];
						
					} elseif(2 == count($context_data)) {
						$has_context_already = $context_data[0];
	
						$new_params = array(
							$field_key => new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_IN, array($has_context_already))
						);
						
						$params = array_merge($params, $new_params);
					}
				}
			}
		}
		
		if(!method_exists($dao_class, 'getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];

		if(empty($has_context_already)) {
			// This intentionally isn't constrained with a LIMIT
			$sql = sprintf("SELECT %s AS context_field, count(*) AS hits %s %s GROUP BY context_field ORDER BY hits DESC ",
				$db->escape($context_field),
				$join_sql,
				$where_sql
			);
			
		} else {
			$sql = sprintf("SELECT %s AS context_field, %s AS context_id_field, count(*) AS hits %s %s GROUP BY context_field, context_id_field ORDER BY hits DESC ",
				$db->escape($context_field),
				$db->escape($context_id_field),
				$join_sql,
				$where_sql
			);
		}
		
		try {
			$results = $db->GetArrayReader($sql, 15000);
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$results = false;
		}

		return $results;
	}
	
	protected function _getSubtotalCountForContextAndIdColumns($context, $field_key, $context_field, $context_id_field, $filter_field='context_link[]') {
		$contexts = Extension_DevblocksContext::getAll(false);
		$counts = [];
		
		$results = $this->_getSubtotalDataForContextAndIdColumns($context, $field_key, $context_field, $context_id_field);
		
		if(!is_array($results))
			return $counts;
		
		foreach($results as $result) {
			$hits = $result['hits'];
			
			if(isset($result['context_id_field'])) {
				$from_context = $result['context_field'];
				$from_context_id = $result['context_id_field'];
	
				if(!isset($contexts[$from_context]))
					continue;
				
				if(null == ($ext = Extension_DevblocksContext::get($from_context)))
					continue;
				
				if(!empty($from_context_id)) {
					if(false == ($meta = $ext->getMeta($from_context_id)) || empty($meta['name']))
						continue;
					
					$label = $meta['name'];
					
				} else {
					$label = $ext->manifest->name;
					
				}

				$oper = DevblocksSearchCriteria::OPER_IN;
				$values = array($filter_field => $from_context . ':' . $from_context_id);
				
			} elseif(isset($result['context_field'])) {
				$from_context = $result['context_field'];
	
				if(!isset($contexts[$from_context]))
					continue;
				
				$label = $contexts[$from_context]->name;
				$oper = DevblocksSearchCriteria::OPER_IN;
				$values = array($filter_field => $from_context);
				
			} else {
				continue;
				
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $hits,
					'label' => $label,
					'filter' =>
						array(
							'field' => $field_key,
							'oper' => $oper,
							'values' => $values,
						),
					'children' => []
				);
		}
		
		return $counts;
	}
	
	protected function _getSubtotalCountForHasFieldsetColumn($context, $field_key) {
		$counts = [];
		
		$custom_fieldsets = DAO_CustomFieldset::getAll();
		$data = $this->_getSubtotalDataForHasFieldsetColumn($context, $context);
		
		foreach($data as $row) {
			@$custom_fieldset = $custom_fieldsets[$row['custom_fieldset_id']];
			
			if(empty($custom_fieldset))
				continue;
			
			$counts[$custom_fieldset->id] = array(
				'hits' => $row['hits'],
				'label' => $custom_fieldset->name,
				'filter' => array(
					'field' => $field_key,
					'query' => 'fieldset:(id:['. $custom_fieldset->id . '])',
				),
				'children' => [],
			);
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForHasFieldsetColumn($dao_class, $context) {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;
		$params = $this->getParams();

		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return [];
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return [];
		
		if(!method_exists($dao_class, 'getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$sql = sprintf("SELECT custom_fieldset_id, COUNT(*) AS hits FROM context_to_custom_fieldset WHERE context = %s AND context_id IN (%s) GROUP BY custom_fieldset_id ORDER BY hits DESC",
			$db->qstr($context),
			(
				sprintf("SELECT %s.id ", $query_parts['primary_table']).
				$query_parts['join'] .
				$query_parts['where']
			)
		);
		
		try {
			$results = $db->GetArrayReader($sql, 15000);
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$results = false;
		}
		
		return $results;
	}
	
	protected function _getSubtotalCountForCustomColumn($context, $field_key) {
		$db = DevblocksPlatform::services()->database();
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = [];
		$custom_fields = DAO_CustomField::getAll();
		$columns = $this->view_columns;
		$params = $this->getParams();

		$field_id = substr($field_key, 3);
		
		// If the custom field id is invalid, abort.
		if(!isset($custom_fields[$field_id]))
			return [];

		// Load the custom field
		$cfield = $custom_fields[$field_id];

		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return [];
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			return [];
		
		if(false == ($search_class = $context_ext->getSearchClass()))
			return [];
		
		$cfield_select_sql = null;
		
		$is_multiple_value_cfield = Model_CustomField::hasMultipleValues($cfield->type);
		
		$cfield_key = $search_class::getCustomFieldContextWhereKey($cfield->context);
		
		if($cfield_key) {
			if($is_multiple_value_cfield) {
				/** @noinspection SqlResolve */
				$cfield_select_sql .= sprintf("SELECT COUNT(field_value) AS hits, field_value AS %s FROM %s WHERE context=%s AND context_id IN (%s) AND field_id=%d GROUP BY %s ORDER BY hits DESC",
					$field_key,
					DAO_CustomFieldValue::getValueTableName($field_id),
					Cerb_ORMHelper::qstr($cfield->context),
					'%s',
					$field_id,
					$field_key
				);
				
			} else {
				$cfield_select_sql .= sprintf("(SELECT field_value FROM %s WHERE context=%s AND context_id=%s AND field_id=%d%s)",
					DAO_CustomFieldValue::getValueTableName($field_id),
					Cerb_ORMHelper::qstr($cfield->context),
					$cfield_key,
					$field_id,
					' LIMIT 1'
				);
			}
		}
		
		// ... and that the DAO object is valid
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return [];

		// Construct the shared query components
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
			
		switch($cfield->type) {
			
			case Model_CustomField::TYPE_CHECKBOX:
				$select = sprintf(
					"SELECT COUNT(*) AS hits, %s AS %s ",
					$cfield_select_sql,
					$field_key
				);
				
				$sql =
					$select.
					$join_sql.
					$where_sql.
					sprintf(
						"GROUP BY %s ",
						$field_key
					).
					"ORDER BY hits DESC "
				;
				
				try {
					$results = $db->GetArrayReader($sql, 15000);
				} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
					$results = false;
				}
		
				if(is_array($results))
				foreach($results as $result) {
					$label = '';
					$oper = DevblocksSearchCriteria::OPER_EQ;
					$values = null;
					
					switch($result[$field_key]) {
						case '':
							$label = '(no data)';
							$oper = DevblocksSearchCriteria::OPER_IS_NULL;
							break;
						case '0':
							$label = $translate->_('common.no');
							$values = array('value' => $result[$field_key]);
							break;
						case '1':
							$label = $translate->_('common.yes');
							$values = array('value' => $result[$field_key]);
							break;
					}
					
					$counts[$result[$field_key]] = array(
						'hits' => $result['hits'],
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => $oper,
								'values' => $values,
							),
					);
				}
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_LINK:
			case Model_CustomField::TYPE_LIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
				if($is_multiple_value_cfield) {
					$subquery_sql =
						sprintf("SELECT %s ", $cfield_key).
						$join_sql.
						$where_sql
					;
					
					$sql = sprintf($cfield_select_sql, $subquery_sql);
					
				} else {
					$select = sprintf(
						"SELECT COUNT(*) AS hits, %s AS %s ",
						$cfield_select_sql,
						$field_key
					);
					
					$sql =
						$select.
						$join_sql.
						$where_sql.
						sprintf(
							"GROUP BY %s ",
							$field_key
						).
						"ORDER BY hits DESC ".
						"LIMIT 20 "
					;
				}
			
				try {
					$results = $db->GetArrayReader($sql, 15000);
				} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
					$results = false;
				}
				
				if(is_array($results))
				foreach($results as $result) {
					$label = '';
					$oper = DevblocksSearchCriteria::OPER_IN;
					$values = '';

					if(!empty($result[$field_key])) {
						$label = $result[$field_key];
						switch($cfield->type) {
							case Model_CustomField::TYPE_DROPDOWN:
							case Model_CustomField::TYPE_MULTI_CHECKBOX:
								$oper = DevblocksSearchCriteria::OPER_IN;
								$values = array('options[]' => $label);
								break;
							default:
								$oper = DevblocksSearchCriteria::OPER_EQ;
								$values = array('value' => $label);
								break;
						}
					}
					
					if(empty($label)) {
						$label = '(no data)';
						$oper = DevblocksSearchCriteria::OPER_IS_NULL;
						$values = array('value' => null);
					}
					
					$counts[$result[$field_key]] = array(
						'hits' => $result['hits'],
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => $oper,
								'values' => $values,
							),
					);
				}
				
				// Special handling of count results
				switch($cfield->type) {
					// For custom record links, we need to change the labels and filters
					case Model_CustomField::TYPE_LINK:
						if(false == ($context = $cfield->params['context']))
							break;
							
						if(false == ($context_ext = Extension_DevblocksContext::get($context)))
							break;
						
						$dao_class = $context_ext->getDaoClass();
						$ids = array_column($counts, 'label');
						$models = $dao_class::getIds($ids);
						$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context);
						
						// Rewrite the results
						$counts = array_map(function($v) use ($dicts) {
							if(array_key_exists($v['label'], $dicts)) {
								$dict = $dicts[$v['label']];
								$v['label'] = $dict->_label;
								$v['filter']['oper'] = DevblocksSearchCriteria::OPER_EQ;
								$v['filter']['values'] = ['context_id' => $dict->id];
								return $v;
							}
							
							return $v;
						}, $counts);
						
						break;
				}
				break;
				
			case Model_CustomField::TYPE_WORKER:
				$workers = DAO_Worker::getAll();
				
				$sql =
					sprintf(
						"SELECT COUNT(*) AS hits, %s AS %s ", //SQL_CALC_FOUND_ROWS
						$cfield_select_sql,
						$field_key
					).
					$join_sql.
					$where_sql.
					sprintf(
						"GROUP BY %s ",
						$field_key
					).
					"ORDER BY hits DESC ".
					"LIMIT 20 "
				;
				
				try {
					$results = $db->GetArrayReader($sql, 15000);
				} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
					$results = false;
				}
		
				if(is_array($results))
				foreach($results as $result) {
					$label = '';
					$oper = DevblocksSearchCriteria::OPER_EQ;
					$values = '';

					if(!empty($result[$field_key])) {
						$worker_id = $result[$field_key];
						if(isset($workers[$worker_id])) {
							$label = $workers[$worker_id]->getName();
							$oper = DevblocksSearchCriteria::OPER_IN;
							$values = array('worker_id[]' => $worker_id);
						}
					}
					
					if(empty($label)) {
						$counts[$result[$field_key]] = array(
							'hits' => $result['hits'],
							'label' => '(nobody)',
							'filter' =>
								[
									//'query' => sprintf('%s:null', $field_key),
									'field' => $field_key,
									'oper' => DevblocksSearchCriteria::OPER_IS_NULL,
									'values' => true,
								],
						);
						
					} else {
						$counts[$result[$field_key]] = array(
							'hits' => $result['hits'],
							'label' => $label,
							'filter' =>
								[
									'field' => $field_key,
									'oper' => $oper,
									'values' => $values,
								],
						);
					}
				}
				break;
				
		}
		
		return $counts;
	}
	
	public static function _doBulkSetCustomFields($context, $custom_fields, $ids) {
		$fields = DAO_CustomField::getAll();
		
		$custom_fieldset_ids = array_unique(array_column(array_intersect_key($fields, $custom_fields), 'custom_fieldset_id'));
		
		// Link any custom fieldsets we bulk update
		if($custom_fieldset_ids) {
			DAO_CustomFieldset::addToContext($custom_fieldset_ids, $context, $ids);
		}
		
		if(is_array($custom_fields))
		foreach($custom_fields as $cf_id => $params) {
			if(!is_array($params) || !array_key_exists('value', $params))
				continue;
			
			if(false == ($cf_field = @$fields[$cf_id]))
				continue;
			
			$cf_val = $params['value'];
			
			// Data massaging
			switch($cf_field->type) {
				case Model_CustomField::TYPE_DATE:
					$cf_val = intval(@strtotime($cf_val));
					break;
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_NUMBER:
					$cf_val = (0==strlen($cf_val)) ? '' : intval($cf_val);
					break;
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					// Remove empty values
					if(is_array($cf_val)) {
						$cf_val = array_filter($cf_val, fn($v) => 0 != strlen($v));
					}
					break;
			}
			
			if(false != ($cf_type = $cf_field->getTypeExtension())) {
				if(is_array($ids))
				foreach($ids as $id)
					$cf_type->setFieldValue($cf_field, $context, $id, $cf_val);
				
			} else {
				// If multi-selection types, handle delta changes
				if(Model_CustomField::hasMultipleValues($cf_field->type)) {
					if(is_array($cf_val))
					foreach($cf_val as $val) {
						if(DevblocksPlatform::strStartsWith($val,['+','-'])) {
							$op = substr($val,0,1);
							$val = substr($val,1);
						} else {
							$op = '+';
						}
					
						if(is_array($ids))
						foreach($ids as $id) {
							if($op=='-') {
								DAO_CustomFieldValue::unsetFieldValue($context, $id, $cf_id, $val);
							} else {
								DAO_CustomFieldValue::setFieldValue($context, $id, $cf_id, $val, true);
							}
						}
					}
					
				// Otherwise, set/unset as a single field
				} else {
					if(is_array($ids))
					foreach($ids as $id) {
						if(0 != strlen($cf_val))
							DAO_CustomFieldValue::setFieldValue($context,$id,$cf_id,$cf_val);
						else
							DAO_CustomFieldValue::unsetFieldValue($context,$id,$cf_id);
					}
				}
			}
		}
	}
	
	public static function _doBulkScheduleBehavior($context, array $params, array $ids) {
		if(!isset($params) || !is_array($params))
			return false;
			
		$behavior_id = $params['id'] ?? null;
		@$behavior_when = strtotime($params['when']) or time();
		@$behavior_params = isset($params['params']) ? $params['params'] : [];
		
		if(empty($behavior_id))
			return false;
		
		foreach($ids as $batch_id) {
			DAO_ContextScheduledBehavior::create(array(
				DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
				DAO_ContextScheduledBehavior::CONTEXT => $context,
				DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
				DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
				DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
			));
		}
		
		return true;
	}
	
	public static function _doBulkChangeWatchers($context, array $params, array $ids) {
		if(!isset($params) || !is_array($params))
			return false;
		
		foreach($ids as $batch_id) {
			if(isset($params['add']) && is_array($params['add']))
				CerberusContexts::addWatchers($context, $batch_id, $params['add']);
			
			if(isset($params['remove']) && is_array($params['remove']))
				CerberusContexts::removeWatchers($context, $batch_id, $params['remove']);
		}
	}
	
	public static function _doBulkBroadcast($context, array $params, array $ids) {
		if(empty($params) || empty($ids))
			return false;
		
		try {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			if(
				!isset($params['to'])
				|| empty($params['to'])
				|| !isset($params['worker_id'])
				|| empty($params['worker_id'])
				|| !isset($params['subject'])
				|| empty($params['subject'])
				|| !isset($params['message'])
				|| empty($params['message'])
				)
				throw new Exception("Missing parameters for broadcast.");

			$is_queued = (isset($params['is_queued']) && $params['is_queued']) ? true : false;
			$status_id = intval(@$params['status_id']);
			
			$models = CerberusContexts::getModels($context, $ids);
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context, array('custom_'));
			
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			/* @var $context_ext IDevblocksContextBroadcast */
			if(!($context_ext instanceof IDevblocksContextBroadcast))
				return;
			
			$message_properties = [
				'worker_id' => ($params['worker_id'] ?? null) ?: 0,
				'subject' => $params['subject'],
				'content' => $params['message'],
				'content_format' => ($params['format'] ?? null) ?: '',
				'group_id' => ($params['group_id'] ?? null) ?: 0,
				'bucket_id' => ($params['bucket_id'] ?? null) ?: 0,
				'html_template_id' => ($params['html_template_id'] ?? null) ?: 0,
				'file_ids' => ($params['file_ids'] ?? null) ?: [],
			];
			
			if(is_array($dicts))
			foreach($dicts as $id => $dict) {
				try {
					if(false == ($recipients = $context_ext->broadcastRecipientFieldsToEmails($params['to'], $dict)))
						continue;
					
					$recipients = DAO_Address::lookupAddresses($recipients, true);
					
					if(is_array($recipients))
					foreach($recipients as $model) {
						// Skip banned or defunct recipients
						if($model->is_banned || $model->is_defunct)
							continue;
						
						// Remove an existing contact
						$dict->scrubKeys('broadcast_email_');
						
						// Prime the new contact
						$dict->broadcast_email__context = CerberusContexts::CONTEXT_ADDRESS;
						$dict->broadcast_email_id = $model->id;
						$dict->broadcast_email_;
						
						// Templates
						$subject = $tpl_builder->build($message_properties['subject'], $dict);
						$body = $tpl_builder->build($message_properties['content'], $dict);
						
						$json_params = array(
							'to' => $dict->broadcast_email__label,
							'group_id' => $message_properties['group_id'],
							'bucket_id' => $message_properties['bucket_id'],
							'status_id' => $status_id,
							'subject' => $subject,
							'content' => $body,
							'worker_id' => $message_properties['worker_id'],
							'is_broadcast' => 1,
							'context_links' => [
								[$context, $id],
							],
						);
						
						if(array_key_exists('content_format', $message_properties))
							$json_params['format'] = $message_properties['content_format'];
						
						if(array_key_exists('html_template_id', $message_properties))
							$json_params['html_template_id'] = intval($message_properties['html_template_id']);
						
						if(array_key_exists('file_ids', $message_properties))
							$json_params['file_ids'] = $message_properties['file_ids'];
						
						$fields = array(
							DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
							DAO_MailQueue::TICKET_ID => 0,
							DAO_MailQueue::WORKER_ID => $message_properties['worker_id'],
							DAO_MailQueue::UPDATED => time(),
							DAO_MailQueue::HINT_TO => $dict->broadcast_email__label,
							DAO_MailQueue::NAME => $subject,
							DAO_MailQueue::PARAMS_JSON => json_encode($json_params),
						);
						
						if($is_queued) {
							$fields[DAO_MailQueue::IS_QUEUED] = 1;
						}
						
						DAO_MailQueue::create($fields);
					}
					
				} catch (Exception $e) {
					return false;
				}
			}
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
};

interface IAbstractView_QuickSearch {
	function getQuickSearchFields();
	function getParamFromQuickSearchFieldTokens($field, $tokens);
};

interface IAbstractView_Subtotals {
	function getSubtotalCounts($column);
	function getSubtotalFields();
};

class CerbQuickSearchLexer {
	private static function _recurse($token, $keys, $node_callback, $after_children_callback=null) {
		if(!is_object($token))
			return;
		
		if(!is_callable($node_callback))
			return;
		
		if(empty($keys)) {
			$node_callback($token);
		} else {
			if(!is_array($keys))
				$keys = [$keys];
			
			if(in_array($token->type, $keys))
				$node_callback($token);
		}

		if(isset($token->children) && is_array($token->children))
		foreach($token->children as $child)
			self::_recurse($child, $keys, $node_callback, $after_children_callback);
		
		if(is_callable($after_children_callback))
			$after_children_callback($token);
	}
	
	static function buildParams($token, &$parent) {
		if(!is_object($token))
			return;
		
		switch($token->type) {
			case 'T_GROUP':
				// Sanitize
				switch($token->value) {
					case DevblocksSearchCriteria::GROUP_OR:
					case DevblocksSearchCriteria::GROUP_OR_NOT:
					case DevblocksSearchCriteria::GROUP_AND:
					case DevblocksSearchCriteria::GROUP_AND_NOT:
						break;
						
					default:
						$token->value = DevblocksSearchCriteria::GROUP_AND;
						break;
				}
				
				$param = [
					$token->value,
				];
				foreach($token->children as $child)
					self::buildParams($child, $param);
				
				if(!is_array($parent)) {
					$parent = $param;
				} else {
					$parent[] = $param;
				}
				break;
				
			case 'T_FIELD':
				$param = new DevblocksSearchCriteria(null, null);
				$param->key = $token->value;
				$param->tokens = $token->children;
				$parent[] = $param;
				break;
		}
	}
	
	static function getFieldsFromQuery($query, array $bindings=[]) {
		$tokens = [];
		
		// Extract double-quoted literals text
		
		$quotes = [];
		$start = 0;
		
		while(false !== ($from = strpos($query, '"', $start))) {
			if(false === ($to = strpos($query, '"', $from+1)))
				break;
			
			$idx = count($quotes);
			$cut = substr($query, $from, $to-$from+1);
			$quotes[] = trim($cut,'"');
			$query = str_replace($cut, ' <$Q:'.$idx.'> ', $query);
			$start = $from;
		}
		
		// Tokenize symbols
		
		$tokenize_symbol_map = [
			' OR ' => ' <$OR> ',
			' AND ' => ' <$AND> ',
			'!(' => ' <$PON> ',
			':all(' => ': <$POA> ',
			':!' => ': <$NOT> ',
			'(' => ' <$PO> ',
			')' => ' <$PC> ',
			'[' => ' <$BO> ',
			']' => ' <$BC> ',
		];
		
		$query = str_replace(
			array_keys($tokenize_symbol_map),
			$tokenize_symbol_map,
			$query
		);
		
		// Cap at two continuous whitespace chars
		
		$query = preg_replace('#\s{2,}#', '  ', $query);
		
		// Tokens for lexer
		$token_map = array(
			'[a-zA-Z0-9\_\.]+\:' => 'T_FIELD',
			'\s+' => 'T_WHITESPACE',
			'[^\s]+' => 'T_TEXT',
		);
		
		$token_offsets = array_values($token_map);
		
		// Compile the regexp
		$regexp = '((' . implode(')|(', array_keys($token_map)) . '))Ax';
		
		$offset = 0;
		$matches = [];
		
		while(isset($query[$offset])) {
			if(!preg_match($regexp, $query, $matches, 0, $offset))
				break;
			
			if('' == $matches[0])
				break;
			
			$match = $matches[0];
			array_shift($matches);
			
			if(false === ($idx = array_search($match, $matches)))
				break;
			
			if(!isset($token_offsets[$idx]))
				break;
			
			$token_type = $token_offsets[$idx];
			$token_value = $match;
			
			switch($token_type) {
				case 'T_FIELD':
					$token_value = rtrim($match, ':');
					break;
					
				case 'T_WHITESPACE':
					$token_type = null;
					break;
					
				case 'T_TEXT':
					if($match == '!') {
						$token_type = 'T_NOT';
						
					} elseif (DevblocksPlatform::strStartsWith($match, '<$Q:')) {
						$idx = intval(substr($match,4));
						$token_type = 'T_QUOTED_TEXT';
						$token_value = $quotes[$idx];
						
					} else {
						switch($match) {
							case '<$PO>':
								$token_type = 'T_PARENTHETICAL_OPEN';
								$token_value = '(';
								break;
							case '<$PON>':
								$token_type = 'T_PARENTHETICAL_OPEN_NEG';
								$token_value = '!(';
								break;
							case '<$POA>':
								$token_type = 'T_PARENTHETICAL_OPEN_ALL';
								$token_value = 'all(';
								break;
							case '<$PC>':
								$token_type = 'T_PARENTHETICAL_CLOSE';
								$token_value = ')';
								break;
							case '<$BO>':
								$token_type = 'T_BRACKET_OPEN';
								$token_value = '[';
								break;
							case '<$BC>':
								$token_type = 'T_BRACKET_CLOSE';
								$token_value = ']';
								break;
							case '<$AND>':
								$token_type = 'T_BOOL';
								$token_value = 'AND';
								break;
							case '<$OR>':
								$token_type = 'T_BOOL';
								$token_value = 'OR';
								break;
							case '<$NOT>':
								$token_type = 'T_NOT';
								$token_value = null;
								break;
						}
					}
					break;
			}
			
			if($token_type)
				$tokens[] = new CerbQuickSearchLexerToken($token_type, $token_value);
			
			$offset += strlen($match);
		}
		
		// Bracket arrays
		
		reset($tokens);
		$start = null;
		while($token = current($tokens)) {
			switch($token->type) {
				case 'T_BRACKET_OPEN':
					$start = key($tokens);
					break;
					
				case 'T_BRACKET_CLOSE':
					if($start) {
						$len = key($tokens)-$start+1;
						$cut = array_splice($tokens, $start, $len, array([]));
						
						array_shift($cut);
						array_pop($cut);
						
						$tokens[$start] = new CerbQuickSearchLexerToken('T_ARRAY', null, $cut);
						$start = null;
					}
					break;
			}
			
			next($tokens);
		}
		
		// Group parentheticals
		
		reset($tokens);
		$opens = [];
		
		while($token = current($tokens)) {
			switch($token->type) {
				case 'T_PARENTHETICAL_OPEN_ALL':
				case 'T_PARENTHETICAL_OPEN_NEG':
				case 'T_PARENTHETICAL_OPEN':
					$opens[] = key($tokens);
					next($tokens);
					break;
					
				case 'T_PARENTHETICAL_CLOSE':
					$start = intval(array_pop($opens));
					$len = key($tokens)-$start+1;
					$cut = array_splice($tokens, $start, $len, array([]));
					
					// Remove the wrappers
					$open_token = array_shift($cut);
					array_pop($cut);
					
					$value = null;
					$token_params = [];
					
					if($open_token->type == 'T_PARENTHETICAL_OPEN_NEG') {
						$value = '!';
						$token_params['subtype'] = '!';
					} else if($open_token->type == 'T_PARENTHETICAL_OPEN_ALL') {
						$value = 'all';
						$token_params['subtype'] = 'all';
					}
					
					$tokens[$start] = new CerbQuickSearchLexerToken('T_GROUP', $value, $cut, $token_params);

					reset($tokens);
					break;
					
				default:
					next($tokens);
					break;
			}
		}
		
		$tokens = new CerbQuickSearchLexerToken('T_GROUP', null, $tokens);
		
		// Arrays
		
		self::_recurse($tokens, 'T_ARRAY', function($token) {
			$elements = [];
			
			self::_recurse($token, ['T_TEXT','T_QUOTED_TEXT'], function($token) use (&$elements) {
				// If quoted, preserve commas and everything
				if($token->type == 'T_QUOTED_TEXT') {
					$elements = array_merge($elements, [$token->value]);
					
				} else {
					$elements = array_merge($elements, DevblocksPlatform::parseCsvString($token->value));
				}
			});
			
			$token->value = $elements;
			$token->children = [];
		});
		
		// Recurse
		
		self::_recurse($tokens, '', function($token) {
			$append_to = null;
			
			foreach($token->children as $k => $child) {
				if(!is_object($child))
					continue;
				
				switch($child->type) {
					case 'T_FIELD':
						$append_to = $k;
						break;
						
					case 'T_ARRAY':
					case 'T_GROUP':
						if(!is_null($append_to)) {
							$token->children[$append_to]->children[] = $child;
							unset($token->children[$k]);
						}
						$append_to = null;
						break;
						
					case 'T_NOT':
						if(!is_null($append_to)) {
							$token->children[$append_to]->children[] = $child;
							unset($token->children[$k]);
						}
						break;
						
					case 'T_TEXT':
					case 'T_QUOTED_TEXT':
						if(!is_null($append_to)) {
							$token->children[$append_to]->children[] = $child;
							unset($token->children[$k]);
							$append_to = null;
						}
						break;
						
					default:
						$append_to = null;
						break;
				}
			}
		});
		
		// Move any unattached text into a fulltext field
		self::_recurse($tokens, 'T_GROUP', function($token) {
			$field = null;
			
			foreach($token->children as $k => $child) {
				if(!is_object($child))
					continue;
				
				switch($child->type) {
					case 'T_QUOTED_TEXT':
					case 'T_TEXT':
						if(is_null($field)) {
							$field = new CerbQuickSearchLexerToken('T_FIELD', 'text');
							$token->children[] = $field;
						}
							
						$field->children[] = $child;
						unset($token->children[$k]);
						break;
						
					default:
						$field = null;
						break;
				}
			}
		});
		
		// Sort out the boolean mode of each group
		self::_recurse($tokens, 'T_GROUP', function($token) {
			// [TODO] Operator precedence AND -> OR
			// [TODO] Handle 'a OR b AND c'
			
			$all = ($token->params['subtype'] ?? '') == 'all';
			$not = ($token->params['subtype'] ?? '') == '!';
			$token->value = null;
			
			foreach($token->children as $k => $child) {
				if(!is_object($child))
					continue;
				
				switch($child->type) {
					case 'T_BOOL':
						if(empty($token->value)) {
							// [TODO] This should write a group like 'NOT (a AND b)' instead
							
							switch($child->value) {
								case 'OR':
									$oper = sprintf('%sOR%s',
										$all ? 'ALL ': '',
										$not ? ' NOT' : ''
									);
									break;
									
								default:
									$oper = sprintf('%sAND%s',
										$all ? 'ALL ': '',
										$not ? ' NOT' : ''
									);
									break;
							}
							
							$token->value = $oper;
						}
						
						unset($token->children[$k]);
						break;
				}
			}
			
			if(empty($token->value))
				$token->value = sprintf('%sAND%s',
					$all ? 'ALL ' : '',
					$not ? ' NOT' : ''
				);
		});
		
		// Sort out the boolean mode of each group
		if($bindings) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$lexer = [
				'tag_variable'  => ['${', '}'],
				'tag_comment'   => ['${#', '#}'],
				'tag_block'     => ['${%', '%}'],
				'interpolation' => ['$#{', '}'],
			];
			
			$token_dict = DevblocksDictionaryDelegate::instance($bindings);
			
			self::_recurse($tokens, ['T_TEXT', 'T_QUOTED_TEXT'], function (CerbQuickSearchLexerToken $token) use ($tpl_builder, $token_dict, $lexer) {
				$was_value = $token->value;
				$token->value = $tpl_builder->build($token->value, $token_dict, $lexer);
				
				// If we changed the value, force it to quoted text
				if($was_value != $token->value)
					$token->type = 'T_QUOTED_TEXT';
			});
			
			self::_recurse($tokens, ['T_ARRAY'], function (CerbQuickSearchLexerToken $token) use ($tpl_builder, $token_dict, $lexer) {
				if(1 == count($token->value) && false !== strpos($token->value[0] ?? '', '${')) {
					$token->value = DevblocksPlatform::parseCsvString($tpl_builder->build($token->value[0], $token_dict, $lexer));
				}
			});
		}
		
		$params = null;
		self::buildParams($tokens, $params);
		
		// Remove the outer grouping if it's not necessary
		if($params[0] == 'AND') {
			array_shift($params);
		} else {
			$params = [$params];
		}

		return $params;
	}
	
	static function getOperStringFromTokens($tokens, &$oper, &$value) {
		self::_getOperValueFromTokens($tokens, $oper, $value);
		
		$not = ($oper == DevblocksSearchCriteria::OPER_NIN);
		
		if(0 == count($value)) {
			$oper = $not ? DevblocksSearchCriteria::OPER_IS_NOT_NULL : DevblocksSearchCriteria::OPER_IS_NULL;
			$value = null;
			
		} else {
			$oper = $not ? DevblocksSearchCriteria::OPER_NEQ : DevblocksSearchCriteria::OPER_EQ;
			$value = array_shift($value);
		}
		
		return true;
	}
	
	static function getOperArrayFromTokens($tokens, &$oper, &$value) {
		return self::_getOperValueFromTokens($tokens, $oper, $value);
	}
	
	static function _getOperValueFromTokens($tokens, &$oper, &$value) {
		if(!is_array($tokens))
			return false;
		
		$not = false;
		$oper = DevblocksSearchCriteria::OPER_IN;
		$value = [];
		
		foreach($tokens as $token) {
			if(!($token instanceof CerbQuickSearchLexerToken))
				continue;
			
			switch($token->type) {
				case 'T_NOT':
					$not = !$not;
					break;
					
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					$oper = $not ? DevblocksSearchCriteria::OPER_NIN : DevblocksSearchCriteria::OPER_IN;
					$value = [$token->value];
					break;
					
				case 'T_ARRAY':
					$oper = $not ? DevblocksSearchCriteria::OPER_NIN : DevblocksSearchCriteria::OPER_IN;
					$value = $token->value;
					break;
			}
		}
		
		return true;
	}
	
	static function getTokensAsQuery($tokens) {
		$string = null;
		$group_stack = [];
		
		$node_callback = function($token) use (&$string, &$group_stack) {
			switch($token->type) {
				case 'T_NOT':
					$string .= '!';
					break;
					
				case 'T_GROUP':
					if($group_stack && DevblocksPlatform::strEndsWith($string,[')'])) {
						$group_oper = end($group_stack);
						
						if('AND' != $group_oper) {
							$string .= ' ' . $group_oper;
						}
						
						$string .= ' ' ;
					}
					
					$string .= sprintf('%s(', $token->params['subtype'] ?? null);
					
					// The separators are always OR/AND, we use NOT for the overall group in !(
					switch($token->value) {
						case DevblocksSearchCriteria::GROUP_OR:
						case DevblocksSearchCriteria::GROUP_OR_NOT:
							$oper = DevblocksSearchCriteria::GROUP_OR;
							break;
						default:
							$oper = DevblocksSearchCriteria::GROUP_AND;
							break;
					}
					
					$group_stack[] = $oper;
					break;
					
				case 'T_ARRAY':
					$values = array_map(function($value) {
						if(is_numeric($value)) {
							return $value;
							
						} else { // Quote strings
							return '"' . $value . '"';
						}
					}, $token->value);
					
					$string .= '[' . implode(',', $values) . ']';
					break;
					
				case 'T_QUOTED_TEXT':
					$string .= '"' . $token->value;
					break;
					
				case 'T_TEXT':
					if($string && !DevblocksPlatform::strEndsWith($string, ['(',':']))
						$string .= ' ';
						
					$string .= $token->value;
					break;
					
				case 'T_FIELD':
					// AND/OR separators
					if($string && !DevblocksPlatform::strEndsWith($string, ['(',':']) && end($group_stack)) {
						if(!DevblocksPlatform::strEndsWith($string, [' ']))
							$string .= ' ';
						
						$group_oper = end($group_stack);
						
						if('AND' != $group_oper)
							$string .= $group_oper;
					}
					
					if(!DevblocksPlatform::strEndsWith($string, [' ','(']))
						$string .= ' ';
					
					switch($token->value) {
						case 'text':
							break;
							
						default:
							$string .= $token->value . ':';
							break;
					}
					break;
			}
		};
		
		$after_children_callback = function($token) use (&$string, &$group_stack) {
			switch($token->type) {
				case 'T_GROUP':
					$string = rtrim($string) . ')';
					array_pop($group_stack);
					break;
					
				case 'T_ARRAY':
					break;
					
				case 'T_QUOTED_TEXT':
					$string .= '"';
					break;
					
				case 'T_TEXT':
					break;
					
				case 'T_FIELD':
					$string .= ' ';
					break;
			}
		};
		
		if(is_array($tokens) && isset($tokens[0]))
			self::_recurse($tokens[0], null, $node_callback, $after_children_callback);
		
		return $string;
	}
	
	static function getDecimalTokensAsNumbers($tokens, $decimal_at=2) {
		if(!is_array($tokens))
			return false;
		
		$new_tokens = $tokens;
		$matches = [];
		
		foreach($new_tokens as &$token) {
			switch($token->type) {
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					$v = $token->value;
					$matches = [];
					
					if(preg_match('#^([\!\=\>\<]+)(.*)#', $v, $matches)) {
						$oper_hint = trim($matches[1]);
						$v = trim($matches[2]);
						
						$v = DevblocksPlatform::strParseDecimal($v, $decimal_at);
						
						$v = $oper_hint . $v;
						
					} else if(preg_match('#^(.*)?\.\.\.(.*)#', $v, $matches)) {
						$from = trim($matches[1]);
						$to = trim($matches[2]);
						
						$from = DevblocksPlatform::strParseDecimal($from, $decimal_at);
						$to = DevblocksPlatform::strParseDecimal($to, $decimal_at);
						
						$v = sprintf("%s...%s", $from, $to);
						
					} else {
						$v = DevblocksPlatform::strParseDecimal($v, $decimal_at);
					}
					
					$token->value = $v;
					break;
			}
		}
		
		return $new_tokens;
	}
	
	static function getHumanTimeTokensAsNumbers($tokens, $interval=1) {
		if(!is_array($tokens))
			return false;
		
		$new_tokens = $tokens;
			
		foreach($new_tokens as &$token) {
			switch($token->type) {
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					$v = $token->value;
					$matches = [];
					
					if(preg_match('#^([\!\=\>\<]+)(.*)#', $v, $matches)) {
						$oper_hint = trim($matches[1]);
						$v = trim($matches[2]);
						
						if(!is_numeric($v))
							$v = floor(DevblocksPlatform::strTimeToSecs($v) / $interval);
						
						$v = $oper_hint . $v;
						
					} else if(preg_match('#^(.*)?\.\.\.(.*)#', $v, $matches)) {
						$from = trim($matches[1]);
						$to = trim($matches[2]);
						
						if(!is_numeric($from))
							$from = floor(DevblocksPlatform::strTimeToSecs($from) / $interval);
						if(!is_numeric($to))
							$to = floor(DevblocksPlatform::strTimeToSecs($to) / $interval);
						
						$v = sprintf("%s...%s", $from, $to);
						
					} else {
						if(!is_numeric($v))
							$v = floor(DevblocksPlatform::strTimeToSecs($v) / $interval);
					}
					
					$token->value = $v;
					break;
			}
		}
		
		return $new_tokens;
	}
	
	public static function getFieldByKey(string $key, array $fields) {
		foreach($fields as $field) {
			if($field->key == $key)
				return $field;
		}
		
		return null;
	}
};

class CerbQuickSearchLexerToken {
	public $type = null;
	public $value = null;
	public $children = [];
	public $params = [];
	
	public function __construct($type, $value, $children=[], $params=[]) {
		$this->type = $type;
		$this->value = $value;
		$this->children = $children;
		$this->params = $params;
	}
};

/**
 * Used to persist a C4_AbstractView instance and not be encumbered by
 * classloading issues (out of the session) from plugins that might have
 * concrete AbstractView implementations.
 */
if(!class_exists('C4_AbstractViewModel')):
class C4_AbstractViewModel {
	public $class_name = '';

	public $id = '';
	public $name = "";
	public $options = [];
	public $is_ephemeral = 0;
	
	public $view_columns = [];
	public $columnsHidden = [];
	
	public $paramsQuery = '';
	public $paramsEditable = [];
	public $paramsDefault = [];
	public $paramsRequired = [];
	public $paramsRequiredQuery = '';
	public $paramsTimezone = '';

	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderTotal = true;
	public $renderSort = '';
	
	public $renderSubtotals = null;
	
	public $renderTemplate = null;
	
	public $placeholderLabels = [];
	public $placeholderValues = [];
	
	static function loadFromClass($class_name) {
		if(empty($class_name))
			return false;
		
		if(false == ($class = new $class_name))
			return false;
		
		$class->setAutoPersist(false);
		
		if(false == ($inst = C4_AbstractViewLoader::serializeAbstractView($class)))
			return false;
		
		return $inst;
	}
};
endif;

/**
 * This is essentially an AbstractView Factory
 */
class C4_AbstractViewLoader {
	/**
	 *
	 * @param string $view_id
	 * @param C4_AbstractViewModel $defaults
	 * @return C4_AbstractView | null
	 */
	static function getView($view_id, C4_AbstractViewModel $defaults=null) {
		$worker_id = 0;
		
		if(null !== ($active_worker = CerberusApplication::getActiveWorker()))
			$worker_id = $active_worker->id;

		// Check if we've ever persisted this view
		if($worker_id && false !== ($model = DAO_WorkerViewModel::getView($worker_id, $view_id))) {
			$view = self::unserializeAbstractView($model);
			return $view;
			
		} elseif(!empty($defaults) && $defaults instanceof C4_AbstractViewModel) {
			// Load defaults if they were provided
			if(null != ($view = self::unserializeAbstractView($defaults, false)))  {
				return $view;
			}
		}
		
		return NULL;
	}

	/**
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @param C4_AbstractView $view
	 */
	static function setView($view_id, C4_AbstractView $view) {
		$worker_id = 0;

		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		$worker_id = $active_worker->id;
		
		$exit_model = self::serializeAbstractView($view);
		
		// Is the view dirty? (do we need to persist it?)
		if(false != ($_init_checksum = ($view->_init_checksum ?? null))) {
			unset($view->_init_checksum);
			$_exit_checksum = sha1(serialize($exit_model));
			
			// If the view model is not dirty (we wouldn't end up changing anything in the database)
			if($_init_checksum == $_exit_checksum)
				return;
		}
		
		DAO_WorkerViewModel::setView($worker_id, $view_id, $exit_model);
	}

	static function deleteView($view_id, $worker_id=null) {
		$worker_id = 0;
		
		if(null !== ($active_worker = CerberusApplication::getActiveWorker()))
			$worker_id = $active_worker->id;

		DAO_WorkerViewModel::deleteView($worker_id, $view_id);
	}
	
	static function serializeAbstractView($view) {
		if(!$view instanceof C4_AbstractView)
			return NULL;
		
		$model = new C4_AbstractViewModel();
		
		$class_name = get_class($view);
		$model->class_name = $class_name;
		
		$parent = new $class_name(); /* @var $parent C4_AbstractView */
		$parent->setAutoPersist(false);

		$model->id = $view->id;
		$model->is_ephemeral = $view->is_ephemeral ? true : false;
		$model->name = $view->name;
		$model->options = $view->options;
		
		$model->view_columns = $view->view_columns;
		
		// Only persist hidden columns that are distinct from the parent (so we can inherit parent changes)
		$model->columnsHidden = array_diff($view->getColumnsHidden(), $parent->getColumnsHidden());
		
		$model->paramsQuery = $view->getParamsQuery();
		$model->paramsEditable = $view->getEditableParams();
		$model->paramsDefault = $view->getParamsDefault();
		$model->paramsRequired = $view->getParamsRequired();
		$model->paramsRequiredQuery = $view->getParamsRequiredQuery();
		$model->paramsTimezone = $view->getParamsTimezone();
		
		$model->renderPage = intval($view->renderPage);
		$model->renderLimit = intval($view->renderLimit);
		$model->renderTotal = intval($view->renderTotal);
		
		$model->renderSort = $view->getSorts();
		
		$model->renderSubtotals = $view->renderSubtotals;
		
		$model->renderTemplate = $view->renderTemplate;
		
		return $model;
	}

	static function unserializeAbstractView(C4_AbstractViewModel $model, $checksum=true) {
		if(!class_exists($model->class_name, true))
			return null;
		
		if(null == ($inst = new $model->class_name))
			return null;
		
		/* @var $inst C4_AbstractView */
		
		if(!empty($model->id))
			$inst->id = $model->id;
		if(null !== $model->is_ephemeral)
			$inst->is_ephemeral = $model->is_ephemeral ? true : false;
		if(!empty($model->name))
			$inst->name = $model->name;
		
		if(is_array($model->options) && !empty($model->options))
			$inst->options = $model->options;
		
		if(is_array($model->view_columns)) 
			$inst->view_columns = $model->view_columns;
		if(is_array($model->columnsHidden))
			$inst->addColumnsHidden($model->columnsHidden, false);
		
		if($model->paramsQuery)
			$inst->setParamsQuery($model->paramsQuery);
		if(is_array($model->paramsEditable))
			$inst->addParams($model->paramsEditable, true);
		if(is_array($model->paramsDefault))
			$inst->addParamsDefault($model->paramsDefault, true);
		if(is_array($model->paramsRequired))
			$inst->addParamsRequired($model->paramsRequired, true);
		if($model->paramsRequiredQuery)
			$inst->setParamsRequiredQuery($model->paramsRequiredQuery);
		if($model->paramsTimezone)
			$inst->setParamsTimezone($model->paramsTimezone);

		if(null !== $model->renderPage)
			$inst->renderPage = intval($model->renderPage);
		if(null !== $model->renderLimit)
			$inst->renderLimit = intval($model->renderLimit);
		if(null !== $model->renderTotal)
			$inst->renderTotal = intval($model->renderTotal);

		if(is_array($model->renderSort)) {
			if(1 == count($model->renderSort)) {
				$inst->renderSortBy = key($model->renderSort);
				$inst->renderSortAsc = current($model->renderSort);
				
			} else {
				$inst->renderSortBy = array_keys($model->renderSort);
				$inst->renderSortAsc = array_values($model->renderSort);
			}
		}
		
		$inst->renderSubtotals = $model->renderSubtotals;
		
		$inst->renderTemplate = $model->renderTemplate;
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
			$labels = $values = [];
			$active_worker->getPlaceholderLabelsValues($labels, $values);
			$inst->setPlaceholderLabels($labels);
			$inst->setPlaceholderValues($values);
		}
		
		// Enforce class restrictions
		$parent = new $model->class_name;
		$parent->__auto_persist = false;
		// [TODO] This is a rather heavy way to accomplish this, these could be static
		$inst->addColumnsHidden($parent->getColumnsHidden());
		$inst->addParamsRequired($parent->getParamsRequired());
		unset($parent);
		
		if($checksum) {
			$init_model = C4_AbstractViewLoader::serializeAbstractView($inst);
			$inst->_init_checksum = sha1(serialize($init_model));
		}
		
		return $inst;
	}
	
	static function serializeViewToAbstractJson(C4_AbstractView $view, $context=null) {
		$model = array(
			'options' => $view->options,
			'columns' => $view->view_columns,
			'params' => json_decode(json_encode($view->getEditableParams()), true),
			'limit' => intval($view->renderLimit),
			'sort_by' => is_array($view->renderSortBy) ? $view->renderSortBy : [$view->renderSortBy],
			'sort_asc' => is_array($view->renderSortAsc) ? $view->renderSortAsc : [$view->renderSortAsc],
			'subtotals' => $view->renderSubtotals,
		);
		
		if(!empty($context))
			$model['context'] = $context;
		
		return json_encode($model);
	}
	
	static function convertParamsJsonToObject($params_json) {
		$func = null;
		
		// Convert JSON params back to objects
		$func = function(&$e) use (&$func) {
			if(is_array($e) && isset($e['field']) && isset($e['operator'])) {
				$e = new DevblocksSearchCriteria($e['field'], $e['operator'], $e['value']);
				
			} elseif(is_array($e)) {
				array_walk(
					$e,
					$func
				);
			} else {
				// Trim?
			}
		};
		
		if(isset($params_json) && is_array($params_json)) {
			array_walk(
				$params_json,
				$func
			);
			
			return $params_json;
		}
		
		return [];
	}
	
	static function unserializeViewFromAbstractJson($view_model, $view_id) {
		$view_context = $view_model['context'] ?? null;
		
		if(empty($view_context))
			return false;
		
		if(null == ($ctx = Extension_DevblocksContext::get($view_context)))
			return false;
		
		if(null == ($view = $ctx->getChooserView($view_id))) /* @var $view C4_AbstractView */
			return false;
		
		if(isset($view_model['options']))
			$view->options = $view_model['options'];
		
		$view->view_columns = $view_model['columns'];
		$view->renderLimit = intval($view_model['limit']);
		$view->renderSortBy = $view_model['sort_by'];
		$view->renderSortAsc = $view_model['sort_asc'];
		$view->renderSubtotals = $view_model['subtotals'];
		
		if(isset($view_model['params_query'])) {
			$view->setParamsQuery($view_model['params_query']);
		}
		
		if(isset($view_model['params']) && is_array($view_model['params'])) {
			$params = self::convertParamsJsonToObject($view_model['params']);
			$view->addParams($params, true);
		}
		
		if(isset($view_model['params_required']) && is_array($view_model['params_required'])) {
			$params = self::convertParamsJsonToObject($view_model['params_required']);
			$view->addParamsRequired($params, true);
		}
		
		if(isset($view_model['params_required_query'])) {
			$view->setParamsRequiredQuery($view_model['params_required_query']);
		}
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
			$labels = $values = [];
			$active_worker->getPlaceholderLabelsValues($labels, $values);
			$view->setPlaceholderLabels($labels);
			$view->setPlaceholderValues($values);
		}
		
		$init_model = C4_AbstractViewLoader::serializeAbstractView($view);
		$view->_init_checksum = sha1(serialize($init_model));
		
		return $view;
	}
};

class DAO_WorkerViewModel extends Cerb_ORMHelper {
	const CLASS_NAME = 'class_name';
	const COLUMNS_HIDDEN_JSON = 'columns_hidden_json';
	const COLUMNS_JSON = 'columns_json';
	const IS_EPHEMERAL = 'is_ephemeral';
	const OPTIONS_JSON = 'options_json';
	const PARAMS_QUERY = 'params_query';
	const PARAMS_DEFAULT_JSON = 'params_default_json';
	const PARAMS_EDITABLE_JSON = 'params_editable_json';
	const PARAMS_REQUIRED_JSON = 'params_required_json';
	const PARAMS_REQUIRED_QUERY = 'params_required_query';
	const PARAMS_TIMEZONE = 'params_timezone';
	const RENDER_LIMIT = 'render_limit';
	const RENDER_PAGE = 'render_page';
	const RENDER_SORT_JSON = 'render_sort_json';
	const RENDER_SUBTOTALS = 'render_subtotals';
	const RENDER_TEMPLATE = 'render_template';
	const RENDER_TOTAL = 'render_total';
	const TITLE = 'title';
	const VIEW_ID = 'view_id';
	const WORKER_ID = 'worker_id';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::CLASS_NAME)
			->string()
			->setMaxLength(255)
			;
		// text
		$validation
			->addField(self::COLUMNS_HIDDEN_JSON)
			->string()
			->setMaxLength(65535)
			;
		// text
		$validation
			->addField(self::COLUMNS_JSON)
			->string()
			->setMaxLength(65535)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::IS_EPHEMERAL)
			->bit()
			;
		// text
		$validation
			->addField(self::OPTIONS_JSON)
			->string()
			->setMaxLength(65535)
			;
		// text
		$validation
			->addField(self::PARAMS_DEFAULT_JSON)
			->string()
			->setMaxLength(65535)
			;
		// text
		$validation
			->addField(self::PARAMS_EDITABLE_JSON)
			->string()
			->setMaxLength(65535)
			;
		// text
		$validation
			->addField(self::PARAMS_QUERY)
			->string()
			->setMaxLength(65535)
			;
		// text
		$validation
			->addField(self::PARAMS_REQUIRED_JSON)
			->string()
			->setMaxLength(65535)
			;
		// text
		$validation
			->addField(self::PARAMS_REQUIRED_QUERY)
			->string()
			->setMaxLength(65535)
			;
		// varchar(255)
		$validation
			->addField(self::PARAMS_TIMEZONE)
			->string()
			;
		// smallint(5) unsigned
		$validation
			->addField(self::RENDER_LIMIT)
			->uint(2)
			;
		// smallint(5) unsigned
		$validation
			->addField(self::RENDER_PAGE)
			->uint(2)
			;
		// varchar(255)
		$validation
			->addField(self::RENDER_SORT_JSON)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::RENDER_SUBTOTALS)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::RENDER_TEMPLATE)
			->string()
			->setMaxLength(255)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::RENDER_TOTAL)
			->uint(1)
			;
		// varchar(255)
		$validation
			->addField(self::TITLE)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::VIEW_ID)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKER_ID)
			->id()
			;

		return $validation->getFields();
	}
	
	/**
	 *
	 * @param string $where
	 * @return C4_AbstractViewModel[]
	 */
	static public function getWhere($where=null) {
		$db = DevblocksPlatform::services()->database();
		
		$objects = [];
		
		$fields = array(
			'worker_id',
			'view_id',
			'is_ephemeral',
			'class_name',
			'title',
			'options_json',
			'columns_json',
			'columns_hidden_json',
			'params_query',
			'params_editable_json',
			'params_required_json',
			'params_required_query',
			'params_default_json',
			'params_timezone',
			'render_page',
			'render_total',
			'render_limit',
			'render_sort_json',
			'render_subtotals',
			'render_template',
		);
		
		$sql = sprintf("SELECT %s FROM worker_view_model %s",
			implode(',', $fields),
			(!empty($where) ? ('WHERE ' . $where) : '')
		);
		
		$rs = $db->QueryReader($sql);
		
		if($rs instanceof mysqli_result)
		while($row = mysqli_fetch_array($rs)) {
			$model = new C4_AbstractViewModel();
			$model->id = $row['view_id'];
			$model->worker_id = $row['worker_id'];
			$model->is_ephemeral = $row['is_ephemeral'] ? true : false;
			$model->class_name = $row['class_name'];
			$model->name = $row['title'];
			$model->paramsQuery = $row['params_query'];
			$model->paramsRequiredQuery = $row['params_required_query'];
			$model->paramsTimezone = $row['params_timezone'];
			$model->renderPage = $row['render_page'];
			$model->renderTotal = $row['render_total'];
			$model->renderLimit = $row['render_limit'];
			$model->renderSubtotals = $row['render_subtotals'];
			$model->renderTemplate = $row['render_template'];
			
			// JSON blocks
			$model->options = json_decode($row['options_json'], true);
			$model->view_columns = json_decode($row['columns_json'], true);
			$model->columnsHidden = json_decode($row['columns_hidden_json'], true);
			$model->paramsEditable = self::decodeParamsJson($row['params_editable_json']);
			$model->paramsRequired = self::decodeParamsJson($row['params_required_json']);
			$model->paramsDefault = self::decodeParamsJson($row['params_default_json']);
			$model->renderSort = json_decode($row['render_sort_json'], true);
			
			// Make sure it's a well-formed view
			if(empty($model->class_name) || !class_exists($model->class_name, true))
				return false;
			
			$objects[] = $model;
		}
			
		return $objects;
	}
	
	/**
	 *
	 * @param integer $worker_id
	 * @param string $view_id
	 * @return C4_AbstractViewModel|false
	 */
	static public function getView($worker_id, $view_id) {
		$db = DevblocksPlatform::services()->database();
		
		$results = DAO_WorkerViewModel::getWhere(sprintf("worker_id = %d AND view_id = %s",
			$worker_id,
			$db->qstr($view_id)
		));
		
		if(empty($results) || !is_array($results))
			return false;

		return array_shift($results);
	}

	static public function decodeParamsJson($json) {
		if(empty($json) || false === ($params = json_decode($json, true)))
			return [];
		
		self::_walkSerializedParams($params, function(&$node) {
			if(is_array($node) && isset($node['field'])) {
				$node = new DevblocksSearchCriteria($node['field'], $node['operator'], $node['value']);
			}
		});
		
		return $params;
	}
	
	static private function _walkSerializedParams(&$params, $callback) {
		if(is_array($params))
			$callback($params);
		
		if(is_array($params))
		foreach($params as &$param) {
			self::_walkSerializedParams($param, $callback);
		}
	}
	
	static public function setView($worker_id, $view_id, C4_AbstractViewModel $model) {
		$db = DevblocksPlatform::services()->database();

		$render_sort = '';
		
		if(isset($model->renderSortBy)) {
			if(is_array($model->renderSortBy) && is_array($model->renderSortAsc) && count($model->renderSortBy) == count($model->renderSortAsc)) {
				$render_sort = array_combine($model->renderSortBy, $model->renderSortAsc);
			} else if(!is_array($model->renderSortBy) && !is_array($model->renderSortAsc)) {
				$render_sort = [$model->renderSortBy => ($model->renderSortAsc ? true : false) ];
			}
		} else {
			$render_sort = $model->renderSort;
		}
		
		$fields = array(
			'worker_id' => $worker_id,
			'view_id' => $db->qstr($view_id),
			'is_ephemeral' => !empty($model->is_ephemeral) ? 1 : 0,
			'class_name' => $db->qstr($model->class_name),
			'title' => $db->qstr($model->name),
			'options_json' => $db->qstr(json_encode($model->options)),
			'columns_json' => $db->qstr(json_encode($model->view_columns)),
			'columns_hidden_json' => $db->qstr(json_encode($model->columnsHidden)),
			'params_query' => $db->qstr($model->paramsQuery),
			'params_editable_json' => $db->qstr(json_encode($model->paramsEditable)),
			'params_required_json' => $db->qstr(json_encode($model->paramsRequired)),
			'params_required_query' => $db->qstr($model->paramsRequiredQuery),
			'params_default_json' => $db->qstr(json_encode($model->paramsDefault)),
			'params_timezone' => $db->qstr($model->paramsTimezone),
			'render_page' => abs(intval($model->renderPage)),
			'render_total' => !empty($model->renderTotal) ? 1 : 0,
			'render_limit' => max(intval($model->renderLimit),0),
			'render_sort_json' => $db->qstr(json_encode($render_sort)),
			'render_subtotals' => $db->qstr($model->renderSubtotals),
			'render_template' => $db->qstr($model->renderTemplate),
		);
		
		$sql = sprintf("REPLACE INTO worker_view_model (%s) ".
			"VALUES (%s)",
			implode(',', array_keys($fields)),
			implode(',', $fields)
		);
		
		$db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
	}
	
	static function updateFromWorkspaceList($worklist_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("UPDATE worker_view_model ".
			"INNER JOIN workspace_list ON (workspace_list.id=%d) SET ".
			"worker_view_model.title = workspace_list.name, ".
			"worker_view_model.options_json = workspace_list.options_json, ".
			"worker_view_model.columns_json = workspace_list.columns_json, ".
			"worker_view_model.render_limit = workspace_list.render_limit, ".
			"worker_view_model.params_required_json = workspace_list.params_required_json, ".
			"worker_view_model.params_required_query = workspace_list.params_required_query ".
			"WHERE worker_view_model.view_id = 'cust_%d'",
			$worklist_id,
			$worklist_id
		);
		return $db->ExecuteMaster($sql);
	}
	
	static public function deleteView($worker_id, $view_id) {
		$db = DevblocksPlatform::services()->database();
		
		return $db->ExecuteMaster(sprintf("DELETE FROM worker_view_model WHERE worker_id = %d AND view_id = %s",
			$worker_id,
			$db->qstr($view_id)
		));
	}
	
	static public function deleteByViewId($view_id) {
		$db = DevblocksPlatform::services()->database();
		
		return $db->ExecuteMaster(sprintf("DELETE FROM worker_view_model WHERE view_id = %s",
			$db->qstr($view_id)
		));
	}
	
	static public function deleteByViewIdPrefix($view_id) {
		$db = DevblocksPlatform::services()->database();
		
		return $db->ExecuteMaster(sprintf("DELETE FROM worker_view_model WHERE view_id LIKE %s",
			$db->qstr($view_id . '%')
		));
	}
	
	/**
	 * Prepares for a new session by removing ephemeral views and
	 * resetting all page cursors to the first page of the list.
	 *
	 * @param integer$worker_id
	 */
	static public function flush($worker_id=null) {
		$db = DevblocksPlatform::services()->database();
		
		if($worker_id) {
			$db->ExecuteMaster(sprintf("DELETE FROM worker_view_model WHERE worker_id = %d and is_ephemeral = 1",
				$worker_id
			));
			$db->ExecuteMaster(sprintf("UPDATE worker_view_model SET render_page = 0 WHERE worker_id = %d",
				$worker_id
			));
			
		} else {
			$db->ExecuteMaster("DELETE FROM worker_view_model WHERE is_ephemeral = 1");
		}
	}
};
