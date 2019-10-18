<?php
class FeedsCron extends CerberusCronPageExtension {
	public function run() {
		$logger = DevblocksPlatform::services()->log();
		$logger->info("[Feeds] Starting Feed Reader");
			
		$feeds = DAO_Feed::getWhere();
		
		if(is_array($feeds))
		foreach($feeds as $feed_id => $feed) {
			$rss = DevblocksPlatform::parseRss($feed->url);
			
			if(isset($rss['items']) && is_array($rss['items']))
			foreach($rss['items'] as $item) {
				$guid = md5($feed_id.$item['title'].$item['link']);
	
				// Look up by GUID
				$results = DAO_FeedItem::getWhere(sprintf("%s = %s AND %s = %d",
					DAO_FeedItem::GUID,
					Cerb_ORMHelper::qstr($guid),
					DAO_FeedItem::FEED_ID,
					$feed_id
				));
				
				// If we've already inserted this item, skip it
				if(!empty($results))
					continue;
				
				$fields = array(
					DAO_FeedItem::FEED_ID => $feed_id,
					DAO_FeedItem::CREATED_DATE => $item['date'],
					DAO_FeedItem::GUID => $guid,
					DAO_FeedItem::TITLE => DevblocksPlatform::stripHTML($item['title']),
					DAO_FeedItem::URL => $item['link'],
				);
				$item_id = DAO_FeedItem::create($fields);
				
				if(empty($item_id))
					continue;
				
				if(!empty($item['content'])) {
					$comment = DevblocksPlatform::stripHTML($item['content']);
					
					if(!empty($comment)) {
						$comment_id = DAO_Comment::create(array(
							DAO_Comment::COMMENT => $comment,
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_FEED_ITEM,
							DAO_Comment::CONTEXT_ID => $item_id,
							DAO_Comment::CREATED => time(),
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
							DAO_Comment::OWNER_CONTEXT_ID => 0,
						));
					}
				}
				
				$logger->info(sprintf("[Feeds] [%s] Imported: %s", $feed->name, $item['title']));
			}
		}
		
		$logger->info("[Feeds] Feed Reader Finished");
	}
	
	public function configure($instance) {
	}
	
	public function saveConfigurationAction() {
	}
};

class EventListener_FeedReader extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				//DAO_Feed::maint();
				DAO_FeedItem::maint();
				break;
		}
	}
};