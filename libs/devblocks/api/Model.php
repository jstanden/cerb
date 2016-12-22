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

interface IDevblocksSearchFields {
	static function getFields();
	static function getPrimaryKey();
	static function getCustomFieldContextKeys();
	static function getWhereSQL(DevblocksSearchCriteria $param);
}

abstract class DevblocksSearchFields implements IDevblocksSearchFields {
	static function getCustomFieldContextData($context) {
		$map = static::getCustomFieldContextKeys();
		
		if(!isset($map[$context]))
			return false;
		
		return $map[$context];
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
		
		if(false === ($ids = $search->query($query, array('context_crc32' => sprintf("%u", crc32($from_context)))))) {
			return '0';
		
		} elseif(is_array($ids)) {
			$from_ids = DAO_Comment::getContextIdsByContextAndIds($from_context, $ids);
			
			return sprintf('%s IN (%s)',
				$pkey,
				implode(', ', (!empty($from_ids) ? $from_ids : array(-1)))
			);
			
		} elseif(is_string($ids)) {
			return sprintf("%s IN (SELECT context_id FROM comment INNER JOIN %s ON (%s.id=comment.id))",
				$pkey,
				$ids,
				$ids
			);
		}
	}
	
	static function _getWhereSQLFromAttachmentsField(DevblocksSearchCriteria $param, $context, $join_key) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			$query = $param->value;
			
			if(false == ($ext_attachments = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_ATTACHMENT)))
				return;
			
			if(false == ($ext = Extension_DevblocksContext::get($context)))
				return;
			
			$view = $ext_attachments->getSearchView(uniqid());
			$view->is_ephemeral = true;
			$view->setAutoPersist(false);
			$view->addParamsWithQuickSearch($query, true);
			
			$params = $view->getParams();
			
			$query_parts = DAO_Attachment::getSearchQueryComponents(array(), $params);
			
			$query_parts['select'] = sprintf("SELECT %s ", SearchFields_Attachment::getPrimaryKey());
			
			$sql = 
				$query_parts['select']
				. $query_parts['join']
				. $query_parts['where']
				. $query_parts['sort']
				;
			
			return sprintf("%s IN (SELECT context_id FROM attachment_link WHERE attachment_id IN (%s)) ",
				Cerb_OrmHelper::escape($join_key),
				$sql
			);
		}
	}
	
	static function _getWhereSQLFromVirtualSearchSqlField(DevblocksSearchCriteria $param, $context, $subquery_sql) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			$query = $param->value;
			
			if(false == ($ext = Extension_DevblocksContext::get($context)))
				return;
			
			$view = $ext->getSearchView(uniqid());
			$view->is_ephemeral = true;
			$view->setAutoPersist(false);
			$view->addParamsWithQuickSearch($query, true);
			
			$params = $view->getParams();
			
			if(false == ($dao_class = $ext->getDaoClass()))
				return;
			
			if(false == ($search_class = $ext->getSearchClass()))
				return;
			
			$query_parts = $dao_class::getSearchQueryComponents(array(), $params);
			
			$query_parts['select'] = sprintf("SELECT %s ", $search_class::getPrimaryKey());
			
			$sql = 
				$query_parts['select']
				. $query_parts['join']
				. $query_parts['where']
				. $query_parts['sort']
				;
			
			return sprintf($subquery_sql, $sql);
		}
	}
	
	static function _getWhereSQLFromVirtualSearchField(DevblocksSearchCriteria $param, $context, $join_key) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			$query = $param->value;
			
			if(false == ($ext = Extension_DevblocksContext::get($context)))
				return;
			
			$view = $ext->getSearchView(uniqid());
			$view->is_ephemeral = true;
			$view->setAutoPersist(false);
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
			
			return sprintf("%s IN (%s) ",
				Cerb_OrmHelper::escape($join_key),
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
			
			$view = $ext->getSearchView(uniqid());
			$view->is_ephemeral = true;
			$view->setAutoPersist(false);
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
			
			return sprintf("%s = %s AND %s IN (%s) ",
				Cerb_OrmHelper::escape($context_field),
				Cerb_ORMHelper::qstr($ext->id),
				Cerb_OrmHelper::escape($context_id_field),
				$sql
			);
		}
		
		if(!is_array($param->value))
			return '0';
		
		$wheres = array();
			
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
				$wheres[] = sprintf("(%s = %s)",
					Cerb_ORMHelper::escape($context_field),
					Cerb_ORMHelper::qstr($context)
				);
			}
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
			
			$view = $ext->getSearchView(uniqid());
			$view->is_ephemeral = true;
			$view->setAutoPersist(false);
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
			
			return sprintf("%s IN (SELECT from_context_id FROM context_link cl WHERE from_context = %s AND to_context = %s AND to_context_id IN (%s)) ",
				$pkey,
				Cerb_ORMHelper::qstr($from_context),
				Cerb_ORMHelper::qstr($ext->id),
				$sql
			);
		}
		
		if($param->operator != DevblocksSearchCriteria::OPER_TRUE) {
			if(empty($param->value) || !is_array($param->value))
				$param->operator = DevblocksSearchCriteria::OPER_IS_NULL;
		}
		
		$where_contexts = array();
		
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
				break;
	
			case DevblocksSearchCriteria::OPER_IS_NULL:
				/*
				$where_sql .= sprintf("AND (SELECT count(*) FROM context_link WHERE context_link.to_context=%s AND context_link.to_context_id=%s) = 0 ",
					self::qstr($to_context),
					$to_index
				);
				*/
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
	
	static function _getWhereSQLFromCustomFields($param) {
		if(0 == ($field_id = intval(substr($param->field,3))))
			return 0;
		
		if(false == ($field = DAO_CustomField::get($field_id)))
			return 0;

		$field_table = sprintf("cf_%d", $field_id);
		$value_table = DAO_CustomFieldValue::getValueTableName($field_id);
		$field_key = $param->field;
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
				
			default:
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
	
	const GROUP_OR = 'OR';
	const GROUP_AND = 'AND';
	
	const TYPE_BOOL = 'bool';
	const TYPE_DATE = 'date';
	const TYPE_FULLTEXT = 'fulltext';
	const TYPE_NUMBER = 'number';
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
	
	public static function getParamFromQueryFieldTokens($field, $tokens, $meta) {
		$search_fields = $meta;
		@$search_field = $search_fields[$field];
		
		// Only parse valid fields
		if(!$search_field || !isset($search_field['type']))
			return false;

		@$param_key = $search_fields[$field]['options']['param_key'];
		
		switch($search_field['type']) {
			case DevblocksSearchCriteria::TYPE_BOOL:
				if($param_key && false != ($param = DevblocksSearchCriteria::getBooleanParamFromTokens($param_key, $tokens)))
					return $param;
				continue;
				
			case DevblocksSearchCriteria::TYPE_DATE:
				if($param_key && false != ($param = DevblocksSearchCriteria::getDateParamFromTokens($param_key, $tokens)))
					return $param;
				continue;
				
			case DevblocksSearchCriteria::TYPE_FULLTEXT:
				if($param_key && false != ($param = DevblocksSearchCriteria::getFulltextParamFromTokens($param_key, $tokens)))
					return $param;
				continue;
				
			case DevblocksSearchCriteria::TYPE_NUMBER:
				if($param_key && false != ($param = DevblocksSearchCriteria::getNumberParamFromTokens($param_key, $tokens)))
					return $param;
				continue;
				
			case DevblocksSearchCriteria::TYPE_TEXT:
				@$match_type = $search_field['options']['match'];
				
				if($param_key && false != ($param = DevblocksSearchCriteria::getTextParamFromTokens($param_key, $tokens, $match_type)))
					return $param;
				continue;
				
			case DevblocksSearchCriteria::TYPE_WORKER:
				if($param_key && false != ($param = DevblocksSearchCriteria::getWorkerParamFromTokens($param_key, $tokens, $search_field)))
					return $param;
				continue;
		}
		
		return false;
	}
	
	public static function getDateParamFromTokens($field_key, $tokens) {
		// [TODO] Add more operators, for now we assume it's always '[date] to [date]' format
		// [TODO] If not a range search, and not a relative start point, we could treat this as an absolute (=)
		// [TODO] Handle >=, >, <=, <, =, !=
		
		$oper = DevblocksSearchCriteria::OPER_BETWEEN;
		$values = array();
		
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					if(0 == strcasecmp(trim($token->value), 'never') || empty($token->value)) {
						$oper = DevblocksSearchCriteria::OPER_EQ;
						$values = 'never';
						
					} else {
						$values = explode(' to ', strtolower($token->value), 2);
						
						if(1 == count($values))
							$values[] = 'now';
					}
					break;
			}
		}

		return new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$values
		);
	}
	
	public static function getBooleanParamFromTokens($field_key, $tokens) {
		$oper = DevblocksSearchCriteria::OPER_EQ;
		$value = true;
		
		foreach($tokens as $token) {
			switch($token->type) {
				case 'T_QUOTED_TEXT':
				case 'T_TEXT':
					if(false !== stristr($token->value, 'n')
						|| false !== stristr($token->value, 'f')
						|| $token->value == '0'
					) {
						$oper = DevblocksSearchCriteria::OPER_EQ_OR_NULL;
						$value = false;
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
		$terms = null;
		
		if(is_array($tokens))
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
		
		if(1 == count($terms) && in_array(strtolower($terms[0]), array('any','anyone','anybody'))) {
			@$is_cfield = $search_field['options']['cf_id'];
			if($is_cfield) {
				$oper = self::OPER_IS_NOT_NULL;
				$value = null;
			} else {
				$oper = self::OPER_NEQ;
				$value = 0;
			}
			
		} else if(1 == count($terms) && in_array(strtolower($terms[0]), array('blank','empty','no','none','noone','nobody'))) {
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
			$workers = DAO_Worker::getAllActive();
				
			$worker_ids = array();
			
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
					if(isset($workers_ids[$worker_id]))
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
		
		$param = new DevblocksSearchCriteria(
			$field_key,
			$oper,
			$value
		);
		
		return $param;
	}
	
	public static function getVirtualQuickSearchParamFromTokens($field_key, $tokens, $search_field_key) {
		$query = CerbQuickSearchLexer::getTokensAsQuery($tokens);
		
		$param = new DevblocksSearchCriteria(
			$search_field_key,
			DevblocksSearchCriteria::OPER_CUSTOM,
			sprintf('%s', $query)
		);
		return $param;
	}
	public static function getVirtualContextParamFromTokens($field_key, $tokens, $prefix, $search_field_key) {
		// Is this a nested subquery?
		if(DevblocksPlatform::strStartsWith($field_key, $prefix.'.')) {
			@list($null, $alias) = explode('.', $field_key);
			
			$query = CerbQuickSearchLexer::getTokensAsQuery($tokens);
			
			$param = new DevblocksSearchCriteria(
				$search_field_key,
				DevblocksSearchCriteria::OPER_CUSTOM,
				sprintf('%s:%s', $alias, $query)
			);
			return $param;
			
		} else {
			$aliases = Extension_DevblocksContext::getAliasesForAllContexts();
			$link_contexts = array();
			
			$oper = null;
			$value = null;
			CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
			
			if(is_array($value))
			foreach($value as $alias) {
				if(isset($aliases[$alias]))
					$link_contexts[$aliases[$alias]] = true;
			}
			
			$param = new DevblocksSearchCriteria(
				$search_field_key,
				DevblocksSearchCriteria::OPER_IN,
				array_keys($link_contexts)
			);
			return $param;
		}
	}
	
	public static function getContextLinksParamFromTokens($field_key, $tokens) {
		// Is this a nested subquery?
		if(substr($field_key,0,6) == 'links.') {
			@list($null, $alias) = explode('.', $field_key);
			
			$query = CerbQuickSearchLexer::getTokensAsQuery($tokens);
			
			$param = new DevblocksSearchCriteria(
				'*_context_link',
				DevblocksSearchCriteria::OPER_CUSTOM,
				sprintf('%s:%s', $alias, $query)
			);
			return $param;
			
		} else {
			$aliases = Extension_DevblocksContext::getAliasesForAllContexts();
			$link_contexts = array();
			
			$oper = null;
			$value = null;
			CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
			
			if(is_array($value))
			foreach($value as $alias) {
				if(isset($aliases[$alias]))
					$link_contexts[$aliases[$alias]] = true;
			}
			
			$param = new DevblocksSearchCriteria(
				'*_context_link',
				DevblocksSearchCriteria::OPER_IN,
				array_keys($link_contexts)
			);
			return $param;
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
		
		if(1 == count($terms) && in_array(strtolower($terms[0]), array('any','yes'))) {
			$oper = self::OPER_IS_NOT_NULL;
			$value = array();
			
		} else if(1 == count($terms) && in_array(strtolower($terms[0]), array('none','no'))) {
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
					if(isset($workers_ids[$worker_id]))
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
		
		$db = DevblocksPlatform::getDatabaseService();
		$where = '';
		
		if(!isset($fields[$this->field]))
			return '';
		
		$db_field_name = $fields[$this->field]->db_table . '.' . $fields[$this->field]->db_column;

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
				
				$vals = array();
				
				// Escape quotes
				foreach($values as $idx=>$val) {
					$vals[$idx] = $db->qstr((string)$val);
				}
				
				if(empty($vals))
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
				if(!is_numeric($from_date)) {
					// Translate periods into dashes on string dates
					if(false !== strpos($from_date,'.'))
						$from_date = str_replace(".", "-", $from_date);
						
					if(false === ($from_date = strtotime($from_date)))
						$from_date = 0;
				}
				
				$to_date = $this->value[1];
				
				if(!is_numeric($to_date)) {
					// Translate periods into dashes on string dates
					if(false !== strpos($to_date,'.'))
						$to_date = str_replace(".", "-", $to_date);
						
					if(false === ($to_date = strtotime($to_date)))
						$to_date = strtotime("now");
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
				$where = sprintf("%s %s",
					$db_field_name,
					$this->value['where']
				);
				break;
				
			default:
				break;
		}
		
		return $where;
	}
	
	static protected function _escapeSearchParam(DevblocksSearchCriteria $param, $fields) {
		$db = DevblocksPlatform::getDatabaseService();
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
	var $id = '';
	var $plugin_id = '';
	var $label = '';
};

class DevblocksEventPoint {
	var $id = '';
	var $plugin_id = '';
	var $name = '';
	var $param = array();
};

class DevblocksExtensionPoint {
	var $id = '';
	var $plugin_id = '';
	var $extensions = array();
};

class DevblocksTemplate {
	var $set = '';
	var $plugin_id = '';
	var $path = '';
	var $sort_key = '';
};

/**
 * Manifest information for plugin.
 * @ingroup plugin
 */
class DevblocksPluginManifest {
	var $id = '';
	var $enabled = 0;
	var $name = '';
	var $description = '';
	var $author = '';
	var $version = 0;
	var $link = '';
	var $dir = '';
	var $manifest_cache = array();
	
	var $extension_points = array();
	var $event_points = array();
	var $acl_privs = array();
	var $class_loader = array();
	var $extensions = array();
	
	var $_requirements_errors = array();
	
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
			
		} elseif(substr($this->dir, 0, 9) == 'features/') {
			return APP_PATH . '/features/' . $this->id;
			
		} else {
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
		foreach($this->manifest_cache['requires']['php_extensions'] as $php_extension => $data) {
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
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		$db->ExecuteMaster(sprintf("DELETE FROM %splugin WHERE id = %s",
			$prefix,
			$db->qstr($this->id)
		));
		$db->ExecuteMaster(sprintf("DELETE FROM %sextension WHERE plugin_id = %s",
			$prefix,
			$db->qstr($this->id)
		));
		
		$db->ExecuteMaster(sprintf("DELETE FROM %1\$sproperty_store WHERE extension_id NOT IN (SELECT id FROM %1\$sextension)", $prefix));
	}
	
	function uninstall() {
		if(!CERB_FEATURES_PLUGIN_LIBRARY)
			return false;
		
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
	var $id = '';
	var $plugin_id ='';
	var $point = '';
	var $name = '';
	var $file = '';
	var $class = '';
	var $params = array();

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

		DevblocksPlatform::registerClasses($class_file,array($class_name));

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

		if(false === ($result = require_once($this->filename)))
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
	public $params = array();

  function __construct($id='',$params=array()) {
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
	public $params = array();
	
	function getUsageStats() {
		// Schemas
		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true, true);
		
		// Stats
		$storage_schema_stats = array();
		foreach($storage_schemas as $schema) {
			$stats = $schema->getStats();
			$key = $this->extension_id . ':' . intval($this->id);
			if(isset($stats[$key]))
				$storage_schema_stats[$schema->id] = $stats[$key];
		}
		
		return $storage_schema_stats;
	}
};
