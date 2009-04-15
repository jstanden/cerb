<?php
class UmScAnnouncementsController extends Extension_UmScController {
	const PARAM_NEWS_RSS = 'announcements.rss';
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$sNewsRss = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_NEWS_RSS, '');
		$aNewsRss = !empty($sNewsRss) ? unserialize($sNewsRss) : array();
		
		$feeds = array();
		
		// [TODO] Implement a feed cache so we aren't bombing out
		foreach($aNewsRss as $title => $url) {
			$feed = null;
			try {
    			$feed = Zend_Feed::import($url);
			} catch(Exception $e) {}
    		if(!empty($feed) && $feed->count()) {
   				$feeds[] = array(
   					'name' => $title,
   					'url' => $url,
   					'feed' => $feed
   				);
    		}
		}
		
		$tpl->assign('feeds', $feeds);
		
		$tpl->display("file:${tpl_path}portal/sc/module/announcements/index.tpl");
	}
	
	function configure() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

        $sNewsRss = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_NEWS_RSS, '');
        $news_rss = !empty($sNewsRss) ? unserialize($sNewsRss) : array();
        $tpl->assign('news_rss', $news_rss);
		
		$tpl->display("file:${tpl_path}portal/sc/config/module/announcements.tpl");
	}
	
	function saveConfiguration() {
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
        
		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_NEWS_RSS, serialize($aNewsRss));
	}
	
};