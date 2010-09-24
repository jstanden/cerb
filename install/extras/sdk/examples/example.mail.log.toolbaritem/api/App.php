<?php


class ExLogTicketToolbarButton extends Extension_LogMailToolbarItem {
		function render() { 
			$tpl = DevblocksPlatform::getTemplateService();
			$translate = DevblocksPlatform::getTranslationService();
			
			$tpl->assign('translate', $translate);
			$tpl->display('devblocks:example.mail.log.toolbaritem::button.tpl');
		}
	};