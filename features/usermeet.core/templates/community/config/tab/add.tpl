<div id="divPortalAdd">
	<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmAddPortal" name="frmAddPortal">
	<input type="hidden" name="c" value="community">
	<input type="hidden" name="a" value="saveAddPortalPeek">
	
	<b>Portal Name:</b> ("Support Portal", "Contact Form", "ProductX FAQ")<br>
	<input type="text" name="name" value="" style="width:98%;"><br>
	<br>
	
	<b>Type:</b><br>
	<select name="extension_id">
		{foreach from=$tool_manifests item=tool}
		<option value="{$tool->id|escape}">{$tool->name|escape}</option>
		{/foreach}
	</select><br>
	<br>
	
	{if $active_worker->is_superuser}
		<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
	{else}
		<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
	{/if}
	
	<br>
	</form>
</div>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"Add Community Portal");
	} );
</script>
