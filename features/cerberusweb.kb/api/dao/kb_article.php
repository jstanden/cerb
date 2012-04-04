<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2012, WebGroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class DAO_KbArticle extends C4_ORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const UPDATED = 'updated';
	const VIEWS = 'views';
	const FORMAT = 'format';
	const CONTENT = 'content';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO kb_article (updated) ".
			"VALUES (%d)",
			time()
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];

		return null;
	}

	static function getWhere($where=null, $sortBy='updated', $sortAsc=false, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, title, views, updated, format, content ".
			"FROM kb_article ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param resource $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_KbArticle();
			$object->id = intval($row['id']);
			$object->title = $row['title'];
			$object->updated = $row['updated'];
			$object->views = $row['views'];
			$object->format = $row['format'];
			$object->content = $row['content'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}

	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = array($ids);
		parent::_update($ids, 'kb_article', $fields);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
		// Articles
		$db->Execute(sprintf("DELETE QUICK FROM kb_article WHERE id IN (%s)", $id_string));
		
		// Categories
		$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_article_id IN (%s)", $id_string));
		
		// Search indexes
		$db->Execute(sprintf("DELETE QUICK FROM fulltext_kb_article WHERE id IN (%s)", $id_string));
		
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
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
	    $eventMgr = DevblocksPlatform::getEventService();
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
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($article_id))
			return array();
		
		$categories = array();
		
		$rs = $db->Execute(sprintf("SELECT kb_category_id ". 
			"FROM kb_article_to_category ".
			"WHERE kb_article_id = %d",
			$article_id
		));
		
		while($row = mysql_fetch_assoc($rs)) {
			$cat_id = intval($row['kb_category_id']);
			$categories[$cat_id] = $cat_id;
		}
		
		mysql_free_result($rs);
		
		return $categories;
	}
	
	static function setCategories($article_ids,$category_ids,$replace=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($article_ids))
			$article_ids = array($article_ids);

		if(!is_array($category_ids))
			$category_ids = array($category_ids);
		
		if($replace) {
			$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_article_id IN (%s)",
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
					$pid = $category_id;
					while($pid) {
						$top_category_id = $pid;
						$pid = $categories[$pid]->parent_id;
					}
					
					if(is_array($article_ids))
					foreach($article_ids as $article_id) {
						$db->Execute(sprintf("REPLACE INTO kb_article_to_category (kb_article_id, kb_category_id, kb_top_category_id) ".
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
						$db->Execute(
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
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
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

		// [JAS]: Dynamic table joins
		if(isset($tables['katc'])) {
			$select_sql .= sprintf(", katc.kb_top_category_id AS %s ",
				SearchFields_KbArticle::TOP_CATEGORY_ID
			);
			$join_sql .= "LEFT JOIN kb_article_to_category katc ON (kb.id=katc.kb_article_id) ";
		}
		
		if(isset($tables['ftkb'])) {
			$join_sql .= 'LEFT JOIN fulltext_kb_article ftkb ON (ftkb.id=kb.id) ';
		}
		
		// [JAS]: Dynamic table joins
		if(isset($tables['context_link'])) 
			$join_sql .= "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.kb_article' AND context_link.to_context_id = kb.id) ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'kb.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");

		// Translate virtual fields
		
		array_walk_recursive(
			$params,
			array('DAO_KbArticle', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);
		
		$result = array(
			'primary_table' => 'kb',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => true,
			'sort' => $sort_sql,
		);
		
		return $result;
	}	
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
		
		$param_key = $param->field;
		settype($param_key, 'string');
		switch($param_key) {
			case SearchFields_KbArticle::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				$from_context = CerberusContexts::CONTEXT_KB_ARTICLE;
				$from_index = 'kb.id';
				
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}
	
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY kb.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_KbArticle::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT kb.id) " : "SELECT COUNT(kb.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_KbArticle implements IDevblocksSearchFields {
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
	
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'kb', 'id', $translate->_('kb_article.id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'kb', 'title', $translate->_('kb_article.title')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'kb', 'updated', $translate->_('kb_article.updated')),
			self::VIEWS => new DevblocksSearchField(self::VIEWS, 'kb', 'views', $translate->_('kb_article.views')),
			self::FORMAT => new DevblocksSearchField(self::FORMAT, 'kb', 'format', $translate->_('kb_article.format')),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'kb', 'content', $translate->_('kb_article.content')),
			
			self::CATEGORY_ID => new DevblocksSearchField(self::CATEGORY_ID, 'katc', 'kb_category_id'),
			self::TOP_CATEGORY_ID => new DevblocksSearchField(self::TOP_CATEGORY_ID, 'katc', 'kb_top_category_id', $translate->_('kb_article.topic')),
			
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);

		// Fulltext
		$tables = DevblocksPlatform::getDatabaseTables();
		if(isset($tables['fulltext_kb_article'])) {
			$columns[self::FULLTEXT_ARTICLE_CONTENT] = new DevblocksSearchField(self::FULLTEXT_ARTICLE_CONTENT, 'ftkb', 'content', $translate->_('kb_article.content'));
		}
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_KB_ARTICLE);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Search_KbArticle {
	const ID = 'cerberusweb.search.schema.kb_article';
	
	public static function index($stop_time=null) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		if(false == ($search = DevblocksPlatform::getSearchService())) {
			$logger->error("[Search] The search engine is misconfigured.");
			return;
		}
		
		$ns = 'kb_article';
		$id = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'last_indexed_id', 0);
		$ptr_time = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf("%s >= %d AND %s > %d", 
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
				
				$search->index($ns, $id, $article->title . ' ' . strip_tags($article->content), true);
				
				flush();
			}
		}
		
		// If we ran out of articles, always reset the ID and use the current time
		if($done) {
			$ptr_id = 0;
			$ptr_time = time();
		}
		
		DAO_DevblocksExtensionPropertyStore::put(self::ID, 'last_indexed_id', $ptr_id);
		DAO_DevblocksExtensionPropertyStore::put(self::ID, 'last_indexed_time', $ptr_time);
	}
};

class Model_KbArticle {
	const FORMAT_PLAINTEXT = 0;
	const FORMAT_HTML = 1;
	const FORMAT_MARKDOWN = 2;
	
	public $id = 0;
	public $title = '';
	public $views = 0;
	public $updated = 0;
	public $format = 0;
	public $content = '';
	
	function getContent() {
		$html = '';
		
		switch($this->format) {
			case self::FORMAT_HTML:
				$html = $this->content;
				break;
			case self::FORMAT_PLAINTEXT:
				$html = nl2br(htmlentities($this->content, ENT_QUOTES, LANG_CHARSET_CODE));
				break;
			case self::FORMAT_MARKDOWN:
				$html = DevblocksPlatform::parseMarkdown($this->content);
				break;
		}
		
		return $html;
	}
	
	// [TODO] Reuse this!
	function getCategories() {
		$categories = DAO_KbCategory::getAll();
		$cats = DAO_KbArticle::getCategoriesByArticleId($this->id);

		$trails = array();
		
		foreach($cats as $cat_id) {
			$pid = $cat_id;
			$trail = array();
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
		
		$breadcrumbs = array();
		
		if(is_array($trails))
		foreach($trails as $idx => $trail) {
			$last_step = end($trail);
			reset($trail);
			
			foreach($trail as $step) {
				if(!isset($breadcrumbs[$last_step]))
					$breadcrumbs[$last_step] = array();
					
				$breadcrumbs[$last_step][$step] = $categories[$step];
			}
		}
		
		unset($trails);
		
		return $breadcrumbs;
	}
};

class Context_KbArticle extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		return TRUE;
	}
	
	function getRandom() {
		return DAO_KbArticle::random();
	}
	
	function getMeta($context_id) {
		$article = DAO_KbArticle::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$friendly = DevblocksPlatform::strToPermalink($article->title);
		
		return array(
			'id' => $article->id,
			'name' => $article->title,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=kb&ar=article&id=%d-%s", $article->id, $friendly, true)),
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
		} else {
			$article = null;
		}
		/* @var $article Model_KbArticle */
			
		// Token labels
		$token_labels = array(
			'content' => $prefix.$translate->_('kb_article.content'),
			'id' => $prefix.$translate->_('common.id'),
			'title' => $prefix.$translate->_('kb_article.title'),
			'updated|date' => $prefix.$translate->_('kb_article.updated'),
			'views' => $prefix.$translate->_('kb_article.views'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_KB_ARTICLE;
		
		// Token values
		if(null != $article) {
			$token_values['_label'] = $article->title;
			$token_values['content'] = $article->getContent();
			$token_values['id'] = $article->id;
			$token_values['title'] = $article->title;
			$token_values['updated'] = $article->updated;
			$token_values['views'] = $article->views;
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=kb&ar=article&id=%d-%s",$article->id, DevblocksPlatform::strToPermalink($article->title)), true);
		}
		
		return TRUE;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_KB_ARTICLE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
			case 'categories':
				// Categories
				if(null != ($categories = $article->getCategories()) && is_array($categories)) {
					$dict->categories = array();
					
					foreach($categories as $category_id => $trail) {
						foreach($trail as $step_id => $step) {
							if(!isset($token_values['categories'][$category_id]))
								$dict->categories[$category_id] = array();
							$dict->categories[$category_id][$step_id] = $step->name;
						}
					}
				}
				break;
			
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}	
	
	function getChooserView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
//		$view->name = 'Headlines';
//		$view->view_columns = array(
//			SearchFields_CallEntry::IS_OUTGOING,
//			SearchFields_CallEntry::PHONE,
//			SearchFields_CallEntry::UPDATED_DATE,
//		);
		$view->addParams(array(
			//SearchFields_KbArticle::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_KbArticle::IS_CLOSED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_KbArticle::UPDATED;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = true;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		//$view->name = 'Calls';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_KbArticle::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_KbArticle::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};

class View_KbArticle extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'kb_overview';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Articles';
		$this->renderSortBy = 'kb_updated';
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_KbArticle::TITLE,
			SearchFields_KbArticle::UPDATED,
			SearchFields_KbArticle::VIEWS,
		);
		$this->addColumnsHidden(array(
			SearchFields_KbArticle::CONTENT,
			SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,
			SearchFields_KbArticle::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_KbArticle::CONTENT,
			SearchFields_KbArticle::FORMAT,
			SearchFields_KbArticle::ID,
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
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_KbArticle', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_KbArticle', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_KbArticle::TOP_CATEGORY_ID:
				case SearchFields_KbArticle::FORMAT:
				case SearchFields_KbArticle::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				$topics = DAO_KbCategory::getAll();
				$label_map = array('0' => '(none)');
				foreach($topics as $topic_id => $topic) {
					if(!empty($topic->parent_id))
						continue;
					$label_map[$topic_id] = $topic->name;
				}
				$counts = $this->_getSubtotalCountForStringColumn('DAO_KbArticle', $column, $label_map, 'in', 'topic_id[]');
				break;
				
			case SearchFields_KbArticle::FORMAT:
				$label_map = array(
					'0' => 'Plaintext',
					'1' => 'HTML',
					'2' => 'Markdown',
				);
				$counts = $this->_getSubtotalCountForStringColumn('DAO_KbArticle', $column, $label_map, '=', 'value');
				break;

			case SearchFields_KbArticle::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_KbArticle', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_KbArticle', $column, 'kb.id');
				}
				
				break;
		}
		
		return $counts;
	}	
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$categories = DAO_KbCategory::getAll();
		$tpl->assign('categories', $categories);

		switch($this->renderTemplate) {
			case 'chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.kb::view/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_KbArticle::TITLE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_KbArticle::UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
//			case SearchFields_KbArticle::FORMAT:
//				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
//				break;

			case SearchFields_KbArticle::VIEWS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				$topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
					DAO_KbCategory::PARENT_ID,
					0
				));

				$options = array();
				
				if(is_array($topics))
				foreach($topics as $cat_id => $cat) { /* @var $cat Model_KbCategory */
					$options[$cat_id] = $cat->name;
				}
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;
				
			case SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_KbArticle::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			default:
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
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
				$topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
					DAO_KbCategory::PARENT_ID,
					0
				));
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($topics[$val]))
						continue;
						$strings[] = $topics[$val]->name;
					}
				}
				echo implode(" or ", $strings);
				break;
				
			case SearchFields_KbArticle::FORMAT:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 0:
							$strings[] = "Plaintext";
							break;
						case 1:
							$strings[] = "HTML";
							break;
						case 2:
							$strings[] = "Markdown";
							break;
					}
				}
				echo implode(" or ", $strings);
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
				
			case SearchFields_KbArticle::TOP_CATEGORY_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'], 'array', array());
				$criteria = new DevblocksSearchCriteria($field, $oper, $options);
				break;
				
			case SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field, $oper, array($value,$scope));
				break;
				
			case SearchFields_KbArticle::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
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
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
//				case 'x':
//					break;
				default:
					// Custom fields
//					if(substr($k,0,3)=="cf_") {
//						$custom_fields[substr($k,3)] = $v;
//					}
					break;
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_KbArticle::search(
				array(
					SearchFields_KbArticle::ID
				),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_KbArticle::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields))
				DAO_KbArticle::update($batch_ids, $change_fields);
			
			// Category deltas
			if(isset($do['category_delta'])) {
				DAO_KbArticle::setCategories($batch_ids, $do['category_delta'], false);
			}
			
			// Custom Fields
			//self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_ADDRESS, $custom_fields, $batch_ids);
			
			// Scheduled behavior
			if(isset($do['behavior']) && is_array($do['behavior'])) {
				$behavior_id = $do['behavior']['id'];
				@$behavior_when = strtotime($do['behavior']['when']) or time();
				@$behavior_params = isset($do['behavior']['params']) ? $do['behavior']['params'] : array();
				
				if(!empty($batch_ids) && !empty($behavior_id))
				foreach($batch_ids as $batch_id) {
					DAO_ContextScheduledBehavior::create(array(
						DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
						DAO_ContextScheduledBehavior::CONTEXT => CerberusContexts::CONTEXT_KB_ARTICLE,
						DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
						DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
						DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
					));
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
		
};