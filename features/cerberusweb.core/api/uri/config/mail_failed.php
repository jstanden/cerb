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

class PageSection_SetupMailFailed extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$visit->set(ChConfigurationPage::ID, 'mail_failed');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'setup_mail_failed';
		$defaults->class_name = 'View_MailParseFail';
		$defaults->name = 'Failed Messages';
		$defaults->is_ephemeral = true;
		$defaults->renderLimit = 15;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		C4_AbstractViewLoader::setView($view->id, $view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_failed/index.tpl');
	}
	
	function showPeekPopupAction() {
		@$file = basename(DevblocksPlatform::importGPC($_REQUEST['file'],'string',''));
		@$view_id = basename(DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',''));
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Resolve any symbolic links
		
		if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
			return false;

		// Make sure our requested file is in the same directory
		
		if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
			return false;
		
		// If the extension isn't .msg, abort.
		if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
			return false;
		
		// Template
		
		$tpl->assign('filename', basename($full_path));

		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_failed/peek.tpl');
	}
	
	function getRawMessageSourceAction() {
		@$file = basename(DevblocksPlatform::importGPC($_REQUEST['file'],'string',''));
		
		// Resolve any symbolic links
		
		if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
			return false;

		// Make sure our requested file is in the same directory
		
		if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
			return false;
		
		// If the extension isn't .msg, abort.
		if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
			return false;
		
		if(null != ($mime = mailparse_msg_parse_file($full_path))) {
			$struct = mailparse_msg_get_structure($mime);
			$msginfo = mailparse_msg_get_part_data($mime);
	
			$message_encoding = strtolower($msginfo['charset']);
			
			header('Content-Type: text/plain; charset=' . $message_encoding);
			
			@$message_source = file_get_contents($full_path);
			echo $message_source;
		}
	}
	
	function parseMessageJsonAction() {
		header("Content-Type: application/json");
		
		$logger = DevblocksPlatform::getConsoleLog('Parser');
		$logger->setLogLevel(4);
		
		ob_start();
		
		$log = null;
		
		try {
			@$file = DevblocksPlatform::importGPC($_REQUEST['file'],'string','');
			@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
	
			// Resolve any symbolic links
			
			if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
				return false;
	
			// Make sure our requested file is in the same directory
			
			if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
				return false;
			
			// If the extension isn't .msg, abort.
			if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
				return false;
			
			// Parse
			
			if(false === ($dict = CerberusParser::parseMessageSource("file://" . $full_path, true, false)))
				throw new Exception("Failed to parse the message.");
			
			// If successful, delete the referenced file and update marquee
			
			if(is_object($dict) && !empty($dict->id)) {
				C4_AbstractView::setMarquee($view_id, sprintf('<b>Ticket updated:</b> <a href="%s">%s</a>',
					$dict->url,
					$dict->_label
				));
				
			} elseif(null === $dict) {
				$log = ob_get_contents();
				
				C4_AbstractView::setMarquee($view_id, sprintf('<b>Rejected:</b> %s',
					$log
				));
			}
			
			// JSON
			
			$json = json_encode(array(
				'status' => true,
			));
			
			ob_end_clean();
			
			echo $json;
			
		} catch (Exception $e) {
			$log = ob_get_contents();
			
			$json = json_encode(array(
				'status' => false,
				'message' => $e->getMessage(),
				'log' => $log,
			));
			
			ob_end_clean();
			
			echo $json;
		}
		
		$logger->setLogLevel(0);
	}
	
	function deleteMessageJsonAction() {
		header("Content-Type: application/json");
		
		@$file = basename(DevblocksPlatform::importGPC($_REQUEST['file'],'string',''));
		@$view_id = basename(DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',''));
		
		try {
			// Resolve any symbolic links
			
			if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
				throw new Exception("Invalid message file.");
	
			// Make sure our requested file is in the same directory
			
			if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
				throw new Exception("Invalid message file.");
			
			// If the extension isn't .msg, abort.
			if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
				throw new Exception("Invalid message file.");
			
			@unlink($full_path);
			
			C4_AbstractView::setMarquee($view_id, sprintf('<b>Deleted file:</b> %s',
				$file
			));
			
			echo json_encode(array(
				'status' => true,
			));
			
		} catch(Exception $e) {
			echo json_encode(array(
				'status' => false,
				'message' => $e->getMessage(),
			));
			
		}
	}
};

class SearchFields_MailParseFail {
	const NAME = 'mf_name';
	const SIZE = 'mf_size';
	const CTIME = 'mf_ctime';
	const MTIME = 'mf_mtime';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::NAME => new DevblocksSearchField(self::NAME, 'mf', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::SIZE => new DevblocksSearchField(self::SIZE, 'mf', 'size', $translate->_('common.size'), Model_CustomField::TYPE_NUMBER),
			self::CTIME => new DevblocksSearchField(self::CTIME, 'mf', 'ctime', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::MTIME => new DevblocksSearchField(self::MTIME, 'mf', 'mtime', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class View_MailParseFail extends C4_AbstractView {
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
		
		$this->addParamsHidden(array(
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = array();

		$mail_fail_path = APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR;
		
		$fail_files = glob($mail_fail_path . '*.msg', GLOB_NOSORT);

		foreach($fail_files as $file) {
			$stat = stat($file);
			
			$result = array(
				SearchFields_MailParseFail::NAME => basename($file),
				SearchFields_MailParseFail::SIZE => $stat['size'],
				SearchFields_MailParseFail::CTIME => $stat['ctime'],
				SearchFields_MailParseFail::MTIME => $stat['mtime'],
			);
			
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
								
								@list($from, $to) = $param->value;
								
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
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::configuration/section/mail_failed/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			//case SearchFields_CallEntry::VIRTUAL_CONTEXT_LINK:
			//	$this->_renderVirtualContextLinks($param);
			//	break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_MailParseFail::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_MailParseFail::SIZE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_MailParseFail::CTIME:
			case SearchFields_MailParseFail::MTIME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case 'placeholder_bool';
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_fulltext';
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
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
				@$bool = DevblocksPlatform::importGPC($_REQUEST['value'],'integer',0);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_MailParseFail::CTIME:
			case SearchFields_MailParseFail::MTIME:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_fulltext':
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
	
	/*
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
				case 'is_closed':
					if(!empty($v)) { // completed
						$change_fields[DAO_CallEntry::IS_CLOSED] = 1;
					} else { // active
						$change_fields[DAO_CallEntry::IS_CLOSED] = 0;
					}
					break;
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
			list($objects,$null) = DAO_CallEntry::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CallEntry::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_CallEntry::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_CALL, $custom_fields, $batch_ids);
			
			// Scheduled behavior
			if(isset($do['behavior']) && is_array($do['behavior'])) {
				$behavior_id = $do['behavior']['id'];
				@$behavior_when = strtotime($do['behavior']['when']) or time();
				@$behavior_params = isset($do['behavior']['params']) ? $do['behavior']['params'] : array();
				
				if(!empty($batch_ids) && !empty($behavior_id))
				foreach($batch_ids as $batch_id) {
					DAO_ContextScheduledBehavior::create(array(
						DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
						DAO_ContextScheduledBehavior::CONTEXT => CerberusContexts::CONTEXT_CALL,
						DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
						DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
						DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
					));
				}
			}
			
			// Watchers
			if(isset($do['watchers']) && is_array($do['watchers'])) {
				$watcher_params = $do['watchers'];
				foreach($batch_ids as $batch_id) {
					if(isset($watcher_params['add']) && is_array($watcher_params['add']))
						CerberusContexts::addWatchers(CerberusContexts::CONTEXT_CALL, $batch_id, $watcher_params['add']);
					if(isset($watcher_params['remove']) && is_array($watcher_params['remove']))
						CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_CALL, $batch_id, $watcher_params['remove']);
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
	*/
};
