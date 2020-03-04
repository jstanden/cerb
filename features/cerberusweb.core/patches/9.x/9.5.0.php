<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Update package library

$packages = [
	'cerb_profile_tab_ticket_overview.json',
	'cerb_profile_widget_ticket_actions.json',
	'cerb_profile_widget_ticket_owner.json',
	'cerb_profile_widget_ticket_status.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

// ===========================================================================
// Add `address.is_trusted` bit

list($columns,) = $db->metaTable('address');

if(!array_key_exists('is_trusted', $columns)) {
	$sql = "ALTER TABLE address ADD COLUMN is_trusted tinyint(1) not null default 0";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("UPDATE address SET is_trusted = 1 WHERE worker_id > 0 OR mail_transport_id > 0");
}
	
// ===========================================================================
// Update the ticket actions widget to new endpoints

$checksum = $db->GetOneMaster("SELECT sha1(extension_params_json) from profile_widget WHERE name = 'Actions' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')");

if(in_array($checksum, ['5d57b97803ff3464cf81da43882c113beae3cfb2','792ca1e1f646143fb03b41333f7af5a60745165c','debc40413751299d18e577177aaf16ffda0234ef','e55a0d7876aaa7683e7ecd9aab598428f22abae1'])) {
	$json = <<< 'EOD'
{"template": "{% if not cerb_record_writeable(record__context, record_id) %}\r\n\t<div style=\"color:rgb(120,120,120);text-align:center;font-size:1.2em;\">\r\n\t\t(you do not have permission to edit this record)\r\n\t</div>\r\n\t\r\n{% else %}\r\n\t{% set is_closed = 'closed' == record_status %}\r\n\t{% set is_deleted = 'deleted' == record_status %}\r\n\t\r\n\t<div id=\"widget{{widget_id}}\" style=\"padding:0px 5px 5px 5px;\">\r\n\t\t{% if is_closed or is_deleted %}\r\n\t\t<button type=\"button\" data-shortcut=\"reopen\">\r\n\t\t\t<span class=\"glyphicons glyphicons-upload\"></span> {{'common.reopen'|cerb_translate|capitalize}}\r\n\t\t</button>\r\n\t\t{% endif %}\r\n\t\t\r\n\t\t{% if not is_deleted and not is_closed and cerb_has_priv('contexts.cerberusweb.contexts.ticket.update') %}\r\n\t\t\t<button type=\"button\" data-shortcut=\"close\" title=\"(C)\">\r\n\t\t\t<span class=\"glyphicons glyphicons-circle-ok\"></span> {{'common.close'|cerb_translate|capitalize}}\r\n\t\t</button>\r\n\t\t{% endif %}\r\n\t\t\r\n\t\t{% if record_spam_training is empty and not is_deleted and cerb_has_priv('contexts.cerberusweb.contexts.ticket.update') %}\r\n\t\t<button type=\"button\" data-shortcut=\"spam\" title=\"(S)\">\r\n\t\t\t<span class=\"glyphicons glyphicons-ban\"></span> {{'common.spam'|cerb_translate|capitalize}}\r\n\t\t</button>\r\n\t\t{% endif %}\r\n\r\n\t\t{% if not is_deleted and cerb_has_priv('contexts.cerberusweb.contexts.ticket.delete') %}\r\n\t\t<button type=\"button\" data-shortcut=\"delete\" title=\"(X)\">\r\n\t\t\t<span class=\"glyphicons glyphicons-circle-remove\"></span> {{'common.delete'|cerb_translate|capitalize}}\r\n\t\t</button>\r\n\t\t{% endif %}\r\n\t\t\r\n\t\t{%if cerb_has_priv('contexts.cerberusweb.contexts.ticket.merge') %}\r\n\t\t<button type=\"button\" data-shortcut=\"merge\">\r\n\t\t\t<span class=\"glyphicons glyphicons-git-merge\"></span> {{'common.merge'|cerb_translate|capitalize}}\r\n\t\t</button>\r\n\t\t{% endif %}\r\n\t\t\r\n\t\t<button type=\"button\" data-shortcut=\"read-all\" title=\"(A)\">\r\n\t\t\t<span class=\"glyphicons glyphicons-book-open\"></span> {{'display.button.read_all'|cerb_translate|capitalize}}\r\n\t\t</button>\r\n\t</div>\r\n\t\r\n\t<script type=\"text/javascript\">\r\n\t$(function() {\r\n\t\tvar $widget = $('#widget{{widget_id}}');\r\n\t\tvar $parent = $widget.closest('.cerb-profile-widget')\r\n\t\t\t.off('.widget{{widget_id}}')\r\n\t\t\t;\r\n\t\tvar $tab = $parent.closest('.cerb-profile-layout');\r\n\t\t\r\n\t\t$widget.find('button.cerb-peek-editor')\r\n\t\t\t.cerbPeekTrigger()\r\n\t\t\t.on('cerb-peek-saved', function(e) {\r\n\t\t\t\t\te.stopPropagation();\r\n\t\t\t\t\tdocument.location.reload();\r\n\t\t\t})\r\n\t\t\t;\r\n\t\t\t\r\n\t\tvar $button_reopen = $widget.find('button[data-shortcut=\"reopen\"]')\r\n\t\t\t.on('click', function(e) {\r\n\t\t\t\te.stopPropagation();\r\n\t\t\t\te.preventDefault();\r\n\t\t\t\t\r\n\t\t\t\tvar formData = new FormData();\r\n\t\t\t\tformData.set('c', 'profiles');\r\n\t\t\t\tformData.set('a', 'invoke');\r\n\t\t\t\tformData.set('module', 'ticket');\r\n\t\t\t\tformData.set('action', 'quickStatus');\r\n\t\t\t\tformData.set('ticket_id', \"{{record_id}}\");\r\n\t\t\t\tformData.set('status', 'o');\r\n\r\n\t\t\t\tgenericAjaxPost(formData, null, null, function(event) {\r\n\t\t\t\t\t\tdocument.location.reload();\r\n\t\t\t\t});\t\t\t\t\r\n\t\t\t})\r\n\t\t\t;\r\n\t\t\r\n\t\tvar $button_close = $widget.find('button[data-shortcut=\"close\"]')\r\n\t\t\t.on('click', function(e) {\r\n\t\t\t\te.stopPropagation();\r\n\t\t\t\te.preventDefault();\r\n\t\t\t\t\r\n\t\t\t\tvar formData = new FormData();\r\n\t\t\t\tformData.set('c', 'profiles');\r\n\t\t\t\tformData.set('a', 'invoke');\r\n\t\t\t\tformData.set('module', 'ticket');\r\n\t\t\t\tformData.set('action', 'quickStatus');\r\n\t\t\t\tformData.set('ticket_id', \"{{record_id}}\");\r\n\t\t\t\tformData.set('status', 'c');\r\n\r\n\t\t\t\tgenericAjaxPost(formData, null, null, function(event) {\r\n\t\t\t\t\t\tdocument.location.reload();\r\n\t\t\t\t});\r\n\t\t\t})\r\n\t\t\t;\r\n\t\t\r\n\t\tvar $button_delete = $widget.find('button[data-shortcut=\"delete\"]')\r\n\t\t\t.on('click', function(e) {\r\n\t\t\t\te.stopPropagation();\r\n\t\t\t\te.preventDefault();\r\n\t\t\t\t\r\n\t\t\t\tvar formData = new FormData();\r\n\t\t\t\tformData.set('c', 'profiles');\r\n\t\t\t\tformData.set('a', 'invoke');\r\n\t\t\t\tformData.set('module', 'ticket');\r\n\t\t\t\tformData.set('action', 'quickStatus');\r\n\t\t\t\tformData.set('ticket_id', \"{{record_id}}\");\r\n\t\t\t\tformData.set('status', 'd');\r\n\r\n\t\t\t\tgenericAjaxPost(formData, null, null, function(event) {\r\n\t\t\t\t\t\tdocument.location.reload();\r\n\t\t\t\t});\r\n\t\t\t});\r\n\t\t\r\n\t\tvar $button_spam = $widget.find('button[data-shortcut=\"spam\"]')\r\n\t\t\t.on('click', function(e) {\r\n\t\t\t\te.stopPropagation();\r\n\t\t\t\te.preventDefault();\r\n\t\t\t\t\r\n\t\t\t\tvar formData = new FormData();\r\n\t\t\t\tformData.set('c', 'profiles');\r\n\t\t\t\tformData.set('a', 'invoke');\r\n\t\t\t\tformData.set('module', 'ticket');\r\n\t\t\t\tformData.set('action', 'quickSpam');\r\n\t\t\t\tformData.set('ticket_id', \"{{record_id}}\");\r\n\t\t\t\tformData.set('is_spam', '1');\r\n\r\n\t\t\t\tgenericAjaxPost(formData, null, null, function(event) {\r\n\t\t\t\t\t\tdocument.location.reload();\r\n\t\t\t\t});\t\t\t\t\r\n\t\t\t})\r\n\t\t\t;\r\n\t\t\r\n\t\tvar $button_merge = $widget.find('button[data-shortcut=\"merge\"]');\r\n\t\tvar $button_readall = $widget.find('button[data-shortcut=\"read-all\"]');\r\n\t\r\n\t\t{% if cerb_has_priv('contexts.cerberusweb.contexts.ticket.merge') %}\r\n\t\t$button_merge\r\n\t\t\t.on('click', function(e) {\r\n\t\t\t\tvar $merge_popup = genericAjaxPopup('peek','c=internal&a=invoke&module=records&action=renderMergePopup&context={{record__context}}&ids={{record_id}}',null,false,'50%');\r\n\t\t\t\t\r\n\t\t\t\t$merge_popup.on('record_merged', function(e) {\r\n\t\t\t\t\te.stopPropagation();\r\n\t\t\t\t\tdocument.location.reload();\r\n\t\t\t\t});\r\n\t\t\t})\r\n\t\t\t;\r\n\t\t{% endif %}\r\n\t\t\r\n\t\t$button_readall.on('click', function(e) {\r\n\t\t\tvar widget_id = $tab.find('.cerb-profile-widget--header:contains(Conversation)').closest('.cerb-profile-widget').attr('data-widget-id');\r\n\t\t\t\r\n\t\t\tif(!widget_id)\r\n\t\t\t\treturn;\r\n\t\t\t\r\n\t\t\tvar evt = $.Event('cerb-widget-refresh');\r\n\t\t\tevt.widget_id = widget_id;\r\n\t\t\tevt.refresh_options = {'expand_all': 1};\r\n\t\t\t$tab.triggerHandler(evt);\r\n\t\t});\r\n\t\t\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'A', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t$button_readall.click();\r\n\t\t});\r\n\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'C', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t$button_close.click();\r\n\t\t});\r\n\t\t\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'S', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t$button_spam.click();\r\n\t\t});\r\n\t\t\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'X', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t$button_delete.click();\r\n\t\t});\r\n\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'H', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t\r\n\t\t\t$tab\r\n\t\t\t\t.find('.cerb-profile-widget--link:contains(Ticket)')\r\n\t\t\t\t.closest('.cerb-profile-widget')\r\n\t\t\t\t.find('button.cerb-search-trigger:contains(Participant History)')\r\n\t\t\t\t.click()\r\n\t\t\t\t;\r\n\t\t});\r\n\t});\r\n\t</script>\r\n{% endif %}"}
EOD;
	
	$sql = sprintf("UPDATE profile_widget SET extension_params_json = %s WHERE name = 'Actions' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')",
		$db->qstr($json)
	);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Update the ticket owner widget to new endpoints

$checksum = $db->GetOneMaster("SELECT sha1(extension_params_json) from profile_widget WHERE extension_id = 'cerb.profile.tab.widget.html' AND name = 'Owner' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')");

if(in_array($checksum, ['0e02083bb08dd4ce1c0044d4b04ec63757d8c752'])) {
	$json = <<< 'EOD'
{"template": "{% set is_writeable = cerb_record_writeable(record__context,record_id,current_worker__context,current_worker_id) %}\r\n<div id=\"widget{{widget_id}}\">\r\n\t<div style=\"float:left;padding:0 10px 5px 5px;\">\r\n\t\t<img src=\"{{cerb_avatar_url(record_owner__context,record_owner_id,record_owner_updated)}}\" width=\"50\" style=\"border-radius:50px;\">\r\n\t</div>\r\n\t<div style=\"position:relative;\">\r\n\t\t<div>\r\n\t\t\t{% if record_owner_id == 0 %}\r\n\t\t\t\t<span style=\"font-size:2em;color:rgb(100,100,100);font-weight:bold;\">\r\n\t\t\t\t\t({{'common.nobody'|cerb_translate|lower}})\r\n\t\t\t\t</span>\r\n\t\t\t{% else %}\r\n\t\t\t\t\t<a href=\"javascript:;\" class=\"cerb-peek-trigger no-underline\" style=\"font-size:2em;color:rgb(100,100,100);font-weight:bold;\" data-context=\"cerberusweb.contexts.worker\" data-context-id=\"{{record_owner_id}}\">{{record_owner__label}}</a>\r\n\t\t\t\t\t<div>\r\n\t\t\t\t\t\t{{record_owner_title}}\r\n\t\t\t\t\t</div>\r\n\t\t\t{% endif %}\r\n\r\n\t\t\t{% if is_writeable %}\r\n\t\t\t<div class=\"cerb-buttons-toolbar\" style=\"display:none;position:absolute;top:0;right:0;\">\r\n\t\t\t\t<button type=\"button\" title=\"{{'common.assign'|cerb_translate|capitalize}} (Shift+T)\" class=\"cerb-button-assign cerb-chooser-trigger\" data-context=\"cerberusweb.contexts.worker\" data-single=\"true\" data-query=\"group:(id:{{record_group_id}}) isDisabled:n\">\r\n\t\t\t\t\t<span class=\"glyphicons glyphicons-send\"></span>\r\n\t\t\t\t</button>\r\n\t\t\t\t\r\n\t\t\t\t{% if record_owner_id %}\r\n\t\t\t\t<button type=\"button\" title=\"{{'common.unassign'|cerb_translate|capitalize}} (U)\" class=\"cerb-button-unassign\">\r\n\t\t\t\t\t<span class=\"glyphicons glyphicons-circle-remove\"></span>\r\n\t\t\t\t</button>\r\n\t\t\t\t{% endif %}\r\n\t\t\t</div>\r\n\t\t\t{% endif %}\r\n\t\t</div>\r\n\t</div>\r\n</div>\r\n\r\n<script type=\"text/javascript\">\r\n$(function() {\r\n\tvar $widget = $('#widget{{widget_id}}');\r\n\tvar $parent = $widget.closest('.cerb-profile-widget')\r\n\t\t.off('.widget{{widget_id}}')\r\n\t\t;\r\n\tvar $toolbar = $widget.find('div.cerb-buttons-toolbar');\r\n\tvar $tab = $parent.closest('.cerb-profile-layout');\r\n\t\r\n\tvar $button_take = $widget.find('button.cerb-button-take');\r\n\tvar $button_assign = $widget.find('.cerb-chooser-trigger');\r\n\tvar $button_unassign = $widget.find('button.cerb-button-unassign');\r\n\r\n\t$widget.find('.cerb-peek-trigger')\r\n\t\t.cerbPeekTrigger()\r\n\t\t;\r\n\t\t\r\n\t{% if is_writeable %}\r\n\t$widget\r\n\t\t.on('mouseover', function() {\r\n\t\t\t$toolbar.show();\r\n\t\t})\r\n\t\t.on('mouseout', function() {\r\n\t\t\t$toolbar.hide();\r\n\t\t})\r\n\t\t;\r\n\t{% endif %}\r\n\t\r\n\t{% if is_writeable %}\r\n\t$button_assign\r\n\t\t.cerbChooserTrigger()\r\n\t\t.on('cerb-chooser-selected', function(e) {\r\n\t\t\tif(!e.values || !$.isArray(e.values))\r\n\t\t\t\treturn;\r\n\t\t\t\t\r\n\t\t\tif(e.values.length != 1)\r\n\t\t\t\treturn;\r\n\r\n\t\t\tvar owner_id = e.values[0];\r\n\t\t\t\r\n\t\t\tvar formData = new FormData();\r\n\t\t\tformData.set('c', 'profiles');\r\n\t\t\tformData.set('a', 'invoke');\r\n\t\t\tformData.set('module', 'ticket');\r\n\t\t\tformData.set('action', 'quickAssign');\r\n\t\t\tformData.set('ticket_id', '{{record_id}}');\r\n\t\t\tformData.set('owner_id', owner_id);\r\n\t\t\t\r\n\t\t\tgenericAjaxPost(formData, null, null, function(response) {\r\n\t\t\t\t// Refresh the entire page\r\n\t\t\t\tdocument.location.reload();\r\n\t\t\t});\r\n\t\t})\r\n\t\t;\r\n\t\t\r\n\t$button_unassign\r\n\t\t.on('click', function(e) {\r\n\t\t\tvar formData = new FormData();\r\n\t\t\tformData.set('c', 'profiles');\r\n\t\t\tformData.set('a', 'invoke');\r\n\t\t\tformData.set('module', 'ticket');\r\n\t\t\tformData.set('action', 'quickAssign');\r\n\t\t\tformData.set('ticket_id', '{{record_id}}');\r\n\t\t\tformData.set('owner_id', '0');\r\n\t\t\t\r\n\t\t\tgenericAjaxPost(formData, null, null, function(response) {\r\n\t\t\t\t// Refresh the entire page\r\n\t\t\t\tdocument.location.reload();\r\n\t\t\t});\r\n\t\t})\r\n\t\t;\r\n\t\t\r\n\t\t{# Allow the (t)ake shortcut for 'assign to me' if unassigned #}\r\n\t\t{% if record_owner_id == 0%}\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'T', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t\r\n\t\t\tvar formData = new FormData();\r\n\t\t\tformData.set('c', 'profiles');\r\n\t\t\tformData.set('a', 'invoke');\r\n\t\t\tformData.set('module', 'ticket');\r\n\t\t\tformData.set('action', 'quickAssign');\r\n\t\t\tformData.set('ticket_id', '{{record_id}}');\r\n\t\t\tformData.set('owner_id', '{{current_worker_id}}');\r\n\r\n\t\t\tgenericAjaxPost(formData, null, null, function(response) {\r\n\t\t\t\t// Refresh the entire page\r\n\t\t\t\tdocument.location.reload();\r\n\t\t\t});\r\n\t\t});\r\n\t\t{% endif %}\r\n\t\t\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'Shift+T', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t$button_assign.click();\r\n\t\t});\r\n\r\n\t\t$parent.on('keydown.widget{{widget_id}}', null, 'U', function(e) {\r\n\t\t\te.stopPropagation();\r\n\t\t\te.preventDefault();\r\n\t\t\t$button_unassign.click();\r\n\t\t});\r\n\r\n\t{% endif %}\r\n});\r\n</script>"}
EOD;
	
	$sql = sprintf("UPDATE profile_widget SET extension_params_json = %s WHERE extension_id = 'cerb.profile.tab.widget.html' AND name = 'Owner' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')",
		$db->qstr($json)
	);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Update the ticket status widget to new endpoints

$checksum = $db->GetOneMaster("SELECT sha1(extension_params_json) from profile_widget WHERE name = 'Status' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')");

if(in_array($checksum, ['11b6eec5165ca9329656d1091dd877ade7963d63','d423cf6e266522810da0c220a10902029a6452d7','2966b92e7895b4b0a6f0e10b8026646c52527b31'])) {
	$json = <<< 'EOD'
{"template": "{% set is_writeable = cerb_record_writeable(record__context,record_id,current_worker__context,current_worker_id) %}\r\n<div id=\"widget{{widget_id}}\">\r\n\t<div style=\"float:left;padding:0 10px 5px 5px;\">\r\n\t\t<img src=\"{{cerb_avatar_url(record_group__context,record_group_id,record_group_updated)}}\" width=\"50\" style=\"border-radius:50px;\">\r\n\t</div>\r\n\t<div style=\"position:relative;\">\r\n\t\t<div>\r\n\t\t\t<a href=\"javascript:;\" class=\"cerb-peek-trigger no-underline\" style=\"font-size:1.3em;color:rgb(150,150,150);font-weight:bold;\" data-context=\"cerberusweb.contexts.group\" data-context-id=\"{{record_group_id}}\">{{record_group__label}}</a>\r\n\t\t</div>\r\n\t\t<div>\r\n\t\t\t<a href=\"javascript:;\" class=\"cerb-peek-trigger no-underline\" style=\"font-size:2em;color:rgb(100,100,100);font-weight:bold;\" data-context=\"cerberusweb.contexts.bucket\" data-context-id=\"{{record_bucket_id}}\">{{record_bucket__label}}</a>\r\n\t\t\t\r\n\t\t\t{% if is_writeable %}\r\n\t\t\t<div class=\"cerb-buttons-toolbar\" style=\"display:none;position:absolute;top:0;right:0;\">\r\n\t\t\t\t<button type=\"button\" title=\"{{'common.move'|cerb_translate|capitalize}}\" class=\"cerb-button-move cerb-chooser-trigger\" data-context=\"cerberusweb.contexts.bucket\" data-single=\"true\" data-query=\"subtotal:group.id\">\r\n\t\t\t\t\t<span class=\"glyphicons glyphicons-send\"></span>\r\n\t\t\t\t</button>\r\n\t\t\t</div>\r\n\t\t\t{% endif %}\r\n\t\t</div>\r\n\t</div>\r\n\t<div style=\"margin-top:5px;font-size:1.5em;font-weight:bold;\">\r\n\t\t{% if record_status == 'waiting' %}\r\n\t\t<div style=\"color:rgb(85,132,204);\">\r\n\t\t\t{{'status.waiting.abbr'|cerb_translate|capitalize}}\r\n\t\t\t{% if record_reopen_date %}\r\n\t\t\t<span style=\"font-size:0.8em;font-weight:normal;color:black;\">\r\n\t\t\t\t(<abbr title=\"{{record_reopen_date|date('F d, Y g:ia')}}\">{{record_reopen_date|date_pretty}}</abbr>)\r\n\t\t\t</span>\r\n\t\t\t{% endif %}\r\n\t\t</div>\r\n\t\t{% elseif record_status == 'closed' %}\r\n\t\t<div style=\"color:rgb(100,100,100);\">\r\n\t\t\t{{'status.closed'|cerb_translate|capitalize}}\r\n\t\t\t{% if record_reopen_date %}\r\n\t\t\t<span style=\"font-size:0.8em;font-weight:normal;color:black;\">\r\n\t\t\t\t(<abbr title=\"{{record_reopen_date|date('F d, Y g:ia')}}\">{{record_reopen_date|date_pretty}}</abbr>)\r\n\t\t\t</span>\r\n\t\t\t{% endif %}\r\n\t\t</div>\r\n\t\t{% elseif record_status == 'deleted' %}\r\n\t\t<div style=\"color:rgb(211,53,43);\">\r\n\t\t\t{{'status.deleted'|cerb_translate|capitalize}}\r\n\t\t</div>\r\n\t\t{% else %}\r\n\t\t<div style=\"color:rgb(102,172,87);\">\r\n\t\t\t{{'status.open'|cerb_translate|capitalize}}\r\n\t\t</div>\r\n\t\t{% endif %}\r\n\t</div>\r\n</div>\r\n\r\n<script type=\"text/javascript\">\r\n$(function() {\r\n\tvar $widget = $('#widget{{widget_id}}');\r\n\tvar $parent = $widget.closest('.cerb-profile-widget')\r\n\t\t.off('.widget{{widget_id}}')\r\n\t\t;\r\n\tvar $toolbar = $widget.find('div.cerb-buttons-toolbar');\r\n\tvar $tab = $parent.closest('.cerb-profile-layout');\r\n\r\n\t$widget.find('.cerb-peek-trigger')\r\n\t\t.cerbPeekTrigger()\r\n\t\t;\r\n\t\t\r\n\t{% if is_writeable %}\r\n\t$widget\r\n\t\t.on('mouseover', function() {\r\n\t\t\t$toolbar.show();\r\n\t\t})\r\n\t\t.on('mouseout', function() {\r\n\t\t\t$toolbar.hide();\r\n\t\t})\r\n\t\t;\r\n\t{% endif %}\r\n\t\r\n\t{% if is_writeable %}\r\n\t$widget.find('.cerb-chooser-trigger')\r\n\t\t.cerbChooserTrigger()\r\n\t\t.on('cerb-chooser-selected', function(e) {\r\n\t\t\tif(!e.values || !$.isArray(e.values))\r\n\t\t\t\treturn;\r\n\t\t\t\t\r\n\t\t\tif(e.values.length != 1)\r\n\t\t\t\treturn;\r\n\r\n\t\t\tvar bucket_id = e.values[0];\r\n\t\t\t\r\n\t\t\tvar formData = new FormData();\r\n\t\t\tformData.set('c', 'profiles');\r\n\t\t\tformData.set('a', 'invoke');\r\n\t\t\tformData.set('module', 'ticket');\r\n\t\t\tformData.set('action', 'quickMove');\r\n\t\t\tformData.set('ticket_id', '{{record_id}}');\r\n\t\t\tformData.set('bucket_id', bucket_id);\r\n\t\t\t\r\n\t\t\tgenericAjaxPost(formData, null, null, function(response) {\r\n\t\t\t\t// Refresh the entire page\r\n\t\t\t\tdocument.location.reload();\r\n\t\t\t});\r\n\t\t})\r\n\t\t;\r\n\t\t{% endif %}\r\n});\r\n</script>"}
EOD;
	
	$sql = sprintf("UPDATE profile_widget SET extension_params_json = %s WHERE extension_id = 'cerb.profile.tab.widget.html' AND name = 'Status' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')",
		$db->qstr($json)
	);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Update the worker actions widget to new endpoints

$checksum = $db->GetOneMaster("SELECT sha1(extension_params_json) from profile_widget WHERE name = 'Actions' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.worker' AND name = 'Overview')");

if(in_array($checksum, ['86e23999f4d694388c80245d7e777fcaac0704c2'])) {
	$json = <<< 'EOD'
{"template": "{% set is_editable = cerb_record_writeable(record__context, record_id) %}\r\n{% if is_editable %}\r\n\t<div style=\"padding:0px 5px 5px 5px;\">\r\n\t\t<button type=\"button\" data-shortcut=\"impersonate\">\r\n\t\t\t<span class=\"glyphicons glyphicons-user\"></span> {{'common.impersonate'|cerb_translate|capitalize}}\r\n\t\t</button>\r\n\t</div>\r\n{% endif %}\r\n\r\n<script type=\"text/javascript\">\r\n$(function() {\r\n\tvar $widget = $('#profileWidget{{widget_id}}');\r\n\r\n\t{% if is_editable %}\r\n\t\tvar $button_impersonate = $widget.find('button[data-shortcut=\"impersonate\"]');\r\n\t\r\n\t\t$button_impersonate\r\n\t\t\t.on('click', function(e) {\r\n\t\t\t\tvar formData = new FormData();\r\n\t\t\t\tformData.set('c', 'profiles');\r\n\t\t\t\tformData.set('a', 'invoke');\r\n\t\t\t\tformData.set('module', 'worker');\r\n\t\t\t\tformData.set('action', 'su');\r\n\t\t\t\tformData.set('worker_id', '{{record_id}}');\r\n\t\t\t\t\r\n\t\t\t\tgenericAjaxPost(formData,null,null,function(o) {\r\n\t\t\t\t\twindow.location = window.location;\r\n\t\t\t\t});\r\n\t\t\t})\r\n\t\t\t;\r\n\t{% else %}\r\n\t\t$widget\r\n\t\t\t.closest('.cerb-profile-widget')\r\n\t\t\t.remove()\r\n\t\t\t;\r\n\t{% endif %}\r\n});\r\n</script>"}
EOD;
	
	$sql = sprintf("UPDATE profile_widget SET extension_params_json = %s WHERE extension_id = 'cerb.profile.tab.widget.html' AND name = 'Actions' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.worker' AND name = 'Overview')",
		$db->qstr($json)
	);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Fix Twig syntax (spaceless is retired)

if(array_key_exists('decision_node', $tables)) {
	$db->ExecuteMaster('update decision_node set params_json=replace(params_json,"{% spaceless %}","{% apply spaceless %}") where params_json like "%spaceless%"');
	$db->ExecuteMaster('update decision_node set params_json=replace(params_json,"{% endspaceless %}","{% endapply %}") where params_json like "%spaceless%"');
}

if(array_key_exists('project_board', $tables)) {
	$db->ExecuteMaster('update project_board set params_json=replace(params_json,"{% spaceless %}","{% apply spaceless %}") where params_json like "%spaceless%"');
	$db->ExecuteMaster('update project_board set params_json=replace(params_json,"{% endspaceless %}","{% endapply %}") where params_json like "%spaceless%"');
}

// ===========================================================================
// Create `gpg_private_key`

if(!isset($tables['gpg_private_key'])) {
	$sql = sprintf("
		CREATE TABLE `gpg_private_key` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) DEFAULT '',
		`fingerprint` varchar(255) DEFAULT '',
		`expires_at` int(10) unsigned NOT NULL DEFAULT '0',
		`updated_at` int(10) unsigned NOT NULL DEFAULT '0',
		`key_text` text,
		`passphrase_encrypted` text,
		PRIMARY KEY (`id`),
		KEY `fingerprint` (`fingerprint`(4))
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['gpg_private_key'] = 'gpg_private_key';
}

// ===========================================================================
// Create `gpg_key_part`

if(!isset($tables['gpg_key_part'])) {
	$sql = sprintf("
		CREATE TABLE `gpg_key_part` (
		`key_context` varchar(255) DEFAULT '',
		`key_id` int(10) unsigned NOT NULL DEFAULT '0',
		`part_name` varchar(16) DEFAULT '',
		`part_value` varchar(255) DEFAULT '',
		KEY `context_and_part` (`key_context`, `part_name`(4), `part_value`(6)),
		KEY `key_context_and_id` (`key_context`, `key_id`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['gpg_key_part'] = 'gpg_key_part';
	
} else {
	list($columns, $indexes) = $db->metaTable('gpg_key_part');
	
	if(array_key_exists('part_name', $columns) && 0 != strcasecmp('varchar(16)', $columns['part_name']['type'])) {
		$db->ExecuteMaster("ALTER TABLE gpg_key_part MODIFY COLUMN part_name VARCHAR(16) NOT NULL DEFAULT ''");
		$db->ExecuteMaster("ALTER TABLE gpg_key_part DROP INDEX context_and_part, ADD INDEX context_and_part (key_context,part_name(4),part_value(6))");
	}
}

// ===========================================================================
// Modify `gpg_public_key`

list($columns,) = $db->metaTable('gpg_public_key');

if(!isset($columns['key_text'])) {
	$sql = "ALTER TABLE gpg_public_key ADD COLUMN key_text text";
	$db->ExecuteMaster($sql);
	
	// Migrate from GNUPG keychain
	if(extension_loaded('gnupg')) {
		putenv("GNUPGHOME=" . APP_STORAGE_PATH . '/.gnupg');
		$gpg = new gnupg();
		$gpg->seterrormode(gnupg::ERROR_SILENT);
		
		$all_keys = $gpg->keyinfo('');
		$gpg->setarmor(1);
		
		foreach($all_keys as $keyinfo) {
			if(false == @$fingerprint = $keyinfo['subkeys'][0]['fingerprint'])
				continue;
			
			if(false != ($key_text = $gpg->export($fingerprint))) {
				DAO_GpgPublicKey::updateWhere(
					[
						DAO_GpgPublicKey::KEY_TEXT => $key_text
					],
					sprintf('fingerprint = %s', $db->qstr($fingerprint))
				);
			}
		}
	}
	
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO gpg_key_part (key_context, key_id, part_name, part_value) ".
		"SELECT %s, id, 'fingerprint', fingerprint FROM gpg_public_key",
		$db->qstr('cerberusweb.contexts.gpg_public_key')
	));
	
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO gpg_key_part (key_context, key_id, part_name, part_value) ".
		"SELECT %s, id, 'fingerprint16', substr(fingerprint,-16) FROM gpg_public_key",
		$db->qstr('cerberusweb.contexts.gpg_public_key')
	));
	
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO gpg_key_part (key_context, key_id, part_name, part_value) ".
		"SELECT %s, id, 'uid', name from gpg_public_key",
		$db->qstr('cerberusweb.contexts.gpg_public_key')
	));
	
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO gpg_key_part (key_context, key_id, part_name, part_value) ".
		"SELECT %s, id, 'name', substr(name,1,char_length(name)-locate('<',reverse(name))-1) from gpg_public_key",
		$db->qstr('cerberusweb.contexts.gpg_public_key')
	));
	
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO gpg_key_part (key_context, key_id, part_name, part_value) ".
		"SELECT 'cerberusweb.contexts.gpg_public_key', from_context_id as key_id, 'email', address.email from context_link inner join address on (to_context_id=address.id) where from_context = 'cerberusweb.contexts.gpg_public_key' and to_context = 'cerberusweb.contexts.address'"
	));
}

// ===========================================================================
// Add `worker_group.reply_signing_key_id`

list($columns,) = $db->metaTable('worker_group');

if(!isset($columns['reply_signing_key_id'])) {
	$sql = "ALTER TABLE worker_group ADD COLUMN reply_signing_key_id int unsigned not null default 0";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add `bucket.reply_signing_key_id`

list($columns,) = $db->metaTable('bucket');

if(!isset($columns['reply_signing_key_id'])) {
	$sql = "ALTER TABLE bucket ADD COLUMN reply_signing_key_id int unsigned not null default 0";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add PGP signature data to `message`
// Index `message.address_id`
// ===========================================================================

list($columns, $indexes) = $db->metaTable('message');

$changes = [];

if(array_key_exists('was_signed', $columns)) {
	$changes[] = "DROP COLUMN was_signed";
}

if(!isset($columns['signed_key_fingerprint'])) {
	$changes[] = "ADD COLUMN signed_key_fingerprint VARCHAR(64) NOT NULL DEFAULT ''";
}

if(!isset($columns['signed_at'])) {
	$changes[] = "ADD COLUMN signed_at INT UNSIGNED NOT NULL DEFAULT 0";
}

if(!array_key_exists('address_id', $indexes)) {
	$changes[] = "ADD INDEX (address_id)";
}

if($changes) {
	$sql = "ALTER TABLE message " . implode(', ', $changes);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return true;