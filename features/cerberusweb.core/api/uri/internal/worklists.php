<?php /** @noinspection PhpUnused */

use Cerb\Records\FileImporter;

/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class PageSection_InternalWorklists extends Extension_PageSection {
	function render() {}
	
	public function handleActionForPage(string $action, string $scope=null) {
		if('internalAction' == $scope) {
			switch ($action) {
				case 'addFilter':
					return $this->_internalAction_addFilter();
				case 'broadcastTest':
					return $this->_internalAction_broadcastTest();
				case 'customize':
					return $this->_internalAction_customize();
				case 'saveCustomize':
					return $this->_internalAction_saveCustomize();
				case 'page':
					return $this->_internalAction_page();
				case 'refresh':
					return $this->_internalAction_refresh();
				case 'renderCopy':
					return $this->_internalAction_renderCopy();
				case 'saveCopy':
					return $this->_internalAction_saveCopy();
				case 'renderImportPopup':
					return $this->_internalAction_renderImportPopup();
				case 'renderImportMappingPopup':
					return $this->_internalAction_renderImportMappingPopup();
				case 'parseImportFile':
					return $this->_internalAction_parseImportFile();
				case 'importPreview':
					return $this->_internalAction_importPreview();
				case 'saveImport':
					return $this->_internalAction_saveImport();
				case 'renderExport':
					return $this->_internalAction_renderExport();
				case 'saveExport':
					return $this->_internalAction_saveExport();
				case 'serializeView':
					return $this->_internalAction_serializeView();
				case 'showQuickSearchPopup':
					return $this->_internalAction_showQuickSearchPopup();
				case 'sort':
					return $this->_internalAction_sort();
				case 'subtotal':
					return $this->_internalAction_subtotal();
				case 'viewBulkUpdateWithCursor':
					return $this->_internalAction_viewBulkUpdateWithCursor();
				case 'viewBulkUpdateNextCursorJson':
					return $this->_internalAction_viewBulkUpdateNextCursorJson();
			}
		}
		return false;
	}
	
	private function _internalAction_refresh() {
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null);
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->render();
		}
	}
	
	private function _internalAction_sort() {
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null);
		$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy'] ?? null);
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doSortBy($sortBy);
			$view->render();
		}
	}
	
	private function _internalAction_page() {
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null);
		$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page'] ?? null));
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doPage($page);
			$view->render();
		}
	}
	
	private function _viewRenderInlineFilters($view, $is_custom=false, $add_mode=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view', $view);
		$tpl->assign('add_mode', $add_mode);
		
		if($is_custom)
			$tpl->assign('is_custom', true);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
	}
	
	private function _internalAction_addFilter() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null);
		$is_custom = DevblocksPlatform::importGPC($_POST['is_custom'] ?? null, 'integer',0);
		
		$add_mode = DevblocksPlatform::importGPC($_POST['add_mode'] ?? null, 'string', null);
		$query = DevblocksPlatform::importGPC($_POST['query'] ?? null, 'string', null);
		
		$field = DevblocksPlatform::importGPC($_POST['field'] ?? null, 'string', null);
		$oper = DevblocksPlatform::importGPC($_POST['oper'] ?? null, 'string', null);
		$value = DevblocksPlatform::importGPC($_POST['value'] ?? null);
		$replace = DevblocksPlatform::importGPC($_POST['replace'] ?? null, 'string', '');
		$field_deletes = DevblocksPlatform::importGPC($_POST['field_deletes'] ?? null, 'array',[]);
		
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
		
		// Remove the same param at the top level
		if($replace) {
			$view->removeParamByField($replace);
		}
		
		// Add
		switch($add_mode) {
			case 'query':
				$view->addParamsWithQuickSearch($query, false);
				break;
			
			default:
				if(!empty($field)) {
					$view->doSetCriteria($field, $oper, $value);
				}
				break;
		}
		
		// If this is a custom worklist we want to swap the req+editable params back
		if($is_custom) {
			$view->addParamsRequired($view->getEditableParams(), true);
			$view->addParams($original_params, true);
		}
		
		// Reset the paging when adding a filter
		$view->renderPage = 0;
		
		$this->_viewRenderInlineFilters($view, $is_custom, $add_mode);
	}
	
	private function _internalAction_customize() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null);
		
		$tpl->assign('id', $id);
		
		if(DevblocksPlatform::strStartsWith($id, ['profile_widget_', 'widget_'])) {
			$error_title = "Configure the widget";
			$tpl->assign('error_title', $error_title);
			
			$error_msg = "This worklist is configured in the widget.";
			$tpl->assign('error_message', $error_msg);
			
			$tpl->display('devblocks:cerberusweb.core::internal/views/view_error.tpl');
			return;
		}
		
		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		// Columns
		
		$columns = [];
		$columns_available = $view->getColumnsAvailable();
		
		// Start with the currently selected columns
		if(is_array($view->view_columns))
			foreach($view->view_columns as $token) {
				if(isset($columns_available[$token]) && !isset($columns[$token]))
					$columns[$token] = $columns_available[$token];
			}
		
		// Finally, append the remaining columns
		foreach($columns_available as $token => $col) {
			if(!isset($columns[$token]))
				if($token && $col->db_label)
					$columns[$token] = $col;
		}
		
		$tpl->assign('columns', $columns);
		
		// Custom worklists
		
		if($view->isCustom()) {
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
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker)) {
					$tpl->display('devblocks:cerberusweb.core::internal/workspaces/customize_no_acl.tpl');
					return;
				}
				
			} catch(Exception) {
				// [TODO] Logger
				return;
			}
		}
		
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view.tpl');
	}
	
	private function _internalAction_renderCopy() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null, 'string');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		
		$worker_pages = DAO_WorkspacePage::getByWorker($active_worker);
		
		// Only worklists tabs
		$tabs = array_filter(
			DAO_WorkspaceTab::getAll(),
			function(Model_WorkspaceTab $tab) use (&$worker_pages) {
				return $tab->extension_id == 'core.workspace.tab.worklists'
					&& array_key_exists($tab->workspace_page_id, $worker_pages)
				;
			}
		);
		
		// Only pages with available tabs
		$worker_pages = array_intersect_key($worker_pages, array_flip(array_column($tabs, 'workspace_page_id')));
		
		$tpl->assign('pages', $worker_pages);
		$tpl->assign('tabs', $tabs);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/copy.tpl');
	}
	
	private function _internalAction_saveCopy() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$list_title = DevblocksPlatform::importGPC($_POST['list_title'] ?? null, 'string', '');
		$workspace_page_id = DevblocksPlatform::importGPC($_POST['workspace_page_id'] ?? null, 'integer', 0);
		$workspace_tab_id = DevblocksPlatform::importGPC($_POST['workspace_tab_id'] ?? null, 'integer', 0);
		
		if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_page_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(null == ($workspace_tab = DAO_WorkspaceTab::get($workspace_tab_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if($workspace_tab->workspace_page_id != $workspace_page->id)
			return;
		
		if(empty($list_title))
			$list_title = DevblocksPlatform::translate('mail.workspaces.new_list');
		
		$workspace_context = $view->getContext();
		
		if(empty($workspace_context))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Save the new worklist
		$fields = [
			DAO_WorkspaceList::COLUMNS_JSON => json_encode($view->view_columns),
			DAO_WorkspaceList::CONTEXT => $workspace_context,
			DAO_WorkspaceList::NAME => $list_title,
			DAO_WorkspaceList::OPTIONS_JSON => json_encode($view->options),
			DAO_WorkspaceList::PARAMS_EDITABLE_JSON => json_encode($view->getEditableParams()),
			DAO_WorkspaceList::PARAMS_REQUIRED_JSON => json_encode($view->getParamsRequired()),
			DAO_WorkspaceList::PARAMS_REQUIRED_QUERY => $view->getParamsRequiredQuery(),
			DAO_WorkspaceList::RENDER_LIMIT => $view->renderLimit,
			DAO_WorkspaceList::RENDER_SORT_JSON => json_encode($view->getSorts()),
			DAO_WorkspaceList::RENDER_SUBTOTALS => $view->renderSubtotals,
			DAO_WorkspaceList::WORKSPACE_TAB_ID => $workspace_tab_id,
			DAO_WorkspaceList::WORKSPACE_TAB_POS => 99,
		];
		$new_id = DAO_WorkspaceList::create($fields);
		
		DAO_WorkerViewModel::deleteByViewId('cust_' . $new_id);
		
		$view->render();
	}
	
	private function _internalAction_viewBulkUpdateWithCursor() {
		$tpl = DevblocksPlatform::services()->template();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$cursor = DevblocksPlatform::importGPC($_POST['cursor'] ?? null, 'string', '');
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		if(empty($cursor))
			return;
		
		$tpl->assign('cursor', $cursor);
		$tpl->assign('view_id', $view_id);
		
		$total = DAO_ContextBulkUpdate::getTotalByCursor($cursor);
		$tpl->assign('total', $total);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_bulk_progress.tpl');
	}
	
	private function _internalAction_viewBulkUpdateNextCursorJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$cursor = DevblocksPlatform::importGPC($_POST['cursor'] ?? null, 'string', '');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if(empty($cursor))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$update = DAO_ContextBulkUpdate::getNextByCursor($cursor);
		
		// We have another job
		if($update) {
			if(false == ($context_ext = Extension_DevblocksContext::get($update->context)))
				return false;
			
			// Make sure non-admin current workers have access to change these IDs, or remove them
			if(!$active_worker->is_superuser) {
				$acl_results = CerberusContexts::isWriteableByActor($update->context, $update->context_ids, $active_worker);
				
				if(is_array($acl_results)) {
					$acl_results = array_filter($acl_results, function($bool) {
						return $bool;
					});
				}
				
				$update->context_ids = array_keys($acl_results);
			}
			
			// If no IDs are left, we're done
			if(!$update->context_ids) {
				echo json_encode(array(
					'completed' => true,
				));
				return;
			}
			
			$dao_class = $context_ext->getDaoClass();
			$dao_class::bulkUpdate($update);
			
			echo json_encode(array(
				'completed' => false,
				'count' => $update->num_records,
			));
			
			// We're done
		} else {
			echo json_encode(array(
				'completed' => true,
			));
		}
	}
	
	private function _internalAction_broadcastTest() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		if(!($view = C4_AbstractViewLoader::getView($view_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$view->setAutoPersist(false);
		
		$view_class = get_class($view);
		
		if(false == ($context_ext = Extension_DevblocksContext::getByViewClass($view_class, true)))
			return;
		
		/* @var $context_ext IDevblocksContextBroadcast */
		if(!($context_ext instanceof IDevblocksContextBroadcast)) {
			echo "ERROR: This record type does not support broadcasts.";
			return;
		}
		
		$search_class = $context_ext->getSearchClass();
		
		$broadcast_to = DevblocksPlatform::importGPC($_POST['broadcast_to'] ?? null, 'array',[]);
		$broadcast_subject = DevblocksPlatform::importGPC($_POST['broadcast_subject'] ?? null, 'string',null);
		$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'] ?? null, 'string',null);
		$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'] ?? null, 'string',null);
		$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'] ?? null, 'integer',0);
		$broadcast_group_id = DevblocksPlatform::importGPC($_POST['broadcast_group_id'] ?? null, 'integer',0);
		$broadcast_bucket_id = DevblocksPlatform::importGPC($_POST['broadcast_bucket_id'] ?? null, 'integer',0);
		
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string','');
		$ids = DevblocksPlatform::importGPC($_POST['ids'] ?? null, 'string','');
		
		// Filter to checked
		if('checks' == $filter && !empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria($search_class::ID, 'in', explode(',', $ids)));
		}
		
		$results = $view->getDataSample(1);
		
		if(empty($results)) {
			$success = false;
			$output = "ERROR: This worklist is empty.";
			
		} else {
			$dict = DevblocksDictionaryDelegate::instance([
				'_context' => $context_ext->id,
				'id' => current($results),
			]);
			
			$broadcast_email_id = 0;
			
			if($broadcast_to) {
				if (false == ($recipients = $context_ext->broadcastRecipientFieldsToEmails($broadcast_to, $dict))) {
					$broadcast_email_id = 0;
					
				} else {
					shuffle($recipients);
					
					if (false == ($email = DAO_Address::lookupAddress($recipients[0], true))) {
						$broadcast_email_id = 0;
					} else {
						$broadcast_email_id = $email->id;
					}
				}
			}
			
			// Load recipient placeholders
			$dict->broadcast_email__context = CerberusContexts::CONTEXT_ADDRESS;
			$dict->broadcast_email_id = $broadcast_email_id;
			$dict->broadcast_email_;
			
			// Templates
			
			if(!empty($broadcast_subject)) {
				$template = "Subject: $broadcast_subject\n\n$broadcast_message";
			} else {
				$template = "$broadcast_message";
			}
			
			$message_properties = [
				'worker_id' => $active_worker->id,
				'content' => $template,
				'content_format' => $broadcast_format,
				'group_id' => $broadcast_group_id ?: $dict->get('group_id', 0),
				'bucket_id' => $broadcast_bucket_id ?: $dict->get('bucket_id', 0),
				'html_template_id' => $broadcast_html_template_id,
			];
			
			CerberusMail::parseBroadcastHashCommands($message_properties);
			
			if(false === (@$out = $tpl_builder->build($message_properties['content'], $dict))) {
				// If we failed, show the compile errors
				$errors = $tpl_builder->getErrors();
				$success= false;
				$output = @array_shift($errors);
				
			} else {
				// If successful, return the parsed template
				$success = true;
				$output = $out;
				
				switch($broadcast_format) {
					case 'parsedown':
						// Markdown
						$output = DevblocksPlatform::parseMarkdown($output);
						
						// HTML Template
						
						$html_template = null;
						
						if($broadcast_html_template_id)
							$html_template = DAO_MailHtmlTemplate::get($broadcast_html_template_id);
						
						if(!$html_template && false != ($group = DAO_Group::get($broadcast_group_id)))
							$html_template = $group->getReplyHtmlTemplate(0);
						
						if($html_template) {
							$template_values = [
								'message_body' => $output,
								'group__context' => CerberusContexts::CONTEXT_GROUP,
								'group_id' => $message_properties['group_id'] ?? 0,
								'bucket__context' => CerberusContexts::CONTEXT_BUCKET,
								'bucket_id' => $message_properties['bucket_id'] ?? 0,
								'message_id_header' => sprintf("<%s@message.example>", sha1(random_bytes(32))),
							];
							
							@$output = $tpl_builder->build($html_template->content, $template_values);
						}
						
						// HTML Purify
						$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
						$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
						break;
					
					default:
						$output = nl2br(DevblocksPlatform::strEscapeHtml($output));
						break;
				}
			}
			
			if($success) {
				$tpl->assign('css_class', 'emailBodyHtmlLight');
				$tpl->assign('content', $output);
				$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
				
			} else {
				echo $output;
			}
		}
	}
	
	private function _internalAction_renderExport() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$view_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null);
		
		$tpl->assign('view_id', $view_id);
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return false;
		
		$tpl->assign('view', $view);
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return false;
		
		/* @var $context_ext Extension_DevblocksContext */
		
		// Check privs
		if(!$active_worker->hasPriv(sprintf("contexts.%s.export", $context_ext->id)))
			return false;
		
		// Check prefs
		
		$pref_key_prefix = sprintf("worklist.%s.",
			$context_ext->manifest->getParam('uri', $context_ext->id)
		);
		
		if(null == ($tokens = DAO_WorkerPref::getAsJson($active_worker->id, $pref_key_prefix . 'export_tokens'))) {
			$tokens = $context_ext->getCardProperties();
			
			// Push _label into the front of $tokens if not set
			if(!in_array('_label', $tokens))
				array_unshift($tokens, '_label');
		}
		
		// Template
		
		$tpl->assign('tokens', $tokens);
		
		$export_kata_default = <<< EOD
		# Enter worklist export KATA (use Ctrl+Space for autocompletion)
		column/id:
		
		column/_label:
		  label: Label
		  value@raw: {{_label|trim}}
		EOD;
		
		$export_kata = DAO_WorkerPref::get($active_worker->id, $pref_key_prefix . 'export_kata', $export_kata_default);
		
		$tpl->assign('export_kata', $export_kata);
		
		$labels = $values = [];
		CerberusContexts::getContext($context_ext->id, null, $labels, $values, '', true, false);
		$tpl->assign('labels', $labels);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_export.tpl');
	}
	
	private function _internalAction_saveExport() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$cursor_key = DevblocksPlatform::importGPC($_POST['cursor_key'] ?? null, 'string', '');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		try {
			if(!$active_worker)
				throw new Exception_DevblocksAjaxError("Access denied.");
			
			if(empty($cursor_key)) {
				$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
				$export_as = DevblocksPlatform::importGPC($_POST['export_as'] ?? null, 'string', 'csv');
				$export_mode = DevblocksPlatform::importGPC($_POST['export_mode'] ?? null, 'string', '');
				
				$tokens = DevblocksPlatform::importGPC($_POST['tokens'] ?? null, 'array', []);
				$format_timestamps = DevblocksPlatform::importGPC($_POST['format_timestamps'] ?? null, 'integer', 0);
				
				$export_kata = DevblocksPlatform::importGPC($_POST['export_kata'] ?? null, 'string', '');
				
				if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
					throw new Exception_DevblocksAjaxError("Invalid worklist.");
				
				if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
					throw new Exception_DevblocksAjaxError("Invalid worklist record type.");
				
				// Check prefs
				
				$pref_key_prefix = sprintf("worklist.%s.",
					$context_ext->manifest->getParam('uri', $context_ext->id)
				);
				
				if(!$export_mode) {
					DAO_WorkerPref::setAsJson($active_worker->id, $pref_key_prefix . 'export_tokens', $tokens);
					
				} else if('kata' == $export_mode) {
					$kata = DevblocksPlatform::services()->kata();
					
					if(!$kata->validate($export_kata, CerberusApplication::kataSchemas()->worklistExport(), $error))
						throw new Exception_DevblocksAjaxError("Export KATA Error: " . $error);
					
					DAO_WorkerPref::set($active_worker->id, $pref_key_prefix . 'export_kata', $export_kata);
				}
				
				if(!isset($_SESSION['view_export_cursors']))
					$_SESSION['view_export_cursors']  = [];
				
				$cursor_key = sha1(json_encode([$view_id, $tokens, $export_as, time()]));
				
				$cursor = [
					'key' => $cursor_key,
					'view_id' => $view_id,
					'tokens' => $tokens,
					'export_as' => $export_as,
					'export_mode' => $export_mode,
					'format_timestamps' => $format_timestamps,
					'export_kata' => $export_kata,
					'page' => 0,
					'rows_exported' => 0,
					'completed' => false,
					'temp_file' => APP_TEMP_PATH . '/' . $cursor_key . '.tmp',
					'attachment_name' => null,
					'attachment_url' => null,
				];
				
				$_SESSION['view_export_cursors'][$cursor_key] = $cursor;
			}
			
			$cursor = $this->_viewIncrementalExport($cursor_key);
			echo json_encode($cursor);
			
		} catch (Exception_DevblocksAjaxError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			return;
			
		} catch (Exception $e) {
			echo json_encode([
				'error' => 'An unknown error occurred.',
			]);
			return;
		}
	}
	
	/**
	 * @param string $cursor_key
	 * @return array|false
	 * @throws Exception_DevblocksAjaxError
	 */
	private function _viewIncrementalExport($cursor_key) {
		if(!isset($_SESSION['view_export_cursors'][$cursor_key]))
			throw new Exception_DevblocksAjaxError("Cursor not found.");
		
		// Load the cursor and do the next step, then return JSON
		$cursor =& $_SESSION['view_export_cursors'][$cursor_key];
		
		if(!is_array($cursor))
			throw new Exception_DevblocksAjaxError("Invalid cursor.");
		
		$mime_type = null;
		
		switch($cursor['export_as']) {
			case 'csv':
				$this->_viewIncrementExportAsCsv($cursor);
				$mime_type = 'text/csv';
				break;
			
			case 'json':
				$this->_viewIncrementExportAsJson($cursor);
				$mime_type = 'application/json';
				break;
			
			case 'jsonl':
				$this->_viewIncrementExportAsJsonl($cursor);
				$mime_type = 'text/plain';
				break;
			
			case 'xml':
				$this->_viewIncrementExportAsXml($cursor);
				$mime_type = 'text/xml';
				break;
		}
		
		if($cursor['completed']) {
			@$sha1_hash = sha1_file($cursor['temp_file'], false);
			$file_name = 'export.' . $cursor['export_as'];
			
			$url_writer = DevblocksPlatform::services()->url();
			
			// Move the temp file to attachments
			$fields = [
				DAO_Attachment::NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $mime_type,
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
				DAO_Attachment::UPDATED => time(),
			];
			
			if(!($id = DAO_Attachment::create($fields)))
				return false;
			
			// [TODO] This is a temporary workaround to allow workers to view exports they create
			$_SESSION['view_export_file_id'] = $id;

			if(!($fp = fopen($cursor['temp_file'], 'r')))
				return false;
			
			Storage_Attachments::put($id, $fp);
			fclose($fp);
			unlink($cursor['temp_file']);
			
			unset($_SESSION['view_export_cursors'][$cursor_key]);
			
			$cursor['attachment_name'] = $file_name;
			$cursor['attachment_url'] = $url_writer->write('c=files&id=' . $id . '&name=' . $file_name);
		}
		
		return $cursor;
	}
	
	private function _getViewFromCursor(array $cursor) {
		$view_id = $cursor['view_id'];
		
		if(!($view = C4_AbstractViewLoader::getView($view_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$view->setAutoPersist(false);
		
		// Override display
		$view->view_columns = [];
		$view->renderPage = $cursor['page'];
		$view->renderLimit = 200;
		
		return $view;
	}
	
	private function _getDictionariesFromView(C4_AbstractView $view, Extension_DevblocksContext $context_ext, &$count=0) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Rows
		$results = $view->getDataAsObjects();
		
		$count = count($results);
		
		$models = CerberusContexts::getModels($context_ext->id, array_keys($results));
		
		unset($results);
		
		// ACL
		$models = CerberusContexts::filterModelsByActorReadable(get_class($context_ext), $models, $active_worker);
		
		// Models->Dictionaries
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
		
		foreach($dicts as $dict)
			$dict->scrubKeys('_types');

		return $dicts;
	}
	
	private function _getExportColumnsKataFromCursor(array $cursor) : array {
		$export_columns = [];
		$error = null;
		
		if(false === ($export_kata = DevblocksPlatform::services()->kata()->parse($cursor['export_kata'] ?? '', $error)))
			return [];
		
		if(false === ($export_kata = DevblocksPlatform::services()->kata()->formatTree($export_kata, null, $error)))
			return [];
		
		if(!is_array($export_kata))
			return [];
		
		foreach($export_kata as $column_key => $column_data) {
			list($column_type, $column_name) = array_pad(explode('/', $column_key, 2), 2, null);
			
			if('column' != $column_type || !$column_name)
				continue;
			
			foreach($column_data as $k => $v) {
				list($k, $k_annotations) = array_pad(explode('@', $k, 2), 2, null);
				
				// Persist value annotations
				if('value' == $k) {
					$column_data['value'] = $v;
					$column_data['annotations'] = $k_annotations;
				}
			}
			
			$export_columns[$column_name] = [
				'label' => $column_data['label'] ?? DevblocksPlatform::strTitleCase($column_name),
				'value' => $column_data['value'] ?? sprintf('{{%s}}', $column_name),
				'annotations' => $column_data['annotations'] ?? '',
			];
		}
		
		return $export_columns;
	}
	
	private function _getExportKataColumnValue($column_name, $column, DevblocksDictionaryDelegate $dict) : mixed {
		$kata = DevblocksPlatform::services()->kata();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if($column_name && is_array($column)) {
			$column_value = $column['value'] ?? '';
			
			if($column['annotations'] ?? false) {
				$value = $kata->formatTree(
					['value@' . $column['annotations'] => $column_value],
					$dict
				)['value'] ?? '';
				
			} else if(is_array($column_value)) {
				$value = $kata->formatTree($column_value, $dict);
				
			} else {
				$value = $tpl_builder->build($column_value, $dict);
			}
			
		} else {
			$value = '';
		}
		
		return $value;
	}
	
	private function _viewIncrementExportAsCsv(array &$cursor) {
		if(!($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		// Append mode to the temp file
		if(!($fp = fopen($cursor['temp_file'], "a")))
			return;
		
		$count = 0;
		
		if(!($dicts = $this->_getDictionariesFromView($view, $context_ext, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			// Bulk lazy load custom fields across the dictionaries
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'customfields');
			
			$export_columns = $this->_getExportColumnsKataFromCursor($cursor);
			
			// If the first page, add headings
			if(0 == $cursor['page']) {
				$csv_labels = [];
				
				foreach($export_columns as $column_name => $column) {
					$csv_labels[] = ($column['label'] ?? null) ?: DevblocksPlatform::strTitleCase($column_name);
				}
				
				fputcsv($fp, $csv_labels);
				unset($csv_labels);
			}
				
			foreach($dicts as $dict) {
				$fields = [];
				
				foreach($export_columns as $column_name => $column) {
					$value = $this->_getExportKataColumnValue($column_name, $column, $dict);
					$fields[] = is_scalar($value) ? $value : json_encode($value);
				}
				
				fputcsv($fp, $fields);
			}
			
		} else {
			$global_labels = $global_values = [];
			CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
			$global_types = $global_values['_types'];
			
			// Bulk lazy load the tokens across all the dictionaries with a temporary cache
			foreach($cursor['tokens'] as $token) {
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
			}
			
			// If the first page
			if(0 == $cursor['page']) {
				// Headings
				$csv_labels = [];
				
				if(is_array($cursor['tokens']))
					foreach($cursor['tokens'] as $token) {
						$csv_labels[] = trim($global_labels[$token] ?? $token);
					}
				
				fputcsv($fp, $csv_labels);
				
				unset($csv_labels);
			}
			
			foreach($dicts as $dict) {
				$fields = [];
				
				foreach($cursor['tokens'] as $token) {
					$value = '';
					
					if($dict->exists($token))
						$value = $dict->get($token);
					
					if(($global_types[$token] ?? null) == Model_CustomField::TYPE_DATE && $cursor['format_timestamps']) {
						if(empty($value)) {
							$value = '';
						} else if(is_numeric($value)) {
							$value = date('r', $value);
						}
					}
					
					if(is_array($value))
						$value = json_encode($value);
					
					if(!is_string($value) && !is_numeric($value))
						$value = '';
					
					$fields[] = $value;
				}
				
				fputcsv($fp, $fields);
			}
		}
		
		$cursor['page']++;
		$cursor['rows_exported'] += $count;
		
		// If our page isn't full, we're done
		if($count < $view->renderLimit) {
			$cursor['completed'] = true;
		}
		
		fclose($fp);
	}
	
	private function _viewIncrementExportAsJson(array &$cursor) {
		if(!($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		// Append mode to the temp file
		if(!($fp = fopen($cursor['temp_file'], "a")))
			return;
		
		$count = 0;
		
		if(!($dicts = $this->_getDictionariesFromView($view, $context_ext, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			// Bulk lazy load custom fields across the dictionaries
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'customfields');
			
			$export_columns = $this->_getExportColumnsKataFromCursor($cursor);
			
			fputs($fp, "{\"results\": [\n");
			
			$objects = [];
				
			foreach($dicts as $dict) {
				$object = [];
				
				foreach($export_columns as $column_name => $column) {
					$value = $this->_getExportKataColumnValue($column_name, $column, $dict);
					$object[$column_name] = $value;
				}
			
				$objects[] = $object;
			}
			
			$json = trim(json_encode($objects),'[]');
			fputs($fp, $json);
			
		} else {
			$global_labels = $global_values = [];
			CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
			$global_types = $global_values['_types'];
			
			// Bulk lazy load the tokens across all the dictionaries with a temporary cache
			foreach($cursor['tokens'] as $token) {
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
			}
			
			// If the first page
			if(0 == $cursor['page']) {
				fputs($fp, "{\n\"fields\":");
				
				$fields = [];
				
				// Fields
				
				if(is_array($global_labels))
					foreach($cursor['tokens'] as $token) {
						$fields[$token] = [
							'label' => @$global_labels[$token],
							'type' => @$global_types[$token],
						];
					}
				
				fputs($fp, json_encode($fields));
				
				fputs($fp, ",\n\"results\": [\n");
			}
			
			// Rows
			
			if($cursor['page'] > 0)
				fputs($fp, ",\n");
			
			$objects = [];
			
			foreach($dicts as $dict) {
				$object = [];
				
				if(is_array($cursor['tokens']))
					foreach($cursor['tokens'] as $token) {
						$value = $dict->$token;
						
						if($global_types[$token] == Model_CustomField::TYPE_DATE && $cursor['format_timestamps']) {
							if(empty($value)) {
								$value = '';
							} else if (is_numeric($value)) {
								$value = date('r', $value);
							}
						}
						
						$object[$token] = $value;
					}
				
				$objects[] = $object;
				
			}
			
			$json = trim(json_encode($objects),'[]');
			fputs($fp, $json);			
		}			
			
		$cursor['page']++;
		$cursor['rows_exported'] += $count;
		
		// If our page isn't full, we're done
		if($count < $view->renderLimit) {
			$cursor['completed'] = true;
			fputs($fp, "]\n}");
		}
		
		fclose($fp);
	}
	
	private function _viewIncrementExportAsJsonl(array &$cursor) {
		if(!($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		// Append mode to the temp file
		if(!($fp = fopen($cursor['temp_file'], "a")))
			return;
		
		$count = 0;
		
		if(!($dicts = $this->_getDictionariesFromView($view, $context_ext, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			// Bulk lazy load custom fields across the dictionaries
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'customfields');
			
			$export_columns = $this->_getExportColumnsKataFromCursor($cursor);
			
			foreach($dicts as $dict) {
				$object = [];
				
				foreach($export_columns as $column_name => $column) {
					$value = $this->_getExportKataColumnValue($column_name, $column, $dict);
					$object[$column_name] = $value;
				}
				
				$json = json_encode($object);
				fputs($fp, $json . "\n");
			}
			
		} else {
			$global_labels = $global_values = [];
			CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
			$global_types = $global_values['_types'];
			
			// Bulk lazy load the tokens across all the dictionaries with a temporary cache
			foreach($cursor['tokens'] as $token) {
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
			}
			
			foreach($dicts as $dict) {
				$object = [];
				
				if(is_array($cursor['tokens']))
					foreach($cursor['tokens'] as $token) {
						$value = $dict->$token;
						
						if($global_types[$token] == Model_CustomField::TYPE_DATE && $cursor['format_timestamps']) {
							if(empty($value)) {
								$value = '';
							} else if (is_numeric($value)) {
								$value = date('r', $value);
							}
						}
						
						$object[$token] = $value;
					}
				
				$json = json_encode($object);
				fputs($fp, $json . "\n");
			}
			
		}
		
		$cursor['page']++;
		$cursor['rows_exported'] += $count;
		
		// If our page isn't full, we're done
		if($count < $view->renderLimit) {
			$cursor['completed'] = true;
		}
		
		fclose($fp);
	}
	
	private function _viewIncrementExportAsXml(array &$cursor) {
		if(!($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		// Append mode to the temp file
		if(!($fp = fopen($cursor['temp_file'], "a")))
			return;
		
		$count = 0;
		
		if(!($dicts = $this->_getDictionariesFromView($view, $context_ext, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			// Bulk lazy load custom fields across the dictionaries
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'customfields');
			
			$export_columns = $this->_getExportColumnsKataFromCursor($cursor);
			
			if(0 == $cursor['page']) {
				fputs($fp, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
				fputs($fp, "<export>\n");
				fputs($fp, "<results>\n");
			}			
			
			foreach($dicts as $dict) {
				$xml_result = simplexml_load_string("<result/>"); /* @var $xml SimpleXMLElement */
				
				foreach($export_columns as $column_name => $column) {
					$value = $this->_getExportKataColumnValue($column_name, $column, $dict);
					$field = $xml_result->addChild("field", DevblocksPlatform::strEscapeHtml(is_scalar($value) ? $value : json_encode($value)));
					$field->addAttribute("key", $column_name);
				}
				
				$dom = dom_import_simplexml($xml_result);
				fputs($fp, $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement));
			}
			
		} else {
			// Bulk lazy load the tokens across all the dictionaries with a temporary cache
			foreach($cursor['tokens'] as $token) {
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
			}
			
			$global_labels = $global_values = [];
			CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
			$global_types = $global_values['_types'];
			
			// If the first page
			if(0 == $cursor['page']) {
				fputs($fp, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
				fputs($fp, "<export>\n");
				
				// Meta
				
				$xml_fields = simplexml_load_string("<fields/>"); /* @var $xml SimpleXMLElement */
				
				foreach($cursor['tokens'] as $token) {
					$field = $xml_fields->addChild("field");
					$field->addAttribute('key', $token);
					$field->addChild('label', $global_labels[$token] ?? '');
					$field->addChild('type', $global_types[$token] ?? '');
				}
				
				$dom = dom_import_simplexml($xml_fields);
				fputs($fp, $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement));
				unset($dom);
				
				fputs($fp, "\n<results>\n");
			}
			
			// Content
			
			foreach($dicts as $dict) {
				$xml_result = simplexml_load_string("<result/>"); /* @var $xml SimpleXMLElement */
				
				if(is_array($cursor['tokens']))
					foreach($cursor['tokens'] as $token) {
						$value = $dict->$token;
						
						if($global_types[$token] == Model_CustomField::TYPE_DATE && $cursor['format_timestamps']) {
							if(empty($value)) {
								$value = '';
							} else if(is_numeric($value)) {
								$value = date('r', $value);
							}
						}
						
						if(is_array($value))
							$value = json_encode($value);
						
						if(!is_string($value) && !is_numeric($value))
							$value = '';
						
						$field = $xml_result->addChild("field", DevblocksPlatform::strEscapeHtml($value));
						$field->addAttribute("key", $token);
					}
				
				$dom = dom_import_simplexml($xml_result);
				fputs($fp, $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement));
			}			
		}			
		
		$cursor['page']++;
		$cursor['rows_exported'] += $count;
		
		// If our page isn't full, we're done
		if($count < $view->renderLimit) {
			$cursor['completed'] = true;
			fputs($fp, "</results>\n");
			fputs($fp, "</export>\n");
		}
		
		fclose($fp);
	}
	
	private function _internalAction_saveCustomize() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'string');
		$columns = DevblocksPlatform::importGPC($_POST['columns'] ?? null, 'array', []);
		$num_rows = DevblocksPlatform::importGPC($_POST['num_rows'] ?? null, 'integer',10);
		$options = DevblocksPlatform::importGPC($_POST['view_options'] ?? null, 'array', []);
		$field_deletes = DevblocksPlatform::importGPC($_POST['field_deletes'] ?? null, 'array',[]);
		
		// Sanitize
		$num_rows = DevblocksPlatform::intClamp($num_rows, 1, 500);
		
		// [Security] Filter custom fields
		$custom_fields = DAO_CustomField::getAll();
		foreach($columns as $idx => $column) {
			if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
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
		
		if(!($view = C4_AbstractViewLoader::getView($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// [TODO] This saves $options even when they're hidden (e.g. header color)
		$view->doCustomize($columns, $num_rows, $options);
		
		$is_custom = $view->isCustom();
		$is_trigger = DevblocksPlatform::strStartsWith($id, '_trigger_');
		
		if($is_custom || $is_trigger) {
			$title = DevblocksPlatform::importGPC($_POST['title'] ?? null, 'string', $translate->_('views.new_list'));
			$view->name = $title;
		}
		
		if($is_custom) {
			$params_required_query = DevblocksPlatform::importGPC($_POST['params_required_query'] ?? null, 'string', '');
			$view->setParamsRequiredQuery($params_required_query);
		}
		
		// Reset the paging
		$view->renderPage = 0;
		
		// Handle worklists specially
		if($is_custom) {
			// Check the custom workspace
			try {
				$worklist_id = intval(substr($id,5));
				
				if(empty($worklist_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($list_model = DAO_WorkspaceList::get($worklist_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace_tab = DAO_WorkspaceTab::get($list_model->workspace_tab_id)))
					throw new Exception("Can't load workspace tab.");
				
				if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_tab->workspace_page_id)))
					throw new Exception("Can't load workspace page.");
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker)) {
					throw new Exception("Permission denied to edit workspace.");
				}
				
				// Nuke legacy required criteria on custom views
				if(is_array($field_deletes) && !empty($field_deletes)) {
					foreach($field_deletes as $field_delete) {
						unset($list_model->params_required[$field_delete]);
					}
				}
				
			} catch(Exception $e) {
				return;
			}
			
			// Don't auto-persist this worklist
			$view->setAutoPersist(false);
			$view->persist();
			
			// Persist
			
			$fields = [
				DAO_WorkspaceList::NAME => $title,
				DAO_WorkspaceList::OPTIONS_JSON => json_encode($options),
				DAO_WorkspaceList::COLUMNS_JSON => json_encode($view->view_columns),
				DAO_WorkspaceList::RENDER_LIMIT => $view->renderLimit,
				DAO_WorkspaceList::PARAMS_EDITABLE_JSON => json_encode([]),
				DAO_WorkspaceList::PARAMS_REQUIRED_JSON => json_encode($list_model->params_required),
				DAO_WorkspaceList::PARAMS_REQUIRED_QUERY => $params_required_query,
				DAO_WorkspaceList::RENDER_SORT_JSON => json_encode($view->getSorts()),
				DAO_WorkspaceList::RENDER_SUBTOTALS => $view->renderSubtotals,
			];
			
			DAO_WorkspaceList::update($worklist_id, $fields);
			
			DAO_WorkspaceList::onUpdateByActor($active_worker, $fields, $worklist_id);
		}
	}
	
	private function _internalAction_showQuickSearchPopup() {
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null,'string','');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/quick_search_popup.tpl');
	}
	
	private function _internalAction_subtotal() {
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null,'string','');
		$toggle = DevblocksPlatform::importGPC($_REQUEST['toggle'] ?? null,'integer',0);
		$category = DevblocksPlatform::importGPC($_REQUEST['category'] ?? null,'string','');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		// Check the interface
		if(!$view instanceof IAbstractView_Subtotals)
			return;
		
		if(!$toggle && !$category)
			$category = $view->renderSubtotals;
		
		$tpl = DevblocksPlatform::services()->template();
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
			} elseif(DevblocksPlatform::strStartsWith($view->renderSubtotals,'__')) {
				$key = ltrim($view->renderSubtotals,'_');
				// Make sure the desired key still exists
				$view->renderSubtotals = isset($fields[$key]) ? $key : key($fields);
				
			} else { // shown->hidden
				$view->renderSubtotals = '__' . $view->renderSubtotals;
				
			}
			
		} else {
			$view->renderSubtotals = $category;
			
		}
		
		// If hidden, no need to draw template
		if(empty($view->renderSubtotals) || DevblocksPlatform::strStartsWith($view->renderSubtotals,'__'))
			return;
		
		$view->renderSubtotals();
	}
	
	private function _internalAction_serializeView() {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			echo json_encode(array(
				'view_name' => $view->name,
				'worklist_model' => C4_AbstractViewLoader::serializeViewToAbstractJson($view, $context),
			));
		}
		
		exit;
	}
	
	private function _internalAction_renderImportPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$layer = DevblocksPlatform::importGPC($_REQUEST['layer'] ?? null,'string');
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null,'string','');
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null,'string','');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($context_ext instanceof IDevblocksContextImport))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.create', $context_ext->id)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.import', $context_ext->id)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Template
		
		$tpl->assign('layer', $layer);
		$tpl->assign('context', $context_ext->id);
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/import/popup_upload.tpl');
	}
	
	private function _internalAction_parseImportFile() {
		$file = $_FILES['import_file'] ?? null;
		
		if(!is_array($file) || empty($file['tmp_name'] ?? null))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$filename = basename($file['tmp_name']);
		$new_filename = APP_TEMP_PATH . '/' . $filename;
		
		if(!rename($file['tmp_name'], $new_filename))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$expires_at = $inputs['expires'] ?? (time() + 86400); // 1d TTL
		$resource_token = DevblocksPlatform::services()->string()->uuid();
		
		if(!($fp = fopen($new_filename, 'r')))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		$mime_type = 'text/csv';
		
		// Peek at the first byte to check the format
		if(fread($fp, 1) == '{') $mime_type = 'text/jsonl';
		fseek($fp, 0); // rewind
		
		$resource_id = \DAO_AutomationResource::create([
			\DAO_AutomationResource::NAME => $file['name'],
			\DAO_AutomationResource::MIME_TYPE => $mime_type,
			\DAO_AutomationResource::TOKEN => $resource_token,
			\DAO_AutomationResource::EXPIRES_AT => $expires_at,
		]);
		
		if(is_resource($fp)) {
			\Storage_AutomationResource::put($resource_id, $fp);
			fclose($fp);
		}
		
		echo json_encode([
			'import_token' => $resource_token,
			'mime_type' => $mime_type
		]);
		
		DevblocksPlatform::exit();
	}
	
	private function _internalAction_renderImportMappingPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$layer = DevblocksPlatform::importGPC($_REQUEST['layer'] ?? null,'string');
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null,'string','');
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null,'string','');
		$import_token = DevblocksPlatform::importGPC($_REQUEST['import_token'] ?? null,'string','');
		
		if(!($context_ext = Extension_DevblocksContext::get($context)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($context_ext instanceof IDevblocksContextImport))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.create', $context_ext->id)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.import', $context_ext->id)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Keys
		$keys = $context_ext->importGetKeys();
		$this->_filterImportCustomFields($keys);
		$tpl->assign('keys', $keys);
		
		// Read the first line from the file
		if(!($automation_resource = DAO_AutomationResource::getByToken($import_token)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		$fp = DevblocksPlatform::getTempFile();
		
		if(!($automation_resource->getFileContents($fp)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		// Is this CSV or JSON?
		if($automation_resource->mime_type == 'text/jsonl') {
			$line = fgets($fp);
			
			// The line must be a JSON-encoded object with keys
			if(false === ($json = json_decode($line, true)) || !is_array($json))
				DevblocksPlatform::dieWithHttpError(null, 500);
			
			$columns = array_keys($json);
		
		} else {
			// In a CSV file, the first line is assumed to be the header names
			if(false === ($columns = fgetcsv($fp)))
				DevblocksPlatform::dieWithHttpError(null, 500);
		}
		
		fclose($fp);
		
		$tpl->assign('columns', $columns);
		
		// Template
		
		$tpl->assign('layer', $layer);
		$tpl->assign('context', $context_ext->id);
		$tpl->assign('view_id', $view_id);
		$tpl->assign('import_token', $import_token);
		
		$tpl->display('devblocks:cerberusweb.core::internal/import/popup_mapping.tpl');
	}
	
	private function _filterImportCustomFields(&$keys) {
		if(!CerberusApplication::getActiveWorker())
			return;
		
		$custom_fields = DAO_CustomField::getAll();
		$custom_fieldsets = DAO_CustomFieldset::getAll();
		
		if(is_array($keys))
			foreach(array_keys($keys) as $key) {
				if(!DevblocksPlatform::strStartsWith($key, 'cf_'))
					continue;
				
				$cfield_id = substr($key, 3);
				
				if(!isset($custom_fields[$cfield_id])) {
					unset($keys[$key]);
					continue;
				}
				
				$cfield = $custom_fields[$cfield_id];
				
				if(!$cfield->custom_fieldset_id)
					continue;
				
				if(!($cfieldset = ($custom_fieldsets[$cfield->custom_fieldset_id] ?? null))) {
					unset($keys[$key]);
					continue;
				}
				
				if($cfieldset->owner_context == CerberusContexts::CONTEXT_BOT) {
					unset($keys[$key]);
					continue;
				}
			}
	}
	
	private function _internalAction_importPreview() {
		$tpl = DevblocksPlatform::services()->template();
		
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string','');
		$import_token = DevblocksPlatform::importGPC($_POST['import_token'] ?? null, 'string','');
		
		$field = DevblocksPlatform::importGPC($_POST['field'] ?? null, 'array', []);
		$column = DevblocksPlatform::importGPC($_POST['column'] ?? null, 'array', []);
		$column_custom = DevblocksPlatform::importGPC($_POST['column_custom'] ?? null, 'array', []);
		$sync_dupes = DevblocksPlatform::importGPC($_POST['sync_dupes'] ?? null, 'array', []);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if (!($automation_resource = DAO_AutomationResource::getByToken($import_token)))
				throw new Exception_DevblocksValidationError("The import file does not exist.");
			
			$importer = new FileImporter($automation_resource, $context);
			
			$error = null;
			$mapping = new FileImporter\Mapping($field, $column, $column_custom, $sync_dupes);
			
			if(!$importer->validate($mapping, $error))
				throw new Exception_DevblocksValidationError($error);
			
			$line_offsets = $importer->getFileRecordOffsets(limit: 10, skip_csv_headings: true);
			
			$preview = array_map(
				fn($offset) => $importer->getFileRecordByOffset($offset[1], $offset[2], $mapping),
				$line_offsets
			);
			
			$preview = $importer->bulkFormatRecordFields($preview, $mapping);
			
			// Strip the raw line data
			$preview = array_map(fn($record) => array_diff_key($record, ['line'=>true]), $preview);
			
			$preview = $importer->bulkTagUpserts($preview, $mapping);
			
			$tpl->assign('record_ext', $importer->getRecordExtension());
			$tpl->assign('keys', $importer->getRecordKeys());
			$tpl->assign('preview', $preview);
			
			$preview_html = $tpl->fetch('devblocks:cerberusweb.core::internal/import/preview.tpl');
			
			echo json_encode([
				'preview_output' => $preview_html,
			]);
			
		} catch (Exception_DevblocksValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
	
	private function _internalAction_saveImport() {
		$queue_service = DevblocksPlatform::services()->queue();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string','');
		$import_token = DevblocksPlatform::importGPC($_POST['import_token'] ?? null, 'string','');
		
		$field = DevblocksPlatform::importGPC($_POST['field'] ?? null, 'array', []);
		$column = DevblocksPlatform::importGPC($_POST['column'] ?? null, 'array', []);
		$column_custom = DevblocksPlatform::importGPC($_POST['column_custom'] ?? null, 'array', []);
		$sync_dupes = DevblocksPlatform::importGPC($_POST['sync_dupes'] ?? null, 'array', []);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!($automation_resource = DAO_AutomationResource::getByToken($import_token)))
				throw new Exception_DevblocksValidationError("The import file does not exist.");
			
			$importer = new FileImporter($automation_resource, $context);
			
			$context_ext = $importer->getRecordExtension();
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.create', $context_ext->id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.import', $context_ext->id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$error = null;
			$mapping = new FileImporter\Mapping($field, $column, $column_custom, $sync_dupes);
			
			if(!$importer->validate($mapping, $error))
				throw new Exception_DevblocksValidationError($error);
			
			$queue = DAO_Queue::getByName('cerb.records.import');
			$aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest);
			
			$line_offsets = $importer->getFileRecordOffsets(skip_csv_headings: true);
			
			$record_count = count($line_offsets);
			
			$queue_job = new Model_QueueJob();
			$queue_job->queue_id = $queue->id;
			$queue_job->name = sprintf('Import %s: %s', $aliases['plural'] ?? $aliases['uri'] ?? $context_ext->id, $automation_resource->name);
			$queue_job->status_id = QueueJobStatus::RUNNING->value;
			$queue_job->count_total = $record_count;
			$queue_job->count_available = $record_count;
			$queue_job->worker_id = $active_worker->id ?? 0;
			$queue_job->metadata = [
				'context' => $context_ext->id,
				'import_token' => $import_token,
				'format' => $importer->getImportFormat(),
				'mapping' => [
					'field' => $mapping->getFields(),
					'column' => $mapping->getColumns(),
					'column_custom' => $mapping->getCustomColumns(),
					'sync_dupes' => $mapping->getSyncColumns(),
				]
			];
			$queue_job = DAO_QueueJob::create($queue_job);
			
			// Create 500 queue messages at once
			foreach(array_chunk($line_offsets, 500) as $chunk) {
				$queue_service->enqueue($queue->name, $chunk, job_id: $queue_job->id);
			}
			
			$results = [
				'status' => true,
				'job_id' => $queue_job->id ?? 0,
			];
			
			echo json_encode($results);
			
		} catch (Exception_DevblocksValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
}