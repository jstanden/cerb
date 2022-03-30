<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class MetricIncrementAction extends AbstractAction {
	const ID = 'metric.increment';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$metrics = DevblocksPlatform::services()->metrics();
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $automation->getParams($this->node, $dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		try {
			// Validate params
			
			$validation->addField('inputs', 'inputs:')
				->array()
				->setRequired(true)
				;
			
			$validation->addField('output', 'output:')
				->string()
				;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate input
			
			$validation->reset();
			
			$validation->addField('dimensions', 'inputs:dimensions:')
				->array()
				;
			
			$validation->addField('is_realtime', 'inputs:is_realtime:')
				->boolean()
				;
			
			$validation->addField('metric_name', 'inputs:metric_name:')
				->string()
				->setRequired(true)
				;
			
			$validation->addField('timestamp', 'inputs:timestamp:')
				->timestamp()
				;
			
			$validation->addField('values', 'inputs:values:')
				->stringOrArray()
				;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
				
			$dimensions = $inputs['dimensions'] ?? [];
			$is_realtime = $inputs['is_realtime'] ?? false;
			$metric_name = $inputs['metric_name'] ?? null;
			$timestamp = $inputs['timestamp'] ?? null;
			$values = $inputs['values'] ?? 1;
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			if(!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not allow this command (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if(false == ($metric = \DAO_Metric::getByName($metric_name))) {
				$error = sprintf('metric.increment: Unknown metric `%s`', $metric_name);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$metric_dimensions = $metric->getDimensions();
			
			if(false != ($unknown_dimensions = array_diff(array_keys($dimensions), array_keys($metric_dimensions)))) {
				$error = sprintf("metric.increment: Unknown dimensions (%s) for metric `%s`",
					implode(', ', $unknown_dimensions),
					$metric_name
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			// Record the metric
			$metrics->increment(
				$metric_name,
				$values,
				$dimensions,
				$timestamp,
				!$is_realtime
			);
			
			if($output) {
				$output_dict = [];
				$dict->set($output, $output_dict);
			}
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				$dict->set($output, [
					'error' => $error,
				]);
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}