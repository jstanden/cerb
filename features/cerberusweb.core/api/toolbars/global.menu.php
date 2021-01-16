<?php
class Toolbar_GlobalMenu extends Extension_Toolbar {
	const ID = 'cerb.toolbar.global.menu';
	
	function getPlaceholdersMeta() : array {
		return [
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