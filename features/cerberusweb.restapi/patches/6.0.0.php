<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `webapi_credentials` ========================
if(!isset($tables['webapi_credentials'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS webapi_credentials (
			id INT UNSIGNED AUTO_INCREMENT,
			label VARCHAR(255) DEFAULT '' NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			access_key VARCHAR(255) DEFAULT '' NOT NULL,
			secret_key VARCHAR(255) DEFAULT '' NOT NULL,
			params_json TEXT,
			PRIMARY KEY (id),
			INDEX worker_id (worker_id),
			INDEX access_key (access_key)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
	
	$tables['webapi_credentials'] = 'webapi_credentials';
}

return TRUE;