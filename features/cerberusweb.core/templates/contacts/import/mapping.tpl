<div id="headerSubMenu">
	<div style="padding:5px;">
		<a href="{devblocks_url}c=contacts{/devblocks_url}">{$translate->_('core.menu.address_book')|lower}</a>
		 &raquo; 
		<a href="{devblocks_url}c=contacts&a=import{/devblocks_url}">{$translate->_('addy_book.tab.import')|lower}</a>
	</div>
</div>

<div class="block">
{assign var=type value=$visit->get('import.last.type')}
<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="formContactImport">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doImport">

{if $type=="orgs"}
<H2>{$translate->_('addy_book.tab.organizations')}</H2>
{elseif $type=="addys"}
<H2>{$translate->_('addy_book.tab.addresses')}</H2>
{/if}
<br>

<table cellpadding="2" cellspacing="0" border="0">
<tr>
	<td><b>{$translate->_('addy_book.import.from_file')}:</b></td>
	<td style="padding-left:10px;"><b>{$translate->_('addy_book.import.to_field')}:</b></td>
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
		
		{if !empty($custom_fields)}
		<optgroup label="- {$translate->_('common.custom_fields')|capitalize} -">
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

<b>{$translate->_('common.options')|capitalize}:</b><br>
<label><input type="checkbox" name="include_first" value="1"> {$translate->_('addy_book.import.options.import_first_row')}</label><br>
<label><input type="checkbox" name="is_blank_unset" value="1"> {$translate->_('addy_book.import.options.blank_fields')}</label><br>
{if $type=="addys"}
<label><input type="checkbox" name="replace_passwords" value="1"> {$translate->_('addy_book.import.options.replace_passwords')}</label><br>
{/if}
<br>

<h2>{$translate->_('common.synchronize')|capitalize}</h2>
<br>

<b>{$translate->_('addy_book.import.dupes.check')}:</b><br>
<select name="sync_column">
	<option value="">-- {$translate->_('addy_book.import.dupes.dont_check')} --</option>
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
