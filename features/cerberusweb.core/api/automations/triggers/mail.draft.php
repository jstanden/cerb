<?php
class AutomationTrigger_MailDraft extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.mail.draft';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		
		try {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
		} catch(SmartyException | Exception $e) {
			error_log($e->getMessage());
		}
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'draft_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'draft',
				],
				'notes' => 'The [draft](https://cerb.ai/docs/records/types/draft/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'is_resumed',
				'type' => 'bool',
				'params' => [],
				'notes' => '`true` if the draft was resumed, `false` if new',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'draft:params',
					'type' => 'dictionary',
					'notes' => 'A dictionary of [draft parameter](https://cerb.ai/docs/records/types/draft/) modifications',
					'required' => false,
				],
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		// Ticket-based custom fields
		$ticket_custom_field_suggestions = array_values(array_map(
			function($field) {
				return [
					'caption' => $field->uri . ':',
					'snippet' => $field->uri . ':',
					'docHTML' => sprintf(
						'<b>%s:</b><br>%s',
						DevblocksPlatform::strEscapeHtml($field->uri),
						DevblocksPlatform::strEscapeHtml($field->getTypeLabel())
					),
				];
			},
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET) ?: []
		));
		
		// Message-based custom fields
		$message_custom_field_suggestions = array_values(array_map(
			function($field) {
				return [
					'caption' => $field->uri . ':',
					'snippet' => $field->uri . ':',
					'docHTML' => sprintf(
						'<b>%s:</b><br>%s',
						DevblocksPlatform::strEscapeHtml($field->uri),
						DevblocksPlatform::strEscapeHtml($field->getTypeLabel())
					),
				];
			},
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MESSAGE) ?: []
		));
		
		return [
			'*' => [
				'(.*):return:' => [
					'draft:',
				],
				'(.*):return:draft:' => [
					'params:',
				],
				'(.*):return:draft:params:' => [
					'bcc:',
					'bucket_id:',
					'cc:',
					'content:',
					'custom_fields:',
					'file_ids:',
					'format: parsedown',
					'group_id:',
					//'message_custom_fields:',
					'options_gpg_encrypt:',
					'options_gpg_sign:',
					'owner_id:',
					'send_at:',
					'status: open',
					'status: waiting',
					'status: closed',
					'subject:',
					'ticket_reopen:',
					'to:',
				],
				'(.*):return:draft:params:custom_fields:' => $ticket_custom_field_suggestions,
				'(.*):return:draft:params:message_custom_fields:' => $message_custom_field_suggestions,
			],
		];
	}
	
	public static function trigger(Model_MailQueue $draft, bool $is_resumed=false) {
		$dict = DevblocksDictionaryDelegate::getDictionaryFromModel($draft, CerberusContexts::CONTEXT_DRAFT);
		
		$initial_state = $dict->getDictionary(null, false, 'draft_');
		
		$initial_state['is_resumed'] = $is_resumed;
		
		$events_kata = DAO_AutomationEvent::getKataByName('mail.draft');
		
		$handlers = DevblocksPlatform::services()->ui()->eventHandler()->parse(
			$events_kata,
			DevblocksDictionaryDelegate::instance($initial_state),
			$error
		);
		
		if(false === $handlers && $error) {
			error_log('[KATA] Invalid mail.draft KATA: ' . $error);
			$handlers = [];
		}
		
		$automation_results = DevblocksPlatform::services()->ui()->eventHandler()->handleEach(
			AutomationTrigger_MailDraft::ID,
			$handlers,
			$initial_state,
			$error
		);
		
		foreach($automation_results as $automation_result) {
			if(!($automation_result instanceof DevblocksDictionaryDelegate))
				continue;
			
			@$new_params = $automation_result->getKeyPath('__return.draft.params', []);
			
			if(array_key_exists('bcc', $new_params) && is_string($new_params['bcc']))
				$draft->params['bcc'] = $new_params['bcc'];
			
			if(array_key_exists('bucket_id', $new_params) && is_string($new_params['bucket_id']))
				$draft->params['bucket_id'] = intval($new_params['bucket_id']);
			
			if(array_key_exists('cc', $new_params) && is_string($new_params['cc']))
				$draft->params['cc'] = $new_params['cc'];
			
			if(array_key_exists('content', $new_params) && is_string($new_params['content']))
				$draft->params['content'] = $new_params['content'];
			
			if(array_key_exists('custom_fields', $new_params) && is_array($new_params['custom_fields']))
				self::_mergeCustomFields($draft, $new_params['custom_fields'], CerberusContexts::CONTEXT_TICKET, 'custom_fields');
			
			if(array_key_exists('message_custom_fields', $new_params) && is_array($new_params['message_custom_fields']))
				self::_mergeCustomFields($draft, $new_params['message_custom_fields'], CerberusContexts::CONTEXT_MESSAGE, 'message_custom_fields');
			
			if(array_key_exists('file_ids', $new_params) && is_array($new_params['file_ids']))
				$draft->params['file_ids'] = $new_params['file_ids'];
			
			if(array_key_exists('format', $new_params) && is_string($new_params['format']))
				$draft->params['format'] = $new_params['format'];
			
			if(array_key_exists('group_id', $new_params) && is_string($new_params['group_id']))
				$draft->params['group_id'] = intval($new_params['group_id']);
			
			if(array_key_exists('options_gpg_encrypt', $new_params) && is_string($new_params['options_gpg_encrypt']))
				$draft->params['options_gpg_encrypt'] = intval($new_params['options_gpg_encrypt']);
			
			if(array_key_exists('options_gpg_sign', $new_params) && is_string($new_params['options_gpg_sign']))
				$draft->params['options_gpg_sign'] = intval($new_params['options_gpg_sign']);
			
			if(array_key_exists('owner_id', $new_params) && is_string($new_params['owner_id']))
				$draft->params['owner_id'] = intval($new_params['owner_id']);
			
			if(array_key_exists('send_at', $new_params) && is_string($new_params['send_at']))
				$draft->params['send_at'] = $new_params['send_at'];
			
			if(array_key_exists('status_id', $new_params) && is_string($new_params['status_id']))
				$draft->params['status_id'] = intval($new_params['status_id']);
			
			if(array_key_exists('status', $new_params) && is_string($new_params['status']))
				$draft->params['status_id'] = DAO_Ticket::getStatusIdFromText($new_params['status']);
			
			if(array_key_exists('subject', $new_params) && is_string($new_params['subject']))
				$draft->params['subject'] = $new_params['subject'];
			
			if(array_key_exists('ticket_reopen', $new_params) && is_string($new_params['ticket_reopen']))
				$draft->params['ticket_reopen'] = $new_params['ticket_reopen'];
			
			if(array_key_exists('to', $new_params) && is_string($new_params['to']))
				$draft->params['to'] = $new_params['to'];
		}
	}
	
	private static function _mergeCustomFields(Model_MailQueue $draft, array $new_custom_fields, string $context, string $custom_fields_key) {
		$custom_fields = DAO_CustomField::getByContext($context);
		$uri_to_field_id = array_column(DevblocksPlatform::objectsToArrays($custom_fields), 'id', 'uri');
		$field_values = [];
		
		// Allow custom field URIs vs IDs
		foreach($new_custom_fields as $field_key => $field_value) {
			if(!is_numeric($field_key))
				$field_key = $uri_to_field_id[$field_key] ?? null;
			
			if(!$field_key)
				continue;
			
			$field_values[$field_key] = $field_value;
		}
		
		$field_values = DAO_CustomFieldValue::formatFieldValues($field_values);
		
		foreach($field_values as $field_id => $field_value) {
			$draft->params[$custom_fields_key][$field_id] = $field_value;
		}
	}
}