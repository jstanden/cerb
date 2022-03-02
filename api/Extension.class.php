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

abstract class Extension_CustomField extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.custom_field';
	
	abstract function renderConfig(Model_CustomField $field);
	abstract function getDictionaryValues(Model_CustomField $field, $value, $as_keys=true, &$token_values=[]);
	abstract function getLabelsForValues(Model_CustomField $field, $values);
	abstract function getValueTableName();
	abstract function getValueTableSql($context, array $context_ids);
	abstract function getValuesContexts(Model_CustomField $field, $token, &$values);
	abstract function getVarValueToContextMap(Model_TriggerEvent $trigger, string $var_key, $var, &$values_to_contexts);
	abstract function populateQuickSearchMeta(Model_CustomField $field, array &$search_field_meta);
	abstract function prepareCriteriaParam(Model_CustomField $field, $param, &$vals, &$implode_token);
	abstract function renderEditable(Model_CustomField $field, $form_key, $form_value);
	abstract function renderValue(Model_CustomField $field, $value);
	abstract function setFieldValue(Model_CustomField $field, $context, $context_id, $value);
	abstract function unsetFieldValue(Model_CustomField $field, $context, $context_id, $value=null);
	abstract function validationRegister(Model_CustomField $field, _DevblocksValidationService &$validation);
	
	abstract function botActionRender(Model_CustomField $field);
	abstract function botActionSimulate(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $value_key);
	abstract function botActionGetValueFromParams(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict);
	abstract function botActionRun(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $context, $context_id, $value_key);
	
	function hasMultipleValues() {
		return false;
	}
	
	function formatFieldValue($value) {
		return $value;
	}
	
	function getParamFromQueryFieldTokens($field, $tokens, $param_key) {
		return false;
	}

	function getWhereSQLFromParam(Model_CustomField $field, DevblocksSearchCriteria $param) {
		return null;
	}
	
	function parseFormPost(Model_CustomField $field) {
		return DevblocksPlatform::importGPC($_POST['field_'.$field->id] ?? null,'string','');
	}
}

abstract class Extension_AppPreBodyRenderer extends DevblocksExtension {
	const POINT = 'cerberusweb.renderer.prebody';
	
	function render() { }
};

abstract class Extension_AppPostBodyRenderer extends DevblocksExtension {
	const POINT = 'cerberusweb.renderer.postbody';
	
	function render() { }
};

abstract class CerberusPageExtension extends DevblocksExtension {
	const POINT = 'cerberusweb.page';
	
	abstract function isVisible();
	abstract function render();
	abstract function invoke(string $action);
};

abstract class Extension_PluginSetup extends DevblocksExtension {
	const POINT = 'cerberusweb.plugin.setup';

	static function getByPlugin($plugin_id, $as_instances=true) {
		$results = [];

		// Include disabled extensions
		$all_extensions = DevblocksPlatform::getExtensionRegistry(true, true);
		foreach($all_extensions as $k => $ext) { /* @var $ext DevblocksExtensionManifest */
			if($ext->plugin_id == $plugin_id && $ext->point == Extension_PluginSetup::POINT)
				$results[$k] = ($as_instances) ? $ext->createInstance() : $ext;
		}
		
		return $results;
	}
	
	abstract function render();
	abstract function save(&$errors);
}

abstract class Extension_PageSection extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.page.section';
	
	/**
	 * @internal
	 * 
	 * @return DevblocksExtensionManifest[]|Extension_PageSection[]
	 */
	static function getExtensions($as_instances=true, $page_id=null) {
		if(empty($page_id))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances, false);

		$results = [];
		
		$exts = DevblocksPlatform::getExtensions(self::POINT, false, false);
		foreach($exts as $ext_id => $ext) {
			if(0 == strcasecmp($page_id, $ext->params['page_id']))
				$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
		}
		
		return $results;
	}
	
	/**
	 * @internal
	 * 
	 * @param string $uri
	 * @return DevblocksExtensionManifest|Extension_PageSection
	 */
	static function getExtensionByPageUri($page_id, $uri, $as_instance=true) {
		$manifests = self::getExtensions(false, $page_id);
		
		// Check plugins
		foreach($manifests as $mft) { /* @var $mft DevblocksExtensionManifest */
			if(0==strcasecmp($uri, $mft->params['uri']))
				return $as_instance ? $mft->createInstance() : $mft;
		}
		
		// Check custom records
		switch($page_id) {
			case 'core.page.profiles':
				if(false == ($custom_record = DAO_CustomRecord::getByUri($uri)))
					break;
					
				// Return a synthetic subpage extension
				
				$ext_id = sprintf('profile.custom_record.%d', $custom_record->id);
				$manifest = new DevblocksExtensionManifest();
				$manifest->id = $ext_id;
				$manifest->plugin_id = 'cerberusweb.core';
				$manifest->point = Extension_PageSection::POINT;
				$manifest->name = $custom_record->name;
				$manifest->file = 'api/uri/profiles/abstract_custom_record.php';
				$manifest->class = 'Profile_AbstractCustomRecord_' . $custom_record->id;
				$manifest->params = [
					'page_id' => 'core.page.profiles',
					'uri' => $custom_record->uri,
				];
				
				if($as_instance) {
					return $manifest->createInstance();
				} else {
					return $manifest;
				}
		}
		
		return null;
	}
	
	abstract function render();
	abstract function handleActionForPage(string $action, string $scope=null);
};

abstract class Extension_PageMenu extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.page.menu';
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_PageMenu[]
	 */
	static function getExtensions($as_instances=true, $page_id=null) {
		if(empty($page_id))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		$results = [];
		
		$exts = DevblocksPlatform::getExtensions(self::POINT, false);
		foreach($exts as $ext_id => $ext) {
			if(0 == strcasecmp($page_id, $ext->params['page_id']))
				$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
		}
		
		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($results, 'name');
		
		return $results;
	}
	
	abstract function render();
};

abstract class Extension_PageMenuItem extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.page.menu.item';
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_PageMenuItem[]
	 */
	static function getExtensions($as_instances=true, $page_id=null, $menu_id=null) {
		if(empty($page_id) && empty($menu_id))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		$results = [];
		
		$exts = DevblocksPlatform::getExtensions(self::POINT, false);
		foreach($exts as $ext_id => $ext) {
			if(empty($page_id) || 0 == strcasecmp($page_id, $ext->params['page_id']))
				if(empty($menu_id) || 0 == strcasecmp($menu_id, $ext->params['menu_id']))
					$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
		}
		
		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($results, 'name');
		
		return $results;
	}
	
	abstract function render();
};

abstract class Extension_MailTransport extends DevblocksExtension {
	const POINT = 'cerberusweb.mail.transport';
	
	static $_registry = [];
	
	/**
	 * @internal
	 * 
	 * @return DevblocksExtensionManifest[]|Extension_MailTransport[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_MailTransport) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderConfig(Model_MailTransport $model);
	abstract function testConfig(array $params, &$error=null);
	abstract function send(Swift_Message $message, Model_MailTransport $model);
	abstract function getLastError();
};

abstract class Extension_ProfileTab extends DevblocksExtension {
	const POINT = 'cerb.profile.tab';

	static $_registry = [];

	/**
	 * @internal
	 * 
	 * @return DevblocksExtensionManifest[]|Extension_ProfileTab[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	/**
	 * @internal
	 */
	static function getByContext($context, $as_instances=true) {
		$extensions = self::getAll($as_instances);
		
		$extensions = array_filter($extensions, function($extension) use ($context, $as_instances) {
			$ptr = ($as_instances) ? $extension->manifest : $extension;
			
			if(!array_key_exists('contexts', $ptr->params))
				return true;
			
			$contexts = ($ptr->params['contexts'][0] ?? null) ?: [];
			
			return isset($contexts[$context]);
		});
		
		return $extensions;
	}
	
	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_ProfileTab) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function showTab(Model_ProfileTab $model, $context, $context_id);
	abstract function invoke(string $action, Model_ProfileTab $model);
	abstract function renderConfig(Model_ProfileTab $model);
	abstract function saveConfig(Model_ProfileTab $model);
};

abstract class Extension_ProfileWidget extends DevblocksExtension {
	const POINT = 'cerb.profile.tab.widget';

	static $_registry = [];

	/**
	 * @internal
	 * 
	 * @return DevblocksExtensionManifest[]|Extension_ProfileWidget[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_ProfileWidget) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	/**
	 * @internal
	 */
	static function getByContext($context, $as_instances=true) {
		$extensions = self::getAll($as_instances);
		
		$extensions = array_filter($extensions, function($extension) use ($context, $as_instances) {
			$ptr = ($as_instances) ? $extension->manifest : $extension;
			
			if(!array_key_exists('contexts', $ptr->params))
				return true;
			
			$contexts = ($ptr->params['contexts'][0] ?? null) ?: [];
			
			return isset($contexts[$context]);
		});
		
		return $extensions;
	}
	
	abstract function render(Model_ProfileWidget $model, $context, $context_id);
	abstract function invoke(string $action, Model_ProfileWidget $model);
	abstract function renderConfig(Model_ProfileWidget $model);
	abstract function invokeConfig($config_action, Model_ProfileWidget $model);
	function saveConfig(array $fields, $id, &$error=null) { return true; }
	
	/**
	 * @internal
	 */
	public function export(Model_ProfileWidget $widget) {
		$widget_json = [
			'widget' => [
				'uid' => 'profile_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
				'name' => $widget->name,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'extension_params' => $widget->extension_params,
			]
		];
		
		return json_encode($widget_json);
	}
};

abstract class Extension_CalendarDatasource extends DevblocksExtension {
	const POINT = 'cerberusweb.calendar.datasource';
	
	static $_registry = [];
	
	/**
	 * @internal
	 * 
	 * @return DevblocksExtensionManifest[]|Extension_WorkspacePage[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_CalendarDatasource) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderConfig(Model_Calendar $calendar, $params, $series_prefix);
	abstract function getData(Model_Calendar $calendar, array $params=[], $params_prefix=null, $date_range_from=null, $date_range_to=null, $timezone=null);
};

abstract class Extension_CardWidget extends DevblocksExtension {
	const POINT = 'cerb.card.widget';
	
	static $_registry = [];
	
	/**
	 * @param bool $as_instances
	 * @return DevblocksExtensionManifest[]|Extension_CardWidget[]
	 * @internal
	 *
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);
		
		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
		
		return $exts;
	}
	
	/**
	 * @param string $extension_id
	 * @return DevblocksExtensionManifest|mixed|null
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_CardWidget) {
			
			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	/**
	 * @internal
	 */
	static function getByContext($context, $as_instances=true) {
		$extensions = self::getAll($as_instances);
		
		$extensions = array_filter($extensions, function($extension) use ($context, $as_instances) {
			$ptr = ($as_instances) ? $extension->manifest : $extension;
			
			if(!array_key_exists('contexts', $ptr->params))
				return true;
			
			$contexts = ($ptr->params['contexts'][0] ?? null) ?: [];
			
			return isset($contexts[$context]);
		});
		
		return $extensions;
	}
	
	abstract function render(Model_CardWidget $model, $context, $context_id);
	abstract function invoke(string $action, Model_CardWidget $model);
	abstract function renderConfig(Model_CardWidget $model);
	abstract function invokeConfig($action, Model_CardWidget $model);
	function saveConfig(array $fields, $id, &$error=null) { return true; }
	
	/**
	 * @internal
	 */
	public function export(Model_CardWidget $widget) {
		$widget_json = [
			'widget' => [
				'uid' => 'card_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_CARD_WIDGET,
				'name' => $widget->name,
				'record_type' => $widget->record_type,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'extension_params' => $widget->extension_params,
			]
		];
		
		return json_encode($widget_json);
	}
};

abstract class Extension_ResourceType extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.resource.type';
	
	static $_registry = [];
	
	/**
	 * @param Model_Resource $resource
	 * @return Model_Resource_ContentData
	 */
	abstract function getContentData(Model_Resource $resource);
	
	abstract function validateContentData($fp, &$extension_params=[], &$error=null) : bool;
	
	/**
	 * @param Model_Resource $resource
	 * @param Model_Resource_ContentData $content_data
	 * @return bool
	 */
	function getContentResource(Model_Resource $resource, Model_Resource_ContentData &$content_data) {
		if($resource->is_dynamic) {
			$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
			$active_worker = CerberusApplication::getActiveWorker();
			
			$content_data->data = fopen('php://memory', 'w');
			
			$dict = DevblocksDictionaryDelegate::instance([]); 
			$dict->mergeKeys('resource_', DevblocksDictionaryDelegate::getDictionaryFromModel($resource, CerberusContexts::CONTEXT_RESOURCE));
			$dict->mergeKeys('actor_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
			
			$handlers = $event_handler->parse($resource->automation_kata, $dict, $error);
			
			$initial_state = $dict->getDictionary();
			
			$automation_results = $event_handler->handleOnce(
				AutomationTrigger_ResourceGet::ID,
				$handlers,
				$initial_state,
				$error
			);
			
			if(!($automation_results instanceof DevblocksDictionaryDelegate)) {
				$content_data->error = 'No automations returned content.';
				return false;
			}
			
			$exit_code = $automation_results->get('__exit');
			
			if($exit_code != 'return') {
				$content_data->error = sprintf('Automation exited in `%s` state.', $exit_code);
				return false;
			}
			
			if(null === ($file = $automation_results->getKeyPath('__return.file', null))) {
				$content_data->error = '';
				return false;
			}
			
			if(!is_array($file) || !array_key_exists('content', $file)) {
				$content_data->expires_at = 0;
				return false;
			}
			
			fwrite($content_data->data, $file['content']);
			fseek($content_data->data, 0);
			
			$content_data->expires_at = $file['expires_at'] ?? null;
			
		} else {
			$content_data->data =
				($resource->storage_size > 1024000)
					? DevblocksPlatform::getTempFile()
					: fopen('php://memory', 'w')
			;
			
			$content_data->expires_at = time() + 604800;
			
			if(!is_resource($content_data->data))
				return false;
			
			if(false == (Storage_Resource::get($resource->id, $content_data->data)))
				return false;
		}
		
		return true;
	}
}

abstract class Extension_AutomationTrigger extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.automation.trigger';
	
	static $_registry = [];
	static $_cache_record_types = null;
	
	abstract function renderConfig(Model_Automation $model);
	abstract function validateConfig(array &$params, &$error);
	abstract function getInputsMeta();
	abstract function getOutputsMeta();
	abstract function getAutocompleteSuggestions() : array;
	abstract function getEditorToolbarItems(array $toolbar) : array;
	
	protected function _getRecordTypeSuggestions() : array {
		if(self::$_cache_record_types)
			return self::$_cache_record_types;
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		
		self::$_cache_record_types = array_values(array_map(function($context_mft) {
			return $context_mft->params['alias'];
		}, $context_mfts));
		
		return self::$_cache_record_types;
	}
	
	public function getEditorToolbar() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$toolbar = [];
		
		$toolbar['interaction/builder'] = [
			'uri' => 'cerb:automation:ai.cerb.automationBuilder',
			'icon' => 'magic',
		];
		
		// Merge Options from `automation.editor`
		
		if(false != ($editor_toolbar = DAO_Toolbar::getByName('automation.editor'))) {
			$editor_toolbar_dict = DevblocksDictionaryDelegate::instance([
				'trigger_id' => $this->manifest->id,
				'trigger_name' => $this->manifest->name,
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
			]);
			
			if(false != ($editor_toolbar_items = $editor_toolbar->getKata($editor_toolbar_dict))) {
				foreach($editor_toolbar_items as $item) {
					$item_k = sprintf('%s/%s', $item['type'], $item['key']);
					$toolbar[$item_k] = $item;
				}
			}
		}
		
		$toolbar['interaction/help'] = [
			'icon' => 'circle-question-mark',
			'uri' => 'ai.cerb.automationBuilder.help',
			'inputs' => [
				'topic' => 'editor',
			]
		];
		
		// Get toolbar modifications from trigger
		return $this->getEditorToolbarItems($toolbar);
	}
	
	public function getEventToolbarItems(array $toolbar) : array {
		return $toolbar;
	}	
	
	public function getEventToolbar() {
		$toolbar = [
			'interaction/automation' => [
				'icon' => 'search',
				'tooltip' => 'Find or create an automation',
				'uri' => 'ai.cerb.eventHandler.automation',
				'inputs' => [
					'trigger' => $this->id,
				],
			],
		];
		
		if(method_exists($this, 'getEventToolbarItems'))
			$toolbar = $this->getEventToolbarItems($toolbar);
		
		return $toolbar;
	}
	
	function getEventPlaceholders() : array {
		return $this->getInputsMeta();
	}
	
	public function getAutocompleteSuggestionsJson() {
		$trigger_features = current($this->manifest->params['features'] ?? []);
		
		$common_actions = [
			[
				'caption' => 'decision:',
				'snippet' => "decision/\${1:key}:\n\t\${2:}",
				'description' => "Run commands only in the first matching outcome",
			],
			[
				'caption' => 'outcome:',
				'snippet' => "outcome/\${1:key}:\n\tif@bool: {{\${2:condition}}}\n\tthen:\n\t\t\${3:}",
				'description' => "Run commands when conditions are true",
			],
			[
				'caption' => 'error:',
				'snippet' => "error:\n\t",
				'description' => "Exit and return an error response",
				//'interaction' => 'ai.cerb.automationBuilder.exit.error',
			],
			[
				'caption' => 'repeat:',
				'snippet' => "repeat:\n\t",
				'description' => "Repeat commands for every collection item",
			],
			[
				'caption' => 'while:',
				'snippet' => "while/\${1:key}:\n\tif@bool: {{\${2:condition}}}\n\tdo:\n\t\t\${3:}",
				'description' => "Repeat commands while conditions are true",
			],
			[
				'caption' => 'return:',
				'snippet' => "return:\n\t",
				'description' => "Exit and return a successful response",
				//'interaction' => 'ai.cerb.automationBuilder.exit.return',
			],
			[
				'caption' => 'set:',
				'snippet' => "set:\n\t\${1:key}: \${2:value}\n",
				'description' => "Set one or more keys",
			],
			[
				'caption' => 'data.query:',
				'snippet' => "data.query:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => 'Get results from a data query',
				'interaction' => 'ai.cerb.automationBuilder.action.dataQuery',
			],
			[
				'caption' => 'decrypt.pgp:',
				'snippet' => "decrypt.pgp:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Decrypt a block of PGP encrypted text",
				'interaction' => 'ai.cerb.automationBuilder.action.pgpDecrypt',
			],
			[
				'caption' => 'email.parse:',
				'snippet' => "email.parse:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => 'Parse an email message in RFC-5322 format',
				'interaction' => 'ai.cerb.automationBuilder.action.emailParser',
			],
			[
				'caption' => 'encrypt.pgp:',
				'snippet' => "encrypt.pgp:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Encrypt a block of text using PGP public keys",
				'interaction' => 'ai.cerb.automationBuilder.action.pgpEncrypt',
			],
			[
				'caption' => 'file.read:',
				'snippet' => "file.read:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Read attachment content",
			],
			[
				'caption' => 'function:',
				'snippet' => "function:\n\turi: \${1:}\n\tinputs:\n\t\t\${2:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Run an automation function",
				'interaction' => 'ai.cerb.automationBuilder.action.function'
			],
			[
				'caption' => 'http.request:',
				'snippet' => "http.request:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Execute a request to an HTTP endpoint",
				'interaction' => 'ai.cerb.automationBuilder.action.httpRequest',
			],
			[
				'caption' => 'log:',
				'snippet' => "log: \${1:This is a debug message}",
				'description' => "Log a message with debug severity",
			],
			[
				'caption' => 'log.error:',
				'snippet' => "log.error: \${1:This is an error message}",
				'description' => "Log a message with error severity",
			],
			[
				'caption' => 'log.warn:',
				'snippet' => "log.warn: \${1:This is a warning message}",
				'description' => "Log a message with warning severity",
			],
			[
				'caption' => 'metric.increment:',
				'snippet' => "metric.increment:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Record new samples on a metric",
				'interaction' => 'ai.cerb.automationBuilder.action.metricIncrement',
			],
			[
				'caption' => 'queue.pop:',
				'snippet' => "queue.pop:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Read messages from a queue",
			],
			[
				'caption' => 'queue.push:',
				'snippet' => "queue.push:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Write messages to a queue",
			],
			[
				'caption' => 'record.create:',
				'snippet' => "record.create:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Create a new record of a given type",
				'interaction' => 'ai.cerb.automationBuilder.action.recordCreate',
			],
			[
				'caption' => 'record.delete:',
				'snippet' => "record.delete:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Delete a target record by type and ID",
				'interaction' => 'ai.cerb.automationBuilder.action.recordDelete',
			],
			[
				'caption' => 'record.get:',
				'snippet' => "record.get:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Read a target record by type and ID",
				'interaction' => 'ai.cerb.automationBuilder.action.recordGet',
			],
			[
				'caption' => 'record.search:',
				'snippet' => "record.search:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Search a record type with a query and return matches",
				'interaction' => 'ai.cerb.automationBuilder.action.recordSearch',
			],
			[
				'caption' => 'record.update:',
				'snippet' => "record.update:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Update a target record by type and ID",
				'interaction' => 'ai.cerb.automationBuilder.action.recordUpdate',
			],
			[
				'caption' => 'record.upsert:',
				'snippet' => "record.upsert:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Insert or update a record based query matches",
				'interaction' => 'ai.cerb.automationBuilder.action.recordUpsert',
			],
			[
				'caption' => 'storage.delete:',
				'snippet' => "storage.delete:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Delete a persistent key",
				'interaction' => 'ai.cerb.automationBuilder.action.storageDelete',
			],
			[
				'caption' => 'storage.get:',
				'snippet' => "storage.get:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Read a persistent value by key",
				'interaction' => 'ai.cerb.automationBuilder.action.storageGet',
			],
			[
				'caption' => 'storage.set:',
				'snippet' => "storage.set:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Write a persistent value by key",
				'interaction' => 'ai.cerb.automationBuilder.action.storageSet',
			],
			[
				'caption' => 'var.expand:',
				'snippet' => "var.expand:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Expand a dictionary by key path",
			],
			[
				'caption' => 'var.push:',
				'snippet' => "var.push:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Append to a list by key path",
			],
			[
				'caption' => 'var.set:',
				'snippet' => "var.set:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Set a key by path",
			],
			[
				'caption' => 'var.unset:',
				'snippet' => "var.unset:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Unset a key by path",
			]
		];
		
		if(array_key_exists('await', $trigger_features)) {
			$common_actions[] =
				[
					'caption' => 'await:',
					'snippet' => "await:\n\t",
					'description' => "Pause and wait for the specified inputs before resuming",
				];
		}
		
		$action_base = [
			[
				'caption' => 'inputs:',
				'snippet' => "inputs:\n\t\${1:}",
				'description' => "Pass these inputs to the command",
			],
			[
				'caption' => 'output:',
				'snippet' => "output: \${1:results}",
				'description' => "Write command output to this key",
			],
			[
				'caption' => 'on_simulate:',
				'snippet' => "on_simulate:\n\t\${1:}",
				'description' => "Run these commands during simulation. Return with `simulate.success:` or `simulate.error:`",
			],
			[
				'caption' => 'on_success:',
				'snippet' => "on_success:\n\t\${1:}",
				'description' => "Run these commands when the command is successful",
			],
			[
				'caption' => 'on_error:',
				'snippet' => "on_error:\n\t\${1:}",
				'description' => "Run these commands when the command fails",
			],
		];
		
		$schema = [
			''=> [
				[
					'caption' => 'inputs:',
					'snippet' => "inputs:\n\t\${1:}",
					'description' => "Accept these inputs from the caller",
				],
				[
					'caption' => 'start:',
					'snippet' => "start:\n\t\${1:}",
					'description' => "Run these commands when the automation starts",
					'score' => 2000,
				],
			],
			
			'*' => [
				'(.*):on_error:' => $common_actions,
				'(.*):on_success:' => $common_actions,
				'(.*):on_simulate:' => array_merge(
					[
						[
							'caption' => 'simulate.error:',
							'snippet' => "simulate.error:\n\t\${1:key}: \${2:value}",
							'description' => "Trigger a command `on_error:` event",
						],
						[
							'caption' => 'simulate.success:',
							'snippet' => "simulate.success:\n\t\${1:key}: \${2:value}",
							'description' => "Trigger a command `on_success:` event",
						],
					],					
					$common_actions
				),
					
				'(.*):decision:' => [
					[
						'caption' => 'outcome:',
						'snippet' => "outcome/\${1:key}:\n\tif@bool: {{\${2:condition}}}\n\tthen:\n\t\t\${3:}",
						'description' => "Run commands when these conditions are true",
					],
				],
				
				'(.*):outcome:' => [
					[
						'caption' => 'if:',
						'snippet' => "if@bool: {{\${1:condition}}}",
						'description' => "When these conditions are true",
					],
					[
						'caption' => 'then:',
						'snippet' => "then:\n\t\${1:}",
						'description' => "Run these commands",
					],
				],
				'(.*):outcome:then:' => $common_actions,
				
				'(.*):data.query:' => $action_base,
				'(.*):data.query:inputs:' => [
					[
						'caption' => 'query:',
						'snippet' => "query@text:\n\ttype:\${1:}worklist.records\n\tof:ticket\n\tquery:()\n\tformat:dictionaries",
						'score' => 2000,
					],
					[
						'caption' => 'query_params:',
						'snippet' => "query_params:\n\t\${1:}",
						'score' => 1999,
					],
				],
				
				'(.*):decrypt.pgp:' => $action_base,
				'(.*):decrypt.pgp:inputs:' => [
					[
						'caption' => 'message:',
						'snippet' => "message:\n\t\${1:}",
					],
				],
				
				'(.*):email.parse:' => $action_base,
				'(.*):email.parse:inputs:' => [
					[
						'caption' => 'message:',
						'snippet' => "message:\n\t\${1:}",
					],
				],
				
				'(.*):encrypt.pgp:' => $action_base,
				'(.*):encrypt.pgp:inputs:' => [
					[
						'caption' => 'message:',
						'snippet' => "message:\n\t\${1:}",
					],
					'public_keys:',
				],
				'(.*):encrypt.pgp:inputs:public_keys:' => [
					'fingerprint: a1b2c3',
					'id: 123',
					'ids@csv: 1,2,3',
					'uri:',
				],
				'(.*):encrypt.pgp:inputs:public_keys:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'gpg_public_key' => null,
					]
				],
				
				'(.*):file.read:' => $action_base,
				'(.*):file.read:inputs:' => [
					[
						'caption' => 'uri:',
						'snippet' => 'uri:',
						'description' => "The `attachment` or `automation_resource` record to read content from",
						'score' => 2000,
					],
					[
						'caption' => 'extract:',
						'snippet' => "extract:",
						'description' => "Extract a file by path from an archive",
					],
					[
						'caption' => 'length:',
						'snippet' => "length: 4096",
						'description' => "Read this many bytes from the content (omit to read the full content)",
					],
					[
						'caption' => 'offset:',
						'snippet' => "offset: 4096",
						'description' => "Start reading content after this many bytes",
					],
				],
				'(.*):file.read:inputs:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'attachment' => null,
						'automation_resource' => null,
					],
				],
				
				'(.*):function:' => array_merge(
					[
						[
							'caption' => 'uri:',
							'snippet' => "uri:",
							'description' => "The automation function to run",
							'score' => 2000,
						],
					],
					$action_base
				),
				'(.*):function:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.automation.function'
							]
						]
					]
				],
				'(.*):function:inputs:' => [
					'type' => 'automation-inputs',
				],
				
				'(.*):http.request:' => $action_base,
				'(.*):http.request:inputs:' => [
					[
						'caption' => 'url:',
						'snippet' => 'url: https://',
						'score' => 2000,
					],
					[
						'caption' => 'method:',
						'snippet' => "method: \${1:GET}",
						'score' => 1999,
					],
					[
						'caption' => 'headers:',
						'snippet' => "headers:\n\t\${1:X-Example: Value}",
						'score' => 1998,
					],
					[
						'caption' => 'body:',
						'snippet' => "body:\n\t",
						'score' => 1997,
					],
					'timeout:',
					'authentication:',
				],
				'(.*):http.request:inputs:authentication:' => [
					'type' => 'cerb-uri',
					'params' => [
						'connected_account' => null,
					]
				],
				'(.*):http.request:inputs:method:' => [
					'GET',
					'POST',
					'PUT',
					'DELETE',
					'PATCH',
					'HEAD',
					'OPTIONS'
				],
				
				'(.*):log:' => $action_base,
				'(.*):log.alert:' => $action_base,
				'(.*):log.error:' => $action_base,
				'(.*):log.warn:' => $action_base,
				
				'(.*):metric.increment:' => $action_base,
				'(.*):metric.increment:inputs:' => [
					[
						'caption' => 'metric_name:',
						'snippet' => 'metric_name:',
						'score' => 2000,
					],
					[
						'caption' => 'dimensions:',
						'snippet' => "dimensions:\n\t",
						'score' => 1999,
					],
					'is_realtime@bool: yes',
					'timestamp@date: now',
					'values:',
				],
				'(.*):metric.increment:inputs:metric_name:' => [
					'type' => 'record-field',
					'params' => [
						'record_type' => 'metric',
						'field_key' => 'name',
					]
				],
				'(.*):metric.increment:inputs:dimensions:' => [
					'type' => 'metric-dimensions',
				],
				
				'(.*):queue.pop:' => $action_base,
				'(.*):queue.pop:inputs:' => [
					[
						'caption' => 'queue_name:',
						'snippet' => 'queue_name:',
						'score' => 2000,
					],
					"limit: 10",
				],
				'(.*):queue.pop:inputs:queue_name:' => [
					'type' => 'record-field',
					'params' => [
						'record_type' => 'queue',
						'field_key' => 'name',
					]
				],
				
				'(.*):queue.push:' => $action_base,
				'(.*):queue.push:inputs:' => [
					[
						'caption' => 'queue_name:',
						'snippet' => 'queue_name:',
						'score' => 2000,
					],
					[
						'caption' => 'messages@list:',
						'snippet' => "# [TODO] Build a collection with one message per line\nmessages@list:\n\tMessage 1\n\tMessage 2",
					],
					[
						'caption' => 'messages@key:',
						'snippet' => "# [TODO] Refer to a key with a collection of messages\nmessages@key: \${1:key}",
					],
				],
				'(.*):queue.push:inputs:queue_name:' => [
					'type' => 'record-field',
					'params' => [
						'record_type' => 'queue',
						'field_key' => 'name',
					]
				],
				
				'(.*):record.create:' => $action_base,
				'(.*):record.delete:' => $action_base,
				'(.*):record.get:' => $action_base,
				'(.*):record.search:' => $action_base,
				'(.*):record.update:' => $action_base,
				'(.*):record.upsert:' => $action_base,
				
				'(.*):record.create:inputs:' => [
					[
						'caption' => 'record_type:',
						'snippet' => 'record_type:',
						'score' => 2000,
						'description' => "The record type to create",
					],
					[
						'caption' => 'fields:',
						'snippet' => "fields:\n\t\${1:}",
						'score' => 1999,
						'description' => "The record fields to set",
					],
					'expand:',
				],
				'(.*):record.create:inputs:fields:' => [
					'type' => 'record-fields',
					'params' => [
						'parent_key' => 'record_type',
					],
				],
				'(.*):record.create:inputs:record_type:' => [
					'type' => 'record-type',
				],
				
				'(.*):record.delete:inputs:' => [
					[
						'caption' => 'record_type:',
						'snippet' => 'record_type:',
						'score' => 2000,
						'description' => "The record type to delete",
					],
					'record_id:',
				],
				'(.*):record.delete:inputs:record_type:' => [
					'type' => 'record-type',
				],
				
				'(.*):record.get:inputs:' => [
					[
						'caption' => 'record_type:',
						'snippet' => 'record_type:',
						'score' => 2000,
						'description' => "The record type to load",
					],
					'record_id:',
				],
				'(.*):record.get:inputs:record_type:' => [
					'type' => 'record-type',
				],
				
				'(.*):record.search:inputs:' => [
					[
						'caption' => 'record_type:',
						'snippet' => 'record_type:',
						'score' => 2000,
						'description' => "The record type to search",
					],
					[
						'caption' => 'record_query:',
						'snippet' => "record_query@text:\n\t\${1:}",
						'score' => 1999,
						'description' => "The query to filter records with",
					],
					[
						'caption' => 'record_query_params:',
						'snippet' => "record_query_params:\n\t\${1:}",
						'score' => 1998,
						'description' => "The key/value pairs to substitute in the query",
					],
					'record_expand:',
				],
				'(.*):record.search:inputs:record_type:' => [
					'type' => 'record-type',
				],
				
				'(.*):record.update:inputs:' => [
					[
						'caption' => 'record_type:',
						'snippet' => 'record_type:',
						'score' => 2000,
						'description' => "The record type to update",
					],
					[
						'caption' => 'record_id:',
						'snippet' => "record_id: \${1:123}",
						'score' => 1999,
						'description' => "The record ID to update",
					],
					[
						'caption' => 'fields:',
						'snippet' => "fields:\n\t\${1:}",
						'score' => 1998,
						'description' => "The record fields to update",
					],
					'expand:',
				],
				'(.*):record.update:inputs:fields:' => [
					'type' => 'record-fields',
					'params' => [
						'parent_key' => 'record_type',
					],
				],
				'(.*):record.update:inputs:record_type:' => [
					'type' => 'record-type',
				],
				
				'(.*):record.upsert:inputs:' => [
					[
						'caption' => 'record_type:',
						'snippet' => 'record_type:',
						'score' => 2000,
						'description' => "The record type to insert or update",
					],
					[
						'caption' => 'record_query:',
						'snippet' => "record_query@text:\n\t\${1:}",
						'score' => 1999,
						'description' => "The query to match exactly zero (create) or one (update) records",
					],
					[
						'caption' => 'record_query_params:',
						'snippet' => "record_query_params:\n\t\${1:}",
						'score' => 1998,
						'description' => "The key/value pairs to substitute in the query",
					],
					[
						'caption' => 'fields:',
						'snippet' => "fields:\n\t\${1:}",
						'score' => 1997,
						'description' => "The record fields to insert or update",
					],
				],
				'(.*):record.upsert:inputs:fields:' => [
					'type' => 'record-fields',
					'params' => [
						'parent_key' => 'record_type',
					],
				],
				'(.*):record.upsert:inputs:record_type:' => [
					'type' => 'record-type',
				],
				
				'(.*):repeat:' => [
					[
						'caption' => 'each@csv:',
						'snippet' => "each@csv:",
						'score' => 2000,
					],
					[
						'caption' => 'each@key:',
						'snippet' => "each@key:",
						'score' => 2000,
					],
					[
						'caption' => 'each@list:',
						'snippet' => "each@list:",
						'score' => 2000,
					],
					[
						'caption' => 'each@json:',
						'snippet' => "each@json:",
						'score' => 2000,
					],
					[
						'caption' => 'as:',
						'snippet' => "as: \${key}",
						'score' => 1999,
					],
					[
						'caption' => 'do:',
						'snippet' => "do:\n\t\${1:# [TODO] Your commands to repeat go here}",
						'score' => 1998,
					]
				],
				'(.*):repeat:do:' => $common_actions,
				
				'(.*):while:' => [
					[
						'caption' => 'if:',
						'snippet' => "if@bool: {{\${1:condition}}}",
						'score' => 2000,
					],
					[
						'caption' => 'do:',
						'snippet' => "do:\n\t\${1:# [TODO] Your commands to repeat go here}",
						'score' => 1999,
					],
				],
				'(.*):while:do:' => $common_actions,
				
				'(.*):storage.delete:' => $action_base,
				'(.*):storage.get:' => $action_base,
				'(.*):storage.set:' => $action_base,
				
				'(.*):storage.delete:inputs:' => [
					'key:',
				],
				'(.*):storage.get:inputs:' => [
					'key:',
				],
				'(.*):storage.set:inputs:' => [
					[
						'caption' => 'key:',
						'snippet' => "key:",
						'score' => 2000,
					],
					'value:',
					'expires:',
				],
				
				'(.*):var.expand:' => $action_base,
				'(.*):var.push:' => $action_base,
				'(.*):var.set:' => $action_base,
				'(.*):var.unset:' => $action_base,
				
				'(.*):var.expand:inputs:' => [
					[
						'caption' => 'key:',
						'snippet' => "key:",
						'score' => 2000,
					],
					'paths:',
				],
				'(.*):var.push:inputs:' => [
					[
						'caption' => 'key:',
						'snippet' => "key:",
						'score' => 2000,
					],
					'value:',
				],
				'(.*):var.set:inputs:' => [
					[
						'caption' => 'key:',
						'snippet' => "key:",
						'score' => 2000,
					],
					'value:',
				],
				'(.*):var.unset:inputs:' => [
					'key:',
				],
				
				'start:' => $common_actions,
				
				'inputs:' => [
					[
						'caption' => 'array:',
						'snippet' => "array/\${1:name}:",
						'description' => 'An array of values',
						'interaction' => 'ai.cerb.automationBuilder.input.array',
					],
					[
						'caption' => 'record:',
						'snippet' => "record/\${1:name}:",
						'description' => 'A record dictionary from an ID',
						'interaction' => 'ai.cerb.automationBuilder.input.record',
					],
					[
						'caption' => 'records:',
						'snippet' => "records/\${1:name}:",
						'description' => 'A collection of record dictionaries from IDs',
						'interaction' => 'ai.cerb.automationBuilder.input.records',
					],
					[
						'caption' => 'text:',
						'snippet' => "text/\${1:name}:",
						'description' => 'A text value with an optional type',
						'interaction' => 'ai.cerb.automationBuilder.input.text',
					],
				],
				
				'inputs:array:' => [
					'required@bool: yes',
					'default@list:',
				],
				
				'inputs:record:' => [
					[
						'caption' => 'record_type:',
						'snippet' => "record_type:",
						'score' => 2000,
					],
					'required@bool: yes',
					'expand:',
					'default:',
				],
				'inputs:record:record_type:' => $this->_getRecordTypeSuggestions(),
				
				'inputs:records:' => [
					[
						'caption' => 'record_type:',
						'snippet' => "record_type:",
						'score' => 2000,
					],
					'required@bool: yes',
					'expand:',
					'default:',
				],
				'inputs:records:record_type:' => $this->_getRecordTypeSuggestions(),
				
				'inputs:text:' => [
					[
						'caption' => 'type:',
						'snippet' => "type:",
						'score' => 2000,
					],
					'type_options:',
					'required@bool: yes',
					'default:',
				],
				'inputs:text:type:' => [
					'bool',
					'date',
					'decimal',
					'email',
					'freeform',
					'geopoint',
					'ip',
					'ipv4',
					'ipv6',
					'record_type',
					'number',
					'timestamp',
					'uri',
					'url',
				],
				'inputs:text:type_options:' => [
					'max_length@int: 255',
					'truncate@bool: yes',
				],
			]
		];
		
		// Trigger-specific autocomplete suggestions
		$trigger_schema = $this->getAutocompleteSuggestions();
		
		if(is_array($trigger_schema))
			$schema = array_merge_recursive($trigger_schema, $schema);
		
		return json_encode($schema);
	}
};

abstract class Extension_Toolbar extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.toolbar';
	
	abstract function getPlaceholdersMeta() : array;
	abstract function getInteractionInputsMeta() : array;
	abstract function getInteractionOutputMeta() : array;
	abstract function getInteractionAfterMeta() : array;
}

abstract class Extension_WorkspacePage extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.workspace.page';
	
	static $_registry = [];
	
	/**
	 * @internal
	 * 
	 * @return DevblocksExtensionManifest[]|Extension_WorkspacePage[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_WorkspacePage) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	/**
	 * @internal
	 */
	function exportPageConfigJson(Model_WorkspacePage $page) {
		$json_array = array(
			'page' => array(
				'uid' => 'workspace_page_' . $page->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_PAGE,
				'name' => $page->name,
				'extension_id' => $page->extension_id,
			),
		);
		
		return json_encode($json_array);
	}
	
	/**
	 * @internal
	 */
	function importPageConfigJson($import_json, Model_WorkspacePage $page) {
		if(!is_array($import_json) || !isset($import_json['page']))
			return false;
		
		return true;
	}
	
	abstract function renderPage(Model_WorkspacePage $page);
	abstract function renderConfig(Model_WorkspacePage $page, $params=[], $params_prefix=null);
};

abstract class Extension_WorkspaceTab extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.workspace.tab';
	
	static $_registry = [];
	
	/**
	 * @internal
	 * 
	 * @return DevblocksExtensionManifest[]|Extension_WorkspaceTab[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
		
		return $exts;
	}

	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_WorkspaceTab) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab);
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {}
	function importTabConfigJson($import_json, Model_WorkspaceTab $tab) {}
	function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {}
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab, ?string &$error=null) : bool { return true; }
};

abstract class Extension_WorkspaceWidgetDatasource extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.workspace.widget.datasource';
	
	static $_registry = [];
	
	/**
	 * @internal
	 */
	static function getAll($as_instances=false, $only_for_widget=null) {
		$extensions = DevblocksPlatform::getExtensions('cerberusweb.ui.workspace.widget.datasource', false);
		
		if(!empty($only_for_widget)) {
			$results = [];
			
			foreach($extensions as $id => $ext) {
				if(in_array($only_for_widget, array_keys($ext->params['widgets'][0])))
					$results[$id] = ($as_instances) ? $ext->createInstance() : $ext;
			}
			
			$extensions = $results;
			unset($results);
		}
		
		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($extensions, 'name');
		
		return $extensions;
	}

	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_WorkspaceWidgetDatasource) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderConfig(Model_WorkspaceWidget $widget, $params=[], $params_prefix=null);
	abstract function getData(Model_WorkspaceWidget $widget, array $params=[], $params_prefix=null);
};

interface ICerbWorkspaceWidget_ExportData {
	function exportData(Model_WorkspaceWidget $widget, $format=null);
};

abstract class Extension_WorkspaceWidget extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.workspace.widget';
	
	static $_registry = [];
	
	/**
	 * @internal
	 */
	static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('cerberusweb.ui.workspace.widget', $as_instances);
		
		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($extensions, 'name');
		
		return $extensions;
	}

	/**
	 * @internal
	 * 
	 * @param string $extension_id
	 * @return Extension_WorkspaceWidget|NULL
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
				&& $extension instanceof Extension_WorkspaceWidget) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function render(Model_WorkspaceWidget $widget);
	abstract function invoke(string $action, Model_WorkspaceWidget $model);
	abstract function renderConfig(Model_WorkspaceWidget $widget);
	abstract function invokeConfig($config_action, Model_WorkspaceWidget $model);
	abstract function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool;
	
	/**
	 * @internal
	 */
	public function export(Model_WorkspaceWidget $widget) {
		$widget_json = [
			'widget' => [
				'uid' => 'workspace_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
				'label' => $widget->label,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'params' => $widget->params,
			]
		];
		
		return json_encode($widget_json);
	}

	/**
	 * @internal
	 */
	public static function getViewFromParams($widget, $params, $view_id) {
		if(false == ($view = C4_AbstractViewLoader::getView($view_id))) {
			if(!isset($params['worklist_model']))
				return false;
			
			$view_model = $params['worklist_model'];
			
			if(false == ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($view_model, $view_id)))
				return false;
			
			$view->_init_checksum = uniqid();
		}
		
		$view->setAutoPersist(true);
		
		// Check for quick search
		$mode = $params['search_mode'] ?? null;
		$q = $params['quick_search'] ?? null;
		
		if($mode == 'quick_search' && $q)
			$view->addParamsWithQuickSearch($q, true);
		
		return $view;
	}
};

abstract class CerberusCronPageExtension extends DevblocksExtension {
	const POINT = 'cerberusweb.cron';
	
	const PARAM_ENABLED = 'enabled';
	const PARAM_LOCKED = 'locked';
	const PARAM_DURATION = 'duration';
	const PARAM_TERM = 'term';
	const PARAM_LASTRUN = 'lastrun';
	
	/**
	 * runs scheduled task
	 * 
	 * @internal
	 */
	abstract function run();
	
	/**
	 * @internal
	 */
	function _run() {
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$lastrun = $this->getParam(self::PARAM_LASTRUN, time());
		
		// [TODO] By setting the locks directly on these extensions, we're invalidating them during the same /cron
		//	and causing redundant retrievals of the params from the DB
		$this->setParam(self::PARAM_LOCKED, time());
		
		$this->run();

		$secs = self::getIntervalAsSeconds($duration, $term);
		$ran_at = time();
		
		if(!empty($secs)) {
			$gap = time() - $lastrun; // how long since we last ran
			$extra = $gap % $secs; // we waited too long to run by this many secs
			$ran_at = time() - $extra; // go back in time and lie
		}
		
		$this->setParam(self::PARAM_LASTRUN, $ran_at);
		$this->setParam(self::PARAM_LOCKED, 0);
	}
	
	/**
	 * @internal
	 * 
	 * @param boolean $is_ignoring_wait Ignore the wait time when deciding to run
	 * @return boolean
	 */
	public function isReadyToRun($is_ignoring_wait=false) {
		$locked = $this->getParam(self::PARAM_LOCKED, 0);
		$enabled = $this->getParam(self::PARAM_ENABLED, false);
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$lastrun = $this->getParam(self::PARAM_LASTRUN, 0);
		
		// If we've been locked too long then unlock
		if($locked && $locked < (time() - 10 * 60)) {
			$locked = 0;
		}

		// Make sure enough time has elapsed.
		$checkpoint = ($is_ignoring_wait)
			? (0) // if we're ignoring wait times, be ready now
			: ($lastrun + self::getIntervalAsSeconds($duration, $term)) // otherwise test
			;

		// Ready?
		return (!$locked && $enabled && time() >= $checkpoint) ? true : false;
	}
	
	/**
	 * @internal
	 */
	static public function getIntervalAsSeconds($duration, $term) {
		$seconds = 0;
		
		if($term=='d') {
			$seconds = $duration * 24 * 60 * 60; // x hours * mins * secs
		} elseif($term=='h') {
			$seconds = $duration * 60 * 60; // x * mins * secs
		} else {
			$seconds = $duration * 60; // x * secs
		}
		
		return $seconds;
	}
	
	public function configure($instance) {}
	
	public function saveConfiguration() {}
};

abstract class Extension_CommunityPortal extends DevblocksExtension implements DevblocksHttpRequestHandler {
	const ID = 'cerb.portal';
	
	private $portal = '';
	
	static $_registry = [];
	
	/**
	 * @internal
	 */
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_CommunityPortal) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $instance) {
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
	}
};

abstract class Extension_ConnectedServiceProvider extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.connected_service.provider';
	
	static $_registry = [];
	
	abstract function renderConfigForm(Model_ConnectedService $service);
	abstract function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null);
	
	abstract function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account);
	abstract function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error=null);
	
	abstract function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options=[]) : bool;
	
	abstract function handleActionForService(string $action);
};