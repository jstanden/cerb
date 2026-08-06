{$peek_context = CerberusContexts::CONTEXT_CALENDAR_EVENT}
{$peek_context_id = $model->id}
{$form_id = "frmCalendarPeek{uniqid()}"}

<form action="#" method="POST" id="{$form_id}" name="{$form_id}" class="calendar_popup">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="calendar_event">
<input type="hidden" name="action" value="savePeekJson">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
{if !empty($view_id)}
<input type="hidden" name="view_id" value="{$view_id}">
{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.calendar'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="calendarChooser_{$form_id}">
					{if $model}
						{$calendar = $model->getCalendar()}
						{if $calendar}
							<li data-context-id="{$calendar->id}" data-label="{$calendar->name}"></li>
						{/if}
					{/if}
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">When</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
				<input type="text" name="date_start" class="input_date" style="flex:1 1 12em;min-width:0;" value="{$model->date_start|devblocks_date:'M d Y h:ia'}">
				<span class="cerb-u-text-muted">until</span>
				<input type="text" name="date_end" class="input_date" style="flex:1 1 12em;min-width:0;" value="{$model->date_end|devblocks_date:'M d Y h:ia'}">
			</div>
			<div class="cerb-ui-form--help">e.g. "tomorrow 5pm", "+2 hours", "2011-04-27 5:00pm", "8am", "August 15", "next Thursday"</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<div>
				<input type="hidden" name="is_available" id="isAvailable_{$form_id}" value="{if empty($model) || $model->is_available}1{else}0{/if}">
				<div class="cerb-ui-switcher" data-cerb-input="isAvailable_{$form_id}">
					<button type="button" data-value="1"{if empty($model) || $model->is_available} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> Available</button>
					<button type="button" data-value="0"{if !empty($model) && empty($model->is_available)} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-clock"></span> Busy</button>
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="calendar event"}
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', '{'common.calendar.event'|devblocks_translate|capitalize|escape:'javascript'}');

		let after = function() {
			$popup.trigger(jQuery.Event('calendar_event_save'));
		};

		$popup.find('button.save').click({ after: after }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete', after: after }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		$popup.find('input.input_date').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });

		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('#calendarChooser_{$form_id}')[0], {
				context: 'calendar',
				name: 'calendar_id',
				emptyIcon: 'calendar',
				searchPlaceholder: "{'common.calendar'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});
		}

		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) input.value = value; }
				});
			});
		}

		$popup.find('input:text[name=name]').focus();
	});
});
</script>
