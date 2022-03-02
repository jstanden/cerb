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
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		
		// Allow quick search queries to be sent in the URL
		$query = DevblocksPlatform::importGPC($_REQUEST['q'] ?? null, 'string', '');
		
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
	
	public function invoke(string $action) {
		switch($action) {
			case 'ajaxQuickSearch':
				return $this->_pageAction_ajaxQuickSearch();
			case 'getSearchMenu':
				return $this->_pageAction_getSearchMenu();
			case 'openSearchPopup':
				return $this->_pageAction_openSearchPopup();
		}
		return false;
	}
	
	private function _pageAction_openSearchPopup() {
		$metrics = DevblocksPlatform::services()->metrics();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null,'string','');
		$query = DevblocksPlatform::importGPC($_REQUEST['q'] ?? null,'string','');
		$query_required = DevblocksPlatform::importGPC($_REQUEST['qr'] ?? null,'string','');
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null,'string',null);
		
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
			DevblocksPlatform::noop();
		} else {
			$view->setParamsQuery($query);
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
		}
		
		$aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest);
		$label = @$aliases['plural'] ?: $context_ext->manifest->name;
		$popup_title = DevblocksPlatform::translateCapitalized('common.search') . ': ' . mb_convert_case($label, MB_CASE_TITLE);
		
		// Immediately increment the search metric
		$metrics->increment(
			'cerb.record.search',
			1,
			[
				'record_type' => $aliases['uri'],
				'worker_id' => $active_worker->id,
			],
			time(),
			false
		);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('popup_title', $popup_title);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/popup.tpl');
	}
	
	private function _pageAction_ajaxQuickSearch() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$query = DevblocksPlatform::importGPC($_POST['query'] ?? null, 'string', '');
		
		header("Content-type: application/json");
		
		if (null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			/* @var $view C4_AbstractView */
			echo json_encode(null);
			return;
		}
		
		$replace_params = true;
		
		// Allow parameters to be added incrementally with a leading '+' character
		if ('+' == substr($query, 0, 1)) {
			$replace_params = false;
			$query = ltrim($query, '+ ');
		}
		
		if ($replace_params)
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
	}
	
	private function _pageAction_getSearchMenu() {
		$tpl = DevblocksPlatform::services()->template();
		
		$search_menu = Toolbar_GlobalSearch::getSearchMenu();
		$tpl->assign('interactions_menu', $search_menu);
		
		$tpl->display('devblocks:cerberusweb.core::console/bot_interactions_menu.tpl');
	}
};