<?php
class Event_JiraIssueCommented extends AbstractEvent_JiraIssue {
	const ID = 'wgmjira.event.issue.commented';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($context_id, $comment_id, $variables=[]) {
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'context_id' => $context_id,
					'comment_id' => $comment_id,
					'_variables' => $variables,
				)
			)
		);
	}
};