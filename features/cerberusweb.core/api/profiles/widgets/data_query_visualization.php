<?php
class ProfileWidget_Visualization extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.visualization';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
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
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
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