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
			if(in_array($resource, ['css/logo','css/user.css'])) {
				$this->_handleUserResourceRequest($resource);
				exit;
			}
		}
		
		if(null == ($plugin = DevblocksPlatform::getPlugin($plugin_id)))
			DevblocksPlatform::dieWithHttpError(null, 404); // not found
		
		try {
			$file = implode(DIRECTORY_SEPARATOR, $path); // combine path
			$dir = realpath($plugin->getStoragePath() . DIRECTORY_SEPARATOR . 'resources');
			
			if(!is_dir($dir))
				DevblocksPlatform::dieWithHttpError(null, 403); // basedir security
			
			if(false == ($resource = realpath($dir . DIRECTORY_SEPARATOR . $file)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!DevblocksPlatform::strStartsWith($resource, $dir))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$ext = DevblocksPlatform::strLower(@array_pop(explode('.', $resource)));
			
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
					header('Cache-control: max-age=604800', true); // 1 wk // , must-revalidate
					header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
					break;
			}
			
			// Content types
			switch($ext) {
				case 'css':
					header('Content-type: text/css');
					break;
				case 'eot':
					header('Content-type: application/vnd.ms-fontobject');
					break;
				case 'gif':
					header('Content-type: image/gif');
					break;
				case 'ico':
					header('Content-type: image/x-icon');
					break;
				case 'jpeg':
				case 'jpg':
					header('Content-type: image/jpeg');
					break;
				case 'js':
					header('Content-type: text/javascript');
					break;
				case 'json':
					header('Content-type: application/json');
					break;
				case 'png':
					header('Content-type: image/png');
					break;
				case 'svg':
					header('Content-type: image/svg+xml');
					break;
				case 'ttf':
					header('Content-type: application/x-font-ttf');
					break;
				case 'woff':
					header('Content-type: application/font-woff');
					break;
				case 'woff2':
					header('Content-type: font/woff2');
					break;
				default:
					DevblocksPlatform::dieWithHttpError(null, 403);
					break;
			}
			
			@$out = file_get_contents($resource, false);
	
			// Pass through
			if($out) {
				header('Content-Length: '. strlen($out));
				echo $out;
			}
			
		} catch (Exception_Devblocks $e) {
			error_log($e->getMessage());
			
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		exit;
	}
	
	private function _handleUserResourceRequest($request_path) {
		switch($request_path) {
			case 'css/logo':
				$logo_path = APP_STORAGE_PATH . '/logo';
				
				if(file_exists($logo_path) && false != ($out = file_get_contents($logo_path))) {
					$logo_mimetype = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::UI_USER_LOGO_MIME_TYPE, 0);
					
				} else {
					$logo_mimetype = 'image/png';
					
					$plugin = DevblocksPlatform::getPlugin('cerberusweb.core');
					$dir = $plugin->getStoragePath() . DIRECTORY_SEPARATOR . 'resources';
					$logo_path = $dir . DIRECTORY_SEPARATOR . 'images/wgm/cerb_logo.png';
					
					$out = file_get_contents($logo_path, false);
				}
				
				// Pass through
				if($out) {
					header('Cache-control: max-age=86400', true); // 1 day // , must-revalidate
					header('Expires: ' . gmdate('D, d M Y H:i:s',time()+86400) . ' GMT'); // 1 day
					header('Content-type: ' . $logo_mimetype);
					header('Content-Length: '. strlen($out));
					echo $out;
				}
				break;
				
			case 'css/user.css':
				header('Content-type: text/css');
				header('Cache-control: max-age=86400', true); // 1 day // , must-revalidate
				header('Expires: ' . gmdate('D, d M Y H:i:s',time()+86400) . ' GMT'); // 1 day
				
				echo DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::UI_USER_STYLESHEET, '');
				break;
		}
	}
};
