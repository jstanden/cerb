<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
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
	    
	    $pass = false;
		foreach ($authorized_ips as $ip) {
			if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip)))
		 	{ $pass=true; break; }
		}
	    if(!$pass) {
		    echo vsprintf($translate->_('cron.ip_unauthorized'), $_SERVER['REMOTE_ADDR']);
		    return;
	    }
		
		$stack = $request->path;
		
		array_shift($stack); // cron
		$job_id = array_shift($stack);

        @set_time_limit(0); // no limit
		
		$url = DevblocksPlatform::getUrlService();
        $timelimit = intval(ini_get('max_execution_time'));
		
        if($reload) {
        	$reload_url = sprintf("%s?reload=%d&loglevel=%d&ignore_wait=%d",
        		$url->write('c=cron' . ($job_id ? ("&a=".$job_id) : "")),
        		intval($reload),
        		intval($loglevel),
        		intval($is_ignoring_wait)
        	);
			echo "<HTML>".
			"<HEAD>".
			"<TITLE></TITLE>".
			"<meta http-equiv='Refresh' content='".intval($reload).";".$reload_url."'>". 
			"<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>".
		    "</HEAD>".
			"<BODY>"; // onload=\"setTimeout(\\\"window.location.replace('".$url->write('c=cron')."')\\\",30);\"
        }

	    // [TODO] Determine if we're on a time limit under 60 seconds
		
	    $cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron', true, true);
        $jobs = array();
	    
	    if(empty($job_id)) { // do everything 
			
		    // Determine who wants to go first by next time and longest waiting
            $nexttime = time() + 86400;
		    
			if(is_array($cron_manifests))
			foreach($cron_manifests as $idx => $instance) { /* @var $instance CerberusCronPageExtension */
			    $lastrun = $instance->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0);
			    
			    if($instance->isReadyToRun($is_ignoring_wait)) {
			        if($timelimit) {
			            if($lastrun < $nexttime) {
			            	// [TODO] This should run more than one thing at a time (e.g. safe_mode)
			                $jobs[0] = $cron_manifests[$idx];
	    		            $nexttime = $lastrun;
			            }
			        } else {
    			        $jobs[] =& $cron_manifests[$idx];
			        }
			    }
			}
			
	    } else { // single job
	        $manifest = DevblocksPlatform::getExtension($job_id, false, true);
	        if(empty($manifest)) exit;
	        	        
	        $instance = $manifest->createInstance();
	        
			if($instance) {
			    if($instance->isReadyToRun($is_ignoring_wait)) {
			        $jobs[0] =& $instance;
			    }
			}
	    }
	    
		if(!empty($jobs)) {
		    foreach($jobs as $nextjob) {
	    	    $nextjob->_run();
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
