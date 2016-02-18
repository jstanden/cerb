{$whos_online = DAO_Worker::getAllOnline(900)}
{if $whos_online}
<h1 style="border-bottom:1px solid rgb(220,220,220);">{'whos_online.currently_active'|devblocks_translate}</h1>

<div id="whos">
	{foreach from=$whos_online item=who name=whos}
		<a href="javascript:;" style="font-weight:bold;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$who->id}">{$who->getName()}</a>{if $who->title} ({$who->title}){/if}{if !$smarty.foreach.whos.last}, {/if}
	{/foreach}
	{/if}
</div>

<script type="text/javascript">
$(function() {
	$('#whos').find('.cerb-peek-trigger').cerbPeekTrigger();
});
</script>