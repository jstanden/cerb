<?php
class DevblocksTourCallout {
	public $selector = '';
	public $title = '';
	public $body = '';
	public $tipCorner = '';
	public $targetCorner = '';
	public $xOffset = 0;
	public $yOffset = 0;
	
	function __construct($selector='',$title='Callout',$body='...',$tipCorner='topLeft',$targetCorner='topLeft',$xOffset=0,$yOffset=0) {
		$this->selector = $selector;
		$this->title = $title;
		$this->body = $body;
		$this->tipCorner = $tipCorner;
		$this->targetCorner = $targetCorner;
		$this->xOffset = $xOffset;
		$this->yOffset = $yOffset;
	}
};

class DevblocksSearchFieldContextKeys {
	public $where_key = null;
	public $field_key = null;
	public $dict_key = null;
	
	function __construct($where_key, $field_key=null, $dict_key=null) {
		$this->where_key = $where_key;
		$this->field_key = $field_key;
		$this->dict_key = $dict_key;
	}
};

class _DevblocksSearchGetValueAsFilterCallbackFactory {
	function link($filter_key='owner') {
		return function($value, &$filter) use ($filter_key) {
			list($value_context, $value_context_id) = explode(':', $value, 2);
			
			if($value_context == CerberusContexts::CONTEXT_APPLICATION) {
				$filter = $filter_key . ':%s';
				$value = 'app';
				return $value;
			}
			
			if(false == ($value_context_mft = Extension_DevblocksContext::get($value_context, false)))
				return '';
			
			$filter = sprintf('%s.%s:%%s', $filter_key, $value_context_mft->params['alias']);
			$value = sprintf("(id:%d)", $value_context_id);
			return $value;
		};
	}
	
	function linkType($filter_key='owner') {
		return function($value, &$filter) use ($filter_key) {
			$value_context = $value;
			
			if($value_context == CerberusContexts::CONTEXT_APPLICATION) {
				$filter = $filter_key . ':%s';
				$value = 'app';
				return $value;
			}
			
			if(false == ($value_context_mft = Extension_DevblocksContext::get($value_context, false)))
				return '';
			
			$filter = sprintf('%s:%%s', $filter_key);
			$value = $value_context_mft->params['alias'];
			return $value;
		};
	}
};

interface IDevblocksSearchFields {
	static function getFields();
	static function getPrimaryKey();
	static function getCustomFieldContextKeys();
	static function getWhereSQL(DevblocksSearchCriteria $param);
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key);
	static function getLabelsForKeyValues($key, $values);
}

abstract class DevblocksSearchFields implements IDevblocksSearchFields {
	static function getValueAsFilterCallback() {
		return new _DevblocksSearchGetValueAsFilterCallbackFactory();
	}
	
	static function getCustomFieldContextData($context) {
		$map = static::getCustomFieldContextKeys();
		
		if(!isset($map[$context]))
			return false;
		
		return $map[$context];
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		@list($key, $bin) = explode('@', $key, 2);
		
		if(isset($query_fields[$key])) {
			@$query_field = $query_fields[$key];
			@$search_key = $query_field['options']['param_key'];
			@$search_field = $search_fields[$search_key]; /* @var $search_field DevblocksSearchField */
			
			// Default the bin on date-based fields
			// Make sure the field is a date if we're binning
			
			if(in_array($search_field->type, [Model_CustomField::TYPE_DATE, DevblocksSearchCriteria::TYPE_DATE])) {
				$bin = DevblocksPlatform::strLower($bin ?: 'month');
				
			} else {
				$bin = null;
			}
			
			if('*_context_link' == $search_key) {
				@$link_context_alias = substr($key, 6);
				@$link_context = Extension_DevblocksContext::getByAlias($link_context_alias, false);
				
				$key_select = 'links' . '_' . uniqid();
				
				return [
					'key_query' => $key,
					'key_select' => $key_select,
					'label' => @$link_context->name, // [TODO] Context name
					'type' => 'context',
					'sql_select' => sprintf("CONCAT_WS(':',`%s`.to_context,`%s`.to_context_id)",
						Cerb_ORMHelper::escape($key_select),
						Cerb_ORMHelper::escape($key_select)
					),
					'sql_join' => sprintf("INNER JOIN context_link AS `%s` ON (`%s`.from_context = %s AND `%s`.from_context_id = %s%s)",
						Cerb_ORMHelper::escape($key_select),
						Cerb_ORMHelper::escape($key_select),
						Cerb_ORMHelper::qstr($context), // .from_context
						Cerb_ORMHelper::escape($key_select),
						$primary_key, // .from_context_id
						$link_context 
							? sprintf(" AND `%s`.to_context = %s",
								Cerb_ORMHelper::escape($key_select),
								Cerb_ORMHelper::qstr($link_context->id) // .to_context
							)
							: ''
					),
					'get_value_as_filter_callback' => function($value, &$filter) {
						list($value_context, $value_context_id) = explode(':', $value, 2);
						
						if($value_context == CerberusContexts::CONTEXT_APPLICATION) {
							$value = 'app';
							return $value;
						}
						
						if(false == ($value_context_mft = Extension_DevblocksContext::get($value_context, false)))
							return '';
						
						$filter = sprintf('links.%s:%%s', $value_context_mft->params['alias']);
						$value = sprintf("(id:%d)", $value_context_id);
						
						return $value;
					}
				];
				
			} else if(DevblocksPlatform::strStartsWith($search_key, '*_')) {
				// Ignore virtual fields that made it this far
				return false;
				
			} else if(DevblocksPlatform::strStartsWith($search_key, 'cf_')) {
				@$custom_field_id = intval(substr($search_key, 3));
				@$custom_field = DAO_CustomField::get($custom_field_id);
				
				$field_key = 'field_value';
				
				$table = DAO_CustomFieldValue::getValueTableName($custom_field->id);
				
				if($table == 'custom_field_geovalue')
					$field_key = 'ST_ASTEXT(field_value) AS field_value';
				
				if(false && in_array($custom_field->type, [Model_CustomField::TYPE_CURRENCY])) {
					if(false == ($currency_id = @$custom_field->params['currency_id']))
						return [];
					
					if(false == ($currency = DAO_Currency::get($currency_id)))
						return [];
					
					return [
						'key_query' => $key,
						'key_select' => $search_key,
						'label' => $custom_field->name,
						'type' => $custom_field->type,
						'type_options' => [
							'code' => $currency->code,
							'decimal_at' => $currency->decimal_at,
							'symbol' => $currency->symbol,
						],
						'sql_select' => sprintf("(SELECT %s FROM %s WHERE context=%s AND context_id=%s AND field_id=%d LIMIT 1)",
							$field_key,
							Cerb_ORMHelper::escape($table),
							Cerb_ORMHelper::qstr($custom_field->context),
							$primary_key,
							$custom_field->id
						)
					];
					
				} else if (in_array($custom_field->type, [Model_CustomField::TYPE_DATE])) {
					$sql_select_field = sprintf("(SELECT %s FROM %s WHERE context=%s AND context_id=%s AND field_id=%d LIMIT 1)",
						$field_key,
						Cerb_ORMHelper::escape($table),
						Cerb_ORMHelper::qstr($custom_field->context),
						$primary_key,
						$custom_field->id
					);
					
					switch($bin) {
						case 'secs':
						case 'seconds':
						case 'ts':
						case 'timestamp':
						case 'unix':
							return [
								'key_query' => $key,
								'key_select' => $search_key,
								'label' => $search_field->db_label,
								'type' => DevblocksSearchCriteria::TYPE_DATE,
								'timestamp_step' => 'seconds',
								'timestamp_format' => '%S',
								'sql_select' => sprintf("%s)",
									$sql_select_field
								),
							];
							break;
							
						case 'week':
						case 'week-mon':
						case 'week-monday':
							$ts_format = '%Y-%m-%d';
							
							return [
								'key_query' => $key,
								'key_select' => $search_key,
								'label' => $search_field->db_label,
								'type' => DevblocksSearchCriteria::TYPE_TEXT,
								'timestamp_step' => 'week',
								'timestamp_format' => $ts_format,
								'sql_select' => sprintf("DATE_FORMAT(SUBDATE(FROM_UNIXTIME(%s), WEEKDAY(FROM_UNIXTIME(%s))), %s)", // Monday
									$sql_select_field,
									$sql_select_field,
									Cerb_ORMHelper::qstr($ts_format)
								),
							];
							break;
							
						case 'week-sun':
						case 'week-sunday':
							$ts_format = '%Y-%m-%d';
							return [
								'key_query' => $key,
								'key_select' => $search_key,
								'label' => $search_field->db_label,
								'type' => DevblocksSearchCriteria::TYPE_TEXT,
								'timestamp_step' => 'week',
								'timestamp_format' => $ts_format,
								'sql_select' => sprintf("DATE_FORMAT(SUBDATE(FROM_UNIXTIME(%s), DAYOFWEEK(FROM_UNIXTIME(%s))-1), %s)", // Sunday
									$sql_select_field,
									$sql_select_field,
									Cerb_ORMHelper::qstr($ts_format)
								),
							];
							break;
							
						case 'hour':
						case 'day':
						case 'month':
						case 'year':
							$date_format = [
								'hour' => '%Y-%m-%d %H:00',
								'day' => '%Y-%m-%d',
								'month' => '%Y-%m',
								'year' => '%Y',
							];
							
							$ts_format = $date_format[$bin];
							
							return [
								'key_query' => $key,
								'key_select' => $search_key,
								'label' => $search_field->db_label,
								'type' => DevblocksSearchCriteria::TYPE_TEXT,
								'timestamp_step' => $bin,
								'timestamp_format' => $ts_format,
								'sql_select' => sprintf("DATE_FORMAT(FROM_UNIXTIME(%s), %s)",
									$sql_select_field,
									Cerb_ORMHelper::qstr($ts_format)
								),
							];
							break;
					}
					
				} else if (in_array($custom_field->type, [Model_CustomField::TYPE_MULTI_CHECKBOX])) {
					return [
						'key_query' => $key,
						'key_select' => $search_key,
						'label' => $custom_field->name,
						'type' => $custom_field->type,
						'type_options' => $custom_field->params,
						'sql_select' => sprintf("%s.field_value", $field_key),
						'sql_join' => sprintf("INNER JOIN %s AS %s ON (%s.context=%s AND %s.context_id = %s AND %s.field_id = %d)",
							Cerb_ORMHelper::escape($table),
							$field_key,
							$field_key,
							Cerb_ORMHelper::qstr($custom_field->context),
							$field_key,
							$primary_key,
							$field_key,
							$custom_field->id
						),
					];
					
				} else {
					return [
						'key_query' => $key,
						'key_select' => $search_key,
						'label' => $custom_field->name,
						'type' => $custom_field->type,
						'type_options' => $custom_field->params,
						'sql_select' => sprintf("(SELECT %s FROM %s WHERE context=%s AND context_id=%s AND field_id=%d LIMIT 1)",
							$field_key,
							Cerb_ORMHelper::escape($table),
							Cerb_ORMHelper::qstr($custom_field->context),
							$primary_key,
							$custom_field->id
						)
					];
				}
			}
			
			if(false && in_array($search_field->type, [Model_CustomField::TYPE_DECIMAL, DevblocksSearchCriteria::TYPE_DECIMAL])) {
			} else if(false && in_array($search_field->type, [Model_CustomField::TYPE_CURRENCY])) {
				$meta = [
					'key_query' => $key,
					'key_select' => $search_key,
					'label' => $search_field->db_label,
					'sql_select' => sprintf("%s.%s",
						Cerb_ORMHelper::escape($search_field->db_table),
						Cerb_ORMHelper::escape($search_field->db_column)
					),
				];
				
				if(array_key_exists('type', $query_field))
					$meta['type'] = $query_field['type'];
					
				if(array_key_exists('type_options', $query_field))
					$meta['type_options'] = $query_field['type_options'];
				
				return $meta;
				
			} else if(in_array($search_field->type, [Model_CustomField::TYPE_DATE, DevblocksSearchCriteria::TYPE_DATE])) {
				switch($bin) {
					case 'secs':
					case 'seconds':
					case 'ts':
					case 'timestamp':
					case 'unix':
						return [
							'key_query' => $key,
							'key_select' => $search_key,
							'label' => $search_field->db_label,
							'type' => DevblocksSearchCriteria::TYPE_DATE,
							'timestamp_step' => 'seconds',
							'timestamp_format' => '%S',
							'sql_select' => sprintf("%s.%s",
								Cerb_ORMHelper::escape($search_field->db_table),
								Cerb_ORMHelper::escape($search_field->db_column)
							),
						];
						break;
						
					case 'week':
					case 'week-mon':
					case 'week-monday':
						$ts_format = '%Y-%m-%d'; 
						return [
							'key_query' => $key,
							'key_select' => $search_key,
							'label' => $search_field->db_label,
							'type' => DevblocksSearchCriteria::TYPE_TEXT,
							'timestamp_step' => 'week',
							'timestamp_format' => $ts_format,
							'sql_select' => sprintf("DATE_FORMAT(SUBDATE(FROM_UNIXTIME(%s.%s), WEEKDAY(FROM_UNIXTIME(%s.%s))), %s)", // Monday
								Cerb_ORMHelper::escape($search_field->db_table),
								Cerb_ORMHelper::escape($search_field->db_column),
								Cerb_ORMHelper::escape($search_field->db_table),
								Cerb_ORMHelper::escape($search_field->db_column),
								Cerb_ORMHelper::qstr($ts_format)
							),
						];
						break;
						
					case 'week-sun':
					case 'week-sunday':
						$ts_format = '%Y-%m-%d'; 
						return [
							'key_query' => $key,
							'key_select' => $search_key,
							'label' => $search_field->db_label,
							'type' => DevblocksSearchCriteria::TYPE_TEXT,
							'timestamp_step' => 'week',
							'timestamp_format' => $ts_format,
							'sql_select' => sprintf("DATE_FORMAT(SUBDATE(FROM_UNIXTIME(%s.%s), DAYOFWEEK(FROM_UNIXTIME(%s.%s))-1), %s)", // Sunday
								Cerb_ORMHelper::escape($search_field->db_table),
								Cerb_ORMHelper::escape($search_field->db_column),
								Cerb_ORMHelper::escape($search_field->db_table),
								Cerb_ORMHelper::escape($search_field->db_column),
								Cerb_ORMHelper::qstr($ts_format)
							),
						];
						break;
						
					case 'hour':
					case 'day':
					case 'month':
					case 'year':
						$date_format = [
							'hour' => '%Y-%m-%d %H:00',
							'day' => '%Y-%m-%d',
							'month' => '%Y-%m',
							'year' => '%Y',
						];
						
						$ts_format = $date_format[$bin];
						
						return [
							'key_query' => $key,
							'key_select' => $search_key,
							'label' => $search_field->db_label,
							'type' => DevblocksSearchCriteria::TYPE_TEXT,
							'timestamp_step' => $bin,
							'timestamp_format' => $ts_format,
							'sql_select' => sprintf("DATE_FORMAT(FROM_UNIXTIME(%s.%s), %s)",
								Cerb_ORMHelper::escape($search_field->db_table),
								Cerb_ORMHelper::escape($search_field->db_column),
								Cerb_ORMHelper::qstr($ts_format)
							),
						];
						break;
				}
				
			} else {
				$meta = [
					'key_query' => $key,
					'key_select' => $search_key,
					'label' => $search_field->db_label,
					'sql_select' => sprintf("%s.%s",
						Cerb_ORMHelper::escape($search_field->db_table),
						Cerb_ORMHelper::escape($search_field->db_column)
					),
				];
				
				if(array_key_exists('type', $query_field))
					$meta['type'] = $query_field['type'];
					
				if(array_key_exists('type_options', $query_field))
					$meta['type_options'] = $query_field['type_options'];
				
				return $meta;
			}
		}
		
		return false;
	}
	
	static function getLabelsForKeyValues($key, $values) {
		if(DevblocksPlatform::strStartsWith($key, 'links_')) {
			return self::_getLabelsForKeyContextAndIdValues($values);
			
		} else if(DevblocksPlatform::strStartsWith($key, 'cf_')) {
			$custom_field_id = intval(substr($key, 3));
			
			if(false != ($custom_field = DAO_CustomField::get($custom_field_id)))
				switch($custom_field->type) {
					case Model_CustomField::TYPE_LINK:
						if(false == ($dao_context = Extension_DevblocksContext::get($custom_field->params['context'], true)))
							break;
							
						$models = $dao_context->getModelObjects($values);
						$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $dao_context->id);
						
						$map = [];
						
						foreach($dicts as $id => $dict)
							$map[$id] = $dict->_label;
						
						return $map;
						break;
						
					default:
						if(null != ($field_ext = $custom_field->getTypeExtension())) {
							return $field_ext->getLabelsForValues($custom_field, $values);
						}
						break;
				}
		}
		
		return array_combine($values, $values);
	}
	
	static function _getLabelsForKeyExtensionValues($extension_id) {
		$extensions = DevblocksPlatform::getExtensions($extension_id, false);
		$label_map = array_column(DevblocksPlatform::objectsToArrays($extensions), 'name', 'id');
		return $label_map;
	}
	
	static function _getLabelsForKeyContextValues() {
		$contexts = Extension_DevblocksContext::getAll(false);
		$label_map = array_column(DevblocksPlatform::objectsToArrays($contexts), 'name', 'id');
		return $label_map;
	}
	
	static function _getLabelsForKeyBooleanValues() {
		$label_map = [
			0 => DevblocksPlatform::translate('common.no'),
			1 => DevblocksPlatform::translate('common.yes'),
		];
		return $label_map;
	}
	
	static function _getLabelsForKeyContextAndIdValues($values) {
		$context_map = [];
		$label_map = [];
		
		foreach($values as $v) {
			@list($context, $context_id) = explode(':', $v, 2);
			if(!array_key_exists($context, $context_map))
				$context_map[$context] = [];
			
			$context_map[$context][] = intval($context_id);
		}
		
		foreach($context_map as $context => $ids) {
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				continue;
			
			if(false == ($models = $context_ext->getModelObjects($ids)))
				continue;
			
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context);
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, '_label');
			
			$labels = array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
			
			foreach($labels as $id => $label)
				$label_map[$context . ':' . $id] = $label;
		}
		
		return $label_map;
	}
	
	static function getCustomFieldContextWhereKey($context) {
		$where_key = null;
		
		if($context && false !== ($cfield_ctx = self::getCustomFieldContextData($context))) { /* @var $cfield_ctx DevblocksSearchFieldContextKeys */
			$where_key = $cfield_ctx->where_key;
		}
		
		return $where_key;

	}
	static function getCustomFieldContextFieldKey($context) {
		$field_key = null;
		
		if(false != ($cfield_ctx = self::getCustomFieldContextData($context))) { /* @var $cfield_ctx DevblocksSearchFieldContextKeys */
			$field_key = $cfield_ctx->field_key;
		}
		
		return $field_key;
	}
	
	static function _getWhereSQLFromFulltextField(DevblocksSearchCriteria $param, $schema, $pkey, $options=array()) {
		if(false == ($search = Extension_DevblocksSearchSchema::get($schema)))
			return null;
		
		$query = $search->getQueryFromParam($param);
		$attribs = array();
		
		if(isset($options['prefetch_sql'])) {
			$attribs['id'] = array(
				'sql' => $options['prefetch_sql'],
			);
		}
		
		if(false === ($ids = $search->query($query, $attribs))) {
			return '0';
			
		} elseif(is_array($ids)) {
			if(empty($ids))
				$ids = array(-1);
			
			return sprintf('%s IN (%s)',
				$pkey,
				implode(', ', $ids)
			);
			
		} elseif(is_string($ids)) {
			return sprintf("%s IN (SELECT %s.id FROM %s WHERE %s.id=%s)",
				$pkey,
				$ids,
				$ids,
				$ids,
				$pkey
			);
		}
		
		return '0';
	}
	
	static function _getWhereSQLFromCommentFulltextField(DevblocksSearchCriteria $param, $schema, $from_context, $pkey) {
		$search = Extension_DevblocksSearchSchema::get($schema);
		$query = $search->getQueryFromParam($param);
		
		$not = false;
		if(DevblocksPlatform::strStartsWith($query, '!')) {
			$not = true;
			$query = mb_substr($query, 1);
		}
		
		if(false === ($ids = $search->query($query, array('context_crc32' => sprintf("%u", crc32($from_context)))))) {
			return '0';
		
		} elseif(is_array($ids)) {
			$from_ids = DAO_Comment::getContextIdsByContextAndIds($from_context, $ids);
			
			return sprintf('%s %sIN (%s)',
				$pkey,
				$not ? 'NOT ' : '',
				implode(', ', (!empty($from_ids) ? $from_ids : array(-1)))
			);
			
		} elseif(is_string($ids)) {
			return sprintf("%s %sIN (SELECT context_id FROM comment INNER JOIN %s ON (%s.id=comment.id))",
				$pkey,
				$not ? 'NOT ' : '',
				$ids,
				$ids
			);
		}
	}
	
	static function _getWhereSQLFromAttachmentsField(DevblocksSearchCriteria $param, $context, $join_key) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			$query = $param->value;
			
			$not = false;
			if(DevblocksPlatform::strStartsWith($query, '!')) {
				$not = true;
				$query = mb_substr($query, 1);
			}
			
			if(false == ($ext_attachments = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_ATTACHMENT)))
				return;
			
			if(false == (Extension_DevblocksContext::get($context)))
				return;
			
			$view = $ext_attachments->getTempView();
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
			
			$params = $view->getParams();
			
			$query_parts = DAO_Attachment::getSearchQueryComponents(array(), $params);
			
			$query_parts['select'] = sprintf("SELECT %s ", SearchFields_Attachment::getPrimaryKey());
			
			$sql = 
				$query_parts['select']
				. $query_parts['join']
				. $query_parts['where']
				. $query_parts['sort']
				;
			
			return sprintf("%s %sIN (SELECT context_id FROM attachment_link WHERE context = %s AND attachment_id IN (%s)) ",
				Cerb_OrmHelper::escape($join_key),
				$not ? 'NOT ' : '',
				Cerb_ORMHelper::qstr($context),
				$sql
			);
		}
	}
	
	static function _getWhereSQLFromVirtualSearchSqlField(DevblocksSearchCriteria $param, $context, $subquery_sql='%s', $where_key=null) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			$query = $param->value;
			
			$not = false;
			if(DevblocksPlatform::strStartsWith($query, '!')) {
				$not = true;
				$query = mb_substr($query, 1);
			}
			
			if(false == ($ext = Extension_DevblocksContext::get($context)))
				return;
			
			$view = $ext->getTempView();
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
			
			$params = $view->getParams();
			
			if(false == ($dao_class = $ext->getDaoClass()))
				return;
			
			if(false == ($search_class = $ext->getSearchClass()))
				return;
			
			$query_parts = $dao_class::getSearchQueryComponents([], $params);
			
			$query_parts['select'] = sprintf("SELECT %s ", $search_class::getPrimaryKey());
			
			$sql = 
				$query_parts['select']
				. $query_parts['join']
				. $query_parts['where']
				. $query_parts['sort']
				;
			
			if(!empty($where_key)) {
				$subquery_sql = sprintf("%s %s (%s)",
					$where_key,
					$not ? 'NOT IN' : 'IN',
					$subquery_sql
				);
			}
			
			return sprintf($subquery_sql, $sql);
		}
	}
	
	static function _getWhereSQLFromVirtualSearchField(DevblocksSearchCriteria $param, $context, $join_key) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			$query = $param->value;
			
			$not = false;
			if(DevblocksPlatform::strStartsWith($query, '!')) {
				$not = true;
				$query = mb_substr($query, 1);
			}
			
			if(false == ($ext = Extension_DevblocksContext::get($context)))
				return;
			
			$view = $ext->getTempView();
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
			
			$params = $view->getParams();
			
			if(false == ($dao_class = $ext->getDaoClass()) || !class_exists($dao_class))
				return;
			
			if(false == ($search_class = $ext->getSearchClass()) || !class_exists($search_class))
				return;
			
			if(false == ($primary_key = $search_class::getPrimaryKey()))
				return;
			
			$query_parts = $dao_class::getSearchQueryComponents(array(), $params);
			
			$query_parts['select'] = sprintf("SELECT %s ", $primary_key);
			
			$sql = 
				$query_parts['select']
				. $query_parts['join']
				. $query_parts['where']
				. $query_parts['sort']
				;
			
			return sprintf("%s %sIN (%s) ",
				Cerb_OrmHelper::escape($join_key),
				$not ? 'NOT ' : '',
				$sql
			);
		}
	}
	
	static function _getWhereSQLFromContextAndID(DevblocksSearchCriteria $param, $context_field, $context_id_field) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			@list($alias, $query) = explode(':', $param->value, 2);
			
			if(empty($alias) || (false == ($ext = Extension_DevblocksContext::getByAlias(str_replace('.', ' ', $alias), true))))
				return;
			
			if(!method_exists($ext, 'getSearchView') || false == ($view = $ext->getTempView())) {
				// Handle contexts without worklists
				switch($alias) {
					case 'app':
						return sprintf("(%s = %s AND %s = %d)",
							Cerb_OrmHelper::escape($context_field),
							Cerb_ORMHelper::qstr($ext->id),
							Cerb_OrmHelper::escape($context_id_field),
							'0'
						);
						break;
				}
				return;
			}
				
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
			
			$params = $view->getParams();
			
			if(false == ($dao_class = $ext->getDaoClass()) || !class_exists($dao_class))
				return;
			
			if(false == ($search_class = $ext->getSearchClass()) || !class_exists($search_class))
				return;
			
			if(false == ($primary_key = $search_class::getPrimaryKey()))
				return;
			
			$query_parts = $dao_class::getSearchQueryComponents(array(), $params);
			
			$query_parts['select'] = sprintf("SELECT %s ", $primary_key);
			
			$sql = 
				$query_parts['select']
				. $query_parts['join']
				. $query_parts['where']
				. $query_parts['sort']
				;
			
			return sprintf("(%s = %s AND %s IN (%s)) ",
				Cerb_OrmHelper::escape($context_field),
				Cerb_ORMHelper::qstr($ext->id),
				Cerb_OrmHelper::escape($context_id_field),
				$sql
			);
		}
		
		if(!is_array($param->value))
			return '0';
		
		$wheres = array();
		$contexts = array();
			
		foreach($param->value as $owner_context) {
			@list($context, $context_id) = explode(':', $owner_context);
			
			if(empty($context))
				continue;
			
			if(!empty($context_id)) {
				$wheres[] = sprintf("(%s = %s AND %s = %d)",
					Cerb_ORMHelper::escape($context_field),
					Cerb_ORMHelper::qstr($context),
					Cerb_ORMHelper::escape($context_id_field),
					$context_id
				);
				
			} else {
				$contexts[] = $context;
			}
		}
		
		if(!empty($contexts)) {
			$wheres[] = sprintf("(%s IN (%s))",
				Cerb_ORMHelper::escape($context_field),
				implode(',', array_map(function($ctx) {
					return Cerb_ORMHelper::qstr($ctx);
				}, $contexts))
			);
		}
		
		if(!empty($wheres))
			return implode(' OR ', $wheres);
	}
	
	static function _getWhereSQLFromContextLinksField(DevblocksSearchCriteria $param, $from_context, $pkey) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			@list($alias, $query) = explode(':', $param->value, 2);
			
			if(empty($alias) || (false == ($ext = Extension_DevblocksContext::getByAlias(str_replace('.', ' ', $alias), true))))
				return;
			
			$not = false;
			$all = false;
			
			if(DevblocksPlatform::strStartsWith($query, '!')) {
				$not = true;
				$query = mb_substr($query, 1);
				
			} elseif(DevblocksPlatform::strStartsWith($query, 'all(')) {
				$all = true;
				$query = mb_substr($query, 3);
			}
			
			$view = $ext->getTempView();
			$view->addParamsWithQuickSearch($query, true);
			
			$params = $view->getParams();
			
			if(false == ($dao_class = $ext->getDaoClass()) || !class_exists($dao_class))
				return;
			
			if(false == ($search_class = $ext->getSearchClass()) || !class_exists($search_class))
				return;
			
			if(false == ($primary_key = $search_class::getPrimaryKey()))
				return;
			
			$query_parts = $dao_class::getSearchQueryComponents(array(), $params);
			
			$query_parts['select'] = sprintf("SELECT %s ", $primary_key);
			
			$sql = 
				$query_parts['select']
				. $query_parts['join']
				. $query_parts['where']
				. $query_parts['sort']
				;
			
			// All
			if($all) {
				return sprintf("%s %sIN (SELECT from_context_id FROM context_link cl WHERE from_context = %s AND to_context = %s AND to_context_id IN (%s) ".
					"GROUP BY (from_context_id) ".
					"HAVING COUNT(*) = (SELECT COUNT(*) FROM context_link WHERE from_context = %s AND to_context = %s AND from_context_id = cl.from_context_id)) ",
					$pkey,
					$not ? 'NOT ' : '',
					Cerb_ORMHelper::qstr($from_context),
					Cerb_ORMHelper::qstr($ext->id),
					$sql,
					Cerb_ORMHelper::qstr($from_context),
					Cerb_ORMHelper::qstr($ext->id)
				);
				
			// Any
			} else {
				return sprintf("%s %sIN (SELECT from_context_id FROM context_link cl WHERE from_context = %s AND to_context = %s AND to_context_id IN (%s)) ",
					$pkey,
					$not ? 'NOT ' : '',
					Cerb_ORMHelper::qstr($from_context),
					Cerb_ORMHelper::qstr($ext->id),
					$sql
				);
			}
		}
		
		if(!in_array($param->operator,[DevblocksSearchCriteria::OPER_TRUE, DevblocksSearchCriteria::OPER_IS_NOT_NULL])) {
			if(empty($param->value) || !is_array($param->value))
				$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
		}
		
		$where_contexts = [];
		
		if(is_array($param->value))
		foreach($param->value as $context_data) {
			@list($context, $context_id) = explode(':', $context_data, 2);
	
			if(empty($context))
				return;
			
			if(!isset($where_contexts[$context]))
				$where_contexts[$context] = array();
			
			if($context_id)
				$where_contexts[$context][] = $context_id;
		}
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_TRUE:
			case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
				return sprintf("EXISTS (SELECT 1 FROM context_link WHERE context_link.to_context=%s AND context_link.to_context_id=%s) ",
					Cerb_ORMHelper::qstr($from_context),
					$pkey
				);
				break;
			
			case DevblocksSearchCriteria::OPER_IS_NULL:
				return sprintf("NOT EXISTS (SELECT 1 FROM context_link WHERE context_link.to_context=%s AND context_link.to_context_id=%s) ",
					Cerb_ORMHelper::qstr($from_context),
					$pkey
				);
				break;
	
			case DevblocksSearchCriteria::OPER_IN:
				$where_sqls = array();
				
				foreach($where_contexts as $context => $ids) {
					$ids = DevblocksPlatform::sanitizeArray($ids, 'integer');
					
					$where_sqls[] = sprintf("%s IN (SELECT from_context_id FROM context_link cl WHERE from_context = %s AND to_context = %s %s) ",
						$pkey,
						Cerb_ORMHelper::qstr($from_context),
						Cerb_ORMHelper::qstr($context),
						(!empty($ids) ? (sprintf("AND to_context_id IN (%s)", implode(',', $ids))) : '')
					);
				}
				
				if(!empty($where_sqls))
					return sprintf('(%s)', implode(' OR ', $where_sqls));
				
				break;
		}
	}
	
	static function _getWhereSQLFromAliasesField(DevblocksSearchCriteria $param, $context, $pkey) {
		$terms = DAO_ContextAlias::prepare($param->value);
		
		if(empty($terms))
			return sprintf("0");
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_EQ:
			case DevblocksSearchCriteria::OPER_NEQ:
				return sprintf("%s %s (SELECT id FROM context_alias WHERE context = %s AND terms IN (%s))",
					$pkey,
					($param->operator == DevblocksSearchCriteria::OPER_NEQ) ? 'NOT IN ' : 'IN',
					Cerb_ORMHelper::qstr($context),
					implode(',', Cerb_ORMHelper::qstrArray($terms))
				);
				break;
				
			case DevblocksSearchCriteria::OPER_IN:
			case DevblocksSearchCriteria::OPER_NIN:
				return sprintf("%s %s (SELECT id FROM context_alias WHERE context = %s AND terms IN (%s))",
					$pkey,
					($param->operator == DevblocksSearchCriteria::OPER_NIN) ? 'NOT IN' : 'IN',
					Cerb_ORMHelper::qstr($context),
					implode(',', Cerb_ORMHelper::qstrArray($terms))
				);
				break;
		}
		
		return null;
	}
	
	static function _getWhereSQLFromWatchersField(DevblocksSearchCriteria $param, $from_context, $pkey) {
		$ids = DevblocksPlatform::sanitizeArray($param->value, 'integer');
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_IN:
				return sprintf("%s IN (SELECT from_context_id FROM context_link WHERE from_context = %s AND from_context_id = %s AND to_context = 'cerberusweb.contexts.worker' AND to_context_id IN (%s))",
					$pkey,
					Cerb_ORMHelper::qstr($from_context),
					$pkey,
					implode(',', $ids)
				);
				break;
				
			case DevblocksSearchCriteria::OPER_NIN:
				return sprintf("%s NOT IN (SELECT from_context_id FROM context_link WHERE from_context = %s AND to_context = 'cerberusweb.contexts.worker' AND to_context_id IN (%s))",
					$pkey,
					Cerb_ORMHelper::qstr($from_context),
					implode(',', $ids)
				);
				break;
			
			case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
				return sprintf("%s IN (SELECT DISTINCT from_context_id FROM context_link WHERE from_context = %s AND from_context_id = %s AND to_context = 'cerberusweb.contexts.worker')",
					$pkey,
					Cerb_ORMHelper::qstr($from_context),
					$pkey
				);
				break;
				
			case DevblocksSearchCriteria::OPER_IS_NULL:
				return sprintf("%s NOT IN (SELECT DISTINCT from_context_id FROM context_link WHERE from_context = %s AND to_context = 'cerberusweb.contexts.worker')",
					$pkey,
					Cerb_ORMHelper::qstr($from_context)
				);
				break;
		}
		
		return null;
	}
	
	static function _getWhereSQLFromWatchersCountField(DevblocksSearchCriteria $param, $from_context, $pkey) {
		$where_sql = null;
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_EQ:
			case DevblocksSearchCriteria::OPER_NEQ:
			case DevblocksSearchCriteria::OPER_GT:
			case DevblocksSearchCriteria::OPER_GTE:
			case DevblocksSearchCriteria::OPER_LT:
			case DevblocksSearchCriteria::OPER_LTE:
				$where_sql = sprintf("%s %d",
					Cerb_ORMHelper::escape($param->operator),
					$param->value
				);
				break;
				
			case DevblocksSearchCriteria::OPER_IN:
			case DevblocksSearchCriteria::OPER_NIN:
				$values = DevblocksPlatform::sanitizeArray($param->value, 'int');
				
				$where_sql = sprintf("%s (%s)",
					Cerb_ORMHelper::escape($param->operator),
					implode(',', $values)
				);
				break;
				
			case DevblocksSearchCriteria::OPER_BETWEEN:
				$values = DevblocksPlatform::sanitizeArray($param->value, 'int');
				
				$where_sql = sprintf("BETWEEN %d AND %d",
					@$values[0] ?: 0,
					@$values[1] ?: 0
				);
				break;
		}
		
		if(!$where_sql)
			return null;
		
		$sql = sprintf("(SELECT COUNT(*) FROM context_link WHERE from_context = %s AND from_context_id=%s AND to_context = %s) %s",
			Cerb_ORMHelper::qstr($from_context),
			Cerb_ORMHelper::escape($pkey),
			Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_WORKER),
			$where_sql
		);
		return $sql;
	}
	
	static function _getWhereSQLFromCustomFields($param) {
		if(0 == ($field_id = intval(substr($param->field,3))))
			return 0;
		
		// Return a soft failure when a filtered custom field has been deleted (i.e. ignore)
		if(false == ($field = DAO_CustomField::get($field_id)))
			return '';

		$field_table = sprintf("cf_%d", $field_id);
		$value_table = DAO_CustomFieldValue::getValueTableName($field_id);
		$cfield_key = null;
		
		$cfield_key = static::getCustomFieldContextWhereKey($field->context);
		
		if(empty($cfield_key))
			return 0;
		
		// [TODO] Efficiently handle the "OR NULL" cfields
		// [TODO] Efficiently handle "NOT 0"
		
		$not = false;
		
		$param = clone $param;
		
		// Custom field optimizations
		
		// Field type special handling
		switch($field->type) {
			
			// An unchecked checkbox is simply NOT a checked checkbox (faster than LEFT JOIN)
			case Model_CustomField::TYPE_CHECKBOX:
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_EQ_OR_NULL:
						if(empty($param->value)) {
							$not = true;
							$param->operator = DevblocksSearchCriteria::OPER_EQ;
							$param->value = 1;
						}
						break;
				}
				break;
				
			case Model_CustomField::TYPE_DATE:
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_EQ_OR_NULL:
						$not = true;
						$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
						$param->value = null;
						break;
				}
				break;
				
			case Model_CustomField::TYPE_LINK:
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_CUSTOM:
						@$link_context = $field->params['context'];
						
						$subquery_sql = sprintf("SELECT context_id FROM %s WHERE field_id = %d AND field_value IN (%%s)",
							$value_table,
							$field_id
						);
						
						$where_sql = self::_getWhereSQLFromVirtualSearchSqlField(
							$param,
							$link_context,
							$subquery_sql,
							$cfield_key
						);
						
						return $where_sql;
						break;
				}
				break;
				
			default:
				if(null != ($field_ext = $field->getTypeExtension())) {
					if(false != ($where_sql = $field_ext->getWhereSQLFromParam($field, $param))) {
						return $where_sql;
					}
				}
				
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IN_OR_NULL:
						$param->operator = DevblocksSearchCriteria::OPER_IN;
						break;
						
					case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
						$not = true;
						$param->operator = DevblocksSearchCriteria::OPER_IN;
						break;
						
					case DevblocksSearchCriteria::OPER_NEQ:
						$not = true;
						$param->operator = DevblocksSearchCriteria::OPER_EQ;
						break;
						
					case DevblocksSearchCriteria::OPER_NIN:
						$not = true;
						$param->operator = DevblocksSearchCriteria::OPER_IN;
						break;
						
					case DevblocksSearchCriteria::OPER_NOT_LIKE:
						$not = true;
						$param->operator = DevblocksSearchCriteria::OPER_LIKE;
						break;
						
					case DevblocksSearchCriteria::OPER_NOT_BETWEEN:
						$not = true;
						$param->operator = DevblocksSearchCriteria::OPER_BETWEEN;
						break;
				}
				break;
		}
		
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_IS_NULL:
			case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
				return sprintf("%s %sIN (SELECT context_id FROM %s AS %s WHERE %s.context = %s AND %s.context_id = %s AND %s.field_id=%d)",
					$cfield_key,
					($param->operator == DevblocksSearchCriteria::OPER_IS_NULL) ? 'NOT ' : '',
					$value_table,
					$field_table,
					$field_table,
					Cerb_ORMHelper::qstr($field->context),
					$field_table,
					$cfield_key,
					$field_table,
					$field_id
				);
				break;

			default:
				return sprintf("%s %sIN (SELECT context_id FROM %s AS %s WHERE %s.context = %s AND %s.context_id = %s AND %s.field_id=%d AND %s)",
					$cfield_key,
					($not) ? 'NOT ' : '',
					$value_table,
					$field_table,
					$field_table,
					Cerb_ORMHelper::qstr($field->context),
					$field_table,
					$cfield_key,
					$field_table,
					$field_id,
					$param->getWhereSQL(static::getFields(), static::getPrimaryKey())
				);
				break;
		}
		
		return 0;
	}
}

class DevblocksSearchCriteria {
	const OPER_EQ = '=';
	const OPER_EQ_OR_NULL = 'equals or null';
	const OPER_NEQ = '!=';
	const OPER_IS_NULL = 'is null';
	const OPER_IS_NOT_NULL = 'is not null';
	const OPER_IN = 'in';
	const OPER_IN_OR_NULL = 'in or null';
	const OPER_NIN = 'not in';
	const OPER_NIN_OR_NULL = 'not in or null';
	const OPER_FULLTEXT = 'fulltext';
	const OPER_GEO_POINT_EQ = 'geo eq';
	const OPER_GEO_POINT_NEQ = 'geo neq';
	const OPER_LIKE = 'like';
	const OPER_NOT_LIKE = 'not like';
	const OPER_GT = '>';
	const OPER_LT = '<';
	const OPER_GTE = '>=';
	const OPER_LTE = '<=';
	const OPER_BETWEEN = 'between';
	const OPER_NOT_BETWEEN = 'not between';
	const OPER_TRUE = '1';
	const OPER_FALSE = '0';
	const OPER_CUSTOM = 'custom';
	
	const GROUP_AND = 'AND';
	const GROUP_AND_NOT = 'AND NOT';
	const GROUP_OR = 'OR';
	const GROUP_OR_NOT = 'OR NOT';
	
	const TYPE_BOOL = 'bool';
	const TYPE_CONTEXT = 'context';
	const TYPE_DATE = 'date';
	const TYPE_DECIMAL = 'decimal';
	const TYPE_FULLTEXT = 'fulltext';
	const TYPE_GEO_POINT = 'geo_point';
	const TYPE_NUMBER = 'number';
	const TYPE_NUMBER_MINUTES = 'number_minutes';
	const TYPE_NUMBER_MS = 'number_ms';
	const TYPE_NUMBER_SECONDS = 'number_seconds';
	const TYPE_SEARCH = 'search';
	const TYPE_TEXT = 'text';
	const TYPE_VIRTUAL = 'virtual';
	const TYPE_WORKER = 'worker';
	
	const OPTION_TEXT_PARTIAL = 1;
	const OPTION_TEXT_PREFIX = 2;
	
	public $field;
	public $operator;
	public $value;
	
	/**
	 * @param string $field
	 * @param string $oper
	 * @param mixed $value
	 * @return DevblocksSearchCriteria
	 */
	public function __construct($field, $oper, $value=null) {
		$this->field = $field;
		$this->operator = $oper;
		$this->value = $value;
	}
	
	public static function getOperators() {
		return [
			self::OPER_BETWEEN,
			self::OPER_CUSTOM,
			self::OPER_EQ,
			self::OPER_EQ_OR_NULL,
			self::OPER_FALSE,
			self::OPER_FULLTEXT,
			self::OPER_GT,
			self::OPER_GTE,
			self::OPER_IN,
			self::OPER_IN_OR_NULL,
			self::OPER_IS_NOT_NULL,
			self::OPER_IS_NULL,
			self::OPER_LIKE,
			self::OPER_LT,
			self::OPER_LTE,
			self::OPER_NEQ,
			self::OPER_NIN,
			self::OPER_NIN_OR_NULL,
			self::OPER_NOT_BETWEEN,
			self::OPER_NOT_LIKE,
			self::OPER_TRUE,
		];
	}
	
	public static function sanitizeOperator($operator) {
		return in_array($operator, self::getOperators()) ? $operator : self::OPER_EQ;
	}
	
	public static function getParamFromQueryFieldTokens($field, $tokens, $meta) {
		$search_fields = $meta;
		@$search_field = $search_fields[$field];
		
		// Only parse valid fields
		if(!$search_field || !isset($search_field['type']))
			return false;

		@$param_key = $search_fields[$field]['options']['param_key'];
		
		// Handle searches for NULL/!NULL
		if(
			(2 == count($tokens) && 'T_NOT' == $tokens[0]->type && 'T_TEXT' == $tokens[1]->type && 'null' == $tokens[1]->value)
			|| (1 == count($tokens) && 'T_TEXT' == $tokens[0]->type && 'null' == $tokens[0]->value)
		) {
			$not = ('T_NOT' == @$tokens[0]->type);
			$oper = $not ? DevblocksSearchCriteria::OPER_IS_NOT_NULL : DevblocksSearchCriteria::OPER_IS_NULL;
			
			return new DevblocksSearchCriteria(
				$param_key,
				$oper,
				null
			);
		}
		
		switch($search_field['type']) {
			case DevblocksSearchCriteria::TYPE_BOOL:
				if($param_key && false != ($param = DevblocksSearchCriteria::getBooleanParamFromTokens($param_key, $tokens)))
					return $param;
				break;
				
			case DevblocksSearchCriteria::TYPE_DATE:
				if($param_key && false != ($param = DevblocksSearchCriteria::getDateParamFromTokens($param_key, $tokens)))
					return $param;
				break;
				
			case DevblocksSearchCriteria::TYPE_DECIMAL:
				$tokens = CerbQuickSearchLexer::getDecimalTokensAsNumbers($tokens);
				return DevblocksSearchCriteria::getNumberParamFromTokens($param_key, $tokens);
				break;
				
			case DevblocksSearchCriteria::TYPE_FULLTEXT:
				if($param_key && false != ($param = DevblocksSearchCriteria::getFulltextParamFromTokens($param_key, $tokens)))
					return $param;
				break;
				
			case DevblocksSearchCriteria::TYPE_GEO_POINT:
				if($param_key && false != ($param = DevblocksSearchCriteria::getGeoPointParamFromTokens($param_key, $tokens, $search_field)))
					return $param;
				break;
				
			case DevblocksSearchCriteria::TYPE_CONTEXT:
			case DevblocksSearchCriteria::TYPE_NUMBER:
				if($param_key && false != ($param = DevblocksSearchCriteria::getNumberParamFromTokens($param_key, $tokens)))
					return $param;
				break;
				
			case DevblocksSearchCriteria::TYPE_NUMBER_SECONDS:
				$tokens = CerbQuickSearchLexer::getHumanTimeTokensAsNumbers($tokens);
				return DevblocksSearchCriteria::getNumberParamFromTokens($param_key, $tokens);
				break;
				
			case DevblocksSearchCriteria::TYPE_TEXT:
				@$match_type = $search_field['options']['match'];
				
				if($param_key && false != ($param = DevblocksSearchCriteria::getTextParamFromTokens($param_key, $tokens, $match_type)))
					return $param;
				break;
			
			case DevblocksSearchCriteria::TYPE_VIRTUAL:
				@$cf_id = $search_fields[$field]['options']['cf_id'];
				
				if(!$cf_id || false == ($custom_field = DAO_CustomField::get($cf_id)))
					break;
				
				switch($custom_field->type) {
					// If a custom record link, add a deep search filter
					case Model_CustomField::TYPE_LINK:
						if($param_key && false != $param = DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, $param_key))
							return $param;
						break;
						
					default:
						if(false != ($field_ext = $custom_field->getTypeExtension())) {
							if($param_key && false != ($param = $field_ext->getParamFromQueryFieldTokens($field, $tokens, $param_key)))
								return $param;
						}
						break;
				}
				break;
			
			case DevblocksSearchCriteria::TYPE_WORKER:
				if($param_key && false != ($param = DevblocksSearchCriteria::getWorkerParamFromTokens($param_key, $tokens, $search_field)))
					return $param;
				break;
		}
		
		return false;
	}
	
	public static function getDateParamFromTokens($field_key, $tokens) {
		// [TODO] Add more operators, for now we assume it's always '[date] to [date]' format
		// [TODO] If not a range search, and not a relative start point, we could treat this as an absolute (=)
		// [TODO] Handle >=, >, <=, <, =, !=
		
		foreach($tokens as $token) {
			switch($token->type) {
				// Parameterized expression
				case 'T_GROUP':
					$query = substr(CerbQuickSearchLexer::getTokensAsQuery($tokens),1,-1);
					
					$fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
					
					$params = [];
					$oper = $value = null;
					
					foreach($fields as $field) {
						switch($field->key) {
							case 'since':
								CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
								$params['since'] = $value;
								break;
								
							case 'until':
								CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
								$params['until'] = $value;
								break;
								
							case 'day':
							case 'days':
								CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
								
								if(is_array($value) && 1 == count($value))
									$value = DevblocksPlatform::parseCsvString(array_shift($value));
								
								$params['day'] = $value;
								break;
								
							case 'time':
							case 'times':
								CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
								
								if(is_array($value) && 1 == count($value))
									$value = DevblocksPlatform::parseCsvString(array_shift($value));
								
								$params['time'] = $value;
								break;
						}
					}
					
					$sql_parts = [];
				
					if(array_key_exists('since', $params) || array_key_exists('until', $params)) {
						@$range_since = $params['since'] ?: 'big bang';
						@$range_until = $params['until'] ?: 'now';
						$range = DevblocksPlatform::services()->date()->parseDateRange($range_since . ' to ' . $range_until);
						$sql_parts[] = sprintf("%%1\$s BETWEEN %d AND %d", $range['from_ts'], $range['to_ts']);
					}
					
					if(array_key_exists('day', $params)) {
						$range_days = DevblocksPlatform::services()->date()->parseDays($params['day']);
						$sql_part = "DATE_FORMAT(FROM_UNIXTIME(%1\$s),'%%w') ";
						
						if(0 == count($range_days)) {
							$sql_part .= '= -1';
						} else {
							$sql_part .= sprintf('IN (%s)', implode(',', $range_days));
						}
						
						$sql_parts[] = $sql_part;
					}
					
					if(array_key_exists('time', $params)) {
						$range_times = DevblocksPlatform::services()->date()->parseTimes($params['time'], true);
						$sql_field = "TIME_TO_SEC(DATE_FORMAT(FROM_UNIXTIME(%1\$s), '%%H:%%i')) ";
						$time_parts = [];
						
						foreach($range_times as $range_time) {
							if (!is_array($range_time)) {
								$time_parts[] = $sql_field . '= ' . $range_time;
							} elseif (is_array($range_time) && 2 == count($range_time)) {
								$time_parts[] = $sql_field . sprintf('BETWEEN %d AND %d ', $range_time[0], $range_time[1]);
							}
						}
						
						if($time_parts)
							$sql_parts[] = '(' . implode(' OR ', $time_parts) . ')';
					}
					
					if($sql_parts) {
						$sql = '(' . implode(' AND ', $sql_parts) . ')';
					} else {
						$sql = '';
					}
					
					return new DevblocksSearchCriteria(
						$field_key,
						DevblocksSearchCriteria::OPER_CUSTOM,
						[
							'label' => $query,
							'sql' => $sql,
						]
					);
					break;
					
				// String
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					$oper = DevblocksSearchCriteria::OPER_BETWEEN;
				
					if(0 == strcasecmp(trim($token->value), 'never') || empty($token->value)) {
							$oper = DevblocksSearchCriteria::OPER_EQ;
							$values = 'never';
							
						} else {
							$values = explode(' to ', DevblocksPlatform::strLower($token->value), 2);
							
							if(1 == count($values))
								$values[] = 'now';
						}
				
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						$values
					);
					break;
			}
		}
	}
	
	public static function getBooleanParamFromTokens($field_key, $tokens) {
		$oper = DevblocksSearchCriteria::OPER_EQ;
		$value = '1';
		
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					if(false !== stristr($token->value, 'n')
						|| false !== stristr($token->value, 'f')
						|| $token->value == '0'
					) {
						$oper = DevblocksSearchCriteria::OPER_EQ_OR_NULL;
						$value = '0';
					}
					break;
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
	}
	
	public static function getBytesParamFromTokens($field_key, $tokens) {
		$oper = DevblocksSearchCriteria::OPER_EQ;
		$value = null;
		$not = false;
		
		if(is_array($tokens))
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_NOT':
					$not = true;
					break;
					
				case 'T_ARRAY':
					$oper = $not ? DevblocksSearchCriteria::OPER_NIN : DevblocksSearchCriteria::OPER_IN;
					
					// Convert values
					array_walk($token->value, function(&$v) {
						$v = DevblocksPlatform::parseBytesString($v);
					});
					
					$value = DevblocksPlatform::sanitizeArray($token->value, 'int');
					break;
					
				case 'T_TEXT':
				case 'T_QUOTED_TEXT':
					$oper = $not ? DevblocksSearchCriteria::OPER_NEQ : DevblocksSearchCriteria::OPER_EQ;
					$value = $token->value;
					$matches = [];
					
					if(preg_match('#(.*?)\.{3}(.*)#', $value, $matches) || preg_match('#(.*?)\s+to\s+(.*)#', $value, $matches)) {
						$from = intval(DevblocksPlatform::parseBytesString($matches[1]));
						$to = intval(DevblocksPlatform::parseBytesString($matches[2]));
						
						$oper = DevblocksSearchCriteria::OPER_BETWEEN;
						$value = array($from, $to);
						
					} else if(preg_match('#^([\<\>\!\=]+)(.*)#', $value, $matches)) {
						$oper_hint = trim($matches[1]);
						$value = DevblocksPlatform::parseBytesString(trim($matches[2]));
						
						switch($oper_hint) {
							case '!':
							case '!=':
								$oper = self::OPER_NEQ;
								break;
								
							case '>':
								$oper = self::OPER_GT;
								break;
								
							case '>=':
								$oper = self::OPER_GTE;
								break;
								
							case '<':
								$oper = self::OPER_LT;
								break;
								
							case '<=':
								$oper = self::OPER_LTE;
								break;
								
							default:
								break;
						}
						
						$value = intval($value);
					}
					break;
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
	}
	
	public static function getGeoPointParamFromTokens($field_key, $tokens) {
		$oper = DevblocksSearchCriteria::OPER_GEO_POINT_EQ;
		$value = null;
		$not = false;
		
		if(is_array($tokens))
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_NOT':
					$not = true;
					break;
				
				case 'T_TEXT':
				case 'T_QUOTED_TEXT':
					$oper = $not ? DevblocksSearchCriteria::OPER_GEO_POINT_NEQ : DevblocksSearchCriteria::OPER_GEO_POINT_EQ;
					$value = DevblocksPlatform::parseGeoPointString($token->value);
					break;
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
	}
	
	public static function getNumberParamFromTokens($field_key, $tokens) {
		$oper = DevblocksSearchCriteria::OPER_EQ;
		$value = null;
		$not = false;
		
		if(is_array($tokens))
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_NOT':
					$not = true;
					break;
					
				case 'T_ARRAY':
					$oper = $not ? DevblocksSearchCriteria::OPER_NIN : DevblocksSearchCriteria::OPER_IN;
					$value = DevblocksPlatform::sanitizeArray($token->value, 'int');
					break;
					
				case 'T_TEXT':
				case 'T_QUOTED_TEXT':
					$oper = $not ? DevblocksSearchCriteria::OPER_NEQ : DevblocksSearchCriteria::OPER_EQ;
					$value = $token->value;
					$matches = [];
					
					if(preg_match('#(\d+)\.{3}(\d+)#', $value, $matches) || preg_match('#(\d+)\s+to\s+(\d+)#', $value, $matches)) {
						$from = intval($matches[1]);
						$to = intval($matches[2]);
						
						$oper = DevblocksSearchCriteria::OPER_BETWEEN;
						$value = array($from, $to);
						
					} else if(preg_match('#^([\<\>\!\=]+)(.*)#', $value, $matches)) {
						$oper_hint = trim($matches[1]);
						$value = trim($matches[2]);
						
						switch($oper_hint) {
							case '!':
							case '!=':
								$oper = self::OPER_NEQ;
								break;
								
							case '>':
								$oper = self::OPER_GT;
								break;
								
							case '>=':
								$oper = self::OPER_GTE;
								break;
								
							case '<':
								$oper = self::OPER_LT;
								break;
								
							case '<=':
								$oper = self::OPER_LTE;
								break;
								
							default:
								break;
						}
						
						$value = intval($value);
					}
					break;
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
	}
	
	public static function getWorkerParamFromTokens($field_key, $tokens, $search_field) {
		// [TODO] This can have placeholders
		
		$oper = self::OPER_IN;
		$not = false;
		$value = null;
		$terms = [];
		
		if(is_array($tokens))
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_NOT':
					$not = !$not;
					break;
					
				case 'T_ARRAY':
					$oper = $not ? DevblocksSearchCriteria::OPER_NIN : DevblocksSearchCriteria::OPER_IN;
					$terms = DevblocksPlatform::sanitizeArray($token->value, 'int');
					break;
					
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					$oper = ($not) ? self::OPER_NIN : self::OPER_IN;
					$terms = DevblocksPlatform::parseCsvString($token->value);
					break;
			}
		}
		
		if(1 == count($terms) && in_array(DevblocksPlatform::strLower($terms[0]), ['any','anyone','anybody'])) {
			@$is_cfield = $search_field['options']['cf_id'];
			if($is_cfield) {
				$oper = self::OPER_IS_NOT_NULL;
				$value = null;
			} else {
				$oper = self::OPER_NEQ;
				$value = 0;
			}
			
		} else if(1 == count($terms) && in_array(DevblocksPlatform::strLower($terms[0]), ['blank','empty','no','none','noone','nobody'])) {
			@$is_cfield = $search_field['options']['cf_id'];
			if($is_cfield) {
				$oper = self::OPER_IS_NULL;
				$value = null;
			} else {
				$oper = self::OPER_EQ;
				$value = 0;
			}
			
		} else {
			$active_worker = CerberusApplication::getActiveWorker();
			$workers = DAO_Worker::getAll();
				
			$worker_ids = [];
			
			if(is_array($terms))
			foreach($terms as $term) {
				if(is_numeric($term) && (empty($term) || isset($workers[$term]))) {
					$worker_ids[intval($term)] = true;
					continue;
				
				} elseif($active_worker && 0 == strcasecmp($term, 'me')) {
					$worker_ids[$active_worker->id] = true;
					continue;
				}
				
				foreach($workers as $worker_id => $worker) {
					if(isset($worker_ids[$worker_id]))
						continue;
					
					if(false !== stristr($worker->getName(), $term)) {
						$worker_ids[$worker_id] = true;
					}
				}
			}
			
			if(!empty($worker_ids)) {
				$value = array_keys($worker_ids);
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
	}
	
	public static function getVirtualQuickSearchParamFromTokens($field_key, $tokens, $search_field_key) {
		$query = CerbQuickSearchLexer::getTokensAsQuery($tokens);
		
		return new DevblocksSearchCriteria(
			$search_field_key,
			DevblocksSearchCriteria::OPER_CUSTOM,
			sprintf('%s', $query)
		);
	}
	
	public static function getVirtualContextParamFromTokens($field_key, $tokens, $prefix, $search_field_key) {
		// Is this a nested subquery?
		if(DevblocksPlatform::strStartsWith($field_key, $prefix.'.')) {
			@list(, $alias) = explode('.', $field_key);
			
			$query = CerbQuickSearchLexer::getTokensAsQuery($tokens);
			
			return new DevblocksSearchCriteria(
				$search_field_key,
				DevblocksSearchCriteria::OPER_CUSTOM,
				sprintf('%s:%s', $alias, $query)
			);
			
		} else {
			$aliases = Extension_DevblocksContext::getAliasesForAllContexts();
			$link_contexts = [];
			
			$oper = null;
			$value = null;
			CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
			
			if(is_array($value) && 1 == count($value)) {
				if(in_array($value[0], ['*','yes','y','true','any','y'])) {
					return new DevblocksSearchCriteria(
						$search_field_key,
						DevblocksSearchCriteria::OPER_IS_NOT_NULL,
						[]
					);
				}
				
				if(in_array($value[0], ['null','no','n','none'])) {
					return new DevblocksSearchCriteria(
						$search_field_key,
						DevblocksSearchCriteria::OPER_IS_NULL,
						[]
					);
				}
			}

			$opers_valid = [
				DevblocksSearchCriteria::OPER_IN => true,
				DevblocksSearchCriteria::OPER_NIN => true,
			];
			
			if(!array_key_exists($oper, $opers_valid))
				$oper = DevblocksSearchCriteria::OPER_IN;
			
			if(is_array($value))
			foreach($value as $alias) {
				if(isset($aliases[$alias]))
					$link_contexts[$aliases[$alias]] = true;
			}
			
			return new DevblocksSearchCriteria(
				$search_field_key,
				$oper,
				array_keys($link_contexts)
			);
		}
	}
	
	public static function getContextLinksParamFromTokens($field_key, $tokens) {
		// Is this a nested subquery?
		if(DevblocksPlatform::strStartsWith($field_key,'links.')) {
			@list(, $alias) = explode('.', $field_key);
			
			$query = CerbQuickSearchLexer::getTokensAsQuery($tokens);
			
			return new DevblocksSearchCriteria(
				'*_context_link',
				DevblocksSearchCriteria::OPER_CUSTOM,
				sprintf('%s:%s', $alias, $query)
			);
			
		} else {
			$aliases = Extension_DevblocksContext::getAliasesForAllContexts();
			$link_contexts = [];
			
			$oper = null;
			$value = null;
			CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
			
			if(is_array($value) && 1 == count($value)) {
				if(in_array($value[0], ['*','yes','y','true','any','y'])) {
					return new DevblocksSearchCriteria(
						'*_context_link',
						DevblocksSearchCriteria::OPER_IS_NOT_NULL,
						[]
					);
				}
				
				if(in_array($value[0], ['null','no','n','none'])) {
					return new DevblocksSearchCriteria(
						'*_context_link',
						DevblocksSearchCriteria::OPER_IS_NULL,
						[]
					);
				}
			}
			
			if(is_array($value))
			foreach($value as $alias) {
				if(isset($aliases[$alias]))
					$link_contexts[$aliases[$alias]] = true;
			}
			
			return new DevblocksSearchCriteria(
				'*_context_link',
				DevblocksSearchCriteria::OPER_IN,
				array_keys($link_contexts)
			);
		}
	}
	
	public static function getWatcherParamFromTokens($field_key, $tokens) {
		$oper = self::OPER_IN;
		$not = false;
		$value = null;
		$terms = null;
		
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_NOT':
					$not = !$not;
					break;
					
				case 'T_ARRAY':
					$oper = ($not) ? self::OPER_NIN : self::OPER_IN;
					$terms = $token->value;
					break;
					
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					$oper = ($not) ? self::OPER_NIN : self::OPER_IN;
					$terms = DevblocksPlatform::parseCsvString($token->value);
					break;
			}
		}
		
		if(1 == count($terms) && in_array(DevblocksPlatform::strLower($terms[0]), array('any','yes'))) {
			$oper = self::OPER_IS_NOT_NULL;
			$value = array();
			
		} else if(1 == count($terms) && in_array(DevblocksPlatform::strLower($terms[0]), array('none','no'))) {
			$oper = self::OPER_IS_NULL;
			$value = array();
			
		} else {
			$active_worker = CerberusApplication::getActiveWorker();
			$workers = DAO_Worker::getAllActive();
				
			$worker_ids = array();
			
			if(is_array($terms))
			foreach($terms as $term) {
				if(is_numeric($term) && isset($workers[$term])) {
					$worker_ids[intval($term)] = true;
				
				} elseif($active_worker && 0 == strcasecmp($term, 'me')) {
					$worker_ids[$active_worker->id] = true;
					continue;
				}
				
				foreach($workers as $worker_id => $worker) {
					if(isset($worker_ids[$worker_id]))
						continue;
					
					if(false !== stristr($worker->getName(), $term)) {
						$worker_ids[$worker_id] = true;
					}
				}
			}
			
			if(!empty($worker_ids)) {
				$value = array_keys($worker_ids);
			} else {
				$value = array(-1);
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
	}
	
	public static function getContextAliasParamFromTokens($field_key, $tokens) {
		$oper = self::OPER_IN;
		$not = false;
		$value = null;
		
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_NOT':
					$not = !$not;
					break;
					
				case 'T_ARRAY':
					$oper = ($not) ? self::OPER_NIN : self::OPER_IN;
					$value = $token->value;
					break;
					
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					$oper = ($not) ? self::OPER_NEQ : self::OPER_EQ;
					$value = $token->value;
					break;
			}
		}
		
		$param = new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
		
		return $param;
	}

	public static function getFulltextParamFromTokens($field_key, $tokens) {
		$terms = array();
		
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_QUOTED_TEXT':
					$terms[] = '"' . $token->value . '"';
					break;
					
				case 'T_TEXT':
					$terms[] = $token->value;
					break;
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			DevblocksSearchCriteria::OPER_FULLTEXT,
			array(
				implode(' ', $terms),
				'expert'
			)
		);
	}
	
	public static function getTextParamFromTokens($field_key, $tokens, $options=0) {
		$oper = DevblocksSearchCriteria::OPER_EQ;
		$value = null;
		$not = false;
		
		if(is_array($tokens))
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_NOT':
					$not = true;
					break;
					
				case 'T_ARRAY':
					$oper = $not ? DevblocksSearchCriteria::OPER_NIN : DevblocksSearchCriteria::OPER_IN;
					$value = $token->value;
					break;
					
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					if(false !== strpos($token->value, '*')) {
						$oper = $not ? DevblocksSearchCriteria::OPER_NOT_LIKE : DevblocksSearchCriteria::OPER_LIKE;
						$value = $token->value;
						
					} else {
						$oper = $not ? DevblocksSearchCriteria::OPER_NEQ : DevblocksSearchCriteria::OPER_EQ;
						$value = $token->value;
						
						if($token->type == 'T_TEXT') {
							if($options & self::OPTION_TEXT_PARTIAL) {
								$oper = $not ? self::OPER_NOT_LIKE : self::OPER_LIKE;
								$value = sprintf('*%s*', $value);
								
							} elseif($options & self::OPTION_TEXT_PREFIX) {
								$oper = $not ? self::OPER_NOT_LIKE : self::OPER_LIKE;
								$value = sprintf('%s*', $value);
							}
						}
					}
					break;
			}
		}
		
		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
	}
	
	public function getWhereSQL($fields, $pkey) {
		if(isset($this->where_sql))
			return $this->where_sql;
		
		$db = DevblocksPlatform::services()->database();
		$where = '';
		
		if(!isset($fields[$this->field]))
			return '';
		
		if($fields[$this->field]->db_table) {
			$db_field_name = $fields[$this->field]->db_table . '.' . $fields[$this->field]->db_column;
		} else {
			$db_field_name = $fields[$this->field]->db_column;
		}
		
		// This should be handled by SearchFields_*::getWhereSQL()
		if('*_' == substr($this->field,0,2)) {
			return '';
		}
		
		// [JAS]: Operators
		switch($this->operator) {
			case "eq":
			case "=":
				$where = sprintf("%s = %s",
					$db_field_name,
					self::_escapeSearchParam($this, $fields)
				);
				break;
				
			case DevblocksSearchCriteria::OPER_EQ_OR_NULL:
				$val = self::_escapeSearchParam($this, $fields);

				if(is_string($val)) {
					$where = sprintf("(%s = %s OR %s IS NULL)",
						$db_field_name,
						$val,
						$db_field_name
					);
				} else {
					$where = sprintf("%s IS NULL",
						$db_field_name
					);
				}
				break;
				
			case "neq":
			case "!=":
				$where = sprintf("%s != %s",
					$db_field_name,
					self::_escapeSearchParam($this, $fields)
				);
				break;
			
			case "in":
				if(!is_array($this->value) && !is_string($this->value)) {
					$where = '0';
					break;
				}
				
				if(!is_array($this->value) && preg_match('#^\[.*\]$#', $this->value)) {
					$values = json_decode($this->value, true);
					
				} elseif(is_array($this->value)) {
					$values = $this->value;
					
				} else {
					$values = array($this->value);
					
				}
				
				// Escape quotes
				$vals = $db->qstrArray(DevblocksPlatform::sanitizeArray($values, 'string'));
				
				if(0 == count($vals))
					$vals = array(-1);
				
				$where = sprintf("%s IN (%s)",
					$db_field_name,
					implode(",", $vals)
				);
				break;
				
			case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				if(!is_array($this->value) && !is_string($this->value)) {
					$where = '0';
					break;
				}
				
				if(!is_array($this->value) && preg_match('#^\[.*\]$#', $this->value)) {
					$values = json_decode($this->value, true);
					
				} elseif(is_array($this->value)) {
					$values = $this->value;
					
				} else {
					$values = array($this->value);
					
				}
				
				$vals = array();
				
				// Escape quotes
				foreach($values as $idx=>$val) {
					$vals[$idx] = $db->qstr((string)$val);
				}

				$where_in = '';
				
				if(empty($vals)) {
					$where_in = '';
					
				} else {
					$where_in = sprintf("%s IN (%s) OR ",
						$db_field_name,
						implode(",",$vals)
					);
				}
				
				$where = sprintf("(%s%s IS NULL)",
					$where_in,
					$db_field_name
				);
				break;

			case DevblocksSearchCriteria::OPER_NIN: // 'not in'
				if(!is_array($this->value) && !is_string($this->value)) {
					$where = '0';
					break;
				}
					
				if(!is_array($this->value) && preg_match('#^\[.*\]$#', $this->value)) {
					$values = json_decode($this->value, true);
					
				} elseif(is_array($this->value)) {
					$values = $this->value;
					
				} else {
					$values = array($this->value);
					
				}
				
				$vals = array();
				
				// Escape quotes
				foreach($values as $idx=>$val) {
					$vals[$idx] = $db->qstr((string)$val);
				}

				if(empty($vals))
					$vals = array(-1);
				
				$has_multiple_values = false;
				
				if(substr($this->field, 0, 3) == 'cf_') {
					$field_id = substr($this->field, 3);
					$custom_field = DAO_CustomField::get($field_id);
					$field_value_table = DAO_CustomFieldValue::getValueTableName($field_id);
					$has_multiple_values = Model_CustomField::hasMultipleValues($custom_field->type);
				}
					
				$val_str = implode(",",$vals);

				$where = sprintf("%s NOT IN (%s)",
					$db_field_name,
					$val_str
				);
				
				if($has_multiple_values) {
					$where .= sprintf(" AND %s NOT IN (SELECT context_id FROM %s WHERE context = %s AND field_id = %d AND field_value IN (%s))",
						$pkey,
						$field_value_table,
						$db->qstr($custom_field->context),
						$field_id,
						$val_str
					);
				}
				
				break;
				
			case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
				if(!is_array($this->value) && !is_string($this->value)) {
					$where = '0';
					break;
				}
				
				if(!is_array($this->value) && preg_match('#^\[.*\]$#', $this->value)) {
					$values = json_decode($this->value, true);
					
				} elseif(is_array($this->value)) {
					$values = $this->value;
					
				} else {
					$values = array($this->value);
					
				}
				
				$vals = array();
				
				// Escape quotes
				foreach($values as $idx=>$val) {
					$vals[$idx] = $db->qstr((string)$val);
				}
				
				$where = '';
				
				if(empty($vals)) {
					
				} else {
					$has_multiple_values = false;
					
					if(substr($this->field, 0, 3) == 'cf_') {
						$field_id = substr($this->field, 3);
						$custom_field = DAO_CustomField::get($field_id);
						$field_value_table = DAO_CustomFieldValue::getValueTableName($field_id);
						$has_multiple_values = Model_CustomField::hasMultipleValues($custom_field->type);
					}
					
					$val_str = implode(",",$vals);
					
					$where = sprintf("(%s IS NULL OR %s NOT IN (%s))",
						$db_field_name,
						$db_field_name,
						$val_str
					);
						
					if($has_multiple_values) {
						$where .= sprintf(" AND %s NOT IN (SELECT context_id FROM %s WHERE context = %s AND field_id = %d AND field_value IN (%s))",
							$pkey,
							$field_value_table,
							$db->qstr($custom_field->context),
							$field_id,
							$val_str
						);
					}
				}
				break;
				
			case DevblocksSearchCriteria::OPER_LIKE: // 'like'
				$where = sprintf("%s LIKE %s",
					$db_field_name,
					str_replace('*','%',self::_escapeSearchParam($this, $fields))
				);
				break;
			
			case DevblocksSearchCriteria::OPER_NOT_LIKE: // 'not like'
				$where = sprintf("%s NOT LIKE %s",
					$db_field_name,
					str_replace('*','%',self::_escapeSearchParam($this, $fields))
				);
				break;
			
			case DevblocksSearchCriteria::OPER_IS_NULL: // 'is null'
				$where = sprintf("%s IS NULL",
					$db_field_name
				);
				break;
				
			case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
				$where = sprintf("%s IS NOT NULL",
					$db_field_name
				);
				break;
			
			case DevblocksSearchCriteria::OPER_TRUE:
				$where = '1';
				break;
				
			/*
			 * [TODO] Someday we may want to call this OPER_DATE_BETWEEN so it doesn't interfere
			 * with the operator in other uses
			 */
			case DevblocksSearchCriteria::OPER_BETWEEN: // 'between'
			case DevblocksSearchCriteria::OPER_NOT_BETWEEN: // 'not between'
				$not = $this->operator == DevblocksSearchCriteria::OPER_NOT_BETWEEN ? true : false;
				
				if(!is_array($this->value) || 2 != count($this->value)) {
					return 0;
					break;
				}
			
				$from_date = $this->value[0];
				$to_date = $this->value[1];
				
				if(!is_numeric($from_date) || !is_numeric($to_date)) {
					if(false == ($dates = DevblocksPlatform::services()->date()->parseDateRange($this->value))) {
						return 0;
						break;
					}
					
					$from_date = $dates['from_ts'];
					$to_date = $dates['to_ts'];
				}
				
				if(0 == $from_date) {
					if($not) {
						$where = sprintf("(%s IS NOT NULL AND %s NOT BETWEEN %s and %s)",
							$db_field_name,
							$db_field_name,
							$from_date,
							$to_date
						);
					} else {
						$where = sprintf("(%s IS NULL OR %s BETWEEN %s and %s)",
							$db_field_name,
							$db_field_name,
							$from_date,
							$to_date
						);
						
					}
				} else {
					$where = sprintf("%s %sBETWEEN %s and %s",
						$db_field_name,
						($not ? 'NOT ' : ''),
						$from_date,
						$to_date
					);
				}
				break;
			
			case DevblocksSearchCriteria::OPER_GT:
			case DevblocksSearchCriteria::OPER_GTE:
			case DevblocksSearchCriteria::OPER_LT:
			case DevblocksSearchCriteria::OPER_LTE:
				$where = sprintf("%s %s %s",
					$db_field_name,
					$this->operator,
					self::_escapeSearchParam($this, $fields)
				);
				break;
			
			case DevblocksSearchCriteria::OPER_CUSTOM:
				if(array_key_exists('sql', $this->value)) {
					$where = sprintf($this->value['sql'], $db_field_name);
					
				} else if(array_key_exists('where', $this->value)) {
					$where = sprintf("%s %s",
						$db_field_name,
						$this->value['where']
					);
				} else {
					return 0;
				}
				break;
				
			case DevblocksSearchCriteria::OPER_GEO_POINT_EQ:
			case DevblocksSearchCriteria::OPER_GEO_POINT_NEQ:
				if(!is_array($this->value))
					return 0;
				
				$where = sprintf("%s %s POINT(%f,%f)",
					$db_field_name,
					$this->operator == DevblocksSearchCriteria::OPER_GEO_POINT_NEQ ? '!=' : '=',
					$this->value['longitude'],
					$this->value['latitude']
				);
				break;
			
			default:
				break;
		}
		
		return $where;
	}
	
	static protected function _escapeSearchParam(DevblocksSearchCriteria $param, $fields) {
		$db = DevblocksPlatform::services()->database();
		$field = $fields[$param->field];
		$where_value = null;

		if($field) {
			if(!is_array($param->value)) {
				$where_value = $db->qstr($param->value);
			} else {
				$where_value = array();
				foreach($param->value as $v) {
					$where_value[] = $db->qstr($v);
				}
			}
		}

		return $where_value;
	}
};

class DevblocksSearchField {
	public $token;
	public $db_table;
	public $db_column;
	public $db_label;
	public $type;
	public $is_sortable = false;
	
	function __construct($token, $db_table, $db_column, $label=null, $type=null, $is_sortable=false) {
		$this->token = $token;
		$this->db_table = $db_table;
		$this->db_column = $db_column;
		$this->db_label = $label;
		$this->type = $type;
		$this->is_sortable = $is_sortable;
	}
	
	static function getCustomSearchFieldsByContexts($contexts) {
		if(!is_array($contexts))
			$contexts = array($contexts);
		
		$columns = array();
		$custom_fieldsets = DAO_CustomFieldset::getAll();

		foreach($contexts as $context) {
			$custom_fields = DAO_CustomField::getByContext($context);
	
			if(is_array($custom_fields))
			foreach($custom_fields as $field_id => $field) {
				$key = 'cf_'.$field_id;
				$label = $field->name;
				
				if(!empty($field->custom_fieldset_id) && isset($custom_fieldsets[$field->custom_fieldset_id])) {
					$label = $custom_fieldsets[$field->custom_fieldset_id]->name . ' ' . $label;
				}
				
				$columns[$key] = new DevblocksSearchField(
					$key, // token
					$key, // table
					'field_value', // column
					$label, // label
					$field->type, // type
					true // is sortable // [TODO] By type?
				);
			}
		}
		
		return $columns;
	}
};

class DevblocksAclPrivilege {
	public $id = '';
	public $plugin_id = '';
	public $label = '';
};

class DevblocksEventPoint {
	public $id = '';
	public $plugin_id = '';
	public $name = '';
	public $param = array();
};

class DevblocksExtensionPoint {
	public $id = '';
	public $plugin_id = '';
	public $extensions = array();
};

class DevblocksTemplate {
	public $set = '';
	public $plugin_id = '';
	public $path = '';
	public $sort_key = '';
};

/**
 * Manifest information for plugin.
 * @ingroup plugin
 */
class DevblocksPluginManifest {
	public $id = '';
	public $enabled = 0;
	public $name = '';
	public $description = '';
	public $author = '';
	public $version = 0;
	public $link = '';
	public $dir = '';
	public $manifest_cache = [];
	
	public $extension_points = [];
	public $event_points = [];
	public $acl_privs = [];
	public $class_loader = [];
	public $extensions = [];
	
	public $_requirements_errors = [];
	
	function setEnabled($bool) {
		$this->enabled = ($bool) ? 1 : 0;
		
		// Persist to DB
		$fields = array(
			'enabled' => $this->enabled
		);
		DAO_Platform::updatePlugin($this->id, $fields);
	}
	
	function getStoragePath() {
		if($this->dir == 'libs/devblocks') {
			return rtrim(DEVBLOCKS_PATH, DIRECTORY_SEPARATOR);
			
		} elseif(DevblocksPlatform::strStartsWith($this->dir, 'features/')) {
			return APP_PATH . '/features/' . $this->id;
			
		} elseif(DevblocksPlatform::strStartsWith($this->dir, 'plugins/')) {
			return APP_PATH . '/plugins/' . $this->id;
			
		} elseif(DevblocksPlatform::strStartsWith($this->dir, 'storage/plugins/')) {
			return APP_STORAGE_PATH . '/plugins/' . $this->id;
		}
		
		return false;
	}
	
	/**
	 *
	 */
	function getActivityPoints() {
		$points = array();

		if(isset($this->manifest_cache['activity_points']))
		foreach($this->manifest_cache['activity_points'] as $point=> $data) {
			$points[$point] = $data;
		}
		
		return $points;
	}
	
	/**
	 * return DevblocksPatch[]
	 */
	function getPatches() {
		$patches = array();
		
		if(isset($this->manifest_cache['patches']))
		foreach($this->manifest_cache['patches'] as $patch) {
			$path = $this->getStoragePath() . '/' . $patch['file'];
			$patches[] = new DevblocksPatch($this->id, $patch['version'], $patch['revision'], $path);
		}
		
		return $patches;
	}
	
	function checkRequirements() {
		$this->_requirements_errors = array();
		
		switch($this->id) {
			case 'devblocks.core':
			case 'cerberusweb.core':
				return true;
				break;
		}
		
		// Check version information
		if(
			null != (@$plugin_app_version = $this->manifest_cache['requires']['app_version'])
			&& isset($plugin_app_version['min'])
			&& isset($plugin_app_version['max'])
		) {
			// If APP_VERSION is below the min or above the max
			if(DevblocksPlatform::strVersionToInt(APP_VERSION) < DevblocksPlatform::strVersionToInt($plugin_app_version['min']))
				$this->_requirements_errors[] = 'This plugin requires a Cerb version of at least ' . $plugin_app_version['min'] . ' and you are using ' . APP_VERSION;
			
			if(DevblocksPlatform::strVersionToInt(APP_VERSION) > DevblocksPlatform::strVersionToInt($plugin_app_version['max']))
				$this->_requirements_errors[] = 'This plugin was tested through Cerb version ' . $plugin_app_version['max'] . ' and you are using ' . APP_VERSION;
			
		// If no version information is available, fail.
		} else {
			$this->_requirements_errors[] = 'This plugin is missing requirements information in its manifest';
		}
		
		// Check PHP extensions
		if(isset($this->manifest_cache['requires']['php_extensions']))
		foreach(array_keys($this->manifest_cache['requires']['php_extensions']) as $php_extension) {
			if(!extension_loaded($php_extension))
				$this->_requirements_errors[] = sprintf("The '%s' PHP extension is required", $php_extension);
		}
		
		// Check dependencies
		if(isset($this->manifest_cache['dependencies'])) {
			$plugins = DevblocksPlatform::getPluginRegistry();
			foreach($this->manifest_cache['dependencies'] as $dependency) {
				if(!isset($plugins[$dependency])) {
					$this->_requirements_errors[] = sprintf("The '%s' plugin is required", $dependency);
				} else if(!$plugins[$dependency]->enabled) {
					$dependency_name = isset($plugins[$dependency]) ? $plugins[$dependency]->name : $dependency;
					$this->_requirements_errors[] = sprintf("The '%s' (%s) plugin must be enabled first", $dependency_name, $dependency);
				}
			}
		}
		
		// Status
		
		if(!empty($this->_requirements_errors))
			return false;
		
		return true;
	}
	
	function getRequirementsErrors() {
		if(empty($this->_requirements_errors))
			$this->checkRequirements();
		
		return $this->_requirements_errors;
	}
	
	function purge() {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("DELETE FROM cerb_plugin WHERE id = %s",
			$db->qstr($this->id)
		));
		$db->ExecuteMaster(sprintf("DELETE FROM cerb_extension WHERE plugin_id = %s",
			$db->qstr($this->id)
		));
		
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id NOT IN (SELECT id FROM cerb_extension)");
	}
	
	function uninstall() {
		$plugin_path = $this->getStoragePath();
		$storage_path = APP_STORAGE_PATH . '/plugins/';
		
		// Only delete the files if the plugin is in the storage filesystem.
		if(0 == substr_compare($plugin_path, $storage_path, 0, strlen($storage_path), true)) {
			$this->_recursiveDelTree($plugin_path);
			return true;
		}
		
		return false;
	}
	
	function _recursiveDelTree($dir) {
		if(!file_exists($dir) || !is_dir($dir))
			return false;
		
		$storage_path = APP_STORAGE_PATH . '/plugins/';
		$dir = rtrim($dir,"/\\") . '/';
		
		if(0 != substr_compare($storage_path, $dir, 0, strlen($storage_path)))
			return false;
		
		$files = glob($dir . '*', GLOB_MARK);
		foreach($files as $file) {
			if(is_dir($file)) {
				$this->_recursiveDelTree($file);
			} else {
				unlink($file);
			}
		}
		
		if(file_exists($dir) && is_dir($dir))
			rmdir($dir);
		
		return true;
	}
};

/**
 * Manifest information for a plugin's extension.
 * @ingroup plugin
 */
class DevblocksExtensionManifest {
	public $id = '';
	public $plugin_id ='';
	public $point = '';
	public $name = '';
	public $file = '';
	public $class = '';
	public $params = array();

	/**
	 * Creates and loads a usable extension from a manifest record.  The object returned
	 * will be of type $class defined by the manifest.
	 *
	 * @return object
	 */
	function createInstance() {
		if(empty($this->id) || empty($this->plugin_id))
			return null;
		
		if(null == ($plugin = DevblocksPlatform::getPlugin($this->plugin_id)))
			return;
		
		$class_file = $plugin->getStoragePath() . '/' . $this->file;
		$class_name = $this->class;
		
		DevblocksPlatform::registerClasses($class_file, [$class_name]);
		
		if(!class_exists($class_name, true)) {
			return null;
		}
		
		$instance = new $class_name($this);
		return $instance;
	}
	
	/**
	 * @return DevblocksPluginManifest
	 */
	function getPlugin() {
		$plugin = DevblocksPlatform::getPlugin($this->plugin_id);
		return $plugin;
	}
	
	function getParams() {
		return DAO_DevblocksExtensionPropertyStore::getByExtension($this->id);
	}
	
	function setParam($key, $value) {
		return DAO_DevblocksExtensionPropertyStore::put($this->id, $key, $value);
	}
	
	function getParam($key, $default=null) {
		return DAO_DevblocksExtensionPropertyStore::get($this->id, $key, $default);
	}
	
	/**
	 * 
	 * @param string $key
	 * @return boolean
	 */
	function hasOption($key) {
		@$options = $this->params['options'][0] ?: [];
		return array_key_exists($key, $options);
	}
};

/**
 * A single session instance
 *
 * @ingroup core
 * [TODO] Evaluate if this is even needed, or if apps can have their own unguided visit object
 */
abstract class DevblocksVisit {
	private $registry = array();
	
	public function exists($key) {
		return isset($this->registry[$key]);
	}
	
	public function get($key, $default=null) {
		@$value = $this->registry[$key];
		
		if(is_null($value) && !is_null($default))
			$value = $default;
			
		return $value;
	}
	
	public function append($key, $object) {
		if(!array_key_exists($key, $this->registry))
			$this->registry[$key] = [];
		
		if(!is_array($this->registry[$key]))
			$this->registry[$key] = [$this->registry[$key]];
		
		$this->registry[$key][] = $object;
	}
	
	public function set($key, $object) {
		$this->registry[$key] = $object;
	}
	
	public function remove($key) {
		unset($this->registry[$key]);
	}
};

/**
 *
 */
class DevblocksPatch {
	private $plugin_id = ''; // cerberusweb.core
	private $version = '';
	private $revision = 0; // 100
	private $filename = ''; // 4.0.0.php
	
	public function __construct($plugin_id, $version, $revision, $filename) {
		$this->plugin_id = $plugin_id;
		$this->version = $version;
		$this->revision = intval($revision);
		$this->filename = $filename;
	}
	
	public function run() {
		if($this->hasRun())
			return TRUE;

		if(empty($this->filename) || !file_exists($this->filename))
			return FALSE;

		if(false === (require_once($this->filename)))
			return FALSE;
		
		DAO_Platform::setPatchRan($this->plugin_id, $this->revision);
		
		return TRUE;
	}
	
	/**
	 * @return boolean
	 */
	public function hasRun() {
		// Compare PLUGIN_ID + REVISION in script history
		return DAO_Platform::hasPatchRun($this->plugin_id,$this->revision);
	}
	
	public function getPluginId() {
		return $this->plugin_id;
	}
	
	public function getFilename() {
		return $this->filename;
	}
	
	public function getVersion() {
		return $this->version;
	}
	
	public function getRevision() {
		return $this->revision;
	}
	
};

class Model_DevblocksEvent {
	public $id = '';
	public $params = [];

	function __construct($id='',$params=[]) {
		$this->id = $id;
		$this->params = $params;
	}
};

class Model_Translation {
	public $id;
	public $string_id;
	public $lang_code;
	public $string_default;
	public $string_override;
};

class Model_DevblocksStorageProfile {
	public $id;
	public $name;
	public $extension_id;
	public $params_json;
	public $params = [];
	
	function getUsageStats() {
		// Schemas
		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true);
		
		// Stats
		$storage_schema_stats = [];
		foreach($storage_schemas as $schema) {
			$stats = $schema->getStats();
			$key = $this->extension_id . ':' . intval($this->id);
			if(isset($stats[$key]))
				$storage_schema_stats[$schema->id] = $stats[$key];
		}
		
		return $storage_schema_stats;
	}
};
