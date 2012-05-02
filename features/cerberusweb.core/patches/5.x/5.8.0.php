<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Calendar Recurring Events

if(!isset($tables['calendar_recurring_profile'])) {
	$sql = sprintf("CREATE TABLE IF NOT EXISTS calendar_recurring_profile (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		event_name VARCHAR(255) NOT NULL DEFAULT '',
		owner_context VARCHAR(255) NOT NULL DEFAULT '',
		owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		is_available TINYINT UNSIGNED NOT NULL DEFAULT 0,
		date_start INT UNSIGNED NOT NULL DEFAULT 0,
		date_end INT UNSIGNED NOT NULL DEFAULT 0,
		params_json TEXT,
		PRIMARY KEY (id),
		INDEX owner (owner_context, owner_context_id)
	) ENGINE=%s", APP_DB_ENGINE);
	
	if(false === $db->Execute($sql))
		return FALSE;
		
	$tables['calendar_recurring_profile'] = 'calendar_recurring_profile';
}

// ===========================================================================
// Calendar Events

if(!isset($tables['calendar_event'])) {
	$sql = sprintf("CREATE TABLE IF NOT EXISTS calendar_event (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		owner_context VARCHAR(255) NOT NULL DEFAULT '',
		owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		name VARCHAR(255) NOT NULL DEFAULT '',
		recurring_id INT UNSIGNED NOT NULL DEFAULT 0,
		is_available TINYINT UNSIGNED NOT NULL DEFAULT 0,
		date_start INT UNSIGNED NOT NULL DEFAULT 0,
		date_end INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX owner (owner_context, owner_context_id),
		INDEX (recurring_id),
		INDEX (is_available),
		INDEX (date_start),
		INDEX (date_end)
	) ENGINE=%s", APP_DB_ENGINE);
	
	if(false === $db->Execute($sql))
		return FALSE;
		
	$tables['calendar_event'] = 'calendar_event';
}

// ===========================================================================
// Add placeholder columns to worker_view_model

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(!isset($columns['placeholder_labels_json'])) {
	$db->Execute("ALTER TABLE worker_view_model ADD COLUMN placeholder_labels_json TEXT");
}

if(!isset($columns['placeholder_values_json'])) {
	$db->Execute("ALTER TABLE worker_view_model ADD COLUMN placeholder_values_json TEXT");
}

// ===========================================================================
// Add recurring to context_scheduled_behavior

if(!isset($tables['context_scheduled_behavior'])) {
	$logger->error("The 'context_scheduled_behavior' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('context_scheduled_behavior');

if(!isset($columns['run_relative'])) {
	$db->Execute("ALTER TABLE context_scheduled_behavior ADD COLUMN run_relative VARCHAR(255) NOT NULL DEFAULT ''");
}

if(!isset($columns['run_literal'])) {
	$db->Execute("ALTER TABLE context_scheduled_behavior ADD COLUMN run_literal VARCHAR(255) NOT NULL DEFAULT ''");
}

if(!isset($columns['repeat_json'])) {
	$db->Execute("ALTER TABLE context_scheduled_behavior ADD COLUMN repeat_json TEXT");
}

// ===========================================================================
// Add 'num_messages' to tickets

if(!isset($tables['ticket'])) {
	$logger->error("The 'ticket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('ticket');

if(!isset($columns['num_messages'])) {
	$db->Execute("ALTER TABLE ticket ADD COLUMN num_messages INT UNSIGNED NOT NULL DEFAULT 0"); // ~13.5s
	$db->Execute("CREATE TEMPORARY TABLE _tmp_ticket_msgcount SELECT ticket_id, count(id) AS hits FROM message GROUP BY ticket_id"); // ~0.94s
	$db->Execute("UPDATE ticket INNER JOIN _tmp_ticket_msgcount ON (ticket.id=_tmp_ticket_msgcount.ticket_id) SET ticket.num_messages=_tmp_ticket_msgcount.hits"); // ~5.93s
	$db->Execute("DROP TABLE _tmp_ticket_msgcount");
}

// ===========================================================================
// Define a reusable function for converting to ctx:// URLs

function upgrade_580_convert_to_ctx_url($url) {
	if(empty($url))
		return null;
		
	$url_parts = explode('/', $url);
	$friendly = '';
		
	switch(@$url_parts[0]) {
		case 'calls':
			$id = intval(@$url_parts[1]);
				
			$url = sprintf("ctx://%s:%d",
				'cerberusweb.contexts.call',
				$id
			);
				
			$friendly = substr(@$url_parts[1], strlen($id)+1);
			break;
	
		case 'contacts':
			switch(@$url_parts[1]) {
				case 'addresses':
					if(@$url_parts[2] == 'display') {
						$id = intval(@$url_parts[3]);
	
						$url = sprintf("ctx://%s:%d",
							'cerberusweb.contexts.address',
							$id
						);
	
						$friendly = substr(@$url_parts[3], strlen($id)+1);
					}
					break;
						
				case 'orgs':
					if(@$url_parts[2] == 'display') {
						$id = intval(@$url_parts[3]);
	
						$url = sprintf("ctx://%s:%d",
							'cerberusweb.contexts.org',
							$id
						);
	
						$friendly = substr(@$url_parts[3], strlen($id)+1);
					}
					break;
						
				case 'people':
					$id = intval(@$url_parts[2]);
						
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.contact_person',
						$id
					);
						
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
			}
			break;
				
		case 'cerb5_licenses':
			if(@$url_parts[1] == 'display') {
				$id = intval(@$url_parts[2]);
	
				$url = sprintf("ctx://%s:%d",
					'wgm.cerb5_licensing.contexts.license',
					$id
				);
	
				$friendly = substr(@$url_parts[2], strlen($id)+1);
			}
			break;
				
		case 'crm':
			if(@$url_parts[1] == 'opps') {
				$id = intval(@$url_parts[2]);
	
				$url = sprintf("ctx://%s:%d",
					'cerberusweb.contexts.opportunity',
					$id
				);
	
				$friendly = substr(@$url_parts[2], strlen($id)+1);
			}
			break;
				
		case 'datacenter':
			if(@$url_parts[1] == 'server') {
				$id = intval(@$url_parts[2]);
	
				$url = sprintf("ctx://%s:%d",
					'cerberusweb.contexts.datacenter.server',
					$id
				);
	
				$friendly = substr(@$url_parts[2], strlen($id)+1);
	
			} elseif(@$url_parts[1] == 'sensors') {
				if(@$url_parts[2] == 'profile') {
					$id = intval(@$url_parts[3]);
	
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.datacenter.sensor',
						$id
					);
	
					$friendly = substr(@$url_parts[3], strlen($id)+1);
				}
			}
	
			break;
				
		case 'datacenter.domains':
			if(@$url_parts[1] == 'domain') {
				$id = intval(@$url_parts[2]);
	
				$url = sprintf("ctx://%s:%d",
					'cerberusweb.contexts.datacenter.domain',
					$id
				);
	
				$friendly = substr(@$url_parts[2], strlen($id)+1);
			}
			break;
				
		case 'display':
			$id = 0;
				
			if(isset($url_parts[1])) {
				if('orgs' == $url_parts[1]) {
					$id = intval(@$url_parts[3]);
						
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.org',
						$id
					);
						
					$friendly = substr(@$url_parts[3], strlen($id)+1);
						
				} elseif(is_numeric($url_parts[1])) {
					$id = intval($url_parts[1]);
						
				} else {
					$id = $url_parts[1];
				}
			}
				
			if(empty($id)) {
				$url = '';
	
			} else {
				$url = sprintf("ctx://%s:%s",
					'cerberusweb.contexts.ticket',
					$id
				);
			}
				
			break;
				
		case 'feeds':
			if(@$url_parts[1] == 'item') {
				$id = intval(@$url_parts[2]);
	
				$url = sprintf("ctx://%s:%d",
					'cerberusweb.contexts.feed.item',
					$id
				);
	
				$friendly = substr(@$url_parts[2], strlen($id)+1);
			}
			break;
				
		case 'profiles':
			switch(@$url_parts[1]) {
				case 'address':
					$id = intval(@$url_parts[2]);
	
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.address',
						$id
					);
	
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
						
				case 'calendar_event':
					$id = intval(@$url_parts[2]);
	
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.calendar_event',
						$id
					);
						
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
	
				case 'call':
					$id = intval(@$url_parts[2]);
	
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.call',
						$id
					);
						
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
	
				case 'cerb5_license':
					$id = intval(@$url_parts[2]);
	
					$url = sprintf("ctx://%s:%d",
						'wgm.cerb5_licensing.contexts.license',
						$id
					);
						
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
	
				case 'domain':
					$id = intval(@$url_parts[2]);
	
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.datacenter.domain',
						$id
					);
	
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
	
				case 'feed':
					$id = intval(@$url_parts[2]);
	
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.feed',
						$id
					);
						
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
	
				case 'group':
					$id = intval(@$url_parts[2]);
	
					$url = sprintf("ctx://%s:%d",
						'cerberusweb.contexts.group',
						$id
					);
	
					$friendly = substr(@$url_parts[2], strlen($id)+1);
					break;
	
				case 'worker':
					$id = intval(@$url_parts[2]);
	
					if(!empty($id)) {
						$url = sprintf("ctx://%s:%d",
							'cerberusweb.contexts.worker',
							$id
						);
		
						$friendly = substr(@$url_parts[2], strlen($id)+1);
					}
					break;
			}
			break;
				
		case 'tasks':
			if(@$url_parts[1] == 'display') {
				$id = intval(@$url_parts[2]);
	
				$url = sprintf("ctx://%s:%d",
					'cerberusweb.contexts.task',
					$id
				);
	
				$friendly = substr(@$url_parts[2], strlen($id)+1);
			}
			break;
				
		case 'timetracking':
			if(@$url_parts[1] == 'display') {
				$id = intval(@$url_parts[2]);
	
				$url = sprintf("ctx://%s:%d",
					'cerberusweb.contexts.timetracking',
					$id
				);
	
				$friendly = substr(@$url_parts[2], strlen($id)+1);
			}
			break;
	}
		
	if(!empty($friendly))
		$url .= '/' . $friendly;

	return $url;
}

// ===========================================================================
// Update `context_activity_log` URLs to ctx:// format

if(!isset($tables['context_activity_log'])) {
	$logger->error("The 'context_activity_log' table does not exist.");
	return FALSE;
}

$url_writer = DevblocksPlatform::getUrlService();
$url_prefix = $url_writer->write('', true, false);

$sql = sprintf("SELECT id, activity_point, actor_context, actor_context_id, entry_json FROM context_activity_log");
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$entry = json_decode($row['entry_json'], true);
	
	// Replace URLs
	if(isset($entry['urls'])) {
		$changed = false;
		
		foreach($entry['urls'] as $k => $v) {
			$url = null;
			
			if(empty($v))
				continue;			
			
			if(substr($v,0,6) == 'ctx://')
				continue;
			
			// Fix the issue with SC links in activity log 
			if(
				$row['activity_point'] == 'ticket.message.inbound'
				&& $row['actor_context'] == 'cerberusweb.contexts.address'
			) {
				$old_url = $v;
				$v = "c=contacts&a=addresses&p=display&id=";
				
				$find = '/' . $row['actor_context_id'] . '-';
				if(false !== ($pos = strpos($old_url, $find))) {
					$v .= substr($old_url,$pos+1);
				} else {
					$v .= $row['actor_context_id'];
				}
			}
			
			if(substr($v,0,2) == 'c=') {
				$v = $url_writer->write($v, true, false);
			}
			
			if(0 == strcasecmp(substr($v,0,strlen($url_prefix)), $url_prefix)) {
				$url = substr($v, strlen($url_prefix));
				
			} elseif(0 == strcasecmp(substr($v,0,4), 'http')) {
				$url_split = explode('/', $v, 4);
				$url = $url_split[3];
			}

			$url = upgrade_580_convert_to_ctx_url($url);
			
			if(substr($url,0,6) == 'ctx://') {
				$entry['urls'][$k] = $url;
				$changed = true;
			}
		}
		
		if($changed) {
			$sql = sprintf("UPDATE context_activity_log SET entry_json = %s WHERE id = %d",
				$db->qstr(json_encode($entry)),
				$row['id']
			);
			$db->Execute($sql);
		}
	}
}

mysql_free_result($rs);

// ===========================================================================
// Update `notification` URLs to ctx:// format

if(!isset($tables['notification'])) {
	$logger->error("The 'notification' table does not exist.");
	return FALSE;
}

$url_writer = DevblocksPlatform::getUrlService();
$url_prefix = $url_writer->write('', true, false);

$sql = sprintf("SELECT id, context, context_id, url FROM notification");
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$context = $row['context'];
	$context_id = $row['context_id'];
	$url = $row['url'];

	if(substr($url,0,6) == 'ctx://')
		continue;
	
	if(empty($url)) {
		$url = sprintf("ctx://%s:%d",
			$context,
			$context_id
		);
	}
		
	if(0 == strcasecmp(substr($url,0,strlen($url_prefix)), $url_prefix)) {
		$url = substr($url, strlen($url_prefix));
	
	} elseif(0 == strcasecmp(substr($url,0,4), 'http')) {
		$url_split = explode('/', $url, 4);
		$url = $url_split[3];
	}
	
	$url = upgrade_580_convert_to_ctx_url($url);
	
	if(substr($url,0,6) == 'ctx://') {
		$sql = sprintf("UPDATE notification SET url = %s WHERE id = %d",
			$db->qstr($url),
			$row['id']
		);
		$db->Execute($sql);
	}
}

mysql_free_result($rs);

return TRUE;