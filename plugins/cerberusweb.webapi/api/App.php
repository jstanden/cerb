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

//DevblocksPlatform::registerClasses($path. 'api/Extension.php', array(
//    'Extension_UsermeetTool'
//));

class ChRestPlugin extends DevblocksPlugin {
	const PLUGIN_ID = 'cerberusweb.controller.rest';
	
	function load(DevblocksPluginManifest $manifest) {
	}
};

class ChWebApiConfigTab extends Extension_ConfigTab {
	const ID = 'webapi.config.tab';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		$access_keys = DAO_WebapiKey::getWhere();
		$tpl->assign('access_keys', $access_keys);
		
		$tpl->display('file:' . $tpl_path . 'config/index.tpl.php');
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
			@$aclFnr = DevblocksPlatform::importGPC($_REQUEST['aclFnr'.$access_id],'integer',0);
			@$aclOrgs = DevblocksPlatform::importGPC($_REQUEST['aclOrgs'.$access_id],'integer',0);
			@$aclParser = DevblocksPlatform::importGPC($_REQUEST['aclParser'.$access_id],'integer',0);
			@$aclTickets = DevblocksPlatform::importGPC($_REQUEST['aclTickets'.$access_id],'integer',0);
			
			$rights['acl_addresses'] = $aclAddresses;
			$rights['acl_fnr'] = $aclFnr;
			$rights['acl_orgs'] = $aclOrgs;
			$rights['acl_parser'] = $aclParser;
			$rights['acl_tickets'] = $aclTickets;
			
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
	 * @param ADORecordSet $rs
	 * @return Model_WebapiKey[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_WebapiKey();
			$object->id = intval($rs->fields['id']);
			$object->nickname = $rs->fields['nickname'];
			$object->access_key = $rs->fields['access_key'];
			$object->secret_key = $rs->fields['secret_key'];
			$rights = $rs->fields['rights'];
			
			if(!empty($rights)) {
				@$object->rights = unserialize($rights);
			}
			
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM webapi_key WHERE id IN (%s)",$ids_list));
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
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('webapi',ChRestPlugin::PLUGIN_ID);
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$controllers = array(
			'addresses' => 'Rest_AddressesController',
			'fnr' => 'Rest_FnrController',
			'orgs' => 'Rest_OrgsController',
			'parser' => 'Rest_ParserController',
			'tickets' => 'Rest_TicketsController',
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
		
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		$db = DevblocksPlatform::getDatabaseService();
		
		// **** BEGIN AUTH
		@$verb = $_SERVER['REQUEST_METHOD'];
		@$header_date = $_SERVER['HTTP_DATE'];
		@$header_signature = $_SERVER['HTTP_CERB4_AUTH'];
		@$this->_payload = $this->_getRawPost();
		@list($auth_access_key,$auth_signature) = explode(":", $header_signature, 2);
		
		if(!$this->_validateRfcDate($header_date)) {
			$this->_error("Access denied! (Invalid timestamp)");
		}
		
		$stored_keychains = DAO_WebapiKey::getWhere(sprintf("%s = %s",
			DAO_WebapiKey::ACCESS_KEY,
			$db->qstr(str_replace(' ','',$auth_access_key))
		)); /* @var $stored_keychain Model_WebApiKey */

		if(!empty($stored_keychains)) {
			@$stored_keychain = array_shift($stored_keychains);
			@$auth_secret_key = $stored_keychain->secret_key;
			@$auth_rights = $stored_keychain->rights;
			
			$url_parts = parse_url(DevblocksPlatform::getWebPath());
			$url_path = $url_parts['path'];
			$url_query = $this->_sortQueryString($_SERVER['QUERY_STRING']);
			
			$string_to_sign = "$verb\n$header_date\n$url_path\n$url_query\n$this->_payload\n$auth_secret_key\n";
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
		// **** END AUTH
		
		// Figure out our format by looking at the last path argument
		@list($command,$format) = explode('.', array_pop($stack));
		array_push($stack, $command);
		$this->_format = $format;
		
		$method = strtolower($verb) .'Action';
		
		if(method_exists($this,$method)) {
			call_user_func(array(&$this,$method),$stack,$stored_keychain);
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
			header("Content-type: text/javascript;");
			echo Zend_Json::fromXml($xml, true);
		} else {
			header("Content-type: text/xml;");
			echo $xml;
		}
		exit;
	}
	
	protected function _error($message) {
		$out_xml = new SimpleXMLElement('<error></error>');
		$out_xml->addChild('message', $message);
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
	
	protected function _renderResults($results, $fields, $element='element', $container='elements') {
		$xml =& new SimpleXMLElement("<$container/>");

		foreach($results as $result) {
			$e =& $xml->addChild($element);
			foreach($fields as $idx => $fld) {
				if((isset($result[$idx])) && ($idx_name = $this->translate($idx, true)) != null)
					$e->addChild($idx_name, htmlspecialchars($result[$idx]));
			}
		}

		$this->_render($xml->asXML());
	}

	protected function _renderOneResult($results, $fields, $element='element') {
		$xml =& new SimpleXMLElement("<$element/>");
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
			'a_phone' => 'phone',
			'a_num_spam' => null,
			'a_num_nonspam' => null,
			'a_is_banned' => 'is_banned',
			'a_sla_id' => null,
		);
		
		if ($dir === true)
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
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
			@$value = (string) $xml_in->$idx_name;
			if(!empty($value)) {
				$fields[$idx] = $value;
			}
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
			@$field_ptr =& $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}

		list($results, $null) = DAO_Address::search(
			$params,
			50,
			$p_page,
			SearchFields_Address::EMAIL,
			true,
			false
		);
		
		$this->_renderResults($results, $search_params, 'address', 'addresses');
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_Address::search(
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
			@$value = (string) $xml_in->$idx_name;
			if(!empty($value))
				$fields[$idx] = $value;
		}

		// [TODO] This referential integrity should really be up to DAO
		if(!empty($fields[DAO_Address::CONTACT_ORG_ID])) {
			$in_contact_org_id = intval($fields[DAO_Address::CONTACT_ORG_ID]);
			if(null == ($contact_org = DAO_ContactOrg::get($in_contact_org_id))) {
				unset($fields[DAO_Address::CONTACT_ORG_ID]);
			}
		}
		
		if(!empty($fields))
			DAO_Address::update($address->id,$fields);

		$this->_getIdAction(array($address->id));
	}
};

class Rest_OrgsController extends Ch_RestController {
	protected function translate($idx, $dir) {
		$translations = array(
			'c_id' => 'id',
			'c_account_number' => 'account_number',
			'c_name' => 'name',
			'c_street' => 'street',
			'c_city' => 'city',
			'c_province' => 'province',
			'c_postal' => 'postal',
			'c_country' => 'country',
			'c_phone' => 'phone',
			'c_fax' => 'fax',
			'c_website' => 'website',
			'c_created' => null,
			'c_sla_id' => null,
		);
		
		if ($dir === true)
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
		
	
	//****

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
			@$value = (string) $xml_in->$idx_name;
			if(!empty($value)) {
				$fields[$idx] = $value;
			}
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
			@$field_ptr =& $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}

		list($orgs, $null) = DAO_ContactOrg::search(
			$params,
			50,
			$p_page,
			DAO_ContactOrg::NAME,
			true,
			false
		);
		
		$this->_renderResults($orgs, $search_params, 'org', 'orgs');
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_ContactOrg::search(
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
			@$value = (string) $xml_in->$idx_name;
			if(!empty($value))
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
			'tm_name' => 'team_name',
			't_category_id' => null,
			't_created_date' => 'created_date',
			't_updated_date' => 'updated_date',
			't_is_waiting' => 'is_waiting',
			't_is_closed' => 'is_closed',
			't_is_deleted' => 'is_deleted',
			't_first_wrote_address_id' => null,
			't_first_wrote' => 'first_wrote',
			't_last_wrote_address_id' => null,
			't_last_wrote' => 'last_wrote',
			't_last_action_code' => null,
			't_last_worker_id' => 'last_worker_id',
			't_next_action' => 'next_action',
			't_next_worker_id' => 'next_worker_id',
			't_spam_training' => 'spam_training',
			't_spam_score' => 'spam_score',
			't_first_wrote_spam' => null,
			't_first_wrote_nonspam' => null,
			't_interesting_words' => null,
			't_due_date' => 'due_date',
			't_sla_id' => null,
			't_sla_priority' => null,
			't_first_contact_org_id' => null,
			'tm_id' => 'team_id',
		);
		
		if ($dir === true)
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
		
	
	//****

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
		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_tickets']))
			$this->_error("Action not permitted.");
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
		// Actions
		switch(array_shift($path)) {
//			case 'create':
//				$this->_postCreateAction($path);
//				break;
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
	
	private function _postSearchAction($path) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		$xml_in = simplexml_load_string($this->getPayload());
		$search_params = SearchFields_Ticket::getFields();
		$params = array();
		
		// Check for params in request
		foreach($search_params as $sp_element => $fld) {
			$sp_element_name = $this->translate($sp_element, true);
			if ($sp_element_name == null) continue;
			@$field_ptr =& $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}

		list($results, $null) = DAO_Ticket::search(
			array(SearchFields_Ticket::TICKET_ID),
			$params,
			50,
			$p_page,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			false,
			false
		);
		
		$this->_renderResults($results, $search_params, 'ticket', 'tickets');
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
	
	private function _getListAction($path) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		@$p_group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string','');		
		@$p_bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$p_is_closed = DevblocksPlatform::importGPC($_REQUEST['is_closed'],'string','');
		@$p_is_deleted = DevblocksPlatform::importGPC($_REQUEST['is_deleted'],'string','');
		
		$params = array();

		// Group
		if(0 != strlen($p_group_id))
			$params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'eq',intval($p_group_id));
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
			@$value = (string) $xml_in->$idx_name;
			if(!empty($value))
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
		$xml_in = simplexml_load_string($this->getPayload());

		@$source = (string) $xml_in->source;
		
		if(empty($source))
			$this->_error("No message source was provided.");
		
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
			$xml_out->addChild("mask", $ticket->mask);
			$this->_render($xml_out->asXML());
			
		} else {
			$this->_error("Message could not be parsed.");
			
		}

	}
	
	private function _postSourceQueueAction($path) {
		$xml_in = simplexml_load_string($this->getPayload());

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

class Rest_FnrController extends Ch_RestController {
	
	//****

	protected function getAction($path,$keychain) {
		if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_fnr']))
			$this->_error("Action not permitted.");
		
		// Single GET
//		if(1==count($path) && is_numeric($path[0]))
//			$this->_getIdAction($path);
		
		// Actions
		switch(array_shift($path)) {
			case 'search':
				$this->_getSearchAction($path);
				break;
			case 'topics':
				switch(array_shift($path)) {
					case 'list':
						$this->_getTopicsListAction($path);
						break;
				}
				break;
		}
	}

	protected function putAction($path,$keychain) {
//		if(Model_WebapiKey::ACL_FULL!=intval($keychain->rights['acl_tickets']))
//			$this->_error("Action not permitted.");
//		
//		// Single PUT
//		if(1==count($path) && is_numeric($path[0]))
//			$this->_putIdAction($path);
	}
	
	protected function postAction($path,$keychain) {
//		if(Model_WebapiKey::ACL_FULL != intval(@$keychain->rights['acl_parser']))
//			$this->_error("Action not permitted.");
//		
//		// Actions
//		switch(array_shift($path)) {
//			case 'parse':
//				$this->_postSourceParseAction($path);
//				break;
//			case 'queue':
//				$this->_postSourceQueueAction($path);
//				break;
//		}
	}
	
	protected function deleteAction($path,$keychain) {
//		if(Model_WebapiKey::ACL_FULL!=intval($keychain->rights['acl_tickets']))
//			$this->_error("Action not permitted.");
//		
//		// Single DELETE
//		if(1==count($path) && is_numeric($path[0]))
//			$this->_deleteIdAction($path);
	}
	
	//****
	
	private function _getTopicsListAction($path) {
		$topics = DAO_FnrTopic::getWhere();

		$xml_out = new SimpleXMLElement("<topics></topics>");
		
		foreach($topics as $topic_id => $topic) { /* @var $topic Model_FnrTopic */
			$eTopic = $xml_out->addChild('topic');
			$eTopic->addChild('id', $topic->id);
			$eTopic->addChild('name', $topic->name);

			$eResources = $eTopic->addChild('resources');
			$resources = $topic->getResources();

			foreach($resources as $resource) { /* @var $resource Model_FnrExternalResource */
				$eResource = $eResources->addChild('resource');
				$eResource->addChild('id', $resource->id);
				$eResource->addChild('name', $resource->name);
				$eResource->addChild('topic_id', $resource->topic_id);
//				$eResource->addChild('url', $resource->url);
			}
		}
		
		$this->_render($xml_out->asXML());
	}
	
	private function _getSearchAction($path) {
		@$p_query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		@$p_resources = DevblocksPlatform::importGPC($_REQUEST['resources'],'string','');
		
		$resource_where = null;
		
		// Specific topics only?
		if(!empty($p_resources)) {
			$db = DevblocksPlatform::getDatabaseService();
			$resource_ids = DevblocksPlatform::parseCsvString($p_resources);
			if(!empty($resource_ids)) {
				$resource_where = sprintf("%s IN (%s)",
					DAO_FnrExternalResource::ID,
					$db->qstr(implode(',', $resource_ids))
				);
			}
		}

		$resources = DAO_FnrExternalResource::getWhere($resource_where);
		
		$feeds = Model_FnrExternalResource::searchResources(
			$resources,
			$p_query
		);
		
		$xml_out = new SimpleXMLElement("<resources></resources>");
		
		foreach($feeds as $matches) {
			$eMatch = $xml_out->addChild("resource");
			$eMatch->addChild('name', $matches['name']);
			$eMatch->addChild('topic', $matches['topic_name']);
			$eMatch->addChild('link', $matches['feed']->link);
			$eResults = $eMatch->addChild("results");
			
			foreach($matches['feed'] as $item) {
				$eResult = $eResults->addChild("result");
				
				if($item instanceof Zend_Feed_Entry_Rss) {
					$eResult->addChild('title', (string) $item->title());
					$eResult->addChild('link', (string) $item->link());
					$eResult->addChild('date', (string) $item->pubDate());
					$eResult->addChild('description', (string) $item->description());
					
				} elseif($item instanceof Zend_Feed_Atom) {
					$eResult->addChild('title', (string) $item->title());
					$eResult->addChild('link', (string) $item->link['href']);
					$eResult->addChild('date', (string) $item->published());
					$eResult->addChild('description', (string) $item->summary());
				}
			}
		}
		
		$this->_render($xml_out->asXML());
	}
	
};

?>