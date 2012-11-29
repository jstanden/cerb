<?php
class UmScContactController extends Extension_UmScController {
	const PARAM_CAPTCHA_ENABLED = 'contact.captcha_enabled';
	const PARAM_ALLOW_SUBJECTS = 'contact.allow_subjects';
	const PARAM_ATTACHMENTS_MODE = 'contact.attachments_mode';
	const PARAM_SITUATIONS = 'contact.situations';
	
	function isVisible() {
		return true;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();

		$umsession = ChPortalHelper::getSession();
		
		$stack = $response->path;
		array_shift($stack); // contact
		$section = array_shift($stack);
		
		$captcha_enabled = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

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
				$sFrom = $umsession->getProperty('support.write.last_from','');
				$sSubject = $umsession->getProperty('support.write.last_subject','');
				$sNature = $umsession->getProperty('support.write.last_nature','');
				$sContent = $umsession->getProperty('support.write.last_content','');
				$aLastFollowupA = $umsession->getProperty('support.write.last_followup_a','');
				$sError = $umsession->getProperty('support.write.last_error','');
				
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
						
						$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/contact/step2.tpl");
						break;
				}
				break;
			}
			
	}

	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		$captcha_enabled = DAO_CommunityToolProperty::get($instance->code, self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

		$allow_subjects = DAO_CommunityToolProperty::get($instance->code, self::PARAM_ALLOW_SUBJECTS, 0);
		$tpl->assign('allow_subjects', $allow_subjects);

		$attachments_mode = DAO_CommunityToolProperty::get($instance->code, self::PARAM_ATTACHMENTS_MODE, 0);
		$tpl->assign('attachments_mode', $attachments_mode);

		$sDispatch = DAO_CommunityToolProperty::get($instance->code,self::PARAM_SITUATIONS, '');
		$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		$tpl->assign('dispatch', $dispatch);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Default reply-to
		$replyto_default = DAO_AddressOutgoing::getDefault();
		$tpl->assign('replyto_default', $replyto_default);
		
		// Contact: Fields
		$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('ticket_fields', $ticket_fields);
		
		// Custom field types
		$types = Model_CustomField::getTypes();
		$tpl->assign('field_types', $types);
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/config/module/contact.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$iCaptcha = DevblocksPlatform::importGPC($_POST['captcha_enabled'],'integer',1);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_CAPTCHA_ENABLED, $iCaptcha);

		@$iAllowSubjects = DevblocksPlatform::importGPC($_POST['allow_subjects'],'integer',0);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_ALLOW_SUBJECTS, $iAllowSubjects);

		@$iAttachmentsMode = DevblocksPlatform::importGPC($_POST['attachments_mode'],'integer',0);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_ATTACHMENTS_MODE, $iAttachmentsMode);

		// Contact Form
		$replyto_default = DAO_AddressOutgoing::getDefault();
		
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
	
	function doContactStep2Action() {
		$umsession = ChPortalHelper::getSession();
		$fingerprint = ChPortalHelper::getFingerprint();

		@$sNature = DevblocksPlatform::importGPC($_POST['nature'],'string','');

		$umsession->setProperty('support.write.last_nature', $sNature);
		$umsession->setProperty('support.write.last_subject', null);
		$umsession->setProperty('support.write.last_content', null);
		$umsession->setProperty('support.write.last_error', null);
		$umsession->setProperty('support.write.last_followup_a', null);
		
		$sDispatch = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
		$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		
		// Check if this nature has followups, if not skip to send
		$followups = array();
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
	
	function doContactSendAction() {
		@$sFrom = DevblocksPlatform::importGPC($_POST['from'],'string','');
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
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		$fingerprint = ChPortalHelper::getFingerprint();

		$umsession->setProperty('support.write.last_from',$sFrom);
		$umsession->setProperty('support.write.last_subject',$sSubject);
		$umsession->setProperty('support.write.last_content',$sContent);
		$umsession->setProperty('support.write.last_followup_a',$aFollowUpA);
		
		$sNature = $umsession->getProperty('support.write.last_nature', '');
		
		$captcha_enabled = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, 1);
		$captcha_session = $umsession->getProperty(UmScApp::SESSION_CAPTCHA,'***');
		
		// Subject is required if the field  is on the form
		if(isset($_POST['subject']) && empty($sSubject)) {
			$umsession->setProperty('support.write.last_error','A subject is required.');
			
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','step2')));
			return;
		}
		
		// Sender and CAPTCHA required
		if(empty($sFrom) || ($captcha_enabled && 0 != strcasecmp($sCaptcha, $captcha_session))) {
			
			if(empty($sFrom)) {
				$umsession->setProperty('support.write.last_error','Invalid e-mail address.');
			} else {
				$umsession->setProperty('support.write.last_error','What you typed did not match the image.');
			}
			
			// Need to report the captcha didn't match and redraw the form
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','step2')));
			return;
		}

		// Dispatch
		$replyto_default = DAO_AddressOutgoing::getDefault();
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
				$fieldContent .= "Q) " . $q . "\r\n" . "A) " . $answer . "\r\n";
				if($idx+1 < count($aFollowUpQ)) $fieldContent .= "\r\n";
			}
			$fieldContent .= "--------------------------------------------\r\n";
			"\r\n";
		}
		
		$community_portal = DAO_CommunityTool::getByCode(ChPortalHelper::getCode());
		
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $to;
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		$message->headers['x-cerberus-portal'] = !empty($community_portal->name) ? $community_portal->name : $community_portal->code;
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist($sFrom,'');
		if(empty($fromList) || !is_array($fromList)) {
			return; // abort with message
		}
		$from = array_shift($fromList);
		$message->headers['from'] = $from->mailbox . '@' . $from->host; 

		$message->body = 'IP: ' . $fingerprint['ip'] . "\r\n\r\n" . $sContent . $fieldContent;

		// Attachments
		$attachments_mode = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_ATTACHMENTS_MODE, 0);

		if(0==$attachments_mode || (1==$attachments_mode && !empty($active_contact)))
		if(is_array($_FILES) && !empty($_FILES))
		foreach($_FILES as $name => $files) {
			// field[]
			if(is_array($files['name'])) {
				foreach($files['name'] as $idx => $name) {
					$attach = new ParserFile();
					$attach->setTempFile($files['tmp_name'][$idx],'application/octet-stream');
					$attach->file_size = filesize($files['tmp_name'][$idx]);
					$message->files[$name] = $attach;
				}
				
			} else {
				$attach = new ParserFile();
				$attach->setTempFile($files['tmp_name'],'application/octet-stream');
				$attach->file_size = filesize($files['tmp_name']);
				$message->files[$files['name']] = $attach;
			}
		}	
		
		// Custom Fields
		
		if(!empty($aFieldIds))
		foreach($aFieldIds as $iIdx => $iFieldId) {
			if(!empty($iFieldId)) {
				$field =& $fields[$iFieldId]; /* @var $field Model_CustomField */
				$value = "";
				
				switch($field->type) {
					case Model_CustomField::TYPE_SINGLE_LINE:
					case Model_CustomField::TYPE_MULTI_LINE:
					case Model_CustomField::TYPE_URL:
						@$value = trim($aFollowUpA[$iIdx]);
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
						
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'array',array());
						break;
						
					case Model_CustomField::TYPE_WORKER:
						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'integer',0);
						break;
				}
				
				if((is_array($value) && !empty($value)) 
					|| (!is_array($value) && 0 != strlen($value)))
						$message->custom_fields[$iFieldId] = $value;
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
		
		// Clear any errors
		$umsession->setProperty('support.write.last_nature',null);
		$umsession->setProperty('support.write.last_nature_string',null);
		$umsession->setProperty('support.write.last_content',null);
		$umsession->setProperty('support.write.last_error',null);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'contact','confirm')));
	}	
	
};