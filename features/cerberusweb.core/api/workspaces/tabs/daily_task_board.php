<?php
class WorkspaceTab_DailyTaskBoard extends Extension_WorkspaceTab {
	const ID = 'cerb.workspace.tab.daily_task_board';

	// Board columns, in render order. The board-spanning 'stash' is handled separately (not a day column).
	const COLUMNS = [
		'todo'        => 'TODO',
		'in_progress' => 'In Progress',
		'done'        => 'Done',
	];

	// Auto-seed palette for a project's accent when none is set. Mirrors
	// CerbUI.palettes.category10 (resources/js/cerb-ui/palettes.js) — keep in sync.
	const PALETTE = ['#0088e6','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b','#e377c2','#7f7f7f','#bcbd22','#17becf'];

	// Cerb tag-color names → hex, so a legacy tag-name accent still resolves to a swatch.
	const TAG_COLORS = [
		'blue' => '#0088e6', 'gray' => '#7f7f7f', 'green' => '#2ca02c',
		'orange' => '#ff7f0e', 'purple' => '#9467bd', 'red' => '#d62728',
	];

	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		// Shared board config (which projects + their accent colors + default order)
		$config = $tab->params['projects'] ?? [];
		$config_order = array_column($config, 'id');
		$config_by_id = array_column($config, null, 'id');

		// Resolve the real records; drop archived + anything this worker can't read.
		// Keyed by id, in config order.
		$live = $this->_getLiveProjects($config_order, $active_worker);

		// Overlay the worker's personal selection/order onto the shared scope.
		list($ordered_ids, $selected_set) = $this->_reconcileView($tab, $active_worker, array_keys($live));

		// PriorityPicker items (per-worker order), an id→color map for card accents, and a flat
		// projects list for the add-task editor's project select.
		$pp_items = [];
		$projects = [];
		$project_colors = $this->_projectColors($config_order, $config_by_id, $ordered_ids);

		foreach($ordered_ids as $id) {
			$color = $project_colors[$id];
			$projects[] = ['id' => $id, 'label' => $live[$id]->name, 'color' => $color];
			$pp_items[] = [
				'id'       => $id,
				'label'    => $live[$id]->name,
				'color'    => $color,
				'selected' => isset($selected_set[$id]),
			];
		}

		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		$tpl->assign('columns', self::COLUMNS);
		// HEX flags so a project name can't break out of the inline <script> JSON blobs
		$json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$tpl->assign('pp_items_json', json_encode($pp_items, $json_flags));
		$tpl->assign('projects_json', json_encode($projects, $json_flags));
		$tpl->assign('project_colors', $project_colors);
		$tpl->assign('is_writeable', Context_WorkspacePage::isWriteableByActor($page, $active_worker));

		// Board rows: today (todo/in_progress/done, all derived) + a 6-day Done log behind it, newest first.
		$today = strtotime('today');
		$from = strtotime('-6 days', $today);
		$boards = $this->_getBoards(array_keys($live), $from, $today);

		// Board-spanning stash (waiting tasks). Grouped until/indefinite (revived tasks leave as open→today).
		$stash = $this->_getStash(array_keys($live));

		// Overlay each card's comment count so the card can show a count pill. The pill is wired end-to-end
		// (template + _attachCommentCounts) but the COUNT query is disabled for now — skip the per-render
		// scan for performance.
		// [TODO] Prefer a denormalized comment_count on the task (a record.changed listener +1/-1 via the
		// queue) over this query — then it's free per render AND lets the planned board polling (tasks in the
		// project changed since last poll, via comment writes bumping updated_at) refresh just the moved cards.
		// Re-enable the live query by restoring: $counts = $this->_getCommentCounts($this->_collectTaskIds($boards, $stash));
		$counts = [];
		$this->_attachCommentCounts($boards, $stash, $counts);

		$tpl->assign('boards', $boards);
		$tpl->assign('stash_until', $stash['until']);
		$tpl->assign('stash_indefinite', $stash['indefinite']);
		$tpl->assign('stash_count', count($stash['until']) + count($stash['indefinite']));

		// Card meta-strip vars (assignee avatars / claim affordance) + the per-worker focus pref.
		$this->_assignCardMetaVars($tpl, $live, $active_worker, $this->_collectOwnerIds($boards, $stash));
		$view_pref = DAO_WorkerPref::getAsJson($active_worker->id, $this->_prefKey($tab));
		$tpl->assign('focus_mine', is_array($view_pref) && !empty($view_pref['focus_mine']));

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/daily_task_board/tab.tpl');
	}

	// ── AJAX (c=pages&a=invokeTab&tab_id={id}&action={action}) ────────────────
	// NOTE: the invokeTab router only enforces page-readable ACL, so write actions
	// must re-check isWriteableByActor themselves.

	public function invoke(string $action, Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$active_worker = CerberusApplication::getActiveWorker();

		switch($action) {
			case 'renderConfig':
				return $this->_invokeRenderConfig($page, $tab, $active_worker);
			case 'saveConfig':
				return $this->_invokeSaveConfig($page, $tab, $active_worker);
			case 'saveView':
				return $this->_invokeSaveView($tab, $active_worker);
			case 'saveFocus':
				return $this->_invokeSaveFocus($tab, $active_worker);
			case 'monthData':
				return $this->_invokeMonthData($tab, $active_worker);
			case 'renderDay':
				return $this->_invokeRenderDay($tab, $active_worker);
			case 'addTask':
				return $this->_invokeAddTask($tab, $active_worker);
			case 'updateTaskTitle':
				return $this->_invokeUpdateTaskTitle($active_worker);
			case 'moveTask':
				return $this->_invokeMoveTask($active_worker);
			case 'deleteTask':
				return $this->_invokeDeleteTask($active_worker);
			case 'setProject':
				return $this->_invokeSetProject($active_worker);
			case 'bulkUpdate':
				return $this->_invokeBulkUpdate($active_worker);
			case 'adjustImportance':
				return $this->_invokeAdjustImportance($active_worker);
			case 'assignTask':
				return $this->_invokeAssignTask($active_worker);
			case 'stashSetReopen':
				return $this->_invokeStashSetReopen($active_worker);
		}

		return false;
	}

	// The board's only persistent config is the tab's params (the shared project set + accent colors +
	// order). Tasks are real records (not tab config) and the per-worker view/order lives in worker prefs,
	// so neither is exported. The page/tab importer applies name/params/options_kata generically.
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = [
			'tab' => [
				'uid' => 'workspace_tab_' . $tab->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_TAB,
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'pos' => $tab->pos,
				'params' => $tab->params,
			],
		];

		if($tab->options_kata)
			$json['tab']['options_kata'] = $tab->options_kata;

		return json_encode($json);
	}

	// Params (incl. the projects config) are applied by the generic tab importer; there are no
	// extension-owned child records to recreate, so just acknowledge success. Referenced project ids that
	// don't resolve on the target system are dropped gracefully at render (see _getLiveProjects).
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		return !empty($tab->id) && is_array($json);
	}

	// Render the shared board-config dialog (project set + colors + order).
	private function _invokeRenderConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab, $active_worker) {
		if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		$tpl = DevblocksPlatform::services()->template();

		$config = $tab->params['projects'] ?? [];
		$config_order = array_column($config, 'id');
		$config_by_id = array_column($config, null, 'id');

		// Only show projects that still exist + are readable (config order). Closed
		// projects stay listed here so an editor can see/remove them.
		$models = DAO_TaskProject::getIds(
			array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($config_order, 'int'))))
		);
		$readable = $models ? Context_TaskProject::isReadableByActor($models, $active_worker) : [];

		// Resolve each owner's label once (same disambiguator as the project chooser autocomplete).
		$owner_labels = [];
		foreach($models as $model) {
			$key = $model->owner_context . ':' . $model->owner_context_id;
			if(!array_key_exists($key, $owner_labels)) {
				$labels = $values = [];
				CerberusContexts::getContext($model->owner_context, $model->owner_context_id, $labels, $values, null, true, true);
				$owner_labels[$key] = $values['_label'] ?? '';
			}
		}

		$rows = [];
		foreach($config_order as $id) {
			if(!isset($models[$id]) || empty($readable[$id]))
				continue;
			$seed = array_search($id, $config_order, true);
			$m = $models[$id];
			$rows[] = [
				'id'    => $id,
				'label' => $m->name,
				'color' => $this->_sanitizeColor($config_by_id[$id]['color'] ?? '', is_int($seed) ? $seed : 0),
				'owner' => $owner_labels["{$m->owner_context}:{$m->owner_context_id}"] ?? '',
			];
		}

		$tpl->assign('workspace_tab', $tab);
		$tpl->assign('rows', $rows);
		$tpl->assign('palette', self::PALETTE);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/daily_task_board/config.tpl');

		return true;
	}

	// Persist the shared board config (project set + colors + order) on the tab.
	private function _invokeSaveConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab, $active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		// Parallel arrays, preserving the submitted (drag) order.
		$project_ids = DevblocksPlatform::importGPC($_POST['project_ids'] ?? [], 'array', []);
		$colors = DevblocksPlatform::importGPC($_POST['colors'] ?? [], 'array', []);

		$project_ids = DevblocksPlatform::sanitizeArray($project_ids, 'int');
		$colors = DevblocksPlatform::sanitizeArray($colors, 'string');

		// Resolve once for validation: keep only readable, non-archived projects.
		$unique_ids = array_values(array_unique(array_filter($project_ids)));
		$models = $unique_ids ? DAO_TaskProject::getIds($unique_ids) : [];
		$readable = $models ? Context_TaskProject::isReadableByActor($models, $active_worker) : [];

		$projects = [];
		$seen = [];
		foreach($project_ids as $i => $id) {
			if($id <= 0 || isset($seen[$id]))
				continue;
			if(!isset($models[$id]) || empty($readable[$id]) || !empty($models[$id]->is_closed))
				continue;
			$seen[$id] = true;
			$projects[] = [
				'id'    => $id,
				'color' => $this->_sanitizeColor($colors[$i] ?? '', count($projects)),
			];
		}

		$params = is_array($tab->params) ? $tab->params : [];
		$params['projects'] = $projects;

		DAO_WorkspaceTab::update($tab->id, [
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		]);

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// Persist the per-worker view state (which projects are on + their priority order).
	private function _invokeSaveView(Model_WorkspaceTab $tab, $active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$selected = DevblocksPlatform::importGPC($_POST['selected'] ?? [], 'array', []);
		$order = DevblocksPlatform::importGPC($_POST['order'] ?? [], 'array', []);

		$selected = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($selected, 'int'))));
		$order = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($order, 'int'))));
		$focus_mine = DevblocksPlatform::importGPC($_POST['focus_mine'] ?? 0, 'integer', 0);

		DAO_WorkerPref::setAsJson($active_worker->id, $this->_prefKey($tab), [
			'selected'   => $selected,
			'order'      => $order,
			'focus_mine' => $focus_mine ? 1 : 0,
		]);

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// Persist just the "focus my tasks" toggle — read-modify-write so it doesn't clobber the saved
	// project selection/order.
	private function _invokeSaveFocus(Model_WorkspaceTab $tab, $active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$focus_mine = DevblocksPlatform::importGPC($_POST['focus_mine'] ?? 0, 'integer', 0);

		$pref = DAO_WorkerPref::getAsJson($active_worker->id, $this->_prefKey($tab));
		if(!is_array($pref))
			$pref = [];
		$pref['focus_mine'] = $focus_mine ? 1 : 0;

		DAO_WorkerPref::setAsJson($active_worker->id, $this->_prefKey($tab), $pref);

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// Quick Triage: nudge a task's importance by a delta (±1, or ±5 with Shift), clamped 0–100.
	private function _invokeAdjustImportance($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_id = DevblocksPlatform::importGPC($_POST['task_id'] ?? 0, 'integer', 0);
		$delta = DevblocksPlatform::importGPC($_POST['delta'] ?? 0, 'integer', 0);

		if(!($task = DAO_Task::get($task_id)))
			DevblocksPlatform::dieWithHttpError(null, 400);

		if(!Context_Task::isWriteableByActor($task, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		$importance = DevblocksPlatform::intClamp(intval($task->importance) + $delta, 0, 100);
		DAO_Task::update($task_id, [DAO_Task::IMPORTANCE => $importance]);

		echo json_encode(['status' => 'ok', 'importance' => $importance]);
		return true;
	}

	// Multi-select column "Actions": move the selected tasks to another column (status change) or to
	// another project. Each task is write-gated independently; failures are skipped. (Later this becomes
	// automation-configurable.)
	private function _invokeBulkUpdate($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_ids = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray(
			DevblocksPlatform::importGPC($_POST['task_ids'] ?? [], 'array', []), 'int'
		))));
		$to_column = DevblocksPlatform::importGPC($_POST['to_column'] ?? '', 'string', '');
		$project_id = DevblocksPlatform::importGPC($_POST['project_id'] ?? 0, 'integer', 0);

		if(!$task_ids)
			DevblocksPlatform::dieWithHttpError(null, 400);

		// Exactly one operation: a column status change OR a project reassignment.
		$do_column = strlen($to_column) && $this->_isValidColumn($to_column);
		$do_project = !$do_column && $project_id > 0;

		if(!$do_column && !$do_project)
			DevblocksPlatform::dieWithHttpError(null, 400);

		// A project move must target a project the worker can read (the task inherits its ACL).
		if($do_project) {
			$models = DAO_TaskProject::getIds([$project_id]);
			if(empty($models[$project_id]))
				DevblocksPlatform::dieWithHttpError(null, 400);
			$readable = Context_TaskProject::isReadableByActor($models, $active_worker);
			if(empty($readable[$project_id]))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}

		$fields = $do_column
			? $this->_statusFieldsForColumn($to_column)
			: [DAO_Task::PROJECT_ID => $project_id];

		$tasks = DAO_Task::getIds($task_ids);
		foreach($tasks as $task) {
			if(!Context_Task::isWriteableByActor($task, $active_worker))
				continue; // skip tasks this worker can't write
			DAO_Task::update($task->id, $fields);
		}

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// "Assign to me" — claim an unassigned task from the card's meta-strip. Write-gated (the task's ACL
	// derives from its project), and only assigns when currently unassigned (a no-op re-claim guard).
	private function _invokeAssignTask($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_id = DevblocksPlatform::importGPC($_POST['task_id'] ?? 0, 'integer', 0);

		if(!($task = DAO_Task::get($task_id)))
			DevblocksPlatform::dieWithHttpError(null, 400);

		if(!Context_Task::isWriteableByActor($task, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(empty($task->owner_id))
			DAO_Task::update($task_id, [DAO_Task::OWNER_ID => $active_worker->id]);

		echo json_encode([
			'status'     => 'ok',
			'owner_id'   => $active_worker->id,
			'owner_name' => $active_worker->getName(),
		]);
		return true;
	}

	// "Jump to Date" pip data for one displayed month: which days carry done (completed) or stash
	// (waiting-until) tasks for this worker's live projects. completed_date/reopen_at are indexed, so
	// these stay light raw reads — one DISTINCT per type, binned client-side-friendly by day-of-month.
	private function _invokeMonthData(Model_WorkspaceTab $tab, $active_worker) {
		$year = DevblocksPlatform::importGPC($_POST['year'] ?? 0, 'integer', 0);
		$month = DevblocksPlatform::importGPC($_POST['month'] ?? 0, 'integer', 0);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$config_order = array_column($tab->params['projects'] ?? [], 'id');
		$live_ids = array_keys($this->_getLiveProjects($config_order, $active_worker));

		if($year < 1970 || $month < 1 || $month > 12 || !$live_ids) {
			echo json_encode(['status' => 'ok', 'done' => [], 'stash' => []]);
			return true;
		}

		$from = mktime(0, 0, 0, $month, 1, $year);
		$to = strtotime('+1 month', $from) - 1;

		$db = DevblocksPlatform::services()->database();
		$ids_list = implode(',', $live_ids); // sanitized ints (from _getLiveProjects)

		$done = $stash = [];

		$rows = $db->GetArrayReader(sprintf(
			"SELECT DISTINCT completed_date FROM task WHERE project_id IN (%s) AND status_id = 1 AND completed_date BETWEEN %d AND %d",
			$ids_list, $from, $to
		));
		foreach($rows as $row)
			$done[(int) date('j', $row['completed_date'])] = true;

		$rows = $db->GetArrayReader(sprintf(
			"SELECT DISTINCT reopen_at FROM task WHERE project_id IN (%s) AND status_id = 2 AND reopen_at BETWEEN %d AND %d",
			$ids_list, $from, $to
		));
		foreach($rows as $row)
			$stash[(int) date('j', $row['reopen_at'])] = true;

		echo json_encode([
			'status' => 'ok',
			'done'   => array_keys($done),
			'stash'  => array_keys($stash),
		]);
		return true;
	}

	// Render a single off-window day for "Jump to Date" — the client inserts it into the timeline.
	// A prior day is a read-only Done log; a future day is an interactive stash-until column. Today is
	// already in the timeline, so it's handled client-side (scroll), never rendered here.
	private function _invokeRenderDay(Model_WorkspaceTab $tab, $active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$year = DevblocksPlatform::importGPC($_POST['year'] ?? 0, 'integer', 0);
		$month = DevblocksPlatform::importGPC($_POST['month'] ?? 0, 'integer', 0);
		$day = DevblocksPlatform::importGPC($_POST['day'] ?? 0, 'integer', 0);

		if($year < 1970 || $month < 1 || $month > 12 || $day < 1 || $day > 31)
			DevblocksPlatform::dieWithHttpError(null, 400);

		$day_ts = strtotime('today', mktime(0, 0, 0, $month, $day, $year));
		$today = strtotime('today');

		// Today already exists in the timeline — the client scrolls to it.
		if($day_ts == $today)
			DevblocksPlatform::dieWithHttpError(null, 400);

		$config = $tab->params['projects'] ?? [];
		$config_order = array_column($config, 'id');
		$config_by_id = array_column($config, null, 'id');

		$live = $this->_getLiveProjects($config_order, $active_worker);
		$project_colors = $this->_projectColors($config_order, $config_by_id, array_keys($live));

		// Future = stash-until-that-day column; past = the Done log via the normal board builder.
		if($day_ts > $today) {
			$board = $this->_getStashDayBoard(array_keys($live), $day_ts);
		} else {
			$boards = $this->_getBoards(array_keys($live), $day_ts, $day_ts);
			$board = $boards[0] ?? null;
		}

		if(!$board)
			DevblocksPlatform::dieWithHttpError(null, 400);

		// Comment count pills for the inserted day's cards (same batched query as the main render).
		// [TODO] Disabled for now (see renderTab); attach zeros so the meta strip stays empty/hidden. Re-enable
		// with: ...$this->_getCommentCounts($this->_collectTaskIds($day_boards)).
		$day_boards = [$board];
		$no_stash = [];
		$this->_attachCommentCounts($day_boards, $no_stash, []);
		$board = $day_boards[0];

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('workspace_tab', $tab);
		$tpl->assign('board', $board);
		$tpl->assign('columns', self::COLUMNS);
		$tpl->assign('project_colors', $project_colors);
		$tpl->assign('stash_count', 0);
		// Card meta-strip vars (assignee avatars / claim) for the inserted day's cards.
		$this->_assignCardMetaVars($tpl, $live, $active_worker, $this->_collectOwnerIds([$board]));

		$html = $tpl->fetch('devblocks:cerberusweb.core::internal/workspaces/tabs/daily_task_board/_day.tpl');

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		echo json_encode(['status' => 'ok', 'html' => $html, 'date_ymd' => $board['date_ymd']]);
		return true;
	}

	// A future day's board: a single interactive "stash_day" column of tasks stashed-until that civil
	// day (waiting, reopen_at within [day, day+1)). It's a drop target + supports quick-add; dropping or
	// adding here sets reopen_at to the day (see _statusFieldsForColumn('stash_day', $day)).
	private function _getStashDayBoard(array $live_ids, $day_ts) {
		$live_ids = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($live_ids, 'int'))));

		$cards = [];
		if($live_ids) {
			$from = $day_ts;
			$to = strtotime('+1 day', $day_ts) - 1;
			$tasks = DAO_Task::getWhere(sprintf("%s IN (%s) AND %s = 2 AND %s BETWEEN %d AND %d",
				DAO_Task::PROJECT_ID, implode(',', $live_ids), DAO_Task::STATUS_ID, DAO_Task::REOPEN_AT, $from, $to));
			DevblocksPlatform::sortObjects($tasks, 'reopen_at', false); // earliest wake time first
			foreach($tasks as $task)
				$cards[] = ['id' => $task->id, 'project_id' => $task->project_id, 'text' => $task->title, 'owner_id' => $task->owner_id, 'importance' => intval($task->importance)];
		}

		return [
			'date_key'  => $day_ts,
			'date_ymd'  => date('Y-m-d', $day_ts),
			'label'     => date('F jS, Y — l', $day_ts),
			'is_today'  => false,
			'is_future' => true,
			'columns'   => ['stash_day' => $cards],
		];
	}

	// Create a task on the board. The lane (column) implies status/is_active; placement on a day is derived,
	// so we never store one. Adds land on today's active columns or the stash (see template).
	private function _invokeAddTask(Model_WorkspaceTab $tab, $active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$title = trim(DevblocksPlatform::importGPC($_POST['title'] ?? '', 'string', ''));
		$project_id = DevblocksPlatform::importGPC($_POST['project_id'] ?? 0, 'integer', 0);
		$column = DevblocksPlatform::importGPC($_POST['column'] ?? '', 'string', '');
		// Lane day-key — only meaningful for a future stash_day add (the reopen-until date).
		$to_date = DevblocksPlatform::importGPC($_POST['to_date'] ?? 0, 'integer', 0);
		// Assignee from the editor's Me/Unassigned switcher: 'me' (default) → self; 'unassigned' → 0.
		$assign = DevblocksPlatform::importGPC($_POST['assign'] ?? 'me', 'string', 'me');
		// Existing lane card ids in order (without the new task) + where to insert the new one.
		$order = array_values(array_filter(DevblocksPlatform::sanitizeArray(
			DevblocksPlatform::importGPC($_POST['order'] ?? [], 'array', []), 'int'
		)));
		$index = DevblocksPlatform::importGPC($_POST['index'] ?? 0, 'integer', 0);

		if(!strlen($title) || !$this->_isValidColumn($column))
			DevblocksPlatform::dieWithHttpError(null, 400);

		// The project must be configured on this board + readable by the worker.
		$config_ids = array_column($tab->params['projects'] ?? [], 'id');
		$live = $this->_getLiveProjects($config_ids, $active_worker);
		if(!isset($live[$project_id]))
			DevblocksPlatform::dieWithHttpError(null, 403);

		$fields = [
			DAO_Task::TITLE => $title,
			DAO_Task::OWNER_ID => ($assign === 'unassigned') ? 0 : $active_worker->id,
			DAO_Task::PROJECT_ID => $project_id,
		] + $this->_statusFieldsForColumn($column, $to_date);

		$id = DAO_Task::create($fields);

		// Only TODO is importance-ordered (the shared/PM day-sort); In-Progress order is ephemeral, so a
		// new In-Progress task just keeps its default importance.
		if($column === 'todo') {
			$index = max(0, min($index, count($order)));
			array_splice($order, $index, 0, [$id]);
			$this->_setImportanceFromOrder($id, $order);
		}

		echo json_encode(['status' => 'ok', 'id' => $id, 'title' => $title]);
		return true;
	}

	// Inline title edit — saves a card's text back to the task title.
	private function _invokeUpdateTaskTitle($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_id = DevblocksPlatform::importGPC($_POST['task_id'] ?? 0, 'integer', 0);
		$title = trim(DevblocksPlatform::importGPC($_POST['title'] ?? '', 'string', ''));

		if(!strlen($title) || !($task = DAO_Task::get($task_id)))
			DevblocksPlatform::dieWithHttpError(null, 400);

		if(!Context_Task::isWriteableByActor($task, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		DAO_Task::update($task_id, [DAO_Task::TITLE => $title]);

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// Drag persistence — the dest lane (column) sets status/is_active/completed_date; active lanes also
	// re-rank via importance. `to_date` is the lane's day, used only to date a done card (incl. prior days).
	private function _invokeMoveTask($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_id = DevblocksPlatform::importGPC($_POST['task_id'] ?? 0, 'integer', 0);
		$to_date = DevblocksPlatform::importGPC($_POST['to_date'] ?? 0, 'integer', 0);
		$to_column = DevblocksPlatform::importGPC($_POST['to_column'] ?? '', 'string', '');
		$order = array_values(array_filter(DevblocksPlatform::sanitizeArray(
			DevblocksPlatform::importGPC($_POST['order'] ?? [], 'array', []), 'int'
		)));

		if(!$this->_isValidColumn($to_column) || !($task = DAO_Task::get($task_id)))
			DevblocksPlatform::dieWithHttpError(null, 400);

		if(!Context_Task::isWriteableByActor($task, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		DAO_Task::update($task_id, $this->_statusFieldsForColumn($to_column, $to_date));

		// Only TODO persists a manual order (importance) — the shared/PM day-sort. In-Progress is
		// ephemeral and Done/stash derive their own order, so they send no `order` and skip the rerank.
		// An empty `order` (focus mode = personal view) also signals "don't rewrite the shared importance".
		if($to_column === 'todo' && $order)
			$this->_setImportanceFromOrder($task_id, $order);

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// Move a task to another project (the board ⋮ menu's Move submenu). The task's ACL then follows the new
	// project; column/day derivation is unaffected.
	private function _invokeSetProject($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_id = DevblocksPlatform::importGPC($_POST['task_id'] ?? 0, 'integer', 0);
		$project_id = DevblocksPlatform::importGPC($_POST['project_id'] ?? 0, 'integer', 0);

		if(!($task = DAO_Task::get($task_id)))
			DevblocksPlatform::dieWithHttpError(null, 400);

		if(!Context_Task::isWriteableByActor($task, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		// The target project must exist + be readable by the worker (the task inherits its ACL).
		if($project_id > 0) {
			$models = DAO_TaskProject::getIds([$project_id]);
			if(empty($models[$project_id]))
				DevblocksPlatform::dieWithHttpError(null, 400);
			$readable = Context_TaskProject::isReadableByActor($models, $active_worker);
			if(empty($readable[$project_id]))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}

		DAO_Task::update($task_id, [DAO_Task::PROJECT_ID => $project_id]);

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// Delete a task from the board's ⋮ menu (the client confirms first).
	private function _invokeDeleteTask($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_id = DevblocksPlatform::importGPC($_POST['task_id'] ?? 0, 'integer', 0);

		if(!($task = DAO_Task::get($task_id)))
			DevblocksPlatform::dieWithHttpError(null, 400);

		if(!Context_Task::isDeletableByActor($task, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		DAO_Task::delete([$task_id]);

		echo json_encode(['status' => 'ok']);
		return true;
	}

	// Set a "stashed until" wake date on a just-stashed task (freeform date string). Status stays waiting;
	// the heartbeat revives it (waiting→open) once reopen_at passes. Empty string = indefinite.
	private function _invokeStashSetReopen($active_worker) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$task_id = DevblocksPlatform::importGPC($_POST['task_id'] ?? 0, 'integer', 0);
		$reopen_raw = trim(DevblocksPlatform::importGPC($_POST['reopen_at'] ?? '', 'string', ''));

		if(!($task = DAO_Task::get($task_id)))
			DevblocksPlatform::dieWithHttpError(null, 400);

		if(!Context_Task::isWriteableByActor($task, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		// Cerb's freeform parser ("+2h", "next friday 1pm", "5pm America/New_York"), not raw strtotime.
		$reopen_at = strlen($reopen_raw) ? max(0, intval(DevblocksPlatform::services()->date()->parseDateString($reopen_raw))) : 0;

		DAO_Task::update($task_id, [
			DAO_Task::STATUS_ID => 2,
			DAO_Task::REOPEN_AT => $reopen_at,
			DAO_Task::COMPLETED_DATE => 0,
		]);

		// Return the formatted display strings too, so the client refreshes the one card in place (pill
		// label + data-reopen) instead of reloading the whole board.
		$response = ['status' => 'ok', 'reopen_at' => $reopen_at];
		if($reopen_at > 0) {
			$response['when'] = $this->_formatWakeAt($reopen_at, time());
			$response['reopen_str'] = DevblocksPlatform::services()->date()->formatTime('M j, Y g:ia', $reopen_at);
		}
		echo json_encode($response);
		return true;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	// The column IS the status now (placement derives from these fields — nothing board-specific is stored):
	//   done        = closed + completed_date (today's done = now; a prior-day drop = that day, so it logs there)
	//   stash       = waiting + indefinite (a wake date is set later from the card; the heartbeat revives it)
	//   in_progress = open + is_active   (the "doing now" lane)
	//   todo        = open + not-active
	// $day is the dest lane's day-key, used only to date a done card.
	private function _statusFieldsForColumn($column, $day = 0) {
		if($column === 'done') {
			$completed = ($day > 0 && $day < strtotime('today')) ? ($day + 43200) : time(); // prior day @ noon, else now
			return [DAO_Task::STATUS_ID => 1, DAO_Task::IS_ACTIVE => 0, DAO_Task::COMPLETED_DATE => $completed, DAO_Task::REOPEN_AT => 0];
		}

		if($column === 'stash')
			return [DAO_Task::STATUS_ID => 2, DAO_Task::IS_ACTIVE => 0, DAO_Task::COMPLETED_DATE => 0, DAO_Task::REOPEN_AT => 0];

		// A future-day stash column: waiting, reopen until that day's midnight (the heartbeat revives it then).
		if($column === 'stash_day')
			return [DAO_Task::STATUS_ID => 2, DAO_Task::IS_ACTIVE => 0, DAO_Task::COMPLETED_DATE => 0, DAO_Task::REOPEN_AT => max(0, intval($day))];

		return [
			DAO_Task::STATUS_ID => 0,
			DAO_Task::IS_ACTIVE => ($column === 'in_progress') ? 1 : 0,
			DAO_Task::COMPLETED_DATE => 0,
			DAO_Task::REOPEN_AT => 0,
		];
	}

	// Valid drop targets: the three day columns plus the board-spanning stash (which is not in
	// self::COLUMNS — day rows stay 3-column and stash is fetched/rendered separately).
	private function _isValidColumn($column) {
		return array_key_exists($column, self::COLUMNS) || $column === 'stash' || $column === 'stash_day';
	}

	// Rank a card within an active lane by writing its `importance` (the lane sorts importance DESC).
	// Sets ONLY the moved card to the midpoint of its new neighbors' importance — no full-lane rerank —
	// falling back to an even re-space of that one small lane when the neighbors leave no integer gap.
	private function _setImportanceFromOrder($task_id, array $ordered_ids) {
		$ordered_ids = array_values(array_filter(DevblocksPlatform::sanitizeArray($ordered_ids, 'int')));
		$pos = array_search(intval($task_id), $ordered_ids, true);
		if($pos === false)
			return;

		$models = DAO_Task::getIds($ordered_ids);
		$imp = function($i) use ($ordered_ids, $models) {
			$id = $ordered_ids[$i] ?? null;
			return ($id !== null && isset($models[$id])) ? intval($models[$id]->importance) : null;
		};

		$above = ($pos > 0) ? $imp($pos - 1) : 100;                       // ceiling above the top slot
		$below = ($pos < count($ordered_ids) - 1) ? $imp($pos + 1) : 0;   // floor below the bottom slot
		$above = is_null($above) ? 100 : $above;
		$below = is_null($below) ? 0 : $below;

		if($above - $below >= 2) {
			$value = intval(floor(($above + $below) / 2));
			DAO_Task::update($task_id, [DAO_Task::IMPORTANCE => DevblocksPlatform::intClamp($value, 0, 100)], false);
			return;
		}

		// No gap between neighbors → evenly re-space this (small) lane, high on top.
		$n = count($ordered_ids);
		foreach($ordered_ids as $i => $id) {
			$value = ($n <= 1) ? 50 : intval(round(90 - ($i * (80 / ($n - 1))))); // 90..10 top→bottom
			DAO_Task::update($id, [DAO_Task::IMPORTANCE => DevblocksPlatform::intClamp($value, 0, 100)], false);
		}
	}

	// Fetch the board-spanning stash (waiting tasks, status_id=2) for these projects, grouped for the
	// template. A revived task (heartbeat → status_id=0) is no longer waiting, so it leaves the stash and
	// renders in today's TODO — there's no "ready" group here.
	//   until      = a wake date set (reopen_at > 0), nearest-first, with a `when` label
	//   indefinite = no wake date (reopen_at = 0)
	private function _getStash(array $live_ids) {
		$live_ids = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($live_ids, 'int'))));
		$out = ['until' => [], 'indefinite' => []];
		if(!$live_ids)
			return $out;

		$where = sprintf("%s IN (%s) AND %s = 2",
			DAO_Task::PROJECT_ID, implode(',', $live_ids), DAO_Task::STATUS_ID
		);
		$tasks = DAO_Task::getWhere($where);

		$now = time();
		foreach($tasks as $task) {
			$card = [
				'id'         => $task->id,
				'project_id' => $task->project_id,
				'text'       => $task->title,
				'owner_id'   => $task->owner_id,
				'importance' => intval($task->importance),
			];

			if($task->reopen_at > 0) { // stashed until a date
				$card['when'] = $this->_formatWakeAt($task->reopen_at, $now);
				// Absolute, re-parseable form so the inline editor can prefill the current value.
				$card['reopen_str'] = DevblocksPlatform::services()->date()->formatTime('M j, Y g:ia', $task->reopen_at);
				$out['until'][] = $card + ['reopen_at' => $task->reopen_at];
			} else { // stashed indefinitely
				$out['indefinite'][] = $card + ['updated_date' => $task->updated_date];
			}
		}

		// Until by nearest wake date first. Indefinite by importance DESC (100 on top, 0 bottom) — that's
		// what Quick Triage adjusts; ties fall back to most-recently-stashed. (The client still drops a
		// freshly-stashed card to the top for usability until the next reload re-sorts by importance.)
		usort($out['until'], fn($a, $b) => $a['reopen_at'] <=> $b['reopen_at']);
		usort($out['indefinite'], fn($a, $b) => ($b['importance'] <=> $a['importance']) ?: ($b['updated_date'] <=> $a['updated_date']));

		return $out;
	}

	// Wake-time label: "now" once it's due (past, pre-heartbeat), absolute date when >24h away, else the
	// largest-unit relative ("5h"/"20m"/"30s").
	private function _formatWakeAt($reopen_at, $now) {
		$delta = intval($reopen_at) - intval($now);

		if($delta <= 0)
			return 'now';
		if($delta > 86400)
			return DevblocksPlatform::services()->date()->formatTime('M j, Y', $reopen_at);

		if($delta >= 3600)
			return intval($delta / 3600) . 'h';
		if($delta >= 60)
			return intval($delta / 60) . 'm';
		return $delta . 's';
	}

	// Per-worker pref key — embeds the tab id (worker prefs are global per worker).
	private function _prefKey(Model_WorkspaceTab $tab) {
		return sprintf('daily_task_board.view.%d', $tab->id);
	}

	/**
	 * Resolve configured project ids to readable, non-archived models in config order.
	 * @return Model_TaskProject[] keyed by id
	 */
	private function _getLiveProjects(array $ids, $actor) {
		$ids = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($ids, 'int'))));
		if(!$ids)
			return [];

		$models = DAO_TaskProject::getIds($ids);

		// Archived projects are hidden from the board.
		$models = array_filter($models, function($m) { return empty($m->is_closed); });
		if(!$models)
			return [];

		// ACL: keep only projects this worker can read.
		$readable = Context_TaskProject::isReadableByActor($models, $actor);

		$live = [];
		foreach($ids as $id) { // preserve config order
			if(isset($models[$id]) && !empty($readable[$id]))
				$live[$id] = $models[$id];
		}

		return $live;
	}

	/**
	 * Overlay the worker's saved selection/order onto the live (shared) project set.
	 * New live projects are appended at the bottom, toggled OFF — except on a worker's
	 * first visit (no saved pref), when every live project starts ON.
	 * @return array{0:int[],1:array<int,bool>} [ordered_ids, selected_set]
	 */
	private function _reconcileView(Model_WorkspaceTab $tab, $worker, array $live_ids) {
		// NB: getAsJson json_decodes its value — its default must be a string/null, never an array.
		$pref = DAO_WorkerPref::getAsJson($worker->id, $this->_prefKey($tab));
		$has_pref = is_array($pref) && (isset($pref['selected']) || isset($pref['order']));
		$live_set = array_flip($live_ids);

		// Order: worker's order ∩ live, then append any new live ids (config order) at the bottom.
		$ordered = [];
		foreach(array_map('intval', $pref['order'] ?? []) as $id) {
			if(isset($live_set[$id]) && !in_array($id, $ordered, true))
				$ordered[] = $id;
		}
		foreach($live_ids as $id) {
			if(!in_array($id, $ordered, true))
				$ordered[] = $id;
		}

		// Selection: saved selection ∩ live (new ids stay OFF). An EMPTY result — first visit, or a
		// stale/emptied pref — falls back to all live ON so the board is never inexplicably blank.
		$selected_set = [];
		if($has_pref) {
			foreach(array_map('intval', $pref['selected'] ?? []) as $id) {
				if(isset($live_set[$id]))
					$selected_set[$id] = true;
			}
		}
		if(!$selected_set)
			$selected_set = array_fill_keys($live_ids, true);

		return [$ordered, $selected_set];
	}

	// Normalize a stored/submitted accent to a concrete hex; auto-seed by index when absent/invalid.
	private function _sanitizeColor($value, $seed_index = 0) {
		$value = is_string($value) ? strtolower(trim($value)) : '';

		if(isset(self::TAG_COLORS[$value]))
			return self::TAG_COLORS[$value];

		if(preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $value))
			return $value;

		return self::PALETTE[$seed_index % count(self::PALETTE)];
	}

	// Build an id→hex accent map for the given project ids (seeded by config position when unset), so
	// the main render and the per-day "Jump to Date" render share identical colors.
	private function _projectColors(array $config_order, array $config_by_id, array $ids) {
		$colors = [];
		foreach($ids as $id) {
			$seed = array_search($id, $config_order, true);
			$colors[$id] = $this->_sanitizeColor($config_by_id[$id]['color'] ?? '', is_int($seed) ? $seed : 0);
		}
		return $colors;
	}

	// ── Card meta-strip (assignee avatar / "assign to me") ──────────────────────

	// Assign the template vars the card meta-strip needs: which projects the worker can write (gates the
	// "assign to me" claim), and a per-owner {name, avatar} map (incl. the active worker, for new/claimed
	// cards). Shared by renderTab + the per-day "Jump to Date" render.
	private function _assignCardMetaVars($tpl, array $live, $active_worker, array $owner_ids) {
		$writeable = $live ? Context_TaskProject::isWriteableByActor($live, $active_worker) : [];
		$writeable_map = [];
		foreach((array) $writeable as $pid => $ok)
			if($ok) $writeable_map[$pid] = true;

		$worker_meta = $this->_workerMeta($owner_ids, $active_worker);
		$json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

		$tpl->assign('writeable_project_ids', $writeable_map);
		$tpl->assign('writeable_project_ids_json', json_encode($writeable_map, $json_flags));
		$tpl->assign('worker_meta', $worker_meta);
		$tpl->assign('worker_meta_json', json_encode($worker_meta, $json_flags));
		$tpl->assign('active_worker_id', $active_worker->id);
	}

	// Distinct task owner ids present across the given boards (+ optional stash buckets).
	private function _collectOwnerIds(array $boards, array $stash = []) {
		$ids = [];
		foreach($boards as $board)
			foreach(($board['columns'] ?? []) as $cards)
				foreach($cards as $card)
					$ids[] = intval($card['owner_id'] ?? 0);
		foreach(['until', 'indefinite'] as $k)
			foreach($stash[$k] ?? [] as $card)
				$ids[] = intval($card['owner_id'] ?? 0);
		return $ids;
	}

	// ── Card comment counts ─────────────────────────────────────────────────────

	// Distinct task ids present across the given boards (+ optional stash buckets) — the exact set already
	// materialized by the board builders, reused as the comment-count lookup keys (no project re-scoping).
	private function _collectTaskIds(array $boards, array $stash = []) {
		$ids = [];
		foreach($boards as $board)
			foreach(($board['columns'] ?? []) as $cards)
				foreach($cards as $card)
					$ids[] = intval($card['id'] ?? 0);
		foreach(['until', 'indefinite'] as $k)
			foreach($stash[$k] ?? [] as $card)
				$ids[] = intval($card['id'] ?? 0);
		return array_values(array_unique(array_filter($ids)));
	}

	// One batched count over the comment `context_and_id` index for the given task ids → [task_id => count].
	private function _getCommentCounts(array $task_ids) {
		$task_ids = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($task_ids, 'int'))));
		if(!$task_ids)
			return [];

		$db = DevblocksPlatform::services()->database();
		$counts = [];

		$rows = $db->GetArrayReader(sprintf(
			"SELECT context_id, COUNT(*) AS hits FROM comment WHERE context = %s AND context_id IN (%s) GROUP BY context_id",
			$db->qstr(Context_Task::ID), implode(',', $task_ids)
		));
		foreach($rows as $row)
			$counts[intval($row['context_id'])] = intval($row['hits']);

		return $counts;
	}

	// Overlay $counts onto every card across the boards + stash buckets (defaulting missing to 0).
	private function _attachCommentCounts(array &$boards, array &$stash, array $counts) {
		foreach($boards as &$board) {
			foreach($board['columns'] as &$cards) {
				foreach($cards as &$card)
					$card['comment_count'] = $counts[intval($card['id'] ?? 0)] ?? 0;
				unset($card);
			}
			unset($cards);
		}
		unset($board);

		foreach(['until', 'indefinite'] as $k) {
			if(!isset($stash[$k]))
				continue;
			foreach($stash[$k] as &$card)
				$card['comment_count'] = $counts[intval($card['id'] ?? 0)] ?? 0;
			unset($card);
		}
	}

	// Build id→{name, avatar_url} for the given owner ids (+ the active worker, always, for new/claimed
	// cards). Avatar URL mirrors the worker _image_url (c=avatars), with the worker's updated cache-buster.
	private function _workerMeta(array $owner_ids, $active_worker) {
		$owner_ids[] = $active_worker->id;
		$owner_ids = array_values(array_unique(array_filter(array_map('intval', $owner_ids))));
		if(!$owner_ids)
			return [];

		$url = DevblocksPlatform::services()->url();
		$workers = DAO_Worker::getAll(); // cached, id => Model_Worker (incl. disabled)

		$meta = [];
		foreach($owner_ids as $id) {
			if(!isset($workers[$id]))
				continue;
			$w = $workers[$id];
			$meta[$id] = [
				'name'   => $w->getName(),
				'avatar' => $url->writeNoProxy(sprintf('c=avatars&ctx=worker&id=%d', $id), true) . '?v=' . $w->updated,
			];
		}
		return $meta;
	}

	// ── Board data ────────────────────────────────────────────────────────────

	/**
	 * Build the day rows (everything derived — nothing board-specific is stored):
	 *   - today  = TODO (open, !active) / IN_PROGRESS (open, active), both by importance DESC, + DONE (closed today).
	 *   - prior days = DONE only (a read-only completion log), keyed by completed_date, by completed_date DESC.
	 * Newest first.
	 */
	private function _getBoards(array $live_ids, $from, $to) {
		$live_ids = array_values(array_unique(array_filter(DevblocksPlatform::sanitizeArray($live_ids, 'int'))));
		$today = strtotime('today');
		$to_end = strtotime('tomorrow', $to) - 1; // include all of the latest day

		$todo = $in_progress = [];
		$done_by_day = [];

		if($live_ids) {
			$card = fn($task) => ['id' => $task->id, 'project_id' => $task->project_id, 'text' => $task->title, 'owner_id' => $task->owner_id, 'importance' => intval($task->importance)];

			// Open tasks → always today: TODO (!active) / IN_PROGRESS (active), importance DESC.
			$open = DAO_Task::getWhere(sprintf("%s IN (%s) AND %s = 0",
				DAO_Task::PROJECT_ID, implode(',', $live_ids), DAO_Task::STATUS_ID));
			DevblocksPlatform::sortObjects($open, 'importance', false); // high importance on top
			foreach($open as $task) {
				if($task->is_active) $in_progress[] = $card($task);
				else $todo[] = $card($task);
			}

			// Done tasks within the window → bucket by completed_date's civil day, oldest completion first
			// (a just-completed task lands at the bottom of its day's log).
			$done = DAO_Task::getWhere(sprintf("%s IN (%s) AND %s = 1 AND %s BETWEEN %d AND %d",
				DAO_Task::PROJECT_ID, implode(',', $live_ids),
				DAO_Task::STATUS_ID, DAO_Task::COMPLETED_DATE, intval($from), intval($to_end)));
			DevblocksPlatform::sortObjects($done, 'completed_date', true); // oldest first → newest appended at the bottom
			foreach($done as $task)
				$done_by_day[strtotime('today', $task->completed_date)][] = $card($task);
		}

		// Emit each day in the window, newest first. Today carries the active columns + its done;
		// prior days carry only their done log.
		$boards = [];
		for($day = $to; $day >= $from; $day = strtotime('-1 day', $day)) {
			$is_today = ($day == $today);
			$boards[] = [
				'date_key' => $day,
				'date_ymd' => date('Y-m-d', $day), // tz-safe key for DOM matching / jump-to-date insert order
				'label'    => date('F jS, Y — l', $day),
				'is_today' => $is_today,
				'columns'  => $is_today
					? ['todo' => $todo, 'in_progress' => $in_progress, 'done' => $done_by_day[$day] ?? []]
					: ['done' => $done_by_day[$day] ?? []],
			];
		}

		return $boards;
	}
};
