{$peek_context = CerberusContexts::CONTEXT_CALENDAR_EVENT}
{$peek_context_id = $model->id}
{$form_id = "frmCalendarPeek{uniqid()}"}

<form action="#" method="POST" id="{$form_id}" name="{$form_id}" onsubmit="return false;" class="calendar_popup">
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

<table cellpadding="0" cellspacing="2" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" value="{$model->name}" style="width:100%;" autofocus="autofocus">
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
		<td width="0%" nowrap="nowrap" valign="top">When: </td>
		<td width="100%">
			<div>
				<input type="text" name="date_start" value="{$model->date_start|devblocks_date:'M d Y h:ia'}" size="32">
				 until 
				<input type="text" name="date_end" value="{$model->date_end|devblocks_date:'M d Y h:ia'}" size="32">
			</div>
			
			<i>(e.g. "tomorrow 5pm", "+2 hours", "2011-04-27 5:00pm", "8am", "August 15", "next Thursday")</i>
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
		Are you sure you want to permanently delete this calendar event?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}
	<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	{/if}
	<br clear="all">
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function(event,ui) {
		// Title
		$popup.dialog('option','title', '{'common.calendar.event'|devblocks_translate|capitalize|escape:'javascript'}');
		
		var after = function() {
			var $event = jQuery.Event('calendar_event_save');
			$popup.trigger($event);
		}
		
		// Buttons
		$popup.find('button.submit').click({ after: after }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete', after: after }, Devblocks.callbackPeekEditSave);
		
		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		$popup.find('.chooser-abstract').cerbChooserTrigger();
		
		// Focus
		$popup.find('input:text[name=name]').focus();
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>