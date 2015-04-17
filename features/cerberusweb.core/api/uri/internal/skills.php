<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerbweb.com	    http://www.webgroupmedia.com/
 ***********************************************************************/

if(class_exists('Extension_PageSection')):
class PageSection_InternalSkills extends Extension_PageSection {
	function render() {}
	
	function showSkillsTabAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$skillsets = DAO_Skillset::getWithSkillsForContext($context, $context_id);
		$tpl->assign('skillsets', $skillsets);
		
		$tpl->display('devblocks:cerberusweb.core::internal/skillsets/tab.tpl');
	}
	
	function getSkillsetAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$skillset_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(false != ($skillset = DAO_Skillset::get($skillset_id))) {
			$skillset->skills = $skillset->getSkills();
			$tpl->assign('context', $context);
			$tpl->assign('context_id', $context_id);
			$tpl->assign('skillset', $skillset);
			$tpl->display('devblocks:cerberusweb.core::internal/skillsets/fieldset.tpl');
		}
	}
	
	function setContextSkillAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$skill_id = DevblocksPlatform::importGPC($_REQUEST['skill_id'],'integer');
		@$level = DevblocksPlatform::importGPC($_REQUEST['level'],'integer');
		
		if(empty($context))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if($level) {
			$db->Execute(sprintf("REPLACE INTO context_to_skill (context, context_id, skill_id, skill_level) ".
					"VALUES (%s, %d, %d, %d)",
					$db->qstr($context),
					$context_id,
					$skill_id,
					$level
			));
		} else {
			$db->Execute(sprintf("DELETE FROM context_to_skill WHERE context = %s AND context_id = %d AND skill_id = %d",
					$db->qstr($context),
					$context_id,
					$skill_id
			));
		}
	}
	
	function saveSkillsForContextAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$skills = DevblocksPlatform::importGPC($_REQUEST['skill'],'array');
		
		if(empty($context))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("DELETE FROM context_to_skill WHERE context = %s AND context_id = %d",
				$db->qstr($context),
				$context_id
		));
		
		$values = array();
		
		foreach($skills as $skill_id => $skill_level) {
			// Don't insert skills without a skill level
			if(empty($skill_level))
				continue;
			
			$values[] = sprintf("(%s, %d, %d, %d)",
					$db->qstr($context),
					$context_id,
					$skill_id,
					$skill_level
			);
		}
		
		if(!empty($values)) {
			$db->Execute(sprintf("INSERT INTO context_to_skill (context, context_id, skill_id, skill_level) VALUES %s",
					implode(', ', $values)
			));
		}
	}
	
	
}
endif;