<table cellspacing="0" cellpadding="2" border="0" width="100%" style="margin-left:25px;">
{foreach from=$subtotal_counts item=category}
	<tr>
		<td style="padding-right:10px;" align="right" width="0%" nowrap="nowrap" valign="top">
			<div class="badge">{$category.hits}</div>
		</td>
		<td valign="top" width="99%">
			<span style="font-weight:bold;" title="{$category.label}">{$category.label}</span>
		</td>
	</tr>
	{if isset($category.children) && !empty($category.children)}
	{foreach from=$category.children item=subcategory}
	<tr>
		<td style="padding-right:10px;" align="right" width="0%" nowrap="nowrap" valign="top">
			<div class="badge badge-lightgray">{$subcategory.hits}</div>
		</td>
		<td style="padding-left:10px;" width="99%" valign="top">
			<span>{$subcategory.label}</span>
		</td>
	</tr>
	{/foreach}
	{/if}
{/foreach}
</table>	

<script type="text/javascript">
</script>