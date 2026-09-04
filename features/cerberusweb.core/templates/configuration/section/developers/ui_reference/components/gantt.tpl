	<div class="cerb-uiref-component" id="gantt">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chart-gantt"></span>Gantt</div>

		{* Named rows of spans on one X axis. Where two rows cover the same X, the overlap is the point. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Named rows of spans on a shared axis; where two rows cover the same range, the overlap is the shared region</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-gantt" id="uiref-gantt-a"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// `step` makes the axis DISCRETE: `end` is inclusive and each unit is one cell.
// Without it, spans are half-open [start, end) -- the right reading for time.
new CerbUI.Gantt(el, {
	xScale: 'linear',        // 'linear' (numbers) | 'time' (epoch ms)
	step: 1,                 // one cell per slot; omit for continuous ranges
	// domain: [1, 26],      // omitted -> derived from the spans
	rows: [
		{ label: 'Bulk jobs',   color: '#0088e6', spans: [[1,19]] },
		{ label: 'Agent turns', color: '#9467bd', spans: [[7,25]] },
	],
	tickFormat: (v) =&gt; v,     // axis tick text
});
// Neither row is fragmented, so the 13 slots they both cover ARE the commons --
// there is no third series to invent for it.{/literal}</pre>
			</div>
		</div>

		{* Segmented: the same discrete rows drawn as one block per unit. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Segmented, for units of capacity rather than durations: a block per unit, and the units a row does not hold stay empty</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-gantt" id="uiref-gantt-c"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// `segment` needs `step`, and cuts both the spans and the track into units.
// The spans stay whole in the data, so hover still reads back the run.
new CerbUI.Gantt(el, {
	xScale: 'linear',
	step: 1,
	segment: true,           // one block per slot
	segmentGap: 3,           // px between blocks
	rows: [
		{ label: 'Bulk jobs',   color: '#0088e6', spans: [[1,4]] },
		{ label: 'Agent turns', color: '#9467bd', spans: [[2,5]] },
	],
});
// Five slots: bulk jobs hold 1-4 and agent turns 2-5, so the empty cell at
// either end is a slot that lane cannot take, and slots 2-4 are the commons.{/literal}</pre>
			</div>
		</div>

		{* A time axis: same component, spans as epoch ms, several per row. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A time axis, with several spans per row</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-gantt" id="uiref-gantt-b"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Spans are half-open [start, end). A span may be [start,end] or an object
// carrying its own label, color and key.
new CerbUI.Gantt(el, {
	xScale: 'time',
	rows: [
		{ label: 'Imports', spans: [ { start: t0, end: t1, label: 'contacts.csv' } ] },
		{ label: 'Exports', spans: [ [t2,t3], [t4,t5] ] },
	],
	tickFormat: CerbUI.date.strftime('%H:%M'),
	rowHeight: 30, barHeight: 16, labelWidth: 110,
});

// Events bubble on the element:
//   'cerb-ui-chart:hover'  detail = { row, span, point:{x,y} }
//   'cerb-ui-chart:click'  detail = same shape{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.Gantt))
		return;

	// Discrete: the queue slot lanes. Both rows cover the tail, so the commons reads as the overlap.
	const a = document.getElementById('uiref-gantt-a');

	if(a) {
		new CerbUI.Gantt(a, {
			xScale: 'linear',
			step: 1,
			rows: [
				{ label: 'Bulk jobs', color: '#0088e6', spans: [[1, 19]] },
				{ label: 'Agent turns', color: '#9467bd', spans: [[7, 25]] }
			]
		});
	}

	// Segmented: a small pool where each slot is its own block.
	const c = document.getElementById('uiref-gantt-c');

	if(c) {
		new CerbUI.Gantt(c, {
			xScale: 'linear',
			step: 1,
			segment: true,
			rows: [
				{ label: 'Bulk jobs', color: '#0088e6', spans: [[1, 4]] },
				{ label: 'Agent turns', color: '#9467bd', spans: [[2, 5]] }
			]
		});
	}

	// Continuous: queue jobs across a morning, several spans per row.
	const b = document.getElementById('uiref-gantt-b');

	if(b) {
		const at = function(h, m) {
			const d = new Date();
			d.setHours(h, m, 0, 0);
			return d.getTime();
		};

		new CerbUI.Gantt(b, {
			xScale: 'time',
			rows: [
				{ label: 'Imports', spans: [ { start: at(9, 0), end: at(9, 40), label: 'contacts.csv' } ] },
				{ label: 'Exports', spans: [ [at(9, 15), at(9, 30)], [at(10, 5), at(10, 50)] ] },
				{ label: 'Agent turns', spans: [ [at(9, 5), at(9, 12)], [at(9, 30), at(10, 20)], [at(10, 40), at(11, 0)] ] }
			],
			tickFormat: CerbUI.date.strftime('%H:%M'),
			rowHeight: 30,
			barHeight: 16
		});
	}
})();
</script>
