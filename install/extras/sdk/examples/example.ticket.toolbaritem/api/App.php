<?php
if (class_exists('Extension_TicketToolbarItem',true)):
	class ExTicketToolbarItem extends Extension_TicketToolbarItem {
		function render(Model_Ticket $ticket) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$translate = DevblocksPlatform::getTranslationService();
			
			$tpl->assign('translate', $translate);
			$tpl->assign('ticket', $ticket); /* @var $message Model_Ticket */			
			$tpl->display('devblocks:example.ticket.toolbaritem::button.tpl');
		}
	};
endif;