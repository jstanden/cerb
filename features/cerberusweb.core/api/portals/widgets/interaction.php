<?php
class PortalWidget_Interaction extends Extension_PortalWidget {
	const ID = 'cerb.portal.widget.interaction';
	
	public function init(array $widget_meta, array $page_meta) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$session = ChPortalHelper::getSession();
		$portal = ChPortalHelper::getPortal();
		
		$interaction_uri = $widget_meta['uri'] ?? '';
		$inputs = $widget_meta['inputs'] ?? [];
		
		// [TODO] Page, widget, route meta in the continuation
		
		$continuation_identifier = 'interaction:' . $page_meta['key'] . ':' . $widget_meta['key'];
		$continuation_token = $session->getProperty($continuation_identifier);
		
		$initial_state = [
			'inputs' => $inputs,
		];
		
		$state_data = [
			'page_meta' => $page_meta,
			'widget_meta' => $widget_meta,
		];
		
		if([] == ($results = $portal->getExtension()->interactionCreate($interaction_uri, [], $initial_state, $state_data, $continuation_token)))
			return null;
		
		['continuation_token' => $continuation_token] = $results;
		
		if($continuation_token)
			$session->setProperty($continuation_identifier, $continuation_token);
		
		$tpl->assign('widget_meta', $widget_meta);
		$tpl->assign('continuation_token', $continuation_token);
		
		return $tpl->fetch('devblocks:cerberusweb.core::portals/builder/widgets/interaction.tpl');
	}
	
	public function fetch(array $widget_meta, array $page_meta) {
	}
	
	// [TODO] Used anywhere?
	public function render(array $widget_meta, array $page_meta) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$session = ChPortalHelper::getSession();
		
		$continuation_identifier = 'interaction:' . $page_meta['key'] . ':' . $widget_meta['key'];
		$continuation_token = $session->getProperty($continuation_identifier);
		
		if(!$continuation_token)
			return null;
		
		$tpl->assign('widget_meta', $widget_meta);
		$tpl->assign('continuation_token', $continuation_token);
		
		$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/interaction.tpl');
	}
}