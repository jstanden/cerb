<?php
class DAO_MessageHeaders extends Cerb_ORMHelper {
	const HEADERS = 'headers';
	const MESSAGE_ID = 'message_id';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// text
		$validation
			->addField(self::HEADERS)
			->string()
			->setMaxLength(65535)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::MESSAGE_ID)
			->id()
			->setRequired(true)
			;

		return $validation->getFields();
	}
	
	static function upsert($message_id, $raw_headers) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($message_id) || !is_string($raw_headers))
			return false;
		
		$db->ExecuteMaster(sprintf("REPLACE INTO message_headers (message_id, headers) ".
				"VALUES (%d, %s)",
				$message_id,
				$db->qstr(ltrim($raw_headers))
		));
	}
	
	static function parse($raw_headers, $flatten_arrays=true, $convert_qp=true) {
		if(false == ($mime = new MimeMessage('var', $raw_headers)))
			return false;
		
		if(!isset($mime->data))
			return false;
		
		if($convert_qp) {
			$headers = CerberusParser::fixQuotePrintableArray($mime->data['headers']);
		} else {
			$headers = $mime->data['headers'];
		}
		
		if($flatten_arrays)
		foreach($headers as &$v) {
			if(is_array($v))
				$v = implode(';; ', $v);
		}
		
		ksort($headers);
		
		return $headers;
	}

	static function getRaw($message_id) {
		$db = DevblocksPlatform::services()->database();

		$sql = sprintf("SELECT headers ".
			"FROM message_headers ".
			"WHERE message_id = %d",
			$message_id
		);
		
		if(false === ($raw_headers = $db->GetOneReader($sql)))
			return false;
		
		return trim($raw_headers) . "\r\n\r\n";
	}
	
	static function getAll($message_id, $flatten_arrays=true) {
		if(false == ($raw_headers = self::getRaw($message_id)))
			return false;
		
		$headers = self::parse($raw_headers, $flatten_arrays);
		
		return $headers;
	}

	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::services()->database();
		 
		$sql = sprintf("DELETE FROM message_headers WHERE message_id IN (%s)",
			implode(',', $ids)
		);
		$db->ExecuteMaster($sql);
	}
};