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

class PageSection_ProfilesGpgPublicKey extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // gpg_public_key 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($gpg_public_key = DAO_GpgPublicKey::get($id))) {
			return;
		}
		$tpl->assign('gpg_public_key', $gpg_public_key);
		
		// Tab persistence
		
		$point = 'profiles.gpg_public_key.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = array();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $gpg_public_key->name,
		);
		
		$properties['fingerprint'] = array(
			'label' => mb_ucfirst($translate->_('dao.gpg_public_key.fingerprint')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $gpg_public_key->fingerprint,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $gpg_public_key->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $gpg_public_key->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $gpg_public_key->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_GPG_PUBLIC_KEY => array(
				$gpg_public_key->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_GPG_PUBLIC_KEY,
						$gpg_public_key->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_GPG_PUBLIC_KEY);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/gpg_public_key.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$gpg = DevblocksPlatform::services()->gpg();
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$gpg || !$gpg->isEnabled())
				throw new Exception_DevblocksAjaxValidationError("The 'gnupg' PHP extension is not enabled.");
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_GpgPublicKey::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$public_key = DevblocksPlatform::importGPC($_REQUEST['public_key'], 'string', '');
				
				if(empty($id)) { // New
					if(empty($public_key))
						throw new Exception_DevblocksAjaxValidationError("The 'Key' field is required.", 'public_key');
					
					if(false == ($import_info = $gpg->importKey($public_key)) || !isset($import_info['fingerprint']))
						throw new Exception_DevblocksAjaxValidationError("Failed to decrypt the given public key.", 'public_key');
					
					$results = $gpg->keyinfo($import_info['fingerprint']);
					
					if(false == $results || !is_array($results) || empty($results) || false == ($keyinfo = array_shift($results)))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve public key info.", 'public_key');
					
					if(!isset($keyinfo['uids']) || !is_array($keyinfo['uids']) || empty($keyinfo['uids']))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve public key UID info.", 'public_key');
					
					if(!$keyinfo['can_sign'] || !$keyinfo['can_encrypt'] || $keyinfo['is_secret'])
						throw new Exception_DevblocksAjaxValidationError("This is not a valid public key.", "public_key");
					
					$key = null;
					
					foreach($keyinfo['subkeys'] as $idx => $subkey) {
						if(0 == strcasecmp($subkey['fingerprint'], $import_info['fingerprint'])) {
							$key = $subkey;
							break;
						}
					}
					
					if(empty($key))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve public key subkey info.", "public_key");
					
					if($key['expired'] || $key['disabled'] || $key['revoked'])
						throw new Exception_DevblocksAjaxValidationError("This public key is expired, revoked, or disabled.", "public_key");
						
					if(empty($name))
						@$name = $keyinfo['uids'][0]['uid'];
					
					$fields = array(
						DAO_GpgPublicKey::NAME => $name,
						DAO_GpgPublicKey::FINGERPRINT => $import_info['fingerprint'],
						DAO_GpgPublicKey::EXPIRES_AT => $key['expires'],
						DAO_GpgPublicKey::UPDATED_AT => time(),
					);
					
					// If this fingerprint already exists, return the existing key info
					if(false != ($record = DAO_GpgPublicKey::getByFingerprint($import_info['fingerprint']))) {
						echo json_encode(array(
							'status' => true,
							'id' => $record->id,
							'label' => $record->name,
							'view_id' => $view_id,
						));
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $record->id);
						return;
					}
					
					if(false != ($id = DAO_GpgPublicKey::create($fields))) {
						if(!empty($view_id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id);
					}
					
					
				} else { // Edit
					$fields = array(
						DAO_GpgPublicKey::NAME => $name,
						DAO_GpgPublicKey::UPDATED_AT => time(),
					);
					
					DAO_GpgPublicKey::update($id, $fields);
				}
				
				if($id) {
					// Links
					
					$uid_emails = [];
					
					foreach($keyinfo['uids'] as $idx => $uid) {
						if(!isset($uid['email']))
							continue;
						
						$uid_emails[DevblocksPlatform::strLower($uid['email'])] = true;
					}
					
					$email_addys = DAO_Address::lookupAddresses(array_keys($uid_emails), true);
					
					foreach($email_addys as $email_addy) {
						DAO_ContextLink::setLink(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, CerberusContexts::CONTEXT_ADDRESS, $email_addy->id);
						
						// Has contact
						if($email_addy->contact_id)
							DAO_ContextLink::setLink(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, CerberusContexts::CONTEXT_CONTACT, $email_addy->contact_id);
						
						// Has bare org
						if($email_addy->contact_org_id && !$email_addy->contact_id)
							DAO_ContextLink::setLink(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, CerberusContexts::CONTEXT_ORG, $email_addy->contact_org_id);
					}
					
					// Custom fields
					
					@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, $field_ids);
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
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=gpg_public_key', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.gpg_public_key.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=gpg_public_key&id=%d-%s", $row[SearchFields_GpgPublicKey::ID], DevblocksPlatform::strToPermalink($row[SearchFields_GpgPublicKey::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_GpgPublicKey::ID],
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
