<?php /** @noinspection PhpUnused */
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
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/fields/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch($action) {
				case 'showFieldsTab':
					return $this->_configAction_showFieldsTab();
				case 'showFieldsetsTab':
					return $this->_configAction_showFieldsetsTab();
			}
		}
		return false;
	}
	
	private function _configAction_showFieldsTab() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
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
	
	private function _configAction_showFieldsetsTab() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_CustomFieldset');
		$defaults->id = 'cfg_fieldsets';
		$defaults->renderSubtotals = SearchFields_CustomFieldset::CONTEXT;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
}