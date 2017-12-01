<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
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

		$tpl = DevblocksPlatform::services()->template();
		$session = DevblocksPlatform::services()->session();
		$settings = DevblocksPlatform::services()->pluginSettings();
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
		$search_menu = [];
		
		foreach($contexts as $context_id => $context) {
			if($context->hasOption('search')) {
				$label = $context->name;

				if(false != ($aliases = Extension_DevblocksContext::getAliasesForContext($context)))
					$label = @$aliases['plural'] ?: $aliases['singular'];
				
				$search_menu[$context_id] = $label;
			}
		}
		
		asort($search_menu);
		
		$tpl->assign('search_menu', $search_menu);
		
		// Conversational interactions
		$interactions = Event_GetInteractionsForWorker::getByPoint('global');
		$tpl->assign('global_interactions_show', !empty($interactions));
		
		// Proactive interactions
		if(!empty($active_worker)) {
			$proactive_interactions_count = DAO_BotInteractionProactive::getCountByWorker($active_worker->id);
			$tpl->assign('proactive_interactions_count', $proactive_interactions_count);
		}
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::border.tpl');
		
		if(!empty($active_worker)) {
			$unread_notifications = DAO_Notification::getUnreadCountByWorker($active_worker->id);
			$tpl->assign('active_worker_notify_count', $unread_notifications);
			$tpl->display('devblocks:cerberusweb.core::badge_notifications_script.tpl');
		}
	}
};

interface IServiceProvider_HttpRequestSigner {
	function authenticateHttpRequest(Model_ConnectedAccount $account, &$ch, &$verb, &$url, &$body, &$headers);
}

interface IServiceProvider_OAuth {
	function oauthRender();
	function oauthCallback();
}

interface IServiceProvider_OAuthRefresh {
	function oauthRefreshAccessToken(Model_ConnectedAccount $account);
}

class WgmCerb_API {
	private $_base_url = '';
	private $_access_key = '';
	private $_secret_key = '';

	public function __construct($base_url, $access_key, $secret_key) {
		$this->_base_url = $base_url;
		$this->_access_key = $access_key;
		$this->_secret_key = $secret_key;
	}

	private function _getBaseUrl() {
		return rtrim($this->_base_url, '/') . '/rest/';
	}
	
	public function get($path) {
		return $this->_connect('GET', $path);
	}

	public function put($path, $payload=array()) {
		return $this->_connect('PUT', $path, $payload);
	}

	public function post($path, $payload=array()) {
		return $this->_connect('POST', $path, $payload);
	}

	public function delete($path) {
		return $this->_connect('DELETE', $path);
	}

	private function _sortQueryString($query) {
		// Strip the leading ?
		if(substr($query,0,1)=='?') $query = substr($query,1);
		$args = array();
		$parts = explode('&', $query);
		foreach($parts as $part) {
			$pair = explode('=', $part, 2);
			if(is_array($pair) && 2==count($pair))
				$args[$pair[0]] = $part;
		}
		ksort($args);
		return implode("&", $args);
	}
	
	function signHttpRequest($url, $verb, $http_date, $postfields) {
		// Authentication
		$url_parts = parse_url($url);
		$url_path = $url_parts['path'];
		
		$verb = DevblocksPlatform::strUpper($verb);

		$url_query = '';
		if(isset($url_parts['query']) && !empty($url_parts))
			$url_query = $this->_sortQueryString($url_parts['query']);

		$secret = DevblocksPlatform::strLower(md5($this->_secret_key));

		$string_to_sign = "$verb\n$http_date\n$url_path\n$url_query\n$postfields\n$secret\n";
		$hash = md5($string_to_sign);
		return sprintf("%s:%s", $this->_access_key, $hash);
	}

	private function _connect($verb, $path, $payload=null) {
		// Prepend the base URL and normalize the given path
		$url = $this->_getBaseUrl() . ltrim($path, '/');
		
		$header = array();
		$ch = DevblocksPlatform::curlInit();

		$verb = DevblocksPlatform::strUpper($verb);
		$http_date = gmdate(DATE_RFC822);

		$header[] = 'Date: '.$http_date;
		$header[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';

		$postfields = '';

		if(!is_null($payload)) {
			if(is_array($payload)) {
				foreach($payload as $pair) {
					if(is_array($pair) && 2==count($pair))
						$postfields .= $pair[0].'='.rawurlencode($pair[1]) . '&';
				}
				rtrim($postfields,'&');

			} elseif (is_string($payload)) {
				$postfields = $payload;
			}
		}

		// HTTP verb-specific options
		switch($verb) {
			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;

			case 'GET':
				break;

			case 'PUT':
				$header[] = 'Content-Length: ' .  strlen($postfields);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
				break;

			case 'POST':
				$header[] = 'Content-Length: ' .  strlen($postfields);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
				break;
		}

		// Authentication
		
		if(false == ($signature = $this->signHttpRequest($url, $verb, $http_date, $postfields)))
			return false;
		
		// [TODO] Use Authoriztion w/ bearer
		$header[] = 'Cerb-Auth: ' . $signature;
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$output = DevblocksPlatform::curlExec($ch);

		$info = curl_getinfo($ch);
		
		// Content-type handling
		@list($content_type, $content_type_opts) = explode(';', DevblocksPlatform::strLower($info['content_type']));
		
		curl_close($ch);
		
		switch($content_type) {
			case 'application/json':
			case 'text/javascript':
				return json_decode($output, true);
				break;
				
			default:
				return $output;
				break;
		}
	}
};

class ServiceProvider_Cerb extends Extension_ServiceProvider implements IServiceProvider_HttpRequestSigner {
	const ID = 'core.service.provider.cerb';
	
	function renderConfigForm(Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_account/providers/cerb.tpl');
	}
	
	function saveConfigForm(Model_ConnectedAccount $account, array &$params) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
	
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!isset($edit_params['base_url']) || empty($edit_params['base_url']))
			return "The 'Base URL' is required.";
		
		if(!isset($edit_params['access_key']) || empty($edit_params['access_key']))
			return "The 'Access Key' is required.";
		
		if(!isset($edit_params['secret_key']) || empty($edit_params['secret_key']))
			return "The 'Secret Key' is required.";
		
		// Test the credentials
		$cerb = new WgmCerb_API($edit_params['base_url'], $edit_params['access_key'], $edit_params['secret_key']);
		
		$json = $cerb->get('workers/me.json');
		
		if(!is_array($json) || !isset($json['__status']))
			return "Unable to connect to the API. Please check your URL.";
		
		if($json['__status'] == 'error')
			return $json['message'];
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, &$ch, &$verb, &$url, &$body, &$headers) {
		$credentials = $account->decryptParams();
		
		if(
			!isset($credentials['base_url'])
			|| !isset($credentials['access_key'])
			|| !isset($credentials['secret_key'])
			|| !is_array($headers)
		)
			return false;
		
		$http_date = gmdate(DATE_RFC822);
		$found_date = false;
		
		foreach($headers as $header) {
			list($k, $v) = explode(':', $header, 2);
			
			if(0 == strcasecmp($k, 'Date')) {
				$http_date = ltrim($v);
				$found_date = true;
				break;
			}
		}
		
		// Add a Date: header if one didn't exist
		if(!$found_date)
			$headers[] = 'Date: ' . $http_date;
		
		$cerb = new WgmCerb_API($credentials['base_url'], $credentials['access_key'], $credentials['secret_key']);
		
		if(false == ($signature = $cerb->signHttpRequest($url, $verb, $http_date, $body)))
			return false;
		
		$headers[] = 'Cerb-Auth: ' . $signature;
		return true;
	}
}

class VaAction_HttpRequest extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$connected_accounts = DAO_ConnectedAccount::getUsableByActor($trigger->getBot());
		$tpl->assign('connected_accounts', $connected_accounts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_http_request.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$out = null;
		
		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'], $dict));
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$auth = $params['auth'];
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
		
		switch($auth) {
			case 'connected_account':
				@$connected_account_id = $params['auth_connected_account_id'];
				if(false != ($connected_account = DAO_ConnectedAccount::get($connected_account_id))) {
					if(!Context_ConnectedAccount::isUsableByActor($connected_account, $trigger->getBot()))
						return "[ERROR] This behavior is attempting to use an unauthorized connected account.";
					
					$out .= sprintf(">>> Authenticating with %s\n\n", $connected_account->name);
				}
				break;
		}
		
		$out .= sprintf(">>> Saving response to {{%1\$s}}\n".
				" * {{%1\$s.body}}\n".
				" * {{%1\$s.content_type}}\n".
				" * {{%1\$s.error}}\n".
				" * {{%1\$s.headers}}\n".
				" * {{%1\$s.info}}\n".
				" * {{%1\$s.info.http_code}}\n".
				"\n",
				$response_placeholder
		);
		
		// If set to run in simulator as well
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
			
			$response = $dict->$response_placeholder;
			
			if(isset($response['error']) && !empty($response['error'])) {
				$out .= sprintf(">>> Error in response:\n%s\n", $response['error']);
			} else {
				if(isset($response['info']))
					$out .= sprintf(">>> Response info:\n%s\n\n", DevblocksPlatform::strFormatJson(json_encode($response['info'])));
				
				if(isset($response['headers']))
					$out .= sprintf(">>> Response headers:\n%s\n\n", DevblocksPlatform::strFormatJson(json_encode($response['headers'])));
				
				if(isset($response['body']))
					$out .= sprintf(">>> Response body:\n%s\n", $response['body']);
			}
			
		} else {
			$out .= ">>> NOTE: This HTTP request is not configured to run in the simulator.\n";
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'], $dict));
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$auth = $params['auth'];
		@$options = $params['options'] ?: array();
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($http_verb) || empty($http_url))
			return false;
		
		if(empty($response_placeholder))
			return false;
		
		$connected_account_id = 0;
		
		if($auth == 'connected_account') {
			$options['connected_account_id'] = @intval($params['auth_connected_account_id']);
			$options['trigger'] = $trigger;
		}
		
		$response = $this->_execute($http_verb, $http_url, array(), $http_body, $http_headers, $options);
		$dict->$response_placeholder = $response;
	}
	
	private function _execute($verb='get', $url, $params=array(), $body=null, $headers=array(), $options=array()) {
		if(!empty($params) && is_array($params))
			$url .= '?' . http_build_query($params);
		
		$ch = DevblocksPlatform::curlInit();
		
		if(isset($options['ignore_ssl_validation']) && $options['ignore_ssl_validation']) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		switch(DevblocksPlatform::strLower($verb)) {
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
				
			case 'patch':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				break;
				
			case 'head':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "HEAD");
				break;
				
			case 'options':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "OPTIONS");
				break;
				
			case 'delete':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
		}

		if(isset($options['connected_account_id']) && $options['connected_account_id']) {
			if(false == ($connected_account = DAO_ConnectedAccount::get($options['connected_account_id'])))
				return false;
			
			if(false == $connected_account->authenticateHttpRequest($ch, $verb, $url, $body, $headers, CerberusContexts::getCurrentActor()))
				return false;
		}
		
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if(!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		
		$response_headers = [];
		
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers) {
			$len = strlen($header);
			
			$parts = explode(':', $header, 2);
			
			if(count($parts) < 2)
				return $len;
			
			$header_name = trim(strtolower($parts[0]));
			$header_value = trim($parts[1]);
			
			if(!isset($response_headers[$header_name])) {
				$response_headers[$header_name] = $header_value;
			} else {
				if(!isset($response_headers[$header_name]))
					$response_headers[$header_name] = [];
				
				$response_headers[$header_name][] = $header_value;
			}
			return $len;
		});
		
		// [TODO] User-level option to follow redirects
		
		$out = DevblocksPlatform::curlExec($ch, true);
		
		$info = curl_getinfo($ch);
		
		// Unauthorized: Give connected accounts a chance to refresh tokens
		if($info['http_code'] == 401 
			&& (!isset($options['ignore_oauth_unauthenticated']) || !$options['ignore_oauth_unauthenticated'])) {
			if(isset($connected_account)) {
				$service_provider = $connected_account->getExtension();
				if($service_provider instanceof IServiceProvider_OAuthRefresh 
					&& $service_provider->oauthRefreshAccessToken($connected_account)) {
						// Don't loop failed auth
						$options['ignore_oauth_unauthenticated'] = true;
						return self::_execute($verb, $url, $params, $body, $headers, $options);
				}
			}
		}
		
		$content_type = null;
		$content_charset = null;
		$error = null;
		
		if(curl_errno($ch)) {
			$error = curl_error($ch);
			
		} elseif (isset($info['content_type'])) {
			// Split content_type + charset in the header
			@list($content_type, $content_charset) = explode(';', DevblocksPlatform::strLower($info['content_type']));
			
			// Auto-convert the response body based on the type
			if(!(isset($options['raw_response_body']) && $options['raw_response_body'])) {
				switch($content_type) {
					case 'application/json':
						@$out = json_decode($out, true);
						break;
						
					case 'application/octet-stream':
					case 'application/pdf':
					case 'application/zip':
						@$out = base64_encode($out);
						break;
						
					case 'audio/mpeg':
					case 'audio/ogg':
						@$out = base64_encode($out);
						break;
					
					case 'image/gif':
					case 'image/jpeg':
					case 'image/jpg':
					case 'image/png':
						@$out = base64_encode($out);
						break;
						
					case 'text/csv':
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
		
		return [
			'content_type' => $content_type,
			'headers' => $response_headers,
			'body' => $out,
			'info' => $info,
			'error' => $error,
		];
	}
};

class BotAction_ScheduleInteractionProactive extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.interaction_proactive.schedule';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_create_interaction.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$date = DevblocksPlatform::services()->date();

		$out = null;
		
		@$on = $params['on'];
		@$behavior_id = $params['behavior_id'];
		@$interaction = $tpl_builder->build($params['interaction'], $dict);
		@$interaction_params_json = $tpl_builder->build($params['interaction_params_json'], $dict);
		@$run = $tpl_builder->build($params['run'], $dict);
		@$expires = $tpl_builder->build($params['expires'], $dict);
		
		$event = $trigger->getEvent();
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(empty($on) || empty($on_objects))
			return "[ERROR] At least one target worker is required.";
		
		if(empty($behavior_id))
			return "[ERROR] behavior is required.";
		
		if(empty($interaction))
			return "[ERROR] behavior is required.";
		
		if(empty($expires) || false == (@$expires_at = strtotime($expires)))
			$expires_at = 0;
		
		if(empty($run) || false == (@$run_at = strtotime($run)))
			$run_at = time();
		
		$out = sprintf(">>> Creating proactive interaction:\nInteraction: %s\nRun: %s\nExpires: %s\nParams:\n%s\n",
			$interaction,
			$run_at ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $run_at) : 'now',
			$expires_at ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $expires_at) : 'never',
			$interaction_params_json . (!empty($interaction_params_json) ? "\n" : '')
		);
		
		if(is_array($on_objects)) {
			$out .= ">>> For:\n";
			
			foreach($on_objects as $on_object) {
				$out .= ' * ' . $on_object->_label . "\n";
			}
		}
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$on = $params['on'];
		@$behavior_id = $params['behavior_id'];
		@$interaction = $tpl_builder->build($params['interaction'], $dict);
		@$interaction_params_json = $tpl_builder->build($params['interaction_params_json'], $dict);
		@$run = $tpl_builder->build($params['run'], $dict);
		@$expires = $tpl_builder->build($params['expires'], $dict);

		$event = $trigger->getEvent();
		
		if(false == ($interaction_params = @json_decode($interaction_params_json, true)))
			$interaction_params = [];
		
		if(empty($expires) || false == (@$expires_at = strtotime($expires)))
			$expires_at = 0;
		
		if(empty($run) || false == (@$run_at = strtotime($run)))
			$run_at = time();
		
		// On workers
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects))
		foreach($on_objects as $on_object) {
			// Create the notification
			DAO_BotInteractionProactive::create($on_object->id, $behavior_id, $interaction, $interaction_params, $trigger->bot_id, $expires_at, $run_at);
		}
	}
};

class BotAction_CalculateTimeElapsed extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.calculate_time_elapsed';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$calendars = DAO_Calendar::getReadableByActor($trigger->getBot());
		$tpl->assign('calendars', $calendars);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_calc_time_elapsed.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$date = DevblocksPlatform::services()->date();

		$out = null;
		
		@$date_from = $tpl_builder->build($params['date_from'], $dict);
		@$date_to = $tpl_builder->build($params['date_to'], $dict);
		@$calendar_id = $params['calendar_id'];
		@$placeholder = $tpl_builder->build($params['placeholder'], $dict);
		
		$event = $trigger->getEvent();
		
		if(empty($date_from) || (!is_numeric($date_from) && false == (@$date_from = strtotime($date_from))))
			$date_from = 0;
		
		if(empty($date_to) || (!is_numeric($date_to) && false == (@$date_to = strtotime($date_to))))
			$date_to = 0;
		
		if(!is_numeric($calendar_id)) {
			$value = $dict->$calendar_id;
			if(is_array($value))
				$value = key($value);
			$calendar_id = intval($value);
		}
		
		if(!$calendar_id || false == ($calendar = DAO_Calendar::get($calendar_id))) {
			return false;
		}
		
		$this->run($token, $trigger, $params, $dict);
		
		$out = sprintf(">>> Calculating time elapsed:\nFrom: %s\nTo: %s\nCalendar: %s\nElapsed: %s",
			$date_from ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $date_from) : 'never',
			$date_to ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $date_to) : 'never',
			$calendar->name,
			_DevblocksTemplateManager::modifier_devblocks_prettysecs($dict->$placeholder)
		);
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$date_from = $tpl_builder->build($params['date_from'], $dict);
		@$date_to = $tpl_builder->build($params['date_to'], $dict);
		@$calendar_id = $params['calendar_id'];
		@$placeholder = $tpl_builder->build($params['placeholder'], $dict);
		
		$event = $trigger->getEvent();
		
		if(empty($date_from) || (!is_numeric($date_from) && false == (@$date_from = strtotime($date_from))))
			$date_from = 0;
		
		if(empty($date_to) || (!is_numeric($date_to) && false == (@$date_to = strtotime($date_to))))
			$date_to = 0;
		
		if(!is_numeric($calendar_id)) {
			$value = $dict->$calendar_id;
			if(is_array($value))
				$value = key($value);
			$calendar_id = intval($value);
		}
		
		if(!$calendar_id || false == ($calendar = DAO_Calendar::get($calendar_id))) {
			return false;
		}
		
		$calendar_events = $calendar->getEvents($date_from, $date_to);
		$availability = $calendar->computeAvailability($date_from, $date_to, $calendar_events);
		
		// [TODO] Option for counting in available or busy time?
		
		$mins = $availability->getMinutes();
		$secs = strlen(str_replace('0', '', $mins)) * 60;
		
		if($placeholder)
			$dict->$placeholder = $secs;
	}
};

class VaAction_CreateAttachment extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_create_attachment.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

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
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
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
				DAO_Attachment::NAME => $file_name,
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

class BotAction_CreateReminder extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.create_reminder';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);

		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_REMINDER, $tpl);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_create_reminder.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$out = null;
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$remind_at = $tpl_builder->build($params['remind_at'], $dict);
		@$object_placeholder = $params['object_placeholder'] ?: '_attachment_meta';
		
		@$worker_ids = DevblocksPlatform::importVar($params['worker_id'],'string','');
		$worker_ids = DevblocksEventHelper::mergeWorkerVars($worker_ids, $dict);
		$worker_id = array_shift($worker_ids) ?: 0;
		
		if(empty($name))
			return "[ERROR] 'Name' is required.";
		
		if(empty($remind_at))
			return "[ERROR] 'Remind at' is required.";
		
		$out = sprintf(">>> Creating reminder: %s (%s)\n", $name, $remind_at);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving metadata to {{%1\$s}}\n".
				" * {{%1\$s.id}}\n".
				" * {{%1\$s.name}}\n".
				" * {{%1\$s.remind_at}}\n".
				" * {{%1\$s.url}}\n".
				" * {{%1\$s.worker_id}}\n".
				"\n",
				$object_placeholder
			);
		}
		
		// Connection
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		
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
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$remind_at = $tpl_builder->build($params['remind_at'], $dict);
		@$behavior_ids = $params['behavior_ids'] ?: [];
		@$behaviors = $params['behaviors'] ?: [];
		@$object_placeholder = $params['object_placeholder'] ?: '_reminder_meta';
		
		@$worker_ids = DevblocksPlatform::importVar($params['worker_id'],'string','');
		$worker_ids = DevblocksEventHelper::mergeWorkerVars($worker_ids, $dict);
		$worker_id = array_shift($worker_ids) ?: 0;
		
		$reminder_params = ['behaviors' => []];
		
		if(is_array($behavior_ids))
		foreach($behavior_ids as $behavior_id)
			$reminder_params['behaviors'][$behavior_id] = @$behaviors[$behavior_id] ?: [];
		
		$fields = [
			DAO_Reminder::NAME => $name,
			DAO_Reminder::REMIND_AT => @strtotime($remind_at) ?: 0,
			DAO_Reminder::IS_CLOSED => 0,
			DAO_Reminder::PARAMS_JSON => json_encode($reminder_params),
			DAO_Reminder::UPDATED_AT => time(),
			DAO_Reminder::WORKER_ID => $worker_id,
		];
		
		$remind_id = DAO_Reminder::create($fields);

		if(empty($remind_id))
			return;
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$url_writer = DevblocksPlatform::services()->url();
			
			$dict->$object_placeholder = [
				'id' => $remind_id,
				'name' => $name,
				'remind_at' => $remind_at,
				'url' => $url_writer->write('c=profiles&what=reminder&id=' . $remind_id, true) . '-' . DevblocksPlatform::strToPermalink($name),
				'worker_id' => $worker_id,
			];
		}
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_REMINDER, $remind_id, $params, $dict);
		
		// Connection
		DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_REMINDER, $remind_id, $params, $dict);

		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_REMINDER, $remind_id, $params, $dict);
	}
};

class BotAction_RecordCreate extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.create';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_create.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return "Invalid changeset JSON.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		// Fail if there's no DAO::create() method
		if(!method_exists($dao_class, 'create'))
			return "This record type is not supported";
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return $error;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error))
			return $error;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return $error;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, null, $error))
			return $error;
		
		$out = sprintf(">>> Creating %s\r\n%s\n", $context_ext->manifest->name, $changeset_json);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = null;
		}
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		// Fail if there's no DAO::create() method
		if(!method_exists($dao_class, 'create'))
			return false;
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return false;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error))
			return false;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return false;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, null, $error))
			return false;
		
		if(false == ($id = $dao_class::create($dao_fields)))
			return false;
		
		if($custom_fields)
		foreach($custom_fields as $field_id => $value)
			DAO_CustomFieldValue::setFieldValue($context_ext->id, $id, $field_id, $value);
		
		$dao_class::onUpdateByActor($actor, $dao_fields, $id);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$labels = $values = [];
			CerberusContexts::getContext($context_ext->id, $id, $labels, $values, null, true, true);
			$obj_dict = DevblocksDictionaryDelegate::instance($values);
			$obj_dict->custom_;
			$dict->$object_placeholder = $obj_dict;
		}
	}
};

class BotAction_RecordUpdate extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.update';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_update.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return "Invalid changeset JSON.";
		
		if(!$id)
			return "ID is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		// Fail if there's no DAO::update() method
		if(!method_exists($dao_class, 'update'))
			return "This record type is not supported";
		
		if(!CerberusContexts::isWriteableByActor($context->id, $id, $actor))
			return DevblocksPlatform::translate('error.core.no_acl.edit') . sprintf(" %s:%d", $context->id, $id);
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return $error;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error, $id))
			return $error;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return $error;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, $id, $error))
			return $error;
		
		$out = sprintf(">>> Updating %s (#%d)\r\n%s\n", $context_ext->manifest->name, $id, $changeset_json);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$changeset_json = $tpl_builder->build(DevblocksPlatform::importVar($params['changeset_json'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = null;
		}
		
		if(false == (@$changeset = json_decode($changeset_json, true)))
			return false;
		
		if(!$id)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		// Fail if there's no DAO::update() method
		if(!method_exists($dao_class, 'update'))
			return false;
		
		if(!CerberusContexts::isWriteableByActor($context->id, $id, $actor))
			return false;
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
			return false;
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error, $id))
			return false;
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			return false;

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, $id, $error))
			return false;
		
		$dao_class::update($id, $dao_fields);
		
		if($custom_fields)
		foreach($custom_fields as $field_id => $value)
			DAO_CustomFieldValue::setFieldValue($context_ext->id, $id, $field_id, $value);
		
		$dao_class::onUpdateByActor($actor, $dao_fields, $id);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$labels = $values = [];
			CerberusContexts::getContext($context_ext->id, $id, $labels, $values, null, true, true);
			$obj_dict = DevblocksDictionaryDelegate::instance($values);
			$obj_dict->custom_;
			$dict->$object_placeholder = $obj_dict;
		}
	}
};

class BotAction_RecordUpsert extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.upsert';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_upsert.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		
		if(!$query)
			return "Query is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		if(false == ($view = $context_ext->getChooserView()))
			return sprintf("Can't create a worklist of type: %s", $context_ext->name);
		
		$view->addParamsWithQuickSearch($query, true);
		$view->renderTotal = true;
		
		list($results, $total) = $view->getData();
		
		if(0 == $total) {
			$action = new BotAction_RecordCreate();
			$action_params = [
				'context' => $context_ext->id,
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator']
			];
			return $action->simulate($token, $trigger, $action_params, $dict);
			
		} elseif (1 == $total) {
			$action = new BotAction_RecordUpdate();
			$action_params = [
				'context' => $context_ext->id,
				'id' => key($results),
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator'],
			];
			return $action->simulate($token, $trigger, $action_params, $dict);
			
		} else {
			return "The upsert query must match exactly 0 or 1 records.";
		}
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		
		if(!$query)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		if(false == ($view = $context_ext->getChooserView()))
			return false;
		
		$view->addParamsWithQuickSearch($query, true);
		$view->renderTotal = true;
		
		list($results, $total) = $view->getData();
		
		if(0 == $total) {
			$action = new BotAction_RecordCreate();
			$action_params = [
				'context' => $context_ext->id,
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator']
			];
			return $action->run($token, $trigger, $action_params, $dict);
			
		} elseif (1 == $total) {
			$action = new BotAction_RecordUpdate();
			$action_params = [
				'context' => $context_ext->id,
				'id' => key($results),
				'changeset_json' => $params['changeset_json'],
				'object_placeholder' => $params['object_placeholder'],
				'run_in_simulator' => $params['run_in_simulator'],
			];
			return $action->run($token, $trigger, $action_params, $dict);
			
		} else {
			return false;
		}
	}
};

class BotAction_RecordDelete extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.delete';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_delete.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		
		if(!$id)
			return "ID is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($model = $dao_class::get($id)))
			return sprintf("%s #%d was not found.", $context_ext->manifest->name, $id);
		
		// Fail if there's no DAO::delete() method
		if(!method_exists($dao_class, 'delete'))
			return "This record type is not supported";
		
		if(!CerberusContexts::isDeleteableByActor($context->id, $id, $actor))
			return DevblocksPlatform::translate('error.core.no_acl.delete') . sprintf(" (%s:%d)", $context->id, $id);
		
		$out = sprintf(">>> Deleting %s (#%d)\n", $context_ext->manifest->name, $id);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		
		if(!$id)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($model = $dao_class::get($id)))
			return false;
		
		// Fail if there's no DAO::delete() method
		if(!method_exists($dao_class, 'delete'))
			return false;
		
		if(!CerberusContexts::isDeleteableByActor($context->id, $id, $actor))
			return false;
		
		$dao_class::delete($id);
	}
};

class BotAction_RecordRetrieve extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.retrieve';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_retrieve.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!$id)
			return "ID is empty.";
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($model = $dao_class::get($id)))
			return sprintf("%s #%d was not found.", $context_ext->manifest->name, $id);
		
		// Fail if there's no DAO::get() method
		if(!method_exists($dao_class, 'get'))
			return "This record type is not supported";
		
		if(!CerberusContexts::isReadableByActor($context->id, $id, $actor))
			return DevblocksPlatform::translate('error.core.no_acl.view') . sprintf(" (%s:%d)", $context->id, $id);
		
		$out = sprintf(">>> Retrieving %s (#%d)\n", $context_ext->manifest->name, $id);
		
		// Always run in simulator mode
		$this->run($token, $trigger, $params, $dict);
		
		$out .= $dict->$object_placeholder;
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$id = $tpl_builder->build(DevblocksPlatform::importVar($params['id'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = null;
		}
		
		if(!$id)
			return false;
		
		if(false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		// Fail if there's no DAO::get() method
		if(!method_exists($dao_class, 'get'))
			return false;
		
		if(false == ($model = $dao_class::get($id)))
			return false;
		
		if(!CerberusContexts::isReadableByActor($context->id, $id, $actor))
			return false;
		
		if(!empty($object_placeholder)) {
			$labels = $values = [];
			CerberusContexts::getContext($context_ext->id, $model, $labels, $values, null, true, true);
			$obj_dict = DevblocksDictionaryDelegate::instance($values);
			$obj_dict->custom_;
			$dict->$object_placeholder = $obj_dict;
		}
	}
};

class BotAction_RecordSearch extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.record.search';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_record_search.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		@$object_placeholder = $params['object_placeholder'];
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return "Invalid record type.";
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return "This record type is not supported.";
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();

		// Fail if there's no DAO::getIds() method
		if(!method_exists($dao_class, 'getIds'))
			return "This record type is not supported";
		
		// Load a view
		if(false == ($view = $context_ext->getChooserView()))
			return "Failed to load a worklist of this record type.";
		
		// Set query filter
		$view->addParamsWithQuickSearch($query, true);
		$view->view_columns = [];
		
		$out = sprintf(">>> Searching %s\nQuery: %s\n", $context_ext->manifest->name, $query);
		
		list($results, $total) = $view->getData();
		
		$ids = array_keys($results);
		
		if(empty($ids))
			return "No results.";
		
		if(false == ($models = $dao_class::getIds($ids)))
			return sprintf("Unable to load %s records.", $context_ext->manifest->name);
		
		// Always run in simulator mode
		$this->run($token, $trigger, $params, $dict);
		
		if($object_placeholder && is_array($dict->$object_placeholder)) {
			$first = current($dict->$object_placeholder);
			
			$out .= sprintf("\n%s:\n%s", $object_placeholder,  $first);
			
			if($total > 1)
				$out .= sprintf("\n\n... and %d more", ($total-1));
			
			$page_placeholder = $object_placeholder . '__page';
			$total_placeholder = $object_placeholder . '__total';
			
			$out .= sprintf("\n\n%s: %d\n%s: %d",
				$page_placeholder,
				$dict->$page_placeholder,
				$total_placeholder,
				$dict->$total_placeholder
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		@$context = $tpl_builder->build(DevblocksPlatform::importVar($params['context'],'string',''), $dict);
		@$query = $tpl_builder->build(DevblocksPlatform::importVar($params['query'],'string',''), $dict);
		@$expands = DevblocksPlatform::parseCrlfString($tpl_builder->build(DevblocksPlatform::importVar($params['expand'],'string',''), $dict));
		@$object_placeholder = $params['object_placeholder'];
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = [];
		}
		
		if(!$context || false == ($context = Extension_DevblocksContext::getByAlias($context, false)))
			return false;
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			return false;
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$dao_class = $context_ext->getDaoClass();
		
		// Fail if there's no DAO::getIds() method
		if(!method_exists($dao_class, 'getIds'))
			return false;
		
		// Load a view
		if(false == ($view = $context_ext->getChooserView()))
			return false;
		
		// Set query filter
		$view->addParamsWithQuickSearch($query, true);
		$view->view_columns = [];
		
		list($results, $total) = $view->getData();
		
		$ids = array_keys($results);
		
		if(empty($ids) || false == ($models = $dao_class::getIds($ids)))
			return false;
		
		if($object_placeholder) {
			$total_placeholder = $object_placeholder . '__total';
			$dict->$total_placeholder = $total;
			
			$page_placeholder = $object_placeholder . '__page';
			$dict->$page_placeholder = $view->renderPage + 1;
			
			// Load dictionaries
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
			
			// Expand tokens
			if(is_array($expands))
			foreach($expands as $expand)
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $expand);
			
			// Set the preferred placeholder
			$dict->$object_placeholder = $dicts;
		}
	}
};

class VaAction_ClassifierPrediction extends Extension_DevblocksEventAction {
	const ID = 'core.va.action.classifier_prediction';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		$classifiers = DAO_Classifier::getReadableByActor(CerberusContexts::CONTEXT_BOT, $trigger->bot_id);
		$tpl->assign('classifiers', $classifiers);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_classifier_prediction.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$classifier_id = $params['classifier_id'];
		@$content = $tpl_builder->build($params['content'], $dict);
		@$object_placeholder = $params['object_placeholder'] ?: '_prediction';

		if(false == ($classifier = DAO_Classifier::get($classifier_id)))
			return "[ERROR] The configured classifier does not exist.";
		
		if(empty($content))
			return "[ERROR] Content is required.";
		
		$out = sprintf(">>> Making a classifier prediction (%s):\n%s\n", $classifier_id, $content);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving result to {{%1\$s}}\n".
				" * {{%1\$s.classification.name}}\n".
				" * {{%1\$s.confidence}}\n".
				" * {{%1\$s.params}}\n".
				"\n",
				$object_placeholder
			);
		}
		
		$this->run($token, $trigger, $params, $dict);
		
		// [TODO] Append raw output?
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		@$classifier_id = $params['classifier_id'];
		@$content = $tpl_builder->build($params['content'], $dict);
		@$object_placeholder = $params['object_placeholder'] ?: '_prediction';
		
		$environment = [
			'lang' => 'en_US',
			'timezone' => '',
		];
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker()))
			$environment['me'] = ['context' => CerberusContexts::CONTEXT_WORKER, 'id' => $active_worker->id, 'model' => $active_worker];
		
		if(false === ($result = $bayes::predict($content, $classifier_id, $environment)))
			return;
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = $result['prediction'];
		}
	}
	
};

/**
 * Based on: https://raw.githubusercontent.com/Mailgarant/switfmailer-openpgp/master/OpenPGPSigner.php
 *
 */
class Cerb_SwiftPlugin_GPGSigner implements Swift_Signers_BodySigner {
	protected $micalg = 'SHA256';
	protected $encrypt = true;
	
	protected function createMessage(Swift_Message $message) {
		$mimeEntity = new Swift_Message('', $message->getBody(), $message->getContentType(), $message->getCharset());
		$mimeEntity->setChildren($message->getChildren());

		$messageHeaders = $mimeEntity->getHeaders();
		$messageHeaders->remove('Message-ID');
		$messageHeaders->remove('Date');
		$messageHeaders->remove('Subject');
		$messageHeaders->remove('MIME-Version');
		$messageHeaders->remove('To');
		$messageHeaders->remove('From');

		return $mimeEntity;
	}
	
	protected function getSignKey(Swift_Message $message) {
		if(false == ($gpg = DevblocksPlatform::services()->gpg()))
			return false;
		
		if(false == ($from = $message->getFrom()) || !is_array($from))
			return false;
		
		$email = key($from);
		
		$fingerprints = [];
		
		if(false != ($keys = $gpg->keyinfo(sprintf("<%s>", $email))) && is_array($keys)) {
			foreach($keys as $key) {
				if($this->isValidKey($key, 'sign'))
				foreach($key['subkeys'] as $subkey) {
					if($this->isValidKey($subkey, 'sign')) {
						return $subkey['fingerprint'];
					}
				}
			}
		}
		
		return false;
	}
	
	protected function getRecipientKeys(Swift_Message $message) {
		$to = $message->getTo() ?: [];
		$cc = $message->getCc() ?: [];
		$bcc = $message->getBcc() ?: [];
		
		$recipients = $to + $cc	+ $bcc;
		
		if(!is_array($recipients) || empty($recipients))
			throw new Swift_SwiftException(sprintf('Error: No valid recipients for GPG encryption'));
		
		$fingerprints = [];
		
		foreach($recipients as $email => $name) {
			$gpg = DevblocksPlatform::services()->gpg();
			$found = false;

			if(false != ($keys = $gpg->keyinfo(sprintf("<%s>", $email))) && is_array($keys)) {
				foreach($keys as $key) {
					if($this->isValidKey($key, 'encrypt'))
					foreach($key['subkeys'] as $subkey) {
						if($this->isValidKey($subkey, 'encrypt')) {
							$fingerprints[] = $subkey['fingerprint'];
							$found = true;
						}
					}
				}
			}
			
			if(!$found)
				throw new Swift_SwiftException(sprintf('Error: No recipient GPG public key for: %s', $email));
		}
		
		return $fingerprints;
	}
	
	protected function isValidKey($key, $purpose) {
		return !(
			$key['disabled'] 
			|| $key['expired'] 
			|| $key['revoked'] 
			|| (
				$purpose == 'sign' 
				&& !$key['can_sign']
				) 
			|| (
				$purpose == 'encrypt' 
				&& !$key['can_encrypt']
			)
		);
	}
	
	protected function signWithPGP($plaintext, $key_fingerprint) {
		$gpg = DevblocksPlatform::services()->gpg();
		
		if(false != ($signed = $gpg->sign($plaintext, $key_fingerprint)))
			return $signed;
		
		throw new Swift_SwiftException('Error: Failed to sign message (passphrase on the secret key?)');
	}
	
	protected function encryptWithPGP($plaintext, $key_fingerprints) {
		$gpg = DevblocksPlatform::services()->gpg();
		
		if(false != ($encrypted = $gpg->encrypt($plaintext, $key_fingerprints)))
			return $encrypted;
		
		throw new Swift_SwiftException('Error: Failed to encrypt message');
	}
	
	/**
	 * Change the Swift_Signed_Message to apply the singing.
	 *
	 * @param Swift_Message $message
	 *
	 * @return self
	 */
	public function signMessage(Swift_Message $message) {
		$sign_key = $this->getSignKey($message);
		
		if(false == ($recipient_keys = $this->getRecipientKeys($message)))
			throw new Swift_SwiftException('Error: No recipient GPG public keys for encryption.');
		
		$originalMessage = $this->createMessage($message);
		$message->setChildren([]);
		$message->setEncoder(Swift_DependencyContainer::getInstance()->lookup('mime.rawcontentencoder'));
		
		if($sign_key) {
			$type = $message->getHeaders()->get('Content-Type');
			$type->setValue('multipart/signed');
			$type->setParameters([
				'micalg' => sprintf('pgp-%s', DevblocksPlatform::strLower($this->micalg)),
				'protocol' => 'application/pgp-signature',
				'boundary' => $message->getBoundary(),
			]);
			
			$signed_body = $originalMessage->toString();
			
			$lines = DevblocksPlatform::parseCrlfString(rtrim($signed_body), true);
			
			array_walk($lines, function(&$line) {
				$line = rtrim($line) . "\r\n";
			});
			
			$signed_body = rtrim(implode('', $lines) . "\r\n");
			
			$signature = $this->signWithPGP($signed_body, $sign_key);
			
			$body = <<< EOD
This is an OpenPGP/MIME signed message (RFC 4880 and 3156)

--{$message->getBoundary()}
$signed_body
--{$message->getBoundary()}
Content-Type: application/pgp-signature; name="signature.asc"
Content-Description: OpenPGP digital signature
Content-Disposition: attachment; filename="signature.asc"

$signature

--{$message->getBoundary()}--
EOD;

		} else { // No signature
			$body = $originalMessage->toString();
			
		}
		
		$message->setBody($body);
		
		if($this->encrypt) {
			if($sign_key) {
				$content = sprintf("%s\r\n%s", $message->getHeaders()->get('Content-Type')->toString(), $body);
			} else {
				$content = $body;
			}
			
			$encrypted_body = $this->encryptWithPGP($content, $recipient_keys);
			
			$type = $message->getHeaders()->get('Content-Type');
			$type->setValue('multipart/encrypted');
			$type->setParameters([
				'protocol' => 'application/pgp-encrypted',
				'boundary' => $message->getBoundary(),
			]);
			
			$body = <<< EOD
This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)

--{$message->getBoundary()}
Content-Type: application/pgp-encrypted
Content-Description: PGP/MIME version identification

Version: 1

--{$message->getBoundary()}
Content-Type: application/octet-stream; name="encrypted.asc"
Content-Description: OpenPGP encrypted message
Content-Disposition: inline; filename="encrypted.asc"

$encrypted_body

--{$message->getBoundary()}--
EOD;
		
			$message->setBody($body);
		}
		
		$message_headers = $message->getHeaders();
		$message_headers->removeAll('Content-Transfer-Encoding');
		
		return $this;
	}

	/**
	 * Return the list of header a signer might tamper.
	 *
	 * @return array
	 */
	public function getAlteredHeaders() {
		return ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition', 'Content-Description'];
	}
	
	/**
	 * return $this
	 */
	public function reset() {
		return $this;
	}
};

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
		$tpl = DevblocksPlatform::services()->template();
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
		
		$mail_service = DevblocksPlatform::services()->mail();
		
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
		$tpl = DevblocksPlatform::services()->template();
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