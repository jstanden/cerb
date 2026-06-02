<?php
abstract class DevblocksApplication {

}

trait DevblocksExtensionGetterTrait {
	static $_registry = [];
	
	public static function getAll($as_instances=true, $with_options=null) {
		$extensions = DevblocksPlatform::getExtensions(self::POINT, $as_instances);
		
		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($extensions, 'name');
		
		if(!empty($with_options)) {
			if(!is_array($with_options))
				$with_options = array($with_options);

			foreach($extensions as $k => $controller) {
				@$options = $controller->params['options'][0];

				if(!is_array($options) || empty($options)) {
					unset($extensions[$k]);
					continue;
				}

				if(count(array_intersect(array_keys($options), $with_options)) != count($with_options))
					unset($extensions[$k]);
			}
		}
		
		return $extensions;
	}
	
	public static function getAsManifest(string $extension_id) : DevblocksExtensionManifest|null {
		return self::get($extension_id, false);
	}
	
	public static function getAsInstance(string $extension_id) : DevblocksExtension|null {
		return self::get($extension_id, true);
	}

	/**
	 * @param string $extension_id
	 * @return DevblocksExtensionManifest|DevblocksExtension|null
	 */
	public static function get($extension_id, $as_instance=true) {
		$extension_id = strval($extension_id);
		
		if($as_instance && array_key_exists($extension_id, self::$_registry))
			return self::$_registry[$extension_id];
		
		$extensions = self::getAll(false);
		
		if(!array_key_exists($extension_id, $extensions))
			return null;
		
		$manifest = $extensions[$extension_id]; /* @var $manifest DevblocksExtensionManifest */

		if($as_instance) {
			self::$_registry[$extension_id] = $manifest->createInstance();
			return self::$_registry[$extension_id];
		} else {
			return $extensions[$extension_id];
		}
	}
}

/**
 * The superclass of instanced extensions.
 *
 * @abstract
 * @ingroup plugin
 */
class DevblocksExtension {
	public $manifest = null;
	public $id  = '';

	/**
	 * Constructor
	 *
	 * @private
	 * @param DevblocksExtensionManifest $manifest
	 * @return DevblocksExtension
	 */

	function __construct($manifest=null) {
		if(empty($manifest))
			return;

		$this->manifest = $manifest;
		$this->id = $manifest->id;
	}

	function getParams() {
		return $this->manifest->getParams();
	}

	function setParam($key, $value) {
		return $this->manifest->setParam($key, $value);
	}

	function getParam($key,$default=null) {
		return $this->manifest->getParam($key, $default);
	}
	
	/**
	 * 
	 * @param string $key
	 * @return boolean
	 */
	function hasOption($key) {
		if(!$this->manifest)
			return false;
		
		return $this->manifest->hasOption($key);
	}
};

class Exception_Devblocks extends Exception {};

class Exception_DevblocksAjaxError extends Exception_Devblocks {};

class Exception_DevblocksAjaxValidationError extends Exception_Devblocks {
	private $_field_name = null;
	
	function __construct($message='', $field_name=null) {
		parent::__construct(strval($message) ?: 'An unexpected error occurred.');
		$this->_field_name = $field_name;
	}
	
	/**
	 * 
	 * @return string
	 */
	function getFieldName() {
		return $this->_field_name;
	}
};

class Exception_DevblocksDatabaseQueryError extends Exception_Devblocks {};
class Exception_DevblocksDatabaseQueryTimeout extends Exception_Devblocks {};

interface IDevblocksHandler_Session extends SessionHandlerInterface {
	public function open(string $path, string $name) : bool;
	public function close() : bool;
	public function read($id) : string|false;
	public function write(string $id, string $data) : bool;
	public function destroy(string $id) : bool;
	public function gc(int $max_lifetime) : int|false;
	public static function maint() : bool;
	public static function getAll();
	public static function destroyAll();
};

interface IDevblocksContextPeek {
	function renderPeekPopup($context_id=0, $view_id='', $edit=false);
}

interface IDevblocksContextImport {
	function importGetKeys();
	function importKeyValue($key, $value);
	function importSaveObject(array $fields, array $custom_fields, array $meta);
}

interface IDevblocksContextMerge {
	function mergeGetKeys();
}

interface IDevblocksContextBroadcast {
	function broadcastPlaceholdersGet();
	function broadcastRecipientFieldsGet();
	function broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict);
}

interface IDevblocksContextProfile {
	function profileGetUrl($context_id);
	function profileGetFields($model=null);
}

interface IDevblocksContextAutocomplete {
	function autocomplete($term, $query=null);
}

interface IDevblocksContextUri {
	function autocompleteUri($term, $uri_params=null) : array;
}

interface IDevblocksContextWorkflow {
	function workflowExport(array $ids, DevblocksWorkflowExportModel $export_model, bool $include_children =false) : array;
}

class DevblocksMenuItemPlaceholder {
	public $label = null;
	public $key = null;
	public $l = null;
	public $children = [];
}

interface IDevblocksContextExtension {
	static function isReadableByActor($models, $actor);
	static function isWriteableByActor($models, $actor);
	static function isDeletableByActor($models, $actor);
}

abstract class Extension_DevblocksContext extends DevblocksExtension implements IDevblocksContextExtension {
	const ID = 'devblocks.context';
	
	static $_changed_contexts = [];

	protected static function _isWriteableOnlyByAdmin($models, $actor) {
		// Only admin workers can modify
		if(!($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	/**
	 * @internal
	 */
	static function markContextChanged($context, $context_ids) {
		// If events are disabled, skip.
		if(!DevblocksPlatform::services()->event()->isEnabled())
			return;
		
		if(!is_array($context_ids))
			$context_ids = [$context_ids];

		if(!isset(self::$_changed_contexts[$context]))
			self::$_changed_contexts[$context] = [];

		self::$_changed_contexts[$context] = array_merge(self::$_changed_contexts[$context], $context_ids);
	}

	/**
	 * @internal
	 */
	static function flushTriggerChangedContextsEvents() {
		$eventMgr = DevblocksPlatform::services()->event();

		if(is_array(self::$_changed_contexts))
		foreach(self::$_changed_contexts as $context => $context_ids) {
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'context.update',
					array(
						'context' => $context,
						'context_ids' => $context_ids,
					)
				)
			);
		}

		self::$_changed_contexts = [];
	}

	/**
	 * @param boolean $as_instances
	 * @param array $with_options
	 * @return Extension_DevblocksContext[]
	 */
	public static function getAll($as_instances=false, $with_options=null) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = $as_instances ? DevblocksPlatform::CACHE_CONTEXTS_INSTANCES : DevblocksPlatform::CACHE_CONTEXTS;
		
		if(null === ($contexts = $cache->load($cache_key))) {
			$contexts = DevblocksPlatform::getExtensions('devblocks.context', $as_instances, false);
			
			if (
				class_exists('DAO_CustomRecord', true)
				&& ($custom_records = DAO_CustomRecord::getAll())
				&& is_array($custom_records)
			) {
				foreach ($custom_records as $custom_record) {
					$options = [
						'autocomplete' => '',
						'cards' => '',
						'custom_fields' => '',
						'links' => '',
						'records' => '',
						'search' => '',
						'snippets' => '',
						'va_variable' => '',
						'watchers' => '',
						'workspace' => '',
					];
					
					if (array_key_exists('options', $custom_record->params) && is_array(@$custom_record->params['options'])) {
						if (in_array('hide_search', $custom_record->params['options']))
							unset($options['search']);
						
						if (in_array('attachments', $custom_record->params['options']))
							$options['attachments'] = '';
						
						if (in_array('avatars', $custom_record->params['options']))
							$options['avatars'] = '';
						
						if (in_array('comments', $custom_record->params['options']))
							$options['comments'] = '';
					}
					
					$context_id = sprintf('contexts.custom_record.%d', $custom_record->id);
					$manifest = new DevblocksExtensionManifest();
					$manifest->id = $context_id;
					$manifest->plugin_id = 'cerberusweb.core';
					$manifest->point = Extension_DevblocksContext::ID;
					$manifest->name = $custom_record->name;
					$manifest->file = 'api/dao/abstract_custom_record.php';
					$manifest->class = 'Context_AbstractCustomRecord_' . $custom_record->id;
					$manifest->params = [
						'alias' => $custom_record->uri,
						'dao_class' => 'DAO_AbstractCustomRecord_' . $custom_record->id,
						'view_class' => 'View_AbstractCustomRecord_' . $custom_record->id,
						'acl' => [
							0 => [
								'broadcast' => '',
								'comment' => '',
								'create' => '',
								'delete' => '',
								'export' => '',
								'import' => '',
								'merge' => '',
								'update' => '',
								'update.bulk' => '',
							],
						],
						'options' => [
							0 => $options,
						],
						'names' => [
							0 => [
								DevblocksPlatform::strLower($custom_record->name) => 'singular',
								DevblocksPlatform::strLower($custom_record->name_plural) => 'plural',
							]
						],
					];
					
					if ($as_instances) {
						$contexts[$context_id] = $manifest->createInstance();
					} else {
						$contexts[$context_id] = $manifest;
					}
				}
			}
			
			if($as_instances)
				DevblocksPlatform::sortObjects($contexts, 'manifest->name');
			else
				DevblocksPlatform::sortObjects($contexts, 'name');
			
			$cache->save($contexts, $cache_key);
		}
		
		if ($with_options) {
			if (!is_array($with_options))
				$with_options = [$with_options];
			
			foreach ($contexts as $k => $context) {
				@$options = $as_instances
					? $context->manifest->params['options'][0]
					: $context->params['options'][0]
				;
				
				if (!is_array($options) || empty($options)) {
					unset($contexts[$k]);
					continue;
				}
				
				if (count(array_intersect(array_keys($options), $with_options)) != count($with_options))
					unset($contexts[$k]);
			}
		}
		
		return $contexts;
	}
	
	public static function getUris() {
		$uris = array_map(function($mft) {
			return $mft->params['alias'];
		}, Extension_DevblocksContext::getAll(false));
		
		asort($uris);
		
		return $uris;
	}
	
	/**
	 * @internal
	 */
	public static function getAliasesForAllContexts() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null !== ($results = $cache->load(DevblocksPlatform::CACHE_CONTEXT_ALIASES)))
			return $results;
		
		$contexts = self::getAll(false);
		$results = [];
		
		if(is_array($contexts))
		foreach($contexts as $ctx_id => $ctx) { /* @var $ctx DevblocksExtensionManifest */
			$ctx_aliases = self::getAliasesForContext($ctx);
			
			$uri = $ctx_aliases['uri'] ?? null;
			$results[$uri] = $ctx_id;
			
			if(isset($ctx_aliases['aliases']) && is_array($ctx_aliases['aliases']))
			foreach(array_keys($ctx_aliases['aliases']) as $alias) {
				// If this alias is already defined and it's not the priority URI for this context, skip
				if(isset($results[$alias]) && $alias != $uri)
					continue;
				
				$results[$alias] = $ctx_id;
			}
		}
		
		$cache->save($results, DevblocksPlatform::CACHE_CONTEXT_ALIASES);
		return $results;
	}

	public static function getAliasesForContext(DevblocksExtensionManifest $ctx_manifest) {
		$names = $ctx_manifest->params['names'][0] ?? null;
		$uri = $ctx_manifest->params['alias'] ?? null;
		
		$results = array(
			'singular' => '',
			'plural' => '',
			'singular_short' => '',
			'plural_short' => '',
			'uri' => $uri,
			'aliases' => [],
		);
		
		if(!empty($uri))
			$results['aliases'][$uri] = array('uri');
		
		if(is_array($names) && !empty($names))
		foreach($names as $name => $meta) {
			$name = mb_convert_case($name, MB_CASE_LOWER);
			@$meta = explode(' ', $meta) ?: [];
			
			$is_plural = in_array('plural', $meta);
			$is_short = in_array('short', $meta);
			
			if(!$is_plural && !$is_short && empty($results['singular']))
				$results['singular'] = $name;
			else if($is_plural && !$is_short && empty($results['plural']))
				$results['plural'] = $name;
			else if(!$is_plural && $is_short && empty($results['singular_short']))
				$results['singular_short'] = $name;
			else if($is_plural && $is_short && empty($results['plural_short']))
				$results['plural_short'] = $name;
			
			$results['aliases'][$name] = $meta;
		}
		
		if(empty($results['singular']))
			$results['singular'] = mb_convert_case($ctx_manifest->name, MB_CASE_LOWER);
		
		return $results;
	}
	
	/**
	 * @param string $alias
	 * @param bool $as_instance
	 * @return Extension_DevblocksContext|DevblocksExtensionManifest
	 */
	public static function getByAlias($alias, $as_instance=false) {
		$alias = trim(strval($alias));
		$aliases = self::getAliasesForAllContexts();
		
		// First, try the fully-qualified ID
		if($alias && ($ctx = Extension_DevblocksContext::get($alias, $as_instance))) {
			return $ctx;
		}
		
		// Otherwise, try it as an alias
		$ctx_id = $aliases[$alias] ?? null;
		
		// If this is a valid context, return it
		if($ctx_id && ($ctx = Extension_DevblocksContext::get($ctx_id, $as_instance))) {
			return $ctx;
		}
		
		return null;
	}
	
	public static function getByAliases(array $aliases, $as_instances=false) {
		$results = [];
		
		foreach($aliases as $alias) {
			if(false != ($context_ext = self::getByAlias(strval($alias), $as_instances)))
				if(!array_key_exists($context_ext->id, $results))
					$results[$context_ext->id] = $context_ext;
		}
		
		return $results;
	}
	
	/**
	 * @internal
	 */
	public static function getByViewClass($view_class, $as_instance=false) {
		$contexts = self::getAll(false);

		if(is_array($contexts))
		foreach($contexts as $ctx) { /* @var $ctx DevblocksExtensionManifest */
			if(isset($ctx->params['view_class']) && 0 == strcasecmp($ctx->params['view_class'], $view_class)) {
				if($as_instance) {
					return $ctx->createInstance();
				} else {
					return $ctx;
				}
			}
		}

		return null;
	}
	
	/**
	 * @internal
	 */
	public static function getByMacros($as_instances=false) {
		$contexts = Extension_DevblocksContext::getAll(false);
		$macro_contexts = Extension_DevblocksEvent::getWithMacroContexts();
		$results = [];
		
		array_walk($macro_contexts, function($macro) use ($contexts, &$results, $as_instances) {
			$macro_context = $macro->params['macro_context'];
			
			if(isset($contexts[$macro_context])) {
				if($as_instances) {
					$results[$macro->id] = $contexts[$macro_context]->createInstance();
				} else {
					$results[$macro->id] = $contexts[$macro_context];
				}
			}
		});
		
		return $results;
	}

	/**
	 *
	 * @param string $context
	 * @return Extension_DevblocksContext|false
	 */
	public static function get(string $context, $as_instance=true) {
		static $_cache = [];
		
		if($as_instance && isset($_cache[$context]))
			return $_cache[$context];
		
		$contexts = self::getAll(false);
		
		if(isset($contexts[$context])) {
			$manifest = $contexts[$context]; /* @var $manifest DevblocksExtensionManifest */
			
			if(!$as_instance) {
				return $manifest;
				
			} else {
				$_cache[$context] = $manifest->createInstance();
				return $_cache[$context];
			}
		}

		return false;
	}
	
	/**
	 * @internal
	 */
	static function getOwnerTree(array $contexts=[CerberusContexts::CONTEXT_APPLICATION, CerberusContexts::CONTEXT_ROLE, CerberusContexts::CONTEXT_GROUP, CerberusContexts::CONTEXT_BOT, CerberusContexts::CONTEXT_WORKER]) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$bots = (DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy') && class_exists('DAO_Bot')) ? DAO_Bot::getWriteableByActor($active_worker) : [];
		$groups = DAO_Group::getAll();
		$roles = DAO_WorkerRole::getAll();
		$workers = DAO_Worker::getAllActive();

		$owners = [];

		if(in_array(CerberusContexts::CONTEXT_WORKER, $contexts)) {
			$item = new DevblocksMenuItemPlaceholder();
			$item->label = 'Me';
			$item->l = 'Me';
			$item->key = CerberusContexts::CONTEXT_WORKER . ':' . $active_worker->id;
			
			$owners['Me'] = $item;
		}
		
		// Apps
		
		if(in_array(CerberusContexts::CONTEXT_APPLICATION, $contexts) && $active_worker->is_superuser) {
			$item = new DevblocksMenuItemPlaceholder();
			$item->label = 'Cerb';
			$item->l = 'Cerb';
			$item->key = CerberusContexts::CONTEXT_APPLICATION . ':' . 0;
			$owners['App'] = $item;
		}
		
		// Bots
		
		if(
			DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')
			&& in_array(CerberusContexts::CONTEXT_BOT, $contexts)
		) {
			$bots_menu = new DevblocksMenuItemPlaceholder();
			
			foreach($bots as $bot) {
				$item = new DevblocksMenuItemPlaceholder();
				$item->label = $bot->name;
				$item->l = $bot->name;
				$item->key = CerberusContexts::CONTEXT_BOT . ':' . $bot->id;
				$bots_menu->children[$item->l] = $item;
			}
			
			$owners['Bot'] = $bots_menu;
		}
		
		// Groups
		
		if(in_array(CerberusContexts::CONTEXT_GROUP, $contexts)) {
			$groups_menu = new DevblocksMenuItemPlaceholder();
			
			foreach($groups as $group) {
				if(!$active_worker->isGroupManager($group->id))
					continue;
				
				$item = new DevblocksMenuItemPlaceholder();
				$item->label = $group->name;
				$item->l = $item->label;
				$item->key = CerberusContexts::CONTEXT_GROUP . ':' . $group->id;
				$groups_menu->children[$item->l] = $item;
			}
			
			$owners['Group'] = $groups_menu;
		}
		
		// Roles
		
		if(in_array(CerberusContexts::CONTEXT_ROLE, $contexts)) {
			$roles_menu = new DevblocksMenuItemPlaceholder();
			$role_ownerships = DAO_WorkerRole::getEditableBy($active_worker->id);
			
			// Include roles if the current worker is a role owner or an admin
			foreach($roles as $role) {
				if(!$active_worker->is_superuser && !array_key_exists($role->id, $role_ownerships))
					continue;
				
				$item = new DevblocksMenuItemPlaceholder();
				$item->label = $role->name;
				$item->l = $item->label;
				$item->key = CerberusContexts::CONTEXT_ROLE . ':' . $role->id;
				$roles_menu->children[$item->l] = $item;
			}
			
			$owners['Role'] = $roles_menu;
		}
		
		// Workers
		
		if(in_array(CerberusContexts::CONTEXT_WORKER, $contexts)) {
			$workers_menu = new DevblocksMenuItemPlaceholder();
			
			foreach($workers as $worker) {
				if(!($active_worker->is_superuser || $active_worker->id == $worker->id))
					continue;
				
				$item = new DevblocksMenuItemPlaceholder();
				$item->label = $worker->getName();
				$item->l = $item->label;
				$item->key = CerberusContexts::CONTEXT_WORKER . ':' . $worker->id;
				$workers_menu->children[$item->l] = $item;
			}
			
			$owners['Worker'] = $workers_menu;
		}
		
		return $owners;
	}
	
	static function getPlaceholderTree($labels, $label_separator=' ', $key_separator=' ', $condense=true, $with_custom_uris=true) {
		natcasesort($labels);
		$custom_fields = DAO_CustomField::getAll();
		
		// Convert custom placeholder keys from IDs to URIs
		// We can do this globally when behaviors are removed
		if($with_custom_uris) {
			$labels = array_combine(
				array_map(
					function($label) use ($custom_fields) {
						return preg_replace_callback(
							'#custom_(\d*)#',
							function($matches) use ($custom_fields, $label) {
								if(array_key_exists($matches[1], $custom_fields)) {
									return $custom_fields[$matches[1]]->uri ?: $matches[0];
								}
								return $matches[0];
							},
							$label
						);
					},
					array_keys($labels)
				),
				$labels
			);
		}
		
		return DevblocksPlatform::services()->ui()->menu()->parse($labels, $label_separator, $condense);
	}

	abstract function getRandom();
	abstract function getMeta($context_id);
	abstract function getContext($object, &$token_labels, &$token_values, $prefix=null);
	
	function getContextIdFromAlias($alias) {
		return is_numeric($alias) ? intval($alias) : null;
	}
	
	function getKeyToDaoFieldMap() {
		$map = [];
		
		if($this->hasOption('custom_fields')) {
			$map['fieldsets'] = '_fieldsets';
		}
		
		if($this->hasOption('links')) {
			$map['links'] = '_links';
		}

		return $map;
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$field_map = $this->getKeyToDaoFieldMap();
		$dao_class = $this->getDaoClass();
		$dao_fields = $dao_class::getFields();
		
		$keys = [];
		
		foreach($field_map as $record_key => $dao_key) {
			if(!($dao_field = ($dao_fields[$dao_key] ?? null)))
				continue;
			
			// Only editable fields
			if(!$dao_field->_type->isEditable())
				continue;
			
			$type = $dao_field->_type->getName();
			$notes = [];
				
			switch($type) {
				case 'context':
					//$type = 'record type';
					break;
					
				case 'number':
				case 'uint':
					if(
						array_key_exists('min', $dao_field->_type->_data)
						&& array_key_exists('max', $dao_field->_type->_data)
					) {
						$notes[] = sprintf("(%d-%d)",
							$dao_field->_type->_data['min'],
							$dao_field->_type->_data['max']
						);
					}
					break;
					
				case 'string':
					if(
						array_key_exists('possible_values', $dao_field->_type->_data)
					) {
						$notes[] = sprintf("[%s]",
							implode(', ', array_map(function($v) {
								if(empty($v))
									return '""';
								
								return $v;
							}, $dao_field->_type->_data['possible_values']))
						);
					}
					break;
			}
			
			$record_meta = [
				'key' => $record_key,
				'is_immutable' => !$dao_field->_type->isEditable(),
				'is_required' => $dao_field->_type->isRequired(),
				'is_unique' => $dao_field->_type->isUnique(),
				'notes' => implode('; ', $notes),
				'type' => $type,
			];
			
			if($with_dao_fields)
				$record_meta['dao_field'] = $dao_field;
			
			$keys[$record_key] = $record_meta;
		}
		
		if(array_key_exists('name', $keys)) {
			$aliases = Extension_DevblocksContext::getAliasesForContext($this->manifest);
			$keys['name']['notes'] = "The name of this " . $aliases['singular'];
		}
		
		if(array_key_exists('created', $keys)) {
			$keys['created']['notes'] = "The date/time when this record was created";
		}
		if(array_key_exists('created_at', $keys)) {
			$keys['created_at']['notes'] = "The date/time when this record was created";
		}
		
		if(array_key_exists('owner__context', $keys)) {
			$aliases = Extension_DevblocksContext::getAliasesForContext($this->manifest);
			$keys['owner__context']['notes'] = "The [record type](/docs/records/types/) of this " . $aliases['singular'] . "'s owner: `app`, `role`, `group`, or `worker`";
		}
		if(array_key_exists('owner_id', $keys)) {
			$aliases = Extension_DevblocksContext::getAliasesForContext($this->manifest);
			$keys['owner_id']['notes'] = "The ID of this " . $aliases['singular'] . "'s owner";
		}
		
		if(array_key_exists('updated', $keys)) {
			$keys['updated']['notes'] = "The date/time when this record was last modified";
		}
		if(array_key_exists('updated_at', $keys)) {
			$keys['updated_at']['notes'] = "The date/time when this record was last modified";
		}
		
		if(array_key_exists('image', $keys)) {
			$keys['image']['notes'] = "The profile image, base64-encoded in [data URI format](https://en.wikipedia.org/wiki/Data_URI_scheme)";
			$keys['image']['type'] = 'image';
		}
		
		if(array_key_exists('fieldsets', $keys)) {
			$keys['fieldsets']['type'] = 'fieldsets';
			$keys['fieldsets']['notes'] = 'An array or comma-separated list of [custom fieldset](/docs/records/types/custom_fieldset/) IDs. Prefix an ID with `-` to remove.';
		}
		
		if(array_key_exists('links', $keys)) {
			$keys['links']['type'] = 'links';
			$keys['links']['notes'] = 'An array of record `type:id` tuples to link to. Prefix with `-` to unlink.';
		}
		
		return $keys;
	}
	
	function getKeyAutocompleteSuggestions() : array {
		return [];
	}
	
	static function getKeyAutocompleteBit() : array {
		return ['0', '1'];
	}
	
	static function getKeyAutocompleteDate() : array {
		return ['now', 'tomorrow 8am', 'Friday 5pm', '2025-12-31 12:00:00'];
	}
	
	static function getKeyAutocompleteLinks() : array {
		return [
			'ticket:123',
		];
	}
	
	static function getKeyAutocompleteRecordFieldSearch(string $record_type, string $field_key) : array {
		return [
			'type' => 'record-field',
			'params' => [
				'record_type' => $record_type,
				'field_key' => $field_key,
			]
		];
	}
	
	static function getAutocompleteRecordOwnerTypes() {
		$owner_record_contexts = Extension_DevblocksContext::getAll(false, ['owner']);
		return array_values(array_map(fn($ctx) => $ctx->params['alias'] ?? $ctx->id, $owner_record_contexts));
	}
	
	static function getAutocompleteRecordTypes() {
		$record_contexts = Extension_DevblocksContext::getAll(false);
		return array_values(array_map(fn($ctx) => $ctx->params['alias'] ?? $ctx->id, $record_contexts));
	}
	
	static function getAutocompleteLanguages() {
		return array_keys(DAO_Translation::getDefinedLangCodes());
	}
	
	static function getAutocompleteTimezones() {
		return DevblocksPlatform::services()->date()->getTimezones();
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		return true;
	}
	
	function getDaoFieldsFromKeysAndValues($data, &$out_fields, &$out_custom_fields, &$error) {
		$out_fields = $out_custom_fields = [];
		$error = null;
		
		$context = $this->id;
		
		$map = $this->getKeyToDaoFieldMap();
		
		// Custom fields
		
		if(!$this->_getDaoCustomFieldsFromKeysAndValues($context, $data, $out_custom_fields, $error))
			return false;
		
		if(is_array($data))
		foreach($data as $key => $value) {
			$fields = [];
			
			if(!$this->getDaoFieldsFromKeyAndValue($key, $value, $fields, $data, $error))
				return false;
			
			if(!empty($fields)) {
				$out_fields = array_merge($out_fields, $fields);
				continue;
			}
			
			if(!isset($map[$key])) {
				$error = sprintf("'%s' is not an editable field.", $key);
				return false;
			}
			
			$out_fields[$map[$key]] = $value;
		}
		
		// Links
		
		if(!$this->_getDaoLinksForContext($context, $data, $out_fields, $error))
			return false;
		
		// Custom fieldsets
		
		if(!$this->_getDaoCustomFieldsetsForContext($context, $data, $out_fields, $error))
			return false;
		
		return true;
	}
	
	function getDefaultProperties() : array {
		return [];
	}
	
	/**
	 * @internal
	 * @deprecated
	 *
	 * @return array
	 */
	function getCardProperties() {
		// Load cascading properties
		$properties = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'card:' . $this->id, [], true);
		
		if(empty($properties))
			$properties = $this->getDefaultProperties();
		
		return $properties;
	}

	/**
	 * @return string|false
	 */
	function getDaoClass() {
		$class = str_replace('Context_','DAO_', get_called_class());
		
		if(!class_exists($class))
			return false;
		
		return $class;
	}
	
	function getModelClass() {
		$class = str_replace('Context_','Model_', get_called_class());
		
		if(!class_exists($class))
			return false;
		
		return $class;
	}
	
	/*
	 * @return DevblocksSearchFields
	 */
	function getSearchClass() {
		$class = str_replace('Context_','SearchFields_', get_called_class());
		
		if(!class_exists($class))
			return false;
		
		return $class;
	}

	function getViewClass() {
		$class = str_replace('Context_','View_', get_called_class());
		
		if(!class_exists($class))
			return false;
		
		return $class;
	}

	function getModelObject(int $id) {
		$objects = self::getModelObjects([$id]);
		
		if(array_key_exists($id, $objects))
			return $objects[$id];
		
		return null;
	}
	
	function getModelObjects(array $ids) {
		$ids = DevblocksPlatform::importVar($ids, 'array:integer');
		$models = [];

		if(null == ($dao_class = $this->getDaoClass()))
			return $models;

		if(method_exists($dao_class, 'getIds')) {
			$models = $dao_class::getIds($ids);

		} elseif(method_exists($dao_class, 'getWhere')) {
			$where = sprintf("id IN (%s)",
				implode(',', $ids)
			);

			// Get without sorting (optimization, no file sort)
			$models = $dao_class::getWhere($where, null);
		}

		return $models;
	}

	/**
	 * @internal
	 */
	public function formatDictionaryValue($key, DevblocksDictionaryDelegate $dict) {
		$translate = DevblocksPlatform::getTranslationService();

		@$type = $dict->_types[$key];
		$value = $dict->$key;

		switch($type) {
			case 'context_url':
				// Try to find the context+id pair for this key
				$parts = explode('_', str_replace('__','_',$key));

				// Start with the longest sub-token, and decrease until found
				while(array_pop($parts)) {
					$prefix = implode('_', $parts);
					$test_key = $prefix . '__context';

					@$context = $dict->$test_key;

					if(!empty($context)) {
						$id_key = $prefix . '_id';
						$context_id = $dict->$id_key;

						if(!empty($context_id)) {
							$context_url = sprintf("ctx://%s:%d/%s",
								$context,
								$context_id,
								$value
							);
							return $context_url;

						} else {
							return $value;

						}
					}
				}

				break;

			case 'percent':
				if(is_float($value)) {
					$value = sprintf("%0.2f%%",
						($value * 100)
					);

				} elseif(is_numeric($value)) {
					$value = sprintf("%d%%",
						$value
					);
				}
				break;

			case 'size_bytes':
				$value = DevblocksPlatform::strPrettyBytes($value);
				break;

			case 'time_secs':
				//$value = DevblocksPlatform::strPrettyTime($value, true);
				break;

			case 'time_mins':
				$secs = intval($value) * 60;
				$value = DevblocksPlatform::strSecsToString($secs, 2);
				break;

			case Model_CustomField::TYPE_CHECKBOX:
				$value = (!empty($value)) ? $translate->_('common.yes') : $translate->_('common.no');
				break;

			case Model_CustomField::TYPE_DATE:
				$value = DevblocksPlatform::strPrettyTime($value);
				break;
		}

		return $value;
	}

	/**
	 * @param string $view_id
	 * @return C4_AbstractView
	 */
	public function getTempView($view_id=null) {
		if(!($defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass())))
			return NULL;
		
		$defaults->id = $view_id ?: uniqid('tmp_');
		$defaults->is_ephemeral = true;
		$defaults->options = [];
		
		if(null != ($view = C4_AbstractViewLoader::unserializeAbstractView($defaults, false))) {
			$view->setAutoPersist(false);
			return $view;
		}
		
		return NULL;
	}
	
	/**
	 * @internal
	 * 
	 * @param string $view_id
	 * @return C4_AbstractView
	 */
	public function getSearchView($view_id=null) {
		if(empty($view_id)) {
			$view_id = sprintf("search_%s",
				str_replace('.','_',DevblocksPlatform::strToPermalink($this->id,'_'))
			);
		}
		
		$view_id = DevblocksPlatform::strTruncate(
			DevblocksPlatform::strAlphaNum($view_id, '_', '_'),
			255
		);
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			if(null == ($view = $this->getChooserView($view_id))) /* @var $view C4_AbstractViewModel */
				return;
		}
		
		$view->name = 'Search Results';
		$view->is_ephemeral = false;

		return $view;
	}

	/**
	 * 
	 * @param string $view_id
	 * @return C4_AbstractView
	 */
	abstract function getChooserView($view_id=null);
	abstract function getView($context=null, $context_id=null, $options=[], $view_id=null);

	function lazyLoadGetKeys() {
		$context_ext = Extension_DevblocksContext::get(static::ID, true);
		
		$lazy_keys = [];
		
		if($context_ext->hasOption('attachments')) {
			$lazy_keys['attachments'] = [
				'label' => '[Attachments](/docs/guide/developers/dictionaries/#key-expansion)',
				'type' => 'Attachments',
			];
		}
		
		if($context_ext->hasOption('comments')) {
			$lazy_keys['comments'] = [
				'label' => '[Comments](/docs/guide/developers/dictionaries/#key-expansion)',
				'type' => 'Comments',
			];
		}
		
		if($context_ext->hasOption('comments')) {
			$lazy_keys['comment_count'] = [
				'label' => '[Comment](/docs/records/types/comments/) count on the record',
				'type' => 'Number',
			];
		}
		
		if($context_ext->hasOption('custom_fields')) {
			$lazy_keys['custom_<id>'] = [
				'label' => '[Custom Fields](/docs/guide/developers/dictionaries/#key-expansion)',
				'type' => 'Mixed',
			];
		}
		
		if($context_ext->hasOption('links')) {
			$lazy_keys['links'] = [
				'label' => '[Links](/docs/guide/developers/dictionaries/#key-expansion)',
				'type' => 'Links',
			];
		}
		
		if($context_ext->hasOption('watchers')) {
			$lazy_keys['watchers'] = [
				'label' => '[Watchers](/docs/guide/developers/dictionaries/#key-expansion)',
				'type' => 'Watchers',
			];
		}
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) { return []; }

	/**
	 * @internal
	 */
	protected function _broadcastRecipientFieldsGet($context, $context_label, array $use=[]) {
		$token_labels = $token_values = [];
		CerberusContexts::getContext($context, $context_label, $token_labels, $token_values, null, true);
		
		$labels = $token_values['_labels'];
		
		$custom_fields = DAO_CustomField::getAll();
		
		// Include any known email addresses or workers
		$results = [
			'links.address' => $context_label . ' linked email addresses',
			'links.contacts' => $context_label . ' linked contacts',
			'links.orgs' => $context_label . ' linked organizations',
			'links.worker' => $context_label . ' watchers',
		];
		
		// Append specified keys
		foreach($use as $k)
			if(isset($labels[$k]))
				$results[$k] = $labels[$k];
		
		array_walk($labels, function($label, $key) use ($custom_fields, $labels, &$results) {
			$matches = [];
			if(preg_match('#^(.*?)\_*custom\_(\d+)$#', $key, $matches)) {
				$field_id = $matches[2];
				
				if(false == (@$field = $custom_fields[$field_id]))
					return;
				
				switch($field->type) {
					case Model_CustomField::TYPE_LINK:
						switch($field->params['context']) {
							case CerberusContexts::CONTEXT_ADDRESS:
							case CerberusContexts::CONTEXT_CONTACT:
							case CerberusContexts::CONTEXT_ORG:
							case CerberusContexts::CONTEXT_WORKER:
								if(array_key_exists($key . '__label', $labels))
									$results[$key] = $labels[$key . '__label'];
								break;
						}
						break;
						
					case Model_CustomField::TYPE_WORKER:
						$results[$key] = $label;
						break;
				}
			}
		});
		
		return $results;
	}
	
	/**
	 * @internal
	 */
	protected function _broadcastPlaceholdersGet($context, $with_broadcast_email=true) {
		$token_labels = $token_values = [];
		CerberusContexts::getContext($context, null, $token_labels, $token_values, null, true);
		
		if($with_broadcast_email) {
			$merge_token_labels = $merge_token_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, null, true);
			
			CerberusContexts::merge(
				'broadcast_email_',
				'Broadcast ',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		}
		
		return $token_values;
	}
	
	/**
	 * @internal
	 */
	protected function _broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict) {
		$emails = [];
		$custom_fields = DAO_CustomField::getAll();
		
		foreach($fields as $field) {
			switch($field) {
				case 'links.address':
					$links = DAO_ContextLink::getContextLinks($dict->_context, $dict->id, CerberusContexts::CONTEXT_ADDRESS);
					@$links = array_shift($links);
					
					if(is_array($links) && !empty($links)) {
						$addresses = DAO_Address::getIds(array_keys($links));
						$addresses = array_column(DevblocksPlatform::objectsToArrays($addresses), 'email', 'id');
						$emails = array_merge($emails, array_values($addresses));
					}
					break;
					
				case 'links.contacts':
					$links = DAO_ContextLink::getContextLinks($dict->_context, $dict->id, CerberusContexts::CONTEXT_CONTACT);
					@$links = array_shift($links);
					
					if(is_array($links) && !empty($links)) {
						$contacts = DAO_Contact::getIds(array_keys($links));
						$address_ids = array_column(DevblocksPlatform::objectsToArrays($contacts), 'primary_email_id', 'id');
						$addresses = DAO_Address::getIds($address_ids);
						$addresses = array_column(DevblocksPlatform::objectsToArrays($addresses), 'email', 'id');
						$emails = array_merge($emails, array_values($addresses));
					}
					break;
					
				case 'links.orgs':
					$links = DAO_ContextLink::getContextLinks($dict->_context, $dict->id, CerberusContexts::CONTEXT_ORG);
					@$links = array_shift($links);
					
					if(is_array($links) && !empty($links)) {
						$orgs = DAO_ContactOrg::getIds(array_keys($links));
						$address_ids = array_column(DevblocksPlatform::objectsToArrays($orgs), 'email_id', 'id');
						$addresses = DAO_Address::getIds($address_ids);
						$addresses = array_column(DevblocksPlatform::objectsToArrays($addresses), 'email', 'id');
						$emails = array_merge($emails, array_values($addresses));
					}
					break;
					
				case 'links.worker':
					$links = DAO_ContextLink::getContextLinks($dict->_context, $dict->id, CerberusContexts::CONTEXT_WORKER);
					@$links = array_shift($links);
					
					if(is_array($links) && !empty($links)) {
						$workers = DAO_Worker::getIds(array_keys($links));
						$address_ids = array_column(DevblocksPlatform::objectsToArrays($workers), 'email_id', 'id');
						$addresses = DAO_Address::getIds($address_ids);
						$addresses = array_column(DevblocksPlatform::objectsToArrays($addresses), 'email', 'id');
						$emails = array_merge($emails, array_values($addresses));
					}
					break;
					
				default:
					$matches = [];
					if(preg_match('#^(.*?)\_*custom\_(\d+)$#', $field, $matches)) {
						$field_id = $matches[2];
						
						if(false == ($custom_field = $custom_fields[$field_id]))
							break;
						
						switch($custom_field->type) {
							case Model_CustomField::TYPE_LINK:
								switch($custom_field->params['context']) {
									case CerberusContexts::CONTEXT_ADDRESS:
										if(false != ($email = $dict->get($field . '__label')))
											$emails[] = $email;
										break;
										
									case CerberusContexts::CONTEXT_CONTACT:
										$field_key = $field . '_email_address_id';
										$dict->$field_key;
										
										if(false != ($email = $dict->get($field . '_email_address')))
											$emails[] = $email;
										break;
										
									case CerberusContexts::CONTEXT_ORG:
										$field_key = $field . '_email_address_id';
										$dict->$field_key;
										
										if(false != ($email = $dict->get($field . '_email_address')))
											$emails[] = $email;
										break;
										
									case CerberusContexts::CONTEXT_WORKER:
										$field_key = $field . '_address_id';
										$dict->$field_key;
										
										if(false != ($email = $dict->get($field . '_address_address')))
											$emails[] = $email;
										break;
								}
								break;
								
							case Model_CustomField::TYPE_WORKER:
								$field_key = $field . '_address_id';
								$dict->$field_key;
						
								$email = $dict->get($field . '_address_address');
								if($email)
									$emails[] = $email;
								break;
						}
						
					} else {
						if(isset($dict->$field) && !empty($dict->$field)) {
							$emails[] = $dict->$field;
						}
					}
					break;
			}
		}
		
		return $emails;
	}
	
	/**
	 * @internal
	 */
	protected function _getDaoCustomFieldsetsForContext($context, array &$data, &$out_fields, &$error=null) {
		$error = null;
		
		if(!array_key_exists('fieldsets', $data))
			return true;
		
		$value = $data['fieldsets'] ?? null;
		
		if($this->hasOption('custom_fields')) {
			if(false == ($this->_getDaoFieldsets($value, $out_fields, $error)))
				return false;
		}
		
		return true;
	}
	
	/**
	 * @internal
	 */
	protected function _getDaoLinksForContext($context, array &$data, &$out_fields, &$error=null) {
		$error = null;
		
		if(!array_key_exists('links', $data))
			return true;
		
		$value = $data['links'] ?? null;
		
		if($this->hasOption('links')) {
			if(false == ($this->_getDaoFieldsLinks($value, $out_fields, $error)))
				return false;
		}
		
		return true;
	}
	
	/**
	 * @param string $context
	 * @param array $data
	 * @param array $out_custom_fields
	 * @param string|null $error
	 * @return bool
	 * @internal
	 */
	protected function _getDaoCustomFieldsFromKeysAndValues(string $context, array &$data, array &$out_custom_fields, &$error=null) {
		$error = null;
		$custom_fields = null;
		
		$record_custom_fields = DAO_CustomField::getByContext($context);
		$record_custom_field_uris = array_column($record_custom_fields, 'id', 'uri');
		
		// Convert friendly URIs to custom_123
		foreach($data as $key => $value) {
			if(array_key_exists($key, $record_custom_field_uris)) {
				$data['custom_' . $record_custom_field_uris[$key]] = $value;
				unset($data[$key]);
			}
		}
		
		foreach($data as $key => $value) {
			if(DevblocksPlatform::strStartsWith($key, 'custom_') 
				&& false !== ($custom_field_id = mb_substr($key,strrpos($key,'_')+1))
				&& is_numeric($custom_field_id)
				) {
				if(is_null($custom_fields))
					$custom_fields = DAO_CustomField::getByContext($context);
				
				if(!isset($custom_fields[$custom_field_id])) {
					$error = sprintf("'%s' is not a valid custom field", $key);
					return false;
				}
				
				$out_custom_fields[$custom_field_id] = $value;
				unset($data[$key]);
			}
		}
		
		return true;
	}
	
	/**
	 * @internal
	 */
	protected function _getDaoFieldsets($value, &$out_fields, &$error) {
		$fieldset_ids = [];
		
		if(!is_string($value) && !is_array($value)) {
			$error = 'must be an array or comma-separated list of fieldset IDs.';
			return false;
		}
		
		if(is_array($value)) {
			$fieldset_ids = $value;
		} else if(is_string($value)) {
			$fieldset_ids = DevblocksPlatform::parseCsvString($value);
		}
		
		if(false == ($json = json_encode($fieldset_ids))) {
			$error = 'could not be JSON encoded.';
			return false;
		}
		
		$out_fields['_fieldsets'] = $json;
		
		return true;
	}
	
	/**
	 * @internal
	 */
	protected function _getDaoFieldsLinks($value, &$out_fields, &$error) {
		if(!is_array($value)) {
			$error = 'must be an array of context:id pairs.';
			return false;
		}
		
		$links = [];
		
		if(is_array($value))
		foreach($value as &$tuple) {
			$is_remove = false;
			
			if(DevblocksPlatform::strStartsWith($tuple, ['-'])) {
				$is_remove = true;
				$tuple = ltrim($tuple,'-');
			}
			
			list($context, $id) = array_pad(explode(':', $tuple, 2), 2, null);
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, false))) {
				$error = sprintf("has a link with an invalid context (%s)", $tuple);
				return false;
			}
			
			$context = $context_ext->id;
			
			$tuple = sprintf("%s%s:%d",
				$is_remove ? '-' : '',
				$context,
				$id
			);
			
			$links[] = $tuple;
		}
		unset($tuple);
		
		
		if(false == ($json = json_encode($links))) {
			$error = 'could not be JSON encoded.';
			return false;
		}
		
		$out_fields['_links'] = $json;
		
		return true;
	}
	
	/**
	 * @internal
	 */
	protected function _importModelCustomFieldsAsValues($model, $token_values) {
		$custom_fields = $model->custom_fields ?? null;
		
		if($custom_fields) {
			$custom_values = $this->_lazyLoadCustomFields(
				'custom_',
				$token_values['_context'],
				$token_values['id'],
				false,
				$custom_fields,
				$token_values
			);
			
			// Also write URIs
			$custom_values_uris = $this->_lazyLoadCustomFields(
				'customfields',
				$token_values['_context'],
				$token_values['id'],
				true,
				$custom_fields,
				$token_values
			);
			
			$token_values = array_merge($token_values, $custom_values);
			$token_values = array_merge($token_values, $custom_values_uris);
		}

		return $token_values;
	}
	
	protected function _lazyLoadDefaults($token, array $dictionary=[]) {
		if(!$token || $token == '__expandable')
			return [];
		
		$context = $dictionary['_context'] ?? null;
		$context_id = $dictionary['id'] ?? null;
		
		if(!$context || !$context_id)
			return [];
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return [];
		
		$context = $context_ext->id;
		
		if('customfields' == $token && $context_ext->hasOption('custom_fields')) {
			return $this->_lazyLoadCustomFields($token, $context, $context_id, true, null, $dictionary);
			
		// @deprecated
		} else if(('custom' == $token || DevblocksPlatform::strStartsWith($token, 'custom_')) && $context_ext->hasOption('custom_fields')) {
			return $this->_lazyLoadCustomFields($token, $context, $context_id, false, null, $dictionary);
		
		} else if(($token === 'links' || DevblocksPlatform::strStartsWith($token, ['links.','links:','links~'])) && $context_ext->hasOption('links')) {
			return $this->_lazyLoadLinks($token, $context, $context_id);
			
		} else if($token === 'watchers' && $context_ext->hasOption('watchers')) {
			return [
				$token => CerberusContexts::getWatchers($context, $context_id, true),
			];
			
		} else if(($token === 'comments' || DevblocksPlatform::strStartsWith($token, ['comments:','comments~'])) && $context_ext->hasOption('comments')) {
			return $this->_lazyLoadComments($token, $context, $context_id);
			
		} else if($token === 'comment_count') {
			return $this->_lazyLoadCommentCount($token, $context, $context_id);
			
		} else if(($token === 'attachments' || DevblocksPlatform::strStartsWith($token, ['attachments:','attachments~'])) && $context_ext->hasOption('attachments')) {
			return $this->_lazyLoadAttachments($token, $context, $context_id);
		}
		
		if(!array_key_exists('customfields', $dictionary)) {
			// Is the key a custom field URI for this record type
			$custom_field_uris = array_column(DAO_CustomField::getByContext($context), 'id', 'uri');
			
			// If we directly matched a custom field URI, load custom fields
			if(array_key_exists($token, $custom_field_uris)) {
				return $this->_lazyLoadCustomFields($token, $context, $context_id, true, null, $dictionary);
				
			} else {
				$prefixes = array_keys($custom_field_uris);
				
				// Longest prefixes first
				usort($prefixes, fn($a, $b) => strlen($a) <=> strlen($b));
				
				$prefix = DevblocksPlatform::strStartsWith(
					$token,
					array_map(fn($k) => $k . '_', $prefixes)
				);
				
				// If we matched a custom field prefix, load custom fields
				if($prefix)
					return $this->_lazyLoadCustomFields($token, $context, $context_id, true, null, $dictionary);
			}
		}
		
		return [];
	}
	
	/**
	 * @param string $token
	 * @param string $context
	 * @param integer $context_id
	 * @return array
	 * @internal
	 */
	protected function _lazyLoadAttachments($token, $context, $context_id) {
		$token_values = [
			'attachments' => [],
		];
		
		@$original_token = $token;
		list($token, $record_expands) = array_pad(explode(':', $token), 2, null);
		list(, $limit) = array_pad(explode('~', $token), 2, null);
		
		$limit = DevblocksPlatform::intClamp($limit ?: 10, 1, 25);
		
		if($record_expands) {
			$record_expands = explode(',', $record_expands);
		} else {
			$record_expands = [];
		}
		
		if(false == ($models = DAO_Attachment::getByContextIds($context, [$context_id], true, $limit)))
			return $token_values;
		
		// Backwards compatibility
		// @deprecated
		if($original_token == 'attachments' && !$record_expands && in_array($context, [CerberusContexts::CONTEXT_COMMENT, CerberusContexts::CONTEXT_MESSAGE])) {
			foreach($models as $attachment_id => $attachment) {
				$object = [
					'id' => $attachment_id,
					'file_name' => $attachment->name,
					'file_size' => $attachment->storage_size,
					'file_type' => $attachment->mime_type,
				];
				$token_values['attachments'][$attachment_id] = $object;
			}
			
			return $token_values;
		}
		
		if(false == ($dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ATTACHMENT, $record_expands)))
			return $token_values;
		
		$token_values['attachments'] = array_values($dicts);
		
		return $token_values;
	}
	
	protected function _lazyLoadCommentCount($token, $context, $context_id) : array {
		return [
			$token => DAO_Comment::count($context, $context_id)
		];
	}
	
	/**
	 * @param string $token
	 * @param string $context
	 * @param integer $context_id
	 * @return array
	 * @internal
	 */
	protected function _lazyLoadComments($token, $context, $context_id) {
		$token_values = [
			'comments' => [],
		];
		
		list($token, $record_expands) = array_pad(explode(':', $token), 2, null);
		list(, $limit) = array_pad(explode('~', $token), 2, null);
		
		$limit = DevblocksPlatform::intClamp($limit ?: 10, 1, 25);
		
		if($record_expands) {
			$record_expands = explode(',', $record_expands);
		} else {
			$record_expands = [];
		}
		
		if(false == ($models = DAO_Comment::getByContext($context, $context_id, $limit)))
			return $token_values;
		
		if(false == ($dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_COMMENT, $record_expands)))
			return $token_values;
		
		$token_values['comments'] = $dicts;
		
		return $token_values;
	}
	
	/**
	 * @param string $token
	 * @param string $context
	 * @param int $context_id
	 * @return array
	 * @internal
	 */
	protected function _lazyLoadLinks($token, $context, $context_id) {
		$token_values = [
			'links' => [],
		];
		
		$original_token = $token;
		list($token, $record_expands) = array_pad(explode(':', $token), 2, null);
		list($token, $limit) = array_pad(explode('~', $token), 2, null);
		
		$limit = DevblocksPlatform::intClamp($limit ?: 10, 1, 25);
		
		if($record_expands) {
			$record_expands = explode(',', $record_expands);
		} else {
			$record_expands = [];
		}
		
		$dicts = [];
		
		// All links
		if(!($record_alias = DevblocksPlatform::services()->string()->strAfter($token, '.'))) {
			if(!($results = DAO_ContextLink::getAllContextLinks($context, $context_id, $limit)))
				return $token_values;
			
			// Backwards compatibility
			// @deprecated
			if($original_token == 'links' && !$record_expands) {
				foreach($results as $result) {
					if(!isset($token_values['links'][$result->context]))
						$token_values['links'][$result->context] = [];
					
					$token_values['links'][$result->context][] = intval($result->context_id);
				}
				return $token_values;
			}
			
			foreach($results as $result) {
				$dicts[] = DevblocksDictionaryDelegate::instance([
					'_context' => $result->context,
					'id' => $result->context_id,
				]);
			}
			
		} else { // Links of a specific type
			if(false == ($results = DAO_ContextLink::getContextLinks($context, $context_id, $record_alias, $limit)))
				return $token_values;
			
			if(array_key_exists($context_id, $results)) {
				foreach($results[$context_id] as $to_link) {
					$dicts[] = DevblocksDictionaryDelegate::instance([
						'_context' => $to_link->context,
						'id' => $to_link->context_id,
					]);
				}
			}
		}
		
		if($record_expands) {
			foreach ($record_expands as $record_expand)
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $record_expand, true);
		}
		
		$token_values['links'] = $dicts;
		
		return $token_values;
	}
	
	public function lazyLoadCustomFields($token, $context, $context_id, $as_keys=true, $field_values=null, array $dictionary=[]) : array {
		return $this->_lazyLoadCustomFields($token, $context, $context_id, $as_keys, $field_values, $dictionary);
	}

	// [TODO] This is setting the wrong type on all linked fields
	/**
	 * @internal
	 */
	protected function _lazyLoadCustomFields($token, $context, $context_id, $as_keys=true, $field_values=null, array $dictionary=[]) {
		$fields = DAO_CustomField::getByContext($context);
		$token_values = [];
		
		if($as_keys) {
			$token_values['customfields'] = [];
		} else {
			$token_values['custom'] = [];
		}
		
		// If (0 == $context_id), we need to null out all the fields and return w/o queries
		if(empty($context_id))
			return $token_values;
			
		// If we weren't passed values
		if(is_null($field_values)) {
			$results = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(is_array($results))
				$field_values = array_shift($results);
			unset($results);
		}
		
		foreach(array_keys($fields) as $cf_id) {
			if(!array_key_exists($cf_id, $fields))
				continue;
			
			if($as_keys) {
				$key_prefix = $fields[$cf_id]->uri;
				$token_values['customfields'][] = $key_prefix;
			} else {
				$key_prefix = 'custom_' . $cf_id;
			}
			
			if(!$as_keys) {
				$token_values['custom'][$cf_id] = '';
			}
			
			// If we already had a value for this key, keep it
			if(array_key_exists($key_prefix, $dictionary))
				$field_values[$cf_id] = $dictionary[$key_prefix];
			
			$token_values[$key_prefix] = '';
			
			if(array_key_exists($cf_id, $field_values)) {
				if($as_keys) {
					$token_values[$key_prefix] = $field_values[$cf_id];
					
				} else {
					$token_values['custom'][$cf_id] = $field_values[$cf_id];

					// Stringify
					if(is_array($field_values[$cf_id])) {
						$token_values['custom_'.$cf_id] = implode(', ', $field_values[$cf_id]);
					} elseif(is_string($field_values[$cf_id])) {
						$token_values['custom_'.$cf_id] = $field_values[$cf_id];
					}
				}
			}

			switch($fields[$cf_id]->type) {
				case Model_CustomField::TYPE_CURRENCY:
					$currency_id = intval($fields[$cf_id]->params['currency_id'] ?? null);
					$token_values[$key_prefix . '_currency__context'] = CerberusContexts::CONTEXT_CURRENCY;
					$token_values[$key_prefix . '_currency_id'] = $currency_id ?? null;
					
					if(($currency = DAO_Currency::get($currency_id))) {
						@$token_values[$key_prefix . '_label'] = $currency->format($field_values[$cf_id], true);
						@$token_values[$key_prefix . '_decimal'] = $currency->format($field_values[$cf_id], false);
					}
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					@$token_values[$key_prefix . '_label'] = DevblocksPlatform::strFormatDecimal($field_values[$cf_id], intval($fields[$cf_id]->params['decimal_at'] ?? null));
					@$token_values[$key_prefix . '_decimal_at'] = intval($fields[$cf_id]->params['decimal_at'] ?? null);
					break;
					
				case Model_CustomField::TYPE_LINK:
					$token_values[$key_prefix . '_id'] = $field_values[$cf_id] ?? null;
					$token_values[$key_prefix . '__context'] = $fields[$cf_id]->params['context'] ?? null;

					if(!isset($token_values[$token])) {
						$dict = new DevblocksDictionaryDelegate($token_values);
						$dict->get($token);
						$token_values = $dict->getDictionary();
					}
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$token_values[$key_prefix . '_id'] = $field_values[$cf_id] ?? null;
					$token_values[$key_prefix . '__context'] = CerberusContexts::CONTEXT_WORKER;

					if(!isset($token_values[$token])) {
						$dict = new DevblocksDictionaryDelegate($token_values);
						$dict->get($token);
						$token_values = $dict->getDictionary();
					}
					break;
					
				default:
					if(false != ($field_ext = $fields[$cf_id]->getTypeExtension())) {
						$field_ext->getDictionaryValues($fields[$cf_id], $field_values[$cf_id], $as_keys, $token_values);
					}
					break;
			}
		}
		
		return $token_values;
	}

	protected function _getTokenLabelsFromCustomFields($fields, $prefix) {
		$context_stack = CerberusContexts::getStack();

		$labels = [];
		$fieldsets = DAO_CustomFieldset::getAll();
		
		if(is_array($fields))
		foreach($fields as $field) { /* @var $field Model_CustomField */
			$fieldset = $field->custom_fieldset_id ? @$fieldsets[$field->custom_fieldset_id] : null;
			//$cf_key = $field->uri ?: ('custom_' . $field->id); 
			$cf_key = 'custom_' . $field->id; 

			$suffix = '';

			switch($field->type) {
				case Model_CustomField::TYPE_LINK:
					if(!isset($field->params['context']))
						break;

					$field_prefix = $prefix . ($fieldset ? ($fieldset->name . ' ') : '') . $field->name . ' ';
					
					// Control infinite recursion
					if(count($context_stack) > 2) {
						$labels[$cf_key] = $field_prefix;
						
					} else {
						$merge_labels = $merge_values = [];
						CerberusContexts::getContext($field->params['context'], null, $merge_labels, $merge_values, $field_prefix, true);
	
						// Unset redundant id
						unset($merge_labels['id']);
	
						$labels[$cf_key] = sprintf("%s%s",
							$field_prefix,
							'ID'
						);
						
						if(is_array($merge_labels))
						foreach($merge_labels as $label_key => $label) {
							$labels[$cf_key.'_'.$label_key] = $label;
						}
					}
					break;
				
				case Model_CustomField::TYPE_WORKER:
					$field_prefix = $prefix . ($fieldset ? ($fieldset->name . ' ') : '') . $field->name . ' ';
					
					if(count($context_stack) > 1) {
						$labels[$cf_key] = $field_prefix;
						
					} else {
						$merge_labels = $merge_values = [];
						CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_labels, $merge_values, $field_prefix, true);
						
						// Unset redundant id
						unset($merge_labels['id']);
						
						$labels[$cf_key] = sprintf("%s%s",
							$field_prefix,
							'ID'
						);
						
						if(is_array($merge_labels))
							foreach($merge_labels as $label_key => $label) {
								$labels[$cf_key.'_'.$label_key] = $label;
							}
					}
					break;
					
				default:
					$labels[$cf_key] = sprintf("%s%s%s%s",
						$prefix,
						($fieldset ? ($fieldset->name . ':') : ''),
						$field->name,
						$suffix
					);
					break;
			}
		}
		
		return $labels;
	}

	protected function _getTokenTypesFromCustomFields($fields, $prefix) {
		$context_stack = CerberusContexts::getStack();
		$types = [];
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) { /* @var Model_CustomField $field */
			$prefix_cf = 'custom_' . $cf_id;
			$prefix_uri = $field->uri;
			
			$types[$prefix_cf] = $field->type;
			$types[$prefix_uri] = $field->type;
			
			switch($field->type) {
				case Model_CustomField::TYPE_LINK:
					if(!isset($field->params['context']))
						break;
						
					// Control infinite recursion
					if(count($context_stack) <= 2) {
						$merge_labels = $merge_values = [];
						CerberusContexts::getContext($field->params['context'], null, $merge_labels, $merge_values, null, true, true);
						
						if(isset($merge_values['_types']) && is_array($merge_values['_types']))
						foreach($merge_values['_types'] as $type_key => $type) {
							$types[$prefix_cf.'_'.$type_key] = $type;
							$types[$prefix_uri.'_'.$type_key] = $type;
						}
						
						$types[$prefix_cf.'__label'] = 'context_url';
						$types[$prefix_uri.'__label'] = 'context_url';
					}
					break;
					
				case Model_CustomField::TYPE_WORKER:
					// Control infinite recursion
					if(count($context_stack) > 1) {
						DevblocksPlatform::noop();
						
					} else {
						$merge_labels = $merge_values = [];
						CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_labels, $merge_values, null, true, true);
						
						if(isset($merge_values['_types']) && is_array($merge_values['_types']))
						foreach($merge_values['_types'] as $type_key => $type) {
							$types[$prefix_cf.'_'.$type_key] = $type;
							$types[$prefix_uri.'_'.$type_key] = $type;
						}
						
						$types[$prefix_cf.'__label'] = 'context_url';
						$types[$prefix_uri.'__label'] = 'context_url';
					}
					break;
					
				default:
					break;
			}
		}
		
		return $types;
	}

	/**
	 * @internal
	 */
	protected function _getImportCustomFields($fields, &$keys) {
		if(is_array($fields))
		foreach($fields as $token => $cfield) {
			if(!DevblocksPlatform::strStartsWith($token, 'cf_'))
				continue;

			$cfield_id = intval(substr($token, 3));

			$keys['cf_' . $cfield_id] = array(
				'label' => $cfield->db_label,
				'type' => $cfield->type,
				'param' => $cfield->token,
			);
		}

		return true;
	}
	
	/**
	 * @internal
	 */
	static function getTimelineComments(string $context, int $context_id, bool $is_ascending=true) : array {
		$timeline = [];
		
		if(!$context_id)
			return [];
		
		if(($comments = DAO_Comment::getByContext($context, $context_id)))
			$timeline = array_merge($timeline, $comments);
		
		usort($timeline, function($a, $b) use ($is_ascending) {
			if($a instanceof Model_Comment) {
				$a_time = intval($a->created);
			} else {
				$a_time = 0;
			}
			
			if($b instanceof Model_Comment) {
				$b_time = intval($b->created);
			} else {
				$b_time = 0;
			}
			
			if($a_time > $b_time) {
				return ($is_ascending) ? 1 : -1;
			} else if ($a_time < $b_time) {
				return ($is_ascending) ? -1 : 1;
			} else {
				return 0;
			}
		});
		
		return $timeline;
	}
};

abstract class DevblocksHttpResponseListenerExtension extends DevblocksExtension {
	const POINT = 'devblocks.listener.http';
	
	function run(DevblocksHttpResponse $request, Smarty $tpl) {
	}
};

abstract class Extension_DevblocksCacheEngine extends DevblocksExtension {
	const POINT ='devblocks.cache.engine';
	
	protected $_config = [];

	/**
	 * @internal
	 */
	public static function getAll($as_instances=false) {
		$engines = DevblocksPlatform::getExtensions('devblocks.cache.engine', $as_instances);
		if($as_instances)
			DevblocksPlatform::sortObjects($engines, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($engines, 'name');
		return $engines;
	}

	/**
	 * @internal
	 * 
	 * @param string $id
	 * @return Extension_DevblocksCacheEngine
	 */
	public static function get($id) {
		static $extensions = null;

		if(isset($extensions[$id]))
			return $extensions[$id];

		if(!isset($extensions[$id])) {
			if(null == ($ext = DevblocksPlatform::getExtension($id, true)))
				return;

			if(!($ext instanceof Extension_DevblocksCacheEngine))
				return;

			$extensions[$id] = $ext;
			return $ext;
		}
	}

	/**
	 * @internal
	 */
	function getConfig() {
		return $this->_config;
	}

	abstract function setConfig(array $config);
	abstract function testConfig(array $config);
	abstract function renderConfig();
	abstract function renderStatus();

	abstract function isVolatile();
	abstract function save($data, $key, $tags=[], $lifetime=0);
	abstract function load($key);
	abstract function remove($key);
	abstract function clean();

	final protected function _packPayload(mixed $wrapper): string {
		$serialized = serialize($wrapper);
		$hmac = hash_hmac('sha256', $serialized, APP_DB_PASS ?? '');
		return $hmac . ':' . $serialized;
	}

	// Returns the unserialized wrapper on success, or false if the HMAC is missing/invalid.
	final protected function _unpackPayload(string $raw): mixed {
		$sep = strpos($raw, ':');
		if(false === $sep) return false;
		$hmac = substr($raw, 0, $sep);
		$body = substr($raw, $sep + 1);
		if(!hash_equals($hmac, hash_hmac('sha256', $body, APP_DB_PASS ?? '')))
			return false;
		return unserialize($body);
	}
};

abstract class Extension_DevblocksStorageEngine extends DevblocksExtension {
	const POINT = 'devblocks.storage.engine';
	
	protected $_options = [];
	
	/**
	 * @internal
	 */
	public static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions(self::POINT, false);

		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->params->[label]');
		else
			DevblocksPlatform::sortObjects($extensions, 'params->[label]');

		return $extensions;
	}

	abstract function renderConfig(Model_DevblocksStorageProfile $profile);
	abstract function saveConfig(Model_DevblocksStorageProfile $profile);
	abstract function testConfig(Model_DevblocksStorageProfile $profile);

	abstract function exists($namespace, $key);
	abstract function put($namespace, $id, $data);
	abstract function get($namespace, $key, &$fp=null);
	abstract function delete($namespace, $key);

	function batchDelete($namespace, $keys) { /* override */
		if(is_array($keys))
		foreach($keys as $key)
			$this->delete($namespace, $key);
	}

	const string MIGRATIONS_QUEUE = 'cerb.storage.migrations';

	/**
	 * Whether deletions for this engine should be deferred to the background queue rather than
	 * performed synchronously. Remote engines (S3, gatekeeper) defer; local engines do not.
	 */
	function deletesAreDeferred() : bool {
		return false;
	}

	/**
	 * Max keys per deferred-delete message / batchDelete request.
	 */
	function getDeleteBatchSize() : int {
		return 100;
	}

	/**
	 * The single entry point for "delete these keys for this profile". Deferred engines enqueue
	 * batched `delete` messages onto the storage queue; immediate engines delete now.
	 *
	 * @param string[] $keys
	 */
	function deleteKeys(string $namespace, array $keys) : void {
		if(!$keys)
			return;

		if(!$this->deletesAreDeferred()) {
			$this->batchDelete($namespace, $keys);
			return;
		}

		$queue = DevblocksPlatform::services()->queue();
		$profile_id = $this->_options['_profile_id'] ?? 0;

		foreach(array_chunk(array_values($keys), max(1, $this->getDeleteBatchSize())) as $chunk) {
			// cardinality = keys in the batch so queue stats count work units, not messages
			$queue->enqueue(self::MIGRATIONS_QUEUE, [[
				'action' => 'delete',
				'ns' => $namespace,
				'ext' => $this->manifest->id,
				'profile' => intval($profile_id),
				'keys' => $chunk,
			]], cardinality: count($chunk));
		}
	}

	/**
	 * @internal
	 */
	public function setOptions($options=[]) {
		if(is_array($options))
			$this->_options = $options;
	}

	/**
	 * @internal
	 */
	protected function escapeNamespace($namespace) {
		return DevblocksPlatform::strLower(DevblocksPlatform::strAlphaNum($namespace, '\_'));
	}
};

abstract class Extension_DevblocksStorageSchema extends DevblocksExtension {
	const POINT = 'devblocks.storage.schema';
	
	abstract function render();
	abstract function renderConfig();
	abstract function saveConfig();

	public static function getActiveStorageProfile() {}

	public static function get($object, &$fp=null) {}
	public static function put($id, $contents, $profile=null) {}
	public static function delete($ids) {}
	public static function archive($stop_time=null) {}
	public static function unarchive($stop_time=null) {}

	/**
	 * @internal
	 */
	protected function _stats($table_name) {
		$db = DevblocksPlatform::services()->database();

		$stats = [];

		$results = $db->GetArrayReader(sprintf("SELECT storage_extension, storage_profile_id, count(id) as hits, sum(storage_size) as bytes FROM %s GROUP BY storage_extension, storage_profile_id ORDER BY storage_extension",
			$db->escape($table_name)
		));
		foreach($results as $result) {
			$stats[$result['storage_extension'].':'.intval($result['storage_profile_id'])] = array(
				'storage_extension' => $result['storage_extension'],
				'storage_profile_id' => $result['storage_profile_id'],
				'count' => intval($result['hits']),
				'bytes' => intval($result['bytes']),
			);
		}

		return $stats;
	}
};

abstract class DevblocksControllerExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
	const POINT = 'devblocks.controller';
	
	public function handleRequest(DevblocksHttpRequest $request) {}
	public function writeResponse(DevblocksHttpResponse $response) {}
	
	public function redirectRequestToLogin(DevblocksHttpIO $request) {
		$query = [];
		if(!empty($request->path)) {
			if(is_array($request->path) && !empty($request->path))
				$query = ['url'=> implode('/',$request->path)];
		}
		DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
	}
};

abstract class DevblocksEventListenerExtension extends DevblocksExtension {
	const POINT = 'devblocks.listener.event';
	
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {}
};

interface DevblocksHttpRequestHandler {
	/**
	 * @param DevblocksHttpRequest $request
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request);
	public function writeResponse(DevblocksHttpResponse $response);
};

class DevblocksHttpRequest extends DevblocksHttpIO {
	public $method = null;
	public bool $is_ajax = false;
	public $csrf_token = null;
	
	/**
	 * @param array $path
	 */
	function __construct($path=[], $query=[], $method=null) {
		parent::__construct($path, $query);
		$this->method = $method;
	}
};

class DevblocksHttpResponse extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path=[], $query=[]) {
		parent::__construct($path, $query);
	}
};

abstract class DevblocksHttpIO {
	public $path = [];
	public $query = [];

	/**
	 *
	 * @param array $path
	 */
	function __construct($path,$query=[]) {
		$this->path = $path;
		$this->query = $query;
	}
};

class _DevblocksSortHelper {
	private static $_sortOn = '';

	static function sortByNestedMember($a, $b) {
		$props = explode('->', self::$_sortOn);

		$a_test = $a;
		$b_test = $b;

		foreach($props as $prop) {
			$is_index = false;

			if('[' == $prop[0]) {
				$is_index = true;
				$prop = trim($prop,'[]');
			}

			if($is_index) {
				if(!isset($a_test[$prop]) && !isset($b_test[$prop]))
					return 0;

				$a_test = $a_test[$prop] ?? null;
				$b_test = $b_test[$prop] ?? null;

			} else {
				if(!isset($a_test->$prop) && !isset($b_test->$prop)) {
					return 0;
				}

				$a_test = $a_test->$prop ?? null;
				$b_test = $b_test->$prop ?? null;
			}
		}

		if(is_numeric($a_test) && is_numeric($b_test)) {
			settype($a_test, 'float');
			settype($b_test, 'float');
			
			if($a_test==$b_test)
				return 0;

			return ($a_test > $b_test) ? 1 : -1;

		} else {
			$a_test = is_null($a_test) ? '' : $a_test;
			$b_test = is_null($b_test) ? '' : $b_test;

			if(!is_string($a_test) || !is_string($b_test))
				return 0;
			
			return strcasecmp($a_test, $b_test);
		}
	}

	static function sortObjects(&$array, $on, $ascending=true) {
		self::$_sortOn = $on;
		
		if(!is_array($array))
			return [];

		uasort($array, array('_DevblocksSortHelper', 'sortByNestedMember'));

		if(!$ascending)
			$array = array_reverse($array, true);
	}
};
