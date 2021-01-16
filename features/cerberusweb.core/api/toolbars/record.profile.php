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
	
	function getAfterMeta() : array {
		return [
		];
	}
}