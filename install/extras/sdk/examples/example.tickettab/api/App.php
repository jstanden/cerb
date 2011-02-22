<?php
if (class_exists('Extension_PreferenceTab')):
class ExTicketTab extends Extension_TicketTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.tickettab::index.tpl');		
	}
}
endif;