<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<h1>Time Tracking</h1>

<table cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>Activity</b></td>
		<td width="99%" nowrap="nowrap"><b>Time Spent</b></td>
	</tr>
	<tr>
		<td nowrap="nowrap">
			<select name="activity_id">
				{if !empty($nonbillable_activities)}
				<optgroup label="Non-Billable">
					{foreach from=$nonbillable_activities item=activity}
					<option value="{$activity->id}" {if $model->activity_id==$activity->id}selected{/if}>{$activity->name|escape}</option>
					{/foreach}
				</optgroup>
				{/if}
				{if !empty($billable_activities)}
				<optgroup label="Billable">
					{foreach from=$billable_activities item=activity}
					<option value="{$activity->id}" {if $model->activity_id==$activity->id}selected{/if}>{$activity->name|escape}</option>
					{/foreach}
				</optgroup>
				{/if}
			</select>
		</td>
		<td><input type="text" name="time_actual_mins" size="5" value="{$model->time_actual_mins}"> min(s)</td>
	</tr>
</table>
<br>

<b>Note:</b> (description of work performed)<br>
<input type="text" name="notes" size="45" maxlength="255" style="width:98%;" value="{$model->notes|escape}"><br>
<br>

<b>Debit time from client:</b> (if org is known; autocompletes)<br>
<div id="contactautocomplete" style="width:98%;" class="yui-ac">
	<input type="text" name="org" id="contactinput" value="{$org->name|escape}" class="yui-ac-input">
	<div id="contactcontainer" class="yui-ac-container"></div>
	<br>
</div>
<br>

{if !empty($source)}
<b>Reference:</b><br>
<input type="hidden" name="source_extension_id" value="{$model->source_extension_id}">
<input type="hidden" name="source_id" value="{$model->source_id}">
<a href="{$source->getLink($model->source_id)}" target="_blank">{$source->getLinkText($model->source_id)}</a><br>
<br>
{/if}

{if empty($model->id)}
<button type="button" onclick="genericAjaxPost('frmTimeEntry','','c=timetracking&a=saveEntry',{literal}function(o){timeTrackingTimer.finish();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Save &amp; Finish</button>
<button type="button" onclick="timeTrackingTimer.play();genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_play_green.png{/devblocks_url}" align="top"> Resume</button>
<button type="button" onclick="timeTrackingTimer.finish();"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_stop_red.png{/devblocks_url}" align="top"> Cancel</button>
{else}
<button type="button" onclick="genericAjaxPost('frmTimeEntry','','c=timetracking&a=saveEntry');genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{if $active_worker->is_superuser || $active_worker->id == $model->worker_id}<button type="button" onclick="if(confirm('Permanently delete this time tracking entry?')){literal}{{/literal}this.form.do_delete.value='1';genericAjaxPost('frmTimeEntry','','c=timetracking&a=saveEntry');genericPanel.hide();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_delete.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
{/if}
</form>