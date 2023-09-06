<?php
class _DevblocksSheetService {
	private static $_instance = null;
	
	private array $_types = [];
	private $_default_type = null;
	private $_type_funcs = null;
	
	static function getInstance() {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksSheetService();
		
		return self::$_instance;
	}
	
	/**
	 * 
	 * @return _DevblocksSheetService
	 */
	function newInstance() {
		return new _DevblocksSheetService();
	}
	
	private function __construct() {
		$this->_type_funcs = new _DevblocksSheetServiceTypes();
	}
	
	function parse($kata, &$error=null) {
		// Deprecated
		if(DevblocksPlatform::strStartsWith(trim($kata), '---')) {
			if(false === ($sheet = $this->_parseYaml($kata, $error)))
				return false;
			
		} else {
			if(false === ($sheet = DevblocksPlatform::services()->kata()->parse($kata, $error)))
				return false;
			
			$sheet = DevblocksPlatform::services()->kata()->formatTree($sheet);
		}
		
		return $sheet;
	}
	
	/**
	 * @param string $yaml
	 * @param string $error
	 * @return array|false
	 * @deprecated
	 */
	private function _parseYaml($yaml, &$error=null) {
		if(false === ($sheet = DevblocksPlatform::services()->string()->yamlParse($yaml, 0, $error)))
			return false;
		
		return $sheet;
	}
	
	function setDefaultType($type) {
		$this->_default_type = $type;
	}
	
	function addType($name, callable $func) {
		$this->_types[$name] = $func;
	}
	
	function types() {
		return $this->_type_funcs;
	}
	
	function getDefaultEnvironment() : array {
		return [
			'dark_mode' => DAO_WorkerPref::get(CerberusApplication::getActiveWorker()->id ?? 0, 'dark_mode', 0),
		];
	}
	
	private function _prepareData(array $sheet, array $sheet_dicts) : array {
		$data = [];
		
		if($sheet_dicts)
			return $sheet_dicts;
		
		if(
			array_key_exists('data', $sheet) 
			&& is_array($sheet['data'])
		) {
			foreach($sheet['data'] as $values) {
				if(!is_array($values))
					continue;
				
				$data[] = DevblocksDictionaryDelegate::instance($values);
			}
		}
		
		return $data;
	}
	
	function getLayout(array $sheet) {
		$layout = [
			'style' => 'table',
			'params' => [],
			'headings' => true,
			'paging' => true,
			'filtering' => false,
			'title_column' => '',
			'colors' => [],
		];
		
		if(array_key_exists('layout', $sheet) && is_array($sheet['layout'])) {
			if(array_key_exists('headings', $sheet['layout'])) {
				if(is_bool($sheet['layout']['headings']))
					$sheet['layout']['headings'] = $sheet['layout']['headings'] ? 'yes' : 'no';
					
				$layout['headings'] = in_array($sheet['layout']['headings'], [false,'no','n','false','0']) ? false: true;
			}
			
			if(array_key_exists('paging', $sheet['layout'])) {
				if(is_bool($sheet['layout']['paging']))
					$sheet['layout']['paging'] = $sheet['layout']['paging'] ? 'yes' : 'no';
				
				$layout['paging'] = in_array($sheet['layout']['paging'], [false,'no','n','false','0']) ? false: true;
			}
			
			if(array_key_exists('filtering', $sheet['layout'])) {
				if(is_bool($sheet['layout']['filtering']))
					$sheet['layout']['filtering'] = $sheet['layout']['filtering'] ? 'yes' : 'no';
				
				$layout['filtering'] = in_array($sheet['layout']['filtering'], [false,'no','n','false','0']) ? false: true;
			}
			
			if(array_key_exists('style', $sheet['layout']))
				$layout['style'] = $sheet['layout']['style'];
			
			if(array_key_exists('params', $sheet['layout']))
				$layout['params'] = $sheet['layout']['params'];
			
			if(array_key_exists('title_column', $sheet['layout'])) {
				$columns = $this->getColumns($sheet);
				
				if(array_key_exists($sheet['layout']['title_column'], $columns))
					$layout['title_column'] = $sheet['layout']['title_column'];
			}
			
			if(array_key_exists('colors', $sheet['layout'])) {
				$validator = DevblocksPlatform::services()->validation()->validators()->colorsHex();
				
				// Validate as color HEX codes
				$colors = array_filter($sheet['layout']['colors'], fn($color_set) => $validator($color_set));
				
				$layout['colors'] = $colors;
			}
		}
		
		return $layout;
	}
	
	function getColumns(array $sheet) {
		if(!array_key_exists('columns', $sheet))
			return [];
		
		$columns = [];
		$column_keys = [];
		
		if(array_key_exists('columns', $sheet) && is_array($sheet['columns']))
		foreach($sheet['columns'] as $column_idx => $column) {
			if(!is_array($column)) {
				unset($columns[$column_idx]);
				continue;
			}
		
			if(is_numeric($column_idx)) {
				$column_type = key($column);
				$column = current($column);
				
				if(!is_array($column)) {
					unset($columns[$column_idx]);
					continue;
				}
				
				$column['_type'] = $column_type;
				
			} else if(is_string($column_idx)) {
				list($column_type, $column_key) = explode('/', $column_idx, 2);
				
				$column['_type'] = $column_type;
				
				if(!array_key_exists('key', $column))
					$column['key'] = $column_key;
			}
			
			if(!array_key_exists('label', $column))
				$column['label'] = DevblocksPlatform::strTitleCase(trim(str_replace('_', ' ', $column['key'])));
			
			// Only return valid column types
			if(array_key_exists($column['_type'], $this->_types)) {
				$column_keys[] = $column['key'];
				$columns[] = $column;
			}
		}
		
		return array_combine($column_keys, $columns);
	}
	
	private function _cellParamColor(string $param_key, array $column, DevblocksDictionaryDelegate $sheet_dict, array $layout, ?array $environment=null) : ?string {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$column_params = $column['params'] ?? [];
		
		$color = null;
		
		if(is_null($environment)) {
			$environment = $this->getDefaultEnvironment();
		}
		
		if(array_key_exists($param_key, $column_params)) {
			$color = $tpl_builder->build($column_params[$param_key], $sheet_dict);
		}
		
		if($color) {
			list($color, $color_index) = array_pad(explode(':', $color, 2), 2, 0);
			
			// Check for a dark mode version when enabled
			if(
				($environment['dark_mode'] ?? false)
				&& !DevblocksPlatform::strEndsWith($color, '_dark')
				&& array_key_exists($color . '_dark', $layout['colors'] ?? [])
			) {
				$color .= '_dark';
			}
			
			if(
				array_key_exists($color, $layout['colors'] ?? [])
				&& is_array($layout['colors'][$color])
				&& array_key_exists($color_index, $layout['colors'][$color])
			) {
				$color = $layout['colors'][$color][$color_index];
			} else {
				$color = null;
			}
		}
		
		return $color;
	}
	
	private function _cellParamTextSize(string $param_key, array $column, DevblocksDictionaryDelegate $sheet_dict, array $layout, ?array $environment=null) : ?string {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$column_params = $column['params'] ?? [];
		
		$text_size = null;
		
		if(array_key_exists($param_key, $column_params)) {
			$text_size = $tpl_builder->build($column_params[$param_key], $sheet_dict);
		}
		
		if($text_size) {
			$text_size = intval($text_size);
		}
		
		return $text_size;
	}
	
	function getVisibleRowIds(array $sheet, array $sheet_dicts, array $columns=[]) : array {
		$sheet_dicts = $this->_prepareData($sheet, $sheet_dicts);
		
		if(!$columns)
			$columns = $this->getColumns($sheet);
		
		$row_ids = [];
		
		foreach($sheet_dicts as $sheet_dict) {
			if(!($sheet_dict instanceof DevblocksDictionaryDelegate))
				$sheet_dict = DevblocksDictionaryDelegate::instance($sheet_dict);
			
			foreach($columns as $column) {
				if(!($column_type = $column['_type'] ?? null))
					$column_type = $this->_default_type;
				
				if(!array_key_exists($column_type, $this->_types))
					continue;
				
				if($column_type != 'selection')
					continue;
				
				$row_ids[] = $this->_types[$column_type]($column, $sheet_dict, ['format'=>'text']);		
			}
		}
		
		return $row_ids;
	}
	
	function getRows(array $sheet, array $sheet_dicts, ?array $environment=null) : array {
		$sheet_dicts = $this->_prepareData($sheet, $sheet_dicts);
		
		// Sanitize
		$columns = $this->getColumns($sheet);
		$layout = $this->getLayout($sheet);
		
		if(!is_array($environment))
			$environment = $this->getDefaultEnvironment();
		
		$rows = [];
		$index = 0;
		
		foreach($sheet_dicts as $sheet_dict_id => $sheet_dict) {
			$row = [];
			
			if(!($sheet_dict instanceof DevblocksDictionaryDelegate))
				$sheet_dict = DevblocksDictionaryDelegate::instance($sheet_dict);
			
			$sheet_dict->setKeyPath('__row.index', $index++);
			
			foreach($columns as $column) {
				if(!($column_key = $column['key'] ?? null))
					continue;
				
				if(!($column_type = $column['_type'] ?? null))
					$column_type = $this->_default_type;
				
				if(!array_key_exists($column_type, $this->_types))
					continue;
				
				$color = $this->_cellParamColor('color', $column, $sheet_dict, $layout, $environment);
				$text_color = $this->_cellParamColor('text_color', $column, $sheet_dict, $layout, $environment);
				$text_size = $this->_cellParamTextSize('text_size', $column, $sheet_dict, $layout, $environment);
				
				$row[$column_key] = new DevblocksSheetCell(
					$this->_types[$column_type]($column, $sheet_dict, $environment),
					[
						'color' => $color,
						'text_color' => $text_color,
						'text_size' => $text_size,
					]
				);
			}
			
			$rows[$sheet_dict_id] = $row;
		}
		
		return $rows;
	}
	
	public function getPaging($count, $page, $limit, $total) : array {
		$paging = [
			'page' => [
				'of' => intval(ceil($total / $limit)),
				'rows' => [
					'of' => intval($total),
					'count' => $count,
					'limit' => $limit,
				],
			]
		];
		
		$paging['page']['index'] = DevblocksPlatform::intClamp($page, 0, PHP_INT_MAX);
		
		$paging['page']['rows']['from'] = $paging['page']['index'] * $paging['page']['rows']['limit'] + 1;
		$paging['page']['rows']['to'] = min($paging['page']['rows']['from']+$paging['page']['rows']['limit'] - 1, $paging['page']['rows']['of']);
		
		if($paging['page']['rows']['from'] > $paging['page']['rows']['of']) {
			$paging['page']['rows']['from'] = 0;
			$paging['page']['rows']['to'] = 0;
		}
		
		if($paging['page']['index'] - 1 >= 0) {
			$paging['page']['prev'] = $paging['page']['index'] - 1;
			$paging['page']['first'] = 0;
		}
		
		if($paging['page']['index'] + 1 < $paging['page']['of']) {
			$paging['page']['next'] = $paging['page']['index'] + 1;
			$paging['page']['last'] = $paging['page']['of']-1;
		}
		
		return $paging;
	}
	
	public function withDefaultTypes() {
		$this->addType('card', $this->types()->card());
		$this->addType('date', $this->types()->date());
		$this->addType('icon', $this->types()->icon());
		$this->addType('link', $this->types()->link());
		$this->addType('markdown', $this->types()->markdown());
		$this->addType('search', $this->types()->search());
		$this->addType('search_button', $this->types()->searchButton());
		$this->addType('selection', $this->types()->selection());
		$this->addType('slider', $this->types()->slider());
		$this->addType('text', $this->types()->text());
		$this->addType('time_elapsed', $this->types()->timeElapsed());
		$this->setDefaultType('text');
		return $this;
	}
}

class DevblocksSheetCell implements JsonSerializable {
	private array $_attrs = [];
	private string $_value = '';
	
	public function __construct(string $value, array $attrs=[]) {
		$this->_attrs = $attrs;
		$this->_value = $value;
	}
	
	/** @noinspection PhpUnused */
	public function getAttr(string $name, mixed $default=null) : mixed {
		return $this->_attrs[$name] ?? $default;
	}
	
	public function __toString(): string {
		return $this->_value;
	}
	
	public function jsonSerialize(): string {
		return $this->_value;
	}
}

class _DevblocksSheetServiceTypes {
	function card() : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) {
			$url_writer = DevblocksPlatform::services()->url();
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$column_key = $column['key'] ?? null;
			$column_params = ($column['params'] ?? null) ?: [];
			
			@$is_underlined = !array_key_exists('underline', $column_params) || $column_params['underline'];
			
			$default_card_context_key = null;
			$default_card_id_key = null;
			$default_card_label_key = null;
			
			if($column_suffix = DevblocksPlatform::strEndsWith($column_key, ['id','_context','_label'])) {
				$column_prefix = substr($column_key, 0, -strlen($column_suffix));
				
				$default_card_context_key = $column_prefix . '_context';
				$default_card_id_key = $column_prefix . 'id';
				$default_card_label_key = $column_prefix . '_label';
			}
			
			$value = '';
			$card_label_is_escaped = false;
			
			if(array_key_exists('label', $column_params) && $column_params['label']) {
				$card_label = $column_params['label'];
			} else if(array_key_exists('label_key', $column_params)) {
				$card_label = $sheet_dict->get($column_params['label_key']);
			} else if(array_key_exists('label_template', $column_params)) {
				$card_label = $tpl_builder->build($column_params['label_template'], $sheet_dict);
				$card_label = DevblocksPlatform::purifyHTML($card_label, false, true, [$filter]);
				$card_label_is_escaped = true;
			} else {
				$card_label = $sheet_dict->get($default_card_label_key);
			}
			
			if(array_key_exists('context', $column_params) && $column_params['context']) {
				$card_context = $column_params['context'];
			} else if(array_key_exists('context_key', $column_params)) {
				$card_context = $sheet_dict->get($column_params['context_key']);
			} else if(array_key_exists('context_template', $column_params)) {
				$card_context = $tpl_builder->build($column_params['context_template'], $sheet_dict);
				$card_context = DevblocksPlatform::purifyHTML($card_context, false, true, [$filter]);
			} else {
				$card_context = $sheet_dict->get($default_card_context_key);
			}
			
			if(array_key_exists('id', $column_params) && $column_params['id']) {
				$card_id = $column_params['id'];
			} else if(array_key_exists('id_key', $column_params)) {
				$card_id = $sheet_dict->get($column_params['id_key']);
			} else if(array_key_exists('id_template', $column_params)) {
				$card_id = $tpl_builder->build($column_params['id_template'], $sheet_dict);
				$card_id = DevblocksPlatform::purifyHTML($card_id, false, true, [$filter]);
			} else {
				$card_id = $sheet_dict->get($default_card_id_key);
			}
			
			if('text' == ($environment['format'] ?? null)) {
				$value = $card_label;
				
			} else { // HTML
				if(array_key_exists('icon', $column_params) && $column_params['icon']) {
					$icon_column = $column;
					$icon_column['params'] = $column_params['icon'];
					$value .= $this->icon()($icon_column, $sheet_dict);
				}
				
				if($card_context && $card_id && $card_label) {
					$avatar_value = '';
					
					// Avatar image?
					if(array_key_exists('image', $column_params) && $column_params['image']) {
						$avatar_size = '1.5em';
						if(false != ($card_context_ext = Extension_DevblocksContext::getByAlias($card_context))) {
							$avatar_value .= sprintf('<img src="%s?v=%s" style="width:%s;border-radius:%s;margin-right:0.25em;vertical-align:middle;">',
								$url_writer->write(sprintf("c=avatars&ctx=%s&id=%d",
									DevblocksPlatform::strEscapeHtml($card_context_ext->params['alias']),
									$card_id
								)),
								DevblocksPlatform::strEscapeHtml($sheet_dict->get('updated_at', $sheet_dict->get('updated'))),
								DevblocksPlatform::strEscapeHtml($avatar_size),
								DevblocksPlatform::strEscapeHtml($avatar_size)
							);
						}
					}
					
					// Card link
					$value .= sprintf('<div class="cerb-peek-trigger" data-context="%s" data-context-id="%d" %s style="text-decoration:%s;display:inline-block;cursor:pointer;">%s%s</div>',
						DevblocksPlatform::strEscapeHtml($card_context),
						$card_id,
						$sheet_dict->get('record_url') ? sprintf('data-profile-url="%s"', DevblocksPlatform::strEscapeHtml($sheet_dict->get('record_url'))) : '',
						$is_underlined ? 'underline' : 'normal',
						$avatar_value,
						$card_label_is_escaped ? $card_label : DevblocksPlatform::strEscapeHtml($card_label)
					);
					
				} else {
					$value .= DevblocksPlatform::strEscapeHtml($card_label);
				}
			}
			
			return $value;
		};
	}
	
	function date(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filters = [];
			
			if($filter_html)
				$filters[] = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$column_params = ($column['params'] ?? null) ?: [];
			
			if(array_key_exists('value', $column_params)) {
				$ts = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$ts = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$ts = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$ts = DevblocksPlatform::purifyHTML($ts, false, true, $filters);
			} else {
				$ts = $sheet_dict->get($column['key']);
			}
			
			if(is_string($ts))
				$ts = intval($ts);
			
			$value = '';
			
			if('text' == ($environment['format'] ?? null)) {
				$value = date('r', $ts);
				
			} else {
				if(array_key_exists('format', $column_params)) {
					if($ts && ($date_str = date($column_params['format'], $ts)))
						$value = DevblocksPlatform::strEscapeHtml($date_str);
					
				} else {
					$value = sprintf('<abbr title="%s">%s</abbr>',
						DevblocksPlatform::strEscapeHtml(date('r', $ts)),
						DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strPrettyTime($ts))
					);
				}
			}
			
			return $value;
		};
	}
	
	function icon() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$value = '';
			
			@$column_params = $column['params'] ?: [];
			
			if(!is_array($column_params)) {
				$column_params = [
					'image' => $column_params
				];
			}
			
			if(array_key_exists('record_uri', $column_params)) {
				$record_uri = $tpl_builder->build($column_params['record_uri'], $sheet_dict);
				
				if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($record_uri)))
					return '';
				
				if(!($img_context = $uri_parts['context'] ?? null))
					return '';
				
				if(CerberusContexts::isSameContext($img_context, CerberusContexts::CONTEXT_AUTOMATION_RESOURCE)) {
					$img = sprintf('<img style="%s" src="%s"/>',
						array_key_exists('text_size', $column_params) ? 'width:1em;height:1em;' : '',
						DevblocksPlatform::services()->url()->writeNoProxy(sprintf('c=ui&a=image&token=%s', $uri_parts['context_id'])),
					);
					
				} else {
					$img = sprintf('<img class="cerb-avatar" style="%s" src="%s?v=%d"/>',
						array_key_exists('text_size', $column_params) ? 'width:1em;height:1em;border-radius:1em;' : 'margin-right:0.25em;',
						DevblocksPlatform::services()->url()->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', $uri_parts['context_ext']->params['alias'], $uri_parts['context_id']), true),
						APP_BUILD
					);
				}
				
				DevblocksPlatform::purifyHTML($img, false, true, [$filter]);
				
				return $img;
			}
			
			if(array_key_exists('image', $column_params) && $column_params['image']) {
				$image = $column_params['image'];
			} else if(array_key_exists('image_key', $column_params)) {
				$image = $sheet_dict->get($column_params['image_key']);
			} else if(array_key_exists('image_template', $column_params)) {
				$image = $tpl_builder->build($column_params['image_template'], $sheet_dict);
			} else {
				$image = '';
			}
			
			$image = trim($image);
			
			// Sanitize image name against known list
			
			$icons_available = PageSection_SetupDevelopersReferenceIcons::getIcons();
			
			if($image) {
				if(!in_array($image, $icons_available))
					$image = null;
			}
			
			/*
			if(array_key_exists('color', $column_params) && $column_params['color']) {
				$color = $column_params['color'];
			} else if(array_key_exists('color_key', $column_params)) {
				$color = $sheet_dict->get($column_params['color_key']);
			} else if(array_key_exists('color_template', $column_params)) {
				$color = $tpl_builder->build($column_params['color_template'], $sheet_dict);
				$color = DevblocksPlatform::purifyHTML($color, false, true, [$filter]);
			} else {
				$color = '';
			}
			*/
			
			// [TODO] Sanitize color
			
			if('text' == ($environment['format'] ?? null)) {
				$value = $image;
				
			} else {
				if($image) {
					$span = sprintf('<span class="glyphicons glyphicons-%s" style="margin-right:0.25em;"></span>',
						DevblocksPlatform::strEscapeHtml($image)
					);
					
					DevblocksPlatform::purifyHTML($span, false, true, [$filter]);
					
					$value .= $span;
				}
			}
			
			return $value;
		};
	}
	
	function interaction(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$kata = DevblocksPlatform::services()->kata();
			$filters = [];
			
			if($filter_html)
				$filters[] = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			
			if(array_key_exists('uri', $column_params)) {
				$uri = $column_params['uri'];
			} else if(array_key_exists('uri_key', $column_params)) {
				$uri = $sheet_dict->get($column_params['uri_key']);
			} else if(array_key_exists('uri_template', $column_params)) {
				$uri = $tpl_builder->build($column_params['uri_template'], $sheet_dict);
				$uri = DevblocksPlatform::purifyHTML($uri, false, true, $filters);
			} else {
				$uri = '';
			}
			
			if(DevblocksPlatform::strStartsWith($uri, 'cerb:')) {
				if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($uri)))
					return '';
				
				if(Context_Automation::ID != ($uri_parts['context'] ?? null))
					return '';
				
				$uri = $uri_parts['context_id'] ?? '';
			}
			
			if(array_key_exists('inputs', $column_params)) {
				$inputs = $column_params['inputs'] ?? [];
				$error = null;
				$inputs = $kata->formatTree($inputs, $sheet_dict, $error);
			} else {
				$inputs = [];
			}
			
			if(array_key_exists('text', $column_params)) {
				$text = $column_params['text'];
			} else if(array_key_exists('text_key', $column_params)) {
				$text = $sheet_dict->get($column_params['text_key']);
			} else if(array_key_exists('text_template', $column_params)) {
				$text = $tpl_builder->build($column_params['text_template'], $sheet_dict);
				$text = DevblocksPlatform::purifyHTML($text, false, true, $filters);
			} else {
				$text = '';
			}
			
			if('text' == ($environment['format'] ?? null)) {
				return '';
				
			} else {
				if(!$uri)
					return $text;
				
				return sprintf('<a href="javascript:;" class="cerb-interaction-trigger" data-interaction-uri="%s" data-interaction-params="%s">%s</a>',
					DevblocksPlatform::strEscapeHtml($uri),
					DevblocksPlatform::services()->url()->arrayToQueryString($inputs),
					DevblocksPlatform::strEscapeHtml($text)
				);
			}
		};
	}
	
	function link(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filters = [];
			
			if($filter_html)
				$filters[] = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			
			if(array_key_exists('href', $column_params)) {
				$href = $column_params['href'];
			} else if(array_key_exists('href_key', $column_params)) {
				$href = $sheet_dict->get($column_params['href_key']);
			} else if(array_key_exists('href_template', $column_params)) {
				$href = $tpl_builder->build($column_params['href_template'], $sheet_dict);
				$href = DevblocksPlatform::purifyHTML($href, false, true, $filters);
			} else {
				$href = '';
			}
			
			if(array_key_exists('text', $column_params)) {
				$text = $column_params['text'];
			} else if(array_key_exists('text_key', $column_params)) {
				$text = $sheet_dict->get($column_params['text_key']);
			} else if(array_key_exists('text_template', $column_params)) {
				$text = $tpl_builder->build($column_params['text_template'], $sheet_dict);
				$text = DevblocksPlatform::purifyHTML($text, false, true, $filters);
			} else {
				$text = '';
			}
			
			$value = '';
			$url = '';
			
			if(DevblocksPlatform::strStartsWith($href, ['http:','https:'])) {
				$url = $href;
				
			} else if(DevblocksPlatform::strStartsWith($href, '/')) {
				$href = trim($href, '/\\');
				$url = DevblocksPlatform::services()->url()->write(explode('/', $href), true);
			}
			
			if($url) {
				if('text' == ($environment['format'] ?? null)) {
					$value = $url;
				} else {
					$value = sprintf('<a href="%s">%s</a>',
						$url,
						DevblocksPlatform::strEscapeHtml($text)
					);
				}
			}
			
			return $value;
		};
	}
	
	function markdown(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filters = [];

			if($filter_html)
				$filters[] = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$column_params = ($column['params'] ?? null) ?: [];
			
			if(array_key_exists('value', $column_params)) {
				$text_value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$text_value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$text_value = $tpl_builder->build($column_params['value_template'], $sheet_dict);
			} else {
				$text_value = $sheet_dict->get($column['key'], null);
			}
			
			$text_value = strval($text_value);
			
			if('text' == ($environment['format'] ?? null))
				return $text_value;
			
			$value = DevblocksPlatform::parseMarkdown($text_value, true);
			
			return DevblocksPlatform::purifyHTML($value, false, true, $filters);
		};
	}
	
	function search() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$column_key = $column['key'] ?? null;
			$column_params = ($column['params'] ?? null) ?: [];
			
			@$is_underlined = !array_key_exists('underline', $column_params) || $column_params['underline'];
			$value = '';
			
			$default_search_context_key = null;
			$default_search_label_key = $column_key;
			
			if($column_suffix = DevblocksPlatform::strEndsWith($column_key, ['_context','_label'])) {
				$column_prefix = substr($column_key, 0, -strlen($column_suffix));
				
				$default_search_context_key = $column_prefix . '_context';
				$default_search_label_key = $column_prefix . '_label';
			}
			
			$is_label_escaped = false;
			
			if(array_key_exists('label', $column_params)) {
				$search_label = $column_params['label'];
			} else if(array_key_exists('label_key', $column_params)) {
				$search_label = $sheet_dict->get($column_params['label_key']);
			} else if(array_key_exists('label_template', $column_params)) {
				$search_label = $tpl_builder->build($column_params['label_template'], $sheet_dict);
				$search_label = DevblocksPlatform::purifyHTML($search_label, false, true, [$filter]);
				$is_label_escaped = true;
			} else {
				$search_label = $sheet_dict->get($default_search_label_key);
			}
			
			if(array_key_exists('context', $column_params)) {
				$search_context = $column_params['context'];
			} else if(array_key_exists('context_key', $column_params)) {
				$search_context = $sheet_dict->get($column_params['context_key']);
			} else if(array_key_exists('context_template', $column_params)) {
				$search_context = $tpl_builder->build($column_params['context_template'], $sheet_dict);
				$search_context = DevblocksPlatform::purifyHTML($search_context, false, true, [$filter]);
			} else {
				$search_context = $sheet_dict->get($default_search_context_key);
			}
			
			if(array_key_exists('query', $column_params)) {
				$search_query = $column_params['query'];
			} else if(array_key_exists('query_key', $column_params)) {
				$search_query = $sheet_dict->get($column_params['query_key']);
			} else if(array_key_exists('query_template', $column_params)) {
				$search_query = $tpl_builder->build($column_params['query_template'], $sheet_dict);
				$search_query = DevblocksPlatform::purifyHTML($search_query, false, true, [$filter]);
			} else {
				$search_query = '';
			}
			
			if('text' == ($environment['format'] ?? null))
				return trim(strip_tags($search_label));
			
			if($search_context && $search_label) {
				if(!($context_ext = Extension_DevblocksContext::getByAlias($search_context, true)))
					return;
				
				if(!($search_query = $tpl_builder->build($search_query, $sheet_dict)))
					return;
				
				// Search link
				$value .= sprintf('<div class="cerb-search-trigger" data-context="%s" data-query="%s" style="display:inline-block;text-decoration:%s;cursor:pointer;">%s</div>',
					DevblocksPlatform::strEscapeHtml($context_ext->id),
					DevblocksPlatform::strEscapeHtml($search_query),
					$is_underlined ? 'underline' : false,
					$is_label_escaped ? $search_label : DevblocksPlatform::strEscapeHtml($search_label)
				);
				
			} else {
				$value = $search_label;
			}
			
			return $value;
		};
	}
	
	function searchButton() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			
			if(array_key_exists('context', $column_params)) {
				$search_context = $column_params['context'];
			} else if(array_key_exists('context_key', $column_params)) {
				$search_context = $sheet_dict->get($column_params['context_key']);
			} else if(array_key_exists('context_template', $column_params)) {
				$search_context = $tpl_builder->build($column_params['context_template'], $sheet_dict);
				$search_context = DevblocksPlatform::purifyHTML($search_context, false, true, [$filter]);
			} else {
				$search_context = '';
			}
			
			if(array_key_exists('query', $column_params)) {
				$search_query = $column_params['query'];
			} else if(array_key_exists('query_key', $column_params)) {
				$search_query = $sheet_dict->get($column_params['query_key']);
			} else if(array_key_exists('query_template', $column_params)) {
				$search_query = $tpl_builder->build($column_params['query_template'], $sheet_dict);
				$search_query = DevblocksPlatform::purifyHTML($search_query, false, true, [$filter]);
			} else {
				$search_query = '';
			}
			
			if(!($query = $tpl_builder->build($search_query, $sheet_dict)))
				return;
			
			if(!$search_context || !($context_ext = Extension_DevblocksContext::getByAlias($search_context, true)))
				return;
			
			if('text' == ($environment['format'] ?? null))
				return $query;
			
			return sprintf('<button type="button" class="cerb-search-trigger" data-context="%s" data-query="%s"><span class="glyphicons glyphicons-search"></span></button>',
				DevblocksPlatform::strEscapeHtml($context_ext->id),
				DevblocksPlatform::strEscapeHtml($query)
			);
		};
	}
	
	function selection() : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			
			$column_params = ($column['params'] ?? null) ?: [];
			$is_single = ('single' == ($column_params['mode'] ?? null));
			
			if(array_key_exists('label', $column_params)) {
				$text_label = $column_params['label'];
			} else if(array_key_exists('label_key', $column_params)) {
				$text_label = $sheet_dict->get($column_params['label_key']);
			} else if(array_key_exists('label_template', $column_params)) {
				$text_label = $tpl_builder->build($column_params['label_template'], $sheet_dict);
			} else {
				$text_label = '';
			}
			
			if(!empty($text_label))
				$text_label = ' ' . ltrim($text_label);

			if(array_key_exists('value', $column_params)) {
				$text_value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$text_value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$text_value = $tpl_builder->build($column_params['value_template'], $sheet_dict);
			} else {
				$text_value = $sheet_dict->get($column['key'], null);
			}
			
			if('text' == ($environment['format'] ?? null)) {
				return is_array($text_value) ? json_encode($text_value) : $text_value;
				
			} else {
				if($is_single) {
					return sprintf('<label class="cerb-sheet-row--selection-label"><input type="radio" name="%s" value="%s">%s</label>',
						DevblocksPlatform::strEscapeHtml('${SHEET_SELECTION_KEY}'),
						DevblocksPlatform::strEscapeHtml(is_array($text_value) ? json_encode($text_value) : $text_value),
						DevblocksPlatform::strEscapeHtml($text_label)
					);
				} else {
					return sprintf('<label class="cerb-sheet-row--selection-label"><input type="checkbox" name="%s" value="%s">%s</label>',
						DevblocksPlatform::strEscapeHtml('${SHEET_SELECTION_KEY}'),
						DevblocksPlatform::strEscapeHtml(is_array($text_value) ? json_encode($text_value) : $text_value),
						DevblocksPlatform::strEscapeHtml($text_label)
					);
				}
			}
		};
	}
	
	function slider(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filters = [];
			
			if($filter_html)
				$filters[] = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$column_params = ($column['params'] ?? null) ?: [];
			
			$value_min = ($column_params['min'] ?? null) ?: 0;
			$value_max = ($column_params['max'] ?? null) ?: 100;
			$value_mid = ($value_max + $value_min)/2;
			
			if(array_key_exists('value', $column_params)) {
				$value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$value = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$value= DevblocksPlatform::purifyHTML($value, false, true, $filters);
			} else {
				$value = $sheet_dict->get($column['key']);
			}
			
			if($value > $value_mid) {
				$color = 'rgb(230,70,70)';
			} else if($value < $value_mid) {
				$color = 'rgb(0,200,0)';
			} else {
				$color = 'rgb(175,175,175)';
			}
			
			if('text' == ($environment['format'] ?? null)) {
				return intval($value);
				
			} else {
				return sprintf(
					'<div title="%d" style="width:60px;height:8px;background-color:var(--cerb-color-background-contrast-220);border-radius:8px;text-align:center;">'.
					'<div style="position:relative;margin-left:5px;width:50px;height:8px;">'.
					'<div style="position:absolute;margin-left:-5px;top:-1px;left:%d%%;width:10px;height:10px;border-radius:10px;background-color:%s;"></div>'.
					'</div>'.
					'</div>',
					$value,
					($value/$value_max)*100,
					DevblocksPlatform::strEscapeHtml($color)
				);
			}
		};
	}
	
	function text(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filters = [];
			
			if($filter_html)
				$filters[] = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$column_params = ($column['params'] ?? null) ?: [];
			$is_escaped = false;
			
			$value = '';
			
			if(array_key_exists('value', $column_params)) {
				$text_value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$text_value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$out = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$text_value = DevblocksPlatform::purifyHTML($out, false, true, $filters);
				$is_escaped = true;
			} else {
				$text_value = $sheet_dict->get($column['key'], null);
			}
			
			// [TODO] Sheets need a chance to use custom field extensions for rendering labels/cells
			if(is_array($text_value))
				$text_value = json_encode($text_value);
			
			if(array_key_exists('value_map', $column_params) && is_array($column_params['value_map'])) {
				if(array_key_exists($text_value, $column_params['value_map']))
					$text_value = $column_params['value_map'][$text_value];
			}
			
			if('text' == ($environment['format'] ?? null))
				return $is_escaped ? trim(strip_tags($text_value)) : $text_value;
			
			if(array_key_exists('icon', $column_params) && $column_params['icon'] && $text_value) {
				$icon_column = $column;
				$icon_column['params'] = $column_params['icon'];
				$value .= $this->icon()($icon_column, $sheet_dict);
			}
			
			$value .= $is_escaped ? $text_value : DevblocksPlatform::strEscapeHtml($text_value);
			
			return $value;
		};
	}
	
	function timeElapsed(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder()::newInstance('html');
			$filters = [];
			
			if($filter_html)
				$filters[] = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$column_params = ($column['params'] ?? null) ?: [];
			$precision = ($column_params['precision'] ?? null) ?: 2;
			
			if(array_key_exists('value', $column_params)) {
				$value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$value = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$value = DevblocksPlatform::purifyHTML($value, false, true, $filters);
			} else {
				$value = $sheet_dict->get($column['key']);
			}
			
			if(empty($value))
				return '';
			
			if('text' == ($environment['format'] ?? null))
				return DevblocksPlatform::strSecsToString($value, $precision);
			
			return DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value, $precision));
		};
	}
	
	function toolbar(bool $filter_html=true) : callable {
		return function($column, DevblocksDictionaryDelegate $sheet_dict, array $environment=[]) use ($filter_html) {
			$column_params = ($column['params'] ?? null) ?: [];
			$error = null;
			
			if('text' == ($environment['format'] ?? null))
				return '';
			
			$toolbar_kata = $column_params['kata'] ?? [];
			
			if(!($toolbar_kata = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $sheet_dict, $error))) {
				return '';
			}
			
			return '<div data-cerb-sheet-column-toolbar>'
				. DevblocksPlatform::services()->ui()->toolbar()->fetch($toolbar_kata, 'cerb-sheet-toolbar--interaction')
				. '</div>'
			;
		};
	}
}