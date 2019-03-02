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

class PageSection_InternalCustomFieldsets extends Extension_PageSection {
	function render() {}
	
	function getCustomFieldSetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$bulk = DevblocksPlatform::importGPC($_REQUEST['bulk'], 'integer', 0);
		@$field_wrapper = DevblocksPlatform::importGPC($_REQUEST['field_wrapper'], 'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('bulk', !empty($bulk) ? true : false);
		
		if(empty($id))
			return;
		
		if(!empty($field_wrapper))
			$tpl->assign('field_wrapper', $field_wrapper);
		
		if(null == ($custom_fieldset = DAO_CustomFieldset::get($id)))
			return;
		
		if(!Context_CustomFieldset::isReadableByActor($custom_fieldset, $active_worker))
			return;
		
		$tpl->assign('custom_fieldset', $custom_fieldset);
		$tpl->assign('custom_fieldset_is_new', true);
		
		// If we're drawing the fieldset for a VA action, include behavior and event meta
		if($trigger_id && false !== ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			$event = $trigger->getEvent();
			$values_to_contexts = $event->getValuesContexts($trigger);
			
			$tpl->assign('trigger', $trigger);
			$tpl->assign('values_to_contexts', $values_to_contexts);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fieldsets/fieldset.tpl');
	}
}