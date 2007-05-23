<?php
require_once(dirname(__FILE__) . '/DAO.php');

class ChFeedEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        $feeds = DAO_Feed::getList();

        if(is_array($feeds))
        foreach($feeds as $feed) {
            if(empty($feed) || !isset($feed->params[$event->id]))
            continue;

            $fields = array(
            DAO_FeedItem::EVENT_ID => $event->id,
            DAO_FeedItem::FEED_ID => $feed->id,
            DAO_FeedItem::PARAMS => serialize($event->params)
            );
            DAO_FeedItem::create($fields);
        }
    }
};

class ChFeedsController extends DevblocksControllerExtension {
    const ID = 'rss.controller';

    function __construct($manifest) {
        parent::__construct($manifest);

        $router = DevblocksPlatform::getRoutingService();
        $router->addRoute('rss', self::ID);
    }

    function isVisible() {
    }

    /*
     * Request Overload
     */
    function handleRequest(DevblocksHttpRequest $request) {
        $event_points = DevblocksPlatform::getEventPointRegistry();
         
        $stack = $request->path;
        
        array_shift($stack); // rss
        $code = array_shift($stack);
        
        if(!empty($stack))
            $clear = array_shift($stack);
        
        $feed = DAO_Feed::getByCode($code);
        if(empty($feed))
            die("Bad feed data.");
         
        // [TODO] Implement logins for the wiretap app
        header("Content-Type: text/xml");

        $xmlstr = <<<XML
		<rss version='2.0'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);

        // Channel
        // [JAS]: [TODO] Support HTTPS/etc
        $host = 'http://' . $_SERVER['HTTP_HOST'] . DEVBLOCKS_WEBPATH;

        $channel = $xml->addChild('channel');

        $channel->addChild('title', $feed->title);

        $url = DevblocksPlatform::getUrlService();
        $channel->addChild('link', $host);

        $channel->addChild('description', '');

        list($events, $events_count) = DAO_FeedItem::search(
	        array(
    	        new DevblocksSearchCriteria(SearchFields_FeedItem::FEED_ID,DevblocksSearchCriteria::OPER_EQ,$feed->id)
	        ),
	        50,
	        0,
	        SearchFields_FeedItem::CREATED,
	        true,
	        false
        );

        $translate = DevblocksPlatform::getTranslationService();
        $notify_ids = array();

        foreach($events as $event) {
            $notify_ids[] = intval($event[SearchFields_FeedItem::ID]);
            $event_id = $event[SearchFields_FeedItem::EVENT_ID];
            $param_str = $event[SearchFields_FeedItem::PARAMS];
            $created = intval($event[SearchFields_FeedItem::CREATED]);
            if(empty($created)) $created = time();
            $params = (!empty($param_str)) ? unserialize($param_str) : array();
            	
            if(!isset($event_points[$event_id]))
                continue;
            	
            $eItem = $channel->addChild('item');
            	
            $string = $translate->_('event.' . $event_id);
            $desc = '';
            if(!empty($string))
                $desc = vsprintf($string, $params);
            	
            $eTitle = $eItem->addChild('title', $event_points[$event_id]->name);
            $eDesc = $eItem->addChild('description', $desc);
            	
            $url = DevblocksPlatform::getUrlService();
            //			$link = (!empty($post['p_permalink']))
            //				? $url->write(sprintf('c=read&y=%d&m=%d&s=%s',gmdate('Y',$post['p_created']),gmdate('m',$post['p_created']),$post['p_permalink']))
            //				: $url->write('c=read&id='.$post['p_id'])
            //				;
            $eLink = $eItem->addChild('link', $host);
            	
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T',$created));
        }

        // [TODO] Take a param to prune the feed?
        if($clear && !empty($notify_ids))
            DAO_FeedItem::delete($notify_ids);

        echo $xml->asXML();
				     
        exit;
    }
};

class ChFeedsPage extends CerberusPageExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
        //		$path = realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR;
    }

    function isVisible() {
        // check login
        $session = DevblocksPlatform::getSessionService();
        $visit = $session->getVisit();

        if(empty($visit)) {
            return false;
        } else {
            return true;
        }
    }

    function render() {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__) . '/../templates');
        $tpl->assign('path', $tpl_path);
        $tpl->cache_lifetime = "0";

        $response = DevblocksPlatform::getHttpResponse();
        $stack = $response->path;

        array_shift($stack); // feeds

        $worker_id = CerberusApplication::getActiveWorker()->id;

        switch(strtolower(array_shift($stack))) {
            default:
                list($feeds,$feeds_count) = DAO_Feed::search(
                array(
                new DevblocksSearchCriteria(SearchFields_Feed::WORKER_ID,DevblocksSearchCriteria::OPER_EQ,$worker_id)
                ),
                100,
                0,
                SearchFields_Feed::TITLE,
                true,
                false
                );
                $tpl->assign('feeds', $feeds);

                $tpl->display('file:' . $tpl_path . '/feeds/index.tpl.php');
                break;

            case 'create':
            case 'manage':
                if(!empty($stack)) { // modify
                    $code = array_shift($stack);
                    $feed = DAO_Feed::getByCode($code,$worker_id);
                    $tpl->assign('feed', $feed);
                }

                $plugins = DevblocksPlatform::getPluginRegistry();
                $tpl->assign('plugins', $plugins);

                $tpl->display('file:' . $tpl_path . '/feeds/feed.tpl.php');

                break;
        }

    }

    // Post
    function saveFeedAction() {
        @$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
        @$title = DevblocksPlatform::importGPC($_POST['title'],'string','Feed');
        @$events = DevblocksPlatform::importGPC($_POST['events'],'array',array());

        $worker_id = CerberusApplication::getActiveWorker()->id;
        $fields = array(
        DAO_Feed::TITLE => $title,
        DAO_Feed::WORKER_ID => $worker_id,
        DAO_Feed::PARAMS => serialize(array_flip($events))
        );
         
        if(empty($id)) {
            $id = DAO_Feed::create($fields);
        } else {
            DAO_Feed::update($id, $fields);
        }
         
        DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('feeds')));
    }
};

?>