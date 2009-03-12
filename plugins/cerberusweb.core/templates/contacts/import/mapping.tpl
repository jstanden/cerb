<div id="headerSubMenu">
	<div style="padding:5px;">
		<a href="{devblocks_url}c=contacts{/devblocks_url}">address book</a>
		 &raquo; 
		<a href="{devblocks_url}c=contacts&a=import{/devblocks_url}">import</a>
	</div>
</div>

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
	<td><b>Columns from your file:</b></td>
	<td style="padding-left:10px;"><b>Set value in field:</b></td>
</tr>
{foreach from=$parts item=part key=pos}
<tr>
	<td>{$part}:<input type="hidden" name="pos[]" value="{$pos}"></td>
	<td style="padding-left:10px;">
	<select name="field[]">
		<option value=""></option>
		{foreach from=$fields item=label key=field}
		{if $field == 'id'}
		{elseif $field == 'sync_id'}
		{else}
			<option value="{$field}">{$label}</option>
		{/if}
		{/foreach}
		<option value="password">Password</option>
		
		{if !empty($custom_fields)}
		<optgroup label="- Custom Fields -">
		{foreach from=$custom_fields item=field}
			<option value="cf_{$field->id}">{$field->name}</option>
		{/foreach}
		{/if}
	</select>
	</td>
</tr>
{/foreach}
</table>
<br>

<b>Options:</b><br>
<label><input type="checkbox" name="include_first" value="1"> Import the first row (only check this if the dropdowns contain real data)</label><br>
<label><input type="checkbox" name="is_blank_unset" value="1"> Blank values for custom fields should clear the field (if unchecked, blank values are skipped)</label><br>
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
