<?php
class ExampleScController extends Extension_UmScController {
	private $portal = '';

	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	/*
	public function handleRequest(DevblocksHttpRequest $request) {
		$path = $request->path;
		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');

		if(empty($a)) {
			@array_shift($path); // controller
			@$action = array_shift($path) . 'Action';
		} else {
			@$action = $a . 'Action';
		}

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action)); // [TODO] Pass HttpRequest as arg?
				}
				break;
		}
	}
	*/

	public function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.support_center.page::support_center/page.tpl');
	}

	public function renderSidebar(DevblocksHttpResponse $response) {
		/* Expect Overload */
		return;
	}

	public function isVisible() {
		/* Expect Overload */
		return true;
	}

	public function configure(Model_CommunityTool $instance) {
		// [TODO] Translate
		echo "This module has no configuration options.<br><br>";
	}

	public function saveConfiguration(Model_CommunityTool $instance) {
		/* Expect Overload */
	}
	
	public function ajaxMethodAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.support_center.page::support_center/ajax.tpl');
		exit;
	}
};