<?php
class UmScServerController extends Extension_UmScController {
	const DC_STATUS_URL = 'server.datacenter_status_url';
	const DC_SHOW_MODULE = 'server.datacenter_show_mode';
	
	function renderSidebar(DevblocksHttpResponse $response) {
//		$tpl = DevblocksPlatform::getTemplateService();
	}
	
	function isVisible() {
		return true;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', NULL);
		
		$tpl->assign('contact', $active_contact);
		
		$stack = $response->path;
		array_shift($stack); //server
		
		$fingerprint = ChPortalHelper::getFingerprint();
		
		switch (array_shift($stack)) {
			case 'detail':
				$id = intval(array_shift($stack));
				$serverowners = DAO_ContactPerson::getOwnedServers($active_contact->id);
				$servers = array();
				foreach ($serverowners as $so) {
					array_push($servers, $so->server_id);
				}
				
				list($entries, $count) = DAO_Journal::search(
					array(), 
					array(
						new DevblocksSearchCriteria(SearchFields_Journal::ID, '=', $id),
						new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT, '=', CerberusContexts::CONTEXT_SERVER),
						new DevblocksSearchCriteria(SearchFields_Journal::ISINTERNAL, '=', '0'),
						array(
							DevblocksSearchCriteria::GROUP_OR,
							new DevblocksSearchCriteria(SearchFields_Journal::ISPUBLIC, '=', 1),
							new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT_ID, 'in', $servers)
						)
					), 
					-1, 
					0, 
					NULL,
					NULL,
					FALSE
				);
				
				if (!isset($entries[$id]))
					break;
				
				$entry = DAO_Journal::get($id);
				$tpl->assign('entry', $entry);
				
				$server = DAO_Server::get($entry->context_id);
				$tpl->assign('server', $server);
				
				// Attachments
				$attachments_map = DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_JOURNAL, $id);
				$tpl->assign('attachments_map', $attachments_map);
				
				$tpl->display("devblocks:cerberusweb.datacenter.servers:portal_".ChPortalHelper::getCode().":support_center/server/display.tpl");
				break;
			case 'newjournalentry':
				if (empty($active_contact))
					break;
				$sContent = $umsession->getProperty('support.write.last_content', '');
				$sServer = $umsession->getProperty('support.write.last_server', '');
				$sError = $umsession->getProperty('support.write.last_error', '');
				
				$tpl->assign('last_content', $sContent);
				$tpl->assign('last_server', $sServer);
				$tpl->assign('last_error', $sError);
				
				$serverowners = DAO_ContactPerson::getOwnedServers($active_contact->id);
				$servers = array();
				foreach ($serverowners as $so) {
					$servers[$so->server_id] = DAO_Server::get($so->server_id);
				}				
				$tpl->assign('servers', $servers);
				
				$address = DAO_Address::get($active_contact->email_id);
				$tpl->assign('address', $address);
				
				$tpl->assign('fingerprint', $fingerprint);
				
				$tpl->display("devblocks:cerberusweb.datacenter.servers:portal_".ChPortalHelper::getCode().":support_center/server/form.tpl");
				break;
			case 'status':
				@$dcStatusUrl = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::DC_STATUS_URL, NULL);
				if (null == ($view = UmScAbstractViewLoader::getView('', UmSc_ServerStatusView::DEFAULT_ID))) {
					$view = new UmSc_ServerStatusView();
				}
				
				$view->addParams(array(
					new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT, DevblocksSearchCriteria::OPER_EQ, CerberusContexts::CONTEXT_SERVER),
					new DevblocksSearchCriteria(SearchFields_Journal::ISINTERNAL, DevblocksSearchCriteria::OPER_EQ, '0'),
					new DevblocksSearchCriteria(SearchFields_Journal::ISPUBLIC, DevblocksSearchCriteria::OPER_EQ, '1'),
					new DevblocksSearchCriteria(SearchFields_Journal::STATE, DevblocksSearchCriteria::OPER_NEQ, '0')
				), TRUE);
				
				$view->renderSortBy = SearchFields_Journal::CREATED;
				$view->renderSortAsc = FALSE;
				$view->renderLimit = 0;
				$view->renderPage = 0;
				
				UmScAbstractViewLoader::setView($view->id, $view);
				
				$tpl->assign('view', $view);
				
				if (!empty($dcStatusUrl))
					$tpl->assign('url', $dcStatusUrl);
				
				$tpl->display("devblocks:cerberusweb.datacenter.servers:portal_".ChPortalHelper::getCode().":support_center/server/status.tpl");				
				break;
			default:
			case 'browse':
				@$dcShowMode = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::DC_SHOW_MODULE, 0);
				$serveronwers = DAO_ContactPerson::getOwnedServers($active_contact->id);				
				$servers = array();				
				foreach ($serveronwers as $so) {
					array_push($servers, $so->server_id);
				}
				
				if(null == ($view = UmScAbstractViewLoader::getView('', UmSc_ServerJournalListView::DEFAULT_ID))) {
					$view = new UmSc_ServerJournalListView();
				}
				
				if ($dcShowMode == 1) {
					$view->addParams(array(
						new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT, DevblocksSearchCriteria::OPER_EQ, CerberusContexts::CONTEXT_SERVER),
						new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT_ID, DevblocksSearchCriteria::OPER_IN, $servers),
						new DevblocksSearchCriteria(SearchFields_Journal::ISINTERNAL, DevblocksSearchCriteria::OPER_EQ, '0')
					), TRUE);
				} elseif ($dcShowMode == 0) {
					$view->addParams(array(
						new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT, DevblocksSearchCriteria::OPER_EQ, CerberusContexts::CONTEXT_SERVER),
						new DevblocksSearchCriteria(SearchFields_Journal::ISPUBLIC, DevblocksSearchCriteria::OPER_EQ, '1'),
						new DevblocksSearchCriteria(SearchFields_Journal::ISINTERNAL, DevblocksSearchCriteria::OPER_EQ, '0')
					), TRUE);
				} else {
					$view->addParams(array(
						new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT, DevblocksSearchCriteria::OPER_EQ, CerberusContexts::CONTEXT_SERVER),
						new DevblocksSearchCriteria(SearchFields_Journal::ISINTERNAL, DevblocksSearchCriteria::OPER_EQ, '0'),
						array(
							DevblocksSearchCriteria::GROUP_OR,
							new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT_ID, DevblocksSearchCriteria::OPER_IN, $servers),
							new DevblocksSearchCriteria(SearchFields_Journal::ISPUBLIC, DevblocksSearchCriteria::OPER_EQ, '1')
						)
					), TRUE);
				}
				
				$view->renderPage = 0;
				$view->renderLimit = 10;
				
				UmScAbstractViewLoader::setView($view->id, $view);
				$tpl->assign('view', $view);
				
				$tpl->display('devblocks:cerberusweb.datacenter.servers:portal_'.ChPortalHelper::getCode().':support_center/server/index.tpl');
				break;
		}
	}
	
	function doJournalEntrySaveAction() {
		@$sJournal = DevblocksPlatform::importGPC($_POST['journal'], 'string', '');
		@$sCaptcha = DevblocksPlatform::importGPC($_POST['captcha'], 'string', '');
		@$sContextId = DevblocksPlatform::importGPC($_POST['context_id'], 'integer', NULL);
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', NULL);
		
		$umsession->setProperty('support.write.last_content', $sJournal);
		$umsession->setProperty('support.write.last_server', $sContextId);
		
		$captcha_session = $umsession->getProperty(UmScApp::SESSION_CAPTCHA, '***');
		
		// Journal entry is required
		if (isset($_POST['journal']) && empty($sJournal)) {
			$umsession->setProperty('support.write.last_error','A message is required'); //[TODO] Translate error messages
			
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'server','newjournalentry')));
			return;
		}
		
		// CAPTCHA required
		if (0 != strcasecmp($sCaptcha, $captcha_session)) {
			$umsession->setProperty('support.write.last_error','What you typed did not match the image.');
			
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'server','newjournalentry')));
			return;
		}
		
		// Required to select context server
		if (empty($sContextId)) {
			$umsession->setProperty('support.write.last_error','A server is required');
			
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'server','newjournalentry')));
			return;
		}
		
		$fields = array(
			DAO_Journal::CONTEXT => CerberusContexts::CONTEXT_SERVER,
			DAO_Journal::CONTEXT_ID => $sContextId,
			DAO_Journal::ADDRESS_ID => $active_contact->email_id,
			DAO_Journal::JOURNAL => $sJournal,
			DAO_Journal::CREATED => time(),
			DAO_Journal::ISPUBLIC => 0,
			DAO_Journal::ISINTERNAL => 0,
			DAO_Journal::STATE => 1
		);
		
		$journal_id = DAO_Journal::create($fields);
		
		// Add Files to journal entry
		foreach ($_FILES as $name => $files) {
			if (is_array($files['name'])) {
				foreach ($files['name'] as $idx => $name) {
					$file = new ParserFile();
					$file->setTempFile($files['tmp_name'][$idx],$files['type'][$idx]);
					$file->file_size = filesize($files['tmp_name'][$idx]);
					$filename = $name;
				}
			} else {
				$file = new ParserFile();
				$file->setTempFile($files['tmp_name'],$files['type']);
				$file->file_size = filesize($files['tmp_name']);
				$filename = $files['name'];
			}
			$file_id = DAO_Attachment::create(array(
				DAO_Attachment::DISPLAY_NAME => $filename,
				DAO_Attachment::MIME_TYPE => $file->mime_type
			));
			if (NULL !== ($fp = fopen($file->getTempFile(), 'rb'))) {
				Storage_Attachments::put($file_id, $fp);
				fclose($fp);
				unlink($file->getTempFile());
				DAO_AttachmentLink::create($file_id, CerberusContexts::CONTEXT_JOURNAL, $journal_id);
			}
		}
		
		// Clear any errors
		$umsession->setProperty('support.write.last_server',NULL);
		$umsession->setProperty('support.write.last_content',NULL);
		$umsession->setProperty('support.write.last_error',NULL);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'server','index')));
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		if (NULL != ($dcShowMode = DAO_CommunityToolProperty::get($instance->code, self::DC_SHOW_MODULE, 0))) {
			$tpl->assign('dc_show_mode', $dcShowMode);
		}
		if (NULL != ($dcStatusUrl = DAO_CommunityToolProperty::get($instance->code, self::DC_STATUS_URL, NULL))) {
			$tpl->assign('dc_status_url', $dcStatusUrl);
		}
		
		$tpl->display('devblocks:cerberusweb.datacenter.servers::portal/sc/config/module/server.tpl');
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$dcStatusUrl = DevblocksPlatform::importGPC($_POST['dc_status_url'], 'string', '');
		@$dcShowMode = DevblocksPlatform::importGPC($_POST['dc_show_mode'], 'integer', 0);
		
		if (!empty($dcStatusUrl) && substr($dcStatusUrl, -1, 1) != '/')
			$dcStatusUrl .= '/';
		
		DAO_CommunityToolProperty::set($instance->code, self::DC_STATUS_URL, $dcStatusUrl);
		DAO_CommunityToolProperty::set($instance->code, self::DC_SHOW_MODULE, $dcShowMode);
	}
};

class UmSc_ServerStatusView extends C4_AbstractView {
	const DEFAULT_ID = 'sc_srv_state';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = '';
		$this->renderSortBy = SearchFields_Journal::CONTEXT_ID;
		$this->renderSortAsc = TRUE;
		
		$this->view_columns = array(
			SearchFields_Journal::CONTEXT_ID,
			SearchFields_Journal::STATE
		);
		
		$this->addParamsHidden(array(
			SearchFields_Journal::ID,
			SearchFields_Journal::ISPUBLIC
		));
		
		$this->doResetCriteria();
	}
	
	function getData() {
		$objects = DAO_Journal::search(
			$this->view_columns,
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		$tpl->display("devblocks:cerberusweb.datacenter.servers:portal_".ChPortalHelper::getCode().":support_center/server/view_status.tpl");
	}
	
	function getFields() {
		SearchFields_Journal::getFields();
	}
}

class UmSc_ServerJournalListView extends C4_AbstractView {
	const DEFAULT_ID = 'sc_srv_jrn_list';
	
	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('portal.sc.public.server_history');
		$this->renderSortBy = SearchFields_Journal::CREATED;
		$this->renderSortAsc = FALSE;
		
		$this->view_columns = array(
			SearchFields_Journal::JOURNAL,
			SearchFields_Journal::CONTEXT_ID,
			SearchFields_Journal::CREATED,
			SearchFields_Journal::STATE
		);
		
		$this->addParamsHidden(array(
			SearchFields_Journal::ID
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Journal::search(
			$this->view_columns, 
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		$tpl->display('devblocks:cerberusweb.datacenter.servers:portal_'.ChPortalHelper::getCode().':support_center/server/view.tpl');
	}
	
	function getFields() {
		return SearchFields_Journal::getFields();
	}	
	
	function getSearchFields() {
		$fields = SearchFields_Journal::getFields();

		foreach($fields as $key => $field) {
			switch($key) {
				case SearchFields_Journal::JOURNAL:
				case SearchFields_Journal::CREATED:
				case SearchFields_Journal::ISPUBLIC:
				case SearchFields_Journal::STATE:
					break;
				default:
					unset($fields[$key]);
			}
		}
		
		return $fields;
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('id', $this->id);
		
		switch ($field) {
			case SearchFields_Journal::JOURNAL:
				$tpl->display("devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__string.tpl");
				break;
			case SearchFields_Journal::STATE:
				$tpl->display("devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__number.tpl");
				break;
			case SearchFields_Journal::CREATED:
				$tpl->display("devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__date.tpl");
				break;
			case SearchFields_Journal::ISPUBLIC:
				$tpl->display("devblocks:cerberusweb.support_center::support_center/internal/view/criteria/__bool.tpl");
				break;
			default:
				break;
		}
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = NULL;
		
		switch ($field) {
			case SearchFields_Journal::JOURNAL:
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE) && FALSE === (strpos($value, '*')))
					$value .= '*';
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Journal::STATE:
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Journal::CREATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'], 'string', '');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'], 'string', '');
				
				if (empty($from) || (!is_numeric($from) && @FALSE === strtotime(str_replace('.', '-', $from))))
					$from = 0;
				
				if (empty($to) || (!is_numeric($to) && @FALSE === strtotime(str_replace('.', '-', $to))))
					$to = 'now';
				
				$criteria = new DevblocksSearchCriteria($field, $oper, array($from, $to));
				break;
			case SearchFields_Journal::ISPUBLIC:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'], 'integer', 1);
				$criteria = new DevblocksSearchCriteria($field, $oper, $bool);
				break;
			default:
				parent::doSetCriteria($field, $oper, $value);
				return;
		}
		if (!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
}