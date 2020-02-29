<?php
class _DevblocksSheetService {
	private static $_instance = null;
	
	private $_types = [];
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
	
	function parseYaml($yaml, &$error=null) {
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
	
	function getLayout(array $sheet) {
		$layout = [
			'style' => 'table',
			'headings' => true,
			'paging' => true,
			'title_column' => '',
		];
		
		if(array_key_exists('layout', $sheet) && is_array($sheet['layout'])) {
			if(array_key_exists('headings', $sheet['layout']))
				$layout['headings'] = $sheet['layout']['headings'];
			
			if(array_key_exists('paging', $sheet['layout']))
				$layout['paging'] = $sheet['layout']['paging'];
			
			if(array_key_exists('style', $sheet['layout']))
				$layout['style'] = $sheet['layout']['style'];
			
			if(array_key_exists('title_column', $sheet['layout'])) {
				$columns = $this->getColumns($sheet);
				
				if(array_key_exists($sheet['layout']['title_column'], $columns))
					$layout['title_column'] = $sheet['layout']['title_column'];
			}
		}
		
		return $layout;
	}
	
	function getColumns(array $sheet) {
		if(!array_key_exists('columns', $sheet))
			return [];
		
		$columns = $sheet['columns'];
		$column_keys = [];
		
		foreach($columns as $column_idx => $column) {
			if(!is_array($column)) {
				unset($columns[$column_idx]);
				continue;
			}
			
			$column_type = key($column);
			$column = current($column);
			
			if(!is_array($column)) {
				unset($columns[$column_idx]);
				continue;
			}
			
			$column_keys[] = $column['key'];
			$column['_type'] = $column_type;
			
			if(!array_key_exists('label', $column))
				$column['label'] = DevblocksPlatform::strTitleCase(trim(str_replace('_', ' ', $column['key'])));
			
			$columns[$column_idx] = $column;
		}
		
		return array_combine($column_keys, $columns);
	}
	
	function getRows(array $sheet, array $sheet_dicts) {
		// Sanitize
		$columns = $this->getColumns($sheet);
		
		$rows = [];
		
		foreach($sheet_dicts as $sheet_dict_id => $sheet_dict) {
			$row = [];
			
			foreach($columns as $column) {
				if(false == (@$column_key = $column['key']))
					continue;
				
				if(false == ($column_type = @$column['_type']))
					$column_type = $this->_default_type;
				
				if(!array_key_exists($column_type, $this->_types))
					continue;
				
				if(!($sheet_dict instanceof DevblocksDictionaryDelegate))
					$sheet_dict = DevblocksDictionaryDelegate::instance($sheet_dict);
				
				$row[$column_key] = $this->_types[$column_type]($column, $sheet_dict);
			}
			
			$rows[$sheet_dict_id] = $row;
		}
		
		return $rows;
	}
}

class _DevblocksSheetServiceTypes {
	function card() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$url_writer = DevblocksPlatform::services()->url();
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_key = $column['key'];
			@$column_params = $column['params'] ?: [];
			
			@$card_label = $column_params['label'];
			@$card_context = $column_params['context'];
			@$card_id = $column_params['id'];
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
			
			if(array_key_exists('label', $column_params) && $column_params['label']) {
				$card_label = $column_params['label'];
			} else if(array_key_exists('label_key', $column_params)) {
				$card_label = $sheet_dict->get($column_params['label_key']);
			} else if(array_key_exists('label_template', $column_params)) {
				$card_label = $tpl_builder->build($column_params['label_template'], $sheet_dict);
				$card_label = DevblocksPlatform::purifyHTML($card_label, false, true, [$filter]);
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
			
			if(array_key_exists('icon', $column_params) && $column_params['icon']) {
				$icon_column = $column;
				$icon_column['params'] = $column_params['icon'];
				$value .= $this->icon()($icon_column, $sheet_dict);
			}
			
			if($card_context && $card_id && $card_label) {
				// Avatar image?
				if(array_key_exists('image', $column_params) && $column_params['image']) {
					$avatar_size = '1.5em';
					
					$value .= sprintf('<img src="%s?v=%s" style="width:%s;border-radius:%s;margin-right:0.25em;vertical-align:middle;">',
						$url_writer->write(sprintf("c=avatars&ctx=%s&id=%d",
							DevblocksPlatform::strEscapeHtml($card_context),
							$card_id
						)),
						DevblocksPlatform::strEscapeHtml($sheet_dict->get('updated_at', $sheet_dict->get('updated'))),
						DevblocksPlatform::strEscapeHtml($avatar_size),
						DevblocksPlatform::strEscapeHtml($avatar_size)
					);
				}
				
				// Card link
				$value .= sprintf('<span class="cerb-peek-trigger" data-context="%s" data-context-id="%d" style="text-decoration:%s;cursor:pointer;">%s</span>',
					DevblocksPlatform::strEscapeHtml($card_context),
					$card_id,
					$is_underlined ? 'underline' : false,
					DevblocksPlatform::strEscapeHtml($card_label)
				);
				
			} else {
				$value .= DevblocksPlatform::strEscapeHtml($card_label);
			}
			
			return $value;
		};
	}
	
	function date() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			
			if(array_key_exists('value', $column_params)) {
				$ts = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$ts = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$ts = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$ts = DevblocksPlatform::purifyHTML($ts, false, true, [$filter]);
			} else {
				$ts = $sheet_dict->get($column['key']);
			}
			
			if(is_string($ts))
				$ts = intval($ts);
			
			if(array_key_exists('format', $column_params) && false != ($date_str = @date($column_params['format'], $ts))) {
				$value = DevblocksPlatform::strEscapeHtml($date_str);
				
			} else {
				$value = sprintf('<abbr title="%s">%s</abbr>',
					DevblocksPlatform::strEscapeHtml(date('r', $ts)),
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strPrettyTime($ts))
				);
			}
			
			return $value;
		};
	}
	
	function icon() {
		return function($column, $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			$value = '';
			
			@$column_params = $column['params'] ?: [];
			
			if(!is_array($column_params)) {
				$column_params = [
					'image' => $column_params
				];
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
			
			if($image) {
				$span = sprintf('<span class="glyphicons glyphicons-%s" style="margin-right:0.25em;"></span>',
					DevblocksPlatform::strEscapeHtml($image)
				);
				
				DevblocksPlatform::purifyHTML($span, false, true, [$filter]);
				
				$value .= $span;
			}
			
			return $value;
		};
	}
	
	function link() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			
			if(array_key_exists('href', $column_params)) {
				$href = $column_params['href'];
			} else if(array_key_exists('href_key', $column_params)) {
				$href = $sheet_dict->get($column_params['href_key']);
			} else if(array_key_exists('href_template', $column_params)) {
				$href = $tpl_builder->build($column_params['href_template'], $sheet_dict);
				$href = DevblocksPlatform::purifyHTML($href, false, true, [$filter]);
			} else {
				$href = '';
			}
			
			if(array_key_exists('text', $column_params)) {
				$text = $column_params['text'];
			} else if(array_key_exists('text_key', $column_params)) {
				$text = $sheet_dict->get($column_params['text_key']);
			} else if(array_key_exists('text_template', $column_params)) {
				$text = $tpl_builder->build($column_params['text_template'], $sheet_dict);
				$text = DevblocksPlatform::purifyHTML($text, false, true, [$filter]);
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
				$value = sprintf('<a href="%s">%s</a>',
					$url,
					DevblocksPlatform::strEscapeHtml($text)
				);
			}
			
			return $value;
		};
	}
	
	function search() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_key = $column['key'];
			@$column_params = $column['params'] ?: [];
			
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
			
			if($search_context && $search_label) {
				if(false == ($context_ext = Extension_DevblocksContext::getByAlias($search_context, true)))
					return;
				
				if(false == ($search_query = $tpl_builder->build($search_query, $sheet_dict)))
					return;
				
				// Search link
				$value .= sprintf('<div class="cerb-search-trigger" data-context="%s" data-query="%s" style="text-decoration:%s;cursor:pointer;">%s</div>',
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
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
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
			
			if(false == ($query = $tpl_builder->build($search_query, $sheet_dict)))
				return;
			
			if(!$search_context || false == ($context_ext = Extension_DevblocksContext::getByAlias($search_context, true)))
				return;
			
			return sprintf('<button type="button" class="cerb-search-trigger" data-context="%s" data-query="%s"><span class="glyphicons glyphicons-search"></span></button>',
				DevblocksPlatform::strEscapeHtml($context_ext->id),
				DevblocksPlatform::strEscapeHtml($query)
			);
		};
	}
	
	function slider() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			
			$value_min = @$column_params['min'] ?: 0;
			$value_max = @$column_params['max'] ?: 100;
			$value_mid = ($value_max + $value_min)/2;
			
			if(array_key_exists('value', $column_params)) {
				$value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$value = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$value= DevblocksPlatform::purifyHTML($value, false, true, [$filter]);
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
			
			return sprintf(
				'<div title="%d" style="width:60px;height:8px;background-color:rgb(220,220,220);border-radius:8px;text-align:center;">'.
					'<div style="position:relative;margin-left:5px;width:50px;height:8px;">'.
						'<div style="position:absolute;margin-left:-5px;top:-1px;left:%d%%;width:10px;height:10px;border-radius:10px;background-color:%s;"></div>'.
					'</div>'.
				'</div>',
				$value,
				($value/$value_max)*100,
				DevblocksPlatform::strEscapeHtml($color)
			);
			
			return $value;
		};
	}
	
	function text() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			$is_escaped = false;
			
			$value = '';
			
			if(array_key_exists('icon', $column_params) && $column_params['icon']) {
				$icon_column = $column;
				$icon_column['params'] = $column_params['icon'];
				$value .= $this->icon()($icon_column, $sheet_dict);
			}
			
			if(array_key_exists('value', $column_params)) {
				$text_value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$text_value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$out = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$text_value = DevblocksPlatform::purifyHTML($out, false, true, [$filter]);
				$is_escaped = true;
			} else {
				$text_value = $sheet_dict->get($column['key'], null);
			}
			
			// [TODO] Sheets need a chance to use custom field extensions for rendering labels/cells
			if(is_array($text_value))
				$text_value = json_encode($text_value);
			
			if(array_key_exists('value_map', $column_params) && is_array($column_params['value_map'])) {
				if(array_key_exists($value, $column_params['value_map']))
					$text_value = $column_params['value_map'][$text_value];
			}
			
			$value .= $is_escaped ? $text_value : DevblocksPlatform::strEscapeHtml($text_value);
			
			return $value;
		};
	}
	
	function timeElapsed() {
		return function($column, DevblocksDictionaryDelegate $sheet_dict) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			
			@$column_params = $column['params'] ?: [];
			
			@$precision = $column_params['precision'] ?: 2;
			
			if(array_key_exists('value', $column_params)) {
				$value = $column_params['value'];
			} else if(array_key_exists('value_key', $column_params)) {
				$value = $sheet_dict->get($column_params['value_key']);
			} else if(array_key_exists('value_template', $column_params)) {
				$value = $tpl_builder->build($column_params['value_template'], $sheet_dict);
				$value = DevblocksPlatform::purifyHTML($value, false, true, [$filter]);
			} else {
				$value = $sheet_dict->get($column['key']);
			}
			
			if(empty($value))
				return '';
			
			return DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value, $precision));
		};
	}
}