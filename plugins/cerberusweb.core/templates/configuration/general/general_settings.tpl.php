<div class="block">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Helpdesk Settings</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveSettings">
			
			<b>Helpdesk Title:</b><br>
			<input type="text" name="title" value="{$settings->get('helpdesk_title')|escape:"html"}" size="64"><br>
			<br>
			
			<b>Timezone:</b><br>
			<select name="timezone">
				<option value="">---</option>
			</select><br>
			<br>
			
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>