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


	<div class="cerb-u-mt-3 cerb-u-text-muted">
		Scheduled jobs draw from the same lane as agent turns, so the two compete for it, and a provider's own rate limits apply on top of whatever this pool allows.
	</div>

	<div class="cerb-ui-panel cerb-u-mt-2{if !$is_licensed} cerb-ui-panel--note{/if}">
	{if !$is_licensed}
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-callout cerb-u-mb-3">
			<span class="cerb-icons cerb-icon-lock cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Locked without a subscription</div>
				<div class="cerb-ui-header--subtitle">Community installs run {$max_concurrency_slots} slots divided evenly. A subscription raises the pool and lets you decide how it divides. <a href="https://cerb.ai/pricing" target="_blank" rel="noopener">See plans</a>.</div>
			</div>
		</div>
	</div>
	{/if}

	<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupQueues" class="cerb-ui-form{if $is_licensed} cerb-u-mt-3{/if}">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="queues">
	<input type="hidden" name="action" value="saveJson">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-u-flex cerb-u-gap-3 cerb-u-items-end cerb-u-flex-wrap">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Slots</label>
			{if $is_slots_provisioned}
			{* A value, not a disabled control. A greyed-out spinner still reads as something you could
			   change if you found the right permission; Cloud's pool is not editable here at any tier. *}
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
				<span class="cerb-icons cerb-icon-cloud cerb-u-text-muted"></span>
				<span class="cerb-ui-header--title-sm">{$max_concurrency_slots}</span>
			</div>
			<div class="cerb-ui-form--help">Set by your plan</div>
			{else}
			<label class="cerb-ui-form--control" style="width:7em;">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-gauge"></span>
				<input type="number" name="slots" min="3" step="1" value="{$max_concurrency_slots}"{if !$is_licensed} disabled{/if}>
			</label>
			{/if}
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Batch only</label>
			<label class="cerb-ui-form--control" style="width:6em;">
				<input type="number" name="lane_fast" min="1" step="1" value="{$lane_reserves.fast}"{if !$is_licensed} disabled{/if}>
			</label>
		</div>

		{* Derived, so it is read-only rather than a field that snaps back: with the three totalling the pool,
		   shared is whatever the two dedicated lanes leave, and typing into it would have to take slots from
		   one of them by a rule nobody chose. Still POSTed, and the server re-validates the sum regardless. *}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Shared</label>
			<label class="cerb-ui-form--control" style="width:6em;">
				<input type="number" name="lane_shared" value="{$lane_reserves.shared}" readonly tabindex="-1">
			</label>
			<div class="cerb-ui-form--help">What's left</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Agent turns only</label>
			<label class="cerb-ui-form--control" style="width:6em;">
				<input type="number" name="lane_slow" min="1" step="1" value="{$lane_reserves.slow}"{if !$is_licensed} disabled{/if}>
			</label>
		</div>

		{if $is_licensed}
		<div class="cerb-u-flex cerb-u-gap-1">
			<button type="button" id="btnQueuesAuto" class="cerb-ui-button" title="Reset to a quarter for each lane, the rest shared"><span class="cerb-icons cerb-icon-magic"></span> Auto</button>
			<button type="button" id="btnQueuesSave" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		</div>
		{/if}
	</div>

	{if $is_licensed}<div class="cerb-u-mt-2 cerb-u-text-muted" id="setupQueuesLaneHint"></div>{/if}

	<div class="cerb-u-mt-2 cerb-u-text-muted">
		{if $is_slots_provisioned}Your plan provisions {$max_concurrency_slots} slot{if $max_concurrency_slots != 1}s{/if}. {/if}Batch work is imports, exports, bulk updates and reindexing; it releases a slot every batch. Agent turns hold one for as long as the model takes to answer. Shared slots serve either.
	</div>
	</form>
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

{if $is_licensed}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
const cerbQueuesProvisioned = {if $is_slots_provisioned}true{else}false{/if};
const cerbQueuesProvisionedSlots = {$max_concurrency_slots};
{literal}
$(function() {
	const $frm = $('#frmSetupQueues');

	if(!$frm.length) return;

	Devblocks.formDisableSubmit($frm);

	const el = {
		slots: $frm.find('input[name=slots]')[0],
		fast: $frm.find('input[name=lane_fast]')[0],
		shared: $frm.find('input[name=lane_shared]')[0],
		slow: $frm.find('input[name=lane_slow]')[0],
		hint: document.getElementById('setupQueuesLaneHint'),
		save: document.getElementById('btnQueuesSave'),
	};

	const num = (input) => Math.max(0, parseInt(input.value, 10) || 0);

	// The pool being EDITED, not the one in effect -- raising slots and re-splitting has to validate as one
	// change, or it could never be saved in a single submit.
	const poolSize = () => cerbQueuesProvisioned ? cerbQueuesProvisionedSlots : num(el.slots);

	const refreshHint = function() {
		const n = poolSize();
		const fast = num(el.fast), shared = num(el.shared), slow = num(el.slow);
		const total = fast + shared + slow;
		const starved = (fast < 1 || shared < 1 || slow < 1);
		let msg;

		if(n < 3) {
			msg = 'A pool needs at least 3 slots.';
		} else if(starved) {
			msg = 'Every lane needs at least one slot.';
		} else if(total !== n) {
			msg = 'The lanes total ' + total + ', but the pool is ' + n + '.';
		} else {
			msg = fast + ' for batch, ' + shared + ' shared, ' + slow + ' for agent turns.';
		}

		const ok = (n >= 3 && !starved && total === n);

		el.hint.textContent = msg;
		el.save.disabled = !ok;
	};

	// SHARED absorbs every change, because a shared slot is the one that serves either kind of work -- so it
	// is what you have spare, not a third thing to budget. It is derived, never typed.
	//
	// The two dedicated lanes are CLAMPED rather than allowed to overrun: the three always total the pool,
	// so an unreachable split is never on screen and the only rejection left is one the browser cannot know
	// about. Recomputed from scratch rather than nudged by a delta, so easing a lane back off restores a
	// valid split without the user repairing shared by hand.
	const rebalance = function(edited) {
		const n = poolSize();

		let fast = Math.max(1, num(el.fast));
		let slow = Math.max(1, num(el.slow));

		// Whichever lane was just edited yields to the other, and both leave one slot shared. Shrinking the
		// POOL has to shrink both, or reserves set at a larger pool would survive one they no longer fit.
		if('slow' === edited) {
			slow = Math.min(slow, n - fast - 1);
		} else if('fast' === edited) {
			fast = Math.min(fast, n - slow - 1);
		} else {
			fast = Math.min(fast, Math.max(1, n - 2));
			slow = Math.min(slow, Math.max(1, n - fast - 1));
		}

		fast = Math.max(1, fast);
		slow = Math.max(1, slow);

		// Write back only on a real change, so clamping doesn't fight the caret mid-keystroke.
		if(num(el.fast) !== fast) el.fast.value = fast;
		if(num(el.slow) !== slow) el.slow.value = slow;

		el.shared.value = Math.max(1, n - fast - slow);

		// The spinners stop where the pool does, so the arrows can't walk past it either.
		el.fast.max = Math.max(1, n - slow - 1);
		el.slow.max = Math.max(1, n - fast - 1);

		refreshHint();
	};

	if(el.slots && !cerbQueuesProvisioned)
		el.slots.addEventListener('input', () => rebalance('slots'));

	el.fast.addEventListener('input', () => rebalance('fast'));
	el.slow.addEventListener('input', () => rebalance('slow'));

	document.getElementById('btnQueuesAuto').addEventListener('click', function() {
		const n = poolSize();
		const width = (n < 3) ? 1 : Math.max(1, Math.floor(n / 4));

		el.fast.value = width;
		el.slow.value = width;
		el.shared.value = Math.max(1, n - (2 * width));

		refreshHint();
	});

	$(el.save).on('click', function(e) {
		e.stopPropagation();
		Devblocks.clearAlerts();
		Devblocks.saveAjaxForm($frm, {
			// The lane diagram and the in-use row both render from the saved split, so a reload is the
			// honest way to show what actually took effect.
			success: function() { document.location.reload(); }
		});
	});

	refreshHint();
});
{/literal}
</script>
{/if}
