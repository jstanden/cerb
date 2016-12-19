<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
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

if(class_exists('Extension_PageSection')):
class PageSection_InternalCustomFieldsets extends Extension_PageSection {
	function render() {}
	
	function showCustomFieldsetPeekAction() {
		// [TODO] Check permissions
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('view_id', $view_id);
		$tpl->assign('layer', $layer);
		
		// Model
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		if($id && null != ($custom_fieldset = DAO_CustomFieldset::get($id))) {
			$tpl->assign('custom_fieldset', $custom_fieldset);
			
			$custom_fields = $custom_fieldset->getCustomFields();
			$tpl->assign('custom_fields', $custom_fields);
			
		} else {
			@$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'string','');
			@$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'],'integer',0);
		
			$custom_fieldset = new Model_CustomFieldset();
			$custom_fieldset->id = 0;
			$custom_fieldset->owner_context = !empty($owner_context) ? $owner_context : '';
			$custom_fieldset->owner_context_id = $owner_context_id;
			
			$tpl->assign('custom_fieldset', $custom_fieldset);
		}
		
		// Contexts
		
		$contexts = Extension_DevblocksContext::getAll(false, array('custom_fields'));
		$tpl->assign('contexts', $contexts);
		
		$link_contexts = Extension_DevblocksContext::getAll(false, array('workspace'));
		$tpl->assign('link_contexts', $link_contexts);
		
		// Owner
		
		$owners_menu = Extension_DevblocksContext::getOwnerTree();
		$tpl->assign('owners_menu', $owners_menu);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fieldsets/peek.tpl');
	}
	
	function saveCustomFieldsetPeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$custom_fieldset_id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'integer', 0);
		
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'], 'array', array());
		@$types = DevblocksPlatform::importGPC($_REQUEST['types'], 'array', array());
		@$names = DevblocksPlatform::importGPC($_REQUEST['names'], 'array', array());
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		@$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'], 'array', array());
		
		// Check permissions
		
		if(!empty($custom_fieldset_id)) {
			if(
				false == ($custom_fieldset = DAO_CustomFieldset::get($custom_fieldset_id))
				|| !Context_CustomFieldset::isWriteableByActor($custom_fieldset, $active_worker)
			)
			return;
			
			$context = $custom_fieldset->context;
		}
		
		// Delete
		
		if($do_delete && $custom_fieldset) {
			DAO_CustomFieldset::delete($custom_fieldset->id);
			return;
		}
		
		// Owner
		
		@list($owner_context, $owner_context_id) = explode(':', $owner);
	
		switch($owner_context) {
			case CerberusContexts::CONTEXT_APPLICATION:
			case CerberusContexts::CONTEXT_ROLE:
			case CerberusContexts::CONTEXT_GROUP:
			case CerberusContexts::CONTEXT_BOT:
			case CerberusContexts::CONTEXT_WORKER:
				break;
				
			default:
				$owner_context = null;
				$owner_context_id = null;
				break;
		}
		
		if(empty($owner_context))
			return;
		
		// Create field set
		if(empty($custom_fieldset_id)) {
			$fields = array(
				DAO_CustomFieldset::NAME => $name,
				DAO_CustomFieldset::CONTEXT => $context,
				DAO_CustomFieldset::OWNER_CONTEXT => $owner_context,
				DAO_CustomFieldset::OWNER_CONTEXT_ID => $owner_context_id,
			);
			$custom_fieldset_id = DAO_CustomFieldset::create($fields);
			
			// View marquee
			if(!empty($custom_fieldset_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $custom_fieldset_id);
			}
			
		// Update field set
		} else {
			$fields = array(
				DAO_CustomFieldset::NAME => $name,
				DAO_CustomFieldset::OWNER_CONTEXT => $owner_context,
				DAO_CustomFieldset::OWNER_CONTEXT_ID => $owner_context_id,
			);
			DAO_CustomFieldset::update($custom_fieldset_id, $fields);
			
		}
		
		foreach($ids as $idx => $id) {
			if(
				!isset($types[$idx])
				|| !isset($names[$idx])
				|| empty($names[$idx])
			)
				continue;
			
			// Handle field deletion
			if(isset($deletes[$idx]) && !empty($deletes[$idx])) {
				if(empty($id)) {
					continue;
					
				} else {
					if(null == ($cfield = DAO_CustomField::get($id)))
						continue;

					// If we have permission to delete fields
					if(
						$active_worker->is_superuser
						|| ($cfield->custom_fieldset_id == $custom_fieldset->id
							&& Context_CustomFieldset::isWriteableByActor($custom_fieldset, $active_worker))
					)
						DAO_CustomField::delete($id);
					
					continue;
				}
			}
			
			$fields = array(
				DAO_CustomField::NAME => $names[$idx],
				DAO_CustomField::POS => $idx,
			);
			
			if(isset($params[$id]['options']))
				$params[$id]['options'] = DevblocksPlatform::parseCrlfString($params[$id]['options']);
			
			if(isset($params[$id]))
				$fields[DAO_CustomField::PARAMS_JSON] = json_encode($params[$id]);
			else
				$fields[DAO_CustomField::PARAMS_JSON] = json_encode(array());
			
			// Create field
			if(empty($id) || !is_numeric($id)) {
				$fields[DAO_CustomField::CONTEXT] = $context;
				$fields[DAO_CustomField::CUSTOM_FIELDSET_ID] = $custom_fieldset_id;
				$fields[DAO_CustomField::TYPE] = $types[$idx];
				
				DAO_CustomField::create($fields);
				
			// Modify field
			} else {
				DAO_CustomField::update($id, $fields);
			}
		}
	}
	
	function showTabCustomFieldsetsAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('owner_context', $context);
		$tpl->assign('owner_context_id', $context_id);
		
		$view_id = str_replace('.','_',$point) . '_cfield_sets';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_CUSTOM_FIELDSET);
			$view = $ctx->getChooserView($view_id);
		}
		
		if($active_worker->is_superuser && 0 == strcasecmp($context, 'all')) {
			$view->addParamsRequired(array(), true);
			
		} else {
			$view->addParamsRequired(array(
				SearchFields_CustomFieldset::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
				SearchFields_CustomFieldset::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
			), true);
		}
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function getCustomFieldSetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$bulk = DevblocksPlatform::importGPC($_REQUEST['bulk'], 'integer', 0);
		@$field_wrapper = DevblocksPlatform::importGPC($_REQUEST['field_wrapper'], 'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();
		
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
endif;