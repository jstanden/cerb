<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Groups:</b><br>
{foreach from=$groups item=group key=group_id}
<label><input name="group_id[]" type="checkbox" value="{$group_id}"><span style="color:rgb(0,120,0);">{$group->name}</span></label><br>
{/foreach}

