<?php
class ResourceType_Font extends Extension_ResourceType {
	const ID = 'cerb.resource.font';
	
	const PARAM_MIME_TYPE = 'mime_type';
	
	public static function getTempPathByName(string $resource_key) {
		// Look up the TTF font
		if(!($font = \DAO_Resource::getByNameAndType($resource_key, self::ID)))
			return null;
		
		// Use a versioned cache key
		$font_cache_path = sprintf("%s/font_cache/%d-%d.ttf",
			APP_TEMP_PATH,
			$font->id,
			$font->updated_at
		);
		
		// Check the temp path first
		if(file_exists($font_cache_path))
			return $font_cache_path;
		
		// Create the font cache if it doesn't exist
		if(!file_exists(APP_TEMP_PATH . '/font_cache/'))
			if(!mkdir(APP_TEMP_PATH . '/font_cache/', 0770))
				return null;
		
		if(!($font_data = $font->getExtension()->getContentData($font)))
			return null;
		
		// Write the bytes to the temp path
		if(!(file_put_contents($font_cache_path, fread($font_data->data, 1_048_576))))
			return null;
		
		return $font_cache_path;
	}
	
	function validateContentData($fp, &$extension_params=[], &$error=null) : bool {
		if($fp) {
			$bytes = null;
			while (!feof($fp)) {
				$bytes .= fread($fp, 65536);
			}
			
			fseek($fp, 0);
			
			$im = imagecreate(100, 100);
			$white = imagecolorallocate($im, 255, 255, 255);
			
			$fp_metadata = stream_get_meta_data($fp);
			$fp_filename = $fp_metadata['uri'] ?? null;
			
			if (!(imagettftext($im, 28, 0, 0, 0, $white, $fp_filename, 'Test'))) {
				imagedestroy($im);
				$error = 'Invalid font.';
				return false;
			}
			
			imagedestroy($im);
			
			$extension_params[self::PARAM_MIME_TYPE] = 'font/ttf';
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