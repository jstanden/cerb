<?php
class UmScContactController extends Extension_UmScController {
	const PARAM_REQUIRE_LOGIN = 'contact.require_login';
	const PARAM_CAPTCHA_ENABLED = 'contact.captcha_enabled';
	const PARAM_ALLOW_SUBJECTS = 'contact.allow_subjects';
	const PARAM_SITUATIONS = 'contact.situations';
	
	function isVisible() {
		$require_login = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_REQUIRE_LOGIN, 0);
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		if($require_login && empty($active_user))
			return false;
		
		return true;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // contact
    	$section = array_shift($stack);
    	
        $captcha_enabled = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

        $allow_subjects = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_ALLOW_SUBJECTS, 0);
		$tpl->assign('allow_subjects', $allow_subjects);
		
    	$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		$tpl->assign('default_from', $default_from);
    	
    	switch($section) {
    		case 'confirm':
    			$tpl->assign('last_opened',$umsession->getProperty('support.write.last_opened',''));
    			$tpl->display("file:${tpl_path}portal/sc/module/contact/confirm.tpl");
    			break;
    		
    		default:
    		case 'step1':
    			$umsession->setProperty('support.write.last_error', null);
    			
    		case 'step2':
    			$sFrom = $umsession->getProperty('support.write.last_from','');
    			$sSubject = $umsession->getProperty('support.write.last_subject','');
    			$sNature = $umsession->getProperty('support.write.last_nature','');
    			$sContent = $umsession->getProperty('support.write.last_content','');
//		    			$aLastFollowupQ = $umsession->getProperty('support.write.last_followup_q','');
    			$aLastFollowupA = $umsession->getProperty('support.write.last_followup_a','');
    			$sError = $umsession->getProperty('support.write.last_error','');
    			
				$tpl->assign('last_from', $sFrom);
				$tpl->assign('last_subject', $sSubject);
				$tpl->assign('last_nature', $sNature);
				$tpl->assign('last_content', $sContent);
//						$tpl->assign('last_followup_q', $aLastFollowupQ);
				$tpl->assign('last_followup_a', $aLastFollowupA);
				$tpl->assign('last_error', $sError);
				
   				$sDispatch = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
//		    			$dispatch = !empty($sDispatch) ? (is_array($sDispatch) ? unserialize($sDispatch): array($sDispatch)) : array();
    			$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		        $tpl->assign('dispatch', $dispatch);
		        
		        switch($section) {
		        	default:
		        		// If there's only one situation, skip to step2
				        if(1==count($dispatch)) {
				        	@$sNature = md5(key($dispatch));
				        	$umsession->setProperty('support.write.last_nature', $sNature);
				        	reset($dispatch);
				        } else {
		        			$tpl->display("file:${tpl_path}portal/sc/module/contact/step1.tpl");
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
				        
				        $ticket_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.ticket');
						$tpl->assign('ticket_fields', $ticket_fields);
				        
		        		$tpl->display("file:${tpl_path}portal/sc/module/contact/step2.tpl");
		        		break;
		        }
		        break;
    		}
    		
	}

    // Ajax
    public function getSituation() {
		@$sCode = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		@$sReason = DevblocksPlatform::importGPC($_REQUEST['reason'],'string','');
    	 
    	$tool = DAO_CommunityTool::getByCode($sCode);
    	 
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

        $settings = CerberusSettings::getInstance();
        
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        $tpl->assign('default_from', $default_from);
        
        $sDispatch = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
        
        if(is_array($dispatch))
        foreach($dispatch as $reason => $params) {
        	if(md5($reason)==$sReason) {
        		$tpl->assign('situation_reason', $reason);
        		$tpl->assign('situation_params', $params);
        		break;
        	}
        }
        
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
        
		// Contact: Fields
		$ticket_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.ticket');
		$tpl->assign('ticket_fields', $ticket_fields);
        
        $tpl->display("file:${tpl_path}portal/sc/config/module/contact/add_situation.tpl");
		exit;
    }

	function configure() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

        $settings = CerberusSettings::getInstance();
        
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        $tpl->assign('default_from', $default_from);

        $captcha_enabled = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

        $allow_subjects = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_ALLOW_SUBJECTS, 0);
		$tpl->assign('allow_subjects', $allow_subjects);

        $sDispatch = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
        $tpl->assign('dispatch', $dispatch);
		
		$require_login = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_REQUIRE_LOGIN, 0);
		$tpl->assign('contact_require_login', $require_login);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Contact: Fields
		$ticket_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.ticket');
		$tpl->assign('ticket_fields', $ticket_fields);
		
		$tpl->display("file:${tpl_path}portal/sc/config/module/contact.tpl");
	}
	
	function saveConfiguration() {
        @$iRequireLogin = DevblocksPlatform::importGPC($_POST['contact_require_login'],'integer',0);
		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_REQUIRE_LOGIN, $iRequireLogin);

        @$iCaptcha = DevblocksPlatform::importGPC($_POST['captcha_enabled'],'integer',1);
        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, $iCaptcha);

        @$iAllowSubjects = DevblocksPlatform::importGPC($_POST['allow_subjects'],'integer',0);
        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_ALLOW_SUBJECTS, $iAllowSubjects);

		// Contact Form
        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        
    	@$arDeleteSituations = DevblocksPlatform::importGPC($_POST['delete_situations'],'array',array());
        
    	@$sEditReason = DevblocksPlatform::importGPC($_POST['edit_reason'],'string','');
    	@$sReason = DevblocksPlatform::importGPC($_POST['reason'],'string','');
        @$sTo = DevblocksPlatform::importGPC($_POST['to'],'string','');
        @$aFollowup = DevblocksPlatform::importGPC($_POST['followup'],'array',array());
        @$aFollowupField = DevblocksPlatform::importGPC($_POST['followup_fields'],'array',array());
        
        if(empty($sTo))
        	$sTo = $default_from;
        
        $sDispatch = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();

        // [JAS]: [TODO] Only needed temporarily to clean up imports
		// [TODO] Move to patch
        if(is_array($dispatch))
        foreach($dispatch as $d_reason => $d_params) {
        	if(!is_array($d_params)) {
        		$dispatch[$d_reason] = array('to'=>$d_params,'followups'=>array());
        	} else {
        		unset($d_params['']);
        	}
        }

        // Nuke a record we're replacing or any checked boxes
		// will be MD5
        foreach($dispatch as $d_reason => $d_params) {
        	if(!empty($sEditReason) && md5($d_reason)==$sEditReason) {
        		unset($dispatch[$d_reason]);
        	} elseif(!empty($arDeleteSituations) && false !== array_search(md5($d_reason),$arDeleteSituations)) {
        		unset($dispatch[$d_reason]);
        	}
        }
        
       	// If we have new data, add it
        if(!empty($sReason) && !empty($sTo) && false === array_search(md5($sReason),$arDeleteSituations)) {
			$dispatch[$sReason] = array(
				'to' => $sTo,
				'followups' => array()
			);
			
			$followups =& $dispatch[$sReason]['followups'];
			
			if(!empty($aFollowup))
			foreach($aFollowup as $idx => $followup) {
				if(empty($followup)) continue;
//				$followups[$followup] = (false !== array_search($idx,$aFollowupLong)) ? 1 : 0;
				$followups[$followup] = @$aFollowupField[$idx];
			}
        }
        
        ksort($dispatch);
        
		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_SITUATIONS, serialize($dispatch));
	}
	
	function doContactStep2Action() {
		$umsession = UmPortalHelper::getSession();
		$fingerprint = UmPortalHelper::getFingerprint();

		@$sNature = DevblocksPlatform::importGPC($_POST['nature'],'string','');

		$umsession->setProperty('support.write.last_nature', $sNature);
		$umsession->setProperty('support.write.last_subject', null);
		$umsession->setProperty('support.write.last_content', null);
		$umsession->setProperty('support.write.last_error', null);
		$umsession->setProperty('support.write.last_followup_a', null);
		
		$sDispatch = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
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
        
        DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'contact','step2')));
	}
	
	function doContactSendAction() {
		@$sFrom = DevblocksPlatform::importGPC($_POST['from'],'string','');
		@$sSubject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
		@$sContent = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$sCaptcha = DevblocksPlatform::importGPC($_POST['captcha'],'string','');
		
		@$aFieldIds = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());
		@$aFollowUpQ = DevblocksPlatform::importGPC($_POST['followup_q'],'array',array());
		
		// Load the answers to any situational questions
		$aFollowUpA = array();
		if(is_array($aFollowUpQ))
		foreach($aFollowUpQ as $idx => $q) {
			@$answer = DevblocksPlatform::importGPC($_POST['followup_a_'.$idx],'string','');
			$aFollowUpA[$idx] = $answer;
		}
		
		$umsession = UmPortalHelper::getSession();
		$fingerprint = UmPortalHelper::getFingerprint();

        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);

		$umsession->setProperty('support.write.last_from',$sFrom);
		$umsession->setProperty('support.write.last_subject',$sSubject);
		$umsession->setProperty('support.write.last_content',$sContent);
//		$umsession->setProperty('support.write.last_followup_q',$aFollowUpQ);
		$umsession->setProperty('support.write.last_followup_a',$aFollowUpA);
        
		$sNature = $umsession->getProperty('support.write.last_nature', '');
		
        $captcha_enabled = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_CAPTCHA_ENABLED, 1);
		$captcha_session = $umsession->getProperty(UmScApp::SESSION_CAPTCHA,'***');
		
		// Subject is required if the field  is on the form
		if(isset($_POST['subject']) && empty($sSubject)) {
			$umsession->setProperty('support.write.last_error','A subject is required.');
			
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'contact','step2')));
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
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'contact','step2')));
			return;
		}

		// Dispatch
		$to = $default_from;
		$subject = 'Contact me: Other';
		
        $sDispatch = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_SITUATIONS, '');
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
		
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $to;
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		$message->headers['x-cerberus-portal'] = 1; 
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist($sFrom,'');
		if(empty($fromList) || !is_array($fromList)) {
			return; // abort with message
		}
		$from = array_shift($fromList);
		$message->headers['from'] = $from->mailbox . '@' . $from->host; 

		$message->body = 'IP: ' . $fingerprint['ip'] . "\r\n\r\n" . $sContent . $fieldContent;

		// Attachments
		
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
		
		$ticket_id = CerberusParser::parseMessage($message);
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		// Auto-save any custom fields
		$fields = DAO_CustomField::getBySource('cerberusweb.fields.source.ticket');
		if(!empty($aFieldIds))
		foreach($aFieldIds as $iIdx => $iFieldId) {
			if(!empty($iFieldId)) {
				$field =& $fields[$iFieldId]; /* @var $field Model_CustomField */
				$value = "";
				
				switch($field->type) {
					case Model_CustomField::TYPE_SINGLE_LINE:
					case Model_CustomField::TYPE_MULTI_LINE:
						@$value = trim($aFollowUpA[$iIdx]);
						break;
					
					case Model_CustomField::TYPE_NUMBER:
						@$value = intval($aFollowUpA[$iIdx]);
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
				}
				
				if(!empty($value))
					DAO_CustomFieldValue::setFieldValue('cerberusweb.fields.source.ticket',$ticket_id,$iFieldId,$value);
			}
		}
		
		// Clear any errors
		$umsession->setProperty('support.write.last_nature',null);
		$umsession->setProperty('support.write.last_nature_string',null);
		$umsession->setProperty('support.write.last_content',null);
		$umsession->setProperty('support.write.last_error',null);
		$umsession->setProperty('support.write.last_opened',$ticket->mask);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'contact','confirm')));
	}	
	
};