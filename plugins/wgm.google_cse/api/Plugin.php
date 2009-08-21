<?php
if (class_exists('DevblocksTranslationsExtension',true)):
	class WgmGoogleCSETranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;

if (class_exists('DevblocksPatchContainerExtension',true)):
class WgmGoogleCSEPatchContainer extends DevblocksPatchContainerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */
		
		$file_prefix = dirname(dirname(__FILE__)) . '/patches/';
		
		$this->registerPatch(new DevblocksPatch('wgm.google_cse',1,$file_prefix.'1.0.0.php',''));
	}
};
endif;