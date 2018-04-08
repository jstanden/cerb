<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
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

class DAO_CustomField extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const CUSTOM_FIELDSET_ID = 'custom_fieldset_id';
	const ID = 'id';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	const POS = 'pos';
	const TYPE = 'type';
	const UPDATED_AT = 'updated_at';
	
	const CACHE_ALL = 'ch_customfields';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::CUSTOM_FIELDSET_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CUSTOM_FIELDSET, true))
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::POS)
			->uint(2)
			->setMin(0)
			->setMax(100)
			;
		$validation
			->addField(self::TYPE)
			->string()
			->setMaxLength(1)
			->setRequired(true)
			->setPossibleValues(array_keys(Model_CustomField::getTypes()))
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
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
		
		$sql = "INSERT INTO custom_field () VALUES ()";
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
		
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELD;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CUSTOM_FIELD, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'custom_field', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.custom_field.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CUSTOM_FIELD, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('custom_field', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, $fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELD;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		@$custom_fieldset_id = $fields[self::CUSTOM_FIELDSET_ID];
		
		if(!$id || $custom_fieldset_id) {
			// On a fieldset
			if(!empty($custom_fieldset_id)) {
				if(false == ($fieldset = DAO_CustomFieldset::get($custom_fieldset_id))) {
					$error = "'custom_fieldset_id' is an invalid record.";
					return false;
				}
				
				if(!Context_CustomFieldset::isWriteableByActor($fieldset, $actor)) {
					$error = "You do not have permission to add fields to this custom fieldset.";
					return false;
				}
				
				// Verify that the field context matches the fieldset
				if(isset($fields[self::CONTEXT])) {
					if($fields[self::CONTEXT] != $fieldset->context) {
						$error = sprintf("The field type (%s) and fieldset type (%s) do not match.",
							$fields[self::CONTEXT],
							$fieldset->context
						);
						return false;
					}
				}
				
			// Global custom field
			} else {
				if(!CerberusContexts::isActorAnAdmin($actor)) {
					$error = "You do not have permission to add global custom fields.";
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CustomField[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT context, custom_fieldset_id, id, name, params_json, pos, type, updated_at ".
			"FROM custom_field ".
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
	 * @param integer $id
	 * @return Model_CustomField|null
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$fields = self::getAll();
		
		if(isset($fields[$id]))
			return $fields[$id];
			
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_CustomField[]
	 */
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return [];

		if(!method_exists(get_called_class(), 'getWhere'))
			return [];

		$db = DevblocksPlatform::services()->database();

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');

		$models = [];

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
	* Returns all of the fields for the specified context available to $group_id, including global fields
	*
	* @param string $context The context of the custom field
	* @param boolean $with_fieldsets Include fieldsets
	* @return Model_CustomField[]
	*/
	
	static function getByContext($context, $with_fieldsets=true, $with_fieldset_names=false) {
		$fields = self::getAll();
		$fieldsets = DAO_CustomFieldset::getAll();
		$results = array();

		// [TODO] Filter to the fieldsets the active worker is allowed to see
		
		// Filter fields to only the requested source
		foreach($fields as $idx => $field) { /* @var $field Model_CustomField */
			// If we only want a specific context, filter out the rest
			if(0 != strcasecmp($field->context, $context))
				continue;
			
			if(!$with_fieldsets && !empty($field->custom_fieldset_id))
				continue;
			
			if($with_fieldset_names && !empty($field->custom_fieldset_id)) {
				if(isset($fieldsets[$field->custom_fieldset_id]))
					$field->name = $fieldsets[$field->custom_fieldset_id]->name . ' ' . $field->name;
			}
			
			$results[$idx] = $field;
		}
		
		return $results;
	}
	
	/**
	 *
	 * @param boolean $nocache
	 * @return Model_CustomField[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($objects = $cache->load(self::CACHE_ALL))) {
			$objects = self::getWhere(null, [DAO_CustomField::CUSTOM_FIELDSET_ID, DAO_CustomField::POS, DAO_CustomField::NAME], [true, true, true]);
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_CustomField[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_CustomField();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->type = $row['type'];
			$object->context = $row['context'];
			$object->custom_fieldset_id = intval($row['custom_fieldset_id']);
			$object->pos = intval($row['pos']);
			$object->params = [];
			$object->updated_at = intval($row['updated_at']);
			
			// JSON params
			if(!empty($row['params_json'])) {
				@$params = json_decode($row['params_json'], true);
				if(!empty($params))
					$object->params = $params;
			}
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('custom_field');
	}
	
	static function countByContextAndFieldset($context, $fieldset_id=0) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM custom_field WHERE context = %s AND custom_fieldset_id=%d",
			$db->qstr($context),
			$fieldset_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function countByFieldsetId($fieldset_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM custom_field WHERE custom_fieldset_id = %d",
			$fieldset_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	public static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::services()->database();
		
		$id_string = implode(',', $ids);
		
		$sql = sprintf("DELETE FROM custom_field WHERE id IN (%s)",$id_string);
		if(false == ($db->ExecuteSlave($sql)))
			return false;

		if(is_array($ids))
		foreach($ids as $id) {
			DAO_CustomFieldValue::deleteByFieldId($id);
		}

		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CUSTOM_FIELD,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$db->ExecuteMaster("DELETE FROM custom_field WHERE custom_fieldset_id != 0 AND custom_fieldset_id NOT IN (SELECT id FROM custom_fieldset)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' custom_field records.');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CustomField::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CustomField', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"custom_field.context as %s, ".
			"custom_field.custom_fieldset_id as %s, ".
			"custom_field.id as %s, ".
			"custom_field.name as %s, ".
			"custom_field.params_json as %s, ".
			"custom_field.pos as %s, ".
			"custom_field.type as %s, ".
			"custom_field.updated_at as %s ",
				SearchFields_CustomField::CONTEXT,
				SearchFields_CustomField::CUSTOM_FIELDSET_ID,
				SearchFields_CustomField::ID,
				SearchFields_CustomField::NAME,
				SearchFields_CustomField::PARAMS_JSON,
				SearchFields_CustomField::POS,
				SearchFields_CustomField::TYPE,
				SearchFields_CustomField::UPDATED_AT
			);
			
		$join_sql = "FROM custom_field ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CustomField');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
	
		array_walk_recursive(
			$params,
			array('DAO_CustomField', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'custom_field',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_CUSTOM_FIELD;
		$from_index = 'custom_field.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
		}
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
			$object_id = intval($row[SearchFields_CustomField::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(custom_field.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
};

class DAO_CustomFieldValue extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const FIELD_ID = 'field_id';
	const FIELD_VALUE = 'field_value';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::FIELD_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::FIELD_VALUE)
			->string()
			->setRequired(true)
			;
		
		return $validation->getFields();
	}
	
	public static function getValueTableName($field_id) {
		$field = DAO_CustomField::get($field_id);
		
		// Determine value table by type
		switch($field->type) {
			// stringvalue
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_LIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
			case Model_CustomField::TYPE_URL:
				$table = 'custom_field_stringvalue';
				break;
			// clobvalue
			case Model_CustomField::TYPE_MULTI_LINE:
				$table = 'custom_field_clobvalue';
				break;
			// number
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_CURRENCY:
			case Model_CustomField::TYPE_DECIMAL:
			case Model_CustomField::TYPE_DATE:
			case Model_CustomField::TYPE_FILE:
			case Model_CustomField::TYPE_FILES:
			case Model_CustomField::TYPE_LINK:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_WORKER:
				$table = 'custom_field_numbervalue';
				break;
			default:
				$table = null;
				break;
		}
		
		return $table;
	}
	
	public static function formatFieldValues($values) {
		if(!is_array($values))
			return;

		$fields = DAO_CustomField::getAll();
		$output = array();

		foreach($values as $field_id => $value) {
			if(!isset($fields[$field_id]))
				continue;

			$field =& $fields[$field_id]; /* @var $field Model_CustomField */

			switch($field->type) {
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$value = (strlen($value) > 255) ? substr($value,0,255) : $value;
					break;

				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_LIST:
					break;

				case Model_CustomField::TYPE_DROPDOWN:
					break;
					
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$values = $value;
					$value = array();
					
					if(!is_array($values))
						$values = array($values);

					// Protect from injection in cases where it's not desireable (controlled above)
					foreach($values as $idx => $v) {
						if(!isset($field->params['options']) || !in_array($v, $field->params['options']))
							continue;

						$is_unset = ('-'==substr($v,0,1)) ? true : false;
						$v = ltrim($v,'+-');
							
						if($is_unset) {
						} else {
							$value[$v] = $v;
						}
					}
					break;
					
				case Model_CustomField::TYPE_FILES:
					$values = $value;
					$value = array();
					
					if(!is_array($values))
						$values = array($values);

					foreach($values as $idx => $v) {
						$is_unset = ('-'==substr($v,0,1)) ? true : false;
						$v = ltrim($v,'+-');
							
						if($is_unset) {
						} else {
							$value[$v] = $v;
						}
					}
					break;

				case Model_CustomField::TYPE_CHECKBOX:
					$value = !empty($value) ? 1 : 0;
					break;

				case Model_CustomField::TYPE_DATE:
					if(is_numeric($value)) {
						$value = intval($value);
					} else {
						@$value = strtotime($value);
					}
					break;

				case Model_CustomField::TYPE_CURRENCY:
					@$currency_id = $field->params['currency_id'];
					
					if($currency_id && false !=  ($currency = DAO_Currency::get($currency_id))) {
						$value = DevblocksPlatform::strParseDecimal($value, $currency->decimal_at, '.');
					}
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					@$decimal_at = $field->params['decimal_at'];
					$value = DevblocksPlatform::strParseDecimal($value, $decimal_at, '.');
					break;
					
				case Model_CustomField::TYPE_FILE:
				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_WORKER:
					$value = intval($value);
					break;
			}
			
			$output[$field_id] = $value;
		}
		
		return $output;
	}
	
	/**
	 *
	 * @param object $context
	 * @param object $context_id
	 * @param object $values
	 * @return
	 */
	public static function formatAndSetFieldValues($context, $context_id, $values, $is_blank_unset=true, $delta=false, $autoadd_options=false) {
		// [TODO] This could probably be combined with ::formatFieldValues()
		
		if(empty($context) || empty($context_id) || !is_array($values))
			return;
		
		self::_linkCustomFieldsets($context, $context_id, $values);
		
		$fields = DAO_CustomField::getByContext($context);

		foreach($values as $field_id => $value) {
			if(!isset($fields[$field_id]))
				continue;

			$field =& $fields[$field_id]; /* @var $field Model_CustomField */
			$is_delta = (Model_CustomField::hasMultipleValues($field->type))
					? $delta
					: false
					;
			// if the field is blank
			if(
				(is_array($value) && empty($value))
				||
				(!is_array($value) && 0==strlen($value))
			) {
				// ... and blanks should unset
				if($is_blank_unset && !$is_delta)
					self::unsetFieldValue($context, $context_id, $field_id);
				
				// Skip setting
				continue;
			}

			switch($field->type) {
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$value = (strlen($value) > 255) ? substr($value,0,255) : $value;
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;
					
				case Model_CustomField::TYPE_MULTI_LINE:
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_LIST:
					if(!is_array($value))
						$value = [$value];
					
					// Clear before inserting
					self::unsetFieldValue($context, $context_id, $field_id);
					
					foreach($value as $v) {
						if(empty($v))
							continue;
						
						self::setFieldValue($context, $context_id, $field_id, $v, true);
					}
					break;

				case Model_CustomField::TYPE_DROPDOWN:
					// If we're setting a field that doesn't exist yet, add it.
					@$options = $field->params['options'] ?: array();
					
					if($autoadd_options && !in_array($value, $options) && !empty($value)) {
						$field->params['options'][] = $value;
						
						DAO_CustomField::update($field_id,
							array(
								DAO_CustomField::PARAMS_JSON => json_encode($field->params)
							)
						);
					}
					
					// If we're allowed to add/remove fields without touching the rest
					if(in_array($value, $options))
						self::setFieldValue($context, $context_id, $field_id, $value);
					
					break;
					
				case Model_CustomField::TYPE_FILES:
					if(!is_array($value))
						$value = array($value);

					if(!$delta) {
						self::unsetFieldValue($context, $context_id, $field_id);
					}
					
					// Protect from injection in cases where it's not desireable (controlled above)
					foreach($value as $idx => $v) {
						$is_unset = ('-'==substr($v,0,1)) ? true : false;
						$v = ltrim($v,'+-');
							
						if($is_unset) {
							if($delta)
								self::unsetFieldValue($context, $context_id, $field_id, $v);
						} else {
							self::setFieldValue($context, $context_id, $field_id, $v, true);
						}
					}
					break;
					
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					if(!is_array($value))
						$value = array($value);

					@$options = $field->params['options'] ?: array();
					
					// If we're setting a field that doesn't exist yet, add it.
					if($autoadd_options) {
						$added = false;
						
						foreach($value as $v) {
							// Ignore values we're removing
							if(DevblocksPlatform::strStartsWith($v, '-'))
								continue;
							
							// Ignore a leading plus
							$v = ltrim($v, '+');
							
							// 
							if(!in_array($v, $options) && !empty($v)) {
								$field->params['options'][] = $v;
								$added = true;
							}
						}
						
						if($added) {
							DAO_CustomField::update($field_id,
								array(
									DAO_CustomField::PARAMS_JSON => json_encode($field->params)
								)
							);
						}
					}
					
					if(!$delta) {
						self::unsetFieldValue($context, $context_id, $field_id);
					}
					
					// Protect from injection in cases where it's not desireable (controlled above)
					foreach($value as $idx => $v) {
						if(empty($v))
							continue;
						
						$is_unset = DevblocksPlatform::strStartsWith($v, '-');
						$v = ltrim($v,'+-');
						
						if(!isset($field->params['options']) || !in_array($v, $field->params['options']))
							continue;
						
						if($is_unset) {
							if($delta)
								self::unsetFieldValue($context, $context_id, $field_id, $v);
						} else {
							self::setFieldValue($context, $context_id, $field_id, $v, true);
						}
					}

					break;

				case Model_CustomField::TYPE_CHECKBOX:
					$value = !empty($value) ? 1 : 0;
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_DATE:
					if(is_numeric($value)) {
						$value = intval($value);
					} else {
						@$value = strtotime($value);
					}
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_CURRENCY:
					@$currency_id = $field->params['currency_id'];
					
					if($currency_id && false !=  ($currency = DAO_Currency::get($currency_id))) {
						$value = DevblocksPlatform::strParseDecimal($value, $currency->decimal_at, '.');
						self::setFieldValue($context, $context_id, $field_id, $value);
					}
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					@$decimal_at = $field->params['decimal_at'];
					$value = DevblocksPlatform::strParseDecimal($value, $decimal_at, '.');
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;
					
				case Model_CustomField::TYPE_FILE:
				case Model_CustomField::TYPE_LINK:
				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_WORKER:
					$value = intval($value);
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;
			}
		}
	}
	
	public static function setFieldValue($context, $context_id, $field_id, $value, $delta=false) {
		CerberusContexts::checkpointChanges($context, array($context_id));
		
		$db = DevblocksPlatform::services()->database();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;

		// Data formating
		switch($field->type) {
			case Model_CustomField::TYPE_DATE: // date
				if(is_numeric($value))
					$value = intval($value);
				else
					$value = @strtotime($value);
				break;
			case Model_CustomField::TYPE_DROPDOWN:
				$possible_values = array_map('mb_strtolower', $field->params['options']);
				
				if(false !== ($value_idx = array_search(DevblocksPlatform::strLower($value), $possible_values))) {
					$value = $field->params['options'][$value_idx];
				} else {
					return FALSE;
				}
				break;
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
				if(255 < strlen($value))
					$value = substr($value,0,255);
				break;
			case Model_CustomField::TYPE_CURRENCY:
			case Model_CustomField::TYPE_DECIMAL:
			case Model_CustomField::TYPE_FILE:
			case Model_CustomField::TYPE_LINK:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_WORKER:
				$value = intval($value);
				break;
		}
		
		// Clear existing values (beats replace logic)
		self::unsetFieldValue($context, $context_id, $field_id, ($delta?$value:null));
		
		// Set values consistently
		if(!is_array($value))
			$value = array($value);
			
		foreach($value as $v) {
			$sql = sprintf("INSERT INTO %s (field_id, context, context_id, field_value) ".
				"VALUES (%d, %s, %d, %s)",
				$table_name,
				$field_id,
				$db->qstr($context),
				$context_id,
				$db->qstr($v)
			);
			$db->ExecuteMaster($sql);
		}
		
		DevblocksPlatform::markContextChanged($context, $context_id);
		
		// Special handling
		switch($field->type) {
			case Model_CustomField::TYPE_FILE:
			case Model_CustomField::TYPE_FILES:
				DAO_Attachment::setLinks(CerberusContexts::CONTEXT_CUSTOM_FIELD, $field_id, $value);
				break;
		}
		
		DevblocksPlatform::markContextChanged($context, $context_id);
		
		return TRUE;
	}
	
	public static function unsetFieldValue($context, $context_id, $field_id, $value=null) {
		CerberusContexts::checkpointChanges($context, array($context_id));
		
		$db = DevblocksPlatform::services()->database();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;
		
		if(!is_array($value))
			$value = array($value);
		
		foreach($value as $v) {
			// Delete all values or optionally a specific given value
			$sql = sprintf("DELETE FROM %s WHERE context = '%s' AND context_id = %d AND field_id = %d %s",
				$table_name,
				$context,
				$context_id,
				$field_id,
				(!is_null($v) ? sprintf("AND field_value = %s ",$db->qstr($v)) : "")
			);
			$db->ExecuteMaster($sql);
		}
		
		// We need to remove context links on file attachments
		// [TODO] Optimize
		switch($field->type) {
			case Model_CustomField::TYPE_FILE:
			case Model_CustomField::TYPE_FILES:
				$sql = sprintf("DELETE FROM context_link WHERE from_context = %s and from_context_id = %d AND to_context = %s AND to_context_id NOT IN (SELECT field_value FROM custom_field_numbervalue WHERE field_id = %d)",
					$db->qstr(CerberusContexts::CONTEXT_CUSTOM_FIELD),
					$field_id,
					$db->qstr(CerberusContexts::CONTEXT_ATTACHMENT),
					$field_id
				);
				$db->ExecuteMaster($sql);
				break;
		}
		
		DevblocksPlatform::markContextChanged($context, $context_id);
		
		return TRUE;
	}
	
	public static function handleBulkPost($do) {
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());

		$fields = DAO_CustomField::getAll();
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_LIST:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_CURRENCY:
				case Model_CustomField::TYPE_DECIMAL:
				case Model_CustomField::TYPE_FILE:
				case Model_CustomField::TYPE_LINK:
				case Model_CustomField::TYPE_NUMBER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$field_value = (0==strlen($field_value)) ? '' : intval($field_value);
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'integer',0);
					$do['cf_'.$field_id] = array('value' => !empty($field_value) ? 1 : 0);
					break;

				case Model_CustomField::TYPE_FILES:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => DevblocksPlatform::sanitizeArray($field_value,'integer',array('nonzero','unique')));
					break;
					
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DATE:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
			}
		}
		
		return $do;
	}
	
	public static function parseFormPost($context, $field_ids) {
		$fields = DAO_CustomField::getByContext($context);
		$results = array();
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
		
			$field_value = null;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_FILES:
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
				case Model_CustomField::TYPE_LIST:
					@$field_value = DevblocksPlatform::importGPC($_REQUEST['field_'.$field_id],'array',array());
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_CURRENCY:
				case Model_CustomField::TYPE_DATE:
				case Model_CustomField::TYPE_DECIMAL:
				case Model_CustomField::TYPE_DROPDOWN:
				case Model_CustomField::TYPE_FILE:
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_LINK:
				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
				case Model_CustomField::TYPE_WORKER:
				default:
					@$field_value = DevblocksPlatform::importGPC($_REQUEST['field_'.$field_id],'string','');
					break;
			}
			
			$results[$field_id] = $field_value;
		}
		
		return $results;
	}
	
	public static function handleFormPost($context, $context_id, $field_ids, &$error) {
		$field_values = self::parseFormPost($context, $field_ids);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return false;
		
		// This will have to change when we require fields
		$field_values_to_validate = array_filter($field_values, function($value) {
			// Only return non-empty values
			return (is_array($value) || 0 != strlen($value));
		});
		
		// Format values before validation
		$field_values_to_validate = self::formatFieldValues($field_values_to_validate);
		
		// Validate
		if(!DevblocksORMHelper::validateCustomFields($field_values_to_validate, $context, $error, $context_id))
			return false;
		
		self::formatAndSetFieldValues($context, $context_id, $field_values);
		return true;
	}

	private static function _linkCustomFieldsets($context, $context_id, &$field_values) {
		/*
		 * If we have a request variable with hints about new custom fieldsets, use it
		 */
		if(isset($_REQUEST['custom_fieldset_adds'])) {
			@$custom_fieldset_adds = DevblocksPlatform::importGPC($_REQUEST['custom_fieldset_adds'], 'array', array());
			
			if(is_array($custom_fieldset_adds))
			foreach($custom_fieldset_adds as $cfset_id) {
				if(empty($cfset_id))
					continue;
			
				DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $cfset_id);
			}
			
		/*
		 * Otherwise, if the request variable doesn't exist we need to introspect the cfields
		 * and look for fieldsets.
		 */
		} else {
			$custom_fields = DAO_CustomField::getAll();
			$custom_fieldsets = DAO_CustomFieldset::getByContextLink($context, $context_id);
	
			foreach(array_keys($field_values) as $field_id) {
				if(!isset($custom_fields[$field_id]))
					continue;
				
				@$cfset_id = $custom_fields[$field_id]->custom_fieldset_id;
				
				if($cfset_id && !isset($custom_fieldsets[$cfset_id])) {
					DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $cfset_id);
				}
			}
		}
		
		/*
		 * If we have a request variable hint about removing fieldsets, do that now
		 */
		@$custom_fieldset_deletes = DevblocksPlatform::importGPC($_REQUEST['custom_fieldset_deletes'], 'array', array());
		
		if(is_array($custom_fieldset_deletes))
		foreach($custom_fieldset_deletes as $cfset_id) {
			if(empty($cfset_id))
				continue;
		
			$custom_fieldset = DAO_CustomFieldset::get($cfset_id);
			$custom_fieldset_fields = $custom_fieldset->getCustomFields();
			
			// Remove the custom field values
			if(is_array($custom_fieldset_fields))
			foreach(array_keys($custom_fieldset_fields) as $cf_id) {
				// Remove any data for this field on this record
				DAO_CustomFieldValue::unsetFieldValue($context, $context_id, $cf_id);
				
				// Remove any field values we're currently setting
				if(isset($field_values[$cf_id]))
					unset($field_values[$cf_id]);
			}
			
			// Break the context link
			DAO_ContextLink::deleteLink($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $cfset_id);
		}
		
		return true;
	}
	
	public static function getValuesByContextIds($context, $context_ids, $only_field_ids=null) {
		if(is_null($context_ids))
			return array();
		
		elseif(!is_array($context_ids))
			$context_ids = array($context_ids);

		if(empty($context_ids))
			return array();
			
		$db = DevblocksPlatform::services()->database();
		
		// Only check the custom fields of this context
		$fields = DAO_CustomField::getByContext($context);
		
		if(is_array($only_field_ids))
		$fields = array_filter($fields, function($item) use ($only_field_ids) {
			return in_array($item->id, $only_field_ids);
		});
		
		$tables = array();
		$sqls = array();
		
		if(empty($fields) || !is_array($fields))
			return array();

		// Default $results to all null values
		$null_values = array_combine(array_keys($fields), array_fill(0, count($fields), null));
		$results = array_combine($context_ids, array_fill(0, count($context_ids), $null_values));
		
		/*
		 * Only scan the tables where this context has custom fields.  For example,
		 * if we only have a string custom field defined on tickets, we only need to
		 * check one table out of the three.
		 */

		if(is_array($fields))
		foreach($fields as $cfield_id => $cfield) { /* @var $cfield Model_CustomField */
			$tables[] = DAO_CustomFieldValue::getValueTableName($cfield_id);
		}
		
		if(empty($tables))
			return array();
		
		$tables = array_unique($tables);

		if(is_array($tables))
		foreach($tables as $table) {
			if(empty($table))
				continue;
		
			$sqls[] = sprintf("SELECT context_id, field_id, field_value ".
				"FROM %s ".
				"WHERE context = '%s' AND context_id IN (%s)",
				$table,
				$context,
				implode(',', $context_ids)
			);
		}
		
		if(empty($sqls))
			return array();
		
		/*
		 * UNION the custom field queries into a single statement so we don't have to
		 * merge them in PHP from different resultsets.
		 */
		
		$sql = implode(' UNION ALL ', $sqls);
		if(false == ($rs = $db->ExecuteSlave($sql)))
			return false;
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$context_id = intval($row['context_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($fields[$field_id]))
				continue;
			
			if(!isset($results[$context_id]))
				$results[$context_id] = $null_values;
				
			$ptr =& $results[$context_id];
			
			// If multiple value type (multi-checkbox)
			if(Model_CustomField::hasMultipleValues($fields[$field_id]->type)) {
				if(!isset($ptr[$field_id]))
					$ptr[$field_id] = [];
					
				$ptr[$field_id][$field_value] = $field_value;
			} else {
				$ptr[$field_id] = $field_value;
			}
		}
		
		mysqli_free_result($rs);
		
		return $results;
	}
	
	public static function deleteByContextIds($context, $context_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($context_ids)) $context_ids = array($context_ids);
		$ids_list = implode(',', $context_ids);

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');
		
		if(!empty($context_ids))
		foreach($tables as $table) {
			$sql = sprintf("DELETE FROM %s WHERE context = %s AND context_id IN (%s)",
				$table,
				$db->qstr($context),
				implode(',', $context_ids)
			);
			if(false == ($db->ExecuteMaster($sql)))
				return false;
		}
	}
	
	public static function deleteByFieldId($field_id) {
		$db = DevblocksPlatform::services()->database();

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');

		foreach($tables as $table) {
			$sql = sprintf("DELETE FROM %s WHERE field_id = %d",
				$table,
				$field_id
			);
			if(false == ($db->ExecuteMaster($sql)))
				return false;
		}
	}
};

class Model_CustomField {
	const TYPE_CHECKBOX = 'C';
	const TYPE_CURRENCY = 'Y';
	const TYPE_DATE = 'E';
	const TYPE_DECIMAL = 'O';
	const TYPE_DROPDOWN = 'D';
	const TYPE_FILE = 'F';
	const TYPE_FILES = 'I';
	const TYPE_LINK = 'L';
	const TYPE_LIST = 'M';
	const TYPE_MULTI_CHECKBOX = 'X';
	const TYPE_MULTI_LINE = 'T';
	const TYPE_NUMBER = 'N';
	const TYPE_SINGLE_LINE = 'S';
	const TYPE_URL = 'U';
	const TYPE_WORKER = 'W';
	
	public $context = '';
	public $custom_fieldset_id = 0;
	public $id = 0;
	public $name = '';
	public $params = [];
	public $pos = 0;
	public $type = '';
	public $updated_at = 0;
	
	static function getTypes() {
		// [TODO] Extension provided custom field types
		
		$fields = array(
			self::TYPE_CHECKBOX => 'Checkbox',
			self::TYPE_CURRENCY => 'Currency',
			self::TYPE_DATE => 'Date',
			self::TYPE_DECIMAL => 'Decimal',
			self::TYPE_DROPDOWN => 'Picklist',
			self::TYPE_FILE => 'File',
			self::TYPE_FILES => 'Files: Multiple',
			self::TYPE_MULTI_CHECKBOX => 'Multiple Checkboxes',
			self::TYPE_MULTI_LINE => 'Text: Multiple Lines',
			self::TYPE_LINK => 'Record Link',
			self::TYPE_LIST => 'List',
			self::TYPE_NUMBER => 'Number',
			self::TYPE_SINGLE_LINE => 'Text: Single Line',
			self::TYPE_URL => 'URL',
			self::TYPE_WORKER => 'Worker',
		);
		
		asort($fields);
		
		return $fields;
	}
	
	static function hasMultipleValues($type) {
		$multiple_types = [
			Model_CustomField::TYPE_MULTI_CHECKBOX,
			Model_CustomField::TYPE_FILES,
			Model_CustomField::TYPE_LIST,
		];
		return in_array($type, $multiple_types);
	}
	
	function getName() {
		$label = '';
		
		if(false != ($fieldset = self::getFieldset()))
			$label = $fieldset->name . ' ';
		
		$label .= $this->name;
		
		return $label;
	}
	
	function getFieldset() {
		if(empty($this->custom_fieldset_id))
			return null;
		
		return DAO_CustomFieldset::get($this->custom_fieldset_id);
	}
};

class SearchFields_CustomField extends DevblocksSearchFields {
	const CONTEXT = 'c_context';
	const CUSTOM_FIELDSET_ID = 'c_custom_fieldset_id';
	const ID = 'c_id';
	const NAME = 'c_name';
	const PARAMS_JSON = 'c_params_json';
	const POS = 'c_pos';
	const TYPE = 'c_type';
	const UPDATED_AT = 'c_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_FIELDSET_SEARCH = '*_fieldset_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'custom_field.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CUSTOM_FIELD => new DevblocksSearchFieldContextKeys('custom_field.id', self::ID),
			CerberusContexts::CONTEXT_CUSTOM_FIELDSET => new DevblocksSearchFieldContextKeys('custom_field.custom_fieldset_id', self::CUSTOM_FIELDSET_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CUSTOM_FIELD, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_FIELDSET_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'custom_field.custom_fieldset_id');
				break;
				
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
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'custom_field', 'context', $translate->_('common.context'), null, true),
			self::CUSTOM_FIELDSET_ID => new DevblocksSearchField(self::CUSTOM_FIELDSET_ID, 'custom_field', 'custom_fieldset_id', $translate->_('common.custom_fieldset'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'custom_field', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'custom_field', 'name', $translate->_('common.name'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'custom_field', 'params_json', $translate->_('common.params'), null, true),
			self::POS => new DevblocksSearchField(self::POS, 'custom_field', 'pos', $translate->_('common.order'), null, true),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'custom_field', 'type', $translate->_('common.type'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'custom_field', 'updated_at', $translate->_('common.updated'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_FIELDSET_SEARCH => new DevblocksSearchField(self::VIRTUAL_FIELDSET_SEARCH, '*', 'fieldset_search', null, null, false),
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

class View_CustomField extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'custom_fields';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.custom_fields');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CustomField::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CustomField::NAME,
			SearchFields_CustomField::CONTEXT,
			SearchFields_CustomField::CUSTOM_FIELDSET_ID,
			SearchFields_CustomField::TYPE,
			SearchFields_CustomField::UPDATED_AT,
			SearchFields_CustomField::POS,
		);
		$this->addColumnsHidden(array(
			SearchFields_CustomField::PARAMS_JSON,
			SearchFields_CustomField::VIRTUAL_CONTEXT_LINK,
			SearchFields_CustomField::VIRTUAL_FIELDSET_SEARCH,
		));
		
		$this->addParamsHidden(array(
			SearchFields_CustomField::PARAMS_JSON,
			SearchFields_CustomField::VIRTUAL_FIELDSET_SEARCH,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CustomField::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CustomField');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CustomField', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CustomField', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_CustomField::CONTEXT:
				case SearchFields_CustomField::CUSTOM_FIELDSET_ID:
				case SearchFields_CustomField::TYPE:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_CustomField::VIRTUAL_CONTEXT_LINK:
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
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELD;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_CustomField::CONTEXT:
				$context_mfts = Extension_DevblocksContext::getAll(false);
				$label_map = array_column($context_mfts, 'name', 'id');
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_CustomField::CUSTOM_FIELDSET_ID:
				$fieldsets = DAO_CustomFieldset::getAll();
				$label_map = array_column($fieldsets, 'name', 'id');
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_CustomField::TYPE:
				$label_map = Model_CustomField::getTypes();
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_CustomField::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
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
		$search_fields = SearchFields_CustomField::getFields();
		
		$context_exts = Extension_DevblocksContext::getAll(false);
		$contexts = array_column($context_exts, 'name', 'id');
		
		$field_types = Model_CustomField::getTypes();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomField::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'context' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomField::CONTEXT),
					'examples' => [
						['type' => 'list', 'values' => $contexts]
					],
				),
			'fieldset.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CustomField::CUSTOM_FIELDSET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CustomField::VIRTUAL_FIELDSET_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'q' => ''],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CustomField::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELD, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomField::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'pos' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CustomField::POS),
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomField::TYPE),
					'examples' => [
						['type' => 'list', 'values' => $field_types]
					],
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CustomField::UPDATED_AT),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CUSTOM_FIELD, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_CustomField::VIRTUAL_FIELDSET_SEARCH);
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
		
		// Contexts
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		// Custom fieldsets
		$custom_fieldsets = DAO_CustomFieldset::getAll();
		$tpl->assign('custom_fieldsets', $custom_fieldsets);
		
		// Custom field types
		$types = Model_CustomField::getTypes();
		$tpl->assign('custom_field_types', $types);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/custom_fields/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CustomField::CONTEXT:
			case SearchFields_CustomField::NAME:
			case SearchFields_CustomField::TYPE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CustomField::CUSTOM_FIELDSET_ID:
			case SearchFields_CustomField::ID:
			case SearchFields_CustomField::POS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_CustomField::UPDATED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_CustomField::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
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
			case SearchFields_CustomField::CONTEXT:
				$context_mfts = Extension_DevblocksContext::getAll(false);
				$label_map = array_column($context_mfts, 'name', 'id');
				$this->_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_CustomField::CUSTOM_FIELDSET_ID:
				$fieldsets = DAO_CustomFieldset::getAll();
				$label_map = array_column($fieldsets, 'name', 'id');
				$this->_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_CustomField::TYPE:
				$label_map = Model_CustomField::getTypes();
				$this->_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_CustomField::VIRTUAL_FIELDSET_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.custom_fieldset')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_CustomField::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CustomField::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CustomField::CONTEXT:
			case SearchFields_CustomField::NAME:
			case SearchFields_CustomField::TYPE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CustomField::CUSTOM_FIELDSET_ID:
			case SearchFields_CustomField::ID:
			case SearchFields_CustomField::POS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CustomField::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CustomField::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
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

class Context_CustomField extends Extension_DevblocksContext implements IDevblocksContextPeek, IDevblocksContextProfile {
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Admins can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_CUSTOM_FIELD)))
			return CerberusContexts::denyEverything($models);
		
		$results = array_fill_keys(array_keys($dicts), false);
		
		foreach($dicts as $id => $dict) {
			// If not in a fieldset, skip
			if(!$dict->custom_fieldset_id)
				continue;
			
			// If in a fieldset, owner delegate can modify
			$results[$id] = CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CUSTOM_FIELD, $dict, 'custom_fieldset_owner_');
		}
		
		if(is_array($models)) {
			return $results;
		} else {
			return array_shift($results);
		}
	}
	
	function getRandom() {
		return DAO_CustomField::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=custom_field&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$custom_field = DAO_CustomField::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($custom_field->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $custom_field->id,
			'name' => $custom_field->name,
			'permalink' => $url,
			'updated' => $custom_field->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'custom_fieldset__label',
			'context',
			'type',
			'pos',
			'updated_at',
		);
	}
	
	function getContext($cfield, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Custom Field:';
			
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CUSTOM_FIELD);
		
		// Polymorph
		if(is_numeric($cfield)) {
			$cfield = DAO_CustomField::get($cfield);
		} elseif($cfield instanceof Model_CustomField) {
		// It's what we want already.
		} elseif(is_array($cfield)) {
			$cfield = Cerb_ORMHelper::recastArrayToModel($cfield, 'Model_CustomField');
		} else {
			$cfield = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'context' => $prefix.$translate->_('common.context'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'pos' => $prefix.$translate->_('common.order'),
			'type' => $prefix.$translate->_('common.type'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'pos' => Model_CustomField::TYPE_NUMBER,
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CUSTOM_FIELD;
		$token_values['_types'] = $token_types;
		
		// Worker token values
		if(null != $cfield) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $cfield->name;
			$token_values['context'] = $cfield->context;
			$token_values['custom_fieldset_id'] = $cfield->custom_fieldset_id;
			$token_values['id'] = $cfield->id;
			$token_values['name'] = $cfield->name;
			$token_values['type'] = $cfield->type;
			$token_values['pos'] = $cfield->pos;
			$token_values['updated_at'] = $cfield->updated_at;
			
			if(!empty($cfield->params))
				$token_values['params'] = $cfield->params;
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=custom_field&id=%d-%s",$cfield->id, DevblocksPlatform::strToPermalink($cfield->name)), true);
		}
		
		// Custom fieldset
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CUSTOM_FIELDSET, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'custom_fieldset_',
			$prefix.'Fieldset:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'context' => DAO_CustomField::CONTEXT,
			'custom_fieldset_id' => DAO_CustomField::CUSTOM_FIELDSET_ID,
			'id' => DAO_CustomField::ID,
			'links' => '_links',
			'name' => DAO_CustomField::NAME,
			'pos' => DAO_CustomField::POS,
			'type' => DAO_CustomField::TYPE,
			'updated_at' => DAO_CustomField::UPDATED_AT,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'links':
				$this->_getDaoFieldsLinks($value, $out_fields, $error);
				break;
				
			case 'params':
				if(!is_array($value)) {
                                        if(is_string($value)) {
                                                if(false == ($value = json_decode($value))) {
                                                        $error = "string could not be JSON decoded";
                                                        return false;
                                                }
                                        } else {
                                                $error = "must be an object.";
                                                return false;
                                        }
                                }
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_CustomField::PARAMS_JSON] = $json;
				break;
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELD;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.custom_fields');
		$view->renderSortBy = SearchFields_CustomField::UPDATED_AT;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.custom_fields');
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CustomField::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELD;
		
		if(!empty($context_id)) {
			$model = DAO_CustomField::get($context_id);
		} else {
			$model = new Model_CustomField();
			$model->pos = 50;
		}
		
		if(empty($context_id) || $edit) {
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$context_mfts = Extension_DevblocksContext::getAll(false);
			$tpl->assign('context_mfts', $context_mfts);
			
			// Check view for defaults by filter
			if(!$context_id && false != ($view = C4_AbstractViewLoader::getView($view_id))) {
				$filters = $view->findParam(SearchFields_CustomField::CUSTOM_FIELDSET_ID, $view->getParams());
				
				if(false != ($filter = array_shift($filters))) {
					$custom_fieldset_id = is_array($filter->value) ? array_shift($filter->value) : $filter->value;
					
					if(false != ($custom_fieldset = DAO_CustomFieldset::get($custom_fieldset_id))) {
						$model->custom_fieldset_id = $custom_fieldset_id;
						$model->context = $custom_fieldset->context;
					}
				}
				
				$filters = $view->findParam(SearchFields_CustomField::CONTEXT, $view->getParams());
				
				if(false != ($filter = array_shift($filters))) {
					$context = is_array($filter->value) ? array_shift($filter->value) : $filter->value;
					$model->context = $context;
				}
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->assign('model', $model);
			$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/peek_edit.tpl');
			
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
			$labels = [];
			$values = [];
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/peek.tpl');
		}
	}
};
