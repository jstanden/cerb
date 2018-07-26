<style type="text/css">
TABLE.cerb-widget-data-table tr:nth-child(odd) th {
	font-weight:bold;
	font-size:1.25em;
	text-align: left;
	padding:5px;
}

TABLE.cerb-widget-data-table tr td {
	padding:5px;
}

TABLE.cerb-widget-data-table tr:nth-child(odd) td {
	background-color: rgb(245,245,245);
}
</style>

<div id="widget{$widget->id}">
	<table cellpadding="0" cellspacing="0" style="width:100%;" class="cerb-widget-data-table">
		<tr>
			{foreach from=$results.data.columns key=column_key item=column name=columns}
			<th>{$column.label}</th>
			{/foreach}
		</tr>
	{$last_row = []}
	{foreach from=$results.data.rows item=row name=rows}
		{if !$smarty.foreach.rows.first}
		{* Subtotals *}
		{/if}
		
		<tr>
		{$is_group_ended = false}
		{foreach from=$results.data.columns key=column_key item=column name=columns}
			{$value = $row.$column_key}
			<td>
				{capture name=value}
					{if 'context' == $column.type}
						{$context = null}
						{$context_id = null}
						
						{if $column.type_options.context_key && $column.type_options.context_id_key}
							{$context_key = $column.type_options.context_key}
							{$context_id_key = $column.type_options.context_id_key}
							{$context = $row.$context_key}
							{$context_id = $row.$context_id_key}
						{elseif $column.type_options.context && $column.type_options.context_id_key}
							{$context = $column.type_options.context}
							{$context_id_key = $column.type_options.context_id_key}
							{$context_id = $row.$context_id_key}
						{else}
							{$value}
						{/if}
						
						{if $context_id}
						<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{$context}" data-context-id="{$context_id}">{$value}</a>
						{else}
						{$value}
						{/if}
					{elseif 'number_minutes' == $column.type}
						{{$value*60}|devblocks_prettysecs:2}
					{elseif 'number_seconds' == $column.type}
						{$value|devblocks_prettysecs:2}
					{elseif 'worker' == $column.type}
						{$context = CerberusContexts::CONTEXT_WORKER}
						{$context_id_key = $column.type_options.context_id_key}
						{$context_id = $row.$context_id_key}
						{if $context_id}
						<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{$context}" data-context-id="{$context_id}">{$value}</a>
						{else}
						{$value}
						{/if}
					{else}
						{$value}
					{/if}
				{/capture}
			
				{if !$smarty.foreach.columns.last}
					{$slice = array_slice($row,0,$smarty.foreach.columns.iteration)}
					{$parent_slice = array_slice($last_row,0,$smarty.foreach.columns.iteration)}
					
					{if $slice == $parent_slice}
						{$is_group_ended = true}
						{*<span style="opacity:0.3;">{$smarty.capture.value nofilter}</span>*}
						{$smarty.capture.value nofilter}
					{else}
						{$smarty.capture.value nofilter}
					{/if}
				{else}
					{$smarty.capture.value nofilter}
				{/if}
			</td>
		{/foreach}
		</tr>
		
		{$last_row = $row}
	{/foreach}
	</table>
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
	
	$widget.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
});
</script>