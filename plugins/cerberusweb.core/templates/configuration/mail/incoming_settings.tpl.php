<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><h2>Incoming Mail</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}#incoming" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveIncomingMailSettings">

			<b>Reply to All:</b><br>
			<label><input type="checkbox" name="parser_autoreq" value="1" {if $settings->get('parser_autoreq')}checked{/if}> Add All TO/CC Recipients As Ticket Requesters</label><br>
			<br>

			<div style="padding-left:10px;">
				<b>Always Exclude These Recipients:</b><br>
				<textarea name="parser_autoreq_exclude" rows="4" cols="76">{$settings->get('parser_autoreq_exclude')|escape:"html"}</textarea><br>
				<i>use * (asterisk) for wildcards, like: *@mydomain.com</i><br>
				<br>
			</div>

			<b>Attachments:</b><br>
			<label><input type="checkbox" name="attachments_enabled" value="1" {if $settings->get('attachments_enabled')}checked{/if}> Allow Incoming Attachments</label><br>
			<br>
			
			<div style="padding-left:10px;">
				<b>Maximum Attachment Size:</b><br>
				<input type="text" name="attachments_max_size" value="{$settings->get('attachments_max_size')|escape:"html"}" size="5"> MB<br>
				<br>
			</div>

			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>
