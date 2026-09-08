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

class PageSection_SetupLicense extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'license');

		$tpl->assign('license', CerberusLicense::getInstance());
		
		// On Cloud the plan is provisioned by the platform, so there is no key to add or remove. The
		// template swaps the active-subscription block and drops the form; _configAction_saveJson()
		// refuses the POST regardless, since hiding a form is not enforcement.
		$tpl->assign('is_cerb_cloud', CerberusApplication::isCerbCloud());
		$tpl->assign('cerb_cloud_subdomain', defined('CERB_CLOUD_SUBDOMAIN') ? constant('CERB_CLOUD_SUBDOMAIN') : '');
		$tpl->assign('cerb_cloud_subscriber', defined('CERB_CLOUD_SUBSCRIBER') ? constant('CERB_CLOUD_SUBSCRIBER') : '');
		
		$queue_service = DevblocksPlatform::services()->queue();
		$llm_service = DevblocksPlatform::services()->llm();
		
		$slots = $queue_service->getMaxConcurrencySlots();
		
		$tpl->assign('max_concurrency_slots', $slots);
		
		// Lapsed coverage floors concurrency to Community, and nothing else announces that -- without
		// this the page would just quietly show a smaller number than the customer is paying for.
		$license = CerberusLicense::getInstance();
		$tpl->assign('license_is_expired', !is_null($license->upgrades) && $license->upgrades < time());
		
		// A self-hosted subscription no longer grants a NUMBER -- it lifts the cap, and the admin then sizes
		// the pool on Setup > Configure > Queues. Printing whatever they happen to have set would read as the
		// subscription's allowance, which is the one thing it isn't. Cloud is provisioned, so its number is
		// real; Community's 3 is a real ceiling; a lapsed license falls back to Community and shows that.
		$tpl->assign('is_slots_unlimited', $license->isLicensed() && !CerberusApplication::isCerbCloud());
		
		// Every figure below is DERIVED at render time from the same policy the drain enforces -- no tier
		// table, no prices, nothing that can go stale against cerb.ai.
		//
		// Turns-at-once is a CEILING, not a reservation: only the slow lane runs agent turns and it is
		// shared with background jobs, which is why the panel says so out loud rather than printing the
		// number bare.
		$agent_slots = count($queue_service->getConfiguredLaneSlots(QueueLane::Slow));
		$turns_per_slot = $llm_service->getMaxConcurrentTurns();
		
		$tpl->assign('agent_slots', $agent_slots);
		$tpl->assign('turns_per_slot', $turns_per_slot);
		$tpl->assign('turns_at_once', $agent_slots * $turns_per_slot);
		
		// Without the lane split on the page, `slots x turns per slot` disagrees with `turns at once` and
		// reads as an arithmetic bug: the slots reserved to the fast lane never run an agent turn.
		//
		// Where the two runs cross IS the diagram: those are the slots either kind of work may take.
		$tpl->assign('lane_spans_fast_json', json_encode($queue_service->getConfiguredLaneSpans(QueueLane::Fast)));
		$tpl->assign('lane_spans_slow_json', json_encode($queue_service->getConfiguredLaneSpans(QueueLane::Slow)));
		
		// Seat usage, for a SELF-REPORTED subscription -- nothing here gates anything, and no login is ever
		// refused over it. Distinct workers ACTIVE during a month, averaged over the months on hand: the
		// daily metric tier is kept forever, so an existing install can answer for as far back as it has run.
		// Activity is sampled sign-ins plus outgoing mail, so a headless API integration still registers as
		// the worker it acts as. It undercounts either way, which is the right direction to be wrong for a
		// number a customer reports themselves.
		$seat_months = DAO_MetricValue::getDistinctDimensionCountsByMonth('cerb.workers.active', 12);

		$seats_peak = $seat_months ? max($seat_months) : 0;
		$seat_rows = [];

		foreach($seat_months as $seat_month => $seat_count)
			$seat_rows[] = [
				'label' => date('F Y', strtotime($seat_month . '-01')),
				// One block per seat, and `step` makes the end inclusive -- so [1,24] is 24 blocks, not 23.
				// A month nobody worked emits no span at all rather than a zero-width one.
				'spans' => $seat_count > 0 ? [[1, $seat_count]] : [],
			];

		// A FLOOR of ten cells, not a rounding: a one-seat install against a track of ten reads as one seat,
		// where a bar scaled to its own peak reads as full. Above ten the track is just the peak, since
		// rounding 11 up to 20 would only add empty cells nobody is waiting to fill.
		//
		// The Gantt domain's end is EXCLUSIVE -- one step past the last unit, which is why the component's
		// own example is [1,26] for a 25-slot pool. Passing the seat count itself would drop the last cell.
		$seats_axis_max = max(10, $seats_peak) + 1;

		$tpl->assign('seat_rows_json', json_encode($seat_rows));
		$tpl->assign('seat_rows_count', count($seat_rows));
		$tpl->assign('seats_axis_max', $seats_axis_max);
		$tpl->assign('seat_months_count', count($seat_months));
		$tpl->assign('seats_peak', $seats_peak);

		// Averaged over months PRESENT, not over 12: a young install would otherwise divide a real number by
		// months it did not exist for and under-report itself.
		$tpl->assign('seats_average', $seat_months ? intval(ceil(array_sum($seat_months) / count($seat_months))) : 0);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/license/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'saveJson':
					return $this->_configAction_saveJson();
			}
		}
		return false;
	}
	
	private function _configAction_saveJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			// Not merely hidden in the template: hiding a form is not enforcement. A stored key would do
			// nothing here anyway -- getMaxConcurrencySlots() reads the Cloud constant and never reaches
			// the license on this path.
			if(CerberusApplication::isCerbCloud())
				throw new Exception("Your subscription is managed by Cerb Cloud and can't be changed here.");
			
			$key = DevblocksPlatform::importGPC($_POST['key'] ?? null, 'string','');
			$company = DevblocksPlatform::importGPC($_POST['company'] ?? null, 'string','');
			$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string','');
			$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'integer',0);

			// Deleting license?
			if(!empty($do_delete)) {
				DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::LICENSE, '');
				echo json_encode(array('status'=>true));
				return;
				
			} else { // Updating license
				if(empty($key) || empty($company) || empty($email)) {
					throw new Exception("You provided an empty license.");
					
				} elseif(null==($valid = CerberusLicense::validate($key,$company,$email)) || empty($valid)) {
					throw new Exception("The provided license could not be verified.  Please double-check the company name and e-mail address and make sure they exactly match your order.");
				}
				
				/*
				 * [IMPORTANT -- Yes, this is simply a line in the sand.]
				 * You're welcome to modify the code to meet your needs, but please respect
				 * our licensing.  Buy a legitimate copy to help support the project!
				 * https://cerb.ai/
				 */
				
				// Please be honest.
				if(!empty($valid))
					DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::LICENSE, json_encode($valid));
				
				echo json_encode([
					'status' => true,
					'message' => DevblocksPlatform::translate('success.saved_changes'),
				]);
				return;
			}
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage()
			]);
			return;
			
		}
	}
}