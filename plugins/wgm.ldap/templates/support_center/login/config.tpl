{$chooser_id = uniqid('ldapChooser')}
<b>{'common.connected_service'|devblocks_translate|capitalize}:</b><br>
<div class="cerb-ui-record-chooser" id="{$chooser_id}">
	{$service = DAO_ConnectedService::get($ldap_service_id)}
	{if $service}
		<li data-context="{CerberusContexts::CONTEXT_CONNECTED_SERVICE}" data-context-id="{$service->id}" data-label="{$service->name}"></li>
	{/if}
</div>
<br>
<br>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	if(window.CerbUI && CerbUI.RecordChooser)
		new CerbUI.RecordChooser(document.getElementById('{$chooser_id}'), {
			context: '{CerberusContexts::CONTEXT_CONNECTED_SERVICE}',
			name: 'params[ldap_service_id]',
			emptyIcon: 'key',
			query: 'service:ldap'
		});
});
</script>
