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
	}
	
	if($changes) {
		$db->Execute(sprintf("UPDATE workspace_widget SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($params)),
			$row['id']
		));
	}
}

return TRUE;