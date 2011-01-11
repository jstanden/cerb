<?php
if (class_exists('Extension_ActivityTab')):
class FeedsCron extends CerberusCronPageExtension {
	public function run() {
		$logger = DevblocksPlatform::getConsoleLog();
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
					C4_ORMHelper::qstr($guid),
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
					DAO_FeedItem::TITLE => $item['title'],
					DAO_FeedItem::URL => $item['link'],
				);
				$item_id = DAO_FeedItem::create($fields);
				
				$logger->info(sprintf("[Feeds] [%s] Imported: %s", $feed->name, $item['title']));
			}
		}		
		
		$logger->info("[Feeds] Feed Reader Finished");
	}
	
	public function configure($instance) {
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->display('devblocks:example.cron::cron/config.tpl');
	}
	
	public function saveConfigurationAction() {
//		@$example_waitdays = DevblocksPlatform::importGPC($_POST['example_waitdays'], 'integer');
//		$this->setParam('example_waitdays', $example_waitdays);
	}
};
endif;

if (class_exists('Extension_ActivityTab')):
class FeedsActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_FEEDS = 'activity_feeds';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Remember the tab
//		$visit->set(CerberusVisit::KEY_ACTIVITY_TAB, 'opps');
		
		// Read original request
		@$request_path = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		@$stack =  explode('/', $request_path);
		@array_shift($stack); // activity
		@array_shift($stack); // feeds
		
		// Index
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_FeedItem';
		$defaults->id = self::VIEW_ACTIVITY_FEEDS;
		$defaults->renderSortBy = SearchFields_FeedItem::CREATED_DATE;
		$defaults->renderSortAsc = 0;
		
		$view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_FEEDS, $defaults);
		
		//$quick_search_type = $visit->get('crm.opps.quick_search_type');
		//$tpl->assign('quick_search_type', $quick_search_type);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.feed_reader::feeds/activity_tab/index.tpl');		
	}
}
endif;

class Page_Feeds extends CerberusPageExtension {
	function isVisible() {
		$active_worker = CerberusApplication::getActiveWorker();
		return ($active_worker) ? true : false;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$response = DevblocksPlatform::getHttpResponse();

		// Remember the last tab/URL
		if(null == ($selected_tab = @$response->path[1])) {
			//$selected_tab = $visit->get(CerberusVisit::KEY_ACTIVITY_TAB, '');
		}
		$tpl->assign('selected_tab', $selected_tab);

		// Path
		$stack = $response->path;
		@array_shift($stack); // feeds
		@$module = array_shift($stack); // item
		
		switch($module) {
			case 'item':
				@$id = array_shift($stack); // id

				if(null != ($item = DAO_FeedItem::get($id)))
					$tpl->assign('item', $item);

				$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.feeds.item.tab', false);
				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
				$tpl->assign('tab_manifests', $tab_manifests);
				
				$tpl->display('devblocks:cerberusweb.feed_reader::feeds/item/display/index.tpl');
				break;
				
//			default:
//				$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.feeds.tab', false);
//				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
//				$tpl->assign('tab_manifests', $tab_manifests);
//				
//				$tpl->display('devblocks:cerberusweb.feed_reader::feeds/item/page/index.tpl');
//				break;
		}		
	}
	
	// Ajax
//	function showTabAction() {
//		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
//		
//		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
//			&& null != ($inst = $tab_mft->createInstance()) 
//			&& $inst instanceof Extension_RssTab) {
//			$inst->showTab();
//		}
//	}
		
	function showFeedItemPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($id) && null != ($item = DAO_FeedItem::get($id))) {
			$tpl->assign('model', $item);
		}
		
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.feed.item');
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds('cerberusweb.contexts.feed.item', $id);
			if(isset($custom_field_values[$id]))
				$tpl->assign('custom_field_values', $custom_field_values[$id]);
		}

		// Comments
		$comments = DAO_Comment::getByContext('cerberusweb.contexts.feed.item', $id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		// Workers
		$context_workers = CerberusContexts::getWorkers('cerberusweb.contexts.feed.item', $id);
		$tpl->assign('context_workers', $context_workers);
		
		$tpl->display('devblocks:cerberusweb.feed_reader::feeds/item/peek.tpl');
	}
	
	function saveFeedItemPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$is_closed = DevblocksPlatform::importGPC($_REQUEST['is_closed'], 'integer', 0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_FeedItem::delete($id);
			
		} else {
			if(empty($id)) { // New
				$fields = array(
					DAO_FeedItem::IS_CLOSED => $is_closed,
				);
				$id = DAO_FeedItem::create($fields);
				
			} else { // Edit
				$fields = array(
					DAO_FeedItem::IS_CLOSED => $is_closed,
				);
				DAO_FeedItem::update($id, $fields);
				
			}

			// If we're adding a comment
			if(!empty($comment)) {
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => 'cerberusweb.contexts.feed.item',
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
				);
				$comment_id = DAO_Comment::create($fields);
			}
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost('cerberusweb.contexts.feed.item', $id, $field_ids);
			
			// Owners
			@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
			CerberusContexts::setWorkers('cerberusweb.contexts.feed.item', $id, $worker_ids);
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
	}
	
	function showFeedItemBulkUpdateAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.feed.item');
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.feed_reader::feeds/item/bulk.tpl');
	}
	
	function doFeedItemBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Call fields
		$is_closed = trim(DevblocksPlatform::importGPC($_POST['is_closed'],'string',''));

		$do = array();
		
		// Do: Due
		if(0 != strlen($is_closed))
			$do['is_closed'] = !empty($is_closed) ? 1 : 0;
			
		// Owners
		$owner_params = array();
		
		@$owner_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_add_ids'],'array',array());
		if(!empty($owner_add_ids))
			$owner_params['add'] = $owner_add_ids;
			
		@$owner_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_remove_ids'],'array',array());
		if(!empty($owner_remove_ids))
			$owner_params['remove'] = $owner_remove_ids;
		
		if(!empty($owner_params))
			$do['owner'] = $owner_params;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}	
	
	function viewFeedItemExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 25;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=activity&tab=tasks', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $id => $row) {
				if($id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $id,
					'url' => $url_writer->write(sprintf("c=feeds&i=item&id=%d", $row[SearchFields_FeedItem::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}

	function showFeedsManagerPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Feeds
		$feeds = DAO_Feed::getWhere();
		$tpl->assign('feeds', $feeds);
		
		// Template		
		$tpl->display('devblocks:cerberusweb.feed_reader::feeds/feed/manager_popup.tpl');		
	}
	
	function saveFeedsManagerPopupAction() {
		@$feed_ids = DevblocksPlatform::importGPC($_REQUEST['feed_id'], 'array', array());
		@$feed_names = DevblocksPlatform::importGPC($_REQUEST['feed_name'], 'array', array());
		@$feed_urls = DevblocksPlatform::importGPC($_REQUEST['feed_url'], 'array', array());
		
		// Compare to existing feeds
		$feeds = DAO_Feed::getWhere();

		// Do deletes
		$deleted_ids = array_diff(array_keys($feeds), $feed_ids);
		if(is_array($deleted_ids))
			DAO_Feed::delete($deleted_ids);

		// Do we need to update anything?
		if(is_array($feed_ids))
		foreach($feed_ids as $idx => $feed_id) {
			if(empty($feed_names[$idx]) || empty($feed_urls[$idx]))
				continue;
			
			if(empty($feed_id) || !isset($feeds[$feed_id])) { // Create
				$fields = array(
					DAO_Feed::NAME => $feed_names[$idx],
					DAO_Feed::URL => $feed_urls[$idx],
				);
				$feed_id = DAO_Feed::create($fields);
				
				// [TODO] Synchronize new feeds
				
			} else { // Update
				if(0 != strcmp(md5($feed->name.$feed->url), md5($feed_names[$idx].$feed_urls[$idx]))) {
					$fields = array(
						DAO_Feed::NAME => $feed_names[$idx],
						DAO_Feed::URL => $feed_urls[$idx],
					);
					DAO_Feed::update($feed_id, $fields);
				}
			}
		}
	}
	
};