{$contexts = Extension_DevblocksContext::getAll(false)}

{if $context != CerberusContexts::CONTEXT_WORKER}
<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:10px;">
	<select onchange="chooserOpen(this);">
		<option value="">-- find &amp; link --</option>
		{foreach from=$contexts item=context_mft key=context_mft_id}
		{if isset($context_mft->params['options'][0]['find'])}
		<option value="{$context_mft_id}">{$context_mft->name}</option>
		{/if}
		{/foreach}
	</select>
	
	<select onchange="linkAddContext(this);">
		<option value="">-- create &amp; link --</option>
		{foreach from=$contexts item=context_mft key=context_mft_id}
		{if isset($context_mft->params['options'][0]['create'])}
		<option value="{$context_mft_id}">{$context_mft->name}</option>
		{/if}
		{/foreach}
	</select>
</form>
{/if}
