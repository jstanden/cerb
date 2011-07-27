<?php
abstract class Extension_DatacenterTab extends DevblocksExtension {
	const POINT = 'cerberusweb.datacenter.tab';
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_ServerTab extends DevblocksExtension {
	const POINT = 'cerberusweb.datacenter.server.tab';
	
	function showTab(Model_Server $server) {}
	function saveTab() {}
};

class DatacenterServersTab extends Extension_DatacenterTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		// View
		$view_id = 'datacenter_servers';
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = 'View_Server';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->id = $view_id;
		$view->name = 'Servers';
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view_id, $view);
		
		$tpl->display('devblocks:cerberusweb.datacenter::datacenter/servers/tab.tpl');
	}
	
	function saveTab() {
		
	}
};

class Page_Datacenter extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();

		// Path
		$stack = $response->path;
		@array_shift($stack); // datacenter
		@$module = array_shift($stack); // server
		
		switch($module) {
			case 'server':
				@$server_id = array_shift($stack); // id
				if(is_numeric($server_id) && null != ($server = DAO_Server::get($server_id)))
					$tpl->assign('server', $server);

				// Remember the last tab/URL
				if(null == ($selected_tab = @$response->path[3])) {
					$selected_tab = $visit->get(Extension_ServerTab::POINT, '');
				}
				$tpl->assign('selected_tab', $selected_tab);
					
				$tab_manifests = DevblocksPlatform::getExtensions(Extension_ServerTab::POINT, false);
				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
				$tpl->assign('tab_manifests', $tab_manifests);
				
				// Custom fields
				
				$custom_fields = DAO_CustomField::getAll();
				$tpl->assign('custom_fields', $custom_fields);
				
				// Properties
				
				$properties = array();
				
//				$properties['created'] = array(
//					'label' => ucfirst($translate->_('common.created')),
//					'type' => Model_CustomField::TYPE_DATE,
//					'value' => $server->created,
//				);
				
				@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds('cerberusweb.contexts.datacenter.server', $server->id)) or array();
		
				foreach($custom_fields as $cf_id => $cfield) {
					if(!isset($values[$cf_id]))
						continue;
						
					$properties['cf_' . $cf_id] = array(
						'label' => $cfield->name,
						'type' => $cfield->type,
						'value' => $values[$cf_id],
					);
				}
				
				$tpl->assign('properties', $properties);
				
				$tpl->display('devblocks:cerberusweb.datacenter::datacenter/servers/display/index.tpl');
				break;
				
			default:
				// Remember the last tab/URL
				if(null == ($selected_tab = @$response->path[1])) {
					$selected_tab = $visit->get(Extension_DatacenterTab::POINT, '');
				}
				$tpl->assign('selected_tab', $selected_tab);
				
				$tab_manifests = DevblocksPlatform::getExtensions(Extension_DatacenterTab::POINT, false);
				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
				$tpl->assign('tab_manifests', $tab_manifests);
				
				$tpl->display('devblocks:cerberusweb.datacenter::datacenter/index.tpl');
				break;
		}
		
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_DatacenterTab) {
				$visit->set(Extension_DatacenterTab::POINT, $inst->manifest->params['uri']);
				$inst->showTab();
		}
	}
		
	// Ajax
	function showServerTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		@$server_id = DevblocksPlatform::importGPC($_REQUEST['server_id'],'integer',0);
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ServerTab) {
				$visit->set(Extension_ServerTab::POINT, $inst->manifest->params['uri']);
				$server = DAO_Server::get($server_id);
				$inst->showTab($server);
		}
	}	
	
	// Post	
	function doServerQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$active_worker = CerberusApplication::getActiveWorker();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'datacenter_servers';
		$defaults->class_name = 'View_Server';
		
		$view = C4_AbstractViewLoader::getView('datacenter_servers', $defaults);
		
        $visit->set('quick_search_type', $type);
        
        $params = array();
        
        switch($type) {
            case "name":
		        if($query && false===strpos($query,'*'))
		            $query = $query . '*';
                $params[SearchFields_Server::NAME] = new DevblocksSearchCriteria(SearchFields_Server::NAME,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
        }
        
        $view->addParams($params, true);
        $view->renderPage = 0;
        
        C4_AbstractViewLoader::setView($view->id, $view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('datacenter','servers')));
	}	
	
	function showServerPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Model
		$model = null;
		if(empty($id) || null == ($model = DAO_Server::get($id)))
			$model = new Model_Server();
		
		$tpl->assign('model', $model);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.server'); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds('cerberusweb.contexts.datacenter.server', $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Comments
		$comments = DAO_Comment::getByContext('cerberusweb.contexts.datacenter.server', $id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		// Render
		$tpl->display('devblocks:cerberusweb.datacenter::datacenter/servers/peek.tpl');
	}
	
	function saveServerPeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		if($do_delete) { // delete
			DAO_Server::delete($id);
			
		} else { // create | update
			$fields = array(
				DAO_Server::NAME => $name,
			);
			
			// Create/Update
			if(empty($id)) {
				$id = DAO_Server::create($fields);
				
				@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
				if($is_watcher)
					CerberusContexts::addWatchers('cerberusweb.contexts.datacenter.server', $id, $active_worker->id);
				
			} else {
				DAO_Server::update($id, $fields);
			}
			
			// If we're adding a comment
			if(!empty($comment)) {
				@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => 'cerberusweb.contexts.datacenter.server',
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost('cerberusweb.contexts.datacenter.server', $id, $field_ids);
			
			// [TODO] Context links
		}
		
	}
	
	function showServerBulkUpdateAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
   		// Teams
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Categories
		//$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		//$tpl->assign('team_categories', $team_categories);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.server');
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.datacenter::datacenter/servers/bulk.tpl');
	}
	
	function doServerBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		$do = array();
		
		// Do: Due
//		$due = trim(DevblocksPlatform::importGPC($_POST['due'],'string',''));
//		if(0 != strlen($due))
//			$do['due'] = $due;
			
//		// Watchers
//		$watcher_params = array();
//		
//		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
//		if(!empty($watcher_add_ids))
//			$watcher_params['add'] = $watcher_add_ids;
//			
//		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
//		if(!empty($watcher_remove_ids))
//			$watcher_params['remove'] = $watcher_remove_ids;
//		
//		if(!empty($watcher_params))
//			$do['watchers'] = $watcher_params;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

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
	
	function viewServersExploreAction() {
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
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=datacenter&tab=servers', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $id => $row) {
				if($id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $id,
					'url' => $url_writer->writeNoProxy(sprintf("c=datacenter&tab=server&id=%d", $id), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}	
};

if (class_exists('DevblocksEventListenerExtension')):
class EventListener_Datacenter extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_Server::maint();
				break;
		}
	}
};
endif;