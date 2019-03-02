<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupPackageLibrary extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'package_library');
		
		$tpl = DevblocksPlatform::services()->template();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_PackageLibrary');
		$defaults->id = 'package_library';
		$defaults->name = DevblocksPlatform::translateCapitalized('common.package.library');
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/package_library/index.tpl');
	}
};
