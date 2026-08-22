<?php
namespace Cerb\Agent\Cli;

use Cerb\Records\SchemaBuilder;
use DevblocksExtensionManifest;
use DevblocksPlatform;
use Extension_DevblocksContext;

/**
 * `cerb records` -- what record types exist in THIS install, and what keys each one accepts.
 *
 * The point is grounding: an agent asked for "Kina's tickets" has to know `ticket` is a record type here and
 * which keys a ticket query takes. Both are knowable server-side and neither is a file anybody would import.
 *
 * Metadata only. This exposes key NAMES, never record values -- the same thing Setup > Records already shows
 * every worker and the `record.fields` / `record.filters` data queries already return -- so it needs no
 * per-record permission model. Reading record DATA is a separate, default-deny concern.
 */
class Records implements Command {
	public function getName() : string {
		return 'records';
	}

	public function getSummary() : string {
		return 'Record types in this install, and the keys you can search or write';
	}

	public function getRequiredScope(array $args) : string {
		// Everything here is metadata. A future `get`/`search`/`set` charges a different scope.
		return 'records:read';
	}

	public function getHelp() : string {
		return implode("\n", [
			"cerb records -- record types in this Cerb install, and their keys.",
			'',
			"  cerb records types [--filter <text>] [--custom|--no-custom]",
			"      List every record type. `alias` is what record.* automations, `of:` in a data query, and",
			"      `links.<alias>:` filters all take. --filter matches the alias or either name.",
			'',
			"  cerb records filters <type>[,<type>...]",
			"      The keys you can SEARCH a record type by -- use these to build a query.",
			'',
			"  cerb records fields <type>[,<type>...]",
			"      The keys you can WRITE -- what record.create / record.update accept, plus custom fields.",
			'',
			"Flags:",
			"  --format md|json   md (default) is a table; json is the same rows as JSON.",
			'',
			"Pipe rows instead of grepping text -- each sub-command binds `data` plus a name of its own:",
			"  cerb records types | types|filter(r => r.is_custom)|column(\"alias\")",
			"  cerb records filters ticket | filters|filter(r => \"owner\" in r.key)",
			'',
			"Typical use: `cerb records filters ticket` to learn the keys, then search with them.",
		]);
	}

	public function exec(array $args, array $flags, array $context = []) : array {
		$subcommand = DevblocksPlatform::strLower(strval(array_shift($args) ?? ''));

		return match($subcommand) {
			// A bare namespace ANSWERS with its usage rather than pointing at it. Telling a model to go read
			// the help costs a whole extra tool round trip to learn something we already had in hand.
			'' => $this->_ok($this->getHelp()),
			'types' => $this->_types($args, $flags),
			'filters' => $this->_keys($args, $flags, 'filters'),
			'fields' => $this->_keys($args, $flags, 'fields'),
			// Same reasoning, but it IS an error: say what went wrong, then answer the obvious next question.
			default => $this->_fail(sprintf("cerb records: %s: unknown sub-command.\n\n%s", $subcommand, $this->getHelp())),
		};
	}

	/**
	 * Every record type, from MANIFESTS ONLY -- no context is instantiated.
	 *
	 * That constraint is the whole reason this doesn't reuse the `record.types` data provider, which calls
	 * getAll(TRUE) and therefore requires every DAO file in the app (see Extension.php's own warning on
	 * getSearchFacetsForContext). Everything below reads off the manifest.
	 */
	private function _types(array $args, array $flags) : array {
		$filter = DevblocksPlatform::strLower(trim(strval($flags['filter'] ?? '')));

		$only_custom = !empty($flags['custom']);
		$no_custom = !empty($flags['no-custom']);

		$rows = [];

		foreach(Extension_DevblocksContext::getAll(false) as $context_id => $mft) {
			$is_custom = DevblocksPlatform::strStartsWith($context_id, 'contexts.custom_record.');

			if($only_custom && !$is_custom)
				continue;

			if($no_custom && $is_custom)
				continue;

			$aliases = Extension_DevblocksContext::getAliasesForContext($mft);
			$alias = strval($aliases['uri'] ?? '');

			if('' === $alias)
				continue;

			$singular = strval($aliases['singular'] ?? '');
			$plural = strval($aliases['plural'] ?? '');

			if('' !== $filter) {
				$haystack = DevblocksPlatform::strLower($alias . ' ' . $singular . ' ' . $plural);

				if(!str_contains($haystack, $filter))
					continue;
			}

			$rows[] = [
				'alias' => $alias,
				'singular' => $singular,
				'plural' => $plural,
				'is_custom' => $is_custom,
				'is_writable' => $mft->hasOption('records'),
				'context' => $context_id,
			];
		}

		usort($rows, fn($a, $b) => strcasecmp($a['alias'], $b['alias']));

		if($this->_wantsJson($flags))
			return $this->_json($rows, 'types');

		$table = $this->_table(
			['alias', 'singular', 'plural', 'custom', 'writable'],
			array_map(fn($r) => [
				$r['alias'],
				$r['singular'],
				$r['plural'],
				$r['is_custom'] ? 'yes' : '',
				$r['is_writable'] ? 'yes' : '',
			], $rows)
		);

		$out = [
			"# Record types in this Cerb install",
			'',
			"`alias` is what record.* automations, `of:` in a data query, and `links.<alias>:` filters take.",
			"`writable` means record.create / record.update accept it. Read `cerb records filters <alias>`",
			"for the keys you can search a type by.",
			'',
			$table,
			'',
			sprintf("%d record type%s%s.",
				count($rows),
				(1 == count($rows)) ? '' : 's',
				('' !== $filter) ? sprintf(" matching '%s'", $filter) : ''
			),
		];

		return $this->_ok(implode("\n", $out), $rows, 'types');
	}

	/**
	 * `filters` (searchable keys) or `fields` (writable keys + custom fields) for one or more types.
	 *
	 * Instantiates exactly the contexts named, and only on this call -- the lazy half of the manifest/detail
	 * split that keeps `types` cheap.
	 */
	private function _keys(array $args, array $flags, string $kind) : array {
		$requested = [];

		// Accept `ticket,worker` and `ticket worker` alike; a model will produce both.
		foreach($args as $arg)
			foreach(explode(',', strval($arg)) as $piece)
				if('' !== ($piece = trim($piece)))
					$requested[] = $piece;

		$requested = array_values(array_unique($requested));

		if(!$requested)
			return $this->_fail(sprintf("cerb records %s: name at least one record type, e.g. `cerb records %s ticket`. Use `cerb records types` to list them.", $kind, $kind));

		$rows = [];
		$sections = [];
		$unknown = [];

		foreach($requested as $ref) {
			if(false == ($mft = Extension_DevblocksContext::getByAlias($ref, false)) || !($mft instanceof DevblocksExtensionManifest)) {
				$unknown[] = $ref;
				continue;
			}

			[$described, $alias] = $this->_describe($mft);

			if('filters' === $kind) {
				$section_rows = array_map(fn($r) => [
					'record_type' => $alias,
					'key' => strval($r['key'] ?? ''),
					'type' => strval($r['type'] ?? ''),
					'notes' => strval($r['notes'] ?? ''),
				], $described['search']);

				$sections[] = $this->_filtersSection($alias, $section_rows);

			} else {
				$section_rows = array_map(fn($r) => [
					'record_type' => $alias,
					'key' => strval($r['key'] ?? ''),
					'type' => strval($r['type'] ?? ''),
					'is_required' => !empty($r['required']),
					'notes' => strval($r['notes'] ?? ''),
				], $described['api']);

				$sections[] = $this->_fieldsSection($alias, $section_rows, $described);
			}

			$rows = array_merge($rows, $section_rows);
		}

		if($unknown && !$rows)
			return $this->_fail(sprintf("cerb records %s: unknown record type%s: %s. Use `cerb records types` to list them.",
				$kind,
				(1 == count($unknown)) ? '' : 's',
				implode(', ', $unknown)
			));

		if($this->_wantsJson($flags))
			return $this->_json($rows, $kind);

		// A partial answer says so rather than quietly dropping the names it couldn't resolve.
		if($unknown)
			$sections[] = sprintf("Unknown record type%s (skipped): %s",
				(1 == count($unknown)) ? '' : 's',
				implode(', ', $unknown)
			);

		return $this->_ok(implode("\n\n", $sections), $rows, $kind);
	}

	/**
	 * One type's reflection, cached.
	 *
	 * This is the expensive half -- it instantiates the context and builds a temp view -- while `types` stays
	 * on cached manifests. Keyed on the CONTEXT, not the agent or the mount, because the content is
	 * install-wide: every agent shares one entry. `schema_records` is already the tag that custom fields,
	 * custom fieldsets, custom records, and plugin enable/disable all bump, so there's no new invalidation
	 * to own here.
	 *
	 * @return array [$described, $alias]
	 */
	private function _describe(DevblocksExtensionManifest $mft) : array {
		$cache = DevblocksPlatform::services()->cache();
		$key = 'agent_cli_records_' . sha1($mft->id);

		if(!is_array($described = $cache->load($key))) {
			$described = SchemaBuilder::describeType($mft->id, $mft);
			$cache->save($described, $key, ['schema_records']);
		}

		return [$described, strval($described['uri'] ?: $mft->id)];
	}

	private function _filtersSection(string $alias, array $rows) : string {
		return implode("\n", [
			sprintf("# %s -- search filters", $alias),
			'',
			"Keys usable in a query. Space-separate to AND; `OR` and `(...)` group; `-` negates.",
			'',
			$this->_keyTable($rows, false),
		]);
	}

	/**
	 * A key/type table, with notes placed wherever they cost least.
	 *
	 * `fields` rows are mostly annotated, so a third column is right. `filters` rows are mostly bare -- only
	 * the collapsed link families carry a note -- and an empty column there is a few tokens times every row,
	 * on the longest output this command produces. So: inline when most rows have a note, footnote otherwise.
	 */
	private function _keyTable(array $rows, bool $mark_required) : string {
		$noted = array_values(array_filter($rows, fn($r) => '' !== trim(strval($r['notes'] ?? ''))));
		$inline = $rows && (count($noted) * 2 >= count($rows));

		$headers = $inline ? ['key', 'type', 'notes'] : ['key', 'type'];
		$body = [];

		foreach($rows as $row) {
			$key = strval($row['key'] ?? '') . (($mark_required && !empty($row['is_required'])) ? ' *' : '');

			$body[] = $inline
				? [$key, strval($row['type'] ?? ''), strval($row['notes'] ?? '')]
				: [$key, strval($row['type'] ?? '')];
		}

		$out = $this->_table($headers, $body);

		if(!$inline && $noted) {
			$notes = ['', 'Notes:'];

			foreach($noted as $row)
				$notes[] = sprintf("- `%s` -- %s", strval($row['key'] ?? ''), $this->_cell($row['notes']));

			$out .= "\n" . implode("\n", $notes);
		}

		return $out;
	}

	private function _fieldsSection(string $alias, array $rows, array $described) : string {
		$out = [
			sprintf("# %s -- writable fields", $alias),
			'',
		];

		if($rows) {
			$out[] = "Accepted by record.create / record.update. `*` marks a required key.";
			$out[] = '';
			$out[] = $this->_keyTable($rows, true);
		} else {
			$out[] = "This record type has no writable API keys.";
		}

		if($described['custom'] ?? []) {
			$out[] = '';
			$out[] = "## Custom fields";
			$out[] = '';
			$out[] = $this->_customFieldTable($described['custom']);
		}

		foreach(($described['fieldsets'] ?? []) as $fieldset) {
			if(!($fieldset['fields'] ?? []))
				continue;

			$out[] = '';
			$out[] = sprintf("## Custom fieldset: %s", strval($fieldset['name'] ?? ''));
			$out[] = '';
			$out[] = $this->_customFieldTable($fieldset['fields']);
		}

		return implode("\n", $out);
	}

	/**
	 * A markdown table. Chosen over JSON as the default because this data is tabular and JSON repeats every
	 * key name on every row -- roughly 40% more tokens on a long filter list, for output nothing parses.
	 */
	/**
	 * Custom fields, with the `notes` column carried only when something in this set has any.
	 *
	 * `type` alone is a dead end for two of them: a Record Link doesn't say WHICH type it points at, and a
	 * Picklist doesn't say what its values are -- so a reader can't write a value or a filter without
	 * guessing. SchemaBuilder answers both in `notes`; a set with none (all plain text and numbers) keeps
	 * the narrower table.
	 */
	private function _customFieldTable(array $rows) : string {
		$has_notes = (bool) array_filter($rows, fn($r) => '' !== trim(strval($r['notes'] ?? '')));

		$headers = $has_notes ? ['key', 'type', 'field', 'notes'] : ['key', 'type', 'field'];

		return $this->_table(
			$headers,
			array_map(function($r) use ($has_notes) {
				$row = [strval($r['key'] ?? ''), strval($r['type'] ?? ''), strval($r['label'] ?? '')];

				if($has_notes)
					$row[] = $this->_cell($r['notes'] ?? '');

				return $row;
			}, $rows)
		);
	}

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
