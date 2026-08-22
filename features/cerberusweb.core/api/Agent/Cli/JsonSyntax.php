<?php
namespace Cerb\Agent\Cli;

/**
 * Where a JSON document first stops being JSON.
 *
 * PHP's `json_decode()` is the authority on valid/invalid, but `json_last_error_msg()` says only "Syntax
 * error" with no position, which is close to useless to anyone -- and worse than useless to a model, which
 * will re-emit the whole document guessing at what to change.
 *
 * So this NEVER decides validity. It runs only after `json_decode()` has already failed, purely to locate
 * what failed, and if its own scan finds nothing wrong the caller falls back to reporting PHP's message
 * with no line. That composition is what makes it safe: a disagreement can cost a location, never a verdict.
 *
 * Strict RFC 8259, matching what `json_decode()` accepts: no comments, no trailing commas, no single quotes,
 * no unquoted keys, no leading `+`, no leading zeroes, no hex. Each of those is a real model mistake, and
 * each produces a message naming it.
 *
 * Deliberately dependency-free (no DevblocksPlatform, no services) so it can move to the platform unchanged
 * the moment a second caller wants it -- an editor marking up its own gutter, most likely.
 */
class JsonSyntax {
	/**
	 * @return array|null ['line'=>int (1-based), 'column'=>int (1-based), 'message'=>string], or null when
	 *                    the scan found nothing to report
	 */
	public static function locate(string $json) : ?array {
		$len = strlen($json);
		$i = 0;

		$fail = function(int $at, string $message) use ($json) : array {
			$at = max(0, min($at, strlen($json)));
			$before = substr($json, 0, $at);
			$line = substr_count($before, "\n") + 1;
			$last_break = strrpos($before, "\n");

			return [
				'line' => $line,
				'column' => (false === $last_break) ? $at + 1 : $at - $last_break,
				'message' => $message,
			];
		};

		self::_skipWhitespace($json, $i, $len);

		if($i >= $len)
			return $fail($i, 'The document is empty');

		if(null !== ($err = self::_scanValue($json, $i, $len, $fail, 0)))
			return $err;

		self::_skipWhitespace($json, $i, $len);

		// A second value after a complete one is the classic result of pasting two objects together, or of
		// forgetting the `[` that would have made them a list.
		if($i < $len)
			return $fail($i, sprintf("Unexpected `%s` after the end of the document -- a JSON document holds exactly one value", self::_glimpse($json, $i)));

		return null;
	}

	private static function _skipWhitespace(string $json, int &$i, int $len) : void {
		while($i < $len && (' ' === $json[$i] || "\t" === $json[$i] || "\n" === $json[$i] || "\r" === $json[$i]))
			$i++;
	}

	/** A short, safe rendering of what is at the cursor, for a message. */
	private static function _glimpse(string $json, int $i) : string {
		$chunk = substr($json, $i, 12);
		$chunk = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $chunk);

		return trim($chunk);
	}

	/** Guards against a pathological nest; json_decode's own default depth is 512. */
	private const MAX_DEPTH = 512;

	private static function _scanValue(string $json, int &$i, int $len, callable $fail, int $depth) : ?array {
		if($depth > self::MAX_DEPTH)
			return $fail($i, 'Nested too deeply');

		self::_skipWhitespace($json, $i, $len);

		if($i >= $len)
			return $fail($i, 'The document ends where a value was expected');

		$c = $json[$i];

		if('{' === $c)
			return self::_scanObject($json, $i, $len, $fail, $depth);

		if('[' === $c)
			return self::_scanArray($json, $i, $len, $fail, $depth);

		if('"' === $c)
			return self::_scanString($json, $i, $len, $fail);

		if("'" === $c)
			return $fail($i, "JSON strings use double quotes, not `'`");

		if('-' === $c || ($c >= '0' && $c <= '9'))
			return self::_scanNumber($json, $i, $len, $fail);

		foreach(['true', 'false', 'null'] as $literal) {
			if(0 === substr_compare($json, $literal, $i, strlen($literal))) {
				$i += strlen($literal);
				return null;
			}
		}

		// Capitalized literals are the common one here (`True`, `None` from a Python-shaped mental model).
		foreach(['True' => 'true', 'False' => 'false', 'None' => 'null', 'NULL' => 'null', 'TRUE' => 'true', 'FALSE' => 'false'] as $wrong => $right) {
			if(0 === substr_compare($json, $wrong, $i, strlen($wrong)))
				return $fail($i, sprintf('`%s` is not JSON -- write `%s`', $wrong, $right));
		}

		return $fail($i, sprintf('Expected a value, found `%s`', self::_glimpse($json, $i)));
	}

	private static function _scanObject(string $json, int &$i, int $len, callable $fail, int $depth) : ?array {
		$open_at = $i;
		$i++; // {

		self::_skipWhitespace($json, $i, $len);

		if($i < $len && '}' === $json[$i]) {
			$i++;
			return null;
		}

		while(true) {
			self::_skipWhitespace($json, $i, $len);

			if($i >= $len)
				return $fail($open_at, 'This `{` is never closed');

			if('}' === $json[$i])
				return $fail($i, 'Trailing comma before `}`');

			if('"' !== $json[$i]) {
				if("'" === $json[$i])
					return $fail($i, "A key must be in double quotes, not `'`");

				return $fail($i, sprintf('A key must be a double-quoted string, found `%s`', self::_glimpse($json, $i)));
			}

			if(null !== ($err = self::_scanString($json, $i, $len, $fail)))
				return $err;

			self::_skipWhitespace($json, $i, $len);

			if($i >= $len || ':' !== $json[$i])
				return $fail($i, ($i < $len && '=' === $json[$i]) ? 'A key and its value are separated by `:`, not `=`' : 'Expected `:` after the key');

			$i++;

			if(null !== ($err = self::_scanValue($json, $i, $len, $fail, $depth + 1)))
				return $err;

			self::_skipWhitespace($json, $i, $len);

			if($i >= $len)
				return $fail($open_at, 'This `{` is never closed');

			if('}' === $json[$i]) {
				$i++;
				return null;
			}

			if(',' !== $json[$i])
				return $fail($i, sprintf('Expected `,` or `}` after a value, found `%s`', self::_glimpse($json, $i)));

			$i++;
		}
	}

	private static function _scanArray(string $json, int &$i, int $len, callable $fail, int $depth) : ?array {
		$open_at = $i;
		$i++; // [

		self::_skipWhitespace($json, $i, $len);

		if($i < $len && ']' === $json[$i]) {
			$i++;
			return null;
		}

		while(true) {
			self::_skipWhitespace($json, $i, $len);

			if($i < $len && ']' === $json[$i])
				return $fail($i, 'Trailing comma before `]`');

			if(null !== ($err = self::_scanValue($json, $i, $len, $fail, $depth + 1)))
				return $err;

			self::_skipWhitespace($json, $i, $len);

			if($i >= $len)
				return $fail($open_at, 'This `[` is never closed');

			if(']' === $json[$i]) {
				$i++;
				return null;
			}

			if(',' !== $json[$i])
				return $fail($i, sprintf('Expected `,` or `]` after a value, found `%s`', self::_glimpse($json, $i)));

			$i++;
		}
	}

	private static function _scanString(string $json, int &$i, int $len, callable $fail) : ?array {
		$open_at = $i;
		$i++; // "

		while($i < $len) {
			$c = $json[$i];

			if('"' === $c) {
				$i++;
				return null;
			}

			if("\\" === $c) {
				$i++;

				if($i >= $len)
					break;

				$esc = $json[$i];

				if('u' === $esc) {
					if($i + 4 >= $len || !ctype_xdigit(substr($json, $i + 1, 4)))
						return $fail($i - 1, 'A `\\u` escape needs exactly four hex digits');

					$i += 5;
					continue;
				}

				if(!str_contains('"\\/bfnrt', $esc))
					return $fail($i - 1, sprintf('`\\%s` is not a valid escape -- a literal backslash is written `\\\\`', $esc));

				$i++;
				continue;
			}

			// A raw newline inside a string is nearly always an unterminated string rather than an attempt
			// at a multi-line one, so it reads better reported at the opening quote.
			if("\n" === $c)
				return $fail($open_at, 'This string is never closed -- a line break inside one must be written `\\n`');

			if($c < ' ')
				return $fail($i, sprintf('A raw control character (0x%02X) must be escaped inside a string', ord($c)));

			$i++;
		}

		return $fail($open_at, 'This string is never closed');
	}

	private static function _scanNumber(string $json, int &$i, int $len, callable $fail) : ?array {
		$start = $i;

		if('-' === $json[$i])
			$i++;

		if($i >= $len || !ctype_digit($json[$i]))
			return $fail($start, 'Expected a digit after `-`');

		if('0' === $json[$i]) {
			$i++;

			if($i < $len && ctype_digit($json[$i]))
				return $fail($start, 'A number must not have a leading zero');

		} else {
			while($i < $len && ctype_digit($json[$i]))
				$i++;
		}

		if($i < $len && '.' === $json[$i]) {
			$i++;

			if($i >= $len || !ctype_digit($json[$i]))
				return $fail($i, 'Expected a digit after the decimal point');

			while($i < $len && ctype_digit($json[$i]))
				$i++;
		}

		if($i < $len && ('e' === $json[$i] || 'E' === $json[$i])) {
			$i++;

			if($i < $len && ('+' === $json[$i] || '-' === $json[$i]))
				$i++;

			if($i >= $len || !ctype_digit($json[$i]))
				return $fail($i, 'Expected a digit in the exponent');

			while($i < $len && ctype_digit($json[$i]))
				$i++;
		}

		return null;
	}
}
