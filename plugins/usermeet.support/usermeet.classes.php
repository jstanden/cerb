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
class UmSupportApp extends Extension_UsermeetTool {
	const PARAM_DISPATCH = 'dispatch';
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
		    			$tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/support/write/confirm.tpl.php');
		    			break;
		    			
		    		default:
        				$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
		    			$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
				        $tpl->assign('dispatch', $dispatch);
				        $tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/support/write/index.tpl.php');
				        break;
		    	}
		    	break;
		        
		    default:
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/support/index.tpl.php');
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
		
		if(0 != strcasecmp($sCaptcha,@$umsession->getProperty(self::SESSION_CAPTCHA,'***'))) {
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
		
//		echo "Created Ticket ID: $ticket_id<br>";
		// [TODO] Could set this ID/mask into the UMsession

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
        
        $tpl->display("file:${tpl_path}usermeet/support/config.tpl.php");
    }
    
    public function saveConfigurationAction() {
        @$aReason = DevblocksPlatform::importGPC($_POST['reason'],'array',array());
        @$aTo = DevblocksPlatform::importGPC($_POST['to'],'array',array());

        $settings = CerberusSettings::getInstance();
        
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
?>