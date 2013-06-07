{$page_context = CerberusContexts::CONTEXT_CALENDAR_EVENT}
{$page_context_id = $event->id}

<form action="#" method="POST" id="frmCalEvtPeek" name="frmCalEvtPeek" onsubmit="return false;" class="calendar_popup">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveCalendarEventPopup">
<input type="hidden" name="event_id" value="{$event->id}">
{if !empty($view_id)}
<input type="hidden" name="view_id" value="{$view_id}">
{/if}
{if !empty($event->calendar_id)}
<input type="hidden" name="calendar_id" value="{$event->calendar_id}">
{/if}
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" value="{$event->name}" style="width:98%;">
		</td>
	</tr>
	{if empty($event->calendar_id) && !empty($calendars)}
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
		<td width="0%" nowrap="nowrap" valign="top" align="right">When: </td>
		<td width="100%">
			<div>
				<input type="text" name="date_start" value="{$event->date_start|devblocks_date:'M d Y h:ia'}" size="32">
				 until 
				<input type="text" name="date_end" value="{$event->date_end|devblocks_date:'M d Y h:ia'}" size="32">
			</div>
			
			<i>(e.g. "tomorrow 5pm", "+2 hours", "2011-04-27 5:00pm", "8am", "August 15", "next Thursday")</i>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<label><input type="radio" name="is_available" value="1" {if empty($event) || $event->is_available}checked="checked"{/if}> Available</label>
			<label><input type="radio" name="is_available" value="0" {if !empty($event) && empty($event->is_available)}checked="checked"{/if}> Busy</label>
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_CALENDAR_EVENT context_id=$event->id}

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

{if !empty($event->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this event?
	</div>
	
	<button type="button" class="delete"><span class="cerb-sprite2 sprite-tick-circle"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

{* [TODO] If the worker can edit this calendar *}
{if 1}
	<div class="buttons">
		<button type="button" class="save"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
		{if !empty($event->id)}
		<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>
		{/if}
		
		{if !empty($event->id)}
		<div style="float:right"><a href="{devblocks_url}c=profiles&type=calendar_event&id={$event->name|devblocks_permalink}-{$event->id}{/devblocks_url}">view full record</a></div>
		{/if}
		
		<br clear="all">
	</div>
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#frmCalEvtPeek');
	$popup.one('popup_open',function(event,ui) {
		$this = $(this);
		$frm = $this.find('form');
		
		// Title
		
		$this.dialog('option','title', 'Calendar Event');
		$('#frmCalEvtPeek :input:text:first').focus();
		
		// Save button
		
		$frm.find('button.save').click(function() {
			genericAjaxPost('frmCalEvtPeek','','c=internal&a=handleSectionAction&section=calendars&action=saveCalendarEventPopupJson',function(json) {
				$popup = genericAjaxPopupFind('#frmCalEvtPeek');
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
			genericAjaxPost('frmCalEvtPeek','','c=internal&a=handleSectionAction&section=calendars&action=saveCalendarEventPopupJson&do_delete=1',function(json) {
				$popup = genericAjaxPopupFind('#frmCalEvtPeek');
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
	});
</script>