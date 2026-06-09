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

class PageSection_SetupDevelopersUiReference extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$visit->set(ChConfigurationPage::ID, 'ui_reference');

		// The canonical icon list (for the gallery's Icon section)
		$tpl->assign('icons_cerb', DevblocksPlatform::services()->ui()->getCerbIcons());

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/ui_reference/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'tabFragment':
					return $this->_configAction_tabFragment();
			}
		}
		return false;
	}

	// Demo-only: an HTML fragment for the gallery's dynamic (AJAX) Tabs example. Includes a nonce'd inline
	// <script> so the example can prove scripts run in genericAjaxGet-loaded panels under CSP.
	private function _configAction_tabFragment() {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$tpl = DevblocksPlatform::services()->template();
		$which = DevblocksPlatform::importGPC($_REQUEST['which'] ?? null, 'integer', 0);
		$tpl->assign('which', $which);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/ui_reference/tab_fragment.tpl');
	}
}
