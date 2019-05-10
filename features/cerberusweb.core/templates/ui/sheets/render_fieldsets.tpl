{$sheet_uid = "sheet{uniqid()}"}
<div id="{$sheet_uid}" class="cerb-data-sheet">
	<table width="100%" cellspacing="0" cellpadding="0" border="0" class="cerb-data-sheet--fieldset">
	{foreach from=$rows item=row name=rows}
		<tbody>
		{foreach from=$columns item=column name=columns}
		{$value = $row[$column.key]}
		{if $value}
			<tr class="cerb-data-sheet--field">
				{if $layout.title_column && $column.key == $layout.title_column}
					<td class="cerb-data-sheet--field-title" colspan="2">
						{$value nofilter}
					</td>
				{else}
					{if $layout.headings}
					<td class="cerb-data-sheet--field-label">
						{$column.label}:
					</td>
					<td>
						{$value nofilter}
					</td>
					{else}
					<td colspan="2">
						{$value nofilter}
					</td>
					{/if}
				{/if}
			</tr>
		{/if}
		{/foreach}
		</tbody>
	{/foreach}
	</table>
	
	{if $layout.paging && $paging}
	<div style="text-align:right;margin-top:5px;">
		(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
		 of {$paging.page.rows.of}) 
	</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $sheet = $('#{$sheet_uid}');
	
	$sheet.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$sheet.find('.cerb-search-trigger')
		.cerbSearchTrigger()
		;
});
</script>