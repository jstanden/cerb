<?php
use PHPUnit\Framework\TestCase;

class DevblocksSearchTest extends TestCase {
	function testGetTokensFromText() {
		$search = DevblocksPlatform::services()->search();

		// Lowercase, punctuation trimmed, stop words removed
		$expected = ['quick', 'brown', 'fox-trot', 'jumped'];
		$actual = $search->getTokensFromText("The quick, brown FOX-TROT jumped!");
		$this->assertEquals($expected, array_values($actual));

		// Accented letters are kept intact
		$expected = ['naïve', 'résumé', 'søster'];
		$actual = $search->getTokensFromText("naïve résumé søster");
		$this->assertEquals($expected, array_values($actual));

		// Emoji are not alphanumeric and never tokenize
		$expected = ['thanks'];
		$actual = $search->getTokensFromText("Thanks! 👍 😀");
		$this->assertEquals($expected, array_values($actual));
	}

	function testGetTokensFromTextStrips4ByteChars() {
		$search = DevblocksPlatform::services()->search();

		// 4-byte letters (e.g. Mathematical Alphanumeric Symbols) can't be stored
		// in utf8mb3 token storage, so they're stripped before hashing
		$expected = ['hello'];
		$actual = $search->getTokensFromText("Hello 𝐅𝐫𝐞𝐞 𝕄𝕠𝕟𝕖𝕪");
		$this->assertEquals($expected, array_values($actual));

		// Mixed tokens keep their 1-3 byte chars (BMP CJK is 3-byte)
		$expected = ['漢字'];
		$actual = $search->getTokensFromText("𠀀漢字");
		$this->assertEquals($expected, array_values($actual));

		// No empty tokens remain after stripping
		$actual = $search->getTokensFromText("𝐅𝐫𝐞𝐞 𝕄𝕠𝕟𝕖𝕪 𝒮𝓅𝒶𝓂");
		$this->assertEquals([], array_values($actual));
	}

	function testGetTokensFromTextWildcards() {
		$search = DevblocksPlatform::services()->search();

		// Wildcards are only kept when allowed
		$expected = ['serv'];
		$actual = $search->getTokensFromText("serv*");
		$this->assertEquals($expected, array_values($actual));

		$expected = ['serv*'];
		$actual = $search->getTokensFromText("serv*", allow_wildcards: true);
		$this->assertEquals($expected, array_values($actual));

		// Bare wildcards are never allowed
		$expected = ['token'];
		$actual = $search->getTokensFromText("* token", allow_wildcards: true);
		$this->assertEquals($expected, array_values($actual));
	}

	function testGetTokensFromTextStemming() {
		$search = DevblocksPlatform::services()->search();

		// Stem markers are only kept (at the end) when allowed
		$expected = ['jump'];
		$actual = $search->getTokensFromText("jump~");
		$this->assertEquals($expected, array_values($actual));

		$expected = ['jump~'];
		$actual = $search->getTokensFromText("ju~mp", allow_stemming: true);
		$this->assertEquals($expected, array_values($actual));
	}

	function testExpandTokens() {
		$search = DevblocksPlatform::services()->search();

		// Compound tokens are also indexed by their parts
		$expected = ['foo.bar', 'foo', 'bar'];
		$actual = $search->expandTokens(['foo.bar']);
		$this->assertEquals($expected, array_values($actual));

		// Simple tokens are unchanged
		$expected = ['foo'];
		$actual = $search->expandTokens(['foo']);
		$this->assertEquals($expected, array_values($actual));
	}

	function testIndexTokens() {
		$search = DevblocksPlatform::services()->search();
		$strings = DevblocksPlatform::services()->string();

		$actual = $search->indexTokens(['alpha', 'beta', 'alpha', 'gamma']);

		// Keyed by xxh3 token hash, valued by [token, term frequency]
		$expected = [
			$strings->xxh3('alpha') => ['alpha', 0.5],
			$strings->xxh3('beta') => ['beta', 0.25],
			$strings->xxh3('gamma') => ['gamma', 0.25],
		];
		$this->assertEquals($expected, $actual);
	}

	function testTruncateOnWhitespace() {
		$search = DevblocksPlatform::services()->search();

		// Truncation extends to the next whitespace
		$expected = 'one two three';
		$actual = $search->truncateOnWhitespace('one two three four', 9);
		$this->assertEquals($expected, $actual);

		// Shorter strings are unchanged
		$expected = 'one two';
		$actual = $search->truncateOnWhitespace('one two', 100);
		$this->assertEquals($expected, $actual);
	}
}
