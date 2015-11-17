{if !empty($requesters)}
{$container_id = "bubbles{uniqid()}"}

<ul class="bubbles" id="{$container_id}">
	{foreach from=$requesters item=req_addy name=reqs}
	<li>
		<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$req_addy->id}">{$req_addy->getNameWithEmail()}</a>
		{if $req_addy->is_defunct} <span class="tag tag-blue">defunct</span>{/if}
		{if $req_addy->is_banned} <span class="tag tag-red">banned</span>{/if}
	</li>
	{/foreach}
</ul>

{else}

<div class="ui-widget" style="display:inline-block;white-space:nowrap;">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>Warning:</strong> {'ticket.recipients.empty'|devblocks_translate}</p>
	</div>
</div>

{/if}