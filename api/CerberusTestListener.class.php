<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
require_once 'PHPUnit/Framework.php';
 
class CerberusTestListener
implements PHPUnit_Framework_TestListener
{
  public function
  addError(PHPUnit_Framework_Test $test,
           Exception $e,
           $time)
  {
    printf(
      "<li>Error while running test '%s'.",
      $test->getName()
    );
  }
 
  public function
  addFailure(PHPUnit_Framework_Test $test,
             PHPUnit_Framework_AssertionFailedError $e,
             $time)
  {
    printf(
      "<li style='color:red;font-weight:bold;'>Test '%s' failed. (%s)",
      $test->getName(),
      htmlspecialchars($e->getMessage())
    );
  }
 
  public function
  addIncompleteTest(PHPUnit_Framework_Test $test,
                    Exception $e,
                    $time)
  {
    printf(
      "<li>Test '%s' is incomplete.",
      $test->getName()
    );
  }
 
  public function
  addSkippedTest(PHPUnit_Framework_Test $test,
                 Exception $e,
                 $time)
  {
    printf(
      "<li>Test '%s' has been skipped.",
      $test->getName()
    );
  }
 
  public function startTest(PHPUnit_Framework_Test $test)
  {
//    printf(
//      "Test '%s' started.<br>",
//      $test->getName()
//    );
  }
 
  public function endTest(PHPUnit_Framework_Test $test, $time)
  {
//    printf(
//      "Test '%s' ended.<br>",
//      $test->getName()
//    );
  }
 
  public function
  startTestSuite(PHPUnit_Framework_TestSuite $suite)
  {
    printf(
      "TestSuite '%s' running...<ul style='margin:0px'>",
      $suite->getName()
    );
  }
 
  public function
  endTestSuite(PHPUnit_Framework_TestSuite $suite)
  {
//    printf(
//      "</ul>TestSuite '%s' ended.<br><br>",
//      $suite->getName()
//    );

  	// [JAS]: [TODO] This could be done better, it's just a start.
  	$failed = false;
  	$num_cases = 0;
  	for($x=0;$x<$suite->testCount();$x++) { 
  		$test = $suite->testAt($x); /* @var $test PHPUnit_Framework_TestCase */
  		if(!($test instanceOf PHPUnit_Framework_TestCase)) continue;
  		if($test->hasFailed()) {
  			$failed = true;
  			break;
  		}
  		$num_cases++;
  	}
  	if(!$failed && $num_cases) printf("<li style='color:green;'>All test cases passed!");
	printf("</ul>");
  }
}
?>
