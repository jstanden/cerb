<?php
class Controller_SSO extends DevblocksControllerExtension {
	public function handleRequest(DevblocksHttpRequest $request) {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		@array_shift($stack); // sso
		@$provider_uri = array_shift($stack); // e.g. cognito, gsuite, ldap, saml

		// Public avatar endpoint for the login form's SSO buttons. Lives on
		// this controller (rather than /avatars) because /avatars requires
		// an active worker and the login form is by definition signed-out.
		if('_avatar' == $provider_uri) {
			$this->_renderServiceAvatar(array_shift($stack));
			return;
		}

		if(!($service = DAO_ConnectedService::getByUri($provider_uri)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		// Is this SSO provider enabled for worker logins?
		$service_ids = explode(',', DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_SSO_SERVICE_IDS, ''));
		if(!in_array($service->id, $service_ids))
			DevblocksPlatform::dieWithHttpError(null, 500);

		// Is the extension valid?
		if(!($service_extension = $service->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 500);

		// Does the extension have the SSO option?
		if(!$service_extension->hasOption('sso'))
			DevblocksPlatform::dieWithHttpError(null, 500);

		// Route the request the extension
		$service_extension->sso($service, $stack);
	}

	private function _renderServiceAvatar($uri) : void {
		if(empty($uri) || !($service = DAO_ConnectedService::getByUri($uri)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		// Only serve avatars for services exposed on the login form
		$service_ids = explode(',', DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_SSO_SERVICE_IDS, ''));
		if(!in_array($service->id, $service_ids))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$http = DevblocksPlatform::services()->http();

		if(
			($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_CONNECTED_SERVICE, $service->id))
			&& !empty($avatar->storage_key)
			&& !empty($avatar->content_type)
			&& false !== ($contents = Storage_ContextAvatar::get($avatar))
		) {
			$http
				->setHeader('Accept-Ranges', 'bytes')
				->setHeader('Cache-Control', 'max-age=86400', true)
				->setHeader('Content-Length', $avatar->storage_size)
				->setHeader('Content-Type', $avatar->content_type)
				->setHeader('Expires', gmdate('D, d M Y H:i:s', time()+86400) . ' GMT')
				->setHeader('Pragma', 'cache')
			;
			echo $contents;
			exit;
		}

		// Deterministic single-letter monogram (e.g. "G" for "Google"), with
		// a stable color hashed off the service URI so it stays consistent
		// even if an admin renames the service
		Controller_Avatars::renderMonogram(mb_substr($service->name, 0, 1), $service->uri);
		exit;
	}

	public function writeResponse(DevblocksHttpResponse $response) {}
}