<?php
class ChRestPlugin extends DevblocksPlugin {
	const PLUGIN_ID = 'cerberusweb.controller.rest';
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class ChWebApiTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;
