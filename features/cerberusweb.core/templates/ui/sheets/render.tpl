{$sheet_uid = "sheet{uniqid()}"}
<div id="{$sheet_uid}" style="margin-top:5px;">
	<table cellpadding="0" cellspacing="0" style="width:100%;" class="cerb-widget-data-table">
		{if $layout.headings}
		<thead>
			<tr>
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
	
	{if $paging}
	<div style="text-align:right;margin-top:5px;">
		(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
		 of {$paging.page.rows.of}) 
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
});
</script>