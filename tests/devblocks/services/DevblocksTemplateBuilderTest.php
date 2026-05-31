<?php
use PHPUnit\Framework\TestCase;

class DevblocksTemplateBuilderTest extends TestCase {
	// Dictionary variables live in the DevblocksDictionaryDelegate, not Twig's
	// $context, so they're only reachable via the undefined-variable callback.
	// The short ternary (?:) and null-coalesce (??) set always_defined on their
	// left operand, which must still fall back to the dictionary.
	function testShortTernaryResolvesDictionaryVariable() {
		$tpl = DevblocksPlatform::services()->templateBuilder();
		$dict = [
			'prompt_email' => 'support@cerb.example',
			'some_default' => 'mailbox@host',
		];

		$expected = 'support@cerb.example';
		$actual = $tpl->build('{{prompt_email ?: some_default}}', $dict);
		$this->assertEquals($expected, $actual);
	}

	function testShortTernaryFallsThroughWhenLeftMissing() {
		$tpl = DevblocksPlatform::services()->templateBuilder();
		$dict = [
			'prompt_email' => 'support@cerb.example',
			'some_default' => 'mailbox@host',
		];

		$expected = 'mailbox@host';
		$actual = $tpl->build('{{missing ?: some_default}}', $dict);
		$this->assertEquals($expected, $actual);
	}

	function testNullCoalesceResolvesDictionaryVariable() {
		$tpl = DevblocksPlatform::services()->templateBuilder();
		$dict = [
			'prompt_email' => 'support@cerb.example',
			'some_default' => 'mailbox@host',
		];

		$expected = 'support@cerb.example';
		$actual = $tpl->build('{{prompt_email ?? some_default}}', $dict);
		$this->assertEquals($expected, $actual);
	}

	function testNullCoalesceFallsThroughWhenLeftMissing() {
		$tpl = DevblocksPlatform::services()->templateBuilder();
		$dict = [
			'prompt_email' => 'support@cerb.example',
			'some_default' => 'mailbox@host',
		];

		$expected = 'mailbox@host';
		$actual = $tpl->build('{{missing ?? some_default}}', $dict);
		$this->assertEquals($expected, $actual);
	}
}
