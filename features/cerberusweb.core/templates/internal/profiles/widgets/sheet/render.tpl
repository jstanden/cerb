<div id="widget{$widget->id}">
	<table cellpadding="0" cellspacing="0" style="width:100%;" class="cerb-widget-data-table">
		{if $show_headings}
		<tr>
			{foreach from=$columns item=column name=columns}
			<th data-column-key="{$column.key}" data-column-type="{$column.type}">{$column.label}</th>
			{/foreach}
		</tr>
		{/if}
	{foreach from=$rows item=row name=rows}
		<tr>
		{foreach from=$columns item=column name=columns}
			<td style="{if $column.style.weight}font-weight:{$column.style.weight};{/if}">{$row[$column.key] nofilter}</td>
		{/foreach}
		</tr>
	{/foreach}
	</table>
	
	{if $paging}
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
			var $tab = $this.closest('.cerb-profile-layout');
			var page = $this.attr('data-page');
			
			if(undefined == page)
				return;
			
			e.stopPropagation();
			
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