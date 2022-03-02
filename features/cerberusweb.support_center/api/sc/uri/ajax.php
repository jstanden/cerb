<?php /** @noinspection PhpUnused */

class UmScAjaxController extends Extension_UmScController {
	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	public function isVisible() {
		return true;
	}
	
	function invoke(string $action, DevblocksHttpRequest $request=null) {
		switch($action) {
			case 'downloadFile':
				return $this->_portalAction_downloadFile($request);
			case 'viewPage':
				return $this->_portalAction_viewPage();
			case 'viewRefresh':
				return $this->_portalAction_viewRefresh();
			case 'viewSortBy':
				return $this->_portalAction_viewSortBy();
		}
		return false;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$path = $request->path ?? [];
		$a = DevblocksPlatform::importGPC($_REQUEST['a'] ?? null, 'string', '');
		
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$umsession = ChPortalHelper::getSession();
		
		$active_contact = $umsession->getProperty('sc_login');
		$tpl->assign('active_contact', $active_contact);
		
		array_shift($path); // ajax
		
		$action = strval($a ?: array_shift($path));
		
		$this->invoke($action, new DevblocksHttpRequest($path));

		DevblocksPlatform::exit();
	}
	
	private function _portalAction_viewRefresh() {
		$view_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'string','');
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
	}
	
	private function _portalAction_viewPage() {
		$view_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'string','');
		$page = DevblocksPlatform::importGPC($_REQUEST['page'] ?? null, 'integer',0);
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->renderPage = $page;
			UmScAbstractViewLoader::setView($view->id, $view);
			
			$view->render();
		}
	}
	
	private function _portalAction_viewSortBy() {
		$view_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'string','');
		$sort_by = DevblocksPlatform::importGPC($_REQUEST['sort_by'] ?? null, 'string','');
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$fields = $view->getColumnsAvailable();
			if(isset($fields[$sort_by])) {
				if(0==strcasecmp($view->renderSortBy,$sort_by)) { // clicked same col?
					$view->renderSortAsc = !(bool)$view->renderSortAsc; // flip order
				} else {
					$view->renderSortBy = $sort_by;
					$view->renderSortAsc = true;
				}
				
				$view->renderPage = 0;
				
				UmScAbstractViewLoader::setView($view->id, $view);
			}
			
			$view->render();
		}
	}
	
	private function _portalAction_downloadFile(DevblocksHttpRequest $request) {
		$umsession = ChPortalHelper::getSession();
		$stack = $request->path;
		
		// Attachment hash + display name
		@$hash = array_shift($stack);
		@$name = array_shift($stack);
		
		if(empty($hash) || empty($name))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Attachment
		if(null == ($file_id = DAO_Attachment::getBySha1Hash($hash)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($file = DAO_Attachment::get($file_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$pass = false;
		
		if(false == ($links = DAO_Attachment::getLinks($file_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!$pass && isset($links[CerberusContexts::CONTEXT_KB_ARTICLE])) {
			// [TODO] Compare KB links to this portal
			$pass = true;
		}
		
		if(!$pass && isset($links[CerberusContexts::CONTEXT_MESSAGE])) {
			if(null == ($active_contact = $umsession->getProperty('sc_login',null))) /* @var $active_contact Model_Contact */
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(false == ($contact_emails = $active_contact->getEmails()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$pass = DAO_Ticket::authorizeByParticipantsAndMessages(array_keys($contact_emails), $links[CerberusContexts::CONTEXT_MESSAGE]);
		}
		
		if(!$pass)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$contents = $file->getFileContents();
			
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Accept-Ranges: bytes");
		header("Content-Type: " . $file->mime_type);
		header("Content-Length: " . strlen($contents));
		
		// Dump contents
		echo $contents;
		DevblocksPlatform::exit();
	}
}