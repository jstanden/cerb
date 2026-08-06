{$peek_context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING}
{$peek_context_id = $model->id}
{$form_id = "frmCalendarPeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="calendar_recurring_event">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="event_name" value="{$model->event_name}" autofocus="autofocus" placeholder="Work">
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
			<label class="cerb-ui-form--label">On</label>
			<textarea name="patterns" style="height:5.5em;" placeholder="Enter any number of patterns to match">{$model->patterns}</textarea>
			<div class="cerb-u-mt-1">
				<select data-cerb-select-examples>
					<option value="">-- {'common.examples'|devblocks_translate|lower} --</option>
					<optgroup label="Days of the week">
						<option value="Weekdays">Weekdays</option>
						<option value="Weekends">Weekends</option>
						<option value="Sunday">Sunday</option>
						<option value="Monday">Monday</option>
						<option value="Tuesday">Tuesday</option>
						<option value="Wednesday">Wednesday</option>
						<option value="Thursday">Thursday</option>
						<option value="Friday">Friday</option>
						<option value="Saturday">Saturday</option>
					</optgroup>
					<optgroup label="Days of the month">
						<option value="1st">1st</option>
						<option value="15th">15th</option>
					</optgroup>
					<optgroup label="Days of the year">
						<option value="Jan 1">Jan 1</option>
						<option value="Dec 25">Dec 25</option>
					</optgroup>
					<optgroup label="Specific weekdays">
						{$examples = ["first Monday of September","fourth Thursday of November","third Friday of every month","first day of every month","last day of every month"]}
						{foreach from=$examples item=example}
						<option value="{$example}">{$example}</option>
						{/foreach}
					</optgroup>
					<optgroup label="Specific holidays">
						{$examples = ["Easter","Easter -7 weeks Wednesday","Easter +3 days"]}
						{foreach from=$examples item=example}
						<option value="{$example}">{$example}</option>
						{/foreach}
					</optgroup>
				</select>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">When</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
				<input type="text" name="event_start" value="{$model->event_start}" style="width:8em;flex:0 0 auto;" placeholder="9am">
				<span class="cerb-u-text-muted">until</span>
				<input type="text" name="event_end" value="{$model->event_end}" style="width:8em;flex:0 0 auto;" placeholder="6pm">
				<select name="tz" data-cerb-tz-selectmenu style="flex:1 1 14em;min-width:0;">
					<option value="">(use calendar timezone)</option>
					{foreach from=$timezones item=timezone}
					<option value="{$timezone}" {if $timezone == $model->tz}selected="selected"{/if}>{$timezone}</option>
					{/foreach}
				</select>
			</div>
			<div class="cerb-ui-form--help">e.g. 9am, 16:00, "+2 hours", "tomorrow 03:00", "+1 week"</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Starting on</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					<input type="text" name="recur_start" class="input_date" style="flex:1 1 auto;min-width:0;" value="{$model->recur_start|devblocks_date:'M d Y h:ia'}" placeholder="e.g. January 9 2002; or leave blank for always">
				</div>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Ending on</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					<input type="text" name="recur_end" class="input_date" style="flex:1 1 auto;min-width:0;" value="{$model->recur_end|devblocks_date:'M d Y h:ia'}" placeholder="e.g. January 19 2038; or leave blank to never end">
				</div>
			</div>
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
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="calendar recurring event"}
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.calendar.event.recurring'|devblocks_translate|capitalize|escape:'javascript'}");

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
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

		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-tz-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) input.value = value; }
				});
			});
		}

		// Examples → insert into the patterns textarea
		$popup.find('[data-cerb-select-examples]').on('change', function(e) {
			e.stopPropagation();
			let $select = $(this);
			$select.closest('.cerb-ui-form--field').find('textarea').insertAtCursor($select.val() + '\n').trigger('change');
			$select.val('');
		});

		$popup.find('input:text[name=event_name]').focus();
	});
});
</script>
