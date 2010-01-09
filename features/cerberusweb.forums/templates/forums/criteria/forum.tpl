<b>{'common.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in">{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{$translate->_('forums.ui.forums')}:</b><br>
{foreach from=$forums item=forum key=forum_id}
<label><input name="forum_id[]" type="checkbox" value="{$forum_id}"><span style="color:rgb(0,120,0);">{$forum->name}</span></label><br>
{/foreach}

