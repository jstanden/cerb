<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOrgPeek" name="formOrgPeek" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$contact->id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{$translate->_('common.name')|capitalize}: </td>
		<td width="100%"><input type="text" name="org_name" value="{$contact->name|escape}" style="width:98%;" class="required"></td>
	</tr>
	<tr>
		<td align="right" valign="top">{$translate->_('contact_org.street')|capitalize}: </td>
		<td><textarea name="street" style="width:98%;height:50px;">{$contact->street}</textarea></td>
	</tr>
	<tr>
		<td align="right">{$translate->_('contact_org.city')|capitalize}: </td>
		<td><input type="text" name="city" value="{$contact->city|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">{$translate->_('contact_org.province')|capitalize}.: </td>
		<td><input type="text" name="province" value="{$contact->province|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">{$translate->_('contact_org.postal')|capitalize}: </td>
		<td><input type="text" name="postal" value="{$contact->postal|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">{$translate->_('contact_org.country')|capitalize}: </td>
		<td>
			<input type="text" name="country" id="org_country_input" value="{$contact->country|escape}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td align="right">{$translate->_('contact_org.phone')|capitalize}: </td>
		<td><input type="text" name="phone" value="{$contact->phone|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">{if !empty($contact->website)}<a href="{$contact->website|escape}" target="_blank">{$translate->_('contact_org.website')|capitalize}</a>{else}{$translate->_('contact_org.website')|capitalize}{/if}: </td>
		<td><input type="text" name="website" value="{$contact->website|escape}" style="width:98%;" class="url"></td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
<br>

{if $active_worker->hasPriv('core.addybook.org.actions.update')}
	<button type="button" onclick="if($('#formOrgPeek').validate().form()) { genericAjaxPopupPostCloseReloadView('peek','formOrgPeek', '{$view_id}'); } "><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if $active_worker->hasPriv('core.addybook.org.actions.delete')}{if !empty($contact->id)}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this contact?')){this.form.do_delete.value='1';genericAjaxPopupClose('peek');genericAjaxPost('formOrgPeek', 'view{/literal}{$view_id}{literal}');}{/literal}"><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}{/if}
{else}
	<div class="error">{$translate->_('error.core.no_acl.edit')}</div>
{/if}
 &nbsp;
 {if !empty($contact->id)}<a href="{devblocks_url}&c=contacts&a=orgs&display=display&id={$contact->id}{/devblocks_url}">{$translate->_('addy_book.peek.view_full')}</a>{/if}
<br>
</form>

<script language="JavaScript1.2" type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('dialogopen',function(event,ui) {
		// Title
		$popup.dialog('option','title', "{'contact_org.name'|devblocks_translate|capitalize|escape:'quotes'}");
		// Autocomplete
		ajax.countryAutoComplete('#org_country_input');
		// Form validation
	    $("#formOrgPeek").validate();
		$('#formOrgPeek :input:text:first').focus();
	} );
</script>
