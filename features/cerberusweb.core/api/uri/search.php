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

class Page_Search extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == (CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function handleSectionActionAction() {
		// GET has precedence over POST
		@$section_uri = DevblocksPlatform::importGPC(isset($_GET['section']) ? $_GET['section'] : $_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		
		// Allow quick search queries to be sent in the URL
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'], 'string', '');
		
		$stack = $response->path;
		@array_shift($stack); // search
		@$context_extid = array_shift($stack); // context
		
		if(empty($context_extid))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context_extid, true))) { /* @var $context_ext Extension_DevblocksContext */
			return;
		}
		
		if(!$context_ext->hasOption('search') && !$context_ext->hasOption('workspace'))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		if(false == ($view = $context_ext->getSearchView())) /* @var $view C4_AbstractView */
			return;
		
		// Quick search initialization
		
		if(!empty($query)) {
			$view->setParamsQuery($query);
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
		}
		
		// Template
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/index.tpl');
	}
	
	function openSearchPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
		@$query_required = DevblocksPlatform::importGPC($_REQUEST['qr'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string',null);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		// Verify that this context is publicly searchable
		if(!$context_ext->hasOption('workspace'))
			return;
		
		if(false == ($view = $context_ext->getSearchView($id)) || !($view instanceof IAbstractView_QuickSearch))
			return;
		
		if($id)
			$view->is_ephemeral = true;
		
		$view->setParamsRequiredQuery($query_required);
		
		if('*' == $query) {
			$query = $view->getParamsQuery();
		} else {
			$view->setParamsQuery($query);
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
		}
		
		$aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest);
		$label = @$aliases['plural'] ?: $context_ext->manifest->name;
		$popup_title = DevblocksPlatform::translateCapitalized('common.search') . ': ' . mb_convert_case($label, MB_CASE_TITLE);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('popup_title', $popup_title);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/popup.tpl');
	}
	
	function ajaxQuickSearchAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$query = DevblocksPlatform::importGPC($_POST['query'],'string','');

		header("Content-type: application/json");
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) { /* @var $view C4_AbstractView */
			echo json_encode(null);
			return;
		}
		
		$replace_params = true;
		
		// Allow parameters to be added incrementally with a leading '+' character
		if('+' == substr($query,0,1)) {
			$replace_params = false;
			$query = ltrim($query, '+ ');
		}
		
		if($replace_params)
			$view->setParamsQuery($query);
		
		$view->addParamsWithQuickSearch($query, $replace_params);
		$view->renderPage = 0;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view', $view);
		
		$html = $tpl->fetch('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
		
		echo json_encode(array(
			'status' => true,
			'html' => $html,
		));
		return;
	}
	
};