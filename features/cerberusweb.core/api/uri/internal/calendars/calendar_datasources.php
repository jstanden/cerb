<?php
class CalendarDatasource_Worklist extends Extension_CalendarDatasource {
	private function _getSeriesIdxFromPrefix($params_prefix) {
		if(!empty($params_prefix) && preg_match("#\[series\]\[(\d+)\]#", $params_prefix, $matches) && count($matches) == 2) {
			return $matches[1];
		}
		
		return null;
	}
	
	function renderConfig(Model_Calendar $calendar, $params, $params_prefix) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('calendar', $calendar);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params_prefix);
		
		if(null !== ($series_idx = $this->_getSeriesIdxFromPrefix($params_prefix)))
			$tpl->assign('series_idx', $series_idx);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, array('workspace'));
		$tpl->assign('context_mfts', $context_mfts);
		
		@$worklist_context = $params['worklist_model']['context'];
		
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
		
		@$worklist_view_id = sprintf("calendar%d_worklist", $calendar->id);
		@$worklist_model = $params['worklist_model'];
		
		if(null != ($worklist_view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $worklist_view_id)))
			C4_AbstractViewLoader::setView($worklist_view_id, $worklist_view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/datasources/worklist/config.tpl');
	}
	
	function getData(Model_Calendar $calendar, array $params=array(), $params_prefix=null, $date_range_from, $date_range_to) {
		$calendar_events = array();

		@$series_idx = $this->_getSeriesIdxFromPrefix($params_prefix);
		
		if(isset($params['worklist_model'])) {
			@$worklist_context = $params['worklist_model']['context'];
			
			if(!empty($worklist_context) && null != ($context_ext = Extension_DevblocksContext::get($worklist_context))) {
				$worklist_model = $params['worklist_model'];
				$view_id = sprintf("calendar%d_worklist%d", $calendar->id, $series_idx);
				
				if(false != ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $view_id))) {
					/* @var $view C4_AbstractView */
					
					$view->addParam(
						new DevblocksSearchCriteria($params['field_start_date'], DevblocksSearchCriteria::OPER_BETWEEN, array($date_range_from, $date_range_to)),
						$params['field_start_date']
					);
					
					$view->renderPage = 0;
					$view->renderLimit = -1;

					// [TODO] Paging
					// [TODO] Subtotals
					
					$tpl_builder = DevblocksPlatform::getTemplateBuilder();
					@$template = $params['label'];
					
					if(empty($template))
						$template = '{{_label}}';
					
					list($results, $count) = $view->getData();

					if(is_array($results))
					foreach($results as $id => $row) {
						$ts = strtotime('now', $row[$params['field_start_date']]);
						$epoch = strtotime('midnight', $row[$params['field_start_date']]);

						// [TODO] This needs to be more efficient
						CerberusContexts::getContext($context_ext->id, $id, $labels, $values);

						$calendar_events[$epoch][] = array(
							'context' => $context_ext->id,
							'context_id' => $id,
							'label' => $tpl_builder->build($template, $values),
							'color' => $params['color'],
							'ts' => $ts,
							'link' => sprintf("ctx://%s:%d",
								$context_ext->id,
								$id
							),
						);
					}
				}
			}
			
		}
		
		return $calendar_events;
	}
};

// [TODO] Convert existing workspace tabs to calendars?  Use this datasource? Aggregate?
