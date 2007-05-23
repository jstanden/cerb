<?php
class ChFeedsPatchContainer extends DevblocksPatchContainerExtension {
    const ID = 'rss.patches';
	const REV_0 = 0;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */
		$revisions = array(
			self::REV_0, // 4.0 Beta
		);
		
		if(is_array($revisions))
		foreach($revisions as $rev) {
			$this->registerPatch(new CerberusPatch('cerberusweb.rss',$rev,$this));
		}
	}

	function runRevision($rev) {
		$result = TRUE;
		
		switch($rev) {
			// 4.0 Beta Clean
			case self::REV_0:
				$result = self::_initDatabase();
				break;
		}
		
		return $result;
	}
	
	private static function _initDatabase() {
		$db = DevblocksPlatform::getDatabaseService();
		$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 
		
		$tables = array();
		
		// Feeds
	    $tables['feed'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			title C(128) DEFAULT '',
			code C(8) DEFAULT '',
			worker_id I4 DEFAULT 0,
			params B DEFAULT ''
		";

		$tables['feed_item'] = "
			id I8 DEFAULT 0 NOTNULL PRIMARY,
			feed_id I4 DEFAULT 0,
			event_id C(128) DEFAULT '',
			created I8 DEFAULT 0,
			params B DEFAULT ''
		";
		
		// [TODO] This could be part of the patcher
		$currentTables = $db->MetaTables('TABLE', false);
		
		if(is_array($tables))
		foreach($tables as $table => $flds) {
			if(false === array_search($table,$currentTables)) {
				$sql = $datadict->CreateTableSQL($table,$flds);
				// [TODO] Need verify step
				// [TODO] Buffer up success and fail messages?  Patcher!
				if(!$datadict->ExecuteSQLArray($sql,false)) {
					echo '[' . $table . '] ' . $db->ErrorMsg();
					exit;
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
};

?>
