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
			$tables = DevblocksPlatform::getDatabaseTables();
			
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
			
			if($tables) {
				$static_classes = [
					'CerberusApplication',
					'CerberusContexts',
					'CerberusLicense',
					'CerberusSettings',
					'CerberusSettingsDefaults',
					'Context_Attachment',
					'DAO_Attachment',
					'DAO_AutomationResource',
					'DAO_Address',
					'DAO_Bucket',
					'DAO_Calendar',
					'DAO_ConnectedAccount',
					'DAO_ConnectedService',
					'DAO_Contact',
					'DAO_ContactOrg',
					'DAO_ContextLink',
					'DAO_Currency',
					'DAO_CustomField',
					'DAO_CustomFieldset',
					'DAO_CustomFieldValue',
					'DAO_EmailSignature',
					'DAO_FileBundle',
					'DAO_GpgPrivateKey',
					'DAO_Group',
					'DAO_KbCategory',
					'DAO_MailHtmlTemplate',
					'DAO_MailQueue',
					'DAO_MailTransport',
					'DAO_Notification',
					'DAO_ProfileTab',
					'DAO_Ticket',
					'DAO_Toolbar',
					'DAO_TriggerEvent',
					'DAO_Worker',
					'DAO_WorkerPref',
					'DAO_WorkspacePage',
					'DAO_WorkspaceTab',
					'DevblocksDictionaryDelegate',
					'DevblocksPlatform',
					'DevblocksSearchCriteria',
					'Extension_CalendarDatasource',
					'Extension_CardWidget',
					'Extension_CustomField',
					'Extension_DevblocksContext',
					'Extension_PageMenu',
					'Extension_PageMenuItem',
					'Extension_ProfileTab',
					'Extension_ProfileWidget',
					'Extension_WorkspaceWidget',
					'Extension_WorkspaceWidgetDatasource',
					'Extension_WorkspaceTab',
					'Model_ContextActivityLogEntry',
					'Model_CustomField',
					'Model_Ticket',
					'Page_Login',
					'Page_Profiles',
				];
				
				foreach($static_classes as $static_class) {
					try {
						$instance->registerClass($static_class, $static_class);
					} catch(Exception) {}
				}
			}
			
			// Devblocks plugins
			$instance->registerPlugin('function','fetch', ['_DevblocksTemplateManager', 'function_void']);

			$instance->registerPlugin('block','devblocks_url', ['_DevblocksTemplateManager', 'block_devblocks_url']);
			$instance->registerPlugin('block','php', ['_DevblocksTemplateManager', 'block_void']);

			$instance->registerPlugin('modifier','devblocks_context_name', ['_DevblocksTemplateManager', 'modifier_devblocks_context_name']);
			$instance->registerPlugin('modifier','devblocks_date', ['_DevblocksTemplateManager', 'modifier_devblocks_date']);
			$instance->registerPlugin('modifier','devblocks_decimal', ['_DevblocksTemplateManager', 'modifier_devblocks_decimal']);
			$instance->registerPlugin('modifier','devblocks_email_quotes_cull', ['_DevblocksTemplateManager', 'modifier_devblocks_email_quotes_cull']);
			$instance->registerPlugin('modifier','devblocks_email_quote', ['_DevblocksTemplateManager', 'modifier_devblocks_email_quote']);
			$instance->registerPlugin('modifier','devblocks_hyperlinks', ['_DevblocksTemplateManager', 'modifier_devblocks_hyperlinks']);
			$instance->registerPlugin('modifier','devblocks_hideemailquotes', ['_DevblocksTemplateManager', 'modifier_devblocks_hide_email_quotes']);
			$instance->registerPlugin('modifier','devblocks_permalink', ['_DevblocksTemplateManager', 'modifier_devblocks_permalink']);
			$instance->registerPlugin('modifier','devblocks_markdown_to_html', ['_DevblocksTemplateManager', 'modifier_devblocks_markdown_to_html']);
			$instance->registerPlugin('modifier','devblocks_prettytime', ['_DevblocksTemplateManager', 'modifier_devblocks_prettytime']);
			$instance->registerPlugin('modifier','devblocks_prettybytes', ['_DevblocksTemplateManager', 'modifier_devblocks_prettybytes']);
			$instance->registerPlugin('modifier','devblocks_prettysecs', ['_DevblocksTemplateManager', 'modifier_devblocks_prettysecs']);
			$instance->registerPlugin('modifier','devblocks_prettyjson', ['_DevblocksTemplateManager', 'modifier_devblocks_prettyjson']);
			$instance->registerPlugin('modifier','devblocks_rangy_deserialize', ['_DevblocksTemplateManager', 'modifier_devblocks_rangy_deserialize']);
			$instance->registerPlugin('modifier','devblocks_translate', ['_DevblocksTemplateManager', 'modifier_devblocks_translate']);
			
			$instance->registerPlugin('modifier','array_column', 'array_column');
			$instance->registerPlugin('modifier','array_diff_key', 'array_diff_key');
			$instance->registerPlugin('modifier','array_fill', 'array_fill');
			$instance->registerPlugin('modifier','array_intersect', 'array_intersect');
			$instance->registerPlugin('modifier','array_intersect_key', 'array_intersect_key');
			$instance->registerPlugin('modifier','array_keys', ['_DevblocksTemplateManager', 'modifier_php_array_keys']);
			$instance->registerPlugin('modifier','array_key_exists', 'array_key_exists');
			$instance->registerPlugin('modifier','array_merge', 'array_merge');
			$instance->registerPlugin('modifier','array_search', 'array_search');
			$instance->registerPlugin('modifier','array_shift', ['_DevblocksTemplateManager', 'modifier_php_array_shift']);
			$instance->registerPlugin('modifier','array_slice', 'array_slice');
			$instance->registerPlugin('modifier','array_unique', 'array_unique');
			$instance->registerPlugin('modifier','boolval', 'boolval');
			$instance->registerPlugin('modifier','ceil', 'ceil');
			$instance->registerPlugin('modifier','count', 'count');
			$instance->registerPlugin('modifier','explode', 'explode');
			$instance->registerPlugin('modifier','extension_loaded', 'extension_loaded');
			$instance->registerPlugin('modifier','floatval', ['_DevblocksTemplateManager', 'modifier_php_floatval']);
			$instance->registerPlugin('modifier','floor', 'floor');
			$instance->registerPlugin('modifier','implode', 'implode');
			$instance->registerPlugin('modifier','json_encode', ['_DevblocksTemplateManager', 'modifier_php_json_encode']);
			$instance->registerPlugin('modifier','json_decode', 'json_decode');
			$instance->registerPlugin('modifier','key', 'key');
			$instance->registerPlugin('modifier','intval', 'intval');
			$instance->registerPlugin('modifier','is_a', 'is_a');
			$instance->registerPlugin('modifier','is_null', 'is_null');
			$instance->registerPlugin('modifier','is_numeric', 'is_numeric');
			$instance->registerPlugin('modifier','is_object', 'is_object');
			$instance->registerPlugin('modifier','is_string', 'is_string');
			$instance->registerPlugin('modifier','mb_ucfirst', 'mb_ucfirst');
			$instance->registerPlugin('modifier','md5', ['_DevblocksTemplateManager', 'modifier_php_md5']);
			$instance->registerPlugin('modifier','method_exists', 'method_exists');
			$instance->registerPlugin('modifier','preg_match', 'preg_match');
			$instance->registerPlugin('modifier','sort', ['_DevblocksTemplateManager', 'modifier_php_sort']);
			$instance->registerPlugin('modifier','str_replace', 'str_replace');
			$instance->registerPlugin('modifier','strcasecmp', 'strcasecmp');
			$instance->registerPlugin('modifier','strpos', 'strpos');
			$instance->registerPlugin('modifier','strtotime', 'strtotime');
			$instance->registerPlugin('modifier','substr', ['_DevblocksTemplateManager', 'modifier_php_substr']);
			$instance->registerPlugin('modifier','trim', ['_DevblocksTemplateManager', 'modifier_php_trim']);
			$instance->registerPlugin('modifier','uniqid', 'uniqid');
			
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
				'is_string',
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
					$line_count = ($quote_ended - $quote_started + 1);
					$script_id = uniqid('script');
					$link_toolbar = sprintf(
						"<div class=\"cerb-code-editor-toolbar\" style=\"display:inline-block;margin:0.5em 0;\">".
						"<button type=\"button\" class=\"cerb-code-editor-toolbar-button\">".
						"<span class=\"glyphicons glyphicons-quote\"></span> Expand quoted text (%d %s)".
						"</button></div><div class=\"cerb-email-quote\" style=\"display:none;\">",
						$line_count,
						(1 == $line_count ? 'line' : 'lines')
					);
					$link_script = sprintf(
						"<script nonce=\"%s\" id=\"%s\" type=\"text/javascript\">".
						"\$('#%s').prevAll('div.cerb-code-editor-toolbar').find('button').on('click',function(e) {".
						"e.stopPropagation();\$(this).closest('div').next('div').show();\$(this).closest('div').hide();});".
						"</script>",
						DevblocksPlatform::strEscapeHtml(DevblocksPlatform::getRequestNonce()),
						DevblocksPlatform::strEscapeHtml($script_id),
						DevblocksPlatform::strEscapeHtml($script_id),
					);
					$lines[$quote_started] = $link_toolbar . $lines[$quote_started];
					$lines[$quote_ended] = $lines[$quote_ended] . "</div>" . $link_script;
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
		
		if(!($path = realpath($plugin->getStoragePath() . '/templates/' . $tpl_path)))
			return false;
		
		if(!DevblocksPlatform::strStartsWith($path, $basepath))
			return false;

		// Only check the DB if the template may be overridden
		if(APP_OPT_DEPRECATED_PORTAL_CUSTOM_TEMPLATES && array_key_exists('templates', $plugin->manifest_cache)) {
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
			
		if(!($source = @file_get_contents($path)))
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
		if(array_key_exists('templates', $plugin->manifest_cache ?? []))
			return time();
		
		// Otherwise, check the mtime via the plugin's relative path on disk
		$path = $plugin->getStoragePath() . '/templates/' . $tpl_path;
		
		if(!($mtime = (filemtime($path) ?? 0)))
			return false;
		
		return $mtime;
	}
};