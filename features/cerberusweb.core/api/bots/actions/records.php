<?php
class BotAction_RecordCreate extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.create';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'context' => [
					'type' => 'context',
					'required' => true,
					'notes' => 'The [record type](/docs/records/types/) to create',
				],
				'changeset_json' => [
					'type' => 'json',
					'required' => true,
					'notes' => 'The field keys and values',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Create records in the simulator: `0`=no, `1`=yes',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the new record',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		if(!$params) {
			$params = [
				'changeset_json' => "{# See: https://cerb.ai/docs/records/types/ #}\n{% set json = {\n  key: 'value',\n} %}\n{{json|json_encode|json_pretty}}",
				'object_placeholder' => '_record',
			];
		}
		
		$tpl->assign('params', $params);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_create.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		$error = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return "Invalid changeset JSON.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		// Fail if there's no DAO::create() method
		if(!method_exists($dao_class, 'create'))
			return "This record type is not supported";
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return $error;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error))
			return $error;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return $error;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, null, $error))
			return $error;
		
		$out = sprintf(">>> Creating %s\r\n%s\n", $context_ext->manifest->name, $changeset_json);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = null;
		}
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		$error = null;
		
		// Fail if there's no DAO::create() method
		if(!method_exists($dao_class, 'create'))
			return false;
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return false;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error))
			return false;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return false;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, null, $error))
			return false;
		
		if(false == ($id = $dao_class::create($dao_fields)))
			return false;
		
		if($custom_fields)
			DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $id, $custom_fields);
		
		$dao_class::onUpdateByActor($actor, $dao_fields, $id);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$labels = $values = [];
			CerberusContexts::getContext($context_ext->id, $id, $labels, $values, null, true, true);
			$obj_dict = DevblocksDictionaryDelegate::instance($values);
			$obj_dict->custom_;
			$dict->$object_placeholder = $obj_dict;
		}
	}
};

class BotAction_RecordUpdate extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.update';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'context' => [
					'type' => 'context',
					'required' => true,
					'notes' => 'The [record type](/docs/records/types/) to update',
				],
				'id' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The ID of the record to update',
				],
				'changeset_json' => [
					'type' => 'json',
					'required' => true,
					'notes' => 'The field keys and values',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Update records in the simulator: `0`=no, `1`=yes',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the updated record',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		if(!$params) {
			$params = [
				'changeset_json' => "{# See: https://cerb.ai/docs/records/types/ #}\n{% set json = {\n  key: 'value',\n} %}\n{{json|json_encode|json_pretty}}",
				'object_placeholder' => '_record',
			];
		}
		
		$tpl->assign('params', $params);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_update.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return "Invalid changeset JSON.";
		
		if(!$id)
			return "ID is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		$error = null;
		
		// Fail if there's no DAO::update() method
		if(!method_exists($dao_class, 'update'))
			return "This record type is not supported";
		
		if(!CerberusContexts::isWriteableByActor($context->id, $id, $actor))
			return DevblocksPlatform::translate('error.core.no_acl.edit') . sprintf(" %s:%d", $context->id, $id);
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return $error;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error, $id))
			return $error;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return $error;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, $id, $error))
			return $error;
		
		$out = sprintf(">>> Updating %s (#%d)\r\n%s\n", $context_ext->manifest->name, $id, $changeset_json);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = null;
		}
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return false;
		
		if(!$id)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		$error = null;
		
		// Fail if there's no DAO::update() method
		if(!method_exists($dao_class, 'update'))
			return false;
		
		if(!CerberusContexts::isWriteableByActor($context->id, $id, $actor))
			return false;
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return false;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error, $id))
			return false;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return false;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, $id, $error))
			return false;
		
		$dao_class::update($id, $dao_fields);
		
		if($custom_fields)
			DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $id, $custom_fields);
		
		$dao_class::onUpdateByActor($actor, $dao_fields, $id);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$labels = $values = [];
			CerberusContexts::getContext($context_ext->id, $id, $labels, $values, null, true, true);
			$obj_dict = DevblocksDictionaryDelegate::instance($values);
			$obj_dict->custom_;
			$dict->$object_placeholder = $obj_dict;
		}
	}
};

class BotAction_RecordUpsert extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.upsert';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'context' => [
					'type' => 'context',
					'required' => true,
					'notes' => 'The [record type](/docs/records/types/) to upsert',
				],
				'query' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The [search query](/docs/search/) to determine if the record exists (update) or not (insert)',
				],
				'changeset_json' => [
					'type' => 'json',
					'required' => true,
					'notes' => 'The field keys and values',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Upsert records in the simulator: `0`=no, `1`=yes',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the record',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		if(!$params) {
			$params = [
				'changeset_json' => "{# See: https://cerb.ai/docs/records/types/ #}\n{% set json = {\n  key: 'value',\n} %}\n{{json|json_encode|json_pretty}}",
				'object_placeholder' => '_record',
			];
		}
		
		$tpl->assign('params', $params);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_upsert.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		
		if(!$query)
			return "Query is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		if(false == ($view = $context_ext->getChooserView()))
			return sprintf("Can't create a worklist of type: %s", $context_ext->name);
		
		$view->addParamsWithQuickSearch($query, true);
		$view->renderTotal = true;
		
		list($results, $total) = $view->getData();
		
		if(0 == $total) {
			$action = new BotAction_RecordCreate();
			$action_params = [
				'context' => $context_ext->id,
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator']
			];
			return $action->simulate($token, $trigger, $action_params, $dict);
			
		} elseif (1 == $total) {
			$action = new BotAction_RecordUpdate();
			$action_params = [
				'context' => $context_ext->id,
				'id' => key($results),
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator'],
			];
			return $action->simulate($token, $trigger, $action_params, $dict);
			
		} else {
			return "The upsert query must match exactly 0 or 1 records.";
		}
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		
		if(!$query)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		if(false == ($view = $context_ext->getChooserView()))
			return false;
		
		$view->addParamsWithQuickSearch($query, true);
		$view->renderTotal = true;
		
		list($results, $total) = $view->getData();
		
		if(0 == $total) {
			$action = new BotAction_RecordCreate();
			$action_params = [
				'context' => $context_ext->id,
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator']
			];
			return $action->run($token, $trigger, $action_params, $dict);
			
		} elseif (1 == $total) {
			$action = new BotAction_RecordUpdate();
			$action_params = [
				'context' => $context_ext->id,
				'id' => key($results),
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator'],
			];
			return $action->run($token, $trigger, $action_params, $dict);
			
		} else {
			return false;
		}
	}
};

class BotAction_RecordDelete extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.delete';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'context' => [
					'type' => 'context',
					'required' => true,
					'notes' => 'The [record type](/docs/records/types/) to delete',
				],
				'id' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The ID of the record to delete',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Delete records in the simulator: `0`=no, `1`=yes',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_delete.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		
		if(!$id)
			return "ID is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($dao_class::get($id)))
			return sprintf("%s #%d was not found.", $context_ext->manifest->name, $id);
		
		// Fail if there's no DAO::delete() method
		if(!method_exists($dao_class, 'delete'))
			return "This record type is not supported";
		
		if(!CerberusContexts::isDeleteableByActor($context->id, $id, $actor))
			return DevblocksPlatform::translate('error.core.no_acl.delete') . sprintf(" (%s:%d)", $context->id, $id);
		
		$out = sprintf(">>> Deleting %s (#%d)\n", $context_ext->manifest->name, $id);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		
		if(!$id)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($dao_class::get($id)))
			return false;
		
		// Fail if there's no DAO::delete() method
		if(!method_exists($dao_class, 'delete'))
			return false;
		
		if(!CerberusContexts::isDeleteableByActor($context->id, $id, $actor))
			return false;
		
		$dao_class::delete($id);
	}
};

class BotAction_RecordRetrieve extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.retrieve';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'context' => [
					'type' => 'context',
					'required' => true,
					'notes' => 'The [record type](/docs/records/types/) to retrieve',
				],
				'id' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The ID of the record to retrieve',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Create records in the simulator: `0`=no, `1`=yes',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the retrieved record',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_retrieve.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!$id)
			return "ID is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($dao_class::get($id)))
			return sprintf("%s #%d was not found.", $context_ext->manifest->name, $id);
		
		// Fail if there's no DAO::get() method
		if(!method_exists($dao_class, 'get'))
			return "This record type is not supported";
		
		if(!CerberusContexts::isReadableByActor($context->id, $id, $actor))
			return DevblocksPlatform::translate('error.core.no_acl.view') . sprintf(" (%s:%d)", $context->id, $id);
		
		$out = sprintf(">>> Retrieving %s (#%d)\n", $context_ext->manifest->name, $id);
		
		// Always run in simulator mode
		$this->run($token, $trigger, $params, $dict);
		
		$out .= $dict->$object_placeholder;
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = null;
		}
		
		if(!$id)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		// Fail if there's no DAO::get() method
		if(!method_exists($dao_class, 'get'))
			return false;
		
		if(false == ($model = $dao_class::get($id)))
			return false;
		
		if(!CerberusContexts::isReadableByActor($context->id, $id, $actor))
			return false;
		
		if(!empty($object_placeholder)) {
			$labels = $values = [];
			CerberusContexts::getContext($context_ext->id, $model, $labels, $values, null, true, true);
			$obj_dict = DevblocksDictionaryDelegate::instance($values);
			$obj_dict->custom_;
			$dict->$object_placeholder = $obj_dict;
		}
	}
};

class BotAction_RecordSearch extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.search';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'context' => [
					'type' => 'context',
					'required' => true,
					'notes' => 'The [record type](/docs/records/types/) to search',
				],
				'query' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The [search query](/docs/search/) to perform',
				],
				'expand' => [
					'type' => 'string',
					'notes' => 'A comma-separated list of keys to expand (e.g. `custom_, owner`)',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the search results',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_search.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();

		// Fail if there's no DAO::getIds() method
		if(!method_exists($dao_class, 'getIds'))
			return "This record type is not supported";
		
		// Load a view
		if(false == ($view = $context_ext->getChooserView()))
			return "Failed to load a worklist of this record type.";
		
		// Set query filter
		$view->addParamsWithQuickSearch($query, true);
		$view->view_columns = [];
		
		$out = sprintf(">>> Searching %s\nQuery: %s\n", $context_ext->manifest->name, $query);
		
		list($results, $total) = $view->getData();
		
		$ids = array_keys($results);
		
		if(empty($ids))
			return "No results.";
		
		if(false == ($dao_class::getIds($ids)))
			return sprintf("Unable to load %s records.", $context_ext->manifest->name);
		
		// Always run in simulator mode
		$this->run($token, $trigger, $params, $dict);
		
		if($object_placeholder) {
			if(is_array($dict->$object_placeholder)) {
				$first = current($dict->$object_placeholder);
			} else {
				$first = $dict->$object_placeholder;
			}
			
			$out .= sprintf("\n%s:\n%s", $object_placeholder,  $first);
			
			if($total > 1)
				$out .= sprintf("\n\n... and %d more", ($total-1));
			
			$page_placeholder = $object_placeholder . '__page';
			$total_placeholder = $object_placeholder . '__total';
			
			$out .= sprintf("\n\n%s: %d\n%s: %d",
				$page_placeholder,
				$dict->$page_placeholder,
				$total_placeholder,
				$dict->$total_placeholder
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		@$expands = DevblocksPlatform::parseCrlfString($tpl_builder->build(DevblocksPlatform::importVar($params['expand'],'string',''), $dict));
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = [];
		}
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		// Fail if there's no DAO::getIds() method
		if(!method_exists($dao_class, 'getIds'))
			return false;
		
		// Load a view
		if(false == ($view = $context_ext->getChooserView()))
			return false;
		
		// Set query filter
		$view->addParamsWithQuickSearch($query, true);
		$view->view_columns = [];
		
		list($results, $total) = $view->getData();
		
		$ids = array_keys($results);
		
		if(empty($ids) || false == ($models = $dao_class::getIds($ids)))
			return false;
		
		if($object_placeholder) {
			$total_placeholder = $object_placeholder . '__total';
			$dict->$total_placeholder = $total;
			
			// [TODO] Document this!
			$page_placeholder = $object_placeholder . '__page';
			$dict->$page_placeholder = $view->renderPage + 1;
			
			// Load dictionaries
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
			
			// Expand tokens
			if(is_array($expands))
			foreach($expands as $expand)
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $expand);
			
			// If we only requested one object, don't make it an array
			if(1 == $view->renderLimit)
				$dicts = array_shift($dicts);
			
			// Set the preferred placeholder
			$dict->$object_placeholder = $dicts;
		}
	}
};