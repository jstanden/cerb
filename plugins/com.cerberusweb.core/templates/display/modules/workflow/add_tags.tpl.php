<input type="hidden" name="c" value="core.display.module.workflow">
<input type="hidden" name="a" value="applyTags">
<input type="hidden" name="id" value="{$ticket->id}">

<br>
<div class="automod">
<H1>Add Tags</H1>
<b>Add tags separated by commas:</b><br>
<div class="autocomplete" style="width:98%;margin:2px;">
<textarea style="background-color:rgb(255,255,255);border:1px solid rgb(200,200,200);" name="tagEntry" id="tagEntry" class="autoinput"></textarea><br>
<div id="myTagContainer" class="autocontainer"></div>
</div>
</div>
<input type="button" value="Apply Tags" onclick="displayAjax.submitWorkflow();">
<input type="button" value="Cancel" onclick="toggleDiv('displayWorkflowOptions','none');">
<br>
<br>
<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td width="50%" valign="top">
			<b>Favorite Tags:</b><br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$favoriteTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('{$moduleLabel}_form','tagEntry',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
			{/foreach}
			</div>
		</td>
		<td width="50%" valign="top">
			<b>Suggested Tags:</b><br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$suggestedTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('{$moduleLabel}_form','tagEntry',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
			{/foreach}
			</div>
		</td>
	</tr>
</table>
