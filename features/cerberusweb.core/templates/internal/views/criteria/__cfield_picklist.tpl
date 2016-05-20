<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{'common.options'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$field->params.options item=opt}
		<label><input type="checkbox" name="options[]" value="{$opt}"  {if is_array($param->value) && in_array($opt,$param->value)}checked="checked"{/if}>{$opt}</label><br>
	{/foreach}
</blockquote>
