<?php
class Toolbar_MailCompose extends Extension_Toolbar {
	const ID = 'cerb.toolbar.mail.compose';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getInteractionInputsMeta() : array {
		return [
			[
				'key' => 'selected_text',
				'notes' => 'The currently selected editor text',
			],
		];
	}
	
	function getInteractionOutputMeta(): array {
		return [
			[
				'key' => 'snippet:',
				'notes' => 'A snippet of text to insert in the editor at the cursor',
			],
		];
	}
	
	function getInteractionAfterMeta() : array {
		return [
		];
	}
}