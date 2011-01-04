<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChSignInPage extends CerberusPageExtension {
	// [TODO] Move this to the default login handler
    const KEY_FORGOT_EMAIL = 'login.recover.email';
    const KEY_FORGOT_SENTCODE = 'login.recover.sentcode';
    const KEY_FORGOT_CODE = 'login.recover.code';
    
	function isVisible() {
		return true;
	}
	
	function getLoginProvider() {
		@$extension_id = DevblocksPlatform::importGPC($_COOKIE['login_extension_id'],'string','login.default');

		if(!empty($extension_id) && null != ($extension = DevblocksPlatform::getExtension($extension_id, true, true))) {
			return $extension;
		} else {
			return DevblocksPlatform::getExtension('login.default', true);
		}
	}
	
	function providerAction() {
	    $request = DevblocksPlatform::getHttpRequest();
	    $stack = $request->path;
	    @array_shift($stack); // login
	    @array_shift($stack); // provider
        @$extension_id = array_shift($stack);
		
        if(!empty($extension_id) && null != ($ext = DevblocksPlatform::getExtension($extension_id, true, true)) 
        	&& $ext instanceof Extension_LoginAuthenticator) {
        		setcookie('login_extension_id', $ext->manifest->id, strtotime('+1 year'), '/');
        } else {
        	setcookie('login_extension_id', 'login.default', strtotime('+1 year'), '/');
        }
        
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	}
	
	function render() {
	    $response = DevblocksPlatform::getHttpResponse();
	    $stack = $response->path;
	    array_shift($stack); // login
        $section = array_shift($stack);
        
        switch($section) {
        	// [TODO] Move this to the default login handler
            case "forgot":
                $step = array_shift($stack);
                $tpl = DevblocksPlatform::getTemplateService();
                
                switch($step) {
                    default:
                    case "step1":
                    	if ((@$failed = array_shift($stack)) == "failed") {
                    		$tpl->assign('failed',true);
                    	}
                        $tpl->display('devblocks:cerberusweb.core::login/forgot1.tpl');
                        break;
                    
                    case "step2":
                        $tpl->display('devblocks:cerberusweb.core::login/forgot2.tpl');
                        break;
                        
                    case "step3":
                        $tpl->display('devblocks:cerberusweb.core::login/forgot3.tpl');
                        break;
                }
                
                break;
                
            default:
				$inst = $this->getLoginProvider();

				$tpl = DevblocksPlatform::getTemplateService();
            	$login_extensions = DevblocksPlatform::getExtensions('cerberusweb.login', false, true);
            	unset($login_extensions[$inst->id]); 
            	$tpl->assign('login_extensions', $login_extensions);
            	
				$inst->renderLoginForm();
                break;
        }
	}
	
	function showAction() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
	}
	
	// [TODO] Still needed?
	function delegateAction() {
	    $request = DevblocksPlatform::getHttpRequest();
	    $stack = $request->path;
	    array_shift($stack); // login
	    array_shift($stack); // delegate
		
		if(null == (@$action = array_shift($stack)))
			exit;

		if(null != ($inst = $this->getLoginProvider())) {
			if(method_exists($inst,$action.'Action')) {
				call_user_func(array($inst,$action.'Action'));
			}
		}
		
		exit;
	}
	
	// POST
	function authenticateAction() {
		@$redirect_path = explode('/',DevblocksPlatform::importGPC($_POST['original_path']));

		$inst = $this->getLoginProvider(); /* @var $inst Extension_LoginAuthenticator */

		$url_service = DevblocksPlatform::getUrlService();
		
		$honesty = CerberusLicense::getInstance();
		
		if($inst->authenticate()) {
			if(!is_array($redirect_path) || empty($redirect_path) || empty($redirect_path[0]))
				$redirect_path = array();
			
			// Only valid pages
			if(is_array($redirect_path) && !empty($redirect_path)) {
				$redirect_uri = current($redirect_path);
				if(!CerberusApplication::getPageManifestByUri($redirect_uri))
					$redirect_path = array();
			}
				
			$devblocks_response = new DevblocksHttpResponse($redirect_path);
			$worker = CerberusApplication::getActiveWorker();
			
			// Please be honest
			$online_workers = DAO_Worker::getAllOnline(86400, 100);
			if(!isset($online_workers[$worker->id]) && $honesty->w <= count($online_workers) && 100 > $honesty->w) {
				$online_workers = DAO_Worker::getAllOnline(600, 1);
				if($honesty->w <= count($online_workers)) {
					$most_idle_worker =  end($online_workers);
					$session = DevblocksPlatform::getSessionService();
					$session->clear();
					$time = 600 - max(0,time()-$most_idle_worker->last_activity_date);
					DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','too_many',$time)));
					exit;
				}
			}
			
			// Timezone
			if(null != ($timezone = DAO_WorkerPref::get($worker->id,'timezone'))) {
				$_SESSION['timezone'] = $timezone;
				@date_default_timezone_set($timezone);
			}
			
			// Language
			if(null != ($lang_code = DAO_WorkerPref::get($worker->id,'locale'))) {
				$_SESSION['locale'] = $lang_code;
				DevblocksPlatform::setLocale($lang_code);
			}
			
			// Flush views
			DAO_WorkerViewModel::flush($worker->id);
			
			if(empty($devblocks_response->path)) {
				$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
				$next_page = ($tour_enabled) ?  'welcome' : 'home';				
				$devblocks_response = new DevblocksHttpResponse(array($next_page));
			}
			
		} else {
			//authentication failed
			$devblocks_response = new DevblocksHttpResponse(array('login','failed'));
		}
		
		DevblocksPlatform::redirect($devblocks_response);
	}
	
	function signoutAction() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		// [TODO] This also needs to invoke a signout on the login auth extension
		
		DAO_Worker::logActivity(new Model_Activity(null));
		
		$session->clear();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	}
	
	// Post
	function doRecoverStep1Action() {
		$translate = DevblocksPlatform::getTranslationService();
		
	    @$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string');
	    
	    $worker = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($worker))
	        return;
	    
	    $_SESSION[self::KEY_FORGOT_EMAIL] = $email;
	    
	    try {
		    $mail_service = DevblocksPlatform::getMailService();
		    $mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			$mail = $mail_service->createMessage();
		    
		    $code = CerberusApplication::generatePassword(10);
		    
		    $_SESSION[self::KEY_FORGOT_SENTCODE] = $code;
		    $settings = DevblocksPlatform::getPluginSettingsService();
			$from = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM,CerberusSettingsDefaults::DEFAULT_REPLY_FROM);
		    $personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL,CerberusSettingsDefaults::DEFAULT_REPLY_PERSONAL);
			
			// Headers
			$mail->setTo(array($email));
			$mail->setFrom(array($from => $personal));
			$mail->setSubject($translate->_('signin.forgot.mail.subject'));
			$mail->generateId();
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk ' . APP_VERSION . ' (Build '.APP_BUILD.')');
	
			$mail->setBody(vsprintf($translate->_('signin.forgot.mail.body'), $code));
			
			if(!$mailer->send($mail)) {
				throw new Exception('Password Forgot confirmation email failed to send.');
			}
	    } catch (Exception $e) {
	    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step1','failed')));
	    }
	    
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	}
	
	// Post
	function doRecoverStep2Action() {
        @$code = DevblocksPlatform::importGPC($_REQUEST['code'],'string');

        $email = $_SESSION[self::KEY_FORGOT_EMAIL];
        $sentcode = $_SESSION[self::KEY_FORGOT_SENTCODE];
        $_SESSION[self::KEY_FORGOT_CODE] = $code;
        
	    $worker_id = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($worker_id) || empty($code))
	        return;
        
	    if(0 == strcmp($sentcode,$code)) { // passed
            DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step3')));	        
	    } else {
            DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	    }
	}
	
	// Post
	function doRecoverStep3Action() {
        @$password = DevblocksPlatform::importGPC($_REQUEST['password'],'string');

        $email = $_SESSION[self::KEY_FORGOT_EMAIL];
        $sentcode = $_SESSION[self::KEY_FORGOT_SENTCODE];
        $code = $_SESSION[self::KEY_FORGOT_CODE];
        
	    $worker_id = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($code) || empty($worker_id))
	        return;
        
	    if(0 == strcmp($sentcode,$code)) { // passed
	        DAO_Worker::update($worker_id, array(
	            DAO_Worker::PASSWORD => md5($password)
	        ));
	        
            unset($_SESSION[self::KEY_FORGOT_EMAIL]);
            unset($_SESSION[self::KEY_FORGOT_CODE]);
            unset($_SESSION[self::KEY_FORGOT_SENTCODE]);
            
            DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	    } else {
	        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	    }
        
	}
};
