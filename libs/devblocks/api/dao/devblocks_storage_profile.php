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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

if(class_exists('C4_AbstractView')):
class View_DevblocksStorageProfile extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'devblocksstorageprofile';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Storage Profiles';

		$this->view_columns = array(
			SearchFields_DevblocksStorageProfile::NAME,
			SearchFields_DevblocksStorageProfile::EXTENSION_ID,
		);

		$this->addColumnsHidden(array(
			SearchFields_DevblocksStorageProfile::PARAMS_JSON,
		), true);
		
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_DevblocksStorageProfile::ID;
		$this->renderSortAsc = true;
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_DevblocksStorageProfile::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_DevblocksStorageProfile');
		
		return $objects;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_DevblocksStorageProfile::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksStorageProfile::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_DevblocksStorageProfile::ID),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksStorageProfile::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			default:
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

		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_profiles/view.tpl');
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
		return SearchFields_DevblocksStorageProfile::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_DevblocksStorageProfile::NAME:
			case SearchFields_DevblocksStorageProfile::EXTENSION_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_DevblocksStorageProfile::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};
endif;