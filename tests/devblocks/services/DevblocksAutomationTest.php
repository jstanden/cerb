<?php
use PHPUnit\Framework\TestCase;

class DevblocksAutomationTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testOutcomeSimpleSingleCondition() {
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		
		$automation_script = <<< EOD
start:
  decision:
    outcome/support:
      if@bool:
        {% if 'support@cerb.example' in email_recipients %}yes{% else %}no{% endif %}
      then:
        return:
          group: Support
EOD;
		
		$initial_state = [
			'email_recipients' => [
				'support@cerb.example',
				'bcc@customer.example',
			],
		];
		
		$automation = new Model_Automation();
		$automation->extension_id = 'cerb.trigger.mail.route';
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertInstanceOf('DevblocksDictionaryDelegate', $automation_result);
		$this->assertEquals('return', $automation_result->get('__exit'));
		$this->assertEquals(['group' => 'Support'], $automation_result->getKeyPath('__return', []));
	}
	
	function testOutcomeBlankCondition() {
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		
		$automation_script = <<< EOD
start:
  decision:
    outcome/support:
      then:
        return:
          group: Dispatch
EOD;
		
		$initial_state = [
			'email_recipients' => [
				'support@cerb.example',
			],
		];
		
		$automation = new Model_Automation();
		$automation->extension_id = 'cerb.trigger.mail.route';
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertInstanceOf('DevblocksDictionaryDelegate', $automation_result);
		$this->assertEquals('return', $automation_result->get('__exit'));
		$this->assertEquals(['group' => 'Dispatch'], $automation_result->getKeyPath('__return', []));
	}
	
	function testOutcomeAnyConditionsWildcard() {
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		
		$automation_script = <<< EOD
start:
  decision:
    outcome/support:
      if@bool:
        {% if array_matches(email_recipients, ['team@cerb.example','support@*','help@*']) %}yes{% endif %}
      then:
        return:
          group: Support
EOD;
		
		$initial_state = [
			'email_recipients' => [
				'support@cerb.example',
				'bcc@customer.example',
			],
		];
		
		$automation = new Model_Automation();
		$automation->extension_id = 'cerb.trigger.mail.route';
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertInstanceOf('DevblocksDictionaryDelegate', $automation_result);
		$this->assertEquals('return', $automation_result->get('__exit'));
		$this->assertEquals(['group' => 'Support'], $automation_result->getKeyPath('__return', []));
	}
	
	function testLoops() {
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		
		// Range
		$automation_script = <<< EOD
start:
  set/init:
    counter@json: ""
  repeat:
    each@csv: {{range(1,5)|join(',')}}
    as: i
    do:
      set:
        counter: {{counter~i~","}}
  return:
    counter@key: counter
EOD;
		
		$initial_state = [];
		
		$automation = new Model_Automation();
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertEquals('1,2,3,4,5,', $automation_result->get('counter'));
		
		// Range ... syntax
		$automation_script = <<< EOD
start:
  set/init:
    counter@json: ""
  repeat:
    each@csv: {{range(1,5)|join(',')}}
    as: i
    do:
      set:
        counter: {{counter~i~","}}
  return:
    counter@key: counter
EOD;
		
		$initial_state = [];
		
		$automation = new Model_Automation();
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertEquals('1,2,3,4,5,', $automation_result->get('counter'));
		
		// Range w/ step
		$automation_script = <<< EOD
start:
  set/init:
    counter@json: ""
  repeat:
    each@csv: {{range(1,5,2)|join(',')}}
    as: i
    do:
      set:
        counter: {{counter~i~","}}
  return:
    counter@key: counter
EOD;
		
		$initial_state = [];
		
		$automation = new Model_Automation();
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertEquals('1,3,5,', $automation_result->get('counter'));
		
		// JSON array
		$automation_script = <<< EOD
start:
  set/init:
    counter@json: ""
  repeat:
    each@json: [1,2,3]
    as: i
    do:
      set:
        counter: {{counter~i~","}}
  return:
    counter@key: counter
EOD;
		
		$initial_state = [];
		
		$automation = new Model_Automation();
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertEquals('1,2,3,', $automation_result->get('counter'));
	}
	
	function testActionAppend() {
		$automator = DevblocksPlatform::services()->automation();
		
		$automation_script = <<< EOD
start:
  var.push/0:
    inputs:
      key: example:deep:stack
      value: 0
  var.push/1:
    inputs:
      key: example:deep:stack
      value: 1
  var.push/2:
    inputs:
      key: example:deep:stack
      value: 2
  return:
    stack@key: example:deep:stack
EOD;
		
		$initial_state = [];
		
		$automation = new Model_Automation();
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertEquals([0,1,2], $automation_result->getKeyPath('__return:stack', null, ':'));
	}
	
	function testDuplicateSibling() {
		$automator = DevblocksPlatform::services()->automation();
		
		$automation_script = <<< EOD
start:
  await:
    form:
      title: Input
  await:
    form:
      title: Output
EOD;
		
		$initial_state = [];
		
		$automation = new Model_Automation();
		$automation->script = $automation_script;
		
		$automation_result = $automator->executeScript($automation, $initial_state, $error);
		
		$this->assertFalse($automation_result);
		$this->assertTrue(!empty($error));
	}
}
