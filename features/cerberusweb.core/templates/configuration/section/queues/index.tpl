<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{'common.queues'|devblocks_translate|capitalize}</div>
		<div class="cerb-ui-header--subtitle">Configure background parallel job processing</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div>
			<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-chart-gantt"></span> Slot lanes</div>
			<div class="cerb-ui-header--subtitle">Which slots each kind of background work may take.</div>
		</div>
		{if $max_concurrency_slots > 0}
		<div class="cerb-ui-header--summary"><b>{$agent_slots}</b> of <b>{$max_concurrency_slots}</b> slot{if $max_concurrency_slots != 1}s{/if} can run agent turns ({$turns_per_slot} sessions each)</div>
		{/if}
	</div>

	{if $max_concurrency_slots > 0}
		{include file="devblocks:cerberusweb.core::internal/queues/slot_lanes.tpl" id="setupQueuesLanes"}
	{else}
		<div class="cerb-u-mt-3 cerb-u-text-muted">This host has no concurrency slots, so no background work runs here.</div>
	{/if}

	{if $max_concurrency_slots != $max_concurrency_slots_licensed}
	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<span class="cerb-icons cerb-icon-circle-info cerb-u-mr-1"></span> This subscription allows {$max_concurrency_slots_licensed} slots. <code>APP_QUEUE_CONCURRENCY_SLOTS</code> in <code>framework.config.php</code> caps them at {$max_concurrency_slots} on this host.
	</div>
	{/if}

	<div class="cerb-u-mt-3 cerb-u-text-muted">
		Scheduled jobs draw from the same lane as agent turns, so the two compete for it, and a provider's own rate limits apply on top of whatever this pool allows.
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div>
			<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-gauge"></span> In use</div>
			<div class="cerb-ui-header--subtitle">The slots held right now, across every lane.</div>
		</div>
		{if $max_concurrency_slots > 0}
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<div class="cerb-ui-header--summary"><b id="setupQueuesUsed">-</b> of <b>{$max_concurrency_slots}</b> slot{if $max_concurrency_slots != 1}s{/if} in use</div>
			<div id="setupQueuesRefreshRing"></div>
			<label class="cerb-ui-toggle"><input type="checkbox" id="setupQueuesAutoRefresh" checked><span class="cerb-ui-toggle--slider"></span></label>
			<ul id="setupQueuesRefreshMenu" hidden>
				<li data-ms="5000">5 sec</li>
				<li data-ms="15000">15 sec</li>
				<li data-ms="60000">1 min</li>
			</ul>
		</div>
		{/if}
	</div>

	{if $max_concurrency_slots > 0}
	<div class="cerb-ui-gantt" id="setupQueuesUsage"
		data-usage="{$slot_usage_json}"
		data-total="{$max_concurrency_slots}"></div>

	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<span id="setupQueuesUsageNote"></span>
	</div>
	{/if}
</div>

{if $max_concurrency_slots > 0}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const el = document.getElementById('setupQueuesUsage');

	if(!el || !(window.CerbUI && CerbUI.Gantt))
		return;

	const usedEl = document.getElementById('setupQueuesUsed');
	const noteEl = document.getElementById('setupQueuesUsageNote');
	const total = parseInt(el.dataset.total, 10) || 0;

	const NOTE_PAUSED = 'Auto-refresh is off.';
	const NOTE_UNKNOWN = 'Slot usage could not be read just now -- the blocks above are the last known state.';

	// An explicit domain, because it must NOT follow the spans: derived, an idle pool would have no
	// spans to derive from and a pool with one busy slot would redraw itself as a pool of one.
	const chart = new CerbUI.Gantt(el, {
		xScale: 'linear',
		step: 1,
		segment: true,
		axis: false,
		domain: [1, total + 1],
		rowHeight: 26,
		barHeight: 14,
		labelWidth: 100,
		// Which slot a drain got is not meaningful -- the acquirer shuffles and takes what is free --
		// so there is nothing to say about an individual block.
		tooltip: false,
		rows: []
	});

	// Occupied or free, one color, deliberately: the lock records that a slot is HELD and cannot say
	// which lane took it, so coloring these by lane would be a guess dressed as a reading.
	const draw = function(busy) {
		chart.setRows([
			{ label: 'In use', color: '#2ea043', spans: (busy || []).map(function(slot) { return [slot, slot]; }) }
		]);
	};

	const apply = function(payload) {
		if(!payload)
			return;

		// A failed read is not an idle pool. Keep the last blocks and say so, rather than drawing an
		// empty pool that looks like good news.
		if(payload.unknown) {
			if(noteEl) noteEl.textContent = NOTE_UNKNOWN;
			return;
		}

		if(noteEl) noteEl.textContent = '';
		if(usedEl) usedEl.textContent = payload.used;

		draw(payload.busy);
	};

	apply(JSON.parse(el.dataset.usage));

	// A polled reading with a countdown the admin controls, which is what a socket feed will replace --
	// at which point the ring stops being a countdown and the interval menu goes away.
	const refresh = {
		INTERVAL_MS: 5000,
		on: true,
		cycleStart: Date.now(),
		pausedAt: 0,
		busy: false,
		startedAt: 0,
		STALL_MS: 20000,
		ringEl: document.getElementById('setupQueuesRefreshRing'),
		ring: null,
		intervalLabel: function(ms) { return (ms >= 60000) ? (Math.round(ms / 60000) + 'm') : (Math.round(ms / 1000) + 's'); },
		applyInterval: function(ms) {
			this.INTERVAL_MS = ms;
			this.cycleStart = Date.now();
			if(this.ring) this.ring.setKey(this.intervalLabel(ms));
			this.paint(0);
		},
		start: function() {
			if(this.on) return;
			this.on = true;
			if(this.ringEl) this.ringEl.style.display = '';
			if(noteEl && noteEl.textContent === NOTE_PAUSED) noteEl.textContent = '';
			this.cycleStart = Date.now();
			this.pausedAt = 0;
			this.paint(0);
		},
		stop: function() {
			this.on = false;
			if(this.ringEl) this.ringEl.style.display = 'none';
			if(this.ring) this.ring.setFraction(0);
			if(noteEl) noteEl.textContent = NOTE_PAUSED;
		},
		// Elapsed drives both halves of the ring, so the arc and the number can't disagree. Ceil, so a
		// full second is still on the clock until it is actually spent: `remain()` reads 0 as "now".
		paint: function(elapsed) {
			if(!this.ring) return;

			this.ring.setFraction(Math.min(1, elapsed / this.INTERVAL_MS));
			this.ring.setValue(CerbUI.date.remain(Math.ceil(Math.max(0, this.INTERVAL_MS - elapsed) / 1000)));
		},
		poll: function() {
			const data = new FormData();
			data.set('c', 'config');
			data.set('a', 'invoke');
			data.set('module', 'queues');
			data.set('action', 'usageJson');

			this.busy = true;
			this.startedAt = Date.now();

			const self = this;

			genericAjaxPost(data, '', '', function(payload) {
				self.busy = false;
				apply(payload);

				// The next cycle starts when the answer LANDS, not when the request went out: a countdown
				// that restarts on send is counting down to something it has already asked for.
				self.cycleStart = Date.now();
				self.paint(0);
			}, { dataType: 'json' });
		}
	};

	if(refresh.ringEl && CerbUI.TimeRing) {
		refresh.ring = new CerbUI.TimeRing(refresh.ringEl, { size: 34, key: refresh.intervalLabel(refresh.INTERVAL_MS) });
		refresh.paint(0);
	}

	// The ring doubles as the interval picker, as it does on a workspace worklist tab.
	const menuUl = document.getElementById('setupQueuesRefreshMenu');

	if(refresh.ringEl && menuUl && CerbUI.Menu) {
		refresh.ringEl.style.cursor = 'pointer';
		refresh.ringEl.setAttribute('title', 'Change refresh interval');

		new CerbUI.Menu(menuUl, {
			clickTrigger: refresh.ringEl,
			onSelect: function(li, src) {
				const ms = parseInt($(src).attr('data-ms'), 10);
				if(ms > 0) refresh.applyInterval(ms);
			}
		});
	}

	const toggleEl = document.getElementById('setupQueuesAutoRefresh');

	if(toggleEl && CerbUI.Toggle)
		new CerbUI.Toggle(toggleEl, { onChange: function(checked) { checked ? refresh.start() : refresh.stop(); } });

	// One tick drives the ring and fires the poll, rather than a timer per concern: the countdown has to
	// agree with what actually happens, including while it is held. Four times a second rather than the
	// once a workspace tab uses -- a five second ring redrawn every second moves in visible 20% jumps.
	window.setInterval(function() {
		if(!refresh.on)
			return;

		// Hold the countdown while the tab is hidden -- draining it would fire a burst of catch-up polls
		// the moment somebody comes back to a page they were not looking at.
		if(document.hidden) {
			if(!refresh.pausedAt) refresh.pausedAt = Date.now();
			return;
		}

		if(refresh.pausedAt) {
			refresh.cycleStart += Date.now() - refresh.pausedAt;
			refresh.pausedAt = 0;
		}

		// genericAjaxPost only calls back on success, so a request that never answers would otherwise
		// hold the poll shut forever.
		if(refresh.busy && Date.now() - refresh.startedAt > refresh.STALL_MS) {
			refresh.busy = false;
			refresh.cycleStart = Date.now();
		}

		// A request in flight holds the ring full at "now" -- the countdown is spent and what it was
		// counting down TO is what is happening.
		if(refresh.busy) {
			refresh.paint(refresh.INTERVAL_MS);
			return;
		}

		const elapsed = Date.now() - refresh.cycleStart;

		if(elapsed >= refresh.INTERVAL_MS) {
			refresh.paint(refresh.INTERVAL_MS);
			refresh.poll();
			return;
		}

		refresh.paint(elapsed);
	}, 250);
});
</script>
{/if}
