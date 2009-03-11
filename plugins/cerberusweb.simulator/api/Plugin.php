<?php
class ChSimulatorPlugin extends DevblocksPlugin {
	
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class ChSimulatorTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;
