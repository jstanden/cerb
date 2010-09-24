<?php
if (class_exists('Extension_MessageBadge')):
class ExMessageBadge extends Extension_MessageBadge {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render(Model_Message $message) {
		if(null != ($email_address = $message->getSender())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('example_message_email', $email_address->email);
			$tpl->display('devblocks:example.message.badge::comment_badge.tpl');
		}
	}
};
endif;