<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('common.context')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$contexts item=context}
		<label><input type="checkbox" name="contexts[]" value="{$context->id}">{$context->name}</label><br>
	{/foreach}
</blockquote>
