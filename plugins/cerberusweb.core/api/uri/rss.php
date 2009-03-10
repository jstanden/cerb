<?php
class ChRssController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		
		// [TODO] Do we want any concept of authentication here?

        $stack = $request->path;
		array_shift($stack); // rss
		$hash = array_shift($stack);

		$feed = DAO_ViewRss::getByHash($hash);
        if(empty($feed)) {
            die($translate->_('rss.bad_feed'));
        }

        // Sources
        $rss_sources = DevblocksPlatform::getExtensions('cerberusweb.rss.source', true);
        if(isset($rss_sources[$feed->source_extension])) {
        	$rss_source =& $rss_sources[$feed->source_extension]; /* @var $rss_source Extension_RssSource */
			header("Content-Type: text/xml");
        	echo $rss_source->getFeedAsRss($feed);
        }
        
		exit;
	}
};
