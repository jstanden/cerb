<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Campaigns</h1>
	</td>
	<td width="99%" valign="middle">
		{include file="$path/crm/menu.tpl.php"}
	</td>
</tr>
</table>

<!-- 
<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data" style="padding-bottom:10px;">
	<input type="hidden" name="c" value="crm">
	<input type="hidden" name="a" value="doOppImportXml">
	
	<b>Import (XML):</b> 
	<input type="file" name="xml_file">
	<button type="submit">Upload</button>
</form>
 -->

<table cellpadding="0" cellspacing="0" width="100%">

<tr>
	<td width="0%" nowrap="nowrap" valign="top">
	
		{*
		<div style="width:220px;">
			<div class="block">
				<h2>Unassigned</h2>
				<a href="javascript:;">-All-</a><br>
				<a href="javascript:;">Inbox</a> (0)<br>
			</div>
			
			<br>
		
			<div class="block">
				<h2>Assigned</h2>
				{foreach from=$workers item=worker key=worker_id}
					<a href="javascript:;">{$worker->getName()}</a> (0)<br>
				{/foreach}
			</div>
		</div>
		*}
		
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		{* 
		<div id="view{$view->id}">{$view->render()}</div>
		*}
		
		<div class="block">
			<h2>Campaigns</h2>
			{foreach from=$campaigns item=campaign key=campaign_id}
				{$campaign->name}<br>
			{/foreach}
		</div>
		<br>
		
		<div class="block">
			<h2>Add Campaign</h2>
			<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data" style="padding-bottom:10px;">
				<input type="hidden" name="c" value="crm">
				<input type="hidden" name="a" value="doAddCampaign">
				
				<b>Campaign Name:</b><br>
				<input type="text" name="add_campaign_name" size="45" value=""><br>
				<br>
				
				<button type="submit">Submit</button>
			</form>
		</div>
		
	</td>
	
</tr>

</table>