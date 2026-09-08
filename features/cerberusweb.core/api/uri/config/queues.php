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
		
		// The effective pool: Cloud reads its provisioned constant, self-hosted reads the admin's setting.
		$slots = $queue_service->getMaxConcurrencySlots();
		
		$tpl->assign('max_concurrency_slots', $slots);
		
		$agent_slots = count($queue_service->getConfiguredLaneSlots(QueueLane::Slow));
		
		$tpl->assign('agent_slots', $agent_slots);
		$tpl->assign('turns_per_slot', $llm_service->getMaxConcurrentTurns());
		
		$tpl->assign('lane_spans_fast_json', json_encode($queue_service->getConfiguredLaneSpans(QueueLane::Fast)));
		$tpl->assign('lane_spans_slow_json', json_encode($queue_service->getConfiguredLaneSpans(QueueLane::Slow)));
		
		// The editor's state. Cloud's pool is provisioned rather than bought, so its slot count is shown
		// and not editable; the lane split still is.
		$tpl->assign('lane_reserves', $queue_service->getLaneReserves($slots));
		$tpl->assign('is_licensed', CerberusLicense::getInstance()->isLicensed());
		$tpl->assign('is_slots_provisioned', CerberusApplication::isCerbCloud());
		
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
				case 'saveJson':
					return $this->_configAction_saveJson();
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
	
	/**
	 * Save the pool size and its lane split.
	 *
	 * Both are subscription features, so an unlicensed install is refused here rather than merely being
	 * shown a disabled form -- hiding an input is not enforcement. The lane split is validated against the
	 * pool size being SAVED, not the one currently in effect, or raising slots and re-splitting in one
	 * submit would always fail.
	 */
	private function _configAction_saveJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!CerberusLicense::getInstance()->isLicensed()) {
			echo json_encode(['status' => false, 'error' => 'A subscription is required to change concurrency.']);
			return true;
		}
		
		$queue_service = DevblocksPlatform::services()->queue();
		
		$slots = DevblocksPlatform::importGPC($_POST['slots'] ?? null, 'integer', 0);
		$fast = DevblocksPlatform::importGPC($_POST['lane_fast'] ?? null, 'integer', 0);
		$shared = DevblocksPlatform::importGPC($_POST['lane_shared'] ?? null, 'integer', 0);
		$slow = DevblocksPlatform::importGPC($_POST['lane_slow'] ?? null, 'integer', 0);
		
		// Cloud's pool is provisioned; only the split is the admin's to set. Tested with isCerbCloud() rather
		// than on the constant, so an instance missing it cannot post itself a pool.
		if(CerberusApplication::isCerbCloud())
			$slots = $queue_service->getMaxConcurrencySlots();
		
		if($slots < _DevblocksQueueService::SLOTS_COMMUNITY) {
			echo json_encode(['status' => false, 'error' => sprintf('Slots must be at least %d.', _DevblocksQueueService::SLOTS_COMMUNITY)]);
			return true;
		}
		
		if(!($reserves = _DevblocksQueueService::validateLaneReserves($slots, $fast, $shared, $slow))) {
			echo json_encode(['status' => false, 'error' => sprintf('The lanes must total %d slots, with at least one in each.', $slots)]);
			return true;
		}
		
		if(!CerberusApplication::isCerbCloud())
			DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::CONCURRENCY_SLOTS, $slots);
		
		DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::CONCURRENCY_LANES, $reserves, true);
		
		echo json_encode(['status' => true]);
		return true;
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
