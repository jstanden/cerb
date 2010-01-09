<b>{'common.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in">{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

{if !empty($nonbillable_activities)}
	<b>{$translate->_('timetracking.ui.criteria.non_billable')|capitalize}</b><br>
	{foreach from=$nonbillable_activities item=activity key=activity_id}
	<label><input name="activity_ids[]" type="checkbox" value="{$activity_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$activity->name}</span></label><br>
	{/foreach}
	<br>
{/if}
{if !empty($billable_activities)}
	<b>{$translate->_('timetracking.ui.criteria.billable')|capitalize}</b><br>
	{foreach from=$billable_activities item=activity key=activity_id}
	<label><input name="activity_ids[]" type="checkbox" value="{$activity_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$activity->name}</span></label><br>
	{/foreach}
{/if}
