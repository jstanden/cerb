<?php
namespace Cerb\Agent;

/**
 * The agent virtual filesystem (VFS) command interpreter.
 *
 * A PURE evaluator over `agent_file` rows: given a resolved mount set, a working directory, and one command
 * line, it returns compact text output plus the (possibly changed) cwd. It is NOT a shell -- it implements a
 * small, fixed command vocabulary (ls / cd / search / read / write / rm / help). The host owns cwd between
 * calls, so the same core backs both the human terminal and the agent's `agent_terminal` tool.
 *
 * Mounting is COMPOSITION: each mount maps a source filesystem onto a mountpoint at a mode (ro|rw), so a path
 * like /memory/team can be a curated ro volume while /memory/me is a per-worker rw one.
 *
 * Directories are VIRTUAL -- there are no directory rows. `agent_file.name` holds a full path
 * ("references/refunds.md") and the tree is derived from path prefixes.
 */
class Filesystem {
	const MODE_RO = 'ro';
	const MODE_RW = 'rw';

	const KIND_VOLUME = 'volume';
	const KIND_TMP = 'tmp';

	// NOT a mount kind: the reserved spec-list entry that carries the `cerb` CLI config alongside the mounts.
	const KIND_CLI = 'cerb';

	const TMP_AT = '/tmp';

	/**
	 * Longest storable path. The unique key is `(filesystem_id, name(700))`, so two paths sharing a 700-char
	 * prefix collide SILENTLY -- one file quietly overwrites another. Every writer enforces this.
	 */
	const MAX_PATH_LENGTH = 700;

	/** Per-file inline ceiling; a bigger buffer spills to an automation_resource so the dict stays small. */
	const TMP_INLINE_MAX_BYTES = 32768;

	/** Total inline ceiling across /tmp; the oldest inline buffers spill (never vanish) to stay under it. */
	const TMP_INLINE_TOTAL_BYTES = 131072;

	/** TTL for a spilled buffer. DAO_AutomationResource::maint() reaps by expires_at. */
	const TMP_RESOURCE_TTL = 3600;

	/** Internal filter injected around a `|` chain to render its result. Never typed by a caller. */
	const RENDER_FILTER = '_agent_fs_render';

	/** @var array[] each: ['kind'=>'volume', 'fs' => \Model_AgentFilesystem, 'at' => '/docs', 'mode' => 'ro'] */
	private array $_mounts = [];

	private array $_options = [];

	/**
	 * The /tmp scratch store: ['files' => [name => entry]]. Each entry has size/lines/created_at plus EITHER
	 * `content` (inline) or `resource` (an automation_resource token). The HOST owns it -- the terminal
	 * round-trips it in its POST, an automation keeps it on the dict -- so this class stays a pure evaluator.
	 */
	private array $_tmp = [];

	private bool $_tmp_dirty = false;

	/** Per-request cache of a filesystem's path list: [filesystem_id => [name => ['size'=>n,'updated_at'=>n]]] */
	private static array $_path_cache = [];

	public function __construct(array $mounts, array $options = []) {
		$this->_options = array_merge([
			'max_results' => 200,   // ls/search row cap
			'max_bytes' => 65536,   // read/output byte cap
			'context_lines' => 0,   // search: lines of context (future)
			'tmp' => null,          // the host's scratch store; null = no /tmp mount at all
			'cerb' => null,         // the `cerb` CLI's enabled namespaces; null = no CLI at all
		], $options);

		foreach($mounts as $mount) {
			if(!isset($mount['fs']) || !($mount['fs'] instanceof \Model_AgentFilesystem))
				continue;

			$this->_mounts[] = [
				'kind' => self::KIND_VOLUME,
				'fs' => $mount['fs'],
				'at' => self::_normalizePath($mount['at'] ?? ('/' . $mount['fs']->name)),
				'mode' => (self::MODE_RW == ($mount['mode'] ?? self::MODE_RO)) ? self::MODE_RW : self::MODE_RO,
			];
		}

		// /tmp exists whenever the host supplies a store. Always rw: it's scratch (command spillover and the
		// agent's own scribbles), so none of the durability concerns that keep volume mounts ro apply.
		if(is_array($this->_options['tmp'])) {
			$this->_tmp = $this->_options['tmp'];

			$this->_mounts[] = [
				'kind' => self::KIND_TMP,
				'fs' => null,
				'at' => self::TMP_AT,
				'mode' => self::MODE_RW,
			];
		}

		// Longest mountpoint first so nested mounts resolve before their parents
		usort($this->_mounts, fn($a, $b) => strlen($b['at']) <=> strlen($a['at']));
	}

	/**
	 * Build from a spec list: [ ['filesystem' => 'cerb-docs', 'at' => '/docs', 'mode' => 'ro'], ... ]
	 * `filesystem` may be a name or an id. Unknown/disabled filesystems are skipped.
	 */
	public static function fromSpecs(array $specs, array $options = []) : self {
		// The `cerb` CLI's config rides the SAME spec list as the mounts, as a reserved non-mount entry. That
		// is what makes it persist with the session for free (setMounts() content-addresses the whole blob),
		// and what makes the two hosts -- the cached tool description and the execution path, which both call
		// fromSpecs($tool['mounts']) -- structurally unable to disagree about what's enabled.
		foreach($specs as $spec) {
			if(self::_isCliSpec($spec) && !array_key_exists('cerb', $options))
				$options['cerb'] = $spec['cerb'] ?? [];
		}

		$mounts = [];

		foreach(self::describeSpecs($specs) as $described) {
			// A spec that names a volume which is gone or disabled simply doesn't mount. Deliberate -- one bad
			// entry must not cost an agent the volumes that DO resolve. `describeSpecs()` is how a viewer can
			// still report what was dropped.
			if(self::MOUNT_OK !== $described['state'])
				continue;

			$mounts[] = [
				'fs' => $described['filesystem'],
				'at' => $described['at'],
				'mode' => $described['mode'],
			];
		}

		return new self($mounts, $options);
	}

	const MOUNT_OK = 'ok';
	const MOUNT_DISABLED = 'disabled';
	const MOUNT_MISSING = 'missing';

	/**
	 * Resolve mount specs to their volumes WITHOUT dropping the ones that fail.
	 *
	 * `fromSpecs()` is the consumer and can only mount what resolves, so it discards the rest -- which means a
	 * session can carry a mount that silently reached nothing, and until this existed there was nowhere to see
	 * that. Anything reporting on a mount set (the Setup transcript viewer) needs the failures precisely
	 * because they're invisible everywhere else.
	 *
	 * `fromSpecs()` is built ON this rather than beside it: two resolvers applying "the same" name/id lookup
	 * would drift, and the drift would show up as a viewer confidently listing a volume the agent never had.
	 *
	 * @return array[] one entry per spec: {ref, filesystem: ?Model_AgentFilesystem, at, mode, state}
	 */
	public static function describeSpecs(array $specs) : array {
		$described = [];
		$by_name = null;

		foreach($specs as $spec) {
			// Reserved non-mount entries (the `cerb` CLI config) share this list but name no volume. Skipped
			// HERE rather than only in fromSpecs() so every caller is covered -- the transcript viewer reads
			// describeSpecs() directly and would otherwise render the CLI entry as a "Missing" volume.
			if(self::_isCliSpec($spec))
				continue;

			$key = $spec['filesystem'] ?? null;
			$model = null;

			if(is_numeric($key)) {
				$model = \DAO_AgentFilesystem::get(intval($key));
			} else if(is_string($key) && $key) {
				if(is_null($by_name)) {
					$by_name = [];
					foreach(\DAO_AgentFilesystem::getAll() as $fs)
						$by_name[\DevblocksPlatform::strLower($fs->name)] = $fs;
				}
				$model = $by_name[\DevblocksPlatform::strLower($key)] ?? null;
			}

			if(!$model) {
				$state = self::MOUNT_MISSING;
			} else if($model->is_disabled) {
				$state = self::MOUNT_DISABLED;
			} else {
				$state = self::MOUNT_OK;
			}

			$described[] = [
				'ref' => strval($key ?? ''),
				'filesystem' => $model,
				// An unresolved spec has no record to name the default mountpoint after, so it falls back to the
				// reference it was written with -- which is what the author typed, and what they'd search for.
				'at' => $spec['at'] ?? ('/' . ($model?->name ?? strval($key ?? ''))),
				'mode' => $spec['mode'] ?? self::MODE_RO,
				'state' => $state,
			];
		}

		return $described;
	}

	/** @return array[] the resolved mounts */
	public function getMounts() : array {
		return $this->_mounts;
	}

	/** The reserved spec entry carrying the `cerb` CLI config, rather than a volume to mount. */
	private static function _isCliSpec($spec) : bool {
		return is_array($spec) && self::KIND_CLI === ($spec['kind'] ?? '');
	}

	/** Does this instance have the `cerb` CLI? Drives whether the tool description advertises the verb. */
	public function hasCli() : bool {
		return Cli::isEnabled($this->_options['cerb']);
	}

	/**
	 * Run one command line.
	 *
	 * Three stages, in this order:
	 *   1. the command runs and produces its FULL output (no byte cap -- a pipeline has to see the whole body);
	 *   2. a pipeline, if any, transforms it;
	 *   3. whatever is left is capped, and the overflow spills to a /tmp file the caller can page through.
	 * So narrowing a huge `search` with a pipeline avoids the spill entirely.
	 *
	 * @param string $command the terminal input; a trailing `| <twig filter chain>` pipes the output
	 * @param string $cwd the caller's working directory
	 * @param string|null $payload out-of-band body for `write`/`append` (a large body doesn't belong on the
	 *                             command line -- the tool passes a `content` param, the terminal an editor)
	 * @param string|null $script out-of-band Twig template applied to the output -- the multi-line alternative
	 *                            to `|`, so nothing has to be escaped onto one line. No script means no pipe.
	 * @param string|null $find out-of-band search needle for `edit` -- the exact snippet to locate (the
	 *                          replacement rides `$payload`, since it's just content being written into the file).
	 * @return array ['output' => string, 'cwd' => string, 'error' => bool, 'tmp' => array|null]
	 */
	public function exec(string $command, string $cwd = '/', ?string $payload = null, ?string $script = null, ?string $find = null) : array {
		$cwd = self::_normalizePath($cwd ?: '/');

		list($command, $pipeline) = self::_splitPipeline($command);

		if(!is_null($pipeline) && '' !== $pipeline && !is_null($script) && '' !== trim($script))
			return $this->_error("Use either a `| filter` pipeline or an out-of-band script, not both.", $cwd);

		$parsed = self::_parse($command);

		if(!$parsed['verb'])
			return $this->_result('', $cwd);

		$result = match($parsed['verb']) {
			'ls', 'dir' => $this->_cmdLs($parsed, $cwd),
			'find' => $this->_cmdFind($parsed, $cwd),
			'cd' => $this->_cmdCd($parsed, $cwd),
			'read', 'cat' => $this->_cmdRead($parsed, $cwd),
			'search', 'grep' => $this->_cmdSearch($parsed, $cwd),
			'write', 'append' => $this->_cmdWrite($parsed, $cwd, $payload, 'append' == $parsed['verb']),
			'edit' => $this->_cmdEdit($parsed, $cwd, $find, $payload),
			'copy', 'cp' => $this->_cmdCopy($parsed, $cwd),
			'rm' => $this->_cmdRm($parsed, $cwd),
			'cerb' => $this->_cmdCerb($parsed, $cwd, $payload),
			'pwd' => $this->_result($cwd, $cwd),
			'help' => $this->_result($this->_help($parsed['args'][0] ?? null), $cwd),
			default => $this->_error(sprintf("%s: command not found. Try `help`.", $parsed['verb']), $cwd),
		};

		// A failed command's message is the output -- don't pipe an error into a text transform.
		if($result['error'] ?? false)
			return $this->_finish($result);

		// An inline `| chain` is applied to `output`; an out-of-band script is a whole template (it may use
		// {% %} tags and pick its own bindings).
		$template = null;

		if(!is_null($pipeline) && '' !== $pipeline) {
			$template = self::_pipelineTemplate($pipeline, $result['data_alias'] ?? null);
		} else if(!is_null($script) && '' !== trim($script)) {
			$template = $script;
		}

		if($template) {
			$error = null;

			$piped = $this->_applyScript(
				$template,
				$result['output'],
				$result['file'] ?? null,
				$result['data'] ?? null,
				$result['data_alias'] ?? null,
				$error
			);

			if(is_null($piped))
				return $this->_finish($this->_error(sprintf("script: %s", $error ?: 'the template could not be evaluated.'), $result['cwd']));

			$result['output'] = $piped;
			unset($result['file']);
		}

		return $this->_finish($result, $parsed['verb']);
	}

	/**
	 * Cap the output, spilling the whole thing to /tmp so nothing is lost, and hand back the scratch store when
	 * it changed. Every command funnels through here so there's one truncation rule instead of per-command ones.
	 */
	private function _finish(array $result, string $verb = 'out') : array {
		$output = strval($result['output'] ?? '');

		// Pipeline-only scaffolding; the caller gets text + cwd.
		unset($result['file'], $result['data'], $result['data_alias']);

		if(strlen($output) > $this->_options['max_bytes']) {
			$max = $this->_options['max_bytes'];

			if(($ref = $this->_tmpSpill($verb, $output))) {
				$result['output'] = substr($output, 0, $max) . sprintf(
					"\n\n[truncated -- full output: %d lines, %s -> %s (read it, or re-run with a `| filter` pipeline)]",
					$ref['lines'],
					\DevblocksPlatform::strPrettyBytes($ref['size']),
					$ref['path']
				);
			} else {
				$result['output'] = substr($output, 0, $max)
					. sprintf("\n\n[truncated at %s -- use --offset/--limit to page]", \DevblocksPlatform::strPrettyBytes($max));
			}
		}

		if($this->_tmp_dirty)
			$result['tmp'] = $this->_tmp;

		return $result;
	}

	/** The scratch store as it stands (the host persists this between calls). */
	public function getTmp() : array {
		return $this->_tmp;
	}

	// ---------------------------------------------------------------------
	// Commands
	// ---------------------------------------------------------------------

	private function _cmdLs(array $cmd, string $cwd) : array {
		$arg = strval($cmd['args'][0] ?? $cwd);
		$pattern = null;

		// `ls *.md` / `ls /docs/c*.md` -- a glob in the LAST segment filters this directory. It stays
		// one-level by definition; a glob further up would mean walking directories, which is `find`'s job.
		if(self::_isGlob($arg)) {
			$slash = strrpos($arg, '/');
			$head = (false === $slash) ? '' : substr($arg, 0, $slash);

			if(self::_isGlob($head))
				return $this->_error("ls: a pattern is limited to the last path segment. Use `find` to match across directories.", $cwd);

			$pattern = (false === $slash) ? $arg : substr($arg, $slash + 1);
			$arg = (false === $slash) ? $cwd : (('' === $head) ? '/' : $head);
		}

		$path = $this->_resolvePath($arg, $cwd);
		$loc = $this->_locate($path);

		if(!$loc['mount'] && !$loc['is_virtual'])
			return $this->_error(sprintf("ls: %s: No such directory", $path), $cwd);

		$regexp = is_null($pattern) ? null : self::_globToRegexp($pattern);
		$matches = fn($name) => is_null($regexp) || preg_match($regexp, $name);

		$lines = [];
		$entries = [];
		$fields = self::_fieldList($cmd['flags']['fields'] ?? null);

		// Names only by default -- a listing is usually just deciding where to look next, and sizes/permissions
		// on every row are tokens you didn't ask for. `-l` adds them.
		$long = !empty($cmd['flags']['l']) || !empty($cmd['flags']['long']);

		// Mountpoints that live directly under this path
		foreach($this->_childMountSegments($path) as $seg) {
			if(!$matches($seg))
				continue;

			$seg_mode = $this->_modeAtPath(rtrim($loc['vfs'], '/') . '/' . $seg);

			$entry = [
				'name' => $seg,
				'is_dir' => true,
				'is_mount' => true,
				'size' => 0,
				'updated_at' => 0,
				'mode' => $seg_mode,
				'can_write' => self::MODE_RW == $seg_mode,
			];

			// A mountpoint ALWAYS reports its mode, `-l` or not: it's the one thing you can't infer from a name,
			// and it's what tells you whether the volume can be written to at all. In long form the mode column
			// already says it, so the tag would just be saying it twice.
			$lines[] = self::_lsLine($long, $entry, $long ? '' : sprintf('  [%s]', $seg_mode));
			$entries[] = $entry;
		}

		if($loc['mount']) {
			$mode = strval($loc['mount']['mode']);
			$children = $this->_children($loc['mount'], $loc['rel']);

			// A path naming a FILE lists that one file, as `ls` does. Directories are virtual here -- implied by
			// path prefixes -- so a filename resolves to a prefix nothing matches, and the listing read as an
			// empty directory instead of the file sitting right there. A real directory still wins the tie.
			// And since a directory can't exist without files under it, an empty result inside a mount means the
			// path doesn't exist at all -- say so rather than passing a typo off as an empty directory.
			if('' !== $loc['rel'] && !$children['dirs'] && !$children['files'] && is_null($pattern)) {
				if(is_null($meta = $this->_fileMeta($loc['mount'], $loc['rel'])))
					return $this->_error(sprintf("ls: %s: No such file or directory", $path), $cwd);

				$children = ['dirs' => [], 'files' => [basename($loc['rel']) => $meta]];
			}

			foreach($children['dirs'] as $dir) {
				if(!$matches($dir))
					continue;

				$entry = [
					'name' => $dir,
					'is_dir' => true,
					'is_mount' => false,
					'size' => 0,
					'updated_at' => 0,
					'mode' => $mode,
					'can_write' => self::MODE_RW == $mode,
				];

				$lines[] = self::_lsLine($long, $entry);
				$entries[] = $entry;
			}

			foreach($children['files'] as $name => $meta) {
				if(!$matches($name))
					continue;

				$entry = [
					'name' => $name,
					'is_dir' => false,
					'is_mount' => false,
					'size' => intval($meta['size'] ?? 0),
					// Scratch buffers are stamped when they're written, so `created_at` IS their mtime.
					'updated_at' => intval($meta['updated_at'] ?? $meta['created_at'] ?? 0),
					'ext' => strval($meta['ext'] ?? strtolower(pathinfo($name, PATHINFO_EXTENSION))),
					'meta' => self::_decodeMeta($meta['meta_json'] ?? ''),
					'mode' => $mode,
					'can_write' => self::MODE_RW == $mode,
				];

				// Scratch buffers know their line count; it's what you want before paging one.
				if(array_key_exists('lines', $meta))
					$entry['lines'] = intval($meta['lines']);

				$lines[] = self::_lsLine($long, $entry, self::_metaColumns($entry['meta'], $fields));
				$entries[] = $entry;
			}
		}

		if(!$lines) {
			$result = $this->_result(
				is_null($pattern)
					? "(empty)"
					: sprintf("No entries matching '%s' in %s.", $pattern, $loc['vfs']),
				$cwd
			);

			$result['data'] = [];
			$result['data_alias'] = 'files';

			return $result;
		}

		$result = $this->_result(implode("\n", array_slice($lines, 0, $this->_options['max_results'])), $cwd);
		$result['data'] = $entries;
		$result['data_alias'] = 'files';

		return $result;
	}

	/**
	 * One listing row, for `ls` and `find` alike. $row: name, is_dir, mode, size, updated_at, lines (optional).
	 *
	 * Short form is the name and nothing else. Long form prefixes the access mode -- the SAME `[ro]`/`[rw]`
	 * a mountpoint wears, rather than a second notation to explain -- the size in RAW BYTES, not `1.2 KB`, so
	 * sizes compare exactly and a read budget is a subtraction instead of an estimate, and the timestamp.
	 * There's no `d` bit: a directory already ends in `/`, and it has no size or mtime of its own to show
	 * (directories here are virtual -- just the prefixes the files imply).
	 */
	private static function _lsLine(bool $long, array $row, string $suffix = '') : string {
		$name = strval($row['name']) . (!empty($row['is_dir']) ? '/' : '');

		if(!$long)
			return $name . $suffix;

		return sprintf("[%s] %9s %s %s%s%s",
			$row['mode'],
			!empty($row['is_dir']) ? '-' : intval($row['size'] ?? 0),
			self::_lsDate(!empty($row['is_dir']) ? 0 : intval($row['updated_at'] ?? 0)),
			array_key_exists('lines', $row) ? sprintf("%6d lines  ", intval($row['lines'])) : '',
			$name,
			$suffix
		);
	}

	/**
	 * The `ls -l` date column, fixed at 12 characters: `Mar 03 09:41` within the past year, `Mar 03  2024`
	 * beyond it -- recent files get the detail that distinguishes them, older ones get the year that does.
	 *
	 * Month first, as Unix writes it: it reads as a date, and it keeps the day's digits from running up
	 * against the size column's.
	 *
	 * Month names are English on purpose: this is read by an LLM far more often than by a person, and the
	 * models are trained on English abbreviations. Localize when a human surface needs it.
	 */
	private static function _lsDate(int $timestamp) : string {
		if($timestamp <= 0)
			return sprintf('%12s', '-');

		// `date()`, not `gmdate()` -- the platform has already set the active timezone.
		return sprintf('%s %5s',
			date('M d', $timestamp),
			date((($timestamp < (time() - 31536000)) ? 'Y' : 'H:i'), $timestamp)
		);
	}

	/**
	 * Find files by NAME pattern, recursively -- the counterpart to `search`, which matches CONTENT.
	 *
	 * `find *.md` is the common shape: a bare pattern matches the BASENAME at any depth below the working
	 * directory, the way `find . -name` does. A pattern containing `/` matches the path relative to the scope
	 * instead, so `find "guides/*.md"` means what it looks like. `*` stops at a `/`, `**` crosses them.
	 *
	 * Listing is a metadata operation -- it never opens a file -- so `meta` (markdown frontmatter, parsed at
	 * import) rides along for free and `--fields title` prints it as a column.
	 */
	private function _cmdFind(array $cmd, string $cwd) : array {
		$args = $cmd['args'];
		$pattern = '*';
		$scope = $cwd;
		$scope_was_positional = false;

		// One arg is the pattern unless it's glob-free (then it's a path); two are path + pattern.
		if(count($args) >= 2) {
			$scope = $args[0];
			$pattern = $args[1];
		} else if(1 == count($args)) {
			if(self::_isGlob($args[0])) {
				$pattern = $args[0];
			} else {
				$scope = $args[0];
				$scope_was_positional = true;
			}
		}

		// `--name` is the explicit form of the positional pattern (and the general form of `--ext`).
		if(is_string($cmd['flags']['name'] ?? null) && '' !== $cmd['flags']['name'])
			$pattern = $cmd['flags']['name'];

		$path = $this->_resolvePath(strval($scope), $cwd);
		$loc = $this->_locate($path);

		// A glob-free positional is ambiguous -- `find guides` is a directory, `find setup.md` is a name. Prefer
		// the directory when one exists, and fall back to matching a name rather than erroring on a path the
		// caller never meant as one.
		if(!$loc['mount'] && !$loc['is_virtual'] && $scope_was_positional) {
			$pattern = strval($scope);
			$path = $this->_resolvePath($cwd, $cwd);
			$loc = $this->_locate($path);
		}

		if(!$loc['mount'] && !$loc['is_virtual'])
			return $this->_error(sprintf("find: %s: No such directory", $path), $cwd);

		$limit = \DevblocksPlatform::intClamp($cmd['flags']['limit'] ?? $this->_options['max_results'], 1, 1000);

		// Depth counts levels BELOW the scope: 0 is this directory (so `find --depth 0` is `ls`), 1 adds its
		// subdirectories' contents. Absent = no limit -- which is why the sentinel is null and not 0.
		$depth = array_key_exists('depth', $cmd['flags'])
			? max(0, intval($cmd['flags']['depth']))
			: null;
		$ext = $cmd['flags']['ext'] ?? null;
		$fields = self::_fieldList($cmd['flags']['fields'] ?? null);
		$long = !empty($cmd['flags']['l']) || !empty($cmd['flags']['long']);

		if($ext)
			$ext = ltrim(strtolower(strval($ext)), '.');

		// `--type d` walks the (virtual) directory tree instead of the files -- a recursive `dirtree`.
		$type = \DevblocksPlatform::strLower(strval($cmd['flags']['type'] ?? ''));
		$want_dirs = in_array($type, ['', 'd', 'dir', 'dirs', 'directory']);
		$want_files = in_array($type, ['', 'f', 'file', 'files']);

		if(!$want_dirs && !$want_files)
			return $this->_error(sprintf("find: --type %s: expected `f` (files) or `d` (directories).", $type), $cwd);

		// An extension filter is meaningless for directories.
		if($ext)
			$want_dirs = false;

		// A pattern with a slash is matched against the relative PATH; a bare one against the basename.
		$match_path = str_contains($pattern, '/');
		$regexp = self::_globToRegexp($pattern);

		// Every mount at or below the scope (so `find` from `/` sweeps everything mounted).
		$searched = [];

		foreach($this->_mounts as $mount) {
			$rel_root = null;

			if($loc['mount'] && $mount === $loc['mount']) {
				$rel_root = $loc['rel'];
			} else if(!$loc['mount'] && str_starts_with($mount['at'] . '/', rtrim($loc['vfs'], '/') . '/')) {
				$rel_root = '';
			}

			if(is_null($rel_root))
				continue;

			$searched[] = [$mount, $rel_root];
		}

		if(!$searched)
			return $this->_error(sprintf("find: %s: nothing mounted in scope.", $loc['vfs']), $cwd);

		$entries = [];
		$dirs_seen = [];

		foreach($searched as list($mount, $rel_root)) {
			$prefix = ('' === $rel_root) ? '' : rtrim($rel_root, '/') . '/';

			$files = $this->_isTmp($mount)
				? $this->_tmpFiles()
				: self::_paths($mount['fs']->id);

			foreach($files as $name => $meta) {
				if('' !== $prefix && !str_starts_with($name, $prefix))
					continue;

				$rel = substr($name, strlen($prefix));

				if('' === $rel)
					continue;

				// Directories are VIRTUAL -- they exist only as path prefixes -- so the tree is whatever the
				// files imply. Each ancestor is emitted once, in first-seen (sorted-path) order.
				if($want_dirs && false !== strpos($rel, '/')) {
					$segments = explode('/', $rel);
					array_pop($segments);
					$dir_rel = '';

					foreach($segments as $segment) {
						$dir_rel = ('' === $dir_rel) ? $segment : ($dir_rel . '/' . $segment);
						$dir_key = $mount['at'] . '|' . $dir_rel;

						if(array_key_exists($dir_key, $dirs_seen))
							continue;

						$dirs_seen[$dir_key] = true;

						if(!is_null($depth) && substr_count($dir_rel, '/') > $depth)
							continue;

						if(!preg_match($regexp, $match_path ? $dir_rel : basename($dir_rel)))
							continue;

						$entries[] = [
							'path' => rtrim($mount['at'], '/') . '/' . $prefix . $dir_rel,
							'name' => $dir_rel,
							'filesystem' => $this->_isTmp($mount) ? '' : strval($mount['fs']->name),
							'is_dir' => true,
							'size' => 0,
							'updated_at' => 0,
							'ext' => '',
							'meta' => [],
							'mode' => strval($mount['mode']),
							'can_write' => self::MODE_RW == $mount['mode'],
						];

						if(count($entries) >= $limit)
							break 3;
					}
				}

				if(!$want_files)
					continue;

				if(!is_null($depth) && substr_count($rel, '/') > $depth)
					continue;

				$subject = $match_path ? $rel : basename($rel);

				if(!preg_match($regexp, $subject))
					continue;

				$file_ext = strval($meta['ext'] ?? strtolower(pathinfo($rel, PATHINFO_EXTENSION)));

				if($ext && $file_ext !== $ext)
					continue;

				$entries[] = [
					'path' => rtrim($mount['at'], '/') . '/' . $name,
					'name' => $rel,
					'filesystem' => $this->_isTmp($mount) ? '' : strval($mount['fs']->name),
					'is_dir' => false,
					'size' => intval($meta['size'] ?? 0),
					'updated_at' => intval($meta['updated_at'] ?? $meta['created_at'] ?? 0),
					'ext' => $file_ext,
					'meta' => self::_decodeMeta($meta['meta_json'] ?? ''),
					'mode' => strval($mount['mode']),
					'can_write' => self::MODE_RW == $mount['mode'],
				];

				if(count($entries) >= $limit)
					break 2;
			}
		}

		// Directories first, then paths ascending -- the same order `ls` uses, and deterministic regardless of
		// which mount (or the insertion-ordered scratch store) the rows came from.
		usort($entries, function($a, $b) {
			if($a['is_dir'] !== $b['is_dir'])
				return $a['is_dir'] ? -1 : 1;

			return strcmp($a['path'], $b['path']);
		});

		if(!$entries)
			return $this->_result(sprintf("No %s matching '%s' under %s.",
				$want_files ? ($want_dirs ? 'entries' : 'files') : 'directories',
				$pattern,
				$loc['vfs']
			), $cwd);

		// The scope is printed ONCE and rows are relative to it -- on a deep tree the repeated prefix is most of
		// the output. Records still carry the absolute `path`, so a follow-up `read` needs no reassembly.
		$scope_prefix = ('/' === $loc['vfs']) ? '/' : (rtrim($loc['vfs'], '/') . '/');

		$lines = [sprintf("%d %s under %s:",
			count($entries),
			(1 == count($entries)) ? 'entry' : 'entries',
			$loc['vfs']
		)];

		foreach($entries as $entry) {
			$display = str_starts_with($entry['path'], $scope_prefix)
				? substr($entry['path'], strlen($scope_prefix))
				: $entry['path'];

			$lines[] = '  ' . self::_lsLine(
				$long,
				['name' => $display] + $entry,
				self::_metaColumns($entry['meta'], $fields)
			);
		}

		$result = $this->_result(implode("\n", $lines), $cwd);
		$result['data'] = $entries;
		$result['data_alias'] = 'files';

		return $result;
	}

	private static function _isGlob(string $value) : bool {
		return str_contains($value, '*') || str_contains($value, '?');
	}

	/**
	 * Glob → regexp. `*` stops at a path separator, `**` crosses them, `?` is one non-separator character.
	 * The separator classes are written `[^\/]` because the pattern is `/`-delimited -- a bare `/` in there
	 * ends the pattern early and every glob compiles to an invalid regexp.
	 */
	private static function _globToRegexp(string $glob) : string {
		$out = '';

		for($i = 0; $i < strlen($glob); $i++) {
			$char = $glob[$i];

			if('*' === $char) {
				if(($glob[$i+1] ?? '') === '*') {
					$out .= '.*';
					$i++;
				} else {
					$out .= '[^\/]*';
				}

			} else if('?' === $char) {
				$out .= '[^\/]';

			} else {
				$out .= preg_quote($char, '/');
			}
		}

		return '/^' . $out . '$/i';
	}

	/** `--fields title,description` → ['title','description']; empty when the flag is absent or bare. */
	private static function _fieldList($flag) : array {
		if(!is_string($flag) || '' === trim($flag))
			return [];

		return array_values(array_filter(array_map('trim', explode(',', $flag))));
	}

	private static function _decodeMeta(string $json) : array {
		if('' === $json)
			return [];

		$meta = json_decode($json, true);

		return is_array($meta) ? $meta : [];
	}

	/** The requested metadata values, appended to a listing row. Missing keys print as empty, not "null". */
	private static function _metaColumns(array $meta, array $fields) : string {
		if(!$fields)
			return '';

		$out = [];

		foreach($fields as $field) {
			$value = $meta[$field] ?? '';

			if(is_array($value))
				$value = implode(',', $value);

			$out[] = strval($value);
		}

		return '  ' . implode('  ', $out);
	}

	private function _cmdCd(array $cmd, string $cwd) : array {
		$target = $this->_resolvePath($cmd['args'][0] ?? '/', $cwd);
		$loc = $this->_locate($target);

		if(!$loc['mount'] && !$loc['is_virtual'])
			return $this->_error(sprintf("cd: %s: No such directory", $target), $cwd);

		// Inside a mount, only allow directories that actually have children
		if($loc['mount'] && '' !== $loc['rel']) {
			$children = $this->_children($loc['mount'], $loc['rel']);

			if(!$children['dirs'] && !$children['files'])
				return $this->_error(sprintf("cd: %s: Not a directory", $target), $cwd);
		}

		return $this->_result('', $target);
	}

	/**
	 * Resolve a path and return its whole body, or null with a reason.
	 *
	 * Split out of _cmdRead() so a `cerb` sub-command that takes a file (`cerb code kata lint /tmp/x.kata`)
	 * reads it by exactly the same rules -- same cwd resolution, same mounts, same /tmp, same not-found
	 * wording. A second resolver would drift into accepting paths `read` rejects, or the reverse.
	 *
	 * @param string|null $resolved_path set to the absolute path the argument resolved to, for error text
	 */
	private function _readPath(string $arg, string $cwd, ?string &$error = null, ?string &$resolved_path = null) : ?string {
		$error = null;
		$resolved_path = $path = $this->_resolvePath($arg, $cwd);
		$loc = $this->_locate($path);

		if(!$loc['mount'] || '' === $loc['rel']) {
			$error = 'No such file';
			return null;
		}

		if($this->_isTmp($loc['mount'])) {
			if(is_null($content = $this->_tmpGet($loc['rel']))) {
				$error = 'No such file (a spilled buffer may have expired)';
				return null;
			}

			return $content;
		}

		if(!($row = $this->_getFile($loc['mount'], $loc['rel']))) {
			$error = 'No such file';
			return null;
		}

		return strval($row['content'] ?? '');
	}

	private function _cmdRead(array $cmd, string $cwd) : array {
		if(!($cmd['args'][0] ?? null))
			return $this->_error("read: missing operand. Usage: read <path> [--offset N] [--limit N]", $cwd);

		$read_error = null;
		$path = null;

		if(is_null($content = $this->_readPath($cmd['args'][0], $cwd, $read_error, $path)))
			return $this->_error(sprintf("read: %s: %s", $path, $read_error), $cwd);

		$total_lines = substr_count($content, "\n") + (('' === $content || str_ends_with($content, "\n")) ? 0 : 1);

		$offset = intval($cmd['flags']['offset'] ?? 0);
		$limit = intval($cmd['flags']['limit'] ?? 0);

		if($offset > 0 || $limit > 0) {
			$lines = preg_split("/\r?\n/", $content);
			$lines = array_slice($lines, max(0, $offset), $limit > 0 ? $limit : null);
			$content = implode("\n", $lines);
		}

		// NOT capped here: a pipeline has to see the whole body. exec() caps (and spills) what's left over.
		$result = $this->_result($content, $cwd);

		// Bound as `file` in a pipeline, so a template can report where its text came from.
		$result['file'] = [
			'path' => $path,
			'size' => strlen($content),
			'lines' => $total_lines,
		];

		return $result;
	}

	/**
	 * The fulltext index covering agent files. Resolved by record type rather than by id or name so an
	 * install that renamed or rebuilt it still works; `query` is the manifest option that says an engine
	 * can be read from at all.
	 */
	private static function _searchIndex() : ?\Model_SearchIndex {
		foreach(\DAO_SearchIndex::getByRecordType(\Context_AgentFile::ID) as $search_index) {
			if($search_index->getExtension()?->hasOption('query'))
				return $search_index;
		}

		return null;
	}

	/**
	 * The literal text to scan lines for. `*` and `~` are INDEX markers, not characters in the document, so
	 * scanning for them verbatim finds nothing -- which is why `search pricing~` reported 0 hits on a file
	 * it had just matched. A wildcard's prefix and a stem's root are the substrings the index actually
	 * matched on, so they are what the line scan should look for.
	 *
	 * Returns '' for a marker with nothing attached; the caller drops those, because an empty needle makes
	 * strpos() report a hit on every line.
	 */
	private static function _literalNeedle(string $term) : string {
		if(str_contains($term, '*'))
			return strval(strtok($term, '*'));

		if(str_ends_with($term, '~'))
			return ($base = rtrim($term, '~')) ? \Cerb\Services\Search\PorterStemmer::Stem($base) : '';

		return $term;
	}

	/**
	 * `+term` PINS a term: it must appear, and every other term is reported alongside it. Stripped HERE
	 * because the tokenizer treats `+` as whitespace -- by the time the index sees the word the marker is
	 * already gone. A bare `+` stays a literal, mirroring _splitQueryTerms()'s `strlen > 1` guard for `-`.
	 */
	private static function _splitRequiredTerms(array $args) : array {
		$words = [];
		$required = [];

		// Split BEFORE reading `+`, then apply it per word. One arg can hold several words -- an agent may
		// quote the whole query, and _parse() strips the quotes but keeps it as ONE token. Splitting first
		// is what makes `"+pricing academic student"` require only `pricing`, which is where the `+` was
		// actually written, rather than all three.
		foreach($args as $arg) {
			foreach(preg_split('/\s+/', strval($arg), -1, PREG_SPLIT_NO_EMPTY) as $word) {
				if(str_starts_with($word, '+') && strlen($word) > 1) {
					$word = substr($word, 1);
					$required[] = $word;
				}

				$words[] = $word;
			}
		}

		return ['words' => $words, 'required' => array_values(array_unique($required))];
	}

	/**
	 * The `--terms` table. Sorted by count DESCENDING, because the job is throwing synonyms at the index and
	 * taking the winner -- typed order would make the reader sort by eye.
	 *
	 * This is read by an AGENT, which pays for every character on every call. So: no prose about what a
	 * number means, no standalone-count column, and counts left-padded to the widest one actually present --
	 * a table of single digits costs one column, not six.
	 */
	private static function _formatTermStats(array $stats, string $scope_label) : string {
		$terms = $stats['terms'] ?? [];
		$required_docs = $stats['required_docs'] ?? null;

		$rows = self::_termRows($terms, $required_docs > 0);

		$lines = [
			sprintf("Term counts in %s (%s files):", $scope_label, number_format(intval($stats['scope_docs'] ?? 0))),
			'',
			...self::_requiredHeading($terms, $required_docs),
			...($rows ?: ['(no other terms to count)']),
		];

		if(array_filter($terms, fn($t) => 'common' == ($t['status'] ?? '')))
			$lines[] = "\n(-) Terms are on the ignore list";

		return implode("\n", $lines);
	}

	/**
	 * The miss reply: the AND query that failed, then the counts `--terms` would have given. Pure, so the
	 * wording is covered without a database. Returns '' when there is nothing to show.
	 */
	private static function _formatTermMiss(string $query, array $stats) : string {
		$terms = $stats['terms'] ?? [];
		$required_docs = $stats['required_docs'] ?? null;

		if(!($rows = self::_termRows($terms, $required_docs > 0)))
			return '';

		$lines = [
			sprintf("No AND matches for '%s'.", $query),
			"Switching to OR `--terms` to find keywords:",
			'',
			...self::_requiredHeading($terms, $required_docs),
			...$rows,
			'',
			"Require a term with `+term`",
		];

		if(array_filter($terms, fn($t) => 'common' == ($t['status'] ?? '')))
			$lines[] = "(-) Terms are on the ignore list";

		return implode("\n", $lines);
	}

	/** `+discount:` above the rows -- the requirement stated once, instead of repeated in prose. */
	private static function _requiredHeading(array $terms, $required_docs) : array {
		if(!($reqs = array_values(array_unique(array_column(array_filter($terms, fn($t) => $t['required'] ?? false), 'term')))))
			return [];

		$label = '+' . implode(' +', $reqs);

		// Without this the counts below read as counts WITHIN the requirement, which they aren't
		return [0 === $required_docs
			? sprintf('%s matches nothing; counts are standalone:', $label)
			: sprintf('%s:', $label)
		];
	}

	/**
	 * The count rows, shared by `--terms` and by a miss. Top-level terms rank by count; a compound's parts
	 * follow it indented, so `llm.agent` keeps its `llm`/`agent` beside it instead of scattering into the
	 * ranking.
	 */
	private static function _termRows(array $terms, bool $use_docs=false) : array {
		$key = $use_docs ? 'docs' : 'alone';
		$parts = [];

		foreach($terms as $term) {
			if($term['parent'] ?? null)
				$parts[$term['parent']][] = $term;
		}

		// A required term's own row is the denominator its heading already implies, and with several
		// requirements every one of them repeats it
		$top = array_values(array_filter($terms, fn($t) => is_null($t['parent'] ?? null) && !($t['required'] ?? false)));

		// The winner floats up. A skipped word has no count at all, so it sorts last rather than as a zero.
		usort($top, fn($a, $b) => (is_null($b[$key]) ? -1 : intval($b[$key])) <=> (is_null($a[$key]) ? -1 : intval($a[$key])));

		$render = fn($term) => is_null($term[$key] ?? null) ? '-' : strval(intval($term[$key]));

		// Parents and children get SEPARATE count widths. Sharing one column let a child's number set the
		// padding for every parent -- `19  anthropic` indented to `  19` because some child was 1046 -- which
		// reads as broken indentation and buries the hierarchy the child rows exist to show.
		$width = 0;

		foreach($top as $term)
			$width = max($width, strlen($render($term)));

		$rows = [];

		foreach($top as $term) {
			$rows[] = sprintf("%{$width}s  %s", $render($term), $term['token'] ?? $term['term']);

			if(!($children = $parts[$term['token']] ?? []))
				continue;

			// ONE line, inline, indented under the parent's token. A row each was ten lines of padding for
			// `auto*` alone -- this is read by an agent that pays per character, and the expansion is
			// supporting detail for the row above, not a list to scan on its own.
			$expanded = [];
			$truncated = false;

			foreach($children as $child) {
				$expanded[] = sprintf('%s (%s)', $child['token'], $render($child));
				$truncated = $truncated || ($child['truncated'] ?? false);
			}

			// A capped expansion says so. No number: the engine probes one row past the cap, which proves
			// there IS more but not how much, and a made-up count is worse than none.
			if($truncated)
				$expanded[] = '...';

			// Two past the parent's token column, not level with it -- at the same offset it reads as a
			// sibling term rather than as detail belonging to the row above
			$rows[] = sprintf('%s%s', str_repeat(' ', $width + 4), implode(' ', $expanded));
		}

		return $rows;
	}

	/**
	 * A search that matched nothing falls back to the OR view instead of guessing why.
	 *
	 * `search` is AND -- every word must be in the SAME file -- so a pile of near-synonyms reliably matches
	 * nothing. The useful answer is not a diagnosis of that; it is the per-word counts, which is exactly
	 * what `--terms` reports. Throw synonyms at it, keep the winner, require it, and throw the next set.
	 *
	 * Deliberately no prose about WHICH word to drop or what the caller probably meant: the counts say it,
	 * and predicting intent costs tokens on every miss to restate what the numbers already show.
	 *
	 * Costs one index query, on the MISS path only -- both call sites are terminal returns, so a successful
	 * search never reaches here. Degrades to the bare message when no index answers.
	 */
	private function _searchMiss(array $cmd, string $query, string $cwd, array $params) : array {
		$bare = sprintf("No AND matches for '%s'.", $query);

		if(!($search_index = self::_searchIndex()))
			return $this->_result($bare, $cwd);

		$parsed = self::_splitRequiredTerms($cmd['args'] ?? []);

		// Keep the requirements. A miss on `+discount academic school` is the SECOND step of the loop: the
		// caller already established `discount`, so counting the new words across the whole corpus would
		// throw that away and answer a question they stopped asking.
		$stats = $search_index->getExtension()->queryTermStats($search_index, $parsed['words'], $parsed['required'], $params);

		if(!($text = self::_formatTermMiss($query, $stats)))
			return $this->_result($bare, $cwd);

		$out = $this->_result($text, $cwd);
		$out['data'] = $stats['terms'];
		$out['data_alias'] = 'terms';

		return $out;
	}

	/**
	 * Search file CONTENT via the fulltext search index, scoped by `filesystem.id:` and an optional path
	 * prefix.
	 *
	 * EVERY positional arg is part of the query -- `search filled disc` searches "filled disc" (quoting also
	 * works). Scope with `--path`, not extra positional args.
	 */
	private function _cmdSearch(array $cmd, string $cwd) : array {
		$query = trim(implode(' ', $cmd['args']));

		if('' === $query)
			return $this->_error("search: missing query. Usage: search <query...> [--path <path>] [--ext md] [--limit N]", $cwd);

		$limit = \DevblocksPlatform::intClamp($cmd['flags']['limit'] ?? $this->_options['max_results'], 1, 1000);
		$ext = $cmd['flags']['ext'] ?? null;
		$scope = $cmd['flags']['path'] ?? $cwd;

		$loc = $this->_locate($this->_resolvePath(strval($scope), $cwd));

		// Which filesystems (and optional name prefix) are in scope
		$fs_ids = [];
		$prefix = '';

		// /tmp is never in the fulltext index (its files are the host's scratch state, not `agent_file` rows),
		// so point at the pipeline instead of silently returning nothing.
		if($this->_isTmp($loc['mount']))
			return $this->_error("search: /tmp isn't indexed. Filter it inline instead: read <path> | lines|filter(l => \"term\" in l)|join(\"\\n\")", $cwd);

		if($loc['mount']) {
			$fs_ids[] = $loc['mount']['fs']->id;
			$prefix = $loc['rel'];

		} else if($loc['is_virtual']) {
			foreach($this->_mounts as $mount) {
				if($this->_isTmp($mount))
					continue;

				if(str_starts_with($mount['at'] . '/', rtrim($loc['vfs'], '/') . '/'))
					$fs_ids[] = $mount['fs']->id;
			}
		}

		$fs_ids = array_values(array_unique($fs_ids));

		if(!$fs_ids)
			return $this->_error(sprintf("search: %s: nothing mounted in scope.", $loc['vfs']), $cwd);

		// Name the scope the way the caller sees it -- mountpoints, not filesystem ids
		$scope_label = $loc['mount']
			? rtrim($loc['vfs'], '/')
			: implode(', ', array_map(
				fn($m) => $m['at'],
				array_filter($this->_mounts, fn($m) => in_array($m['fs']?->id ?? 0, $fs_ids))
			));
		$scope_label = $scope_label ?: '/';

		// `top:k` asks the index to SCORE and return only the k most relevant files (`--top 0` = every match,
		// unranked). The scoring is limited to the filters below -- they reach the index as co-params, so the
		// k is spent inside the mounted volumes instead of across every volume in the install.
		$top = \DevblocksPlatform::intClamp($cmd['flags']['top'] ?? 25, 0, 1000);

		// The scope filters, which are also what the index scores within
		$q = [
			sprintf('filesystem.id:[%s]', implode(',', $fs_ids)),
		];

		// [Later] materialize the path tree -- a name-prefix match can't use an index well as volumes grow
		if('' !== $prefix)
			$q[] = sprintf('name:"%s*"', self::_sanitizeQueryValue($prefix));

		if($ext)
			$q[] = sprintf('file_extension:"%s"', self::_sanitizeQueryValue(ltrim(strtolower(strval($ext)), '.')));

		$view = new \View_AgentFile();
		$error = null;
		$params = $view->getParamsFromQuickSearch(implode(' ', $q), [], $error);

		if(false === $params || !is_array($params))
			return $this->_error(sprintf("search: %s", $error ?: 'could not parse the query.'), $cwd);

		// --terms: report what each word matches instead of searching. Placed here so it inherits the same
		// mount scope, --path and --ext the search itself would have used.
		if($cmd['flags']['terms'] ?? false) {
			if(!($search_index = self::_searchIndex()))
				return $this->_error("search --terms: no search index covers agent files.", $cwd);

			$parsed = self::_splitRequiredTerms($cmd['args']);

			$stats = $search_index->getExtension()->queryTermStats(
				$search_index,
				$parsed['words'],
				$parsed['required'],
				$params
			);

			if(!$stats)
				return $this->_error("search --terms: this search index can't report term counts.", $cwd);

			$out = $this->_result(self::_formatTermStats($stats, $scope_label), $cwd);
			$out['data'] = $stats['terms'];
			$out['data_alias'] = 'terms';

			return $out;
		}

		// id => position, so relevance survives the worklist fetch (which can only sort by a column)
		$rank = [];

		if($top && ($search_index = self::_searchIndex())) {
			// Ask the index for the ranked ids directly rather than going through `text:(... top:k)`. Same
			// scoping either way, but the worklist has no way to sort by score, so the group form returns the
			// right SET in the wrong ORDER. This also keeps it to ONE index query.
			$docs = $search_index->getExtension()->queryDocumentsWithScore(
				$search_index,
				$query,
				limit: $top,
				co_params: $params
			);

			if(!$docs)
				return $this->_searchMiss($cmd, $query, $cwd, $params);

			$doc_ids = array_column($docs, 'id');
			$rank = array_flip($doc_ids);

			$params[] = new \DevblocksSearchCriteria(
				\SearchFields_AgentFile::ID,
				\DevblocksSearchCriteria::OPER_IN,
				$doc_ids
			);

		} else {
			// Unranked: every match in scope, capped by --limit. The group form `(...)` matches the terms
			// (more forgiving than a strict quoted phrase); the index still scores within the co-filters.
			$params = $view->getParamsFromQuickSearch(
				sprintf('text:(%s) %s', self::_sanitizeQueryValue($query), implode(' ', $q)),
				[],
				$error
			);

			if(false === $params || !is_array($params))
				return $this->_error(sprintf("search: %s", $error ?: 'could not parse the query.'), $cwd);
		}

		$columns = [
			\SearchFields_AgentFile::ID,
			\SearchFields_AgentFile::FILESYSTEM_ID,
			\SearchFields_AgentFile::NAME,
			\SearchFields_AgentFile::SIZE,
			\SearchFields_AgentFile::FRONTMATTER_JSON,
			\SearchFields_AgentFile::CONTENT,
		];

		list($rows,) = \DAO_AgentFile::search($columns, $params, $limit, 0, \SearchFields_AgentFile::NAME, true, false);

		if(!$rows)
			return $this->_searchMiss($cmd, $query, $cwd, $params);

		// The index matches per FILE (tokenized/stemmed), so a file can legitimately match with no literal line
		// hit. Per file: try the whole phrase, then any single term.
		$terms = array_values(array_filter(array_map(
			self::_literalNeedle(...),
			preg_split('/\s+/', \DevblocksPlatform::strLower($query), -1, PREG_SPLIT_NO_EMPTY)
		), fn($t) => '' !== $t));

		$needle = implode(' ', $terms);

		$results = [];

		foreach($rows as $row) {
			$name = strval($row[\SearchFields_AgentFile::NAME] ?? '');
			$filesystem_id = intval($row[\SearchFields_AgentFile::FILESYSTEM_ID] ?? 0);
			$content = strval($row[\SearchFields_AgentFile::CONTENT] ?? '');

			$body = preg_split("/\r?\n/", $content);
			$hits = [];

			if('' !== $needle) {
				foreach($body as $i => $line) {
					if(str_contains(\DevblocksPlatform::strLower($line), $needle))
						$hits[] = ['line' => $i + 1, 'text' => trim($line)];
				}
			}

			if(!$hits && $terms) {
				foreach($body as $i => $line) {
					$haystack = \DevblocksPlatform::strLower($line);

					foreach($terms as $term) {
						if(false !== strpos($haystack, $term)) {
							$hits[] = ['line' => $i + 1, 'text' => trim($line)];
							break;
						}
					}
				}
			}

			// `meta` is the file's own metadata (frontmatter for .md), parsed once at import and stored -- it
			// rides along so a chain can read a title/summary without a `read` per candidate. ALWAYS a map, even
			// when absent, so `r.meta.title ?? r.path` is safe across volumes where only some files carry it.
			$meta = json_decode(strval($row[\SearchFields_AgentFile::FRONTMATTER_JSON] ?? ''), true);

			$results[] = [
				'id' => intval($row[\SearchFields_AgentFile::ID] ?? 0),
				'path' => $this->_vfsPathFor($filesystem_id, $name),
				'name' => $name,
				'filesystem' => $this->_filesystemName($filesystem_id),
				'size' => intval($row[\SearchFields_AgentFile::SIZE] ?? 0),
				'lines' => count($body),
				'hits' => count($hits),
				'meta' => is_array($meta) ? $meta : [],
				'matches' => $hits,
				'content' => $content,
			];
		}

		// Most hits first: that's the ranking a reader actually acts on. The index PICKS the candidates (by
		// TF-IDF, within scope) but does NOT order them, because for the single-term queries this command
		// mostly sees, `token_tf` is just term-count over document length -- which ranks a short release note
		// mentioning a word 8 times above the reference page mentioning it 37 times. Measured on cerb-docs.
		// Index rank breaks ties, so a file that matched only through stemming (zero literal hits) still has
		// a deterministic place instead of floating.
		usort($results, fn($a, $b) =>
			($b['hits'] <=> $a['hits'])
				?: (($rank[$a['id']] ?? PHP_INT_MAX) <=> ($rank[$b['id']] ?? PHP_INT_MAX))
		);

		$header = sprintf("%d file%s matched%s:",
			count($results),
			(1 == count($results)) ? '' : 's',
			$top ? sprintf(" (top %d by relevance)", $top) : ''
		);

		// DEFAULT: hit counts + paths only -- that's the decision surface (which file to open). `--lines` adds
		// the numbered matches, which is pages of text and rarely what you needed to choose.
		if(!($cmd['flags']['lines'] ?? false)) {
			$lines = [$header];

			foreach($results as $result)
				$lines[] = sprintf("  %5d  %s", $result['hits'], $result['path']);

			$out = $this->_result(implode("\n", $lines), $cwd);
			$out['data'] = $results;
			$out['data_alias'] = 'results';
			return $out;
		}

		// STACKED output: the candidate list first (like `grep -l`), then the numbered matches grouped per file
		// (path printed once, not per line). A file that matched the index with no literal line says so rather
		// than vanishing.
		$blocks = [];
		$budget = $limit;

		foreach($results as $result) {
			if(!$result['matches']) {
				$blocks[] = $result['path'] . "\n  (indexed match; no literal line)";
				continue;
			}

			$block = [$result['path']];

			foreach($result['matches'] as $hit) {
				if($budget <= 0) {
					$block[] = sprintf("  ... (%d more)", count($result['matches']) - (count($block) - 1));
					break;
				}

				$block[] = sprintf("  %d: %s", $hit['line'], $hit['text']);
				$budget--;
			}

			$blocks[] = implode("\n", $block);
		}

		$summary = $header . "\n" . implode("\n", array_map(fn($r) => '  ' . $r['path'], $results));

		$out = $this->_result($summary . "\n\n" . implode("\n\n", $blocks), $cwd);
		$out['data'] = $results;
		$out['data_alias'] = 'results';
		return $out;
	}

	/** The mounted volume's name for a filesystem id (empty when it isn't mounted here). */
	private function _filesystemName(int $filesystem_id) : string {
		foreach($this->_mounts as $mount) {
			if(!$this->_isTmp($mount) && $mount['fs']->id == $filesystem_id)
				return strval($mount['fs']->name);
		}

		return '';
	}

	/** Strip characters that would break out of a quick-search value. */
	private static function _sanitizeQueryValue(string $value) : string {
		return trim(str_replace(['"', '(', ')', ':'], ' ', $value));
	}

	private function _cmdWrite(array $cmd, string $cwd, ?string $payload, bool $append) : array {
		$verb = $append ? 'append' : 'write';

		if(!($cmd['args'][0] ?? null))
			return $this->_error(sprintf("%s: missing operand. Usage: %s <path>", $verb, $verb), $cwd);

		$path = $this->_resolvePath($cmd['args'][0], $cwd);
		$loc = $this->_locate($path);

		if(!$loc['mount'] || '' === $loc['rel'])
			return $this->_error(sprintf("%s: %s: Not inside a mounted filesystem", $verb, $path), $cwd);

		if(self::MODE_RW != $loc['mount']['mode'])
			return $this->_error(sprintf("%s: %s: Read-only filesystem", $verb, $path), $cwd);

		if(is_null($payload))
			return $this->_error(sprintf("%s: no content supplied.", $verb), $cwd);

		if(strlen($loc['rel']) > self::MAX_PATH_LENGTH)
			return $this->_error(sprintf("%s: %s: path is longer than %d characters", $verb, $path, self::MAX_PATH_LENGTH), $cwd);

		if($this->_isTmp($loc['mount'])) {
			$content = $append ? (strval($this->_tmpGet($loc['rel'])) . $payload) : $payload;
			$entry = $this->_tmpPut($loc['rel'], $content);

			return $this->_result(sprintf("Wrote %s to %s", \DevblocksPlatform::strPrettyBytes($entry['size']), $path), $cwd);
		}

		$row = $this->_getFile($loc['mount'], $loc['rel']);
		$content = $append ? (strval($row['content'] ?? '') . $payload) : $payload;

		// sha1/size/file_extension/frontmatter_json are derived by the DAO from the content + the row's path,
		// so every writer stays consistent without repeating the derivation here.
		$fields = [
			\DAO_AgentFile::CONTENT => $content,
			\DAO_AgentFile::UPDATED_AT => time(),
		];

		if($row) {
			$file_id = intval($row['id']);
			\DAO_AgentFile::update($file_id, $fields);
		} else {
			$fields[\DAO_AgentFile::FILESYSTEM_ID] = $loc['mount']['fs']->id;
			$fields[\DAO_AgentFile::NAME] = $loc['rel'];
			$file_id = \DAO_AgentFile::create($fields);
		}

		self::_flushCache($loc['mount']['fs']->id);

		\DAO_AgentFile::indexRecords([$file_id]);

		return $this->_result(sprintf("Wrote %s to %s", \DevblocksPlatform::strPrettyBytes(strlen($content)), $path), $cwd);
	}

	/**
	 * Patch-style search/replace: swap an EXACT snippet for another, in place. The needle must match exactly ONE
	 * place -- 0 or many is an error the caller fixes by expanding the context until it's unique. Anchoring an
	 * edit to unique CONTENT (not a byte offset or line number) is what lets collaborators edit the same file
	 * without a full-file `write` clobbering each other or a prior edit shifting positions out from under them.
	 */
	private function _cmdEdit(array $cmd, string $cwd, ?string $find, ?string $replace) : array {
		if(!($cmd['args'][0] ?? null))
			return $this->_error("edit: missing operand. Usage: edit <path> (find/replace are supplied out-of-band)", $cwd);

		$path = $this->_resolvePath($cmd['args'][0], $cwd);
		$loc = $this->_locate($path);

		if(!$loc['mount'] || '' === $loc['rel'])
			return $this->_error(sprintf("edit: %s: No such file", $path), $cwd);

		if(self::MODE_RW != $loc['mount']['mode'])
			return $this->_error(sprintf("edit: %s: Read-only filesystem", $path), $cwd);

		if(is_null($find) || '' === $find)
			return $this->_error("edit: no find text supplied.", $cwd);

		// edit NEVER creates -- an absent file is a `write`, and a missing needle in a phantom file would just
		// read as "not found", hiding the real problem.
		if($this->_isTmp($loc['mount'])) {
			if(is_null($content = $this->_tmpGet($loc['rel'])))
				return $this->_error(sprintf("edit: %s: No such file. Use `write` to create it.", $path), $cwd);

			$row = null;
		} else {
			$row = $this->_getFile($loc['mount'], $loc['rel']);

			if(!$row)
				return $this->_error(sprintf("edit: %s: No such file. Use `write` to create it.", $path), $cwd);

			$content = strval($row['content'] ?? '');
		}

		$count = substr_count($content, $find);

		if(0 === $count)
			return $this->_error(sprintf("edit: %s: the find text was not found. Read the file and copy an exact snippet (whitespace matters).", $path), $cwd);

		if($count > 1)
			return $this->_error(sprintf("edit: %s: the find text matches %d places. Add surrounding lines until it uniquely identifies one.", $path, $count), $cwd);

		$replace = strval($replace);
		$at = strpos($content, $find);
		$line = substr_count($content, "\n", 0, $at) + 1;
		$new_content = substr_replace($content, $replace, $at, strlen($find));

		if($this->_isTmp($loc['mount'])) {
			$this->_tmpPut($loc['rel'], $new_content);
		} else {
			// An edit can rewrite the frontmatter block itself; the DAO re-derives it (and sha1/size) from the
			// new body.
			\DAO_AgentFile::update(intval($row['id']), [
				\DAO_AgentFile::CONTENT => $new_content,
				\DAO_AgentFile::UPDATED_AT => time(),
			]);

			self::_flushCache($loc['mount']['fs']->id);

			\DAO_AgentFile::indexRecords([intval($row['id'])]);
		}

		return $this->_result(sprintf("Edited %s (1 replacement at line %d; %s -> %s)",
			$path,
			$line,
			\DevblocksPlatform::strPrettyBytes(strlen($content)),
			\DevblocksPlatform::strPrettyBytes(strlen($new_content))
		), $cwd);
	}

	/**
	 * Copy ONE file. There's no `move`: a rename across mounts of differing modes is a copy plus a delete with
	 * a half-failed state in between, so the move is `copy` then `rm` and the failure modes stay visible.
	 *
	 * The source may live anywhere mounted, in any mode -- lifting a file OUT of a read-only volume is the
	 * main reason this exists. Only the destination has to be read-write.
	 */
	private function _cmdCopy(array $cmd, string $cwd) : array {
		if(!($cmd['args'][0] ?? null) || !($cmd['args'][1] ?? null))
			return $this->_error("copy: missing operand. Usage: copy <source> <destination> [-f]", $cwd);

		$src_arg = strval($cmd['args'][0]);
		$dest_arg = strval($cmd['args'][1]);

		if(self::_isGlob($src_arg) || self::_isGlob($dest_arg))
			return $this->_error("copy: patterns aren't supported -- copy one file per command.", $cwd);

		$src_path = $this->_resolvePath($src_arg, $cwd);
		$src = $this->_locate($src_path);

		if(!$src['mount'] || '' === $src['rel'])
			return $this->_error(sprintf("copy: %s: No such file", $src_path), $cwd);

		if($this->_isTmp($src['mount'])) {
			// null here also means a spilled buffer whose resource TTL lapsed -- say so, rather than copying
			// an empty file and calling it a success.
			if(is_null($content = $this->_tmpGet($src['rel'])))
				return $this->_error(sprintf("copy: %s: No such file (a scratch buffer may have expired).", $src_path), $cwd);

		} else {
			if(!($src_row = $this->_getFile($src['mount'], $src['rel'])))
				return $this->_error(sprintf("copy: %s: No such file", $src_path), $cwd);

			$content = strval($src_row['content'] ?? '');
		}

		$dest_path = $this->_resolvePath($dest_arg, $cwd);
		$dest = $this->_locate($dest_path);

		// Refuse a read-only destination BEFORE the directory probe below, which costs a query. Appending a
		// basename can't change which mount we land in, so this verdict holds for the final path too.
		if($dest['mount'] && self::MODE_RW != $dest['mount']['mode'])
			return $this->_error(sprintf("copy: %s: Read-only filesystem", $dest_path), $cwd);

		// A destination naming a directory keeps the source's filename, as `cp` does. Without this,
		// `copy a.md /docs/guides` writes a FILE called `guides` beside the directory of the same name --
		// which this VFS permits (directories are just path prefixes) and nobody ever means.
		if(str_ends_with(rtrim($dest_arg), '/') || $this->_isDirectory($dest_path)) {
			$dest_path = rtrim($dest_path, '/') . '/' . basename($src['rel']);
			$dest = $this->_locate($dest_path);
		}

		if(!$dest['mount'] || '' === $dest['rel'])
			return $this->_error(sprintf("copy: %s: Not inside a mounted filesystem", $dest_path), $cwd);

		if(self::MODE_RW != $dest['mount']['mode'])
			return $this->_error(sprintf("copy: %s: Read-only filesystem", $dest_path), $cwd);

		if($dest['vfs'] === $src['vfs'])
			return $this->_error(sprintf("copy: %s and %s are the same file", $src_path, $dest_path), $cwd);

		if(strlen($dest['rel']) > self::MAX_PATH_LENGTH)
			return $this->_error(sprintf("copy: %s: path is longer than %d characters", $dest_path, self::MAX_PATH_LENGTH), $cwd);

		$dest_row = null;
		$overwrote = null;

		if($this->_isTmp($dest['mount'])) {
			$files = $this->_tmpFiles();

			if(array_key_exists($dest['rel'], $files))
				$overwrote = intval($files[$dest['rel']]['size'] ?? 0);

		} else if(($dest_row = $this->_getFile($dest['mount'], $dest['rel']))) {
			$overwrote = intval($dest_row['size'] ?? 0);
		}

		// `cp` overwrites silently. Here the caller is often a model with no confirmation prompt in front of
		// it, so a mistyped destination has to fail loudly and name the way through.
		if(!is_null($overwrote) && empty($cmd['flags']['f']) && empty($cmd['flags']['force']))
			return $this->_error(sprintf("copy: %s: File exists. Use -f to overwrite, or `rm` it first.", $dest_path), $cwd);

		if($this->_isTmp($dest['mount'])) {
			$this->_tmpPut($dest['rel'], $content);

		} else {
			// The DAO derives sha1/size/file_extension/frontmatter_json from the content + the DESTINATION's
			// path -- so a `.md` copied to `.txt` can't carry metadata its new name doesn't imply.
			// IMPORT_UUID is deliberately NOT copied: a stale stamp would get the copy pruned by the next
			// import of the volume it landed in.
			$fields = [
				\DAO_AgentFile::CONTENT => $content,
				\DAO_AgentFile::UPDATED_AT => time(),
			];

			// Naive get-then-write rather than an `INSERT ... SELECT`: it's one inline row, and going through
			// the DAO is what fires markContextChanged().
			if($dest_row) {
				$dest_id = intval($dest_row['id']);
				\DAO_AgentFile::update($dest_id, $fields);
			} else {
				$fields[\DAO_AgentFile::FILESYSTEM_ID] = $dest['mount']['fs']->id;
				$fields[\DAO_AgentFile::NAME] = $dest['rel'];
				$dest_id = \DAO_AgentFile::create($fields);
			}

			self::_flushCache($dest['mount']['fs']->id);

			\DAO_AgentFile::indexRecords([$dest_id]);
		}

		return $this->_result(sprintf("Copied %d bytes to %s%s",
			strlen($content),
			$dest_path,
			is_null($overwrote) ? '' : sprintf(" (overwrote %d bytes)", $overwrote)
		), $cwd);
	}

	private function _cmdRm(array $cmd, string $cwd) : array {
		if(!($cmd['args'][0] ?? null))
			return $this->_error("rm: missing operand. Usage: rm <path>", $cwd);

		$path = $this->_resolvePath($cmd['args'][0], $cwd);
		$loc = $this->_locate($path);

		if(!$loc['mount'] || '' === $loc['rel'])
			return $this->_error(sprintf("rm: %s: No such file", $path), $cwd);

		if(self::MODE_RW != $loc['mount']['mode'])
			return $this->_error(sprintf("rm: %s: Read-only filesystem", $path), $cwd);

		if($this->_isTmp($loc['mount'])) {
			if(!$this->_tmpDelete($loc['rel']))
				return $this->_error(sprintf("rm: %s: No such file", $path), $cwd);

			return $this->_result(sprintf("Removed %s", $path), $cwd);
		}

		if(!($row = $this->_getFile($loc['mount'], $loc['rel'])))
			return $this->_error(sprintf("rm: %s: No such file", $path), $cwd);

		\DAO_AgentFile::delete(intval($row['id']));
		self::_flushCache($loc['mount']['fs']->id);

		return $this->_result(sprintf("Removed %s", $path), $cwd);
	}

	/**
	 * `cerb <namespace> ...` -- the Cerb CLI. Everything about it lives in Cerb\Agent\Cli; this evaluator
	 * stays a filesystem and knows nothing about record types.
	 *
	 * The CLI has no cwd and no paths, so `$cwd` only rides through unchanged. `data`/`data_alias` are
	 * forwarded so a `| chain` gets the command's ROWS, not just its rendered text.
	 */
	private function _cmdCerb(array $cmd, string $cwd, ?string $payload = null) : array {
		// With nothing enabled the verb doesn't exist at all -- same reply as any unknown command, matching
		// the fact that `help` doesn't document it either. A "no commands are enabled" message here would
		// advertise a capability this host wasn't given.
		if(!$this->hasCli())
			return $this->_error(sprintf("%s: command not found. Try `help`.", self::KIND_CLI), $cwd);

		// What the host can offer a sub-command beyond its own arguments. Deliberately a narrow bundle rather
		// than the Filesystem itself: a command that could reach the evaluator could write, and the CLI's
		// scope chokepoint only governs what it knows it handed out.
		$result = Cli::exec($cmd['args'], $cmd['flags'], $this->_options['cerb'], [
			'cwd' => $cwd,
			'payload' => $payload,
			'read' => fn(string $arg, ?string &$error = null, ?string &$path = null) : ?string
				=> $this->_readPath($arg, $cwd, $error, $path),
		]);

		$out = ($result['error'] ?? false)
			? $this->_error(strval($result['output'] ?? ''), $cwd)
			: $this->_result(strval($result['output'] ?? ''), $cwd);

		if(is_array($result['data'] ?? null)) {
			$out['data'] = $result['data'];

			if($result['data_alias'] ?? null)
				$out['data_alias'] = strval($result['data_alias']);
		}

		return $out;
	}

	// ---------------------------------------------------------------------
	// Path + mount resolution
	// ---------------------------------------------------------------------

	/**
	 * Resolve a VFS path to its mount (if any).
	 * @return array ['vfs'=>string, 'mount'=>array|null, 'rel'=>string, 'is_virtual'=>bool]
	 */
	private function _locate(string $vfs_path) : array {
		$vfs_path = self::_normalizePath($vfs_path);

		foreach($this->_mounts as $mount) {
			$at = $mount['at'];

			if($vfs_path === $at)
				return ['vfs' => $vfs_path, 'mount' => $mount, 'rel' => '', 'is_virtual' => false];

			if(str_starts_with($vfs_path, rtrim($at, '/') . '/')) {
				$rel = substr($vfs_path, strlen(rtrim($at, '/')) + 1);
				return ['vfs' => $vfs_path, 'mount' => $mount, 'rel' => $rel, 'is_virtual' => false];
			}
		}

		// Not in a mount -- is it a virtual dir ABOVE one? (e.g. `/` or `/memory` when /memory/me is mounted)
		$prefix = ('/' === $vfs_path) ? '/' : $vfs_path . '/';

		foreach($this->_mounts as $mount) {
			if(str_starts_with($mount['at'] . '/', $prefix))
				return ['vfs' => $vfs_path, 'mount' => null, 'rel' => '', 'is_virtual' => true];
		}

		return ['vfs' => $vfs_path, 'mount' => null, 'rel' => '', 'is_virtual' => false];
	}

	/** Mountpoint segments that sit directly under $vfs_path (so `ls /` lists the volumes). */
	private function _childMountSegments(string $vfs_path) : array {
		$prefix = ('/' === $vfs_path) ? '/' : rtrim($vfs_path, '/') . '/';
		$segments = [];

		foreach($this->_mounts as $mount) {
			if(!str_starts_with($mount['at'], $prefix))
				continue;

			$rest = substr($mount['at'], strlen($prefix));

			if('' === $rest)
				continue;

			$seg = explode('/', $rest)[0];
			$segments[$seg] = true;
		}

		ksort($segments);
		return array_keys($segments);
	}

	/**
	 * Does this path name a directory? Mountpoints and the virtual dirs above them always do; inside a mount,
	 * a directory exists only if some file's path implies it.
	 */
	private function _isDirectory(string $vfs_path) : bool {
		$loc = $this->_locate($vfs_path);

		if($loc['is_virtual'] || ($loc['mount'] && '' === $loc['rel']))
			return true;

		if(!$loc['mount'])
			return false;

		$children = $this->_children($loc['mount'], $loc['rel']);

		return $children['dirs'] || $children['files'];
	}

	/**
	 * The access mode a mountpoint reports. An exact mount answers for itself; an intermediate segment (a
	 * mountpoint nested like `/team/notes` makes `/team` one) answers for the tree below it, so it reads `rw`
	 * when anything writable lives down there.
	 */
	private function _modeAtPath(string $vfs_path) : string {
		$prefix = rtrim($vfs_path, '/') . '/';
		$mode = self::MODE_RO;

		foreach($this->_mounts as $mount) {
			if($mount['at'] === $vfs_path)
				return $mount['mode'];

			if(str_starts_with($mount['at'] . '/', $prefix) && self::MODE_RW == $mount['mode'])
				$mode = self::MODE_RW;
		}

		return $mode;
	}

	/** The absolute VFS path for a file in a filesystem (first mount of that filesystem wins). */
	private function _vfsPathFor(int $filesystem_id, string $name) : string {
		foreach($this->_mounts as $mount) {
			if($this->_isTmp($mount))
				continue;

			if($mount['fs']->id == $filesystem_id)
				return rtrim($mount['at'], '/') . '/' . $name;
		}

		return $name;
	}

	/** Immediate children (dirs + files) of a directory inside a mount. */
	private function _children(array $mount, string $dir) : array {
		// The scratch store is a flat name→entry map, but a name may still contain slashes (a spill under
		// `guides/`, or an explicit write), so it splits into virtual directories exactly like a volume --
		// otherwise `ls` and `find` would disagree about the same namespace.
		$paths = $this->_isTmp($mount)
			? $this->_tmpFiles()
			: self::_paths($mount['fs']->id);
		$prefix = ('' === $dir) ? '' : rtrim($dir, '/') . '/';

		$dirs = [];
		$files = [];

		foreach($paths as $name => $meta) {
			if('' !== $prefix && !str_starts_with($name, $prefix))
				continue;

			$rest = substr($name, strlen($prefix));

			if('' === $rest)
				continue;

			if(false !== ($slash = strpos($rest, '/'))) {
				$dirs[substr($rest, 0, $slash)] = true;
			} else {
				$files[$rest] = $meta;
			}
		}

		ksort($dirs);
		ksort($files);

		return ['dirs' => array_keys($dirs), 'files' => $files];
	}

	/**
	 * A single file's listing metadata inside a mount, or null when the path doesn't name a file. Reads the same
	 * path list `_children()` does, so a listing can't disagree with itself about what exists.
	 */
	private function _fileMeta(array $mount, string $rel) : ?array {
		$paths = $this->_isTmp($mount)
			? $this->_tmpFiles()
			: self::_paths($mount['fs']->id);

		return $paths[$rel] ?? null;
	}

	private function _getFile(array $mount, string $rel) : ?array {
		$db = \DevblocksPlatform::services()->database();

		$row = $db->GetRowReader(sprintf("SELECT id, name, content, size FROM agent_file WHERE filesystem_id = %d AND name = %s LIMIT 1",
			$mount['fs']->id,
			$db->qstr($rel)
		));

		return $row ?: null;
	}

	/**
	 * The path list for a filesystem (name => meta) — the same per-request cached view the commands use,
	 * exposed for callers that need to OFFER paths rather than resolve one (the agentPrompt's `@<volume>/<path>`
	 * autocomplete). Read-only: no mount resolution, no cwd, no access check — the caller decides which
	 * filesystem ids it's allowed to list.
	 */
	public static function listPaths(int $filesystem_id) : array {
		return self::_paths($filesystem_id);
	}

	/**
	 * The path list for a filesystem (name => meta), cached per request.
	 * [Future] persist this materialized tree and invalidate on agent_filesystem.updated_at.
	 */
	private static function _paths(int $filesystem_id) : array {
		if(array_key_exists($filesystem_id, self::$_path_cache))
			return self::$_path_cache[$filesystem_id];

		$db = \DevblocksPlatform::services()->database();

		// `frontmatter_json` rides along (parsed once at import) so a listing can show/return a file's own
		// metadata without a read per candidate. Kept RAW here -- decoded only for the rows a command lists.
		$rows = $db->GetArrayReader(sprintf("SELECT name, size, updated_at, file_extension, frontmatter_json FROM agent_file WHERE filesystem_id = %d ORDER BY name ASC",
			$filesystem_id
		));

		$paths = [];

		foreach($rows as $row)
			$paths[$row['name']] = [
				'size' => intval($row['size']),
				'updated_at' => intval($row['updated_at']),
				'ext' => strval($row['file_extension'] ?? ''),
				'meta_json' => strval($row['frontmatter_json'] ?? ''),
			];

		return self::$_path_cache[$filesystem_id] = $paths;
	}

	public static function _flushCache(?int $filesystem_id = null) : void {
		if(is_null($filesystem_id))
			self::$_path_cache = [];
		else
			unset(self::$_path_cache[$filesystem_id]);
	}

	// ---------------------------------------------------------------------
	// /tmp -- the host-owned scratch store
	// ---------------------------------------------------------------------

	private function _isTmp(?array $mount) : bool {
		return is_array($mount) && self::KIND_TMP == ($mount['kind'] ?? self::KIND_VOLUME);
	}

	/** [name => entry], oldest first. */
	private function _tmpFiles() : array {
		return is_array($this->_tmp['files'] ?? null) ? $this->_tmp['files'] : [];
	}

	/**
	 * Park overflow output in /tmp under a content-hashed name, so re-running the same command reuses the same
	 * buffer instead of piling up copies.
	 */
	private function _tmpSpill(string $verb, string $content) : ?array {
		if(!is_array($this->_options['tmp']))
			return null;

		$name = sprintf('%s-%s.txt',
			\DevblocksPlatform::strAlphaNum(\DevblocksPlatform::strLower($verb), '-') ?: 'out',
			substr(sha1($content), 0, 6)
		);

		$entry = $this->_tmpPut($name, $content);

		return [
			'path' => self::TMP_AT . '/' . $name,
			'size' => $entry['size'],
			'lines' => $entry['lines'],
		];
	}

	/** Write a scratch file: inline while it's small, otherwise spilled to an automation_resource. */
	private function _tmpPut(string $name, string $content) : array {
		$entry = [
			'size' => strlen($content),
			'lines' => substr_count($content, "\n") + (('' === $content || str_ends_with($content, "\n")) ? 0 : 1),
			'created_at' => time(),
		];

		if($entry['size'] > self::TMP_INLINE_MAX_BYTES && ($token = self::_resourcePut($name, $content))) {
			$entry['resource'] = $token;
		} else {
			$entry['content'] = $content;
		}

		$files = $this->_tmpFiles();
		unset($files[$name]); // re-add at the end so insertion order stays oldest-first
		$files[$name] = $entry;

		$this->_tmp['files'] = $files;
		$this->_tmp_dirty = true;

		$this->_tmpEnforceInlineTotal();

		return $entry;
	}

	/**
	 * Keep the inline bytes under the ceiling by spilling the OLDEST inline buffers to resources -- they stay
	 * readable at the same path, they just stop riding along in the host's state (a continuation dict).
	 */
	private function _tmpEnforceInlineTotal() : void {
		$files = $this->_tmpFiles();

		$total = 0;
		foreach($files as $entry)
			$total += array_key_exists('content', $entry) ? strlen($entry['content']) : 0;

		if($total <= self::TMP_INLINE_TOTAL_BYTES)
			return;

		foreach($files as $name => $entry) {
			if($total <= self::TMP_INLINE_TOTAL_BYTES)
				break;

			if(!array_key_exists('content', $entry))
				continue;

			if(!($token = self::_resourcePut($name, $entry['content'])))
				continue;

			$total -= strlen($entry['content']);

			unset($entry['content']);
			$entry['resource'] = $token;
			$files[$name] = $entry;
		}

		$this->_tmp['files'] = $files;
		$this->_tmp_dirty = true;
	}

	private function _tmpGet(string $name) : ?string {
		$files = $this->_tmpFiles();

		if(!array_key_exists($name, $files))
			return null;

		$entry = $files[$name];

		if(array_key_exists('content', $entry))
			return strval($entry['content']);

		if(($token = $entry['resource'] ?? null) && ($resource = \DAO_AutomationResource::getByToken($token)))
			return strval($resource->getFileContents());

		// The resource TTL lapsed out from under us; say so rather than returning an empty file.
		return null;
	}

	private function _tmpDelete(string $name) : bool {
		$files = $this->_tmpFiles();

		if(!array_key_exists($name, $files))
			return false;

		unset($files[$name]);

		$this->_tmp['files'] = $files;
		$this->_tmp_dirty = true;

		return true;
	}

	/** Park a blob out-of-band. Mirrors what `http.request` does with a big response body. */
	private static function _resourcePut(string $name, string $content) : ?string {
		try {
			$token = \DevblocksPlatform::services()->string()->uuid();

			$resource_id = \DAO_AutomationResource::create([
				\DAO_AutomationResource::NAME => $name,
				\DAO_AutomationResource::MIME_TYPE => 'text/plain',
				\DAO_AutomationResource::TOKEN => $token,
				\DAO_AutomationResource::EXPIRES_AT => time() + self::TMP_RESOURCE_TTL,
			]);

			if(!$resource_id)
				return null;

			\Storage_AutomationResource::put($resource_id, $content);

			return $token;

		} catch (\Throwable) {
			// Keep it inline rather than losing the buffer.
			return null;
		}
	}

	// ---------------------------------------------------------------------
	// Pipelines
	// ---------------------------------------------------------------------

	/**
	 * Split a command line at the first UNQUOTED `|`. The tail is returned VERBATIM -- never tokenized -- so
	 * quotes, backslashes, and Twig's own `|` separators reach the template engine exactly as written.
	 *
	 * @return array [command, pipeline|null]
	 */
	private static function _splitPipeline(string $line) : array {
		$in_single = false;
		$in_double = false;

		for($i = 0; $i < strlen($line); $i++) {
			$char = $line[$i];

			if("'" === $char && !$in_double) {
				$in_single = !$in_single;
			} else if('"' === $char && !$in_single) {
				$in_double = !$in_double;
			} else if('|' === $char && !$in_single && !$in_double) {
				return [substr($line, 0, $i), trim(substr($line, $i + 1))];
			}
		}

		return [$line, null];
	}

	/**
	 * Turn a `| chain` into a template.
	 *
	 * Two conveniences, because this is a command line and not a template editor:
	 *
	 * 1. The subject is `output` unless the chain NAMES one of the bindings first -- `| lines|length` has to
	 *    mean `{{lines|length}}`, not `{{output|lines|length}}` (which reads `lines` as a filter that doesn't
	 *    exist). Keeps the common `| lines|filter(...)` form working with no second syntax to learn.
	 * 2. Whatever the chain ENDS on is rendered for a terminal by renderPipedValue() -- a list of lines joins
	 *    with newlines, a list of records prints as JSON. Bare `{{ }}` would print the literal string "Array",
	 *    which is never what you wanted.
	 *
	 * The running command's own `data_alias` joins the recognized names, so a command that invents an alias
	 * gets `| <alias>|...` for free. Hardcoding the whole list here instead would mean every new alias --
	 * every `cerb` sub-command's rows -- had to be registered in the evaluator, and until it was, naming it
	 * would fail as an unknown FILTER rather than an unknown binding.
	 */
	private static function _pipelineTemplate(string $pipeline, ?string $data_alias = null) : string {
		$names = ['output', 'lines', 'file', 'data', 'results', 'files'];

		// Only a bare identifier can be a binding; anything else can't be one and must not reach the pattern.
		if($data_alias && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $data_alias))
			$names[] = $data_alias;

		$expression = preg_match(sprintf('/^\s*(%s)\b/', implode('|', array_unique($names))), $pipeline)
			? $pipeline
			: sprintf('output|%s', $pipeline);

		return sprintf('{%% set _piped = %s %%}{{_piped|%s}}', $expression, self::RENDER_FILTER);
	}

	/**
	 * Render a pipeline's result as terminal text.
	 *
	 * A chain can end on anything -- a string, a list of lines, a list of records -- and the caller is a
	 * terminal, so: scalars speak for themselves, a flat list is lines, and anything structured is JSON (the
	 * shape an agent can hand straight back to a `script`, and a human can still read).
	 */
	public static function renderPipedValue($value) : string {
		if(is_null($value))
			return '';

		if(is_bool($value))
			return $value ? 'true' : 'false';

		if(is_scalar($value))
			return strval($value);

		if($value instanceof \Twig\Markup)
			return strval($value);

		if($value instanceof \Traversable)
			$value = iterator_to_array($value);

		if(!is_array($value))
			return strval($value);

		// A flat list of scalars is the common case: lines of text.
		$is_lines = \DevblocksPlatform::arrayIsIndexed($value);

		if($is_lines) {
			foreach($value as $item) {
				if(!is_scalar($item) && !is_null($item)) {
					$is_lines = false;
					break;
				}
			}
		}

		if($is_lines)
			return implode("\n", array_map(fn($item) => is_bool($item) ? ($item ? 'true' : 'false') : strval($item), $value));

		return strval(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Evaluate a Twig template over a command's output.
	 *
	 * Bindings are deliberately tiny -- `output` (the text), `lines` (pre-split, the usual first move), `file`
	 * (metadata when the output came from one), and, when the command emitted one, its structured payload as
	 * both `data` and a per-command alias (`results` for search, `files` for ls). No automation dict is in
	 * scope, so no record or secret is reachable even by name.
	 */
	private function _applyScript(string $template, string $output, ?array $file, ?array $data = null, ?string $data_alias = null, ?string &$error = null) : ?string {
		try {
			$builder = \_DevblocksTemplateBuilder::newInstance();

			// A FRESH builder, so narrowing its sandbox can't leak into the shared singleton.
			$sandbox = $builder->getEngine()->getExtension(\Twig\Extension\SandboxExtension::class);
			$sandbox->setSecurityPolicy(self::_scriptPolicy($sandbox->getSecurityPolicy()));

			// How a chain's RESULT becomes terminal text. Injected by _pipelineTemplate(), never typed.
			$builder->addFilter(new \Twig\TwigFilter(self::RENDER_FILTER, [self::class, 'renderPipedValue']));

			$dict = [
				'output' => $output,
				'lines' => ('' === $output) ? [] : preg_split("/\r?\n/", $output),
				'file' => $file,
			];

			if(!is_null($data)) {
				$dict['data'] = $data;

				if($data_alias)
					$dict[$data_alias] = $data;
			}

			$result = $builder->build($template, $dict);

			if(false === $result) {
				$error = $builder->getLastError();
				return null;
			}

			return strval($result);

		} catch (\Throwable $e) {
			$error = $e->getMessage();
			return null;
		}
	}

	/**
	 * A stricter policy for model-authored templates, DERIVED from the standard one (start from what Cerb
	 * already allows, subtract) so new filters arrive for free and we never drift into re-allowing something
	 * the platform has since removed.
	 *
	 * Dropped: every `cerb_*` (`cerb_automation()` would run automations the caller was never granted), every
	 * `dns_*` (network egress), and `range()` (a one-liner unbounded loop). Filters are all in-memory text and
	 * array work, so they carry over intact.
	 */
	private static function _scriptPolicy(\Twig\Sandbox\SecurityPolicyInterface $standard) : \_DevblocksTwigSecurityPolicy {
		if(!($standard instanceof \_DevblocksTwigSecurityPolicy))
			return new \_DevblocksTwigSecurityPolicy();

		$functions = array_values(array_filter(
			$standard->getAllowedFunctions(),
			fn($name) =>
				!str_starts_with($name, 'cerb_')
				&& !str_starts_with($name, 'dns_')
				&& 'range' != $name
		));

		// Plus our own render filter, which _pipelineTemplate() injects.
		$filters = array_merge($standard->getAllowedFilters(), [self::RENDER_FILTER]);

		return new \_DevblocksTwigSecurityPolicy(
			$standard->getAllowedTags(),
			$filters,
			$standard->getAllowedMethods(),
			$standard->getAllowedProperties(),
			$functions
		);
	}

	// ---------------------------------------------------------------------
	// Parsing + helpers
	// ---------------------------------------------------------------------

	/**
	 * Long flags that take a VALUE. Everything else is a switch.
	 *
	 * Declaring it per flag is what getopt does -- whether an option consumes the next word is a property of
	 * the option, never a guess about what follows it. Guessing is how `find --long /tmp` ends up listing the
	 * working directory, having read the path as the flag's value. `--flag=value` always works regardless.
	 */
	private const VALUE_FLAGS = ['path', 'ext', 'top', 'limit', 'offset', 'fields', 'name', 'type', 'depth', 'format', 'filter', 'schema'];

	/**
	 * Tokenize + split a command line into verb/args/flags. Quoting is honored; nothing is expanded.
	 *
	 * Short flags are always switches (`-l`, and `-lr` is two of them) -- there's no `-n 5` form here.
	 */
	private static function _parse(string $line) : array {
		$tokens = [];

		if(preg_match_all('/"([^"]*)"|\'([^\']*)\'|(\S+)/', trim($line), $m, PREG_SET_ORDER)) {
			foreach($m as $set) {
				if(isset($set[3]) && '' !== $set[3])
					$tokens[] = $set[3];
				else
					$tokens[] = $set[2] ?? $set[1] ?? '';
			}
		}

		$verb = \DevblocksPlatform::strLower(array_shift($tokens) ?? '');
		$args = [];
		$flags = [];

		for($i = 0; $i < count($tokens); $i++) {
			$token = $tokens[$i];

			if(str_starts_with($token, '--')) {
				$token = substr($token, 2);

				if(false !== ($eq = strpos($token, '='))) {
					$flags[substr($token, 0, $eq)] = substr($token, $eq + 1);
				} else if(!in_array($token, self::VALUE_FLAGS)) {
					// A switch never takes a value, so the token after it stays positional.
					$flags[$token] = true;
				} else if(isset($tokens[$i+1]) && !str_starts_with($tokens[$i+1], '-')) {
					$flags[$token] = $tokens[++$i];
				} else {
					$flags[$token] = true;
				}

			} else if(str_starts_with($token, '-') && strlen($token) > 1) {
				foreach(str_split(substr($token, 1)) as $f)
					$flags[$f] = true;

			} else {
				$args[] = $token;
			}
		}

		return ['verb' => $verb, 'args' => $args, 'flags' => $flags];
	}

	/**
	 * Resolve a user-supplied path to an absolute VFS path.
	 *
	 * `@filesystem-name/rest` addresses a mount by its FILESYSTEM name and rewrites to that mount's actual
	 * mountpoint -- so `@cerb-docs/x.md` works even when cerb-docs is mounted at /docs. Unknown names fall
	 * through as a literal path (and simply won't resolve to a mount).
	 */
	private function _resolvePath(string $path, string $cwd = '/') : string {
		$path = trim($path);

		if(str_starts_with($path, '@')) {
			$rest = substr($path, 1);
			$slash = strpos($rest, '/');
			$name = (false === $slash) ? $rest : substr($rest, 0, $slash);
			$tail = (false === $slash) ? '' : substr($rest, $slash);

			foreach($this->_mounts as $mount) {
				if($this->_isTmp($mount))
					continue;

				if(0 == strcasecmp($mount['fs']->name, $name)) {
					$path = rtrim($mount['at'], '/') . $tail;
					break;
				}
			}
		}

		return self::_normalizePath($path, $cwd);
	}

	/** Normalize a VFS path: relative segments, `.`/`..`, and collapse slashes. */
	private static function _normalizePath(string $path, string $cwd = '/') : string {
		$path = trim($path);

		if('' === $path)
			$path = $cwd;

		// A bare `@name` that reached here (no matching mount) is treated as a literal path segment
		if(str_starts_with($path, '@'))
			$path = '/' . substr($path, 1);

		if(!str_starts_with($path, '/'))
			$path = rtrim($cwd, '/') . '/' . $path;

		$out = [];

		foreach(explode('/', $path) as $seg) {
			if('' === $seg || '.' === $seg)
				continue;

			if('..' === $seg) {
				array_pop($out);
				continue;
			}

			$out[] = $seg;
		}

		return '/' . implode('/', $out);
	}

	private function _result(string $output, string $cwd) : array {
		return ['output' => $output, 'cwd' => $cwd, 'error' => false];
	}

	private function _error(string $output, string $cwd) : array {
		return ['output' => $output, 'cwd' => $cwd, 'error' => true];
	}

	/**
	 * The command vocabulary. Public because the `agent_terminal` tool description reuses it verbatim -- one
	 * source of truth, so the terminal's `help` and what an agent is told can't drift.
	 *
	 * @param string|null $verb usage for one command instead of the whole list
	 * @param array|null $only restrict the list to these verbs (the agent tool has no cwd and no write access)
	 */
	public static function help(?string $verb = null, ?array $only = null) : string {
		$help = self::_helpEntries();

		if($verb && array_key_exists($verb, $help))
			return $help[$verb];

		if($only)
			$help = array_intersect_key($help, array_flip($only));

		$out = ["This is a virtual filesystem, not a shell. Available commands:", ''];

		foreach($help as $name => $text)
			$out[] = '  ' . str_replace("\n  ", "\n      ", $text);

		return implode("\n", $out);
	}

	/** Every documented verb, in help order. */
	public static function helpVerbs() : array {
		return array_keys(self::_helpEntries());
	}

	/**
	 * Instance-aware help: hides verbs this instance can't actually run, so `help` never documents a command
	 * whose only possible reply is an error. The agent tool does the same thing from the other side by passing
	 * an explicit `$only` (llm.php's `$verbs`).
	 */
	private function _help(?string $verb = null) : string {
		if($this->hasCli())
			return self::help($verb);

		if(self::KIND_CLI === $verb)
			$verb = null;

		return self::help($verb, array_values(array_diff(self::helpVerbs(), [self::KIND_CLI])));
	}

	private static function _helpEntries() : array {
		return [
			'ls' => "ls [path] [-l] [--fields title,description]\n  List ONE directory, names only. At the root this lists the mounted filesystems, each tagged with its access\n  mode -- `[ro]` is read-only, `[rw]` accepts write/append/edit/rm. A glob in the last segment filters the\n  listing (`ls *.md`, `ls c*.md`, `ls /docs/*.md`); matching ACROSS directories is `find`'s job.\n  A path naming a FILE lists just that file -- `ls -l <file>` checks its size and modified time without\n  spending a `read` on the body.\n  -l (--long) adds that same mode tag to every row, plus the exact size in BYTES and the modified time\n  (`Mar 03 09:41` within the past year, `Mar 03  2024` beyond it). Directories show `-` for both: they're\n  virtual here, implied by the paths of the files under them.\n  --fields prints values from each file's own metadata (markdown frontmatter).",
			'find' => "find [path] [pattern] [--name <glob>] [--type f|d] [--ext md] [--depth N] [-l] [--fields ...] [--limit N]\n  Find entries by NAME, recursively -- the counterpart to `search`, which matches CONTENT. A bare pattern\n  matches the basename at any depth (`find *.md`); a pattern with a `/` matches the relative path\n  (`find \"guides/*.md\"`). `*` stops at a `/`, `**` crosses them. --name is the same thing spelled as a flag.\n  --type d lists only directories (a recursive tree of them), --type f only files; the default is both.\n  --depth N counts levels BELOW the scope: 0 is this directory (so `find --depth 0` is `ls`), 1 adds one\n  level down; omit it for no limit. --ext md is shorthand for --name *.md.\n  Paths print relative to the scope, which is named once in the header. Listing never opens a file, so\n  metadata is free: --fields prints it, -l adds the `[ro]`/`[rw]` mode + byte sizes + modified times, and\n  piping gives you `files` records carrying the ABSOLUTE path plus name, filesystem, size, updated_at (epoch\n  seconds), ext, is_dir, mode, can_write, and meta.",
			'cd' => "cd [path]\n  Change the working directory. Supports `..`, absolute `/paths`, and `@filesystem/path`.",
			'search' => "search <query...> [--terms] [--lines] [--path <path>] [--ext md] [--top K] [--limit N]\n  Search file contents. Prints one line per matching file -- hit count + path -- most hits first, so you can\n  pick what to read. --lines adds the numbered matching lines per file (pages of text; ask for it when you\n  need the context, not to choose a file).\n  Every word is part of the query (`search filled disc`), and ALL of them must appear in the SAME file -- so\n  one word that matches nothing empties the result. Scope with --path (default: the working directory).\n  --top K keeps only the K most relevant files (default 25; --top 0 = all matches). --limit N caps printed lines.\n  --terms switches from AND to OR: it counts how many files contain EACH word, ranked, instead of searching.\n  Throw a pile of synonyms at it in one call, keep the winner, then require it with + and try the next set:\n  `search --terms school college academic university` then `search --terms +college tuition fees pricing`.\n  A `-` count means the word is on the ignore list; it was never searched for. A wildcard or stem term\n  (`auto*`, `automate~`) also lists the concrete words it covers, inline underneath -- that is how you learn\n  the vocabulary this corpus actually uses.\n  Piping gives you `results` -- one record per file: path, name, filesystem, size, lines, hits, meta (the\n  file's own metadata, e.g. markdown frontmatter -- always a map, empty when it has none), matches, content.\n  With --terms you get `terms` instead: term, token, docs, alone, status.",
			'read' => "read <path> [--offset N] [--limit N]\n  Print a file's contents. --offset/--limit page by line. This is the only command that returns a whole body.",
			'write' => "write <path>\n  Replace a file's contents (read-write mounts only). Content is supplied out-of-band.",
			'append' => "append <path>\n  Append to a file (read-write mounts only). Content is supplied out-of-band.",
			'edit' => "edit <path>\n  Replace an EXACT snippet in a file (read-write mounts only). The find text must match exactly ONE place;\n  if it matches none or several you get an error -- add surrounding lines until it's unique. Find and replace\n  are supplied out-of-band. Use `write` for a full-file replacement, `append` to add to the end.",
			'copy' => "copy <source> <destination> [-f]\n  Copy ONE file. The DESTINATION must be on a read-write mount; the source can be anywhere mounted, so\n  this is how you lift a file out of a read-only volume. A destination ending in `/`, or naming a directory\n  that already exists, keeps the source's filename.\n  Refuses an existing destination unless you pass -f. No patterns -- one file per command.\n  There is no `move`: copy, then `rm` the source.",
			'rm' => "rm <path>\n  Delete a file (read-write mounts only).",
			'pwd' => "pwd\n  Print the working directory.",
			'help' => "help [command]\n  Show this list, or usage for one command.",
			// Deliberately says nothing about WHICH commands exist: this text is embedded in the cached prompt
			// prefix, so naming sub-commands here would invalidate it for every agent each time one is added.
			// `cerb help` answers that at runtime instead.
			'cerb' => "cerb <command> [args] [--flags]\n  The Cerb command line: ask about THIS Cerb installation rather than guessing at it. What exists here and\n  what it's called is not in your training data. Run `cerb help` for the commands you have, and\n  `cerb <command> help` for one command's usage. Output pipes like any other command.",
			'|' => "<command> | <twig filter chain>\n  Pipe a command's output through Cerb scripting filters, in lieu of Unix pipes. Always available: `output`\n  (the text), `lines` (it pre-split), `file` ({path,size,lines}). Commands that return records add them as\n  `data`, plus a name of their own -- `results` (search), `files` (ls) -- e.g.\n    read /tmp/hits.txt | lines|filter(l => \"ERROR\" in l)\n    read notes.md | lines|length\n    search widgets | results|map(r => r.meta.title ?? r.path)\n    search widgets | results|filter(r => r.hits > 3)|column(\"path\")\n    ls /docs | files|filter(f => not f.is_dir)|column(\"name\")\n  A chain starting with a binding name uses it as the subject; otherwise the subject is `output`. Lines come\n  back as text, records as JSON. The pipeline runs on the FULL output, before any truncation. Quote a literal\n  `|` to protect it from the split.",
			'/tmp' => "/tmp\n  A scratch area. Output too large to return is saved here whole and referenced by path, so you can page it\n  with `read --offset/--limit` or narrow it with a `|` pipeline. `ls -l /tmp` shows sizes + line counts; write\n  and rm work there too. It isn't indexed, so `search` doesn't reach it -- filter with a pipeline instead.",
		];
	}
}
