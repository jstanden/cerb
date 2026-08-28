<?php
class DAO_AgentModel extends Cerb_ORMHelper {
	const API_ENDPOINT_URL = 'api_endpoint_url';
	const CONNECTED_ACCOUNT_ID = 'connected_account_id';
	const CONTEXT_WINDOW = 'context_window';
	const CREATED_AT = 'created_at';
	const HAS_THINKING = 'has_thinking';
	const HAS_VISION = 'has_vision';
	const ICON = 'icon';
	const ICON_COLOR = 'icon_color';
	const ID = 'id';
	const LABEL = 'label';
	const MODEL = 'model';
	const NAME = 'name';
	const PARAMS_KATA = 'params_kata';
	const PRIORITY = 'priority';
	const PROVIDER = 'provider';
	const RATING_COST = 'rating_cost';
	const RATING_INTELLIGENCE = 'rating_intelligence';
	const RATING_PRIVACY = 'rating_privacy';
	const RATING_SPEED = 'rating_speed';
	const STATUS = 'status';
	const UPDATED_AT = 'updated_at';

	const STATUS_AVAILABLE = 0;
	const STATUS_UNLISTED = 1;
	const STATUS_DISABLED = 2;

	// The order a routed pool falls back to when a query names no `sort:` of its own.
	//
	// `priority` LEADS, because it is the admin's deliberate fixed order and has to be able to override the
	// ratings -- "our blessed model first" is exactly the case where the smartest model is not the wanted one.
	// At a uniform default it contributes nothing and the ratings decide, so an install that ranks nothing sees
	// no difference. A tier is not a total order either, so `name` is the load-bearing final tie-break: without
	// it, which of two `frontier` models a caller gets is whatever the database returned, and it can change
	// between resolves. Unrated (0) sorts last under DESC, which is right for an install that rates nothing.
	const QUERY_DEFAULT_SORT = 'priority,-intelligence,name';

	// Resolved query results are cached per EXACT query string and invalidated wholesale whenever any model
	// changes. Tag versioning rather than one big map: the key space is open (any automation may author any
	// query), so a map would grow without bound and every miss would rewrite it.
	const CACHE_QUERY_TAG = 'agent_model_query';

	// Insurance only -- the tag bust is the real invalidation. This covers what a write to `agent_model` can't
	// see: a query with a time-dependent term (`updated:`, later `usage:`), and CUSTOM FIELD or LINK values,
	// which are written through their own DAOs and never touch this one.
	const CACHE_QUERY_TTL = 300;

	// ⚠ The platform cache's in-REQUEST registry is NOT tag-aware: once a key has been loaded in this process it
	// is served from the registry without re-checking tag versions (see `_DevblocksCacheManager::load()`), so a
	// tag bust alone can't be seen by the request that caused it. An automation that writes an `agent_model` and
	// then resolves a pool in the same run would read its own stale answer.
	//
	// So the key carries a per-PROCESS epoch that `clearQueryCache()` bumps. It is 0 in every request that
	// doesn't write, so ordinary traffic still shares one set of keys; a writing request moves to its own and
	// keeps caching normally for the rest of its work.
	private static int $_query_cache_epoch = 0;

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		// A per-model endpoint override, lifted ahead of Model in the editor so it feeds the model-list fetch
		// and the connection test. Blank means "(auto)" -- use the provider's own default endpoint. It WINS
		// over any `api_endpoint_url:` left in `params_kata`.
		$validation
			->addField(self::API_ENDPOINT_URL)
			->url()
			;
		// The credentials this model authenticates with. Optional: a local provider (ollama, docker) needs none.
		$validation
			->addField(self::CONNECTED_ACCOUNT_ID)
			->id()
			->setNotEmpty(false)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, true))
			;
		$validation
			->addField(self::CONTEXT_WINDOW)
			->uint()
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::HAS_THINKING)
			->bit()
			;
		$validation
			->addField(self::HAS_VISION)
			->bit()
			;
		// The brand mark this model reads as, overriding its provider's. Most models arrive over the
		// OpenAI-compatible API (llama.cpp, z.ai, Qwen, LM Studio), so the provider id can't name the vendor.
		// Blank falls back to the provider's own icon. Constrained to the shipped glyph set: an unknown name
		// renders as an empty box, and the value is interpolated into a class attribute.
		$validation
			->addField(self::ICON)
			->string()
			->setMaxLength(64)
			->addValidator(function($value, &$error=null) {
				if('' === $value)
					return true;

				if(!in_array($value, DevblocksPlatform::services()->ui()->getCerbIcons(), true)) {
					$error = "must be a cerb-icons name.";
					return false;
				}

				return true;
			})
			;
		// The avatar's brand background. This lands inside a `style=` attribute, where the template layer's
		// htmlspecialchars does NOT stop CSS-context injection (`red;background-image:url(...)` needs no
		// quotes) -- so it's pinned to a strict hex, never free text.
		$validation
			->addField(self::ICON_COLOR)
			->string()
			->setMaxLength(32)
			->addValidator(function($value, &$error=null) use ($validation) {
				if('' === $value)
					return true;

				return ($validation->validators()->colorHex())($value, $error);
			})
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// A friendly display name for pickers -- `name` is the URI handle (`glm-4.6`), this is what a reader
		// should see ("GLM 4.6"). Blank falls back to `name`.
		$validation
			->addField(self::LABEL)
			->string()
			->setMaxLength(128)
			;
		// The provider's model string (e.g. `claude-sonnet-5`). Free text on purpose -- providers ship new
		// model ids constantly, and a whitelist would block the day they land.
		$validation
			->addField(self::MODEL)
			->string()
			->setMaxLength(255)
			;
		// `name` IS the uri: `cerb:agent_model:<name>` is how automations reference a model, so it's
		// identifier-shaped and unique. Same rule as `agent_filesystem.name`, plus underscore.
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			->setUnique(__CLASS__)
			->addValidator(function($value, &$error=null) {
				if(!preg_match('/^[A-Za-z0-9_.-]+$/', $value)) {
					$error = "must only contain letters, numbers, dots, dashes, and underscores (it's the model's URI).";
					return false;
				}
				
				return true;
			})
			;
		// The open tail of the `llm:<provider>:` block (cache/effort/thinking/compaction/...), in that same
		// grammar -- the block is deliberately open, so these can't be columns without a migration per knob.
		$validation
			->addField(self::PARAMS_KATA)
			->string()
			->setMaxLength(65535)
			;
		// The admin's fixed default ORDER for routed pools, ascending (0 first, 255 last) -- the same `priority`
		// convention as `automation_event_listener`, `mail_routing_rule`, `search_index`, and `toolbar_section`.
		// Leads QUERY_DEFAULT_SORT; any explicit `sort:` in a query overrides it entirely.
		$validation
			->addField(self::PRIORITY)
			->number()
			->setMin(0)
			->setMax(255)
			;
		// An LLM provider extension id (`anthropic`, `openai`, ...) -- the `llm:<provider>:` block key.
		$validation
			->addField(self::PROVIDER)
			->string()
			->setMaxLength(32)
			->setRequired(true)
			->addValidator(function($value, &$error=null) {
				if(!in_array($value, DevblocksPlatform::services()->llm()->getProviderIds())) {
					$error = sprintf("must be a known LLM provider (%s).", implode(', ', DevblocksPlatform::services()->llm()->getProviderIds()));
					return false;
				}
				
				return true;
			})
			;
		// Available (offered by routers) | Unlisted (skipped by routers, still runs when named) |
		// Disabled (refused everywhere). Router resolution filters on this; see Model_AgentModel::getStatuses().
		// Ordinal 10/20/30/40 tiers (0 = unrated), sparse so a tier can be inserted without a migration.
		// Model_AgentModel::getRatingScale() owns the label<->value mapping.
		$validation
			->addField(self::RATING_COST)
			->uint()
			->setMax(255)
			;
		$validation
			->addField(self::RATING_INTELLIGENCE)
			->uint()
			->setMax(255)
			;
		$validation
			->addField(self::RATING_PRIVACY)
			->uint()
			->setMax(255)
			;
		$validation
			->addField(self::RATING_SPEED)
			->uint()
			->setMax(255)
			;
		$validation
			->addField(self::STATUS)
			->uint()
			->setMax(2)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;

		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();

		$sql = "INSERT INTO agent_model () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		CerberusContexts::checkpointCreations(Context_AgentModel::ID, $id);

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_AgentModel::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'agent_model', $fields);

			self::clearQueryCache();

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}

	static function bulkUpdate(Model_ContextBulkUpdate $update) : bool {
		$do = $update->actions;
		$ids = $update->context_ids;

		if(empty($ids) || empty($do))
			return false;

		$context = Context_AgentModel::ID;

		$change_fields = [];
		$custom_fields = [];
		$deleted = false;

		foreach($do as $k => $v) {
			switch($k) {
				case 'delete':
					$deleted = true;
					break;

				case 'connected_account_id':
					$change_fields[self::CONNECTED_ACCOUNT_ID] = intval($v);
					break;

				case 'has_thinking':
					$change_fields[self::HAS_THINKING] = intval($v) ? 1 : 0;
					break;

				case 'has_vision':
					$change_fields[self::HAS_VISION] = intval($v) ? 1 : 0;
					break;

				case 'rating_cost':
					$change_fields[self::RATING_COST] = intval($v);
					break;

				case 'rating_intelligence':
					$change_fields[self::RATING_INTELLIGENCE] = intval($v);
					break;

				case 'rating_privacy':
					$change_fields[self::RATING_PRIVACY] = intval($v);
					break;

				case 'rating_speed':
					$change_fields[self::RATING_SPEED] = intval($v);
					break;

				case 'status':
					$change_fields[self::STATUS] = DevblocksPlatform::intClamp(intval($v), 0, 2);
					break;

				default:
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		if($deleted) {
			CerberusContexts::logActivityRecordDelete($context, $ids);

			self::delete($ids);

			return true;
		}

		DevblocksPlatform::markContextChanged($context, $ids);

		if(!empty($change_fields))
			self::update($ids, $change_fields, false);

		if(!empty($custom_fields))
			C4_AbstractView::_doBulkSetCustomFields($context, $custom_fields, $ids);

		if(isset($do['watchers']))
			C4_AbstractView::_doBulkChangeWatchers($context, $do['watchers'], $ids);

		CerberusContexts::checkpointChanges($context, $ids);

		return true;
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('agent_model', $fields, $where);
	}

	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}

		return true;
	}

	/**
	 * @param string $where
	 * @return Model_AgentModel[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT api_endpoint_url, connected_account_id, context_window, created_at, has_thinking, has_vision, icon, icon_color, id, label, model, name, params_kata, priority, provider, rating_cost, rating_intelligence, rating_privacy, rating_speed, status, updated_at " .
			"FROM agent_model " .
			$where_sql .
			$sort_sql .
			$limit_sql
		;

		if($options & DevblocksORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}

		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @return Model_AgentModel[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_AgentModel|null
	 */
	static function get($id) {
		if(empty($id))
			return null;

		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));

		if(array_key_exists($id, $objects))
			return $objects[$id];

		return null;
	}

	/**
	 * Resolve a model by its `name` -- which IS its uri (`cerb:agent_model:<name>`), so this is where
	 * every reference form ends up.
	 *
	 * @return Model_AgentModel|null
	 */
	static function getByName(string $name) {
		if('' === trim($name))
			return null;

		$db = DevblocksPlatform::services()->database();

		$objects = self::getWhere(sprintf("%s = %s",
			self::NAME,
			$db->qstr($name)
		));

		return reset($objects) ?: null;
	}

	/**
	 * Run an `agent_model` search and return the matching NAMES in the query's own sort order.
	 *
	 * **The query is OPTIONAL.** Blank means every available model -- the zero-config pool, and what makes an
	 * `llm.agent:` with nothing configured resolve at all. Narrowing is the opt-in, not the baseline.
	 *
	 * `status:available` is forced as a REQUIRED param before the caller's string is applied, and required
	 * params are pure AND-narrowing -- an editable param can never widen past one. That is what makes it safe
	 * to hand this an automation-authored query: a caller can only ever narrow the pool, never reach an
	 * unlisted or disabled model.
	 *
	 * Cached per EXACT query string, so an ad-hoc string from an automation is as cheap on the second call as a
	 * stored one, and two callers asking the same thing issue one search. Editing any model invalidates every
	 * entry (see CACHE_QUERY_TAG).
	 *
	 * ⚠ Pass `$nocache` where the answer must reflect an edit made moments ago -- tag versions have ONE-SECOND
	 * granularity, so a resolve in the same second as a model write can still read the stale entry. Editor
	 * previews want this; routing does not.
	 *
	 * @return string[]
	 */
	static function resolveQueryModelNames(string $query, ?string &$error=null, bool $nocache=false) : array {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf('cerb:agent_model:query:%d:%s', self::$_query_cache_epoch, sha1(trim($query)));

		// A VALID query matching nothing legitimately caches as `[]`, so test the TYPE -- a miss is null.
		if(!$nocache && is_array($cached = $cache->load($cache_key)))
			return $cached;

		if(!($context_ext = Extension_DevblocksContext::get(Context_AgentModel::ID, true)))
			return [];

		$view = $context_ext->getTempView();

		// The constructor seeds a sort, so null it first: that is the only way to tell afterwards whether the
		// QUERY set one. `sort:` is written to view state rather than to the params array.
		$view->renderSortBy = null;

		// Membership is enforced HERE, not in each caller's query, so no query author can forget it.
		$view->addParamsRequiredWithQuickSearch(sprintf('status.id:%d', self::STATUS_AVAILABLE), true);

		// Returns BEFORE the save below on purpose: a query that didn't parse has no result to cache.
		if('' !== trim($query) && !$view->addParamsWithQuickSearch($query, true, [], $error))
			return [];

		if(!$view->renderSortBy) {
			if(($sort = $view->_getSortFromQuickSearchQuery(self::QUERY_DEFAULT_SORT))) {
				$view->renderSortBy = $sort['sort_by'];
				$view->renderSortAsc = $sort['sort_asc'];
			} else {
				// Only reachable if a key in QUERY_DEFAULT_SORT stops being a quick-search field. Never leave
				// the sort null: an unordered pool means the model a caller gets can change between resolves.
				$view->renderSortBy = SearchFields_AgentModel::NAME;
				$view->renderSortAsc = true;
			}
		}

		// Every match, unpaged.
		$view->renderLimit = 0;
		$view->renderTotal = false;
		$view->renderSubtotals = null;

		list($models,) = $view->getData();

		$names = [];

		foreach($models as $row)
			$names[] = strval($row[SearchFields_AgentModel::NAME] ?? '');

		$names = array_values(array_filter($names));

		// Past epoch 0 the entry is meaningful only to THIS request, so keep it out of the shared cache rather
		// than leaving keys nobody will read again to age out.
		$cache->save($names, $cache_key, [self::CACHE_QUERY_TAG], self::CACHE_QUERY_TTL, self::$_query_cache_epoch > 0);

		return $names;
	}

	/**
	 * Invalidate every cached query result. Called from `update()` (which `create()` routes through) and
	 * `delete()`, so any change to any model re-resolves every pool on the next request.
	 */
	static function clearQueryCache() : void {
		DevblocksPlatform::services()->cache()->removeByTags([self::CACHE_QUERY_TAG]);

		// Bumped for this process too -- the tag bust alone is invisible to the request that made it. See the
		// note on $_query_cache_epoch.
		self::$_query_cache_epoch++;
	}

	/**
	 * Resolve several `agent_model` queries and return the names matching ALL of them.
	 *
	 * This is how a caller's hard requirement ("there is an image, so vision") composes with an admin's policy
	 * ("this org allows ZDR only") without either naming a model or naming the other's records. Both are hard
	 * sets; they intersect.
	 *
	 * **Order comes from the FIRST query.** One rule, no arbitration -- `array_intersect` preserves the first
	 * array's order, so the first query's `sort:` (or the default) decides ranking and later queries only
	 * remove.
	 *
	 * ⚠ **Each query is parsed SEPARATELY and must stay that way. Do NOT concatenate them.** The root group's
	 * boolean mode is decided by the first `T_BOOL` token and applies to every sibling, so a fragment holding a
	 * top-level `OR` turns the whole string into a UNION -- strictly wider than either input, which is the one
	 * direction this must never go. Concatenation also defeats `sort:`/`limit:` stripping and lets quote and
	 * paren pairing span the seam.
	 *
	 * @param string[] $queries
	 * @return string[]
	 */
	static function intersectQueryModelNames(array $queries, ?string &$error=null) : array {
		// ⚠ Blank entries are NOT filtered out. A blank query means "every available model", so it narrows
		// nothing -- but it still counts as a query, which matters because the FIRST one owns the order. Dropping
		// blanks here would silently hand ordering to the next query and make a caller's declared-but-empty pool
		// invisible. Callers that want a blank key gone should not pass it (KATA's `@optional` does that).
		$queries = array_values(array_map(fn($query) => trim(strval($query)), $queries));

		// No queries AT ALL is the zero-config pool.
		if(!$queries)
			return self::resolveQueryModelNames('', $error);

		$names = null;
		$counts = [];

		foreach($queries as $query) {
			$query_error = null;
			$matched = self::resolveQueryModelNames($query, $query_error);

			if($query_error) {
				$error = $query_error;
				return [];
			}

			$counts[$query] = count($matched);

			$names = is_null($names) ? $matched : array_intersect($names, $matched);
		}

		$names = array_values($names ?: []);

		// An empty intersection is otherwise undebuggable, so say what each side contributed.
		if(!$names) {
			$error = sprintf("No agent model matches every query (%s).",
				implode('; ', array_map(
					fn($query, $count) => sprintf('`%s` matched %d', $query, $count),
					array_keys($counts),
					$counts
				))
			);
		}

		return $names;
	}

	/**
	 * Names -> the `{<name> => <overrides>}` map that `llm.agent: model:` and `agentPrompt: models:` already
	 * consume, in the order given.
	 *
	 * The status of each record is re-checked HERE rather than trusted from the name, so a stale or cached
	 * name can never resurrect a model that has since been unlisted or disabled.
	 *
	 * @param string[] $names
	 * @return array `{<name> => []}`
	 */
	static function mapNamesToModels(array $names) : array {
		if(!$names)
			return [];

		// One pass over the table instead of a getByName() per name -- the recheck has to touch every record
		// anyway, and N names would otherwise be N queries.
		$by_name = [];

		foreach(self::getAll() as $record)
			$by_name[$record->name] = $record;

		$out = [];

		foreach($names as $name) {
			$name = strval($name);
			$record = $by_name[$name] ?? null;

			if(!$record || !$record->isAvailable())
				continue;

			$out[$name] = [];
		}

		return $out;
	}

	/**
	 * @param array $ids
	 * @return Model_AgentModel[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AgentModel[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AgentModel();
			$object->api_endpoint_url = $row['api_endpoint_url'];
			$object->connected_account_id = intval($row['connected_account_id']);
			$object->context_window = intval($row['context_window']);
			$object->created_at = intval($row['created_at']);
			$object->has_thinking = intval($row['has_thinking']);
			$object->has_vision = intval($row['has_vision']);
			$object->icon = $row['icon'];
			$object->icon_color = $row['icon_color'];
			$object->id = intval($row['id']);
			$object->label = $row['label'];
			$object->model = $row['model'];
			$object->name = $row['name'];
			$object->params_kata = $row['params_kata'];
			$object->priority = intval($row['priority']);
			$object->provider = $row['provider'];
			$object->rating_cost = intval($row['rating_cost']);
			$object->rating_intelligence = intval($row['rating_intelligence']);
			$object->rating_privacy = intval($row['rating_privacy']);
			$object->rating_speed = intval($row['rating_speed']);
			$object->status = intval($row['status']);
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function random() {
		return self::_getRandom('agent_model');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_AgentModel::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM agent_model WHERE id IN (%s)", $ids_list));

		self::clearQueryCache();

		parent::_deleteAbstractAfter($context, $ids);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AgentModel::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AgentModel', $sortBy);

		$select_sql = sprintf("SELECT " .
			"agent_model.api_endpoint_url as %s, " .
			"agent_model.connected_account_id as %s, " .
			"agent_model.context_window as %s, " .
			"agent_model.created_at as %s, " .
			"agent_model.has_thinking as %s, " .
			"agent_model.has_vision as %s, " .
			"agent_model.icon as %s, " .
			"agent_model.icon_color as %s, " .
			"agent_model.id as %s, " .
			"agent_model.label as %s, " .
			"agent_model.model as %s, " .
			"agent_model.name as %s, " .
			"agent_model.priority as %s, " .
			"agent_model.provider as %s, " .
			"agent_model.rating_cost as %s, " .
			"agent_model.rating_intelligence as %s, " .
			"agent_model.rating_privacy as %s, " .
			"agent_model.rating_speed as %s, " .
			"agent_model.status as %s, " .
			"agent_model.updated_at as %s",
			SearchFields_AgentModel::API_ENDPOINT_URL,
			SearchFields_AgentModel::CONNECTED_ACCOUNT_ID,
			SearchFields_AgentModel::CONTEXT_WINDOW,
			SearchFields_AgentModel::CREATED_AT,
			SearchFields_AgentModel::HAS_THINKING,
			SearchFields_AgentModel::HAS_VISION,
			SearchFields_AgentModel::ICON,
			SearchFields_AgentModel::ICON_COLOR,
			SearchFields_AgentModel::ID,
			SearchFields_AgentModel::LABEL,
			SearchFields_AgentModel::MODEL,
			SearchFields_AgentModel::NAME,
			SearchFields_AgentModel::PRIORITY,
			SearchFields_AgentModel::PROVIDER,
			SearchFields_AgentModel::RATING_COST,
			SearchFields_AgentModel::RATING_INTELLIGENCE,
			SearchFields_AgentModel::RATING_PRIVACY,
			SearchFields_AgentModel::RATING_SPEED,
			SearchFields_AgentModel::STATUS,
			SearchFields_AgentModel::UPDATED_AT
		);

		$join_sql = "FROM agent_model ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AgentModel');

		return [
			'primary_table' => 'agent_model',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		return self::_searchWithTimeout(
			SearchFields_AgentModel::ID,
			$query_parts['select'],
			$query_parts['join'],
			$query_parts['where'],
			$query_parts['sort'],
			$page,
			$limit,
			$withCounts
		);
	}
};

class SearchFields_AgentModel extends DevblocksSearchFields {
	const API_ENDPOINT_URL = 'a_api_endpoint_url';
	const CONNECTED_ACCOUNT_ID = 'a_connected_account_id';
	const CONTEXT_WINDOW = 'a_context_window';
	const CREATED_AT = 'a_created_at';
	const HAS_THINKING = 'a_has_thinking';
	const HAS_VISION = 'a_has_vision';
	const ICON = 'a_icon';
	const ICON_COLOR = 'a_icon_color';
	const ID = 'a_id';
	const LABEL = 'a_label';
	const MODEL = 'a_model';
	const NAME = 'a_name';
	const PRIORITY = 'a_priority';
	const PROVIDER = 'a_provider';
	const RATING_COST = 'a_rating_cost';
	const RATING_INTELLIGENCE = 'a_rating_intelligence';
	const RATING_PRIVACY = 'a_rating_privacy';
	const RATING_SPEED = 'a_rating_speed';
	const STATUS = 'a_status';
	const UPDATED_AT = 'a_updated_at';

	const VIRTUAL_CONNECTED_ACCOUNT_SEARCH = '*_connected_account_search';

	static private $_fields = null;

	static function getTableName() : string {
		return 'agent_model';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModel::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModel::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModel::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_AgentModel::ID => new DevblocksSearchFieldContextKeys('agent_model.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONNECTED_ACCOUNT_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, 'agent_model.connected_account_id');

			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_AgentModel::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::CONNECTED_ACCOUNT_ID:
				$models = DAO_ConnectedAccount::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');

				// The column is optional -- a local provider (ollama, docker) authenticates with nothing
				if(in_array(0, $values))
					$label_map[0] = DevblocksPlatform::translate('common.none');

				return $label_map;

			case self::ID:
				$models = DAO_AgentModel::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');

			case self::STATUS:
				return array_intersect_key(Model_AgentModel::getStatuses(), array_flip($values));

			case self::PROVIDER:
				// The vendor names a reader knows ("AWS Bedrock", not `aws_bedrock`). Embedding-only
				// providers (voyage, pinecone) aren't in this map, so they fall back to their raw id.
				$labels = array_column(DevblocksPlatform::services()->llm()->getAgentProviders(), 'label', 'id');
				return array_intersect_key($labels, array_flip($values));
		}

		return parent::getLabelsForKeyValues($key, $values);
	}

	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'authentication':
				$key = 'authentication.id';
				break;

			case 'status':
				$key = 'status.id';
				break;
		}

		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}

	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();

		return self::$_fields;
	}

	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = [
			self::API_ENDPOINT_URL => new DevblocksSearchField(self::API_ENDPOINT_URL, 'agent_model', 'api_endpoint_url', $translate->_('dao.agent_model.api_endpoint_url'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CONNECTED_ACCOUNT_ID => new DevblocksSearchField(self::CONNECTED_ACCOUNT_ID, 'agent_model', 'connected_account_id', $translate->_('dao.agent_model.connected_account_id'), Model_CustomField::TYPE_NUMBER, true),
			self::CONTEXT_WINDOW => new DevblocksSearchField(self::CONTEXT_WINDOW, 'agent_model', 'context_window', $translate->_('dao.agent_model.context_window'), Model_CustomField::TYPE_NUMBER, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'agent_model', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::HAS_THINKING => new DevblocksSearchField(self::HAS_THINKING, 'agent_model', 'has_thinking', $translate->_('dao.agent_model.has_thinking'), Model_CustomField::TYPE_CHECKBOX, true),
			self::HAS_VISION => new DevblocksSearchField(self::HAS_VISION, 'agent_model', 'has_vision', $translate->_('dao.agent_model.has_vision'), Model_CustomField::TYPE_CHECKBOX, true),
			self::ICON => new DevblocksSearchField(self::ICON, 'agent_model', 'icon', $translate->_('dao.agent_model.icon'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ICON_COLOR => new DevblocksSearchField(self::ICON_COLOR, 'agent_model', 'icon_color', $translate->_('dao.agent_model.icon_color'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ID => new DevblocksSearchField(self::ID, 'agent_model', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'agent_model', 'label', $translate->_('dao.agent_model.label'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MODEL => new DevblocksSearchField(self::MODEL, 'agent_model', 'model', $translate->_('dao.agent_model.model'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'agent_model', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PRIORITY => new DevblocksSearchField(self::PRIORITY, 'agent_model', 'priority', $translate->_('common.priority'), Model_CustomField::TYPE_NUMBER, true),
			self::PROVIDER => new DevblocksSearchField(self::PROVIDER, 'agent_model', 'provider', $translate->_('dao.agent_model.provider'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::RATING_COST => new DevblocksSearchField(self::RATING_COST, 'agent_model', 'rating_cost', $translate->_('dao.agent_model.rating_cost'), Model_CustomField::TYPE_NUMBER, true),
			self::RATING_INTELLIGENCE => new DevblocksSearchField(self::RATING_INTELLIGENCE, 'agent_model', 'rating_intelligence', $translate->_('dao.agent_model.rating_intelligence'), Model_CustomField::TYPE_NUMBER, true),
			self::RATING_PRIVACY => new DevblocksSearchField(self::RATING_PRIVACY, 'agent_model', 'rating_privacy', $translate->_('dao.agent_model.rating_privacy'), Model_CustomField::TYPE_NUMBER, true),
			self::RATING_SPEED => new DevblocksSearchField(self::RATING_SPEED, 'agent_model', 'rating_speed', $translate->_('dao.agent_model.rating_speed'), Model_CustomField::TYPE_NUMBER, true),
			self::STATUS => new DevblocksSearchField(self::STATUS, 'agent_model', 'status', $translate->_('common.status'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'agent_model', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONNECTED_ACCOUNT_SEARCH => new DevblocksSearchField(self::VIRTUAL_CONNECTED_ACCOUNT_SEARCH, '*', 'connected_account_search', null, null, false),
		];

		if(($virtual_columns = DevblocksSearchField::getVirtualFields()))
			$columns = array_merge($columns, $virtual_columns);

		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));

		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_AgentModel extends DevblocksRecordModel {
	public $api_endpoint_url;
	public $connected_account_id;
	public $context_window;
	public $created_at;
	public $has_thinking;
	public $has_vision;
	public $icon;
	public $icon_color;
	public $id;
	public $label;
	public $model;
	public $name;
	public $params_kata;
	public $priority;
	public $provider;
	public $rating_cost;
	public $rating_intelligence;
	public $rating_privacy;
	public $rating_speed;
	public $status;
	public $updated_at;

	/**
	 * The label<->value mapping for the ordinal ratings. THE single source: the editor, worklist cells,
	 * quick-search parsing, autocomplete, and subtotals all read this and none may hard-code a label.
	 *
	 * Values are sparse decades so a tier can be inserted without a migration, and so a label can be
	 * REPOINTED later (when today's `frontier` becomes ordinary, move it to 50 and rows storing 40 read as
	 * `advanced` with nothing rewritten). Repointing is safe; RENAMING breaks saved searches that typed it.
	 *
	 * Labels must contain no spaces -- they double as quick-search values, which lex as `[^\s]+`.
	 */
	public static function getRatingScale(string $rating) : array {
		return match($rating) {
			'intelligence' => [10 => 'basic', 20 => 'efficient', 30 => 'advanced', 40 => 'frontier'],
			'speed' => [10 => 'slow', 20 => 'moderate', 30 => 'fast', 40 => 'instant'],
			'privacy' => [10 => 'standard', 20 => 'no-training', 30 => 'zdr', 40 => 'local'],
			'cost' => [10 => 'free', 20 => 'cheap', 30 => 'moderate', 40 => 'premium'],
			default => [],
		};
	}

	/** The rating keys, in editor order. */
	public static function getRatings() : array {
		return ['intelligence', 'speed', 'privacy', 'cost'];
	}

	/**
	 * One hue per axis, from the `--cerb-color-tag-*` palette, so an axis is identifiable without reading
	 * its column header. Cost reads as MONEY rather than as a score -- it's the one axis where MORE is
	 * WORSE, so it must not share the language of the three where more is better.
	 *
	 * The hue is a palette name, not CSS: every drawing surface turns it into a `.cerb-ui-meter--<hue>`
	 * modifier or a `--cerb-color-tag-<hue>` var. Only the six palette hues render; anything else falls
	 * back to the component default.
	 */
	public static function getRatingColors() : array {
		return [
			'intelligence' => 'purple',
			'speed' => 'orange',
			'privacy' => 'blue',
			'cost' => 'green',
		];
	}

	/** One glyph per axis. Paired with getRatingColors() -- the two together are the axis's identity. */
	public static function getRatingIcons() : array {
		return [
			'intelligence' => 'brain',
			'speed' => 'zap',
			'privacy' => 'lock',
			'cost' => 'coins',
		];
	}

	/**
	 * The same scale, cased for DISPLAY. `getRatingScale()` stays lowercase because those words double as
	 * quick-search values (`privacy:zdr`) and as the labels a bulk update posts back, so casing can't be
	 * applied there -- it happens here, at the last moment, and only on the way to a screen.
	 *
	 * An acronym keeps its own case. Anything not listed gets a leading capital, so a new tier needs an
	 * entry here only when `zdr` is the shape of it.
	 */
	public static function getRatingScaleLabels(string $rating) : array {
		$acronyms = [
			'zdr' => 'ZDR',
		];

		$out = [];

		foreach(self::getRatingScale($rating) as $tier => $word)
			$out[$tier] = $acronyms[$word] ?? mb_ucfirst($word);

		return $out;
	}

	/**
	 * Available = offered by routers. Unlisted = skipped by routers but still runs when an automation names
	 * it. Disabled = refused everywhere, including by name.
	 */
	public static function getStatuses() : array {
		return [
			DAO_AgentModel::STATUS_AVAILABLE => 'available',
			DAO_AgentModel::STATUS_UNLISTED => 'unlisted',
			DAO_AgentModel::STATUS_DISABLED => 'disabled',
		];
	}

	/** Routers may offer this model. */
	public function isAvailable() : bool {
		return DAO_AgentModel::STATUS_AVAILABLE == $this->status;
	}

	/** Usable at all -- an automation naming it directly still runs it unless it's disabled. */
	public function isUsable() : bool {
		return DAO_AgentModel::STATUS_DISABLED != $this->status;
	}

	/**
	 * This model as the `llm:<provider>:` params block an automation would otherwise author inline.
	 *
	 * The typed columns and `params_kata` are already in that grammar, so this is a MERGE, not a
	 * translation: the parsed KATA supplies the open knobs (cache/effort/thinking/compaction/...) and the
	 * columns win over anything the KATA repeats — a column is the field someone actually edited in the form.
	 *
	 * @return array [provider_id, params] — feed straight to `llm()->getProvider()`
	 */
	public function getProviderParams(?string &$error=null) : array {
		$params = [];

		if('' !== trim(strval($this->params_kata))) {
			$kata = DevblocksPlatform::services()->kata();

			if(false === ($parsed = $kata->parse($this->params_kata, $error)))
				return [strval($this->provider), []];

			// `@bool`/`@int` annotations are applied by formatTree(), not parse() -- without it a
			// `cache@bool: yes` would arrive as the string "yes" under the key `cache@bool`.
			if(false === ($parsed = $kata->formatTree($parsed, null, $error)))
				return [strval($this->provider), []];

			if(is_array($parsed))
				$params = $parsed;
		}

		if('' !== trim(strval($this->model)))
			$params['model'] = $this->model;

		// A first-class column wins over any `api_endpoint_url:` in the parsed params_kata -- it's the field
		// someone actually edited in the form. Blank leaves the provider's own default endpoint in place.
		if('' !== trim(strval($this->api_endpoint_url)))
			$params['api_endpoint_url'] = $this->api_endpoint_url;

		if($this->connected_account_id)
			$params['authentication'] = sprintf('cerb:connected_account:%d', $this->connected_account_id);

		if($this->has_vision)
			$params['vision'] = true;

		// `has_thinking`, not `thinking` -- that key is the provider's grouped reasoning block AND one of the
		// four fields _capabilitySignature() hashes, so reusing it would plant a summary boundary mid-session.
		// Inert on the wire (providers read $_params by key, never enumerate it), same contract as `display:`.
		if($this->has_thinking)
			$params['has_thinking'] = true;

		if($this->context_window > 0)
			$params['context_window'] = intval($this->context_window);

		// Stored tiers, not labels -- getRatingScale() stays the one place a value becomes a word, so a
		// re-pointed anchor reads correctly even off a session snapshot taken before the change.
		$ratings = array_filter([
			'intelligence' => intval($this->rating_intelligence),
			'speed' => intval($this->rating_speed),
			'privacy' => intval($this->rating_privacy),
			'cost' => intval($this->rating_cost),
		]);

		if($ratings)
			$params['ratings'] = $ratings;

		// Display metadata rides the params bag on purpose: a transcript stores the RESOLVED block, never
		// `agent_model.id`, so this is the only way the vendor a session actually ran survives the record being
		// renamed, re-pointed, or deleted. Providers read `$_params` by key and never enumerate it, so an
		// unknown `display:` is inert on the wire; `_capabilitySignature()` ignores it, so changing an icon
		// mid-session doesn't plant a summary boundary.
		$display = array_filter([
			'name' => trim(strval($this->label)),
			'icon' => trim(strval($this->icon)),
			'icon_color' => trim(strval($this->icon_color)),
		], fn($v) => '' !== $v);

		// array_replace, not assignment: a blank column must contribute nothing (same contract as
		// `api_endpoint_url`), so a `display:` authored in params_kata survives one column being set.
		if($display)
			$params['display'] = array_replace(
				is_array($params['display'] ?? null) ? $params['display'] : [],
				$display
			);

		return [strval($this->provider), $params];
	}

	/** The name a reader should see. `name` is the URI handle (`glm-4.6`); `label` is the friendly form. */
	public function getDisplayName() : string {
		return trim(strval($this->label)) ?: strval($this->name);
	}

	/**
	 * The fallback chain, as statics so the worklist can resolve a raw search row without hydrating a model.
	 * Its own override wins: `provider` can't name the vendor when a model arrives over the OpenAI-compatible
	 * API (llama.cpp, z.ai, Qwen, LM Studio).
	 */
	public static function displayIconFor($icon, $provider) : string {
		return trim(strval($icon))
			?: (DevblocksPlatform::services()->llm()->getProviderIcon(strval($provider)) ?: 'bot-message');
	}

	public static function displayIconColorFor($icon_color, $provider) : string {
		return trim(strval($icon_color))
			?: DevblocksPlatform::services()->llm()->getProviderIconColor(strval($provider));
	}

	/** The brand mark this model reads as, everywhere it's listed. */
	public function getDisplayIcon() : string {
		return self::displayIconFor($this->icon, $this->provider);
	}

	public function getDisplayIconColor() : string {
		return self::displayIconColorFor($this->icon_color, $this->provider);
	}
};

class View_AgentModel extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'agent_models';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Agent Model');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AgentModel::NAME;
		$this->renderSortAsc = true;

		// The at-a-glance answer to "what models do we have here": which vendor, which model string, how big a
		// window, and is it live. The rest are available but off by default.
		$this->view_columns = [
			SearchFields_AgentModel::NAME,
			SearchFields_AgentModel::PROVIDER,
			SearchFields_AgentModel::PRIORITY,
			SearchFields_AgentModel::CONNECTED_ACCOUNT_ID,
			SearchFields_AgentModel::CONTEXT_WINDOW,
			SearchFields_AgentModel::HAS_VISION,
			SearchFields_AgentModel::HAS_THINKING,
			SearchFields_AgentModel::RATING_INTELLIGENCE,
			SearchFields_AgentModel::RATING_PRIVACY,
			SearchFields_AgentModel::RATING_SPEED,
			SearchFields_AgentModel::RATING_COST,
			SearchFields_AgentModel::UPDATED_AT,
		];

		$this->addColumnsHidden([
			SearchFields_AgentModel::ID,
			SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH,
		]);

		$this->doResetCriteria();
	}

	// The glyph/color for a worklist ROW. Rows are raw search arrays, not models, and Model_AgentModel isn't in
	// Smarty's static-class allowlist — so the template calls these on $view and the fallback chain stays
	// defined in exactly one place.
	function getRowIcon($row) : string {
		return Model_AgentModel::displayIconFor(
			$row[SearchFields_AgentModel::ICON] ?? '',
			$row[SearchFields_AgentModel::PROVIDER] ?? ''
		);
	}

	function getRowIconColor($row) : string {
		return Model_AgentModel::displayIconColorFor(
			$row[SearchFields_AgentModel::ICON_COLOR] ?? '',
			$row[SearchFields_AgentModel::PROVIDER] ?? ''
		);
	}

	protected function _getData() {
		return DAO_AgentModel::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}

	function getData() {
		$objects = $this->_getDataBoundedTimed();
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AgentModel');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_AgentModel', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AgentModel', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;

			switch($field_key) {
				// Low-cardinality only. `model` and `label` are near-unique per row, so grouping by them
				// would just re-list the worklist.
				case SearchFields_AgentModel::API_ENDPOINT_URL:
				case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
				case SearchFields_AgentModel::CONTEXT_WINDOW:
				case SearchFields_AgentModel::HAS_THINKING:
				case SearchFields_AgentModel::HAS_VISION:
				case SearchFields_AgentModel::PRIORITY:
				case SearchFields_AgentModel::PROVIDER:
				case SearchFields_AgentModel::RATING_COST:
				case SearchFields_AgentModel::RATING_INTELLIGENCE:
				case SearchFields_AgentModel::RATING_PRIVACY:
				case SearchFields_AgentModel::RATING_SPEED:
				case SearchFields_AgentModel::STATUS:
					$pass = true;
					break;

				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_')) {
						$pass = $this->_canSubtotalCustomField($field_key);
					} else if (str_starts_with($field_key, '*_')) {
						$pass = $this->_canSubtotalVirtualField($field_key);
					}
					break;
			}

			if($pass)
				$fields[$field_key] = $field_model;
		}

		return $fields;
	}

	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = Context_AgentModel::ID;

		if(!array_key_exists($column, $fields))
			return [];

		switch($column) {
			case SearchFields_AgentModel::HAS_THINKING:
			case SearchFields_AgentModel::HAS_VISION:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_AgentModel::STATUS:
				$label_map = function(array $values) use ($column) {
					return SearchFields_AgentModel::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in');
				break;

			case SearchFields_AgentModel::RATING_COST:
			case SearchFields_AgentModel::RATING_INTELLIGENCE:
			case SearchFields_AgentModel::RATING_PRIVACY:
			case SearchFields_AgentModel::RATING_SPEED:
				$rating = substr(SearchFields_AgentModel::getFields()[$column]->db_column, 7);
				$label_map = function(array $values) use ($rating) {
					$scale = Model_AgentModel::getRatingScaleLabels($rating);
					$map = [];

					foreach($values as $value)
						$map[$value] = $scale[$value] ?? DevblocksPlatform::translate('common.unknown');

					return $map;
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in');
				break;

			case SearchFields_AgentModel::API_ENDPOINT_URL:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;

			case SearchFields_AgentModel::PROVIDER:
				$label_map = function(array $values) use ($column) {
					return SearchFields_AgentModel::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;

			case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_AgentModel::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in');
				break;

			case SearchFields_AgentModel::PRIORITY:
				// Raw numbers read fine here (they ARE the interface), but 50 is the neutral default, so say so --
				// otherwise a column of "50" looks like someone set it deliberately.
				$label_map = function(array $values) {
					$map = [];

					foreach($values as $value)
						$map[$value] = (50 == $value)
							? sprintf('%d (%s)', $value, DevblocksPlatform::translate('common.default'))
							: strval($value);

					return $map;
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in');
				break;

			case SearchFields_AgentModel::CONTEXT_WINDOW:
				// Group by the size a reader recognizes (`200K`) rather than the raw token count
				$label_map = function(array $values) {
					$map = [];

					foreach($values as $value)
						$map[$value] = DevblocksPlatform::strPrettyNumber($value);

					return $map;
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map);
				break;

			default:
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				} else if(DevblocksPlatform::strStartsWith($column, '*_')) {
					$counts = $this->_getSubtotalCountForVirtualField($context, $column);
				}
				break;
		}

		return $counts;
	}

	function getQuickSearchDefaultFilter(?DevblocksSearchCriteria $criteria=null) : string {
		return 'name';
	}

	/**
	 * Tier names as filter examples, plus the comparisons that are the point of an ordinal.
	 *
	 * ⚠ These only surface because the field is declared TYPE_TEXT. The `number` case in the suggestion
	 * builder (`abstract_view.php`) hardcodes (equals)/(greater than)/… and never reads `examples`, so a
	 * TYPE_NUMBER rating would autocomplete as an anonymous integer and the tier names would be invisible.
	 * Parsing doesn't care -- getParamFromQuickSearchFieldTokens() handles these keys itself.
	 */
	private static function _getRatingExamples(string $rating) : array {
		$labels = array_values(Model_AgentModel::getRatingScale($rating));

		if(!$labels)
			return [];

		$examples = $labels;

		if(count($labels) > 2) {
			$examples[] = sprintf('>=%s', $labels[2]);
			$examples[] = sprintf('<=%s', $labels[1]);
			$examples[] = sprintf('[%s,%s]', $labels[2], $labels[3] ?? $labels[2]);
		}

		return $examples;
	}

	/**
	 * `intelligence:>=advanced` and `intelligence:>=30` are the same query -- a label is translated to its
	 * value before the numeric criteria is built, so operators keep working either way.
	 */
	private static function _getRatingParamFromTokens(string $field, $tokens) {
		$scale = Model_AgentModel::getRatingScale($field);
		$values = array_flip($scale);

		// Keep any leading operator; swap only the label for its number. Arrays (`[advanced,frontier]`)
		// carry a list rather than a string, so both shapes are translated.
		$translate = function($value) use ($values) {
			$matches = [];

			if(!is_string($value) || !preg_match('/^([<>!=]*)(.*)$/', $value, $matches))
				return $value;

			$label = DevblocksPlatform::strLower(trim($matches[2]));

			return array_key_exists($label, $values) ? $matches[1] . $values[$label] : $value;
		};

		foreach($tokens as $token) {
			if(!($token instanceof CerbQuickSearchLexerToken))
				continue;

			if(is_array($token->value)) {
				$token->value = array_map($translate, $token->value);
			} else {
				$token->value = $translate($token->value);
			}
		}

		$param_keys = [
			'cost' => SearchFields_AgentModel::RATING_COST,
			'intelligence' => SearchFields_AgentModel::RATING_INTELLIGENCE,
			'privacy' => SearchFields_AgentModel::RATING_PRIVACY,
			'speed' => SearchFields_AgentModel::RATING_SPEED,
		];

		return DevblocksSearchCriteria::getNumberParamFromTokens($param_keys[$field], $tokens);
	}

	/** `status:available`, `status:[a,u]`, `status:!d` -- matched on the first letter, like ticket status. */
	private static function _getStatusParamFromTokens($tokens) {
		$oper = null;
		$values = [];

		CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $values);

		$ids = [];

		$statuses = Model_AgentModel::getStatuses();

		foreach($values as $value) {
			$value = trim(strval($value));

			// A raw id is accepted too -- `status.id:` is the documented numeric form, but nobody should get
			// an empty result for typing the number here.
			if(is_numeric($value)) {
				if(array_key_exists(intval($value), $statuses))
					$ids[] = intval($value);

				continue;
			}

			foreach($statuses as $id => $label) {
				if(0 === strncasecmp($value, $label, 1))
					$ids[] = $id;
			}
		}

		return new DevblocksSearchCriteria(SearchFields_AgentModel::STATUS, $oper, $ids);
	}

	function getQuickSearchFields() {
		$search_fields = SearchFields_AgentModel::getFields();

		// Whatever providers this install actually has, so the autocomplete can't offer a dead id
		$provider_labels = array_column(DevblocksPlatform::services()->llm()->getAgentProviders(), 'label', 'id');

		$fields = [
			'apiEndpointUrl' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::API_ENDPOINT_URL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			// Named for the column's own label ("Authentication"), which is what the worklist header,
			// the peek, and the profile all say -- not for the record type behind it.
			'authentication' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => [
					'param_key' => SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH,
					'select_key' => 'agent_model.connected_account_id',
				],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, 'q' => ''],
				]
			],
			'authentication.id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::CONNECTED_ACCOUNT_ID],
				'examples' => [
					['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, 'q' => ''],
				]
			],
			'contextWindow' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::CONTEXT_WINDOW],
			],
			'priority' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::PRIORITY],
				'examples' => ['50', '<50', '>50', '[0..10]'],
			],
			'cost' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::RATING_COST],
				'examples' => self::_getRatingExamples('cost'),
			],
			'intelligence' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::RATING_INTELLIGENCE],
				'examples' => self::_getRatingExamples('intelligence'),
			],
			'privacy' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::RATING_PRIVACY],
				'examples' => self::_getRatingExamples('privacy'),
			],
			'speed' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::RATING_SPEED],
				'examples' => self::_getRatingExamples('speed'),
			],
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentModel::CREATED_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_AgentModel::ID],
				]
			],
			'hasThinking' => [
				'type' => DevblocksSearchCriteria::TYPE_BOOL,
				'options' => ['param_key' => SearchFields_AgentModel::HAS_THINKING],
			],
			'hasVision' => [
				'type' => DevblocksSearchCriteria::TYPE_BOOL,
				'options' => ['param_key' => SearchFields_AgentModel::HAS_VISION],
			],
			'icon' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::ICON],
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_AgentModel::ID, 'q' => ''],
				]
			],
			'label' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::LABEL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'model' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::MODEL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'provider' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::PROVIDER],
				'examples' => [
					['type' => 'list', 'values' => $provider_labels],
				]
			],
			'status' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::STATUS],
				'examples' => array_merge(array_values(Model_AgentModel::getStatuses()), ['[a,u]', '![d]']),
			],
			'status.id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::STATUS],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentModel::UPDATED_AT],
			],
			'watchers' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_WATCHERS],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
				],
			],
		];

		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		$fields = self::_appendFieldsFromQuickSearchContext(Context_AgentModel::ID, $fields, null);
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		ksort($fields);

		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'authentication':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH);

			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');

			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(DevblocksSearchField::VIRTUAL_WATCHERS, $tokens);

			case 'status':
				return self::_getStatusParamFromTokens($tokens);

			case 'cost':
			case 'intelligence':
			case 'privacy':
			case 'speed':
				return self::_getRatingParamFromTokens($field, $tokens);

			default:
				if($field == 'links' || str_starts_with($field, 'links.'))
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);

				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}

	function render() {
		$this->_sanitize();

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$custom_fields = DAO_CustomField::getByContext(Context_AgentModel::ID);
		$tpl->assign('custom_fields', $custom_fields);

		// Resolved in ONE query here so the Authentication cell can print a NAME instead of an id.
		// Never from the template: a per-row lookup there is a query per row. There are only ever a
		// handful of connected accounts, so getAll() is cheaper than collecting ids off the rows.
		if(in_array(SearchFields_AgentModel::CONNECTED_ACCOUNT_ID, $this->view_columns))
			$tpl->assign('connected_accounts', DAO_ConnectedAccount::getAll());

		// Everything a rating cell's meter needs, keyed by SearchField so the template never reaches into
		// Model_AgentModel (which isn't in Smarty's static allowlist). `levels` maps a stored tier to its
		// ordinal position so the cell doesn't have to count the scale itself.
		$rating_meters = [];
		$rating_colors = Model_AgentModel::getRatingColors();

		foreach(Model_AgentModel::getRatings() as $rating) {
			$labels = Model_AgentModel::getRatingScaleLabels($rating);
			$levels = [];
			$ordinal = 0;

			foreach(array_keys($labels) as $tier)
				$levels[$tier] = ++$ordinal;

			$rating_meters['a_rating_' . $rating] = [
				'of' => count($labels),
				'color' => $rating_colors[$rating] ?? '',
				'labels' => $labels,
				'levels' => $levels,
			];
		}

		$tpl->assign('rating_meters', $rating_meters);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/agent_model/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		switch($param->field) {
			case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
			case SearchFields_AgentModel::PROVIDER:
				$field = $param->field;
				$label_map = function($values) use ($field) {
					return SearchFields_AgentModel::getLabelsForKeyValues($field, $values);
				};
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			case SearchFields_AgentModel::HAS_THINKING:
			case SearchFields_AgentModel::HAS_VISION:
				parent::_renderCriteriaParamBoolean($param);
				break;

			case SearchFields_AgentModel::STATUS:
				$label_map = function($values) {
					return SearchFields_AgentModel::getLabelsForKeyValues(SearchFields_AgentModel::STATUS, $values);
				};
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			case SearchFields_AgentModel::RATING_COST:
			case SearchFields_AgentModel::RATING_INTELLIGENCE:
			case SearchFields_AgentModel::RATING_PRIVACY:
			case SearchFields_AgentModel::RATING_SPEED:
				$rating = substr(SearchFields_AgentModel::getFields()[$param->field]->db_column, 7);
				$label_map = function($values) use ($rating) {
					$scale = Model_AgentModel::getRatingScale($rating);
					return array_intersect_key($scale, array_flip($values));
				};
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			case SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('dao.agent_model.connected_account_id')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;

			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_AgentModel::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_AgentModel::CREATED_AT:
			case SearchFields_AgentModel::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
			case SearchFields_AgentModel::CONTEXT_WINDOW:
			case SearchFields_AgentModel::ID:
			case SearchFields_AgentModel::PRIORITY:
			case SearchFields_AgentModel::RATING_COST:
			case SearchFields_AgentModel::RATING_INTELLIGENCE:
			case SearchFields_AgentModel::RATING_PRIVACY:
			case SearchFields_AgentModel::RATING_SPEED:
			case SearchFields_AgentModel::STATUS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			// A bit column posts its value as `bool`, not `value` -- that's the payload
			// _getSubtotalCountForBooleanColumn() builds when a subtotal row is clicked.
			case SearchFields_AgentModel::HAS_THINKING:
			case SearchFields_AgentModel::HAS_VISION:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer', 1);
				$criteria = new DevblocksSearchCriteria($field, $oper, $bool);
				break;

			case SearchFields_AgentModel::API_ENDPOINT_URL:
			case SearchFields_AgentModel::ICON:
			case SearchFields_AgentModel::ICON_COLOR:
			case SearchFields_AgentModel::LABEL:
			case SearchFields_AgentModel::MODEL:
			case SearchFields_AgentModel::NAME:
			case SearchFields_AgentModel::PROVIDER:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			default:
				if(str_starts_with($field, 'cf_')) {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				} else if (str_starts_with($field, '*_')) {
					if(($virtual_criteria = $this->_doSetCriteriaVirtual($field, $_POST, $oper)))
						$criteria = $virtual_criteria;
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_AgentModel extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.agent.model';
	const URI = 'agent_model';

	static function isReadableByActor($models, $actor) {
		return CerberusContexts::allowEverything($models);
	}

	static function isWriteableByActor($models, $actor) {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}

	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}

	function getRandom() {
		return DAO_AgentModel::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=agent_model&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_AgentModel();

		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => ['context' => self::ID],
		];

		$properties['connected_account_id'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.connected_account_id')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->connected_account_id,
			'params' => ['context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT],
		];

		$properties['context_window'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.context_window')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->context_window,
		];

		$properties['priority'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.priority'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->priority,
		];

		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];

		$properties['has_thinking'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.has_thinking')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->has_thinking,
		];

		$properties['has_vision'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.has_vision')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->has_vision,
		];

		$properties['icon'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.icon')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->icon,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['status'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.status'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => Model_AgentModel::getStatuses()[$model->status] ?? '',
		];

		// The same meter the worklist and the model picker draw, so an axis reads identically wherever it
		// appears. `value` stays the stored tier so an unrated axis is falsy and the widget's hide-empty
		// option still governs it; the meter itself is built from `params`.
		$rating_colors = Model_AgentModel::getRatingColors();

		foreach(Model_AgentModel::getRatings() as $rating) {
			$scale = Model_AgentModel::getRatingScaleLabels($rating);
			$value = intval($model->{'rating_' . $rating});
			$tiers = array_keys($scale);

			$properties['rating_' . $rating] = [
				'label' => mb_ucfirst($translate->_('dao.agent_model.rating_' . $rating)),
				'type' => 'meter',
				'value' => $value,
				'params' => [
					'level' => $value ? (array_search($value, $tiers, true) + 1) : 0,
					'of' => count($tiers),
					'color' => $rating_colors[$rating] ?? '',
					'label' => $scale[$value] ?? 'Unrated',
				],
			];
		}

		$properties['label'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.label')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->label,
		];

		$properties['model'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.model')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->model,
		];

		$properties['params_kata'] = [
			'label' => mb_ucfirst($translate->_('common.parameters')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->params_kata,
		];

		$properties['provider'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.provider')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->provider,
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($agent_model = DAO_AgentModel::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($agent_model->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $agent_model->id,
			'name' => $agent_model->name,
			'permalink' => $url,
			'updated' => $agent_model->updated_at,
			'created' => $agent_model->created_at,
			// A model reads as its vendor everywhere it's listed
			'icon' => $agent_model->getDisplayIcon(),
			'icon_color' => $agent_model->getDisplayIconColor(),
		];
	}

	function getDefaultProperties() : array {
		return [
			'provider',
			'model',
			'connected_account_id',
			'context_window',
			'has_vision',
			'has_thinking',
			'rating_intelligence',
			'rating_privacy',
			'status',
			'updated_at',
		];
	}

	function getContext($agent_model, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Agent Model:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_AgentModel::ID);

		if(is_numeric($agent_model)) {
			$agent_model = DAO_AgentModel::get($agent_model);
		} elseif($agent_model instanceof Model_AgentModel) {
			DevblocksPlatform::noop();
		} elseif(is_array($agent_model)) {
			$agent_model = Cerb_ORMHelper::recastArrayToModel($agent_model, 'Model_AgentModel');
		} else {
			$agent_model = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'api_endpoint_url' => $prefix.$translate->_('dao.agent_model.api_endpoint_url'),
			'connected_account_id' => $prefix.$translate->_('dao.agent_model.connected_account_id'),
			'context_window' => $prefix.$translate->_('dao.agent_model.context_window'),
			'priority' => $prefix.$translate->_('common.priority'),
			'has_vision' => $prefix.$translate->_('dao.agent_model.has_vision'),
			'icon' => $prefix.$translate->_('dao.agent_model.icon'),
			'icon_color' => $prefix.$translate->_('dao.agent_model.icon_color'),
			'has_thinking' => $prefix.$translate->_('dao.agent_model.has_thinking'),
			'rating_cost' => $prefix.$translate->_('dao.agent_model.rating_cost'),
			'rating_intelligence' => $prefix.$translate->_('dao.agent_model.rating_intelligence'),
			'rating_privacy' => $prefix.$translate->_('dao.agent_model.rating_privacy'),
			'rating_speed' => $prefix.$translate->_('dao.agent_model.rating_speed'),
			'status' => $prefix.$translate->_('common.status'),
			'label' => $prefix.$translate->_('dao.agent_model.label'),
			'model' => $prefix.$translate->_('dao.agent_model.model'),
			'params_kata' => $prefix.$translate->_('common.parameters'),
			'provider' => $prefix.$translate->_('dao.agent_model.provider'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'api_endpoint_url' => Model_CustomField::TYPE_SINGLE_LINE,
			'connected_account_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'context_window' => Model_CustomField::TYPE_SINGLE_LINE,
			'priority' => Model_CustomField::TYPE_NUMBER,
			'has_vision' => Model_CustomField::TYPE_SINGLE_LINE,
			'icon' => Model_CustomField::TYPE_SINGLE_LINE,
			'icon_color' => Model_CustomField::TYPE_SINGLE_LINE,
			'has_thinking' => Model_CustomField::TYPE_SINGLE_LINE,
			'rating_cost' => Model_CustomField::TYPE_NUMBER,
			'rating_intelligence' => Model_CustomField::TYPE_NUMBER,
			'rating_privacy' => Model_CustomField::TYPE_NUMBER,
			'rating_speed' => Model_CustomField::TYPE_NUMBER,
			'status' => Model_CustomField::TYPE_SINGLE_LINE,
			'label' => Model_CustomField::TYPE_SINGLE_LINE,
			'model' => Model_CustomField::TYPE_SINGLE_LINE,
			'params_kata' => Model_CustomField::TYPE_SINGLE_LINE,
			'provider' => Model_CustomField::TYPE_SINGLE_LINE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_AgentModel::ID;
		$token_values['_type'] = 'agent_model';
		$token_values['_types'] = $token_types;

		if($agent_model) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $agent_model->name;
			// The per-record glyph the shared card/profile chrome reads via $context_ext->getIcon($dict) —
			// resolved (override, else the provider's brand), so a model reads as its VENDOR rather than as
			// the generic record-type mark. Sits where `_image_url` would; a context avatar would outrank it.
			$token_values['_icon'] = $agent_model->getDisplayIcon();
			$token_values['_icon_color'] = $agent_model->getDisplayIconColor();
			$token_values['id'] = $agent_model->id;
			$token_values['name'] = $agent_model->name;
			$token_values['created_at'] = $agent_model->created_at;
			$token_values['updated_at'] = $agent_model->updated_at;
			$token_values['api_endpoint_url'] = $agent_model->api_endpoint_url;
			$token_values['connected_account_id'] = $agent_model->connected_account_id;
			$token_values['context_window'] = $agent_model->context_window;
			$token_values['priority'] = $agent_model->priority;
			$token_values['has_vision'] = $agent_model->has_vision;
			$token_values['icon'] = $agent_model->icon;
			$token_values['icon_color'] = $agent_model->icon_color;
			$token_values['has_thinking'] = $agent_model->has_thinking;
			$token_values['rating_cost'] = $agent_model->rating_cost;
			$token_values['rating_intelligence'] = $agent_model->rating_intelligence;
			$token_values['rating_privacy'] = $agent_model->rating_privacy;
			$token_values['rating_speed'] = $agent_model->rating_speed;
			$token_values['status'] = Model_AgentModel::getStatuses()[$agent_model->status] ?? '';
			$token_values['label'] = $agent_model->label;
			$token_values['model'] = $agent_model->model;
			$token_values['params_kata'] = $agent_model->params_kata;
			$token_values['provider'] = $agent_model->provider;
			$token_values = $this->_importModelCustomFieldsAsValues($agent_model, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=agent_model&id=%d-%s", $agent_model->id, DevblocksPlatform::strToPermalink($agent_model->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'api_endpoint_url' => DAO_AgentModel::API_ENDPOINT_URL,
			'connected_account_id' => DAO_AgentModel::CONNECTED_ACCOUNT_ID,
			'context_window' => DAO_AgentModel::CONTEXT_WINDOW,
			'created_at' => DAO_AgentModel::CREATED_AT,
			'priority' => DAO_AgentModel::PRIORITY,

			'has_vision' => DAO_AgentModel::HAS_VISION,
			'icon' => DAO_AgentModel::ICON,
			'icon_color' => DAO_AgentModel::ICON_COLOR,
			'id' => DAO_AgentModel::ID,
			'has_thinking' => DAO_AgentModel::HAS_THINKING,
			'rating_cost' => DAO_AgentModel::RATING_COST,
			'rating_intelligence' => DAO_AgentModel::RATING_INTELLIGENCE,
			'rating_privacy' => DAO_AgentModel::RATING_PRIVACY,
			'rating_speed' => DAO_AgentModel::RATING_SPEED,
			'status' => DAO_AgentModel::STATUS,
			'label' => DAO_AgentModel::LABEL,
			'model' => DAO_AgentModel::MODEL,
			'name' => DAO_AgentModel::NAME,
			'params_kata' => DAO_AgentModel::PARAMS_KATA,
			'provider' => DAO_AgentModel::PROVIDER,
			'updated_at' => DAO_AgentModel::UPDATED_AT,
			'links' => '_links',
		];
	}

	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		return $keys;
	}

	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		// Accept the same names the editor shows and a query matches, so `status: unlisted` and
		// `rating_intelligence: frontier` work rather than only their stored integers.
		switch(DevblocksPlatform::strLower($key)) {
			case 'status':
				if(is_numeric($value))
					break;

				if(false === ($id = array_search(DevblocksPlatform::strLower(trim(strval($value))), Model_AgentModel::getStatuses(), true))) {
					$error = sprintf("`status` must be one of: %s", implode(', ', Model_AgentModel::getStatuses()));
					return false;
				}

				$out_fields[DAO_AgentModel::STATUS] = $id;
				break;

			case 'rating_cost':
			case 'rating_intelligence':
			case 'rating_privacy':
			case 'rating_speed':
				if(is_numeric($value))
					break;

				$rating = substr(DevblocksPlatform::strLower($key), 7);
				$scale = Model_AgentModel::getRatingScale($rating);

				if(false === ($tier = array_search(DevblocksPlatform::strLower(trim(strval($value))), $scale, true))) {
					$error = sprintf("`%s` must be one of: %s", $key, implode(', ', $scale));
					return false;
				}

				$out_fields['rating_' . $rating] = $tier;
				break;
		}
		return true;
	}

	function lazyLoadGetKeys() {
		return parent::lazyLoadGetKeys();
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = Context_AgentModel::ID;
		$context_id = $dictionary['id'];
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];

		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}

		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
				$values = array_merge($values, $defaults);
				break;
		}

		return $values;
	}

	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);

		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Agent Model';
		$view->renderSortBy = SearchFields_AgentModel::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';

		return $view;
	}

	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);

		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Agent Model';

		$params_req = [];

		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(DevblocksSearchField::VIRTUAL_CONTEXT_LINK,'in',[$context.':'.$context_id]),
			];
		}

		$view->addParamsRequired($params_req, true);
		$view->renderTemplate = 'context';
		return $view;
	}

	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_AgentModel::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_AgentModel::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}

		if(empty($context_id) || $edit) {
			// ACL
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);

			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);

				$tpl->assign('model', $model);
			}

			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);

			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);

			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);

			// Everything provider-specific in this editor comes from the provider EXTENSIONS, so a new
			// provider (or a new knob on an existing one) shows up here with no change to this form:
			// model-id suggestions + the default endpoint from the provider catalog, and the params
			// autocomplete from each provider's own getChatKataAutocomplete().
			$provider_catalog = self::_getProviderCatalog();
			$tpl->assign('providers', array_values($provider_catalog));
			$tpl->assign('providers_json', json_encode($provider_catalog));

			// Seed the auth chooser's chip
			if($model && $model->connected_account_id
				&& ($connected_account = DAO_ConnectedAccount::get($model->connected_account_id)))
				$tpl->assign('connected_account', $connected_account);

			// The glyph and hue for each axis, so the editor draws an axis the same way the worklist and the
			// model picker do. Both maps live on the model -- an axis's identity is one fact, in one place.
			$rating_scales = [];

			foreach(Model_AgentModel::getRatings() as $rating)
				$rating_scales[$rating] = Model_AgentModel::getRatingScaleLabels($rating);

			$tpl->assign('rating_scales', $rating_scales);
			$tpl->assign('rating_colors', Model_AgentModel::getRatingColors());
			$tpl->assign('rating_glyphs', Model_AgentModel::getRatingIcons());

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/agent_model/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}

	/**
	 * The per-provider editor catalog: `[provider_id => {id, label, icon, models, endpoint_default, params}]`.
	 *
	 * `params` is that provider's OWN `llm:<provider>:` autocomplete, re-keyed to the root — because the
	 * params editor's content is already scoped to one provider, so `thinking:` should suggest at the top
	 * level, not under `anthropic:`. This is how a provider extension contributes its config surface here:
	 * whatever it returns from `getChatKataAutocomplete()` shows up, including knobs added later.
	 */
	private static function _getProviderCatalog() : array {
		$llm = DevblocksPlatform::services()->llm();

		// One call gives every provider's block, keyed `<provider>:` + `<provider>:<subpath>:`
		$autocomplete = $llm->getKataProviderAutocomplete('');

		$out = [];

		foreach($llm->getAgentProviders() as $provider) {
			$prefix = $provider['id'] . ':';
			$params = [];

			foreach($autocomplete as $path => $suggestions) {
				if(!str_starts_with($path, $prefix))
					continue;

				// `anthropic:` → '' (the root), `anthropic:thinking:type:` → `thinking:type:`
				$params[substr($path, strlen($prefix))] = $suggestions;
			}

			$out[$provider['id']] = $provider + ['params' => $params];
		}

		return $out;
	}
};
