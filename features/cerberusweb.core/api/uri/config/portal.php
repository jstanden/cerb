<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class PageSection_SetupPortal extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		array_shift($stack); // config
		array_shift($stack); // portal
		
		if(!empty($stack)) {
			@$code = array_shift($stack); // code
			$tool = DAO_CommunityTool::getByCode($code);
			$tpl->assign('tool', $tool);
			$tpl->assign('tool_manifests', DevblocksPlatform::getExtensions('usermeet.tool', false));

			@$tab_selected = array_shift($stack);
			if(empty($tab_selected))
				$tab_selected = 'settings';
			$tpl->assign('tab_selected', $tab_selected);
			
			$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/index.tpl');
		}
	}
	
	// [TODO] Move this to the SC plugin!!! (and reflect to the controller somehow)
	function addContactSituationAction() {
		//@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		//ChPortalHelper::setCode($portal);

		$tpl = DevblocksPlatform::getTemplateService();
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Contact: Fields
		$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('ticket_fields', $ticket_fields);
		
		// Custom field types
		$types = Model_CustomField::getTypes();
		$tpl->assign('field_types', $types);
		
		// Default reply-to
		$replyto_default = DAO_AddressOutgoing::getDefault();
		$tpl->assign('replyto_default', $replyto_default);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/config/module/contact/situation.tpl');
	}
	
	function showTabSettingsAction() {
		@$tool_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null != ($instance = DAO_CommunityTool::get($tool_id))) {
			$tool = DevblocksPlatform::getExtension($instance->extension_id, true);
			$tpl->assign('tool', $tool);
			$tpl->assign('instance', $instance);
		}
			
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/settings/index.tpl');
	}
	
	function saveTabSettingsAction() {
		@$code = DevblocksPlatform::importGPC($_POST['portal'],'string');
		@$name = DevblocksPlatform::importGPC($_POST['portal_name'],'string','');
		@$iDelete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		if(null != ($instance = DAO_CommunityTool::getByCode($code))) {
			// Deleting?
			if(!empty($iDelete)) {
				$tool = DAO_CommunityTool::getByCode($code); /* @var $tool Model_CommunityTool */
				DAO_CommunityTool::delete($tool->id);
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','portals')));
				return;
				
			} else {
				$manifest = DevblocksPlatform::getExtension($instance->extension_id, false, true);
				$tool = $manifest->createInstance(); /* @var $tool Extension_UsermeetTool */
				
				// Update the tool name if it has changed
				if(0 != strcmp($instance->name,$name))
					DAO_CommunityTool::update($instance->id, array(
						DAO_CommunityTool::NAME => $name
					));
				
				// Defer the rest to tool instances and extensions
				$tool->saveConfiguration($instance);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','portal',$code,'settings')));
	}
	
	function showTabTemplatesAction() {
		@$tool_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null != ($tool = DAO_CommunityTool::get($tool_id)))
			$tpl->assign('tool', $tool);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'portal_templates';
		$defaults->class_name = 'View_DevblocksTemplate';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);

		$view->name = 'Custom Templates';
		$view->addParam(new DevblocksSearchCriteria(SearchFields_DevblocksTemplate::TAG,'=','portal_'.$tool->code));
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
			
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/templates/index.tpl');
	}
	
	function getTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(null != ($template = DAO_DevblocksTemplate::get($id)))
			$tpl->assign('template', $template);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/templates/peek.tpl');
	}
	
	function showTemplatesBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}

		// Custom Fields
//		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK);
//		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/templates/bulk.tpl');
	}
	
	function doTemplatesBulkUpdateAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Templates fields
		@$deleted = trim(DevblocksPlatform::importGPC($_POST['deleted'],'integer',0));

		$do = array();
		
		// Do: Deleted
		if(0 != strlen($deleted))
			$do['deleted'] = $deleted;
			
		// Do: Custom fields
//		$do = DAO_CustomFieldValue::handleBulkPost($do);

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

	function saveTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		if(!empty($do_delete)) {
			DAO_DevblocksTemplate::delete($id);
			
		} else {
			DAO_DevblocksTemplate::update($id, array(
				DAO_DevblocksTemplate::CONTENT => $content,
				DAO_DevblocksTemplate::LAST_UPDATED => time(),
			));
		}

		// Clear compiled templates
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->clearCompiledTemplate();
		$tpl->clearAllCache();
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id)))
			$view->render();
	}
	
	function showAddTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);

		if(null == ($tool = DAO_CommunityTool::getByCode($portal)))
			return;
			
		if(null == ($tool_ext = DevblocksPlatform::getExtension($tool->extension_id, false)))
			return;
			
		if(null == ($template_set = @$tool_ext->params['template_set']))
			$template_set = ''; // not null
			
		$templates = DevblocksPlatform::getTemplates($template_set);
		$existing_templates = DAO_DevblocksTemplate::getWhere(sprintf("%s = %s",
			DAO_DevblocksTemplate::TAG,
			C4_ORMHelper::qstr('portal_'.$portal)
		));
		
		// Sort templates
		DevblocksPlatform::sortObjects($templates, 'sort_key');
		
		// Filter out templates implemented by this portal already
		if(is_array($templates))
		foreach($templates as $idx => $template) { /* @var $template DevblocksTemplate */
			if(is_array($existing_templates))
			foreach($existing_templates as $existing) { /* @var $existing Model_DevblocksTemplate */
				if(0 == strcasecmp($template->plugin_id, $existing->plugin_id)
					&& 0 == strcasecmp($template->path, $existing->path))
						unset($templates[$idx]);
			}
		}
		$tpl->assign('templates', $templates);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/templates/add.tpl');
	}
	
	function saveAddTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		@$template = DevblocksPlatform::importGPC($_REQUEST['template'],'string','');
		
		list($plugin_id, $template_path) = explode(':', $template, 2);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Pull from filesystem for editing
		$content = '';
		if(null != ($plugin = DevblocksPlatform::getPlugin($plugin_id))) {
			$path = APP_PATH . '/' . $plugin->dir . '/templates/' . $template_path;
			if(file_exists($path)) {
				$content = file_get_contents($path);
			}
		}
		
		$fields = array(
			DAO_DevblocksTemplate::LAST_UPDATED => 0,
			DAO_DevblocksTemplate::PLUGIN_ID => $plugin_id,
			DAO_DevblocksTemplate::PATH => $template_path,
			DAO_DevblocksTemplate::TAG => 'portal_' . $portal,
			DAO_DevblocksTemplate::CONTENT => $content,
		);
		$id = DAO_DevblocksTemplate::create($fields);

		$template = DAO_DevblocksTemplate::get($id);
		$tpl->assign('template', $template);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/templates/peek.tpl');
	}
	
	function showImportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/templates/import.tpl');
	}
	
	function saveImportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		@$import_file = $_FILES['import_file'];

		DAO_DevblocksTemplate::importXmlFile($import_file['tmp_name'], 'portal_'.$portal);
		
		// Clear compiled templates
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->clearCompiledTemplate();
		$tpl->clearAllCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','portal',$portal,'templates')));
	}

	function showExportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/templates/export.tpl');
	}
	
	function saveExportTemplatesPeekAction() {
		if(null == ($active_worker = CerberusApplication::getActiveWorker()) || !$active_worker->is_superuser)
			exit;
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		@$filename = DevblocksPlatform::importGPC($_REQUEST['filename'],'string','');
		@$author = DevblocksPlatform::importGPC($_REQUEST['author'],'string','');
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		// Build XML file
		$xml = simplexml_load_string(
			'<?xml version="1.0" encoding="' . LANG_CHARSET_CODE . '"?>'.
			'<cerb5>'.
			'<templates>'.
			'</templates>'.
			'</cerb5>'
		); /* @var $xml SimpleXMLElement */
		
		// Author
		$eAuthor =& $xml->templates->addChild('author'); /* @var $eAuthor SimpleXMLElement */
		$eAuthor->addChild('name', htmlspecialchars($author));
		$eAuthor->addChild('email', htmlspecialchars($email));
		
		// Load view
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			exit;
		
		// Load all data
		$view->renderLimit = -1;
		$view->renderPage = 0;
		list($results, $null) = $view->getData();
		
		// Add template
		if(is_array($results))
		foreach($results as $result) {
			// Load content
			if(null == ($template = DAO_DevblocksTemplate::get($result[SearchFields_DevblocksTemplate::ID])))
				continue;

			$eTemplate =& $xml->templates->addChild('template', htmlspecialchars($template->content)); /* @var $eTemplate SimpleXMLElement */
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
		exit;
	}
	
	function showTabInstallationAction() {
		@$tool_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null != ($tool = DAO_CommunityTool::get($tool_id)))
			$tpl->assign('tool', $tool);
			
		// Install
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=portal&a='.$tool->code,true);
		$url_parts = parse_url($url);
		
		$host = $url_parts['host'];
		@$port = $_SERVER['SERVER_PORT'];
		$base = substr(DEVBLOCKS_WEBPATH,0,-1); // consume trailing
		$path = substr($url_parts['path'],strlen(DEVBLOCKS_WEBPATH)-1); // consume trailing slash

		@$parts = explode('/', $path);
		if($parts[1]=='index.php') // 0 is null from /part1/part2 paths.
			unset($parts[1]);
		$path = implode('/', $parts);
		
		$tpl->assign('host', $host);
		$tpl->assign('is_ssl', ($url_writer->isSSL() ? 1 : 0));
		$tpl->assign('port', $port);
		$tpl->assign('base', $base);
		$tpl->assign('path', $path);
			
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portal/tabs/installation/index.tpl');
	}
}