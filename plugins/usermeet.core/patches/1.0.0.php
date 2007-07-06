<?php
		$db = DevblocksPlatform::getDatabaseService();
		$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ 

		$tables = array();
		
		// ***** Application

		$tables['community_tool'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			code C(8) DEFAULT '' NOTNULL,
			community_id I4 DEFAULT 0 NOTNULL,
			extension_id C(128) DEFAULT '' NOTNULL
		";
		
		$currentTables = $db->MetaTables('TABLE', false);

		if(is_array($tables))
		foreach($tables as $table => $flds) {
			if(false === array_search($table,$currentTables)) {
				$sql = $datadict->CreateTableSQL($table,$flds);
			//			print_r($sql);
				// [TODO] Buffer up success and fail messages?  Patcher!
				if(!$datadict->ExecuteSQLArray($sql,false)) {
					return FALSE;
				}
			}
//			echo "<HR>";
		}				

?>