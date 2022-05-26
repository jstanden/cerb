{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}
{$context_mft = Extension_DevblocksContext::getByAlias($prompt.params.context)}

{if $context_mft}
<div style="display:inline-block;vertical-align:middle;">
	<div id="{$uniqid}" class="bubble cerb-filter-editor" style="padding:5px;display:block;">
		<div>
			<b>{$prompt.label}</b>
		</div>
		<div>
			<button type="button" class="cerb-chooser-prompt" data-field-name="prompts[{$prompt.placeholder}][]" data-context="{$context_mft->id}" {if $prompt.params.query}data-query="{$prompt.params.query}"{/if} {if $prompt.params.single}data-single="true"{/if}><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $prompt_value}
					{if is_array($prompt_value)}
						{$context_ids = $prompt_value}
						{$prompt_value = implode(',', $prompt_value)}
					{elseif is_string($prompt_value)}
						{$context_ids = explode(',', $prompt_value)}
					{else}
						{$context_ids = []}
						{$prompt_value = ''}
					{/if}
					{$models = CerberusContexts::getModels($context_mft->id, $context_ids)}
					{$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_mft->id)}
					
					{foreach from=$dicts item=dict}
					<li style="margin:0;">
						{if $context_mft->hasOption('avatars')}
						<img class="cerb-avatar" src="{devblocks_url}c=avatars&context={$context_mft->params.alias}&context_id={$dict->id}{/devblocks_url}?v={$dict->get('updated_at', $dict->updated)}">
						{/if}
						<input type="hidden" name="prompts[{$prompt.placeholder}][]" value="{$dict->id}">
						<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{$dict->_context}" data-context-id="{$dict->id}">
							{$dict->_label}
						</a>
					</li>
					{/foreach}
				{/if}
			</ul>
		</div>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $filter = $('#{$uniqid}');
	
	$filter.find('.cerb-chooser-prompt')
		.cerbChooserTrigger()
		;
	
	$filter.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
});
</script>
{/if}