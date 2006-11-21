<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/ump/UserMeetPlatform.class.php');
require(UM_PATH . '/api/CerberusApplication.class.php');

UserMeetPlatform::init();

$smarty = UserMeetTemplateManager::getInstance();
$session = UserMeetSessionManager::getInstance(); /* @var $session UserMeetSessionManager */
$translate = UserMeetTranslationManager::getInstance();

// [JAS]: Security check
$visit = $session->getVisit();
if(empty($visit))
	exit;

//sleep(3); // [JAS]: For testing 'please wait... loading' images

// [JAS]: Handle component actions
@$c = (isset($_REQUEST['c']) ? $_REQUEST['c'] : null);
@$a = (isset($_REQUEST['a']) ? $_REQUEST['a'] : null);

if(!empty($c) && !empty($a)) {
	// [JAS]: [TODO] Split $c and look for an ID and an instance
	$mfTarget = UserMeetPlatform::getExtension($c);
	$target = $mfTarget->createInstance();
	
	if(method_exists($target,$a)) {
		call_user_method($a,$target); // [JAS]: [TODO] Action Args
	}
}

?>