<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class ChTranslators_SetupPageSection extends Extension_PageSection {
	const ID = 'translators.setup.section.translations';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Translation');
		$defaults->id = View_Translation::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_Translation::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.translators::config/section/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'save':
					return $this->_configAction_save();
				case 'showFindStringsPanel':
					return $this->_configAction_showFindStringsPanel();
				case 'saveFindStringsPanel':
					return $this->_configAction_saveFindStringsPanel();
				case 'showAddLanguagePanel':
					return $this->_configAction_showAddLanguagePanel();
				case 'saveAddLanguagePanel':
					return $this->_configAction_saveAddLanguagePanel();
				case 'showImportStringsPanel':
					return $this->_configAction_showImportStringsPanel();
				case 'saveImportStringsPanel':
					return $this->_configAction_saveImportStringsPanel();
				case 'exportTmx':
					return $this->_configAction_exportTmx();
				case 'saveView':
					return $this->_configAction_saveView();
			}
		}
		return false;
	}
	
	private function _configAction_save() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	private function _clearCache() {
		// Reload
		$cache = DevblocksPlatform::services()->cache();
		
		$langs = DAO_Translation::getDefinedLangCodes();
		if(is_array($langs) && !empty($langs))
		foreach($langs as $lang_code => $lang_name) {
			$cache->remove(DevblocksPlatform::CACHE_TAG_TRANSLATIONS . '_' . $lang_code);
		}
	}
	
	private function _configAction_showFindStringsPanel($model=null) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$codes = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('codes', $codes);
		
		$tpl->display('devblocks:cerberusweb.translators::config/ajax/find_strings_panel.tpl');
	}
	
	private function _configAction_saveFindStringsPanel() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$lang_codes = DevblocksPlatform::importGPC($_POST['lang_codes'],'array',array());
		@$lang_actions = DevblocksPlatform::importGPC($_POST['lang_actions'],'array',array());
			
		$strings_en = DAO_Translation::getMapByLang('en_US');

		// Build a hash of all existing strings so we can quickly INSERT/UPDATE check
		$hash = array();
		$all_strings = DAO_Translation::getWhere();
		foreach($all_strings as $s) { /* @var $s Model_Translation */
			$hash[$s->lang_code.'_'.$s->string_id] = $s;
		}
		unset($all_strings); // free()
		
		// Find all en_US strings that aren't translated
		if(is_array($strings_en) && is_array($lang_codes) && !empty($lang_codes))
		foreach($strings_en as $key => $model) { /* @var $model Model_Translation */
			foreach($lang_codes as $idx => $lang_code) {
				@$lang_action = $lang_actions[$idx];
				
				if(!isset($hash[$lang_code.'_'.$key])) {
					$fields = array(
						DAO_Translation::STRING_ID => $key,
						DAO_Translation::LANG_CODE => $lang_code,
						DAO_Translation::STRING_DEFAULT => '',
						DAO_Translation::STRING_OVERRIDE => (('en_US'==$lang_action) ? $model->string_default : ''),
					);
					
					DAO_Translation::create($fields);
				}
			}
		}
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Translation');
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

		self::_clearCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	private function _configAction_showAddLanguagePanel($model=null) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Language Names
		$translate = DevblocksPlatform::getTranslationService();
		$locs = $translate->getLocaleStrings();
		$tpl->assign('locales', $locs);

		// Defined languages (from translations)
		$codes = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('codes', $codes);
		
		$tpl->display('devblocks:cerberusweb.translators::config/ajax/add_language_panel.tpl');
	}
	
	private function _configAction_saveAddLanguagePanel() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$codes = DAO_Translation::getDefinedLangCodes();
			
		@$add_lang_code = DevblocksPlatform::importGPC($_POST['add_lang_code'],'string','');
		@$copy_lang_code = DevblocksPlatform::importGPC($_POST['copy_lang_code'],'string','');
		@$del_lang_ids = DevblocksPlatform::importGPC($_POST['del_lang_ids'],'array',array());
		
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
			$defaults = C4_AbstractViewModel::loadFromClass('View_Translation');
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
			
		}

		self::_clearCache();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	private function _configAction_showImportStringsPanel($model=null) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->display('devblocks:cerberusweb.translators::config/ajax/import_strings_panel.tpl');
	}
	
	private function _configAction_saveImportStringsPanel() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$import_file = $_FILES['import_file'];
		
		DAO_Translation::importTmxFile($import_file['tmp_name']);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','translations')));
	}
	
	private function _configAction_exportTmx() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Translation');
		$defaults->id = View_Translation::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView(View_Translation::DEFAULT_ID, $defaults);

		// Extract every result from the view
		list($results,) = DAO_Translation::search(
			$view->view_columns,
			$view->getParams(),
			-1,
			0,
			SearchFields_Translation::STRING_ID,
			true,
			false
		);
		
		$codes = [];
		$strings = [];
		
		// Loop translated strings
		if(is_array($results))
		foreach($results as $result) {
			$string_id = $result[SearchFields_Translation::STRING_ID];
			$lang_code = $result[SearchFields_Translation::LANG_CODE];
			$string_default = $result[SearchFields_Translation::STRING_DEFAULT];
			$string_override = $result[SearchFields_Translation::STRING_OVERRIDE];
			
			if(!isset($codes[$lang_code]))
				$codes[$lang_code] = true;
			
			$string = (!empty($string_override))
				? $string_override
				: $string_default
				;
				
			if(!isset($strings[$string_id]))
				$strings[$string_id] = array();
				
			$strings[$string_id][$lang_code] = $string;
		}
		
		// Build TMX outline
		$xml = simplexml_load_string(
			'<?xml version="1.0" encoding="' . LANG_CHARSET_CODE . '"?>'.
			'<tmx version="1.4">'.
			'<header creationtool="Cerb" creationtoolversion="' . APP_VERSION . '" srclang="en_US" adminlang="en" datatype="unknown" o-tmf="unknown" segtype="sentence" creationid="" creationdate=""></header>'.
			'<body></body>'.
			'</tmx>'
		); /* @var $xml SimpleXMLElement */
		
		$namespaces = $xml->getNamespaces(true);
		
		// Loop translated strings
		foreach($strings as $string_id => $langs) {
			$eTu = $xml->body->addChild('tu'); /* @var $eTu SimpleXMLElement */
			$eTu->addAttribute('tuid', $string_id);
			
			// Fill in blanks
			foreach(array_diff(array_keys($codes), array_keys($langs)) as $lang_code) {
				$langs[$lang_code] = '';
			}
			
			// Create tuple nodes
			foreach($langs as $lang_code => $string) {
				$eTuv = $eTu->addChild('tuv'); /* @var $eTuv SimpleXMLElement */
				$eTuv->addAttribute('xml:lang', $lang_code, 'http://www.w3.org/XML/1998/namespace');
				$eSeg = $eTuv->addChild('seg', htmlspecialchars($string)); /* @var $eSeg SimpleXMLElement */
			}
		}
		
		$imp = new DOMImplementation();
		$dtd = $imp->createDocumentType('tmx', '-//LISA OSCAR:1998//DTD for Translation Memory eXchange//EN', 'tmx14.dtd');

		$doc = $imp->createDocument('', '', $dtd);
		$doc->encoding = LANG_CHARSET_CODE;
		$doc->formatOutput = true;
		
		$simplexml = dom_import_simplexml($xml); /* @var $dom DOMElement */
		$simplexml = $doc->importNode($simplexml, true);
		$simplexml = $doc->appendChild($simplexml);

		$filename = "cerb_lang_" . implode('_', array_keys($codes)) . ".tmx";
		
		header("Content-Type: text/xml");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		
		echo $doc->saveXML();
	}
	
	private function _configAction_saveView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$row_ids = DevblocksPlatform::importGPC($_POST['row_ids'],'array',array());
		@$translations = DevblocksPlatform::importGPC($_POST['translations'],'array',array());

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

class ChTranslators_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const ID = 'translators.setup.menu.plugins.translations';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:cerberusweb.translators::config/menu_item.tpl');
	}
}

class View_Translation extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
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
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Translation::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Translation');
		
		return $objects;
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Translation::LANG_CODE:
					$pass = true;
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
		$context = null; // [TODO]

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Translation::LANG_CODE:
				$codes = DAO_Translation::getDefinedLangCodes();
				$counts = $this->_getSubtotalCountForLanguage();
				break;
		}
		
		return $counts;
	}
	
	private function _getSubtotalCountForLanguage() {
		$field_key = SearchFields_Translation::LANG_CODE;
		$value_oper = DevblocksSearchCriteria::OPER_IN;
		$value_key = 'options[]';
		
		if(false == ($results = $this->_getSubtotalDataForLanguage()))
			return false;
		
		$counts = [];
		
		if(is_array($results))
		foreach($results as $result) {
			$label = $result['label'];
			$key = $label;
			$hits = $result['hits'];

			if(isset($label_map[$result['label']]))
				$label = $label_map[$result['label']];
			
			// Null strings
			if(empty($label)) {
				$label = '(none)';
				if(!isset($counts[$key]))
					$counts[$key] = array(
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => DevblocksSearchCriteria::OPER_IN_OR_NULL,
								'values' => array($value_key => ''),
							),
						'children' => array()
					);
				
			// Anything else
			} else {
				if(!isset($counts[$key]))
					$counts[$key] = array(
						'hits' => $hits,
						'label' => $label,
						'filter' =>
							array(
								'field' => $field_key,
								'oper' => $value_oper,
								'values' => array($value_key => $key),
							),
						'children' => array()
					);
				
			}
		}
		
		return $counts;
	}
	
	private function _getSubtotalDataForLanguage() {
		$db = DevblocksPlatform::services()->database();
		
		$field_key = SearchFields_Translation::LANG_CODE;
		
		$fields = $this->getFields();
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(!method_exists('DAO_Translation','getSearchQueryComponents'))
			return [];
		
		if(!isset($columns[$field_key]))
			$columns[] = $field_key;
		
		$query_parts = call_user_func_array(
			array('DAO_Translation','getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = sprintf("SELECT %s.%s as label, count(*) as hits ", //SQL_CALC_FOUND_ROWS
				$fields[$field_key]->db_table,
				$fields[$field_key]->db_column
			).
			$join_sql.
			$where_sql.
			"GROUP BY label ".
			"ORDER BY hits DESC ".
			"LIMIT 0,250 "
		;
		
		$results = $db->GetArrayReader($sql);
		return $results;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Translation::getFields();
		
		$languages = DAO_Translation::getDefinedLangCodes();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Translation::STRING_DEFAULT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Translation::STRING_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'lang' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Translation::LANG_CODE),
					'examples' => array(
						['type' => 'list', 'values' => $languages],
					)
				),
			'mine' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Translation::STRING_OVERRIDE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'theirs' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Translation::STRING_DEFAULT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'lang':
				$field_key = SearchFields_Translation::LANG_CODE;
				$oper = null;
				$patterns = array();
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $patterns);
				
				$lang_codes = DAO_Translation::getDefinedLangCodes();
				
				$values = array();
				
				foreach($patterns as $pattern) {
					foreach($lang_codes as $lang_code => $lang_label) {
						if(false !== stripos($lang_code . ' ' . $lang_label, $pattern))
							$values[$lang_code] = true;
					}
				}

				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
				break;
		
			case 'ticket.id':
				$field_key = SearchFields_Address::VIRTUAL_TICKET_ID;
				$oper = null;
				$value = null;
				
				if(false == CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value))
					return false;
				
				$value = DevblocksPlatform::sanitizeArray($value, 'int');
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					$value
				);
				break;
				
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Address::VIRTUAL_WATCHERS, $tokens);
				break;
				
			default:
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
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
					$strings[] = DevblocksPlatform::strEscapeHtml($langs[$val]);
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
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

