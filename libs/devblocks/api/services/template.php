<?php
define('SMARTY_RESOURCE_CHAR_SET', DevblocksPlatform::strUpper(LANG_CHARSET_CODE));

/**
 * Smarty Template Manager Singleton
 *
 * @ingroup services
 */
class _DevblocksTemplateManager {
	static $_instance = null;
	static $_instance_sandbox = null;
	
	/**
	 * Constructor
	 *
	 * @private
	 */
	private function __construct() {}
	
	/**
	 * Returns an instance of the Smarty Template Engine
	 *
	 * @static
	 * @return Smarty
	 */
	static function getInstance() {
		if(null == self::$_instance) {
			$instance = new Smarty();
			
			$instance->setTemplateDir(APP_PATH . '/templates');
			$instance->setCompileDir(APP_SMARTY_COMPILE_PATH);
			$instance->setCacheDir(APP_TEMP_PATH . '/cache');
			
			$instance->setUseSubDirs(APP_SMARTY_COMPILE_USE_SUBDIRS);

			$instance->caching = Smarty::CACHING_OFF;
			$instance->cache_lifetime = 0;
			
			$instance->compile_check = DEVELOPMENT_MODE ? Smarty::COMPILECHECK_ON : Smarty::COMPILECHECK_OFF;
			$instance->compile_id = APP_BUILD;

			$instance->error_unassigned = false;
			$instance->error_reporting = DEVELOPMENT_MODE ? (E_ALL & ~E_NOTICE) : (E_ERROR & ~E_WARNING & ~E_NOTICE);
			$instance->muteUndefinedOrNullWarnings();
			
			// Auto-escape HTML output
			$instance->registerFilter('variable', ['_DevblocksTemplateManager','devblocks_autoescape'], 'devblocks_autoescape');
			
			// Devblocks plugins
			$instance->registerPlugin('function','fetch', array('_DevblocksTemplateManager', 'function_void'));
			$instance->registerPlugin('block','devblocks_url', array('_DevblocksTemplateManager', 'block_devblocks_url'));
			$instance->registerPlugin('block','php', array('_DevblocksTemplateManager', 'block_void'));
			$instance->registerPlugin('modifier','devblocks_context_name', array('_DevblocksTemplateManager', 'modifier_devblocks_context_name'));
			$instance->registerPlugin('modifier','devblocks_date', array('_DevblocksTemplateManager', 'modifier_devblocks_date'));
			$instance->registerPlugin('modifier','devblocks_decimal', array('_DevblocksTemplateManager', 'modifier_devblocks_decimal'));
			$instance->registerPlugin('modifier','devblocks_email_quotes_cull', array('_DevblocksTemplateManager', 'modifier_devblocks_email_quotes_cull'));
			$instance->registerPlugin('modifier','devblocks_email_quote', array('_DevblocksTemplateManager', 'modifier_devblocks_email_quote'));
			$instance->registerPlugin('modifier','devblocks_hyperlinks', array('_DevblocksTemplateManager', 'modifier_devblocks_hyperlinks'));
			$instance->registerPlugin('modifier','devblocks_hideemailquotes', array('_DevblocksTemplateManager', 'modifier_devblocks_hide_email_quotes'));
			$instance->registerPlugin('modifier','devblocks_permalink', array('_DevblocksTemplateManager', 'modifier_devblocks_permalink'));
			$instance->registerPlugin('modifier','devblocks_markdown_to_html', array('_DevblocksTemplateManager', 'modifier_devblocks_markdown_to_html'));
			$instance->registerPlugin('modifier','devblocks_prettytime', array('_DevblocksTemplateManager', 'modifier_devblocks_prettytime'));
			$instance->registerPlugin('modifier','devblocks_prettybytes', array('_DevblocksTemplateManager', 'modifier_devblocks_prettybytes'));
			$instance->registerPlugin('modifier','devblocks_prettysecs', array('_DevblocksTemplateManager', 'modifier_devblocks_prettysecs'));
			$instance->registerPlugin('modifier','devblocks_prettyjson', array('_DevblocksTemplateManager', 'modifier_devblocks_prettyjson'));
			$instance->registerPlugin('modifier','devblocks_rangy_deserialize', array('_DevblocksTemplateManager', 'modifier_devblocks_rangy_deserialize'));
			$instance->registerPlugin('modifier','devblocks_translate', array('_DevblocksTemplateManager', 'modifier_devblocks_translate'));
			$instance->registerPlugin('modifier','array_keys', array('_DevblocksTemplateManager', 'modifier_php_array_keys'));
			$instance->registerPlugin('modifier','array_shift', array('_DevblocksTemplateManager', 'modifier_php_array_shift'));
			$instance->registerPlugin('modifier','floatval', array('_DevblocksTemplateManager', 'modifier_php_floatval'));
			$instance->registerPlugin('modifier','json_encode', array('_DevblocksTemplateManager', 'modifier_php_json_encode'));
			$instance->registerPlugin('modifier','md5', array('_DevblocksTemplateManager', 'modifier_php_md5'));
			$instance->registerPlugin('modifier','sort', array('_DevblocksTemplateManager', 'modifier_php_sort'));
			$instance->registerPlugin('modifier','substr', array('_DevblocksTemplateManager', 'modifier_php_substr'));
			$instance->registerPlugin('modifier','trim', array('_DevblocksTemplateManager', 'modifier_php_trim'));
			
			$instance->registerResource('devblocks', new _DevblocksSmartyTemplateResource());
			
			self::$_instance = $instance;
		}
		return self::$_instance;
	}
	
	static function devblocks_autoescape($source) {
		if(is_null($source))
			return '';
		
		if(is_scalar($source)) {
			$source = strval($source);	
		} else {
			return '';
		}
		
		return htmlspecialchars($source, ENT_QUOTES, Smarty::$_CHARSET);
	}
	
	/**
	 * Returns an instance of the Smarty Template Engine
	 *
	 * @static
	 * @return Smarty
	 */
	static function getInstanceSandbox() {
		if(null == self::$_instance_sandbox) {
			$instance = clone self::getInstance();
			
			// Customize Smarty for the sandbox
			$instance->setCompileDir(APP_SMARTY_SANDBOX_COMPILE_PATH);
			$instance->setUseSubDirs(APP_SMARTY_COMPILE_USE_SUBDIRS);
			$instance->setCompileId(null); //APP_BUILD;
			
			// Security policy
			$security = new Smarty_Security($instance);
			$security->secure_dir = [];
			$security->trusted_uri = [];
			$security->allow_constants = false;
			$security->allow_super_globals = false;
			$security->allowed_tags = [
				'assign',
				'capture',
				'captureclose',
				'else',
				'elseif',
				'foreach',
				'foreachclose',
				'if',
				'ifclose',
				'include',
			];
			$security->disabled_tags = [
				'assign',
				'fetch',
			];
			$security->php_functions = [
				'array_keys',
				'ceil',
				'empty',
				'explode',
				'implode',
				'in_array',
				'is_a',
				'is_array',
				'isset',
				'method_exists',
				'strcasecmp',
				'substr',
				'uniqid',
			];
			$security->php_modifiers = [
				'array_keys',
				'count',
				'explode',
				'json_encode',
				'ltrim',
				'md5',
				'nl2br',
				'sort',
				'trim',
			];
			$security->static_classes = [
				'Model_CustomField',
				'Model_Ticket',
			];
			$security->streams = [
				'none'
			];
			$security->disabled_special_smarty_vars = ["template_object"];			
			$instance->enableSecurity($security);
			
			self::$_instance_sandbox = $instance;
		}
		return self::$_instance_sandbox;
	}
	
	static function modifier_devblocks_rangy_deserialize($string) {
		$callback = function(array $matches) {
			return sprintf('<span class="%s">%s</span>',
				DevblocksPlatform::strEscapeHtml($matches[1]),
				DevblocksPlatform::strEscapeHtml($matches[2])
			);
		};
		return preg_replace_callback('#\\{\{(.*?)\:(.*?)\}\}#', $callback, $string);
	}

	static function modifier_devblocks_translate($string) {
		$translate = DevblocksPlatform::getTranslationService();
		
		// Variable number of arguments
		$args = func_get_args();
		array_shift($args); // pop off $string
		
		$translated = $translate->_($string);
		
		if(!empty($args))
			$translated = vsprintf($translated ?? '', $args ?? []);
		
		return $translated;
	}
	
	// Disable the {fetch} function
	static function function_void($params, Smarty_Internal_Template $template) {
		return null;
	}
	
	// Disable the {php} block
	static function block_void($params, $content, Smarty_Internal_Template $template, &$repeat) {
		return null;
	}
	
	static function block_devblocks_url($params, $content, Smarty_Internal_Template $template, &$repeat) {
		if($repeat)
			return;
		
		$url = DevblocksPlatform::services()->url();
		
		$contents = $url->write($content, !empty($params['full']) ? true : false);
		
		if (!empty($params['assign'])) {
			$template->assign($params['assign'], $contents);
		} else {
			return $contents;
		}
	}
	
	static function modifier_devblocks_context_name($string, $type='plural') {
		if(!is_string($string))
			return '';
		
		if(false == ($ctx_manifest = Extension_DevblocksContext::getByAlias($string, false)))
			return '';
		
		if('id' == $type)
			return $ctx_manifest->id;
		
		if(false == ($aliases = Extension_DevblocksContext::getAliasesForContext($ctx_manifest)))
			return '';
		
		if(isset($aliases[$type]))
			return $aliases[$type];
		
		return '';
	}
	
	static function modifier_devblocks_date($string, $format=null, $gmt=false) {
		if(empty($string))
			return '';
	
		$date = DevblocksPlatform::services()->date();
		return $date->formatTime($format, $string, $gmt);
	}
	
	static function modifier_devblocks_decimal($string, $decimal_places=2) {
		if(empty($string))
			return '';
	
		return DevblocksPlatform::strFormatDecimal($string, $decimal_places);
	}
	
	static function modifier_devblocks_permalink($string) {
		return DevblocksPlatform::strToPermalink($string);
	}

	static function modifier_devblocks_markdown_to_html($string, $is_untrusted=true) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		@$string = strval($string);
		return DevblocksPlatform::parseMarkdown($string, $is_untrusted);
	}
	
	static function modifier_devblocks_prettytime($string, $is_delta=false) {
		return DevblocksPlatform::strPrettyTime($string, $is_delta);
	}
		
	static function modifier_devblocks_prettysecs($string, $length=0) {
		return DevblocksPlatform::strSecsToString($string, $length);
	}
	
	static function modifier_devblocks_prettyjson($string) {
		return DevblocksPlatform::strFormatJson($string);
	}

	static function modifier_devblocks_prettybytes($string, $precision='0') {
		return DevblocksPlatform::strPrettyBytes($string, $precision);
	}
	
	static function modifier_devblocks_hyperlinks($string) {
		return DevblocksPlatform::strToHyperlinks($string);
	}
	
	static function modifier_devblocks_email_quote($string, $wrap_to=76, $max_length=50000) {
		// Max length on what we're quoting
		if($max_length)
			$string = substr($string, 0, $max_length);
		
		$lines = DevblocksPlatform::parseCrlfString($string, true);
		$bins = [];
		$last_prefix = null;
		$matches = [];
		
		// Sort lines into bins
		foreach($lines as $i => $line) {
			// If a line is all whitespace and quotes, and the previous line is the same, skip it
			if($i && preg_match("/^[ >]+$/", $lines[$i-1], $matches) && preg_match("/^[ >]+$/", $line, $matches)) {
				continue;
			}
			
			$prefix = '';

			if(preg_match("/^((\> )+)/", $line, $matches))
				$prefix = $matches[1];
			
			if($prefix != $last_prefix) {
				$bins[] = array(
					'prefix' => $prefix,
					'lines' => array(),
				);
			}
			
			// Strip the prefix
			$line = mb_substr($line, mb_strlen($prefix));
			
			if(empty($bins)) {
				$bins[] = array(
					'prefix' => $prefix,
					'lines' => array(),
				);
			}
			
			end($bins);
			
			$bins[key($bins)]['lines'][] = $line;
			
			$last_prefix = $prefix;
		}
		
		// Rewrap quoted blocks
		foreach($bins as $i => $bin) {
			$prefix = $bin['prefix'];
			$l = 0;
			$bail = 25000; // prevent infinite loops
		
			if(mb_strlen($prefix) == 0)
				continue;
		
			while(isset($bins[$i]['lines'][$l]) && $bail > 0) {
				$line = $bins[$i]['lines'][$l];
				$line_len = mb_strlen($line);
				$boundary = max(0, $wrap_to - mb_strlen($prefix));
				
				if($line_len && $boundary && $line_len > $boundary) {
					// Try to split on a space
					$pos = mb_strrpos($line, ' ', -1 * (mb_strlen($line)-$boundary));
					$break_word = (false === $pos);
					
					$overflow = mb_substr($line, ($break_word ? $boundary : ($pos+1)));
					
					$bins[$i]['lines'][$l] = mb_substr($line, 0, $break_word ? $boundary : $pos);
		
					// If we don't have more lines, add a new one
					if(!empty($overflow)) {
						if(isset($bins[$i]['lines'][$l+1])) {
							$next_line = $bins[$i]['lines'][$l+1];
							
							if(mb_strlen($next_line) == 0 || DevblocksPlatform::strIsListItem($next_line)) {
								array_splice($bins[$i]['lines'], $l+1, 0, $overflow);
							} else {
								$bins[$i]['lines'][$l+1] = $overflow . " " . $bins[$i]['lines'][$l+1];
							}
						} else {
							$bins[$i]['lines'][] = $overflow;
						}
					}
				}
				
				$l++;
				$bail--;
			}
		}
		
		$out = "";
		
		foreach($bins as $i => $bin) {
			$prefix = $bin['prefix'];
			
			foreach($bin['lines'] as $line) {
				$out .= $prefix . $line . "\n";
			}
		}
		
		return $out;
	}
	
	static function modifier_devblocks_email_quotes_cull($string) {
		$lines = DevblocksPlatform::parseCrlfString($string, true);
		$out = array();
		$found_sig = false;
		
		foreach($lines as $lineno => $line) {
			if($found_sig)
				continue;
			
			if(0 == $lineno && preg_match('#On (.*) wrote:$#', $line))
				continue;
			
			if(preg_match('#^\-\- *$#', $line)) {
				$found_sig = true;
				continue;
			}
			
			if(0 == preg_match('#^\>#', $line))
				$out[] = $line;
		}
		
		return implode("\n", $out);
	}
	
	static function modifier_devblocks_hide_email_quotes($string, $length=3) {
		$string = str_replace("\r\n","\n",$string);
		$string = str_replace("\r","\n",$string);
		$string = preg_replace("/\n{3,99}/", "\n\n", $string);
		$lines = explode("\n", $string);
		
		$quote_started = false;
		$last_line = count($lines) - 1;
		
		while(false !== ($line = current($lines))) {
			$idx = key($lines);
			$quote_ended = false;
			
			// If we're in a quote and on a blank line, check the next line
			if(false !== $quote_started && 0 === strlen(ltrim($line))) {
				next($lines);
				
				if(DevblocksPlatform::strStartsWith(current($lines), ['>','&gt;'])) {
					$line = current($lines);
					$idx = key($lines);
				} else {
					prev($lines);
				}
			}
			
			// Check if the line starts with a > before any content
			
			if(DevblocksPlatform::strStartsWith($line, ['>','&gt;'])) {
				if(false === $quote_started)
					$quote_started = $idx;
				$quote_ended = false;
			} else {
				if(false !== $quote_started)
					$quote_ended = $idx-1;
			}
			
			// Always finish quoting on the last line
			if(!$quote_ended && $last_line == $idx)
				$quote_ended = $idx;
			
			if(false !== $quote_started && false !== $quote_ended) {
				if($quote_ended - $quote_started >= $length) {
					$line_count = ($quote_ended - $quote_started + 1) . ' line' . (count($lines) == 1 ? '' : 's');
					$lines[$quote_started] = "<div class='cerb-code-editor-toolbar' style='display:inline-block;margin:0.5em 0;'><button type='button' class='cerb-code-editor-toolbar-button' onclick=\"$(this).closest('div').next('div').toggle();$(this).parent().hide();\"><span class=\"glyphicons glyphicons-quote\"></span> Expand quoted text (" . $line_count . ")</button></div><div class='cerb-email-quote' style='display:none;'>" . $lines[$quote_started];
					$lines[$quote_ended] = $lines[$quote_ended]."</div>";
				}
				$quote_started = false;
			}
			
			next($lines);
		}
		
		return implode("\n", $lines);
	}
	
	static function modifier_php_array_keys($array) : array {
		if(!is_array($array)) return [];
		return array_keys($array);
	}
	
	static function modifier_php_array_shift($array) : void {
		array_shift($array);
	}
	
	static function modifier_php_floatval($value) : float {
		return floatval($value);
	}
	
	static function modifier_php_json_encode($string) : string {
		return json_encode($string);
	}
	
	static function modifier_php_md5($string) : string {
		if(!is_string($string))
			return '';
		
		return md5($string);
	}
	
	static function modifier_php_sort($array) : array {
		if(!is_array($array))
			return [];
		
		sort($array);
		return $array;
	}
	
	static function modifier_php_substr($string, $offset, $length=null) : string {
		return substr($string, $offset, $length);
	}
	
	static function modifier_php_trim($string) : string {
		return trim($string);
	}
	
};

class _DevblocksSmartyTemplateResource extends Smarty_Resource_Custom {
	public function getBasename(Smarty_Template_Source $source) {
		return basename(str_replace(':','_',$source->name));
	}

	protected function fetch($name, &$source, &$mtime) {
		list($plugin_id, $tag, $tpl_path) = explode(':',$name,3);
		
		if(empty($plugin_id) || empty($tpl_path))
			return false;
			
		$plugins = DevblocksPlatform::getPluginRegistry();
			
		if(null == ($plugin = @$plugins[$plugin_id])) /* @var $plugin DevblocksPluginManifest */
			return false;
		
		// If not in DB, check plugin's relative path on disk
		$basepath = realpath($plugin->getStoragePath() . '/templates/') . DIRECTORY_SEPARATOR;
		
		if(false == ($path = realpath($plugin->getStoragePath() . '/templates/' . $tpl_path)))
			return false;
		
		if(!DevblocksPlatform::strStartsWith($path, $basepath))
			return false;

		// Only check the DB if the template may be overridden
		if(isset($plugin->manifest_cache['templates'])) {
			foreach($plugin->manifest_cache['templates'] as $v) {
				if(0 == strcasecmp($v['path'], $tpl_path)) {
					// Check if template is overloaded in DB/cache
					$matches = DAO_DevblocksTemplate::getWhere(sprintf("plugin_id = %s AND path = %s %s",
						Cerb_ORMHelper::qstr($plugin_id),
						Cerb_ORMHelper::qstr($tpl_path),
						(!empty($tag) ? sprintf("AND tag = %s ",Cerb_ORMHelper::qstr($tag)) : "")
					));
						
					if(!empty($matches)) {
						$match = array_shift($matches); /* @var $match Model_DevblocksTemplate */
						$source = $match->content;
						$mtime = $match->last_updated;
						return true;
					}
				}
			}
		}
			
		if(false == ($source = @file_get_contents($path)))
			return false;
		
		// Check the modified timestamp
		$mtime = filemtime($path);
		
		return true;
	}
	
	protected function fetchTimestamp($name) {
		list($plugin_id, , $tpl_path) = explode(':',$name,3);
		
		if(empty($plugin_id) || empty($tpl_path))
			return false;
		
		$plugins = DevblocksPlatform::getPluginRegistry();
			
		if(null == ($plugin = ($plugins[$plugin_id] ?? null))) /* @var $plugin DevblocksPluginManifest */
			return false;
		
		// If we can overload this template through the DB, don't return an mtime (faster to do one query)
		if(isset($plugin->manifest_cache['templates']))
			return time();
		
		// Otherwise, check the mtime via the plugin's relative path on disk
		$path = $plugin->getStoragePath() . '/templates/' . $tpl_path;
		
		if(false == ($mtime = @filemtime($path)))
			return false;
		
		return $mtime;
	}
};