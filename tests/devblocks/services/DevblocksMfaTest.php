<?php
class DevblocksMfaTest extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testGetMultiFactorOtpFromSeed() {
		$mfa = DevblocksPlatform::services()->mfa();
		$seed = 'BVFOHCBRITFVRLUFVOPBAS5V';
		$at = 1566497107;
		
		$expected = [460390,191149];
		$actual = $mfa->getMultiFactorOtpFromSeed($seed,2, $at);
		
		$this->assertEquals($expected, $actual);
	}
	
	function testIsAuthorized() {
		$mfa = DevblocksPlatform::services()->mfa();
		$seed = 'BVFOHCBRITFVRLUFVOPBAS5V';
		$at = 1566497839;
		
		// Correct current
		$actual = $mfa->isAuthorized(93245, $seed, $at);
		$this->assertTrue($actual);
		
		// Correct previous 30s window leniency
		$actual = $mfa->isAuthorized(326071, $seed, $at);
		$this->assertTrue($actual);
		
		// Incorrect
		$actual = $mfa->isAuthorized(123456, $seed, $at);
		$this->assertNotTrue($actual);
	}
	
	function testReturnSingleCode() {
		$mfa = DevblocksPlatform::services()->mfa();
		$seed = 'BVFOHCBRITFVRLUFVOPBAS5V';
		$at = 1566497839;
		
		// Correct current
		$expected = 93245;
		$actual = $mfa->getMultiFactorOtpFromSeed($seed, 1, $at);
		$this->assertEquals($expected, $actual);
	}
}