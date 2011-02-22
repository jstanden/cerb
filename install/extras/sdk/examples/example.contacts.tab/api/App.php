<?php
if (class_exists('Extension_AddressBookTab')):
class ExAddressBookTab extends Extension_AddressBookTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.contacts.tab::index.tpl');		
	}
}
endif;