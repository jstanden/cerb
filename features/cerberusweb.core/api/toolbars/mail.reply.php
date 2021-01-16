<?php
class Toolbar_MailReply extends Extension_Toolbar {
	const ID = 'cerb.toolbar.mail.reply';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'message_*',
				'notes' => 'The [message](https://cerb.ai/docs/records/types/message/#dictionary-placeholders) record being replied to. Supports key expansion.',
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