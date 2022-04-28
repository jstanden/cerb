<?php
// [TODO] Show/hide nav menu
// [TODO] Logo
// [TODO] Stylesheet

/**
 * Class PortalPage_Interaction
 */
class PortalPage_Interaction extends Extension_PortalPage {
	const ID = 'cerb.portal.page.interaction';
	
//	function invoke(array $page_meta, Model_CommunityTool $portal) {
//		return false;
//	}
	
	// [TODO] Pass a dictionary to pages?
	function render(array $page_meta, array $route_meta, Model_CommunityTool $portal) {
		$renderer = new Extension_PortalPageRenderer(function() use ($page_meta, $portal) {
			//$this->_handleInteractionStart($page);
			//var_dump($page);
			//var_dump($portal);
			//var_dump($response);
			
			/*
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($page_meta['uri'] ?? '');
			
			if($uri_parts['context_ext']->id ?? '' != CerberusContexts::CONTEXT_AUTOMATION)
				return;
			
			if(false == ($automation = DAO_Automation::getByNameAndTrigger($uri_parts['context_id'], AutomationTrigger_InteractionPortal::ID)))
				return;
			*/
			
			// [TODO] Run the automation -- potentially no continuation
			
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$session = ChPortalHelper::getSession();
			
			$interaction_uri = $page_meta['uri'] ?? '';
			$interaction_params = $page_meta['inputs'] ?? [];
			
			if(!$interaction_uri)
				return;
			
			$continuation_identifier = 'interaction:' . $page_meta['key'];
			$continuation_token = $session->getProperty($continuation_identifier);
			
			$initial_state = [];
			
			$state_data = [
				'page_meta' => $page_meta,
			];
			
			$results = $portal->getExtension()->interactionCreate($interaction_uri, $interaction_params, $initial_state, $state_data, $continuation_token);
			
			// [TODO] Are the automation results in await?
			
			list('continuation_token' => $continuation_token) = $results;
			
			if($continuation_token)
				$session->setProperty($continuation_identifier, $continuation_token);
			
			$tpl->assign('page_meta', $page_meta);
			$tpl->assign('continuation_token', $continuation_token);
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/interaction.tpl');
		});
		
		$layout = $route_meta['layout'] ?? null;
		
		// [TODO] Different layouts
		if('bare' == $layout) {
			parent::renderBareLayout($page_meta, $portal, $renderer);
		} else {
			parent::renderDefaultLayout($page_meta, $portal, $renderer);
		}
	}
}