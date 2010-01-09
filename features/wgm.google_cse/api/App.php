<?php
if (class_exists('Extension_ResearchTab')):
class WgmGoogleCSEResearchTab extends Extension_ResearchTab {
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$translate = DevblocksPlatform::getTranslationService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		// Google Search Engines
		$engines = DAO_WgmGoogleCse::getWhere();
		$tpl->assign('engines', $engines);
		
		$tpl->display($tpl_path . 'research_tab/index.tpl');		
	}
}
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class WgmGoogleCSEReplyToolbarButton extends Extension_ReplyToolbarItem {
		function render(CerberusMessage $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('message', $message); /* @var $message CerberusMessage */
			
			$tpl->display('file:' . $tpl_path . 'renderers/reply_button.tpl');
		}
	};
endif;

class WgmGoogleCSEConfigTab extends Extension_ConfigTab {
	const ID = 'wgm.google_cse.config.tab';
	
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(__FILE__)).'/templates/';
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->cache_lifetime = "0";

		$engines = DAO_WgmGoogleCse::getWhere();
		$tpl->assign('engines', $engines);

		$tpl->display('file:' . $this->_TPL_PATH . 'config_tab/index.tpl');
	}
	
	function saveTab() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$name = trim(DevblocksPlatform::importGPC($_POST['name'],'string','New Search Engine'));
		@$url = trim(DevblocksPlatform::importGPC($_POST['url'],'string',''));
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);	

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','wgm.google_cse')));
			return;
		}
	    
		if(!empty($delete)) {
			DAO_WgmGoogleCse::delete($id);
			
		} else {
			// Data massaging
			$token = '';
			
			if(!empty($url)) {
				if(null != ($query_args = parse_url($url,PHP_URL_QUERY))) {
					$args = array();
					parse_str($query_args,$args);
					$token = (isset($args['cx'])) ? $args['cx'] : '';
				}
			}
			
		    $fields = array(
		        DAO_WgmGoogleCse::NAME => (!empty($name) ? $name : 'New Search Engine'),
		        DAO_WgmGoogleCse::URL => $url,
		        DAO_WgmGoogleCse::TOKEN => $token,
		    );
			
			if(empty($id)) { // Create
			    $id = DAO_WgmGoogleCse::create($fields);
				
			} else { // Edit
			    DAO_WgmGoogleCse::update($id,$fields);
			}
			
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','wgm.google_cse')));
	}
};

class WgmGoogleCSEAjaxController extends DevblocksControllerExtension {
	private $_CORE_TPL_PATH = '';
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_CORE_TPL_PATH = APP_PATH . '/features/cerberusweb.core/templates/';
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		parent::__construct($manifest);
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
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // timetracking

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	function showReplyPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		// Google Search Engines
		$engines = DAO_WgmGoogleCse::getWhere();
		$tpl->assign('engines', $engines);

		$tpl->display('file:' . $this->_TPL_PATH . 'ajax/reply_panel.tpl');
	}
	
	function getConfigEngineAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		if(!empty($id)) {
			$engine = DAO_WgmGoogleCse::get($id);
			$tpl->assign('engine', $engine);
		}
		
		$tpl->display('file:' . $this->_TPL_PATH . 'config_tab/edit_engine.tpl');
	}
	
};

class DAO_WgmGoogleCse extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const URL = 'url';
	const TOKEN = 'token';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO wgm_google_cse (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'wgm_google_cse', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_WgmGoogleCse[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, url, token ".
			"FROM wgm_google_cse ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WgmGoogleCse	 */
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
	 * @return Model_WgmGoogleCse[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_WgmGoogleCse();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$object->url = $rs->fields['url'];
			$object->token = $rs->fields['token'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM wgm_google_cse WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class Model_WgmGoogleCse {
	public $id;
	public $name;
	public $url;
	public $token;
};
