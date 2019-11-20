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

class DAO_KbArticle extends Cerb_ORMHelper {
	const CONTENT = 'content';
	const FORMAT = 'format';
	const ID = 'id';
	const TITLE = 'title';
	const UPDATED = 'updated';
	const VIEWS = 'views';
	
	const _CATEGORY_IDS = '_category_ids';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// mediumtext
		$validation
			->addField(self::CONTENT)
			->string()
			->setMaxLength(16777215)
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::FORMAT)
			->uint(1)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(128)
		$validation
			->addField(self::TITLE)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::VIEWS)
			->uint(4)
			;
		// text
		$validation
			->addField(self::_CATEGORY_IDS)
			->string() // [TODO] test CSV ID list
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
		
		$sql = "INSERT INTO kb_article () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * @param $id
	 * @return Model_KbArticle|null
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

	static function getWhere($where=null, $sortBy='updated', $sortAsc=false, $limit=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, title, views, updated, format ".
			"FROM kb_article ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_KbArticle[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	static function getContent($id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneSlave(sprintf("SELECT content FROM kb_article WHERE id = %d", $id));
	}
	
	/**
	 *
	 * @param resource $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_KbArticle();
			$object->id = intval($row['id']);
			$object->title = $row['title'];
			$object->updated = $row['updated'];
			$object->views = $row['views'];
			$object->format = $row['format'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED]))
			$fields[self::UPDATED] = time();
		
		$context = CerberusContexts::CONTEXT_KB_ARTICLE;
		self::_updateAbstract($context, $ids, $fields);
		
		if(isset($fields[self::_CATEGORY_IDS])) {
			$category_ids = DevblocksPlatform::parseCsvString($fields[self::_CATEGORY_IDS]);
			unset($fields[self::_CATEGORY_IDS]);
			
			DAO_KbArticle::setCategories($ids, $category_ids);
		}
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_KB_ARTICLE, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'kb_article', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.kb_article.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_KB_ARTICLE, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('kb_article', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_KB_ARTICLE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		$custom_fields = [];

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		if(!empty($change_fields))
			DAO_KbArticle::update($ids, $change_fields);
		
		// Category deltas
		if(isset($do['category_delta']))
			DAO_KbArticle::setCategories($ids, $do['category_delta'], false);
		
		// Custom Fields
		C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_KB_ARTICLE, $custom_fields, $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_KB_ARTICLE, $do['behavior'], $ids);
		
		$update->markCompleted();
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::services()->database();
		
		$id_string = implode(',', $ids);
		
		// Articles
		$db->ExecuteMaster(sprintf("DELETE FROM kb_article WHERE id IN (%s)", $id_string));
		
		// Categories
		$db->ExecuteMaster(sprintf("DELETE FROM kb_article_to_category WHERE kb_article_id IN (%s)", $id_string));
		
		// Search indexes
		$search = Extension_DevblocksSearchSchema::get(Search_KbArticle::ID, true);
		$search->delete($ids);
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_KB_ARTICLE,
					'context_ids' => $ids
				)
			)
		);
	}
	
	static function maint() {
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_KB_ARTICLE,
					'context_table' => 'kb_article',
					'context_key' => 'id',
				)
			)
		);
	}

	static function getCategoriesByArticleId($article_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($article_id))
			return [];
		
		$categories = [];
		
		$rs = $db->ExecuteSlave(sprintf("SELECT kb_category_id ".
			"FROM kb_article_to_category ".
			"WHERE kb_article_id = %d",
			$article_id
		));
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$cat_id = intval($row['kb_category_id']);
			$categories[$cat_id] = $cat_id;
		}
		
		mysqli_free_result($rs);
		
		return $categories;
	}
	
	static function getTopArticlesForCategories(array $category_ids, $limit=5) {
		$db = DevblocksPlatform::services()->database();
		
		$tree = DAO_KbCategory::getTree(0);
		
		$articles_by_category = [];
		
		// [TODO] Cache by category?
		
		if(is_array($category_ids))
		foreach($category_ids as $category_id) {
			$sql = sprintf("SELECT DISTINCT a.id, a.title, a.views, a.updated, %d as parent_id FROM kb_article a INNER JOIN kb_article_to_category kbc ON (kbc.kb_article_id=a.id) WHERE kbc.kb_category_id IN (%s) ORDER BY a.views DESC LIMIT %d",
				$category_id,
				implode(',', DAO_KbCategory::getDescendents($category_id, $tree)),
				$limit
			);
			$results = $db->GetArraySlave($sql);
			
			if(is_array($results))
			foreach($results as $result) {
				if(!isset($articles_by_category[$result['parent_id']]))
					$articles_by_category[$result['parent_id']] = [];
				
				$articles_by_category[$result['parent_id']][] = $result;
			}
		}
		
		return $articles_by_category;
	}
	
	static function setCategories($article_ids,$category_ids,$replace=true) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($article_ids))
			$article_ids = array($article_ids);

		if(!is_array($category_ids))
			$category_ids = array($category_ids);
		
		if($replace) {
			$db->ExecuteMaster(sprintf("DELETE FROM kb_article_to_category WHERE kb_article_id IN (%s)",
				implode(',', $article_ids)
			));
		}
		
		$categories = DAO_KbCategory::getAll();
		
		if(is_array($category_ids) && !empty($category_ids)) {
			foreach($category_ids as $category_id) {
				$is_add = '-'==substr($category_id, 0, 1) ? false : true;
				$category_id = ltrim($category_id,'+-');
				
				// Add
				if($is_add) {
					// Valid?
					if(!isset($categories[$category_id]))
						continue;
					
					$pid = $category_id;
					while($pid) {
						$top_category_id = $pid;
						$pid = $categories[$pid]->parent_id;
					}
					
					if(is_array($article_ids))
					foreach($article_ids as $article_id) {
						$db->ExecuteMaster(sprintf("REPLACE INTO kb_article_to_category (kb_article_id, kb_category_id, kb_top_category_id) ".
							"VALUES (%d, %d, %d)",
							$article_id,
							$category_id,
							$top_category_id
						));
					}
					
				// Delete
				} else {
					if(is_array($article_ids))
					foreach($article_ids as $article_id) {
						$db->ExecuteMaster(
							sprintf("DELETE FROM kb_article_to_category WHERE kb_article_id = %d AND kb_category_id = %d",
								$article_id,
								$category_id
							)
						);
					}
				}
			}
		}
		
		return TRUE;
	}
	
	public static function random() {
		return self::_getRandom('kb_article');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_KbArticle::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_KbArticle', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"kb.id as %s, ".
			"kb.title as %s, ".
			"kb.updated as %s, ".
			"kb.views as %s, ".
			"kb.format as %s, ".
			"kb.content as %s ",
				SearchFields_KbArticle::ID,
				SearchFields_KbArticle::TITLE,
				SearchFields_KbArticle::UPDATED,
				SearchFields_KbArticle::VIEWS,
				SearchFields_KbArticle::FORMAT,
				SearchFields_KbArticle::CONTENT
			);
			
		$join_sql = "FROM kb_article kb ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_KbArticle');

		$result = array(
			'primary_table' => 'kb',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function countByCategoryId($category_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(kb_article_id) FROM kb_article_to_category WHERE kb_category_id = %d",
			$category_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
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
			$sort_sql
			;
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_KbArticle::ID]);
			$results[$id] = $row;
		}
		
		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(kb.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_KbArticle extends DevblocksSearchFields {
	// Table
	const ID = 'kb_id';
	const TITLE = 'kb_title';
	const UPDATED = 'kb_updated';
	const VIEWS = 'kb_views';
	const FORMAT = 'kb_format';
	const CONTENT = 'kb_content';
	
	const CATEGORY_ID = 'katc_category_id';
	const TOP_CATEGORY_ID = 'katc_top_category_id';
	
	const FULLTEXT_ARTICLE_CONTENT = 'ftkb_content';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'kb.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_KB_ARTICLE => new DevblocksSearchFieldContextKeys('kb.id', self::ID),
			CerberusContexts::CONTEXT_KB_CATEGORY => new DevblocksSearchFieldContextKeys('katc.kb_category_id', self::CATEGORY_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::CATEGORY_ID:
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_LT:
					case DevblocksSearchCriteria::OPER_LTE:
					case DevblocksSearchCriteria::OPER_GT:
					case DevblocksSearchCriteria::OPER_GTE:
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_NEQ:
						$value = is_array($param->value) ? current($param->value) : $param->value;
						
						return sprintf("EXISTS (SELECT * FROM kb_article_to_category WHERE kb_article_to_category.kb_article_id=%s AND kb_article_to_category.kb_category_id %s %d)",
							self::getPrimaryKey(),
							Cerb_ORMHelper::escape($param->operator),
							$value
						);
						break;
						
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_NIN:
						$values = !is_array($param->value) ? [$param->value] : $param->value;
						$category_ids = DevblocksPlatform::sanitizeArray($values, 'integer');
						
						return sprintf("EXISTS (SELECT * FROM kb_article_to_category WHERE kb_article_to_category.kb_article_id=%s AND kb_article_to_category.kb_category_id %s (%s))",
							self::getPrimaryKey(),
							$param->operator == DevblocksSearchCriteria::OPER_NIN ? 'NOT IN' : 'IN',
							implode(',', $category_ids)
						);
						break;
				}
				return 0;
				break;
				
			case self::TOP_CATEGORY_ID:
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_LT:
					case DevblocksSearchCriteria::OPER_LTE:
					case DevblocksSearchCriteria::OPER_GT:
					case DevblocksSearchCriteria::OPER_GTE:
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_NEQ:
						$value = is_array($param->value) ? current($param->value) : $param->value;
						
						return sprintf("EXISTS (SELECT * FROM kb_article_to_category WHERE kb_article_to_category.kb_article_id=%s AND kb_article_to_category.kb_top_category_id %s %d)",
							self::getPrimaryKey(),
							Cerb_ORMHelper::escape($param->operator),
							$value
						);
						break;
						
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_NIN:
						$values = !is_array($param->value) ? [$param->value] : $param->value;
						$category_ids = DevblocksPlatform::sanitizeArray($values, 'integer');
						
						return sprintf("EXISTS (SELECT * FROM kb_article_to_category WHERE kb_article_to_category.kb_article_id=%s AND kb_article_to_category.kb_top_category_id %s (%s))",
							self::getPrimaryKey(),
							$param->operator == DevblocksSearchCriteria::OPER_NIN ? 'NOT IN' : 'IN',
							implode(',', $category_ids)
						);
						break;
				}
				return 0;
				break;
				
			case self::FULLTEXT_ARTICLE_CONTENT:
				return self::_getWhereSQLFromFulltextField($param, Search_KbArticle::ID, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_KB_ARTICLE, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_KB_ARTICLE)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_KB_ARTICLE, self::getPrimaryKey());
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
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		// [TODO] Category
		switch($key) {
			case 'category':
			case 'category.id':
			case 'topic.id':
				$key = null;
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_KbArticle::CATEGORY_ID:
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				$models = DAO_KbCategory::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_KbArticle::ID:
				$models = DAO_KbArticle::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'title', 'id');
				break;
				
			case SearchFields_KbArticle::FORMAT:
				$label_map = [
					'0' => 'Plaintext',
					'1' => 'HTML',
					'2' => 'Markdown',
				];
				return $label_map;
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
			self::ID => new DevblocksSearchField(self::ID, 'kb', 'id', $translate->_('kb_article.id'), null, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'kb', 'title', $translate->_('kb_article.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'kb', 'updated', $translate->_('kb_article.updated'), Model_CustomField::TYPE_DATE, true),
			self::VIEWS => new DevblocksSearchField(self::VIEWS, 'kb', 'views', $translate->_('kb_article.views'), Model_CustomField::TYPE_NUMBER, true),
			self::FORMAT => new DevblocksSearchField(self::FORMAT, 'kb', 'format', $translate->_('kb_article.format'), null, true),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'kb', 'content', $translate->_('kb_article.content'), null, true),
			
			self::CATEGORY_ID => new DevblocksSearchField(self::CATEGORY_ID, 'katc', 'kb_category_id', DevblocksPlatform::translateCapitalized('common.category'), Model_CustomField::TYPE_NUMBER, true),
			self::TOP_CATEGORY_ID => new DevblocksSearchField(self::TOP_CATEGORY_ID, 'katc', 'kb_top_category_id', $translate->_('kb_article.topic'), null, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			
			self::FULLTEXT_ARTICLE_CONTENT => new DevblocksSearchField(self::FULLTEXT_ARTICLE_CONTENT, 'ftkb', 'content', $translate->_('kb_article.content'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_ARTICLE_CONTENT]->ft_schema = Search_KbArticle::ID;

		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_KbArticle extends Extension_DevblocksSearchSchema {
	const ID = 'cerberusweb.search.schema.kb_article';
	
	public function getNamespace() {
		return 'kb_article';
	}
	
	public function getAttributes() {
		return [];
	}
	
	public function getFields() {
		return array(
			'content',
		);
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the index has a delta, start from the current record
		if($meta['is_indexed_externally']) {
			// Do nothing (let the remote tool update the DB)
			
		// Otherwise, start over
		} else {
			$this->setIndexPointer(self::INDEX_POINTER_RESET);
		}
	}

	public function setIndexPointer($pointer) {
		switch($pointer) {
			case self::INDEX_POINTER_RESET:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', 0);
				break;
				
			case self::INDEX_POINTER_CURRENT:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', time());
				break;
		}
	}
	
	public function query($query, $attributes=[], $limit=null) {
		if(false == ($engine = $this->getEngine()))
			return false;

		$ids = $engine->query($this, $query, $attributes, $limit);
		
		return $ids;
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::services()->log();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%1$s = %2$d AND %3$s > %4$d) OR (%1$s > %2$d)',
				DAO_KbArticle::UPDATED,
				$ptr_time,
				DAO_KbArticle::ID,
				$id
			);
			$articles = DAO_KbArticle::getWhere($where, array(DAO_KbArticle::UPDATED, DAO_KbArticle::ID), array(true, true), 100);

			if(empty($articles)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			foreach($articles as $article) { /* @var $article Model_KbArticle */
				$id = $article->id;
				$ptr_time = $article->updated;

				// If we're not inside a block of the same timestamp, reset the seek pointer
				$ptr_id = ($last_time == $ptr_time) ? $id : 0;

				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));
				
				$doc = array(
					'content' => implode("\n", array(
						$article->title,
						strip_tags($article->getContent())
					)),
				);
				
				if(false === ($engine->index($this, $id, $doc)))
					return false;
			}
		}
		
		// If we ran out of articles, always reset the ID and use the current time
		if($done) {
			$ptr_id = 0;
			$ptr_time = time();
		}
		
		$this->setParam('last_indexed_id', $ptr_id);
		$this->setParam('last_indexed_time', $ptr_time);
	}
	
	public function delete($ids) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		return $engine->delete($this, $ids);
	}
};

class Model_KbArticle {
	const FORMAT_PLAINTEXT = 0;
	const FORMAT_HTML = 1;
	const FORMAT_MARKDOWN = 2;
	
	private $_content = null;
	public $format = 0;
	public $id = 0;
	public $title = '';
	public $updated = 0;
	public $views = 0;
	
	function getContent($sanitized=true) {
		$html = '';
		
		switch($this->format) {
			case self::FORMAT_HTML:
				$tpl_builder = _DevblocksTemplateBuilder::newInstance();
				$html = $tpl_builder->build($this->content, []);
				break;
				
			case self::FORMAT_PLAINTEXT:
				$html = nl2br(DevblocksPlatform::strEscapeHtml($this->content));
				break;
				
			case self::FORMAT_MARKDOWN:
				$tpl_builder = _DevblocksTemplateBuilder::newInstance();
				$html = DevblocksPlatform::parseMarkdown($tpl_builder->build($this->content, []));
				break;
		}
		
		if($sanitized)
			$html = DevblocksPlatform::purifyHTML($html, true, true);
		
		return $html;
	}
	
	// Lazy load content
	function __get($name) {
		switch($name) {
			case 'content':
				if(is_null($this->_content)) {
					$this->_content = DAO_KbArticle::getContent($this->id);
				}
				
				return $this->_content;
				break;
		}
		
		return null;
	}
	
	function getCustomFields() {
		return array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $this->id));
	}
	
	// [TODO] Reuse this!
	function getCategories() {
		$categories = DAO_KbCategory::getAll();
		$cats = DAO_KbArticle::getCategoriesByArticleId($this->id);

		$trails = [];
		
		if(is_array($cats))
		foreach($cats as $cat_id) {
			$pid = $cat_id;
			$trail = [];
			while($pid) {
				array_unshift($trail,$pid);
				$pid = $categories[$pid]->parent_id;
			}
			
			$trails[] = $trail;
		}
		
		// Remove redundant trails
		if(is_array($trails))
		foreach($trails as $idx => $trail) {
			foreach($trails as $c_idx => $compare_trail) {
				if($idx != $c_idx && count($compare_trail) >= count($trail)) {
					if(array_slice($compare_trail,0,count($trail))==$trail) {
						unset($trails[$idx]);
					}
				}
			}
		}
		
		$breadcrumbs = [];
		
		if(is_array($trails))
		foreach($trails as $idx => $trail) {
			$last_step = end($trail);
			reset($trail);
			
			foreach($trail as $step) {
				if(!isset($breadcrumbs[$last_step]))
					$breadcrumbs[$last_step] = [];
					
				$breadcrumbs[$last_step][$step] = $categories[$step];
			}
		}
		
		unset($trails);
		
		return $breadcrumbs;
	}
};

class Context_KbArticle extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.kb_article';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read kb articles
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can edit kb articles
		return CerberusContexts::allowEverything($models);
	}
	
	function getRandom() {
		return DAO_KbArticle::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=kb&id=%d", $context_id, true));
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_KbArticle();
		
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
			'value' => $model->updated,
		);
			
		$properties['views'] = array(
			'label' => mb_ucfirst($translate->_('kb_article.views')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->views,
		);
			
		$properties['id'] = array(
			'label' => mb_ucfirst($translate->_('common.id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$article = DAO_KbArticle::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($article->title);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $article->id,
			'name' => $article->title,
			'permalink' => $url,
			'updated' => $article->updated,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'views',
			'updated',
		);
	}
	
	function getContext($article, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Article:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_KB_ARTICLE);
		
		// Polymorph
		if(is_numeric($article)) {
			$article = DAO_KbArticle::get($article);
		} elseif($article instanceof Model_KbArticle) {
			// It's what we want already.
		} elseif(is_array($article)) {
			$article = Cerb_ORMHelper::recastArrayToModel($article, 'Model_KbArticle');
		} else {
			$article = null;
		}
		/* @var $article Model_KbArticle */
			
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'content' => $prefix.$translate->_('kb_article.content'),
			'format' => $prefix.$translate->_('common.format'),
			'id' => $prefix.$translate->_('common.id'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'title' => $prefix.$translate->_('kb_article.title'),
			'updated' => $prefix.$translate->_('kb_article.updated'),
			'views' => $prefix.$translate->_('kb_article.views'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'content' => null,
			'format' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'record_url' => Model_CustomField::TYPE_URL,
			'title' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
			'views' => Model_CustomField::TYPE_NUMBER,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_KB_ARTICLE;
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $article) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $article->title;
			$token_values['content'] = $article->getContent();
			$token_values['format'] = $article->format;
			$token_values['id'] = $article->id;
			$token_values['title'] = $article->title;
			$token_values['updated'] = $article->updated;
			$token_values['views'] = $article->views;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($article, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=kb&id=%d-%s",$article->id, DevblocksPlatform::strToPermalink($article->title)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'categories' => DAO_KbArticle::_CATEGORY_IDS,
			'content' => DAO_KbArticle::CONTENT,
			'format' => DAO_KbArticle::FORMAT,
			'id' => DAO_KbArticle::ID,
			'links' => '_links',
			'title' => DAO_KbArticle::TITLE,
			'updated' => DAO_KbArticle::UPDATED,
			'views' => DAO_KbArticle::VIEWS,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['format'] = [
			'is_immutable' => false,
			'is_required' => false,
			'notes' => '`text`, `markdown`, or `html`',
			'type' => 'string',
		];
		
		$keys['categories']['notes'] = "A comma-separated list of IDs of [categories](/docs/records/types/kb_category/) to assign this article to";
		$keys['content']['notes'] = "The content of the article";
		$keys['title']['notes'] = "The title of the article";
		$keys['views']['notes'] = "The number of times the article has been viewed in a [community portal](/docs/portals/)";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'format':
				$formats_to_ids = [
					'p' => 0,
					't' => 0,
					'h' => 1,
					'm' => 2,
				];
				
				$format_label = DevblocksPlatform::strLower(mb_substr($value,0,1));
				@$format_id = $formats_to_ids[$format_label];
				
				if(is_null($format_id)) {
					$error = 'Format must be: text, markdown, or html.';
					return false;
				}
				
				$out_fields[DAO_KbArticle::FORMAT] = $format_id;
				break;
				
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['categories'] = [
			'label' => 'Categories',
			'type' => 'HashMap',
		];
		
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_KB_ARTICLE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'categories':
				// Categories
				if(null != ($article = DAO_KbArticle::get($context_id))
					&& null != ($categories = $article->getCategories()) 
					&& is_array($categories)
					) {
					$values['categories'] = [];
					
					foreach($categories as $category_id => $trail) {
						foreach($trail as $step_id => $step) {
							if(!isset($values['categories'][$category_id]))
								$values['categories'][$category_id] = [];
							$values['categories'][$category_id][$step_id] = $step->name;
						}
					}
				}
				break;
				
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
		$view->addParams([], true);
		$view->renderSortBy = SearchFields_KbArticle::UPDATED;
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
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_KB_ARTICLE;
		$model = null;
		
		if(!empty($context_id)) {
			$model = DAO_KbArticle::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(isset($model))
				$tpl->assign('model', $model);
			
			$article_categories = DAO_KbArticle::getCategoriesByArticleId($context_id);
			$tpl->assign('article_categories', $article_categories);
			
			// Categories
			$categories = DAO_KbCategory::getAll();
			$tpl->assign('categories', $categories);
			
			$levels = DAO_KbCategory::getTree(0); //$root_id
			$tpl->assign('levels',$levels);
			
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
			$tpl->display('devblocks:cerberusweb.kb::kb/article/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};

class View_KbArticle extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'kb_overview';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.articles');
		$this->renderSortBy = 'kb_updated';
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_KbArticle::TITLE,
			SearchFields_KbArticle::UPDATED,
			SearchFields_KbArticle::VIEWS,
		);
		$this->addColumnsHidden(array(
			SearchFields_KbArticle::CATEGORY_ID,
			SearchFields_KbArticle::CONTENT,
			SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,
			SearchFields_KbArticle::TOP_CATEGORY_ID,
			SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK,
			SearchFields_KbArticle::VIRTUAL_HAS_FIELDSET,
			SearchFields_KbArticle::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_KbArticle::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_KbArticle');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_KbArticle', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_KbArticle', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_KbArticle::CATEGORY_ID:
				case SearchFields_KbArticle::FORMAT:
				case SearchFields_KbArticle::TOP_CATEGORY_ID:
					$pass = true;
					break;
					
				case SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK:
				case SearchFields_KbArticle::VIRTUAL_HAS_FIELDSET:
				case SearchFields_KbArticle::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_KB_ARTICLE;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_KbArticle::CATEGORY_ID:
				return $this->_getSubtotalCountForCategory();
				break;
				
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				return $this->_getSubtotalCountForCategoryTopic();
				break;
				
			case SearchFields_KbArticle::FORMAT:
				$label_map = function(array $values) use ($column) {
					return SearchFields_KbArticle::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, '=', 'value');
				break;

			case SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_KbArticle::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_KbArticle::VIRTUAL_WATCHERS:
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
	
	protected function _getSubtotalDataForCategory() {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(!method_exists('DAO_KbArticle','getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			['DAO_KbArticle', 'getSearchQueryComponents'],
			[
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			]
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = sprintf("SELECT COUNT(*) AS hits, kb_article_to_category.kb_category_id ".
			"FROM kb_article_to_category ".
			"WHERE kb_article_to_category.kb_article_id IN (".
				"SELECT id ".
				"%s ".
				"%s".
			") ".
			"GROUP BY kb_article_to_category.kb_category_id",
			$join_sql,
			$where_sql
		);
		
		$results = $db->GetArraySlave($sql);
		
		return $results;
	}
	
	protected function _getSubtotalCountForCategory() {
		$counts = [];
		$results = $this->_getSubtotalDataForCategory();

		$categories = DAO_KbCategory::getAll();
		
		if(is_array($results))
		foreach($results as $result) {
			if(empty($result['hits']))
				continue;
			
			if(!array_key_exists($result['kb_category_id'], $categories))
				continue;
			
			$label = $categories[$result['kb_category_id']]->name;
			$value = $result['kb_category_id'];
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $result['hits'],
					'label' => $label,
					'filter' =>
						array(
							'field' => SearchFields_KbArticle::CATEGORY_ID,
							'oper' => DevblocksSearchCriteria::OPER_EQ,
							'values' => array('options[]' => $value),
						),
					'children' => []
				);
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForCategoryTopic() {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(!method_exists('DAO_KbArticle','getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			['DAO_KbArticle', 'getSearchQueryComponents'],
			[
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			]
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = sprintf("SELECT COUNT(*) AS hits, kb_article_to_category.kb_top_category_id ".
			"FROM kb_article_to_category ".
			"WHERE kb_article_to_category.kb_article_id IN (".
				"SELECT id ".
				"%s ".
				"%s".
			") ".
			"GROUP BY kb_article_to_category.kb_top_category_id",
			$join_sql,
			$where_sql
		);
		
		$results = $db->GetArraySlave($sql);
		
		return $results;
	}
	
	protected function _getSubtotalCountForCategoryTopic() {
		$counts = [];
		$results = $this->_getSubtotalDataForCategoryTopic();

		$categories = DAO_KbCategory::getAll();
		
		if(is_array($results))
		foreach($results as $result) {
			if(empty($result['hits']))
				continue;
			
			if(!array_key_exists($result['kb_top_category_id'], $categories))
				continue;
			
			$label = $categories[$result['kb_top_category_id']]->name;
			$value = $result['kb_top_category_id'];
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $result['hits'],
					'label' => $label,
					'filter' =>
						array(
							'field' => SearchFields_KbArticle::TOP_CATEGORY_ID,
							'oper' => DevblocksSearchCriteria::OPER_EQ,
							'values' => array('options[]' => $value),
						),
					'children' => []
				);
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_KbArticle::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT),
				),
			'category.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_KbArticle::CATEGORY_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_KB_CATEGORY, 'q' => ''],
					]
				),
			'content' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_KbArticle::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_KB_ARTICLE],
					]
				),
			'format' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_KbArticle::FORMAT),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_KbArticle::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_KB_ARTICLE, 'q' => ''],
					]
				),
			'title' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_KbArticle::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_KbArticle::UPDATED),
				),
			'views' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_KbArticle::VIEWS),
				),
			'watchers' => 
				array(
					'type' => 'WS',
					'options' => array('param_key' => SearchFields_KbArticle::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_KB_ARTICLE, $fields, null);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_KbArticle::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
			$fields['content']['examples'] = $ft_examples;
		}
		
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

		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);

		switch($this->renderTemplate) {
			case 'chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.kb::kb/article/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_KbArticle::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_KbArticle::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;
		
		switch($field) {
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
			case SearchFields_KbArticle::CATEGORY_ID:
				$label_map = SearchFields_KbArticle::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_KbArticle::FORMAT:
				$label_map = SearchFields_KbArticle::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_KbArticle::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_KbArticle::TITLE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_KbArticle::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_KbArticle::FORMAT:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_KbArticle::VIEWS:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_KbArticle::CATEGORY_ID:
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'], 'array', []);
				$criteria = new DevblocksSearchCriteria($field, $oper, $options);
				break;
				
			case SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field, $oper, array($value,$scope));
				break;
				
			case SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_KbArticle::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_KbArticle::VIRTUAL_WATCHERS:
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
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};