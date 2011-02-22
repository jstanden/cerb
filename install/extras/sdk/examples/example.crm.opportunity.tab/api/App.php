<?php
if (class_exists('Extension_CrmOpportunityTab')):
class ExCrmOppTab extends Extension_CrmOpportunityTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.crm.opportunity.tab::index.tpl');		
	}
}
endif;