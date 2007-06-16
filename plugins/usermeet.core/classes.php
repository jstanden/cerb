<?php
class UmPortalController extends DevblocksControllerExtension {
    const ID = 'usermeet.controller.portal';
//    private $apps = array();
//    private $uri_map = array();
	private $tools = array();
	private $hash = array();
    
	function __construct($manifest) {
		parent::__construct($manifest);

		$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		
		// Classes
		DevblocksPlatform::registerClasses($path. 'api/Extension.php', array(
		    'Extension_UsermeetTool'
		));
		    
	    // Routing
	    $router = DevblocksPlatform::getRoutingService();
	    $router->addRoute('portal', self::ID);
	    
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

        if(null != ($tool = $this->hash[$code])) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id);
            $tool = $manifest->createInstance(); /* @var $app Extension_UsermeetTool */
	        return $tool->handleRequest(new DevblocksHttpRequest($stack));
        } else {
            die("Tool not found.");
        }
	}
	
	/**
	 * @param DevblocksHttpResponse $response
	 */
	function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;

		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

        if(null != ($tool = $this->hash[$code])) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id);
            $tool = $manifest->createInstance(); /* @var $app Extension_UsermeetTool */
	        $tool->writeResponse(new DevblocksHttpResponse($stack));
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

class UmCommunityPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);

		$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		
		DevblocksPlatform::registerClasses($path. 'api/DAO.php', array(
		    'DAO_CommunityTool'
		));
		DevblocksPlatform::registerClasses($path. 'api/Model.php', array(
		    'Model_CommunityTool'
		));
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		$host = $_SERVER['HTTP_HOST'];
		$tpl->assign('host', $host);
		
		array_shift($stack); // community
		
		switch(array_shift($stack)) {
		    case 'add_tool':
		        $communities = DAO_Community::getList();
			    $tpl->assign('communities', $communities);
		        
			    // Tool Manifests
			    $tools = DevblocksPlatform::getExtensions('usermeet.tool', false);
			    $tpl->assign('tool_manifests', $tools);
			    
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/community/add_tool.tpl.php');
		        break;
		        
		    case 'add_widget':
		        $communities = DAO_Community::getList();
			    $tpl->assign('communities', $communities);
			    
			    // Widget Manifests
			    $widgets = DevblocksPlatform::getExtensions('usermeet.widget', false);
			    $tpl->assign('widget_manifests', $widgets);
			    
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/community/add_widget.tpl.php');
		        break;
		    
		    case 'tool':
		        $code = array_shift($stack);

		        // Instance (Tool Fields)
	            // [TODO] This makes templates a bit wacky, convert to a DAO call returning a Model obj?
		        list($instances, $count) = DAO_CommunityTool::search(
		            array(
		                new DevblocksSearchCriteria(SearchFields_CommunityTool::CODE,DevblocksSearchCriteria::OPER_EQ,$code)
		            ),
		            1,
		            0,
		            null,
		            null,
		            false
		        );
		        $instance = array_shift($instances);
		        $tpl->assign('instance', $instance);

		        // Community Record
		        $community_id = $instance[SearchFields_CommunityTool::COMMUNITY_ID];
		        $community = DAO_Community::get($community_id);
		        $tpl->assign('community', $community);
		        
		        // Tool Manifest
		        $toolManifest = DevblocksPlatform::getExtension($instance[SearchFields_CommunityTool::EXTENSION_ID]);
		        $tool = $toolManifest->createInstance();
		        $tpl->assign('tool', $tool);
		        
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/community/tool_config.tpl.php');
		        break;
		    
		    case 'widget':
		        echo "CONFIGURE";
		        break;
		    
		    default:
				// Community sites
			    $communities = DAO_Community::getList();
			    $tpl->assign('communities', $communities);
			    
			    // Community Tools + Widgets (Indexed)
			    $community_addons = array();
		        foreach(array_keys($communities) as $idx) {
		            $community_addons[$idx] = array('tools'=>array(),'widgets'=>array());
		        }
			    
			    $community_tools = DAO_CommunityTool::getList();
			    foreach($community_tools as $tool) {
			        if(!isset($community_addons[$tool->community_id])) continue;
			        $community_addons[$tool->community_id]['tools'][$tool->code] = $tool->extension_id;
			    }
			    
			    // Tool Manifests
			    $tools = DevblocksPlatform::getExtensions('usermeet.tool', false);
			    $tpl->assign('tool_manifests', $tools);
			    
			    // [TODO] Widget Manifests
			    $widgets = DevblocksPlatform::getExtensions('usermeet.widget', false);
			    $tpl->assign('widget_manifests', $widgets);
			    			    
			    $tpl->assign('community_addons', $community_addons);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/community/index.tpl.php');
		        break;
		}
		
	}
	
	function createCommunityAction() {
	    @$name = DevblocksPlatform::importGPC($_POST['name'],'string');	    
	    @$url = DevblocksPlatform::importGPC($_POST['url'],'string');

	    if(empty($name)) return;
	    
	    $fields = array(
	        DAO_Community::NAME => $name,
	        DAO_Community::URL => $url,
	    );
	    $id = DAO_Community::create($fields);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('community')));
	}
	
	function addCommunityToolAction() {
	    @$community_id = DevblocksPlatform::importGPC($_POST['community_id'],'integer');	    
	    @$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string');

	    if(empty($community_id) || empty($extension_id)) return;
	    
	    $fields = array(
	        DAO_CommunityTool::COMMUNITY_ID => $community_id,
	        DAO_CommunityTool::EXTENSION_ID => $extension_id
	    );
	    $id = DAO_CommunityTool::create($fields);
	    
	    $tool = DAO_CommunityTool::get($id);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('community','tool',$tool->code)));
	}
	
};


?>