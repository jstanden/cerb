<?php
class ChRest_Tickets extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(empty($stack) && is_numeric($action)) {
			$this->getId(intval($action));
			
		} else {
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Updating a single ticket ID?
		if(is_numeric($action)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'requester':
					$this->putRequester(intval($id));
					break;
					
				default:
					$this->putId(intval($id));
					break;
			}
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		if(is_numeric($action) && !empty($stack)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'comment':
					$this->postComment($id);
					break;
			}
			
		} else {
			switch($action) {
				case 'compose':
					$this->postCompose();
					break;
					
				case 'merge':
					$this->_postMerge();
					break;
					
				case 'reply':
					$this->postReply();
					break;
					
				case 'search':
					$this->postSearch();
					break;
					
				case 'split':
					$this->_postSplit();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		@$action = array_shift($stack);

		// Delete a single ID?
		if(is_numeric($action)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'requester':
					$this->deleteRequester(intval($id));
					break;
				default:
					$this->deleteId(intval($id));
					break;
			}
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $model, $labels, $values, null, true);

		unset($values['initial_message_content']);
		unset($values['latest_message_content']);
		
		return $values;
	}
	
	private function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// Internally search (checks ACL via groups)
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket id '%d'", $id));
	}
	
	private function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		$workers = DAO_Worker::getAll();
		
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string', '');
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string', '');
		@$status_id = DevblocksPlatform::importGPC($_REQUEST['status_id'],'string','');
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'string', '');
		@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'],'string', '');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to modify tickets in this group.');
		
		$fields = array(
//			DAO_Ticket::UPDATED_DATE => time(),
		);
		
		$custom_fields = []; // [TODO]
		
		if(0 != strlen($subject))
			$fields[DAO_Ticket::SUBJECT] = $subject;
			
		// Group + Bucket
		
		if(0 != strlen($group_id) || 0 != strlen($bucket_id)) {
			$group_id = intval($group_id);
			$bucket_id = intval($bucket_id);
			
			if(empty($group_id))
				$this->error(self::ERRNO_ACL, "The 'group_id' field is required.");
			
			if(false == ($group = DAO_Group::get($group_id)))
				$this->error(self::ERRNO_ACL, "The given 'group_id' is invalid.");

			if(empty($bucket_id)) {
				$bucket = $group->getDefaultBucket();
				$bucket_id = $bucket->id;
				
			} else if(false == ($bucket = DAO_Bucket::get($bucket_id))) {
				$this->error(self::ERRNO_ACL, "The given 'bucket_id' is invalid.");
			}
			
			if(isset($bucket) && $bucket->group_id != $group_id)
				$group_id = $bucket->group_id;
				
			$fields[DAO_Ticket::GROUP_ID] = intval($group->id);
			$fields[DAO_Ticket::BUCKET_ID] = intval($bucket->id);
		}
		
		// Owner
		
		if(0 != strlen($owner_id) || 0 != strlen($owner_id)) {
			$owner_id = intval($owner_id);
			
			if(!empty($owner_id)) {
				if(false == ($worker = DAO_Worker::get($owner_id)))
					$this->error(self::ERRNO_ACL, "The given 'owner_id' is invalid.");
				
				if($worker->is_disabled)
					$this->error(self::ERRNO_ACL, "The given 'owner_id' is disabled.");
			}
			
			$fields[DAO_Ticket::OWNER_ID] = $owner_id;
		}
		
		// Org
		
		if(0 != strlen($org_id) || 0 != strlen($org_id)) {
			$org_id = intval($org_id);
			
			if(!empty($org_id)) {
				if(false == ($org = DAO_ContactOrg::get($org_id)))
					$this->error(self::ERRNO_ACL, "The given 'org_id' is invalid.");
			}
			
			$fields[DAO_Ticket::ORG_ID] = $org_id;
		}
		
		// Waiting
		
		if(0 != strlen($status_id)) {
			switch($status_id) {
				case Model_Ticket::STATUS_WAITING:
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_WAITING;
					break;
					
				case Model_Ticket::STATUS_CLOSED:
					// ACL
					if(!$worker->hasPriv('core.ticket.actions.close'))
						$this->error(self::ERRNO_ACL, 'Access denied to close tickets.');
					
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_CLOSED;
					break;
					
				case Model_Ticket::STATUS_DELETED:
					// ACL
					if(!$worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_TICKET)))
						$this->error(self::ERRNO_ACL, 'Access denied to delete tickets.');
		
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_DELETED;
					break;
					
				default:
				case Model_Ticket::STATUS_OPEN:
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_OPEN;
					break;
			}
		}
		
		// Only update fields that changed
		$fields = Cerb_ORMHelper::uniqueFields($fields, $ticket);
		
		// Update
		if(!empty($fields))
			DAO_Ticket::update($id, $fields);
			
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $id, $customfields, true, true, true);

		$this->getId($id);
	}
	
	public function putRequester($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to modify tickets in this group.');

		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
			
		if(!empty($email))
			DAO_Ticket::createRequester($email, $id);
		
		$this->getId($id);
	}
	
	private function deleteId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_TICKET)))
			$this->error(self::ERRNO_ACL, 'Access denied to delete tickets.');

		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to delete tickets in this group.');

		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
		);
		
		// Only update fields that changed
		$fields = Cerb_ORMHelper::uniqueFields($fields, $ticket);
		
		if(!empty($fields))
			DAO_Ticket::update($ticket->id, $fields);
		
		$result = array('id'=> $id);
		
		$this->success($result);
	}
	
	public function deleteRequester($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to modify tickets in this group.');

		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		if(!empty($email)) {
			if(null != ($email = DAO_Address::lookupAddress($email, false))) {
				DAO_Ticket::deleteRequester($id, $email->id);
			} else {
				$this->error(self::ERRNO_CUSTOM, $email . ' is not a valid requester on ticket ' . $id);
			}
		}
		
		$this->getId($id);
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'bucket_id' => DAO_Ticket::BUCKET_ID,
				'group_id' => DAO_Ticket::GROUP_ID,
				'id' => DAO_Ticket::ID,
				'status_id' => DAO_Ticket::STATUS_ID,
				'mask' => DAO_Ticket::MASK,
				'org_id' => DAO_Ticket::ORG_ID,
				'owner_id' => DAO_Ticket::OWNER_ID,
				'subject' => DAO_Ticket::SUBJECT,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Ticket::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_Ticket::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_Ticket::VIRTUAL_WATCHERS,
				
				'first_wrote' => SearchFields_Ticket::TICKET_FIRST_WROTE_ID,
				'group' => SearchFields_Ticket::TICKET_GROUP_ID,
				'last_wrote' => SearchFields_Ticket::TICKET_LAST_WROTE_ID,
				'org_name' => SearchFields_Ticket::TICKET_ORG_ID,
				'owner' => SearchFields_Ticket::TICKET_OWNER_ID,
				'spam_training' => SearchFields_Ticket::TICKET_SPAM_TRAINING,
				'status' => SearchFields_Ticket::VIRTUAL_STATUS,
				'subject' => SearchFields_Ticket::TICKET_SUBJECT,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_TICKET);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'bucket_id' => SearchFields_Ticket::TICKET_BUCKET_ID,
				'content' => SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,
				'created' => SearchFields_Ticket::TICKET_CREATED_DATE,
				'first_wrote' => SearchFields_Ticket::TICKET_FIRST_WROTE_ID,
				'group' => SearchFields_Ticket::TICKET_GROUP_ID,
				'group_id' => SearchFields_Ticket::TICKET_GROUP_ID,
				'id' => SearchFields_Ticket::TICKET_ID,
				'status_id' => SearchFields_Ticket::TICKET_STATUS_ID,
				'last_wrote' => SearchFields_Ticket::TICKET_LAST_WROTE_ID,
				'mask' => SearchFields_Ticket::TICKET_MASK,
				'org_id' => SearchFields_Ticket::TICKET_ORG_ID,
				'requester' => SearchFields_Ticket::REQUESTER_ADDRESS,
				'subject' => SearchFields_Ticket::TICKET_SUBJECT,
				'updated' => SearchFields_Ticket::TICKET_UPDATED_DATE,
					
				'links' => SearchFields_Ticket::VIRTUAL_CONTEXT_LINK,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function search($filters=array(), $sortToken='updated', $sortAsc=0, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$worker = CerberusApplication::getActiveWorker();

		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;

		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_TICKET,
			$params,
			$limit,
			$page,
			$sortBy,
			$sortAsc
		);
		
		if(!empty($query) && $view instanceof IAbstractView_QuickSearch)
			$view->addParamsWithQuickSearch($query, true);

		// If we're given explicit filters, merge them in to our quick search
		if(!empty($filters)) {
			if(!empty($query))
				$params = $view->getParams(false);
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_TICKET);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$view->addParam(
				new DevblocksSearchCriteria(
					SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,
					'=',
					$worker->id
				),
				SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER
			);
		}
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_TICKET, array_keys($results));
			
			unset($results);
			
			if(is_array($models))
			foreach($models as $id => $model) {
				$values = $this->getContext($model);
				$objects[$id] = $values;
			}
		}
		
		$container = array();
		
		if($show_results) {
			$container['results'] = $objects;
			$container['total'] = $total;
			$container['count'] = count($objects);
			$container['page'] = $page;
			$container['limit'] = $limit;
		}
		
		if(!empty($subtotals)) {
			$container['subtotals'] = $subtotal_data;
		}
		
		return $container;
	}
	
	private function postSearch() {
		$worker = CerberusApplication::getActiveWorker();

		// ACL
// 		if(!$worker->hasPriv('core.mail.search'))
// 			$this->error(self::ERRNO_ACL, 'Access denied to search tickets.');

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	private function _handlePostCompose() {
		$worker = CerberusApplication::getActiveWorker();
		
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
		@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'],'integer',0);
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
		@$cc = DevblocksPlatform::importGPC($_REQUEST['cc'],'string','');
		@$bcc = DevblocksPlatform::importGPC($_REQUEST['bcc'],'string','');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$content_format = DevblocksPlatform::importGPC($_REQUEST['content_format'],'string','');
		@$html_template_id = DevblocksPlatform::importGPC($_REQUEST['html_template_id'],'integer',0);
		
		@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_id'],'array',array());
		
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$reopen_at = DevblocksPlatform::importGPC($_REQUEST['reopen_at'],'string','');
		
		@$send_at = DevblocksPlatform::importGPC($_REQUEST['send_at'],'string','');
		@$dont_send = DevblocksPlatform::importGPC($_REQUEST['dont_send'],'integer',0);
		
		if(empty($subject))
			$this->error(self::ERRNO_CUSTOM, "The 'subject' parameter is required");
		
		if(empty($content))
			$this->error(self::ERRNO_CUSTOM, "The 'content' parameter is required");

		if(!empty($file_ids))
			$file_ids = DevblocksPlatform::sanitizeArray($file_ids, 'integer', array('nonzero','unique'));
		
		if(empty($group_id))
			$this->error(self::ERRNO_CUSTOM, "The 'group_id' parameter is required");
		
		if(false == ($group = DAO_Group::get($group_id)))
			$this->error(self::ERRNO_CUSTOM, "The given 'group_id' parameter is invalid");
		
		if(!empty($bucket_id) && false == ($bucket = DAO_Bucket::get($bucket_id)))
			$this->error(self::ERRNO_CUSTOM, "The given 'bucket_id' parameter is invalid");

		if(!empty($html_template_id) && false == ($html_template = DAO_MailHtmlTemplate::get($html_template_id)))
			$this->error(self::ERRNO_CUSTOM, "The given 'html_template_id' parameter is invalid");
		
		if(isset($bucket) && $bucket->group_id != $group_id)
			$group_id = $bucket->group_id;
		
		$properties = array(
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'org_id' => $org_id,
			'owner_id' => $owner_id,
			'to' => $to,
			'subject' => $subject,
			'content' => $content,
			'worker_id' => $worker->id,
		);

		if(!empty($cc))
			$properties['cc'] = $cc;
		
		if(!empty($bcc))
			$properties['bcc'] = $bcc;
		
		if(!empty($status) && in_array($status, array(0,1,2,3)))
			$properties['status_id'] = $status;
		
		if(!empty($reopen_at))
			$properties['ticket_reopen'] = $reopen_at;
		
		if(!empty($send_at))
			$properties['send_at'] = $send_at;
		
		if(!empty($file_ids)) {
			$properties['link_forward_files'] = true;
			$properties['forward_files'] = $file_ids;
		}
		
		if(!empty($content_format) && in_array($content_format, array('markdown','parsedown','html')))
			$properties['content_format'] = 'parsedown';
		
		if(isset($html_template))
			$properties['html_template_id'] = $html_template->id;
		
		if(!empty($dont_send))
			$properties['dont_send'] = $dont_send ? 1 : 0;
		
		// Handle custom fields
		$custom_fields = $this->_handleCustomFields($_POST);
		
		if($custom_fields)
			$properties['custom_fields'] = $custom_fields;
		
		if(false == ($ticket_id = CerberusMail::compose($properties)))
			$this->error(self::ERRNO_CUSTOM, "Failed to create a new message.");
		
		return $ticket_id;
	}
	
	private function postCompose() {
		$worker = CerberusApplication::getActiveWorker();

		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.ticket.create'))
			$this->error(self::ERRNO_ACL, 'Access denied to compose mail.');
		
		$ticket_id = $this->_handlePostCompose();
		$this->getId($ticket_id);
	}
	
	private function _handlePostReply() {
		$worker = CerberusApplication::getActiveWorker();
		
		/*
		'headers'
		*/
		
		// Required
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['message_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');

		// Optional
		@$bcc = DevblocksPlatform::importGPC($_REQUEST['bcc'],'string','');
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string',null);
		@$cc = DevblocksPlatform::importGPC($_REQUEST['cc'],'string','');
		@$content_format = DevblocksPlatform::importGPC($_REQUEST['content_format'],'string','');
		@$dont_keep_copy = DevblocksPlatform::importGPC($_REQUEST['dont_keep_copy'],'integer',0);
		@$dont_send = DevblocksPlatform::importGPC($_REQUEST['dont_send'],'integer',0);
		@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_id'],'array',array());
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$is_autoreply = DevblocksPlatform::importGPC($_REQUEST['is_autoreply'],'integer',0);
		@$is_broadcast = DevblocksPlatform::importGPC($_REQUEST['is_broadcast'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0);
		@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'],'string',null);
		@$reopen_at = DevblocksPlatform::importGPC($_REQUEST['reopen_at'],'string','');
		@$send_at = DevblocksPlatform::importGPC($_REQUEST['send_at'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$html_template_id = DevblocksPlatform::importGPC($_REQUEST['html_template_id'],'integer',0);
		
		if(empty($content))
			$this->error(self::ERRNO_CUSTOM, "The 'content' parameter is required");
		
		if(empty($message_id))
			$this->error(self::ERRNO_CUSTOM, "The 'message_id' parameter is required");
		
		if(false == ($message = DAO_Message::get($message_id)))
			$this->error(self::ERRNO_CUSTOM, "The given 'message_id' is invalid");
		
		if(false == ($ticket = $message->getTicket()))
			$this->error(self::ERRNO_CUSTOM, "The given 'ticket_id' is invalid");
		
		if(false === Context_Ticket::isWriteableByActor($ticket, $worker))
			$this->error(self::ERRNO_CUSTOM, "You do not have write access to this ticket");
		
		if(!empty($file_ids))
			$file_ids = DevblocksPlatform::sanitizeArray($file_ids, 'integer', array('nonzero','unique'));
		
		if(!empty($html_template_id) && false == ($html_template = DAO_MailHtmlTemplate::get($html_template_id)))
			$this->error(self::ERRNO_CUSTOM, "The given 'html_template_id' parameter is invalid");
		
		$properties = array(
			'message_id' => $message_id,
			'content' => $content,
		);

		// [TODO] Tell the activity log we're impersonating?
		if($worker->is_superuser && !empty($worker_id)) {
			if(false != ($sender_worker = DAO_Worker::get($worker_id)))
				$properties['worker_id'] = $sender_worker->id;
		}
		
		// Default to current worker
		if(!isset($properties['worker_id']))
			$properties['worker_id'] = $worker->id;
		
		// Bucket
		if(!$bucket_id && !$group_id)
			$bucket_id = $ticket->bucket_id;
			
		if($bucket_id && false != ($bucket = DAO_Bucket::get($bucket_id))) {
			$bucket_id = $bucket->id;
			$properties['bucket_id'] = $bucket->id;
			
			// Always set the group_id in unison with the bucket_id
			$group_id = $bucket->group_id;
			$properties['group_id'] = $group_id;
		}
		
		// Group inbox
		if(!isset($properties['group_id']) && $ticket->group_id && false != ($group = $ticket->getGroup())) {
			$properties['group_id'] = $group->id;
			$properties['bucket_id'] = $group->getDefaultBucket()->id;
		}
		
		// Owner
		if(strlen($owner_id) > 0 && (empty($owner_id) || false != ($owner = DAO_Worker::get($owner_id)))) {
			if(isset($owner))
				$properties['owner_id'] = $owner->id;
			else
				$properties['owner_id'] = 0;
		}
		
		if(!empty($subject))
			$properties['subject'] = $subject;
		
		if(!empty($to)) {
			$properties['to'] = $to;
		} else {
			if(false != ($recipients = $ticket->getRequesters()))
				$properties['to'] = implode(',', array_column($recipients, 'email'));
		}
		
		if(!empty($cc))
			$properties['cc'] = $cc;
		
		if(!empty($bcc))
			$properties['bcc'] = $bcc;
		
		if(!empty($content_format) && in_array($content_format, array('markdown','parsedown','html')))
			$properties['content_format'] = 'parsedown';
		
		if(isset($html_template))
			$properties['html_template_id'] = $html_template->id;
		
		if(in_array($status, [0,1,2,3]))
			$properties['status_id'] = $status;
		
		if(!empty($reopen_at))
			$properties['ticket_reopen'] = $reopen_at;
		
		if($send_at)
			$properties['send_at'] = $send_at;
		
		//Files
		
		if(!empty($file_ids)) {
			$properties['link_forward_files'] = true;
			$properties['forward_files'] = $file_ids;
		}

		// Flags
		
		if(!empty($dont_keep_copy))
			$properties['dont_keep_copy'] = $dont_keep_copy ? 1 : 0;
		
		if(!empty($dont_send))
			$properties['dont_send'] = $dont_send ? 1 : 0;
		
		if(!empty($is_autoreply))
			$properties['is_autoreply'] = $is_autoreply ? 1 : 0;
		
		if(!empty($is_broadcast))
			$properties['is_broadcast'] = $is_broadcast ? 1 : 0;
		
		if(!empty($is_forward))
			$properties['is_forward'] = $is_forward ? 1 : 0;
		
		// Custom fields
		
		$custom_fields = $this->_handleCustomFields($_POST);
		
		if(!empty($custom_fields))
			$properties['custom_fields'] = $custom_fields;

		// Send the message
		
		if(false == ($message_id = CerberusMail::sendTicketMessage($properties)))
			$this->error(self::ERRNO_CUSTOM, "Failed to create a reply message.");
		
		return $message->ticket_id;
	}
	
	private function postReply() {
		$worker = CerberusApplication::getActiveWorker();

		$ticket_id = $this->_handlePostReply();
		$this->getId($ticket_id);
	}
	
	private function postComment($id) {
		$worker = CerberusApplication::getActiveWorker();

		@$comment = DevblocksPlatform::importGPC($_POST['comment'],'string','');
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to delete tickets in this group.');
		
		// Worker address exists
		if(null === ($address = $worker->getEmailModel()))
			$this->error(self::ERRNO_CUSTOM, 'Your worker does not have a valid e-mail address.');
		
		// Required fields
		if(empty($comment))
			$this->error(self::ERRNO_CUSTOM, "The 'comment' field is required.");
			
		$fields = array(
			DAO_Comment::CREATED => time(),
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
			DAO_Comment::CONTEXT_ID => $ticket->id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::COMMENT => $comment,
		);
		$comment_id = DAO_Comment::create($fields);

		$this->success(array(
			'ticket_id' => $ticket->id,
			'comment_id' => $comment_id,
		));
	}
	
	private function _postMerge() {
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_ids'],'array:int',[]);
		
		$worker = CerberusApplication::getActiveWorker();
		$eventMgr = DevblocksPlatform::services()->event();
		
		if(false == ($tickets = DAO_Ticket::getIds($ticket_ids)))
			$this->error(self::ERRNO_PARAM_INVALID, "Failed to load the given ticket IDs.");
		
		if(count($tickets) < 2)
			$this->error(self::ERRNO_PARAM_INVALID, "At least two `ticket_ids[]` parameters are required.");
		
		if(!Context_Ticket::isWriteableByActor($tickets, $worker))
			$this->error(self::ERRNO_ACL, "You do not have permission to modify these tickets.");
		
		$from_ids = array_keys($tickets);
		sort($from_ids); // oldest first
		$to_id = array_shift($from_ids); // merge into oldest
		
		if(false == DAO_Ticket::mergeIds($from_ids, $to_id))
			$this->error(self::ERRNO_PARAM_INVALID, "Failed to merge tickets.");
		
		foreach($from_ids as $from_id) {
			/*
			 * Log activity (context.merge)
			 */
			$entry = [
				//{{actor}} merged {{context_label}} {{source}} into {{context_label}} {{target}}
				'message' => 'activities.record.merge',
				'variables' => [
					'context' => CerberusContexts::CONTEXT_TICKET,
					'context_label' => 'ticket',
					'source' => sprintf("[%s] %s", $tickets[$from_id]->mask, $tickets[$from_id]->subject),
					'target' => sprintf("[%s] %s", $tickets[$to_id]->mask, $tickets[$to_id]->subject),
					],
				'urls' => [
					'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TICKET, $to_id, DevblocksPlatform::strToPermalink($tickets[$to_id]->subject)),
					],
			];
			CerberusContexts::logActivity('record.merge', CerberusContexts::CONTEXT_TICKET, $to_id, $entry);
		}
		
		// Fire a merge event for plugins
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'record.merge',
				array(
					'context' => CerberusContexts::CONTEXT_TICKET,
					'target_id' => $to_id,
					'source_ids' => $from_ids,
				)
			)
		);
		
		DAO_Ticket::delete($from_ids);
		
		$this->getId($to_id);
	}
	
	private function _postSplit() {
		$worker = CerberusApplication::getActiveWorker();
		
		@$message_id = DevblocksPlatform::importGPC($_POST['message_id'],'integer',0);
		
		if(!$message_id)
			$this->error(self::ERRNO_PARAM_INVALID, "The 'message_id' is required.");
		
		if(false == ($message = DAO_Message::get($message_id)))
			$this->error(self::ERRNO_PARAM_INVALID, "The given message ID was not found.");
		
		if(!Context_Message::isWriteableByActor($message, $worker))
			$this->error(self::ERRNO_ACL, "You do not have permission to modify this message.");
		
		if(false == ($results = DAO_Ticket::split($message, $error)))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		$this->getId($results['id']);
	}
};