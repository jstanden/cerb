<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="saveImportTemplatesPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="portal" value="{$portal}">

<h1>{'common.import'|devblocks_translate|capitalize}</h1>

<b>Import File:</b> (.xml)<br>
<input type="file" name="import_file" size="45"><br>
<br>

<button type="button" onclick="genericPanel.hide();this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/import1.png{/devblocks_url}" align="top"> {'common.import'|devblocks_translate|capitalize}</button>
<button type="button" onclick="genericPanel.hide();genericAjaxPostAfterSubmitEvent.unsubscribeAll();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

</form>