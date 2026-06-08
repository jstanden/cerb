<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class ChCoreEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		// Cerb Workflow
		switch($event->id) {
			case 'comment.create':
				$this->_handleCommentCreate($event);
				break;
				
			case 'context.update':
				$this->_handleContextUpdate($event);
				break;
				
			case 'context.delete':
				$this->_handleContextDelete($event);
				break;
				
			case 'context_link.set':
				$this->_handleContextLinkSet($event);
				break;
				
			case 'cron.heartbeat':
				$this->_handleCronHeartbeat($event);
				break;
				
			case 'cron.maint':
				$this->_handleCronMaint($event);
				break;
		}
	}
	
	private function _handleContextLinkSet($event) {
		$from_context = $event->params['from_context'] ?? null;
		$from_context_id = $event->params['from_context_id'] ?? null;
		$to_context = $event->params['to_context'] ?? null;
		$to_context_id = $event->params['to_context_id'] ?? null;
		
		if($to_context == Context_ProjectBoardColumn::ID) {
			if(false == ($to_column = DAO_ProjectBoardColumn::get($to_context_id)))
				return;
			
			$to_column->runDropActionsForCard($from_context, $from_context_id);
		}
	}
	
	private function _handleContextUpdate($event) {
		$context = $event->params['context'] ?? null;
		$context_ids = $event->params['context_ids'] ?? null;
		
		if(
			DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')
			&& class_exists('DAO_ContextScheduledBehavior')
		) {
			DAO_ContextScheduledBehavior::updateRelativeSchedules($context, $context_ids);
		}
	}
	
	private function _handleContextDelete($event) {
		$context = $event->params['context'] ?? null;
		$context_ids = $event->params['context_ids'] ?? null;
		
		if(empty($context))
			return;
		
		if(!is_array($context_ids) || empty($context_ids))
			return;
		
		// Core
		DAO_Attachment::deleteLinks($context, $context_ids);
		DAO_Calendar::deleteByContext($context, $context_ids);
		DAO_Comment::deleteByContext($context, $context_ids);
		DAO_ContextActivityLog::deleteByContext($context, $context_ids);
		DAO_ContextAlias::delete($context, $context_ids);
		DAO_ContextAvatar::deleteByContext($context, $context_ids);
		DAO_ContextLink::delete($context, $context_ids);
		DAO_ContextMergeHistory::deleteByContextIds($context, $context_ids);
		DAO_CustomFieldset::deleteByOwner($context, $context_ids);
		DAO_CustomFieldset::removeByContextIds($context, $context_ids, false);
		DAO_CustomFieldValue::deleteByContextIds($context, $context_ids);
		DAO_EmailSignature::deleteByOwner($context, $context_ids);
		DAO_MailHtmlTemplate::deleteByOwner($context, $context_ids);
		DAO_Notification::deleteByContext($context, $context_ids);
		DAO_Snippet::deleteByOwner($context, $context_ids);
		DAO_WorkspacePage::deleteByOwner($context, $context_ids);
	}
	
	private function _handleCronMaint($event) {
		DevblocksPlatform::services()->queue()->maint();
		
		DAO_AutomationLog::maint();
		DAO_ConfirmationCode::maint();
		DAO_ExplorerSet::maint();
		DAO_MailDeliveryLog::maint();
		DAO_MailInboundLog::maint();
		DAO_OAuthToken::maint();
		DAO_Ticket::maint();
		DAO_Worker::maint();
		DAO_Notification::maint();
		DAO_Attachment::maint();
		DAO_WorkerViewModel::flush();

		DevblocksPlatform::services()->metrics()->maint();
	}
	
	private function _handleCronHeartbeat($event) {
		$this->_handleCronHeartbeatMetrics();
		$this->_handleCronHeartbeatReopenTickets();
		$this->_handleCronHeartbeatReopenTasks();
		DAO_AutomationContinuation::maint();
		DAO_AutomationDatastore::maint();
		DAO_AutomationResource::maint();
		DAO_DevblocksRegistry::maint();
		DAO_MessageHtmlCache::maint();
		Cerb_DevblocksSessionHandler::maint();
	}
	
	private function _handleCronHeartbeatMetrics() {
		$metrics = DevblocksPlatform::services()->metrics();
		
		// Active workers
		
		$results = Cerb_DevblocksSessionHandler::getLoggedInSeats();
		
		if (is_array($results)) {
			foreach ($results as $row) {
				$metrics->increment('cerb.workers.active', 1, ['worker_id' => $row['user_id']]);
			}
		}

		$this->_handleCronHeartbeatMetricsTicket();
		$this->_handleCronHeartbeatMetricsQueue();
	}

	private function _handleCronHeartbeatMetricsTicket() {
		$metrics = DevblocksPlatform::services()->metrics();
		$registry = DevblocksPlatform::services()->registry();
		$db = DevblocksPlatform::services()->database();
		
		// ============================
		// Open tickets by group/bucket
		
		$registry_key = 'metrics.cerb.tickets.open.last';
		
		$last_ts = $registry->get($registry_key, DevblocksRegistryEntry::TYPE_NUMBER, 0);
		
		// If we last persisted this within 15 mins (but non-zero), abort
		if ($last_ts && (time() - $last_ts) < 900)
			return;
		
		$results = $db->GetArrayReader("SELECT COUNT(id) AS hits, group_id, bucket_id FROM ticket WHERE status_id = 0 GROUP BY group_id, bucket_id");
		
		if (is_array($results)) {
			foreach ($results as $row) {
				$metrics->increment('cerb.tickets.open', $row['hits'], ['group_id' => $row['group_id'], 'bucket_id' => $row['bucket_id']]);
			}
		}
		
		$registry->set($registry_key, time(), DevblocksRegistryEntry::TYPE_NUMBER);
	}

	private function _handleCronHeartbeatMetricsQueue() {
		$metrics = DevblocksPlatform::services()->metrics();
		$registry = DevblocksPlatform::services()->registry();
		$db = DevblocksPlatform::services()->database();

		// =====================================
		// Open/in-flight queue messages by queue, job, and status

		$registry_key = 'metrics.cerb.queue.messages.open.last';

		$last_ts = $registry->get($registry_key, DevblocksRegistryEntry::TYPE_NUMBER, 0);
		
		// If we last persisted this within 10 mins (but non-zero), abort
		if ($last_ts && (time() - $last_ts) < 600)
			return;

		// Count ready (status 0, available now) and in-flight (status 1) messages.
		// Future-scheduled messages (available_at > now) are excluded from the open count.
		$results = $db->GetArrayReader("SELECT queue_id, job_id, status_id, COUNT(*) AS hits FROM queue_message WHERE status_id IN (0,1) AND available_at < UNIX_TIMESTAMP() GROUP BY queue_id, job_id, status_id");
		
		if (is_array($results)) {
			foreach ($results as $row) {
				$metrics->increment('cerb.queue.messages.open', intval($row['hits']), ['queue_id' => intval($row['queue_id']), 'job_id' => intval($row['job_id']), 'status_id' => intval($row['status_id'])]);
			}
		}

		$registry->set($registry_key, time(), DevblocksRegistryEntry::TYPE_NUMBER);
	}

	private function _handleCronHeartbeatReopenTickets() : void {
		// Re-open any conversations past their reopen date
		try {
			list($results,) = DAO_Ticket::search(
				[],
				[
					SearchFields_Ticket::TICKET_STATUS_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID, 'in', [Model_Ticket::STATUS_WAITING, Model_Ticket::STATUS_CLOSED]),
					SearchFields_Ticket::TICKET_REOPEN_AT => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_REOPEN_AT, DevblocksSearchCriteria::OPER_BETWEEN, [1, time()]),
				],
				200,
				0,
				null,
				true,
				false
			);
			
		} catch (Exception_DevblocksDatabaseQueryTimeout) {
			$results = [];
		}
		
		// Only update records with fields that changed
		
		if(!is_array($results))
			return;
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			DAO_Ticket::REOPEN_AT => 0
		];
		
		$models = DAO_Ticket::getIds(array_keys($results));
		
		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
	}
	
	private function _handleCronHeartbeatReopenTasks() {
		// Re-open any conversations past their reopen date
		list($results,) = DAO_Task::search(
			array(),
			array(
				SearchFields_Task::STATUS_ID => new DevblocksSearchCriteria(SearchFields_Task::STATUS_ID,'in',array(1,2)),
				array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_Task::REOPEN_AT,DevblocksSearchCriteria::OPER_GT,0),
					new DevblocksSearchCriteria(SearchFields_Task::REOPEN_AT,DevblocksSearchCriteria::OPER_LT,time()),
				),
			),
			200,
			0,
			DAO_Task::ID,
			true,
			false
		);
		
		$fields = array(
			DAO_Task::STATUS_ID => 0,
			DAO_Task::REOPEN_AT => 0
		);
		
		// Only update records with fields that changed
		$models = DAO_Task::getIds(array_keys($results));
		
		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Task::update($model_id, $update_fields);
		}
	}
	
	private function _handleCommentCreate($event) { /* @var $event Model_DevblocksEvent */
		$fields = $event->params['fields'] ?? null;
		
		if(!isset($fields[DAO_Comment::CONTEXT]) || !isset($fields[DAO_Comment::CONTEXT_ID]))
			return;
			
		// Context-specific behavior for comments
		switch($fields[DAO_Comment::CONTEXT]) {
			case CerberusContexts::CONTEXT_TASK:
				$update_fields = array(
					DAO_Task::UPDATED_DATE => time(),
				);
				DAO_Task::update($fields[DAO_Comment::CONTEXT_ID], $update_fields);
				break;
				
			case CerberusContexts::CONTEXT_TICKET:
				$update_fields = array(
					DAO_Ticket::UPDATED_DATE => time(),
				);
				DAO_Ticket::update($fields[DAO_Comment::CONTEXT_ID], $update_fields, false);
				break;
		}
	}
	
};
