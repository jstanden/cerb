{$is_selection_enabled = false}

<div style="box-shadow:0 0 5px rgb(200,200,200);">
	<div style="margin-top:5px;">
		<table cellpadding="0" cellspacing="0" style="width:100%;" class="cerb-sheet">
		{if $rows}
			{if $layout.headings}
			<thead>
				<tr>
					{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{elseif $column._type == 'selection'}
						<th style="width:20px;text-align:center;"></th>
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
								<input type="radio" name="{$sheet_selection_key|default:'_selection'}" value="{$row[$column.key]}">
								{else}
								<input type="checkbox" name="{$sheet_selection_key|default:'_selection'}[]" value="{$row[$column.key]}">
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
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
{
	var $script = document.querySelector('#{$script_uid}');
	var $sheet = $script.parentElement.querySelector('.cerb-sheet');
	var $prompt = $sheet.closest('.cerb-interaction-popup--form-elements-sheet');
	
	$$.disableSelection($prompt);
	
	$prompt.addEventListener('click', function(e) {
		e.stopPropagation();
		
		var $target = e.target;
		
		if($target.hasAttribute('data-page')) {
			var page = $target.getAttribute('data-page');

			var evt = $$.createEvent('cerb-sheet--page-changed', { "page": page });
			$prompt.dispatchEvent(evt);
			
		} else {
			{if $is_selection_enabled}
			var $tbody = $target.closest('tbody');
			
			if(!$tbody)
				return;
			
			if('a' === e.target.nodeName.toLowerCase() || 'input' === e.target.nodeName.toLowerCase())
				return;

			// If removing selected, add back hover

			var $checkbox = $tbody.querySelector('input[type=radio], input[type=checkbox]');

			// If our target was something other than the input toggle
			if($checkbox !== e.target) {
				$checkbox.checked = !$checkbox.checked;
			}

			var is_multiple = 'checkbox' === $checkbox.attributes.type.value.toLowerCase();

			$sheet.dispatchEvent(
				$$.createEvent('cerb-sheet--selection', { ui: { item: $checkbox }, is_multiple: is_multiple })
			);

			var row_selections = [];

			var $checkboxes = $tbody.closest('table.cerb-sheet').querySelectorAll('input[type=radio]:checked ,input[type=checkbox]:checked');

			$$.forEach($checkboxes, function(index, $e) {
				row_selections.push($e.value);
			});

			$sheet.dispatchEvent(
				$$.createEvent('cerb-sheet--selections-changed', { row_selections: row_selections, is_multiple: is_multiple })
			);
			{/if}
		}
	});
}
</script>