<?php

$json = file_get_contents('dump.json');

if(false == ($page = json_decode($json, true)))
	throw new Exception("The file is not valid JSON.");

$records = [];
$page_uid = $page['page']['uid'];

foreach($page['page']['tabs'] as $tab) {
	$tab_uid = $tab['uid'];
	
	$widgets = $tab['widgets'];
	unset($tab['widgets']);
	
	$records[] = $tab;
	
	foreach($widgets as $widget) {
		$widget['tab_id'] = sprintf("{{{uid.%s}}}", $tab_uid);
		$records[] = $widget;
	}
}

echo json_encode($records);