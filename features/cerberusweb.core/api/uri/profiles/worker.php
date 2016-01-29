<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_ProfilesWorker extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		if(isset($stack[2])) {
			$this->_renderWorkerPage();
		}
	}
	
	private function _renderWorkerPage() {
		$tpl = DevblocksPlatform::getTemplateService();
		$request = DevblocksPlatform::getHttpRequest();
		
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $request->path;
		
		@array_shift($stack); // profiles
		@array_shift($stack); // worker
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		@$id = array_shift($stack);

		switch($id) {
			case 'me':
				$worker_id = $active_worker->id;
				break;
				
			default:
				@$worker_id = intval($id);
				break;
		}

		if(false != (@$tab = array_shift($stack)))
			$tpl->assign('tab', $tab);
		
		$point = 'cerberusweb.profiles.worker.' . $worker_id;

		if(empty($worker_id) || null == ($worker = DAO_Worker::get($worker_id)))
			return;
			
		$tpl->assign('worker', $worker);
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
		
		$properties['email'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_ADDRESS),
			'value' => $worker->email_id,
		);
		
		if(!empty($worker->location)) {
			$properties['location'] = array(
				'label' => mb_ucfirst($translate->_('common.location')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $worker->location,
			);
		}
		
		$properties['is_superuser'] = array(
			'label' => mb_ucfirst($translate->_('worker.is_superuser')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $worker->is_superuser,
		);
		
		if(!empty($worker->mobile)) {
			$properties['mobile'] = array(
				'label' => mb_ucfirst($translate->_('common.mobile')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $worker->mobile,
			);
		}
		
		if(!empty($worker->phone)) {
			$properties['phone'] = array(
				'label' => mb_ucfirst($translate->_('common.phone')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $worker->phone,
			);
		}
		
		$properties['language'] = array(
			'label' => mb_ucfirst($translate->_('worker.language')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $worker->language,
		);
		
		$properties['timezone'] = array(
			'label' => mb_ucfirst($translate->_('worker.timezone')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $worker->timezone,
		);
		
		if(!empty($worker->calendar_id)) {
			$properties['calendar_id'] = array(
				'label' => mb_ucfirst($translate->_('common.calendar')),
				'type' => Model_CustomField::TYPE_LINK,
				'params' => array('context' => CerberusContexts::CONTEXT_CALENDAR),
				'value' => $worker->calendar_id,
			);
		}
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WORKER, $worker->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_WORKER, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);

		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_WORKER, $worker->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_WORKER => array(
				$worker->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_WORKER,
						$worker->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.worker'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Prefs
		$profile_worker_prefs = DAO_WorkerPref::getByWorker($worker->id);
		$tpl->assign('profile_worker_prefs', $profile_worker_prefs);
		
		// SSL
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl->assign('is_ssl', $url_writer->isSSL());
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/worker.tpl');
	}
	
	function setAvailabilityCalendarAction() {
		@$availability_calendar_id = DevblocksPlatform::importGPC($_REQUEST['availability_calendar_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($availability_calendar_id)) {
			if(false == ($calendar = DAO_Calendar::get($availability_calendar_id)))
				$availability_calendar_id = 0;
			
			if(!CerberusContexts::isWriteableByActor($calendar->owner_context, $calendar->owner_context_id, $active_worker))
				$availability_calendar_id = 0;
		}
		
		if(empty($availability_calendar_id)) {
			$fields = array(
				DAO_Calendar::NAME => $active_worker->getName() .  "'s Schedule",
				DAO_Calendar::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
				DAO_Calendar::OWNER_CONTEXT_ID => $active_worker->id,
				DAO_Calendar::PARAMS_JSON => '{"manual_disabled":"0","sync_enabled":"0","color_available":"#A0D95B","color_busy":"#C8C8C8"}',
				DAO_Calendar::UPDATED_AT => time(),
			);
			$availability_calendar_id = DAO_Calendar::create($fields);
		}
		
		if(!empty($availability_calendar_id)) {
			$fields = array(
				DAO_Worker::CALENDAR_ID => $availability_calendar_id,
			);
			DAO_Worker::update($active_worker->id, $fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','worker','me','availability')));
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=worker', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $worker_id => $row) {
				if($worker_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Worker::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=worker&id=%d", $row[SearchFields_Worker::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};