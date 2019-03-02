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

class PageSection_InternalSkills extends Extension_PageSection {
	function render() {}
	
	function showSkillsTabAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$skillsets = DAO_Skillset::getWithSkillsForContext($context, $context_id);
		$tpl->assign('skillsets', $skillsets);
		
		$tpl->display('devblocks:cerberusweb.core::internal/skillsets/tab_readonly.tpl');
	}
	
	function getSkillsetAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$skillset_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(false != ($skillset = DAO_Skillset::get($skillset_id))) {
			$skillset->skills = $skillset->getSkills();
			$tpl->assign('context', $context);
			$tpl->assign('context_id', $context_id);
			$tpl->assign('skillset', $skillset);
			$tpl->display('devblocks:cerberusweb.core::internal/skillsets/fieldset.tpl');
		}
	}
	
	function showSkillsChooserPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$skillsets = DAO_Skillset::getWithSkillsForContext($context, $context_id);
		$tpl->assign('skillsets', $skillsets);
		
		$tpl->display('devblocks:cerberusweb.core::internal/skillsets/chooser_popup.tpl');
	}
	
	function saveSkillsForContextAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$skills = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['skill'],'array',array()), 'int');
		
		if(empty($context) || !is_array($skills))
			return;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Check permissions on active worker
		
		if(!CerberusContexts::isWriteableByActor($context, $context_id, $active_worker))
			return;
		
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_to_skill WHERE context = %s AND context_id = %d",
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
			$db->ExecuteMaster(sprintf("INSERT INTO context_to_skill (context, context_id, skill_id, skill_level) VALUES %s",
					implode(', ', $values)
			));
		}
	}
	
	
}