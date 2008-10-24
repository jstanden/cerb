<b>{$translate->_('forums.ui.search.operator')}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('forums.ui.search.in_list')}</option>
		<option value="not in">{$translate->_('forums.ui.search.not_in_list')}</option>
	</select>
</blockquote>

<b>{$translate->_('forums.ui.search.forums')}:</b><br>
{foreach from=$forums item=forum key=forum_id}
<label><input name="forum_id[]" type="checkbox" value="{$forum_id}"><span style="color:rgb(0,120,0);">{$forum->name}</span></label><br>
{/foreach}

