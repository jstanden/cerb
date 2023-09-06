<?php
class Toolbar_RecordProfileImageEditor extends Extension_Toolbar {
	const ID = 'cerb.toolbar.record.profile.image.editor';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'record_*',
				'notes' => 'The [record](https://cerb.ai/docs/records/types) profile being viewed. Supports key expansion. The `record__type` placeholder is the type (e.g. `ticket`).',
			],
			[
				'key' => 'image_width',
				'notes' => 'The width of the image to be generated.',
			],
			[
				'key' => 'image_height',
				'notes' => 'The height of the image to be generated.',
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
			[
				'key' => 'image_width',
				'notes' => 'The width of the image to be generated.',
			],
			[
				'key' => 'image_height',
				'notes' => 'The height of the image to be generated.',
			],
		];
	}
	
	function getInteractionOutputMeta(): array {
		return [
			[
				'key' => 'image:text:',
				'notes' => 'Text or emoji to convert into an image.',
			],
			[
				'key' => 'image:url:',
				'notes' => 'An image URL to load.',
			],
		];
	}
	function getInteractionAfterMeta() : array {
		return [
			[
				'key' => 'refresh_toolbar@bool:',
				'notes' => 'Refresh the current [toolbar](https://cerb.ai/docs/toolbars/)',
			],
		];
	}
	
	public function getAutocompleteSuggestions() : array {
		$suggestions = parent::getAutocompleteSuggestions();
		
		$suggestions['*']['(.*):?interaction:after:'] = [
			'refresh_toolbar@bool: yes',
		];
		
		return $suggestions;
	}
}