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

if(class_exists('Extension_PageSection')):
class ChTranslators_SetupPageSection extends Extension_PageSection {
	const ID = 'translators.setup.section.translations';
	
	function render() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$tpl = DevblocksPlatform::getTemplateService();
	
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Translation';
		$defaults->id = View_Translation::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_Translation::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.translators::config/section/index.tpl');
	}
	
	function saveAction() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
		exit;
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

	function showFindStringsPanelAction($model=null) {
		$tpl = DevblocksPlatform::getTemplateService();

		$codes = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('codes', $codes);
		
		$tpl->display('devblocks:cerberusweb.translators::config/ajax/find_strings_panel.tpl');
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
		$defaults->class_name = 'View_Translation';
		$defaults->id = View_Translation::DEFAULT_ID;
			
		// Clear the existing view
		$view = C4_AbstractViewLoader::getView(View_Translation::DEFAULT_ID, $defaults);
		$view->doResetCriteria();
		
		// Set search to untranslated strings that aren't English
		$view->renderSortBy = SearchFields_Translation::STRING_ID;
		$view->renderSortAsc = true;
		$view->addParams(array(
			SearchFields_Translation::STRING_OVERRIDE => new DevblocksSearchCriteria(SearchFields_Translation::STRING_OVERRIDE,DevblocksSearchCriteria::OPER_EQ,''),
			SearchFields_Translation::LANG_CODE => new DevblocksSearchCriteria(SearchFields_Translation::LANG_CODE,DevblocksSearchCriteria::OPER_NEQ,'en_US'),
		), true);
		C4_AbstractViewLoader::setView($view->id, $view);

		self::_clearCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	function showAddLanguagePanelAction($model=null) {
		$tpl = DevblocksPlatform::getTemplateService();

		// Language Names
		$translate = DevblocksPlatform::getTranslationService();
		$locs = $translate->getLocaleStrings();
		$tpl->assign('locales', $locs);

		// Defined languages (from translations)
		$codes = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('codes', $codes);
		
		$tpl->display('devblocks:cerberusweb.translators::config/ajax/add_language_panel.tpl');
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
				if($copy_string instanceof Model_Translation)
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
			$defaults->class_name = 'View_Translation';
			$defaults->id = View_Translation::DEFAULT_ID;
			
			// Clear the existing view
			$view = C4_AbstractViewLoader::getView(View_Translation::DEFAULT_ID, $defaults);
			$view->doResetCriteria();
			
			// Set search to untranslated strings that aren't English
			$view->renderSortBy = SearchFields_Translation::STRING_ID;
			$view->renderSortAsc = true;
			$view->addParams(array(
				SearchFields_Translation::LANG_CODE => new DevblocksSearchCriteria(SearchFields_Translation::LANG_CODE,DevblocksSearchCriteria::OPER_EQ,$add_lang_code),
			), true);
			
			/*
			 * If we didn't copy from another language, only show empty strings
			 * which makes it easier to translate in the GUI.
			 */
			if(empty($copy_lang_code)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_Translation::STRING_OVERRIDE,DevblocksSearchCriteria::OPER_EQ,''));
			}
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
		}

		self::_clearCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	function showImportStringsPanelAction($model=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.translators::config/ajax/import_strings_panel.tpl');
	}

	function saveImportStringsPanelAction() {
		@$import_file = $_FILES['import_file'];

		DAO_Translation::importTmxFile($import_file['tmp_name']);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	function exportTmxAction() {
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Translation';
		$defaults->id = View_Translation::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_Translation::DEFAULT_ID, $defaults);

		// Extract every result from the view
		list($results, $null) = DAO_Translation::search(
			$view->getParams(),
			-1,
			0,
			SearchFields_Translation::STRING_ID,
			true,
			false
		);
		
		// Build TMX outline
		$xml = simplexml_load_string(
			'<?xml version="1.0" encoding="' . LANG_CHARSET_CODE . '"?>'.
			'<!DOCTYPE tmx>'.
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
}
endif;

if(class_exists('Extension_PageMenuItem')):
class ChTranslators_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const ID = 'translators.setup.menu.plugins.translations';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.translators::config/menu_item.tpl');
	}
}
endif;

class View_Translation extends C4_AbstractView implements IAbstractView_Subtotals {
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
		
		$this->addColumnsHidden(array(
			SearchFields_Translation::ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Translation::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Translation::search(
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Translation::LANG_CODE:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Translation::LANG_CODE:
				$codes = DAO_Translation::getDefinedLangCodes();
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Translation', $column, $codes, 'in', 'options[]');
				break;

			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Translation', $column, 't.id');
				}
				
				break;
		}
		
		return $counts;
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
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.translators::config/section/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Translation::STRING_ID:
			case SearchFields_Translation::STRING_DEFAULT:
			case SearchFields_Translation::STRING_OVERRIDE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Translation::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_Translation::LANG_CODE:
				$options = array();
				$langs = DAO_Translation::getDefinedLangCodes();

				if(is_array($langs))
				foreach($langs as $lang_id => $lang) {
					$options[$lang_id] = $lang;
				}
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
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
				$langs = DAO_Translation::getDefinedLangCodes();
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

	function getFields() {
		return SearchFields_Translation::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Translation::STRING_ID:
			case SearchFields_Translation::STRING_DEFAULT:
			case SearchFields_Translation::STRING_OVERRIDE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Translation::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Translation::LANG_CODE:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

