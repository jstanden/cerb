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

class Page_Search extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function handleSectionActionAction() {
		@$section_uri = DevblocksPlatform::importGPC($_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $response->path;
		@array_shift($stack); // search
		@$context_extid = array_shift($stack); // context
		
		if(empty($context_extid))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context_extid, true))) { /* @var $context_ext Extension_DevblocksContext */
			if(null == ($context_ext = Extension_DevblocksContext::get($context_extid)))
				return;
		}
		
		if(!isset($context_ext->manifest->params['options'][0]['workspace']))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		$view = $context_ext->getSearchView();
		
		// Placeholders
		
		$labels = array();
		$values = array();
		
		$labels['current_worker_id'] = array(
			'label' => 'Current Worker',
			'context' => CerberusContexts::CONTEXT_WORKER,
		);
		
		$values['current_worker_id'] = $active_worker->id;
		
		$view->setPlaceholderLabels($labels);
		$view->setPlaceholderValues($values);
		
		// Template
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/index.tpl');
	}
	
	function ajaxQuickSearchAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$token = DevblocksPlatform::importGPC($_REQUEST['field'],'string','');
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		@$reset = DevblocksPlatform::importGPC($_REQUEST['reset'],'integer',0);
		
		header("Content-type: application/json");
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) { /* @var $view C4_AbstractView */
			echo json_encode(null);
			return;
		}
		
		if(preg_match('/^\#reset(.*)/', $query, $matches)) {
			$reset = 1;
			$query = trim($matches[1]);
		}
		
		DAO_WorkerPref::set($active_worker->id, 'quicksearch_' . strtolower(get_class($view)), $token);
		
		if(!empty($reset))
			$view->doResetCriteria();
		
		$fields = $view->getParamsAvailable();
		
		if(!isset($fields[$token])) {
			echo json_encode(null);
			return;
		}
		
		$field = $fields[$token];
		$oper = DevblocksSearchCriteria::OPER_EQ;
		$value = null;
		
		switch($field->type) {
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_URL:
				if(strlen($query) == 0)
					break;
					
				$oper = DevblocksSearchCriteria::OPER_LIKE;
				$value = $query;
				
				if(false === strpos($value, '*')) {
					$value .= '*';
				}

				// Parse operator hints
				if(preg_match('#([\<\>\!\=]+)(.*)#', $value, $matches)) {
					$oper_hint = trim($matches[1]);
					$value = trim($matches[2]);
					
					switch($oper_hint) {
						case '!':
						case '!=':
							if($oper == DevblocksSearchCriteria::OPER_LIKE) {
								$oper = DevblocksSearchCriteria::OPER_NOT_LIKE;
							} else {
								$oper = DevblocksSearchCriteria::OPER_NEQ;
							}
							break;
					}
				}
				
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				if(strlen($query) == 0)
					break;
				
				// Parse operator hints
				if(preg_match('#([\<\>\!\=]+)(.*)#', $query, $matches)) {
					$oper_hint = trim($matches[1]);
					$query = trim($matches[2]);
					
					switch($oper_hint) {
						case '!':
						case '!=':
							$oper = DevblocksSearchCriteria::OPER_NEQ;
							break;
						case '>':
							$oper = DevblocksSearchCriteria::OPER_GT;
							break;
						case '<':
							$oper = DevblocksSearchCriteria::OPER_LT;
							break;
						case '>=':
							$oper = DevblocksSearchCriteria::OPER_GTE;
							break;
						case '<=':
							$oper = DevblocksSearchCriteria::OPER_LTE;
							break;
						default:
							$oper = DevblocksSearchCriteria::OPER_EQ;
							break;
					}
				}
				
				$value = intval($query);
				break;
				
			case Model_CustomField::TYPE_DATE:
				if(strlen($query) == 0)
					break;
					
				$oper = DevblocksSearchCriteria::OPER_BETWEEN;
				$value = explode(' to ', $query);

				if(count($value) > 2) {
					$value = array_slice($value, 0, 2);
				}
				
				if(count($value) != 2) {
					$from = @intval(strtotime($query));
					
					if($from > time())
						array_unshift($value, 'now');
					else
						$value[] = 'now';
				}
				
				break;
				
			case Model_CustomField::TYPE_CHECKBOX:
				if(strlen($query) == 0)
					break;
				
				// Attempt to interpret bool values
				if(
					false !== stristr($query, 'yes')
					|| false !== stristr($query, 'y')
					|| false !== stristr($query, 'true')
					|| false !== stristr($query, 't')
					|| intval($query) > 0
				) {
					$oper = DevblocksSearchCriteria::OPER_EQ;
					$value = true;
				} else {
					if(substr($token,0,3) == 'cf_') {
						$oper = DevblocksSearchCriteria::OPER_EQ_OR_NULL;
					} else {
						$oper = DevblocksSearchCriteria::OPER_EQ;
					}
					$value = false;
				}
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				if(strlen($query) == 0)
					break;
				
				$oper = DevblocksSearchCriteria::OPER_IN;
					
				// Parse operator hints
				if(preg_match('#([\<\>\!\=]+)(.*)#', $query, $matches)) {
					$oper_hint = trim($matches[1]);
					$query = trim($matches[2]);
					
					switch($oper_hint) {
						case '!':
						case '!=':
							$oper = DevblocksSearchCriteria::OPER_NIN;
							break;
						default:
							$oper = DevblocksSearchCriteria::OPER_IN;
							break;
					}
				}
					
				$custom_fields = DAO_CustomField::getAll();
				$patterns = DevblocksPlatform::parseCsvString($query);
				
				list($null, $cf_id) = explode('_', $token);
				
				if(empty($cf_id) || !isset($custom_fields[$cf_id]))
					break;
				
				$options = $custom_fields[$cf_id]->options;
				
				if(empty($options))
					break;
				
				$results = array();
				
				foreach($options as $option) {
					foreach($patterns as $pattern) {
						if(isset($results[$option]))
							continue;
						
						if(0 == strcasecmp(substr($option,0,strlen($pattern)), $pattern)) {
							$results[$option] = true;
						}
					}
				}
				
				if(!empty($results)) {
					$value = array_keys($results);
				}
				break;
				
			case Model_CustomField::TYPE_WORKER:
			case 'WS': // Watchers
				$oper = DevblocksSearchCriteria::OPER_IN;
				
				// Parse operator hints
				if(preg_match('#([\<\>\!\=]+)(.*)#', $query, $matches)) {
					$oper_hint = trim($matches[1]);
					$query = trim($matches[2]);
					
					switch($oper_hint) {
						case '!':
						case '!=':
							$oper = DevblocksSearchCriteria::OPER_NIN_OR_NULL;
							
							if(empty($query)) {
								$oper = DevblocksSearchCriteria::OPER_IS_NOT_NULL;
								$value = true;
							}
							
							break;
							
						default:
							$oper = DevblocksSearchCriteria::OPER_IN;
							
							if(empty($query)) {
								$oper = DevblocksSearchCriteria::OPER_IS_NULL;
								$value = true;
							}
							
							break;
					}
				}

				if(!empty($query)) {
					$workers = DAO_Worker::getAllActive();
					$patterns = DevblocksPlatform::parseCsvString($query);
					
					$worker_names = array();
					$worker_ids = array();
					
					foreach($patterns as $pattern) {
						if(0 == strcasecmp($pattern, 'me')) {
							$worker_ids[$active_worker->id] = true;
							continue;
						}
						
						foreach($workers as $worker_id => $worker) {
							$worker_name = $worker->getName();
						
							if(isset($workers_ids[$worker_id]))
								continue;
							
							if(false !== stristr($worker_name, $pattern)) {
								$worker_ids[$worker_id] = true;
							}
						}
					}
					
					if(!empty($worker_ids)) {
						$value = array_keys($worker_ids);
					}
				}
				
				break;
				
			case 'FT':
				if(!empty($query)) {
					$oper = DevblocksSearchCriteria::OPER_FULLTEXT;
					$value = array($query, 'expert');
				}
				break;
		}
		
		if(!is_null($value)) {
			$criteria = new DevblocksSearchCriteria($token,$oper,$value);
			$view->addParam($criteria, $token);
		} else {
			$view->removeParam($token);
		}
		
		$view->renderPage = 0;
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view', $view);
			
		$html = $tpl->fetch('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
		
		echo json_encode(array(
			'status' => true,
			'html' => $html,
		));
		return;
	}
	
};