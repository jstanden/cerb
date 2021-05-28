<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Update built-in automations

$automation_files = [
	'ai.cerb.automationBuilder.action.function.json',
	'ai.cerb.automationBuilder.action.httpRequest.json',
	'ai.cerb.automationBuilder.interaction.worker.await.promptSheet.json',
	'ai.cerb.editor.mapBuilder.json',
	'ai.cerb.eventHandler.automation.json',
	'ai.cerb.toolbarBuilder.interaction.json',
	'cerb.data.records.json',
	'cerb.projectBoard.toolbar.task.find.json',
	'cerb.ticket.move.json',
	'cerb.ticket.participants.manage.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Finish up

return TRUE;