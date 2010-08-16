<table cellspacing="0" cellpadding="2" border="0" width="220" style="padding-top:5px;">
{foreach from=$counts item=category}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			<span style="font-weight:bold;">{$category.label}</span> <div class="badge">{$category.hits}</div>
			{if isset($category.children) && !empty($category.children)}
			{foreach from=$category.children item=subcategory}
			<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
				<span>{$subcategory.label}</span> <div class="badge badge-lightgray">{$subcategory.hits}</div><br>
			</div>
			{/foreach}
			{/if}
		</td>
	</tr>
{/foreach}
</table>