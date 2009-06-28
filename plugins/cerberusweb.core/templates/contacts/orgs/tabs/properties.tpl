<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmOrgFields">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgProperties">
<input type="hidden" name="org_id" value="{$org_id}">

<blockquote style="margin:10px;">

	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.name'|devblocks_translate}: </td>
			<td width="100%"><input type="text" name="org_name" value="{$contact->name|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right" valign="top">{'contact_org.street'|devblocks_translate}: </td>
			<td><textarea name="street" style="width:98%;height:50px;">{$contact->street}</textarea></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.city'|devblocks_translate}: </td>
			<td><input type="text" name="city" value="{$contact->city|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.province'|devblocks_translate}: </td>
			<td><input type="text" name="province" value="{$contact->province|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.postal'|devblocks_translate}: </td>
			<td><input type="text" name="postal" value="{$contact->postal|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.country'|devblocks_translate}: </td>
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
			<td align="right">{'contact_org.phone'|devblocks_translate}: </td>
			<td><input type="text" name="phone" value="{$contact->phone|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{if !empty($contact->website)}<a href="{$contact->website|escape}" target="_blank">{'contact_org.website'|devblocks_translate}</a>{else}{'contact_org.website'|devblocks_translate}{/if}: </td>
			<td><input type="text" name="website" value="{$contact->website|escape}" style="width:98%;"></td>
		</tr>
	</table>

	{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
	
	<br>
	
	{if $active_worker->hasPriv('core.addybook.org.actions.update')}
		<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
	{else}
		<div class="error">You do not have permission to modify this record.</div>	
	{/if}
</blockquote>

</form>