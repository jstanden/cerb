{include file="file:$path/contacts/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Import</h1>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
	</td>
</tr>
</table>

<div class="block">
<H2>Import Records</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="formContactImport">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="parseUpload">

<b>Record Type:</b><br>
<label><input type="radio" name="type" value="orgs" checked>Organizations</label>
<label><input type="radio" name="type" value="addys">E-mail Addresses</label>
<br>
<br>

<b>Upload .CSV File:</b><br>
<input type="file" name="csv_file" size="45"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.upload')|capitalize}</button><br>
</form>
</div>
<br>
