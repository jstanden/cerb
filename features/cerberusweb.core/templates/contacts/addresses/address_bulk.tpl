<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="address_ids" value="">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>

<label><input type="radio" name="filter" value="" {if empty($address_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($address_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<br>
<br>

<H2>{$translate->_('common.bulk_update.do')|capitalize}:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap">{$translate->_('contact_org.name')|capitalize}:</td>
		<td width="100%">
			<input type="text" name="contact_org" id="orginput" value="" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">{$translate->_('address.is_banned')|capitalize}:</td>
		<td width="100%"><select name="is_banned">
			<option value=""></option>
			<option value="0">{$translate->_('common.no')|capitalize}</option>
			<option value="1">{$translate->_('common.yes')|capitalize}</option>
      	</select></td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}	
<br>

{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="ajax.saveAddressBatchPanel('{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{/if}
<button type="button" onclick="genericPanel.dialog('close');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>

<script language="JavaScript1.2" type="text/javascript">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
		ajax.orgAutoComplete('#orginput');
	} );
</script>
