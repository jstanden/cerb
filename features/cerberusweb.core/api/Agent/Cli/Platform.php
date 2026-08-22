<?php
namespace Cerb\Agent\Cli;

use DevblocksExtensionManifest;
use DevblocksPlatform;
use DevblocksPluginManifest;

/**
 * `cerb platform` -- which plugins are installed and enabled HERE, and what extends what.
 *
 * The documentation describes the plugins that exist; it can't say which of them this install actually
 * runs. That gap is the same one `cerb records` closes for record types: an agent asked whether it can
 * file a JIRA issue, or which automation triggers it may write for, has no way to know without asking
 * the install.
 *
 * The extension listing is not only for plugin development. An extension ID is a VALUE in ordinary
 * searches -- `trigger:cerb.trigger.interaction.worker` on an automation is an extension ID off the
 * `cerb.automation.trigger` point -- so this is how an agent gets from "automations that run on a
 * worker interaction" to a query that returns them.
 *
 * Metadata only, all of it already visible at Setup > Plugins and Setup > Developers, so it needs no
 * per-record permission model.
 */
class Platform implements Command {
	use Output;

	public function getName() : string {
		return 'platform';
	}

	public function getSummary() : string {
		return 'Plugins installed here, extension points, and the extensions on them';
	}

	public function getRequiredScope(array $args) : string {
		// Everything here is metadata. Anything that CHANGES the install charges a different scope.
		return 'platform:read';
	}

	public function getHelp() : string {
		return implode("\n", [
			"cerb platform -- what's installed and extensible in THIS Cerb install.",
			'',
			"  cerb platform plugins [--filter <text>] [--enabled|--disabled]",
			"      Every plugin, by fully qualified ID, with whether it's enabled here. A disabled plugin's",
			"      record types, extensions, and features are all absent from this install.",
			'',
			"  cerb platform points [--filter <text>]",
			"      Every extension point, by fully qualified ID, with how many extensions are on it here.",
			'',
			"  cerb platform extensions <point>[,<point>...] [--filter <text>]",
			"      Every extension on a point, by fully qualified ID.",
			'',
			"Flags:",
			"  --format md|json   md (default) is a table; json is the same rows as JSON.",
			"  --filter <text>    Substring match on the ID and the name. Some points have 100+ extensions.",
			'',
			"An extension ID is a search VALUE, not just a plugin-development detail:",
			"  cerb platform extensions cerb.automation.trigger",
			"  -> then search automations with `trigger:cerb.trigger.interaction.worker`",
			'',
			"Pipe rows instead of grepping text -- each sub-command binds `data` plus a name of its own:",
			"  cerb platform plugins | plugins|filter(r => not r.is_enabled)|column(\"plugin\")",
			"  cerb platform extensions cerb.automation.trigger | extensions|column(\"id\")",
		]);
	}

	public function exec(array $args, array $flags, array $context = []) : array {
		$subcommand = DevblocksPlatform::strLower(strval(array_shift($args) ?? ''));

		return match($subcommand) {
			// A bare namespace ANSWERS with its usage rather than pointing at it. Telling a model to go read
			// the help costs a whole extra tool round trip to learn something we already had in hand.
			'' => $this->_ok($this->getHelp()),
			'plugins' => $this->_plugins($args, $flags),
			'points' => $this->_points($args, $flags),
			'extensions' => $this->_extensions($args, $flags),
			// Same reasoning, but it IS an error: say what went wrong, then answer the obvious next question.
			default => $this->_fail(sprintf("cerb platform: %s: unknown sub-command.\n\n%s", $subcommand, $this->getHelp())),
		};
	}

	/**
	 * Every plugin registered on disk, enabled or not.
	 *
	 * Disabled ones are listed by default and marked, because "this install has the JIRA plugin but it's
	 * switched off" is a different answer from "this install has no JIRA integration", and only the first
	 * one is fixable by an admin.
	 */
	private function _plugins(array $args, array $flags) : array {
		$filter = DevblocksPlatform::strLower(trim(strval($flags['filter'] ?? '')));

		$only_enabled = !empty($flags['enabled']);
		$only_disabled = !empty($flags['disabled']);

		$rows = [];

		foreach(DevblocksPlatform::getPluginRegistry() ?: [] as $plugin) { /* @var $plugin DevblocksPluginManifest */
			$is_enabled = (bool) $plugin->enabled;

			if($only_enabled && !$is_enabled)
				continue;

			if($only_disabled && $is_enabled)
				continue;

			if('' !== $filter) {
				$haystack = DevblocksPlatform::strLower($plugin->id . ' ' . $plugin->name);

				if(!str_contains($haystack, $filter))
					continue;
			}

			$rows[] = [
				'plugin' => strval($plugin->id),
				'name' => strval($plugin->name),
				'version' => strval(DevblocksPlatform::intVersionToStr($plugin->version)),
				'is_enabled' => $is_enabled,
				'source' => $this->_source(strval($plugin->dir)),
				'description' => strval($plugin->description),
				'author' => strval($plugin->author),
				'link' => strval($plugin->link),
			];
		}

		usort($rows, fn($a, $b) => strcasecmp($a['plugin'], $b['plugin']));

		if($this->_wantsJson($flags))
			return $this->_json($rows, 'plugins');

		$table = $this->_table(
			['plugin', 'name', 'version', 'enabled', 'source'],
			array_map(fn($r) => [
				$r['plugin'],
				$r['name'],
				$r['version'],
				$r['is_enabled'] ? 'yes' : 'NO',
				$r['source'],
			], $rows)
		);

		$enabled_count = count(array_filter($rows, fn($r) => $r['is_enabled']));

		$out = [
			"# Plugins in this Cerb install",
			'',
			"`plugin` is the fully qualified plugin ID. `source` is where it comes from: `framework` and",
			"`feature` ship with Cerb, `legacy` is a bundled older feature, and `plugin` was installed here.",
			"A plugin that is not enabled contributes nothing -- its record types and extensions do not exist.",
			'',
			$table,
			'',
			sprintf("%d plugin%s%s, %d enabled.",
				count($rows),
				(1 == count($rows)) ? '' : 's',
				('' !== $filter) ? sprintf(" matching '%s'", $filter) : '',
				$enabled_count
			),
		];

		return $this->_ok(implode("\n", $out), $rows, 'plugins');
	}

	/**
	 * Every extension point, counted against what this install actually has on it.
	 *
	 * DevblocksPlatform::getExtensionPointRegistry() is shared with the `platform.extension.points` and
	 * `platform.extensions` data queries, deliberately: a fork would let an agent be told a point exists
	 * that a data query then rejects.
	 */
	private function _points(array $args, array $flags) : array {
		$filter = DevblocksPlatform::strLower(trim(strval($flags['filter'] ?? '')));

		$rows = [];

		foreach(DevblocksPlatform::getExtensionPointRegistry() as $point => $meta) {
			if('' !== $filter) {
				$haystack = DevblocksPlatform::strLower($point . ' ' . strval($meta['label'] ?? ''));

				if(!str_contains($haystack, $filter))
					continue;
			}

			$rows[] = [
				'point' => $point,
				'label' => strval($meta['label'] ?? ''),
				'class' => strval($meta['class'] ?? ''),
				'extensions' => intval($meta['extensions'] ?? 0),
			];
		}

		if($this->_wantsJson($flags))
			return $this->_json($rows, 'points');

		$table = $this->_table(
			['point', 'extensions', 'label', 'class'],
			array_map(fn($r) => [$r['point'], $r['extensions'], $r['label'], $r['class']], $rows)
		);

		$out = [
			"# Extension points in this Cerb install",
			'',
			"`point` is the fully qualified extension point ID. `extensions` counts what's registered on it",
			"here; extensions belonging to a disabled plugin are not counted and not reachable. `class` is",
			"the base class an implementation extends, where Cerb names one.",
			'',
			$table,
			'',
			sprintf("%d extension point%s%s. Read `cerb platform extensions <point>` for what's on one.",
				count($rows),
				(1 == count($rows)) ? '' : 's',
				('' !== $filter) ? sprintf(" matching '%s'", $filter) : ''
			),
		];

		return $this->_ok(implode("\n", $out), $rows, 'points');
	}

	/**
	 * The extensions on one or more points.
	 *
	 * Manifests only -- nothing is instantiated, so listing a point costs no DAO includes.
	 */
	private function _extensions(array $args, array $flags) : array {
		$requested = [];

		// Accept `a,b` and `a b` alike; a model will produce both.
		foreach($args as $arg)
			foreach(explode(',', strval($arg)) as $piece)
				if('' !== ($piece = trim($piece)))
					$requested[] = $piece;

		$requested = array_values(array_unique($requested));

		if(!$requested)
			return $this->_fail("cerb platform extensions: name at least one extension point, e.g. `cerb platform extensions cerb.automation.trigger`. Use `cerb platform points` to list them.");

		$filter = DevblocksPlatform::strLower(trim(strval($flags['filter'] ?? '')));

		$meta = DevblocksPlatform::getExtensionPointRegistry();
		$registry = DevblocksPlatform::getExtensionRegistry() ?: [];

		$rows = [];
		$sections = [];
		$unknown = [];

		foreach($requested as $point) {
			$point_rows = [];

			foreach($registry as $extension) { /* @var $extension DevblocksExtensionManifest */
				if($extension->point !== $point)
					continue;

				if('' !== $filter) {
					$haystack = DevblocksPlatform::strLower($extension->id . ' ' . $extension->name);

					if(!str_contains($haystack, $filter))
						continue;
				}

				$point_rows[] = [
					'point' => $point,
					'id' => strval($extension->id),
					'name' => strval($extension->name),
					'plugin' => strval($extension->plugin_id),
					'class' => strval($extension->class),
				];
			}

			// A point nobody has ever heard of is a typo; a known point with nothing on it is an answer, and
			// so is a known point a --filter emptied.
			if(!array_key_exists($point, $meta)) {
				$unknown[] = $point;
				continue;
			}

			usort($point_rows, fn($a, $b) => strcasecmp($a['id'], $b['id']));

			$sections[] = $this->_pointSection($point, $point_rows, $meta[$point] ?? [], $filter);

			$rows = array_merge($rows, $point_rows);
		}

		if($unknown && !$sections)
			return $this->_fail(sprintf("cerb platform extensions: unknown extension point%s: %s. Use `cerb platform points` to list them.",
				(1 == count($unknown)) ? '' : 's',
				implode(', ', $unknown)
			));

		if($this->_wantsJson($flags))
			return $this->_json($rows, 'extensions');

		// A partial answer says so rather than quietly dropping the names it couldn't resolve.
		if($unknown)
			$sections[] = sprintf("Unknown extension point%s (skipped): %s",
				(1 == count($unknown)) ? '' : 's',
				implode(', ', $unknown)
			);

		return $this->_ok(implode("\n\n", $sections), $rows, 'extensions');
	}

	private function _pointSection(string $point, array $rows, array $meta, string $filter) : string {
		$out = [
			sprintf("# %s%s", $point, ($meta['label'] ?? '') ? sprintf(' -- %s', $meta['label']) : ''),
			'',
		];

		if(!$rows) {
			$out[] = ('' !== $filter)
				? sprintf("Nothing on this point matches '%s'.", $filter)
				: "This install has no extensions on this point.";

			return implode("\n", $out);
		}

		$out[] = "`id` is the fully qualified extension ID -- the value stored on records that reference an";
		$out[] = "extension, and therefore what a search filters on. `name` is its registered short name.";
		$out[] = '';
		$out[] = $this->_table(
			['id', 'name', 'plugin', 'class'],
			array_map(fn($r) => [$r['id'], $r['name'], $r['plugin'], $r['class']], $rows)
		);
		$out[] = '';
		$out[] = sprintf("%d extension%s.", count($rows), (1 == count($rows)) ? '' : 's');

		return implode("\n", $out);
	}

	/**
	 * Where a plugin came from, off its manifest directory -- the same split Setup > Plugins renders as
	 * separate worklists.
	 */
	private function _source(string $dir) : string {
		if(DevblocksPlatform::strStartsWith($dir, 'storage/plugins/'))
			return 'plugin';

		if(DevblocksPlatform::strStartsWith($dir, 'features/'))
			return 'feature';

		if(DevblocksPlatform::strStartsWith($dir, 'plugins/'))
			return 'legacy';

		return 'framework';
	}
}
