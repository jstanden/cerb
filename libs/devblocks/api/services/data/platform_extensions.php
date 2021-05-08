<?php
class _DevblocksDataProviderPlatformExtensions extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions  = [
			'' => [
				'point:',
				'filter:',
				'limit:',
				'page:',
				'format:',
			],
			'format:' => [
				'dictionaries',
			],
			'point:' => array_keys(self::getExtensionPoints()),
		];
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$extension_points = self::getExtensionPoints();
		
		$chart_model = [
			'type' => 'platform.extensions',
			'point' => null,
			'filter' => null,
			'limit' => null,
			'page' => 0,
			'format' => 'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				null;
				
			} else if($field->key == 'point') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['point'] = $value;
				
			} else if($field->key == 'filter') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['filter'] = $value;
				
			} else if($field->key == 'limit') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['limit'] = intval($value);
				
			} else if($field->key == 'page') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['page'] = intval($value);
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		if(!$chart_model['point']) {
			$error = 'The `point:` parameter is required.';
			return false;
		}
		
		if(!array_key_exists($chart_model['point'], $extension_points)) {
			$error = sprintf('The `point:` parameter (%s) is not a valid extension point.', $chart_model['point']);
			return false;
		}
		
		// Data
		
		$data = [];
		$paging = [];
		
		$extensions = DevblocksPlatform::getExtensions($chart_model['point'], false);
		
		if ($chart_model['filter']) {
			$extensions = array_filter($extensions, function($extension) use ($chart_model) {
				$match = sprintf('%s %s', $extension->name, $extension->id);
				return stristr($match, $chart_model['filter']);
			});
		}
		
		foreach($extensions as $extension) {
			$data[] = [
				'id' => $extension->id,
				'name' => $extension->name,
				'class' => $extension->class,
				'plugin_id' => $extension->plugin_id,
				'params' => $extension->params,
			];
		}
		
		if($chart_model['limit']) {
			$page = $chart_model['page'] ?? 0;
			$limit = $chart_model['limit'] ?? 10;
			
			$total = count($data);
			
			$data = array_slice($data, $page * $limit, $limit);
			
			$paging = DevblocksPlatform::services()->data()->generatePaging($data, $total, $limit, $page);
		}
		
		$chart_model['data'] = $data;
		
		if($paging)
			$chart_model['paging'] = $paging;
		
		// Respond
		
		@$format = $chart_model['format'] ?: 'dictionaries';
		
		switch($format) {
			case 'dictionaries':
				return $this->_formatDataAsDictionaries($chart_model);
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: dictionaries",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsDictionaries($chart_model) {
		$meta = [
			'data' => $chart_model['data'],
			'_' => [
				'type' => $chart_model['type'],
				'format' => 'dictionaries',
			]
		];
		
		if(array_key_exists('paging', $chart_model)) {
			$meta['_']['paging'] = $chart_model['paging'];
		}
		
		return $meta;
	}
	
	// [TODO] Move this to the platform (plugin.xml)
	static function getExtensionPoints() {
		$extension_point_meta = [
			'cerb.automation.trigger' => [
				'id' => 'cerb.automation.trigger',
				'label' => 'Automation Trigger',
				'class' => 'Extension_AutomationTrigger',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.resource.type' => [
				'id' => 'cerb.resource.type',
				'label' => 'Resource Type',
				'class' => 'Extension_ResourceType',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.plugin.setup' => [
				'label' => 'Plugin Setup',
				'class' => 'Extension_PluginSetup',
				'examples' => [],
			],
			'cerb.card.widget' => [
				'label' => 'Card Widget',
				'class' => 'Extension_CardWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.custom_field' => [
				'label' => 'Custom Field Type',
				'class' => 'Extension_CustomField',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.portal' => [
				'label' => 'Portal',
				'class' => 'Extension_CommunityPortal',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.portal.page' => [
				'label' => 'Portal Page',
				'class' => 'Extension_PortalPage',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.portal.widget' => [
				'label' => 'Portal Widget',
				'class' => 'Extension_PortalWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.profile.tab' => [
				'label' => 'Profile Tab Type',
				'class' => 'Extension_ProfileTab',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.profile.tab.widget' => [
				'label' => 'Profile Widget Type',
				'class' => 'Extension_ProfileWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.connected_service.provider' => [
				'label' => 'Connected Service Provider',
				'class' => 'Extension_ConnectedServiceProvider',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.calendar.datasource' => [
				'label' => 'Calendar Datasource',
				'class' => 'Extension_CalendarDatasource',
				'examples' => [],
			],
			'cerberusweb.cron' => [
				'label' => 'Scheduled Job',
				'class' => 'CerberusCronPageExtension',
				'examples' => [],
			],
			'cerberusweb.datacenter.sensor' => [
				'label' => 'Sensor Type',
				'class' => 'Extension_Sensor',
				'examples' => [],
			],
			'cerberusweb.mail.transport' => [
				'label' => 'Mail Transport Type',
				'class' => 'Extension_MailTransport',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.page' => [
				'label' => 'Page Type',
				'class' => 'CerberusPageExtension',
				'examples' => [],
			],
			'cerberusweb.renderer.prebody' => [
				'label' => 'Prebody Renderer',
				'class' => 'Extension_AppPreBodyRenderer',
				'examples' => [],
			],
			'cerberusweb.rest.controller' => [
				'label' => 'Rest API Controller',
				'class' => 'Extension_RestController',
				'examples' => [],
			],
			'cerberusweb.ui.page.menu.item' => [
				'label' => 'Page Menu Item',
				'class' => 'Extension_PageMenuItem',
				'examples' => [],
			],
			'cerberusweb.ui.page.section' => [
				'label' => 'Page Section',
				'class' => 'Extension_PageSection',
				'examples' => [],
			],
			'cerberusweb.ui.workspace.page' => [
				'label' => 'Workspace Page Type',
				'class' => 'Extension_WorkspacePage',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.ui.workspace.tab' => [
				'label' => 'Workspace Tab Type',
				'class' => 'Extension_WorkspaceTab',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.ui.workspace.widget' => [
				'label' => 'Workspace Widget Type',
				'class' => 'Extension_WorkspaceWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.ui.workspace.widget.datasource' => [
				'label' => 'Workspace Widget Datasource',
				'class' => 'Extension_WorkspaceWidgetDatasource',
				'examples' => [],
				'extensible' => true,
			],
			'devblocks.cache.engine' => [
				'label' => 'Cache Engine',
				'class' => 'Extension_DevblocksCacheEngine',
				'examples' => [],
				'extensible' => true,
			],
			'devblocks.context' => [
				'label' => 'Record Type',
				'class' => 'Extension_DevblocksContext',
				'examples' => [],
			],
			'devblocks.controller' => [
				'label' => 'Controller',
				'class' => 'DevblocksControllerExtension',
				'examples' => [],
			],
			'devblocks.event' => [
				'label' => 'Bot Event',
				'class' => 'Extension_DevblocksEvent',
				'examples' => [],
			],
			'devblocks.event.action' => [
				'label' => 'Bot Action',
				'class' => 'Extension_DevblocksEventAction',
				'examples' => [],
			],
			'devblocks.listener.event' => [
				'label' => 'Event Listener',
				'class' => 'DevblocksEventListenerExtension',
				'examples' => [],
			],
			'devblocks.listener.http' => [
				'label' => 'Http Request Listener',
				'class' => 'DevblocksHttpResponseListenerExtension',
				'examples' => [],
			],
			'devblocks.search.engine' => [
				'label' => 'Search Engine',
				'class' => 'Extension_DevblocksSearchEngine',
				'examples' => [],
			],
			'devblocks.search.schema' => [
				'label' => 'Search Schema',
				'class' => 'Extension_DevblocksSearchSchema',
				'examples' => [],
			],
			'devblocks.storage.engine' => [
				'label' => 'Storage Engine',
				'class' => 'Extension_DevblocksStorageEngine',
				'examples' => [],
			],
			'devblocks.storage.schema' => [
				'label' => 'Storage Schema',
				'class' => 'Extension_DevblocksStorageSchema',
				'examples' => [],
			],
			'usermeet.login.authenticator' => [
				'label' => 'Support Center Login Authenticator',
				'class' => 'Extension_ScLoginAuthenticator',
				'examples' => [],
			],
			'usermeet.sc.controller' => [
				'label' => 'Support Center Controller',
				'class' => 'Extension_UmScController',
				'examples' => [],
			],
			'usermeet.sc.rss.controller' => [
				'label' => 'Support Center RSS Feed',
				'class' => 'Extension_UmScRssController',
				'examples' => [],
			],
		];
		
		DevblocksPlatform::sortObjects($extension_point_meta, '[label]');
		
		return $extension_point_meta;
	}
};