<?php
use PHPUnit\Framework\TestCase;

class DevblocksKataTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testKataTabIndents() {
		$error = null;
		
		$kata_string = "object:\n\tstring: This is some text";
		
		$kata = DevblocksPlatform::services()->kata()->parse($kata_string, $error);
		
		$this->assertFalse($kata);
	}
	
	function testKataText() {
		$error = null;
		
		$kata_string = <<< EOD
object:
  string@text:
    This is a paragraph
    on multiple lines
    with no blank at the end
EOD;
		
		$kata = DevblocksPlatform::services()->kata()->parse($kata_string, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($kata, $error);
		$expected = "This is a paragraph\non multiple lines\nwith no blank at the end";
		$this->assertEquals($expected, $actual['object']['string']);
		
		$kata_string = <<< EOD
object:
  string@text:
    This is a paragraph
    on multiple lines
    with one blank at the end
    
EOD;
		
		$kata = DevblocksPlatform::services()->kata()->parse($kata_string, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($kata, $error);
		$expected = "This is a paragraph\non multiple lines\nwith one blank at the end\n";
		$this->assertEquals($expected, $actual['object']['string']);
		
		$kata_string = <<< EOD
object:
  string@text:
    This is a paragraph
    on multiple lines
    with two blanks at the end
    
    
EOD;
		
		$kata = DevblocksPlatform::services()->kata()->parse($kata_string, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($kata, $error);
		$expected = "This is a paragraph\non multiple lines\nwith two blanks at the end\n\n";
		$this->assertEquals($expected, $actual['object']['string']);
		
		$kata_string = <<< EOD
object:
  string@text:
    This is a paragraph
    
    on multiple lines
    
    with blank lines between
EOD;
		
		$kata = DevblocksPlatform::services()->kata()->parse($kata_string, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($kata, $error);
		$expected = "This is a paragraph\n\non multiple lines\n\nwith blank lines between";
		$this->assertEquals($expected, $actual['object']['string']);
	}
	
	function testKataRaw() {
		$kata_string = <<< EOD
object:
  template@text,raw:
    {{name}} is {{age}}
EOD;
		
		$error = null;
		
		$kata = DevblocksPlatform::services()->kata()->parse($kata_string, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($kata, $error);
		
		$expected = '{{name}} is {{age}}';
		
		$this->assertEquals($expected, $actual['object']['template']);
	}
	
	function testKataComments() {
		$kata = <<< EOD
#event/default:
#  actions:
#    return:
#      used: default
#
event/example:
  actions:
    return:
      used: example
EOD;
		
		$error = null;
		
		$actual = DevblocksPlatform::services()->kata()->parse($kata, $error);
		
		$expected = [
			'event/example' => [
				'actions' => [
					'return' => [
						'used' => 'example',
					]
				]
			]
		];
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataParseBlankLines() {
		$kata = <<< EOD
event/default:
  actions:
    return:
      used: default

event/example:
  actions:
    return:
      used: example
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		
		$this->assertTrue(is_array($tree));
		
		// Test child count when there is a blank line
		$expected = 2;
		$actual = count($tree);
		$this->assertEquals($expected, $actual);
	}
	
	function testKataParseBlankAfterTextBlock() {
		$kata = <<< EOD
object1:
  params:
    days@list:
      day
      week
      month
      year

object2:
  params:
    context: group
    single@bool: no
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$expected = [
			'object1' => [
				'params' => [
					'days' => ['day', 'week', 'month', 'year'],
				],
			],
			'object2' => [
				'params' => [
					'context' => 'group',
					'single' => false,
				],
			],
		];
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataParseBlankAfterKey() {
		$kata = <<< EOD
object1:
  params:
    key:

object2:
  params:
    context: group
    single@bool: no
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$expected = [
			'object1' => [
				'params' => [
					'key' => [],
				],
			],
			'object2' => [
				'params' => [
					'context' => 'group',
					'single' => false,
				],
			],
		];
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataParseBlankSiblings() {
		$kata = <<< EOD
records:
  org:
  task:
  ticket:

example:
  return:
    used: example
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		
		$this->assertTrue(is_array($tree));
		
		$record_types = $tree['records'];
		
		$expected = ['org', 'task', 'ticket'];
		$this->assertEquals($expected, array_keys($record_types));
	}
	
	function testKataParseCsv() {
		$kata = <<< EOD
colors@csv: red,green, blue
EOD;
		
		$error = null;
		
		$expected = ['colors' => ['red','green','blue']];
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataParseLists() {
		$kata = <<< EOD
colors@list:
  red
  green
  blue
EOD;
		
		$error = null;
		
		$expected = ['colors' => ['red','green','blue']];
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataReferences() {
		$kata = <<< EOD
event/start:
  decision:
    if: {{"yes"}}
    outcome/yes@ref: outcome_yes
    outcome/no@ref: outcome_no

&outcome_yes:
  is: yes
  then:
    return:
      success@bool: yes

&outcome_no:
  is: no
  then:
    return:
      success@bool: no
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$this->assertTrue(is_array($actual));
		$this->assertArrayHasKey('success', $actual['event/start']['decision']['outcome/yes']['then']['return'] ?? []);
	}
	
	function testKataReferencesWithMergedAnnotations() {
		$kata = <<< EOD
picklist:
  options@ref,list: colors

&colors@text:
  red
  green
  blue
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$this->assertTrue(is_array($actual));
		$this->assertContains('green', $actual['picklist']['options'] ?? []);
	}
	
	function testKataDeepReferences() {
		$kata = <<< EOD
picklist:
  options@ref: options:colors

&options:
  colors@list:
    red
    green
    blue
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$this->assertTrue(is_array($actual));
		$this->assertContains('green', $actual['picklist']['options'] ?? []);
	}
	
	function testKataNestedDeepReferences() {
		$kata = <<< EOD
picklist:
  options@ref: options:colors

&options:
  colors@ref: colors
  
&colors@list:
  red
  green
  blue
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		$actual = DevblocksPlatform::services()->kata()->formatTree($tree);
		
		$this->assertTrue(is_array($actual));
		$this->assertContains('green', $actual['picklist']['options'] ?? []);
	}
	
	function testKataEmit() {
		$data = [
			'object' => [
				'string' => 'Cerb',
				'int' => 2020,
				'float' => 3.1415,
				'list_int' => [1, 2, 3],
				'list_string' => ['red', 'green', 'blue'],
				'text' => "This has\nlinefeeds in it\nso we should see\na text block",
				'nested_object' => [
					'string' => 'Cerb',
					'int' => 2020,
					'float' => 3.1415,
					'list_int' => [1, 2, 3],
					'list_string' => ['red', 'green', 'blue'],
					'text' => "This has\nlinefeeds in it\nso we should see\na text block",
					'more_nested' => [
						'string' => 'Cerb',
					]
				]
			],
		];
		
		$actual = DevblocksPlatform::services()->kata()->emit($data);
		
		$expected = <<< EOD
object:
  string: Cerb
  int@int: 2020
  float: 3.1415
  list_int@list:
    1
    2
    3
  list_string@list:
    red
    green
    blue
  text@text:
    This has
    linefeeds in it
    so we should see
    a text block
  nested_object:
    string: Cerb
    int@int: 2020
    float: 3.1415
    list_int@list:
      1
      2
      3
    list_string@list:
      red
      green
      blue
    text@text:
      This has
      linefeeds in it
      so we should see
      a text block
    more_nested:
      string: Cerb
EOD;
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataEmitTextBlockLeadingWhitespace() {
		$data = [
    	'record.update' => [
				'inputs' => [
					'fields' => [
						'name' => 'automation.example.script',
						'script' => "start:\n  return:\n    output: Testing!",
					],
				],
			],
    ];
		
		$actual = DevblocksPlatform::services()->kata()->emit($data);
		
		$expected = <<< EOD
    record.update:
      inputs:
        fields:
          name: automation.example.script
          script@text:
            start:
              return:
                output: Testing!
    EOD;
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataEmitEmptyKey() {
		$data = [
			'blank' => '',
		];
		
		$actual = DevblocksPlatform::services()->kata()->emit($data);
		
		$expected = <<< EOD
    blank@text:
    EOD;
		
		$this->assertEquals($expected, $actual);
	}
	
	function testKataDuplicateSiblingWithDiffAnnotations() {
		$kata = <<< EOD
allow/rule@text: ok
allow/rule: ok
EOD;
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($kata, $error);
		
		$this->assertFalse($tree);
		$this->assertNotEmpty($error);
	}
	
	function testKataPreserveComments() {
		$expected_kata = <<< EOD
      # Branch A
      branch/a:
        name: Branch A
      # Branch B
      branch/b:
        name: Branch B
      # Branch C
      branch/c:
        name: Branch C
      EOD;
		
		$error = null;
		$symbol_meta = [];
		
		$kata = DevblocksPlatform::services()->kata()->parse($expected_kata, $error, true, $symbol_meta, true);
		
		$actual_kata = DevblocksPlatform::services()->kata()->emit($kata);
		
		$this->assertEquals($expected_kata, $actual_kata);
	}
	
	function _testKataPreserveWhitespace() {
		$expected_kata = <<< EOD
      # Branch A
      branch/a:
        name: Branch A
      
      # Branch B
      branch/b:
        name: Branch B
      
      # Branch C
      branch/c:
        name: Branch C
      EOD;
		
		$error = null;
		$symbol_meta = [];
		
		$kata = DevblocksPlatform::services()->kata()->parse($expected_kata, $error, true, $symbol_meta, true);
		
		$actual_kata = DevblocksPlatform::services()->kata()->emit($kata);
		
		$this->assertEquals($expected_kata, $actual_kata);
	}
	
	function testKataInsertBranch() {
		$existing_kata_string = <<< EOD
      branch/a:
        name: Branch A
      branch/b:
        name@text: Branch B
      branch/c:
        name: Branch C
      EOD;
		
		$replace_kata_string = <<< EOD
      branch/b:
        name@text: New Branch B
      EOD;
		
		$expected_kata = [
      'branch/a' => [
        'name' => 'Branch A',
      ],
			'branch/b' => [
        'name@text' => 'New Branch B',
			],
      'branch/c' => [
        'name' => 'Branch C',
			],
		];
		
		$error = null;
		$symbol_meta = [];
		
		$existing_kata = DevblocksPlatform::services()->kata()->parse($existing_kata_string, $error, true, $symbol_meta, true);
		$replace_kata = DevblocksPlatform::services()->kata()->parse($replace_kata_string, $error, true, $symbol_meta, true);
		
		$this->assertIsArray($existing_kata);
		$this->assertIsArray($replace_kata);
		
		foreach($replace_kata as $k => $v) {
			$existing_kata[$k] = $v;
		}
		
		$this->assertEquals($expected_kata, $existing_kata);
	}
}