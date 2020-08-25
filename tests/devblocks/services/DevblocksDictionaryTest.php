<?php
class DevblocksDictionaryTest extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testSetSimpleKey() {
		$dict = new DevblocksDictionaryDelegate([]);
		
		$expected = 'test_value';
		$actual = $dict->set('test_key', $expected);
		
		$this->assertEquals($expected, $actual);
	}
	
	function testGetSimpleKey() {
		$dict = new DevblocksDictionaryDelegate([
			'test_key' => 'test_value',
		]);
		
		$expected = 'test_value';
		$actual = $dict->get('test_key');
		
		$this->assertEquals($expected, $actual);
	}
	
	function testGetSimpleKeyDefault() {
		$dict = new DevblocksDictionaryDelegate([]);
		
		$expected = 'default_value';
		$actual = $dict->get('missing_key', $expected);
		
		$this->assertEquals($expected, $actual);
	}
	
	function testGetKeyPath() {
		$dict = new DevblocksDictionaryDelegate([
			'deep' => [
				'deep' => [
					'key' => 'deep_value',
				],
			]
		]);
		
		$expected = 'deep_value';
		$actual = $dict->getKeyPath('deep.deep.key');
		$this->assertEquals($expected, $actual);
	}
	
	function testSetKeyPath() {
		$dict = new DevblocksDictionaryDelegate([]);
		
		$dict->setKeyPath('deep.deep.key', 'deep_value');
		$expected = 'deep_value';
		$actual = $dict->getKeyPath('deep.deep.key');
		$this->assertEquals($expected, $actual);
		
		// Indexed elements
		$dict->setKeyPath('deep.deep.0.key', 'deep_value');
		$expected = 'deep_value';
		$actual = $dict->getKeyPath('deep.deep.0.key');
		$this->assertEquals($expected, $actual);
	}
	
	function testSetKeyIndexed() {
		$dict = new DevblocksDictionaryDelegate([]);
		
		$dict->setKeyPath('nested.array.0', '123');
		$expected = '123';
		$actual = $dict->getKeyPath('nested.array.0');
		$this->assertEquals($expected, $actual);
		
		$dict->setKeyPath('nested.array.1', '456');
		$expected = '456';
		$actual = $dict->getKeyPath('nested.array.1');
		$this->assertEquals($expected, $actual);
	}
	
	function testSetKeyPathsOverwriteString() {
		$dict = new DevblocksDictionaryDelegate([]);
		
		// Shallow set
		$key_path = '__state.next';
		$dict->setKeyPath($key_path, 'old_state');
		$expected = 'old_state';
		$actual = $dict->getKeyPath($key_path);
		$this->assertEquals($expected, $actual);
		
		// Shallow set
		$key_path = '__state.next';
		$dict->setKeyPath($key_path, 'new_state');
		$expected = 'new_state';
		$actual = $dict->getKeyPath($key_path);
		$this->assertEquals($expected, $actual);
	}
	
	function testSetKeyPathsDeepShallow() {
		$dict = new DevblocksDictionaryDelegate([]);
		
		// Empty get
		$key_path = '__state.memory.someNode';
		$dict->getKeyPath($key_path);
		
		// Deep set
		$key_path = '__state.memory.someNode';
		$dict->setKeyPath($key_path, 'deep_value');
		$expected = 'deep_value';
		$actual = $dict->getKeyPath($key_path);
		$this->assertEquals($expected, $actual);
		
		// Shallow set
		$key_path = '__state.next';
		$dict->setKeyPath($key_path, 'next_state');
		$expected = 'next_state';
		$actual = $dict->getKeyPath($key_path);
		$this->assertEquals($expected, $actual);
		
		// Shallow set
		$key_path = '__state.last';
		$dict->setKeyPath($key_path, 'last_state');
		$expected = 'last_state';
		$actual = $dict->getKeyPath($key_path);
		$this->assertEquals($expected, $actual);
	}
	
	function testSetPush() {
		$dict = new DevblocksDictionaryDelegate([
			'existing_key' => [1,2],
			'deeply' => [
				'nested' => [
					'key' => ['a','b'],
				],
			],
			'a_string' => 'a',
			'a_number' => 1,
		]);
		
		$dict->setPush('existing_key', 3);
		$expected = [1,2,3];
		$actual = $dict->get('existing_key');
		$this->assertEquals($expected, $actual);
		
		$dict->setPush('deeply.nested.key', 'c');
		$expected = ['a','b','c'];
		$actual = $dict->getKeyPath('deeply.nested.key');
		$this->assertEquals($expected, $actual);
		
		$dict->setPush('a_string', 'b');
		$expected = ['a','b'];
		$actual = $dict->getKeyPath('a_string');
		$this->assertEquals($expected, $actual);
		
		$dict->setPush('a_number', 2);
		$dict->setPush('a_number', 3);
		$expected = [1,2,3];
		$actual = $dict->getKeyPath('a_number');
		$this->assertEquals($expected, $actual);
	}
	
	function testUnset() {
		$dict = new DevblocksDictionaryDelegate([
			'existing_key' => 'existing_value',
		]);
		
		$dict->unset('existing_key');
		
		$expected = null;
		$actual = $dict->get('existing_key', null);
		
		$this->assertEquals($expected, $actual);
	}
	
	function testUnsetKeyPath() {
		$dict = new DevblocksDictionaryDelegate([
			'deep' => [
				'deep' => [
					'key' => 'deep_value',
				],
			]
		]);
		
		$dict->unsetKeyPath('deep.deep.key');
		
		$expected = null;
		$actual = $dict->getKeyPath('deep.deep.key', null);
		$this->assertEquals($expected, $actual);
		
		$expected = [];
		$actual = $dict->getKeyPath('deep.deep', null);
		$this->assertEquals($expected, $actual);
	}
	
	function testScrubKeyPathPrefix() {
		$dict = new DevblocksDictionaryDelegate([
			'nested' => [
				'a_0' => [
					'key' => 'a_0_value',
				],
				'a_1' => [
					'key' => 'a_1_value',
				],
				'b_0' => [
					'key' => 'b_0_value',
				],
				'b_1' => [
					'key' => 'b_1_value',
				],
			]
		]);
		
		$dict->scrubKeyPathPrefix('nested', 'a_');
		
		$expected = [
			'b_0' => [
				'key' => 'b_0_value',
			],
			'b_1' => [
				'key' => 'b_1_value',
			],
		];
		$actual = $dict->getKeyPath('nested', []);
		$this->assertEquals($expected, $actual);
	}
	
	function testScrubKeyPathSuffix() {
		$dict = new DevblocksDictionaryDelegate([
			'nested' => [
				'a_0' => [
					'key' => 'a_0_value',
				],
				'a_1' => [
					'key' => 'a_1_value',
				],
				'b_0' => [
					'key' => 'b_0_value',
				],
				'b_1' => [
					'key' => 'b_1_value',
				],
			]
		]);
		
		$dict->scrubKeyPathSuffix('nested', '_0');
		
		$expected = [
			'a_1' => [
				'key' => 'a_1_value',
			],
			'b_1' => [
				'key' => 'b_1_value',
			],
		];
		$actual = $dict->getKeyPath('nested', []);
		$this->assertEquals($expected, $actual);
	}
	
	function testExists() {
		$dict = new DevblocksDictionaryDelegate([
			'key_exists' => true,
		]);
		
		// Hit
		$expected = true;
		$actual = $dict->exists('key_exists');
		$this->assertEquals($expected, $actual);
		
		// Miss
		$expected = false;
		$actual = $dict->exists('missing_key');
		$this->assertEquals($expected, $actual);
	}
}
