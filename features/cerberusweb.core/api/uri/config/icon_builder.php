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

		// The agent-pane launchers for this editor: one tile per AGENT enabled on this surface. An
		// agent record's `components:` block is the only thing that says where it appears.
		$agent_toolbar_html = \Cerb\Agent\Pane\Launchers::fetch(
			'icon',
			\Cerb\Agent\Pane\Launchers::newDict('icon')
		);
		$tpl->assign('agent_toolbar_html_json', json_encode($agent_toolbar_html));

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/icon-builder/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}
}
