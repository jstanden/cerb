<form action="#" method="post" id="frmEditWorkspaceTab" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="doEditWorkspaceTabJson">
<input type="hidden" name="id" value="{$workspace_tab->id}">
<input type="hidden" name="workspace_page_id" value="{$workspace_tab->workspace_page_id}">
{if !empty($workspace_tab)}<input type="hidden" name="do_delete" value="0">{/if}

<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:5px;">
	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.name'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<input type="text" name="name" value="{$workspace_tab->name}" size="35" style="width:100%;">
		</td>
	</tr>
	
	{if !empty($workspace_tab->extension_id)}
	<tr>
		<td>
			<b>{'common.type'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			{$tab_extension = DevblocksPlatform::getExtension($workspace_tab->extension_id, false)}
			{if $tab_extension}
				{$tab_extension->params.label|devblocks_translate|capitalize}
			{/if}
		</td>
	</tr>
	{/if}
	
</table>

{$tab_extension = DevblocksPlatform::getExtension($workspace_tab->extension_id, true)}
{if $tab_extension && method_exists($tab_extension,'renderTabConfig')}
	{$tab_extension->renderTabConfig($workspace_page, $workspace_tab)}
{/if}

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete this workspace tab?</legend>
	
	<button type="button" class="red" onclick="$('#frmEditWorkspaceTab').find('input:hidden[name=do_delete]').val('1');genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspaceTab','',false,'workspace_delete');">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('fieldset').fadeOut().siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="toolbar">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($workspace_tab)}<button type="button" onclick="$(this).closest('div.toolbar').fadeOut().siblings('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if !empty($workspace_tab)}Edit Tab{else}Add Tab{/if}");
		$('#frmEditWorkspaceTab').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
		
		$frm = $('#frmEditWorkspaceTab');
		
		$frm.find('button.submit').click(function(e) {
			genericAjaxPost('frmEditWorkspaceTab', '', null, function(json) {
				event = jQuery.Event('workspace_save');
						
				if(null != json.name)
					event.name = json.name;

				genericAjaxPopupClose('peek', event);
			});
		});
		
		$frm.find('input:text:first').focus().select();
	});
</script>
