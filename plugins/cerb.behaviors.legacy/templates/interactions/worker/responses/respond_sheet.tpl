{$element_id = uniqid()}
<div class="cerb-form-builder-response-sheet" id="response_{$element_id}">
	<table cellpadding="0" cellspacing="0" class="cerb-data-table">
		{if $layout.headings}
		<thead>
			<tr>
				{foreach from=$columns item=column name=columns}
				{if $layout.title_column == $column.key}
				{else}
				<th data-column-key="{$column.key}" data-column-type="{$column._type}">{$column.label}</th>
				{/if}
				{/foreach}
			</tr>
		</thead>
		{/if}

		{foreach from=$rows item=row name=rows}
			<tbody>
			{if $layout.selection}
				<tr>
					<td rowspan="{if $layout.title_column}3{else}2{/if}" colspan="1" style="width:20px;text-align:center;">
						<input type="checkbox" name="_selection" value="{$row._selection nofilter}">
					</td>
				</tr>
			{/if}
			{if $layout.title_column}
				{$column = $columns[$layout.title_column]}
				<tr>
					<td colspan="{$columns|count-1}" style="padding:0 0 0 5px;font-size:1.1em;font-weight:bold;">{$row[$column.key] nofilter}</td>
				</tr>
			{/if}
			<tr>
				{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{else}
						<td style="{if $column.params.bold}font-weight:bold;{/if}">{$row[$column.key] nofilter}</td>
					{/if}
				{/foreach}
			</tr>
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
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