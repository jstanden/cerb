<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_MailDrafts extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		// Remember the tab
		$visit->set(Extension_MailTab::POINT, 'drafts');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_MailQueue';
		$defaults->id = 'mail_drafts';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$view->name = 'Drafts';
		
		$view->addColumnsHidden(array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::TICKET_ID,
			SearchFields_MailQueue::WORKER_ID,
		), true);
		
		$view->addParamsRequired(array(
			SearchFields_MailQueue::WORKER_ID => new DevblocksSearchCriteria(SearchFields_MailQueue::WORKER_ID, DevblocksSearchCriteria::OPER_EQ, $active_worker->id),
			SearchFields_MailQueue::IS_QUEUED => new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED, DevblocksSearchCriteria::OPER_EQ, 0),
		), true);
		$view->addParamsHidden(array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::TICKET_ID,
			SearchFields_MailQueue::WORKER_ID,
		), true);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::mail/section/drafts.tpl');		
	}
	
	function saveDraftAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0); 

		// Common
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string',''); 
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string',''); 
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');

		// Compose
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0); 
		@$cc = DevblocksPlatform::importGPC($_REQUEST['cc'],'string',''); 
		@$bcc = DevblocksPlatform::importGPC($_REQUEST['bcc'],'string',''); 
		
		$params = array();
		
		$hint_to = null;
		$type = null;
		
		if(!empty($to))
			$params['to'] = $to;
			
		if(empty($subject) && empty($content))
			return json_encode(array());
			
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string','');
		
		switch($type) {
			case 'compose':
				@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
				@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0); 
				
				if(!empty($cc))
					$params['cc'] = $cc;
				if(!empty($bcc))
					$params['bcc'] = $bcc;
				if(!empty($group_id))
					$params['group_id'] = $group_id;
				if(!empty($bucket_id))
					$params['bucket_id'] = $bucket_id;
				if(!is_null($org_name))
					$params['org_name'] = $org_name;
					
				$type = 'mail.compose';
				$hint_to = $to;
				break;
				
			default:
				// Bail out
				echo json_encode(array());
				return;
				break;
		}
			
		$fields = array(
			DAO_MailQueue::TYPE => $type,
			DAO_MailQueue::TICKET_ID => 0,
			DAO_MailQueue::WORKER_ID => $active_worker->id,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::HINT_TO => $hint_to,
			DAO_MailQueue::SUBJECT => $subject,
			DAO_MailQueue::BODY => $content,
			DAO_MailQueue::PARAMS_JSON => json_encode($params),
			DAO_MailQueue::IS_QUEUED => 0,
			DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
		);
		
		// Make sure the current worker is the draft author
		if(!empty($draft_id)) {
			$draft = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id
			));
			
			if(!isset($draft[$draft_id]))
				$draft_id = null;
		}
		
		if(empty($draft_id)) {
			$draft_id = DAO_MailQueue::create($fields);
		} else {
			DAO_MailQueue::update($draft_id, $fields);
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('timestamp', time());
		$html = $tpl->fetch('devblocks:cerberusweb.core::mail/queue/saved.tpl');
		
		echo json_encode(array('draft_id'=>$draft_id, 'html'=>$html));
	}
	
	function deleteDraftAction() {
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($draft_id)
			&& null != ($draft = DAO_MailQueue::get($draft_id))
			&& ($active_worker->id == $draft->worker_id || $active_worker->is_superuser)) {
			
			DAO_MailQueue::delete($draft_id);
		}
	}
	
	function showDraftsPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(null != ($draft = DAO_MailQueue::get($id)))
			if($active_worker->is_superuser || $draft->worker_id==$active_worker->id)
				$tpl->assign('draft', $draft);
		
		$tpl->display('devblocks:cerberusweb.core::mail/queue/peek.tpl');
	}
	
	function showDraftsBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
	    // Lists
//	    $lists = DAO_FeedbackList::getWhere();
//	    $tpl->assign('lists', $lists);
	    
		// Custom Fields
//		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK);
//		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::mail/queue/bulk.tpl');		
	}
	
	function doDraftsBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    $ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Draft fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string'));

		$do = array();
		
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
			
		// Do: Custom fields
//		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}

		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;		
	}

	function viewDraftsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=mail&section=drafts', true),
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $draft_id => $row) {
				if($draft_id==$explore_from)
					$orig_pos = $pos;
				
				if($row[SearchFields_MailQueue::TYPE]==Model_MailQueue::TYPE_COMPOSE) {
					$url = $url_writer->writeNoProxy(sprintf("c=mail&a=compose&id=%d", $draft_id), true);
				} elseif($row[SearchFields_MailQueue::TYPE]==Model_MailQueue::TYPE_TICKET_REPLY) {
					$url = $url_writer->writeNoProxy(sprintf("c=display&id=%d", $row[SearchFields_MailQueue::TICKET_ID]), true) . sprintf("#draft%d", $draft_id);
				}

				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_MailQueue::ID],
					'url' => $url,
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}	
};
