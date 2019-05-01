{$element_id = uniqid()}
<div class="cerb-form-builder-response-sheet" id="response_{$element_id}">
	<table cellpadding="0" cellspacing="0" class="cerb-data-table">
		<tr>
			{foreach from=$columns item=column name=columns}
			<th data-column-key="{$column.key}" data-column-type="{$column.type}">{$column.label}</th>
			{/foreach}
		</tr>
	{foreach from=$rows item=row name=rows}
		<tr>
			{foreach from=$columns item=column name=columns}
			<td style="{if $column.style.weight}font-weight:{$column.style.weight};{/if}">{$row[$column.key] nofilter}</td>
			{/foreach}
		</tr>
	{/foreach}
	</table>
	
	{*
	{if $paging}
	<div style="text-align:right;margin-top:5px;">
		{if array_key_exists('first', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.first}">&lt;&lt;</a>{/if}
		{if array_key_exists('prev', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.prev}">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>{/if}
		(Showing {$paging.page.rows.from}-{$paging.page.rows.to} of {$paging.page.rows.of}) 
		{if array_key_exists('next', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.next}">{'common.next'|devblocks_translate|capitalize}&gt;</a>{/if}
		{if array_key_exists('last', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.last}">&gt;&gt;</a>{/if}
	</div>
	{/if}
	*}
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