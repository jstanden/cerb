<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Replace org.merge with record.merge in activity log

$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'activities.org.merge', 'activities.record.merge') WHERE activity_point = 'org.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = replace(entry_json, 'variables\":{', 'variables\":{\"context\":\"cerberusweb.contexts.org\",\"context_label\":\"organization\",') WHERE activity_point = 'org.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET activity_point = 'record.merge' WHERE activity_point = 'org.merge'");

// ===========================================================================
// Replace ticket.merge with record.merge in activity log

$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'activities.ticket.merge', 'activities.record.merge') WHERE activity_point = 'ticket.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = replace(entry_json, 'variables\":{', 'variables\":{\"context\":\"cerberusweb.contexts.ticket\",\"context_label\":\"ticket\",') WHERE activity_point = 'ticket.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET activity_point = 'record.merge' WHERE activity_point = 'ticket.merge'");

// ===========================================================================
// Clear empty email addresses

$db->ExecuteMaster("DELETE FROM address WHERE email = ''");

// ===========================================================================
// Add `updated_at` field to the `custom_fieldset` table

if(!isset($tables['custom_fieldset'])) {
	$logger->error("The 'custom_fieldset' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('custom_fieldset');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE custom_fieldset ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE custom_fieldset SET updated_at = %d", time()));
}

// ===========================================================================
// Add `updated_at` field to the `workspace_page` table

if(!isset($tables['workspace_page'])) {
	$logger->error("The 'workspace_page' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('workspace_page');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_page ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_page SET updated_at = %d", time()));
}

// ===========================================================================
// Add `updated_at` field to the `workspace_tab` table

if(!isset($tables['workspace_tab'])) {
	$logger->error("The 'workspace_tab' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('workspace_tab');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_tab ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_tab SET updated_at = %d", time()));
}

// ===========================================================================
// Add `updated_at` field to the `workspace_widget` table

if(!isset($tables['workspace_widget'])) {
	$logger->error("The 'workspace_widget' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('workspace_widget');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_widget ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_widget SET updated_at = %d", time()));
}

// ===========================================================================
// Migrate `workspace_list` serialized content to field values

if(!isset($tables['workspace_list'])) {
	$logger->error("The 'workspace_list' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('workspace_list');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_list ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_list SET updated_at = %d", time()));
}

if(!isset($columns['workspace_tab_pos']) && isset($columns['list_pos'])) {
	$sql = 'ALTER TABLE workspace_list CHANGE COLUMN list_pos workspace_tab_pos smallint(5) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_list SET updated_at = %d", time()));
}

$changes = [];

if(!isset($columns['name'])) {
	$changes[] = "ADD COLUMN name varchar(255) NOT NULL DEFAULT '' AFTER id";
	$changes[] = "ADD INDEX name (name(4))";
}

if(!isset($columns['options_json'])) {
	$changes[] = "ADD COLUMN options_json text";
}

if(!isset($columns['columns_json'])) {
	$changes[] = "ADD COLUMN columns_json text";
}

if(!isset($columns['params_editable_json'])) {
	$changes[] = "ADD COLUMN params_editable_json text";
}

if(!isset($columns['params_required_json'])) {
	$changes[] = "ADD COLUMN params_required_json text";
}

if(!isset($columns['params_required_query'])) {
	$changes[] = "ADD COLUMN params_required_query text";
}

if(!isset($columns['render_limit'])) {
	$changes[] = "ADD COLUMN render_limit smallint(5) unsigned not null default 0";
}

if(!isset($columns['render_subtotals'])) {
	$changes[] = "ADD COLUMN render_subtotals varchar(255) not null default ''";
}

if(!isset($columns['render_sort_json'])) {
	$changes[] = "ADD COLUMN render_sort_json varchar(255) not null default ''";
}

if(!empty($changes)) {
	$sql = "ALTER TABLE workspace_list " . implode(', ', $changes);
	if(!$db->ExecuteMaster($sql)) {
		echo $db->ErrorMsg();
		return false;
	}
}

if(isset($columns['list_view'])) {
	if(!class_exists('Model_WorkspaceListView', false)) {
		class Model_WorkspaceListView {
			public $title = 'New List';
			public $options = [];
			public $columns = [];
			public $num_rows = 10;
			public $params = [];
			public $params_required = [];
			public $sort_by = null;
			public $sort_asc = 1;
			public $subtotals = '';
		};
	}
	
	$sql = "SELECT id, list_view, context FROM workspace_list";
	$rs = $db->ExecuteMaster($sql);
	
	while($result = mysqli_fetch_assoc($rs)) {
		$list_view = unserialize($result['list_view']); /* @var $list_view Model_WorkspaceListView */
		
		if(!$list_view)
			continue;
		
		$view_id = 'cust_' . $result['id'];
		
		if(null == ($ext = Extension_DevblocksContext::get($result['context'])))
			continue;
		
		$view = $ext->getChooserView($view_id);  /* @var $view C4_AbstractView */
		
		if(empty($view))
			continue;
		
		$view->name = $list_view->title;
		$view->renderLimit = $list_view->num_rows;
		$view->renderPage = 0;
		$view->is_ephemeral = 0;
		$view->view_columns = $list_view->columns;
		if(property_exists($list_view, 'columns_hidden'))
			$view->addColumnsHidden($list_view->columns_hidden, true);
		$view->addParams($list_view->params, true);
		if(property_exists($list_view, 'params_required'))
			$view->addParamsRequired($list_view->params_required, true);
		if(property_exists($list_view, 'params_default'))
			$view->addParamsDefault($list_view->params_default, true);
		if(property_exists($list_view, 'params_hidden'))
			$view->addParamsHidden($list_view->params_hidden, true);
		$view->renderSortBy = $list_view->sort_by;
		$view->renderSortAsc = $list_view->sort_asc;
		$view->options = $list_view->options;
		
		$sort_json = $view->getSorts();
		
		$sql = sprintf("UPDATE workspace_list SET ".
			"name = %s, ".
			"options_json = %s, ".
			"columns_json = %s, ".
			"params_editable_json = %s, ".
			"params_required_json = %s, ".
			"render_limit = %d, ".
			"render_subtotals = %s, ".
			"render_sort_json = %s ".
			"WHERE id = %d",
			$db->qstr($view->name),
			$db->qstr(json_encode($view->options)),
			$db->qstr(json_encode($view->view_columns)),
			$db->qstr(json_encode($view->getParams(false))),
			$db->qstr(json_encode($view->getParamsRequired())),
			$view->renderLimit,
			$db->qstr($view->renderSubtotals),
			$db->qstr(json_encode($sort_json)),
			$result['id']
		);
		
		$db->ExecuteMaster($sql);
	}
	
	$db->ExecuteMaster("ALTER TABLE workspace_list DROP COLUMN list_view");
}

// ===========================================================================
// Migrate dashboard widgets using 'core.workspace.widget.datasource.worklist' to metric/series

$db->ExecuteMaster("UPDATE workspace_widget SET params_json = REPLACE(params_json,'\"core.workspace.widget.datasource.worklist\"','\"core.workspace.widget.datasource.worklist.series\"') WHERE params_json LIKE '%\"core.workspace.widget.datasource.worklist\"%' AND extension_id IN ('core.workspace.widget.chart', 'core.workspace.widget.pie_chart', 'core.workspace.widget.scatterplot')");

$db->ExecuteMaster("UPDATE workspace_widget SET params_json = REPLACE(params_json,'\"core.workspace.widget.datasource.worklist\"','\"core.workspace.widget.datasource.worklist.metric\"') WHERE params_json LIKE '%\"core.workspace.widget.datasource.worklist\"%' AND extension_id IN ('core.workspace.widget.counter', 'core.workspace.widget.gauge')");

// ===========================================================================
// Add `currency`

if(!isset($tables['currency'])) {
	$sql = sprintf("
	CREATE TABLE `currency` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		name_plural VARCHAR(255) DEFAULT '',
		code VARCHAR(4) DEFAULT '',
		symbol VARCHAR(16) DEFAULT '',
		decimal_at TINYINT UNSIGNED NOT NULL DEFAULT 0,
		is_default TINYINT UNSIGNED NOT NULL DEFAULT 0,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['currency'] = 'currency';
	
	// USD
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('US Dollar'),
		$db->qstr('US Dollars'),
		$db->qstr('USD'),
		$db->qstr('$'),
		2,
		1,
		time()
	));
	
	// EUR
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Euro'),
		$db->qstr('Euros'),
		$db->qstr('EUR'),
		$db->qstr('€'),
		2,
		0,
		time()
	));
	
	// GBP
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('British Pound'),
		$db->qstr('British Pounds'),
		$db->qstr('GBP'),
		$db->qstr('£'),
		2,
		0,
		time()
	));
	
	// BTC
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Bitcoin'),
		$db->qstr('Bitcoins'),
		$db->qstr('BTC'),
		$db->qstr('Ƀ'),
		8,
		0,
		time()
	));
}

// ===========================================================================
// Increase the size of `custom_field_numbervalue` from int4 to int8

if(!isset($tables['custom_field_numbervalue'])) {
	$logger->error("The 'custom_field_numbervalue' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('custom_field_numbervalue');

if(@$columns['field_value'] && 0 != strcasecmp($columns['field_value']['type'], 'bigint(20)')) {
	$db->ExecuteMaster("ALTER TABLE custom_field_numbervalue MODIFY COLUMN field_value BIGINT NOT NULL DEFAULT 0");
}

// ===========================================================================
// Add `updated_at` and `uri` field to the `community_tool` table

if(!isset($tables['community_tool'])) {
	$logger->error("The 'community_tool' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('community_tool');

if(!isset($columns['uri'])) {
	$sql = "ALTER TABLE community_tool ADD COLUMN uri varchar(32) NOT NULL DEFAULT ''";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("UPDATE community_tool SET uri = code");
}

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE community_tool ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE community_tool SET updated_at = %d", time()));
}

// ===========================================================================
// Add `params_required_query` and remove `render_filters` on `worker_view_model`

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('worker_view_model');

if(!isset($columns['params_required_query'])) {
	$sql = "ALTER TABLE worker_view_model ADD COLUMN params_required_query TEXT AFTER params_required_json";
	$db->ExecuteMaster($sql);
}

if(isset($columns['render_filters'])) {
	$sql = "ALTER TABLE worker_view_model DROP COLUMN render_filters";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add `worker_id` field to the `address` table and drop `address_to_worker`

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('address');

if(!isset($columns['worker_id'])) {
	$sql = 'ALTER TABLE address ADD COLUMN worker_id int(10) unsigned NOT NULL DEFAULT 0, ADD INDEX (worker_id)';
	$db->ExecuteMaster($sql);
	
	// Associate all worker primary email addresses with a worker
	$db->ExecuteMaster("UPDATE address INNER JOIN worker ON (address.id=worker.email_id) SET address.worker_id=worker.id WHERE address.mail_transport_id=0");
}

if(isset($tables['address_to_worker'])) {
	$db->ExecuteMaster("UPDATE address INNER JOIN address_to_worker ON (address.id=address_to_worker.address_id) SET address.worker_id=address_to_worker.worker_id WHERE address_to_worker.is_confirmed = 1 AND address.mail_transport_id=0");
	$db->ExecuteMaster('DROP TABLE address_to_worker');
}

// ===========================================================================
// Default worker search menus

if(0 == $db->GetOneMaster("SELECT COUNT(*) FROM worker_pref WHERE setting = 'search_favorites_json'")) {
	$db->ExecuteMaster("INSERT IGNORE INTO worker_pref (worker_id, setting, value) SELECT id, 'search_favorites_json', '[\"cerberusweb.contexts.contact\",\"cerberusweb.contexts.address\",\"cerberusweb.contexts.org\",\"cerberusweb.contexts.task\",\"cerberusweb.contexts.ticket\"]' from worker");
}

// ===========================================================================
// Remove unused worker view models

$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id like '%_attendants'");
$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id like '%_cfield_sets'");
$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id like '%_snippets'");
$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id = 'group_buckets'");
$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id = 'group_members'");

// ===========================================================================
// Finish up

return TRUE;
