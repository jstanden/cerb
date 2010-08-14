<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class ChMailOverviewTab extends Extension_MailTab {
	const VIEW_MAIL_OVERVIEW = 'mail_overview';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'overview');
		
		// Request path
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		$response_path = explode('/', $request);
		@array_shift($response_path); // tickets
		@$controller = array_shift($response_path); // overview
		
		// Make sure the global URL was for us
		if(0!=strcasecmp('overview',$controller))
			$response_path = null;

		$active_worker = CerberusApplication::getActiveWorker();
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// View
		//$title = $translate->_('mail.overview.all_groups');
		$title = "Overview";

		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = self::VIEW_MAIL_OVERVIEW;
		$defaults->name = $title;
		$defaults->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		
		$overView = C4_AbstractViewLoader::getView(self::VIEW_MAIL_OVERVIEW, $defaults);
		
		$overView->paramsRequired = array(
			new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
			new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($active_worker->getMemberships())),
		);
		$overView->paramsDefault = array(
		);
		$overView->paramsHidden = array(
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_WAITING,
		);
		
		$overView->renderPage = 0;
		
		// Filter persistence
		if(empty($response_path)) {
			@$response_path = explode('/',$visit->get('mail.overview.filter', 'all'));
		} else {
			// View Filter
			$visit->set('mail.overview.filter', implode('/',$response_path));
		}
		
		@$filter = array_shift($response_path);
		
		$overView->doResetCriteria();
		
		switch($filter) {
			case 'open':
				$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0));
				$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0));
				
				@$filter_category_id = array_shift($response_path);
				if(!is_null($filter_category_id) && isset($groups[$filter_category_id])) {
					$tpl->assign('filter_category_id', $filter_category_id);
					$title = $groups[$filter_category_id]->name;
					$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_category_id));
					
					@$filter_subcategory_id = array_shift($response_path);
					if(!is_null($filter_subcategory_id)) {
						$tpl->assign('filter_subcategory_id', $filter_subcategory_id);
						@$title .= ': '.
							(($filter_subcategory_id == 0) ? $translate->_('common.inbox') : $group_buckets[$filter_category_id][$filter_subcategory_id]->name);
						$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',$filter_subcategory_id));
					}
				}
				break;
				
			case 'waiting':
				$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0));
				$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',1));
				
				@$filter_category_id = array_shift($response_path);
				if(!is_null($filter_category_id) && isset($groups[$filter_category_id])) {
					$tpl->assign('filter_category_id', $filter_category_id);
					$title = $groups[$filter_category_id]->name;
					$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_category_id));
					
					@$filter_subcategory_id = array_shift($response_path);
					if(!is_null($filter_subcategory_id)) {
						$tpl->assign('filter_subcategory_id', $filter_subcategory_id);
						@$title .= ': '.
							(($filter_subcategory_id == 0) ? $translate->_('common.inbox') : $group_buckets[$filter_category_id][$filter_subcategory_id]->name);
						$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',$filter_subcategory_id));
					}
				}
				break;
				
			case 'worker':
				@$filter_category_id = array_shift($response_path);
				
				if(!is_null($filter_category_id) && isset($workers[$filter_category_id])) {
					$tpl->assign('filter_category_id', $filter_category_id);
					$title = $workers[$filter_category_id]->getName();
					$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WORKERS,'in',array($filter_category_id)));
					
					@$filter_subcategory_id = array_shift($response_path);
					if(!is_null($filter_subcategory_id)) {
						$tpl->assign('filter_subcategory_id', $filter_subcategory_id);
						@$title .= ': '. $groups[$filter_subcategory_id]->name;
						$overView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_subcategory_id));
					}
				}
				break;
				
			case 'all':
			default:
				break;
		}
		
		$overView->name = $title;
		C4_AbstractViewLoader::setView($overView->id, $overView);
		
		$tpl->assign('view', $overView);
		
		// Totals (only drill down as deep as a group)
		$original_params = $overView->getEditableParams();
		$overView->doResetCriteria();
		
		$open_counts = $overView->getCounts('open');
		$tpl->assign('open_counts', $open_counts);

		$waiting_counts = $overView->getCounts('waiting');
		$tpl->assign('waiting_counts', $waiting_counts);
		
		$worker_counts = $overView->getCounts('worker');
		$tpl->assign('worker_counts', $worker_counts);
		
		$overView->addParams($original_params, true);
		
		// Log activity
//		DAO_Worker::logActivity(
//			new Model_Activity(
//				'activity.mail.workflow',
//				array(
//					'<i>'.$overView->name.'</i>'
//				)
//			)
//		);
		
		$tpl->display('devblocks:cerberusweb.mail.overview::mail/overview/tab.tpl');
	}
	
	function saveTab() {
		// Do nothing...
	}
}