<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupStorageContent extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'storage_content');
		
		// Scope
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('storage_engines', $storage_engines);

		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);

		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true);
		$tpl->assign('storage_schemas', $storage_schemas);
		
		// Totals
		
		$db = DevblocksPlatform::services()->database();
		
		if(false == ($rs = $db->ExecuteMaster("SHOW TABLE STATUS")))
			return false;

		$total_db_size = 0;
		$total_db_data = 0;
		$total_db_indexes = 0;
		$total_db_slack = 0;
		
		// [TODO] This would likely be helpful to the /debug controller
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$table_size_data = floatval($row['Data_length']);
			$table_size_indexes = floatval($row['Index_length']);
			$table_size_slack = floatval($row['Data_free']);
			
			$total_db_size += $table_size_data + $table_size_indexes;
			$total_db_data += $table_size_data;
			$total_db_indexes += $table_size_indexes;
			$total_db_slack += $table_size_slack;
		}
		
		mysqli_free_result($rs);
		
		$tpl->assign('total_db_size', $total_db_size);
		$tpl->assign('total_db_data', $total_db_data);
		$tpl->assign('total_db_indexes', $total_db_indexes);
		$tpl->assign('total_db_slack', $total_db_slack);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/index.tpl');
	}
	
	function showStorageSchemaAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('storage_engines', $storage_engines);
		
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		$tpl->assign('schema', $extension);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/rule.tpl');
	}
	
	function showStorageSchemaPeekAction() {
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		$tpl->assign('schema', $extension);
		
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/peek.tpl');
	}
	
	function saveStorageSchemaPeekAction() {
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		/* @var $extension Extension_DevblocksStorageSchema */
		$extension->saveConfig();
		
		$this->showStorageSchemaAction();
	}
}