<div>
	<h3>The following records were created:</h3>
</div>

<div>
{$context_mfts = Extension_DevblocksContext::getAll(false)}
{foreach from=$context_mfts key=context_ext_id item=context_mft}
	{if $records_created.{$context_ext_id}}
		{$context_aliases = Extension_DevblocksContext::getAliasesForContext($context_mft)}
		<fieldset class="peek">
			<legend>{$context_aliases.plural|capitalize}</legend>
			
			<ul class="bubbles">
				{foreach from=$records_created.{$context_ext_id} item=record}
				{if $context_mft->hasOption('cards')}
				<li><a href="javascript:;" class="cerb-peek-trigger" data-context="{$context_mft->id}" data-context-id="{$record.id}">{$record.label}</a></li>
				{else}
				<li>{$record.label}</li>
				{/if}
				{/foreach}
			</ul>
		</fieldset>
	{/if}
{/foreach}
</div>
