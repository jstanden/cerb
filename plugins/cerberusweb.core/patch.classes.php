<?php
class ChCorePatchContainer extends DevblocksPatchContainerExtension {
	const REV_0 = 0;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */
		$revisions = array(
			REV_0, // 4.0 Beta
		);
		
		if(is_array($revisions))
		foreach($revisions as $rev) {
			$this->registerPatch(new CerberusPatch('cerberusweb.core',$rev,$this));
		}
	}

	function runRevision($rev) {
		switch($rev) {
			
			// 4.0 Beta Clean
			case REV_0:
				self::_initDatabase();
				break;
				
		}
		
		return TRUE;
	}
	
	private static function _initDatabase() {
		$db = DevblocksPlatform::getDatabaseService();
		$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 
		
		// ***** Application
		
		$tables['ticket'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			mask C(16) DEFAULT '' NOTNULL, 
			subject C(128)  DEFAULT '' NOTNULL, 
			bitflags I2 DEFAULT 0,
			created_date I4,
			updated_date I4,
			status C(1) DEFAULT '' NOTNULL, 
			priority I1 DEFAULT 0 NOTNULL, 
			mailbox_id I4 NOTNULL, 
			first_wrote_address_id I4 NOTNULL DEFAULT 0,
			last_wrote_address_id I4 NOTNULL DEFAULT 0,
			spam_score F NOTNULL DEFAULT 0,
			spam_training C(1) NOTNULL DEFAULT ''
		";
		
		$tables['message'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			ticket_id I4 DEFAULT 0 NOTNULL,
			is_admin I1 DEFAULT 0 NOTNULL,
			message_type C(1),
			created_date I4,
			address_id I4,
			message_id C(255),
			headers B DEFAULT '' NOTNULL,
			content B DEFAULT '' NOTNULL
		";
		
		$tables['attachment'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			message_id I4 DEFAULT 0 NOTNULL,
			display_name C(128) DEFAULT '' NOTNULL,
			filepath C(255) DEFAULT '' NOTNULL
		";
		
		$tables['team'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			name C(32) DEFAULT '' NOTNULL
		";
		
		$tables['mailbox'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			name C(32) DEFAULT '' NOTNULL,
			reply_address_id I4 DEFAULT 0 NOTNULL,
			display_name C(32) DEFAULT '',
			close_autoresponse B,
			new_autoresponse B
		";
		
		$tables['dashboard'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			name C(32) DEFAULT '' NOTNULL,
			agent_id I4 NOTNULL
		";
		
		$tables['dashboard_view'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			dashboard_id I4 DEFAULT 0 NOTNULL,
			type C(1) DEFAULT 'D',
			name C(32) DEFAULT '' NOTNULL,
			view_columns B,
			sort_by C(32) DEFAULT '' NOTNULL,
			sort_asc I1 DEFAULT 1 NOTNULL,
			num_rows I2 DEFAULT 10 NOTNULL,
			page I2 DEFAULT 0 NOTNULL,
			params B
		";
		
		$tables['dashboard_view_action'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			dashboard_view_id I4 DEFAULT 0 NOTNULL,
			name C(64) DEFAULT '' NOTNULL,
			worker_id I4 NOTNULL,
			params B
		";
		
		$tables['address'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			email C(255) DEFAULT '' NOTNULL,
			personal C(255) DEFAULT '',
			bitflags I2 DEFAULT 0
		";
		
		$tables['mail_rule'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			criteria B DEFAULT '' NOTNULL,
			sequence C(4) DEFAULT '',
			strictness C(4) DEFAULT ''
		";
		
		$tables['mail_routing'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			pattern C(255) DEFAULT '' NOTNULL,
			mailbox_id I4 DEFAULT 0 NOTNULL,
			pos I2 DEFAULT 0 NOT NULL
		";
		
		$tables['requester'] = "
			address_id I4 DEFAULT 0 NOTNULL PRIMARY,
			ticket_id I4 DEFAULT 0 NOTNULL PRIMARY
		";
		
		$tables['tag'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			name C(32) DEFAULT '' NOTNULL
		";
		
		$tables['tag_term'] = "
			tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
			term C(128) DEFAULT '' NOTNULL PRIMARY
		";
		
		$tables['tag_to_ticket'] ="
			tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
			ticket_id I4 DEFAULT 0 NOTNULL PRIMARY
		";
		
		$tables['assign_to_ticket'] = "
			agent_id I4 DEFAULT 0 NOTNULL PRIMARY,
			ticket_id I4 DEFAULT 0 NOTNULL PRIMARY,
			is_flag I1 DEFAULT 0 NOTNULL
		";
		
		$tables['mailbox_to_team'] = "
			mailbox_id I4 DEFAULT 0 NOTNULL PRIMARY,
			team_id I4 DEFAULT 0 NOTNULL PRIMARY,
			is_routed I1 DEFAULT 0 NOTNULL
		";
		
		$tables['worker_to_team'] = "
			agent_id I4 DEFAULT 0 NOTNULL PRIMARY,
			team_id I4 DEFAULT 0 NOTNULL PRIMARY
		";
		
		$tables['favorite_tag_to_worker'] = "
			tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
			agent_id I4 DEFAULT 0 NOTNULL PRIMARY
		";
		
		$tables['favorite_worker_to_worker'] = "
			worker_id I4 DEFAULT 0 NOTNULL PRIMARY,
			agent_id I4 DEFAULT 0 NOTNULL PRIMARY
		";
		
		$tables['pop3_account'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			nickname C(128) DEFAULT '' NOTNULL,
			host C(128) DEFAULT '' NOTNULL,
			username C(128) DEFAULT '' NOTNULL,
			password C(128) DEFAULT '' NOTNULL
		";
		
		$tables['kb_category'] = "
			id I2 DEFAULT 0 NOTNULL PRIMARY,
			name C(128) DEFAULT '' NOTNULL,
			parent_id I2 DEFAULT 0 NOTNULL
		";
		
		$tables['kb_to_category'] = "
			kb_id I4 DEFAULT 0 NOTNULL PRIMARY,
			category_id I2 DEFAULT 0 NOTNULL PRIMARY
		";
		
		$tables['kb'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			title C(128) DEFAULT '' NOTNULL,
			type C(1) DEFAULT 'A' NOTNULL
		";
		
		$tables['kb_content'] = "
			kb_id I4 DEFAULT 0 NOTNULL PRIMARY,
			content B DEFAULT '' NOTNULL
		";
		
		$tables['tag_to_kb'] ="
			tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
			kb_id I4 DEFAULT 0 NOTNULL PRIMARY
		";
		
		$tables['worker'] ="
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			first_name C(32) DEFAULT '',
			last_name C(64) DEFAULT '',
			title C(64) DEFAULT '',
			email C(128) DEFAULT '',
			pass C(32) DEFAULT '',
			is_superuser I1 DEFAULT 0 NOTNULL,
			last_activity_date I4
		";
		
		$tables['bayes_words'] = "
			id I4 DEFAULT 0 NOTNULL PRIMARY,
			word C(64) DEFAULT '' NOTNULL,
			spam I4 DEFAULT 0,
			nonspam I4 DEFAULT 0
		";
		
		$tables['bayes_stats'] = "
			spam I4 DEFAULT 0,
			nonspam I4 DEFAULT 0
		";
		
		// [JAS]: [TODO] Platform table?
		$tables['setting'] = "
			setting C(32) DEFAULT '' NOTNULL PRIMARY,
			value C(255) DEFAULT '' NOTNULL
		";
		
		if(is_array($tables))
		foreach($tables as $table => $flds) {
			$sql = $datadict->ChangeTableSQL($table,$flds);
//			print_r($sql);
			// [TODO] Buffer up success and fail messages?  Patcher!
			if(!$datadict->ExecuteSQLArray($sql,false)) {
				return FALSE;
			}
//			echo "<HR>";
		}				
	}
};

?>