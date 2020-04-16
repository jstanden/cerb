<?php
class CerberusMailTest extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	public function testWriteRfcAddress() {
		// No personal
		$expected = 'customer@example.com';
		$actual = CerberusMail::writeRfcAddress('customer@example.com', null);
		$this->assertEquals($expected, $actual);
		
		// Simple personal
		$expected = 'Customer <customer@example.com>';
		$actual = CerberusMail::writeRfcAddress('customer@example.com', 'Customer');
		$this->assertEquals($expected, $actual);
		
		// Dot in personal
		$expected = '"John Q. Customer" <john@example.com>';
		$actual = CerberusMail::writeRfcAddress('john@example.com', 'John Q. Customer');
		$this->assertEquals($expected, $actual);
		
		// Nested quotes
		$expected = '"Customer, James \"Jim\"" <jim@example.com>';
		$actual = CerberusMail::writeRfcAddress('jim@example.com', 'Customer, James "Jim"');
		$this->assertEquals($expected, $actual);
	}
	
	public function testParseRfcAddresses() {
		// Singular with no personal names
		$expected = [
			'jeff@example.com' => [
				'full_email' => 'jeff@example.com',
				'email' => 'jeff@example.com',
				'mailbox' => 'jeff',
				'host' => 'example.com',
				'personal' => '',
			],
		];
		$actual = CerberusMail::parseRfcAddresses('jeff@example.com');
		$this->assertEquals($expected, $actual);
		
		// Singular with no personal nor host
		$expected = [];
		$actual = CerberusMail::parseRfcAddresses('jeff');
		$this->assertEquals($expected, $actual);
		
		// Multiple with no personal names and mixed delimiters
		$expected = [
			'customer@example.com' => [
				'full_email' => 'customer@example.com',
				'email' => 'customer@example.com',
				'mailbox' => 'customer',
				'host' => 'example.com',
				'personal' => '',
			],
			'boss@example.com' => [
				'full_email' => 'boss@example.com',
				'email' => 'boss@example.com',
				'mailbox' => 'boss',
				'host' => 'example.com',
				'personal' => '',
			],
			'partner@example.com' => [
				'full_email' => 'partner@example.com',
				'email' => 'partner@example.com',
				'mailbox' => 'partner',
				'host' => 'example.com',
				'personal' => '',
			],
		];
		$actual = CerberusMail::parseRfcAddresses('customer@example.com, boss@example.com; partner@example.com');
		$this->assertEquals($expected, $actual);
		
		// Multiple with mixed personal names and mixed delimiters
		$expected = [
			'customer@example.com' => [
				'full_email' => 'Customer Name <customer@example.com>',
				'email' => 'customer@example.com',
				'mailbox' => 'customer',
				'host' => 'example.com',
				'personal' => 'Customer Name',
			],
			'boss@example.com' => [
				'full_email' => 'Boss <boss@example.com>',
				'email' => 'boss@example.com',
				'mailbox' => 'boss',
				'host' => 'example.com',
				'personal' => 'Boss',
			],
			'partner@example.com' => [
				'full_email' => 'partner@example.com',
				'email' => 'partner@example.com',
				'mailbox' => 'partner',
				'host' => 'example.com',
				'personal' => '',
			],
		];
		$actual = CerberusMail::parseRfcAddresses('Customer Name <customer@example.com>, "Boss" <boss@example.com>; partner@example.com');
		$this->assertEquals($expected, $actual);
		
		// Quotes
		$expected = [
			'john@example.com' => [
				'full_email' => '"John Q. Customer" <john@example.com>',
				'email' => 'john@example.com',
				'mailbox' => 'john',
				'host' => 'example.com',
				'personal' => 'John Q. Customer',
			],
		];
		$actual = CerberusMail::parseRfcAddresses('"John Q. Customer" <john@example.com>');
		$this->assertEquals($expected, $actual);
		
		// Nested quotes
		$expected = [
			'jim@example.com' => [
				'full_email' => '"Customer, James \"Jim\"" <jim@example.com>',
				'email' => 'jim@example.com',
				'mailbox' => 'jim',
				'host' => 'example.com',
				'personal' => 'Customer, James "Jim"',
			],
		];
		$actual = CerberusMail::parseRfcAddresses('"Customer, James \"Jim\"" <jim@example.com>');
		$this->assertEquals($expected, $actual);
	}
	
	public function testDecodeMimeHeader() {
		// ISO-8859-1 quoted-printable with underscores
		$expected = 'Keld Jørn Simonsen <keld@example.com>';
		$actual = CerberusMail::decodeMimeHeader("=?ISO-8859-1?Q?Keld=20J=F8rn=20Simonsen?= <keld@example.com>");
		$this->assertEquals($expected, $actual);
	}
};
