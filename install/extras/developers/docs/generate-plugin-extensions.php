<?php
require(getcwd() . '/../../../../framework.config.php');
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');
require_once(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');

if(!file_exists('./out/'))
	mkdir('./out/');

if(!file_exists('./out/') || !is_dir('./out/'))
	die('The ./out/ directory does not exist.');

if(!is_writeable('./out/'))
	die('The ./out/ directory is not writeable.');

if(!file_exists('./out/plugins/'))
	mkdir('./out/plugins/');

if(!file_exists('./out/extensions/'))
	mkdir('./out/extensions/');

if(!file_exists('./out/points/'))
	mkdir('./out/points/');

$plugins = DevblocksPlatform::getPluginRegistry();

DevblocksPlatform::sortObjects($plugins, 'name');

$extension_point_meta = [
	'cerberusweb.plugin.setup' => [
		'label' => 'Plugin Setup',
		'class' => 'Extension_PluginSetup',
		'examples' => [],
		],
	'cerb.custom_field' => [
		'label' => 'Custom Field Type',
		'class' => 'Extension_CustomField',
		'examples' => [],
		'extensible' => true,
		],
	'cerb.portal' => [
		'label' => 'Portal',
		'class' => 'Extension_CommunityPortal',
		'examples' => [],
		'extensible' => true,
		],
	'cerb.portal.page' => [
		'label' => 'Portal Page',
		'class' => 'Extension_PortalPage',
		'examples' => [],
		'extensible' => true,
		],
	'cerb.portal.widget' => [
		'label' => 'Portal Widget',
		'class' => 'Extension_PortalWidget',
		'examples' => [],
		'extensible' => true,
		],
	'cerb.profile.tab' => [
		'label' => 'Profile Tab Type',
		'class' => 'Extension_ProfileTab',
		'examples' => [],
		'extensible' => true,
		],
	'cerb.profile.tab.widget' => [
		'label' => 'Profile Widget Type',
		'class' => 'Extension_ProfileWidget',
		'examples' => [],
		'extensible' => true,
		],
	'cerb.connected_service.provider' => [
		'label' => 'Connected Service Provider',
		'class' => 'Extension_ConnectedServiceProvider',
		'examples' => [],
		'extensible' => true,
		],
	'cerb.webhooks.listener.engine' => [
		'label' => 'Webhook Listener Type',
		'class' => 'Extension_WebhookListenerEngine',
		'examples' => [],
		'extensible' => true,
		],
	'cerberusweb.calendar.datasource' => [
		'label' => 'Calendar Datasource',
		'class' => 'Extension_CalendarDatasource',
		'examples' => [],
		],
	'cerberusweb.cron' => [
		'label' => 'Scheduled Job',
		'class' => 'CerberusCronPageExtension',
		'examples' => [],
		],
	'cerberusweb.datacenter.sensor' => [
		'label' => 'Sensor Type',
		'class' => 'Extension_Sensor',
		'examples' => [],
		],
	'cerberusweb.mail.transport' => [
		'label' => 'Mail Transport Type',
		'class' => 'Extension_MailTransport',
		'examples' => [],
		'extensible' => true,
		],
	'cerberusweb.message.toolbaritem' => [
		'label' => 'Message Toolbar Item',
		'class' => 'Extension_MessageToolbarItem',
		'examples' => [],
		],
	'cerberusweb.page' => [
		'label' => 'Page Type',
		'class' => 'CerberusPageExtension',
		'examples' => [],
		],
	'cerberusweb.renderer.prebody' => [
		'label' => 'Prebody Renderer',
		'class' => 'Extension_AppPreBodyRenderer',
		'examples' => [],
		],
	'cerberusweb.reply.toolbaritem' => [
		'label' => 'Reply Toolbar Item',
		'class' => 'Extension_ReplyToolbarItem',
		'examples' => [],
		],
	'cerberusweb.rest.controller' => [
		'label' => 'Rest API Controller',
		'class' => 'Extension_RestController',
		'examples' => [],
		],
	'cerberusweb.ui.context.profile.script' => [
		'label' => 'Profile Script',
		'class' => 'Extension_ContextProfileScript',
		'examples' => [],
		],
	'cerberusweb.ui.page.menu.item' => [
		'label' => 'Page Menu Item',
		'class' => 'Extension_ContextProfileScript',
		'examples' => [],
		],
	'cerberusweb.ui.page.section' => [
		'label' => 'Page Section',
		'class' => 'Extension_PageSection',
		'examples' => [],
		],
	'cerberusweb.ui.workspace.page' => [
		'label' => 'Workspace Page Type',
		'class' => 'Extension_WorkspacePage',
		'examples' => [],
		'extensible' => true,
		],
	'cerberusweb.ui.workspace.tab' => [
		'label' => 'Workspace Tab Type',
		'class' => 'Extension_WorkspaceTab',
		'examples' => [],
		'extensible' => true,
		],
	'cerberusweb.ui.workspace.widget' => [
		'label' => 'Workspace Widget Type',
		'class' => 'Extension_WorkspaceWidget',
		'examples' => [],
		'extensible' => true,
		],
	'cerberusweb.ui.workspace.widget.datasource' => [
		'label' => 'Workspace Widget Datasource',
		'class' => 'Extension_WorkspaceWidgetDatasource',
		'examples' => [],
		'extensible' => true,
		],
	'devblocks.cache.engine' => [
		'label' => 'Cache Engine',
		'class' => 'Extension_DevblocksCacheEngine',
		'examples' => [],
		'extensible' => true,
		],
	'devblocks.context' => [
		'label' => 'Record Type',
		'class' => 'Extension_DevblocksContext',
		'examples' => [],
		],
	'devblocks.controller' => [
		'label' => 'Controller',
		'class' => 'DevblocksControllerExtension',
		'examples' => [],
		],
	'devblocks.event' => [
		'label' => 'Bot Event',
		'class' => 'Extension_DevblocksEvent',
		'examples' => [],
		],
	'devblocks.event.action' => [
		'label' => 'Bot Action',
		'class' => 'Extension_DevblocksEventAction',
		'examples' => [],
		],
	'devblocks.listener.event' => [
		'label' => 'Event Listener',
		'class' => 'DevblocksEventListenerExtension',
		'examples' => [],
		],
	'devblocks.listener.http' => [
		'label' => 'Http Request Listener',
		'class' => 'DevblocksHttpResponseListenerExtension',
		'examples' => [],
		],
	'devblocks.search.engine' => [
		'label' => 'Search Engine',
		'class' => 'Extension_DevblocksSearchEngine',
		'examples' => [],
		],
	'devblocks.search.schema' => [
		'label' => 'Search Schema',
		'class' => 'Extension_DevblocksSearchSchema',
		'examples' => [],
		],
	'devblocks.storage.engine' => [
		'label' => 'Storage Engine',
		'class' => 'Extension_DevblocksStorageEngine',
		'examples' => [],
		],
	'devblocks.storage.schema' => [
		'label' => 'Storage Schema',
		'class' => 'Extension_DevblocksStorageSchema',
		'examples' => [],
		],
	'usermeet.login.authenticator' => [
		'label' => 'Support Center Login Authenticator',
		'class' => 'Extension_ScLoginAuthenticator',
		'examples' => [],
		],
	'usermeet.sc.controller' => [
		'label' => 'Support Center Controller',
		'class' => 'Extension_UmScController',
		'examples' => [],
		],
	'usermeet.sc.rss.controller' => [
		'label' => 'Support Center RSS Feed',
		'class' => 'Extension_UmScRssController',
		'examples' => [],
		],
];

DevblocksPlatform::sortObjects($extension_point_meta, '[label]');

$out = "";

foreach($plugins as $plugin_id => $plugin) {
	if(in_array($plugin_id, [
		'example.plugin',
		'mediafly.gainsight',
		'wgm.cerb_licensing',
		'wgm.cerb5.plugin_portal',
	]))
		continue;
	
	$manifest = DevblocksPlatform::readPluginManifest(APP_PATH . '/' .$plugin->dir, true);
	
	/*
	if(DevblocksPlatform::strStartsWith($plugin->dir, 'libs/devblocks')) {
		$plugin->repo_url = 'https://github.com/cerb/cerb-release/tree/master/' . $plugin->dir . '/';
	} else if(DevblocksPlatform::strStartsWith($plugin->dir, 'features/')) {
		$plugin->repo_url = 'https://github.com/cerb/cerb-release/tree/master/' . $plugin->dir . '/';
	} else if(DevblocksPlatform::strStartsWith($plugin->dir, 'storage/')) {
		$plugin->repo_url = sprintf('https://github.com/cerb-plugins/%s/tree/9.0/',
			$plugin_id
		);
	} else {
		$plugin->repo_url = '';
	}
	*/
	
	$extension_sets = [];
	
	foreach($manifest->extensions as $ext) {
		//if(!isset($extension_sets[$ext->point]))
		//	$extension_sets[$ext->point] = [];
		
		$extension_sets[$ext->point][$ext->id] = [
			'label' => $ext->name,
			'plugin_id' => $plugin_id,
			'file' => $ext->file,
			'class' => $ext->class,
		];
	}
	
	$out = <<< EOD
---
title: "Plugin: {$manifest->name}"
permalink: /docs/plugins/{$manifest->id}/
toc:
  title: "{$manifest->name}"
  expand: Plugins
jumbotron:
  title: "{$manifest->name}"
  tagline: 
  breadcrumbs:
  -
    label: Docs &raquo;
    url: /docs/home/
  -
    label: Plugins &raquo;
    url: /docs/plugins/
---

|---
|-|-
| **Name:** | {$manifest->name}
| **Identifier (ID):** | {$manifest->id}
| **Author:** | {$manifest->author}
| **Path:** | {$manifest->dir}/
| **Image:** | <img src="/assets/images/plugins/{$manifest->id}.png" class="screenshot">

{$manifest->description}

* TOC
{:toc}

# Extensions

EOD;

	foreach($extension_point_meta as $point => $point_meta) {
		@$set = $extension_sets[$point];
		
		if(!$set)
			continue;
		
		DevblocksPlatform::sortObjects($set, '[label]');
		
		$out .= <<< EOD

### {$extension_point_meta[$point]['label']}


EOD;
		
		$extension_point_meta[$point]['examples'][$plugin->id] = [];
		
		$lines = [];
	
		foreach($set as $ext_id => $ext) {
			$extension_point_meta[$point]['examples'][$plugin_id][$ext_id] = $ext;
			
			if(array_key_exists('extensible', $point_meta)) {
				$label = sprintf("[**%s**](/docs/plugins/extensions/%s/)",
					$ext['label'],
					$ext_id
				);
			} else {
				$label = sprintf("%s",
					$ext['label']
				);
			}
			
			$lines[] = sprintf("| %s | `%s`",
				$label,
				$ext_id
			);
		}
		
		if($lines) {
			sort($lines);
			$out .= implode("\n", $lines) . "\n";
		} else {
			$out .= "(none)\n";
		}
		
		$out .= "\n";
	}
	
	$out .= <<< EOD

<div class="section-nav">
	<div class="left">
		<a href="/docs/plugins/#plugins" class="prev">&lt; Plugins</a>
	</div>
	<div class="right align-right">
	</div>
</div>
<div class="clear"></div>
EOD;
	
	file_put_contents('./out/plugins/' . $manifest->id . '.md', $out);
	
	echo sprintf("Wrote ./plugins/" . $manifest->id . ".md\n");
}

foreach($extension_point_meta as $point => $point_meta) {
		$out = <<< EOD
---
title: "Extension Point: {$point_meta['label']}"
permalink: /docs/plugins/extensions/points/{$point}/
toc:
  title: "{$point_meta['label']}"
  expand: Plugins
jumbotron:
  title: "{$point_meta['label']}"
  tagline: 
  breadcrumbs:
  -
    label: Docs &raquo;
    url: /docs/home/
  -
    label: Plugins &raquo;
    url: /docs/plugins/
  -
    label: Extension Points &raquo;
    url: /docs/plugins/extensions/
---

|---
|-|-
| **Name:** | {$point_meta['label']}
| **Identifier (ID):** | {$point}


EOD;
	
	$include_out = "# Manifest\n\n";
	
	foreach($point_meta['examples'] as $example_plugin_id => $examples) {
		@$example_plugin = $plugins[$example_plugin_id];
		
		if($point == 'cerb.webhooks.listener.engine') {
			//var_dump($examples);
		}
		
		if(empty($example_plugin))
			continue;
		
		// [TODO] Find plugin.xml path
		$plugin_xml_path = APP_PATH . '/' . $example_plugin->dir . '/plugin.xml';
		
		$doc = new DOMDocument();
		$doc->load($plugin_xml_path);
		
		$xpath = new DOMXPath($doc);
		
		$xpath_query = sprintf('//*[@point="%s"]', $point);
		$elements = $xpath->query($xpath_query);
		
		if(empty($elements))
			continue;
		
		$include_out .= <<< EOD
<pre>
<code class="language-xml">
		
EOD;
		$include_out .= htmlentities($doc->saveXML($elements[0]));
		
		$include_out .= <<< EOD

</code>
</pre>


EOD;
		//file_put_contents(sprintf('out/points-include-manifests/%s.md', $point), $manifest_xml);
		break;
	}
	
	//$out .= file_get_contents(sprintf('include/points/manifests/%s.md', $point));
	
	$include_out .= <<< EOD
# Code


EOD;

	$class_name = $point_meta['class'];
	
	if(!class_exists($class_name)) {
		continue;
	}
	
	$class = new ReflectionClass($class_name);
	
	$include_out .= <<< EOD
<pre>
<code class="language-php">

EOD;
	
	$include_out .= sprintf("class ExampleExtension extends %s",
		$class->getName()
	);
	
	$include_out .= " {\n";
	
	$methods = $class->getMethods();
	
	DevblocksPlatform::sortObjects($methods, 'name');
	
	foreach($methods as $method) {
		$params = $method->getParameters();
		
		if($method->getDeclaringClass() != $class)
			continue;
		
		$comment = $method->getDocComment();
		
		// Skip internal methods
		if(false !== strstr($comment, '@internal'))
			continue;
		
		$params_out = [];
		
		foreach($params as $param) {
			$params_out[] = sprintf("%s\$%s%s",
				$param->hasType() ? ($param->getType() . ' ') : '',
				$param->getName(),
				//$param->isDefaultValueAvailable() ? ('=' . $param->getDefaultValue()) : ''
				''
			);
		}
		
		$include_out .= sprintf("%s\t%s %sfunction %s(%s)\n\n",
			$comment ? sprintf("\t%s\n", $comment) : '',
			$method->isPublic() ? 'public' : ($method->isProtected() ? 'protected' : 'private'),
			$method->isStatic() ? 'static ' : '',
			$method->getName(),
			implode(', ', $params_out)
		);
	}
	
	$include_out .= <<< EOD
}
</code>
</pre>


EOD;
	
	file_put_contents(sprintf('out/points-include/%s.md', $point), $include_out);
	
	//$out .= file_get_contents(sprintf('include/points/%s.md', $point));
	
	$out .= <<< EOD
{% include docs/plugins/points/{$point}.md %}

# Extensions


EOD;

	$lines = [];
		
	foreach($plugins as $plugin_id => $plugin) {
		@$examples = $point_meta['examples'][$plugin_id];
		
		if(!$examples)
			continue;
		
		foreach($examples as $ext_id => $example) {
			if(array_key_exists('extensible', $point_meta)) {
				$label = sprintf("[**%s**](/docs/plugins/extensions/%s/)",
					$example['label'],
					$ext_id
				);
			} else {
				$label = sprintf("%s",
					$example['label']
				);
			}
			
			$lines[] = sprintf("| %s | `%s`",
				$label,
				$ext_id
			);
		}
	}

	if($lines) {
		sort($lines);
		$out .= implode("\n", $lines) . "\n";
	} else {
		$out .= "(none)\n";
	}
	
	$out .= <<< EOD

<div class="section-nav">
	<div class="left">
		<a href="/docs/plugins/extensions/#extension-points" class="prev">&lt; Extension Points</a>
	</div>
	<div class="right align-right">
	</div>
</div>
<div class="clear"></div>
EOD;
	
	file_put_contents('./out/points/' . $point . '.md', $out);
	
	echo sprintf("Wrote ./points/" . $point . ".md\n");
	
	if(array_key_exists('extensible', $point_meta)) {
		foreach($plugins as $plugin_id => $plugin) {
			@$examples = $point_meta['examples'][$plugin_id];
			
			if(!$examples)
				continue;
		
			foreach($examples as $ext_id => $example) {
				$out = <<< EOD
---
title: "Extension: {$example['label']}"
permalink: /docs/plugins/extensions/{$ext_id}/
toc:
  title: "{$example['label']}"
  expand: Plugins
jumbotron:
  title: "{$example['label']}"
  tagline: 
  breadcrumbs:
  -
    label: Docs &raquo;
    url: /docs/home/
  -
    label: Plugins &raquo;
    url: /docs/plugins/
  -
    label: Extension Points &raquo;
    url: /docs/plugins/extensions/
  -
    label: {$point_meta['label']} &raquo;
    url: /docs/plugins/extensions/points/{$point}
---

|---
|-|-
| **Name:** | {$example['label']}
| **Identifier (ID):** | {$ext_id}
| **Plugin:** | [{$example['plugin_id']}](/docs/plugins/{$example['plugin_id']}/)
| **File:** | {$example['file']}
| **Class:** | {$example['class']}


EOD;
		
				file_put_contents('./out/extensions/' . $ext_id . '.md', $out);
				echo sprintf("Wrote ./extensions/" . $ext_id . ".md\n");
			}
		}
	}
}

//echo "<pre>\n";
//echo htmlentities($out);
//echo "</pre>\n";

//ksort($extension_points);

exit;