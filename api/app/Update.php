<?php
class ChUpdateController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
	    @set_time_limit(0); // no timelimit (when possible)

	    $translate = DevblocksPlatform::getTranslationService();
	    
	    $stack = $request->path;
	    array_shift($stack); // update

	    $cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
    	$url = DevblocksPlatform::getUrlService();
	    
	    switch(array_shift($stack)) {
	    	case 'unlicense':
	    		DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::LICENSE, '');
	    		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update')));
	    		break;
	    		
	    	case 'locked':
	    		if(!DevblocksPlatform::versionConsistencyCheck()) {
	    			echo "<h1>Cerberus Helpdesk 5.x</h1>";
	    			echo "The helpdesk is currently waiting for an administrator to finish upgrading. ".
	    				"Please wait a few minutes and then ". 
		    			sprintf("<a href='%s'>try again</a>.<br><br>",
							$url->write('c=update&a=locked')
		    			);
	    			echo sprintf("If you're an admin you may <a href='%s'>finish the upgrade</a>.",
	    				$url->write('c=update')
	    			);
	    		} else {
	    			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	    		}
	    		break;
	    		
	    	default:
			    $path = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
				$file = $path . 'c4update_lock';	    		
				
				$settings = DevblocksPlatform::getPluginSettingsService();
				
			    $authorized_ips_str = $settings->get('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS,CerberusSettingsDefaults::AUTHORIZED_IPS);
			    $authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
			    
		   	    $authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
			    $authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
			    
			    // Is this IP authorized?
			    $pass = false;
				foreach ($authorized_ips as $ip)
				{
					if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip)))
				 	{ $pass=true; break; }
				}
			    if(!$pass) {
				    echo vsprintf($translate->_('update.ip_unauthorized'), $_SERVER['REMOTE_ADDR']);
				    return;
			    }
				
			    // Potential errors
			    $errors = array();
			    
			    // Check upgrades
			    $remuneration = CerberusLicense::getInstance();
			    if(!is_null($remuneration->upgrades) && intval(gmdate("Ymd99",$remuneration->upgrades)) < APP_BUILD) {
			    	$errors[] = sprintf("Your Cerb5 license permits software updates through %s, and %s was released on %s.  Please <a href='%s' target='_blank'>renew your license</a>%s, <a href='%s'>remove your license</a> and enter Evaluation Mode (1 simultaneous worker), or <a href='%s' target='_blank'>download</a> an earlier version.",
			    		gmdate("F d, Y",$remuneration->upgrades),
			    		APP_VERSION,
			    		gmdate("F d, Y",gmmktime(0,0,0,substr(APP_BUILD,4,2),substr(APP_BUILD,6,2),substr(APP_BUILD,0,4))),
			    		'http://www.cerberusweb.com/buy',
			    		!is_null($remuneration->key) ? sprintf(" (%s)",$remuneration->key) : '',
			    		$url->write('c=update&a=unlicense'),
			    		'http://www.cerberusweb.com/download'
			    	);
			    }
			    
			    // Check requirements
			    $errors += CerberusApplication::checkRequirements();
			    
			    if(!empty($errors)) {
				    echo "
				    <style>
				    a { color: red; font-weight:bold; }
				    ul { color:red; }
				    </style>
				    ";
			    	
				    echo "<h1>Cerberus Helpdesk 5.x</h1>";
				    
			    	echo $translate->_('update.correct_errors');
			    	echo "<ul>";
			    	foreach($errors as $error) {
			    		echo "<li>".$error."</li>";
			    	}
			    	echo "</ul>";
			    	exit;
			    }
			    
			    try {
				    // If authorized, lock and attempt update
					if(!file_exists($file) || @filectime($file)+600 < time()) { // 10 min lock
						// Log everybody out since we're touching the database
						$session = DevblocksPlatform::getSessionService();
						$session->clearAll();

						// Lock file
						touch($file);
						
						// Recursive patch
						CerberusApplication::update();
						
						// Clean up
						@unlink($file);

						$cache = DevblocksPlatform::getCacheService();
						$cache->save(APP_BUILD, "devblocks_app_build");

						// Clear all caches
						$cache->clean();
						DevblocksPlatform::getClassLoaderService()->destroy();
						
						// Clear compiled templates
						$tpl = DevblocksPlatform::getTemplateService();
						$tpl->utility->clearCompiledTemplate();
						$tpl->cache->clearAll();

						// Reload plugin translations
						DAO_Translation::reloadPluginStrings();

						// Redirect
				    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	
					} else {
						echo $translate->_('update.locked_another');
					}
					
	    	} catch(Exception $e) {
	    		unlink($file);
	    		die($e->getMessage());
	    	}
	    }
	    
		exit;
	}
}
