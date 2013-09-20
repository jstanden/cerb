{if empty($contexts)}
{$contexts = Extension_DevblocksContext::getAll(false)}
{/if}
<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{'common.context'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$contexts item=context}
		<label><input type="checkbox" name="contexts[]" value="{$context->id}" {if is_array($param->value) && in_array($context->id,$param->value)}checked="checked"{/if}>{$context->name}</label><br>
	{/foreach}
</blockquote>
