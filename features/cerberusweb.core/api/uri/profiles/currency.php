<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
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

class PageSection_ProfilesCurrency extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // currency 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($currency = DAO_Currency::get($id))) {
			return;
		}
		$tpl->assign('currency', $currency);
		
		// Tab persistence
		
		$point = 'profiles.currency.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['symbol'] = array(
			'label' => mb_ucfirst($translate->_('dao.currency.symbol')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $currency->symbol,
		);
		
		$properties['code'] = array(
			'label' => mb_ucfirst($translate->_('dao.currency.code')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $currency->code,
		);
		
		$properties['decimal_at'] = array(
			'label' => mb_ucfirst($translate->_('dao.currency.decimal_at')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $currency->decimal_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $currency->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CURRENCY, $currency->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CURRENCY, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CURRENCY, $currency->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CURRENCY => array(
				$currency->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CURRENCY,
						$currency->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CURRENCY);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/currency.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CURRENCY)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Currency::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$name_plural = DevblocksPlatform::importGPC($_REQUEST['name_plural'], 'string', '');
				@$symbol = DevblocksPlatform::importGPC($_REQUEST['symbol'], 'string', '');
				@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
				@$decimal_at = DevblocksPlatform::importGPC($_REQUEST['decimal_at'], 'integer', 0);
				@$is_default = DevblocksPlatform::importGPC($_REQUEST['is_default'], 'integer', 0);
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Currency::NAME => $name,
						DAO_Currency::NAME_PLURAL => $name_plural,
						DAO_Currency::SYMBOL => $symbol,
						DAO_Currency::CODE => $code,
						DAO_Currency::DECIMAL_AT => $decimal_at,
						DAO_Currency::UPDATED_AT => time(),
					);
					
					if(!DAO_Currency::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_Currency::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Currency::create($fields);
					DAO_Currency::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CURRENCY, $id);
					
				} else { // Edit
					$fields = array(
						DAO_Currency::NAME => $name,
						DAO_Currency::NAME_PLURAL => $name_plural,
						DAO_Currency::SYMBOL => $symbol,
						DAO_Currency::CODE => $code,
						DAO_Currency::DECIMAL_AT => $decimal_at,
						DAO_Currency::UPDATED_AT => time(),
					);
					
					if(!DAO_Currency::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_Currency::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Currency::update($id, $fields);
					DAO_Currency::onUpdateByActor($active_worker, $id, $fields);
				}
				
				if($id) {
					if($is_default)
						DAO_Currency::setDefault($id);
					
					// Custom fields
					@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', []);
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CURRENCY, $id, $field_ids);
				}
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
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
			$models = [];
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=currency', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.currency.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=currency&id=%d-%s", $row[SearchFields_Currency::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Currency::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Currency::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
