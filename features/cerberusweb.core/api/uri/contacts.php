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
class ChContactsPage extends CerberusPageExtension {
	function getActivity() {
		return new Model_Activity('activity.address_book');
	}
	
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
		$stack = $response->path;

		@array_shift($stack); // contacts
		@$selected_tab = array_shift($stack);
		
		// Remember the last tab/URL
		$visit = CerberusApplication::getVisit();
		if(null == $selected_tab) {
			$selected_tab = $visit->get(Extension_AddressBookTab::POINT, '');
		}
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
								$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
								$tpl->assign('custom_fields', $custom_fields);
								break;
							case 'addys':
								$fields = DAO_Address::getFields();
								$tpl->assign('fields',$fields);
								$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
								$tpl->assign('custom_fields', $custom_fields);
								break;
						}
						
						$tpl->display('devblocks:cerberusweb.core::contacts/import/mapping.tpl');
						return;
						break;
				}
				break;
			
			case 'addresses':
				switch(@array_shift($stack)) {
					case 'display':
						$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.address.tab', false);
						$tpl->assign('tab_manifests', $tab_manifests);
						
						$id = intval(array_shift($stack));
						
						$address = DAO_Address::get($id);
						$tpl->assign('address', $address);
						
						$tpl->display('devblocks:cerberusweb.core::contacts/addresses/display/index.tpl');
						return;
						break;
						
				} // switch (action)
				break;
				
			case 'orgs':
				switch(@array_shift($stack)) {
					case 'display':
						$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.org.tab', false);
						$tpl->assign('tab_manifests', $tab_manifests);
						
						$id = intval(array_shift($stack));
						
						$contact = DAO_ContactOrg::get($id);
						$tpl->assign('contact', $contact);
						
						// Tabs
						
						$people_count = DAO_Address::getCountByOrgId($contact->id);
						$tpl->assign('people_total', $people_count);
						
						$tpl->display('devblocks:cerberusweb.core::contacts/orgs/display.tpl');
						return;
						break;
						
				} // switch (action)
				break;
				
			case 'people':
				@$id = array_shift($stack);
				
				if(!empty($id) && is_numeric($id)) {
					$person = DAO_ContactPerson::get($id);
					$tpl->assign('person', $person);
					
					$tpl->display('devblocks:cerberusweb.core::contacts/people/display/index.tpl');
					return;
				}
				break;
				
		} // switch (tab)
		
		$tab_manifests = DevblocksPlatform::getExtensions(Extension_AddressBookTab::POINT, false);
		uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('tab_manifests', $tab_manifests);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/index.tpl');
		return;
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$visit = CerberusApplication::getVisit();

		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_AddressBookTab) {
				$visit->set(Extension_AddressBookTab::POINT, $inst->manifest->params['uri']);
				$inst->showTab();
		}
	}
	
	function showOrgsTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(Extension_AddressBookTab::POINT, 'orgs');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_ContactOrg';
		$defaults->id = View_ContactOrg::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_ContactOrg::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		$tpl->assign('contacts_page', 'orgs');
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/index.tpl');
	}
	
	function showAddysTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(Extension_AddressBookTab::POINT, 'addresses');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Address';
		$defaults->id = View_Address::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_Address::DEFAULT_ID, $defaults);
		
		$tpl->assign('view', $view);
		$tpl->assign('contacts_page', 'addresses');
		
		$tpl->display('devblocks:cerberusweb.core::contacts/addresses/index.tpl');
	}
	
	function showPeopleTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(Extension_AddressBookTab::POINT, 'people');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_ContactPerson';
		$defaults->id = View_ContactPerson::DEFAULT_ID;
		//$defaults->paramsDefault = array(
			//SearchFields_Example::PROPERTY => new DevblocksSearchCriteria(SearchFields_Example::PROPERTY,'=',1),
		//);
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		
		//$view->addParamsDefault(array(
			//SearchFields_Example::PROPERTY => new DevblocksSearchCriteria(SearchFields_Example::PROPERTY,'=',1),
		//), true);
		
		$tpl->assign('view', $view);
		$tpl->assign('contacts_page', 'people');
		
		$tpl->display('devblocks:cerberusweb.core::contacts/people/index.tpl');
	}	
	
	function showImportTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
	
		$visit->set(Extension_AddressBookTab::POINT, 'import');
		
		if(!$active_worker->hasPriv('core.addybook.import'))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->display('devblocks:cerberusweb.core::contacts/import/index.tpl');
	}
	
	function showPeopleBulkUpdateAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
				$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $ids = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', $ids);
	    }
		
	    // Custom fields
	    $custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT_PERSON);
	    $tpl->assign('custom_fields', $custom_fields);
	    
		// Groups
		//$groups = DAO_Group::getAll();
		//$tpl->assign('groups', $groups);
		
		// Broadcast
		//CerberusContexts::getContext(CerberusContexts::CONTEXT_CONTACT_PERSON, null, $token_labels, $token_values);
		//$tpl->assign('token_labels', $token_labels);
	    
		$tpl->display('devblocks:cerberusweb.core::contacts/people/bulk.tpl');
	}
	
	function doPeopleBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Fields
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		$do = array();
		
		// Do: Delete
		if(!empty($do_delete))
			$do['delete'] = 1;
			
		// Do: Custom fields
		//$do = DAO_CustomFieldValue::handleBulkPost($do);

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
	
	function viewOrgsExploreAction() {
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=contacts&tab=orgs', true),
//					'toolbar_extension_id' => '',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $org_id => $row) {
				if($org_id==$explore_from)
					$orig_pos = $pos;
				
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
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function viewPeopleExploreAction() {
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=contacts&tab=people', true),
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
					'url' => $url_writer->write(sprintf("c=contacts&tab=people&id=%d", $id), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
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
				$context_ext_id = ($type=="orgs") ? CerberusContexts::CONTEXT_ORG : CerberusContexts::CONTEXT_ADDRESS;
				DAO_CustomFieldValue::formatAndSetFieldValues($context_ext_id, $id, $custom_fields, $is_blank_unset, true, true);
			}
			
		}
		
		@unlink($csv_file); // nuke the imported file
		
		$visit->set('import.last.csv',null);
		$visit->set('import.last.type',null);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','import')));
	}
	
	// Ajax
	function showOrgTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_OrgTab) {
			$inst->showTab();
		}
	}
	
	function showTabPeopleAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
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
		$view->addParams(array(
			new DevblocksSearchCriteria(SearchFields_Address::CONTACT_ORG_ID,'=',$org)
		), true);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/tabs/people.tpl');
		exit;
	}
	
	function showTabPeopleAddressesAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$contact_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		$person = DAO_ContactPerson::get($contact_id);
		$tpl->assign('person', $person);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */

		$view = C4_AbstractViewLoader::getView('contact_person_addresses');
		
		// All contact addresses
		$contact_addresses = $person->getAddresses();
		
		if(null == $view) {
			$view = new View_Address();
			$view->id = 'contact_person_addresses';
			$view->name = '';
			$view->view_columns = array(
				SearchFields_Address::FIRST_NAME,
				SearchFields_Address::LAST_NAME,
				SearchFields_Address::ORG_NAME,
			);
			$view->renderLimit = 10;
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Address::EMAIL;
			$view->renderSortAsc = true;
		}

		@$view->name = 'Verified Email Addresses';
		$view->addParams(array(
			SearchFields_Address::ID => new DevblocksSearchCriteria(SearchFields_Address::ID,'in',array_keys($contact_addresses)),
		), true);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/people/display/addresses_tab.tpl');
		exit;
	}	
	
	function showTabPeopleHistoryAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$contact_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		$person = DAO_ContactPerson::get($contact_id);
		$tpl->assign('person', $person);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */

		$view = C4_AbstractViewLoader::getView('contact_person_history');
		
		// All contact addresses
		$contact_addresses = $person->getAddresses();
		
		if(null == $view) {
			$view = new View_Ticket();
			$view->id = 'contact_person_history';
			$view->name = $translate->_('addy_book.history.view_title');
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TICKET_TEAM_ID,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
			);
			$view->renderLimit = 10;
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$view->renderSortAsc = false;
		}

		@$view->name = $translate->_('ticket.requesters') . ": " . intval(count($contact_addresses)) . ' address(es)';
		$view->addParams(array(
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($contact_addresses)),
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,DevblocksSearchCriteria::OPER_EQ,0)
		), true);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/people/display/history_tab.tpl');
		exit;
	}	
	
	function showTabHistoryAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
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
			$tickets_view->renderLimit = 10;
			$tickets_view->renderPage = 0;
			$tickets_view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$tickets_view->renderSortAsc = false;
		}

		@$tickets_view->name = $translate->_('ticket.requesters') . ": " . htmlspecialchars($contact->name) . ' - ' . intval(count($people)) . ' contact(s)';
		$tickets_view->addParams(array(
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($people)),
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,DevblocksSearchCriteria::OPER_EQ,0)
		), true);
		$tpl->assign('contact_history', $tickets_view);
		
		C4_AbstractViewLoader::setView($tickets_view->id,$tickets_view);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/tabs/history.tpl');
		exit;
	}
	
	function showAddressPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$address_id = DevblocksPlatform::importGPC($_REQUEST['address_id'],'integer',0);
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Handle context links ([TODO] as an optional array)
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Display
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('devblocks:cerberusweb.core::contacts/addresses/address_peek.tpl');
	}
	
	function findTicketsAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'string','');
		
		if(null == ($address = DAO_Address::lookupAddress($email, false)))
			return;
		
		if(null == ($search_view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH))) {
			$search_view = View_Ticket::createSearchView();
		}
		
		$search_view->removeAllParams();
		
		if(!empty($address))
			$search_view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'=',$address->email));

		if(0 != strlen($closed))
			$search_view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',$closed));
			
		$search_view->renderPage = 0;
		
		C4_AbstractViewLoader::setView(CerberusApplication::VIEW_SEARCH, $search_view);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function showAddressBatchPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
				$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $address_ids = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('address_ids', $address_ids);
	    }
		
	    // Custom fields
	    $custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
	    $tpl->assign('custom_fields', $custom_fields);
	    
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
	    
		$tpl->display('devblocks:cerberusweb.core::contacts/addresses/address_bulk.tpl');
	}
	
	function showOrgBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
				$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $org_ids = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('org_ids', implode(',', $org_ids));
	    }
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/org_bulk.tpl');
	}
	
	function showOrgMergePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/org_merge_peek.tpl');
	}
	
	function showOrgMergeContinuePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_ids = DevblocksPlatform::importGPC($_REQUEST['org_id'],'array',array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$workers = DAO_Worker::getAll();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('org_ids', $org_ids);

		// Sanitize
		if(is_array($org_ids) && !empty($org_ids))
		foreach($org_ids as $idx => $org_id) {
			$org_ids[$idx] = intval($org_id);
			if(empty($org_ids[$idx]))
				unset($org_ids[$idx]);
		}
		
		// Give the implode something to do
		if(empty($org_ids))
			$org_ids = array(-1);

		// Retrieve orgs
		$orgs = DAO_ContactOrg::getWhere(sprintf("%s IN (%s)",
			DAO_ContactOrg::ID,
			implode(',', $org_ids)
		));
		$tpl->assign('orgs', $orgs);
		
		// Index unique values
		$properties = array(
			'name' => 'common.name',
			'street' => 'contact_org.street',
			'city' => 'contact_org.city',
			'province' => 'contact_org.province',
			'postal' => 'contact_org.postal',
			'country' => 'contact_org.country',
			'phone' => 'contact_org.phone',
			'website' => 'contact_org.website',
		);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $org_ids);
		$tpl->assign('custom_fields', $custom_fields);
		
		foreach($custom_fields as $cfield_id => $cfield) { /* @var $cfield Model_CustomField */
			$properties['cf_' . $cfield_id] = $cfield->name;
		}

		// Combinations
		
		$combinations = array();
		foreach($properties as $prop => $label) {
			$combinations[$prop] = array(
				'label' => $label,
				'values' => array(),
			);
			$vals = array();
			$is_multival = false;
			
			if('cf_' == substr($prop,0,3)) { // custom field
				$cfield_id = substr($prop, 3);
				if(!isset($custom_fields[$cfield_id]))
					continue;
				$cfield_type = $custom_fields[$cfield_id]->type;
				
				foreach($custom_field_values as $org_id => $cfield_keyvalues) {
					if(!is_array($cfield_keyvalues))
						continue;
						
					foreach($cfield_keyvalues as $cfield_key => $cfield_value) {
						$val = null;
						
						// We're only interested in a particular field value
						if($cfield_key != $cfield_id)
							continue;
	
						if(empty($cfield_value))
							continue;
							
						if(is_array($cfield_value)) { // multi-value
							$val = $cfield_value;
							
						} else { // single-value
							if('W' == $cfield_type) { // use worker name
								if(isset($workers[$cfield_value]))
									$val = $workers[$cfield_value]->getName();								
							} elseif('C' == $cfield_type) { // checkbox
								$val = !empty($cfield_value) ? 'Yes' : 'No';
							} else {
								$val = strtr($cfield_value,"\r\n",' '); // no newlines
							}
							
						}
						
						if(!empty($val))
							$vals[$org_id] = $val;
					}
				}
				
				switch($cfield_type) {
					case 'X':
						// Combine all the values into one array
						$newvals = array();
						foreach($vals as $val) {
							if(is_array($val)) {
								foreach($val as $v)
									$newvals[] = $v;
							} else {
								$newvals[] = $val;
							}
						}
						
						// Only keep unique vlaues
						if(!empty($newvals))
							$vals = array(implode(', ', array_unique($newvals)));
						else
							$vals = array();
							
						break;
				}
				
			} else { // record
				foreach($orgs as $org_id => $org) {
					$val = strtr($org->$prop,"\r\n",' '); // no newlines
					if(empty($val))
						continue;
					$vals[$org_id] = $val;
				}
				
			}
			
			if(!empty($vals))
				$combinations[$prop]['values'] = array_unique($vals);
		}
		$tpl->assign('combinations', $combinations);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/org_merge_continue_peek.tpl');
	}
	
	function saveOrgMergePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_ids = DevblocksPlatform::importGPC($_REQUEST['org_id'],'array',array());
		@$properties = DevblocksPlatform::importGPC($_REQUEST['prop'],'array',array());
		
		$db = DevblocksPlatform::getDatabaseService();
		$eventMgr = DevblocksPlatform::getEventService();
		$date = DevblocksPlatform::getDateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.addybook.org.actions.merge'))
			return false;		
		
		// Sanitize
		if(is_array($org_ids) && !empty($org_ids))
		foreach($org_ids as $idx => $org_id) {
			$org_ids[$idx] = intval($org_id);
			if(empty($org_ids[$idx]))
				unset($org_ids[$idx]);
		}

		// We need at least two records to merge
		if(count($org_ids) < 2)
			return;
		
		// Retrieve orgs
		$orgs = DAO_ContactOrg::getWhere(
			sprintf("%s IN (%s)",
				DAO_ContactOrg::ID,
				(empty($org_ids) ? array(-1) : implode(',', $org_ids)) // handle empty
			),
			DAO_ContactOrg::ID,
			true
		);

		// Create a new destination org
		$oldest_org_id = key($orgs); /* @var $oldest_org Model_ContactOrg */
		$merge_to_id = DAO_ContactOrg::create(array(
			DAO_ContactOrg::NAME => $orgs[$oldest_org_id]->name,
			DAO_ContactOrg::CREATED => $orgs[$oldest_org_id]->created,
		));
		
		// Merge records
		
		$fields = array();
		$custom_fields = array();
		$custom_field_types = DAO_CustomField::getAll();
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, array_keys($orgs));
		
		foreach($properties as $k => $v) {
			$is_cfield = ('cf_' == substr($k,0,3));
			
			// If an empty property field, skip
			if(!$is_cfield && (empty($v) || !isset($orgs[$v])))
				continue;
				
			switch($k) {
				case 'name':
					$fields[DAO_ContactOrg::NAME] = $orgs[$v]->name;
					break;
				case 'street':
					$fields[DAO_ContactOrg::STREET] = $orgs[$v]->street;
					break;
				case 'city':
					$fields[DAO_ContactOrg::CITY] = $orgs[$v]->city;
					break;
				case 'province':
					$fields[DAO_ContactOrg::PROVINCE] = $orgs[$v]->province;
					break;
				case 'postal':
					$fields[DAO_ContactOrg::POSTAL] = $orgs[$v]->postal;
					break;
				case 'country':
					$fields[DAO_ContactOrg::COUNTRY] = $orgs[$v]->country;
					break;
				case 'phone':
					$fields[DAO_ContactOrg::PHONE] = $orgs[$v]->phone;
					break;
				case 'website':
					$fields[DAO_ContactOrg::WEBSITE] = $orgs[$v]->website;
					break;
				// Custom fields
				default:
					if(!$is_cfield)
						break;
						
					$cfield_id = intval(substr($k,3));
					
					if(!isset($custom_field_types[$cfield_id]))
						break;
					
					$is_cfield_multival = $custom_field_types[$cfield_id];
					
					if(empty($v)) { // no org_id
						// Handle aggregation of multi-value fields when blank
						if($is_cfield_multival) {
							foreach($orgs as $org_id => $org) {
								if(isset($custom_field_values[$org_id][$cfield_id])) {
									$custom_fields[$cfield_id] = array_merge(
										(isset($custom_fields[$cfield_id]) ? $custom_fields[$cfield_id] : array()),
										$custom_field_values[$org_id][$cfield_id]
									);
								}
							}
						}
						
					} else { // org_id exists
						if(isset($orgs[$v]) && isset($custom_field_values[$v][$cfield_id])) {
							$val = $custom_field_values[$v][$cfield_id];
							
							// Overrides
							switch($custom_field_types[$cfield_id]->type) {
								case Model_CustomField::TYPE_CHECKBOX:
									$val = !empty($val) ? 1 : 0;
									break;
								case Model_CustomField::TYPE_DATE:
									$val = $date->formatTime(null, $val, false);
									break;
							}
							$custom_fields[$cfield_id] = $val;
						}
					}
					
					break;
			}
		}
		
		if(!empty($fields))
			DAO_ContactOrg::update($merge_to_id, $fields);
		
		if(!empty($custom_fields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ORG, $merge_to_id, $custom_fields, true, true, false);

		// Merge connections
		DAO_ContactOrg::mergeIds($org_ids, $merge_to_id);
		
		foreach($org_ids as $from_org_id) {
			if($from_org_id == $properties['name'])
				continue;
			
			/*
			 * Log activity (org.merge)
			 */
			$entry = array(
				//{{actor}} merged organization {{source}} with organization {{target}}
				'message' => 'activities.org.merge',
				'variables' => array(
					'source' => sprintf("%s", $orgs[$from_org_id]->name),
					'target' => sprintf("%s", $fields[DAO_ContactOrg::NAME]),
					),
				'urls' => array(
					'source' => sprintf("c=contacts&tab=orgs&p=display&id=%d-%s",$from_org_id,DevblocksPlatform::strToPermalink($orgs[$from_org_id]->name)),
					'target' => sprintf("c=display&tab=orgs&p=display&id=%d-%s",$merge_to_id,DevblocksPlatform::strToPermalink($fields[DAO_ContactOrg::NAME])),
					)
			);
			CerberusContexts::logActivity('org.merge', CerberusContexts::CONTEXT_ORG, $merge_to_id, $entry);
		}
		
		// Fire a merge event for plugins
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'org.merge',
                array(
                    'merge_to_id' => $merge_to_id,
                    'merge_from_ids' => $org_ids,
                )
            )
	    );
	    
	    // Nuke the source orgs
	    DAO_ContactOrg::delete($org_ids);
		
	    // [TODO] Redirect
	}
	
	function showOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		// Handle context links ([TODO] as an optional array)
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
				
		$contact = DAO_ContactOrg::get($id);
		$tpl->assign('contact', $contact);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
				
		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_ORG, $id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		// View
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/org_peek.tpl');
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
			
			if($id==0) {
				$fields = $fields + array(DAO_Address::EMAIL => $email);
				$id = DAO_Address::create($fields);
				
				@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
				if($is_watcher)
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_ADDRESS, $id, $active_worker->id);
				
				// Context Link (if given)
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
				@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
				if(!empty($id) && !empty($context) && !empty($context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_ADDRESS, $id, $context, $context_id);
				}
				
			} else {
				DAO_Address::update($id, $fields);
			}
	
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_ADDRESS, $id, $field_ids);
			
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
	
	function saveOrgPeekAction() {
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
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();

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
					
					@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
					if($is_watcher)
						CerberusContexts::addWatchers(CerberusContexts::CONTEXT_ORG, $id, $active_worker->id);
					
					// Context Link (if given)
					@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
					@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
					if(!empty($id) && !empty($context) && !empty($context_id)) {
						DAO_ContextLink::setLink(CerberusContexts::CONTEXT_ORG, $id, $context, $context_id);
					}
				}
				else {
					DAO_ContactOrg::update($id, $fields);	
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_ORG, $id, $field_ids);
				
				if(!empty($comment)) {
					@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
					
					$fields = array(
						DAO_Comment::CREATED => time(),
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_ORG,
						DAO_Comment::CONTEXT_ID => $id,
						DAO_Comment::COMMENT => $comment,
						DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
					);
					$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
				}				
			}
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->render();		
	}
	
	function doAddressBatchUpdateAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    $ids = array();
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);

		@$org_name = trim(DevblocksPlatform::importGPC($_POST['contact_org'],'string',''));
		@$sla = DevblocksPlatform::importGPC($_POST['sla'],'string','');
		@$is_banned = DevblocksPlatform::importGPC($_POST['is_banned'],'integer',0);

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
		
		// Broadcast: Compose
		if($active_worker->hasPriv('core.addybook.addy.view.actions.broadcast')) {
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
				@$address_id_str = DevblocksPlatform::importGPC($_REQUEST['address_ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($address_id_str);
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

	function doAddressBulkUpdateBroadcastTestAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$view = C4_AbstractViewLoader::getView($view_id);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if($active_worker->hasPriv('core.addybook.addy.view.actions.broadcast')) {
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);

			@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
			@$ids = DevblocksPlatform::importGPC($_REQUEST['address_ids'],'string','');
			
			// Filter to checked
			if('checks' == $filter && !empty($ids)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_Address::ID,'in',explode(',', $ids)));
			}
			
			$results = $view->getDataSample(1);
			
			if(empty($results)) {
				$success = false;
				$output = "There aren't any rows in this view!";
				
			} else {
				@$addy = DAO_Address::get(current($results));
				
				// Try to build the template
				CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $addy, $token_labels, $token_values);

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
			$tpl->assign('output', $output);
			
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
		}
	}	
	
	function doOrgBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    $ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Org fields
		@$country = trim(DevblocksPlatform::importGPC($_POST['country'],'string',''));

		$do = array();
		
		// Do: Country
		if(0 != strlen($country))
			$do['country'] = $country;
			
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
			    @$org_ids_str = DevblocksPlatform::importGPC($_REQUEST['org_ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($org_ids_str);
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
        
        $view->addParams($params, true);
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
        
        $view->addParams($params, true);
        $view->renderPage = 0;
        $view->renderSortBy = null;
        
        C4_AbstractViewLoader::setView(View_ContactOrg::DEFAULT_ID,$view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs')));
	}
	
	function getOrgsAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');		
		
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
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}
	
	function getEmailAutoCompletionsAction() {
		$db = DevblocksPlatform::getDatabaseService();
		@$query = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');		
		
		if(false !== (strpos($query,'@'))) { // email search
			$sql = sprintf("SELECT first_name, last_name, email, num_nonspam ".
				"FROM address ".
				"WHERE is_banned = 0 ".
				"AND email LIKE %s ".
				"ORDER BY num_nonspam DESC ".
				"LIMIT 0,25",
				$db->qstr($query . '%')
			);
		} elseif(false !== (strpos($query,' '))) { // first+last
			$sql = sprintf("SELECT first_name, last_name, email, num_nonspam ".
				"FROM address ".
				"WHERE is_banned = 0 ".
				"AND concat(first_name,' ',last_name) LIKE %s ".
				"ORDER BY num_nonspam DESC ".
				"LIMIT 0,25",
				$db->qstr($query . '%')
			);
		} else { // first, last, or email 
			$sql = sprintf("SELECT first_name, last_name, email, num_nonspam ".
				"FROM address ".
				"WHERE is_banned = 0 ".
				"AND (email LIKE %s ".
				"OR first_name LIKE %s ".
				"OR last_name LIKE %s) ".
				"ORDER BY num_nonspam DESC ".
				"LIMIT 0,25",
				$db->qstr($query . '%'),
				$db->qstr($query . '%'),
				$db->qstr($query . '%')
			);
		}
		
		$rs = $db->Execute($sql);
		
		$list = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$first = $row['first_name'];
			$last = $row['last_name'];
			$email = strtolower($row['email']);
			$num_nonspam = intval($row['num_nonspam']);
			
			$personal = sprintf("%s%s%s",
				(!empty($first)) ? $first : '',
				(!empty($first) && !empty($last)) ? ' ' : '',
				(!empty($last)) ? $last : ''
			);

			$label = sprintf("%s%s (%d messages)",
				!empty($personal) ? $personal : '',
				!empty($personal) ? (" <".$email.">") : $email,
				$num_nonspam
			);

			$entry = new stdClass();
			$entry->label = $label;
			$entry->value = $email; 
			
			$list[] = $entry;
		}
		
		mysql_free_result($rs);

		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}
	
	function getCountryAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		
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
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}
};
