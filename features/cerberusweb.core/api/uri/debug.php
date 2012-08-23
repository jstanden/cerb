<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

//	    $cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
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
						<h1>Cerberus Helpdesk - Requirements Checker:</h1>
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
					echo '<span class="pass">Your server is compatible with Cerberus Helpdesk '.APP_VERSION.'!</span>';
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
					"[Cerberus Helpdesk] App Version: %s\n".
					"[Cerberus Helpdesk] App Build: %s\n".
					"[Cerberus Helpdesk] Devblocks Build: %s\n".
					"[Cerberus Helpdesk] URL-Rewrite: %s\n".
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
							<h1>Cerberus Helpdesk - Debug Report:</h1>
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

   	    		header("Content-type: text/xml");
   	    		$doc = new DOMDocument("1.0", "UTF-8");
   	    		$eAttendants =& $doc->createElement('attendants');
   	    		$doc->appendChild($eAttendants);   	    		

   	    		$workers = DAO_Worker::getAll();
   	    		
	    		foreach($event_mfts as $event_id => $event_mft) { /* @var $event_mft DevblocksExtensionManifest */
	    			$triggers = DAO_TriggerEvent::getByEvent($event_id);
	    			
	    			if(empty($triggers))
	    				continue;

	    			// New event element
	    			$eEvent =& $doc->createElement("event");
	    			$eEvent->setAttribute("point", $event_id);
	    			$eEvent->setAttribute("label", $event_mft->name);
	    			$eAttendants->appendChild($eEvent);

	    			$event = $event_mft->createInstance(); /* @var $event Extension_DevblocksEvent */
	    			
	    			// Behaviors
		    		foreach($triggers as $trigger) { /* @var $trigger Model_TriggerEvent */
	    				$event_conditions = $event->getConditions($trigger);
	    				$event_actions = $event->getActions($trigger);
	    			
		    			$eBehavior =& $doc->createElement("behavior");
		    			$eBehavior->setAttribute("label", $trigger->title);
		    			$eEvent->appendChild($eBehavior);
		    			
		    			// Owner
		    			$eBehaviorOwner =& $doc->createElement("owner");
		    			$eBehaviorOwner->setAttribute("context", $trigger->owner_context);
		    			$eBehaviorOwner->setAttribute("context_id", $trigger->owner_context_id);
		    			$eBehavior->appendChild($eBehaviorOwner);

		    			// Variables
		    			if(!empty($trigger->variables)) {
		    				$eBehaviorVariables =& $doc->createElement("variables");
		    				$eBehavior->appendChild($eBehaviorVariables);
		    				
			    			foreach($trigger->variables as $var) {
			    				$eVariable =& $doc->createElement("variable");
			    				$eVariable->setAttribute("key", $var['key']);
			    				$eVariable->setAttribute("label", $var['label']);
			    				$eVariable->setAttribute("type", $var['type']);
			    				$eBehaviorVariables->appendChild($eVariable);
			    			}
		    			}
		    			
		    			$context = Extension_DevblocksContext::get($trigger->owner_context); /* @var $context Extension_DevblocksContext */
		    			if(!empty($context)) {
			    			$meta = $context->getMeta($trigger->owner_context_id);
			    			$txt = $doc->createTextNode(sprintf("%s (%s)",
			    				$meta['name'],
			    				$context->manifest->name
			    			));
			    			$eBehaviorOwner->appendChild($txt);
		    			}
		    			
		    			$data = $trigger->getDecisionTreeData();
	    				$nodes = $data['nodes'];
	    				$tree = $data['tree'];
	    				$depths = $data['depths'];
		    			
	    				if(empty($nodes))
	    					continue;
	    				
	    				$index = array();
		    			foreach($nodes as $idx => $node) { /* @var $node Model_DecisionNode */
		    				$node_type = $node->node_type;
		    				
		    				switch($node_type) {
		    					case 'action':
		    						$node_type = 'actions';
		    						break;
		    				}
		    				
							$eNode =& $doc->createElement($node_type);
							$eNode->setAttribute('label', $node->title);
							
		    				$params = $node->params;

		    				switch($node->node_type) {
		    					case 'switch':
		    						break;
		    						
		    					case 'outcome':
		    						// Groups
		    						if(isset($params['groups']))
		    						foreach($params['groups'] as $group) {
	    								$is_any = $group['any'];
	    								
	    								$eConditions =& $doc->createElement("conditions");
	    								$eConditions->setAttribute("scope", ($is_any?"any":"all"));
	    								$eNode->appendChild($eConditions);
	    								
	    								$conditions = $group['conditions'];
	    								
		    							foreach($conditions as $values) {
		    								if(!isset($values['condition']))
		    									continue;
		    								
		    								$eCondition =& $doc->createElement("condition");
		    								$eConditions->appendChild($eCondition);
		    								
	    									$condition_key = $values['condition'];
	    									unset($values['condition']);
	    									$eCondition->setAttribute("key", $condition_key);
	    									
		    								if(isset($event_conditions[$condition_key]))
		    									$eCondition->setAttribute('label', $event_conditions[$condition_key]['label']);
		    								
			    							foreach($values as $k => $v) {
			    								$eParam =& $doc->createElement("param");
			    								$eParam->setAttribute("key", $k);
			    								
			    								if(!is_array($v))
			    									$v = array($v);
			    								
		    									foreach($v as $iter_v) {
													switch($k) {
			    										case 'day':
			    											$days = array(
			    												0 => 'Sun',
			    												1 => 'Mon',
			    												2 => 'Tue',
			    												3 => 'Wed',
			    												4 => 'Thu',
			    												5 => 'Fri',
			    												6 => 'Sat',
			    											);
			    											$eParam->appendChild($doc->createComment($days[$iter_v]));
			    											break;
			    									}
		    										
		    										$eValue =& $doc->createElement("value");
			    									$eValue->appendChild($doc->createTextNode($iter_v));
			    									$eParam->appendChild($eValue);
		    									}
			    									
			    								$eCondition->appendChild($eParam);
			    							}
		    							}
		    						}
		    						break;
		    						
		    					case 'action':
		    						if(isset($params['actions'])) {
			    						foreach($params['actions'] as $values) {
			    							if(!isset($values['action']))
			    								continue;
			    							
		    								$eAction =& $doc->createElement("action");
		    								$eNode->appendChild($eAction);
		    								
	    									$action_key = $values['action'];
	    									unset($values['action']);
	    									$eAction->setAttribute("key", $action_key);
	    									
		    								if(isset($event_actions[$action_key]))
		    									$eAction->setAttribute('label', $event_actions[$action_key]['label']);
		    								
			    							foreach($values as $k => $v) {
			    								$eParam =& $doc->createElement("param");
			    								$eParam->setAttribute("key", $k);
			    								
			    								if(!is_array($v))
			    									$v = array($v);
			    								
		    									foreach($v as $iter_v) {
			    									switch($k) {
			    										case 'worker_id':
				    										if(isset($workers[$iter_v])) {
				    											$eParam->appendChild($doc->createComment($workers[$iter_v]->getName()));
				    										}
				    										break;
			    									}
			    									
		    										$eValue =& $doc->createElement("value");
			    									$eValue->appendChild($doc->createTextNode($iter_v));
			    									$eParam->appendChild($eValue);
			    									
		    									}
			    								
			    								$eAction->appendChild($eParam);
			    							}
			    						}
		    						}
		    						break;
		    				}
		    				
							$index[$idx] = $eNode;
							
		    			} // end nodes
		    			
		    			foreach($tree as $parent_id => $children) {
		    				foreach($children as $child_id) {
		    					$eChild =& $index[$child_id];
		    					
		    					if(empty($parent_id)) {
		    						$eBehavior->appendChild($eChild);
		    					} else {
		    						$eParent = $index[$parent_id];
		    						$eParent->appendChild($eChild);
		    					}
		    				}
		    			}	    			
		    			
		    		} // end behaviors
		    		
	    		} // end events

   	    		echo $doc->saveXML();
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
							<h1>Cerberus Helpdesk - Debug Menu:</h1>
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
