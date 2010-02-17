<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class CustomFieldSource_CommunityPortal extends Extension_CustomFieldSource {
	const ID = 'usermeet.fields.source.community_portal';
};

class UmPortalHelper {
	static private $_code = null; 
	
	public static function getCode() {
		return self::$_code;
	}	
	
	public static function setCode($code) {
		self::$_code = $code;
	}
	
	/**
	 * @return Model_CommunitySession
	 */
	public static function getSession() {
		$fingerprint = self::getFingerprint();
		
		$session_id = md5($fingerprint['ip'] . self::getCode() . $fingerprint['local_sessid']);
		return DAO_CommunitySession::get($session_id);
	}
	
	public static function getFingerprint() {
		$sFingerPrint = DevblocksPlatform::importGPC($_COOKIE['GroupLoginPassport'],'string','');
		$fingerprint = null;
		if(!empty($sFingerPrint)) {
			$fingerprint = unserialize($sFingerPrint);
		}
		return $fingerprint;
	}
};

class UmCommunityPage extends CerberusPageExtension {
	const ID = 'usermeet.page.community';

	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(__FILE__) . '/templates/';
		parent::__construct($manifest);
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
		
		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));
		
		$stack = $response->path;
		array_shift($stack); // community
		
		if(!empty($stack)) {
			@$code = array_shift($stack); // code
			$tool = DAO_CommunityTool::getByCode($code);
			$tpl->assign('tool', $tool);
			$tpl->assign('tool_manifests', DevblocksPlatform::getExtensions('usermeet.tool', false));

//			$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.activity.tab', false);
//			uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
//			$tpl->assign('tab_manifests', $tab_manifests);

			@$tab_selected = array_shift($stack);
			if(empty($tab_selected)) $tab_selected = 'settings';
			$tpl->assign('tab_selected', $tab_selected);
			
			$tpl->display('file:' . $this->_TPL_PATH . 'community/display/index.tpl');
		}
		
	}
	
	function showAddPortalPeekAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tool_manifests = DevblocksPlatform::getExtensions('usermeet.tool', false);
		$tpl->assign('tool_manifests', $tool_manifests);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'community/config/tab/add.tpl');
	}
	
	function saveAddPortalPeekAction() {
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string', '');
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string', '');
		
		$portal_code = DAO_CommunityTool::generateUniqueCode();
		
		// Create portal
		$fields = array(
			DAO_CommunityTool::NAME => $name,
			DAO_CommunityTool::EXTENSION_ID => $extension_id,
			DAO_CommunityTool::CODE => $portal_code,
		);
		$portal_id = DAO_CommunityTool::create($fields);
		
		// Redirect to the display page
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('community',$portal_code)));
	}
	
	function showTabSettingsAction() {
		@$tool_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		if(null != ($instance = DAO_CommunityTool::get($tool_id))) {
			$tool = DevblocksPlatform::getExtension($instance->extension_id, true);
			$tpl->assign('tool', $tool);
			$tpl->assign('instance', $instance);
		}
			
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/settings/index.tpl');
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
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','communities')));
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
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('community',$code,'settings')));
	}
	
	function showTabTemplatesAction() {
		@$tool_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		if(null != ($tool = DAO_CommunityTool::get($tool_id)))
			$tpl->assign('tool', $tool);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'portal_templates';
		$defaults->class_name = 'View_DevblocksTemplate';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);

		$view->name = 'Custom Templates';
		$view->params[SearchFields_DevblocksTemplate::TAG] = new DevblocksSearchCriteria(SearchFields_DevblocksTemplate::TAG,'=','portal_'.$tool->code);
		C4_AbstractViewLoader::setView($view->id, $view);  
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', View_DevblocksTemplate::getFields());
		$tpl->assign('view_searchable_fields', View_DevblocksTemplate::getSearchFields());
			
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/templates/index.tpl');
	}
	
	function getTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('view_id', $view_id);
		
		if(null != ($template = DAO_DevblocksTemplate::get($id)))
			$tpl->assign('template', $template);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/templates/peek.tpl');
	}
	
	function showTemplatesBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$path = $this->_TPL_PATH;
		$tpl->assign('path', $path);
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
		// Custom Fields
//		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_FeedbackEntry::ID);
//		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $path . 'community/display/tabs/templates/bulk.tpl');
	}
	
	function doTemplatesBulkUpdateAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
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

		// Clear template cache
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->clear_compiled_tpl();
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id)))
			$view->render();
	}
	
	function showAddTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
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
		uasort($templates, create_function('$a, $b', "return strcasecmp(\$a->plugin_id.' '.\$a->path,\$b->plugin_id.' '.\$b->path);\n"));
		
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
		
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/templates/add.tpl');
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
		
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/templates/peek.tpl');
	}
	
	function showImportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/templates/import.tpl');
	}
	
	function saveImportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		@$import_file = $_FILES['import_file'];

		DAO_DevblocksTemplate::importXmlFile($import_file['tmp_name'], 'portal_'.$portal);
		
		// Clear template cache
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->clear_compiled_tpl();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('community',$portal,'templates')));
	}

	function showExportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/templates/export.tpl');
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
		$tpl->assign('path', $this->_TPL_PATH);

		if(null != ($tool = DAO_CommunityTool::get($tool_id)))
			$tpl->assign('tool', $tool);
			
        // Install
        $url_writer = DevblocksPlatform::getUrlService();
        $url = $url_writer->write('c=portal&a='.$tool->code,true);
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
			
		$tpl->display('file:' . $this->_TPL_PATH . 'community/display/tabs/installation/index.tpl');
	}
	
};

class UmPortalController extends DevblocksControllerExtension {
    const ID = 'usermeet.controller.portal';
    
	function __construct($manifest) {
		parent::__construct($manifest);
	}
		
	/**
	 * @param DevblocksHttpRequest $request 
	 * @return DevblocksHttpResponse $response 
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;

		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

		UmPortalHelper::setCode($code);

        if(null != (@$tool = DAO_CommunityTool::getByCode($code))) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
            if(null != (@$tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
	        	return $tool->handleRequest(new DevblocksHttpRequest($stack));
            }
        } else {
            die("Tool not found.");
        }
	}
	
	/**
	 * @param DevblocksHttpResponse $response
	 */
	function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;

		$tpl = DevblocksPlatform::getTemplateService();

		// Globals for Community Tool template scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

        if(null != ($tool = DAO_CommunityTool::getByCode($code))) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
            if(null != ($tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
		        $tool->writeResponse(new DevblocksHttpResponse($stack));
            }
        } else {
            die("Tool not found.");
        }
	}
	
};

class UmConfigCommunitiesTab extends Extension_ConfigTab {
	const ID = 'usermeet.config.tab.communities';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

	    // View
		$tpl->assign('response_uri', 'config/communities');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'portals_cfg';
		$defaults->class_name = 'View_CommunityPortal';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', View_CommunityPortal::getFields());
		$tpl->assign('view_searchable_fields', View_CommunityPortal::getSearchFields());
	    
		$tpl->display('file:' . $tpl_path . 'community/config/tab/index.tpl');
	}
	
	// [TODO] This really doesn't belong on the tab here
	function getContactSituationAction() {
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		UmPortalHelper::setCode($portal);
		
		$module = DevblocksPlatform::getExtension('sc.controller.contact',true,true);
		$module->getSituation();
	}
	
};

