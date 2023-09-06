<?php
require(getcwd() . '/../../../../framework.config.php');
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');
require_once(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');
DevblocksPlatform::setStateless(true);

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

if(!file_exists('./out/points-include/'))
	mkdir('./out/points-include/');

$plugins = DevblocksPlatform::getPluginRegistry();

DevblocksPlatform::sortObjects($plugins, 'name');

$extension_point_meta = DevblocksPlatform::getExtensionPoints();

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
		
		if($point == 'cerb.webhooks.listener.engine')
			continue;
		
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
				//$param->hasType() ? ($param->getType() . ' ') : '',
				'',
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