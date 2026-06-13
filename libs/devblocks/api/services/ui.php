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
	public function eventHandler() : DevblocksUiEventHandler {
		return new DevblocksUiEventHandler();
	}
	
	/**
	 * @return DevblocksUiMap
	 */
	public function map() {
		return new DevblocksUiMap();
	}
	
	/**
	 * @return DevblocksUiMenu
	 */
	public function menu() {
		return new DevblocksUiMenu();
	}
	
	/**
	 * @return DevblocksUiToolbar
	 */
	public function toolbar() {
		return new DevblocksUiToolbar();
	}

	// The canonical list of `cerb-icons` glyph names. Single source of truth for the UI Reference
	// gallery, KATA icon autocomplete, sheet `icon()` cells, and the `ui_icons` data query.
	function getCerbIcons($limit=null, $page=0, $filter=null, &$paging=[]) : array {
		$icons = [
			'academic-cap',
			'adjust',
			'alert',
			'antenna',
			'archive',
			'ban',
			'bell',
			'bold',
			'book',
			'book-open',
			'bookmark',
			'bot',
			'bot-message',
			'branch',
			'bug',
			'building-apartments',
			'building-gov',
			'building-house',
			'building-office',
			'calendar',
			'camera',
			'chart-area',
			'chart-axis-x',
			'chart-axis-y',
			'chart-axis-y2',
			'chart-bar',
			'chart-bar-stacked',
			'chart-line',
			'chart-scatterplot',
			'check',
			'checked',
			'chevron-down',
			'chevron-left',
			'chevron-right',
			'chevron-up',
			'circle-arrow-down',
			'circle-arrow-left',
			'circle-arrow-right',
			'circle-arrow-up',
			'circle-exclamation-mark',
			'circle-info',
			'circle-minus',
			'circle-ok',
			'circle-plus',
			'circle-question-mark',
			'circle-remove',
			'clipboard',
			'clock',
			'cloud',
			'cloud-download',
			'cloud-upload',
			'color-palette',
			'comments',
			'compass',
			'computer-desktop',
			'computer-laptop',
			'computer-tablet',
			'console',
			'conversation',
			'copy',
			'crosshairs',
			'cube',
			'dashboard',
			'database',
			'dequeue',
			'dice',
			'disk-export',
			'disk-save',
			'down-arrow',
			'download',
			'duplicate',
			'edit',
			'embed',
			'enqueue',
			'erase',
			'eye-close',
			'eye-open',
			'face-frown',
			'face-neutral',
			'face-smile',
			'fast-backward',
			'fast-forward',
			'file',
			'file-document',
			'file-export',
			'file-image',
			'file-import',
			'file-zip',
			'flag',
			'folder',
			'folder-open',
			'folder-plus',
			'funnel',
			'gear',
			'gender-female',
			'gender-male',
			'gift',
			'globe',
			'hammer',
			'hash',
			'header',
			'heart',
			'hierarchy',
			'history',
			'hourglass',
			'id-card',
			'inbox',
			'italic',
			'lab',
			'left-arrow',
			'light-bulb',
			'link',
			'list',
			'location',
			'lock',
			'magic',
			'mail',
			'mail-lock',
			'map',
			'megaphone',
			'mention',
			'menu-hamburger',
			'merge',
			'microphone',
			'minus',
			'mobile',
			'moon',
			'more',
			'more-vertical',
			'move',
			'move-horizontal',
			'move-vertical',
			'new-window',
			'nodes',
			'paintbrush',
			'paperclip',
			'paste',
			'pause',
			'pen',
			'phone-handset',
			'phone-headset',
			'picture',
			'placeholders',
			'play',
			'play-button',
			'plug',
			'plus',
			'print',
			'pushpin',
			'qr-code',
			'quote',
			'refresh',
			'remove',
			'repeat',
			'resize-full',
			'resize-small',
			'restart',
			'return',
			'right-arrow',
			'save',
			'search',
			'send',
			'share',
			'shield',
			'sign-out',
			'signal',
			'sort-asc',
			'sort-desc',
			'sparkle',
			'sparkles',
			'spinner',
			'star',
			'step-backward',
			'step-forward',
			'stop',
			'stopwatch',
			'sun',
			'table',
			'tag',
			'tags',
			'target',
			'telescope',
			'text',
			'text-color',
			'text-size',
			'thumbs-down',
			'thumbs-up',
			'ticket',
			'todo',
			'toolbox',
			'transfer',
			'translate',
			'trash',
			'trophy',
			'unchecked',
			'unlock',
			'up-arrow',
			'upload',
			'user',
			'user-lock',
			'users',
			'wifi',
			'window-bottom',
			'window-left',
			'window-right',
			'window-top',
			'wrench',
			'zap',
			'zoom-in',
			'zoom-out',
		];

		if($filter) {
			$icons = array_filter($icons, function($icon) use ($filter) {
				return stristr($icon, $filter);
			});
		}

		if($limit) {
			$total = count($icons);

			$icons = array_splice($icons, $page*$limit, $limit);

			$paging = DevblocksPlatform::services()->data()->generatePaging($icons, $total, $limit, $page);
		}

		return $icons;
	}

	/**
	 * @param string $uri
	 * @return array|false
	 */
	function parseURI(?string $uri) {
		if(!DevblocksPlatform::strStartsWith($uri, 'cerb:'))
			return false;
		
		$uri_parts = explode(':', $uri);
		
		// Must have a length of 2 (context) or 3 (context:id)
		if(!in_array(count($uri_parts), [2,3]))
			return false;
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($uri_parts[1])))
			return false;
		
		return [
			'context' => $context_ext->id,
			'context_id' => $uri_parts[2] ?? 0,
			'context_ext' => $context_ext,
		];
	}
}

class DevblocksUiEventHandler {
	function parse(?string $handlers_kata, DevblocksDictionaryDelegate $dict, &$error=null, $is_strict=true) {
		if(is_null($handlers_kata))
			return [];
		
		$kata = DevblocksPlatform::services()->kata();
		$kata->setStrictMode($is_strict);
		
		$results = [];
		$symbols_meta= [];
		
		if(false === ($handlers = $kata->parse($handlers_kata, $error, true, $symbols_meta)))
			return false;
		
		if(false === ($handlers = $kata->formatTree($handlers, $dict, $error)))
			return false;
		
		foreach($handlers as $handler_key => $handler_data) {
			if(!is_array($handler_data) || !$this->_isHandlerEnabled($handler_data))
				continue;
			
			list($handler_type, $handler_name) = array_pad(explode('/', $handler_key, 2), 2, null);
			
			$line = $symbols_meta[$handler_key] ?? -1;
			
			$result = [
				'id' => $handler_key,
				'type' => $handler_type,
				'key' => $handler_name,
				'data' => $handler_data,
				'kata' => [
					'line' => ++$line,
				]
			];
			
			$results[$handler_name] = $result;
		}
		
		return $results;
	}
	
	private function _isHandlerEnabled(array $handler_data) : bool {
		foreach($handler_data as $k => $v) {
			$key_type = DevblocksPlatform::strLower(DevblocksPlatform::services()->string()->strBefore($k, '/'));
			
			if(in_array($key_type, ['enabled', 'disabled'])) {
				if(!is_bool($v))
					$v = DevblocksPlatform::services()->string()->toBool($v);
				
				if($key_type == 'enabled' && $v)
					return true;
				
				if($key_type == 'disabled' && $v)
					return false;
			}
		}
		
		return true;
	}
	
	function handleOnce($triggers, array $handlers, array $initial_state, &$error=null, ?callable $behavior_callback=null, &$handler=null) {
		if(is_string($triggers))
			$triggers = [$triggers];
		
		if(!is_array($triggers) || empty($triggers))
			return null;

		$automator = DevblocksPlatform::services()->automation();
		
		foreach($handlers as $handler) {
			$started_ms = microtime(true);
			
			if('automation' == ($handler['type'] ?? null)) {
				$automation_uri = $handler['data']['uri'] ?? null;
				
				// Handle `uri:`
				if(DevblocksPlatform::strStartsWith($automation_uri, 'cerb:')) {
					if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($automation_uri)))
						continue;
					
					$automation_uri = $uri_parts['context_id'];
				}
				
				if(!($automation = DAO_Automation::getByNameAndTrigger($automation_uri, $triggers)))
					continue;
				
				if(array_key_exists('inputs', $handler['data'] ?? []))
					$initial_state['inputs'] = $handler['data']['inputs'];
				
				if(!($automation_results = $automator->executeScript($automation, $initial_state, $error)))
					return null;
				
				$automation_results->setKeyPath('__handler', $handler['id'] ?? null);
				$automation_results->setKeyPath('__handler_uri', $handler['data']['uri'] ?? null);
				$automation_results->setKeyPath('__handler_duration_ms', (microtime(true) - $started_ms) * 1000);
				
				$handler = $automation;
				
				return $automation_results;
				
			// @deprecated
			} elseif('behavior' == @$handler['type']) {
				if(!DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy'))
					continue;
				
				if(is_callable($behavior_callback)) {
					$behavior_uri = $handler['data']['uri'] ?? null;
					
					if(DevblocksPlatform::strStartsWith($behavior_uri, 'cerb:')) {
						if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($behavior_uri)))
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
						$behavior_results = $behavior_callback($behavior, $handler);
						
						if($behavior_results instanceof DevblocksDictionaryDelegate) {
							$behavior_results->setKeyPath('__handler', $handler['id'] ?? null);
							$behavior_results->setKeyPath('__handler_uri', $handler['data']['uri'] ?? null);
							$behavior_results->setKeyPath('__handler_duration_ms', (microtime(true) - $started_ms) * 1000);
						}
						
						return $behavior_results;
					}
					
					return false;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * @param array|string $triggers
	 * @param array $handlers
	 * @param array $initial_state
	 * @param null $error
	 * @param null $behavior_callback
	 * @return DevblocksDictionaryDelegate|null
	 */
	function handleUntilReturn($triggers, array $handlers, array $initial_state, &$error=null, $behavior_callback=null) : ?DevblocksDictionaryDelegate {
		if(is_string($triggers))
			$triggers = [$triggers];
		
		if(!is_array($triggers) || empty($triggers))
			return null;
		
		// Loop handlers until one exits as return
		$results = $this->handleEach(
			$triggers,
			$handlers,
			$initial_state,
			$error,
			function(DevblocksDictionaryDelegate $result) {
				return 'return' !== $result->getKeyPath('__exit');
			},
			$behavior_callback
		);
		
		// If no results, abort
		if(null == ($result = array_pop($results)))
			return null;
		
		// If the final automation exited as `return`, return it
		if('return' === $result->getKeyPath('__exit'))
			return $result;
		
		// Otherwise null
		return null;
	}
	
	function handleEach($triggers, array $handlers, array $initial_state, &$error=null, ?callable $continue_callback=null, ?callable $behavior_callback=null) : array {
		if(is_string($triggers))
			$triggers = [$triggers];
		
		if(!is_array($triggers) || empty($triggers))
			return [];

		$results = [];
		
		// By default, always continue through all handlers
		if(is_null($continue_callback))
			$continue_callback = fn(DevblocksDictionaryDelegate $result, array $handler, array &$initial_state) => true;
		
		// [TODO] Preload automations?
		
		foreach($handlers as $handler_key => $handler) {
			$result = $this->handleOnce($triggers, [$handler], $initial_state, $error, $behavior_callback);
			
			if(!($result instanceof DevblocksDictionaryDelegate))
				continue;
			
			$results[$handler_key] = $result;
			
			if(is_callable($continue_callback)) {
				// Does the callback say to exit?
				if(!$continue_callback($result, $handler, $initial_state))
					return $results;
			}
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
		
		if(null != ($map['resource']['uri'] ?? null)) {
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($map['resource']['uri']);
			$resource_keys[] = $uri_parts['context_id'];
			$map['resource']['uri'] = $uri_parts['context_id'];
		}
		
		if(null != ($map['regions']['properties']['resource']['uri'] ?? null)) {
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($map['regions']['properties']['resource']['uri']);
			$resource_keys[] = $uri_parts['context_id'];
			$map['regions']['properties']['resource']['uri'] = $uri_parts['context_id'];
		}
		
		if(null != ($map['points']['resource']['uri'] ?? null)) {
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($map['points']['resource']['uri']);
			$resource_keys[] = $uri_parts['context_id'];
			$map['points']['resource']['uri'] = $uri_parts['context_id'];
		}
		
		$resources = DAO_Resource::getByNames($resource_keys);
		$resources = array_combine(array_column($resources, 'name'), $resources);
		
		if(null != ($map['resource']['uri'] ?? null)) {
			if (false != ($resource = @$resources[$map['resource']['uri']])) {
				$map['resource']['name'] = $resource->name;
				$map['resource']['size'] = $resource->storage_size;
				$map['resource']['updated_at'] = $resource->updated_at;
			}
		}
		
		if(null != ($map['regions']['properties']['resource']['uri'] ?? null)) {
			if(false != ($regions_resource = @$resources[$map['regions']['properties']['resource']['uri']])) {
				$map['regions']['properties']['resource']['name'] = $regions_resource->name;
				$map['regions']['properties']['resource']['size'] = $regions_resource->storage_size;
				$map['regions']['properties']['resource']['updated_at'] = $regions_resource->updated_at;
			}
		}
		
		if(null != ($map['points']['resource']['uri'] ?? null)) {
			if(false != ($points_resource = ($resources[$map['points']['resource']['uri']] ?? null))) {
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
		if(null != ($map['regions']['properties']['data'] ?? null)) {
			if(is_array($map['regions']['properties']['data'])) {
				$region_properties = $map['regions']['properties']['data'];
				$tpl->assign('region_properties_json', json_encode($region_properties));
			}
		}
		
		// Manual points
		if(null != ($map['points']['data'] ?? null)) {
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

class DevblocksUiMenu {
	public function parse($labels, string $label_separator=' ', bool $condense=true) : array {
		$labels_tokenized = array_map(
			fn($label) => explode($label_separator, $label), // DevblocksPlatform::strTitleCase()
			$labels
		);
		
		$labels_tree = new DevblocksMenuItemPlaceholder();
		
		// Start at the root and branch until a new leaf is reached
		foreach($labels_tokenized as $label_key => $label_parts) {
			$ptr =& $labels_tree;
			$label_chain = '';
			
			foreach($label_parts as $label_part) {
				$label_chain .= ($label_chain ? $label_separator : '') . $label_part;
				
				if(!array_key_exists($label_part, $ptr->children)) {
					$item = new DevblocksMenuItemPlaceholder();
					$item->label = $label_chain;
					$item->l = $label_part;
					$ptr->children[$label_part] = $item;
				}
				
				$ptr =& $ptr->children[$label_part];
			}
			
			$ptr->key = $label_key;
		}
		
		if($condense) {
			foreach($labels_tree->children as $child)
				$this->_condenseTree($child, $label_separator);

			$labels_tree->children = array_combine(
				array_map(fn($child) => $child->l, $labels_tree->children),
				$labels_tree->children,
			);
		}

		return $labels_tree->children;
	}

	// Collapse node chains with a single child between them
	private function _condenseTree(DevblocksMenuItemPlaceholder $node, string $label_separator=' ') : void {
		foreach($node->children as $child) {
			$this->_condenseTree($child, $label_separator);
		}
		
		// Re-key any condensed children
		$node->children = array_combine(
			array_map(fn($child) => $child->l, $node->children),
			$node->children,
		);

		while(count($node->children) === 1) {
			$child = array_shift($node->children);
			$node->key = $child->key;
			$node->label = $child->label;
			$node->l .= $label_separator . $child->l;
			$node->children = $child->children;
		}
	}
}

class DevblocksUiToolbar {
	function parse($kata, DevblocksDictionaryDelegate $dict, &$error=null) {
		$kata_tree = null;
		$symbol_meta = [];
		
		if(!$kata)
			return [];
		
		if(is_array($kata)) {
			$kata_tree = $kata;
			unset($kata);
			
		} elseif (is_string($kata)) {
			if(false === ($kata_tree = DevblocksPlatform::services()->kata()->parse($kata, $error, true, $symbol_meta))) {
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
			
			list($type, $key) = array_pad(explode('/', $toolbar_item_key), 2, null);
			
			if(!$key)
				continue;
			
			if('interaction' == $type) {
				if(!array_key_exists('uri', $toolbar_item))
					continue;
				
				if(DevblocksPlatform::strStartsWith($toolbar_item['uri'], 'cerb:')) {
					if(($uri_parts = DevblocksPlatform::services()->ui()->parseURI($toolbar_item['uri']))) {
						$toolbar_item['uri'] = $uri_parts['context_id'];
					}
				}
				
			} elseif('behavior' == $type) {
				if(!array_key_exists('id', $toolbar_item))
					continue;
			}
			
			$line = $symbol_meta[$toolbar_item_key] ?? -1;
			
			$toolbar_item['key'] = $key;
			$toolbar_item['type'] = $type;
			$toolbar_item['kata'] = [
				'line' => ++$line,
			];
			
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
				if(!($automation = ($automations[$node['uri']] ?? null))) {
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
				
			} else if('menu' === $node['type'] && array_key_exists('items', $node)) {
				foreach(@$node['items'] as &$n) {
					$this->enforceCallerPolicy($n, $automations, $dict);
				}
				unset($n);
			}
			
		} elseif(is_array($node)) {
			foreach($node as &$n) {
				$this->enforceCallerPolicy($n, $automations, $dict);
			}
			unset($n);
		}
	}
	
	function fetch($toolbar, $interaction_class='cerb-bot-trigger') {
		if(!is_array($toolbar))
			return null;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('toolbar', $toolbar);
		$tpl->assign('interaction_class', $interaction_class);
		
		return $tpl->fetch('devblocks:devblocks.core::ui/toolbar/render.tpl');
	}
	
	function render($toolbar) {
		if(!is_array($toolbar))
			return null;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('toolbar', $toolbar);
		
		echo $this->fetch($toolbar);
	}
	
	public function extractKeyboardShortcuts(array $toolbar, array &$toolbar_keyboard_shortcuts) {
		array_walk_recursive(
			$toolbar,
			function($v, $k) use (&$toolbar_keyboard_shortcuts) {
				if('keyboard' == $k)
					$toolbar_keyboard_shortcuts[$v] = [
						'keys' => $v,
					];
			}
		);
	}
}