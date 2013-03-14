<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class ChExplorerController extends DevblocksControllerExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($worker)) {
			$query = array();
			// Must be a valid page controller
			if(!empty($request->path)) {
				if(is_array($request->path) && !empty($request->path))
					$query = array('url'=> urlencode(implode('/',$request->path)));
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
		$tpl = DevblocksPlatform::getTemplateService();

		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) {
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
			
			// Toolbar
			if(isset($meta->params['toolbar_extension_id'])) {
				$toolbar_extension_id = $meta->params['toolbar_extension_id'];
				if(!empty($toolbar_extension_id) && null !== ($toolbar_extension = DevblocksPlatform::getExtension($toolbar_extension_id, true))) {
					$tpl->assign('toolbar_extension', $toolbar_extension);
				}
			}
			
			// 20% of the time, update our timestamp
			if(3==mt_rand(1, 5)) {
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

