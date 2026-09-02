<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

/**
 * `/queue` -- the browser-driven drains, on a stable path prefix that nginx routes to the
 * background FPM pool (`install/docker/_conf/nginx.conf`) so a long drain never occupies a child
 * that interactive page loads need.
 *
 *   POST /queue/nextAgentTurn      worker session + CSRF   a browser donating a drain while it waits
 *   POST /queue/drainJob           worker session + CSRF   the queue-job monitor widget
 *
 * Both are session-authenticated POSTs, deliberately. **External draining lives on `/cron`**
 * (`GET /cron/cron.background_queue`, service token, scope `cron`), which is already stateless,
 * already CSRF-exempt, and routed to the same pool by the same nginx map. A token client cannot
 * POST here at all: `Engine.php:699` runs the session CSRF check on every non-GET
 * unconditionally, and exempting `queue` would drop the origin check, the referer check AND the
 * session token for these two endpoints at once.
 *
 * Draining is bounded by the concurrency-slot pool exactly like every other entry point -- see the
 * drain-entry-point rule in `.claude/skills/cerb-dev/references/queue-system.md`. A cap means
 * nothing if something can drain around it.
 */
class Controller_Queue extends DevblocksControllerExtension {
	const ID = 'core.controller.queue';

	const QUEUE_LLM_AGENT_REQUESTS = 'cerb.llm.agent.requests';

	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;

		array_shift($stack); // queue

		$action = strval(array_shift($stack));

		// `is_ajax` makes `dieWithHttpError()` answer with a status rather than rendering an HTML
		// error page into a JSON reader.
		$request->is_ajax = true;

		if(null == CerberusApplication::getActiveWorker())
			DevblocksPlatform::dieWithHttpError(null, 401);

		if(false === ($this->_invoke($action))) {
			if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
				trigger_error(
					sprintf('Call to undefined queue action `%s::%s`', get_class($this), $action),
					E_USER_NOTICE
				);
			}

			DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}

	private function _invoke($action) {
		switch($action) {
			case 'drainJob':
				return $this->_queueAction_drainJob();
			case 'nextAgentTurn':
				return $this->_queueAction_nextAgentTurn();
		}

		return false;
	}

	/**
	 * `POST /queue/nextAgentTurn` -- advance the shared LLM queue by exactly ONE turn and return only
	 * counters. Moved here from `module=automation&action=awaitQueueWork`.
	 *
	 * **It claims the NEXT waiting turn, not YOURS.** `llm.php` dequeues with no `$job_id` and
	 * agent turns are all `job_id = 0`, so live polls compete free-for-all: your poll may spend its
	 * budget on a stranger's turn, and someone else's poll may be what clears your wait. That is why
	 * the old `continuation_token` argument is gone rather than merely unused -- it never constrained
	 * which message was claimed, so carrying it implied a scoping that does not exist. Status polling
	 * stays where it belongs, on `sendMessage` and `pollAgentTurn`.
	 *
	 * Auth is therefore just "an active worker", which is the right level: the response carries only
	 * counters -- never turn content, which lands in `llm_agent_session` / `llm_agent_message` -- so a
	 * drainer learns queue depth and nothing else, and the work it advances would be run by the cron
	 * regardless. The slot pool, not a token, is what bounds spend.
	 *
	 * **The SERVER decides how many turns a request takes** (`getTurnBatchSize()`), not the client. It used
	 * to pass 1, which made every sidecar wait out one turn that was probably somebody else's before it
	 * could try again -- the client cannot know what is queued or how much this process can carry, so that
	 * was the wrong place for the number. One request now sweeps a batch, finishing in the time of its
	 * SLOWEST turn rather than the sum, because the batch size is tied to the concurrency cap.
	 *
	 * This stays safe for the watching worker because the two loops are decoupled: the gate poll and the
	 * transcript watcher run on their own short cadence against `ajax.php`, so the chat keeps advancing
	 * while this one long request is in flight. When it returns, the client fires another.
	 */
	private function _queueAction_nextAgentTurn() : bool {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$queue_service = DevblocksPlatform::services()->queue();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$processed = 0;
		$ready = 0;

		if(($queue = DAO_Queue::getByName(self::QUEUE_LLM_AGENT_REQUESTS))) {
			$ready = DAO_QueueMessage::countAvailable($queue->id);

			// One sidecar per open chat means N workers with chats open would otherwise yield N
			// concurrent provider calls, bounded by nothing. Losing the race is NOT an error and must
			// not disturb the gate: the message stays AVAILABLE, awaitGate() reads `pending`, the panel
			// keeps its transcript and spinner, and a later sidecar or the cron takes the turn.
			if(null === ($slot = $queue_service->getAvailableConcurrencySlot())) {
				// 529, the same status nginx returns when the background pool has no child free.
				// One status for one meaning, so a client needs one rule -- the JSON body is
				// unchanged, and jQuery hands it back as `err.responseJSON`.
				$queue_service->respondThrottled(['processed' => 0, 'ready' => $ready, 'throttled' => true]);
				return true;
			}

			try {
				// One turn, published so the gated poll sees the result.
				$llm = DevblocksPlatform::services()->llm();
				$processed = $llm->processQueue($queue, time() + 25, $ready, null, $llm->getTurnBatchSize());
				$queue_service->publish();
			} finally {
				$queue_service->releaseConcurrencySlot($slot);
			}

			$ready = DAO_QueueMessage::countAvailable($queue->id);
		}

		echo json_encode(['processed' => $processed, 'ready' => $ready]);
		return true;
	}

	/**
	 * `POST /queue/drainJob` -- run one bounded drain against ONE job, for the queue-job monitor.
	 *
	 * SCOPED, unlike `nextAgentTurn`: every job-backed consumer passes the job id down, so a job's
	 * workers only ever claim that job's messages. It also PARALLELIZES -- the widget spawns up to
	 * `max_concurrency` of these against one job.
	 *
	 * The three ACL layers below run in order. Dropping any one of them is a quiet relaxation.
	 */
	private function _queueAction_drainJob() : bool {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$active_worker = CerberusApplication::getActiveWorker();

		$widget_id = DevblocksPlatform::importGPC($_POST['widget_id'] ?? null, 'int', 0);
		$widget_type = DevblocksPlatform::importGPC($_POST['widget_type'] ?? null, 'string', '');
		$job_id = DevblocksPlatform::importGPC($_POST['card_context_id'] ?? null, 'int', 0);

		// 1. The widget is readable. Both widget kinds are checked with Context_ProfileWidget, which
		// is what the card extension already did (`api/cards/widgets/queue_job_monitor.php:42`) --
		// replicated rather than corrected, so this move changes no permissions.
		$widget = ('card' == $widget_type)
			? DAO_CardWidget::get($widget_id)
			: DAO_ProfileWidget::get($widget_id);

		if(!$widget)
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!Context_ProfileWidget::isReadableByActor($widget, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		// 2. The job is readable.
		if(!($queue_job = DAO_QueueJob::get($job_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!Context_QueueJob::isReadableByActor($queue_job, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		// 3. Draining somebody else's job additionally requires write.
		if($queue_job->worker_id != $active_worker->id
			&& !Context_QueueJob::isWriteableByActor($queue_job, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		$queue_service = DevblocksPlatform::services()->queue();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		if(null === ($slot = $queue_service->getAvailableConcurrencySlot())) {
			// Surface live counts so the widget can distinguish "throttled with
			// claimable work" (keep trying) from "throttled but nothing ready"
			// (back off / idle).
			$counts = DAO_QueueJob::getLiveCounts($queue_job);

			// WHY we're throttled, not just that we are. The pool is shared with LLM agent turns and
			// the cron, so a job can stall on work that isn't its own and has no other way to say so.
			// Only read here: on the success path we hold a slot, so occupancy would count ourselves
			// and explain nothing. The client keeps the last reading while any worker is throttled.
			$usage = $queue_service->getConcurrencyUsage();

			// 529, matching nginx's refusal when the background pool has no child free -- one
			// status for one meaning. The BODY is unchanged byte for byte: the widget reads
			// `slot`, `ready`, `scheduled`, `inflight`, `next_available_at` and the two slot
			// counts off it, and jQuery still delivers them via `err.responseJSON`.
			$queue_service->respondThrottled([
				'slot' => false,
				'ready' => $counts['available'],
				'scheduled' => $counts['scheduled'],
				'inflight' => $counts['inflight'],
				'next_available_at' => $counts['next_available_at'],
				'slots_used' => $usage['used'],
				'slots_total' => $usage['total'],
			]);
			return true;
		}

		$stop_time = time() + 10;

		if(!($queue = DAO_Queue::get($queue_job->queue_id))
			|| !($queue_extension = $queue->getExtension())) {
			$queue_service->releaseConcurrencySlot($slot);
			echo json_encode([
				'slot' => true, 'processed' => 0,
				'ready' => 0, 'scheduled' => 0, 'inflight' => 0, 'next_available_at' => 0,
			]);
			return true;
		}

		// Hand the slot back when the 10s drain ends rather than at request close -- the counts
		// below and the JSON encode don't need it, and slots are scarce on a small license.
		try {
			$processed = $queue_extension->processQueueMessages($queue, $stop_time, 0, $queue_job);
		} finally {
			$queue_service->releaseConcurrencySlot($slot);
		}

		// Read live counts directly so the widget paces its worker pool to the work
		// that's actually claimable now (`ready`), backs off while failed messages
		// wait out their retry window (`scheduled`), and finalizes only when nothing
		// remains -- cheaper and more accurate than the cached queue_job.count_*.
		$counts = DAO_QueueJob::getLiveCounts($queue_job);

		echo json_encode([
			'slot' => true,
			'processed' => $processed,
			'ready' => $counts['available'],
			'scheduled' => $counts['scheduled'],
			'inflight' => $counts['inflight'],
			'next_available_at' => $counts['next_available_at'],
		]);

		return true;
	}
};
