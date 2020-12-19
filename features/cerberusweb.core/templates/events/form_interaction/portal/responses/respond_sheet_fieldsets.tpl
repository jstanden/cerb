{$element_id = uniqid()}
<div class="cerb-portal-form-response-sheet cerb-portal-data" id="response_{$element_id}">
	<table width="100%" cellspacing="0" cellpadding="0" border="0" class="cerb-portal-data--fieldset">
	{foreach from=$rows item=row name=rows}
		<tbody>
		{foreach from=$columns item=column name=columns}
		{$value = $row[$column.key]}
		{if $value}
			<tr class="cerb-portal-data--field">
				{if $layout.title_column && $column.key == $layout.title_column}
					<td class="cerb-portal-data--field-title" colspan="2">
						{$value nofilter}
					</td>
				{else}
					{if $layout.headings}
					<td class="cerb-portal-data--field-label">
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
	
	{if $layout.paging && $paging && $paging.page.of > 1}
	<div style="text-align:right;margin-top:5px;">
		(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
		 of {$paging.page.rows.of}) 
	</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $response = $('#response_{$element_id}');
	
	$response.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$response.find('.cerb-search-trigger')
		.cerbSearchTrigger()
		;

});
</script>