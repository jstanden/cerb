<div class="block">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Attachment Storage</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveAttachmentLocation">
			
			This is the location where Cerberus will store attachments, both received via email and uploaded by Workers.
			<br>&nbsp;&nbsp;&nbsp;&nbsp;To save to disk, use a complete file path (e.g. C:\httpd\cerb4\temp\ or /usr/local/cerb4/tmp/).
			<br>&nbsp;&nbsp;&nbsp;&nbsp;To use an FTP server, use a full FTP URI (e.g. ftps://user:password@hostname/file/path/).
			<br>
			<br>
			
			<b>Attachment Storage Location:</b><br>
			<input type="text" name="attachmentlocation" value="{$settings->get('save_file_path')|escape:"html"}" size="64"><br>
			<br>
			
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>