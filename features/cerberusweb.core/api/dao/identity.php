<?php
// [TODO] Last login at
// [TODO] Password
// [TODO] MFA
class DAO_Identity extends Cerb_ORMHelper {
	const ADDRESS = 'address';
	// [TODO] Validate as YYYY-MM-DD
	const BIRTHDATE = 'birthdate';
	const CREATED_AT = 'created_at';
	const EMAIL_ID = 'email_id';
	const EMAIL_VERIFIED = 'email_verified';
	const FAMILY_NAME = 'family_name';
	const GENDER = 'gender';
	const GIVEN_NAME = 'given_name';
	const ID = 'id';
	const LOCALE = 'locale';
	const MIDDLE_NAME = 'middle_name';
	const NAME = 'name';
	const NICKNAME = 'nickname';
	const PHONE_NUMBER = 'phone_number';
	const PHONE_NUMBER_VERIFIED = 'phone_number_verified';
	const POOL_ID = 'pool_id';
	const USERNAME = 'username';
	const UPDATED_AT = 'updated_at';
	const WEBSITE = 'website';
	const ZONEINFO = 'zoneinfo';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ADDRESS)
			->string()
			;
		$validation
			->addField(self::BIRTHDATE)
			->string()
			->addValidator(function($value, &$error) {
				if($value && false == (strtotime($value . ' 00:00 GMT'))) {
					$error = sprintf("(%s) is not formatted properly (YYYY-MM-DD).",
						$value
					);
					return false;
				}
				
				return true;
			});
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::EMAIL_ID, DevblocksPlatform::translateCapitalized('common.email'))
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS))
			->setUniqueCallback(function(DevblocksValidationField $field, $value, array $scope, &$error=null) {
				$data = DevblocksPlatform::services()->data();
				
				$id = $scope['id'] ?? null;
				$pool_id = $scope['fields']['pool_id'] ?? null;
				
				// If we have an ID, load the pool_id value which wasn't provided
				if(!$pool_id && $id) {
					if(false == ($identity = DAO_Identity::get($id)))
						return false;
					
					$pool_id = $identity->pool_id;
				}
				
				if(!$pool_id)
					return false;
				
				$data_query = sprintf('type:worklist.records of:identity query:(%s pool.id:${pool_id} email.id:${email_id}) format:dictionaries',
					@$scope['id'] ? sprintf('id:!%d', $scope['id']) : ''
				);
				
				$data_query_params = [
					'pool_id' => intval($pool_id),
					'email_id' => intval($value),
				];
				
				// $value must be unique per identity pool
				if(false == ($results = $data->executeQuery($data_query, $data_query_params, $error))) {
					$error = sprintf("Invalid uniqueness constraint on '%s'.", $field->_label);
					return false;
				}
				
				if(!empty($results['data'])) {
					$error = sprintf("An identity already exists with this '%s'. It must be unique in this identity pool.", $field->_label);
					return false;
				}
				
				return true;
			})
			->setRequired(true)
			;
		$validation
			->addField(self::EMAIL_VERIFIED)
			->bit()
			;
		$validation
			->addField(self::FAMILY_NAME)
			->string()
			;
		$validation
			->addField(self::GENDER)
			->string()
			->setMaxLength(1)
			->setPossibleValues(['','F','M'])
			;
		$validation
			->addField(self::GIVEN_NAME)
			->string()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::LOCALE)
			->string()
			->addValidator($validation->validators()->language())
			;
		$validation
			->addField(self::MIDDLE_NAME)
			->string()
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::NICKNAME)
			->string()
			;
		// [TODO] Phone validation
		$validation
			->addField(self::PHONE_NUMBER)
			->string()
			;
		$validation
			->addField(self::PHONE_NUMBER_VERIFIED)
			->bit()
			;
		$validation
			->addField(self::POOL_ID, DevblocksPlatform::translateCapitalized('common.identity.pool'))
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_IDENTITY_POOL))
			->setRequired(true)
			;
		$validation
			->addField(self::USERNAME)
			->string()
			->setUniqueCallback(function(DevblocksValidationField $field, $value, array $scope, &$error=null) {
				$data = DevblocksPlatform::services()->data();
				
				$id = $scope['id'] ?? null;
				$pool_id = $scope['fields']['pool_id'] ?? null;
				
				// If we have an ID, load the pool_id value which wasn't provided
				if(!$pool_id && $id) {
					if(false == ($identity = DAO_Identity::get($id)))
						return false;
					
					$pool_id = $identity->pool_id;
				}
				
				if(!$pool_id)
					return false;
				
				$data_query = sprintf('type:worklist.records of:identity query:(%s pool.id:${pool_id} username:${username}) format:dictionaries',
					@$scope['id'] ? sprintf('id:!%d', $scope['id']) : '',
				);
				
				$data_query_params = [
					'pool_id' => $pool_id,
					'username' => $value,
				];
				
				// $value must be unique per identity pool
				if(false == ($results = $data->executeQuery($data_query, $data_query_params, $error))) {
					$error = sprintf("Invalid uniqueness constraint on '%s'.", $field->_label);
					return false;
				}
				
				if(!empty($results['data'])) {
					$error = sprintf("An identity already exists with this '%s'. It must be unique in this identity pool.", $field->_label);
					return false;
				}
				
				return true;
			})
			->setNotEmpty(false)
			;
		$validation
			->addField(self::WEBSITE)
			->url()
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::ZONEINFO)
			->string()
			->addValidator($validation->validators()->timezone())
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
		
		if(!array_key_exists(self::CREATED_AT, $fields)) {
			$fields[self::CREATED_AT] = time();
		}
		
		$sql = "INSERT INTO identity () VALUES ()";
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
			
		$context = CerberusContexts::CONTEXT_IDENTITY;
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
			parent::_update($batch_ids, 'identity', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.identity.update',
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
		parent::_updateWhere('identity', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_IDENTITY;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Identity[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, pool_id, name, given_name, family_name, middle_name, nickname, username, website, email_id, email_verified, gender, birthdate, zoneinfo, locale, phone_number, phone_number_verified, address, created_at, updated_at ".
			"FROM identity ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_Identity[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_Identity::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_Identity	 */
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
	 * @return Model_Identity[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * 
	 * @param integer|string|Model_Address $email
	 * @param integer $pool_id
	 * @return Model_Identity
	 */
	static function getByEmailAndPool($email, $pool_id) {
		$email_id = null;
		
		if(is_numeric($email)) {
			$email_id = intval($email);
			
		} elseif(is_string($email)) {
			if(false == ($email = DAO_Address::getByEmail($email)))
				return null;
			
			$email_id = $email->id;
		} elseif($email instanceof Model_Address) {
			$email_id = $email->id;
		}
		
		if(!$email_id)
			return null;
		
		$results = DAO_Identity::getWhere(
			sprintf('%s = %d AND %s = %d',
				Cerb_ORMHelper::escape(DAO_Identity::EMAIL_ID),
				$email_id,
				Cerb_ORMHelper::escape(DAO_Identity::POOL_ID),
				$pool_id
			),
			null,
			null,
			1
		);
		
		if(!is_array($results) || empty($results))
			return null;
		
		return array_shift($results);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Identity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Identity();
			$object->id = intval($row['id']);
			$object->pool_id = intval($row['pool_id']);
			$object->name = $row['name'];
			$object->given_name = $row['given_name'];
			$object->family_name = $row['family_name'];
			$object->middle_name = $row['middle_name'];
			$object->nickname = $row['nickname'];
			$object->username = $row['username'];
			$object->website = $row['website'];
			$object->email_id = intval($row['email_id']);
			$object->email_verified = $row['email_verified'] ? 1 : 0;
			$object->gender = $row['gender'];
			$object->birthdate = $row['birthdate'];
			$object->zoneinfo = $row['zoneinfo'];
			$object->locale = $row['locale'];
			$object->phone_number = $row['phone_number'];
			$object->phone_number_verified = $row['phone_number_verified'] ? 1 : 0;
			$object->address = $row['address'];
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('identity');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM identity WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_IDENTITY,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Identity::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Identity', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"identity.id as %s, ".
			"identity.pool_id as %s, ".
			"identity.name as %s, ".
			"identity.given_name as %s, ".
			"identity.family_name as %s, ".
			"identity.middle_name as %s, ".
			"identity.nickname as %s, ".
			"identity.username as %s, ".
			"identity.website as %s, ".
			"identity.email_id as %s, ".
			"identity.email_verified as %s, ".
			"identity.gender as %s, ".
			"identity.birthdate as %s, ".
			"identity.zoneinfo as %s, ".
			"identity.locale as %s, ".
			"identity.phone_number as %s, ".
			"identity.phone_number_verified as %s, ".
			"identity.address as %s, ".
			"identity.created_at as %s, ".
			"identity.updated_at as %s ",
				SearchFields_Identity::ID,
				SearchFields_Identity::POOL_ID,
				SearchFields_Identity::NAME,
				SearchFields_Identity::GIVEN_NAME,
				SearchFields_Identity::FAMILY_NAME,
				SearchFields_Identity::MIDDLE_NAME,
				SearchFields_Identity::NICKNAME,
				SearchFields_Identity::USERNAME,
				SearchFields_Identity::WEBSITE,
				SearchFields_Identity::EMAIL_ID,
				SearchFields_Identity::EMAIL_VERIFIED,
				SearchFields_Identity::GENDER,
				SearchFields_Identity::BIRTHDATE,
				SearchFields_Identity::ZONEINFO,
				SearchFields_Identity::LOCALE,
				SearchFields_Identity::PHONE_NUMBER,
				SearchFields_Identity::PHONE_NUMBER_VERIFIED,
				SearchFields_Identity::ADDRESS,
				SearchFields_Identity::CREATED_AT,
				SearchFields_Identity::UPDATED_AT
			);
			
		$join_sql = "FROM identity ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Identity');
	
		return array(
			'primary_table' => 'identity',
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
			SearchFields_Identity::ID,
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

class SearchFields_Identity extends DevblocksSearchFields {
	const ID = 'i_id';
	const POOL_ID = 'i_pool_id';
	const NAME = 'i_name';
	const GIVEN_NAME = 'i_given_name';
	const FAMILY_NAME = 'i_family_name';
	const MIDDLE_NAME = 'i_middle_name';
	const NICKNAME = 'i_nickname';
	const USERNAME = 'i_username';
	const WEBSITE = 'i_website';
	const EMAIL_ID = 'i_email_id';
	const EMAIL_VERIFIED = 'i_email_verified';
	const GENDER = 'i_gender';
	const BIRTHDATE = 'i_birthdate';
	const ZONEINFO = 'i_zoneinfo';
	const LOCALE = 'i_locale';
	const PHONE_NUMBER = 'i_phone_number';
	const PHONE_NUMBER_VERIFIED = 'i_phone_number_verified';
	const ADDRESS = 'i_address';
	const CREATED_AT = 'i_created_at';
	const UPDATED_AT = 'i_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'identity.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_IDENTITY => new DevblocksSearchFieldContextKeys('identity.id', self::ID),
			CerberusContexts::CONTEXT_IDENTITY_POOL => new DevblocksSearchFieldContextKeys('identity.pool_id', self::POOL_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_IDENTITY, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf(/** @lang text */ 'SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_IDENTITY)), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_IDENTITY, self::getPrimaryKey());
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Identity::EMAIL_ID:
				$models = DAO_Address::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'email', 'id');
				break;
				
			case SearchFields_Identity::ID:
				$models = DAO_Identity::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Identity::GENDER:
				return [
					'' => DevblocksPlatform::translateCapitalized('common.unknown'),
					'F' => DevblocksPlatform::translateCapitalized('common.gender.female'),
					'M' => DevblocksPlatform::translateCapitalized('common.gender.male'),
				];
				break;
				
			case SearchFields_Identity::POOL_ID:
				$models = DAO_IdentityPool::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'identity', 'id', $translate->_('common.id'), null, true),
			self::POOL_ID => new DevblocksSearchField(self::POOL_ID, 'identity', 'pool_id', $translate->_('dao.identity.pool_id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'identity', 'name', $translate->_('common.name'), null, true),
			self::GIVEN_NAME => new DevblocksSearchField(self::GIVEN_NAME, 'identity', 'given_name', $translate->_('dao.identity.given_name'), null, true),
			self::FAMILY_NAME => new DevblocksSearchField(self::FAMILY_NAME, 'identity', 'family_name', $translate->_('dao.identity.family_name'), null, true),
			self::MIDDLE_NAME => new DevblocksSearchField(self::MIDDLE_NAME, 'identity', 'middle_name', $translate->_('dao.identity.middle_name'), null, true),
			self::NICKNAME => new DevblocksSearchField(self::NICKNAME, 'identity', 'nickname', $translate->_('dao.identity.nickname'), null, true),
			self::USERNAME => new DevblocksSearchField(self::USERNAME, 'identity', 'username', $translate->_('common.username'), null, true),
			self::WEBSITE => new DevblocksSearchField(self::WEBSITE, 'identity', 'website', $translate->_('common.website'), null, true),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'identity', 'email_id', $translate->_('common.email'), null, true),
			self::EMAIL_VERIFIED => new DevblocksSearchField(self::EMAIL_VERIFIED, 'identity', 'email_verified', $translate->_('dao.identity.email_verified'), null, true),
			self::GENDER => new DevblocksSearchField(self::GENDER, 'identity', 'gender', $translate->_('common.gender'), null, true),
			self::BIRTHDATE => new DevblocksSearchField(self::BIRTHDATE, 'identity', 'birthdate', $translate->_('dao.identity.birthdate'), null, true),
			self::ZONEINFO => new DevblocksSearchField(self::ZONEINFO, 'identity', 'zoneinfo', $translate->_('dao.identity.zoneinfo'), null, true),
			self::LOCALE => new DevblocksSearchField(self::LOCALE, 'identity', 'locale', $translate->_('dao.identity.locale'), null, true),
			self::PHONE_NUMBER => new DevblocksSearchField(self::PHONE_NUMBER, 'identity', 'phone_number', $translate->_('common.phone'), null, true),
			self::PHONE_NUMBER_VERIFIED => new DevblocksSearchField(self::PHONE_NUMBER_VERIFIED, 'identity', 'phone_number_verified', $translate->_('dao.identity.phone_number_verified'), null, true),
			self::ADDRESS => new DevblocksSearchField(self::ADDRESS, 'identity', 'address', $translate->_('dao.identity.address'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'identity', 'created_at', $translate->_('common.created'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'identity', 'updated_at', $translate->_('common.updated'), null, true),

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

class Model_Identity extends DevblocksRecordModel {
	public $id;
	public $pool_id;
	public $name;
	public $given_name;
	public $family_name;
	public $middle_name;
	public $nickname;
	public $username;
	public $website;
	public $email_id;
	public $email_verified;
	public $gender;
	public $birthdate;
	public $zoneinfo;
	public $locale;
	public $phone_number;
	public $phone_number_verified;
	public $address;
	public $created_at;
	public $updated_at;
	
	function getEmailModel() {
		if(!$this->email_id)
			return null;
		
		return DAO_Address::get($this->email_id);
	}
	
	function getEmailAsString() {
		if(false == ($email = $this->getEmailModel()))
			return null;
		
		return $email->email;
	}
	
	function getGenderAsString() {
		switch($this->gender) {
			case 'F':
				return 'female';
				
			case 'M':
				return 'male';
		}
		
		return null;
	}
	
	function getIdentityPool() {
		if(!$this->pool_id)
			return null;
		
		return DAO_IdentityPool::get($this->pool_id);
	}
	
	function login($password) {
		return false;
	}
};

class View_Identity extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'identity';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.identity');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Identity::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Identity::NAME,
			SearchFields_Identity::POOL_ID,
			SearchFields_Identity::USERNAME,
			SearchFields_Identity::EMAIL_ID,
			SearchFields_Identity::EMAIL_VERIFIED,
			SearchFields_Identity::GENDER,
			SearchFields_Identity::ZONEINFO,
			SearchFields_Identity::LOCALE,
			SearchFields_Identity::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Identity::VIRTUAL_CONTEXT_LINK,
			SearchFields_Identity::VIRTUAL_HAS_FIELDSET,
			SearchFields_Identity::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Identity::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Identity');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Identity', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Identity', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_Identity::EMAIL_ID:
				case SearchFields_Identity::EMAIL_VERIFIED:
				case SearchFields_Identity::GENDER:
				case SearchFields_Identity::LOCALE:
				case SearchFields_Identity::PHONE_NUMBER_VERIFIED:
				case SearchFields_Identity::POOL_ID:
				case SearchFields_Identity::ZONEINFO:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Identity::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Identity::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Identity::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_IDENTITY;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Identity::EMAIL_VERIFIED:
			case SearchFields_Identity::PHONE_NUMBER_VERIFIED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_Identity::EMAIL_ID:
			case SearchFields_Identity::GENDER:
			case SearchFields_Identity::POOL_ID:
				$label_map = function($values) use ($column) {
					return SearchFields_Identity::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_Identity::GENDER:
			case SearchFields_Identity::LOCALE:
			case SearchFields_Identity::ZONEINFO:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Identity::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Identity::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Identity::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Identity::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Identity::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Identity::CREATED_AT),
				),
			'email.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Identity::EMAIL_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Identity::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_IDENTITY],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Identity::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_IDENTITY, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Identity::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'pool.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Identity::POOL_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_IDENTITY_POOL, 'q' => ''],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Identity::UPDATED_AT),
				),
			'username' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Identity::USERNAME),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Identity::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Identity::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_IDENTITY, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Identity::VIRTUAL_WATCHERS, $tokens);

			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_IDENTITY);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/identity/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_Identity::EMAIL_ID:
			case SearchFields_Identity::GENDER:
			case SearchFields_Identity::POOL_ID:
				$label_map = function($values) use ($field) {
					return SearchFields_Identity::getLabelsForKeyValues($field, $values);
				};
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Identity::EMAIL_VERIFIED:
			case SearchFields_Identity::PHONE_NUMBER_VERIFIED:
				parent::_renderCriteriaParamBoolean($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Identity::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Identity::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Identity::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Identity::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Identity::NAME:
			case SearchFields_Identity::GIVEN_NAME:
			case SearchFields_Identity::FAMILY_NAME:
			case SearchFields_Identity::MIDDLE_NAME:
			case SearchFields_Identity::NICKNAME:
			case SearchFields_Identity::USERNAME:
			case SearchFields_Identity::WEBSITE:
			case SearchFields_Identity::GENDER:
			case SearchFields_Identity::BIRTHDATE:
			case SearchFields_Identity::ZONEINFO:
			case SearchFields_Identity::LOCALE:
			case SearchFields_Identity::PHONE_NUMBER:
			case SearchFields_Identity::ADDRESS:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Identity::EMAIL_ID:
			case SearchFields_Identity::ID:
			case SearchFields_Identity::POOL_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Identity::CREATED_AT:
			case SearchFields_Identity::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Identity::EMAIL_VERIFIED:
			case SearchFields_Identity::PHONE_NUMBER_VERIFIED:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Identity::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Identity::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Identity::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
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

class Context_Identity extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_IDENTITY;
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Identity::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=identity&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Identity();
		
		$properties['address'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.address'),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $model->address,
		);
		
		$properties['birthdate'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.dob'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->birthdate,
		);
		
		$properties['created_at'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['email_id'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->email_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			],
		);
		
		$properties['email_verified'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.email_verified'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->email_verified,
		);
		
		$properties['family_name'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.family_name'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->family_name,
		);
		
		$properties['gender'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.gender'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->gender,
		);
		
		$properties['given_name'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.given_name'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->given_name,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['locale'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.locale'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->locale,
		);
		
		$properties['middle_name'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.middle_name'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->middle_name,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['nickname'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.nickname'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->nickname,
		);
		
		$properties['phone'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.phone'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->phone_number,
		);
		
		$properties['phone_verified'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.identity.phone_number_verified'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->phone_number_verified,
		);
		
		$properties['pool_id'] = array(
			'label' => mb_ucfirst($translate->_('common.identity.pool')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->pool_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_IDENTITY_POOL,
			],
		);
		
		$properties['updated_at'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['username'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.username'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->username,
		);
		
		$properties['website'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.website'),
			'type' => Model_CustomField::TYPE_URL,
			'value' => $model->website,
		);
		
		$properties['zoneinfo'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.timezone'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->zoneinfo,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($identity = DAO_Identity::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($identity->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $identity->id,
			'name' => $identity->name,
			'permalink' => $url,
			'updated' => $identity->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($identity, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Identity:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_IDENTITY);

		// Polymorph
		if(is_numeric($identity)) {
			$identity = DAO_Identity::get($identity);
		} elseif($identity instanceof Model_Identity) {
			// It's what we want already.
		} elseif(is_array($identity)) {
			$identity = Cerb_ORMHelper::recastArrayToModel($identity, 'Model_Identity');
		} else {
			$identity = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'address' => $prefix.$translate->_('dao.identity.address'),
			'birthdate' => $prefix.$translate->_('common.dob'),
			'created_at' => $prefix.$translate->_('common.created'),
			'email_verified' => $prefix.$translate->_('dao.identity.email_verified'),
			'family_name' => $prefix.$translate->_('dao.identity.family_name'),
			'gender' => $prefix.$translate->_('common.gender'),
			'given_name' => $prefix.$translate->_('dao.identity.given_name'),
			'id' => $prefix.$translate->_('common.id'),
			'locale' => $prefix.$translate->_('dao.identity.locale'),
			'middle_name' => $prefix.$translate->_('dao.identity.middle_name'),
			'name' => $prefix.$translate->_('common.name'),
			'nickname' => $prefix.$translate->_('dao.identity.nickname'),
			'phone' => $prefix.$translate->_('common.phone'),
			'phone_verified' => $prefix.$translate->_('dao.identity.phone_number_verified'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'username' => $prefix.$translate->_('common.username'),
			'website' => $prefix.$translate->_('common.website'),
			'zoneinfo' => $prefix.$translate->_('common.timezone'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'address' => Model_CustomField::TYPE_MULTI_LINE,
			'birthdate' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'email_verified' => Model_CustomField::TYPE_CHECKBOX,
			'family_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'gender' => Model_CustomField::TYPE_SINGLE_LINE,
			'given_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'locale' => Model_CustomField::TYPE_SINGLE_LINE,
			'middle_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'nickname' => Model_CustomField::TYPE_SINGLE_LINE,
			'phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'phone_verified' => Model_CustomField::TYPE_CHECKBOX,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'username' => Model_CustomField::TYPE_SINGLE_LINE,
			'website' => Model_CustomField::TYPE_URL,
			'zoneinfo' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_IDENTITY;
		$token_values['_types'] = $token_types;
		
		if($identity) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $identity->name;
			$token_values['address'] = $identity->address;
			$token_values['birthdate'] = $identity->birthdate;
			$token_values['created_at'] = $identity->created_at;
			$token_values['email_verified'] = $identity->email_verified;
			$token_values['family_name'] = $identity->family_name;
			$token_values['gender'] = $identity->gender;
			$token_values['given_name'] = $identity->given_name;
			$token_values['id'] = $identity->id;
			$token_values['locale'] = $identity->locale;
			$token_values['middle_name'] = $identity->middle_name;
			$token_values['name'] = $identity->name;
			$token_values['nickname'] = $identity->nickname;
			$token_values['phone'] = $identity->phone_number;
			$token_values['phone_verified'] = $identity->phone_number_verified;
			$token_values['updated_at'] = $identity->updated_at;
			$token_values['username'] = $identity->username;
			$token_values['website'] = $identity->website;
			$token_values['zoneinfo'] = $identity->zoneinfo;
			
			$token_values['email_id'] = $identity->email_id;
			$token_values['pool_id'] = $identity->pool_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($identity, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=identity&id=%d-%s",$identity->id, DevblocksPlatform::strToPermalink($identity->name)), true);
		}
		
		// Email address
		$merge_token_labels = $merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'email_',
			$prefix.'Email:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Identity Pool
		$merge_token_labels = $merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_IDENTITY_POOL, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'pool_',
			$prefix.'Pool:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'address' => DAO_Identity::ADDRESS,
			'birthdate' => DAO_Identity::BIRTHDATE,
			'created_at' => DAO_Identity::CREATED_AT,
			'email_id' => DAO_Identity::EMAIL_ID,
			'email_verified' => DAO_Identity::EMAIL_VERIFIED,
			'family_name' => DAO_Identity::FAMILY_NAME,
			'gender' => DAO_Identity::GENDER,
			'given_name' => DAO_Identity::GIVEN_NAME,
			'id' => DAO_Identity::ID,
			'links' => '_links',
			'locale' => DAO_Identity::LOCALE,
			'middle_name' => DAO_Identity::MIDDLE_NAME,
			'name' => DAO_Identity::NAME,
			'nickname' => DAO_Identity::NICKNAME,
			'phone' => DAO_Identity::PHONE_NUMBER,
			'phone_verified' => DAO_Identity::PHONE_NUMBER_VERIFIED,
			'pool_id' => DAO_Identity::POOL_ID,
			'updated_at' => DAO_Identity::UPDATED_AT,
			'username' => DAO_Identity::USERNAME,
			'website' => DAO_Identity::WEBSITE,
			'zoneinfo' => DAO_Identity::ZONEINFO,
		];
	}
	
	// [TODO]
	function getKeyMeta($with_dao_fields=true) {
		return parent::getKeyMeta($with_dao_fields);
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
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
		
		$context = CerberusContexts::CONTEXT_IDENTITY;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? null;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
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
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
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
		$view->name = 'Identity';
		/*
		$view->addParams(array(
			SearchFields_Identity::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Identity::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_Identity::UPDATED_AT;
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
		$view->name = 'Identity';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Identity::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_IDENTITY;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_Identity::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_Identity::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Languages
			$translate = DevblocksPlatform::getTranslationService();
			$locales = $translate->getLocaleStrings();
			$tpl->assign('languages', $locales);
			
			// Timezones
			$date = DevblocksPlatform::services()->date();
			$tpl->assign('timezones', $date->getTimezones());
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/identity/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
