<?php
class ChFnrScController extends Extension_UmScController {
	const PARAM_FNR_TOPICS = 'fnr.roots';
	
	const SESSION_ARTICLE_LIST = 'fnr_article_list';	
	
	function isVisible() {
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

				$tpl->display("devblocks:cerberusweb.fnr:support_center/fnr/search_results.tpl:portal_".UmPortalHelper::getCode());
				break;
				
		}
		
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';

		// F&R
		$fnr_topics = DAO_FnrTopic::getWhere();
		$tpl->assign('fnr_topics', $fnr_topics);

		$sFnrTopics = DAO_CommunityToolProperty::get($instance->code,self::PARAM_FNR_TOPICS, '');
        $fnr_topics = !empty($sFnrTopics) ? unserialize($sFnrTopics) : array();
        $tpl->assign('enabled_topics', $fnr_topics);

		$tpl->display("file:${tpl_path}portal/sc/config/resources.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
        // KB
        @$aFnrTopics = DevblocksPlatform::importGPC($_POST['topic_ids'],'array',array());
        $aFnrTopics = array_flip($aFnrTopics);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_FNR_TOPICS, serialize($aFnrTopics));
	}
};