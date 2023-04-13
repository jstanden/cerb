<?php
class Toolbar_CommentEditor extends Extension_Toolbar {
	const ID = 'cerb.toolbar.comment.editor';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'record_*',
				'notes' => 'The parent [record](https://cerb.ai/docs/records/types/) being commented upon. Supports key expansion.',
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
			[
				'key' => 'record_id',
				'notes' => 'The record  ID being commented upon',
			],
			[
				'key' => 'record_type',
				'notes' => 'The record type being commented upon',
			],
		];
	}
	
	function getInteractionOutputMeta() : array {
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