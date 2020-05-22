<?php
class CardWidget_Sheet extends Extension_CardWidget {
	const ID = 'cerb.card.widget.sheet';
	
	function getData(Model_CardWidget $widget, $page=null, $context, $context_id, &$error=null) {
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker= CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($widget->extension_params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->extension_params['cache_secs'], 'integer', 0);
		
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
		
		if(false === ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		@$page = DevblocksPlatform::importGPC($_POST['page'], 'integer', 0);
		
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
				
				@$sheet_yaml = DevblocksPlatform::importGPC($model->extension_params['sheet_yaml'], 'string', null);
				$sheet = $sheets->parseYaml($sheet_yaml, $error);
				
				$sheets->addType('card', $sheets->types()->card());
				$sheets->addType('date', $sheets->types()->date());
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
				
				@$paging = $results['_']['paging'];
				
				if($paging) {
					$tpl->assign('paging', $paging);
				}
				
				$tpl->assign('widget_ext', $this);
				$tpl->assign('widget', $model);
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
		
		if(!array_key_exists('sheet_yaml', $model->extension_params)) {
			$model->extension_params['sheet_yaml'] = "layout:\n  style: table\n  headings: true\n  paging: true\n  #title_column: _label\ncolumns:\n- text:\n    key: id\n    label: ID\n- card:\n    key: _label\n    label: Label\n    params:\n      #image: true\n      #bold: true\n- ";
		}
		
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/sheet/config.tpl');
	}
}