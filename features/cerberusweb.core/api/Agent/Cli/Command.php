<?php
namespace Cerb\Agent\Cli;

/**
 * One top-level `cerb <namespace>` command.
 *
 * Implementations are registered in Cerb\Agent\Cli. Adding one costs a registry entry and nothing else --
 * in particular it does NOT change the agent_terminal tool description, because only the bare `cerb` verb
 * is advertised there and sub-command usage is fetched at runtime via `cerb help`. That is the whole reason
 * this indirection exists: the tool description is part of the provider's cached prompt prefix, so a new
 * verb would invalidate it for every agent, while a new namespace costs nothing.
 */
interface Command {
	/** The namespace as typed: `cerb <name> ...`. */
	public function getName() : string;

	/** One line for `cerb help`. No trailing period needed. */
	public function getSummary() : string;

	/** Full usage for `cerb <name> help`. */
	public function getHelp() : string;

	/**
	 * The permission scope this invocation needs, e.g. `records:read`. Takes the args so a namespace can
	 * charge different scopes for different sub-commands (a future `records set` wants `records:write`);
	 * v1 commands can ignore them and return one constant.
	 */
	public function getRequiredScope(array $args) : string;

	/**
	 * Run it. $args excludes the namespace itself, so `cerb records types` arrives as ['types'].
	 *
	 * $context is what the HOST can offer beyond the command line, and every key is optional -- a command
	 * that needs one must degrade with a clear message rather than assume it:
	 *   'cwd'     => string, the working directory the command was typed in
	 *   'payload' => ?string, out-of-band content (the agent's `content` parameter; the Setup terminal's
	 *                Payload box)
	 *   'read'    => callable(string $path, ?string &$error, ?string &$resolved) : ?string, reading a file
	 *                by exactly the rules `read` uses -- same cwd resolution, same mounts, same /tmp
	 *
	 * Returns the Filesystem result shape minus `cwd`:
	 *   ['output' => string, 'error' => bool, 'data' => ?array, 'data_alias' => ?string]
	 *
	 * `data`/`data_alias` are what make output pipeable as ROWS rather than as text -- always populate them
	 * for a listing, or the only thing a `|` chain can do is grep the rendered table.
	 *
	 * `error` means the command could not RUN. It is not a verdict: a linter that ran and found the document
	 * invalid succeeded, and says so in its output -- flagging it as an error would stop the pipeline and
	 * make the issue rows unreachable.
	 */
	public function exec(array $args, array $flags, array $context = []) : array;
}
