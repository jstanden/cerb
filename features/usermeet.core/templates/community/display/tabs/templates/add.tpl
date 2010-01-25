<div id="divPortalAddTemplate">
	<h1>Add Template</h1>
	
	{if !empty($templates)}
		<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmPortalAddTemplatePeek" name="frmPortalAddTemplatePeek" onsubmit="return false;">
		<input type="hidden" name="c" value="community">
		<input type="hidden" name="a" value="saveAddTemplatePeek">
		<input type="hidden" name="view_id" value="{$view_id}">
		<input type="hidden" name="portal" value="{$portal}">
		
		<b>Template:</b><br>
		<select name="template">
			{foreach from=$templates item=template}
			<option value="{$template->plugin_id|escape}:{$template->path|escape}">[{$template->plugin_id|escape}] ({$template->set}) {$template->path|escape}</option>
			{/foreach}
		</select><br>
		<br>
		
		{if $active_worker->is_superuser}
			<button type="button" onclick="genericAjaxPost('frmPortalAddTemplatePeek', 'divPortalAddTemplate', '');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
		{else}
			<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
		{/if}
		<button type="button" onclick="genericPanel.hide();genericAjaxPostAfterSubmitEvent.unsubscribeAll();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
		
		<br>
		</form>
	{else}
		You've customized all the available templates.<br>
		<br>
		<button type="button" onclick="genericPanel.hide();genericAjaxPostAfterSubmitEvent.unsubscribeAll();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
	{/if}
</div>