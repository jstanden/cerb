<?php
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

class Controller_Resource extends DevblocksControllerExtension {
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path; // URLs like: /resource/cerberusweb.core/images/images.png
		
		array_shift($stack); // resource
		$plugin_id = array_shift($stack); // cerberusweb.core
		$path = $stack; // images/image.png
		
		if('cerberusweb.core' == $plugin_id) {
			$resource = implode('/', $path);
			if(in_array($resource, ['css/logo','css/logo-dark','css/user.css'])) {
				$this->_handleUserResourceRequest($resource);
				exit;
			}
		}
		
		if(null == ($plugin = DevblocksPlatform::getPlugin($plugin_id)))
			DevblocksPlatform::dieWithHttpError(null, 404); // not found
		
		try {
			$file = implode(DIRECTORY_SEPARATOR, $path); // combine path
			$dir = realpath($plugin->getStoragePath() . DIRECTORY_SEPARATOR . 'resources') . DIRECTORY_SEPARATOR;
			
			if(!is_dir($dir))
				DevblocksPlatform::dieWithHttpError(null, 403); // basedir security
			
			if(!($resource = realpath($dir . $file)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!DevblocksPlatform::strStartsWith($resource, $dir))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$ext = explode('.', DevblocksPlatform::strLower($resource));
			$ext = end($ext);
			
			// Security
			switch($ext) {
				case 'php':
					if(!is_file($resource))
						DevblocksPlatform::dieWithHttpError(null, 403); // extension security
					break;
			}
			
			// Caching
			switch($ext) {
				case 'css':
				case 'gif':
				case 'ico':
				case 'jpg':
				case 'jpeg':
				case 'js':
				case 'png':
				case 'svg':
				case 'ttf':
				case 'woff':
				case 'woff2':
					DevblocksPlatform::services()->http()
						->setHeader('Cache-Control', ' max-age=604800') // 1 wk // , must-revalidate
						->setHeader('Expires', gmdate('D, d M Y H:i:s',time()+604800) . ' GMT') // 1 wk
					;
					break;
			}
			
			// Content types
			switch($ext) {
				case 'css':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'text/css');
					break;
				case 'eot':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/vnd.ms-fontobject');
					break;
				case 'gif':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'image/gif');
					break;
				case 'ico':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'image/x-icon');
					break;
				case 'jpeg':
				case 'jpg':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'image/jpeg');
					break;
				case 'js':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'text/javascript');
					break;
				case 'json':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
					break;
				case 'png':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'image/png');
					break;
				case 'svg':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'image/svg+xml');
					break;
				case 'ttf':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/x-font-ttf');
					break;
				case 'woff':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/font-woff');
					break;
				case 'woff2':
					DevblocksPlatform::services()->http()->setHeader('Content-Type', 'font/woff2');
					break;
				default:
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			@$out = file_get_contents($resource, false);
	
			// Pass through
			if($out) {
				DevblocksPlatform::services()->http()->setHeader('Content-Length', strlen($out));
				echo $out;
			}
			
		} catch (Exception $e) {
			error_log($e->getMessage());
			
		}
		
		exit;
	}
	
	private function _handleUserResourceRequest($request_path) {
		switch($request_path) {
			case 'css/logo':
			case 'css/logo-dark':
				$logo = null;
				$resource_content = null;
			
				// Load the dark logo if requested
				if($request_path == 'css/logo-dark')
					$logo = DAO_Resource::getByName('ui.logo.dark');
				
				// Otherwise, load the light logo
				if(!$logo)
					$logo = DAO_Resource::getByName('ui.logo');
				
				// If we have a logo resource
				if($logo && $logo->extension_id == ResourceType_Image::ID)
					$resource_content = $logo->getExtension()->getContentData($logo);
					
				// If we don't have a logo resource, use the Cerb logo defaults
				if(!($resource_content instanceof Model_Resource_ContentData)) {
					$resource_content = new Model_Resource_ContentData();
					
					$resource_content->headers = [
						'Content-Type: image/svg+xml',
					];
					
					$plugin = DevblocksPlatform::getPlugin('cerberusweb.core');
					$dir = $plugin->getStoragePath() . DIRECTORY_SEPARATOR . 'resources';
					
					if($request_path == 'css/logo-dark') {
						$logo_path = $dir . DIRECTORY_SEPARATOR . 'images/wgm/cerb_logo_dark.svg';
					} else {
						$logo_path = $dir . DIRECTORY_SEPARATOR . 'images/wgm/cerb_logo.svg';
					}
					
					$resource_content->data = fopen($logo_path, 'rb');
				}
				
				// If no expiration, synthesize 1d
				if(!$resource_content->expires_at)
					$resource_content->expires_at = time() + 86400; // 1 day
				
				$resource_content->headers = array_merge($resource_content->headers, [
					'Pragma: cache',
					sprintf('Cache-control: max-age=%d', $resource_content->expires_at - time()),
					'Expires: ' . gmdate('D, d M Y H:i:s', $resource_content->expires_at) . ' GMT',
					'Accept-Ranges: bytes',
				]);
				
				// Pass through
				if($resource_content instanceof Model_Resource_ContentData) {
					$resource_content->writeHeaders();					
					$resource_content->writeBody();
				}
				break;
				
			case 'css/user.css':
				DevblocksPlatform::services()->http()
					->setHeader('Cache-Control', ' max-age=86400') // 1 day // , must-revalidate
					->setHeader('Content-Type', 'text/css')
					->setHeader('Expires', gmdate('D, d M Y H:i:s',time()+86400) . ' GMT') // 1 day
				;
				
				echo DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::UI_USER_STYLESHEET, '');
				break;
		}
	}
};
