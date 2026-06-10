<?php
/*
 * Flat metric subtotals straight off `metric_value`: one range aggregate per dimension tuple, with no
 * time binning, lerp, zero-fill, or per-bin averaging. This is the inverse projection of
 * `metrics.timeseries` (which is built for charts): it answers "which dimension values cross a threshold
 * over this range?" and returns the RAW dimension values (record ids, extension ids) alongside labels,
 * so callers can feed them back into a worklist filter, table, or sheet.
 */
class _DevblocksDataProviderMetricsSubtotals extends _DevblocksDataProvider {
	// Subtotals are a single range aggregate, so the chart-only functions (distinct, faceted_*) don't apply
	private array $_allowed_functions = [
		'avg' => 'avg',
		'average' => 'avg',
		'count' => 'count',
		'max' => 'max',
		'min' => 'min',
		'samples' => 'count',
		'sum' => 'sum',
	];

	// Optional override for which stored granularity to read; otherwise auto-picked by range span
	private array $_allowed_periods = [
		'minute' => 300,
		'hour' => 3600,
		'day' => 86400,
	];

	private array $_allowed_formats = [
		'categories',
		'dictionaries',
		'table',
	];

	public function getSuggestions($type, array $params = []) {
		$metrics = DAO_Metric::getAll();
		$metric_names = array_column($metrics, 'name');

		return [
			'' => [
				[
					'caption' => 'series:',
					'snippet' => "series.\${1:name}:(\n  \n)",
				],
				'period:',
				'range:',
				'timeout:',
				'timezone:',
				'format:',
			],
			'period:' => array_keys($this->_allowed_periods),
			'range:' => [
				'today',
				'yesterday',
				'"-2 hours"',
				'"-24 hours"',
				'"this week"',
				'"this month"',
				'"last week"',
				'"last month"',
			],
			'timeout:' => [
				'20000',
			],
			'timezone:' => DevblocksPlatform::services()->date()->getTimezones(),
			'format:' => $this->_allowed_formats,
			'series.*:' => [
				'' => [
					[
						'caption' => 'by:',
						'snippet' => 'by:[${1:dimension_name}]',
					],
					'function:',
					'label:',
					'metric:',
					'query:',
				],
				'function:' => array_keys($this->_allowed_functions),
				'metric:' => $metric_names,
			],
		];
	}

	public function getData($query, $chart_fields, &$error = null, array $options = []) {
		$model = [
			'type' => 'metrics.subtotals',
			'series' => [],
			'range' => '-24 hours',
			'period_unit' => null, // null = auto-pick by span
			'timeout' => 20000,
			'format' => 'dictionaries',
		];

		foreach($chart_fields as $field) {
			$oper = $value = null;

			if(!($field instanceof DevblocksSearchCriteria))
				continue;

			if($field->key == 'type') {
				continue;

			} else if($field->key == 'range') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$model['range'] = DevblocksPlatform::strLower($value);

			} else if($field->key == 'period') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$period = DevblocksPlatform::strLower($value);

				if(!array_key_exists($period, $this->_allowed_periods)) {
					$error = sprintf("Unknown `period:` (%s). Must be one of: %s",
						$period,
						implode(', ', array_keys($this->_allowed_periods))
					);
					return false;
				}

				$model['period_unit'] = $this->_allowed_periods[$period];

			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$format = DevblocksPlatform::strLower($value);

				if(false === array_search($format, $this->_allowed_formats)) {
					$error = sprintf("Unknown `format:` (%s). Must be one of: %s",
						$format,
						implode(', ', $this->_allowed_formats)
					);
					return false;
				}

				$model['format'] = $format;

			} else if($field->key == 'timezone') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);

				if(!$value)
					$value = DevblocksPlatform::getTimezone();

				if(!DevblocksPlatform::services()->date()->isValidTimezoneLocation($value)) {
					$error = sprintf('Timezone `%s` is unknown.', $value);
					return false;
				}

				$model['timezone'] = $value;

			} else if($field->key == 'timeout') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$model['timeout'] = DevblocksPlatform::intClamp($value, 0, 60000);

			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);

				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);

				$series_id = explode('.', $field->key, 2)[1];

				$series_model = [
					'id' => $series_id,
					'metric' => '',
					'by' => [],
					'query' => [],
					'function' => 'count',
				];

				foreach($series_fields as $series_field) {
					if($series_field->key == 'metric') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['metric'] = $value;

					} else if($series_field->key == 'function') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$function = DevblocksPlatform::strLower($value);

						if(!array_key_exists($function, $this->_allowed_functions)) {
							$error = sprintf("Unknown value for `function:` (%s). Must be one of: %s",
								$function,
								implode(', ', array_keys($this->_allowed_functions))
							);
							return false;
						}

						$series_model['function'] = $this->_allowed_functions[$function];

					} else if($series_field->key == 'by') {
						CerbQuickSearchLexer::getOperArrayFromTokens($series_field->tokens, $oper, $value);

						if(is_array($value)) {
							foreach($value as $k)
								$series_model['by'][] = ['with' => $k, 'record_label' => '_label'];
						}

					} else if($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;

					} else if($series_field->key == 'query') {
						$series_filter_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$series_filter_query = substr($series_filter_query, 1, -1);

						$series_model['query'] = [];

						$series_filter_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_filter_query);

						foreach($series_filter_fields as $series_filter_field) {
							// Parameterized (deep-search) filters on record dimensions
							if('T_GROUP' == ($series_filter_field->tokens[0]->type ?? null)) {
								$oper = DevblocksSearchCriteria::OPER_CUSTOM;
								$value = CerbQuickSearchLexer::getTokensAsQuery($series_filter_field->tokens);

							} else {
								CerbQuickSearchLexer::getOperArrayFromTokens($series_filter_field->tokens, $oper, $value);
							}

							$series_model['query'][] = [
								'key' => $series_filter_field->key,
								'oper' => $oper,
								'value' => $value,
							];
						}

					} else {
						$error = sprintf("The series parameter '%s' is unknown.", $series_field->key);
						return false;
					}
				}

				if(!$series_model['metric']) {
					$error = sprintf("Series `%s` is missing a `metric:`.", $series_id);
					return false;
				}

				$model['series'][] = $series_model;

			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}

		if(!$model['series']) {
			$error = "At least one `series:` is required.";
			return false;
		}

		if(!array_key_exists('timezone', $model))
			$model['timezone'] = DevblocksPlatform::getTimezone();

		$db = DevblocksPlatform::services()->database();
		$platform_timezone = DevblocksPlatform::getTimezone();

		try {
			// "today"/"this week"/etc. resolve in the requested timezone
			DevblocksPlatform::setTimezone($model['timezone']);
			$db->SetReaderTimezone($model['timezone']);

			return $this->_getData($model, $error);

		} catch(Throwable $e) {
			$error = "An unexpected error occurred.";
			return false;

		} finally {
			DevblocksPlatform::setTimezone($platform_timezone);
			$db->ResetReaderTimezone();
		}
	}

	private function _getData(array $model, &$error = null) {
		$range = DevblocksPlatform::services()->date()->parseDateRange($model['range']);

		$from_ts = intval($range['from_ts'] ?? 0);
		$to_ts = intval($range['to_ts'] ?? time());

		// Pick the finest stored granularity whose retention can cover the span, unless overridden.
		// (Each granularity holds complete, independent totals, so SUM-ing its bins gives the true total.)
		$granularity = $model['period_unit'];

		if(is_null($granularity)) {
			$span = max(0, $to_ts - $from_ts);

			if($span <= 86400) // <= 1 day: 5-minute bins (1-day retention covers it)
				$granularity = 300;
			else if($span <= (14 * 86400)) // <= 14 days: hourly (14-day retention)
				$granularity = 3600;
			else // longer: daily
				$granularity = 86400;
		}

		// Daily bins live on the UTC midnight grid (DAO_MetricValue); resolve the window in UTC so the
		// range edges line up with it. Span is timezone-robust, so granularity was picked correctly above.
		if(86400 == $granularity && 'UTC' != $model['timezone']) {
			$tz = DevblocksPlatform::getTimezone();
			DevblocksPlatform::setTimezone('UTC');
			$range = DevblocksPlatform::services()->date()->parseDateRange($model['range']);
			DevblocksPlatform::setTimezone($tz);
			$from_ts = intval($range['from_ts'] ?? 0);
			$to_ts = intval($range['to_ts'] ?? time());
		}

		// Align the lower bound down to the granularity so partial bins at the edge are included
		$from_ts -= ($from_ts % $granularity);

		// One flat row per (series, dimension tuple). `dictionaries` returns these rows as-is; `table`
		// wraps them with column metadata. (Chart-shaped, series-keyed output is a separate concern.)
		$rows = [];
		$columns = [];

		foreach($model['series'] as $series_model) {
			if(false === ($series_rows = $this->_loadSeriesData($series_model, $granularity, $from_ts, $to_ts, $error)))
				return false;

			$series_label = ($series_model['label'] ?? null) ?: $series_model['id'];

			foreach($series_rows as $row) {
				$row['series'] = $series_label;
				$rows[] = $row;
			}

			// Collect dimension columns (same across series, keyed so duplicates collapse)
			foreach($series_model['by'] as $by) {
				$name = $by['with'];
				$columns[$name] = ['label' => DevblocksPlatform::strTitleCase($name), 'type' => DevblocksSearchCriteria::TYPE_TEXT];
				$columns[$name . '__label'] = ['label' => DevblocksPlatform::strTitleCase($name) . ' label', 'type' => DevblocksSearchCriteria::TYPE_TEXT];
			}
		}

		$columns['series'] = ['label' => 'Series', 'type' => DevblocksSearchCriteria::TYPE_TEXT];
		$columns['value'] = ['label' => 'Value', 'type' => DevblocksSearchCriteria::TYPE_NUMBER];

		$meta = [
			'type' => 'metrics.subtotals',
			'format' => $model['format'],
			'range' => [
				'from_ts' => $from_ts,
				'to_ts' => $to_ts,
			],
			'granularity' => $granularity,
		];

		switch($model['format']) {
			case 'categories':
				// Chart-ready columnar shape, keyed by series (the first dimension is the x-axis)
				return $this->_formatDataAsCategories($rows, $model, $meta);

			case 'table':
				return ['data' => ['columns' => $columns, 'rows' => $rows], '_' => $meta];

			default: // dictionaries: the flat list of row dicts
				return ['data' => $rows, '_' => $meta];
		}
	}

	// C3/billboard "columns" shape: [['label', cat1, cat2, ...], [seriesA, v1, v2, ...], ...]. The first
	// `by:` dimension is the x-axis (categories); each series is its own row aligned to those categories.
	private function _formatDataAsCategories(array $rows, array $model, array $meta) {
		$cat_dim = $model['series'][0]['by'][0]['with'] ?? null;

		// No dimension to categorize by -> one category per series (the grand total)
		if(!$cat_dim) {
			$data = [['label', 'value']];
			foreach($rows as $row)
				$data[] = [$row['series'], floatval($row['value'] ?? 0)];

			$meta['format_params'] = ['xaxis_key' => 'label'];
			return ['data' => $data, '_' => $meta];
		}

		$label_key = $cat_dim . '__label';

		// Ordered unique categories (raw value -> display label) and per-series values
		$categories = [];
		$series_values = [];

		foreach($rows as $row) {
			if(!array_key_exists($cat_dim, $row))
				continue;

			$raw = (string) $row[$cat_dim];
			$categories[$raw] = $row[$label_key] ?? $raw;
			$series_values[$row['series']][$raw] = floatval($row['value'] ?? 0);
		}

		$data = [array_merge(['label'], array_values($categories))];

		foreach($series_values as $series_name => $vals) {
			$col = [$series_name];

			foreach(array_keys($categories) as $raw)
				$col[] = $vals[$raw] ?? 0;

			$data[] = $col;
		}

		$meta['format_params'] = ['xaxis_key' => 'label'];

		return ['data' => $data, '_' => $meta];
	}

	private function _loadSeriesData(array $series_model, int $granularity, int $from_ts, int $to_ts, &$error = null) {
		$db = DevblocksPlatform::services()->database();

		if(!($metric = DAO_Metric::getByName($series_model['metric'] ?? null))) {
			$error = sprintf("Unknown `metric:` (%s).", $series_model['metric'] ?? '');
			return false;
		}

		$metric_dimensions = $metric->getDimensions();

		// Dimension-value filters (query:) — same per-type resolution as metrics.timeseries
		$sql_wheres = [];

		if(array_key_exists('query', $series_model) && is_array($series_model['query'])) {
			foreach($series_model['query'] as $filter) {
				$metric_dimension = $metric_dimensions[$filter['key']] ?? null;

				if(is_null($metric_dimension)) {
					$error = sprintf("Query filter `%s:` is unknown.", $filter['key']);
					return false;
				}

				$dim_key = sprintf('dim%d_value_id', array_search($filter['key'], array_keys($metric_dimensions)));

				switch($metric_dimension['type']) {
					case 'number':
					case 'record':
						// record/number dims store the literal id/number in dimN_value_id
						if(
							in_array($filter['oper'], [DevblocksSearchCriteria::OPER_IN, DevblocksSearchCriteria::OPER_NIN])
							&& is_array($filter['value']) && $filter['value']
						) {
							if(array_filter($filter['value'], fn($n) => !is_numeric($n))) {
								$error = sprintf("Query filter `%s:` must be a number or a list of numbers.", $filter['key']);
								return false;
							}

							$sql_wheres[] = sprintf('%s %sIN (%s)',
								$db->escape($dim_key),
								$filter['oper'] == DevblocksSearchCriteria::OPER_NIN ? 'NOT ' : '',
								implode(',', $db->qstrArray(DevblocksPlatform::sanitizeArray($filter['value'], 'int')))
							);

						// Deep search on records resolves to a set of record ids
						} elseif('record' == $metric_dimension['type'] && $filter['oper'] == DevblocksSearchCriteria::OPER_CUSTOM) {
							if(!($context_ext = Extension_DevblocksContext::getByAlias($metric_dimension['params']['record_type'] ?? null, true))) {
								$error = sprintf('Query filter `%s:` is an unknown record type (`%s`).',
									$filter['key'],
									$metric_dimension['params']['record_type'] ?? null
								);
								return false;
							}

							$dao_class = $context_ext->getDaoClass();
							$search_class = $context_ext->getSearchClass();

							if(!($view = $context_ext->getTempView())) {
								$error = sprintf('Query filter `%s:` failed to initialize a worklist.', $filter['key']);
								return false;
							}

							$view->addParamsWithQuickSearch($filter['value'] ?? '', true);
							$view->renderPage = 0;
							$view->renderTotal = false;

							$query_parts = $dao_class::getSearchQueryComponents($view->view_columns, $view->getParams());

							$sql_subquery = sprintf("SELECT %s AS id %s%s",
								$search_class::getPrimaryKey(),
								$query_parts['join'],
								$query_parts['where']
							);

							$sql_wheres[] = sprintf('%s IN (%s)',
								$db->escape($dim_key),
								str_replace('%', '%%', $sql_subquery)
							);

						} else {
							$error = sprintf("Query filter `%s:` must be a number or a list of numbers.", $filter['key']);
							return false;
						}
						break;

					default: // string/extension dims store a metric_dimension.id (lookup by name)
						if($filter['oper'] == DevblocksSearchCriteria::OPER_IN && is_array($filter['value']) && $filter['value']) {
							$sql_wheres[] = sprintf('%s IN (SELECT id FROM metric_dimension WHERE name IN (%s))',
								$db->escape($dim_key),
								implode(',', $db->qstrArray($filter['value']))
							);

						} else {
							$error = sprintf("Query filter `%s:` must be a string or a list of strings.", $filter['key']);
							return false;
						}
						break;
				}
			}
		}

		// SELECT the grouped dimension slots
		$sql_select_dims = [];
		$sql_group_by = [];
		$by_slots = []; // by_idx => ['name'=>, 'dimension'=>, 'record_label'=>]

		foreach($series_model['by'] as $by_idx => $by_meta) {
			$dim_name = $by_meta['with'];

			if(!array_key_exists($dim_name, $metric_dimensions)) {
				$error = sprintf("Unknown series dimension (%s). Should be one of: %s",
					$dim_name,
					implode(', ', array_keys($metric_dimensions))
				);
				return false;
			}

			$slot = array_search($dim_name, array_keys($metric_dimensions));
			$alias = sprintf('by_%d', $by_idx);

			$sql_select_dims[] = sprintf('dim%d_value_id AS %s', $slot, $alias);
			$sql_group_by[] = $alias;

			$by_slots[$by_idx] = [
				'name' => $dim_name,
				'dimension' => $metric_dimensions[$dim_name],
				'record_label' => $by_meta['record_label'] ?? '_label',
				'alias' => $alias,
			];
		}

		$sql_agg = match($series_model['function']) {
			'sum' => 'SUM(`sum`)',
			'avg' => 'IF(SUM(samples) = 0, 0, SUM(`sum`) / SUM(samples))',
			'min' => 'MIN(`min`)',
			'max' => 'MAX(`max`)',
			default => 'SUM(samples)', // count
		};

		$sql = sprintf("SELECT %s%s AS value FROM metric_value ".
			"WHERE metric_id = %d AND granularity = %d AND bin BETWEEN %d AND %d %s%s",
			$sql_select_dims ? (implode(', ', $sql_select_dims) . ', ') : '',
			$sql_agg,
			$metric->id,
			$granularity,
			$from_ts,
			$to_ts,
			$sql_wheres ? ('AND ' . implode(' AND ', $sql_wheres) . ' ') : '',
			$sql_group_by ? ('GROUP BY ' . implode(', ', $sql_group_by)) : ''
		);

		$rows = $db->GetArrayReader($sql);

		if(!is_array($rows))
			return [];

		// Resolve each grouped dimension's raw value + human label, per dimension type
		foreach($by_slots as $by_idx => $by_slot) {
			$alias = $by_slot['alias'];
			$dim_type = $by_slot['dimension']['type'] ?? 'string';

			if('record' == $dim_type) {
				$record_type = $by_slot['dimension']['params']['record_type'] ?? null;
				$record_label = $by_slot['record_label'];

				$record_ids = array_unique(array_filter(array_map('intval', array_column($rows, $alias))));
				$models = $record_type ? CerberusContexts::getModels($record_type, $record_ids) : [];
				$dicts = $models ? DevblocksDictionaryDelegate::getDictionariesFromModels($models, $record_type, [$record_label]) : [];

				foreach($rows as $i => $row) {
					$raw = $row[$alias]; // literal record id
					$rows[$i][$by_slot['name']] = $raw;
					$rows[$i][$by_slot['name'] . '__label'] = isset($dicts[$raw]) ? $dicts[$raw]->get($record_label) : $raw;
					$rows[$i][$by_slot['name'] . '__context'] = $record_type;
					unset($rows[$i][$alias]);
				}

			} else if('number' == $dim_type) {
				foreach($rows as $i => $row) {
					$rows[$i][$by_slot['name']] = $row[$alias];
					$rows[$i][$by_slot['name'] . '__label'] = $row[$alias];
					unset($rows[$i][$alias]);
				}

			} else { // string / extension: dimN_value_id -> metric_dimension.name
				$dim_value_ids = array_unique(array_filter(array_map('intval', array_column($rows, $alias))));
				$names = $dim_value_ids ? array_column(DAO_MetricDimension::getIds($dim_value_ids), 'name', 'id') : [];

				$extensions = ('extension' == $dim_type) ? DevblocksPlatform::getExtensionRegistry() : [];

				foreach($rows as $i => $row) {
					$name = $names[$row[$alias]] ?? $row[$alias]; // the raw dimension value (e.g. extension_id)
					$rows[$i][$by_slot['name']] = $name;

					if('extension' == $dim_type && isset($extensions[$name]))
						$rows[$i][$by_slot['name'] . '__label'] = $extensions[$name]->name;
					else
						$rows[$i][$by_slot['name'] . '__label'] = $name;

					unset($rows[$i][$alias]);
				}
			}
		}

		// Normalize the aggregate to a float
		foreach($rows as $i => $row)
			$rows[$i]['value'] = floatval($row['value']);

		return $rows;
	}
};
