<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

abstract class Extension_AutomationApiCommand extends \DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.automation.api_command';
	
	abstract function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array;
	abstract function run(array $params=[], &$error=null) : array|false;
}

abstract class Extension_CustomField extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.custom_field';
	
	abstract function renderConfig(Model_CustomField $field);
	abstract function getDictionaryValues(Model_CustomField $field, $value, $as_keys=true, &$token_values=[]);
	abstract function getFieldForSubtotalKey(array &$meta, Model_CustomField $custom_field, string $field_key, string $table, string $primary_key);
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
				if(!($custom_record = DAO_CustomRecord::getByUri($uri)))
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
	abstract function handleActionForPage(string $action, ?string $scope=null);
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
	abstract function send(Model_DevblocksOutboundEmail $email_model, Model_MailTransport $model);
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

	// The `cerb-icons` glyph name for this widget type (from the `icon` manifest param); `dashboard` when unset.
	function getIcon() : string {
		return $this->manifest->params['icon'] ?? 'dashboard';
	}
	
	/**
	 * @internal
	 */
	public function export(Model_ProfileWidget $widget) {
		$widget_json = [
			'widget' => [
				'uid' => 'profile_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
				'name' => $widget->name,
				'icon' => $widget->icon,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'extension_params' => $widget->extension_params,
			]
		];
		
		if($widget->options_kata)
			$widget_json['widget']['options_kata'] = $widget->options_kata;
		
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
		$extension_id = strval($extension_id);
		
		if(array_key_exists($extension_id, self::$_registry))
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

	// The `cerb-icons` glyph name for this widget type (from the `icon` manifest param); `dashboard` when unset.
	function getIcon() : string {
		return $this->manifest->params['icon'] ?? 'dashboard';
	}
	
	/**
	 * @internal
	 */
	public function export(Model_CardWidget $widget) {
		$widget_json = [
			'widget' => [
				'uid' => 'card_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_CARD_WIDGET,
				'name' => $widget->name,
				'icon' => $widget->icon,
				'record_type' => $widget->record_type,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'extension_params' => $widget->extension_params,
			]
		];
		
		if($widget->options_kata)
			$widget_json['widget']['options_kata'] = $widget->options_kata;
		
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

	// Shared validator for JSON-backed resource types (map geometry/points/properties). Leniently accepts an
	// empty upload; rejects malformed JSON with a real error. Rewinds $fp so the caller can still store it.
	protected static function _validateJsonContentData($fp, &$error=null) : bool {
		if(!is_resource($fp))
			return true;

		$bytes = stream_get_contents($fp);
		fseek($fp, 0);

		if($bytes === '' || $bytes === false)
			return true;

		json_decode($bytes);

		if(json_last_error() !== JSON_ERROR_NONE) {
			$error = 'The uploaded file is not valid JSON: ' . json_last_error_msg();
			return false;
		}

		return true;
	}
	
	/**
	 * @param Model_Resource $resource
	 * @param Model_Resource_ContentData $content_data
	 * @return bool
	 */
	function getContentResource(Model_Resource $resource, Model_Resource_ContentData &$content_data) {
		if($resource->is_dynamic) {
			// Do we have cached data?
			if(
				$resource->cache_until > time()
				&& $resource->storage_extension
				&& $resource->storage_key
			) {
				return $this->_returnCachedData($resource, $content_data);
			
			// If we don't have cached data, generate it
			} else {
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
				
				$expires_at = $file['expires_at'] ?? null;
				$content_data->expires_at = $expires_at;
				
				// If we have a future expiration, cache the data
				if($expires_at) {
					DAO_Resource::update($resource->id, [
						DAO_Resource::CACHE_UNTIL => $expires_at,
					]);
					
					Storage_Resource::put($resource->id, $content_data->data);
					
					fseek($content_data->data, 0);
				}
			}
			
		} else {
			return $this->_returnCachedData($resource, $content_data);
		}
		
		return true;
	}
	
	private function _returnCachedData(Model_Resource $resource, Model_Resource_ContentData $content_data) {
		$content_data->data =
			($resource->storage_size > 1024000)
				? DevblocksPlatform::getTempFile()
				: fopen('php://memory', 'w')
		;
		
		$content_data->expires_at = time() + 604800;
		
		if(!is_resource($content_data->data))
			return false;
		
		if(!(Storage_Resource::get($resource->id, $content_data->data)))
			return false;
		
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
	abstract function getUsageMeta(string $automation_name) : array;
	abstract function getAutocompleteSuggestions() : array;
	abstract function getEditorToolbarItems(array $toolbar) : array;

	public static function getFormComponentClass(string $type) : ?string {
		if(!method_exists(static::class, 'getFormComponentMeta'))
			return null;
		$meta = static::getFormComponentMeta();
		$class = $meta[$type]['class'] ?? null;
		return is_string($class) && strlen($class) && class_exists($class) ? $class : null;
	}
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
		
		if(($editor_toolbar = DAO_Toolbar::getByName('automation.editor'))) {
			$editor_toolbar_dict = DevblocksDictionaryDelegate::instance([
				'trigger_id' => $this->manifest->id,
				'trigger_name' => $this->manifest->name,
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
			]);
			
			if(($editor_toolbar_items = $editor_toolbar->getKata($editor_toolbar_dict))) {
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
	
	public function getEventToolbar() : array {
		$toolbar = [
			'interaction/automation' => [
				'icon' => 'search',
				'tooltip' => 'Find or create an automation',
				'uri' => 'ai.cerb.eventHandler.automation',
			],
		];
		
		if(method_exists($this, 'getEventToolbarItems'))
			$toolbar = $this->getEventToolbarItems($toolbar);
		
		return $toolbar;
	}
	
	function getEventPlaceholders() : array {
		return $this->getInputsMeta();
	}
	
	public function getAutocompleteSuggestionsArray() : array {
		$trigger_features = current($this->manifest->params['features'] ?? []);
		
		$api_commands = array_values(array_map(
			fn($mft) => $mft->id,
			Extension_AutomationApiCommand::getAll(false)
		));
		
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
				'caption' => 'api.command:',
				'snippet' => "api.command:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => 'Invoke low-level API commands',
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
				'caption' => 'file.write:',
				'snippet' => "file.write:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Write attachment content",
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
				'caption' => 'kata.parse:',
				'snippet' => "kata.parse:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Parse a KATA tree and substitute placeholders",
			],
			[
				'caption' => 'llm.agent:',
				'snippet' => "llm.agent:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Multi-turn chat with tool use, memory, and transcripts using a large language model",
			],
			[
				'caption' => 'llm.chat:',
				'snippet' => "llm.chat:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Single-turn chat completion using a large language model",
			],
			[
				'caption' => 'llm.embed:',
				'snippet' => "llm.embed:\n\tinputs:\n\t\t\${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
				'description' => "Generate text vector embeddings using a large language model",
			],
			[
				'caption' => 'llm.router:',
				'snippet' => "llm.router:\n\tinputs:\n\t\t\${1:}\n\toutput: routed\n\t#on_success:\n\t#on_error:\n",
				'description' => "Resolve an agent model router to a `models:` list",
				'docHTML' => 'Resolve an <b>agent model router</b> to the <code>models:</code> map that <code>llm.agent:</code>, <code>llm.chat:</code>, and <code>agentPrompt</code> consume &mdash; then feed it with <code>model@key: routed:models</code>.<br><br>Use this only when the list needs <b>handling before it\'s consumed</b> (filtering, round-robin, feeding two commands from one resolution). To simply USE the models, name an <code>agent:</code> or omit the config entirely and the default router supplies them.<br><br>Omitting <code>router:</code> resolves the default router &mdash; which is what portable automations should do, since a hardcoded router name is yours, not the customer\'s.',
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
		
		if (array_key_exists('await', $trigger_features ?: [])) {
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
			'' => [
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
				
				'(.*):api.command:' => $action_base,
				'(.*):api.command:inputs:' => [
					[
						'caption' => 'name:',
						'snippet' => "name: \${1:}",
						'score' => 2000,
					],
					[
						'caption' => 'params:',
						'snippet' => "params:\n\t\${1:}",
						'score' => 1999,
					],
				],
				'(.*):api.command:inputs:name:' => $api_commands,
				'(.*):api.command:inputs:params:(.*):?' => [
					'type' => 'automation-command-params',
				],
				
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
						'snippet' => "message@text:\n\t\${1:}",
					],
				],
				
				'(.*):email.parse:' => $action_base,
				'(.*):email.parse:inputs:' => [
					[
						'caption' => 'message:',
						'snippet' => "message@text:\n\t\${1:}",
					],
				],
				
				'(.*):encrypt.pgp:' => $action_base,
				'(.*):encrypt.pgp:inputs:' => [
					[
						'caption' => 'message:',
						'snippet' => "message@text:\n\t\${1:}",
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
						'caption' => 'filters:',
						'snippet' => "filters:",
						'description' => "Apply filters to the bytes being read (e.g. gzip)",
					],
					[
						'caption' => 'length:',
						'snippet' => "length: 4096",
						'description' => "Read this many bytes from the content (omit to read the first 4MB)",
					],
					[
						'caption' => 'length_split:',
						'snippet' => "length_split@json: \"\\n\"",
						'description' => "When using `length:` truncate at the last occurrence of this delimiter within the read bytes",
					],
					[
						'caption' => 'offset:',
						'snippet' => "offset: 4096",
						'description' => "Start reading content after this many bytes",
					],
					[
						'caption' => 'password:',
						'snippet' => "password:",
						'description' => "Set an optional password for encrypted files",
					],
				],
				'(.*):file.read:inputs:filters:' => [
					'gzip.decompress:',
				],
				'(.*):file.read:inputs:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'attachment' => null,
						'automation_resource' => null,
						'resource' => null,
					],
				],
				
				'(.*):file.write:' => $action_base,
				'(.*):file.write:inputs:' => [
					'content:',
					'expires@date: +15 mins',
					'mime_type:',
					'name:',
					[
						'caption' => 'uri:',
						'snippet' => 'uri:',
						'docHTML' => '<b>uri:</b> (optional)<br>Append content to an existing automation resource',
					]
				],
				'(.*):file.write:inputs:content:' => [
					'bytes:',
					'text:',
					'zip:',
				],
				'(.*):file.write:inputs:content:zip:' => [
					'files:',
					'password:',
				],
				'(.*):file.write:inputs:content:zip:files:' => [
					[
						'caption' => 'file:',
						'snippet' => "file/\${0:key}:\n\t\${1:}",
					]
				],
				'(.*):file.write:inputs:content:zip:files:file:' => [
					'bytes:',
					'path:',
					'uri:',
				],
				'(.*):file.write:inputs:content:zip:files:file:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'attachment' => null,
						'automation_resource' => null,
					],
				],
				'(.*):file.write:inputs:name:' => [
					'example.txt',
					'example.json',
					'example.png',
				],
				'(.*):file.write:inputs:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation_resource' => null,
					]
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
					'response:',
					'timeout:',
					'authentication:',
				],
				'(.*):http.request:inputs:authentication:' => [
					'type' => 'cerb-uri',
					'params' => [
						'connected_account' => null,
					]
				],
				'(.*):http.request:inputs:headers:' => [
					'Authorization: Bearer {token}',
					'Content-Type: application/json',
					'Content-Type: application/x-www-form-urlencoded',
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
				'(.*):http.request:inputs:response:' => [
					'resource:',
				],
				'(.*):http.request:inputs:response:resource:' => [
					'expires@date:',
				],
				
				'(.*):llm.agent:' => array_merge(
					$action_base,
					[
						[
							'caption' => 'on_tool:',
							'snippet' => "on_tool:\n\t# [TODO] Inputs: {{__tool.name}} and {{__tool.parameters}}\n\ttool.return:\n\t\tcontent: This is the tool result.",
							'description' => "Run these commands when a tool is invoked",
						],
					]
				),
				'(.*):llm.agent:on_tool:' => array_merge(
					$common_actions,
					[
						[
							'caption' => 'tool.return:',
							'snippet' => "tool.return:\n\tcontent: \${1:value}",
							'description' => "Return tool results to an LLM agent",
						],
					],
				),
				'(.*):llm.agent:inputs:' => [
					[
						'caption' => 'agent:',
						'snippet' => "agent: @\${1:mention}",
						'score' => 2001,
						'docHTML' => 'Run as an <b>AI worker</b> &mdash; whom the turn is attributed to, AND (via that agent\'s <b>model router</b>) where its models come from. Takes an <code>@mention</code>, a bare handle, a worker id, or a <code>cerb:worker:&lt;id|mention&gt;</code> URI.<br><br>An explicit <code>model:</code> or <code>llm:</code> still wins; naming an agent is what lets a <b>portable</b> automation avoid naming models at all. Omit everything and the system default router is used.',
					],
					[
						'caption' => 'llm:',
						'snippet' => "llm:",
						'score' => 2000,
					],
					[
						'caption' => 'commands:',
						'snippet' => "commands:\n\tcommand/\${1:compact}:",
						'score' => 1996,
						'docHTML' => 'Built-in <b>/commands</b> this agent honors, opted in by bare key (<code>command/compact:</code>). A command is only acted on when it LEADS the user\'s message, and it replaces that turn rather than preceding it &mdash; the message is never added to the conversation and no answer is generated. Off by default and per-node: an undeclared <code>/whatever</code> just reaches the model as ordinary text. Unrelated to the <code>agentPrompt</code> element\'s own <code>commands:</code>, which only decides what the composer OFFERS.',
					],
					[
						'caption' => 'system_prompt:',
						'snippet' => "system_prompt@text:\n\t\${1:You are a helpful AI assistant.}",
						'score' => 1999,
					],
					[
						'caption' => 'messages:',
						'snippet' => "messages:\n\tmessage:\n\t\trole: user\n\t\tcontent@text:\n\t\t\t\${1:Hello!}\n",
						'score' => 1998,
					],
					[
						'caption' => 'tools:',
						'snippet' => "tools:",
						'score' => 1997,
					],
					[
						'caption' => 'mounts:',
						'snippet' => "mounts:",
						'score' => 1996,
						'docHTML' => 'Mount agent filesystems and give the agent an <code>agent_fs</code> tool to browse them. Each key is a filesystem name. Leave the block <b>empty</b> to mount nothing but <code>/tmp</code> — a scratch pad plus the <code>|</code> scripting pipeline, so the agent can park and transform text without spending context on it.',
					],
					[
						'caption' => 'thinking_level:',
						'snippet' => "thinking_level: low",
						'docHTML' => '<b>thinking_level:</b>Adjust the reasoning effort based on the complexity of a request.<br><code>low</code> or <code>high</code> for Gemini 3 Pro, any setting for Gemini 3 Flash. Not supported for Gemini 2.5.',
					],
				],
				'(.*):llm.agent:inputs:llm:gemini:api_endpoint_url:' => [
					'https://generativelanguage.googleapis.com/v1beta/openai'
				],
				'(.*):llm.agent:inputs:llm:gemini:authentication:' => [
					'type' => 'cerb-uri',
					'params' => [
						'connected_account' => null,
					]
				],
				// https://ai.google.dev/gemini-api/docs/models
				'(.*):llm.agent:inputs:llm:gemini:model:' => [
					'gemini-3-pro-preview',
					'gemini-3-flash-preview',
					'gemini-2.5-pro',
					'gemini-2.5-flash',
					'gemini-2.5-flash-lite',
					'gemini-2.0-flash',
					'gemini-2.0-flash-lite',
				],
				'(.*):llm.agent:inputs:llm:gemini:thinking_level:' => [
					'minimal',
					'low',
					'medium',
					'high',
				],
				'(.*):llm.agent:inputs:llm:groq:' => [
					[
						'caption' => 'model:',
						'snippet' => "model:",
						'score' => 2000,
					],
				],
				// Per-provider `llm:<provider>:` params autocomplete (model lists + knobs) — sourced from the
				// LLM provider extensions and looped by prefix, so it isn't duplicated here.
				...DevblocksPlatform::services()->llm()->getKataProviderAutocomplete('(.*):llm.agent:inputs:llm:'),

					// `model:` reference grammar — the configured `agent_model` names, and under each name that
					// record's OWN provider knobs. Looped over the records, so a new model shows up on reload.
					...DevblocksPlatform::services()->llm()->getKataAgentModelAutocomplete('(.*):llm.agent:inputs:model:'),
				
				'(.*):llm.agent:inputs:commands:' => [
					[
						'caption' => 'command/compact:',
						'snippet' => "command/compact:",
						'score' => 2000,
						'docHTML' => '<b>/compact</b> &mdash; fold the conversation into a summary NOW, instead of waiting for the context threshold. Runs the session\'s own compaction policy with only the WHEN forced, so the model, system prompt and tools (and their cached prefix) are untouched &mdash; only the messages are replaced. Mostly useful for testing compaction on demand.',
					],
				],

				'(.*):llm.agent:inputs:messages:' => [
					[
						'caption' => 'message:',
						'snippet' => "message:\n\trole: user\n\tcontent@text:\n\t\tThis is a test message.\n",
						'score' => 2000,
					]
				],
				'(.*):llm.agent:inputs:messages:message:' => [
					'role: user',
					'content@text:',
					[
						'caption' => 'images:',
						'snippet' => "images:\n\timage/\${1:0}:\n\t\turi: cerb:automation_resource:\${2:token}",
						'description' => "Image inputs (vision models only). Each is a mime-typed resource: a cerb:automation_resource: uri (resolved to base64 at send) or inline base64 data:.",
					],
				],
				'(.*):llm.agent:inputs:messages:message:role:' => [
					'assistant',
					'user',
				],
				'(.*):llm.agent:inputs:messages:message:images:' => [
					[
						'caption' => 'image:',
						'snippet' => "image/\${1:0}:\n\turi: cerb:automation_resource:\${2:token}",
					],
				],
				'(.*):llm.agent:inputs:messages:message:images:image:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:automation_resource:\${1:token}",
						'description' => "A cerb:automation_resource: uri (or bare token) — resolved to base64 at send; mime type comes from the resource.",
					],
					[
						'caption' => 'data:',
						'snippet' => "data@text:\n\t\${1:<base64>}",
						'description' => "Inline base64 image data (requires mime_type:).",
					],
					'mime_type: image/png',
				],
				// Each key IS a filesystem name, so the whole list is baked in here. A volume set is small and
				// `getAll()` is cached — an AJAX suggestion type would be overkill.
				'(.*):llm.agent:inputs:mounts:' => array_values(
					array_map(
						function($filesystem) { /* @var $filesystem Model_AgentFilesystem */
							$doc = array_filter([
								$filesystem->is_disabled ? '(disabled)' : '',
								$filesystem->description,
								sprintf('%d file%s, %s',
									$filesystem->file_count,
									(1 == $filesystem->file_count) ? '' : 's',
									DevblocksPlatform::strPrettyBytes($filesystem->total_bytes)
								),
							]);

							return [
								'caption' => $filesystem->name . ':',
								'snippet' => $filesystem->name . ":\n",
								'docHTML' => '<b>' . DevblocksPlatform::strEscapeHtml($filesystem->name) . '</b><br>'
									. DevblocksPlatform::strEscapeHtml(implode(' — ', $doc)),
							];
						},
						DAO_AgentFilesystem::getAll()
					)
				),
				// One path segment (`[^:]+`), NOT a greedy `(.*)` — that would also match the deeper `…:at:`
				// value scope and offer these key completions while typing a mountpoint.
				'(.*):llm.agent:inputs:mounts:[^:]+:' => [
					[
						'caption' => 'at:',
						'snippet' => "at: /\${1:mountpoint}",
						'docHTML' => 'Where to mount this filesystem. Defaults to <code>/&lt;name&gt;</code>.',
					],
					[
						'caption' => 'filesystem:',
						'snippet' => "filesystem: \${1:name}",
						'docHTML' => '<b>filesystem:</b> The SOURCE volume — a name, an id, or a <code>cerb:agent_filesystem:&lt;name&gt;</code> URI. Omitted, the key itself names the source; give it to decouple the two (mount a per-chat volume at a fixed mountpoint).',
					],
					[
						'caption' => 'mode:',
						'snippet' => "mode: \${1:read-write}",
						'docHTML' => '<b>mode:</b> <code>read-only</code> (default) or <code>read-write</code>. Writes (<code>write</code>/<code>append</code>/<code>edit</code>/<code>copy</code>/<code>rm</code>) are refused on a read-only mount.',
					],
					[
						'caption' => 'create@bool:',
						'snippet' => "create@bool: \${1:yes}",
						'docHTML' => '<b>create:</b> Provision the volume by name if it doesn\'t exist yet (skipped while simulating). A name must start with a letter and contain only letters, digits, and dashes.',
					],
				],
				'(.*):llm.agent:inputs:mounts:[^:]+:mode:' => [
					'read-only',
					'read-write',
				],
				'(.*):llm.agent:inputs:mounts:[^:]+:create:' => [
					'yes',
					'no',
				],
				'(.*):llm.agent:inputs:tools:' => [
					[
						'caption' => 'automation:',
						'snippet' => "automation/\${1:tool_name}:",
						'score' => 2000,
						'docHTML' => '<b>automation:</b> Run an <code>llm.tool</code> automation with inputs as a tool.',
					],
					[
						'caption' => 'tool:',
						'snippet' => "tool/\${1:tool_name}:",
						'score' => 1999,
						'docHTML' => '<b>tool:</b> Run logic in <code>llm.agent:on_tool:</code> and use the <code>tool.return:</code> command.',
					],
				],
				'(.*):llm.agent:inputs:tools:automation:' => [
					'disabled@bool:',
					[
						'caption' => 'icon:',
						'snippet' => "icon: \${1:search}",
						'docHTML' => '<b>icon:</b> A cerb-icons name shown beside this tool in a transcript. The agent never sees it. Defaults to <code>hammer</code>.',
					],
					[
						'caption' => 'labels:',
						'snippet' => "labels:\n\tsummary: \${1:Did the thing}\n\tactive: \${2:Doing the thing}",
						'docHTML' => '<b>labels:</b> Display phrasings for transcripts. The agent never sees these.',
					],
					'uri:',
				],
				'(.*):llm.agent:inputs:tools:automation:labels:' => [
					'summary:',
					'active:',
				],
				'(.*):llm.agent:inputs:tools:automation:disabled:' => [
					'yes',
					'no',
				],
				'(.*):llm.agent:inputs:tools:automation:icon:' => [
					'type' => 'icon',
				],
				'(.*):llm.agent:inputs:tools:automation:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.llm.tool'
							]
						]
					]
				],
				'(.*):llm.agent:inputs:tools:tool:' => [
					[
						'caption' => 'description:',
						'snippet' => "description: \${1:This is a detailed description of the tool.}",
					],
					'disabled@bool:',
					[
						'caption' => 'icon:',
						'snippet' => "icon: \${1:search}",
						'docHTML' => '<b>icon:</b> A cerb-icons name shown beside this tool in a transcript. The agent never sees it. Defaults to <code>hammer</code>.',
					],
					[
						'caption' => 'labels:',
						'snippet' => "labels:\n\tsummary: \${1:Did the thing}\n\tactive: \${2:Doing the thing}",
						'docHTML' => '<b>labels:</b> Display phrasings for transcripts. The agent never sees these.',
					],
					[
						'caption' => 'parameters:',
						'snippet' => "parameters:",
						'docHTML' => '<b>parameters:</b> Optional parameters passed to the tool.',
					]
				],
				'(.*):llm.agent:inputs:tools:tool:labels:' => [
					'summary:',
					'active:',
				],
				'(.*):llm.agent:inputs:tools:tool:disabled:' => [
					'yes',
					'no',
				],
				'(.*):llm.agent:inputs:tools:tool:icon:' => [
					'type' => 'icon',
				],
				'(.*):llm.agent:inputs:tools:tool:parameters:' => [
					[
						'caption' => 'string:',
						'snippet' => "string/\${1:input_name}:\n\tdescription: \${2:A description of this parameter}\n\trequired@bool: \${3:no}",
						'docHTML' => '<b>string:</b> A text-based tool parameter.',
					]
				],
				'(.*):llm.agent:inputs:tools:tool:parameters:string:' => [
					'description:',
					'enum@csv: option1, option2, option3:',
					'required@bool: yes',
				],
				'(.*):llm.agent:inputs:tools:tool:parameters:required:' => [
					'yes',
					'no',
				],
				
				// The AI workers, by @mention. Looped over the records, so a new agent shows up on reload.
				'(.*):llm.agent:inputs:agent:' => DevblocksPlatform::services()->llm()->getKataAgentWorkerAutocomplete(),

				'(.*):llm.router:' => $action_base,
				'(.*):llm.router:inputs:' => [
					[
						'caption' => 'router:',
						'snippet' => "router: \${1}",
						'score' => 2000,
						'docHTML' => 'The agent model router to resolve, by name. <b>Omit it</b> to use the system default &mdash; which is what portable automations should do, since a hardcoded router name is yours and not the customer\'s.',
					],
				],
				// Plain names, not `cerb:` URIs: `router:` only ever points at one record type, so a URI prefix
				// disambiguates nothing. Looped over the records, so a new router shows up on reload.
				'(.*):llm.router:inputs:router:' => DevblocksPlatform::services()->llm()->getKataAgentModelRouterAutocomplete(),

				'(.*):llm.chat:' => $action_base,
				'(.*):llm.chat:inputs:' => [
					[
						'caption' => 'llm:',
						'snippet' => "llm:",
						'score' => 2000,
					],
					[
						'caption' => 'system_prompt:',
						'snippet' => "system_prompt@text:\n\t\${1:You are a helpful AI assistant.}",
						'score' => 1999,
					],
					[
						'caption' => 'messages:',
						'snippet' => "messages:\n\t0:\n\t\trole: user\n\t\tcontent@text: \${1:This is an example}",
						'score' => 1998,
					],
				],
				// Per-provider `llm:<provider>:` params autocomplete (model lists + knobs) — sourced from the
				// LLM provider extensions and looped by prefix, so it isn't duplicated here.
				...DevblocksPlatform::services()->llm()->getKataProviderAutocomplete('(.*):llm.chat:inputs:llm:'),
				
				'(.*):llm.chat:inputs:messages:' => [
					[
						'caption' => 'message:',
						'snippet' => "message:\n\trole: user\n\tcontent@text:\n\t\tThis is a test message.\n",
						'score' => 2000,
					]
				],
				'(.*):llm.chat:inputs:messages:message:' => [
					'role: user',
					'content@text:',
					[
						'caption' => 'images:',
						'snippet' => "images:\n\timage/\${1:0}:\n\t\turi: cerb:automation_resource:\${2:token}",
						'description' => "Image inputs (vision models only). Each is a mime-typed resource: a cerb:automation_resource: uri (resolved to base64 at send) or inline base64 data:.",
					],
				],
				'(.*):llm.chat:inputs:messages:message:role:' => [
					'assistant',
					'user',
				],
				'(.*):llm.chat:inputs:messages:message:images:' => [
					[
						'caption' => 'image:',
						'snippet' => "image/\${1:0}:\n\turi: cerb:automation_resource:\${2:token}",
					],
				],
				'(.*):llm.chat:inputs:messages:message:images:image:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:automation_resource:\${1:token}",
						'description' => "A cerb:automation_resource: uri (or bare token) — resolved to base64 at send; mime type comes from the resource.",
					],
					[
						'caption' => 'data:',
						'snippet' => "data@text:\n\t\${1:<base64>}",
						'description' => "Inline base64 image data (requires mime_type:).",
					],
					'mime_type: image/png',
				],
				
				'(.*):llm.embed:' => $action_base,
				'(.*):llm.embed:inputs:' => [
					[
						'caption' => 'llm:',
						'snippet' => "llm:",
						'score' => 2000,
					],
					[
						'caption' => 'texts:',
						'snippet' => "texts:\n\t0@text:\n\t\t\${1:This is an example}",
						'score' => 1998,
					],
				],
				// Per-provider embedding `llm:<provider>:` params autocomplete — sourced from the Embedding
				// provider extensions, same builder as chat (embedding mode).
				...DevblocksPlatform::services()->llm()->getKataProviderAutocomplete('(.*):llm.embed:inputs:llm:', 'embedding'),
				
				'(.*):kata.parse:' => $action_base,
				'(.*):kata.parse:inputs:' => [
					[
						'caption' => 'kata:',
						'snippet' => 'kata:',
						'score' => 2000,
					],
					[
						'caption' => 'dict:',
						'snippet' => 'dict:',
						'score' => 1999,
					],
					[
						'caption' => 'schema:',
						'snippet' => 'schema:',
						'score' => 1998,
					],
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
					[
						'caption' => 'job_id:',
						'snippet' => 'job_id:',
						'score' => 1999,
						'docHTML' => '(optional) A queue job for grouping messages.'
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
						'caption' => 'job_id:',
						'snippet' => 'job_id:',
						'score' => 1999,
						'docHTML' => '(optional) A queue job for grouping messages.'
					],
					[
						'caption' => 'messages@list:',
						'snippet' => "# [TODO] Build a collection with one message per line\nmessages@list:\n\tMessage 1\n\tMessage 2",
						'score' => 1998,
					],
					[
						'caption' => 'messages@key:',
						'snippet' => "# [TODO] Refer to a key with a collection of messages\nmessages@key: \${1:key}",
						'score' => 1997,
					],
					[
						'caption' => 'available_at@date:',
						'snippet' => "available_at@date: now",
						'score' => 1996,
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
					[
						'caption' => 'disable_events:',
						'snippet' => "disable_events@bool: \${1:yes}",
						'score' => 900,
						'description' => "Don't trigger automations or behaviors after creating this record",
					],
					'expand:',
				],
				'(.*):record.create:inputs:fields:(.*?):' => [
					'type' => 'record-fields-value',
				],
				'(.*):record.create:inputs:fields:' => [
					'type' => 'record-fields',
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
					'record_expand:',
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
					'validation@raw:',
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
					[
						'caption' => 'disable_events:',
						'snippet' => "disable_events@bool: \${1:yes}",
						'score' => 900,
						'description' => "Don't trigger automations or behaviors after modifying this record",
					],
					'expand:',
				],
				'(.*):record.update:inputs:fields:' => [
					'type' => 'record-fields',
				],
				'(.*):record.update:inputs:fields:(.*?):' => [
					'type' => 'record-fields-value',
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
						'caption' => 'disable_events:',
						'snippet' => "disable_events@bool: \${1:yes}",
						'score' => 900,
						'description' => "Don't trigger automations or behaviors after creating or modifying this record",
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
				],
				'(.*):record.upsert:inputs:fields:(.*?):' => [
					'type' => 'record-fields-value',
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
				
				'(.*):tool.return:' => [
					[
						'caption' => 'content:',
						'snippet' => 'content@text: value',
						'score' => 2000,
					]
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
					[
						'caption' => 'value:',
						'snippet' => "value: \${1:text}",
						'score' => 1999,
					],
					[
						'caption' => 'delimiter:',
						'snippet' => "delimiter:",
						'score' => 1998,
					],
				],
				'(.*):var.set:inputs:delimiter:' => [
					':',
					'.',
					'::',
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
					'description:',
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
					'description:',
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
					'description:',
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
					[
						'caption' => 'allowed_values:',
						'snippet' => "allowed_values@csv: \${1:value1, value2}",
						'description' => 'An optional list of allowed values',
					],
					'default:',
					'description:',
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
		if(($trigger_schema = $this->getAutocompleteSuggestions()))
			$schema = array_merge_recursive($trigger_schema, $schema);
		
		return $schema;
	}
	
	public function getAutocompleteSuggestionsJson(): string {
		if(($schema = $this->getAutocompleteSuggestionsArray()))
			return json_encode($schema);
		
		return '';
	}
};

abstract class Extension_Toolbar extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.toolbar';
	
	abstract function getPlaceholdersMeta() : array;
	abstract function getInteractionInputsMeta() : array;
	abstract function getInteractionOutputMeta() : array;
	abstract function getInteractionAfterMeta() : array;
	
	public function getAutocompleteSuggestions() : array {
		return [
			'' => [
				[
					'caption' => 'interaction:',
					'snippet' => 'interaction/${1:name}:'
				],
				[
					'caption' => 'menu:',
					'snippet' => 'menu/${1:name}:'
				]
			],
			'*' => [
				'(.*):?interaction:' => [
					'after:',
					[
						'caption' => 'uri:',
						'snippet' => 'uri: cerb:automation:${1:}'
					],
					'label:',
					'icon:',
					'tooltip:',
					[
						'caption' => 'hidden:',
						'snippet' => 'hidden@bool: ${1:yes}'
					],
					[
						'caption' => 'badge:',
						'snippet' => 'badge: 123'
					],
					[
						'caption' => 'class:',
						'snippet' => 'class: action-always-show'
					],
					'inputs:'
				],
				'(.*):?interaction:hidden:'=> [
					'yes',
					'no',
					[
						'caption' => '{{key}}',
						'snippet' => '{{${1:key}}}',
					],
					[
						'caption' => '{{not key}}',
						'snippet' => '{{not ${1:key}}}',
					]
				],
				'(.*):?interaction:icon:' => [
					'type' => 'icon'
				],
				'(.*):?interaction:inputs:' => [
					'type' => 'automation-inputs'
				],
				'(.*):?interaction:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.worker'
							]
						]
					]
				],
				'(.*):?menu:' => [
					'label:',
					[
						'caption' => 'hidden:',
						'snippet' => 'hidden@bool: ${1:yes}'
					],
					'icon:',
					'tooltip:',
					'items:'
				],
				'(.*):?menu:icon:' => [
					'type' => 'icon',
				],
				'(.*):?menu:items:' => [
					[
						'caption' => 'interaction:',
						'snippet' => 'interaction/${1:name}:'
					],
					[
						'caption' => 'menu:',
						'snippet' => 'menu/${1:name}:'
					]
				],
			]
		];
	}
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
	function invoke(string $action, Model_WorkspacePage $page, Model_WorkspaceTab $tab) { return false; }
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
		$extension_id = strval($extension_id);
		
		if(array_key_exists($extension_id, self::$_registry))
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

	// The `cerb-icons` glyph name for this widget type (from the `icon` manifest param); `dashboard` when unset.
	function getIcon() : string {
		return $this->manifest->params['icon'] ?? 'dashboard';
	}
	
	/**
	 * @internal
	 */
	public function export(Model_WorkspaceWidget $widget) {
		$widget_json = [
			'widget' => [
				'uid' => 'workspace_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
				'label' => $widget->label,
				'icon' => $widget->icon,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'params' => $widget->params,
			]
		];
		
		if($widget->options_kata)
			$widget_json['widget']['options_kata'] = $widget->options_kata;
		
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
	
	const string PARAM_CONCURRENCY = 'concurrency';
	const string PARAM_DURATION = 'duration';
	const string PARAM_ENABLED = 'enabled';
	const string PARAM_LASTRUN = 'lastrun';
	const string PARAM_LOCKED = 'locked';
	const string PARAM_TERM = 'term';
	
	/**
	 * runs scheduled task
	 * 
	 * @internal
	 */
	abstract function run();
	
	function _run() {
		$is_concurrent = array_key_exists('parallel', $this->manifest->params);
		
		if(!$is_concurrent)
			$this->setParam(self::PARAM_LOCKED, time());

		$started_at = microtime(true) * 1000;

		$this->run();
		$ran_at = time();

		// Track invocation count and duration for this scheduler job
		$elapsed_ms = (microtime(true) * 1000) - $started_at;

		// Write immediately (buffer:false); scheduler jobs run at most once per
		// pass, so buffering wouldn't coalesce anything — it'd only defer the
		// same writes a cycle behind through the metrics queue
		$metrics = DevblocksPlatform::services()->metrics();
		$metrics->increment('cerb.scheduler.invocations', 1, ['job' => $this->id], null, false);
		$metrics->increment('cerb.scheduler.duration', $elapsed_ms, ['job' => $this->id], null, false);

		if(!$is_concurrent) {
			$duration = $this->getParam(self::PARAM_DURATION, 5);
			$term = $this->getParam(self::PARAM_TERM, 'm');
			$last_run = $this->getParam(self::PARAM_LASTRUN, time());
			
			if(($secs = self::getIntervalAsSeconds($duration, $term))) {
				$gap = time() - $last_run; // how long since we last ran
				$extra = $gap % $secs; // we waited too long to run by this many secs
				$ran_at = time() - $extra; // go back in time and lie
			}
			
			$this->setParam(self::PARAM_LOCKED, 0);
		}
		
		$this->setParam(self::PARAM_LASTRUN, $ran_at);
	}
	
	/**
	 *
	 * @param boolean $is_ignoring_wait Ignore the wait time when deciding to run
	 * @return boolean
	 */
	public function isReadyToRun(bool $is_ignoring_wait=false) : bool {
		$enabled = $this->getParam(self::PARAM_ENABLED, false);
		
		if(!$enabled) return false;
		
		$is_concurrent = array_key_exists('parallel', $this->manifest->params);
		
		if($is_concurrent) {
			$queue_services = DevblocksPlatform::services()->queue();
			$concurrency = $this->getParam(self::PARAM_CONCURRENCY, APP_QUEUE_CONCURRENCY_SLOTS);
			return null !== $queue_services->getConcurrencySlot($concurrency);
		}
		
		$locked = $this->getParam(self::PARAM_LOCKED, 0);
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$last_run = $this->getParam(self::PARAM_LASTRUN, 0);
		
		// If we've been locked too long then unlock
		if($locked && $locked < (time() - 10 * 60)) {
			$locked = 0;
		}

		// Make sure enough time has elapsed.
		$checkpoint = ($is_ignoring_wait)
			? (0) // if we're ignoring wait times, be ready now
			: ($last_run + self::getIntervalAsSeconds($duration, $term)) // otherwise test
			;

		// Ready?
		return !$locked && time() >= $checkpoint;
	}
	
	static public function getIntervalAsSeconds($duration, $term) {
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
	 * @param $as_instances
	 * @return Extension_CommunityPortal[] | DevblocksExtensionManifest[]
	 */
	static function getAll(bool $as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::ID, $as_instances);
		
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
			&& $extension instanceof Extension_CommunityPortal) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	/**
	 * @param DevblocksHttpRequest $request
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
