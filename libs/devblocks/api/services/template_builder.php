<?php
class _DevblocksTemplateBuilder {
	private $_twig = null;
	private $_errors = array();
	
	private function _DevblocksTemplateBuilder() {
		$this->_twig = new Twig_Environment(new Twig_Loader_String(), array(
			'cache' => false,
			'debug' => false,
			'auto_reload' => true,
			'trim_blocks' => true,
			'autoescape' => false,
		));
		
		if(class_exists('_DevblocksTwigExtensions', true)) {
			$this->_twig->addExtension(new _DevblocksTwigExtensions());
		}
	}
	
	/**
	 * 
	 * @return _DevblocksTemplateBuilder
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _DevblocksTemplateBuilder();
		}
		return $instance;
	}

	/**
	 * @return Twig_Environment
	 */
	public function getEngine() {
		return $this->_twig;
	}
	
	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->_errors;
	}
	
	private function _setUp() {
		$this->_errors = array();
	}
	
	private function _tearDown() {
	}
	
	/**
	 * 
	 * @param string $template
	 * @param array $vars
	 * @return string
	 */
	function build($template, $vars) {
		$this->_setUp();
		try {
			$template = $this->_twig->loadTemplate($template);
			$out = $template->render($vars);
		} catch(Exception $e) {
			$this->_errors[] = $e->getMessage();
		}
		$this->_tearDown();

		if(!empty($this->_errors))
			return false;
		
		return $out;
	} 
};

if(class_exists('Twig_Extension', true)):
class _DevblocksTwigExtensions extends Twig_Extension {
	public function getName() {
		return 'devblocks_twig';
	}
	
	public function getFilters() {
		return array(
			'truncate' => new Twig_Filter_Method($this, 'filter_truncate'),
		);
	}
	
	/**
	 * https://github.com/fabpot/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Text.php
	 *  
	 * @param string $value
	 * @param integer $length
	 * @param boolean $preserve
	 * @param string $separator
	 * 
	 */
	function filter_truncate($value, $length = 30, $preserve = false, $separator = '...') {
		if (mb_strlen($value, LANG_CHARSET_CODE) > $length) {
			if ($preserve) {
				if (false !== ($breakpoint = mb_strpos($value, ' ', $length, LANG_CHARSET_CODE))) {
					$length = $breakpoint;
				}
			}
			return mb_substr($value, 0, $length, LANG_CHARSET_CODE) . $separator;
		}
		return $value;
	}	
};
endif;