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
	<button type="button" class="cerb-chooser-trigger" data-field-name="prompts[{$prompt.key}]{if !$prompt.params.single}[]{/if}" data-context="{$prompt.params.context}" {if $prompt.params.single}data-single="true"{/if} data-query="{$prompt.params.query}"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container"></ul>
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

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$fieldset_id}');
	$fieldset.find('.cerb-chooser-trigger')
		.cerbChooserTrigger()
		;
});
</script>
{/if}