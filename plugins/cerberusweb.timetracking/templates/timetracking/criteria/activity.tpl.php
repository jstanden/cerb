<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

{if !empty($nonbillable_activities)}
	<b>Non-Billable Activities:</b><br>
	{foreach from=$nonbillable_activities item=activity key=activity_id}
	<label><input name="activity_ids[]" type="checkbox" value="{$activity_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$activity->name}</span></label><br>
	{/foreach}
	<br>
{/if}
{if !empty($billable_activities)}
	<b>Billable Activities:</b><br>
	{foreach from=$billable_activities item=activity key=activity_id}
	<label><input name="activity_ids[]" type="checkbox" value="{$activity_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$activity->name}</span></label><br>
	{/foreach}
{/if}
