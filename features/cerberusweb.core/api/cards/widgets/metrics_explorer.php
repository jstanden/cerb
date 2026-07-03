<?php
class CardWidget_MetricsExplorer extends Extension_CardWidget {
	const ID = 'cerb.card.widget.metrics.explorer';

	// Aggregate functions the explorer exposes (a curated subset of metrics.timeseries).
	// The faceted_* variants aggregate across a metric's dimension rows per bin (sum-of-mins, etc.).
	const FUNCTIONS = ['count', 'sum', 'avg', 'min', 'max', 'distinct', 'faceted_average', 'faceted_min', 'faceted_max'];

	// period -> metrics.timeseries `period:` keyword
	const PERIODS = ['minute', 'hour', 'day'];

	// Cap dimension-value autocomplete payloads (Menu virtualizes, but JSON size is real)
	const DIMENSION_VALUES_LIMIT = 1000;

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_CardWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();

		// The record this card is rendered on (a metric, or any other record type)
		$current_metric = ($context == CerberusContexts::CONTEXT_METRIC)
			? DAO_Metric::get($context_id)
			: null;

		// Defaults from the widget config
		$default_range = DevblocksPlatform::importGPC($model->extension_params['range'] ?? null, 'string', '-24 hours to now');
		$default_period = DevblocksPlatform::importGPC($model->extension_params['period'] ?? null, 'string', 'hour');
		$series_kata = DevblocksPlatform::importGPC($model->extension_params['series_kata'] ?? null, 'string', '');

		if(!in_array($default_period, self::PERIODS))
			$default_period = 'hour';

		// Default series defined in config (with placeholders), seeded into the explorer
		$config_series = $this->_parseSeriesKata($series_kata);

		// Resolve each series' `hidden` against the current record (supports `{{record_type == 'gauge'}}`)
		$dict = $this->_recordDict($model, $context, $context_id);
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$string = DevblocksPlatform::services()->string();

		foreach($config_series as &$spec) {
			$raw = (string)($spec['hidden_raw'] ?? '');
			unset($spec['hidden_raw']);
			$spec['hidden'] = ($raw === '') ? false : $string->toBool($tpl_builder->build($raw, $dict));
		}
		unset($spec);

		// All metrics for the series chooser (dimensions lazy-loaded per metric via `metricMeta`)
		$metrics = [];

		foreach(DAO_Metric::getAll() as $metric) { /* @var $metric Model_Metric */
			$metrics[] = [
				'name' => $metric->name,
				'type' => $metric->type,
				'description' => $metric->description,
			];
		}

		$bootstrap = [
			'widget_id' => $model->id,
			'uniqid' => $model->getUniqueId($context_id),
			'record__context' => $context,
			'record_id' => $context_id,
			'current_metric' => $current_metric ? [
				'name' => $current_metric->name,
				'type' => $current_metric->type,
				'dimensions' => $this->_formatDimensions($current_metric),
			] : null,
			'metrics' => $metrics,
			'functions' => self::FUNCTIONS,
			'config_series' => $config_series,
			'defaults' => [
				'range' => $default_range,
				'period' => $default_period,
			],
		];

		$tpl->assign('widget', $model);
		$tpl->assign('uniqid', $model->getUniqueId($context_id));
		$tpl->assign('bootstrap_json', json_encode($bootstrap));
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/metrics/explorer/render.tpl');
	}

	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		switch($action) {
			case 'query':
				return $this->_invokeQuery($model);
			case 'metricMeta':
				return $this->_invokeMetricMeta($model);
			case 'dimensionValues':
				return $this->_invokeDimensionValues($model);
		}

		return false;
	}

	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/metrics/explorer/config.tpl');
	}

	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}

	function saveConfig(array $fields, $id, &$error=null) {
		$params = json_decode($fields[DAO_CardWidget::EXTENSION_PARAMS_JSON] ?? '', true) ?: [];
		$series_kata = trim($params['series_kata'] ?? '');

		if($series_kata !== '') {
			$kata = DevblocksPlatform::services()->kata();

			if(false === $kata->validate($series_kata, CerberusApplication::kataSchemas()->metricsExplorerSeries(), $error)) {
				$error = 'Default series: ' . $error;
				return false;
			}
		}

		return true;
	}

	// === AJAX actions ========================================================

	private function _invokeQuery(Model_CardWidget $model) {
		$http = DevblocksPlatform::services()->http();
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$http->setHeader('Content-Type', 'application/json; charset=utf-8');

		$range = DevblocksPlatform::importGPC($_POST['range'] ?? null, 'string', '-24 hours to now');
		$period = DevblocksPlatform::importGPC($_POST['period'] ?? null, 'string', 'hour');
		$series_json = DevblocksPlatform::importGPC($_POST['series'] ?? null, 'string', '');

		$series_specs = json_decode($series_json, true) ?: [];

		// The single combined metrics.timeseries KATA (what `Copy Data Query` copies)
		$data_query = $this->_buildTimeseriesKata($series_specs, $range, $period);

		// The Chart KATA pair that drives rendering + the `Copy chart widget` export
		[$datasets_kata, $chart_kata] = $this->_buildChartKata($series_specs, $range, $period);

		if(!$chart_kata) {
			echo json_encode(['error' => 'Add at least one series with a metric.']);
			return true;
		}

		// Resolve {{record_*}} placeholders and run the combined metrics.timeseries query. The client builds the
		// chart from its own series metadata + this raw data (no server-side chart compile needed to render).
		$dict = $this->_recordDict($model);
		$error = null;

		$query = $tpl_builder->build($data_query, $dict);
		$bindings = $dict->getDictionary();

		if(false === ($results = $data->executeQuery($query, $bindings, $error))) {
			echo json_encode(['error' => $error]);
			return true;
		}

		echo json_encode([
			'data' => $results['data'] ?? null,
			'meta' => $results['_'] ?? null,
			'data_query' => $data_query,
			'datasets_kata' => $datasets_kata,
			'chart_kata' => $chart_kata,
			'series_kata' => $this->_buildSeriesKata($series_specs),
		]);

		return true;
	}

	private function _invokeMetricMeta(Model_CardWidget $model) {
		$http = DevblocksPlatform::services()->http();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$http->setHeader('Content-Type', 'application/json; charset=utf-8');

		$metric_name = DevblocksPlatform::importGPC($_POST['metric'] ?? null, 'string', '');

		// Resolve a placeholder metric (e.g. {{record_name}}) against the current record
		$metric_name = $tpl_builder->build($metric_name, $this->_recordDict($model));

		if(!($metric = DAO_Metric::getByName($metric_name))) {
			echo json_encode(['error' => 'Unknown metric.']);
			return true;
		}

		echo json_encode([
			'name' => $metric->name,
			'type' => $metric->type,
			'dimensions' => $this->_formatDimensions($metric),
		]);

		return true;
	}

	private function _invokeDimensionValues(Model_CardWidget $model) {
		$http = DevblocksPlatform::services()->http();
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$http->setHeader('Content-Type', 'application/json; charset=utf-8');

		$metric_name = DevblocksPlatform::importGPC($_POST['metric'] ?? null, 'string', '');
		$dimension = DevblocksPlatform::importGPC($_POST['dimension'] ?? null, 'string', '');
		$range = DevblocksPlatform::importGPC($_POST['range'] ?? null, 'string', '-24 hours to now');

		// Resolve a placeholder metric against the current record
		$metric_name = $tpl_builder->build($metric_name, $this->_recordDict($model));

		if(!($metric = DAO_Metric::getByName($metric_name))) {
			echo json_encode(['error' => 'Unknown metric.']);
			return true;
		}

		if(!array_key_exists($dimension, $metric->getDimensions())) {
			echo json_encode(['error' => 'Unknown dimension.']);
			return true;
		}

		// Enumerate the distinct values seen for this dimension over the range
		$kata = implode("\n", [
			'type:metrics.subtotals',
			'range:' . json_encode($range),
			'series.s:(',
			'  metric:' . json_encode($metric->name),
			'  function:count',
			'  by:[' . $dimension . ']',
			')',
			'format:dictionaries',
		]);

		$error = null;

		if(false === ($results = $data->executeQuery($kata, [], $error))) {
			echo json_encode(['error' => $error]);
			return true;
		}

		$values = [];

		foreach(($results['data'] ?? []) as $row) {
			if(!array_key_exists($dimension, $row))
				continue;

			$values[] = [
				'id' => (string)$row[$dimension],
				'label' => (string)($row[$dimension . '__label'] ?? $row[$dimension]),
				'value' => floatval($row['value'] ?? 0),
			];
		}

		// Most-active values first, then cap the payload
		usort($values, fn($a, $b) => $b['value'] <=> $a['value']);
		$truncated = count($values) > self::DIMENSION_VALUES_LIMIT;
		$values = array_slice($values, 0, self::DIMENSION_VALUES_LIMIT);

		echo json_encode([
			'values' => $values,
			'truncated' => $truncated,
		]);

		return true;
	}

	// === Helpers =============================================================

	// A record dictionary for the current card, for resolving {{record_*}} placeholders.
	// During render() the context/id are passed directly; in AJAX actions they come from $_POST.
	private function _recordDict(Model_CardWidget $model, $card_context=null, $card_context_id=null) : DevblocksDictionaryDelegate {
		$active_worker = CerberusApplication::getActiveWorker();

		$card_context = $card_context ?? DevblocksPlatform::importGPC($_POST['card_context'] ?? null, 'string', '');
		$card_context_id = $card_context_id ?? DevblocksPlatform::importGPC($_POST['card_context_id'] ?? null, 'string', '');

		return DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $card_context,
			'record_id' => $card_context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
	}

	// Normalize a metric's dimensions into [{name, type, params}] for the client
	private function _formatDimensions(Model_Metric $metric) : array {
		$out = [];

		foreach($metric->getDimensions() as $name => $meta) {
			$out[] = [
				'name' => $name,
				'type' => $meta['type'] ?? '',
				'params' => $meta['params'] ?? [],
			];
		}

		return $out;
	}

	// Parse the config `series_kata` (a `series/<id>:` KATA tree) into structured specs.
	// Placeholders (e.g. {{record_id}}) are preserved verbatim for later resolution.
	private function _parseSeriesKata(string $series_kata) : array {
		$series_kata = trim($series_kata);

		if($series_kata === '')
			return [];

		$kata = DevblocksPlatform::services()->kata();
		$error = null;

		if(false === ($tree = $kata->parse($series_kata, $error)))
			return [];

		$specs = [];

		foreach($tree as $key => $node) {
			if(!is_array($node) || !DevblocksPlatform::strStartsWith($key, 'series/'))
				continue;

			$filters = [];

			if(is_array($node['filters'] ?? null)) {
				foreach($node['filters'] as $dim => $val) {
					$val = ltrim((string)$val);

					// A leading `!` negates the filter (NOT IN)
					$negate = str_starts_with($val, '!');
					if($negate)
						$val = substr($val, 1);

					$values = array_values(array_filter(
						array_map('trim', explode(',', $val)),
						fn($v) => $v !== ''
					));

					if($values)
						$filters[] = ['dimension' => $dim, 'values' => $values, 'negate' => $negate];
				}
			}

			// `hidden` may carry an annotation (e.g. `hidden@bool`) — parse() keeps it in the key
			$hidden_raw = '';
			foreach($node as $k => $v) {
				if($k === 'hidden' || str_starts_with($k, 'hidden@')) {
					$hidden_raw = (string)$v;
					break;
				}
			}

			$specs[] = [
				'metric' => trim($node['metric'] ?? ''),
				'function' => trim($node['function'] ?? 'count'),
				'label' => trim($node['label'] ?? ''),
				'color' => trim($node['color'] ?? ''),
				'type' => trim($node['type'] ?? ''),
				'axis' => trim($node['axis'] ?? ''),
				'stack' => trim((string)($node['stack'] ?? '')),
				'hidden_raw' => $hidden_raw,
				'filters' => $filters,
			];
		}

		return $specs;
	}

	// Inverse of _parseSeriesKata: emit `series/<id>:` KATA from structured specs.
	// This is what `Copy explorer config` produces (symmetric with the parser).
	private function _buildSeriesKata(array $series_specs) : string {
		$blocks = [];

		foreach(array_values($series_specs) as $i => $spec) {
			$metric = trim($spec['metric'] ?? '');

			if(!$metric)
				continue;

			$function = $spec['function'] ?? 'count';
			if(!in_array($function, self::FUNCTIONS))
				$function = 'count';

			$lines = ['series/s' . $i . ':'];
			$lines[] = '  metric: ' . $metric;
			$lines[] = '  function: ' . $function;

			if(($label = trim($spec['label'] ?? '')) !== '')
				$lines[] = '  label: ' . $label;

			if(($color = trim($spec['color'] ?? '')) !== '')
				$lines[] = '  color: ' . $color;

			if(in_array($spec['type'] ?? '', ['line', 'bar', 'area']))
				$lines[] = '  type: ' . $spec['type'];

			if(($spec['axis'] ?? '') === 'y2')
				$lines[] = '  axis: y2';

			if(($stack = trim((string)($spec['stack'] ?? ''))) !== '')
				$lines[] = '  stack: ' . $stack;

			if(!empty($spec['hidden']))
				$lines[] = '  hidden@bool: yes';

			$filter_lines = [];

			foreach(($spec['filters'] ?? []) as $filter) {
				$dim = trim($filter['dimension'] ?? '');
				$values = array_values(array_filter($filter['values'] ?? [], fn($v) => $v !== '' && !is_null($v)));

				if($dim && $values) {
					$neg = !empty($filter['negate']) ? '!' : '';
					$filter_lines[] = '    ' . $dim . ': ' . $neg . implode(',', $values);
				}
			}

			if($filter_lines) {
				$lines[] = '  filters:';
				$lines = array_merge($lines, $filter_lines);
			}

			$blocks[] = implode("\n", $lines);
		}

		return implode("\n\n", $blocks);
	}

	// Build the inner `query:(...)` body from a series' dimension filters (no wrapper).
	// `is` → dim:value / dim:[a,b]; `not` (negate) → dim:!value / dim:![a,b] (NOT IN).
	private function _buildQueryFilters(array $filters) : string {
		$parts = [];

		foreach($filters as $filter) {
			$dim = trim($filter['dimension'] ?? '');
			$values = array_values(array_filter($filter['values'] ?? [], fn($v) => $v !== '' && !is_null($v)));

			if(!$dim || !$values)
				continue;

			$neg = !empty($filter['negate']) ? '!' : '';

			if(count($values) > 1)
				$parts[] = $dim . ':' . $neg . '[' . implode(',', array_map('json_encode', $values)) . ']';
			else
				$parts[] = $dim . ':' . $neg . json_encode($values[0]);
		}

		return implode(' ', $parts);
	}

	// Build a metrics.timeseries data query from structured series specs.
	// Each series carries a unique `label:` so result columns (and C3 colors) map cleanly.
	// Placeholder strings (e.g. {{record_name}}) are emitted verbatim.
	private function _buildTimeseriesKata(array $series_specs, string $range, string $period) : string {
		if(!in_array($period, self::PERIODS))
			$period = 'hour';

		$lines = [];
		$lines[] = 'type:metrics.timeseries';
		$lines[] = 'range:' . json_encode($range);
		$lines[] = 'period:' . $period;

		$has_series = false;

		foreach(array_values($series_specs) as $i => $spec) {
			$metric = trim($spec['metric'] ?? '');
			$function = $spec['function'] ?? 'count';

			if(!$metric || !empty($spec['hidden']))
				continue;

			if(!in_array($function, self::FUNCTIONS))
				$function = 'count';

			$has_series = true;

			// Always emit a label so columns are uniquely keyed
			$label = trim($spec['label'] ?? '');
			if($label === '')
				$label = sprintf('%s(%s) #%d', $function, $metric, $i + 1);

			$lines[] = 'series.s' . $i . ':(';
			$lines[] = '  metric:' . json_encode($metric);
			$lines[] = '  function:' . $function;
			$lines[] = '  label:' . json_encode($label);

			$query = $this->_buildQueryFilters($spec['filters'] ?? []);
			if($query !== '')
				$lines[] = '  query:(' . $query . ')';

			$lines[] = ')';
		}

		if(!$has_series)
			return '';

		$lines[] = 'format:timeseries';

		return implode("\n", $lines);
	}

	// Compile structured series specs into a Chart KATA pair: [datasets_kata, chart_kata].
	// One single-series `dataQuery/sN` per series unlocks per-series type / y-axis / stacking
	// (those are per-dataset in the chart service). Placeholders are emitted verbatim.
	private function _buildChartKata(array $series_specs, string $range, string $period) : array {
		if(!in_array($period, self::PERIODS))
			$period = 'hour';

		$palette = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];

		$dataset_blocks = [];
		$series_blocks = [];
		$color_blocks = [];
		$stacks = []; // stack group => [dataset keys]
		$i = 0;

		foreach(array_values($series_specs) as $spec) {
			$metric = trim($spec['metric'] ?? '');

			if(!$metric || !empty($spec['hidden']))
				continue;

			$function = $spec['function'] ?? 'count';
			if(!in_array($function, self::FUNCTIONS))
				$function = 'count';

			$type = $spec['type'] ?? 'line';
			if(!in_array($type, ['line', 'bar', 'area']))
				$type = 'line';

			$axis = (($spec['axis'] ?? '') === 'y2') ? 'y2' : 'y';
			$stack = trim((string)($spec['stack'] ?? ''));

			$ds = 's' . $i;

			$label = trim($spec['label'] ?? '');
			if($label === '')
				$label = sprintf('%s(%s)', $function, $metric);

			$color = trim($spec['color'] ?? '');
			if($color === '' || !preg_match('/^#[0-9a-fA-F]{3,8}$/', $color))
				$color = $palette[$i % count($palette)];

			// Per-series single-column metrics.timeseries query (column key `v`)
			$q = [];
			$q[] = '    type:metrics.timeseries';
			$q[] = '    range:' . json_encode($range);
			$q[] = '    period:' . $period;
			// The metrics column label becomes the chart series' display name
			$q[] = '    series.v:(';
			$q[] = '      metric:' . json_encode($metric);
			$q[] = '      function:' . $function;
			$q[] = '      label:' . json_encode($label);

			$query = $this->_buildQueryFilters($spec['filters'] ?? []);
			if($query !== '')
				$q[] = '      query:(' . $query . ')';

			$q[] = '    )';
			$q[] = '    format:timeseries';

			$dataset_blocks[] = 'dataQuery/' . $ds . ":\n  query@text:\n" . implode("\n", $q);

			// Chart series binding
			$sb = [];
			$sb[] = '    ' . $ds . ':';
			$sb[] = '      x_key: ts';
			$sb[] = '      y_type: ' . $type;
			if($axis === 'y2')
				$sb[] = '      y_axis: y2';
			$sb[] = '      color_pattern: c' . $i;
			$series_blocks[] = implode("\n", $sb);

			$color_blocks[] = '    c' . $i . '@csv: ' . $color;

			if($stack !== '')
				$stacks[$stack][] = $ds;

			$i++;
		}

		if(!$dataset_blocks)
			return ['', ''];

		$tick_format = ($period === 'day') ? '%Y-%m-%d' : '%Y-%m-%d %H:%M';

		$c = [];
		$c[] = 'data:';
		$c[] = '  type: line';
		$c[] = '  series:';
		$c = array_merge($c, $series_blocks);

		if($stacks) {
			$c[] = '  stacks:';
			foreach($stacks as $group => $ds_keys)
				$c[] = '    ' . $group . '@csv: ' . implode(', ', $ds_keys);
		}

		$c[] = 'color:';
		$c[] = '  patterns:';
		$c = array_merge($c, $color_blocks);
		$c[] = 'axis:';
		$c[] = '  x:';
		$c[] = '    type: timeseries';
		$c[] = '    tick:';
		$c[] = '      format: ' . $tick_format;
		$c[] = '      multiline@bool: no';
		$c[] = '      rotate: -90';

		return [implode("\n", $dataset_blocks), implode("\n", $c)];
	}
}
