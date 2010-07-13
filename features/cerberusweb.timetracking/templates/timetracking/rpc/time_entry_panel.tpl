<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<table cellpadding="2" cellspacing="0" width="100%">
	<tr>
		{if !empty($nonbillable_activities) || !empty($billable_activities)}
		<td width="1%" nowrap="nowrap"><b>{$translate->_('timetracking.ui.entry_panel.activity')}</b></td>
		{/if}
		<td width="99%" nowrap="nowrap"><b>{$translate->_('timetracking.ui.entry_panel.time_spent')}</b></td>
	</tr>
	<tr>
		{if !empty($nonbillable_activities) || !empty($billable_activities)}
		<td nowrap="nowrap">
			<select name="activity_id">
				{if !empty($nonbillable_activities)}
				<optgroup label="{$translate->_('timetracking.ui.non_billable')}">
					{foreach from=$nonbillable_activities item=activity}
					<option value="{$activity->id}" {if $model->activity_id==$activity->id}selected{/if}>{$activity->name|escape}</option>
					{/foreach}
				</optgroup>
				{/if}
				{if !empty($billable_activities)}
				<optgroup label="{$translate->_('timetracking.ui.billable')}">
					{foreach from=$billable_activities item=activity}
					<option value="{$activity->id}" {if $model->activity_id==$activity->id}selected{/if}>{$activity->name|escape}</option>
					{/foreach}
				</optgroup>
				{/if}
			</select>
		</td>
		{/if}
		<td><input type="text" name="time_actual_mins" size="5" value="{$model->time_actual_mins}"> {$translate->_('timetracking.ui.entry_panel.mins')}</td>
	</tr>
</table>
<br>

<b>{$translate->_('timetracking.ui.entry_panel.note')}</b> {$translate->_('timetracking.ui.entry_panel.note_hint')}<br>
<input type="text" name="notes" size="45" maxlength="255" style="width:98%;" value="{$model->notes|escape}"><br>
<br>

<b>{$translate->_('timetracking.ui.entry_panel.debit_time_client')}</b> {$translate->_('timetracking.ui.entry_panel.debit_time_client_hint')}<br>
<input type="text" name="org" id="orginput" value="{$org->name|escape}" style="width:98%;">
<br>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
<br>

{if !empty($source)}
<b>{$translate->_('timetracking.ui.entry_panel.reference')}</b><br>
<input type="hidden" name="source_extension_id" value="{$model->source_extension_id}">
<input type="hidden" name="source_id" value="{$model->source_id}">
<a href="{$source->getLink($model->source_id)}" target="_blank">{$source->getLinkText($model->source_id)}</a><br>
<br>
{/if}

{if ($active_worker->hasPriv('timetracking.actions.create') && (empty($model->id) || $active_worker->id==$model->worker_id))
	|| $active_worker->hasPriv('timetracking.actions.update_all')}
	{if empty($model->id)}
		<button type="button" onclick="timeTrackingTimer.finish();genericAjaxPopupPostCloseReloadView('peek','frmTimeEntry','{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('timetracking.ui.entry_panel.save_finish')}</button>
		<button type="button" onclick="timeTrackingTimer.play();genericAjaxPopupClose('peek');"><span class="cerb-sprite sprite-media_play_green"></span> {$translate->_('timetracking.ui.entry_panel.resume')}</button>
		<button type="button" onclick="timeTrackingTimer.finish();"><span class="cerb-sprite sprite-media_stop_red"></span> {$translate->_('common.cancel')|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmTimeEntry','{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
		<button type="button" onclick="if(confirm('Permanently delete this time tracking entry?')) { this.form.do_delete.value='1'; genericAjaxPopupPostCloseReloadView('peek','frmTimeEntry','{$view_id}'); } "><span class="cerb-sprite sprite-forbidden"></span> {$translate->_('common.delete')|capitalize}</button>
		<button type="button" onclick="genericAjaxPopupClose('peek');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>
	{/if}
{else}
	<div class="error">You do not have permission to modify this record.</div>
{/if}
</form>

<script language="JavaScript1.2" type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title',"{'timetracking.ui.timetracking'|devblocks_translate|escape:'quotes'}");
		ajax.orgAutoComplete('#orginput');
	} );
</script>
