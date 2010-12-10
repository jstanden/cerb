<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmServer">
<input type="hidden" name="c" value="datacenter">
<input type="hidden" name="a" value="saveServerPeek">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;">
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmServer','{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id && $active_worker->is_superuser}<button type="button" onclick="if(confirm('Permanently delete this server?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView('peek','frmServer','{$view_id}'); } "><span class="cerb-sprite sprite-forbidden"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'Server'}");
	} );
</script>
