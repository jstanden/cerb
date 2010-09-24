<?php
if(class_exists('Extension_DatacenterTab', true)):
class ChDomainsDatacenterTab extends Extension_DatacenterTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();

		// View
		$view_id = 'datacenter_domains';
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = 'View_Domain';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->id = $view_id;
		$view->name = 'Domains';
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view_id, $view);

		// Template
		$tpl->display('devblocks:cerberusweb.datacenter.domains::datacenter_tab/index.tpl');
	}
};
endif;

if(class_exists('Extension_ServerTab', true)):
class ChDomainsServerTab extends Extension_ServerTab {
	function showTab(Model_Server $server) {
		$tpl = DevblocksPlatform::getTemplateService();

		// View
		$view_id = 'server_domains';
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = 'View_Domain';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->id = $view_id;
		$view->name = 'Domains';
		$tpl->assign('view', $view);
		
		$view->addParamsHidden(array(
			SearchFields_Domain::SERVER_ID,
		));
		$view->addParamsRequired(array(
			SearchFields_Domain::SERVER_ID => new DevblocksSearchCriteria(SearchFields_Domain::SERVER_ID, '=', $server->id),
		));
		
		C4_AbstractViewLoader::setView($view_id, $view);

		// Template
		$tpl->display('devblocks:cerberusweb.datacenter.domains::server_tab/index.tpl');
	}
};
endif;

// [TODO] This will no longer be necessary soon
class ChCustomFieldSource_Domain extends Extension_CustomFieldSource {
	const ID = 'cerberusweb.datacenter.domains.fields.domain';
};

// Controller
class Page_Domains extends CerberusPageExtension {
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
		@array_shift($stack); // datacenter.domains
		@$module = array_shift($stack); // domain

		switch($module) {
			case 'domain':
				@$domain_id = array_shift($stack); // id
				if(is_numeric($domain_id) && null != ($domain = DAO_Domain::get($domain_id)))
					$tpl->assign('domain', $domain);
				
				$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.datacenter.domain.tab', false);
				uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
				$tpl->assign('tab_manifests', $tab_manifests);
				
				// [TODO] Comments
				
				$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/display/index.tpl');		
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
		$defaults->id = 'datacenter_domains';
		$defaults->class_name = 'View_Domain';
		
		$view = C4_AbstractViewLoader::getView('datacenter_domains', $defaults);
		
        $visit->set('quick_search_type', $type);
        
        $params = array();
        
        switch($type) {
            case "name":
		        if($query && false===strpos($query,'*'))
		            $query = $query . '*';
                $params[SearchFields_Domain::NAME] = new DevblocksSearchCriteria(SearchFields_Domain::NAME,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
        }
        
        $view->addParams($params, true);
        $view->renderPage = 0;
        
        C4_AbstractViewLoader::setView($view->id, $view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('datacenter','domains')));
	}	
	
	function showDomainPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Model
		$model = null;
		if(empty($id) || null == ($model = DAO_Domain::get($id)))
			$model = new Model_Domain();
		
		$tpl->assign('model', $model);
		
		// Servers
		$servers = DAO_Server::getAll();
		$tpl->assign('servers', $servers);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Domain::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Domain::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Context: Addresses
		$context_addresses = Context_Address::searchInboundLinks('cerberusweb.contexts.datacenter.domain', $id);
		$tpl->assignByRef('context_addresses', $context_addresses);
		
		// Render
		$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/peek.tpl');
	}
	
	function saveDomainPeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$server_id = DevblocksPlatform::importGPC($_REQUEST['server_id'],'integer',0);
		@$created = DevblocksPlatform::importGPC($_REQUEST['created'],'string','');
		@$contact_address_ids = DevblocksPlatform::importGPC($_REQUEST['contact_address_id'],'array',array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		if($do_delete) { // delete
			DAO_Domain::delete($id);
			
		} else { // create | update
			if(false == (@$created = strtotime($created)))
				$created = time();
			
			$fields = array(
				DAO_Domain::NAME => $name,
				DAO_Domain::SERVER_ID => $server_id,
				DAO_Domain::CREATED => $created,
			);
			
			// Create/Update
			if(empty($id)) {
				$id = DAO_Domain::create($fields);
				
			} else {
				DAO_Domain::update($id, $fields);
			}
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Domain::ID, $id, $field_ids);
			
			// Address context links
			DAO_ContextLink::setContextOutboundLinks('cerberusweb.contexts.datacenter.domain', $id, CerberusContexts::CONTEXT_ADDRESS, $contact_address_ids);
		}
		
	}
	
	function showDomainBulkUpdateAction() {
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
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Domain::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Broadcast
		CerberusContexts::getContext('cerberusweb.contexts.datacenter.domain', null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/bulk.tpl');
	}
	
	function doDomainBulkUpdateAction() {
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
				$addresses = Context_Address::searchInboundLinks('cerberusweb.contexts.datacenter.domain', key($results));
				
				if(empty($addresses)) {
					echo "This row has no associated addresses. Try again.";
					return;
				}

				// Randomize the address
				@$addy = DAO_Address::get(array_rand($addresses, 1));

				// Try to build the template
				CerberusContexts::getContext('cerberusweb.contexts.datacenter.domain', array('id'=>key($results),'address_id'=>$addy->id), $token_labels, $token_values);

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