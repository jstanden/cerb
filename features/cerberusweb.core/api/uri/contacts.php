<?php
class ChContactsPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
		
	function getActivity() {
		return new Model_Activity('activity.address_book');
	}
	
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$visit = CerberusApplication::getVisit();		
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		@array_shift($stack); // contacts
		@$selected_tab = array_shift($stack); // orgs|addresses|*
		
		$tpl->assign('selected_tab', $selected_tab);
		
		// Allow a non-tab renderer
		switch($selected_tab) {
			case 'import':
				switch(@array_shift($stack)) {
					case 'step2':
						$type = $visit->get('import.last.type', '');
						
						switch($type) {
							case 'orgs':
								$fields = DAO_ContactOrg::getFields();
								$tpl->assign('fields',$fields);
								$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
								$tpl->assign('custom_fields', $custom_fields);
								break;
							case 'addys':
								$fields = DAO_Address::getFields();
								$tpl->assign('fields',$fields);
								$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
								$tpl->assign('custom_fields', $custom_fields);
								break;
						}
						
						$tpl->display('file:' . $this->_TPL_PATH . 'contacts/import/mapping.tpl');
						return;
						break;
				}
				break;
			
			// [TODO] The org display page should probably move to its own controller
			case 'orgs':
				switch(@array_shift($stack)) {
					case 'display':
						$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.org.tab', false);
						$tpl->assign('tab_manifests', $tab_manifests);
						
						$id = array_shift($stack);
						
						$contact = DAO_ContactOrg::get($id);
						$tpl->assign('contact', $contact);
						
						$task_count = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.org', $contact->id);
						$tpl->assign('tasks_total', $task_count);
						
						$people_count = DAO_Address::getCountByOrgId($contact->id);
						$tpl->assign('people_total', $people_count);
						
						$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/display.tpl');
						return;
						break; // case 'orgs/display'
						
				} // switch (action)
				break;
				
		} // switch (tab)
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/index.tpl');
		return;
		
//			case 'people':
//				$view = C4_AbstractViewLoader::getView('addybook_people'); // View_Address::DEFAULT_ID
//				
//				if(null == $view) {
//					$view = new View_Address();
//					$view->id = 'addybook_people';
//					$view->name = 'People';
//					$view->params = array(
//						new DevblocksSearchCriteria(SearchFields_Address::CONTACT_ORG_ID,'!=',0),
//					);
//					
//					C4_AbstractViewLoader::setView('addybook_people', $view);
//				}
//				
//				$tpl->assign('view', $view);
//				$tpl->assign('contacts_page', 'people');
//				$tpl->assign('view_fields', View_Address::getFields());
//				$tpl->assign('view_searchable_fields', View_Address::getSearchFields());
//				$tpl->display('file:' . $this->_TPL_PATH . 'contacts/people/index.tpl');
//				break;
	}
	
	function showOrgsTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_ContactOrg';
		$defaults->id = View_ContactOrg::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_ContactOrg::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('response_uri', 'contacts/orgs');
		$tpl->assign('view_fields', View_ContactOrg::getFields());
		$tpl->assign('view_searchable_fields', View_ContactOrg::getSearchFields());
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/index.tpl');
	}
	
	function showAddysTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Address';
		$defaults->id = View_Address::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_Address::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		$tpl->assign('contacts_page', 'addresses');
		$tpl->assign('response_uri', 'contacts/addresses');
		$tpl->assign('view_fields', View_Address::getFields());
		$tpl->assign('view_searchable_fields', View_Address::getSearchFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/addresses/index.tpl');
	}
	
	function showImportTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
	
		if(!$active_worker->hasPriv('core.addybook.import'))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/import/index.tpl');
	}
	
	function viewOrgsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		$view->renderPage = 0;
		$view->renderLimit = 25;
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
					'return_url' => $url_writer->write('c=contacts&tab=orgs', true),
//					'toolbar_extension_id' => '',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $ticket_id => $row) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ContactOrg::ID],
					'url' => $url_writer->write(sprintf("c=contacts&tab=orgs&mode=display&id=%d", $row[SearchFields_ContactOrg::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,'1')));
	}	
	
	// Post
	function parseUploadAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string','');
		$csv_file = $_FILES['csv_file'];

		if(empty($type) || !is_array($csv_file) || !isset($csv_file['tmp_name']) || empty($csv_file['tmp_name'])) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('contacts','import')));
			return;
		}
		
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$filename = basename($csv_file['tmp_name']);
		$newfilename = APP_TEMP_PATH . '/' . $filename;
		
		if(!rename($csv_file['tmp_name'], $newfilename)) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('contacts','import')));
			return; // [TODO] Throw error
		}
		
		// [TODO] Move these to a request holding object?
		$visit->set('import.last.type', $type);
		$visit->set('import.last.csv', $newfilename);
		
		$fp = fopen($newfilename, "rt");
		if($fp) {
			$parts = fgetcsv($fp, 8192, ',', '"');
			$tpl->assign('parts', $parts);
		}
		
		@fclose($fp);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('contacts','import','step2')));
	}
	
	// Post
	function doImportAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.addybook.import'))
			return;
		
		@$pos = DevblocksPlatform::importGPC($_REQUEST['pos'],'array',array());
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		@$sync_column = DevblocksPlatform::importGPC($_REQUEST['sync_column'],'string','');
		@$include_first = DevblocksPlatform::importGPC($_REQUEST['include_first'],'integer',0);
		@$is_blank_unset = DevblocksPlatform::importGPC($_REQUEST['is_blank_unset'],'integer',0);
		
		@$replace_passwords = DevblocksPlatform::importGPC($_REQUEST['replace_passwords'],'integer',0);
		
		$visit = CerberusApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();
		
		$csv_file = $visit->get('import.last.csv','');
		$type = $visit->get('import.last.type','');
		
		$fp = fopen($csv_file, "rt");
		if(!$fp) return;

		// [JAS]: Do we need to consume a first row of headings?
		if(!$include_first)
			@fgetcsv($fp, 8192, ',', '"');
		
		while(!feof($fp)) {
			$parts = fgetcsv($fp, 8192, ',', '"');
			
			if(empty($parts) || (1==count($parts) && is_null($parts[0])))
				continue;
			
			$fields = array();
			$custom_fields = array();
			$sync_field = '';
			$sync_val = '';
			
			// Overrides
			$contact_password = '';
			
			if(is_array($pos))
			foreach($pos as $idx => $p) {
				$key = $field[$idx];
				$val = $parts[$idx];
				
				if(!empty($key)) {
					// Organizations
					if($type=="orgs") {
						switch($key) {
							// Multi-Line
							case 'street':
								@$val = isset($fields[$key]) ? ($fields[$key].', '.$val) : ($val);
								break;
							
							// Dates
							case 'created':
								@$val = !is_numeric($val) ? strtotime($val) : $val;
								break;
						}

						// Custom fields
						if('cf_' == substr($key,0,3)) {
							$custom_fields[substr($key,3)] = $val;
						} else {
							$fields[$key] = $val;
						}
					
					// Addresses	
					} elseif($type=="addys") {
						switch($key) {
							// Org (from string into id)
							case 'contact_org_id':
								if(null != ($org_id = DAO_ContactOrg::lookup($val, true))) {
									$val = $org_id;
								} else {
									$val = 0;
								}
								break;
								
							case 'pass':
								$key = null;
								// Detect if we need to MD5 a plaintext password.
								if(preg_match("/[a-z0-9]{32}/", $val)) {
									$contact_password = $val;
								} else {
									$contact_password = md5($val);
								}
								break;
						}

						// Custom fields
						if('cf_' == substr($key,0,3)) {
							$custom_fields[substr($key,3)] = $val;
						} elseif(!empty($key)) {
							$fields[$key] = $val;
						}
						
					}

					if(!empty($key)) {
						// [JAS]: Are we looking for matches in a certain field?
						if($sync_column==$key && !empty($val)) {
							$sync_field = $key;
							$sync_val = $val;
						}
					}
				}
			}
			
			if(!empty($fields)) {
				if($type=="orgs") {
					@$orgs = DAO_ContactOrg::getWhere(
						(!empty($sync_field) && !empty($sync_val)) 
							? sprintf('%s = %s', $sync_field, $db->qstr($sync_val))
							: sprintf('name = %s', $db->qstr($fields['name']))
					);

					if(isset($fields['name'])) {
						if(empty($orgs)) {
							$id = DAO_ContactOrg::create($fields);
						} else {
							$id = key($orgs);
							DAO_ContactOrg::update($id, $fields);
						}
					}
				} elseif ($type=="addys") {
					
					if(!empty($sync_field) && !empty($sync_val))
						@$addys = DAO_Address::getWhere(
							sprintf('%s = %s', $sync_field, $db->qstr($sync_val))
						);
					
					if(isset($fields['email'])) {
						// Overrides
						if(!empty($contact_password)) {
							if($replace_passwords) { // always replace
								$fields[DAO_Address::IS_REGISTERED] = 1;
								$fields[DAO_Address::PASS] = $contact_password;
								
							} else { // only replace if null
								if(null == ($addy = DAO_Address::lookupAddress($fields['email'], false))
									|| !$addy->is_registered) {
										$fields[DAO_Address::IS_REGISTERED] = 1;
										$fields[DAO_Address::PASS] = $contact_password;
								}
							}
						}
						
						if(empty($addys)) {
							$id = DAO_Address::create($fields);
						} else {
							$id = key($addys);
							DAO_Address::update($id, $fields);
						}

					}
				}
			}
			
			if(!empty($custom_fields) && !empty($id)) {
				// Format (typecast) and set the custom field types
				$source_ext_id = ($type=="orgs") ? ChCustomFieldSource_Org::ID : ChCustomFieldSource_Address::ID;
				DAO_CustomFieldValue::formatAndSetFieldValues($source_ext_id, $id, $custom_fields, $is_blank_unset);
			}
			
		}
		
		@unlink($csv_file); // nuke the imported file
		
		$visit->set('import.last.csv',null);
		$visit->set('import.last.type',null);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','import')));
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_OrgTab) {
			$inst->showTab();
		}
	}
	
	function showTabPropertiesAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('org_id', $org);
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);

		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $org);
		if(isset($custom_field_values[$org]))
			$tpl->assign('custom_field_values', $custom_field_values[$org]);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/tabs/properties.tpl');
		exit;
	}
	
	function showTabPeopleAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Address';
		$defaults->id = 'org_contacts';
		$defaults->view_columns = array(
			SearchFields_Address::FIRST_NAME,
			SearchFields_Address::LAST_NAME,
			SearchFields_Address::NUM_NONSPAM,
		);
		
		$view = C4_AbstractViewLoader::getView('org_contacts', $defaults);
		$view->name = 'Contacts: ' . $contact->name;
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Address::CONTACT_ORG_ID,'=',$org)
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/tabs/people.tpl');
		exit;
	}
	
	function showTabTasksAction() {
		$translate = DevblocksPlatform::getTranslationService();
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Task';
		$defaults->id = 'org_tasks';
		$defaults->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			SearchFields_Task::COMPLETED_DATE,
		);
		
		$view = C4_AbstractViewLoader::getView('org_tasks', $defaults);
		$view->name = $translate->_('common.tasks') . ' ' . $contact->name;
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.org'),
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$org),
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/tabs/tasks.tpl');
		exit;
	}
	
	function showTabNotesAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$org = DAO_ContactOrg::get($org_id);
		$tpl->assign('org', $org);
		
		list($notes, $null) = DAO_Note::search(
			array(
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_EXT_ID,'=',ChNotesSource_Org::ID),
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_ID,'=',$org->id),
			),
			25,
			0,
			SearchFields_Note::CREATED,
			false,
			false
		);

		$tpl->assign('notes', $notes);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);

		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);

		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/tabs/notes.tpl');
	}
	
	function showTabHistoryAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */

		$tickets_view = C4_AbstractViewLoader::getView('contact_history');
		
		// All org contacts
		$people = DAO_Address::getWhere(sprintf("%s = %d",
			DAO_Address::CONTACT_ORG_ID,
			$contact->id
		));
		
		if(null == $tickets_view) {
			$tickets_view = new View_Ticket();
			$tickets_view->id = 'contact_history';
			$tickets_view->name = $translate->_('addy_book.history.view_title');
			$tickets_view->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TICKET_TEAM_ID,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
			);
			$tickets_view->params = array(
			);
			$tickets_view->renderLimit = 10;
			$tickets_view->renderPage = 0;
			$tickets_view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$tickets_view->renderSortAsc = false;
		}

		@$tickets_view->name = $translate->_('ticket.requesters') . ": " . htmlspecialchars($contact->name) . ' - ' . intval(count($people)) . ' contact(s)';
		$tickets_view->params = array(
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($people)),
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,DevblocksSearchCriteria::OPER_EQ,0)
		);
		$tpl->assign('contact_history', $tickets_view);
		
		C4_AbstractViewLoader::setView($tickets_view->id,$tickets_view);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/tabs/history.tpl');
		exit;
	}
	
	function showAddressPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$address_id = DevblocksPlatform::importGPC($_REQUEST['address_id'],'integer',0);
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		if(!empty($address_id)) {
			$email = '';
			if(null != ($addy = DAO_Address::get($address_id))) {
				@$email = $addy->email;
			}
		}
		$tpl->assign('email', $email);
		
		if(!empty($email)) {
			list($addresses,$null) = DAO_Address::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Address::EMAIL,DevblocksSearchCriteria::OPER_EQ,$email)
				),
				1,
				0,
				null,
				null,
				false
			);
			
			$address = array_shift($addresses);
			$tpl->assign('address', $address);
			$id = $address[SearchFields_Address::ID];
			
			list($open_tickets, $open_count) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
					new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'=',$address[SearchFields_Address::ID]),
				),
				1
			);
			$tpl->assign('open_count', $open_count);
			
			list($closed_tickets, $closed_count) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1),
					new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'=',$address[SearchFields_Address::ID]),
				),
				1
			);
			$tpl->assign('closed_count', $closed_count);
		}
		
		if (!empty($org_id)) {
			$org = DAO_ContactOrg::get($org_id);
			$tpl->assign('org_name',$org->name);
			$tpl->assign('org_id',$org->id);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Display
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/addresses/address_peek.tpl');
	}
	
	function showAddressTicketsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		
		if(null == ($address = DAO_Address::get($id)))
			return;
		
		if(null == ($search_view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH))) {
			$search_view = View_Ticket::createSearchView();
		}
		
		$search_view->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',$closed),
			SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'=',$address->email),
		);
		$search_view->renderPage = 0;
		
		C4_AbstractViewLoader::setView(CerberusApplication::VIEW_SEARCH, $search_view);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function showAddressBatchPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $address_ids = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('address_ids', $address_ids);
	    }
		
	    $custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
	    $tpl->assign('custom_fields', $custom_fields);
	    
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/addresses/address_bulk.tpl');
	}
	
	function showOrgBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $org_ids = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('org_ids', implode(',', $org_ids));
	    }
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/org_bulk.tpl');
	}
		
	function showOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$contact = DAO_ContactOrg::get($id);
		$tpl->assign('contact', $contact);

		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
				
		// View
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'contacts/orgs/org_peek.tpl');
	}
	
	function saveContactAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$db = DevblocksPlatform::getDatabaseService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$email = trim(DevblocksPlatform::importGPC($_REQUEST['email'],'string',''));
		@$first_name = trim(DevblocksPlatform::importGPC($_REQUEST['first_name'],'string',''));
		@$last_name = trim(DevblocksPlatform::importGPC($_REQUEST['last_name'],'string',''));
		@$contact_org = trim(DevblocksPlatform::importGPC($_REQUEST['contact_org'],'string',''));
		@$is_banned = DevblocksPlatform::importGPC($_REQUEST['is_banned'],'integer',0);
		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass'],'string','');
		@$unregister = DevblocksPlatform::importGPC($_REQUEST['unregister'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');
		
		if($active_worker->hasPriv('core.addybook.addy.actions.update')) {
			$contact_org_id = 0;
			
			if(!empty($contact_org)) {
				$contact_org_id = DAO_ContactOrg::lookup($contact_org, true);
				$contact_org = DAO_ContactOrg::get($contact_org_id);
			}
			
			// Common fields
			$fields = array(
				DAO_Address::FIRST_NAME => $first_name,
				DAO_Address::LAST_NAME => $last_name,
				DAO_Address::CONTACT_ORG_ID => $contact_org_id,
				DAO_Address::IS_BANNED => $is_banned,
			);
			
			// Are we clearing the contact's login?
			if($unregister) {
				$fields[DAO_Address::IS_REGISTERED] = 0;
				$fields[DAO_Address::PASS] = '';
				
			} elseif(!empty($pass)) { // Are we changing their password?
				$fields[DAO_Address::IS_REGISTERED] = 1;
				$fields[DAO_Address::PASS] = md5($pass);
			}
			
			if($id==0) {
				$fields = $fields + array(DAO_Address::EMAIL => $email);
				$id = DAO_Address::create($fields);
			}
			else {
				DAO_Address::update($id, $fields);
			}
	
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Address::ID, $id, $field_ids);
			
			/*
			 * Notify anything that wants to know when Address Peek saves.
			 */
		    $eventMgr = DevblocksPlatform::getEventService();
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'address.peek.saved',
	                array(
	                    'address_id' => $id,
	                    'changed_fields' => $fields,
	                )
	            )
		    );
		}
		
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView($view_id);
			$view->render();
		}
	}
	
	function saveOrgPropertiesAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer');
		
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
		@$street = DevblocksPlatform::importGPC($_REQUEST['street'],'string','');
		@$city = DevblocksPlatform::importGPC($_REQUEST['city'],'string','');
		@$province = DevblocksPlatform::importGPC($_REQUEST['province'],'string','');
		@$postal = DevblocksPlatform::importGPC($_REQUEST['postal'],'string','');
		@$country = DevblocksPlatform::importGPC($_REQUEST['country'],'string','');
		@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string','');
		@$website = DevblocksPlatform::importGPC($_REQUEST['website'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(!empty($id) && !empty($delete)) { // delete
			if($active_worker->hasPriv('core.addybook.org.actions.delete'))
				DAO_ContactOrg::delete($id);
				
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs')));
			return;
			
		} else { // create/edit
			if($active_worker->hasPriv('core.addybook.org.actions.update')) {
				$fields = array(
					DAO_ContactOrg::NAME => $org_name,
					DAO_ContactOrg::STREET => $street,
					DAO_ContactOrg::CITY => $city,
					DAO_ContactOrg::PROVINCE => $province,
					DAO_ContactOrg::POSTAL => $postal,
					DAO_ContactOrg::COUNTRY => $country,
					DAO_ContactOrg::PHONE => $phone,
					DAO_ContactOrg::WEBSITE => $website
				);
		
				if($id==0) {
					$id = DAO_ContactOrg::create($fields);
				}
				else {
					DAO_ContactOrg::update($id, $fields);	
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Org::ID, $id, $field_ids);
			}
		}		
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs','display',$id))); //,'fields'
	}	
	
	function saveOrgNoteAction() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer', 0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($org_id) && 0 != strlen(trim($content))) {
			$fields = array(
				DAO_Note::SOURCE_EXTENSION_ID => ChNotesSource_Org::ID,
				DAO_Note::SOURCE_ID => $org_id,
				DAO_Note::WORKER_ID => $active_worker->id,
				DAO_Note::CREATED => time(),
				DAO_Note::CONTENT => $content,
			);
			$note_id = DAO_Note::create($fields);
		}
		
		$org = DAO_ContactOrg::get($org_id);
		
		// Worker notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=contacts&a=orgs&d=display&id='.$org_id,true),
				DAO_WorkerEvent::TITLE => 'New Organization Note', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("%s\n%s notes: %s", $org->name, $active_worker->getName(), $content), // [TODO] Translate
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs','display',$org_id)));
	}
	
	// [TODO] This is redundant and should be handled by ?c=internal by passing a $return_path
	function deleteOrgNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($note = DAO_Note::get($id))) {
			if($note->worker_id == $active_worker->id || $active_worker->is_superuser) {
				DAO_Note::delete($id);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs','display',$org_id)));
	}
	
	function saveOrgPeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
		@$street = DevblocksPlatform::importGPC($_REQUEST['street'],'string','');
		@$city = DevblocksPlatform::importGPC($_REQUEST['city'],'string','');
		@$province = DevblocksPlatform::importGPC($_REQUEST['province'],'string','');
		@$postal = DevblocksPlatform::importGPC($_REQUEST['postal'],'string','');
		@$country = DevblocksPlatform::importGPC($_REQUEST['country'],'string','');
		@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string','');
		@$website = DevblocksPlatform::importGPC($_REQUEST['website'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(!empty($id) && !empty($delete)) { // delete
			if($active_worker->hasPriv('core.addybook.org.actions.delete'))
				DAO_ContactOrg::delete($id);
			
		} else { // create/edit
			if($active_worker->hasPriv('core.addybook.org.actions.update')) {
				$fields = array(
					DAO_ContactOrg::NAME => $org_name,
					DAO_ContactOrg::STREET => $street,
					DAO_ContactOrg::CITY => $city,
					DAO_ContactOrg::PROVINCE => $province,
					DAO_ContactOrg::POSTAL => $postal,
					DAO_ContactOrg::COUNTRY => $country,
					DAO_ContactOrg::PHONE => $phone,
					DAO_ContactOrg::WEBSITE => $website,
				);
		
				if($id==0) {
					$id = DAO_ContactOrg::create($fields);
				}
				else {
					DAO_ContactOrg::update($id, $fields);	
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Org::ID, $id, $field_ids);
			}
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->render();		
	}
	
	function doAddressBatchUpdateAction() {
	    @$address_id_str = DevblocksPlatform::importGPC($_REQUEST['address_ids'],'string');

	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);

		@$org_name = trim(DevblocksPlatform::importGPC($_POST['contact_org'],'string',''));
		@$sla = DevblocksPlatform::importGPC($_POST['sla'],'string','');
		@$is_banned = DevblocksPlatform::importGPC($_POST['is_banned'],'integer',0);

		$address_ids = DevblocksPlatform::parseCsvString($address_id_str);
		
		$do = array();
		
		// Do: Organization
		if(!empty($org_name)) {
			if(null != ($org_id = DAO_ContactOrg::lookup($org_name, true)))
				$do['org_id'] = $org_id;
		}
		// Do: SLA
		if('' != $sla)
			$do['sla'] = $sla;
		// Do: Banned
		if(0 != strlen($is_banned))
			$do['banned'] = $is_banned;
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $address_ids);
		
		$view->render();
		return;
	}

	function doOrgBulkUpdateAction() {
		// Checked rows
	    @$org_ids_str = DevblocksPlatform::importGPC($_REQUEST['org_ids'],'string');
		$org_ids = DevblocksPlatform::parseCsvString($org_ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Org fields
		@$country = trim(DevblocksPlatform::importGPC($_POST['country'],'string',''));

		$do = array();
		
		// Do: Country
		if(0 != strlen($country))
			$do['country'] = $country;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $org_ids);
		
		$view->render();
		return;
	}
	
	function doAddressQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Address';
		$defaults->id = View_Address::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_Address::DEFAULT_ID, $defaults);

        $params = array();
        
        if($query && false===strpos($query,'*'))
            $query = '*' . $query . '*';
        
        switch($type) {
            case "email":
                $params[SearchFields_Address::EMAIL] = new DevblocksSearchCriteria(SearchFields_Address::EMAIL,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
            case "org":
                $params[SearchFields_Address::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_Address::ORG_NAME,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
        }
        
        $view->params = $params;
        $view->renderPage = 0;
        $view->renderSortBy = null;
        
        C4_AbstractViewLoader::setView(View_Address::DEFAULT_ID,$view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','addresses')));
	}
	
	function doOrgQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_ContactOrg';
		$defaults->id = View_ContactOrg::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_ContactOrg::DEFAULT_ID, $defaults);

        $params = array();
        
        if($query && false===strpos($query,'*'))
            $query = '*' . $query . '*';
        
        switch($type) {
            case "name":
                $params[SearchFields_ContactOrg::NAME] = new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
            case "phone":
                $params[SearchFields_ContactOrg::PHONE] = new DevblocksSearchCriteria(SearchFields_ContactOrg::PHONE,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
        }
        
        $view->params = $params;
        $view->renderPage = 0;
        $view->renderSortBy = null;
        
        C4_AbstractViewLoader::setView(View_ContactOrg::DEFAULT_ID,$view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs')));
	}
	
	function getOrgsAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		
		list($orgs,$null) = DAO_ContactOrg::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE, $starts_with. '*'), 
			),
			25,
		    0,
		    SearchFields_ContactOrg::NAME,
		    true,
		    false
		);
		
		$list = array();
		
		foreach($orgs AS $val){
			$list[] = $val[SearchFields_ContactOrg::NAME];
		}
		
		echo json_encode($list);
		exit;
	}
	
	function getEmailAutoCompletionsAction() {
		$db = DevblocksPlatform::getDatabaseService();
		@$query = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		
		$starts_with = strtolower($query) . '%';
		
		$sql = sprintf("SELECT first_name, last_name, email, num_nonspam ".
			"FROM address ".
			"WHERE is_banned = 0 ".
			"AND (lower(email) LIKE %s ".
			"OR lower(concat(first_name,' ',last_name)) LIKE %s ".
			"OR lower(last_name) LIKE %s) ".
			"ORDER BY num_nonspam DESC ".
			"LIMIT 0,25",
			$db->qstr($starts_with),
			$db->qstr($starts_with),
			$db->qstr($starts_with)
		);
		$rs = $db->Execute($sql);
		
		$list = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$first = $row['first_name'];
			$last = $row['last_name'];
			$email = $row['email'];
			$num_nonspam = $row['num_nonspam'];
			
			$personal = sprintf("%s%s%s",
				(!empty($first)) ? $first : '',
				(!empty($first) && !empty($last)) ? ' ' : '',
				(!empty($last)) ? $last : ''
			);

			$label = sprintf("%s%s (%d messages)",
				!empty($personal) ? $personal : '',
				!empty($personal) ? (" &lt;".$email."&gt;") : $email,
				$num_nonspam
			);

			$entry = new stdClass();
			$entry->label = $label;
			$entry->value = $email; 
			
			$list[] = $entry;
		}
		
		mysql_free_result($rs);

		echo json_encode($list);
		exit;
	}
	
	function getCountryAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT DISTINCT country AS country ".
			"FROM contact_org ".
			"WHERE country != '' ".
			"AND country LIKE %s ".
			"ORDER BY country ASC ".
			"LIMIT 0,25",
			$db->qstr($starts_with.'%')
		);
		$rs = $db->Execute($sql);
		
		$list = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$list[] = $row['country'];
		}
		
		mysql_free_result($rs);
		
		echo json_encode($list);
		exit;
	}
};
