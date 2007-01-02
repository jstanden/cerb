<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . '/libs/devblocks/Devblocks.class.php');
require(DEVBLOCKS_PATH . '/api/CerberusApplication.class.php');

DevblocksPlatform::init();

$smarty = DevblocksPlatform::getTemplateService();
$session = DevblocksPlatform::getSessionService();
$translate = DevblocksPlatform::getTranslationService();

$tpl = DevblocksPlatform::getTemplateService();
$tpl->assign('translate', $translate);

// [JAS]: Security check
$visit = $session->getVisit();
if(empty($visit))
	exit;

//sleep(3); // [JAS]: For testing 'please wait... loading' images

// [JAS]: Handle component actions
@$c = (isset($_REQUEST['c']) ? $_REQUEST['c'] : null);
@$a = (isset($_REQUEST['a']) ? $_REQUEST['a'] : null);

$tpl->assign('c',$c);
//$tpl->assign('a',$a);

if(!empty($c) && !empty($a)) {
	// [JAS]: [TODO] Split $c and look for an ID and an instance
	$mfTarget = DevblocksPlatform::getExtension($c);
	$target = $mfTarget->createInstance();
	
	if(method_exists($target,$a)) {
		call_user_method($a,$target); // [JAS]: [TODO] Action Args
	}
}

?>