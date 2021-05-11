<?php
class Toolbar_RecordCard extends Extension_Toolbar {
	const ID = 'cerb.toolbar.record.card';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'record_*',
				'notes' => 'The [record](https://cerb.ai/docs/records/types) card being viewed. Supports key expansion. The `record__type` placeholder is the type (e.g. `ticket`).',
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
				'key' => 'record_*',
				'notes' => 'The [record](https://cerb.ai/docs/records/types/) dictionary. Supports key expansion.',
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
				'notes' => 'One or more [card widget](https://cerb.ai/docs/records/types/card_widget/) names to refresh',
			]
		];
	}
}