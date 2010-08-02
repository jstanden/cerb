<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT UNIQUE
$tables_autoinc = array(
	'watcher_mail_filter',
);
foreach($tables_autoinc as $table) {
	if(!isset($tables[$table]))
		return FALSE;
	
	list($columns, $indexes) = $db->metaTable($table);
	if(isset($columns['id']) 
		&& ('int(10) unsigned' != $columns['id']['type'] 
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->Execute(sprintf("ALTER TABLE %s MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT", $table));
	}
}

// ===========================================================================
// Convert watcher_mail_filter actions from 'next_worker_id' to 'owner'

if(!isset($tables['watcher_mail_filter']))
	return FALSE;

$sql = "SELECT id, criteria_ser FROM watcher_mail_filter";
$rs = $db->Execute($sql);

if(false !== $rs)
while($row = mysql_fetch_assoc($rs)) {
	$filter_id = $row['id'];
	$filter_criteria_ser = $row['criteria_ser'];
	
	$filter_criteria = array();
	if(!empty($filter_criteria_ser))
		@$filter_criteria = unserialize($filter_criteria_ser);
		
	if(!empty($filter_criteria)) {
		if(isset($filter_criteria['next_worker_id'])) {
			@$worker_id = $filter_criteria['next_worker_id']['value'];
			
			if(!empty($worker_id)) {
				$filter_criteria['owner'] = array(
					'value'=>array($worker_id),
				);
			}
				
			unset($filter_criteria['next_worker_id']);
			
			$db->Execute(sprintf("UPDATE watcher_mail_filter SET criteria_ser = %s WHERE id = %d",
				$db->qstr(serialize($filter_criteria)),
				$filter_id
			));
		}
	}
}

mysql_free_result($rs);

return TRUE;