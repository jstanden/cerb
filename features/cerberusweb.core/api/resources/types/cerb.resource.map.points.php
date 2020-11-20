<?php
class ResourceType_MapPoints extends Extension_ResourceType {
	const ID = 'cerb.resource.map.points';
	
	/**
	 * @param Model_Resource $resource
	 * @return Model_Resource_ContentData
	 */
	function getContentData(Model_Resource $resource) {
		$content_data = new Model_Resource_ContentData();
		
		$content_data->headers = [
			'Content-Type: application/json; charset=utf-8',
		];
		
		$this->getContentResource($resource, $content_data);
		
		return $content_data;
	}
}