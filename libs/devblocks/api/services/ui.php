<?php
class _DevblocksUiManager {
	private static $instance = null;
		
		private function __construct() {}
	
	/**
	 * @return _DevblocksUiManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksUiManager();
		}
		return self::$instance;
	}
	
	/**
	 * @return DevblocksUiEventHandler
	 */
	public function eventHandler() {
		return new DevblocksUiEventHandler();
	}
	
	/**
	 * @return DevblocksUiMap
	 */
	public function map() {
		return new DevblocksUiMap();
	}
	
	/**
	 * @return DevblocksUiToolbar
	 */
	public function toolbar() {
		return new DevblocksUiToolbar();
	}
	
	/**
	 * @param string $uri
	 * @return array|false
	 */
	function parseURI(string $uri) {
		if(!DevblocksPlatform::strStartsWith($uri, 'cerb:'))
			return false;
		
		$uri_parts = explode(':', $uri);
		
		if(3 !== count($uri_parts))
			return false;
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($uri_parts[1])))
			return false;
		
		return [
			'context' => $context_ext->id,
			'context_id' => $uri_parts[2],
			'context_ext' => $context_ext,
		];
	}
}

class DevblocksUiEventHandler {
	function parse(?string $handlers_kata, DevblocksDictionaryDelegate $dict, &$error=null) {
		if(is_null($handlers_kata))
			return [];
		
		$kata = DevblocksPlatform::services()->kata();
		
		$results = [];
		
		if(false == ($handlers = $kata->parse($handlers_kata, $error)))
			return [];
		
		$handlers = $kata->formatTree($handlers, $dict);
		
		foreach($handlers as $handler_key => $handler_data) {
			if(array_key_exists('disabled', $handler_data) && $handler_data['disabled'])
				continue;
			
			@list($handler_type, $handler_name) = explode('/', $handler_key, 2);
			
			$result = [
				'type' => $handler_type,
				'key' => $handler_name,
				'data' => $handler_data,
			];
			
			$results[$handler_name] = $result;
		}
		
		return $results;
	}
	
	function handleOnce(string $trigger, array $handlers, array $initial_state, &$error=null, callable $behavior_callback=null, &$handler=null) {
		$automator = DevblocksPlatform::services()->automation();
		
		foreach($handlers as $handler) {
			if('automation' == @$handler['type']) {
				$automation_uri = @$handler['data']['uri'];
				
				// Handle `uri:`
				if(DevblocksPlatform::strStartsWith($automation_uri, 'cerb:')) {
					if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($automation_uri)))
						continue;
					
					$automation_uri = $uri_parts['context_id'];
				}
				
				if(false == ($automation = DAO_Automation::getByNameAndTrigger($automation_uri, $trigger)))
					continue;
				
				if(array_key_exists('inputs', @$handler['data']))
					$initial_state['inputs'] = $handler['data']['inputs'];
				
				if(false == ($automation_results = $automator->executeScript($automation, $initial_state, $error)))
					return null;
				
				$handler = $automation;
				
				return $automation_results;
				
			// @deprecated
			} elseif('behavior' == @$handler['type']) {
				if(is_callable($behavior_callback)) {
					@$behavior_uri = $handler['data']['uri'];
					
					if(DevblocksPlatform::strStartsWith($behavior_uri, 'cerb:')) {
						if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($behavior_uri)))
							continue;
						
						$behavior_uri = $uri_parts['context_id'];
					}
					
					$behavior = null;
					
					if(is_numeric($behavior_uri)) {
						$behavior = DAO_TriggerEvent::get($behavior_uri);
					} elseif(is_string($behavior_uri)) {
						$behavior = DAO_TriggerEvent::getByUri($behavior_uri);
					}
					
					if($behavior instanceof Model_TriggerEvent) {
						$handler = $behavior;
						return $behavior_callback($behavior, $handler);
					}
					
					return false;
				}
			}
		}
		
		return null;
	}
	
	function handleEach(string $trigger, array $handlers, $initial_state, &$error=null, callable $behavior_callback=null) {
		$results = [];
		
		// [TODO] Preload automations?
		// [TODO] Can these forward propagate to communicate with each other? (e.g. mail rules)
		
		foreach($handlers as $handler_key => $handler) {
			$result = $this->handleOnce($trigger, [$handler], $initial_state, $error, $behavior_callback);
			
			if(is_null($result))
				continue;
			
			$results[$handler_key] = $result;
		}
		
		return $results;
	}
}

class DevblocksUiMap {
	function parse($kata, DevblocksDictionaryDelegate $dict, &$error=null) {
		$map = [
			'resource' => [
				'uri' => 'cerb:resource:map.world.countries',
			],
			'projection' => [
				'type' => 'mercator',
				'scale' => 90,
				'center' => [
					'longitude' => 0,
					'latitude' => 25,
				]
			],
		];
		
		if(is_array($kata)) {
			$map_data = $kata;
			unset($kata);
			
		} elseif (is_string($kata)) {
			if(false === ($map_data = DevblocksPlatform::services()->kata()->parse($kata, $error))) {
				return false;
			}
		}
		
		if(is_array($map_data) && array_key_exists('map', $map_data)) {
			$map_data = DevblocksPlatform::services()->kata()->formatTree($map_data, $dict);
			$map = array_merge($map, $map_data['map'] ?? []);
			unset($map_data);
		}
		
		$resource_keys = [];
		
		if(@$map['resource']['uri']) {
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($map['resource']['uri']);
			$resource_keys[] = $uri_parts['context_id'];
			$map['resource']['uri'] = $uri_parts['context_id'];
		}
		
		if(@$map['regions']['properties']['resource']['uri']) {
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($map['regions']['properties']['resource']['uri']);
			$resource_keys[] = $uri_parts['context_id'];
			$map['regions']['properties']['resource']['uri'] = $uri_parts['context_id'];
		}
		
		if(@$map['points']['resource']['uri']) {
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($map['points']['resource']['uri']);
			$resource_keys[] = $uri_parts['context_id'];
			$map['points']['resource']['uri'] = $uri_parts['context_id'];
		}
		
		$resources = DAO_Resource::getByNames($resource_keys);
		$resources = array_combine(array_column($resources, 'name'), $resources);
		
		if(@$map['resource']['uri']) {
			if (false != ($resource = @$resources[$map['resource']['uri']])) {
				$map['resource']['name'] = $resource->name;
				$map['resource']['size'] = $resource->storage_size;
				$map['resource']['updated_at'] = $resource->updated_at;
			}
		}
		
		if(@$map['regions']['properties']['resource']['uri']) {
			if(false != ($regions_resource = @$resources[$map['regions']['properties']['resource']['uri']])) {
				$map['regions']['properties']['resource']['name'] = $regions_resource->name;
				$map['regions']['properties']['resource']['size'] = $regions_resource->storage_size;
				$map['regions']['properties']['resource']['updated_at'] = $regions_resource->updated_at;
			}
		}
		
		if(@$map['points']['resource']['uri']) {
			if(false != ($points_resource = @$resources[$map['points']['resource']['uri']])) {
				$map['points']['resource']['name'] = $points_resource->name;
				$map['points']['resource']['size'] = $points_resource->storage_size;
				$map['points']['resource']['updated_at'] = $points_resource->updated_at;
			}
		}
		
		return $map;
	}
	
	function render($map, $widget=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		// Manual region properties
		if(@$map['regions']['properties']['data']) {
			if(is_array($map['regions']['properties']['data'])) {
				$region_properties = $map['regions']['properties']['data'];
				$tpl->assign('region_properties_json', json_encode($region_properties));
			}
		}
		
		// Manual points
		if(@$map['points']['data']) {
			$points = [
				'type' => 'FeatureCollection',
				'features' => []
			];
			
			foreach($map['points']['data'] as $point) {
				if(!is_array($point))
					continue;
				
				if(!array_key_exists('longitude', $point) || !array_key_exists('latitude', $point))
					continue;
				
				$points['features'][] = [
					'type' => 'Feature',
					'properties' => $point['properties'],
					'geometry' => [
						'type' => 'Point',
						'coordinates' => [
							$point['longitude'],
							$point['latitude']
						]
					]
				];
			}
			
			$tpl->assign('points_json', json_encode($points));
		}
		
		$tpl->assign('widget', $widget);
		
		if($map) {
			$tpl->assign('map', $map);
			$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_regions.tpl');
		}		
	}
}

class DevblocksUiToolbar {
	function parse($kata, DevblocksDictionaryDelegate $dict) {
		$error = null;
		$kata_tree = null;
		
		if(!$kata)
			return [];
		
		if(is_array($kata)) {
			$kata_tree = $kata;
			unset($kata);
			
		} elseif (is_string($kata)) {
			if(false === ($kata_tree = DevblocksPlatform::services()->kata()->parse($kata, $error))) {
				return false;
			}
		}
		
		if(!is_array($kata_tree))
			return [];
		
		$kata_tree = DevblocksPlatform::services()->kata()->formatTree($kata_tree, $dict);
		
		if(!is_array($kata_tree))
			return [];
		
		$results = [];
		
		foreach($kata_tree as $toolbar_item_key => $toolbar_item) {
			if(!is_array($toolbar_item))
				continue;
			
			@list($type, $key) = explode('/', $toolbar_item_key);
			
			if(!$key)
				continue;
			
			if(in_array($type, ['interaction'])) {
				if(!array_key_exists('uri', $toolbar_item))
					continue;
				
				if(DevblocksPlatform::strStartsWith($toolbar_item['uri'], 'cerb:')) {
					if(false != ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($toolbar_item['uri']))) {
						$toolbar_item['uri'] = $uri_parts['context_id'];
					}
				}
			}
			
			$toolbar_item['key'] = $key;
			$toolbar_item['type'] = $type;
			
			$results[$key] = $toolbar_item;
		}
		
		$automations = $this->extractUris($results);
		$this->enforceCallerPolicy($results, $automations, $dict);
		
		return $results;
	}
	
	private function extractUris($results) {
		$uris = [];
		
		array_walk_recursive($results, function($v, $k) use (&$uris) {
			if('uri' == $k)
				$uris[$v] = true;
		});
		
		$automations = DAO_Automation::getByUris(array_keys($uris));
		
		if(!is_array($automations))
			return [];
		
		return array_combine(array_column($automations, 'name'), $automations);
	}
	
	private function enforceCallerPolicy(&$node, $automations, DevblocksDictionaryDelegate $dict) {
		if(is_array($node) && array_key_exists('type', $node)) {
			if('interaction' === $node['type']) {
				// If no URI
				if(!array_key_exists('uri', $node)) {
					$node['hidden'] = true;
					return;
				}
				
				// If no automation
				if(false == ($automation = @$automations[$node['uri']])) {
					$node['hidden'] = true;
					return;
				}
				
				/* @var $automation Model_Automation */
				$policy = $automation->getPolicy();
				
				$toolbar_caller = $dict->get('caller_name');
				
				if(!$policy->isCallerAllowed($toolbar_caller, $dict)) {
					$node['hidden'] = true;
					return;
				}
				
			} else if('menu' === $node['type']) {
				foreach(@$node['items'] as &$n) {
					$this->enforceCallerPolicy($n, $automations, $dict);
				}
			}
			
		} elseif(is_array($node)) {
			foreach($node as &$n) {
				$this->enforceCallerPolicy($n, $automations, $dict);
			}
		}
	}
	
	function render($toolbar) {
		if(!is_array($toolbar))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('toolbar', $toolbar);
		$tpl->display('devblocks:devblocks.core::ui/toolbar/render.tpl');
	}
}