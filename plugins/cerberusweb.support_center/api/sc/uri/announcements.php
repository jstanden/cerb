<?php /** @noinspection PhpUnused */

class UmScAnnouncementsController extends Extension_UmScController {
	const PARAM_NEWS_RSS = 'announcements.rss';
	
	public function isVisible() {
		return true;
	}
	
	public function invoke(string $action, ?DevblocksHttpRequest $request=null) {
		return false;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$aNewsRss = DAO_CommunityToolProperty::getJson(ChPortalHelper::getCode(),self::PARAM_NEWS_RSS, '[]');
		
		$feeds = [];
		
		// [TODO] Implement a feed cache so we aren't bombing out
		foreach($aNewsRss as $title => $url) {
			$feed = null;
			try {
				if(!($feed = DevblocksPlatform::parseRss($url)))
					continue;
				if(!empty($title))
					$feed['title'] = $title;
			} catch(Throwable) {}
			if(!empty($feed) && isset($feed['items']) && !empty($feed['items'])) {
				$feeds[] = $feed;
			}
		}

		$tpl->assign('feeds', $feeds);
		$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/announcements/index.tpl");
	}
	
	function configure(Model_CommunityTool $portal) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $portal);

		$news_rss = DAO_CommunityToolProperty::getJson($portal->code,self::PARAM_NEWS_RSS, '[]');
		$tpl->assign('news_rss', $news_rss);
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/announcements.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $portal) {
		$aNewsRssTitles = DevblocksPlatform::importGPC($_POST['news_rss_title'] ?? null, 'array', []);
		$aNewsRssUrls = DevblocksPlatform::importGPC($_POST['news_rss_url'] ?? null, 'array', []);
		
		$aNewsRss = array();
		
		foreach($aNewsRssUrls as $idx => $rss) {
			if(empty($rss)) {
				unset($aNewsRss[$idx]);
				continue;
			}
			$aNewsRss[$aNewsRssTitles[$idx]] = $rss;
		}
		
		DAO_CommunityToolProperty::setJson($portal->code, self::PARAM_NEWS_RSS, $aNewsRss);
	}
}