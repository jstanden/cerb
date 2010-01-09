<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('common.options')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$field->options item=opt}
		<label><input type="checkbox" name="options[]" value="{$opt}">{$opt}</label><br>
	{/foreach}
</blockquote>
