<script type="text/javascript">
$('FORM#viewForm{$view->id} TABLE.worklistBody TBODY').click(function(e) {
	$target = $(event.target);

	// Are any of our parents an anchor tag?	
	$parents = $target.parents('a');
	if($parents.length > 0) {
		$target = $parents[$parents.length-1]; // 0-based
	}
	
	if($target.is(':input,:button,a')) {
		// Ignore these clicks
	} else {
		e.preventDefault();
		$chk=$(this).find('input:checkbox:first');
		if(!$chk)
			return;
		$chk.attr('checked', !$chk.is(':checked'));
	}
	e.stopPropagation();
});
</script>