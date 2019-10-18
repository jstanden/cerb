<h3>{'common.synchronize'|devblocks_translate|capitalize}</h3>

<div style="margin:0 0 15px 10px;">
	<b>Download @mentions as Twitter Message records for these connected accounts:</b><br>
	<button type="button" class="chooser-abstract" data-field-name="sync_account_ids[]" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-query="name:twitter"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{if $sync_accounts}
		{foreach from=$sync_accounts item=sync_account}
		<li>
			<input type="hidden" name="sync_account_ids[]" value="{$sync_account->id}">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$sync_account->id}">{$sync_account->name}</a>
		</li>
		{/foreach}
		{/if}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmJobwgmtwitter_cron');
	
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$frm.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
});
</script>