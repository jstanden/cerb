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

class UmCorePlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class UmTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return realpath(dirname(__FILE__) . '/strings.xml');
		}
	};
endif;

class UmPortalController extends DevblocksControllerExtension {
    const ID = 'usermeet.controller.portal';
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
        if(null != (@$tool = $this->hash[$code])) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id);
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
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id);
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
		$tpl_path = realpath(dirname(__FILE__) . '/templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

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
	    
	    $tpl->assign('community_addons', $community_addons);
		
		$tpl->display('file:' . $tpl_path . 'community/config/tab/index.tpl');
	}
	
	function getCommunityAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = realpath(dirname(__FILE__) . '/templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		if(!empty($id)) {
			$community = DAO_Community::get($id);
			$tpl->assign('community', $community);
		}
		
	    // Tool Manifests
	    $tools = DevblocksPlatform::getExtensions('usermeet.tool', false);
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
		
		if(null != ($instance = DAO_CommunityTool::getByCode($portal))) {
			$manifest = DevblocksPlatform::getExtension($instance->extension_id);
            if(null != ($tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
				$tool->setPortal($portal); // [TODO] Kinda hacky
				if(method_exists($tool,'getSituation'))
					$tool->getSituation();
            }
		}
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
		$tpl_path = realpath(dirname(__FILE__) . '/templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
	
		$tpl->assign('portal', $portal);
		$tpl->assign('is_submitted', $is_submitted);
		
		if(null != ($instance = DAO_CommunityTool::getByCode($portal))) {
			$tpl->assign('instance', $instance);
			$manifest = DevblocksPlatform::getExtension($instance->extension_id);
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
				$manifest = DevblocksPlatform::getExtension($instance->extension_id);
	            $tool = $manifest->createInstance(); /* @var $tool Extension_UsermeetTool */
				$tool->setPortal($code); // [TODO] Kinda hacky
				$tool->saveConfiguration();
			}
		}
		
		self::getCommunityToolAction();
	}
};

class UmContactApp extends Extension_UsermeetTool {
	const PARAM_DISPATCH = 'dispatch';
	const PARAM_LOGO_URL = 'logo_url';
	const PARAM_THEME_URL = 'theme_url';
	const PARAM_PAGE_TITLE = 'page_title';
	const PARAM_CAPTCHA_ENABLED = 'captcha_enabled';
	const SESSION_CAPTCHA = 'write_captcha';
	
    function __construct($manifest) {
        parent::__construct($manifest);
        
        $filepath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        
//        DevblocksPlatform::registerClasses('Text/CAPTCHA.php',array(
//        	'Text_CAPTCHA',
//        ));
    }
    
	public function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
        $umsession = $this->getSession();
		$stack = $response->path;
		
		$logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
		$page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Contact Us');
		$tpl->assign('page_title', $page_title);
        
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);
		
		// Usermeet Session
		if(null == ($fingerprint = parent::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);

		switch(array_shift($stack)) {
			case 'captcha':
                header('Cache-control: max-age=0', true); // 1 wk // , must-revalidate
                header('Expires: ' . gmdate('D, d M Y H:i:s',time()-604800) . ' GMT'); // 1 wk
				header('Content-type: image/jpeg');
                //header('Content-length: '. count($jpg));

//		        // Get CAPTCHA secret passphrase
				$phrase = CerberusApplication::generatePassword(4);
		        $umsession->setProperty(self::SESSION_CAPTCHA, $phrase);
                
				$im = @imagecreate(150, 80) or die("Cannot Initialize new GD image stream");
				$background_color = imagecolorallocate($im, 0, 0, 0);
				$text_color = imagecolorallocate($im, 255, 255, 255); //233, 14, 91
				$font = DEVBLOCKS_PATH . 'resources/font/ryanlerch_-_Tuffy_Bold(2).ttf';
				imagettftext($im, 24, mt_rand(0,20), 5, 60+6, $text_color, $font, $phrase);
//				$im = imagerotate($im, mt_rand(-20,20), $background_color);
				imagejpeg($im,null,85);
				imagedestroy($im);
				exit;
				break;
			
		    	default:
				case 'write':
		    	$response = array_shift($stack);
		    	switch($response) {
		    		case 'confirm':
		    			$tpl->assign('last_opened',$umsession->getProperty('support.write.last_opened',''));
		    			$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/contact/write/confirm.tpl');
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
				        		$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/contact/write/step1.tpl');
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
				        		$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/contact/write/step2.tpl');
				        		break;
				        		
				        	case 'step3':
				        		$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/contact/write/step3.tpl');
				        		break;
				        }
				        break;
		    	}
		    	break;
		}
	}
	
	function doStep2Action() {
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();
		
		@$sNature = DevblocksPlatform::importGPC($_POST['nature'],'string','');

		$umsession->setProperty('support.write.last_nature', $sNature);
		$umsession->setProperty('support.write.last_content', null);
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
		
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		
		if(empty($sFrom) || ($captcha_enabled && 0 != strcasecmp($sCaptcha,@$umsession->getProperty(self::SESSION_CAPTCHA,'***')))) {
			
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
        		$subject = 'Contact me: ' . strip_tags($k);
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

		//$message->body = 'IP: ' . $fingerprint['ip'] . "\r\n\r\n" . $sContent;
		$message->body = $sContent;

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
        
        $page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Contact Us');
		$tpl->assign('page_title', $page_title);
        
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);
        
        $tpl->display("file:${tpl_path}portal/contact/config/index.tpl");
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
        
        $tpl->display("file:${tpl_path}portal/contact/config/add_situation.tpl");
		exit;
    }
    
    public function saveConfiguration() {
        @$sLogoUrl = DevblocksPlatform::importGPC($_POST['logo_url'],'string','');
        @$sPageTitle = DevblocksPlatform::importGPC($_POST['page_title'],'string','Contact Us');
        @$iCaptcha = DevblocksPlatform::importGPC($_POST['captcha_enabled'],'integer',1);
//        @$sThemeUrl = DevblocksPlatform::importGPC($_POST['theme_url'],'string','');

        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_LOGO_URL, $sLogoUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_PAGE_TITLE, $sPageTitle);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, $iCaptcha);
//        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_THEME_URL, $sThemeUrl);
        
        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        
    	@$arDeleteSituations = DevblocksPlatform::importGPC($_POST['delete_situations'],'array',array());
    	
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

        // Nuke a record we're replacing or any checked boxes
        foreach($dispatch as $d_reason => $d_params) {
        	if(!empty($sEditReason) && md5($d_reason)==$sEditReason) {
        		unset($dispatch[$d_reason]);
        	} elseif(!empty($arDeleteSituations) && false !== array_search(md5($d_reason),$arDeleteSituations)) {
        		unset($dispatch[$d_reason]);
        	}
        }
                
       	// If we have new data, add it
        if(!empty($sReason) && !empty($sTo) && false === array_search(md5($sReason),$arDeleteSituations)) {
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

class UmKbApp extends Extension_UsermeetTool {
	const PARAM_BASE_URL = 'base_url';
	const PARAM_LOGO_URL = 'logo_url';
	const PARAM_THEME_URL = 'theme_url';
	const PARAM_PAGE_TITLE = 'page_title';
	const PARAM_CAPTCHA_ENABLED = 'captcha_enabled';
	const PARAM_KB_ROOTS = 'kb_roots';
	
	const SESSION_CAPTCHA = 'write_captcha';
	const SESSION_ARTICLE_LIST = 'kb_article_list';
	
    function __construct($manifest) {
        parent::__construct($manifest);
        $filepath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
    }
    
    private function _writeArticlesAsRss($articles, $title) {
		$url_writer = DevblocksPlatform::getUrlService();
	
		$aFeed = array(
			'title' => $title,
	    	'link' => $url_writer->write('', true),
			'charset' => 'utf-8',
			'entries' => array(),
		);
		
		foreach($articles as $article_id => $article) {
			$summary = substr(strip_tags($article->content),0,255) . (strlen($article->content)>255?'...':'');
			
			$aEntry = array(
				'title' => utf8_encode($article->title),
				'link' => $url_writer->write('c=article&id='.$article_id, true),
				'lastUpdate' => $article->updated,
				'published' => $article->updated,
				'guid' => md5($article->title.$article->updated),
				'description' => utf8_encode($summary),
				'content' => utf8_encode($article->content),
			);
			$aFeed['entries'][] = $aEntry;
		}
		unset($articles);
	
		$rssFeed = Zend_Feed::importArray($aFeed, 'rss');
		
		echo $rssFeed->saveXML();				
    }
    
	public function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('tpl_path', dirname(__FILE__) . '/templates/');
		
        $umsession = $this->getSession();
		$stack = $response->path;
		
		$logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
		$page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Knowledgebase');
		$tpl->assign('page_title', $page_title);
        
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

		// KB Roots
		$sKbRoots = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_KB_ROOTS, '');
        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		
		$kb_roots_str = '0';
		if(!empty($kb_roots))
			$kb_roots_str = implode(',', array_keys($kb_roots)); 
        
		// Usermeet Session
		if(null == ($fingerprint = parent::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);

		switch(array_shift($stack)) {
			case 'rss':
				header("Content-type: application/rss+xml");
				
				switch(array_shift($stack)) {
					case 'recent_changes':
						list($results, $null) = DAO_KbArticle::search(
							array(
								new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
							),
							25,
							0,
							SearchFields_KbArticle::UPDATED,
							false,
							false
						);
						
						if(is_array($results) && !empty($results))
						$full_articles = DAO_KbArticle::getWhere(sprintf("%s IN (%s)",
							DAO_KbArticle::ID,
							implode(',', array_keys($results))
						));
						
						$order= array_keys($results);
						$articles = array();
						foreach($order as $id) {
							$articles[$id] =& $full_articles[$id];
						}
						
						$this->_writeArticlesAsRss($articles, $page_title);
						
						break;
						
					case 'most_popular':
						list($results, $null) = DAO_KbArticle::search(
							array(
								new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
							),
							25,
							0,
							SearchFields_KbArticle::VIEWS,
							false,
							false
						);
						
						if(is_array($results) && !empty($results))
						$full_articles = DAO_KbArticle::getWhere(sprintf("%s IN (%s)",
							DAO_KbArticle::ID,
							implode(',', array_keys($results))
						));

						$order= array_keys($results);
						$articles = array();
						foreach($order as $id) {
							$articles[$id] =& $full_articles[$id];
						}
						
						$this->_writeArticlesAsRss($articles, $page_title);
													
						break;
					
					case 'search':
						$query = rawurldecode(array_shift($stack));
						
						list($articles, $count) = DAO_KbArticle::search(
						 	array(
						 		array(
						 			DevblocksSearchCriteria::GROUP_OR,
						 			new DevblocksSearchCriteria(SearchFields_KbArticle::TITLE,'fulltext',$query),
						 			new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,'fulltext',$query),
						 		),
						 		new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
						 	),
						 	50,
						 	0,
						 	null,
						 	null,
						 	true
						);
						
						if(!empty($articles)) {
							$articles = DAO_KbArticle::getWhere(sprintf("%s IN (%s)",
								DAO_KbArticle::ID,
								implode(',', array_keys($articles))
							));
						}
						
						$this->_writeArticlesAsRss($articles, $page_title);
						
						break;
					
//					case 'article':
//						$id = intval(array_shift($stack));
//		
//						// [TODO] Convert to KB categories
//						$articles = DAO_KbArticle::getWhere(sprintf("%s = %d AND %s = '%s'",
//							DAO_KbArticle::ID,
//							$id,
//							DAO_KbArticle::TITLE,
//							$this->getPortal()
//						));
//
//						$this->_writeArticlesAsRss($articles, $page_title);
//						
//						break;
				}
				
				break;
				
			case 'search':
				@$query = urldecode(array_shift($stack));
				
				$session = $this->getSession();
				
				if(!empty($query)) {
					$session->setProperty('last_query', $query);
				} else {
					$query = $session->getProperty('last_query', '');
				}

				list($articles, $count) = DAO_KbArticle::search(
				 	array(
				 		array(
				 			DevblocksSearchCriteria::GROUP_OR,
				 			new DevblocksSearchCriteria(SearchFields_KbArticle::TITLE,'fulltext',$query),
				 			new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,'fulltext',$query),
				 		),
				 		new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
				 	),
				 	100,
				 	0,
				 	null,
				 	null,
				 	true
				);
				
				$tpl->assign('query', $query);
				$tpl->assign('articles', $articles);
				$tpl->assign('count', $count);

				$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/kb/search.tpl');
				break;
			
//			case 'import':
//				if(empty($editor))
//					break;
//				
//				$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/kb/public_config/import.tpl');
//				break;
				
			case 'article':
				// If no roots are enabled, no articles are visible
				if(empty($kb_roots))
					return;
				
				$id = intval(array_shift($stack));

				list($articles, $count) = DAO_KbArticle::search(
					array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::ID,'=',$id),
						new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots))
					),
					-1,
					0,
					null,
					null,
					false
				);
				
				if(!isset($articles[$id]))
					break;
				
				$article = DAO_KbArticle::get($id);
				$tpl->assign('article', $article);

				@$article_list = $umsession->getProperty(self::SESSION_ARTICLE_LIST, array());
				if(!empty($article) && !isset($article_list[$id])) {
					DAO_KbArticle::update($article->id, array(
						DAO_KbArticle::VIEWS => ++$article->views
					));
					$article_list[$id] = $id;
					$umsession->setProperty(self::SESSION_ARTICLE_LIST, $article_list);
				}

				$categories = DAO_KbCategory::getWhere();
				$tpl->assign('categories', $categories);
				
				$cats = DAO_KbArticle::getCategoriesByArticleId($id);

				$breadcrumbs = array();
				foreach($cats as $cat_id) {
					if(!isset($breadcrumbs[$cat_id]))
						$breadcrumbs[$cat_id] = array();
					$pid = $cat_id;
					while($pid) {
						$breadcrumbs[$cat_id][] = $pid;
						$pid = $categories[$pid]->parent_id;
					}
					$breadcrumbs[$cat_id] = array_reverse($breadcrumbs[$cat_id]);
					
					// Remove any breadcrumbs not in this SC profile
					$pid = reset($breadcrumbs[$cat_id]);
					if(!isset($kb_roots[$pid]))
						unset($breadcrumbs[$cat_id]);
					
				}
				
				$tpl->assign('breadcrumbs',$breadcrumbs);
				
		    	$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/kb/article.tpl');
				break;
			
			case 'browse':
	    	default:
				// [TODO] Root
				@$root = intval(array_shift($stack));
				$tpl->assign('root_id', $root);
					
				$categories = DAO_KbCategory::getWhere();
				$tpl->assign('categories', $categories);
				
				$tree_map = DAO_KbCategory::getTreeMap(0);
				
				// Remove other top-level categories
				if(is_array($tree_map[0]))
				foreach($tree_map[0] as $child_id => $count) {
					if(!isset($kb_roots[$child_id]))
						unset($tree_map[0][$child_id]);
				}
				
				// Remove empty categories
				if(is_array($tree_map[0]))
				foreach($tree_map as $node_id => $children) {
					foreach($children as $child_id => $count) {
						if(empty($count)) {
							@$pid = $categories[$child_id]->parent_id;
							unset($tree_map[$pid][$child_id]);
							unset($tree_map[$child_id]);
						}
					}
				}
				
				$tpl->assign('tree', $tree_map);
				
				// Breadcrumb // [TODO] API-ize inside Model_KbTree ?
				$breadcrumb = array();
				$pid = $root;
				while(0 != $pid) {
					$breadcrumb[] = $pid;
					$pid = $categories[$pid]->parent_id;
				}
				$tpl->assign('breadcrumb',array_reverse($breadcrumb));
				
				$tpl->assign('mid', @intval(ceil(count($tree_map[$root])/2)));
				
				// Articles
				$articles = array();
				
				if(!empty($root)) {
					list($articles, $count) = DAO_KbArticle::search(
						array(
							new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root),
							new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots))
						),
						-1,
						0,
						null,
						null,
						false
					);
		    		$tpl->assign('articles', $articles);
				}
	    		
   				$tpl->display('file:' . dirname(__FILE__) . '/templates/portal/kb/index.tpl');
		    	break;
		}
	}
	
	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure(Model_CommunityTool $instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        $tpl->assign('config_path', $tpl_path);
        
        $base_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_BASE_URL, '');
		$tpl->assign('base_url', $base_url);
        
        $logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
        $page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Knowledgebase');
		$tpl->assign('page_title', $page_title);
        
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);
        
		// Roots
		$tree_map = DAO_KbCategory::getTreeMap();
		$tpl->assign('tree_map', $tree_map);
		
		$levels = DAO_KbCategory::getTree(0);
		$tpl->assign('levels', $levels);
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$sKbRoots = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_KB_ROOTS, '');
        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
        $tpl->assign('kb_roots', $kb_roots);
		
        $tpl->display("file:${tpl_path}portal/kb/config/index.tpl");
    }
    
    public function saveConfiguration() {
        @$sBaseUrl = DevblocksPlatform::importGPC($_POST['base_url'],'string','');
        @$sLogoUrl = DevblocksPlatform::importGPC($_POST['logo_url'],'string','');
        @$sPageTitle = DevblocksPlatform::importGPC($_POST['page_title'],'string','Contact Us');
        @$iCaptcha = DevblocksPlatform::importGPC($_POST['captcha_enabled'],'integer',1);
//        @$sThemeUrl = DevblocksPlatform::importGPC($_POST['theme_url'],'string','');

        // Sanitize (add trailing slash)
        $sBaseUrl = rtrim($sBaseUrl,'/ ') . '/';
        
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_BASE_URL, $sBaseUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_LOGO_URL, $sLogoUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_PAGE_TITLE, $sPageTitle);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, $iCaptcha);
//        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_THEME_URL, $sThemeUrl);

        // KB
        @$aKbRoots = DevblocksPlatform::importGPC($_POST['category_ids'],'array',array());
        $aKbRoots = array_flip($aKbRoots);
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_KB_ROOTS, serialize($aKbRoots));
    }
    
    public function doSearchAction() {
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		@$match = DevblocksPlatform::importGPC($_REQUEST['match'],'string','');
    	
		$session = $this->getSession();
		$session->setProperty('last_query', $query);
		$session->setProperty('last_query_type', $match);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'search')));
    }
    
//    public function doImportAction() {
//    	@$import_file = $_FILES['import_file'];
//    	
//    	if(!empty($import_file)) {
//    		$xmlstr = file_get_contents($import_file['tmp_name']);
//    		
//    		// [TODO] Parse XML
//			$xml = new SimpleXMLElement($xmlstr);
//			
//			foreach($xml->articles->article AS $article) {
//				$title = (string) $article->title;
//				$content = (string) $article->content;
//
//				$fields = array(
//						DAO_KbArticle::CODE => $this->getPortal(),
//						DAO_KbArticle::TITLE => $title,
//						DAO_KbArticle::CONTENT => $content,
//				);
//				$id = DAO_KbArticle::create($fields);
    
};

?>
