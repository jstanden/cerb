	<div class="cerb-uiref-component" id="async">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-spinner"></span>Async</div>

		{* CerbUI.utils async flow-control — the small surface we kept from the retired caolan/async library *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><code>CerbUI.utils</code> &mdash; tiny async flow-control helpers (the replacement for the retired <code>caolan/async</code> library). A <b>task</b> is a node-style function <code>task(callback)</code> that eventually calls <code>callback(err, result)</code>; iteratees may call <code>callback()</code> with no args (treated as success). Both runners aggregate results in original order and stop on the first error.</div>
		</div>

		{* parallelLimit *}
		<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm"><code>parallelLimit(tasks, limit, done)</code> &mdash; run tasks with at most <code>limit</code> in flight (this is the dashboard widget-load throttle)</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mb-3 cerb-u-flex-wrap">
					<label class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">Limit
						<select class="uiref-async-limit"><option value="1">1</option><option value="2" selected>2</option><option value="3">3</option></select>
					</label>
					<button type="button" class="cerb-ui-button uiref-async-run"><span class="cerb-icons cerb-icon-play"></span>Run</button>
					<span class="cerb-uiref-utils--note uiref-async-readout">idle</span>
				</div>
				<div class="uiref-async-chips cerb-u-flex cerb-u-gap-2 cerb-u-flex-wrap" id="uiref-async-parallel"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Build tasks with apply(fn, ...args) => a task `cb => fn(...args, cb)`
const tasks = ids.map(id =&gt; CerbUI.utils.apply(loadWidget, id));

// Run at most 2 at a time; done fires once all finish (or on first error)
CerbUI.utils.parallelLimit(tasks, 2, function(err, results) {
	if(err) return console.error(err);
	// results[] is in original task order
});{/literal}</pre>
			</div>
		</div>

		{* series *}
		<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm"><code>series(tasks, done)</code> &mdash; run tasks strictly one at a time</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mb-3 cerb-u-flex-wrap">
					<button type="button" class="cerb-ui-button uiref-async-run"><span class="cerb-icons cerb-icon-play"></span>Run</button>
					<span class="cerb-uiref-utils--note uiref-async-readout">idle</span>
				</div>
				<div class="uiref-async-chips cerb-u-flex cerb-u-gap-2 cerb-u-flex-wrap" id="uiref-async-series"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.utils.series([
	cb =&gt; step1(cb),
	cb =&gt; step2(cb),
], function(err, results) {
	// each step ran only after the previous one called its callback
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
(function() {
	if(!(window.CerbUI && CerbUI.utils && CerbUI.utils.parallelLimit)) return;

	const DELAYS = [600, 1000, 450, 1200, 750, 550];

	// Paint a chip for a given state (queued / running / done).
	const paint = function(chip, state) {
		chip.setAttribute('data-state', state);
		if(state === 'running') {
			chip.style.background = '#e0a800'; chip.style.color = '#fff';
			chip.textContent = chip.dataset.label + ' …';
		} else if(state === 'done') {
			chip.style.background = '#3fa34d'; chip.style.color = '#fff';
			chip.textContent = chip.dataset.label + ' ✓';
		} else {
			chip.style.background = 'var(--cerb-color-background-contrast-230)';
			chip.style.color = 'var(--cerb-color-text)';
			chip.textContent = chip.dataset.label;
		}
	};

	const initDemo = function(root, runner) {
		const chipsEl = root.querySelector('.uiref-async-chips');
		const runBtn = root.querySelector('.uiref-async-run');
		const readout = root.querySelector('.uiref-async-readout');
		const limitSel = root.querySelector('.uiref-async-limit');

		// Build the chips once.
		const chips = DELAYS.map(function(delay, i) {
			const chip = document.createElement('span');
			chip.dataset.label = 'T' + (i + 1);
			chip.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;min-width:3.4em;'
				+ 'padding:0.4em 0.7em;border-radius:0.4em;font-weight:600;transition:background 0.15s;';
			paint(chip, 'queued');
			chipsEl.appendChild(chip);
			return chip;
		});

		runBtn.addEventListener('click', function() {
			const limit = limitSel ? parseInt(limitSel.value, 10) : 1;
			let active = 0, peak = 0, doneCount = 0;
			const started = performance.now();

			chips.forEach(function(c) { paint(c, 'queued'); });
			runBtn.disabled = true;
			if(limitSel) limitSel.disabled = true;

			const render = function() {
				readout.textContent = 'running ' + active + (limitSel ? (' / ' + limit) : '')
					+ ' · peak ' + peak + ' · done ' + doneCount + '/' + chips.length;
			};
			render();

			// One task per chip: go 'running', wait its delay, go 'done', then call back.
			const tasks = chips.map(function(chip, i) {
				return CerbUI.utils.apply(function(c, delay, callback) {
					active++; if(active > peak) peak = active;
					paint(c, 'running'); render();
					window.setTimeout(function() {
						active--; doneCount++;
						paint(c, 'done'); render();
						callback();
					}, delay);
				}, chip, DELAYS[i]);
			});

			const finish = function() {
				const ms = Math.round(performance.now() - started);
				readout.textContent = 'done — ' + chips.length + ' tasks in ' + ms + 'ms'
					+ (limitSel ? (' (peak concurrency ' + peak + ' / ' + limit + ')') : ' (sequential)');
				runBtn.disabled = false;
				if(limitSel) limitSel.disabled = false;
			};

			if(runner === 'series')
				CerbUI.utils.series(tasks, finish);
			else
				CerbUI.utils.parallelLimit(tasks, limit, finish);
		});
	};

	const parallelRoot = document.getElementById('uiref-async-parallel');
	const seriesRoot = document.getElementById('uiref-async-series');
	if(parallelRoot) initDemo(parallelRoot.closest('.cerb-uiref-demo'), 'parallel');
	if(seriesRoot) initDemo(seriesRoot.closest('.cerb-uiref-demo'), 'series');
})();
{/literal}
</script>
