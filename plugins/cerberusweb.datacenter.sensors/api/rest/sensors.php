<?php
class ChRest_Sensors extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		if(is_numeric($action) && !empty($stack)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
// 				case 'note':
// 					$this->postNote($id);
// 					break;
			}
			
		} else {
			switch($action) {
				case 'bulk_update':
					$this->postBulkUpdate();
					break;
				case 'create':
					$this->postCreate();
					break;
				case 'search':
					$this->postSearch();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$id = array_shift($stack);
		
		$worker = CerberusApplication::getActiveWorker();

		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.datacenter.sensor.delete'))
			$this->error(self::ERRNO_ACL);
		
		if(null == ($sensor = DAO_DatacenterSensor::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid sensor ID %d", $id));

		DAO_DatacenterSensor::delete($id);

		$result = array('id' => $id);
		$this->success($result);
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'metric' => DAO_DatacenterSensor::METRIC,
				'name' => DAO_DatacenterSensor::NAME,
				'output' => DAO_DatacenterSensor::OUTPUT,
				'status' => DAO_DatacenterSensor::STATUS,
				'type' => DAO_DatacenterSensor::EXTENSION_ID,
				'updated' => DAO_DatacenterSensor::UPDATED,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_CrmOpportunity::VIRTUAL_WATCHERS,
					
				'extension_id' => SearchFields_DatacenterSensor::EXTENSION_ID,
				'is_disabled' => SearchFields_DatacenterSensor::IS_DISABLED,
				'status' => SearchFields_DatacenterSensor::STATUS,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_SENSOR);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'id' => SearchFields_DatacenterSensor::ID,
				'metric' => SearchFields_DatacenterSensor::METRIC,
				'name' => SearchFields_DatacenterSensor::NAME,
				'output' => SearchFields_DatacenterSensor::OUTPUT,
				'status' => SearchFields_DatacenterSensor::STATUS,
				'type' => SearchFields_DatacenterSensor::EXTENSION_ID,
				'updated' => SearchFields_DatacenterSensor::UPDATED,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}

	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_SENSOR, $model, $labels, $values, null, true);

		return $values;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
//		if(!$worker->hasPriv('...'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid sensor id '%d'", $id));
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = array();
				
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_SENSOR,
			$params,
			$limit,
			$page,
			$sortBy,
			$sortAsc
		);
		
		if(!empty($query) && $view instanceof IAbstractView_QuickSearch)
			$view->addParamsWithQuickSearch($query, true);

		// If we're given explicit filters, merge them in to our quick search
		if(!empty($filters)) {
			if(!empty($query))
				$params = $view->getParams(false);
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_SENSOR);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_SENSOR, array_keys($results));
			
			unset($results);
			
			foreach($models as $id => $model) {
				$values = $this->getContext($model);
				$objects[$id] = $values;
			}
		}
		
		$container = array();
		
		if($show_results) {
			$container['results'] = $objects;
			$container['total'] = $total;
			$container['count'] = count($objects);
			$container['page'] = $page;
		}
		
		if(!empty($subtotals)) {
			$container['subtotals'] = $subtotal_data;
		}
		
		return $container;
	}
	
	function postBulkUpdate() {
		$worker = CerberusApplication::getActiveWorker();

		$payload = $this->getPayload();
		$xml = simplexml_load_string($payload);
		
		foreach($xml->sensor as $eSensor) {
			@$sensor_id = (string) $eSensor['id'];
			@$name = (string) $eSensor->name;
			@$status = (string) $eSensor->status;
			@$metric = (string) $eSensor->metric;
			@$metric_type = (string) $eSensor->metric_type;
			@$output = (string) $eSensor->output;
			
			$sensor = null;
			
			if(!is_numeric($sensor_id)) {
				// Look up by tag
				$tag = $sensor_id;
				
				// Look it up or create it
				if(null == ($sensor = DAO_DatacenterSensor::getByTag($tag))) {
					$fields = array(
						DAO_DatacenterSensor::TAG => $tag,
						DAO_DatacenterSensor::NAME => (!empty($name) ? $name : $tag),
						DAO_DatacenterSensor::EXTENSION_ID => 'cerberusweb.datacenter.sensor.external',
						DAO_DatacenterSensor::PARAMS_JSON => json_encode(array()),
					);
					$sensor_id = DAO_DatacenterSensor::create($fields);
					
				} else {
					$sensor_id = $sensor->id;
				}
					
			} else {
				if(null == ($sensor = DAO_DatacenterSensor::get($sensor_id)))
					$sensor_id = null;
			}

			if(is_numeric($sensor_id) && !empty($sensor_id)) {
				$fields = array(
					DAO_DatacenterSensor::OUTPUT => $output,
					DAO_DatacenterSensor::METRIC => $metric,
					DAO_DatacenterSensor::METRIC_TYPE => $metric_type,
					DAO_DatacenterSensor::UPDATED => time(),
				);
				
				if(!empty($name))
					$fields[DAO_DatacenterSensor::NAME] = $name;
				
				if(!empty($status) && in_array($status, array('O', 'W', 'C')))
					$fields[DAO_DatacenterSensor::STATUS] = $status;
				
				DAO_DatacenterSensor::update($sensor_id, $fields);
			}
		}
		
		$this->success(array());
	}
	
	function postSearch() {
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// Validate the ID
		if(null == ($sensor = DAO_DatacenterSensor::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid sensor ID '%d'", $id));
			
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.datacenter.sensor.update'))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'metric' => 'string',
			'name' => 'string',
			'output' => 'string',
			'status' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();

		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_DatacenterSensor::UPDATED]))
			$fields[DAO_DatacenterSensor::UPDATED] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_SENSOR, $id, $customfields, true, true, true);
		
		// Update
		DAO_DatacenterSensor::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.datacenter.sensor.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'metric' => 'string',
			'name' => 'string',
			'output' => 'string',
			'status' => 'string',
			'type' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
				
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}

			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			switch($field) {
				case 'type':
					$field = DAO_DatacenterSensor::EXTENSION_ID;
					$ext_id = null;
					
					switch($value) {
						case 'external':
							$ext_id = 'cerberusweb.datacenter.sensor.external';
							break;
						case 'http':
							$ext_id = 'cerberusweb.datacenter.sensor.http';
							break;
						case 'port':
							$ext_id = 'cerberusweb.datacenter.sensor.port';
							break;
						default:
							// Allow custom sensors as long as they're well-formed
							if(null != ($ext = DevblocksPlatform::getExtension($value, true))) {
								if(is_a($ext,'Extension_Sensor'))
									$ext_id = $ext->id;
							}
							break;
					}
					
					$fields[$field] = $ext_id;
					break;
					
				default:
					$fields[$field] = $value;
					break;
			}
		}

		// Defaults
		if(!isset($fields[DAO_DatacenterSensor::UPDATED]))
			$fields[DAO_DatacenterSensor::UPDATED] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_DatacenterSensor::NAME,
			DAO_DatacenterSensor::EXTENSION_ID,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_DatacenterSensor::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_SENSOR, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}

};