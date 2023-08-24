<?php
class ResourceType_DatasetJsonl extends Extension_ResourceType {
	const ID = 'cerb.resource.dataset.jsonl';
	
	const PARAM_MIME_TYPE = 'mime_type';
	
	function validateContentData($fp, &$extension_params=[], &$error=null) : bool {
		if($fp) {
			fseek($fp, 0);
			
			// Make sure the first line is valid JSON
			
			if(false === ($line = fgets($fp))) {
				$error = "The dataset must contain one valid JSON document per line.";
				return false;
			}
			
			$json = json_decode($line, true);
			
			if(is_null($json) || false === $json) {
				$error = "The dataset must contain one valid JSON document per line.";
				return false;
			}
			
			fseek($fp, 0);
			
			$extension_params[self::PARAM_MIME_TYPE] = 'text/jsonl';
		}
		
		return true;
	}
	
	/**
	 * @param Model_Resource $resource
	 * @return Model_Resource_ContentData
	 */
	function getContentData(Model_Resource $resource) {
		$content_data = new Model_Resource_ContentData();
		
		if(!($params = $resource->getExtensionParams()))
			return null;
		
		if(array_key_exists(self::PARAM_MIME_TYPE, $params)) {
			$content_data->headers[] = sprintf('Content-Type: %s', $params[self::PARAM_MIME_TYPE]);
		}
		
		$this->getContentResource($resource, $content_data);
		
		return $content_data;
	}
}