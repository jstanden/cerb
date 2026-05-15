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

class ChCronController extends DevblocksControllerExtension {
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$reload = DevblocksPlatform::importGPC($_REQUEST['reload'] ?? null, 'integer',0);
		$loglevel = DevblocksPlatform::importGPC($_REQUEST['loglevel'] ?? null, 'integer',0);
		
		$logger = DevblocksPlatform::services()->log();
		$translate = DevblocksPlatform::getTranslationService();
		
		$is_ignoring_wait = DevblocksPlatform::importGPC($_REQUEST['ignore_wait'] ?? null, 'integer',0);
		$is_ignoring_internal = DevblocksPlatform::importGPC($_REQUEST['ignore_internal'] ?? null, 'integer',0);
		
		$stack = $request->path;
		
		array_shift($stack); // cron
		$job_ids_str = array_shift($stack);
		$job_ids = DevblocksPlatform::parseCsvString($job_ids_str);
		
		// Authorize each requested job
		if($job_ids) {
			foreach ($job_ids as $job_id) {
				if (!CerberusApplication::isRequestAuthorized('cron:' . $job_id))
					CerberusApplication::respondWithErrorReason(CerbErrorReason::AccessDeniedToken, true);
			}
		} else {
			if (!CerberusApplication::isRequestAuthorized('cron'))
				CerberusApplication::respondWithErrorReason(CerbErrorReason::AccessDeniedToken, true);
		}
		
		$logger->setLogLevel($loglevel);
		
		@set_time_limit(600); // 10 mins
		
		CerberusContexts::pushActivityDefaultActor(CerberusContexts::CONTEXT_APPLICATION, 0);
		
		$url = DevblocksPlatform::services()->url();
		$time_left = intval(ini_get('max_execution_time')) ?: 86400;
		
		$logger->info(sprintf("[Scheduler] Set Time Limit: %d seconds", $time_left));
		
		if($reload) {
			$reload_url = sprintf("%s?reload=%d&loglevel=%d&ignore_wait=%d&ignore_internal=%d",
				$url->write('c=cron' . ($job_ids_str ? ("&a=".$job_ids_str) : "")),
				intval($reload),
				intval($loglevel),
				intval($is_ignoring_wait),
				intval($is_ignoring_internal)
			);
			echo "<HTML>".
			"<HEAD>".
			"<TITLE></TITLE>".
			"<meta http-equiv='Refresh' content='".intval($reload).";".$reload_url."'>".
			"<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>".
			"</HEAD>".
			"<BODY>";
		}

		$cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
		$jobs = new CerbPriorityQueueDesc();
		
		if(!$job_ids) { // do everything
			if($is_ignoring_internal) {
				$cron_manifests = array_filter($cron_manifests, function($instance) {
					return match ($instance->id) {
						'cron.automations',
						'cron.background_queue',
						'cron.bot.scheduled_behavior',
						'cron.heartbeat',
						'cron.mail_queue',
						'cron.mailbox',
						'cron.maint',
						'cron.packages',
						'cron.parser',
						'cron.reminders',
						'cron.search',
						'cron.storage' => false,
						default => true,
					};
				});
			}
			
			if(is_array($cron_manifests))
			foreach($cron_manifests as $instance) { /* @var $instance CerberusCronPageExtension */
				if($instance->isReadyToRun($is_ignoring_wait)) {
					$last_ran_at = $instance->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0);
					$jobs->insert($instance, $last_ran_at);
				}
			}
			
		} else { // do named jobs
			foreach($job_ids as $job_id) {
				if(array_key_exists($job_id, $cron_manifests)) {
					$instance = $cron_manifests[$job_id]; /* @var $instance CerberusCronPageExtension */
					
					if($instance->isReadyToRun($is_ignoring_wait)) {
						$last_ran_at = $instance->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0);
						$jobs->insert($instance, $last_ran_at);
					}
				}
			}
		}

		if($jobs->count()) {
			$jobs->top();
			
			while($jobs->valid()) {
				$job = $jobs->current();
				
				// Are we out of time?
				if($time_left < 20)
					continue;
				
				$started_at = time();
				
				$job->_run();
				
				// Subtract the time we've used
				$time_left -= (time()-$started_at);
				
				$logger->info(sprintf("[Scheduler] Time Remaining: %d seconds", $time_left));
				
				$jobs->next();
			}
		} elseif($reload) {
			$logger->info(sprintf($translate->_('cron.nothing_to_do'), intval($reload)));
		}
		
		if($reload) {
			echo "</BODY>".
			"</HTML>";
		}
		
		CerberusContexts::popActivityDefaultActor();
		
		exit;
	}
};
