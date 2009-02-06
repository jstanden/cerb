<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<label><input name="list_ids[]" type="checkbox" value="0"><span style="font-weight:bold;color:rgb(0,120,0);">None</span></label><br>
{if !empty($lists)}
	{foreach from=$lists item=list key=list_id}
	<label><input name="list_ids[]" type="checkbox" value="{$list_id}"><span style="font-weight:normal;color:rgb(0,120,0);">{$list->name}</span></label><br>
	{/foreach}
	<br>
{/if}
