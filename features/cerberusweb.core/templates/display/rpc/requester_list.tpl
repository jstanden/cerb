{if !empty($requesters)}
{$container_id = "bubbles{uniqid()}"}

<ul class="bubbles" id="{$container_id}">
	{foreach from=$requesters item=req_addy name=reqs}
	<li class="bubble-gray">
		<img src="{devblocks_url}c=avatars&context=address&context_id={$req_addy->id}{/devblocks_url}?v={$req_addy->updated}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
		<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$req_addy->id}">{$req_addy->getNameWithEmail()}</a>
		{if $req_addy->is_defunct} <span class="tag tag-blue">defunct</span>{/if}
		{if $req_addy->is_banned} <span class="tag tag-red">banned</span>{/if}
	</li>
	{/foreach}
</ul>

{if $is_refresh}
<script type="text/javascript">
$(function() {
	var $container = $('#{$container_id} .cerb-peek-trigger');
	$container.cerbPeekTrigger();
});
</script>
{/if}

{else}

<div class="ui-widget" style="display:inline-block;white-space:nowrap;">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>Warning:</strong> {'ticket.recipients.empty'|devblocks_translate}</p>
	</div>
</div>

{/if}