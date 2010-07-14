<div id="divPortalAddTemplate">
	{if !empty($templates)}
		<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmPortalAddTemplatePeek" name="frmPortalAddTemplatePeek" onsubmit="return false;">
		<input type="hidden" name="c" value="community">
		<input type="hidden" name="a" value="saveAddTemplatePeek">
		<input type="hidden" name="view_id" value="{$view_id}">
		<input type="hidden" name="portal" value="{$portal}">
		
		<b>Template:</b><br>
		<select name="template">
			{foreach from=$templates item=template}
			<option value="{$template->plugin_id|escape}:{$template->path|escape}">[{$template->plugin_id|escape}]{*({$template->set})*} {$template->path|escape}</option>
			{/foreach}
		</select><br>
		<br>
		
		{if $active_worker->is_superuser}
			<button type="button" onclick="genericAjaxPost('frmPortalAddTemplatePeek', 'divPortalAddTemplate', '');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
		{else}
			<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
		{/if}
		<br>
		</form>
	{else}
		You've customized all the available templates.<br>
		<br>
	{/if}
</div>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Add Custom Template");
	} );
</script>
