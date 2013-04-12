<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerb Development Team
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
 \* - Jeff Standen, Darren Sugita, Dan Hildebrandt
 *	 Webgroup Media LLC - Developers of Cerb
 */

class Plugin_RestAPI {
	public static function render($array, $format='json') {
		if(!is_array($array))
			return false;
		
		if('json' == $format) {
			// Fix numeric keys
			if(isset($array['results'])) {
				$filtered_results = array();
				
				foreach($array['results'] as $k => $v) {
					$filtered_results[] = $v;
				}
				
				$array['results'] = $filtered_results;
			}
			
			header("Content-type: text/javascript; charset=utf-8");
			echo json_encode($array);
			
		} elseif ('xml' == $format) {
			header("Content-type: text/xml; charset=utf-8");
			$xml = new SimpleXMLElement("<response/>");
			self::xml_encode($array, $xml);
			echo $xml->asXML();
			
		} else {
			header("Content-type: text/plain; charset=utf-8");
			echo "'" . $format . "' is not implemented.";
		}
		
		exit;
	}
	
	private static function xml_encode($object, &$xml) {
		if(is_array($object))
		foreach($object as $k => $v) {
			if(is_array($v)) {
				$e = $xml->addChild("array", '');
				$e->addAttribute("key", $k);
				self::xml_encode($v, $e);
				
			} else {
				$e = $xml->addChild("string", htmlspecialchars($v, ENT_QUOTES, LANG_CHARSET_CODE));
				$e->addAttribute("key", (string)$k);
			}
		}
	}
};

if (class_exists('Extension_PreferenceTab')):
class Ch_RestPreferencesTab extends Extension_PreferenceTab {
	const ID = 'rest.preferences.tab.api';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'webapi_credentials';
		$defaults->class_name = 'View_WebApiCredentials';
		
		if(null == ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults)))
			return;
		
		// Force filter the view to the current worker's API keys
		$params = array(
			new DevblocksSearchCriteria(SearchFields_WebApiCredentials::WORKER_ID,'=',$active_worker->id),
		);
		
		$view->addParamsRequired($params, true);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showPeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($id) && null == ($model = DAO_WebApiCredentials::get($id)))
			return;
		
		if(!empty($id) && !$active_worker->is_superuser && $active_worker->id != $model->worker_id)
			return;
		
		if(!empty($model))
			$tpl->assign('model', $model);

		$tpl->display('devblocks:cerberusweb.restapi::peek.tpl');
	}
	
	function savePeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$label = DevblocksPlatform::importGPC($_REQUEST['label'],'string','');
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'],'array',array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id)) {
			if(null == ($model = DAO_WebApiCredentials::get($id)))
				return;
		}
		
		if(!empty($id) && !empty($do_delete)) {
			if($active_worker->is_superuser || $active_worker->id == $model->worker_id) {
				DAO_WebApiCredentials::delete($id);
			}
			return;
		}
		
		$allowed_paths = DevblocksPlatform::importGPC($params['allowed_paths'],'string','');
		
		$params = array(
			'allowed_paths' => DevblocksPlatform::parseCrlfString($allowed_paths)
		);
		
		$fields = array(
			DAO_WebApiCredentials::LABEL => (!empty($label) ? $label : 'New API credentials'),
			DAO_WebApiCredentials::PARAMS_JSON => json_encode($params),
		);

		@$generate_new_keys = DevblocksPlatform::importGPC($_REQUEST['regenerate_keys'],'integer',0);
		
		if(empty($id) || $generate_new_keys) {
			$fields[DAO_WebApiCredentials::ACCESS_KEY] = strtolower(CerberusApplication::generatePassword(12));
			$fields[DAO_WebApiCredentials::SECRET_KEY] = strtolower(CerberusApplication::generatePassword(32));
		}
		
		if(empty($id)) { // Create
			$fields[DAO_WebApiCredentials::WORKER_ID] = $active_worker->id;
			DAO_WebApiCredentials::create($fields);
			
		} else { // Edit
			DAO_WebApiCredentials::update($id, $fields);
			
		}
		
		return;
	}
}
endif;

class Ch_RestFrontController implements DevblocksHttpRequestHandler {
	protected $_payload = '';
	
	private function _getRestControllers() {
		$manifests = DevblocksPlatform::getExtensions('cerberusweb.rest.controller', false, true);
		$controllers = array();
		
		if(is_array($manifests))
		foreach($manifests as $manifest) {
			if(isset($manifest->params['uri']))
			$controllers[$manifest->params['uri']] = $manifest;
		}
		
		return $controllers;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		$db = DevblocksPlatform::getDatabaseService();
		
		// **** BEGIN AUTH
		@$verb = $_SERVER['REQUEST_METHOD'];
		@$header_date = $_SERVER['HTTP_X_DATE'];
		
		// If the custom X-Date: header isn't provided, fall back to Date:
		if(empty($header_date))
			@$header_date = $_SERVER['HTTP_DATE'];
		
		@$header_signature = null;
		
		// Try new header first
		if(isset($_SERVER['HTTP_CERB_AUTH'])) {
			$header_signature = $_SERVER['HTTP_CERB_AUTH'];

		// Fallback to older header
		} elseif(isset($_SERVER['HTTP_CERB5_AUTH'])) {
			$header_signature = $_SERVER['HTTP_CERB5_AUTH'];
		}
		
		@$this->_payload = $this->_getRawPost();
		@list($auth_access_key, $auth_signature) = explode(":", $header_signature, 2);
		$url_parts = parse_url(DevblocksPlatform::getWebPath());
		$url_path = $url_parts['path'];
		$url_query = $this->_sortQueryString($_SERVER['QUERY_STRING']);
		$string_to_sign_prefix = "$verb\n$header_date\n$url_path\n$url_query\n$this->_payload";

		if(!$this->_validateRfcDate($header_date)) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid timestamp)"));
		}

		// Worker-level auth
		if(null == ($credential = DAO_WebApiCredentials::getByAccessKey($auth_access_key))) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid credentials: access key)"));
		}
		
		if(null == (@$worker = DAO_Worker::get($credential->worker_id))) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid credentials: worker)"));
		}

		$secret = strtolower(md5($credential->secret_key));
		$string_to_sign = "$string_to_sign_prefix\n$secret\n";
		$compare_hash = md5($string_to_sign);

		if(0 != strcmp($auth_signature, $compare_hash)) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (Invalid credentials: checksum)"));
		}
		
		// REST extensions
		@array_shift($stack); // rest
		
		// Check this API key's path restrictions
		$requested_path = implode('/', $stack);
		@$allowed_paths = $credential->params['allowed_paths'];
		
		if(empty($allowed_paths)) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (This path is prohibited)"));
		}
		
		$permitted = false;
		
		foreach($allowed_paths as $allowed_path) {
			$pattern = DevblocksPlatform::strToRegExp($allowed_path);
			if(preg_match($pattern, $requested_path)) {
				$permitted = true;
				break;
			}
		}
		
		if(!$permitted) {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Access denied! (You are not authorized to make this request)"));
		}
		
		@$controller_uri = array_shift($stack); // e.g. tickets
		
		// Look up the subcontroller for this URI
		$controllers = $this->_getRestControllers();
		
		if(isset($controllers[$controller_uri])) {
			$controller = DevblocksPlatform::getExtension($controllers[$controller_uri]->id, true, true);
			/* @var $controller Extension_RestController */
			CerberusApplication::setActiveWorker($worker);
			$controller->setPayload($this->_payload);
			array_unshift($stack, $verb);
			$controller->handleRequest(new DevblocksHttpRequest($stack));
			
		} else {
			Plugin_RestAPI::render(array('__status'=>'error', 'message'=>"Unknown command ({$controller_uri})"));
		}
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
	
	/**
	 * Enter description here...
	 *
	 * @param string $rfcDate
	 * @return boolean
	 */
	private function _validateRfcDate($rfcDate) {
		$diff_allowed = 600; // 10 min
		$mktime_rfcdate = strtotime($rfcDate);
		$mktime_rfcnow = strtotime(date('r'));
		$diff = $mktime_rfcnow - $mktime_rfcdate;
		return ($diff > (-1*$diff_allowed) && $diff < $diff_allowed) ? true : false;
	}
	
	private function _getRawPost() {
		$contents = "";
		
		$putdata = fopen( "php://input" , "rb" );
		while(!feof( $putdata ))
			$contents .= fread($putdata, 4096);
		fclose($putdata);

		return $contents;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
	}
};

abstract class Extension_RestController extends DevblocksExtension {
	const ERRNO_CUSTOM = 0;
	const ERRNO_ACL = 1;
	const ERRNO_NOT_IMPLEMENTED = 2;
	const ERRNO_SEARCH_FILTERS_INVALID = 20;
	
	private $_activeWorker = null; /* @var $_activeWorker Model_Worker */
	private $_format = 'json';
	private $_payload = '';
	
	/**
	 *
	 * @param string $message
	 */
	protected function error($code, $message='') {
		// Polymorph for convenience
		if(is_string($code)) {
			$message = $code;
			$code = self::ERRNO_CUSTOM;
		}
		
		// Error codes
		switch(intval($code)) {
			case self::ERRNO_ACL:
				if(empty($message))
					$message = 'Access denied.';
				break;
				
			case self::ERRNO_NOT_IMPLEMENTED:
				if(empty($message))
					$message = 'Not implemented.';
				break;
				
			case self::ERRNO_SEARCH_FILTERS_INVALID:
				if(empty($message))
					$message = 'The provided search filters are invalid.';
				break;
				
			default:
			case self::ERRNO_CUSTOM:
				$code = self::ERRNO_CUSTOM;
				if(empty($message))
					$message = '';
				break;
		}

		if(!is_string($message))
			$message = '';
		
		$out = array(
			'__status' => 'error',
			'__version' => APP_VERSION,
			'__build' => APP_BUILD,
			'__error' => $code,
			'message' => $message,
		);
		
		return Plugin_RestAPI::render($out, $this->_format);
	}
	
	/**
	 *
	 * @param array $array
	 */
	protected function success($array) {
		if(!is_array($array))
			return false;

		@$expand = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['expand'],'string',null));

		// Do we need to lazy load some fields to be helpful?
		if(is_array($expand) && !empty($expand)) {
			if(isset($array['_context'])) {
				$dict = new DevblocksDictionaryDelegate($array);
				
				foreach($expand as $expand_field)
					$dict->$expand_field;
				
				$array = $dict->getDictionary();
				
			} elseif(isset($array['results'])) {
				foreach($array['results'] as $k => $v) {
					if(!isset($array['results'][$k]['_context']))
						continue;
					
					$dict = new DevblocksDictionaryDelegate($array['results'][$k]);
					
					foreach($expand as $expand_field)
						$dict->$expand_field;
					
					$array['results'][$k] = $dict->getDictionary();
				}
				
			}
		}
		
		$out = array(
			'__status' => 'success',
			'__version' => APP_VERSION,
			'__build' => APP_BUILD,
		) + $array;
		
		// These keys aren't needed
		unset($out['_loaded']);
		
		// Sort by key
		ksort($out);
		
		return Plugin_RestAPI::render($out, $this->_format);
	}
	
	/**
	 * @deprecated
	 * @return Model_Worker
	 */
	public function getActiveWorker() {
		return CerberusApplication::getActiveWorker();
	}
	
	public function getPayload() {
		return $this->_payload;
	}
	
	public function setPayload($payload) {
		$this->_payload = $payload;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		
		// Figure out our format by looking at the last path argument
		$command = explode('.', array_pop($stack));
		$format = array_pop($command);
		$command = implode('.', $command);
		
		array_push($stack, $command);
		if(null != $format)
			$this->_format = $format;
		
		// Verb
		@$verb = array_shift($stack);
		
		if(0==strcasecmp('PUT',$verb)) {
			$_PUT = array();
			parse_str($this->_payload, $_PUT);
			foreach($_PUT as $k => $v) {
				$_POST[$k] = $v;
				$_REQUEST[$k] = $v;
			}
		}
		
		// Verb Actions
		$method = strtolower($verb) .'Action';
		if(method_exists($this,$method)) {
			call_user_func(array(&$this,$method), $stack);
		}
	}
	
	function getAction($stack) {
		/* Override */
		$this->error('GET not implemented.');
	}
	
	function putAction($stack) {
		/* Override */
		$this->error('PUT not implemented.');
	}
	
	function postAction($stack) {
		/* Override */
		$this->error('POST not implemented.');
	}
	
	function deleteAction($stack) {
		/* Override */
		$this->error('DELETE not implemented.');
	}
	
//	protected function _search($filters, $sortToken, $sortAsc, $page, $limit) {
// [TODO] Overload
//	}

	protected function _handleSearchBuildParamsCustomFields(&$filters, $context) {
		$params = array();
		// Handle custom fields
		if(is_array($filters))
		foreach($filters as $key => $filter) {

			$parts = explode("_",$filter[0],2);
			if(2==count($parts) && 'custom'==$parts[0] && is_numeric($parts[1])) {
				// Custom Fields
				$fields = DAO_CustomField::getByContext($context);

				if(is_array($fields))
				foreach($fields as $field_id => $fieldData) {
					if($field_id === intval($parts[1])) {
						$field = 'cf_'.$field_id;
						unset($filters[$key]);
					}
				}
				$params[$field] = new DevblocksSearchCriteria($field, $filter[1], $filter[2]);
			}
		}
		return $params;
	}
	
	protected function _handleSearchBuildParams($filters) {
		// Criteria
		$params = array();
		
		if(is_array($filters))
		foreach($filters as $filter) {
			if(!is_array($filter) && 3 != count($filter))
				$this->error(self::ERRNO_SEARCH_FILTERS_INVALID);
			
			if(null === ($field = $this->translateToken($filter[0], 'search')))
				$this->error(self::ERRNO_SEARCH_FILTERS_INVALID, sprintf("'%s' is not a valid search token.", $filter[0]));
			
			$oper = $filter[1];
			$value = $filter[2];
			
			// Translate OPER_IN from JSON arrays to PHP primitives
			switch($oper) {
				case DevblocksSearchCriteria::OPER_IN:
				case DevblocksSearchCriteria::OPER_IN_OR_NULL:
				case DevblocksSearchCriteria::OPER_NIN:
				case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
					if(!is_array($value) && preg_match('#^\[.*\]$#', $value)) {
						$value = json_decode($value, true);
						
					} elseif(is_array($value)) {
						$value;
						
					} else {
						$value = array($value);
						
					}
					break;
				
				case DevblocksSearchCriteria::OPER_BETWEEN:
					if(!is_array($value) && preg_match('#^\[.*\]$#', $value)) {
						$value = json_decode($value, true);
					}
					break;
			}
			
			$params[$field] = new DevblocksSearchCriteria($field, $oper, $value);
		}
		
		return $params;
	}
	
	protected function _handlePostSearch() {
		@$criteria = DevblocksPlatform::importGPC($_REQUEST['criteria'],'array',array());
		@$opers = DevblocksPlatform::importGPC($_REQUEST['oper'],'array',array());
		@$values = DevblocksPlatform::importGPC($_REQUEST['value'],'array',array());

		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer',1);
		@$limit = DevblocksPlatform::importGPC($_REQUEST['limit'],'integer',10);
		
		@$sortToken = DevblocksPlatform::importGPC($_REQUEST['sortBy'],'string',null);
		@$sortAsc = DevblocksPlatform::importGPC($_REQUEST['sortAsc'],'integer',1);

		if(count($criteria) != count($opers) || count($criteria) != count($values))
			$this->error(self::ERRNO_SEARCH_FILTERS_INVALID);
		
		// Filters
		$filters = array();
		
		if(is_array($criteria))
		foreach($criteria as $idx => $token) {
			$field = $token;
			$oper = $opers[$idx];
			$value = $values[$idx];
			
			if(!empty($field))
				$filters[$field] = array($field, $oper, $value);
		}
		
		return $this->search($filters, $sortToken, $sortAsc, $page, $limit);
	}
	
	protected function _handleRequiredFields($required, $fields) {
		// Check required fields
		if(is_array($required))
		foreach($required as $reqfield)
			if(!isset($fields[$reqfield]))
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is a required field.", $reqfield));
	}
	
	protected function _handleCustomFields($scope_array) {
		$fields = array();
		
		if(is_array($scope_array))
		foreach($scope_array as $k => $v) {
			$parts = explode("_",$k,2);
			if(2==count($parts) && 'custom'==$parts[0] && is_numeric($parts[1])) {
				$fields[intval($parts[1])] = DevblocksPlatform::importGPC($scope_array[$k]);
			}
		}
		
		return $fields;
	}
};

interface IExtensionRestController {
	function getContext($id);
	function search($filters=array(), $sortToken='', $sortAsc=1, $page=1, $limit=10);
	function translateToken($token, $type='dao');
};
