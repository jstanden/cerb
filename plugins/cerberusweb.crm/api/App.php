<?php
// Classes
$path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/App.php', array(
    'C4_CrmOpportunityView'
));

class CrmPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class CrmPage extends CerberusPageExtension {
	private $plugin_path = '';
	
	function __construct($manifest) {
		parent::__construct($manifest);

		$this->plugin_path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = $this->plugin_path . '/templates/';
		$tpl->assign('path', $tpl_path);

		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // crm
		
		$visit = CerberusApplication::getVisit();
		
		$module = array_shift($stack); // opps
		
		switch($module) {
			case 'campaigns':
				$campaigns = DAO_CrmCampaign::getWhere();
				$tpl->assign('campaigns', $campaigns);
				
				$tpl->display($tpl_path . 'crm/campaigns/index.tpl.php');
				break;
				
			default:
			case 'opps':
				switch(array_shift($stack)) {
					case 'display':
						@$opp_id = intval(array_shift($stack));
						if(null == ($opp = DAO_CrmOpportunity::get($opp_id))) {
							break; // [TODO] Not found
						}
						$tpl->assign('opp', $opp);						

						$campaigns = DAO_CrmCampaign::getWhere();
						$tpl->assign('campaigns', $campaigns);

						$buckets = DAO_CrmCampaignBucket::getWhere();
						$tpl->assign('buckets', $buckets);
						
						$address = DAO_Address::get($opp->primary_email_id);
						$tpl->assign('address', $address);
						
						$workers = DAO_Worker::getAll();
						$tpl->assign('workers', $workers);
						
						$task_count = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.opp', $opp_id);
						$tpl->assign('tasks_total', $task_count);
						
						$comments_count = DAO_CrmOppComment::getCountByOpportunityId($opp_id);
						$tpl->assign('comments_total', $comments_count);
						
						$visit = CerberusApplication::getVisit();
						
						// Does a series exist?
						if(null != ($series_info = $visit->get('ch_opp_series', null))) {
							@$series = $series_info['series'];
							// Is this ID part of the series?  If not, invalidate
							if(!isset($series[$opp_id])) {
								$visit->set('ch_opp_series', null);
							} else {
								$series_stats = array(
									'title' => $series_info['title'],
									'total' => $series_info['total'],
									'count' => count($series)
								);
								reset($series);
								$cur = 1;
								while(current($series)) {
									$pos = key($series);
									if(intval($pos)==intval($opp_id)) {
										$series_stats['cur'] = $cur;
										if(false !== prev($series)) {
											@$series_stats['prev'] = $series[key($series)][SearchFields_CrmOpportunity::ID];
											next($series); // skip to current
										} else {
											reset($series);
										}
										next($series); // next
										@$series_stats['next'] = $series[key($series)][SearchFields_CrmOpportunity::ID];
										break;
									}
									next($series);
									$cur++;
								}
								
								$tpl->assign('series_stats', $series_stats);
							}
						}
						
						$tpl->display($tpl_path . 'crm/opps/display/index.tpl.php');
						break;
					
					case 'search':
						if(null == ($view = C4_AbstractViewLoader::getView('', 'opps_search'))) {
							$view = new C4_CrmOpportunityView();
							$view->id = 'opps_search';
							C4_AbstractViewLoader::setView($view->id, $view);
						}

						$view->name = "Search Results";
						$tpl->assign('view', $view);

						$campaigns = DAO_CrmCampaign::getWhere();
						$tpl->assign('campaigns', $campaigns);
						
						$tpl->assign('view_fields', C4_CrmOpportunityView::getFields());
						$tpl->assign('view_searchable_fields', C4_CrmOpportunityView::getSearchFields());
						
						$tpl->display($tpl_path . 'crm/opps/search.tpl.php');
						break;
						
					default:
					case 'overview':
						$workers = DAO_Worker::getAll();
						$tpl->assign('workers', $workers);
		
						$campaigns = DAO_CrmCampaign::getWhere();
						$tpl->assign('campaigns', $campaigns);
						
						$campaign_buckets = DAO_CrmCampaignBucket::getByCampaigns();
						$tpl->assign('campaign_buckets', $campaign_buckets);
						
						if(null == ($view = C4_AbstractViewLoader::getView('', C4_CrmOpportunityView::DEFAULT_ID))) {
							$view = new C4_CrmOpportunityView();
							C4_AbstractViewLoader::setView($view->id, $view);
						}
						
						// Filter persistence
						if(empty($stack)) {
							@$stack = explode('/',$visit->get('crm.overview.filter', 'all'));
						} else {
							// View Filter
							$visit->set('crm.overview.filter', implode('/',$stack));
						}
							
						// Are we changing the view?
						switch(array_shift($stack)) {
							case 'all':
								$view->params = array(
									SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
									SearchFields_CrmOpportunity::WORKER_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::WORKER_ID,'=',0),
								);
								$view->name = "All Opportunities";
								$view->renderPage = 0;									
								C4_AbstractViewLoader::setView($view->id, $view);									
								break;
								
							case 'campaign':
								@$module_id = array_shift($stack);
								@$bucket_id = array_shift($stack);
								
								if(!empty($module_id) && isset($campaigns[$module_id])) {
									$view->params = array(
										SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
										SearchFields_CrmOpportunity::CAMPAIGN_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::CAMPAIGN_ID,'=',$module_id),
										SearchFields_CrmOpportunity::WORKER_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::WORKER_ID,'=',0),
									);

									$tpl->assign('filter_campaign_id', $module_id);
									
									if(!empty($bucket_id)) {
										$view->params[SearchFields_CrmOpportunity::CAMPAIGN_BUCKET_ID] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::CAMPAIGN_BUCKET_ID,'=',$bucket_id);
										$view->name = sprintf("%s: %s",
											@$campaigns[$module_id]->name,
											@$campaign_buckets[$module_id][$bucket_id]->name
										);
										$tpl->assign('filter_bucket_id', $bucket_id);
										
									} else {
										$view->name = $campaigns[$module_id]->name;
									}
									
									$view->renderPage = 0;									
									C4_AbstractViewLoader::setView($view->id, $view);									
								}
								break;
								
							case 'worker':
								@$module_id = array_shift($stack);
								if(!empty($module_id) && isset($workers[$module_id])) {
									$view->params = array(
										SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
										SearchFields_CrmOpportunity::WORKER_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::WORKER_ID,'=',$module_id),
									);
									$view->name = "For " . $workers[$module_id]->getName();
									$view->renderPage = 0;							
									C4_AbstractViewLoader::setView($view->id, $view);									
								}
								break;
						}

						$tpl->assign('view', $view);
						
						$unassigned_totals = DAO_CrmOpportunity::getUnassignedOppTotals();
						$tpl->assign('unassigned_totals', $unassigned_totals);
						
						$assigned_totals = DAO_CrmOpportunity::getAssignedOppTotals();
						$tpl->assign('assigned_totals', $assigned_totals);
						
						$tpl->display($tpl_path . 'crm/opps/index.tpl.php');
						break;
				}
				
				break;
		}
	}
	
	function browseOppsAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // crm
		array_shift($stack); // browseOpps
		
		@$id = array_shift($stack);
		
		$opp = DAO_CrmOpportunity::get($id);
	
		if(empty($opp)) {
			echo "<H1>Invalid Opportunity ID.</H1>";
			return;
		}
		
		// Display series support (inherited paging from Display)
		@$view_id = array_shift($stack);
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('',$view_id);

			// Restrict to the active worker's groups
			$active_worker = CerberusApplication::getActiveWorker();
//			$memberships = $active_worker->getMemberships();
//			$view->params['tmp'] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::TEAM_ID, 'in', array_keys($memberships)); 
			
			$range = 100;
			$pos = $view->renderLimit * $view->renderPage;
			$page = floor($pos / $range);
			
			list($series, $series_count) = DAO_CrmOpportunity::search(
				$view->params,
				$range,
				$page,
				$view->renderSortBy,
				$view->renderSortAsc,
				false
			);
			
			$series_info = array(
				'title' => $view->name,
				'total' => count($series),
				'series' => $series
			);
			
			$visit->set('ch_opp_series', $series_info);
		}
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps','display',$opp->id)));
		exit;
	}
	
	function showOppPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('email', $email);
		
		if(!empty($opp_id) && null != ($opp = DAO_CrmOpportunity::get($opp_id))) {
			$tpl->assign('opp', $opp);
			
			if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
				$tpl->assign('address', $address);
			}
		}
		
		$campaigns = DAO_CrmCampaign::getWhere();
		$tpl->assign('campaigns', $campaigns);
		
		$buckets = DAO_CrmCampaignBucket::getByCampaigns();
		$tpl->assign('campaign_buckets', $buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/peek.tpl.php');
	}
	
	function saveOppPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$email_str = DevblocksPlatform::importGPC($_REQUEST['emails'],'string','');
		@$source = DevblocksPlatform::importGPC($_REQUEST['source'],'string','');
		@$next_action = DevblocksPlatform::importGPC($_REQUEST['next_action'],'string','');
//		@$campaign_id = DevblocksPlatform::importGPC($_REQUEST['campaign_id'],'integer',0);
		@$bucket_str = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();

		$campaign_id = 0;
		$bucket_id = 0;
		
		if(!empty($bucket_str)) {
			if($hits = preg_match("/^c(.*?)\_b(.*?)$/", $bucket_str, $matches)) {
				$campaign_id = $matches[1];
				$bucket_id = $matches[2];
			}
		}
		
		if(empty($opp_id)) {
			$emails = DevblocksPlatform::parseCsvString($email_str);
			
			// One opportunity per provided e-mail address
			if(is_array($emails))
			foreach($emails as $email) {
				if(null == ($address = DAO_Address::lookupAddress($email, true)))
					continue;
				
				$fields = array(
					DAO_CrmOpportunity::NAME => $name,
					DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
					DAO_CrmOpportunity::CAMPAIGN_ID => $campaign_id,
					DAO_CrmOpportunity::CAMPAIGN_BUCKET_ID => $bucket_id,
					DAO_CrmOpportunity::CREATED_DATE => time(),
					DAO_CrmOpportunity::UPDATED_DATE => time(),
					DAO_CrmOpportunity::SOURCE => $source,
					DAO_CrmOpportunity::NEXT_ACTION => $next_action,
					DAO_CrmOpportunity::WORKER_ID => $worker_id,
				);
				$opp_id = DAO_CrmOpportunity::create($fields);
				
				// If we're adding a first comment
				if(!empty($comment)) {
					$fields = array(
						DAO_CrmOppComment::CREATED_DATE => time(),
						DAO_CrmOppComment::OPPORTUNITY_ID => $opp_id,
						DAO_CrmOppComment::WORKER_ID => $active_worker->id,
						DAO_CrmOppComment::CONTENT => $comment,
					);
					$comment_id = DAO_CrmOppComment::create($fields);
				}
			}
			
		} else {
			if(empty($opp_id))
				return;
			
			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
//				DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
				DAO_CrmOpportunity::CAMPAIGN_ID => $campaign_id,
				DAO_CrmOpportunity::CAMPAIGN_BUCKET_ID => $bucket_id,
//				DAO_CrmOpportunity::CREATED_DATE => time(),
//				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::SOURCE => $source,
				DAO_CrmOpportunity::NEXT_ACTION => $next_action,
				DAO_CrmOpportunity::WORKER_ID => $worker_id,
			);
			DAO_CrmOpportunity::update($opp_id, $fields);
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showCampaignPanelAction() {
		@$campaign_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($campaign_id) && null != ($campaign = DAO_CrmCampaign::get($campaign_id))) {
			$tpl->assign('campaign', $campaign);
			
			$buckets = DAO_CrmCampaignBucket::getByCampaignId($campaign_id);
			$tpl->assign('buckets', $buckets);
		}
		
		$tpl->display('file:' . $tpl_path . 'crm/campaigns/rpc/peek.tpl.php');
	}
	
	function saveCampaignPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$campaign_id = DevblocksPlatform::importGPC($_REQUEST['campaign_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$add_buckets_csv = DevblocksPlatform::importGPC($_REQUEST['add_buckets_csv'],'string','');
		@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_ids'],'array',array());
		@$bucket_names = DevblocksPlatform::importGPC($_REQUEST['bucket_names'],'array',array());
		@$bucket_dels = DevblocksPlatform::importGPC($_REQUEST['bucket_dels'],'array',array());

		$fields = array(
			DAO_CrmCampaign::NAME => $name			
		);
		
		// [TODO] Delete campaign
		
		if(empty($campaign_id)) { // new
			$campaign_id = DAO_CrmCampaign::create($fields);
			
		} else { // update
			DAO_CrmCampaign::update($campaign_id, $fields);
			
		}
		
		// Add buckets
		if(!empty($campaign_id) && !empty($add_buckets_csv)) {
			$bucket_labels = DevblocksPlatform::parseCsvString($add_buckets_csv);
			foreach($bucket_labels as $bucket_label) {
				if(!empty($bucket_label))
					DAO_CrmCampaignBucket::create(array(
						DAO_CrmCampaignBucket::NAME => $bucket_label,
						DAO_CrmCampaignBucket::CAMPAIGN_ID => $campaign_id,
					));
			}
		}
		
		// Edit buckets // [TODO] if changed
		if(!empty($bucket_ids) && !empty($bucket_names)) {
			foreach($bucket_ids as $idx => $bucket_id) {
				DAO_CrmCampaignBucket::update($bucket_id,array(
					DAO_CrmCampaignBucket::NAME => $bucket_names[$idx]
				));
			}
		}
		
		// Del buckets
		if(!empty($campaign_id) && !empty($bucket_dels)) {
			foreach($bucket_dels as $bucket_id) {
				DAO_CrmCampaignBucket::delete($bucket_id);
			}
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		exit;
	}
	
	function showOppImportPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$campaigns = DAO_CrmCampaign::getWhere();
		$tpl->assign('campaigns', $campaigns);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/import.tpl.php');
	}
	
	function showOppTasksTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);
		
		$view = C4_AbstractViewLoader::getView('C4_TaskView', 'opp_tasks');
		$view->id = 'opp_tasks';
		$view->name = 'Opportunity Tasks';
		$view->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::PRIORITY,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			SearchFields_Task::COMPLETED_DATE,
		);
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.opp'),
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$opp_id),
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
//		$view->name = "Most recent tickets from " . htmlentities($contact->email);
//		$view->params = array(
//			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_EQ,$contact->email)
//		);
//		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/tasks.tpl.php');
	}
	
	function showOppMailTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		$address = DAO_Address::get($opp->primary_email_id);
		$tpl->assign('address', $address);
		
		$view = C4_AbstractViewLoader::getView('C4_TicketView', 'opp_tickets');
		$view->id = 'opp_tickets';
		$view->name = 'Open Tickets';
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TEAM_NAME,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_NEXT_ACTION,
			SearchFields_Ticket::TICKET_NEXT_WORKER_ID,
		);
		$view->params = array(
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array($opp->primary_email_id)),
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
		);
		$view->name = "Requester: " . $address->email;
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/mail.tpl.php');
	}
	
	function showOppCommentsTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		$comments = DAO_CrmOppComment::getWhere(sprintf("%s = %d",
			DAO_CrmOppComment::OPPORTUNITY_ID,
			$opp_id
		));
		$tpl->assign('comments', $comments);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/comments.tpl.php');
	}
	
//	function showOppWonPanelAction() {
//		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
//		$tpl->assign('path', $tpl_path);
//
//		$opp = DAO_CrmOpportunity::get($opp_id);
//		$tpl->assign('opp', $opp);
//		
//		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/won.tpl.php');
//	}
	
	function saveOppWonPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer');
		
		$fields = array(
			DAO_CrmOpportunity::CLOSED_DATE => time(),
			DAO_CrmOpportunity::IS_CLOSED => 1,
			DAO_CrmOpportunity::IS_WON => 1,
		);
		DAO_CrmOpportunity::update($opp_id, $fields);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps','display',$opp_id)));
	}
	
//	function showOppLostPanelAction() {
//		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl_path = realpath(dirname(__FILE__) . '/../templates/') . DIRECTORY_SEPARATOR;
//		$tpl->assign('path', $tpl_path);
//
//		$opp = DAO_CrmOpportunity::get($opp_id);
//		$tpl->assign('opp', $opp);
//		
//		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/lost.tpl.php');
//	}
	
	function saveOppLostPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer');
		
		$fields = array(
			DAO_CrmOpportunity::CLOSED_DATE => time(),
			DAO_CrmOpportunity::IS_CLOSED => 1,
			DAO_CrmOpportunity::IS_WON => 0,
		);
		DAO_CrmOpportunity::update($opp_id, $fields);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps','display',$opp_id)));
	}
	
	function reopenOppAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer');
		
		$fields = array(
			DAO_CrmOpportunity::CLOSED_DATE => 0,
			DAO_CrmOpportunity::IS_CLOSED => 0,
			DAO_CrmOpportunity::IS_WON => 0,
		);
		DAO_CrmOpportunity::update($opp_id, $fields);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps','display',$opp_id)));
	}
	
	function saveOppCommentAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_CrmOppComment::OPPORTUNITY_ID => $opp_id,
			DAO_CrmOppComment::CREATED_DATE => time(),
			DAO_CrmOppComment::WORKER_ID => $active_worker->id,
			DAO_CrmOppComment::CONTENT => $comment,
		);
		$comment_id = DAO_CrmOppComment::create($fields);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps','display',$opp_id,'comments')));
	}
	
	function deleteOppCommentAction() {
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['comment_id'],'integer',0);
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		
		DAO_CrmOppComment::delete($comment_id);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps','display',$opp_id,'comments')));
	}
	
	function doOppImportXmlAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
/*		<opportunity>
			<name><![CDATA[Austin@hrproguys.com-Free]]></name>
			<contact>
				<email>trainump@msn.com</email>
				<org_name><![CDATA[: Cogenix Search Consultants]]></org_name>
				<first_name><![CDATA[Austin]]></first_name>
				<last_name><![CDATA[Barrington]]></last_name>
				<phone>(830) 237-5867</phone>
			</contact>
			<created_date>1102492800</created_date>
			<updated_date>1105257600</updated_date>
			<closed_date>0</closed_date>
			<is_closed>0</is_closed>
			<is_won>0</is_won>
		</opportunity>
*/		
		$campaigns = DAO_CrmCampaign::getWhere();
		
		@$import_campaign_id = DevblocksPlatform::importGPC($_REQUEST['import_campaign_id'],'integer',0);
		@$import_worker_id = DevblocksPlatform::importGPC($_REQUEST['import_worker_id'],'integer',0);
		
		$file_props = $_FILES['xml_file'];
		$file = $file_props['tmp_name'];
	
		if(empty($file))
			return;
		
		$xml_in = simplexml_load_file($file); /* @var $xml_in SimpleXMLElement */
		
		$imports = 0;
		$dupes = 0;
		
		foreach($xml_in->opportunity as $opp) { /* @var $opp SimpleXMLElement */
			$in_name = trim((string) $opp->name);
			$in_source = (string) $opp->source;
			$in_created_date = (string) $opp->created_date;
			$in_updated_date = (string) $opp->updated_date;
			$in_closed_date = (string) $opp->closed_date;
			$in_is_closed = (string) $opp->is_closed;
			$in_is_won = (string) $opp->is_won;

			$in_org_id = 0;
			$in_email_id = 0;

			$in_email = trim((string) $opp->contact->email);
			$in_org_name = trim((string) $opp->contact->org_name);
			$in_first_name = trim((string) $opp->contact->first_name);
			$in_last_name = trim((string) $opp->contact->last_name);
			$in_phone = (string) $opp->contact->phone;
			
			// Set a default name if we only have email addresses
			if(empty($in_name)) {
				if(isset($campaigns[$import_campaign_id])) {
					$in_name = $campaigns[$import_campaign_id]->name;
				} elseif(!empty($in_email)) {
					$in_name = $in_email;
				} else {
					$in_name = "(Opportunity)";
				}
			}
			
			// Set today's create date if none provided
			if(empty($in_created_date))
				$in_created_date = time();
			if(empty($in_updated_date))
				$in_updated_date = time();
			
			if(!empty($in_name)) {
				// Lookup|create the addresses provided by XML
				if(!empty($in_email) 
					&& null != ($in_address = DAO_Address::lookupAddress($in_email, true))) {
						$in_email_id = $in_address->id;
						
						$update_fields = array();
						
						// Org sync
						$in_org_id = $in_address->contact_org_id;
						if(empty($in_org_id) && !empty($in_org_name)) {
							if(null != ($in_org_id = DAO_ContactOrg::lookup(utf8_decode($in_org_name), true)))
								$update_fields[DAO_Address::CONTACT_ORG_ID] = $in_org_id; 							
						}
						
						// First name sync
						$check_first_name = $in_address->first_name;
						if(empty($check_first_name) && !empty($in_first_name)) {
							$update_fields[DAO_Address::FIRST_NAME] = $in_first_name;
						}
						
						// Last name sync
						$check_last_name = $in_address->last_name;
						if(empty($check_last_name) && !empty($in_last_name)) {
							$update_fields[DAO_Address::LAST_NAME] = $in_last_name;
						}
						
						// Phone sync
						$check_phone = $in_address->phone;
						if(empty($check_phone) && !empty($in_phone)) {
							$update_fields[DAO_Address::PHONE] = $in_phone;
						}

						if(!empty($update_fields))
							DAO_Address::update($in_email_id, $update_fields);
							
						// Dupe check by email + campaign combo
						$hits = DAO_CrmOpportunity::getWhere(sprintf("%s = %d AND %s = %d AND %s = %s",
							DAO_CrmOpportunity::PRIMARY_EMAIL_ID,
							$in_email_id,
							DAO_CrmOpportunity::CAMPAIGN_ID,
							$import_campaign_id,
							DAO_CrmOpportunity::NAME,
							$db->qstr($in_name)
						));
						
						// Don't dupe if we matched the same name + e-mail addy on the same campaign
						if(!empty($hits)) {
							$dupes++;
							continue;
						}
				}
				
				if(!empty($in_name)) {
					$fields = array(
						DAO_CrmOpportunity::NAME => utf8_decode($in_name), 
						DAO_CrmOpportunity::CAMPAIGN_ID => intval($import_campaign_id),
						DAO_CrmOpportunity::PRIMARY_EMAIL_ID => intval($in_email_id),
						DAO_CrmOpportunity::SOURCE => $in_source, 
						DAO_CrmOpportunity::CREATED_DATE => intval($in_created_date),
						DAO_CrmOpportunity::UPDATED_DATE => intval($in_updated_date), 
						DAO_CrmOpportunity::CLOSED_DATE => intval($in_closed_date), 
						DAO_CrmOpportunity::IS_CLOSED => intval($in_is_closed), 
						DAO_CrmOpportunity::IS_WON => intval($in_is_won), 
						DAO_CrmOpportunity::WORKER_ID => intval($import_worker_id), 
					);
					$opp_id = DAO_CrmOpportunity::create($fields);
					$imports++;
				}
			}
		}
		
		echo "Imported ",$imports," records from ",$file_props['name'],"<BR>";
		echo "Skipped ",$dupes," duplicate records from ",$file_props['name'],"<BR>";
		
//		echo $xml_in->asXML();
	}
	
	function doAddCampaignAction() {
		@$in_campaign_name = DevblocksPlatform::importGPC($_REQUEST['add_campaign_name'],'string','');
		
		if(!empty($in_campaign_name)) {
			$fields = array(
				DAO_CrmCampaign::NAME => $in_campaign_name
			);
			$campaign_id = DAO_CrmCampaign::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','campaigns')));
	}
	
	function viewOppSetCampaignAction() {
		@$in_bucket_str = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());
		
		$campaign_id = 0;
		$bucket_id = 0;
		
		if(!empty($in_bucket_str)) {
			if($hits = preg_match("/^c(.*?)\_b(.*?)$/", $in_bucket_str, $matches)) {
				$campaign_id = $matches[1];
				$bucket_id = $matches[2];
			}
		}

		if(!empty($campaign_id)) {
			DAO_CrmOpportunity::update($row_ids,array(
				DAO_CrmOpportunity::CAMPAIGN_ID => $campaign_id,
				DAO_CrmOpportunity::CAMPAIGN_BUCKET_ID => $bucket_id,
			));
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps')));
	}
	
	// [TODO] Move to a view bulk update
	function viewOppDeleteAction() {
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());
		
		DAO_CrmOpportunity::delete($row_ids);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps')));
	}
	
	// [TODO] Move to a view bulk update
	function viewOppSetWorkerAction() {
		@$in_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());
		
		DAO_CrmOpportunity::update($row_ids,array(
			DAO_CrmOpportunity::WORKER_ID => $in_worker_id
		));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps')));
	}
};

class DAO_CrmOpportunity extends DevblocksORMHelper {
	const ID = 'id';
	const CAMPAIGN_ID = 'campaign_id';
	const CAMPAIGN_BUCKET_ID = 'campaign_bucket_id';
	const NAME = 'name';
	const PRIMARY_EMAIL_ID = 'primary_email_id';
	const SOURCE = 'source';
	const NEXT_ACTION = 'next_action';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const CLOSED_DATE = 'closed_date';
	const IS_WON = 'is_won';
	const IS_CLOSED = 'is_closed';
	const WORKER_ID = 'worker_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('crm_opportunity_seq');
		
		$sql = sprintf("INSERT INTO crm_opportunity (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'crm_opportunity', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('crm_opportunity', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmOpportunity[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, campaign_id, campaign_bucket_id, name, primary_email_id, source, next_action, created_date, updated_date, closed_date, is_won, is_closed, worker_id ".
			"FROM crm_opportunity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmOpportunity	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_CrmOpportunity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_CrmOpportunity();
			$object->id = intval($rs->fields['id']);
			$object->campaign_id = intval($rs->fields['campaign_id']);
			$object->campaign_bucket_id = intval($rs->fields['campaign_bucket_id']);
			$object->name = $rs->fields['name'];
			$object->primary_email_id = intval($rs->fields['primary_email_id']);
			$object->source = $rs->fields['source'];
			$object->next_action = $rs->fields['next_action'];
			$object->created_date = $rs->fields['created_date'];
			$object->updated_date = $rs->fields['updated_date'];
			$object->closed_date = $rs->fields['closed_date'];
			$object->is_won = $rs->fields['is_won'];
			$object->is_closed = $rs->fields['is_closed'];
			$object->worker_id = $rs->fields['worker_id'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM crm_opportunity WHERE id IN (%s)", $ids_list));
		
		DAO_CrmOppComment::delete($ids);
		
		return true;
	}

	/**
	 * @return array
	 */
	static function getUnassignedOppTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT count(id) AS hits, campaign_id, campaign_bucket_id ".
			"FROM crm_opportunity ".
			"WHERE is_closed = 0 ".
			"AND worker_id = 0 ".
			"GROUP BY campaign_id, campaign_bucket_id"
		;
		$rs = $db->Execute($sql);
		
		$totals = array();
		
		while(!$rs->EOF) {
			$campaign_id = $rs->fields['campaign_id'];
			$bucket_id = $rs->fields['campaign_bucket_id'];
			
			if(!isset($totals[$campaign_id]))
				$totals[$campaign_id] = array('total'=>0);
				
			$totals[$campaign_id][$bucket_id] = intval($rs->fields['hits']);
			$totals[$campaign_id]['total'] += intval($rs->fields['hits']);
			$rs->MoveNext();
		}
		
		return $totals;
	}
	
	/**
	 * @return array
	 */
	static function getAssignedOppTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT count(id) AS hits, worker_id ".
			"FROM crm_opportunity ".
			"WHERE is_closed = 0 ".
			"AND worker_id > 0 ".
			"GROUP BY worker_id"
		;
		$rs = $db->Execute($sql);
		
		$totals = array();
		
		while(!$rs->EOF) {
			$totals[$rs->fields['worker_id']] = intval($rs->fields['hits']);
			$rs->MoveNext();
		}
		
		return $totals;
	}
	
    /**
     * Enter description here...
     *
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, array(), SearchFields_CrmOpportunity::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"o.id as %s, ".
			"o.campaign_id as %s, ".
			"o.campaign_bucket_id as %s, ".
			"o.name as %s, ".
			"o.source as %s, ".
			"o.next_action as %s, ".
			"org.id as %s, ".
			"org.name as %s, ".
			"org.website as %s, ".
			"o.primary_email_id as %s, ".
			"a.email as %s, ".
			"a.phone as %s, ".
			"o.created_date as %s, ".
			"o.updated_date as %s, ".
			"o.closed_date as %s, ".
			"o.is_closed as %s, ".
			"o.is_won as %s, ".
			"o.worker_id as %s ".
			"FROM crm_opportunity o ".
			"LEFT JOIN address a ON (a.id = o.primary_email_id) ".
			"LEFT JOIN contact_org org ON (org.id = a.contact_org_id) ",
			    SearchFields_CrmOpportunity::ID,
			    SearchFields_CrmOpportunity::CAMPAIGN_ID,
			    SearchFields_CrmOpportunity::CAMPAIGN_BUCKET_ID,
			    SearchFields_CrmOpportunity::NAME,
			    SearchFields_CrmOpportunity::SOURCE,
			    SearchFields_CrmOpportunity::NEXT_ACTION,
			    SearchFields_CrmOpportunity::ORG_ID,
			    SearchFields_CrmOpportunity::ORG_NAME,
			    SearchFields_CrmOpportunity::ORG_WEBSITE,
			    SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			    SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			    SearchFields_CrmOpportunity::CONTACT_PHONE,
			    SearchFields_CrmOpportunity::CREATED_DATE,
			    SearchFields_CrmOpportunity::UPDATED_DATE,
			    SearchFields_CrmOpportunity::CLOSED_DATE,
			    SearchFields_CrmOpportunity::IS_CLOSED,
			    SearchFields_CrmOpportunity::IS_WON,
			    SearchFields_CrmOpportunity::WORKER_ID
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['m']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_CrmOpportunity::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
	
};

class SearchFields_CrmOpportunity implements IDevblocksSearchFields {
	// Table
	const ID = 'o_id';
	const CAMPAIGN_ID = 'o_campaign_id';
	const CAMPAIGN_BUCKET_ID = 'o_campaign_bucket_id';
	const PRIMARY_EMAIL_ID = 'o_primary_email_id';
	const SOURCE = 'o_source';
	const NEXT_ACTION = 'o_next_action';
	const NAME = 'o_name';
	const CREATED_DATE = 'o_created_date';
	const UPDATED_DATE = 'o_updated_date';
	const CLOSED_DATE = 'o_closed_date';
	const IS_WON = 'o_is_won';
	const IS_CLOSED = 'o_is_closed';
	const WORKER_ID = 'o_worker_id';
	
	const ORG_ID = 'org_id';
	const ORG_NAME = 'org_name';
	const ORG_WEBSITE = 'org_website';

	const EMAIL_ADDRESS = 'a_email';
	const CONTACT_PHONE = 'a_phone';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'o', 'id', null, $translate->_('crm.opportunity.id')),
			self::CAMPAIGN_ID => new DevblocksSearchField(self::CAMPAIGN_ID, 'o', 'campaign_id', null, $translate->_('crm.opportunity.campaign_id')),
			self::CAMPAIGN_BUCKET_ID => new DevblocksSearchField(self::CAMPAIGN_BUCKET_ID, 'o', 'campaign_bucket_id', null, $translate->_('crm.opportunity.campaign_bucket_id')),
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'o', 'primary_email_id', null, $translate->_('crm.opportunity.primary_email_id')),
			self::SOURCE => new DevblocksSearchField(self::SOURCE, 'o', 'source', null, $translate->_('crm.opportunity.source')),
			self::NEXT_ACTION => new DevblocksSearchField(self::NEXT_ACTION, 'o', 'next_action', null, $translate->_('crm.opportunity.next_action')),
			self::NAME => new DevblocksSearchField(self::NAME, 'o', 'name', null, $translate->_('crm.opportunity.name')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'o', 'created_date', null, $translate->_('crm.opportunity.created_date')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'o', 'updated_date', null, $translate->_('crm.opportunity.updated_date')),
			self::CLOSED_DATE => new DevblocksSearchField(self::CLOSED_DATE, 'o', 'closed_date', null, $translate->_('crm.opportunity.closed_date')),
			self::IS_WON => new DevblocksSearchField(self::IS_WON, 'o', 'is_won', null, $translate->_('crm.opportunity.is_won')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'o', 'is_closed', null, $translate->_('crm.opportunity.is_closed')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'o', 'worker_id', null, $translate->_('crm.opportunity.worker_id')),
			
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'org', 'id', null),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'org', 'name', null, $translate->_('crm.opportunity.org_name')),
			self::ORG_WEBSITE => new DevblocksSearchField(self::ORG_WEBSITE, 'org', 'website', null, $translate->_('crm.opportunity.org_website')),
			
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'a', 'email', null, $translate->_('crm.opportunity.email_address')),
			self::CONTACT_PHONE => new DevblocksSearchField(self::CONTACT_PHONE, 'a', 'phone', null, $translate->_('crm.opportunity.contact_phone')),
		);
	}
};	

class Model_CrmOpportunity {
	public $id;
	public $campaign_id;
	public $campaign_bucket_id;
	public $name;
	public $source;
	public $next_action;
	public $primary_email_id;
	public $created_date;
	public $updated_date;
	public $closed_date;
	public $is_won;
	public $is_closed;
	public $worker_id;
};

class DAO_CrmOppComment extends DevblocksORMHelper {
	const ID = 'id';
	const OPPORTUNITY_ID = 'opportunity_id';
	const CREATED_DATE = 'created_date';
	const WORKER_ID = 'worker_id';
	const CONTENT = 'content';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO crm_opp_comment (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'crm_opp_comment', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmOppComment[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, opportunity_id, created_date, worker_id, content ".
			"FROM crm_opp_comment ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmOppComment	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getCountByOpportunityId($id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) FROM crm_opp_comment WHERE opportunity_id = %d",
			$id
		);
		return $db->GetOne($sql);
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_CrmOppComment[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_CrmOppComment();
			$object->id = $rs->fields['id'];
			$object->opportunity_id = $rs->fields['opportunity_id'];
			$object->created_date = $rs->fields['created_date'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->content = $rs->fields['content'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function deleteByOppIds($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM crm_opp_comment WHERE opportunity_id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM crm_opp_comment WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class Model_CrmOppComment {
	public $id;
	public $opportunity_id;
	public $created_date;
	public $worker_id;
	public $content;
};

class DAO_CrmCampaign extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO crm_campaign (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'crm_campaign', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmCampaign[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name ".
			"FROM crm_campaign ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmCampaign	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_CrmCampaign[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_CrmCampaign();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM crm_campaign WHERE id IN (%s)", $ids_list));
		
		// Cascade: Buckets
		DAO_CrmCampaignBucket::deleteByCampaignIds($ids);
		
		// [TODO] Delete opps from campaign
		
		return true;
	}
};

class Model_CrmCampaign {
	public $id;
	public $name;
};

class DAO_CrmCampaignBucket extends DevblocksORMHelper {
	const ID = 'id';
	const CAMPAIGN_ID = 'campaign_id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO crm_campaign_bucket (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'crm_campaign_bucket', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmCampaignBucket[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, campaign_id, name ".
			"FROM crm_campaign_bucket ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * Enter description here...
	 *
	 * @return array
	 */
	static function getByCampaigns() {
		$campaigns = array();
		$buckets = self::getWhere();
		
		foreach($buckets as $bucket_id => $bucket) {
			if(!isset($campaigns[$bucket->campaign_id]))
				$campaigns[$bucket->campaign_id] = array();
				
			$campaigns[$bucket->campaign_id][$bucket_id] = $bucket;
		}
		
		return $campaigns;
	}
	
	/**
	 * @param integer $id
	 * @return Model_CrmCampaignBucket	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getByCampaignId($campaign_id) {
		return self::getWhere(sprintf("%s = %d",
			self::CAMPAIGN_ID,
			$campaign_id
		));
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_CrmCampaignBucket[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_CrmCampaignBucket();
			$object->id = $rs->fields['id'];
			$object->campaign_id = $rs->fields['campaign_id'];
			$object->name = $rs->fields['name'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function deleteByCampaignIds($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM crm_campaign_bucket WHERE campaign_id IN (%s)", $ids_list));
		$db->Execute(sprintf("UPDATE crm_opportunity SET campaign_bucket_id = 0 WHERE campaign_id IN (%s)", $ids_list));
		
		return true;
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM crm_campaign_bucket WHERE id IN (%s)", $ids_list));
		$db->Execute(sprintf("UPDATE crm_opportunity SET campaign_bucket_id = 0 WHERE campaign_bucket_id IN (%s)", $ids_list));
		
		return true;
	}

};

class Model_CrmCampaignBucket {
	public $id;
	public $campaign_id;
	public $name;
};

class C4_CrmOpportunityView extends C4_AbstractView {
	const DEFAULT_ID = 'crm_opportunities';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Opportunities';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::UPDATED_DATE,
			SearchFields_CrmOpportunity::CAMPAIGN_ID,
			SearchFields_CrmOpportunity::CAMPAIGN_BUCKET_ID,
			SearchFields_CrmOpportunity::NEXT_ACTION,
		);
		
		$this->params = array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_CrmOpportunity::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$campaigns = DAO_CrmCampaign::getWhere();
		$tpl->assign('campaigns', $campaigns);
		
		$buckets = DAO_CrmCampaignBucket::getByCampaigns();
		$tpl->assign('campaign_buckets', $buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.crm/templates/crm/opps/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CrmOpportunity::CAMPAIGN_ID:
				$campaigns = DAO_CrmCampaign::getWhere();
				$tpl->assign('campaigns', $campaigns);
				$tpl->display('file:' . $tpl_path . 'crm/opps/criteria/campaign.tpl.php');
				break;
				
			case SearchFields_CrmOpportunity::SOURCE:
			case SearchFields_CrmOpportunity::NEXT_ACTION:
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::ORG_WEBSITE:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::CONTACT_PHONE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl.php');
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
				
			case SearchFields_CrmOpportunity::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl.php');
				break;
				
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_CrmOpportunity::CAMPAIGN_ID:
				$campaigns = DAO_CrmCampaign::getWhere();
				$strings = array();
				
				foreach($values as $val) {
					if(!isset($campaigns[$val]))
						continue;
					else
						$strings[] = $campaigns[$val]->name;
				}
				echo implode(", ", $strings);
				
				break;
				
			case SearchFields_CrmOpportunity::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
						$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
						continue;
					else
						$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	// [TODO] change globally to getColumnFields() in AbstractView
	static function getFields() {
		$fields = SearchFields_CrmOpportunity::getFields();
//		unset($fields[SearchFields_CrmLead::ID]);
		return $fields;
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_CrmOpportunity::ID]);
		unset($fields[SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID]);
		unset($fields[SearchFields_CrmOpportunity::ORG_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_CrmOpportunity::ID]);
		unset($fields[SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID]);
		unset($fields[SearchFields_CrmOpportunity::ORG_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			default:
			case SearchFields_CrmOpportunity::CAMPAIGN_ID:
				@$campaign_ids = DevblocksPlatform::importGPC($_REQUEST['campaign_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$campaign_ids);
				break;
				
			case SearchFields_CrmOpportunity::SOURCE:
			case SearchFields_CrmOpportunity::NEXT_ACTION:
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::ORG_WEBSITE:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::CONTACT_PHONE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_CrmOpportunity::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
				
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};	

class CrmTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return realpath(dirname(__FILE__).'/../') . '/strings.xml';
	}
};

// [TODO] Can possibly remove this listener
class CrmEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
//        switch($event->id) {
//            case 'address.peek.saved':
//            	/*
//            	 * If the address has a new address.org_id assigned, we want to 
//            	 * copy this change in our local CRM opp, if the current opps 
//            	 * aren't assigned to an org yet (opp.org_id=0)
//            	 */
//            	
//            	@$address_id = intval($event->params['address_id']);
//            	@$fields = $event->params['changed_fields'];
//            	@$org_id = intval($fields[DAO_Address::CONTACT_ORG_ID]);
//            	
//            	if(empty($address_id) || empty($org_id))
//            		return;
//
//	   			DAO_CrmOpportunity::updateWhere(
//		   			array(
//	   					DAO_CrmOpportunity::ORG_ID => $org_id
//	   				),
//	   				sprintf(
//		  				"%s = %d AND %s = %d",
//		  				DAO_CrmOpportunity::PRIMARY_EMAIL_ID,
//		  				$address_id,
//		  				DAO_CrmOpportunity::ORG_ID,
//		  				0
//	   			));
//	   			
//            	break;
//        }
    }
};

class CrmTaskSource_Opp extends Extension_TaskSource {
	function getSourceName() {
		return "Opportunities";
	}
	
	function getSourceInfo($object_id) {
		if(null == ($opp = DAO_CrmOpportunity::get($object_id)))
			return;
		
		$url = DevblocksPlatform::getUrlService();
		return array(
			'name' => '[Opp] '.$opp->name,
			'url' => $url->write(sprintf('c=crm&a=opps&d=display&id=%d',$opp->id)),
		);
	}
};

class CrmOrgOppTab extends Extension_OrgTab {
	function showTab() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$org = DAO_ContactOrg::get($org_id);
		$tpl->assign('org_id', $org_id);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', 'org_opps'))) {
			$view = new C4_CrmOpportunityView();
			$view->id = 'org_opps';
		}
		
		$view->name = "Org: " . $org->name;
		$view->params = array(
			SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org_id) 
		);

		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/org/tab.tpl.php');
	}
	
	function saveTab() {
	}
};

class CrmTicketOppTab extends Extension_TicketTab {
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		
		$address = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('address', $address);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', 'ticket_opps'))) {
			$view = new C4_CrmOpportunityView();
			$view->id = 'ticket_opps';
		}

		if(!empty($address->contact_org_id)) { // org
			@$org = DAO_ContactOrg::get($address->contact_org_id);
			
			$view->name = "Org: " . $org->name;
			$view->params = array(
				SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org->id) 
			);
			
		} else { // address
			$view->name = "Requester: " . $address->email;
			$view->params = array(
				SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,'=',$ticket->first_wrote_address_id) 
			);
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/ticket/tab.tpl.php');
	}
	
	function saveTab() {
	}
};
?>