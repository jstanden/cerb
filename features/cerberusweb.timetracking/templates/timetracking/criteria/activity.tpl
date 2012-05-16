<b>{'common.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in">{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

{foreach from=$activities item=activity key=activity_id}
<label><input name="activity_ids[]" type="checkbox" value="{$activity_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$activity->name}</span></label><br>
{/foreach}
