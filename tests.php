<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

require_once 'PHPUnit/Framework.php';
require_once 'api/CerberusTestListener.class.php';

$suite = new PHPUnit_Framework_TestSuite('Cerberus Helpdesk');

require_once 'api/Application.tests.php';
$suite->addTestSuite('ApplicationTest');
$suite->addTestSuite('CerberusBayesTest');
$suite->addTestSuite('CerberusParserTest');

$result = new PHPUnit_Framework_TestResult;
$result->addListener(new CerberusTestListener);
 
$suite->run($result);
?>