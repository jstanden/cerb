<?php
class UmScHistoryController extends Extension_UmScController {
	
	function isVisible() {
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		return !empty($active_user);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // history
		$mask = array_shift($stack);
		
		if(empty($mask)) {
			
			// Open Tickets
			
			if(null == ($open_view = UmScAbstractViewLoader::getView('', 'sc_history_open'))) {
				$open_view = new UmSc_TicketHistoryView();
				$open_view->id = 'sc_history_open';
			}
			
			// Lock to current visitor and open tickets
			$open_view->addParams(array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
			), true);

			$open_view->name = "";
			$open_view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
			$open_view->renderSortAsc = false;
			$open_view->renderLimit = 10;

			UmScAbstractViewLoader::setView($open_view->id, $open_view);
			$tpl->assign('open_view', $open_view);
			
			// Closed Tickets
			
			if(null == ($closed_view = UmScAbstractViewLoader::getView('', 'sc_history_closed'))) {
				$closed_view = new UmSc_TicketHistoryView();
				$closed_view->id = 'sc_history_closed';
			}
			
			// Lock to current visitor and closed tickets
			$closed_view->addParams(array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
			), true);

			$closed_view->name = "";
			$closed_view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
			$closed_view->renderSortAsc = false;
			$closed_view->renderLimit = 10;

			UmScAbstractViewLoader::setView($closed_view->id, $closed_view);
			$tpl->assign('closed_view', $closed_view);

			$tpl->display("devblocks:usermeet.core:portal_".UmPortalHelper::getCode() . ":support_center/history/index.tpl");
			
		} elseif ('search'==$mask) {
			@$q = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
			$tpl->assign('q', $q);

			if(null == ($view = UmScAbstractViewLoader::getView('', 'sc_history_search'))) {
				$view = new UmSc_TicketHistoryView();
				$view->id = 'sc_history_search';
			}
			
			$view->name = "";
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_MASK,
				SearchFields_Ticket::TICKET_SUBJECT,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TICKET_CLOSED,
			);
			$view->addParams(array(
				array(
					DevblocksSearchCriteria::GROUP_OR,
					new DevblocksSearchCriteria(SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($q,'all')),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,DevblocksSearchCriteria::OPER_LIKE,$q.'%'),
				),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
			), true);
			
			UmScAbstractViewLoader::setView($view->id, $view);
			$tpl->assign('view', $view);
			
			$tpl->display("devblocks:usermeet.core:portal_".UmPortalHelper::getCode() . ":support_center/history/search_results.tpl");
			
		} else {
			// Secure retrieval (address + mask)
			list($tickets) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
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
				$messages = DAO_Message::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
				$messages = array_reverse($messages, true);
				$attachments = array();						
				
				// Attachments
				if(is_array($messages) && !empty($messages)) {
					list($msg_attachments) = DAO_Attachment::search(
						array(
							SearchFields_Attachment::MESSAGE_ID => new DevblocksSearchCriteria(SearchFields_Attachment::MESSAGE_ID,'in',array_keys($messages))
						),
						-1,
						0,
						null,
						null,
						false
					);
					
					if(is_array($msg_attachments))
					foreach($msg_attachments as $attach_id => $attach) {
						if(null == ($msg_id = intval($attach[SearchFields_Attachment::MESSAGE_ID])))
							continue;
							
						if(0 == strcasecmp('original_message.html',$attach[SearchFields_Attachment::DISPLAY_NAME]))
							continue;
							
						if(!isset($attachments[$msg_id]))
							$attachments[$msg_id] = array();
						
						$attachments[$msg_id][$attach_id] = $attach;
						
						unset($attach);
					}
				}
				
				$tpl->assign('ticket', $ticket);
				$tpl->assign('messages', $messages);
				$tpl->assign('attachments', $attachments);
				
				$tpl->display("devblocks:usermeet.core:portal_".UmPortalHelper::getCode() . ":support_center/history/display.tpl");
			}
		}
				
	}
	
	function saveTicketPropertiesAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer','0');
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));		
	}
	
	function doReplyAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
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

		// Helpdesk settings
		$settings = DevblocksPlatform::getPluginSettingsService();
		$global_from = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM,CerberusSettingsDefaults::DEFAULT_REPLY_FROM);
		
		// Ticket group settings
		$group_id = $ticket[SearchFields_Ticket::TICKET_TEAM_ID];
		@$group_from = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_REPLY_FROM, '');
		
		// Headers
		$to = !empty($group_from) ? $group_from : $global_from;
		@$in_reply_to = $last_message_headers['message-id'];
		@$message_id = CerberusApplication::generateMessageId();
		
		$message = new CerberusParserMessage();
		$message->headers['from'] = $active_user->email;
		$message->headers['to'] = $to;
		$message->headers['date'] = date('r');
		$message->headers['subject'] = 'Re: ' . $ticket[SearchFields_Ticket::TICKET_SUBJECT];
		$message->headers['message-id'] = $message_id;
		$message->headers['in-reply-to'] = $in_reply_to;
		
		$message->body = sprintf(
			"%s",
			$content
		);
   
		CerberusParser::parseMessage($message,array('no_autoreply'=>true));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));
	}
};

class UmSc_TicketHistoryView extends C4_AbstractView {
	const DEFAULT_ID = 'sc_history';
	
	private $_TPL_PATH = '';

	function __construct() {
		$this->_TPL_PATH = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_MASK,
			SearchFields_Ticket::TICKET_SUBJECT,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
		);
		
		$this->addParamsHidden(array(
			SearchFields_Ticket::TICKET_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Ticket::search(
			array(),
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		//$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->display("devblocks:usermeet.core:portal_".UmPortalHelper::getCode() . ":support_center/history/view.tpl");
	}

	function getFields() {
		return SearchFields_Ticket::getFields();
	}
};