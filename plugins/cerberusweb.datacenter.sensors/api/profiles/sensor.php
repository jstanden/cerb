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

class PageSection_ProfilesSensor extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // sensor
		@$context_id = intval(array_shift($stack));
		
		$context = CerberusContexts::CONTEXT_SENSOR;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'savePeek':
					return $this->_profileAction_savePeek();
				case 'renderConfigExtension':
					return $this->_profileAction_renderConfigExtension();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$tag = DevblocksPlatform::importGPC($_POST['tag'],'string','');
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string','');
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		if($do_delete && !empty($id)) { // delete
			if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_SENSOR)))
				return;
			
			if(false == ($model = DAO_DatacenterSensor::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_Sensor::isDeletableByActor($model, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_SENSOR, $model->id, $model->name);
			
			DAO_DatacenterSensor::delete($id);
			
		} else {
			$tag = DevblocksPlatform::strLower($tag);
			
			// Make sure the tag is unique
			if(!empty($tag)) {
				$result = DAO_DatacenterSensor::getByTag($tag);
				// If we matched the tag and it's not this object
				if(!empty($result) && $result->id != $id)
					$tag = null;
			}
			
			$fields = array(
				DAO_DatacenterSensor::NAME => $name,
				DAO_DatacenterSensor::TAG => (!empty($tag) ? $tag : uniqid()),
				DAO_DatacenterSensor::EXTENSION_ID => $extension_id,
				DAO_DatacenterSensor::PARAMS_JSON => json_encode($params),
			);
			
			if(!empty($id)) { // update
				if(!DAO_DatacenterSensor::validate($fields, $error, $id))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_DatacenterSensor::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				DAO_DatacenterSensor::update($id, $fields);
				DAO_DatacenterSensor::onUpdateByActor($active_worker, $fields, $id);
				
			} else { // create
				if(!DAO_DatacenterSensor::validate($fields, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_DatacenterSensor::onBeforeUpdateByActor($active_worker, $fields, null, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				$id = DAO_DatacenterSensor::create($fields);
				DAO_DatacenterSensor::onUpdateByActor($active_worker, $fields, $id);
				
				// View marquee
				if(!empty($id) && !empty($view_id)) {
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_SENSOR, $id);
				}
			}
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
			if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SENSOR, $id, $field_ids, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
		}
	}
	
	private function _profileAction_renderConfigExtension() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
		$sensor_id = DevblocksPlatform::importGPC($_REQUEST['sensor_id'], 'integer', '0');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($extension_id))
			&& null != ($inst = $tab_mft->createInstance())
			&& $inst instanceof Extension_Sensor) {
			
			if(null == ($sensor = DAO_DatacenterSensor::get($sensor_id))) {
				$inst->renderConfig();
			} else {
				if(!Context_Sensor::isWriteableByActor($sensor, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$inst->renderConfig($sensor->params);
			}
		}
	}
};