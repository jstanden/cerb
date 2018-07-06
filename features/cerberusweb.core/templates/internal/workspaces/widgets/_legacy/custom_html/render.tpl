{$html nofilter}

<script type="text/javascript">
$(function() {
	var $widget = $('#workspaceWidget{$widget->id}');
	$widget.find('.cerb-peek-trigger').cerbPeekTrigger();
	$widget.find('.cerb-search-trigger').cerbSearchTrigger();
});
</script>