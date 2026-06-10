<?php /** @noinspection PhpUnused */
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

class PageSection_SetupScheduler extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'scheduler');
		
		$jobs = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
		$tpl->assign('jobs', $jobs);
		
		$tpl->assign('max_parallel', APP_QUEUE_CONCURRENCY_SLOTS);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/scheduler/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'showJobPeek':
					return $this->_configAction_showJobPeek();
				case 'saveJobJson':
					return $this->_configAction_saveJobJson();
				case 'showJobRun':
					return $this->_configAction_showJobRun();
				case 'runJob':
					return $this->_configAction_runJob();
				case 'runReadyJobs':
					return $this->_configAction_runReadyJobs();
				case 'viewSparklinesJson':
					return $this->_configAction_viewSparklinesJson();
			}
		}
		return false;
	}

	private function _configAction_showJobPeek() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null,'string','');

		if(null == ($job = DevblocksPlatform::getExtension($id, true)))
			return;

		$tpl->assign('job', $job);
		$tpl->assign('max_parallel', APP_QUEUE_CONCURRENCY_SLOTS);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/scheduler/job_peek.tpl');
	}

	private function _configAction_showJobRun() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null,'string','');

		if(null == ($job = DevblocksPlatform::getExtension($id, true)))
			return;

		$tpl->assign('job', $job);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/scheduler/job_run.tpl');
	}

	// Run a scheduler job on demand, bypassing the /cron endpoint (and its admin-cookie auth), so we
	// enforce superuser here. Captures the level-7 log output to show in the run popup.
	private function _configAction_runJob() {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			$id = DevblocksPlatform::importGPC($_POST['id'] ?? null,'string','');

			$manifest = DevblocksPlatform::getExtension($id);
			$job = $manifest?->createInstance(); /* @var $job CerberusCronPageExtension */

			if(!$job instanceof CerberusCronPageExtension)
				throw new Exception("Can't load scheduler job.");

			CerberusContexts::pushActivityDefaultActor(CerberusContexts::CONTEXT_APPLICATION, 0);
			@set_time_limit(600);

			$logger = DevblocksPlatform::services()->log();
			$old_level = $logger->setLogLevel(7); // debug — capture everything the job logs

			$started_at = microtime(true);

			ob_start(); // the log manager fputs to php://output, which output buffering intercepts
			try {
				$job->_run(); // runs run(), records metrics, updates lastrun; ignores the wait interval
			} catch(Throwable $e) {
				$logger->error($e->getMessage());
			}
			$log = ob_get_clean();

			$elapsed_ms = (int)((microtime(true) - $started_at) * 1000);

			$logger->setLogLevel($old_level);
			CerberusContexts::popActivityDefaultActor();

			echo json_encode([
				'status' => true,
				'log' => $log, // already HTML-escaped by _DevblocksLogManager
				'lastrun' => (int) $job->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0),
				'elapsed_ms' => $elapsed_ms,
			]);
			return;

		} catch(Exception $e) {
			echo json_encode(['status'=>false,'error'=>$e->getMessage()]);
			return;
		}
	}

	// In-browser auto-runner: runs every job that's ready, bypassing /cron (so it enforces superuser
	// here). Driven by the "Automatically run ready jobs" toggle polling on an interval.
	private function _configAction_runReadyJobs() {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		@set_time_limit(300);

		$ran = [];
		$crons = DevblocksPlatform::getExtensions('cerberusweb.cron', true);

		foreach($crons as $job) { /* @var $job CerberusCronPageExtension */
			if(!$job instanceof CerberusCronPageExtension || !$job->isReadyToRun(false))
				continue;

			// Attribute events to (app:0; Cerb), NOT the signed-in admin — before/after each run
			CerberusContexts::pushActivityDefaultActor(CerberusContexts::CONTEXT_APPLICATION, 0);
			try {
				$job->_run();
			} catch(Throwable $e) {
				// keep going; a failed job shouldn't stop the rest of the pass
			}
			CerberusContexts::popActivityDefaultActor();

			$ran[] = [
				'id' => $job->id,
				'lastrun' => (int) $job->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0),
			];
		}

		echo json_encode(['status' => true, 'ran' => $ran]);
	}

	// Inline sparkline series (runs bars + avg-duration line) per job, loaded async so the page paints
	// fast. One metrics.timeseries query for the whole page (no N+1). Mirrors the automations worklist.
	private function _configAction_viewSparklinesJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? [], 'array', []);
		$ids = array_filter(array_map('strval', $ids)); // job extension ids (e.g. cron.parser)

		$window = DevblocksPlatform::importGPC($_REQUEST['window'] ?? '24h', 'string', '24h');

		$row_series = [];

		// Runs (invocations) as bars first (blue), then avg duration as a line in front (orange) via
		// the shared colorScale — keyed by the 'job' dimension (the cron extension id).
		foreach($ids as $id) {
			$row_series[$id] = [
				['metric' => 'cerb.scheduler.invocations', 'function' => 'count', 'type' => 'bar', 'label' => 'runs', 'query' => ['job' => $id], 'missing' => 'zero'],
				['metric' => 'cerb.scheduler.duration', 'function' => 'avg', 'type' => 'line', 'label' => 'duration', 'query' => ['job' => $id], 'missing' => 'zero', 'suffix' => 'ms'],
			];
		}

		$out = $row_series
			? DAO_MetricValue::getSparklines($row_series, $window, $active_worker->timezone ?: null)
			: [];

		// Cast so the response is always a JSON object ({} when empty), keyed by job id
		echo json_encode((object) $out);
	}

	private function _configAction_saveJobJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$id = DevblocksPlatform::importGPC($_POST['id'] ?? null,'string','');
			
			$manifest = DevblocksPlatform::getExtension($id);
			$job = $manifest->createInstance(); /* @var $job CerberusCronPageExtension */
			
			if (!$job instanceof CerberusCronPageExtension)
				throw new Exception("Can't load scheduler job.");
			
			$is_concurrent = array_key_exists('parallel', $job->manifest->params);
			
			$enabled = DevblocksPlatform::importGPC($_POST['enabled'] ?? null,'integer',0);
			$job->setParam(CerberusCronPageExtension::PARAM_ENABLED, $enabled);
			
			if($is_concurrent) {
				$concurrency = DevblocksPlatform::importGPC($_POST['concurrency'] ?? null,'integer',0);
				$concurrency = DevblocksPlatform::intClamp($concurrency, 0, APP_QUEUE_CONCURRENCY_SLOTS);
				
				$job->setParam(CerberusCronPageExtension::PARAM_LOCKED, 0);
				$job->setParam(CerberusCronPageExtension::PARAM_CONCURRENCY, $concurrency);
				
			} else {
				$locked = DevblocksPlatform::importGPC($_POST['locked'] ?? null,'integer',0);
				$duration = DevblocksPlatform::importGPC($_POST['duration'] ?? null,'integer',5);
				$term = DevblocksPlatform::importGPC($_POST['term'] ?? null,'string','m');
				$starting = DevblocksPlatform::importGPC($_POST['starting'] ?? null,'string','');
				
				if (!empty($starting)) {
					$starting_time = strtotime($starting);
					if (false === $starting_time)
						$starting_time = time();
					
					$starting_time -= CerberusCronPageExtension::getIntervalAsSeconds($duration, $term);
					$job->setParam(CerberusCronPageExtension::PARAM_LASTRUN, $starting_time);
				}
				
				$job->setParam(CerberusCronPageExtension::PARAM_LOCKED, $locked);
				$job->setParam(CerberusCronPageExtension::PARAM_DURATION, $duration);
				$job->setParam(CerberusCronPageExtension::PARAM_TERM, $term);
			}
			
			$job->saveConfiguration();

			// Re-render just this job's row so the page can swap it in place (no full reload). The
			// in-memory $job already reflects the saved params, so the row reflects the new state.
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('job', $job);
			$tpl->assign('max_parallel', APP_QUEUE_CONCURRENCY_SLOTS);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/scheduler/_job_row.tpl');

			echo json_encode(array('status'=>true, 'html'=>$html));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}
}