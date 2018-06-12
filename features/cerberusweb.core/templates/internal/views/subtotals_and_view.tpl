{$show_subtotals = !(!$view->renderSubtotals || DevblocksPlatform::strStartsWith($view->renderSubtotals,'__'))}
<div style="display:flex;flex-flow:row wrap;">
	<div style="flex:0 0 250px;padding-right:5px;{if !$show_subtotals}display:none;{/if}" id="view{$view->id}_sidebar">
		{if $show_subtotals}
		{$view->renderSubtotals()}
		{/if}
	</div>
	<div style="flex:1 1 250px;">
		{include file=$view_template}
	</div>
</div>