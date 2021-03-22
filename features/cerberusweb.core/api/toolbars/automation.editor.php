<?php
class Toolbar_AutomationEditor extends Extension_Toolbar {
	const ID = 'cerb.toolbar.automation.editor';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'trigger_id',
				'notes' => 'The extension ID of the current automation trigger',
			],
			[
				'key' => 'trigger_name',
				'notes' => 'The name of the current automation trigger',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getAfterMeta() : array {
		return [
		];
	}
}