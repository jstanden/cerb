<?php
class Event_KbArticleMacro extends AbstractEvent_KbArticle {
	const ID = 'event.macro.kb_article';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($trigger_id, $article_id, $variables=array()) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'article_id' => $article_id,
                    '_variables' => $variables,
                	'_whisper' => array(
                		'_trigger_id' => array($trigger_id),
                	),
                )
            )
		);
	}
};