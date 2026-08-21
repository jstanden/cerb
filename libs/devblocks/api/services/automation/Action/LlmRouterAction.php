<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_AgentModel;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

/**
 * `llm.router:` — resolve one or more `agent_model` searches to the `models:` map that `llm.agent:`,
 * `llm.chat:`, and `agentPrompt` already consume.
 *
 * Nothing here names a model, and nothing names a router record. The CALLER states what the work needs as a
 * query; the ADMIN states what the org allows as another; the two intersect. Both are hard requirements, and
 * because a query can only ever NARROW the pool (`status:available` is forced ahead of it), handing an
 * automation this vocabulary grants it nothing.
 *
 *     llm.router:
 *       inputs:
 *         models_query/work: hasVision:y                 # what THIS work needs -- Cerb's own vocabulary
 *         models_query/pool: {{config.models_query}}     # what the ADMIN allows -- a workflow `text/` config
 *       output: routed
 *
 *     llm.agent:
 *       inputs:
 *         model@key: routed:models
 *
 * **Each query is its own named key**, `models_query/<name>:`, following the house `<qualifier>/<name>:`
 * convention (`record.search/ticket:`, `chooser/account_id:`). One scalar per key, so the editor can
 * autocomplete the `agent_model` filter vocabulary as you type each one, and there is never a question of
 * whether a line break means a second query or a wrapped one. A bare `models_query:` is accepted for the
 * single-query case; omitting it entirely is the zero-config pool -- every available model, in the admin's
 * `priority` order.
 *
 * A key that resolves to BLANK is kept as an empty query -- which means "every available model", so it adds
 * no narrowing, but it is reported in `queries` and, if it is first, still decides the set and its order. It is
 * deliberately NOT dropped: a restriction that disappears when a setting happens to be blank is the one
 * failure this command must not have. Use `models_query/<name>@optional:` to drop a key when its value is
 * empty -- an explicit choice, not a default.
 *
 * **The FIRST query decides the set and its order; each later one only reduces it.** One rule, no arbitration
 * over whose `sort:` wins. Order is the order the keys are authored in, whatever they're named.
 *
 * Bare `llm.router:` is the usual form; `llm.router/<name>:` disambiguates two calls in the same block (KATA
 * keys are unique among siblings), the same way `record.search/ticket:` does.
 *
 * This command is for when the list is needed **as data** — to round-robin it, weight it by cost, skip a model
 * over a rate or spend budget, balance across credentials, or feed two commands from one resolution. To simply
 * USE models, let `llm.agent:` fall through to the default pool.
 */
class LlmRouterAction extends AbstractAction {
	const ID = 'llm.router';

	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();

		$policy = $automation->getPolicy();

		$params = $automation->getParams($this->node, $dict);
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? '';

		try {
			// Params validation

			$validation->addField('inputs', 'inputs:')
				->array()
			;

			$validation->addField('output', 'output:')
				->string()
				->setRequired(true)
			;

			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);

			$validation->reset();

			// Inputs validation

			// Collected in AUTHOR order, which is what makes "the first query owns the set and the order" a rule
			// rather than an arbitration. `models_query:` and `models_query/<name>:` are the same thing here --
			// the name is for the reader, and for KATA's requirement that sibling keys be unique.
			$queries = [];
			$query_labels = [];

			foreach($inputs as $input_key => $input_value) {
				if('models_query' !== $input_key && !str_starts_with(strval($input_key), 'models_query/'))
					continue;

				// An array or object is an authoring mistake -- almost certainly a list block, which is the shape
				// this input deliberately does NOT take (one query per key is what lets the editor autocomplete it).
				if(!is_null($input_value) && !is_string($input_value) && !is_numeric($input_value))
					throw new Exception_DevblocksAutomationError(sprintf(
						"`%s:` must be a single agent model search. Give each query its own `models_query/<name>:` key.",
						$input_key
					));

				// ⚠ A key that resolves to BLANK is kept, as an empty query. It is NOT silently dropped.
				//
				// Dropping it would be the one direction this command must never move in: a
				// `models_query/pool: {{config.models_query}}` that an admin hasn't filled in would quietly stop
				// narrowing, and a policy that vanishes when a setting is blank looks exactly like one being
				// honored. An empty query still participates -- it reports in `queries`, and if it is FIRST it
				// still owns the set and its order.
				//
				// To genuinely drop a key when its value is empty, annotate it: `models_query/pool@optional:`.
				// That is an explicit authoring choice rather than a default.
				$queries[] = trim(strval($input_value ?? ''));
				$query_labels[] = strval($input_key);
			}

			// Validated per key so an error names the one that's wrong rather than the whole block.
			foreach($query_labels as $i => $label) {
				$validation->reset();

				$validation->addField($label, $label . ':')
					->string()
					->setMaxLength(65535)
				;

				// validateAll() takes its values BY REFERENCE, so this can't be an inline literal.
				$check = [$label => $queries[$i]];

				if(false === ($validation->validateAll($check, $error)))
					throw new Exception_DevblocksAutomationError($error);
			}

			// Policy
			//
			// `models_query:` carries no scope dimension on purpose: it can only narrow a pool that
			// `status:available` already bounds, so there is no privilege for a policy to withhold.

			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);

			if(!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = "The automation policy does not allow the `llm.router:` command.";
				throw new Exception_DevblocksAutomationError($error);
			}

			// Resolution. Each query is resolved and cached SEPARATELY, then intersected in code.
			//
			// Never concatenate the queries into one string. The root group's boolean mode is decided by its
			// first `T_BOOL` token and applies to every sibling, so a fragment holding a top-level `OR` would
			// turn the whole thing into a UNION -- a WIDER pool than either query asked for, which is the one
			// direction this must never go.

			$query_error = null;
			$names = DAO_AgentModel::intersectQueryModelNames($queries, $query_error);

			if($query_error)
				throw new Exception_DevblocksAutomationError($query_error);

			$models = DAO_AgentModel::mapNamesToModels($names);

			if(!$models) {
				throw new Exception_DevblocksAutomationError(
					$queries
						? sprintf("`llm.router:` resolved no available models for: %s", implode('; ', $queries))
						: "`llm.router:` resolved no available models. Every agent model is unlisted, disabled, or missing."
				);
			}

			$dict->set($output, [
				'queries' => array_combine($query_labels, $queries),
				'models' => $models,
			]);

		} catch (Exception_DevblocksAutomationError $e) {
			$error = sprintf("[%s] %s", $this->node->getId(), $e->getMessage());

			if(null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}

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
