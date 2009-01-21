{include file="file:$path/contacts/submenu.tpl.php"}

<h1>Import</h1>

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
		<option value="password">Password</option>
	</select>
	</td>
</tr>
{/foreach}
</table>
<br>

<b>Options:</b><br>
<label><input type="checkbox" name="include_first" value="1"> Import the first row (only check this if the dropdowns contain real data)</label><br>
{if $type=="addys"}
<label><input type="checkbox" name="replace_passwords" value="1"> Replace all passwords with import values, even if they already exist</label><br>
{/if}
<br>

<h2>Synchronization</h2>
<br>

<b>Check for duplicates using:</b><br>
<select name="sync_column">
	<option value="">-- don't check for duplicates --</option>
	{if $type=="orgs"}
		<option value="name" selected>{$fields.name|capitalize}</option>
		<!-- 
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
