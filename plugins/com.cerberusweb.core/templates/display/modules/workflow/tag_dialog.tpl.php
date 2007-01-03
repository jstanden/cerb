<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="images/bookmark.gif" align="absmiddle"><img src="spacer.gif" width="5" height="1"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Tag: {$tag->name}</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="displayAjax.tagDialog.hide();"></form></td>
	</tr>
</table>
<form method="POST" action="javascript:;" id="tagPanel">
	<input type="hidden" name="c" value="core.display.module.workflow">
	<input type="hidden" name="a" value="saveTagDialog">
	<input type="hidden" name="id" value="{$tag->id}">
	
	<b>Related terms &amp; phrases to find in conversation:</b><br>
	<textarea style="width:98%;height:50px;" name="terms">{foreach from=$tag->getTerms() item=term name=terms}{$term->term|cat:"\n"}{/foreach}</textarea><br>
	<i>(enter one phrase per line)</i><br>
	
	{if !empty($ticket_id)}
	<br>
	<input type="hidden" name="ticket_id" value="{$ticket_id}">
	<label><input type="checkbox" name="untag" value="1"> Remove '{$tag->name}' from current ticket</label><br>
	{/if}
	
	<br>
	<input type="button" value="{$translate->say('common.save_changes')}" onclick="displayAjax.postShowTag();">
</form>