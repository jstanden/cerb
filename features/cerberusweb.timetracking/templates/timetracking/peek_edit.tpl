{$peek_context = CerberusContexts::CONTEXT_TIMETRACKING}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{if empty($workers)}{$workers = DAO_Worker::getAll()}{/if}
{if $model->is_closed}{$is_closed = 1}{else}{$is_closed = 0}{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="time_tracking">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{if !empty($model->worker_id) && isset($workers.{$model->worker_id})}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.worker'|devblocks_translate|capitalize}</label>
			<div>{$workers.{$model->worker_id}->getName()}</div>
		</div>
		{/if}

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'timetracking.ui.entry_panel.time_spent'|devblocks_translate|capitalize}</label>
			<input type="text" name="time_actual" value="{if $model->time_actual_secs}{$model->time_actual_secs|devblocks_prettysecs}{/if}" placeholder="e.g. 2 hours, 5 mins" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'timetracking.ui.entry_panel.activity'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="activityChooser_{$form_id}">
				{if $model->activity_id}
					{foreach from=$activities item=activity}
						{if $activity->id == $model->activity_id}<li data-context-id="{$activity->id}" data-label="{$activity->name}"></li>{/if}
					{/foreach}
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'timetracking_entry.log_date'|devblocks_translate|capitalize}</label>
				<input type="text" name="log_date" class="input_date" value="{$model->log_date|devblocks_date}">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
				<input type="hidden" name="is_closed" id="isClosed{$form_id}" value="{$is_closed}">
				<div>
					<div class="cerb-ui-switcher" data-cerb-input="isClosed{$form_id}">
						<button type="button" data-value="0" {if !$is_closed}class="cerb-ui-switcher--active"{/if}>{'status.open'|devblocks_translate|capitalize}</button>
						<button type="button" data-value="1" {if $is_closed}class="cerb-ui-switcher--active"{/if}>{'status.closed'|devblocks_translate|capitalize}</button>
					</div>
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

{if is_a($model,'Model_TimeTrackingEntry') && isset($model->context) && isset($model->context_id)}
<input type="hidden" name="context" value="{$model->context}">
<input type="hidden" name="context_id" value="{$model->context_id}">
{/if}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="time slip"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle resume"><span class="cerb-icons cerb-icon-play"></span> {'timetracking.ui.entry_panel.resume'|devblocks_translate}</button>
	{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$popup.dialog('option','title',"{'timetracking.ui.timetracking'|devblocks_translate|escape:'javascript' nofilter}");

		// Status switcher (open/closed) bound to the hidden is_closed input
		let statusInput = document.getElementById('isClosed{$form_id}');
		let statusEl = $popup.find('[data-cerb-input="isClosed{$form_id}"]')[0];
		if(statusInput && statusEl && window.CerbUI && CerbUI.Switcher)
			new CerbUI.Switcher(statusEl, {
				value: statusInput.value,
				onSelect: function(value) { statusInput.value = value; }
			});

		// Activity (timetracking activity record chooser)
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($popup.find('#activityChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_TIMETRACKING_ACTIVITY}',
				name: 'activity_id',
				emptyIcon: 'clock',
				searchPlaceholder: "{'timetracking.ui.entry_panel.activity'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
				create: 'if-null'
			});

		// Buttons — the `after` callbacks sync the global timeTrackingTimer
		$popup.find('button.delete').click(
			{
				mode: 'delete',
				after: function(evt) {
					if(evt.hasOwnProperty('id') && timeTrackingTimer.id == evt.id)
						timeTrackingTimer.finish();
				}
			},
			Devblocks.callbackPeekEditSave
		);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('button.save').click(
			{
				after: function(evt) {
					if(evt.hasOwnProperty('id') && timeTrackingTimer.id == evt.id)
						timeTrackingTimer.finish();
				}
			},
			Devblocks.callbackPeekEditSave
		);

		$popup.find('button.resume').click(
			{
				after: function(evt) {
					if(evt.hasOwnProperty('id'))
						timeTrackingTimer.play(evt.id);
				}
			},
			Devblocks.callbackPeekEditSave
		);

		$popup.find('input.input_date').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });

		$popup.find('input[name=time_actual]').focus();
	});
});
</script>
