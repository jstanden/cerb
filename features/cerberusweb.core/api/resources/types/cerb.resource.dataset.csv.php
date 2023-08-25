<?php
class ResourceType_DatasetCsv extends Extension_ResourceType {
	const ID = 'cerb.resource.dataset.csv';
	
	const PARAM_MIME_TYPE = 'mime_type';
	
	function validateContentData($fp, &$extension_params=[], &$error=null) : bool {
		if($fp) {
			$line = fgets($fp);
			fseek($fp, 0);
			
			// Make sure the first line is valid CSV
			if(
				!json_encode($line, true)
				|| !is_array(str_getcsv($line))
			) {
				$error = "The dataset must contain lines of comma-separated values.";
				return false;
			}
			
			$extension_params[self::PARAM_MIME_TYPE] = 'text/csv';
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