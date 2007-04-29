<?php
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
