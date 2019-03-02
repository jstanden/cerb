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

class PageSection_SetupCustomFields extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:cerberusweb.core::configuration/section/fields/index.tpl');
	}
	
	function showFieldsTabAction() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'fields');
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_CustomField');
		$defaults->id = 'cfg_fields';
		$defaults->view_columns = [
			SearchFields_CustomField::NAME,
			SearchFields_CustomField::CONTEXT,
			SearchFields_CustomField::TYPE,
			SearchFields_CustomField::UPDATED_AT,
		];
		$defaults->renderSubtotals = SearchFields_CustomField::CONTEXT;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		
		$view->addParamsRequired(
			[
				new DevblocksSearchCriteria(SearchFields_CustomField::CUSTOM_FIELDSET_ID, '=', 0)
			],
			true
		);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showFieldsetsTabAction() {
		$tpl = DevblocksPlatform::services()->template();

		$defaults = C4_AbstractViewModel::loadFromClass('View_CustomFieldset');
		$defaults->id = 'cfg_fieldsets';
		$defaults->renderSubtotals = SearchFields_CustomFieldset::CONTEXT;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
};