<?php
if (class_exists('Extension_SupportCenterMessageBadge')):
class ExSCMessageBadge extends Extension_SupportCenterMessageBadge {
	function render(Model_Message $message) {
		if(null != ($email_address = $message->getSender())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('example_message_email', $email_address->email);
			$tpl->display('devblocks:example.support_center.message.badge::comment_badge.tpl');
		}
	}
};
endif;