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
		
		header("Content-type: application/json");
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) { /* @var $view C4_AbstractView */
			echo json_encode(null);
			return;
		}
		DAO_WorkerPref::set($active_worker->id, 'quicksearch_' . strtolower(get_class($view)), $token);
		
		$view->doQuickSearch($token, $query);
		
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