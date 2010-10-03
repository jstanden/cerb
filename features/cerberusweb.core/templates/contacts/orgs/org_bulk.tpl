<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doOrgBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="org_ids" value="{$org_ids}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($org_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
	<label><input type="radio" name="filter" value="checks" {if !empty($org_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
</fieldset>

<fieldset>
	<legend>Set Fields</legend>
	
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('contact_org.country')|capitalize}:</td>
			<td width="100%">
				<input type="text" name="country" value="" size="35">
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.owners'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-worker add"><span class="cerb-sprite sprite-add"></span></button>
				<br>
				<button type="button" class="chooser-worker remove"><span class="cerb-sprite sprite-forbidden"></span></button>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>Set Custom Fields</legend>
	{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}	
</fieldset>
{/if}

{if $active_worker->hasPriv('core.addybook.org.actions.update')}<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if}
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'common.bulk_update'|devblocks_translate|escape:'quotes'}");
		
		$('#formBatchUpdate button.chooser-worker').each(function() {
			$button = $(this);
			context = 'cerberusweb.contexts.worker';
			
			if($button.hasClass('remove'))
				ajax.chooser(this, context, 'do_owner_remove_ids');
			else
				ajax.chooser(this, context, 'do_owner_add_ids');
		});
	});
</script>
