<h1>Import Articles</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data" style="padding-bottom:10px;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="doKbImportXml">
	
	<b>Import File (.xml):</b><br> 
	<input type="file" name="xml_file" size="32"><br>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Upload</button>
	<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
</form>
