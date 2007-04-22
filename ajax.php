<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

@$uri = DevblocksPlatform::importGPC($_REQUEST['c']); // extension
@$listener = DevblocksPlatform::importGPC($_REQUEST['a']); // listener

// [JAS]: [TODO] Is an explicit init() really required?
DevblocksPlatform::init();

$session = DevblocksPlatform::getSessionService();
$settings = CerberusSettings::getInstance();

$tpl = DevblocksPlatform::getTemplateService();
$tpl->assign('translate', DevblocksPlatform::getTranslationService());
$tpl->assign('session', $_SESSION);
$tpl->assign('visit', $session->getVisit());
$tpl->assign('settings', $settings);

$request = new DevblocksHttpRequest(array($uri,$listener));
DevblocksPlatform::processRequest($request,true);

exit;
?>