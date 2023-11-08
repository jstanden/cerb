<?php
class AutomationTrigger_MailRoute extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.mail.route';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'email_sender_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'address',
				],
				'notes' => 'The [sender email](https://cerb.ai/docs/records/types/address/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'email_subject',
				'type' => 'text',
				'notes' => 'The message subject.',
			],
			[
				'key' => 'email_headers',
				'notes' => 'A set of header/value pairs. Keys are lowercase with dashes as underscores (e.g. `content_type`).',
			],
			[
				'key' => 'email_body',
				'type' => 'text',
				'notes' => 'The email body as plaintext.',
			],
			[
				'key' => 'email_body_html',
				'type' => 'text',
				'notes' => 'The email body as HTML (if provided).',
			],
			[
				'key' => 'email_recipients',
				'type' => 'records',
				'params' => [
					'record_type' => 'address',
				],
				'notes' => 'An array of recipient email addresses in the `To:`/`Cc:`/`Envelope-To:`/`Delivered-To:` headers.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'group_id',
					'type' => 'text',
					'notes' => 'The group ID to deliver the message to. Alternative to `group_name`.',
					'required' => false,
				],
				[
					'key' => 'group_name',
					'type' => 'text',
					'notes' => 'The group name to deliver the message to. Alternative to `group_id`.',
					'required' => false,
				],
				[
					'key' => 'bucket_id',
					'type' => 'text',
					'notes' => 'The optional bucket ID to deliver the message to. This can be provided instead of `group_id`.',
					'required' => false,
				],
				[
					'key' => 'bucket_name',
					'type' => 'text',
					'notes' => "The optional bucket name to deliver the message to. A `group_id` or `group_name` must also be provided to disambiguate names like 'Inbox'.",
					'required' => false,
				],
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}