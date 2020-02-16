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

class PageSection_SetupMailIncoming extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();

		$visit->set(ChConfigurationPage::ID, 'mail_incoming');
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // mail_incoming
		@$tab = array_shift($stack);
		$tpl->assign('tab', $tab);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_incoming/index.tpl');
	}
	
	function saveSettingsJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
			
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			@$default_group_id = DevblocksPlatform::importGPC($_POST['default_group_id'],'integer',0);
			@$parser_autoreq = DevblocksPlatform::importGPC($_POST['parser_autoreq'],'integer',0);
			@$parser_autoreq_exclude = DevblocksPlatform::importGPC($_POST['parser_autoreq_exclude'],'string','');
			@$attachments_enabled = DevblocksPlatform::importGPC($_POST['attachments_enabled'],'integer',0);
			@$attachments_max_size = DevblocksPlatform::importGPC($_POST['attachments_max_size'],'integer',10);
			@$ticket_mask_format = DevblocksPlatform::importGPC($_POST['ticket_mask_format'],'string','');
			@$html_no_strip_microsoft = DevblocksPlatform::importGPC($_POST['html_no_strip_microsoft'],'integer',0);
			
			if(empty($ticket_mask_format))
				$ticket_mask_format = 'LLL-NNNNN-NNN';
				
			// Count the number of combinations in ticket mask pattern
			
			$cardinality = CerberusApplication::generateTicketMaskCardinality($ticket_mask_format);
			if($cardinality < 10000000)
				throw new Exception(sprintf("Error! There are only %s ticket mask combinations.",
					strrev(implode(',',str_split(strrev($cardinality),3)))
				));
			
			// Save
			
			if($default_group_id)
				DAO_Group::setDefaultGroup($default_group_id);
			
			$settings = DevblocksPlatform::services()->pluginSettings();
			$settings->set('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ, $parser_autoreq);
			$settings->set('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, $parser_autoreq_exclude);
			$settings->set('cerberusweb.core',CerberusSettings::ATTACHMENTS_ENABLED, $attachments_enabled);
			$settings->set('cerberusweb.core',CerberusSettings::ATTACHMENTS_MAX_SIZE, $attachments_max_size);
			$settings->set('cerberusweb.core',CerberusSettings::TICKET_MASK_FORMAT, $ticket_mask_format);
			$settings->set('cerberusweb.core',CerberusSettings::HTML_NO_STRIP_MICROSOFT, $html_no_strip_microsoft);
			
			echo json_encode([
				'status' => true,
				'message' => DevblocksPlatform::translate('success.saved_changes'),
			]);
			return;
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage()
			]);
			return;
		}
	}
	
	function testMaskAction() {
		@$ticket_mask_format = DevblocksPlatform::importGPC($_POST['ticket_mask_format'],'string','');
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			$cardinality = CerberusApplication::generateTicketMaskCardinality($ticket_mask_format);
			if($cardinality < 10000000)
				throw new Exception(sprintf("Error! There are only %s ticket mask combinations.",
					strrev(implode(',',str_split(strrev($cardinality),3)))
				));
			
			$sample_mask = CerberusApplication::generateTicketMask($ticket_mask_format);
			
			$output = sprintf("There are %s possible ticket mask combinations (%s)",
				strrev(implode(',',str_split(strrev($cardinality),3))),
				$sample_mask
			);
			
			echo json_encode([
				'status' => true,
				'message' => $output,
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage()
			]);
			return;
		}
	}
	
	function renderTabMailboxesAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass('View_Mailbox');
		$defaults->id = 'setup_mailboxes';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function renderTabMailRoutingAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		$rules = DAO_MailToGroupRule::getAll();
		$tpl->assign('rules', $rules);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		// Custom Field Sources
		$tpl->assign('context_manifests', Extension_DevblocksContext::getAll());
		
		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_incoming/tabs/mail_routing.tpl');
	}
	
	// Form Submit
	function saveRoutingAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		@$sticky_ids = DevblocksPlatform::importGPC($_POST['sticky_ids'],'array',array());
		@$sticky_order = DevblocksPlatform::importGPC($_POST['sticky_order'],'array',array());
		
		@$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->is_superuser)
			return;
		
		// Deletes
		if(!empty($deletes)) {
			DAO_MailToGroupRule::delete($deletes);
		}
		
		// Reordering
		if(is_array($sticky_ids) && is_array($sticky_order))
		foreach($sticky_ids as $idx => $id) {
			@$order = intval($sticky_order[$idx]);
			DAO_MailToGroupRule::update($id, array (
				DAO_MailToGroupRule::STICKY_ORDER => $order
			));
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail_incoming','routing')));
	}
	
	function showMailRoutingRulePanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('group_id', $group_id);
		
		if(null != ($rule = DAO_MailToGroupRule::get($id))) {
			$tpl->assign('rule', $rule);
		}

		// Make sure we're allowed to change this group's setup
		if(!$active_worker->isGroupManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom Fields: Address
		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS, true, true);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG, true, true);
		$tpl->assign('org_fields', $org_fields);

		// Custom Fields: Ticket
		$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, true, true);
		$tpl->assign('ticket_fields', $ticket_fields);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_incoming/tabs/mail_routing_peek.tpl');
	}

	function saveMailRoutingRuleAddAction() {
		$translate = DevblocksPlatform::getTranslationService();

		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		
		@$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->is_superuser)
			return;

		/*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(empty($name))
			$name = $translate->_('Mail Routing Rule');
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNum($rule, '\_\.\-');
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'dayofweek':
					// days
					$days = DevblocksPlatform::importGPC($_POST['value_dayofweek'],'array',array());
					if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
					if(in_array(1,$days)) $criteria['mon'] = 'Monday';
					if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
					if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
					if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
					if(in_array(5,$days)) $criteria['fri'] = 'Friday';
					if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
					unset($criteria['value']);
					break;
					
				case 'timeofday':
					$from = DevblocksPlatform::importGPC($_POST['timeofday_from'],'string','');
					$to = DevblocksPlatform::importGPC($_POST['timeofday_to'],'string','');
					$criteria['from'] = $from;
					$criteria['to'] = $to;
					unset($criteria['value']);
					break;
					
				case 'subject':
					break;
					
				case 'from':
					break;
					
				case 'tocc':
					break;
					
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = DevblocksPlatform::strLower($header);
					break;
					
				case 'body':
					break;
					
				default: // ignore invalids // [TODO] Very redundant
					// Custom fields
					if("cf_" == substr($rule,0,3)) {
						$field_id = intval(substr($rule,3));
						
						if(!isset($custom_fields[$field_id]))
							continue 2;

						switch($custom_fields[$field_id]->type) {
							case Model_CustomField::TYPE_SINGLE_LINE:
							case Model_CustomField::TYPE_MULTI_LINE:
							case Model_CustomField::TYPE_URL:
								$oper = DevblocksPlatform::importGPC($_POST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
								
							case Model_CustomField::TYPE_CURRENCY:
							case Model_CustomField::TYPE_DECIMAL:
								$oper = DevblocksPlatform::importGPC($_POST['value_cf_'.$field_id.'_oper'],'string','=');
								$criteria['oper'] = $oper;
								break;
								
							case Model_CustomField::TYPE_DROPDOWN:
							case Model_CustomField::TYPE_MULTI_CHECKBOX:
							case Model_CustomField::TYPE_WORKER:
								$in_array = DevblocksPlatform::importGPC($_POST['value_cf_'.$field_id],'array',[]);
								$out_array = [];
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $v) {
									$out_array[$v] = $v;
								}
								
								$criteria['value'] = $out_array;
								break;
								
							case Model_CustomField::TYPE_DATE:
								$from = DevblocksPlatform::importGPC($_POST['value_cf_'.$field_id.'_from'],'string','0');
								$to = DevblocksPlatform::importGPC($_POST['value_cf_'.$field_id.'_to'],'string','now');
								$criteria['from'] = $from;
								$criteria['to'] = $to;
								unset($criteria['value']);
								break;
								
							case Model_CustomField::TYPE_NUMBER:
								$oper = DevblocksPlatform::importGPC($_POST['value_cf_'.$field_id.'_oper'],'string','=');
								$criteria['oper'] = $oper;
								$criteria['value'] = intval($value);
								break;
								
							case Model_CustomField::TYPE_CHECKBOX:
								$criteria['value'] = intval($value);
								break;
						}
						
					} else {
						continue 2;
					}
					
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = [];
			
			switch($act) {
				// Move group/bucket
				case 'move':
					@$group_id = DevblocksPlatform::importGPC($_POST['do_move'],'string',null);
					if(0 != strlen($group_id) && false != ($group = DAO_Group::get($group_id))) {
						$action = array(
							'group_id' => $group->id,
						);
					}
					break;
					
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($act,0,3)) {
						$field_id = intval(substr($act,3));
						
						@$custom_field = $custom_fields[$field_id];
						
						if(!$custom_field)
							continue 2;

						$action = [];
						
						switch($custom_fields[$field_id]->type) {
							case Model_CustomField::TYPE_CURRENCY:
							case Model_CustomField::TYPE_DECIMAL:
							case Model_CustomField::TYPE_DROPDOWN:
							case Model_CustomField::TYPE_MULTI_LINE:
							case Model_CustomField::TYPE_SINGLE_LINE:
							case Model_CustomField::TYPE_URL:
							case Model_CustomField::TYPE_WORKER:
								$value = DevblocksPlatform::importGPC($_POST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
								
							case Model_CustomField::TYPE_MULTI_CHECKBOX:
								$in_array = DevblocksPlatform::importGPC($_POST['do_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $v) {
									$out_array[$v] = $v;
								}
								
								$action['value'] = $out_array;
								break;
								
							case Model_CustomField::TYPE_DATE:
								$value = DevblocksPlatform::importGPC($_POST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
								
							case Model_CustomField::TYPE_NUMBER:
							case Model_CustomField::TYPE_CHECKBOX:
								$value = DevblocksPlatform::importGPC($_POST['do_cf_'.$field_id],'string','');
								$action['value'] = intval($value);
								break;
						}
						
					} else {
						continue 2;
					}
					break;
			}
			
			$actions[$act] = $action;
		}

		$fields = array(
			DAO_MailToGroupRule::NAME => $name,
			DAO_MailToGroupRule::IS_STICKY => $is_sticky,
			DAO_MailToGroupRule::CRITERIA_SER => serialize($criterion),
			DAO_MailToGroupRule::ACTIONS_SER => serialize($actions),
		);

		// Only sticky filters can manual order and be stackable
		if(!$is_sticky) {
			$fields[DAO_MailToGroupRule::STICKY_ORDER] = 0;
		}
		
		// Create
		if(empty($id)) {
			$fields[DAO_MailToGroupRule::POS] = 0;
			$id = DAO_MailToGroupRule::create($fields);
			
		// Update
		} else {
			DAO_MailToGroupRule::update($id, $fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','mail_incoming','routing')));
	}
	
	function renderTabMailFilteringAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		$view_id = 'setup_mail_filtering';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_BEHAVIOR);
			$view = $ctx->getChooserView($view_id);
		}
		
		// [TODO] Limit to VA owned bots?
		
		$view->addParamsRequired(array(
			new DevblocksSearchCriteria(SearchFields_TriggerEvent::EVENT_POINT, '=', 'event.mail.received.app'),
		), true);
		
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function renderTabMailImportAction() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_incoming/tabs/mail_import.tpl');
	}
	
	function parseMessageJsonAction() {
		header("Content-Type: application/json");
		
		CerberusContexts::pushActivityDefaultActor(CerberusContexts::CONTEXT_APPLICATION, 0);
		
		$logger = DevblocksPlatform::services()->log('Parser');
		$logger->setLogLevel(4);
		
		ob_start();
		
		$log = null;
		
		try {
			@$message_source = DevblocksPlatform::importGPC($_POST['message_source'],'string','');
	
			$dict = CerberusParser::parseMessageSource($message_source, true, true);
			$json = null;
			
			if(is_object($dict) && !empty($dict->id)) {
				$json = json_encode(array(
					'status' => true,
					'ticket_url' => $dict->url, 
					'ticket_label' => $dict->_label, 
				));
				
			} elseif(null === $dict) {
				$log = ob_get_contents();
				$log = str_replace('<BR>', ' ', $log);
				
				$json = json_encode(array(
					'status' => false,
					'error' => sprintf('Rejected: %s',
						trim($log)
					)
				));
			}
			
			ob_end_clean();
			
			echo $json;
			
		} catch (Exception $e) {
			$log = ob_get_contents();
			$log = str_replace('<BR>', ' ', $log);
			
			$json = json_encode(array(
				'status' => false,
				'error' => trim($e->getMessage()),
				'log' => trim($log),
			));
			
			ob_end_clean();
			
			echo $json;
		}
		
		$logger->setLogLevel(0);
		
		CerberusContexts::popActivityDefaultActor();
	}
	
	function renderTabMailFailedAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_MailParseFail');
		$defaults->id = 'setup_mail_failed';
		$defaults->name = 'Failed Messages';
		$defaults->is_ephemeral = true;
		$defaults->renderLimit = 15;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showMailFailedPeekPopupAction() {
		@$file = basename(DevblocksPlatform::importGPC($_REQUEST['file'],'string',''));
		@$view_id = basename(DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',''));
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		// Resolve any symbolic links
		
		if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
			return false;

		// Make sure our requested file is in the same directory
		
		if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
			return false;
		
		// If the extension isn't .msg, abort.
		if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
			return false;
		
		// Template
		
		$tpl->assign('filename', basename($full_path));

		$tpl->display('devblocks:cerberusweb.core::internal/mail_failed/peek.tpl');
	}
	
	function getRawMessageSourceAction() {
		@$file = basename(DevblocksPlatform::importGPC($_REQUEST['file'],'string',''));
		
		// Resolve any symbolic links
		
		try {
			$active_worker = CerberusApplication::getActiveWorker();
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(403);

			if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
				throw new Exception("Path not found.");
			
			// Make sure our requested file is in the same directory
			
			if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
				throw new Exception("File not found.");
			
			// If the extension isn't .msg, abort.
			if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
				throw new Exception("File not valid.");
			
			// Display the raw message using the envelope encoding
			if(false !== ($mime = new MimeMessage('file', $full_path)) && isset($mime->data['charset'])) {
				$message_encoding = $mime->data['charset'];
				header('Content-Type: text/plain; charset=' . $message_encoding);
				echo file_get_contents($full_path);
			}
			
		} catch (Exception $e) {
		}
	}
	
	function parseFailedMessageJsonAction() {
		header("Content-Type: application/json");
		
		$logger = DevblocksPlatform::services()->log('Parser');
		$logger->setLogLevel(4);
		
		ob_start();
		
		$log = null;
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));

			@$file = DevblocksPlatform::importGPC($_POST['file'],'string','');
			@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
			
			// Resolve any symbolic links
			
			if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
				return false;
	
			// Make sure our requested file is in the same directory
			
			if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
				return false;
			
			// If the extension isn't .msg, abort.
			if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
				return false;
			
			// Parse
			
			if(false === ($dict = CerberusParser::parseMessageSource("file://" . $full_path, true, false)))
				throw new Exception("Failed to parse the message.");
			
			// If successful, delete the referenced file and update marquee
			
			if(is_object($dict) && !empty($dict->id)) {
				C4_AbstractView::setMarquee($view_id, sprintf('<b>Created:</b> <a href="%s">%s</a>',
					$dict->url,
					$dict->_label
				));
				
			} elseif(null === $dict) {
				$log = ob_get_contents();
				
				C4_AbstractView::setMarquee($view_id, sprintf('<b>Rejected:</b> %s',
					$log
				));
			}
			
			// JSON
			
			$json = json_encode(array(
				'status' => true,
			));
			
			ob_end_clean();
			
			echo $json;
			
		} catch (Exception $e) {
			$log = ob_get_contents();
			
			$json = json_encode(array(
				'status' => false,
				'message' => $e->getMessage(),
				'log' => $log,
			));
			
			ob_end_clean();
			
			echo $json;
		}
		
		$logger->setLogLevel(0);
	}
	
	function deleteMessageJsonAction() {
		header("Content-Type: application/json");
		
		@$file = basename(DevblocksPlatform::importGPC($_POST['file'],'string',''));
		@$view_id = basename(DevblocksPlatform::importGPC($_POST['view_id'],'string',''));
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(403);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(403);
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			// Resolve any symbolic links
			
			if(false == ($full_path = realpath(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $file)))
				throw new Exception("Invalid message file.");
	
			// Make sure our requested file is in the same directory
			
			if(false == (dirname(APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . 'test.msg') == dirname($full_path)))
				throw new Exception("Invalid message file.");
			
			// If the extension isn't .msg, abort.
			if(false == ($pathinfo = pathinfo($file)) || !isset($pathinfo['extension']) && $pathinfo['extension'] != 'msg')
				throw new Exception("Invalid message file.");
			
			@unlink($full_path);
			
			C4_AbstractView::setMarquee($view_id, sprintf('<b>Deleted file:</b> %s',
				$file
			));
			
			echo json_encode(array(
				'status' => true,
			));
			
		} catch(Exception $e) {
			echo json_encode(array(
				'status' => false,
				'message' => $e->getMessage(),
			));
			
		}
	}
	
	function renderTabMailRelayAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		$replyto_default = DAO_Address::getDefaultLocalAddress();
		$tpl->assign('replyto_default', $replyto_default);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_incoming/tabs/mail_relay.tpl');
	}
	
	function saveMailRelayJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			@$relay_disable = DevblocksPlatform::importGPC($_POST['relay_disable'],'integer',0);
			@$relay_disable_auth = DevblocksPlatform::importGPC($_POST['relay_disable_auth'],'integer',0);
			@$relay_spoof_from = DevblocksPlatform::importGPC($_POST['relay_spoof_from'],'integer',0);
			
			// Save
			
			$settings = DevblocksPlatform::services()->pluginSettings();
			$settings->set('cerberusweb.core',CerberusSettings::RELAY_DISABLE, $relay_disable);
			$settings->set('cerberusweb.core',CerberusSettings::RELAY_DISABLE_AUTH, $relay_disable_auth);
			$settings->set('cerberusweb.core',CerberusSettings::RELAY_SPOOF_FROM, $relay_spoof_from);
			
			echo json_encode([
				'status'=>true,
				'message' => DevblocksPlatform::translate('success.saved_changes'),
			]);
			return;
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage()
			]);
			return;
			
		}
	}
}