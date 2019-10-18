<?php
abstract class Extension_Sensor extends DevblocksExtension {
	const POINT = 'cerberusweb.datacenter.sensor';
	
	/**
	 * @internal
	 */
	static function getAll($as_instances=false) {
		$results = DevblocksPlatform::getExtensions('cerberusweb.datacenter.sensor', $as_instances);
		
		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($results, 'name');
		
		return $results;
	}
	
	abstract function renderConfig($params=array());
	abstract function run($params, &$fields);
};

class WgmDatacenterSensorsSensorExternal extends Extension_Sensor {
	function renderConfig($params=array()) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		$tpl->display('devblocks:cerberusweb.datacenter.sensors::sensors/external/config.tpl');
	}
	
	function run($params, &$fields) {
		return TRUE;
	}
};

class WgmDatacenterSensorsSensorHttp extends Extension_Sensor {
	function renderConfig($params=array()) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		$tpl->display('devblocks:cerberusweb.datacenter.sensors::sensors/http/config.tpl');
	}
	
	function run($params, &$fields) {
		if(!extension_loaded('curl')) {
			$error = "The 'curl' PHP extension is required.";
			$fields[DAO_DatacenterSensor::STATUS] = 'C';
			$fields[DAO_DatacenterSensor::METRIC] = $error;
			$fields[DAO_DatacenterSensor::OUTPUT] = $error;
			return FALSE;
		}
		
		$ch = DevblocksPlatform::curlInit();
		$success = false;
		
		@$url = $params['url'];
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		DevblocksPlatform::curlExec($ch);
		
		$info = curl_getinfo($ch);
		$status = $info['http_code'];
		
		if(200 == $status) {
			$success = true;
			$output = $status;
		} else {
			$success = false;
			$output = curl_error($ch);
		}
		
		curl_close($ch);
		
		$fields = array(
			DAO_DatacenterSensor::STATUS => ($success?'O':'C'),
			DAO_DatacenterSensor::METRIC => ($success?1:0),
			DAO_DatacenterSensor::OUTPUT => $output,
		);
		
		return $success;
	}
};

class WgmDatacenterSensorsSensorPort extends Extension_Sensor {
	function renderConfig($params=array()) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		$tpl->display('devblocks:cerberusweb.datacenter.sensors::sensors/port/config.tpl');
	}
	
	function run($params, &$fields) {
		// [TODO] cURL required when running fsockopen?
		if(!extension_loaded('curl')) {
			$error = "The 'curl' PHP extension is required.";
			$fields[DAO_DatacenterSensor::STATUS] = 'C';
			$fields[DAO_DatacenterSensor::METRIC] = $error;
			$fields[DAO_DatacenterSensor::OUTPUT] = $error;
			return FALSE;
		}
		
		$errno = null;
		$errstr = null;
		
		@$host = $params['host'];
		@$port = intval($params['port']);
		
		if(false !== (@$conn = fsockopen($host, $port, $errno, $errstr, 10))) {
			$success = true;
			$output = fgets($conn);
			fclose($conn);
		} else {
			$success = false;
			$output = $errstr;
		}
		
		$fields = array(
			DAO_DatacenterSensor::STATUS => ($success?'O':'C'),
			DAO_DatacenterSensor::METRIC => ($success?1:0),
			DAO_DatacenterSensor::OUTPUT => $output,
		);
		
		return $success;
	}
};

if (class_exists('CerberusCronPageExtension')):
class Cron_WgmDatacenterSensors extends CerberusCronPageExtension {
	public function run() {
		$logger = DevblocksPlatform::services()->log("Sensors");
		$logger->info("Started");

		// Only non-disabled sensors that need to run, up to a max number, longest since updated, not external
		$sensors = DAO_DatacenterSensor::getWhere(
			sprintf("%s = 0 AND %s != %s",
				DAO_DatacenterSensor::IS_DISABLED,
				DAO_DatacenterSensor::EXTENSION_ID,
				Cerb_ORMHelper::qstr('cerberusweb.datacenter.sensor.external')
			),
			DAO_DatacenterSensor::UPDATED,
			true,
			100
		);
		
		foreach($sensors as $sensor) {
			$pass = $sensor->run();
			$logger->info($sensor->name . ': ' . ($pass===true ? 'PASS' : 'FAIL'));
		}
		
		$logger->info("Finished");
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->cache_lifetime = "0";
		//$tpl->display('devblocks:cerberusweb.datacenter.sensors::cron/config.tpl');
	}
	
	public function saveConfigurationAction() {
		//@$example_waitdays = DevblocksPlatform::importGPC($_POST['example_waitdays'], 'integer');
		//$this->setParam('example_waitdays', $example_waitdays);
	}
};
endif;

// Controller
class Page_Sensors extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
	}
	
	// [TODO] Card/verify
	function savePeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$tag = DevblocksPlatform::importGPC($_REQUEST['tag'],'string','');
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string','');
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if($do_delete && !empty($id)) { // delete
			if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_SENSOR)))
				return;
				//throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
			
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
	
	function renderConfigExtensionAction() {
		$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
		$sensor_id = DevblocksPlatform::importGPC($_REQUEST['sensor_id'], 'integer', '0');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($extension_id))
				&& null != ($inst = $tab_mft->createInstance())
				&& $inst instanceof Extension_Sensor) {
			
			if(null == ($sensor = DAO_DatacenterSensor::get($sensor_id))) {
				$inst->renderConfig();
			} else {
				$inst->renderConfig($sensor->params);
			}
		}
	}
};

class WorkspaceWidgetDatasource_Sensor extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params_prefix);
		
		// Sensors
		
		if(class_exists('DAO_DatacenterSensor', true)) {
			$sensors = DAO_DatacenterSensor::getWhere();
			
			if(is_array($sensors))
			foreach($sensors as $sensor_id => $sensor) {
				if(!in_array($sensor->metric_type, array('decimal','percent')))
					unset($sensors[$sensor_id]);
			}
			$tpl->assign('sensors', $sensors);
		}
		
		$tpl->display('devblocks:cerberusweb.datacenter.sensors::datasources/config_sensor.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		// Load sensor
		if(class_exists('DAO_DatacenterSensor', true)
			&& null != ($sensor_id = @$params['sensor_id'])
			&& null != ($sensor = DAO_DatacenterSensor::get($sensor_id))
			) {
			
			switch($sensor->metric_type) {
				case 'decimal':
					$params['metric_value'] = floatval($sensor->metric);
					break;
				case 'percent':
					$params['metric_value'] = intval($sensor->metric);
					break;
				default:
					$params['metric_value'] = 0;
			}
		}

		return $params;
	}
};
