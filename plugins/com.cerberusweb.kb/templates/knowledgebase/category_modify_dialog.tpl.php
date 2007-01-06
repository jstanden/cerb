<form>
<input type="hidden" name="c" value="core.module.kb">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$node->id}">
<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%" nowrap="nowrap"><img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/cerb_fnr_header.jpg"></td>
	</tr>
</table>

{if !empty($node)}
<b>Modify Category '{$node->name}':</b><br>
{else}
<b>Add Category:</b><br>
{/if}

<table cellspacing="0" cellpadding="3" width="98%">
	<tr>
		<td align="right">Label:</td>
		<td>
			<input type="text" name="name" size="35" value="{$node->name|escape:"htmlall"}">
		</td>
	</tr>
	<tr>
		<td align="right" valign="top">Parent:</td>
		<td>
		<div style="height:150px;overflow:auto;background-color:rgb(255,255,255);border:1px solid rgb(180,180,180);margin:2px;padding:3px;">
			<label><input type="radio" name="parent_id" value="0" {if $node->parent_id==0}checked{/if}><b>Top Level</b></label><br>
			{foreach from=$sorted key=si item=level name=sorted}
				{assign var=category value=$tree.$si}
				{if !empty($category)}
					<label><input type="radio" name="parent_id" value="{$category->id}" {if $parent==$category->id}checked{/if}>
					{if $level>2}<img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" align="absmiddle" width="{math equation="(x-2)*16" x=$level}" height="1">{/if}
					{if $level>1}<img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/tree_cap.gif" align="absmiddle">{/if}
					<img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/folder.gif" align="absmiddle"> <b>{$category->name}</b>
					</label><br>
				{/if}
			{/foreach}
		</div>
		</td>
	</tr>
</table>
<br>
<input type="button" value="{$translate->say('common.save_changes')|capitalize}" onclick="kbAjax.categoryModifyPanel.hide();">
<input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="kbAjax.categoryModifyPanel.hide();">
</form>