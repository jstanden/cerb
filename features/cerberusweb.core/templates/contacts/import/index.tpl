<div class="block">
<H2>{$translate->_('addy_book.tab.import')}</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="formContactImport">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="parseUpload">

<b>{$translate->_('addy_book.import.record_type')}:</b><br>
<label><input type="radio" name="type" value="orgs" checked="checked">{$translate->_('addy_book.tab.organizations')}</label>
<label><input type="radio" name="type" value="addys">{$translate->_('addy_book.tab.addresses')}</label>
<br>
<br>

<b>{$translate->_('addy_book.import.upload_csv')}:</b> {$translate->_('addy_book.import.upload_csv.tip')}<br>
<input type="file" name="csv_file" size="45"><br>
<br>

{if $active_worker->hasPriv('core.addybook.import')}
<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.upload')|capitalize}</button><br>
{/if}
</form>
</div>
<br>
