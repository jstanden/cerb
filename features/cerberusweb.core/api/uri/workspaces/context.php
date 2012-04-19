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

class PageSection_WorkspacesContext extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $response->path;
		@array_shift($stack); // workspaces
		@array_shift($stack); // context
		@$context_extid = array_shift($stack); // context
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context_extid, true))) { /* @var $context_ext Extension_DevblocksContext */
			$context_ext = Extension_DevblocksContext::get($context_extid);
		}

		$tpl->assign('context_ext', $context_ext);
		
		$view = $context_ext->getChooserView(); /* @var $view C4_AbstractViewModel */
		$view->name = 'Search Results';
		$view->renderFilters = false;
		
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
		
		$tpl->display('devblocks:cerberusweb.core::workspaces/page.tpl');
	}
};