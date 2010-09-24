<?php


class ExReplyToolbarButton extends Extension_ReplyToolbarItem {
		function render() { 
			$tpl = DevblocksPlatform::getTemplateService();
			$translate = DevblocksPlatform::getTranslationService();
			
			$tpl->assign('translate', $translate);
			$tpl->display('devblocks:example.reply.toolbaritem::button.tpl');
		}
	};