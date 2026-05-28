<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `classifier` table

if(!isset($tables['classifier'])) {
	$sql = sprintf("
	CREATE TABLE `classifier` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		owner_context varchar(255) not null default '',
		owner_context_id int unsigned not null default 0,
		created_at int unsigned not null default 0,
		updated_at int unsigned not null default 0,
		dictionary_size int unsigned not null default 0,
		params_json text,
		primary key (id),
		index owner (owner_context, owner_context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['classifier'] = 'classifier';
}

if(!$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type = 'cerberusweb.contexts.classifier' and name = 'Properties' and extension_id = 'cerb.card.widget.fields'")) {
	$db->ExecuteMaster(
		"INSERT INTO `card_widget` (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES('Properties', 'cerberusweb.contexts.classifier', 'cerb.card.widget.fields', '{\"context\":\"cerberusweb.contexts.classifier\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"updated\"]],\"links\":{\"show\":1},\"search\":{\"context\":[\"cerberusweb.contexts.classifier.class\",\"cerberusweb.contexts.classifier.example\"],\"query\":[\"classifier.id:{{record_id}}\",\"classifier.id:{{record_id}}\"],\"label_singular\":[\"Classification\",\"Example\"],\"label_plural\":[\"Classifications\",\"Examples\"]}}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 4, 'content')"
	);
}

// Classifier training
if(!$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type = 'cerberusweb.contexts.classifier' and name = 'Training' and extension_id = 'cerb.card.widget.classifier.trainer'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Training'),
		$db->qstr(CerberusContexts::CONTEXT_CLASSIFIER),
		$db->qstr('cerb.card.widget.classifier.trainer'),
		$db->qstr(json_encode([
			"classifier_id" => "{{record_id}}",
		])),
		time(),
		time(),
		2,
		4,
		$db->qstr('content')
	));
}

// Add default classifier profile tab
if(!($db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.classifier' AND name = 'Overview'"))) {
	$sqls = <<< EOD
	INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
	SET @last_tab_id = LAST_INSERT_ID();
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifications',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.classifier.class\",\"query_required\":\"classifier.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"c_name\",\"c_training_count\",\"c_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifier',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Examples',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.classifier.example\",\"query_required\":\"classifier.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"c_class_id\",\"c_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
	EOD;
	
	foreach (DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r', '\n', '\t'], ['\\\r', '\\\n', '\\\t'], $sql);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add `classifier_class` table

if(!isset($tables['classifier_class'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_class` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		classifier_id int unsigned not null default 0,
		training_count int unsigned not null default 0,
		dictionary_size int unsigned not null default 0,
		entities varchar(255) default '',
		slots_json text,
		updated_at int unsigned not null default 0,
		primary key (id),
		index (classifier_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['classifier_class'] = 'classifier_class';
}

if(!$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type = 'cerberusweb.contexts.classifier.class' and name = 'Properties' and extension_id = 'cerb.card.widget.fields'")) {
	$db->ExecuteMaster("INSERT INTO `card_widget` (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES ('Properties', 'cerberusweb.contexts.classifier.class', 'cerb.card.widget.fields', '{\"context\":\"cerberusweb.contexts.classifier.class\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"classifier_id\",\"updated\"]],\"links\":{\"show\":1},\"search\":{\"context\":[\"cerberusweb.contexts.classifier.example\"],\"query\":[\"classifier.id:{{record_classifier_id}} class.id:{{record_id}}\"],\"label_singular\":[\"Example\"],\"label_plural\":[\"Examples\"]}}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 4, 'content')"
	);
}

if(!($db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.classifier.class' AND name = 'Overview'"))) {
	$sqls = <<< EOD
		# Classifier Class
		INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier.class','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
		SET @last_tab_id = LAST_INSERT_ID();
		INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classification',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.class\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"training_count\",\"dictionary_size\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
		INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifier',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier\",\"context_id\":\"{{record_classifier_id}}\",\"properties\":[[\"name\",\"owner\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.classifier.class\",\"cerberusweb.contexts.classifier.example\"],\"label_singular\":[\"Classification\",\"Example\"],\"label_plural\":[\"Classifications\",\"Examples\"],\"query\":[\"classifier.id:{{record_classifier_id}}\",\"classifier.id:{{record_classifier_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
		INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Training Examples',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.classifier.example\",\"query_required\":\"classifier.id:{{record_classifier_id}} class.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"c_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
		EOD;
	
	foreach (DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r', '\n', '\t'], ['\\\r', '\\\n', '\\\t'], $sql);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add `classifier_entity` table

if(!isset($tables['classifier_entity'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_entity` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		description varchar(255) not null default '',
		type varchar(255) not null default '',
		params_json text,
		updated_at int unsigned not null default 0,
		primary key (id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['classifier_entity'] = 'classifier_entity';
}

if(!$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type = 'cerberusweb.contexts.classifier.entity' and name = 'Properties' and extension_id = 'cerb.card.widget.fields'")) {
	$db->ExecuteMaster("INSERT INTO `card_widget` (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES ('Properties','cerberusweb.contexts.classifier.entity','cerb.card.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.entity\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\"]],\"links\":{\"show\":1},\"search\":[]}',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),0,4,'content')"
	);
}

if(!($db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.classifier.entity' AND name = 'Overview'"))) {
	$sqls = <<< EOD
	# Classifier Entity
	INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier.entity','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
	SET @last_tab_id = LAST_INSERT_ID();
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Entity',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.entity\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\"]]}','sidebar',1,4,UNIX_TIMESTAMP());
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.classifier.entity\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
	EOD;
	
	foreach (DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r', '\n', '\t'], ['\\\r', '\\\n', '\\\t'], $sql);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add `classifier_example` table

if(!isset($tables['classifier_example'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_example` (
		id int unsigned auto_increment,
		classifier_id int unsigned not null default 0,
		class_id int unsigned not null default 0,
		expression text,
		updated_at int unsigned not null default 0,
		primary key (id),
		index (classifier_id),
		index (class_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['classifier_example'] = 'classifier_example';
}

if(!$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type = 'cerberusweb.contexts.classifier.example' and name = 'Properties' and extension_id = 'cerb.card.widget.fields'")) {
	$db->ExecuteMaster("INSERT INTO `card_widget` (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES('Properties', 'cerberusweb.contexts.classifier.example', 'cerb.card.widget.fields', '{\"context\":\"cerberusweb.contexts.classifier.example\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"classifier_id\",\"class_id\",\"updated\"]],\"links\":{\"show\":1},\"search\":[]}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 4, 'content')"
	);
}

if(!($db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.classifier.example' AND name = 'Overview'"))) {
	$sqls = <<< EOD
	# Classifier Example
	INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier.example','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
	SET @last_tab_id = LAST_INSERT_ID();
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Training Example',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.example\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classification',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.class\",\"context_id\":\"{{record_class_id}}\",\"properties\":[[\"name\",\"dictionary_size\",\"training_count\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifier',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier\",\"context_id\":\"{{record_classifier_id}}\",\"properties\":[[\"name\",\"owner\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',3,4,UNIX_TIMESTAMP());
	INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.classifier.example\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
	EOD;
	
	foreach (DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r', '\n', '\t'], ['\\\r', '\\\n', '\\\t'], $sql);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add `classifier_ngram` table

if(!isset($tables['classifier_ngram'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_ngram` (
		id int unsigned auto_increment,
		token varchar(255) not null default '',
		n tinyint unsigned not null default 0,
		primary key (id),
		unique (token)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['classifier_ngram'] = 'classifier_ngram';
}

if(!isset($tables['classifier_ngram_to_class'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_ngram_to_class` (
		token_id int unsigned not null default 0,
		class_id int unsigned not null default 0,
		training_count int unsigned not null default 0,
		primary key (token_id, class_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['classifier_ngram_to_class'] = 'classifier_ngram_to_class';
}