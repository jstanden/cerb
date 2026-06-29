<?php
namespace Cerb\Charts;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksValidationError;

/**
 * Shared config-time "testers" for the chart/KATA widgets (card, profile, workspace).
 *
 * The only thing that varies per widget family is the placeholder dictionary seeded for
 * resolution, so each widget builds its own `$initial_state` and hands it here.
 */
class ChartKataWidgetTester {
	/**
	 * The shared parse -> formatTree -> dataset -> chart pipeline used by both the live
	 * render paths and the chart tester.
	 *
	 * @throws Exception_DevblocksValidationError
	 */
	public static function buildChartJson(string $chart_kata, string $datasets_kata, DevblocksDictionaryDelegate $chart_dict, int $dark_mode=0) : array {
		$chart = DevblocksPlatform::services()->chart();
		$dataset = DevblocksPlatform::services()->dataset();
		$kata = DevblocksPlatform::services()->kata();

		$error = null;

		if(!($chart_kata = $kata->parse($chart_kata, $error)))
			throw new Exception_DevblocksValidationError($error);

		if(!($chart_kata = $kata->formatTree($chart_kata, $chart_dict, $error)))
			throw new Exception_DevblocksValidationError($error);

		if(false === ($datasets_kata = $dataset->parse($datasets_kata, $chart_dict, $error)))
			throw new Exception_DevblocksValidationError($error);

		$chart_options = [
			'dark_mode' => $dark_mode,
		];

		if(!$chart_json = $chart->parse($chart_kata, $datasets_kata, $chart_options, $error))
			throw new Exception_DevblocksValidationError($error);

		return $chart_json;
	}

	/**
	 * Dataset tester: resolve the datasets against the simulated placeholders and echo the
	 * resulting JSON (or an `{error:...}` payload). Click-search series are stripped from output.
	 */
	public static function previewDataset(string $datasets_kata, string $placeholders_kata, array $initial_state) : void {
		$dataset = DevblocksPlatform::services()->dataset();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$chart_dict = self::_buildSimulatedDict($placeholders_kata, $initial_state);

		$error = null;

		if(!($datasets = $dataset->parse($datasets_kata, $chart_dict, $error))) {
			echo DevblocksPlatform::strFormatJson([
				'error' => 'ERROR: ' . $error,
			]);
			return;
		}

		// We don't need to show click series meta in the results
		foreach($datasets as $dataset_key => $dataset_series) {
			$datasets[$dataset_key] = array_filter($dataset_series, function($k) {
				return !DevblocksPlatform::strEndsWith($k, '__click');
			}, ARRAY_FILTER_USE_KEY);
		}

		echo DevblocksPlatform::strFormatJson($datasets);
	}

	/**
	 * Chart tester: resolve the chart + datasets against the simulated placeholders and render
	 * the chart preview HTML (or the escaped validation error message).
	 */
	public static function previewChart(string $chart_kata, string $datasets_kata, string $placeholders_kata, array $initial_state, ?string $chart_id=null) : void {
		$tpl = DevblocksPlatform::services()->template();

		$chart_dict = self::_buildSimulatedDict($placeholders_kata, $initial_state);

		try {
			$chart_json = self::buildChartJson($chart_kata, $datasets_kata, $chart_dict);

		} catch(Exception_DevblocksValidationError $e) {
			echo DevblocksPlatform::strEscapeHtml($e->getMessage());
			return;

		} catch(\Throwable $e) {
			echo DevblocksPlatform::strEscapeHtml('An unexpected error occurred.');
			DevblocksPlatform::logException($e);
			return;
		}

		$tpl->assign('chart_id', $chart_id);
		$tpl->assign('chart_json', json_encode($chart_json));
		$tpl->display('devblocks:cerberusweb.core::internal/chart_kata/render.tpl');
	}

	/**
	 * Merge the simulated placeholders (parsed from the simulator KATA) on top of a widget's
	 * `$initial_state` and return a dictionary for resolution.
	 */
	private static function _buildSimulatedDict(string $placeholders_kata, array $initial_state) : DevblocksDictionaryDelegate {
		$kata = DevblocksPlatform::services()->kata();

		$error = null;

		if($placeholders_kata) {
			if(false !== ($placeholders = $kata->parse($placeholders_kata, $error))) {
				if(false !== ($placeholders = $kata->formatTree($placeholders, DevblocksDictionaryDelegate::instance([]), $error)))
					$initial_state = array_merge($initial_state, $placeholders);
			}
		}

		return DevblocksDictionaryDelegate::instance($initial_state);
	}
}
