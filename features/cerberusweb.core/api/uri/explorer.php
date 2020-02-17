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
		if(false == ($worker = CerberusApplication::getActiveWorker())) {
			$query = array();
			// Must be a valid page controller
			if(!empty($request->path)) {
				if(is_array($request->path) && !empty($request->path))
					$query = array('url'=> implode('/',$request->path));
			}
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'),$query));
			exit;
		}
		
		$stack = $request->path;
		array_shift($stack); // explorer
		
		@$action = array_shift($stack) . 'Action';

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
				
			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
				break;
		}
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == ($worker = CerberusApplication::getActiveWorker())) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
			exit;
		}
		
		$stack = $response->path;
		array_shift($stack); // explorer
		$hashset = array_shift($stack); // set
		@$p = array_shift($stack); // item

		if(empty($p))
			$p = 1;
		
		$items = DAO_ExplorerSet::get($hashset, array(0, $p));
		$total = 0;
		
		$tpl->assign('hashset', $hashset);
		
		if(isset($items['0'])) {
			$meta = $items['0'];
			$total = $meta->params['total'];
			$title = isset($meta->params['title']) ? $meta->params['title'] : '';
			$return_url = isset($meta->params['return_url']) ? $meta->params['return_url'] : '';
			
			$tpl->assign('title', $title);
			$tpl->assign('count', $total);
			$tpl->assign('return_url', $return_url);
			
			// Update the access time on the first request, and no more often than every 30 seconds thereafter
			if(!isset($meta->params['last_accessed']) || $meta->params['last_accessed'] < (time()-30)) {
				$meta->params['last_accessed'] = time();
				DAO_ExplorerSet::update($hashset, $meta->params);
			}
		}
		
		if(isset($items[$p])) {
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
		}
		
		// Common scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
			
		$tpl->display('devblocks:cerberusweb.core::explorer/index.tpl');
	}
};

