<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
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
	    			echo sprintf("<h1>Cerberus Helpdesk %s</h1>", APP_VERSION);
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

			    // Release dates
			    $r = array(
			    	'5.0' => gmmktime(0,0,0,4,22,2010),
			    	'5.1' => gmmktime(0,0,0,8,15,2010),
			    );
			    
			    /*																																																																																																																																																																																																																			*/$r = array('5.0'=>1271894400,'5.1'=>1281873600,);/*
			     * This well-designed software is the result of over 8 years of R&D.
			     * We're sharing every resulting byte of that hard work with you.
			     * You're free to make changes for your own use, but we ask that you 
			     * please respect our licensing and help support commerical open source.
			     */
			    $remuneration = CerberusLicense::getInstance();
				@$u = $remuneration->upgrades;
				
			    $version = null;
				foreach(array_keys($r) as $v) {
					if($u>=$r[$v])
						$version = array($v => $r[$v]);
				}
				
				end($r);
				
			    if(!is_null($u) && $u < end($r)) {
			    	$errors[] = sprintf("Your Cerb5 license is valid for %s software updates.  Your coverage for major software updates expired on %s, and %s is not included.  Please <a href='%s' target='_blank'>renew your license</a>%s, <a href='%s'>remove your license</a> and enter Evaluation Mode (1 simultaneous worker), or <a href='%s' target='_blank'>download</a> an earlier version.",
			    		is_array($version)?(key($version).'.x'):('earlier'),
			    		gmdate("F d, Y",$u),
			    		APP_VERSION,
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
			    	
				    echo sprintf("<h1>Cerberus Helpdesk %s</h1>", APP_VERSION);
				    
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
