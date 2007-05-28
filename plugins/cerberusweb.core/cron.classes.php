<?php
/*
 * [JAS]: [TODO] This really belongs in CORE, though it was a good
 * plugin exercise.
 */ 
class Pop3Cron extends CerberusCronPageExtension {
	function run() {
		$accounts = DAO_Mail::getPop3Accounts(); /* @var $accounts CerberusPop3Account[] */
		
		foreach ($accounts as $account) { /* @var $account CerberusPop3Account */
			echo ('Account being parsed is ' . $account->nickname . '<br>');
			
			$mail = DevblocksPlatform::getMailService();
			
			$msgs = $mail->getMessages($account->host, $account->port, $account->protocol, $account->username, $account->password);
			
			if(is_array($msgs))
			foreach($msgs as $msg) {
				CerberusParser::parseMessage($msg);
			}
		}
	}
};

?>