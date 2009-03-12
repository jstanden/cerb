<div class="block">
<H2>Import Records</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="formContactImport">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="parseUpload">

<b>Record Type:</b><br>
<label><input type="radio" name="type" value="orgs" checked="checked">Organizations</label>
<label><input type="radio" name="type" value="addys">E-mail Addresses</label>
<br>
<br>

<b>Upload .CSV File:</b> (any format; the next step will let you choose where the data goes)<br>
<input type="file" name="csv_file" size="45"><br>
<br>

{if $active_worker->hasPriv('core.addybook.import')}
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.upload')|capitalize}</button><br>
{/if}
</form>
</div>
<br>
