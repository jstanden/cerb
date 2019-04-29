<?php
class MockObject {
	public $id = 0;
	public $name = '';
	
	public function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
	}
	
	public function __toString() {
		return $this->name;
	}
}

class DevblocksPlatformTest extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	public function testRequirements() {
		// Version
		$actual = version_compare(PHP_VERSION, "7.0", ">=");
		$this->assertEquals(true, $actual, sprintf('Cerb requires a PHP version of 7.0+, currently %s', PHP_VERSION));

		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		$actual = ($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0);
		$this->assertEquals(true, $actual, 'In php.ini, file_uploads is not enabled.');
		
		// Memory Limit
		$expected = 16777216;
		$ini_memory_limit = ini_get("memory_limit");
		$actual = DevblocksPlatform::parseBytesString($ini_memory_limit ?: PHP_INT_MAX);
		$this->assertGreaterThanOrEqual($expected, $actual, 'Cerb requires a memory_limit in php.ini of at least 16MB');
		
		// Required extensions
		$required_extensions = array(
			'ctype',
			'curl',
			'dom',
			'gd',
			'imap',
			'json',
			'mailparse',
			'mbstring',
			'mysqli',
			'openssl',
			'pcre',
			'session',
			'simplexml',
			'spl',
			'xml',
		);
		
		foreach($required_extensions as $extension) {
			$actual = extension_loaded($extension);
			$this->assertEquals(true, $actual, sprintf('The %s extension is required.', $extension));
		}
	}
	
	public function testCompareStrings() {
		// Equals
		$actual = DevblocksPlatform::compareStrings('foo', 'foo', 'is');
		$this->assertEquals(true, $actual);
		
		// Doesn't equal
		$actual = DevblocksPlatform::compareStrings('foo', 'bar', '!is');
		$this->assertEquals(true, $actual);
		
		// Like
		$actual = DevblocksPlatform::compareStrings('foobar', 'foo*', 'like');
		$this->assertEquals(true, $actual);
		
		// Not like
		$actual = DevblocksPlatform::compareStrings('barfoo', 'foo*', '!like');
		$this->assertEquals(true, $actual);
		
		// Contains
		$actual = DevblocksPlatform::compareStrings('foobar', 'bar', 'contains');
		$this->assertEquals(true, $actual);
		
		// Doesn't contain
		$actual = DevblocksPlatform::compareStrings('foobar', 'baz', '!contains');
		$this->assertEquals(true, $actual);
		
		// Regexp matches
		$actual = DevblocksPlatform::compareStrings('foobar', '/^foo/', 'regexp');
		$this->assertEquals(true, $actual);
		
		// Regexp doesn't match
		$actual = DevblocksPlatform::compareStrings('foobar', '/^baz/', '!regexp');
		$this->assertEquals(true, $actual);
	}
	
	public function testFormatNumberAs() {
		// Bytes
		$actual = DevblocksPlatform::formatNumberAs(586, 'bytes');
		$this->assertEquals('586 bytes', $actual);
		
		// Bytes (KB)
		$actual = DevblocksPlatform::formatNumberAs(123456, 'bytes');
		$this->assertEquals('123 KB', $actual);
		
		// Bytes (MB)
		$actual = DevblocksPlatform::formatNumberAs(12345678, 'bytes');
		$this->assertEquals('12 MB', $actual);
		
		// Bytes (GB)
		$actual = DevblocksPlatform::formatNumberAs(1234567890, 'bytes');
		$this->assertEquals('1 GB', $actual);
		
		// Seconds
		$actual = DevblocksPlatform::formatNumberAs(1350, 'seconds');
		$this->assertEquals('22 mins, 30 secs', $actual);
		
		// Minutes
		$actual = DevblocksPlatform::formatNumberAs(725, 'minutes');
		$this->assertEquals('12 hours, 5 mins', $actual);
		
		// Number
		$actual = DevblocksPlatform::formatNumberAs(12345678, 'number');
		$this->assertEquals('12,345,678', $actual);
		
		// Decimal (rounded up)
		$actual = DevblocksPlatform::formatNumberAs(123.4567, 'decimal');
		$this->assertEquals('123.46', $actual);
		
		// Decimal (rounded down)
		$actual = DevblocksPlatform::formatNumberAs(123.1716, 'decimal');
		$this->assertEquals('123.17', $actual);
		
		// Percent (rounded up)
		$actual = DevblocksPlatform::formatNumberAs(57.86, 'percent');
		$this->assertEquals('58%', $actual);
		
		// Percent (rounded down)
		$actual = DevblocksPlatform::formatNumberAs(57.34, 'percent');
		$this->assertEquals('57%', $actual);
	}
	
	public function testGetSetLocale() {
		DevblocksPlatform::setLocale('de_DE');
		$locale = DevblocksPlatform::getLocale();
		$this->assertEquals('de_DE', $locale);

		DevblocksPlatform::setLocale('en_US');
		$locale = DevblocksPlatform::getLocale();
		$this->assertEquals('en_US', $locale);
	}
	
	public function testGetTempFile() {
		$fp = DevblocksPlatform::getTempFile();
		
		// Is resource?
		$this->assertEquals(true, is_resource($fp));
		
		// Get temp file name
		$filename = DevblocksPlatform::getTempFileInfo($fp);
		$this->assertEquals(true, !empty($filename));
		
		// File exists?
		$this->assertEquals(true, file_exists($filename));
		
		// Write to temp file pointer
		$this->assertEquals(true, fwrite($fp, '12345'));
		$this->assertEquals(5, filesize($filename));
		
		// Remove file and close pointer
		$this->assertEquals(true, unlink($filename));
		$this->assertEquals(true, fclose($fp));
	}
	
	public function testImportGPC() {
		// array (null)
		$actual = DevblocksPlatform::importGPC(null, 'array', array());
		$this->assertEquals(array(), $actual);
		
		// array (not type checked)
		$actual = DevblocksPlatform::importGPC(array(1,2,3,4), 'array', array());
		$this->assertEquals(array(1,2,3,4), $actual);
		
		// array (type checked: int)
		$actual = DevblocksPlatform::importGPC(array('1',2,'3',4), 'array:integer', array());
		$this->assertEquals(array(1,2,3,4), $actual);
		
		// array (type checked: bools)
		$actual = DevblocksPlatform::importGPC(array(true,'1',1,false,0,null), 'array:bool', array());
		$this->assertEquals(array(true,true,true,false,false,false), $actual);
		
		
		// bit (null -> 0)
		$actual = DevblocksPlatform::importGPC(null, 'bit', 0);
		$this->assertEquals(0, $actual);
		
		// bit (null -> 1)
		$actual = DevblocksPlatform::importGPC(null, 'bit', 1);
		$this->assertEquals(1, $actual);
		
		// bit (false -> 0)
		$actual = DevblocksPlatform::importGPC(false, 'bit', 0);
		$this->assertEquals(0, $actual);
		
		// bit (true -> 1)
		$actual = DevblocksPlatform::importGPC(true, 'bit', 1);
		$this->assertEquals(1, $actual);
		
		// bit (anything -> 1)
		$actual = DevblocksPlatform::importGPC('yes', 'bit', 1);
		$this->assertEquals(1, $actual);
		
		
		// boolean (null -> false)
		$actual = DevblocksPlatform::importGPC(null, 'bool', false);
		$this->assertEquals(false, $actual);
		
		// boolean (null -> true)
		$actual = DevblocksPlatform::importGPC(null, 'bool', true);
		$this->assertEquals(true, $actual);
		
		// boolean (false -> false)
		$actual = DevblocksPlatform::importGPC(false, 'bool');
		$this->assertEquals(false, $actual);
		
		// boolean (true -> 1)
		$actual = DevblocksPlatform::importGPC(true, 'bool');
		$this->assertEquals(true, $actual);
		
		// boolean (empty -> false)
		$actual = DevblocksPlatform::importGPC('', 'bool');
		$this->assertEquals(false, $actual);
		
		// boolean (string: no -> true)
		$actual = DevblocksPlatform::importGPC('no', 'bool');
		$this->assertEquals(false, $actual);
		
		// boolean (string: yes -> true)
		$actual = DevblocksPlatform::importGPC('yes', 'bool');
		$this->assertEquals(true, $actual);
		
		// boolean (string: false -> false)
		$actual = DevblocksPlatform::importGPC('false', 'bool');
		$this->assertEquals(false, $actual);
		
		// boolean (string: true -> true)
		$actual = DevblocksPlatform::importGPC('true', 'bool');
		$this->assertEquals(true, $actual);
		
		
		// float (null -> 3.14)
		$actual = DevblocksPlatform::importGPC(null, 'float', 3.14);
		$this->assertEquals(3.14, $actual);
		
		// float (string -> float)
		$actual = DevblocksPlatform::importGPC('3.14', 'float');
		$this->assertEquals(3.14, $actual);

		
		// int (null -> 42)
		$actual = DevblocksPlatform::importGPC(null, 'int', 42);
		$this->assertEquals(42, $actual);
		
		// int (string -> int)
		$actual = DevblocksPlatform::importGPC('42', 'int');
		$this->assertEquals(42, $actual);
		
		
		// string (null -> string)
		$actual = DevblocksPlatform::importGPC(null, 'string', 'name');
		$this->assertEquals('name', $actual);
		
		// string (int -> string)
		$actual = DevblocksPlatform::importGPC(123, 'string', 'name');
		$this->assertEquals('123', $actual);
		
		// string (float -> string)
		$actual = DevblocksPlatform::importGPC(123.45, 'string', 'name');
		$this->assertEquals('123.45', $actual);
		
		// string (bool false -> string)
		$actual = DevblocksPlatform::importGPC(false, 'string', 'name');
		$this->assertEquals('false', $actual);
		
		// string (bool true -> string)
		$actual = DevblocksPlatform::importGPC(true, 'string', 'name');
		$this->assertEquals('true', $actual);
		
		// string (string -> string)
		$actual = DevblocksPlatform::importGPC('this is a string', 'string', 'name');
		$this->assertEquals('this is a string', $actual);
		
		// string (array -> string)
		// [TODO] This could json_encode, etc
		$actual = DevblocksPlatform::importGPC(array(1,2,3), 'string', 'name');
		$this->assertEquals('Array', $actual);

		
		// timestamp (null)
		$time = time();
		$actual = DevblocksPlatform::importGPC(null, 'timestamp', $time);
		$this->assertEquals($time, $actual);
		
		// timestamp (strtotime)
		$time = strtotime('-1 day');
		$actual = DevblocksPlatform::importGPC('-1 day', 'timestamp');
		$this->assertEquals($time, $actual);
		
		// timestamp (unix timestamp)
		$time = time();
		$actual = DevblocksPlatform::importGPC($time, 'timestamp');
		$this->assertEquals($time, $actual);
	}
	
	public function testIntClamp() {
		// Inside bounds
		$actual = DevblocksPlatform::intClamp(5, 1, 10);
		$this->assertEquals(5, $actual);
		
		// Below lower bounds
		$actual = DevblocksPlatform::intClamp(-5, 1, 10);
		$this->assertEquals(1, $actual);
		
		// Exceeds upper bounds
		$actual = DevblocksPlatform::intClamp(20, 1, 10);
		$this->assertEquals(10, $actual);
	}
	
	public function testFloatClamp() {
		// Inside bounds
		$actual = DevblocksPlatform::floatClamp(0.5, 0.0, 1.0);
		$this->assertEquals(0.5, $actual);
		
		// Below lower bounds
		$actual = DevblocksPlatform::floatClamp(-5, 0.0, 1.0);
		$this->assertEquals(0.0, $actual);
		
		// Exceeds upper bounds
		$actual = DevblocksPlatform::floatClamp(20, 0.0, 1.0);
		$this->assertEquals(1.0, $actual);
	}
	
	public function testIsIpAuthorized() {
		$authorized_ips = ['192.168.1.1','127.0.','::1'];
		
		// Exact match
		$actual = DevblocksPlatform::isIpAuthorized('192.168.1.1', $authorized_ips);
		$this->assertTrue($actual);
		
		// Blocked IP
		$actual = DevblocksPlatform::isIpAuthorized('8.8.8.8', $authorized_ips);
		$this->assertFalse($actual);
		
		// IPv6 loopback
		$actual = DevblocksPlatform::isIpAuthorized('::1', $authorized_ips);
		$this->assertTrue($actual);
		
		// strStartsWith collision without wildcard (doesn't end with dot)
		$actual = DevblocksPlatform::isIpAuthorized('192.168.1.100', $authorized_ips);
		$this->assertFalse($actual);
		
		// Localhost subnet wildcard
		$actual = DevblocksPlatform::isIpAuthorized('127.0.0.1', $authorized_ips);
		$this->assertTrue($actual);
	}
	
	public function testObjectsToStrings() {
		// Objects (__toString)
		$objects = array(
			10 => new MockObject(10, 'Jeff'),
			20 => new MockObject(20, 'Dan'),
			30 => new MockObject(30, 'Darren'),
		);
		$expected = array(10 => 'Jeff', 20 => 'Dan', 30 => 'Darren');
		$actual = DevblocksPlatform::objectsToStrings($objects);
		$this->assertEquals($expected, $actual);
		
		// Normal strings
		$objects = array('a', 'b', 'c');
		$expected = array('a', 'b', 'c');
		$actual = DevblocksPlatform::objectsToStrings($objects);
		$this->assertEquals($expected, $actual);
		
		// Integers
		$objects = array(1, 2, 3);
		$expected = array('1', '2', '3');
		$actual = DevblocksPlatform::objectsToStrings($objects);
		$this->assertEquals($expected, $actual);
	}
	
	public function testIntVersionToStr() {
		// x -> x
		$actual = DevblocksPlatform::intVersionToStr(7, 1);
		$this->assertEquals('7', $actual);
		
		// x -> x.y
		$actual = DevblocksPlatform::intVersionToStr(7, 2);
		$this->assertEquals('0.7', $actual);
		
		// x -> x.y.z
		$actual = DevblocksPlatform::intVersionToStr(7, 3);
		$this->assertEquals('0.0.7', $actual);
		
		// x -> x.y.z
		$actual = DevblocksPlatform::intVersionToStr(48, 3);
		$this->assertEquals('0.0.48', $actual);
		
		// xyy -> x
		$actual = DevblocksPlatform::intVersionToStr(701, 1);
		$this->assertEquals('7', $actual);
		
		// xyy -> x.y
		$actual = DevblocksPlatform::intVersionToStr(701, 2);
		$this->assertEquals('7.1', $actual);
		
		// xyy -> x.y.z
		$actual = DevblocksPlatform::intVersionToStr(701, 3);
		$this->assertEquals('0.7.1', $actual);
		
		// xyyzz -> x
		$actual = DevblocksPlatform::intVersionToStr(70109, 1);
		$this->assertEquals('7', $actual);
		
		// xyyzz -> x.y
		$actual = DevblocksPlatform::intVersionToStr(70109, 2);
		$this->assertEquals('7.1', $actual);
		
		// xyyzz -> x.y.z
		$actual = DevblocksPlatform::intVersionToStr(70109, 3);
		$this->assertEquals('7.1.9', $actual);
		
		// vxxyyzz -> v.x.y
		$actual = DevblocksPlatform::intVersionToStr(7010901, 3);
		$this->assertEquals('7.1.9', $actual);
		
		// vxxyyzz -> v.x.y.z
		$actual = DevblocksPlatform::intVersionToStr(7010900, 4);
		$this->assertEquals('7.1.9.0', $actual);
		
		// xxyyz -> 0.0.z
		$actual = DevblocksPlatform::intVersionToStr(8, 3);
		$this->assertEquals('0.0.8', $actual);
		
		// xyyzz -> x.0.0
		$actual = DevblocksPlatform::intVersionToStr(80000, 3);
		$this->assertEquals('8.0.0', $actual);
	}
	
	public function testStrVersionToInt() {
		// x -> x
		$actual = DevblocksPlatform::strVersionToInt('7', 1);
		$this->assertEquals(7, $actual);
		
		// x.y -> x
		$actual = DevblocksPlatform::strVersionToInt('7.1', 1);
		$this->assertEquals(7, $actual);
		
		// x.y.z -> x
		$actual = DevblocksPlatform::strVersionToInt('7.1.2', 1);
		$this->assertEquals(7, $actual);
		
		// x -> xyy
		$actual = DevblocksPlatform::strVersionToInt('7', 2);
		$this->assertEquals(700, $actual);
		
		// x -> xyyzz
		$actual = DevblocksPlatform::strVersionToInt('7', 3);
		$this->assertEquals(70000, $actual);

		// x.y -> xyy
		$actual = DevblocksPlatform::strVersionToInt('7.1', 2);
		$this->assertEquals(701, $actual);
		
		// x.y.z -> xyy
		$actual = DevblocksPlatform::strVersionToInt('7.1.9', 2);
		$this->assertEquals(701, $actual);
		
		// x.yy.z -> xyy
		$actual = DevblocksPlatform::strVersionToInt('7.10.9', 2);
		$this->assertEquals(710, $actual);
		
		// x -> xyyzz
		$actual = DevblocksPlatform::strVersionToInt('7.1', 3);
		$this->assertEquals(70100, $actual);

		// x.y.z -> xyyzz
		$actual = DevblocksPlatform::strVersionToInt('7.1.9', 3);
		$this->assertEquals(70109, $actual);
		
		// x.yy.z -> xyyzz
		$actual = DevblocksPlatform::strVersionToInt('7.10.9', 3);
		$this->assertEquals(71009, $actual);
		
		// v.x.y.z -> vxxyyzz
		$actual = DevblocksPlatform::strVersionToInt('7.1.9.2', 4);
		$this->assertEquals(7010902, $actual);
		
		// v.x.yy.z -> vxxyyzz
		$actual = DevblocksPlatform::strVersionToInt('7.10.9.0', 4);
		$this->assertEquals(7100900, $actual);
		
		// 0.0.z -> xxyyz
		$actual = DevblocksPlatform::strVersionToInt('0.0.8', 3);
		$this->assertEquals(8, $actual);
		
		// x.0.0 -> xyyzz
		$actual = DevblocksPlatform::strVersionToInt('8.0.0', 3);
		$this->assertEquals(80000, $actual);
	}
	
	public function testJsonGetPointerFromPath() {
		$json = '{"person":{"name":"Joe Customer","age":35,"groups":["Billing","Sales","Support"]}}';
		$array = json_decode($json, true);
		
		$ptr = DevblocksPlatform::jsonGetPointerFromPath($array, 'person.name');
		$this->assertEquals('Joe Customer', $ptr);
		
		$ptr = DevblocksPlatform::jsonGetPointerFromPath($array, 'person.age');
		$this->assertEquals(35, $ptr);
		
		$ptr = DevblocksPlatform::jsonGetPointerFromPath($array, 'person.groups');
		$this->assertEquals(array('Billing', 'Sales', 'Support'), $ptr);
		
		$ptr = DevblocksPlatform::jsonGetPointerFromPath($array, 'person.groups[1]');
		$this->assertEquals('Sales', $ptr);
	}
	
	public function testParseAtMentionString() {
		// No @mention
		$str = 'Can you check on this for me tomorrow?';
		$actual = DevblocksPlatform::parseAtMentionString($str);
		$this->assertEquals(array(), $actual);
		
		// Single @mention
		$str = '@Hildy Can you check on this for me tomorrow?';
		$actual = DevblocksPlatform::parseAtMentionString($str);
		$this->assertEquals(array('@Hildy'), $actual);
		
		// Multiple @mentions
		$str = '@Hildy Do you have time for this today?  If not, ask @Jeff, or @Darren';
		$actual = DevblocksPlatform::parseAtMentionString($str);
		$this->assertEquals(array('@Hildy', '@Jeff', '@Darren'), $actual);
		
		// Redundant @mentions
		$str = '@Hildy Do you have time for this today?  Let me know. Thanks, @Hildy';
		$actual = DevblocksPlatform::parseAtMentionString($str);
		$this->assertEquals(array('@Hildy'), $actual);
		
		// @mention in parentheses
		$str = 'Maybe someone can look at this (@Hildy)';
		$actual = DevblocksPlatform::parseAtMentionString($str);
		$this->assertEquals(array('@Hildy'), $actual);
		
		// @mention with trailing punctuation
		$str = 'Thanks, @Darren!';
		$actual = DevblocksPlatform::parseAtMentionString($str);
		$this->assertEquals(array('@Darren'), $actual);
	}
	
	public function testParseBytesString() {
		// Bytes (no unit)
		$actual = DevblocksPlatform::parseBytesString('256');
		$this->assertEquals(256, $actual);
		
		// [TODO] This could test 'bytes', 'MB', 'GB', etc.
		// [TODO] Handle commas and decimals?
		// [TODO] Test KB/MB/GB/TB vs KiB/...
		
		// B
		$actual = DevblocksPlatform::parseBytesString('512B');
		$this->assertEquals(512, $actual);
		
		// KiB
		$actual = DevblocksPlatform::parseBytesString('256KiB');
		$this->assertEquals(262144, $actual);
		
		// MiB
		$actual = DevblocksPlatform::parseBytesString('100MiB');
		$this->assertEquals(104857600, $actual);
		
		// GiB
		$actual = DevblocksPlatform::parseBytesString('8GiB');
		$this->assertEquals(8589934592, $actual);
		
		// TiB
		$actual = DevblocksPlatform::parseBytesString('2TiB');
		$this->assertEquals(2 * pow(1024,4), $actual);
	}
	
	public function testParseCrlfString() {
		// LF, no blanks, no trim
		$str = "1\n2\n3\n\n";
		$actual = DevblocksPlatform::parseCrlfString($str, false, false);
		$this->assertEquals(array('1','2','3'), $actual);
		
		// CRLF, no blanks, no trim
		$str = "1\r\n2\r\n3\r\n";
		$actual = DevblocksPlatform::parseCrlfString($str, false, false);
		$this->assertEquals(array('1','2','3'), $actual);
		
		// LF, with blanks, no trim
		$str = "1\n2\n3\n";
		$actual = DevblocksPlatform::parseCrlfString($str, true, false);
		$this->assertEquals(array('1','2','3',''), $actual);
		
		// LF, no blanks, with trim
		$str = "1\n 2\n 3\n\n";
		$actual = DevblocksPlatform::parseCrlfString($str, false, true);
		$this->assertEquals(array('1','2','3'), $actual);
	}
	
	public function testParseCsvString() {
		// CSV, no blanks, no cast
		$str = "1,2,3,";
		$actual = DevblocksPlatform::parseCsvString($str, false, null);
		$this->assertEquals(array('1','2','3'), $actual);
		
		// CSV, no blanks, no cast, limit 2
		$str = "1,2,3,";
		$actual = DevblocksPlatform::parseCsvString($str, false, null, 2);
		$this->assertEquals(array('1','2,3'), $actual);
		
		// CSV, with blanks, no cast
		$str = "1,2,3,";
		$actual = DevblocksPlatform::parseCsvString($str, true, null);
		$this->assertEquals(array('1','2','3',''), $actual);
		
		// CSV, no blanks, with cast to int
		$str = "1,2,3,";
		$actual = DevblocksPlatform::parseCsvString($str, false, 'int');
		$this->assertEquals(array(1,2,3), $actual);
		
		// CSV, no blanks, with cast to str
		$str = "red,green,blue";
		$actual = DevblocksPlatform::parseCsvString($str, false, 'string');
		$this->assertEquals(array('red','green','blue'), $actual);
		
		// CSV, with blanks, no cast, and limit
		$str = "1,2,3,";
		$actual = DevblocksPlatform::parseCsvString($str, true, null, 2);
		$this->assertEquals(array('1','2,3,'), $actual);
		
	}
	
	public function testParseGeoPointString() {
		$error = null;
		
		// Invalid string
		$str = "arbitrary text";
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals(false, $actual);
		
		// Not floats
		$str = "a,b";
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals(false, $actual);
		
		// Too many commas
		$str = "1,2,3";
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals(false, $actual);
		
		// Invalid latitude
		$str = "95.12, 79.92";
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals(false, $actual);
		
		// Invalid longitude
		$str = "1.23,185.92";
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals(false, $actual);
		
		// Valid
		$str = "37.733795,-122.446747";
		$expected = ['latitude' => 37.733795, 'longitude' => -122.446747];
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals($expected, $actual);
		
		// Valid w/ spaces
		$str = "37.733795 -122.446747";
		$expected = ['latitude' => 37.733795, 'longitude' => -122.446747];
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals($expected, $actual);
		
		// Valid w/ spaces and commas
		$str = "37.733795, -122.446747";
		$expected = ['latitude' => 37.733795, 'longitude' => -122.446747];
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals($expected, $actual);
		
		// N/W syntax (with quotes)
		$str = '37.733795" N, -122.446747" W';
		$expected = ['latitude' => 37.733795, 'longitude' => -122.446747];
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals($expected, $actual);
		
		// N/W syntax (without quotes)
		$str = '37.733795 N, -122.446747 W';
		$expected = ['latitude' => 37.733795, 'longitude' => -122.446747];
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals($expected, $actual);
		
		// S/E syntax
		$str = '33.868" S, 151.21" E';
		$expected = ['latitude' => 33.868, 'longitude' => 151.21];
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals($expected, $actual);
		
		// Valid POINT(long,lat) with spaces
		$str = "POINT(-122.446747 37.733795)";
		$expected = ['latitude' => 37.733795, 'longitude' => -122.446747];
		$actual = DevblocksPlatform::parseGeoPointString($str, $error);
		$this->assertEquals($expected, $actual);
	}
	
	public function testParseHttpHeaderAttributes() {
		// Lowercase attribute names
		$expected = ['charset' => 'utf-8'];
		$header_value = "CHARSET=utf-8";
		$actual = DevblocksPlatform::parseHttpHeaderAttributes($header_value);
		$this->assertEquals($expected, $actual);
		
		// Multiple attributes
		$expected = ['charset' => 'ISO-8859-1', 'boundary' => 'a1b2c3de'];
		$header_value = "charset=ISO-8859-1; boundary=a1b2c3de";
		$actual = DevblocksPlatform::parseHttpHeaderAttributes($header_value);
		$this->assertEquals($expected, $actual);
		
		// Whitespace in the header value
		$expected = ['charset' => 'ISO-8859-1', 'boundary' => 'a1b2c3de'];
		$header_value = "charset = ISO-8859-1 ; boundary = a1b2c3de";
		$actual = DevblocksPlatform::parseHttpHeaderAttributes($header_value);
		$this->assertEquals($expected, $actual);
	}
	
	public function testParseMarkdown() {
		// Bold
		$expected = '<p><strong>Bold</strong></p>'; 
		$actual = DevblocksPlatform::parseMarkdown('**Bold**');
		$this->assertEquals($expected, $actual);
		
		// Italics
		$expected = '<p><em>Bold</em></p>'; 
		$actual = DevblocksPlatform::parseMarkdown('_Bold_');
		$this->assertEquals($expected, $actual);
		
		// Link
		$expected = '<p><a href="http://www.example.com">text</a></p>'; 
		$actual = DevblocksPlatform::parseMarkdown('[text](http://www.example.com)');
		$this->assertEquals($expected, $actual);
	}
	
	public function testParseStringAsRegExp() {
		// Wildcard prefix substring
		$expected = '/(.*?)wildcard/i';
		$actual = DevblocksPlatform::parseStringAsRegExp('*wildcard', true);
		$this->assertEquals($expected, $actual);
		
		// Wildcard prefix non-substring
		$expected = '/^(.*?)wildcard$/i';
		$actual = DevblocksPlatform::parseStringAsRegExp('*wildcard');
		$this->assertEquals($expected, $actual);
		
		// Wildcard suffix substring
		$expected = '/wildcard(.*?)/i';
		$actual = DevblocksPlatform::parseStringAsRegExp('wildcard*', true);
		$this->assertEquals($expected, $actual);
		
		// Test substring
		$expected = '/text/i';
		$actual = DevblocksPlatform::parseStringAsRegExp('text', true);
		$this->assertEquals($expected, $actual);
		
		// Test non-substring
		$expected = '/^text$/i';
		$actual = DevblocksPlatform::parseStringAsRegExp('text');
		$this->assertEquals($expected, $actual);
	}
	
	public function testPurifyHTML() {
		// Strip <script> blocks
		$dirty_html = "<script>alert('hi!');</script><b>Bold</b>";
		$expected = "<b>Bold</b>";
		$actual = DevblocksPlatform::purifyHTML($dirty_html, false, true);
		$this->assertEquals($expected, $actual);
		
		// Inline style attributes
		$dirty_html = "<html><head><style>span { font-weight:bold; }</style></head><body><span>Bold</span></body></html>";
		$expected = '<span style="font-weight:bold;">Bold</span>';
		$actual = DevblocksPlatform::purifyHTML($dirty_html, true, true);
		$this->assertEquals($expected, $actual);
		
		// Test trusted ($is_untrusted=false)
		$dirty_html = '<a href="https://cerb.ai">Website</a>';
		$expected = '<a href="https://cerb.ai">Website</a>';
		$actual = DevblocksPlatform::purifyHTML($dirty_html, false, false);
		$this->assertEquals($expected, $actual);
		
		// Test untrusted ($is_untrusted=true)
		$dirty_html = '<a href="https://cerb.ai">Website</a>';
		$expected = '<a href="https://cerb.ai" target="_blank">Website</a>';
		$actual = DevblocksPlatform::purifyHTML($dirty_html, false, true);
		$this->assertEquals($expected, $actual);
	}
	
	public function testExtractArrayValues() {
		$array = [
			['color' => 'red', 'material' => 'glass'],
			['color' => 'gold', 'material' => 'metal'],
			['color' => 'black', 'material' => 'concrete'],
			['color' => 'red', 'material' => 'wood'],
		];
		
		// Non-unique
		$actual = DevblocksPlatform::extractArrayValues($array, 'color', false);
		$this->assertEquals(['red','gold','black','red'], $actual);
		
		// Unique
		$actual = DevblocksPlatform::extractArrayValues($array, 'color', true);
		$this->assertEquals(['red','gold','black'], $actual);
		
		// Ignore
		$actual = DevblocksPlatform::extractArrayValues($array, 'color', true, ['red']);
		$this->assertEquals(['gold','black'], $actual);
	}
	
	public function testSanitizeArray() {
		// mixed -> int, no options
		$array = array('1',2,'3');
		$actual = DevblocksPlatform::sanitizeArray($array, 'int', array());
		$this->assertEquals(array(1,2,3), $actual);
		
		// mixed -> int, nonzero
		$array = array('0','1',2,'3');
		$actual = DevblocksPlatform::sanitizeArray($array, 'int', array('nonzero'));
		$this->assertEquals(array(1,2,3), array_values($actual));
		
		// mixed -> int, unique
		$array = array('0','1',2,'3',3,3);
		$actual = DevblocksPlatform::sanitizeArray($array, 'int', array('unique'));
		$this->assertEquals(array(0,1,2,3), $actual);
		
		// mixed assoc -> int, nonzero
		$array = array('a'=>'0','b'=>'1','c'=>2,'d'=>'3');
		$actual = DevblocksPlatform::sanitizeArray($array, 'int', array('nonzero'));
		$this->assertEquals(array('b'=>1,'c'=>2,'d'=>3), $actual);
	}
	
	public function testSetDateTimeFormat() {
		$original = DevblocksPlatform::getDateTimeFormat();
		DevblocksPlatform::setDateTimeFormat('Y-m-d h:i a');
		
		$actual = DevblocksPlatform::getDateTimeFormat();
		$this->assertEquals('Y-m-d h:i a', $actual);
		
		DevblocksPlatform::setDateTimeFormat($original);
	}
	
	public function testSortObjects() {
		
		// Assoc arrays first-level property asc sort
		
		$expected = array(
			array('animal' => 'Asp'),
			array('animal' => 'Monkey'),
			array('animal' => 'Zebra'),
		);
		
		$actual = array(
			array('animal' => 'Zebra'),
			array('animal' => 'Asp'),
			array('animal' => 'Monkey'),
		);
		
		DevblocksPlatform::sortObjects($actual, '[animal]', true);
		$this->assertEquals(array_values($expected), array_values($actual));
		
		// Assoc arrays first-level property desc sort
		
		$expected = array(
			array('animal' => 'Zebra'),
			array('animal' => 'Monkey'),
			array('animal' => 'Asp'),
		);
		
		$actual = array(
			array('animal' => 'Zebra'),
			array('animal' => 'Asp'),
			array('animal' => 'Monkey'),
		);
		
		DevblocksPlatform::sortObjects($actual, '[animal]', false);
		$this->assertEquals(array_values($expected), array_values($actual));
		
		// Objects first-level property asc sort
		
		$expected = json_decode(
			'[{"animal":"Asp"},{"animal":"Monkey"},{"animal":"Zebra"}]'	
		);
		
		$actual = json_decode(
			'[{"animal":"Zebra"},{"animal":"Asp"},{"animal":"Monkey"}]'	
		);
		
		DevblocksPlatform::sortObjects($actual, 'animal', true);
		$this->assertEquals(json_encode(array_values($expected)), json_encode(array_values($actual)));
		
		// Objects first-level property desc sort
		
		$expected = json_decode(
			'[{"animal":"Zebra"},{"animal":"Monkey"},{"animal":"Asp"}]'	
		);
		
		$actual = json_decode(
			'[{"animal":"Zebra"},{"animal":"Asp"},{"animal":"Monkey"}]'	
		);
		
		DevblocksPlatform::sortObjects($actual, 'animal', false);
		$this->assertEquals(json_encode(array_values($expected)), json_encode(array_values($actual)));
		
		// Assoc arrays deeply nested property asc sort
		
		$expected = array(
			array(
				10 => array(
					'name' => 'Robert Middleswarth',	
					'org' => array(
						'name' => 'Answernet',
					),	
				),
				5 => array(
					'name' => 'Niek Beernink',	
					'org' => array(
						'name' => 'Oxilion',
					),	
				),
				1 => array(
					'name' => 'Jeff Standen',	
					'org' => array(
						'name' => 'Webgroup Media LLC',
					),	
				),
			),
		);
		
		$actual = array(
			array(
				1 => array(
					'name' => 'Jeff Standen',	
					'org' => array(
						'name' => 'Webgroup Media LLC',
					),	
				),
				5 => array(
					'name' => 'Niek Beernink',	
					'org' => array(
						'name' => 'Oxilion',
					),	
				),
				10 => array(
					'name' => 'Robert Middleswarth',	
					'org' => array(
						'name' => 'Answernet',
					),	
				),
			),
		);

		DevblocksPlatform::sortObjects($actual, '[org][name]', true);
		$this->assertEquals($expected, $actual);
		
		// Assoc arrays deeply nested property desc sort
		
		$expected = array(
			array(
				10 => array(
					'name' => 'Robert Middleswarth',	
					'org' => array(
						'name' => 'Answernet',
					),	
				),
				5 => array(
					'name' => 'Niek Beernink',	
					'org' => array(
						'name' => 'Oxilion',
					),	
				),
				1 => array(
					'name' => 'Jeff Standen',	
					'org' => array(
						'name' => 'Webgroup Media LLC',
					),	
				),
			),
		);
		
		$actual = array(
			array(
				1 => array(
					'name' => 'Jeff Standen',	
					'org' => array(
						'name' => 'Webgroup Media LLC',
					),	
				),
				5 => array(
					'name' => 'Niek Beernink',	
					'org' => array(
						'name' => 'Oxilion',
					),	
				),
				10 => array(
					'name' => 'Robert Middleswarth',	
					'org' => array(
						'name' => 'Answernet',
					),	
				),
			),
		);

		DevblocksPlatform::sortObjects($actual, '[name]', false);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrAlphaNum() {
		// Strip non-alphanumeric
		$expected = 'ABCDEF123';
		$actual = DevblocksPlatform::strAlphaNum('A*B(C)D$E.F-1/2<<3>>');
		$this->assertEquals($expected, $actual);
		
		// allow underscores
		$expected = 'ABC_DEF_123';
		$actual = DevblocksPlatform::strAlphaNum('((A-B))C_D<E>*F*_{1};2"3"', '_');
		$this->assertEquals($expected, $actual);
		
		// allow parentheses, replace invalid with _
		$expected = 'ABC_DEF(123)';
		$actual = DevblocksPlatform::strAlphaNum('ABC$DEF(123)', '()', '_');
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrUpper() {
		$expected = "Ü";
		$actual = DevblocksPlatform::strUpper("ü");
		$this->assertEquals($expected, $actual);
		
		$expected = "THIS IS SOME TEXT!";
		$actual = DevblocksPlatform::strUpper("this is some text!");
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrLower() {
		$expected = "ü";
		$actual = DevblocksPlatform::strLower("Ü");
		$this->assertEquals($expected, $actual);
		
		$expected = "this is some text!";
		$actual = DevblocksPlatform::strLower("THIS IS SOME TEXT!");
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrStartsWith() {
		// Single prefix
		$string = '1234567890';
		$expected = '1';
		$actual = DevblocksPlatform::strStartsWith($string, '1');
		$this->assertEquals($expected, $actual);
		
		// Array of prefixes
		$string = 'CDEFGH';
		$expected = 'C';
		$actual = DevblocksPlatform::strStartsWith($string, ['A','B','C']);
		$this->assertEquals($expected, $actual);
		
		// Not found
		$string = 'ABCDEFG';
		$expected = false;
		$actual = DevblocksPlatform::strEndsWith($string, ['X','Y','Z']);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrEndsWith() {
		// Single suffix
		$string = '1234567890';
		$expected = '0';
		$actual = DevblocksPlatform::strEndsWith($string, '0');
		$this->assertEquals($expected, $actual);
		
		// Array of suffixes
		$string = 'TUVWXYZ';
		$expected = 'Z';
		$actual = DevblocksPlatform::strEndsWith($string, ['A','M','Z']);
		$this->assertEquals($expected, $actual);
		
		// Not found
		$string = 'TUVWXYZ';
		$expected = false;
		$actual = DevblocksPlatform::strEndsWith($string, ['A','B','C']);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrTrimStart() {
		// Array of prefixes
		$string = 'ABCDEFG';
		$expected = 'DEFG';
		$actual = DevblocksPlatform::strTrimStart($string, ['A','B','C']);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrTrimEnd() {
		// Array of suffixes
		$string = 'TUVWXYZ';
		$expected = 'TUVW';
		$actual = DevblocksPlatform::strTrimEnd($string, ['X','Y','Z']);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrBase32Encode() {
		$expected = 'I5UXMZJANVSSAJBRGAYDAMBAN5TCAQKBKBGCA43UN5RWWLBAOBWGKYLTMUQQ====';
		$actual = DevblocksPlatform::strBase32Encode('Give me $10000 of AAPL stock, please!');
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrBase32Decode() {
		$expected = 'Give me $10000 of AAPL stock, please!';
		$actual = DevblocksPlatform::strBase32Decode('I5UXMZJANVSSAJBRGAYDAMBAN5TCAQKBKBGCA43UN5RWWLBAOBWGKYLTMUQQ====', true);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrBase64UrlEncode() {
		$string = '';
		for($i=0;$i<255;$i++) { $string .= chr($i); }
		$expected = 'AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8gISIjJCUmJygpKissLS4vMDEyMzQ1Njc4OTo7PD0-P0BBQkNERUZHSElKS0xNTk9QUVJTVFVWV1hZWltcXV5fYGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6e3x9fn-AgYKDhIWGh4iJiouMjY6PkJGSk5SVlpeYmZqbnJ2en6ChoqOkpaanqKmqq6ytrq-wsbKztLW2t7i5uru8vb6_wMHCw8TFxsfIycrLzM3Oz9DR0tPU1dbX2Nna29zd3t_g4eLj5OXm5-jp6uvs7e7v8PHy8_T19vf4-fr7_P3-';
		$actual = DevblocksPlatform::services()->string()->base64UrlEncode($string);
		$this->assertSame($expected, $actual);
	}
	
	public function testStrBase64UrlDecode() {
		$string = 'AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8gISIjJCUmJygpKissLS4vMDEyMzQ1Njc4OTo7PD0-P0BBQkNERUZHSElKS0xNTk9QUVJTVFVWV1hZWltcXV5fYGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6e3x9fn-AgYKDhIWGh4iJiouMjY6PkJGSk5SVlpeYmZqbnJ2en6ChoqOkpaanqKmqq6ytrq-wsbKztLW2t7i5uru8vb6_wMHCw8TFxsfIycrLzM3Oz9DR0tPU1dbX2Nna29zd3t_g4eLj5OXm5-jp6uvs7e7v8PHy8_T19vf4-fr7_P3-';
		$expected = '';
		for($i=0;$i<255;$i++) { $expected .= chr($i); }
		$actual = DevblocksPlatform::services()->string()->base64UrlDecode($string);
		$this->assertSame($expected, $actual);
	}
	
	public function testStrBitsToInt() {
		// Test 4 bits as int == 2^4=16
		$expected = 16;
		$actual = DevblocksPlatform::strBitsToInt(4);
		$this->assertEquals($expected, $actual);
		
		// Test 4 bits as string == 2^4=16
		$expected = 16;
		$actual = DevblocksPlatform::strBitsToInt('4');
		$this->assertEquals($expected, $actual);
		
		// Test 2 bytes as string == 2^16=65,536
		$expected = pow(2,16);
		$actual = DevblocksPlatform::strBitsToInt('2 bytes');
		$this->assertEquals($expected, $actual);

		// Test negative bits as number
		$expected = -128;
		$actual = DevblocksPlatform::strBitsToInt(-7);
		$this->assertEquals($expected, $actual);
		
		// Test negative bits as string
		$expected = -65536;
		$actual = DevblocksPlatform::strBitsToInt('-2 bytes');
		$this->assertEquals($expected, $actual);
		
		// Test 32-bit overflow
		if(4 == PHP_INT_SIZE) {
			$expected = pow(2,31);
			$actual = DevblocksPlatform::strBitsToInt(32);
			$this->assertEquals($expected, $actual);
			
		// Test 32 bits as int == 2^32=4,294,967,296
		} else {
			$expected = pow(2,32);
			$actual = DevblocksPlatform::strBitsToInt(32);
			$this->assertEquals($expected, $actual);
		}
		
		// Test overflow on PHP_INT_MAX
		$expected = PHP_INT_MAX;
		$actual = DevblocksPlatform::strBitsToInt(512);
		$this->assertEquals($expected, $actual);
		
		// Test underflow on PHP_INT_MIN
		$expected = PHP_INT_MIN;
		$actual = DevblocksPlatform::strBitsToInt(-512);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrFormatJson() {
		$expected = 
		"[\n".
		"    {\n".
		"        \"name\": \"Jeff\",\n".
		"        \"title\": \"Software Architect\",\n".
		"        \"org\": \"Webgroup Media LLC\"\n".
		"    }\n".
		"]";
		
		// Test array input
		
		$actual = DevblocksPlatform::strFormatJson(array(
			array('name'=>'Jeff','title'=>'Software Architect','org'=>'Webgroup Media LLC'),
		));
		
		$this->assertEquals($expected, $actual);
		
		// Test string input
		
		$actual = DevblocksPlatform::strFormatJson('[{"name":"Jeff","title":"Software Architect","org":"Webgroup Media LLC"}]');
		
		$this->assertEquals($expected, $actual);
	}
	
	public function testStripHTML() {
		// Strip basic HTML tags
		$html = '<html><head><script>alert("hi");</script></head><body><b>Bold</b> really <i>suits</i> you!</body></html>';
		$expected = "Bold really suits you!\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Strip a bunch of whitespace
		$html = '1<br>2<br />3<div>4</div>';
		$expected = "1\n2\n3\n4\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Convert Windows' silly quotes
		$html = '&ldquo;quotes&rdquo; and &#8220;quotes&#8221; and &#x201c;quotes&#x201d;';
		$expected = '"quotes" and "quotes" and "quotes"' . "\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Convert Ellipsis
		$html = 'And&hellip; then&#8230; it&#x2026;';
		$expected = "And... then... it...\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Convert links
		$html = '<a href="http://www.example.com/">link text</a>';
		$expected = "[link text](http://www.example.com/)\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Convert unordered list
		$html = '<ul><li>one</li><li>two</li><li>three</li></ul>';
		$expected = "- one\n\n- two\n\n- three\n\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Convert ordered list
		$html = '<ol><li>one</li><li>two</li><li>three</li></ol>';
		$expected = "1. one\n\n2. two\n\n3. three\n\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Convert nested lists
		$html = '<ol><li><ul><li>red</li><li>green</li></ul></li><li><ul><li>blue</li><li>orange</li></ul></li><li><ul><li>yellow</li><li>purple</li></ul></li></ol>';
		$expected = "1. - red\n\n- green\n\n2. - blue\n\n- orange\n\n3. - yellow\n\n- purple\n\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Convert nested lists
		$html = '<ol><li>one<ul><li>red</li><li>green</li></ul></li><li>two<ul><li>blue</li><li>orange</li></ul></li><li>three<ul><li>yellow</li><li>purple</li></ul></li></ol>';
		$expected = "1. one\n- red\n\n- green\n\n2. two\n- blue\n\n- orange\n\n3. three\n- yellow\n\n- purple\n\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Collapse multiple spaces
		$html = 'this  had	multiple	  spaces';
		$expected = "this had multiple spaces\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
		
		// Maximum of two consecutive linefeeds
		$html = "this<br><br><br>had<br>multiple<br><div>linefeeds</div><br>";
		$expected = "this\n\nhad\nmultiple\n\nlinefeeds\n\n\n";
		$actual = DevblocksPlatform::stripHTML($html, true, false);
		$this->assertEquals($expected, $actual);
	}
	
	public function testArrayDictSet() {
		// Nested dot notation
		
		$expected = [
			'person' => [
				'name' => [
					'first' => 'Bob'
				]
			]
		];
		$actual = [];
		$key = "person.name.first";
		$val = "Bob";
		
		$actual = DevblocksPlatform::arrayDictSet($actual, $key, $val);
		$this->assertEquals($expected, $actual);
		
		// Using {DOT} escaping
		
		$expected = [
			'person' => [
				'name.first' => 'Bob'
			]
		];
		$actual = [];
		$key = "person.name{DOT}first";
		$val = "Bob";
		
		$actual = DevblocksPlatform::arrayDictSet($actual, $key, $val);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrParseDecimal() {
		// comma thousands, no dot, two decimal places
		$string = '525,000';
		$expected = '52500000';
		$actual = DevblocksPlatform::strParseDecimal($string, 2);
		$this->assertSame($expected, $actual);
		
		// No comma or dot, no decimal places
		$string = '525000';
		$expected = '525000';
		$actual = DevblocksPlatform::strParseDecimal($string, 0);
		$this->assertSame($expected, $actual);
		
		// Comma thousands, decimal dot, four decimal places
		$string = '525,000.00';
		$expected = '5250000000';
		$actual = DevblocksPlatform::strParseDecimal($string, 4);
		$this->assertSame($expected, $actual);
		
		// Comma decimal separator, space thousands separator, two decimal places
		$string = '525 000,00';
		$expected = '52500000';
		$actual = DevblocksPlatform::strParseDecimal($string, 2, ',');
		$this->assertSame($expected, $actual);
		
		// Overflow decimal places
		$string = '1.234';
		$expected = '123';
		$actual = DevblocksPlatform::strParseDecimal($string, 2);
		$this->assertSame($expected, $actual);
		
		// Invalid number
		$string = 'abcd';
		$expected = '0';
		$actual = DevblocksPlatform::strParseDecimal($string, 2, ',');
		$this->assertSame($expected, $actual);
		
		// Invalid number with decimal
		$string = 'abcd.ef';
		$expected = '0';
		$actual = DevblocksPlatform::strParseDecimal($string, 2, ',');
		$this->assertSame($expected, $actual);
		
		// Blank with two decimals
		$string = '';
		$expected = '0';
		$actual = DevblocksPlatform::strParseDecimal($string, 2, ',');
		$this->assertSame($expected, $actual);
	}
	
	public function testStrFormatDecimal() {
		$string = '52500000';
		$expected = '525,000.00';
		$actual = DevblocksPlatform::strFormatDecimal($string, 2, '.', ',');
		$this->assertSame($expected, $actual);
		
		$string = '525000';
		$expected = '525,000';
		$actual = DevblocksPlatform::strFormatDecimal($string, 0);
		$this->assertSame($expected, $actual);
		
		$string = '5250000000';
		$expected = '525 000.0000';
		$actual = DevblocksPlatform::strFormatDecimal($string, 4, '.', ' ');
		$this->assertSame($expected, $actual);
		
		$string = '52500000';
		$expected = '525 000,00';
		$actual = DevblocksPlatform::strFormatDecimal($string, 2, ',',' ');
		$this->assertSame($expected, $actual);
		
		// Blank with two decimals
		$string = '';
		$expected = '0.00';
		$actual = DevblocksPlatform::strFormatDecimal($string, 2, '.',',');
		$this->assertSame($expected, $actual);
		
		// Trailing zeroes
		$string = '250';
		$expected = '2.50';
		$actual = DevblocksPlatform::strFormatDecimal($string, 2, '.',',');
		$this->assertSame($expected, $actual);
		
		// Bitcoin
		$string = '1';
		$expected = '0.00000001';
		$actual = DevblocksPlatform::strFormatDecimal($string, 8, '.',',');
		$this->assertSame($expected, $actual);
	}
	
	public function testStrParseQueryString() {
		// Bracket fields
		
		$query = "expand=custom_&fields%5Bcustom_54%5D%5B%5D=MySQL&fields%5Bcustom_54%5D%5B%5D=PHP";

		$expected = [
			'expand' => 'custom_',
			'fields' => [
				'custom_54' => [
					'MySQL',
					'PHP',
				]
			]
		];
		
		$args = DevblocksPlatform::strParseQueryString($query);
		$this->assertEquals($expected, $args);
		
		// Multiple values
		
		$query = "field1=value1&field2=value2&field3=value3";
		
		$expected = [
			'field1' => 'value1',
			'field2' => 'value2',
			'field3' => 'value3',
		];
		
		$actual = DevblocksPlatform::strParseQueryString($query);
		$this->assertEquals($expected, $actual);
		
		// Nested fields
		
		$expected = [
			'fields' => [
				'title' => 'This is the title',
			]
		];
		
		$raw_body = DevblocksPlatform::arrayBuildQueryString($expected);
		
		$actual = DevblocksPlatform::strParseQueryString($raw_body);
		$this->assertEquals($expected, $actual);
		
		// Dots in field names (nested)
		
		$expected = [
			'record.id' => 'id',
			'record.name' => 'name',
		];
		
		$raw_body = DevblocksPlatform::arrayBuildQueryString($expected);
		
		$actual = DevblocksPlatform::strParseQueryString($raw_body);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrPrettyBytes() {
		// bytes
		$expected = '512 bytes';
		$actual = DevblocksPlatform::strPrettyBytes('512');
		$this->assertEquals($expected, $actual);
		
		// KB
		$expected = '768 KB';
		$actual = DevblocksPlatform::strPrettyBytes('768000');
		$this->assertEquals($expected, $actual);
		
		// MB
		$expected = '1 MB';
		$actual = DevblocksPlatform::strPrettyBytes('1000000');
		$this->assertEquals($expected, $actual);
		
		// MB (precision)
		$expected = '1.25 MB';
		$actual = DevblocksPlatform::strPrettyBytes('1252600', 2);
		$this->assertEquals($expected, $actual);
		
		// GB
		$expected = '2 GB';
		$actual = DevblocksPlatform::strPrettyBytes('2048000000');
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrPrettyTime() {
		// just now
		$expected = 'just now';
		$actual = DevblocksPlatform::strPrettyTime(time());
		$this->assertEquals($expected, $actual);
		
		// mins ago
		$expected = '5 mins ago';
		$actual = DevblocksPlatform::strPrettyTime(time()-300);
		$this->assertEquals($expected, $actual);
		
		// hours ago
		$expected = '2 hours ago';
		$actual = DevblocksPlatform::strPrettyTime(time()-7200);
		$this->assertEquals($expected, $actual);
		
		// days ago
		$expected = '5 days ago';
		$actual = DevblocksPlatform::strPrettyTime(time()-(86400*5));
		$this->assertEquals($expected, $actual);
		
		// months ago
		$expected = '9 months ago';
		$actual = DevblocksPlatform::strPrettyTime(time()-(86400*30*9));
		$this->assertEquals($expected, $actual);
		
		// days future
		$expected = '5 days';
		$actual = DevblocksPlatform::strPrettyTime(time()+(86400*5));
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrSecsToString() {
		// zero
		$expected = '0 secs';
		$actual = DevblocksPlatform::strSecsToString(0);
		$this->assertEquals($expected, $actual);
		
		// secs
		$expected = '30 secs';
		$actual = DevblocksPlatform::strSecsToString(30);
		$this->assertEquals($expected, $actual);
		
		// mins
		$expected = '5 mins';
		$actual = DevblocksPlatform::strSecsToString(300);
		$this->assertEquals($expected, $actual);
		
		// mins, secs
		$expected = '5 mins, 30 secs';
		$actual = DevblocksPlatform::strSecsToString(330);
		$this->assertEquals($expected, $actual);
		
		// hrs
		$expected = '2 hours';
		$actual = DevblocksPlatform::strSecsToString(7200);
		$this->assertEquals($expected, $actual);
		
		// hrs, mins (precision)
		$expected = '2 hours, 5 mins';
		$actual = DevblocksPlatform::strSecsToString(7530, 2);
		$this->assertEquals($expected, $actual);
		
		// weeks
		$expected = '1 week';
		$actual = DevblocksPlatform::strSecsToString(86400*7);
		$this->assertEquals($expected, $actual);
		
		// weeks
		$expected = '2 weeks, 1 day';
		$actual = DevblocksPlatform::strSecsToString(86400*15);
		$this->assertEquals($expected, $actual);
		
		// months
		$expected = '6 months';
		$actual = DevblocksPlatform::strSecsToString(86400*6*30);
		$this->assertEquals($expected, $actual);
		
		// months, weeks, days
		$expected = '6 months, 2 weeks, 1 day';
		$actual = DevblocksPlatform::strSecsToString(86400*((6*30)+15));
		$this->assertEquals($expected, $actual);
		
		// years
		$expected = '2 years';
		$actual = DevblocksPlatform::strSecsToString(86400*365*2, 1);
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrToHyperlinks() {
		$expected = '<a href="http://www.example.com" target="_blank" rel="noopener noreferrer">http://www.example.com</a>';
		$actual = DevblocksPlatform::strToHyperlinks('http://www.example.com');
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrToPermalink() {
		// With defaults
		$expected = 'devs-1000-ways-to-improve-sales';
		$actual = DevblocksPlatform::strLower(DevblocksPlatform::strToPermalink('Devs: 1000 Ways to Improve Sales'));
		$this->assertEquals($expected, $actual);
		
		// With underscores
		$expected = 'devs_1000_ways_to_improve_sales';
		$actual = DevblocksPlatform::strLower(DevblocksPlatform::strToPermalink('Devs: 1000 Ways to Improve Sales!', '_'));
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrToRegexp() {
		// Prefix
		$expected = '/^prefix(.*?)$/i';
		$actual = DevblocksPlatform::strToRegExp('prefix*');
		$this->assertEquals($expected, $actual);
		
		// Suffix
		$expected = '/^(.*?)suffix$/i';
		$actual = DevblocksPlatform::strToRegExp('*suffix');
		$this->assertEquals($expected, $actual);
		
		// Partial
		$expected = '/^(.*?)partial(.*?)$/i';
		$actual = DevblocksPlatform::strToRegExp('*partial*');
		$this->assertEquals($expected, $actual);
	}
	
	public function testStrUnidecode() {
		// Chinese
		$expected = 'Zhuang Zi ';
		$actual = DevblocksPlatform::strUnidecode('莊子');
		$this->assertEquals($expected, $actual);
		
		// Drop or convert accents
		$expected = 'voila, el bano, uber, Francois, Strasse';
		$actual = DevblocksPlatform::strUnidecode('voilà, el baño, über, François, Straße');
		$this->assertEquals($expected, $actual);
	}
	
}