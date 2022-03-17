{$element_id = uniqid()}
<div class="cerb-data-sheet" id="cardSheet_{$element_id}">
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
	
	{if $layout.paging && $paging && $paging.page.of > 1}
	<div style="text-align:right;margin-top:5px;">
		(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
		 of {$paging.page.rows.of}) 
	</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $response = $('#cardSheet_{$element_id}');
	var $column = $response.closest('.cerb-board-column');
	var $board = $column.closest('.cerb-board');
	
	$response.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-links-changed', function(e) {
			$column.trigger('cerb-refresh');
		})
		.on('cerb-peek-saved', function(e) {
			var $card = $board.find('div.cerb-board-card[data-context="' + e.context + '"][data-context-id=' + e.id + ']')
				.closest('div.cerb-board-card')
			;

			$card.trigger('cerb-refresh');
		})
		.on('cerb-peek-deleted', function(e) {
			var $card = $board.find('div.cerb-board-card[data-context="' + e.context + '"][data-context-id=' + e.id + ']')
				.closest('div.cerb-board-card')
			;
			$card.remove();
		})	
		;

	$response.find('.cerb-search-trigger')
		.cerbSearchTrigger()
		;

});
</script>