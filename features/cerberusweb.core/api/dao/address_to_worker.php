<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
	static function getAll($nocache=false, $with_disabled=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($results = $cache->load(self::_CACHE_ALL))) {
			$results = self::getWhere(
				null,
				null,
				null,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($results))
				return false;
			
			if(!empty($results))
				$cache->save($results, self::_CACHE_ALL);
		}
		
		if(!$with_disabled) {
			$workers = DAO_Worker::getAll();
			
			$results = array_filter($results, function($address) use ($workers) {
				@$worker = $workers[$address->worker_id];
				return !(empty($worker) || $worker->is_disabled);
			});
		}
		
		return $results;
	}
};