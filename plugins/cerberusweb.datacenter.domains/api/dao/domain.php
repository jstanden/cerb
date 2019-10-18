<?php
class Context_Domain extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport, IDevblocksContextBroadcast, IDevblocksContextAutocomplete {
	const ID = CerberusContexts::CONTEXT_DOMAIN;
	
	static function isReadableByActor($models, $actor) {
		// Everyone can view
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function getRandom() {
		return DAO_Domain::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=domain&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();

		$properties = [];
		
		if(is_null($model))
			$model = new Model_Domain();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_DOMAIN,
			],
		);
		
		$properties['server'] = array(
			'label' => mb_ucfirst($translate->_('cerberusweb.datacenter.common.server')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_SERVER),
			'value' => $model->server_id,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated,
		);
		
		return $properties;
	}
	
	function autocomplete($term, $query=null) {
		$results = DAO_Domain::autocomplete($term);
		$list = [];
		
		if(stristr('none', $term) || stristr('empty', $term) || stristr('null', $term)) {
			$empty = new stdClass();
			$empty->label = '(no domain)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the domain');
			$list[] = $empty;
		}
		
		if(is_array($results))
		foreach($results as $domain_id => $domain){
			$entry = new stdClass();
			$entry->label = $domain->name;
			$entry->value = sprintf("%d", $domain_id);
			
			$meta = [];

			$entry->meta = $meta;
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getMeta($context_id) {
		$domain = DAO_Domain::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($domain->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $domain->id,
			'name' => $domain->name,
			'permalink' => $url,
			'updated' => $domain->updated,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'server__label',
			'created',
			'updated',
		);
	}
	
	function getContextIdFromAlias($alias) {
		if(false != ($domain = DAO_Domain::getByName($alias)))
			return $domain->id;
		
		return null;
	}
	
	function getContext($id_map, &$token_labels, &$token_values, $prefix=null) {
		$domain = null;

		// Polymorph
		if(is_numeric($id_map)) {
			$domain = DAO_Domain::get($id_map);
		} elseif(is_array($id_map) && isset($id_map['name'])) {
			$domain = Cerb_ORMHelper::recastArrayToModel($id_map, 'Model_Domain');
		} elseif(is_array($id_map) && isset($id_map['id'])) {
			$domain = DAO_Domain::get($id_map['id']);
		} elseif($id_map instanceof Model_Domain) {
			$domain = $id_map;
		} else {
			$domain = null;
		}
		
		if(is_null($prefix))
			$prefix = 'Domain:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_DOMAIN);
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'created' => $prefix.$translate->_('common.created'),
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'updated' => $prefix.$translate->_('common.updated'),
			'contacts_list' => $prefix.'Contacts List',
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'created' => Model_CustomField::TYPE_DATE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'updated' => Model_CustomField::TYPE_DATE,
			'contacts_list' => null,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_DOMAIN;
		$token_values['_types'] = $token_types;
		
		// Domain token values
		if(null != $domain) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $domain->name;
			$token_values['id'] = $domain->id;
			$token_values['created'] = $domain->created;
			$token_values['name'] = $domain->name;
			$token_values['updated'] = $domain->updated;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($domain, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=domain&id=%s-%d",DevblocksPlatform::strToPermalink($domain->name),$domain->id), true);
			
			// Server
			$server_id = (null != $domain && !empty($domain->server_id)) ? $domain->server_id : null;
			$token_values['server_id'] = $server_id;
		}

		// Server
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_SERVER, null, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'server_',
			$prefix,
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created' => DAO_Domain::CREATED,
			'id' => DAO_Domain::ID,
			'links' => '_links',
			'name' => DAO_Domain::NAME,
			'server_id' => DAO_Domain::SERVER_ID,
			'updated' => DAO_Domain::UPDATED,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['server_id']['notes'] = "The ID of the [server](/docs/records/types/server/) linked to this domain";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['contacts'] = [
			'label' => 'Contacts',
			'type' => 'Records',
		];
		
		$lazy_keys['contacts_list'] = [
			'label' => 'Contacts List',
			'type' => 'Text',
		];
		
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_DOMAIN;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'contacts':
				$contacts = [];
				$address_links = DAO_ContextLink::getContextLinks($context, $context_id, CerberusContexts::CONTEXT_ADDRESS);

				if(!is_array($address_links))
					break;
				
				// The results are keyed by source ID
				$address_links = array_shift($address_links);
				
				if(is_array($address_links))
				foreach($address_links as $address_link) { /* @var $address_link Model_ContextLink */
					$token_labels = [];
					$token_values = [];
					CerberusContexts::getContext($address_link->context, $address_link->context_id, $token_labels, $token_values, null, true);
					
					if(!empty($token_values))
						$contacts[$address_link->context_id] = $token_values;
				}
				
				$values[$token] = $contacts;
				break;
				
			case 'contacts_list':
				$result = $this->lazyLoadContextValues('contacts', $dictionary);
				$contacts = [];
				
				if(isset($result['contacts']))
				foreach($result['contacts'] as $contact) {
					$contacts[] = $contact['address'];
				}
				
				$values[$token] = implode(', ', $contacts);
				break;
				
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		$view->renderSortBy = SearchFields_Domain::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = str_replace('.','_', $this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Domain::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$id = $context_id; // [TODO] Cleanup
		
		$tpl = DevblocksPlatform::services()->template();
		
		$context = CerberusContexts::CONTEXT_DOMAIN;
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('view_id', $view_id);

		// Model
		$model = null;
		if(empty($id) || null == ($model = DAO_Domain::get($id)))
			$model = new Model_Domain();
		
		
		if(empty($context_id) || $edit) {
			$tpl->assign('model', $model);
			
			// Servers
			$servers = DAO_Server::getAll();
			$tpl->assign('servers', $servers);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_DOMAIN, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_DOMAIN, $id);
			if(isset($custom_field_values[$id]))
				$tpl->assign('custom_field_values', $custom_field_values[$id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Context: Addresses
			$results = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_DOMAIN, $id, CerberusContexts::CONTEXT_ADDRESS);
			if(isset($results[$id])) {
				$contact_addresses = DAO_Address::getIds(array_keys($results[$id]));
				$tpl->assign('contact_addresses', $contact_addresses);
			}
			
			$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/peek_edit.tpl');
			
		} else {
			// Links
			$links = array(
				CerberusContexts::CONTEXT_DOMAIN => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							CerberusContexts::CONTEXT_DOMAIN,
							$context_id,
							[]
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments(CerberusContexts::CONTEXT_DOMAIN, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}
			
			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_DOMAIN)))
				return;
			
			// Dictionary
			$labels = [];
			$values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_DOMAIN, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			// Interactions
			
			$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
			$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
			$tpl->assign('interactions_menu', $interactions_menu);

			// Properties
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Card search buttons
			$search_buttons = $context_ext->getCardSearchButtons($dict, []);
			$tpl->assign('search_buttons', $search_buttons);
			
			$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/peek.tpl');
		}
	}
	
	function broadcastRecipientFieldsGet() {
		$results = $this->_broadcastRecipientFieldsGet(CerberusContexts::CONTEXT_DOMAIN, 'Domain');
		asort($results);
		return $results;
	}
	
	function broadcastPlaceholdersGet() {
		$token_values = $this->_broadcastPlaceholdersGet(CerberusContexts::CONTEXT_DOMAIN);
		return $token_values;
	}
	
	function broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict) {
		$emails = $this->_broadcastRecipientFieldsToEmails($fields, $dict);
		return $emails;
	}
	
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'created' => array(
				'label' => 'Created Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Domain::CREATED,
			),
			'name' => array(
				'label' => 'Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Domain::NAME,
				'required' => true,
				'force_match' => true,
			),
			'updated' => array(
				'label' => 'Updated Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Domain::UPDATED,
			),
		);
	
		$fields = SearchFields_Domain::getFields();
		self::_getImportCustomFields($fields, $keys);
		
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have a name
			if(!isset($fields[DAO_Domain::NAME])) {
				$fields[DAO_Domain::NAME] = 'New ' . $this->manifest->name;
			}
				
			// Default the created date to now
			if(!isset($fields[DAO_Domain::CREATED]))
				$fields[DAO_Domain::CREATED] = time();
			
			if(!isset($fields[DAO_Domain::UPDATED]))
				$fields[DAO_Domain::UPDATED] = time();
				
			// Create
			$meta['object_id'] = DAO_Domain::create($fields);
				
		} else {
			// Update
			DAO_Domain::update($meta['object_id'], $fields);
		}
	
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
};

class DAO_Domain extends Cerb_ORMHelper {
	const CREATED = 'created';
	const ID = 'id';
	const NAME = 'name';
	const SERVER_ID = 'server_id';
	const UPDATED = 'updated';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setUnique(get_class())
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::SERVER_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_SERVER, true))
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
			
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[DAO_Domain::CREATED]))
			$fields[DAO_Domain::CREATED] = time();
		
		$sql = "INSERT INTO datacenter_domain () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED]))
			$fields[self::UPDATED] = time();
		
		$context = CerberusContexts::CONTEXT_DOMAIN;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_DOMAIN, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'datacenter_domain', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.domain.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_DOMAIN, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('datacenter_domain', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_DOMAIN;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		$custom_fields = [];
		$deleted = false;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'delete':
					$deleted = true;
					break;
					
				case 'server_id':
					$change_fields[DAO_Domain::SERVER_ID] = $v;
					break;
					
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		if(!$deleted) {
			if(!empty($change_fields))
				DAO_Domain::update($ids, $change_fields);

			// Custom Fields
			if(!empty($custom_fields))
				C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_DOMAIN, $custom_fields, $ids);
			
			// Scheduled behavior
			if(isset($do['behavior']))
				C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_DOMAIN, $do['behavior'], $ids);
			
			// Watchers
			if(isset($do['watchers']))
				C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_DOMAIN, $do['watchers'], $ids);
			
			// Broadcast
			if(isset($do['broadcast']))
				C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_DOMAIN, $do['broadcast'], $ids);
			
		} else {
			DAO_Domain::delete($ids);
		}
		
		$update->markCompleted();
		return true;
	}
	
	static function countByServerId($server_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM datacenter_domain WHERE server_id = %d",
			$server_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Domain[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, server_id, created, updated ".
			"FROM datacenter_domain ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Domain	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getByName($name) {
		if(empty($name))
			return null;
		
		$results = self::getWhere(
			sprintf("%s = %s", self::escape(DAO_Domain::NAME), self::qstr($name)),
			null,
			null,
			1
		);
		
		if(is_array($results) && !empty($results))
			return array_shift($results);
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Domain[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Domain();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->server_id = intval($row['server_id']);
			$object->created = intval($row['created']);
			$object->updated = intval($row['updated']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM datacenter_domain WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_DOMAIN,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function maint() {
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_DOMAIN,
					'context_table' => 'datacenter_domain',
					'context_key' => 'id',
				)
			)
		);
	}
	
	public static function random() {
		return self::_getRandom('datacenter_domain');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Domain::getFields();
		
		list(, $wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Domain', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"datacenter_domain.id as %s, ".
			"datacenter_domain.name as %s, ".
			"datacenter_domain.server_id as %s, ".
			"datacenter_domain.created as %s, ".
			"datacenter_domain.updated as %s ",
				SearchFields_Domain::ID,
				SearchFields_Domain::NAME,
				SearchFields_Domain::SERVER_ID,
				SearchFields_Domain::CREATED,
				SearchFields_Domain::UPDATED
			);
			
		$join_sql = "FROM datacenter_domain ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Domain');

		$result = array(
			'primary_table' => 'datacenter_domain',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$ids = [];
		
		$results = $db->GetArraySlave(sprintf("SELECT id ".
			"FROM datacenter_domain ".
			"WHERE name LIKE %s ".
			"LIMIT 25",
			$db->qstr($term.'%')
		));
		
		if(is_array($results))
		foreach($results as $row) {
			$ids[] = $row['id'];
		}
		
		switch($as) {
			case 'ids':
				return $ids;
				break;
				
			default:
				return DAO_Domain::getIds($ids);
				break;
		}
	}
	
	/**
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
		
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Domain::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(datacenter_domain.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_Domain extends DevblocksSearchFields {
	const ID = 'w_id';
	const NAME = 'w_name';
	const SERVER_ID = 'w_server_id';
	const CREATED = 'w_created';
	const UPDATED = 'w_updated';
	
	// Virtuals
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_SERVER_SEARCH = '*_server_search';
	const VIRTUAL_WATCHERS = '*_workers';

	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'datacenter_domain.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_DOMAIN => new DevblocksSearchFieldContextKeys('datacenter_domain.id', self::ID),
			CerberusContexts::CONTEXT_SERVER => new DevblocksSearchFieldContextKeys('datacenter_domain.server_id', self::SERVER_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case SearchFields_Domain::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_DOMAIN, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_DOMAIN, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_DOMAIN)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_SERVER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_SERVER, 'datacenter_domain.server_id');
				break;
				
			case SearchFields_Domain::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_DOMAIN, self::getPrimaryKey());
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
		
		return false;
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'server':
				$key = 'server.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Domain::ID:
				$models = DAO_Domain::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				if(in_array(0,$values))
					$label_map[0] = DevblocksPlatform::translate('common.none');
				return $label_map;
				break;
				
			case SearchFields_Domain::SERVER_ID:
				$models = DAO_Server::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				if(in_array(0,$values))
					$label_map[0] = DevblocksPlatform::translate('common.none');
				return $label_map;
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'datacenter_domain', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'datacenter_domain', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::SERVER_ID => new DevblocksSearchField(self::SERVER_ID, 'datacenter_domain', 'server_id', $translate->_('dao.datacenter_domain.server_id'), null, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'datacenter_domain', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'datacenter_domain', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_SERVER_SEARCH => new DevblocksSearchField(self::VIRTUAL_SERVER_SEARCH, '*', 'server_search', null, null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			
			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Domain {
	public $id;
	public $name;
	public $server_id;
	public $created;
	public $updated;
	
	private $_server = null;
	
	function getServer() {
		if($this->_server)
			return $this->_server;
		
		$this->_server = DAO_Server::get($this->server_id);
		return $this->_server;
	}
};

class View_Domain extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'datacenter_domain';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('cerberusweb.datacenter.domains');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Domain::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Domain::SERVER_ID,
			SearchFields_Domain::UPDATED,
		);
		
		// Filter columns
		$this->addColumnsHidden(array(
			SearchFields_Domain::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Domain::VIRTUAL_CONTEXT_LINK,
			SearchFields_Domain::VIRTUAL_HAS_FIELDSET,
			SearchFields_Domain::VIRTUAL_SERVER_SEARCH,
			SearchFields_Domain::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Domain::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Domain');
		
		return $objects;
	}

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Domain', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Domain', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Booleans
				case SearchFields_Domain::SERVER_ID:
					$pass = true;
					break;
					
				// Booleans
				case SearchFields_Domain::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Domain::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Domain::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_DOMAIN;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Domain::SERVER_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Domain::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options[]');
				break;

			case SearchFields_Domain::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Domain::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Domain::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
				
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Domain::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Domain::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Domain::FULLTEXT_COMMENT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Domain::CREATED),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Domain::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_DOMAIN],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Domain::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_DOMAIN, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Domain::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'server' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Domain::VIRTUAL_SERVER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_SERVER, 'q' => ''],
					]
				),
			'server.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Domain::SERVER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_SERVER, 'q' => ''],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Domain::UPDATED),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Domain::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Domain::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_DOMAIN, $fields, null);
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_SERVER, $fields, 'server');
		
		// Engine/schema examples: Comments
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['comments']['examples'] = $ft_examples;
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
			case 'server':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Domain::VIRTUAL_SERVER_SEARCH);
				break;
				
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Domain::VIRTUAL_WATCHERS, $tokens);
				break;
				
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_DOMAIN);
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.datacenter.domains::domain/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Domain::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Domain::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Domain::VIRTUAL_SERVER_SEARCH:
				echo sprintf("Server matches <b>%s</b>", DevblocksPlatform::strEscapeHtml($param->value));
				break;
				
			case SearchFields_Domain::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Domain::SERVER_ID:
				$label_map = SearchFields_Domain::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Domain::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Domain::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			case SearchFields_Domain::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Domain::CREATED:
			case SearchFields_Domain::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Domain::SERVER_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
				
			case SearchFields_Domain::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Domain::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Domain::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Domain::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};