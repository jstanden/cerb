<?php
if(class_exists('Extension_DatacenterTab', true)):
class ChSitesDatacenterTab extends Extension_DatacenterTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();

		// View
		$view_id = 'datacenter_sites';
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = 'View_DatacenterSite';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->id = $view_id;
		$view->name = 'Sites';
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view_id, $view);

		// Template
		$tpl->display('devblocks:cerberusweb.datacenter.sites::datacenter_tab/index.tpl');
	}
};
endif;

if(class_exists('Extension_ServerTab', true)):
class ChSitesServerTab extends Extension_ServerTab {
	function showTab(Model_Server $server) {
		$tpl = DevblocksPlatform::getTemplateService();

		// View
		$view_id = 'server_sites';
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = 'View_DatacenterSite';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->id = $view_id;
		$view->name = 'Sites';
		$tpl->assign('view', $view);
		
		$view->addParamsHidden(array(
			SearchFields_DatacenterSite::SERVER_ID,
		));
		$view->addParamsRequired(array(
			SearchFields_DatacenterSite::SERVER_ID => new DevblocksSearchCriteria(SearchFields_DatacenterSite::SERVER_ID, '=', $server->id),
		));
		
		C4_AbstractViewLoader::setView($view_id, $view);

		// Template
		$tpl->display('devblocks:cerberusweb.datacenter.sites::server_tab/index.tpl');
	}
};
endif;

// [TODO] This will no longer be necessary soon
class ChCustomFieldSource_DatacenterSite extends Extension_CustomFieldSource {
	const ID = 'cerberusweb.datacenter.sites.fields.site';
};

// Controller
class Page_DatacenterSites extends CerberusPageExtension {
	function isVisible() {
		$active_worker = CerberusApplication::getActiveWorker();
		return ($active_worker) ? true : false;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));

		// Remember the last tab/URL
		if(null == ($selected_tab = @$response->path[1])) {
			//$selected_tab = $visit->get(CerberusVisit::KEY_ACTIVITY_TAB, '');
		}
		$tpl->assign('selected_tab', $selected_tab);

		// Path
		$stack = $response->path;
		@array_shift($stack); // datacenter.sites
		@$module = array_shift($stack); // sites

		switch($module) {
			case 'site':
				@$site_id = array_shift($stack); // id
				if(is_numeric($site_id) && null != ($site = DAO_DatacenterSite::get($site_id)))
					$tpl->assign('site', $site);
				
				$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.datacenter.site.tab', false);
				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
				$tpl->assign('tab_manifests', $tab_manifests);
				
				// [TODO] Comments
				
				$tpl->display('devblocks:cerberusweb.datacenter.sites::site/display/index.tpl');		
				break;
			default:
				break;
		}
		
	}
	
	// Post	
	function doQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$active_worker = CerberusApplication::getActiveWorker();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'datacenter_sites';
		$defaults->class_name = 'View_DatacenterSite';
		
		$view = C4_AbstractViewLoader::getView('datacenter_sites', $defaults);
		
        $visit->set('quick_search_type', $type);
        
        $params = array();
        
        switch($type) {
            case "domain":
		        if($query && false===strpos($query,'*'))
		            $query = $query . '*';
                $params[SearchFields_DatacenterSite::DOMAIN] = new DevblocksSearchCriteria(SearchFields_DatacenterSite::DOMAIN,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
        }
        
        $view->addParams($params, true);
        $view->renderPage = 0;
        
        C4_AbstractViewLoader::setView($view->id, $view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('datacenter','sites')));
	}	
	
	function showSitePeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Model
		$model = null;
		if(empty($id) || null == ($model = DAO_DatacenterSite::get($id)))
			$model = new Model_DatacenterSite();
		
		$tpl->assign('model', $model);
		
		// Servers
		$servers = DAO_Server::getAll();
		$tpl->assign('servers', $servers);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_DatacenterSite::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_DatacenterSite::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Context: Addresses
		$context_addresses = Context_Address::searchInboundLinks('cerberusweb.contexts.datacenter.site', $id);
		$tpl->assignByRef('context_addresses', $context_addresses);
		
		// Render
		$tpl->display('devblocks:cerberusweb.datacenter.sites::site/peek.tpl');
	}
	
	function saveSitePeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$domain = DevblocksPlatform::importGPC($_REQUEST['domain'],'string','');
		@$server_id = DevblocksPlatform::importGPC($_REQUEST['server_id'],'integer',0);
		@$created = DevblocksPlatform::importGPC($_REQUEST['created'],'string','');
		@$contact_address_ids = DevblocksPlatform::importGPC($_REQUEST['contact_address_id'],'array',array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		if($do_delete) { // delete
			DAO_DatacenterSite::delete($id);
			
		} else { // create | update
			if(false == (@$created = strtotime($created)))
				$created = time();
			
			$fields = array(
				DAO_DatacenterSite::DOMAIN => $domain,
				DAO_DatacenterSite::SERVER_ID => $server_id,
				DAO_DatacenterSite::CREATED => $created,
			);
			
			// Create/Update
			if(empty($id)) {
				$id = DAO_DatacenterSite::create($fields);
				
			} else {
				DAO_DatacenterSite::update($id, $fields);
			}
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_DatacenterSite::ID, $id, $field_ids);
			
			// Address context links
			DAO_ContextLink::setContextOutboundLinks('cerberusweb.contexts.datacenter.site', $id, CerberusContexts::CONTEXT_ADDRESS, $contact_address_ids);
		}
		
	}
	
	function showSiteBulkUpdateAction() {
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
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_DatacenterSite::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Broadcast
		CerberusContexts::getContext('cerberusweb.contexts.datacenter.site', null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		$tpl->display('devblocks:cerberusweb.datacenter.sites::site/bulk.tpl');
	}
	
	function doSiteBulkUpdateAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
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
		
		// Broadcast: Mass Reply
		if(1 || $active_worker->hasPriv('fill.in.the.acl.string')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			@$broadcast_is_closed = DevblocksPlatform::importGPC($_REQUEST['broadcast_next_is_closed'],'integer',0);
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'is_queued' => $broadcast_is_queued,
					'next_is_closed' => $broadcast_is_closed,
					'group_id' => $broadcast_group_id,
					'worker_id' => $active_worker->id,
				);
			}
		}		
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;		
	}
	
	function doBulkUpdateBroadcastTestAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$view = C4_AbstractViewLoader::getView($view_id);

		$tpl = DevblocksPlatform::getTemplateService();
		
		// [TODO]
		if(1 || $active_worker->hasPriv('core.addybook.addy.view.actions.broadcast')) {
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);

			// Get total
			$view->renderPage = 0;
			$view->renderLimit = 1;
			$view->renderTotal = true;
			list($null, $total) = $view->getData();
			
			// Get the first row from the view
			$view->renderPage = mt_rand(0, $total-1);
			$view->renderLimit = 1;
			$view->renderTotal = false;
			list($results, $null) = $view->getData();
			
			if(empty($results)) {
				$success = false;
				$output = "There aren't any rows in this view!";
				
			} else {
				// Pull one of the addresses on this row
				$addresses = Context_Address::searchInboundLinks('cerberusweb.contexts.datacenter.site', key($results));
				
				if(empty($addresses)) {
					echo "This row has no associated addresses. Try again.";
					return;
				}

				// Randomize the address
				@$addy = DAO_Address::get(array_rand($addresses, 1));

				// Try to build the template
				CerberusContexts::getContext('cerberusweb.contexts.datacenter.site', array('id'=>key($results),'address_id'=>$addy->id), $token_labels, $token_values);

				if(empty($broadcast_subject)) {
					$success = false;
					$output = "Subject is blank.";
				
				} else {
					$template = "Subject: $broadcast_subject\n\n$broadcast_message";
					
					if(false === ($out = $tpl_builder->build($template, $token_values))) {
						// If we failed, show the compile errors
						$errors = $tpl_builder->getErrors();
						$success= false;
						$output = @array_shift($errors);
					} else {
						// If successful, return the parsed template
						$success = true;
						$output = $out;
					}
				}
			}
			
			$tpl->assign('success', $success);
			$tpl->assign('output', htmlentities($output, null, LANG_CHARSET_CODE));
			
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
		}
	}	
};