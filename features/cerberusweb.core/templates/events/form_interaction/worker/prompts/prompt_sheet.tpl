{$element_id = uniqid('prompt_')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-sheet" id="{$element_id}">
	<h6>{$label}</h6>

	{if $rows}
	<table cellpadding="0" cellspacing="0" style="width:100%;" class="cerb-sheet cerb-data-table">
		{if $layout.headings}
			<thead>
			<tr>
				{if $layout.selection}
					<th style="width:20px;text-align:center;"></th>
				{/if}

				{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{else}
						<th data-column-key="{$column.key}" data-column-type="{$column.type}">{$column.label}</th>
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
						{if $layout.selection.mode == 'multiple'}
						<input type="checkbox" name="prompts[{$var}][]" value="{$row._selection nofilter}">
						{else}
						<input type="radio" name="prompts[{$var}][]" value="{$row._selection nofilter}">
						{/if}
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
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');
	var $sheet = $prompt.find('.cerb-sheet');

	$prompt.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;

	$prompt.find('.cerb-search-trigger')
		.cerbSearchTrigger()
	;

	{if $layout.selection}
	$sheet.find('tbody')
		.disableSelection()
		.on('click', function(e) {
			e.stopPropagation();

			var $target = $(e.target);

			if($target.is('a'))
				return;

			if($target.is('input'))
				return;

			if($target.hasClass('cerb-peek-trigger'))
				return;

			if($target.hasClass('cerb-search-trigger'))
				return;

			// If removing selected, add back hover

			var $checkbox = $target.closest('tbody').find('input[type=checkbox],input[type=radio]');

			if($checkbox.is(':checked')) {
				$checkbox.prop('checked', false);
			} else {
				$checkbox.prop('checked', true);
			}
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