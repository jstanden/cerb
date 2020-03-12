<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;

class Controller_Security extends DevblocksControllerExtension {
	const ID = 'core.controller.security';
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		$path = $request->path;
		array_shift($path); // security
		@$action = array_shift($path); // renderLinkPopup
		
		switch($action) {
			case 'proxyImage':
				$this->_controllerAction_proxyImage();
				break;
			case 'renderLinkPopup':
				$this->_controllerAction_renderLinkPopup();
				break;
		}
		
		DevblocksPlatform::exit();
	}
	
	function writeResponse(DevblocksHttpResponse $response) {}
	
	private function _controllerAction_renderLinkPopup() {
		$tpl = DevblocksPlatform::services()->template();
		
		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		@$url = DevblocksPlatform::importGPC($_GET['url'], 'string', null);
		$tpl->assign('url', $url);
		
		$url_parts = parse_url($url);
		$tpl->assign('url_parts', $url_parts);
		
		if(array_key_exists('query', $url_parts)) {
			$query_parts = DevblocksPlatform::strParseQueryString($url_parts['query']);
			$tpl->assign('query_parts', $query_parts);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/security/redirect_popup.tpl');
		
		DevblocksPlatform::exit();
	}
	
	private function _controllerAction_proxyImage() {
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'], 'string', null);
		@$url_sig = DevblocksPlatform::importGPC($_REQUEST['s'], 'string', null);
		
		// If the URL is blank, 404
		if(!$url)
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		try {
			$http_client = DevblocksPlatform::services()->http()->getClient();
			
			$image_proxy_redirects_disabled = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_IMAGE_PROXY_REDIRECTS_DISABLED, 0);
			$image_proxy_timeout_ms = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_IMAGE_PROXY_TIMEOUT_MS, 2000);
			$image_proxy_secret = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_IMAGE_SECRET, null);
			
			if($image_proxy_secret) {
				$hash = hash_hmac('sha256', $url, $image_proxy_secret, true);
				$hash = DevblocksPlatform::services()->string()->base64UrlEncode($hash);
				
				// If unauthenticated
				if(!$url_sig || !DevblocksPlatform::strStartsWith($hash, $url_sig))
					DevblocksPlatform::dieWithHttpError(null, 401);
			}
			
			$http_options = [
				RequestOptions::COOKIES => false,
				RequestOptions::TIMEOUT => $image_proxy_timeout_ms/1000,
				RequestOptions::ALLOW_REDIRECTS => $image_proxy_redirects_disabled ? false : true,
			];
			
			$response = $http_client->get($url, $http_options);
			$content_type = null;
			
			$body_reader = $response->getBody();
			$body_data = $body_reader->getContents();
			
			if(false == (@$img = imagecreatefromstring($body_data)))
				DevblocksPlatform::dieWithHttpError(null, 500);
			
			/*
			// Block transparent pixels
			if($this->isFullyTransparent($img)) {
				imagedestroy($img);
				DevblocksPlatform::dieWithHttpError(null, 403);
			}
			*/
			
			header('Content-Type: image/png');
			
			header('Pragma: cache');
			header('Cache-control: max-age=604800', true); // 1 wk
			header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
			
			imagesavealpha($img, true);
			imagepng($img);
			imagedestroy($img);
			
			DevblocksPlatform::exit(200);
			
		} catch (ConnectException $e) {
			DevblocksPlatform::dieWithHttpError(null, 408);
			
		} catch (Exception $e) {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}
	
	private function isFullyTransparent($img) : bool {
		$width = imagesx($img);
		$height = imagesy($img);
		
		// Reject all single pixels
		if(1 == $width && 1 == $height)
			return true;
		
		// [TODO] Randomly sample a few pixels before doing it linearly
		// [TODO] An image can be 99% transparent (the last pixel can cheat)
		
		for($x = 0; $x < $width; $x++) {
			for($y = 0; $y < $height; $y++) {
				$rgba = imagecolorat($img, $x, $y);
				$alpha = ($rgba & 0x7F000000) >> 24;
				if($alpha != 127)
					return false;
			}
		}
		
		return true;
	}
}