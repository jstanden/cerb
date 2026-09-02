<?php
class DAO_AutomationContinuation extends Cerb_ORMHelper {
	const EXPIRES_AT = 'expires_at';
	const EXTENSION_ID = 'extension_id';
	const PARENT_TOKEN = 'parent_token';
	const RESUME_LABEL = 'resume_label';
	const RESUME_METADATA = 'resume_metadata';
	const RESUME_SCOPE = 'resume_scope';
	const ROOT_TOKEN = 'root_token';
	const STATE = 'state';
	const STATE_AWAIT = 'state_await';
	const STATE_DATA = 'state_data';
	const TOKEN = 'token';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	const WORKER_ID = 'worker_id';

	// How many recent user turns to look through for a preview before giving up. Tool results wear `role=user`
	// too, and on some providers they slip past the `kind` filter, so the newest candidate isn't always prose.
	const RESUME_PREVIEW_SCAN_DEPTH = 10;
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::EXPIRES_AT)
			->timestamp()
			;
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(255)
			;
		$validation
			->addField(self::STATE_AWAIT)
			->string()
			->setMaxLength(32)
			;
		$validation
			->addField(self::PARENT_TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::RESUME_LABEL)
			->string()
			->setMaxLength(255)
			;
		$validation
			->addField(self::RESUME_METADATA)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::RESUME_SCOPE)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::ROOT_TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::STATE)
			->string()
			->setPossibleValues([
				'',
				'await',
				'error',
				'exit',
				'return',
			])
			;
		$validation
			->addField(self::STATE_DATA)
			->string()
			->setMaxLength(16777216)
			;
		$validation
			->addField(self::TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::URI)
			->string()
			->addValidator($validation->validators()->uri())
			;
		$validation
			->addField(self::WORKER_ID)
			->id()
			;

		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$token = DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(48));
		
		$sql = sprintf("INSERT INTO automation_continuation (token) VALUES (%s)",
			$db->qstr($token)
		);
		$db->ExecuteMaster($sql);
		
		self::update($token, $fields);
		
		return $token;
	}
	
	static function upsert($fields, $token=null) {
		$db = DevblocksPlatform::services()->database();
		
		if(is_null($token))
			$token = DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(48));
		
		$sql = sprintf("REPLACE INTO automation_continuation (token) VALUES (%s)",
			$db->qstr($token)
		);
		$db->ExecuteMaster($sql);
		
		self::update($token, $fields);
		
		return $token;
	}
	
	static function update($ids, $fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		self::updateWhere($fields, sprintf("token IN (%s)",
			implode(',', $db->qstrArray($ids))
		));
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('automation_continuation', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_AutomationContinuation[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT token, uri, state, state_data, parent_token, root_token, expires_at, updated_at, extension_id, worker_id, state_await, resume_scope, resume_label, resume_metadata ".
			"FROM automation_continuation ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param string $token
	 * @return Model_AutomationContinuation
	 */
	static function getByToken(string $token) {
		if(empty($token))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %s",
			self::TOKEN,
			Cerb_ORMHelper::qstr($token)
		));
		
		if(array_key_exists($token, $objects))
			return $objects[$token];
		
		return null;
	}
	
	/**
	 * @param string $token
	 * @return Model_AutomationContinuation[]
	 */
	static function getByRootToken(string $token) : iterable {
		if(!$token)
			return [];
		
		return self::getWhere(sprintf("%s = %s OR %s = %s",
			self::TOKEN,
			Cerb_ORMHelper::qstr($token),
			self::ROOT_TOKEN,
			Cerb_ORMHelper::qstr($token)
		));
	}
	
	// The await SUB-STATE for a parked continuation, derived from its `__return`: which kind of await it's
	// parked on. Pure record metadata — computed only when writing the continuation, never fed back into
	// automation state. It answers READINESS ("is it parked somewhere a UI can re-enter"); WHETHER and WHERE it
	// may be reopened is `resume_scope`, set by the launcher.
	static function stateAwaitFor(array $return) : string {
		foreach(self::getAwaitTypes() as $type) {
			if(array_key_exists($type, $return))
				return $type;
		}

		return '';
	}

	static function getAwaitTypes() : array {
		return ['form', 'interaction', 'duration', 'draft', 'record', 'queue'];
	}

	// The await kinds a UI can drop back into. `form` is the obvious one; `queue` matters because a worker who
	// navigates away mid-LLM-turn would otherwise have NO way back — the turn finishes server-side, but the row
	// stayed invisible until it expired. Resuming one re-renders the poll marker, which restarts the two-loop.
	// The rest (interaction/duration/draft/record) are mid-flow with their own client handling.
	static function getResumableAwaitTypes() : array {
		return ['form', 'queue'];
	}

	// The interaction triggers that render in the worker popup and are therefore resumable. Website/portal
	// (anon, token-as-cookie) and the headless timer are deliberately excluded; explore renders full-page.
	// A sanity check on the trigger family — `worker_id` is the security gate, `resume_scope` the routing one.
	static function getWorkerResumableExtensionIds() : array {
		return [
			AutomationTrigger_InteractionWorker::ID,
			AutomationTrigger_InteractionWorkerAgent::ID,
			AutomationTrigger_InteractionInternal::ID,
		];
	}

	/**
	 * WHERE a parked interaction may be reopened — and, by being non-empty, whether it may be at all. Derived
	 * from the launcher's `caller` (recorded in `state_data` at start), NOT from the automation or the record:
	 * a chat scoped `agent.pane:automation` follows the worker from one automation editor to the next, which is
	 * the point. A launcher that wants per-record pinning appends its own segment (`agent.pane:icon:123`).
	 *
	 * `ui_capabilities` is deliberately excluded — it grows as a host gains commands, and including it would
	 * orphan every history row on each update.
	 */
	static function resumeScopeFor(?array $caller) : string {
		if(!is_array($caller) || !($name = trim(strval($caller['name'] ?? ''))))
			return '';

		// The global command bar's own launches read as a plain `commandbar` namespace rather than its toolbar id.
		if(Toolbar_GlobalMenu::ID === $name)
			return 'commandbar';

		if(\Cerb\Agent\Pane\Launchers::CALLER_NAME === $name) {
			$component = trim(strval($caller['params']['component'] ?? ''));
			return $component ? ('agent.pane:' . $component) : '';
		}

		return '';
	}

	// The scopes the GLOBAL command bar lists. A positive allowlist, not a `NOT LIKE`: sargable, and it fails
	// CLOSED, so a future launcher's chats never leak into a surface that can't drive them. (An editor-pane chat
	// opened from the command bar would render in a popup with no `command` bridge and its `uiCommand` awaits
	// would silently return empty.) `''` is excluded — unscoped means not resumable anywhere.
	static function getGlobalResumeScopes() : array {
		return ['commandbar'];
	}

	/**
	 * The `await:form: resume:` keys an automation may set. `label` is promoted to its own column (a single-column
	 * write keeps `/rename` safe beside a live turn); the rest ride in `resume_metadata` as display payload, so
	 * the shape can keep moving without a migration per key.
	 */
	static function getResumeMetadataKeys() : array {
		return ['preview', 'icon', 'color', 'agent_id'];
	}

	/**
	 * Title-normalize one resume value. Multibyte-aware `mb_substr` on purpose: `substr()` would cut mid-codepoint
	 * and hand MySQL invalid UTF-8, failing the whole write instead of shortening a string nobody reads in full.
	 * A clipped label is a non-event; a rejected one loses the name.
	 */
	static function normalizeResumeText(mixed $value) : string {
		if(!is_scalar($value))
			return '';

		// These are titles, not prose -- collapse any whitespace run (newlines included) to one space.
		$out = trim(preg_replace('/\s+/u', ' ', strval($value)));

		// Drop the quotes a model likes to wrap a generated title in, but only a MATCHED pair at both ends:
		// `trim($out, "\"'")` would eat the closing quote of `move the antenna "tip"` and leave it unbalanced.
		if(mb_strlen($out) > 1) {
			$quote = mb_substr($out, 0, 1);

			if(('"' === $quote || "'" === $quote) && $quote === mb_substr($out, -1))
				$out = trim(mb_substr($out, 1, -1));
		}

		return mb_substr($out, 0, 255);
	}

	/**
	 * Describe a parked conversation from the form it's parked on: what it should be called, previewed by, and
	 * marked with in the agent pane's History and the command bar.
	 *
	 * This is NOT an opt-in -- `resume_scope` already answers whether a conversation is resumable, and it fails
	 * closed for anything but the two conversational launchers (the command bar and an editor's agent pane). An
	 * editor-local chooser resolves to no scope and was never listed, so requiring an author to declare
	 * resumability a second time only meant every automation had to be edited before its rows read properly.
	 *
	 * An optional `await:form: resume:` block overrides any of it, for the rare case where the author knows a
	 * better value than the toolbar or the transcript.
	 *
	 * Values are STICKY: a key that resolves to nothing leaves the stored one alone, so an await that says
	 * nothing can't blank a name the conversation already has.
	 *
	 * @return array Partial `update()` fields; empty when nothing changed.
	 */
	static function resumeFieldsFromAwait(DevblocksDictionaryDelegate $results, Model_AutomationContinuation $continuation) : array {
		$form = $results->getKeyPath('__return.form', []);

		if(!is_array($form))
			return [];

		$resume = is_array($form['resume'] ?? null) ? $form['resume'] : [];

		// What the form can tell us about itself. The explicit keys below win over all of it.
		$derived = self::_resumeDerivedFromForm($form);

		$fields = [];

		if('' !== ($label = self::normalizeResumeText($resume['label'] ?? null)))
			$fields[self::RESUME_LABEL] = $label;

		$metadata = $continuation->resume_metadata;

		foreach(self::getResumeMetadataKeys() as $key) {
			$value = self::normalizeResumeText($resume[$key] ?? null);

			if('' === $value)
				$value = self::normalizeResumeText($derived[$key] ?? null);

			if('' !== $value)
				$metadata[$key] = $value;
		}

		if($metadata !== $continuation->resume_metadata)
			$fields[self::RESUME_METADATA] = json_encode($metadata);

		return $fields;
	}

	/**
	 * What a form can say about itself without the author spelling it out: the form's own `title:` as a preview,
	 * upgraded to the live transcript's provider mark and latest prompt when the form shows one.
	 *
	 * The session is read ONLY from an `llmTranscript` element. An `agentPrompt` also carries a `session_id`, but
	 * it carries one from the very first render -- before anything has been sent -- so trusting it would decorate
	 * a conversation that doesn't exist yet. A transcript element is rendered once there's something to show.
	 */
	private static function _resumeDerivedFromForm(array $form) : array {
		$elements = $form['elements'] ?? [];

		// Not a chat at all: the form's own title is the best short answer to "where is this parked" -- a
		// multi-step flow sitting on "Choose recipients" says something useful.
		//
		// "Is a chat" is decided by the COMPOSER (`agentPrompt`), not by the transcript, because the transcript
		// is typically rendered only once there's something to show. Asking the transcript would make a brand-new
		// chat look like a plain form and print its screen title as the preview.
		if(!self::_formHasElementType($elements, 'agentPrompt') && !self::_formHasElementType($elements, 'llmTranscript'))
			return ['preview' => strval($form['title'] ?? '')];

		// A chat is previewed by what was said in it, or by NOTHING until something is -- falling back to the
		// title would print the screen's name under the launcher's name, and a chat nobody has spoken in would
		// look identical to one that had.
		if(!($session_uuid = self::_sessionUuidFromFormElements($elements)))
			return [];

		return array_filter(self::_resumeFromTranscript($session_uuid), fn($v) => '' !== $v);
	}

	// Does this `await:form: elements:` map contain an element of `$type`? Keys are `<type>/<var>` (or a bare
	// `<type>`), with any `@annotation` stripped.
	private static function _formHasElementType(mixed $elements, string $type) : bool {
		if(!is_array($elements))
			return false;

		foreach(array_keys($elements) as $element_key) {
			list($element_type,) = array_pad(explode('/', preg_replace('/@.*$/', '', strval($element_key)), 2), 2, null);

			if($type === $element_type)
				return true;
		}

		return false;
	}

	// The `session_id` of the first `llmTranscript` element in an `await:form: elements:` map, if any.
	private static function _sessionUuidFromFormElements(mixed $elements) : string {
		if(!is_array($elements))
			return '';

		foreach($elements as $element_key => $element) {
			if(!is_array($element))
				continue;

			list($type,) = array_pad(explode('/', preg_replace('/@.*$/', '', strval($element_key)), 2), 2, null);

			if('llmTranscript' === $type && '' !== ($uuid = trim(strval($element['session_id'] ?? ''))))
				return $uuid;
		}

		return '';
	}

	/**
	 * The provider mark and latest prompt for a live transcript, in one round trip.
	 *
	 * `role = 'user'` is NOT the same as "something a person typed" -- every tool RESULT is also a user-role
	 * message, and on a working agent they outnumber real prompts two to one. A preview taken from the newest
	 * user row reads `ok`, or a raw SVG path.
	 *
	 * And `kind` alone does NOT sort them out. It's the right first filter (`text` plus legacy `''`, never
	 * `tool_result` or `summary` -- a compaction artifact, not something anybody said), but AWS Bedrock records
	 * Converse-shaped `toolResult` blocks under a passing `kind`. The PROVIDER decides the rest: its own
	 * `convertToGenericMessage()` already knows its wire format, and every converter flags a tool result by
	 * setting the neutral role to `tool`. Walk the newest handful of candidates and take the first that yields
	 * prose.
	 */
	private static function _resumeFromTranscript(string $session_uuid) : array {
		$db = DevblocksPlatform::services()->database();
		$out = [];

		try {
			if(!($row = $db->GetRowReader(sprintf("SELECT agent_id, provider, provider_params FROM llm_agent_session WHERE uuid = UUID_TO_BIN(%s)",
				$db->qstr($session_uuid)
			))))
				return $out;

			// Bounded: a preview is a nicety, and a session whose last few user turns are all tool results has
			// nothing worth showing anyway.
			$candidates = $db->GetArrayReader(sprintf("SELECT data_json FROM llm_agent_message ".
				"WHERE session_uuid = UUID_TO_BIN(%s) AND role = 'user' AND kind IN ('text','') ".
				"ORDER BY seq DESC LIMIT %d",
				$db->qstr($session_uuid),
				self::RESUME_PREVIEW_SCAN_DEPTH
			));

		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			return $out;
		}

		// The model's stamped `display:` block wins over the provider's own mark, mirroring
		// AgentPromptAwait::_buildModelEntry(). The provider is the TRANSPORT, not the model: Bedrock serving
		// Kimi is still Kimi, and an OpenAI-compatible endpoint fronting someone else's model is the case
		// `display:` exists for. Reading it off `provider_params` covers both entry paths, since a
		// record-resolved model and a session fallback both carry the block in that bag.
		$llm = DevblocksPlatform::services()->llm();

		$params = json_decode(strval($row['provider_params'] ?? ''), true);
		$display = (is_array($params) && is_array($params['display'] ?? null)) ? $params['display'] : [];

		$icon = trim(strval($display['icon'] ?? ''));
		$color = trim(strval($display['icon_color'] ?? ''));

		// An unprimed session has no provider yet; `getProviderIcon` would answer `bot` for it, which would
		// out-rank the launching toolbar's own icon for no reason.
		if('' !== ($provider_id = strval($row['provider'] ?? ''))) {
			$icon = $icon ?: $llm->getProviderIcon($provider_id);
			$color = $color ?: $llm->getProviderIconColor($provider_id);
		}

		if('' !== $icon)
			$out['icon'] = $icon;

		if('' !== $color)
			$out['color'] = $color;

		// WHO the conversation is with, so History can paint the agent's own face and demote the model's mark to
		// a corner badge -- the same split the transcript itself makes. The ID is stored rather than the avatar
		// URL: a URL would freeze a name and a picture that the record is free to change, and a parked
		// conversation is exactly where that goes stale. Resolved at list time instead
		// (`getResumableRowsForScopes`), which is also how the launcher's own identity works.
		if(($agent_id = intval($row['agent_id'] ?? 0)) > 0)
			$out['agent_id'] = strval($agent_id);

		// `validate: false` builds the provider as a PARSER -- no credentials, no connected account, no
		// network. The same form the transcript viewer and `AgentPromptAwait::_boundaryText()` use.
		$provider = ('' !== $provider_id) ? $llm->getProvider($provider_id, [], false) : null;

		if(!($provider instanceof \Cerb\LLM\Providers\Interfaces\Chat))
			return $out;

		foreach($candidates as $candidate) {
			if('' !== ($text = self::_messageTextFromJson($provider, $candidate['data_json'] ?? null))) {
				$out['preview'] = $text;
				break;
			}
		}

		return $out;
	}

	/**
	 * A message's prose, or '' when it isn't prose at all.
	 *
	 * The stored shape is whatever the provider sends, and each one differs -- a bare `content` string on a
	 * fresh prompt, Anthropic's `{type:text}` blocks, Bedrock's typeless `{text:…}` Converse blocks. Rather
	 * than re-deriving that here, hand the row to the provider that wrote it: `convertToGenericMessage()` is
	 * the same reader the transcript viewer uses, so this can never drift from what a reader sees.
	 *
	 * A tool result is rejected on the neutral ROLE, which every converter sets to `tool` when it meets one
	 * (Anthropic `tool_result`, Bedrock `toolResult`, OpenAI's `role: tool`).
	 */
	private static function _messageTextFromJson(\Cerb\LLM\Providers\Interfaces\Chat $provider, ?string $json) : string {
		if(!$json || !is_array($data = json_decode($json, true)))
			return '';

		try {
			$message = $provider->convertToGenericMessage($data);
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			return '';
		}

		if('tool' === $message->getRole())
			return '';

		$text = '';

		foreach($message->getMessages() as $block)
			$text .= strval($block['content'] ?? '');

		return trim($text);
	}

	/**
	 * Blank the descriptor. A `reset@bool: yes` submit restarts the conversation inside the same continuation,
	 * so its old name and preview describe a flow that no longer exists; the next form await re-derives them.
	 *
	 * `resume_scope` is deliberately NOT cleared -- it belongs to the launcher, which hasn't changed, and
	 * blanking it would make the restarted conversation unresumable.
	 */
	static function resumeFieldsCleared() : array {
		return [
			self::RESUME_LABEL => '',
			self::RESUME_METADATA => '',
		];
	}

	/**
	 * Display rows for a worker's opted-in conversations in the GLOBAL command bar's scopes.
	 */
	static function getResumableRows(int $worker_id, int $limit=25, array $launcher_identity=[]) : array {
		return self::getResumableRowsForScopes($worker_id, self::getGlobalResumeScopes(), $limit, $launcher_identity);
	}

	/**
	 * Display rows for a worker's opted-in conversations reopenable from one or more launcher scopes, newest
	 * first and keyed by token.
	 *
	 * Deliberately NOT built on `getWhere()`: that always selects `state_data`, a mediumtext holding the whole
	 * serialized automation dict, so listing ten conversations deserialized ten full dicts to print ten labels.
	 * Everything the list needs now lives in its own columns.
	 *
	 * Shared by the global command bar and the agent pane's History so the two can't drift into describing the
	 * same conversation differently. Batches the automation lookup rather than one query per row.
	 */
	/**
	 * `uri => {label, icon}` for every interaction a toolbar can launch, so a resumed conversation can wear the
	 * identity of the tile that started it. Recurses into submenu `items:`.
	 *
	 * Resolved from the CALLER's live toolbar at render time rather than stamped onto the continuation at launch:
	 * it costs no storage, and renaming a toolbar item retitles its past conversations instead of leaving a list
	 * of names that no longer exist anywhere in the UI.
	 */
	static function launcherIdentityFromToolbarItems(mixed $items) : array {
		$out = [];

		if(!is_array($items))
			return $out;

		foreach($items as $item) {
			if(!is_array($item))
				continue;

			if(array_key_exists('items', $item))
				$out += self::launcherIdentityFromToolbarItems($item['items']);

			if('' === ($uri = trim(strval($item['uri'] ?? ''))) || array_key_exists($uri, $out))
				continue;

			$out[$uri] = [
				'label' => trim(strval($item['label'] ?? '')),
				'icon' => trim(strval($item['icon'] ?? '')),
				// An agent launcher carries the agent's avatar, so a resumed conversation wears the same face in
				// History and the command bar that started it. Blank for a launcher with no picture.
				'image' => trim(strval($item['image'] ?? '')),
			];
		}

		return $out;
	}

	static function getResumableRowsForScopes(int $worker_id, array $scopes, int $limit=25, array $launcher_identity=[]) : array {
		if($worker_id < 1 || !($scopes = array_filter(array_map('strval', $scopes), fn($s) => '' !== $s)))
			return [];

		$db = DevblocksPlatform::services()->database();

		$sql = sprintf("SELECT token, uri, resume_label, resume_metadata, updated_at ".
			"FROM automation_continuation ".
			"WHERE %s = %d AND %s IN (%s) AND %s IN (%s) AND %s = '' AND %s IN (%s) AND %s = %s AND (%s = 0 OR %s > %d) ".
			"ORDER BY %s DESC ".
			"LIMIT %d",
			self::WORKER_ID,
			$worker_id,
			self::RESUME_SCOPE,
			implode(',', $db->qstrArray(array_values($scopes))),
			self::STATE_AWAIT,
			implode(',', $db->qstrArray(self::getResumableAwaitTypes())),
			self::PARENT_TOKEN,
			self::EXTENSION_ID,
			implode(',', $db->qstrArray(self::getWorkerResumableExtensionIds())),
			self::STATE,
			$db->qstr('await'),
			self::EXPIRES_AT,
			self::EXPIRES_AT,
			time(),
			self::UPDATED_AT,
			max(1, $limit)
		);

		if(!(($rs = $db->QueryReader($sql)) instanceof mysqli_result))
			return [];

		$rows = [];

		while($row = mysqli_fetch_assoc($rs)) {
			@$metadata = json_decode(strval($row['resume_metadata'] ?? ''), true);

			$rows[$row['token']] = [
				'token' => $row['token'],
				'uri' => $row['uri'],
				'label' => strval($row['resume_label'] ?? ''),
				'metadata' => is_array($metadata) ? $metadata : [],
				'updated_at' => intval($row['updated_at']),
			];
		}

		mysqli_free_result($rs);

		if(!$rows)
			return [];

		// A row that opted in but never named itself still needs to read as something: the automation's own
		// description, then its uri.
		$descriptions = [];

		foreach(DAO_Automation::getByUris(array_values(array_unique(array_column($rows, 'uri')))) as $automation) {
			if($automation->description)
				$descriptions[$automation->name] = $automation->description;
		}

		$out = [];

		foreach($rows as $token => $row) {
			$metadata = $row['metadata'];
			$launcher = $launcher_identity[$row['uri']] ?? [];

			// The agent the conversation is actually with, resolved NOW rather than read off a stamped URL, so a
			// renamed agent or a new picture shows up in History immediately. `DAO_Worker::get()` reads the
			// cached `getAll()`, so this is a lookup rather than a query per row.
			//
			// It also outranks the launcher's picture on purpose: launcher identity is keyed by automation URI,
			// and every agent that hasn't overridden `automation:` shares the one Cerb ships -- so the launcher
			// map holds whichever agent happened to be listed first, which is the wrong face for all the others.
			$agent = ($agent_id = intval($metadata['agent_id'] ?? 0))
				? DAO_Worker::get($agent_id) : null;

			$image = ($agent && $agent->is_ai) ? $agent->getImageUrl() : '';

			// Precedence, most specific first. The author's explicit keys win; then what the transcript knows
			// about itself; then the tile that launched it; then the automation's own description.
			$out[$token] = [
				'token' => $token,
				'label' => $row['label']
					?: ($launcher['label'] ?? '')
					?: ($descriptions[$row['uri']] ?? $row['uri']),
				'preview' => strval($metadata['preview'] ?? ''),
				'icon' => strval($metadata['icon'] ?? '')
					?: ($launcher['icon'] ?? '')
					?: 'history',
				'color' => strval($metadata['color'] ?? ''),
				// With a picture, `icon`/`color` become the model's corner badge rather than the avatar itself.
				'image' => $image ?: strval($launcher['image'] ?? ''),
				'description' => DevblocksPlatform::strPrettyTime($row['updated_at']),
				'updated_at' => $row['updated_at'],
			];
		}

		return $out;
	}

	/**
	 *
	 * @param array $ids
	 * @return Model_AutomationContinuation[]
	 */
	static function getIds(array $ids) : array {
		if(!is_array($ids))
			$ids = [$ids];

		if(empty($ids))
			return [];

		if(!method_exists(get_called_class(), 'getWhere'))
			return [];

		$db = DevblocksPlatform::services()->database();

		$models = [];

		$results = static::getWhere(sprintf("token IN (%s)",
			implode(',', $db->qstrArray($ids))
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AutomationContinuation[]|false
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AutomationContinuation();
			$object->token = $row['token'];
			$object->parent_token = $row['parent_token'];
			$object->root_token = $row['root_token'];
			$object->uri = $row['uri'];
			$object->state = $row['state'];
			$object->expires_at = intval($row['expires_at']);
			$object->updated_at = intval($row['updated_at']);
			$object->extension_id = $row['extension_id'];
			$object->worker_id = intval($row['worker_id']);
			$object->state_await = $row['state_await'];
			$object->resume_scope = strval($row['resume_scope'] ?? '');
			$object->resume_label = strval($row['resume_label'] ?? '');

			@$state_data = json_decode($row['state_data'], true);
			$object->state_data = $state_data ?: [];

			@$resume_metadata = json_decode(strval($row['resume_metadata'] ?? ''), true);
			$object->resume_metadata = is_array($resume_metadata) ? $resume_metadata : [];
			
			$objects[$object->token] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation_continuation');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return false;
		
		if(!is_array($ids))
			$ids = [$ids];
		
		$ids_list = implode(',', $db->qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation_continuation WHERE token IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM automation_continuation WHERE expires_at BETWEEN 1 AND %d",
			time()
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AutomationContinuation::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AutomationContinuation', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation_continuation.token as %s, ".
			"automation_continuation.parent_token as %s, ".
			"automation_continuation.root_token as %s, ".
			"automation_continuation.uri as %s, ".
			"automation_continuation.state as %s, ".
			"automation_continuation.expires_at as %s, ".
			"automation_continuation.updated_at as %s ",
			SearchFields_AutomationContinuation::TOKEN,
			SearchFields_AutomationContinuation::PARENT_TOKEN,
			SearchFields_AutomationContinuation::ROOT_TOKEN,
			SearchFields_AutomationContinuation::URI,
			SearchFields_AutomationContinuation::STATE,
			SearchFields_AutomationContinuation::EXPIRES_AT,
			SearchFields_AutomationContinuation::UPDATED_AT
			);
			
		$join_sql = "FROM automation_continuation ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AutomationContinuation');
	
		return array(
			'primary_table' => 'automation_continuation',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	/**
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_AutomationContinuation::TOKEN,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
};

class SearchFields_AutomationContinuation extends DevblocksSearchFields {
	const EXPIRES_AT = 'a_expires_at';
	const PARENT_TOKEN = 'a_parent_token';
	const ROOT_TOKEN = 'a_root_token';
	const STATE = 'a_state';
	const TOKEN = 'a_token';
	const UPDATED_AT = 'a_updated_at';
	const URI = 'a_uri';

	static private $_fields = null;
	
	static function getTableName() : string {
		return 'automation_continuation';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AutomationContinuation::TOKEN);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AutomationContinuation::UPDATED_AT);
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('automation_continuation.token', self::TOKEN),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = [
			self::EXPIRES_AT => new DevblocksSearchField(self::EXPIRES_AT, 'automation_continuation', 'expires_at', $translate->_('common.expires'), Model_CustomField::TYPE_DATE, true),
			self::PARENT_TOKEN => new DevblocksSearchField(self::PARENT_TOKEN, 'automation_continuation', 'parent_token', null, Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ROOT_TOKEN => new DevblocksSearchField(self::ROOT_TOKEN, 'automation_continuation', 'root_token', null, Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STATE => new DevblocksSearchField(self::STATE, 'automation_continuation', 'state', DevblocksPlatform::translateCapitalized('common.state'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TOKEN => new DevblocksSearchField(self::TOKEN, 'automation_continuation', 'token', null, Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation_continuation', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::URI => new DevblocksSearchField(self::STATE, 'automation_continuation', 'uri', DevblocksPlatform::translate('common.uri'), Model_CustomField::TYPE_SINGLE_LINE, true),
		];
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_AutomationContinuation {
	public $expires_at = 0;
	public $extension_id = '';
	public $parent_token = null;
	public $root_token = null;
	public $state = null;
	public $state_data = [];
	public $token = null;
	public $updated_at = 0;
	public $uri = null;
	public $worker_id = 0;
	public $state_await = '';
	// WHERE this may be reopened; '' = nowhere (not resumable). See DAO_AutomationContinuation::resumeScopeFor().
	public $resume_scope = '';
	// The conversation's name, from `await:form: resume: label:`. A working name until a proper `name` column lands.
	public $resume_label = '';
	// The rest of the resume descriptor (preview/icon/color) -- display payload only, nothing queries it.
	public $resume_metadata = [];
	
	private ?Model_AutomationContinuation  $_parent = null;
	private ?Model_AutomationContinuation  $_root = null;
	private ?Model_Automation $_automation = null;
	
	function getAutomation() : ?Model_Automation {
		if(is_null($this->_automation)) {
			$this->_automation = DAO_Automation::getByUri($this->uri);
		}
		
		return $this->_automation;
	}
	
	public function getParent() : ?Model_AutomationContinuation {
		if(is_null($this->_parent) && $this->parent_token) {
			$this->_parent = DAO_AutomationContinuation::getByToken($this->parent_token);
		}
		
		return $this->_parent;
	}
	
	public function getRoot() : ?Model_AutomationContinuation {
		if(is_null($this->_root) && $this->root_token) {
			$this->_root = DAO_AutomationContinuation::getByToken($this->root_token);
		}
		
		return $this->_root;
	}
};
