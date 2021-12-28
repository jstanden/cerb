<?php
class ResourceType_PortalImage extends Extension_ResourceType {
	const ID = 'cerb.resource.portal.image';
	
	const PARAM_HEIGHT = 'height';
	const PARAM_MIME_TYPE = 'mime_type';
	const PARAM_WIDTH = 'width';
	
	function validateContentData($fp, &$extension_params=[], &$error=null) : bool {
		if($fp) {
			$bytes = null;
			while(!feof($fp)) {
				$bytes .= fread($fp, 65536);
			}
			
			fseek($fp, 0);
			
			if(false === ($image_stats = getimagesizefromstring($bytes))) {
				$error = "The upload file is not a valid image.";
				return false;
			}
			
			$extension_params[self::PARAM_WIDTH] = $image_stats[0] ?? 0;
			$extension_params[self::PARAM_HEIGHT] = $image_stats[1] ?? 0;
			$extension_params[self::PARAM_MIME_TYPE] = $image_stats['mime'] ?? '';
		}
		
		return true;
	}
	
	/**
	 * @param Model_Resource $resource
	 * @return Model_Resource_ContentData
	 */
	function getContentData(Model_Resource $resource) {
		$content_data = new Model_Resource_ContentData();
		
		if(false == ($params = $resource->getExtensionParams()))
			return null;
		
		if(array_key_exists(self::PARAM_MIME_TYPE, $params)) {
			$content_data->headers[] = sprintf('Content-Type: %s', $params[self::PARAM_MIME_TYPE]);
		}
		
		$this->getContentResource($resource, $content_data);
		
		return $content_data;
	}
}