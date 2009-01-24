<?php
class CrmPatchContainer extends DevblocksPatchContainerExtension {
	
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */
		
		$file_prefix = realpath(dirname(__FILE__).'/../') . '/patches/';
		
		$this->registerPatch(new DevblocksPatch('cerberusweb.crm',6,$file_prefix.'1.0.0.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.crm',9,$file_prefix.'1.0.1.php',''));
	}
};

?>