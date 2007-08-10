{include file="file:$path/contacts/menu.tpl.php"}

<div class="block">
{assign var=type value=$visit->get('import.last.type')}
<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="formContactImport">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doImport">

{if $type=="orgs"}
<H2>Import Organizations</H2>
{elseif $type=="addys"}
<H2>Import E-mail Addresses</H2>
{/if}
<br>

<table cellpadding="2" cellspacing="0" border="0">
<tr>
	<td><b>First Row from File:</b></td>
	<td><b>Map to Field:</b></td>
</tr>
{foreach from=$parts item=part key=pos}
<tr>
	<td>
	{$part|capitalize}: 
	<input type="hidden" name="pos[]" value="{$pos}">
	</td>
	<td>
	<select name="field[]">
		<option value=""></option>
		{foreach from=$fields item=label key=field}
		{if $field == 'id'}
		{elseif $field == 'sync_id'}
		{else}
			<option value="{$field}">{$label|capitalize}</option>
		{/if}
		{/foreach}
	</select>
	</td>
</tr>
{/foreach}
</table>
<br>

<b>Include the sample record above in the import?</b><br>
<label><input type="checkbox" name="include_first" value="1"> Yes, the sample record above includes real data.</label><br>
<br>

<h2>Synchronization</h2>
<br>

<b>Check for duplicates using:</b><br>
<select name="sync_column">
	<option value="">-- don't check for duplicates --</option>
	{if $type=="orgs"}
		<option value="name" selected>{$fields.name|capitalize}</option>
		<!-- 
		<option value="account_number">{$fields.account_number|capitalize}</option>
		<option value="phone">{$fields.phone|capitalize}</option>
		 -->
	{elseif $type=="addys"}
		<option value="email" selected>{$fields.email|capitalize}</option>
		<!-- 
		<option value="phone">{$fields.phone|capitalize}</option>
		-->
	{/if}
</select><br>
<br>

<br>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.upload')|capitalize}</button>
<button type="button" onclick="document.location='{devblocks_url}c=contacts&a=import{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>
</div>
<br>
