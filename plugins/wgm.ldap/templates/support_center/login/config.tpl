<b>{'common.connected_service'|devblocks_translate|capitalize}:</b><br>
<button type="button" class="cerb-chooser-trigger" data-field-name="params[ldap_service_id]" data-context="{CerberusContexts::CONTEXT_CONNECTED_SERVICE}" data-single="true" data-query="service:ldap"><span class="glyphicons glyphicons-search"></span></button>

<ul class="bubbles chooser-container">
	{$service = DAO_ConnectedService::get($ldap_service_id)}
	{if $service}
		<li><input type="hidden" name="params[ldap_service_id]" value="{$service->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_SERVICE}" data-context-id="{$service->id}">{$service->name}</a></li>
	{/if}
</ul>
<br>
<br>
