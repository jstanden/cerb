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

class Page_Profiles extends CerberusPageExtension {
	const ID = 'cerberusweb.page.profiles';
	
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == (CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		@array_shift($stack); // profiles
		@$section_uri = array_shift($stack);

		if(empty($section_uri))
			$section_uri = 'worker';
		
		// Subpage
		$subpage = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		$tpl->assign('subpage', $subpage);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/index.tpl');
	}
	
	static function renderProfile($context, $context_id, $path=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		// Context
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		// Model
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		$tpl->assign('record', $record);
		
		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $record, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Interactions
		
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
		
		// Active tab
		
		if(!empty($path)) {
			$tpl->assign('tab_selected', array_shift($path));
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/profile.tpl');
	}
	
	function handleSectionActionAction() {
		// GET has precedence over POST
		@$section_uri = DevblocksPlatform::importGPC(isset($_GET['section']) ? $_GET['section'] : $_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function configTabsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		if(!$active_worker->is_superuser)
			return;
		
		$tpl->assign('context', $context);
		
		$profile_tabs_available = DAO_ProfileTab::getByContext($context);
		$profile_tabs_enabled = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'profile:tabs:' . $context, [], true);
		
		// Sort enabled tabs first by dragged rank, disabled lexicographically
		usort($profile_tabs_available, function($a, $b) use ($profile_tabs_enabled) {
			/* @var $a Model_ProfileTab */
			/* @var $b Model_ProfileTab */
			
			if(false === @$a_pos = array_search($a->id, $profile_tabs_enabled))
				$a_pos = PHP_INT_MAX;
			
			if(false === (@$b_pos = array_search($b->id, $profile_tabs_enabled)))
				$b_pos = PHP_INT_MAX;
			
			if($a_pos == $b_pos) {
				if($a_pos == PHP_INT_MAX)
					return strcmp($a->name, $b->name);
				
				return 0;
			}
			
			return $a_pos < $b_pos ? -1 : 1;
		});
		
		$tpl->assign('profile_tabs_available', $profile_tabs_available);
		$tpl->assign('profile_tabs_enabled', $profile_tabs_enabled);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/config_tabs.tpl');
	}
	
	function configTabsSaveJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$profile_tabs = DevblocksPlatform::importGPC($_REQUEST['profile_tabs'],'array',[]);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return json_encode(false);
		
		DevblocksPlatform::setPluginSetting('cerberusweb.core', 'profile:tabs:' . $context, $profile_tabs, true);
		
		return json_encode(true);
	}
	
	function renderWidgetConfigAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'], 'string', '');
		
		if(false == ($extension = Extension_ProfileWidget::get($extension_id)))
			return;
		
		$model = new Model_ProfileWidget();
		$model->extension_id = $extension_id;
		
		$extension->renderConfig($model);
	}

	function showProfileTabAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		if(false == ($profile_tab = DAO_ProfileTab::get($tab_id)))
			return;
		
		if(false == ($extension = $profile_tab->getExtension()))
			return;
		
		$extension->showTab($profile_tab, $context, $context_id);
	}
	
	function handleProfileTabActionAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'integer',0);
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');
		
		if(false == ($profile_tab = DAO_ProfileTab::get($tab_id)))
			return;
		
		if(false == ($extension = $profile_tab->getExtension()))
			return;
		
		if($extension instanceof Extension_ProfileTab && method_exists($extension, $action.'Action')) {
			call_user_func_array([$extension, $action.'Action'], [$profile_tab]);
		}
	}
	
	function handleProfileWidgetActionAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'],'integer',0);
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');
		
		if(false == ($profile_widget = DAO_ProfileWidget::get($widget_id)))
			return;
		
		if(false == ($extension = $profile_widget->getExtension()))
			return;
		
		if($extension instanceof Extension_ProfileWidget && method_exists($extension, $action.'Action')) {
			call_user_func_array([$extension, $action.'Action'], [$profile_widget]);
		}
	}
	
	static function getProfilePropertiesCustomFields($context, $values=null) {
		$custom_fields = DAO_CustomField::getByContext($context, false);
		$properties = [];
		
		foreach($custom_fields as $cf_id => $cfield) {
			if($cfield->custom_fieldset_id != 0)
				continue;
			
			if(is_array($values) && !isset($values[$cf_id]))
				continue;
		
			$properties['cf_' . $cf_id] = [
				'id' => $cf_id,
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => @$values[$cf_id],
				'params' => @$cfield->params ?: [],
			];
		}
		
		return $properties;
	}
	
	static function getProfilePropertiesCustomFieldsets($context, $context_id, $values=[], $return_empty=false) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$custom_fields = DAO_CustomField::getByContext($context);
		$custom_fieldsets = DAO_CustomFieldset::getByContext($context);
		
		$properties = [];
		
		if(is_array($custom_fieldsets))
		foreach($custom_fieldsets as $custom_fieldset) { /* @var $custom_fieldset Model_CustomFieldset */
			if(!Context_CustomFieldset::isReadableByActor($custom_fieldset, $active_worker))
				continue;
		
			$cf_group_fields = $custom_fieldset->getCustomFields();
			$cf_group_props = [];
			
			if(is_array($cf_group_fields))
			foreach($cf_group_fields as $cf_group_field_id => $cf_group_field) {
				if(!isset($custom_fields[$cf_group_field_id]))
					continue;
				
				@$value = (is_array($values) && array_key_exists($cf_group_field->id, $values)) 
					? $values[$cf_group_field->id]
					: null 
					;
				
				if(!$return_empty && is_null($value))
					continue;
				
				$cf_group_key = 'cf_' . $cf_group_field_id;
				
				$cf_group_props[$cf_group_key] = [
					'id' => $cf_group_field_id,
					'label' => $cf_group_field->name,
					'type' => $cf_group_field->type,
					'value' => $value,
				];
				
				// Include parameters for abstract handling
				if(!empty($cf_group_field->params))
					$cf_group_props[$cf_group_key]['params'] = $cf_group_field->params;
			}
			
			if(!empty($cf_group_props))
				$properties[$custom_fieldset->id] = [
					'model' => $custom_fieldset,
					'properties' => $cf_group_props,
				];
		}
		
		return $properties;
	}
	
	static function getTimelineJson($models, $is_ascending=true, $start_index=null) {
		$json = array(
			'objects' => [],
			'length' => count($models),
			'last' => 0,
			'index' => 0,
			'context' => '',
			'context_id' => 0,
		);
		
		foreach($models as $model) {
			if($model instanceof Model_Comment) {
				$context = CerberusContexts::CONTEXT_COMMENT;
				$object = array('context' => $context, 'context_id' => $model->id);
				$json['objects'][] = $object;
			} elseif($model instanceof Model_Message) {
				$context = CerberusContexts::CONTEXT_MESSAGE;
				$object = array('context' => $context, 'context_id' => $model->id);
				$json['objects'][] = $object;
			}
		}
		
		if(isset($json['objects']) && is_array($json['objects'])) {
			// Move to the end
			end($json['objects']);
			
			if(is_null($start_index) || !isset($json['objects'][$start_index])) {
				$start_index = key($json['objects']);
			}
			
			if(!is_null($start_index) && false != ($object = $json['objects'][$start_index])) {
				$json['last'] = key($json['objects']);
				$json['index'] = $start_index;
				$json['context'] = $object['context'];
				$json['context_id'] = $object['context_id'];
			}
		}
		
		return json_encode($json);
	}
};

class ProfileTab_Dashboard extends Extension_ProfileTab {
	const ID = 'cerb.profile.tab.dashboard';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function renderConfig(Model_ProfileTab $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('tab', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/dashboard/config.tpl');
	}
	
	function saveConfig(Model_ProfileTab $model) {
	}

	function showTab(Model_ProfileTab $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$widgets = DAO_ProfileWidget::getByTab($model->id);
		
		@$layout = $model->extension_params['layout'] ?: '';
		
		$zones = [
			'content' => [],
		];
		
		switch($layout) {
			case 'sidebar_left':
				$zones = [
					'sidebar' => [],
					'content' => [],
				];
				break;
				
			case 'sidebar_right':
				$zones = [
					'content' => [],
					'sidebar' => [],
				];
				break;
		}

		// Sanitize zones
		foreach($widgets as $widget_id => $widget) {
			if(array_key_exists($widget->zone, $zones)) {
				$zones[$widget->zone][$widget_id] = $widget;
				continue;
			}
			
			// If the zone doesn't exist, drop the widget into the first zone
			$zones[key($zones)][$widget_id] = $widget;
		}
		
		$tpl->assign('layout', $layout);
		$tpl->assign('zones', $zones);
		$tpl->assign('model', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/dashboard/tab.tpl');
	}
	
	function renderWidgetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'], 'bool', false);
		@$refresh_options = DevblocksPlatform::importGPC($_REQUEST['options'], 'array', []);
		
		if(false == ($widget = DAO_ProfileWidget::get($id)))
			return;
		
		if(false == ($extension = $widget->getExtension()))
			return;
		
		// If full, we also want to replace the container
		if($full) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('widget', $widget);
			
			if(false == ($widget->getProfileTab()))
				return;
			
			$tpl->assign('context', $context);
			$tpl->assign('context_id', $context_id);
			$tpl->assign('full', true);
			
			$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl');
			
		} else {
			$extension->render($widget, $context, $context_id, $refresh_options);
		}
	}
	
	function reorderWidgetsAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'], 'integer', 0);
		@$zones = DevblocksPlatform::importGPC($_REQUEST['zones'], 'array', []);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		$widgets = DAO_ProfileWidget::getByTab($tab_id);
		
		// Sanitize widget IDs
		foreach($zones as &$zone) {
			$zone = array_values(array_intersect($zone, array_keys($widgets)));
		}
		
		DAO_ProfileWidget::reorder($zones);
	}
	
	function getPlaceholderToolbarForTabAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(false == ($tab = DAO_ProfileTab::get($tab_id))) {
			return;
		}
		
		$labels = $values = [];
		
		// Record dictionary
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext($tab->context, null, $merge_labels, $merge_values, '', true);
		CerberusContexts::merge('record_', 'Record ', $merge_labels, $merge_values, $labels, $values);
		
		// Merge in the widget dictionary
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, null, $merge_labels, $merge_values, '', true);
		CerberusContexts::merge('widget_', 'Widget ', $merge_labels, $merge_values, $labels, $values);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/dashboard/toolbar.tpl');
	}
}

class ProfileTab_PortalConfigure extends Extension_ProfileTab {
	const ID = 'cerb.profile.tab.portal.configure';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function renderConfig(Model_ProfileTab $model) {
	}
	
	function saveConfig(Model_ProfileTab $model) {
	}
	
	function showTab(Model_ProfileTab $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// [TODO] Must be an admin to see/use this tab
		if(!$active_worker->is_superuser)
			return;
		
		if(false == ($portal = DAO_CommunityTool::get($context_id)))
			return;
		
		$tpl->assign('community_tool', $portal);
		
		$tpl->assign('page_context', $context);
		$tpl->assign('page_context_id', $context_id);
		
		if(false != ($extension = $portal->getExtension()))
			$tpl->assign('extension', $extension);
		
		$extension->configure($portal);
	}
}

class ProfileTab_PortalDeploy extends Extension_ProfileTab {
	const ID = 'cerb.profile.tab.portal.deploy';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function renderConfig(Model_ProfileTab $model) {
	}
	
	function saveConfig(Model_ProfileTab $model) {
	}

	function showTab(Model_ProfileTab $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$url_writer = DevblocksPlatform::services()->url();
		
		if(false == ($portal = DAO_CommunityTool::get($context_id)))
			return;
		
		if(false != ($extension = $portal->getExtension()))
			$tpl->assign('extension', $extension);
		
		$tpl->assign('portal', $portal);
			
		// Built-in
		
		$url = $url_writer->write('c=portal&uri=' . $portal->uri, true, false);
		$tpl->assign('url', $url);
		
		// Pure PHP reverse proxy
		
		// Install
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=portal&a='.$portal->code, true);
		$url_parts = parse_url($url);
		
		$host = $url_parts['host'];
		$port = isset($url_parts['port']) ? $url_parts['port'] : ($url_writer->isSSL() ? 443 : 80);
		$base = substr(DEVBLOCKS_WEBPATH,0,-1); // consume trailing
		$path = substr($url_parts['path'],strlen(DEVBLOCKS_WEBPATH)-1); // consume trailing slash

		@$parts = explode('/', $path);
		if($parts[1]=='index.php') // 0 is null from /part1/part2 paths.
			unset($parts[1]);
		$path = implode('/', $parts);
		
		$tpl->assign('host', $host);
		$tpl->assign('is_ssl', ($url_writer->isSSL() ? 1 : 0));
		$tpl->assign('port', $port);
		$tpl->assign('base', $base);
		$tpl->assign('path', $path);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/portal/deploy.tpl');
	}
}

class ProfileTab_WorkerSettings extends Extension_ProfileTab {
	const ID = 'cerb.profile.tab.worker.settings';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function renderConfig(Model_ProfileTab $model) {
	}
	
	function saveConfig(Model_ProfileTab $model) {
		//$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/config.tpl');
	}

	function showTab(Model_ProfileTab $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		
		if($context != CerberusContexts::CONTEXT_WORKER)
			return;
		
		@$worker_id = $context_id;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
			return;
		
		if(false == ($worker = DAO_Worker::get($worker_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('tab', $model);
		$tpl->assign('worker', $worker);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/settings.tpl');
	}
	
	function showSettingsSectionTabAction(Model_ProfileTab $model) {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'], 'string', null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
			return;
		
		if(false == ($worker = DAO_Worker::get($worker_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('worker', $worker);
		$tpl->assign('tab', $model);
		
		switch($tab) {
			case 'profile':
				$prefs = [];
				$prefs['assist_mode'] = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
				$prefs['keyboard_shortcuts'] = intval(DAO_WorkerPref::get($worker->id, 'keyboard_shortcuts', 1));
				$tpl->assign('prefs', $prefs);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/profile.tpl');
				break;
				
			case 'pages':
				$page_ids = DAO_WorkerPref::getAsJson($worker->id, 'menu_json', '[]');
				
				if($page_ids) {
					$pages = DAO_WorkspacePage::getIds($page_ids);
					$tpl->assign('pages', $pages);
				}
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/pages.tpl');
				break;
				
			case 'availability':
				$prefs = [];
				$prefs['availability_calendar_id'] = intval($worker->calendar_id);
				$prefs['time_format'] = $worker->time_format ?: DevblocksPlatform::getDateTimeFormat();
				$tpl->assign('prefs', $prefs);
				
				// Availability
				$calendars = DAO_Calendar::getAll();
				$tpl->assign('calendars', $calendars);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/availability.tpl');
				break;
				
			case 'localization':
				$date_service = DevblocksPlatform::services()->date();
				
				$prefs = [];
				$prefs['time_format'] = $worker->time_format ?: DevblocksPlatform::getDateTimeFormat();
				$tpl->assign('prefs', $prefs);
				
				// Timezones
				$tpl->assign('timezones', $date_service->getTimezones());
				@$server_timezone = DevblocksPlatform::getTimezone();
				$tpl->assign('server_timezone', $server_timezone);
				
				// Languages
				$langs = DAO_Translation::getDefinedLangCodes();
				$tpl->assign('langs', $langs);
				$tpl->assign('selected_language', $worker->language ?: 'en_US');
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/localization.tpl');
				break;
				
			case 'mail':
				$prefs = [];
				$prefs['mail_always_read_all'] = DAO_WorkerPref::get($worker->id,'mail_always_read_all',0);
				$prefs['mail_disable_html_display'] = DAO_WorkerPref::get($worker->id,'mail_disable_html_display',0);
				$prefs['mail_reply_html'] = DAO_WorkerPref::get($worker->id,'mail_reply_html',0);
				$prefs['mail_reply_textbox_size_auto'] = DAO_WorkerPref::get($worker->id,'mail_reply_textbox_size_auto',0);
				$prefs['mail_reply_textbox_size_px'] = DAO_WorkerPref::get($worker->id,'mail_reply_textbox_size_px',300);
				$prefs['mail_reply_button'] = DAO_WorkerPref::get($worker->id,'mail_reply_button',0);
				$prefs['mail_reply_format'] = DAO_WorkerPref::get($worker->id,'mail_reply_format','');
				$prefs['mail_status_compose'] = DAO_WorkerPref::get($worker->id,'compose.status','waiting');
				$prefs['mail_status_reply'] = DAO_WorkerPref::get($worker->id,'mail_status_reply','waiting');
				$prefs['mail_signature_pos'] = DAO_WorkerPref::get($worker->id,'mail_signature_pos',2);
				$tpl->assign('prefs', $prefs);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/mail.tpl');
				break;
				
			case 'search':
				// Search
				$search_contexts = Extension_DevblocksContext::getAll(false, ['search']);
				$tpl->assign('search_contexts', $search_contexts);
				
				$search_favorites = DAO_WorkerPref::getAsJson($worker->id, 'search_favorites_json', '[]');
				$search_favorites = array_flip($search_favorites);
				$tpl->assign('search_favorites', $search_favorites);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/search.tpl');
				break;
				
			case 'security':
				// Secret questions
				
				$secret_questions_json = DAO_WorkerPref::get($worker->id, 'login.recover.secret_questions', null);
				
				if(false !== ($secret_questions = json_decode($secret_questions_json, true)) && is_array($secret_questions)) {
					$tpl->assign('secret_questions', $secret_questions);
				}
				
				// MFA
				if(!$worker->is_mfa_required) {
					$is_mfa_enabled = !is_null(DAO_WorkerPref::get($worker_id, 'mfa.totp.seed', null));
					$tpl->assign('is_mfa_enabled', $is_mfa_enabled);
					
					if(!$is_mfa_enabled) {
						$seed = DevblocksPlatform::services()->mfa()->generateMultiFactorOtpSeed(24);
						$tpl->assign('seed', $seed);
					}
				}
				
				// Template
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/security.tpl');
				break;
				
			case 'sessions':
				// View
				$defaults = C4_AbstractViewModel::loadFromClass('View_DevblocksSession');
				$defaults->id = 'workerprefs_sessions';
				
				$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
				
				$view->is_ephemeral = true;
				
				$view->addParamsRequired(array(
					SearchFields_DevblocksSession::USER_ID => new DevblocksSearchCriteria(SearchFields_DevblocksSession::USER_ID, '=', $worker->id),
				));
				
				$tpl->assign('view', $view);
				$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
				break;
				
			case 'watchers':
				// Activities
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$tpl->assign('activities', $activities);
				
				$dont_notify_on_activities = WorkerPrefs::getDontNotifyOnActivities($worker->id);
				$tpl->assign('dont_notify_on_activities', $dont_notify_on_activities);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/watchers.tpl');
				break;
		}
	}
	
	function saveSettingsSectionTabJsonAction(Model_ProfileTab $model) {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'], 'string', null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			// ACL
			if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
				throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this worker.");
			
			if(false == ($worker = DAO_Worker::get($worker_id)))
				throw new Exception_DevblocksAjaxValidationError("This worker record does not exist.");
			
			switch($tab) {
				case 'profile':
					@$gender = DevblocksPlatform::importGPC($_REQUEST['gender'],'string');
					@$location = DevblocksPlatform::importGPC($_REQUEST['location'],'string');
					@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string');
					@$mobile = DevblocksPlatform::importGPC($_REQUEST['mobile'],'string');
					@$dob = DevblocksPlatform::importGPC($_REQUEST['dob'],'string');
					@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'],'string');
					
					$worker_fields = [];
					
					$dob_ts = null;
					
					if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
						$dob_ts = null;
					
					// Account info
					
					$worker_fields[DAO_Worker::LOCATION] = $location;
					$worker_fields[DAO_Worker::PHONE] = $phone;
					$worker_fields[DAO_Worker::MOBILE] = $mobile;
					$worker_fields[DAO_Worker::DOB] = (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts);
					
					if(in_array($gender, array('M','F','')))
						$worker_fields[DAO_Worker::GENDER] = $gender;
					
					$error = null;
					
					// Validate
					if(!DAO_Worker::validate($worker_fields, $error, $worker->id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Update
					if(!empty($worker_fields))
						DAO_Worker::update($worker->id, $worker_fields);
					
					@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);
					
					@$keyboard_shortcuts = DevblocksPlatform::importGPC($_REQUEST['keyboard_shortcuts'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);
					
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_WORKER, $worker->id, $avatar_image);
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'pages':
					@$page_ids = DevblocksPlatform::importGPC($_REQUEST['pages'],'array:integer',[]);
					
					if(false != ($pages = DAO_WorkspacePage::getIds($page_ids))) {
						if(!Context_WorkspacePage::isReadableByActor($pages, $worker))
							throw new Exception_DevblocksAjaxValidationError(
								sprintf("%s can't view a selected workspace page.",
									$worker->getName()
								)
							);
					}
					
					DAO_WorkerPref::setAsJson($worker->id, 'menu_json', $page_ids);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					break;
					
				case 'availability':
					@$availability_calendar_id = DevblocksPlatform::importGPC($_REQUEST['availability_calendar_id'],'integer',0);
					
					$worker_fields = [];
					$worker_fields[DAO_Worker::CALENDAR_ID] = $availability_calendar_id;
					
					// Validate
					if(!DAO_Worker::validate($worker_fields, $error, $worker->id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Update
					if(!empty($worker_fields))
						DAO_Worker::update($worker->id, $worker_fields);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					break;
					
				case 'localization':
					@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
					@$time_format = DevblocksPlatform::importGPC($_REQUEST['time_format'],'string',null);
					@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
					
					$worker_fields = [];
					
					$worker_fields[DAO_Worker::LANGUAGE] = $lang_code;
					$worker_fields[DAO_Worker::TIME_FORMAT] = $time_format;
					$worker_fields[DAO_Worker::TIMEZONE] = $timezone;
					
					// Validate
					if(!DAO_Worker::validate($worker_fields, $error, $worker->id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Update
					if(!empty($worker_fields))
						DAO_Worker::update($worker->id, $worker_fields);
					
					// Update this session?
					if($worker->id == $active_worker->id) {
						$_SESSION['locale'] = $lang_code;
						$_SESSION['timezone'] = $timezone;
					
						DevblocksPlatform::setLocale($lang_code);
						DevblocksPlatform::setTimezone($timezone);
					}
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					break;
					
				case 'mail':
					@$mail_disable_html_display = DevblocksPlatform::importGPC($_REQUEST['mail_disable_html_display'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_disable_html_display', $mail_disable_html_display);
					
					@$mail_always_read_all = DevblocksPlatform::importGPC($_REQUEST['mail_always_read_all'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_always_read_all', $mail_always_read_all);
					
					@$mail_reply_html = DevblocksPlatform::importGPC($_REQUEST['mail_reply_html'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_html', $mail_reply_html);
					
					@$mail_reply_textbox_size_px = DevblocksPlatform::importGPC($_REQUEST['mail_reply_textbox_size_px'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_textbox_size_px', max(100, min(2000, $mail_reply_textbox_size_px)));
					
					@$mail_reply_textbox_size_auto = DevblocksPlatform::importGPC($_REQUEST['mail_reply_textbox_size_auto'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_textbox_size_auto', $mail_reply_textbox_size_auto);
					
					@$mail_reply_button = DevblocksPlatform::importGPC($_REQUEST['mail_reply_button'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_button', $mail_reply_button);
					
					@$mail_reply_format = DevblocksPlatform::importGPC($_REQUEST['mail_reply_format'],'string','');
					DAO_WorkerPref::set($worker->id, 'mail_reply_format', $mail_reply_format);
					
					@$mail_signature_pos = DevblocksPlatform::importGPC($_REQUEST['mail_signature_pos'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_signature_pos', $mail_signature_pos);
					
					@$mail_status_compose = DevblocksPlatform::importGPC($_REQUEST['mail_status_compose'],'string','waiting');
					DAO_WorkerPref::set($worker->id, 'compose.status', $mail_status_compose);
					
					@$mail_status_reply = DevblocksPlatform::importGPC($_REQUEST['mail_status_reply'],'string','waiting');
					DAO_WorkerPref::set($worker->id, 'mail_status_reply', $mail_status_reply);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					break;
					
				case 'search':
					@$search_favorites = DevblocksPlatform::importGPC($_REQUEST['search_favorites'],'array',[]);
					DAO_WorkerPref::setAsJson($worker->id, 'search_favorites_json', $search_favorites);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					break;
					
				case 'security':
					// Secret questions
					
					@$q = DevblocksPlatform::importGPC($_REQUEST['sq_q'], 'array', array('','',''));
					@$h = DevblocksPlatform::importGPC($_REQUEST['sq_h'], 'array', array('','',''));
					@$a = DevblocksPlatform::importGPC($_REQUEST['sq_a'], 'array', array('','',''));
					
					$secret_questions = array(
						array('q'=>$q[0], 'h'=>$h[0], 'a'=>$a[0]),
						array('q'=>$q[1], 'h'=>$h[1], 'a'=>$a[1]),
						array('q'=>$q[2], 'h'=>$h[2], 'a'=>$a[2]),
					);
					
					DAO_WorkerPref::set($worker->id, 'login.recover.secret_questions', json_encode($secret_questions));
					
					// MFA
					
					if(!$worker->is_mfa_required) {
						@$mfa_params = DevblocksPlatform::importGPC($_REQUEST['mfa_params'], 'array', []);
						@$state = DevblocksPlatform::importGPC($mfa_params['state'], 'integer', 0);
						@$seed = DevblocksPlatform::importGPC($mfa_params['seed'], 'string', '');
						@$otp = DevblocksPlatform::importGPC($mfa_params['otp'], 'string', '');
						
						try {
							$is_mfa_enabled = !is_null(DAO_WorkerPref::get($worker_id, 'mfa.totp.seed', null));
							
							// If disabling an enabled MFA
							if(!$state && $is_mfa_enabled) {
								DAO_WorkerPref::delete($worker_id, 'mfa.totp.seed');
								
							// Or enabling a disabled MFA
							} elseif ($state && !$is_mfa_enabled) {
								if(!($active_worker->id == $worker_id || $active_worker->is_superuser))
									throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translateCapitalized('common.access_denied'));
								
								if($is_mfa_enabled)
									throw new Exception_DevblocksAjaxValidationError("Two-factor authentication is already enabled for this account.");
									
								if(!$seed)
									throw new Exception_DevblocksAjaxValidationError("The TOTP seed is invalid.");
								
								if(!$otp || strlen($otp) != 6 || !is_numeric($otp))
									throw new Exception_DevblocksAjaxValidationError("The given security code is invalid. It must be six digits.");
								
								$server_otp = DevblocksPlatform::services()->mfa()->getMultiFactorOtpFromSeed($seed);
								
								if(0 != strcmp($server_otp, $otp))
									throw new Exception_DevblocksAjaxValidationError("The given security code is invalid. Please try again.");
								
								DAO_WorkerPref::set($worker_id, 'mfa.totp.seed', $seed);
								
							} else {
								// Leave the same settings intact
							}
							
						} catch (Exception_DevblocksAjaxValidationError $e) {
							echo json_encode([
								'status' => false,
								'error' => $e->getMessage(),
							]);
							return;
						}
					}
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					break;
					
				case 'watchers':
					@$activity_points = DevblocksPlatform::importGPC($_REQUEST['activity_point'],'array',array());
					@$activity_points_enabled = DevblocksPlatform::importGPC($_REQUEST['activity_enable'],'array',array());
					
					$dont_notify_on_activities = array_diff($activity_points, $activity_points_enabled);
					WorkerPrefs::setDontNotifyOnActivities($worker->id, $dont_notify_on_activities);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					break;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
}

class ProfileWidget_Worklist extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.worklist';
	
	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		@$view_context = $model->extension_params['context'];
		@$query = $model->extension_params['query'];
		@$query_required = $model->extension_params['query_required'];
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// Unique instance per widget/record combo
		$view_id = sprintf('profile_widget_%d_%d', $model->id, $context_id);
		
		if(false == $view_context || false == ($view_context_ext = Extension_DevblocksContext::get($view_context)))
			return;
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$defaults = C4_AbstractViewModel::loadFromClass($view_context_ext->getViewClass());
			$defaults->id = $view_id;
			$defaults->is_ephemeral = true;
			$defaults->options = [];
			$defaults->name = ' ';
			$defaults->paramsEditable = [];
			$defaults->paramsDefault = [];
			$defaults->view_columns = $model->extension_params['columns'];
			$defaults->options['header_color'] = @$model->extension_params['header_color'] ?: '#626c70';
			$defaults->renderLimit = DevblocksPlatform::intClamp(@$model->extension_params['render_limit'], 1, 50);
			
			if(false == ($view = C4_AbstractViewLoader::unserializeAbstractView($defaults, false)))
				return;
		}
		
		$view->renderPage = 0;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		if($query_required) {
			$query_required = $tpl_builder->build($query_required, $dict);
		}
		
		$view->addParamsRequiredWithQuickSearch($query_required);
		
		if($query) {
			$query = $tpl_builder->build($query, $dict);
		}
		
		$view->setParamsQuery($query);
		$view->addParamsWithQuickSearch($query);
		
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, ['workspace']);
		$tpl->assign('context_mfts', $context_mfts);
		
		@$context = $model->extension_params['context'];
		@$columns = @$model->extension_params['columns'] ?: [];
		
		if($context)
			$columns = $this->_getContextColumns($context, $columns);
			
		$tpl->assign('columns', $columns);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/worklist/config.tpl');
	}
	
	function saveConfig(array $fields, $id=null, &$error=null) {
		if($id) {
			// Remove worker view models
			$view_id = sprintf('profile_widget_%d_', $id);
			DAO_WorkerViewModel::deleteByViewIdPrefix($view_id);
		}
		
		return true;
	}
	
	private function _getContextColumns($context, $columns_selected=[]) {
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) {
			return json_encode(false);
		}
		
		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) /* @var $view C4_AbstractView */
			return json_encode(false);
		
		$view->setAutoPersist(false);
		
		$results = [];
		
		$columns_avail = $view->getColumnsAvailable();
		
		if(empty($columns_selected))
			$columns_selected = $view->view_columns;
		
		if(is_array($columns_avail))
		foreach($columns_avail as $column) {
			if(empty($column->db_label))
				continue;
			
			$results[] = array(
				'key' => $column->token,
				'label' => mb_convert_case($column->db_label, MB_CASE_TITLE),
				'type' => $column->type,
				'is_selected' => in_array($column->token, $columns_selected),
			);
		}
		
		usort($results, function($a, $b) use ($columns_selected) {
			if($a['is_selected'] == $b['is_selected']) {
				if($a['is_selected']) {
					$a_idx = array_search($a['key'], $columns_selected);
					$b_idx = array_search($b['key'], $columns_selected);
					return $a_idx < $b_idx ? -1 : 1;
					
				} else {
					return $a['label'] < $b['label'] ? -1 : 1;
				}
				
			} else {
				return $a['is_selected'] ? -1 : 1;
			}
		});
		
		return $results;
	}
}

class ProfileWidget_BotBehavior extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.bot';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		@$behavior_id = $model->extension_params['behavior_id'];
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return;
		
		if(!$behavior_id 
			|| false == ($behavior = DAO_TriggerEvent::get($behavior_id))
			|| $behavior->event_point != Event_DashboardWidgetRender::ID
			) {
			echo "A bot behavior isn't configured.";
			return;
		}
		
		@$behavior_params_json = DevblocksPlatform::importVar($model->extension_params['behavior_params_json'], 'string', '');
		@$behavior_params = json_decode($tpl_builder->build($behavior_params_json, $dict), true) ?: [];
		
		// Event model
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DashboardWidgetRender::ID,
			[
				'widget' => $model,
				'_variables' => $behavior_params,
				'actions' => &$actions,
			]
		);
		
		if(false == ($event = $behavior->getEvent()))
			return;
			
		$event->setEvent($event_model, $behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		// Format behavior vars
		
		if(is_array($behavior_params))
		foreach($behavior_params as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!isset($behavior->variables[$k]))
					continue;
				
				$value = $behavior->formatVariable($behavior->variables[$k], $v);
				$dict->set($k, $value);
			}
		}
		
		// Run tree
		
		$behavior->runDecisionTree($dict, false, $event);
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'render_html':
					$html = @$action['html'];
					echo $html;
					break;
			}
		}
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/bot/config.tpl');
	}
}

class ProfileWidget_TicketSpamAnalysis extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.ticket.spam_analysis';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		if(0 != strcasecmp($context, CerberusContexts::CONTEXT_TICKET))
			return;
		
		$tpl = DevblocksPlatform::services()->template();

		$ticket = DAO_Ticket::get($context_id);
		$tpl->assign('ticket_id', $ticket->id);
		$tpl->assign('ticket', $ticket);
		
		// Receate the original spam decision
		$words = DevblocksPlatform::parseCsvString($ticket->interesting_words);
		$words = DAO_Bayes::lookupWordIds($words);

		// Calculate word probabilities
		foreach($words as $word) { /* @var $word Model_BayesWord */
			$word->probability = CerberusBayes::calculateWordProbability($word);
		}
		$tpl->assign('words', $words);
		
		// Determine what the spam probability would be if the decision was made right now
		$analysis = CerberusBayes::calculateTicketSpamProbability($context_id, true);
		$tpl->assign('analysis', $analysis);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/ticket/spam_analysis/spam_analysis.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
	}
}

class ProfileWidget_Responsibilities extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.responsibilities';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		switch($context) {
			case CerberusContexts::CONTEXT_WORKER:
				if(false == ($worker = DAO_Worker::get($context_id)))
					return;
					
				$tpl->assign('worker', $worker);
				
				$responsibilities = $worker->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$tpl->assign('widget', $model);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/tab_by_worker_readonly.tpl');
				break;
				
			case CerberusContexts::CONTEXT_GROUP:
				if(false == ($group = DAO_Group::get($context_id)))
					return;
					
				$tpl->assign('group', $group);
				
				$buckets = $group->getBuckets();
				$tpl->assign('buckets', $buckets);
				
				$members = $group->getMembers();
				$tpl->assign('members', $members);
				
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				$responsibilities = $group->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$tpl->assign('widget', $model);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/tab_by_group_readonly.tpl');
				break;
		}
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		//$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/config.tpl');
	}
	
	function showResponsibilitiesPopupAction(Model_ProfileWidget $model) {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $model);
		
		switch($context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(false == ($group = DAO_Group::get($context_id)))
					return;
					
				$tpl->assign('group', $group);
				
				$buckets = $group->getBuckets();
				$tpl->assign('buckets', $buckets);
				
				$members = $group->getMembers();
				$tpl->assign('members', $members);
				
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				$responsibilities = $group->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/peek_by_group_editable.tpl');
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if(false == ($worker = DAO_Worker::get($context_id)))
					return;
					
				$tpl->assign('worker', $worker);
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$memberships = $worker->getMemberships();
				$tpl->assign('memberships', $memberships);
				
				$responsibilities = $worker->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/peek_by_worker_editable.tpl');
				break;
		}
	}
	
	function saveResponsibilitiesPopupAction(Model_ProfileWidget $model) {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();

		switch($context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(!$active_worker->isGroupManager($context_id))
					return;
				
				@$responsibilities = DevblocksPlatform::importGPC($_REQUEST['responsibilities'], 'array', []);
				
				if(false == ($group = DAO_Group::get($context_id)))
					return;
				
				$group->setResponsibilities($responsibilities);
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if(!$active_worker->is_superuser)
					return;
				
				@$responsibilities = DevblocksPlatform::importGPC($_REQUEST['responsibilities'], 'array', []);
				
				if(false == ($worker = DAO_Worker::get($context_id)))
					return;
				
				$worker->setResponsibilities($responsibilities);
				break;
		}
	}
}

class ProfileWidget_CalendarAvailability extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.calendar.availability';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$target_context_id = $model->extension_params['calendar_id'];
		$calendar = null;
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$tpl->assign('widget', $model);
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		if($target_context_id) {
			$labels = $values = $merge_token_labels = $merge_token_values = [];
			
			CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'record_',
				'Record:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $model, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'widget_',
				'Widget:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			$values['widget__context'] = CerberusContexts::CONTEXT_PROFILE_WIDGET;
			$values['widget_id'] = $model->id;
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$context_id = $tpl_builder->build($target_context_id, $dict);
			
			if(false == ($calendar = DAO_Calendar::get($context_id))) {
				return;
			}
		}
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		if($calendar) {
			$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
			
			$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
	
			unset($calendar_events);
			
			// Convert availability back to abstract calendar events
	
			$calendar_events = $availability->getAsCalendarEvents($calendar_properties);
			
			$tpl->assign('calendar', $calendar);
			$tpl->assign('calendar_events', $calendar_events);
			
		} else {
			//$calendars = DAO_Calendar::getOwnedByWorker($active_worker);
			//$tpl->assign('calendars', $calendars);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar_availability/calendar.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar_availability/config.tpl');
	}
	
	function showCalendarAvailabilityTabAction(Model_ProfileWidget $model) {
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();

		$calendar = DAO_Calendar::get($calendar_id);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('widget', $model);
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		if($calendar) {
			$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
			
			$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
	
			unset($calendar_events);
			
			// Convert availability back to abstract calendar events
	
			$calendar_events = $availability->getAsCalendarEvents($calendar_properties);
			
			$tpl->assign('calendar', $calendar);
			$tpl->assign('calendar_events', $calendar_events);
			
		} else {
			$calendars = DAO_Calendar::getOwnedByWorker($active_worker);
			$tpl->assign('calendars', $calendars);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar_availability/calendar.tpl');
	}
}

class ProfileWidget_BehaviorTree extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.behavior.tree';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$target_context_id = $model->extension_params['behavior_id'];
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		if($target_context_id) {
			$labels = $values = $merge_token_labels = $merge_token_values = [];
			
			CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'record_',
				'Record:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $model, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'widget_',
				'Widget:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			$values['widget__context'] = CerberusContexts::CONTEXT_PROFILE_WIDGET;
			$values['widget_id'] = $model->id;
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$context_id = $tpl_builder->build($target_context_id, $dict);
		}
		
		if(false == ($behavior = DAO_TriggerEvent::get($context_id)))
			return;
		
		if(false == ($event = $behavior->getEvent()))
			return;
		
		if(false == ($behavior->getBot()))
			return;
		
		$tpl->assign('behavior', $behavior);
		$tpl->assign('event', $event->manifest);
		
		$tpl->display('devblocks:cerberusweb.core::internal/bot/behavior/tab.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/behavior_tree/config.tpl');
	}
}

class ProfileWidget_Calendar extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.calendar';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$target_context_id = $model->extension_params['context_id'];
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		if($target_context_id) {
			$labels = $values = $merge_token_labels = $merge_token_values = [];
			
			CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'record_',
				'Record:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $model, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'widget_',
				'Widget:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			$values['widget__context'] = CerberusContexts::CONTEXT_PROFILE_WIDGET;
			$values['widget_id'] = $model->id;
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$context_id = $tpl_builder->build($target_context_id, $dict);
			
			if(false == ($calendar = DAO_Calendar::get($context_id))) {
				return;
			}
		}
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		// Occlusion
		$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
		$availability->occludeCalendarEvents($calendar_events);

		// Template scope
		$tpl->assign('widget', $model);
		$tpl->assign('calendar', $calendar);
		$tpl->assign('calendar_events', $calendar_events);
		
		$tpl->assign('calendar_properties', $calendar_properties);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar/calendar.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar/config.tpl');
	}
	
	function showCalendarTabAction(Model_ProfileWidget $model) {
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();

		if(null == ($calendar = DAO_Calendar::get($calendar_id))) /* @var Model_Calendar $calendar */
			return;
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);

		// Occlusion
		
		$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
		$availability->occludeCalendarEvents($calendar_events);
		
		// Template scope
		$tpl->assign('widget', $model);
		$tpl->assign('calendar', $calendar);
		$tpl->assign('calendar_events', $calendar_events);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar/calendar.tpl');
	}
}

class ProfileWidget_Fields extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.fields';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		@$target_context = $model->extension_params['context'];
		@$target_context_id = $model->extension_params['context_id'];
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		$record_dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		if($target_context && !is_null($target_context_id)) {
			$context = $target_context;
			$context_id = $tpl_builder->build($target_context_id, $record_dict);
			
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			$dao_class = $context_ext->getDaoClass();
			
			if(!method_exists($dao_class, 'get') || false == ($record = $dao_class::get($context_id))) {
				$tpl->assign('context_ext', $context_ext);
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/fields/empty.tpl');
				return;
			}
		}
		
		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $record, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		if(!($context_ext instanceof IDevblocksContextProfile))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('widget', $model);
		$tpl->assign('page_context', $context);
		$tpl->assign('page_context_id', $context_id);
		
		// Properties
		
		$properties_selected = @$model->extension_params['properties'] ?: [];
		
		foreach($properties_selected as &$v)
			$v = array_flip($v);
		
		$properties_available = $context_ext->profileGetFields($record);
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $record->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties_available = array_merge($properties_available, $properties_cfields);
		
		$properties = [];
		
		// Only keep selected properties
		if(isset($properties_selected[0]))
			foreach(array_keys($properties_selected[0]) as $key)
				if(isset($properties_available[$key]))
					$properties[$key] = $properties_available[$key];
		
		// Empty fields
		
		$show_empty_fields = @$model->extension_params['options']['show_empty_properties'] ?: false;
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $record->id, $values, true);
		$properties_custom_fieldsets = array_intersect_key($properties_custom_fieldsets, $properties_selected);
		
		// Only keep selected properties
		foreach($properties_custom_fieldsets as $fieldset_id => &$fieldset_properties)
			$fieldset_properties['properties'] = array_intersect_key($fieldset_properties['properties'], @$properties_selected[$fieldset_id] ?: []);
		
		if(!$show_empty_fields) {
			$filter_empty_properties = function(&$properties) {
				foreach($properties as $k => $property) {
					if(!empty($property['value']))
						continue;
					
					switch($property['type']) {
						// Checkboxes can be empty
						case Model_CustomField::TYPE_CHECKBOX:
							continue 2;
							break;
							
						// Sliders can have empty values
						case 'slider':
							continue 2;
							break;
						
						case Model_CustomField::TYPE_LINK:
							// App-owned context links can be blank
							if(@$property['params']['context'] == CerberusContexts::CONTEXT_APPLICATION)
								continue 2;
							break;
					}
					
					unset($properties[$k]);
				}
			};
			
			$filter_empty_properties($properties);
			
			foreach($properties_custom_fieldsets as $fieldset_id => &$fieldset) {
				$filter_empty_properties($fieldset['properties']);
				
				if(empty($fieldset['properties']))
					unset($properties_custom_fieldsets[$fieldset_id]);
			}
		}
		
		$tpl->assign('properties', $properties);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		@$show_links = $model->extension_params['links']['show'];
		
		if($show_links) {
			$properties_links = [
				$context => [
					$record->id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$record->id,
							[]
						),
				],
			];
			$tpl->assign('properties_links', $properties_links);
		}
		
		// Card search buttons
		
		$search_buttons = $this->_getSearchButtons($model, $record_dict);
		$tpl->assign('search_buttons', $search_buttons);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/fields/fields.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		@$context = $model->extension_params['context'];
		
		if($context) {
			if(false == ($context_ext = Extension_DevblocksContext::get($context))) {
				echo '(ERROR: Missing record type: ' . DevblocksPlatform::strEscapeHtml($context) . ')';
				return;
			}
			
			$tpl->assign('context_ext', $context_ext);
			
			// =================================================================
			// Properties
			
			if($context_ext instanceof IDevblocksContextProfile) {
				$properties = $context_ext->profileGetFields();
				
				$tpl->assign('custom_field_values', []);
				
				$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context);
				
				if(!empty($properties_cfields))
					$properties = array_merge($properties, $properties_cfields);
				
				// Sort properties by the configured order
				
				@$properties_enabled = array_flip($model->extension_params['properties'][0] ?: []);
				
				uksort($properties, function($a, $b) use ($properties_enabled, $properties) {
					$a_pos = array_key_exists($a, $properties_enabled) ? $properties_enabled[$a] : 1000;
					$b_pos = array_key_exists($b, $properties_enabled) ? $properties_enabled[$b] : 1000;
					
					if($a_pos == $b_pos)
						return $properties[$a]['label'] > $properties[$b]['label'] ? 1 : -1;
					
					return $a_pos < $b_pos ? -1 : 1;
				});
				
				$tpl->assign('properties', $properties);
			}
			
			$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, null, [], true);
			$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
			
			// =================================================================
			// Search buttons
			
			$search_contexts = Extension_DevblocksContext::getAll(false, ['search']);
			$tpl->assign('search_contexts', $search_contexts);
			
			$search_buttons = $this->_getSearchButtons($model, null);
			$tpl->assign('search_buttons', $search_buttons);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/fields/config.tpl');
	}
	
	private function _getSearchButtons(Model_ProfileWidget $model, DevblocksDictionaryDelegate $dict=null) {
		@$search = $model->extension_params['search'] ?: [];
		
		$search_buttons = [];
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(empty($search))
			return [];
		
		if(is_array($search) && array_key_exists('context', $search))
		foreach(array_keys($search['context']) as $idx) {
			$query = $search['query'][$idx];
			
			if($dict) {
				$query = $tpl_builder->build($query, $dict);
			}
			
			$search_buttons[] = [
				'context' => $search['context'][$idx],
				'label_singular' => $search['label_singular'][$idx],
				'label_plural' => $search['label_plural'][$idx],
				'query' => $query,
			];
		}
		
		// If we have a dictionary, perform the actual counts
		if($dict) {
			$results = [];
			
			if(is_array($search_buttons))
			foreach($search_buttons as $search_button) {
				if(false == ($search_button_context = Extension_DevblocksContext::get($search_button['context'], true)))
					continue;
				
				if(false == ($view = $search_button_context->getTempView()))
					continue;
				
				$label_aliases = Extension_DevblocksContext::getAliasesForContext($search_button_context->manifest);
				$label_singular = @$search_button['label_singular'] ?: $label_aliases['singular'];
				$label_plural = @$search_button['label_plural'] ?: $label_aliases['plural'];
				
				$search_button_query = $tpl_builder->build($search_button['query'], $dict);
				$view->addParamsWithQuickSearch($search_button_query);
				
				$total = $view->getData()[1];
				
				$results[] = [
					'label' => ($total == 1 ? $label_singular : $label_plural),
					'context' => $search_button_context->id,
					'count' => $total,
					'query' => $search_button_query,
				];
			}
			
			return $results;
		}
		
		return $search_buttons;
	}
}

class ProfileWidget_TicketConvo extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.ticket.convo';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		// [TODO] Handle focus?
		
		$refresh_options['comments_mode'] = DevblocksPlatform::importVar(@$model->extension_params['comments_mode'], 'int', 0);
		
		$this->_showConversationAction($context_id, $refresh_options);
	}
	
	private function _showConversationAction($id, $display_options=[]) {
		@$expand_all = DevblocksPlatform::importVar($display_options['expand_all'], 'bit', 0);
		@$comments_mode = DevblocksPlatform::importVar($display_options['comments_mode'], 'int', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('comments_mode', $comments_mode);
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		$prefs_mail_always_read_all = DAO_WorkerPref::get($active_worker->id, 'mail_always_read_all', 0);
		
		if($expand_all || $prefs_mail_always_read_all)
			$expand_all = 1;
		
		$tpl->assign('expand_all', $expand_all);
		
		$ticket = DAO_Ticket::get($id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());
		
		// If deleted, check for a new merge parent URL
		if($ticket->status_id == Model_Ticket::STATUS_DELETED) {
			if(false !== ($new_mask = DAO_Ticket::getMergeParentByMask($ticket->mask))) {
				if(false !== ($merge_parent = DAO_Ticket::getTicketByMask($new_mask)))
					if(!empty($merge_parent->mask)) {
						$tpl->assign('merge_parent', $merge_parent);
					}
			}
		}
		
		// Drafts
		$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND (%s = %s OR %s = %s)",
			DAO_MailQueue::TICKET_ID,
			$id,
			DAO_MailQueue::TYPE,
			Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
			DAO_MailQueue::TYPE,
			Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD)
		));
		
		if(!empty($drafts))
			$tpl->assign('drafts', $drafts);
		
		// Only unqueued drafts
		$pending_drafts = [];
		
		if(!empty($drafts) && is_array($drafts))
		foreach($drafts as $draft_id => $draft) {
			if(!$draft->is_queued)
				$pending_drafts[$draft_id] = $draft;
		}
		
		if(!empty($pending_drafts))
			$tpl->assign('pending_drafts', $pending_drafts);
		
		// Messages
		$messages = $ticket->getMessages();
		
		arsort($messages);
		
		$tpl->assign('latest_message_id',key($messages));
		$tpl->assign('messages', $messages);
		
		// Thread comments and messages on the same level
		$convo_timeline = [];
		
		// Track senders and their orgs
		$message_senders = [];
		$message_sender_orgs = [];
		
		// Loop messages
		if(is_array($messages))
		foreach($messages as $message_id => $message) { /* @var $message Model_Message */
			$key = $message->created_date . '_m' . $message_id;
			// build a chrono index of messages
			$convo_timeline[$key] = array('m', $message_id);
			
			// If we haven't cached this sender address yet
			if($message->address_id)
				$message_senders[$message->address_id] = null;
		}
		
		// Bulk load sender address records
		$message_senders = CerberusApplication::hashLookupAddresses(array_keys($message_senders));
		
		// Bulk load org records
		array_walk($message_senders, function($sender) use (&$message_sender_orgs) { /* @var $sender Model_Address */
			if($sender->contact_org_id)
				$message_sender_orgs[$sender->contact_org_id] = null;
		});
		$message_sender_orgs = CerberusApplication::hashLookupOrgs(array_keys($message_sender_orgs));

		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);

		// Comments
		
		// If we're not hiding them
		if(1 != $comments_mode) {
			$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $id);
			$tpl->assign('comments', $comments);
			
			if($comments) {
				$pin_ts = null;
				
				if(2 == $comments_mode) {
					$pin_ts = max(array_column(DevblocksPlatform::objectsToArrays($comments), 'created'));
				}
				
				// build a chrono index of comments
				foreach($comments as $comment_id => $comment) { /* @var $comment Model_Comment */
					if($pin_ts && $comment->created == $pin_ts) {
						$key = time() . '_c' . $comment_id;
					} else {
						$key = $comment->created . '_c' . $comment_id;
					}
					$convo_timeline[$key] = array('c',$comment_id);
				}
			}
		}
		
		// Thread drafts into conversation
		if(!empty($drafts)) {
			foreach($drafts as $draft_id => $draft) { /* @var $draft Model_MailQueue */
				if(!empty($draft->queue_delivery_date)) {
					$key = $draft->queue_delivery_date . '_d' . $draft_id;
				} else {
					$key = $draft->updated . '_d' . $draft_id;
				}
				$convo_timeline[$key] = array('d', $draft_id);
			}
		}
		
		// Sort the timeline
		if(!$expand_all) {
			krsort($convo_timeline);
		} else {
			ksort($convo_timeline);
		}
		$tpl->assign('convo_timeline', $convo_timeline);
		
		// Message Notes
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages));
		$message_notes = [];
		// Index notes by message id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->context_id]))
				$message_notes[$note->context_id] = [];
			$message_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
		
		// Draft Notes
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_DRAFT, array_keys($drafts));
		$draft_notes = [];
		// Index notes by draft id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($draft_notes[$note->context_id]))
				$draft_notes[$note->context_id] = [];
			$draft_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('draft_notes', $draft_notes);
		
		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);
		
		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Prefs
		$mail_reply_button = DAO_WorkerPref::get($active_worker->id, 'mail_reply_button', 0);
		$tpl->assign('mail_reply_button', $mail_reply_button);
		
		$mail_reply_format = DAO_WorkerPref::get($active_worker->id, 'mail_reply_format', '');
		$tpl->assign('mail_reply_format', $mail_reply_format);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/ticket/convo/conversation.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/ticket/convo/config.tpl');
	}
}

class ProfileWidget_ChartCategories extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.categories';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		@$xaxis_format = DevblocksPlatform::importGPC($model->extension_params['xaxis_format'], 'string', 'label');
		@$yaxis_format = DevblocksPlatform::importGPC($model->extension_params['yaxis_format'], 'string', 'label');
		@$height = DevblocksPlatform::importGPC($model->extension_params['height'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		@$xaxis_key = $results['_']['format_params']['xaxis_key'] ?: '';
		
		if(!array_key_exists('data', $results))
			return;
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $model->id),
			'padding' => [
				'left' => 150,
			],
			'data' => [
				'x' => $xaxis_key,
				'columns' => $results['data'],
				'type' => 'bar',
				'colors' => [
					'hits' => '#1f77b4'
				]
			],
			'axis' => [
				'rotated' => true,
				'x' => [
					'type' => 'category',
					'tick' => [
						'format' => null,
						'multiline' => true,
						'multilineMax' => 2,
						'width' => 150,
					]
				],
				'y' => [
					'tick' => [
						'format' => null,
						'multiline' => true,
						'multilineMax' => 2,
						'rotate' => -90,
					]
				]
			],
			'legend' => [
				'show' => true,
			]
		];
		
		if(@$results['_']['stacked']) {
			$config_json['data']['type']  = 'bar';
			$groups = array_column($results['data'], 0);
			array_shift($groups);
			$config_json['data']['groups'] = [array_values($groups)];
			$config_json['legend']['show'] = true;
			
			if(!$height)
				$height = 100 + (50 * @count($results['data'][0]));
			
		} else if ($results) {
			$config_json['data']['type']  = 'bar';
			$config_json['legend']['show'] = false;
			
			if(!$height)
				$height = 100 + (50 * @count($results['data'][0]));
		}
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/categories/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/categories/config.tpl');
	}
}

class ProfileWidget_ChartPie extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.pie';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		@$chart_as = DevblocksPlatform::importGPC($model->extension_params['chart_as'], 'string', null);
		@$options = DevblocksPlatform::importGPC($model->extension_params['options'], 'array', []);
		@$height = DevblocksPlatform::importGPC($model->extension_params['height'], 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $model->id),
			'data' => [
				'columns' => $results['data'],
				'type' => $chart_as == 'pie' ? 'pie' : 'donut',
			],
			'donut' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'pie' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'tooltip' => [
				'format' => [
					'value' => null,
				],
			],
			'legend' => [
				'show' => true,
			]
		];
		
		$config_json['legend']['show']  = @$options['show_legend'] ? true : false;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/pie/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/pie/config.tpl');
	}
}

class ProfileWidget_ChartScatterplot extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.scatterplot';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		@$xaxis_label = DevblocksPlatform::importGPC($model->extension_params['xaxis_label'], 'string', '');
		@$xaxis_format = DevblocksPlatform::importGPC($model->extension_params['xaxis_format'], 'string', '');
		@$yaxis_label = DevblocksPlatform::importGPC($model->extension_params['yaxis_label'], 'string', '');
		@$yaxis_format = DevblocksPlatform::importGPC($model->extension_params['yaxis_format'], 'string', '');
		@$height = DevblocksPlatform::importGPC($model->extension_params['height'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $model->id),
			'data' => [
				'xs' => [],
				'columns' => $results['data'],
				'type' => 'scatter',
			],
			'axis' => [
				'x' => [
					'tick' => [
						'format' => null,
						'fit' => false,
						'rotate' => -90,
					]
				],
				'y' => [
					'tick' => [
						'fit' => false,
						'format' => null,
					]
				]
			],
		];
		
		foreach($results['data'] as $result) {
			if(@DevblocksPlatform::strEndsWith($result[0], '_x'))
				$config_json['data']['xs'][mb_substr($result[0],0,-2)] = $result[0];
		}
		
		if($xaxis_label)
			$config_json['axis']['x']['label'] = $xaxis_label;
		
		if($yaxis_label)
			$config_json['axis']['y']['label'] = $yaxis_label;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/scatterplot/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/scatterplot/config.tpl');
	}
}

class ProfileWidget_ChartTable extends Extension_ProfileWidget {
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		if(0 != strcasecmp('table', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'table' format.");
			return;
		}
		
		$tpl->assign('widget', $model);
		$tpl->assign('results', $results);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/table/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/table/config.tpl');
	}
};

class ProfileWidget_ChartTimeSeries extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.timeseries';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		@$chart_as = DevblocksPlatform::importGPC($model->extension_params['chart_as'], 'string', 'line');
		@$options = DevblocksPlatform::importGPC($model->extension_params['options'], 'array', []);
		@$xaxis_label = DevblocksPlatform::importGPC($model->extension_params['xaxis_label'], 'string', '');
		@$yaxis_label = DevblocksPlatform::importGPC($model->extension_params['yaxis_label'], 'string', '');
		@$yaxis_format = DevblocksPlatform::importGPC($model->extension_params['yaxis_format'], 'string', '');
		@$height = DevblocksPlatform::importGPC($model->extension_params['height'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(!$results) {
			echo "(no data)";
			return;
		}
		if(0 != strcasecmp('timeseries', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'timeseries' format.");
			return;
		}
		
		// Error
		$xaxis_key = @$results['_']['format_params']['xaxis_key'];
		$xaxis_format = @$results['_']['format_params']['xaxis_format'];
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $model->id),
			'data' => [
				'x' => 'ts',
				'xFormat' => '%Y-%m-%d',
				'json' => $results['data'],
				'type' => 'line'
			],
			'axis' => [
				'x' => [
					'type' => 'timeseries',
					'tick' => [
						'rotate' => -90,
						'fit' => true,
					]
				],
				'y' => [
					'tick' => [
						'fit' => true,
					]
				]
			],
			'subchart' => [
				'show' => false,
				'size' => [
					'height' => 50,
				]
			],
			'legend' => [
				'show' => true,
			],
			'point' => [
				'show' => true,
			]
		];
		
		$config_json['data']['xFormat']  = $xaxis_format;
		
		if($xaxis_format)
			$config_json['axis']['x']['tick']['format']  = $xaxis_format;
		
		$config_json['subchart']['show']  = @$options['subchart'] ? true : false;
		$config_json['legend']['show']  = @$options['show_legend'] ? true : false;
		$config_json['point']['show']  = @$options['show_points'] ? true : false;
		
		switch($chart_as) {
			case 'line':
				$config_json['data']['type']  = 'line';
				break;
				
			case 'spline':
				$config_json['data']['type']  = 'spline';
				break;
				
			case 'area':
				$config_json['data']['type']  = 'area-step';
				$config_json['data']['groups'] = [array_values(array_diff(array_keys($results['data']), [$xaxis_key]))];
				break;
				
			case 'bar':
				$config_json['data']['type'] = 'bar';
				$config_json['bar']['width'] = [
					'ratio' => 0.75,
				];
				break;
				
			case 'bar_stacked':
				$config_json['data']['type']  = 'bar';
				$config_json['bar']['width'] = [
					'ratio' => 0.75,
				];
				$config_json['data']['groups'] = [array_values(array_diff(array_keys($results['data']), [$xaxis_key]))];
				break;
		}
		
		if($xaxis_label)
			$config_json['axis']['x']['label'] = $xaxis_label;
		
		if($yaxis_label)
			$config_json['axis']['y']['label'] = $yaxis_label;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/timeseries/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/timeseries/config.tpl');
	}
}

class ProfileWidget_MapGeoPoints extends Extension_ProfileWidget {
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$projection = DevblocksPlatform::importGPC($model->extension_params['projection'], 'string', 'world');
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			return;
		}
		
		if(0 != strcasecmp('geojson', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'geojson' format.");
			return;
		}
		
		$points = $results['data'];
		
		$tpl->assign('points', $points);
		$tpl->assign('widget', $model);
		
		switch($projection) {
			case 'usa':
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_usa.tpl');
				break;
				
			default:
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_world.tpl');
				break;
		}
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/map/geopoints/config.tpl');
	}
};

class ProfileWidget_Visualization extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.visualization';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', '');
		@$cache_ttl = DevblocksPlatform::importGPC($model->extension_params['cache_ttl'], 'integer', 0);
		@$cache_by_worker = DevblocksPlatform::importGPC($model->extension_params['cache_by_worker'], 'integer', 0);
		@$template = DevblocksPlatform::importGPC($model->extension_params['template'], 'string', '');
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data_service = DevblocksPlatform::services()->data();
		$cache = DevblocksPlatform::services()->cache();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$cache_ttl = DevblocksPlatform::intClamp($cache_ttl, 0, 86400);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		try {
			$query = $tpl_builder->build($data_query, $dict);
			
			$cache_key = sprintf("profile:widget:%d%s",
				$model->id,
				$cache_by_worker ? sprintf(":%d", $active_worker->id) : ''
			);
			
			$error = null;
			
			if(!$cache_ttl || false == ($results = $cache->load($cache_key))) {
				if(false === ($results = $data_service->executeQuery($query, $error))) {
					echo DevblocksPlatform::strEscapeHtml($error);
					return;
				}
				
				if($cache_ttl)
					$cache->save($results, $cache_key, [], $cache_ttl);
			}
			
			if(!is_string($results))
				$results = json_encode($results);
			
			$dict->set('json', $results);
			
		} catch(Exception_DevblocksValidationError $e) {
			$results = ['_status' => false, '_error' => $e->getMessage() ];
			
		} catch(Exception $e) {
			$results = ['_status' => false];
		}
		
		if(empty($template))
			return;
		
		$html = $tpl_builder->build($template, $dict);
		
		echo $html;
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/visualization/config.tpl');
	}
	
	function saveConfig(array $fields, $id=null, &$error=null) {
		$cache = DevblocksPlatform::services()->cache();
		
		if(!$id)
			return true;
		
		if(false == (@$json = json_decode($fields[DAO_ProfileWidget::EXTENSION_PARAMS_JSON], true)))
			return true;
		
		@$cache_ttl = DevblocksPlatform::importGPC($json['cache_ttl'], 'integer', 0);
		@$cache_by_worker = DevblocksPlatform::importGPC($json['cache_by_worker'], 'integer', 0);
		
		if(!$cache_ttl)
			return true;
		
		if($cache_by_worker && false == ($active_worker = CerberusApplication::getActiveWorker()))
			return true;
		
		$cache_key = sprintf("profile:widget:%d%s",
			$id,
			$cache_by_worker ? sprintf(":%d", $active_worker->id) : ''
		);
		
		$cache->remove($cache_key);
		
		return true;
	}
}

class ProfileWidget_CustomHtml extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.html';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		@$template = $model->extension_params['template'];
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($template))
			return;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$html = $tpl_builder->build($template, $dict);
		
		echo $html;
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/html/config.tpl');
	}
}

class ProfileWidget_Comments extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.comments';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		// [TODO] Translate 'context' and 'context_id' settings (may not be this record)
		// [TODO] Limit?
		
		$comments = DAO_Comment::getByContext($context, $context_id);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('comments', $comments);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/comments/comments.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/comments/config.tpl');
	}
}