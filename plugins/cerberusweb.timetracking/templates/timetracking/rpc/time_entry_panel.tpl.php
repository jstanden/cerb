<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">

<h1>Time Tracking</h1>

<table cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>Activity</b></td>
		<td width="99%" nowrap="nowrap"><b>Time Spent</b></td>
	</tr>
	<tr>
		<td nowrap="nowrap">
			<select name="activity_id">
				<option value="0"></option>
				{if !empty($nonbillable_activities)}
				<optgroup label="Non-Billable">
					{foreach from=$nonbillable_activities item=activity}
					<option value="{$activity->id}">{$activity->name|escape}</option>
					{/foreach}
				</optgroup>
				{/if}
				{if !empty($billable_activities)}
				<optgroup label="Billable">
					{foreach from=$billable_activities item=activity}
					<option value="{$activity->id}">{$activity->name|escape}</option>
					{/foreach}
				</optgroup>
				{/if}
			</select>
		</td>
		<td><input type="text" name="time_actual_mins" size="5" value="{$total_mins}"> min(s)</td>
	</tr>
</table>
<br>

<b>Note:</b> (description of work performed)<br>
<input type="text" name="notes" size="45" maxlength="255" style="width:98%;" value="{$note|escape}"><br>
<br>

{*
<b>Performed for:</b> (required; contact e-mail)<br>
<input type="text" name="performed_for" size="45" maxlength="255" style="width:98%;" value="{$performed_for|escape}"><br>
<br>
*}

<b>Debit time from client:</b> (if org is known; autocompletes)<br>
<div id="contactautocomplete" style="width:98%;" class="yui-ac">
	<input type="text" name="org" id="contactinput" value="{$org|escape}" class="yui-ac-input">
	<div id="contactcontainer" class="yui-ac-container"></div>
	<br>
</div>
<br>

<b>Reference:</b><br>
<input type="hidden" name="source_ext_id" value="{$source_ext_id}">
<input type="hidden" name="source_id" value="{$source_id}">
{$source_ext_id} = {$source_id}<br>
{*<input type="text" name="reference" size="45" maxlength="255" style="width:98%;" value="{$reference|escape}"><br>*}
<br>

<button type="button" onclick="genericAjaxPost('frmTimeEntry','','c=timetracking&a=saveEntry',{literal}function(o){timeTrackingTimer.finish();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Save &amp; Finish</button>
<button type="button" onclick="timeTrackingTimer.play();genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_play_green.png{/devblocks_url}" align="top"> Resume</button>
<button type="button" onclick="timeTrackingTimer.finish();"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_stop_red.png{/devblocks_url}" align="top"> Cancel</button>
</form>