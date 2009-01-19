<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/office-building.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="98%"><h1>Organization</h1></td>
		<td align="left" width="1%" nowrap="nowrap">{if !empty($contact->id)}<a href="{devblocks_url}&c=contacts&a=orgs&id={$contact->id}{/devblocks_url}">view full record</a>{/if}</td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<!-- <input type="hidden" name="action_id" value="{$id}"> -->
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$contact->id}">
<input type="hidden" name="do_delete" value="0">

<div style="height:400px;overflow:auto;margin:2px;padding:3px;">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Name: </td>
		<td width="100%"><input type="text" name="org_name" value="{$contact->name|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Account #: </td>
		<td width="100%"><input type="text" name="account_num" value="{$contact->account_number|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right" valign="top">Street: </td>
		<td><textarea name="street" style="width:98%;height:50px;">{$contact->street}</textarea></td>
	</tr>
	<tr>
		<td align="right">City: </td>
		<td><input type="text" name="city" value="{$contact->city|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">State/Prov.: </td>
		<td><input type="text" name="province" value="{$contact->province|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Postal: </td>
		<td><input type="text" name="postal" value="{$contact->postal|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Country: </td>
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
		<td align="right">Phone: </td>
		<td><input type="text" name="phone" value="{$contact->phone|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Fax: </td>
		<td><input type="text" name="fax" value="{$contact->fax|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Website: </td>
		<td><input type="text" name="website" value="{$contact->website|escape}" style="width:98%;"></td>
	</tr>
	{if !empty($slas)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Service Level: </td>
		<td width="100%">
			<select name="sla_id">
				<option value="0">-- none --</option>
				{foreach from=$slas item=sla key=sla_id}
					<option value="{$sla_id}" {if $sla_id==$contact->sla_id}selected{/if}>{$sla->name|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{/if}
	
	<!-- Custom Fields -->
	<tr>
		<td colspan="2" align="center"><b>Custom Fields</b></td>
	</tr>
	{foreach from=$org_fields item=f key=f_id}
		<tr>
			<td valign="top" width="25%" align="right">
				<input type="hidden" name="field_ids[]" value="{$f_id}">
				<span style="font-size:90%;">{$f->name}:</span>
			</td>
			<td valign="top" width="75%">
				{if $f->type=='S'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$org_field_values.$f_id|escape}"><br>
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$org_field_values.$f_id}</textarea><br>
				{elseif $f->type=='C'}
					<input type="checkbox" name="field_{$f_id}" value="1" {if $org_field_values.$f_id}checked{/if}><br>
				{elseif $f->type=='D'}
					<select name="field_{$f_id}">{* [TODO] Fix selected *}
						<option value=""></option>
						{foreach from=$f->options item=opt}
						<option value="{$opt|escape}" {if $opt==$org_field_values.$f_id}selected{/if}>{$opt}</option>
						{/foreach}
					</select><br>
				{elseif $f->type=='E'}
					<input type="text" name="field_{$f_id}" size="30" maxlength="255" value="{if !empty($org_field_values.$f_id)}{$org_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
					<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
				{/if}	
			</td>
		</tr>
	{/foreach}
	
</table>

</div>

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formBatchUpdate', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
{if !empty($contact->id)}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this contact?')){this.form.do_delete.value='1';genericPanel.hide();genericAjaxPost('formBatchUpdate', 'view{/literal}{$view_id}{literal}');}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<br>
</form>
