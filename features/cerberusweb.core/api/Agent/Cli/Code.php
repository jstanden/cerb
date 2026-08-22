<?php
namespace Cerb\Agent\Cli;

use CerberusApplication;
use DevblocksPlatform;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * `cerb code` -- check a document before something else has to.
 *
 * A model writing KATA or JSON has no feedback loop. It writes a whole automation, saves it, and finds out
 * from a save error what the fifth line got wrong -- then rewrites the whole thing, because it cannot see
 * which part was fine. That is expensive in turns and it is expensive in confidence.
 *
 * These commands close the loop on a scratch file: write to /tmp, lint, fix, lint again, and only then save.
 * KATA validation runs the SAME `kata()->validate()` against the SAME schemas the record DAOs use on save,
 * so passing here means the save will not be rejected for that reason -- a second implementation would let
 * an agent be told a document is fine that Cerb then refuses.
 *
 * Read-only. Nothing here writes, and `json format` prints rather than rewriting the file, so an agent that
 * wants the formatted version writes it back itself through the normal `write` path and its mount's mode.
 */
class Code implements Command {
	use Output;

	public function getName() : string {
		return 'code';
	}

	public function getSummary() : string {
		return 'Check KATA and JSON before you save it -- syntax, schema, and formatting';
	}

	public function getRequiredScope(array $args) : string {
		// Everything here reads a document the caller already has. A future `cerb code fix` writes, and
		// charges `code:write`.
		return 'code:read';
	}

	public function getHelp() : string {
		return implode("\n", [
			"cerb code -- check a document before you commit to it.",
			'',
			"  cerb code kata lint <path> [--schema <name>]",
			"      Parse KATA and report every problem, each with its line. With --schema it also validates",
			"      against that schema -- the same check the record's own save performs, so passing here",
			"      means the save won't be rejected for it. A SYNTAX error is reported alone: a document that",
			"      doesn't parse has no tree for anything else to check.",
			'',
			"  cerb code kata schemas",
			"      The schema names --schema accepts in this install.",
			'',
			"  cerb code json lint <path>",
			"      Parse JSON and report where it stops being JSON, with the line, the column, and what is",
			"      wrong -- a trailing comma, a single-quoted key, a missing comma.",
			'',
			"  cerb code json format <path>",
			"      Print the document in Cerb's own JSON formatting. Refuses to format an invalid document,",
			"      and reports it the way `json lint` would.",
			'',
			"Flags:",
			"  --payload          Check the out-of-band content instead of a file, for something you haven't",
			"                     written anywhere yet. (The `content` tool parameter; the Payload box in the",
			"                     Setup terminal.) Give either a path or --payload, not both.",
			"  --format md|json   md (default) is a report; json is the issue rows as JSON.",
			'',
			"A document that FAILS is not a command that failed -- the check ran and answered, so the issues",
			"pipe like any other rows:",
			"  cerb code kata lint /tmp/a.kata --schema automation | issues|column(\"line\")",
			'',
			"Typical use: `write /tmp/draft.kata`, then `cerb code kata lint /tmp/draft.kata --schema",
			"automation`, then fix and repeat until it passes.",
		]);
	}

	public function exec(array $args, array $flags, array $context = []) : array {
		$language = DevblocksPlatform::strLower(strval(array_shift($args) ?? ''));

		return match($language) {
			// A bare namespace ANSWERS with its usage rather than pointing at it. Telling a model to go read
			// the help costs a whole extra tool round trip to learn something we already had in hand.
			'' => $this->_ok($this->getHelp()),
			'kata' => $this->_kata($args, $flags, $context),
			'json' => $this->_json_($args, $flags, $context),
			// Same reasoning, but it IS an error: say what went wrong, then answer the obvious next question.
			default => $this->_fail(sprintf("cerb code: %s: unknown language.\n\n%s", $language, $this->getHelp())),
		};
	}

	// -- kata ----------------------------------------------------------------

	private function _kata(array $args, array $flags, array $context) : array {
		$action = DevblocksPlatform::strLower(strval(array_shift($args) ?? ''));

		return match($action) {
			'schemas' => $this->_kataSchemas($flags),
			'lint', '' => $this->_kataLint($args, $flags, $context),
			default => $this->_fail(sprintf("cerb code kata: %s: unknown action. Try `lint` or `schemas`.", $action)),
		};
	}

	private function _kataSchemas(array $flags) : array {
		$rows = array_map(fn($name) => ['schema' => $name], self::_schemaNames());

		if($this->_wantsJson($flags))
			return $this->_json($rows, 'schemas');

		$out = [
			"# KATA schemas in this install",
			'',
			"Pass one to `cerb code kata lint <path> --schema <name>`. Dashes and underscores are ignored, so",
			"`automationPolicy`, `automation-policy`, and `automation_policy` all name the same schema.",
			'',
			$this->_table(['schema'], array_map(fn($r) => [$r['schema']], $rows)),
		];

		return $this->_ok(implode("\n", $out), $rows, 'schemas');
	}

	private function _kataLint(array $args, array $flags, array $context) : array {
		$input = $this->_input($args, $flags, $context, 'cerb code kata lint <path> [--schema <name>]');

		if(isset($input['error']))
			return $this->_fail($input['error']);

		$schema_kata = null;
		$schema_name = trim(strval($flags['schema'] ?? ''));

		if(true === ($flags['schema'] ?? null))
			return $this->_fail(sprintf("cerb code kata lint: --schema needs a name. Available: %s.", implode(', ', self::_schemaNames())));

		if('' !== $schema_name) {
			if(is_null($schema_kata = self::_schemaKata($schema_name, $resolved_name)))
				return $this->_fail(sprintf("cerb code kata lint: `%s` is not a schema in this install. Available: %s.",
					$schema_name,
					implode(', ', self::_schemaNames())
				));

			$schema_name = $resolved_name;
		}

		$kata = DevblocksPlatform::services()->kata();

		$error = null;
		$issues = [];

		// Without a schema this is a syntax check, which parse() alone answers. validate() collects every
		// schema problem it finds, each already carrying the line its key path maps to.
		if($schema_kata) {
			$kata->validate($input['content'], $schema_kata, $error, null, $issues);

		} else if(false === $kata->parse($input['content'], $error)) {
			$issues = [['message' => strval($error), 'line' => self::_lineIn(strval($error)), 'path' => null]];
		}

		$label = sprintf('%s%s', $input['label'], $schema_name ? sprintf(' -- schema: %s', $schema_name) : '');

		if(!$issues)
			return $this->_clean(
				$input,
				$schema_name
					? sprintf("OK -- %s is valid KATA and matches the `%s` schema.", $input['label'], $schema_name)
					: sprintf("OK -- %s is valid KATA. Syntax only: pass --schema to check it against one (`cerb code kata schemas`).", $input['label']),
				$flags
			);

		return $this->_report($input, $label, array_map(fn($issue) => [
			// The line is rendered once, by _report. The parser and the scripting check embed their own.
			'message' => self::_stripLine(strval($issue['message'] ?? '')),
			'line' => is_null($issue['line'] ?? null) ? null : intval($issue['line']),
			'path' => $issue['path'] ?? null,
		], $issues), $flags);
	}

	/**
	 * Drop a `(line N)` the message carries itself. The parser and the scripting check embed one and the
	 * schema validator doesn't, so without this the two layers print it a different number of times.
	 */
	private static function _stripLine(string $error) : string {
		return trim(strval(preg_replace('/\s*\(line \d+\)/', '', $error, 1)));
	}

	private static function _lineIn(string $message) : ?int {
		$matches = [];

		return preg_match('/\(line (\d+)\)/', $message, $matches) ? intval($matches[1]) : null;
	}

	// -- json ----------------------------------------------------------------

	/** Named with a trailing underscore only because `_json()` is the Output trait's JSON responder. */
	private function _json_(array $args, array $flags, array $context) : array {
		$action = DevblocksPlatform::strLower(strval(array_shift($args) ?? ''));

		return match($action) {
			'lint', '' => $this->_jsonLint($args, $flags, $context),
			'format', 'pretty' => $this->_jsonFormat($args, $flags, $context),
			default => $this->_fail(sprintf("cerb code json: %s: unknown action. Try `lint` or `format`.", $action)),
		};
	}

	private function _jsonLint(array $args, array $flags, array $context) : array {
		$input = $this->_input($args, $flags, $context, 'cerb code json lint <path>');

		if(isset($input['error']))
			return $this->_fail($input['error']);

		[$message, $line, $column] = $this->_jsonError($input['content']);

		if(is_null($message))
			return $this->_clean($input, sprintf("OK -- %s is valid JSON.", $input['label']), $flags);

		return $this->_report($input, $input['label'], [['message' => $message, 'line' => $line, 'column' => $column]], $flags);
	}

	private function _jsonFormat(array $args, array $flags, array $context) : array {
		$input = $this->_input($args, $flags, $context, 'cerb code json format <path>');

		if(isset($input['error']))
			return $this->_fail($input['error']);

		[$message, $line, $column] = $this->_jsonError($input['content']);

		// Formatting an invalid document would decode to null and print `null` -- a silent, total loss. Say
		// what's wrong instead, in the same shape `json lint` would.
		if(!is_null($message))
			return $this->_report($input, $input['label'], [['message' => $message, 'line' => $line, 'column' => $column]], $flags);

		return $this->_ok(DevblocksPlatform::strFormatJson($input['content']));
	}

	/**
	 * Is this JSON, and if not, where does it stop being JSON?
	 *
	 * json_decode() is the authority on the verdict -- JsonSyntax only runs afterwards to locate a failure
	 * PHP already found, because json_last_error_msg() says "Syntax error" and nothing else. When the scan
	 * can't place it, the caller still gets PHP's message, without a line.
	 *
	 * @return array [?string $message, ?int $line, ?int $column]
	 */
	private function _jsonError(string $content) : array {
		json_decode($content, true);

		if(JSON_ERROR_NONE === json_last_error())
			return [null, null, null];

		$php_message = json_last_error_msg();

		if(!($located = JsonSyntax::locate($content)))
			return [$php_message, null, null];

		return [strval($located['message']), intval($located['line']), intval($located['column'])];
	}

	// -- shared --------------------------------------------------------------

	/**
	 * What to check: a file the host can read, or the out-of-band payload.
	 *
	 * @return array ['content'=>string, 'label'=>string] or ['error'=>string]
	 */
	private function _input(array $args, array $flags, array $context, string $usage) : array {
		$path = trim(strval($args[0] ?? ''));
		$wants_payload = !empty($flags['payload']);

		// Silently preferring one would make a stale path look like a fresh edit, or the reverse.
		if($wants_payload && '' !== $path)
			return ['error' => sprintf("%s: give either a path or --payload, not both.", $usage)];

		if($wants_payload) {
			$payload = strval($context['payload'] ?? '');

			if('' === trim($payload))
				return ['error' => "--payload: no content was supplied. Send the document out of band (the `content` tool parameter, or the Payload box), or give a path instead."];

			return ['content' => $payload, 'label' => 'the payload'];
		}

		if('' === $path)
			return ['error' => sprintf("Usage: %s. Write the document to /tmp first, or pass --payload.", $usage)];

		if(!is_callable($read = $context['read'] ?? null))
			return ['error' => "This host can't read files, so only --payload works here."];

		$error = null;
		$resolved = null;
		$content = $read($path, $error, $resolved);

		if(is_null($content))
			return ['error' => sprintf("%s: %s", $resolved ?: $path, $error ?: 'No such file')];

		return ['content' => $content, 'label' => strval($resolved ?: $path)];
	}

	/** A document with nothing wrong with it. `issues` is bound and empty, so a pipeline still works. */
	private function _clean(array $input, string $message, array $flags) : array {
		if($this->_wantsJson($flags))
			return $this->_json([], 'issues');

		return $this->_ok($message, [], 'issues');
	}

	/** More than this and the report lists them without excerpts; `data` still carries every one. */
	private const MAX_EXCERPTS = 1;

	/** A long list of problems is a document to rewrite, not to read line by line. */
	private const MAX_LISTED = 25;

	/**
	 * A document with something wrong with it.
	 *
	 * NOT flagged as a command error: the check ran and answered. Flagging it would short-circuit the
	 * pipeline (`Filesystem::exec()` refuses to pipe a failed command) and put the issue rows out of reach
	 * of the one caller most likely to want them structurally -- an editor marking up its own gutter.
	 *
	 * Sorted by line so the report reads down the document. `data` is sorted the same way, so what a
	 * pipeline sees and what the text shows are in the same order.
	 */
	private function _report(array $input, string $label, array $issues, array $flags) : array {
		usort($issues, fn($a, $b) => ($a['line'] ?? PHP_INT_MAX) <=> ($b['line'] ?? PHP_INT_MAX));

		$rows = array_map(fn($issue) => [
			'source' => $input['label'],
			'severity' => 'error',
			'line' => $issue['line'] ?? null,
			'column' => $issue['column'] ?? null,
			'path' => $issue['path'] ?? null,
			'message' => strval($issue['message'] ?? ''),
		], $issues);

		if($this->_wantsJson($flags))
			return $this->_json($rows, 'issues');

		$out = [sprintf("FAIL -- %s", $label), ''];

		// One problem gets the line in context, which is usually the whole answer. Several get a list, because
		// interleaving excerpts buries them and a reader is going to open the file anyway.
		if(count($rows) <= self::MAX_EXCERPTS) {
			$out[] = $this->_located($rows[0]);

			if(!is_null($rows[0]['line']) && ($excerpt = $this->_excerpt($input['content'], $rows[0]['line']))) {
				$out[] = '';
				$out[] = $excerpt;
			}

		} else {
			$out[] = sprintf("%d issues:", count($rows));
			$out[] = '';

			foreach(array_slice($rows, 0, self::MAX_LISTED) as $row)
				$out[] = sprintf("  %-9s %s",
					is_null($row['line']) ? '' : sprintf('line %d', $row['line']),
					$row['message']
				);

			if(count($rows) > self::MAX_LISTED) {
				$out[] = '';
				$out[] = sprintf("...and %d more. Fix these first -- a schema error often explains the ones under it.",
					count($rows) - self::MAX_LISTED
				);
			}
		}

		return $this->_ok(implode("\n", $out), $rows, 'issues');
	}

	private function _located(array $row) : string {
		if(is_null($row['line']))
			return strval($row['message']);

		return sprintf("%s  (line %d%s)",
			$row['message'],
			$row['line'],
			is_null($row['column'] ?? null) ? '' : sprintf(", column %d", $row['column'])
		);
	}

	/** The offending line with a little context, marked. Cheaper than making the reader re-read the file. */
	private function _excerpt(string $content, int $line, int $context_lines = 2) : string {
		$lines = preg_split("/\r?\n/", $content);

		if(!is_array($lines) || $line < 1 || $line > count($lines))
			return '';

		$from = max(1, $line - $context_lines);
		$to = min(count($lines), $line + $context_lines);
		$width = strlen(strval($to));

		$out = [];

		for($n = $from; $n <= $to; $n++)
			$out[] = sprintf("%s %{$width}d | %s", ($n === $line) ? '>' : ' ', $n, rtrim($lines[$n - 1], "\r"));

		return implode("\n", $out);
	}

	// -- the schema catalog --------------------------------------------------

	/**
	 * The KATA schemas this install ships, read off _CerbApplication_KataSchemas by reflection.
	 *
	 * Reflected rather than listed so a schema added there is offered here the same day. The filter is what
	 * a schema method looks like -- public, not static, no required arguments, returns a string -- so a
	 * helper that turns up on that class later doesn't get advertised as a schema.
	 *
	 * @return string[] method names, sorted
	 */
	private static function _schemaNames() : array {
		static $names = null;

		if(is_null($names)) {
			$names = [];

			foreach((new ReflectionClass(CerberusApplication::kataSchemas()))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				$type = $method->getReturnType();

				if($method->isStatic() || $method->getNumberOfRequiredParameters())
					continue;

				if(!($type instanceof ReflectionNamedType) || 'string' !== $type->getName())
					continue;

				$names[] = $method->getName();
			}

			sort($names);
		}

		return $names;
	}

	/**
	 * A schema by name, matched loosely: a model will write `automation_policy` or `automation-policy` at
	 * least as often as `automationPolicy`, and rejecting those teaches it nothing.
	 */
	private static function _schemaKata(string $name, ?string &$resolved = null) : ?string {
		$resolved = null;
		$wanted = self::_schemaKey($name);

		foreach(self::_schemaNames() as $registered) {
			if(self::_schemaKey($registered) !== $wanted)
				continue;

			$resolved = $registered;

			return strval(CerberusApplication::kataSchemas()->$registered());
		}

		return null;
	}

	private static function _schemaKey(string $name) : string {
		return DevblocksPlatform::strLower(str_replace(['-', '_', ' '], '', trim($name)));
	}
}
