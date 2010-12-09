<?php
if (class_exists('Extension_CommentBadge')):
class WgmGravatarCommentBadge extends Extension_CommentBadge {
	function render(Model_Comment $comment) {
		if(null != ($email_address = $comment->getAddress())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$url_writer = DevblocksPlatform::getUrlService();

			$tpl->assign('is_ssl', $url_writer->isSSL());
			$tpl->assign('gravatar_email', $email_address->email);
			
			$tpl->display('devblocks:cerberusweb.gravatar::renderers/gravatar_icon.tpl');
		}
	}
};
endif;

if (class_exists('Extension_MessageBadge')):
class WgmGravatarMessageBadge extends Extension_MessageBadge {
	function render(Model_Message $message) {
		
		if(null != ($email_address = $message->getSender())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$url_writer = DevblocksPlatform::getUrlService();
			
			$tpl->assign('is_ssl', $url_writer->isSSL());
			$tpl->assign('gravatar_email', $email_address->email);
			
			$tpl->display('devblocks:cerberusweb.gravatar::renderers/gravatar_icon.tpl');
		}
	}
};
endif;

if (class_exists('Extension_SupportCenterMessageBadge')):
class WgmGravatarSupportCenterMessageBadge extends Extension_SupportCenterMessageBadge {
	function render(Model_Message $message) {
		if(null != ($email_address = $message->getSender())) {
			$tpl = DevblocksPlatform::getTemplateService();
			$url_writer = DevblocksPlatform::getUrlService();
			
			$tpl->assign('is_ssl', $url_writer->isSSL());
			$tpl->assign('gravatar_email', $email_address->email);
			
			$tpl->display('devblocks:cerberusweb.gravatar::renderers/gravatar_icon.tpl');
		}
	}
};
endif;
