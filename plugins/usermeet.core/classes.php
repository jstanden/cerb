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

// Classes
$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
DevblocksPlatform::registerClasses($path. 'api/Extension.php', array(
    'Extension_UsermeetTool'
));
DevblocksPlatform::registerClasses($path. 'api/Model.php', array(
    'Model_CommunityTool'
));
DevblocksPlatform::registerClasses($path. 'api/DAO.php', array(
    'DAO_CommunityTool'
));

class UmPortalController extends DevblocksControllerExtension {
    const ID = 'usermeet.controller.portal';
//    private $apps = array();
//    private $uri_map = array();
	private $tools = array();
	private $hash = array();
    
	function __construct($manifest) {
		parent::__construct($manifest);

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
			$tool->setPortal($code); // [TODO] Kinda hacky
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
			$tool->setPortal($code); // [TODO] Kinda hacky
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

		        $url_writer = DevblocksPlatform::getUrlService();
		        $url = $url_writer->write('c=portal&a='.$code,true);
		        $url_parts = parse_url($url);
		        
		        $host = $url_parts['host'];
				$base = substr(DEVBLOCKS_WEBPATH,0,-1); // consume trailing
		        $path = substr($url_parts['path'],strlen(DEVBLOCKS_WEBPATH)-1); // consume trailing slash
				
				$tpl->assign('host', $host);
				$tpl->assign('base', $base);
				$tpl->assign('path', $path);
		        
		        // Community Record
		        $community_id = $instance[SearchFields_CommunityTool::COMMUNITY_ID];
		        $community = DAO_Community::get($community_id);
		        $tpl->assign('community', $community);
		        
		        // Tool Manifest
		        $toolManifest = DevblocksPlatform::getExtension($instance[SearchFields_CommunityTool::EXTENSION_ID]);
		        $tool = $toolManifest->createInstance();
		        $tool->setPortal($code); // [TODO] Kinda hacky
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
	
	// Facade
	function saveConfigurationAction() {
		@$code = DevblocksPlatform::importGPC($_POST['code'],'string');
		
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
		
        if(null != ($toolManifest = DevblocksPlatform::getExtension($instance[SearchFields_CommunityTool::EXTENSION_ID]))) {
        	$tool = $toolManifest->createInstance(); /* @var $app Extension_UsermeetTool */
			$tool->setPortal($code); // [TODO] Kinda hacky
	        $tool->handleRequest(new DevblocksHttpRequest(array('saveConfiguration')));
        } else {
            echo "Tool not found."; // [TODO] Better error handling
        }
	}
	
	function createCommunityAction() {
	    @$name = DevblocksPlatform::importGPC($_POST['name'],'string');	    

	    if(empty($name)) return;
	    
	    $fields = array(
	        DAO_Community::NAME => $name,
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

class UmSupportApp extends Extension_UsermeetTool {
	const PARAM_DISPATCH = 'dispatch';
	const PARAM_LOGO_URL = 'logo_url';
	const PARAM_THEME_URL = 'theme_url';
	const SESSION_CAPTCHA = 'write_captcha';
	
    function __construct($manifest) {
        parent::__construct($manifest);
        
        $filepath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        
        DevblocksPlatform::registerClasses('Text/CAPTCHA.php',array(
        	'Text_CAPTCHA',
        ));
    }
    
	public function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
        $umsession = $this->getSession();
		$stack = $response->path;
		
		$logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
		
		// Usermeet Session
		if(null == ($fingerprint = parent::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);

		switch(array_shift($stack)) {
			case 'captcha':
				/*
				 * CAPTCHA [TODO] API-ize
				 */
		        $imageOptions = array(
		            'font_size' => 24,
		            'font_path' => DEVBLOCKS_PATH . 'resources/font/',
		            'font_file' => 'ryanlerch_-_Tuffy_Bold(2).ttf'
		        );
		
		        // Set CAPTCHA options
		        $options = array(
		            'width' => 200,
		            'height' => 75,
		            'output' => 'jpg',
		//            'phrase' => $pass,
		            'imageOptions' => $imageOptions
		        );
				
		        // Generate a new Text_CAPTCHA object, Image driver
		        $c = Text_CAPTCHA::factory('Image');
		        $retval = $c->init($options);
		        if (PEAR::isError($retval)) {
		            echo 'Error initializing CAPTCHA!';
		            exit;
		        }
		    
		        // Get CAPTCHA secret passphrase
		        $umsession->setProperty(self::SESSION_CAPTCHA, $c->getPhrase());
		    
		        // Get CAPTCHA image (as PNG)
		        $jpg = $c->getCAPTCHA($options);
		        
		        if (PEAR::isError($jpg)) {
		            echo 'Error generating CAPTCHA!';
		            exit;
		        }
		    	
		        // Headers, don't allow to be cached
                header('Cache-control: max-age=0', true); // 1 wk // , must-revalidate
                header('Expires: ' . gmdate('D, d M Y H:i:s',time()-604800) . ' GMT'); // 1 wk
                header('Content-length: '. count($jpg));
		        echo $jpg;
		        exit;
		        
				break;
			
		    case 'write':
		    	switch(array_shift($stack)) {
		    		case 'confirm':
		    			$tpl->assign('last_opened',$umsession->getProperty('support.write.last_opened',''));
		    			$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/write/confirm.tpl.php');
		    			break;
		    			
		    		default:
		    			$tpl->assign('last_from',$umsession->getProperty('support.write.last_from',''));
						$tpl->assign('last_nature',$umsession->getProperty('support.write.last_nature',''));
						$tpl->assign('last_content',$umsession->getProperty('support.write.last_content',''));
						$tpl->assign('last_error',$umsession->getProperty('support.write.last_error',''));
		    			
        				$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
		    			$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
				        $tpl->assign('dispatch', $dispatch);
				        $tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/write/index.tpl.php');
				        break;
		    	}
		    	break;
		        
		    default:
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/index.tpl.php');
		        break;
		}
	}
	
	function doSendMessageAction() {
		@$sFrom = DevblocksPlatform::importGPC($_POST['from'],'string','');
		@$sNature = DevblocksPlatform::importGPC($_POST['nature'],'string','');
		@$sContent = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$sCaptcha = DevblocksPlatform::importGPC($_POST['captcha'],'string','');
		
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();

        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);

        // [TODO] These could be combined into a single property
		$umsession->setProperty('support.write.last_from',$sFrom);
		$umsession->setProperty('support.write.last_nature',$sNature);
		$umsession->setProperty('support.write.last_content',$sContent);
        
		if(empty($sFrom) || 0 != strcasecmp($sCaptcha,@$umsession->getProperty(self::SESSION_CAPTCHA,'***'))) {
			
			if(empty($sFrom)) {
				$umsession->setProperty('support.write.last_error','Invalid e-mail address.');
			} else {
				$umsession->setProperty('support.write.last_error','What you typed did not match the image.');
			}
			
			// [TODO] Need to report the captcha didn't match and redraw the form
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'write')));
			return;
		}

		// Dispatch
		$to = $default_from;
		$subject = 'Contact me: Other';
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();

        foreach($dispatch as $k => $v) {
        	if(md5($k)==$sNature) {
        		$to = $v;
        		$subject = 'Contact me: ' . $k;
        		break;
        	}
        }
		
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $to;
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		$message->headers['x-cerberus-portal'] = 1; 
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist($sFrom,'');
		if(empty($fromList) || !is_array($fromList)) {
			return; // abort with message
		}
		$from = array_shift($fromList);
		$message->headers['from'] = $from->mailbox . '@' . $from->host; 

		$message->body = 'IP: ' . $fingerprint['ip'] . "\r\n\r\n" . $sContent;

		$ticket_id = CerberusParser::parseMessage($message);
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
//		echo "Created Ticket ID: $ticket_id<br>";
		// [TODO] Could set this ID/mask into the UMsession

		// Clear any errors
		$umsession->setProperty('support.write.last_nature',null);
		$umsession->setProperty('support.write.last_content',null);
		$umsession->setProperty('support.write.last_error',null);
		$umsession->setProperty('support.write.last_opened',$ticket->mask);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'write','confirm')));
	}
	
	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure($instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        
        $settings = CerberusSettings::getInstance();
        
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        $tpl->assign('default_from', $default_from);
        
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
        $tpl->assign('dispatch', $dispatch);
        
        $logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
        $tpl->display("file:${tpl_path}portal/support/config.tpl.php");
    }
    
    public function saveConfigurationAction() {
        @$sLogoUrl = DevblocksPlatform::importGPC($_POST['logo_url'],'string','');
        @$sThemeUrl = DevblocksPlatform::importGPC($_POST['theme_url'],'string','');
    	    	
    	@$aReason = DevblocksPlatform::importGPC($_POST['reason'],'array',array());
        @$aTo = DevblocksPlatform::importGPC($_POST['to'],'array',array());

        $settings = CerberusSettings::getInstance();
        
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_LOGO_URL, $sLogoUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_THEME_URL, $sThemeUrl);
        
        $dispatch = array();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);

        if(is_array($aReason) && is_array($aTo))
        foreach($aReason as $idx => $reason) {
        	if(!empty($reason)) {
        		$to = !empty($aTo[$idx]) ? $aTo[$idx] : $default_from;
        		$dispatch[$reason] = $to;
        	}
        }
        
        ksort($dispatch);
        
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_DISPATCH, serialize($dispatch));
    }
	
};

class UmCorePlugin extends DevblocksPlugin {
	function install(DevblocksPluginManifest $manifest) {
		/*
		 * [IMPORTANT -- Yes, this is simply a line in the sand.]
		 * You're welcome to modify the code to meet your needs, but please respect 
		 * our licensing.  Buy a legitimate copy to help support the project!
		 * http://www.cerberusweb.com/
		 */
		$license = CerberusLicense::getInstance();
		
		if(CerberusHelper::is_class($manifest) && @isset($license['features'][$manifest->name])) {
			return TRUE;
		}
		
		return FALSE;
	}
};

?>