<?php
use PHPUnit\Framework\TestCase;

/**
 * Locks in the behavior of Cerb's lazy bare-name resolution, which used to live
 * in a Twig fork (registerUndefinedVariableCallback + a NameExpression::compile()
 * patch) and now lives in Cerb-owned classes in template_builder.php:
 *
 *   _DevblocksTwigEnvironment            -- holds the dictionary callback
 *   _DevblocksContextVariable            -- compiles the context-then-dictionary fallback
 *   _DevblocksUndefinedVariableNodeVisitor -- swaps bare reads for the above
 *
 * These tests pin the contract against stock upstream Twig so a future Twig bump
 * (e.g. a new operator node like the `?:` / `??` regression) surfaces here.
 */
class DevblocksTemplateBuilderTest extends TestCase {
	private function build($template, array $dict) {
		return DevblocksPlatform::services()->templateBuilder()->build($template, $dict);
	}

	function testBareNameRead() {
		// Bare name resolves lazily from the dictionary, not just from $context
		$this->assertEquals('support@cerb.example', $this->build('{{prompt_email}}', ['prompt_email' => 'support@cerb.example']));
	}

	function testBareNameMissingIsEmpty() {
		// A name absent from the dictionary resolves to null (empty string output)
		$this->assertEquals('', $this->build('{{missing}}', []));
	}

	function testElvisOperatorWithPresentValue() {
		// The original regression: `?:` set always_defined on its operand and
		// routed it around the fork's fallback. Must yield the left value.
		$this->assertEquals('support@cerb.example', $this->build('{{prompt_email ?: "fallback"}}', ['prompt_email' => 'support@cerb.example']));
	}

	function testElvisOperatorWithMissingValue() {
		$this->assertEquals('fallback', $this->build('{{missing ?: "fallback"}}', []));
	}

	function testNullCoalesceWithPresentValue() {
		// Same regression class as `?:` -- NullCoalesceBinary clones its operand.
		$this->assertEquals('support@cerb.example', $this->build('{{prompt_email ?? "fallback"}}', ['prompt_email' => 'support@cerb.example']));
	}

	function testNullCoalesceWithMissingValue() {
		$this->assertEquals('fallback', $this->build('{{missing ?? "fallback"}}', []));
	}

	function testDefinedTestTrueForDictValue() {
		// `is defined` state must transfer through the node swap (enableDefinedTest)
		$this->assertEquals('y', $this->build("{{ prompt_email is defined ? 'y' : 'n' }}", ['prompt_email' => 'support@cerb.example']));
	}

	function testDefinedTestFalseForMissing() {
		$this->assertEquals('n', $this->build("{{ missing is defined ? 'y' : 'n' }}", []));
	}

	function testDefaultFilterFallsBack() {
		$this->assertEquals('x', $this->build("{{ missing|default('x') }}", []));
	}

	function testDefaultFilterKeepsValue() {
		$this->assertEquals('support@cerb.example', $this->build("{{ prompt_email|default('x') }}", ['prompt_email' => 'support@cerb.example']));
	}

	function testIfConditionOnBareName() {
		$this->assertEquals('yes', $this->build('{% if prompt_email %}yes{% else %}no{% endif %}', ['prompt_email' => 'support@cerb.example']));
		$this->assertEquals('no', $this->build('{% if missing %}yes{% else %}no{% endif %}', []));
	}

	function testForLoopVariableNotDictResolved() {
		// Loop vars are TempNameExpression-based, not ContextVariable -- excluded from the swap
		$this->assertEquals('a,b,c,', $this->build('{% for x in items %}{{x}},{% endfor %}', ['items' => ['a', 'b', 'c']]));
	}

	function testNameWithUnderscoresResolves() {
		// A multi-segment bare name present in the dictionary resolves directly
		$this->assertEquals('found', $this->build('{{deeply_nested_key}}', ['deeply_nested_key' => 'found']));
	}

	function testLocalSetWinsOverDict() {
		// A {% set %} target writes to $context, which the fallback reads first
		$this->assertEquals('override', $this->build("{% set prompt_email = 'override' %}{{prompt_email}}", ['prompt_email' => 'support@cerb.example']));
	}

	function testAttributeAccessOnNestedDict() {
		// Attribute access ({{rec.name}}) still works; `rec` is the swapped bare read
		$this->assertEquals('Cerb', $this->build('{{rec.name}}', ['rec' => ['name' => 'Cerb']]));
	}

	function testCerbPlaceholdersListReturnsDictionary() {
		// Exercises function_cerb_placeholders_list() -> getUndefinedVariableCallbacks()
		// on the _DevblocksTwigEnvironment subclass
		$out = $this->build('{% set p = cerb_placeholders_list() %}{{ p|length > 0 ? "has" : "empty" }}', ['prompt_email' => 'support@cerb.example', 'foo' => 'bar']);
		$this->assertEquals('has', $out);
	}
}
