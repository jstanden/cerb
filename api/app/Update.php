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
		$settings = DevblocksPlatform::getPluginSettingsService();
	    
	    switch(array_shift($stack)) {
	    	case 'locked':
	    		if(!DevblocksPlatform::versionConsistencyCheck()) {
	    			$url = DevblocksPlatform::getUrlService();
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
				
			    $authorized_ips_str = $settings->get('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS);
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
				
			    // Check requirements
			    $errors = CerberusApplication::checkRequirements();
			    
			    if(!empty($errors)) {
			    	echo $translate->_('update.correct_errors');
			    	echo "<ul style='color:red;'>";
			    	foreach($errors as $error) {
			    		echo "<li>".$error."</li>";
			    	}
			    	echo "</ul>";
			    	exit;
			    }
			    
			    // If authorized, lock and attempt update
				if(!file_exists($file) || @filectime($file)+600 < time()) { // 10 min lock
					touch($file);

				    //echo "Running plugin patches...<br>";
				    if(DevblocksPlatform::runPluginPatches('core.patches')) {
						@unlink($file);

						// [JAS]: Clear all caches
						$cache->clean();
						DevblocksPlatform::getClassLoaderService()->destroy();

						// Reload plugin translations
						DAO_Translation::reloadPluginStrings();
						
				    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
				    } else {
						@unlink($file);
				    	echo "Failure!"; // [TODO] Needs elaboration
				    } 
				    break;
				}
				else {
					echo $translate->_('update.locked_another');
				}
	    }
	    
		exit;
	}
}
