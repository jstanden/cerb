<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Expand project boards

list($columns,) = $db->metaTable('project_board');

if(!array_key_exists('cards_kata', $columns)) {
	$sql = "ALTER TABLE project_board ADD COLUMN cards_kata mediumtext";
	$db->ExecuteMaster($sql);
	
	$cards_kata = "automation/task_sheet:\n  uri: cerb:automation:cerb.projectBoard.card.sheet\n  disabled@bool:\n    {{card__context is not record type ('task')}}\n  inputs:\n    sheet:\n      columns:\n        card/card__label:\n          label: Record\n        text/card_status:\n          label: Status\n        slider/card_importance:\n          label: Importance\n        card/card_owner__label:\n          label: Owner\n          params:\n            image@bool: yes";
	
	$db->ExecuteMaster(sprintf("UPDATE project_board SET cards_kata = %s WHERE cards_kata IS NULL",
		$db->qstr($cards_kata)
	));
}

if(array_key_exists('params_json', $columns)) {
	$results = $db->ExecuteMaster("select id, params_json from project_board where params_json not in ('[]','{\"card_queries\":[],\"card_templates\":[]}')");
	
	foreach($results as $result) {
		$cards_kata = '';
		$params = json_decode($result['params_json'], true);
		
		if(array_key_exists('card_templates', $params) && $params['card_templates']) {
			$templates_yaml = DevblocksPlatform::services()->string()->yamlEmit($params['card_templates'], false);
			$templates_yaml = DevblocksPlatform::services()->string()->indentWith($templates_yaml, '# ');
			$templates_yaml = "# [TODO] Migrate to automations\n" . $templates_yaml;
			
			$sql = sprintf("UPDATE project_board SET cards_kata=CONCAT(%s,'\n\n',ifnull(cards_kata,'')) WHERE id = %d",
				$db->qstr($templates_yaml),
				$result['id']
			);
			
			$db->ExecuteMaster($sql);
		}
	}
	
	$db->ExecuteMaster("ALTER TABLE project_board DROP COLUMN params_json");
}

// ===========================================================================
// Expand project board columns

list($columns,) = $db->metaTable('project_board_column');

if(!isset($columns['cards_kata'])) {
	$sql = "ALTER TABLE project_board_column ADD COLUMN cards_kata mediumtext";
	$db->ExecuteMaster($sql);
	
	$cards_kata = "automation/done:\n  uri: cerb:automation:cerb.projectBoard.card.done";
	
	$db->ExecuteMaster(sprintf("UPDATE project_board_column SET cards_kata = %s WHERE name IN ('Completed','Completed!')",
		$db->qstr($cards_kata)
	));
}

if(!isset($columns['toolbar_kata'])) {
	$sql = "ALTER TABLE project_board_column ADD COLUMN toolbar_kata mediumtext";
	$db->ExecuteMaster($sql);
}

if(!isset($columns['functions_kata'])) {
	$sql = "ALTER TABLE project_board_column ADD COLUMN functions_kata mediumtext";
	$db->ExecuteMaster($sql);

	$boards = $db->GetArrayMaster("SELECT id, columns_json FROM project_board");
	$toolbar_kata = "interaction/add:\n  uri: cerb:automation:cerb.projectBoard.record.create\n  icon: circle-plus\n  inputs:\n    record_type: task\n    column: {{column_id}}\n\ninteraction/find:\n  uri: cerb:automation:cerb.projectBoard.toolbar.task.find\n  icon: search\n  inputs:\n    column: {{column_id}}";
	
	foreach($boards as $board) {
		$column_ids = json_decode($board['columns_json'] ?? '[]', true);
		
		if(!is_array($column_ids) || !$column_ids)
			continue;
		
		$first_column_id = current($column_ids);
		
		$db->ExecuteMaster(sprintf("UPDATE project_board_column SET toolbar_kata = %s WHERE id = %d",
			$db->qstr($toolbar_kata),
			$first_column_id
		));
	}
}

if(array_key_exists('params_json', $columns)) {
	$sql = "select id, params_json from project_board_column where params_json not in ('','[]','{\"behaviors\":[]}','{\"actions\":[],\"behaviors\":[]}')";
	$board_columns = $db->GetArrayMaster($sql);
	
	$status_map = [
		0 => 'open',
		1 => 'closed',
		2 => 'waiting',
	];
	
	foreach($board_columns as $board_column) {
		$functions_kata = '';
		
		$params = json_decode($board_column['params_json'], true);
		
		// Built-in actions
		if(array_key_exists('actions', $params)) {
			foreach($params['actions'] as $action_key => $action_params) {
				if('task_status' == $action_key) {
					@$status = $status_map[$action_params['status_id']];
					
					if(null !== $status) {
						$functions_kata .= sprintf("automation/%s:\n  uri: cerb:automation:cerb.projectBoard.action.task.close\n  disabled@bool:\n    {{card__context is not record type ('task')}}\n",
							uniqid(),
							$status
						);
					}
				}
			}
		}
		
		// Behaviors
		if(array_key_exists('behaviors', $params)) {
			foreach($params['behaviors'] as $behavior_id => $behavior_data) {
				$functions_kata .= sprintf("# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%d\n  disabled@bool: no\n",
					uniqid(),
					$behavior_id
				);
				
				if(is_array($behavior_data) && $behavior_data) {
					$functions_kata .= "  inputs:\n";
					
					foreach($behavior_data as $k => $v) {
						if(is_array($v)) {
							$functions_kata .= sprintf("    %s@list:\n      %s\n",
								$k,
								implode('\n      ', $v)
							);
							
						} else {
							$functions_kata .= sprintf("    %s: %s\n",
								$k,
								$v
							);
						}
					}
				}
				
				$functions_kata .= "\n";
			}
		}
		
		if($functions_kata) {
			$sql = sprintf("UPDATE project_board_column SET functions_kata = %s WHERE id = %d",
				$db->qstr($functions_kata),
				$board_column['id']
			);
			
			$db->ExecuteMaster($sql);
		}
	}
	
	$db->ExecuteMaster("ALTER TABLE project_board_column DROP COLUMN params_json");
}

return TRUE;