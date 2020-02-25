{$peek_context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING}
{$peek_context_id = $model->id}
{$form_id = "frmCalendarPeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="calendar_recurring_profile">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="0" cellspacing="2" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="event_name" value="{$model->event_name}" autofocus="autofocus" style="width:100%;" placeholder="Work">
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.calendar'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<button type="button" class="chooser-abstract" data-field-name="calendar_id" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$calendar = $model->getCalendar()}
					{if $calendar}
						<li><input type="hidden" name="calendar_id" value="{$calendar->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$calendar->id}">{$calendar->name}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>

	<tr>
		<td width="0%" nowrap="nowrap" valign="top">On: </td>
		<td width="100%">
			<textarea name="patterns" style="width:100%;" placeholder="Enter any number of patterns to match">{$model->patterns}</textarea>
			<select class="placeholders" onchange="var $select=$(this); $select.siblings('textarea').insertAtCursor($select.val() + '\n').trigger('change'); $select.val('');">
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
			</select>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">When: </td>
		<td width="100%">
			<input type="text" name="event_start" value="{$model->event_start}" size="16" placeholder="9am">
			 until 
			<input type="text" name="event_end" value="{$model->event_end}" size="16" placeholder="6pm">
			
			<select name="tz">
				{foreach from=$timezones item=timezone}
				<option value="{$timezone}" {if $timezone == $model->tz}selected="selected"{/if}>{$timezone}</option>
				{/foreach}
			</select>
			<br>
			
			<i style="display:none;">(e.g. 9am, 16:00, "+2 hours", "tomorrow 03:00", "+1 week")</i>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">Starting on: </td>
		<td width="100%">
			<input type="text" name="recur_start" value="{$model->recur_start|devblocks_date:'M d Y h:ia'}" size="64" placeholder="e.g. January 9 2002; or leave blank for always">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">Ending on: </td>
		<td width="100%">
			<input type="text" name="recur_end" value="{$model->recur_end|devblocks_date:'M d Y h:ia'}" size="64" placeholder="e.g. January 19 2038; or leave blank to never end">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.status'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<label><input type="radio" name="is_available" value="1" {if empty($model) || $model->is_available}checked="checked"{/if}> Available</label>
			<label><input type="radio" name="is_available" value="0" {if !empty($model) && empty($model->is_available)}checked="checked"{/if}> Busy</label>
		</td>
	</tr>
</table>
<br>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this calendar recurring event?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.calendar.event.recurring'|devblocks_translate|capitalize|escape:'javascript'}");
		
		$popup.find('input[name=event_start], input[name=event_end]')
			.focus(function() {
				$(this).siblings('i').fadeIn();
			})
			.blur(function() {
				$(this).siblings('i').fadeOut();
			})
			;
		
		$popup.find('textarea[name=patterns]')
			.autosize();
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		$popup.find('.chooser-abstract').cerbChooserTrigger();
		
		// Focus
		$popup.find('input:text[name=event_name]').focus();
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>