<?php
class Controller_SSO extends DevblocksControllerExtension {
	public function handleRequest(DevblocksHttpRequest $request) {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		@array_shift($stack); // sso
		@$provider_uri = array_shift($stack); // e.g. cognito, gsuite, ldap, saml
		
		if(false == ($service = DAO_ConnectedService::getByUri($provider_uri)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Is this SSO provider enabled for worker logins?
		$service_ids = explode(',', DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_SSO_SERVICE_IDS, ''));
		if(false == in_array($service->id, $service_ids))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		// Is the extension valid?
		if(false == ($service_extension = $service->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		// Does the extension have the SSO option?
		if(!$service_extension->hasOption('sso'))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		// Route the request the extension
		$service_extension->sso($service, $stack);
	}

	public function writeResponse(DevblocksHttpResponse $response) {}
}