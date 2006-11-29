<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/ump/UserMeetPlatform.class.php');
require(UM_PATH . '/api/CerberusApplication.class.php');
//
//UserMeetPlatform::init();

//$email = new UserMeetEmailObject(array("jeff@webgroupmedia.com"), "hildy@webgroupmedia.com", "test email from usermeet", "this email was sent through a pear implementation embedded in usermeet.");

//print_r($email);

//$email->send();

//echo "random body text";
//
//if (extension_loaded("imap")) {
//	$mailbox = imap_open("{cylon.webgroupmedia.com:110/service=pop3}INBOX","pop1","poptester")
//		or die("Failed with error: ".imap_last_error());
//	$check = imap_check($mailbox);
//	
//	for ($i=1; $i<=$check->Nmsgs; $i++) {
//		$header = preg_replace("/\n/","<br>",preg_replace("/\r\n/","<br>",imap_fetchheader($mailbox, $i)));
//		$body = imap_body($mailbox, $i);
//		echo "Message with id ".$i." follows:<br><br>";
//		echo $header."<br><br>".$body."<br><br><hr><br>";
//	}
//	
//	imap_close($mailbox);	
//} else {
//	echo "IMAP extension not loaded!  Unable to download email!";
//}


$cfg = new UserMeetEmailConfig("cylon.webgroupmedia.com","110","pop3","pop1","poptester");
$msgs = UserMeetEmailManager::getMail($cfg);

if(is_array($msgs))
foreach($msgs as $msg) {
	CerberusParser::parseMessage($msg);
}

//print_r($msgs);

?>