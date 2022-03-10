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

class PageSection_InternalRecords extends Extension_PageSection {
	function render() {}
	
	public function handleActionForPage(string $action, string $scope=null) {
		if('internalAction' == $scope) {
			switch ($action) {
				case 'autocomplete':
					return $this->_internalAction_autocomplete();
				case 'chooserOpen':
					return $this->_internalAction_chooserOpen();
				case 'chooserOpenAvatar':
					return $this->_internalAction_chooserOpenAvatar();
				case 'chooserOpenFile':
					return $this->_internalAction_chooserOpenFile();
				case 'chooserOpenFileAjaxUpload':
					return $this->_internalAction_chooserOpenFileAjaxUpload();
				case 'chooserOpenFileLoadBundle':
					return $this->_internalAction_chooserOpenFileLoadBundle();
				case 'chooserOpenParams':
					return $this->_internalAction_chooserOpenParams();
				case 'contextAddLinksJson':
					return $this->_internalAction_contextAddLinksJson();
				case 'contextDeleteLinksJson':
					return $this->_internalAction_contextDeleteLinksJson();
				case 'editorOpenTemplate':
					return $this->_internalAction_editorOpenTemplate();
				case 'getCustomFieldSet':
					return $this->_internalAction_getCustomFieldSet();
				case 'getLinkCountsJson':
					return $this->_internalAction_getLinkCountsJson();
				case 'linksOpen':
					return $this->_internalAction_linksOpen();
				case 'renderMergePopup':
					return $this->_internalAction_renderMergePopup();
				case 'renderMergeMappingPopup':
					return $this->_internalAction_renderMergeMappingPopup();
				case 'saveMerge':
					return $this->_internalAction_saveMerge();
				case 'showPeekPopup':
					return $this->_internalAction_showPeekPopup();
				case 'showPermalinkPopup':
					return $this->_internalAction_showPermalinkPopup();
				case 'viewLogDelete':
					return $this->_internalAction_viewLogDelete();
			}
		}
		return false;
	}
	
	private function _internalAction_showPeekPopup() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'string',null);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$edit = DevblocksPlatform::importGPC($_REQUEST['edit'], 'string', null);
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($context_ext instanceof IDevblocksContextPeek))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$context_ext->renderPeekPopup($context_id, $view_id, $edit);
	}
	
	private function _internalAction_showPermalinkPopup() {
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string','');
		
		if(empty($url))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('url', $url);
		$tpl->display('devblocks:cerberusweb.core::internal/peek/popup_peek_permalink.tpl');
	}
	
	private function _internalAction_linksOpen() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'],'integer');
		@$to_context = DevblocksPlatform::importGPC($_POST['to_context'],'string');
		
		if(null == ($to_context_extension = Extension_DevblocksContext::get($to_context))
			|| null == ($from_context_extension = Extension_DevblocksContext::get($context)))
			return;
		
		$view_id = 'links_' . DevblocksPlatform::strAlphaNum($to_context_extension->id, '_', '_');
		
		if(false != ($view = $to_context_extension->getView($context, $context_id, null, $view_id))) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('from_context_extension', $from_context_extension);
			$tpl->assign('from_context_id', $context_id);
			$tpl->assign('to_context_extension', $to_context_extension);
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/profiles/profile_links_popup.tpl');
		}
	}
	
	private function _internalAction_getLinkCountsJson() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'],'integer');
		
		$contexts = Extension_DevblocksContext::getAll(false);
		
		$counts = DAO_ContextLink::getContextLinkCounts($context, $context_id, []);
		$results = [];
		
		foreach($counts as $ext_id => $count) {
			if(false == (@$context = $contexts[$ext_id]))
				continue;
			
			$aliases = Extension_DevblocksContext::getAliasesForContext($context);
			
			$results[] = [
				'context' => $ext_id,
				'label' => DevblocksPlatform::strTitleCase($aliases['plural']) ?? $context->name,
				'count' => $count,
			];
		}
		
		DevblocksPlatform::sortObjects($results, '[label]');
		$results = array_values($results);
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($results);
	}
	
	private function _internalAction_chooserOpen() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string', '');
		@$single = DevblocksPlatform::importGPC($_REQUEST['single'],'integer',0);
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'],'string', '');
		@$query_req = DevblocksPlatform::importGPC($_REQUEST['qr'],'string', '');
		@$worklist = DevblocksPlatform::importGPC($_REQUEST['worklist'], 'array', []);
		
		if(null == ($context_extension = Extension_DevblocksContext::getByAlias($context, true)))
			return;
		
		if(false == ($view = $context_extension->getChooserView()))
			return;
		
		if(array_key_exists('columns', $worklist)) {
			$view->view_columns = DevblocksPlatform::parseCsvString($worklist['columns']);
		}
		
		// Required params
		if(!empty($query_req)) {
			if(false != ($params_req = $view->getParamsFromQuickSearch($query_req)))
				$view->addParamsRequired($params_req);
		}
		
		// Query
		if(!empty($query)) {
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context', $context_extension);
		$tpl->assign('layer', $layer);
		$tpl->assign('view', $view);
		$tpl->assign('single', $single);
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__generic.tpl');
	}
	
	private function _internalAction_chooserOpenParams() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$q = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
		
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
		
		if(!empty($q)) {
			$view->addParamsWithQuickSearch($q, true);
			$view->setParamsQuery($q);
			$view->renderPage = 0;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context', $context);
		$tpl->assign('layer', $layer);
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/choosers/__worklist.tpl');
	}
	
	private function _internalAction_getCustomFieldSet() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$bulk = DevblocksPlatform::importGPC($_REQUEST['bulk'], 'integer', 0);
		@$field_wrapper = DevblocksPlatform::importGPC($_REQUEST['field_wrapper'], 'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('bulk', !empty($bulk) ? true : false);
		
		if(empty($id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!empty($field_wrapper))
			$tpl->assign('field_wrapper', $field_wrapper);
		
		if(null == ($custom_fieldset = DAO_CustomFieldset::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_CustomFieldset::isReadableByActor($custom_fieldset, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('custom_fieldset', $custom_fieldset);
		$tpl->assign('custom_fieldset_is_new', true);
		
		// If we're drawing the fieldset for a VA action, include behavior and event meta
		if($trigger_id && false !== ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			$event = $trigger->getEvent();
			$values_to_contexts = $event->getValuesContexts($trigger);
			
			$tpl->assign('trigger', $trigger);
			$tpl->assign('values_to_contexts', $values_to_contexts);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fieldsets/fieldset.tpl');
	}
	
	private function _internalAction_autocomplete() {
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		@$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		
		header('Content-Type: application/json');
		
		$list = [];
		
		// [TODO] Abstractly handle '(no record)' blank functionality?
		
		if(false != ($context_ext = Extension_DevblocksContext::get($context))) {
			if($context_ext instanceof IDevblocksContextAutocomplete)
				$list = $context_ext->autocomplete($term, $query);
		}
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		
		DevblocksPlatform::exit();
	}
	
	private function _internalAction_editorOpenTemplate() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$label_prefix = DevblocksPlatform::importGPC($_REQUEST['label_prefix'],'string', '');
		@$key_prefix = DevblocksPlatform::importGPC($_REQUEST['key_prefix'],'string', '');
		@$template = DevblocksPlatform::importGPC($_REQUEST['template'],'string');
		@$placeholders = DevblocksPlatform::importGPC($_REQUEST['placeholders'],'array',[]);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('template', $template);
		
		$tpl->assign('key_prefix', $key_prefix);
		
		$labels = $placeholders;
		$values = $merge_labels = $merge_values = [];
		
		if($context && false != ($context_ext = Extension_DevblocksContext::get($context))) {
			$tpl->assign('context_ext', $context_ext);
			
			if(empty($label_prefix))
				$label_prefix =  $context_ext->manifest->name . ' ';
			
			// Load the context dictionary for scope
			CerberusContexts::getContext($context_ext->id, null, $merge_labels, $merge_values, '', true, false);
			
			CerberusContexts::merge(
				$key_prefix,
				$label_prefix,
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		}
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/editors/__template.tpl');
	}
	
	private function _internalAction_chooserOpenFile() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string');
		@$single = DevblocksPlatform::importGPC($_REQUEST['single'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('layer', $layer);
		
		// Single chooser mode?
		$tpl->assign('single', $single);
		
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__file.tpl');
	}
	
	private function _internalAction_chooserOpenFileAjaxUpload() {
		$file_name = rawurldecode($_SERVER['HTTP_X_FILE_NAME'] ?? null);
		$file_type = $_SERVER['HTTP_X_FILE_TYPE'] ?? null;
		$file_size = $_SERVER['HTTP_X_FILE_SIZE'] ?? null;
		
		$url_writer = DevblocksPlatform::services()->url();
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		// [TODO] Privs!
		// [TODO] Exceptions return JSON
		if(empty($file_name) || empty($file_size)) {
			return;
		}
		
		if(empty($file_type))
			$file_type = 'application/octet-stream';
		
		// Copy the HTTP body into a temp file
		
		$fp = DevblocksPlatform::getTempFile();
		$temp_name = DevblocksPlatform::getTempFileInfo($fp);
		
		$body_data = fopen("php://input" , "rb");
		while(!feof($body_data))
			fwrite($fp, fread($body_data, 8192));
		fclose($body_data);
		
		// Reset the temp file pointer
		fseek($fp, 0);
		
		// SHA-1 the temp file
		$sha1_hash = sha1_file($temp_name) ?? null;
		
		if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $file_size, $file_type, $file_name))) {
			// Create a record w/ timestamp + ID
			$fields = [
				DAO_Attachment::NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $file_type,
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
			];
			$file_id = DAO_Attachment::create($fields);
			
			// Save the file
			Storage_Attachments::put($file_id, $fp);
			
		} else {
			if(false != ($file = DAO_Attachment::get($file_id))) {
				$file_name = $file->name; 
				$file_type = $file->mime_type; 
				$file_size = $file->storage_size; 
			}
		}
		
		// A worker who uploaded this file will always have access to it, whether it was a dupe or not
		DAO_Attachment::addLinks(CerberusContexts::CONTEXT_WORKER, $active_worker->id, $file_id);
		
		// Close the temp file
		fclose($fp);
		
		if($file_id) {
			echo json_encode([
				'id' => intval($file_id),
				'name' => $file_name,
				'type' => $file_type,
				'size' => intval($file_size),
				'size_label' => DevblocksPlatform::strPrettyBytes($file_size),
				'sha1_hash' => $sha1_hash,
				'url' => $url_writer->write(sprintf("c=files&id=%d&name=%s", $file_id, urlencode($file_name)), true),
			]);
		}
	}
	
	private function _internalAction_chooserOpenFileLoadBundle() {
		$url_writer = DevblocksPlatform::services()->url();
		
		@$bundle_id = DevblocksPlatform::importGPC($_REQUEST['bundle_id'], 'integer', 0);
		
		header('Content-Type: application/json; charset=utf-8');
		
		$results = [];
		
		if(false == ($bundle = DAO_FileBundle::get($bundle_id))) {
			echo json_encode($results);
			return;
		}
		
		foreach($bundle->getAttachments() as $attachment) {
			$results[] = array(
				'id' => $attachment->id,
				'name' => $attachment->name,
				'type' => $attachment->mime_type,
				'size' => $attachment->storage_size,
				'size_label' => DevblocksPlatform::strPrettyBytes($attachment->storage_size),
				'sha1_hash' => $attachment->storage_sha1hash,
				'url' => $url_writer->write(sprintf("c=files&id=%d&name=%s", $attachment->id, urlencode($attachment->name)), true),
			);
		}
		
		echo json_encode($results);
	}
	
	private function _internalAction_chooserOpenAvatar() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$defaults_string = DevblocksPlatform::importGPC($_REQUEST['defaults'],'string','');
		@$image_width = DevblocksPlatform::importGPC($_REQUEST['image_width'],'integer',0);
		@$image_height = DevblocksPlatform::importGPC($_REQUEST['image_height'],'integer',0);
		
		if(empty($image_width))
			$image_width = 100;
		
		if(empty($image_height))
			$image_height = 100;
		
		$url_writer = DevblocksPlatform::services()->url();
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('image_width', $image_width);
		$tpl->assign('image_height', $image_height);
		
		if(false != ($avatar = DAO_ContextAvatar::getByContext($context, $context_id))) {
			$contents = 'data:' . $avatar->content_type . ';base64,' . base64_encode(Storage_ContextAvatar::get($avatar));
			$tpl->assign('imagedata', $contents);
		}
		
		$suggested_photos = [];
		
		// Suggest more extended content
		
		$defaults = [];
		
		$tokens = explode(' ', trim($defaults_string));
		foreach($tokens as $token) {
			@list($k,$v) = explode(':', $token);
			$defaults[trim($k)] = trim($v);
		}
		
		// Per context suggestions
		
		switch($context) {
			case CerberusContexts::CONTEXT_CONTACT:
				// Suggest from the address we're adding to the new contact
				if(empty($context_id) && isset($defaults['email'])) {
					$context_id = intval($defaults['email']);
				}
				
				// Suggest from all of the contact's alternate email addys
				if($context_id && false != ($contact = DAO_Contact::get($context_id))) {
					$addys = $contact->getEmails();
					
					if(is_array($addys))
						foreach($addys as $addy) {
							$suggested_photos[] = array(
								'url' => 'https://gravatar.com/avatar/' . md5($addy->email) . '?s=100&d=404',
								'title' => 'Gravatar: ' . $addy->email,
							);
						}
				}
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person1.png', true),
					'title' => 'Silhouette: Male #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person3.png', true),
					'title' => 'Silhouette: Male #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person4.png', true),
					'title' => 'Silhouette: Male #3',
				);
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person2.png', true),
					'title' => 'Silhouette: Female #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person5.png', true),
					'title' => 'Silhouette: Female #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person6.png', true),
					'title' => 'Silhouette: Female #3',
				);
				
				break;
			
			case CerberusContexts::CONTEXT_ORG:
				if(false != ($org = DAO_ContactOrg::get($context_id))) {
					// Suggest from all of the org's top email addys w/o contacts
					$addys = $org->getEmailsWithoutContacts(10);
					
					if(is_array($addys))
						foreach($addys as $addy) {
							$suggested_photos[] = array(
								'url' => 'https://gravatar.com/avatar/' . md5($addy->email) . '?s=100&d=404',
								'title' => 'Gravatar: ' . $addy->email,
							);
						}
				}
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/building1.png', true),
					'title' => 'Building #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/building2.png', true),
					'title' => 'Building #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/building3.png', true),
					'title' => 'Building #3',
				);
				break;
			
			case CerberusContexts::CONTEXT_WORKER:
				// Suggest from the address we're adding to the new worker
				if(empty($context_id)) {
					if(isset($defaults['email']) && false != ($addy = DAO_Address::get($defaults['email']))) {
						$suggested_photos[] = array(
							'url' => 'https://gravatar.com/avatar/' . md5($addy->email) . '?s=100&d=404',
							'title' => 'Gravatar: ' . $addy->email,
						);
					}
					
				} else if($context_id && false != ($worker = DAO_Worker::get($context_id))) {
					if(false != ($email = $worker->getEmailString())) {
						$suggested_photos[] = array(
							'url' => 'https://gravatar.com/avatar/' . md5($email) . '?s=100&d=404',
							'title' => 'Gravatar: ' . $email,
						);
					}
				}
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person1.png', true),
					'title' => 'Silhouette: Male #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person3.png', true),
					'title' => 'Silhouette: Male #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person4.png', true),
					'title' => 'Silhouette: Male #3',
				);
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person2.png', true),
					'title' => 'Silhouette: Female #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person5.png', true),
					'title' => 'Silhouette: Female #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person6.png', true),
					'title' => 'Silhouette: Female #3',
				);
				
				break;
		}
		
		$tpl->assign('suggested_photos', $suggested_photos);
		
		$tpl->display('devblocks:cerberusweb.core::internal/choosers/avatar_chooser_popup.tpl');
	}
	
	private function _internalAction_contextAddLinksJson() {
		header('Content-type: application/json');
		
		@$from_context = DevblocksPlatform::importGPC($_POST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_POST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_POST['context_id'],'array',[]);
		
		// [TODO] Privs
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(is_array($context_ids))
			foreach($context_ids as $context_id)
				DAO_ContextLink::setLink($context, $context_id, $from_context, $from_context_id);
		
		echo json_encode(true);
	}
	
	private function _internalAction_contextDeleteLinksJson() {
		header('Content-type: application/json');
		
		@$from_context = DevblocksPlatform::importGPC($_POST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_POST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_POST['context_id'],'array',[]);
		
		// [TODO] Privs
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(is_array($context_ids))
			foreach($context_ids as $context_id)
				DAO_ContextLink::deleteLink($context, $context_id, $from_context, $from_context_id);
		
		echo json_encode(true);
	}
	
	private function _internalAction_renderMergePopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		try {
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				throw new Exception_DevblocksValidationError("Invalid record type.");
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.merge', $context_ext->id)))
				throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
			
			if($ids) {
				$ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($ids), 'int');
				
				if(is_array($ids)) {
					$desired_models = $context_ext->getModelObjects($ids);
					
					// Check permissions
					
					$models = array_intersect_key(
						$desired_models,
						array_flip(
							array_keys(
								CerberusContexts::isWriteableByActor($context_ext->id, $desired_models, $active_worker), true
							)
						)
					);
					
					if(count($desired_models) != count($models))
						throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
					
					unset($desired_models);
					
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
					ksort($dicts);
					
					$tpl->assign('dicts', $dicts);
				}
			}
			$tpl->assign('aliases', $context_ext->getAliasesForContext($context_ext->manifest));
			$tpl->assign('context_ext', $context_ext);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_chooser.tpl');
			
		} catch (Exception_DevblocksValidationError $e) {
			$tpl->assign('error_message', $e->getMessage());
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
			
		} catch (Exception $e) {
			$tpl->assign('error_message', 'An unexpected error occurred.');
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
		}
	}
	
	private function _internalAction_renderMergeMappingPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array',[]);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$DEBUG = false;
		
		try {
			if($ids)
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
			
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				throw new Exception_DevblocksValidationError("Invalid record type.");
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.merge', $context_ext->id)))
				throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
			
			if(
				empty($ids)
				|| count($ids) < 2
				|| false == ($models = $context_ext->getModelObjects($ids))
				|| count($models) < 2
			)
				throw new Exception_DevblocksValidationError("You haven't provided at least two records to merge.");
			
			$field_labels = $field_values = [];
			CerberusContexts::getContext($context_ext->id, null, $field_labels, $field_values, '', false, false);
			$field_types = $field_values['_types'];
			
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'custom_');
			
			ksort($dicts);
			
			if($DEBUG) {
				var_dump($dicts);
			}
			
			if(!($context_ext instanceof IDevblocksContextMerge))
				throw new Exception_DevblocksValidationError("This record type doesn't support merging.");
			
			$properties = $context_ext->mergeGetKeys();
			$custom_fields = DAO_CustomField::getByContext($context);
			
			// Add custom fields
			foreach(array_keys($field_labels) as $label_key) {
				if(preg_match('#^custom_\d+$#', $label_key))
					$properties[] = $label_key;
			}
			
			$field_values = [];
			
			foreach($properties as $k) {
				if(!isset($field_labels[$k]) || !isset($field_types[$k]))
					continue;
				
				$field_values[$k] = [
					'label' => $field_labels[$k],
					'type' => $field_types[$k],
					'values' => [],
				];
				
				$cfield_id = 0;
				$matches = [];
				
				if(preg_match('#^custom_(\d+)$#', $k, $matches)) {
					$cfield_id = $matches[1];
					
					// If the field doesn't exist anymore, skip
					if(!isset($custom_fields[$cfield_id]))
						continue;
				}
				
				foreach($dicts as $dict) {
					$v = $dict->get($k) ?: '';
					$handled = false;
					
					// Skip null custom fields
					if(DevblocksPlatform::strStartsWith($k, 'custom_') && 0 == strlen($v))
						continue;
					
					// Label translation
					switch($field_types[$k]) {
						case 'context_url':
							if($v) {
								$dict_key_id = substr($k, 0, -6) . 'id';
								$v = sprintf("%s", $v);
							}
							break;
						
						case Model_CustomField::TYPE_CHECKBOX:
							$v = (1 == $v) ? 'yes' : 'no';
							break;
						
						case Model_CustomField::TYPE_CURRENCY:
							@$currency_id = $dict->get($k . '_currency_id');
							if($currency_id && false != ($currency = DAO_Currency::get($currency_id))) {
								$v = $currency->format($dict->$k);
							}
							break;
						
						case Model_CustomField::TYPE_DECIMAL:
							// [TODO]
							break;
						
						case Model_CustomField::TYPE_DROPDOWN:
							@$options = $custom_fields[$cfield_id]->params['options'];
							
							// Ignore invalid options
							if(!in_array($v, $options)) {
								$handled = true;
							}
							break;
						
						case Model_CustomField::TYPE_FILE:
							if($v && false !== ($file = DAO_Attachment::get($v)))
								$v = sprintf("%s (%s) %s", $file->name, $file->mime_type, DevblocksPlatform::strPrettyBytes($file->storage_size));
							break;
						
						case Model_CustomField::TYPE_FILES:
							@$values = $dict->custom[$cfield_id];
							
							if(!is_array($values))
								break;
							
							$file_ids = DevblocksPlatform::parseCsvString($v);
							$ptr =& $field_values[$k]['values'];
							
							if(is_array($file_ids) && false !== ($files = DAO_Attachment::getIds($file_ids))) {
								foreach($files as $file_id => $file) {
									$ptr[$file_id] = sprintf("%s (%s) %s", $file->name, $file->mime_type, DevblocksPlatform::strPrettyBytes($file->storage_size));
								}
							}
							
							asort($ptr);
							$handled = true;
							break;
						
						case Model_CustomField::TYPE_LINK:
							if($v) {
								$dict_key_id = $k . '__label';
								$v = sprintf("%s (#%d)", $dict->$dict_key_id, $dict->$k);
							}
							break;
						
						case Model_CustomField::TYPE_LIST:
							@$values = $dict->custom[$cfield_id];
							
							if(!is_array($values))
								break;
							
							foreach($values as $v)
								$field_values[$k]['values'][$v] = $v;
							
							asort($field_values[$k]['values']);
							
							$handled = true;
							break;
						
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							@$values = $dict->custom[$cfield_id];
							@$options = $custom_fields[$cfield_id]->params['options'];
							
							if(!is_array($values))
								break;
							
							foreach($values as $v)
								if(in_array($v, $options))
									$field_values[$k]['values'][$v] = $v;
							
							asort($field_values[$k]['values']);
							
							$handled = true;
							break;
						
						case Model_CustomField::TYPE_WORKER:
							if($v && false !== ($worker = DAO_Worker::get($v)))
								$v = $worker->getName();
							break;
					}
					
					if(!$handled) {
						if(0 != strlen($v)) {
							if(false === array_search($v, $field_values[$k]['values']))
								$field_values[$k]['values'][$dict->id] = $v;
						}
					}
				}
			}
			
			// Always sort an updated column in descending order (most recent first)
			foreach(['updated', 'updated_at', 'updated_date'] as $kk) {
				if(array_key_exists($kk, $field_values)) {
					arsort($field_values[$kk]['values']);
				}
			}
			
			// Always sort statuses in order
			if(array_key_exists('status', $field_values) && in_array($context_ext->id, [CerberusContexts::CONTEXT_TICKET, CerberusContexts::CONTEXT_TASK])) {
				uasort($field_values['status']['values'], function ($a, $b) {
					$a_status_id = DAO_Ticket::getStatusIdFromText($a);
					$b_status_id = DAO_Ticket::getStatusIdFromText($b);
					return $a_status_id <=> $b_status_id;
				});
			}
			
			if($DEBUG) {
				var_dump($field_values);
			}
			
			$tpl->assign('aliases', $context_ext->getAliasesForContext($context_ext->manifest));
			$tpl->assign('context_ext', $context_ext);
			$tpl->assign('dicts', $dicts);
			$tpl->assign('field_values', $field_values);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_mapping.tpl');
			
		} catch (Exception_DevblocksValidationError $e) {
			$tpl->assign('error_message', $e->getMessage());
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			
		} catch (Exception $e) {
			$tpl->assign('error_message', 'An unexpected error occurred.');
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
		}
		
		return true;
	}
	
	private function _internalAction_saveMerge() : bool {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string','');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',[]);
		@$target_id = DevblocksPlatform::importGPC($_POST['target_id'],'integer',0);
		@$keys = DevblocksPlatform::importGPC($_POST['keys'],'array',[]);
		@$values = DevblocksPlatform::importGPC($_POST['values'],'array',[]);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		
		$DEBUG = false;
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if($ids)
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
			
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				throw new Exception_DevblocksValidationError("Invalid record type.");
			
			$aliases = $context_ext->getAliasesForContext($context_ext->manifest);
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.merge', $context_ext->id)))
				throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
			
			if(
				empty($ids)
				|| count($ids) < 2
				|| false == ($desired_models = $context_ext->getModelObjects($ids))
				|| count($desired_models) < 2
			)
				throw new Exception_DevblocksValidationError("You must provide at least two records to merge.");
			
			// Determine target + sources
			
			if(!in_array($target_id, $ids))
				throw new Exception_DevblocksValidationError("Invalid target record.");
			
			// Check permissions
			
			$models = array_intersect_key(
				$desired_models,
				array_flip(
					array_keys(
						CerberusContexts::isWriteableByActor($context_ext->id, $desired_models, $active_worker), true
					)
				)
			);
			
			if(count($desired_models) != count($models))
				throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
			
			unset($desired_models);
			
			// Merge
			
			$source_ids = array_diff($ids, [$target_id]);
			
			$field_labels = $field_values = [];
			CerberusContexts::getContext($context_ext->id, null, $field_labels, $field_values, '', false, false);
			$field_types = $field_values['_types'];
			
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'custom_');
			
			ksort($dicts);
			
			$changeset = [];
			
			if($DEBUG) {
				var_dump($values);
			}
			
			foreach($keys as $value_key) {
				if(preg_match('#^custom\_(\d+)$#', $value_key)) {
					$cfield_id = intval(substr($value_key, 7));
					
					switch(@$field_types[$value_key]) {
						case Model_CustomField::TYPE_CHECKBOX:
							@$dict_id = $values[$value_key];
							@$value = $dicts[$dict_id]->custom[$cfield_id] ? 1 : 0;
							break;
						
						case Model_CustomField::TYPE_CURRENCY:
							@$dict_id = $values[$value_key];
							$value = $dicts[$dict_id]->get(sprintf('%s_decimal', $value_key));
							break;
						
						case Model_CustomField::TYPE_DECIMAL:
							// [TODO] Format as user input
							break;
						
						case Model_CustomField::TYPE_FILES:
						case Model_CustomField::TYPE_LIST:
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							if(!isset($values[$value_key])) {
								$value = [];
							} else {
								$value = $values[$value_key];
							}
							
							break;
						
						default:
							@$dict_id = $values[$value_key];
							$value = $dicts[$dict_id]->custom[$cfield_id];
							break;
					}
					
				} else {
					@$dict_id = $values[$value_key];
					
					switch(@$field_types[$value_key]) {
						case 'context_url':
							if($value_key) {
								$value_key = substr($value_key, 0, -6) . 'id';
								$value = $dicts[$dict_id]->$value_key;
							}
							break;
						
						default:
							$value = $dicts[$dict_id]->$value_key;
							break;
					}
				}
				
				$changeset[$value_key] = $value;
			}
			
			if($DEBUG) {
				var_dump($target_id);
				var_dump($source_ids);
				var_dump($changeset);
			}
			
			if(false != ($merge_automation = DAO_AutomationEvent::getByName('record.merge'))) {
				$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
				$error = null;
				
				$initial_state = [
					'record_type' => $context_ext->manifest->getParam('uri', $context_ext->id),
					'records' => $dicts,
					'source_ids' => $source_ids,
					'target_id' => $target_id,
					'worker__context' => CerberusContexts::CONTEXT_WORKER,
					'worker_id' => $active_worker->id,
				];
				
				$event_dict = DevblocksDictionaryDelegate::instance($initial_state);
				
				if(false != ($handlers = $merge_automation->getKata($event_dict, $error))) {
					$automation_results = $event_handler->handleUntilReturn(
						AutomationTrigger_RecordMerge::ID,
						$handlers,
						$initial_state,
						$error
					);
					
					if($automation_results instanceof DevblocksDictionaryDelegate) {
						$return = $automation_results->getKeyPath('__return', []);
						
						if (array_key_exists('deny', $return)) {
							$error_message = $return['deny'] ?: 'This merge is not allowed.';
							throw new Exception_DevblocksValidationError($error_message);
						}
					}
				}
			}
			
			$dao_class = $context_ext->getDaoClass();
			$dao_fields = $custom_fields = [];
			$error = null;
			
			if(!method_exists($dao_class, 'update'))
				throw new Exception_DevblocksValidationError("Not implemented.");
			
			if(!method_exists($context_ext, 'getDaoFieldsFromKeysAndValues'))
				throw new Exception_DevblocksValidationError("Not implemented.");
			
			if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
				throw new Exception_DevblocksValidationError($error);
			
			if(is_array($dao_fields))
				if(!$dao_class::validate($dao_fields, $error, $target_id))
					throw new Exception_DevblocksValidationError($error);
			
			if($custom_fields)
				if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
					throw new Exception_DevblocksValidationError($error);
			
			if(!$dao_class::onBeforeUpdateByActor($active_worker, $dao_fields, $target_id, $error))
				throw new Exception_DevblocksValidationError($error);
			
			if($DEBUG) {
				var_dump($dao_fields);
				var_dump($custom_fields);
			}
			
			$dao_class::update($target_id, $dao_fields);
			$dao_class::onUpdateByActor($active_worker, $dao_fields, $target_id);
			
			if($custom_fields)
				DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $target_id, $custom_fields);
			
			if(method_exists($dao_class, 'mergeIds'))
				$dao_class::mergeIds($source_ids, $target_id);
			
			foreach($source_ids as $source_id) {
				/*
				 * Log activity (context.merge)
				 */
				$entry = [
					//{{actor}} merged {{context_label}} {{source}} into {{context_label}} {{target}}
					'message' => 'activities.record.merge',
					'variables' => [
						'context' => $context_ext->id,
						'context_label' => DevblocksPlatform::strLower($aliases['singular']),
						'source' => sprintf("%s", $dicts[$source_id]->_label),
						'target' => sprintf("%s", $dicts[$target_id]->_label),
					],
					'urls' => [
						'target' => sprintf("ctx://%s:%d/%s", $context_ext->id, $target_id, DevblocksPlatform::strToPermalink($dicts[$target_id]->_label)),
					],
				];
				CerberusContexts::logActivity('record.merge', $context_ext->id, $target_id, $entry);
			}
			
			// Fire a merge event for plugins
			$eventMgr = DevblocksPlatform::services()->event();
			
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'record.merge',
					array(
						'context' => $context_ext->id,
						'target_id' => $target_id,
						'source_ids' => $source_ids,
					)
				)
			);
			
			// Nuke the source records
			$dao_class::delete($source_ids);
			
			// Display results
			$tpl->assign('aliases', $context_ext->getAliasesForContext($context_ext->manifest));
			$tpl->assign('context_ext', $context_ext);
			$tpl->assign('target_id', $target_id);
			$tpl->assign('dicts', $dicts);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_results.tpl');
			
		} catch(Exception_DevblocksValidationError $e) {
			$tpl->assign('error_message', $e->getMessage());
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			
		} catch(Exception $e) {
			$tpl->assign('error_message', 'An unexpected error occurred.');
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
		}
		
		return true;
	}
	
	private function _internalAction_viewLogDelete() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		@$row_ids = DevblocksPlatform::importGPC($_POST['row_id'], 'array', []);
		
		$row_ids = DevblocksPlatform::sanitizeArray($row_ids, 'int');
		
		if($row_ids) {
			DAO_ContextActivityLog::delete($row_ids);
		}
		
		if($view_id) {
			if(false != ($view = C4_AbstractViewLoader::getView($view_id))) {
				$view->render();
			}
		}
	}
}