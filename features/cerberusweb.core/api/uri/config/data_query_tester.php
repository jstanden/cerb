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

class PageSection_SetupDevelopersDataQueryTester extends Extension_PageSection {
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		
		@array_shift($stack); // config
		@array_shift($stack); // data_queries
		
		$visit->set(ChConfigurationPage::ID, 'data_query_tester');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/data-query-tester/index.tpl');
	}
	
	function runQueryAction() {
		@$data_query = DevblocksPlatform::importGPC($_REQUEST['data_query'], 'string', null);
		
		$tpl = DevblocksPlatform::services()->template();
		$data = DevblocksPlatform::services()->data();
		$error = null;
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(false === (@$results = $data->executeQuery($data_query, $error, 0))) {
			echo json_encode([
				'status' => false,
				'error' => $error,
			]);
			
		} else {
			$tpl->assign('results_json', DevblocksPlatform::strFormatJson(json_encode($results)));
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/data-query-tester/results.tpl');
			
			echo json_encode([
				'status' => true,
				'html' => $html,
			]);
		}
	}
};