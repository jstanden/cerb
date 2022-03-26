<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */
class Cerb_Packages {
	private function __construct() {}
	
	static function loadPackageFromJson(&$json_string) {
		if(!is_array($json_string) && false == ($json = json_decode($json_string ?? '', true))) {
			throw new Exception_DevblocksValidationError("Invalid JSON: " . json_last_error_msg());
		}
		
		if(!isset($json['package']))
			throw new Exception_DevblocksValidationError("Invalid package JSON");
			
		$package = $json['package'];
		
		// Requirements
		$requires = $package['requires'] ?? null;
		
		if(is_array($requires)) {
			$target_version = $requires['cerb_version'] ?? null;
			$target_plugins = $requires['plugins'] ?? null;
			
			if(!empty($target_version) && is_string($target_version)) {
				if(!version_compare(APP_VERSION, $target_version, '>='))
					throw new Exception_DevblocksValidationError(sprintf("This package requires Cerb version %s or later.", $target_version));
			}
			
			if(is_array($target_plugins))
			foreach($target_plugins as $target_plugin_id) {
				if(!DevblocksPlatform::isPluginEnabled($target_plugin_id))
					throw new Exception_DevblocksValidationError(sprintf("This package requires the %s plugin to be installed and enabled.", $target_plugin_id));
			}
		}
		
		return $json;
	}
	
	static function getPromptsFromJson($json) {
		if(!is_array($json))
			throw new Exception_DevblocksValidationError("Invalid package JSON");
		
		$configure = $json['package']['configure'] ?? null;
		$config_prompts = $configure['prompts'] ?? null;
		
		if(is_array($config_prompts) && $config_prompts)
			return $config_prompts;
		
		return [];
	}
	
	/**
	 * @param array $json
	 * @param array $prompts
	 * @param array $records_created
	 * @param array $records_modified
	 * @return bool|null
	 * @throws Exception_DevblocksValidationError
	 */
	static function importFromJson(array $json, array $prompts=[], array &$records_created=null, array &$records_modified=null) {
		$event = DevblocksPlatform::services()->event();
		
		if(!is_array($json))
			throw new Exception_DevblocksValidationError("Invalid package JSON");
		
		$placeholders = [];
		
		// Pre-import configuration
		$configure = $json['package']['configure'] ?? null;
		$config_prompts = $configure['prompts'] ?? null;
		$config_placeholders = $configure['placeholders'] ?? null;
		$config_options = array_merge(
			[
				'disable_events' => false,
			],
			($configure['options'] ?? null) ?: []
		);
		
		if(is_array($config_prompts) && $config_prompts) {
			foreach($config_prompts as $config_prompt) {
				$key = $config_prompt['key'] ?? null;
				
				if(!$key)
					throw new Exception_DevblocksValidationError(sprintf("Prompt key is missing."));
				
				$value = $prompts[$key] ?? null;
				
				switch($config_prompt['type']) {
					case 'chooser':
						@$is_single = $config_prompt['params']['single'] ?: false;
						
						if($is_single && 0 == strlen($value)) {
							throw new Exception_DevblocksValidationError(sprintf("'%s' (%s) is required.", $config_prompt['label'], $key));
							
						} else if(!$is_single && empty($value)) {
							throw new Exception_DevblocksValidationError(sprintf("'%s' (%s) is required.", $config_prompt['label'], $key));
						}
						
						$placeholders[$key] = $value;
						
						// If the key ends with '_id', allow lazy loading of all context placeholders
						if(DevblocksPlatform::strEndsWith($key, '_id')
							&& isset($config_prompt['params']['context'])
							&& @$config_prompt['params']['single']) {
								$context_key = substr($key, 0, -3);
								$placeholders[$context_key . '__context'] = $config_prompt['params']['context'];
						}
						
						break;
						
					case 'picklist':
						$options = $config_prompt['params']['options'] ?? null;
						
						if(!is_array($options) || !in_array($value, $options))
							throw new Exception_DevblocksValidationError(sprintf("'%s' (%s) is required.", $config_prompt['label'], $key));
						
						if($config_prompt['params']['multiple'] ?? null) {
						} else {
							$placeholders[$key] = $value;
						}
						break;
						
					case 'text':
						if(0 == strlen($value))
							throw new Exception_DevblocksValidationError(sprintf("'%s' (%s) is required.", $config_prompt['label'], $key));
						
						$placeholders[$key] = $value;
						break;
				}
			}
		}
		
		if(is_array($config_placeholders) && $config_placeholders)
		foreach($config_placeholders as $config_placeholder) {
			$key = $config_placeholder['key'] ?? null;
			
			if(!$key)
				throw new Exception_DevblocksValidationError(sprintf("Placeholder key is missing."));
			
			switch($config_placeholder['type']) {
				case 'data_query':
					$data = DevblocksPlatform::services()->data();
					$tpl_builder = DevblocksPlatform::services()->templateBuilder();
					
					$data_query = $config_placeholder['params']['query'] ?? null;
					$selector = $config_placeholder['params']['selector'] ?? null;
					$validate = $config_placeholder['params']['validate'] ?? null;
					$format = $config_placeholder['params']['format'] ?? null;
					
					$error = null;
					
					if(false == ($results = $data->executeQuery($data_query, [], $error)))
						throw new Exception_DevblocksValidationError($error);
					
					if($validate) {
						$dict = DevblocksDictionaryDelegate::instance([
							'results' => $results,
						]);

						if(false === ($error = $tpl_builder->build($validate, $dict)))
							throw new Exception_DevblocksValidationError();
						
						$error = trim($error);
						
						if($error) {
							throw new Exception_DevblocksValidationError($error);
						}
					}
					
					if($selector) {
						$dict = DevblocksDictionaryDelegate::instance([
							'results' => $results,
						]);
						
						if(false === ($out = $tpl_builder->build($selector, $dict)))
							throw new Exception_DevblocksValidationError();
						
						if('json' == DevblocksPlatform::strLower($format)) {
							$placeholders[$key] = json_decode($out, true);
						} else {
							$placeholders[$key] = $out;
						}
						
					} else {
						$placeholders[$key] = $results['data'];
					}
					
					break;
				
				case 'random':
					$length = @$config_placeholder['params']['length'] ?: 8;
					$placeholders[$key] = CerberusApplication::generatePassword($length);
					break;
			}
		}
		
		$uids = [];
		$records_created = [];
		$records_modified = [];
		
		if($config_options['disable_events'])
			$event->disable();
		
		self::_packageCreateCustomRecords($json, $uids, $records_created, $records_modified, $placeholders);
		self::_packageFilterExcluded($json, $uids, $records_created, $records_modified, $placeholders);
		self::_packageValidate($json, $uids, $records_created, $records_modified, $placeholders);
		self::_packageGenerateIds($json, $uids, $records_created, $records_modified, $placeholders);
		self::_packageImport($json, $uids, $records_created, $records_modified);
		
		// Flush the entire cache
		DevblocksPlatform::services()->cache()->clean();
		
		if($config_options['disable_events'])
			$event->enable();
		
		return true;
	}
	
	private static function _packageCreateCustomRecords(&$json, &$uids, &$records_created, &$records_modified, &$placeholders) {
		$records = $json['records'] ?? [];
		
		// Only keep custom records
		if(is_array($records))
		$custom_records = array_filter($records, function($record) {
			if(!isset($record['_context']))
				return false;
			
			return in_array($record['_context'], ['custom_record', CerberusContexts::CONTEXT_CUSTOM_RECORD]);
		});
		
		if(empty($custom_records))
			return;
		
		$custom_records_json = [
			'records' => $custom_records,
		];
		
		self::_packageFilterExcluded($custom_records_json, $uids, $records_created, $records_modified, $placeholders);
		self::_packageValidate($custom_records_json, $uids, $records_created, $records_modified, $placeholders);
		self::_packageGenerateIds($custom_records_json, $uids, $records_created, $records_modified, $placeholders);
	}
	
	private static function _packageFilterExcluded(&$json, &$uids, &$records_created, &$records_modified, &$placeholders) {
		// Prepare the template builder
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$lexer = array(
			'tag_comment'   => array('{{#', '#}}'),
			'tag_block'     => array('{{%', '%}}'),
			'tag_variable'  => array('{{{', '}}}'),
			'interpolation' => array('#{{', '}}'),
		);
		
		foreach($json as $object_type => $objects) {
			if($object_type == 'package')
				continue;
			
			if(is_array($objects))
			foreach($objects as $record_idx => $record) {
				$uid_record = $record['uid'] ?? null;
				
				if(!array_key_exists('_exclude', $record))
					continue;
				
				$exclude_template = $record['_exclude'];
				unset($json[$object_type][$record_idx]['_exclude']);
				
				if(false === ($result = $tpl_builder->build($exclude_template, $placeholders, $lexer)))
					throw new Exception_DevblocksValidationError(sprintf("Invalid _exclude template: record (%s) %s", $uid_record, implode(',', $tpl_builder->getErrors())));
				
				// If the template is true, remove this package record from processing
				if($result)
					unset($json[$object_type][$record_idx]);
			}
		}
	}
	
	/**
	 * @param $json
	 * @param $uids
	 * @param $records_created
	 * @param $records_modified
	 * @param $placeholders
	 * @throws Exception_DevblocksValidationError
	 */
	private static function _packageValidate(&$json, &$uids, &$records_created, &$records_modified, &$placeholders) {
		$records = $json['records'] ?? [];
		
		// Validate records
		if(is_array($records))
		foreach($records as $record) {
			$keys_to_require = ['uid','_context'];
			$diff = array_diff_key(array_flip($keys_to_require), $record);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: record (%s) is missing properties (%s)", @$record['uid'], implode(', ', array_keys($diff))));
			
			$uid_record = $record['uid'];
			
			// If we already processed this, ignore it here
			if(isset($uids[$uid_record]))
				continue;
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($record['_context'], true)))
				throw new Exception_DevblocksValidationError(sprintf("Unknown context '%s' on record (%s).", $record['_context'], $record['uid']));
			
			$fields = $custom_fields = $dict = [];
			$error = null;
			
			if(is_array($record))
			foreach($record as $key => $value) {
				// Ignore internal keys
				if(in_array($key, ['_context','uid'])) {
					continue;
				}
				
				// Ignore keys or values with unfilled placeholders
				if(false !== strstr($key,'{{{')) {
					continue;
				}
				
				$dict[$key] = $value;
			}
			
			if(!$context_ext->getDaoFieldsFromKeysAndValues($dict, $fields, $custom_fields, $error))
				throw new Exception_DevblocksValidationError(sprintf("Error validating record (%s): %s", $record['uid'], $error));
			
			if(false == ($dao_class = $context_ext->getDaoClass()))
				throw new Exception_DevblocksValidationError(sprintf("Error validating record (%s): %s", $record['uid'], "Can't load DAO class."));
			
			$excludes = [];
			
			if(is_array($fields))
			foreach($fields as $key => $value) {
				// Bypass the dynamic value in this phase
				if(is_string($value) && false !== strstr($value,'{{{')) {
					$excludes[] = $key;
				}
			}
			
			if(!$dao_class::validate($fields, $error, null, $excludes))
				throw new Exception_DevblocksValidationError(sprintf("Error validating record (%s): %s", $record['uid'], $error));
		}
		
		//$settings = $json['settings'] ?? null;
		//$worker_prefs = $json['worker_prefs'] ?? null;
		
		$custom_fieldsets = $json['custom_fieldsets'] ?? null;
		
		if(is_array($custom_fieldsets))
		foreach($custom_fieldsets as $custom_fieldset) {
			$keys_to_require = ['uid','name','context','owner','fields'];
			$diff = array_diff_key(array_flip($keys_to_require), $custom_fieldset);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: custom fieldset is missing properties (%s)", implode(', ', array_keys($diff))));
			
			$fields = $custom_fieldset['fields'] ?? null;
			$keys_to_require = ['uid','name','type','params'];
			
			// Check fields
			if(is_array($fields))
			foreach($fields as $field) {
				$diff = array_diff_key(array_flip($keys_to_require), $field);
				if(count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: field is missing properties (%s)", implode(', ', array_keys($diff))));
			}
		}
		
		$bots = $json['bots'] ?? null;
		
		if(is_array($bots))
		foreach($bots as $bot) {
			$keys_to_require = ['uid','name','owner','is_disabled','params','behaviors'];
			$diff = array_diff_key(array_flip($keys_to_require), $bot);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: bot is missing properties (%s)", implode(', ', array_keys($diff))));
			
			$behaviors = $bot['behaviors'] ?? null;
			$keys_to_require = ['uid','title','is_disabled','is_private','priority','event','nodes'];
			
			// Check behaviors
			if(is_array($behaviors))
			foreach($behaviors as $behavior) {
				$diff = array_diff_key(array_flip($keys_to_require), $behavior);
				if(count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: behavior is missing properties (%s)", implode(', ', array_keys($diff))));
			}
		}
		
		$behaviors = $json['behaviors'] ?? [];
		
		if(is_array($behaviors))
		foreach($behaviors as $behavior) {
			$keys_to_require = ['uid','bot_id','title','is_disabled','is_private','priority','event','nodes'];
			$diff = array_diff_key(array_flip($keys_to_require), $behavior);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: behavior is missing properties (%s)", implode(', ', array_keys($diff))));
		}
		
		$behavior_nodes = $json['behavior_nodes'] ?? [];
		
		if(is_array($behavior_nodes))
		foreach($behavior_nodes as $behavior_node) {
			$keys_to_require = ['uid','behavior_id','parent_id','title'];
			$diff = array_diff_key(array_flip($keys_to_require), $behavior_node);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: behavior node is missing properties (%s)", implode(', ', array_keys($diff))));
		}
		
		$workspaces = $json['workspaces'] ?? [];
		
		if(is_array($workspaces))
		foreach($workspaces as $workspace) {
			$keys_to_require = ['uid','name','extension_id','tabs'];
			$diff = array_diff_key(array_flip($keys_to_require), $workspace);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: workspace is missing properties (%s)", implode(', ', array_keys($diff))));
			
			$tabs = $bot['tabs'] ?? null;
			$keys_to_require = ['uid','name','extension_id','params'];
			
			// Check tabs
			if(is_array($tabs))
			foreach($tabs as $tab) {
				$diff = array_diff_key(array_flip($keys_to_require), $tab);
				if(count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: workspace tab is missing properties (%s)", implode(', ', array_keys($diff))));
			}
		}
		
		$portals = $json['portals'] ?? [];
		
		if(is_array($portals))
		foreach($portals as $portal) {
			$keys_to_require = ['uid','name','extension_id','params'];
			$diff = array_diff_key(array_flip($keys_to_require), $portal);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: portal is missing properties (%s)", implode(', ', array_keys($diff))));
		}
	
		$saved_searches = $json['saved_searches'] ?? [];
		
		if(is_array($saved_searches))
		foreach($saved_searches as $saved_search) {
			$keys_to_require = ['uid','name','context','tag','query'];
			$diff = array_diff_key(array_flip($keys_to_require), $saved_search);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: saved search is missing properties (%s)", implode(', ', array_keys($diff))));
		}
		
		$calendars = $json['calendars'] ?? [];
		
		if(is_array($calendars))
		foreach($calendars as $calendar) {
			$keys_to_require = ['uid','name','params'];
			$diff = array_diff_key(array_flip($keys_to_require), $calendar);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: calendar is missing properties (%s)", implode(', ', array_keys($diff))));
			
			$events = $calendar['events'] ?? null;
			$keys_to_require = ['uid','name','is_available','tz','event_start','event_end','recur_start','recur_end','patterns'];
			
			// Check events
			if(is_array($events))
			foreach($events as $event) {
				$diff = array_diff_key(array_flip($keys_to_require), $event);
				if(count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: calendar event is missing properties (%s)", implode(', ', array_keys($diff))));
			}
		}
		
		$classifiers = $json['classifiers'] ?? [];
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		if(is_array($classifiers))
		foreach($classifiers as $classifier) {
			$keys_to_require = ['uid','name','params'];
			$diff = array_diff_key(array_flip($keys_to_require), $classifier);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: classifier is missing properties (%s)", implode(', ', array_keys($diff))));
			
			$classes = $classifier['classes'] ?? null;
			$keys_to_require = ['uid','name','expressions'];
			
			// Check classifications
			if(is_array($classes))
			foreach($classes as $class) {
				$diff = array_diff_key(array_flip($keys_to_require), $class);
				if(count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: classification is missing properties (%s)", implode(', ', array_keys($diff))));
				
				$expressions = $class['expressions'] ?? null;
				
				if(!is_array($expressions))
					continue;
				
				foreach($expressions as $expression) {
					if(!$bayes::verify($expression))
						throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: invalid training in classifier (%s -> %s): %s", $classifier['name'], $class['name'], $expression));
				}
			}
		}
		
		$project_boards = $json['project_boards'] ?? [];
		
		if(is_array($project_boards))
		foreach($project_boards as $project_board) {
			$keys_to_require = ['uid','name','columns'];
			$diff = array_diff_key(array_flip($keys_to_require), $project_board);
			if(count($diff))
				throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: project board is missing properties (%s)", implode(', ', array_keys($diff))));
			
			$columns = $project_board['columns'] ?? null;
			
			// Validate columns
			if(is_array($columns))
			foreach($columns as $column) {
				$keys_to_require = ['uid','name'];
				$diff = array_diff_key(array_flip($keys_to_require), $column);
				if(count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: project board column is missing properties (%s)", implode(', ', array_keys($diff))));
				
				$cards = $column['cards'] ?? null;
				
				// Validate column cards
				if(is_array($cards))
				foreach($cards as $card) {
					$keys_to_require = ['uid','_context'];
					$diff = array_diff_key(array_flip($keys_to_require), $card);
					if(count($diff))
						throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: project card is missing properties (%s)", implode(', ', array_keys($diff))));
					
					if(false == ($context_ext = Extension_DevblocksContext::getByAlias($card['_context'], true)))
						throw new Exception_DevblocksValidationError(sprintf("Unknown context '%s' on project card.", $card['_context']));
					
					// Ignore any keys with placeholders
					$dict = array_filter($card, function($value, $key) {
						// Ignore internal keys
						if(in_array($key, ['_context','uid']))
							return false;
						
						// Ignore keys or values with unfilled placeholders
						if(
							false !== strstr($key,'{{{')
							|| false !== strstr($value,'{{{')
							) {
							return false;
						}
						
						return true;
						
					}, ARRAY_FILTER_USE_BOTH);
					
					$fields = $custom_fields = [];
					$error = null;
					
					if(!$context_ext->getDaoFieldsFromKeysAndValues($dict, $fields, $custom_fields, $error))
						throw new Exception_DevblocksValidationError(sprintf("Error on project card (%s): %s", $card['uid'], $error));
					
					if(false == ($dao_class = $context_ext->getDaoClass()))
						throw new Exception_DevblocksValidationError(sprintf("Error on project card (%s): %s", $card['uid'], "Can't load DAO class."));
					
					if(!$dao_class::validate($fields, $error))
						throw new Exception_DevblocksValidationError($error);
				}
			}
		}
		
		$events = $json['events'] ?? [];
		
		if(is_array($events)) {
			foreach ($events as $event) {
				$keys_to_require = ['event', 'kata'];
				$diff = array_diff_key(array_flip($keys_to_require), $event);
				if (count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: event is missing properties (%s)", implode(', ', array_keys($diff))));
				
				if(false == DAO_AutomationEvent::getByName($event['event']))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: event (%s) doesn't exist", $event['event']));
			}
		}

		$toolbars = $json['toolbars'] ?? [];
		
		if(is_array($toolbars)) {
			foreach ($toolbars as $toolbar) {
				$keys_to_require = ['toolbar', 'kata'];
				$diff = array_diff_key(array_flip($keys_to_require), $toolbar);
				if (count($diff))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: toolbar is missing properties (%s)", implode(', ', array_keys($diff))));
				
				if(false == DAO_Toolbar::getByName($toolbar['toolbar']))
					throw new Exception_DevblocksValidationError(sprintf("Invalid JSON: toolbar (%s) doesn't exist", $toolbar['toolbar']));
			}
		}
	}
	
	/**
	 * @param $json
	 * @param $uids
	 * @param $records_created
	 * @param $records_modified
	 * @param $placeholders
	 * @throws Exception_DevblocksValidationError
	 */
	private static function _packageGenerateIds(&$json, &$uids, &$records_created, &$records_modified, &$placeholders) {
		$records = $json['records'] ?? [];
		
		// Prepare the template builder
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$lexer = array(
			'tag_comment'   => array('{{#', '#}}'),
			'tag_block'     => array('{{%', '%}}'),
			'tag_variable'  => array('{{{', '}}}'),
			'interpolation' => array('#{{', '}}'),
		);
		
		if(is_array($records))
		foreach($records as $record) {
			$uid_record = $record['uid'];
			
			// If we already processed this, ignore it here
			if(isset($uids[$uid_record]))
				continue;
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($record['_context'], true)))
				throw new Exception_DevblocksValidationError(sprintf("Unknown context on record (%s)", $record['_context']));

			$dict = [];
			
			// If we're creating a custom record, also include its uri so we can use the context later in the package
			if(in_array($record['_context'], ['custom_record', CerberusContexts::CONTEXT_CUSTOM_RECORD])) {
				$dict['uri'] = $record['uri'];
			}
			
			if(false == ($dao_class = $context_ext->getDaoClass()))
				throw new Exception_DevblocksValidationError(sprintf("Error generating record (%s): %s", $uid_record, "Can't load DAO class."));
			
			$record_id = $dao_class::create($dict);
			
			$uids[$uid_record] = $record_id;
		}
		
		//$settings = $json['settings'] ?? null;
		//$worker_prefs = $json['worker_preferences'] ?? null;
		
		$custom_fieldsets = $json['custom_fieldsets'] ?? [];
		
		if(is_array($custom_fieldsets))
		foreach($custom_fieldsets as $custom_fieldset) {
			$uid = $custom_fieldset['uid'];
			
			$custom_fieldset_id = DAO_CustomFieldset::create([
				DAO_CustomFieldset::NAME => $custom_fieldset['name'],
				DAO_CustomFieldset::CONTEXT => $custom_fieldset['context'],
				DAO_CustomFieldset::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
				DAO_CustomFieldset::OWNER_CONTEXT_ID => 0,
			]);
			
			$uids[$uid] = $custom_fieldset_id;
			
			$fields = $custom_fieldset['fields'];
			
			if(is_array($fields))
			foreach($fields as $field) {
				$uid = $field['uid'];
				
				$custom_field_id = DAO_CustomField::create([
					DAO_CustomField::NAME => $uid,
					DAO_CustomField::TYPE => $field['type'],
					DAO_CustomField::PARAMS_JSON => json_encode([]),
					DAO_CustomField::CUSTOM_FIELDSET_ID => $custom_fieldset_id,
				]);
				
				$uids[$field['uid']] = $custom_field_id;
			}
		}
		
		$bots = $json['bots'] ?? [];
		
		if(is_array($bots))
		foreach($bots as $bot) {
			$uid = $bot['uid'];
			
			$bot_id = DAO_Bot::create([
				DAO_Bot::NAME => $bot['name'],
				DAO_Bot::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
				DAO_Bot::OWNER_CONTEXT_ID => 0,
			]);
			
			$uids[$uid] = $bot_id;
			
			$behaviors = $bot['behaviors'] ?? null;
			
			if(is_array($behaviors))
			foreach($behaviors as $behavior) {
				$uid = $behavior['uid'];
				
				$behavior_id = DAO_TriggerEvent::create([
					DAO_TriggerEvent::TITLE => $behavior['title'],
					DAO_TriggerEvent::BOT_ID => $bot_id,
				]);
				
				$uids[$uid] = $behavior_id;
			}
		}
		
		$behaviors = $json['behaviors'] ?? [];
		
		if(is_array($behaviors))
		foreach($behaviors as $behavior) {
			$uid = $behavior['uid'];
			$bot_id = $behavior['bot_id'];
			
			// If the bot_id is a placeholder
			if(preg_match('#\{\{[\#\%\{]#', $bot_id))
				$behavior['bot_id'] = $tpl_builder->build($bot_id, $placeholders, $lexer);
			
			$behavior_id = DAO_TriggerEvent::create([
				DAO_TriggerEvent::TITLE => $behavior['title'],
				DAO_TriggerEvent::BOT_ID => $behavior['bot_id'],
			]);
			
			$uids[$uid] = $behavior_id;
		}
		
		$workspaces = $json['workspaces'] ?? [];
		
		if(is_array($workspaces))
		foreach($workspaces as $workspace) {
			$uid = $workspace['uid'];
			
			$owner_context = $workspace['owner__context'] ?? null;
			$owner_context_id = $workspace['owner_id'] ?? null;
			
			if(!$owner_context) {
				$owner_context = CerberusContexts::CONTEXT_APPLICATION;
				$owner_context_id = 0;
			}
			
			$workspace_id = DAO_WorkspacePage::create([
				DAO_WorkspacePage::NAME => $workspace['name'],
				DAO_WorkspacePage::OWNER_CONTEXT => $owner_context,
				DAO_WorkspacePage::OWNER_CONTEXT_ID => $owner_context_id,
			]);
			
			$uids[$uid] = $workspace_id;
			
			$tabs = $workspace['tabs'] ?? null;
			
			if(is_array($tabs))
			foreach($tabs as $tab) {
				$uid = $tab['uid'];
				
				$tab_id = DAO_WorkspaceTab::create([
					DAO_WorkspaceTab::NAME => $tab['name'],
					DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $workspace_id,
				]);
				
				$uids[$uid] = $tab_id;
			}
		}
		
		$portals = $json['portals'] ?? [];
		
		if(is_array($portals))
		foreach($portals as $portal) {
			$uid = $portal['uid'];
			
			$portal_code = DAO_CommunityTool::generateUniqueCode(8);
			
			$portal_id = DAO_CommunityTool::create([
				DAO_CommunityTool::NAME => $portal['name'],
				DAO_CommunityTool::CODE => $portal_code,
				DAO_CommunityTool::EXTENSION_ID => $portal['extension_id'],
			]);
			
			$uids[$uid] = $portal_id;
		}
		
		$saved_searches = $json['saved_searches'] ?? [];
		
		if(is_array($saved_searches))
		foreach($saved_searches as $saved_search) {
			$uid = $saved_search['uid'];
			
			$search_id = DAO_ContextSavedSearch::create([
				DAO_ContextSavedSearch::NAME => $saved_search['name'],
				DAO_ContextSavedSearch::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
				DAO_ContextSavedSearch::OWNER_CONTEXT_ID => 0,
				DAO_ContextSavedSearch::UPDATED_AT => time(),
			]);
			
			$uids[$uid] = $search_id;
		}
		
		$calendars = $json['calendars'] ?? [];
		
		if(is_array($calendars))
		foreach($calendars as $calendar) {
			$uid = $calendar['uid'];
			
			$calendar_id = DAO_Calendar::create([
				DAO_Calendar::NAME => $calendar['name'],
				DAO_Calendar::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
				DAO_Calendar::OWNER_CONTEXT_ID => 0,
				DAO_Calendar::UPDATED_AT => time(),
			]);
			
			$uids[$uid] = $calendar_id;
			
			$events = $calendar['events'] ?? null;
			
			if(is_array($events))
			foreach($events as $event) {
				$uid = $event['uid'];
				
				$event_id = DAO_CalendarRecurringProfile::create([
					DAO_CalendarRecurringProfile::EVENT_NAME => $event['name'],
					DAO_CalendarRecurringProfile::CALENDAR_ID => $calendar_id,
				]);
				
				$uids[$uid] = $event_id;
			}
		}
		
		$classifiers = $json['classifiers'] ?? [];
		
		if(is_array($classifiers))
		foreach($classifiers as $classifier) {
			$uid = $classifier['uid'];
			
			$classifier_id = DAO_Classifier::create([
				DAO_Classifier::NAME => $classifier['name'],
				DAO_Classifier::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
				DAO_Classifier::OWNER_CONTEXT_ID => 0,
				DAO_Classifier::CREATED_AT => time(),
				DAO_Classifier::UPDATED_AT => time(),
			]);
			
			$uids[$uid] = $classifier_id;
			
			$classes = $classifier['classes'] ?? null;
			
			if(is_array($classes))
			foreach($classes as $class) {
				$uid = $class['uid'];
				
				$class_id = DAO_ClassifierClass::create([
					DAO_ClassifierClass::NAME => $class['name'],
					DAO_ClassifierClass::CLASSIFIER_ID => $classifier_id,
					DAO_ClassifierClass::UPDATED_AT => time(),
				]);
				
				$uids[$uid] = $class_id;
			}
		}
		
		$project_boards = $json['project_boards'] ?? [];
		
		if(is_array($project_boards))
		foreach($project_boards as $project_board) {
			$uid = $project_board['uid'];
			
			$project_board_id = DAO_ProjectBoard::create([
				DAO_ProjectBoard::NAME => $project_board['name'],
				DAO_ProjectBoard::COLUMNS_JSON => '[]',
				DAO_ProjectBoard::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
				DAO_ProjectBoard::OWNER_CONTEXT_ID => 0,
				DAO_ProjectBoard::UPDATED_AT => time(),
			]);
			
			$uids[$uid] = $project_board_id;
			
			$columns = $project_board['columns'] ?? null;
			
			if(is_array($columns))
			foreach($columns as $column) {
				$uid_column = $column['uid'];
				
				$column_id = DAO_ProjectBoardColumn::create([
					DAO_ProjectBoardColumn::NAME => $column['name'],
					DAO_ProjectBoardColumn::BOARD_ID => $project_board_id,
					DAO_ProjectBoardColumn::UPDATED_AT => time(),
				]);
				
				$uids[$uid_column] = $column_id;
				
				$cards = $column['cards'] ?? null;
				
				if(is_array($cards))
				foreach($cards as $card) {
					$uid_card = $card['uid'];
					
					if(false == ($context_ext = Extension_DevblocksContext::getByAlias($card['_context'], true)))
						throw new Exception_DevblocksValidationError(sprintf("Unknown context on project card (%s)", $card['_context']));

					$dict = [];
					
					if(false == ($dao_class = $context_ext->getDaoClass()))
						throw new Exception_DevblocksValidationError(sprintf("Error on project card (%s): %s", $uid_card, "Can't load DAO class."));
					
					$card_id = $dao_class::create($dict);
					
					$uids[$uid_card] = $card_id;
				}
			}
		}
		
		// Add UID placeholders
		$placeholders['uid'] = $uids;
		
		// Add defaults
		$url_writer = DevblocksPlatform::services()->url();
		$default_replyto = DAO_Address::getDefaultLocalAddress();
		$default_group = DAO_Group::getDefaultGroup();
		
		$placeholders['default'] = [
			'base_url' => $url_writer->write('', true),
			'group_id' => $default_group ? $default_group->id : 0,
			'bucket_id' => $default_group ? $default_group->getDefaultBucket()->id : 0,
			'replyto_id' => $default_replyto ? $default_replyto->id : 0,
			'replyto_email' => $default_replyto ? $default_replyto->email : 0,
			'mail_transport_id' => $default_replyto ? $default_replyto->mail_transport_id : 0,
		];
		
		// Recursively rebuild the package and run the template builder when necessary for a key/value.
		// This is memory efficient. Running Twig on a large package will OOM
		
		unset($json['package']);
		
		$findTemplates = function($array) use (&$findTemplates, $tpl_builder, $placeholders, $lexer) {
			$result = [];
			
			foreach($array as $key => $val) {
				if(is_array($val)) {
					if(preg_match('#\{\{[\#\%\{]#', $key))
						$key = $tpl_builder->build($key, $placeholders, $lexer);
					
					$result[$key] = $findTemplates($val);
					
				} else {
					if(preg_match('#\{\{[\#\%\{]#', $key))
						$key = $tpl_builder->build($key, $placeholders, $lexer);
						
					if(preg_match('#\{\{[\#\%\{]#', $val))
						$val = $tpl_builder->build($val, $placeholders, $lexer);
						
					$result[$key] = $val;
				}
			}
			
			return $result;
		};
		
		$json = $findTemplates($json);
	}
	
	/**
	 * @param $json
	 * @param $uids
	 * @param $records_created
	 * @param $records_modified
	 * @throws Exception_DevblocksValidationError
	 */
	private static function _packageImport(&$json, &$uids, &$records_created, &$records_modified) {
		// Records
		$records = $json['records'] ?? [];
		
		if(is_array($records))
		foreach($records as $record) {
			$uid_record = $record['uid'];
			$record_id = $uids[$uid_record];
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($record['_context'], true)))
				throw new Exception_DevblocksValidationError(sprintf("Unknown extension on record (%s): %s", $uid_record, $record['_context']));
			
			$dict = array_diff_key($record, ['_context'=>true,'uid'=>true]);
			$fields = $custom_fields = [];
			$error = null;
			
			if(!$context_ext->getDaoFieldsFromKeysAndValues($dict, $fields, $custom_fields, $error))
				throw new Exception_DevblocksValidationError(sprintf("Error importing record (%s): %s", $uid_record, $error));
			
			if(false == ($dao_class = $context_ext->getDaoClass()))
				throw new Exception_DevblocksValidationError(sprintf("Error importing record (%s): %s", $uid_record, "Can't load DAO class."));
			
			if(!$dao_class::validate($fields, $error, $record_id))
				throw new Exception_DevblocksValidationError(sprintf("Error importing record (%s): %s", $uid_record, $error));
			
			$actor = new Model_Application();
				
			if(!$dao_class::onBeforeUpdateByActor($actor, $fields, $record_id, $error))
				throw new Exception_DevblocksValidationError(sprintf("Error importing record (%s): %s", $uid_record, $error));
				
			$dao_class::update($record_id, $fields);
			
			$dao_class::onUpdateByActor($actor, $fields, $record_id);
			
			DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $record_id, $custom_fields);
			
			$records_created[$context_ext->id][$uid_record] = [
				'id' => $record_id,
				'label' => $record['_label'] ?? $record['name'] ?? $record['uid'] ?? null,
			];
		}
		
		// Fill in labels for abstractly created records
		foreach($records_created as $context_ext_id => $records) {
			if(false == ($context_ext = Extension_DevblocksContext::get($context_ext_id, true)))
				continue;
			
			if(false == ($dao_class = $context_ext->getDaoClass()))
				continue;
			
			if(false == ($models = $dao_class::getIds(array_column($records, 'id'))))
				continue;
				
			if(false == ($dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext_id)))
				continue;
			
			foreach($records as $uid => &$record) {
				if(false == (@$dict = $dicts[$record['id']])) {
					unset($records[$uid]);
					continue;
				}
				
				$record = [
					'id' => $dict->id,
					'label' => $dict->_label,
				];
			}
			
			$records_created[$context_ext_id] = $records;
		}
		
		$plugin_settings = $json['settings'] ?? [];
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		if(is_array($plugin_settings))
		foreach($plugin_settings as $plugin_id => $settings) {
			// Valid plugin?
			if(!isset($plugins[$plugin_id]))
				continue;
			
			// [TODO] Intersect approved $setting_key?
			
			foreach($settings as $setting_key => $setting_value) {
				if(!is_string($setting_value) && !is_numeric($setting_value))
					continue;
				
				DevblocksPlatform::setPluginSetting($plugin_id, $setting_key, $setting_value);
			}
		}
		
		$worker_prefs = $json['worker_preferences'] ?? [];
		$workers = DAO_Worker::getAll();
		
		if(is_array($worker_prefs))
		foreach($worker_prefs as $worker_id => $prefs) {
			if(!isset($workers[$worker_id]))
				continue;
			
			// [TODO] Intersect approved $pref_key?
			
			foreach($prefs as $pref_key => $pref_value) {
				if(!is_string($pref_value) && !is_numeric($pref_value))
					continue;
				
				DAO_WorkerPref::set($worker_id, $pref_key, $pref_value);
			}
		}
		
		$custom_fieldsets = $json['custom_fieldsets'] ?? [];
		
		if(is_array($custom_fieldsets))
		foreach($custom_fieldsets as $custom_fieldset) {
			$uid = $custom_fieldset['uid'];
			$id = $uids[$uid];
			
			DAO_CustomFieldset::update($id, [
				DAO_CustomFieldset::NAME => $custom_fieldset['name'],
				DAO_CustomFieldset::CONTEXT => $custom_fieldset['context'],
			]);
			
			$records_created[CerberusContexts::CONTEXT_CUSTOM_FIELDSET][$uid] = [
				'id' => $id,
				'label' => $custom_fieldset['name'],
			];
			
			$custom_fields = $custom_fieldset['fields'];
			
			if(is_array($custom_fields))
			foreach($custom_fields as $pos => $custom_field) {
				$uid = $custom_field['uid'];
				$id = $uids[$uid];
				
				DAO_CustomField::update($id, [
					DAO_CustomField::NAME => $custom_field['name'],
					DAO_CustomField::TYPE => $custom_field['type'],
					DAO_CustomField::CONTEXT => $custom_fieldset['context'],
					DAO_CustomField::POS => $pos,
					DAO_CustomField::PARAMS_JSON => json_encode($custom_field['params']),
				]);
			}
		}
		
		$bots = $json['bots'] ?? [];
		
		if(is_array($bots))
		foreach($bots as $bot) {
			$uid = $bot['uid'];
			$id = $uids[$uid];
			
			$owner_context = @$bot['owner']['context'] ?: CerberusContexts::CONTEXT_APPLICATION;
			$owner_context_id = @$bot['owner']['id'] ?: 0;
			
			DAO_Bot::update($id, [
				DAO_Bot::NAME => $bot['name'],
				DAO_Bot::OWNER_CONTEXT => $owner_context,
				DAO_Bot::OWNER_CONTEXT_ID => $owner_context_id,
				DAO_Bot::IS_DISABLED => @$bot['is_disabled'] ? 1 : 0,
				DAO_Bot::CREATED_AT => time(),
				DAO_Bot::UPDATED_AT => time(),
				DAO_Bot::PARAMS_JSON => json_encode($bot['params']),
			]);
			
			if(!isset($records_created[CerberusContexts::CONTEXT_BOT]))
				$records_created[CerberusContexts::CONTEXT_BOT] = [];
			
			$records_created[CerberusContexts::CONTEXT_BOT][$uid] = [
				'id' => $id,
				'label' => $bot['name'],
			];
			
			// Image
			
			if(isset($bot['image']) && !empty($bot['image'])) {
				DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_BOT, $id, $bot['image']);
			}
			
			// Behaviors
			
			$behaviors = $bot['behaviors'];
			
			if(is_array($behaviors))
			foreach($behaviors as $behavior) {
				$uid = $behavior['uid'];
				$id = $uids[$uid];
				
				@$event_params = isset($behavior['event']['params']) ? $behavior['event']['params'] : '';
				$error = null;

				if(false != (@$event = Extension_DevblocksEvent::get($behavior['event']['key'], true)))
					$event->prepareEventParams(null, $event_params, $error);
				
				$fields_behavior = [
					DAO_TriggerEvent::EVENT_POINT => $behavior['event']['key'],
					DAO_TriggerEvent::EVENT_PARAMS_JSON => json_encode($event_params),
					DAO_TriggerEvent::IS_DISABLED => 1, // until successfully imported
					DAO_TriggerEvent::IS_PRIVATE => @$behavior['is_private'] ? 1 : 0,
					DAO_TriggerEvent::PRIORITY => @$behavior['priority'],
					DAO_TriggerEvent::TITLE => $behavior['title'],
					DAO_TriggerEvent::UPDATED_AT => time(),
					DAO_TriggerEvent::VARIABLES_JSON => isset($behavior['variables']) ? json_encode($behavior['variables']) : '',
				];
				
				if(array_key_exists('uri', $behavior) && $behavior['uri'])
					$fields_behavior[DAO_TriggerEvent::URI] = $behavior['uri'];
				
				DAO_TriggerEvent::update($id, $fields_behavior);
				
				// Create records for all child nodes and link them to the proper parents
				
				if(isset($behavior['nodes']) && !empty($behavior['nodes']))
				if(false == DAO_TriggerEvent::recursiveImportDecisionNodes($behavior['nodes'], $id, 0))
					throw new Exception_DevblocksValidationError('Failed to import behavior nodes');
				
				// Enable the new behavior since we've succeeded
				
				DAO_TriggerEvent::update($id, array(
					DAO_TriggerEvent::IS_DISABLED => @$behavior['is_disabled'] ? 1 : 0,
				));
				
				if(!isset($records_created[CerberusContexts::CONTEXT_BEHAVIOR]))
					$records_created[CerberusContexts::CONTEXT_BEHAVIOR] = [];
				
				$records_created[CerberusContexts::CONTEXT_BEHAVIOR][$uid] = [
					'id' => $id,
					'label' => $behavior['title'],
				];
			}
		}
		
		$behaviors = $json['behaviors'] ?? [];
		
		if(is_array($behaviors))
		foreach($behaviors as $behavior) {
			$uid = $behavior['uid'];
			$id = $uids[$uid];
			
			@$event_params = isset($behavior['event']['params']) ? $behavior['event']['params'] : '';
			$error = null;

			if(false != (@$event = Extension_DevblocksEvent::get($behavior['event']['key'], true)))
				$event->prepareEventParams(null, $event_params, $error);
			
			DAO_TriggerEvent::update($id, [
				DAO_TriggerEvent::EVENT_POINT => $behavior['event']['key'],
				DAO_TriggerEvent::EVENT_PARAMS_JSON => json_encode($event_params),
				DAO_TriggerEvent::IS_DISABLED => 1, // until successfully imported
				DAO_TriggerEvent::IS_PRIVATE => @$behavior['is_private'] ? 1 : 0,
				DAO_TriggerEvent::PRIORITY => @$behavior['priority'],
				DAO_TriggerEvent::TITLE => $behavior['title'],
				DAO_TriggerEvent::UPDATED_AT => time(),
				DAO_TriggerEvent::VARIABLES_JSON => isset($behavior['variables']) ? json_encode($behavior['variables']) : '',
			]);
			
			// Create records for all child nodes and link them to the proper parents
			
			if(isset($behavior['nodes']) && !empty($behavior['nodes']))
			if(false == DAO_TriggerEvent::recursiveImportDecisionNodes($behavior['nodes'], $id, 0))
				throw new Exception_DevblocksValidationError('Failed to import behavior nodes');
			
			// Enable the new behavior since we've succeeded
			
			DAO_TriggerEvent::update($id, array(
				DAO_TriggerEvent::IS_DISABLED => @$behavior['is_disabled'] ? 1 : 0,
			));
			
			if(!isset($records_created[CerberusContexts::CONTEXT_BEHAVIOR]))
				$records_created[CerberusContexts::CONTEXT_BEHAVIOR] = [];
			
			$records_created[CerberusContexts::CONTEXT_BEHAVIOR][$uid] = [
				'id' => $id,
				'label' => $behavior['title'],
			];
		}
		
		$behavior_nodes = $json['behavior_nodes'] ?? [];
		
		if(is_array($behavior_nodes))
		foreach($behavior_nodes as $behavior_node) {
			$uid = $behavior_node['uid'];
			
			$error = null;

			$behavior_id = @$behavior_node['behavior_id'];
			$parent_id = @$behavior_node['parent_id'] ?: 0;
			
			unset($behavior_node['behavior_id']);
			unset($behavior_node['parent_id']);
			
			$pos = 0;
			
			if($parent_id) {
				// If we have a parent, count its children and append
				$pos = count(DAO_DecisionNode::getByTriggerParent($behavior_id, $parent_id));
				
			} else {
				// Otherwise, count the behavior's children and append
				$pos = count(DAO_DecisionNode::getByTriggerParent($behavior_id));
			}
			
			if(false == ($node = DAO_TriggerEvent::recursiveImportDecisionNodes([$behavior_node], $behavior_id, $parent_id, $pos)))
				throw new Exception_DevblocksValidationError('Failed to import behavior nodes');
			
			if(!isset($records_created[CerberusContexts::CONTEXT_BEHAVIOR_NODE]))
				$records_created[CerberusContexts::CONTEXT_BEHAVIOR_NODE] = [];
			
			$records_created[CerberusContexts::CONTEXT_BEHAVIOR_NODE][$uid] = [
				'id' => $node['id'],
				'label' => $behavior_node['title'],
				'behavior_id' => $behavior_id,
				'parent_id' => $parent_id,
				'type' => $node['type'],
			];
		}
		
		$workspaces = $json['workspaces'] ?? [];
		
		if(is_array($workspaces))
		foreach($workspaces as $workspace) {
			$uid = $workspace['uid'];
			$id = $uids[$uid];
			
			DAO_WorkspacePage::update($id, [
				DAO_WorkspacePage::NAME => $workspace['name'],
				DAO_WorkspacePage::EXTENSION_ID => $workspace['extension_id'],
			]);
			
			if(!isset($records_created[CerberusContexts::CONTEXT_WORKSPACE_PAGE]))
				$records_created[CerberusContexts::CONTEXT_WORKSPACE_PAGE] = [];
			
			$records_created[CerberusContexts::CONTEXT_WORKSPACE_PAGE][$uid] = [
				'id' => $id,
				'label' => $workspace['name'],
			];
			
			$tabs = $workspace['tabs'];
			
			foreach($tabs as $tab_idx => $tab) {
				$uid = $tab['uid'];
				$id = $uids[$uid];
				
				DAO_WorkspaceTab::update($id, [
					DAO_WorkspaceTab::NAME => $tab['name'],
					DAO_WorkspaceTab::EXTENSION_ID => $tab['extension_id'],
					DAO_WorkspaceTab::POS => $tab_idx,
					DAO_WorkspaceTab::PARAMS_JSON => isset($tab['params']) ? json_encode($tab['params']) : '',
				]);
				
				if(false == ($extension = Extension_WorkspaceTab::get($tab['extension_id']))) /* @var $extension Extension_WorkspaceTab */
					throw new Exception_DevblocksValidationError('Failed to instantiate workspace tab extension: ' . $tab['extension_id']);
				
				if(false == ($model = DAO_WorkspaceTab::get($id)))
					throw new Exception_DevblocksValidationError('Failed to load workspace tab model: ' . $tab['extension_id']);
				
				$import_json = ['tab' => $tab];
				$extension->importTabConfigJson($import_json, $model);
			}
		}
		
		$portals = $json['portals'] ?? [];
		
		if(is_array($portals))
		foreach($portals as $portal) {
			$uid = $portal['uid'];
			$id = $uids[$uid];
			
			DAO_CommunityTool::update($id, [
				DAO_CommunityTool::NAME => $portal['name'],
				DAO_CommunityTool::EXTENSION_ID => $portal['extension_id'],
			]);
			
			$portal_model = DAO_CommunityTool::get($id);
			
			if(!isset($records_created[CerberusContexts::CONTEXT_PORTAL]))
				$records_created[CerberusContexts::CONTEXT_PORTAL] = [];
			
			$records_created[CerberusContexts::CONTEXT_PORTAL][$uid] = [
				'id' => $id,
				'label' => $portal['name'],
				'code' => $portal_model->code,
			];
			
			$params = $portal['params'];
			
			if(is_array($params))
			foreach($params as $k => $v) {
				DAO_CommunityToolProperty::set($portal_model->code, $k, $v);
			}
		}
		
		$saved_searches = $json['saved_searches'] ?? [];
		
		if(is_array($saved_searches))
		foreach($saved_searches as $saved_search) {
			$uid = $saved_search['uid'];
			$id = $uids[$uid];
			
			$owner_context = @$saved_search['owner__context'] ?: CerberusContexts::CONTEXT_APPLICATION;
			$owner_context_id = @$saved_search['owner_id'] ?: 0;
			
			DAO_ContextSavedSearch::update($id, [
				DAO_ContextSavedSearch::NAME => $saved_search['name'],
				DAO_ContextSavedSearch::CONTEXT => $saved_search['context'],
				DAO_ContextSavedSearch::TAG => $saved_search['tag'],
				DAO_ContextSavedSearch::QUERY => $saved_search['query'],
				DAO_ContextSavedSearch::OWNER_CONTEXT => $owner_context,
				DAO_ContextSavedSearch::OWNER_CONTEXT_ID => $owner_context_id,
			]);
			
			if(!isset($records_created[CerberusContexts::CONTEXT_SAVED_SEARCH]))
				$records_created[CerberusContexts::CONTEXT_SAVED_SEARCH] = [];
			
			$records_created[CerberusContexts::CONTEXT_SAVED_SEARCH][$uid] = [
				'id' => $id,
				'label' => $saved_search['name'],
			];
		}
		
		$calendars = $json['calendars'] ?? [];
		
		if(is_array($calendars))
		foreach($calendars as $calendar) {
			$uid = $calendar['uid'];
			$id = $uids[$uid];
			
			$owner_context = @$calendar['owner__context'] ?: CerberusContexts::CONTEXT_APPLICATION;
			$owner_context_id = @$calendar['owner_id'] ?: 0;
			
			DAO_Calendar::update($id, [
				DAO_Calendar::NAME => $calendar['name'],
				DAO_Calendar::PARAMS_JSON => isset($calendar['params']) ? json_encode($calendar['params']) : '',
				DAO_Calendar::UPDATED_AT => time(),
				DAO_Calendar::OWNER_CONTEXT => $owner_context,
				DAO_Calendar::OWNER_CONTEXT_ID => $owner_context_id,
			]);
			
			if(!isset($records_created[CerberusContexts::CONTEXT_CALENDAR]))
				$records_created[CerberusContexts::CONTEXT_CALENDAR] = [];
			
			$records_created[CerberusContexts::CONTEXT_CALENDAR][$uid] = [
				'id' => $id,
				'label' => $calendar['name'],
			];
			
			$calendar_id = $id;
			$events = $calendar['events'] ?? null;
			
			if(is_array($events))
			foreach($events as $event) {
				$uid = $event['uid'];
				$id = $uids[$uid];
				
				DAO_CalendarRecurringProfile::update($id, [
					DAO_CalendarRecurringProfile::EVENT_NAME => $event['name'],
					DAO_CalendarRecurringProfile::CALENDAR_ID => $calendar_id,
					DAO_CalendarRecurringProfile::IS_AVAILABLE => @$event['is_available'] ? 1 : 0,
					DAO_CalendarRecurringProfile::TZ => $event['tz'],
					DAO_CalendarRecurringProfile::EVENT_START => $event['event_start'],
					DAO_CalendarRecurringProfile::EVENT_END => $event['event_end'],
					DAO_CalendarRecurringProfile::RECUR_START => $event['recur_start'],
					DAO_CalendarRecurringProfile::RECUR_END => $event['recur_end'],
					DAO_CalendarRecurringProfile::PATTERNS => implode("\n", is_array(@$event['patterns']) ? $event['patterns'] : []),
				]);
			}
		}
		
		$classifiers = $json['classifiers'] ?? [];
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		if(is_array($classifiers))
		foreach($classifiers as $classifier) {
			$uid = $classifier['uid'];
			$id = $uids[$uid];
			$classifier_id = $id;
			
			$owner_context = @$classifier['owner__context'] ?: CerberusContexts::CONTEXT_APPLICATION;
			$owner_context_id = @$classifier['owner_id'] ?: 0;
			
			DAO_Classifier::update($id, [
				DAO_Classifier::NAME => $classifier['name'],
				DAO_Classifier::PARAMS_JSON => isset($classifier['params']) ? json_encode($classifier['params']) : '',
				DAO_Classifier::UPDATED_AT => time(),
				DAO_Classifier::OWNER_CONTEXT => $owner_context,
				DAO_Classifier::OWNER_CONTEXT_ID => $owner_context_id,
			]);
			
			if(!isset($records_created[CerberusContexts::CONTEXT_CLASSIFIER]))
				$records_created[CerberusContexts::CONTEXT_CLASSIFIER] = [];
			
			$records_created[CerberusContexts::CONTEXT_CLASSIFIER][$uid] = [
				'id' => $id,
				'label' => $classifier['name'],
			];
			
			$classes = $classifier['classes'] ?? null;
			
			if(is_array($classes))
			foreach($classes as $class) {
				$uid = $class['uid'];
				$id = $uids[$uid];
				$class_id = $id;
				
				DAO_ClassifierClass::update($id, [
					DAO_ClassifierClass::NAME => $class['name'],
					DAO_ClassifierClass::CLASSIFIER_ID => $classifier_id,
					DAO_ClassifierClass::UPDATED_AT => time(),
				]);
				
				$expressions = $class['expressions'] ?? null;
				
				if(!is_array($expressions))
					continue;
				
				foreach($expressions as $expression) {
					DAO_ClassifierExample::create([
						DAO_ClassifierExample::CLASSIFIER_ID => $classifier_id,
						DAO_ClassifierExample::CLASS_ID => $class_id,
						DAO_ClassifierExample::EXPRESSION => $expression,
						DAO_ClassifierExample::UPDATED_AT => time(),
					]);
					
					$bayes::train($expression, $classifier_id, $class_id, true);
				}
			}
			
			$bayes::build($classifier_id);
		}
		
		$project_boards = $json['project_boards'] ?? [];
		
		if(is_array($project_boards))
		foreach($project_boards as $project_board) {
			$uid = $project_board['uid'];
			$project_board_id = $uids[$uid];
			
			$columns = $project_board['columns'] ?? null;
			$column_ids = [];
			
			if(is_array($columns))
			foreach($columns as $column) {
				$uid = $column['uid'];
				$column_id = $uids[$uid];
				$column_ids[] = $column_id;
				
				// Cards
				$cards = $column['cards'] ?? null;
				$card_ids = [];
				
				if(is_array($cards))
				foreach($cards as $card) {
					$uid = $card['uid'];
					$card_id = $uids[$uid];
					$card_ids[] = $card_id;
					
					if(false == ($context_ext = Extension_DevblocksContext::getByAlias($card['_context'], true)))
						throw new Exception_DevblocksValidationError(sprintf("Unknown extension on project card (%s): %s", $card['uid'], $card['_context']));
					
					$dict = array_diff_key($card, ['_context'=>true,'uid'=>true]);
					$fields = $custom_fields = [];
					$error = null;
					
					if(!$context_ext->getDaoFieldsFromKeysAndValues($dict, $fields, $custom_fields, $error))
						throw new Exception_DevblocksValidationError(sprintf("Error on project card (%s): %s", $card['uid'], $error));
					
					if(false == ($dao_class = $context_ext->getDaoClass()))
						throw new Exception_DevblocksValidationError(sprintf("Error on project card (%s): %s", $card['uid'], "Can't load DAO class."));
					
					$dao_class::update($card_id, $fields);
					
					DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $card_id, $custom_fields);
					
					// Add a record link card<->column
					DAO_ContextLink::setLink($context_ext->id, $card_id, Context_ProjectBoardColumn::ID, $column_id);
				}
				
				DAO_ProjectBoardColumn::update($column_id, [
					DAO_ProjectBoardColumn::NAME => $column['name'],
					DAO_ProjectBoardColumn::BOARD_ID => $project_board_id,
					DAO_ProjectBoardColumn::CARDS_JSON => json_encode($card_ids),
					DAO_ProjectBoardColumn::UPDATED_AT => time(),
				]);
			}
			
			DAO_ProjectBoard::update($project_board_id, [
				DAO_ProjectBoard::NAME => $project_board['name'],
				DAO_ProjectBoard::COLUMNS_JSON => json_encode($column_ids),
				DAO_ProjectBoard::UPDATED_AT => time(),
				DAO_ProjectBoard::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
				DAO_ProjectBoard::OWNER_CONTEXT_ID => 0,
			]);
			
			if(!isset($records_created['cerberusweb.contexts.project.board']))
				$records_created['cerberusweb.contexts.project.board'] = [];
			
			$records_created['cerberusweb.contexts.project.board'][$project_board['uid']] = [
				'id' => $project_board_id,
				'label' => $project_board['name'],
			];
		}
		
		$events = $json['events'] ?? [];
		
		if(is_array($events)) {
			foreach ($events as $event) {
				if (false != ($event_model = DAO_AutomationEvent::getByName($event['event']))) {
					DAO_AutomationEvent::update($event_model->id, [
						DAO_AutomationEvent::AUTOMATIONS_KATA => rtrim($event_model->automations_kata) . "\n\n" . rtrim($event['kata']),
					]);
					
					$records_modified['cerb.contexts.automation.event'][$event_model->id] = [
						'id' => $event_model->id,
						'label' => $event_model->name,
					];
				}
			}
		}
		
		$toolbars = $json['toolbars'] ?? [];
		
		if(is_array($toolbars)) {
			foreach ($toolbars as $toolbar) {
				if (false != ($toolbar_model = DAO_Toolbar::getByName($toolbar['toolbar']))) {
					DAO_Toolbar::update($toolbar_model->id, [
						DAO_Toolbar::TOOLBAR_KATA => rtrim($toolbar_model->toolbar_kata) . "\n\n" . rtrim($toolbar['kata']),
					]);
					
					$records_modified['cerb.contexts.toolbar'][$toolbar_model->id] = [
						'id' => $toolbar_model->id,
						'label' => $toolbar_model->name,
					];
				}
			}
		}
	}
};
