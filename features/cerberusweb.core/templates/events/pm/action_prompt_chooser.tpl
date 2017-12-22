<b>{'common.type'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select name="{$namePrefix}[context]">
	{foreach from=$contexts item=context key=context_id}
	<option value="{$context_id}" {if $params.context == $context_id}selected="selected"{/if}>{$context->name}</option>
	{/foreach}
	</select>
</div>

<b>{'common.query'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[query]" value="{$params.query}" style="width:100%;" class="placeholders">
</div>

<b>{'common.selection'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[selection]" value="single" {if $params.selection != 'multiple'}checked="checked"{/if}> {'common.selection.single'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[selection]" value="multiple" {if $params.selection == 'multiple'}checked="checked"{/if}> {'common.selection.multiple'|devblocks_translate|capitalize}</label>
</div>

<b>{'common.autocomplete'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[autocomplete]" value="0" {if !$params.autocomplete}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[autocomplete]" value="1" {if $params.autocomplete}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
