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
		if(null == ($subpage = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true))) {
			$tpl->display('devblocks:cerberusweb.core::404.tpl');
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		$tpl->assign('subpage', $subpage);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/index.tpl');
	}
	
	public function invoke(string $action) {
		switch($action) {
			case 'configTabs':
				return $this->_profileAction_configTabs();
			case 'configTabsSaveJson':
				return $this->_profileAction_configTabsSaveJson();
			case 'invoke':
				return $this->_profileAction_invoke();
			case 'invokeTab':
				return $this->_profileAction_invokeTab();
			case 'invokeWidget':
				return $this->_profileAction_invokeWidget();
			case 'renderTab':
				return $this->_profileAction_renderTab();
			case 'renderToolbar':
				return $this->_profileAction_renderToolbar();
			case 'renderWidgetConfig':
				return $this->_profileAction_renderWidgetConfig();
		}
		return false;
	}
	
	static function renderCard($context, $context_id, $model=null) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$model) {
			$tpl->assign('error_message', "This record no longer exists.");
			$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
			return;
		}
		
		// Context
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		// Links
		if($context_ext->hasOption('links')) {
			$links = [
				$context => [
					$context_id =>
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							[]
						),
				],
			];
			$tpl->assign('links', $links);
		}
		
		// Dictionary
		if($model) {
			$labels = $values = [];
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
		} else {
			$dict = DevblocksDictionaryDelegate::instance([
				'_context' => $context,
				'id' => $context_id,
			]);
		}
		$tpl->assign('dict', $dict);
		
		// Widgets
		$widgets = DAO_CardWidget::getByContext($context);
		
		$zones = [
			'content' => [],
		];
		
		// Sanitize zones
		foreach($widgets as $widget_id => $widget) {
			if(array_key_exists($widget->zone, $zones)) {
				$zones[$widget->zone][$widget_id] = $widget;
				continue;
			}
			
			// If the zone doesn't exist, drop the widget into the first zone
			$zones[key($zones)][$widget_id] = $widget;
		}
		
		$tpl->assign('zones', $zones);
		
		$is_readable = CerberusContexts::isReadableByActor($context, $dict, $active_worker);
		$tpl->assign('is_readable', $is_readable);
		
		$is_writeable = CerberusContexts::isWriteableByActor($context, $dict, $active_worker);
		$tpl->assign('is_writeable', $is_writeable);
		
		// Toolbar
		
		$toolbar_placeholders = $dict->getDictionary(null, false, 'record_');
		$toolbar_placeholders['worker__context'] = CerberusContexts::CONTEXT_WORKER;
		$toolbar_placeholders['worker_id'] = $active_worker->id;

		$toolbar_dict = DevblocksDictionaryDelegate::instance($toolbar_placeholders);
		
		if(false != ($toolbar_kata = DAO_Toolbar::getKataByName('record.card', $toolbar_dict))) {
			$tpl->assign('toolbar_card', $toolbar_kata);
		}
		
		// Template
		
		$tpl->assign('peek_context', $context);
		$tpl->assign('peek_context_id', $context_id);
		$tpl->assign('context_ext', $context_ext);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/card.tpl');
	}
	
	static function renderProfile($context, $context_id, $path=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();

		// Context
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		// Model
		
		$dao_class = $context_ext->getDaoClass();
		
		// Load the record
		if(false == ($record = $dao_class::get($context_id)))
			DevblocksPlatform::redirect(new DevblocksHttpRequest());
		
		$tpl->assign('record', $record);
		
		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $record, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Permissions
		
		if(!CerberusContexts::isReadableByActor($context, $dict, $active_worker))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translateCapitalized('common.access_denied'), 403);

		// Events
		
		if(
			false != ($record_viewed_event = DAO_AutomationEvent::getByName('record.profile.viewed'))
			&& $record_viewed_event->automations_kata
		) {
			$event_dict = DevblocksDictionaryDelegate::instance([]);
			$event_dict->mergeKeys('record_', $dict);
			$event_dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
			
			$initial_state = $event_dict->getDictionary();
			$error = null;
			
			$handlers = $record_viewed_event->getKata($event_dict, $error);
			
			if(false === $handlers && $error) {
				error_log('[KATA] Invalid record.profile.viewed KATA: ' . $error);
				$handlers = [];
			}
			
			$event_handler->handleEach(
				AutomationTrigger_RecordProfileViewed::ID,
				$handlers,
				$initial_state,
				$error
			);
		}
		
		if($context == CerberusContexts::CONTEXT_TICKET) {
			// Trigger ticket view event (before we load it, in case we change it)
			Event_TicketViewedByWorker::trigger($record->id, $active_worker->id);
		}
		
		// Toolbar
		$toolbar_placeholders = $dict->getDictionary(null, false, 'record_');
		$toolbar_placeholders['worker__context'] = CerberusContexts::CONTEXT_WORKER;
		$toolbar_placeholders['worker_id'] = $active_worker->id;
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance($toolbar_placeholders);
		
		$toolbar_kata = '';
		
		if(null != ($toolbar = DAO_Toolbar::getByName('record.profile')))
			$toolbar_kata = $toolbar->toolbar_kata;
		
		//************* [TODO] LEGACY SUPPORT - Remove in 11.0
		$point_params = DevblocksDictionaryDelegate::instance([
			'_context' => $context,
			'id' => $context_id,
		]);
		
		$legacy_interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $point_params, $active_worker);
		
		if($legacy_interactions) {
			$url_writer = DevblocksPlatform::services()->url();
			
			$legacy_kata = "\n\nmenu/legacy:\n  tooltip: Legacy Chat Bots\n  icon: more\n  items:\n";
			
			foreach ($legacy_interactions as $interaction) {
				$legacy_kata .= sprintf("    behavior/%s:\n      label: %s\n      id: %d\n      interaction: %s\n      image: %s\n      params:\n",
					uniqid(),
					$interaction['label'],
					$interaction['behavior_id'],
					$interaction['interaction'],
					$url_writer->write(sprintf('c=avatars&context=bot&context_id=%d', $interaction['bot_id'])) . '?v=0',
				);
				
				if ($interaction['params']) {
					foreach ($interaction['params'] as $k => $v) {
						$legacy_kata .= sprintf("        %s: %s\n",
							$k,
							$v
						);
					}
				}
			}
			
			$toolbar_kata .= $legacy_kata;
		}
		//*************
		
		$toolbar_kata = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict);
		$tpl->assign('toolbar_profile', $toolbar_kata);
		
		// Active tab
		
		if(!empty($path)) {
			$tpl->assign('tab_selected', array_shift($path));
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/profile.tpl');
	}
	
	private function _profileAction_invoke() {
		DevblocksPlatform::getHttpRequest()->is_ajax = true;
		
		$page_uri = DevblocksPlatform::importGPC($_GET['module'] ?? $_REQUEST['module'] ?? null, 'string','');
		$action = DevblocksPlatform::importGPC($_GET['action'] ?? $_REQUEST['action'] ?? null, 'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $page_uri, true);
		
		/* @var $inst Extension_PageSection */
		
		if($inst instanceof Extension_PageSection) {
			if(false === ($inst->handleActionForPage($action, 'profileAction'))) {
				if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
					trigger_error(
						sprintf('Call to undefined profile action `%s::%s`',
							get_class($inst),
							$action
						),
						E_USER_NOTICE
					);
				}
				DevblocksPlatform::dieWithHttpError(null, 404);
			}
		}
	}
	
	private function _profileAction_configTabs() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null,'string','');
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
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
	
	private function _profileAction_configTabsSaveJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string','');
		$profile_tabs = DevblocksPlatform::importGPC($_POST['profile_tabs'] ?? null, 'array',[]);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DevblocksPlatform::setPluginSetting('cerberusweb.core', 'profile:tabs:' . $context, $profile_tabs, true);
		
		return json_encode(true);
	}
	
	private function _profileAction_renderWidgetConfig() {
		$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension'] ?? null, 'string', '');
		
		if(false == ($extension = Extension_ProfileWidget::get($extension_id)))
			return;
		
		$model = new Model_ProfileWidget();
		$model->extension_id = $extension_id;
		
		$extension->renderConfig($model);
	}
	
	private function _profileAction_renderToolbar() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$toolbar_id = DevblocksPlatform::importGPC($_REQUEST['toolbar'] ?? null, 'string','');
		$record_type = DevblocksPlatform::importGPC($_REQUEST['record_type'] ?? null, 'string','');
		$record_id = DevblocksPlatform::importGPC($_REQUEST['record_id'] ?? null, 'integer',0);
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $record_type,
			'record_id' => $record_id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		if(false == ($toolbar = DAO_Toolbar::getKataByName($toolbar_id, $toolbar_dict)))
			return;
		
		DevblocksPlatform::services()->ui()->toolbar()->render($toolbar);
	}
	
	private function _profileAction_renderTab() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'] ?? null, 'string','');
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null,'string','');
		$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'] ?? null, 'integer',0);
		
		if(null == Extension_DevblocksContext::get($context))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($profile_tab = DAO_ProfileTab::get($tab_id)))
			return;
		
		if(!Context_ProfileTab::isReadableByActor($profile_tab, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($extension = $profile_tab->getExtension()))
			return;
		
		$extension->showTab($profile_tab, $context, $context_id);
	}
	
	private function _profileAction_invokeTab() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'] ?? null, 'integer',0);
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');
		
		if(false == ($profile_tab = DAO_ProfileTab::get($tab_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($extension = $profile_tab->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_ProfileTab::isReadableByActor($profile_tab, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($extension instanceof Extension_ProfileTab) {
			if(false === ($extension->invoke($action, $profile_tab))) {
				if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
					trigger_error(
						sprintf('Call to undefined profile tab action `%s::%s`',
							get_class($extension),
							$action
						),
						E_USER_NOTICE
					);
				}
			}
		}
	}
	
	private function _profileAction_invokeWidget() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'] ?? null, 'integer',0);
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');
		
		if(false == ($profile_widget = DAO_ProfileWidget::get($widget_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($extension = $profile_widget->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_ProfileWidget::isReadableByActor($profile_widget, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($extension instanceof Extension_ProfileWidget) {
			if(false === ($extension->invoke($action, $profile_widget))) {
				if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
					trigger_error(
						sprintf('Call to undefined profile widget action `%s::%s`',
							get_class($extension),
							$action
						),
						E_USER_NOTICE
					);
				}
			}
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
			} else if($model instanceof Model_MailQueue) {
				$context = CerberusContexts::CONTEXT_DRAFT;
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
	
	public function invoke(string $action, Model_ProfileTab $model) {
		switch($action) {
			case 'renderWidget':
				return $this->_profileTabAction_renderWidget($model);
			case 'reorderWidgets':
				return $this->_profileTabAction_reorderWidgets($model);
		}
		return false;
	}
	
	function showTab(Model_ProfileTab $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$widgets = $model->getWidgets();
		
		$layout = ($model->extension_params['layout'] ?? null) ?: '';
		
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
	
	private function _profileTabAction_renderWidget(Model_ProfileTab $profile_tab) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
		$context_id = DevblocksPlatform::importGPC($_POST['context_id'] ?? null, 'integer', 0);
		$full = DevblocksPlatform::importGPC($_POST['full'] ?? null, 'bool', false);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(false == ($widget = DAO_ProfileWidget::get($id, $context)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_ProfileWidget::isReadableByActor($widget, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!CerberusContexts::isReadableByActor($context, $context_id, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($extension = $widget->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// If full, we also want to replace the container
		if($full) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('widget', $widget);
			
			if(false == ($widget->getProfileTab()))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$tpl->assign('context', $context);
			$tpl->assign('context_id', $context_id);
			$tpl->assign('full', true);
			
			$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl');
			
		} else {
			$extension->render($widget, $context, $context_id);
		}
	}
	
	private function _profileTabAction_reorderWidgets(Model_ProfileTab $profile_tab) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$zones = DevblocksPlatform::importGPC($_POST['zones'] ?? null, 'array', []);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$widgets = $profile_tab->getWidgets();
		$new_zones = [];
		
		// Sanitize widget IDs
		foreach($zones as $zone_id => $zone) {
			$new_zones[$zone_id] = array_values(array_intersect(explode(',', $zone), array_keys($widgets)));
		}
		
		DAO_ProfileWidget::reorder($new_zones, $profile_tab->id);
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
	
	public function invoke(string $action, Model_ProfileTab $model) {
		return false;
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
		
		if(false == ($extension = $portal->getExtension()))
			return;
			
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
	
	public function invoke(string $action, Model_ProfileTab $model) {
		return false;
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
	
	public function invoke(string $action, Model_ProfileTab $model) {
		switch($action) {
			case 'showSettingsSectionTab':
				return $this->_profileTabAction_showSettingsSectionTab($model);
			case 'saveSettingsSectionTabJson':
				return $this->_profileTabAction_saveSettingsSectionTabJson($model);
		}
		return false;
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
	
	private function _profileTabAction_showSettingsSectionTab(Model_ProfileTab $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'] ?? null, 'integer', 0);
		$tab = DevblocksPlatform::importGPC($_REQUEST['tab'] ?? null, 'string', null);
		
		// ACL
		if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($worker = DAO_Worker::get($worker_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('worker', $worker);
		$tpl->assign('tab', $model);
		
		switch($tab) {
			case 'profile':
				$prefs = [];
				$prefs['assist_mode'] = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
				$prefs['dark_mode'] = intval(DAO_WorkerPref::get($worker->id, 'dark_mode', 0));
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
				$prefs['mail_reply_button'] = DAO_WorkerPref::get($worker->id,'mail_reply_button',0);
				$prefs['mail_reply_format'] = DAO_WorkerPref::get($worker->id,'mail_reply_format','');
				$prefs['mail_status_compose'] = DAO_WorkerPref::get($worker->id,'compose.status','waiting');
				$prefs['mail_status_reply'] = DAO_WorkerPref::get($worker->id,'mail_status_reply','waiting');
				$prefs['mail_signature_pos'] = DAO_WorkerPref::get($worker->id,'mail_signature_pos',2);
				$tpl->assign('prefs', $prefs);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/mail.tpl');
				break;
				
			case 'records':
				$prefs = [];
				$prefs['comment_disable_formatting'] = DAO_WorkerPref::get($worker->id,'comment_disable_formatting',0);
				$tpl->assign('prefs', $prefs);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/worker/settings/tabs/records.tpl');
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
	
	private function _profileTabAction_saveSettingsSectionTabJson(Model_ProfileTab $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'integer', 0);
		$tab = DevblocksPlatform::importGPC($_POST['tab'] ?? null, 'string', null);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			// ACL
			if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
				throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this worker.");
			
			if(false == ($worker = DAO_Worker::get($worker_id)))
				throw new Exception_DevblocksAjaxValidationError("This worker record does not exist.");
			
			switch($tab) {
				case 'profile':
					$gender = DevblocksPlatform::importGPC($_POST['gender'] ?? null, 'string');
					$location = DevblocksPlatform::importGPC($_POST['location'] ?? null, 'string');
					$phone = DevblocksPlatform::importGPC($_POST['phone'] ?? null, 'string');
					$mobile = DevblocksPlatform::importGPC($_POST['mobile'] ?? null, 'string');
					$dob = DevblocksPlatform::importGPC($_POST['dob'] ?? null, 'string');
					$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'] ?? null, 'string');
					
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
					
					$assist_mode = DevblocksPlatform::importGPC($_POST['assist_mode'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);
					
					$dark_mode = DevblocksPlatform::importGPC($_POST['dark_mode'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'dark_mode', $dark_mode);
					
					$keyboard_shortcuts = DevblocksPlatform::importGPC($_POST['keyboard_shortcuts'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);
					
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_WORKER, $worker->id, $avatar_image);
					
					echo json_encode([
						'message' => DevblocksPlatform::translate('success.saved_changes'),
						'status' => true,
					]);
					return;
					
				case 'pages':
					$page_ids = DevblocksPlatform::importGPC($_POST['pages'] ?? null, 'array:integer',[]);
					
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
					
				case 'availability':
					$availability_calendar_id = DevblocksPlatform::importGPC($_POST['availability_calendar_id'] ?? null, 'integer',0);
					
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
					
				case 'localization':
					$lang_code = DevblocksPlatform::importGPC($_POST['lang_code'] ?? null, 'string','en_US');
					$time_format = DevblocksPlatform::importGPC($_POST['time_format'] ?? null, 'string',null);
					$timezone = DevblocksPlatform::importGPC($_POST['timezone'] ?? null, 'string');
					
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
					
				case 'mail':
					$mail_disable_html_display = DevblocksPlatform::importGPC($_POST['mail_disable_html_display'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_disable_html_display', $mail_disable_html_display);
					
					$mail_always_read_all = DevblocksPlatform::importGPC($_POST['mail_always_read_all'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_always_read_all', $mail_always_read_all);
					
					$mail_reply_html = DevblocksPlatform::importGPC($_POST['mail_reply_html'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_html', $mail_reply_html);
					
					$mail_reply_button = DevblocksPlatform::importGPC($_POST['mail_reply_button'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_button', $mail_reply_button);
					
					$mail_reply_format = DevblocksPlatform::importGPC($_POST['mail_reply_format'] ?? null, 'string','');
					DAO_WorkerPref::set($worker->id, 'mail_reply_format', $mail_reply_format);
					
					$mail_signature_pos = DevblocksPlatform::importGPC($_POST['mail_signature_pos'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_signature_pos', $mail_signature_pos);
					
					$mail_status_compose = DevblocksPlatform::importGPC($_POST['mail_status_compose'] ?? null, 'string','waiting');
					DAO_WorkerPref::set($worker->id, 'compose.status', $mail_status_compose);
					
					$mail_status_reply = DevblocksPlatform::importGPC($_POST['mail_status_reply'] ?? null, 'string','waiting');
					DAO_WorkerPref::set($worker->id, 'mail_status_reply', $mail_status_reply);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					
				case 'records':
					$comment_disable_formatting = DevblocksPlatform::importGPC($_POST['comment_disable_formatting'] ?? null, 'integer',0);
					DAO_WorkerPref::set($worker->id, 'comment_disable_formatting', $comment_disable_formatting);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					
				case 'search':
					$search_favorites = DevblocksPlatform::importGPC($_POST['search_favorites'] ?? null, 'array',[]);
					DAO_WorkerPref::setAsJson($worker->id, 'search_favorites_json', $search_favorites);
					
					$cache = DevblocksPlatform::services()->cache();
					$cache_key = 'worker_search_menu_' . $worker_id;
					$cache->remove($cache_key);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
					
				case 'security':
					// Secret questions
					
					$q = DevblocksPlatform::importGPC($_POST['sq_q'] ?? null, 'array', array('','',''));
					$h = DevblocksPlatform::importGPC($_POST['sq_h'] ?? null, 'array', array('','',''));
					$a = DevblocksPlatform::importGPC($_POST['sq_a'] ?? null, 'array', array('','',''));
					
					$secret_questions = array(
						array('q'=>$q[0], 'h'=>$h[0], 'a'=>$a[0]),
						array('q'=>$q[1], 'h'=>$h[1], 'a'=>$a[1]),
						array('q'=>$q[2], 'h'=>$h[2], 'a'=>$a[2]),
					);
					
					DAO_WorkerPref::set($worker->id, 'login.recover.secret_questions', json_encode($secret_questions));
					
					// MFA
					
					if(!$worker->is_mfa_required) {
						$mfa_params = DevblocksPlatform::importGPC($_POST['mfa_params'] ?? null, 'array', []);
						$state = DevblocksPlatform::importGPC($mfa_params['state'] ?? null, 'integer', 0);
						$seed = DevblocksPlatform::importGPC($mfa_params['seed'] ?? null, 'string', '');
						$otp = DevblocksPlatform::importGPC($mfa_params['otp'] ?? null, 'string', '');
						
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
					
				case 'watchers':
					$activity_points = DevblocksPlatform::importGPC($_POST['activity_point'] ?? null, 'array', []);
					$activity_points_enabled = DevblocksPlatform::importGPC($_POST['activity_enable'] ?? null, 'array', []);
					
					$dont_notify_on_activities = array_diff($activity_points, $activity_points_enabled);
					WorkerPrefs::setDontNotifyOnActivities($worker->id, $dont_notify_on_activities);
					
					echo json_encode([
						'status' => true,
						'message' => DevblocksPlatform::translate('success.saved_changes'),
					]);
					return;
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