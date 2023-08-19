{$is_selection_enabled = false}
{$sheet_uid = "sheet{uniqid()}"}
<div id="{$sheet_uid}" class="cerb-data-sheet">
	<table width="100%" cellspacing="0" cellpadding="0" border="0" class="cerb-data-sheet--fieldset">
	{foreach from=$rows item=row name=rows}
		<tbody class="cerb-sheet--row">
		{foreach from=$columns item=column name=columns}
		{$cell = $row[$column.key]}
		{if is_a($cell, 'DevblocksSheetCell')}
			{$color = $cell->getAttr('color')}
			{$text_color = $cell->getAttr('text_color')}
			{$text_size = $cell->getAttr('text_size')}
			{$style_css = "{if $column.params.bold}font-weight:bold;{/if}{if $color}background-color:{$color};{/if}{if $text_color}color:{$text_color};{/if}{if $text_size}font-size:{$text_size}%;{/if}"}
			{$class_css = "{if $column._type == 'markdown'}commentBodyHtml{/if}"}
			<tr class="cerb-data-sheet--field">
				{if $column._type == 'selection'}
					{$is_selection_enabled = true}
					<td colspan="{if $layout.title_column}3{else}2{/if}" rowspan="{$columns|count}" style="width:1em;text-align:center;vertical-align:middle;">
						{$row[$column.key]|replace:'${SHEET_SELECTION_KEY}':{$sheet_selection_key|default:'_selection'} nofilter}
					</td>
				{/if}
			
				{if $column._type == 'selection'}
				{elseif $layout.title_column && $column.key == $layout.title_column}
					{if ((!$is_selection_enabled && 0 == $smarty.foreach.columns.index) || ($is_selection_enabled && 1 == $smarty.foreach.columns.index)) && 'icon' == $column._type}
						<td class="cerb-data-sheet--field-title" nowrap rowspan="{$columns|count}" style="width:1em;vertical-align:top;{$style_css}">
							{$cell nofilter}
						</td>
					{else}
						<td class="cerb-data-sheet--field-title" colspan="2" style="{$style_css}">
							{$cell nofilter}
						</td>
					{/if}
				{else}
					{if $layout.headings}
					<td class="cerb-data-sheet--field-label">
						{$column.label}:
					</td>
					<td class="{$class_css}" style="{$style_css}">
						{$cell nofilter}
					</td>
					{else}
					<td colspan="2" class="{$class_css}" style="{$style_css}">
						{$cell nofilter}
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
	var $sheet = $('#{$sheet_uid}');
	
	$sheet.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$sheet.find('.cerb-search-trigger')
		.cerbSearchTrigger()
		;

	$sheet.find('.cerb-bot-trigger, .cerb-interaction-trigger')
		.cerbBotTrigger({
			done: function(e) {
				var evt = $.Event('cerb-sheet--interaction-done', e);
				evt.type = 'cerb-sheet--interaction-done';
				$sheet.trigger(evt);
			}
		})
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

	{if $is_selection_enabled}
	$sheet.find('tbody')
		.disableSelection()
		.on('click', function(e) {
			e.stopPropagation();
			console.log(e);

			var $target = $(e.target);

			if($target.is('a'))
				return;

			if($target.hasClass('cerb-peek-trigger'))
				return;

			if($target.hasClass('cerb-search-trigger'))
				return;

			var $tbody = $(this);

			// If removing selected, add back hover

			var $checkbox = $tbody.find('input[type=radio], input[type=checkbox]');

			// If our target was something other than the input toggle
			if(!$checkbox.is($target)) {
				if ($checkbox.is(':checked')) {
					$checkbox.prop('checked', false);
				} else {
					$checkbox.prop('checked', true);
				}
			}

			var is_multiple = $checkbox.is('[type=checkbox]');

			// [TODO] Can we include a label/avatar with selections?
			$sheet.trigger(
				$.Event('cerb-sheet--selection', { ui: { item: $checkbox }, is_multiple: is_multiple, selected: $checkbox.prop('checked') })
			);

			let row_selections = [];
            let rows_visible = [];

			$tbody.closest('table.cerb-data-sheet--fieldset')
				.find('input[type=radio] ,input[type=checkbox]')
				.each(function() {
                    let $this = $(this);
                    
                    rows_visible.push($this.val());
                    
                    if($this.is(':checked')) {
                        row_selections.push($this.val());
                    }
				})
			;

			$sheet.trigger(
				$.Event('cerb-sheet--selections-changed', {
					row_selections: row_selections,
					rows_visible: rows_visible,
					is_multiple: is_multiple
                })
			);
		})
		.hover(
			function(e) {
				e.stopPropagation();
				$(this).addClass('hover');
			},
			function(e) {
				e.stopPropagation();
				$(this).removeClass('hover');
			}
		)
	;
	{/if}	
});
</script>