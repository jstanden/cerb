<?php
class ChRest_Records extends Extension_RestController {
	function getAction($stack) {
		@$alias = array_shift($stack);
		@$action = array_shift($stack);
		
		switch($action) {
			case 'search':
				if(false == ($context = $this->_getContextByAliasOrId($alias)))
					$this->error(self::ERRNO_NOT_FOUND);
				
				$this->_getContextSearch($context);
				break;
				
			default:
				if(!is_numeric($action) || false == ($context = $this->_getContextByAliasOrId($alias)))
					$this->error(self::ERRNO_NOT_FOUND);
				
				array_unshift($stack, $action);
				$this->_getContextRecord($context, $stack);
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function patchAction($stack) {
		@$alias = array_shift($stack);
		@$action = array_shift($stack);
		
		switch($action) {
			case 'upsert':
				if(false == ($context = $this->_getContextByAliasOrId($alias)))
					$this->error(self::ERRNO_NOT_FOUND);
				
				$this->_upsertContextRecord($context, $stack);
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$alias = array_shift($stack);
		@$action = array_shift($stack);
		
		switch($action) {
			default:
				if(!is_numeric($action) || false == ($context = $this->_getContextByAliasOrId($alias)))
					$this->error(self::ERRNO_NOT_FOUND);
				
				array_unshift($stack, $action);
				$this->_updateContextRecord($context, $stack);
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$alias = array_shift($stack);
		@$action = array_shift($stack);
		
		switch($action) {
			case 'create':
				if(false == ($context = $this->_getContextByAliasOrId($alias)))
					$this->error(self::ERRNO_NOT_FOUND);
				
				$this->_createContextRecord($context, $stack);
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		@$alias = array_shift($stack);
		@$action = array_shift($stack);
		
		switch($action) {
			default:
				if(!is_numeric($action) || false == ($context = $this->_getContextByAliasOrId($alias)))
					$this->error(self::ERRNO_NOT_FOUND);
				
				array_unshift($stack, $action);
				$this->_deleteContextRecord($context, $stack);
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function _getContextByAliasOrId($name) {
		if(false != ($context = Extension_DevblocksContext::getByAlias($name, false))) {
			return $context;
			
		} else if(false != ($context = Extension_DevblocksContext::get($name, false))) {
			return $context;
		}
		
		return false;
	}
	
	private function _verifyContextString($string) {
		list($context, $context_id) = array_pad(explode(':', $string, 2), 2, null);
		return $this->_verifyContext($context, $context_id);
	}
	
	private function _verifyContext($context, $context_id) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return false;
		
		if(false === (CerberusContexts::isReadableByActor($context, $context_id, $active_worker)))
			return false;
		
		if(false == (@$meta = $context_ext->getMeta($context_id)))
			return false;
		
		return array(
			'context' => $context,
			'context_id' => intval($context_id),
			'meta' => $meta,
		);
	}
	
	private function _upsertContextRecord(DevblocksExtensionManifest $context) {
		$query = DevblocksPlatform::importGPC($_REQUEST['query'] ?? null, 'string', '');
		
		if(empty($query))
			$this->error(self::ERRNO_PARAM_REQUIRED, "The 'query' parameter is required.");
		
		if(false == ($context_ext = $context->createInstance())) /* @var $context_ext Extension_DevblocksContext */
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
		if(false == ($view = $context_ext->getChooserView()))
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
		$view->setAutoPersist(false);
		$view->addParamsWithQuickSearch($query, true);
		list($results, $total) = $view->getData();
		
		if(0 == $total) {
			$this->_createContextRecord($context);
			
		} elseif (1 == $total) {
			$this->_updateContextRecord($context, [key($results)]);
			
		} else {
			$this->error(self::ERRNO_NOT_FOUND, "An upsert query must match exactly one or zero records.");
		}
	}
	
	private function _createContextRecord(DevblocksExtensionManifest $context) {
		$fields = DevblocksPlatform::importGPC($_REQUEST['fields'] ?? null, 'array', []);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($context->id))
			$this->error(self::ERRNO_NOT_FOUND, "Invalid context.");
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		// Make sure the worker has access to create records of this type
		if(!$active_worker->hasPriv(sprintf("contexts.%s.create", $context_ext->id)))
			$this->error(self::ERRNO_ACL, DevblocksPlatform::translate('error.core.no_acl.create'));
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		// Fail of there's no DAO::create() method
		if(!method_exists($dao_class, 'create'))
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
		$error = null;
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($fields, $dao_fields, $custom_fields, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);

		// Check implementation permissions
		if(!$dao_class::onBeforeUpdateByActor($active_worker, $dao_fields, null, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(false == ($id = $dao_class::create($dao_fields)))
			$this->error(self::ERRNO_PARAM_INVALID, "Failed to create the record.");
		
		$dao_class::onUpdateByActor($active_worker, $dao_fields, $id);
		
		if($custom_fields)
			DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $id, $custom_fields);
		
		$this->_getContextRecord($context_ext->manifest, [$id]);
	}
	
	private function _updateContextRecord(DevblocksExtensionManifest $context, array $stack) {
		@$id = intval(array_shift($stack));
		$fields = DevblocksPlatform::importGPC($_REQUEST['fields'] ?? null, 'array', []);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($context->id))
			$this->error(self::ERRNO_NOT_FOUND, "Invalid context.");
		
		// Make sure we can create records of this type
		if(!$context->hasOption('records'))
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */
		
		$models = $context_ext->getModelObjects([$id]);
		
		if(!isset($models[$id]))
			$this->error(self::ERRNO_NOT_FOUND, sprintf("Record #%d not found", $id));
		
		if(!CerberusContexts::isWriteableByActor($context->id, $models[$id], $active_worker))
			$this->error(self::ERRNO_ACL, DevblocksPlatform::translate('error.core.no_acl.edit'));
		
		$dao_class = $context_ext->getDaoClass();
		$dao_fields = $custom_fields = [];
		
		$error = null;
		
		if(!method_exists($dao_class, 'update'))
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
		if(!method_exists($context_ext, 'getDaoFieldsFromKeysAndValues'))
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
		if(!$context_ext->getDaoFieldsFromKeysAndValues($fields, $dao_fields, $custom_fields, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(is_array($dao_fields))
		if(!$dao_class::validate($dao_fields, $error, $id))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if($custom_fields)
		if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		if(!$dao_class::onBeforeUpdateByActor($active_worker, $dao_fields, $id, $error))
			$this->error(self::ERRNO_PARAM_INVALID, $error);
		
		$dao_class::update($id, $dao_fields);
		
		$dao_class::onUpdateByActor($active_worker, $dao_fields, $id);
		
		if($custom_fields)
			DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $id, $custom_fields);
		
		$this->_getContextRecord($context_ext->manifest, [$id]);
	}
	
	private function _deleteContextRecord(DevblocksExtensionManifest $context, array $stack) {
		@$id = intval(array_shift($stack));
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($context->id))
			$this->error(self::ERRNO_NOT_FOUND, "Invalid context.");
		
		$context_ext = $context->createInstance(); /* @var $context_ext Extension_DevblocksContext */

		$dao_class = $context_ext->getDaoClass();
		
		if(!method_exists($dao_class, 'get') || false == ($model = $dao_class::get($id)))
			$this->error(self::ERRNO_NOT_FOUND, sprintf("Record #%d not found", $id));
		
		if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context_ext->id)))
			$this->error(self::ERRNO_ACL, DevblocksPlatform::translate('error.core.no_acl.delete'));
		
		if(!CerberusContexts::isDeletableByActor($context->id, $model, $active_worker))
			$this->error(self::ERRNO_ACL, DevblocksPlatform::translate('error.core.no_acl.delete'));
		
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels([$model->id => $model], $context->id);
		
		if(false != (@$dict = $dicts[$model->id])) {
			CerberusContexts::logActivityRecordDelete($context_ext, $model->id, $dict->_label);
		}
		
		$dao_class::delete($id);
		
		$this->success([]);
	}
	
	private function _getContextRecord(DevblocksExtensionManifest $context, array $stack) {
		@$id = intval(array_shift($stack));
		@$show_meta = DevblocksPlatform::importVar($_REQUEST['show_meta'], 'boolean', false);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($context->id))
			$this->error(self::ERRNO_NOT_FOUND);
		
		// Check read permission
		if(!CerberusContexts::isReadableByActor($context->id, $id, $active_worker))
			$this->error(self::ERRNO_ACL, DevblocksPlatform::translate('error.core.no_acl.view'));
		
		$container = $this->search(
			[],
			null, // 'id'
			true,
			1,
			10,
			[
				'query' => 'id:' . $id,
			],
			$context
		);
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id])) {
			$container = $container['results'][$id];
			
			if($show_meta) {
				$this->_includeContextMeta($context, $container);
			}
			
			$this->success($container);
		}

		// Error
		$this->error(self::ERRNO_NOT_FOUND, sprintf("Invalid record %s #%d", $context->id, $id));
	}
	
	function search($filters=[], $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=[], DevblocksExtensionManifest $context) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', []);
		
		$limit = DevblocksPlatform::intClamp($limit, 1, 500);
		
		$params = [];
		
		// Search
		
		$view = $this->_getSearchView(
			$context->id,
			$params,
			$limit,
			$page
		);
		
		if(!($view instanceof C4_AbstractView))
			return [];
		
		if(!empty($query) && $view instanceof IAbstractView_QuickSearch)
			$view->addParamsWithQuickSearch($query, true);

		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleRecordSubtotals($view, $subtotals, $context);
		
		$objects = [];
		
		if($show_results) {
			$models = CerberusContexts::getModels($context->id, array_keys($results));
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context->id);
			
			foreach($dicts as $dict_id => $dict)
				$objects[$dict_id] = $dict->getDictionary('', false);
		}
		
		$container = [];
		
		if($show_results) {
			$container['results'] = $objects;
			$container['total'] = intval($total);
			$container['count'] = count($objects);
			$container['page'] = intval($page);
			$container['limit'] = intval($limit);
		}
		
		if(!empty($subtotals)) {
			$container['subtotals'] = $subtotal_data;
		}
		
		return $container;
	}
	
	/**
	 * @param C4_AbstractView $view
	 * @param array $subtotals
	 * @param DevblocksExtensionManifest $context
	 * @return array
	 */
	protected function _handleRecordSubtotals($view, $subtotals, $context) {
		$subtotal_data = [];
		
		if(!($view instanceof IAbstractView_QuickSearch))
			return [];
		
		if(false == ($query_fields = $view->getQuickSearchFields()))
			return [];
		
		if(is_array($subtotals) && !empty($subtotals)) {
			foreach($subtotals as $subtotal) {
				if(null == ($query_field = $query_fields[$subtotal]))
					$this->error(self::ERRNO_SEARCH_FILTERS_INVALID, sprintf("'%s' is not a valid subtotal token.", $subtotal));
				
				if(false == ($field = $query_field['options']['param_key']))
					$this->error(self::ERRNO_SEARCH_FILTERS_INVALID, sprintf("'%s' is not a valid subtotal token.", $subtotal));
				
				$counts = $view->getSubtotalCounts($field);
				
				$subtotal_data[$subtotal] = [];
				
				foreach($counts as $key => $count) {
					$data = array(
						'label' => $count['label'],
						'hits' => intval($count['hits']),
					);
					
					if(0 != strcasecmp($count['label'], $key))
						$data['key'] = $key;
					
					if(isset($count['children']) && !empty($count['children'])) {
						$data['distribution'] = [];
						
						foreach($count['children'] as $child_key => $child) {
							$child_data = array(
								'label' => $child['label'],
								'hits' => intval($child['hits']),
							);
							
							if(0 != strcasecmp($child['label'], $child_key))
								$child_data['key'] = $child_key;
							
							$data['distribution'][] = $child_data;
						}
					}
					
					$subtotal_data[$subtotal][] = $data;
				}
			}
		}
		
		return $subtotal_data;
	}
	
	private function _getContextSearch(DevblocksExtensionManifest $context) { 
		if(!$context->hasOption('search')) {
			$this->error(self::ERRNO_NOT_IMPLEMENTED);
		}
		
		@$show_meta = DevblocksPlatform::importVar($_REQUEST['show_meta'], 'boolean', false);
		
		$container = $this->_handlePostSearch($context);
		
		if($show_meta) {
			$this->_includeContextMeta($context, $container);
		}
		
		$this->success($container);
	}
	
	private function _includeContextMeta(DevblocksExtensionManifest $context, array &$container) {
		$context_ext = $context->createInstance();
		$token_labels = $token_values = [];
		$context_ext->getContext(null, $token_labels, $token_values);
		
		$meta_dict = DevblocksDictionaryDelegate::instance($token_values);
		$meta_dict->custom_;
		
		unset($token_labels);
		unset($token_values);
		
		$container['_meta'] = [
			'labels' => $meta_dict->_labels,
			'types' => $meta_dict->_types,
		];
	}
};