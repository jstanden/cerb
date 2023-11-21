<?php
class AutomationTrigger_MailFilter extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.mail.filter';
	
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
				'notes' => 'The [sender email](https://cerb.ai/docs/records/types/address/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'email_subject',
				'notes' => 'The message subject.',
			],
			[
				'key' => 'email_headers',
				'notes' => 'A set of header/value pairs. Keys are lowercase with dashes as underscores (e.g. `content_type`).',
			],
			[
				'key' => 'email_body',
				'notes' => 'The email body as plaintext.',
			],
			[
				'key' => 'email_body_html',
				'notes' => 'The email body as HTML (if provided).',
			],
			[
				'key' => 'email_recipients',
				'notes' => 'An array of recipient email addresses in the `To:`/`Cc:`/`Envelope-To:`/`Delivered-To:` headers.',
			],
			[
				'key' => 'parent_ticket_*',
				'notes' => 'The parent [ticket](https://cerb.ai/docs/records/types/ticket/#dictionary-placeholders) record (if a reply). Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'reject',
					'notes' => '`true` to reject delivery',
					'required' => false,
				],
				[
					'key' => 'set:email_subject',
					'notes' => 'Rewrite the email subject',
					'required' => false,
				],
				[
					'key' => 'set:email_body',
					'notes' => 'Rewrite the email plaintext body',
					'required' => false,
				],
				[
					'key' => 'set:email_body_html',
					'notes' => 'Rewrite the email HTML body',
					'required' => false,
				],
				[
					'key' => 'set:headers',
					'notes' => 'An object of header keys (names) and values',
					'required' => false,
				],
				[
					'key' => 'set:email_sender_org_id',
					'notes' => 'Assign an organization to the email sender',
					'required' => false,
				],
				[
					'key' => 'set:custom_fields',
					'notes' => 'An object of [ticket](https://cerb.ai/docs/records/types/ticket/) custom field keys (ID/URIs) and values',
					'required' => false,
				],
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'reject@bool:',
					'set:',
				],
				'(.*):return:reject@bool:' => [
					'yes',
					'no',
				],
				'(.*):return:set:' => [
					'custom_fields:',
					'email_body:',
					'email_body_html:',
					'email_sender_org_id@int:',
					'email_subject:',
					'headers:',
				],
			]
		];
	}
}