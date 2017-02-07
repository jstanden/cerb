<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class ChCronController extends DevblocksControllerExtension {
	function isVisible() {
		// [TODO] This should restrict by IP rather than session
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		@$reload = DevblocksPlatform::importGPC($_REQUEST['reload'],'integer',0);
		@$loglevel = DevblocksPlatform::importGPC($_REQUEST['loglevel'],'integer',0);
		
		$logger = DevblocksPlatform::getConsoleLog();
		$translate = DevblocksPlatform::getTranslationService();
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$authorized_ips_str = $settings->get('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS,CerberusSettingsDefaults::AUTHORIZED_IPS);
		$authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
		
		$authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
		$authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
		
		@$is_ignoring_wait = DevblocksPlatform::importGPC($_REQUEST['ignore_wait'],'integer',0);
		@$is_ignoring_internal = DevblocksPlatform::importGPC($_REQUEST['ignore_internal'],'integer',0);
		
		$pass = false;
		foreach ($authorized_ips as $ip) {
			if(substr($ip,0,strlen($ip)) == substr(DevblocksPlatform::getClientIp(),0,strlen($ip)))
		 	{ $pass=true; break; }
		}
		if(!$pass) {
			echo vsprintf($translate->_('cron.ip_unauthorized'), DevblocksPlatform::strEscapeHtml(DevblocksPlatform::getClientIp()));
			return;
		}
		
		$stack = $request->path;
		
		array_shift($stack); // cron
		$job_ids = array_shift($stack);
		
		@set_time_limit(3600); // 60 mins
		
		$url = DevblocksPlatform::getUrlService();
		$time_left = intval(ini_get('max_execution_time')) ?: 86400;
		
		$logger->info(sprintf("[Scheduler] Set Time Limit: %d seconds", $time_left));
		
		if($reload) {
			$reload_url = sprintf("%s?reload=%d&loglevel=%d&ignore_wait=%d&ignore_internal=%d",
				$url->write('c=cron' . ($job_ids ? ("&a=".$job_ids) : "")),
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
			"<BODY>"; // onload=\"setTimeout(\\\"window.location.replace('".$url->write('c=cron')."')\\\",30);\"
		}

		$cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron', true, true);
		$jobs = array();
		
		if(empty($job_ids)) { // do everything
			if($is_ignoring_internal) {
				$cron_manifests = array_filter($cron_manifests, function($instance) {
					switch($instance->id) {
						case 'cron.mailbox':
						case 'cron.parser':
						case 'cron.maint':
						case 'cron.heartbeat':
						case 'cron.import':
						case 'cron.storage':
						case 'cron.search':
						case 'cron.mail_queue':
						case 'cron.virtual_attendant.scheduled_behavior':
							return false;
							break;
					}
					
					return true;
				});
			}
			
			if(is_array($cron_manifests))
			foreach($cron_manifests as $idx => $instance) { /* @var $instance CerberusCronPageExtension */
				if($instance->isReadyToRun($is_ignoring_wait)) {
					$jobs[] = $cron_manifests[$idx];
				}
			}
			
		} else { // do named jobs
			foreach(DevblocksPlatform::parseCsvString($job_ids) as $job_id) {
				if(isset($cron_manifests[$job_id])) {
					$instance = $cron_manifests[$job_id];
					
					if($instance->isReadyToRun($is_ignoring_wait))
						$jobs[] = $instance;
				}
			}
		}

		// Find out when each job ran last
		if(is_array($jobs))
		foreach($jobs as $idx => $job) {
			$jobs[$idx]->last_ran_at = $job->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0);
		}
		
		// Sort jobs by longest wait to run
		DevblocksPlatform::sortObjects($jobs, 'last_ran_at');
		
		if(!empty($jobs)) {
			foreach($jobs as $nextjob) {
				// Are we out of time?
				if($time_left < 20)
					continue;
				
				$started_at = time();
				
				$nextjob->_run();
				
				// Subtract the time we've used
				$time_left -= (time()-$started_at);
				
				$logger->info(sprintf("[Scheduler] Time Remaining: %d seconds", $time_left));
			}
		} elseif($reload) {
			$logger->info(vsprintf($translate->_('cron.nothing_to_do'), intval($reload)));
		}
		
		if($reload) {
			echo "</BODY>".
			"</HTML>";
		}
		
		exit;
	}
};
