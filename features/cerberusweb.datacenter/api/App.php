<?php
abstract class Extension_DatacenterTab extends DevblocksExtension {
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_ServerTab extends DevblocksExtension {
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
		$active_worker = CerberusApplication::getActiveWorker();
		return ($active_worker) ? true : false;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$response = DevblocksPlatform::getHttpResponse();

		// Remember the last tab/URL
		if(null == ($selected_tab = @$response->path[1])) {
			//$selected_tab = $visit->get(CerberusVisit::KEY_ACTIVITY_TAB, '');
		}
		$tpl->assign('selected_tab', $selected_tab);

		// Path
		$stack = $response->path;
		@array_shift($stack); // datacenter
		@$module = array_shift($stack); // server
		
		switch($module) {
			case 'server':
				@$server_id = array_shift($stack); // id
				if(is_numeric($server_id) && null != ($server = DAO_Server::get($server_id)))
					$tpl->assign('server', $server);
				
				$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.datacenter.server.tab', false);
				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
				$tpl->assign('tab_manifests', $tab_manifests);
				
				$tpl->display('devblocks:cerberusweb.datacenter::datacenter/servers/display/index.tpl');
				break;
				
			default:
				$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.datacenter.tab', false);
				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
				$tpl->assign('tab_manifests', $tab_manifests);
				
				$tpl->display('devblocks:cerberusweb.datacenter::datacenter/index.tpl');
				break;
		}
		
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_DatacenterTab) {
			$inst->showTab();
		}
	}
		
	// Ajax
	function showServerTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		@$server_id = DevblocksPlatform::importGPC($_REQUEST['server_id'],'integer',0);
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ServerTab) {
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
		
		// Render
		$tpl->display('devblocks:cerberusweb.datacenter::datacenter/servers/peek.tpl');
	}
	
	function saveServerPeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
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
				
			} else {
				DAO_Server::update($id, $fields);
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
			
//		// Owners
//		$owner_params = array();
//		
//		@$owner_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_add_ids'],'array',array());
//		if(!empty($owner_add_ids))
//			$owner_params['add'] = $owner_add_ids;
//			
//		@$owner_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_remove_ids'],'array',array());
//		if(!empty($owner_remove_ids))
//			$owner_params['remove'] = $owner_remove_ids;
//		
//		if(!empty($owner_params))
//			$do['owner'] = $owner_params;
			
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
};