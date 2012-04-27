<ul class="bubbles">
{foreach from=$requesters item=req_addy name=reqs}
<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$req_addy->id}',null,false,'500');">{$req_name=$req_addy->getName()}{if !empty($req_name)}{$req_name} {/if}&lt;{$req_addy->email}&gt;</a></li>
{foreachelse}
<div class="ui-widget" style="display:inline-block;">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>Warning:</strong> {$translate->_('ticket.recipients.empty')}</p>
	</div>
</div>
{/foreach}
</ul>
