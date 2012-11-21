<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Refactor series datasources in workspace widgets

if(!isset($tables['workspace_widget'])) {
	$logger->error("The 'workspace_widget' table does not exist.");
	return FALSE;
}

$sql = "SELECT id, extension_id, params_json FROM workspace_widget";
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$changes = 0;
	
	$params = json_decode($row['params_json'], true);
	$params_orig = $params;
	
	switch($row['extension_id']) {
		
		// On charts, we want to default the datasource for each series 
		//  to 'worklist' if there is data
		case 'core.workspace.widget.chart':
			if(isset($params['chart_type']) && $params['chart_type'] == 'scatterplot') {
				// Move scatterplots out of charts and into their own extension
				$db->Execute(sprintf("UPDATE workspace_widget SET extension_id = %s WHERE id = %d",
					$db->qstr('core.workspace.widget.scatterplot'),
					$row['id']
				));
				$row['extension_id'] = 'core.workspace.widget.scatterplot';
			}
			
			if(isset($params['datasource'])) {
				unset($params['datasource']);
				$changes++;
			}
			
			// Loop through each series
			if(isset($params['series']))
			foreach($params['series'] as $series_idx => $series) {
				// Only if the widget hasn't been converted yet
				if(!isset($series['datasource'])) {
					
					// If this wasn't a worklist (empty series) blank it out
					if(!isset($series['view_context']) || empty($series['view_context'])) {
						//unset($params['series'][$series_idx]);
						
						$params['series'][$series_idx]['datasource'] = '';
						unset($params['series'][$series_idx]['view_context']);
						unset($params['series'][$series_idx]['view_id']);
						unset($params['series'][$series_idx]['view_model']);
						unset($params['series'][$series_idx]['xaxis_field']);
						unset($params['series'][$series_idx]['xaxis_tick']);
						unset($params['series'][$series_idx]['yaxis_func']);
						
					// Otherwise set it to a worklist
					} else if (isset($series['view_context']) && !empty($series['view_context'])) {
						$params['series'][$series_idx]['datasource'] = 'core.workspace.widget.datasource.worklist';
					}
					
					$changes++;
				}
			}
			break;
			
		// On counters, we want to migrate the built-in datasources to extensions
		case 'core.workspace.widget.counter':
			switch(@$params['datasource']) {
				// Remove pointless params
				case '':
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'sensor':
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['metric_value']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'worklist':
					if(isset($params['counter_func']))
						$params['metric_func'] = $params['counter_func'];
					
					if(isset($params['counter_field']))
						$params['metric_field'] = $params['counter_field'];
					
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['metric_value']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					$changes++;
					break;
					
				case 'url':
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['metric_value']);
					unset($params['sensor_id']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
				
			}
			
			switch(@$params['datasource']) {
				case '':
					$params['datasource'] = 'core.workspace.widget.datasource.manual';
					$changes++;
					break;
					
				case 'worklist':
					$params['datasource'] = 'core.workspace.widget.datasource.worklist';
					$changes++;
					break;
					
				case 'sensor':
					$params['datasource'] = 'cerberusweb.datacenter.sensor.widget.datasource';
					$changes++;
					break;
					
				case 'url':
					$params['datasource'] = 'core.workspace.widget.datasource.url';
					$changes++;
					break;
			}
			break;
			
		// On gauges, we want to migrate the built-in datasources to extensions
		case 'core.workspace.widget.gauge':
			switch(@$params['datasource']) {
				// Remove pointless params
				case '':
					unset($params['counter_func']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'sensor':
					unset($params['counter_func']);
					unset($params['metric_value']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'worklist':
					if(isset($params['needle_format']))
						$params['metric_type'] = $params['needle_format'];
					
					if(isset($params['needle_func']))
						$params['metric_func'] = $params['needle_func'];
					
					if(isset($params['needle_field']))
						$params['metric_field'] = $params['needle_field'];
					
					unset($params['metric_value']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					$changes++;
					break;
					
				case 'url':
					unset($params['counter_func']);
					unset($params['metric_value']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['sensor_id']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
				
			}
			
			switch(@$params['datasource']) {
				case '':
					$params['datasource'] = 'core.workspace.widget.datasource.manual';
					$changes++;
					break;
					
				case 'worklist':
					$params['datasource'] = 'core.workspace.widget.datasource.worklist';
					$changes++;
					break;
					
				case 'sensor':
					$params['datasource'] = 'cerberusweb.datacenter.sensor.widget.datasource';
					$changes++;
					break;
					
				case 'url':
					$params['datasource'] = 'core.workspace.widget.datasource.url';
					$changes++;
					break;
			}
			break;
	}
	
	if($changes) {
		$db->Execute(sprintf("UPDATE workspace_widget SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($params)),
			$row['id']
		));
	}
}

// ===========================================================================
// Add auth_extension_id to worker records

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(!isset($columns['auth_extension_id'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN auth_extension_id VARCHAR(255) NOT NULL DEFAULT ''");
	$db->Execute("UPDATE worker SET auth_extension_id='login.password'");
}

return TRUE;