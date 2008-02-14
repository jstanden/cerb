<h1>Import Opportunities</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data" style="padding-bottom:10px;">
	<input type="hidden" name="c" value="crm">
	<input type="hidden" name="a" value="doOppImportXml">
	
	<b>Import File (.xml):</b><br> 
	<input type="file" name="xml_file" size="32"><br>
	<br>
	
	<b>Into Campaign:</b><br>
	<select name="import_campaign_id">
		{foreach from=$campaigns item=campaign key=campaign_id}
			<option value="{$campaign_id}">{$campaign->name}</option>
		{/foreach}
	</select><br>
	<br>

	<b>Assign to:</b><br>
	<select name="import_worker_id">
		<option value="0"> - nobody -</option>
		{foreach from=$workers item=worker key=worker_id}
			<option value="{$worker_id}">{$worker->getName()}</option>
		{/foreach}
	</select><br>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Upload</button>
	<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
</form>
