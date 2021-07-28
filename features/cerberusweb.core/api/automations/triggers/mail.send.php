<?php
class AutomationTrigger_MailSend extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.mail.send';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		
		try {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
		} catch (SmartyException | Exception $e) {
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
				'notes' => 'The [draft](https://cerb.ai/docs/records/types/draft/#dictionary-placeholders) record being sent. Supports key expansion.',
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
				[
					'key' => 'content',
					'type' => 'dictionary',
					'notes' => 'A dictionary of content modifications',
					'required' => false,
				]
			],
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	/** @noinspection DuplicatedCode */
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
		// [TODO]
		/*
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
		*/
		
		$content_on_suggestions = [
			'html@bool: no',
			'text@bool: no',
			'sent@bool: no',
			'saved@bool: no',
		];
		
		return [
			'*' => [
				'(.*):return:' => [
					'draft:',
					'content:',
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
					'headers:',
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
				//'(.*):return:draft:params:message_custom_fields:' => $message_custom_field_suggestions,
				
				'(.*):return:draft:params:headers:' => [
					'X-HeaderName: header value',
				],
				
				'(.*):return:content:' => [
					'append:',
					'prepend:',
					'replace:',
				],
				
				'(.*):return:content:append:' => [
					'on:',
					'text:',
				],
				'(.*):return:content:append:on:' => $content_on_suggestions,
				
				'(.*):return:content:prepend:' => [
					'on:',
					'text:',
				],
				'(.*):return:content:prepend:on:' => $content_on_suggestions,
				
				'(.*):return:content:replace:' => [
					'on:',
					'text:',
					'with:',
				],
				'(.*):return:content:replace:on:' => $content_on_suggestions,
			],
		];
	}
	
	/** @noinspection DuplicatedCode */
	public static function trigger(Model_MailQueue $draft, array &$properties=[]) {
		$initial_state = [
			'draft__context' => CerberusContexts::CONTEXT_DRAFT,
			'draft_id' => $draft->id,
		];
		
		$events_kata = DAO_AutomationEvent::getKataByName('mail.send');
		
		$handlers = DevblocksPlatform::services()->ui()->eventHandler()->parse(
			$events_kata,
			DevblocksDictionaryDelegate::instance($initial_state)
		);

		$automation_results = DevblocksPlatform::services()->ui()->eventHandler()->handleEach(
			AutomationTrigger_MailSend::ID,
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
			
			// Headers
			
			if(array_key_exists('headers', $new_params) && is_array($new_params['headers'])) {
				if(!array_key_exists('headers', $draft->params))
					$draft->params['headers'] = [];
				
				foreach ($new_params['headers'] as $header_key => $header_value) {
					if($header_value)
						$draft->params['headers'][$header_key] = $header_value;
				}
			}
			
			// Content modifications
			
			@$content_modifications = $automation_result->getKeyPath('__return.content', []);
			
			if(is_array($content_modifications) && $content_modifications) {
				if(!array_key_exists('content_modifications', $properties))
					$properties['content_modifications'] = [];
				
				foreach ($content_modifications as $content_key => $content_data) {
					list($content_key_type,) = array_pad(explode('/', $content_key, 2), 2, null);
					$content_key_type = DevblocksPlatform::strLower($content_key_type);
					
					$content_modification = [];
					
					if('append' == $content_key_type) {
						if(!array_key_exists('text', $content_data))
							continue;
						
						$content_modification = [
							'action' => 'append',
							'params' => [
								'on' => $content_data['on'] ?? [],
								'content' => strval($content_data['text']),
							],
						];
						
					} else if('prepend' == $content_key_type) {
						if(!array_key_exists('text', $content_data))
							continue;
						
						$content_modification = [
							'action' => 'prepend',
							'params' => [
								'on' => $content_data['on'] ?? [],
								'content' => strval($content_data['text']),
							],
						];
						
					} else if('replace' == $content_key_type) {
						if(!array_key_exists('text', $content_data) || !array_key_exists('with', $content_data))
							continue;
						
						$content_modification = [
							'action' => 'replace',
							'params' => [
								'on' => $content_data['on'] ?? [],
								'replace' => strval($content_data['text']),
								'with' => strval($content_data['with']),
							],
						];
						
						if(array_key_exists('regex', $content_data) && $content_data['regex'])
							$content_modification['params']['replace_is_regexp'] = true;
					}
					
					if($content_modification)
						$properties['content_modifications'][] = $content_modification;
				}
			}
		}
	}
	
	/** @noinspection DuplicatedCode */
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