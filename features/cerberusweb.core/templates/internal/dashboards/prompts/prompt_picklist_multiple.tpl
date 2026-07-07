{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div class="cerb-ui-form--field cerb-filter-editor" style="flex:1 1 14em;">
	<label class="cerb-ui-form--label">{$prompt.label}</label>
	<div id="{$uniqid}" class="cerb-ui-value-picker">
		{foreach from=$prompt.params.options item=option key=option_key}
		<label><input type="checkbox" name="prompts[{$prompt.placeholder}][]" value="{$option}" {if is_array($prompt_value) && in_array($option, $prompt_value)}checked="checked"{/if}> {if is_string($option_key)}{$option_key}{else}{$option}{/if}</label>
		{/foreach}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let el = document.getElementById('{$uniqid}');

	if(el && window.CerbUI && CerbUI.ValuePicker)
		new CerbUI.ValuePicker(el, { multiple: true });
});
</script>
