<?php
class CardWidget_ChartKata extends Extension_CardWidget {
	const ID = 'cerb.card.widget.chart.kata';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_CardWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();

		$chart_kata = DevblocksPlatform::importGPC($model->extension_params['chart_kata'] ?? '', 'string', '');
		$datasets_kata = DevblocksPlatform::importGPC($model->extension_params['datasets_kata'] ?? '', 'string', '');

		if(!trim($chart_kata) || !trim($datasets_kata)) {
			echo "(not configured)";
			return;
		}

		try {
			$chart_json = $this->_getChartJson($model, $context, $context_id, $chart_kata, $datasets_kata);

		} catch (Exception_DevblocksValidationError $e) {
			echo DevblocksPlatform::strEscapeHtml($e->getMessage());
			return;

		} catch (Throwable $e) {
			echo DevblocksPlatform::strEscapeHtml('An unexpected error occurred.');
			DevblocksPlatform::logException($e);
			return;
		}

		$tpl->assign('chart_id', sprintf('cardWidget%s', $model->getUniqueId($context_id)));
		$tpl->assign('chart_json', json_encode($chart_json));
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/chart_kata/render.tpl');
	}

	/**
	 * @throws Exception_DevblocksValidationError
	 */
	private function _getChartJson(Model_CardWidget $model, $context, $context_id, string $chart_kata, string $datasets_kata) : array {
		$active_worker = CerberusApplication::getActiveWorker();
		$worker_id = $active_worker?->id ?? 0;

		// Resolve {{record_*}} placeholders against the card's record
		$chart_dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $worker_id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $worker_id,
		]);

		$dark_mode = $worker_id ? DAO_WorkerPref::get($worker_id, 'dark_mode', 0) : 0;

		return \Cerb\Charts\ChartKataWidgetTester::buildChartJson($chart_kata, $datasets_kata, $chart_dict, $dark_mode);
	}

	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		return false;
	}

	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->assign('datasets_autocomplete_json', json_encode(CerberusApplication::kataAutocompletions()->dataset()));
		$tpl->assign('chart_autocomplete_json', json_encode(CerberusApplication::kataAutocompletions()->chart()));
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/chart/kata/config.tpl');
	}

	function invokeConfig($action, Model_CardWidget $model) {
		switch($action) {
			case 'previewChart':
				return $this->_widgetConfig_previewChart($model);
			case 'previewDataset':
				return $this->_widgetConfig_previewDataset($model);
		}
		return false;
	}

	private function _widgetConfig_previewDataset(Model_CardWidget $model) {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);

		$datasets_kata = $params['datasets_kata'] ?? '';
		$placeholders_kata = $params['placeholder_simulator_kata'] ?? '';

		\Cerb\Charts\ChartKataWidgetTester::previewDataset($datasets_kata, $placeholders_kata, $this->_getTesterInitialState($model));
	}

	private function _widgetConfig_previewChart(Model_CardWidget $model) {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);

		$datasets_kata = $params['datasets_kata'] ?? '';
		$chart_kata = $params['chart_kata'] ?? '';
		$placeholders_kata = $params['placeholder_simulator_kata'] ?? '';

		\Cerb\Charts\ChartKataWidgetTester::previewChart($chart_kata, $datasets_kata, $placeholders_kata, $this->_getTesterInitialState($model));
	}

	// Card-specific placeholder seed for the testers; the record (`record_*`) is supplied by
	// the placeholder simulator since there's no live record in the config popup.
	private function _getTesterInitialState(Model_CardWidget $model) : array {
		$active_worker = CerberusApplication::getActiveWorker();
		$worker_id = $active_worker?->id ?? 0;

		return [
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $worker_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $worker_id,
		];
	}
}
