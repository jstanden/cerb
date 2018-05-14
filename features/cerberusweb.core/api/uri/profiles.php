<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
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
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
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
	
	static function renderProfile($context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Remember the last tab/URL
		$point = sprintf("profile.%s", $context);
		$tpl->assign('point', $point);
		
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
		$tpl->assign('profile_tabs', $profile_tabs_available);
		
		$profile_tabs_enabled = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'profile:tabs:' . $context, [], true);
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
			call_user_func([$extension, $action.'Action']);
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
	
	static function getProfilePropertiesCustomFields($context, $values) {
		$custom_fields = DAO_CustomField::getByContext($context);
		$properties = [];
		
		foreach($custom_fields as $cf_id => $cfield) {
			if($cfield->custom_fieldset_id != 0)
				continue;
			
			if(!isset($values[$cf_id]))
				continue;
		
			$properties['cf_' . $cf_id] = [
				'id' => $cf_id,
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => $values[$cf_id],
				'params' => @$cfield->params ?: [],
			];
		}
		
		return $properties;
	}
	
	static function getProfilePropertiesCustomFieldsets($context, $context_id, $values) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$custom_fields = DAO_CustomField::getByContext($context);
		$custom_fieldsets = DAO_CustomFieldset::getByContextLink($context, $context_id);
		
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
			
				$cf_group_props['cf_' . $cf_group_field_id] = [
					'id' => $cf_group_field_id,
					'label' => $cf_group_field->name,
					'type' => $cf_group_field->type,
					'value' => isset($values[$cf_group_field->id]) ? $values[$cf_group_field->id] : null,
				];
				
				// Include parameters for abstract handling
				if(!empty($cf_group_field->params))
					$cf_group_props['cf_' . $cf_group_field_id]['params'] = $cf_group_field->params;
			}
			
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
		
		foreach($models as $idx => $model) {
			if($model instanceof Model_Comment) {
				$context = CerberusContexts::CONTEXT_COMMENT;
				$context_id = $model->id;
				$object = array('context' => $context, 'context_id' => $model->id);
				$json['objects'][] = $object;
			} elseif($model instanceof Model_Message) {
				$context = CerberusContexts::CONTEXT_MESSAGE;
				$context_id = $model->id;
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
		$tpl->assign('widgets', $widgets);
		
		$tpl->assign('model', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/dashboard/tab.tpl');
	}
	
	function renderWidgetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'], 'integer', 0);
		
		if(false == ($widget = DAO_ProfileWidget::get($id)))
			return;
		
		if(false == ($extension = $widget->getExtension()))
			return;
		
		// If full, we also want to replace the container
		if($full) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('widget', $widget);
			
			if(false == ($tab = $widget->getProfileTab()))
				return;
			
			$unit_width = $tab->extension_params['column_width'];
			$tpl->assign('unit_width', $unit_width);
			
			$tpl->assign('context', $context);
			$tpl->assign('context_id', $context_id);
			$tpl->assign('extension', $extension);
			
			$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl');
		} else {
			$extension->render($widget, $context, $context_id);
		}
	}
	
	function reorderWidgetsAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'], 'integer', 0);
		@$widget_ids = DevblocksPlatform::importGPC($_REQUEST['widget_ids'], 'array', []);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		$widgets = DAO_ProfileWidget::getByTab($tab_id);
		
		// Sanitize widget IDs
		$widget_ids = array_values(array_intersect($widget_ids, array_keys($widgets)));
		
		DAO_ProfileWidget::reorder($widget_ids);
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
		
		@$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'],'integer',0);
		
		// Install
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=portal&a='.$portal->code,true);
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

class ProfileWidget_Worklist extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.worklist';
	
	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id) {
		@$view_context = $model->extension_params['context'];
		@$query = $model->extension_params['query'];
		@$query_required = $model->extension_params['query_required'];
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// Unique instance per widget/record combo
		$view_id = sprintf('profile_widget_%d_%d', $model->id, $context_id);
		
		if(false == $view_context || false == ($view_context_ext = Extension_DevblocksContext::get($view_context)))
			return;
		
		if(false == ($view = $view_context_ext->getSearchView($view_id)))
			return;
		
		if($view->getContext() != $view_context_ext->id) {
			DAO_WorkerViewModel::deleteView(CerberusApplication::getActiveWorker()->id, $view_id);
			
			if(false == ($view = $view_context_ext->getSearchView($view_id)))
				return;
		}
		
		$view->name = ' ';
		$view->addParams([], true);
		$view->addParamsDefault([], true);
		$view->is_ephemeral = true;
		$view->view_columns = @$model->extension_params['columns'] ?: $view->view_columns;
		$view->options['header_color'] = @$model->extension_params['header_color'] ?: '#626c70';
		$view->renderLimit = DevblocksPlatform::intClamp(@$model->extension_params['render_limit'], 1, 50);
		$view->renderPage = 0;
		
		$dict = DevblocksDictionaryDelegate::instance([
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
		
		$view->addParamsWithQuickSearch($query);
		
		$view->persist();
		
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
	
	function saveConfig(Model_ProfileWidget $model) {
		
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

	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$dict = DevblocksDictionaryDelegate::instance([
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
		
		$result = $behavior->runDecisionTree($dict, false, $event);
		
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

	function render(Model_ProfileWidget $model, $context, $context_id) {
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
		foreach($words as $idx => $word) { /* @var $word Model_BayesWord */
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

	}
}

class ProfileWidget_CalendarAvailability extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.calendar.availability';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
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
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
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

		
		
		$tpl->assign('widget', $model);
		
		
	}
}

class ProfileWidget_Calendar extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.calendar';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
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
		$active_worker = CerberusApplication::getActiveWorker();
		
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
class ProfileWidget_Snippet extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.snippet';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$target_context = $model->extension_params['context'];
		@$target_context_id = $model->extension_params['context_id'];
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		if($target_context && $target_context_id) {
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
			
			$context = CerberusContexts::CONTEXT_SNIPPET;
			$context_id = $tpl_builder->build($target_context_id, $dict);
			
			if(false == ($record = DAO_Snippet::get($context_id))) {
				return;
			}
		}
		
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$context_id || false == ($snippet = DAO_Snippet::get($context_id)))
			return;
		
		if(false == Context_Snippet::isReadableByActor($snippet, $active_worker))
			return;
		
		$tpl->assign('snippet', $record);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/snippet/snippet.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/snippet/config.tpl');
	}
}

