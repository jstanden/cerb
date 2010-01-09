<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>{$translate->_('common.bulk_update')|capitalize}</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="address_ids" value="">
<div style="height:400px;overflow:auto;">

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
			<div id="contactautocomplete" style="width:98%;" class="yui-ac">
				<input type="text" name="contact_org" id="contactinput" value="" class="yui-ac-input">
				<div id="contactcontainer" class="yui-ac-container"></div>
				<br>
				<br>
			</div>
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
</div>

{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="ajax.saveAddressBatchPanel('{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>