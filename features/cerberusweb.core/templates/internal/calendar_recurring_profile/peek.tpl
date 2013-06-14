<form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;" id="frmCalendarRecurringProfilePeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="calendar_recurring_profile">
<input type="hidden" name="action" value="savePeekPopupJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model->calendar_id)}
<input type="hidden" name="calendar_id" value="{$model->calendar_id}">
{/if}
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="event_name" value="{$model->event_name}" style="width:98%;" placeholder="Work">
		</td>
	</tr>
	{if empty($model->calendar_id) && !empty($calendars)}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.calendar'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<select name="calendar_id">
				{foreach from=$calendars item=calendar key=calendar_id}
				<option value="{$calendar_id}">{$calendar->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{/if}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">On: </td>
		<td width="100%">
			<textarea name="patterns" style="width:98%;" placeholder="Enter any number of patterns to match">{$model->patterns}</textarea>
			<select class="placeholders" onchange="var $select=$(this); $select.siblings('textarea').insertAtCursor($select.val() + '\n').trigger('change'); $select.val('');">
				<option value="">-- examples --</option>
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
		<td width="0%" nowrap="nowrap" valign="top" align="right">When: </td>
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
		<td width="0%" nowrap="nowrap" valign="top" align="right">Starting on: </td>
		<td width="100%">
			<input type="text" name="recur_start" value="{$model->recur_start|devblocks_date:'M d Y h:ia'}" size="64" placeholder="e.g. January 9 2002; or leave blank for always">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">Ending on: </td>
		<td width="100%">
			<input type="text" name="recur_end" value="{$model->recur_end|devblocks_date:'M d Y h:ia'}" size="64" placeholder="e.g. January 19 2038; or leave blank to never end">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}: </td>
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this calendar recurring event?
	</div>
	
	<button type="button" class="delete" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"><span class="cerb-sprite2 sprite-tick-circle"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmCalendarRecurringProfilePeek','{$view_id}', false, 'calendar_recurring_profile_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=calendar_recurring_profile&id={$model->id}-{$model->event_name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title',"{'Calendar Recurring Event'}");
		
		$this.find('input[name=event_start], input[name=event_end]')
			.focus(function() {
				$(this).siblings('i').fadeIn();
			})
			.blur(function() {
				$(this).siblings('i').fadeOut();
			})
			;
		
		$this.find('textarea[name=patterns]')
			.elastic();
		
		$this.find('input:text:first').focus();
		
		// Save button
		
		$frm.find('button.submit').click(function() {
			genericAjaxPost('frmCalendarRecurringProfilePeek','','c=profiles&a=handleSectionAction&section=calendar_recurring_profile&action=savePeekPopupJson',function(json) {
				$popup = genericAjaxPopupFind('#frmCalendarRecurringProfilePeek');
				if(null != $popup) {
					$layer = $popup.prop('id').substring(5);
					
					$event = jQuery.Event('calendar_event_save');
					if(json.month)
						$event.month = json.month;
					if(json.year)
						$event.year = json.year;
					
					genericAjaxPopupClose($layer, $event);
					
					{if !empty($view_id)}
					genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
					{/if}
				}
			});
		});
		
		$frm.find('button.delete').click(function() {
			genericAjaxPost('frmCalendarRecurringProfilePeek','','c=profiles&a=handleSectionAction&section=calendar_recurring_profile&action=savePeekPopupJson&do_delete=1',function(json) {
				$popup = genericAjaxPopupFind('#frmCalendarRecurringProfilePeek');
				if(null != $popup) {
					$layer = $popup.prop('id').substring(5);

					$event = jQuery.Event('calendar_event_delete');
					
					genericAjaxPopupClose($layer, $event);
					
					{if !empty($view_id)}
					genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
					{/if}
				}
			});
		});
		
	} );
</script>
