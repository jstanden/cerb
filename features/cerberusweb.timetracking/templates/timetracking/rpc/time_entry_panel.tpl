<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">
{if empty($workers)}{$workers = DAO_Worker::getAll()}{/if}

{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>
	<table cellpadding="2" cellspacing="0" width="100%">
		{if !empty($model->worker_id) && isset($workers.{$model->worker_id})}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{$translate->_('common.worker')|capitalize}</b>:</td>
			<td width="100%">
				{$workers.{$model->worker_id}->getName()}
			</td>
		</tr>
		{/if}
		{if !empty($nonbillable_activities) || !empty($billable_activities)}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{$translate->_('timetracking.ui.entry_panel.activity')}</b>:</td>
			<td width="100%">
				<select name="activity_id">
					<option value=""></option>
					{if !empty($nonbillable_activities)}
					<optgroup label="{$translate->_('timetracking.ui.non_billable')}">
						{foreach from=$nonbillable_activities item=activity}
						<option value="{$activity->id}" {if $model->activity_id==$activity->id}selected{/if}>{$activity->name}</option>
						{/foreach}
					</optgroup>
					{/if}
					{if !empty($billable_activities)}
					<optgroup label="{$translate->_('timetracking.ui.billable')}">
						{foreach from=$billable_activities item=activity}
						<option value="{$activity->id}" {if $model->activity_id==$activity->id}selected{/if}>{$activity->name}</option>
						{/foreach}
					</optgroup>
					{/if}
				</select>
			</td>
		</tr>
		{/if}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{$translate->_('timetracking.ui.entry_panel.time_spent')}</b>:</td>
			<td width="100%">
				<input type="text" name="time_actual_mins" size="5" value="{$model->time_actual_mins}"> {$translate->_('timetracking.ui.entry_panel.mins')}
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{$translate->_('timetracking_entry.log_date')|capitalize}</b>:</td>
			<td width="100%">
				<input type="text" name="log_date" size="45" style="width:98%;" value="{$model->log_date|devblocks_date}"> 
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{$translate->_('common.status')|capitalize}</b>:</td>
			<td width="100%">
				<label><input type="radio" name="is_closed" value="0" {if !$model->is_closed}checked="checked"{/if}> {$translate->_('status.open')|capitalize}</label>
				<label><input type="radio" name="is_closed" value="1" {if $model->is_closed}checked="checked"{/if}> {$translate->_('status.closed')|capitalize}</label>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.watchers'|devblocks_translate|capitalize}</b>:</td>
			<td width="100%">
				<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="chooser-container bubbles" style="display:block;">
				{if !empty($context_watchers)}
					{foreach from=$context_watchers item=context_worker}
					<li>{$context_worker->getName()}<input type="hidden" name="worker_id[]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
					{/foreach}
				{/if}
				</ul>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset>
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea><br>
	<b>{'common.notify_workers'|devblocks_translate}:</b>
	<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
		{if !empty($context_watchers)}
			{foreach from=$context_watchers item=context_worker}
			{if $context_worker->id != $active_worker->id}
				<li>{$context_worker->getName()}<input type="hidden" name="notify_worker_ids[]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
			{/if}
			{/foreach}
		{/if}
	</ul>
</fieldset>

{if $model->context && $model->context_id}
<input type="hidden" name="context" value="{$model->context}">
<input type="hidden" name="context_id" value="{$model->context_id}">
{/if}

{if ($active_worker->hasPriv('timetracking.actions.create') && (empty($model->id) || $active_worker->id==$model->worker_id))
	|| $active_worker->hasPriv('timetracking.actions.update_all')}
	{if empty($model->id)}
		<button type="button" onclick="timeTrackingTimer.finish();genericAjaxPopupPostCloseReloadView('peek','frmTimeEntry','{$view_id}',false,'timetracking_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('timetracking.ui.entry_panel.save_finish')}</button>
		<button type="button" onclick="timeTrackingTimer.play();genericAjaxPopupClose('peek');"><span class="cerb-sprite sprite-media_play_green"></span> {$translate->_('timetracking.ui.entry_panel.resume')}</button>
		<button type="button" onclick="timeTrackingTimer.finish();"><span class="cerb-sprite sprite-media_stop_red"></span> {$translate->_('common.cancel')|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmTimeEntry','{$view_id}',false,'timetracking_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
		<button type="button" onclick="if(confirm('Permanently delete this time tracking entry?')) { this.form.do_delete.value='1'; genericAjaxPopupPostCloseReloadView('peek','frmTimeEntry','{$view_id}',true,'timetracking_delete'); } "><span class="cerb-sprite2 sprite-minus-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>
		<button type="button" onclick="genericAjaxPopupClose('peek');"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.cancel')|capitalize}</button>
	{/if}
{else}
	<div class="error">You do not have permission to modify this record.</div>
{/if}

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=timetracking&a=display&id={$model->id}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"{'timetracking.ui.timetracking'|devblocks_translate}");
	} );
	$('#frmTimeEntry button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
	$('#frmTimeEntry button.chooser_notify_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>
