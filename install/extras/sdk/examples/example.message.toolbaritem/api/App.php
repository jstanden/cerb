<?php
class ExMessageToolbarItem extends Extension_MessageToolbarItem {
	function render(Model_Message $message) {
		$tpl = DevblocksPlatform::services()->template();
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl->assign('translate', $translate);
		$tpl->assign('message', $message); /* @var $message Model_Message */
		$tpl->display('devblocks:example.message.toolbaritem::button.tpl');
	}
};