{if !empty($requesters)}
<ul class="bubbles">
	{foreach from=$requesters item=req_addy name=reqs}
	<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$req_addy->id}',null,false,'500');">{$req_name=$req_addy->getName()}{if !empty($req_name)}{$req_name} {/if}&lt;{$req_addy->email}&gt;</a>{if $req_addy->is_defunct} <span class="tag tag-blue">defunct</span>{/if}{if $req_addy->is_banned} <span class="tag tag-red">banned</span>{/if}</li>
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