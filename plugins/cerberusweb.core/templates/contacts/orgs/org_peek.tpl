<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/office-building.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="98%"><h1>{$translate->_('contact_org.name')|capitalize}</h1></td>
		<td align="left" width="1%" nowrap="nowrap">{if !empty($contact->id)}<a href="{devblocks_url}&c=contacts&a=orgs&display=display&id={$contact->id}{/devblocks_url}">{$translate->_('addy_book.peek.view_full')}</a>{/if}</td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$contact->id}">
<input type="hidden" name="do_delete" value="0">

<div style="height:400px;overflow:auto;margin:2px;padding:3px;">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{$translate->_('common.name')|capitalize}: </td>
		<td width="100%"><input type="text" name="org_name" value="{$contact->name|escape}" style="width:98%;"></td>
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
		
			<div id="org_country_autocomplete" style="width:98%;" class="yui-ac">
				<input type="text" name="country" id="org_country_input" value="{$contact->country|escape}" class="yui-ac-input">
				<div id="org_country_container" class="yui-ac-container"></div>
			</div>			
			<br>
			<br>
		</td>
	</tr>
	<tr>
		<td align="right">{$translate->_('contact_org.phone')|capitalize}: </td>
		<td><input type="text" name="phone" value="{$contact->phone|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">{if !empty($contact->website)}<a href="{$contact->website|escape}" target="_blank">{$translate->_('contact_org.website')|capitalize}</a>{else}{$translate->_('contact_org.website')|capitalize}{/if}: </td>
		<td><input type="text" name="website" value="{$contact->website|escape}" style="width:98%;"></td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
<br>

</div>

{if $active_worker->hasPriv('core.addybook.org.actions.update')}
	<button type="button" onclick="genericPanel.hide();genericAjaxPost('formBatchUpdate', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
	{if $active_worker->hasPriv('core.addybook.org.actions.delete')}{if !empty($contact->id)}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this contact?')){this.form.do_delete.value='1';genericPanel.hide();genericAjaxPost('formBatchUpdate', 'view{/literal}{$view_id}{literal}');}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}{/if}
{else}
	<div class="error">{$translate->_('errors.core.no_acl.edit')}</div>
{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>
