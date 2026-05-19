<?php

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class DevblocksCacheTest extends TestCase {
	function testCachePersistSave() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = true;
		$actual = $cache->save('test123', 'test.cache');
		
		$this->assertEquals($expected, $actual);
	}
	
	#[Depends('testCachePersistSave')]
	function testCachePersistRead() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = 'test123';
		$actual = $cache->load('test.cache');
		
		$this->assertEquals($expected, $actual);
	}
	
	#[Depends('testCachePersistRead')]
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
	
	#[Depends('testCacheLocalSave')]
	function testCacheLocalRead() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = 'this is some data';
		$actual = $cache->load('test.cache.local', false, true);
		
		$this->assertEquals($expected, $actual);
	}
	
	#[Depends('testCacheLocalRead')]
	function testCacheLocalRemove() {
		$cache = DevblocksPlatform::services()->cache();
		
		$expected = true;
		$actual = $cache->remove('test.cache.local', true);
		
		$this->assertEquals($expected, $actual);
	}
	
}
