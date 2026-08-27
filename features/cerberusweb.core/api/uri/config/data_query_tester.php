<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

class PageSection_SetupDevelopersDataQueryTester extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // data_queries
		
		$visit->set(ChConfigurationPage::ID, 'data_query_tester');

		// The agent-pane launchers for this editor: one tile per AGENT enabled on this surface. An
		// agent record's `components:` block is the only thing that says where it appears.
		$agent_toolbar_html = \Cerb\Agent\Pane\Launchers::fetch(
			'data_query',
			\Cerb\Agent\Pane\Launchers::newDict('data_query')
		);
		$tpl->assign('agent_toolbar_html_json', json_encode($agent_toolbar_html));

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/data-query-tester/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}
}