<?php
class DAO_Classifier extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const DICTIONARY_SIZE = 'dictionary_size';
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const PARAMS_JSON = 'params_json';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'cerb_classifiers';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::DICTIONARY_SIZE)
			->uint()
			->setEditable(false)
			;
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
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
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
		
		$sql = "INSERT INTO classifier () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_CLASSIFIER, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_CLASSIFIER;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CLASSIFIER, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'classifier', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.classifier.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CLASSIFIER, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('classifier', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CLASSIFIER;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		@$owner_context = $fields[self::OWNER_CONTEXT];
		@$owner_context_id = intval($fields[self::OWNER_CONTEXT_ID]);
		
		// Verify that the actor can use this new owner
		if($owner_context) {
			if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $actor)) {
				$error = DevblocksPlatform::translate('error.core.no_acl.owner');
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Classifier[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, owner_context, owner_context_id, created_at, updated_at, dictionary_size, params_json ".
			"FROM classifier ".
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
	 * @return Model_Classifier[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_Classifier::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}

	static function getReadableByActor($actor_context, $actor_context_id) {
		$classifiers = self::getAll();
		
		$classifiers = array_filter($classifiers, function($classifier) use ($actor_context, $actor_context_id) {
			return CerberusContexts::isReadableByActor($classifier->owner_context, $classifier->owner_context_id, array($actor_context, $actor_context_id));
		});
		
		return $classifiers;
	}

	/**
	 * @param integer $id
	 * @return Model_Classifier
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(array_key_exists($id, $objects))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_Classifier[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Classifier[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Classifier();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			$object->dictionary_size = $row['dictionary_size'];
			
			if(false != ($params_json = json_decode($row['params_json'] ?? '', true)))
				$object->params = $params_json;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('classifier');
	}
	
	static public function countByBot($bot_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(*) FROM classifier ".
			"WHERE owner_context = %s AND owner_context_id = %d",
			$db->qstr(CerberusContexts::CONTEXT_BOT),
			$bot_id
		));
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_CLASSIFIER;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM classifier WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Classifier::getFields();
		
		switch($sortBy) {
			case SearchFields_Classifier::VIRTUAL_OWNER:
				$sortBy = SearchFields_Classifier::OWNER_CONTEXT;
				
				if(!in_array($sortBy, $columns))
					$columns[] = $sortBy;
				break;
		}
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Classifier', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"classifier.id as %s, ".
			"classifier.name as %s, ".
			"classifier.owner_context as %s, ".
			"classifier.owner_context_id as %s, ".
			"classifier.created_at as %s, ".
			"classifier.updated_at as %s, ".
			"classifier.dictionary_size as %s, ".
			"classifier.params_json as %s ",
				SearchFields_Classifier::ID,
				SearchFields_Classifier::NAME,
				SearchFields_Classifier::OWNER_CONTEXT,
				SearchFields_Classifier::OWNER_CONTEXT_ID,
				SearchFields_Classifier::CREATED_AT,
				SearchFields_Classifier::UPDATED_AT,
				SearchFields_Classifier::DICTIONARY_SIZE,
				SearchFields_Classifier::PARAMS_JSON
			);
			
		$join_sql = "FROM classifier ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Classifier');
	
		return array(
			'primary_table' => 'classifier',
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
			SearchFields_Classifier::ID,
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

class SearchFields_Classifier extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const CREATED_AT = 'c_created_at';
	const UPDATED_AT = 'c_updated_at';
	const DICTIONARY_SIZE = 'c_dictionary_size';
	const PARAMS_JSON = 'c_params_json';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'classifier.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CLASSIFIER => new DevblocksSearchFieldContextKeys('classifier.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CLASSIFIER, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromFieldset($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, self::getPrimaryKey());
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'classifier.owner_context', 'classifier.owner_context_id');
			
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
			case 'owner':
				$key = 'owner';
				$search_key = 'owner';
				$owner_field = $search_fields[SearchFields_Classifier::OWNER_CONTEXT];
				$owner_id_field = $search_fields[SearchFields_Classifier::OWNER_CONTEXT_ID];
				
				return [
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'sql_select' => sprintf("CONCAT_WS(':',%s.%s,%s.%s)",
						Cerb_ORMHelper::escape($owner_field->db_table),
						Cerb_ORMHelper::escape($owner_field->db_column),
						Cerb_ORMHelper::escape($owner_id_field->db_table),
						Cerb_ORMHelper::escape($owner_id_field->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('owner'),
				];
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Classifier::ID:
				$models = DAO_Classifier::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				
			case 'owner':
				return self::_getLabelsForKeyContextAndIdValues($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'classifier', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'classifier', 'name', $translate->_('common.name'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'classifier', 'owner_context', $translate->_('common.owner_context'), null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'classifier', 'owner_context_id', $translate->_('common.owner_context_id'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'classifier', 'created_at', $translate->_('common.created'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'classifier', 'updated_at', $translate->_('common.updated'), null, true),
			self::DICTIONARY_SIZE => new DevblocksSearchField(self::DICTIONARY_SIZE, 'classifier', 'dictionary_size', $translate->_('dao.classifier.dictionary_size'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'classifier', 'params_json', $translate->_('common.params'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner')),
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

class Model_Classifier extends DevblocksRecordModel {
	public $id = 0;
	public $name = null;
	public $owner_context = null;
	public $owner_context_id = 0;
	public $created_at = 0;
	public $updated_at = 0;
	public $dictionary_size = 0;
	public $params = [];
	
	function trainModel() {
		self::clearModel($this->id);
		
		// Load examples
		$examples = DAO_ClassifierExample::getByClassifier($this->id);
		
		foreach($examples as $example) {
			// Only train examples with a given class_id
			if(!$example->class_id)
				continue;
			
			self::train($example->expression, $example->classifier_id, $example->class_id, true);
		}
		
		self::build($this->id);
	}
	
	static function clearModel($classifier_id) {
		$db = DevblocksPlatform::services()->database();
		
		DAO_Classifier::update($classifier_id, array(
			DAO_Classifier::DICTIONARY_SIZE => 0,
			DAO_Classifier::UPDATED_AT => time(),
		));
		
		$db->ExecuteMaster(sprintf("UPDATE classifier_class SET training_count = 0, dictionary_size = 0 WHERE classifier_id = %d", $classifier_id));
		
		$db->ExecuteMaster(sprintf("DELETE FROM classifier_ngram_to_class WHERE class_id IN (SELECT id FROM classifier_class WHERE classifier_id = %d)", $classifier_id));
		
		return true;
	}
	
	static function build($classifier_id) {
		self::_updateCounts($classifier_id);
		
		DAO_Classifier::clearCache();
		DAO_ClassifierClass::clearCache();
		DAO_ClassifierEntity::clearCache();
	}
	
	private static function _updateCounts($classifier_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SET @class_ids := (SELECT GROUP_CONCAT(id) FROM classifier_class WHERE classifier_id = %d)",
			$classifier_id
		);
		$db->ExecuteMaster($sql);
		
		// Cache entity hints per classifier
		$sql = "UPDATE classifier_class SET entities = (SELECT GROUP_CONCAT(SUBSTRING(n.token,2,LENGTH(n.token)-2)) FROM classifier_ngram n INNER JOIN classifier_ngram_to_class c ON (n.id=c.token_id) WHERE n.token LIKE '[%' AND c.class_id = classifier_class.id)";
		$db->ExecuteMaster($sql);
		
		$sql = sprintf("UPDATE classifier_class SET dictionary_size = (SELECT COUNT(DISTINCT token_id) FROM classifier_ngram_to_class WHERE class_id=classifier_class.id), training_count = (SELECT count(id) FROM classifier_example WHERE class_id=classifier_class.id) WHERE FIND_IN_SET(id, @class_ids)");
		$db->ExecuteMaster($sql);
		
		$sql = sprintf("UPDATE classifier SET dictionary_size = (SELECT COUNT(DISTINCT token_id) FROM classifier_ngram_to_class WHERE FIND_IN_SET(class_id, @class_ids)) WHERE id = %d",
			$classifier_id
		);
		$db->ExecuteMaster($sql);
	}
	
	static function getEntities() {
		$entities = [
			'contact' => [
				'label' => 'Contact',
				'description' => 'The name of a contact',
			],
			'date' => [
				'label' => 'Date',
				'description' => 'July 9, 2019-09-29, the 1st',
			],
			'duration' => [
				'label' => 'Duration',
				'description' => 'for 5 mins, for 2 hours',
			],
			'number' => [
				'label' => 'Number',
				'description' => 'A number',
			],
			'org' => [
				'label' => 'Organization',
				'description' => 'The name of an organization',
			],
			'context' => [
				'label' => 'Record type',
				'description' => 'message, task, ticket, worker',
			],
			'status' => [
				'label' => 'Status',
				'description' => 'open, closed, waiting, completed, active',
			],
			'temperature' => [
				'label' => 'Temperature',
				'description' => '212F, 12C, 75 degrees, 32 F',
			],
			'time' => [
				'label' => 'Time',
				'description' => 'at 2pm, 08:00, noon, in the morning, from now, in hours',
			],
			'worker' => [
				'label' => 'Worker',
				'description' => 'The name of a worker',
			]
		];
		
		$custom_entities = DAO_ClassifierEntity::getAll();
		
		foreach($custom_entities as $entity) {
			$entities[DevblocksPlatform::strLower($entity->name)] = [
				'label' => $entity->name,
				'description' => $entity->description,
			];
		}
		
		return $entities;
	}
	
	static function train($text, $classifier_id, $class_id, $delta=false) {
		$db = DevblocksPlatform::services()->database();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		// Convert tags to tokens
		
		$tagged_text = preg_replace('#\{\{(.*?)\:(.*?)\}\}#','[${1}]', $text);
		
		// Tokenize words
		// [TODO] Apply filtering based on classifier
		
		// [TODO] Unigrams
		$tokens = $bayes::tokenizeWords($tagged_text);
		//$tokens = $bayes::tokenizeStrings($tagged_text);
		
		// Remove stop words
		// [TODO] Make this configurable
		//$tokens = array_diff($tokens, $bayes::$STOP_WORDS_EN);
		
		// Generate ngrams (if configured)
		// [TODO] Make this configurable
		
		if(false) {
			$ngrams = $bayes::getNGrams(implode(' ', $tokens), 2);
			
			array_walk($ngrams, function($ngram) use (&$tokens) {
				$tokens[] = implode(' ', $ngram);
			});
		}
		
		// [TODO] Don't care about frequency in a single example, just over # examples
		//$words = array_count_values($tokens);
		$words = array_fill_keys($tokens, 1);
		
		$values = array_fill_keys(array_keys($words), 0);
		
		array_walk($values, function(&$v, $word) use ($db){
			$v = sprintf("(%s, %d)",
				$db->qstr($word),
				count(explode(' ', $word))
			);
		});
		
		// Save any new unigrams
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO classifier_ngram (token, n) VALUES %s",
			implode(',', $values)
		));
		
		// Pull the IDs for all unigrams in this text
		$results = $db->GetArrayMaster(sprintf("SELECT id, token FROM classifier_ngram WHERE token IN (%s)",
			implode(',', $db->qstrArray(array_keys($words)))
		));
		$token_ids = array_column($results, 'id', 'token');
		
		$values = [];
		
		foreach($token_ids as $token => $token_id) {
			$values[] = sprintf("(%d,%d,%d)",
				$token_id, $class_id, $words[$token]
			);
		}
		
		if(!empty($values)) {
			$sql = sprintf("INSERT INTO classifier_ngram_to_class (token_id, class_id, training_count) ".
				"VALUES %s ".
				"ON DUPLICATE KEY UPDATE training_count=training_count+VALUES(training_count)",
				implode(',', $values)
			);
			$db->ExecuteMaster($sql);
			
			if(!$delta) {
				self::build($classifier_id);
			}
			
			// [TODO] Invalidate caches
		}
	}
	
	// [TODO] remind me about lunch at Twenty Nine Palms Resort on the fifth at five thirty pm for sixty mins
	// [TODO] $environment has locale, lang, me
	static function predict($text, $classifier_id, $environment=[]) {
		$db = DevblocksPlatform::services()->database();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		// Load all classes
		// [TODO] This would be cached between training
		// [TODO] Add the training sum to the classifier
		$results = $db->GetArrayMaster('SELECT id, name, dictionary_size, params_json FROM classifier');
		$classifiers = array_column($results, null, 'id');
		//var_dump($classifiers);
		
		if(!isset($classifiers[$classifier_id]))
			return false;
		
		// Load the frequency of classes for this classifier from the database
		// [TODO] This would be cached between training (by classifier)
		$results = $db->GetArrayMaster(sprintf('SELECT id, name, training_count, dictionary_size, entities FROM classifier_class WHERE classifier_id = %d', $classifier_id));
		$classes = array_column($results, null, 'id');
		$class_freqs = array_column($results, 'training_count', 'id');
		
		$raw_words = $bayes::tokenizeWords($text);
		
		$tags = self::tag($raw_words, $environment);
		
		// [TODO] Disambiguate tags once
		
		$class_data = [];
		$unique_tokens = [];
		
		foreach($classes as $class_id => $class) {
			$tokens = $words = $raw_words;
			$types = DevblocksPlatform::parseCsvString($class['entities']);
			
			// [TODO] Check required types?
			
			$entities = self::extractNamedEntities($raw_words, $tags, $types);
			
			foreach($entities as $entity_type => $results) {
				foreach($results as $result) {
					foreach(array_keys($result['range']) as $pos) {
						$tokens[$pos] = sprintf('[%s]', $entity_type);
					}
				}
			}
			
			/*
			if(false) {
				$ngrams = $bayes::getNGrams(implode(' ', $tokens), 2);
				
				array_walk($ngrams, function($ngram) use (&$tokens) {
					$tokens[] = implode(' ', $ngram);
				});
			}
			*/
			
			$class_data[$class_id] = [
				'tokens' => $tokens,
				'words' => $words,
				//'tags' => $tags,
				'token_counts' => array_count_values($tokens),
				'token_freqs' => array_fill_keys($tokens, 0),
				//'token_tfidf' => array_fill_keys($tokens, 0),
				'entities' => $entities,
				'p_class' => 0.0000,
				'p' => 0.0000,
			];
			
			$unique_tokens = array_replace($unique_tokens, array_flip($tokens));
		}
		
		$unique_tokens = array_keys($unique_tokens);
		
		// [TODO] Suppress stop words
		$unique_tokens = $bayes::preprocessWordsRemoveStopWords($unique_tokens, $bayes::$STOP_WORDS_EN);
		
		// [TODO] Add every entity too?
		//$unique_tokens = array_unique(array_merge($unique_tokens, array_values($bayes::$TAGS_TO_TOKENS)));
		
		$corpus_freqs = [];
		
		// Bayes theorem: P(i=ask|x) = P(x|ask=1) * P(ask=1) / P(x|ask=0) * P(ask=0) + P(x|ask=1) * P(ask=1) ...
		
		// [TODO] Handle classifier options (stop words, etc)
		
		// Do a single query to pull all the tokens for the classifier
		
		if(empty($classes) || empty($unique_tokens)) {
			$results = array();
		} else {
			$sql = sprintf("SELECT u.token, utc.class_id, utc.training_count FROM classifier_ngram_to_class utc INNER JOIN classifier_ngram u ON (utc.token_id=u.id) WHERE utc.class_id IN (%s) AND u.token IN (%s)",
				implode(',', array_keys($classes)),
				implode(',', $db->qstrArray($unique_tokens))
			);
			$results = $db->GetArrayMaster($sql);
		}
		
		//****** IDF *******
		
		/*
		$idf = [];
		
		foreach($results as $row) {
			@$count = intval($idf[$row['token']]);
 			$idf[$row['token']] = ++$count;
		}
		
		$num_classes = count($classes);
		
		array_walk($idf, function(&$val, $term) use ($num_classes) {
			$val = log($num_classes/$val);
		});
		
		//var_dump($idf);
		 */
		
		//****** IDF *******
		
		// Merge in the classification frequencies
		
		foreach($results as $row) {
			if(isset($class_data[$row['class_id']]['token_freqs'][$row['token']]))
				$class_data[$row['class_id']]['token_freqs'][$row['token']] = intval($row['training_count']);
			
			/*
			if(DevblocksPlatform::strStartsWith($row['token'], '['))
				$class_data[$row['class_id']]['entity_counts'][$row['token']] = intval($row['training_count']);
			*/
			
			$corpus_freqs[$row['token']] = intval($corpus_freqs[$row['token']] ?? null) + intval($row['training_count']);
		}
		
		// Test each class
		
		$class_freqs_sum = array_sum($class_freqs);
		
		foreach($class_data as $class_id => $data) {
			//$training_count = @$classes[$class_id]['training_count'] ?: 0;
			
			// If we've never seen this class in training (or any class), we can't make any predictions
			if(0 == $class_freqs_sum || 0 == $classifiers[$classifier_id]['dictionary_size']) {
				$class_data[$class_id]['p'] = 0;
				continue;
			}
			
			// If the training includes an entity 100% of the time and we don't have it, predict 0%
			/*
			foreach($bayes::$TAGS_TO_TOKENS as $entity) {
				$p_entity = (@$data['entity_counts'][$entity] ?: 0) / $training_count;
				
				if($p_entity == 1 && !in_array($entity, $data['tokens'])) {
					$class_data[$class_id]['p'] = 0;
					continue 2;
				}
			}
			*/
			
			// [TODO] If none of our tokens matched up, skip this intent
			// [TODO] If we uncomment this, it just picks something arbitrary anyway
			/*
			if(0 == array_sum($data['token_freqs'])) {
				$class_data[$class_id]['p'] = 0;
				continue;
			}
			*/
			
			// [TODO] Option for weighted classes
			//$class_prob = $class_freqs[$class_id] / $class_freqs_sum;
			
			// [TODO] Option for equiprobable classes
			$class_prob = 1/count($classes);
			
			$class_data[$class_id]['p_class'] = $class_prob;
			$probs = [];
			
			// Laplace smoothing
			// [TODO] How many examples had the term vs how many examples exist for this intent
			foreach($data['token_freqs'] as $token => $count) {
				$probs[$token] = ($count + 0.4) / (($corpus_freqs[$token] ?? 0) + $classifiers[$classifier_id]['dictionary_size']);
			}
			
			$class_data[$class_id]['p'] = array_product($probs) * $class_prob;
		}
		
		$p_x = array_sum(array_column($class_data, 'p'));
		$results = array();
		
		// Normalize confidence scores
		foreach($class_data as $class_id => $data) {
			if(0 == $p_x) {
				$p = 0;
			} else {
				$p = $data['p'] / $p_x;
			}
			
			$results[$class_id] = $p;
		}
		
		arsort($results);
		
		//var_dump($results);
		
		$predicted_class_id = key($results);
		$predicted_class_confidence = current($results);
		
		// [TODO] Setting for default class for low confidence
		/*
		if($predicted_class_confidence < 0.30) {
			$predicted_class_id = 2;
			$predicted_class_confidence = $results[2];
		}
		*/
		
		//var_dump($class_data[$predicted_class_id]);
		
		$params = [];
		
		if(@isset($class_data[$predicted_class_id]['entities']) && is_array($class_data[$predicted_class_id]['entities']))
			foreach($class_data[$predicted_class_id]['entities'] as $entity_type => $results) {
				if(!isset($params[$entity_type]))
					$params[$entity_type] = [];
				
				foreach($results as $result_id => $result) {
					$param = [];
					
					switch($entity_type) {
						case 'contact':
							@$ids = array_keys($result['value']);
							
							if(empty($ids))
								break;
							
							$contacts = DAO_Contact::getIds($ids);
							
							$param_key = implode(' ', $result['range']);
							
							if(!isset($params['contact']))
								$params['contact'] = [];
							
							// Preserve order
							foreach($ids as $id) {
								//if(!isset($contacts[$id]) || isset($params['contact'][$id]))
								if(!isset($contacts[$id]))
									continue;
								
								$contact = $contacts[$id];
								
								if(!isset($params['contact'][$param_key]))
									$params['contact'][$param_key] = [];
								
								$params['contact'][$param_key][$contact->id] = [
									'id' => $contact->id,
									'email_id' => $contact->primary_email_id,
									'email' => $contact->getEmailAsString(),
									'first_name' => $contact->first_name,
									'full_name' => $contact->getName(),
									'gender' => $contact->gender,
									'image' => $contact->getImageUrl(),
									'language' => $contact->language,
									'last_name' => $contact->last_name,
									'location' => $contact->location,
									'mobile' => $contact->mobile,
									'org' => $contact->getOrgAsString(),
									'org_id' => $contact->org_id,
									'org_image' => $contact->getOrgImageUrl(),
									'phone' => $contact->phone,
									'timezone' => $contact->timezone,
									'title' => $contact->title,
									'updated' => $contact->updated_at,
								];
							}
							break;
						
						case 'context':
							if(!empty($result['value']))
								$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
							break;
						
						case 'date':
							$param_key = implode(' ', $result['range']);
							$date_words = $result['range'];
							$seq = $result['sequence'];
							
							// [TODO] normalize 'on the fifth' -> '5th'
							
							// Normalize wed/weds
							if(false !== ($hits = array_intersect($date_words, ['weds']))) {
								foreach($hits as $idx => $hit) {
									$date_words[$idx] = 'wed';
								}
							}
							
							// Normalize thu/thur/thurs
							if(false !== ($hits = array_intersect($date_words, ['thur', 'thurs']))) {
								foreach($hits as $idx => $hit) {
									$date_words[$idx] = 'thu';
								}
							}
							
							// In dates and times, tag 'a' and 'an' as a {number:1}
							if(false !== ($hits = array_intersect($seq, ['a','an']))) {
								foreach($hits as $idx => $hit) {
									$date_words[$idx] = '1';
									$seq[$idx] = '{number}';
								}
							}
							
							foreach($seq as $idx => $token) {
								if('{number}' == $token) {
									if(false !== ($ordinal = array_search($date_words[$idx], $bayes::$NUM_WORDS)))
										$date_words[$idx] = $ordinal;
									
									if(false !== ($ordinal = array_search($date_words[$idx], $bayes::$NUM_ORDINAL)))
										$date_words[$idx] = $ordinal;
								}
							}
							
							// Flip a relative number negative if _before_ an anchor
							if(false !== ($pos = $bayes::_findSubsetInArray(['{number}','{date_unit}','before'], $seq))) {
								$date_words[$pos] = '-' . abs($date_words[$pos]);
								unset($date_words[$pos+2]);
								unset($seq[$pos+2]);
							}
							
							// Flip a relative number negative if _ago_ an anchor
							if(false !== ($pos = $bayes::_findSubsetInArray(['{number}','{date_unit}','ago'], $seq))) {
								$date_words[$pos] = '-' . abs($date_words[$pos]);
								unset($date_words[$pos+2]);
								unset($seq[$pos+2]);
							}
							
							$date_string = implode(' ', array_intersect_key($date_words, array_filter($seq, function($seq) {
								return '{' == substr($seq, 0, 1);
							})));
							
							$date_string = str_replace($bayes::$DAYS_LONG_PLURAL, $bayes::$DAYS_LONG, $date_string);
							
							// [TODO] Handle ranges
							if($date_string == 'this month') {
								$date_string = 'first day of this month';
								
							} else if($date_string == 'last month') {
								$date_string = 'first day of last month';
								
							} else if($date_string == 'this year') {
								$date_string = '1 Jan ' . date('Y');
								
							} else if($date_string == 'last year') {
								$date_string = '1 Jan ' . date('Y', strtotime('last year'));
								
								// [TODO] Verify the third position here is a date_unit
							} else if(preg_match('#^(past|last|prev|previous|prior) (\d+) (\w+)$#i', $date_string, $matches)) {
								$date_string = sprintf("-%d %s",
									$matches[2],
									$matches[3]
								);
								
								// [TODO] Verify the second position here is a date_unit
							} else if(preg_match('#^(past|last|prev|previous|prior) (\w+)$#i', $date_string, $matches)) {
								$date_string = sprintf("-1 %s",
									$matches[2]
								);
								
								// [TODO] Verify the third position here is a date_unit
							} else if(preg_match('#^(next) (\d+) (\w+)$#i', $date_string, $matches)) {
								$date_string = sprintf("+%d %s",
									$matches[2],
									$matches[3]
								);
							}
							
							//var_dump($date_string);
							
							if(!isset($params['date']))
								$params['date'] = [];
							
							$params['date'][$param_key] = [
								'date' => date('Y-m-d', strtotime($date_string)),
							];
							break;
						
						// [TODO] This can't handle "for the next 2 hours"
						// [TODO] "For three and a half hours"
						case 'duration':
							$param_key = implode(' ', $result['range']);
							$dur_words = $result['range'];
							$seq = $result['sequence'];
							
							// In durations, tag 'a' and 'an' as a {number:1}
							if(false !== ($hits = array_intersect($seq, ['a','an']))) {
								foreach($hits as $idx => $hit) {
									$dur_words[$idx] = '1';
									$seq[$idx] = '{number}';
								}
							}
							
							foreach($seq as $idx => $token) {
								if('{number}' == $token) {
									if(false !== ($ordinal = array_search($dur_words[$idx], $bayes::$NUM_WORDS)))
										$dur_words[$idx] = $ordinal;
								}
							}
							
							$dur_string = implode(' ', array_intersect_key($dur_words, array_filter($seq, function($token) {
								return '{' == substr($token, 0, 1);
							})));
							
							// [TODO] for time_unit we can use seconds.  For date_unit we could pick an absolute
							
							if(!isset($params['duration']))
								$params['duration'] = [];
							
							$params['duration'][$param_key] = [
								'secs' => strtotime($dur_string) - time(),
							];
							break;
						
						case 'event':
							if(!isset($params[$entity_type]))
								$params[$entity_type] = [];
							
							$param_key = implode(' ', $result['range']);
							
							// [TODO] We should keep a case-sensitive version of the original tokenized string for params
							$params[$entity_type][$param_key] = [
								//'value' => implode(' ', array_intersect_key(explode(' ', $text), $result['range'])),
								'value' => implode(' ', $result['range']),
							];
							break;
						
						case 'org':
							@$ids = array_keys($result['value']);
							
							if(empty($ids))
								break;
							
							$orgs = DAO_ContactOrg::getIds($ids);
							
							$param_key = implode(' ', $result['range']);
							
							if(!isset($params['org']))
								$params['org'] = [];
							
							foreach($ids as $id) {
								if(!isset($orgs[$id]) || isset($params['org'][$id]))
									continue;
								
								$org = $orgs[$id];
								
								if(!isset($params['org'][$param_key]))
									$params['org'][$param_key] = [];
								
								$params['org'][$param_key][$org->id] = [
									'id' => $org->id,
									'name' => $org->name,
									'street' => $org->street,
									'city' => $org->city,
									'image' => $org->getImageUrl(),
									'postal' => $org->postal,
									'province' => $org->province,
									'country' => $org->country,
									'email' => $org->getEmailAsString(),
									'phone' => $org->phone,
									'website' => $org->website,
									'updated' => $org->updated,
								];
							}
							break;
						
						case 'status':
							if(!empty($result['value']))
								$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
							break;
						
						case 'temperature':
							$param_key = implode(' ', $result['range']);
							$temp_words = $result['range'];
							$seq = $result['sequence'];
							
							if(!isset($params['temperature']))
								$params['temperature'] = [];
							
							// [TODO] localize ºC/ºF defaults
							
							$temp_string = trim(implode(' ', $temp_words));
							
							$params['temperature'][$param_key] = [
								'value' => intval($temp_string),
								'unit' => stristr($temp_string, 'c') ? 'C' : 'F', // [TODO] This is way too naive
							];
							break;
						
						case 'time':
							$param_key = implode(' ', $result['range']);
							$time_words = $result['range'];
							$seq = $result['sequence'];
							
							// [TODO] Normalize 'five thirty five pm' -> '5:30 pm'
							
							// In dates and times, tag 'a' and 'an' as a {number:1}
							if(false !== ($hits = array_intersect($seq, ['a','an']))) {
								foreach($hits as $idx => $hit) {
									$time_words[$idx] = '1';
									$seq[$idx] = '{number}';
								}
							}
							
							foreach($seq as $idx => $token) {
								if('{number}' == $token) {
									if(false !== ($ordinal = array_search($time_words[$idx], $bayes::$NUM_WORDS)))
										$time_words[$idx] = $ordinal;
								}
							}
							
							$time_string = implode(' ', array_intersect_key($time_words, array_filter($seq, function($token) {
								return '{' == substr($token, 0, 1);
							})));
							
							// [TODO] in the (morning/afternoon/evening/night)
							$time_string = preg_replace('#^(\d+) (morning)$#', '\1am', $time_string);
							$time_string = preg_replace('#^(\d+) (afternoon|evening)$#', '\1pm', $time_string);
							//$time_string = preg_replace('$#([12,1-4]) (night)$#', '\1am', $time_string);
							//$time_string = preg_replace('$#([5-11]) (night)$#', '\1pm', $time_string);
							
							/*
							switch($val) {
								case 'midnight':
									$val = '00:00';
									break;
								case 'morning':
									$val = '08:00';
									break;
								case 'noon':
									$val = '12:00';
									break;
								case 'afternoon':
									$val = '13:00';
									break;
								case 'evening':
									$val = '17:00';
									break;
								case 'night':
								case 'tonight':
									$val = '20:00';
									break;
							}
							
							// Normalize 8a 5:00p
							if(preg_match('#^([\d\:]+)\s*([ap])$#', $val, $matches)) {
								$val = $matches[1] . $matches[2] . 'm';
							}
							*/
							
							if(!isset($params['time']))
								$params['time'] = [];
							
							$params['time'][$param_key] = [
								'time' => date('H:i:s', strtotime($time_string)),
							];
							break;
						
						case 'worker':
							@$ids = array_keys($result['value']);
							
							if(empty($ids))
								break;
							
							$workers = DAO_Worker::getIds($ids);
							
							$param_key = implode(' ', $result['range']);
							
							if(!isset($params['worker']))
								$params['worker'] = [];
							
							foreach($ids as $id) {
								if(!isset($workers[$id]) || isset($params['worker'][$id]))
									continue;
								
								$worker = $workers[$id];
								
								if(!isset($params['worker'][$param_key]))
									$params['worker'][$param_key] = [];
								
								$params['worker'][$param_key][$worker->id] = [
									'id' => $worker->id,
									'at_mention_name' => $worker->at_mention_name,
									'email_id' => $worker->email_id,
									'email' => $worker->getEmailString(),
									'first_name' => $worker->first_name,
									'full_name' => $worker->getName(),
									'gender' => $worker->gender,
									'image' => $worker->getImageUrl(),
									'language' => $worker->language,
									'last_name' => $worker->last_name,
									'location' => $worker->location,
									'mobile' => $worker->mobile,
									'phone' => $worker->phone,
									'timezone' => $worker->timezone,
									'title' => $worker->title,
									'updated' => $worker->updated,
								];
							}
							break;
						
						default:
							if(false == ($custom_entity = DAO_ClassifierEntity::getByName($entity_type)))
								break;
							
							switch($custom_entity->type) {
								case 'list':
									if(!empty($result['value']))
										$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
									break;
								
								case 'regexp':
									if(!empty($result['value']))
										$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
									break;
								
								case 'text':
									if(!isset($params[$entity_type]))
										$params[$entity_type] = [];
									
									$param_key = implode(' ', $result['range']);
									
									// [TODO] We should keep a case-sensitive version of the original tokenized string for params
									$params[$entity_type][$param_key] = [
										//'value' => implode(' ', array_intersect_key(explode(' ', $text), $result['range'])),
										'value' => implode(' ', $result['range']),
									];
									break;
							}
							
							break;
					}
				}
				
				if(empty($params[$entity_type]))
					unset($params[$entity_type]);
			}
		
		$prediction = [
			'prediction' => [
				'text' => $text,
				//'words' => $words,
				//'tags' => $tags,
				'classifier' => array_intersect_key($classifiers[$classifier_id] ?? [], ['id'=>true,'name'=>true]) ?: [],
				'classification' => array_intersect_key($classes[$predicted_class_id] ?? [], ['id'=>true,'name'=>true,'attribs'=>true]) ?: [],
				'confidence' => $predicted_class_confidence,
				'params' => $params
			]
		];
		
		//var_dump($prediction);
		
		return $prediction;
		
		/*
		// [TODO] Allow more than one prediction to be returned
		// [TODO] Configurable threshold
		if($predicted_class_confidence >= 0.20) {
		} else {
			// [TODO] Default intent
			return ['input'=>$text, 'prediction'=>null];
		}
		*/
	}
	
	public static function extractNamedEntities(array $words, array $tags, array $types=[]) {
		$db = DevblocksPlatform::services()->database();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		$entities = [];
		
		// [TODO] Lemmatize from/for/... ?
		if(in_array('date', $types)) {
			$sequences = [
				'from {number} {date_unit} ago',
				'for {number} {date_unit} ago',
				'since {number} {date_unit} ago',
				'from {number} {date_unit} before {day}',
				'from {number} {date_unit} before {unit_rel} {day}',
				'from a {date_unit} before {day}',
				'from a {date_unit} before {unit_rel} {day}',
				'for {number} {date_unit} before {day}',
				'for {number} {date_unit} before {unit_rel} {day}',
				'for a {date_unit} before {day}',
				'for a {date_unit} before {unit_rel} {day}',
				'since {unit_rel} {day}',
				'since {unit_rel} {date_unit} ago',
				'since {unit_rel} {date_unit}',
				'since {day} {date_unit}',
				'in the {unit_rel} {number} {date_unit}',
				'for the {unit_rel} {number} {date_unit}',
				'for {day} {number} {month} {year}',
				'for {day} {month} {number} {year}',
				'for {day} {month} {year}',
				'for {month} {number} {year}',
				'for {month} {day} {year}',
				'for {number} {month} {year}',
				'on {day} {number} {month} {year}',
				'on {day} {month} {number} {year}',
				'on the {day} of {month} {year}',
				'on {day} {unit_rel} {date_unit}',
				'{day} {unit_rel} {date_unit}',
				'{unit_rel} {date_unit} on {day}',
				'from {month} {number} {year}',
				'from {unit_rel} {day}',
				'from {month} {year}',
				'from {month}',
				'to {month} {number} {year}',
				'to {month} {year}',
				'on {month} {number} {year}',
				'on {month} {day} {year}',
				'on {month} {day}',
				'on {month} {year}',
				'on {month} {number}',
				'on {number} {month} {year}',
				'on {number} {month}',
				'{month} {number} {year}',
				'{month} {day}',
				'{month} {year}',
				'{month} {number}',
				'on the {number} {number}',
				'on the {number}',
				'on the {day}',
				'in {month}',
				'in a {date_unit}',
				'in {number} {day}',
				'in {number} {date_unit}',
				'{number} {date_unit} ago',
				'{unit_rel} {date_unit}',
				'{unit_rel} {day}',
				'since {month}',
				'since {day}',
				'{unit_rel} {date_unit}',
				'{unit_rel} {day}',
				'on {day}',
				'on {date}',
				'for {date}',
				'{day}',
				'{date}',
			];
			
			self::_sequenceToEntity($sequences, 'date', $words, $tags, $entities);
		}
		
		if(in_array('temperature', $types)) {
			$sequences = [
				'{number} {temp_unit} {temp_unit}',
				'{number} {temp_unit}',
				'{temp}',
			];
			
			self::_sequenceToEntity($sequences, 'temperature', $words, $tags, $entities);
		}
		
		if(in_array('time', $types)) {
			$sequences = [
				'at {number} {number} {number} {time_meridiem}',
				'at {number} {number} {number}',
				'at {number} {number} {time_meridiem}',
				'at {number} {number}',
				'at {number} {time_meridiem}',
				'at {number} o\'clock in the {time_rel}',
				'at {number} o\'clock',
				'at {number} in the {time_rel}',
				'in {number} {time_unit}',
				'at {time} {time_meridiem}',
				'at {number} {time_meridiem}',
				'at {time}',
				'at {number}',
				'from {time} {time_meridiem}',
				'from {number} {time_meridiem}',
				'from {time}',
				'to {time} {time_meridiem}',
				'to {number} {time_meridiem}',
				'to {time}',
				'until {time} {time_meridiem}',
				'until {number} {time_meridiem}',
				'until {time}',
				'at {time_rel}',
				'in the {time_rel}',
				'in an {time_unit}',
				'in a {time_unit}',
				'on the {time_unit}',
				'{time_rel}',
				'{time}',
			];
			
			self::_sequenceToEntity($sequences, 'time', $words, $tags, $entities);
		}
		
		if(in_array('duration', $types)) {
			$sequences = [
				'for the {unit_rel} {number} {date_unit}',
				'for the {unit_rel} {number} {time_unit}',
				'for {number} {date_unit} {number} {date_unit}',
				'for {number} {date_unit} {number} {time_unit}',
				'for {number} {time_unit} {number} {time_unit}',
				'for {number} {date_unit}',
				'for {number} {time_unit}',
				'for a {date_unit}',
				'for a {time_unit}',
				'for an {date_unit}',
				'for an {time_unit}',
				'{number} {date_unit}',
				'{number} {time_unit}',
			];
			
			self::_sequenceToEntity($sequences, 'duration', $words, $tags, $entities);
		}
		
		if(in_array('context', $types))
			self::_tagToEntity('context', $words, $tags, $entities);
		
		if(in_array('status', $types))
			self::_tagToEntity('status', $words, $tags, $entities);
		
		if(in_array('worker', $types))
			self::_tagToEntity('worker', $words, $tags, $entities);
		
		if(in_array('contact', $types))
			self::_tagToEntity('contact', $words, $tags, $entities);
		
		if(in_array('org', $types))
			self::_tagToEntity('org', $words, $tags, $entities);
		
		// If we're finding a contact, use '{contact}+ at {org}' and '{contact}+ @ {org}' patterns
		// If we have both an org and a contact described
		if(isset($entities['contact']) && isset($entities['org'])) {
			foreach($entities['org'] as $from_idx => $org) {
				// If we have room for at least two words before the org, and the joiner before {org} is [@, at, from, of]
				if($from_idx - 2 >= 0 && in_array($words[$from_idx-1], ['at','@','from','of'])) {
					// If any contact ends at the position before the joiner
					foreach($entities['contact'] as $contact_idx => $contact) {
						$range = array_keys($contact['range']);
						
						if(end($range) == $from_idx - 2) {
							// Set the range of the contact through the org
							$contact['range'] = $contact['range'] + [$from_idx-1 => $words[$from_idx-1]] + $org['range'];
							$entities['contact'][$contact_idx]['range'] = $contact['range'];
							
							// Find a matching contact at the org
							$contact_find = implode(' ', $bayes::preprocessWordsPad(array_slice($words, $contact_idx, end($range) - $contact_idx + 1), 4));
							
							$sql = sprintf("SELECT id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND id IN (SELECT id FROM contact WHERE org_id = %d) LIMIT 5",
								$db->qstr($contact_find),
								$db->qstr(CerberusContexts::CONTEXT_CONTACT),
								key($org['value'])
							);
							
							$res = $db->GetArrayReader($sql);
							
							// If we found no matches, the whole contact + org is invalid
							if(empty($res)) {
								unset($entities['contact'][$contact_idx]);
								unset($entities['org'][$from_idx]);
								
								// If we found contacts at that org, inject them
							} else {
								$candidates = array_column($res, 'name', 'id');
								$entities['contact'][$contact_idx]['value'] = $candidates;
								
								unset($entities['org'][$from_idx]);
							}
							
							break 1;
						}
					}
				}
			}
			
			// If we have no orgs left, remove the parent
			if(empty($entities['contact']))
				unset($entities['contact']);
			
			if(empty($entities['org']))
				unset($entities['org']);
		}
		
		// Custom entities
		
		$custom_entities = DAO_ClassifierEntity::getAll();
		
		foreach($custom_entities as $entity) {
			$entity_token = DevblocksPlatform::strLower($entity->name);
			
			if(!in_array($entity_token, $types))
				continue;
			
			switch($entity->type) {
				case 'list':
					self::_tagToEntity($entity_token, $words, $tags, $entities);
					break;
				
				case 'regexp':
					foreach($tags as $idx => $tagset) {
						$entity_value = $tagset['{' . $entity_token . '}'] ?? null;
						
						if($entity_value) {
							if(!isset($entities[$entity_token]))
								$entities[$entity_token] = [];
							
							$entities[$entity_token][$idx] = [
								'range' => [
									$idx => [
										$idx => $entity_value
									],
								],
								'value' => [
									$entity_value => $entity_value
								]
							];
						}
					}
					break;
				
				case 'text':
					$tokens = $words;
					
					// [TODO] Windowing (3 words before and after?)
					
					// [TODO] We don't care about entities here, just tags
					
					foreach($entities as $entity_type => $results) {
						foreach($results as $result) {
							if(isset($result['range']))
								foreach(array_keys($result['range']) as $key) {
									$tokens[$key] = sprintf('[%s]', $entity_type);
								}
						}
					}
					
					$text = implode(' ', $tokens);
					
					// [TODO] These patterns should be learnable
					// [TODO] These can be optimized as a tree
					
					$patterns = $entity->params['patterns'] ?? null;
					
					if(empty($patterns) || !is_array($patterns))
						break;
					
					foreach($patterns as $pattern) {
						$pattern = str_replace('\{' . $entity_token . '\}', '(.*?)', DevblocksPlatform::strToRegExp($pattern, true, false));
						$matches = array();
						
						if(preg_match($pattern, $text, $matches)) {
							$terms = explode(' ', $matches[1]);
							if(false !== ($pos = $bayes::_findSubsetInArray($terms, $words))) {
								if(!isset($entities[$entity_token]))
									$entities[$entity_token] = [];
								
								$remind = [
									'range' => array_combine(range($pos, $pos+count($terms)-1), $terms),
								];
								
								$entities[$entity_token][] = $remind;
								break;
							}
						}
					}
					break;
			}
		}
		
		return $entities;
	}
	
	private static function _sequenceToEntity($sequences, $entity_name, $words, &$tags, &$entities) {
		foreach($sequences as $seq) {
			$seq = explode(' ', $seq);
			
			$left = $seq;
			$found = false;
			
			for($idx = 0; $idx < count($tags); $idx++) {
				$k = $left[0];
				$pass = false;
				
				if('{' == substr($k, 0, 1)) {
					if(isset($tags[$idx][$k]))
						$pass = true;
				} else {
					if(isset($words[$idx]) && $words[$idx] == $k)
						$pass = true;
				}
				
				if($pass) {
					array_shift($left);
					//var_dump([$words[$idx], $k, $left]);
					
					if(false === $found)
						$found = $idx;
				} else {
					$left = $seq;
					$found = false;
				}
				
				if(empty($left)) {
					$left = $seq;
					
					if(!isset($entities[$entity_name]))
						$entities[$entity_name] = [];
					
					$range = array_slice($words, $found, count($seq), true);
					
					$entity = [
						'range' => $range,
						'sequence' => array_combine(array_keys($range), $seq),
					];
					
					// Wipe out our tags on these tokens
					array_splice($tags, $found, count($seq), array_fill(0, count($seq), []));
					
					$entities[$entity_name][] = $entity;
				}
			}
		}
	}
	
	private static function _tagToEntity($context, &$words, &$tags, &$entities) {
		$idx = 0;
		$tag = sprintf('{%s}', $context);
		while($idx < count($tags)) {
			if(isset($tags[$idx][$tag])) {
				$from_idx = $idx;
				$candidates = array_keys($tags[$idx][$tag]);
				
				$to_idx = $idx+1;
				while(isset($tags[$to_idx]) && isset($tags[$to_idx][$tag])) {
					foreach($tags[$to_idx][$tag] as $id => $label) {
						if(!isset($tags[$to_idx]) || !isset($tags[$to_idx][$tag]))
							break 2;
						
						$matches = array_intersect($candidates, array_keys($tags[$to_idx][$tag]));
						
						if(empty($matches)) {
							break 2;
						}
						
						$candidates = $matches;
						$idx++;
						$to_idx++;
					}
				}
				
				$to_idx--;
				
				$range = array_slice($words, $from_idx, $to_idx - $from_idx + 1, true);
				
				if(!empty($candidates)) {
					$entity = [
						'range' => $range,
						'value' => array_combine($candidates, array_fill(0, count($candidates), implode(' ', $range))),
					];
					
					if(!isset($entities[$context]))
						$entities[$context] = [];
					
					$entities[$context][$from_idx] = $entity;
				}
			}
			
			$idx++;
		}
	}
	
	public static function tag($words, $environment=[]) {
		$db = DevblocksPlatform::services()->database();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		if(!is_array($words))
			return [];
		
		$tags = array_fill_keys(array_keys($words), []);
		
		/**
		 * Punctuation
		 */
		
		array_walk($words, function($word, $idx) use (&$tags) {
			if($word == '?') {
				$tags[$idx]['{question}'] = $word;
			} else if($word == "'s") {
				$tags[$idx]['{possessive}'] = $word;
			}
		});
		
		/**
		 * Numbers
		 */
		
		array_walk($words, function($word, $idx) use (&$bayes, &$tags) {
			if(is_numeric($word) || (false !== ($hits = array_intersect([$word], array_merge($bayes::$NUM_ORDINAL, $bayes::$NUM_WORDS))) && $hits)) {
				$tags[$idx]['{number}'] = $word;
				
				if($word >= 1900 && $word <= 2100)
					$tags[$idx]['{year}'] = $word;
			}
		});
		
		/**
		 * Dates
		 */
		
		array_walk($words, function($word, $idx) use (&$tags) {
			if(preg_match('#^\d{1,4}[/-]\d{1,2}[/-]\d{1,4}$#', $word))
				$tags[$idx]['{date}'] = $word;
		});
		
		$hits = array_intersect($words, array_merge($bayes::$MONTHS_SHORT, $bayes::$MONTHS_LONG));
		foreach($hits as $idx => $token)
			$tags[$idx]['{month}'] = $token;
		
		$hits = array_intersect($words, array_merge($bayes::$DAYS_NTH, $bayes::$DAYS_SHORT, $bayes::$DAYS_LONG, $bayes::$DAYS_LONG_PLURAL, $bayes::$DAYS_REL));
		foreach($hits as $idx => $token)
			$tags[$idx]['{day}'] = $token;
		
		$hits = array_intersect($words, ['next', 'previous', 'prev', 'last', 'past', 'prior', 'this']);
		foreach($hits as $idx => $token)
			$tags[$idx]['{unit_rel}'] = $token;
		
		$hits = array_intersect($words, $bayes::$DATE_UNIT);
		foreach($hits as $idx => $token)
			$tags[$idx]['{date_unit}'] = $token;
		
		$hits = array_intersect($words, $bayes::$TIME_UNITS);
		foreach($hits as $idx => $token)
			$tags[$idx]['{time_unit}'] = $token;
		
		/**
		 * Temps
		 */
		
		array_walk($words, function($word, $idx) use (&$tags) {
			if(preg_match('#^\d+[º]*(c|f)*$#', $word))
				$tags[$idx]['{temp}'] = $word;
		});
		
		$hits = array_intersect($words, $bayes::$TEMP_UNITS);
		foreach($hits as $idx => $token)
			$tags[$idx]['{temp_unit}'] = $token;
		
		/**
		 * Times
		 */
		
		$hits = array_intersect($words, $bayes::$TIME_REL);
		foreach($hits as $idx => $token)
			$tags[$idx]['{time_rel}'] = $token;
		
		$hits = array_intersect($words, $bayes::$TIME_MERIDIEM);
		foreach($hits as $idx => $token)
			$tags[$idx]['{time_meridiem}'] = $token;
		
		// Times (5pm, 8a)
		array_walk($words, function(&$token, $idx) use (&$tags) {
			if(preg_match('#^\d+(a|am|p|pm|a.m|p.m){1}$#', $token))
				$tags[$idx]['{time}'] = $token;
		});
		
		// Times (hh:ii:ss)
		array_walk($words, function(&$token, $idx) use (&$tags) {
			if(preg_match('#^\d+\:\d+\s*(a|p|am|pm|a.m|p.m){0,1}$#', $token))
				$tags[$idx]['{time}'] = $token;
		});
		
		/**
		 * Contexts (tasks, tickets, etc)
		 */
		
		$contexts = [];
		$aliases = Extension_DevblocksContext::getAliasesForAllContexts();
		
		foreach($aliases as $alias => $context) {
			if(!isset($contexts[$context]))
				$contexts[$context] = [];
			
			$contexts[$context][] = $alias;
		}
		
		self::_tagEntitySynonyms($contexts, 'context', $words, $tags);
		
		/**
		 * Statuses (open, waiting, closed)
		 */
		
		$statuses = [
			'open' => [
				'active',
				'incomplete',
				'open',
				'unfinished',
				'unresolved',
			],
			'waiting' => [
				'pending',
				'waiting',
				'waiting for reply',
			],
			'closed' => [
				'closed',
				'complete',
				'completed',
				'finished',
				'resolved',
			],
			'deleted' => [
				'deleted'
			],
		];
		
		self::_tagEntitySynonyms($statuses, 'status', $words, $tags);
		
		/**
		 * Entities (worker, contact, org)
		 */
		
		$lookup = implode(' ', $bayes::preprocessWordsPad($words, 4));
		$terms = $db->qstr($lookup);
		
		$sql = implode(' UNION ALL ', [
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 0 LIMIT 10)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_WORKER),
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 1 LIMIT 5)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_WORKER),
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 0 LIMIT 10)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_ORG),
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 1 LIMIT 5)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_ORG),
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 0 LIMIT 10)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_CONTACT),
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 1 LIMIT 5)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_CONTACT),
			),
		]);
		
		$results = $db->GetArrayReader($sql);
		$hits = [];
		
		// Tag me|my|i as the current user if we know who they are
		if(array_intersect($words, ['i','me','my']) && isset($environment['me']) && isset($environment['me']['context'])) {
			switch($environment['me']['context']) {
				case CerberusContexts::CONTEXT_CONTACT:
				case CerberusContexts::CONTEXT_WORKER:
					$results[] = [
						'context' => $environment['me']['context'],
						'id' => $environment['me']['id'],
						'name' => 'me',
					];
					$results[] = [
						'context' => $environment['me']['context'],
						'id' => $environment['me']['id'],
						'name' => 'my',
					];
					$results[] = [
						'context' => $environment['me']['context'],
						'id' => $environment['me']['id'],
						'name' => 'i',
					];
					break;
			}
		}
		
		foreach($results as $idx => $result) {
			$name = $bayes::tokenizeWords($result['name']);
			
			while(!empty($name)) {
				if(false !== ($pos = $bayes::_findSubsetInArray($name, $words))) {
					$hits[$idx] = array_combine(range($pos, $pos + count($name) - 1), $name);
					break;
				}
				array_pop($name);
			}
		}
		
		foreach($hits as $pos => $hit) {
			$result = $results[$pos];
			
			$context_map = [
				CerberusContexts::CONTEXT_CONTACT => 'contact',
				CerberusContexts::CONTEXT_ORG => 'org',
				CerberusContexts::CONTEXT_WORKER => 'worker',
			];
			
			if(!isset($context_map[$result['context']]))
				continue;
			
			$context_idx = '{' . $context_map[$result['context']] . '}';
			
			foreach(array_keys($hit) as $idx) {
				if(!isset($tags[$idx][$context_idx]))
					$tags[$idx][$context_idx] = [];
				
				// Only keep the first match per context:id tuple
				if(isset($tags[$idx][$context_idx][$result['id']]))
					continue;
				
				$tags[$idx][$context_idx][$result['id']] = $result['name'];
			}
		}
		
		self::_disambiguateActorTags($tags);
		
		$custom_entities = DAO_ClassifierEntity::getAll();
		
		foreach($custom_entities as $entity) {
			switch($entity->type) {
				case 'list':
					$label_map = $entity->params['map'] ?? null;
					
					if(empty($label_map) || !is_array($label_map))
						break;
					
					self::_tagEntitySynonyms($label_map, DevblocksPlatform::strLower($entity->name), $words, $tags);
					break;
				
				case 'regexp':
					$pattern = $entity->params['pattern'] ?? null;
					$entity_name = DevblocksPlatform::strLower($entity->name);
					
					if(empty($pattern))
						break;
					
					// [TODO] Currently these patterns can only handle one token
					
					array_walk($words, function(&$token, $idx) use (&$tags, $pattern, $entity_name) {
						if(preg_match($pattern, $token))
							$tags[$idx]['{' . $entity_name . '}'] = $token;
					});
					break;
				
				case 'text':
					// Already tagged
					break;
			}
		}
		
		return $tags;
	}
	
	private static function _tagEntitySynonyms($labels, $tag, $words, &$tags) {
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		$context_idx = sprintf('{%s}', $tag);
		
		foreach($labels as $label => $synonyms) {
			foreach($synonyms as $synonym) {
				$stokens = $bayes::tokenizeWords($synonym);
				
				if(false !== ($pos = $bayes::_findSubsetInArray($stokens, $words))) {
					foreach(array_keys($stokens) as $n) {
						$idx = $pos + $n;
						
						if(!isset($tags[$idx][$context_idx]))
							$tags[$idx][$context_idx] = [];
						
						$tags[$idx][$context_idx][$label] = $label;
					}
				}
			}
		}
	}
	
	private static function _disambiguateActorTags(&$tags) {
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		$idx = 0;
		
		$contexts = [
			'worker' => '{worker}',
			'contact' => '{contact}',
			'org' => '{org}',
		];
		
		while($idx < count($tags)) {
			if(!isset($tags[$idx])) {
				$idx++;
				continue;
			}
			
			$hits = array_intersect($contexts, array_keys($tags[$idx]));
			
			if(empty($hits)) {
				$idx++;
				continue;
			}
			
			$start_idx = $idx;
			$candidates = [];
			
			// Record stats about each candidate so we can compare them
			foreach($hits as $context => $tag) {
				$idx = $start_idx;
				
				if(isset($tags[$idx][$tag]))
					foreach($tags[$idx][$tag] as $candidate_id => $candidate_label) {
						$idx = $start_idx;
						$cand_key = sprintf('%s:%d', $context, $candidate_id);
						$max = count($bayes::tokenizeWords($candidate_label));
						
						$candidates[$cand_key] = [
							'n' => 1,
							'max' => $max,
						];
						
						$n = 1;
						
						while(isset($tags[++$idx])) {
							if(!isset($tags[$idx][$tag][$candidate_id]))
								break;
							
							$n++;
						}
						
						$candidates[$cand_key]['n'] = $n;
						
						$fit = $n / $candidates[$cand_key]['max'];
						$candidates[$cand_key]['fit'] = $fit;
					}
			}
			
			$idx = $start_idx;
			
			// Find the longest match and the highest coverage match
			$longest = max(array_column($candidates, 'n'));
			$best_fit = max(array_column($candidates, 'fit'));
			
			// Reject the least likely candidates
			foreach($candidates as $key => $candidate) {
				if($candidate['n'] < $longest || $candidate['fit'] < $best_fit) {
					list($context, $candidate_id) = explode(':', $key);
					$tag = $contexts[$context];
					
					// Remove rejected candidate chains
					for($x=$idx; $x < $idx + $candidate['max']; $x++) {
						unset($tags[$x][$tag][$candidate_id]);
					}
					
					if(empty($tags[$x][$tag]))
						unset($tags[$x][$tag]);
				}
			}
			
			// Remove other tag starts in the rest of our range
			for($x=$idx+1; $x < $idx + $longest; $x++) {
				if(isset($tags[$x]))
					foreach($tags[$x] as $tag => $members) {
						if(isset($tags[$idx][$tag])) {
							$members = array_intersect($tags[$idx][$tag], $members);
							
						} else {
							if(is_array($members))
								foreach(array_keys($members) as $id) {
									$y = $x;
									
									while(isset($tags[$y][$tag][$id])) {
										unset($tags[$y][$tag][$id]);
										$y++;
									}
								}
						}
						
						if(empty($tags[$x][$tag]))
							unset($tags[$x][$tag]);
					}
			}
			
			$idx += $longest + 1;
		}
	}
	
	static function getNGramsForClass($class_id, $n=0, $oper='>') {
		$db = DevblocksPlatform::services()->database();
		
		// Validate
		switch($oper) {
			case '=':
			case '!=':
			case '>':
			case '>=':
			case '<':
			case '<=':
			case '<>':
				break;
			
			default:
				return false;
		}
		
		//$sql = sprintf("SELECT id, token FROM classifier_ngram WHERE n %s %d AND class_id = %d",
		/** @noinspection SqlDialectInspection */
		$sql = sprintf("SELECT id, token FROM classifier_ngram WHERE id IN (SELECT token_id FROM classifier_ngram_to_class WHERE class_id = %d) AND n %s %d",
			$class_id,
			$oper,
			$n
		);
		$results = $db->GetArrayReader($sql);
		
		return array_column($results, 'token', 'id');
	}
};

class View_Classifier extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'classifiers';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = mb_convert_case($translate->_('common.classifiers'), MB_CASE_TITLE);
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Classifier::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Classifier::NAME,
			SearchFields_Classifier::VIRTUAL_OWNER,
			SearchFields_Classifier::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Classifier::OWNER_CONTEXT,
			SearchFields_Classifier::OWNER_CONTEXT_ID,
			SearchFields_Classifier::PARAMS_JSON,
			SearchFields_Classifier::VIRTUAL_CONTEXT_LINK,
			SearchFields_Classifier::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Classifier::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Classifier');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_Classifier', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Classifier', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
//				case SearchFields_Classifier::EXAMPLE:
//					$pass = true;
//					break;
					
				// Virtuals
				case SearchFields_Classifier::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Classifier::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Classifier::VIRTUAL_OWNER:
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
		$counts = array();
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_CLASSIFIER;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
//			case SearchFields_Classifier::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_Classifier::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
				
			case SearchFields_Classifier::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Classifier::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Classifier::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_Classifier::OWNER_CONTEXT, DAO_Classifier::OWNER_CONTEXT_ID, 'owner_context[]');
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
		$search_fields = SearchFields_Classifier::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Classifier::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Classifier::CREATED_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Classifier::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_CLASSIFIER],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Classifier::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CLASSIFIER, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Classifier::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'score' => 2000,
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:classifier by:name~25 query:(name:{{term}}*) format:dictionaries',
						'key' => 'name',
						'limit' => 25,
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Classifier::UPDATED_AT),
				),
		);
		
		// Add dynamic owner.* fields
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner', SearchFields_Classifier::VIRTUAL_OWNER);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Classifier::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CLASSIFIER, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		$search_fields = $this->getQuickSearchFields();
		
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
			default:
				if($field == 'owner' || substr($field, 0, strlen('owner.')) == 'owner.')
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_Classifier::VIRTUAL_OWNER);
				
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CLASSIFIER);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerb.classifiers::record_types/classifier/view.tpl');
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
			case SearchFields_Classifier::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Classifier::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Classifier::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners');
				break;
		}
	}

	function getFields() {
		return SearchFields_Classifier::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			//case SearchFields_Classifier::OWNER_CONTEXT:
			//case SearchFields_Classifier::OWNER_CONTEXT_ID:
			
			case SearchFields_Classifier::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Classifier::DICTIONARY_SIZE:
			case SearchFields_Classifier::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Classifier::CREATED_AT:
			case SearchFields_Classifier::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Classifier::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Classifier::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Classifier::VIRTUAL_OWNER:
				$owner_contexts = DevblocksPlatform::importGPC($_POST['owner_context'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
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

class Context_Classifier extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete { // IDevblocksContextImport
	const ID = CerberusContexts::CONTEXT_CLASSIFIER;
	const URI = 'classifier';
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_CLASSIFIER, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CLASSIFIER, $models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Classifier::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=classifier&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Classifier();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CLASSIFIER,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			]
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($classifier = DAO_Classifier::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($classifier->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $classifier->id,
			'name' => $classifier->name,
			'permalink' => $url,
			'updated' => $classifier->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'owner__label',
			'created_at',
			'updated_at',
		);
	}
	
	function autocomplete($term, $query=null) {
		$list = [];
		
		list($results,) = DAO_Classifier::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Classifier::NAME,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%'),
			),
			25,
			0,
			SearchFields_Classifier::NAME,
			true,
			false
		);

		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_Classifier::NAME];
			$entry->value = $row[SearchFields_Classifier::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($classifier, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Classifier:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CLASSIFIER);

		// Polymorph
		if(is_numeric($classifier)) {
			$classifier = DAO_Classifier::get($classifier);
		} elseif($classifier instanceof Model_Classifier) {
			// It's what we want already.
		} elseif(is_array($classifier)) {
			$classifier = Cerb_ORMHelper::recastArrayToModel($classifier, 'Model_Classifier');
		} else {
			$classifier = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
				
			'owner__label' => $prefix.$translate->_('common.owner'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
				
			'owner__label' => 'context_url',
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_Classifier::ID;
		$token_values['_type'] = Context_Classifier::URI;
		
		$token_values['_types'] = $token_types;
		
		if($classifier) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $classifier->name;
			$token_values['id'] = $classifier->id;
			$token_values['name'] = $classifier->name;
			$token_values['created_at'] = $classifier->created_at;
			$token_values['updated_at'] = $classifier->updated_at;
			
			$token_values['owner__context'] = $classifier->owner_context;
			$token_values['owner_id'] = $classifier->owner_context_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($classifier, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=classifier&id=%d-%s",$classifier->id, DevblocksPlatform::strToPermalink($classifier->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_Classifier::CREATED_AT,
			'id' => DAO_Classifier::ID,
			'links' => '_links',
			'name' => DAO_Classifier::NAME,
			'owner__context' => DAO_Classifier::OWNER_CONTEXT,
			'owner_id' => DAO_Classifier::OWNER_CONTEXT_ID,
			'updated_at' => DAO_Classifier::UPDATED_AT,
		];
	}
	
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
		
		$context = CerberusContexts::CONTEXT_CLASSIFIER;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
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
		$view->name = 'Classifier';
		$view->renderSortBy = SearchFields_Classifier::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Classifier';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Classifier::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_CLASSIFIER;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_Classifier::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!Context_Classifier::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree();
			$tpl->assign('owners_menu', $owners_menu);
			
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
			$tpl->display('devblocks:cerb.classifiers::record_types/classifier/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};