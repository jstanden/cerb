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

class PageSection_InternalPortals extends Extension_PageSection {
	function render() {}
	
	public function handleActionForPage(string $action, string $scope=null) {
		if('internalAction' == $scope) {
			switch ($action) {
				case 'renderTemplatePeek':
					return $this->_internalAction_renderTemplatePeek();
				case 'saveTemplatePeek':
					return $this->_internalAction_saveTemplatePeek();
				case 'showImportTemplatesPeek':
					return $this->_internalAction_showImportTemplatesPeek();
				case 'saveImportTemplatesPeek':
					return $this->_internalAction_saveImportTemplatesPeek();
				case 'showExportTemplatesPeek':
					return $this->_internalAction_showExportTemplatesPeek();
				case 'saveExportTemplatesPeek':
					return $this->_internalAction_saveExportTemplatesPeek();
			}
		}
		return false;
	}
	
	private function _internalAction_renderTemplatePeek() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl->assign('view_id', $view_id);
		
		if(false != ($template = DAO_DevblocksTemplate::get($id)))
			$tpl->assign('template', $template);
		
		if(DevblocksPlatform::strStartsWith($template->tag, 'portal_')) {
			list(, $portal_code) = explode('_', $template->tag, 2);
			
			if(false == ($portal = DAO_CommunityTool::getByCode($portal_code)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$tpl->assign('portal', $portal);
		}
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/peek.tpl');
	}
	
	private function _internalAction_saveTemplatePeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		if(false == ($template = DAO_DevblocksTemplate::get($id)))
			return false;
		
		if(DevblocksPlatform::strStartsWith($template->tag, 'portal_')) {
			list(, $portal_code) = explode('_', $template->tag, 2);
			
			if(false == ($portal = DAO_CommunityTool::getByCode($portal_code)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$tpl->assign('portal', $portal);
			
			if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(!empty($do_delete)) {
			DAO_DevblocksTemplate::delete($id);
			
		} else {
			DAO_DevblocksTemplate::update($id, array(
				DAO_DevblocksTemplate::CONTENT => $content,
				DAO_DevblocksTemplate::LAST_UPDATED => time(),
			));
		}
		
		// Clear compiled template
		$tpl_sandbox = DevblocksPlatform::services()->templateSandbox();
		$hash_key = sprintf("devblocks:%s:%s:%s", $template->plugin_id, $template->tag, $template->path);
		$tpl->clearCompiledTemplate($hash_key, APP_BUILD);
		$tpl_sandbox->clearCompiledTemplate($hash_key, null);
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id)))
			$view->render();
	}
	
	private function _internalAction_showImportTemplatesPeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'],'integer',0);
		
		if(!$portal_id)
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(false == ($portal = DAO_CommunityTool::get($portal_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_CommunityTool::isReadableByActor($portal, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/import.tpl');
	}
	
	private function _internalAction_saveImportTemplatesPeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'],'integer',0);
		@$file_id = DevblocksPlatform::importGPC($_POST['file_id'],'integer',0);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$portal_id || false == ($portal = DAO_CommunityTool::get($portal_id)))
				throw new Exception_DevblocksAjaxError("Invalid portal.");
			
			if(!$file_id || false == ($file = DAO_Attachment::get($file_id)))
				throw new Exception_DevblocksAjaxError("Invalid import file.");
			
			if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
				throw new Exception_DevblocksAjaxError(DevblocksPlatform::translate('error.core.no_acl.edit'));
			
			$fp = DevblocksPlatform::getTempFile();
			$filename = DevblocksPlatform::getTempFileInfo($fp);
			
			$file->getFileContents($fp);
			
			DAO_DevblocksTemplate::importXmlFile($filename, 'portal_'.$portal->code);
			
			echo json_encode([
				'success' => true,
			]);
			
		} catch (Exception_DevblocksAjaxError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
	
	private function _internalAction_showExportTemplatesPeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/export.tpl');
	}
	
	private function _internalAction_saveExportTemplatesPeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$filename = DevblocksPlatform::importGPC($_POST['filename'],'string','');
		@$author = DevblocksPlatform::importGPC($_POST['author'],'string','');
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string','');
		
		// Build XML file
		$xml = simplexml_load_string(
			'<?xml version="1.0" encoding="' . LANG_CHARSET_CODE . '"?>'.
			'<cerb>'.
			'<templates>'.
			'</templates>'.
			'</cerb>'
		); /* @var $xml SimpleXMLElement */
		
		// Author
		$eAuthor = $xml->templates->addChild('author'); /* @var $eAuthor SimpleXMLElement */
		$eAuthor->addChild('name', htmlspecialchars($author));
		$eAuthor->addChild('email', htmlspecialchars($email));
		
		// Load view
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Load all data
		$view->renderLimit = -1;
		$view->renderPage = 0;
		$view->setAutoPersist(false);
		list($results,) = $view->getData();
		
		// Add template
		if(is_array($results))
			foreach($results as $result) {
				// Load content
				if(null == ($template = DAO_DevblocksTemplate::get($result[SearchFields_DevblocksTemplate::ID])))
					continue;
				
				$eTemplate = $xml->templates->addChild('template', htmlspecialchars($template->content)); /* @var $eTemplate SimpleXMLElement */
				$eTemplate->addAttribute('plugin_id', htmlspecialchars($template->plugin_id));
				$eTemplate->addAttribute('path', htmlspecialchars($template->path));
			}
		
		// Format download file
		$imp = new DOMImplementation;
		$doc = $imp->createDocument("", "");
		$doc->encoding = LANG_CHARSET_CODE;
		$doc->formatOutput = true;
		
		$simplexml = dom_import_simplexml($xml); /* @var $dom DOMElement */
		$simplexml = $doc->importNode($simplexml, true);
		$simplexml = $doc->appendChild($simplexml);
		
		header("Content-type: text/xml");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		
		echo $doc->saveXML();
		DevblocksPlatform::exit();
	}
	
}