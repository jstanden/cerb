<?php
class Toolbar_RecordProfile extends Extension_Toolbar {
	const ID = 'cerb.toolbar.record.profile';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'record_*',
				'notes' => 'The [record](https://cerb.ai/docs/records/types) profile being viewed. Supports key expansion. The `record__type` placeholder is the type (e.g. `ticket`).',
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
				'notes' => 'One or more [profile widget](https://cerb.ai/docs/records/types/profile_widget/) names to refresh',
			],
		];
	}
}