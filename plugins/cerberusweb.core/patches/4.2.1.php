<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2009, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db,'mysql'); /* @var $datadict ADODB2_mysql */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Fix ticket workspaces still using tm_id and tm_name (deprecated JOIN)

if(isset($tables['worker_workspace_list'])) {
	$sql = "SELECT id, list_view FROM worker_workspace_list WHERE source_extension = 'core.workspace.source.ticket'";
	$rs = $db->Execute($sql);
	
	while(!$rs->EOF) {
		if(!empty($rs->fields['list_view'])) {
			$updated_list_view = '';
			
			// Loop through view schemas (serialized) and replace tm_id||tm_name with t_team_id
			if(!empty($rs->fields['list_view'])) {
				$list_view = $rs->fields['list_view'];
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
					$rs->fields['id']
				);
				$db->Execute($sql);
			}
		}
		
		$rs->MoveNext();
	}
}

return TRUE;