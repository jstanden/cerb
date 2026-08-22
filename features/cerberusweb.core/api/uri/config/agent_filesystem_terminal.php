<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

/**
 * A human driver for the agent virtual filesystem (`Cerb\Agent\Filesystem`).
 *
 * You mount `agent_filesystem` records (each at its own name, ro or rw) and run the same fixed command set an
 * agent gets -- so the VFS command layer can be built and exercised before any agent is wired to it. The page
 * holds the mount set + cwd client-side; the server is stateless per command.
 */
class PageSection_SetupDevelopersAgentFilesystemTerminal extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$visit->set(ChConfigurationPage::ID, 'agent_filesystem_terminal');

		$tpl->assign('context_agent_filesystem', Context_AgentFilesystem::ID);

		// The `cerb` CLI's namespaces are a capability rather than a mount, so the page offers them separately
		// and the client names the ones it wants -- same shape an `llm.agent` author writes in `cerb:`.
		$tpl->assign('cerb_namespaces', Cerb\Agent\Cli::getNamespaces());

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/agent_filesystem_terminal/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch($action) {
				case 'execJson':
					return $this->_configAction_execJson();
			}
		}

		return false;
	}

	private function _configAction_execJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		if(!$active_worker || !$active_worker->is_superuser) {
			echo json_encode(['status' => false, 'error' => 'Access denied.']);
			return;
		}

		$mounts_json = DevblocksPlatform::importGPC($_POST['mounts'] ?? null, 'string', '[]');
		$cwd = DevblocksPlatform::importGPC($_POST['cwd'] ?? null, 'string', '/');
		$command = DevblocksPlatform::importGPC($_POST['command'] ?? null, 'string', '');
		$payload = DevblocksPlatform::importGPC($_POST['payload'] ?? null, 'string', '');
		$find = DevblocksPlatform::importGPC($_POST['find'] ?? null, 'string', '');
		$tmp_json = DevblocksPlatform::importGPC($_POST['tmp'] ?? null, 'string', '{}');
		$cerb_json = DevblocksPlatform::importGPC($_POST['cerb'] ?? null, 'string', '[]');

		// The client owns the scratch store the same way it owns mounts + cwd -- the server stays stateless
		// between commands. (An automation host keeps this on its dict instead, where it rides the continuation.)
		$tmp = json_decode($tmp_json, true);

		if(!is_array($tmp))
			$tmp = [];

		// Mount specs are resolved server-side; the client only names filesystems + modes.
		// No mountpoints yet -- each filesystem mounts at its own name (chroot/symlink comes later).
		$specs = [];
		$mounts = json_decode($mounts_json, true);

		if(is_array($mounts)) {
			foreach($mounts as $mount) {
				if(!is_array($mount) || !($filesystem_id = intval($mount['filesystem_id'] ?? 0)))
					continue;

				$specs[] = [
					'filesystem' => $filesystem_id,
					'mode' => ('rw' == ($mount['mode'] ?? 'ro')) ? 'rw' : 'ro',
				];
			}
		}

		// Which `cerb` CLI namespaces this session has. The client sends names; anything it doesn't name is
		// unreachable, and naming nothing removes the `cerb` verb entirely rather than leaving an empty one.
		$cerb = [];
		$cerb_names = json_decode($cerb_json, true);

		if(is_array($cerb_names)) {
			$registered = Cerb\Agent\Cli::getNamespaces();

			foreach($cerb_names as $name) {
				if(is_string($name) && array_key_exists($name, $registered))
					$cerb[$name] = [];
			}
		}

		try {
			$vfs = Cerb\Agent\Filesystem::fromSpecs($specs, ['tmp' => $tmp, 'cerb' => $cerb]);

			// The Payload box is the out-of-band channel for THREE jobs, whichever the verb implies: file content
			// for `write`/`append`, the REPLACEMENT text for `edit` (its search needle rides the Find box), and a
			// multi-line Twig template for anything else (so a script never has to be escaped onto the command
			// line).
			//
			// `cerb` sits on both sides of that: `cerb records types` wants the box as a script, while
			// `cerb code kata lint --payload` wants it as the document being checked. The flag is the only
			// unambiguous signal, so it's what decides -- which is also what it says on the box.
			$verb = DevblocksPlatform::strLower(strtok(trim($command), " \t"));
			$is_write = in_array($verb, ['write', 'append']);
			$is_edit = 'edit' == $verb;
			$is_cerb_payload = 'cerb' == $verb && preg_match('/(^|\s)--payload(\s|$)/', $command);
			$is_payload = $is_write || $is_edit || $is_cerb_payload;

			$result = $vfs->exec(
				$command,
				$cwd,
				($is_payload && '' !== $payload) ? $payload : null,
				(!$is_payload && '' !== $payload) ? $payload : null,
				($is_edit && '' !== $find) ? $find : null
			);

			echo json_encode([
				'status' => true,
				'output' => $result['output'],
				'cwd' => $result['cwd'],
				'error' => $result['error'],
				'tmp' => $vfs->getTmp(),
			]);

		} catch (Throwable $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
		}
	}
};
