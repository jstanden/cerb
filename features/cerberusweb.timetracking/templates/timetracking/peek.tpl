{if empty($workers)}{$workers = DAO_Worker::getAll()}{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
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
		{if !empty($activities)}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{$translate->_('timetracking.ui.entry_panel.activity')}</b>:</td>
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
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TIMETRACKING, array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TIMETRACKING context_id=$model->id full=true}
				{/if}
			</td>
		</tr>

	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
	</div>
</fieldset>

{if $model->context && $model->context_id}
<input type="hidden" name="context" value="{$model->context}">
<input type="hidden" name="context_id" value="{$model->context_id}">
{/if}

{if ($active_worker->hasPriv('timetracking.actions.create') && (empty($model->id) || $active_worker->id==$model->worker_id))
	|| $active_worker->hasPriv('timetracking.actions.update_all')}
	{if empty($model->id)}
		<button type="button" onclick="timeTrackingTimer.finish();genericAjaxPopupPostCloseReloadView(null,'frmTimeEntry','{$view_id}',false,'timetracking_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('timetracking.ui.entry_panel.save_finish')}</button>
		<button type="button" onclick="timeTrackingTimer.play();genericAjaxPopupClose('peek');"><span class="cerb-sprite sprite-media_play_green"></span> {$translate->_('timetracking.ui.entry_panel.resume')}</button>
		<button type="button" onclick="timeTrackingTimer.finish();"><span class="cerb-sprite sprite-media_stop_red"></span> {$translate->_('common.cancel')|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmTimeEntry','{$view_id}',false,'timetracking_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
		<button type="button" onclick="if(confirm('Permanently delete this time tracking entry?')) { this.form.do_delete.value='1'; genericAjaxPopupPostCloseReloadView(null,'frmTimeEntry','{$view_id}',true,'timetracking_delete'); } "><span class="cerb-sprite2 sprite-cross-circle"></span> {$translate->_('common.delete')|capitalize}</button>
	{/if}
{else}
	<div class="error">You do not have permission to modify this record.</div>
{/if}

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=time_tracking&id={$model->id}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"{'timetracking.ui.timetracking'|devblocks_translate}");
		
		$(this).find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
	});
	
	$('#frmTimeEntry button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
	$('#frmTimeEntry button.chooser_notify_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>
