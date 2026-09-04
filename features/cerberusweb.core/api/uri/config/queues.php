<?php /** @noinspection PhpUnused */
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

/**
 * Setup > Configure > Queues -- what the concurrency pool is divided into, and what is running in it
 * right now.
 *
 * Setup > Subscription answers "how much capacity did we buy"; this page answers "what is it doing".
 * They draw the lane split from the same partial so the two cannot disagree about the shape of it.
 */
class PageSection_SetupQueues extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'queues');
		
		$queue_service = DevblocksPlatform::services()->queue();
		$llm_service = DevblocksPlatform::services()->llm();
		
		// The effective pool, not the license's raw number: APP_QUEUE_CONCURRENCY_SLOTS clamps it, and on
		// Cloud the constant replaces it outright. The page has to show what actually gets enforced.
		$slots = $queue_service->getMaxConcurrencySlots();
		$slots_licensed = $queue_service->getMaxConcurrencySlots(with_soft_cap: false);
		
		$tpl->assign('max_concurrency_slots', $slots);
		$tpl->assign('max_concurrency_slots_licensed', $slots_licensed);
		
		$agent_slots = count(_DevblocksQueueService::getLaneSlots($slots, QueueLane::Slow));
		
		$tpl->assign('agent_slots', $agent_slots);
		$tpl->assign('turns_per_slot', $llm_service->getMaxConcurrentTurns());
		
		$tpl->assign('lane_spans_fast_json', json_encode(_DevblocksQueueService::getLaneSpans($slots, QueueLane::Fast)));
		$tpl->assign('lane_spans_slow_json', json_encode(_DevblocksQueueService::getLaneSpans($slots, QueueLane::Slow)));
		
		// Seed the occupancy row from the same shape the poll returns, so the page paints once with real
		// state instead of drawing an empty pool and correcting itself a few seconds later.
		$tpl->assign('slot_usage_json', json_encode($this->_getSlotUsagePayload()));
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/queues/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'usageJson':
					return $this->_configAction_usageJson();
			}
		}
		return false;
	}
	
	/**
	 * Which slots are busy, in the shape the page's occupancy row consumes.
	 *
	 * `unknown` is a THIRD state, not a fancy zero: a failed lock read is indistinguishable from an idle
	 * pool in the numbers, and quietly drawing an empty pool would be the most reassuring possible lie.
	 */
	private function _getSlotUsagePayload() : array {
		$queue_service = DevblocksPlatform::services()->queue();
		
		$total = $queue_service->getMaxConcurrencySlots();
		$usage = $queue_service->getConcurrencySlotUsage();
		
		if(is_null($usage)) {
			return [
				'unknown' => true,
				'total' => $total,
				'used' => 0,
				'busy' => [],
			];
		}
		
		return [
			'unknown' => false,
			'total' => $total,
			'used' => count(array_filter($usage)),
			// Slot numbers rather than a bool per slot: the chart wants the busy ones, and this stays
			// small on a big pool.
			'busy' => array_values(array_keys(array_filter($usage))),
		];
	}
	
	private function _configAction_usageJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		echo json_encode($this->_getSlotUsagePayload());
		return true;
	}
}
