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

class DAO_ContextMergeHistory extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const FROM_CONTEXT_ID = 'from_context_id';
	const TO_CONTEXT_ID = 'to_context_id';
	const UPDATED = 'updated';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::FROM_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::TO_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
			
		return $validation->getFields();
	}
	
	public static function logMerge($context, $from_id, $to_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($context) || empty($from_id) || empty($to_id))
			return false;
		
		// We can't merge with ourselves.
		if($from_id == $to_id)
			return false;
		
		/*
		 * Make sure to handle situations where A merges with B, then B merges with C.
		 * A should point to C (B can no longer be a destination)
		 */
		$db->ExecuteMaster(sprintf("UPDATE context_merge_history SET to_context_id = %d, updated = %d WHERE to_context_id = %d",
			$to_id,
			time(),
			$from_id
		));
			
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO context_merge_history (context, from_context_id, to_context_id, updated) ".
			"VALUES(%s, %d, %d, %d)",
			$db->qstr($context),
			$from_id,
			$to_id,
			time()
		));
	}
	
	public static function deleteByContextIds(mixed $context, array $context_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!($context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int')))
			return;
		
		// Delete context merge history
		$db->ExecuteMaster(sprintf("DELETE FROM context_merge_history WHERE context = %s AND to_context_id IN (%s)",
			$db->qstr($context),
			implode(',', $context_ids)
		));
	}
}