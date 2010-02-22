<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class ChCorePatchContainer extends DevblocksPatchContainerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */

		$file_prefix = dirname(dirname(__FILE__)) . '/patches/';
		
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',180,$file_prefix.'4.0.0__.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',555,$file_prefix.'4.0.0_beta.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',809,$file_prefix.'4.0.0_rc1.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',826,$file_prefix.'4.1.0.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',827,$file_prefix.'4.1.1.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',830,$file_prefix.'4.2.0.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',832,$file_prefix.'4.2.1.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',834,$file_prefix.'4.2.3.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',836,$file_prefix.'4.3.0.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',837,$file_prefix.'4.3.1.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.core',842,$file_prefix.'5.0.0.php',''));
	}
};
