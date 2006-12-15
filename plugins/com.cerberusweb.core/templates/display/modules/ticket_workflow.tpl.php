<img src="images/bookmark_add.gif" align="absmiddle"> <a href="javascript:;" onclick="toggleDiv('displayWorkflowOptions');">Add Tags</a>
| <img src="images/flag_red.gif" align="absmiddle"> <a href="#">Assign Agents</a>
| <img src="images/businessman_add.gif" align="absmiddle"> <a href="#">Suggest Agents</a>
<br>

{assign var=tags value=$ticket->getTags()}
{if !empty($tags)}
<br>
<b>Applied Tags:</b><br>
<div style="width:98%;background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
{foreach from=$tags item=tag name=tags}
	<a href="javascript:;" onclick="displayAjax.showTag('{$tag->id}',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
{/foreach}
</div>
{/if}

<span style="display:none;" id="displayWorkflowOptions">
<input type="hidden" name="c" value="core.display.module.workflow">
<input type="hidden" name="a" value="applyTags">
<input type="hidden" name="id" value="{$ticket->id}">

<br>
<b>Add tags separated by commas:</b><br>
<textarea style="width:98%;height:50px;margin:2px;background-color:rgb(255,255,255);border:1px solid rgb(200,200,200);" name="tagEntry"></textarea>
<br>
<input type="button" value="Apply Tags" onclick="displayAjax.applyTagsToTicket();">
<input type="button" value="Cancel" onclick="toggleDiv('displayWorkflowOptions');">
<br>
<br>
<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td width="50%">
			<b>Favorite Tags:</b> (<a href="#">manage</a>)<br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$favoriteTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('{$moduleLabel}_form',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
			{/foreach}
			</div>
		</td>
		<td width="50%">
			<b>Suggested Tags:</b><br>
			<div style="background-color:rgb(250,250,255);border:1px solid rgb(200,200,200);margin:2px;padding:5px;">
			{foreach from=$suggestedTags item=tag name=tags}
				<a href="javascript:;" onclick="appendTextboxAsCsv('{$moduleLabel}_form',this);">{$tag->name}</a>{if !$smarty.foreach.tags.last}, {/if}
			{/foreach}
			</div>
		</td>
	</tr>
</table>
</span>