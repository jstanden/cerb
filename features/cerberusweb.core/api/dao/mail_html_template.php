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

class DAO_MailHtmlTemplate extends Cerb_ORMHelper {
	const CONTENT = 'content';
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const SIGNATURE = 'signature';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'mail_html_templates_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// mediumtext
		$validation
			->addField(self::CONTENT)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// varchar(128)
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		// int(11)
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		// mediumtext
		$validation
			->addField(self::SIGNATURE)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
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
		
		$sql = "INSERT INTO mail_html_template () VALUES ()";
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
		
		$context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'mail_html_template', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.mail_html_template.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('mail_html_template', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE;
		
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
	 * @return Model_MailHtmlTemplate[]
	 */
	static function getWhere($where=null, $sortBy=DAO_MailHtmlTemplate::NAME, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, updated_at, owner_context, owner_context_id, content, signature ".
			"FROM mail_html_template ".
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
	 * @param integer $id
	 * @return Model_MailHtmlTemplate
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = DAO_MailHtmlTemplate::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_MailHtmlTemplate[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($html_templates = $cache->load(self::_CACHE_ALL))) {
			$html_templates = self::getWhere(
				null,
				DAO_MailHtmlTemplate::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($html_templates))
				return false;
			
			$cache->save($html_templates, self::_CACHE_ALL);
		}
		
		return $html_templates;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_MailHtmlTemplate[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_MailHtmlTemplate[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_MailHtmlTemplate();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->updated_at = $row['updated_at'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->content = $row['content'];
			$object->signature = $row['signature'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('mail_html_template');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM mail_html_template WHERE id IN (%s)", $ids_list));
		
		// Clear the template setting if used on a group or bucket

		$db->ExecuteMaster(sprintf("UPDATE worker_group SET reply_html_template_id=0 WHERE reply_html_template_id IN (%s)", $ids_list));
		DAO_Address::clearCache();

		$db->ExecuteMaster(sprintf("UPDATE bucket SET reply_html_template_id=0 WHERE reply_html_template_id IN (%s)", $ids_list));
		DAO_Bucket::clearCache();
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_MailHtmlTemplate::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_MailHtmlTemplate', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"mail_html_template.id as %s, ".
			"mail_html_template.name as %s, ".
			"mail_html_template.updated_at as %s, ".
			"mail_html_template.owner_context as %s, ".
			"mail_html_template.owner_context_id as %s, ".
			"mail_html_template.content as %s, ".
			"mail_html_template.signature as %s ",
				SearchFields_MailHtmlTemplate::ID,
				SearchFields_MailHtmlTemplate::NAME,
				SearchFields_MailHtmlTemplate::UPDATED_AT,
				SearchFields_MailHtmlTemplate::OWNER_CONTEXT,
				SearchFields_MailHtmlTemplate::OWNER_CONTEXT_ID,
				SearchFields_MailHtmlTemplate::CONTENT,
				SearchFields_MailHtmlTemplate::SIGNATURE
			);
			
		$join_sql = "FROM mail_html_template ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_MailHtmlTemplate');
	
		return array(
			'primary_table' => 'mail_html_template',
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
		
		$results = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_MailHtmlTemplate::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(mail_html_template.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}

};

class SearchFields_MailHtmlTemplate extends DevblocksSearchFields {
	const CONTENT = 'm_content';
	const ID = 'm_id';
	const NAME = 'm_name';
	const OWNER_CONTEXT = 'm_owner_context';
	const OWNER_CONTEXT_ID = 'm_owner_context_id';
	const SIGNATURE = 'm_signature';
	const UPDATED_AT = 'm_updated_at';

	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	
	// [TODO] Virtual owner
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'mail_html_template.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE => new DevblocksSearchFieldContextKeys('mail_html_template.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE)), self::getPrimaryKey());
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
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_MailHtmlTemplate::ID:
				$models = DAO_MailHtmlTemplate::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'mail_html_template', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'mail_html_template', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'mail_html_template', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'mail_html_template', 'owner_context', null, null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'mail_html_template', 'owner_context_id', null, null, true),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'mail_html_template', 'content', $translate->_('common.content'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::SIGNATURE => new DevblocksSearchField(self::SIGNATURE, 'mail_html_template', 'signature', $translate->_('common.signature'), Model_CustomField::TYPE_MULTI_LINE, true),

			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_MailHtmlTemplate {
	public $content;
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	public $signature;
	public $updated_at;
	
	function getSignature($worker=null) {
		$signature = $this->signature;
		
		if(!empty($worker)) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance();
			
			$dict = new DevblocksDictionaryDelegate([
				'_context' => CerberusContexts::CONTEXT_WORKER,
				'id' => $worker->id,
			]);
			
			$signature = $tpl_builder->build($signature, $dict);
		}
		
		return $signature;
	}
	
	function getAttachments() {
		return DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $this->id);
	}
};

class View_MailHtmlTemplate extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'mail_html_template';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.email_templates');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_MailHtmlTemplate::UPDATED_AT;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_MailHtmlTemplate::NAME,
			SearchFields_MailHtmlTemplate::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_MailHtmlTemplate::FULLTEXT_COMMENT_CONTENT,
			SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK,
			SearchFields_MailHtmlTemplate::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_MailHtmlTemplate::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_MailHtmlTemplate');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_MailHtmlTemplate', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_MailHtmlTemplate', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Virtuals
				case SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK:
				case SearchFields_MailHtmlTemplate::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_MailHtmlTemplate::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
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
		$search_fields = SearchFields_MailHtmlTemplate::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::FULLTEXT_COMMENT_CONTENT),
				),
			'content' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::CONTENT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'signature' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::SIGNATURE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_MailHtmlTemplate::UPDATED_AT),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $fields, null);
		
		// Engine/schema examples: Comments
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['comments']['examples'] = $ft_examples;
		
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

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/mail_html_template/view.tpl');
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
			case SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_MailHtmlTemplate::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_MailHtmlTemplate::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_MailHtmlTemplate::NAME:
			case SearchFields_MailHtmlTemplate::OWNER_CONTEXT:
			case SearchFields_MailHtmlTemplate::CONTENT:
			case SearchFields_MailHtmlTemplate::SIGNATURE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_MailHtmlTemplate::ID:
			case SearchFields_MailHtmlTemplate::OWNER_CONTEXT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_MailHtmlTemplate::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_MailHtmlTemplate::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_POST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_MailHtmlTemplate::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
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

class Context_MailHtmlTemplate extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = 'cerberusweb.contexts.mail.html_template';
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_MailHtmlTemplate::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=html_template&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_MailHtmlTemplate();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$mail_html_template = DAO_MailHtmlTemplate::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($mail_html_template->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $mail_html_template->id,
			'name' => $mail_html_template->name,
			'permalink' => $url,
			'updated' => $mail_html_template->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'owner__label',
			'updated_at',
		);
	}
	
	function autocomplete($term, $query=null) {
		$list = [];
		
		list($results,) = DAO_MailHtmlTemplate::search(
			[],
			[
				new DevblocksSearchCriteria(SearchFields_MailHtmlTemplate::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
			],
			25,
			0,
			DAO_MailHtmlTemplate::NAME,
			true,
			false
		);

		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_MailHtmlTemplate::NAME];
			$entry->value = $row[SearchFields_MailHtmlTemplate::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($mail_html_template, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'HTML Template:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE);

		// Polymorph
		if(is_numeric($mail_html_template)) {
			$mail_html_template = DAO_MailHtmlTemplate::get($mail_html_template);
		} elseif($mail_html_template instanceof Model_MailHtmlTemplate) {
			// It's what we want already.
		} elseif(is_array($mail_html_template)) {
			$mail_html_template = Cerb_ORMHelper::recastArrayToModel($mail_html_template, 'Model_MailHtmlTemplate');
		} else {
			$mail_html_template = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'content' => $prefix.$translate->_('common.content'),
			'name' => $prefix.$translate->_('common.name'),
			'signature' => $prefix.$translate->_('common.signature'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'content' => Model_CustomField::TYPE_SINGLE_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'signature' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE;
		$token_values['_types'] = $token_types;
		
		if($mail_html_template) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $mail_html_template->name;
			$token_values['content'] = $mail_html_template->content;
			$token_values['id'] = $mail_html_template->id;
			$token_values['name'] = $mail_html_template->name;
			$token_values['signature'] = $mail_html_template->signature;
			$token_values['updated_at'] = $mail_html_template->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($mail_html_template, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=html_template&id=%d-%s",$mail_html_template->id, DevblocksPlatform::strToPermalink($mail_html_template->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'content' => DAO_MailHtmlTemplate::CONTENT,
			'id' => DAO_MailHtmlTemplate::ID,
			'links' => '_links',
			'name' => DAO_MailHtmlTemplate::NAME,
			'owner__context' => DAO_MailHtmlTemplate::OWNER_CONTEXT,
			'owner_id' => DAO_MailHtmlTemplate::OWNER_CONTEXT_ID,
			'signature' => DAO_MailHtmlTemplate::SIGNATURE,
			'updated_at' => DAO_MailHtmlTemplate::UPDATED_AT,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['content']['notes'] = "The content of the template";
		$keys['signature']['notes'] = "A template-specific email signature [template](/docs/bots/scripting/)";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
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
		
		$context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
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
		$view->name = DevblocksPlatform::translateCapitalized('common.email_templates');
		$view->renderSortBy = SearchFields_MailHtmlTemplate::UPDATED_AT;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.email_templates');
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_MailHtmlTemplate::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!Context_MailHtmlTemplate::isWriteableByActor($model, $active_worker))
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
			
			// Tokens
			
			$worker_token_labels = [];
			$worker_token_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $worker_token_labels, $worker_token_values);
			
			$placeholders = Extension_DevblocksContext::getPlaceholderTree($worker_token_labels);
			$tpl->assign('placeholders', $placeholders);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/mail_html_template/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
