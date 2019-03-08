<?php
require(getcwd() . '/../../../../framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');

if(!file_exists('./out/records/') || !is_dir('./out/'))
	die('The ./out/records/ directory does not exist.');

if(!is_writeable('./out/records/'))
	die('The ./out/records/ directory is not writeable.');

$all_contexts = Extension_DevblocksContext::getAll(true);

foreach($all_contexts as $context_ext) {
	// Skip custom records
	if(DevblocksPlatform::strStartsWith($context_ext->id, 'contexts.custom_record.'))
		continue;
	
	// Skip internal record types
	if(in_array($context_ext->id, [
		Context_JiraIssue::ID,
		Context_JiraProject::ID,
		Context_WgmCerbLicense::ID,
	]))
		continue;
	
	$context_name = DevblocksPlatform::strLower($context_ext->manifest->name);
	$context_alias = DevblocksPlatform::strLower($context_ext->manifest->params['alias']);
	$context_aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest);
	$context_name_singular = DevblocksPlatform::strTitleCase($context_aliases['singular']);
	$context_name_plural = DevblocksPlatform::strTitleCase($context_aliases['plural']);
	
	$dao_fieldmap = $context_ext->getKeyMeta();
	ksort($dao_fieldmap);
	
	$custom_field_types = Model_CustomField::getTypes();
	
	$custom_field_types[Model_CustomField::TYPE_CHECKBOX] = 'Boolean';
	$custom_field_types[Model_CustomField::TYPE_SINGLE_LINE] = 'Text';
	$custom_field_types[Model_CustomField::TYPE_MULTI_LINE] = 'Text';
	$custom_field_types[Model_CustomField::TYPE_URL] = 'Text';
	$custom_field_types['bool'] = 'Boolean';
	$custom_field_types['time_mins'] = 'Minutes';
	$custom_field_types['time_secs'] = 'Seconds';
	$custom_field_types['url'] = 'Text';
	$custom_field_types['FT'] = 'Fulltext';
	$custom_field_types['WS'] = 'Watchers';
	
	if(false == ($context_view = $context_ext->getTempView())) {
		continue;
	}
	
	$fields = $context_view->getFields();
	$quick_search = $context_view->getQuickSearchFields();
	
	$out = <<< EOD
---
title: {$context_ext->manifest->name} Records
permalink: /docs/records/types/{$context_alias}/
toc:
  title: {$context_ext->manifest->name}
  expand: Records
jumbotron:
  title: {$context_ext->manifest->name}
  tagline: 
  breadcrumbs:
  -
    label: Docs &raquo;
    url: /docs/home/
  -
    label: Records &raquo;
    url: /docs/records/
  -
    label: Types &raquo;
    url: /docs/records/types/
---

|---
|-|-
| **Name (singular):** | {$context_name_singular}
| **Name (plural):** | {$context_name_plural}
| **Alias (uri):** | {$context_alias}
| **Identifier (ID):** | {$context_ext->id}

* TOC
{:toc}

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

|---
| Req'd | Field | Type | Notes
|:-:|-|-|-

EOD;
	
	foreach($dao_fieldmap as $record_key => $key_meta) {
		// Only editable fields
		if(@$key_meta['is_immutable'])
			continue;
		
		@$type = $key_meta['type'];
		@$notes = $key_meta['notes'];
		
		if(empty($type))
			continue;
		
		switch($type) {
			case 'bit':
				$type = 'boolean';
				
				$type = sprintf("[%s](/docs/records/fields/types/boolean/)",
					$type
				);
				break;
				
			case 'context':
				$type = sprintf("[%s](/docs/records/fields/types/context/)",
					$type
				);
				break;
				
			case 'extension':
				$type = sprintf("[%s](/docs/records/fields/types/extension/)",
					$type
				);
				break;
				
			case 'float':
				$type = sprintf("[%s](/docs/records/fields/types/float/)",
					$type
				);
				break;
				
			case 'image':
				$type = sprintf("[%s](/docs/records/fields/types/image/)",
					$type
				);
				break;
				
			case 'links':
				$type = sprintf("[%s](/docs/records/fields/types/links/)",
					$type
				);
				break;
				
			case 'number':
			case 'id':
			case 'uint':
				$type = 'number';
				
				$type = sprintf("[%s](/docs/records/fields/types/number/)",
					$type
				);
				break;
			
			case 'object':
				$type = sprintf("[%s](/docs/records/fields/types/object/)",
					$type
				);
				break;
				
			case 'string':
				$type = 'text';
				
				$type = sprintf("[%s](/docs/records/fields/types/text/)",
					$type
				);
				break;
				
			case 'timestamp':
				$type = 'timestamp';
				
				$type = sprintf("[%s](/docs/records/fields/types/timestamp/)",
					$type
				);
				break;
				
			case 'url':
				$type = sprintf("[%s](/docs/records/fields/types/url/)",
					$type
				);
				break;
		}
		
		$out .= sprintf("| %s | %s | %s | %s \n",
			@$key_meta['is_required'] ? '**x**' : ' ',
			@$key_meta['is_required'] ? sprintf("**`%s`**", $record_key) : sprintf("`%s`", $record_key),
			$type,
			$notes
		);
	}
	
	$labels = $values = [];
	CerberusContexts::getContext($context_ext->id, null, $labels, $values, '', true);
	
	$context_prefixes = [];
	
	foreach($values as $k => $v) {
		if(DevblocksPlatform::strEndsWith($k, '__context'))
			$context_prefixes[substr($k, 0, -8)] = $v;
	}
	
	uksort($context_prefixes, function($a, $b) {
		$len_a = strlen($a);
		$len_b = strlen($b);
		
		if($len_a == $len_b)
			return 0;
		
		return ($len_a > $len_b) ? -1 : 1;
	});
	
	$context_prefix_keys = array_keys($context_prefixes);
	
	$out .= <<< EOD

### Dictionary Placeholders

These [placeholders](/docs/bots/scripting/placeholders/) are available in [dictionaries](/docs/bots/behaviors/dictionaries/) for [bot behaviors](/docs/bots/behaviors/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

|---
| Field | Type | Description
|-|-|-

EOD;
	
	// Lazy-loaded placeholders
	
	$out_lazy = '';
	$lazy_keys = $context_ext->lazyLoadGetKeys();
	
	if($lazy_keys) {
		foreach($lazy_keys as $lazy_key => $lazy_meta) {
			$labels[$lazy_key] = $lazy_meta['label'];
			$values['_types'][$lazy_key] = $lazy_meta['type'];
		}
		
		ksort($labels);
	}
	
	// Output dictionary keys
	
	foreach($labels as $k => $label) {
		if(false !== strpos($k, 'custom_') && $k != 'custom_<id>')
			continue;
		
		$hits = 0;
		foreach($context_prefix_keys as $cpk) {
			if(DevblocksPlatform::strStartsWith($k, $cpk))
				$hits++;
		}
		
		if($hits > 1)
			continue;
		
		if(1 == $hits && !DevblocksPlatform::strEndsWith($k, ['_label']))
			continue;
		
		@$type = $values['_types'][$k];
		
		if(array_key_exists($type, $custom_field_types))
			$type = $custom_field_types[$type];
		
		$label = DevblocksPlatform::strTitleCase($label);
		$type = DevblocksPlatform::strTitleCase($type);
		
		$label_map = [
			'_label' => 'Label',
		];
		
		$label_type_map = [
			'_label' => 'Text',
		];
		
		$type_map = [
			'Id' => 'Number',
		];
		
		switch($context_ext->id) {
			case Context_Attachment::ID:
				$label_map['size'] = 'Size (Bytes)';
				$label_type_map['size'] = 'Number';
				break;
			case Context_Bot::ID:
				$label_type_map['config'] = 'Object';
				break;
			case Context_KbArticle::ID:
				$label_type_map['content'] = 'Text';
				break;
			case Context_Message::ID:
				$label_map['storage_size'] = 'Size (Bytes)';
				$label_type_map['storage_size'] = 'Number';
				break;
			case Context_ProjectBoard::ID:
				$label_type_map['params'] = 'Object';
				break;
			case Context_WebApiCredentials::ID:
				$label_type_map['params'] = 'Object';
				break;
			case Context_WebhookListener::ID:
				$label_type_map['extension_params'] = 'Object';
				break;
			case Context_WorkspaceList::ID:
				$label_type_map['columns'] = 'Object';
				$label_type_map['options'] = 'Object';
				$label_type_map['params'] = 'Object';
				$label_type_map['render_sort'] = 'Object';
				break;
			case Context_WorkspaceWidget::ID:
				$label_type_map['params'] = 'Object';
				break;
		}
		
		if(array_key_exists($k, $label_map))
			$label = $label_map[$k];
		
		if(array_key_exists($k, $label_type_map))
			$type = $label_type_map[$k];
		
		if(array_key_exists($type, $type_map))
			$type = $type_map[$type];
		
		if(DevblocksPlatform::strEndsWith($k, '__label')) {
			$k = substr($k, 0, -6);
			$type = 'Record';
			
			if(false != (@$context = $context_prefixes[$k])) {
				$context_mft = Extension_DevblocksContext::get($context, false);
				
				$label = sprintf("[%s](/docs/records/types/%s/)",
					trim($label),
					$context_mft->params['alias']
				);
			}
		}
		
		if(array_key_exists($k, $lazy_keys)) {
			$out_lazy .= sprintf("| `%s` | %s | %s\n",
				$k,
				DevblocksPlatform::strLower($type),
				$label
			);
			
		} else {
			$out .= sprintf("| `%s` | %s | %s\n",
				$k,
				DevblocksPlatform::strLower($type),
				$label
			);
		}
	};
	
	if($out_lazy) {
		$out .= <<< EOD

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/bots/behaviors/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

|---
| Field | Type | Description
|-|-|-

EOD;
		
		$out .= $out_lazy;
		unset($out_lazy);
	}
	
	$out .= <<< EOD
	
### Search Query Fields

These [filters](/docs/search/filters/) are available in {$context_name} [search queries](/docs/search/):

|---
| Field | Type | Description
|-|-|-

EOD;
	
	// Record-based exceptions
	switch($context_ext->id) {
		case Context_ContextActivityLog::ID:
			$quick_search['actor']['type'] = Model_CustomField::TYPE_SINGLE_LINE;
			$quick_search['target']['type'] = Model_CustomField::TYPE_SINGLE_LINE;
			
			foreach($quick_search as $k => $v) {
				if(DevblocksPlatform::strStartsWith($k, 'actor.')) {
					unset($quick_search[$k]);
					continue;
				}
				
				if(DevblocksPlatform::strStartsWith($k, 'target.')) {
					unset($quick_search[$k]);
					continue;
				}
			}
			
			$quick_search['actor.<type>'] = [
				'type' => 'Record',
				'options' => [
					'param_key' => SearchFields_ContextActivityLog::VIRTUAL_ACTOR,
				],
			];
			$quick_search['target.<type>'] = [
				'type' => 'Record',
				'options' => [
					'param_key' => SearchFields_ContextActivityLog::VIRTUAL_TARGET,
				],
			];
			
			ksort($quick_search);
			break;
			
		case Context_Attachment::ID:
			$quick_search['on']['type'] = Model_CustomField::TYPE_SINGLE_LINE;
			
			foreach($quick_search as $k => $v) {
				if(DevblocksPlatform::strStartsWith($k, 'on.')) {
					unset($quick_search[$k]);
					continue;
				}
			}
			
			$quick_search['on.<type>'] = [
				'type' => 'Record',
				'options' => [
					'param_key' => SearchFields_Attachment::VIRTUAL_ON,
				],
			];
			
			ksort($quick_search);
			break;
			
		case Context_Bot::ID:
			$quick_search['owner']['type'] = Model_CustomField::TYPE_SINGLE_LINE;
			
			foreach($quick_search as $k => $v) {
				if(DevblocksPlatform::strStartsWith($k, 'owner.')) {
					unset($quick_search[$k]);
					continue;
				}
			}
			
			$quick_search['owner.<type>'] = [
				'type' => 'Record',
				'options' => [
					'param_key' => SearchFields_Bot::VIRTUAL_OWNER,
				],
			];
			
			ksort($quick_search);
			break;
			
		case Context_Comment::ID:
			$quick_search['author']['type'] = Model_CustomField::TYPE_SINGLE_LINE;
			$quick_search['on']['type'] = Model_CustomField::TYPE_SINGLE_LINE;
			
			foreach($quick_search as $k => $v) {
				if(DevblocksPlatform::strStartsWith($k, 'author.')) {
					unset($quick_search[$k]);
					continue;
				}
				if(DevblocksPlatform::strStartsWith($k, 'on.')) {
					unset($quick_search[$k]);
					continue;
				}
			}
			
			$quick_search['author.<type>'] = [
				'type' => 'Record',
				'options' => [
					'param_key' => SearchFields_Comment::VIRTUAL_OWNER,
				],
			];
			
			$quick_search['on.<type>'] = [
				'type' => 'Record',
				'options' => [
					'param_key' => SearchFields_Comment::VIRTUAL_TARGET,
				],
			];
			
			ksort($quick_search);
			break;
			
		case Context_ContextScheduledBehavior::ID:
			$quick_search['on']['type'] = Model_CustomField::TYPE_SINGLE_LINE;
			
			foreach($quick_search as $k => $v) {
				if(DevblocksPlatform::strStartsWith($k, 'on.')) {
					unset($quick_search[$k]);
					continue;
				}
			}
			
			$quick_search['on.<type>'] = [
				'type' => 'Record',
				'options' => [
					'param_key' => SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET,
				],
			];
			
			ksort($quick_search);
			break;
			
	}
	
	foreach($quick_search as $k => $v) {
		if(DevblocksPlatform::strStartsWith($k, 'links.'))
			continue;
		
		@$param_key = $v['options']['param_key'];
		
		if($param_key && DevblocksPlatform::strStartsWith($param_key, 'cf_'))
			continue;
		
		if($k == 'text')
			continue;
		
		@$field = $fields[$param_key];
		
		if(false == (@$label = @$field->db_label)) {
			$label = str_replace('.', ' ', $k);
		}
		
		@$type = @$field->type ?: $v['type'];
		
		if(array_key_exists($type, $custom_field_types))
			$type = $custom_field_types[$type];
		
		$label = DevblocksPlatform::strTitleCase($label);
		$type = DevblocksPlatform::strTitleCase($type);
		
		if($k == 'links') {
			$type = 'Links';
			$label = 'Record Links';
		}
		
		else if($label == 'Params' && $type = '') {
			$type = 'Map';
		}
		
		else if(
			@$k != 'id'
			&& @$v['examples'][0]['type'] == 'chooser'
			&& @$v['examples'][0]['context']
			) {
				$context = $v['examples'][0]['context'];
				$context_mft = Extension_DevblocksContext::get($context, false);
				
				if(DevblocksPlatform::strStartsWith($context_mft->id, 'contexts.custom_record.'))
					continue;
				
				$type = 'Chooser';
				
				$label = sprintf("[%s](/docs/records/types/%s/)",
					trim($label),
					$context_mft->params['alias']
				);
		}
		
		else if(
			@$v['examples'][0]['type'] == 'search'
			&& @$v['examples'][0]['context']
			) {
				$context = $v['examples'][0]['context'];
				$context_mft = Extension_DevblocksContext::get($context, false);
				
				if(DevblocksPlatform::strStartsWith($context_mft->id, 'contexts.custom_record.'))
					continue;
				
				$type = 'Record';
				
				$label = sprintf("[%s](/docs/records/types/%s/)",
					trim($label),
					$context_mft->params['alias']
				);
		} else {
			
			// Record-based exceptions
			switch($context_ext->id) {
				case Context_ContextActivityLog::ID:
					$label_map = [
						'actor' => 'Actor Type',
						'target' => 'Target Type',
					];
					
					if(array_key_exists($k, $label_map))
						$label = $label_map[$k];
					break;
					
				case Context_Attachment::ID:
					$label_map = [
						'on' => 'On Type',
					];
					
					if(array_key_exists($k, $label_map))
						$label = $label_map[$k];
					break;
					
				case Context_Bot::ID:
					$label_map = [
						'owner' => 'Owner Type',
					];
					
					if(array_key_exists($k, $label_map))
						$label = $label_map[$k];
					break;
					
				case Context_Comment::ID:
					$label_map = [
						'on' => 'On Type',
					];
					
					if(array_key_exists($k, $label_map))
						$label = $label_map[$k];
					break;
			}
		}
		
		switch($type) {
			case 'Boolean':
				$type = sprintf("[%s](/docs/search/filters/booleans/)",
					$type
				);
				break;
			case 'Date':
				$type = sprintf("[%s](/docs/search/filters/dates/)",
					$type
				);
				break;
			case 'Chooser':
				$type = sprintf("[%s](/docs/search/filters/choosers/)",
					$type
				);
				break;
			case 'Fulltext':
				$type = sprintf("[%s](/docs/search/filters/fulltext/)",
					$type
				);
				break;
			case 'Links':
				$type = sprintf("[%s](/docs/search/filters/links/)",
					$type
				);
				break;
			case 'Number':
				$type = sprintf("[%s](/docs/search/filters/numbers/)",
					$type
				);
				break;
			case 'Record':
				$type = sprintf("[%s](/docs/search/deep-search/)",
					$type
				);
				break;
			case 'Text':
				$type = sprintf("[%s](/docs/search/filters/text/)",
					$type
				);
				break;
			case 'Watchers':
				$type = sprintf("[%s](/docs/search/filters/watchers/)",
					$type
				);
				break;
		}
		
		//var_dump($field);
		$out .= sprintf("| `%s:` | %s | %s\n",
			$k,
			DevblocksPlatform::strLower($type),
			$label
		);
	}
	
	if(false == (@$view_class = $context_ext->getViewClass()))
		continue;
	
	$view = new $view_class();
	
	$columns = $view->getColumnsAvailable();
	
	foreach($columns as $column_key => $column) {
		if(DevblocksPlatform::strStartsWith($column_key, 'cf_')) {
			unset($columns[$column_key]);
			continue;
		}
		
		if(!$column->db_label) {
			unset($columns[$column_key]);
		}
	}
	
	if($context_ext->hasOption('custom_fields')) {
		$columns['cf_<id>'] = new DevblocksSearchField('cf_<id>', '', '', '[Custom field](/docs/records/types/custom_field/)');
	}
	
	if($columns) {
		ksort($columns);
		
		$out .= <<< EOD
	
### Worklist Columns

These columns are available on {$context_name} [worklists](/docs/worklists/):

|---
| Column | Description
|-|-

EOD;
	
		foreach($columns as $column_key => $column) {
			$out .= sprintf("| `%s` | %s\n",
				$column_key,
				DevblocksPlatform::strTitleCase($column->db_label)
			);
		}
	}
	
	$out .= <<< EOD

<div class="section-nav">
	<div class="left">
		<a href="/docs/records/types/" class="prev">&lt; Record Types</a>
	</div>
	<div class="right align-right">
	</div>
</div>
<div class="clear"></div>
EOD;
	
	$filename = sprintf('./out/records/%s.md', $context_alias);
	
	echo "Wrote ", $filename, "\n";
	
	file_put_contents($filename, $out);
}

/*
echo "<pre>";
echo $out;
echo "</pre>";
*/
exit;