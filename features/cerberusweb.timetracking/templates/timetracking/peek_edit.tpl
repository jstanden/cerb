{$peek_context = CerberusContexts::CONTEXT_TIMETRACKING}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{if empty($workers)}{$workers = DAO_Worker::getAll()}{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="time_tracking">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" width="100%">
	{if !empty($model->worker_id) && isset($workers.{$model->worker_id})}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.worker'|devblocks_translate|capitalize}</b>:</td>
		<td width="100%">
			{$workers.{$model->worker_id}->getName()}
		</td>
	</tr>
	{/if}
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'timetracking.ui.entry_panel.time_spent'|devblocks_translate}</b>:</td>
		<td width="100%">
			<input type="text" name="time_actual" size="64" value="{if $model->time_actual_secs}{$model->time_actual_secs|devblocks_prettysecs}{/if}" placeholder="e.g. 2 hours, 5 mins" autofocus="autofocus">
		</td>
	</tr>
	
	{if !empty($activities)}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'timetracking.ui.entry_panel.activity'|devblocks_translate}</b>:</td>
		<td width="100%">
			<select name="activity_id">
				<option value=""></option>
				{foreach from=$activities item=activity}
				<option value="{$activity->id}" {if $model->activity_id==$activity->id}selected{/if}>{$activity->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{/if}
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'timetracking_entry.log_date'|devblocks_translate|capitalize}</b>:</td>
		<td width="100%">
			<input type="text" name="log_date" size="64" class="input_date" value="{$model->log_date|devblocks_date}"> 
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.status'|devblocks_translate|capitalize}</b>:</td>
		<td width="100%">
			<label><input type="radio" name="is_closed" value="0" {if !$model->is_closed}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="is_closed" value="1" {if $model->is_closed}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}</label>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

{if is_a($model,'Model_TimeTrackingEntry') && isset($model->context) && isset($model->context_id)}
<input type="hidden" name="context" value="{$model->context}">
<input type="hidden" name="context_id" value="{$model->context_id}">
{/if}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this time slip?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" class="resume"><span class="glyphicons glyphicons-play"></span> {'timetracking.ui.entry_panel.resume'|devblocks_translate}</button>
	{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function() {
		$popup.dialog('option','title',"{'timetracking.ui.timetracking'|devblocks_translate|escape:'javascript' nofilter}");
		
		// Buttons
		
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
		
		var $buttons = $popup.find('div.buttons');
		
		$buttons.find('button.submit').click(
			{
				after: function(evt) {
					if(evt.hasOwnProperty('id') && timeTrackingTimer.id == evt.id)
						timeTrackingTimer.finish();
				}
			},
			Devblocks.callbackPeekEditSave
		);
		
		$buttons.find('button.resume').click(
			{
				after: function(evt) {
					if(evt.hasOwnProperty('id')) {
						timeTrackingTimer.play(evt.id);
					} else {
						//timeTrackingTimer.finish();
					}
				}
			},
			Devblocks.callbackPeekEditSave
		);
		
		$popup.find('input.input_date').cerbDateInputHelper();
		
		$popup.find('button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
		});
		
		$popup.find('input[name=time_actual]').focus();
	});
});
</script>
