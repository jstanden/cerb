<?php
class UmPortalController extends DevblocksControllerExtension {
    const ID = 'usermeet.controller.portal';
    private $apps = array();
    private $uri_map = array();
    
	function __construct($manifest) {
		parent::__construct($manifest);

		$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		
		// Classes
		CerberusClassLoader::registerClasses($path. 'api/Extension.php', array(
		    'Extension_UsermeetApp'
		));
		    
	    // Routing
	    $router = DevblocksPlatform::getRoutingService();
	    $router->addRoute('portal', self::ID);
	    
	    // Internal Routing
        $this->apps = DevblocksPlatform::getExtensions('usermeet.app', false);
        foreach($this->apps as $idx => $app) {
            $this->uri_map[$app->params['uri']] =& $this->apps[$idx];
        }
	}
		
	/**
	 * @param DevblocksHttpRequest $request 
	 * @return DevblocksHttpResponse $response 
	 */
	function handleRequest(DevblocksHttpRequest $request) {
	    // [TODO] Pass on a function pointer to *Action()

		$stack = $request->path;

		array_shift($stack); // portal
		$app_uri = array_shift($stack); // forums

        if(null != ($app_manifest = $this->_getAppManifest($app_uri))) {
            // [TODO] Instance ID
	        // [TODO] Don't double instance any apps
            $app = $app_manifest->createInstance(); /* @var $app Extension_UsermeetApp */
	        return $app->handleRequest(new DevblocksHttpRequest($stack));
        } else {
            die("App not found.");
        }
	}
	
	/**
	 * @param DevblocksHttpResponse $response
	 */
	function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;

		array_shift($stack); // portal
		$app_uri = array_shift($stack); // forums

        if(null != ($app_manifest = $this->_getAppManifest($app_uri))) {
            // [TODO] Instance ID
	        // [TODO] Don't double instance any apps
            $app = $app_manifest->createInstance(); /* @var $app Extension_UsermeetApp */
	        $app->writeResponse(new DevblocksHttpResponse($stack));
        } else {
            die("App not found.");
        }
	}
	
	private function _getAppManifest($uri) {
        return $this->uri_map[$uri];
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
		
//		CerberusClassLoader::registerClasses($path. 'api/DAO.php', array(
//		    'DAO_Faq'
//		));
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
		
		// Community sites
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/community/index.tpl.php');
	}
	
};


?>