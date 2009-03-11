<?php
class ChWatchersPlugin extends DevblocksPlugin {
	const WORKER_PREF_ASSIGN_EMAIL = 'watchers_assign_email';
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
