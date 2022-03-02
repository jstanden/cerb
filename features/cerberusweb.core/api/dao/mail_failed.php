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

class SearchFields_MailParseFail extends DevblocksSearchFields {
	const NAME = 'mf_name';
	const SIZE = 'mf_size';
	const CTIME = 'mf_ctime';
	const MTIME = 'mf_mtime';
	
	static function getPrimaryKey() {
		return '';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::NAME => new DevblocksSearchField(self::NAME, 'mf', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::SIZE => new DevblocksSearchField(self::SIZE, 'mf', 'size', $translate->_('common.size'), Model_CustomField::TYPE_NUMBER, true),
			self::CTIME => new DevblocksSearchField(self::CTIME, 'mf', 'ctime', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::MTIME => new DevblocksSearchField(self::MTIME, 'mf', 'mtime', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class View_MailParseFail extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'setup_mail_failed';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = 'Failed Messages';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_MailParseFail::CTIME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_MailParseFail::NAME,
			SearchFields_MailParseFail::SIZE,
			SearchFields_MailParseFail::CTIME,
		);
		$this->addColumnsHidden(array(
		));
		
		$this->doResetCriteria();
	}
	
	function getData() {
		$objects = [];

		$mail_fail_path = APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR;
		
		$fail_files = glob($mail_fail_path . '*.msg', GLOB_NOSORT);

		foreach($fail_files as $file) {
			$stat = stat($file);
			
			$result = [
				SearchFields_MailParseFail::NAME => basename($file),
				SearchFields_MailParseFail::SIZE => $stat['size'],
				SearchFields_MailParseFail::CTIME => $stat['ctime'],
				SearchFields_MailParseFail::MTIME => $stat['mtime'],
			];
			
			$objects[] = $result;
		}

		// Filter
		// [TODO] This is reusable for other simulated views
		
		foreach($this->getParams() as $param_key => $param) {
			if(!($param instanceof DevblocksSearchCriteria))
				continue;
			
			switch($param->field) {
				case SearchFields_MailParseFail::NAME:
					switch($param->operator) {
						case DevblocksSearchCriteria::OPER_LIKE:
						case DevblocksSearchCriteria::OPER_NOT_LIKE:
							$objects = array_filter($objects, function($object) use ($param) {
								$not = ($param->operator == DevblocksSearchCriteria::OPER_NOT_LIKE) ? true : false;
								$pass = preg_match(DevblocksPlatform::strToRegExp($param->value), $object[$param->field]) ? true : false;
								return $pass == !$not;
							});
							break;
							
						case DevblocksSearchCriteria::OPER_EQ:
						case DevblocksSearchCriteria::OPER_NEQ:
							$objects = array_filter($objects, function($object) use ($param) {
								$not = ($param->operator == DevblocksSearchCriteria::OPER_NEQ) ? true : false;
								$pass = ($param->value == $object[$param->field]);
								return $pass == !$not;
							});
							break;
							
						case DevblocksSearchCriteria::OPER_IS_NULL:
							$objects = array_filter($objects, function($object) use ($param) {
								return empty($object[$param->field]);
							});
							break;
					}
					
					break;
					
				case SearchFields_MailParseFail::SIZE:
					switch($param->operator) {
						case DevblocksSearchCriteria::OPER_EQ:
						case DevblocksSearchCriteria::OPER_NEQ:
							$objects = array_filter($objects, function($object) use ($param) {
								$not = ($param->operator == DevblocksSearchCriteria::OPER_NEQ) ? true : false;
								$pass = ($param->value == $object[$param->field]);
								return $pass == !$not;
							});
							break;
							
						case DevblocksSearchCriteria::OPER_GT:
							$objects = array_filter($objects, function($object) use ($param) {
								return ($object[$param->field] > $param->value);
							});
							break;
							
						case DevblocksSearchCriteria::OPER_LT:
							$objects = array_filter($objects, function($object) use ($param) {
								return ($object[$param->field] < $param->value);
							});
							break;
					}
					break;
					
				case SearchFields_MailParseFail::CTIME:
				case SearchFields_MailParseFail::MTIME:
					switch($param->operator) {
						case DevblocksSearchCriteria::OPER_BETWEEN:
						case DevblocksSearchCriteria::OPER_NOT_BETWEEN:
							$objects = array_filter($objects, function($object) use ($param) {
								$not = ($param->operator == DevblocksSearchCriteria::OPER_NOT_BETWEEN) ? true : false;
								
								list($from, $to) = array_pad($param->value, 2, null);
								
								if(false == (@$from = strtotime($from)))
									$from = 0;
								
								if(false == (@$to= strtotime($to)))
									$to = time();
								
								$pass = ($from <= $object[$param->field] && $to >= $object[$param->field]);
								return $pass == !$not;
							});
							break;
							
						case DevblocksSearchCriteria::OPER_EQ_OR_NULL:
							$objects = array_filter($objects, function($object) use ($param) {
								return empty($object[$param->field]);
							});
							break;
					}
					break;
			}
		}
		
		// Sort
		
		DevblocksPlatform::sortObjects($objects, sprintf('[%s]', $this->renderSortBy), $this->renderSortAsc);
		
		// Limit
		
		$total = count($objects);
		
		$start = $this->renderPage * $this->renderLimit;
		
		if($start > $total)
			$objects = array();
		else
			$objects = array_slice($objects, $start, $this->renderLimit);
		
		return array($objects, $total);
	}

	function getDataAsObjects($ids=null) {
		return array();
		//return $this->_getDataAsObjects('DAO_CallEntry', $ids);
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_MailParseFail::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailParseFail::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_MailParseFail::CTIME),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_MailParseFail::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'size' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_MailParseFail::SIZE),
					'examples' => [
						'>1MB',
						'<=512KB',
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_MailParseFail::MTIME),
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
			case 'size':
				return DevblocksSearchCriteria::getBytesParamFromTokens(SearchFields_MailParseFail::SIZE, $tokens);
				break;
				
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

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/mail_failed/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			//case SearchFields_MailParseFail::VIRTUAL_CONTEXT_LINK:
			//	$this->_renderVirtualContextLinks($param);
			//	break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			//case SearchFields_MailParseFail::CTIME:
			//	$this->_renderCriteriaParamBoolean($param);
			//	break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_MailParseFail::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_MailParseFail::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_MailParseFail::SIZE:
				$bool = DevblocksPlatform::importGPC($_POST['value'] ?? null, 'integer',0);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_MailParseFail::CTIME:
			case SearchFields_MailParseFail::MTIME:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_fulltext':
				$scope = DevblocksPlatform::importGPC($_POST['scope'] ?? null, 'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};
