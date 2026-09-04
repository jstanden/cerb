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
		
		// The effective pool, not the license's raw number: APP_QUEUE_CONCURRENCY_SLOTS clamps it, and on
		// Cloud the constant replaces it outright. The page has to show what actually gets enforced.
		$queue_service = DevblocksPlatform::services()->queue();
		$llm_service = DevblocksPlatform::services()->llm();
		
		$slots = $queue_service->getMaxConcurrencySlots();
		$slots_licensed = $queue_service->getMaxConcurrencySlots(with_soft_cap: false);
		
		$tpl->assign('max_concurrency_slots', $slots);
		$tpl->assign('max_concurrency_slots_licensed', $slots_licensed);
		
		// Lapsed coverage floors concurrency to Community, and nothing else announces that -- without
		// this the page would just quietly show a smaller number than the customer is paying for.
		$license = CerberusLicense::getInstance();
		$tpl->assign('license_is_expired', !is_null($license->upgrades) && $license->upgrades < time());
		
		// Drives one column or two. NOT `$license->key`: an expired key still renders its serial in the
		// chip above, but its concurrency has already floored to Community, so the comparison would
		// otherwise claim an entitlement the install no longer has.
		$tpl->assign('is_licensed', $license->isLicensed());
		
		// The Community baseline beside what this install actually gets. Both columns are DERIVED at
		// render time from the same policy the drain enforces -- no tier table, no prices, nothing that
		// can go stale against cerb.ai. Turns-at-once is a ceiling, not a reservation: the slow lane is
		// shared with background jobs, which the template says out loud.
		$turns_community = _DevblocksLlmService::TURN_MULTIPLEX_COMMUNITY;
		$turns = $llm_service->getMaxConcurrentTurns();
		
		$slots_community = _DevblocksQueueService::SLOTS_COMMUNITY;
		
		$drainers = fn(int $pool) => count(_DevblocksQueueService::getLaneSlots($pool, QueueLane::Slow));
		
		$tpl->assign('entitlements', [
			'community' => [
				'slots' => $slots_community,
				'agent_slots' => $drainers($slots_community),
				'turns' => $turns_community,
				'turns_at_once' => $drainers($slots_community) * $turns_community,
			],
			'current' => [
				'slots' => $slots,
				'agent_slots' => $drainers($slots),
				'turns' => $turns,
				'turns_at_once' => $drainers($slots) * $turns,
			],
		]);
		
		// Without the lane split on the page, `slots x turns per slot` disagrees with `turns at once` and
		// reads as an arithmetic bug: the slots reserved to the fast lane never run an agent turn.
		$lane_width = _DevblocksQueueService::getLaneWidth($slots);
		
		$tpl->assign('lane_width', $lane_width);
		$tpl->assign('lane_slots_shared', max(0, $slots - (2 * $lane_width)));
		
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