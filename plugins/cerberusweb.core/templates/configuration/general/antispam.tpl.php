<div class="block">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Anti-Spam</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveSpamSettings">
			
			<b>Learning:</b><br>
			<label><input type="checkbox" value="whitelist"> Whitelist a sender after <input type='text' size='3' value='5'> non-spam messages.</label><br>
			<br>			
			
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>