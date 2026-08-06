<?php
class ProfileWidget_ChartCategories extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.categories';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'] ?? null, 'string', null);
		$xaxis_format = DevblocksPlatform::importGPC($model->extension_params['xaxis_format'] ?? null, 'string', 'label');
		$yaxis_format = DevblocksPlatform::importGPC($model->extension_params['yaxis_format'] ?? null, 'string', 'label');
		$height = DevblocksPlatform::importGPC($model->extension_params['height'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		if(false === ($results = $data->executeQuery($query, [], $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		if(!array_key_exists('data', $results))
			return;

		$columns = $results['data'] ?? [];
		$stacked = !empty($results['_']['stacked']);

		// First column = category labels; each remaining column = a numeric series aligned to categories.
		$categories = array_values($columns[0] ?? []);
		array_shift($categories); // drop the id header

		$series = [];
		for($i = 1; $i < count($columns); $i++) {
			$col = array_values($columns[$i]);
			$id = array_shift($col);
			$series[] = ['key' => $id, 'values' => array_map('floatval', $col)];
		}

		if(!$height)
			$height = 100 + (50 * count($columns[0] ?? []));

		if(($chart_meta = ($results['_'] ?? null)))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));

		$tpl->assign('categories', json_encode($categories));
		$tpl->assign('series', json_encode($series));
		$tpl->assign('stacked', $stacked);
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('height', $height ?: 320);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/categories/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/categories/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}
