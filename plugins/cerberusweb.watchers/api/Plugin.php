<?php
class ChWatchersPlugin extends DevblocksPlugin {
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class ChWatchersTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;
