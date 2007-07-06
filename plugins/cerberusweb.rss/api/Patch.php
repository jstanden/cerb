<?php
class ChFeedsPatchContainer extends DevblocksPatchContainerExtension {
    const ID = 'rss.patches';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */
		
		$file_prefix = dirname(__FILE__) . '/patches/';
		
		$this->registerPatch(new CerberusPatch('cerberusweb.rss',$rev,$file_prefix.'1.0.0.php',''));
	}
};

?>
