<?php
/**
 * Smarty Template Manager Singleton
 *
 * @ingroup services
 */
class _DevblocksTemplateManager {
	/**
	 * Constructor
	 *
	 * @private
	 */
	private function _DevblocksTemplateManager() {}
	/**
	 * Returns an instance of the Smarty Template Engine
	 *
	 * @static
	 * @return Smarty
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			define('SMARTY_RESOURCE_CHAR_SET', strtoupper(LANG_CHARSET_CODE));
			require(DEVBLOCKS_PATH . 'libs/smarty/Smarty.class.php');

			$instance = new Smarty();
			
			$instance->template_dir = APP_PATH . '/templates';
			$instance->compile_dir = APP_TEMP_PATH . '/templates_c';
			$instance->cache_dir = APP_TEMP_PATH . '/cache';

			$instance->use_sub_dirs = false;

			$instance->caching = 0;
			$instance->cache_lifetime = 0;
			$instance->compile_check = (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) ? true : false;
			$instance->debugging = (defined('SMARTY_DEBUG_MODE') && SMARTY_DEBUG_MODE) ? true : false;
			
			$instance->error_unassigned = false;
			$instance->error_reporting = E_ERROR & ~E_NOTICE;
			
			// Auto-escape HTML output
			$instance->loadFilter('variable','htmlspecialchars');
			//$instance->register->variableFilter(array('_DevblocksTemplateManager','variable_filter_esc'));
			
			// Devblocks plugins
			$instance->registerPlugin('block','devblocks_url', array('_DevblocksTemplateManager', 'block_devblocks_url'));
			$instance->registerPlugin('modifier','devblocks_date', array('_DevblocksTemplateManager', 'modifier_devblocks_date'));
			$instance->registerPlugin('modifier','devblocks_email_quote', array('_DevblocksTemplateManager', 'modifier_devblocks_email_quote'));
			$instance->registerPlugin('modifier','devblocks_hyperlinks', array('_DevblocksTemplateManager', 'modifier_devblocks_hyperlinks'));
			$instance->registerPlugin('modifier','devblocks_hideemailquotes', array('_DevblocksTemplateManager', 'modifier_devblocks_hide_email_quotes'));
			$instance->registerPlugin('modifier','devblocks_permalink', array('_DevblocksTemplateManager', 'modifier_devblocks_permalink'));
			$instance->registerPlugin('modifier','devblocks_prettytime', array('_DevblocksTemplateManager', 'modifier_devblocks_prettytime'));
			$instance->registerPlugin('modifier','devblocks_prettybytes', array('_DevblocksTemplateManager', 'modifier_devblocks_prettybytes'));
			$instance->registerPlugin('modifier','devblocks_prettysecs', array('_DevblocksTemplateManager', 'modifier_devblocks_prettysecs'));
			$instance->registerPlugin('modifier','devblocks_translate', array('_DevblocksTemplateManager', 'modifier_devblocks_translate'));
			$instance->registerResource('devblocks', array(
				array('_DevblocksSmartyTemplateResource', 'get_template'),
				array('_DevblocksSmartyTemplateResource', 'get_timestamp'),
				array('_DevblocksSmartyTemplateResource', 'get_secure'),
				array('_DevblocksSmartyTemplateResource', 'get_trusted'),
			));
		}
		return $instance;
	}

	static function modifier_devblocks_translate($string) {
		$translate = DevblocksPlatform::getTranslationService();
		
		// Variable number of arguments
		$args = func_get_args();
		array_shift($args); // pop off $string
		
		$translated = $translate->_($string);
		
		if(!empty($args))
			@$translated = vsprintf($translated, $args);
		
		return $translated;
	}
	
	static function block_devblocks_url($params, $content, $smarty, &$repeat, $template) {
		if($repeat)
			return;
		
		$url = DevblocksPlatform::getUrlService();
		
		$contents = $url->write($content, !empty($params['full']) ? true : false);
		
		if (!empty($params['assign'])) {
			$smarty->assign($params['assign'], $contents);
		} else {
			return $contents;
		}
	}
	
	static function modifier_devblocks_date($string, $format=null, $gmt=false) {
		if(empty($string))
			return '';
	
		$date = DevblocksPlatform::getDateService();
		return $date->formatTime($format, $string, $gmt);
	}
	
	static function modifier_devblocks_permalink($string) {
		return DevblocksPlatform::strToPermalink($string);
	}
	
	static function modifier_devblocks_prettytime($string, $is_delta=false) {
		return DevblocksPlatform::strPrettyTime($string, $is_delta);
	}
		
	static function modifier_devblocks_prettysecs($string, $length=0) {
		return DevblocksPlatform::strSecsToString($string, $length);
	}

	static function modifier_devblocks_prettybytes($string, $precision='0') {
		return DevblocksPlatform::strPrettyBytes($string, $precision);
	}
	
	static function modifier_devblocks_hyperlinks($string) {
		return DevblocksPlatform::strToHyperlinks($string);
	}
	
	static function modifier_devblocks_email_quote($string, $wrap_to=76) {
		$lines = DevblocksPlatform::parseCrlfString($string, true);
		$bins = array();
		$last_prefix = null;
		
		// Sort lines into bins
		foreach($lines as $i => $line) {
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
			$bail = 75000; // prevent infinite loops
		
			if(mb_strlen($prefix) == 0)
				continue;
		
			while(isset($bins[$i]['lines'][$l]) && $bail > 0) {
				$line = $bins[$i]['lines'][$l];
				$boundary = $wrap_to - mb_strlen($prefix);
				
				if(mb_strlen($line) > $boundary) {
					// Try to split on a space
					$pos = mb_strrpos($line, ' ', -1 * (mb_strlen($line)-$boundary));
					$break_word = (false === $pos);
					
					$overflow = mb_substr($line, ($break_word ? $boundary : ($pos+1)));
					
					$bins[$i]['lines'][$l] = mb_substr($line, 0, $break_word ? $boundary : $pos);
		
					// If we don't have more lines, add a new one
					if(!empty($overflow)) {
						if(isset($bins[$i]['lines'][$l+1])) {
							if(mb_strlen($bins[$i]['lines'][$l+1]) == 0) {
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
	
	static function modifier_devblocks_hide_email_quotes($string, $length=3) {
		$string = str_replace("\r\n","\n",$string);
		$string = str_replace("\r","\n",$string);
		$string = preg_replace("/\n{3,99}/", "\n\n", $string);
		$lines = explode("\n", $string);
		
		$quote_started = false;
		$last_line = count($lines) - 1;
		
		foreach($lines as $idx => $line) {
			$quote_ended = false;
			
			// Check if the line starts with a > before any content
			if(preg_match('#^\s*(\>|\&gt;)#', $line)) {
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
			
			if($quote_started && $quote_ended) {
				if($quote_ended - $quote_started >= $length) {
					$lines[$quote_started] = "<div style='margin:5px;'><a href='javascript:;' style='background-color:rgb(255,255,204);' onclick=\"$(this).closest('div').next('div').toggle();$(this).parent().fadeOut();\">-show quote-</a></div><div class='hidden' style='display:none;font-style:italic;color:rgb(66,116,62);'>" . $lines[$quote_started];
					$lines[$quote_ended] = $lines[$quote_ended]."</div>";
				}
				$quote_started = false;
			}
		}
		
		return implode("\n", $lines);
	}
};

class _DevblocksSmartyTemplateResource {
	static function get_template($tpl_name, &$tpl_source, $smarty_obj) {
		list($plugin_id, $tag, $tpl_path) = explode(':',$tpl_name,3);
		
		if(empty($plugin_id) || empty($tpl_path))
			return false;
			
		$plugins = DevblocksPlatform::getPluginRegistry();
			
		if(null == ($plugin = @$plugins[$plugin_id])) /* @var $plugin DevblocksPluginManifest */
			return false;

		// Only check the DB if the template may be overridden
		// [TODO] Alternatively, keep a cache of override paths
		if(isset($plugin->manifest_cache['templates'])) {
			foreach($plugin->manifest_cache['templates'] as $k => $v) {
				if(0 == strcasecmp($v['path'], $tpl_path)) {
					// [TODO] Use cache
					// Check if template is overloaded in DB/cache
					$matches = DAO_DevblocksTemplate::getWhere(sprintf("plugin_id = %s AND path = %s %s",
						C4_ORMHelper::qstr($plugin_id),
						C4_ORMHelper::qstr($tpl_path),
						(!empty($tag) ? sprintf("AND tag = %s ",C4_ORMHelper::qstr($tag)) : "")
					));
						
					if(!empty($matches)) {
						$match = array_shift($matches); /* @var $match Model_DevblocksTemplate */
						$tpl_source = $match->content;
						return true;
					}
				}
			}
		}
			
		// If not in DB, check plugin's relative path on disk
		$path = APP_PATH . '/' . $plugin->dir . '/templates/' . $tpl_path;
		
		if(!file_exists($path))
			return false;
		
		$tpl_source = file_get_contents($path);
		return true;
	}
	
	static function get_timestamp($tpl_name, &$tpl_timestamp, $smarty_obj) { /* @var $smarty_obj Smarty */
		list($plugin_id, $tag, $tpl_path) = explode(':',$tpl_name,3);
		
		if(empty($plugin_id) || empty($tpl_path))
			return false;
		
		$plugins = DevblocksPlatform::getPluginRegistry();
			
		if(null == ($plugin = @$plugins[$plugin_id])) /* @var $plugin DevblocksPluginManifest */
			return false;
			
		// Only check the DB if the template may be overridden
		// [TODO] Alternatively, keep a cache of override paths
		if(isset($plugin->manifest_cache['templates'])) {
			foreach($plugin->manifest_cache['templates'] as $k => $v) {
				if(0 == strcasecmp($v['path'], $tpl_path)) {
					// Check if template is overloaded in DB/cache
					$matches = DAO_DevblocksTemplate::getWhere(sprintf("plugin_id = %s AND path = %s %s",
						C4_ORMHelper::qstr($plugin_id),
						C4_ORMHelper::qstr($tpl_path),
						(!empty($tag) ? sprintf("AND tag = %s ",C4_ORMHelper::qstr($tag)) : "")
					));
			
					if(!empty($matches)) {
						$match = array_shift($matches); /* @var $match Model_DevblocksTemplate */
						//echo time(),"==(DB)",$match->last_updated,"<BR>";
						$tpl_timestamp = $match->last_updated;
						return true;
					}
				}
			}
		}
			
		// If not in DB, check plugin's relative path on disk
		$path = APP_PATH . '/' . $plugin->dir . '/templates/' . $tpl_path;
		
		if(!file_exists($path))
			return false;
		
		$stat = stat($path);
		$tpl_timestamp = $stat['mtime'];
//		echo time(),"==(DISK)",$stat['mtime'],"<BR>";
		return true;
	}
	
	static function get_secure($tpl_name, &$smarty_obj) {
		return false;
	}
	
	static function get_trusted($tpl_name, &$smarty_obj) {
		// not used
	}
};