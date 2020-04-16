<?php /** @noinspection PhpUnused */

class UmScContactController extends Extension_UmScController {
	const PARAM_CAPTCHA_ENABLED = 'contact.captcha_enabled';
	const PARAM_ALLOW_CC = 'contact.allow_cc';
	const PARAM_ALLOW_SUBJECTS = 'contact.allow_subjects';
	const PARAM_ATTACHMENTS_MODE = 'contact.attachments_mode';
	const PARAM_SITUATIONS = 'contact.situations';
	
	function isVisible() {
		return true;
	}
	
	public function invoke(string $action, DevblocksHttpRequest $request=null) {
		switch($action) {
			case 'doContactSend':
				return $this->_portalAction_doContactSend();
			case 'doContactStep2':
				return $this->_portalAction_doContactStep2();
		}
		return false;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();

		$umsession = ChPortalHelper::getSession();
		
		$stack = $response->path;
		array_shift($stack); // contact
		$section = array_shift($stack);
		
		$captcha_enabled = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

		$allow_cc = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_ALLOW_CC, 0);
		$tpl->assign('allow_cc', $allow_cc);
		
		$allow_subjects = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_ALLOW_SUBJECTS, 0);
		$tpl->assign('allow_subjects', $allow_subjects);

		$attachments_mode = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_ATTACHMENTS_MODE, 0);
		$tpl->assign('attachments_mode', $attachments_mode);
		
		switch($section) {
			case 'confirm':
				$tpl->assign('last_opened',$umsession->getProperty('support.write.last_opened',''));
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/contact/confirm.tpl");
				break;
			
			default:
			case 'step1':
				$umsession->setProperty('support.write.last_error', null);
				
			case 'step2':
				$sCc = $umsession->getProperty('support.write.last_cc','');
				$sFrom = $umsession->getProperty('support.write.last_from','');
				$sSubject = $umsession->getProperty('support.write.last_subject','');
				$sNature = $umsession->getProperty('support.write.last_nature','');
				$sContent = $umsession->getProperty('support.write.last_content','');
				$aLastFollowupA = $umsession->getProperty('support.write.last_followup_a','');
				$sError = $umsession->getProperty('support.write.last_error','');
				
				$tpl->assign('last_cc', $sCc);
				$tpl->assign('last_from', $sFrom);
				$tpl->assign('last_subject', $sSubject);
				$tpl->assign('last_nature', $sNature);
				$tpl->assign('last_content', $sContent);
				$tpl->assign('last_followup_a', $aLastFollowupA);
				$tpl->assign('last_error', $sError);
				
				$sDispatch = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
				$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
				
				// Remove hidden contact situations
				if(is_array($dispatch))
				foreach($dispatch as $k => $params) {
					if(isset($params['is_hidden']) && !empty($params['is_hidden']))
						unset($dispatch[$k]);
				}
				
				$tpl->assign('dispatch', $dispatch);
				
				switch($section) {
					default:
						// If there's only one situation, skip to step2
						if(1==count($dispatch)) {
							@$sNature = md5(key($dispatch));
							$umsession->setProperty('support.write.last_nature', $sNature);
							reset($dispatch);
						} else {
							$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/contact/step1.tpl");
							break;
						}
						
					case 'step2':
						// Cache along with answers?
						if(is_array($dispatch))
						foreach($dispatch as $k => $v) {
							if(md5($k)==$sNature) {
								$umsession->setProperty('support.write.last_nature_string', $k);
								$tpl->assign('situation', $k);
								$tpl->assign('situation_params', $v);
								break;
							}
						}
						
						$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
						$tpl->assign('ticket_fields', $ticket_fields);
						
						$workers = DAO_Worker::getAllActive();
						$tpl->assign('workers', $workers);
						
						$currencies = DAO_Currency::getAll();
						$tpl->assign('currencies', $currencies);
						
						$tpl->assign('client_ip', DevblocksPlatform::getClientIp());
						
						$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/contact/step2.tpl");
						break;
				}
				break;
			}
			
	}

	function configure(Model_CommunityTool $portal) {
		@$tab_action = DevblocksPlatform::importGPC($_POST['tab_action'], 'string', '');
		
		switch($tab_action) {
			case 'addContactSituation':
				$tpl = DevblocksPlatform::services()->template();
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				// Contact: Fields
				$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, true, true);
				$tpl->assign('ticket_fields', $ticket_fields);
				
				// Custom field types
				$types = Model_CustomField::getTypes();
				$tpl->assign('field_types', $types);
				
				// Default reply-to
				$replyto_default = DAO_Address::getDefaultLocalAddress();
				$tpl->assign('replyto_default', $replyto_default);
				
				$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/contact/situation.tpl');
				break;
				
			default:
				$tpl = DevblocksPlatform::services()->template();
				$tpl->assign('portal', $portal);
		
				$captcha_enabled = DAO_CommunityToolProperty::get($portal->code, self::PARAM_CAPTCHA_ENABLED, 1);
				$tpl->assign('captcha_enabled', $captcha_enabled);
		
				$allow_cc = DAO_CommunityToolProperty::get($portal->code, self::PARAM_ALLOW_CC, 0);
				$tpl->assign('allow_cc', $allow_cc);
		
				$allow_subjects = DAO_CommunityToolProperty::get($portal->code, self::PARAM_ALLOW_SUBJECTS, 0);
				$tpl->assign('allow_subjects', $allow_subjects);
		
				$attachments_mode = DAO_CommunityToolProperty::get($portal->code, self::PARAM_ATTACHMENTS_MODE, 0);
				$tpl->assign('attachments_mode', $attachments_mode);
		
				$sDispatch = DAO_CommunityToolProperty::get($portal->code,self::PARAM_SITUATIONS, '');
				$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
				$tpl->assign('dispatch', $dispatch);
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				// Default reply-to
				$replyto_default = DAO_Address::getDefaultLocalAddress();
				$tpl->assign('replyto_default', $replyto_default);
				
				// Contact: Fields
				$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, true, true);
				$tpl->assign('ticket_fields', $ticket_fields);
				
				// Custom field types
				$types = Model_CustomField::getTypes();
				$tpl->assign('field_types', $types);
				
				$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/contact.tpl");
				break;
		}
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$iCaptcha = DevblocksPlatform::importGPC($_POST['captcha_enabled'],'integer',1);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_CAPTCHA_ENABLED, $iCaptcha);

		@$iAllowCc = DevblocksPlatform::importGPC($_POST['allow_cc'],'integer',0);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_ALLOW_CC, $iAllowCc);
		
		@$iAllowSubjects = DevblocksPlatform::importGPC($_POST['allow_subjects'],'integer',0);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_ALLOW_SUBJECTS, $iAllowSubjects);

		@$iAttachmentsMode = DevblocksPlatform::importGPC($_POST['attachments_mode'],'integer',0);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_ATTACHMENTS_MODE, $iAttachmentsMode);

		// Contact Form
		$replyto_default = DAO_Address::getDefaultLocalAddress();
		
		// Situations
		@$aReason = DevblocksPlatform::importGPC($_POST['contact_reason'],'array',array());
		@$aTo = DevblocksPlatform::importGPC($_POST['contact_to'],'array',array());
		@$aStatus = DevblocksPlatform::importGPC($_POST['status'],'array',array());
		@$aFollowup = DevblocksPlatform::importGPC($_POST['contact_followup'],'array',array());
		@$aFollowupField = DevblocksPlatform::importGPC($_POST['contact_followup_fields'],'array',array());
		
		$dispatch = array();
			
		foreach($aReason as $key => $reason) {
			if(empty($reason))
				continue;
			
			@$to = $aTo[$key];
			@$followups = $aFollowup[$key];
			@$followup_fields = $aFollowupField[$key];
			@$status = $aStatus[$key];

			if('deleted' == $status)
				continue;
			
			$part = array(
				'to' => !empty($to) ? $to : $replyto_default->email,
				'is_hidden' => ('hidden' == $status) ? true : false,
				'followups' => array()
			);

			// Process followups
			if(is_array($followups))
			foreach($followups as $followup_idx => $followup) {
				if(empty($followup))
					continue; // skip blanks
					
				$part['followups'][$followup] =
					(is_array($followup_fields) && isset($followup_fields[$followup_idx]))
					? $followup_fields[$followup_idx]
					: array()
					;
			}
			
			$dispatch[$reason] = $part;
		}

		DAO_CommunityToolProperty::set($instance->code, self::PARAM_SITUATIONS, serialize($dispatch));
	}
	
	private function _portalAction_doContactStep2() {
		$umsession = ChPortalHelper::getSession();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$sNature = DevblocksPlatform::importGPC($_POST['nature'],'string','');

		$umsession->setProperty('support.write.last_nature', $sNature);
		$umsession->setProperty('support.write.last_subject', null);
		$umsession->setProperty('support.write.last_content', null);
		$umsession->setProperty('support.write.last_error', null);
		$umsession->setProperty('support.write.last_followup_a', null);
		
		$sDispatch = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
		$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		
		// Check if this nature has followups, if not skip to send
		if(is_array($dispatch))
		foreach($dispatch as $k => $v) {
			if(md5($k)==$sNature) {
				$umsession->setProperty('support.write.last_nature_string', $k);
				@$followups = $v['followups'];
				break;
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','step2')));
	}
	
	private function _portalAction_doContactSend() {
		$umsession = ChPortalHelper::getSession();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$sFrom = DevblocksPlatform::importGPC($_POST['from'],'string','');
		@$sCc = DevblocksPlatform::importGPC($_POST['cc'],'string','');
		@$sSubject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
		@$sContent = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$sCaptcha = DevblocksPlatform::importGPC($_POST['captcha'],'string','');
		
		@$aFieldIds = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());
		@$aFollowUpQ = DevblocksPlatform::importGPC($_POST['followup_q'],'array',array());
		
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		
		// Load the answers to any situational questions
		$aFollowUpA = array();
		if(is_array($aFollowUpQ))
		foreach($aFollowUpQ as $idx => $q) {
			// Only form values we were passed
			if(!isset($_POST['followup_a_'.$idx]))
				continue;
				
			if(is_array($_POST['followup_a_'.$idx])) {
				@$answer = DevblocksPlatform::importGPC($_POST['followup_a_'.$idx],'array',array());
				$aFollowUpA[$idx] = implode(', ', $answer);
			} else {
				@$answer = DevblocksPlatform::importGPC($_POST['followup_a_'.$idx],'string','');
				$aFollowUpA[$idx] = $answer;
			}
			
			// Translate field values into something human-readable (if needed)
			if(isset($aFieldIds[$idx]) && !empty($aFieldIds[$idx])) {
				
				// Were we given a legit field id?
				if(null != (@$field = $fields[$aFieldIds[$idx]])) {
					
					switch($field->type) {
						
						// Translate 'worker' fields into worker name (not ID)
						case Model_CustomField::TYPE_WORKER:
							if(null != ($worker = DAO_Worker::get($answer))) {
								$aFollowUpA[$idx] = $worker->getName();
							}
							break;
							
					} // switch
					
				} // if
				
			} // if
			
		}
		
		$active_contact = $umsession->getProperty('sc_login', null);

		$umsession->setProperty('support.write.last_cc',$sCc);
		$umsession->setProperty('support.write.last_from',$sFrom);
		$umsession->setProperty('support.write.last_subject',$sSubject);
		$umsession->setProperty('support.write.last_content',$sContent);
		$umsession->setProperty('support.write.last_followup_a',$aFollowUpA);
		
		$sNature = $umsession->getProperty('support.write.last_nature', '');
		
		$captcha_enabled = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, 1);
		$captcha_session = $umsession->getProperty(UmScApp::SESSION_CAPTCHA,'***');
		
		// Subject is required if the field is on the form
		if(isset($_POST['subject']) && empty($sSubject)) {
			$umsession->setProperty('support.write.last_error','A subject is required.');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','step2')));
			return;
		}
		
		// Message is required
		if(empty($sContent)) {
			$umsession->setProperty('support.write.last_error','The message content is required.');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','step2')));
			return;
		}
		
		// Sender and CAPTCHA required
		$captcha_required = (1 == $captcha_enabled || (2 == $captcha_enabled && empty($active_contact)));
		if(empty($sFrom) || ($captcha_required && !empty($captcha_session) && 0 != strcasecmp($sCaptcha, $captcha_session))) {
			
			if(empty($sFrom)) {
				$umsession->setProperty('support.write.last_error','Invalid email address.');
			} else {
				$umsession->setProperty('support.write.last_error','What you typed did not match the image.');
			}
			
			// Need to report the captcha didn't match and redraw the form
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','step2')));
			return;
		}

		// Dispatch
		$replyto_default = DAO_Address::getDefaultLocalAddress();
		$to = $replyto_default->email;
		$subject = 'Contact me: Other';
		
		$sDispatch = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
		$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		
		foreach($dispatch as $k => $v) {
			if(md5($k)==$sNature) {
				$to = $v['to'];
				$subject = 'Contact me: ' . strip_tags($k);
				break;
			}
		}
		
		if(!empty($sSubject))
			$subject = $sSubject;
		
		$fieldContent = '';
		
		if(!empty($aFollowUpQ)) {
			$fieldContent = "\r\n\r\n";
			$fieldContent .= "--------------------------------------------\r\n";
			if(!empty($sNature)) {
				$fieldContent .= $subject . "\r\n";
				$fieldContent .= "--------------------------------------------\r\n";
			}
			foreach($aFollowUpQ as $idx => $q) {
				$answer = isset($aFollowUpA[$idx]) ? $aFollowUpA[$idx] : '';
				
				if('*' == substr($q,0,1) && 0 == strlen($answer)) {
					$umsession->setProperty('support.write.last_error',sprintf("'%s' is required.", ltrim($q,'*')));
					DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','step2')));
					return;
				}
				
				$fieldContent .= "Q) " . $q . "\r\n" . "A) " . $answer . "\r\n";
				if($idx+1 < count($aFollowUpQ)) $fieldContent .= "\r\n";
			}
			$fieldContent .= "--------------------------------------------\r\n";
			$fieldContent .= "\r\n";
		}
		
		$community_portal = DAO_CommunityTool::getByCode(ChPortalHelper::getCode());
		
		$message_headers = array(
			'date' => date('r'),
			'to' => $to,
			'subject' => $subject,
			'message-id' => CerberusApplication::generateMessageId(),
			'x-cerberus-portal' => !empty($community_portal->name) ? $community_portal->name : $community_portal->code,
		);
		
		if(!empty($sCc))
			$message_headers['cc'] = $sCc;
		
		// Sender
		if(false == ($from = CerberusMail::parseRfcAddress($sFrom)))
			return; // abort with message
		
		$message_headers['from'] = $from['email'];

		$message = new CerberusParserMessage();
		
		foreach($message_headers as $h => $v) {
			$message->headers[$h] = $v;
		}
		
		$message->body = 'IP: ' . DevblocksPlatform::getClientIp() . "\r\n\r\n" . $sContent . $fieldContent;

		// Attachments
		$attachments_mode = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_ATTACHMENTS_MODE, 0);

		if(0==$attachments_mode || (1==$attachments_mode && !empty($active_contact)))
		if(is_array($_FILES) && isset($_FILES['attachments']) && !empty($_FILES['attachments'])) {
			$files = $_FILES['attachments'];
			
			foreach($files['name'] as $idx => $name) {
				if(empty($files['tmp_name'][$idx]) || empty($files['name'][$idx]))
					continue;
				
				$mime_type = @$files['type'][$idx] ?: 'application/octet-stream';
				
				$attach = new ParserFile();
				$attach->setTempFile($files['tmp_name'][$idx], $mime_type);
				$attach->file_size = filesize($files['tmp_name'][$idx]);
				$message->files[$name] = $attach;
			}
		}
		
		// Custom Fields
		
		if(!empty($aFieldIds))
		foreach($aFieldIds as $iIdx => $iFieldId) {
			if(!empty($iFieldId)) {
				$field = $fields[$iFieldId]; /* @var $field Model_CustomField */
				$value = "";
				
				switch($field->type) {
					case Model_CustomField::TYPE_SINGLE_LINE:
					case Model_CustomField::TYPE_MULTI_LINE:
					case Model_CustomField::TYPE_URL:
						@$value = trim($aFollowUpA[$iIdx]);
						break;
					
					case Model_CustomField::TYPE_CURRENCY:
						@$value = $aFollowUpA[$iIdx];
						if(!is_numeric($value) || 0 == strlen($value))
							$value = null;
						break;
						
					case Model_CustomField::TYPE_DECIMAL:
						@$value = $aFollowUpA[$iIdx];
						if(!is_numeric($value) || 0 == strlen($value))
							$value = null;
						break;
						
					case Model_CustomField::TYPE_NUMBER:
						@$value = $aFollowUpA[$iIdx];
						if(!is_numeric($value) || 0 == strlen($value))
							$value = null;
						break;
						
					case Model_CustomField::TYPE_DATE:
						if(false !== ($time = strtotime($aFollowUpA[$iIdx])))
							@$value = intval($time);
						break;
						
					case Model_CustomField::TYPE_DROPDOWN:
						@$value = $aFollowUpA[$iIdx];
						break;
						
					case Model_CustomField::TYPE_CHECKBOX:
						@$value = (isset($aFollowUpA[$iIdx]) && !empty($aFollowUpA[$iIdx])) ? 1 : 0;
						break;
						
					case Model_CustomField::TYPE_LIST:
						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'array',array());
						break;
						
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'array',array());
						break;
						
					case Model_CustomField::TYPE_WORKER:
						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'integer',0);
						break;
						
					case Model_CustomField::TYPE_FILE:
						@$file = $_FILES['followup_a_'.$iIdx];
						$file_ids = CerberusApplication::saveHttpUploadedFiles($file);
						
						if(is_array($file_ids) && !empty($file_ids))
							$value = array_shift($file_ids);
						break;
						
					case Model_CustomField::TYPE_FILES:
						@$files = $_FILES['followup_a_'.$iIdx];
						$file_ids = CerberusApplication::saveHttpUploadedFiles($files);
						
						if(is_array($file_ids) && !empty($file_ids))
							$value = $file_ids;
						break;
				}
				
				if((is_array($value) && !empty($value))
					|| (!is_array($value) && 0 != strlen($value)))
						$message->custom_fields[] = array(
							'field_id' => $iFieldId,
							'context' => CerberusContexts::CONTEXT_TICKET,
							'value' => $value,
						);
			}
		}
		
		// Parse
		$ticket_id = CerberusParser::parseMessage($message);
		
		// It's possible for the parser to reject the message using pre-filters
		if(!empty($ticket_id) && null != ($ticket = DAO_Ticket::get($ticket_id))) {
			$umsession->setProperty('support.write.last_opened',$ticket->mask);
		} else {
			$umsession->setProperty('support.write.last_opened',null);
		}
		
		// Include Cc: recipients as participants
		if(!empty($sCc)) {
			if($ccs = DevblocksPlatform::parseCsvString($sCc)) {
				$participants = DAO_Address::lookupAddresses($ccs, true);
				
				if(is_array($ccs) && !empty($ccs))
					DAO_Ticket::addParticipantIds($ticket_id, array_keys($participants));
			}
		}
		
		// Clear any errors
		$umsession->setProperty('support.write.last_nature',null);
		$umsession->setProperty('support.write.last_nature_string',null);
		$umsession->setProperty('support.write.last_content',null);
		$umsession->setProperty('support.write.last_error',null);
		
		// Clear the CAPTCHA (no resubmissions)
		$umsession->setProperty(UmScApp::SESSION_CAPTCHA,null);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','confirm')));
	}
}