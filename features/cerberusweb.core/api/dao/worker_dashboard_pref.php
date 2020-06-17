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

class DAO_WorkerDashboardPref {
	static function get($tab_id, Model_Worker $worker) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT * FROM worker_dashboard_pref ".
			"WHERE tab_context = %s ".
			"AND tab_context_id = %d ".
			"AND worker_id = %d ".
			"ORDER BY widget_id",
			$db->qstr(CerberusContexts::CONTEXT_WORKSPACE_TAB),
			$tab_id,
			$worker->id
		);
		
		$results = $db->GetArrayReader($sql);
		
		return $results;
	}
	
	static function set($tab_id, array $prefs, Model_Worker $worker) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM worker_dashboard_pref WHERE tab_context = %s AND tab_context_id = %d AND worker_id = %d",
			$db->qstr(CerberusContexts::CONTEXT_WORKSPACE_TAB),
			$tab_id,
			$worker->id
		);
		$db->ExecuteMaster($sql);
		
		foreach($prefs as $pref_key => $pref_value) {
			if(is_array($pref_value))
				$pref_value = implode(',', $pref_value);

			if(0 == strlen($pref_value))
				continue;
			
			$sql = sprintf("INSERT INTO worker_dashboard_pref (tab_context, tab_context_id, worker_id, widget_id, pref_key, pref_value) ".
				"VALUES (%s, %d, %d, %d, %s, %s)",
				$db->qstr(CerberusContexts::CONTEXT_WORKSPACE_TAB),
				$tab_id,
				$worker->id,
				0,
				$db->qstr($pref_key),
				$db->qstr($pref_value)
			);
			$db->ExecuteMaster($sql);
		}
		
		return TRUE;
	}
}