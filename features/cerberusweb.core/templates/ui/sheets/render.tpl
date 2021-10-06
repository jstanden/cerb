{$is_selection_enabled = false}

<div style="margin-top:5px;">
	<table cellpadding="0" cellspacing="0" style="width:100%;" class="cerb-sheet cerb-widget-data-table">
	{if $rows}
		{if $layout.headings}
		<thead>
			<tr>
				{foreach from=$columns item=column name=columns}
				{if $layout.title_column == $column.key}
				{elseif $column._type == 'selection'}
					<th style="width:20px;text-align:center;">
						{if $column.params.mode != 'single'}
						<input type="checkbox" title="Toggle all" data-cerb-select-all>
						{/if}
					</th>
				{else}
				<th data-column-key="{$column.key}" data-column-type="{$column._type}">{$column.label}</th>
				{/if}
				{/foreach}
			</tr>
		</thead>
		{/if}

		{foreach from=$rows item=row name=rows}
			<tbody class="cerb-sheet--row">
				{foreach from=$columns item=column name=columns}
					{if $column._type == 'selection'}
					{$is_selection_enabled = true}
					<tr>
						<td rowspan="{if $layout.title_column}3{else}2{/if}" colspan="1" style="width:20px;text-align:center;">
							{if $column.params.mode == 'single'}
							<input type="radio" name="{$sheet_selection_key|default:'_selection'}" value="{$row[$column.key]}" {if $row[$column.key] == $default}checked="checked"{/if}>
							{else}
							<input type="checkbox" name="{$sheet_selection_key|default:'_selection'}[]" value="{$row[$column.key]}" {if is_array($default) && in_array($row[$column.key], $default)}checked="checked"{/if}>
							{/if}
						</td>
					</tr>
					{/if}
				{/foreach}

				{if $layout.title_column}
				{$column = $columns[$layout.title_column]}
				<tr>
					<td colspan="{$columns|count-1}" style="padding:0 0 0 5px;font-size:1.1em;font-weight:bold;">{$row[$column.key] nofilter}</td>
				</tr>
				{/if}

				<tr>
				{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{elseif $column._type == 'selection'}
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
	
	$sheet.find('[data-cerb-select-all]').on('change', function(e) {
		e.preventDefault();
		
		var $checkbox = $(this);
		var is_checked = $checkbox.is(':checked');
		var $table = $checkbox.closest('table.cerb-sheet');
		var $tbody = $table.find('tbody');
		var row_selections = [];
		
		$tbody.find('input:checkbox').each(function() {
			var $checkbox = $(this);
			$checkbox.prop('checked', is_checked ? 'checked' : null);
			
			if(is_checked)
				row_selections.push($checkbox.val());

			var event_data = {
				ui: {
					item: $checkbox
				},
				is_multiple: true, 
				no_toolbar_update: true,
				selected: is_checked
			};
			
			$sheet.trigger(
				$.Event('cerb-sheet--selection', event_data)
			);
		});

		$sheet.trigger(
			$.Event('cerb-sheet--selections-changed', { row_selections: row_selections, is_multiple: true })
		);
	});

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

			// [TODO] Is this second variation really needed?
			var row_selections = [];

			$tbody.closest('table.cerb-sheet')
				.find('input[type=radio]:checked ,input[type=checkbox]:checked')
				.each(function() {
					row_selections.push($(this).val());
				})
				;

			// [TODO] Can we include a label/avatar with selections?
			$sheet.trigger(
				$.Event('cerb-sheet--selections-changed', { row_selections: row_selections, is_multiple: is_multiple })
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