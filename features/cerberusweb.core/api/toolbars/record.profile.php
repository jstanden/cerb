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
				'key' => 'refresh_toolbar@bool:',
				'notes' => 'Refresh the current [toolbar](https://cerb.ai/docs/toolbars/)',
			],
			[
				'key' => 'refresh_widgets@csv:',
				'notes' => 'One or more [profile widget](https://cerb.ai/docs/records/types/profile_widget/) names to refresh. Can also use `@bool:` to refresh all (yes) or none (no).',
			],
		];
	}
	
	public function getAutocompleteSuggestions() : array {
		$suggestions = parent::getAutocompleteSuggestions();
		
		$suggestions['*']['(.*):?interaction:after:'] = [
			'refresh_toolbar@bool: yes',
			'refresh_widgets@bool: no',
			'refresh_widgets@csv: Some Widget, Other Widget',
		];
		
		return $suggestions;
	}
}