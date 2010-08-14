<div class="block" style="margin-bottom:10px;">
	<table cellspacing="0" cellpadding="2" border="0" width="220">
	<tr>
		<td><h2>Overview</h2></td>
	</tr>
	<tr>
		<td>
			<a href="{devblocks_url}c=tickets&a=overview&filter=all{/devblocks_url}">{'common.reset'|devblocks_translate|escape}</a><br>
		</td>
	</tr>
	</table>
</div>

{if empty($open_counts) && empty($waiting_counts) && empty($worker_counts)}

{else}

	{if !empty($open_counts)}
	<div class="block" style="margin-bottom:10px;">
	<table cellspacing="0" cellpadding="2" border="0" width="220">
	<tr>
		<td><h2>Open</h2></td>
	</tr>
	{foreach from=$open_counts item=category key=category_id}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=tickets&a=overview&filter=open&group={$category_id}{/devblocks_url}" style="font-weight:bold;">{$category.label}</a> <div class="badge">{$category.hits}</div> 
				{if isset($category.children) && !empty($category.children)}
				<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
					{foreach from=$category.children item=subcategory key=subcategory_id}
						<a href="{devblocks_url}c=tickets&a=overview&filter=open&group={$category_id}&bucket={$subcategory_id}{/devblocks_url}">{$subcategory.label}</a> <div class="badge badge-lightgray">{$subcategory.hits}</div><br>
					{/foreach}
				</div>
				{/if}
			</td>
		</tr>
	{/foreach}
	</table>
	</div>
	{/if}
	
	{if !empty($waiting_counts)}
	<div class="block" style="margin-bottom:10px;">
	<table cellspacing="0" cellpadding="2" border="0" width="220">
	<tr>
		<td><h2>Waiting</h2></td>
	</tr>
	{foreach from=$waiting_counts item=category key=category_id}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=tickets&a=overview&filter=waiting&group={$category_id}{/devblocks_url}" style="font-weight:bold;">{$category.label}</a> <div class="badge">{$category.hits}</div> 
				{if isset($category.children) && !empty($category.children)}
				<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
					{foreach from=$category.children item=subcategory key=subcategory_id}
						<a href="{devblocks_url}c=tickets&a=overview&filter=waiting&group={$category_id}&bucket={$subcategory_id}{/devblocks_url}">{$subcategory.label}</a> <div class="badge badge-lightgray">{$subcategory.hits}</div><br>
					{/foreach}
				</div>
				{/if}
			</td>
		</tr>
	{/foreach}
	</table>
	</div>
	{/if}
	
	{if !empty($worker_counts)}
	<div class="block" style="margin-bottom:10px;">
	<table cellspacing="0" cellpadding="2" border="0" width="220">
	<tr>
		<td><h2>Workers</h2></td>
	</tr>
	{foreach from=$worker_counts item=category key=category_id}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=tickets&a=overview&filter=worker&worker={$category_id}{/devblocks_url}" style="font-weight:bold;">{$category.label}</a> <div class="badge">{$category.hits}</div> 
				{if isset($category.children) && !empty($category.children)}
				<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
					{foreach from=$category.children item=subcategory key=subcategory_id}
						<a href="{devblocks_url}c=tickets&a=overview&filter=worker&worker={$category_id}&group={$subcategory_id}{/devblocks_url}">{$subcategory.label}</a> <div class="badge badge-lightgray">{$subcategory.hits}</div><br>
					{/foreach}
				</div>
				{/if}
			</td>
		</tr>
	{/foreach}
	</table>
	</div>
	{/if}

{/if}

</div>
<br>
