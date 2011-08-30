<?php
class UmScKbRssController extends Extension_UmScRssController {
	const ID = 'cerberusweb.kb.sc.rss.controller';

	function __const($manifest=null) {
		parent::_const($manifest);
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$path = $request->path;
		
		if(empty($path) || !is_array($path))
			return;

		if(null == ($portal = ChPortalHelper::getCode()))
			return;
		
		switch(array_shift($path)) {
			case 'most_popular':
				$this->_renderMostPopularRss($portal);
				break;
				
			case 'recent_changes':
				$this->_renderRecentChangesRss($portal);
				break;
				
			case 'new_articles':
				$this->_renderNewArticlesRss($portal);
				break;
		}
	}
	
	private function _renderMostPopularRss($portal) {
		header("Content-Type: text/xml");
		
        $xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);
        $translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

		// Portal details
		$portal_name = DAO_CommunityToolProperty::get($portal, UmScApp::PARAM_PAGE_TITLE, '');

        // Channel
        $channel = $xml->addChild('channel');
		$channel->addChild('title', (!empty($portal_name) ? ('['.$portal_name.'] ') : '') . "Most Popular Articles");
        $channel->addChild('link', $url->write(sprintf('c=rss&kb=kb&a=most_popular', $portal),true));
        $channel->addChild('description', '');
        
		// Limit topics to portal config
		@$topics = unserialize(DAO_CommunityToolProperty::get($portal, UmScKbController::PARAM_KB_ROOTS, ''));
		if(empty($topics))
			return;
		
        // Search Results
		list($results, $null) = DAO_KbArticle::search(
			array(),
			array(
				SearchFields_KbArticle::TOP_CATEGORY_ID => new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($topics)),
			),
			25,
			0,
			SearchFields_KbArticle::VIEWS,
			false,
			false
		);

		$articles = DAO_KbArticle::getWhere(
			sprintf("%s IN (%s)",
				DAO_KbArticle::ID,
				implode(',', array_keys($results))
			),
			DAO_KbArticle::VIEWS,
			false
		);
		
        foreach($articles as $article) { /* @var $article Model_KbArticle */
        	$created = intval($article->updated);
            if(empty($created)) $created = time();

            $eItem = $channel->addChild('item');
            
            $escapedSubject = htmlspecialchars($article->title,null,LANG_CHARSET_CODE);
            //filter out a couple non-UTF-8 characters (0xC and ESC)
            $escapedSubject = mb_convert_encoding($escapedSubject, 'utf-8', LANG_CHARSET_CODE);
            $eTitle = $eItem->addChild('title', $escapedSubject);

            $escapedDesc = htmlspecialchars($article->getContent(),null,LANG_CHARSET_CODE);
            $escapedDesc = mb_convert_encoding($escapedDesc, 'utf-8', LANG_CHARSET_CODE);
            $eDesc = $eItem->addChild('description', $escapedDesc);

            $link = $url->write('c=kb&a=article&id='.$article->id, true);
            $eLink = $eItem->addChild('link', $link);
	            
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
            $eGuid->addAttribute('isPermaLink', "false");
        }		

        echo $xml->asXML();		
	}
	
	private function _renderRecentChangesRss($portal) {
		header("Content-Type: text/xml");

        $xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);
        $translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

		// Portal details
		$portal_name = DAO_CommunityToolProperty::get($portal, UmScApp::PARAM_PAGE_TITLE, '');

        // Channel
        $channel = $xml->addChild('channel');
		$channel->addChild('title', (!empty($portal_name) ? ('['.$portal_name.'] ') : '') . "Recently Updated Articles");
        $channel->addChild('link', $url->write(sprintf('c=rss&kb=kb&a=recent_changes', $portal),true));
        $channel->addChild('description', '');
        
		// Limit topics to portal config
		@$topics = unserialize(DAO_CommunityToolProperty::get($portal, UmScKbController::PARAM_KB_ROOTS, ''));
		if(empty($topics))
			return;
		
        // Search Results
		list($results, $null) = DAO_KbArticle::search(
			array(),
			array(
				SearchFields_KbArticle::TOP_CATEGORY_ID => new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($topics)),
			),
			25,
			0,
			SearchFields_KbArticle::UPDATED,
			false,
			false
		);

		if(empty($results)) {
			echo $xml->asXML();
			return;
		}
		
		$articles = DAO_KbArticle::getWhere(
			sprintf("%s IN (%s)",
				DAO_KbArticle::ID,
				implode(',', array_keys($results))
			),
			DAO_KbArticle::UPDATED,
			false
		);
		
        foreach($articles as $article) { /* @var $article Model_KbArticle */
        	$created = intval($article->updated);
            if(empty($created)) $created = time();

            $eItem = $channel->addChild('item');
            
            $escapedSubject = htmlspecialchars($article->title,null,LANG_CHARSET_CODE);
            //filter out a couple non-UTF-8 characters (0xC and ESC)
            $escapedSubject = mb_convert_encoding($escapedSubject, 'utf-8', LANG_CHARSET_CODE);
            $eTitle = $eItem->addChild('title', $escapedSubject);

            $escapedDesc = htmlspecialchars($article->getContent(),null,LANG_CHARSET_CODE);
            $escapedDesc = mb_convert_encoding($escapedDesc, 'utf-8', LANG_CHARSET_CODE);
            $eDesc = $eItem->addChild('description', $escapedDesc);

            $link = $url->write('c=kb&a=article&id='.$article->id, true);
            $eLink = $eItem->addChild('link', $link);
	            
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
            $eGuid->addAttribute('isPermaLink', "false");
        }

        echo $xml->asXML();		
	}
	
	private function _renderNewArticlesRss($portal) {
		header("Content-Type: text/xml");

        $xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);
        $translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

		// Portal details
		$portal_name = DAO_CommunityToolProperty::get($portal, UmScApp::PARAM_PAGE_TITLE, '');

        // Channel
        $channel = $xml->addChild('channel');
		$channel->addChild('title', (!empty($portal_name) ? ('['.$portal_name.'] ') : '') . "Recently Added Articles");
        $channel->addChild('link', $url->write(sprintf('c=rss&kb=kb&a=new_articles', $portal),true));
        $channel->addChild('description', '');
        
		// Limit topics to portal config
		@$topics = unserialize(DAO_CommunityToolProperty::get($portal, UmScKbController::PARAM_KB_ROOTS, ''));
		if(empty($topics))
			return;
		
        // Search Results
		list($results, $null) = DAO_KbArticle::search(
			array(),
			array(
				SearchFields_KbArticle::TOP_CATEGORY_ID => new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($topics)),
			),
			25,
			0,
			SearchFields_KbArticle::ID,
			false,
			false
		);

		if(empty($results)) {
			echo $xml->asXML();
			return;
		}
		
		$articles = DAO_KbArticle::getWhere(
			sprintf("%s IN (%s)",
				DAO_KbArticle::ID,
				implode(',', array_keys($results))
			),
			DAO_KbArticle::ID,
			false
		);
		
        foreach($articles as $article) { /* @var $article Model_KbArticle */
        	$created = intval($article->updated);
            if(empty($created)) $created = time();

            $eItem = $channel->addChild('item');
            
            $escapedSubject = htmlspecialchars($article->title,null,LANG_CHARSET_CODE);
            //filter out a couple non-UTF-8 characters (0xC and ESC)
            $escapedSubject = mb_convert_encoding($escapedSubject, 'utf-8', LANG_CHARSET_CODE);
            $eTitle = $eItem->addChild('title', $escapedSubject);

            $escapedDesc = htmlspecialchars($article->getContent(),null,LANG_CHARSET_CODE);
            $escapedDesc = mb_convert_encoding($escapedDesc, 'utf-8', LANG_CHARSET_CODE);
            $eDesc = $eItem->addChild('description', $escapedDesc);

            $link = $url->write('c=kb&a=article&id='.$article->id, true);
            $eLink = $eItem->addChild('link', $link);
	            
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
            $eGuid->addAttribute('isPermaLink', "false");
        }

        echo $xml->asXML();		
	}
	
	
};