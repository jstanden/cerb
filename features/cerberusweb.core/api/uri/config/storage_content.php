<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
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

class PageSection_SetupStorageContent extends Extension_PageSection {
	function render() {
		if(ONDEMAND_MODE)
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'storage_content');
		
		// Scope
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false, true);
		$tpl->assign('storage_engines', $storage_engines);

		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);

		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true, true);
		$tpl->assign('storage_schemas', $storage_schemas);
		
		// Totals
		
		$db = DevblocksPlatform::getDatabaseService();
		$rs = $db->Execute("SHOW TABLE STATUS");

		$total_db_size = 0;
		$total_db_data = 0;
		$total_db_indexes = 0;
		$total_db_slack = 0;
		
		// [TODO] This would likely be helpful to the /debug controller
		
		while($row = mysql_fetch_assoc($rs)) {
			$table_size_data = floatval($row['Data_length']);
			$table_size_indexes = floatval($row['Index_length']);
			$table_size_slack = floatval($row['Data_free']);
			
			$total_db_size += $table_size_data + $table_size_indexes;
			$total_db_data += $table_size_data;
			$total_db_indexes += $table_size_indexes;
			$total_db_slack += $table_size_slack;
		}
		
		mysql_free_result($rs);
		
		$tpl->assign('total_db_size', $total_db_size);
		$tpl->assign('total_db_data', $total_db_data);
		$tpl->assign('total_db_indexes', $total_db_indexes);
		$tpl->assign('total_db_slack', $total_db_slack);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/index.tpl');
	}
	
	function showStorageSchemaAction() {
		if(ONDEMAND_MODE)
			return;
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false, true);
		$tpl->assign('storage_engines', $storage_engines);
		
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		$tpl->assign('schema', $extension);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/rule.tpl');
	}
	
	function showStorageSchemaPeekAction() {
		if(ONDEMAND_MODE)
			return;
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		$tpl->assign('schema', $extension);
		
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/peek.tpl');
	}
	
	function saveStorageSchemaPeekAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(ONDEMAND_MODE)
			return;
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		/* @var $extension Extension_DevblocksStorageSchema */
		$extension->saveConfig();
		
		$this->showStorageSchemaAction();
	}
}