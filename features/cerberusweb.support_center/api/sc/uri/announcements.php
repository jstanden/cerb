<?php
class UmScAnnouncementsController extends Extension_UmScController {
	const PARAM_NEWS_RSS = 'announcements.rss';
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$sNewsRss = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_NEWS_RSS, '');
		$aNewsRss = !empty($sNewsRss) ? unserialize($sNewsRss) : array();
		
		$feeds = array();
		
		// [TODO] Implement a feed cache so we aren't bombing out
		foreach($aNewsRss as $title => $url) {
			$feed = null;
			try {
				$feed = DevblocksPlatform::parseRss($url);
				if(!empty($title))
					$feed['title'] = $title;
			} catch(Exception $e) {}
    		if(!empty($feed) && isset($feed['items']) && !empty($feed['items'])) {
   				$feeds[] = $feed;
    		}
		}

		$tpl->assign('feeds', $feeds);
		
		$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/announcements/index.tpl");
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();

        $sNewsRss = DAO_CommunityToolProperty::get($instance->code,self::PARAM_NEWS_RSS, '');
        $news_rss = !empty($sNewsRss) ? unserialize($sNewsRss) : array();
        $tpl->assign('news_rss', $news_rss);
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/config/module/announcements.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
        // RSS Feeds
        @$aNewsRssTitles = DevblocksPlatform::importGPC($_POST['news_rss_title'],'array',array());
        @$aNewsRssUrls = DevblocksPlatform::importGPC($_POST['news_rss_url'],'array',array());
        
        $aNewsRss = array();
        
        foreach($aNewsRssUrls as $idx => $rss) {
        	if(empty($rss)) {
        		unset($aNewsRss[$idx]);
        		continue;
        	}
        	$aNewsRss[$aNewsRssTitles[$idx]] = $rss;
        }
        
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_NEWS_RSS, serialize($aNewsRss));
	}
	
};