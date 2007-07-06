<?php
class ChCorePatchContainer extends DevblocksPatchContainerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */

		$file_prefix = dirname(__FILE__) . '/patches/';
		
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',180,$file_prefix.'4.0.0__.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',181,$file_prefix.'4.0.0_beta.php',''));
	}
};

?>
