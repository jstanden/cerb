{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}
{$context_mft = Extension_DevblocksContext::getByAlias($prompt.params.context)}

{if $context_mft}
<div id="{$uniqid}" class="cerb-ui-form--field cerb-filter-editor" style="flex:1 1 16em;">
	<label class="cerb-ui-form--label">{$prompt.label}</label>
	<div class="cerb-ui-record-chooser">
		{if $prompt_value}
			{if is_array($prompt_value)}
				{$context_ids = $prompt_value}
			{elseif is_string($prompt_value)}
				{$context_ids = explode(',', $prompt_value)}
			{else}
				{$context_ids = []}
			{/if}
			{$models = CerberusContexts::getModels($context_mft->id, $context_ids)}
			{$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_mft->id)}

			{foreach from=$dicts item=dict}
			<li data-context="{$dict->_context}" data-context-id="{$dict->id}" data-label="{$dict->_label}"{if $context_mft->hasOption('avatars')} data-image="{devblocks_url}c=avatars&context={$context_mft->params.alias}&context_id={$dict->id}{/devblocks_url}?v={$dict->get('updated_at', $dict->updated)}"{/if}></li>
			{/foreach}
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $filter = $('#{$uniqid}');

	if(window.CerbUI && CerbUI.RecordChooser) {
		$filter.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{$context_mft->id}',
				emptyIcon: '{$context_mft->params.icon|default:"file"}',
				{if $prompt.params.single}name: 'prompts[{$prompt.placeholder}][]', multiple: false{else}name: 'prompts[{$prompt.placeholder}]', multiple: true{/if},
				query: '{$prompt.params.query|escape:'javascript' nofilter}'
			});
		});
	}
});
</script>
{/if}
