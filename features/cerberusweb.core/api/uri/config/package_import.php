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

class PageSection_SetupPackageImport extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'package_import');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/package_import/index.tpl');
	}
	
	function importJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			$worker = CerberusApplication::getActiveWorker();
			$tpl = DevblocksPlatform::services()->template();
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			@$json_string = DevblocksPlatform::importGPC($_POST['json'],'string','');
			@$prompts = DevblocksPlatform::importGPC($_POST['prompts'],'array',[]);
			
			// If prompts weren't passed, check if we should have any
			if(!isset($_POST['prompts'])) {
				$config_prompts = CerberusApplication::packages()->prompts($json_string);
				
				// If we should have, prompt for them
				if($config_prompts) {
					$tpl->assign('prompts', $config_prompts);
					$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/package_import/prompts.tpl');
					
					echo json_encode([
						'status' => false,
						'prompts' => $html,
					]);
					return;
				}
			}
			
			$records_created = [];
			
			CerberusApplication::packages()->import($json_string, $prompts, $records_created);
			
			$tpl->assign('records_created', $records_created);
			
			$context_mfts = Extension_DevblocksContext::getAll(false);
			DevblocksPlatform::sortObjects($context_mfts, 'name', true);
			$tpl->assign('context_mfts', $context_mfts);
			
			$results_html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/package_import/results.tpl');
			
			echo json_encode([
				'status' => true,
				'message' => DevblocksPlatform::translate('success.imported'),
				'results_html' => $results_html,
			]);
			
		} catch(Exception_DevblocksValidationError $e) {
			echo json_encode(array('status' => false, 'error' => $e->getMessage()));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status' => false, 'error' => 'An unexpected error occurred.'));
			return;
		}
	}
};
