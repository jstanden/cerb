/*
 * CerbMetricsExplorer — an interactive, client-side metric chart builder.
 *
 * Rendered by the `cerb.card.widget.metrics.explorer` card widget on a metric record card, but written as
 * a self-contained component so it can be reused elsewhere (e.g. the Search->Metrics worklist) later.
 *
 * The widget config carries default series (as KATA, placeholders allowed) + range/period/chart defaults;
 * all live exploration happens here. Series specs are sent to the widget's invoke() actions, which build +
 * run the `metrics.timeseries` KATA server-side (resolving {{record_*}} placeholders against the current
 * record) and return a ready-to-draw C3 config. The chart call is isolated in renderChart() so the C3
 * dependency can be swapped for a CerbUI-native chart later.
 *
 * Bootstrap shape:
 *   { widget_id, record__context, record_id,
 *     current_metric:{name,type,dimensions:[{name,type,params}]}|null,
 *     metrics:[{name,type,description}], functions:[...],
 *     config_series:[{metric,function,label,color,filters:[{dimension,values}]}],
 *     defaults:{range,period,chart_as} }
 */
class CerbMetricsExplorer {
	constructor(el, bootstrap) {
		this.el = el;
		bootstrap = bootstrap || {};

		this.widgetId = bootstrap.widget_id;
		this.recordContext = bootstrap.record__context || '';
		this.recordId = bootstrap.record_id || '';
		this.metrics = bootstrap.metrics || [];
		this.functions = bootstrap.functions || ['count', 'sum', 'avg', 'min', 'max'];
		this.currentMetric = bootstrap.current_metric || null;
		this.configSeries = bootstrap.config_series || [];

		const defaults = bootstrap.defaults || {};
		this.range = defaults.range || '-24 hours to now';
		this.period = defaults.period || 'hour';

		// Placeholder tokens offered in the choosers (resolved server-side against the current record)
		this.metricPlaceholders = [{ value: '{{record_name}}', label: '⟨ current record ⟩' }];
		this.filterPlaceholders = [{ value: '{{record_id}}', label: '⟨ current record id ⟩' }];

		this.series = [];
		this.seq = 0;
		this.lastDataQuery = '';
		this.lastSeriesKata = '';
		this.lastDatasetsKata = '';
		this.lastChartKata = '';
		this.chart = null;
		// Collapse the series editor by default when the widget shipped with a saved config
		this.configCollapsed = this.configSeries.length > 0;
		// Unique per (widget, record) so multiple open metric cards don't collide
		this.uniqid = bootstrap.uniqid || this.widgetId;
		this.chartId = 'mxChart' + this.uniqid;

		this.palette = (window.CerbUI && CerbUI.palettes && CerbUI.palettes.category10)
			|| ['#0088e6', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];

		// Cache the current metric's dimensions so a {{record_name}} series needs no round-trip
		this.dimCache = {};
		if(this.currentMetric)
			this.dimCache[this.currentMetric.name] = this.currentMetric.dimensions || [];

		this._build();

		// Seed series from config (placeholders preserved); else a sensible default
		if(this.configSeries.length) {
			this.configSeries.forEach(spec => this.addSeries(spec));
		} else if(this.currentMetric) {
			this.addSeries({ metric: '{{record_name}}', function: 'count' });
		} else {
			this.addSeries({ metric: '', function: 'count' });
		}

		this._applyConfigVisibility();
		this.refresh();
	}

	// === DOM helpers =========================================================

	_el(tag, cls, props) {
		const node = document.createElement(tag);
		if(cls) node.className = cls;
		if(props) Object.assign(node, props);
		return node;
	}

	_select(options, value) {
		const sel = this._el('select');
		options.forEach(opt => {
			const o = this._el('option');
			o.value = (typeof opt === 'object') ? opt.value : opt;
			o.textContent = (typeof opt === 'object') ? opt.label : opt;
			if(o.value === value) o.selected = true;
			sel.appendChild(o);
		});
		return sel;
	}

	// === Layout ==============================================================

	_build() {
		this.el.innerHTML = '';

		// --- Toolbar: range + period + chart type + export ---
		const toolbar = this._el('div', 'cerb-metrics-explorer--toolbar cerb-u-flex cerb-u-items-center cerb-u-gap-3 cerb-u-flex-wrap');

		const rangeWrap = this._el('label', 'cerb-metrics-explorer--field cerb-u-flex-inline cerb-u-items-center cerb-u-gap-1');
		rangeWrap.appendChild(this._el('span', 'cerb-metrics-explorer--field-label cerb-u-fs-n3 cerb-u-text-muted', { textContent: 'Range' }));
		this.rangeInput = this._el('input', 'cerb-metrics-explorer--range', { type: 'text', value: this.range, style: 'width:16em;max-width:100%;' });
		this.rangeInput.addEventListener('change', () => { this.range = this.rangeInput.value.trim(); this.refresh(); });
		rangeWrap.appendChild(this.rangeInput);
		toolbar.appendChild(rangeWrap);

		toolbar.appendChild(this._buildSwitcher('period', [
			{ value: 'minute', label: '5 min' },
			{ value: 'hour', label: 'Hour' },
			{ value: 'day', label: 'Day' },
		], this.period, (v) => { this.period = v; this.refresh(); }));

		// Edit toggle — collapses the series editor; sits left of the Copy button
		toolbar.appendChild(this._buildEditToggle());
		toolbar.appendChild(this._buildCopyMenu());
		toolbar.appendChild(this._el('span', 'cerb-u-flex-1'));

		this.el.appendChild(toolbar);

		// --- Chart + status (above the series so it doesn't jump as series change) ---
		this.chartEl = this._el('div', 'cerb-metrics-explorer--chart', { id: this.chartId, style: 'min-height:320px;' });
		this.el.appendChild(this.chartEl);

		this.statusEl = this._el('div', 'cerb-metrics-explorer--status cerb-u-fs-n2 cerb-u-text-muted');
		this.el.appendChild(this.statusEl);

		// --- Series list ---
		this.seriesList = this._el('div', 'cerb-metrics-explorer--series cerb-u-flex cerb-u-flex-column cerb-u-gap-2');
		this.el.appendChild(this.seriesList);

		this.addBtn = this._el('button', 'cerb-ui-button cerb-ui-button--subtle cerb-metrics-explorer--add-series', { type: 'button', textContent: '+ Add series' });
		this.addBtn.addEventListener('click', () => {
			const metric = this.currentMetric ? '{{record_name}}' : (this.metrics[0] && this.metrics[0].name) || '';
			this.addSeries({ metric: metric, function: 'count' });
			this._applyConfigVisibility();
			this.refresh();
		});
		this.el.appendChild(this.addBtn);
	}

	_buildEditToggle() {
		this.editToggle = this._el('button', 'cerb-ui-button cerb-ui-button--subtle cerb-metrics-explorer--edit-toggle', { type: 'button' });
		this.editToggleIcon = this._el('span', 'cerb-icons');
		this.editToggleLabel = this._el('span');
		this.editToggle.appendChild(this.editToggleIcon);
		this.editToggle.appendChild(this.editToggleLabel);
		this.editToggle.addEventListener('click', () => {
			this.configCollapsed = !this.configCollapsed;
			this._applyConfigVisibility();
		});
		return this.editToggle;
	}

	// Show/hide the series editor. Collapsed only applies when there's a series to collapse;
	// with zero series the editor is always shown (and the toggle hidden).
	_applyConfigVisibility() {
		const hasSeries = this.series.length > 0;
		const collapsed = this.configCollapsed && hasSeries;

		this.seriesList.classList.toggle('cerb-u-hide', collapsed);
		this.addBtn.classList.toggle('cerb-u-hide', collapsed);
		this.editToggle.classList.toggle('cerb-u-hide', !hasSeries);

		this.editToggleIcon.className = 'cerb-icons cerb-icon-' + (collapsed ? 'edit' : 'chevron-up');
		this.editToggleLabel.textContent = collapsed ? ' Edit chart' : ' Hide editor';
	}

	_buildSwitcher(name, options, value, onSelect) {
		const wrap = this._el('div', 'cerb-metrics-explorer--field cerb-u-flex-inline cerb-u-items-center cerb-u-gap-1');
		wrap.appendChild(this._el('span', 'cerb-metrics-explorer--field-label cerb-u-fs-n3 cerb-u-text-muted', { textContent: name === 'period' ? 'Period' : 'Chart' }));

		const sw = this._el('div', 'cerb-ui-switcher');
		options.forEach(opt => {
			const b = this._el('button', null, { type: 'button', textContent: opt.label });
			b.dataset.value = opt.value;
			sw.appendChild(b);
		});
		wrap.appendChild(sw);

		new CerbUI.Switcher(sw, { value: value, onSelect: (v) => onSelect(v) });
		return wrap;
	}

	// A compact, label-less switcher for per-series controls (chart type, axis).
	// Each option: { value, label?, icon?, title? } — icon renders a cerb-icons glyph, title is the tooltip.
	_inlineSwitcher(options, value, onSelect) {
		const sw = this._el('div', 'cerb-ui-switcher cerb-metrics-explorer--switcher');
		options.forEach(opt => {
			const b = this._el('button', null, { type: 'button' });
			b.dataset.value = opt.value;
			b.title = opt.title || opt.label || '';
			if(opt.icon) {
				b.appendChild(this._el('span', 'cerb-icons cerb-icon-' + opt.icon));
			} else {
				b.textContent = opt.label || '';
			}
			sw.appendChild(b);
		});
		new CerbUI.Switcher(sw, { value: value, onSelect: (v) => onSelect(v) });
		return sw;
	}

	// A single "Copy" button opening a CerbUI.Menu (Data Query / Chart Widget / Explorer Config)
	_buildCopyMenu() {
		const wrap = this._el('div', 'cerb-metrics-explorer--export');

		const btn = this._el('button', 'cerb-ui-button cerb-ui-button--subtle', { type: 'button' });
		btn.appendChild(this._el('span', 'cerb-icons cerb-icon-copy'));
		btn.appendChild(this._el('span', null, { textContent: ' Copy' }));
		wrap.appendChild(btn);

		const ul = this._el('ul', 'cerb-metrics-explorer--copy-menu');
		ul.style.display = 'none';
		[
			{ action: 'dataQuery', label: 'Data Query' },
			{ action: 'chartWidget', label: 'Chart Widget' },
			{ action: 'explorerConfig', label: 'Explorer Config' },
		].forEach(item => {
			const li = this._el('li', null, { textContent: item.label });
			li.dataset.action = item.action;
			ul.appendChild(li);
		});
		wrap.appendChild(ul);

		const menu = new CerbUI.Menu(ul, {
			onSelect: (renderedLi, sourceLi) => {
				const action = sourceLi.dataset.action;
				if(action === 'dataQuery') this._copy(this.lastDataQuery, btn);
				else if(action === 'chartWidget') this._copyChartWidget(btn);
				else if(action === 'explorerConfig') this._copy(this.lastSeriesKata, btn);
			},
		});

		btn.addEventListener('click', () => menu.isOpen() ? menu.close() : menu.open(btn));

		return wrap;
	}

	// === Series ==============================================================

	addSeries(spec) {
		spec = spec || {};
		const idx = this.series.length;

		const state = {
			uid: ++this.seq,
			metric: spec.metric || '',
			function: spec.function || 'count',
			label: spec.label || '',
			color: spec.color || this.palette[idx % this.palette.length],
			type: ['line', 'bar', 'area'].indexOf(spec.type) >= 0 ? spec.type : 'line',
			axis: spec.axis === 'y2' ? 'y2' : 'y',
			stack: (spec.stack != null && String(spec.stack) !== '') ? String(spec.stack) : '',
			hidden: !!spec.hidden,
			filters: [],
			el: null,
		};

		// Expand config filters ({dimension, values:[...]}) into single-value rows
		(spec.filters || []).forEach(f => {
			(f.values || []).forEach(v => {
				state.filters.push({ uid: ++this.seq, dimension: f.dimension, value: String(v), valueLabel: String(v), negate: !!f.negate });
			});
		});

		this.series.push(state);
		this._renderSeriesRow(state);

		if(state.metric)
			this._loadDimensions(state.metric, () => this._renderFilters(state));

		return state;
	}

	_renderSeriesRow(state) {
		const row = this._el('div', 'cerb-metrics-explorer--series-row cerb-ui-panel');
		state.el = row;

		const top = this._el('div', 'cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap');

		// Visibility toggle (hidden series are excluded from the chart but stay here, dimmed)
		const eyeBtn = this._el('button', 'cerb-ui-button cerb-ui-button--subtle cerb-metrics-explorer--visibility', { type: 'button' });
		const eyeIcon = this._el('span', 'cerb-icons');
		eyeBtn.appendChild(eyeIcon);
		const applyVis = () => {
			eyeIcon.className = 'cerb-icons cerb-icon-eye-' + (state.hidden ? 'close' : 'open');
			eyeBtn.title = state.hidden ? 'Series hidden — click to show' : 'Hide series';
			row.classList.toggle('cerb-u-opacity-50', state.hidden);
		};
		applyVis();
		eyeBtn.addEventListener('click', () => { state.hidden = !state.hidden; applyVis(); this.refresh(); });
		top.appendChild(eyeBtn);

		// Color
		const colorInput = this._el('input', 'cerb-metrics-explorer--color', { type: 'text', value: state.color });
		top.appendChild(colorInput);
		new CerbUI.ColorPicker(colorInput, {
			showInput: false,
			onChange: (hex) => { state.color = hex; this.refresh(); },
		});

		// Metric chooser (placeholders first, then all metrics)
		const metricOpts = this.metricPlaceholders.concat(this.metrics.map(m => ({ value: m.name, label: m.name })));
		const metricSel = this._select(metricOpts, state.metric);
		metricSel.classList.add('cerb-metrics-explorer--metric');
		metricSel.style.minWidth = '12em';
		top.appendChild(metricSel);
		new CerbUI.SelectMenu(metricSel, {
			placeholder: 'Metric…',
			onSelect: (value) => {
				state.metric = value;
				state.filters = [];
				this._renderFilters(state);
				this._loadDimensions(value, () => this._renderFilters(state));
				this.refresh();
			},
		});

		// Function
		const fnSel = this._select(this.functions, state.function);
		fnSel.classList.add('cerb-metrics-explorer--function');
		top.appendChild(fnSel);
		new CerbUI.SelectMenu(fnSel, {
			filter: false,
			onSelect: (value) => { state.function = value; this.refresh(); },
		});

		// Chart type switcher (icons; tooltips name them)
		top.appendChild(this._inlineSwitcher([
			{ value: 'line', icon: 'chart-line', title: 'Line' },
			{ value: 'bar', icon: 'chart-bar', title: 'Bars' },
			{ value: 'area', icon: 'chart-area', title: 'Area' },
		], state.type, (v) => { state.type = v; this.refresh(); }));

		// Stack group (unstacked, or stack 1–9; series sharing a group stack together)
		const stackOpts = [{ value: '', label: 'unstacked' }];
		for(let n = 1; n <= 9; n++) stackOpts.push({ value: String(n), label: 'stack ' + n });
		const stackSel = this._select(stackOpts, state.stack);
		stackSel.classList.add('cerb-metrics-explorer--stack');
		top.appendChild(stackSel);
		new CerbUI.SelectMenu(stackSel, {
			filter: false,
			onSelect: (value) => { state.stack = value; this.refresh(); },
		});

		// Y axis switcher (icons; tooltips name them)
		top.appendChild(this._inlineSwitcher([
			{ value: 'y', icon: 'chart-axis-y', title: 'Left axis (y)' },
			{ value: 'y2', icon: 'chart-axis-y2', title: 'Right axis (y2)' },
		], state.axis, (v) => { state.axis = v; this.refresh(); }));

		// Label
		const labelInput = this._el('input', 'cerb-metrics-explorer--label', { type: 'text', placeholder: 'Label (optional)', value: state.label, style: 'min-width:8em;' });
		labelInput.addEventListener('change', () => { state.label = labelInput.value.trim(); this.refresh(); });
		top.appendChild(labelInput);

		top.appendChild(this._el('span', 'cerb-u-flex-1'));

		const removeBtn = this._el('button', 'cerb-ui-button cerb-ui-button--subtle', { type: 'button', textContent: '✕' });
		removeBtn.title = 'Remove series';
		removeBtn.addEventListener('click', () => this._removeSeries(state));
		top.appendChild(removeBtn);

		row.appendChild(top);

		// Filters
		state.filtersEl = this._el('div', 'cerb-metrics-explorer--filters cerb-u-flex cerb-u-flex-wrap cerb-u-gap-2 cerb-u-mt-2');
		row.appendChild(state.filtersEl);

		this.seriesList.appendChild(row);
		this._renderFilters(state);
	}

	_removeSeries(state) {
		const i = this.series.indexOf(state);
		if(i >= 0) this.series.splice(i, 1);
		if(state.el) state.el.remove();
		this._applyConfigVisibility();
		this.refresh();
	}

	// === Dimension filters ===================================================

	// Dimensions for a (possibly placeholder) metric: prefer the current metric's cached dims
	_dimsFor(metric) {
		if(this.dimCache[metric])
			return this.dimCache[metric];

		// A placeholder metric on a metric card resolves to the current metric
		if(/^\{\{.*\}\}$/.test(metric) && this.currentMetric)
			return this.currentMetric.dimensions || [];

		return null;
	}

	_renderFilters(state) {
		if(!state.filtersEl) return;
		state.filtersEl.innerHTML = '';

		const dims = this._dimsFor(state.metric) || [];

		state.filters.forEach(f => state.filtersEl.appendChild(this._buildFilterRow(state, f, dims)));

		if(dims.length) {
			const addBtn = this._el('button', 'cerb-ui-button cerb-ui-button--subtle cerb-metrics-explorer--add-filter', { type: 'button', textContent: '+ Filter' });
			addBtn.addEventListener('click', () => {
				const f = { uid: ++this.seq, dimension: dims[0].name, value: '', valueLabel: '', negate: false };
				state.filters.push(f);
				this._renderFilters(state);
			});
			state.filtersEl.appendChild(addBtn);
		}
	}

	_buildFilterRow(state, filter, dims) {
		const row = this._el('div', 'cerb-metrics-explorer--filter cerb-ui-chip cerb-u-flex cerb-u-items-center cerb-u-gap-1', { style: 'padding:0.15em 0.4em;' });

		const dimSel = this._select(dims.map(d => ({ value: d.name, label: d.name })), filter.dimension);
		row.appendChild(dimSel);
		new CerbUI.SelectMenu(dimSel, {
			filter: dims.length > 8,
			onSelect: (value) => {
				filter.dimension = value;
				filter.value = '';
				filter.valueLabel = '';
				this._renderFilters(state);
				this.refresh();
			},
		});

		// is / not operator (not -> NOT IN, e.g. "all other groups")
		const opSel = this._select([{ value: 'is', label: 'is' }, { value: 'not', label: 'not' }], filter.negate ? 'not' : 'is');
		opSel.classList.add('cerb-metrics-explorer--filter-op');
		row.appendChild(opSel);
		new CerbUI.SelectMenu(opSel, {
			filter: false,
			onSelect: (value) => { filter.negate = (value === 'not'); this.refresh(); },
		});

		// Value chooser (placeholders + async-enumerated values)
		const valWrap = this._el('span', 'cerb-metrics-explorer--filter-value');
		valWrap.textContent = '…';
		row.appendChild(valWrap);

		this._loadDimensionValues(state.metric, filter.dimension, (values) => {
			valWrap.textContent = '';
			const opts = [{ value: '', label: '(all)' }]
				.concat(this.filterPlaceholders)
				.concat(values.map(v => ({ value: v.id, label: v.label })));
			const valSel = this._select(opts, filter.value);
			valWrap.appendChild(valSel);
			new CerbUI.SelectMenu(valSel, {
				filter: true,
				placeholder: '(all)',
				onSelect: (value, text) => {
					filter.value = value;
					filter.valueLabel = text;
					this.refresh();
				},
			});
		});

		const removeBtn = this._el('button', 'cerb-ui-button cerb-ui-button--subtle', { type: 'button', textContent: '✕' });
		removeBtn.addEventListener('click', () => {
			const i = state.filters.indexOf(filter);
			if(i >= 0) state.filters.splice(i, 1);
			this._renderFilters(state);
			this.refresh();
		});
		row.appendChild(removeBtn);

		return row;
	}

	// === Server interactions =================================================

	_invoke(action, params, callback) {
		const fd = new FormData();
		fd.set('c', 'profiles');
		fd.set('a', 'invoke');
		fd.set('module', 'card_widget');
		fd.set('action', 'invokeWidget');
		fd.set('widget_id', String(this.widgetId));
		fd.set('invoke_action', action);
		fd.set('card_context', this.recordContext);
		fd.set('card_context_id', String(this.recordId));

		Object.keys(params || {}).forEach(k => fd.set(k, params[k]));

		genericAjaxPost(fd, null, null, function(json) {
			callback((typeof json === 'object' && json !== null) ? json : null);
		});
	}

	_loadDimensions(metricName, done) {
		if(!metricName) return;

		// Placeholder metric on a metric card -> use the current metric's dims (no round-trip)
		if(this._dimsFor(metricName)) { if(done) done(); return; }

		this._invoke('metricMeta', { metric: metricName }, (json) => {
			this.dimCache[metricName] = (json && json.dimensions) ? json.dimensions : [];
			if(done) done();
		});
	}

	_loadDimensionValues(metricName, dimension, callback) {
		const key = metricName + '::' + dimension + '::' + this.range;
		this._valueCache = this._valueCache || {};

		if(this._valueCache[key]) { callback(this._valueCache[key]); return; }

		this._invoke('dimensionValues', { metric: metricName, dimension: dimension, range: this.range }, (json) => {
			const values = (json && json.values) ? json.values : [];
			this._valueCache[key] = values;
			callback(values);
		});
	}

	// === Query + render ======================================================

	_buildSeriesPayload() {
		return this.series
			.filter(s => s.metric)
			.map(s => {
				// Group filter rows by (dimension, negate) → {dimension, values:[...], negate}
				// (OR within a group; `is` and `not` on the same dimension stay distinct)
				const groups = {};
				s.filters.forEach(f => {
					if(!f.dimension || f.value === '' || f.value == null) return;
					const negate = !!f.negate;
					const key = (negate ? '!' : '') + f.dimension;
					(groups[key] = groups[key] || { dimension: f.dimension, negate: negate, values: [] }).values.push(f.value);
				});
				const filters = Object.keys(groups).map(k => groups[k]);

				return {
					metric: s.metric,
					function: s.function,
					label: s.label,
					color: s.color,
					type: s.type,
					axis: s.axis,
					stack: s.stack,
					hidden: s.hidden,
					filters: filters,
				};
			});
	}

	refresh() {
		clearTimeout(this._refreshTimer);
		this._refreshTimer = setTimeout(() => this._doRefresh(), 150);
	}

	_doRefresh() {
		const specs = this._buildSeriesPayload();

		if(!specs.length) {
			this.statusEl.textContent = 'Add a series to chart.';
			this._destroyChart();
			return;
		}

		this.statusEl.textContent = 'Loading…';

		this._invoke('query', {
			range: this.range,
			period: this.period,
			series: JSON.stringify(specs),
		}, (json) => {
			if(!json) { this.statusEl.textContent = 'Request failed.'; return; }

			if(json.error) { this.statusEl.textContent = 'Error: ' + json.error; this._destroyChart(); return; }

			this.lastDataQuery = json.data_query || '';
			this.lastSeriesKata = json.series_kata || '';
			this.lastDatasetsKata = json.datasets_kata || '';
			this.lastChartKata = json.chart_kata || '';
			this.statusEl.textContent = '';
			this.renderChart(json);
		});
	}

	// Isolated chart renderer — builds a CerbUI.CartesianChart from the client's own series metadata
	// (type/axis/stack/color/label) zipped onto the raw {ts, <label>:[…]} timeseries the server returns.
	renderChart(payload) {
		const data = payload && payload.data;
		const meta = payload && payload.meta;
		const ts = (data && data.ts) || [];

		// Series metadata is owned client-side; the server only supplies the numbers.
		const specs = this.series.filter(s => s.metric && !s.hidden);
		const hasRows = ts.length > 0 && specs.some(s => Array.isArray(data[s.label]) && data[s.label].length > 0);

		if(!hasRows) {
			this.statusEl.textContent = '(no data)';
			this._destroyChart();
			return;
		}

		// PHP date tokens -> strftime (minutes/seconds); fall back to a full timestamp pattern.
		const xaxisFormat = ((meta && meta.format_params && meta.format_params.xaxis_format) || '%Y-%m-%d %H:%M')
			.replace('%i', '%M').replace('%s', '%S');
		const timestamps = ts.map(s => Date.parse(String(s).replace(' ', 'T')));

		const series = specs.map(s => ({
			key: s.label,
			name: s.label,
			type: s.type,               // line | bar | area
			axis: (s.axis === 'y2') ? 'y2' : 'y',
			stack: s.stack || null,
			color: s.color,
			values: (data[s.label] || []).map(v => Number(v) || 0),
		}));

		const options = {
			x: { scale: 'time', timestamps: timestamps, tickFormat: CerbUI.date.strftime(xaxisFormat), rotate: -90 },
			y: { tickFormat: CerbUI.num.format(','), grid: true },
			series: series,
			legend: true,
			height: 320,
		};

		if(specs.some(s => s.axis === 'y2'))
			options.y2 = { tickFormat: CerbUI.num.format(',') };

		try {
			this._destroyChart();
			this.chart = new CerbUI.CartesianChart(this.chartEl, options);
		} catch(e) {
			if(console && console.error) console.error(e);
			this.statusEl.textContent = 'Failed to render chart.';
		}
	}

	_destroyChart() {
		if(this.chart) {
			try { this.chart.destroy(); } catch(e) { /* noop */ }
			this.chart = null;
		}
	}

	// === Export ==============================================================

	_copy(text, btn) {
		if(!text) return;

		// Save/restore innerHTML so the button's icon survives the transient "Copied!" feedback
		const original = btn.innerHTML;
		const done = () => { btn.textContent = 'Copied!'; setTimeout(() => { btn.innerHTML = original; }, 1500); };

		if(navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done, done);
		} else {
			const ta = this._el('textarea');
			ta.value = text;
			document.body.appendChild(ta);
			ta.select();
			try { document.execCommand('copy'); } catch(e) { /* noop */ }
			ta.remove();
			done();
		}
	}

	// Build an importable Chart: KATA card-widget JSON (paste into Add Widget -> import_json).
	// Carries per-series type / y-axis / stacking faithfully.
	_copyChartWidget(btn) {
		if(!this.lastChartKata || !this.lastDatasetsKata) return;

		const widgetJson = {
			widget: {
				_context: 'cerb.contexts.card.widget',
				name: 'Metrics Chart',
				record_type: this.recordContext,
				extension_id: 'cerb.card.widget.chart.kata',
				pos: 0,
				width_units: 8,
				zone: 'content',
				extension_params: {
					datasets_kata: this.lastDatasetsKata,
					chart_kata: this.lastChartKata,
				},
			},
		};

		this._copy(JSON.stringify(widgetJson, null, 2), btn);
	}
}
