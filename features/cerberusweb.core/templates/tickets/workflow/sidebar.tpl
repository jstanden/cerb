<div class="block">
<table cellspacing="0" cellpadding="2" border="0" width="220">
<tr>
	<td><h2>{$translate->_('common.available')|capitalize}</h2></td>
</tr>

{if !empty($counts)}
{foreach from=$counts item=category key=group_id}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			<a href="{devblocks_url}c=tickets&a=workflow&filter=group&group={$group_id}{/devblocks_url}" style="font-weight:bold;">{$category.label}</a> <div class="badge">{$category.hits}</div> 
			{if isset($category.children) && !empty($category.children)}
			<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
				{foreach from=$category.children item=subcategory key=bucket_id}
					<a href="{devblocks_url}c=tickets&a=workflow&filter=group&group={$group_id}&bucket={$bucket_id}{/devblocks_url}">{$subcategory.label}</a> <div class="badge badge-lightgray">{$subcategory.hits}</div><br>
				{/foreach}
			</div>
			{/if}
		</td>
	</tr>
{/foreach}
{else}
	<tr>
		<td>
			{'mail.overview.all_done_text'|devblocks_translate|escape}
		</td>
	</tr>
{/if}

<tr>
	<td>
		<div style="display:none;visibility:hidden;">
			<button id="btnWorkflowListAll" onclick="document.location='{devblocks_url}c=tickets&a=workflow&all=all{/devblocks_url}';"></button>
		</div>
		<div style="margin-top:2px;color:rgb(150,150,150);">
			(<b>a</b>) <a href="javascript:;" onclick="document.getElementById('btnWorkflowListAll').click();" style="color:rgb(150,150,150);">{$translate->_('mail.overview.all_groups')|lower}</a>
		</div>
	</td>
</tr>
</table>
</div>
<br>
