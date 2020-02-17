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

class PageSection_SetupDevelopersExportBots extends Extension_PageSection {
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		
		@array_shift($stack); // config
		@array_shift($stack); // export_bots
		
		$visit->set(ChConfigurationPage::ID, 'export_bots');
	
		$output = [
			'bots' => [],
		];
		
		$bots = DAO_Bot::getAll();
		
		foreach($bots as $bot) {
			$output['bots'][$bot->id] = json_decode($bot->exportToJson());
		}
		
		$bots_json = DevblocksPlatform::strFormatJson($output);
		$tpl->assign('bots_json', $bots_json);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/export-bots/index.tpl');
	}
};