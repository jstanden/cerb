<?php
if (class_exists('DevblocksTranslationsExtension',true)):
class ChAuditLogTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return dirname(dirname(__FILE__)) . '/strings.xml';
	}
};
endif;
