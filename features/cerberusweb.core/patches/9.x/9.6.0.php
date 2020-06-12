<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Update package library

$packages = [
	'cerb_bot_behavior_auto_reply.json',
	'cerb_bot_behavior_form_interaction_worker.json',
	'cerb_connected_service_yahoo.json',
	'cerb_profile_widget_ticket_participants.json',
	'cerb_project_board_kanban.json',
	'cerb_workspace_page_reports.json',
	'cerb_workspace_tab_dashboard_with_filters.json',
	'cerb_workspace_widget_chart_sheet.json',
	'card_widget/cerb_card_widget_gpg_public_key_ascii.json',
	'card_widget/cerb_card_widget_gpg_public_key_subkeys.json',
	'card_widget/cerb_card_widget_gpg_public_key_uids.json',
	'card_widget/cerb_card_widget_snippet_content.json',
	'profile_widget/cerb_profile_widget_ticket_draft_interaction.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

// ===========================================================================
// Convert `custom_field_stringvalue.field_value` to utf8mb4

if(!isset($tables['custom_field_stringvalue']))
	return FALSE;

list($columns,) = $db->metaTable('custom_field_stringvalue');

if(!array_key_exists('field_value', $columns))
	return FALSE;

if('utf8_general_ci' == $columns['field_value']['collation']) {
	$db->ExecuteMaster("ALTER TABLE custom_field_stringvalue MODIFY COLUMN field_value varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE custom_field_stringvalue");
	$db->ExecuteMaster("OPTIMIZE TABLE custom_field_stringvalue");
}

// ===========================================================================
// Convert `custom_field_clobvalue.field_value` to utf8mb4

if(!isset($tables['custom_field_clobvalue']))
	return FALSE;

list($columns,) = $db->metaTable('custom_field_clobvalue');

if(!array_key_exists('field_value', $columns))
	return FALSE;

if('utf8_general_ci' == $columns['field_value']['collation']) {
	$db->ExecuteMaster("ALTER TABLE custom_field_clobvalue MODIFY COLUMN field_value MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE custom_field_clobvalue");
	$db->ExecuteMaster("OPTIMIZE TABLE custom_field_clobvalue");
}

// ===========================================================================
// Drop `mailbox.ssl_ignore_validation` and `mailbox.auth_disable_plain` bits

list($columns,) = $db->metaTable('mailbox');

if(array_key_exists('ssl_ignore_validation', $columns)) {
	$sql = "ALTER TABLE mailbox DROP COLUMN ssl_ignore_validation";
	$db->ExecuteMaster($sql);
}

if(array_key_exists('auth_disable_plain', $columns)) {
	$sql = "ALTER TABLE mailbox DROP COLUMN auth_disable_plain";
	$db->ExecuteMaster($sql);
}

if(!array_key_exists('connected_account_id', $columns)) {
	$sql = "ALTER TABLE mailbox ADD COLUMN connected_account_id INT UNSIGNED NOT NULL DEFAULT 0";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Convert dashboard filters to KATA

$cerb960_dashboardFilterYamlToKata = function($yaml_string) {
	$yaml = yaml_parse($yaml_string, -1);
	$kata = '';
	
	foreach($yaml as $obj) {
		$kata .= $obj['type'] . "/" . $obj['placeholder'] . ":\n";
		$kata .= "  label: " . $obj['label'] . "\n";
		
		if(array_key_exists('default', $obj))
			if(is_null($obj['default'])) {
				$kata .= "  default@json: null\n";
			} else if(is_string($obj['default'])) {
				$kata .= "  default: " . $obj['default'] . "\n";
			} else if(is_bool($obj['default'])) {
				$kata .= "  default@bool: " . ($obj['default'] ? 'yes' : 'no') . "\n";
			} else if(is_array($obj['default'])) {
				$kata .= "  default@json: " . json_encode($obj['default']) . "\n";
			}
		
		if(array_key_exists('params', $obj)) {
			$kata .= "  params:\n";
			
			foreach($obj['params'] as $k => $v) {
				if('picklist' == $obj['type'] && $k == 'options') {
					if(DevblocksPlatform::arrayIsIndexed($v)) {
						$kata .= "    " . $k . "@list:";
						if ($v) {
							$kata .= "\n      " . implode("\n      ", $v) . "\n";
						}
					} else {
						$kata .= "    " . $k . ":\n";
						foreach($v as $kk => $vv) {
							$kata .= "      " . $kk . ": " . $vv . "\n";
						}
					}
				} else if(is_null($v)) {
					$kata .= "    " . $k . "@json: null\n";
				} else if(is_string($v)) {
					$kata .= "    " . $k . ": " . $v . "\n";
				} elseif(is_bool($v)) {
					$kata .= "    " . $k . "@bool: ";
					$kata .= ($v ? 'yes' : 'no') . "\n";
				} elseif (is_array($v)) {
					$kata .= "    " . $k . "@json:\n";
					$kata .= "      " . json_encode($v) . "\n";
				}
			}
		}
		
		$kata .= "\n";
	}
	
	return $kata;
};

list($columns,) = $db->metaTable('workspace_tab');

$workspace_dashboards = $db->GetArrayMaster("SELECT id, params_json FROM workspace_tab WHERE extension_id = 'core.workspace.tab.dashboard' AND params_json LIKE '%placeholder_prompts%'");

foreach($workspace_dashboards as $workspace_dashboard) {
	$workspace_dashboard_params = json_decode($workspace_dashboard['params_json'], true);
	
	if(!array_key_exists('placeholder_prompts', $workspace_dashboard_params))
		continue;
	
	if($workspace_dashboard_params['placeholder_prompts']) {
		$kata = $cerb960_dashboardFilterYamlToKata($workspace_dashboard_params['placeholder_prompts']);
		
		$kata = $kata . "\n" . DevblocksPlatform::services()->string()->indentWith($workspace_dashboard_params['placeholder_prompts'], '#');
		
		$workspace_dashboard_params['prompts_kata'] = $kata;
	}
	
	unset($workspace_dashboard_params['placeholder_prompts']);
	
	$sql = sprintf("UPDATE workspace_tab SET params_json = %s WHERE id = %d",
		$db->qstr(json_encode($workspace_dashboard_params)),
		$workspace_dashboard['id']
	);
	
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Convert snippet prompted placeholders to KATA

$cerb960_snippetPromptJsonToKata = function($json_string) {
	$json = json_decode($json_string, true);
	$kata = '';
	
	$types = [
		'C' => 'checkbox',
		'D' => 'picklist',
		'S' => 'text',
		'T' => 'text',
	];
	
	foreach($json as $obj) {
		$new_prompt_type = $types[$obj['type']];
		
		$kata .= $new_prompt_type . "/" . $obj['key'] . ":\n";
		$kata .= "  label: " . $obj['label'] . "\n";
		
		if('T' == $obj['type']) {
			if (!array_key_exists('params', $obj))
				$obj['params'] = [
					'multiple' => true,
				];
		} elseif('checkbox' == $new_prompt_type) {
			if(array_key_exists('default', $obj)) {
				$obj['default'] = boolval($obj['default']);
			}
		}
		
		if(array_key_exists('default', $obj)) {
			if (is_null($obj['default'])) {
				$kata .= "  default@json: null\n";
			} else if (is_string($obj['default'])) {
				$kata .= "  default: " . $obj['default'] . "\n";
			} else if (is_bool($obj['default'])) {
				$kata .= "  default@bool: " . ($obj['default'] ? 'yes' : 'no') . "\n";
			} else if (is_array($obj['default'])) {
				$kata .= "  default@json: " . json_encode($obj['default']) . "\n";
			}
		}
		
		if(array_key_exists('params', $obj)) {
			$kata .= "  params:\n";
			
			foreach($obj['params'] as $k => $v) {
				if('picklist' == $new_prompt_type && $k == 'options') {
					if(DevblocksPlatform::arrayIsIndexed($v)) {
						$kata .= "    " . $k . "@list:";
						if ($v) {
							$kata .= "\n      " . implode("\n      ", $v) . "\n";
						}
					} else {
						$kata .= "    " . $k . ":\n";
						foreach($v as $kk => $vv) {
							$kata .= "      " . $kk . ": " . $vv . "\n";
						}
					}
				} else if(is_null($v)) {
					$kata .= "    " . $k . "@json: null\n";
				} else if(is_string($v)) {
					$kata .= "    " . $k . ": " . $v . "\n";
				} elseif(is_bool($v)) {
					$kata .= "    " . $k . "@bool: ";
					$kata .= ($v ? 'yes' : 'no') . "\n";
				} elseif (is_array($v)) {
					$kata .= "    " . $k . "@json:\n";
					$kata .= "      " . json_encode($v) . "\n";
				}
			}
		}
		
		$kata .= "\n";
	}
	
	return $kata;
};

list($columns,) = $db->metaTable('snippet');

if(!array_key_exists('prompts_kata', $columns)) {
	$db->ExecuteMaster("ALTER TABLE snippet ADD COLUMN prompts_kata mediumtext");
}

if(array_key_exists('custom_placeholders_json', $columns)) {
	$snippet_prompts = $db->GetArrayMaster("SELECT id, custom_placeholders_json FROM snippet WHERE custom_placeholders_json NOT IN ('','[]')");
	
	foreach ($snippet_prompts as $snippet) {
		$kata = $cerb960_snippetPromptJsonToKata($snippet['custom_placeholders_json']);
		
		/** @noinspection SqlResolve */
		$sql = sprintf("UPDATE snippet SET custom_placeholders_json = '', prompts_kata = %s WHERE id = %d",
			$db->qstr($kata),
			$snippet['id']
		);
		
		$db->ExecuteMaster($sql);
	}
	
	$db->ExecuteMaster("ALTER TABLE snippet DROP COLUMN custom_placeholders_json");
}

// ===========================================================================
// Sheet widget YAML to KATA

$cerb960_sheetWidgetYamlToKata = function($yaml_string) {
	if(false === (@$yaml = yaml_parse($yaml_string, 0)))
		return false;
	
	if(!is_array($yaml))
		return false;
	
	$kata = '';
	
	if(array_key_exists('layout', $yaml)) {
		$kata .= "layout:\n";
		
		foreach($yaml['layout'] as $obj_key => $obj) {
			if('style' == $obj_key) {
				$kata .= "  style: " . $obj . "\n";
			} else if('headings' == $obj_key) {
				$kata .= "  headings: " . ($obj ? 'yes' : 'no') . "\n";
			} else if('paging' == $obj_key) {
				$kata .= "  paging: " . ($obj ? 'yes' : 'no') . "\n";
			} else if('title_column' == $obj_key) {
				$kata .= "  title_column: " . $obj . "\n";
			} else if('selection' == $obj_key) {
				$kata .= "  selection:\n";
				
				if(array_key_exists('value_key', $obj))
					$kata .= "    value_key: " . $obj['value_key'] . "\n";
			}
		}
		
		$kata .= "\n";
	}
	
	if(array_key_exists('columns', $yaml)) {
		$kata .= "columns:\n";
		
		if(DevblocksPlatform::arrayIsIndexed($yaml['columns'])) {
			$is_old_style = true;
		} else {
			$is_old_style = false;
		}
		
		foreach($yaml['columns'] as $column_key => $column) {
			if(is_null($column))
				continue;
			
			if($is_old_style) {
				$obj_type = key($column);
				
				if(!(1 == count($column) && array_key_exists($obj_type, $column) && is_array($column[$obj_type])))
					continue;
				
				$obj = $column[$obj_type];
				
				$kata .= '  ' . $obj_type . "/" . $obj['key'] . ":\n";
				
			} else {
				$obj = $column;
				
				list($obj_type, $obj_key) = explode('/', $column_key, 2);
				
				$obj['key'] = $obj_key;
				
				$kata .= '  ' . $obj_type . "/" . $obj['key'] . ":\n";
			}
			
			if (array_key_exists('label', $obj))
				$kata .= "    label: " . $obj['label'] . "\n";
			
			if(array_key_exists('params', $obj) && $obj['params']) {
				$kata .= "    params:\n";
				
				foreach ($obj['params'] as $k => $v) {
					if (is_null($v)) {
						$kata .= "      " . $k . "@json: null\n";
					} else if (is_string($v)) {
						if(in_array($obj_type, ['card', 'text']) && in_array($k, ['label_template','id_template','context_template','value_template'])) {
							if (false !== strpos($v, "\n")) {
								$kata .= "      " . $k . "@raw:\n" .
									DevblocksPlatform::services()->string()->indentWith(rtrim($v), '        ') . "\n";
							} else {
								$kata .= "      " . $k . "@raw: " . $v . "\n";
							}

						} else {
							if (false !== strpos($v, "\n")) {
								$kata .= "      " . $k . "@text:\n" .
									DevblocksPlatform::services()->string()->indentWith(rtrim($v), '        ') . "\n";
							} else {
								$kata .= "      " . $k . ": " . $v . "\n";
							}
						}
					} elseif (is_bool($v)) {
						$kata .= "      " . $k . "@bool: ";
						$kata .= ($v ? 'yes' : 'no') . "\n";
					} elseif (is_array($v)) {
						$kata .= "      " . $k . "@json:\n";
						$kata .= "        " . json_encode($v) . "\n";
					}
				}
			}
			
			$kata .= "\n";
		}
	}
	
	return $kata;
};

// ===========================================================================
// Convert card sheet widgets to KATA

list($columns,) = $db->metaTable('card_widget');

$card_sheet_widgets = $db->GetArrayMaster("SELECT id, extension_params_json FROM card_widget WHERE extension_id = 'cerb.card.widget.sheet' AND (extension_params_json LIKE '%sheet_yaml%' OR extension_params_json LIKE '%placeholder_simulator_yaml%')");

foreach($card_sheet_widgets as $card_sheet_widget) {
	$card_sheet_widget_params = json_decode($card_sheet_widget['extension_params_json'], true);
	
	if(array_key_exists('sheet_yaml', $card_sheet_widget_params)) {
		$kata = $cerb960_sheetWidgetYamlToKata($card_sheet_widget_params['sheet_yaml']);
		
		$kata = $kata . "\n" . DevblocksPlatform::services()->string()->indentWith($card_sheet_widget_params['sheet_yaml'], '#');
		
		$card_sheet_widget_params['sheet_kata'] = $kata;
		
		unset($card_sheet_widget_params['sheet_yaml']);
	}
	
	if(array_key_exists('placeholder_simulator_yaml', $card_sheet_widget_params)) {
		$kata = $cerb960_sheetWidgetYamlToKata($card_sheet_widget_params['placeholder_simulator_yaml']);
		
		$kata = $kata . "\n" . DevblocksPlatform::services()->string()->indentWith($card_sheet_widget_params['placeholder_simulator_yaml'], '#');
		
		$card_sheet_widget_params['placeholder_simulator_kata'] = $kata;
		
		unset($card_sheet_widget_params['placeholder_simulator_yaml']);
	}
	
	$sql = sprintf("UPDATE card_widget SET extension_params_json = %s WHERE id = %d",
		$db->qstr(json_encode($card_sheet_widget_params)),
		$card_sheet_widget['id']
	);
	
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Convert profile sheet widgets to KATA

list($columns,) = $db->metaTable('profile_widget');

$profile_sheet_widgets = $db->GetArrayMaster("SELECT id, extension_params_json FROM profile_widget WHERE extension_id = 'cerb.profile.tab.widget.sheet' AND (extension_params_json LIKE '%sheet_yaml%' OR extension_params_json LIKE '%placeholder_simulator_yaml%')");

foreach($profile_sheet_widgets as $profile_sheet_widget) {
	$profile_sheet_widget_params = json_decode($profile_sheet_widget['extension_params_json'], true);
	
	if(array_key_exists('sheet_yaml', $profile_sheet_widget_params)) {
		$kata = $cerb960_sheetWidgetYamlToKata($profile_sheet_widget_params['sheet_yaml']);
		
		$kata = $kata . "\n" . DevblocksPlatform::services()->string()->indentWith($profile_sheet_widget_params['sheet_yaml'], '#');
		
		$profile_sheet_widget_params['sheet_kata'] = $kata;
		
		unset($profile_sheet_widget_params['sheet_yaml']);
	}
	
	if(array_key_exists('placeholder_simulator_yaml', $profile_sheet_widget_params)) {
		$kata = $cerb960_sheetWidgetYamlToKata($profile_sheet_widget_params['placeholder_simulator_yaml']);
		
		$kata = $kata . "\n" . DevblocksPlatform::services()->string()->indentWith($profile_sheet_widget_params['placeholder_simulator_yaml'], '#');
		
		$profile_sheet_widget_params['placeholder_simulator_kata'] = $kata;
		
		unset($profile_sheet_widget_params['placeholder_simulator_yaml']);
	}
	
	$sql = sprintf("UPDATE profile_widget SET extension_params_json = %s WHERE id = %d",
		$db->qstr(json_encode($profile_sheet_widget_params)),
		$profile_sheet_widget['id']
	);
	
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Convert workspace sheet widgets to KATA

list($columns,) = $db->metaTable('workspace_widget');

$workspace_sheet_widgets = $db->GetArrayMaster("SELECT id, params_json FROM workspace_widget WHERE extension_id = 'core.workspace.widget.sheet' AND (params_json LIKE '%sheet_yaml%' OR params_json LIKE '%placeholder_simulator_yaml%')");

foreach($workspace_sheet_widgets as $workspace_sheet_widget) {
	$workspace_sheet_widget_params = json_decode($workspace_sheet_widget['params_json'], true);
	
	if(array_key_exists('sheet_yaml', $workspace_sheet_widget_params)) {
		$kata = $cerb960_sheetWidgetYamlToKata($workspace_sheet_widget_params['sheet_yaml']);
		
		$kata = $kata . "\n" . DevblocksPlatform::services()->string()->indentWith($workspace_sheet_widget_params['sheet_yaml'], '#');
		
		$workspace_sheet_widget_params['sheet_kata'] = $kata;
		
		unset($workspace_sheet_widget_params['sheet_yaml']);
	}
	
	if(array_key_exists('placeholder_simulator_yaml', $workspace_sheet_widget_params)) {
		$kata = $cerb960_sheetWidgetYamlToKata($workspace_sheet_widget_params['placeholder_simulator_yaml']);
		
		$kata = $kata . "\n" . DevblocksPlatform::services()->string()->indentWith($workspace_sheet_widget_params['placeholder_simulator_yaml'], '#');
		
		$workspace_sheet_widget_params['placeholder_simulator_kata'] = $kata;
		
		unset($workspace_sheet_widget_params['placeholder_simulator_yaml']);
	}
	
	$sql = sprintf("UPDATE workspace_widget SET params_json = %s WHERE id = %d",
		$db->qstr(json_encode($workspace_sheet_widget_params)),
		$workspace_sheet_widget['id']
	);

	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return true;