<table cellpadding="2" cellspacing="0" border="0" width="100%" class="configTable">
	<tr>
		<td class="configTableTh">Attachment Storage</td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveAttachmentLocation">
			
			This is the location where Cerberus will store attachments, both received via email and uploaded by Workers.
			<br>&nbsp;&nbsp;&nbsp;&nbsp;To save to disk, use a complete file path (e.g. C:\httpd\cerb4\temp\ or /usr/local/cerb4/tmp/).
			<br>&nbsp;&nbsp;&nbsp;&nbsp;To use an FTP server, use a full FTP URI (e.g. ftps://user:password@hostname/file/path/).
			<br><b>Attachment Storage Location:</b>
			<input type="text" name="attachmentlocation" value="{$attachmentlocation|escape:"html"}" size="45">
			
			<br>
			<input type="submit" value="Save Changes">
			</form>
		</td>
	</tr>
</table>
