<?php
class DAO_ContextMergeHistory {
	const CONTEXT = 'context';
	const FROM_CONTEXT_ID = 'from_context_id';
	const TO_CONTEXT_ID = 'to_context_id';
	const UPDATED = 'updated';
	
	public static function logMerge($context, $from_id, $to_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($context) || empty($from_id) || empty($to_id))
			return false;
		
		// We can't merge with ourselves.
		if($from_id == $to_id)
			return false;
		
		/*
		 * Make sure to handle situations where A merges with B, then B merges with C.
		 * A should point to C (B can no longer be a destination)
		 */
		$db->Execute(sprintf("UPDATE context_merge_history SET to_context_id = %d, updated = %d WHERE to_context_id = %d",
			$to_id,
			time(),
			$from_id
		));
			
		$db->Execute(sprintf("INSERT IGNORE INTO context_merge_history (context, from_context_id, to_context_id, updated) ".
			"VALUES(%s, %d, %d, %d)",
			$db->qstr($context),
			$from_id,
			$to_id,
			time()
		));
	}
	
	public static function findNewId($context, $from_id) {
		$db = DevblocksPlatform::getDatabaseService();
		// [TODO]
	}
}