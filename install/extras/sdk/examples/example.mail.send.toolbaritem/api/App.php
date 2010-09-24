<?php


class ExSendMailToolbarButton extends Extension_SendMailToolbarItem {
		function render() { 
			$tpl = DevblocksPlatform::getTemplateService();
			$translate = DevblocksPlatform::getTranslationService();
			
			$tpl->assign('translate', $translate);
			$tpl->display('devblocks:example.mail.send.toolbaritem::button.tpl');
		}
	};