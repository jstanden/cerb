<?php
class ChFnrScController extends Extension_UmScController {
	const PARAM_REQUIRE_LOGIN = 'fnr.require_login';
	const PARAM_FNR_TOPICS = 'fnr.roots';
	
	const SESSION_ARTICLE_LIST = 'fnr_article_list';	
	
	function isVisible() {
		$require_login = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_REQUIRE_LOGIN, 0);
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		// If we're requiring log in...
		if($require_login && empty($active_user))
			return false;
		
		// Disable F&R if no topics were selected
		$sFnrTopics = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_FNR_TOPICS, '');
        $fnr_topics = !empty($sFnrTopics) ? unserialize($sFnrTopics) : array();
        return !empty($fnr_topics);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // resources
		
		// F&R Topics
		$sFnrTopics = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_FNR_TOPICS, '');
        $fnr_topics = !empty($sFnrTopics) ? unserialize($sFnrTopics) : array();
		
		switch(array_shift($stack)) {
			default:
			case 'search':
				@$q = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
				$tpl->assign('q', $q);

				if(!empty($q)) {
					$feeds = array();
					$where = null;
					
					if(!empty($fnr_topics)) {
						$where = sprintf("%s IN (%s)",
							DAO_FnrExternalResource::TOPIC_ID,
							implode(',', array_keys($fnr_topics))
						);
					}
				
					$resources = DAO_FnrExternalResource::getWhere($where);
					$feeds = Model_FnrExternalResource::searchResources($resources, $q);
				
					$tpl->assign('feeds', $feeds);
					$tpl->assign('sources', $sources);
				}

				$tpl->display("file:${tpl_path}portal/sc/resources/search_results.tpl");
				break;
				
		}
		
	}
	
	function configure() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';

//		$require_login = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_REQUIRE_LOGIN, 0);
//		$tpl->assign('kb_require_login', $require_login);

		// F&R
		$fnr_topics = DAO_FnrTopic::getWhere();
		$tpl->assign('fnr_topics', $fnr_topics);

		$sFnrTopics = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_FNR_TOPICS, '');
        $fnr_topics = !empty($sFnrTopics) ? unserialize($sFnrTopics) : array();
        $tpl->assign('enabled_topics', $fnr_topics);

		$tpl->display("file:${tpl_path}portal/sc/config/resources.tpl");
	}
	
	function saveConfiguration() {
//        @$iRequireLogin = DevblocksPlatform::importGPC($_POST['kb_require_login'],'integer',0);
//		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_REQUIRE_LOGIN, $iRequireLogin);
		
        // KB
        @$aFnrTopics = DevblocksPlatform::importGPC($_POST['topic_ids'],'array',array());
        $aFnrTopics = array_flip($aFnrTopics);
		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_FNR_TOPICS, serialize($aFnrTopics));
	}
};