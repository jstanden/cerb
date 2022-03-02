<?php
class UmScHistoryController extends Extension_UmScController {
	const PARAM_WORKLIST_COLUMNS_JSON = 'history.worklist.columns';
	
	function isVisible() {
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		return !empty($active_contact);
	}
	
	public function invoke(string $action, DevblocksHttpRequest $request=null) {
		switch($action) {
			case 'doReply':
				return $this->_portalAction_doReply();
			case 'saveTicketProperties':
				return $this->_portalAction_saveTicketProperties();
		}
		return false;
	}
	
	function renderSidebar(DevblocksHttpResponse $response) {
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		
		if(false == $active_contact)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		array_shift($stack); // history
		$mask = array_shift($stack);
		
		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(empty($shared_address_ids))
			$shared_address_ids = array(-1);
		
		if(!$mask) {
			// Ticket history
			
			// Prompts
			$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array',[]);
			$prompts['status'] = array_intersect($prompts['status'] ?: ['o','w','c'], ['o','w','c']);
			$tpl->assign('prompts', $prompts);
			
			// Query
			$query = '';
			
			if(@$prompts['created']) {
				$query .= sprintf('created:"%s" ',
					str_replace('"', '', $prompts['created'])
				);
			}
			
			if(@$prompts['status']) {
				$query .= sprintf('status:[%s] ',
					implode(',', $prompts['status'])
				);
			}
			
			if(@$prompts['keywords']) {
				$query .= sprintf('%s ',
					str_replace(':', '', $prompts['keywords'])
				);
			}
			
			// View
			if(null == ($history_view = UmScAbstractViewLoader::getView('', 'sc_history_list'))) {
				$history_view = new UmSc_TicketHistoryView();
				$history_view->id = 'sc_history_list';
				$history_view->name = "";
				$history_view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
				$history_view->renderSortAsc = false;
				$history_view->renderLimit = 10;
				$history_view->addParams([], true);
			}
			
			$history_view->addParamsWithQuickSearch($query, true);
			
			@$params_columns = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_WORKLIST_COLUMNS_JSON, '[]', true);
			
			if(empty($params_columns))
				$params_columns = array(
					SearchFields_Ticket::TICKET_LAST_WROTE_ID,
					SearchFields_Ticket::TICKET_UPDATED_DATE,
				);
				
			$history_view->view_columns = $params_columns;
			
			// Lock to current visitor
			$history_view->addParamsRequired(array(
				'_acl_reqs' => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',$shared_address_ids),
				'_acl_status' => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID,'!=',Model_Ticket::STATUS_DELETED),
			), true);
			
			UmScAbstractViewLoader::setView($history_view->id, $history_view);
			$tpl->assign('view', $history_view);
			
			$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/history/index.tpl");
			
		} else {
			// If this is an invalid ticket mask, deny access
			if(false == ($ticket = DAO_Ticket::getTicketByMask($mask)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$participants = $ticket->getRequesters();
			
			// See if the current account is one of the participants on this ticket
			$matching_participants = array_intersect(array_keys($participants), $shared_address_ids);
			
			// If none of the participants on the ticket match this account, deny access
			if(!is_array($matching_participants) || empty($matching_participants))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$messages = DAO_Message::getMessagesByTicket($ticket->id);
			$messages = array_reverse($messages, true);
			
			$tpl->assign('ticket', $ticket);
			$tpl->assign('participants', $participants);
			$tpl->assign('messages', $messages);
			
			$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages), false);
			$tpl->assign('attachments', $attachments);
			
			$badge_extensions = DevblocksPlatform::getExtensions('cerberusweb.support_center.message.badge', true);
			$tpl->assign('badge_extensions', $badge_extensions);
			
			$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/history/display.tpl");
		}
	}
	
	function configure(Model_CommunityTool $portal) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $portal);

		$params = array(
			'columns' => DAO_CommunityToolProperty::get($portal->code, self::PARAM_WORKLIST_COLUMNS_JSON, '[]', true),
		);
		$tpl->assign('history_params', $params);
		
		
		$view = new View_Ticket();
		$view->id = View_Ticket::DEFAULT_ID;
		
		$columns = array_filter(
			$view->getColumnsAvailable(),
			function($column) {
				return !empty($column->db_label);
			}
		);
		
		$columns_selected = [];
		if(is_array($params['columns']))
		foreach($params['columns'] as $column_key)
			if(isset($columns[$column_key]))
				$columns_selected[$column_key] = $columns[$column_key];
		
		$columns_available = array_diff_key($columns, $columns_selected);
		DevblocksPlatform::sortObjects($columns_available, 'db_label');
		
		$tpl->assign('history_columns', array_merge($columns_selected, $columns_available));
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/history.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $portal) {
		$columns = DevblocksPlatform::importGPC($_POST['history_columns'] ?? null, 'array', []);

		$columns = array_filter($columns, function($column) {
			return !empty($column);
		});
		
		DAO_CommunityToolProperty::set($portal->code, self::PARAM_WORKLIST_COLUMNS_JSON, $columns, true);
	}
	
	private function _portalAction_saveTicketProperties() {
		$umsession = ChPortalHelper::getSession();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$mask = DevblocksPlatform::importGPC($_POST['mask'] ?? null, 'string','');
		$subject = DevblocksPlatform::importGPC($_POST['subject'] ?? null, 'string','');
		$participants = DevblocksPlatform::importGPC($_POST['participants'] ?? null, 'string','');
		$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'] ?? null, 'integer','0');
		
		if(false == ($active_contact = $umsession->getProperty('sc_login', null)))
			DevblocksPlatform::dieWithHttpError(null, 403);

		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(!$shared_address_ids)
			$shared_address_ids = [-1];
		
		CerberusContexts::pushActivityDefaultActor(CerberusContexts::CONTEXT_CONTACT, $active_contact->id);
		
		if(false == ($ticket = DAO_Ticket::getTicketByMask($mask)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$participants_old = $ticket->getRequesters();
		
		// Only allow access if mask has one of the valid requesters
		$allowed_requester_ids = array_intersect(array_keys($participants_old), $shared_address_ids);
		
		if(!$allowed_requester_ids)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$fields = [];
		
		if(!empty($subject))
			$fields[DAO_Ticket::SUBJECT] = $subject;
		
		// Status: Ignore deleted/waiting
		if($is_closed && !in_array($ticket->status_id, array(Model_Ticket::STATUS_CLOSED, Model_Ticket::STATUS_DELETED))) {
			$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_CLOSED;
		} elseif (!$is_closed && ($ticket->status_id == Model_Ticket::STATUS_CLOSED)) {
			$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_OPEN;
		}
		
		if($fields)
			DAO_Ticket::update($ticket->id, $fields);
		
		CerberusContexts::popActivityDefaultActor();
		
		// Participants
		$participants_new = DAO_Address::lookupAddresses(DevblocksPlatform::parseCrlfString($participants), true);
		$participants_removed = array_diff(array_keys($participants_old), array_keys($participants_new));
		$participants_added = array_diff(array_keys($participants_new), array_keys($participants_old));
		
		if(!empty($participants_removed)) {
			DAO_Ticket::removeParticipantIds($ticket->id, $participants_removed);
		}
		
		if(!empty($participants_added)) {
			DAO_Ticket::addParticipantIds($ticket->id, $participants_added);
		}
		
		// Redirect
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'history', $ticket->mask)));
	}
	
	private function _portalAction_doReply() {
		$umsession = ChPortalHelper::getSession();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$from = DevblocksPlatform::importGPC($_POST['from'] ?? null, 'string','');
		$mask = DevblocksPlatform::importGPC($_POST['mask'] ?? null, 'string','');
		$content = DevblocksPlatform::importGPC($_POST['content'] ?? null, 'string','');
		
		if(false == ($active_contact = $umsession->getProperty('sc_login', null)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Load contact addresses
		$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
		if(empty($shared_address_ids))
			$shared_address_ids = array(-1);
		
		// Validate FROM address
		if(null == ($from_address = DAO_Address::lookupAddress($from, false)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if($from_address->contact_id != $active_contact->id)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($ticket = DAO_Ticket::getTicketByMask($mask)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Only allow access if mask has one of the valid requesters
		$requesters = $ticket->getRequesters();
		$allowed_requester_ids = array_intersect(array_keys($requesters), $shared_address_ids);
		
		if(!$allowed_requester_ids)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$messages = DAO_Message::getMessagesByTicket($ticket->id);
		$last_message = array_pop($messages); /* @var $last_message Model_Message */
		$last_message_headers = $last_message->getHeaders();
		unset($messages);

		// Ticket group settings
		$group = DAO_Group::get($ticket->group_id);
		@$group_replyto = $group->getReplyTo($ticket->bucket_id);
		
		// Headers
		$message = new CerberusParserMessage();
		$message->headers['from'] = $from_address->email;
		$message->headers['to'] = $group_replyto->email;
		$message->headers['date'] = date('r');
		$message->headers['subject'] = 'Re: ' . $ticket->subject;
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
					if(empty($name))
						continue;
					
					$attach = new ParserFile();
					$attach->setTempFile($files['tmp_name'][$idx],'application/octet-stream');
					$attach->file_size = filesize($files['tmp_name'][$idx]);
					$message->files[$name] = $attach;
				}
				
			} else {
				if(!isset($files['name']) || empty($files['name']))
					continue;
				
				$attach = new ParserFile();
				$attach->setTempFile($files['tmp_name'],'application/octet-stream');
				$attach->file_size = filesize($files['tmp_name']);
				$message->files[$files['name']] = $attach;
			}
		}
		
		CerberusParser::parseMessage($message, array('no_autoreply'=>true));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal', ChPortalHelper::getCode(), 'history', $ticket->mask)));
	}
};

class UmSc_TicketHistoryView extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'sc_history';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_SUBJECT,
			SearchFields_Ticket::TICKET_LAST_WROTE_ID,
		);
		
		$this->doResetCriteria();
	}

	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		$columns = array_merge($this->view_columns, array($this->renderSortBy));
		
		return DAO_Ticket::search(
			$columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Ticket');
		
		return $objects;
	}

	function render() {
		//$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$currencies = DAO_Currency::getAll();
		$tpl->assign('currencies', $currencies);
		
		$results = $this->getData();
		$tpl->assign('results', $results);
		$tpl->assign('total', $results[1]);
		$tpl->assign('data', $results[0]);
		
		// Bulk lazy load first wrote
		$object_first_wrotes = [];
		if(in_array('t_first_wrote_address_id', $this->view_columns)) {
			$first_wrote_ids = DevblocksPlatform::extractArrayValues($results, 't_first_wrote_address_id');
			$object_first_wrotes = DAO_Address::getIds($first_wrote_ids);
			$tpl->assign('object_first_wrotes', $object_first_wrotes);
		}
		
		// Bulk lazy load last wrote
		$object_last_wrotes = [];
		if(in_array('t_last_wrote_address_id', $this->view_columns)) {
			$last_wrote_ids = DevblocksPlatform::extractArrayValues($results, 't_last_wrote_address_id');
			$object_last_wrotes = DAO_Address::getIds($last_wrote_ids);
			$tpl->assign('object_last_wrotes', $object_last_wrotes);
		}
		
		// Bulk lazy load orgs
		$object_orgs = [];
		if(in_array('t_org_id', $this->view_columns)) {
			$org_ids = DevblocksPlatform::extractArrayValues($results, 't_org_id');
			$object_orgs = DAO_ContactOrg::getIds($org_ids);
			$tpl->assign('object_orgs', $object_orgs);
		}
		
		$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/history/view.tpl");
	}

	function getFields() {
		return SearchFields_Ticket::getFields();
	}
	
	function getSearchFields() {
		$fields = SearchFields_Ticket::getFields();

		foreach(array_keys($fields) as $key) {
			switch($key) {
				case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				case SearchFields_Ticket::REQUESTER_ID:
				case SearchFields_Ticket::TICKET_MASK:
				case SearchFields_Ticket::TICKET_SUBJECT:
				case SearchFields_Ticket::TICKET_CREATED_DATE:
				case SearchFields_Ticket::TICKET_UPDATED_DATE:
				case SearchFields_Ticket::VIRTUAL_STATUS:
					break;
				default:
					unset($fields[$key]);
			}
		}
		
		return $fields;
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		$translate = DevblocksPlatform::getTranslationService();
		
		switch($field) {
			// Overload
			case SearchFields_Ticket::REQUESTER_ID:
				$strings = array();
				if(empty($values) || !is_array($values))
					break;
				$addresses = DAO_Address::getWhere(sprintf("%s IN (%s)", DAO_Address::ID, implode(',', $values)));
				
				foreach($values as $val) {
					if(isset($addresses[$val]))
						$strings[] = DevblocksPlatform::strEscapeHtml($addresses[$val]->email);
				}
				echo implode('</b> or <b>', $strings);
				break;
				
			// Overload
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'open':
							$strings[] = DevblocksPlatform::strEscapeHtml($translate->_('status.waiting'));
							break;
						case 'waiting':
							$strings[] = DevblocksPlatform::strEscapeHtml($translate->_('status.open'));
							break;
						case 'closed':
							$strings[] = DevblocksPlatform::strEscapeHtml($translate->_('status.closed'));
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function doSetCriteria($field, $oper, $value) {
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		
		$criteria = null;

		switch($field) {
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				$scope = DevblocksPlatform::importGPC($_POST['scope'] ?? null, 'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$statuses = DevblocksPlatform::importGPC($_POST['value'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field, $oper, $statuses);
				break;
				
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
				$from = DevblocksPlatform::importGPC($_POST['from'] ?? null, 'string','');
				$to = DevblocksPlatform::importGPC($_POST['to'] ?? null, 'string','');

				if(empty($from) || (!is_numeric($from) && @false === strtotime(str_replace('.','-',$from))))
					$from = 0;
					
				if(empty($to) || (!is_numeric($to) && @false === strtotime(str_replace('.','-',$to))))
					$to = 'now';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Ticket::REQUESTER_ID:
				$requester_ids = DevblocksPlatform::importGPC($_POST['requester_ids'] ?? null, 'array', []);
				
				// If blank, this is pointless.
				if(empty($active_contact) || empty($requester_ids))
					break;
				
				$shared_address_ids = DAO_SupportCenterAddressShare::getContactAddressesWithShared($active_contact->id, true);
				if(empty($shared_address_ids))
					$shared_address_ids = array(-1);
					
				// Sanitize the selections to make sure they only include verified addresses on this contact
				$intersect = array_intersect(array_keys($shared_address_ids), $requester_ids);
				
				if(empty($intersect))
					break;
				
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$intersect);
				break;
				
//			default:
//				// Custom Fields
//				if(substr($field,0,3)=='cf_') {
//					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
//				}
//				break;
		}

		if(!empty($criteria)) {
			$param_key = null;
			$results = ($this->findParam($criteria->field, $this->getEditableParams()));
			
			if(!empty($results))
				$param_key = key($results);
			
			$this->addParam($criteria, $param_key);
			$this->renderPage = 0;
		}
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Ticket::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT),
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_CREATED_DATE),
				),
			'mask' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_MASK, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'examples' => array(
						'ABC',
						'("XYZ-12345-678")',
					),
				),
			'participant' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Ticket::REQUESTER_ADDRESS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'participant.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'status' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_STATUS),
					'examples' => array(
						'open',
						'waiting',
						'closed',
						'deleted',
						'[o,w]',
						'![d]',
					),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_UPDATED_DATE),
				),
		);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_MessageContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
		}
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'participant':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_PARTICIPANT_SEARCH);
				break;
				
			case 'status':
				$field_key = SearchFields_Ticket::VIRTUAL_STATUS;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
				
				$values = array();
				
				// Normalize status labels
				foreach($value as $status) {
					switch(substr(DevblocksPlatform::strLower($status), 0, 1)) {
						case 'o':
							$values['open'] = true;
							break;
						case 'w':
							$values['waiting'] = true;
							break;
						case 'c':
							$values['closed'] = true;
							break;
						case 'd':
							$values['deleted'] = true;
							break;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
				break;
			
			default:
				break;
		}
		
		$search_fields = $this->getQuickSearchFields();
		return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
	}
};