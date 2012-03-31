<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>{$translate->_('search.oper.in_list')}</option>
		<option value="{DevblocksSearchCriteria::OPER_IN_OR_NULL}" {if $param && $param->operator=="{DevblocksSearchCriteria::OPER_IN_OR_NULL}"}selected="selected"{/if}>blank or in list</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>{$translate->_('search.oper.in_list.not')}</option>
		<option value="{DevblocksSearchCriteria::OPER_NIN_OR_NULL}" {if $param && $param->operator=="{DevblocksSearchCriteria::OPER_NIN_OR_NULL}"}selected="selected"{/if}>blank or not in list</option>
	</select>
</blockquote>

<b>{$translate->_('common.options')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$options item=opt key=k}
		<label><input type="checkbox" name="options[]" value="{$k}"  {if is_array($param->value) && in_array($k,$param->value)}checked="checked"{/if}>{$opt}</label><br>
	{/foreach}
</blockquote>
