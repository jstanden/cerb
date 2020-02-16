<?php
use GuzzleHttp\Psr7\Request;

class WgmTwitter_MessageProfileSection extends Extension_PageSection {
	const ID = 'cerberusweb.profiles.twitter.message';
	
	function render() {
	}
	
	// [TODO] This should be handled by the context
	function showPeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('view_id', $view_id);
		
		// Message
		
		if(null != ($message = DAO_TwitterMessage::get($id))) {
			$tpl->assign('message', $message);
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(Context_TwitterMessage::ID, false);
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(Context_TwitterMessage::ID, $message->id);
		if(isset($custom_field_values[$message->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$message->id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Template
		
		$tpl->display('devblocks:wgm.twitter::tweet/peek.tpl');
	}
	
	function savePeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_reply = DevblocksPlatform::importGPC($_POST['do_reply'], 'integer', 0);
		@$reply = DevblocksPlatform::importGPC($_POST['reply'], 'string', '');
		@$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'], 'integer', 0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($id) || null == ($message = DAO_TwitterMessage::get($id)))
			return;
		
		if(!$message->connected_account_id || false == ($connected_account = DAO_ConnectedAccount::get($message->connected_account_id)))
			return;
			
		if(!Context_ConnectedAccount::isReadableByActor($connected_account, $active_worker))
			return;
		
		$fields = [
			DAO_TwitterMessage::IS_CLOSED => $is_closed ? 1 : 0,
		];
		
		DAO_TwitterMessage::update($message->id, $fields);
		
		// Custom field saves
		$error = null;
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
		if(!DAO_CustomFieldValue::handleFormPost(Context_TwitterMessage::ID, $id, $field_ids, $error))
			throw new Exception_DevblocksAjaxValidationError($error);
		
		// Replies
		if(!empty($do_reply) && !empty($reply)) {
			$url = 'https://api.twitter.com/1.1/statuses/update.json';
			
			$post_data = [
				'status' => $reply,
				'in_reply_to_status_id' => $message->twitter_id,
			];
			
			$request = new Request(
				'POST',
				$url,
				[
					'Content-Type' => 'application/x-www-form-urlencoded'
				],
				http_build_query($post_data, null, '&', PHP_QUERY_RFC3986)
			);
			$request_options = [];
			$error = null;
			$actor = [CerberusContexts::CONTEXT_APPLICATION, 0];
			
			if(false == ($connected_account->authenticateHttpRequest($request, $request_options, $actor)))
				return;
			
			$response = DevblocksPlatform::services()->http()->sendRequest($request, $request_options, $error);
			
			if(200 != $response->getStatusCode())
				throw new Exception_DevblocksAjaxValidationError("Failed to reply to tweet.");
			
			//$json = DevblocksPlatform::services()->http()->getResponseAsJson($response);
		}
	}
	
	function viewMarkClosedAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$row_ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());

		try {
			if(is_array($row_ids))
			foreach($row_ids as $row_id) {
				$row_id = intval($row_id);
				
				if(!empty($row_id))
					DAO_TwitterMessage::update($row_id, array(
						DAO_TwitterMessage::IS_CLOSED => 1,
					));
			}
		} catch (Exception $e) {
			//
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		
		exit;
	}
	
	function showBulkUpdatePopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(Context_TwitterMessage::ID, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:wgm.twitter::tweet/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));

		$do = array();
		
		// Do: Status
		if(0 != strlen($status)) {
			switch($status) {
				default:
					$do['status'] = intval($status);
					break;
			}
		}
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_TwitterMessage::ID, 'in', $ids)
			], true);
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
}

class Cron_WgmTwitterChecker extends CerberusCronPageExtension {
	public function run() {
		$logger = DevblocksPlatform::services()->log('Twitter Checker');
		$db = DevblocksPlatform::services()->database();
		
		$logger->info("Started");
		
		$sync_account_ids = DevblocksPlatform::getPluginSetting('wgm.twitter', 'sync_account_ids_json', [], true);
		
		if(!is_array($sync_account_ids) || empty($sync_account_ids))
			return;
		
		$accounts = DAO_ConnectedAccount::getIds($sync_account_ids);
		
		foreach($accounts as $account) {
			$logger->info(sprintf("Checking mentions for @%s", $account->name));
			
			$url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json?count=150&tweet_mode=extended';
			
			$max_id = $db->GetOneMaster(sprintf("SELECT MAX(CAST(twitter_id as unsigned)) FROM twitter_message WHERE connected_account_id = %d", $account->id));
			
			if($max_id)
				$url .= sprintf("&since_id=%s", $max_id);
			
			$request = new Request('GET', $url);
			$request_options = [];
			$error = null;
			
			if(false == ($account->authenticateHttpRequest($request, $request_options, [CerberusContexts::CONTEXT_APPLICATION,0])))
				continue;
			
			$response = DevblocksPlatform::services()->http()->sendRequest($request, $request_options, $error);
			
			$json = DevblocksPlatform::services()->http()->getResponseAsJson($response);
			
			foreach($json as $message) {
				$fields = [
					DAO_TwitterMessage::CONNECTED_ACCOUNT_ID => $account->id,
					DAO_TwitterMessage::TWITTER_ID => $message['id_str'],
					DAO_TwitterMessage::TWITTER_USER_ID => $message['user']['id_str'],
					DAO_TwitterMessage::CREATED_DATE => strtotime($message['created_at']),
					DAO_TwitterMessage::IS_CLOSED => 0,
					DAO_TwitterMessage::USER_NAME => $message['user']['name'],
					DAO_TwitterMessage::USER_SCREEN_NAME => $message['user']['screen_name'],
					DAO_TwitterMessage::USER_PROFILE_IMAGE_URL => $message['user']['profile_image_url_https'],
					DAO_TwitterMessage::USER_FOLLOWERS_COUNT => $message['user']['followers_count'],
					DAO_TwitterMessage::CONTENT => $message['full_text'],
				];
				
				$tweet_id = DAO_TwitterMessage::create($fields);
				
				$logger->info(sprintf("Saved mention #%d from %s", $tweet_id, $message['user']['screen_name']));
			}
		}
		
		$logger->info("Finished");
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->cache_lifetime = "0";
		
		$sync_account_ids = DevblocksPlatform::getPluginSetting('wgm.twitter', 'sync_account_ids_json', [], true);
		
		if(is_array($sync_account_ids) && !empty($sync_account_ids)) {
			$sync_accounts = DAO_ConnectedAccount::getIds($sync_account_ids);
			$tpl->assign('sync_accounts', $sync_accounts);
		}
		
		// Template
		
		$tpl->display('devblocks:wgm.twitter::setup/cron_setup.tpl');
	}
	
	public function saveConfiguration() {
		try {
			@$sync_account_ids = DevblocksPlatform::importGPC($_POST['sync_account_ids'],'array',[]);
			
			$sync_account_ids = DevblocksPlatform::sanitizeArray($sync_account_ids, 'int');
			DevblocksPlatform::setPluginSetting('wgm.twitter', 'sync_account_ids_json', $sync_account_ids, true);
			
			//echo json_encode(array('status'=>true,'message'=>'Saved!'));
			return;
			
		} catch (Exception $e) {
			//echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};