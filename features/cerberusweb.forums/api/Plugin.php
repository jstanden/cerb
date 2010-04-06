<?php
class ChForumsPlugin {
	const ID = 'cerberusweb.forums';
	const SETTING_POSTER_WORKERS = 'forums.forum_workers';
};

class ChForumsTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return dirname(dirname(__FILE__)) . '/strings.xml';
	}
};
