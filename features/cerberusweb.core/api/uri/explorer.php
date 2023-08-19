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

class ChExplorerController extends DevblocksControllerExtension {
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!CerberusApplication::getActiveWorker()) {
			if($request->is_ajax) {
				DevblocksPlatform::dieWithHttpError(null, 401);
			} else {
				$this->redirectRequestToLogin($request);
			}
		}
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;
		array_shift($stack); // explorer
		$hashset = array_shift($stack); // set
		
		if(!$hashset)
			CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
		
		// Dynamic explore
		if(40 == strlen($hashset)) {
			$this->_handleDynamicExplore($hashset);
			
		// Built-in explore
		} elseif(32 == strlen($hashset)) {
			$p = array_shift($stack) ?? 1; // item
			
			if($p != abs(intval($p)) || $p < 1)
				CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
			
			$p = DevblocksPlatform::intClamp($p, 1, PHP_INT_MAX);
			
			$this->_handleLegacyExplore($hashset, $p);
			
		} elseif('wait' == $hashset) {
			CerberusApplication::respondWithErrorReason(CerbErrorReason::NoContent);
			
		} else {
			CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
		}
	}
	
	private function _handleDynamicExplore(string $hashset) : void {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!($active_worker = CerberusApplication::getActiveWorker()))
			CerberusApplication::respondWithErrorReason(CerbErrorReason::SessionExpired);
		
		if(!($items = DAO_ExplorerSet::get($hashset, 0)))
			CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
		
		$tpl->assign('hashset', $hashset);
		
		$meta = $items[0] ?? null;
		
		if($meta && array_key_exists('interaction', $meta->params ?? [])) {
			$interaction_uri = $meta->params['interaction'];
			
			$initial_state = [
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id,
				'inputs' => $meta->params['interaction_inputs'] ?? [],
			];
			
			$continuation_token = $meta->params['interaction_continuation_token'] ?? null;
			
			if($continuation_token) {
				if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token))) {
					$continuation_token = null;
				} else {
					$initial_state = $continuation->state_data['dict'];
				}
			}
			
			if(!($automation = DAO_Automation::getByNameAndTrigger($interaction_uri, AutomationTrigger_InteractionWorkerExplore::ID)))
				CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
			
			$error = null;
			$http_method = DevblocksPlatform::getHttpMethod();
			
			// Refresh on GET, next on POST
			if($continuation_token && 'GET' == $http_method) {
				if(!($automation_results = DevblocksDictionaryDelegate::instance($initial_state)))
					CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
				
			} else {
				$initial_state['explore_hash'] = $hashset;
				
				if('POST' == DevblocksPlatform::getHttpMethod()) {
					$page = DevblocksPlatform::importGPC($_POST['page'], 'string', null);
					$initial_state['explore_page'] = $page;
				}
				
				$dict = DevblocksDictionaryDelegate::instance($initial_state);
				
				if(!($automation_results = $automation->execute($dict, $error)))
					CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
				
				$exit_code = $automation_results->getKeyPath('__exit');
				
				// If we exited in await, create or update a continuation
				if('await' == $exit_code) {
					$state_data = [
						'trigger' => AutomationTrigger_InteractionWorkerExplore::ID,
						'dict' => $automation_results->getDictionary(),
					];
					
					if($continuation_token) {
						DAO_AutomationContinuation::update($continuation_token, [
							DAO_AutomationContinuation::EXPIRES_AT => time() + 43_200, // 12 hrs
							DAO_AutomationContinuation::STATE => $automation_results->getKeyPath('__exit'),
							DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
							DAO_AutomationContinuation::UPDATED_AT => time(),
						]);
						
					} else {
						$continuation_token = DAO_AutomationContinuation::create([
							DAO_AutomationContinuation::UPDATED_AT => time(),
							DAO_AutomationContinuation::EXPIRES_AT => time() + 43_200, // 12 hrs
							DAO_AutomationContinuation::STATE => $automation_results->getKeyPath('__exit'),
							DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
							DAO_AutomationContinuation::URI => $interaction_uri,
						]);
					}
					
					$meta->params['interaction_continuation_token'] = $continuation_token;
					DAO_ExplorerSet::update($hashset, $meta->params);
				}
			}
			
			$explore_title = $automation_results->getKeyPath('__return.explore.title', 'Explore');
			$explore_url = $automation_results->getKeyPath('__return.explore.url');
			$explore_label = $automation_results->getKeyPath('__return.explore.label');
			$explore_toolbar = $automation_results->getKeyPath('__return.explore.toolbar');
			
			if(!$explore_url) {
				$explore_title = $explore_title ?: 'End of results';
				$explore_url = DevblocksPlatform::services()->url()->write('c=explore&a=wait');
			}
			
			$tpl->assign('title', $explore_title);
			$tpl->assign('url', $explore_url);
			$tpl->assign('content', $explore_label);
			
			if(is_array($explore_toolbar)) {
				foreach($explore_toolbar as $item_key => $item) {
					$item_name = DevblocksPlatform::services()->string()->strAfter($item_key, '/');
					
					if($item_name && !array_key_exists('uri', $item)) {
						$explore_toolbar[$item_key]['uri'] = 'cerb:automation:cerb.interaction.echo';
						$explore_toolbar[$item_key]['inputs'] = [
							'outputs' => [
								'explore_page' => $item_name,
							]
						];
					}
				}
				
				$toolbar_dict = DevblocksDictionaryDelegate::instance([]);
				$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($explore_toolbar, $toolbar_dict);
				$tpl->assign('toolbar', $toolbar);
			}
			
			// Common scope
			$translate = DevblocksPlatform::getTranslationService();
			$tpl->assign('translate', $translate);
			$tpl->assign('session', $_SESSION ?? []);
			$tpl->assign('active_worker', $active_worker);
			$tpl->assign('pref_dark_mode', DAO_WorkerPref::get($active_worker->id,'dark_mode',0));
			
			$tpl->display('devblocks:cerberusweb.core::explorer/automation/index.tpl');
		}
	}
	
	private function _handleLegacyExplore(string $hashset, int $p) : void {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!($active_worker = CerberusApplication::getActiveWorker()))
			CerberusApplication::respondWithErrorReason(CerbErrorReason::SessionExpired);
		
		if(!($items = DAO_ExplorerSet::get($hashset, $p)))
			CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
		
		$tpl->assign('hashset', $hashset);
		
		$total = 0;
		
		if(isset($items['0'])) {
			$meta = $items['0'];
			$total = $meta->params['total'];
			$title = $meta->params['title'] ?? '';
			$return_url = $meta->params['return_url'] ?? '';
			
			$tpl->assign('title', $title);
			$tpl->assign('count', $total);
			$tpl->assign('return_url', $return_url);
			
			// Update the access time on the first request, and no more often than every 30 seconds thereafter
			if(!isset($meta->params['last_accessed']) || $meta->params['last_accessed'] < (time()-30)) {
				$meta->params['last_accessed'] = time();
				DAO_ExplorerSet::update($hashset, $meta->params);
			}
		}
		
		if(array_key_exists($p, $items)) {
			$item = $items[$p];
			$tpl->assign('item', $item);
			
			$tpl->assign('p', $p);
			$tpl->assign('url', $item->params['url']);
			
			if(isset($item->params['content']))
				$tpl->assign('content', $item->params['content']);
			
			// Next
			if($total > $p)
				$tpl->assign('next', $p+1);
				
			// Prev
			if($p > 1)
				$tpl->assign('prev', $p-1);
			
		} else {
			CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
		}
		
		// Common scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		$tpl->assign('session', $_SESSION ?? []);
		$tpl->assign('active_worker', $active_worker);
		$tpl->assign('pref_dark_mode', DAO_WorkerPref::get($active_worker->id,'dark_mode',0));
			
		$tpl->display('devblocks:cerberusweb.core::explorer/index.tpl');
	}
};

