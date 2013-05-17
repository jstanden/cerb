<?php
if(class_exists('Extension_WorkspaceTab')):
class WorkspaceTab_Calendar extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);

		$this->_showCalendarTab($page, $tab);
	}
	
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, array('workspace'));
		$tpl->assign('context_mfts', $context_mfts);

		@$worklist_context = $tab->params['worklist_model']['context'];
		
		// Load the initial fields from the context
		
		if(!empty($worklist_context)) {
			if(null != ($ctx = Extension_DevblocksContext::get($worklist_context))) {
				if(null != ($view_class = $ctx->getViewClass())) { /* @var $view_class C4_AbstractView */
					if(null != ($view = new $view_class))
						$tpl->assign('ctx_fields', $view->getFields());
				}
				
				CerberusContexts::getContext($ctx->id, null, $labels, $values, null, true);
				$tpl->assign('placeholders', $labels);
			}
		}
		
		// Prime the worklist view
		
		@$worklist_view_id = sprintf("workspace_tab%d_worklist", $tab->id);
		@$worklist_model = $tab->params['worklist_model'];
		
		if(null != ($worklist_view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $view_id)))
			C4_AbstractViewLoader::setView($worklist_view_id, $worklist_view);
		
		// Render template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/calendar/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array');

		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$params['worklist_model'] = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
		}
		
		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
	}
	
	private function _showCalendarTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year);

		// [TODO] The following code can probably be shared with Extension_CalendarDatasourceWorklist
		
		// Load the events
		
		$calendar_events = array();
		
		if(isset($tab->params['worklist_model'])) {
			@$worklist_context = $tab->params['worklist_model']['context'];
			
			if(!empty($worklist_context) && null != ($context_ext = Extension_DevblocksContext::get($worklist_context))) {
				$worklist_model = $tab->params['worklist_model'];
				$view_id = sprintf("workspace_tab%d_worklist", $tab->id);
				
				if(false != ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $view_id))) {
					/* @var $view C4_AbstractView */
					
					$view->addParam(
						new DevblocksSearchCriteria($tab->params['field_start_date'], DevblocksSearchCriteria::OPER_BETWEEN, array($calendar_properties['date_range_from'], $calendar_properties['date_range_to'])),
						$tab->params['field_start_date']
					);
					
					$view->renderPage = 0;
					$view->renderLimit = -1;

					// [TODO] Paging
					// [TODO] Subtotals
					
					$tpl_builder = DevblocksPlatform::getTemplateBuilder();
					@$template = $tab->params['label'];
					
					if(empty($template))
						$template = '{{_label}}';
					
					list($results, $count) = $view->getData();
					
					foreach($results as $id => $row) {
						$epoch = strtotime('midnight', $row[$tab->params['field_start_date']]);

						// [TODO] This needs to be more efficient
						CerberusContexts::getContext($context_ext->id, $id, $labels, $values);

						$calendar_events[$epoch][$id] = array(
							'id' => $id,
							'context' => @$worklist_context,
							'label' => $tpl_builder->build($template, $values),
						);
					}
				}
			}
			
		}
		
		// Template scope
		$tpl->assign('calendar_properties', $calendar_properties);
		$tpl->assign('calendar_events', $calendar_events);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/calendar/tab.tpl');
	}
}
endif;