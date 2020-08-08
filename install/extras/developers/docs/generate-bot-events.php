<?php
require(getcwd() . '/../../../../framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');
DevblocksPlatform::setStateless(true);

if(!file_exists('./out') || !is_dir('./out/'))
	die('The ./out/ directory does not exist.');
	
if(!file_exists('./out/bots-events/'))
	if(!mkdir('./out/bots-events/', 0775))
		die('The ./out/bots-events/ directory does not exist and cannot be created.');

if(!is_writeable('./out/bots-events/'))
	die('The ./out/bots-events/ directory is not writeable.');

if(!file_exists('./out/bots-actions/'))
	if(!mkdir('./out/bots-actions/', 0775))
		die('The ./out/bots-actions/ directory does not exist and cannot be created.');

if(!is_writeable('./out/bots-actions/'))
	die('The ./out/bots-actions/ directory is not writeable.');

$all_events = Extension_DevblocksEvent::getAll(true);

foreach($all_events as $event_ext) { /* @var $event_ext Extension_DevblocksEvent */
	// Skip local custom record macros
	if(DevblocksPlatform::strStartsWith($event_ext->id, 'event.macro.custom_record.'))
		continue;
	
	$trigger = new Model_TriggerEvent();
	$trigger->bot_id = 1;
	$trigger->event_point = $event_ext->id;
	$trigger->event_params = [];
	
	$event = new Model_DevblocksEvent($event_ext->id);
	
	@$event_ext->setEvent($event, $trigger);
	
	$event_actions = $event_ext->getActionExtensions($trigger) ?: [];
	$event_actions = array_filter($event_actions, function($action, $action_key) {
		if(@$action['deprecated'])
			return false;
		return !DevblocksPlatform::strStartsWith($action_key, 'set_cf_');
	}, ARRAY_FILTER_USE_BOTH);
	
	DevblocksPlatform::sortObjects($event_actions, '[label]');
	
	// [TODO] Use `scope` key

	$global_actions = $event_ext->getActions($trigger);
	$global_actions = array_diff_key($global_actions, $event_actions);
	
	$global_actions = array_filter($global_actions, function($action) {
		return @$action['deprecated'] ? false : true;
	});
	
	$out = <<< EOD
---
title: >-
  {$event_ext->manifest->name}
permalink: /docs/bots/events/{$event_ext->id}/
toc:
  title: "{$event_ext->manifest->name}"
  expand: Bots
jumbotron:
  title: "{$event_ext->manifest->name}"
  tagline: 
  breadcrumbs:
  -
    label: Docs &raquo;
    url: /docs/home/
  -
    label: Bots &raquo;
    url: /docs/bots/
  -
    label: Events &raquo;
    url: /docs/bots/events/
---

|---
|-|-
| **Name:** | {$event_ext->manifest->name}
| **Identifier (ID):** | {$event_ext->id}
| **Class:** | {$event_ext->manifest->class}
| **File:** | {$event_ext->manifest->getPlugin()->dir}/{$event_ext->manifest->file}

EOD;

	if($event_actions) {

$out .= <<< EOD

### Event Actions

|---
| Action | ID
|-|-

EOD;
	
		if(is_array($event_actions))
		foreach($event_actions as $action_key => $action) {
			$out .= sprintf("| [**%s**](%s) | `%s`\n",
				$action['label'],
				'/docs/bots/events/' . $event_ext->id  . '/actions/' . $action_key . '/',
				$action_key
			);
		}
	}
	
$out .= <<< EOD

### Global Actions

|---
| Action | ID
|-|-

EOD;
	
	if(is_array($global_actions))
	foreach($global_actions as $action_key => $action) {
		if(DevblocksPlatform::strStartsWith($action_key, 'set_cf_'))
			continue;
		
		$out .= sprintf("| [**%s**](%s) | `%s`\n",
			$action['label'],
			'/docs/bots/events/actions/' . $action_key . '/',
			$action_key
		);
	}

	$filename = sprintf('./out/bots-events/%s.md', $event_ext->id);
	
	echo "Wrote ", $filename, "\n";
	
	file_put_contents($filename, $out);
	
	foreach($global_actions as $action_key => $action) {
		if(DevblocksPlatform::strStartsWith($action_key, 'set_cf_'))
			continue;
		
		$action_uri = ltrim($action_key, '_');
		
		$filename = sprintf('./out/bots-actions/%s.md', $action_uri);
		
		if(!file_exists($filename)) {
			$out = <<< EOD
---
title: >-
  Bot Action: {$action['label']}
permalink: /docs/bots/events/actions/{$action_key}/
toc:
  expand: Bots
jumbotron:
  title: >-
    {$action['label']}
  tagline: 
  breadcrumbs:
  -
    label: Docs &raquo;
    url: /docs/home/
  -
    label: Bots &raquo;
    url: /docs/bots/
  -
    label: Events &raquo;
    url: /docs/bots/events/
  -
    label: Actions &raquo;
    url: /docs/bots/events/
---

|---
|-|-
| **Identifier (ID):** | {$action_key}
| **Event:** | (All)

EOD;

			if(array_key_exists('notes', $action) && $action['notes'])
				$out .= "\n" . $action['notes'] . "\n\n";
	
			if(array_key_exists('params', $action) && $action['params']) {
				$out .= <<< EOD

### Params

|---
| Req'd | Key | Type | Notes 
|:-:|-|-|-

EOD;
				foreach($action['params'] as $param_key => $param) {
					$out .= sprintf("| %s | `%s` | %s | %s\n",
						@$param['required'] ? '**x**' : '',
						$param_key,
						@$param['type'],
						@$param['notes']
					);
				}
			}

			file_put_contents($filename, $out);
			echo "Wrote ", $filename, "\n";
		}
	}
	
	foreach($event_actions as $action_key => $action) {
		if(DevblocksPlatform::strStartsWith($action_key, 'set_cf_'))
			continue;
		
		if(@$action['deprecated'])
			continue;
		
		$action_uri = ltrim($action_key, '_');
		
		$filename = sprintf('./out/bots-actions/%s/%s.md', $event_ext->id, $action_uri);
		
		if(!file_exists($filename)) {
			$out = <<< EOD
---
title: >-
  Bot Action: {$action['label']}
permalink: /docs/bots/events/{$event_ext->id}/actions/{$action_key}/
toc:
  expand: Bots
jumbotron:
  title: >-
    {$action['label']}
  tagline: 
  breadcrumbs:
  -
    label: Docs &raquo;
    url: /docs/home/
  -
    label: Bots &raquo;
    url: /docs/bots/
  -
    label: Events &raquo;
    url: /docs/bots/events/
  -
    label: >-
      {$event_ext->manifest->name} &raquo;
    url: /docs/bots/events/{$event_ext->id}/
  -
    label: Actions &raquo;
    url: /docs/bots/events/{$event_ext->id}/#event-actions
---

|---
|-|-
| **Identifier (ID):** | {$action_key}
| **Event:** | {$event_ext->manifest->id}

EOD;
		
			if(array_key_exists('notes', $action) && $action['notes'])
				$out .= "\n" . $action['notes'] . "\n\n";
			
			if(array_key_exists('params', $action) && $action['params']) {
				$out .= <<< EOD

### Params

|---
| Req'd | Key | Type | Notes
|:-:|-|-|-

EOD;
				foreach($action['params'] as $param_key => $param) {
					$out .= sprintf("| %s | `%s` | %s | %s\n",
						@$param['required'] ? '**x**' : '',
						$param_key,
						@$param['type'],
						@$param['notes']
					);
				}
			}
			
			if(!is_dir(dirname($filename)))
				mkdir(dirname($filename), 0775);
			
			file_put_contents($filename, $out);
			echo "Wrote ", $filename, "\n";
		}
	}
}

//echo "<pre>";
//echo $out;
//echo "</pre>";
//exit;