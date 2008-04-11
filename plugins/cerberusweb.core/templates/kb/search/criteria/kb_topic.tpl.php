<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
		<option value="is null">is null</option>
	</select>
</blockquote>

<b>Topics:</b><br>
{foreach from=$topics item=topic key=topic_id}
<label><input name="topic_id[]" type="checkbox" value="{$topic_id}"><span style="color:rgb(0,120,0);">{$topic->name}</span></label><br>
{/foreach}

