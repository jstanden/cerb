<div>
	<h3>The following records were created:</h3>
</div>

<div>
{$time_now = time()}
{foreach from=$context_mfts key=context_ext_id item=context_mft}
	{if $records_created.{$context_ext_id}}
		{$context_aliases = Extension_DevblocksContext::getAliasesForContext($context_mft)}
		<fieldset class="peek">
			<legend>{$context_aliases.plural|capitalize}</legend>
			
			<ul class="bubbles">
				{foreach from=$records_created.{$context_ext_id} item=record}
				<li>
					{if $context_mft->hasOption('avatars')}
						<img src="{devblocks_url}c=avatars&context={$context_ext_id}&context_id={$record.id}{/devblocks_url}?v={$time_now}" style="height:16px;width:16px;border-radius:16px;vertical-align:middle;">
					{/if}
					{if $context_mft->hasOption('cards')}
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{$context_mft->id}" data-context-id="{$record.id}">{$record.label}</a>
					{else}
						{$record.label}
					{/if}
				</li>
				{/foreach}
			</ul>
		</fieldset>
	{/if}
{/foreach}
</div>
