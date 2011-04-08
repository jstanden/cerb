<select name="{$namePrefix}[value]">
	<option value="" {if empty($params.value)}selected="selected"{/if}></option>
	{foreach from=$options item=option}
	<option value="{$option}" {if $params.value==$option}selected="checked"{/if}>{$option}</option>
	{/foreach}
</select>