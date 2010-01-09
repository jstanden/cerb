<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('search.groups')|capitalize}:</b><br>
{foreach from=$groups item=group key=group_id}
<label><input name="group_id[]" type="checkbox" value="{$group_id}"><span style="color:rgb(0,120,0);">{$group->name}</span></label><br>
{/foreach}

