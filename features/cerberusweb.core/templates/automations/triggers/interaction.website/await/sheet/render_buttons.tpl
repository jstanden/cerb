{$is_selection_enabled = false}

<div>
	<div class="cerb-sheet-buttons">
	{if $rows}
		{foreach from=$rows item=row name=rows}
			<div class="cerb-sheet-buttons--button cerb-sheet--row">
				{if $layout.title_column}
				{$column = $columns[$layout.title_column]}
					<div class="cerb-sheet-buttons--title">{$row[$column.key] nofilter}</div>
				{/if}

				{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{elseif $column._type == 'selection'}
						{$is_selection_enabled = true}
						{$row[$column.key]|replace:'${SHEET_SELECTION_KEY}':{$sheet_selection_key|default:'_selection'} nofilter}
					{else}
						{$row[$column.key] nofilter}
					{/if}
				{/foreach}
			</div>
		{/foreach}
	{/if}
	</div>

	{if $paging && $paging.page.of > 1}
	<span class="cerb-sheet-paging">
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
{
	var $script = document.querySelector('#{$script_uid}');
	var $sheet = $script.parentElement.querySelector('.cerb-sheet-buttons');

	{if $is_selection_enabled}
	$$.forEach($sheet.querySelectorAll('.cerb-sheet-buttons--button'), function(index, $button) {
		$$.disableSelection($button);
		
		$button.addEventListener('click', function(e) {
			e.stopPropagation();

			if('a' === e.target.nodeName.toLowerCase())
				return;

			var $checkbox = $button.querySelector('input[type=radio], input[type=checkbox]');

			var is_multiple = 'checkbox' === $checkbox.attributes.type.value.toLowerCase();

			// If our target was something other than the input toggle
			if($checkbox !== e.target) {
				$checkbox.checked = !$checkbox.checked;
			}

			$sheet.dispatchEvent(
				$$.createEvent(
					'cerb-sheet--selection',
					{
						ui: {
							item: $checkbox 
						},
						is_multiple: is_multiple,
						selected: $checkbox.checked
					}
				)
			);

			var row_selections = [];

			var $checkboxes = $sheet.querySelectorAll('input[type=radio]:checked, input[type=checkbox]:checked');

			$$.forEach($checkboxes, function(index, $e) {
				row_selections.push($e.value);
			});

			$sheet.dispatchEvent(
				$$.createEvent('cerb-sheet--selections-changed', { row_selections: row_selections, is_multiple: is_multiple })
			);
		});
	});
	{/if}
}
</script>