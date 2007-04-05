<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="{devblocks_url}images/bookmark.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><H1>Add Tags</H1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>
<form id="tagChooserForm">
<input type="hidden" name="c" value="core.display.module.workflow">
<input type="hidden" name="a" value="applyTags">
<input type="hidden" name="id" value="{$ticket->id}">
<input type="hidden" name="divName" value="">
<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="top">
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;" id="tagChooserCloud">
				{$cloud->render()}
			</div>
		</td>
		{*
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$favoriteTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('tagChooserForm','tagEntry',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
			{/foreach}
			</div>
		*}
		{*
			{foreach from=$suggestedTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('tagChooserForm','tagEntry',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
		*}
	</tr>
</table>
<input type="button" value="Apply Tags" onclick="displayAjax.saveApplyTagDialog();">
<input type="button" value="Clear" onclick="">
</form>