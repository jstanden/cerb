<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="not in">not in list</option>
	</select>
</blockquote>

<b>Forums:</b><br>
{foreach from=$forums item=forum key=forum_id}
<label><input name="forum_id[]" type="checkbox" value="{$forum_id}"><span style="color:rgb(0,120,0);">{$forum->name}</span></label><br>
{/foreach}

