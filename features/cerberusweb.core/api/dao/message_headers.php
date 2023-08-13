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
		
		if(empty($message_id))
			return [];

		$sql = sprintf("SELECT headers ".
			"FROM message_headers ".
			"WHERE message_id = %d",
			$message_id
		);
		
		if(false === ($raw_headers = $db->GetOneReader($sql)))
			return false;
		
		return trim($raw_headers) . "\r\n\r\n";
	}
	
	static function getRaws(array $message_ids) : array {
		$db = DevblocksPlatform::services()->database();
		
		$message_ids = DevblocksPlatform::sanitizeArray($message_ids, 'int', ['nonzero']);
		
		if(empty($message_ids))
			return [];
		
		$sql = sprintf("SELECT message_id, headers ".
			"FROM message_headers ".
			"WHERE message_id IN (%s)",
			implode(',', $message_ids)
		);
		
		$results = [];
		
		if(false === ($rows = $db->GetArrayReader($sql)))
			return [];
		
		foreach($rows as $row) {
			$results[$row['message_id']] = trim($row['headers']) . "\r\n\r\n";
		}
		
		return $results;
	}
	
	static function getSinceId($since_id, $limit=25) {
		$db = DevblocksPlatform::services()->database();
		$message_headers = [];
		
		$sql = sprintf("SELECT message_id, headers ".
			"FROM message_headers ".
			"WHERE message_id > %d ".
			"LIMIT %d",
			$since_id,
			$limit
		);
		
		if(false === ($results = $db->GetArrayReader($sql)))
			return false;
		
		foreach($results as $row) {
			$message_headers[$row['message_id']] = self::parse(trim($row['headers']) . "\r\n\r\n");
		}
		
		return $message_headers;
	}
	
	static function getAll($message_id, $flatten_arrays=true) {
		if(false == ($raw_headers = self::getRaw($message_id)))
			return false;
		
		return self::parse($raw_headers, $flatten_arrays);
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;
		
		$db = DevblocksPlatform::services()->database();
		 
		$sql = sprintf("DELETE FROM message_headers WHERE message_id IN (%s)",
			implode(',', $ids)
		);
		$db->ExecuteMaster($sql);
	}
};

class Search_MessageHeaders extends Extension_DevblocksSearchSchema {
	const ID = 'cerberusweb.search.schema.message_headers';
	
	public function getNamespace() {
		return 'message_header';
	}
	
	public function getAttributes() {
		return [
			'header_name' => 'string',
		];
	}
	
	public function getIdField() {
		return 'message_id';
	}
	
	public function getDataField() {
		return 'header_value';
	}
	
	public function getPrimaryKey() {
		return [
			'message_id',
			'header_name',
		];
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the engine can tell us where the index left off
		if(isset($meta['max_id']) && $meta['max_id']) {
			$this->setParam('last_indexed_id', $meta['max_id']);
			
			// If the index has a delta, start from the current record
		} elseif($meta['is_indexed_externally']) {
			// Do nothing (let the remote tool update the DB)
			
			// Otherwise, start over
		} else {
			$this->setIndexPointer(self::INDEX_POINTER_RESET);
		}
	}
	
	public function setIndexPointer($pointer) {
		switch($pointer) {
			case self::INDEX_POINTER_RESET:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', 0);
				break;
			
			case self::INDEX_POINTER_CURRENT:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', time());
				break;
		}
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::services()->log();
		$ns = self::getNamespace();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$message_id = $this->getParam('last_indexed_id', 0);
		
		$done = false;
		
		while(!$done && time() < $stop_time) {
			$message_headers = DAO_MessageHeaders::getSinceId($message_id, 100);
			
			if(!$message_headers) {
				$done = true;
				continue;
			}
			
			$count = 0;
			
			if(is_array($message_headers))
				foreach($message_headers as $message_id => $headers) {
					$logger->info(sprintf("[Search] Indexing %s %d...",
						$ns,
						$message_id
					));
					
					// Normalize delivered-to
					if(!array_key_exists('delivered-to', $headers)) {
						if(array_key_exists('envelope-to', $headers))
							$headers['delivered-to'] = $headers['envelope-to'];
						if(array_key_exists('x-envelope-to', $headers))
							$headers['delivered-to'] = $headers['x-envelope-to'];
						if(array_key_exists('original-to', $headers))
							$headers['delivered-to'] = $headers['original-to'];
					}
					
					// Only headers we care about
					
					$headers = array_intersect_key(
						$headers,
						[
							'cc' => true,
							'delivered-to' => true,
							'from' => true,
							'to' => true,
							'x-forwarded-to' => true,
							'x-mailer' => true,
						]
					);
					
					foreach($headers as $header_name => $header_value) {
						$doc = [
							'header_value' => $header_value,
						];
						
						$attributes = [
							'header_name' => $header_name,
						];
						
						$engine->index($this, $message_id, $doc, $attributes);
					}
					
					// Record our progress every 25th index
					if(++$count % 25 == 0 && $message_id) {
						$this->setParam('last_indexed_id', $message_id);
					}
				}
			
			// Record our index every batch
			if($message_id)
				$this->setParam('last_indexed_id', $message_id);
		}
		
		return true;
	}
	
	public function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) {
			if(is_string($ids) || is_numeric($ids)) {
				$ids = [$ids];
			} else {
				return false;
			}
		}
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return true;
		
		$sql = sprintf("DELETE FROM fulltext_message_header WHERE message_id IN (%s)",
			implode(',', $ids)
		);
		
		if(false === $db->ExecuteMaster($sql))
			return false;
		
		return true;
	}
};