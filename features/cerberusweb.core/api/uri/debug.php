<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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

class ChDebugController extends DevblocksControllerExtension  {
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		array_shift($stack); // update

//		$cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
		$settings = DevblocksPlatform::getPluginSettingsService();

		$authorized_ips_str = $settings->get('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS,CerberusSettingsDefaults::AUTHORIZED_IPS);
		$authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
		
		$authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
		$authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
		
		// Is this IP authorized?
		$pass = false;
		foreach ($authorized_ips as $ip) {
			if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip))) {
				$pass = true;
				break;
			}
		}
		
		if(!$pass) {
			echo 'Your IP address ('.$_SERVER['REMOTE_ADDR'].') is not authorized to debug this helpdesk.  Your administrator needs to authorize your IP in Helpdesk Setup or in the framework.config.php file under AUTHORIZED_IPS_DEFAULTS.';
			return;
		}
		
		switch(array_shift($stack)) {
			case 'phpinfo':
				phpinfo();
				break;
				
			case 'check':
				echo sprintf(
					"<html>
					<head>
						<title></title>
						<style>
							BODY {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}
							FORM {margin:0px; }
							H1 { margin:0px; }
							.fail {color:red;font-weight:bold;}
							.pass {color:green;font-weight:bold;}
						</style>
					</head>
					<body>
						<h1>Cerb - Requirements Checker:</h1>
					"
				);

				$errors = CerberusApplication::checkRequirements();

				if(!empty($errors)) {
					echo "<ul class='fail'>";
					foreach($errors as $error) {
						echo sprintf("<li>%s</li>",$error);
					}
					echo "</ul>";
					
				} else {
					echo '<span class="pass">Your server is compatible with Cerb '.APP_VERSION.'!</span>';
				}
				
				echo sprintf("
					</body>
					</html>
				");
				
				break;
				
			case 'report':
				@$db = DevblocksPlatform::getDatabaseService();
				@$settings = DevblocksPlatform::getPluginSettingsService();
				
				@$tables = $db->MetaTables('TABLE',false);
				
				$report_output = sprintf(
					"[Cerb] App Version: %s\n".
					"[Cerb] App Build: %s\n".
					"[Cerb] Devblocks Build: %s\n".
					"[Cerb] URL-Rewrite: %s\n".
					"\n".
					"[Privs] storage/attachments: %s\n".
					"[Privs] storage/mail/new: %s\n".
					"[Privs] storage/mail/fail: %s\n".
					"[Privs] storage/tmp: %s\n".
					"[Privs] storage/tmp/templates_c: %s\n".
					"[Privs] storage/tmp/cache: %s\n".
					"\n".
					"[PHP] Version: %s\n".
					"[PHP] OS: %s\n".
					"[PHP] SAPI: %s\n".
					"\n".
					"[php.ini] safe_mode: %s\n".
					"[php.ini] max_execution_time: %s\n".
					"[php.ini] memory_limit: %s\n".
					"[php.ini] file_uploads: %s\n".
					"[php.ini] upload_max_filesize: %s\n".
					"[php.ini] post_max_size: %s\n".
					"\n".
					"[PHP:Extension] MySQL: %s\n".
					"[PHP:Extension] MailParse: %s\n".
					"[PHP:Extension] IMAP: %s\n".
					"[PHP:Extension] Session: %s\n".
					"[PHP:Extension] PCRE: %s\n".
					"[PHP:Extension] GD: %s\n".
					"[PHP:Extension] mbstring: %s\n".
					"[PHP:Extension] XML: %s\n".
					"[PHP:Extension] SimpleXML: %s\n".
					"[PHP:Extension] DOM: %s\n".
					"[PHP:Extension] SPL: %s\n".
					"[PHP:Extension] ctype: %s\n".
					"[PHP:Extension] JSON: %s\n".
					"[PHP:Extension] cURL: %s\n".
					"\n",
					APP_VERSION,
					APP_BUILD,
					PLATFORM_BUILD,
					(file_exists(APP_PATH . '/.htaccess') ? 'YES' : 'NO'),
					substr(sprintf('%o', @fileperms(APP_STORAGE_PATH.'/attachments')), -4),
					substr(sprintf('%o', fileperms(APP_STORAGE_PATH.'/mail/new')), -4),
					substr(sprintf('%o', fileperms(APP_STORAGE_PATH.'/mail/fail')), -4),
					substr(sprintf('%o', fileperms(APP_TEMP_PATH)), -4),
					substr(sprintf('%o', fileperms(APP_TEMP_PATH.'/templates_c')), -4),
					substr(sprintf('%o', fileperms(APP_TEMP_PATH.'/cache')), -4),
					PHP_VERSION,
					PHP_OS . ' (' . php_uname() . ')',
					php_sapi_name(),
					ini_get('safe_mode'),
					ini_get('max_execution_time'),
					ini_get('memory_limit'),
					ini_get('file_uploads'),
					ini_get('upload_max_filesize'),
					ini_get('post_max_size'),
					(extension_loaded("mysql") ? 'YES' : 'NO'),
					(extension_loaded("mailparse") ? 'YES' : 'NO'),
					(extension_loaded("imap") ? 'YES' : 'NO'),
					(extension_loaded("session") ? 'YES' : 'NO'),
					(extension_loaded("pcre") ? 'YES' : 'NO'),
					(extension_loaded("gd") ? 'YES' : 'NO'),
					(extension_loaded("mbstring") ? 'YES' : 'NO'),
					(extension_loaded("xml") ? 'YES' : 'NO'),
					(extension_loaded("simplexml") ? 'YES' : 'NO'),
					(extension_loaded("dom") ? 'YES' : 'NO'),
					(extension_loaded("spl") ? 'YES' : 'NO'),
					(extension_loaded("ctype") ? 'YES' : 'NO'),
					(extension_loaded("json") ? 'YES' : 'NO'),
					(extension_loaded("curl") ? 'YES' : 'NO')
				);
				
				if(!empty($settings)) {
					$report_output .= sprintf(
						"[Setting] HELPDESK_TITLE: %s\n".
						"[Setting] SMTP_HOST: %s\n".
						"[Setting] SMTP_PORT: %s\n".
						"[Setting] SMTP_ENCRYPTION_TYPE: %s\n".
						"\n".
						'%s',
						$settings->get('cerberusweb.core',CerberusSettings::HELPDESK_TITLE,''),
						$settings->get('cerberusweb.core',CerberusSettings::SMTP_HOST,''),
						$settings->get('cerberusweb.core',CerberusSettings::SMTP_PORT,''),
						$settings->get('cerberusweb.core',CerberusSettings::SMTP_ENCRYPTION_TYPE,''),
						''
					);
				}
				
				if(is_array($tables) && !empty($tables)) {
					$report_output .= sprintf(
						"[Stats] # Workers: %s\n".
						"[Stats] # Groups: %s\n".
						"[Stats] # Tickets: %s\n".
						"[Stats] # Messages: %s\n".
						"\n".
						"[Database] Tables:\n * %s\n".
						"\n".
						'%s',
						intval($db->getOne('SELECT count(id) FROM worker')),
						intval($db->getOne('SELECT count(id) FROM worker_group')),
						intval($db->getOne('SELECT count(id) FROM ticket')),
						intval($db->getOne('SELECT count(id) FROM message')),
						implode("\n * ",array_values($tables)),
						''
					);
				}
				
				echo sprintf(
					"<html>
					<head>
						<title></title>
						<style>
							BODY {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}
							FORM {margin:0px; }
							H1 { margin:0px; }
							.fail {color:red;font-weight:bold;}
							.pass {color:green;font-weight:bold;}
						</style>
					</head>
					<body>
						<form>
							<h1>Cerb - Debug Report:</h1>
							<textarea rows='25' cols='100'>%s</textarea>
						</form>
					</body>
					</html>
					",
				$report_output
				);
				
				break;
				
			case 'export_attendants':
				$event_mfts = DevblocksPlatform::getExtensions('devblocks.event', false, true);

				header("Content-type: application/json");
				
				$output = array(
					'events' => array(),
				);
				
				foreach($event_mfts as $event_id => $event_mft) { /* @var $event_mft DevblocksExtensionManifest */
					$triggers = DAO_TriggerEvent::getByEvent($event_id);
					
					if(empty($triggers))
						continue;
					
					$output['events'][$event_id] = array(
						'label' => $event_mft->name,
						'behaviors' => array(),
					);
					// Behaviors
					foreach($triggers as $trigger) { /* @var $trigger Model_TriggerEvent */
						if(false !== ($json = $trigger->exportToJson())) {
							$json_array = json_decode($json, true);
							$json_array['behavior']['owner'] = array(
								'owner_context' => $trigger->owner_context,
								'owner_context_id' => $trigger->owner_context_id,
							);
							$output['events'][$event_id]['behaviors'][] = $json_array;
						}
						
					} // end behaviors
					
				} // end events

				echo DevblocksPlatform::strFormatJson(json_encode($output));
				break;
				
			default:
				$url_service = DevblocksPlatform::getUrlService();
				
				echo sprintf(
					"<html>
					<head>
						<title></title>
						<style>
							BODY {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}
							FORM {margin:0px; }
							H1 { margin:0px; }
						</style>
					</head>
					<body>
						<form>
							<h1>Cerb - Debug Menu:</h1>
							<ul>
								<li><a href='%s'>Requirements Checker</a></li>
								<li><a href='%s'>Debug Report (for technical support)</a></li>
								<li><a href='%s'>phpinfo()</a></li>
								<li><a href='%s'>Export Virtual Attendants</a></li>
							</ul>
						</form>
					</body>
					</html>
					"
					,
					$url_service->write('c=debug&a=check'),
					$url_service->write('c=debug&a=report'),
					$url_service->write('c=debug&a=phpinfo'),
					$url_service->write('c=debug&a=export_attendants')
				);
				break;
		}
		
		exit;
	}
};
