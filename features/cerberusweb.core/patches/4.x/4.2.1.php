<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Fix ticket workspaces still using tm_id and tm_name (deprecated JOIN)

if(isset($tables['worker_workspace_list'])) {
	$sql = "SELECT id, list_view FROM worker_workspace_list WHERE source_extension = 'core.workspace.source.ticket'";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		if(!empty($row['list_view'])) {
			$updated_list_view = '';
			
			// Loop through view schemas (serialized) and replace tm_id||tm_name with t_team_id
			if(!empty($row['list_view'])) {
				$list_view = $row['list_view'];
				$updated_list_view = str_replace(
					array(
						's:5:"tm_id"',
						's:7:"tm_name"',
					),
					's:9:"t_team_id"',
					$list_view
				);
			}
			
			// Checksum and replace in DB if changed
			if(!empty($updated_list_view) && 0 != strcmp(md5($updated_list_view), md5($list_view))) {
				$sql = sprintf("UPDATE worker_workspace_list SET list_view = %s WHERE id = %d",
					$db->qstr($updated_list_view),
					$row['id']
				);
				$db->Execute($sql);
			}
		}
	}
	
	mysql_free_result($rs);
}

// ===========================================================================
// Expand ticket.mask max size from 16 to 32

if(isset($tables['ticket'])) {
	list($columns, $indexes) = $db->metaTable('ticket');
	
	if(isset($columns['mask'])) {
		$sql = sprintf("ALTER TABLE ticket CHANGE COLUMN mask mask varchar(32) DEFAULT '' NOT NULL");
		$db->Execute($sql);
	}
}

return TRUE;