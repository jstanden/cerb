<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>{'search.oper.in_list'|devblocks_translate}</option>
		<option value="{DevblocksSearchCriteria::OPER_IN_OR_NULL}" {if $param && $param->operator=="{DevblocksSearchCriteria::OPER_IN_OR_NULL}"}selected="selected"{/if}>blank or in list</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>{'search.oper.in_list.not'|devblocks_translate}</option>
		<option value="{DevblocksSearchCriteria::OPER_NIN_OR_NULL}" {if $param && $param->operator=="{DevblocksSearchCriteria::OPER_NIN_OR_NULL}"}selected="selected"{/if}>blank or not in list</option>
	</select>
</blockquote>

<b>{'common.options'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$field->params.options item=opt}
		<label><input type="checkbox" name="options[]" value="{$opt}"  {if is_array($param->value) && in_array($opt,$param->value)}checked="checked"{/if}>{$opt}</label><br>
	{/foreach}
</blockquote>
