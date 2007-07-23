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

		// [TODO] Convert to Model_CommunityTool::getByCode()
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

		// [TODO] Convert to Model_CommunityTool::getByCode()
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
	
	// Ajax
	function actionAction() {
    	if(null == ($worker = CerberusApplication::getActiveWorker()))
    		die();
		
    	@$sCode = DevblocksPlatform::importGPC($_REQUEST['code'],'string','');
    	@$sAction = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($instance = DAO_CommunityTool::getByCode($sCode))) {
			$manifest = DevblocksPlatform::getExtension($instance->extension_id);
            $tool = $manifest->createInstance(); /* @var $app Extension_UsermeetTool */
			$tool->setPortal($sCode); // [TODO] Kinda hacky
			
			// Passthrough [TODO] need to secure to agent logins
			if(method_exists($tool, $sAction)) {
				call_user_method($sAction, $tool);
			}
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

				if(null != ($instance = DAO_CommunityTool::getByCode($code))) {
					$tpl->assign('instance', $instance);
					$manifest = DevblocksPlatform::getExtension($instance->extension_id);
		            $tool = $manifest->createInstance(); /* @var $app Extension_UsermeetTool */
					$tool->setPortal($code); // [TODO] Kinda hacky
		        	$tpl->assign('tool', $tool);
				}
		        
		        // Community Record
		        $community_id = $instance->community_id;
		        $community = DAO_Community::get($community_id);
		        $tpl->assign('community', $community);
		        
		        $action = array_shift($stack);
		        switch($action) {
		        	case 'configure': // configure
				        $url_writer = DevblocksPlatform::getUrlService();
				        $url = $url_writer->write('c=portal&a='.$code,true);
				        $url_parts = parse_url($url);
				        
				        $host = $url_parts['host'];
						$base = substr(DEVBLOCKS_WEBPATH,0,-1); // consume trailing
				        $path = substr($url_parts['path'],strlen(DEVBLOCKS_WEBPATH)-1); // consume trailing slash
						
						$tpl->assign('host', $host);
						$tpl->assign('base', $base);
						$tpl->assign('path', $path);
						
						$tpl->display('file:' . dirname(__FILE__) . '/templates/community/tool_config.tpl.php');						
		        		break;
		        		
		        	default:
	        			break;
		        }
		        
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
        @$iFinished = DevblocksPlatform::importGPC($_POST['finished'],'integer',0);
		
		if(null != ($instance = DAO_CommunityTool::getByCode($code))) {
			$manifest = DevblocksPlatform::getExtension($instance->extension_id);
            $tool = $manifest->createInstance(); /* @var $app Extension_UsermeetTool */
			$tool->setPortal($code); // [TODO] Kinda hacky
	        $tool->saveConfiguration();
		}
		
		if($iFinished) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('community')));
		} else {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('community','tool',$code,'configure')));
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
		            'width' => 120,
		            'height' => 75,
		            'output' => 'jpg',
		            'length' => 4,
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
		    	$response = array_shift($stack);
		    	switch($response) {
		    		case 'confirm':
		    			$tpl->assign('last_opened',$umsession->getProperty('support.write.last_opened',''));
		    			$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/write/confirm.tpl.php');
		    			break;
		    		
		    		default:
		    		case 'step1':
		    		case 'step2':
		    		case 'step3':
		    			$sFrom = $umsession->getProperty('support.write.last_from','');
		    			$sNature = $umsession->getProperty('support.write.last_nature','');
		    			$sContent = $umsession->getProperty('support.write.last_content','');
		    			$sError = $umsession->getProperty('support.write.last_error','');
		    			
						$tpl->assign('last_from', $sFrom);
						$tpl->assign('last_nature', $sNature);
						$tpl->assign('last_content', $sContent);
						$tpl->assign('last_error', $sError);
						
        				$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
		    			$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
				        $tpl->assign('dispatch', $dispatch);
				        
				        switch($response) {
				        	default:
				        		$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/write/step1.tpl.php');
				        		break;
				        		
				        	case 'step2':
				        		// Cache along with answers?
								if(is_array($dispatch))
						        foreach($dispatch as $k => $v) {
						        	if(md5($k)==$sNature) {
						        		$umsession->setProperty('support.write.last_nature_string', $k);
						        		$tpl->assign('situation', $k);
						        		$tpl->assign('situation_params', $v);
						        		break;
						        	}
						        }
				        		$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/write/step2.tpl.php');
				        		break;
				        		
				        	case 'step3':
				        		$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/write/step3.tpl.php');
				        		break;
				        }
				        break;
		    	}
		    	break;
		        
		    default:
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/portal/support/index.tpl.php');
		        break;
		}
	}
	
	function doStep2Action() {
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();
		
		@$sNature = DevblocksPlatform::importGPC($_POST['nature'],'string','');

		$umsession->setProperty('support.write.last_nature', $sNature);
		$umsession->setProperty('support.write.last_error', null);
		
		$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
		$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		
		// Check if this nature has followups, if not skip to step3
		$followups = array();
		if(is_array($dispatch))
        foreach($dispatch as $k => $v) {
        	if(md5($k)==$sNature) {
        		$umsession->setProperty('support.write.last_nature_string', $k);
        		@$followups = $v['followups'];
        		break;
        	}
        }

        if(empty($followups)) {		
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'write','step3')));
        } else {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'write','step2')));
        }
	}
	
	function doStep3Action() {
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();

		@$aFollowUpQ = DevblocksPlatform::importGPC($_POST['followup_q'],'array',array());
		@$aFollowUpA = DevblocksPlatform::importGPC($_POST['followup_a'],'array',array());
		$nature = $umsession->getProperty('support.write.last_nature_string','');
		$content = '';
		
		if(!empty($aFollowUpQ)) {
			$content = "Comments:\r\n\r\n\r\n";
			$content .= "--------------------------------------------\r\n";
			if(!empty($nature)) {
				$content .= $nature . "\r\n";
				$content .= "--------------------------------------------\r\n";
			}
			foreach($aFollowUpQ as $idx => $q) {
				$content .= "Q) " . $q . "\r\n" . "A) " . $aFollowUpA[$idx] . "\r\n";
				if($idx+1 < count($aFollowUpQ)) $content .= "\r\n";
			}
			$content .= "--------------------------------------------\r\n";
			"\r\n";
		}
		
		$umsession->setProperty('support.write.last_content', $content);
		$umsession->setProperty('support.write.last_error', null);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'write','step3')));
	}
	
	function doSendMessageAction() {
		@$sFrom = DevblocksPlatform::importGPC($_POST['from'],'string','');
		@$sContent = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$sCaptcha = DevblocksPlatform::importGPC($_POST['captcha'],'string','');
		
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();

        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);

		$umsession->setProperty('support.write.last_from',$sFrom);
		$umsession->setProperty('support.write.last_content',$sContent);
        
		$sNature = $umsession->getProperty('support.write.last_nature', '');
		
		if(empty($sFrom) || 0 != strcasecmp($sCaptcha,@$umsession->getProperty(self::SESSION_CAPTCHA,'***'))) {
			
			if(empty($sFrom)) {
				$umsession->setProperty('support.write.last_error','Invalid e-mail address.');
			} else {
				$umsession->setProperty('support.write.last_error','What you typed did not match the image.');
			}
			
			// [TODO] Need to report the captcha didn't match and redraw the form
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'write','step3')));
			return;
		}

		// Dispatch
		$to = $default_from;
		$subject = 'Contact me: Other';
		
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();

        foreach($dispatch as $k => $v) {
        	if(md5($k)==$sNature) {
        		$to = $v['to'];
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
		$umsession->setProperty('support.write.last_nature_string',null);
		$umsession->setProperty('support.write.last_content',null);
		$umsession->setProperty('support.write.last_error',null);
		$umsession->setProperty('support.write.last_opened',$ticket->mask);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'write','confirm')));
	}
	
	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure(Model_CommunityTool $instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        $tpl->assign('config_path', $tpl_path);
        
        $settings = CerberusSettings::getInstance();
        
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        $tpl->assign('default_from', $default_from);
        
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
        $tpl->assign('dispatch', $dispatch);
        
        $logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
        $tpl->display("file:${tpl_path}portal/support/config/index.tpl.php");
    }
    
    // Ajax
    public function getSituation() {
		@$sCode = DevblocksPlatform::importGPC($_REQUEST['code'],'string','');
		@$sReason = DevblocksPlatform::importGPC($_REQUEST['reason'],'string','');
    	 
    	$tool = DAO_CommunityTool::getByCode($sCode);
    	 
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;

        $settings = CerberusSettings::getInstance();
        
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        $tpl->assign('default_from', $default_from);
        
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
        
        if(is_array($dispatch))
        foreach($dispatch as $reason => $params) {
        	if(md5($reason)==$sReason) {
        		$tpl->assign('situation_reason', $reason);
        		$tpl->assign('situation_params', $params);
        		break;
        	}
        }
        
        $tpl->display("file:${tpl_path}portal/support/config/add_situation.tpl.php");
		exit;
    }
    
    public function saveConfiguration() {
        @$sLogoUrl = DevblocksPlatform::importGPC($_POST['logo_url'],'string','');
//        @$sThemeUrl = DevblocksPlatform::importGPC($_POST['theme_url'],'string','');

        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_LOGO_URL, $sLogoUrl);
//        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_THEME_URL, $sThemeUrl);
        
        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        
    	@$sEditReason = DevblocksPlatform::importGPC($_POST['edit_reason'],'string','');
    	@$sReason = DevblocksPlatform::importGPC($_POST['reason'],'string','');
        @$sTo = DevblocksPlatform::importGPC($_POST['to'],'string','');
        @$aFollowup = DevblocksPlatform::importGPC($_POST['followup'],'array',array());
        @$aFollowupLong = DevblocksPlatform::importGPC($_POST['followup_long'],'array',array());
        
        if(empty($sTo))
        	$sTo = $default_from;
        
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();

        // [JAS]: [TODO] Only needed temporarily to clean up imports
		// [TODO] Move to patch
        if(is_array($dispatch))
        foreach($dispatch as $d_reason => $d_params) {
        	if(!is_array($d_params)) {
        		$dispatch[$d_reason] = array('to'=>$d_params,'followups'=>array());
        	} else {
        		unset($d_params['']);
        	}
        }

        // Nuke a record we're replacing
       	if(!empty($sEditReason)) {
			// will be MD5
	        if(is_array($dispatch))
	        foreach($dispatch as $d_reason => $d_params) {
	        	if(md5($d_reason)==$sEditReason) {
	        		unset($dispatch[$d_reason]);
	        	}
	        }
       	}
        
       	// If we have new data, add it
        if(!empty($sReason) && !empty($sTo)) {
			$dispatch[$sReason] = array(
				'to' => $sTo,
				'followups' => array()
			);
			
			$followups =& $dispatch[$sReason]['followups'];
			
			if(!empty($aFollowup))
			foreach($aFollowup as $idx => $followup) {
				if(empty($followup)) continue;
				$followups[$followup] = (false !== array_search($idx,$aFollowupLong)) ? 1 : 0;
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