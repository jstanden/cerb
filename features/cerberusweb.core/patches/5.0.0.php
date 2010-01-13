<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
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
// Hand 'setting' over to 'devblocks_setting' (and copy)

if(isset($tables['setting']) && isset($tables['devblocks_setting'])) {
	$sql = "INSERT INTO devblocks_setting (plugin_id, setting, value) ".
		"SELECT 'cerberusweb.core', setting, value FROM setting";
	$db->Execute($sql);
	
	$sql = $datadict->DropTableSQL('setting');
	$datadict->ExecuteSQLArray($sql);
	
    unset($tables['setting']);
}
