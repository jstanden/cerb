{$fieldset_id = uniqid()}
<fieldset id="{$fieldset_id}">
<legend>Configuration</legend>
{foreach from=$prompts item=prompt}
<b>{$prompt.label}</b>
<div style="margin-bottom:1em;">
{if $prompt.type == 'text'}
<input type="text" name="prompts[{$prompt.key}]" value="{$prompt.params.default}" style="width:100%;" placeholder="{$prompt.params.placeholder}">
{elseif $prompt.type == 'chooser'}
<button type="button" class="cerb-chooser-trigger" data-field-name="prompts[{$prompt.key}]" data-context="{$prompt.params.context}" {if $prompt.params.single}data-single="true"{/if} data-query="{$prompt.params.query}"><span class="glyphicons glyphicons-search"></span></button>
<ul class="bubbles chooser-container"></ul>
{/if}
</div>
{/foreach}
</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$fieldset_id}');
	$fieldset.find('.cerb-chooser-trigger')
		.cerbChooserTrigger()
		;
});
</script>