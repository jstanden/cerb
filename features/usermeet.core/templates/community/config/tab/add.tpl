<div id="divPortalAdd">
	<h1>Add Community Portal</h1>
	
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
		<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
	{else}
		<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
	{/if}
	<button type="button" onclick="genericPanel.hide();genericAjaxPostAfterSubmitEvent.unsubscribeAll();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
	
	<br>
	</form>
</div>