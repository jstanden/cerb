<?php
if (class_exists('Extension_UsermeetTool',true)):
class ExCommunityPortal extends Extension_UsermeetTool {
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = ChPortalHelper::getSession();
		
		// Here are the most useful objects for implementing a portal:
		//var_dump(ChPortalHelper::getCode());
		//var_dump(ChPortalHelper::getSession());
		//var_dump(ChPortalHelper::getFingerprint());

		// This demonstrates how to load and save session variables
		$counter = $umsession->getProperty('counter', 1);
		$tpl->assign('counter', $counter);
		
		// This demonstrates how to save session variables
		$umsession->setProperty('counter', ++$counter);
		
		$tpl->display('devblocks:example.community_portal::portal/index.tpl');
	}
	
	public function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();
    	$portal_id = ChPortalHelper::getCode();
		
    	// This demonstrates how to load portal settings
		$properties = DAO_CommunityToolProperty::getAllByTool($portal_id);
		$tpl->assign('properties', $properties);
		
		$tpl->display('devblocks:example.community_portal::config.tpl');
	}
	
    public function saveConfiguration() {
    	$portal_id = ChPortalHelper::getCode();
    	
    	// This demonstrates how to save portal settings
        @$property = DevblocksPlatform::importGPC($_POST['property'],'string','');
        DAO_CommunityToolProperty::set($portal_id, 'property', $property);
    }
}
endif;