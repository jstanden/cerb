<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class ChRssController extends DevblocksControllerExtension {
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
		
		// Grab the worker
		if(null == ($worker = DAO_Worker::get($feed->worker_id))) {
			die($translate->_('rss.bad_feed'));
		}
		
		// Make sure the feed is valid
		// and the worker isn't disabled
		if(empty($feed) || $worker->is_disabled) {
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
