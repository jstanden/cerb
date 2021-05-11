<?php
class Toolbar_MailRead extends Extension_Toolbar {
	const ID = 'cerb.toolbar.mail.read';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'message_*',
				'notes' => 'The [message](https://cerb.ai/docs/records/types/message/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'widget_*',
				'notes' => 'The widget record. Supports key expansion. `widget__type` will be one of: [card_widget](https://cerb.ai/docs/records/types/card_widget/#dictionary-placeholders), [profile_widget](https://cerb.ai/docs/records/types/profile_widget/#dictionary-placeholders), or [workspace_widget](https://cerb.ai/docs/records/types/workspace_widget/#dictionary-placeholders).',
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
				'key' => 'message_id',
				'notes' => 'The [message](https://cerb.ai/docs/records/types/message/) record. Supports key expansion.',
			],
			[
				'key' => 'selected_text',
				'notes' => 'The currently selected editor text',
			],
		];
	}
	
	function getInteractionOutputMeta(): array {
		return [
			[
				'key' => 'reply:draft_id:',
				'notes' => 'The [draft](https://cerb.ai/docs/records/types/draft/) ID to resume',
			],
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