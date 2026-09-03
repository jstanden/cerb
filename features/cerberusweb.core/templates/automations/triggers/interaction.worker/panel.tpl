<form action="{devblocks_url}{/devblocks_url}" method="POST" class="cerb-form-builder">
	<input type="hidden" name="continuation_token" value="{$continuation_token}">
	<div class="cerb-form-data"></div>
</form>

{$script_uid = uniqid('script_')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
	var $script = $('#{$script_uid}');
	var $spinner = $(CerbUI.Spinner.create('dots'));
	
	var $form = $script.siblings('form.cerb-form-builder');
	var $data = $form.find('.cerb-form-data');

	// `await:queue:` background poll (async llm.agent turns). A queue-await response is an INVISIBLE marker, not
	// a view — so we keep the current transcript + the dots spinner in place and drive the two-loop in the
	// background (worker sidecars drain the shared LLM queue; a gated re-POST resumes the instant the turn lands),
	// instead of clobbering $data with a "waiting" screen. Rearmed on each still-pending render; torn down when
	// any real content replaces $data.
	var _queuePoll = null;

	// The EDGE throttle, which the server cannot see. `data-slots-*` on the marker reports the
	// queue_slot_N pool; nginx's 529 means the background FPM pool had no CHILD free -- a
	// different resource entirely, and one the app has no visibility into. Without this the
	// reader gets a spinner and no explanation for a wait that is working exactly as designed.
	// Client-side because only the client ever learns of it, and it must survive the marker
	// re-render below.
	var _edgeThrottled = false;

	// A one-line status beside the spinner, used only when the server has something to say that the spinner
	// can't: a turn requeued after a rate limit is a silent 10-second gap otherwise, indistinguishable from a
	// slow model. Detached whenever there's nothing to say, so an ordinary turn looks exactly as it did.
	var $queueNotice = $('<div class="cerb-u-text-muted" style="padding:0.5em;"/>');

	function setQueueNotice(text) {
		if(!text) {
			$queueNotice.detach();
			return;
		}

		// .text() — this is server prose, not markup, and it carries a provider's own error text.
		$queueNotice.text(text).insertAfter($data);
	}

	// The other silent wait: every concurrency slot is busy, so this turn is queued behind other work
	// and the spinner alone reads as a slow model. Server-computed each gate cycle and zeroed the
	// moment a slot frees, so this needs no state of its own.
	//
	// The chip's CONTENT comes from the shared partial (the queue-job monitor includes the same one);
	// placement stays here, because only this host knows it belongs beside the spinner. inline-flex +
	// vertical-align pairs it with the spinner's inline-block on one line.
	const $queueSlots = $('<div style="vertical-align:middle;margin-left:0.5em;"/>');

	{include file="devblocks:cerberusweb.core::internal/queue/_slot_chip.tpl"}

	function setQueueSlots(used, total, waiting, throttled) {
		if(!renderSlotChip($queueSlots[0], used, total, waiting, throttled)) {
			$queueSlots.detach();
			return;
		}

		// Beside the spinner, not above it. The spinner is normally already in the DOM on this path
		// (the submit inserts it before the gate poll ever renders a marker); fall back to $data if a
		// future path ever calls this with the spinner detached.
		if($spinner.parent().length)
			$queueSlots.insertAfter($spinner);
		else
			$queueSlots.insertAfter($data);
	}

	function stopQueuePoll() {
		_edgeThrottled = false;
		setQueueNotice('');
		setQueueSlots(0, 0, 0, false);

		// Deactivate before dropping the reference: a worker sidecar still in flight from this cycle would
		// otherwise call gatePoll() and fire a stray submit after the interaction has moved on.
		if(_queuePoll) {
			_queuePoll.active = false;

			if(_queuePoll.timer)
				clearTimeout(_queuePoll.timer);
		}

		_queuePoll = null;
	}

	// Throttle for respawning a sidecar that found claimable work but lost the claim. Named and bounded
	// for the same reason job_monitor.tpl bounds its own: a no-op response returns in milliseconds, so an
	// immediate respawn is a hot loop against the queue.
	var _WORKER_RETRY_MS = 1500;
	var _WORKER_MAX_RETRIES = 3;

	function runQueuePoll(cfg) {
		stopQueuePoll();

		var poll = { active: true, timer: null, inflight: 0, spawns: 0, retries: 0 };
		_queuePoll = poll;

		// The gated poll: a normal interaction re-POST. The server re-runs the script and the automation engine's
		// await gate decides advance-vs-reassert, so this needs no queue knowledge — it just re-submits.
		function gatePoll() {
			if(!poll.active)
				return;
			poll.active = false;
			if(poll.timer)
				clearTimeout(poll.timer);
			$form.triggerHandler('cerb-form-builder-submit');
		}

		// A worker sidecar: advance the shared LLM queue by ONE turn, then (if it did) re-check the gate NOW so a
		// slow turn adds no latency beyond its own processing time. If work is claimable but we lost the claim,
		// retry -- throttled, because a no-op response comes back in milliseconds and an immediate respawn is a
		// hot loop (see job_monitor.tpl, which handles the same case against the same queue).
		function spawnWorker() {
			if(!poll.active || poll.inflight >= cfg.workers)
				return;

			// Nothing claimable: the turn is already IN_FLIGHT somewhere, so a sidecar would dequeue nothing
			// and return zeros in milliseconds -- one wasted round trip per gate cycle for the whole turn.
			if(!cfg.needsWorker)
				return;

			// A backgrounded tab isn't being watched; it still needs to drain work, but not eagerly.
			if(document.visibilityState === 'hidden' && poll.spawns >= 1)
				return;

			poll.spawns++;
			poll.inflight++;

			var _logTag = ($data.find('[data-cerb-transcript-session-id]')
				.attr('data-cerb-transcript-session-id') || '').substring(0, 6);

			// No continuation token, and nothing else: this endpoint claims the NEXT waiting turn, not
			// ours, so the request says only "I am a logged-in worker donating a drain" -- which is all
			// it ever meant. A real path (not c/a on ajax.php) so nginx can route drains to the
			// background FPM pool on a URI prefix.
			var fd = new FormData();

			genericAjaxPost(fd, null, null, function(json) {
				poll.inflight--;

				// Got through -- whatever the answer, the edge is no longer refusing us.
				_edgeThrottled = false;
				if(!poll.active)
					return;

				var processed = (json && typeof json.processed === 'number') ? json.processed : 0;
				var ready = (json && typeof json.ready === 'number') ? json.ready : 0;

				if(processed > 0) {
					gatePoll();
					return;
				}

				// Every concurrency slot is busy. Unlike a lost claim race, retrying in 1.5s can't help --
				// nothing frees a slot that fast -- so let the next gate cycle spawn a fresh sidecar and
				// spend no requests in between. The marker's slot line explains the wait meanwhile.
				if(json && json.throttled)
					return;

				// Claimable work we didn't win. `ready` is queue-GLOBAL, so this can be somebody else's
				// turn entirely -- retry a bounded number of times, throttled, then let the gate's own
				// timer carry it rather than spinning against a queue we keep losing.
				if(ready > 0 && poll.retries < _WORKER_MAX_RETRIES) {
					poll.retries++;
					setTimeout(spawnWorker, _WORKER_RETRY_MS);
				}
			}, {
				// `?_log=` is for the FPM access log only -- the path alone cannot tell two chats apart,
				// and every sidecar posts an empty body. Six characters of the session uuid is enough to
				// group a log by conversation without putting a full identifier in a URL.
				//
				// It identifies the CALLER, not the work: this endpoint claims the NEXT waiting turn,
				// which may well belong to someone else.
				path: 'queue/nextAgentTurn' + (_logTag ? '?_log=' + encodeURIComponent(_logTag) : ''),
				// Expected, not an error: this sidecar deliberately outlives the gateway's ~30s request timeout
				// (504, or 0 when the socket is simply closed). The queue worker finishes the turn off-request and
				// the gated poll collects it, so stay quiet — the default UI would alert and clear alerts.
				fail: function(err) {
					poll.inflight--;

					if(504 === err.status || 0 === err.status)
						return;

					// 529 = no capacity, from either layer: the app when the concurrency pool is
					// full (200 + `throttled` on the success path above), nginx when the background
					// FPM pool has no child free. Same meaning, so the same response -- stay quiet
					// and spend no requests. Retrying fast cannot help, and the next gate cycle
					// spawns a fresh sidecar anyway. NOT falling through to ajaxFail() matters:
					// that clears any alert the worker was reading, for a condition that is normal
					// backpressure rather than a failure.
					if(529 === err.status) {
						_edgeThrottled = true;
						return;
					}

					Devblocks.ajaxFail(err);
				}
			});
		}

		for(var i = 0; i < cfg.workers; i++)
			spawnWorker();

		poll.timer = setTimeout(gatePoll, cfg.pollMs);
	}

	// A FAILED sendMessage (a 500, a dropped connection) used to strand the interaction: genericAjaxPost only
	// calls its `done` callback on success, so the spinner — detached there — stayed up forever, the queue poll
	// was already deactivated by gatePoll(), and nothing was polling anything. The user saw "working" and it
	// never moved again.
	//
	// The continuation is untouched by a failed POST, so the interaction IS recoverable: retry the same submit.
	// Transient statuses retry themselves a few times (a blip mid-turn shouldn't need a human); anything else —
	// or a retry budget spent — surfaces an error with a manual Retry, next to a transcript we deliberately
	// leave on screen.
	var _MAX_SUBMIT_RETRIES = 3;
	var _submitRetries = 0;
	var _submitRetryTimer = null;

	function clearSubmitError() {
		if(_submitRetryTimer) {
			clearTimeout(_submitRetryTimer);
			_submitRetryTimer = null;
		}

		$form.find('.cerb-form-builder-submit-error').remove();
	}

	function showSubmitError(status) {
		stopQueuePoll();
		$spinner.detach();
		clearSubmitError();

		var message = status
			? ('The server returned an error (HTTP ' + status + ') and this turn did not go through.')
			: 'The connection to the server was lost and this turn did not go through.';

		var $retry = $('<button type="button" class="cerb-ui-button cerb-ui-button--subtle" style="margin-left:0.75em;">')
			.html('<span class="cerb-icons cerb-icon-refresh"></span> Retry')
			.on('click', function() {
				clearSubmitError();
				_submitRetries = 0;
				$form.triggerHandler('cerb-form-builder-submit');
			});

		$('<div class="cerb-form-builder-error cerb-form-builder-submit-error" style="margin-top:0.75em;">')
			.text(message)
			.append($retry)
			.insertAfter($data);

		// Composers hide themselves while a turn is in flight (agentPrompt swaps in a Stop button). Nothing is in
		// flight anymore, so tell them to come back — otherwise the reader is left with no way to act at all.
		$form.trigger('cerb-interaction-submit-failed');
	}

	// Circuit breaker for runaway auto-submits: an auto-submitting await (submit_auto / uiCommand) that keeps
	// erroring re-renders and re-fires forever. Track submit timestamps in a rolling window; a burst far faster
	// than any human or LLM turn is a loop — halt and surface it instead of spamming sendMessage.
	var _submitTimes = [];
	var _SUBMIT_WINDOW_MS = 4000;
	var _SUBMIT_MAX = 25;

	$form.on('submit', function(e) {
		e.preventDefault();
		e.stopPropagation();

		$form.triggerHandler('cerb-form-builder-submit');
		return false;
	});

	$form.on('cerb-form-builder-submit', function(e) {
		e.stopPropagation();

		var _now = (window.performance && performance.now) ? performance.now() : (new Date()).getTime();
		_submitTimes.push(_now);
		while(_submitTimes.length && (_now - _submitTimes[0]) > _SUBMIT_WINDOW_MS) _submitTimes.shift();

		if(_submitTimes.length > _SUBMIT_MAX) {
			_submitTimes = [];
			$spinner.detach();
			// A "retrying in 10 secs" line left standing under a stop message would promise something that is
			// no longer going to happen.
			setQueueNotice('');
			$data.html('<div class="cerb-form-builder-error">This interaction was stopped after too many automatic steps without input (a possible loop). Check the automation.</div>').fadeIn();
			return;
		}

		$data.find('.cerb-form-builder-prompt-submit').hide();
		clearSubmitError();
		$spinner.insertAfter($data);

		var formData = new FormData($form[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'sendMessage');

		genericAjaxPost(formData, null, null, function(html) {
			// Reached the server and got a response — any earlier failure is water under the bridge.
			_submitRetries = 0;

			// A queue-await response is an invisible background-poll marker (an async llm.agent turn is in flight).
			// Keep the current transcript + the dots spinner exactly where they are and just poll; don't clobber.
			var $queue = $($.parseHTML(html || '')).filter('[data-cerb-await-queue]');

			if($queue.length) {
				// Resuming a chat that was left mid-turn lands here with NOTHING on screen: the panel is empty
				// and the marker is invisible, so the reader would stare at a bare spinner. There's no form to
				// re-render — a queue await replaces `__return` wholesale, so the previous form's elements are
				// gone — so say what's happening instead. The turn's own render replaces this when it lands.
				if(!$.trim($data.html()).length)
					$data.html('<div class="cerb-u-text-muted" style="padding:0.5em;">Picking up a reply that was already in progress…</div>').fadeIn();

				runQueuePoll({
					// A retry wait deliberately exceeds the usual ramp ceiling: nothing can happen until the
					// message is claimable, so the server tells us to sleep through it rather than re-running
					// the script every couple of seconds to be told the same thing.
					pollMs: Math.max(500, parseInt($queue.attr('data-poll-ms'), 10) || 2000),
					workers: Math.min(4, Math.max(1, parseInt($queue.attr('data-workers'), 10) || 1)),
					// Absent attribute means an older marker -- spawn, since failing to drain the queue is
					// worse than one wasted request.
					needsWorker: '0' !== ($queue.attr('data-needs-worker') || '1')
				});

				// AFTER runQueuePoll(), which re-arms via stopQueuePoll() and would otherwise clear this right
				// back off. Recomputed server-side every cycle, so it appears when a wait starts and disappears
				// on its own when the turn resumes.
				setQueueNotice($queue.attr('data-notice') || '');
				// `data-needs-worker` is 0 once nothing is claimable -- i.e. our turn is IN FLIGHT, so we
				// are no longer waiting for a worker whatever the last refusal said. Clearing here is what
				// stops the chip sticking: the flag used to clear only when a SIDECAR SUCCEEDED, but the
				// panel stops spawning sidecars the moment needsWorker goes 0, so nothing ever cleared it
				// and the chip stayed up for the whole turn it was already running.
				if('0' === ($queue.attr('data-needs-worker') || '1'))
					_edgeThrottled = false;

				setQueueSlots(
					parseInt($queue.attr('data-slots-used'), 10) || 0,
					parseInt($queue.attr('data-slots-total'), 10) || 0,
					parseInt($queue.attr('data-waiting'), 10) || 0,
					_edgeThrottled
				);

				// THE THIRD ENTRY POINT for the transcript poll. The other two both assume a human at a
				// composer: a render the server already knew was in-progress (`$is_in_progress`), and the
				// `cerb-agentprompt-submitted` handler. A turn that begins any other way -- a tile-launched
				// interaction, a resumed continuation, or simply a submit whose event didn't reach a
				// transcript that wasn't on screen yet -- has no starter, so the transcript, the Stop row
				// and the working chip all stay at their server-rendered display:none for the WHOLE turn
				// while the queue drains it. You get nothing, then everything at once.
				//
				// This marker is the universal signal that a turn is queued or running, and it arrives on
				// every route in, so starting here closes the gap for all of them. startPoll() is
				// idempotent (flagged on the container, not in a closure) precisely so it can be driven
				// from outside like this, and an idle transcript is never reached -- no marker, no start.
				// ONCE, not every cycle. startPoll() resets its elapsed clock and force-reveals the
				// working chip BEFORE its own `_cerbTranscriptPolling` guard, so re-calling it on each
				// marker pinned the clock at 0s and re-showed a chip the poll response had just hidden
				// -- a blinking stopwatch. Check the flag here; it clears when the poll stops, so a
				// genuinely new turn still gets a starter.
				$data.find('[data-cerb-transcript-echo-key]').each(function() {
					if(this._cerbTranscriptPolling)
						return;

					if('function' === typeof this._cerbTranscriptStartPoll)
						this._cerbTranscriptStartPoll();
				});

				return;
			}

			stopQueuePoll();
			$spinner.detach();
			$data.html(html).fadeIn();

			let _focusable = (window.CerbUI && CerbUI.utils ? CerbUI.utils.focusable($data[0]) : []).find(function(el) { return el.getAttribute('tabindex') !== '-1' && !(el.tagName === 'INPUT' && el.type === 'checkbox') && !el.classList.contains('cerb-paging'); });
			if(_focusable) _focusable.focus();
		}, {
			// `fail` REPLACES the default failure UI (an alert), which on its own would leave the spinner up and
			// the interaction dead. Retry transient failures silently — a 5xx or a dropped socket mid-turn is
			// usually a blip, and the continuation didn't advance — then surface a recoverable error.
			fail: function(err) {
				var status = (err && err.status) ? err.status : 0;

				// 0 = no response at all (socket closed / navigating away). 5xx = the server broke on THIS request.
				// A 4xx is a real answer (403/404/405) and retrying it just repeats the same answer.
				if((0 === status || status >= 500) && _submitRetries < _MAX_SUBMIT_RETRIES) {
					_submitRetries++;

					// Geometric backoff, and slow enough that the retries can't trip the runaway-submit breaker.
					_submitRetryTimer = setTimeout(function() {
						_submitRetryTimer = null;
						$form.triggerHandler('cerb-form-builder-submit');
					}, 500 * Math.pow(2, _submitRetries));

					return;
				}

				_submitRetries = 0;
				showSubmitError(status);
			}
		});
	});

	$form.on('cerb-form-builder-reset', function(e) {
		stopQueuePoll();

		e.stopPropagation();

		$spinner.insertAfter($data.hide());

		var formData = new FormData($form[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'sendMessage');
		formData.set('reset', '1');

		genericAjaxPost(formData, null, null, function(html) {
			$spinner.detach();
			$data.html(html).fadeIn();

			let _focusable = (window.CerbUI && CerbUI.utils ? CerbUI.utils.focusable($data[0]) : []).find(function(el) { return el.getAttribute('tabindex') !== '-1' && !(el.tagName === 'INPUT' && el.type === 'checkbox') && !el.classList.contains('cerb-paging'); });
			if(_focusable) _focusable.focus();
			$form.trigger($.Event('cerb-interaction-reset'));
		});
	});

	$form.on('cerb-form-builder-end', function(e) {
		e.stopPropagation();

		var event_data = {
			eventData: e.eventData
		};

		$form.trigger($.Event('cerb-interaction-done', event_data));
	});

	$form.triggerHandler('cerb-form-builder-submit');
});
</script>
