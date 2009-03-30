<h1>Import Opportunities</h1>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="frmOppImport">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="parseUpload">

<b>Upload .CSV File:</b> (any format; the next step will let you choose where the data goes)<br>
<input type="file" name="csv_file" size="45"><br>
<br>

{if $active_worker->hasPriv('crm.opp.actions.import')}<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.upload')|capitalize}</button>{/if}
<button type="button" onclick="document.location.href='{devblocks_url}c=activity&a=opps{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>
