{$show_subtotals = !(!$view->renderSubtotals || DevblocksPlatform::strStartsWith($view->renderSubtotals,'__'))}
<div style="display:flex;flex-flow:row wrap;">
	<div style="flex:0 0 250px;padding-right:5px;{if !$show_subtotals}display:none;{/if}" id="view{$view->id}_sidebar"></div>
	<div style="flex:1 1 250px;">
		{include file=$view_template}
	</div>
</div>

{if $show_subtotals}
<script type="text/javascript">
$(function() {
	$('#view{$view->id}_sidebar').append(Devblocks.getSpinner());
	
	var formData = new FormData();
	formData.set('c', 'internal');
	formData.set('a', 'invoke');
	formData.set('module', 'worklists');
	formData.set('action', 'subtotal');
	formData.set('view_id', '{$view->id}');
	
	genericAjaxPost(formData, null, null, function(html) {
		$('#view{$view->id}_sidebar')
			.html(html)
			.fadeTo('normal',1.0)
		});
});
</script>
{/if}