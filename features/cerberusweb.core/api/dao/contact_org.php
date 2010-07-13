<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_ContactOrg extends C4_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const STREET = 'street';
	const CITY = 'city';
	const PROVINCE = 'province';
	const POSTAL = 'postal';
	const COUNTRY = 'country';
	const PHONE = 'phone';
	const WEBSITE = 'website';
	const CREATED = 'created';
	
	private function __construct() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			'id' => $translate->_('contact_org.id'),
			'name' => $translate->_('contact_org.name'),
			'street' => $translate->_('contact_org.street'),
			'city' => $translate->_('contact_org.city'),
			'province' => $translate->_('contact_org.province'),
			'postal' => $translate->_('contact_org.postal'),
			'country' => $translate->_('contact_org.country'),
			'phone' => $translate->_('contact_org.phone'),
			'website' => $translate->_('contact_org.website'),
			'created' => $translate->_('contact_org.created'),
		);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $fields
	 * @return integer
	 */
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('contact_org_seq');
		
		$sql = sprintf("INSERT INTO contact_org (id,name,street,city,province,postal,country,phone,website,created) ".
  			"VALUES (%d,'','','','','','','','',%d)",
			$id,
			time()
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		self::update($id, $fields);
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @param array $fields
	 * @return Model_ContactOrg
	 */
	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = array($ids);
		parent::_update($ids, 'contact_org', $fields);
	}
	
	/**
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$id_list = implode(',', $ids);
		
		// Orgs
		$sql = sprintf("DELETE QUICK FROM contact_org WHERE id IN (%s)",
			$id_list
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		// Clear any associated addresses
		$sql = sprintf("UPDATE address SET contact_org_id = 0 WHERE contact_org_id IN (%s)",
			$id_list
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		// Context links
		DAO_ContextLink::delete(CerberusContexts::CONTEXT_ORG, $ids);
        
        // Custom fields
        DAO_CustomFieldValue::deleteBySourceIds(ChCustomFieldSource_Org::ID, $ids);

        // Notes
        DAO_Comment::deleteByContext(CerberusContexts::CONTEXT_ORG, $ids);
	}
	
	/**
	 * @param string $where
	 * @return Model_ContactOrg[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,name,street,city,province,postal,country,phone,website,created ".
			"FROM contact_org ".
			(!empty($where) ? sprintf("WHERE %s ", $where) : " ")
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		return self::_getObjectsFromResultSet($rs);
	}
	
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ContactOrg();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->street = $row['street'];
			$object->city = $row['city'];
			$object->province = $row['province'];
			$object->postal = $row['postal'];
			$object->country = $row['country'];
			$object->phone = $row['phone'];
			$object->website = $row['website'];
			$object->created = intval($row['created']);
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_ContactOrg
	 */
	static function get($id) {
		$where = sprintf("%s = %d",
			self::ID,
			$id
		);
		$objects = self::getWhere($where);

		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}	

	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @param boolean $create_if_null
	 * @return Model_ContactOrg
	 */
	static function lookup($name, $create_if_null=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$orgs = self::getWhere(
			sprintf('%s = %s', self::NAME, $db->qstr($name))
		);
		
		if(empty($orgs)) {
			if($create_if_null) {
				$fields = array(
					self::NAME => $name
				);
				return self::create($fields);
			}
		} else {
			return key($orgs);
		}
		
		return NULL;
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
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		$fields = SearchFields_ContactOrg::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$select_sql = sprintf("SELECT ".
			"c.id as %s, ".
			"c.name as %s, ".
			"c.street as %s, ".
			"c.city as %s, ".
			"c.province as %s, ".
			"c.postal as %s, ".
			"c.country as %s, ".
			"c.phone as %s, ".
			"c.website as %s, ".
			"c.created as %s ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_ContactOrg::ID,
			    SearchFields_ContactOrg::NAME,
			    SearchFields_ContactOrg::STREET,
			    SearchFields_ContactOrg::CITY,
			    SearchFields_ContactOrg::PROVINCE,
			    SearchFields_ContactOrg::POSTAL,
			    SearchFields_ContactOrg::COUNTRY,
			    SearchFields_ContactOrg::PHONE,
			    SearchFields_ContactOrg::WEBSITE,
			    SearchFields_ContactOrg::CREATED
			);

		$join_sql = 
			"FROM contact_org c ".
			
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.org' AND context_link.to_context_id = c.id) " : " ")
			;		
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'c.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
			
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY c.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($row[SearchFields_ContactOrg::ID]);
			$results[$ticket_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT c.id) " : "SELECT COUNT(c.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }	
};

class SearchFields_ContactOrg {
	const ID = 'c_id';
	const NAME = 'c_name';
	const STREET = 'c_street';
	const CITY = 'c_city';
	const PROVINCE = 'c_province';
	const POSTAL = 'c_postal';
	const COUNTRY = 'c_country';
	const PHONE = 'c_phone';
	const WEBSITE = 'c_website';
	const CREATED = 'c_created';

	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'c', 'id', $translate->_('contact_org.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'c', 'name', $translate->_('contact_org.name')),
			self::STREET => new DevblocksSearchField(self::STREET, 'c', 'street', $translate->_('contact_org.street')),
			self::CITY => new DevblocksSearchField(self::CITY, 'c', 'city', $translate->_('contact_org.city')),
			self::PROVINCE => new DevblocksSearchField(self::PROVINCE, 'c', 'province', $translate->_('contact_org.province')),
			self::POSTAL => new DevblocksSearchField(self::POSTAL, 'c', 'postal', $translate->_('contact_org.postal')),
			self::COUNTRY => new DevblocksSearchField(self::COUNTRY, 'c', 'country', $translate->_('contact_org.country')),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'c', 'phone', $translate->_('contact_org.phone')),
			self::WEBSITE => new DevblocksSearchField(self::WEBSITE, 'c', 'website', $translate->_('contact_org.website')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'c', 'created', $translate->_('contact_org.created')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));
		
		return $columns;
	}
};

class Model_ContactOrg {
	public $id;
	public $name;
	public $street;
	public $city;
	public $province;
	public $postal;
	public $country;
	public $phone;
	public $website;
	public $created;
	public $sync_id = '';
};

class View_ContactOrg extends C4_AbstractView {
	const DEFAULT_ID = 'contact_orgs';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('addy_book.tab.organizations');
		$this->renderSortBy = 'c_name';
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContactOrg::CREATED,
			SearchFields_ContactOrg::COUNTRY,
			SearchFields_ContactOrg::PHONE,
			SearchFields_ContactOrg::WEBSITE,
		);
		$this->columnsHidden = array(
			SearchFields_ContactOrg::CONTEXT_LINK,
			SearchFields_ContactOrg::CONTEXT_LINK_ID,
		);
		
		$this->paramsHidden = array(
			SearchFields_ContactOrg::ID,
			SearchFields_ContactOrg::CONTEXT_LINK,
			SearchFields_ContactOrg::CONTEXT_LINK_ID,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContactOrg::search(
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

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('core_tpl', APP_PATH . '/features/cerberusweb.core/templates/');
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$org_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('custom_fields', $org_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/contacts/orgs/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/contacts/orgs/contact_view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ContactOrg::NAME:
			case SearchFields_ContactOrg::STREET:
			case SearchFields_ContactOrg::CITY:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::POSTAL:
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::PHONE:
			case SearchFields_ContactOrg::WEBSITE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_ContactOrg::CREATED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
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
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContactOrg::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContactOrg::NAME:
			case SearchFields_ContactOrg::STREET:
			case SearchFields_ContactOrg::CITY:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::POSTAL:
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::PHONE:
			case SearchFields_ContactOrg::WEBSITE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_ContactOrg::CREATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
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
		@set_time_limit(0);
	  
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
				case 'country':
					$change_fields[DAO_ContactOrg::COUNTRY] = $v;
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_ContactOrg::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_ContactOrg::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_ContactOrg::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_Org::ID, $custom_fields, $batch_ids);

			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Org extends Extension_DevblocksContext {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=contacts&tab=orgs&action=display&id='.$context_id, true);
    }
    
	function getContext($org, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Org:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);

		// Polymorph
		if(is_numeric($org)) {
			$org = DAO_ContactOrg::get($org);
		} elseif($org instanceof Model_ContactOrg) {
			// It's what we want already.
		} else {
			$org = null;
		}
		
		// Token labels
		$token_labels = array(
			'name' => $prefix.$translate->_('contact_org.name'),
			'city' => $prefix.$translate->_('contact_org.city'),
			'country' => $prefix.$translate->_('contact_org.country'),
			'created' => $prefix.$translate->_('contact_org.created'),
			'phone' => $prefix.$translate->_('contact_org.phone'),
			'postal' => $prefix.$translate->_('contact_org.postal'),
			'province' => $prefix.$translate->_('contact_org.province'),
			'street' => $prefix.$translate->_('contact_org.street'),
			'website' => $prefix.$translate->_('contact_org.website'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Org token values
		if($org) {
			$token_values['id'] = $org->id;
			$token_values['name'] = $org->name;
			$token_values['created'] = $org->created;
			if(!empty($org->city))
				$token_values['city'] = $org->city;
			if(!empty($org->country))
				$token_values['country'] = $org->country;
			if(!empty($org->phone))
				$token_values['phone'] = $org->phone;
			if(!empty($org->postal))
				$token_values['postal'] = $org->postal;
			if(!empty($org->province))
				$token_values['province'] = $org->province;
			if(!empty($org->street))
				$token_values['street'] = $org->street;
			if(!empty($org->website))
				$token_values['website'] = $org->website;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $org->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $org)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $org)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}

		return true;		
	}

	function getChooserView() {
		// View
		$view_id = 'contextlink_'.str_replace('.','_',$this->id);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = 'View_ContactOrg';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Organizations';

		$view->view_columns = array(
			SearchFields_ContactOrg::PHONE,
			SearchFields_ContactOrg::COUNTRY,
			SearchFields_ContactOrg::WEBSITE,
		);
		
		$view->renderSortBy = SearchFields_ContactOrg::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context, $context_id) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = 'View_ContactOrg';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Organizations';
		$view->addParams(array(
			new DevblocksSearchCriteria(SearchFields_ContactOrg::CONTEXT_LINK,'=',$context),
			new DevblocksSearchCriteria(SearchFields_ContactOrg::CONTEXT_LINK_ID,'=',$context_id),
		), true);
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};