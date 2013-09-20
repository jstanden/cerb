<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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

class ChInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';

	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;

		$stack = $request->path;
		array_shift($stack); // internal

		@$action = array_shift($stack) . 'Action';

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;

			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
				break;
		}
	}

	function handleSectionActionAction() {
		@$section_uri = DevblocksPlatform::importGPC($_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	// Post
	function doStopTourAction() {
		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences')));
	}

	// Imposter mode
	
	function suAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$active_worker->is_superuser)
			return;
		
		if($active_worker->id == $worker_id)
			return;
		
		if(null != ($switch_worker = DAO_Worker::get($worker_id))) {
			/*
			 * Log activity (worker.impersonated)
			 */
			$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'an unknown IP';
			
			$entry = array(
				//{{actor}} impersonated {{target}} from {{ip}}
				'message' => 'activities.worker.impersonated',
				'variables' => array(
					'target' => $switch_worker->getName(),
					'ip' => $ip_address,
					),
				'urls' => array(
					'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_WORKER, $switch_worker->id),
					)
			);
			CerberusContexts::logActivity('worker.impersonated', CerberusContexts::CONTEXT_WORKER, $worker_id, $entry);
			
			// Imposter
			if($visit->isImposter() && $imposter = $visit->getImposter()) {
				if($worker_id == $imposter->id) {
					$visit->setImposter(null);
				}
			} else if(!$visit->isImposter()) {
				$visit->setImposter($active_worker);
			}
			
			$visit->setWorker($switch_worker);
		}
	}
	
	function suRevertAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if($visit->isImposter()) {
			if(null != ($imposter = $visit->getImposter())) {
				$visit->setWorker($imposter);
				$visit->setImposter(null);
			}
		}
		
	}
	
	// Contexts

	function showTabContextLinksAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		if(!empty($point))
			$visit->set($point, 'links');

		// Context Links

		$contexts = DAO_ContextLink::getDistinctContexts($context, $context_id);
		$all_contexts = Extension_DevblocksContext::getAll(false);
		
		// Only valid extensions
		$contexts = array_intersect($contexts, array_keys($all_contexts));
		
		$contexts = array_diff($contexts, array(
			CerberusContexts::CONTEXT_CUSTOM_FIELDSET, // hide custom fieldset
			CerberusContexts::CONTEXT_WORKER, // hide workers
		));
		
		$tpl->assign('contexts', $contexts);
		
		$tpl->display('devblocks:cerberusweb.core::context_links/tab.tpl');
		
		unset($contexts);
	}

	function initConnectionsViewAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$to_context = DevblocksPlatform::importGPC($_REQUEST['to_context'],'string');
		
		if(empty($context) || empty($context_id) || empty($to_context))
			return;
			
		if(null == ($ext_context = DevblocksPlatform::getExtension($to_context, true)))
			return;

		if(!$ext_context instanceof Extension_DevblocksContext)
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
			
		if(null != ($view = $ext_context->getView($context, $context_id))) {
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
			$tpl->clearAssign('view');
		}

		unset($view);
		unset($ext_content);
	}
	
	/*
	 * Popups
	 */
	
	function showPeekPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextPeek))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();

		// Handle context links
		
		@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
		@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
		$tpl->assign('link_context', $link_context);
		$tpl->assign('link_context_id', $link_context_id);

		// Template
		
		$context_ext->renderPeekPopup($context_id, $view_id);
	}

	/*
	 * Import
	 */
	
	function showImportPopupAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextImport))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();

		// Template
		
		$tpl->assign('layer', $layer);
		$tpl->assign('context', $context_ext->id);
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/import/popup_upload.tpl');
	}
	
	function parseImportFileAction() {
		@$csv_file = $_FILES['csv_file'];
		
		// [TODO] Return false in JSON if file is empty, etc.
		
		if(!is_array($csv_file) || !isset($csv_file['tmp_name']) || empty($csv_file['tmp_name'])) {
			exit;
		}
		
		$filename = basename($csv_file['tmp_name']);
		$newfilename = APP_TEMP_PATH . '/' . $filename;
		
		if(!rename($csv_file['tmp_name'], $newfilename)) {
			exit;
		}
		
		$visit = CerberusApplication::getVisit();
		$visit->set('import.last.csv', $newfilename);
		
		exit;
	}

	function showImportMappingPopupAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
	
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
	
		if(!($context_ext instanceof IDevblocksContextImport))
			return;

		// Keys
		$keys = $context_ext->importGetKeys();
		$tpl->assign('keys', $keys);
	
		// Read the first line from the file
		$csv_file = $visit->get('import.last.csv','');
		$fp = fopen($csv_file, 'rt');
		$columns = fgetcsv($fp);
		fclose($fp);

		$tpl->assign('columns', $columns);
		
		// Template
	
		$tpl->assign('layer', $layer);
		$tpl->assign('context', $context_ext->id);
		$tpl->assign('view_id', $view_id);
	
		$tpl->display('devblocks:cerberusweb.core::internal/import/popup_mapping.tpl');
	}
	
	function doImportAction() {
		//header("Content-Type: application/json");

		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$is_preview = DevblocksPlatform::importGPC($_REQUEST['is_preview'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		@$column = DevblocksPlatform::importGPC($_REQUEST['column'],'array',array());
		@$column_custom = DevblocksPlatform::importGPC($_REQUEST['column_custom'],'array',array());
		@$sync_dupes = DevblocksPlatform::importGPC($_REQUEST['sync_dupes'],'array',array());
		
		$visit = CerberusApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();

		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextImport))
			return;

		$view_class = $context_ext->getViewClass();
		$view = new $view_class; /* @var $view C4_AbstractView */
		
		$keys = $context_ext->importGetKeys();

		// Counters
		$line_number = 0;
		
		// CSV
		$csv_file = $visit->get('import.last.csv','');
		
		$fp = fopen($csv_file, "rt");
		if(!$fp)
			return;
		
		// Do we need to consume a first row of headings?
		@fgetcsv($fp, 8192, ',', '"');
		
		while(!feof($fp)) {
			$parts = fgetcsv($fp, 8192, ',', '"');

			if($is_preview && $line_number > 25)
				continue;
			
			if(empty($parts) || (1==count($parts) && is_null($parts[0])))
				continue;
			
			$line_number++;

			// Snippets dictionary
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			$dict = new DevblocksDictionaryDelegate(array());
				
			foreach($parts as $idx => $part) {
				$col = 'column_' . ($idx + 1); // 0-based to 1-based
				$dict->$col = $part;
			}

			// Meta
			$meta = array(
				'line' => $parts,
				'fields' => $field,
				'columns' => $column,
				'virtual_fields' => array(),
			);
			
			$fields = array();
			$custom_fields = array();
			$sync_fields = array();
			
			foreach($field as $idx => $key) {
				if(!isset($keys[$key]))
					continue;
				
				$col = $column[$idx];

				// Are we providing custom values?
				if($col == 'custom') {
					@$val = $tpl_builder->build($column_custom[$idx], $dict);
					
				// Are we referencing a column number from the CSV file?
				} elseif(is_numeric($col)) {
					$val = $parts[$col];
				
				// Otherwise, use a literal value.
				} else {
					$val = $col;
				}

				if(0 == strlen($val))
					continue;
				
				// What type of field is this?
				$type = $keys[$key]['type'];
				$value = null;

				// Can we automatically format the value?
				
				switch($type) {
					case 'ctx_' . CerberusContexts::CONTEXT_ADDRESS:
						if(null != ($addy = DAO_Address::lookupAddress($val, true))) {
							$value = $addy->id;
						}
						break;
						
					case 'ctx_' . CerberusContexts::CONTEXT_ORG:
						if(null != ($org_id = DAO_ContactOrg::lookup($val, true))) {
							$value = $org_id;
						}
						break;
						
					case Model_CustomField::TYPE_CHECKBOX:
						// Attempt to interpret bool values
						if(
							false !== stristr($val, 'yes')
							|| false !== stristr($val, 'y')
							|| false !== stristr($val, 'true')
							|| false !== stristr($val, 't')
							|| intval($val) > 0
						) {
							$value = 1;
							
						} else {
							$value = 0;
						}
						break;
						
					case Model_CustomField::TYPE_DATE:
						@$value = !is_numeric($val) ? strtotime($val) : $val;
						break;
						
					case Model_CustomField::TYPE_DROPDOWN:
						// [TODO] Add where missing
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						// [TODO] Add where missing
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_MULTI_LINE:
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_NUMBER:
						$value = intval($val);
						break;
						
					case Model_CustomField::TYPE_SINGLE_LINE:
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_URL:
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_WORKER:
					case 'ctx_' . CerberusContexts::CONTEXT_WORKER:
						$workers = DAO_Worker::getAllActive();

						$worker_names = array();
						$val_worker_id = 0;
						
						if(0 == strcasecmp($val, 'me')) {
							$val_worker_id = $active_worker->id;
						}
							
						foreach($workers as $worker_id => $worker) {
							if(!empty($val_worker_id))
								break;
							
							$worker_name = $worker->getName();
								
							if(false !== stristr($worker_name, $val)) {
								$val_worker_id = $worker_id;
							}
						}
						
						$value = $val_worker_id;
						break;
						
					default:
						$value = $val;
						break;
				}

				/* @var $context_ext IDevblocksContextImport */
				$value = $context_ext->importKeyValue($key, $value);
				
				if($is_preview) {
					echo sprintf("%s => %s<br>",
						$keys[$key]['label'],
						$value
					);
				}
				
				if(!is_null($value)) {
					$val = $value;
					
					// Are we setting a custom field?
					$cf_id = null;
					if('cf_' == substr($key,0,3)) {
						$cf_id = substr($key,3);
					}
					
					// Is this a virtual field?
					if(substr($key,0,1) == '_') {
						$meta['virtual_fields'][$key] = $value;
						
					// ...or is it a normal DAO field?
					} else {
						if(is_null($cf_id)) {
							$fields[$key] = $value;
						} else {
							$custom_fields[$cf_id] = $value;
						}
					}
				}
				
				if(isset($keys[$key]['force_match']) || in_array($key, $sync_dupes)) {
					$sync_fields[] = new DevblocksSearchCriteria($keys[$key]['param'], '=', $val);
				}
			}

			if($is_preview) {
				echo "<hr>";
			}
			
			// Check for dupes
			$meta['object_id'] = null;
			
			if(!empty($sync_fields)) {
				$view->addParams($sync_fields, true);
				$view->renderLimit = 1;
				$view->renderPage = 0;
				$view->renderTotal = false;
				list($results) = $view->getData();
			
				if(!empty($results)) {
					$meta['object_id'] = key($results);
				}
			}
			
			if(!$is_preview)
				$context_ext->importSaveObject($fields, $custom_fields, $meta);
		}
		
		if(!$is_preview) {
			@unlink($csv_file); // nuke the imported file}
			$visit->set('import.last.csv',null);
		}
		
		if(!empty($view_id) && !empty($context)) {
			C4_AbstractView::setMarqueeContextImported($view_id, $context, $line_number);
		}
	}
	
	/*
	 * Choosers
	 */
	
	function chooserOpenAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$single = DevblocksPlatform::importGPC($_REQUEST['single'],'integer',0);

		if(null != ($context_extension = DevblocksPlatform::getExtension($context, true))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('context', $context_extension);
			$tpl->assign('layer', $layer);
			$tpl->assign('view', $context_extension->getChooserView());
			$tpl->assign('single', $single);
			$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__generic.tpl');
		}
	}
	
	function chooserOpenSnippetAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');

		if(null != ($context_extension = DevblocksPlatform::getExtension($context, true))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('context', $context_extension);
			$tpl->assign('layer', $layer);
			
			$view = $context_extension->getChooserView();
			
			// If we're being given contexts to filter down to
			if(!empty($contexts)) {
				$target_contexts = DevblocksPlatform::parseCsvString($contexts);
				$contexts = array('');
				$dicts = array();
				
				if(is_array($target_contexts))
				foreach($target_contexts as $target_context_pair) {
					@list($target_context, $target_context_id) = explode(':', $target_context_pair);

					// Load the context dictionary for scope
					$labels = array();
					$values = array();
					CerberusContexts::getContext($target_context, $target_context_id, $labels, $values);

					$dicts[$target_context] = $values;
					
					// Stack filters for the view
					if(!empty($target_context))
						$contexts[] = $target_context;
				}
				
				// Filter the snippet worklist by target contexts
				if(!empty($contexts)) {
					$view->addParamsRequired(array(
						SearchFields_Snippet::CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT, DevblocksSearchCriteria::OPER_IN, $contexts)
					));
				}
				
				if(!empty($dicts)) {
					$placeholder_values = $view->getPlaceholderValues();
					$placeholder_values['dicts'] = $dicts;
					$view->setPlaceholderValues($placeholder_values);
				}
			}
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
			
			$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__snippet.tpl');
		}
	}

	function chooserOpenParamsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer',0);
		
		// [TODO] This should be able to take a simplified JSON view model
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) { /* @var $context_ext Extension_DevblocksContext */
			return;
		}

		if(!isset($context_ext->manifest->params['view_class']))
			return;
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(!($view instanceof $context_ext->manifest->params['view_class'])) {
			C4_AbstractViewLoader::deleteView($view_id);
			$view = null;
		}
		
		if(empty($view)) {
			if(null == ($view = $context_ext->getChooserView($view_id)))
				return;
		}
		
		// Add placeholders
		
		if(!empty($trigger_id) && null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			$event = $trigger->getEvent();
			
			if(method_exists($event,'generateSampleEventModel')) {
				$event_model = $event->generateSampleEventModel($trigger);
				$event->setEvent($event_model);
				$values = $event->getValues();
				$view->setPlaceholderValues($values);
			}
			
			$conditions = $event->getConditions($trigger);
			$valctx = $event->getValuesContexts($trigger);
			foreach($valctx as $token => $vtx) {
				$conditions[$token] = $vtx;
			}

			foreach($conditions as $cond_id => $cond) {
				if(substr($cond_id,0,1) == '_')
					unset($conditions[$cond_id]);
			}
			
			$view->setPlaceholderLabels($conditions);
			
		} elseif(null != $active_worker = CerberusApplication::getActiveWorker()) {
			$labels = array();
			$values = array();
			
			$labels['current_worker_id'] = array(
				'label' => 'Current Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			);
			
			$values['current_worker_id'] = $active_worker->id;
			
			$view->setPlaceholderLabels($labels);
			$view->setPlaceholderValues($values);
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context', $context);
		$tpl->assign('layer', $layer);
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/choosers/__worklist.tpl');
	}
	
	function serializeViewAction() {
		header("Content-type: application/json");
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			echo json_encode(array(
				'view_name' => $view->name,
				'worklist_model' => C4_AbstractViewLoader::serializeViewToAbstractJson($view, $context),
			));
		}
		
		exit;
	}
	
	function chooserOpenFileAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string');
		@$single = DevblocksPlatform::importGPC($_REQUEST['single'], 'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('layer', $layer);
		
		// Single chooser mode?
		$tpl->assign('single', $single);
		
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__file.tpl');
	}

	function chooserOpenFileUploadAction() {
		@$files = $_FILES['file_data'];
		$results = array();

		if(is_array($files) && isset($files['tmp_name']))
		foreach(array_keys($files['tmp_name']) as $file_idx) {
			$file_name = $files['name'][$file_idx];
			$file_type = $files['type'][$file_idx];
			$file_size = $files['size'][$file_idx];
			$file_tmp_name = $files['tmp_name'][$file_idx];
		
			if(empty($file_tmp_name) || empty($file_name))
				continue;
			
			// Create a record w/ timestamp + ID
			$fields = array(
				DAO_Attachment::DISPLAY_NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $file_type,
			);
			$file_id = DAO_Attachment::create($fields);
	
			// Save the file
			if(null !== ($fp = fopen($file_tmp_name, 'rb'))) {
				Storage_Attachments::put($file_id, $fp);
				fclose($fp);
				unlink($file_tmp_name);
				
				$results[] = array(
					'id' => $file_id,
					'name' => $file_name,
					'type' => $file_type,
					'size' => $file_size,
				);
			}
		}

		// [TODO] Unlinked records should expire

		echo json_encode($results);
	}

	function contextAddLinksJsonAction() {
		header('Content-type: application/json');
		
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::setLink($context, $context_id, $from_context, $from_context_id);

		echo json_encode(array(
			'links_count' => DAO_ContextLink::count($from_context, $from_context_id),
		));
	}

	function contextDeleteLinksJsonAction() {
		header('Content-type: application/json');
		
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::deleteLink($context, $context_id, $from_context, $from_context_id);
		
		echo json_encode(array(
			'links_count' => DAO_ContextLink::count($from_context, $from_context_id),
		));
	}

	// Context Activity Log
	
	function showTabActivityLogAction() {
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','target');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string');
		
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($context) || empty($context_id))
			return;
		
		// Remember tab
		if(!empty($point))
			$visit->set($point, 'activity');

		switch($scope) {
			case 'target':
				$params = array(
					SearchFields_ContextActivityLog::TARGET_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT,'=',$context),
					SearchFields_ContextActivityLog::TARGET_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,'=',$context_id),
				);
				break;
				
			case 'both':
				$params = array(
					array(
						DevblocksSearchCriteria::GROUP_OR,
						
						array(
							DevblocksSearchCriteria::GROUP_AND,
							SearchFields_ContextActivityLog::TARGET_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT,'=',$context),
							SearchFields_ContextActivityLog::TARGET_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,'=',$context_id),
						),
						array(
							DevblocksSearchCriteria::GROUP_AND,
							SearchFields_ContextActivityLog::ACTOR_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT,'=',$context),
							SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,'=',$context_id),
						),
					),
				);
				break;
				
			default:
			case 'actor':
				$params = array(
					SearchFields_ContextActivityLog::ACTOR_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT,'=',$context),
					SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,'=',$context_id),
				);
				break;
		}
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'context_activity_log_'.str_replace('.','_',$context.'_'.$context_id);
		$defaults->is_ephemeral = true;
		$defaults->class_name = 'View_ContextActivityLog';
		$defaults->view_columns = array(
			SearchFields_ContextActivityLog::CREATED
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_ContextActivityLog::CREATED;
		$defaults->renderSortAsc = false;
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->addColumnsHidden(array(
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::ID,
			), true);
			
			$view->addParamsHidden(array(
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::ID,
			), true);
			
			$view->addParamsRequired($params, true);
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/activity_log/tab.tpl');
	}
	
	// Autocomplete

	function autocompleteAction() {
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();
		
		$list = array();

		// [TODO] This should be handled by the context extension
		switch($context) {
			case CerberusContexts::CONTEXT_ADDRESS:
				list($results, $null) = DAO_Address::search(
					array(),
					array(
						array(
							DevblocksSearchCriteria::GROUP_OR,
							new DevblocksSearchCriteria(SearchFields_Address::LAST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
							new DevblocksSearchCriteria(SearchFields_Address::FIRST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
							new DevblocksSearchCriteria(SearchFields_Address::EMAIL,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
						),
					),
					25,
					0,
					SearchFields_Address::NUM_NONSPAM,
					false,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = trim($row[SearchFields_Address::FIRST_NAME] . ' ' . $row[SearchFields_Address::LAST_NAME] . ' <' .$row[SearchFields_Address::EMAIL] . '>');
					$entry->value = $row[SearchFields_Address::ID];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_GROUP:
				list($results, $null) = DAO_Group::search(
					array(),
					array(
						new DevblocksSearchCriteria(SearchFields_Group::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
					),
					25,
					0,
					DAO_Group::NAME,
					true,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = $row[SearchFields_Group::NAME];
					$entry->value = $row[SearchFields_Group::ID];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_ORG:
				list($results, $null) = DAO_ContactOrg::search(
					array(),
					array(
						new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
					),
					25,
					0,
					SearchFields_ContactOrg::NAME,
					true,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = $row[SearchFields_ContactOrg::NAME];
					$entry->value = $row[SearchFields_ContactOrg::ID];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_SNIPPET:
				$contexts = DevblocksPlatform::getExtensions('devblocks.context', false);
				
				$worker_groups = $active_worker->getMemberships();
				$worker_roles = $active_worker->getRoles();
				
				// Restrict owners
				$param_ownership = array(
					DevblocksSearchCriteria::GROUP_OR,
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_WORKER),
						SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_EQ,$active_worker->id),
					),
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_GROUP),
						SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,array_keys($worker_groups)),
					),
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_ROLE),
						SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,array_keys($worker_roles)),
					),
				);
				
				$params = array(
					new DevblocksSearchCriteria(SearchFields_Snippet::TITLE,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%'),
					$param_ownership,
				);
				
				@$context_list = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				if(is_array($context_list))
				foreach($context_list as $k => $v) {
					if(!isset($contexts[$v]))
						unset($context_list[$k]);
				}

				$context_list[] = ''; // plaintext
				
				// Filter contexts
				$params[SearchFields_Snippet::CONTEXT] =
					new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT,DevblocksSearchCriteria::OPER_IN,$context_list)
					;
				
				list($results, $null) = DAO_Snippet::search(
					array(
						SearchFields_Snippet::TITLE,
						SearchFields_Snippet::USE_HISTORY_MINE,
					),
					$params,
					25,
					0,
					SearchFields_Snippet::USE_HISTORY_MINE,
					false,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = sprintf("%s -- used %s",
						$row[SearchFields_Snippet::TITLE],
						((1 != $row[SearchFields_Snippet::USE_HISTORY_MINE]) ? (intval($row[SearchFields_Snippet::USE_HISTORY_MINE]) . ' times') : 'once')
					);
					$entry->value = $row[SearchFields_Snippet::ID];
					$entry->context = $row[SearchFields_Snippet::CONTEXT];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_WORKER:
				$results = DAO_Worker::autocomplete($term);

				if(is_array($results))
				foreach($results as $worker_id => $worker){
					$entry = new stdClass();
					$entry->label = $worker->getName();
					$entry->value = sprintf("%d", $worker_id);
					$list[] = $entry;
				}
				break;
		}

		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}

	function toggleContextWatcherAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$follow = DevblocksPlatform::importGPC($_REQUEST['follow'],'integer',0);
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'],'integer',0);
		
		// [TODO] Verify context + context_id
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('full', $full);
		
		// Add or remove watcher as current worker
		if($follow) {
			CerberusContexts::addWatchers($context, $context_id, $active_worker->id);
		} else {
			CerberusContexts::removeWatchers($context, $context_id, $active_worker->id);
		}
		
		// Watchers
		$object_watchers = DAO_ContextLink::getContextLinks($context, array($context_id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl');
		
		$tpl->clearAssign('context');
		$tpl->clearAssign('context_id');
		$tpl->clearAssign('object_watchers');
	}
	
	function showContextWatchersAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null != ($extension = DevblocksPlatform::getExtension($context, true))) { /* @var $extension Extension_DevblocksContext */
			$tpl->assign('extension', $extension);
			$meta = $extension->getMeta($context_id);
			$tpl->assign('meta', $meta);
		}
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		// Watchers
		$object_watchers = DAO_ContextLink::getContextLinks($context, array($context_id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_popup.tpl');
	}
	
	function addContextWatchersAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$add_worker_ids_str = DevblocksPlatform::importGPC($_REQUEST['add_worker_ids'],'string','');
		@$delete_worker_ids_str = DevblocksPlatform::importGPC($_REQUEST['delete_worker_ids'],'string','');
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'],'integer',0);
		
		$add_worker_ids = DevblocksPlatform::parseCsvString($add_worker_ids_str, false, 'integer');
		$delete_worker_ids = DevblocksPlatform::parseCsvString($delete_worker_ids_str, false, 'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('full', $full);
		
		// Add
		if(!empty($add_worker_ids) && $active_worker->hasPriv('core.watchers.assign'))
			CerberusContexts::addWatchers($context, $context_id, $add_worker_ids);
			
		// Remove
		if(!empty($delete_worker_ids) && $active_worker->hasPriv('core.watchers.unassign'))
			CerberusContexts::removeWatchers($context, $context_id, $delete_worker_ids);
		
		// Watchers
		$object_watchers = DAO_ContextLink::getContextLinks($context, array($context_id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl');
		
		$tpl->clearAssign('context');
		$tpl->clearAssign('context_id');
		$tpl->clearAssign('object_watchers');
	}
	
	// Snippets

	function showTabSnippetsAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('owner_context', $context);
		$tpl->assign('owner_context_id', $context_id);
		
		// Remember the tab
		$visit->set($point, 'snippets');

		$view_id = str_replace('.','_',$point) . '_snippets';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$view = new View_Snippet();
			$view->id = $view_id;
			$view->name = 'Snippets';
		}
		
		if($active_worker->is_superuser && 0 == strcasecmp($context, 'all')) {
			$view->addParamsRequired(array(), true);
		} else {
			$view->addParamsRequired(array(
				SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
				SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
			), true);
		}
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showSnippetsPeekAction() {
		@$snippet_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer']);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('layer', $layer);
		
		if(empty($snippet_id) || null == ($snippet = DAO_Snippet::get($snippet_id))) {
			@$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'string','');
			@$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'],'integer',0);
		
			$snippet = new Model_Snippet();
			$snippet->id = 0;
			$snippet->owner_context = !empty($owner_context) ? $owner_context : '';
			$snippet->owner_context_id = $owner_context_id;
		}
		
		$tpl->assign('snippet', $snippet);
		
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET, false);
		$tpl->assign('custom_fields', $custom_fields);

		if(!empty($custom_fields)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_SNIPPET, $snippet_id);
			if(isset($custom_field_values[$snippet_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$snippet_id]);
		}
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Owners
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$owner_groups = array();
		foreach($groups as $k => $v) {
			if($active_worker->is_superuser || $active_worker->isGroupManager($k))
				$owner_groups[$k] = $v;
		}
		$tpl->assign('owner_groups', $owner_groups);
		
		$owner_roles = array();
		foreach($roles as $k => $v) { /* @var $v Model_WorkerRole */
			if($active_worker->is_superuser)
				$owner_roles[$k] = $v;
		}
		$tpl->assign('owner_roles', $owner_roles);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek.tpl');
	}
	
	function showSnippetsPeekToolbarAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context', $context);
		
		if(!empty($context)) {
			$token_labels = array();
			$token_values = array();
			
			CerberusContexts::getContext($context, null, $token_labels, $token_values);
			
			$tpl->assign('token_labels', $token_labels);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek_toolbar.tpl');
	}
	
	function saveSnippetsPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$fields = array(
			DAO_Snippet::TITLE => $title,
			DAO_Snippet::CONTEXT => $context,
			DAO_Snippet::CONTENT => $content,
		);

		if($do_delete) {
			if(null != ($snippet = DAO_Snippet::get($id))) { /* @var $snippet Model_Snippet */
				if($snippet->isWriteableByWorker($active_worker)) {
					DAO_Snippet::delete($id);
				}
			}
			
		} else { // Create || Update
			@list($owner_type, $owner_id) = explode('_', DevblocksPlatform::importGPC($_REQUEST['owner'],'string',''));
		
			switch($owner_type) {
				// Role
				case 'r':
					$owner_context = CerberusContexts::CONTEXT_ROLE;
					$owner_context_id = $owner_id;
					break;
				// Group
				case 'g':
					$owner_context = CerberusContexts::CONTEXT_GROUP;
					$owner_context_id = $owner_id;
					break;
				// Worker
				case 'w':
					$owner_context = CerberusContexts::CONTEXT_WORKER;
					$owner_context_id = $owner_id;
					break;
				// Default
				default:
					$owner_context = null;
					$owner_context_id = null;
					break;
			}
			
			if(empty($owner_context) || empty($owner_context_id)) {
				$owner_context = CerberusContexts::CONTEXT_WORKER;
				$owner_context_id = $active_worker->id;
			}
			
			$fields[DAO_Snippet::OWNER_CONTEXT] = $owner_context;
			$fields[DAO_Snippet::OWNER_CONTEXT_ID] = $owner_context_id;
			
			if(empty($id)) {
				if($active_worker->hasPriv('core.snippets.actions.create')) {
					$id = DAO_Snippet::create($fields);
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids);
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_SNIPPET, $id);
					}
				}
				
			} else {
				if(null != ($snippet = DAO_Snippet::get($id))) { /* @var $snippet Model_Snippet */
					if($snippet->isWriteableByWorker($active_worker)) {
						DAO_Snippet::update($id, $fields);
						
						// Custom field saves
						@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
						DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids);
					}
				}
			}
		}
		
		
		if(null !== ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
	}
	
	function snippetPasteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null != ($snippet = DAO_Snippet::get($id))) {
			// Make sure the worker is allowed to view this context+ID
			if(!empty($snippet->context)) {
				if(null == ($context = Extension_DevblocksContext::get($snippet->context))) /* @var $context Extension_DevblocksContext */
					exit;
				if(!$context->authorize($context_id, $active_worker))
					exit;
			}
			
			CerberusContexts::getContext($snippet->context, $context_id, $token_labels, $token_values);

			$snippet->incrementUse($active_worker->id);
		}

		// Build template
		if(!empty($context_id)) {
			$output = $tpl_builder->build($snippet->content, $token_values);
			
		} else {
			$output = $snippet->content;
		}

		if(!empty($output))
			echo rtrim($output,"\r\n"),"\n";
	}
	
	function snippetPlaceholdersAction() {
		@$text = DevblocksPlatform::importGPC($_REQUEST['text'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('text', $text);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/paste_placeholders.tpl');
	}

	function snippetTestAction() {
		@$snippet_context = DevblocksPlatform::importGPC($_REQUEST['snippet_context'],'string','');
		@$snippet_context_id = DevblocksPlatform::importGPC($_REQUEST['snippet_context_id'],'integer',0);
		@$snippet_field = DevblocksPlatform::importGPC($_REQUEST['snippet_field'],'string','');

		$content = '';
		if(isset($_REQUEST[$snippet_field]))
			$content = DevblocksPlatform::importGPC($_REQUEST[$snippet_field]);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$tpl = DevblocksPlatform::getTemplateService();

		$token_labels = array();
		$token_value = array();

		$ctx = Extension_DevblocksContext::get($snippet_context);

		// If no ID is given, randomize one
		if(empty($snippet_context_id) && method_exists($ctx, 'getRandom'))
			$snippet_context_id = $ctx->getRandom();
		
		CerberusContexts::getContext($snippet_context, $snippet_context_id, $token_labels, $token_values);
		
		$success = false;
		$output = '';

		if(!empty($token_values)) {
			// Tokenize
			$tokens = $tpl_builder->tokenize($content);
			$valid_tokens = $tpl_builder->stripModifiers(array_keys($token_labels));
			
			// Test legal values
			$unknown_tokens = array_diff($tokens, $valid_tokens);
			$matching_tokens = array_intersect($tokens, $valid_tokens);
			
			if(!empty($unknown_tokens)) {
				$success = false;
				$output = "The following placeholders are unknown: ".
					implode(', ', $unknown_tokens);
				
			} else {
				// Try to build the template
				if(false === ($out = $tpl_builder->build($content, $token_values))) {
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

	function showSnippetBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/bulk.tpl');
	}
	
	function doSnippetBulkUpdateAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Snippet fields
		@$owner = trim(DevblocksPlatform::importGPC($_POST['owner'],'string',''));

		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Do: Due
		if(0 != strlen($owner))
			$do['owner'] = $owner;
			
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
	
	// Views

	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->render();
		}
	}

	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doSortBy($sortBy);
			C4_AbstractViewLoader::setView($id, $view);
			$view->render();
		}
	}

	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doPage($page);
			C4_AbstractViewLoader::setView($id, $view);
			$view->render();
		}
	}

	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$tpl->assign('view', $view);

			// [TODO] Detect if we're customizing (swap Editable for Required)
			
			// Do we already have this filter to re-edit?
			$params = $view->getEditableParams();
			
			if(isset($params[$field])) {
				$tpl->assign('param', $params[$field]);
			}
			
			// Render from the View_* implementation.
			$view->renderCriteria($field);
		}
	}

	private function _viewRenderInlineFilters($view, $is_custom=false) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view', $view);
		
		if($is_custom)
			$tpl->assign('is_custom', true);
			
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
	}

	// Ajax
	
	function viewToggleFiltersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$show = DevblocksPlatform::importGPC($_REQUEST['show'],'integer',0);
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->renderFilters = !empty($show) ? 1 : 0;
			C4_AbstractViewLoader::setView($view->id, $view);
		}
	}
	
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$is_custom = DevblocksPlatform::importGPC($_REQUEST['is_custom'],'integer',0);

		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;

		if($is_custom && 0 != strcasecmp('cust_',substr($id,0,5)))
			$is_custom = 0;
		
		// If this is a custom worklist we want to swap the req+editable params
		if($is_custom) {
			$original_params = $view->getEditableParams();
			$view->addParams($view->getParamsRequired(), true);
		}
			
		// Nuke criteria
		if(is_array($field_deletes) && !empty($field_deletes)) {
			foreach($field_deletes as $field_delete) {
				$view->doRemoveCriteria($field_delete);
			}
		}
		
		// Add
		if(!empty($field)) {
			$view->doSetCriteria($field, $oper, $value);
		}

		// If this is a custom worklist we want to swap the req+editable params back
		if($is_custom) {
			$view->addParamsRequired($view->getEditableParams(), true);
			$view->addParams($original_params, true);
		}
		
		// Reset the paging when adding a filter
		$view->renderPage = 0;
		
		C4_AbstractViewLoader::setView($view->id, $view);

		$this->_viewRenderInlineFilters($view, $is_custom);
	}

	function viewResetFiltersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;

		$view->doResetCriteria();

		C4_AbstractViewLoader::setView($view->id, $view);

		$this->_viewRenderInlineFilters($view);
	}

	function viewLoadPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_id = DevblocksPlatform::importGPC($_REQUEST['_preset'],'integer',0);

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;

		$view->removeAllParams();

		if(null != ($preset = DAO_ViewFiltersPreset::get($preset_id))) {
			$view->addParams($preset->params);
			if(!is_null($preset->sort_by)) {
				$view->renderSortBy = $preset->sort_by;
				$view->renderSortAsc = !empty($preset->sort_asc);
			}
		}

		C4_AbstractViewLoader::setView($view->id, $view);

		$this->_viewRenderInlineFilters($view);
	}

	function viewAddPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_name = DevblocksPlatform::importGPC($_REQUEST['_preset_name'],'string','');
		@$preset_replace_id = DevblocksPlatform::importGPC($_REQUEST['_preset_replace'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		$params_json = json_encode($view->getEditableParams());

		if(!empty($preset_replace_id)) {
			$fields = array(
				DAO_ViewFiltersPreset::NAME => !empty($preset_name) ? $preset_name : 'New Preset',
				DAO_ViewFiltersPreset::PARAMS_JSON => $params_json,
				DAO_ViewFiltersPreset::SORT_JSON => json_encode(array(
					'by' => $view->renderSortBy,
					'asc' => !empty($view->renderSortAsc),
				)),
			);

			DAO_ViewFiltersPreset::update($preset_replace_id, $fields);

		} else { // new
			$fields = array(
				DAO_ViewFiltersPreset::NAME => !empty($preset_name) ? $preset_name : 'New Preset',
				DAO_ViewFiltersPreset::VIEW_CLASS => get_class($view),
				DAO_ViewFiltersPreset::WORKER_ID => $active_worker->id,
				DAO_ViewFiltersPreset::PARAMS_JSON => $params_json,
				DAO_ViewFiltersPreset::SORT_JSON => json_encode(array(
					'by' => $view->renderSortBy,
					'asc' => !empty($view->renderSortAsc),
				)),
			);

			DAO_ViewFiltersPreset::create($fields);
		}

		$this->_viewRenderInlineFilters($view);
	}

	function viewEditPresetsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_dels = DevblocksPlatform::importGPC($_REQUEST['_preset_del'],'array',array());

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			DAO_ViewFiltersPreset::delete($preset_dels);
			$this->_viewRenderInlineFilters($view);
		}
	}

	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $id);

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		// Custom worklists
		if('cust_' == substr($view->id,0,5)) {
			try {
				$worklist_id = substr($view->id,5);
				
				if(!is_numeric($worklist_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($worklist = DAO_WorkspaceList::get($worklist_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace_tab = DAO_WorkspaceTab::get($worklist->workspace_tab_id)))
					throw new Exception("Can't load workspace tab.");
				
				if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_tab->workspace_page_id)))
					throw new Exception("Can't load workspace page.");
				
				if(!$workspace_page->isWriteableByWorker($active_worker)) {
					$tpl->display('devblocks:cerberusweb.core::internal/workspaces/customize_no_acl.tpl');
					return;
				}
				
			} catch(Exception $e) {
				// [TODO] Logger
				return;
			}
		}

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view.tpl');
	}

	function viewShowCopyAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;

		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

		$tpl->display('devblocks:cerberusweb.core::internal/views/copy.tpl');
	}

	function viewDoCopyAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;

		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace_page_id = DevblocksPlatform::importGPC($_POST['workspace_page_id'],'integer', 0);
		@$workspace_tab_id = DevblocksPlatform::importGPC($_POST['workspace_tab_id'],'integer', 0);

		if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_page_id)))
			return;
		
		if(!$workspace_page->isWriteableByWorker($active_worker))
			return;
		
		if(null == ($workspace_tab = DAO_WorkspaceTab::get($workspace_tab_id)))
			return;

		if($workspace_tab->workspace_page_id != $workspace_page->id)
			return;
		
		if(empty($list_title))
			$list_title = $translate->_('mail.workspaces.new_list');

		// Find the context
		$contexts = Extension_DevblocksContext::getAll();
		$workspace_context = '';
		$view_class = get_class($view);
		foreach($contexts as $context_id => $context) {
			if(0 == strcasecmp($context->params['view_class'], $view_class))
				$workspace_context = $context_id;
		}

		if(empty($workspace_context))
			return;

		// View params inside the list for quick render overload
		$list_view = new Model_WorkspaceListView();
		$list_view->title = $list_title;
		$list_view->num_rows = $view->renderLimit;
		$list_view->columns = $view->view_columns;
		$list_view->params = $view->getEditableParams();
		$list_view->params_required = $view->getParamsRequired();
		$list_view->sort_by = $view->renderSortBy;
		$list_view->sort_asc = $view->renderSortAsc;
		$list_view->subtotals = $view->renderSubtotals;

		// Save the new worklist
		$fields = array(
			DAO_WorkspaceList::WORKSPACE_TAB_ID => $workspace_tab_id,
			DAO_WorkspaceList::CONTEXT => $workspace_context,
			DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
			DAO_WorkspaceList::LIST_POS => 99,
		);
		$list_id = DAO_WorkspaceList::create($fields);

		$view->render();
	}

	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		$tpl->assign('view', $view);

		$model_columns = $view->getColumnsAvailable();
		$tpl->assign('model_columns', $model_columns);

		$view_columns = $view->view_columns;
		$tpl->assign('view_columns', $view_columns);

		$tpl->display('devblocks:cerberusweb.core::internal/views/view_export.tpl');
	}

	function viewDoExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array',array());
		@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'],'string','csv');

		// Scan through the columns and remove any blanks
		if(is_array($columns))
		foreach($columns as $idx => $col) {
			if(empty($col))
				unset($columns[$idx]);
		}

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$column_manifests = $view->getColumnsAvailable();

		// Override display
		$view->view_columns = $columns;
		$view->renderPage = 0;
		$view->renderLimit = -1;

		if('csv' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);

			// Column headers
			if(is_array($columns)) {
				$cols = array();
				foreach($columns as $col) {
					$cols[] = sprintf("\"%s\"",
						str_replace('"','\"',mb_convert_case($column_manifests[$col]->db_label,MB_CASE_TITLE))
					);
				}
				echo implode(',', $cols) . "\r\n";
			}

			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				if(is_array($row)) {
					$cols = array();
					if(is_array($columns))
					foreach($columns as $col) {
						$cols[] = sprintf("\"%s\"",
							str_replace('"','\"',$row[$col])
						);
					}
					echo implode(',', $cols) . "\r\n";
				}
			}

		} elseif('json' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);

			$objects = array();
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				$object = array();
				if(is_array($columns))
				foreach($columns as $col) {
					$object[$col] = $row[$col];
				}
				
				$objects[] = $object;
			}
			
			echo json_encode($objects);
			
		} elseif('xml' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);

			$xml = simplexml_load_string("<results/>"); /* @var $xml SimpleXMLElement */

			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				$result =& $xml->addChild("result");
				if(is_array($columns))
				foreach($columns as $col) {
					$field =& $result->addChild("field",htmlspecialchars($row[$col],null,LANG_CHARSET_CODE));
					$field->addAttribute("id", $col);
				}
			}

			// Pretty format and output
			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($xml->asXML());
			$doc->formatOutput = true;
			echo $doc->saveXML();
		}

		exit;
	}

	function viewSaveCustomizeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);

		$num_rows = max($num_rows, 1); // make 1 the minimum

		// [Security] Filter custom fields
		$custom_fields = DAO_CustomField::getAll();
		foreach($columns as $idx => $column) {
			if(substr($column, 0, 3)=="cf_") {
				$field_id = intval(substr($column, 3));
				@$field = $custom_fields[$field_id]; /* @var $field Model_CustomField */

				// Is this a valid custom field?
				if(empty($field)) {
					unset($columns[$idx]);
					continue;
				}

				// Do we have permission to see it?
				if(!empty($field->group_id)
					&& !$active_worker->isGroupMember($field->group_id)) {
						unset($columns[$idx]);
						continue;
				}
			}
		}

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		$view->doCustomize($columns, $num_rows);

		$is_custom = substr($id,0,5)=='cust_';
		$is_trigger = substr($id,0,9)=='_trigger_';
		
		if($is_custom || $is_trigger) {
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
			$view->name = $title;
		}
		
		// Reset the paging
		$view->renderPage = 0;
		
		// Handle worklists specially
		if($is_custom) { // custom workspace
			// Check the custom workspace

			try {
				$list_view_id = intval(substr($id,5));
				
				if(empty($list_view_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($list_model = DAO_WorkspaceList::get($list_view_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace_tab = DAO_WorkspaceTab::get($list_model->workspace_tab_id)))
					throw new Exception("Can't load workspace tab.");
				
				if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_tab->workspace_page_id)))
					throw new Exception("Can't load workspace page.");
				
				if(!$workspace_page->isWriteableByWorker($active_worker)) {
					throw new Exception("Permission denied to edit workspace.");
				}
				
			} catch(Exception $e) {
				// [TODO] Logger
				$view->render();
				return;
			}
			
			// Persist Object
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $title;
			$list_view->columns = $view->view_columns;
			$list_view->num_rows = $view->renderLimit;
			$list_view->params = array();
			$list_view->params_required = $view->getParamsRequired();
			$list_view->sort_by = $view->renderSortBy;
			$list_view->sort_asc = $view->renderSortAsc;
			$list_view->subtotals = $view->renderSubtotals;

			DAO_WorkspaceList::update($list_view_id, array(
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view)
			));

			// Syndicate
			$worker_views = DAO_WorkerViewModel::getWhere(sprintf("view_id = %s", Cerb_ORMHelper::qstr($id)));

			// Update any instances of this view with the new required columns + params
			foreach($worker_views as $worker_view) { /* @var $worker_view C4_AbstractViewModel */
				$worker_view->name = $view->name;
				$worker_view->view_columns = $view->view_columns;
				$worker_view->paramsRequired = $view->getParamsRequired();
				$worker_view->renderLimit = $view->renderLimit;
				DAO_WorkerViewModel::setView($worker_view->worker_id, $worker_view->id, $worker_view);
			}
		}

		C4_AbstractViewLoader::setView($id, $view);

		$view->render();
	}

	function viewShowQuickSearchPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/quick_search_popup.tpl');
	}
	
	function viewSubtotalAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$toggle = DevblocksPlatform::importGPC($_REQUEST['toggle'],'integer',0);
		@$category = DevblocksPlatform::importGPC($_REQUEST['category'],'string','');

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
			
		// Check the interface
		if(!$view instanceof IAbstractView_Subtotals)
			return;
			
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

		$fields = $view->getSubtotalFields();
		$tpl->assign('subtotal_fields', $fields);

		// If we're toggling on/off, persist our preference
		if($toggle) {
			// hidden->shown
			if(empty($view->renderSubtotals)) {
				$view->renderSubtotals = key($fields);
				
			// hidden->shown ('__' prefix means hidden w/ pref)
			} elseif('__'==substr($view->renderSubtotals,0,2)) {
				$key = ltrim($view->renderSubtotals,'_');
				// Make sure the desired key still exists
				$view->renderSubtotals = isset($fields[$key]) ? $key : key($fields);
				
			} else { // shown->hidden
				$view->renderSubtotals = '__' . $view->renderSubtotals;
				
			}
			
		} else {
			$view->renderSubtotals = $category;
			
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);

		// If hidden, no need to draw template
		if(empty($view->renderSubtotals) || '__'==substr($view->renderSubtotals,0,2))
			return;

		$view->renderSubtotals();
	}

	/**
	 * Triggers
	 */

	function applyMacroAction() {
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$run_relative = DevblocksPlatform::importGPC($_REQUEST['run_relative'],'string','');
		@$run_date = DevblocksPlatform::importGPC($_REQUEST['run_date'],'string','');
		@$return_url = DevblocksPlatform::importGPC($_REQUEST['return_url'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($return_url) && isset($_SERVER['http_referer']))
			$return_url = $_SERVER['http_referer'];
		
		try {
			if(empty($context) || empty($context_id) || empty($macro_id))
				return;
	
			// Load context
			if(null == ($context_ext = DevblocksPlatform::getExtension($context, true)))
				throw new Exception("Invalid context.");
	
			// ACL: Ensure access to the context object
			if(!$context_ext->authorize($context_id, $active_worker))
				throw new Exception("Access denied to context.");
			
			// Load macro
			if(null == ($macro = DAO_TriggerEvent::get($macro_id))) /* @var $macro Model_TriggerEvent */
				throw new Exception("Invalid macro.");
			
			if(null == ($va = $macro->getVirtualAttendant()))
				throw new Exception("Invalid VA.");
			
			// ACL: Ensure the worker has access to the macro
			if(!$va->isReadableByActor($active_worker))
				throw new Exception("Access denied to macro.");

			// Relative scheduling
			// [TODO] This is almost wholly redundant with saveMacroSchedulerPopup()
			
			$event = $macro->getEvent();
			$event_model = $event->generateSampleEventModel($macro, $context_id);
			$event->setEvent($event_model);
			$values = $event->getValues();
			
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			@$run_relative_timestamp = strtotime($tpl_builder->build(sprintf("{{%s|date}}",$run_relative), $values));
			
			if(empty($run_relative_timestamp))
				$run_relative_timestamp = time();
			
			// Recurring events
			// [TODO] This is almost wholly redundant with saveMacroSchedulerPopup()
			@$repeat_freq = DevblocksPlatform::importGPC($_REQUEST['repeat_freq'],'string', '');
			@$repeat_end = DevblocksPlatform::importGPC($_REQUEST['repeat_end'],'string', '');
			
			$repeat_params = array();
			
			if(!empty($repeat_freq)) {
				@$repeat_options = DevblocksPlatform::importGPC($_REQUEST['repeat_options'][$repeat_freq], 'array', array());
				@$repeat_ends = DevblocksPlatform::importGPC($_REQUEST['repeat_ends'][$repeat_end], 'array', array());
	
				switch($repeat_end) {
					case 'date':
						if(isset($repeat_ends['on'])) {
							$repeat_ends['on'] = @strtotime($repeat_ends['on']);
						}
						break;
				}
				
				$repeat_params = array(
					'freq' => $repeat_freq,
					'options' => $repeat_options,
					'end' => array(
						'term' => $repeat_end,
						'options' => $repeat_ends,
					),
				);
			}
			
			// Time
			$run_timestamp = @strtotime($run_date, $run_relative_timestamp) or time();

			// Variables
			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_keys'],'array',array());
			@$var_vals = DevblocksPlatform::importGPC($_REQUEST['var_vals'],'array',array());

			$vars = DAO_ContextScheduledBehavior::buildVariables($var_keys, $var_vals, $macro);

			// Create
			$behavior_id = DAO_ContextScheduledBehavior::create(array(
				DAO_ContextScheduledBehavior::BEHAVIOR_ID => $macro->id,
				DAO_ContextScheduledBehavior::CONTEXT => $context,
				DAO_ContextScheduledBehavior::CONTEXT_ID => $context_id,
				DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp,
				DAO_ContextScheduledBehavior::RUN_RELATIVE => $run_relative,
				DAO_ContextScheduledBehavior::RUN_LITERAL => $run_date,
				DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($vars),
				DAO_ContextScheduledBehavior::REPEAT_JSON => json_encode($repeat_params),
			));
			
			// Execute now if the start time is in the past
			if($run_timestamp <= time()) {
				$behavior = DAO_ContextScheduledBehavior::get($behavior_id);
				$behavior->run();
			}
			
		} catch (Exception $e) {
			// System log error?
		}
		
		// Redirect
		DevblocksPlatform::redirectURL($return_url);
		exit;
	}
	
	function renderContextScheduledBehaviorAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('expanded', true);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl');
	}
	
	function showMacroSchedulerPopupAction() {
		@$job_id = DevblocksPlatform::importGPC($_REQUEST['job_id'],'integer',0);
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$return_url = DevblocksPlatform::importGPC($_REQUEST['return_url'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($job_id)) {
			$tpl->assign('return_url', $return_url);
			
			try {
				if(null == ($macro = DAO_TriggerEvent::get($macro_id)))
					throw new Exception("Missing macro.");
				
				$tpl->assign('macro', $macro);
				
			} catch(Exception $e) {
				DevblocksPlatform::redirectURL($return_url);
				exit;
			}
			
		} else { // Update
			$job = DAO_ContextScheduledBehavior::get($job_id);

			if(null == $job)
				return;
			
			$tpl->assign('job', $job);
			
			$context = $job->context;
			$context_id = $job->context_id;
			$macro_id = $job->behavior_id;
			
			$macro = DAO_TriggerEvent::get($macro_id);
			$tpl->assign('macro', $macro);
			
		}
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		if(null == ($ctx = DevblocksPlatform::getExtension($context, true))) /* @var $ctx Extension_DevblocksContext */
			return;

		$tpl->assign('ctx', $ctx);
		
		// Verify permission
		$editable = $ctx->authorize($context_id, $active_worker);
		$tpl->assign('editable', $editable);
		
		if(empty($job) && !$editable)
			return;

		$event = $macro->getEvent();
		$conditions = $event->getConditions($macro);
		$dates = array();
		
		foreach($conditions as $k => $cond) {
			if(isset($cond['type']) && $cond['type'] == Model_CustomField::TYPE_DATE)
				$dates[$k] = $cond;
		}
		
		$tpl->assign('dates', $dates);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/display/scheduler_popup.tpl');
	}
	
	function saveMacroSchedulerPopupAction() {
		@$job_id = DevblocksPlatform::importGPC($_REQUEST['job_id'],'integer',0);
		@$run_relative = DevblocksPlatform::importGPC($_REQUEST['run_relative'],'string','');
		@$run_date = DevblocksPlatform::importGPC($_REQUEST['run_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($job = DAO_ContextScheduledBehavior::get($job_id)))
			return;
		
		if(null == ($trigger = DAO_TriggerEvent::get($job->behavior_id)))
			return;
		
		if(null == ($ctx = DevblocksPlatform::getExtension($job->context, true))) /* @var $ctx Extension_DevblocksContext */
			return;
		
		// Verify permission
		if(!$ctx->authorize($job->context_id, $active_worker))
			return;

		// Load the event with this context
		if(null == ($event = $trigger->getEvent()))
			return;
		
		$event_model = $event->generateSampleEventModel($trigger, $job->context_id);
		$event->setEvent($event_model);
		$values = $event->getValues();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		@$run_relative_timestamp = strtotime($tpl_builder->build(sprintf("{{%s|date}}", $run_relative), $values));
		
		if(empty($run_relative_timestamp))
			$run_relative_timestamp = time();
		
		if($do_delete) {
			DAO_ContextScheduledBehavior::delete($job->id);
			
		} else {
			@$repeat_freq = DevblocksPlatform::importGPC($_REQUEST['repeat_freq'],'string', '');
			@$repeat_end = DevblocksPlatform::importGPC($_REQUEST['repeat_end'],'string', '');
			
			$repeat_params = array();
			
			if(!empty($repeat_freq)) {
				@$repeat_options = DevblocksPlatform::importGPC($_REQUEST['repeat_options'][$repeat_freq], 'array', array());
				@$repeat_ends = DevblocksPlatform::importGPC($_REQUEST['repeat_ends'][$repeat_end], 'array', array());
	
				switch($repeat_end) {
					case 'date':
						if(isset($repeat_ends['on'])) {
							$repeat_ends['on'] = @strtotime($repeat_ends['on']);
						}
						break;
				}
				
				$repeat_params = array(
					'freq' => $repeat_freq,
					'options' => $repeat_options,
					'end' => array(
						'term' => $repeat_end,
						'options' => $repeat_ends,
					),
				);
			}
			
			$run_timestamp = @strtotime($run_date, $run_relative_timestamp) or time();
			
			// Variables
			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_keys'],'array',array());
			@$var_vals = DevblocksPlatform::importGPC($_REQUEST['var_vals'],'array',array());
			
			$vars = DAO_ContextScheduledBehavior::buildVariables($var_keys, $var_vals, $trigger);
			
			DAO_ContextScheduledBehavior::update($job->id, array(
				DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp,
				DAO_ContextScheduledBehavior::RUN_RELATIVE => $run_relative,
				DAO_ContextScheduledBehavior::RUN_LITERAL => $run_date,
				DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($vars),
				DAO_ContextScheduledBehavior::REPEAT_JSON => json_encode($repeat_params),
			));
		}
		
		exit;
	}
	
	function showAttendantsTabAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(empty($context))
			return;

		// Remember tab
		if(!empty($point))
			$visit->set($point, 'attendants');
		
		$tpl->assign('owner_context', $context);
		$tpl->assign('owner_context_id', $context_id);
		
		$view_id = str_replace('.','_',$point) . '_attendants';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT);
			$view = $ctx->getChooserView($view_id);
		}
		
		if($active_worker->is_superuser && 0 == strcasecmp($context, 'all')) {
			$view->addParamsRequired(array(), true);
			
		} else {
			$view->addParamsRequired(array(
				SearchFields_VirtualAttendant::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_VirtualAttendant::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
				SearchFields_VirtualAttendant::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_VirtualAttendant::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
			), true);
		}
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showAttendantBehaviorsTabAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($id))
			return;
		
		// Remember tab
		if(!empty($point))
			$visit->set($point, 'behaviors');
		
		if(null == ($va = DAO_VirtualAttendant::get($id)))
			return;

		$tpl->assign('va', $va);

		// Security check

		if(!$va->isReadableByActor($active_worker))
			return;
		
		$is_writeable = $va->isWriteableByActor($active_worker) ? true : false;
		$tpl->assign('is_writeable', $is_writeable);
		
		// Events
		
		$events = Extension_DevblocksEvent::getByContext($va->owner_context, false);
		$tpl->assign('events', $events);
		
		$triggers = DAO_TriggerEvent::getByVirtualAttendant($va, null, true, 'pos');
		$tpl->assign('triggers', $triggers);
		
		$triggers_by_event = array();
		
		foreach($triggers as $trigger) { /* @var $trigger Model_TriggerEvent */
			if(!isset($triggers_by_event[$trigger->event_point]))
				$triggers_by_event[$trigger->event_point] = array();
			
			$triggers_by_event[$trigger->event_point][$trigger->id] = $trigger;
		}

		$tpl->assign('triggers_by_event', $triggers_by_event);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/assistant/tab.tpl');
	}

	function reparentNodeAction() {
		@$child_id = DevblocksPlatform::importGPC($_REQUEST['child_id'],'integer', 0);
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
		
		if(null == ($child_node = DAO_DecisionNode::get($child_id)))
			exit;
		
		$nodes = DAO_DecisionNode::getByTriggerParent($child_node->trigger_id, $parent_id);
		
		// Remove current node if exists
		unset($nodes[$child_node->id]);
		
		$pos = 0;
		
		// Insert child at top of parent
		DAO_DecisionNode::update($child_id, array(
			DAO_DecisionNode::PARENT_ID => $parent_id,
			DAO_DecisionNode::POS => $pos++,
		));
		
		// Renumber children
		foreach($nodes as $node_id => $node) {
			DAO_DecisionNode::update($node_id, array(
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::POS => $pos++,
			));
		}
		
		exit;
	}
	
	function reorderTriggersAction() {
		@$trigger_ids = DevblocksPlatform::importGPC($_REQUEST['trigger_id'], 'array', array());
		
		$trigger_ids = DevblocksPlatform::sanitizeArray($trigger_ids, 'integer');

		DAO_TriggerEvent::setTriggersOrder($trigger_ids);
		
		exit;
	}
	
	function showDecisionMovePopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			$tpl->assign('node', $node);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				$tpl->assign('trigger', $trigger);
			}
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_move.tpl');
	}
	
	function showDecisionReorderPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			$trigger_id = $node->trigger_id;
			$tpl->assign('node', $node);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				$tpl->assign('trigger', $trigger);
			}
		}
		
		$children = DAO_DecisionNode::getByTriggerParent($trigger_id, $id);
		$tpl->assign('children', $children);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_reorder.tpl');
	}
	
	function saveDecisionReorderPopupAction() {
		@$child_ids = DevblocksPlatform::importGPC($_REQUEST['child_id'],'array', array());
		
		if(!empty($child_ids))
		foreach($child_ids as $pos => $child_id) {
			DAO_DecisionNode::update($child_id, array(
				DAO_DecisionNode::POS => $pos,
			));
		}
	}
	
	function saveDecisionDeletePopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);

		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			if(null != ($trigger = DAO_TriggerEvent::get($node->trigger_id))) {
				// Load the trigger's tree so we can delete all children from this node
				$data = $trigger->getDecisionTreeData();
				$depths = $data['depths'];
				
				$ids_to_delete = array();

				$found = false;
				foreach($depths as $node_id => $depth) {
					if($node_id == $id) {
						$found = true;
						$ids_to_delete[] = $id;
						continue;
					}
						
					if(!$found)
						continue;
						
					// Continue deleting (queuing IDs) while depth > origin
					if($depth > $depths[$id]) {
						$ids_to_delete[] = $node_id;
					} else {
						$found = false;
					}
				}
				
				DAO_DecisionNode::delete($ids_to_delete);
			}
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			DAO_TriggerEvent::delete($trigger_id);
			
		}
	}
	
	function showDecisionPopupAction() {
		@$va_id = DevblocksPlatform::importGPC($_REQUEST['va_id'],'integer', 0);
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$only_event_ids = DevblocksPlatform::importGPC($_REQUEST['only_event_ids'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id)) { // Edit node
			// Model
			if(null != ($model = DAO_DecisionNode::get($id))) {
				$tpl->assign('id', $id);
				$tpl->assign('model', $model);
				$tpl->assign('trigger_id', $model->trigger_id);
				$type = $model->node_type;
				$trigger_id = $model->trigger_id;
			}
			
		} elseif(isset($_REQUEST['parent_id'])) { // Add child node
			@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
			@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			$tpl->assign('parent_id', $parent_id);
			$tpl->assign('type', $type);
			$tpl->assign('trigger_id', $trigger_id);
			
		} elseif(isset($_REQUEST['trigger_id'])) { // Add child node
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			$tpl->assign('trigger_id', $trigger_id);
			$type = 'trigger';
			
			if(empty($trigger_id)) {
				$trigger = null;
				
				$va = DAO_VirtualAttendant::get($va_id);
				$tpl->assign('va', $va);
				
				$events = Extension_DevblocksEvent::getByContext($va->owner_context, false);

				// Filter the available events by VA
				$events = $va->filterEventsByAllowed($events);
				
				// Are we filtering the available events?
				if(!empty($only_event_ids)) {
					$only_event_ids = DevblocksPlatform::parseCsvString($only_event_ids,false,'string');
					
					if(is_array($events))
					foreach($events as $event_id => $event) {
						if(!in_array($event_id, $only_event_ids))
							unset($events[$event_id]);
					}
				}
				
				$tpl->assign('events', $events);
				
			} else {
				if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				}
			}
			
		}

		if(!isset($trigger) && !empty($trigger_id))
			if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				return;
				
		$tpl->assign('trigger', $trigger);
		
		$event = null;
		if(!empty($trigger))
			if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
				return;

		$tpl->assign('event', $event);
		
		// Template
		switch($type) {
			case 'switch':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/switch.tpl');
				break;
				
			case 'outcome':
				if(null != ($evt = $trigger->getEvent()))
					$tpl->assign('conditions', $evt->getConditions($trigger));
					
				// Action labels
				$labels = $evt->getLabels($trigger);
				$tpl->assign('labels', $labels);
					
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/outcome.tpl');
				break;
				
			case 'action':
				if(null != ($evt = $trigger->getEvent()))
					$tpl->assign('actions', $evt->getActions($trigger));
					
				// Workers
				$tpl->assign('workers', DAO_Worker::getAll());
				
				// Action labels
				$labels = $evt->getLabels($trigger);
				$tpl->assign('labels', $labels);

				// Template
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/action.tpl');
				break;
				
			case 'trigger':
				if(!empty($trigger)) {
					$ext = DevblocksPlatform::getExtension($trigger->event_point, false);
					$tpl->assign('ext', $ext);
				}
				
				// Contexts that can show up in VA vars
				$list_contexts = Extension_DevblocksContext::getAll(false, 'va_variable');
				$tpl->assign('list_contexts', $list_contexts);
				
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/trigger.tpl');
				break;
		}
		
		// Free
		$tpl->clearAssign('actions');
		$tpl->clearAssign('conditions');
		$tpl->clearAssign('event');
		$tpl->clearAssign('ext');
		$tpl->clearAssign('id');
		$tpl->clearAssign('model');
		$tpl->clearAssign('parent_id');
		
		$tpl->clearAssign('trigger');
		$tpl->clearAssign('trigger_id');
		$tpl->clearAssign('type');
	}

	function showBehaviorSimulatorPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		$tpl->assign('trigger', $trigger);

		if(null == ($ext_event = DevblocksPlatform::getExtension($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
			return;

		$event_model = $ext_event->generateSampleEventModel($trigger, $context_id);
		$ext_event->setEvent($event_model);
		
		$event_params_json = json_encode($event_model->params);
		$tpl->assign('event_params_json', $event_params_json);
		
		$tpl->assign('ext_event', $ext_event);
		$tpl->assign('event_model', $event_model);
		
		$labels = $ext_event->getLabels($trigger);
		$values = $ext_event->getValues();
		$dict = new DevblocksDictionaryDelegate($values);

		$conditions = $ext_event->getConditions($trigger);

		$dictionary = array();
		
		foreach($conditions as $k => $v) {
			if(isset($dict->$k)) {
				$dictionary[$k] = array(
					'label' => $v['label'],
					'type' => $v['type'],
					'value' => $dict->$k,
				);
			}
		}
		
		$tpl->assign('dictionary', $dictionary);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/simulate.tpl');
	}
	
	function runBehaviorSimulatorAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$event_params_json = DevblocksPlatform::importGPC($_POST['event_params_json'],'string', '');
		@$custom_values = DevblocksPlatform::importGPC($_POST['values'],'array', array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		$logger = DevblocksPlatform::getConsoleLog('Attendant');
		
		$logger->setLogLevel(7);
		
		ob_start();
		
		$tpl->assign('trigger_id', $trigger_id);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		$tpl->assign('trigger', $trigger);
		
 		if(null == ($ext_event = DevblocksPlatform::getExtension($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
 			return;

		// Set the base event scope
		
		// [TODO] This is hacky and needs to be handled by the extensions
 		
		switch($trigger->event_point) {
			case Event_MailReceivedByApp::ID:
				$event_model = $ext_event->generateSampleEventModel($trigger, 0);
				break;
				
			default:
				$event_model = new Model_DevblocksEvent();
				$event_model->id = $trigger->event_point;
				$event_model_params = json_decode($event_params_json, true);
				$event_model->params = is_array($event_model_params) ? $event_model_params : array();
				break;
		}
		
		$ext_event->setEvent($event_model);

		$tpl->assign('event', $ext_event);
		
		// Format custom values
		
		if(is_array($trigger->variables))
		foreach($trigger->variables as $var_key => $var) {
			if(!empty($var['is_private']))
				continue;
			
			if(!isset($custom_values[$var_key]))
				continue;
			
			try {
				$custom_values[$var_key] = $trigger->formatVariable($var, $custom_values[$var_key]);
				
			} catch(Exception $e) {
				
			}
		}
		
		// Merge baseline values with user overrides
		
		$values = $ext_event->getValues();
		$values = array_merge($values, $custom_values);
 		
 		// Get conditions
 		
 		$conditions = $ext_event->getConditions($trigger);
 		
 		// Sanitize values
 		
 		if(is_array($values))
 		foreach($values as $k => $v) {
 			if(
 				((isset($conditions[$k]) && $conditions[$k]['type'] == Model_CustomField::TYPE_DATE)
 					|| $k == '_current_time')
 			) {
 				if(!is_numeric($v))
 					$values[$k] = strtotime($v);
 			}
 		}
 		
 		// Dictionary
 		
 		$dict = new DevblocksDictionaryDelegate($values);
 		
 		// [TODO] Update variables/values on assocated worklists
 		
 		// Behavior data

		$behavior_data = $trigger->getDecisionTreeData();
		$tpl->assign('behavior_data', $behavior_data);

		$behavior_path = $trigger->runDecisionTree($dict, true);
		$tpl->assign('behavior_path', $behavior_path);
		
		if(isset($dict->_simulator_output))
			$tpl->assign('simulator_output', $dict->_simulator_output);
		
		$logger->setLogLevel(0);

		$conditions_output = ob_get_contents();

		ob_end_clean();

		$conditions_output = preg_replace("/^\[INFO\] \[Attendant\] /m", '', strip_tags($conditions_output));
		$tpl->assign('conditions_output', trim($conditions_output));
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/simulator/results.tpl');
	}
	
	function showBehaviorExportPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		$behavior_json = $trigger->exportToJson();
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('behavior_json', $behavior_json);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_export.tpl');
	}
	
	function saveBehaviorImportJsonAction() {
		@$import_json = DevblocksPlatform::importGPC($_REQUEST['import_json'],'string', '');
		@$va_id = DevblocksPlatform::importGPC($_REQUEST['va_id'],'integer', 0);
		@$configure = DevblocksPlatform::importGPC($_REQUEST['configure'],'array', array());
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-type: application/json');
		
		$trigger_id = null;
		
		try {
			if(empty($import_json))
				throw new Exception("Import JSON not provided.");
			
			if(
				false == ($json = json_decode($import_json, true))
				|| !isset($json['behavior'])
				|| !isset($json['behavior']['event']['key'])
			) {
				throw new Exception("Import JSON is an invalid format.");
			}

			@$event_point = $json['behavior']['event']['key'];
			
			if(
				// [TODO] We should have an Extension_DevblocksEvent::get($id) method
				false == ($event = DevblocksPlatform::getExtension($event_point, true))
				|| !($event instanceof Extension_DevblocksEvent)
			) {
				throw new Exception("Invalid event in the provided behavior.");
			}
			
			// Verify the event can be owned by this context
			
			if(false == ($va = DAO_VirtualAttendant::get($va_id))) {
				throw new Exception("Invalid Virtual Attendant as import destination.");
			}
			
			// Verify that the VA is allowed to make these events
			
			if(!$va->isWriteableByActor($active_worker))
				throw new Exception("You don't have access to modify this Virtual Attendant.");
			
			// Verify that the active worker has access to make events for this context
			
			if(!$va->canUseEvent($event_point))
				throw new Exception("This Virtual Attendant can't make behaviors for this event.");
			
			if(
				!isset($event->manifest->params['contexts'])
				|| !isset($event->manifest->params['contexts'][0])
				|| !is_array($event->manifest->params['contexts'][0])
				|| !in_array($va->owner_context, array_keys($event->manifest->params['contexts'][0]))
			) {
				throw new Exception("Unable to bind event to this owner.");
			}
			
			// Allow prompted configuration of the VA behavior import
			
			@$configure_fields = $json['behavior']['configure'];
			
			// Are there configurable fields in this import file?
			if(is_array($configure_fields) && !empty($configure_fields)) {
				// If the worker has provided the configuration, make the changes to JSON array
				if(!empty($configure)) {
					foreach($configure_fields as $config_field_idx => $config_field) {
						if(!isset($config_field['path']))
							continue;
						
						if(!isset($configure[$config_field_idx]))
							continue;
						
						$ptr =& DevblocksPlatform::jsonGetPointerFromPath($json, $config_field['path']);
						$ptr = $configure[$config_field_idx];
					}
					
				// If the worker hasn't been prompted, do that now
				} else {
					$tpl = DevblocksPlatform::getTemplateService();
					$tpl->assign('import_json', $import_json);
					$tpl->assign('import_fields', $configure_fields);
					$config_html = $tpl->fetch('devblocks:cerberusweb.core::internal/import/prompted/configure_json_import.tpl');
					
					echo json_encode(array(
						'config_html' => $config_html,
					));
					return;
				}
			}
			
			// Create behavior record
			// [TODO] We need to sanitize this data
			
			$fields = array(
				DAO_TriggerEvent::TITLE => $json['behavior']['title'],
				DAO_TriggerEvent::EVENT_POINT => $event_point,
				DAO_TriggerEvent::IS_PRIVATE => @$json['behavior']['is_private'] ? 1 : 0,
				DAO_TriggerEvent::VARIABLES_JSON => isset($json['behavior']['variables']) ? json_encode($json['behavior']['variables']) : '',
				DAO_TriggerEvent::VIRTUAL_ATTENDANT_ID => $va->id,
				DAO_TriggerEvent::POS => DAO_TriggerEvent::getNextPosByVirtualAttendantAndEvent($va->id, $event_point),
				DAO_TriggerEvent::IS_DISABLED => 1, // default to disabled until successfully imported
			);
			
			$trigger_id = DAO_TriggerEvent::create($fields);
			
			// Create records for all child nodes and link them to the proper parents

			if(isset($json['behavior']['nodes']))
			if(false == $this->_recursiveImportDecisionNodes($json['behavior']['nodes'], $trigger_id, 0))
				throw new Exception("Failed to import nodes");
			
			// Enable the new behavior since we've succeeded
			
			DAO_TriggerEvent::update($trigger_id, array(
				DAO_TriggerEvent::IS_DISABLED => @$json['behavior']['is_disabled'] ? 1 : 0,
			));
			
			echo json_encode(array(
				'trigger_id' => $trigger_id,
				'event_point' => $event_point,
			));
			return;
			
		} catch (Exception $e) {
			if(!empty($trigger_id))
				DAO_TriggerEvent::delete($trigger_id);
			
			echo json_encode(array(false, $e->getMessage()));
			return;
		}
	}
	
	private function _recursiveImportDecisionNodes($nodes, $trigger_id, $parent_id) {
		if(!is_array($nodes) || empty($nodes))
			return;
		
		$pos = 0;
		
		// [TODO] We need to sanitize this data
		foreach($nodes as $node) {
			//var_dump($node);
			
			if(
				!isset($node['type'])
				|| !isset($node['title'])
				|| !in_array($node['type'], array('switch','outcome','action'))
			)
				return false;
			
			$fields = array(
				DAO_DecisionNode::NODE_TYPE => $node['type'],
				DAO_DecisionNode::TITLE => $node['title'],
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::TRIGGER_ID => $trigger_id,
				DAO_DecisionNode::POS => $pos++,
				DAO_DecisionNode::PARAMS_JSON => isset($node['params']) ? json_encode($node['params']) : '',
			);
			
			$node_id = DAO_DecisionNode::create($fields);
			
			if(isset($node['nodes']) && is_array($node['nodes']))
				if(false == ($result = $this->_recursiveImportDecisionNodes($node['nodes'], $trigger_id, $node_id)))
					return false;
		}
		
		return true;
	}
	
	function showBehaviorParamsAction() {
		@$name_prefix = DevblocksPlatform::importGPC($_REQUEST['name_prefix'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('namePrefix', $name_prefix);
		
		if(null != ($trigger = DAO_TriggerEvent::get($trigger_id)))
			$tpl->assign('macro_params', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::events/_action_behavior_params.tpl');
	}
	
	function showScheduleBehaviorBulkParamsAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($trigger = DAO_TriggerEvent::get($trigger_id)))
			$tpl->assign('macro_params', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/behavior/bulk_params.tpl');
	}
	
	function doDecisionAddConditionAction() {
		@$condition = DevblocksPlatform::importGPC($_REQUEST['condition'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
			
		$event->renderCondition($condition, $trigger, null, $seq);
	}
	
	function doDecisionAddActionAction() {
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
			
		$event->renderAction($action, $trigger, null, $seq);
	}

	function showDecisionTreeAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		if(false == ($va = $trigger->getVirtualAttendant()))
			return;
		
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, false)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$is_writeable = $va->isWriteableByActor($active_worker) ? true : false;
		$tpl->assign('is_writeable', $is_writeable);
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/tree.tpl');
	}
	
	function addTriggerVariableAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('seq', uniqid());
		
		// Contexts that can show up in VA vars
		$list_contexts = Extension_DevblocksContext::getAll(false, 'va_variable');
		$tpl->assign('list_contexts', $list_contexts);

		// New variable
		$var = array(
			'key' => '',
			'type' => $type,
			'label' => 'New Variable',
			'is_private' => 1,
			'params' => array(),
		);
		$tpl->assign('var', $var);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/trigger_variable.tpl');
	}
	
	function saveDecisionPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', '');

		@$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array();
		
		// DAO
		
		if(!empty($id)) { // Edit
			if(null != ($model = DAO_DecisionNode::get($id))) {
				$type = $model->node_type;
				$trigger_id = $model->trigger_id;

				// Security
		
				if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
					return false;
				
				if(false == ($va = $trigger->getVirtualAttendant()))
					return false;
				
				if(!$va->isWriteableByActor($active_worker))
					return false;
				
				// Title changed
				if(0 != strcmp($model->title, $title) && !empty($title))
					DAO_DecisionNode::update($id, array(
						DAO_DecisionNode::TITLE => $title,
					));
			}
			
		} elseif(isset($_REQUEST['parent_id'])) { // Create
			@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');

			// Security
	
			if(!empty($trigger_id)) {
				if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
					return false;
				
				if(false == ($va = $trigger->getVirtualAttendant()))
					return false;
				
				if(!$va->isWriteableByActor($active_worker))
					return false;
			}
			
			$id = DAO_DecisionNode::create(array(
				DAO_DecisionNode::TITLE => $title,
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::TRIGGER_ID => $trigger_id,
				DAO_DecisionNode::NODE_TYPE => $type,
				DAO_DecisionNode::PARAMS_JSON => '',
			));
			
		} elseif(isset($_REQUEST['trigger_id'])) { // Trigger
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', '');
			@$is_disabled = DevblocksPlatform::importGPC($_REQUEST['is_disabled'],'integer', 0);
			@$is_private = DevblocksPlatform::importGPC($_REQUEST['is_private'],'integer', 0);
			@$json = DevblocksPlatform::importGPC($_REQUEST['json'],'integer', 0);

			// Variables

			@$var_idxs = DevblocksPlatform::importGPC($_REQUEST['var'],'array',array());
			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_key'],'array',array());
			@$var_types = DevblocksPlatform::importGPC($_REQUEST['var_type'],'array',array());
			@$var_labels = DevblocksPlatform::importGPC($_REQUEST['var_label'],'array',array());
			@$var_is_private = DevblocksPlatform::importGPC($_REQUEST['var_is_private'],'array',array());
			
			$variables = array();
			
			if(is_array($var_labels))
			foreach($var_labels as $idx => $v) {
				if(empty($var_labels[$idx]))
					continue;
				
				$var_name = 'var_' . DevblocksPlatform::strAlphaNum(DevblocksPlatform::strToPermalink($v),'_');
				$key = strtolower(!empty($var_keys[$idx]) ? $var_keys[$idx] : $var_name);
				
				// Variable params
				@$var_idx = $var_idxs[$idx];
				$var_params = isset($_REQUEST['var_params'.$var_idx]) ? DevblocksPlatform::importGPC($_REQUEST['var_params'.$var_idx],'array',array()) : array();
				
				$variables[$key] = array(
					'key' => $key,
					'label' => $v,
					'type' => $var_types[$idx],
					'is_private' => $var_is_private[$idx],
					'params' => $var_params,
				);
			}
			
			// Create trigger
			if(empty($trigger_id)) {
				@$va_id = DevblocksPlatform::importGPC($_REQUEST['va_id'],'integer', 0);
				@$event_point = DevblocksPlatform::importGPC($_REQUEST['event_point'],'string', '');
				
				// Make sure the extension is valid
				
				if(null == ($ext = DevblocksPlatform::getExtension($event_point, false)))
					return false;

				// Verify permissions

				if(false == ($va = DAO_VirtualAttendant::get($va_id)))
					return false;
				
				if(empty($va) || !$va->isWriteableByActor($active_worker))
					return false;
				
				if(!$va->canUseEvent($event_point))
					return false;
				
				// Only macros can decide to be private or not
				
				if(!isset($ext->params['macro_context']))
					$is_private = 0;
				
				$type = 'trigger';
				
				$pos = DAO_TriggerEvent::getNextPosByVirtualAttendantAndEvent($va_id, $event_point);
				
				// DAO
					
				$trigger_id = DAO_TriggerEvent::create(array(
					DAO_TriggerEvent::VIRTUAL_ATTENDANT_ID => $va_id,
					DAO_TriggerEvent::EVENT_POINT => $event_point,
					DAO_TriggerEvent::TITLE => $title,
					DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
					DAO_TriggerEvent::IS_PRIVATE => !empty($is_private) ? 1 : 0,
					DAO_TriggerEvent::POS => $pos,
					DAO_TriggerEvent::VARIABLES_JSON => json_encode($variables),
				));
				
				if($json) {
					header("Content-Type: text/json;");
					echo json_encode(array(
						'trigger_id' => $trigger_id,
						'event_point' => $event_point,
					));
					exit;
				}
				
			// Update trigger
			} else {
				if(false !== ($trigger = DAO_TriggerEvent::get($trigger_id))) {
					$type = 'trigger';

					// Security
		
					if(false == ($va = $trigger->getVirtualAttendant()))
						return false;
					
					if(!$va->isWriteableByActor($active_worker))
						return false;

					// Fields
					
					if(empty($title))
						if(null != ($ext = DevblocksPlatform::getExtension($trigger->event_point, false)))
							$title = $ext->name;
					
					// Handle deletes
					if(is_array($trigger->variables))
					foreach($trigger->variables as $var => $data) {
						if(!isset($variables[$var])) {
							DAO_DecisionNode::deleteTriggerVar($trigger->id, $var);
						}
					}
					
					DAO_TriggerEvent::update($trigger->id, array(
						DAO_TriggerEvent::TITLE => $title,
						DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
						DAO_TriggerEvent::IS_PRIVATE => !empty($is_private) ? 1 : 0,
						DAO_TriggerEvent::VARIABLES_JSON => json_encode($variables),
					));
				}
			}
			
		}

		// Type-specific properties
		switch($type) {
			case 'switch':
				// Nothing
				break;
				
			case 'outcome':
				@$nodes = DevblocksPlatform::importGPC($_REQUEST['nodes'],'array',array());

				$groups = array();
				$group_key = null;
				
				foreach($nodes as $k) {
					switch($k) {
						case 'any':
						case 'all':
							$groups[] = array(
								'any' => ($k=='any'?1:0),
								'conditions' => array(),
							);
							end($groups);
							$group_key = key($groups);
							break;
							
						default:
							if(!is_numeric($k))
								continue;
							
							$condition = DevblocksPlatform::importGPC($_POST['condition'.$k],'array',array());
							$groups[$group_key]['conditions'][] = $condition;
							break;
					}
				}
				
				DAO_DecisionNode::update($id, array(
					DAO_DecisionNode::PARAMS_JSON => json_encode(array('groups'=>$groups)),
				));
				break;
				
			case 'action':
				@$action_ids = DevblocksPlatform::importGPC($_REQUEST['actions'],'array',array());
				$params = array();
				$params['actions'] = $this->_parseActions($action_ids, $_POST);
				DAO_DecisionNode::update($id, array(
					DAO_DecisionNode::PARAMS_JSON => json_encode($params),
				));
				break;
				
			case 'trigger':
				break;
		}
	}
	
	private function _parseActions($action_ids, $scope) {
		$objects = array();
		
		foreach($action_ids as $action_id) {
			$params = DevblocksPlatform::importGPC($scope['action'.$action_id],'array',array());

			/*
			 * [TODO] This should probably be given to each action extension so they
			 * can make any last minute changes to the persisted params.  We don't really
			 * want to bury the worklist_model_json stuff here since this is global, and
			 * only set_var_* uses this param.
			 */
			if(isset($params['worklist_model_json'])) {
				$params['worklist_model'] = json_decode($params['worklist_model_json'], true);
				unset($params['worklist_model_json']);
			}
			
			$objects[] = $params;
		}
		
		return $objects;
	}

	function showDecisionNodeMenuAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($node = DAO_DecisionNode::get($id)))
			$tpl->assign('node', $node);
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		if(!empty($trigger_id))
			$tpl->assign('trigger_id', $trigger_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/menu.tpl');
	}
	
	function deleteDecisionNodeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		if(!empty($id)) {
			DAO_DecisionNode::delete($id);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			// [TODO] Make sure this worker owns the trigger (or is group mgr)
			if(!empty($trigger_id))
				DAO_TriggerEvent::delete($trigger_id);
		}
	}
	
	function testDecisionEventSnippetsAction() {
		@$prefix = DevblocksPlatform::importGPC($_REQUEST['prefix'],'string','');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer',0);

		$content = '';
		
		if(is_array($_REQUEST['field'])) {
			@$fields = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		
			if(is_array($fields))
			foreach($fields as $field) {
				@$append = DevblocksPlatform::importGPC($_REQUEST[$prefix][$field],'string','');
				$content .= !empty($append) ? ('[' . $field . ']: ' . PHP_EOL . $append . PHP_EOL . PHP_EOL) : '';
			}
			
		} else {
			@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string','');
			@$content = DevblocksPlatform::importGPC($_REQUEST[$prefix][$field],'string','');
		}
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		$event = $trigger->getEvent();
		$event_model = $event->generateSampleEventModel($trigger);
		$event->setEvent($event_model);
		$values = $event->getValues();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$success = false;
		$output = '';

		if(isset($values)) {
			// Try to build the template
			if(false === ($out = $tpl_builder->build($content, $values))) {
				// If we failed, show the compile errors
				$errors = $tpl_builder->getErrors();
				$success = false;
				$output = @array_shift($errors);
			} else {
				// If successful, return the parsed template
				$success = true;
				$output = $out;
			}
		}

		$tpl->assign('success', $success);
		$tpl->assign('output', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
	}
	
	// Scheduled Behavior
	
	function showScheduledBehaviorTabAction() {
		Subcontroller_Internal_VirtualAttendants::showScheduledBehaviorAction();
	}
	
	// Utils

	function transformMarkupToHTMLAction() {
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string', '');
		@$data = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		
		$body = '';
		
		switch($format) {
			case 'markdown':
				$body = DevblocksPlatform::parseMarkdown($data);
				break;
			case 'html':
			default:
				$body = $data;
				break;
		}
		
		$tpl->assign('body', $body);
		
		$tpl->display('devblocks:cerberusweb.core::internal/html_editor/preview.tpl');
	}

	// Comments

	function showTabContextCommentsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		if(!empty($point))
			$visit->set($point, 'comments');

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$comments = DAO_Comment::getByContext($context, $context_id);
		$tpl->assign('comments', $comments);

		$tpl->display('devblocks:cerberusweb.core::internal/comments/tab.tpl');
	}

	function commentShowPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$notify_workers = array();
		
		// Automatically tell anybody associated with this context object
		switch($context) {
			case CerberusContexts::CONTEXT_WORKER:
				$notify_workers[] = $context_id;
				break;
				
			case CerberusContexts::CONTEXT_GROUP:
				if(null != ($group = DAO_Group::get($context_id))) {
					$members = $group->getMembers();
					$notify_workers = array_keys($members);
				}
				break;
		}
		
		$tpl->assign('notify_workers', $notify_workers);

		$tpl->display('devblocks:cerberusweb.core::internal/comments/peek.tpl');
	}

	function commentSavePopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_ids'],'array',array());

		$translate = DevblocksPlatform::getTranslationService();

		// Worker is logged in
		if(null === ($active_worker = CerberusApplication::getActiveWorker()))
			return;

		// [TODO] Validate context + ID
		// [TODO] Validate ACL

		// Form was filled in
		if(empty($context) || empty($context_id) || empty($comment))
			return;

		@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		
		$fields = array(
			DAO_Comment::CONTEXT => $context,
			DAO_Comment::CONTEXT_ID => $context_id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
			DAO_Comment::COMMENT => $comment,
			DAO_Comment::CREATED => time(),
		);
		$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids, $file_ids);
	}

	function commentDeleteAction() {
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		DAO_Comment::delete($comment_id);
	}
};
