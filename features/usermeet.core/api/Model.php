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
class Model_CommunityTool {
    public $id = 0;
    public $name = '';
    public $code = '';
    public $extension_id = '';
};

class Model_CommunitySession {
	public $session_id = '';
	public $created = 0;
	public $updated = 0;
	private $_properties = array();

	function setProperties($properties) {
		$this->_properties = $properties;
	}
	
	function getProperties() {
		return $this->_properties;
	}
	
	function setProperty($key, $value) {
		if(null==$value) {
			unset($this->_properties[$key]);	
		} else {
			$this->_properties[$key] = $value;
		}
		DAO_CommunitySession::save($this);
	}
	
	function getProperty($key, $default = null) {
		return isset($this->_properties[$key]) ? $this->_properties[$key] : $default;
	}
};

class View_CommunityPortal extends C4_AbstractView {
	const DEFAULT_ID = 'community_portals';
	const DEFAULT_TITLE = 'Community Portals';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CommunityTool::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CommunityTool::NAME,
			SearchFields_CommunityTool::CODE,
			SearchFields_CommunityTool::EXTENSION_ID,
			);
		
		$this->params = array(
			//SearchFields_CommunityTool::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_CommunityTool::IS_DISABLED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_CommunityTool::search(
			$this->view_columns,
			$this->params,
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
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

	    // Tool Manifests
	    $tools = DevblocksPlatform::getExtensions('usermeet.tool', false, true);
	    $tpl->assign('tool_extensions', $tools);
	    
		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);

		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(CustomFieldSource_CommunityPortal::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/usermeet.core/templates/community/config/tab/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = APP_PATH . '/features/usermeet.core/templates/';
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_CommunityTool::NAME:
			case SearchFields_CommunityTool::CODE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CommunityTool::EXTENSION_ID:
//				$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
//				$tpl->assign('sources', $source_renderers);
//				$tpl->display('file:' . $tpl_path . 'tasks/criteria/source.tpl');
				break;
				
//			case SearchFields_CommunityTool::IS_COMPLETED:
//				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
//				break;
				
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
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
//			case SearchFields_CommunityTool::EXTENSION_ID:
//				$sources = $ext = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);			
//				$strings = array();
//				
//				foreach($values as $val) {
//					if(!isset($sources[$val]))
//						continue;
//					else
//						$strings[] = $sources[$val]->getSourceName();
//				}
//				echo implode(", ", $strings);
//				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_CommunityTool::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_CommunityTool::ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_CommunityTool::ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
//			SearchFields_CommunityTool::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_CommunityTool::IS_COMPLETED,'=',0)
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CommunityTool::NAME:
			case SearchFields_CommunityTool::CODE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_CommunityTool::EXTENSION_ID:
//				@$sources = DevblocksPlatform::importGPC($_REQUEST['sources'],'array',array());
//				$criteria = new DevblocksSearchCriteria($field,$oper,$sources);
				break;
				
//			case SearchFields_Task::IS_COMPLETED:
//				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
//				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
//				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
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
//				case 'status':
//					if(1==intval($v)) { // completed
//						$change_fields[DAO_Task::IS_COMPLETED] = 1;
//						$change_fields[DAO_Task::COMPLETED_DATE] = time();
//					} else { // active
//						$change_fields[DAO_Task::IS_COMPLETED] = 0;
//						$change_fields[DAO_Task::COMPLETED_DATE] = 0;
//					}
//					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_CommunityTool::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_CommunityTool::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_CommunityTool::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CustomFieldSource_CommunityPortal::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};