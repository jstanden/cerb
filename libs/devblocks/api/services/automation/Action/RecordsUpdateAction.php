<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_CustomField;
use DAO_CustomFieldValue;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;
use Model_Automation;

class RecordsUpdateAction extends AbstractAction {
	const ID = 'records.update';

	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();

		$params = $automation->getParams($this->node, $dict);
		$policy = $automation->getPolicy();

		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;

		$was_events_enabled = DevblocksPlatform::services()->event()->isEnabled();

		try {
			// Validate params

			$validation->addField('inputs', 'inputs:')
				->array()
			;

			$validation->addField('output', 'output:')
				->string()
			;

			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);

			// Validate input

			$validation->reset();

			$validation->addField('disable_events', 'inputs:disable_events:')
				->boolean()
			;

			$validation->addField('fields', 'inputs:fields:')
				->array()
				->setRequired(true)
				->setNotEmpty(false)
			;

			$validation->addField('record_ids', 'inputs:record_ids:')
				->idArray()
				->setRequired(true)
			;

			$validation->addField('record_type', 'inputs:record_type:')
				->context()
				->setRequired(true)
			;

			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);

			// Policy

			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);

			// Bulk mutation must be granted deliberately; no fallback to `record.update`
			if(!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not allow this command (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}

			$record_type = $inputs['record_type'];
			$record_ids = array_map('intval', $inputs['record_ids'] ?? []);
			$fields = $inputs['fields'] ?? [];
			$disable_events = boolval($inputs['disable_events'] ?? null);

			if($disable_events)
				DevblocksPlatform::services()->event()->disable();

			if(!($context_ext = Extension_DevblocksContext::getByAlias($record_type, true))) {
				throw new Exception_DevblocksAutomationError(sprintf(
					"Unknown record type `%s`",
					$record_type
				));
			}

			// Make sure we can update records of this type
			if(!$context_ext->manifest->hasOption('records'))
				throw new Exception_DevblocksAutomationError("Not implemented.");

			$dao_class = $context_ext->getDaoClass();
			$dao_fields = $custom_fields = [];

			if(!method_exists($dao_class, 'update'))
				throw new Exception_DevblocksAutomationError("Not implemented.");

			if(!method_exists($context_ext, 'getDaoFieldsFromKeysAndValues'))
				throw new Exception_DevblocksAutomationError("Not implemented.");

			// We don't pre-fetch the models to verify existence; non-existent IDs are
			// harmless no-ops in a batched UPDATE, and a pre-fetch would defeat the
			// one-query goal of this bulk command.

			if($record_ids && is_array($fields) && !empty($fields)) {
				if(!$context_ext->getDaoFieldsFromKeysAndValues($fields, $dao_fields, $custom_fields, $error))
					throw new Exception_DevblocksAutomationError($error);

				// An id array flags a batch update (not a create), so required-on-create
				// checks are skipped and unique fields are rejected (can't be bulk updated)
				if(is_array($dao_fields) && !$dao_class::validate($dao_fields, $error, $record_ids))
					throw new Exception_DevblocksAutomationError($error);

				if($custom_fields && !DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error, $record_ids))
					throw new Exception_DevblocksAutomationError($error);

				// One query for the whole batch of standard DAO fields
				if($dao_fields)
					$dao_class::update($record_ids, $dao_fields);

				// [TODO] Naive per-record loop for custom fields; optimize to one query per value table
				if($custom_fields) {
					foreach($record_ids as $record_id)
						DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $record_id, $custom_fields);
				}
			}

			if($output) {
				$dict->set($output, [
					'record_type' => $context_ext->id,
					'record_ids' => $record_ids,
					'count' => count($record_ids),
				]);
			}

		} catch (Exception_DevblocksAutomationError $e) {
			$error = sprintf("[%s] %s", $this->node->getId(), $e->getMessage());

			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if ($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}

				return $event_error->getId();
			}

			return false;

		} finally {
			// Reset the event listener status
			DevblocksPlatform::services()->event()->setEnabled($was_events_enabled);
		}

		if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
			return $event_success->getId();
		}

		return $this->node->getParent()->getId();
	}
}
