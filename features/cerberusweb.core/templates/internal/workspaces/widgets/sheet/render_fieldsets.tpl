<div id="widget{$widget->id}" class="cerb-data-sheet">
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
		{if array_key_exists('first', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.first}">&lt;&lt;</a>{/if}
		{if array_key_exists('prev', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.prev}">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>{/if}
		(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
		 of {$paging.page.rows.of}) 
		{if array_key_exists('next', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.next}">{'common.next'|devblocks_translate|capitalize}&gt;</a>{/if}
		{if array_key_exists('last', $paging.page)}<a href="javascript:;" class="cerb-paging" data-page="{$paging.page.last}">&gt;&gt;</a>{/if}
	</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
	
	$widget.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$widget.find('.cerb-search-trigger')
		.cerbSearchTrigger()
		;
	
	$widget.find('.cerb-paging')
		.click(function(e) {
			var $this = $(this);
			var $tab = $this.closest('.cerb-workspace-layout');
			var page = $this.attr('data-page');
			
			if(undefined == page)
				return;
			
			var evt = $.Event('cerb-widget-refresh');
			evt.widget_id = {$widget->id};
			evt.refresh_options = {
				'page': page
			};
			
			$tab.triggerHandler(evt);
		})
		;
});
</script>