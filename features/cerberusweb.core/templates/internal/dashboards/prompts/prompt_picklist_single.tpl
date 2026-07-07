{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div class="cerb-ui-form--field cerb-filter-editor" style="flex:0 1 auto;">
	<label class="cerb-ui-form--label">{$prompt.label}</label>
	<select id="{$uniqid}" name="prompts[{$prompt.placeholder}]">
		{foreach from=$prompt.params.options item=option key=option_key}
			<option value="{$option}" {if $prompt_value == $option}selected='selected'{/if}>
				{if is_string($option_key)}
					{$option_key}
				{else}
					{$option}
				{/if}
			</option>
		{/foreach}
	</select>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let el = document.getElementById('{$uniqid}');

	if(el && window.CerbUI && CerbUI.SelectMenu)
		new CerbUI.SelectMenu(el);
});
</script>
