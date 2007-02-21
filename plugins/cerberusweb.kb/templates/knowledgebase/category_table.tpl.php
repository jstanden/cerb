{if $half}
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="50%" valign="top">
		{foreach from=$node->children item=category name=categories}
			
			<img src="{devblocks_url}images/folder.gif{/devblocks_url}" align="absmiddle"> <a href="{devblocks_url}c=kb&id={$category->id}{/devblocks_url}" style="font-weight:bold;">{$category->name}</a> ({$category->hits})<br>
			<ul style="margin:2px;">
				{foreach from=$category->children item=child}
					<li><a href="{devblocks_url}c=kb&id={$child->id}{/devblocks_url}">{$child->name}</a> ({$child->hits})</li>
				{/foreach}
			</ul>
			<br>
			
			{if $half == $smarty.foreach.categories.iteration}
				</td>
				<td width="50%" valign="top">
			{/if}
		{/foreach}
		</td>
	</tr>
	</table>
{else}
	No subcategories.<br>
	<br>
{/if}
