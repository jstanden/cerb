<?php /** @noinspection PhpUnused */
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

class PageSection_SetupDevelopersLlmAgentTranscripts extends Extension_PageSection {
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // llm_agent_transcripts
		$transcript_id = array_shift($stack); // a1b2c3d4-a1b2-c3d4-e5f6-a1b2c3d4e5f6
		
		$visit->set(ChConfigurationPage::ID, 'llm_agent_transcripts');
		
		$limit = 100;
		$transcripts = DAO_LlmAgentSession::search($limit);
		
		$tpl->assign('limit', $limit);
		$tpl->assign('transcripts', $transcripts);
		$tpl->assign('transcript_id', $transcript_id);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope = null) {
		if ('configAction' == $scope) {
			switch ($action) {
				case 'deleteTranscript':
					return $this->_configAction_deleteTranscript();
				case 'getTranscript':
					return $this->_configAction_getTranscript();
				case 'loadTranscripts':
					return $this->_configAction_loadTranscripts();
				case 'markTranscriptRead':
					return $this->_configAction_markTranscriptRead();
			}
		}
		return false;
	}
	
	private function _configAction_loadTranscripts(): void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$before_id = DevblocksPlatform::importGPC($_POST['before_id'] ?? null, 'string', '');
		$limit = DevblocksPlatform::importGPC($_POST['limit'] ?? null, 'integer', 0);
		$is_unread = DevblocksPlatform::importGPC($_POST['is_unread'] ?? null, 'bool', false);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$transcripts = DAO_LlmAgentSession::search($limit ?: 100, $is_unread, $before_id);
			
			$tpl->assign('limit', $limit);
			$tpl->assign('transcripts', $transcripts);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/transcripts.tpl');
			
			echo json_encode([
				'status' => true,
				'html' => $html,
			]);
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
		}
	}
	
	private function _configAction_getTranscript() : void {
		$tpl = DevblocksPlatform::services()->template();
		$llm = DevblocksPlatform::services()->llm();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!($llm_session = DAO_LlmAgentSession::get($transcript_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid transcript ID.');
				
			if(!($llm_provider = $llm->getProvider($llm_session->provider, [], validate: false)))
				throw new Exception_DevblocksAjaxValidationError('Invalid LLM provider.');
			
			if(!($messages = DAO_LlmAgentMessage::getMessagesBySession($transcript_id, 250)))
				$messages = [];
			
			// Convert the messages into a neutral format using providers
			$messages = array_map(fn($message) => $llm_provider->convertToGenericMessage($message->data, $message->uuid), $messages);
			
			$tpl->assign('filter_links', new \Cerb_HTMLPurifier_URIFilter_Extract());
			
			$tpl->assign('llm_session', $llm_session);
			$tpl->assign('llm_session_automation', $llm_session->getAutomation());
			$tpl->assign('llm_session_user', $llm_session->getUser());
			$tpl->assign('messages', $messages);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/transcript.tpl');
			
			echo json_encode([
				'status' => true,
				'html' => $html,
			]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			return;
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
			return;
		}
	}
	
	private function _configAction_deleteTranscript() : void {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if (!(DAO_LlmAgentSession::get($transcript_id))) {
				echo json_encode([
					'status' => false,
					'error' => 'Invalid LLM transcript ID.',
				]);
				return;
			}
			
			DAO_LlmAgentSession::delete($transcript_id);
			
			echo json_encode([
				'status' => true,
			]);
			return;
			
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
			return;
		}
	}
	
	private function _configAction_markTranscriptRead() : void {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		$is_read = true;
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if (!(DAO_LlmAgentSession::get($transcript_id))) {
				echo json_encode([
					'status' => false,
					'error' => 'Invalid LLM transcript ID.',
				]);
				return;
			}
			
			DAO_LlmAgentSession::markRead($transcript_id, $is_read);
			
			echo json_encode([
				'status' => true,
			]);
			return;
			
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
			return;
		}
	}
}