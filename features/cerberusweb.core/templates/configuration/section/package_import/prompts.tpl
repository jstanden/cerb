{capture name=package_prompts}
{foreach from=$prompts item=prompt}
	{if !$prompt.hidden}
	<b>{$prompt.label}</b>
	<div style="margin-bottom:1em;">
	{if $prompt.type == 'text'}
	<input type="text" name="prompts[{$prompt.key}]" value="{$prompt.params.default}" style="width:100%;" placeholder="{$prompt.params.placeholder}">
	{elseif $prompt.type == 'picklist'}
		{if $prompt.params.multiple}
		{else}
		<select name="prompts[{$prompt.key}]">
			{foreach from=$prompt.params.options item=option key=option_label}
			<option value="{$option}" {if $option == $prompt.params.default}selected="selected"{/if}>{if is_string($option_label)}{$option_label}{else}{$option}{/if}</option>
			{/foreach}
		</select>
		{/if}
	{elseif $prompt.type == 'chooser'}
	<div class="cerb-ui-record-chooser cerb-package-prompt-chooser" data-context="{$prompt.params.context}" data-name="prompts[{$prompt.key}]"{if $prompt.params.single} data-single="1"{/if}{if $prompt.params.query} data-query="{$prompt.params.query}"{/if}></div>
	{/if}
	</div>
	{/if}
{/foreach}
{/capture}

{if $smarty.capture.package_prompts|trim|strlen > 0}
{$fieldset_id = uniqid()}
<fieldset id="{$fieldset_id}">
<legend>{'common.configuration'|devblocks_translate|capitalize}</legend>
{$smarty.capture.package_prompts nofilter}
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $fieldset = $('#{$fieldset_id}');

	if(window.CerbUI && CerbUI.RecordChooser) {
		$fieldset.find('.cerb-package-prompt-chooser').each(function() {
			const single = this.getAttribute('data-single') === '1';
			new CerbUI.RecordChooser(this, {
				context: this.getAttribute('data-context'),
				name: this.getAttribute('data-name'),
				multiple: !single,
				query: this.getAttribute('data-query') || ''
			});
		});
	}
});
</script>
{/if}