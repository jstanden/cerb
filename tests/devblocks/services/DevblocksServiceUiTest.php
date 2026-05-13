<?php
use PHPUnit\Framework\TestCase;

class DevblocksServiceUiTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testLabelsNoCondense() {
		$menu = DevblocksPlatform::services()->ui()->menu();
		
		$labels = [
			'calendar_day_of_week' => 'Calendar Day of Week',
		];
		
		$expected = new DevblocksMenuItemPlaceholder();
		$expected->label = 'Calendar';
		$expected->l = 'Calendar';
		
		$expected->children['Day'] = new DevblocksMenuItemPlaceholder();
		$expected->children['Day']->label = 'Calendar Day';
		$expected->children['Day']->l = 'Day';
		
		$expected->children['Day']->children['of'] = new DevblocksMenuItemPlaceholder();
		$expected->children['Day']->children['of']->label = 'Calendar Day of';
		$expected->children['Day']->children['of']->l = 'of';
		
		$expected->children['Day']->children['of']->children['Week'] = new DevblocksMenuItemPlaceholder();
		$expected->children['Day']->children['of']->children['Week']->label = 'Calendar Day of Week';
		$expected->children['Day']->children['of']->children['Week']->l = 'Week';
		$expected->children['Day']->children['of']->children['Week']->key = 'calendar_day_of_week';
		
		$expected = ['Calendar' => $expected];
		
		$actual = $menu->parse($labels, condense: false);
		
		$this->assertEquals($expected, $actual);
	}
	
	function testLabelsCondense() {
		$menu = DevblocksPlatform::services()->ui()->menu();
		
		$labels = [
			'calendar_day_of_week' => 'Calendar Day of Week',
		];
		
		$expected = new DevblocksMenuItemPlaceholder();
		$expected->label = 'Calendar Day of Week';
		$expected->l = 'Calendar Day of Week';
		$expected->key = 'calendar_day_of_week';
		
		$expected = [$expected->label => $expected];
		
		$actual = $menu->parse($labels);
		
		$this->assertEquals($expected, $actual);
	}

	function testLabelsCondenseBranch() {
		$menu = DevblocksPlatform::services()->ui()->menu();

		$labels = [
			'calendar_day_of_week' => 'Calendar Day Of Week',
			'calendar_day_of_month' => 'Calendar Day Of Month',
		];

		$expected_parent = new DevblocksMenuItemPlaceholder();
		$expected_parent->label = 'Calendar Day Of';
		$expected_parent->l = 'Calendar Day Of';

		$week = new DevblocksMenuItemPlaceholder();
		$week->label = 'Calendar Day Of Week';
		$week->l = 'Week';
		$week->key = 'calendar_day_of_week';

		$month = new DevblocksMenuItemPlaceholder();
		$month->label = 'Calendar Day Of Month';
		$month->l = 'Month';
		$month->key = 'calendar_day_of_month';

		$expected_parent->children = ['Week' => $week, 'Month' => $month];

		$actual = $menu->parse($labels);

		$this->assertEquals(['Calendar Day Of' => $expected_parent], $actual);
	}

	function testLabelsCondenseMultipleRoots() {
		$menu = DevblocksPlatform::services()->ui()->menu();

		$labels = [
			'alpha' => 'Alpha',
			'beta' => 'Beta',
		];

		$alpha = new DevblocksMenuItemPlaceholder();
		$alpha->label = 'Alpha';
		$alpha->l = 'Alpha';
		$alpha->key = 'alpha';

		$beta = new DevblocksMenuItemPlaceholder();
		$beta->label = 'Beta';
		$beta->l = 'Beta';
		$beta->key = 'beta';

		$actual = $menu->parse($labels);

		$this->assertEquals(['Alpha' => $alpha, 'Beta' => $beta], $actual);
	}
}