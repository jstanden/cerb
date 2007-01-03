<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="images/bookmark.gif" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><H1>Add Tags</H1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="displayAjax.applyTagDialog.hide();"></form></td>
	</tr>
</table>
<form id="applyTagForm">
<input type="hidden" name="c" value="core.display.module.workflow">
<input type="hidden" name="a" value="applyTags">
<input type="hidden" name="id" value="{$ticket->id}">

<div class="automod">
<b>Add tags separated by commas:</b><br>
<div class="autocomplete" style="width:98%;margin:2px;">
<input type='text' style="background-color:rgb(255,255,255);border:1px solid rgb(200,200,200);width:100%" name="tagEntry" id="tagEntry" class="autoinput"><br>
<div id="myTagContainer" class="autocontainer"></div>
</div>
</div>
<input type="button" value="Apply Tags" onclick="displayAjax.saveApplyTagDialog();">
<br>
<br>
<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td width="50%" valign="top">
			<b>Favorite Tags:</b><br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$favoriteTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('applyTagForm','tagEntry',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
			{/foreach}
			</div>
		</td>
		<td width="50%" valign="top">
			<b>Suggested Tags:</b><br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$suggestedTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('applyTagForm','tagEntry',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
			{/foreach}
			</div>
		</td>
	</tr>
</table>
</form>