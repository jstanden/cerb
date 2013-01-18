<?php
class UmScTicketController extends Extension_UmScController {
	
	function renderSidebar(DevblocksHttpResponse $response) {
//		$tpl = DevblocksPlatform::getTemplateService();
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		
//		$umsession = ChPortalHelper::getSession();
//		$active_contact = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // ticket
		$mask = array_shift($stack);
		
		if(empty($mask)) { //TODO
			// Ticket show
			exit('No Ticket ID in URL');
		} else {
			// Insecure retrieval (get ticket just by mask)
			list($tickets) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask)
				),
				1,
				0,
				null,
				null,
				false
			);
			$ticket = array_shift($tickets);
			
			// Security check (mask compare)
			if(0 == strcasecmp($ticket[SearchFields_Ticket::TICKET_MASK],$mask)) {
				$requesters = DAO_Ticket::getRequestersByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
				$messages = array_reverse(DAO_Message::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]), true);
				$attachments = array();
				$active_contact = DAO_ContactPerson::get(@array_shift($requesters)->contact_person_id);
				
				// Attachments
				if(is_array($messages) && !empty($messages)) {
					// Populate attachments per message
					foreach($messages as $message_id => $message) {
						$map = $message->getLinksAndAttachments();
						
						if(!isset($map['links']) || empty($map['links']) 
							|| !isset($map['attachments']) || empty($map['attachments']))
							continue;
						
						foreach($map['links'] as $link_id => $link) {
							$file = $map['attachments'][$link->attachment_id];
							
							if(empty($file)) {
								unset($map['links'][$link_id]);
								continue;
							}
								
							if(0 == strcasecmp('original_message.html', $file->display_name)) {
								unset($map['links'][$link_id]);
								unset($map['files'][$link->attachment_id]);
								continue;
							}
						}
						
						if(!empty($map)) {
							if(!isset($attachments[$message_id]))
								$attachments[$message_id] = array();
							
							$attachments[$message_id][$link->guid] = $map;
						}
					}
				}
				// Show fields
				if(null != ($show_fields = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'ticket_view.fields', null))) {
					$tpl->assign('show_fields', @json_decode($show_fields, true));
				}
				
				$tpl->assign('active_contact', $active_contact);
				$tpl->assign('ticket', $ticket);
				$tpl->assign('messages', $messages);
				$tpl->assign('attachments', $attachments);
				
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/ticket/display.tpl");
			}
		}
				
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(null != ($show_fields = DAO_CommunityToolProperty::get($instance->code, 'ticket_view.fields', null))) {
			$tpl->assign('show_fields', @json_decode($show_fields, true));
		}
		if(null != ($ticket_view_url = DAO_CommunityToolProperty::get($instance->code, 'ticket_view.ticket_view_url', ''))) {
			$tpl->assign('ticket_view_url', $ticket_view_url);
		}
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/config/module/ticket.tpl');
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$aFields			= DevblocksPlatform::importGPC($_POST['fields'], 'array', array());
		@$aFieldsEditable	= DevblocksPlatform::importGPC($_POST['fields_editable'], 'array', array());		
		@$aTicketViewUrl	= DevblocksPlatform::importGPC($_POST['ticket_view_url'], 'string', '');

		$fields = array();
		
		if(is_array($aFields))
		foreach($aFields as $idx => $field) {
			$mode = $aFieldsEditable[$idx];
			if(!is_null($mode))
				$fields[$field] = intval($mode);
		}
		
		DAO_CommunityToolProperty::set($instance->code, 'ticket_view.fields', json_encode($fields));
		DAO_CommunityToolProperty::set($instance->code, 'ticket_view.ticket_view_url', $aTicketViewUrl);
	}
	
	function saveTicketPropertiesAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer','0');
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);

		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(empty($shared_address_ids))
			$shared_address_ids = array(-1);
		
		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',$shared_address_ids),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		$ticket_id = $ticket[SearchFields_Ticket::TICKET_ID];

		$fields = array(
			DAO_Ticket::IS_CLOSED => ($closed) ? 1 : 0
		);
		DAO_Ticket::update($ticket_id,$fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));		
	}
	
	function doReplyAction() {
		@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);

		// Load contact addresses
		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(empty($shared_address_ids))
			$shared_address_ids = array(-1);
		
		// Validate FROM address
		if(null == ($from_address = DAO_Address::lookupAddress($from, false)) 
			|| $from_address->contact_person_id != $active_contact->id)
			return FALSE;
			
		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',$shared_address_ids),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		
		$messages = DAO_Message::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
		$last_message = array_pop($messages); /* @var $last_message Model_Message */
		$last_message_headers = $last_message->getHeaders();
		unset($messages);

		// Ticket group settings
		$group = DAO_Group::get($ticket[SearchFields_Ticket::TICKET_GROUP_ID]);
		@$group_replyto = $group->getReplyTo($ticket[SearchFields_Ticket::TICKET_BUCKET_ID]);
		
		// Headers
		$message = new CerberusParserMessage();
		$message->headers['from'] = $from_address->email;
		$message->headers['to'] = $group_replyto->email;
		$message->headers['date'] = date('r');
		$message->headers['subject'] = 'Re: ' . $ticket[SearchFields_Ticket::TICKET_SUBJECT];
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		$message->headers['in-reply-to'] = @$last_message_headers['message-id'];
		
		$message->body = sprintf(
			"%s",
			$content
		);
   
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
		
		CerberusParser::parseMessage($message,array('no_autoreply'=>true));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));
	}
};
?>