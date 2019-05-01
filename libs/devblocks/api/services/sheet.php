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
	
	function newInstance() {
		return new _DevblocksSheetService();
	}
	
	private function __construct() {
		$this->_type_funcs = new _DevblocksSheetServiceTypes();
	}
	
	function parseYaml($yaml, &$error=null) {
		$sheet = DevblocksPlatform::services()->string()->yamlParse($yaml, 0);
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
	
	function getRows(array $sheet, array $sheet_dicts) {
		// Sanitize
		$columns = $sheet['columns'];
		
		$rows = [];
		
		foreach($sheet_dicts as $sheet_dict_id => $sheet_dict) {
			$row = [];
			
			foreach($columns as $column) {
				if(false == (@$column_key = $column['key']))
					continue;
				
				if(false == ($column_type = @$column['type']))
					$column_type = $this->_default_type;
				
				if(!array_key_exists($column_type, $this->_types))
					continue;
				
				$row[$column_key] = $this->_types[$column_type]($column, $sheet_dict);
			}
			
			$rows[$sheet_dict_id] = $row;
		}
		
		return $rows;
	}
}

class _DevblocksSheetServiceTypes {
	function card() {
		return function($column, $sheet_dict) {
			$url_writer = DevblocksPlatform::services()->url();
			
			@$card_label = $column['params']['label'];
			@$card_context = $column['params']['context'];
			@$card_id = $column['params']['id'];
			$value = '';
			
			if(!$card_label) {
				$card_label_key = @$column['params']['label_key'] ?: '_label';
				$card_label = $sheet_dict->get($card_label_key);
			}
			
			if(!$card_context) {
				$card_context_key = @$column['params']['context_key'] ?: '_context';
				$card_context = $sheet_dict->get($card_context_key);
			}
			
			if(!$card_id) {
				$card_id_key = @$column['params']['id_key'] ?: 'id';
				$card_id = $sheet_dict->get($card_id_key);
			}
			
			if($card_id) {
				// Avatar image?
				if(array_key_exists('params', $column) && array_key_exists('image', $column['params'])) {
					$avatar_size = @$column['params']['image']['size'] ?: "1.5em";
					
					$value .= sprintf('<img src="%s?v=%s" style="{$avatar_size};width:%s;border-radius:%s;margin-right:0.25em;vertical-align:middle;">',
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
				$value .= sprintf('<span class="cerb-peek-trigger" data-context="%s" data-context-id="%d" style="text-decoration:underline;cursor:pointer;">%s</span>',
					DevblocksPlatform::strEscapeHtml($card_context),
					$card_id,
					DevblocksPlatform::strEscapeHtml($card_label)
				);
				
			} else {
				$value = $card_label;
			}
			
			return $value;
		};
	}
	
	}
}