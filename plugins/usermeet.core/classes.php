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

class UmPortalController extends DevblocksControllerExtension {
    const ID = 'usermeet.controller.portal';
	private $tools = array();
	private $hash = array();
    
	function __construct($manifest) {
		parent::__construct($manifest);

	    // Internal Routing
	    // [TODO] Cache the code to extension lookup -- silly to go to DB every time for this
	    $this->tools = DAO_CommunityTool::getList();
	    foreach($this->tools as $idx => $tool) {
	        $this->hash[$tool->code] =& $this->tools[$idx];
	    }
	}
		
	/**
	 * @param DevblocksHttpRequest $request 
	 * @return DevblocksHttpResponse $response 
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;

		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

		// [TODO] Convert to Model_CommunityTool::getByCode()
        if(null != (@$tool = $this->hash[$code])) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
            if(null != (@$tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
				$tool->setPortal($code); // [TODO] Kinda hacky
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

		// [TODO] Convert to Model_CommunityTool::getByCode()
        if(null != ($tool = $this->hash[$code])) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
            if(null != ($tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
				$tool->setPortal($code); // [TODO] Kinda hacky
		        $tool->writeResponse(new DevblocksHttpResponse($stack));
            }
        } else {
            die("Tool not found.");
        }
	}
	
//	function test() {
//	    $proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'];
//	    $proxybase = $_SERVER['HTTP_DEVBLOCKSPROXYBASE'];
//
//	    echo "<html><head></head><body>";
//	    
//	    echo 'Proxy Host: ', $proxyhost, '<BR>';
//	    echo 'Proxy Base: ', $proxybase, '<BR>';
//	    echo 'Response: ', var_dump($response), '<BR>';
//	    
//	    echo "<h2>Get Test</h2>";
//	    echo "<a href='$proxybase/latest'>latest</a><br>";
//
//	    echo "<h2>Post Test</h2>";
//	    echo "<form action=\"${proxybase}/\" method=\"post\">";
//	    echo "<input type='text' name='name' value=''><br>";
//	    echo "<input type='checkbox' name='checky' value='1'><br>";
//	    echo "<input type='submit' value='Submit'>";
//	    echo "</form>";
//	    
//	    if(!empty($_POST)) {
//	        echo "<HR>"; print_r($_POST); echo "<HR>";
//	    }
//	    
//        echo "Cookies: ";
//	    print_r($_COOKIE);
//	    echo "<HR>";
//	    
////	    echo "PORTAL RESPONSE";
//	    echo "</body></html>";
//	    exit;
//	}
	
};

class UmConfigCommunitiesTab extends Extension_ConfigTab {
	const ID = 'usermeet.config.tab.communities';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		// Community sites
	    $communities = DAO_Community::getList();
	    $tpl->assign('communities', $communities);

	    // Tool Manifests
	    $tools = DevblocksPlatform::getExtensions('usermeet.tool', false, true);
	    $tpl->assign('tool_manifests', $tools);
	    
	    // Tool Instances
	    $community_tools = array();
	    $instances = DAO_CommunityTool::getList();
	    foreach($instances as $tool) {
	    	// Only tools with valid plugins
	    	if(!isset($tools[$tool->extension_id]))
				continue;
			
	        if(!isset($community_tools[$tool->community_id]))
				$community_tools[$tool->community_id] = array();
				
	        $community_tools[$tool->community_id][$tool->code] = $tool;
	    }
	    $tpl->assign('community_tools', $community_tools);
	    
		$tpl->display('file:' . $tpl_path . 'community/config/tab/index.tpl');
	}
	
	function getCommunityAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		if(!empty($id)) {
			$community = DAO_Community::get($id);
			$tpl->assign('community', $community);
		}
		
	    // Tool Manifests
	    $tools = DevblocksPlatform::getExtensions('usermeet.tool', false, true);
	    $tpl->assign('tool_manifests', $tools);
		
		$tpl->display('file:' . $tpl_path . 'community/config/tab/community_config.tpl');
	}
	
	function saveCommunityAction() {
		// [TODO] Privs
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','New Community');	
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);	

	    @$add_tool_id = DevblocksPlatform::importGPC($_POST['add_tool_id'],'string');

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','communities')));
			return;
		}
	    
		if(!empty($delete)) {
			DAO_Community::delete($id);
			
		} else {
		    $fields = array(
		        DAO_Community::NAME => (!empty($name) ? $name : "New Community"),
		    );
			
			if(empty($id)) { // Create
			    $id = DAO_Community::create($fields);
				
			} else { // Edit || Delete
			    DAO_Community::update($id,$fields);
			}
			
			if(!empty($add_tool_id) && !empty($id)) {
			    $fields = array(
			        DAO_CommunityTool::COMMUNITY_ID => $id,
			        DAO_CommunityTool::EXTENSION_ID => $add_tool_id
			    );
			    $tool_id = DAO_CommunityTool::create($fields);
			}
			
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','communities')));
	}
	
	// [TODO] This really doesn't belong on the tab here
	function getContactSituationAction() {
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$module = DevblocksPlatform::getExtension('sc.controller.contact',true,true);
		$module->setPortal($portal);
		$module->getSituation();
	}
	
	function getCommunityToolAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		@$is_submitted = DevblocksPlatform::importGPC($_POST['is_submitted'],'integer',0);
		
		if(!empty($is_submitted))
			$is_submitted = time();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
	
		$tpl->assign('portal', $portal);
		$tpl->assign('is_submitted', $is_submitted);
		
		if(null != ($instance = DAO_CommunityTool::getByCode($portal))) {
			$tpl->assign('instance', $instance);
			$manifest = DevblocksPlatform::getExtension($instance->extension_id, false, true);
            if(null != ($tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
				$tool->setPortal($portal); // [TODO] Kinda hacky
        		$tpl->assign('tool', $tool);
            }
		}
        
        // Community Record
        $community_id = $instance->community_id;
        $community = DAO_Community::get($community_id);
        $tpl->assign('community', $community);
		
        // Install
        $url_writer = DevblocksPlatform::getUrlService();
        $url = $url_writer->write('c=portal&a='.$portal,true);
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
        
		$tpl->display('file:' . $tpl_path . 'community/config/tab/tool_config.tpl');
	}
	
	function saveCommunityToolAction() {
		@$code = DevblocksPlatform::importGPC($_POST['portal'],'string');
		@$name = DevblocksPlatform::importGPC($_POST['portal_name'],'string','');
        @$iDelete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		if(DEMO_MODE) {
			if($iDelete) {
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','communities')));
			} else {
				self::getCommunityToolAction();
			}
			return;
		}

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
				$tool->setPortal($code);
				
				// Any global updates?
				if(null != ($dao_tool = DAO_CommunityTool::getByCode($code))) {
					
					// Update the tool name if it has changed
					if(0 != strcmp($dao_tool->name,$name))
						DAO_CommunityTool::update($dao_tool->id, array(
							DAO_CommunityTool::NAME => $name
						));
				}
				
				// Defer the rest to tool instances and extensions
				$tool->saveConfiguration();
			}
		}
		
		self::getCommunityToolAction();
	}
};

