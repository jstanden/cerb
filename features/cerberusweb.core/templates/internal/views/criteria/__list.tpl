<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{'common.options'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$options item=opt key=k}
		<label><input type="checkbox" name="options[]" value="{$k}"  {if is_array($param->value) && in_array($k,$param->value)}checked="checked"{/if}> {$opt}</label><br>
	{/foreach}
</blockquote>
