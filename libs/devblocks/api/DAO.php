<?php
abstract class DevblocksORMHelper {
	const OPT_GET_NO_CACHE = 1;
	const OPT_GET_MASTER_ONLY = 2;
	
	const OPT_UPDATE_NO_FLUSH_CACHE = 1;
	const OPT_UPDATE_NO_EVENTS = 2;
	const OPT_UPDATE_NO_READ_AFTER_WRITE = 4;
	
	/**
	 * @abstract
	 * @param mixed $actor
	 * @param array $fields
	 * @param integer $id
	 * @param string $error
	 * @return boolean
	 */
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		return true;
	}
	
	static protected function _onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, &$error) {
		if(!($actor instanceof DevblocksDictionaryDelegate))
			if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor, true))) // false=undelegate
				return false;
		
		switch($actor->_context) {
			case CerberusContexts::CONTEXT_APPLICATION:
				return true;
				
			case CerberusContexts::CONTEXT_BOT:
				return true;
				
			case CerberusContexts::CONTEXT_ROLE:
				return true;
				
			case CerberusContexts::CONTEXT_GROUP:
				return false;
				
			case CerberusContexts::CONTEXT_WORKER:
				if(false == ($worker = DAO_Worker::get($actor->id)))
					return false;
				
				// Update
				if($id) {
					if(!$worker->hasPriv(sprintf("contexts.%s.update", $context))) {
						$error = DevblocksPlatform::translate('error.core.no_acl.edit');
						return false;
					}
					
				// Create
				} else {
					if(!$worker->hasPriv(sprintf("contexts.%s.create", $context))) {
						$error = DevblocksPlatform::translate('error.core.no_acl.create');
						return false;
					}
				}
				
				return true;
		}
		
		return false;
	}
	
	/**
	 * @abstract
	 * @param mixed $actor
	 * @param array $fields
	 * @param integer $id
	 */
	static public function onUpdateByActor($actor, $fields, $id) {
		return;
	}
	
	static public function validate(array &$fields, &$error=null, $id=null, array $excludes=[]) {
		if(!method_exists(get_called_class(), 'getFields'))
			return false;
		
		$validation = DevblocksPlatform::services()->validation();
		$valid_fields = get_called_class()::getFields();
		
		// Check required fields on creation
		if(is_array($valid_fields) && !$id)
		foreach($valid_fields as $field_key => $field) {
			if($field->_type->isRequired()) {
				if(!$field->_type->canBeEmpty() && 0 == strlen(@$fields[$field_key])) {
					$error = sprintf("'%s' is required.", $field->_label);
					return false;
				}
				
				if(!isset($fields[$field_key])) {
					$error = sprintf("'%s' is required.", $field->_label);
					return false;
				}
			}
		}
		
		if(is_array($fields))
		foreach($fields as $field_key => &$value) {
			// Bypass
			if(in_array($field_key, $excludes))
				continue;
			
			if(false == (@$field = $valid_fields[$field_key])) { /* @var $field DevblocksValidationField */
				$error = sprintf("'%s' is not a valid field.", $field_key);
				return false;
			}
			
			try {
				$validation->validate($field, $value, ['id' => $id, 'fields' => $fields]);
				
			} catch (Exception_DevblocksValidationError $e) {
				$error = $e->getMessage();
				return false;
			}
		}
		
		return true;
	}
	
	static function validateCustomFields(array &$fields, $context, &$error=null, $id=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$custom_fields = DAO_CustomField::getByContext($context);
		
		if(is_array($custom_fields))
		foreach($custom_fields as $field_id => $custom_field) {
			$custom_field_label = $custom_field->getName();
			
			switch($custom_field->type) {
				case Model_CustomField::TYPE_CHECKBOX:
					$validation
						->addField($field_id, $custom_field_label)
						->number()
						->setMin(0)
						->setMax(1)
						;
					break;
				case Model_CustomField::TYPE_CURRENCY:
					$validation
						->addField($field_id, $custom_field_label)
						->number()
						->setMin(0)
						->setMax('64 bits')
					;
					break;
				case Model_CustomField::TYPE_DATE:
					$validation
						->addField($field_id, $custom_field_label)
						->timestamp()
						->setMin(0)
						->setMax('32 bits')
						;
					break;
				case Model_CustomField::TYPE_DECIMAL:
					$validation
						->addField($field_id, $custom_field_label)
						->number()
						->setMin(0)
						->setMax('64 bits')
					;
					break;
				case Model_CustomField::TYPE_DROPDOWN:
					$options = $custom_field->params['options'] ?? [];
					
					if(!is_array($options))
						$options = [];
					
					if(!in_array('', $options))
						$options[] = '';
					
					$validation
						->addField($field_id, $custom_field_label)
						->string()
						->setMaxLength(255)
						->setPossibleValues($options)
						;
					break;
				case Model_CustomField::TYPE_FILE:
					$validation
						->addField($field_id, $custom_field_label)
						->number()
						->setMin(0)
						->setMax('32 bits')
					;
					break;
				case Model_CustomField::TYPE_FILES:
					$validation
						->addField($field_id, $custom_field_label)
						->idArray()
						->addValidator($validation->validators()->contextIds(CerberusContexts::CONTEXT_ATTACHMENT, true))
					;
					break;
				case Model_CustomField::TYPE_LINK:
					@$link_context = $custom_field->params['context'];
					
					$validation
						->addField($field_id, $custom_field_label)
						->id()
						->addValidator($validation->validators()->contextId($link_context, true))
					;
					break;
				case Model_CustomField::TYPE_LIST:
					$validation
						->addField($field_id, $custom_field_label)
						->stringOrArray()
						->setMaxLength(255)
						;
					break;
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$options = $custom_field->params['options'] ?? [];
					
					if(!is_array($options))
						$options = [];
					
					if(!in_array('', $options))
						$options[] = '';
					
					$validation
						->addField($field_id, $custom_field_label)
						->stringOrArray()
						->setPossibleValues($options)
						;
					break;
				case Model_CustomField::TYPE_MULTI_LINE:
					$validation
						->addField($field_id, $custom_field_label)
						->string()
						->setMaxLength(16777215)
					;
					break;
				case Model_CustomField::TYPE_NUMBER:
					$validation
						->addField($field_id, $custom_field_label)
						->number()
						->setMin('-63 bits')
						->setMax('63 bits')
					;
					break;
				case Model_CustomField::TYPE_SINGLE_LINE:
					$validation
						->addField($field_id, $custom_field_label)
						->string()
						->setMaxLength(255)
					;
					break;
				case Model_CustomField::TYPE_URL:
					$validation
						->addField($field_id, $custom_field_label)
						->url()
					;
					break;
				case Model_CustomField::TYPE_WORKER:
					$validation
						->addField($field_id, $custom_field_label)
						->id()
						->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER, true))
					;
					break;
				default:
					if(false != ($field_ext = Extension_CustomField::get($custom_field->type))) {
						$field_ext->validationRegister($custom_field, $validation);
					}
					break;
			}
		}
		
		$valid_fields = $validation->getFields();
		
		if(is_array($fields))
		foreach($fields as $field_key => $value) {
			if(false == (@$field = $valid_fields[$field_key])) { /* @var $field DevblocksValidationField */
				$error = sprintf("'%s' is not a valid custom field.", $field_key);
				return false;
			}
			
			try {
				$validation->validate($field, $value, ['id' => $id]);
				
			} catch (Exception_DevblocksValidationError $e) {
				$error = $e->getMessage();
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @param string $id_key
	 * @param string $select_sql
	 * @param string $join_sql
	 * @param string $where_sql
	 * @param string $sort_sql
	 * @param int $page
	 * @param int $limit
	 * @param bool $withCounts
	 * @param int $timeout_ms
	 * @return array|bool
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected static function _searchWithTimeout(string $id_key, string $select_sql, string $join_sql, ?string $where_sql, ?string $sort_sql, int $page, int $limit, $withCounts=true, int $timeout_ms=15000) {
		$db = DevblocksPlatform::services()->database();
		
		$limit = DevblocksPlatform::intClamp($limit, 0, PHP_INT_MAX);
		$timeouts = [];
		
		$sqls[] = 
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql.
			($limit ? sprintf(" LIMIT %d,%d", $page*$limit, $limit) : '')
		;
		$timeouts[] = $timeout_ms;
		
		if($withCounts) {
			$sqls[] =
				"SELECT COUNT(1) " .
				$join_sql .
				$where_sql
			;
			$timeouts[] = $timeout_ms;
		}
		
		try {
			if(false == ($responses = $db->QueryReaderAsync($sqls, $timeouts)))
				return false;
			
			if($responses[0] instanceof Exception_DevblocksDatabaseQueryTimeout)
				throw $responses[0];
			
			if(!($responses[0] instanceof mysqli_result))
				return false;
			
			$data = [];
			
			if($withCounts) {
				$total = '-1';
			} else {
				$total = $responses[0]->num_rows;
			}
			
			while($row = mysqli_fetch_assoc($responses[0])) {
				$id = $row[$id_key];
				
				if(is_numeric($id))
					$id = intval($id);
				
				$data[$id] = $row;
			}
			
			$db->Free($responses[0]);
			
			if($withCounts) {
				if ($responses[1] instanceof Exception_DevblocksDatabaseQueryTimeout) {
					$total = -1;
					
				} else if (!($responses[1] instanceof mysqli_result)) {
					return false;
					
				} else {
					$total = $db->GetOneFromResultset($responses[1]);
				}
			}
			
			return [$data, $total];
			
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			throw $e;
		}
	}
	
	static protected function _buildSortClause($sortBy, $sortAsc, $fields, &$select_sql, $search_class=null) {
		$sort_sql = null;
		
		if(!is_array($sortBy))
			$sortBy = array($sortBy);
		
		if(!is_array($sortAsc))
			$sortAsc = array($sortAsc);
		
		// Append custom fields to the SELECT if (and only if) we're sorting on it
		
		foreach($sortBy as $sort_field) {
			if(!DevblocksPlatform::strStartsWith($sort_field, 'cf_'))
				continue;
			
			if(false == ($field_id = intval(substr($sort_field, 3))))
				continue;
	
			if(false == ($field = DAO_CustomField::get($field_id)))
				continue;
			
			$cfield_key = null;
			
			if($search_class && class_exists($search_class))
				$cfield_key = $search_class::getCustomFieldContextWhereKey($field->context);
			
			if($cfield_key) {
				$select_sql .= sprintf(", (SELECT field_value FROM %s WHERE context=%s AND context_id=%s AND field_id=%d ORDER BY field_value%s) AS %s ",
					DAO_CustomFieldValue::getValueTableName($field->id),
					Cerb_ORMHelper::qstr($field->context),
					$cfield_key,
					$field_id,
					' LIMIT 1',
					$sort_field
				);
			}
		}
		
		if(is_array($sortBy) && is_array($sortAsc) && count($sortBy) == count($sortAsc)) {
			$sorts = [];
			
			foreach($sortBy as $idx => $field) {
				// We can't sort on virtual fields, the field must exist, and must be flagged sortable
				if(
					!is_string($field)
					|| '*'==substr($field,0,1)
					|| !isset($fields[$field])
					|| !$fields[$field]->is_sortable
				) {
					continue;
				}
				
				$asc = $sortAsc[$idx] ?? null;
				
				$sorts[] = sprintf("%s %s",
					$field,
					($asc || is_null($asc)) ? "ASC" : "DESC"
				);
			}
			
			if(!empty($sorts))
				$sort_sql = sprintf('ORDER BY %s', implode(', ', $sorts));
			
		}
		
		return $sort_sql;
	}
	
	static public function buildSort($sortBy, $sortAsc, $fields, $search_class=null) {
		if(!is_array($sortBy))
			$sortBy = [$sortBy];
		
		if(!is_array($sortAsc))
			$sortAsc = [$sortAsc];
		
		// Append custom fields to the SELECT if (and only if) we're sorting on it
		
		$results = [
			'fields' => [],
			'sql_select' => '',
			'sql_sort' => '',
		];
		
		if(is_array($sortBy) && is_array($sortAsc) && count($sortBy) == count($sortAsc)) {
			$selects = [];
			$sorts = [];
			
			foreach($sortBy as $idx => $field) {
				// We can't sort on virtual fields, the field must exist, and must be flagged sortable
				if('*'==substr($field,0,1)
						|| !isset($fields[$field])
						|| !$fields[$field]->is_sortable) {
					continue;
				}
				
				$sort_field = DevblocksPlatform::objectToArray($fields[$field]);
				
				$sort_field['sql_select'] = null;
				$sort_field['sql_key'] = sprintf("%s.%s",
					Cerb_ORMHelper::escape($sort_field['db_table']),
					Cerb_ORMHelper::escape($sort_field['db_column'])
				);
				unset($sort_field['is_sortable']);
				
				// Custom fields
				if(DevblocksPlatform::strStartsWith($field, 'cf_')) {
					if(false != ($field_id = intval(substr($field, 3)))) {
						if(false != ($custom_field = DAO_CustomField::get($field_id))) {
							$cfield_key = null;
							
							if($search_class && class_exists($search_class))
								$cfield_key = $search_class::getCustomFieldContextWhereKey($custom_field->context);
							
							if($cfield_key) {
								$sort_field['sql_key'] = $field;
								$selects[] = sprintf("(SELECT field_value FROM %s WHERE context=%s AND context_id=%s AND field_id=%d ORDER BY field_value LIMIT 1) AS %s",
									DAO_CustomFieldValue::getValueTableName($custom_field->id),
									Cerb_ORMHelper::qstr($custom_field->context),
									$cfield_key,
									$custom_field->id,
									$field
								);
							}
						}
					}
				}
				
				$results['fields'][$field] = $sort_field;
				
				@$asc = $sortAsc[$idx];
				
				$sorts[] = sprintf("%s %s",
					$results['fields'][$field]['sql_key'],
					($asc || is_null($asc)) ? "ASC" : "DESC"
				);
			}
			
			if($selects)
				$results['sql_select'] = implode(', ', $selects);
			
			if($sorts)
				$results['sql_sort'] = sprintf('ORDER BY %s', implode(', ', $sorts));
		}
		
		return $results;
	}
	
	static protected function _getWhereSQL($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		// Where
		$where_sql = !empty($where) ? sprintf("WHERE %s ", $where) : '';
		
		// Sorting
		if(is_array($sortBy)) {
			$sortPairs = [];
			foreach($sortBy as $k => $v) {
				$sortPairs[] = sprintf("%s %s",
					$v,
					(is_array($sortAsc) ? (@$sortAsc[$k] ? 'ASC' : 'DESC') : ($sortAsc ? 'ASC' : 'DESC'))
				);
			}
			
			$sort_sql = 'ORDER BY '. implode(', ', $sortPairs) . ' ';
			
		} else {
			$sortAsc = ($sortAsc) ? 'ASC' : 'DESC';
			$sort_sql = !empty($sortBy) ? sprintf("ORDER BY %s %s ", $sortBy, $sortAsc) : '';
		}
		
		// Limit
		$limit_sql = !empty($limit) ? sprintf("LIMIT 0,%d ", $limit) : '';
		
		$return = array(
			$where_sql,
			$sort_sql,
			$limit_sql
		);
		
		return $return;
	}
	
	/**
	 * @param integer $id
	 * @param array $fields
	 */
	static protected function _insert($table, $fields, $idcol='id') {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($fields) || empty($fields))
			return;
		
		foreach($fields as &$v) {
			if(is_null($v))
				$v = 'NULL';
			else
				$v = $db->qstr($v);
		}

		$sql = sprintf("INSERT INTO %s (%s) VALUES (%s)",
			$table,
			implode(', ', array_keys($fields)),
			implode(', ', array_values($fields))
		);
		$db->ExecuteMaster($sql);
		
		return $db->LastInsertId();
	}

    /**
     * @param array|integer $ids
     * @param string $table
     * @param array $fields
     * @param string $idcol
     * @param int $option_bits
     */
	static protected function _update($ids, string $table, array $fields, $idcol='id', $option_bits = 0) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		$sets = [];
		
		if(!is_array($fields) || empty($fields) || empty($ids))
			return;
		
		foreach($fields as $k => $v) {
			if(is_null($v))
				$value = 'NULL';
			else
				$value = $db->qstr($v);
			
			$sets[] = sprintf("%s = %s",
				$k,
				$value
			);
		}
			
		$db_option_bits = ($option_bits & DevblocksORMHelper::OPT_UPDATE_NO_READ_AFTER_WRITE) ? _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE : 0;
		
		/** @noinspection SqlDialectInspection */
		$sql = sprintf(/** @lang text */"UPDATE %s SET %s WHERE %s IN (%s)",
			$table,
			implode(', ', $sets),
			$idcol,
			implode(',', $ids)
		);
		$db->ExecuteMaster($sql, $db_option_bits);
	}
	
	static protected function _updateWhere($table, $fields, $where) {
		$db = DevblocksPlatform::services()->database();
		$sets = [];
		
		if(!is_array($fields) || empty($fields) || empty($where))
			return;
		
		foreach($fields as $k => $v) {
			if(is_null($v))
				$value = 'NULL';
			else
				$value = $db->qstr($v);
			
			$sets[] = sprintf("%s = %s",
				$k,
				$value
			);
		}
		
		/** @noinspection SqlDialectInspection */
		$sql = sprintf(/** @lang text */"UPDATE %s SET %s WHERE %s",
			$table,
			implode(', ', $sets),
			$where
		);
		$db->ExecuteMaster($sql);
	}
	
	static protected function _updateAbstract($context, $ids, array &$fields) {
		if(!is_array($ids))
			$ids = [$ids];
		
		// Handle avatar images
		
		if(isset($fields['_image'])) {
			foreach($ids as $id) {
				DAO_ContextAvatar::upsertWithImage($context, $id, $fields['_image']);
			}
			unset($fields['_image']);
		}
		
		// Custom fieldsets
		
		if(isset($fields['_fieldsets'])) {
			$fieldsets_json = $fields['_fieldsets'];
			unset($fields['_fieldsets']);
			
			if(false == ($fieldset_ids = json_decode($fieldsets_json ?? '')))
				$fieldset_ids = [];
			
			$fieldsets_to_add = $fieldsets_to_remove = [];
			
			foreach($fieldset_ids as $fieldset_id) {
				if($fieldset_id < 0) {
					$fieldsets_to_remove[] = abs($fieldset_id);
				} else {
					$fieldsets_to_add[] = intval($fieldset_id);
				}
			}
			
			unset($fieldset_ids);
			
			if(is_array($ids))
			foreach($ids as $id) {
				if($fieldsets_to_add)
					DAO_CustomFieldset::addToContext($fieldsets_to_add, $context, $id);
				if($fieldsets_to_remove)
					DAO_CustomFieldset::removeFromContext($fieldsets_to_remove, $context, $id);
			}
		}
		
		// Links
		
		if(isset($fields['_links'])) {
			$links_json = $fields['_links'];
			unset($fields['_links']);
			
			if(false == (@$links = json_decode($links_json)))
				$links = [];
			
			if(is_array($links))
			foreach($links as $link) {
				$link_context = $link_id = null;
				$is_remove = false;
				
				if(!is_string($link))
					continue;
				
				if(DevblocksPlatform::strStartsWith($link, ['-'])) {
					$is_remove = true;
					$link = ltrim($link, '-');
				}
				
				@list($link_context, $link_id) = explode(':', $link, 2);
				
				if(false == ($link_context_ext = Extension_DevblocksContext::getByAlias($link_context, false)))
					continue;
				
				if(is_array($ids)) {
					foreach($ids as $id) {
						if($is_remove) {
							DAO_ContextLink::deleteLink($link_context_ext->id, $link_id, $context, $id);
						} else {
							DAO_ContextLink::setLink($link_context_ext->id, $link_id, $context, $id);
						}
					}
				}
			}
		}
	}
	
	static protected function _mergeIds($context, $from_ids, $to_id) {
		if(empty($from_ids) || empty($to_id))
			return false;
			
		if(!is_numeric($to_id) || !is_array($from_ids))
			return false;
		
		// Log the ID changes
		foreach($from_ids as $from_id)
			DAO_ContextMergeHistory::logMerge($context, $from_id, $to_id);
		
		$db = DevblocksPlatform::services()->database();
		
		// Merge bot owners
		$db->ExecuteMaster(sprintf("UPDATE bot SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge calendar owners
		$db->ExecuteMaster(sprintf("UPDATE calendar SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge classifier owners
		$db->ExecuteMaster(sprintf("UPDATE classifier SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge comment targets
		$db->ExecuteMaster(sprintf("UPDATE comment SET context_id = %d WHERE context = %s AND context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge comment owners
		$db->ExecuteMaster(sprintf("UPDATE comment SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge connected account owners
		$db->ExecuteMaster(sprintf("UPDATE connected_account SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge context_activity_log actors
		$db->ExecuteMaster(sprintf("UPDATE IGNORE context_activity_log SET actor_context_id = %d WHERE actor_context = %s AND actor_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge context_activity_log targets
		$db->ExecuteMaster(sprintf("UPDATE IGNORE context_activity_log SET target_context_id = %d WHERE target_context = %s AND target_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge context_alias
		$db->ExecuteMaster(sprintf("UPDATE IGNORE context_alias SET id = %d WHERE context = %s AND id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge context_avatar
		$db->ExecuteMaster(sprintf("UPDATE IGNORE context_avatar SET context_id = %d WHERE context = %s AND context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		$db->ExecuteMaster(sprintf("DELETE FROM context_avatar WHERE context = %s AND context_id IN (%s)",
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge context_link
		$db->ExecuteMaster(sprintf("UPDATE IGNORE context_link SET from_context_id = %d WHERE from_context = %s AND from_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		$db->ExecuteMaster(sprintf("UPDATE IGNORE context_link SET to_context_id = %d WHERE to_context = %s AND to_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		$db->ExecuteMaster(sprintf("DELETE FROM context_link WHERE from_context = %s AND from_context_id IN (%s)",
			$db->qstr($context),
			implode(',', $from_ids)
		));
		$db->ExecuteMaster(sprintf("DELETE FROM context_link WHERE to_context = %s AND to_context_id IN (%s)",
			$db->qstr($context),
			implode(',', $from_ids)
		));
		$db->ExecuteMaster(sprintf("DELETE FROM context_link WHERE to_context = %s AND to_context_id = %d AND to_context=from_context AND to_context_id=from_context_id",
			$db->qstr($context),
			$to_id
		));
		
		// Merge context_saved_search
		$db->ExecuteMaster(sprintf("UPDATE context_saved_search SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));

		// Merge context_scheduled_behavior
		$db->ExecuteMaster(sprintf("UPDATE IGNORE context_scheduled_behavior SET context_id = %d WHERE context = %s AND context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge custom_fieldset owner
		$db->ExecuteMaster(sprintf("UPDATE custom_fieldset SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge email_signature owner
		$db->ExecuteMaster(sprintf("UPDATE email_signature SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge file_bundle owner
		$db->ExecuteMaster(sprintf("UPDATE file_bundle SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge mail_html_template owner
		$db->ExecuteMaster(sprintf("UPDATE mail_html_template SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge notifications
		$db->ExecuteMaster(sprintf("UPDATE IGNORE notification SET context_id = %d WHERE context = %s AND context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		// Merge project_board owner
		
		if(false != ($plugin = DevblocksPlatform::getPlugin('cerb.project_boards')) && $plugin->enabled) {
			$db->ExecuteMaster(sprintf("UPDATE project_board SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
				$to_id,
				$db->qstr($context),
				implode(',', $from_ids)
			));
		}
		
		// Merge snippet owner
		$db->ExecuteMaster(sprintf("UPDATE snippet SET owner_context_id = %d WHERE owner_context = %s AND owner_context_id IN (%s)",
			$to_id,
			$db->qstr($context),
			implode(',', $from_ids)
		));
		
		return true;
	}
	
	static protected function _parseSearchParams($params, array $columns=[], $search_class=null, $sortBy='') {
		if(!class_exists($search_class) || !class_implements($search_class, 'DevblocksSearchFields'))
			return false;
		
		$pkey = $search_class::getPrimaryKey();
		$fields = $search_class::getFields();
		
		$tables = [];
		$selects = [];
		$wheres = [];
		
		// Sort By
		if(!empty($sortBy)) {
			if(is_string($sortBy) && isset($fields[$sortBy])) {
				$tables[$fields[$sortBy]->db_table] = $fields[$sortBy]->db_table;
				
			} elseif(is_array($sortBy)) {
				foreach($sortBy as $sort_field) {
					if(isset($fields[$sort_field]))
						$tables[$fields[$sort_field]->db_table] = $fields[$sort_field]->db_table;
				}
			}
		}
		
		// Columns
		if(is_array($columns))
		foreach($columns as $column) {
			if(!isset($fields[$column]))
				continue;
			
			$table_name = $fields[$column]->db_table;
			$tables[$fields[$column]->db_table] = $table_name;
			
			// Skip virtuals
			if('*' == $table_name)
				continue;
			
			$selects[] = sprintf("%s.%s AS %s",
				$fields[$column]->db_table,
				$fields[$column]->db_column,
				$column
			);
		}
		
		// Params
		if(is_array($params))
		foreach($params as $param_key => $param) {
			if(false === $param) {
				$where = "0";
				
			} elseif(!is_array($param) && !is_object($param)) {
				$where = "0";
				
			// Is this a criteria group (OR, AND, OR NOT, AND NOT)?
			} elseif(is_array($param)) {
				$where = self::_parseNestedSearchParams($param, $tables, $search_class, $pkey);
				
			// Is this a single parameter?
			} elseif($param instanceOf DevblocksSearchCriteria) { /* @var $param DevblocksSearchCriteria */
				if(isset($fields[$param->field]))
					$tables[$fields[$param->field]->db_table] = $fields[$param->field]->db_table;
				
				$where = $search_class::getWhereSQL($param);
			}
			
			if(0 != strlen($where)) {
				$wheres[$param_key] = $where;
			}
		}
		
		return array($tables, $wheres, $selects);
	}
	
	static private function _parseNestedSearchParams($param, &$tables, $search_class, $pkey=null) {
		$pkey = $search_class::getPrimaryKey();
		$fields = $search_class::getFields();
		
		$outer_wheres = [];
		$group_wheres = [];
		@$group_oper = DevblocksPlatform::strUpper(array_shift($param));
		$sql = '';
		$where = '';
		$not = in_array($group_oper, [DevblocksSearchCriteria::GROUP_AND_NOT, DevblocksSearchCriteria::GROUP_OR_NOT]);
		
		if(empty($param))
			return null;
		
		switch($group_oper) {
			case DevblocksSearchCriteria::GROUP_OR:
			case DevblocksSearchCriteria::GROUP_AND:
			case DevblocksSearchCriteria::GROUP_AND_NOT:
			case DevblocksSearchCriteria::GROUP_OR_NOT:
				$inner_opers = [
					DevblocksSearchCriteria::GROUP_AND => DevblocksSearchCriteria::GROUP_AND,
					DevblocksSearchCriteria::GROUP_AND_NOT => DevblocksSearchCriteria::GROUP_AND,
					DevblocksSearchCriteria::GROUP_OR => DevblocksSearchCriteria::GROUP_OR, 
					DevblocksSearchCriteria::GROUP_OR_NOT => DevblocksSearchCriteria::GROUP_OR
				];
				
				foreach($param as $p) { /* @var $p DevblocksSearchCriteria */
					if(is_array($p)) {
						if(false != ($result = self::_parseNestedSearchParams($p, $tables, $search_class, $pkey))) {
							$outer_wheres[] = $result;
						} else {
							// We can ignore an invalid OR, but must fail on an invalid AND
							if(in_array($group_oper, [DevblocksSearchCriteria::GROUP_AND, DevblocksSearchCriteria::GROUP_AND_NOT]))
								$outer_wheres[] = '0';
						}
						
					} else {
						if(!isset($fields[$p->field])) {
							$group_wheres[] = '0';
							
						} else {
							// [JAS]: Indexes for optimization
							if(isset($fields[$p->field]))
								$tables[$fields[$p->field]->db_table] = $fields[$p->field]->db_table;
							
							$group_wheres[] = $search_class::getWhereSQL($p);
						}
						
						$where = sprintf("%s",
							implode(sprintf(" %s ", $inner_opers[$group_oper]), $group_wheres)
						);
					}
				}
				break;
		}
		
		if(0 != strlen($where))
			$outer_wheres[] = $where;
		
		if($group_oper && $outer_wheres) {
			$sql = sprintf("%s(%s)",
				$not ? 'NOT ' : '',
				implode(sprintf(" %s ", $group_oper), $outer_wheres)
			);
		}
		
		return $sql;
	}
};

class DAO_Platform extends DevblocksORMHelper {
	private function __construct() {}
	
	static function cleanupPluginTables() {
		$db = DevblocksPlatform::services()->database();

		/*
		 * Make sure this uses the DB directly and not the registry, since
		 * that automatically filters out bad rows and we'd never purge them.
		 */
		$sql = "SELECT p.* ".
			"FROM cerb_plugin p ".
			"ORDER BY p.enabled DESC, p.name ASC "
			;
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$plugin = new DevblocksPluginManifest();
			@$plugin->id = $row['id'];
			@$plugin->dir = $row['dir'];
			
			if(!file_exists($plugin->getStoragePath())) {
				$plugin->purge();
			}
		}
		
		DevblocksPlatform::clearCache();
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$db->ExecuteMaster("DELETE FROM cerb_extension WHERE plugin_id NOT IN (SELECT id FROM cerb_plugin)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned extensions.');
		
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id NOT IN (SELECT id FROM cerb_extension)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned extension properties.');
	}
	
	static function updatePlugin($id, $fields) {
		$db = DevblocksPlatform::services()->database();
		$sets = [];
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$db->qstr($v)
			);
		}
			
		$sql = sprintf(/** @lang text */ "UPDATE cerb_plugin SET %s WHERE id = %s",
			implode(', ', $sets),
			$db->qstr($id)
		);
		$db->ExecuteMaster($sql);
	}
	
	static function deleteExtension($extension_id) {
		$db = DevblocksPlatform::services()->database();
		
		// Nuke cached extension manifest
		$sql = sprintf("DELETE FROM cerb_extension WHERE id = %s",
			$db->qstr($extension_id)
		);
		$db->ExecuteMaster($sql);
		
		// Nuke cached extension properties
		$sql = sprintf("DELETE FROM cerb_property_store WHERE extension_id = %s",
			$db->qstr($extension_id)
		);
		$db->ExecuteMaster($sql);
	}

	/**
	 * @param string $plugin_id
	 * @param integer $revision
	 * @return boolean
	 */
	static function hasPatchRun($plugin_id,$revision) {
		$tables = DevblocksPlatform::getDatabaseTables();
		if(empty($tables))
			return false;
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT run_date FROM cerb_patch_history WHERE plugin_id = %s AND revision >= %d",
			$db->qstr($plugin_id),
			$revision
		);
		
		if($db->GetOneMaster($sql))
			return true;
			
		return false;
	}
	
	/**
	 * @param string $plugin_id
	 * @param integer $revision
	 */
	static function setPatchRan($plugin_id,$revision) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("REPLACE INTO cerb_patch_history (plugin_id, revision, run_date) ".
			"VALUES (%s, %d, %d)",
			$db->qstr($plugin_id),
			$revision,
			time()
		);
		$db->ExecuteMaster($sql);
	}
	
	static function getClassLoaderMap() {
		if(null == ($db = DevblocksPlatform::services()->database()))
			return [];

		if(DevblocksPlatform::isDatabaseEmpty())
			return [];
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		$class_loader_map = [];
		
		$sql = "SELECT class, plugin_id, rel_path FROM cerb_class_loader ORDER BY plugin_id";
		$results = $db->GetArrayMaster($sql);
		
		foreach($results as $row) {
			@$class = $row['class'];
			@$plugin_id = $row['plugin_id'];
			@$rel_path = $row['rel_path'];
			
			// Make sure the plugin is valid
			if(isset($plugins[$plugin_id])) {
				// Build an absolute path
				$path = $plugins[$plugin_id]->getStoragePath() . '/' . $rel_path;
				
				// Init the array
				if(!isset($class_loader_map[$path]))
					$class_loader_map[$path] = [];
				
				$class_loader_map[$path][] = $class;
			}
		}
		
		return $class_loader_map;
	}
	
};

class DAO_DevblocksSetting extends DevblocksORMHelper {
	const PLUGIN_ID = 'plugin_id';
	const SETTING = 'setting';
	const VALUE = 'value';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(128)
		$validation
			->addField(self::PLUGIN_ID)
			->string()
			->setMaxLength(128)
			;
		// varchar(128)
		$validation
			->addField(self::SETTING)
			->string()
			->setMaxLength(128)
			;
		// text
		$validation
			->addField(self::VALUE)
			->string()
			->setMaxLength(65535)
			;
		
		return $validation->getFields();
	}
	
	static function set($plugin_id, $key, $value) {
		if(false == ($db = DevblocksPlatform::services()->database()))
			return;
		
		$db->ExecuteMaster(sprintf(
			"REPLACE INTO devblocks_setting (plugin_id, setting, value) ".
			"VALUES (%s,%s,%s) ",
				$db->qstr($plugin_id),
				$db->qstr($key),
				$db->qstr($value)
		));
	}
	
	// This doesn't need to cache because it's handled by the platform
	static function getSettings($plugin_id) {
		$tables = DevblocksPlatform::getDatabaseTables();
		
		if(false == ($db = DevblocksPlatform::services()->database()))
			return;
		
		$settings = [];
		
		if(!isset($tables['devblocks_setting']))
			return $settings;
		
		$results = $db->GetArrayMaster(sprintf("SELECT setting, value FROM devblocks_setting WHERE plugin_id = %s",
			$db->qstr($plugin_id)
		));
		
		if(is_array($results))
		foreach($results as $row) {
			$settings[$row['setting']] = $row['value'];
		}
		
		return $settings;
	}
	
	static function delete($plugin_id, array $keys=[]) {
		if(false == ($db = DevblocksPlatform::services()->database()))
			return;
		
		return $db->ExecuteMaster(sprintf("DELETE FROM devblocks_setting WHERE plugin_id = %s AND setting IN (%s)",
			$db->qstr($plugin_id),
			implode(',', $db->qstrArray($keys))
		));
	}
};

class DAO_DevblocksExtensionPropertyStore extends DevblocksORMHelper {
	const EXTENSION_ID = 'extension_id';
	const PROPERTY = 'property';
	const VALUE = 'value';
	
	const _CACHE_ALL = '_cache_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(128)
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(128)
			;
		// varchar(128)
		$validation
			->addField(self::PROPERTY)
			->string()
			->setMaxLength(128)
			;
		// text
		$validation
			->addField(self::VALUE)
			->string()
			->setMaxLength(65535)
			;

		return $validation->getFields();
	}
	
	static private function _getCacheKey($extension_id) {
		return sprintf("devblocks:ext:%s:params",
			DevblocksPlatform::strAlphaNum($extension_id, '_.')
		);
	}
	
	static function getAll() {
		$extensions = DevblocksPlatform::getExtensionRegistry();
		$cache = DevblocksPlatform::services()->cache();
		
		if(null == ($params = $cache->load(self::_CACHE_ALL))) {
			$db = DevblocksPlatform::services()->database();
			$params = [];
			
			// Add manifest params as our initial params

			foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
				$params[$extension->id] = $extension->params;
			}
			
			// Now load the DB params on top of them
			
			$sql = "SELECT extension_id, property, value ".
				"FROM cerb_property_store "
				;
			
			if(false == ($results = $db->GetArrayMaster($sql)))
				return false;
			
			foreach($results as $row) {
				$params[$row['extension_id']][$row['property']] = $row['value'];
			}
			
			$cache->save($params, self::_CACHE_ALL);
		}
		
		return $params;
	}
	
	static function getByExtension($extension_id) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = self::_getCacheKey($extension_id);
		
		if(null === ($params = $cache->load($cache_key))) {
			$db = DevblocksPlatform::services()->database();
			$params = [];
			
			if(false != ($extension = DevblocksPlatform::getExtension($extension_id, false, true))) {
				$params = $extension->params;
			}
			
			$sql = sprintf("SELECT property, value ".
				"FROM cerb_property_store ".
				"WHERE extension_id = %s",
				$db->qstr($extension_id)
			);
			
			if(false == ($results = $db->GetArrayMaster($sql)))
				return false;
			
			if(is_array($results))
			foreach($results as $row)
				$params[$row['property']] = $row['value'];
			
			$cache->save($params, $cache_key);
		}
		
		return $params;
	}

	static function get($extension_id, $key, $default=null) {
		$params = self::getByExtension($extension_id);
		return isset($params[$key]) ? $params[$key] : $default;
	}
	
	static function put($extension_id, $key, $value) {
		$db = DevblocksPlatform::services()->database();
		$cache_key = self::_getCacheKey($extension_id);

		$db->ExecuteMaster(sprintf(
			"REPLACE INTO cerb_property_store (extension_id, property, value) ".
			"VALUES (%s,%s,%s)",
			$db->qstr($extension_id),
			$db->qstr($key),
			$db->qstr($value)
		));

		$cache = DevblocksPlatform::services()->cache();
		$cache->remove($cache_key);
		return true;
	}
};

class DAO_Translation extends DevblocksORMHelper {
	const ID = 'id';
	const LANG_CODE = 'lang_code';
	const STRING_DEFAULT = 'string_default';
	const STRING_ID = 'string_id';
	const STRING_OVERRIDE = 'string_override';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(16)
		$validation
			->addField(self::LANG_CODE)
			->string()
			->setMaxLength(16)
			;
		// text
		$validation
			->addField(self::STRING_DEFAULT)
			->string()
			->setMaxLength(65535)
			;
		// varchar(255)
		$validation
			->addField(self::STRING_ID)
			->string()
			->setMaxLength(255)
			;
		// text
		$validation
			->addField(self::STRING_OVERRIDE)
			->string()
			->setMaxLength(65535)
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO translation () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'translation', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_Translation[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, string_id, lang_code, string_default, string_override ".
			"FROM translation ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY string_id ASC, lang_code ASC";
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Translation
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
	
	static function importTmxFile($filename) {
		if(!file_exists($filename))
			return;
		
		/*
		 * [JAS] [TODO] This could be inefficient when reading a lot
		 * of TMX sources, but it could also be inefficient always
		 * keeping it in memory after using it once.  I'm going to err
		 * on the side of a little extra DB work for the few times it's
		 * called.
		 */
		
		$hash = [];
		foreach(DAO_Translation::getWhere() as $s) { /* @var $s Model_Translation */
			$hash[$s->lang_code.'_'.$s->string_id] = $s;
		}
		
		if(false == (@$xml = simplexml_load_file($filename))) /* @var $xml SimpleXMLElement */
			return;
			
		$namespaces = $xml->getNamespaces(true);
		
		foreach($xml->body->tu as $tu) { /* @var $tu SimpleXMLElement */
			$msgid = DevblocksPlatform::strLower((string) $tu['tuid']);
			
			foreach($tu->tuv as $tuv) { /* @var $tuv SimpleXMLElement */
				$attribs = $tuv->attributes($namespaces['xml']);
				$lang = (string) $attribs['lang'];
				$string = (string) $tuv->seg[0]; // [TODO] Handle multiple segs?
				
				$hash_obj = $hash[$lang.'_'.$msgid] ?? null; /* @var $hash_obj Model_Translation */
				
				// If not found in the DB
				if(empty($hash_obj)) {
					$fields = array(
						DAO_Translation::STRING_ID => $msgid,
						DAO_Translation::LANG_CODE => $lang,
						DAO_Translation::STRING_DEFAULT => $string,
					);
					$id = DAO_Translation::create($fields);

					// Add to our hash to prevent dupes
					$new = new Model_Translation();
						$new->id = $id;
						$new->string_id = $msgid;
						$new->lang_code = $lang;
						$new->string_default = $string;
						$new->string_override = '';
					$hash[$lang.'_'.$msgid] = $new;
					
				// If exists in DB and the string has changed
				} elseif (!empty($hash_obj) && 0 != strcasecmp($string, $hash_obj->string_default)) {
					$fields = array(
						DAO_Translation::STRING_DEFAULT => $string,
					);
					DAO_Translation::update($hash_obj->id, $fields);
				}
			}
		}
	
		unset($xml);
	}
	
	static function reloadPluginStrings() {
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		if(is_array($plugins))
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			if($plugin->enabled) {
				$strings_xml = $plugin->getStoragePath() . '/strings.xml';
				if(file_exists($strings_xml)) {
					self::importTmxFile($strings_xml);
				}
			}
		}
	}
	
	static function getDefinedLangCodes() {
		$db = DevblocksPlatform::services()->database();
		$translate = DevblocksPlatform::getTranslationService();
		
		$lang_codes = [];
		
		// Look up distinct land codes from existing translations
		$sql = sprintf("SELECT DISTINCT lang_code FROM translation ORDER BY lang_code ASC");
		$results = $db->GetArrayReader($sql);
		
		// Languages
		$langs = $translate->getLanguageCodes();

		// Countries
		$countries = $translate->getCountryCodes();
		
		foreach($results as $row) {
			$code = $row['lang_code'];
			$data = explode('_', $code);
			@$lang = $langs[DevblocksPlatform::strLower($data[0])];
			@$terr = $countries[DevblocksPlatform::strUpper($data[1])];

			$lang_codes[$code] = (!empty($lang) && !empty($terr))
				? ($lang . ' (' . $terr . ')')
				: $code;
		}
		
		return $lang_codes;
	}
	
	static function getByLang($lang='en_US') {
		$db = DevblocksPlatform::services()->database();
		
		return self::getWhere(sprintf("%s = %s",
			self::LANG_CODE,
			$db->qstr($lang)
		));
	}
	
	static function getMapByLang($lang='en_US') {
		$strings = self::getByLang($lang);
		$map = [];
		
		if(is_array($strings))
		foreach($strings as $string) { /* @var $string Model_Translation */
			if($string instanceof Model_Translation)
				$map[$string->string_id] = $string;
		}
		
		return $map;
	}
	
	// [TODO] Allow null 2nd arg for all instances of a given string?
	static function getString($string_id, $lang='en_US') {
		$db = DevblocksPlatform::services()->database();
		
		$objects = self::getWhere(sprintf("%s = %s AND %s = %s",
			self::STRING_ID,
			$db->qstr($string_id),
			self::LANG_CODE,
			$db->qstr($lang)
		));

		if(!empty($objects) && is_array($objects))
			return array_shift($objects);
		
		return null;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Translation[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return $objects;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Translation();
			$object->id = $row['id'];
			$object->string_id = $row['string_id'];
			$object->lang_code = $row['lang_code'];
			$object->string_default = $row['string_default'];
			$object->string_override = $row['string_override'];
			$objects[$object->id] = $object;
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM translation WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function deleteByLangCodes($codes) {
		if(!is_array($codes)) $codes = array($codes);
		$db = DevblocksPlatform::services()->database();
		
		$codes_list = implode("','", $codes);
		
		$db->ExecuteMaster(sprintf("DELETE FROM translation WHERE lang_code IN ('%s') AND lang_code != 'en_US'", $codes_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Translation::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, [], 'SearchFields_Translation', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"tl.id as %s, ".
			"tl.string_id as %s, ".
			"tl.lang_code as %s, ".
			"tl.string_default as %s, ".
			"tl.string_override as %s ",
				SearchFields_Translation::ID,
				SearchFields_Translation::STRING_ID,
				SearchFields_Translation::LANG_CODE,
				SearchFields_Translation::STRING_DEFAULT,
				SearchFields_Translation::STRING_OVERRIDE
			);
		
		$join_sql =
			"FROM translation tl ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Translation');
		
		$result = array(
			'primary_table' => 'translation',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
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
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_Translation::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
};

class SearchFields_Translation extends DevblocksSearchFields {
	// Translate
	const ID = 'tl_id';
	const STRING_ID = 'tl_string_id';
	const LANG_CODE = 'tl_lang_code';
	const STRING_DEFAULT = 'tl_string_default';
	const STRING_OVERRIDE = 'tl_string_override';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'tl.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('tl.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		if('cf_' == substr($param->field, 0, 3)) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
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
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'tl', 'id', $translate->_('translate.id'), null, true),
			self::STRING_ID => new DevblocksSearchField(self::STRING_ID, 'tl', 'string_id', $translate->_('translate.string_id'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LANG_CODE => new DevblocksSearchField(self::LANG_CODE, 'tl', 'lang_code', $translate->_('translate.lang_code'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STRING_DEFAULT => new DevblocksSearchField(self::STRING_DEFAULT, 'tl', 'string_default', $translate->_('translate.string_default'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STRING_OVERRIDE => new DevblocksSearchField(self::STRING_OVERRIDE, 'tl', 'string_override', $translate->_('translate.string_override'), Model_CustomField::TYPE_SINGLE_LINE, true),
		);
	}
};

class DAO_DevblocksStorageQueue extends DevblocksORMHelper {
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_NAMESPACE = 'storage_namespace';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(128)
		$validation
			->addField(self::STORAGE_EXTENSION)
			->string()
			->setMaxLength(128)
			;
		// varchar(255)
		$validation
			->addField(self::STORAGE_KEY)
			->string()
			->setMaxLength(255)
			;
		// varchar(64)
		$validation
			->addField(self::STORAGE_NAMESPACE)
			->string()
			->setMaxLength(64)
			;
		// int(10) unsigned
		$validation
			->addField(self::STORAGE_PROFILE_ID)
			->id()
			;

		return $validation->getFields();
	}
	
	static function getPendingProfiles() {
		$db = DevblocksPlatform::services()->database();
		
		return $db->GetArrayMaster("SELECT DISTINCT storage_extension, storage_profile_id, storage_namespace FROM devblocks_storage_queue_delete");
	}
	
	static function getKeys($storage_namespace, $storage_extension, $storage_profile_id=0, $limit=500) {
		$db = DevblocksPlatform::services()->database();
		
		$keys = $db->GetArrayMaster(sprintf("SELECT storage_key FROM devblocks_storage_queue_delete WHERE storage_namespace = %s AND storage_extension = %s AND storage_profile_id = %d LIMIT %d",
			$db->qstr($storage_namespace),
			$db->qstr($storage_extension),
			$storage_profile_id,
			$limit
		));
		
		return array_map(function($e) {
			return $e['storage_key'];
		}, $keys);
	}
	
	static function enqueueDelete($storage_namespace, $storage_key, $storage_extension, $storage_profile_id=0) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_storage_queue_delete (storage_namespace, storage_key, storage_extension, storage_profile_id) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($storage_namespace),
			$db->qstr($storage_key),
			$db->qstr($storage_extension),
			$storage_profile_id
		));
		
		return TRUE;
	}
	
	static function purgeKeys($keys, $storage_namespace, $storage_extension, $storage_profile_id=0) {
		$db = DevblocksPlatform::services()->database();
		
		$escaped_keys = array_map(function($e) use ($db) {
			return $db->qstr($e);
		}, $keys);
		
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_storage_queue_delete WHERE storage_namespace = %s AND storage_extension = %s AND storage_profile_id = %d AND storage_key IN (%s)",
			$db->qstr($storage_namespace),
			$db->qstr($storage_extension),
			$storage_profile_id,
			implode(',', $escaped_keys)
		));
		
		return TRUE;
	}
};

class DAO_DevblocksStorageProfile extends DevblocksORMHelper {
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(128)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(128)
			;
		// longtext
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength('32 bits')
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO devblocks_storage_profile () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'devblocks_storage_profile', $fields);
		self::_clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('devblocks_storage_profile', $fields, $where);
		self::_clearCache();
	}
	
	static function getAll() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($profiles = $cache->load(DevblocksPlatform::CACHE_STORAGE_PROFILES))) {
			$profiles = self::getWhere();
			
			if(!is_array($profiles))
				return false;
			
			$cache->save($profiles, DevblocksPlatform::CACHE_STORAGE_PROFILES);
		}
		
		return $profiles;
	}
	
	static private function _clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(DevblocksPlatform::CACHE_STORAGE_PROFILES);
	}
	
	/**
	 * @param string $where
	 * @return Model_DevblocksStorageProfile[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, name, extension_id, params_json ".
			"FROM devblocks_storage_profile ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * Accepts either an integer id or a storage extension (profile_id=0)
	 *
	 * @param mixed $id
	 * @return Model_DevblocksStorageProfile
	 **/
	static function get($id) {
		if(is_numeric($id)) {
			$profiles = self::getAll();
			if(isset($profiles[$id]))
				return $profiles[$id];
				
		} else {
			// [TODO] Validate extension id
			$profile = new Model_DevblocksStorageProfile();
			$profile->id = 0;
			$profile->extension_id = $id;
			return $profile;
		}
			
		return NULL;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_DevblocksStorageProfile[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_DevblocksStorageProfile();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->extension_id = $row['extension_id'];
			$object->params = [];
			
			if(false !== ($params = json_decode($row['params_json'] ?? '', true))) {
				$object->params = $params;
			}
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = [$ids];
		
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_storage_profile WHERE id IN (%s)", $ids_list));
		
		self::_clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_DevblocksStorageProfile::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_DevblocksStorageProfile', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"devblocks_storage_profile.id as %s, ".
			"devblocks_storage_profile.name as %s, ".
			"devblocks_storage_profile.extension_id as %s, ".
			"devblocks_storage_profile.params_json as %s ",
				SearchFields_DevblocksStorageProfile::ID,
				SearchFields_DevblocksStorageProfile::NAME,
				SearchFields_DevblocksStorageProfile::EXTENSION_ID,
				SearchFields_DevblocksStorageProfile::PARAMS_JSON
			);
			
		$join_sql = "FROM devblocks_storage_profile ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_DevblocksStorageProfile');
		
		$result = array(
			'primary_table' => 'devblocks_storage_profile',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
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
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_DevblocksStorageProfile::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}

};

class SearchFields_DevblocksStorageProfile extends DevblocksSearchFields {
	const ID = 'd_id';
	const NAME = 'd_name';
	const EXTENSION_ID = 'd_extension_id';
	const PARAMS_JSON = 'd_params_json';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'devblocks_storage_profile.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('devblocks_storage_profile.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		if('cf_' == substr($param->field, 0, 3)) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
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
			self::ID => new DevblocksSearchField(self::ID, 'devblocks_storage_profile', 'id', $translate->_('id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'devblocks_storage_profile', 'name', $translate->_('name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'devblocks_storage_profile', 'extension_id', $translate->_('extension_id'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'devblocks_storage_profile', 'params_json', $translate->_('params_json'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};
