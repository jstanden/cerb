<?php
class DAO_MetricValue {
	const PERIOD_MINS_5 = 300;
	const PERIOD_HOURS_1 = 3_600;
	const PERIOD_DAYS_1 = 86_400;
	
	/**
	 * @param Model_Metric $metric
	 * @param array|int|Model_MetricValueSampleSet $values
	 * @param int|null $ts
	 * @param array $dimension_values
	 * @return bool
	 */
	static function increment(Model_Metric $metric, mixed $values, ?int $ts=null, array $dimension_values=[]) : bool {
		$db = DevblocksPlatform::services()->database();
		
		if(!$ts) $ts = time();
		
		$ts_5min = $ts - ($ts % self::PERIOD_MINS_5);
		$ts_1hr = $ts - ($ts % self::PERIOD_HOURS_1);
		$ts_1d = $ts - ($ts % self::PERIOD_DAYS_1);
		
		if(is_numeric($values)) {
			$values = [floatval($values)];
			
		} else if(is_array($values)) {
			$values = new Model_MetricValueSampleSet(
				count($values),
				array_sum($values),
				min($values),
				max($values)
			);
		}
		
		// Create a sample set if given an array
		if($values instanceof Model_MetricValueSampleSet) {
			$insert_values = [];
			
			// 5 min
			$insert_values[] = sprintf("(%d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s)",
				$metric->id,
				$dimension_values[0] ?? 0,
				$dimension_values[1] ?? 0,
				$dimension_values[2] ?? 0,
				self::PERIOD_MINS_5,
				$ts_5min,
				$ts_5min + self::PERIOD_DAYS_1, // 1 day
				$values->samples,
				$db->qstr($values->sum),
				$db->qstr($values->min),
				$db->qstr($values->max)
			);
			
			// 1 hour
			$insert_values[] = sprintf("(%d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s)",
				$metric->id,
				$dimension_values[0] ?? 0,
				$dimension_values[1] ?? 0,
				$dimension_values[2] ?? 0,
				self::PERIOD_HOURS_1,
				$ts_1hr,
				$ts_1hr + (self::PERIOD_DAYS_1 * 14), // 14 days
				$values->samples,
				$db->qstr($values->sum),
				$db->qstr($values->min),
				$db->qstr($values->max)
			);
			
			// 1 day
			$insert_values[] = sprintf("(%d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s)",
				$metric->id,
				$dimension_values[0] ?? 0,
				$dimension_values[1] ?? 0,
				$dimension_values[2] ?? 0,
				self::PERIOD_DAYS_1,
				$ts_1d,
				(86_400 * $metric->retention_days), // 0 = forever
				$values->samples,
				$db->qstr($values->sum),
				$db->qstr($values->min),
				$db->qstr($values->max)
			);
			
			$db->ExecuteWriter(sprintf("INSERT INTO metric_value (metric_id, dim0_value_id, dim1_value_id, dim2_value_id, granularity, bin, expires_at, samples, sum, min, max) ".
				"VALUES %s ".
				"ON DUPLICATE KEY UPDATE samples=samples+VALUES(samples), sum=sum+VALUES(sum), min=LEAST(min,VALUES(min)), max=GREATEST(max,VALUES(max))",
				implode(',', $insert_values)
			));
			
		} else {
			return false;
		}
		
		return true;
	}
	
	public static function gc() {
		$db = DevblocksPlatform::services()->database();
		
		// An expires_at of 0 is forever
		$db->ExecuteWriter(sprintf("DELETE FROM metric_value WHERE expires_at BETWEEN 1 AND %d", time()));
		
		return true;
	}

	/**
	 * Build inline worklist sparkline data via a SINGLE metrics.timeseries data query (no N+1). Each row
	 * can have one or more series (e.g. an invocations line + a duration bar), optionally filtered by a
	 * metric dimension (automation_id, trigger, ...). The query handles bin lerp, timezone, and gap-fill.
	 *
	 * @param array $row_series [rowKey => [ series_spec, ... ]] where series_spec = [
	 *     'metric'   => 'cerb.automation.invocations',   // required, metric name
	 *     'function' => 'count'|'sum'|'avg'|'min'|'max', // default 'count'
	 *     'type'     => 'line'|'bar',                    // chart type (default 'line'); bars render behind lines
	 *     'label'    => 'invocations',                   // series label (tooltip/legend)
	 *     'color'    => '#0088e6',                       // optional explicit color (else the shared colorScale by label)
	 *     'query'    => ['automation_id' => 123],        // optional dimension filters
	 *     'missing'  => 'zero'|'carry',                  // optional gap-fill (counters usually 'zero')
	 *     'suffix'   => 'ms',                            // optional text suffix
	 *   ]
	 * @param string $window '24h' | '7d' | '30d'
	 * @param string|null $tz worker timezone (defaults to the platform timezone)
	 * @return array [rowKey => ['categories'=>[...], 'series'=>[ ['type'=>,'label'=>,'values'=>[],'text'=>[]], ... ]]]
	 */
	static function getSparklines(array $row_series, string $window='24h', ?string $tz=null) : array {
		if(!$row_series)
			return [];

		// window => [period, range]; the data query does the bin lerp, gap-fill, and timezone
		$windows = [
			'24h' => ['hour', '-24 hours to now'],
			'7d'  => ['day', '-7 days to now'],
			'30d' => ['day', '-30 days to now'],
		];
		[$period, $range] = $windows[$window] ?? $windows['24h'];

		if(!$tz)
			$tz = DevblocksPlatform::getTimezone();

		// One series per (row, spec). Track an opaque key back to its row + position so we never parse labels.
		$series_kata = '';
		$key_map = []; // seriesKey => [rowKey, specIdx, spec]
		$n = 0;

		foreach($row_series as $row_key => $specs) {
			foreach($specs as $spec_idx => $spec) {
				$key = 's' . $n++;
				$key_map[$key] = [$row_key, $spec_idx, $spec];

				$query_kata = '';
				if(!empty($spec['query']) && is_array($spec['query'])) {
					$filters = '';
					foreach($spec['query'] as $dim => $val)
						$filters .= sprintf("\n    %s:%s", $dim, json_encode((string) $val));
					$query_kata = sprintf("\n  query:(%s\n  )", $filters);
				}

				$missing_kata = !empty($spec['missing']) ? sprintf("\n  missing:%s", $spec['missing']) : '';

				$series_kata .= sprintf("series.%s:(\n  label:%s\n  metric:%s\n  function:%s%s%s\n)\n",
					$key,
					$key,
					json_encode($spec['metric'] ?? ''),
					$spec['function'] ?? 'count',
					$missing_kata,
					$query_kata
				);
			}
		}
		
		$query = sprintf("type:metrics.timeseries\nperiod:%s\nrange:%s\ntimezone:%s\n%sformat:timeseries",
			$period,
			json_encode($range),
			$tz,
			$series_kata
		);
		
		$error = null;

		if(false === ($results = DevblocksPlatform::services()->data()->executeQuery($query, [], $error)))
			return [];

		$ts = $results['data']['ts'] ?? [];

		$out = [];
		foreach($key_map as $key => [$row_key, $spec_idx, $spec]) {
			$values = array_values($results['data'][$key] ?? array_fill(0, count($ts), 0));
			$suffix = $spec['suffix'] ?? '';

			$text = array_map(
				fn($v) => number_format((float) $v, ((float) $v == (int) $v) ? 0 : 2) . $suffix,
				$values
			);

			if(!isset($out[$row_key]))
				$out[$row_key] = ['categories' => $ts, 'series' => []];

			$out[$row_key]['series'][$spec_idx] = [
				'type' => $spec['type'] ?? 'line',
				'label' => $spec['label'] ?? '',
				'color' => $spec['color'] ?? null, // explicit color wins over the shared colorScale (null = use scale)
				'stack' => $spec['stack'] ?? null, // bars sharing a stack key render stacked (null = standalone)
				'scaleGroup' => $spec['scaleGroup'] ?? null, // lines sharing a scaleGroup share one min/max (null = own)
				'values' => $values,
				'text' => $text,
			];
		}

		// Keep each row's series in spec order (bars before lines, so lines render in front)
		foreach($out as &$row) {
			ksort($row['series']);
			$row['series'] = array_values($row['series']);
		}
		unset($row);

		return $out;
	}

	/**
	 * Parse a worklist metric-filter quick-search group (each worklist names its own key, e.g.
	 * `usage:(...)` or `activity:(...)`) and return the dimension values that match.
	 * Reserved sub-keys `since`/`until` set the time range (defaults to all time: 'big bang' to 'now', like
	 * `created:`). Every other recognized sub-key is a metric threshold from $metric_map; multiple
	 * thresholds are AND-ed (set intersection). An empty group means "ran in range" -> the first (primary)
	 * metric, count > 0.
	 *
	 * Resolution runs through the `metrics.subtotals` data query (one range aggregate per dimension
	 * value), so per-type dimension handling (record id vs. extension/string) stays in one place.
	 *
	 * @param string $inner_query the raw text inside the filter group `(...)`
	 * @param array $metric_map ['runs' => ['metric'=>'cerb.automation.invocations','function'=>'count'], ...]
	 *   each entry may add an optional 'query' => ['status_id'=>2] to pin extra dimensions for that threshold
	 * @param string $dimension dimension name to resolve against (e.g. 'automation_id', 'trigger')
	 * @param string|null $tz worker timezone (defaults to the platform timezone); matches getSparklines so
	 *   relative range boundaries (since:today/yesterday) align with the sparkline column
	 * @return array matching dimension values (strings); empty = match nothing
	 */
	static function getDimensionValuesByMetricQuery(string $inner_query, array $metric_map, string $dimension, ?string $tz=null) : ?array {
		$parsed = self::_parseMetricQuery($inner_query, $metric_map);

		// null = invalid filter (typo, unknown key, unparseable value) => match nothing (fail loud)
		if(is_null($parsed))
			return null;

		// Resolve relative range boundaries (since:today/yesterday) in the worker's timezone, same as getSparklines
		if(!$tz)
			$tz = DevblocksPlatform::getTimezone();

		$matches = null;

		foreach($parsed['thresholds'] as $t) {
			$set = self::_getMetricSubtotalMatches($t['metric'], $dimension, $t['function'], $t['oper'], $t['value'], $parsed['since'], $parsed['until'], $tz, $t['query'] ?? null);

			$matches = is_null($matches) ? $set : array_values(array_intersect($matches, $set));

			if(!$matches) // AND short-circuit
				break;
		}

		return $matches ?? [];
	}

	// Valid metrics.subtotals aggregates per metric type; functions[0] is the bare-series default.
	// Gauges omit `sum` (summing snapshots is meaningless); counters total by `sum`.
	const METRIC_FILTER_FUNCTIONS = [
		'counter' => ['sum', 'count', 'avg', 'max', 'min'],
		'gauge'   => ['avg', 'max', 'min', 'count'],
	];

	// Compose one filter-series entry for getMetricFilterMap(). $type picks the function set + default;
	// $extra carries 'query', 'unit', or a 'default' override (e.g. a timer counter that should default
	// to mean, not total). So `runs:` uses functions[0], `runs.max:` selects another from the same set.
	static function metricFilterSeries(string $metric, string $type = 'counter', array $extra = []) : array {
		$functions = self::METRIC_FILTER_FUNCTIONS[$type] ?? self::METRIC_FILTER_FUNCTIONS['counter'];

		if(($default = $extra['default'] ?? null) && in_array($default, $functions, true))
			$functions = array_merge([$default], array_values(array_diff($functions, [$default])));
		unset($extra['default']);

		return array_merge(['metric' => $metric, 'functions' => $functions], $extra);
	}

	// Build the in-parens sub-key autocomplete suggestions for a parameterized metric filter
	// (usage:/activity:/records:). One entry per series (bare = default function) + one per
	// series.function, then since:/until:. Caption + Ace snippet, matching the date filter's `field:()`.
	static function getMetricFilterSubkeySuggestions(array $metric_map) : array {
		$out = [];

		foreach($metric_map as $series => $def) {
			$is_duration = (($def['unit'] ?? null) === 'ms');
			// Value placeholder: durations read better in time units; sums in a larger unit
			$placeholder = fn($fn) => $is_duration ? ($fn === 'sum' ? '1h' : '500ms') : '100';

			$functions = $def['functions'] ?? [];

			$out[] = ['caption' => "$series:", 'snippet' => sprintf('%s:>${1:%s}', $series, $placeholder($functions[0] ?? ''))];

			foreach($functions as $fn)
				$out[] = ['caption' => "$series.$fn:", 'snippet' => sprintf('%s.%s:>${1:%s}', $series, $fn, $placeholder($fn))];
		}

		$out[] = ['caption' => 'since:', 'snippet' => 'since:"${1:-1 week}"'];
		$out[] = ['caption' => 'until:', 'snippet' => 'until:"${1:now}"'];

		return $out;
	}

	/**
	 * Parse the filter group `(...)` into a range + threshold list. Returns null and sets $error with a
	 * human-readable reason on any invalid token (unknown key, stray text, or unparseable value); an empty
	 * group or a range-only filter is VALID ("ran in range"). Shared by getDimensionValuesByMetricQuery
	 * (SQL time) and validateMetricQuery (quick-search parse time) so the two can never disagree.
	 *
	 * @return array{since:string,until:string,thresholds:array}|null
	 */
	private static function _parseMetricQuery(string $inner_query, array $metric_map, ?string &$error = null) : ?array {
		if(!$metric_map) {
			$error = 'No metric filters are available here.';
			return null;
		}

		$leaves = self::_flattenQueryLeaves(CerbQuickSearchLexer::getFieldsFromQuery($inner_query));

		$since = null;
		$until = null;
		$thresholds = [];
		$valid_keys = implode(', ', array_keys($metric_map));

		foreach($leaves as $leaf) {
			$key = $leaf->key ?? null;

			if(!$key)
				continue;

			// The lexer keys bare/stray text as '_text' (e.g. a lone `>5` with no filter name)
			if($key == '_text') {
				$error = 'Metric filters need a name, e.g. runs:>100.';
				return null;
			}

			// Reserved range keys; kept as raw strings for the data query's range parser
			// ('yesterday', '-2 hours', '2026-01-01', ...)
			if($key == 'since' || $key == 'until') {
				$oper = null;
				$val = null;
				CerbQuickSearchLexer::getOperStringFromTokens($leaf->tokens, $oper, $val);

				if(is_string($val) && $val !== '') {
					if($key == 'since') $since = $val;
					else $until = $val;
				}

			// Metric threshold keys: a series (runs:>100) or series.function (duration.avg:>500ms,
			// runs.max:>10). A bare series uses its default function (functions[0]); a `.function` suffix
			// must be one of the series' functions. Series tagged 'unit'=>'ms' parse the value as a duration.
			} else {
				// Split "series.function" (neither contains a dot); a bare series uses its default function
				[$series, $func] = array_pad(explode('.', $key, 2), 2, null);
				$series_def = $metric_map[$series] ?? null;

				if(is_null($series_def)) {
					$error = sprintf('Unknown metric filter `%s`. Try: %s (or since:/until:).', $series, $valid_keys);
					return null;
				}

				$functions = $series_def['functions'] ?? ['count'];

				if(is_null($func))
					$func = $functions[0];
				else if(!in_array($func, $functions, true)) {
					$error = sprintf('`%s` has no `%s` function. Try: %s.', $series, $func, implode(', ', $functions));
					return null;
				}

				$is_duration = (($series_def['unit'] ?? null) === 'ms');
				$crit = $is_duration
					? self::_getDurationParamFromTokens($key, $leaf->tokens)
					: DevblocksSearchCriteria::getNumberParamFromTokens($key, $leaf->tokens);

				// A recognized key with an unparseable value (runs:abc, duration.avg:>xyz) is a mistake too
				if(!($crit instanceof DevblocksSearchCriteria) || !self::_isUsableThresholdValue($crit->value)) {
					$error = $is_duration
						? sprintf("Couldn't read `%s` as a duration. Use milliseconds, or e.g. 500ms, 2s, 5m, 1h.", $key)
						: sprintf("`%s` expects a number (e.g. %s:>100).", $key, $key);
					return null;
				}

				$thresholds[] = [
					'metric' => $series_def['metric'],
					'function' => $func,
					'query' => $series_def['query'] ?? null, // optional fixed dimension filter (e.g. status_id)
					'oper' => $crit->operator,
					'value' => $crit->value,
				];
			}
		}

		// No date given => all of available history (like `created:`'s 'big bang' default); the subtotals
		// granularity auto-pick resolves the huge span to daily bins.
		$since = $since ?: 'big bang';
		$until = $until ?: 'now';

		// No threshold: an empty group or a range-only filter means "ran in range" (primary metric, count>0).
		if(!$thresholds) {
			$primary = reset($metric_map);
			$thresholds[] = [
				'metric' => $primary['metric'],
				'function' => 'count',
				'query' => $primary['query'] ?? null,
				'oper' => DevblocksSearchCriteria::OPER_GT,
				'value' => 0,
			];
		}

		return ['since' => $since, 'until' => $until, 'thresholds' => $thresholds];
	}

	// A threshold value is usable if it's a number (or a numeric BETWEEN/IN array). Duration values arrive
	// already numeric (ms) or null; number values may arrive as a non-numeric string (e.g. runs:abc) which we
	// reject so the filter fails loud with a hint instead of silently matching nothing.
	private static function _isUsableThresholdValue(mixed $value) : bool {
		if(is_null($value))
			return false;

		if(is_array($value)) {
			if(!$value)
				return false;
			foreach($value as $v)
				if(!is_numeric($v))
					return false;
			return true;
		}

		return is_numeric($value);
	}

	// DB-free validation for the quick-search parse path: true = usable (incl. empty group / range-only / a
	// valid filter that simply matched nothing). On false, $error carries a human-readable hint for the marquee.
	static function validateMetricQuery(string $inner_query, array $metric_map, ?string &$error = null) : bool {
		return !is_null(self::_parseMetricQuery($inner_query, $metric_map, $error));
	}

	// Run one metrics.subtotals aggregate (metric, by:[dimension], over the range) and return the raw
	// dimension values whose subtotal satisfies the threshold. An optional $query pins extra dimensions
	// (e.g. status_id) so a threshold can target a slice of the metric.
	private static function _getMetricSubtotalMatches(string $metric, string $dimension, string $function, string $oper, mixed $value, string $since, string $until, ?string $tz=null, ?array $query=null) : array {
		$range = sprintf('%s to %s', $since, $until);

		if(!$tz)
			$tz = DevblocksPlatform::getTimezone();

		$query_kata = '';
		if($query) {
			$filters = '';
			foreach($query as $dim => $val)
				$filters .= sprintf("\n    %s:%s", $dim, json_encode((string) $val));
			$query_kata = sprintf("\n  query:(%s\n  )", $filters);
		}

		$query = sprintf("type:metrics.subtotals\nrange:%s\ntimezone:%s\nseries.s:(\n  metric:%s\n  function:%s\n  by:[%s]%s\n)\nformat:dictionaries",
			json_encode($range),
			$tz,
			json_encode($metric),
			$function,
			$dimension,
			$query_kata
		);

		$error = null;

		if(false === ($results = DevblocksPlatform::services()->data()->executeQuery($query, [], $error)))
			return [];

		$matches = [];

		// format:dictionaries => flat list of row dicts (single series here)
		foreach(($results['data'] ?? []) as $row) {
			if(!array_key_exists($dimension, $row))
				continue;

			if(self::_metricThresholdMatches((float) ($row['value'] ?? 0), $oper, $value))
				$matches[] = (string) $row[$dimension];
		}

		return $matches;
	}

	// Compare a subtotal against the parsed threshold operator/value (mirrors DevblocksSearchCriteria opers).
	private static function _metricThresholdMatches(float $v, string $oper, mixed $value) : bool {
		if($oper == DevblocksSearchCriteria::OPER_BETWEEN && is_array($value)) {
			return $v >= (float) ($value[0] ?? 0) && $v <= (float) ($value[1] ?? 0);
		}

		$t = (float) (is_array($value) ? reset($value) : $value);

		return match($oper) {
			DevblocksSearchCriteria::OPER_NEQ => $v != $t,
			DevblocksSearchCriteria::OPER_GTE => $v >= $t,
			DevblocksSearchCriteria::OPER_LT => $v < $t,
			DevblocksSearchCriteria::OPER_LTE => $v <= $t,
			DevblocksSearchCriteria::OPER_EQ => $v == $t,
			default => $v > $t, // OPER_GT
		};
	}

	// Like DevblocksSearchCriteria::getNumberParamFromTokens() but the value is a duration string converted
	// to milliseconds (500ms/2s/5m/1h/1000d; a bare number stays ms). Unparseable units => null value so the
	// caller flags the threshold invalid (e.g. duration.avg:>xyz matches nothing instead of silently passing).
	private static function _getDurationParamFromTokens(string $field_key, $tokens) : DevblocksSearchCriteria {
		$oper = DevblocksSearchCriteria::OPER_EQ;
		$value = null;

		if(is_array($tokens))
		foreach($tokens as $token) {
			if(!in_array($token->type, ['T_TEXT', 'T_QUOTED_TEXT']))
				continue;

			$raw = $token->value;
			$matches = [];

			// Range: "1h...2h" or "1h to 2h"
			if(preg_match('#^(.+?)\.{3}(.+)$#', $raw, $matches) || preg_match('#^(.+?)\s+to\s+(.+)$#', $raw, $matches)) {
				$from = self::_parseDurationToMs(trim($matches[1]));
				$to = self::_parseDurationToMs(trim($matches[2]));
				if(!is_null($from) && !is_null($to)) {
					$oper = DevblocksSearchCriteria::OPER_BETWEEN;
					$value = [$from, $to];
				}

			} else if(preg_match('#^([\<\>\!\=]+)(.*)#', $raw, $matches)) {
				$oper = match(trim($matches[1])) {
					'!', '!=' => DevblocksSearchCriteria::OPER_NEQ,
					'>' => DevblocksSearchCriteria::OPER_GT,
					'>=' => DevblocksSearchCriteria::OPER_GTE,
					'<' => DevblocksSearchCriteria::OPER_LT,
					'<=' => DevblocksSearchCriteria::OPER_LTE,
					default => DevblocksSearchCriteria::OPER_EQ,
				};
				$value = self::_parseDurationToMs(trim($matches[2]));

			} else {
				$value = self::_parseDurationToMs(trim($raw));
			}
		}

		return new DevblocksSearchCriteria($field_key, $oper, $value);
	}

	// Parse a single duration token to milliseconds. Bare number => that many ms; otherwise <number><unit>
	// with unit in {ms,s,m,h,d,w} (minute = m, matching date.php's expandHumanTimeAbbreviations). null = invalid.
	private static function _parseDurationToMs(string $v) : ?float {
		$v = trim($v);

		if($v === '')
			return null;

		if(is_numeric($v))
			return (float) $v; // already milliseconds

		if(preg_match('#^(\d+(?:\.\d+)?)\s*(ms|s|m|h|d|w)$#i', $v, $matches)) {
			static $scale = ['ms' => 1, 's' => 1000, 'm' => 60000, 'h' => 3600000, 'd' => 86400000, 'w' => 604800000];
			return (float) $matches[1] * $scale[DevblocksPlatform::strLower($matches[2])];
		}

		return null;
	}

	// Collect the field criteria (->key + ->tokens) from a parsed quick-search tree, ignoring grouping.
	private static function _flattenQueryLeaves($params) : array {
		$out = [];

		if(!is_array($params))
			return $out;

		foreach($params as $p) {
			if($p instanceof DevblocksSearchCriteria) {
				if($p->key)
					$out[] = $p; // a field leaf
				elseif(is_array($p->value))
					$out = array_merge($out, self::_flattenQueryLeaves($p->value));
			} elseif(is_array($p)) {
				// A group like ['AND', criteria, ...]; the leading boolean string is skipped naturally
				$out = array_merge($out, self::_flattenQueryLeaves($p));
			}
		}

		return $out;
	}
};
