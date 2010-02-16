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

class ChWebApiConfigTab extends Extension_ConfigTab {
	const ID = 'webapi.config.tab';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$kb_topics = DAO_KbCategory::getWhere('parent_id = 0');
		$tpl->assign('kb_topics',$kb_topics);
		
		$access_keys = DAO_WebapiKey::getWhere();
		$tpl->assign('access_keys', $access_keys);
		
		$tpl->display('file:' . $tpl_path . 'config/index.tpl');
	}
	
	function saveTab() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');
		@$access_ids = DevblocksPlatform::importGPC($_REQUEST['access_ids'],'array',array());
		@$add_nickname = DevblocksPlatform::importGPC($_REQUEST['add_nickname'],'string');
		@$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
		
		// Deletes
		if(!empty($deletes)) {
			DAO_WebapiKey::delete($deletes);
		}
		
		// Edits
		if(is_array($access_ids))
		foreach($access_ids as $access_id) {
			$rights = array();

			// ACL
			@$aclAddresses = DevblocksPlatform::importGPC($_REQUEST['aclAddresses'.$access_id],'integer',0);
//			@$aclFnr = DevblocksPlatform::importGPC($_REQUEST['aclFnr'.$access_id],'integer',0);
			@$aclOrgs = DevblocksPlatform::importGPC($_REQUEST['aclOrgs'.$access_id],'integer',0);
			@$aclTasks = DevblocksPlatform::importGPC($_REQUEST['aclTasks'.$access_id],'integer',0);
			@$aclParser = DevblocksPlatform::importGPC($_REQUEST['aclParser'.$access_id],'integer',0);
			@$aclTickets = DevblocksPlatform::importGPC($_REQUEST['aclTickets'.$access_id],'integer',0);
			@$aclKB = DevblocksPlatform::importGPC($_REQUEST['aclKB'.$access_id],'array');

			$aclKBTopics = array();
			foreach($aclKB as $k => $v)
				$aclKBTopics[$v] = 1;
			
			$rights['acl_addresses'] = $aclAddresses;
//			$rights['acl_fnr'] = $aclFnr;
			$rights['acl_orgs'] = $aclOrgs;
			$rights['acl_tasks'] = $aclTasks;
			$rights['acl_parser'] = $aclParser;
			$rights['acl_tickets'] = $aclTickets;
			$rights['acl_kb_topics'] = $aclKBTopics;
			
			// IPs
			@$ipList = DevblocksPlatform::importGPC($_REQUEST['ips'.$access_id],'string','');
			if(!empty($ipList)) {
				$ips = array_unique(DevblocksPlatform::parseCsvString($ipList));
				$rights['ips'] = $ips;
			}
			
			$fields = array(
				DAO_WebapiKey::RIGHTS => serialize($rights)
			);
			DAO_WebapiKey::update($access_id, $fields);
		}
		
		// Add Access Key
		if(!empty($add_nickname)) {
			$gen_access_key = CerberusApplication::generatePassword(20);
			$gen_secret_key = CerberusApplication::generatePassword(30);
			
			$fields = array(
				DAO_WebapiKey::NICKNAME => $add_nickname,
				DAO_WebapiKey::ACCESS_KEY => $gen_access_key,
				DAO_WebapiKey::SECRET_KEY => $gen_secret_key,
			);
			$key_id = DAO_WebapiKey::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','webapi')));
		exit;
	}
};

class DAO_WebapiKey extends DevblocksORMHelper {
	const ID = 'id';
	const NICKNAME = 'nickname';
	const ACCESS_KEY = 'access_key';
	const SECRET_KEY = 'secret_key';
	const RIGHTS = 'rights';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO webapi_key (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'webapi_key', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_WebapiKey[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, nickname, access_key, secret_key, rights ".
			"FROM webapi_key ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY nickname asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WebapiKey	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WebapiKey[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WebapiKey();
			$object->id = intval($row['id']);
			$object->nickname = $row['nickname'];
			$object->access_key = $row['access_key'];
			$object->secret_key = $row['secret_key'];
			$rights = $row['rights'];
			
			if(!empty($rights)) {
				@$object->rights = unserialize($rights);
			}
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM webapi_key WHERE id IN (%s)",$ids_list));
	}
};

class Model_WebapiKey {
	const ACL_NONE = 0;
	const ACL_READONLY = 1;
	const ACL_FULL = 2;
	
	public $id;
	public $nickname;
	public $access_key;
	public $secret_key;
	public $rights;
	
	public function isValidIp($ip) {
		@$valid_ips = $this->rights['ips'];
		if(!is_array($valid_ips) || empty($valid_ips))
			return true;
		
		foreach($valid_ips as $valid_ip) {
			if(substr($ip,0,strlen($valid_ip))==$valid_ip)
				return true;
		}
		
		return false;
	}
};

class ChRestFrontController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$controllers = array(
			'addresses' => 'Rest_AddressesController',
//			'fnr' => 'Rest_FnrController',
			'orgs' => 'Rest_OrgsController',
			'tasks' => 'Rest_TasksController',
			'parser' => 'Rest_ParserController',
			'tickets' => 'Rest_TicketsController',
			'messages' => 'Rest_MessagesController',
			'articles' => 'Rest_KbArticlesController',
			'notes' => 'Rest_NotesController',
			'comments' => 'Rest_CommentsController',
		);

		$stack = $request->path;
		array_shift($stack); // webapi
		
		@$controller = array_shift($stack);

		if(isset($controllers[$controller])) {
			$inst = new $controllers[$controller]();
			if($inst instanceof Ch_RestController) {
				$inst->handleRequest(new DevblocksHttpRequest($stack));
			}
		}
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
	}
};

// [TODO] This should be an extension so people can add new functionality to REST w/ plugins
abstract class Ch_RestController implements DevblocksHttpRequestHandler {
	protected $_format = 'xml';
	protected $_payload = '';
	protected $_activeWorker = null; /* @var $_activeWorker CerberusWorker */ 
	
	protected function getActiveWorker() {
		return($this->_activeWorker);
	}
	
	protected function setActiveWorker($worker) {
		$this->_activeWorker = $worker;
	}
		
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		$db = DevblocksPlatform::getDatabaseService();
		
		// **** BEGIN AUTH
		@$verb = $_SERVER['REQUEST_METHOD'];
		@$header_date = $_SERVER['HTTP_DATE'];
		@$header_signature = $_SERVER['HTTP_CERB4_AUTH'];
		@$this->_payload = $this->_getRawPost();
		@list($auth_access_key,$auth_signature) = explode(":", $header_signature, 2);
		$url_parts = parse_url(DevblocksPlatform::getWebPath());
		$url_path = $url_parts['path'];
		$url_query = $this->_sortQueryString($_SERVER['QUERY_STRING']);
		$string_to_sign_prefix = "$verb\n$header_date\n$url_path\n$url_query\n$this->_payload";
		
		if(!$this->_validateRfcDate($header_date)) {
			$this->_error("Access denied! (Invalid timestamp)");
		}
		
		
		if(strpos($auth_access_key,'@')) { // WORKER-LEVEL AUTH
			$workers = DAO_Worker::getAll();
			foreach($workers as $worker) { /* @var $worker CerberusWorker */
				if($worker->email == $auth_access_key) {
					$this->setActiveWorker($worker);
					break;
				}
			}
			
			if(null == $this->getActiveWorker()) {
				$this->_error("Access denied! (Invalid worker)");
			}
				
			$pass = $this->getActiveWorker()->pass;
			$string_to_sign = "$string_to_sign_prefix\n$pass\n";
			$compare_hash = base64_encode(sha1($string_to_sign,true));

			if(0 != strcmp($auth_signature,$compare_hash)) {
				$this->_error("Access denied! (Invalid password)");
			}
			
		} // END WORKER AUTH
		else { // APP-LEVEL AUTH
			$stored_keychains = DAO_WebapiKey::getWhere(sprintf("%s = %s",
				DAO_WebapiKey::ACCESS_KEY,
				$db->qstr(str_replace(' ','',$auth_access_key))
			)); /* @var $stored_keychain Model_WebApiKey */
	
			if(!empty($stored_keychains)) {
				@$stored_keychain = array_shift($stored_keychains);
				@$auth_secret_key = $stored_keychain->secret_key;
				@$auth_rights = $stored_keychain->rights;
				
				$string_to_sign = "$string_to_sign_prefix\n$auth_secret_key\n";
				$compare_hash = base64_encode(sha1($string_to_sign,true));
	
				if(0 != strcmp($auth_signature,$compare_hash)) {
					$this->_error("Access denied! (Invalid signature)");
				}
				
				// Check that this IP is allowed to perform the VERB
				if(!$stored_keychain->isValidIp($_SERVER['REMOTE_ADDR'])) {
					$this->_error(sprintf("Access denied! (IP %s not authorized)",$_SERVER['REMOTE_ADDR']));				
				}
	
			} else {
				$this->_error("Access denied! (Unknown access key)");
			}
		}
		// **** END APP AUTH
		
		// Figure out our format by looking at the last path argument
		@list($command,$format) = explode('.', array_pop($stack));
		array_push($stack, $command);
		$this->_format = $format;
		
		if(null != $this->getActiveWorker()) {
			$method = strtolower($verb) .'WorkerAction';
			if(method_exists($this,$method)) {
				call_user_func(array(&$this,$method),$stack);
			}
		} else {
			$method = strtolower($verb) .'Action';
			if(method_exists($this,$method)) {
				call_user_func(array(&$this,$method),$stack,$stored_keychain);
			}
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
	
	protected function _render($xml) {
		if('json' == $this->_format) {
			//header("Content-type: text/javascript; charset=utf-8");
			//echo ::fromXml($xml, true);
			// [TODO] Not implemented
		} else {
			header("Content-type: text/xml; charset=utf-8");
			echo $xml;
		}
		exit;
	}
	
	protected function _error($message) {
		$out_xml = new SimpleXMLElement('<error></error>');
		$out_xml->addChild('message', htmlspecialchars($message));
		$this->_render($out_xml->asXML());
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
	}
	
	protected function getPayload() {
		return $this->_payload;
	}
	
	private function _getRawPost() {
		$contents = "";
		
		$putdata = fopen( "php://input" , "rb" ); 
		while(!feof( $putdata )) 
			$contents .= fread($putdata, 4096); 
		fclose($putdata);

		return $contents;
	}
	
	protected function _renderResults($results, $fields, $element='element', $container='elements', $attribs=array()) {
		$xml = new SimpleXMLElement("<$container/>");

		if(is_array($attribs))
		foreach($attribs as $k=>$v)
			$xml->addAttribute($k, htmlspecialchars($v));

		foreach($results as $result) {
			$e = $xml->addChild($element);
			foreach($fields as $idx => $fld) {
				if((isset($result[$idx])) && ($idx_name = $this->translate($idx, true)) != null)
					$e->addChild($idx_name, htmlspecialchars($result[$idx]));
			}
		}

		$this->_render($xml->asXML());
	}

	protected function _renderOneResult($results, $fields, $element='element') {
		$xml = new SimpleXMLElement("<$element/>");
		$result = array_shift($results);

		foreach($fields as $idx => $fld) {
			if((isset($result[$idx])) && ($idx_name = $this->translate($idx, true)) != null)
				$xml->addChild($idx_name, htmlspecialchars($result[$idx]));
		}

		$this->_render($xml->asXML());
	}
};

class Rest_AddressesController extends Ch_RestController {
	protected function translate($idx, $dir) {
		$translations = array(
			'a_id' => 'id',
			'a_email' => 'email',
			'a_first_name' => 'first_name',
			'a_last_name' => 'last_name',
			'a_contact_org_id' => 'contact_org_id',
			'a_num_spam' => null,
			'a_num_nonspam' => null,
			'a_is_banned' => 'is_banned',
		);
		
		if ($dir === true && array_key_exists($idx, $translations))
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
	
	protected function isValid($idx_name, $value) {
		switch($idx_name) {
			case 'contact_org_id':
				return is_numeric($value) ? true : false;
			case 'is_banned':
				return ('1' == $value || '0' == $value) ? true : false;
			case 'email':
				$addr_array = imap_rfc822_parse_adrlist($value, "webgroupmedia.com");
				return (is_array($addr_array) && count($addr_array) > 0) ? true : false;
			case 'first_name':
			case 'last_name':
				return !empty($value) ? true : false;
			default:
				return false;
		}
	}
		
	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE == intval(@$keychain->rights['acl_addresses']))
			$this->_error("Action not permitted.");
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path);
				break;
		}
	}

	protected function putAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_addresses']))
			$this->_error("Action not permitted.");
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
			case 'create':
				if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_addresses']))
					$this->_error("Action not permitted.");
				$this->_postCreateAction($path);
				break;
			case 'search':
				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_addresses']))
					$this->_error("Action not permitted.");
				$this->_postSearchAction($path);
				break;
			case 'validate':
				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_addresses']))
					$this->_error("Action not permitted.");
				$this->_postValidateAction($path);
				break;
		}
	}
	
	protected function deleteAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_addresses']))
			$this->_error("Action not permitted.");
	}
	
	//****
	
	private function _postCreateAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = simplexml_load_string($xmlstr);
		
		$fields = array();
		
		$flds = DAO_Address::getFields();
		unset($flds[DAO_Address::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}
		
		if(empty($fields[DAO_Address::EMAIL]))
			$this->_error("All required fields were not provided.");
		
		if(null != ($address = DAO_Address::lookupAddress($fields[DAO_Address::EMAIL],false)))
			$this->_error("Address already exists.");

		// Only valid orgs
		// [TODO] This referential integrity belongs in DAO
		if(!empty($fields[DAO_Address::CONTACT_ORG_ID])) {
			$in_contact_org = intval($fields[DAO_Address::CONTACT_ORG_ID]);
			if(null == ($contact_org = DAO_ContactOrg::get($in_contact_org))) {
				unset($fields[DAO_Address::CONTACT_ORG_ID]);
			}
		}
		
		$id = DAO_Address::create($fields);
		
		// send confirmation if requested
		@$confirmation_link = DevblocksPlatform::importGPC($xml_in->send_confirmation,'string','');
		if (!empty($confirmation_link)) {
			$this->_sendConfirmation($fields[DAO_Address::EMAIL],$confirmation_link);
		}
		
		// Render
		$this->_getIdAction(array($id));
	}
	
	private function _postSearchAction($path) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		$xml_in = simplexml_load_string($this->getPayload());
		$search_params = SearchFields_Address::getFields();
		$params = array();
		
		// Check for params in request
		foreach($search_params as $sp_element => $fld) {
			$sp_element_name = $this->translate($sp_element, true);
			if ($sp_element_name == null) continue;
			@$field_ptr = $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}

		list($results, $total) = DAO_Address::search(
			array(),
			$params,
			50,
			$p_page,
			SearchFields_Address::EMAIL,
			true,
			true
		);
		
		$attribs = array(
			'page_results' => count($results),
			'total_results' => intval($total)
		);
		
		$this->_renderResults($results, $search_params, 'address', 'addresses', $attribs);
	}
	
	private function _postValidateAction($path) {
		$xml_in = simplexml_load_string($this->getPayload());
		@$email = $xml_in->params->email;
		@$pass_hash = $xml_in->params->pass_hash;
		@$confirmation_code = $xml_in->params->confirmation_code;
		
		if(null != ($addy = DAO_Address::lookupAddress($email, false))) {
			if($addy->is_registered && !empty($addy->pass) && $pass_hash==$addy->pass) {
				$xml = new SimpleXMLElement("<success></success>");
				$xml->addChild('address',htmlspecialchars($email));
			}
			if(!$addy->is_registered && !empty($addy->pass) && $confirmation_code==$addy->pass) {
				$xml = new SimpleXMLElement("<success></success>");
				$xml->addChild('address',htmlspecialchars($email));
			}
		} else {
			$xml = new SimpleXMLElement("<failure></failure>");
			$xml->addChild('validation_failed');
		}
		
		$this->_render($xml->asXML());
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_Address::search(
			array(),
			array(
				SearchFields_Address::ID => new DevblocksSearchCriteria(SearchFields_Address::ID,'=',$in_id)
			),
			1,
			0,
			null,
			null,
			false
		);
			
		if(empty($results))
			$this->_error("ID not valid.");
		
		$this->_renderOneResult($results, SearchFields_Address::getFields(), 'address');
	}
	
	private function _getListAction($path) {
		$xml = new SimpleXMLElement("<addresses></addresses>"); 
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		list($addresses,$null) = DAO_Address::search(
			array(),
			array(),
			50,
			$p_page,
			SearchFields_Address::EMAIL,
			true,
			false
		);

		$this->_renderResults($addresses, SearchFields_Address::getFields(), 'address', 'addresses');
	}
	
	private function _putIdAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = new SimpleXMLElement($xmlstr);
			
		$in_id = array_shift($path);
		
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($address = DAO_Address::get($in_id)))
			$this->_error("ID not valid.");

		$fields = array();
			
		$flds = DAO_Address::getFields();
		unset($flds[DAO_Address::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}
		
		// [TODO] This referential integrity should really be up to DAO
		if(!empty($fields[DAO_Address::CONTACT_ORG_ID])) {
			$in_contact_org_id = intval($fields[DAO_Address::CONTACT_ORG_ID]);
			if(null == ($contact_org = DAO_ContactOrg::get($in_contact_org_id))) {
				unset($fields[DAO_Address::CONTACT_ORG_ID]);
			}
		}
		
		// update password if requested
		@$password = DevblocksPlatform::importGPC($xml_in->password,'string','');
		if (!empty($password)) {
			$fields[DAO_Address::PASS] = md5($password); 
		}
		
		if(!empty($fields))
			DAO_Address::update($address->id,$fields);
		
		// send confirmation if requested
		@$confirmation_link = DevblocksPlatform::importGPC($xml_in->send_confirmation,'string','');
		if (!empty($confirmation_link)) {
			$this->_sendConfirmation($address->email,$confirmation_link);
		}
		
		$this->_getIdAction(array($address->id));
	}
	
	private function _sendConfirmation($email,$link) {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$from = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM);
		$from_personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL);
		
		$url = DevblocksPlatform::getUrlService();
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			
			$code = CerberusApplication::generatePassword(8);
			
			if(!empty($email) && null != ($addy = DAO_Address::lookupAddress($email, false))) {
				$fields = array(
					DAO_Address::IS_REGISTERED => 0,
					DAO_Address::PASS => $code,
				);
				DAO_Address::update($addy->id, $fields);
				
			} else {
				return;
			}
			
			$message = $mail_service->createMessage();
			$message->setTo(array($email));
			$message->setFrom(array($from => $from_personal));
			$message->setSubject("Account Confirmation Code");
			$message->setBody(sprintf("Below is your confirmation code.  Please copy and paste it into the confirmation form at:\r\n".
				"%s\r\n".
				"\r\n".
				"Your confirmation code is: %s\r\n".
				"\r\n".
				"Thanks!\r\n",
				$link,
				$code
			));
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
			
			$result = $mailer->send($message);
		}
		catch (Exception $e) {
			return;
		}
	}
};

class Rest_OrgsController extends Ch_RestController {
	protected function translate($idx, $dir) {
		$translations = array(
			'c_id' => 'id',
			'c_name' => 'name',
			'c_street' => 'street',
			'c_city' => 'city',
			'c_province' => 'province',
			'c_postal' => 'postal',
			'c_country' => 'country',
			'c_phone' => 'phone',
			'c_website' => 'website',
			'c_created' => null,
		);
		
		if ($dir === true && array_key_exists($idx, $translations))
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
		
	protected function isValid($idx_name, $value) {
		switch($idx_name) {
			case 'name':
			case 'street':
			case 'city':
			case 'province':
			case 'postal':
			case 'country':
			case 'phone':
			case 'website':
				return !empty($value) ? true : false;
			default:
				return false;
		}
	}
		
	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_orgs']))
			$this->_error("Action not permitted.");
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path);
				break;
		}
	}

	protected function putAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_orgs']))
			$this->_error("Action not permitted.");
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
			case 'create':
				if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_orgs']))
					$this->_error("Action not permitted.");
				$this->_postCreateAction($path);
				break;
			case 'search':
				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_orgs']))
					$this->_error("Action not permitted.");
				$this->_postSearchAction($path);
				break;
		}
	}
	
	protected function deleteAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_orgs']))
			$this->_error("Action not permitted.");
		
		// Single DELETE
		if(1==count($path) && is_numeric($path[0]))
			$this->_deleteIdAction($path);
	}
	
	//****
	
	private function _postCreateAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = simplexml_load_string($xmlstr);

		$fields = array();
		
		$flds = DAO_ContactOrg::getFields();
		unset($flds[DAO_ContactOrg::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}
				
		if(empty($fields[DAO_ContactOrg::NAME]))
			$this->_error("All required fields were not provided.");
		
		$id = DAO_ContactOrg::create($fields);
		
		// Render
		$this->_getIdAction(array($id));		
	}
	
	private function _postSearchAction($path) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		$xml_in = simplexml_load_string($this->getPayload());
		$search_params = SearchFields_ContactOrg::getFields();
		$params = array();
		
		// Check for params in request
		foreach($search_params as $sp_element => $fld) {
			$sp_element_name = $this->translate($sp_element, true);
			if ($sp_element_name == null) continue;
			@$field_ptr = $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}

		list($orgs, $total) = DAO_ContactOrg::search(
			array(),
			$params,
			50,
			$p_page,
			DAO_ContactOrg::NAME,
			true,
			true
		);
		
		$attribs = array(
			'page_results' => count($orgs),
			'total_results' => intval($total)
		);
		
		$this->_renderResults($orgs, $search_params, 'org', 'orgs', $attribs);
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_ContactOrg::search(
			array(),
			array(
				SearchFields_ContactOrg::ID => new DevblocksSearchCriteria(SearchFields_ContactOrg::ID,'=',$in_id)
			),
			1,
			0,
			null,
			null,
			false
		);
			
		if(empty($results))
			$this->_error("ID not valid.");

		$this->_renderOneResult($results, SearchFields_ContactOrg::getFields(), 'org');
	}
	
	private function _getListAction($path) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		list($orgs,$null) = DAO_ContactOrg::search(
			array(),
			array(),
			50,
			$p_page,
			SearchFields_ContactOrg::NAME,
			true,
			false
		);

		$this->_renderResults($orgs, SearchFields_ContactOrg::getFields(), 'org', 'orgs');
	}
	
	private function _putIdAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = new SimpleXMLElement($xmlstr);
		
		$in_id = array_shift($path);
		
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($contact_org = DAO_ContactOrg::get($in_id)))
			$this->_error("ID not valid.");

		$fields = array();
			
		$flds = DAO_ContactOrg::getFields();
		unset($flds[DAO_ContactOrg::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}
		
		if(!empty($fields))
			DAO_ContactOrg::update($contact_org->id,$fields);

		$this->_getIdAction(array($contact_org->id));
	}
	
	private function _deleteIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($contact_org = DAO_ContactOrg::get($in_id)))
			$this->_error("ID is not valid.");
		
		DAO_ContactOrg::delete($contact_org->id);
		
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
};

class Rest_TicketsController extends Ch_RestController {
	protected function translate($idx, $dir) {
		$translations = array(
			't_id' => 'id',
			't_mask' => 'mask',
			't_subject' => 'subject',
			't_category_id' => null,
			't_created_date' => 'created_date',
			't_updated_date' => 'updated_date',
			't_is_waiting' => 'is_waiting',
			't_is_closed' => 'is_closed',
			't_is_deleted' => 'is_deleted',
			't_first_wrote_address_id' => 'first_wrote_address_id',
			't_first_wrote' => 'first_wrote',
			't_last_wrote_address_id' => 'last_wrote_address_id',
			't_last_wrote' => 'last_wrote',
			't_last_action_code' => null,
			't_last_worker_id' => 'last_worker_id',
			't_next_worker_id' => 'next_worker_id',
			't_spam_training' => 'spam_training',
			't_spam_score' => 'spam_score',
			't_first_wrote_spam' => null,
			't_first_wrote_nonspam' => null,
			't_interesting_words' => null,
			't_due_date' => 'due_date',
			't_first_contact_org_id' => 'first_contact_org_id',
			't_team_id' => 'group_id',
			't_category_id' => 'bucket_id',
		);
		
		if ($dir === true && array_key_exists($idx, $translations))
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
	
	protected function isValid($idx_name, $value) {
		switch($idx_name) {
			case 'created_date':
			case 'updated_date':
			case 'due_date':
			case 'last_worker_id':
			case 'next_worker_id':
			case 'first_wrote_address_id':
			case 'last_wrote_address_id':
			case 'first_contact_org_id':
			case 'group_id':
			case 'bucket_id':
				return is_numeric($value) ? true : false;
			case 'is_waiting':
			case 'is_closed':
			case 'is_deleted':
				return ('1' == $value || '0' == $value) ? true : false;
			case 'spam_training':
				return ('N' == $value || 'S' == $value) ? true : false;
			case 'spam_score':
				return (is_numeric($value) && 1 > $value && 0 < $value) ? true : false;
			case 'mask':
			case 'subject':
			case 'first_wrote':
			case 'last_wrote':
				return !empty($value) ? true : false;
			default:
				return false;
		}
	}
		
	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path);
		
		// Actions
		$value = array_shift($path);
		switch($value) {
			case 'list':
				$this->_getListAction($path);
				break;
			default:
				if(($id = DAO_Ticket::getTicketIdByMask($value)) != null)
					$this->_getIdAction(array($id));
				break;
		}
	}

	protected function getWorkerAction($path) {
		$worker = parent::getActiveWorker(); /* @var $worker CerberusWorker */
		$memberships = $worker->getMemberships();
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			if(($ticket = DAO_Ticket::getTicket($path[0])) != null && isset($memberships[$ticket->team_id]))
				$this->_getIdAction($path);
		
		// Actions
		$value = array_shift($path);
		switch($value) {
			case 'list':
				$this->_getListAction($path,
					array(
						SearchFields_Ticket::TICKET_TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($memberships))
					)
				);
				break;
			default:
				if(($ticket = DAO_Ticket::getTicketByMask($value)) != null && isset($memberships[$ticket->team_id]))
					$this->_getIdAction(array($ticket->id));
				break;
		}
	}

	protected function putAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			$this->_putIdAction($path);
	}
	
	protected function putWorkerAction($path) {
		$worker = parent::getActiveWorker(); /* @var $worker CerberusWorker */
		$memberships = $worker->getMemberships();
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			if(($ticket = DAO_Ticket::getTicket($path[0])) != null && isset($memberships[$ticket->team_id]))
				$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
			case 'search':
				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
					$this->_error("Action not permitted.");
				$this->_postSearchAction($path);
				break;
		}
	}
	
	protected function postWorkerAction($path) {
		$worker = parent::getActiveWorker(); /* @var $worker CerberusWorker */
		$memberships = $worker->getMemberships();
		
		// Actions
		switch(array_shift($path)) {
			case 'search':
				$this->_postSearchAction($path,
					array(
						SearchFields_Ticket::TICKET_TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($memberships))
					)
				);
				break;
		}
	}
	
	protected function deleteAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single DELETE
		if(1==count($path) && is_numeric($path[0]))
			$this->_deleteIdAction($path);
	}
	
	protected function deleteWorkerAction($path) {
		$worker = parent::getActiveWorker(); /* @var $worker CerberusWorker */
		$memberships = $worker->getMemberships();
				
		// Single DELETE
		if(1==count($path) && is_numeric($path[0]))
			if(($ticket = DAO_Ticket::getTicket($path[0])) != null && isset($memberships[$ticket->team_id]))
				$this->_deleteIdAction($path);
	}
	
	private function _postSearchAction($path, $params=array()) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		$xml_in = simplexml_load_string($this->getPayload());
		$search_params = SearchFields_Ticket::getFields();
		
		// Check for params in request
		foreach($search_params as $sp_element => $fld) {
			$sp_element_name = $this->translate($sp_element, true);
			if ($sp_element_name == null) continue;
			@$field_ptr = $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				if(SearchFields_Ticket::TICKET_TEAM_ID == $sp_element && isset($params[SearchFields_Ticket::TICKET_TEAM_ID])) { // Worker level search
					// TODO: Allow overrides of Worker teams (if they want to search for just a single team)
					// for now, all Worker-level team searches will use all their teams as the search param 
				} else { // app-level search
					$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
				}
			}
		}
		
		list($results, $total) = DAO_Ticket::search(
			array(SearchFields_Ticket::TICKET_ID),
			$params,
			50,
			$p_page,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			false,
			true
		);
		
		$attribs = array(
			'page_results' => count($results),
			'total_results' => intval($total)
		);
		
		$this->_renderResults($results, $search_params, 'ticket', 'tickets', $attribs);
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_Ticket::search(
			array(),
			array(
				SearchFields_Ticket::TICKET_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID,'=',$in_id)
			),
			1,
			0,
			null,
			null,
			false
		);
			
		if(empty($results))
			$this->_error("ID not valid.");

		$this->_renderOneResult($results, SearchFields_Ticket::getFields(), 'ticket');
	}
	
	private function _getListAction($path, $params=array()) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		@$p_group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string','');		
		@$p_bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$p_is_closed = DevblocksPlatform::importGPC($_REQUEST['is_closed'],'string','');
		@$p_is_deleted = DevblocksPlatform::importGPC($_REQUEST['is_deleted'],'string','');
		
		// Group
		if(0 != strlen($p_group_id)) {
			// cannot allow override by Worker search to invalid team
			if(isset($params[SearchFields_Ticket::TICKET_TEAM_ID])) { // this is a Worker search
				if(false !== array_search($p_group_id,$params[SearchFields_Ticket::TICKET_TEAM_ID]->value)); { // worker is member of group_id
					$params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'eq',intval($p_group_id));
				}
			} else { // app-level key search
				$params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'eq',intval($p_group_id));
			}
		}
		// Bucket
		if(0 != strlen($p_bucket_id))
			$params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'eq',intval($p_bucket_id));
		// Closed
		if(0 != strlen($p_is_closed))
			$params[SearchFields_Ticket::TICKET_CLOSED] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'eq',intval($p_is_closed));
		// Deleted
		if(0 != strlen($p_is_deleted))
			$params[SearchFields_Ticket::TICKET_DELETED] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'eq',intval($p_is_deleted));
		
		list($results,$null) = DAO_Ticket::search(
			array(),
			$params,
			50,
			$p_page,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			true,
			false
		);

		$this->_renderResults($results, SearchFields_Ticket::getFields(), 'ticket', 'tickets');
	}
	
	private function _putIdAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = new SimpleXMLElement($xmlstr);
		
		$in_id = array_shift($path);
		
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($ticket = DAO_Ticket::getTicket($in_id)))
			$this->_error("ID not valid.");

		$fields = array();
			
		$flds = DAO_Ticket::getFields();
		unset($flds[DAO_Ticket::ID]);
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}
		if(!empty($fields))
			DAO_Ticket::updateTicket($ticket->id,$fields);

		$this->_getIdAction(array($ticket->id));
	}
	
	private function _deleteIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($ticket = DAO_Ticket::getTicket($in_id)))
			$this->_error("ID is not valid.");
		
		DAO_Ticket::updateTicket($ticket->id,array(
			DAO_Ticket::IS_CLOSED => 1,
			DAO_Ticket::IS_DELETED => 1,
		));
		
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
};

class Rest_MessagesController extends Ch_RestController {
	protected function translate($idx, $dir) {
		return $idx; //message data is being built by hand, not from DAO_Message::search() (since search is incomplete)
		$translations = array(
			'm_id' => 'id',
		);
		
		if ($dir === true && array_key_exists($idx, $translations))
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
		
	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path);
				break;
		}
	}

	protected function putAction($path,$keychain) {
		$this->_error("Action not permitted.");
//		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
//			$this->_error("Action not permitted.");
//		
//		// Single PUT
//		if(1==count($path) && is_numeric($path[0]))
//			$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
		$this->_error("Action not permitted.");
		// Actions
//		switch(array_shift($path)) {
//			case 'create':
//				if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
//					$this->_error("Action not permitted.");
//				$this->_postCreateAction($path);
//				break;
//			case 'search':
//				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
//					$this->_error("Action not permitted.");
//				$this->_postSearchAction($path);
//				break;
//		}
	}
	
	//****
	
	private function _postCreateAction($path) {
//		$xmlstr = $this->getPayload();
//		$xml_in = simplexml_load_string($xmlstr);
//		
//		$fields = array();
//		
//		$flds = DAO_Message::getFields();
//		unset($flds[DAO_Message::ID]);
//		
//		foreach($flds as $idx => $f) {
//			$idx_name = $this->translate($idx, true);
//			if ($idx_name == null) continue;
//			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
//			if($this->isValid($idx_name,$value))
//				$fields[$idx] = $value;
//		}
//				
//		if(empty($fields[DAO_Message::ADDRESS_ID])
//		|| empty($fields[DAO_Message::TICKET_ID]))
//			$this->_error("All required fields were not provided.");
//		
//		if(null != ($address = DAO_Address::get(intval($fields[DAO_Message::ADDRESS_ID]))))
//			$this->_error("Address id specified does not exist.");
//
//		if(null != ($ticket = DAO_Ticket::getTicket(intval($fields[DAO_Message::TICKET_ID]))))
//			$this->_error("Ticket id specified does not exist.");
//
//		// Supply creation date if not specified
//		if(empty($fields[DAO_Message::CREATED_DATE]))
//			$fields[DAO_Message::CREATED_DATE] = time();
//		
//		$id = DAO_Message::create($fields);
//		
//		// Render
//		$this->_getIdAction(array($id));
	}
	
	private function _postSearchAction($path) {
//		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
//		
//		$xml_in = simplexml_load_string($this->getPayload());
//		$search_params = SearchFields_Message::getFields();
//		$params = array();
//		
//		// Check for params in request
//		foreach($search_params as $sp_element => $fld) {
//			$sp_element_name = $this->translate($sp_element, true);
//			if ($sp_element_name == null) continue;
//			@$field_ptr = $xml_in->params->$sp_element_name;
//			if(!empty($field_ptr)) {
//				@$value = (string) $field_ptr['value'];
//				@$oper = (string) $field_ptr['oper'];
//				if(empty($oper)) $oper = 'eq';
//				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
//			}
//		}
//
//		list($results, $null) = DAO_Message::search(
//			$params,
//			50,
//			$p_page,
//			SearchFields_Message::ID,
//			false,
//			false
//		);
//		
//		$this->_renderResults($results, $search_params, 'message', 'messages');
	}
	
	private function _getMessageXML($id) {
		$message = DAO_Ticket::getMessage($id); /* @var $message CerberusMessage */
		if(is_null($message))
			$this->_error("ID $id not valid.");
		$message_content = DAO_MessageContent::get($id);
		$message_headers = DAO_MessageHeader::getAll($id);
		$message_notes = DAO_MessageNote::getByMessageId($id);
		
		$xml_out = new SimpleXMLElement("<message></message>");
		$xml_out->addChild('id', $message->id);
		$xml_out->addChild('ticket_id', $message->ticket_id);
		$xml_out->addChild('created_date', $message->created_date);
		$xml_out->addChild('address_id', $message->address_id);
		$xml_out->addChild('is_outgoing', $message->is_outgoing);
		$xml_out->addChild('worker_id', $message->worker_id);
		$xml_out->addChild('content', htmlspecialchars($message_content));
		
		$headers = $xml_out->addChild('headers');
		foreach($message_headers as $header_name => $header_value)
			$headers->addChild($header_name, htmlspecialchars($header_value));

		$xml_notes = $xml_out->addChild('notes');
    	foreach($message_notes as $note) {
    		$xml_note = $xml_notes->addChild('note');
    		$xml_note->addChild('id', $note->id);
    		$xml_note->addChild('type', htmlspecialchars($note->type));
    		$xml_note->addChild('message_id', $note->message_id);
    		$xml_note->addChild('created', $note->created);
    		$xml_note->addChild('worker_id', $note->worker_id);
    		$xml_note->addChild('content', htmlspecialchars($note->content));
    	}
		
    	return $xml_out;
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);
		if(empty($in_id))
			$this->_error("ID was not provided.");

		$xml_out = $this->_getMessageXML($in_id);

		$this->_render($xml_out->asXML());
	}
	
	private function _getListAction($path) {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		if(0 == $ticket_id)
			$this->_error("Ticket ID was not provided.");
			
		$messages = DAO_Ticket::getMessagesByTicket($ticket_id);
		
		$xml_out = new SimpleXMLElement("<messages></messages>");
		foreach($messages as $message) {
			$message_xml = $xml_out->addChild('message');
			$message_xml->addChild('id', $message->id);
			$message_xml->addChild('ticket_id', $message->ticket_id);
			$message_xml->addChild('created_date', $message->created_date);
			$message_xml->addChild('address_id', $message->address_id);
			$message_xml->addChild('is_outgoing', $message->is_outgoing);
			$message_xml->addChild('worker_id', $message->worker_id);
		}
		
		$this->_render($xml_out->asXML());
	}
	
	private function _putIdAction($path) {
//		$xmlstr = $this->getPayload();
//		$xml_in = new SimpleXMLElement($xmlstr);
//		
//		$in_id = array_shift($path);
//		
//		if(empty($in_id))
//			$this->_error("ID was not provided.");
//			
//		if(null == ($message = DAO_Ticket::getMessage($in_id)))
//			$this->_error("ID not valid.");
//
//		$fields = array();
//			
//		$flds = DAO_Message::getFields();
//		unset($flds[DAO_Message::ID]);
//		
//		foreach($flds as $idx => $f) {
//			$idx_name = $this->translate($idx, true);
//			if ($idx_name == null) continue;
//			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
//			if($this->isValid($idx_name,$value))
//				$fields[$idx] = $value;
//		}
//		
//		if(!empty($fields))
//			DAO_Message::update($in_id,$fields);
//
//		$this->_getIdAction(array($in_id));
	}
};

class Rest_NotesController extends Ch_RestController {
	protected function translate($idx, $dir) {
		return $idx;
	}
		
	protected function isValid($idx_name, $value) {
		switch($idx_name) {
			case 'worker_id':
			case 'message_id':
				return is_numeric($value) ? true : false;
			case 'content':
				return !empty($value) ? true : false;
			default:
				return false;
		}
	}
		
	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single GET (no list on Notes... use the Message object)
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path);
		else
			$this->_error("Invalid request.");
	}

	protected function putAction($path,$keychain) {
		$this->_error("Action not permitted.");
	}
	
	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
			case 'create':
				if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
					$this->_error("Action not permitted.");
				$this->_postCreateAction($path);
				break;
			case 'search':
				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
					$this->_error("Action not permitted.");
				$this->_postSearchAction($path);
				break;
		}
	}
	
	protected function deleteAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single DELETE
		if(1==count($path) && is_numeric($path[0]))
			$this->_deleteIdAction($path);
	}
	
	//****
	
	private function _postCreateAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = simplexml_load_string($xmlstr);

		$fields = array();
		
		$flds = array(
			DAO_MessageNote::ID => DAO_MessageNote::ID,
			DAO_MessageNote::TYPE => DAO_MessageNote::TYPE,
			DAO_MessageNote::MESSAGE_ID => DAO_MessageNote::MESSAGE_ID,
			DAO_MessageNote::WORKER_ID => DAO_MessageNote::WORKER_ID,
			DAO_MessageNote::CREATED => DAO_MessageNote::CREATED,
			DAO_MessageNote::CONTENT => DAO_MessageNote::CONTENT,
		);
		unset($flds[DAO_MessageNote::ID]);
		unset($flds[DAO_MessageNote::CREATED]);
		unset($flds[DAO_MessageNote::TYPE]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}

		if(empty($fields[DAO_MessageNote::CONTENT])
		|| empty($fields[DAO_MessageNote::MESSAGE_ID]))
			$this->_error("All required fields were not provided.");
		
		DAO_MessageNote::create($fields);

		// not necessarily accurate, but create() doesn't return an id.  TODO: fix DAO_MessageNote::create
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
	
	private function _postSearchAction($path) {
		$this->_error("Search not yet implemented");
//		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
//		
//		$xml_in = simplexml_load_string($this->getPayload());
//		$search_params = SearchFields_ContactOrg::getFields();
//		$params = array();
//		
//		// Check for params in request
//		foreach($search_params as $sp_element => $fld) {
//			$sp_element_name = $this->translate($sp_element, true);
//			if ($sp_element_name == null) continue;
//			@$field_ptr = $xml_in->params->$sp_element_name;
//			if(!empty($field_ptr)) {
//				@$value = (string) $field_ptr['value'];
//				@$oper = (string) $field_ptr['oper'];
//				if(empty($oper)) $oper = 'eq';
//				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
//			}
//		}
//
//		list($orgs, $null) = DAO_ContactOrg::search(
//			$params,
//			50,
//			$p_page,
//			DAO_ContactOrg::NAME,
//			true,
//			false
//		);
//		
//		$this->_renderResults($orgs, $search_params, 'org', 'orgs');
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);
		if(empty($in_id))
			$this->_error("ID was not provided.");

		if(null == ($message = DAO_MessageNote::get($in_id)))
			$this->_error("ID is not valid");
    	
    	$xml_out = new SimpleXMLElement("<note></note>");
    	$xml_out->addChild('id', $message->id);
    	$xml_out->addChild('type', htmlspecialchars($message->type));
    	$xml_out->addChild('message_id', $message->message_id);
    	$xml_out->addChild('created', $message->created);
    	$xml_out->addChild('worker_id', $message->worker_id);
    	$xml_out->addChild('content', htmlspecialchars($message->content));
		
		$this->_render($xml_out->asXML());
	}
	
	private function _deleteIdAction($path) {
		$in_id = array_shift($path);
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($note = DAO_MessageNote::get($in_id)))
			$this->_error("ID is not valid.");
		
		DAO_MessageNote::delete($note->id);
		
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
};

class Rest_CommentsController extends Ch_RestController {
	protected function translate($idx, $dir) {
		return $idx;
	}
		
	protected function isValid($idx_name, $value) {
		switch($idx_name) {
			case 'address_id':
			case 'ticket_id':
			case 'created':
				return is_numeric($value) ? true : false;
			case 'comment':
				return !empty($value) ? true : false;
			default:
				return false;
		}
	}
		
	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path);
				break;
		}
	}

	protected function putAction($path,$keychain) {
		$this->_error("Action not permitted.");
	}
	
	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
			case 'create':
				if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
					$this->_error("Action not permitted.");
				$this->_postCreateAction($path);
				break;
			case 'search':
				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
					$this->_error("Action not permitted.");
				$this->_postSearchAction($path);
				break;
		}
	}
	
	protected function deleteAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single DELETE
		if(1==count($path) && is_numeric($path[0]))
			$this->_deleteIdAction($path);
	}
	
	//****
	
	private function _postCreateAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = simplexml_load_string($xmlstr);

		$fields = array();
		
		$flds = array(
			DAO_TicketComment::ID => DAO_TicketComment::ID,
			DAO_TicketComment::TICKET_ID => DAO_TicketComment::TICKET_ID,
			DAO_TicketComment::ADDRESS_ID => DAO_TicketComment::ADDRESS_ID,
			DAO_TicketComment::CREATED => DAO_TicketComment::CREATED,
			DAO_TicketComment::COMMENT => DAO_TicketComment::COMMENT,
		);
		unset($flds[DAO_TicketComment::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}

		// Set a default created date if not provided
		if(!isset($fields[DAO_TicketComment::CREATED]))
			$fields[DAO_TicketComment::CREATED] = time();

		if(empty($fields[DAO_TicketComment::COMMENT])
		|| empty($fields[DAO_TicketComment::TICKET_ID]))
			$this->_error("All required fields were not provided.");
		
		$id = DAO_TicketComment::create($fields);

		// Render
		$this->_getIdAction(array($id));		
	}
	
	private function _postSearchAction($path) {
		$this->_error("Search not yet implemented");
//		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
//		
//		$xml_in = simplexml_load_string($this->getPayload());
//		$search_params = SearchFields_ContactOrg::getFields();
//		$params = array();
//		
//		// Check for params in request
//		foreach($search_params as $sp_element => $fld) {
//			$sp_element_name = $this->translate($sp_element, true);
//			if ($sp_element_name == null) continue;
//			@$field_ptr = $xml_in->params->$sp_element_name;
//			if(!empty($field_ptr)) {
//				@$value = (string) $field_ptr['value'];
//				@$oper = (string) $field_ptr['oper'];
//				if(empty($oper)) $oper = 'eq';
//				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
//			}
//		}
//
//		list($orgs, $null) = DAO_ContactOrg::search(
//			$params,
//			50,
//			$p_page,
//			DAO_ContactOrg::NAME,
//			true,
//			false
//		);
//		
//		$this->_renderResults($orgs, $search_params, 'org', 'orgs');
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);
		if(empty($in_id))
			$this->_error("ID was not provided.");

		if(null == ($comment = DAO_TicketComment::get($in_id)))
			$this->_error("ID is not valid");
    	
    	$xml_out = new SimpleXMLElement("<comment></comment>");
    	$xml_out->addChild('id', $comment->id);
    	$xml_out->addChild('ticket_id', $comment->ticket_id);
    	$xml_out->addChild('created', $comment->created);
    	$xml_out->addChild('address_id', $comment->address_id);
    	$xml_out->addChild('comment', htmlspecialchars($comment->comment));
		
		$this->_render($xml_out->asXML());
	}
	
	private function _getListAction($path) {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		if(0 == $ticket_id)
			$this->_error("Ticket ID was not provided.");

		$ticket_comments = DAO_TicketComment::getWhere(sprintf("%s = %d",
			DAO_TicketComment::TICKET_ID,
			$ticket_id
		));
		
		$xml_out = new SimpleXMLElement("<comments></comments>");
    	foreach($ticket_comments as $comment) {
    		$xml_comment = $xml_out->addChild('comment');
    		$xml_comment->addChild('id', $comment->id);
    		$xml_comment->addChild('ticket_id', $comment->ticket_id);
    		$xml_comment->addChild('created', $comment->created);
    		$xml_comment->addChild('address_id', $comment->address_id);
    		$xml_comment->addChild('comment', htmlspecialchars($comment->comment));
    	}

		$this->_render($xml_out->asXML());
	}
	
	private function _deleteIdAction($path) {
		$in_id = array_shift($path);
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($note = DAO_TicketComment::get($in_id)))
			$this->_error("ID is not valid.");
		
		DAO_TicketComment::delete($note->id);
		
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
};

class Rest_TasksController extends Ch_RestController {
	protected function translate($idx, $dir) {
		$translations = array(
			't_id' => 'id',
			't_title' => 'title',
			't_is_completed' => 'is_completed',
			't_due_date' => 'due_date',
			't_completed_date' => 'completed_date',
			't_worker_id' => 'worker_id',
			't_source_extension' => 'source_extension',
			't_source_id' => 'source_id',
			't_updated_date' => 'updated_date',
		);
		
		if ($dir === true && array_key_exists($idx, $translations))
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
			
		return $idx;
	}
		
	protected function isValid($idx_name, $value) {
		switch($idx_name) {
			case 'due_date':
			case 'worker_id':
			case 'source_id':
			case 'is_completed':
			case 'completed_date':
			case 'updated_date':
				return is_numeric($value) ? true : false;
			case 'title':
			case 'source_extension':
				return !empty($value) ? true : false;
			default:
				return false;
		}
	}
		
	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tasks']))
			$this->_error("Action not permitted.");
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path,
				array(
					SearchFields_Task::ID => new DevblocksSearchCriteria(SearchFields_Task::ID,'=',$path[0])
				)
			);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path);
				break;
		}
	}

	protected function getWorkerAction($path) {
		$worker = parent::getActiveWorker();
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path,
				array(
					SearchFields_Task::ID => new DevblocksSearchCriteria(SearchFields_Task::ID,'=',$in_id),
					SearchFields_Task::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Task::WORKER_ID,'=',$worker->id)
				)
			);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path,
					array(
						SearchFields_Task::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Task::WORKER_ID,'=',$worker->id)
					)
				);
				break;
		}
	}

	protected function putAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tasks']))
			$this->_error("Action not permitted.");
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			$this->_putIdAction($path);
	}
	
	protected function putWorkerAction($path) {
		$worker = parent::getActiveWorker();
		$task = DAO_Task::get($path[0]);
		if(null == $task || $task->worker_id != $worker->id)
			$this->_error("Action not permitted.");
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
			case 'create':
				if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tasks']))
					$this->_error("Action not permitted.");
				$this->_postCreateAction($path);
				break;
			case 'search':
				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tasks']))
					$this->_error("Action not permitted.");
				$this->_postSearchAction($path);
				break;
		}
	}
	
	protected function postWorkerAction($path) {
		$worker = parent::getActiveWorker();
		
		// Actions
		switch(array_shift($path)) {
			case 'create':
				$this->_postCreateAction($path);
				break;
			case 'search':
				$this->_postSearchAction($path,
					array(
						SearchFields_Task::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Task::WORKER_ID,'=',$worker->id)
					)
				);
				break;
		}
	}
	
	protected function deleteAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tasks']))
			$this->_error("Action not permitted.");
		
		// Single DELETE
		if(1==count($path) && is_numeric($path[0]))
			$this->_deleteIdAction($path);
	}
	
	protected function deleteWorkerAction($path) {
		$worker = parent::getActiveWorker();
		$task = DAO_Task::get($path[0]);
		if(null == $task || $task->worker_id != $worker->id)
			$this->_error("Action not permitted.");
				
		// Single DELETE
		if(1==count($path) && is_numeric($path[0]))
			$this->_deleteIdAction($path);
	}
	
	//****
	
	private function _postCreateAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = simplexml_load_string($xmlstr);

		$fields = array();
		
		$flds = SearchFields_Task::getFields();
		unset($flds[DAO_Task::ID]);
		
		if(is_array($flds))
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null)
				continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx_name] = $value;
		}

		if(empty($fields[DAO_Task::SOURCE_EXTENSION])
			|| empty($fields[DAO_Task::SOURCE_ID])
			|| empty($fields[DAO_Task::TITLE]))
				$this->_error("All required fields were not provided.");
		
		$id = DAO_Task::create($fields);

		// Render
		$this->_getIdAction(array($id));		
	}
	
	private function _postSearchAction($path, $params=array()) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		$xml_in = simplexml_load_string($this->getPayload());
		$search_params = SearchFields_Task::getFields();
		
		// Check for params in request
		foreach($search_params as $sp_element => $fld) {
			$sp_element_name = $this->translate($sp_element, true);
			if ($sp_element_name == null) continue;
			@$field_ptr = $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}

		list($tasks, $total) = DAO_Task::search(
			array(),
			$params,
			50,
			$p_page,
			DAO_Task::DUE_DATE,
			true,
			true
		);
		
		$attribs = array(
			'page_results' => count($tasks),
			'total_results' => intval($total)
		);
		
		$this->_renderResults($tasks, $search_params, 'task', 'tasks', $attribs);
	}
	
	private function _getIdAction($path, $params=array()) {
		$in_id = array_shift($path);
		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_Task::search(
			array(),
			$params,
			1,
			0,
			null,
			null,
			false
		);
			
		if(empty($results))
			$this->_error("ID not valid.");

		$this->_renderOneResult($results, SearchFields_Task::getFields(), 'task');
	}
	
	private function _getListAction($path, $params=array()) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		list($tasks,$null) = DAO_Task::search(
			array(),
			$params,
			50,
			$p_page,
			SearchFields_Task::DUE_DATE,
			true,
			false
		);

		$this->_renderResults($tasks, SearchFields_Task::getFields(), 'task', 'tasks');
	}
		
	private function _putIdAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = new SimpleXMLElement($xmlstr);
		
		$in_id = array_shift($path);
		
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($task = DAO_Task::get($in_id)))
			$this->_error("ID not valid.");

		$fields = array();
			
		$flds = SearchFields_Task::getFields();
		unset($flds[DAO_Task::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx_name] = $value;
		}
		
		if(!empty($fields))
			DAO_Task::update($task->id,$fields);

		$this->_getIdAction(array($task->id));
	}
	
	private function _deleteIdAction($path) {
		$in_id = array_shift($path);
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($task = DAO_Task::get($in_id)))
			$this->_error("ID is not valid.");
		
		DAO_Task::delete($task->id);
		
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
};

class Rest_KBArticlesController extends Ch_RestController {
	protected function translate($idx, $dir) {
		$translations = array(
			'kb_id' => 'id',
			'kb_title' => 'title',
			'kb_updated' => 'updated',
			'kb_views' => 'views',
			'kb_format' => 'format',
			'kb_content' => 'content',
		);
		
		if ($dir === true && array_key_exists($idx, $translations))
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
		
	protected function getAction($path,$keychain) {
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path,$keychain);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path,$keychain);
				break;
			case 'tree':
				$this->_getTreeAction($path,$keychain);
				break;
		}
	}

	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
			case 'search':
				$this->_postSearchAction($path,$keychain);
				break;
		}
	}
	
	//****
	
	private function _postSearchAction($path,$keychain) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		$xml_in = simplexml_load_string($this->getPayload());
		$search_params = SearchFields_KbArticle::getFields();
		$params = array();
		
		// Check for params in request
		foreach($search_params as $sp_element => $fld) {
			$sp_element_name = $this->translate($sp_element, true);
			if ($sp_element_name == null) continue;
			@$field_ptr = $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}
		$params[SearchFields_KbArticle::TOP_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys(@$keychain->rights['acl_kb_topics']));
		
		list($results, $total) = DAO_KbArticle::search(
			$params,
			50,
			$p_page,
			SearchFields_KbArticle::ID,
			false,
			true
		);

		$attribs = array(
			'page_results' => count($results),
			'total_results' => intval($total)
		);
		
		$this->_renderResults($results, $search_params, 'article', 'articles', $attribs);
	}
	
	private function _getIdAction($path,$keychain) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_KbArticle::search(
			array(
				SearchFields_KbArticle::ID => new DevblocksSearchCriteria(SearchFields_KbArticle::ID,'=',$in_id),
				SearchFields_KbArticle::TOP_CATEGORY_ID => new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys(@$keychain->rights['acl_kb_topics'])),
			),
			1,
			0,
			null,
			null,
			false
		);
			
		if(empty($results))
			$this->_error("ID not valid.");

		$this->_renderOneResult($results, SearchFields_KbArticle::getFields(), 'article');
	}
	
	private function _getListAction($path,$keychain) {
		@$root = DevblocksPlatform::importGPC($_REQUEST['root'],'integer',0);
		
		// how many, and what page?
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);
		@$limit = DevblocksPlatform::importGPC($_REQUEST['limit'],'integer',10);
		
		// sort field and order
		@$sort = DevblocksPlatform::importGPC($_REQUEST['sort'],'string','');
		$sort_field = SearchFields_KbArticle::ID;
		$sort_asc = true;
		switch($sort) {
			case 'views':
				$sort_field = SearchFields_KbArticle::VIEWS;
				$sort_asc = false;
				break;
			case 'updated':
				$sort_field = SearchFields_KbArticle::UPDATED;
				$sort_asc = false;
				break;
			default:
				$sort_field = SearchFields_KbArticle::ID;
				$sort_asc = true;
		}
		
		$params = array();
		if(0 != $root) $params[] = new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root);
		$params[SearchFields_KbArticle::TOP_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys(@$keychain->rights['acl_kb_topics']));
		
		list($results,$null) = DAO_KbArticle::search(
			$params,
			$limit,
			$p_page,
			$sort_field,
			$sort_asc,
			false
		);

		$this->_renderResults($results, SearchFields_KbArticle::getFields(), 'article', 'articles');
	}
	
	private function _getTreeAction($path,$keychain) {
		@$root = DevblocksPlatform::importGPC($_REQUEST['root'],'integer',0);
		//TODO: verify access permissions to that root node
		
		$cats = DAO_KbCategory::getWhere();
		$tree = DAO_KbCategory::getTreeMap();

		// create breadcrumb trail
		$breadcrumb = array();
		$pid = $root;
		while(0 != $pid) {
			$breadcrumb[$pid] = $cats[$pid]->name;
			$pid = $cats[$pid]->parent_id;
		}
		$breadcrumb[0] = 'Top';
		$breadcrumb = array_reverse($breadcrumb, true);
		
		$xml_out = new SimpleXMLElement("<categories></categories>");
		if(0 == $root)
			$xml_out->addChild('name',"Top");
		else
			$xml_out->addChild('name',htmlspecialchars($cats[$root]->name));
			
		$xml_out->addChild('breadcrumb',htmlspecialchars(serialize($breadcrumb)));
		
		if (is_array($tree[$root]))
			foreach($tree[$root] as $tree_idx => $cat)
				$this->_addSubCategory($tree_idx, $tree, $cats, $xml_out);

		$this->_render($xml_out->asXML());
	}
	
	private function _addSubCategory($tree_idx, $tree, $cats, $xml_out) {
		$category = $xml_out->addChild('category');
		$category->addChild('id', $cats[$tree_idx]->id);
		$category->addChild('parent_id', $cats[$tree_idx]->parent_id);
		$category->addChild('name', htmlspecialchars($cats[$tree_idx]->name));
		$category->addChild('article_count', $tree[$cats[$tree_idx]->parent_id][$tree_idx]);
		if(isset($tree[$tree_idx]) && is_array($tree[$tree_idx]))
			foreach($tree[$tree_idx] as $subtree_idx => $count)
				$this->_addSubCategory($subtree_idx, $tree, $cats, $category);
	}
	
};

class Rest_ParserController extends Ch_RestController {
	
	//****

	protected function getAction($path,$keychain) {
//		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_tickets']))
//			$this->_error("Action not permitted.");
//		
//		// Single GET
//		if(1==count($path) && is_numeric($path[0]))
//			$this->_getIdAction($path);
//		
//		// Actions
//		switch(array_shift($path)) {
//			case 'list':
//				$this->_getListAction($path);
//				break;
//		}
	}

	protected function putAction($path,$keychain) {
//		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
//			$this->_error("Action not permitted.");
//		
//		// Single PUT
//		if(1==count($path) && is_numeric($path[0]))
//			$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
		if(Model_WebapiKey::ACL_FULL != intval(@$keychain->rights['acl_parser']))
			$this->_error("Action not permitted.");
		
		// Actions
		switch(array_shift($path)) {
			case 'parse':
				$this->_postSourceParseAction($path);
				break;
			case 'queue':
				$this->_postSourceQueueAction($path);
				break;
		}
	}
	
	protected function deleteAction($path,$keychain) {
//		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
//			$this->_error("Action not permitted.");
//		
//		// Single DELETE
//		if(1==count($path) && is_numeric($path[0]))
//			$this->_deleteIdAction($path);
	}
	
	//****
	
	private function _postSourceParseAction($path) {
		$xml_in = simplexml_load_string($this->getPayload(), null, LIBXML_NOCDATA);

		@$source = (string) $xml_in->source;
		
		if(empty($source))
			$this->_error("No message source was provided.");
		
//echo("<pre>");print_r($source);echo("</pre>");exit();
		$file = CerberusParser::saveMimeToFile($source);
		$mime = mailparse_msg_parse_file($file);
		$message = CerberusParser::parseMime($mime, $file);
		mailparse_msg_free($mime);
		@unlink($file);
		
		$ticket_id = CerberusParser::parseMessage($message);

		if(null != ($ticket = DAO_Ticket::getTicket($ticket_id))) {
			// [TODO] Denote if ticket is new or reply?
			$xml_out = new SimpleXMLElement("<ticket></ticket>");
			$xml_out->addChild("id", $ticket_id);
			$xml_out->addChild("mask", htmlspecialchars($ticket->mask));
			$this->_render($xml_out->asXML());
			
		} else {
			$this->_error("Message could not be parsed.");
			
		}

	}
	
	private function _postSourceQueueAction($path) {
		$xml_in = simplexml_load_string($this->getPayload(), null, LIBXML_NOCDATA);

		@$source = (string) $xml_in->source;
		
		if(empty($source))
			$this->_error("No message source was provided.");
		
		// Queue up in the new mail directory
		$path = APP_MAIL_PATH . DIRECTORY_SEPARATOR . 'new';
		$file = CerberusParser::saveMimeToFile($source, $path);
		
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
	
};

class Rest_WorkerController extends Ch_RestController {
	// don't return password hashes if we implement worker object access!!!
};

?>
