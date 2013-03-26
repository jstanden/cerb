<?php
if (class_exists('Extension_CommentBadge')):
class ExCommentBadge extends Extension_CommentBadge {
	function render(Model_Comment $comment) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('comment_author_meta', $comment->getOwnerMeta());
		$tpl->display('devblocks:example.comment.badge::badge.tpl');
	}
};
endif;