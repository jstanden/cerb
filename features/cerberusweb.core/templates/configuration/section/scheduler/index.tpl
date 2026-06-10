{* Setup→Scheduler — cerb-ui; per-job sparkcharts are backed by the cerb.scheduler.* metrics *}
<style nonce="{DevblocksPlatform::getRequestNonce()}">
/* Page-local layout only — every visual element is a cerb-ui-* component composed inside these */
{* Flex/alignment atoms (display:flex, items-center, justify-center, flex-shrink-0, ml-auto, text-*,
   nowrap, relative, cursor-pointer) live on cerb-u-* classes in the markup; only page-specific values
   (em gaps, fixed widths/basis, fonts, colors, the request block, media query) remain here. *}
.cerb-sched-split { gap:1.5em; margin-bottom:1.25em; }

.cerb-sched-job { gap:1.2em; }
.cerb-sched-job.cerb-sched-off { opacity:0.55; }
.cerb-sched-job--info { gap:0.7em; flex:0 0 240px; min-width:0; }
.cerb-sched-job--name { font-size:1.1em; font-weight:600; color:var(--cerb-color-widget-header); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cerb-sched-job--meta { gap:0.5em; font-size:0.82em; color:var(--cerb-color-background-contrast-150); margin-top:0.2em; }
.cerb-sched-job--chart { flex:1 1 auto; min-width:120px; }
/* Fixed-width stats column so the flex:1 sparkchart is the exact same width on every row,
   regardless of legend value lengths or whether a "run now" button is present.
   Layout: legend (left) · ring (centered in its slot) · edit/run buttons (right). */
.cerb-sched-job--stats { gap:0.5em; width:23em; }
.cerb-sched-ring-slot { width:62px; }
.cerb-sched-ring-off { font-size:0.7em; color:var(--cerb-color-background-contrast-150); }

/* Development / Production card bodies (the cards are cerb-ui-panel + cerb-ui-header components) */
.cerb-sched-autorun-status { font-size:0.82em; color:var(--cerb-color-background-contrast-150); }
.cerb-sched-req { margin:0.5em 0 0.4em; }
.cerb-sched-req pre { margin:0; padding:0.7em 2.4em 0.7em 0.9em; font-family:monospace; font-size:0.82em; line-height:1.6; white-space:pre-wrap; word-break:break-word; background:var(--cerb-color-background-contrast-245); border:1px solid var(--cerb-color-background-contrast-225); }
/*.cerb-sched-req .cerb-sched-copy { position:absolute; top:0.45em; right:0.45em; background:none; border:0; cursor:pointer; color:var(--cerb-color-background-contrast-150); padding:0.2em; }*/
/*.cerb-sched-req .cerb-sched-copy:hover { color:var(--cerb-color-link); }*/
.cerb-sched-token { color:var(--cerb-color-link); text-decoration:underline; }

/* Mobile: wrap each job row so the sparkchart drops to its own full-width line within the panel */
@media (max-width:768px) {
	.cerb-sched-job { flex-wrap:wrap; }
	.cerb-sched-job--info { flex-basis:100%; }
	.cerb-sched-job--chart { flex-basis:100%; min-width:0; }
	.cerb-sched-job--stats { width:100%; }
}
</style>

{* Active/total computed here; per-job charts + run counts load async from cerb.scheduler.* metrics *}
{$sched_total = 0}{$sched_active = 0}
{foreach from=$jobs item=_j}{if $_j instanceof CerberusCronPageExtension}{$sched_total = $sched_total + 1}{if $_j->getParam('enabled',0)}{$sched_active = $sched_active + 1}{/if}{/if}{/foreach}

<div class="cerb-ui-page cerb-ui-page--max-width">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">Scheduler</div>
			<div class="cerb-ui-header--subtitle" id="cerbSchedSubtitle">Configure recurring background tasks</div>
		</div>
		<div class="cerb-ui-header--right">
			<div class="cerb-ui-chip">
				<div><div class="cerb-ui-chip--label">Active</div><div class="cerb-ui-chip--value" id="cerbSchedActive">{$sched_active} / {$sched_total}</div></div>
				<div><div class="cerb-ui-chip--label" id="cerbSchedRunsLabel">Runs (24h)</div><div class="cerb-ui-chip--value" id="cerbSchedRuns">&mdash;</div></div>
			</div>
			<div class="cerb-ui-switcher" id="cerbSchedWindow">
				<button type="button" data-value="24h" class="cerb-ui-switcher--active">24h</button>
				<button type="button" data-value="7d">7d</button>
			</div>
		</div>
	</div>

	{* Development / Production *}
	<div class="cerb-sched-split cerb-u-flex cerb-u-flex-wrap cerb-u-items-stretch" id="cerbSchedTile">
		<div class="cerb-ui-panel cerb-u-flex-1">
			<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
				<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-window-top"></span> Development</div>
				<div class="cerb-ui-header--right">
					<span class="cerb-ui-header--label">Auto-run</span>
					<label class="cerb-ui-toggle"><input type="checkbox" id="cerbSchedAutorun"><span class="cerb-ui-toggle--slider"></span></label>
				</div>
			</div>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3 cerb-u-mt-2">
				<div id="cerbSchedAutorunRing" style="display:none;"></div>
				<div class="cerb-ui-chip">
					<div><div class="cerb-ui-chip--label">Next to fire</div><div class="cerb-ui-chip--value" id="cerbSchedUpcomingValue">&mdash;</div></div>
				</div>
				<div class="cerb-sched-autorun-status" id="cerbSchedAutorunStatus"></div>
			</div>
		</div>
		<div class="cerb-ui-panel cerb-u-flex-1">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div>
					<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-console"></span> Production</div>
					<div class="cerb-ui-header--subtitle">Trigger from an external cron with a service token</div>
				</div>
				<div class="cerb-ui-header--right">
					<button type="button" class="cerb-ui-button" data-cerb-copy="{devblocks_url full=true}c=cron{/devblocks_url}" title="Copy URL"><span class="cerb-icons cerb-icon-copy"></span></button>
				</div>
			</div>
			<div class="cerb-sched-req cerb-u-relative">
<pre class="cerb-u-rounded-2">GET {devblocks_url full=true}c=cron{/devblocks_url}

Authorization: Bearer <a class="cerb-search-trigger cerb-sched-token cerb-u-cursor-pointer" data-context="cerb.contexts.service.token" data-query="*">&lt;service-token&gt;</a></pre>
			</div>
		</div>
	</div>

	{* Jobs *}
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--label">Jobs</div>
	</div>

	{foreach from=$jobs item=job key=job_id}
	{if $job instanceof CerberusCronPageExtension}
		{include file="devblocks:cerberusweb.core::configuration/section/scheduler/_job_row.tpl"}
	{/if}
	{/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
$(function() {
	if(!window.CerbUI) return;

	// Relative-time + compact-number formatting comes from the shared cerb-ui helpers:
	// CerbUI.date.remain / CerbUI.date.ago (date.js), CerbUI.num.compact (num.js).
	const sum = function(a) { let t = 0; for(let i = 0; i < (a ? a.length : 0); i++) t += (+a[i] || 0); return t; };

	// window => chip label + subtitle phrasing + sparkchart caption start
	const windowMeta = {
		'24h': { runs: 'Runs (24h)', sub: 'past 24 hours', caption: '24h ago' },
		'7d':  { runs: 'Runs (7d)',  sub: 'past 7 days',   caption: '7d ago' },
		'30d': { runs: 'Runs (30d)', sub: 'past 30 days',  caption: '30d ago' },
	};

	// One shared color scale so every chart + legend colors runs/duration identically (bar=blue, line=orange).
	// Prime the order up front so it's stable no matter which job renders first.
	const schedScale = CerbUI.colorScale ? CerbUI.colorScale() : null;
	if(schedScale) { schedScale.color('runs'); schedScale.color('duration'); }

	const runsChip = document.getElementById('cerbSchedRuns');
	const runsLabel = document.getElementById('cerbSchedRunsLabel');
	const upcomingValueEl = document.getElementById('cerbSchedUpcomingValue');
	const activeChip = document.getElementById('cerbSchedActive');

	// "Active N / M" chip — recount from the DOM so it stays correct after an in-place row swap
	const renderActive = function() {
		if(!activeChip) return;
		const all = document.querySelectorAll('[data-sched-job]');
		let active = 0;
		all.forEach(function(el) { if(el.getAttribute('data-enabled') === '1') active++; });
		activeChip.textContent = active + ' / ' + all.length;
	};

	// Build a registry for enabled jobs; charts/stats load async, rings/countdowns tick locally
	const rows = [];
	const rowsById = {};

	// Wire one job row's controls and (for enabled jobs) register it for the chart/ring loop.
	// Reusable so a row swapped in after a save (cerbSchedAfterSave) re-initializes identically.
	const initRow = function(jobEl) {
		const enabled = jobEl.getAttribute('data-enabled') === '1';
		const jobId = jobEl.getAttribute('data-job-id') || '';
		const agoEl = jobEl.querySelector('[data-sched-ago]');
		const lastrun = parseInt(jobEl.getAttribute('data-lastrun'), 10) || 0;

		// Edit + Run now both open a peek popup (like Setup→Storage); the buttons are bare markers —
		// the job id comes from the row's data-job-id, not baked into each button.
		const editBtn = jobEl.querySelector('[data-sched-edit]');
		if(editBtn) editBtn.addEventListener('click', function() {
			genericAjaxPopup('peek', 'c=config&a=invoke&module=scheduler&action=showJobPeek&id=' + encodeURIComponent(jobId), null, false);
		});
		const runBtn = jobEl.querySelector('[data-sched-run]');
		if(runBtn) runBtn.addEventListener('click', function() {
			genericAjaxPopup('peek', 'c=config&a=invoke&module=scheduler&action=showJobRun&id=' + encodeURIComponent(jobId), null, false);
		});

		if(!enabled) {
			if(agoEl) agoEl.textContent = lastrun ? ('· ran ' + CerbUI.date.ago(Math.floor(Date.now() / 1000) - lastrun)) : '· never run';
			return;
		}

		const ringEl = jobEl.querySelector('[data-sched-ring]');
		let ring = null;
		if(ringEl && CerbUI.TimeRing) ring = new CerbUI.TimeRing(ringEl, { key: 'next' });

		const row = {
			id: jobId,
			el: jobEl,
			concurrent: jobEl.getAttribute('data-concurrent') === '1', // runs on queue slots, not a fixed interval
			chartEl: jobEl.querySelector('[data-sched-chart]'),
			statsEl: jobEl.querySelector('[data-sched-stats]'),
			ring: ring,
			name: jobEl.getAttribute('data-name') || '',
			nextfire: parseInt(jobEl.getAttribute('data-nextfire'), 10) || 0,
			interval: parseInt(jobEl.getAttribute('data-interval'), 10) || 60,
			agoEl: agoEl,
			lastrun: lastrun,
			runs: 0,
		};
		rows.push(row);
		if(jobId) rowsById[jobId] = row;
	};

	document.querySelectorAll('[data-sched-job]').forEach(initRow);

	let schedWindow = '24h';
	let schedReq = 0; // generation token; a newer load drops a slower earlier response

	// Render one row's sparkchart + stats stack from the endpoint payload (null = no data)
	const renderRow = function(row, d) {
		const meta = windowMeta[schedWindow] || windowMeta['24h'];

		if(row.chartEl && CerbUI.Sparkchart) {
			const prev = CerbUI.Sparkchart.from(row.chartEl);
			if(prev) prev.destroy(); else row.chartEl.innerHTML = '';
			if(d && d.series) {
				new CerbUI.Sparkchart(row.chartEl, {
					scale: schedScale,
					categories: d.categories,
					series: d.series, // [runs bar (behind), duration line (in front)]
					caption: [meta.caption, 'now'],
					height: 46, barWidth: 0.55,
				});
			}
		}

		// Stats: runs total (Σ bar values) + avg duration. Average only over buckets that actually had
		// runs — empty buckets are zero-filled for the chart and would otherwise drag the avg down on sparse data.
		const runsValues = (d && d.series && d.series[0]) ? d.series[0].values : [];
		const durValues = (d && d.series && d.series[1]) ? d.series[1].values : [];
		row.runs = sum(runsValues);
		let durSum = 0, durCount = 0;
		for(let i = 0; i < durValues.length; i++) {
			if((+runsValues[i] || 0) > 0) { durSum += (+durValues[i] || 0); durCount++; }
		}
		const avg = durCount ? Math.round(durSum / durCount) : 0;

		if(row.statsEl) {
			const runsItem = row.statsEl.querySelector('[data-sched-stat-runs]');
			const avgItem = row.statsEl.querySelector('[data-sched-stat-avg]');
			if(runsItem) runsItem.dataset.text = CerbUI.num.compact(row.runs);
			if(avgItem) avgItem.dataset.text = avg + 'ms';
			// Construct the vertical legend once, then just re-render on later loads (avoids duplicate DOM)
			const lg = CerbUI.Legend ? CerbUI.Legend.from(row.statsEl) : null;
			if(lg) lg.render();
			else if(CerbUI.Legend) new CerbUI.Legend(row.statsEl, { scale: schedScale, percent: false });
		}
	};

	const loadSparklines = function(win) {
		schedWindow = win || schedWindow || '24h';

		const meta = windowMeta[schedWindow] || windowMeta['24h'];
		if(runsLabel) runsLabel.textContent = meta.runs;

		if(!rows.length) { if(runsChip) runsChip.textContent = '0'; return; }

		const req = ++schedReq;

		// Spinner only where there's no chart yet; keep an existing chart visible while reloading
		if(CerbUI.Spinner) rows.forEach(function(r) {
			if(r.chartEl && !r.chartEl.childElementCount) {
				const sp = CerbUI.Spinner.create();
				sp.style.width = sp.style.height = '16px';
				r.chartEl.appendChild(sp);
			}
		});

		const data = new FormData();
		data.set('c', 'config');
		data.set('a', 'invoke');
		data.set('module', 'scheduler');
		data.set('action', 'viewSparklinesJson');
		data.set('window', schedWindow);
		rows.forEach(function(r) { if(r.id) data.append('ids[]', r.id); });

		genericAjaxPost(data, '', '', function(payload) {
			if(req !== schedReq) return; // a newer window/load superseded this response
			let grand = 0;
			rows.forEach(function(r) {
				renderRow(r, payload ? payload[r.id] : null);
				grand += r.runs;
			});
			if(runsChip) runsChip.textContent = CerbUI.num.compact(grand);
		}, { dataType: 'json' });
	};

	// "Next to fire" chip in the Development panel — names whatever governs the upcoming auto-run poll.
	// The countdown itself lives on the auto-run ring.
	const renderUpcoming = function(now) {
		if(!upcomingValueEl) return;
		let next = null;
		const concurrentNames = [];
		rows.forEach(function(r) {
			if(r.concurrent) { concurrentNames.push(r.name); return; }
			if(!(r.nextfire > 0)) return;
			const remaining = r.nextfire - now;
			if(next === null || remaining < next.remaining) next = { name: r.name, remaining: remaining };
		});
		// While auto-run is on the cadence is min(next interval, 15s background heartbeat) — name whichever
		// governs the next poll so the chip matches what actually fires. (Mirrors autorun.nextDelay.)
		let label = next ? next.name : '—';
		if(autorun.on && concurrentNames.length && (next === null || next.remaining * 1000 > autorun.HEARTBEAT_MS))
			label = (concurrentNames.length === 1) ? concurrentNames[0] : 'Background queue';
		upcomingValueEl.textContent = label;
	};

	const tick = function() {
		const now = Math.floor(Date.now() / 1000);
		let soonest = null;
		rows.forEach(function(r) {
			const remaining = r.nextfire - now;
			if(r.ring) {
				r.ring.setFraction(remaining <= 0 ? 1 : (r.interval - remaining) / r.interval);
				r.ring.setValue(CerbUI.date.remain(remaining));
			}
			if(r.agoEl) r.agoEl.textContent = r.lastrun ? ('· ran ' + CerbUI.date.ago(now - r.lastrun)) : '· never run';
			// Concurrent jobs have no fixed cadence — exclude them from the "next to fire" countdown
			if(!r.concurrent && remaining > 0 && (soonest === null || remaining < soonest.remaining)) soonest = { remaining: remaining, name: r.name };
		});
		renderUpcoming(now);
		// Auto-run: drive the ring and fire the next poll when the synced cycle elapses
		if(autorun.on) {
			// Self-heal a poll whose response never came (genericAjaxPost calls cb on success only)
			if(autorun.busy && Date.now() - autorun.pollStart > autorun.STALL_MS) { autorun.busy = false; autorun.scheduleNext(); }
			if(autorun.ring && autorun.cycleMs > 0) {
				const elapsed = Date.now() - autorun.cycleStart;
				autorun.ring.setFraction(Math.min(1, elapsed / autorun.cycleMs));
				autorun.ring.setValue(CerbUI.date.remain(Math.max(0, Math.round((autorun.cycleMs - elapsed) / 1000))));
			}
			if(!autorun.busy && Date.now() >= autorun.nextPollAt) autorun.poll();
		}
	};

	// After a manual "Run now": adopt the new lastrun, reset the countdown, and re-pull the chart/stats
	window.cerbSchedAfterRun = function(jobId, lastrun) {
		const row = rowsById[jobId];
		if(row) {
			const last = parseInt(lastrun, 10) || 0;
			if(last) {
				row.lastrun = last;
				row.nextfire = last + row.interval;
				if(row.el) row.el.setAttribute('data-lastrun', last);
			}
			tick();
		}
		if(autorun.on) autorun.scheduleNext(); // a manual run shifted nextfire — re-target the next poll
		loadSparklines(schedWindow);
	};

	// After a save in the job-edit popup: swap just this row's HTML in place — state, interval, ring slot,
	// and the run-now button can all change — instead of reloading the whole page; then re-init + repaint.
	window.cerbSchedAfterSave = function(jobId, html) {
		const oldRow = rowsById[jobId];
		const oldEl = (oldRow && oldRow.el) || Array.prototype.find.call(
			document.querySelectorAll('[data-sched-job]'),
			function(el) { return el.getAttribute('data-job-id') === jobId; }
		);
		if(!oldEl || !html) { document.location.reload(); return; } // fall back to a full reload if we can't target the row

		// Drop the stale registry entry; the old ring/chart live on the detached node and get GC'd
		if(oldRow) {
			const i = rows.indexOf(oldRow);
			if(i >= 0) rows.splice(i, 1);
			delete rowsById[jobId];
		}

		const tmp = document.createElement('div');
		tmp.innerHTML = ('' + html).trim();
		const newEl = tmp.firstElementChild;
		if(!newEl) { document.location.reload(); return; }
		oldEl.replaceWith(newEl);

		initRow(newEl);
		renderActive();                      // enabled state may have flipped — recount the "Active N / M" chip
		loadSparklines(schedWindow);         // repaint this row's sparkchart + stats
		if(autorun.on) autorun.scheduleNext(); // interval may have changed — re-target the next auto-run poll
		tick();                              // refresh rings, "ran X ago", and the "next to fire" chip
	};

	// --- Development tile: in-browser auto-runner (polls runReadyJobs while toggled on) ---
	// Cadence syncs to the soonest "next to fire" rather than a fixed interval; tick() drives the countdown.
	const pad2 = function(n) { return (n < 10 ? '0' : '') + n; };
	const nowClock = function() { const d = new Date(); return pad2(d.getHours()) + ':' + pad2(d.getMinutes()) + ':' + pad2(d.getSeconds()); };

	const autorun = {
		HEARTBEAT_MS: 15000, // max wait while concurrent (queue-slot) jobs are enabled
		MIN_MS: 3000,        // floor so a perpetually-ready job can't hammer the server
		STALL_MS: 30000,     // watchdog: force-clear a poll whose response never arrived

		on: false, busy: false, pollStart: 0,
		cycleStart: 0, cycleMs: 15000, nextPollAt: 0,

		statusEl: document.getElementById('cerbSchedAutorunStatus'),
		// Ring that visualizes the countdown to the next poll — driven from tick() while auto-run is on
		ringEl: document.getElementById('cerbSchedAutorunRing'),
		ring: null,

		// ms until the next poll: sync to the soonest interval job's nextfire; never wait past the heartbeat
		// while concurrent (queue-slot) jobs are enabled, since their readiness is unpredictable. Sooner wins.
		nextDelay() {
			const now = Math.floor(Date.now() / 1000);
			let soonest = null, hasConcurrent = false;
			rows.forEach(function(r) {
				if(r.concurrent) { hasConcurrent = true; return; }
				if(r.nextfire > 0) { const rem = r.nextfire - now; if(soonest === null || rem < soonest) soonest = rem; }
			});
			let ms = (soonest === null) ? this.HEARTBEAT_MS : Math.max(0, soonest) * 1000;
			if(hasConcurrent) ms = Math.min(ms, this.HEARTBEAT_MS);
			return Math.max(this.MIN_MS, ms);
		},
		scheduleNext() {
			this.cycleMs = this.nextDelay();
			this.cycleStart = Date.now();
			this.nextPollAt = this.cycleStart + this.cycleMs;
		},
		poll() {
			if(this.busy) return; // never overlap a slow run
			this.busy = true;
			this.pollStart = Date.now();
			const self = this;
			const data = new FormData();
			data.set('c', 'config'); data.set('a', 'invoke'); data.set('module', 'scheduler'); data.set('action', 'runReadyJobs');
			genericAjaxPost(data, '', '', function(json) {
				self.busy = false;
				if(json && json.status) {
					const ran = json.ran || [];
					ran.forEach(function(r) {
						const row = rowsById[r.id];
						if(row) {
							const last = parseInt(r.lastrun, 10) || 0; // coerce: JSON may carry it as a string
							row.lastrun = last;
							row.nextfire = last + row.interval;
							if(row.el) row.el.setAttribute('data-lastrun', last);
						}
					});
					if(ran.length) loadSparklines(schedWindow); // only re-pull charts when something actually ran
					if(self.statusEl) self.statusEl.textContent = (ran.length ? ('Ran ' + ran.length + ' job' + (ran.length === 1 ? '' : 's')) : 'Nothing ready') + ' · ' + nowClock();
				}
				// Re-sync BEFORE tick() so tick doesn't see a stale (passed) nextPollAt and immediately re-poll
				self.scheduleNext();
				tick();
			}, { dataType: 'json' });
		},
		start() {
			if(this.on) return;
			this.on = true;
			if(this.statusEl) this.statusEl.textContent = 'Watching for ready jobs…';
			if(this.ringEl) this.ringEl.style.display = '';
			this.scheduleNext(); // give the ring a denominator immediately
			this.poll();         // poll right away on enable
		},
		stop() {
			this.on = false;
			this.busy = false;
			if(this.statusEl) this.statusEl.textContent = '';
			if(this.ringEl) this.ringEl.style.display = 'none';
			if(this.ring) this.ring.setFraction(0);
		},
	};
	if(autorun.ringEl && CerbUI.TimeRing) autorun.ring = new CerbUI.TimeRing(autorun.ringEl, { key: 'scan' });

	const autorunToggleEl = document.getElementById('cerbSchedAutorun');
	if(autorunToggleEl && CerbUI.Toggle)
		new CerbUI.Toggle(autorunToggleEl, { onChange: function(checked) { if(checked) autorun.start(); else autorun.stop(); } });

	// --- Advanced tile: service-token search link + copy-URL button ---
	$('#cerbSchedTile .cerb-search-trigger').cerbSearchTrigger();
	document.querySelectorAll('#cerbSchedTile [data-cerb-copy]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			navigator.clipboard.writeText(btn.getAttribute('data-cerb-copy'));
			Devblocks.createAlert('Copied to clipboard!');
		});
	});

	// Window switcher (24h / 7d), persisted per worker; re-fetches on change
	let initialWindow = '24h';
	if(CerbUI.Switcher) {
		const sw = new CerbUI.Switcher(document.getElementById('cerbSchedWindow'), {
			storageKey: 'cerb.sched.window',
			onSelect: function(value) { loadSparklines(value); },
		});
		initialWindow = sw.getValue() || '24h';
	}

	loadSparklines(initialWindow);
	tick();
	setInterval(tick, 1000);
});
{/literal}
</script>