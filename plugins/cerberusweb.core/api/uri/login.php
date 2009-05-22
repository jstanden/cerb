<?php
class ChSignInPage extends CerberusPageExtension {
    const KEY_FORGOT_EMAIL = 'login.recover.email';
    const KEY_FORGOT_SENTCODE = 'login.recover.sentcode';
    const KEY_FORGOT_CODE = 'login.recover.code';
    
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	function isVisible() {
		return true;
	}
	
	function render() {
	    $response = DevblocksPlatform::getHttpResponse();
	    $stack = $response->path;
	    array_shift($stack); // login
        $section = array_shift($stack);
        
        switch($section) {
            case "forgot":
                $step = array_shift($stack);
                $tpl = DevblocksPlatform::getTemplateService();
                
                switch($step) {
                    default:
                    case "step1":
                    	if ((@$failed = array_shift($stack)) == "failed") {
                    		$tpl->assign('failed',true);
                    	}
                        $tpl->display('file:' . $this->_TPL_PATH . 'login/forgot1.tpl');
                        break;
                    
                    case "step2":
                        $tpl->display('file:' . $this->_TPL_PATH . 'login/forgot2.tpl');
                        break;
                        
                    case "step3":
                        $tpl->display('file:' . $this->_TPL_PATH . 'login/forgot3.tpl');
                        break;
                }
                
                break;
            default:
				$manifest = DevblocksPlatform::getExtension('login.default');
//				$manifest = DevblocksPlatform::getExtension('login.ldap');
				$inst = $manifest->createInstance(1); /* @var $inst CerberusLoginPageExtension */
				$inst->renderLoginForm();
                break;
        }
	}
	
	function showAction() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
	}

	// POST
	function authenticateAction() {
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
		@$original_path = explode(',',DevblocksPlatform::importGPC($_POST['original_path']));
//		@$original_query_str = DevblocksPlatform::importGPC($_POST['original_query']);
		
		$manifest = DevblocksPlatform::getExtension('login.default');
		$inst = $manifest->createInstance(); /* @var $inst CerberusLoginPageExtension */

		$url_service = DevblocksPlatform::getUrlService();
		
		if($inst->authenticate(array('email' => $email, 'password' => $password))) {
			//authentication passed
			//$original_query = $url_service->parseQueryString($original_query_str);
			if($original_path[0]=='')
				unset($original_path[0]);
			
			//$devblocks_response = new DevblocksHttpResponse($original_path, $original_query);
			$devblocks_response = new DevblocksHttpResponse($original_path);

			// Worker
			$worker = CerberusApplication::getActiveWorker();

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
			
			if(!empty($devblocks_response->path) && $devblocks_response->path[0]=='login') {
				$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
				$next_page = ($tour_enabled) ?  'welcome' : 'home';				
				$devblocks_response = new DevblocksHttpResponse(array($next_page));
			}
		}
		else {
			//authentication failed
			$devblocks_response = new DevblocksHttpResponse(array('login'));
		}
		DevblocksPlatform::redirect($devblocks_response);
	}
	
	function authenticateLDAPAction() {
		@$server = DevblocksPlatform::importGPC($_POST['server']);
		@$port = DevblocksPlatform::importGPC($_POST['port']);
		@$dn = DevblocksPlatform::importGPC($_POST['dn']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
		@$original_path = explode(',',DevblocksPlatform::importGPC($_POST['original_path']));
//		@$original_query_str = DevblocksPlatform::importGPC($_POST['original_query']);
		
		$manifest = DevblocksPlatform::getExtension('login.ldap');
		$inst = $manifest->createInstance(); /* @var $inst CerberusLoginPageExtension */

		$url_service = DevblocksPlatform::getUrlService();
		
		if($inst->authenticate(array('server' => $server, 'port' => $port, 'dn' => $dn, 'password' => $password))) {
			//authentication passed
//			$original_query = $url_service->parseQueryString($original_query_str);
			if($original_path[0]=='')
				unset($original_path[0]);
			
			//$devblocks_response = new DevblocksHttpResponse($original_path, $original_query);
			$devblocks_response = new DevblocksHttpResponse($original_path);
			if(!empty($devblocks_response->path) && $devblocks_response->path[0]=='login') {
				$session = DevblocksPlatform::getSessionService();
				$visit = $session->getVisit();
		        $tour_enabled = false;
				if(!empty($visit) && !is_null($visit->getWorker())) {
		        	$worker = $visit->getWorker();
					$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
				}
				$next_page = ($tour_enabled) ?  'welcome' : 'home';				
				$devblocks_response = new DevblocksHttpResponse(array($next_page));
			}
		}
		else {
			//authentication failed
			$devblocks_response = new DevblocksHttpResponse(array('login'));
		}
		DevblocksPlatform::redirect($devblocks_response);
	}
	
	function signoutAction() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(null != ($worker = CerberusApplication::getActiveWorker())) {
			DAO_Worker::logActivity($worker->id, new Model_Activity(null));
		}
		
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
		    $settings = CerberusSettings::getInstance();
			$from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		    $personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL);
			
			$sendTo = new Swift_Address($email);
			$sendFrom = new Swift_Address($from, $personal);
		    
			// Headers
			$mail->setTo($sendTo);
			$mail->setFrom($sendFrom);
			$mail->setSubject($translate->_('signin.forgot.mail.subject'));
			$mail->generateId();
			$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
	
			$mail->attach(new Swift_Message_Part(
				vsprintf($translate->_('signin.forgot.mail.body'), $code),
				'text/plain',
				'base64',
				LANG_CHARSET_CODE
			));
			
			if(!$mailer->send($mail, $sendTo, $sendFrom)) {
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
	        DAO_Worker::updateAgent($worker_id, array(
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
