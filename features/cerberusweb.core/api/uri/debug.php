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

class ChDebugController extends DevblocksControllerExtension  {
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		array_shift($stack); // update

		$settings = DevblocksPlatform::services()->pluginSettings();

		$authorized_ips_str = $settings->get('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS,CerberusSettingsDefaults::AUTHORIZED_IPS);
		$authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
		
		$authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
		$authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
		
		if(!DevblocksPlatform::isIpAuthorized(DevblocksPlatform::getClientIp(), $authorized_ips)) {
			echo sprintf('Your IP address (%s) is not authorized to debug this helpdesk.  Your administrator needs to authorize your IP in Helpdesk Setup or in the framework.config.php file under AUTHORIZED_IPS_DEFAULTS.',
				DevblocksPlatform::strEscapeHtml(DevblocksPlatform::getClientIp())
			);
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
				
			case 'status':
				@$db = DevblocksPlatform::services()->database();

				header('Content-Type: application/json; charset=utf-8');

				$tickets_by_status = array();
				
				foreach($db->GetArrayMaster('SELECT count(*) as hits, status_id from ticket group by status_id') as $row) {
					switch($row['status_id']) {
						case 0:
							$tickets_by_status['open'] = intval($row['hits']);
							break;
						case 1:
							$tickets_by_status['waiting'] = intval($row['hits']);
							break;
						case 2:
							$tickets_by_status['closed'] = intval($row['hits']);
							break;
						case 3:
							$tickets_by_status['deleted'] = intval($row['hits']);
							break;
					}
				}
				
				$results = $db->GetArrayMaster('SELECT COUNT(*) AS hits, event_point FROM trigger_event GROUP BY event_point ORDER BY hits DESC');
				
				if(false != ($bot_behavior_counts = array_column($results, 'hits', 'event_point')) && is_array($bot_behavior_counts)) {
					array_walk($bot_behavior_counts, function(&$count) {
						$count = intval($count);
					});
					
					ksort($bot_behavior_counts);
				}
				
				$status = array(
					'counts' => array(
						'attachments' => intval($db->GetOneMaster('SELECT count(id) FROM attachment')),
						'automations' => intval($db->GetOneMaster('SELECT count(id) FROM automation')),
						'bots' => intval($db->GetOneMaster('SELECT count(id) FROM bot')),
						'bot_behaviors' => intval($db->GetOneMaster('SELECT count(id) FROM trigger_event')),
						'bot_events' => is_array($bot_behavior_counts) ? $bot_behavior_counts : [],
						'buckets' => intval($db->GetOneMaster('SELECT count(id) FROM bucket')),
						'classifiers' => intval(@$db->GetOneMaster('SELECT count(id) FROM classifier')),
						'comments' => intval($db->GetOneMaster('SELECT count(id) FROM comment')),
						'custom_fields' => intval($db->GetOneMaster('SELECT count(id) FROM custom_field')),
						'custom_fieldsets' => intval($db->GetOneMaster('SELECT count(id) FROM custom_fieldset')),
						'groups' => intval($db->GetOneMaster('SELECT count(id) FROM worker_group')),
						'mailboxes' => intval($db->GetOneMaster('SELECT count(id) FROM mailbox WHERE enabled=1')),
						'mail_transports' => intval($db->GetOneMaster('SELECT count(id) FROM mail_transport')),
						'messages' => intval($db->GetOneMaster('SELECT count(id) FROM message')),
						'messages_stats' => array(
							'received' => intval($db->GetOneMaster('SELECT count(id) FROM message WHERE is_outgoing=0')),
							'received_24h' => intval($db->GetOneMaster(sprintf('SELECT count(id) FROM message WHERE is_outgoing=0 AND created_date >= %d', time()-86400))),
							'sent' => intval($db->GetOneMaster('SELECT count(id) FROM message WHERE is_outgoing=1')),
							'sent_24h' => intval($db->GetOneMaster(sprintf('SELECT count(id) FROM message WHERE is_outgoing=1 AND created_date >= %d', time()-86400))),
						),
						'portals' => intval(@$db->GetOneMaster('SELECT count(id) FROM community_tool')),
						'resources' => intval(@$db->GetOneMaster('SELECT count(id) FROM resource')),
						'tickets' => intval($db->GetOneMaster('SELECT count(id) FROM ticket')),
						'tickets_status' => $tickets_by_status,
						'workers' => intval($db->GetOneMaster('SELECT count(id) FROM worker')),
						'workers_active_15m' => intval($db->GetOneMaster(sprintf('SELECT count(DISTINCT actor_context_id) FROM context_activity_log WHERE actor_context = "cerberusweb.contexts.worker" AND created >= %d', time()-900))),
						'workers_active_30m' => intval($db->GetOneMaster(sprintf('SELECT count(DISTINCT actor_context_id) FROM context_activity_log WHERE actor_context = "cerberusweb.contexts.worker" AND created >= %d', time()-1800))),
						'workers_active_1h' => intval($db->GetOneMaster(sprintf('SELECT count(DISTINCT actor_context_id) FROM context_activity_log WHERE actor_context = "cerberusweb.contexts.worker" AND created >= %d', time()-3600))),
						'workers_active_12h' => intval($db->GetOneMaster(sprintf('SELECT count(DISTINCT actor_context_id) FROM context_activity_log WHERE actor_context = "cerberusweb.contexts.worker" AND created >= %d', time()-43200))),
						'workers_active_24h' => intval($db->GetOneMaster(sprintf('SELECT count(DISTINCT actor_context_id) FROM context_activity_log WHERE actor_context = "cerberusweb.contexts.worker" AND created >= %d', time()-86400))),
						'workers_active_1w' => intval($db->GetOneMaster(sprintf('SELECT count(DISTINCT actor_context_id) FROM context_activity_log WHERE actor_context = "cerberusweb.contexts.worker" AND created >= %d', time()-604800))),
						'workspace_pages' => intval($db->GetOneMaster('SELECT count(id) FROM workspace_page')),
						'workspace_tabs' => intval($db->GetOneMaster('SELECT count(id) FROM workspace_tab')),
						'workspace_widgets' => intval($db->GetOneMaster('SELECT count(id) FROM workspace_widget')),
					),
					'storage_bytes' => array(
						'attachment' => intval($db->GetOneMaster('SELECT sum(storage_size) FROM attachment')),
						'context_avatar' => intval($db->GetOneMaster('SELECT sum(storage_size) FROM context_avatar')),
						'message_content' => intval($db->GetOneMaster('SELECT sum(storage_size) FROM message')),
					)
				);
				
				if(DevblocksPlatform::isPluginEnabled('cerb.project_boards'))
					$status['counts']['project_boards'] = intval(@$db->GetOneMaster('SELECT count(id) FROM project_board'));
				
				if(DevblocksPlatform::isPluginEnabled('cerb.webhooks'))
					$status['counts']['webhooks'] = intval(@$db->GetOneMaster('SELECT count(id) FROM webhook_listener'));
				
				ksort($status['counts']);
				
				// Storage
				
				$status['storage_bytes']['_total'] = array_sum($status['storage_bytes']);
				
				// Plugins
				
				$status['plugins'] = array();
				$plugins = DevblocksPlatform::getPluginRegistry();
				$plugins_enabled = 0;
				unset($plugins['cerberusweb.core']);
				unset($plugins['devblocks.core']);
				ksort($plugins);
				
				foreach($plugins as $plugin) {
					if($plugin->enabled) {
						$status['plugins'][] = $plugin->id;
						$plugins_enabled++;
					}
				}
				
				$status['counts']['plugins_enabled'] = $plugins_enabled;
				
				// Tables
				
				$status['database'] = array(
					'data_bytes' => 0,
					'index_bytes' => 0,
					'data_slack_bytes' => 0,
				);
				@$tables = $db->metaTablesDetailed();
				
				foreach($tables as $info) {
					$status['database']['data_bytes'] += $info['Data_length'];
					$status['database']['index_bytes'] += $info['Index_length'];
					$status['database']['data_slack_bytes'] += $info['Data_free'];
				}
				
				// Schedule
			
				$status['scheduler'] = [];
			
				$crons = DevblocksPlatform::getExtensions(CerberusCronPageExtension::POINT);
				
				ksort($crons);
				
				foreach($crons as $cron) {
					$cron_params = $cron->getParams();
					$last_run = intval($cron_params['lastrun'] ?? 0);
					
					$status['scheduler'][$cron->id] = [
						'enabled' => (bool)($cron_params['enabled'] ?? null),
						'duration' => intval($cron_params['duration'] ?? null),
						'term' => $cron_params['term'] ?? null,
						'locked_ts' => intval($cron_params['locked'] ?? 0),
						'last_run_ts' => $last_run,
						'last_run_string' => $last_run ? gmdate('r', $last_run) : '',
					];
				}
				
				// Output
				
				echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
				break;
				
			case 'report':
				@$db = DevblocksPlatform::services()->database();
				
				@$tables = $db->metaTablesDetailed();
				
				$report_output = sprintf(
					"[Cerb] App Version: %s\n".
					"[Cerb] App Build: %s\n".
					"[Cerb] Devblocks Build: %s\n".
					"[Cerb] URL-Rewrite: %s\n".
					"[Cerb] APP_HOSTNAME: %s\n".
					"\n".
					"[Privs] storage/attachments: %s\n".
					"[Privs] storage/mail/new: %s\n".
					"[Privs] storage/mail/fail: %s\n".
					"[Privs] tmp: %s\n".
					"[Privs] tmp/templates_c: %s\n".
					"[Privs] tmp/cache: %s\n".
					"\n".
					"[PHP] Version: %s\n".
					"[PHP] OS: %s\n".
					"[PHP] SAPI: %s\n".
					"\n".
					"[MySQL] Version: %s\n".
					"\n".
					"[php.ini] max_execution_time: %s\n".
					"[php.ini] memory_limit: %s\n".
					"[php.ini] file_uploads: %s\n".
					"[php.ini] upload_max_filesize: %s\n".
					"[php.ini] post_max_size: %s\n".
					"\n".
					"[PHP:Extension] MySQLi: %s\n".
					"[PHP:Extension] mysqlnd: %s\n".
					"[PHP:Extension] MailParse: %s\n".
					"[PHP:Extension] cURL: %s\n".
					"[PHP:Extension] Session: %s\n".
					"[PHP:Extension] PCRE: %s\n".
					"[PHP:Extension] GD: %s\n".
					"[PHP:Extension] mbstring: %s\n".
					"[PHP:Extension] iconv: %s\n".
					"[PHP:Extension] XML: %s\n".
					"[PHP:Extension] SimpleXML: %s\n".
					"[PHP:Extension] DOM: %s\n".
					"[PHP:Extension] SPL: %s\n".
					"[PHP:Extension] ctype: %s\n".
					"[PHP:Extension] JSON: %s\n".
					"[PHP:Extension] YAML: %s\n".
					"[PHP:Extension] tidy: %s\n".
					"[PHP:Extension] XCache: %s\n".
					"[PHP:Extension] XDebug: %s\n".
					"[PHP:Extension] memcache: %s\n".
					"[PHP:Extension] memcached: %s\n".
					"[PHP:Extension] redis: %s\n".
					"\n",
					APP_VERSION,
					APP_BUILD,
					PLATFORM_BUILD,
					(DEVBLOCKS_REWRITE ? 'YES' : 'NO'),
					(APP_HOSTNAME ?: '(not set)'),
					substr(sprintf('%o', @fileperms(APP_STORAGE_PATH.'/attachments')), -4),
					substr(sprintf('%o', fileperms(APP_STORAGE_PATH.'/mail/new')), -4),
					substr(sprintf('%o', fileperms(APP_STORAGE_PATH.'/mail/fail')), -4),
					substr(sprintf('%o', fileperms(APP_TEMP_PATH)), -4),
					substr(sprintf('%o', fileperms(APP_SMARTY_COMPILE_PATH)), -4),
					substr(sprintf('%o', fileperms(APP_TEMP_PATH.'/cache')), -4),
					PHP_VERSION,
					PHP_OS . ' (' . php_uname() . ')',
					php_sapi_name(),
					mysqli_get_server_version($db->getMasterConnection()),
					ini_get('max_execution_time'),
					ini_get('memory_limit'),
					ini_get('file_uploads'),
					ini_get('upload_max_filesize'),
					ini_get('post_max_size'),
					(extension_loaded("mysqli") ? 'YES' : 'NO'),
					(extension_loaded("mysqlnd") ? 'YES' : 'NO'),
					(extension_loaded("mailparse") ? 'YES' : 'NO'),
					(extension_loaded("curl") ? 'YES' : 'NO'),
					(extension_loaded("session") ? 'YES' : 'NO'),
					(extension_loaded("pcre") ? 'YES' : 'NO'),
					(extension_loaded("gd") ? 'YES' : 'NO'),
					(extension_loaded("mbstring") ? 'YES' : 'NO'),
					(extension_loaded("iconv") ? 'YES' : 'NO'),
					(extension_loaded("xml") ? 'YES' : 'NO'),
					(extension_loaded("simplexml") ? 'YES' : 'NO'),
					(extension_loaded("dom") ? 'YES' : 'NO'),
					(extension_loaded("spl") ? 'YES' : 'NO'),
					(extension_loaded("ctype") ? 'YES' : 'NO'),
					(extension_loaded("json") ? 'YES' : 'NO'),
					(extension_loaded("yaml") ? 'YES' : 'NO'),
					(extension_loaded("tidy") ? 'YES' : 'NO'),
					(extension_loaded("xcache") ? 'YES' : 'NO'),
					(extension_loaded("xdebug") ? 'YES' : 'NO'),
					(extension_loaded("memcache") ? 'YES' : 'NO'),
					(extension_loaded("memcached") ? 'YES' : 'NO'),
					(extension_loaded("redis") ? 'YES' : 'NO')
				);
				
				if(is_array($tables) && !empty($tables)) {
					$report_output .= sprintf(
						"[Stats] # Workers: %s\n".
						"[Stats] # Groups: %s\n".
						"[Stats] # Tickets: %s\n".
						"[Stats] # Messages: %s\n".
						"\n".
						"[Database] Tables:\n",
						intval($db->GetOneMaster('SELECT count(id) FROM worker')),
						intval($db->GetOneMaster('SELECT count(id) FROM worker_group')),
						intval($db->GetOneMaster('SELECT count(id) FROM ticket')),
						intval($db->GetOneMaster('SELECT count(id) FROM message')),
						''
					);
					
					foreach($tables as $table_name => $table_data) {
						$report_output .= sprintf(" * %s - %s - %d records\n",
							$table_name,
							$table_data['Engine'],
							$table_data['Rows']
						);
					}
					
					$report_output .= "\n";
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
				
			default:
				$url_service = DevblocksPlatform::services()->url();
				
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
							</ul>
						</form>
					</body>
					</html>
					"
					,
					$url_service->write('c=debug&a=check'),
					$url_service->write('c=debug&a=report'),
					$url_service->write('c=debug&a=phpinfo')
				);
				break;
		}
		
		exit;
	}
};
