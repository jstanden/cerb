<?php /** @noinspection PhpUnused */

/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
				
			} catch(Exception $e) {
				// [TODO] Logger
				return;
			}
		}
		
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view.tpl');
	}
	
	private function _internalAction_renderCopy() {
		$tpl = DevblocksPlatform::services()->template();
		
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null, 'string');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		
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
		
		header('Content-Type: application/json; charset=utf-8');
		
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
		
		if(false == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
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
		
		$pref_key = sprintf("worklist.%s.export_tokens",
			$context_ext->manifest->getParam('uri', $context_ext->id)
		);
		
		if(null == ($tokens = DAO_WorkerPref::getAsJson($active_worker->id, $pref_key))) {
			$tokens = $context_ext->getCardProperties();
			
			// Push _label into the front of $tokens if not set
			if(!in_array('_label', $tokens))
				array_unshift($tokens, '_label');
		}
		
		// Template
		
		$tpl->assign('tokens', $tokens);
		
		$export_kata = <<< EOD
		# Enter worklist export KATA (use Ctrl+Space for autocompletion)
		column/id:
		
		column/_label:
		  label: Label
		  value@raw: {{_label|trim}}
		EOD;
		
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
		
		header('Content-Type: application/json; charset=utf-8');
		
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
				if(!$export_mode) {
					$pref_key = sprintf("worklist.%s.export_tokens",
						$context_ext->manifest->getParam('uri', $context_ext->id)
					);
					
					DAO_WorkerPref::setAsJson($active_worker->id, $pref_key, $tokens);
					
				} else if('kata' == $export_mode) {
					$kata = DevblocksPlatform::services()->kata();
					
					if(!$kata->validate($export_kata, CerberusApplication::kataSchemas()->worklistExport(), $error))
						throw new Exception_DevblocksAjaxError("Export KATA Error: " . $error);
				}
				
				if(!isset($_SESSION['view_export_cursors']))
					$_SESSION['view_export_cursors']  = [];
				
				$cursor_key = sha1(serialize([$view_id, $tokens, $export_as, time()]));
				
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
			$fields = array(
				DAO_Attachment::NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $mime_type,
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
				DAO_Attachment::UPDATED => time(),
			);
			
			if(false == ($id = DAO_Attachment::create($fields)))
				return false;
			
			// [TODO] This is a temporary workaround to allow workers to view exports they create
			$_SESSION['view_export_file_id'] = $id;
			
			$fp = fopen($cursor['temp_file'], 'r');
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
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return false;
		
		$view->setAutoPersist(false);
		
		// Override display
		$view->view_columns = [];
		$view->renderPage = $cursor['page'];
		$view->renderLimit = 200;
		
		return $view;
	}
	
	private function _getDictionariesFromView(C4_AbstractView $view, Extension_DevblocksContext $context_ext, array $cursor, &$count=0) {
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
		
		// Bulk lazy load the tokens across all the dictionaries with a temporary cache
		foreach($cursor['tokens'] as $token) {
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
		}
		
		foreach($dicts as $dict)
			$dict->scrubKeys('_types');

		return $dicts;
	}
	
	private function _getExportColumnsKataFromCursor(array $cursor) {
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
			
			$export_columns[$column_name] = [
				'label' => $column_data['label'] ?? DevblocksPlatform::strTitleCase($column_name),
				'value' => $column_data['value'] ?? sprintf('{{%s}}', $column_name),
			];
		}
		
		return $export_columns;
	}
	
	private function _viewIncrementExportAsCsv(array &$cursor) {
		if(false == ($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		$global_labels = $global_values = [];
		CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
		$global_types = $global_values['_types'];
		
		// Append mode to the temp file
		$fp = fopen($cursor['temp_file'], "a");
		
		$count = 0;
		
		if(false == ($dicts = $this->_getDictionariesFromView($view, $context_ext, $cursor, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
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
					if($column_name) {
						$value = $tpl_builder->build($column['value'] ?? '', $dict);
						$fields[] = $value;
						
					} else {
						$fields[] = '';
					}
				}
				
				fputcsv($fp, $fields);
			}
			
		} else {
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
		if(false == ($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		$global_labels = $global_values = [];
		CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
		$global_types = $global_values['_types'];
		
		// Append mode to the temp file
		$fp = fopen($cursor['temp_file'], "a");
		
		$count = 0;
		
		if(false == ($dicts = $this->_getDictionariesFromView($view, $context_ext, $cursor, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$export_columns = $this->_getExportColumnsKataFromCursor($cursor);
			
			fputs($fp, "{\"results\": [\n");
			
			$objects = [];
				
			foreach($dicts as $dict) {
				$object = [];
				
				foreach($export_columns as $column_name => $column) {
					if($column_name) {
						$value = $tpl_builder->build($column['value'] ?? '', $dict);
					} else {
						$value = '';
					}
					
					$object[$column_name] = $value;
				}
			
				$objects[] = $object;
			}
			
			$json = trim(json_encode($objects),'[]');
			fputs($fp, $json);
			
		} else {
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
		if(false == ($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		$global_labels = $global_values = [];
		CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
		$global_types = $global_values['_types'];
		
		// Append mode to the temp file
		$fp = fopen($cursor['temp_file'], "a");
		
		$count = 0;
		
		if(false == ($dicts = $this->_getDictionariesFromView($view, $context_ext, $cursor, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$export_columns = $this->_getExportColumnsKataFromCursor($cursor);
			
			foreach($dicts as $dict) {
				$object = [];
				
				foreach($export_columns as $column_name => $column) {
					if($column_name) {
						$value = $tpl_builder->build($column['value'] ?? '', $dict);
					} else {
						$value = '';
					}
					
					$object[$column_name] = $value;
				}
				
				$json = json_encode($object);
				fputs($fp, $json . "\n");
			}
			
		} else {
			// Rows
			
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
		if(false == ($view = $this->_getViewFromCursor($cursor)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		// Append mode to the temp file
		$fp = fopen($cursor['temp_file'], "a");
		
		$count = 0;
		
		if(false == ($dicts = $this->_getDictionariesFromView($view, $context_ext, $cursor, $count)))
			$dicts = [];
		
		if('kata' == $cursor['export_mode']) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$export_columns = $this->_getExportColumnsKataFromCursor($cursor);
			
			if(0 == $cursor['page']) {
				fputs($fp, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
				fputs($fp, "<export>\n");
				fputs($fp, "<results>\n");
			}			
			
			foreach($dicts as $dict) {
				$xml_result = simplexml_load_string("<result/>"); /* @var $xml SimpleXMLElement */
				
				foreach($export_columns as $column_name => $column) {
					if($column_name) {
						$value = $tpl_builder->build($column['value'] ?? '', $dict);
					} else {
						$value = '';
					}
					
					$field = $xml_result->addChild("field", DevblocksPlatform::strEscapeHtml($value));
					$field->addAttribute("key", $column_name);
				}
				
				$dom = dom_import_simplexml($xml_result);
				fputs($fp, $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement));
			}
			
		} else {
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
		
		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
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
		
		// If hidden, no need to draw template
		if(empty($view->renderSubtotals) || '__'==substr($view->renderSubtotals,0,2))
			return;
		
		$view->renderSubtotals();
	}
	
	private function _internalAction_serializeView() {
		header("Content-type: application/json");
		
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
		$csv_file = $_FILES['csv_file'] ?? null;
		
		if(!is_array($csv_file) || !isset($csv_file['tmp_name']) || empty($csv_file['tmp_name']))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$filename = basename($csv_file['tmp_name']);
		$new_filename = APP_TEMP_PATH . '/' . $filename;
		
		if(!rename($csv_file['tmp_name'], $new_filename))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit = CerberusApplication::getVisit();
		$visit->set('import.last.csv', $new_filename);
		
		DevblocksPlatform::exit();
	}
	
	private function _internalAction_renderImportMappingPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
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
		
		// Keys
		$keys = $context_ext->importGetKeys();
		$this->_filterImportCustomFields($keys);
		$tpl->assign('keys', $keys);
		
		// Read the first line from the file
		
		if(!($csv_file = $visit->get('import.last.csv', '')))
			DevblocksPlatform::dieWithHttpError(null, 500);
			
		if(!($fp = fopen($csv_file, 'rt')))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		$columns = fgetcsv($fp);
		fclose($fp);
		
		$tpl->assign('columns', $columns);
		
		// Template
		
		$tpl->assign('layer', $layer);
		$tpl->assign('context', $context_ext->id);
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/import/popup_mapping.tpl');
	}
	
	private function _filterImportCustomFields(&$keys) {
		if(false == (CerberusApplication::getActiveWorker()))
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
				
				if(false == ($cfieldset = @$custom_fieldsets[$cfield->custom_fieldset_id])) {
					unset($keys[$key]);
					continue;
				}
				
				if($cfieldset->owner_context == CerberusContexts::CONTEXT_BOT) {
					unset($keys[$key]);
					continue;
				}
			}
	}
	
	private function _internalAction_saveImport() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string','');
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string','');
		$is_preview = DevblocksPlatform::importGPC($_POST['is_preview'] ?? null, 'integer',0);
		
		$field = DevblocksPlatform::importGPC($_POST['field'] ?? null, 'array', []);
		$column = DevblocksPlatform::importGPC($_POST['column'] ?? null, 'array', []);
		$column_custom = DevblocksPlatform::importGPC($_POST['column_custom'] ?? null, 'array', []);
		$sync_dupes = DevblocksPlatform::importGPC($_POST['sync_dupes'] ?? null, 'array', []);
		
		$visit = CerberusApplication::getVisit();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$preview_output = '';
		
		try {
			if(null == ($context_ext = Extension_DevblocksContext::get($context)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!($context_ext instanceof IDevblocksContextImport))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.create', $context_ext->id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.import', $context_ext->id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$view_class = $context_ext->getViewClass();
			$view = new $view_class; /* @var $view C4_AbstractView */
			
			$keys = $context_ext->importGetKeys();
			
			// Ensure we have some mapping
			if(0 == count(array_filter($column, fn($v) => strlen(strval($v)))))
				throw new Exception_DevblocksValidationError("No fields are mapped from the import file.");
			
			// If our required fields weren't provided
			foreach($field as $field_idx => $field_key) {
				if(array_key_exists($field_key, $keys) && ($keys[$field_key]['required'] ?? false)) {
					if(!strlen(strval($column[$field_idx] ?? ''))) {
						$error = sprintf("`%s` is required.",
							DevblocksPlatform::strEscapeHtml($keys[$field_key]['label'] ?? $field_key)
						);
						throw new Exception_DevblocksValidationError($error);
					}
				}
			}
			
			// Use the context to validate sync options, if available
			if(method_exists($context_ext, 'importValidateSync')) {
				if(true !== ($error = $context_ext->importValidateSync($sync_dupes)))
					throw new Exception_DevblocksValidationError($error);
			}
			
			// Counters
			$line_number = 0;
			
			// CSV
			if(!($csv_file = $visit->get('import.last.csv', '')))
				DevblocksPlatform::dieWithHttpError(null, 500);
			
			if(!($fp = fopen($csv_file, 'rt')))
				DevblocksPlatform::dieWithHttpError(null, 500);
			
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
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$dict = new DevblocksDictionaryDelegate([]);
				
				foreach($parts as $idx => $part) {
					$col = 'column_' . ($idx + 1); // 0-based to 1-based
					$dict->$col = $part;
				}
				
				// Meta
				$meta = [
					'line' => $parts,
					'fields' => $field,
					'columns' => $column,
					'virtual_fields' => [],
				];
				
				$fields = [];
				$custom_fields = [];
				$sync_fields = [];
				
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
							if($is_preview) {
								$value = $val;
							} elseif(null != ($addy = DAO_Address::lookupAddress($val, true))) {
								$value = $addy->id;
							}
							break;
						
						case 'ctx_' . CerberusContexts::CONTEXT_ORG:
							if($is_preview) {
								$value = $val;
							} elseif(null != ($org_id = DAO_ContactOrg::lookup($val, true))) {
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
						
						case Model_CustomField::TYPE_LIST:
							$value = $val;
							break;
						
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$value = DevblocksPlatform::parseCsvString(str_replace(
								'\"',
								'',
								$val
							));
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
						$preview_output .= sprintf("%s =&gt; %s<br>",
							DevblocksPlatform::strEscapeHtml($keys[$key]['label']),
							DevblocksPlatform::strEscapeHtml(is_array($value) ? sprintf('[%s]', implode(', ', $value)) : $value)
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
					$preview_output .= "<hr>";
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
				@unlink($csv_file); // nuke the imported file
				$visit->set('import.last.csv',null);
				
				if(!empty($view_id) && !empty($context)) {
					C4_AbstractView::setMarqueeContextImported($view_id, $context, $line_number);
				}
			}
			
			$results = [
				'status' => true,
			];
			
			if($is_preview)
				$results['preview_output'] = $preview_output;
			
			echo json_encode($results);
			
		} catch (Exception_DevblocksValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
}