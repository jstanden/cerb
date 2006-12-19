<?php
class Pop3Module extends CerberusCronModuleExtension {
	function run() {
		$accounts = CerberusMailDAO::getPop3Accounts(); /* @var $accounts CerberusPop3Account[] */
		
		foreach ($accounts as $account) { /* @var $account CerberusPop3Account */
			echo ('Account being parsed is ' . $account->nickname . '<br>');
			$cfg = new UserMeetEmailConfig($account->host,'110','pop3',$account->username,$account->password);
			$msgs = UserMeetEmailManager::getMail($cfg);
			
			if(is_array($msgs))
			foreach($msgs as $msg) {
				CerberusParser::parseMessage($msg);
			}
		}
	}
};

?>