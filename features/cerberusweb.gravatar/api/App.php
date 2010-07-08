<?php
class WgmGravatarCommentBadge extends Extension_CommentBadge {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render(Model_Comment $comment) {
		if(null != ($email_address = $comment->getAddress())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('gravatar_email', $email_address->email);
			$tpl->display('devblocks:cerberusweb.gravatar::gravatar_icon.tpl');
		}
	}
};

class WgmGravatarMessageBadge extends Extension_CommentBadge {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render(Model_Message $message) {
		if(null != ($email_address = $message->getSender())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('gravatar_email', $email_address->email);
			$tpl->display('devblocks:cerberusweb.gravatar::gravatar_icon.tpl');
		}
	}
};