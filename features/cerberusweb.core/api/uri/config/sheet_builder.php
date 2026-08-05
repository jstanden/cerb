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

use Cerb\Sheets\SheetBuilder;

class PageSection_SetupDevelopersSheetBuilder extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$visit->set(ChConfigurationPage::ID, 'sheet_builder');

		$cfg = SheetBuilder::getClientConfig();
		$tpl->assign('column_schema_json', json_encode($cfg['columnSchema']));
		$tpl->assign('layout_schema_json', json_encode($cfg['layoutSchema']));
		$tpl->assign('datasource_schema_json', json_encode($cfg['dataSourceSchema']));
		$tpl->assign('allowed_column_types_json', json_encode($cfg['allowedColumnTypes']));
		$tpl->assign('allowed_datasource_types_json', json_encode($cfg['allowedDataSourceTypes']));
		$tpl->assign('record_types_json', json_encode($cfg['recordTypes']));
		$tpl->assign('sheet_data_automations_json', json_encode($cfg['sheetDataAutomations']));

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/sheet-builder/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}
}
