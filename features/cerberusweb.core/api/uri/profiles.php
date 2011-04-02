<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class Page_Profiles extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;

		@array_shift($stack); // profiles
		@$type = array_shift($stack); // group | worker

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		switch($type) {
			case 'group':
				@$group_id = intval(array_shift($stack));
				$point = 'cerberusweb.profiles.group.' . $group_id;

				if(empty($group_id) || null == ($group = DAO_Group::get($group_id)))
					throw new Exception();
				
				$tpl->assign('group', $group);
				
				// Remember the last tab/URL
				if(null == ($selected_tab = @$response->path[3])) {
					$selected_tab = $visit->get($point, '');
				}
				$tpl->assign('selected_tab', $selected_tab);
				
				$tpl->display('devblocks:cerberusweb.core::profiles/group/index.tpl');
				break;
				
			case 'worker':
				@$id = array_shift($stack);
				
				switch($id) {
					case 'me':
						$worker_id = $active_worker->id;
						break;
						
					default:
						@$worker_id = intval($id);
						break;
				}

				$point = 'cerberusweb.profiles.worker.' . $worker_id;
				
				if(empty($worker_id) || null == ($worker = DAO_Worker::get($worker_id)))
					throw new Exception();
					
				$tpl->assign('worker', $worker);
				
				// Remember the last tab/URL
				if(null == ($selected_tab = @$response->path[3])) {
					$selected_tab = $visit->get($point, '');
				}
				$tpl->assign('selected_tab', $selected_tab);
				
				// Counts
				$counts = DAO_ContextLink::getContextLinkCounts(CerberusContexts::CONTEXT_WORKER, $worker_id);
				$watching_total = intval(array_sum($counts));
				$tpl->assign('watching_total', $watching_total);
				
				$tpl->display('devblocks:cerberusweb.core::profiles/worker/index.tpl');
				break;
				
			default:
				$tpl->display('devblocks:cerberusweb.core::profiles/index.tpl');
				break;
		}
	}
};
