<?php
class ChTranslatorsConfigTab extends Extension_ConfigTab {
	const ID = 'translators.config.tab';
	
	function showTab() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$core_tplpath = dirname(dirname(dirname(__FILE__))) . '/cerberusweb.core/templates/';
		$tpl->assign('core_tplpath', $core_tplpath);

		$tpl->assign('response_uri', 'config/translations');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TranslationView';
		$defaults->id = C4_TranslationView::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(C4_TranslationView::DEFAULT_ID, $defaults);
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_TranslationView::getFields());
		$tpl->assign('view_searchable_fields', C4_TranslationView::getSearchFields());
		
		$tpl->display('file:' . $tpl_path . 'config/translations/index.tpl');
	}
	
	function saveTab() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translators')));
		exit;
	}
	
};

class C4_TranslationView extends C4_AbstractView {
	const DEFAULT_ID = 'translations';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Translations';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Translation::STRING_ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Translation::STRING_OVERRIDE,
			SearchFields_Translation::STRING_ID,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Translation::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Language Names
		$langs = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('langs', $langs);
		
		// For defaulting
		$english_map = DAO_Translation::getMapByLang('en_US');
		$tpl->assign('english_map', $english_map);
		
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.translators/templates/config/translations/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Translation::STRING_ID:
			case SearchFields_Translation::STRING_DEFAULT:
			case SearchFields_Translation::STRING_OVERRIDE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Translation::ID:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Translation::LANG_CODE:
				$langs = DAO_Translation::getDefinedLangCodes(); // [TODO] Cache!
				$tpl->assign('langs', $langs);
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__language.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Translation::LANG_CODE:
				$langs = DAO_Translation::getDefinedLangCodes(); // [TODO] Cache!
				$strings = array();

				foreach($values as $val) {
					if(!isset($langs[$val]))
						continue;
					$strings[] = $langs[$val];
				}
				echo implode(", ", $strings);
				
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Translation::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Translation::ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Translation::ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
//			SearchFields_FeedbackEntry::LOG_DATE => new DevblocksSearchCriteria(SearchFields_FeedbackEntry::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-1 month','now')),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Translation::STRING_ID:
			case SearchFields_Translation::STRING_DEFAULT:
			case SearchFields_Translation::STRING_OVERRIDE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Translation::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			case SearchFields_Translation::LANG_CODE:
				@$lang_ids = DevblocksPlatform::importGPC($_REQUEST['lang_ids'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$lang_ids);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
};

class ChTranslatorsAjaxController extends DevblocksControllerExtension {
	function __construct($manifest) {
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
	
	private function _clearCache() {
		// Reload
		$cache = DevblocksPlatform::getCacheService();
		
	    $langs = DAO_Translation::getDefinedLangCodes();
	    if(is_array($langs) && !empty($langs))
	    foreach($langs as $lang_code => $lang_name) {
	    	$cache->remove(DevblocksPlatform::CACHE_TAG_TRANSLATIONS . '_' . $lang_code);
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
	
//	function writeResponse(DevblocksHttpResponse $response) {
//		if(!$this->isVisible())
//			return;
//	}

	function showFindStringsPanelAction($model=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);

		$codes = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('codes', $codes);
		
		$tpl->display('file:' . $tpl_path . 'translators/ajax/find_strings_panel.tpl');
	}

	function saveFindStringsPanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Make sure we're an active worker
		if(empty($active_worker) || empty($active_worker->id))
			return;

		@$lang_codes = DevblocksPlatform::importGPC($_REQUEST['lang_codes'],'array',array());
		@$lang_actions = DevblocksPlatform::importGPC($_REQUEST['lang_actions'],'array',array());
			
		$strings_en = DAO_Translation::getMapByLang('en_US');

		// Build a hash of all existing strings so we can quickly INSERT/UPDATE check
		$hash = array();
		$all_strings = DAO_Translation::getWhere();
		foreach($all_strings as $s) { /* @var $s Model_TranslationDefault */
			$hash[$s->lang_code.'_'.$s->string_id] = $s;
		}
		unset($all_strings); // free()
		
		// Find all en_US strings that aren't translated
		if(is_array($strings_en) && is_array($lang_codes) && !empty($lang_codes))
		foreach($strings_en as $key => $string) {
			foreach($lang_codes as $idx => $lang_code) {
				@$lang_action = $lang_actions[$idx];
				if(!isset($hash[$lang_code.'_'.$key])) {
					$fields = array(
						DAO_Translation::STRING_ID => $key,
						DAO_Translation::LANG_CODE => $lang_code,
						DAO_Translation::STRING_DEFAULT => '',
						DAO_Translation::STRING_OVERRIDE => (('en_US'==$lang_action) ? $string : ''),
					);
					DAO_Translation::create($fields);
				}
			}
		}
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TranslationView';
		$defaults->id = C4_TranslationView::DEFAULT_ID;
			
		// Clear the existing view
		$view = C4_AbstractViewLoader::getView(C4_TranslationView::DEFAULT_ID, $defaults);
		$view->doResetCriteria();
		
		// Set search to untranslated strings that aren't English
		$view->renderSortBy = SearchFields_Translation::STRING_ID;
		$view->renderSortAsc = true;
		$view->params = array(
			SearchFields_Translation::STRING_OVERRIDE => new DevblocksSearchCriteria(SearchFields_Translation::STRING_OVERRIDE,DevblocksSearchCriteria::OPER_EQ,''),
			SearchFields_Translation::LANG_CODE => new DevblocksSearchCriteria(SearchFields_Translation::LANG_CODE,DevblocksSearchCriteria::OPER_NEQ,'en_US'),
		);
		C4_AbstractViewLoader::setView($view->id, $view);

		self::_clearCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	function showAddLanguagePanelAction($model=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);

		// Language Names
		$translate = DevblocksPlatform::getTranslationService();
		$locs = $translate->getLocaleStrings();
		$tpl->assign('locales', $locs);

		// Defined languages (from translations)
		$codes = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('codes', $codes);
		
		$tpl->display('file:' . $tpl_path . 'translators/ajax/add_language_panel.tpl');
	}

	function saveAddLanguagePanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Make sure we're an active worker
		if(empty($active_worker) || empty($active_worker->id))
			return;
		
		$codes = DAO_Translation::getDefinedLangCodes();
			
		@$add_lang_code = DevblocksPlatform::importGPC($_REQUEST['add_lang_code'],'string','');
		@$copy_lang_code = DevblocksPlatform::importGPC($_REQUEST['copy_lang_code'],'string','');
		@$del_lang_ids = DevblocksPlatform::importGPC($_REQUEST['del_lang_ids'],'array',array());
		
		if(!empty($del_lang_ids)) {
			if(is_array($del_lang_ids))
			foreach($del_lang_ids as $lang_id) {
				DAO_Translation::deleteByLangCodes($lang_id);
			}
		}
		
		// Don't add blanks or the same language twice.
		if(!empty($add_lang_code) && !isset($codes[$add_lang_code])) {
			// English reference strings (to know our scope)
			$english_strings = DAO_Translation::getMapByLang('en_US');
			$copy_strings = array();
			
			// If we have a desired source language for defaults, load it.
			if(!empty($copy_lang_code)) {
				if(0 == strcasecmp('en_US', $copy_lang_code)) {
					$copy_strings = $english_strings;
				} else {
					$copy_strings = DAO_Translation::getMapByLang($copy_lang_code);
				}
			}
			
			// Loop through English strings for new language
			if(is_array($english_strings))
			foreach($english_strings as $string_id => $src_en) { /* @var $src_en Model_Translation */
				$override = '';
				
				// If we have a valid source, copy its override or its default (in that order)
				@$copy_string = $copy_strings[$string_id];
				if(is_a($copy_string,'Model_Translation'))
					$override = 
						!empty($copy_string->string_override)
						? $copy_string->string_override
						: $copy_string->string_default
						;
				
				// Insert the new string as an override.  Only official translations are defaults
				$fields = array(
					DAO_Translation::STRING_ID => $string_id,
					DAO_Translation::LANG_CODE => $add_lang_code,
					DAO_Translation::STRING_DEFAULT => '',
					DAO_Translation::STRING_OVERRIDE => $override,
				);
				DAO_Translation::create($fields);
			}
		}
		
		// If we added a new language then change the view to display it
		if(!empty($add_lang_code)) {
			$defaults = new C4_AbstractViewModel();
			$defaults->class_name = 'C4_TranslationView';
			$defaults->id = C4_TranslationView::DEFAULT_ID;
			
			// Clear the existing view
			$view = C4_AbstractViewLoader::getView(C4_TranslationView::DEFAULT_ID, $defaults);
			$view->doResetCriteria();
			
			// Set search to untranslated strings that aren't English
			$view->renderSortBy = SearchFields_Translation::STRING_ID;
			$view->renderSortAsc = true;
			$view->params = array(
				SearchFields_Translation::LANG_CODE => new DevblocksSearchCriteria(SearchFields_Translation::LANG_CODE,DevblocksSearchCriteria::OPER_EQ,$add_lang_code),
			);
			
			/*
			 * If we didn't copy from another language, only show empty strings 
			 * which makes it easier to translate in the GUI.
			 */ 
			if(empty($copy_lang_code)) {
				$view->params[SearchFields_Translation::STRING_OVERRIDE] = new DevblocksSearchCriteria(SearchFields_Translation::STRING_OVERRIDE,DevblocksSearchCriteria::OPER_EQ,'');
			}
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
		}

		self::_clearCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	function showImportStringsPanelAction($model=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display('file:' . $tpl_path . 'translators/ajax/import_strings_panel.tpl');
	}

	function saveImportStringsPanelAction() {
		@$import_file = $_FILES['import_file'];

		DAO_Translation::importTmxFile($import_file['tmp_name']);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	function exportTmxAction() {
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TranslationView';
		$defaults->id = C4_TranslationView::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(C4_TranslationView::DEFAULT_ID, $defaults);

		// Extract every result from the view
		list($results, $null) = DAO_Translation::search(
			$view->params,
			-1,
			0,
			SearchFields_Translation::STRING_ID,
			true,
			false
		);
		
		// Build TMX outline
		$xml = simplexml_load_string(
			'<?xml version="1.0" encoding="' . LANG_CHARSET_CODE . '"?>'.
			'<!DOCTYPE tmx SYSTEM "tmx14.dtd">'.
			'<tmx version="1.4">'.
			'<body></body>'.
			'</tmx>'
		); /* @var $xml SimpleXMLElement */
		
		$namespaces = $xml->getNamespaces(true);
		
		$codes = array();
		
		// Loop translated strings
		if(is_array($results))
		foreach($results as $result) {
			$string_id = $result[SearchFields_Translation::STRING_ID];
			$lang_code = $result[SearchFields_Translation::LANG_CODE];
			$string_default = $result[SearchFields_Translation::STRING_DEFAULT];
			$string_override = $result[SearchFields_Translation::STRING_OVERRIDE];
			
			$codes[$lang_code] = 1;
			
			$string = (!empty($string_override))
				? $string_override
				: $string_default
				;
			
			// [TODO] Nest multiple <tuv> in a single <tu> parent
			$eTu =& $xml->body->addChild('tu'); /* @var $eTu SimpleXMLElement */
			$eTu->addAttribute('tuid', $string_id);
			$eTuv =& $eTu->addChild('tuv'); /* @var $eTuv SimpleXMLElement */
			$eTuv->addAttribute('xml:lang', $lang_code, 'http://www.w3.org/XML/1998/namespace');
			$eSeg =& $eTuv->addChild('seg', htmlspecialchars($string)); /* @var $eSeg SimpleXMLElement */
		}
		
		$imp = new DOMImplementation;
//		$dtd = $imp->createDocumentType('tmx', '', 'tmx14.dtd');
//		$doc = $imp->createDocument("", "", $dtd);
		$doc = $imp->createDocument("", "");
		$doc->encoding = LANG_CHARSET_CODE;
		$doc->formatOutput = true;
		
		$simplexml = dom_import_simplexml($xml); /* @var $dom DOMElement */
		$simplexml = $doc->importNode($simplexml, true);
		$simplexml = $doc->appendChild($simplexml);

		$filename = "cerb5_lang_" . implode('_', array_keys($codes)) . ".xml";
		
		header("Content-type: text/xml");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		
		echo $doc->saveXML();
	}
	
	function saveViewAction() {
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_ids'],'array',array());
		@$translations = DevblocksPlatform::importGPC($_REQUEST['translations'],'array',array());

		// Save the form strings
		if(is_array($row_ids))
		foreach($row_ids as $idx => $row_id) {
			$fields = array(
				DAO_Translation::STRING_OVERRIDE => $translations[$idx],
			);
			DAO_Translation::update($row_id, $fields);
		}

		self::_clearCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
};

?>