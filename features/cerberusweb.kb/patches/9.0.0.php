<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.kb_article'");

if(!$result) {
	$sqls = <<< EOD
# Kb Article
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.kb_article','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Properties',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.kb_article\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\",\"views\",\"id\"]],\"search\":{\"context\":[\"cerberusweb.contexts.kb_category\"],\"label_singular\":[\"Category\"],\"label_plural\":[\"Categories\"],\"query\":[\"article.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Article',@last_tab_id,'cerb.profile.tab.widget.kb_article','{\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.kb_article',CONCAT('[',@last_tab_id,']'));

# Kb Category
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.kb_category','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Knowledgebase Category',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.kb_category\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"parent_id\",\"updated\"]],\"search\":{\"context\":[\"cerberusweb.contexts.kb_category\"],\"label_singular\":[\"Subcategory\"],\"label_plural\":[\"Subcategories\"],\"query\":[\"parent.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Articles',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.kb_article\",\"query_required\":\"category.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"kb_title\",\"kb_updated\",\"kb_views\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.kb_category',CONCAT('[',@last_tab_id,']'));
EOD;
	
	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
}

return TRUE;