<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class Controller_OAuth extends DevblocksControllerExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		$active_worker = CerberusApplication::getActiveWorker();
		return !is_null($active_worker);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path; // URLs like: /oauth/callback/wgm.facebook.service.provider
		array_shift($stack); // oauth
		@$action = array_shift($stack); // callback
		@$ext_id = array_shift($stack); // wgm.facebook.service.provider
		
		// A session must exist to use this controller
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		// We only accept a single action right now
		if($action != 'callback')
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		// The given extension must be valid
		if(empty($ext_id) || false == ($ext = Extension_ServiceProvider::get($ext_id)))
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		// The given extension must implement OAuth callbacks
		if(!($ext instanceof IServiceProvider_OAuth))
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		/* @var $ext IServiceProvider_OAuth */
		// Trigger the extension's oauth callback
		$ext->oauthCallback();
	}
};
