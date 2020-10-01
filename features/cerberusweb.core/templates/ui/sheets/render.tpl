<div style="margin-top:5px;">
	<table cellpadding="0" cellspacing="0" style="width:100%;" class="cerb-sheet cerb-widget-data-table">
	{if $rows}
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
			<tbody class="cerb-sheet--row">
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
	{else}
		<tr>
			<td>
				({'common.data.no'|devblocks_translate|lower})
			</td>
		</tr>
	{/if}
	</table>

	{if $paging && $paging.page.of > 1}
	<span style="float:right;margin-top:5px;">
		{if array_key_exists('first', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.first}">&lt;&lt;</a>{/if}
		{if array_key_exists('prev', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.prev}">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>{/if}
		(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
		 of {$paging.page.rows.of})
		{if array_key_exists('next', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.next}">{'common.next'|devblocks_translate|capitalize}&gt;</a>{/if}
		{if array_key_exists('last', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.last}">&gt;&gt;</a>{/if}
	</span>
	{/if}
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	var $sheet = $script.prev('div').find('> .cerb-sheet');

	$sheet.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved cerb-peek-deleted', function(e) {
			e.stopPropagation();
			$sheet.trigger($.Event('cerb-sheet--refresh'));
		})
	;

	$sheet.find('.cerb-search-trigger')
		.cerbSearchTrigger()
	;

	$sheet.parent().find('.cerb-paging')
		.click(function() {
			var $this = $(this);
			var page = $this.attr('data-page');

			if(null == page)
				return;

			var evt = $.Event('cerb-sheet--page-changed', { page: page });
			$sheet.trigger(evt);
		})
	;
});
</script>