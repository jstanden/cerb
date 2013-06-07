<?php
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

class DAO_CustomField extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const TYPE = 'type';
	const CONTEXT = 'context';
	const POS = 'pos';
	const OPTIONS = 'options';
	const CUSTOM_FIELDSET_ID = 'custom_fieldset_id';
	
	const CACHE_ALL = 'ch_customfields';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO custom_field () ".
			"VALUES ()"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId();

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'custom_field', $fields);
		
		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_CustomField|null
	 */
	static function get($id) {
		$fields = self::getAll();
		
		if(isset($fields[$id]))
			return $fields[$id];
			
		return null;
	}
	
	/**
	* Returns all of the fields for the specified context available to $group_id, including global fields
	*
	* @param string $context The context of the custom field
	* @param boolean $with_fieldsets Include fieldsets
	* @return array
	*/
	
	static function getByContext($context, $with_fieldsets=true) {
		$fields = self::getAll();
		$results = array();

		// Filter fields to only the requested source
		foreach($fields as $idx => $field) { /* @var $field Model_CustomField */
			// If we only want a specific context, filter out the rest
			if(0 != strcasecmp($field->context, $context))
				continue;
			
			if(!$with_fieldsets && !empty($field->custom_fieldset_id))
				continue;
			
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
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = "SELECT id, name, type, context, custom_fieldset_id, pos, options ".
				"FROM custom_field ".
				"ORDER BY custom_fieldset_id ASC, pos ASC "
			;
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			
			$objects = self::_createObjectsFromResultSet($rs);
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	private static function _createObjectsFromResultSet($rs) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CustomField();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->type = $row['type'];
			$object->context = $row['context'];
			$object->custom_fieldset_id = intval($row['custom_fieldset_id']);
			$object->pos = intval($row['pos']);
			$object->options = DevblocksPlatform::parseCrlfString($row['options']);
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM custom_field WHERE id IN (%s)",$id_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		if(is_array($ids))
		foreach($ids as $id) {
			DAO_CustomFieldValue::deleteByFieldId($id);
		}
		
		self::clearCache();
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class DAO_CustomFieldValue extends DevblocksORMHelper {
	const FIELD_ID = 'field_id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const FIELD_VALUE = 'field_value';
	
	public static function getValueTableName($field_id) {
		$field = DAO_CustomField::get($field_id);
		
		// Determine value table by type
		$table = null;
		switch($field->type) {
			// stringvalue
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_DROPDOWN:
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
			case Model_CustomField::TYPE_DATE:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_WORKER:
				$table = 'custom_field_numbervalue';
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
						if(!in_array($v, $field->options))
							continue;

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

				case Model_CustomField::TYPE_NUMBER:
					$value = intval($value);
					break;
					
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

		$fields = DAO_CustomField::getByContext($context);

		foreach($values as $field_id => $value) {
			if(!isset($fields[$field_id]))
				continue;

			$field =& $fields[$field_id]; /* @var $field Model_CustomField */
			$is_delta = ($field->type==Model_CustomField::TYPE_MULTI_CHECKBOX)
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

				case Model_CustomField::TYPE_DROPDOWN:
					// If we're setting a field that doesn't exist yet, add it.
					if($autoadd_options && !in_array($value, $field->options) && !empty($value)) {
						$field->options[] = $value;
						DAO_CustomField::update($field_id, array(DAO_CustomField::OPTIONS => implode("\n",$field->options)));
					}
					
					// If we're allowed to add/remove fields without touching the rest
					if(in_array($value, $field->options))
						self::setFieldValue($context, $context_id, $field_id, $value);
					
					break;
					
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					if(!is_array($value))
						$value = array($value);

					// If we're setting a field that doesn't exist yet, add it.
					foreach($value as $v) {
						if($autoadd_options && !in_array($v, $field->options) && !empty($v)) {
							$field->options[] = $v;
							DAO_CustomField::update($field_id, array(DAO_CustomField::OPTIONS => implode("\n",$field->options)));
						}
					}

					if(!$delta) {
						self::unsetFieldValue($context, $context_id, $field_id);
					}
					
					// Protect from injection in cases where it's not desireable (controlled above)
					foreach($value as $idx => $v) {
						if(!in_array($v, $field->options))
							continue;

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

				case Model_CustomField::TYPE_NUMBER:
					$value = intval($value);
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$value = intval($value);
					self::setFieldValue($context, $context_id, $field_id, $value);
					break;
			}
		}
		
		DevblocksPlatform::markContextChanged($context, $context_id);
	}
	
	public static function setFieldValue($context, $context_id, $field_id, $value, $delta=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;

		// Data formating
		switch($field->type) {
			case 'E': // date
				if(is_numeric($value))
					$value = intval($value);
				else
					$value = @strtotime($value);
				break;
			case 'D': // dropdown
			case 'S': // string
			case 'U': // URL
				if(255 < strlen($value))
					$value = substr($value,0,255);
				break;
			case 'N': // number
			case 'W': // worker
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
			$db->Execute($sql);
		}
		
		DevblocksPlatform::markContextChanged($context, $context_id);
		
		return TRUE;
	}
	
	public static function unsetFieldValue($context, $context_id, $field_id, $value=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;
		
		if(!is_array($value))
			$value = array($value);
			
		foreach($value as $v) {
			// Delete all values or optionally a specific given value
			$sql = sprintf("DELETE QUICK FROM %s WHERE context = '%s' AND context_id = %d AND field_id = %d %s",
				$table_name,
				$context,
				$context_id,
				$field_id,
				(!is_null($v) ? sprintf("AND field_value = %s ",$db->qstr($v)) : "")
			);
			$db->Execute($sql);
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
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_REQUEST['field_'.$field_id],'array',array());
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_DATE:
				case Model_CustomField::TYPE_DROPDOWN:
				case Model_CustomField::TYPE_MULTI_LINE:
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
	
	public static function handleFormPost($context, $context_id, $field_ids) {
		$field_values = self::parseFormPost($context, $field_ids);
		self::_linkCustomFieldsets($context, $context_id, $field_values);
		self::formatAndSetFieldValues($context, $context_id, $field_values);
		return true;
	}

	private static function _linkCustomFieldsets($context, $context_id, &$field_values) {
		@$custom_fieldset_adds = DevblocksPlatform::importGPC($_REQUEST['custom_fieldset_adds'], 'array', array());
		@$custom_fieldset_deletes = DevblocksPlatform::importGPC($_REQUEST['custom_fieldset_deletes'], 'array', array());
		
		if(empty($custom_fieldset_adds) && empty($custom_fieldset_deletes))
			return;
		
		// [TODO] Check worker privs
		
		if(is_array($custom_fieldset_adds))
		foreach($custom_fieldset_adds as $cfset_id) {
			if(empty($cfset_id))
				continue;
		
			DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $cfset_id);
		}
		
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
	
	public static function getValuesByContextIds($context, $context_ids) {
		if(is_null($context_ids))
			return array();
		elseif(!is_array($context_ids))
			$context_ids = array($context_ids);

		if(empty($context_ids))
			return array();
			
		$db = DevblocksPlatform::getDatabaseService();
		
		// Only check the custom fields of this context
		$fields = DAO_CustomField::getByContext($context);
		
		$results = array();
		$tables = array();
		$sqls = array();
		
		if(empty($fields))
			return array();
		
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
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		while($row = mysql_fetch_assoc($rs)) {
			$context_id = intval($row['context_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($results[$context_id]))
				$results[$context_id] = array();
				
			$ptr =& $results[$context_id];
			
			// If multiple value type (multi-checkbox)
			if($fields[$field_id]->type=='X') {
				if(!isset($ptr[$field_id]))
					$ptr[$field_id] = array();
					
				$ptr[$field_id][$field_value] = $field_value;
				
			} else { // single value
				$ptr[$field_id] = $field_value;
				
			}
		}
		
		mysql_free_result($rs);
		
		return $results;
	}
	
	public static function deleteByContextIds($context, $context_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($context_ids)) $context_ids = array($context_ids);
		$ids_list = implode(',', $context_ids);

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');
		
		if(!empty($context_ids))
		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE context = %s AND context_id IN (%s)",
				$table,
				$db->qstr($context),
				implode(',', $context_ids)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}
	}
	
	public static function deleteByFieldId($field_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');

		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE field_id = %d",
				$table,
				$field_id
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}

	}
};

class Model_CustomField {
	const TYPE_CHECKBOX = 'C';
	const TYPE_DROPDOWN = 'D';
	const TYPE_DATE = 'E';
	const TYPE_NUMBER = 'N';
	const TYPE_SINGLE_LINE = 'S';
	const TYPE_MULTI_LINE = 'T';
	const TYPE_URL = 'U';
	const TYPE_WORKER = 'W';
	const TYPE_MULTI_CHECKBOX = 'X';
	
	public $id = 0;
	public $name = '';
	public $type = '';
	public $custom_fieldset_id = 0;
	public $context = '';
	public $pos = 0;
	public $options = array();
	
	static function getTypes() {
		return array(
			self::TYPE_SINGLE_LINE => 'Text: Single Line',
			self::TYPE_MULTI_LINE => 'Text: Multi-Line',
			self::TYPE_NUMBER => 'Number',
			self::TYPE_DATE => 'Date',
			self::TYPE_DROPDOWN => 'Picklist',
			self::TYPE_CHECKBOX => 'Checkbox',
			self::TYPE_MULTI_CHECKBOX => 'Multi-Checkbox',
			self::TYPE_WORKER => 'Worker',
			self::TYPE_URL => 'URL',
//			self::TYPE_FILE => 'File',
		);
	}
};