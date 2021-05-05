<?php
class Toolbar_DraftRead extends Extension_Toolbar {
	const ID = 'cerb.toolbar.draft.read';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'draft_*',
				'notes' => 'The [draft](https://cerb.ai/docs/records/types/draft/#dictionary-placeholders) record. Supports key expansion.',
			],
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
		];
	}
	
	function getInteractionAfterMeta() : array {
		return [
			[
				'key' => 'refresh_widgets@list:',
				'notes' => 'One or more widget names to refresh',
			]
		];
	}
}