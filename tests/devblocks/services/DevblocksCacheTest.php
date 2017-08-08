<?php
class DevblocksCacheTest extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testCachePersistSave() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = true;
		$actual = $cache->save('test123', 'test.cache');
		
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * @depends testCachePersistSave
	 */
	function testCachePersistRead() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = 'test123';
		$actual = $cache->load('test.cache');
		
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * @depends testCachePersistRead
	 */
	function testCachePersistRemove() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = true;
		$actual = $cache->remove('test.cache');
		
		$this->assertEquals($expected, $actual);
	}
	
	function testCacheLocalSave() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = true;
		$actual = $cache->save('this is some data', 'test.cache.local', array(), 0, true);
		
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * @depends testCacheLocalSave
	 */
	function testCacheLocalRead() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = 'this is some data';
		$actual = $cache->load('test.cache.local', false, true);
		
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * @depends testCacheLocalRead
	 */
	function testCacheLocalRemove() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = true;
		$actual = $cache->remove('test.cache.local', true);
		
		$this->assertEquals($expected, $actual);
	}
	
}
