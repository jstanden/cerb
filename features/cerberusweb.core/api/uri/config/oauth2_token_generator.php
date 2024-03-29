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

class PageSection_SetupDevelopersOAuth2TokenGenerator extends Extension_PageSection {
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		
		@array_shift($stack); // config
		@array_shift($stack); // bot_scripting_tester
		
		$visit->set(ChConfigurationPage::ID, 'oauth2_token_generator');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/oauth2-token-generator/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'generateToken':
					return $this->_configAction_generateToken();
			}
		}
		return false;
	}
	
	private function _configAction_generateToken() : void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$oauth_app_id = DevblocksPlatform::importGPC($_POST['oauth_app_id'] ?? null, 'integer', 0);
		$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'integer', 0);
		$scopes = DevblocksPlatform::importGPC($_POST['scopes'] ?? null, 'string', '');
		$expires_duration = DevblocksPlatform::importGPC($_POST['expires_duration'] ?? null, 'integer', 0);
		$expires_term = DevblocksPlatform::importGPC($_POST['expires_term'] ?? null, 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$oauth_app_id || false == ($oauth_app = DAO_OAuthApp::get($oauth_app_id)))
				throw new Exception_DevblocksAjaxValidationError('A valid OAuth app is required.');
			
			if(!$worker_id || false == ($worker = DAO_Worker::get($worker_id)))
				throw new Exception_DevblocksAjaxValidationError('A valid worker is required.');
			
			if(!$scopes)
				throw new Exception_DevblocksAjaxValidationError('A valid scope is required.');
			
			if(!$expires_duration || !in_array($expires_term,['minutes','hours','days','weeks','months','years'])) {
				$expires_at = '1 hours';
			} else {
				$expires_at = sprintf("%d %s", $expires_duration, $expires_term);
			}
			
			// Validate scopes against app
			
			$scopes = explode(' ', $scopes);
			$app_scopes = $oauth_app->getAvailableScopes();
			$unknown_scopes = array_values(array_diff($scopes, array_keys($app_scopes)));
			
			if($unknown_scopes)
				throw new Exception_DevblocksAjaxValidationError(sprintf('Unknown scopes: %s', implode(', ', $unknown_scopes)));
			
			// Generate tokens
			
			$grant = new Cerb_OAuth2GrantManual();
			
			$bearer_token = $grant->generateBearerToken(
				$oauth_app,
				$worker->id,
				$scopes,
				$expires_at,
				$expires_at . ' + 1 month'
			);
			
			if(false === $bearer_token)
				throw new Exception_DevblocksAjaxValidationError('Failed to create an access token.');
				
			$tpl->assign('bearer_token', $bearer_token);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/oauth2-token-generator/results.tpl');
			
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
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred when creating the token.',
			]);
			return;
		}
	}
}