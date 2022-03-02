<?php
class ChRest_Contexts extends Extension_RestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'list':
				$this->getContextList();
				break;
				
			case 'activity':
				@$subaction = array_shift($stack);
				
				switch($subaction) {
					case 'events':
						$this->getActivityEventsList();
						break;
				}
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'link':
				$this->postLink();
				break;
				
			case 'unlink':
				$this->postUnlink();
				break;
				
			case 'activity':
				@$subaction = array_shift($stack);
				
				switch($subaction) {
					case 'create':
						$this->postActivityCreate();
						break;
				}
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
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
	
	private function getContextList() {
		$results = array();
		
		$contexts = Extension_DevblocksContext::getAll();
		
		foreach($contexts as $context) {
			$result = array(
				'id' => $context->id,
				'name' => $context->name,
				'plugin_id' => $context->plugin_id,
				'alias' => $context->params['alias'],
				'params' => [
					'names' => $context->params['names'][0],
					'acl' => @$context->params['acl'][0] ? array_keys($context->params['acl'][0]) : [],
					'options' => @$context->params['options'][0] ? array_keys($context->params['options'][0]) : [],
				]
			);
			
			// [TODO] Filter custom fieldsets by owner?  (API worker?)
			
			$custom_fieldsets = DAO_CustomFieldset::getByContext($context->id);
			$custom_fields = DAO_CustomField::getByContext($context->id, false);
			
			$labels = array();
			
			$result_fields = array();
			$result_fieldsets = array();
			
			foreach($custom_fields as $cfield) {
				$merge_labels = array();
				$merge_values = array();
				CerberusContexts::getContext(CerberusContexts::CONTEXT_CUSTOM_FIELD, $cfield, $merge_labels, $merge_values, null, true, true);
				
				CerberusContexts::scrubTokensWithRegexp(
					$merge_labels,
					$merge_values,
					array(
						'#^_context$#',
						'#^_label$#',
						'#^_loaded$#',
						'#^custom_fieldset_id$#',
						'#^context$#',
						'#^pos$#',
					)
				);
				
				if(!empty($merge_values))
					$result_fields[] = $merge_values;
			}
			
			if(!empty($result_fields))
				$result['custom_fields'] = $result_fields;
			
			foreach($custom_fieldsets as $fieldset_id => $fieldset) {
				$merge_labels = array();
				$merge_values = array();
				CerberusContexts::getContext(CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $fieldset, $merge_labels, $merge_values, null, true, true);
				
				if(!empty($merge_values)) {
					$merge_dict = new DevblocksDictionaryDelegate($merge_values);
					$merge_dict->custom_fields;
					
					$merge_values = $merge_dict->getDictionary();
					
					CerberusContexts::scrubTokensWithRegexp(
						$merge_labels,
						$merge_values,
						array(
							'#^_context$#',
							'#^_types#',
							'#^_labels$#',
							'#^_label$#',
							'#^_loaded$#',
							'#^custom_fieldset_id$#',
							'#^context$#',
						)
					);
					
					if(isset($merge_values['custom_fields']))
					foreach($merge_values['custom_fields'] as &$merge_cfields_values) {
						$merge_cfields_labels = array();
						
						CerberusContexts::scrubTokensWithRegexp(
							$merge_cfields_labels,
							$merge_cfields_values,
							array(
								'#^_context$#',
								'#^_label$#',
								'#^_loaded$#',
								'#^custom_fieldset_id$#',
								'#^context$#',
								'#^pos$#',
							)
						);
					}
					
					$result_fieldsets[] = $merge_values;
				}
				
			}
			
			$result['custom_fieldsets'] = $result_fieldsets;
			
			$results[$context->id] = $result;
		}
		
		$this->success(array('results' => $results));
	}
	
	private function getActivityEventsList() {
		$results = array();

		$translate = DevblocksPlatform::getTranslationService();
		$activities = DevblocksPlatform::getActivityPointRegistry();

		foreach($activities as $activity) {
			if(isset($activity['point'])
				&& isset($activity['params'])
				&& isset($activity['params'])) {
					if(!isset($activity['params']['label_key']))
						continue;
				
					$result = array(
						'id' => $activity['point'],
						'name' => $translate->_($activity['params']['label_key']),
						'options' => array(),
						'text' => array(
							'key' => '',
							'value' => '',
						)
					);
					
					if(isset($activity['params']['string_key'])) {
						$result['text']['key'] = isset($activity['params']['string_key']) ? $activity['params']['string_key'] : '';
						$result['text']['value'] = isset($activity['params']['string_key']) ? $translate->_($activity['params']['string_key']) : '';
					}
					
					if(isset($activity['params']['options'])) {
						$result['options'] = DevblocksPlatform::parseCsvString($activity['params']['options']);
					}
					
					$results[] = $result;
			}
		}
		
		$this->success(array('results' => $results));
	}
	
	private function postLink() {
		$on = DevblocksPlatform::importGPC($_POST['on'] ?? null, 'string', '');
		$targets = DevblocksPlatform::importGPC($_POST['targets'] ?? null, 'string', '');
		
		// Verify the 'on' context and accessibility by active worker
		if(false == ($result_on = $this->_verifyContextString($on)))
			$this->error(self::ERRNO_CUSTOM, sprintf("The 'on' value of '%s' is not valid.", $on));
		
		// Verify a well-formed JSON array for 'targets', and that each exists
		@$targets = json_decode($targets, true);
		
		if(!is_array($targets))
			$this->error(self::ERRNO_CUSTOM, "The 'targets' parameter should be a JSON formatted array of context:id pairs.");
		
		$result_targets = array();
		
		foreach($targets as $target) {
			if(false == ($result_target = $this->_verifyContextString($target)))
				$this->error(self::ERRNO_CUSTOM, sprintf("The 'targets' value of '%s' is not valid.", $target));
			
			$result_targets[] = $result_target;
		}
		
		foreach($result_targets as $result_target) {
			// [TODO] If false, return the list of errors
			DAO_ContextLink::setLink($result_on['context'], $result_on['context_id'], $result_target['context'], $result_target['context_id'], $result_on['meta'], $result_target['meta']);
		}
		
		$this->success(array(
			'on' => $result_on,
			'targets' => $result_targets,
		));
	}
	
	private function postUnlink() {
		$on = DevblocksPlatform::importGPC($_POST['on'] ?? null, 'string', '');
		$targets = DevblocksPlatform::importGPC($_POST['targets'] ?? null, 'string', '');
		
		// Verify the 'on' context and accessibility by active worker
		if(false == ($result_on = $this->_verifyContextString($on)))
			$this->error(self::ERRNO_CUSTOM, sprintf("The 'on' value of '%s' is not valid.", $on));
		
		// Verify a well-formed JSON array for 'targets', and that each exists
		@$targets = json_decode($targets, true);
		
		if(!is_array($targets))
			$this->error(self::ERRNO_CUSTOM, "The 'targets' parameter should be a JSON formatted array of context:id pairs.");
		
		$result_targets = array();
		
		foreach($targets as $target) {
			if(false == ($result_target = $this->_verifyContextString($target)))
				$this->error(self::ERRNO_CUSTOM, sprintf("The 'targets' value of '%s' is not valid.", $target));
			
			$result_targets[] = $result_target;
		}
		
		foreach($result_targets as $result_target) {
			// [TODO] If false, return the list of errors
			DAO_ContextLink::deleteLink($result_on['context'], $result_on['context_id'], $result_target['context'], $result_target['context_id'], $result_on['meta'], $result_target['meta']);
		}
		
		$this->success(array(
			'on' => $result_on,
			'targets' => $result_targets,
		));
	}
	
	private function postActivityCreate() {
		$on = DevblocksPlatform::importGPC($_POST['on'] ?? null, 'string', '');
		$activity_point = DevblocksPlatform::strLower(DevblocksPlatform::importGPC($_POST['activity_point'] ?? null, 'string', ''));
		$variables_json = DevblocksPlatform::importGPC($_POST['variables'] ?? null, 'string', '');
		$urls_json = DevblocksPlatform::importGPC($_POST['urls'] ?? null, 'string', '');

		// [TODO] Actor impersonation
		
		$activities = DevblocksPlatform::getActivityPointRegistry();
		$translate = DevblocksPlatform::getTranslationService();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// Verify the 'on' context and accessibility by active worker
		if(false == ($result_on = $this->_verifyContextString($on)))
			$this->error(self::ERRNO_CUSTOM, sprintf("The 'on' value of '%s' is not valid.", $on));
		
		// Verify the activity point
		if(!isset($activities[$activity_point]))
			$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid activity point.", $activity_point));

		$activity = $activities[$activity_point] ?? null;
		$activity_params = $activity['params'] ?? null;
		$activity_options = DevblocksPlatform::parseCsvString($activity_params['options'] ?? null);
		$variables = [];
		$urls = [];
		
		// Verify that we have a translation for this activity point
		if(!is_array($activity_params) || !isset($activity_params['string_key']))
			$this->error(self::ERRNO_CUSTOM, sprintf("The '%s' event doesn't have any translation data set.", $activity_point));
		
		// Verify that the activity is API createable
		if(!is_array($activity_options) || !in_array('api_create', $activity_options))
			$this->error(self::ERRNO_CUSTOM, sprintf("Creation of '%s' events are not permitted through the API.", $activity_point));
		
		// Verify variables
		if(!empty($variables_json) && (false == ($variables = json_decode($variables_json, true)) || !is_array($variables)))
			$this->error(self::ERRNO_CUSTOM, "The 'variables' parameter should be a JSON formatted array.");
		
		// Verify URLs
		if(!empty($urls_json) && (false == ($urls = json_decode($urls_json, true)) || !is_array($urls)))
			$this->error(self::ERRNO_CUSTOM, "The 'urls' parameter should be a JSON formatted array.");
		
		// Check for missing variables in the activity log text
		
		$tokens = $tpl_builder->tokenize($translate->_($activity_params['string_key']));
		
		if(is_array($tokens))
		foreach($tokens as $token) {
			if(in_array($token, array('actor')))
				continue;
			
			if(!is_array($variables) || !isset($variables[$token]))
				$this->error(self::ERRNO_CUSTOM, sprintf("The 'variables' parameter doesn't contain a value for '%s'.", $token));
		}
		
		// Log it
		
		$entry = array(
			'message' => $activity_params['string_key'],
			'variables' => $variables,
			'urls' => $urls
		);
		$entry_id = CerberusContexts::logActivity($activity_point, $result_on['context'], $result_on['context_id'], $entry, null, null);

		$entry['id'] = $entry_id;
		$entry['activity_point'] = $activity_point;
		$entry['message'] = array(
			'plaintext' => CerberusContexts::formatActivityLogEntry($entry, 'plaintext'),
			'html' => CerberusContexts::formatActivityLogEntry($entry, 'html'),
		);
		
		$result = array(
			'entry' => $entry,
		);
		
		$this->success($result);
	}
};