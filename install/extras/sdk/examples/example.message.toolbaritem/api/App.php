<?php
if (class_exists('Extension_MessageToolbarItem',true)):
	class ExMessageToolbarItem extends Extension_MessageToolbarItem {
		function render(Model_Message $message) {
			$tpl = DevblocksPlatform::getTemplateService();
			$translate = DevblocksPlatform::getTranslationService();
			
			$tpl->assign('translate', $translate);
			$tpl->assign('message', $message); /* @var $message Model_Message */
			$tpl->display('devblocks:example.message.toolbaritem::button.tpl');
		}
	};
endif;