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
			$extension->render($widget, $context, $context_id);
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
	const ID = 'cerb.profile.tab.portal.config';

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
								
								if(!DevblocksPlatform::services()->mfa()->isAuthorized($otp, $seed))
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