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

class DAO_ContextLink extends Cerb_ORMHelper {
	const FROM_CONTEXT = 'from_context';
	const FROM_CONTEXT_ID = 'from_context_id';
	const TO_CONTEXT = 'to_context';
	const TO_CONTEXT_ID = 'to_context_id';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::FROM_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::FROM_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::TO_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::TO_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		
		return $validation->getFields();
	}

	static public function setLink($src_context, $src_context_id, $dst_context, $dst_context_id, $src_context_meta=null, $dst_context_meta=null) {
		$db = DevblocksPlatform::services()->database();
		$event = DevblocksPlatform::services()->event();
		$active_worker = CerberusApplication::getActiveWorker();

		// Don't link something to itself.
		if(0 == strcasecmp($src_context, $dst_context)
			&& intval($src_context_id) == intval($dst_context_id))
				return false;
		
		// If someone is linking a custom fieldset, do that instead
		if($src_context == Context_CustomFieldset::ID) {
			$sql = sprintf("INSERT IGNORE INTO context_to_custom_fieldset (context, context_id, custom_fieldset_id) ".
				"VALUES (%s, %d, %d)",
				$db->qstr($dst_context),
				$dst_context_id,
				$src_context_id
			);
			$db->ExecuteMaster($sql);
			return true;
			
		} elseif($dst_context == Context_CustomFieldset::ID) {
			$sql = sprintf("INSERT IGNORE INTO context_to_custom_fieldset (context, context_id, custom_fieldset_id) ".
				"VALUES (%s, %d, %d)",
				$db->qstr($src_context),
				$src_context_id,
				$dst_context_id
			);
			$db->ExecuteMaster($sql);
			return true;
		}
		
		$ext_src_context = Extension_DevblocksContext::get($src_context);
		$ext_dst_context = Extension_DevblocksContext::get($dst_context);
		
		if(false == $src_context_meta)
			@$src_context_meta = $ext_src_context->getMeta($src_context_id);
		
		if(false == $dst_context_meta)
			@$dst_context_meta = $ext_dst_context->getMeta($dst_context_id);
		
		if(!$src_context_meta || !$dst_context_meta)
			return false;
		
		// [TODO] Verify contexts on both sides prior to linking, or return false
		
		$sql = sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
			"VALUES (%s, %d, %s, %d) ",
			$db->qstr($src_context),
			$src_context_id,
			$db->qstr($dst_context),
			$dst_context_id
		);
		$db->ExecuteMaster($sql);

		// Fire an event
		if($db->Affected_Rows()) {
			$event->trigger(
				new Model_DevblocksEvent(
					'context_link.set',
					array(
						'from_context' => $src_context,
						'from_context_id' => $src_context_id,
						'to_context' => $dst_context,
						'to_context_id' => $dst_context_id,
					)
				)
			);
			
		} else {
			return false;
		}
		
		// Reciprocal
		$sql = sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
			"VALUES (%s, %d, %s, %d) ",
			$db->qstr($dst_context),
			$dst_context_id,
			$db->qstr($src_context),
			$src_context_id
		);
		$db->ExecuteMaster($sql);
		
		// Fire an event
		$event->trigger(
			new Model_DevblocksEvent(
				'context_link.set',
				array(
					'from_context' => $dst_context,
					'from_context_id' => $dst_context_id,
					'to_context' => $src_context,
					'to_context_id' => $src_context_id,
				)
			)
		);
		
		/*
		 * Log activity (connection.link)
		 */
		
		// Are we following something?
		if($dst_context == CerberusContexts::CONTEXT_WORKER) {
			// If worker is actor and target, and we're not inside a bot
			if($active_worker && $active_worker->id == $dst_context_id && 0 == EventListener_Triggers::getDepth()) {
				$entry = array(
					//{{actor}} started watching {{target_object}} {{target}}
					'message' => 'activities.watcher.follow',
					'variables' => array(
						'target_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
						'target' => $src_context_meta['name'],
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d/%s", $src_context, $src_context_id, DevblocksPlatform::strToPermalink($src_context_meta['name'])),
						)
				);
				CerberusContexts::logActivity('watcher.follow', $src_context, $src_context_id, $entry);
			} else {
				$watcher_worker = DAO_Worker::get($dst_context_id);
				
				$entry = array(
					//{{actor}} added {{watcher}} as a watcher to {{target_object}} {{target}}
					'message' => 'activities.watcher.assigned',
					'variables' => array(
						'watcher' => $watcher_worker->getName(),
						'target_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
						'target' => $src_context_meta['name'],
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d/%s", $src_context, $src_context_id, DevblocksPlatform::strToPermalink($src_context_meta['name'])),
						'watcher' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_WORKER, $watcher_worker->id, DevblocksPlatform::strToPermalink($watcher_worker->getName())),
						)
				);
				CerberusContexts::logActivity('watcher.assigned', $src_context, $src_context_id, $entry);
			}
			
		// Otherwise, do the connection
		} else {
			$entry = array(
				//{{actor}} connected {{target_object}} {{target}} to {{link_object}} {{link}}
				'message' => 'activities.connection.link',
				'variables' => array(
					'target_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
					'target' => $src_context_meta['name'],
					'link_object' => mb_convert_case($ext_dst_context->manifest->name, MB_CASE_LOWER),
					'link' => $dst_context_meta['name'],
					),
				'urls' => array(
					'target' => sprintf("ctx://%s:%d/%s", $src_context, $src_context_id, DevblocksPlatform::strToPermalink($src_context_meta['name'])),
					'link' => sprintf("ctx://%s:%d/%s", $dst_context, $dst_context_id, DevblocksPlatform::strToPermalink($dst_context_meta['name'])),
					)
			);
			CerberusContexts::logActivity('connection.link', $src_context, $src_context_id, $entry);
			
			$entry = array(
				//{{actor}} connected {{target_object}} {{target}} to {{link_object}} {{link}}
				'message' => 'activities.connection.link',
				'variables' => array(
					'target_object' => mb_convert_case($ext_dst_context->manifest->name, MB_CASE_LOWER),
					'target' => $dst_context_meta['name'],
					'link_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
					'link' => $src_context_meta['name'],
					),
				'urls' => array(
					'target' => sprintf("ctx://%s:%d/%s", $dst_context, $dst_context_id, DevblocksPlatform::strToPermalink($dst_context_meta['name'])),
					'link' => sprintf("ctx://%s:%d/%s", $src_context, $src_context_id, DevblocksPlatform::strToPermalink($src_context_meta['name'])),
					)
			);
			CerberusContexts::logActivity('connection.link', $dst_context, $dst_context_id, $entry);
			
		}
		
		return true;
	}
	
	static public function getDistinctContexts($context, $context_id) {
		$db = DevblocksPlatform::services()->database();
		
		$rows = array();
		
		$rs = $db->QueryReader(sprintf("SELECT DISTINCT to_context AS context FROM context_link WHERE from_context = %s AND from_context_id = %d",
			$db->qstr($context),
			$context_id
		));
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$rows[] = $row['context'];
		}
		
		mysqli_free_result($rs);
		
		return $rows;
	}
	
	static public function getContextLinkCounts($context, $context_ids, $ignore_contexts=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$is_single_result = false;
		
		if(!$context_ids)
			return [];
		
		if(!is_array($context_ids)) {
			$is_single_result = true;
			$context_ids = [$context_ids];
		}
		
		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int');
		
		$sql = sprintf("SELECT count(to_context_id) AS hits, to_context as context, from_context_id ".
			"FROM context_link ".
			"WHERE from_context = %s ".
			"AND from_context_id IN (%s) ".
			"GROUP BY to_context, from_context_id ".
			"ORDER BY hits desc ",
			$db->qstr($context),
			implode(',', $context_ids)
		);
		$rs = $db->QueryReader($sql);
		
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			if(is_array($ignore_contexts) && in_array($row['context'], $ignore_contexts))
				continue;
			
			if(!array_key_exists('from_context_id', $row))
				$objects[$row['from_context_id']] = [];
				
			$objects[$row['from_context_id']][$row['context']] = intval($row['hits']);
		}
		
		mysqli_free_result($rs);
		
		if($is_single_result) {
			return $objects[current($context_ids)] ?? [];
		}
		
		return $objects;
	}
	
	// [TODO] This could replace the worker specific implementations
	static public function setContextOutboundLinks($from_context, $from_context_id, $to_context, $to_context_ids) {
		$links = DAO_ContextLink::getContextLinks($from_context, $from_context_id, $to_context, $to_context_ids);

		if(!is_array($to_context_ids))
			$to_context_ids = array($to_context_ids);
		
		// Remove
		if(is_array($links) && isset($links[$from_context_id]))
		foreach(array_keys($links[$from_context_id]) as $link_id) {
			if(false === array_search($link_id, $to_context_ids))
				DAO_ContextLink::deleteLink($from_context, $from_context_id, $to_context, $link_id);
		}
		
		// Add
		if(is_array($to_context_ids))
		foreach($to_context_ids as $to_context_id) {
			DAO_ContextLink::setLink($from_context, $from_context_id, $to_context, $to_context_id);
		}
	}
	
	static public function getAllContextLinks($from_context, $from_context_id, $sort=false) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT to_context, to_context_id ".
			"FROM context_link ".
			"WHERE (%s = %s AND %s IN (%s)) ".
			($sort ? "ORDER BY to_context, to_context_id " : '') ,
			self::FROM_CONTEXT,
			$db->qstr($from_context),
			self::FROM_CONTEXT_ID,
			$from_context_id
		);
		$rs = $db->QueryReader($sql);
		
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$to_context = $row['to_context'];
			$to_context_id = $row['to_context_id'];
			$object = new Model_ContextLink($row['to_context'], $row['to_context_id']);
			$objects[$to_context.':'.$to_context_id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static public function getContextLinks($from_context, $from_context_ids, $to_context, $limit=0) {
		if(!is_array($from_context_ids))
			$from_context_ids = [$from_context_ids];
		
		$db = DevblocksPlatform::services()->database();
		
		$from_context_ids = DevblocksPlatform::sanitizeArray($from_context_ids, 'integer', array('nonzero','unique'));
		
		if(empty($from_context_ids))
			return [];
		
		if(false == ($to_context_mft = Extension_DevblocksContext::getByAlias($to_context, false)))
			return [];
		
		$sql = sprintf("SELECT from_context, from_context_id, to_context, to_context_id ".
			"FROM context_link ".
			"WHERE %s = %s ".
			"AND (%s = %s AND %s IN (%s)) ".
			($limit ? sprintf('LIMIT %d', $limit) : ''),
			self::TO_CONTEXT,
			$db->qstr($to_context_mft->id),
			self::FROM_CONTEXT,
			$db->qstr($from_context),
			self::FROM_CONTEXT_ID,
			implode(',', $from_context_ids)
		);
		$rs = $db->QueryReader($sql);
		
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$from_context_id = $row['from_context_id'];
			$to_context_id = $row['to_context_id'];
			$object = new Model_ContextLink($row['to_context'], $row['to_context_id']);
			
			if(!array_key_exists($from_context_id, $objects))
				$objects[$from_context_id] = [];
			
			$objects[$from_context_id][$to_context_id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static public function count($from_context, $from_context_id, $ignore_internal=true) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(*) FROM context_link ".
			"WHERE from_context = %s AND from_context_id = %d ".
			"%s",
			$db->qstr($from_context),
			$from_context_id,
			($ignore_internal ? sprintf("AND to_context NOT IN (%s) ", $db->qstr(CerberusContexts::CONTEXT_WORKER)) : "")
		));
	}
	
	static public function intersect($from_context, $from_context_id, $context_strings) {
		$db = DevblocksPlatform::services()->database();
		$wheres = array();
		
		if(!is_array($context_strings) || empty($context_strings))
			return array();
		
		/*
		 * Performance optimization. Use one of two strategies: (1) source context
		 * has lots of links; (2) source context has few links
		 */
		$link_count = DAO_ContextLink::count($from_context, $from_context_id);
		
		// Strategy 1: Lots of links
		// Let the database figure it out since our $context_objects list is smaller
		
		if($link_count > 100) {
			
			$context_objects = array();
		
			if(is_array($context_strings))
			foreach($context_strings as $context_string) {
				$context_data = explode(':', $context_string);
				
				if(!isset($context_objects[$context_data[0]]))
					$context_objects[$context_data[0]] = array();
				
				$context_objects[$context_data[0]][] = $context_data[1];
			}
			
			// Build a query
			foreach($context_objects as $context => $context_ids) {
				if(empty($context) || !is_array($context_ids))
					continue;
				
				// Security time
				$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'integer');
				
				if(empty($context_ids))
					continue;
				
				$wheres[] = sprintf("(to_context = %s AND to_context_id IN (%s))",
					$db->qstr($context),
					implode(',', $context_ids)
				);
			}
			
			// If empty
			if(empty($wheres))
				return array();
			
			$sql = sprintf("SELECT to_context, to_context_id ".
				"FROM context_link ".
				"WHERE from_context = %s AND from_context_id = %d ".
				"AND (%s)",
				$db->qstr($from_context),
				$from_context_id,
				implode(' OR ', $wheres)
			);
			
			$results = $db->GetArrayReader($sql);
			
			$out = array();
			
			foreach($results as $row) {
				$to_context = $row['to_context'];
				$to_context_id = $row['to_context_id'];
				
				$object = new Model_ContextLink($to_context, $to_context_id);
				$out[] = $object;
			}
			
			return $out;
		
		// Strategy 2: Few links
		// Pull all links from source and compare manually to $context_objects
		
		} else {
			$links = DAO_ContextLink::getAllContextLinks($from_context, $from_context_id);
			$out = array();
			
			foreach($links as $key => $link) {
				if(in_array($key, $context_strings))
					$out[$key] = $link;
			}
			
			return $out;
		}
		
		return false;
	}
	
	static public function delete($context, $context_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		$ids = implode(',', $context_ids);
		
		if(empty($ids))
			return;
		
		$sql = sprintf("DELETE FROM context_link WHERE (from_context = %s AND from_context_id IN (%s)) OR (to_context = %s AND to_context_id IN (%s))",
			$db->qstr($context),
			$ids,
			$db->qstr($context),
			$ids
		);
		$db->ExecuteMaster($sql);
	}
	
	static public function deleteLink($src_context, $src_context_id, $dst_context, $dst_context_id, $src_context_meta=null, $dst_context_meta=null) {
		$db = DevblocksPlatform::services()->database();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($ext_src_context = Extension_DevblocksContext::get($src_context)))
			return false;
		
		if(false == ($ext_dst_context = Extension_DevblocksContext::get($dst_context)))
			return false;
		
		if(false == $src_context_meta)
			@$src_context_meta = $ext_src_context->getMeta($src_context_id);
		
		if(false == $dst_context_meta)
			@$dst_context_meta = $ext_dst_context->getMeta($dst_context_id);
		
		// If someone is unlinking a custom fieldset
		if($src_context == Context_CustomFieldset::ID) {
			DAO_CustomFieldset::removeFromContext($src_context_id, $dst_context, $dst_context_id);
			return true;
			
		} elseif($dst_context == Context_CustomFieldset::ID) {
			DAO_CustomFieldset::removeFromContext($dst_context_id, $src_context, $src_context_id);
			return true;
		}
		
		/*
		 * Delete from source side
		 */
		
		$sql = sprintf("DELETE FROM context_link WHERE from_context = %s AND from_context_id = %d AND to_context = %s AND to_context_id = %d",
			$db->qstr($src_context),
			$src_context_id,
			$db->qstr($dst_context),
			$dst_context_id
		);
		$db->ExecuteMaster($sql);

		/*
		 * Delete from destination side
		 */
		
		$sql = sprintf("DELETE FROM context_link WHERE from_context = %s AND from_context_id = %d AND to_context = %s AND to_context_id = %d",
			$db->qstr($dst_context),
			$dst_context_id,
			$db->qstr($src_context),
			$src_context_id
		);
		$db->ExecuteMaster($sql);
		
		/*
		 * Activities
		 */

		// Unfollow?
		if($dst_context == CerberusContexts::CONTEXT_WORKER) {
			if($active_worker && $active_worker->id == $dst_context_id) {
				$entry = array(
					//{{actor}} stopped watching {{target_object}} {{target}}
					'message' => 'activities.watcher.unfollow',
					'variables' => array(
						'target_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
						'target' => $src_context_meta['name'] ?? '',
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d", $src_context, $src_context_id),
						)
				);
				CerberusContexts::logActivity('watcher.unfollow', $src_context, $src_context_id, $entry);
			} else {
				$watcher_worker = DAO_Worker::get($dst_context_id);
				
				$entry = array(
					//{{actor}} removed {{watcher}} as a watcher from {{target_object}} {{target}}
					'message' => 'activities.watcher.unassigned',
					'variables' => array(
						'watcher' => $watcher_worker->getName(),
						'target_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
						'target' => $src_context_meta['name'] ?? '',
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d", $src_context, $src_context_id),
						'watcher' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_WORKER, $watcher_worker->id, DevblocksPlatform::strToPermalink($watcher_worker->getName())),
						)
				);
				CerberusContexts::logActivity('watcher.unassigned', $src_context, $src_context_id, $entry);
			}
		
		// Disconnect
		} else {
			$entry = array(
				//{{actor}} disconnected {{target_object}} {{target}} from {{link_object}} {{link}}
				'message' => 'activities.connection.unlink',
				'variables' => array(
					'target_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
					'target' => $src_context_meta['name'] ?? '',
					'link_object' => mb_convert_case($ext_dst_context->manifest->name, MB_CASE_LOWER),
					'link' => $dst_context_meta['name'] ?? '',
					),
				'urls' => array(
					'target' => sprintf("ctx://%s:%d", $src_context, $src_context_id),
					'link' => sprintf("ctx://%s:%d", $dst_context, $dst_context_id),
					)
			);
			CerberusContexts::logActivity('connection.unlink', $src_context, $src_context_id, $entry);
			
			$entry = array(
				//{{actor}} disconnected {{target_object}} {{target}} from {{link_object}} {{link}}
				'message' => 'activities.connection.unlink',
				'variables' => array(
					'target_object' => mb_convert_case($ext_dst_context->manifest->name, MB_CASE_LOWER),
					'target' => $dst_context_meta['name'] ?? '',
					'link_object' => mb_convert_case($ext_src_context->manifest->name, MB_CASE_LOWER),
					'link' => $src_context_meta['name'] ?? '',
					),
				'urls' => array(
					'target' => sprintf("ctx://%s:%d", $dst_context, $dst_context_id),
					'link' => sprintf("ctx://%s:%d", $src_context, $src_context_id),
					)
			);
			CerberusContexts::logActivity('connection.unlink', $dst_context, $dst_context_id, $entry);
		}
		
		return true;
	}
};

class Model_ContextLink {
	public $context = '';
	public $context_id = 0;
	
	function __construct($context, $context_id) {
		$this->context = $context;
		$this->context_id = $context_id;
	}
};