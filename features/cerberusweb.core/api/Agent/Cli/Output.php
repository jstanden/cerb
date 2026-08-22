<?php
namespace Cerb\Agent\Cli;

use DevblocksPlatform;

/**
 * The rendering half of a `cerb` sub-command: tables, JSON, and the result shape.
 *
 * Shared so every namespace answers in the same format. A model that has learned to read one command's
 * output can read the next one's, and a `|` pipeline written against one works against another.
 */
trait Output {
	/**
	 * A Markdown table. Chosen over JSON as the default because this data is tabular and JSON repeats every
	 * key name on every row -- roughly 40% more tokens on a long list, for output nothing parses.
	 */
	private function _table(array $headers, array $rows) : string {
		$lines = [
			'| ' . implode(' | ', $headers) . ' |',
			'|' . str_repeat(' --- |', count($headers)),
		];

		foreach($rows as $row)
			$lines[] = '| ' . implode(' | ', array_map([$this, '_cell'], $row)) . ' |';

		return implode("\n", $lines);
	}

	/** A pipe inside a cell would silently break the row into extra columns. */
	private function _cell($value) : string {
		$value = str_replace(["\r\n", "\r", "\n"], ' ', strval($value));

		return str_replace('|', '\\|', $value);
	}

	private function _wantsJson(array $flags) : bool {
		return 'json' === DevblocksPlatform::strLower(trim(strval($flags['format'] ?? '')));
	}

	private function _json(array $rows, string $alias) : array {
		return $this->_ok(
			strval(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
			$rows,
			$alias
		);
	}

	/** `data` carries the rows for a `|` pipeline; `data_alias` gives them a readable name beside `data`. */
	private function _ok(string $output, ?array $data = null, ?string $data_alias = null) : array {
		return ['output' => $output, 'error' => false, 'data' => $data, 'data_alias' => $data_alias];
	}

	private function _fail(string $output) : array {
		return ['output' => $output, 'error' => true, 'data' => null, 'data_alias' => null];
	}
}
