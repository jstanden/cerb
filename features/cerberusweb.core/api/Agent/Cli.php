<?php
namespace Cerb\Agent;

use Cerb\Agent\Cli\Command;
use Cerb\Agent\Cli\Platform;
use Cerb\Agent\Cli\Records;

/**
 * The `cerb` command line inside the agent terminal -- a namespace of sub-commands (`cerb records ...`)
 * reached through the single `cerb` verb.
 *
 * WHY A NAMESPACE INSTEAD OF MORE VERBS: Filesystem::help() is embedded verbatim in the agent_terminal tool
 * description, which is part of the provider's cached prompt prefix and must be byte-identical across turns.
 * Every new verb therefore invalidates that prefix for every agent. Behind `cerb`, only the one verb is
 * advertised; sub-command usage arrives at runtime through `cerb help`, in a tool RESULT, where content is
 * free to change. The CLI can grow indefinitely without ever touching the prefix again.
 *
 * Config comes from the host as `$options['cerb']` on Filesystem -- a map of enabled namespace => its config
 * (`['records' => []]`). An absent namespace is not reachable.
 */
class Cli {
	/**
	 * The registry. One entry per namespace; order is the order `cerb help` lists them.
	 *
	 * @return Command[] keyed by name
	 */
	private static function _commands() : array {
		static $commands = null;

		if(is_null($commands)) {
			$commands = [];

			foreach([new Records(), new Platform(), new Code()] as $command)
				$commands[$command->getName()] = $command;
		}

		return $commands;
	}

	/** Is any namespace enabled? Drives whether `cerb` is advertised at all. */
	public static function isEnabled(?array $config) : bool {
		return is_array($config) && (bool) self::_enabled($config);
	}

	/**
	 * Every REGISTERED namespace and its one-line summary, regardless of config -- for hosts that let a human
	 * choose what to enable (the Setup terminal). Not for deciding what an agent may run: that's exec()'s
	 * chokepoint, which reads the config.
	 *
	 * @return array [name => summary]
	 */
	public static function getNamespaces() : array {
		return array_map(fn(Command $command) => $command->getSummary(), self::_commands());
	}

	/** @return Command[] the registered commands this config actually enables, keyed by name */
	private static function _enabled(array $config) : array {
		return array_intersect_key(self::_commands(), $config);
	}

	/**
	 * The scopes this config grants.
	 *
	 * v1 derives them: enabling a namespace grants its read scope. This is the seam for the real permission
	 * layer -- when scopes become roles or OAuth-style grants assigned to an agent, ONLY this method changes.
	 * Every call site already asks the chokepoint in exec(), not the config.
	 */
	private static function _grantedScopes(array $config) : array {
		$granted = [];

		foreach(self::_enabled($config) as $name => $command)
			$granted[$name . ':read'] = true;

		return $granted;
	}

	/**
	 * Run `cerb <namespace> <args...>`.
	 *
	 * $context is the host's offer of what a command can reach beyond its arguments (cwd, an out-of-band
	 * payload, a file reader). See Command::exec(). It is passed through untouched -- this class governs
	 * WHETHER a command runs, not what it is handed.
	 *
	 * @return array ['output'=>string, 'error'=>bool, 'data'=>?array, 'data_alias'=>?string]
	 */
	public static function exec(array $args, array $flags, ?array $config, array $context = []) : array {
		$config = is_array($config) ? $config : [];
		$enabled = self::_enabled($config);

		$name = \DevblocksPlatform::strLower(strval(array_shift($args) ?? ''));

		if('' === $name || 'help' === $name)
			return self::_ok(self::help($config));

		if(!array_key_exists($name, $enabled)) {
			// Deliberately identical whether the namespace is unknown or merely not enabled -- a distinct
			// "not enabled" reply would enumerate what exists elsewhere. The index rides along so a wrong
			// guess is corrected in ONE tool call rather than two.
			return self::_fail(sprintf("cerb: %s: unknown command.\n\n%s", $name, self::help($config)));
		}

		$command = $enabled[$name];

		if(($args[0] ?? '') === 'help')
			return self::_ok($command->getHelp());

		// THE CHOKEPOINT. Default-deny, and it lives here rather than in help because the dispatch below is
		// what actually runs: Filesystem's verb `match` executes whatever the model types, so filtering a
		// command out of the advertised list hides it without disabling it.
		$scope = $command->getRequiredScope($args);
		$granted = self::_grantedScopes($config);

		if(!array_key_exists($scope, $granted))
			return self::_fail(sprintf("cerb %s: permission denied (requires `%s`).", $name, $scope));

		$result = $command->exec($args, $flags, $context);

		return [
			'output' => strval($result['output'] ?? ''),
			'error' => (bool) ($result['error'] ?? false),
			'data' => $result['data'] ?? null,
			'data_alias' => $result['data_alias'] ?? null,
		];
	}

	/** The `cerb help` index: which namespaces this agent has, one line each. */
	public static function help(?array $config) : string {
		$enabled = self::_enabled(is_array($config) ? $config : []);

		if(!$enabled)
			return "cerb: no commands are enabled.";

		$lines = [
			"The Cerb command line. Commands describe and query THIS Cerb installation.",
			'',
			"Usage: cerb <command> [args] [--flags]",
			"       cerb <command> help    usage for one command",
			'',
			"Commands:",
		];

		foreach($enabled as $name => $command)
			$lines[] = sprintf("  %-10s %s", $name, $command->getSummary());

		$lines[] = '';
		$lines[] = "Output pipes like any other command: `cerb records types | data|column(\"alias\")`.";

		return implode("\n", $lines);
	}

	private static function _ok(string $output) : array {
		return ['output' => $output, 'error' => false, 'data' => null, 'data_alias' => null];
	}

	private static function _fail(string $output) : array {
		return ['output' => $output, 'error' => true, 'data' => null, 'data_alias' => null];
	}
}
