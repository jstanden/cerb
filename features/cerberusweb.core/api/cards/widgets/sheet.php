<?php
class CardWidget_Sheet extends Extension_CardWidget {
	const ID = 'cerb.card.widget.sheet';
	
	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($action) {
			case 'renderToolbar':
				return $this->_cardWidgetAction_renderToolbar($model);
		}
		
		return false;
	}
	
	function getData(Model_CardWidget $widget, $page, $context, $context_id, &$error=null) {
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker= CerberusApplication::getActiveWorker();
		
		$data_query = DevblocksPlatform::importGPC($widget->extension_params['data_query'] ?? null, 'string', null);
		$cache_secs = DevblocksPlatform::importGPC($widget->extension_params['cache_secs'] ?? null, 'integer', 0);
		
		if($page) {
			$data_query .= sprintf(' page:%d', $page);
		}
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $widget->id,
			'record__context' => $context,
			'record_id' => $context_id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false === ($results = $data->executeQuery($query, $dict->getDictionary(), $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		$page = DevblocksPlatform::importGPC($_POST['page'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$error = null;
		
		if(false == ($results = $this->getData($model, $page, $context, $context_id, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$format = DevblocksPlatform::strLower(@$results['_']['format']);
		
		if(!in_array($format, ['dictionaries'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in one of the following formats: dictionaries.");
			return;
		}
		
		switch($format) {
			case 'dictionaries':
				$sheets = DevblocksPlatform::services()->sheet();
				
				$sheet_kata = DevblocksPlatform::importGPC($model->extension_params['sheet_kata'] ?? null, 'string', null);
				$sheet = $sheets->parse($sheet_kata, $error);
				
				$sheets->addType('card', $sheets->types()->card());
				$sheets->addType('date', $sheets->types()->date());
				$sheets->addType('selection', $sheets->types()->selection());
				$sheets->addType('icon', $sheets->types()->icon());
				$sheets->addType('link', $sheets->types()->link());
				$sheets->addType('search', $sheets->types()->search());
				$sheets->addType('search_button', $sheets->types()->searchButton());
				$sheets->addType('slider', $sheets->types()->slider());
				$sheets->addType('text', $sheets->types()->text());
				$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
				$sheets->setDefaultType('text');
				
				$sheet_dicts = $results['data'];
				
				$layout = $sheets->getLayout($sheet);
				$tpl->assign('layout', $layout);
				
				$rows = $sheets->getRows($sheet, $sheet_dicts);
				$tpl->assign('rows', $rows);
				
				$columns = $sheets->getColumns($sheet);
				$tpl->assign('columns', $columns);
				
				$paging = $results['_']['paging'] ?? null;
				
				if($paging) {
					$tpl->assign('paging', $paging);
				}
				
				$tpl->assign('widget_ext', $this);
				$tpl->assign('widget', $model);
				$tpl->assign('card_context', $context);
				$tpl->assign('card_context_id', $context_id);
				
				if($layout['style'] == 'fieldsets') {
					$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/sheet/render_fieldsets.tpl');
				} else {
					$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/sheet/render.tpl');
				}
				break;
		}
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!array_key_exists('data_query', $model->extension_params)) {
			$model->extension_params['data_query'] = "type:worklist.records\nof:ticket\nexpand: [custom_,]\nquery:(\n  status:o\n  limit:10\n  sort:[id]\n)\nformat:dictionaries";
		}
		
		if(!array_key_exists('sheet_kata', $model->extension_params)) {
			$model->extension_params['sheet_kata'] = "layout:\n  style: table\n  headings@bool: yes\n  paging@bool: yes\n  #title_column: _label\n\ncolumns:\n  text/id:\n    label: ID\n\n  card/_label:\n    label: Label\n    params:\n      #image@bool: yes\n      #bold@bool: yes\n  ";
		}
		
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/sheet/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		switch($action) {
			case 'previewToolbar':
				return $this->_cardWidgetConfigAction_previewToolbar($model);
		}
		
		return false;
	}
	
	function saveConfig(array $fields, $id, &$error=null) {
		$kata = DevblocksPlatform::services()->kata();
		
		if(false == (@$json = json_decode($fields[DAO_CardWidget::EXTENSION_PARAMS_JSON], true)))
			return true;
		
		if(array_key_exists('sheet_kata', $json)) {
			if(false === $kata->validate($json['sheet_kata'], CerberusApplication::kataSchemas()->sheet(), $error)) {
				$error = 'Sheet: ' . $error;
				return false;
			}
		}
		
		if(array_key_exists('toolbar_kata', $json)) {
			if(false === $kata->validate($json['toolbar_kata'], CerberusApplication::kataSchemas()->interactionToolbar(), $error)) {
				$error = 'Toolbar: ' . $error;
				return false;
			}
		}
		
		return true;
	}
	
	private function _cardWidgetConfigAction_previewToolbar(Model_CardWidget $widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$toolbar_kata = DevblocksPlatform::importGPC($_POST['params']['toolbar_kata'] ?? null, 'string', '');
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.cardWidget.sheet',
			
			'record__context' => null,
			'record_id' => null,
			
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $widget->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
			
			'row_selections' => [],
		]);
		
		if(false == ($toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)))
			return;
		
		$tpl->assign('toolbar', $toolbar);
		$tpl->display('devblocks:devblocks.core::ui/toolbar/preview.tpl');
	}
	
	function renderToolbar(Model_CardWidget $widget, $record_context_id, $row_selections=[]) {
		$ui = DevblocksPlatform::services()->ui();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.cardWidget.sheet',
			
			'record__context' => $widget->record_type,
			'record_id' => $record_context_id,
			
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $widget->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
			
			'row_selections' => $row_selections,
		]);
		
		if(false != ($toolbar_kata = @$widget->extension_params['toolbar_kata'])) {
			$toolbar = $ui->toolbar()->parse($toolbar_kata, $toolbar_dict);
			
			$ui->toolbar()->render($toolbar);
		}
	}
	
	private function _cardWidgetAction_renderToolbar(Model_CardWidget $widget) {
		$row_selections = DevblocksPlatform::importGPC($_POST['row_selections'] ?? null, 'array', []);
		$card_context_id = DevblocksPlatform::importGPC($_POST['card_context_id'] ?? null, 'integer', null);
		
		$this->renderToolbar($widget, $card_context_id, $row_selections);
	}
}