<?php
class Toolbar_RecordsWorklist extends Extension_Toolbar {
	const ID = 'cerb.toolbar.records.worklist';

	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'worklist_record_type',
				'notes' => 'The [record type](https://cerb.ai/docs/records/types/) of the worklist (e.g. `ticket`).',
			],
			[
				'key' => 'worklist_id',
				'notes' => 'The id of the worklist (e.g. `cust_1234`).',
			],
			[
				'key' => 'worklist_query',
				'notes' => 'The [query](https://cerb.ai/docs/search/) of the worklist (e.g. `status:o group:Support`).',
			],
			[
				'key' => 'worklist_query_required',
				'notes' => 'The required [query](https://cerb.ai/docs/search/) of the worklist (e.g. `status:o group:Support`).',
			],
			[
				'key' => 'worklist_page',
				'notes' => 'The current page of the worklist (e.g. `2`).',
			],
			[
				'key' => 'worklist_limit',
				'notes' => 'The number of records per worklist page (e.g. `25`).',
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
				'key' => 'worklist_id',
				'notes' => 'The ID of the displayed worklist.',
			],
			[
				'key' => 'worklist_record_type',
				'notes' => 'The record type of the displayed worklist.',
			],
			[
				'key' => 'selected_record_ids',
				'notes' => 'An array of selected record IDs in the worklist (if any).',
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
				'key' => 'refresh_worklist@bool:',
				'notes' => 'Refresh the worklist after an interaction ends',
			]
		];
	}
}