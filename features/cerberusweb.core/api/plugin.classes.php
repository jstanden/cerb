<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class ChPageController extends DevblocksControllerExtension {
	const ID = 'core.controller.page';
	
	// [TODO] We probably need a CerberusApplication scope for getting content that has ACL applied
	private function _getAllowedPages() {
		$active_worker = CerberusApplication::getActiveWorker();
		$page_manifests = DevblocksPlatform::getExtensions('cerberusweb.page', false);

		// [TODO] This may cause problems on other pages where an active worker isn't required
		
		// Check worker level ACL (if set by manifest)
		foreach($page_manifests as $idx => $page_manifest) {
			// If ACL policy defined
			if(isset($page_manifest->params['acl'])) {
				if($active_worker && !$active_worker->hasPriv($page_manifest->params['acl'])) {
					unset($page_manifests[$idx]);
				}
			}
		}
		
		return $page_manifests;
	}
	
	public function handleRequest(DevblocksHttpRequest $request) {
		$path = $request->path;
		$controller = array_shift($path);

		$page = null;
		if(null != ($page_manifest = CerberusApplication::getPageManifestByUri($controller))) {
			$page = $page_manifest->createInstance(); /* @var $page CerberusPageExtension */
		}

		if(empty($page)) {
			switch($controller) {
				case "portal":
					DevblocksPlatform::dieWithHttpError(null, 404);
					break;
					
				default:
					return; // default page
					break;
			}
		}

		@$action = DevblocksPlatform::strAlphaNum(array_shift($path), '\_') . 'Action';

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
				
			default:
				// Default action, call arg as a method suffixed with Action
				
				if($page->isVisible()) {
					if(method_exists($page,$action)) {
						call_user_func(array($page, $action)); // [TODO] Pass HttpRequest as arg?
					}
				} else {
					// if Ajax [TODO] percolate isAjax from platform to handleRequest
					// DevblocksPlatform::dieWithHttpError("Access denied.  Session expired?", 403);
				}

				break;
		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$path = $response->path;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$visit = $session->getVisit();
		$page_manifests = $this->_getAllowedPages();

		$controller = array_shift($path);

		// Default page
		if(empty($controller)) {
			if(is_a($active_worker, 'Model_Worker')) {
				$controller = 'pages';
				$path = array('pages');
				
				// Find the worker's first page
				
				if(null != ($menu_json = DAO_WorkerPref::get($active_worker->id, 'menu_json', null))) {
					@$menu = json_decode($menu_json);

					if(is_array($menu) && !empty($menu)) {
						$page_id = current($menu);
						$path[] = $page_id;
					}
				}

				$response = new DevblocksHttpResponse($path);
				
				DevblocksPlatform::setHttpResponse($response);
			}
		}
		
		// [JAS]: Require us to always be logged in for Cerberus pages
		if(empty($visit) && 0 != strcasecmp($controller,'login')) {
			$query = array();
			// Must be a valid page controller
			if(!empty($response->path)) {
				if(is_array($response->path) && !empty($response->path) && CerberusApplication::getPageManifestByUri(current($response->path)))
					$query = array('url'=> urlencode(implode('/',$response->path)));
			}
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'),$query));
		}
		
		$page = null;
		if(null != ($page_manifest = CerberusApplication::getPageManifestByUri($controller))) {
			@$page = $page_manifest->createInstance(); /* @var $page CerberusPageExtension */
		}
		
		if(empty($page)) {
			$tpl->assign('settings', $settings);
			$tpl->assign('session', $_SESSION);
			$tpl->assign('translate', $translate);
			$tpl->assign('visit', $visit);
			$message = $tpl->fetch('devblocks:cerberusweb.core::404.tpl');
			
			DevblocksPlatform::dieWithHttpError($message, 404);
			return;
		}
		
		// [JAS]: Listeners (Step-by-step guided tour, etc.)
		$listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
		foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
			 $inst = $listenerManifest->createInstance(); /* @var $inst DevblocksHttpRequestListenerExtension */
			 $inst->run($response, $tpl);
		}

		$tpl->assign('active_worker', $active_worker);
		$tour_enabled = false;
		
		if(!empty($visit) && !is_null($active_worker)) {
			$tour_enabled = intval(DAO_WorkerPref::get($active_worker->id, 'assist_mode', 1));

			$keyboard_shortcuts = intval(DAO_WorkerPref::get($active_worker->id,'keyboard_shortcuts',1));
			$tpl->assign('pref_keyboard_shortcuts', $keyboard_shortcuts);
			
			$active_worker_memberships = $active_worker->getMemberships();
			$tpl->assign('active_worker_memberships', $active_worker_memberships);
		}
		$tpl->assign('tour_enabled', $tour_enabled);
		
		// [JAS]: Variables provided to all page templates
		$tpl->assign('settings', $settings);
		$tpl->assign('session', $_SESSION);
		$tpl->assign('translate', $translate);
		$tpl->assign('visit', $visit);
		
		$tpl->assign('page_manifests',$page_manifests);
		$tpl->assign('page',$page);

		$tpl->assign('response_path', $response->path);
		$tpl->assign('response_uri', implode('/', $response->path));
		
		// Prebody Renderers
		$preBodyRenderers = DevblocksPlatform::getExtensions('cerberusweb.renderer.prebody', true);
		if(!empty($preBodyRenderers))
			$tpl->assign('prebody_renderers', $preBodyRenderers);

		// Postbody Renderers
		$postBodyRenderers = DevblocksPlatform::getExtensions('cerberusweb.renderer.postbody', true);
		if(!empty($postBodyRenderers))
			$tpl->assign('postbody_renderers', $postBodyRenderers);
		
		// Timings
		$tpl->assign('render_time', (microtime(true) - DevblocksPlatform::getStartTime()));
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$tpl->assign('render_memory', memory_get_usage() - DevblocksPlatform::getStartMemory());
			$tpl->assign('render_peak_memory', memory_get_peak_usage() - DevblocksPlatform::getStartPeakMemory());
		}

		// Contexts
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);

		$tpl->display('devblocks:cerberusweb.core::border.tpl');
		
		if(!empty($active_worker)) {
			$unread_notifications = DAO_Notification::getUnreadCountByWorker($active_worker->id);
			$tpl->assign('active_worker_notify_count', $unread_notifications);
			$tpl->display('devblocks:cerberusweb.core::badge_notifications_script.tpl');
		}
	}
};

if(class_exists('Extension_DevblocksEventAction')):
class VaAction_HttpRequest extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_http_request.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$out = null;
		
		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'], $dict));
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$options = $params['options'] ?: array();
		@$run_in_simulator = $params['run_in_simulator'];
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($http_verb))
			return "[ERROR] HTTP verb is required.";
		
		if(empty($http_url))
			return "[ERROR] HTTP URL is required.";
		
		if(empty($response_placeholder))
			return "[ERROR] No result placeholder given.";
		
		// Output
		$out = sprintf(">>> Sending HTTP request:\n%s %s\n%s%s\n",
			mb_convert_case($http_verb, MB_CASE_UPPER),
			$http_url,
			!empty($http_headers) ? (implode("\n", $http_headers)."\n") : '',
			(in_array($http_verb, array('post','put')) ? ("\n" . $http_body. "\n") : "")
		);
		
		$out .= sprintf(">>> Saving response to {{%1\$s}}\n".
				" * {{%1\$s.content_type}}\n".
				" * {{%1\$s.body}}\n".
				" * {{%1\$s.info}}\n".
				" * {{%1\$s.info.http_code}}\n".
				" * {{%1\$s.error}}\n".
				"\n",
				$response_placeholder
		);

		// If set to run in simulator as well
		if($run_in_simulator) {
			$response = $this->_execute($http_verb, $http_url, array(), $http_body, $http_headers, $options);
			$dict->$response_placeholder = $response;
			
			if(isset($response['error']) && !empty($response['error'])) {
				$out .= sprintf(">>> Error in response:\n%s\n", $response['error']);
			}
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'], $dict));
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$options = $params['options'] ?: array();
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($http_verb) || empty($http_url))
			return false;
		
		if(empty($response_placeholder))
			return false;
		
		$response = $this->_execute($http_verb, $http_url, array(), $http_body, $http_headers, $options);
		$dict->$response_placeholder = $response;
	}
	
	private function _execute($verb='get', $url, $params=array(), $body=null, $headers=array(), $options=array()) {
		if(!empty($params) && is_array($params))
			$url .= '?' . http_build_query($params);
		
		$ch = DevblocksPlatform::curlInit($url);
		
		if(isset($options['ignore_ssl_validation']) && $options['ignore_ssl_validation']) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		switch($verb) {
			case 'get':
				break;
				
			case 'post':
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				break;
				
			case 'put':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				break;
				
			case 'delete':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
		}
		
		if(!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$out = DevblocksPlatform::curlExec($ch);
		
		$info = curl_getinfo($ch);
		
		$error = curl_error($ch);

		if(curl_errno($ch)) {
			
		} else {
			// Auto-convert the response body based on the type
			if(!(isset($options['raw_response_body']) && $options['raw_response_body'])) {
				switch(@$info['content_type']) {
					case 'application/json':
						@$out = json_decode($out, true);
						break;
						
					case 'application/octet-stream':
					case 'application/pdf':
					case 'application/zip':
						@$out = base64_encode($out);
						break;
					
					case 'image/gif':
					case 'image/jpeg':
					case 'image/jpg':
					case 'image/png':
						@$out = base64_encode($out);
						break;
						
					case 'text/html':
					case 'text/plain':
					case 'text/xml':
						break;
						
					default:
						//@$out = base64_encode($out);
						break;
				}
			}
		}
		
		curl_close($ch);
		
		return array(
			'content_type' => $info['content_type'],
			'body' => $out,
			'info' => $info,
			'error' => $error,
		);
	}
};
endif;

if(class_exists('Extension_DevblocksEventAction')):
class VaAction_CreateAttachment extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_create_attachment.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$out = null;
		
		@$file_name = $tpl_builder->build($params['file_name'], $dict);
		@$file_type = $tpl_builder->build($params['file_type'], $dict);
		@$content = $tpl_builder->build($params['content'], $dict);
		@$content_encoding = $params['content_encoding'];
		@$object_placeholder = $params['object_placeholder'] ?: '_attachment_meta';
		
		if(empty($file_name))
			return "[ERROR] File name is required.";
		
		if(empty($content))
			return "[ERROR] File content is required.";
		
		// MIME Type
		
		if(empty($file_type))
			$file_type = 'text/plain';

		$out = sprintf(">>> Creating attachment: %s (%s)\n", $file_name, $file_type);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving metadata to {{%1\$s}}\n".
				" * {{%1\$s.id}}\n".
				" * {{%1\$s.name}}\n".
				" * {{%1\$s.type}}\n".
				" * {{%1\$s.size}}\n".
				" * {{%1\$s.hash}}\n".
				"\n",
				$object_placeholder
			);
		}
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$file_name = $tpl_builder->build($params['file_name'], $dict);
		@$file_type = $tpl_builder->build($params['file_type'], $dict);
		@$content = $tpl_builder->build($params['content'], $dict);
		@$content_encoding = $params['content_encoding'];
		@$object_placeholder = $params['object_placeholder'] ?: '_attachment_meta';
		
		// MIME Type
		
		if(empty($file_type))
			$file_type = 'text/plain';
		
		// Encoding
		
		switch($content_encoding) {
			case 'base64':
				$content = base64_decode($content);
				break;
		}
		
		$file_size = strlen($content);

		$sha1_hash = sha1($content, false);
		
		if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $file_name))) {
			$fields = array(
				DAO_Attachment::DISPLAY_NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $file_type,
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
			);
				
			$file_id = DAO_Attachment::create($fields);
		}

		if(empty($file_id))
			return;
		
		if(false == Storage_Attachments::put($file_id, $content))
			return;
		
		unset($content);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = array(
				'id' => $file_id,
				'name' => $file_name,
				'type' => $file_type,
				'size' => $file_size,
				'hash' => $sha1_hash,
			);
		}
		
		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_ATTACHMENT, $file_id, $params, $dict);
	}
	
};
endif;

class Cerb_SwiftPlugin_TransportExceptionLogger implements Swift_Events_TransportExceptionListener {
	private $_lastError = null;
	
	function exceptionThrown(Swift_Events_TransportExceptionEvent $evt) {
		$exception = $evt->getException();
		$this->_lastError = str_replace(array("\r","\n"),array('',' '), $exception->getMessage());
	}
	
	function getLastError() {
		return $this->_lastError;
	}
	
	function clear() {
		$this->_lastError = null;
	}
}

if(class_exists('Extension_MailTransport')):
class CerbMailTransport_Smtp extends Extension_MailTransport {
	const ID = 'core.mail.transport.smtp';
	
	private $_lastErrorMessage = null;
	private $_logger = null;
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/smtp/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) {
		@$host = $params['host'];
		@$port = $params['port'];
		@$encryption = $params['encryption'];
		@$auth_enabled = $params['auth_enabled'];
		@$auth_user = $params['auth_user'];
		@$auth_pass = $params['auth_pass'];
		@$timeout = $params['timeout'];
		
		if(empty($host)) {
			$error = 'The SMTP "host" parameter is required.';
			return false;
		}
		
		if(empty($port)) {
			$error = 'The SMTP "port" parameter is required.';
			return false;
		}
		
		// Try connecting
		
		$mail_service = DevblocksPlatform::getMailService();
		
		$options = array(
			'host' => $host,
			'port' => $port,
			'enc' => $encryption,
			'auth_user' => $auth_user,
			'auth_pass' => $auth_pass,
			'timeout' => $timeout,
		);
		
		try {
			$mailer = $this->_getMailer($options);
			
			@$transport = $mailer->getTransport();
			@$transport->start();
			@$transport->stop();
			return true;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param Swift_Message $message
	 * @return boolean
	 */
	function send(Swift_Message $message, Model_MailTransport $model) {
		$options = array(
			'host' => @$model->params['host'],
			'port' => @$model->params['port'],
			'auth_user' => @$model->params['auth_user'],
			'auth_pass' => @$model->params['auth_pass'],
			'enc' => @$model->params['encryption'],
			'max_sends' => @$model->params['max_sends'],
			'timeout' => @$model->params['timeout'],
		);
		
		if(false == ($mailer = $this->_getMailer($options)))
			return false;
		
		$failed_recipients = array();
		
		$result = $mailer->send($message, $failed_recipients);
		
		if(!$result) {
			$this->_lastErrorMessage = $this->_logger->getLastError();
		}
		
		$this->_logger->clear();
		
		return $result;
	}
	
	function getLastError() {
		return $this->_lastErrorMessage;
	}
	
	/**
	 * @return Swift_Mailer
	 */
	private function _getMailer(array $options) {
		static $connections = array();
		
		// Options
		$smtp_host = isset($options['host']) ? $options['host'] : '127.0.0.1';
		$smtp_port = isset($options['port']) ? $options['port'] : '25';
		$smtp_user = isset($options['auth_user']) ? $options['auth_user'] : null;
		$smtp_pass = isset($options['auth_pass']) ? $options['auth_pass'] : null;
		$smtp_enc = isset($options['enc']) ? $options['enc'] : 'None';
		$smtp_max_sends = isset($options['max_sends']) ? intval($options['max_sends']) : 20;
		$smtp_timeout = isset($options['timeout']) ? intval($options['timeout']) : 30;
		
		/*
		 * [JAS]: We'll cache connection info hashed by params and hold a persistent
		 * connection for the request cycle.  If we ask for the same params again
		 * we'll get the existing connection if it exists.
		 */

		$hash = md5(json_encode(array(
			$smtp_host,
			$smtp_user,
			$smtp_pass,
			$smtp_port,
			$smtp_enc,
			$smtp_max_sends,
			$smtp_timeout
		)));
		
		if(!isset($connections[$hash])) {
			// Encryption
			switch($smtp_enc) {
				case 'TLS':
					$smtp_enc = 'tls';
					break;
					
				case 'SSL':
					$smtp_enc = 'ssl';
					break;
					
				default:
					$smtp_enc = null;
					break;
			}
			
			$smtp = Swift_SmtpTransport::newInstance($smtp_host, $smtp_port, $smtp_enc);
			$smtp->setTimeout($smtp_timeout);
			
			if(!empty($smtp_user)) {
				$smtp->setUsername($smtp_user);
				$smtp->setPassword($smtp_pass);
			}
			
			$mailer = Swift_Mailer::newInstance($smtp);
			$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin($smtp_max_sends, 1));
			
			$this->_logger = new Cerb_SwiftPlugin_TransportExceptionLogger();
			$mailer->registerPlugin($this->_logger);
			
			$connections[$hash] = $mailer;
		}
		
		if($connections[$hash])
			return $connections[$hash];
		
		return null;
	}
}
endif;

if(class_exists('Extension_MailTransport')):
class CerbMailTransport_Null extends Extension_MailTransport {
	const ID = 'core.mail.transport.null';
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/null/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) {
		return true;
	}
	
	/**
	 * @param Swift_Message $message
	 * @return boolean
	 */
	function send(Swift_Message $message, Model_MailTransport $model) {
		if(false == ($mailer = $this->_getMailer()))
			return false;
		
		return $mailer->send($message);
	}
	
	function getLastError() {
		return null;
	}
	
	private function _getMailer() {
		static $mailer = null;
		
		if(is_null($mailer)) {
			$null = Swift_NullTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($null);
		}
		
		return $mailer;
	}
}
endif;