{if empty($group_counts)}
<div class="block">
<h2>{$translate->_('mail.overview.all_done')}</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
	<tr>
		<td>{$translate->_('mail.overview.all_done_text')}</td>
	</tr>
</table>
</div>
<br>
{/if}

{if !empty($group_counts)}
<div class="block">
<table cellspacing="0" cellpadding="2" border="0" width="220">
<tr>
	<td><h2>{$translate->_('common.available')|capitalize}</h2></td>
</tr>
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$group_counts.$group_id}
	{if !empty($counts.total)}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=tickets&a=workflow&s=group&gid={$group_id}{/devblocks_url}" style="font-weight:bold;">{$groups.$group_id->name}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span> 
				<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
				{if !empty($counts.0)}<a href="{devblocks_url}c=tickets&a=workflow&s=group&gid={$group_id}&bid=0{/devblocks_url}">{$translate->_('common.inbox')|capitalize}</a> <span style="color:rgb(150,150,150);">({$counts.0})</span><br>{/if}
				{foreach from=$group_buckets.$group_id key=bucket_id item=b}
					{if !empty($counts.$bucket_id)}	<a href="{devblocks_url}c=tickets&a=workflow&s=group&gid={$group_id}&bid={$bucket_id}{/devblocks_url}">{$b->name}</a> <span style="color:rgb(150,150,150);"> ({$counts.$bucket_id})</span><br>{/if}
				{/foreach}
				</div>
			</td>
		</tr>
	{/if}
{/foreach}
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
{/if}
