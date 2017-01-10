<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_SetupSkills extends Extension_PageSection {
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		
		@array_shift($stack); // config
		@array_shift($stack); // skills
		
		if(false != (@$tab = array_shift($stack)))
			$tpl->assign('tab', $tab);
		
		$visit->set(ChConfigurationPage::ID, 'skills');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/skills/index.tpl');
	}

	function showSkillsetsTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Skillset');
		$defaults->id = 'setup_skillsets';
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Skillset::NAME;
		$defaults->renderSortAsc = true;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		
		$tpl->assign('view', $view);
		
		$tpl->display("devblocks:cerberusweb.core::internal/views/search_and_view.tpl");
	}
	
	function showSkillsTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Skill');
		$defaults->id = 'setup_skills';
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Skill::NAME;
		$defaults->renderSortAsc = true;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		
		$tpl->assign('view', $view);
		
		$tpl->display("devblocks:cerberusweb.core::internal/views/search_and_view.tpl");
	}
};