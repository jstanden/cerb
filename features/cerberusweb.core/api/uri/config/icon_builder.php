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

class PageSection_SetupDevelopersIconBuilder extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$visit->set(ChConfigurationPage::ID, 'icon_builder');

		// The agent.pane toolbar, scoped to this editor via {{component}} = 'icon'. Its items launch interactions
		// inline into the agent pane; the caller name must match a caller the launched automation's policy allows.
		$agent_toolbar_html = '';

		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'component' => 'icon',
			'caller_name' => 'agent.pane',
			'worker_id' => $active_worker->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
		]);

		if(($toolbar = DAO_Toolbar::getKataByName('agent.pane', $toolbar_dict)))
			$agent_toolbar_html = DevblocksPlatform::services()->ui()->toolbar()->fetch($toolbar);

		$tpl->assign('agent_toolbar_html_json', json_encode($agent_toolbar_html));

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/icon-builder/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}
}
