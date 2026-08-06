{$peek_context = CerberusContexts::CONTEXT_TASK}
{$peek_context_id = $task->id}
{$form_id = uniqid()}
{$tabset_id = "peek-editor-{DevblocksPlatform::strAlphaNum($peek_context,'','_')}"}

{$owner = $task->getOwner()}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskPeek" name="formTaskPeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="task">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$task->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="{$tabset_id}">
	{if !$task->id && $packages}
	<ul id="tabs_{$form_id}">
		<li data-alias="library"><a href="#task-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li data-alias="builder"><a href="#task-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>

	<div id="task-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}

	<div id="task-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{* Title *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
					<input type="text" name="title" maxlength="255" value="{$task->title}" autofocus="autofocus">
				</div>

				{* Status + Importance *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
						<div>
							<input type="hidden" name="status_id" id="statusId_{$form_id}" value="{$task->status_id}">
							<div class="cerb-ui-switcher" data-cerb-input="statusId_{$form_id}" id="statusSwitcher_{$form_id}">
								<button type="button" data-value="0"{if $task->status_id == 0} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-play-button"></span> {'status.open'|devblocks_translate|capitalize}</button>
								<button type="button" data-value="2"{if $task->status_id == 2} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-clock"></span> {'status.waiting.abbr'|devblocks_translate|capitalize}</button>
								<button type="button" data-value="1"{if $task->status_id == 1} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> {'status.closed'|devblocks_translate|capitalize}</button>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.importance'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-slider" id="importanceSlider_{$form_id}">
							<input type="hidden" name="importance" value="{$task->importance|default:0}">
						</div>
					</div>
				</div>

				{* Reopen-at — revealed full-width when status is 'waiting' *}
				<div class="cerb-ui-form--field" id="taskReopen_{$form_id}"{if $task->status_id != 2} style="display:none;"{/if}>
					<label class="cerb-ui-form--label">{'common.reopen_at'|devblocks_translate}</label>
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
						<input type="text" name="reopen_at" class="input_date" style="flex:1 1 auto;min-width:0;" value="{if !empty($task->reopen_at)}{$task->reopen_at|devblocks_date}{/if}">
					</div>
					<div class="cerb-ui-form--help">{'display.reply.next.resume_eg'|devblocks_translate}</div>
				</div>

				{* Due date *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'task.due_date'|devblocks_translate|capitalize}</label>
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
						<input type="text" name="due_date" class="input_date" style="flex:1 1 auto;min-width:0;" value="{if !empty($task->due_date)}{$task->due_date|devblocks_date}{/if}">
					</div>
				</div>

				{* Project + Owner *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.project'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-record-chooser" id="projectChooser_{$form_id}">
							{if $task_project}
								<li data-context-id="{$task->project_id}" data-label="{$task_project->name}"></li>
							{/if}
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-record-chooser" id="ownerChooser_{$form_id}">
							{if $owner}
								<li data-context-id="{$owner->id}" data-label="{$owner->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"></li>
							{/if}
						</div>
					</div>
				</div>

				{* Watchers (create only) *}
				{if empty($task->id)}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.watchers'|devblocks_translate|capitalize}</label>
					<div class="cerb-ui-record-chooser" id="watchersChooser_{$form_id}"></div>
				</div>
				{/if}

				{* Custom fields (cerb-ui renderer) *}
				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
				{/if}
			</div>
		</div>

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$task->id}

		{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

		{if !empty($task->id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="task"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			{if $task->id}
				<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
				{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
			{else}
				<button type="button" class="cerb-ui-button create"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
			{/if}
		</div>
	</div>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#formTaskPeek');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$popup.dialog('option','title','Tasks');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.create').click({ mode: 'create' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Abstract peeks (chips link to record cards)
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Record choosers
		if(window.CerbUI && CerbUI.RecordChooser) {
			let $projEl = $popup.find('#projectChooser_{$form_id}');
			if($projEl.length) {
				new CerbUI.RecordChooser($projEl[0], {
					context: 'task_project',
					name: 'project_id',
					emptyIcon: 'collection',
					searchPlaceholder: 'Search task projects...',
				});
			}

			new CerbUI.RecordChooser($popup.find('#ownerChooser_{$form_id}')[0], {
				context: 'worker',
				name: 'owner_id',
				emptyIcon: 'user',
				query: 'isDisabled:n',
				searchPlaceholder: "{'common.owner'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
			});

			let $watchersEl = $popup.find('#watchersChooser_{$form_id}');
			if($watchersEl.length) {
				new CerbUI.RecordChooser($watchersEl[0], {
					context: 'worker',
					name: 'add_watcher_ids',
					multiple: true,
					emptyIcon: 'user',
					query: 'isDisabled:n',
					searchPlaceholder: "{'common.watchers'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
				});
			}
		}

		// Status switcher → reveal the reopen-at date for 'waiting' only
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				let isStatus = (this.id === 'statusSwitcher_{$form_id}');

				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) {
						if(input) input.value = value;

						if(isStatus) {
							// Only 'waiting' (2) carries a scheduled reopen-at date.
							// Toggle display explicitly (keep the field's flex layout; jQuery fadeIn would reset it to block).
							let $reopen = $('#taskReopen_{$form_id}');
							if(value === '2') {
								$reopen.stop(true,true).css({ display:'flex', opacity:0 }).animate({ opacity:1 }, 150);
							} else {
								$reopen.stop(true,true).fadeOut(150);
							}
						}
					}
				});
			});
		}

		// Package Library tabs
		{if !$task->id && $packages}
			let $library_container = $popup.find('#{$tabset_id}');
			let tabsUl = $popup.find('#tabs_{$form_id}')[0];

			if(tabsUl && window.CerbUI && CerbUI.Tabs)
				new CerbUI.Tabs(tabsUl, { remember: 'tabs_{$tabset_id}' });

			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}

			$library_container.on('cerb-package-library-form-submit', function() {
				$popup.one('peek_saved peek_error', function() {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});

				$popup.find('button.create').click();
			});
		{/if}

		// Importance slider
		if(window.CerbUI && CerbUI.Slider) {
			let importanceEl = $popup.find('#importanceSlider_{$form_id}')[0];
			if(importanceEl)
				new CerbUI.Slider(importanceEl, { min: 0, max: 100, step: 1, midpoint: 50 });
		}

		// Dates
		$frm.find('input.input_date').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });
	});
});
</script>
