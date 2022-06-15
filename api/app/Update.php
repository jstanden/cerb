<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */
class ChUpdateController extends DevblocksControllerExtension {
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		@set_time_limit(3600); // 1h

		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path;
		array_shift($stack); // update

		$cache = DevblocksPlatform::services()->cache();
		$url = DevblocksPlatform::services()->url();
		
		switch(array_shift($stack)) {
			case 'unlicense':
				DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::LICENSE, '');
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update')));
				break;
				
			case 'locked':
				if(!DevblocksPlatform::versionConsistencyCheck()) {
					echo "<html><head>";
					echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">';
					echo "</head>";
					echo "<body>";
					echo sprintf("<h1>Cerb %s</h1>", APP_VERSION);
					echo "The application is currently waiting for an administrator to finish upgrading. ".
						"Please wait a few minutes and then ".
						sprintf("<a href='%s'>try again</a>.<br><br>",
							$url->write('c=update&a=locked')
						);
					echo sprintf("If you're an admin you may <a href='%s'>finish the upgrade</a>.",
						$url->write('c=update')
					);
					echo "</body>";
					echo "</html>";
				} else {
					DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
				}
				break;
				
			default:
				$path = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
				$file = $path . 'cerb_update_lock';
				
				$settings = DevblocksPlatform::services()->pluginSettings();
				
				$authorized_ips_str = $settings->get('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS,CerberusSettingsDefaults::AUTHORIZED_IPS);
				$authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
				
				$authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
				$authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
				
				// Is this IP authorized?
				if(!DevblocksPlatform::isIpAuthorized(DevblocksPlatform::getClientIp(), $authorized_ips)) {
					echo sprintf($translate->_('update.ip_unauthorized'), DevblocksPlatform::getClientIp());
					return;
				}
				
				// Potential errors
				$errors = [];

				/*
				 * This well-designed software is the result of over 8 years of R&D.
				 * We're sharing every resulting byte of that hard work with you.
				 * You're free to make changes for your own use, but we ask that you
				 * please respect our licensing and help support commerical open source.
				 */
				$remuneration = CerberusLicense::getInstance();
				@$u = $remuneration->upgrades;
				
				if(!is_null($u) && $u < CerberusLicense::getReleaseDate(APP_VERSION)) {
					$errors[] = sprintf("Your Cerb license coverage for major software updates expired on %s, and %s is not included.  Please <a href='%s' target='_blank' rel='noopener'>renew your license</a>%s, <a href='%s'>remove your license</a> and enter Evaluation Mode (1 simultaneous worker), or <a href='%s' target='_blank' rel='noopener'>download</a> an earlier version.",
						gmdate("F d, Y",$u),
						APP_VERSION,
						'https://cerb.ai/pricing/self-hosted/',
						!is_null($remuneration->key) ? sprintf(" (%s)",$remuneration->key) : '',
						$url->write('c=update&a=unlicense'),
						'https://github.com/cerb/cerb-release'
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
					
					echo sprintf("<h1>Cerb %s</h1>", APP_VERSION);
					
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
					if(!file_exists($file) || @filectime($file)+1200 < time()) { // 20 min lock
						// Log everybody out since we're touching the database
						//$session = DevblocksPlatform::services()->session();
						//$session->clearAll();

						// Lock file
						touch($file);
						
						// Reset the classloader
						DevblocksPlatform::services()->classloader()->destroy();
						
						// Recursive patch
						CerberusApplication::update();
						
						// Clean up
						@unlink($file);

						// Clear all caches
						$cache->clean();
						DevblocksPlatform::services()->classloader()->destroy();
						
						// Clear compiled templates
						if(!APP_SMARTY_COMPILE_PATH_MULTI_TENANT) {
							$tpl = DevblocksPlatform::services()->template();
							$tpl->clearCompiledTemplate();
							$tpl->clearAllCache();
						}

						if(!APP_SMARTY_SANDBOX_COMPILE_PATH_MULTI_TENANT) {
							$tpl = DevblocksPlatform::services()->templateSandbox();
							$tpl->clearCompiledTemplate();
							$tpl->clearAllCache();
						}
						
						// Synchronize bundled resources
						CerberusApplication::initBundledResources();
						
						// Reload plugin translations
						DAO_Translation::reloadPluginStrings();

						// Set the build in opcache
						file_put_contents(APP_STORAGE_PATH . '/version.php', sprintf("<?php define('APP_BUILD_CACHED', %d); ?>", APP_BUILD));
						
						// Redirect
						DevblocksPlatform::redirect(new DevblocksHttpResponse([]));
	
					} else {
						echo $translate->_('update.locked_another');
					}
					
			} catch(Exception $e) {
				unlink($file);
				DevblocksPlatform::dieWithHttpError($e->getMessage());
			}
		}
		
		exit;
	}
}
