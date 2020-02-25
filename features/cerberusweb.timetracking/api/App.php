<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class ChTimeTrackingPreBodyRenderer extends Extension_AppPreBodyRenderer {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('current_timestamp', time());
		$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/prebody.tpl');
	}
};

class ChTimeTrackingProfileScript extends Extension_ContextProfileScript {
	const ID = 'timetracking.profile_script.timer';
	
	function renderScript($context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('page_context', $context);
		$tpl->assign('page_context_id', $context_id);

		$tpl->display('devblocks:cerberusweb.timetracking::timetracking/renderers/toolbar_timer.js.tpl');
	}
}

class ChTimeTrackingEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				//DAO_TimeTrackingActivity::maint();
				DAO_TimeTrackingEntry::maint();
				break;
				
			case 'record.merge':
				$context = $event->params['context'];
				// [TODO]
				//$new_ticket_id = $event->params['new_ticket_id'];
				//$old_ticket_ids = $event->params['old_ticket_ids'];
				break;
		}
	}
};